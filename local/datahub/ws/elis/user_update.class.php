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
 * Update user webservices method.
 */
class local_datahub_elis_user_update extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets user custom fields
     * @return array An array of custom user fields
     */
    public static function get_user_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            // Get custom fields.
            $sql = 'SELECT shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_USER);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Gets a description of the user input object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user input record in webservice terms.
     */
    public static function get_user_input_object_description() {
        global $DB;
        $params = array(
            'username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'password' => new external_value(PARAM_TEXT, 'User password', VALUE_OPTIONAL),
            'idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'firstname' => new external_value(PARAM_TEXT, 'User first name', VALUE_OPTIONAL),
            'lastname' => new external_value(PARAM_TEXT, 'User last name', VALUE_OPTIONAL),
            'mi' => new external_value(PARAM_TEXT, 'User middle initial', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'email2' => new external_value(PARAM_TEXT, 'User secondary email', VALUE_OPTIONAL),
            'address' => new external_value(PARAM_TEXT, 'User primary address', VALUE_OPTIONAL),
            'address2' => new external_value(PARAM_TEXT, 'User secondary address', VALUE_OPTIONAL),
            'city' => new external_value(PARAM_TEXT, 'User city', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'User state/province', VALUE_OPTIONAL),
            'postalcode' => new external_value(PARAM_TEXT, 'User postal code', VALUE_OPTIONAL),
            'country' => new external_value(PARAM_TEXT, 'User country', VALUE_OPTIONAL),
            'phone' => new external_value(PARAM_TEXT, 'User primary phone number', VALUE_OPTIONAL),
            'phone2' => new external_value(PARAM_TEXT, 'User secondary phone number', VALUE_OPTIONAL),
            'fax' => new external_value(PARAM_TEXT, 'User fax number', VALUE_OPTIONAL),
            'birthdate' => new external_value(PARAM_TEXT, 'User birthdate', VALUE_OPTIONAL),
            'gender' => new external_value(PARAM_TEXT, 'User gender', VALUE_OPTIONAL),
            'language' => new external_value(PARAM_TEXT, 'User language', VALUE_OPTIONAL),
            'transfercredits' => new external_value(PARAM_FLOAT, 'Credits user has earned elsewhere', VALUE_OPTIONAL),
            'comments' => new external_value(PARAM_TEXT, 'Comments', VALUE_OPTIONAL),
            'notes' => new external_value(PARAM_TEXT, 'Notes', VALUE_OPTIONAL),
            'inactive' => new external_value(PARAM_BOOL, 'User inactive status', VALUE_OPTIONAL),
        );

        $fields = self::get_user_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Gets a description of the user output object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user output record in webservice terms.
     */
    public static function get_user_output_object_description() {
        global $DB;
        $params = array(
            'id' => new external_value(PARAM_INT, 'User DB id', VALUE_REQUIRED),
            'username' => new external_value(PARAM_TEXT, 'User username', VALUE_REQUIRED),
            'password' => new external_value(PARAM_TEXT, 'User password', VALUE_OPTIONAL),
            'idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_REQUIRED),
            'firstname' => new external_value(PARAM_TEXT, 'User first name', VALUE_REQUIRED),
            'lastname' => new external_value(PARAM_TEXT, 'User last name', VALUE_REQUIRED),
            'mi' => new external_value(PARAM_TEXT, 'User middle initial', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_REQUIRED),
            'email2' => new external_value(PARAM_TEXT, 'User secondary email', VALUE_OPTIONAL),
            'address' => new external_value(PARAM_TEXT, 'User primary address', VALUE_OPTIONAL),
            'address2' => new external_value(PARAM_TEXT, 'User secondary address', VALUE_OPTIONAL),
            'city' => new external_value(PARAM_TEXT, 'User city', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'User state/province', VALUE_OPTIONAL),
            'postalcode' => new external_value(PARAM_TEXT, 'User postal code', VALUE_OPTIONAL),
            'country' => new external_value(PARAM_TEXT, 'User country', VALUE_REQUIRED),
            'phone' => new external_value(PARAM_TEXT, 'User primary phone number', VALUE_OPTIONAL),
            'phone2' => new external_value(PARAM_TEXT, 'User secondary phone number', VALUE_OPTIONAL),
            'fax' => new external_value(PARAM_TEXT, 'User fax number', VALUE_OPTIONAL),
            'birthdate' => new external_value(PARAM_TEXT, 'User birthdate', VALUE_OPTIONAL),
            'gender' => new external_value(PARAM_TEXT, 'User gender', VALUE_OPTIONAL),
            'language' => new external_value(PARAM_TEXT, 'User language', VALUE_OPTIONAL),
            'transfercredits' => new external_value(PARAM_FLOAT, 'Credits user has earned elsewhere', VALUE_OPTIONAL),
            'comments' => new external_value(PARAM_TEXT, 'Comments', VALUE_OPTIONAL),
            'notes' => new external_value(PARAM_TEXT, 'Notes', VALUE_OPTIONAL),
            'inactive' => new external_value(PARAM_BOOL, 'User inactive status', VALUE_OPTIONAL),
        );

        $fields = self::get_user_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }
    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function user_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_user_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs user updating
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error editing the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function user_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::user_update_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        // Get the user we're updating via identifying fields.
        $idfields = array('idnumber', 'username', 'email');
        $valididfields = array();
        $invalididfields = array();
        $userid = null;
        foreach ($idfields as $field) {
            if (isset($data[$field])) {
                $users = $DB->get_records(user::TABLE, array($field => $data[$field]), '', 'id', 0, 2);
                $numusers = count($users);
                if ($numusers > 1) {
                    throw new moodle_exception('ws_user_update_fail_multipleusersforidentifier', 'local_datahub', '', $field);
                } else if ($numusers === 1) {
                    $user = reset($users);
                    if (!empty($userid) && $userid !== $user->id) {
                        // If we already have a userid from a previous field and this user doesn't match that user, throw exception.
                        $a = implode(', ', $valididfields).', '.$field;
                        throw new moodle_exception('ws_user_update_fail_conflictingidfields', 'local_datahub', '', $a);
                    } else {
                        $userid = $user->id;
                        $valididfields[] = $field;
                    }
                } else {
                    if (!empty($userid)) {
                        // The user has supplied a valid identifying field already, but this one is an invalid field.
                        // This is likely an attempt to update an identifying field, which has to be done elsewhere.
                        throw new moodle_exception('ws_user_update_fail_idfieldsnotallowed', 'local_datahub');
                    }
                    $invalididfields[] = $field;
                }
            }
        }

        if (empty($userid)) {
            // No valid identifying fields found.
            throw new moodle_exception('ws_user_update_fail_noidfields', 'local_datahub');
        } else {
            if (!empty($invalididfields)) {
                // The user has supplied a valid identifying field already, but has also supplied at least one invalid id field.
                // This is likely an attempt to update an identifying field, which has to be done elsewhere.
                throw new moodle_exception('ws_user_update_fail_idfieldsnotallowed', 'local_datahub');
            }
        }

        // Capability checking.
        require_capability('local/elisprogram:user_edit', \local_elisprogram\context\user::instance($userid));

        // Initialize update data.
        $data = (object)$data;
        $data = $importplugin->add_custom_field_prefixes($data);
        $data = $importplugin->initialize_user_fields($data);

        // Handle password changes.
        if (isset($data->password)) {
            $data->newpassword = $data->password;
            unset($data->password);
        }

        // Convert the birthdate field to the proper setup for storage.
        if (isset($data->birthdate)) {
            $value = $importplugin->parse_date($data->birthdate);
            if (!empty($value)) {
                $data->birthday = gmdate('d', $value);
                $data->birthmonth = gmdate('m', $value);
                $data->birthyear = gmdate('Y', $value);
            } else {
                throw new moodle_exception('ws_bad_param', 'local_datahub', '', 'birthdate');
            }
            unset($data->birthdate);
        }

        // Update the user.
        $user = new user($userid);
        $user->load();
        $user->set_from_data($data);
        $user->save();

        // Respond.
        if (!empty($user->id)) {
            $userrec = (array)$DB->get_record(user::TABLE, array('id' => $user->id));
            $userobj = $user->to_array();
            // Convert multi-valued custom field arrays to comma-separated listing.
            $fields = self::get_user_custom_fields();
            foreach ($fields as $field) {
                // Generate name using custom field prefix.
                $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

                if ($field->multivalued && isset($userobj[$fullfieldname]) && is_array($userobj[$fullfieldname])) {
                    $userobj[$fullfieldname] = implode(',', $userobj[$fullfieldname]);
                }
            }
            return array(
                'messagecode' => get_string('ws_user_update_success_code', 'local_datahub'),
                'message' => get_string('ws_user_update_success_msg', 'local_datahub'),
                'record' => array_merge($userrec, $userobj),
            );
        } else {
            throw new data_object_exception('ws_user_update_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function user_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_user_output_object_description())
                )
        );
    }
}
