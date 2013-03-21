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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');

/**
 * Legacy PM / ELIS import
 */
class rlip_importplugin_version1elis extends rlip_importplugin_base {

    //required field definition
    static $import_fields_user_create = array('idnumber',
                                              'username',
                                              'firstname',
                                              'lastname',
                                              'email',
                                              'country');

    static $import_fields_user_update = array(array('username', 'email', 'idnumber'));

    static $import_fields_user_add = array('idnumber',
                                           'username',
                                           'firstname',
                                           'lastname',
                                           'email',
                                           'country');

    static $import_fields_user_delete = array(array('username', 'email', 'idnumber'));
    static $import_fields_user_disable = array(array('username', 'email', 'idnumber'));

    //fields that are available during the "course" (i.e. pm entity) import
    static $available_fields_user = array('username', 'password', 'idnumber', 'firstname',
                                          'lastname', 'mi', 'email', 'email2', 'address','address2',
                                          'city', 'state', 'postalcode', 'country', 'phone', 'phone2',
                                          'fax', 'birthdate', 'gender', 'language', 'transfercredits',
                                          'comments', 'notes', 'inactive');
    static $user_field_keywords = array('action', 'context', 'username', 'password', 'idnumber', 'firstname',
                                        'lastname', 'mi', 'email', 'email2', 'address','address2',
                                        'city', 'state', 'postalcode', 'country', 'phone', 'phone2',
                                        'fax', 'birthdate', 'gender', 'language', 'transfercredits',
                                        'comments', 'notes', 'inactive');

    //fields that are available during the "course" (i.e. pm entity) import
    static $available_fields_course = array('context', 'name', 'code', 'idnumber', 'syllabus', 'lengthdescription', 'length',
                                            'credits', 'cost', 'version', 'description', 'reqcredits', 'timetocomplete',
                                            'frequency', 'priority', 'startdate', 'enddate', 'autocreate', 'assignment',
                                            'starttimehour', 'starttimeminute', 'endtimehour', 'endtimeminute', 'maxstudents',
                                            'enrol_from_waitlist', 'track', 'autoenrol', 'link', 'display', 'parent', 'recursive');
    static $course_field_keywords       = array('action', 'context', 'name', 'code', 'idnumber', 'syllabus', 'lengthdescription',
                                                'length', 'credits', 'completion_grade', 'cost', 'version', 'assignment', 'link');

    static $import_fields_course_create = array(
        'context',
        'idnumber',
        'name'
    );

    static $import_fields_course_update = array(
        'context',
        'idnumber'
    );

    static $import_fields_course_delete = array(
        'context',
        'idnumber'
    );

    static $import_fields_cluster_create = array(
        'context',
        'name'
    );

    static $import_fields_cluster_update = array(
        'context',
        'name'
    );

    static $import_fields_cluster_delete = array(
        'context',
        'name'
    );

    static $import_fields_curriculum_create = array(
        'context',
        'idnumber',
        'name'
    );

    static $import_fields_curriculum_update = array(
        'context',
        'idnumber'
    );

    static $import_fields_curriculum_delete = array(
        'context',
        'idnumber'
    );

    static $program_field_keywords = array('action', 'context', 'idnumber', 'name', 'description', 'reqcredits', 'timetocomplete', 'frequency', 'priority');

    static $import_fields_class_create = array(
        'assignment',
        'context',
        'idnumber'
    );

    static $import_fields_class_update = array(
        'context',
        'idnumber'
    );

    static $import_fields_class_delete = array(
        'context',
        'idnumber'
    );

    static $available_fields_class = array('idnumber', 'startdate', 'enddate', 'starttimehour',
                                          'starttimeminute', 'endtimehour', 'endtimeminute', 'maxstudents',
                                          'enrol_from_waitlist', 'assignment', 'track', 'autoenrol', 'link');
    static $class_field_keywords = array('action', 'context', 'idnumber', 'name', 'startdate', 'enddate', 'starttimehour',
                                          'starttimeminute', 'endtimehour', 'endtimeminute', 'maxstudents',
                                          'enrol_from_waitlist', 'assignment', 'track', 'autoenrol', 'link');

    static $import_fields_track_create = array(
        'assignment',
        'context',
        'idnumber',
        'name'
    );

    static $import_fields_track_update = array(
        'context',
        'idnumber'
    );

    static $import_fields_track_delete = array(
        'context',
        'idnumber'
    );

    static $available_fields_track = array('idnumber', 'name', 'description', 'startdate',
                                           'enddate', 'assignment', 'autocreate');
    static $track_field_keywords = array('action', 'context', 'idnumber', 'name', 'description', 'startdate',
                                         'enddate', 'assignment', 'autocreate');

    static $cluster_field_keywords = array('action', 'context', 'name', 'display', 'parent');

    static $import_fields_enrolment_create = array(
        'context',
        array('user_idnumber', 'user_username', 'user_email')
    );

    // legacy action 'enrol'
    static $import_fields_enrolment_enrol = array(
        'context',
        array('user_idnumber', 'user_username', 'user_email')
    );

    // legacy action 'enroll'
    static $import_fields_enrolment_enroll = array(
        'context',
        array('user_idnumber', 'user_username', 'user_email')
    );

    static $import_fields_enrolment_update = array(
        'context',
        array('user_idnumber', 'user_username', 'user_email')
    );

    static $import_fields_enrolment_delete = array(
        'context',
        array('user_idnumber', 'user_username', 'user_email')
    );

    //fields that are available during the enrolment import
    static $available_fields_enrolment = array('context', 'user_idnumber', 'user_username', 'user_email',
                                               'enrolmenttime', 'assigntime', 'completetime', 'completestatusid',
                                               'grade', 'credits', 'locked', 'role');

    static $yes_no_field_options = array(
        'yes',
        'no',
        '1',
        '0',
    );

    //store mappings for the current entity type
    var $mappings = array();

    //storage for custom fields
    var $fields = array();

