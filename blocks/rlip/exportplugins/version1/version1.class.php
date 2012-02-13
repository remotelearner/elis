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

require_once($CFG->dirroot.'/blocks/rlip/rlip_exportplugin.class.php');

/**
 * Moodle course grade export compatible with the original Moodle-only grade
 * export for Moodle 1.9 
 */
class rlip_exportplugin_version1 extends rlip_exportplugin_base {
    //mapping of profile field ids to data types
    var $datatypes = array();
    //mapping of profile field ids to default values
    var $defaultdata = array();
    //stores which profile fields contain a date and time value
    var $showtime = array();
    //recordset for tracking current export record
    var $recordset = null;

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     */
    function init() {
        global $CFG, $DB;

        //columns that are always displayed
        $columns = array(get_string('header_firstname', 'rlipexport_version1'),
                         get_string('header_lastname', 'rlipexport_version1'),
                         get_string('header_username', 'rlipexport_version1'),
                         get_string('header_useridnumber', 'rlipexport_version1'),
                         get_string('header_courseidnumber', 'rlipexport_version1'),
                         get_string('header_startdate', 'rlipexport_version1'),
                         get_string('header_enddate', 'rlipexport_version1'),
                         get_string('header_grade', 'rlipexport_version1'),
                         get_string('header_letter', 'rlipexport_version1'));

        //track extra SQL and parameters needed for custom fields
        $extra_select = '';
        $extra_joins = '';
        $extra_params = array();

        //query to fetch all configured profile fields
        $sql = "SELECT export.fieldid,
                       export.header,
                       field.datatype,
                       field.defaultdata,
                       field.param3
                FROM {block_rlip_version1_export} export
                JOIN {user_info_field} field
                  ON export.fieldid = field.id
                ORDER BY export.fieldorder";

        if ($recordset = $DB->get_recordset_sql($sql)) {
            foreach ($recordset as $record) {
                /**
                 * Calculate information we'll need to format / transform records
                 */

                //field id used to index stored information
                $fieldid = $record->fieldid;
                //store the data type
                $this->datatypes[$fieldid] = $record->datatype;
                //store the default value
                $this->defaultdata[$fieldid] = $record->defaultdata;
                //track which fields show date and time values
                if ($record->datatype == 'datetime' && $record->param3 == 1) {
                    $this->showtime[$record->fieldid] = 1;
                }

                /**
                 * Calculate extra SQL fragments / parameters
                 */

                //extra columns we'll need to display profile field values
                $extra_select .= ",
                                   profile_data_{$record->fieldid}.data
                                   AS profile_field_{$record->fieldid}";
                //extra joins we''l need to display profile filed values
                $extra_joins = "{$extra_joins}
                                LEFT JOIN {user_info_data} profile_data_{$record->fieldid}
                                  ON profile_data_{$record->fieldid}.fieldid = ?
                                  AND u.id = profile_data_{$record->fieldid}.userid";
                //id of the appropriate custom field
                $extra_params[] = $fieldid;

                /**
                 * Calculate extra column headers
                 */
                $columns[] = $record->header;
            }
        }

        //initialize our recordset to the core data
        $sql = "SELECT u.firstname,
                       u.lastname,
                       u.username,
                       u.idnumber AS usridnumber,
                       c.shortname AS crsidnumber,
                       c.startdate AS timestart,
                       gg.finalgrade AS usergrade,
                       gi.id AS gradeitemid
                       {$extra_select}
                FROM {grade_items} gi
                JOIN {grade_grades} gg
                  ON gg.itemid = gi.id
                JOIN {user} u
                  ON gg.userid = u.id
                JOIN {course} c
                  ON c.id = gi.courseid
                {$extra_joins}
                WHERE itemtype = 'course'
                AND u.deleted = 0";
 
        $this->recordset = $DB->get_recordset_sql($sql, $extra_params);

        //write out header
        $this->fileplugin->write($columns);
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
     * Transforms a custom field value for display in the export file
     *
     * @param int $fieldid The database record id of the custom field
     * @param string $value The custom field value
     * @return string The formatted string
     */
    function transform_value($fieldid, $value) {
        if ($value === NULL) {
            //not set, so use the default value
            $value = $this->defaultdata[$fieldid];
        }

        if ($this->datatypes[$fieldid] == 'checkbox') {
            //format as a yes or no value
            if ($value == '0') {
                $value = 'no';
            } else {
                $value = 'yes';
            }
        } else if ($this->datatypes[$fieldid] == 'datetime') {
            //format as a date, with or without time
            if (!empty($this->showtime[$fieldid])) {
                $value = date('M/d/Y, h:i a', $value);
            } else {
                $value = date('M/d/Y', $value);
            }
        }

        return $value;
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    function next() {
        global $CFG, $DB;
        require_once($CFG->libdir.'/gradelib.php');

        //obtain the standardized date format
    	$format = get_string('dateformat', 'block_rlip');

        //fetch the current record
        $record = $this->recordset->current();

        /*
         * Perform necessary data transformation
         */

        //format start time
        $record->timestart = date($format, $record->timestart);
        //set end time time current date
        $record->timeend = date($format, time());

        //try to convert course grade to a letter
        $record->gradeletter = '-';
        if ($grade_item = grade_item::fetch(array('id' => $record->gradeitemid))) {
            $record->gradeletter = grade_format_gradevalue($record->usergrade, $grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
        }

        //write the line out of a file
        $csvrecord = array($record->firstname,
                           $record->lastname,
                           $record->username,
                           $record->usridnumber,
                           $record->crsidnumber,
                           $record->timestart,
                           $record->timeend,
                           $record->usergrade,
                           $record->gradeletter);

        //iterate through our list of profile fields and perform data
        //transformation on each field value
        foreach (array_keys($this->datatypes) as $fieldid) {
            $property = "profile_field_{$fieldid}";
            $value = $record->{$property};
            $value = $this->transform_value($fieldid, $value);

            $csvrecord[] = $value;
        }

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

        //clean up
        $this->datatypes = array();
        $this->defaultdata = array();
        $this->showtime = array();
        $this->recordset = null;
    }

}