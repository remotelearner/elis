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

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');

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
    static $import_fields_user_delete = array(array('username',
                                                    'email',
                                                    'idnumber'));
    static $import_fields_user_disable = array(array('username',
                                                     'email',
                                                     'idnumber'));

    static $import_fields_course_create = array('shortname',
                                                'fullname',
                                                'category');
    static $import_fields_course_update = array('shortname');
    static $import_fields_course_delete = array('shortname');

    static $import_fields_enrolment_create = array(array('username',
                                                         'email',
                                                         'idnumber'),
                                                   'context',
                                                   'instance',
                                                   'role');
    static $import_fields_enrolment_add = array(array('username',
                                                      'email',
                                                      'idnumber'),
                                                'context',
                                                'instance',
                                                'role');
    static $import_fields_enrolment_delete = array(array('username',
                                                        'email',
                                                        'idnumber'),
                                                  'context',
                                                  'instance',
                                                  'role');

    //available fields
    static $available_fields_user = array('username', 'auth', 'password', 'firstname',
                                          'lastname', 'email', 'maildigest', 'autosubscribe',
                                          'trackforums', 'screenreader', 'city', 'country',
                                          'timezone', 'theme', 'lang', 'description',
                                          'idnumber', 'institution', 'department');
    static $available_fields_course = array('shortname', 'fullname', 'idnumber', 'summary',
                                            'format', 'numsections', 'startdate', 'newsitems',
                                            'showgrades', 'showreports', 'maxbytes', 'guest',
                                            'password', 'visible', 'lang', 'category', 'link',
                                            'theme');
    static $available_fields_enrolment = array('username', 'email', 'idnumber', 'context',
                                               'instance', 'role', 'group', 'grouping');

    //store mappings for the current entity type
    var $mappings = array();

    //cache the list of themes within the lifespan of this plugin
    var $themes = array();

    /**
     * Hook run after a file header is read
     *
     * @param string $entity   The type of entity
     * @param array  $header   The header record
     * @param string $filename ?
     */
    function header_read_hook($entity, $header, $filename) {
        global $DB;

        if ($entity !== 'user') {
            return;
        }

        $this->fields = array();
        $shortnames = array();
        $errors = false;

        foreach ($header as $column) {
            //determine the "real" fieldname, taking mappings into account
            $realcolumn = $column;
            foreach ($this->mappings as $standardfieldname => $customfieldname) {
                if ($column == $customfieldname) {
                    $realcolumn = $standardfieldname;
                    break;
                }
            }

            //attempt to fetch the field
            if (strpos($realcolumn, 'profile_field_') === 0) {
                $shortname = substr($realcolumn, strlen('profile_field_'));
                if ($result = $DB->get_record('user_info_field', array('shortname' => $shortname))) {
                    $this->fields[$shortname] = $result;
                } else {
                    $shortnames[] = "${shortname}";
                    $errors = true;
                }
            }
        }

        if ($errors) {
            $this->fslogger->log_failure("Import file contains the following invalid user profile field(s): " . implode(', ', $shortnames), 0, $filename, $this->linenumber);
            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
        }
    }

    /**
     * Checks a field's data is one of the specified values
     * @todo: consider moving this because it's fairly generalized
     *
     * @param object $record The record containing the data to validate,
                             and possibly modify if $stringvalues used.
     * @param string $property The field / property to check
     * @param array $list The valid possible values
     * @param array $stringvalues associative array of strings to map back to
     *                            $list value. Eg. array('no' => 0, 'yes' => 1)
     */
    function validate_fixed_list(&$record, $property, $list, $stringvalues = null) {
        //note: do not worry about missing fields here
        if (isset($record->$property)) {
            if (is_array($stringvalues) && isset($stringvalues[$record->$property])) {
                $record->$property = (string)$stringvalues[$record->$property];
            }
            // CANNOT use in_array() 'cause types don't match ...
            // AND PHP::in_array('yes', array(0, 1)) == true ???
            foreach ($list as $entry) {
                if ((string)$record->$property == (string)$entry) {
                    return true;
                }
            }
            return false;
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
            //legacy format (zero values handled below by checkdate)
            $month = (int)$month;
        } else {
            //new "text" format
            $month = $pos + 1;
        }

        //make sure the combination of date components is valid
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
        $allowed_fields = $this->get_available_fields('user');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields) && strpos($key, 'profile_field_') !== 0) {
                unset($record->$key);
            }
        }

        return $record;
    }

    /**
     * Obtains the listing of fields that are available for the specified
     * entity type
     *
     * @param string $entitytype The type of entity
     */
    function get_available_fields($entitytype) {
        global $DB;

        if ($this->plugin_supports($entitytype) !== false) {
            $attribute = 'available_fields_'.$entitytype;

            $result = array_merge(array('action'), static::$$attribute);

            //add user profile fields
            if ($entitytype == 'user') {
                if ($fields = $DB->get_records('user_info_field')) {
                    foreach ($fields as $field) {
                        $result[] = 'profile_field_'.$field->shortname;
                    }
                }
            }

            return $result;
        } else {
            return false;
        }
    }

    /**
     * Check the lengths of fields based on the supplied maximum lengths
     *
     * @param string $entitytype The entity type, as expected by the logger
     * @param object $record The import record
     * @param string $filename The name of the import file, excluding path
     * @param array $lengths Mapping of fields to max lengths
     */
    function check_field_lengths($entitytype, $record, $filename, $lengths) {
        foreach ($lengths as $field => $length) {
            //note: do not worry about missing fields here
            if (isset($record->$field)) {
                $value = $record->$field;
                if (strlen($value) > $length) {
                    $identifier = $this->mappings[$field];
                    $this->fslogger->log_failure("{$identifier} value of \"{$value}\" exceeds ".
                                                 "the maximum field length of {$length}.",
                                                 0, $filename, $this->linenumber, $record, $entitytype);
                    return false;
                }
            }
        }

        //no problems found
        return true;
    }

    /**
     * Check the lengths of fields from a user record
     * @todo: consider generalizing
     *
     * @param object $record The user record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_user_field_lengths($record, $filename) {
        $lengths = array('username' => 100,
                         'firstname' => 100,
                         'lastname' => 100,
                         'email' => 100,
                         'city' => 120,
                         'idnumber' => 255,
                         'institution' => 40,
                         'department' => 30);

        return $this->check_field_lengths('user', $record, $filename, $lengths);
    }

    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_user_createorupdate($record, $action) {
        global $CFG, $DB;

        //check config setting
        $createorupdate = get_config('rlipimport_version1', 'createorupdate');

        if (!empty($createorupdate)) {
            //determine if any identifying fields are set
            $username_set = isset($record->username) && $record->username !== '';
            $email_set = isset($record->email) && $record->email !== '';
            $idnumber_set = isset($record->idnumber) && $record->idnumber !== '';

            //make sure at least one identifying field is set
            if ($username_set || $email_set || $idnumber_set) {
                //identify the user
                $params = array();
                if ($username_set) {
                    $params['username'] = $record->username;
                    $params['mnethostid'] = $CFG->mnet_localhost_id;
                }
                if ($email_set) {
                    $params['email'] = $record->email;
                }
                if ($idnumber_set) {
                    $params['idnumber'] = $record->idnumber;
                }

                if ($DB->record_exists('user', $params)) {
                    //user exists, so the action is an update
                    $action = 'update';
                } else {
                    //user does not exist, so the action is a create
                    $action = 'create';
                }
            } else {
                $action = 'create';
            }
        }

        return $action;
    }

    /**
     * Removes fields equal to the empty string from the provided record
     *
     * @param object $record The import record
     * @return object A version of the import record, with all empty fields removed
     */
    function remove_empty_fields($record) {
        foreach ($record as $key => $value) {
            if ($value === '') {
                unset($record->$key);
            }
        }

        return $record;
    }

    /**
     * Calculates a string that specifies which fields can be used to identify
     * a user record based on the import record provided
     *
     * Can be called statically if $value_syntax is false
     *
     * @param object $record
     * @param boolean $value_syntax true if we want to use "field" value of
     *                              "value" syntax, otherwise use field "value"
     *                              syntax
     * @return string The description of identifying fields, as a
     *                comma-separated string
     * [field1] "value1", ...
     */
    function get_user_descriptor($record, $value_syntax = false) {
        $fragments = array();

        //the fields we care to check
        $possible_fields = array('username',
                                 'email',
                                 'idnumber');

        foreach ($possible_fields as $field) {
            if (isset($record->$field) && $record->$field !== '') {
                //data for that field
                $value = $record->$field;

                //calculate syntax fragment
                if ($value_syntax) {
                    $identifier = $this->mappings[$field];
                    $fragments[] = "{$identifier} value of \"{$value}\"";
                } else {
                    $fragments[] = "{$field} \"{$value}\"";
                }
            }
        }

        //combine into string
        return implode(', ', $fragments);
    }

    /**
     * Calculates a string that specifies a descriptor for a context instance
     *
     * @param object $record The object specifying the context and instance
     * @return string The descriptive string
     */
    static function get_context_descriptor($record) {
        if ($record->context == 'system') {
            //no instance for the system context
            $context_descriptor = 'the system context';
        } else if ($record->context == 'coursecat') {
            //convert "coursecat" to "course category" due to legacy 1.9 weirdness
            $context_descriptor = "course category \"{$record->instance}\"";
        } else {
            //standard case
            $context_descriptor = "{$record->context} \"{$record->instance}\"";
        }

        return $context_descriptor;
    }

    /**
     * Delegate processing of an import line for entity type "user"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     * @param string $filename The import file name, used for logging
     *
     * @return boolean true on success, otherwise false
     */
    function user_action($record, $action = '', $filename = '') {
        if ($action === '') {
            //set from param
            $action = isset($record->action) ? $record->action : '';
        }

        if (!$this->check_action_field('user', $record, $filename)) {
            //missing an action value
            return false;
        }

        //apply "createorupdate" flag, if necessary
        //using "add" for legacy support
        if ($action == 'create' || $action == 'add') {
            $action = $this->handle_user_createorupdate($record, $action);
        }
        $record->action = $action;

        if (!$this->check_required_fields('user', $record, $filename)) {
            //missing a required field
            return false;
        }

        //remove empty fields
        $record = $this->remove_empty_fields($record);

        //perform action
        $method = "user_{$action}";
        return $this->$method($record, $filename);
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
    function validate_core_user_data($action, $record, $filename) {
        global $CFG;

        //make sure auth plugin refers to a valid plugin
        $auths = get_plugin_list('auth');
        if (!$this->validate_fixed_list($record, 'auth', array_keys($auths))) {
            $identifier = $this->mappings['auth'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->auth}\" is not a valid auth plugin.", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure password satisfies the site password policy
        if (isset($record->password)) {
            $errmsg = '';
            if (!check_password_policy($record->password, $errmsg)) {
                $identifier = $this->mappings['password'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->password}\" does not conform to your site's password policy.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        //make sure email is in user@domain.ext format
        if ($action == 'create') {
            if (!validate_email($record->email)) {
                $identifier = $this->mappings['email'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->email}\" is not a valid email address.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        //make sure maildigest is one of the available values
        if (!$this->validate_fixed_list($record, 'maildigest', array(0, 1, 2))) {
            $identifier = $this->mappings['maildigest'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->maildigest}\" is not one of the available options (0, 1, 2).", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure autosubscribe is one of the available values
        if (!$this->validate_fixed_list($record, 'autosubscribe', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['autosubscribe'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->autosubscribe}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure trackforums can only be set if feature is enabled
        if (isset($record->trackforums)) {
            if (empty($CFG->forum_trackreadposts)) {
                $this->fslogger->log_failure("Tracking unread posts is currently disabled on this site.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        //make sure trackforums is one of the available values
        if (!$this->validate_fixed_list($record, 'trackforums', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['trackforums'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->trackforums}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure screenreader is one of the available values
        if (!$this->validate_fixed_list($record, 'screenreader', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['screenreader'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->screenreader}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure country refers to a valid country code
        $countries = get_string_manager()->get_list_of_countries();
        if (!$this->validate_fixed_list($record, 'country',
                        array_keys($countries), array_flip($countries))) {
            $identifier = $this->mappings['country'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->country}\" is not a valid country or country code.", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure timezone can only be set if feature is enabled
        if (isset($record->timezone)) {
            if ($CFG->forcetimezone != 99 && $record->timezone != $CFG->forcetimezone) {
                $identifier = $this->mappings['timezone'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->timezone}\" is not consistent with forced timezone value of \"{$CFG->forcetimezone}\" on your site.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        //make sure timezone refers to a valid timezone offset
        $timezones = get_list_of_timezones();
        if (!$this->validate_fixed_list($record, 'timezone', array_keys($timezones))) {
            $identifier = $this->mappings['timezone'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->timezone}\" is not a valid timezone.", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure theme can only be set if feature is enabled
        if (isset($record->theme)) {
            if (empty($CFG->allowuserthemes)) {
                $this->fslogger->log_failure("User themes are currently disabled on this site.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        //make sure theme refers to a valid theme
        if ($this->themes == array()) {
            //lazy-loading of themes, store to save time
            $this->themes = get_list_of_themes();
        }

        if (!$this->validate_fixed_list($record, 'theme', array_keys($this->themes))) {
            $identifier = $this->mappings['theme'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->theme}\" is not a valid theme.", 0, $filename, $this->linenumber, $record, "user");
            return false;
        }

        //make sure lang refers to a valid language
        $languages = get_string_manager()->get_list_of_translations();
        if (!$this->validate_fixed_list($record, 'lang', array_keys($languages))) {
            $identifier = $this->mappings['lang'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->lang}\" is not a valid language code.", 0, $filename, $this->linenumber, $record, "user");
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
    function validate_user_profile_data($record, $filename) {
        //go through each profile field in the header

        foreach ($this->fields as $shortname => $field) {
            $key = 'profile_field_'.$shortname;
            $data = $record->$key;

            //perform type-specific validation and transformation
            if ($field->datatype == 'checkbox') {
                if ($data != 0 && $data != 1) {
                    $this->fslogger->log_failure("\"{$data}\" is not one of the available options for a checkbox profile field {$shortname} (0, 1).", 0, $filename, $this->linenumber, $record, "user");
                    return false;
                }
            } else if ($field->datatype == 'menu') {
                $options = explode("\n", $field->param1);
                if (!in_array($data, $options)) {
                    $this->fslogger->log_failure("\"{$data}\" is not one of the available options for a menu of choices profile field {$shortname}.", 0, $filename, $this->linenumber, $record, "user");
                    return false;
                }
            } else if ($field->datatype == 'datetime') {
                $value = $this->parse_date($data);
                if ($value === false) {
                    $identifier = $this->mappings["profile_field_{$shortname}"];
                    $this->fslogger->log_failure("{$identifier} value of \"{$data}\" ".
                                                 "is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.",
                                                 0, $filename, $this->linenumber, $record, "user");
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
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_create($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('create', $record, $filename)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record, $filename)) {
            return false;
        }

        //uniqueness checks
        // ELIS-6881 -- refactored to make this a single query as opposed to up to three queries to determine if a user record
        //              exists or not.
        $select = '(username = :username AND mnethostid = :mnethostid) OR (email = :email)';
        $params = array(
            'username'   => $record->username,
            'mnethostid' => $CFG->mnet_localhost_id,
            'email'      => $record->email,
        );

        if (isset($record->idnumber)) {
             $select            .= ' OR (idnumber = :idnumber)';
             $params['idnumber'] = $record->idnumber;
        }

        $existing_user = $DB->get_record_select('user', $select, $params, 'username, email, idnumber', IGNORE_MISSING);

        if ($existing_user !== false) {
            if ($existing_user->username == $record->username)  {
                $identifier = $this->mappings['username'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->username}\" refers to a user that already exists.",
                                             0, $filename, $this->linenumber, $record, "user");
            } else if ($existing_user->email == $record->email) {
                $identifier = $this->mappings['email'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->email}\" refers to a user that already exists.",
                                             0, $filename, $this->linenumber, $record, "user");
            } else if (isset($record->idnumber) && $existing_user->idnumber == $record->idnumber) {
                $identifier = $this->mappings['idnumber'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->idnumber}\" refers to a user that already exists.",
                                             0, $filename, $this->linenumber, $record, "user");
            }

            return false;
        }

        //final data sanitization
        if (!isset($record->description)) {
            $record->description = '';
        }

        if (!isset($record->lang)) {
            $record->lang = $CFG->lang;
        }

        //write to the database
        $record->descriptionformat = FORMAT_HTML;
        $record->mnethostid = $CFG->mnet_localhost_id;
        $record->password = hash_internal_user_password($record->password);
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        //make sure the user is confirmed!
        $record->confirmed = 1;

        $record->id = $DB->insert_record('user', $record);

        profile_save_data($record);

        //sync to PM is necessary
        $user = $DB->get_record('user', array('id' => $record->id));
        events_trigger('user_created', $user);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);

        //log success
        $this->fslogger->log_success("User with {$user_descriptor} successfully created.", 0, $filename, $this->linenumber);

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Add a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_add($record, $filename) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->user_create($record, $filename);
    }

    /**
     * Generate "Username matches different user than [idnumber] [or] [email]"
     * @params array  $params     record parameters
     * @params int    $usernameid user id of username param
     * @params int    $idnumberid user id of idnumber param
     * @params int    $emailid    user id of email param
     * @return string the error string
     */
    function user_matches_multiple_string($params, $usernameid, $idnumberid, $emailid) {
        $msg = array();
        if (!empty($params['username']) && !empty($usernameid)) {
            $msg[] = "username \"{$params['username']}\"";
        }
        if (!empty($params['email']) && !empty($emailid)) {
            $msg[] = "email \"{$params['email']}\"";
        }
        if (!empty($params['idnumber']) && !empty($idnumberid)) {
            $msg[] = "idnumber \"{$params['idnumber']}\"";
        }
        return implode(', ', $msg) .' matches multiple users.';
    }

    /**
     * Update a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_update($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('update', $record, $filename)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record, $filename)) {
            return false;
        }

        //find existing user record
        $params = array();
        $usernameid = 0;
        if (isset($record->username)) {
            $params['username']   = $record->username;
            $params['mnethostid'] = $CFG->mnet_localhost_id;
            $updateusername = $DB->get_record('user', $params);
            if (!$updateusername) {
                $identifier = $this->mappings['username'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['username']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $usernameid = $updateusername->id;
        }

        $eamilid = 0;
        if (isset($record->email)) {
            $params['email'] = $record->email;
            $updateemail = $DB->get_record('user', array('email' => $params['email']));
            if (!$updateemail) {
                $identifier = $this->mappings['email'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['email']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $emailid = $updateemail->id;
        }

        $idnumberid = 0;
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
            $updateidnumber = $DB->get_record('user', array('idnumber' => $params['idnumber']));
            if (!$updateidnumber) {
                $identifier = $this->mappings['idnumber'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['idnumber']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $idnumberid = $updateidnumber->id;
        }

        $record->id = $DB->get_field('user', 'id', $params);
        if (empty($record->id)) {
            $msg = $this->user_matches_multiple_string($params, $usernameid, $idnumberid, $emailid);
            $this->fslogger->log_failure($msg, 0, $filename, $this->linenumber,
                                         $record, "user");
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

        profile_save_data($record);

        // trigger user_updated event on the full database user row
        $updateduser = $DB->get_record('user', array('id' => $record->id));
        events_trigger('user_updated', $updateduser);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);

        //log success
        $this->fslogger->log_success("User with {$user_descriptor} successfully updated.", 0, $filename, $this->linenumber);

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Delete a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_delete($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //find existing user record
        $params = array();
        $usernameid = 0;
        if (isset($record->username)) {
            $params['username']   = $record->username;
            $params['mnethostid'] = $CFG->mnet_localhost_id;
            $updateusername = $DB->get_record('user', $params);
            if (!$updateusername) {
                $identifier = $this->mappings['username'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['username']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $usernameid = $updateusername->id;
        }

        $emailid = 0;
        if (isset($record->email)) {
            $params['email'] = $record->email;
            $updateemail = $DB->get_record('user', array('email' => $params['email']));
            if (!$updateemail) {
                $identifier = $this->mappings['email'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['email']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $emailid = $updateemail->id;
        }

        $idnumberid = 0;
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
            $updateidnumber = $DB->get_record('user', array('idnumber' => $params['idnumber']));
            if (!$updateidnumber) {
                $identifier = $this->mappings['idnumber'];
                $this->fslogger->log_failure("{$identifier} value of \"{$params['idnumber']}\" does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
            $idnumberid = $updateidnumber->id;
        }

        //make the appropriate changes
        if ($user = $DB->get_record('user', $params)) {
            user_delete_user($user);

            //string to describe the user
            $user_descriptor = $this->get_user_descriptor($record);

            //log success
            $this->fslogger->log_success("User with {$user_descriptor} successfully deleted.", 0, $filename, $this->linenumber);

            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
            return true;
        } else {
            // parameters point to different users
            $msg = $this->user_matches_multiple_string($params, $usernameid, $idnumberid, $emailid);
            $this->fslogger->log_failure($msg, 0, $filename, $this->linenumber,
                                         $record, "user");
            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
        }

        return false;
    }

    /**
     * Create a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_disable($record, $filename) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->user_delete($record, $filename);
    }

    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_course_createorupdate($record, $action) {
        global $DB;

        //check config setting
        $createorupdate = get_config('rlipimport_version1', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->shortname) && $record->shortname !== '') {
                //identify the course
                if ($DB->record_exists('course', array('shortname' => $record->shortname))) {
                    //course exists, so the action is an update
                    $action = 'update';
                } else {
                    //course does not exist, so the action is a create
                    $action = 'create';
                }
            } else {
                $action = 'create';
            }
        }

        return $action;
    }

    /**
     * Delegate processing of an import line for entity type "course"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     * @param string $filename The import file name, used for logging
     *
     * @return boolean true on success, otherwise false
     */
    function course_action($record, $action = '', $filename = '') {
        if ($action === '') {
            //set from param
            $action = isset($record->action) ? $record->action : '';
        }

        if (!$this->check_action_field('course', $record, $filename)) {
            //missing an action value
            return false;
        }

        //apply "createorupdate" flag, if necessary
        if ($action == 'create') {
            $action = $this->handle_course_createorupdate($record, $action);
        }
        $record->action = $action;

        if (!$this->check_required_fields('course', $record, $filename)) {
            //missing a required field
            return false;
        }

        //remove empty fields
        $record = $this->remove_empty_fields($record);

        //perform action
        $method = "course_{$action}";
        return $this->$method($record, $filename);
    }

    /**
     * Remove invalid fields from a course record
     * @todo: consider generalizing this
     *
     * @param object $record The course record
     * @return object The course record with the invalid fields removed
     */
    function remove_invalid_course_fields($record) {
        $allowed_fields = $this->get_available_fields('course');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($record->$key);
            }
        }

        return $record;
    }

    /**
     * Check the lengths of fields from a course record
     * @todo: consider generalizing
     *
     * @param object $record The course record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_course_field_lengths($record, $filename) {
        $lengths = array('fullname' => 254,
                         'shortname' => 100,
                         'idnumber' => 100);

        return $this->check_field_lengths('course', $record, $filename, $lengths);
    }

    /**
     * Intelligently splits a category specification into a list of categories
     *
     * @param string $category_string  The category specification string, using
     *                                 \\\\ to represent \, \\/ to represent /,
     *                                 and / as a category separator
     * @return array An array with one entry per category, containing the
     *               unescaped category names
     */
    function get_category_path($category_string) {
        //in-progress method result
        $result = array();

        //used to build up the current token before splitting
        $current_token = '';

        //tracks which token we are currently looking at
        $current_token_num = 0;

        for ($i = 0; $i < strlen($category_string); $i++) {
            //initialize the entry if necessary
            if (!isset($result[$current_token_num])) {
                $result[$current_token_num] = '';
            }

            //get the ith character from the category string
            $current_token .= substr($category_string, $i, 1);

            if(strpos($current_token, '\\\\') === strlen($current_token) - strlen('\\\\')) {
                //backslash character

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('\\\\')) . '\\';
                //reset the token
                $current_token = '';
            } else if(strpos($current_token, '\\/') === strlen($current_token) - strlen('\\/')) {
                //forward slash character

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('\\/')) . '/';
                //reset the token so that the / is not accidentally counted as a category separator
                $current_token = '';
            } else if(strpos($current_token, '/') === strlen($current_token) - strlen('/')) {
                //category separator

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('/'));
                //reset the token
                $current_token = '';
                //move on to the next token
                $current_token_num++;
            }
        }

        //append leftovers after the last slash

        //initialize the entry if necessary
        if (!isset($result[$current_token_num])) {
            $result[$current_token_num] = '';
        }

        $result[$current_token_num] .= $current_token;

        return $result;
    }

    /**
     * Map the specified category to a record id
     *
     * @param string $category_string The category specification string, using
     *                                \\\\ to represent \, \\/ to represent /,
     *                                and / as a category separator
     * @return mixed Returns false on error, or the integer category id otherwise
     */
    function get_category_id($record, $filename) {
        global $DB;

        $category_string = $record->category;

        $parentids = array();

        //check for a leading / for the case where an absolute path is specified
        if (strpos($category_string, '/') === 0) {
            $category_string = substr($category_string, 1);
            $parentids[] = 0;
        }

        //split the category string into a list of categories
        $path = $this->get_category_path($category_string);

        foreach ($path as $categoryname) {
            //look for categories with the correct name
            $select = "name = ?";
            $params = array($categoryname);

            if (!empty($parentids)) {
                //only allow categories that also are children of categories
                //found in the last iteration of the specified path
                list($parentselect, $parentparams) = $DB->get_in_or_equal($parentids);
                $select = "{$select} AND parent {$parentselect}";
                $params = array_merge($params, $parentparams);
            }

            //find matching records
            if ($records = $DB->get_recordset_select('course_categories', $select, $params)) {
                if (!$records->valid()) {
                    //none found, so try see if the id was specified
                    if (is_numeric($category_string)) {
                        if ($DB->record_exists('course_categories', array('id' => $category_string))) {
                            return $category_string;
                        }
                    }

                    $parent = 0;
                    if (count($parentids) == 1) {
                        //we have a specific parent to create a child for
                        $parent = $parentids[0];
                    } else if (count($parentids) > 0) {
                        //ambiguous parent, so we can't continue
                        $identifier = $this->mappings['category'];
                        $this->fslogger->log_failure("{$identifier} value of \"{$category_string}\" ".
                                                     "refers to an ambiguous parent category path.",
                                                     0, $filename, $this->linenumber, $record, 'course');
                        return false;
                    }

                    //create a new category
                    $newcategory = new stdClass;
                    $newcategory->name = $categoryname;
                    $newcategory->parent = $parent;
                    $newcategory->id = $DB->insert_record('course_categories', $newcategory);

                    //set "parent ids" to the new category id
                    $parentids = array($newcategory->id);
                } else {
                    //set "parent ids" to the current result set for our next iteration
                    $parentids = array();

                    foreach ($records as $childrecord) {
                        $parentids[] = $childrecord->id;
                    }
                }
            }
        }

        if (count($parentids) == 1) {
            //found our category
            return $parentids[0];
        } else {
            //path refers to multiple potential categories
            $identifier = $this->mappings['category'];
            $this->fslogger->log_failure("{$identifier} value of \"{$category_string}\" refers to ".
                                         "multiple categories.", 0, $filename, $this->linenumber,
                                         $record, 'course');
            return false;
        }
    }

    /**
     * Validates that core course fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_core_course_data($action, $record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //make sure theme can only be set if feature is enabled
        if (isset($record->theme)) {
             if (empty($CFG->allowcoursethemes)) {
               $this->fslogger->log_failure("Course themes are currently disabled on this site.", 0, $filename, $this->linenumber, $record, "course");
               return false;
           }
        }

        //make sure theme refers to a valid theme
        if ($this->themes == array()) {
            //lazy-loading of themes, store to save time
            $this->themes = get_list_of_themes();
        }

        if (!$this->validate_fixed_list($record, 'theme', array_keys($this->themes))) {
            $identifier = $this->mappings['theme'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->theme}\" is not a valid theme.", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure format refers to a valid course format
        if (isset($record->format)) {
            $courseformats = get_plugin_list('format');

            if (!$this->validate_fixed_list($record, 'format', array_keys($courseformats))) {
                $identifier = $this->mappings['format'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->format}\" does not refer to a valid course format.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        //make sure numsections is an integer between 0 and the configured max
        if (isset($record->numsections)) {
            $maxsections = (int)get_config('moodlecourse', 'maxsections');

            if ((int)$record->numsections != $record->numsections) {
                //not an integer
                return false;
            }

            $record->numsections = (int)$record->numsections;
            if ($record->numsections < 0 || $record->numsections > $maxsections) {
                $identifier = $this->mappings['numsections'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->numsections}\" is not one of the available options (0 .. {$maxsections}).", 0, $filename, $this->linenumber, $record, "course");
                //not between 0 and max
                return false;
            }
        }

        //make sure startdate is a valid date
        if (isset($record->startdate)) {
            $value = $this->parse_date($record->startdate);
            if ($value === false) {
                $identifier = $this->mappings['startdate'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->startdate}\" is not a ".
                                             "valid date in MMM/DD/YYYY or MM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "course");
                return false;
            }

            //use the unix timestamp
            $record->startdate = $value;
        }

        //make sure newsitems is an integer between 0 and 10
        $options = range(0, 10);
        if (!$this->validate_fixed_list($record, 'newsitems', $options)) {
            $identifier = $this->mappings['newsitems'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->newsitems}\" is not one of the available options (0 .. 10).", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure showgrades is one of the available values
        if (!$this->validate_fixed_list($record, 'showgrades', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['showgrades'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->showgrades}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure showreports is one of the available values
        if (!$this->validate_fixed_list($record, 'showreports', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['showreports'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->showreports}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure maxbytes is one of the available values
        if (isset($record->maxbytes)) {
            $choices = get_max_upload_sizes($CFG->maxbytes);
            if (!$this->validate_fixed_list($record, 'maxbytes', array_keys($choices))) {
                $identifier = $this->mappings['maxbytes'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->maxbytes}\" is not one of the available options.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        //make sure guest is one of the available values
        if (!$this->validate_fixed_list($record, 'guest', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['guest'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->guest}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure visible is one of the available values
        if (!$this->validate_fixed_list($record, 'visible', array(0, 1),
                                        array('no' => 0, 'yes' => 1))) {
            $identifier = $this->mappings['visible'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->visible}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //make sure lang refers to a valid language or the default value
        $languages = get_string_manager()->get_list_of_translations();
        $language_codes = array_merge(array(''), array_keys($languages));
        if (!$this->validate_fixed_list($record, 'lang', $language_codes)) {
            $identifier = $this->mappings['lang'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->lang}\" is not a valid language code.", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        //determine if this plugin is even enabled
        $enabled = explode(',', $CFG->enrol_plugins_enabled);
        if (!in_array('guest', $enabled) && !empty($record->guest)) {
            $this->fslogger->log_failure("guest enrolments cannot be enabled because the guest enrolment plugin is globally disabled.", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        if ($action == 'create') {
            //make sure "guest" settings are consistent for new course
            if (isset($record->guest) && empty($record->guest) && !empty($record->password)) {
                //password set but guest is not enabled
                $this->fslogger->log_failure('guest enrolment plugin cannot be assigned a password '.
                                             'because the guest enrolment plugin is not enabled.',
                                             0, $filename, $this->linenumber, $record, 'course');
                return false;
            }

            $defaultenrol = get_config('enrol_guest', 'defaultenrol');
            if (empty($defaultenrol) && !empty($record->guest)) {
                //enabling guest access without the guest plugin being added by default
                $this->fslogger->log_failure('guest enrolment plugin cannot be assigned a password '.
                                             'because the guest enrolment plugin is not configured '.
                                             'to be added to new courses by default.',
                                             0, $filename, $this->linenumber, $record, 'course');
                return false;
            } else if (empty($defaultenrol) && !empty($record->password)) {
                //enabling guest password without the guest plugin being added by default
                $this->fslogger->log_failure('guest enrolment plugin cannot be assigned a password '.
                                             'because the guest enrolment plugin is not configured to '.
                                             'be added to new courses by default.', 0, $filename, $this->linenumber,
                                             $record, 'course');
                return false;
            }

            //make sure we don't have a course "link" (template) that refers to
            //an invalid course shortname
            if (isset($record->link)) {
                if (!$DB->record_exists('course', array('shortname' => $record->link))) {
                    $this->fslogger->log_failure("Template course with shortname \"{$record->link}\" ".
                                                 "could not be found.", 0, $filename, $this->linenumber,
                                                 $record, 'course');
                    return false;
                }
            }
        }

        if ($action == 'update') {
            //todo: consider moving into course_update function
            //make sure "guest" settings are consistent for new course

            //determine whether the guest enrolment plugin is added to the current course
            $guest_plugin_exists = false;
            if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {
                if ($DB->record_exists('enrol', array('courseid' => $courseid,
                                                      'enrol' => 'guest'))) {
                    $guest_plugin_exists = true;
                }
            }

            if (!$guest_plugin_exists) {
                //guest enrolment plugin specifically removed from course
                if (isset($record->guest)) {
                    $this->fslogger->log_failure("guest enrolment plugin cannot be enabled because ".
                                                 "the guest enrolment plugin has been removed from ".
                                                 "course \"{$record->shortname}\".", 0, $filename, $this->linenumber,
                                                 $record, 'course');
                    return false;
                } else if (isset($record->password)) {
                    $this->fslogger->log_failure("guest enrolment plugin cannot be assigned a password ".
                                                 "because the guest enrolment plugin has been removed ".
                                                 "from course \"{$record->shortname}\".", 0, $filename, $this->linenumber,
                                                 $record, 'course');
                    return false;
                }
            }

            if (!empty($record->password)) {
                //make sure a password can only be set if guest access is enabled
                if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {

                    if (isset($record->guest) && empty($record->guest)) {
                        //guest access specifically disabled, which isn't
                        //consistent with providing a password
                        $this->fslogger->log_failure("guest enrolment plugin cannot be assigned a ".
                                                     "password because the guest enrolment plugin has been ".
                                                     "disabled in course \"{$record->shortname}\".",
                                                     0, $filename, $this->linenumber,
                                                     $record, 'course');
                        return false;
                    } else if (!isset($record->guest)) {
                        $params = array('courseid' => $courseid,
                                        'enrol' => 'guest',
                                        'status' => ENROL_INSTANCE_ENABLED);
                        if (!$DB->record_exists('enrol', $params)) {
                            //guest access disabled in the database
                            $this->fslogger->log_failure("guest enrolment plugin cannot be assigned a ".
                                                         "password because the guest enrolment plugin has been ".
                                                         "disabled in course \"{$record->shortname}\".",
                                                         0, $filename, $this->linenumber,
                                                         $record, 'course');
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Create a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_create($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_course_fields($record);

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_course_data('create', $record, $filename)) {
            return false;
        }

        //validate and set up the category
        $categoryid = $this->get_category_id($record, $filename);
        if ($categoryid === false) {
            return false;
        }

        $record->category = $categoryid;

        //uniqueness check
        if ($DB->record_exists('course', array('shortname' => $record->shortname))) {
            $identifier = $this->mappings['shortname'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->shortname}\" refers to a ".
                                         "course that already exists.", 0, $filename, $this->linenumber,
                                         $record, 'course');
            return false;
        }

        //final data sanitization
        if (isset($record->guest)) {
            if ($record->guest == 0) {
                $record->enrol_guest_status_0 = ENROL_INSTANCE_DISABLED;
            } else {
                $record->enrol_guest_status_0 = ENROL_INSTANCE_ENABLED;
                if (isset($record->password)) {
                    $record->enrol_guest_password_0 = $record->password;
                } else {
                    $record->enrol_guest_password_0 = NULL;
                }
            }
        }

        //check that any unset fields are set to course default
        $courseconfig = get_config('moodlecourse');

        //set up an array with all the course fields that have defaults
        $course_defaults = array('format', 'numsections', 'hiddensections', 'newsitems', 'showgrades',
            'showreports', 'maxbytes', 'groupmode', 'groupmodeforce', 'visible', 'lang');
        foreach ($course_defaults as $course_default) {
            if (!isset($record->$course_default) && isset($courseconfig->$course_default)) {
                $record->$course_default = $courseconfig->$course_default;
            }
        }

        //write to the database
        if (isset($record->link)) {
            //creating from template
            require_once($CFG->dirroot.'/elis/core/lib/setup.php');
            require_once(elis::lib('rollover/lib.php'));
            $courseid = $DB->get_field('course', 'id', array('shortname' => $record->link));

            //perform the content rollover
            $record->id = course_rollover($courseid);
            //update appropriate fields, such as shortname
            //todo: validate if this fully works with guest enrolments?
            update_course($record);

            //log success
            $this->fslogger->log_success("Course with shortname \"{$record->shortname}\" successfully created from template course with shortname \"{$record->link}\".", 0, $filename, $this->linenumber);
        } else {
            //creating directly (not from template)
            create_course($record);

            //log success
            $this->fslogger->log_success("Course with shortname \"{$record->shortname}\" successfully created.", 0, $filename, $this->linenumber);
        }

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Update a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_update($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //remove invalid fields
        $record = $this->remove_invalid_course_fields($record);

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_course_data('update', $record, $filename)) {
            return false;
        }

        //validate and set up the category
        if (isset($record->category)) {
            $categoryid = $this->get_category_id($record, $filename);
            if ($categoryid === false) {
                return false;
            }

            $record->category = $categoryid;
        }

        $record->id = $DB->get_field('course', 'id', array('shortname' => $record->shortname));
        if (empty($record->id)) {
            $identifier = $this->mappings['shortname'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->shortname}\" does not refer to a valid course.", 0, $filename, $this->linenumber, $record, "course");
            return false;
        }

        update_course($record);

        //special work for "guest" settings

        if (isset($record->guest) && empty($record->guest)) {
            //todo: add more error checking
            if ($enrol = $DB->get_record('enrol', array('courseid' => $record->id,
                                                        'enrol' => 'guest'))) {
                //disable the plugin for the current course
                $enrol->status = ENROL_INSTANCE_DISABLED;
                $DB->update_record('enrol', $enrol);
            } else {
                //should never get here due to validation
                //$this->process_error("[$filename line $this->linenumber] \"guest\" enrolments cannot be enabled because the guest enrolment plugin has been removed from course {$record->shortname}.");
                return false;
            }
        }

        if (!empty($record->guest)) {
            //todo: add more error checking
            if ($enrol = $DB->get_record('enrol', array('courseid' => $record->id,
                                                        'enrol' => 'guest'))) {
                //enable the plugin for the current course
                $enrol->status = ENROL_INSTANCE_ENABLED;
                if (isset($record->password)) {
                    //password specified, so set it
                    $enrol->password = $record->password;
                }
                $DB->update_record('enrol', $enrol);
            } else {
                //should never get here due to validation
                //$this->process_error("[$filename line $this->linenumber] guest enrolment plugin cannot be assigned a password because the guest enrolment plugin has been removed from course {$record->shortname}.");
                return false;
            }
        }

        //log success
        $this->fslogger->log_success("Course with shortname \"{$record->shortname}\" successfully updated.", 0, $filename, $this->linenumber);

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Delete a course
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_delete($record, $filename) {
        global $DB;

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {
            delete_course($courseid, false);
            fix_course_sortorder();

            //log success
            $this->fslogger->log_success("Course with shortname \"{$record->shortname}\" successfully deleted.", 0, $filename, $this->linenumber);

            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
            return true;
        }

        $identifier = $this->mappings['shortname'];
        $this->fslogger->log_failure("{$identifier} value of \"{$record->shortname}\" does not ".
                                     "refer to a valid course.", 0, $filename, $this->linenumber,
                                     $record, 'course');

        return false;
    }

    /**
     * Delegate processing of an import line for entity type "enrolment"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     * @param string $filename The import file name, used for logging
     *
     * @return boolean true on success, otherwise false
     */
    function enrolment_action($record, $action = '', $filename = '') {
        if ($action === '') {
            //set from param
            $action = isset($record->action) ? $record->action : '';
        }

        if (!$this->check_action_field('enrolment', $record, $filename)) {
            //missing an action value
            return false;
        }

        $record->action = $action;
        $exceptions = array('instance' => array('context' => 'system'));
        if (!$this->check_required_fields('enrolment', $record, $filename, $exceptions)) {
            //missing a required field
            return false;
        }

        //remove empty fields
        $record = $this->remove_empty_fields($record);

        //perform action
        $method = "enrolment_{$action}";
        if (method_exists($this, $method)) {
            return $this->$method($record, $filename);
        } else {
            //todo: add logging
            return false;
        }
    }

    /**
     * Check the lengths of fields from an enrolment record
     * @todo: consider generalizing
     *
     * @param object $record The course record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_enrolment_field_lengths($record, $filename) {
        $lengths = array('username' => 100,
                         'email' => 100,
                         'idnumber' => 255,
                         'group' => 254,
                         'grouping' => 254);

        return $this->check_field_lengths('enrolment', $record, $filename, $lengths);
    }

    /**
     * Obtains a userid from a data record, logging an error message to the
     * file system log on failure
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return mixed The user id, or false if not found
     */
    function get_userid_from_record($record, $filename) {
        global $CFG, $DB;

        //find existing user record
        $params = array();
        //track how many fields identify the user
        $num_identifiers = 0;

        if (isset($record->username)) {
            $num_identifiers++;
            $params['username']   = $record->username;
            $params['mnethostid'] = $CFG->mnet_localhost_id;
        }
        if (isset($record->email)) {
            $num_identifiers++;
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $num_identifiers++;
            $params['idnumber'] = $record->idnumber;
        }

        if (!$userid = $DB->get_field('user', 'id', $params)) {
            //failure

            //get description of identifying fields
            $user_descriptor = $this->get_user_descriptor((object)$params, true);

            if ($num_identifiers > 1) {
                $does_token = 'do';
            } else {
                $does_token = 'does';
            }

            //log message
            $this->fslogger->log_failure("{$user_descriptor} {$does_token} not refer to a valid user.",
                                         0, $filename, $this->linenumber, $record, 'enrolment');
            return false;
        }

        //success
        return $userid;
    }

    /**
     * Obtains a context level and context record based on a role assignment
     * data record, logging an error message to the file system on failure
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return mixed The user id, or
     */
    function get_contextinfo_from_record($record, $filename) {
        global $CFG, $DB;

        if ($record->context == 'course') {
            //find existing course
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $record->instance))) {
                //invalid shortname
                $identifier = $this->mappings['instance'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->instance}\" does not refer ".
                                             "to a valid instance of a course context.",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            }

            //obtain the course context instance
            $contextlevel = CONTEXT_COURSE;
            $context = get_context_instance($contextlevel, $courseid);
            return array($contextlevel, $context);
        } else if ($record->context == 'system') {
            //obtain the system context instance
            $contextlevel = CONTEXT_SYSTEM;
            $context = get_context_instance($contextlevel);
            return array($contextlevel, $context, false);
        } else if ($record->context == 'coursecat') {
            //make sure category name is not ambiguous
            $count = $DB->count_records('course_categories', array('name' => $record->instance));
            if ($count > 1) {
                //ambiguous category name
                $identifier = $this->mappings['instance'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->instance}\" refers to ".
                                             "multiple course category contexts.",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            }

            //find existing course category
            if (!$categoryid = $DB->get_field('course_categories', 'id', array('name' => $record->instance))) {
                //invalid name
                $identifier = $this->mappings['instance'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->instance}\" does not refer ".
                                             "to a valid instance of a course category context.",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            }

            //obtain the course category context instance
            $contextlevel = CONTEXT_COURSECAT;
            $context = get_context_instance($contextlevel, $categoryid);
            return array($contextlevel, $context, false);
        } else if ($record->context == 'user') {
            //find existing user
            if (!$targetuserid = $DB->get_field('user', 'id', array('username' => $record->instance,
                                                                    'mnethostid' => $CFG->mnet_localhost_id))) {
                //invalid username
                $identifier = $this->mappings['instance'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->instance}\" does not refer ".
                                             "to a valid instance of a user context.",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            }

            //obtain the user context instance
            $contextlevel = CONTEXT_USER;
            $context = get_context_instance($contextlevel, $targetuserid);
            return array($contextlevel, $context, false);
        } else {
            //currently only supporting course, system, user and category
            //context levels
            $identifier = $this->mappings['context'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->context}\" is not one of ".
                                         "the available options (system, user, coursecat, course).",
                                         0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }
    }

    /**
     * Create an enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function enrolment_create($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //set initial logging state with respect to enrolments (give non-specific message for now)
        $this->fslogger->set_enrolment_state(false, false);

        //field length checking
        $lengthcheck = $this->check_enrolment_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$roleid = $DB->get_field('role', 'id', array('shortname' => $record->role))) {
            $identifier = $this->mappings['role'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->role}\" does not refer ".
                                         "to a valid role.", 0, $filename, $this->linenumber,
                                         $record, 'enrolment');
            return false;
        }

        //find existing user record
        if (!$userid = $this->get_userid_from_record($record, $filename)) {
            return false;
        }

        //track context info
        $contextinfo = $this->get_contextinfo_from_record($record, $filename);
        if ($contextinfo == false) {
            return false;
        }
        list($contextlevel, $context) = $contextinfo;

        //make sure the role is assignable at the course context level
        if (!$DB->record_exists('role_context_levels', array('roleid' => $roleid,
                                                             'contextlevel' => $contextlevel))) {
            $this->fslogger->log_failure("The role with shortname \"{$record->role}\" is not assignable ".
                                         "on the {$record->context} context level.",
                                         0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        //note: this seems redundant but will be useful for error messages later
        $params = array('roleid' => $roleid,
                        'contextid' => $context->id,
                        'userid' => $userid,
                        'component' => '',
                        'itemid' => 0);
        $role_assignment_exists = $DB->record_exists('role_assignments', $params);

        //track whether an enrolment exists
        $enrolment_exists = false;

        if ($contextlevel == CONTEXT_COURSE) {
            $enrolment_exists = is_enrolled($context, $userid);
        }

        //after this point, general error messages should contain role assignment info
        //they should also contain enrolment info if the context is a course
        $track_enrolments = $record->context == 'course';
        $this->fslogger->set_enrolment_state(true, $track_enrolments);

        //track the group and grouping specified
        $groupid = 0;
        $groupingid = 0;

        //duplicate group / grouping name checks and name validity checking
        if ($record->context == 'course' && isset($record->group)) {
            $count = $DB->count_records('groups', array('name' => $record->group,
                                                        'courseid' => $context->instanceid));

            $creategroups = get_config('rlipimport_version1', 'creategroupsandgroupings');
            if ($count > 1) {
                //ambiguous
                $identifier = $this->mappings['group'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->group}\" refers to multiple ".
                                             "groups in course with shortname \"{$record->instance}\".",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            } else if ($count == 0 && empty($creategroups)) {
                //does not exist and not creating
                $identifier = $this->mappings['group'];
                $this->fslogger->log_failure("{$identifier} value of \"{$record->group}\" does not refer to ".
                                             "a valid group in course with shortname \"{$record->instance}\".",
                                             0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            } else {
                //exact group exists
                $groupid = groups_get_group_by_name($context->instanceid, $record->group);
            }

            if (isset($record->grouping)) {
                $count = $DB->count_records('groupings', array('name' => $record->grouping,
                                                               'courseid' => $context->instanceid));
                if ($count > 1) {
                    //ambiguous
                    $identifier = $this->mappings['grouping'];
                    $this->fslogger->log_failure("{$identifier} value of \"{$record->grouping}\" refers to multiple ".
                                                 "groupings in course with shortname \"{$record->instance}\".",
                                                 0, $filename, $this->linenumber, $record, "enrolment");
                    return false;
                } else if ($count == 0 && empty($creategroups)) {
                    //does not exist and not creating
                    $identifier = $this->mappings['grouping'];
                    $this->fslogger->log_failure("{$identifier} value of \"{$record->grouping}\" does not refer to ".
                                                 "a valid grouping in course with shortname \"{$record->instance}\".",
                                                 0, $filename, $this->linenumber, $record, "enrolment");
                    return false;
                } else {
                    //exact grouping exists
                    $groupingid = groups_get_grouping_by_name($context->instanceid, $record->grouping);
                }
            }
        }

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);
        //string to describe the context instance
        $context_descriptor = $this->get_context_descriptor($record);

        //going to collect all messages for this action
        $logmessages = array();

        if ($record->context == 'course') {

            //set enrolment start time to the course start date
            //$timestart = $DB->get_field('course', 'startdate', array('id' => $context->instanceid));
            // ELIS-6694: set enrolment time to 'now' to allow immediate access
            $timestart = time();
            if ($role_assignment_exists && !$enrolment_exists) {

                //role assignment already exists, so just enrol the user
                enrol_try_internal_enrol($context->instanceid, $userid, null, $timestart);
            } else if (!$enrolment_exists) {
                //role assignment does not exist, so enrol and assign role
                enrol_try_internal_enrol($context->instanceid, $userid, $roleid, $timestart);

                //collect success message for logging at end of action
                $logmessages[] = "User with {$user_descriptor} successfully assigned role with shortname ".
                                 "\"{$record->role}\" on {$context_descriptor}.";
            } else if (!$role_assignment_exists) {
                //just assign the role
                role_assign($roleid, $userid, $context->id);

                //collect success message for logging at end of action
                $logmessages[] = "User with {$user_descriptor} successfully assigned role with ".
                                 "shortname \"{$record->role}\" on {$context_descriptor}.";
            } else {
                //duplicate enrolment attempt
                $this->fslogger->log_failure("User with {$user_descriptor} is already assigned role ".
                                             "with shortname \"{$record->role}\" on {$context_descriptor}. ".
                                             "User with {$user_descriptor} is already enrolled in course with ".
                                             "shortname \"{$record->instance}\".", 0, $filename, $this->linenumber,
                                             $record, 'enrolment');
                return false;
            }

            //collect success message for logging at end of action
            if (!$enrolment_exists) {
                $logmessages[] = "User with {$user_descriptor} enrolled in course with shortname \"{$record->instance}\".";
            }
        } else {

            if ($role_assignment_exists) {
                //role assignment already exists, so this action serves no purpose
                $this->fslogger->log_failure("User with {$user_descriptor} is already assigned role ".
                                             "with shortname \"{$record->role}\" on {$context_descriptor}.",
                                             0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            }

            role_assign($roleid, $userid, $context->id);

            //collect success message for logging at end of action
            $logmessages[] = "User with {$user_descriptor} successfully assigned role with shortname \"{$record->role}\" on {$context_descriptor}.";
        }

        if ($record->context == 'course' && isset($record->group)) {
            //process specified group
            require_once($CFG->dirroot.'/lib/grouplib.php');
            require_once($CFG->dirroot.'/group/lib.php');

            if ($groupid == 0) {
                //need to create the group
                $data = new stdClass;
                $data->courseid = $context->instanceid;
                $data->name = $record->group;

                $groupid = groups_create_group($data);

                //collect success message for logging at end of action
                $logmessages[] = "Group created with name \"{$record->group}\".";
            }

            if (groups_is_member($groupid, $userid)) {
                //error handling
                $logmessages[] = "User with {$user_descriptor} is already assigned to group with name \"{$record->group}\".";
            } else {
                //try to assign the user to the group
                if (!groups_add_member($groupid, $userid)) {
                    //should never happen
                }

                //collect success message for logging at end of action
                $logmessages[] = "Assigned user with {$user_descriptor} to group with name \"{$record->group}\".";
            }

            if (isset($record->grouping)) {
                //process the specified grouping

                if ($groupingid == 0) {
                    //need to create the grouping
                    $data = new stdClass;
                    $data->courseid = $context->instanceid;
                    $data->name = $record->grouping;

                    $groupingid = groups_create_grouping($data);

                    //collect success message for logging at end of action
                    $logmessages[] = "Created grouping with name \"{$record->grouping}\".";
                }

                //assign the group to the grouping
                if ($DB->record_exists('groupings_groups', array('groupingid' => $groupingid,
                                                                 'groupid' => $groupid))) {
                    //error handling
                    $logmessages[] = "Group with name \"{$record->group}\" is already assigned to grouping with name \"{$record->grouping}\".";
                } else {
                    if (!groups_assign_grouping($groupingid, $groupid)) {
                        //should never happen
                    }

                    //collect success message for logging at end of action
                    $logmessages[] = "Assigned group with name \"{$record->group}\" to grouping with name \"{$record->grouping}\".";
                }
            }
        }

        //log success
        $this->fslogger->log_success(implode(' ', $logmessages), 0, $filename, $this->linenumber);

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Add an enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function enrolment_add($record, $filename) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->enrolment_create($record);
    }

    /**
     * Delete an enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function enrolment_delete($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //set initial logging state with respect to enrolments (give non-specific message for now)
        $this->fslogger->set_enrolment_state(false, false);

        //field length checking
        $lengthcheck = $this->check_enrolment_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$roleid = $DB->get_field('role', 'id', array('shortname' => $record->role))) {
            $identifier = $this->mappings['role'];
            $this->fslogger->log_failure("{$identifier} value of \"{$record->role}\" does not refer to a valid role.",
                                         0, $filename, $this->linenumber, $record, 'enrolment');
            return false;
        }

        //find existing user record
        if (!$userid = $this->get_userid_from_record($record, $filename)) {
            return false;
        }

        //track the context info
        $contextinfo = $this->get_contextinfo_from_record($record, $filename);
        if ($contextinfo == false) {
            return false;
        }
        list($contextlevel, $context) = $contextinfo;

        //track whether an enrolment exists
        $enrolment_exists = false;

        if ($contextlevel == CONTEXT_COURSE) {
            $enrolment_exists = is_enrolled($context, $userid);
        }

        //determine whether the role assignment and enrolment records exist
        $role_assignment_exists = $DB->record_exists('role_assignments', array('roleid' => $roleid,
                                                                               'contextid' => $context->id,
                                                                               'userid' => $userid));

        //after this point, general error messages should contain role assignment info
        //they should also contain enrolment info if the the context is a course
        $track_enrolments = $record->context == 'course';
        $this->fslogger->set_enrolment_state(true, $track_enrolments);

        if (!$role_assignment_exists) {
            $user_descriptor = $this->get_user_descriptor($record, false);
            $context_descriptor = $this->get_context_descriptor($record);
            $message = "User with {$user_descriptor} is not assigned role with ".
                       "shortname \"{$record->role}\" on {$context_descriptor}.";

            if ($record->context != 'course') {
                //nothing to delete
                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            } else if (!$enrolment_exists) {
                $message .= " User with {$user_descriptor} is not enrolled in ".
                            "course with shortname \"{$record->instance}\".";
                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, 'enrolment');
                return false;
            } else {
                //count how many role assignments the user has on this context
                $num_assignments = $DB->count_records('role_assignments', array('userid' => $userid,
                                                                                'contextid' => $context->id));

                if ($num_assignments > 0) {
                    //can't unenrol because of some other role assignment
                    $message .= " User with {$user_descriptor} requires their enrolment ".
                                "to be maintained because they have another role assignment in this course.";
                    $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, 'enrolment');
                    return false;
                }
            }
        }

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);
        //string to describe the context instance
        $context_descriptor = $this->get_context_descriptor($record);

        //going to collect all messages for this action
        $logmessages = array();

        if ($role_assignment_exists) {
            //unassign role
            role_unassign($roleid, $userid, $context->id);

            //collect success message for logging at end of action
            $logmessages[] = "User with {$user_descriptor} successfully unassigned role with shortname \"{$record->role}\" on {$context_descriptor}.";
        }

        if ($enrolment_exists) {
            //remove enrolment
            if ($instance = $DB->get_record('enrol', array('enrol' => 'manual',
                                                           'courseid' => $context->instanceid))) {

                //count how many role assignments the user has on this context
                $num_assignments = $DB->count_records('role_assignments', array('userid' => $userid,
                                                                                'contextid' => $context->id));

                if ($num_assignments == 0) {
                    //no role assignments left, so we can delete enrolment record
                    $plugin = enrol_get_plugin('manual');
                    $plugin->unenrol_user($instance, $userid);

                    //collect success message for logging at end of action
                    $logmessages[] = "User with {$user_descriptor} unenrolled from course with shortname \"{$record->instance}\".";
                }
            }
        }

        //log success
        $this->fslogger->log_success(implode(' ', $logmessages), 0, $filename, $this->linenumber);

        if (!$this->fslogger->get_logfile_status()) {
            return false;
        }
        return true;
    }

    /**
     * Apply the configured field mapping to a single record
     *
     * @param string $entity The type of entity
     * @param object $record One record of import data
     *
     * @return object The record, with the field mapping applied
     */
    function apply_mapping($entity, $record) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        //mappings should already be fetched
        foreach ($this->mappings as $standardfieldname => $customfieldname) {
            if ($standardfieldname != $customfieldname) {
                if (isset($record->$customfieldname)) {
                    //do the conversion
                    $record->$standardfieldname = $record->$customfieldname;
                    unset($record->$customfieldname);
                } else if (isset($record->$standardfieldname)) {
                    //remove the standard field because it should have been
                    //provided as a mapped value
                    unset($record->$standardfieldname);
                }
            }
        }

        return $record;
    }

    /**
     * Entry point for processing a single record
     *
     * @param string $entity The type of entity
     * @param object $record One record of import data
     * @param string $filename Import file name to user for logging
     *
     * @return boolean true on success, otherwise false
     */
    function process_record($entity, $record, $filename) {
        //apply the field mapping
        $record = $this->apply_mapping($entity, $record);

        return parent::process_record($entity, $record, $filename);
    }

    /**
     * Entry point for processing an import file
     *
     * @param string $entity       The type of entity
     * @param int    $maxruntime   The max time in seconds to complete import
     *                             default: 0 => unlimited time
     * @param object $state        Previous ran state data to continue from
     * @return mixed object        Current state of import processing
     *                             or null for success.
     */
    function process_import_file($entity, $maxruntime = 0, $state = null) {
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        //store field mappings for this entity type
        $this->mappings = rlipimport_version1_get_mapping($entity);

        return parent::process_import_file($entity, $maxruntime, $state);
    }

    /**
     * Specifies the UI labels for the various import files supported by this
     * plugin
     *
     * @return array The string labels, in the order in which the
     *               associated [entity]_action methods are defined
     */
    function get_file_labels() {
        return array(get_string('userfile', 'rlipimport_version1'),
                     get_string('coursefile', 'rlipimport_version1'),
                     get_string('enrolmentfile', 'rlipimport_version1'));
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
        $displaystring = get_string('configfieldstreelink', 'rlipimport_version1');
        $url = $CFG->wwwroot.'/blocks/rlip/importplugins/version1/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        //add it to the tree
        $adminroot->add($parentname, $page);
    }

    /**
     * Perform any necessary transformation on required fields
     * for display purposes
     *
     * @param mixed $fieldorgroup a single field name string, or an array
     *                            of them
     * @return mixed the field or array of fields to display
     */
    function get_required_field_display($fieldorgroup) {
        if (is_array($fieldorgroup)) {
            $result = array();
            foreach ($fieldorgroup as $field) {
                $result[] = $this->mappings[$field];
            }
            return $result;
        } else {
            return $this->mappings[$fieldorgroup];
        }
    }

    /**
     * Validate that the action field is included in the header
     *
     * @param string $entity Type of entity, such as 'user'
     * @param array $header The list of supplied header columns
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if the action column is correctly specified,
     *                 otherwise false
     */
    function check_action_header($entity, $header, $filename) {
        $translated_action = $this->mappings['action'];

        if (!in_array($translated_action, $header)) {
            //action column not specified
            $message = "Import file {$filename} was not processed because it is missing the ".
                       "following column: {$translated_action}. Please fix the import file and re-upload it.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            $this->dblogger->signal_missing_columns($message);
            return false;
        }

        return true;
    }

    /**
     * Validate that all required fields are included in the header
     *
     * @param string $entity Type of entity, such as 'user'
     * @param array $header The list of supplied header columns
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if the action column is correctly specified,
     *                 otherwise false
     */
    function check_required_headers($entity, $header, $filename) {
        //get list of required fields
        //note: for now, assuming that the delete action is available for
        //all entity types and requires the bare minimum in terms of fields
        $required_fields = $this->plugin_supports_action($entity, 'delete');

        //handle context / instance fix
        if ($entity == 'enrolment') {
            foreach ($required_fields as $key => $required_field) {
                if ($required_field == 'instance') {
                    //never require instance in the header, since each row
                    //could by a system-context role assignment
                    unset($required_fields[$key]);
                }
            }
        }

        //perform the necessary transformation on the list of required fields
        $translated_required_fields = array();
        foreach ($required_fields as $fieldorgroup) {
            if (is_array($fieldorgroup)) {
                $group = array();
                foreach ($fieldorgroup as $field) {
                    $group[] = $this->mappings[$field];
                }
                $translated_required_fields[] = $group;
            } else {
                $translated_required_fields[] = $this->mappings[$fieldorgroup];
            }
        }

        //convert the header into a data record
        $record = new stdClass;
        foreach ($header as $value) {
            $record->$value = $value;
        }

        //figure out which are missing
        $missing_fields = $this->get_missing_required_fields($record, $translated_required_fields);

        if ($missing_fields !== false) {
            $field_display = '';
            $first = reset($missing_fields);

            //for now, assume "groups" are always first and only showing
            //that one problem in the log
            if (!is_array($first)) {
                //1-of-n case

                //list of fields, as displayed
                $field_display = implode(', ', $missing_fields);

                //singular/plural handling
                $label = count($missing_fields) > 1 ? 'columns' : 'column';

                $message = "Import file {$filename} was not processed because it is missing the following ".
                           "required {$label}: {$field_display}. Please fix the import file and re-upload it.";
            } else {
                //basic case, all missing fields are required

                //list of fields, as displayed
                $group = reset($missing_fields);
                $field_display = implode(', ', $group);

                $message = "Import file {$filename} was not processed because one of the following columns is ".
                           "required but all are unspecified: {$field_display}. Please fix the import file and re-upload it.";
            }

            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            $this->dblogger->signal_missing_columns($message);
            return false;
        }

        return true;
    }

    /**
     * Obtain the file-system logger for this plugin
     *
     * @param object $fileplugin The file plugin used for IO in the logger
     * @param boolean $manual True on a manual run, false on a scheduled run
     * @return object The appropriate logging object
     */
    static function get_fs_logger($fileplugin, $manual) {
        require_once(dirname(__FILE__).'/rlip_import_version1_fslogger.class.php');

        return new rlip_import_version1_fslogger($fileplugin, $manual);
    }

    /**
     * Validates whether the "action" field is correctly set on a record,
     * logging error to the file system, if necessary - call from child class
     * when needed
     *
     * @param string $entitytype The type of entity we are performing an action on
     * @param object $record One data import record
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if action field is set, otherwise false
     */
    function check_action_field($entitytype, $record, $filename) {
        if (!isset($record->action) || $record->action === '') {
            //not set, so error

            //use helper to do any display-related field name transformation
            $field_display = $this->get_required_field_display('action');
            $message = "Required field {$field_display} is unspecified or empty.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $entitytype);

            return false;
        }

        //feature, in the standard Moodle "plugin_supports" format
        $feature = $entitytype.'_'.$record->action;

        if (!$this->plugin_supports($feature)) {
            //invalid action for this entity type
            $message = "Action of \"{$record->action}\" is not supported.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $entitytype);
            return false;
        }

        return true;
    }

    /**
     * Mainline for running the import
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (N/A for import)
     * @param int $maxruntime      The max time in seconds to complete import
     *                             default: 0 => unlimited time
     * @param object $state        Previous ran state data to continue from
     *
     * @return object              State data to pass back on re-entry,
     *                             null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = parent::run($targetstarttime, $lastruntime, $maxruntime, $state);

        if (!defined('PHPUnit_MAIN_METHOD')) {
            //not in a unit test, so send out log files in a zip
            $logids = $this->dblogger->get_log_ids();
            rlip_send_log_emails('rlipimport_version1', $logids, $this->manual);
        }

        return $result;
    }
}
