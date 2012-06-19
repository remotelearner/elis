<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * PM class instance grade export compatible with the original PM grade
 * export for Moodle 1.9
 */
class rlip_exportplugin_version1elis extends rlip_exportplugin_base {
    //recordset for tracking current export record
    var $recordset = null;
    //complete status string, used to avoid calling get_string for each row
    var $completestatusstring = '';

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    function init($targetstarttime = 0, $lastruntime = 0) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));

        //columns that are always displayed
        $columns = array(get_string('header_firstname', 'rlipexport_version1'),
                         get_string('header_lastname', 'rlipexport_version1'),
                         get_string('header_username', 'rlipexport_version1'),
                         get_string('header_useridnumber', 'rlipexport_version1'),
                         get_string('header_courseidnumber', 'rlipexport_version1'),
                         get_string('header_startdate', 'rlipexport_version1'),
                         get_string('header_enddate', 'rlipexport_version1'),
                         get_string('header_status', 'rlipexport_version1elis'),
                         get_string('header_grade', 'rlipexport_version1'),
                         get_string('header_letter', 'rlipexport_version1'));

        //query parameters
        $params = array(student::STUSTATUS_PASSED);

        //sql time condition
        $time_condition = '';

        //determine if we're in incremental or non-incremental mode
        $nonincremental = get_config('rlipexport_version1elis', 'nonincremental');
        if (empty($nonincremental)) {
            if ($this->manual) {
                //manual export incremental mode

                //get string delta
                $incrementaldelta = get_config('rlipexport_version1elis', 'incrementaldelta');
                //convert to number of seconds
                $numsecs = rlip_time_string_to_offset($incrementaldelta);

                //add to query parameters
                $params[] = time() - $numsecs;

                //add query fragment
                $time_condition = 'AND stu.completetime >= ?';
            } else {
                //TODO: implement
            }
        }

        //initialize our recordset to the core data
        $sql = "SELECT u.firstname,
                       u.lastname,
                       u.username,
                       u.idnumber,
                       crs.idnumber AS crsidnumber,
                       stu.enrolmenttime,
                       stu.completetime,
                       stu.grade,
                       mdlcrs.id AS mdlcrsid
                FROM {".user::TABLE."} u
                JOIN {".student::TABLE."} stu
                  ON u.id = stu.userid
                JOIN {".pmclass::TABLE."} cls
                  ON stu.classid = cls.id
                JOIN {".course::TABLE."} crs
                  ON cls.courseid = crs.id
                LEFT JOIN {".classmoodlecourse::TABLE."} clsmdl
                  ON cls.id = clsmdl.classid
                LEFT JOIN {course} mdlcrs
                  ON clsmdl.moodlecourseid = mdlcrs.id
                WHERE stu.completestatusid = ?
                {$time_condition}
                ORDER BY u.idnumber,
                         crs.idnumber,
                         stu.completetime,
                         stu.grade DESC,
                         cls.idnumber";
        $this->recordset = $DB->get_recordset_sql($sql, $params);

        //write out header
        $this->fileplugin->write($columns);

        //load string to prevent calling get_string for every record
        $this->completestatusstring = get_string('completestatusstring', 'rlipexport_version1elis');
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    function has_next() {
        return $this->recordset->valid();
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    function next() {
        //fetch the current record
        $record = $this->recordset->current();

        //set up our grade item
        $grade_item = new stdClass;
        if ($record->mdlcrsid !== NULL) {
            $grade_item->courseid = $record->mdlcrsid;
        } else {
            $grade_item->courseid = SITEID;
        }
        $grade_item->gradetype = GRADE_TYPE_VALUE;
        $grade_item->grademin = 0;
        $grade_item->grademax = 100;

        //write the line out of a file
        $csvrecord = array($record->firstname,
                           $record->lastname,
                           $record->username,
                           $record->idnumber,
                           $record->crsidnumber,
                           date("M/d/Y", $record->enrolmenttime),
                           date("M/d/Y", $record->completetime),
                           $this->completestatusstring,
                           $record->grade,
                           grade_format_gradevalue($record->grade, $grade_item, true, GRADE_DISPLAY_TYPE_LETTER, 5));

        //move on to the next data record
        $this->recordset->next();

        return $csvrecord;
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    function close() {
        //close our current recordset
        $this->recordset->close();
        $this->recordset = NULL;
    }

    /**
     * Add custom entries to the Settings block tree menu
     *
     * @param object $adminroot The main admin tree root object
     * @param string $parentname The name of the parent node to add children to
     */
     function admintree_setup(&$adminroot, $parentname) {
        global $CFG;

        //create a link to the page for configuring field mappings
        $displaystring = get_string('configfieldstreelink', 'rlipexport_version1elis');
        $url = $CFG->wwwroot.'/blocks/rlip/exportplugins/version1elis/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        //add it to the tree
        $adminroot->add($parentname, $page);
    }
}