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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_exportplugin.class.php');

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
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    function init($targetstarttime = 0, $lastruntime = 0) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        $file = get_plugin_directory('dhexport', 'version1').'/lib.php';
        require_once($file);

        //columns that are always displayed
        $columns = array(get_string('header_firstname', 'dhexport_version1'),
                         get_string('header_lastname', 'dhexport_version1'),
                         get_string('header_username', 'dhexport_version1'),
                         get_string('header_useridnumber', 'dhexport_version1'),
                         get_string('header_courseidnumber', 'dhexport_version1'),
                         get_string('header_startdate', 'dhexport_version1'),
                         get_string('header_enddate', 'dhexport_version1'),
                         get_string('header_grade', 'dhexport_version1'),
                         get_string('header_letter', 'dhexport_version1'));

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
                FROM {".RLIPEXPORT_VERSION1_FIELD_TABLE."} export
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

        /**
         * Handle the "incremental" offset, if necessary
         */
        //determine if we're in incremental or non-incremental mode
        $nonincremental = get_config('dhexport_version1', 'nonincremental');


        if (empty($nonincremental)) {
            if($this->manual) {
                //manual export incremental mode

                //get string delta
                $incrementaldelta = get_config('dhexport_version1', 'incrementaldelta');
                //conver to number of seconds
                $numsecs = rlip_time_string_to_offset($incrementaldelta);

            } else if (!$this->manual) {
                //scheduled export incremental mode

                //calculate number of seconds
                $numsecs = $targetstarttime - $lastruntime;
            }

            $extra_params[] = time() - $numsecs;
           //SQL and params
            $sql .= " AND gg.timemodified >= ?";
        }

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
            if ($value == 0) {
                //use a marker to indicate that it's not set
                $value = get_string('nodatemarker', 'dhexport_version1');
            } else if (!empty($this->showtime[$fieldid])) {
                //format as a date, with time
                $value = date('M/d/Y, h:i a', $value);
            } else {
                //format as date, without time
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
        $format = get_string('dateformat', 'local_datahub');

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

    /**
     * Hook for performing any final actions depending on export result
     * @param   bool  $result   The state of the export, true => success
     * @uses    $CFG
     * @todo    refactor to remove dependency on $CFG and 'export_path'
     * @return  mixed           State info on failure or null for success.
     */
    function finish($result) {
        global $CFG;
        $obj = null;
        if ($result) {
            if ($this->fileplugin->sendtobrowser) {
                // nothing to do here ...
                return null;
            }
            // Export successfull, move temp. file (if exists) to export path
            $tempfile = $this->fileplugin->get_filename(true);
            if (file_exists($tempfile)) {
                $exportbase = basename($tempfile);
                $exportpath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) .
                              DIRECTORY_SEPARATOR .
                              trim(get_config($this->plugin, 'export_path'),
                                   DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $outfile = $exportpath . $exportbase;
                if (!@rename($tempfile, $outfile)) {
                    error_log("/local/datahub/exportplugins/version1/version1.class.php::finish() - Error renaming: '{$tempfile}' to '{$outfile}'");
                }
            }
           /*
              else {
                error_log("/local/datahub/exportplugins/version1/version1.class.php::finish() - Error file: '{$tempfile}' doesn't exist!");
            }
           */
        } else {
            $obj = new stdClass;
            $obj->result = $result;
            // no other state info to save for export
        }
        return $obj;
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
        $displaystring = get_string('configfieldstreelink', 'dhexport_version1');
        $url = $CFG->wwwroot.'/local/datahub/exportplugins/version1/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        //add it to the tree
        $adminroot->add($parentname, $page);
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
    function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        $result = parent::run($targetstarttime, $lastruntime, $maxruntime, $state);

        if (!defined('PHPUnit_MAIN_METHOD')) {
            //not in a unit test, so send out log files in a zip
            $logids = $this->dblogger->get_log_ids();
            rlip_send_log_emails('dhexport_version1', $logids, $this->manual);
        }

        return $result;
    }
}
