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
require_once ($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/user.class.php'));

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

    static $import_fields_course_create = array('context');
    static $import_fields_course_update = array('context');
    static $import_fields_course_delete = array('context');
    //TODO: deal with all required fields structures / setup
    /*
    static $import_fields_course_create = array('idnumber', 'name');
    static $import_fields_course_update = array('idnumber');
    static $import_fields_course_delete = array('idnumber');
    */

    static $import_fields_program_create = array('idnumber', 'name');
    static $import_fields_program_update = array('idnumber');
    static $import_fields_program_delete = array('idnumber');

    static $import_fields_class_create = array('idnumber','assignment');
    static $import_fields_class_update = array('idnumber');
    static $import_fields_class_creat = array('idnumber');
    static $available_fields_class = array('idnumber', 'startdate', 'enddate', 'starttimehour',
                                          'starttimeminute', 'endtimehour', 'endtimeminute', 'maxstudents',
                                          'enrol_from_waitlist', 'assignment', 'track', 'autoenrol', 'link');

    static $import_fields_track_create = array('idnumber', 'assignment');
    static $import_fields_track_update = array('idnumber');
    static $import_fields_track_delete = array('idnumber');
    static $available_fields_track = array('idnumber', 'name', 'description', 'startdate',
                                          'enddate', 'assignment');


    //store mappings for the current entity type
    var $mappings = array();

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
     * Create a user
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function user_create($record, $filename) {
        global $CFG, $DB;

        // TODO: validation
        $data = new object();
        $data->idnumber = $record->idnumber;
        $data->username = $record->username;
        $data->firstname = $record->firstname;
        $data->lastname = $record->lastname;
        $data->email = $record->email;
        $data->country = $record->country;
        /*$data->mi = $record->mi;
        $data->email2 = $record->email2;
        $data->address = $record->address;
        $data->address2 = $record->address2;
        $data->city = $record->city;
        $data->state = $record->state;
        $data->postalcode = $record->postalcode;
        $data->phone = $record->phone;
        $data->phone2 = $record->phone2;
        $data->fax = $record->fax;
        $data->birthdate = $record->birthdate;
        $data->gender = $record->gender;
        $data->language = $record->language;
        $data->transfercredits = $record->transfercredits;
        $data->comments = $record->comments;
        $data->notes = $record->notes;
        $data->inactive = $record->inactive;*/

        // TODO: validation
        $user = new user($data);

        $user->save();
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

        // TODO: validation
        $params = array('username'  => $record->username,
                        'email'     => $record->email,
                        'idnumber'  => $record->idnumber);

        $record->id = $DB->get_field('crlm_user', 'id', $params);
        $record->timemodified = time();
        $DB->update_record('crlm_user', $record);

        $user = new user($record);
        $user->save();

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

        // TODO: validation
        $params = array('username'  => $record->username,
                        'email'     => $record->email,
                        'idnumber'  => $record->idnumber);

        if ($user = $DB->get_record('user', $params)) {
            user_delete_user($user);
        }

        if ($user = $DB->get_record('crlm_user', $params)) {
            $user = new user($user);
            $user->delete();
        }

        return true;
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
        //$this->mappings = rlipimport_version1_get_mapping($entity);

        return parent::process_import_file($entity, $maxruntime, $state);
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
            //$message = "Required field {$field_display} is unspecified or empty.";
            //$this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $entitytype);
            return false;
        }

        //feature, in the standard Moodle "plugin_supports" format
        $feature = $entitytype.'_'.$record->action;
        if (!$this->plugin_supports($feature)) {
            //invalid action for this entity type
            //$message = "Action of \"{$record->action}\" is not supported.";
            //$this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $entitytype);
            return false;
        }

        return true;
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
        $createorupdate = get_config('rlipimport_version1elis', 'createorupdate');

        if (!empty($createorupdate)) {
            if (isset($record->idnumber) && $record->idnumber !== '') {
                //identify the course
                if ($DB->record_exists('crlm_course', array('idnumber' => $record->idnumber))) {
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

        $context = '';
        if (isset($record->context)) {
            $context = $record->context;
        }

        switch ($context) {
            // TODO
            case 'class':

            break;
            case 'curriculum':

            break;
            case 'course':
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
            break;
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

    function class_create($record, $filename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        // TODO: validation
        $record = $this->remove_invalid_class_fields($record);

        $lengthcheck = $this->check_class_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        /*if (!$this->validate_core_class_data('create', $record, $filename)) {
            return false;
        }*/

        $data = new object();
        $data->idnumber = $record->idnumber;
        $id = $DB->get_field('crlm_course', 'id', array('idnumber'  => $record->assignment));
        $data->courseid = $id;
        /*
        $data->startdate = $record->startdate;
        $data->enddate = $record->enddate;
        $data->starttimehour = $record->starttimehour;
        $data->starttimeminute = $record->starttimeminute;
        $data->endtimehour = $record->endtimehour;
        $data->endtimeminute = $record->endtimeminute;
        $data->maxstudents = $record->maxstudents;
        $data->enrol_from_waitlist = $record->enrol_from_waitlist;
        */
        $pmclass = new pmclass($data);
        $pmclass->save();

        //associate this class instance to a track, if necessary
        $this->associate_class_to_track($record, $pmclass->id);
        //associate this class instance to a Moodle course, if necessary
        $this->associate_class_to_moodle_course($record, $pmclass->id);

        return true;
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
                    // TODO: validaton
                    /*$this->fslogger->log_failure("{$identifier} value of \"{$value}\" exceeds ".
                                                 "the maximum field length of {$length}.",
                                                 0, $filename, $this->linenumber, $record, $entitytype);*/
                    return false;
                }
            }
        }

        //no problems found
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
        global $DB;

        // TODO: Add plugin support for "class" type?
        //if ($this->plugin_supports($entitytype) !== false) {
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
        //} else {
            //return false;
        //}
    }

    function class_update($record, $filename) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        // TODO: validation
        $record = $this->remove_invalid_class_fields($record);

        $lengthcheck = $this->check_class_field_lengths($record, $filename);
        if (!$lengthcheck) {
            return false;
        }

        /*if (!$this->validate_core_class_data('update', $record, $filename)) {
            return false;
        }*/

        $data = new object();
        $id = $DB->get_field('crlm_class', 'id', array('idnumber'  => $record->idnumber));
        $data->id = $id;
        $data->idnumber = $record->idnumber;
        $data->maxstudents = $record->maxstudents;
        $pmclass = new pmclass($data);
        $pmclass->save();

        //associate this class instance to a track, if necessary
        $this->associate_class_to_track($record, $pmclass->id);
        //associate this class instance to a Moodle course, if necessary
        $this->associate_class_to_moodle_course($record, $pmclass->id);

        return true;
    }

    function class_delete($record, $filename) {
        global $DB, $CFG;

        // TODO: validation
        if ($course = $DB->get_record('crlm_class', array('idnumber' => $record->idnumber))) {
            $course = new pmclass($course);
            $course->delete();
        }

        return true;
    }

    function curriculum_create($record, $filename) {
        global $DB, $CFG;

        // TODO: validation
        $data = new object();
        $data->idnumber = $record->idnumber;
        $data->name = $record->name;
        /*$data->description = $record->description;
        $data->reqcredits = $record->reqcredits;
        $data->timetocomplete = $record->timetocomplete;
        $data->frequency = $record->frequency;
        $data->priority = $record->priority;*/

        $cur = new curriculum($data);
        $cur->save();

        return true;
    }

    function curriculum_update($record, $filename) {
        global $CFG, $DB;

        // TODO: validation
        $id = $DB->get_field('crlm_curriculum', 'id', array('idnumber'  => $record->idnumber));
        $data = new object();
        $data->id = $id;
        $data->idnumber = $record->idnumber;
        $data->name = $record->name;
        /*$data->description = $record->description;
        $data->reqcredits = $record->reqcredits;
        $data->timetocomplete = $record->timetocomplete;
        $data->frequency = $record->frequency;
        $data->priority = $record->priority;*/

        $cur = new curriculum($data);
        $cur->save();

        return true;
    }

    function curriculum_delete($record, $filename) {
        global $DB, $CFG;

        // TODO: validation
        if ($cur = $DB->get_record('crlm_curriculum', array('idnumber' => $record->idnumber))) {
            $cur = new curriculum($cur);
            $cur->delete();
        }

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
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        //TODO: remove unavailable fields from the record
        if (!isset($record->parent) || $record->parent == 'top') {
            $record->parent = 0;
        } else if ($parentid = $DB->get_field(userset::TABLE, 'id', array('name' => $record->parent))) {
            $record->parent = $parentid;
        } else {
            //invalid parent specification
            //TODO: log error and return false
        }

        if (!isset($record->display)) {
            //should default to the empty string rather than null
            $record->display = '';
        }

        $cluster = new userset($record);
        $cluster->save();

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

        //TODO: remove unspported fields here

        $id = $DB->get_field(userset::TABLE, 'id', array('name'  => $record->name));

        if (!$id) {
            //invalid name specification
            //TODO: log an error and return false
        }

        $data = new userset($record);
        $data->id = $id;
        $data->save();

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

        //TODO: remove unspported fields here

        $id = $DB->get_field(userset::TABLE, 'id', array('name'  => $record->name));

        if (!$id) {
            //invalid name specification
            //TODO: log an error and return false
        }

        $data = new userset($id);
        $data->delete();

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
     * Create a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @return boolean true on success, otherwise false
     */
    function course_create($record, $filename) {
        global $CFG, $DB;
        require_once ($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        // TODO: validation
        $data = new object();
        $data->idnumber = $record->idnumber;
        $data->name = $record->name;
        $data->syllabus = '';
        /*$data->code = $record->code;
        $data->lengthdescription = $recrod->lengthdescription;
        $data->length = $record->length;
        $data->credits = $record->credits;
        $data->completion_grade = $record->completion_grade;
        $data->cost = $record->cost;
        $data->version = $record->version;
        $data->assignment = $record->assignment;
        $data->link = $record->link;*/

        $course = new course($data);
        $course->save();

        //associate this course description to a Moodle course, if necessary
        $this->associate_course_to_moodle_course($record, $course->id);

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
        require_once ($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        // TODO: validation
        if ($course = $DB->get_record('crlm_course', array('idnumber' => $record->idnumber))) {
            $course = new course($course);
            $course->delete();
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
        require_once ($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        // TODO: validation
        $id = $DB->get_field('crlm_course', 'id', array('idnumber'  => $record->idnumber));
        $data = new object();
        $data->id = $id;
        $data->idnumber = $record->idnumber;
        $data->name = $record->name;
        $data->syllabus = '';
        $course = new course($data);
        $course->save();

        //associate this course description to a Moodle course, if necessary
        $this->associate_course_to_moodle_course($record, $course->id);

        return true;
    }

    function track_create($record, $filename) {
        global $DB, $CFG;

        $record = $this->remove_invalid_track_fields($record);

        // TODO: validation
        $id = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $record->assignment));
        $record->curid = $id;
        $record->timecreated = time();

        $track = new track($record);
        $track->save();

        return true;
    }

    function track_update($record, $filename) {
        global $DB, $CFG;

        // TODO: Validaiton

        $record = $this->remove_invalid_track_fields($record);

       // $data = new object();
        $id = $DB->get_field('crlm_track', 'id', array('idnumber' => $record->idnumber));
        $record->id = $id;
      //  $data->name = $record->name;
      //  $data->description = $record->description;
      //  $data->startdate = $record->startdate;
      //  $data->enddate = $record->enddate;
        $record->timemodified = time();
       // $data->idnumber = $record->idnumber;

        $track = new track($record);
        $track->save();

        return true;
    }

    function track_delete($record, $filename) {
        global $DB, $CFG;

        // TODO: validation
        if ($track = $DB->get_record('crlm_track', array('idnumber' => $record->idnumber))) {
            $track = new track($track);
            $track->delete();
        }

        return true;
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

        // TODO: more validation

        //remove empty fields
        $record = $this->remove_empty_fields($record);

        $pos = strpos($record->context, "_");
        $entity = substr($record->context, 0, $pos);
        $idnumber = substr($record->context, $pos + 1);

        $method = "{$entity}_enrolment_{$action}";
        if (method_exists($this, $method)) {
            return $this->$method($record, $filename, $idnumber);
        } else {
            //todo: add logging
            return false;
        }
    }

    function curriculum_enrolment_create($record, $filename, $idnumber) {
        global $DB, $CFG;

        // TODO: validation
        $curid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $idnumber));
        $userid = $DB->get_field('crlm_user', 'id', array('idnumber' => $record->user_idnumber));

        $stucur = new curriculumstudent(array('userid' => $userid, 'curriculumid' => $curid));
        $stucur->save();

        return true;
    }

    function curriculum_enrolment_delete($record, $filename, $idnumber) {
        global $DB, $CFG;

        // TODO: validation
        $curid = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $idnumber));
        $userid = $DB->get_field('crlm_user', 'id', array('idnumber' => $record->user_idnumber));
        $associd = $DB->get_field('crlm_curriculum_assignment', 'id', array('userid' => $userid, 'curriculumid' => $curid));

        $stucur = new curriculumstudent(array('id' => $associd));
        $stucur->delete();

        return true;
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
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        //TODO: validation

        //obtain the track id
        $trackid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $idnumber));

        //obtain the user id
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
        $userid = $DB->get_field(user::TABLE, 'id', $params);

        //create the association
        $usertrack = new usertrack(array('userid' => $userid,
                                         'trackid' => $trackid));
        $usertrack->save();

        return true;
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
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        //TODO: validation

        //obtain the track id
        $trackid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $idnumber));

        //obtain the user id
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
        $userid = $DB->get_field(user::TABLE, 'id', $params);

        //delete the association
        $usertrackid = $DB->get_field(usertrack::TABLE, 'id', array('userid' => $userid,
                                                                    'trackid' => $trackid));
        $usertrack = new usertrack($usertrackid);
        $usertrack->delete();

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
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        //TODO: validation

        //obtain the cluster / userset id
        $clusterid = $DB->get_field(userset::TABLE, 'id', array('name' => $name));

        //obtain the user id
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
        $userid = $DB->get_field(user::TABLE, 'id', $params);

        //create the association
        $clusterassignment = new clusterassignment(array('userid' => $userid,
                                                         'clusterid' => $clusterid,
                                                         'plugin' => 'manual',
                                                         'autoenrol' => 0));
        $clusterassignment->save();

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
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        //TODO: validation

        //obtain the cluster / userset id
        $clusterid = $DB->get_field(userset::TABLE, 'id', array('name' => $name));

        //obtain the user id
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
        $userid = $DB->get_field(user::TABLE, 'id', $params);

        //delete the association
        $clusterassignmentid = $DB->get_field(clusterassignment::TABLE, 'id', array('userid' => $userid,
                                                                                    'clusterid' => $clusterid,
                                                                                    'plugin' => 'manual'));
        $clusterassignment = new clusterassignment($clusterassignmentid);
        $clusterassignment->delete();

        return true;
    }

    /**
     * Hook run after a file header is read
     *
     * @param string $entity The type of entity
     * @param array $header The header record
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
            /*if (strpos($realcolumn, 'profile_field_') === 0) {
                $shortname = substr($realcolumn, strlen('profile_field_'));
                if ($result = $DB->get_record('user_info_field', array('shortname' => $shortname))) {
                    $this->fields[$shortname] = $result;
                } else {
                    $shortnames[] = "${shortname}";
                    $errors = true;
                }
            }*/
        }

        /*if ($errors) {
            $this->fslogger->log_failure("Import file contains the following invalid user profile field(s): " . implode(', ', $shortnames), 0, $filename, $this->linenumber);
            if (!$this->fslogger->get_logfile_status()) {
                return false;
            }
        }*/
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

        /*if (!defined('PHPUnit_MAIN_METHOD')) {
            //not in a unit test, so send out log files in a zip
            $logids = $this->dblogger->get_log_ids();
            rlip_send_log_emails('rlipimport_version1', $logids, $this->manual);
        }*/

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

}

