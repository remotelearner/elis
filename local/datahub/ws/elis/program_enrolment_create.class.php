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

/**
 * Create user webservices method.
 */
class local_datahub_elis_program_enrolment_create extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/curriculum.class.php'));
            require_once(elispm::lib('data/curriculumstudent.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets a description of the program_enrolment object for use in the parameter.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_program_enrolment_input_object_description() {
        return array(
            'program_idnumber' => new external_value(PARAM_TEXT, 'Program idnumber', VALUE_REQUIRED),
            'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'credits' => new external_value(PARAM_FLOAT, 'Credits user has in program', VALUE_OPTIONAL),
            'locked' => new external_value(PARAM_BOOL, 'Program enrolment locked status', VALUE_OPTIONAL),
            'timecompleted' => new external_value(PARAM_TEXT, 'Program enrolment time completed', VALUE_OPTIONAL),
            'timeexpired' => new external_value(PARAM_TEXT, 'Program enrolment time expired', VALUE_OPTIONAL),
        );
    }

    /**
     * Gets a description of the program_enrolment object returned by functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_program_enrolment_output_object_description() {
        return array(
            'curriculumid' => new external_value(PARAM_INT, 'The Program db id', VALUE_OPTIONAL),
            'userid' => new external_value(PARAM_INT, 'The User db id', VALUE_OPTIONAL),
            'credits' => new external_value(PARAM_FLOAT, 'Credits user has in program', VALUE_OPTIONAL),
            'locked' => new external_value(PARAM_BOOL, 'Program enrolment locked status', VALUE_OPTIONAL),
            'timecompleted' => new external_value(PARAM_INT, 'Program enrolment time completed', VALUE_OPTIONAL),
            'timeexpired' => new external_value(PARAM_INT, 'Program enrolment time expired', VALUE_OPTIONAL),
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function program_enrolment_create_parameters() {
        $params = array('data' => new external_single_structure(static::get_program_enrolment_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs program_enrolment creation
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function program_enrolment_create(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::program_enrolment_create_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Parse program
        if (empty($data->program_idnumber) || !($curid = $DB->get_field(curriculum::TABLE, 'id', array('idnumber' => $data->program_idnumber)))) {
            throw new data_object_exception('ws_program_enrolment_create_fail_invalid_program', 'local_datahub', '', $data);
        }

        // Capability checking.
        require_capability('local/elisprogram:program_enrol', \local_elisprogram\context\program::instance($curid));

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
            throw new data_object_exception('ws_program_enrolment_create_fail_invalid_user', 'local_datahub', '', $a);
        }

        $record = new stdClass;
        $record->userid = $userid;
        $record->curriculumid = $curid;
        if (isset($data->credits)) {
            $record->credits = $data->credits;
        }
        if (isset($data->locked)) {
            $record->locked = $data->locked ? 1 : 0;
        }
        if (isset($data->timecompleted)) {
            $record->timecompleted = $importplugin->parse_date($data->timecompleted);
        }
        if (isset($data->timeexpired)) {
            $record->timeexpired = $importplugin->parse_date($data->timeexpired);
        }
        $stucur = new curriculumstudent($record);
        $stucur->save();

        // Respond.
        if (!empty($stucur->id)) {
            return array(
                'messagecode' => get_string('ws_program_enrolment_create_success_code', 'local_datahub'),
                'message' => get_string('ws_program_enrolment_create_success_msg', 'local_datahub'),
                'record' => $stucur->to_array(),
            );
        } else {
            throw new data_object_exception('ws_program_enrolment_create_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function program_enrolment_create_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_program_enrolment_output_object_description())
                )
        );
    }
}