    /**
     * Specifies the UI labels for the various import files supported by this
     * plugin
     *
     * @return array The string labels, in the order in which the
     *               associated [entity]_action methods are defined
     */
    function get_file_labels() {
        return array(get_string('userfile', 'rlipimport_version1elis'),
                     get_string('coursefile', 'rlipimport_version1elis'),
                     get_string('enrolmentfile', 'rlipimport_version1elis'));
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
     * Determines whether the current plugin supports the supplied combination
     * of entity type and action
     *
     * @param string $entity The type of entity
     * @param string $action The action being performed
     *
     * @return mixed An array of required fields, or false on error
     */
    function plugin_supports_action($entity, $action) {
        //look for a method named [entity]_[action]
        $method = "{$entity}_{$action}";
        if (method_exists($this, $method)) {
            return $this->get_import_fields($entity, $action);
        }

        return false;
    }

    /**
     * Converts a date in MMM/DD/YYYY format or one of the legacy IP formats
     * to a unix timestamp
     * @todo: consider further generalizing / moving to base class
     *
     * @param string $date Date in MMM/DD/YYYY format, or MMM/DD/YYYY:HH:MM format if $include_time is true
     * @param boolean $old_formats Only support old (legacy) formats if set to true
     * @param boolean $include_time For including time
     * @param int     $min_year     For validating year >= minimum allowed
     * @param int     $max_year     For validating year <= maximum allowed
     * @param bool    &$year_oor    return param, if true, indicates year out-of-range
     * @return mixed The unix timestamp, or false if date is
     *               not in the right format
     */
    function parse_date($date, $old_formats = true, $include_time = false, $min_year = 0, $max_year = 0, &$year_oor = null) {
        if (!is_string($date)) {
            return false;
        }

        $valid = preg_match('|^([a-zA-Z0-9]+)([/\-.])([0-9]+)[/\-.]([0-9]+)[ :]([0-9]+):([0-9]+)|', $date, $matches);
        // TBD: if ($valid && !$include_time) { return false; }
        // we'll just ignore the time info later if it's not allowed
        if (!$valid) {
            $valid = preg_match('|^([a-zA-Z0-9]+)([/\-.])([0-9]+)[/\-.]([0-9]+)|', $date, $matches);
        }

        $matchcount = count($matches);
        if (!$valid || ($matchcount != 5 && $matchcount != 7)) {
            // invalid format
            return false;
        }

        if ($year_oor !== null) {
            $year_oor = false;
        }

        $delimiter = $matches[2];
        if ($delimiter == '/') {
            // MMM/DD/YYYY or MM/DD/YYYY format
            // make sure the month is valid
            $months = array('jan', 'feb', 'mar', 'apr',
                            'may', 'jun', 'jul', 'aug',
                            'sep', 'oct', 'nov', 'dec');
            $pos = array_search(strtolower($matches[1]), $months);
            if ($pos === false) {
                // legacy format (zero values handled below by checkdate)
                $month = $matches[1];
            } else {
                // new "text" format
                $month = $pos + 1;
            }
            $day = $matches[3];
            $year = $matches[4];
        } else if ($delimiter == '-') {
            // DD-MM-YYYY format
            if (!$old_formats) {
                // not supporting this
                return false;
            }

            $day = $matches[1];
            $month = $matches[3];
            $year = $matches[4];
        } else {
            // YYYY.MM.DD format
            if (!$old_formats) {
                // not supporting this
                return false;
            }

            $year = $matches[1];
            $month = $matches[3];
            $day = $matches[4];
        }

        //make sure the combination of date components is valid
        if (!preg_match('/^\d{1,2}$/', $day)) {
            // invalid day
            return false;
        }

        if (!preg_match('/^\d\d\d\d$/', $year)) {
            // invalid year
            return false;
        }

        $day = intval($day);
        $month = intval($month);
        $year = intval($year);

        if (!checkdate($month, $day, $year)) {
            // invalid combination of month, day and year
            return false;
        }

        if (($min_year && $year < $min_year) || ($max_year && $year > $max_year)) {
            // year not in allowed range
            if ($year_oor !== null) {
                $year_oor = true;
            }
            return false;
        }

        $hour = 0;
        $minute = 0;

        // time specified
        if ($include_time && $matchcount == 7) {
            $hour = intval($matches[5]);
            $minute = intval($matches[6]);
            if (!($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59)) {
                return false;
            }
        }

        // return unix timestamp
        return mktime($hour, $minute, 0, $month, $day, $year);
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
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
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
        global $DB;

        //apply the field mapping
        $record = $this->apply_mapping($entity, $record);

        $this->fields = array();
        $errors = false;
        $shortnames = array();

        // Custom fields are available only for user and course entities
        if ($entity == "user" || $entity == "course") {
            $tmpcustomfields = array();
            $entitykeywords = array();

            foreach ($record as $field => $value) {
                if ($entity == "user") {
                    $entitykeywords = static::$user_field_keywords;
                } else {
                    if (isset($record->context)) {
                        if ($record->context == "curriculum") {
                            $entitykeywords = static::$program_field_keywords;
                        } else if ($record->context == "track") {
                            $entitykeywords = static::$track_field_keywords;
                        } else if ($record->context == "course") {
                            $entitykeywords = static::$course_field_keywords;
                        } else if ($record->context == "cluster") {
                            $entitykeywords = static::$cluster_field_keywords;
                        } else if ($record->context == "class") {
                            $entitykeywords = static::$class_field_keywords;
                        } else {
                            // invalid contexxt
                        }
                    }
                }

                if (!in_array($field, $entitykeywords)) {
                    $tmpcustomfields[] = $field;
                }
            }

            foreach ($tmpcustomfields as $field) {
                // Check if valid field
                if ($result = $DB->get_record('elis_field', array('shortname' => $field))) {
                    $this->fields[] = $result;
                } else {
                    // field is not valid
                    $shortnames[] = $field;
                    $errors = true;
                }
            }
        }

        // TODO: error logging
        /*if ($errors) {
            $this->fslogger->log_failure("Import file contains the following invalid user profile field(s): " . implode(', ', $shortnames), 0, $filename, $this->linenumber);
            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
        }*/

        return parent::process_record($entity, $record, $filename);
    }

    /**
     * Validates that custom fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     * @param string $filename The import file name, used for logging
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_custom_field_data($action, $record, $filename, $type) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        //needed for "trim_cr" method
        require_once($CFG->dirroot.'/elis/core/fields/manual/custom_fields.php');

        foreach ($this->fields as $field) {
            //obtain the control type
            $f = new field($field);
            if (!isset($f->owners['manual'])) {
                //NOTE: should only really happen during unit tests
                continue;
            }
            $control = $f->owners['manual']->param_control;

            //obtain the submitted value
            $v = $record->{'field_'.$field->shortname};

            //handle as an array
            if (is_array($v)) {
                $values = $v;
            } else {
                $values = array($v);
            }

            // apply custom field mapping to shortname
            $identifier = $this->get_field_mapping($field->shortname);

            foreach ($values as $value) {
                switch ($control) {
                    case 'checkbox':
                        // just in case
                        $string_value = (string)$value;

                        if ($string_value != '0' && $string_value != '1' && strtolower($string_value) != 'yes' && strtolower($string_value) != 'no') {
                            // not a valid checkbox value
                            $message = '"'.$value.'" is not one of the available options for checkbox custom field "'.$identifier.'" (0, 1).';
                            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                            return false;
                        }

                        if (strtolower($string_value) == 'yes') {
                            // NOTE: this control type only supports a single value but the
                            // API expects an array if it's multivalued
                            $record->{'field_'.$field->shortname} = $field->multivalued ? array('1') : '1';

                        }
                        if (strtolower($string_value) == 'no') {
                            // NOTE: this control type only supports a single value but the
                            // API expects an array if it's multivalued
                            $record->{'field_'.$field->shortname} = $field->multivalued ? array('0') : '0';
                        }
                        break;
                    case 'menu':
                        $options = explode("\n", $f->owners['manual']->param_options);
                        // remove carriage return characters
                        array_walk($options, 'trim_cr');

                        if (!in_array($value, $options)) {
                            // not a valid option
                            $message = '"'.$value.'" is not one of the available options for menu of choices custom field "'.$identifier.'".';
                            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                            return false;
                        }
                        break;
                    case 'text':
                        $maxlength = $f->owners['manual']->param_maxlength;
                        if (strlen($value) > $maxlength) {
                            // too long
                            $message = 'Text input custom field "'.$identifier.'" value of "'.$value.'" exceeds the maximum field length of '.$maxlength.'.';
                            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                            return false;
                        }
                        break;
                    case 'password':
                        $maxlength = $f->owners['manual']->param_maxlength;
                        if (strlen($value) > $maxlength) {
                            // too long
                            $message = 'Password custom field "'.$identifier.'" value of "'.$value.'" exceeds the maximum field length of '.$maxlength.'.';
                            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                            return false;
                        }
                        break;
                    case 'datetime':
                        // determine whether the field supports the "time" component and the max & min years allowed
                        $inctime = $f->owners['manual']->param_inctime;
                        $startyear = $f->owners['manual']->param_startyear;
                        $stopyear = $f->owners['manual']->param_stopyear;
                        $year_oor = false;
                        if ($inctime) {
                            // date and time supported
                            $date = $this->parse_date($value, false, true, $startyear, $stopyear, $year_oor);
                            if (!$date) {
                                if (!$year_oor) {
                                    // could not parse date + time
                                    $message = '"'.$value.'" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "'.$identifier.'".';
                                } else {
                                    $message = '"'.$value.'" has out-of-range year for date / time custom field "'.$identifier.'".';
                                }
                                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                                return false;
                            } else {
                                // NOTE: this control type only supports a single value but the
                                // API expects an array if it's multivalued
                                $record->{'field_'.$field->shortname} = $field->multivalued ? array($date) : $date;
                            }
                        } else {
                            // date only supported without time
                            $date = $this->parse_date($value, false, false, $startyear, $stopyear, $year_oor);
                            if (!$date) {
                                if (!$year_oor) {
                                    // could not parse date
                                    $message = '"'.$value.'" is not a valid date in MMM/DD/YYYY format for date custom field "'.$identifier.'".';
                                } else {
                                    $message = '"'.$value.'" has out-of-range year for date custom field "'.$identifier.'".';
                                }
                                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $type);
                                return false;
                            } else {
                                // NOTE: this control type only supports a single value but the
                                // API expects an array if it's multivalued
                                $record->{'field_'.$field->shortname} = $field->multivalued ? array($date) : $date;
                            }
                        }
                    default:
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Initialize user fields that are needed but are not required in the data format
     *
     * @param object $record One import record
     * @return object The record, with necessary fields added
     */
    function initialize_user_fields($record) {
        if (!isset($record->birthday)) {
            $record->birthday = '';
        }
        if (!isset($record->birthmonth)) {
            $record->birthmonth = '';
        }
        if (!isset($record->birthyear)) {
            $record->birthyear = '';
        }

        return $record;
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
     * Create a user
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_create($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        // Custom fields validation
        /*if (!$this->validate_user_profile_data($record, $filename)) {
            return false;
        }*/

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->username)) {
            if ($DB->record_exists('crlm_user', array('username' => $record->username))) {
                $identifier = $this->get_field_mapping('username');
                $this->fslogger->log_failure("$identifier value of \"{$record->username}\" refers to a user that already exists.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        $allowduplicateemails = get_config('rlipimport_version1elis','allowduplicateemails');
        if (empty($allowduplicateemails)) {
            if (isset($record->email)) {
                if ($DB->record_exists('crlm_user', array('email' => $record->email))) {
                    $identifier = $this->get_field_mapping('email');
                    $this->fslogger->log_failure("$identifier value of \"{$record->email}\" refers to a user that already exists.", 0, $filename, $this->linenumber, $record, "user");
                    return false;
                }
            }
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('crlm_user', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" refers to a user that already exists.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (!$this->validate_core_user_data('create', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'user')) {
            return false;
        }

        $record = $this->initialize_user_fields($record);

        if (isset($record->password)) {
            //trigger password hashing
            $record->newpassword = $record->password;
            unset($record->password);
        }

        if (isset($record->birthdate)) {
            //convert the birthdate field to the proper setup for storage
            $value = $this->parse_date($record->birthdate);

            $record->birthday = date('d', $value);
            $record->birthmonth = date('m', $value);
            $record->birthyear = date('Y', $value);
            unset($record->birthdate);
        }

        // TODO: validation
        $user = new user();
        $user->set_from_data($record);
        $user->save();

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);

        //log success
        $success_message = "User with {$user_descriptor} successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
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

        if (isset($record->email)) {
            if (!validate_email($record->email)) {
                $identifier = $this->get_field_mapping('email');
                $this->fslogger->log_failure("$identifier value of \"{$record->email}\" is not a valid email address.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->email2)) {
            if (!validate_email($record->email2)) {
                $identifier = $this->get_field_mapping('email2');
                $this->fslogger->log_failure("$identifier value of \"{$record->email2}\" is not a valid email address.", 0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->country)) {
            //make sure country refers to a valid country code
            $countries = get_string_manager()->get_list_of_countries();
            if (!$this->validate_fixed_list($record, 'country', array_keys($countries), array_flip($countries))) {
                $identifier = $this->get_field_mapping('country');
                $this->fslogger->log_failure("$identifier value of \"{$record->country}\" is not a valid country or country code.",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->birthdate)) {
            $value = $this->parse_date($record->birthdate);
            if ($value === false) {
                $identifier = $this->get_field_mapping('birthdate');
                $this->fslogger->log_failure("$identifier value of \"{$record->birthdate}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->gender)) {
            if (!in_array(strtolower($record->gender), array('m', 'f', 'male', 'female'))) {
                $identifier = $this->get_field_mapping('gender');
                $this->fslogger->log_failure("$identifier value of \"{$record->gender}\" is not one of the available options (M, male, F, female).",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->lang)) {
            $languages = get_string_manager()->get_list_of_translations();
            if (!$this->validate_fixed_list($record, 'lang', array_keys($languages))) {
                $identifier = $this->get_field_mapping('lang');
                $this->fslogger->log_failure("$identifier value of \"{$record->lang}\" is not a valid language code.",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->transfercredits)) {
            if ($record->transfercredits < 0) {
                $identifier = $this->get_field_mapping('transfercredits');
                $this->fslogger->log_failure("$identifier value of \"{$record->transfercredits}\" is not a non-negative integer.",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            }
        }

        if (isset($record->inactive)) {
            if (!$this->validate_fixed_list($record, 'inactive', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('inactive');
                $this->fslogger->log_failure("$identifier value of \"{$record->inactive}\" is not one of the available options (0, 1).",
                                             0, $filename, $this->linenumber, $record, "user");
                return false;
            } else {
                $record->inactive = ((string) $record->inactive == 'yes' || (string) $record->inactive == '1') ? '1' : '0';
            }
        }

        return true;
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
     * Update a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_update($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        $errors = array();
        $error = false;

        if (isset($record->username)) {
            if (!$DB->record_exists('crlm_user', array('username' => $record->username))) {
                $identifier = $this->get_field_mapping('username');
                $errors[] = "$identifier value of \"{$record->username}\"";
                $error = true;
            }
        }

        if (isset($record->email)) {
            if (!$DB->record_exists('crlm_user', array('email' => $record->email))) {
                $identifier = $this->get_field_mapping('email');
                $errors[] = "$identifier value of \"{$record->email}\"";
                $error = true;
            }
        }

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_user', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $errors[] = "$identifier value of \"{$record->idnumber}\"";
                $error = true;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
            }
            return false;
        }

        if (!$this->validate_core_user_data('update', $record, $filename)) {
            return false;
        }

        // TODO: validation
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

        //$record->timemodified = time();
        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'user')) {
            return false;
        }

        $record = $this->initialize_user_fields($record);

        if (isset($record->password)) {
            //trigger password hashing
            $record->newpassword = $record->password;
            unset($record->password);
        }

        if (isset($record->birthdate)) {
            //convert the birthdate field to the proper setup for storage
            $value = $this->parse_date($record->birthdate);

            $record->birthday = date('d', $value);
            $record->birthmonth = date('m', $value);
            $record->birthyear = date('Y', $value);
            unset($record->birthdate);
        }

        $user = new user($DB->get_field('crlm_user', 'id', $params));
        $user->load();
        $user->set_from_data($record);
        $user->save();

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);

        //log success
        $success_message = "User with {$user_descriptor} successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Disable a user
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
     * Delete a user
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_delete($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        $params = array();
        $errors = array();
        $error = false;

        if (isset($record->username)) {
            if (!$DB->record_exists('crlm_user', array('username' => $record->username))) {
                $identifier = $this->get_field_mapping('username');
                $errors[] = "$identifier value of \"{$record->username}\"";
                $error = true;
            } else {
                $params['username'] = $record->username;
            }
        }

        if (isset($record->email)) {
            if (!$DB->record_exists('crlm_user', array('email' => $record->email))) {
                $identifier = $this->get_field_mapping('email');
                $errors[] = "$identifier value of \"{$record->email}\"";
                $error = true;
            } else {
                $params['email'] = $record->email;
            }
        }

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_user', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $errors[] = "$identifier value of \"{$record->idnumber}\"";
                $error = true;
            } else {
                $params['idnumber'] = $record->idnumber;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "user");
            }
            return false;
        }

        if ($user = $DB->get_record('crlm_user', $params)) {
            $user = new user($user);
            $user->delete();
        }

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record);

        //log success
        $success_message = "User with {$user_descriptor} successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }


    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for users
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_user_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

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
                }
                if ($email_set) {
                    $params['email'] = $record->email;
                }
                if ($idnumber_set) {
                    $params['idnumber'] = $record->idnumber;
                }

                if ($DB->record_exists(user::TABLE, $params)) {
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
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //store field mappings for this entity type
        $this->mappings = rlipimport_version1elis_get_mapping($entity);

        return parent::process_import_file($entity, $maxruntime, $state);
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
        $lengths = array(
            'username' => 100,
            'password' => 25,
            'idnumber' => 255,
            'firstname' => 100,
            'lastname' => 100,
            'mi' => 100,
            'email' => 100,
            'email2' => 100,
            'address' => 100,
            'address2' => 100,
            'city' => 100,
            'postalcode' => 32,
            'phone' => 100,
            'phone2' => 100,
            'fax' => 100
        );

        return $this->check_field_lengths('user', $record, $filename, $lengths);
    }

    /**
     * Check the lengths of fields from a class record
     * @todo: consider generalizing
     *
     * @param object $record The class record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_class_field_lengths($record, $filename) {
        $lengths = array('idnumber' => 100);

        return $this->check_field_lengths('class', $record, $filename, $lengths);
    }

    /**
     * Check the lengths of fields from a course description record
     * @todo: consider generalizing
     *
     * @param object $record The course description record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_course_field_lengths($record, $filename) {
        $lengths = array(
            'idnumber' => 100,
            'name' => 255,
            'code' => 100,
            'lengthdescription' => 100,
            'credits' => 10,
            'cost' => 10,
            'version' => 100
        );

        return $this->check_field_lengths('course', $record, $filename, $lengths);
    }

    /**
     * Check the lengths of fields from a program record
     * @todo: consider generalizing
     *
     * @param object $record The program record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_program_field_lengths($record, $filename) {
        $lengths = array(
            'idnumber' => 100,
            'name' => 64,
            'timetocomplete' => 64,
            'frequency' => 64
        );

        return $this->check_field_lengths('curriculum', $record, $filename, $lengths);
    }

    /**
     * Check the lengths of fields from a track record
     * @todo: consider generalizing
     *
     * @param object $record The track record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_track_field_lengths($record, $filename) {
        $lengths = array(
            'idnumber' => 100,
            'name' => 255
        );

        return $this->check_field_lengths('track', $record, $filename, $lengths);
    }

    /**
     * Check the lengths of fields from a userset record
     * @todo: consider generalizing
     *
     * @param object $record The userset record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_userset_field_lengths($record, $filename) {
        $lengths = array(
            'name' => 255,
            'display' => 255
        );

        return $this->check_field_lengths('cluster', $record, $filename, $lengths);
    }

    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for class instances
     * TODO: consider refactoring all similar methods?
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_class_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->idnumber) && $record->idnumber !== '') {
                //identify the course
                if ($DB->record_exists(pmclass::TABLE, array('idnumber' => $record->idnumber))) {
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
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for course descriptions
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_course_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->idnumber) && $record->idnumber !== '') {
                //identify the course
                if ($DB->record_exists(course::TABLE, array('idnumber' => $record->idnumber))) {
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
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for programs
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_curriculum_createorupdate($record, $action) {
        return $this->handle_program_createorupdate($record, $action);
    }

    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for programs
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_program_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->idnumber) && $record->idnumber !== '') {
                //identify the course
                if ($DB->record_exists(curriculum::TABLE, array('idnumber' => $record->idnumber))) {
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
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for tracks
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_track_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/track.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->idnumber) && $record->idnumber !== '') {
                //identify the course
                if ($DB->record_exists(track::TABLE, array('idnumber' => $record->idnumber))) {
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
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for user sets
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_userset_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->name) && $record->name !== '') {
                //identify the course
                if ($DB->record_exists(userset::TABLE, array('name' => $record->name))) {
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
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for user sets
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     */
    function handle_cluster_createorupdate($record, $action) {
        return $this->handle_userset_createorupdate($record, $action);
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

        $valid_contexts = array('course', 'curriculum', 'cluster', 'track', 'class');
        $valid_actions = array('create', 'update', 'delete');

        $context = '';
        if (isset($record->context)) {
            $context = $record->context;
        }

        //to support ELIS IP1.9, we map 'curr' to 'curriculum' if it is used
        if ($context == 'curr') {
            $context = 'curriculum';
        }

        if (!in_array($context, $valid_contexts)) {
            if (in_array($action, $valid_actions)) {
                $message = "Entity could not be {$record->action}d.";
            } else {
                $message = "Entity could not be processed.";
            }
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, '');
            return false;
        }

        if (!$this->check_action_field('course', $record, $filename)) {
            //missing an action value
            return false;
        }

        //apply "createorupdate" flag, if necessary
        if ($action == 'create') {
            $method = "handle_{$context}_createorupdate";
            if (method_exists($this, $method)) {
                $action = $this->$method($record, $action);
            } // else TBD: bad context ???
        }
        $record->action = $action;

        if (!$this->check_required_fields($context, $record, $filename)) {
            //missing a required field
            return false;
        }

        //remove empty fields
        $record = $this->remove_empty_fields($record);
        //perform action
        $method = "{$context}_{$action}";
        return $this->$method($record, $filename);
    }

    /**
     * Associate a class instance to a track, if necessary
     *
     * @param object $record The import record containing information about the track
     * @param int $classid The id of the class instance
     */
    function associate_class_to_track($record, $classid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/track.class.php'));

        if (isset($record->track)) {
            //attempt to associate this class instance to a track
            if ($trackid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $record->track))) {
                //valid track, so associate

                //determine whether we should auto-enrol
                $autoenrol = (isset($record->autoenrol) && $record->autoenrol == 1) ? 1 : 0;
                $trackassignment = new trackassignment(array('trackid' => $trackid,
                                                             'classid' => $classid,
                                                             'autoenrol' => $autoenrol));
                $trackassignment->save();
            }
        }

        //TODO: return a status, add error handling
    }

    /**
     * Associate a class instance to a Moodle course, if necessary, either by
     * auto-creating that Moodle course or by using a new one
     *
     * @param object $record The import record containing information about the track
     * @param int $classid The id of the class instance
     */
    function associate_class_to_moodle_course($record, $classid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));

        if (isset($record->link)) {
            if ($record->link == 'auto') {
                moodle_attach_class($classid, 0, '', true, true, true);
            } else {
                $moodlecourseid = $DB->get_field('course', 'id', array('shortname' => $record->link));
                moodle_attach_class($classid, $moodlecourseid, '', true, true, false);
            }
        }

        //TODO: return a status, add error handling
    }

    /**
     * Validates that class fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_class_data($action, $record, $filename) {
        global $CFG, $DB;

        if (isset($record->startdate)) {
            $value = $this->parse_date($record->startdate);
            if ($value === false) {
                $identifier = $this->get_field_mapping('startdate');
                $this->fslogger->log_failure("$identifier value of \"{$record->startdate}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            } else {
                $record->startdate = $value;
            }
        }

        if (isset($record->enddate)) {
            $value = $this->parse_date($record->enddate);
            if ($value === false) {
                $identifier = $this->get_field_mapping('enddate');
                $this->fslogger->log_failure("$identifier value of \"{$record->enddate}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            } else {
                $record->enddate = $value;
            }
        }

        if (isset($record->starttimeminute)) {
            if (((int)$record->starttimeminute % 5) !== 0) {
                $identifier = $this->get_field_mapping('starttimeminute');
                $this->fslogger->log_failure("$identifier value of \"{$record->starttimeminute}\" is not on a five-minute boundary.",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (isset($record->endtimeminute)) {
            if (((int)$record->endtimeminute % 5) !== 0) {
                $identifier = $this->get_field_mapping('endtimeminute');
                $this->fslogger->log_failure("$identifier value of \"{$record->endtimeminute}\" is not on a five-minute boundary.",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (isset($record->maxstudents)) {
            if ($record->maxstudents < 0) {
                $identifier = $this->get_field_mapping('maxstudents');
                $this->fslogger->log_failure("$identifier value of \"{$record->maxstudents}\" is not a non-negative integer.",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (isset($record->enrol_from_waitlist)) {
            if (!$this->validate_fixed_list($record, 'enrol_from_waitlist', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('enrol_from_waitlist');
                $this->fslogger->log_failure("$identifier value of \"{$record->enrol_from_waitlist}\" is not one of the available options (0, 1).",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            } else {
                $record->enrol_from_waitlist = ((string) $record->enrol_from_waitlist == 'yes' || (string) $record->enrol_from_waitlist == '1') ? '1' : '0';
            }
        }

        if (isset($record->track)) {
            if (!$DB->record_exists('crlm_track', array('idnumber' => $record->track))) {
                $identifier = $this->get_field_mapping('assignment');
                $this->fslogger->log_failure("$identifier value of \"{$record->track}\" does not refer to a valid track.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (isset($record->autoenrol)) {
            if (!$this->validate_fixed_list($record, 'autoenrol', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('autoenrol');
                $this->fslogger->log_failure("$identifier value of \"{$record->autoenrol}\" is not one of the available options (0, 1).",
                                             0, $filename, $this->linenumber, $record, "class");
                return false;
            } else {
                $record->autoenrol = ((string) $record->autoenrol == 'yes' || (string) $record->autoenrol == '1') ? '1' : '0';
            }
        }

        if (isset($record->link) && $record->link != 'auto') {
            if (!$DB->record_exists('course', array('shortname' => $record->link))) {
                $identifier = $this->get_field_mapping('link');
                $this->fslogger->log_failure("$identifier value of \"{$record->link}\" does not refer to a valid Moodle course.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        return true;
    }

    function class_create($record, $filename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        //field length checking
        $lengthcheck = $this->check_class_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('crlm_class', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" refers to a class instance that already exists.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (isset($record->assignment)) {
            if (!$crsid = $DB->get_field('crlm_course', 'id', array('idnumber' => $record->assignment))) {
                $identifier = $this->get_field_mapping('assignment');
                $this->fslogger->log_failure("$identifier value of \"{$record->assignment}\" does not refer to a valid course description.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if (!$this->validate_class_data('create', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'class')) {
            return false;
        }

        $record->courseid = $crsid;

        $pmclass = new pmclass();
        $pmclass->set_from_data($record);
        $pmclass->save();

        //associate this class instance to a track, if necessary
        $this->associate_class_to_track($record, $pmclass->id);
        //associate this class instance to a Moodle course, if necessary
        $this->associate_class_to_moodle_course($record, $pmclass->id);

        //log success
        $success_message = "Class instance with idnumber \"{$record->idnumber}\" successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Remove invalid fields from a class record
     * @todo: consider generalizing this
     *
     * @param object $record The class record
     * @return object The class record with the invalid fields removed
     */
    function remove_invalid_class_fields($record) {
        $allowed_fields = $this->get_available_fields('class');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($record->$key);
            }
        }

