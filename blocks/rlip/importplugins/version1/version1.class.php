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

require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');

/**
 * Original Moodle-only import 
 */
class rlip_importplugin_version1 extends rlip_importplugin_base {
    //required field definition
    static $import_fields_user_create = array('username',
                                              'password',
                                              'firstname',
                                              'lastname',
                                              'email',
                                              'city',
                                              'country');
    static $import_fields_user_add = array('username',
                                           'password',
                                           'firstname',
                                           'lastname',
                                           'email',
                                           'city',
                                           'country');
    static $import_fields_user_update = array(array('username',
                                                    'email',
                                                    'idnumber'));

    /**
     * Hook run after a file header is read
     *
     * @param string $entity The type of entity
     * @param array $header The header record
     */
    function header_read_hook($entity, $header) {
        global $DB;

        if ($entity !== 'user') {
            return;
        }

        $this->fields = array();

        foreach ($header as $column) {
            if (strpos($column, 'profile_field_') === 0) {
                $shortname = substr($column, strlen('profile_field_'));
                $this->fields[$shortname] = $DB->get_record('user_info_field', array('shortname' => $shortname)); 
            }
        }
    }

    /**
     * Checks a field's data is one of the specified values
     * @todo: consider moving this because it's fairly generalized
     *
     * @param object $record The record containing the data to validate
     * @param string $property The field / property to check
     * @param array $list The valid possible values
     */
    function validate_fixed_list($record, $property, $list) {
        //note: do not worry about missing fields here
        if (isset($record->$property)) {
            return in_array($record->$property, $list);
        }

        return true;
    }

