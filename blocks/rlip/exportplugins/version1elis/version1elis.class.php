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
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * PM class instance grade export compatible with the original PM grade
 * export for Moodle 1.9
 */
class rlip_exportplugin_version1elis extends rlip_exportplugin_base {
    //mapping of user context custom field ids to data types
    var $datatypes = array();
    //mapping of user context custom field ids to default values
    var $defaultdata = array();
    //controls for datatypes
    var $controls = array();
    //stores which profile fields contain a date and time value
    var $showtime = array();
    //recordset for tracking current export record
    var $recordset = null;
    //complete status string, used to avoid calling get_string for each row
    var $completestatusstring = '';

    /**
     * constants for noting the state of a field with relation to multi-values
     */

    //field currently allows multi-value setups and has relevant data
    const MULTIVALUE_ENABLED = 1;
    //field does not currently allow multi-value setups but has historical data
    //(i.e. only display first value)
    const MULTIVALUE_HISTORICAL = 2;
    //no multi-value data for this field
    const MULTIVALUE_NONE = 3;

    //maps fields to their multivalue statuses
    var $multivaluestatus = array();

    /**
     * Set up the local tracking of the a custom field's status in relation to
     * whether it's multivalued or not
     *
     * @param int $fieldid The id of the appropriate ELIS custom user field
     * @param int $multivalued 1 if the field is multivalued, otherwise 0
     * @return int The multivalue status flag, as calculated and stored for the
     *             provided field
     */
    private function init_multivalue_status_for_field($fieldid, $multivalued) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        //determine if multi-valued data exists for this custom field, whether
        //the field currently supports it or not
        $field = new field($fieldid);
        $data_table = $field->data_table();
        $sql = "SELECT 'x'
                FROM {".$data_table."} data1
                WHERE EXISTS (
                    SELECT 'x'
                    FROM {".$data_table."} data2
                    WHERE data1.contextid = data2.contextid
                    AND data1.contextid IS NOT NULL
                    AND data1.fieldid = data2.fieldid
                    AND data1.id != data2.id
                    AND data1.fieldid = ?
                )";
        $params = array($fieldid);

        $multivalue_data_exists = $DB->record_exists_sql($sql, $params);

        if ($multivalue_data_exists) {
            //one or more contexts have multiple values assigned for this field
            if ($multivalued) {
                //field currently supports multi-values
                $this->multivaluestatus[$fieldid] = self::MULTIVALUE_ENABLED;
            } else {
                //field no longer supports multi-values
                $this->multivaluestatus[$fieldid] = self::MULTIVALUE_HISTORICAL;
            }
        } else {
            //basic single value case
            $this->multivaluestatus[$fieldid] = self::MULTIVALUE_NONE;
        }

