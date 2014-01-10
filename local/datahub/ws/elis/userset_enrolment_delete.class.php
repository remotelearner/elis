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
 * Create userset_enrolment delete webservices method.
 */
class local_datahub_elis_userset_enrolment_delete extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/userset.class.php'));
            require_once(elispm::lib('data/clusterassignment.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets a description of the userset_enrolment object for use in the parameter.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_enrolment_object_description() {
        return array(
            'userset_name' => new external_value(PARAM_TEXT, 'Userset name', VALUE_REQUIRED),
            'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'plugin' => new external_value(PARAM_TEXT, 'Userset enrolment plugin type', VALUE_OPTIONAL),
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function userset_enrolment_delete_parameters() {
        $params = array('data' => new external_single_structure(static::get_userset_enrolment_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs userset_enrolment deletion
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error deleting the association.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function userset_enrolment_delete(array $data) {
        global $DB, $USER;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::userset_enrolment_delete_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Parse Userset
        if (empty($data->userset_name) || !($clstid = $DB->get_field(userset::TABLE, 'id', array('name' => $data->userset_name)))) {
            throw new data_object_exception('ws_userset_enrolment_delete_fail_invalid_userset', 'local_datahub', '', $data);
        }

        if (empty($data->plugin)) {
            $data->plugin = 'manual';
        }

        // Capability checking.
        require_capability('local/elisprogram:userset_enrol', \local_elisprogram\context\userset::instance($clstid));

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
            throw new data_object_exception('ws_userset_enrolment_delete_fail_invalid_user', 'local_datahub', '', $a);
        }

        $id = $DB->get_field(clusterassignment::TABLE, 'id', array('clusterid' => $clstid, 'userid' => $userid, 'plugin' => $data->plugin));

        // Respond.
        if (!empty($id) && ($clstass = new clusterassignment($id))) {
            $clstass->delete();
            return array(
                'messagecode' => get_string('ws_userset_enrolment_delete_success_code', 'local_datahub'),
                'message' => get_string('ws_userset_enrolment_delete_success_msg', 'local_datahub'),
            );
        } else {
            throw new data_object_exception('ws_userset_enrolment_delete_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function userset_enrolment_delete_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                )
        );
    }
}

