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

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_loguser extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logcourse extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logenrolment extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * File plugin that reads from memory and reports a dynamic filename
 */
class rlip_fileplugin_readmemory_dynamic extends rlip_fileplugin_readmemory {

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     * @param string $filename The name of the file to report
     */
    function __construct($rows, $filename) {
        parent::__construct($rows);
        $this->filename = $filename;
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        return $this->filename;
    }
}

/**
 * Import provider that allow for multiple user records to be passed to the
 * import plugin
 */
class rlip_importprovider_multiuser extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        //sort out the header
        $rows = array();
        $rows[] = array();
        $datum = reset($this->data);
        foreach (array_keys($datum) as $key) {
            $rows[0][] = $key;
        }

        //iterate through each user
        foreach ($this->data as $datum) {
            $index = count($rows);

            //turn an associative array into rows of data
            $rows[] = array();
            foreach (array_values($datum) as $value) {
                $rows[$index][] = $value;
            }
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Import provider that allows for user processing and specifies a dynamic
 * filename to the file plugin
 */
class rlip_importprovider_loguser_dynamic extends rlip_importprovider_loguser {
    var $data;
    var $filename;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     * @param string $filename The name of the file to report
     */
    function __construct($data, $filename) {
        $this->data = $data;
        $this->filename = $filename;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory_dynamic($rows, $this->filename);
    }
}

/**
 * Class for testing database logging with the version 1 plugin
 */
class version1DatabaseLoggingTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB', 'USER');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('block_rlip_summary_log' => 'block_rlip',
                     'user' => 'moodle',
                     'context' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'cohort_members' => 'moodle',
                     'groups_members' => 'moodle',
                     'user_preferences' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'block_instances' => 'moodle',
                     'block_positions' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'role_assignments' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'cache_flags' => 'moodle',
                     'events_handlers' => 'moodle',
                     'course_categories' => 'moodle',
                     'course' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'course_completion_criteria' => 'moodle',
                     'course_completion_aggr_methd' => 'moodle',
                     'course_completions' => 'moodle',
                     'course_completion_crit_compl' => 'moodle',
                     'grade_categories' => 'moodle',
                     'grade_categories_history' => 'moodle',
                     'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_settings' => 'moodle',
                     'grade_letters' => 'moodle',
                     'course_modules_completion' => 'moodle',
                     'course_modules' => 'moodle',
                     'course_modules_availability' => 'moodle',
                     'modules' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'groups' => 'moodle',
                     'course_display' => 'moodle',
                     'backup_courses' => 'moodle',
                     'backup_log' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('log' => 'moodle',
                     'event' => 'moodle');
    }

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or NULL to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = NULL) {
        global $DB;

        if ($message === NULL) {
            $message = 'All lines from import file memoryfile were successfully processed.';
        }

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => $message);
        return $DB->record_exists_select('block_rlip_summary_log', $select, $params);
    }

    /**
     * Run the user import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_user_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_loguser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Run the course import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_course_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_logcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Run the enrolment import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_enrolment_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_logenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserCreate() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserCreate() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'boguscountry');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserUpdate() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->run_user_import($data);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'rlipfirstname2');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user update
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserUpdate() {
        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'rlipfirstname2');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserDelete() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->run_user_import($data);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'user',
                      'action' => 'delete',
                      'username' => 'rlipusername');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user delete
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserDelete() {
        $data = array('entity' => 'user',
                      'action' => 'delete',
                      'username' => 'rlipusername');
        $this->run_user_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'format' => 'bogusformat');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseUpdate() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $this->run_course_import($data);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'course',
                      'action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course update
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseUpdate() {
        global $CFG;

        $data = array('entity' => 'course',
                      'action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseDelete() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $this->run_course_import($data);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course delete
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseDelete() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $this->run_course_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnEnrolmentCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_enrolment_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedEnrolmentCreate() {
        global $CFG;

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_enrolment_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnEnrolmentDelete() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/user/lib.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_enrolment_import($data);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'enrolment',
                      'action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_enrolment_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment delete
     */
    public function tesVersion1tDBLoggingDoesNotLogSuccessMessageOnFailedEnrolmentDelete() {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $data = array('entity' => 'enrolment',
                      'action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_enrolment_import($data);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging includes the correct file name / path in the
     * success summary log message
     */
    public function testVersion1DBLoggingLogsCorrectFileNameOnSuccess() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $provider = new rlip_importprovider_loguser_dynamic($data, 'fileone');

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        $message = 'All lines from import file fileone were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');
        $DB->delete_records('user');

        $provider = new rlip_importprovider_loguser_dynamic($data, 'filetwo');

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        $message = 'All lines from import file filetwo were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging object correctly resets its state when flushing
     * data to the DB
     */
    public function testVersion1DBLoggingSuccessTrackingStoresCorrectValues() {
        //set up the logger object
        $logger = new rlip_dblogger();

        //give it one of each "status"
        $logger->track_success(true, true);
        $logger->track_success(true, false);
        $logger->track_success(false, true);
        $logger->track_success(false, false);

        //validate setup
        $this->assertEquals($logger->filesuccesses, 1);
        $this->assertEquals($logger->filefailures, 1);
        $this->assertEquals($logger->storedsuccesses, 1);
        $this->assertEquals($logger->storedfailures, 1);

        //flush
        $logger->flush('bogusfilename');

        //validate that the state is reset
        $this->assertEquals($logger->filesuccesses, 0);
        $this->assertEquals($logger->filefailures, 0);
        $this->assertEquals($logger->storedsuccesses, 0);
        $this->assertEquals($logger->storedfailures, 0);

        //todo: add other fields
    }

    /**
     * Validate that DB logging does not log a success message when a mixtures
     * of successes and failures is encountered
     */
    public function testVersion1DBLoggingDoesNotLogSuccessOnMixedResults() {
        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername2',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname2',
                            'lastname' => 'rliplastname2',
                            'email' => 'rlipuse2r@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'));

        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging records the correct number of successes and
     * failues from import file
     */
    public function tesVersion1tDBLoggingLogsCorrectCountsForManualImport() {
        global $DB;

        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername2',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname2',
                            'lastname' => 'rliplastnam2e',
                            'email' => 'rlipuser2@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername3',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname3',
                            'lastname' => 'rliplastname3',
                            'email' => 'rlipuser3@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'));

        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        $exists = $DB->record_exists('block_rlip_summary_log', array('filesuccesses' => 1,
                                                                     'filefailures' => 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging stores the current user id when processing
     * import files
     */
    public function testVersion1DBLoggingLogsCorrectUseridForManualImport() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $USER->id = 9999;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->run_user_import($data);

        $exists = $DB->record_exists('block_rlip_summary_log', array('userid' => $USER->id));
        $this->assertEquals($exists, true);
    }

}