        return $record;
    }

        /**
     * Remove invalid fields from a class record
     * @todo: consider generalizing this
     *
     * @param object $record The class record
     * @return object The class record with the invalid fields removed
     */
    function remove_invalid_track_fields($record) {
        $allowed_fields = $this->get_available_fields('track');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
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
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once(elis::lib('data/customfield.class.php'));

        if ($this->plugin_supports($entitytype) !== false) {
            $attribute = 'available_fields_'.$entitytype;

            $result = array_merge(array('action'), static::$$attribute);

            // enrolments do not have custom fields
            if ($entitytype == 'enrolment') {
                return $result;
            }

            if (!is_numeric($entitytype)) {
                $entitytype = context_elis_helper::get_level_from_name($entitytype);
            }

            // courses get all custom fields except user custom fields
            if ($entitytype == CONTEXT_ELIS_COURSE) {
                $contextsql = "ctx.contextlevel != ".CONTEXT_ELIS_USER;
            } else if ($entitytype == CONTEXT_ELIS_USER) {
                $contextsql = "ctx.contextlevel = $entitytype";
            } else {
                // unsupported entity type
                return $result;
            }

            //add ELIS custom fields - get by context
            $sql = "SELECT field.shortname
                      FROM {".field::TABLE."} field
                      JOIN {".field_contextlevel::TABLE."} ctx ON ctx.fieldid = field.id AND ".$contextsql;
            if ($fields = $DB->get_recordset_sql($sql)) {
                foreach ($fields as $field) {
                    $result[] = $field->shortname;
                }
            }

            // make sure there are no duplicate field shortnames returned
            return array_unique($result);
        } else {
            return false;
        }
    }

    function class_update($record, $filename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        //NOTE: not checking field lengths because only idnumber can be too long, and
        //we can't set this during updates

        $message = "";

        if (isset($record->idnumber)) {
            if (!$clsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid class instance.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }

            if (isset($record->assignment)) {
                $message = "Class instance with idnumber \"{$record->idnumber}\" was not re-assigned to course description with name \"{$record->assignment}\" because moving class instances between course descriptions is not supported.";
            }
        }

        if (!$this->validate_class_data('update', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'class')) {
            return false;
        }

        $record->id = $clsid;

        $pmclass = new pmclass();
        $pmclass->set_from_data($record);
        $pmclass->save();

        //associate this class instance to a track, if necessary
        $this->associate_class_to_track($record, $pmclass->id);
        //associate this class instance to a Moodle course, if necessary
        $this->associate_class_to_moodle_course($record, $pmclass->id);

        //log success
        $success_message = "Class instance with idnumber \"{$record->idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    function class_delete($record, $filename) {
        global $DB, $CFG;

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_class', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid class instance.", 0, $filename, $this->linenumber, $record, "class");
                return false;
            }
        }

        if ($course = $DB->get_record('crlm_class', array('idnumber' => $record->idnumber))) {
            $course = new pmclass($course);
            $course->delete();
        }

        //log success
        $success_message = "Class instance with idnumber \"{$record->idnumber}\" successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Validates that program fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_program_data($action, $record, $filename) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/elis/program/lib/datedelta.class.php');

        if (isset($record->reqcredits)) {
            $digits = strlen(substr($record->reqcredits, 0, strpos($record->reqcredits, '.')));
            $decdigits = strlen(substr(strrchr($record->reqcredits, '.'), 1));

            if (!is_numeric($record->reqcredits) || $decdigits > 2 || $digits > 8) {
                $identifier = $this->get_field_mapping('reqcredits');
                $this->fslogger->log_failure("$identifier value of \"{$record->reqcredits}\" is not a number with at most ten total digits and two decimal digits.",
                                          0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if (isset($record->timetocomplete)) {
            $datedelta = new datedelta($record->timetocomplete);
            if (!$datedelta->getDateString()) {
                $identifier = $this->get_field_mapping('timetocomplete');
                $this->fslogger->log_failure("$identifier value of \"{$record->timetocomplete}\" is not a valid time delta in *h, *d, *w, *m, *y format.",
                                          0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if (isset($record->frequency)) {
            $enabled = (bool) get_config('elis_program', 'enable_curriculum_expiration');
            if ($enabled) {
                $datedelta = new datedelta($record->frequency);
                if (!$datedelta->getDateString()) {
                    $identifier = $this->get_field_mapping('frequency');
                    $this->fslogger->log_failure("$identifier value of \"{$record->frequency}\" is not a valid time delta in *h, *d, *w, *m, *y format.",
                                                 0, $filename, $this->linenumber, $record, "curriculum");
                    return false;
                }
            } else {
                $identifier = $this->get_field_mapping('frequency');
                $this->fslogger->log_failure("Program $identifier / expiration cannot be set because program expiration is globally disabled.", 0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if (isset($record->priority)) {
            if ($record->priority < 0 || $record->priority > 10) {
                $identifier = $this->get_field_mapping('priority');
                $this->fslogger->log_failure("$identifier value of \"{$record->priority}\" is not one of the available options (0 .. 10).", 0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        return true;
    }

    /*
     * Intelligently splits a custom field specification into several values
     *
     * @param string $custom_field_string  The data specification string, using
     *                                     \\\\ to represent \, \\/ to represent /,
     *                                     and / as a category separator
     * @return array An array with one entry per data value, containing the
     *               unescaped values
     */
    function get_custom_field_values($custom_field_string) {
        //TODO: refactor this and make it general so that we can use the same method
        //to handle course categories in the Version 1 "Moodle" plugin

        //in-progress method result
        $result = array();

        //used to build up the current token before splitting
        $current_token = '';

        //tracks which token we are currently looking at
        $current_token_num = 0;

        for ($i = 0; $i < strlen($custom_field_string); $i++) {
            //initialize the entry if necessary
            if (!isset($result[$current_token_num])) {
                $result[$current_token_num] = '';
            }

            //get the ith character from the category string
            $current_token .= substr($custom_field_string, $i, 1);

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
     * Perform the data transformation needed for custom fields to work
     * automatically
     *
     * @param object $record One import record
     * @return object The transformed version of that record, with field keys changed to
     * field_[shortname], and multi-valued fields having data set as an array
     */
    function add_custom_field_prefixes($record) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        //loop through all profile fields
        foreach ($this->fields as $field) {
            //old and new keys
            $old_key = $field->shortname;
            $new_key = "field_{$old_key}";

            if (isset($record->$old_key)) {
                //need the actual field object
                $temp = new field($field->id);

                if ($field->multivalued && $temp->owners['manual']->param_control == field::MENU) {
                    //multivalued menu
                    $values = $this->get_custom_field_values($record->$old_key);
                    $record->$new_key = $this->get_custom_field_values($record->$old_key);;
                } else if ($field->multivalued) {
                    //any other multivalued setup
                    $record->$new_key = array($record->$old_key);
                } else {
                    //single value
                    $record->$new_key = $record->$old_key;
                }

                //unset the old key
                unset($record->$old_key);
            }
        }

        return $record;
    }

    function curriculum_create($record, $filename) {
        global $DB, $CFG;

        //field length checking
        $lengthcheck = $this->check_program_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('crlm_curriculum', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" refers to a program that already exists.", 0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if (!$this->validate_program_data('create', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'curriculum')) {
            return false;
        }

        $cur = new curriculum();
        $cur->set_from_data($record);
        $cur->save();

        //log success
        $success_message = "Program with idnumber \"{$record->idnumber}\" successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    function curriculum_update($record, $filename) {
        global $CFG, $DB;

        //field length checking
        $lengthcheck = $this->check_program_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_curriculum', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid program.", 0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if (!$this->validate_program_data('update', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'curriculum')) {
            return false;
        }

        $id = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $record->idnumber));
        $record->id = $id;

        if (!$this->validate_program_data('update', $record, $filename)) {
            return false;
        }

        $cur = new curriculum();
        $cur->set_from_data($record);
        $cur->save();

        //log success
        $success_message = "Program with idnumber \"{$record->idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    function curriculum_delete($record, $filename) {
        global $DB, $CFG;

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_curriculum', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid program.", 0, $filename, $this->linenumber, $record, "curriculum");
                return false;
            }
        }

        if ($cur = $DB->get_record('crlm_curriculum', array('idnumber' => $record->idnumber))) {
            $cur = new curriculum($cur);
            $cur->delete();
        }

        //log success
        $success_message = "Program with idnumber \"{$record->idnumber}\" successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Create a cluster (user set)
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function cluster_create($record, $filename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        //field length checking
        $lengthcheck = $this->check_userset_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->name)) {
            if ($DB->record_exists('crlm_cluster', array('name' => $record->name))) {
                $identifier = $this->get_field_mapping('name');
                $this->fslogger->log_failure("$identifier value of \"{$record->name}\" refers to a user set that already exists.", 0, $filename, $this->linenumber, $record, "cluster");
                return false;
            }
        }

        if (!isset($record->parent) || $record->parent == 'top') {
            $record->parent = 0;
        }

        if ($parentid = $DB->get_field(userset::TABLE, 'id', array('name' => $record->parent))) {
            $record->parent = $parentid;
        } else if ($record->parent !== 0) {
            $identifier = $this->get_field_mapping('parent');
            $this->fslogger->log_failure("$identifier value of \"{$record->parent}\" should refer to a valid user set, or be set to \"top\" to place this user set at the top level.",
                                        0, $filename, $this->linenumber, $record, "cluster");
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'cluster')) {
            return false;
        }

        if (!isset($record->display)) {
            //should default to the empty string rather than null
            $record->display = '';
        }

        $cluster = new userset();
        $cluster->set_from_data($record);
        $cluster->save();

        //log success
        $success_message = "User set with name \"{$record->name}\" successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Create a cluster (user set)
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function cluster_update($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        //field length checking
        $lengthcheck = $this->check_userset_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->name)) {
            $id = $DB->get_field(userset::TABLE, 'id', array('name'  => $record->name));
            if (!$id) {
                $identifier = $this->get_field_mapping('name');
                $this->fslogger->log_failure("$identifier value of \"{$record->name}\" does not refer to a valid user set.", 0, $filename, $this->linenumber, $record, "cluster");
                return false;
            }
        }

        if (isset($record->parent)) {
            if ($record->parent == 'top') {
                $record->parent = 0;
            } else if ($parentid = $DB->get_field(userset::TABLE, 'id', array('name' => $record->parent))) {
                $record->parent = $parentid;
            } else {
                $identifier = $this->get_field_mapping('parent');
                $this->fslogger->log_failure("$identifier value of \"{$record->parent}\" should refer to a valid user set, or be set to \"top\" to place this user set at the top level.",
                                             0, $filename, $this->linenumber, $record, "cluster");
                return false;

            }
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'cluster')) {
            return false;
        }

        $record->id = $id;

        $data = new userset();
        $data->set_from_data($record);
        $data->save();

        //log success
        $success_message = "User set with name \"{$record->name}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Update a cluster (user set)
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function cluster_delete($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        if (isset($record->name)) {
            $id = $DB->get_field(userset::TABLE, 'id', array('name'  => $record->name));
            if (!$id) {
                $identifier = $this->get_field_mapping('name');
                $this->fslogger->log_failure("$identifier value of \"{$record->name}\" does not refer to a valid user set.", 0, $filename, $this->linenumber, $record, "cluster");
                return false;
            }
        }

        if (isset($record->recursive)) {
            if (!$this->validate_fixed_list($record, 'recursive', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('recursive');
                $this->fslogger->log_failure("$identifier value of \"{$record->recursive}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "cluster");
                return false;
            } else {
                $record->recursive = ((string) $record->recursive == 'yes' || (string) $record->recursive == '1') ? '1' : '0';
            }
        }

        $data = new userset($id);
        //handle recursive delete, if necessary
        if (!empty($record->recursive)) {
            $data->deletesubs = true;
        }
        $data->delete();

        //log success
        $success_message = "User set with name \"{$record->name}\" successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Associate a course description to a Moodle template course, if necessary
     *
     * @param object $record The import record containing information about the moodle course
     * @param int $courseid The id of the course description
     */
    function associate_course_to_moodle_course($record, $courseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        if (isset($record->link)) {
            //attempt to associate this course description to a Moodle course
            if ($mdlcourseid = $DB->get_field('course', 'id', array('shortname' => $record->link))) {
                //valid Moodle course, so associate
                $coursetemplate = new coursetemplate(array('courseid' => $courseid,
                                                           'location' => $mdlcourseid,
                                                           'templateclass' => 'moodlecourseurl'));
                $coursetemplate->save();
            }
        }

        //TODO: return a status, add error handling
    }

    /**
     * Initialize course fields that are needed but are not required in the data format
     *
     * @param object $record One import record
     * @return object The record, with necessary fields added
     */
    function initialize_course_fields($record) {
        if (!isset($record->syllabus)) {
            $record->syllabus = '';
        }

        return $record;
    }

    /**
     * Validates that course fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_course_data($action, $record, $filename) {
        global $CFG, $DB;

        if (isset($record->credits)) {
            if ($record->credits < 0) {
                $identifier = $this->get_field_mapping('credits');
                $this->fslogger->log_failure("$identifier value of \"{$record->credits}\" is not a non-negative number.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        if (isset($record->completion_grade)) {
            if ($record->completion_grade < 0 || $record->completion_grade > 100) {
                $identifier = $this->get_field_mapping('completion_grade');
                $this->fslogger->log_failure("$identifier value of \"{$record->completion_grade}\" is not one of the available options (0 .. 100).", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        if (isset($record->link)) {
            if (!$DB->record_exists('course', array('shortname' => $record->link))) {
                $identifier = $this->get_field_mapping('link');
                $this->fslogger->log_failure("$identifier value of \"{$record->link}\" does not refer to a valid Moodle course.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        return true;
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
     * Retrieve mapped field if it exists
     *
     * @param string $field a single field name string
     * @return string the field to display
     */
    function get_field_mapping($field) {
        return isset($this->mappings[$field])?$this->mappings[$field]:$field;
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
     * Create a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_create($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('crlm_course', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" refers to a course description that already exists.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        if (!$this->validate_course_data('create', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'course')) {
            return false;
        }

        $record = $this->initialize_course_fields($record);

        $course = new course();
        $course->set_from_data($record);
        $course->save();

        if (isset($record->assignment)) {
            if (!$currid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $record->assignment))) {
                $identifier = $this->get_field_mapping('assignment');
                $this->fslogger->log_failure("$identifier value of \"{$record->assignment}\" does not refer to a valid program.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }

            $currcrs = new curriculumcourse();
            $currcrsrec = new stdClass;
            $currcrsrec->curriculumid = $currid;
            $currcrsrec->courseid = $course->id;

            $currcrs->set_from_data($currcrsrec);
            $currcrs->save();
        }

        //associate this course description to a Moodle course, if necessary
        $this->associate_course_to_moodle_course($record, $course->id);

        //log success
        $success_message = "Course description with idnumber \"{$record->idnumber}\" successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Delete a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_delete($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        if (isset($record->idnumber)) {
            if (!$DB->record_exists('crlm_course', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid course description.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        if (!$this->validate_course_data('delete', $record, $filename)) {
            return false;
        }

        if ($course = $DB->get_record('crlm_course', array('idnumber' => $record->idnumber))) {
            $course = new course($course);
            $course->delete();
        }

        //log success
        $success_message = "Course description with idnumber \"{$record->idnumber}\" successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

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
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        $message = "";

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if (!$crsid = $DB->get_field('crlm_course', 'id', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" does not refer to a valid course description.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            }
        }

        $currid = 0;

        if (isset($record->assignment)) {
            if (!$currid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $record->assignment))) {
                $identifier = $this->get_field_mapping('assignment');
                $this->fslogger->log_failure("$identifier value of \"{$record->assignment}\" does not refer to a valid program.", 0, $filename, $this->linenumber, $record, "course");
                return false;
            } else {
                if ($DB->record_exists('crlm_curriculum_course', array('curriculumid' => $currid, 'courseid' => $crsid))) {
                    $identifier = $this->get_field_mapping('idnumber');
                    $message = "Course description with $identifier \"{$record->idnumber}\" already assigned to program with idnumber \"{$record->assignment}\".";
                }
            }
        }

        if (!$this->validate_course_data('update', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'course')) {
            return false;
        }

        $record = $this->initialize_course_fields($record);

        $record->id = $crsid;

        $course = new course();
        $course->set_from_data($record);
        $course->save();

        if ($currid != 0) {
            $currcrs = new curriculumcourse();
            $assoc = new stdClass;
            $assoc->curriculumid = $currid;
            $assoc->courseid = $course->id;

            $currcrs->set_from_data($assoc);
            $currcrs->save();
        }

        //associate this course description to a Moodle course, if necessary
        $this->associate_course_to_moodle_course($record, $course->id);

        //log success
        $success_message = "Course description with idnumber \"{$record->idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Validates that track fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_track_data($action, $record, $filename) {
        global $CFG, $DB;

        if (isset($record->startdate)) {
            $value = $this->parse_date($record->startdate);
            if ($value === false) {
                $identifier = $this->get_field_mapping('startdate');
                $this->fslogger->log_failure("$identifier value of \"{$record->startdate}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "track");
                return false;
            } else {
                $record->startdate = $value;
            }
        }

        if (isset($record->enddate)) {
            $value = $this->parse_date($record->enddate);
            if ($value === false) {
                $identifier = $this->get_field_mapping('enddate');
                $this->fslogger->log_failure("$identifier value of \"{$record->enddate}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                             0, $filename, $this->linenumber, $record, "track");
                return false;
            } else {
                $record->enddate = $value;
            }
        }

        if (isset($record->autocreate)) {
            if (!$this->validate_fixed_list($record, 'autocreate', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('autocreate');
                $this->fslogger->log_failure("$identifier value of \"{$record->autocreate}\" is not one of the available options (0, 1).", 0, $filename,  $this->linenumber, $record, "track");
                return false;
            } else {
                $record->autocreate = ((string) $record->autocreate == 'yes' || (string) $record->autocreate == '1') ? '1' : '0';
            }
        }

        return true;
    }

    function track_create($record, $filename) {
        global $DB, $CFG;

        //field length checking
        $lengthcheck = $this->check_track_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('crlm_track', array('idnumber' => $record->idnumber))) {
                $identifier = $this->get_field_mapping('idnumber');
                $this->fslogger->log_failure("$identifier value of \"{$record->idnumber}\" refers to a track that already exists.", 0, $filename,  $this->linenumber, $record, "track");
                return false;
            }
        }

        if (isset($record->assignment)) {
            $id = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $record->assignment));
            if (!$id) {
                $identifier = $this->get_field_mapping('assignment');
                $this->fslogger->log_failure("$identifier value of \"{$record->assignment}\" does not refer to a valid program.", 0, $filename,  $this->linenumber, $record, "track");
                return false;
            }
        }

        if (!$this->validate_track_data('create', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('create', $record, $filename, 'track')) {
            return false;
        }

        $record->curid = $id;
        $record->timecreated = time();

        $track = new track();
        $track->set_from_data($record);
        $track->save();

        //log success
        $success_message = "Track with idnumber \"{$record->idnumber}\" successfully created.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    function track_update($record, $filename) {
        global $DB, $CFG;
        $message = "";

        //field length checking
        $lengthcheck = $this->check_track_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        if (isset($record->idnumber)) {
            if (isset($record->assignment)) {
                $message = "track with idnumber \"{$record->idnumber}\" was not re-assigned to program with idnumber \"{$record->assignment}\" because moving tracks between programs is not supported.";
            }

            if (!$id = $DB->get_field('crlm_track', 'id', array('idnumber' => $record->idnumber))) {
                $this->fslogger->log_failure("idnumber value of \"{$record->idnumber}\" does not refer to a valid track.", 0, $filename,  $this->linenumber, $record, "track");
                return false;
            }
        }

        if (!$this->validate_track_data('update', $record, $filename)) {
            return false;
        }

        $record = $this->add_custom_field_prefixes($record);

        //custom field validation
        if (!$this->validate_custom_field_data('update', $record, $filename, 'track')) {
            return false;
        }

        $record->id = $id;
        $record->timemodified = time();

        $track = new track();
        $track->set_from_data($record);
        $track->save();

        //log success
        $success_message = "Track with idnumber \"{$record->idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    function track_delete($record, $filename) {
        global $DB, $CFG;

        if (isset($record->idnumber)) {
            if (!$track = $DB->get_record('crlm_track', array('idnumber' => $record->idnumber))) {
                $this->fslogger->log_failure("idnumber value of \"{$record->idnumber}\" does not refer to a valid track.", 0, $filename,  $this->linenumber, $record, "track");
                return false;
            }
        }

        $track = new track($track);
        $track->delete();

        //log success
        $success_message = "Track with idnumber \"{$record->idnumber}\" successfully deleted.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * static method to determine if specified role shortname is an 'instructor' role
     *
     * @param  string $roleshortname  the role to check for 'instructor' role
     * @return bool   true if specified role shortname is an 'instructor' role, false otherwise
     */
    static function is_instructor_role($roleshortname) {
        static $instructorroles = array(
            'editingteacher',
            'teacher',
            'instructor'
        );
        return in_array(strtolower($roleshortname), $instructorroles);
    }

    /**
     * Performs any necessary conversion of the action value based on the
     * "createorupdate" setting for enrolments
     *
     * @param object $record One record of import data
     * @param string $action The supplied action
     * @return string The action to use in the import
     * @uses   $CFG
     * @uses   $DB
     */
    function handle_enrolment_createorupdate($record, $action) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        // check config setting
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        // split "context" into level and instance
        $pos = strpos($record->context, "_");
        $context = substr($record->context, 0, $pos);
        $instance = substr($record->context, $pos + 1);

        // user-related fields
        $username_set = isset($record->user_username) && $record->user_username != '';
        $email_set = isset($record->user_email) && $record->user_email != '';
        $idnumber_set = isset($record->user_idnumber) && $record->user_idnumber != '';

        $required_user_field_set = $username_set || $email_set || $idnumber_set;

        if (!empty($createorupdate)) {
            // determine if we have the necessary fields set
            if ($instance != '' && $required_user_field_set) {
                // determine enrolment validitiy
                $userid = $this->get_userid_from_record($record, 'bogus');
                $classid = $DB->get_field('crlm_class', 'id', array('idnumber' => $instance));

                if (!empty($userid) && !empty($classid)) {
                    if (isset($record->role) && self::is_instructor_role($record->role)) {
                        // instructor enrolment
                        $table = instructor::TABLE;
                    } else {
                        // student enrolment
                        $table = student::TABLE;
                    }

                    // identify the course
                    if ($DB->record_exists($table, array('userid' => $userid,
                                                         'classid' => $classid))) {
                        // course exists, so the action is an update
                        $action = 'update';
                    } else {
                        // course does not exist, so the action is a create
                        $action = 'create';
                    }
                }
            } else {
                $action = 'create';
            }
        }

        return $action;
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

        //remove empty fields
        $record = $this->remove_empty_fields($record);

        $pos = strpos($record->context, "_");
        $context = substr($record->context, 0, $pos);
        $idnumber = substr($record->context, $pos + 1);

        $valid_contexts = array(
            'course',
            'curriculum',
            'cluster',
            'track',
            'class',
            'user'
        );

        $valid_actions = array('create', 'update', 'delete', 'enrol', 'enroll', 'unenrol', 'unenroll');

        if (!in_array($context, $valid_contexts)) {
            if (in_array($action, $valid_actions)) {
                if ($action == 'enrol' || $action == 'enroll') {
                    $message = "Enrolment could not be created.";
                } else if ($action == 'unenrol' || $action == 'unenroll') {
                    $message = "Enrolment could not be deleted.";
                } else {
                    $message = "Enrolment could not be {$record->action}d.";
                }
            } else {
                $message = "Enrolment could not be processed.";
            }
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, '');
            return false;
        }

        if (!in_array($action, $valid_actions)) {
            $message = "Action of \"{$action}\" is not supported.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, 'enrolment');
            return false;
        }

        switch ($context) {
            case 'class':
                //apply "createorupdate" flag, if necessary
                if ($action == 'create' || $action == 'enrol' || $action == 'enroll') {
                    $action = $this->handle_enrolment_createorupdate($record, $action);
                }
                $record->action = $action;
                break;
        }

        // TBD ???
        if (!$this->check_required_fields('enrolment', $record, $filename)) {
            //missing a required field
            return false;
        }

        $method = "{$context}_enrolment_{$action}";
        if (method_exists($this, $method)) {
            return $this->$method($record, $filename, $idnumber);
        } else {
            //todo: add logging
            return false;
        }
    }

    /**
     * Stub required to get our custom "plugin_supports" method to work for enrolment
     * create actions
     */
    function enrolment_create() {
        //never actually called
    }

    /**
     * Stub required to get our custom "plugin_supports" method to work for enrolment
     * update actions
     */
    function enrolment_update() {
        //never actually called
    }

    /**
     * Stub required to get our custom "plugin_supports" method to work for enrolment
     * delete actions
     */
    function enrolment_delete() {
        //never actually called
    }

    /**
     * Obtains a userid from a data record for enrolment purposes
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return mixed The user id, or false if not found
     */
    function get_userid_from_record($record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');;
        require_once(elispm::lib('data/user.class.php'));

        $params = array();
        if (isset($record->user_username)) {
            $params['username'] = $record->user_username;
        }
        if (isset($record->user_email)) {
            $params['email'] = $record->user_email;
        }
        if (isset($record->user_idnumber)) {
            $params['idnumber'] = $record->user_idnumber;
        }

        return $DB->get_field(user::TABLE, 'id', $params);
    }

    /**
     * Validates that enrolment fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_program_enrolment_data($action, $record, $filename) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $errors = array();
        $error = false;

        if (isset($record->user_username)) {
            if (!$DB->record_exists(user::TABLE, array('username' => $record->user_username))) {
                $errors[] = "username value of \"{$record->user_username}\"";
                $error = true;
            }
        }

        if (isset($record->user_email)) {
            if (!$DB->record_exists(user::TABLE, array('email' => $record->user_email))) {
                $errors[] = "email value of \"{$record->user_email}\"";
                $error = true;
            }
        }

        if (isset($record->user_idnumber)) {
            if (!$DB->record_exists(user::TABLE, array('idnumber' => $record->user_idnumber))) {
                $errors[] = "idnumber value of \"{$record->user_idnumber}\"";
                $error = true;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            }
            return false;
        }

        if (isset($record->credits)) {
            $digits = strlen(substr($record->credits, 0, strpos($record->credits, '.')));
            $decdigits = strlen(substr(strrchr($record->credits, '.'), 1));

            if (!is_numeric($record->credits) || $decdigits > 2 || $digits > 10) {
                $this->fslogger->log_failure("credits value of \"{$record->credits}\" is not a number with at most ten total digits and two decimal digits.",
                                             0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        if (isset($record->locked)) {
            if ($record->locked != 0 && $record->locked != 1) {
                $this->fslogger->log_failure("locked value of \"{$record->locked}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        return true;
    }

    // Legacy support for 'enroll' action
    function curriculum_enrolment_enroll($record, $filename, $idnumber) {
        return $this->curriculum_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'enrol' action
    function curriculum_enrolment_enrol($record, $filename, $idnumber) {
        return $this->curriculum_enrolment_create($record, $filename, $idnumber);
    }

    function curriculum_enrolment_create($record, $filename, $idnumber) {
        global $DB, $CFG;

        if (!$curid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a program context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if ($DB->record_exists('crlm_curriculum_assignment', array('curriculumid' => $curid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is already enrolled in program \"{$idnumber}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_program_enrolment_data('create', $record, $filename)) {
            return false;
        }

        $record->userid = $userid;
        $record->curriculumid = $curid;
        $stucur = new curriculumstudent($record);
        $stucur->save();

        //log success
        $success_message = "User with {$user_descriptor} successfully enrolled in program \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    // Legacy support for 'unenroll' action
    function curriculum_enrolment_unenroll($record, $filename, $idnumber) {
        return $this->curriculum_enrolment_delete($record, $filename, $idnumber);
    }

    // Legacy support for 'unenrol' action
    function curriculum_enrolment_unenrol($record, $filename, $idnumber) {
        return $this->curriculum_enrolment_delete($record, $filename, $idnumber);
    }

    function curriculum_enrolment_delete($record, $filename, $idnumber) {
        global $DB, $CFG;

        if (!$curid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a program context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_program_enrolment_data('delete', $record, $filename)) {
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);
        $associd = $DB->get_field('crlm_curriculum_assignment', 'id', array('userid' => $userid, 'curriculumid' => $curid));

        $stucur = new curriculumstudent(array('id' => $associd));
        $stucur->delete();

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        //log success
        $success_message = "User with {$user_descriptor} successfully unenrolled from program \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Validates that track fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_track_enrolment_data($action, $record, $filename) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/elis/program/lib/datedelta.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $errors = array();
        $error = false;

        if (isset($record->user_username)) {
            if (!$DB->record_exists(user::TABLE, array('username' => $record->user_username))) {
                $errors[] = "username value of \"{$record->user_username}\"";
                $error = true;
            }
        }

        if (isset($record->user_email)) {
            if (!$DB->record_exists(user::TABLE, array('email' => $record->user_email))) {
                $errors[] = "email value of \"{$record->user_email}\"";
                $error = true;
            }
        }

        if (isset($record->user_idnumber)) {
            if (!$DB->record_exists(user::TABLE, array('idnumber' => $record->user_idnumber))) {
                $errors[] = "$identifier value of \"{$record->user_idnumber}\"";
                $error = true;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            }
            return false;
        }

        return true;
    }

    // Legacy support for 'enrol' action
    function track_enrolment_enrol($record, $filename, $idnumber) {
        return $this->track_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'enroll' action
    function track_enrolment_enroll($record, $filename, $idnumber) {
        return $this->track_enrolment_create($record, $filename, $idnumber);
    }

    /**
     * Create a track enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the track
     *
     * @return boolean true on success, otherwise false
     */
    function track_enrolment_create($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        if (!$trackid = $DB->get_field('crlm_track', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a track context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if ($DB->record_exists('crlm_user_track', array('trackid' => $trackid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is already enrolled in track \"{$idnumber}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_track_enrolment_data('create', $record, $filename)) {
            return false;
        }

        //obtain the track id
        $trackid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $idnumber));

        //create the association
        usertrack::enrol($userid, $trackid);

        //log success
        $success_message = "User with {$user_descriptor} successfully enrolled in track \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    // Legacy support for 'unenrol' action
    function track_enrolment_unenrol($record, $filename, $idnumber) {
        return $this->track_enrolment_delete($record, $filename, $idnumber);
    }

    // Legacy support for 'unenroll' action
    function track_enrolment_unenroll($record, $filename, $idnumber) {
        return $this->track_enrolment_delete($record, $filename, $idnumber);
    }

    /**
     * Delete a track enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the track
     *
     * @return boolean true on success, otherwise false
     */
    function track_enrolment_delete($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        if (!$trackid = $DB->get_field('crlm_track', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a track context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if (!$DB->record_exists('crlm_user_track', array('trackid' => $trackid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is not enrolled in track \"{$idnumber}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_track_enrolment_data('delete', $record, $filename)) {
            return false;
        }

        //obtain the track id
        $trackid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $idnumber));

        //delete the association
        $usertrackid = $DB->get_field(usertrack::TABLE, 'id', array('userid' => $userid,
                                                                    'trackid' => $trackid));
        $usertrack = new usertrack($usertrackid);
        $usertrack->delete();

        //log success
        $success_message = "User with {$user_descriptor} successfully unenrolled from track \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    // Legacy support for 'enrol' action
    function cluster_enrolment_enrol($record, $filename, $idnumber) {
        return $this->cluster_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'enroll' action
    function cluster_enrolment_enroll($record, $filename, $idnumber) {
        return $this->cluster_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'unenrol' action
    function cluster_enrolment_unenrol($record, $filename, $idnumber) {
        return $this->cluster_enrolment_delete($record, $filename, $idnumber);
    }

    // Legacy support for 'unenroll' action
    function cluster_enrolment_unenroll($record, $filename, $idnumber) {
        return $this->cluster_enrolment_delete($record, $filename, $idnumber);
    }

    /**
     * Validates that cluster fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_cluster_enrolment_data($action, $record, $filename) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/elis/program/lib/datedelta.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $errors = array();
        $error = false;

        if (isset($record->user_username)) {
            if (!$DB->record_exists(user::TABLE, array('username' => $record->user_username))) {
                $errors[] = "username value of \"{$record->user_username}\"";
                $error = true;
            }
        }

        if (isset($record->user_email)) {
            if (!$DB->record_exists(user::TABLE, array('email' => $record->user_email))) {
                $errors[] = "email value of \"{$record->user_email}\"";
                $error = true;
            }
        }

        if (isset($record->user_idnumber)) {
            if (!$DB->record_exists(user::TABLE, array('idnumber' => $record->user_idnumber))) {
                $errors[] = "idnumber value of \"{$record->user_idnumber}\"";
                $error = true;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            }
            return false;
        }

        return true;
    }

    /**
     * Create a cluster (user set) enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $name The name of the cluster / user set
     *
     * @return boolean true on success, otherwise false
     */
    function cluster_enrolment_create($record, $filename, $name) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        if (!$clusterid = $DB->get_field('crlm_cluster', 'id', array('name' => $name))) {
            $this->fslogger->log_failure("instance value of \"{$name}\" does not refer to a valid instance of a user set context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if ($DB->record_exists('crlm_cluster_assignments', array('clusterid' => $clusterid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is already enrolled in user set \"{$name}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_cluster_enrolment_data('create', $record, $filename)) {
            return false;
        }

        //obtain the cluster / userset id
        $clusterid = $DB->get_field(userset::TABLE, 'id', array('name' => $name));

        //create the association
        $clusterassignment = new clusterassignment(array('userid' => $userid,
                                                         'clusterid' => $clusterid,
                                                         'plugin' => 'manual',
                                                         'autoenrol' => 0));
        $clusterassignment->save();
        //log success
        $success_message = "User with {$user_descriptor} successfully enrolled in user set \"{$name}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Delete a cluster (user set) enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $name The name of the cluster / user set
     *
     * @return boolean true on success, otherwise false
     */
    function cluster_enrolment_delete($record, $filename, $name) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        if (!$clusterid = $DB->get_field('crlm_cluster', 'id', array('name' => $name))) {
            $this->fslogger->log_failure("instance value of \"{$name}\" does not refer to a valid instance of a user set context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if (!$DB->record_exists('crlm_cluster_assignments', array('clusterid' => $clusterid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is not enrolled in user set \"{$name}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_cluster_enrolment_data('delete', $record, $filename)) {
            return false;
        }

        //obtain the cluster / userset id
        $clusterid = $DB->get_field(userset::TABLE, 'id', array('name' => $name));

        //delete the association
        $clusterassignmentid = $DB->get_field(clusterassignment::TABLE, 'id', array('userid' => $userid,
                                                                                    'clusterid' => $clusterid,
                                                                                    'plugin' => 'manual'));
        $clusterassignment = new clusterassignment($clusterassignmentid);
        $clusterassignment->delete();

        //log success
        $success_message = "User with {$user_descriptor} successfully unenrolled from user set \"{$name}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Obtain the back-end completion status constant from an enrolment import record
     *
     * @param object $record An import record
     * @return mixed The numerical completion status value, NULL if not set, or
     *               false if set to an invalid value
     */
    function get_completestatusid($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        $result = NULL;

        if (!isset($record->completestatusid)) {
            //not set
            return $result;
        }

        //should be case-insensitive
        $completestatusid = strtolower($record->completestatusid);

        $valid_statuses = array(
            student::STUSTATUS_NOTCOMPLETE,
            student::STUSTATUS_FAILED,
            student::STUSTATUS_PASSED
        );

        if ($completestatusid === "passed") {
            $result = student::STUSTATUS_PASSED;
        } else if ($completestatusid === "failed") {
            $result = student::STUSTATUS_FAILED;
        } else if ($completestatusid === "not completed") {
            $result = student::STUSTATUS_NOTCOMPLETE;
        } else if ($this->validate_fixed_list($record, 'completestatusid', $valid_statuses)) {
            $result = (int)$record->completestatusid;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Validates whether the specified class-context enrolment record is an
     * instructor enrolment
     *
     * @param $record The current class-context enrolment import record
     * @return boolean Return true if instructor enrolment, or false if student
     */
    function record_is_instructor_assignment($record) {
        if (isset($record->role)) {
            $is_instructor = self::is_instructor_role($record->role);
        } else {
            $is_instructor = false;
        }

        return $is_instructor;
    }

    /**
     * Validates that class fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_class_enrolment_data($action, $record, $filename) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/elis/program/lib/datedelta.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $errors = array();
        $error = false;

        if (isset($record->user_username)) {
            if (!$DB->record_exists(user::TABLE, array('username' => $record->user_username))) {
                $errors[] = "username value of \"{$record->user_username}\"";
                $error = true;
            }
        }

        if (isset($record->user_email)) {
            if (!$DB->record_exists(user::TABLE, array('email' => $record->user_email))) {
                $errors[] = "email value of \"{$record->user_email}\"";
                $error = true;
            }
        }

        if (isset($record->user_idnumber)) {
            if (!$DB->record_exists(user::TABLE, array('idnumber' => $record->user_idnumber))) {
                $errors[] = "idnumber value of \"{$record->user_idnumber}\"";
                $error = true;
            }
        }

        if ($error) {
            if (count($errors) == 1) {
                $this->fslogger->log_failure(implode($errors, ", ") . " does not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            } else {
                $this->fslogger->log_failure(implode($errors, ", ") . " do not refer to a valid user.", 0, $filename, $this->linenumber, $record, "enrolment");
            }
            return false;
        }

        if (isset($record->enrolmenttime)) {
            $value = $this->parse_date($record->enrolmenttime);
            if ($value === false) {
                $identifier = $this->get_field_mapping('enrolmenttime');
                $this->fslogger->log_failure("$identifier value of \"{$record->enrolmenttime}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                              0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        if (isset($record->completetime)) {
            $value = $this->parse_date($record->completetime);
            if ($value === false) {
                $identifier = $this->get_field_mapping('completetime');
                $this->fslogger->log_failure("$identifier value of \"{$record->completetime}\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.",
                                              0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        $completestatusid = $this->get_completestatusid($record);

        if ($completestatusid === false) {
            $identifier = $this->get_field_mapping('completestatusid');
            $this->fslogger->log_failure("$identifier value of \"{$record->completestatusid}\" is not one of the available options (0, 1, 2).",
                                          0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (isset($record->grade)) {
            $digits = strlen(substr($record->grade, 0, strpos($record->grade, '.')));
            $decdigits = strlen(substr(strrchr($record->grade, '.'), 1));

            if (!is_numeric($record->grade) || $decdigits > 5 || $digits > 10) {
                $identifier = $this->get_field_mapping('reqcredits');
                $this->fslogger->log_failure("$identifier value of \"{$record->grade}\" is not a number with at most ten total digits and five decimal digits.",
                                          0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        if (isset($record->credits)) {
            $digits = strlen(substr($record->credits, 0, strpos($record->credits, '.')));
            $decdigits = strlen(substr(strrchr($record->credits, '.'), 1));

            if (!is_numeric($record->credits) || $decdigits > 2 || $digits > 10) {
                $identifier = $this->get_field_mapping('credits');
                $this->fslogger->log_failure("$identifier value of \"{$record->credits}\" is not a number with at most ten total digits and two decimal digits.",
                                          0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            }
        }

        if (isset($record->locked)) {
            if (!$this->validate_fixed_list($record, 'locked', static::$yes_no_field_options)) {
                $identifier = $this->get_field_mapping('locked');
                $this->fslogger->log_failure("$identifier value of \"{$record->locked}\" is not one of the available options (0, 1).", 0, $filename, $this->linenumber, $record, "enrolment");
                return false;
            } else {
                $record->locked = ((string) $record->locked == 'yes' || (string) $record->locked == '1') ? '1' : '0';
            }
        }

        return true;
    }
    /**
     * Create a student class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_create_student($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        if (!$crsid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if ($DB->record_exists(student::TABLE, array('classid' => $crsid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is already enrolled in class \"{$idnumber}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('create', $record, $filename)) {
            return false;
        }

        //obtain the class id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        //determine enrolment and completion times
        $today = mktime(0, 0, 0);
        if (isset($record->enrolmenttime)) {
            $enrolmenttime = $this->parse_date($record->enrolmenttime);
        } else {
            $enrolmenttime = $today;
        }
        if (isset($record->completetime)) {
            $completetime = $this->parse_date($record->completetime);
        } else {
            $completetime = $today;
        }

        //create the association
        $student = new student(array('userid' => $userid,
                                     'classid' => $classid,
                                     'enrolmenttime' => $enrolmenttime,
                                     'completetime' => $completetime));
        $completestatusid = $this->get_completestatusid($record);
        //set up a completion status, if set
        if ($completestatusid !== NULL) {
            $student->completestatusid = $completestatusid;
        }

        //handle optional values
        if (isset($record->grade)) {
            $student->grade = $record->grade;
        }
        if (isset($record->credits)) {
            $student->credits = $record->credits;
        }
        if (isset($record->locked)) {
            $student->locked = $record->locked;
        }

        $student->save();

        //TODO: consider refactoring once ELIS-6546 is resolved
        if (isset($student->completestatusid) && $student->completestatusid == STUSTATUS_PASSED) {
            $student->complete();
        } else {
            $student->save();
        }

        //log success
        $success_message = "User with {$user_descriptor} successfully enrolled in class instance \"{$idnumber}\" as a student.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Create an instructor class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     * @uses   $CFG
     * @uses   $DB
     */
    function class_enrolment_create_instructor($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));

        if (!$crsid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if ($DB->record_exists(instructor::TABLE, array('classid' => $crsid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is already enrolled in " .
                                         "class instance \"{$idnumber}\".", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('create', $record, $filename)) {
            return false;
        }

        // obtain the class id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        // determine assignment and completion times
        $today = mktime(0, 0, 0);
        if (isset($record->assigntime)) {
            $assigntime = $this->parse_date($record->assigntime);
        } else {
            $assigntime = $today;
        }
        if (isset($record->completetime)) {
            $completetime = $this->parse_date($record->completetime);
        } else {
            $completetime = $today;
        }

        // create the association
        $insdata = array(
            'userid'       => $userid,
            'classid'      => $classid,
            'assigntime'   => $assigntime,
            'completetime' => $completetime
        );
        $instructor = new instructor($insdata);
        if (!empty($record->role)) {
            $instructor->roleshortname = $record->role;
        }
        $instructor->save();

        // log success
        $success_message = "User with {$user_descriptor} successfully enrolled in class instance \"{$idnumber}\" as an instructor.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Create a student or instructor class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_create($record, $filename, $idnumber) {
        //determine if student or instructor
        if ($this->record_is_instructor_assignment($record)) {
            //run instructor import
            return $this->class_enrolment_create_instructor($record, $filename, $idnumber);
        } else {
            //run student import
            return $this->class_enrolment_create_student($record, $filename, $idnumber);
        }
    }

    /**
     * Update a student class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_update_student($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        if (!$crsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('update', $record, $filename)) {
            return false;
        }

        //obtain the class id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        //obtain the user id
        $userid = $this->get_userid_from_record($record, $filename);

        //update the record
        $id = $DB->get_field(student::TABLE, 'id', array('classid' => $classid,
                                                         'userid' => $userid));
        $student = new student($id);
        //need to call load because saving a student needs the full object for events
        //and dynamic loading will blow away changes otherwise
        $student->load();

        //enrolment and completion times
        if (isset($record->enrolmenttime)) {
            $student->enrolmenttime = $this->parse_date($record->enrolmenttime);
        }
        if (isset($record->completetime)) {
            $student->completetime = $this->parse_date($record->completetime);
        }

        $completestatusid = $this->get_completestatusid($record);
        //set up a completion status, if set
        if ($completestatusid !== NULL) {
            $student->completestatusid = $completestatusid;
        }
        if (isset($record->grade)) {
            $student->grade = $record->grade;
        }
        if (isset($record->credits)) {
            $student->credits = $record->credits;
        }
        if (isset($record->locked)) {
            $student->locked = $record->locked;
        }

        //TODO: consider refactoring once ELIS-6546 is resolved
        if (isset($student->completestatusid) && $student->completestatusid == STUSTATUS_PASSED &&
            $DB->get_field(student::TABLE, 'completestatusid', array('id' => $student->id)) != STUSTATUS_PASSED) {
            $student->complete();
        } else {
            $student->save();
        }

        //string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        //log success
        $success_message = "Student enrolment for user with {$user_descriptor} in class instance \"{$idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Update an instructor class instance assignment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     * @uses   $CFG
     * @uses   $DB
     */
    function class_enrolment_update_instructor($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        if (!$crsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('update', $record, $filename)) {
            return false;
        }

        // obtain the class id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        // obtain the user id
        $userid = $this->get_userid_from_record($record, $filename);

        // update the record
        $id = $DB->get_field(instructor::TABLE, 'id', array('classid' => $classid,
                                                            'userid' => $userid));
        $instructor = new instructor($id);
        $instructor->load();
        // enrolment and completion times
        if (isset($record->assigntime)) {
            $instructor->assigntime = $this->parse_date($record->assigntime);
        }
        if (isset($record->completetime)) {
            $instructor->completetime = $this->parse_date($record->completetime);
        }
        if (!empty($record->role)) {
            $instructor->roleshortname = $record->role;
        }
        $instructor->save();

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        // log success
        $success_message = "Instructor enrolment for user with {$user_descriptor} in class instance \"{$idnumber}\" successfully updated.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Update a student or instructor class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_update($record, $filename, $idnumber) {
        //determine if student or instructor
        if ($this->record_is_instructor_assignment($record)) {
            //run instructor import
            return $this->class_enrolment_update_instructor($record, $filename, $idnumber);
        } else {
            //run student import
            return $this->class_enrolment_update_student($record, $filename, $idnumber);
        }
    }

    /**
     * Delete a student class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_delete_student($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        if (!$crsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if (!$DB->record_exists('crlm_class_enrolment', array('classid' => $crsid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is not enrolled in class instance \"{$idnumber}\" as student.",
                                          0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('delete', $record, $filename)) {
            return false;
        }

        // obtain the class id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        // delete the association
        $studentid = $DB->get_field(student::TABLE, 'id', array('userid' => $userid,
                                                                'classid' => $classid));
        $student = new student($studentid);
        $student->load();
        $student->delete();

        // log success
        $success_message = "User with {$user_descriptor} successfully unenrolled from class instance \"{$idnumber}\" as a student.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Delete an instructor class instance assignment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     * @uses   $CFG
     * @uses   $DB
     */
    function class_enrolment_delete_instructor($record, $filename, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        if (!$crsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $idnumber))) {
            $this->fslogger->log_failure("instance value of \"{$idnumber}\" does not refer to a valid instance of a class context.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        $userid = $this->get_userid_from_record($record, $filename);

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        if (!$DB->record_exists(instructor::TABLE, array('classid' => $crsid, 'userid' => $userid))) {
            $this->fslogger->log_failure("User with {$user_descriptor} is not enrolled in " .
                                         "class instance \"{$idnumber}\" as instructor.", 0, $filename, $this->linenumber, $record, "enrolment");
            return false;
        }

        if (!$this->validate_class_enrolment_data('delete', $record, $filename)) {
            return false;
        }

        // obtain the cluster / userset id
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $idnumber));

        // delete the association
        $studentid = $DB->get_field(instructor::TABLE, 'id', array('userid' => $userid,
                                                                   'classid' => $classid));
        $instructor = new instructor($studentid);
        $instructor->load();
        $instructor->delete();

        // log success
        $success_message = "User with {$user_descriptor} successfully unenrolled from class instance \"{$idnumber}\" as an instructor.";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    // Legacy support for 'enrol' action
    function class_enrolment_enrol($record, $filename, $idnumber) {
        return $this->class_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'enroll' action
    function class_enrolment_enroll($record, $filename, $idnumber) {
        return $this->class_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'unenrol' action
    function class_enrolment_unenrol($record, $filename, $idnumber) {
        return $this->class_enrolment_delete($record, $filename, $idnumber);
    }

    // Legacy support for 'unenroll' action
    function class_enrolment_unenroll($record, $filename, $idnumber) {
        return $this->class_enrolment_delete($record, $filename, $idnumber);
    }

    /**
     * Delete a student or instructor class instance enrolment
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the class instance
     *
     * @return boolean true on success, otherwise false
     */
    function class_enrolment_delete($record, $filename, $idnumber) {
        //determine if student or instructor
        if ($this->record_is_instructor_assignment($record)) {
            //run instructor import
            return $this->class_enrolment_delete_instructor($record, $filename, $idnumber);
        } else {
            //run student import
            return $this->class_enrolment_delete_student($record, $filename, $idnumber);
        }
    }

    // Legacy support for 'enroll' action
    function user_enrolment_enroll($record, $filename, $idnumber) {
        return $this->user_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'enrol' action
    function user_enrolment_enrol($record, $filename, $idnumber) {
        return $this->user_enrolment_create($record, $filename, $idnumber);
    }

    // Legacy support for 'unenroll' action
    function user_enrolment_unenroll($record, $filename, $idnumber) {
        return $this->user_enrolment_delete($record, $filename, $idnumber);
    }

    // Legacy support for 'unenrol' action
    function user_enrolment_unenrol($record, $filename, $idnumber) {
        return $this->user_enrolment_delete($record, $filename, $idnumber);
    }

    /**
     * Assign a role on a Moodle user context
     *
     * @param object $record   One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the target user
     *
     * @return boolean true on success, otherwise false
     * @uses   $CFG
     * @uses   $DB
     */
    function user_enrolment_create($record, $filename, $idnumber) {
        global $CFG, $DB;

        $params = array();
        if (isset($record->user_username)) {
            $params['username'] = $record->user_username;
            $params['mnethostid'] = $CFG->mnet_localhost_id;
        }
        if (isset($record->user_email)) {
            $params['email'] = $record->user_email;
        }
        if (isset($record->user_idnumber)) {
            $params['idnumber'] = $record->user_idnumber;
        }

        $userid = $DB->get_field('user', 'id', $params);

        $targetuserid = $DB->get_field('user', 'id', array('idnumber' => $idnumber));

        $targetcontext = context_user::instance($targetuserid);

        $roleid = $DB->get_field('role', 'id', array('shortname' => $record->role));
        // TBD: test $roleid & $targetcontext
        role_assign($roleid, $userid, $targetcontext->id);

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        // log success
        $success_message = "User with {$user_descriptor} successfully assigned role with shortname \"{$record->role}\" on user \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

        return true;
    }

    /**
     * Unassign a role from a Moodle user context
     *
     * @param object $record   One record of import data
     * @param string $filename The import file name, used for logging
     * @param string $idnumber The idnumber of the target user
     *
     * @return boolean true on success, otherwise false
     * @uses   $CFG
     * @uses   $DB
     */
    function user_enrolment_delete($record, $filename, $idnumber) {
        global $CFG, $DB;

        $params = array();
        if (isset($record->user_username)) {
            $params['username'] = $record->user_username;
            $params['mnethostid'] = $CFG->mnet_localhost_id;
        }
        if (isset($record->user_email)) {
            $params['email'] = $record->user_email;
        }
        if (isset($record->user_idnumber)) {
            $params['idnumber'] = $record->user_idnumber;
        }

        $userid = $DB->get_field('user', 'id', $params);

        $targetuserid = $DB->get_field('user', 'id', array('idnumber' => $idnumber));

        $targetcontext = context_user::instance($targetuserid);

        $roleid = $DB->get_field('role', 'id', array('shortname' => $record->role));
        // TBD: test $roleid & $targetcontext
        role_unassign($roleid, $userid, $targetcontext->id);

        // string to describe the user
        $user_descriptor = $this->get_user_descriptor($record, false, 'user_');

        // log success
        $success_message = "User with {$user_descriptor} successfully unassigned role with shortname \"{$record->role}\" on user \"{$idnumber}\".";
        $this->fslogger->log_success($success_message, 0, $filename, $this->linenumber);

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
        require_once(dirname(__FILE__).'/rlip_import_version1elis_fslogger.class.php');

        return new rlip_import_version1elis_fslogger($fileplugin, $manual);
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
            rlip_send_log_emails('rlipimport_version1elis', $logids, $this->manual);
        }

        return $result;
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
        $displaystring = get_string('configfieldstreelink', 'rlipimport_version1elis');
        $url = $CFG->wwwroot.'/blocks/rlip/importplugins/version1elis/config_fields.php';
        $page = new admin_externalpage("{$parentname}_fields", $displaystring, $url);

        //add it to the tree
        $adminroot->add($parentname, $page);
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
        $translated_action = $this->get_field_mapping('action');

        if (!in_array($translated_action, $header)) {
            //action column not specified
            $message = "Import file {$filename} was not processed because it is missing the following ".
                       "required column: {$translated_action}. Please fix the import file and re-upload it.";
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
        $required_fields = ($entity == 'course')
                           ? array('context') // ELIS-6133: ugly hack!
                           : $this->plugin_supports_action($entity, 'delete');

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
            // ELIS-6149 -- A group of fields can appear anywhere within the list of requried fields, so we must check
            // each field to see if it's an array.
            $fields  = array_values($missing_fields);
            $message = '';

            foreach ($fields as $field) {
                // A group of fields should be the only thing appearing within the log file
                if (is_array($field)) {
                    //basic case, all missing fields are required
                    $field_display = implode(', ', $field);

                    $message = "Import file {$filename} was not processed because one of the following columns is ".
                            "required but all are unspecified: {$field_display}. Please fix the import file and re-upload it.";
                }
            }

            //1-of-n case
            if (empty($message)) {
                //list of fields, as displayed
                $field_display = implode(', ', $missing_fields);
                //singular/plural handling
                $label = 'column';
                if (count($missing_fields) > 1) {
                    $label .= 's';
                }

                $message = "Import file {$filename} was not processed because it is missing the following required {$label}: ".
                           "{$field_display}. Please fix the import file and re-upload it.";
            }

            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            $this->dblogger->signal_missing_columns($message);
            return false;
        }

        return true;
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
     * @param string $prefix a string prefix to expect at the beginning of properties
     * @return string The description of identifying fields, as a
     *                comma-separated string
     * [field1] "value1", ...
     */
    function get_user_descriptor($record, $value_syntax = false, $prefix = '') {
        $fragments = array();

        //the fields we care to check
        $possible_fields = array('username',
                                 'email',
                                 'idnumber');

        foreach ($possible_fields as $field) {
            //use the prefix to find data, if necessary
            $customkey = $prefix.$field;

            if (isset($record->$customkey) && $record->$customkey !== '') {
                //data for that field
                $value = $record->$customkey;

                //calculate syntax fragment
                if ($value_syntax) {
                    $identifier = $this->mappings[$customkey];
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
     * Specifies flag for indicating whether this plugin is actually available
     * on the current system, particularly for viewing in the UI and running
     * scheduled tasks
     */
    function is_available() {
        global $CFG;

        //this plugin is only available if the PM code is present
        return file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
    }
}

