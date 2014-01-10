<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * PM class instance grade export compatible with the original PM grade
 * export for Moodle 1.9
 */
class rlip_exportplugin_version1elis extends rlip_exportplugin_base {
    /**
     * @var array Mapping of user context custom field ids to default values.
     */
    public $defaultdata = array();

    /**
     * @var array Controls for datatypes.
     */
    public $controls = array();

    /**
     * @var array Stores which profile fields contain a date and time value.
     */
    public $showtime = array();

    /**
     * @var moodle_recordset Recordset for tracking current export record.
     */
    public $recordset = null;

    /**
     * @var string Complete status string, used to avoid calling get_string for each row.
     */
    public $completestatusstring = '';

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    public function init($targetstarttime = 0, $lastruntime = 0) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));

        // Columns that are always displayed.
        $columns = array(
                get_string('header_firstname', 'dhexport_version1'),
                get_string('header_lastname', 'dhexport_version1'),
                get_string('header_username', 'dhexport_version1'),
                get_string('header_useridnumber', 'dhexport_version1'),
                get_string('header_courseidnumber', 'dhexport_version1'),
                get_string('header_startdate', 'dhexport_version1'),
                get_string('header_enddate', 'dhexport_version1'),
                get_string('header_status', 'dhexport_version1elis'),
                get_string('header_grade', 'dhexport_version1'),
                get_string('header_letter', 'dhexport_version1')
        );

        // Query parameters.
        $params = array();

        // Track extra SQL and parameters needed for custom fields.
        $extra_joins = implode(" \n ", rlipexport_version1elis_extrafields::get_extra_joins());
        $extra_select = implode(', ', rlipexport_version1elis_extrafields::get_extra_select());
        if (!empty($extra_select)) {
            $extra_select = ', '.$extra_select;
        }

        // Get columns.
        $columns = array_merge($columns, rlipexport_version1elis_extrafields::get_extra_columns());

        // Add passed as completion status requirement.
        $params[] = student::STUSTATUS_PASSED;

        // Sql time condition.
        $time_condition = '';

        // Determine if we're in incremental or non-incremental mode.
        $nonincremental = get_config('dhexport_version1elis', 'nonincremental');
        if (empty($nonincremental)) {
            if ($this->manual) {
                // Manual export incremental mode.

                // Get string delta.
                $incrementaldelta = get_config('dhexport_version1elis', 'incrementaldelta');
                // Convert to number of seconds.
                $numsecs = rlip_time_string_to_offset($incrementaldelta);

                // Add to query parameters.
                $params[] = time() - $numsecs;

                // Add query fragment.
                $time_condition = 'AND stu.completetime >= ?';
            } else {
                // Scheduled export incremental mode.

                // Set up the query fragment and parameters.
                $params[] = $lastruntime;
                $time_condition = 'AND stu.completetime >= ?';
            }
        }

        // Initialize our recordset to the core data.
        $sql = "SELECT u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.username,
                       u.idnumber,
                       crs.idnumber AS crsidnumber,
                       stu.enrolmenttime,
                       stu.completetime,
                       stu.grade,
                       mdlcrs.id AS mdlcrsid
                       {$extra_select}
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
                {$extra_joins}
                WHERE stu.completestatusid = ?
                {$time_condition}
                ORDER BY u.idnumber ASC,
                         crs.idnumber ASC,
                         stu.completetime ASC,
                         stu.grade DESC,
                         cls.idnumber ASC,
                         u.username ASC";

        $this->recordset = $DB->get_recordset_sql($sql, $params);

        // Write out header.
        $this->fileplugin->write($columns);

        // Load string to prevent calling get_string for every record.
        $this->completestatusstring = get_string('completestatusstring', 'dhexport_version1elis');
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    public function has_next() {
        return $this->recordset->valid();
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    public function next() {
        // Fetch the current record.
        $record = $this->recordset->current();

        // Set up our grade item.
        $grade_item = new stdClass;
        if ($record->mdlcrsid !== null) {
            $grade_item->courseid = $record->mdlcrsid;
        } else {
            $grade_item->courseid = SITEID;
        }
        $grade_item->gradetype = GRADE_TYPE_VALUE;
        $grade_item->grademin = 0;
        $grade_item->grademax = 100;

        // Write the line out of a file.
        $csvrecord = array(
                $record->firstname,
                $record->lastname,
                $record->username,
                $record->idnumber,
                $record->crsidnumber,
                date('M/d/Y', $record->enrolmenttime),
                date('M/d/Y', $record->completetime),
                $this->completestatusstring,
                $record->grade,
                grade_format_gradevalue($record->grade, $grade_item, true, GRADE_DISPLAY_TYPE_LETTER, 5)
        );

        // Add additional data for extra fields.
        $additional_data = rlipexport_version1elis_extrafields::get_all_data($record);
        $csvrecord = array_merge($csvrecord, $additional_data);

        // Move on to the next data record.
        $this->recordset->next();

        return $csvrecord;
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    public function close() {
        // Close our current recordset.
        $this->recordset->close();
        $this->recordset = null;
    }

    /**
     * Hook for performing any final actions depending on export result
     * @param   bool  $result   The state of the export, true => success
     * @uses    $CFG
     * @todo    refactor to remove dependency on $CFG and 'export_path'
     * @return  mixed           State info on failure or null for success.
     */
    public function finish($result) {
        global $CFG;
        $obj = null;
        if ($result) {
            if ($this->fileplugin->sendtobrowser) {
                // Nothing to do here ...
                return null;
            }
            // Export successfull, move temp. file (if exists) to export path.
            $tempfile = $this->fileplugin->get_filename(true);
            if (file_exists($tempfile)) {
                $exportbase = basename($tempfile);
                $exportpath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $exportpath .= trim(get_config($this->plugin, 'export_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $outfile = $exportpath.$exportbase;
                if (!@rename($tempfile, $outfile)) {
                    $errctx = '/local/datahub/exportplugins/version1elis/version1elis.class.php::finish()';
                    $errstr = "Error renaming: '{$tempfile}' to '{$outfile}'";
                    error_log($errctx.' - '.$errstr);
                }
            }
            /*
              else {
                error_log("/local/datahub/exportplugins/version1elis/version1elis.class.php::finish() -
                            Error file: '{$tempfile}' doesn't exist!");
            }
            */
        } else {
            $obj = new stdClass;
            $obj->result = $result;
            // No other state info to save for export.
        }
        return $obj;
    }

    /**
     * Add custom entries to the Settings block tree menu
     *
     * @param object $adminroot The main admin tree root object
     * @param string $parentname The name of the parent node to add children to
     */
    public function admintree_setup(&$adminroot, $parentname) {
        global $CFG;

        // Create a link to the page for configuring field mappings.
        $displaystring = get_string('configfieldstreelink', 'dhexport_version1elis');
        $url = $CFG->wwwroot.'/local/datahub/exportplugins/version1elis/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        // Add it to the tree.
        $adminroot->add($parentname, $page);
    }

    /**
     * Specifies flag for indicating whether this plugin is actually available
     * on the current system, particularly for viewing in the UI and running
     * scheduled tasks
     */
    public function is_available() {
        global $CFG;

        // This plugin is only available if the PM code is present.
        return file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    }

    /**
     * Mainline for export processing
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     * @param int $maxruntime      The max time in seconds to complete export
     *                             default: 0 => unlimited
     * @param object $state        Previous ran state data to continue from
     *                             (currently not used for export)
     * @return mixed object        Current state of export processing
     *                             or null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    public function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        $result = parent::run($targetstarttime, $lastruntime, $maxruntime, $state);

        if (!defined('PHPUnit_MAIN_METHOD')) {
            // Not in a unit test, so send out log files in a zip.
            $logids = $this->dblogger->get_log_ids();
            rlip_send_log_emails('dhexport_version1elis', $logids, $this->manual);
        }

        return $result;
    }

}
