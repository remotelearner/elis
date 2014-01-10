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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../lib.php');

/**
 * Update Class enrolment webservices method.
 */
class local_datahub_elis_class_enrolment_update extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/student.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets a description of the class_enrolment object for use in the parameter.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_class_enrolment_input_object_description() {
        return array(
            'class_idnumber' => new external_value(PARAM_TEXT, 'Class idnumber', VALUE_REQUIRED),
            'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'enrolmenttime' => new external_value(PARAM_TEXT, 'Date/time user enroled into class', VALUE_OPTIONAL),
            'completetime' => new external_value(PARAM_TEXT, 'Date/time user completed class', VALUE_OPTIONAL),
            'completestatus' => new external_value(PARAM_TEXT, 'Class completion status string', VALUE_OPTIONAL),
            'grade' => new external_value(PARAM_FLOAT, 'Grade user has in class', VALUE_OPTIONAL),
            'credits' => new external_value(PARAM_FLOAT, 'Credits the user has earned in class', VALUE_OPTIONAL),
            'locked' => new external_value(PARAM_BOOL, 'Class enrolment locked status', VALUE_OPTIONAL),
        );
    }

    /**
     * Gets a description of the class_enrolment object returned by functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_class_enrolment_output_object_description() {
        return array(
            'classid' => new external_value(PARAM_INT, 'The Class db id', VALUE_OPTIONAL),
            'userid' => new external_value(PARAM_INT, 'The User db id', VALUE_OPTIONAL),
            'enrolmenttime' => new external_value(PARAM_INT, 'The class enrolment time', VALUE_OPTIONAL),
            'completetime' => new external_value(PARAM_INT, 'The class completion time', VALUE_OPTIONAL),
            'endtime' => new external_value(PARAM_INT, 'The class end time', VALUE_OPTIONAL),
            'completestatusid' => new external_value(PARAM_INT, 'The class completion status', VALUE_OPTIONAL),
            'grade' => new external_value(PARAM_FLOAT, 'The grade user has in class', VALUE_OPTIONAL),
            'credits' => new external_value(PARAM_FLOAT, 'The credits the user has earned in class', VALUE_OPTIONAL),
            'locked' => new external_value(PARAM_BOOL, 'The class enrolment locked status', VALUE_OPTIONAL),
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function class_enrolment_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_class_enrolment_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs class_enrolment update
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function class_enrolment_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        static $completestatuses = array(
            'not completed' => STUSTATUS_NOTCOMPLETE,
            'not_completed' => STUSTATUS_NOTCOMPLETE,
            'notcompleted' => STUSTATUS_NOTCOMPLETE,
            'failed' => 'STUSTATUS_FAILED',
            'passed' => STUSTATUS_PASSED
        );

        // Parameter validation.
        $params = self::validate_parameters(self::class_enrolment_update_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Parse class
        if (empty($data->class_idnumber) || !($classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $data->class_idnumber)))) {
            throw new data_object_exception('ws_class_enrolment_update_fail_invalid_class', 'local_datahub', '', $data);
        }

        // Capability checking.
        require_capability('local/elisprogram:class_enrol', \local_elisprogram\context\pmclass::instance($classid));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        $userparams = array();
        $userid = $importplugin->get_userid_from_record($data, '', $userparams);
        if ($userid == false) {
            $a = new stdClass;
            if (empty($userparams)) {
                $a->userparams = '{empty}';
            } else {
                $a->userparams = '';
                foreach ($userparams as $userfield => $uservalue) {
                    $subfield = strpos($userfield, '_');
                    $userfield = substr($userfield, ($subfield === false) ? 0 : $subfield + 1);
                    if (!empty($a->userparams)) {
                        $a->userparams .= ', ';
                    }
                    $a->userparams .= "{$userfield}: '{$uservalue}'";
                }
            }
            throw new data_object_exception('ws_class_enrolment_update_fail_invalid_user', 'local_datahub', '', $a);
        }

        if (!($stuid = $DB->get_field(student::TABLE, 'id', array('classid' => $classid, 'userid' => $userid)))) {
            throw new data_object_exception('ws_class_enrolment_update_fail_missing_enrolment', 'local_datahub', '', $data);
        }

        $stu = new student($stuid);
        $stu->load();
        $record = new stdClass;
        $record->userid = $userid;
        $record->classid = $classid;
        if (isset($data->completestatus)) {
            $completestatus = strtolower($data->completestatus);
            if (isset($completestatuses[$completestatus])) {
                $record->completestatusid = $completestatuses[$completestatus];
                if ($record->completestatusid != STUSTATUS_NOTCOMPLETE) {
                    if (empty($stu->completetime) && !isset($data->completetime)) {
                        $record->completetime = rlip_timestamp();
                    }
                }
            } else {
                throw new data_object_exception('ws_class_enrolment_update_fail_invalid_completestatus', 'local_datahub', '', $data);
            }
        }
        if (isset($data->grade) && is_numeric($data->grade)) {
            $record->grade = $data->grade;
        }
        if (isset($data->credits) && is_numeric($data->credits)) {
            $record->credits = $data->credits;
        }
        if (isset($data->locked)) {
            $record->locked = $data->locked ? 1 : 0;
        }
        if (isset($data->enrolmenttime)) {
            if ($enrolmenttime = $importplugin->parse_date($data->enrolmenttime)) {
                $record->enrolmenttime = $enrolmenttime;
            } else {
                throw new data_object_exception('ws_class_enrolment_update_fail_invalid_enrolmenttime', 'local_datahub', '', $data);
            }
        }
        if (isset($data->completetime)) {
            if ($completetime = $importplugin->parse_date($data->completetime)) {
                $record->completetime = $completetime;
            } else {
                throw new data_object_exception('ws_class_enrolment_update_fail_invalid_completetime', 'local_datahub', '', $data);
            }
        }

        $stu->set_from_data($record);
        $stu->save();

        // Respond.
        if (!empty($stu->id)) {
            return array(
                'messagecode' => get_string('ws_class_enrolment_update_success_code', 'local_datahub'),
                'message' => get_string('ws_class_enrolment_update_success_msg', 'local_datahub'),
                'record' => $stu->to_array(),
            );
        } else {
            throw new data_object_exception('ws_class_enrolment_update_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function class_enrolment_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_class_enrolment_output_object_description())
                )
        );
    }
}
