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
require_once(elispm::lib('data/user.class.php'));
require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');

/**
 * Delete user webservices method.
 */
class block_rldh_elis_user_delete extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function user_delete_parameters() {
        $params = array(
            'data' => new external_single_structure(array(
                'username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
                'email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            ))
        );
        return new external_function_parameters($params);
    }

    /**
     * Performs user deletion
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error deleting the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function user_delete(array $data) {
        global $USER, $DB;

        // Parameter validation.
        $params = self::validate_parameters(self::user_delete_parameters(), array('data' => $data));

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        // Get the user we're updating via identifying fields.
        $idfields = array('idnumber', 'username', 'email');
        $userid = null;
        foreach ($idfields as $field) {
            if (isset($data[$field])) {
                $user = $DB->get_record(user::TABLE, array($field => $data[$field]));
                if (!empty($user)) {
                    if (!empty($userid) && $userid !== $user->id) {
                        // If we already have a userid from a previous field and this user doesn't match that user, throw exception.
                        throw new moodle_exception('ws_user_delete_fail_conflictingidfields', 'block_rlip');
                    } else {
                        $userid = $user->id;
                    }
                }
            }
        }

        if (empty($userid)) {
            // No valid identifying fields found.
            throw new moodle_exception('ws_user_delete_fail_noidfields', 'block_rlip');
        }

        // Capability checking.
        require_capability('elis/program:user_delete', context_elis_user::instance($userid));

        // Delete the user.
        $user = new user($userid);
        $user->delete();

        // Verify user deleted & respond
        if (!$DB->record_exists(user::TABLE, array('id' => $userid))) {
            return array(
                'messagecode' => get_string('ws_user_delete_success_code', 'block_rlip'),
                'message' => get_string('ws_user_delete_success_msg', 'block_rlip'),
            );
        } else {
            throw new data_object_exception('ws_user_delete_fail', 'block_rlip');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function user_delete_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                )
        );
    }
}