    /**
     * Converts a date in MMM/DD/YYYY format
     * to a unix timestamp
     * @todo: consider further generalizing / moving to base class
     *
     * @param string $date Date in MMM/DD/YYYY format
     * @return mixed The unix timestamp, or false if date is
     *               not in the right format
     */
    function parse_date($date) {
        //make sure there are three parts
        $parts = explode('/', $date);
        if (count($parts) != 3) {
            return false;
        }

        //make sure the month is valid
        $month = $parts[0];
        $day = $parts[1];
        $year = $parts[2];
        $months = array('jan', 'feb', 'mar', 'apr',
                        'may', 'jun', 'jul', 'aug',
                        'sep', 'oct', 'nov', 'dec');
        $pos = array_search(strtolower($month), $months);
        if ($pos === false) {
            //invalid month
            return false;
        }

        //make sure the combination of date components is valid
        $month = $pos + 1;
        $day = (int)$day;
        $year = (int)$year;
        if (!checkdate($month, $day, $year)) {
            //invalid combination of month, day and year
            return false;
        }

        //return unix timestamp
        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Remove invalid fields from a user record
     * @todo: consider generalizing this
     *
     * @param object $record The user record
     * @return object The user record with the invalid fields removed
     */
    function remove_invalid_user_fields($record) {
        $allowed_fields = array('entity', 'action', 'username', 'auth',
                                'password', 'firstname', 'lastname', 'email',
                                'maildigest', 'autosubscribe', 'trackforums',
                                'screenreader', 'city', 'country', 'timezone',
                                'theme', 'lang', 'description', 'idnumber',
                                'institution', 'department');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields) && strpos($key, 'profile_field_') !== 0) {
                unset($record->$key);
            }
        }

        return $record;
    }

    /**
     * Check the lengths of fields from a user record
     * @todo: consider generalizing
     *
     * @param object $record The user record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_user_field_lengths($record) {
        $lengths = array('firstname' => 100,
                         'lastname' => 100,
                         'email' => 100,
                         'city' => 120,
                         'idnumber' => 255,
                         'institution' => 40,
                         'department' => 30);

        foreach ($lengths as $field => $length) {
            //note: do not worry about missing fields here
            if (isset($record->$field)) {
                if (strlen($record->$field) > $length) {
                    return false;
                }
            }
        }

        //no problems found
        return true;
    }

    /**
     * Delegate processing of an import line for entity type "user"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function user_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "user_{$action}";
        return $this->$method($record);
    }

    /**
     * Validates that core user fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_core_user_data($action, $record) {
        global $CFG;

        //make sure auth plugin refers to a valid plugin
        $auths = get_plugin_list('auth');
        if (!$this->validate_fixed_list($record, 'auth', array_keys($auths))) {
            return false;
        }

        //make sure password satisfies the site password policy
        if (isset($record->password)) {
            $errmsg = '';
            if (!check_password_policy($record->password, $errmsg)) {
                return false;
            }
        }

        //make sure email is in user@domain.ext format
        if ($action == 'create') {
            if (!validate_email($record->email)) {
                return false;
            }
        }

        //make sure maildigest is one of the available values
        if (!$this->validate_fixed_list($record, 'maildigest', array(0, 1, 2))) {
            return false;
        }

        //make sure autosubscribe is one of the available values
        if (!$this->validate_fixed_list($record, 'autosubscribe', array(0, 1))) {
            return false;
        }

        //make sure trackforums can only be set if feature is enabled
        if (isset($record->trackforums)) {
            if (empty($CFG->forum_trackreadposts)) {
                return false;
            }
        }

        //make sure trackforums is one of the available values
        if (!$this->validate_fixed_list($record, 'trackforums', array(0, 1))) {
            return false;
        }

        //make sure screenreader is one of the available values
        if (!$this->validate_fixed_list($record, 'screenreader', array(0, 1))) {
            return false;
        }

        //make sure country refers to a valid country code
        $countries = get_string_manager()->get_list_of_countries();
        if (!$this->validate_fixed_list($record, 'country', array_keys($countries))) {
            return false;
        }

        //make sure timezone can only be set if feature is enabled
        if (isset($record->timezone)) {
            if ($CFG->forcetimezone != 99 && $record->timezone != $CFG->forcetimezone) {
                return false;
            }
        }

        //make sure timezone refers to a valid timezone offset
        $timezones = get_list_of_timezones();
        if (!$this->validate_fixed_list($record, 'timezone', array_keys($timezones))) {
            return false;
        }

        //make sure theme can only be set if feature is enabled
        if (isset($record->theme)) {
            if (empty($CFG->allowuserthemes)) {
                return false;
            }
        }

        //make sure theme refers to a valid theme
        $themes = get_list_of_themes();
        if (!$this->validate_fixed_list($record, 'theme', array_keys($themes))) {
            return false;
        }

        //make sure language refers to a valid language
        $languages = get_string_manager()->get_list_of_translations();
        if (!$this->validate_fixed_list($record, 'lang', array_keys($languages))) {
            return false;
        }

        return true;
    }

    /**
     * Validates user profile field data and performs any required
     * data transformation in-place
     *
     * @param object $record The import record
     *
     * @return boolean true if the record validates, otherwise false
     */
    function validate_user_profile_data($record) {
        //go through each profile field in the header
        foreach ($this->fields as $shortname => $field) {
            $key = 'profile_field_'.$shortname;
            $data = $record->$key;

            //perform type-specific validation and transformation
            if ($field->datatype == 'checkbox') {
                if ($data != 0 && $data != 1) {
                    return false;
                }
            } else if ($field->datatype == 'menu') {
                $options = explode("\n", $field->param1);
                if (!in_array($data, $options)) {
                    return false;
                }
            } else if ($field->datatype == 'datetime') {
                $value = $this->parse_date($data);
                if ($value === false) {
                    return false;
                }

                $record->$key = $value;
            }
        }

        return true;
    }

    /**
     * Create a user
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_create($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('create', $record)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record)) {
            return false;
        }

        //uniqueness checks
        if ($DB->record_exists('user', array('username' => $record->username,
                                             'mnethostid'=> $CFG->mnet_localhost_id))) {
            return false;
        }

        if ($DB->record_exists('user', array('email' => $record->email))) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('user', array('idnumber' => $record->idnumber))) {
                return false;
            }
        }

        //final data sanitization
        if (!isset($record->description)) {
            $record->description = '';
        }

        if (!isset($record->lang)) {
            $record->lang = $CFG->lang;
        }

        $record->descriptionformat = FORMAT_HTML;

        $record->mnethostid = $CFG->mnet_localhost_id;

        //write to the database
        $record->id = user_create_user($record);
        profile_save_data($record);

        return true;
    }

    /**
     * Create a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_add($record) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->user_create($record);
    }

    /**
     * Update a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_update($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('update', $record)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record)) {
            return false;
        }

        //find existing user record
        $params = array();
        if (isset($record->username)) {
            $params['username'] = $record->username;
        }
        if (isset($record->email)) {
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
        }

        $record->id = $DB->get_field('user', 'id', $params);
        if (empty($record->id)) {
            return false;
        }

        //write to the database

        //taken from user_update_user
        // hash the password
        if (isset($record->password)) {
            $record->password = hash_internal_user_password($record->password);
        }

        $record->timemodified = time();
        $DB->update_record('user', $record);

        // trigger user_updated event on the full database user row
        $updateduser = $DB->get_record('user', array('id' => $record->id));
        events_trigger('user_updated', $updateduser);

        profile_save_data($record);

        return true;
    }

    /**
     * Delegate processing of an import line for entity type "course"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function course_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "course_{$action}";
        return $this->$method($record);
    }

    /**
     * Delegate processing of an import line for entity type "enrolment"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function enrolment_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "enrolment_{$action}";
        return $this->$method($record);
    }
}