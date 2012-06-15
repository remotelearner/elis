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

    static $import_fields_course_create = array('idnumber', 'name');
    static $import_fields_course_update = array('idnumber');
    static $import_fields_course_delete = array('idnumber');

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
        $user = new user(array('idnumber'   => $record->idnumber,
                               'username'   => $record->username,
                               'firstname'  => $record->firstname,
                               'lastname'   => $record->lastname,
                               'email'      => $record->email,
                               'country'    => $record->country));

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
        $course = new course($data);
        $course->save();

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