        return $this->multivaluestatus[$fieldid];
    }

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
        $params = array();

        //track extra SQL and parameters needed for custom fields
        $extra_select = '';
        $extra_joins = '';

        //query to fetch all configured custom fields in the user context
        $sql = "SELECT export.fieldid,
                       export.header,
                       field.datatype,
                       field.multivalued
                FROM {".RLIPEXPORT_VERSION1ELIS_FIELD_TABLE."} export
                  JOIN {".field_contextlevel::TABLE."} context
                    ON export.fieldid = context.fieldid
                  JOIN {".field::TABLE."} field
                    ON field.id = context.fieldid
                WHERE context.contextlevel = ".CONTEXT_ELIS_USER."
                ORDER BY export.fieldorder";

        if (($recordset = $DB->get_recordset_sql($sql)) && $recordset->valid()) {
            $extra_joins = "LEFT JOIN {context} context
                    ON context.contextlevel = ".CONTEXT_ELIS_USER."
                    AND context.instanceid = u.id";
            foreach ($recordset as $record) {
                /**
                 * Calculate information we'll need to format / transform records
                 */

                //field id used to index stored information
                $fieldid = $record->fieldid;
                //store the data type
                $this->datatypes[$fieldid] = $record->datatype;

                //store the default value
                $field = new field($fieldid);
                $this->defaultdata[$fieldid] = $field->get_default();
                //track which fields show date and time values
                $this->controls[$fieldid] = $field->owners['manual']->param_control;
                if ($this->controls[$fieldid] == 'datetime' && $field->owners['manual']->param_inctime) {
                    $this->showtime[$record->fieldid] = 1;
                }

                /**
                 * Determine if the field is multi-valued or has some historical
                 * multi-value data tied to it
                 */
                $multivaluestatus = $this->init_multivalue_status_for_field($record->fieldid, $record->multivalued);

                /**
                 * Calculate extra SQL fragments / parameters
                 */

                if ($multivaluestatus == self::MULTIVALUE_NONE) {
                    //extra columns we'll need to display profile field values
                    $extra_select .= ",
                                       custom_data_{$fieldid}.data
                                       AS custom_field_{$fieldid}";
                    //extra joins we'll need to display profile field values
                    $field_data_table = "field_data_".$field->data_type();
                    $extra_joins = "{$extra_joins}
                                    LEFT JOIN {".$field_data_table::TABLE."} custom_data_{$record->fieldid}
                                      ON custom_data_{$fieldid}.fieldid = ?
                                      AND context.id = custom_data_{$fieldid}.contextid
                                      AND custom_data_{$fieldid}.contextid IS NOT NULL";
                    //id of the appropriate custom field
                    $params[] = $fieldid;
                } else {
                    //extra columns we'll need to display profile field values
                    $extra_select .= ",
                                       '' AS custom_field_{$fieldid}";
                }

                /**
                 * Calculate extra column headers
                 */
                $columns[] = $record->header;
            }
            $recordset->close();
        }

        // add passed as completion status requirement
        $params[] = student::STUSTATUS_PASSED;

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
                //scheduled export incremental mode

                //set up the query fragment and parameters
                $params[] = $lastruntime;
                $time_condition = 'AND stu.completetime >= ?';
            }
        }

        //initialize our recordset to the core data
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
     * Transforms a custom field value for display in the export file
     *
     * @param int $userid The id of the PM user in the current data row
     * @param int $fieldid The database record id of the custom field
     * @param string $value The custom field value
     * @return string The formatted string
     */
    function transform_value($userid, $fieldid, $value) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));

        if ($value === NULL) {
            //not set, so use the default value
            $value = $this->defaultdata[$fieldid];
        }

        if ($this->multivaluestatus[$fieldid] !== self::MULTIVALUE_NONE) {
            $field = new field($fieldid);

            $context = context_elis_user::instance($userid);
            $data = field_data::get_for_context_and_field($context, $field);

            if ($this->multivaluestatus[$fieldid] == self::MULTIVALUE_ENABLED) {
                $parts = array();
                foreach ($data as $datum) {
                    $parts[] = $datum->data;
                }

                $value = implode(' / ', $parts);
            } else {
                $value = $data->current()->data;
            }
        }

        if ($this->controls[$fieldid] == 'datetime') {
            if ($value == 0) {
                //use a marker to indicate that it's not set
                $value = get_string('nodatemarker', 'rlipexport_version1elis');
            } else if (isset($this->showtime[$fieldid]) && $this->showtime[$fieldid]){
                //date and time
                $value = date('M/d/Y:H:i', $value);
            } else {
                //just date
                $value = date('M/d/Y', $value);
            }
        }

        // remove html from text
        if ($this->controls[$fieldid] == 'text' || $this->controls[$fieldid] == 'textarea') {
            $value = trim(html_to_text($value),"\n\r");
        }
        return $value;
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

        //iterate through our list of profile fields and perform data
        //transformation on each field value
        foreach (array_keys($this->datatypes) as $fieldid) {
            $property = "custom_field_{$fieldid}";
            $value = $record->{$property};
            $value = $this->transform_value($record->userid, $fieldid, $value);

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
        $this->recordset = NULL;
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
                    error_log("/blocks/rlip/exportplugins/version1elis/version1elis.class.php::finish() - Error renaming: '{$tempfile}' to '{$outfile}'");
                }
            }
           /*
              else {
                error_log("/blocks/rlip/exportplugins/version1elis/version1elis.class.php::finish() - Error file: '{$tempfile}' doesn't exist!");
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
        $displaystring = get_string('configfieldstreelink', 'rlipexport_version1elis');
        $url = $CFG->wwwroot.'/blocks/rlip/exportplugins/version1elis/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        //add it to the tree
        $adminroot->add($parentname, $page);
    }

    /**
     * Specifies flag for indicating whether this plugin is actually available
     * on the current system, particularly for viewing in the UI and running
     * scheduled tasks
     */
    function is_available() {
        global $CFG;

        //this plugin is only available if the PM code is present
        return file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
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
            rlip_send_log_emails('rlipexport_version1elis', $logids, $this->manual);
        }

        return $result;
    }

}
