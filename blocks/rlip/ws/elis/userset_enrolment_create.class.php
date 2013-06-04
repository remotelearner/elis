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
 * @package    block_rlip
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/clusterassignment.class.php'));
require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');

/**
 * Create userset enrolment create webservices method.
 */
class block_rldh_elis_userset_enrolment_create extends external_api {

    /**
     * Gets a description of the userset_enrolment object for use in the parameter.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_enrolment_input_object_description() {
        return array(
            'userset_name' => new external_value(PARAM_TEXT, 'Userset name', VALUE_REQUIRED),
            'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'plugin' => new external_value(PARAM_TEXT, 'Userset enrolment plugin type', VALUE_OPTIONAL),
            'leader' => new external_value(PARAM_BOOL, 'Userset enrolment leader flag', VALUE_OPTIONAL),
        );
    }

    /**
     * Gets a description of the userset_enrolment object returned by functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_enrolment_output_object_description() {
        return array(
            'clusterid' => new external_value(PARAM_INT, 'The Userset db id', VALUE_OPTIONAL),
            'userid' => new external_value(PARAM_INT, 'The User db id', VALUE_OPTIONAL),
            'plugin' => new external_value(PARAM_TEXT, 'The Userset enrolment plugin type', VALUE_OPTIONAL),
            'leader' => new external_value(PARAM_BOOL, 'The Userset enrolment leader flag', VALUE_OPTIONAL),
            'autoenrol' => new external_value(PARAM_INT, 'The Userset enrolment autoenrol setting', VALUE_OPTIONAL),
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function userset_enrolment_create_parameters() {
        $params = array('data' => new external_single_structure(static::get_userset_enrolment_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs userset_enrolment creation
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function userset_enrolment_create(array $data) {
        global $USER, $DB;

        // Parameter validation.
        $params = self::validate_parameters(self::userset_enrolment_create_parameters(), array('data' => $data));

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Parse userset
        if (empty($data->userset_name) || !($clstid = $DB->get_field('crlm_cluster', 'id', array('name' => $data->userset_name)))) {
            throw new data_object_exception('ws_userset_enrolment_create_fail_invalid_userset', 'block_rlip', '', $data);
        }

        // Capability checking.
        require_capability('elis/program:userset_enrol', context_elis_userset::instance($clstid));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

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
            throw new data_object_exception('ws_userset_enrolment_create_fail_invalid_user', 'block_rlip', '', $a);
        }

        $record = new stdClass;
        $record->userid = $userid;
        $record->clusterid = $clstid;
        $record->plugin = 'manual';
        if (!empty($data->plugin)) {
            $record->plugin = $data->plugin;
        }
        $record->leader = !empty($data->leader);
        $clstass = new clusterassignment($record);
        $clstass->save();

        // Respond.
        if (!empty($clstass->id)) {
            return array(
                'messagecode' => get_string('ws_userset_enrolment_create_success_code', 'block_rlip'),
                'message' => get_string('ws_userset_enrolment_create_success_msg', 'block_rlip'),
                'record' => $clstass->to_array(),
            );
        } else {
            throw new data_object_exception('ws_userset_enrolment_create_fail', 'block_rlip');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function userset_enrolment_create_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_userset_enrolment_output_object_description())
                )
        );
    }
}
