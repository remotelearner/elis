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
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
$file = get_plugin_directory('rlipfile', 'csv').'/csv.class.php';
require_once($file);
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_loguser extends rlip_importprovider_mock {

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

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logcourse extends rlip_importprovider_mock {

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

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logenrolment extends rlip_importprovider_mock {

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
        return parent::get_import_file($entity);
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
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    function get_filename($withpath = false) {
        return $this->filename;
    }
}

/**
 * Import provider that allow for multiple user records to be passed to the
 * import plugin
 */
class rlip_importprovider_multiuser extends rlip_importprovider_multi_mock {

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
        return parent::get_import_file($entity);
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

class rlip_importprovider_file extends rlip_importprovider {
    var $filename;

    function __construct($filename) {
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

        return rlip_fileplugin_factory::factory($this->filename);
    }
}

class rlip_importprovider_manual_delay
      extends rlip_importprovider_file_delay {

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

        //force MANUAL
        return new rlip_dblogger_import(true);
    }
}

/**
 * Class for testing database logging with the version 1 plugin
 */
class version1DatabaseLoggingTest extends rlip_test {
    protected $backupGlobalsBlacklist = array('DB', 'USER');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'backup_courses' => 'moodle',
            'backup_logs' => 'moodle',
            'block_instances' => 'moodle',
            'block_positions' => 'moodle',
            'cache_flags' => 'moodle',
            'cohort_members' => 'moodle',
            'config' => 'moodle',
            //this prevents createorupdate from being used
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'comments' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'course_completion_aggr_methd' => 'moodle',
            'course_completion_crit_compl' => 'moodle',
            'course_completion_criteria' => 'moodle',
            'course_completions' => 'moodle',
            'course_modules' => 'moodle',
            'course_modules_availability' => 'moodle',
            'course_modules_completion' => 'moodle',
            'course_sections' => 'moodle',
            'enrol' => 'moodle',
            'events_handlers' => 'moodle',
            'files' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'grade_categories' => 'moodle',
            'grade_categories_history' => 'moodle',
            'grade_items' => 'moodle',
            'grade_items_history' => 'moodle',
            'grade_letters' => 'moodle',
            'grade_outcomes_courses' => 'moodle',
            'grade_settings' => 'moodle',
            'groupings' => 'moodle',
            'groupings_groups' => 'moodle',
            'groups' => 'moodle',
            'groups_members' => 'moodle',
            'modules' => 'moodle',
            'rating' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_info_data' => 'moodle',
            'user_lastaccess' => 'moodle',
            'user_preferences' => 'moodle',
            'elis_scheduled_tasks' => 'elis_core',
            RLIP_LOG_TABLE => 'block_rlip',
            RLIP_SCHEDULE_TABLE => 'block_rlip',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
        );

        $manager = $DB->get_manager();

        // We are deleting a course, so we need to add a lot of plugin tables here
        $tables = array_merge($tables, self::load_plugin_xmldb('course/format'));

        if ($manager->table_exists('course_display')) {
            $tables['course_display'] = 'moodle';
        }

        if ($manager->table_exists('course_sections_availability')) {
            $tables['course_sections_availability'] = 'moodle';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array(
            'external_services_users' => 'moodle',
            'external_tokens' => 'moodle',
            'event' => 'moodle',
            //usually written to during course delete
            'grade_grades' => 'moodle',
            'grade_grades_history' => 'moodle',
            'log' => 'moodle',
            'sessions' => 'moodle'
        );
    }

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or NULL to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        if ($message === NULL) {
            $message = 'All lines from import file memoryfile were successfully processed.';
        }

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => $message);
        return $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
    }

    /**
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $this->assertEquals($exists, true);
    }


    /**
     * Run the user import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_user_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_loguser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the course import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_course_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_logcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the enrolment import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_enrolment_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_logenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
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
        $result = $this->run_user_import($data);
        $this->assertNull($result);

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
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'rlipfirstname2');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

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
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserDelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array('entity' => 'user',
                      'action' => 'delete',
                      'username' => 'rlipusername');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

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
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseCreate() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $prefix = self::$origdb->get_prefix();

        //set up the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the site course context
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
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseCreate() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'format' => 'bogusformat');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseUpdate() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        //prevent problem with cached contexts
        $UNITTEST = new stdClass;
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        $prefix = self::$origdb->get_prefix();

        //set up the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the site course context
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
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array('entity' => 'course',
                      'action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

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
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseDelete() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        $prefix = self::$origdb->get_prefix();

        //set up the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the site course context
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
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course delete
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseDelete() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

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

        $prefix = self::$origdb->get_prefix();

        //set up the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the site course context
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
        set_config('gradebookroles', "{$roleid}");

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

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
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

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
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up config values
        set_config('enrol_plugins_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $prefix = self::$origdb->get_prefix();

        //set up the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the site course context
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
        $syscontext = get_context_instance(CONTEXT_SYSTEM);

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array('entity' => 'enrolment',
                      'action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

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
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging includes the correct file name / path in the
     * success summary log message
     */
    public function testVersion1DBLoggingLogsCorrectFileNameOnSuccess() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

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
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file fileone were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);

        //prevent db conflicts
        $DB->delete_records(RLIP_LOG_TABLE);
        $DB->delete_records('user');

        $provider = new rlip_importprovider_loguser_dynamic($data, 'filetwo');

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file filetwo were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging object correctly persists values and resets its state
     * when flushing data to the DB
     */
    public function testVersion1DBLoggingSuccessTrackingStoresCorrectValuesViaAPI() {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set up the logger object
        $logger = new rlip_dblogger_import();

        //provide appropriate times
        $logger->set_plugin('plugin');
        $logger->set_targetstarttime(1000000000);
        $logger->set_starttime(1000000001);
        $logger->set_endtime(1000000002);

        //give it one of each "status"
        $logger->track_success(true, true);
        $logger->track_success(true, false);
        $logger->track_success(false, true);
        $logger->track_success(false, false);

        //specify number of db ops
        $logger->set_dbops(5);

        $logger->signal_unmetdependency();

        //validate setup
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 1000000001);
        $this->assertEquals($logger->endtime, 1000000002);
        $this->assertEquals($logger->filesuccesses, 1);
        $this->assertEquals($logger->filefailures, 1);
        $this->assertEquals($logger->storedsuccesses, 1);
        $this->assertEquals($logger->storedfailures, 1);
        $this->assertEquals($logger->dbops, 5);
        $this->assertEquals($logger->unmetdependency, 1);

        //flush
        $logger->flush('bogusfilename');

        //validate that the values were correctly persisted
        $params = array('plugin' => 'plugin',
                        'userid' => $USER->id,
                        'targetstarttime' => 1000000000,
                        'starttime' => 1000000001,
                        'endtime' => 1000000002,
                        'filesuccesses' => 1,
                        'filefailures' => 1,
                        'storedsuccesses' => 1,
                        'storedfailures' => 1,
                        'dbops' => 5,
                        'unmetdependency' => 1);
        $this->assert_record_exists(RLIP_LOG_TABLE, $params);

        //validate that the state is reset
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 0);
        $this->assertEquals($logger->endtime, 0);
        $this->assertEquals($logger->filesuccesses, 0);
        $this->assertEquals($logger->filefailures, 0);
        $this->assertEquals($logger->storedsuccesses, 0);
        $this->assertEquals($logger->storedfailures, 0);
        $this->assertEquals($logger->dbops, -1);
        $this->assertEquals($logger->unmetdependency, 0);
    }

    /**
     * Validate that correct values are stored after an actual run of a
     * "version 1" import
     */
    public function testVersion1DBLoggingStoresCorrectValuesOnRun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //capture the earliest possible start time
        $mintime = time();

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

        //run the import
        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        //capture the latest possible end time
        $maxtime = time();

        //validate that values were persisted correctly
        $select = "plugin = :plugin AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   starttime >= :minstarttime AND
                   starttime <= :maxstarttime AND
                   endtime >= :minendtime AND
                   endtime <= :maxendtime";
        $params = array('plugin' => 'rlipimport_version1',
                        'filesuccesses' => 1,
                        'filefailures' => 1,
                        'minstarttime' => $mintime,
                        'maxstarttime' => $maxtime,
                        'minendtime' => $mintime,
                        'maxendtime' => $maxtime);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that filenames are correctly stored when an import is run from
     * a file on the file system
     */
    public function testVersion1DBLoggingStoresCorrectFilenameOnRun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set the log file name to a fixed value
        $filepath = $CFG->dataroot;

        //set up a "user" import provider, using a single fixed file
        // MUST copy file to temp area 'cause it'll be deleted after import
        $testfile = dirname(__FILE__) .'/userfile.csv';
        $tempdir = $CFG->dataroot .'/block_rlip_phpunit/';
        $file = $tempdir .'userfile.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlip_importprovider_file($file, 'user');

        //run the import
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);

        // clean-up data file & tempdir
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that MANUAL import obeys maxruntime
     */
    public function testVersion1ManualImportObeysMaxRunTime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        //set the log file name to a fixed value
        $filepath = $CFG->dataroot;
        //set up a "user" import provider, using a single fixed file
        // MUST copy file to temp area 'cause it'll be deleted after import
        $testfile = dirname(__FILE__) .'/userfile2.csv';
        $tempdir = $CFG->dataroot .'/block_rlip_phpunit/';
        $file = $tempdir .'userfile2.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlip_importprovider_manual_delay($file, 'user');

        //run the import
        $importplugin = new rlip_importplugin_version1($provider, true);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // maxruntime 1 sec
        $ui = ob_get_contents();
        ob_end_clean();
        $this->assertNotNull($result);
        $expected_ui = "/.*generalbox.*Failed importing all lines from import file.*due to time limit exceeded.*/";
        $this->assertRegExp($expected_ui, $ui);

        // clean-up data file & tempdir
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that scheduled import obeys maxruntime
     */
    public function testVersion1ScheduledImportObeysMaxRunTime() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        //set the log file name to a fixed value
        $filepath = $CFG->dataroot;

        //set up a "user" import provider, using a single fixed file
        // MUST copy file to temp area 'cause it'll be deleted after import
        $testfile = dirname(__FILE__) .'/userfile2.csv';
        $tempdir = $CFG->dataroot .'/block_rlip_phpunit/';
        $file = $tempdir .'userfile2.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlip_importprovider_file_delay($file,'user');

        //run the import
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run(0, 0, 1); // maxruntime 1 sec
        $this->assertNotNull($result);
        if (!empty($result)) {
//            print_object($result);
            $this->assertFalse($result->result);
            $this->assertEquals($result->entity, 'user');
            $this->assertEquals($result->filelines, 4);
            $this->assertEquals($result->linenumber, 1);
        }

        //clean-up data file & test dir
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that import starts from saved state
     */
    public function testVersion1ImportFromSavedState() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set up the import file path & entities filenames
        // Note: schedule_files_path now relative to $CFG->dataroot
        //       must copy them there.
        $relimportpath = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot . $relimportpath;
        set_config('schedule_files_path', $relimportpath, 'rlipimport_version1');
        set_config('user_schedule_file', 'userfile2.csv', 'rlipimport_version1');
        set_config('course_schedule_file', 'course.csv', 'rlipimport_version1');
        set_config('enrolment_schedule_file', 'enroll.csv', 'rlipimport_version1');
        @copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'userfile2.csv',
              $testdir . 'userfile2.csv');
        @copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'course.csv',
              $testdir . 'course.csv');
        @copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'enroll.csv',
              $testdir . 'enroll.csv');

        //create a scheduled job
        $data = array('plugin' => 'rlipimport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipimport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('plugin' => 'rlipimport_version1'));
        $job->nextruntime = 99;
        $state = new stdClass;
        $state->result = false;
        $state->entity = 'user';
        $state->filelines = 4;
        $state->linenumber = 3; // Should start at line 3 of userfile2.csv
        $ipjobdata = unserialize($job->config);
        $ipjobdata['state'] = $state;
        $job->config = serialize($ipjobdata);
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // MUST copy the userfile2.csv file to process into temp location
        // ... where it would be left if state != NULL
        $temppath = sprintf($CFG->dataroot . RLIP_IMPORT_TEMPDIR,
                            'rlipimport_version1');
        @copy(dirname(__FILE__) .'/userfile2.csv', $temppath .'/userfile2.csv');

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);
        // verify the 1st & 2nd lines were NOT processed
        $notexists1 = $DB->record_exists('user', array('username' => 'testusername'));
        $this->assertFalse($notexists1);
        $notexists2 = $DB->record_exists('user', array('username' => 'testusername2'));
        $this->assertFalse($notexists2);
        $exists = $DB->record_exists('user', array('username' => 'testusername3'));
        $this->assertTrue($exists);

        // clean-up data files
        @unlink($testdir . 'userfile2.csv');
        @unlink($testdir . 'course.csv');
        @unlink($testdir . 'enroll.csv');
        // TBD: @rmdir($testdir);
    }

    /**
     * Validate that filenames are correctly stored when an import is run
     * based on a Moodle file-system file
     */
    public function testVersion1DBLoggingStoresCorrectFilenameOnRunWithMoodleFile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');

        //set the filepath to a fixed value
        $filepath = $CFG->dataroot;

        //store it at the system context
        $context = get_context_instance(CONTEXT_SYSTEM);

        //file path and name
        $file_path = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/';
        $file_name = 'userfile.csv';

        //file information
        $fileinfo = array('contextid' => $context->id,
                          'component' => 'system',
                          'filearea'  => 'draft',
                          'itemid'    => 9999,
                          'filepath'  => $file_path,
                          'filename'  => $file_name
                    );

        //create a file in the Moodle file system with the right content
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, "{$file_path}{$file_name}");
        $fileid = $DB->get_field_select('files', 'id', "filename != '.'");
        //run the import
        $entity_types = array('user', 'bogus', 'bogus');
        $fileids = array($fileid, false, false);
        $provider = new rlip_importprovider_moodlefile($entity_types, $fileids);
        $importplugin = new rlip_importplugin_version1($provider);

        //buffer output due to summary display
        ob_start();
        $result = $importplugin->run();
        ob_end_clean();
        $this->assertNull($result);

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);
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
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging records the correct number of successes and
     * failues from import file
     */
    public function testVersion1DBLoggingLogsCorrectCountsForManualImport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

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
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('filesuccesses' => 1,
                                                           'filefailures' => 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate import accepts full country name in addition to country code
     */
    public function testVersion1AcceptsFullCountryOnImport() {
        global $DB;

        $data = array('entity'    => 'user',
                      'action'    => 'create',
                      'username'  => 'rlipusername',
                      'password'  => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname'  => 'rliplastname',
                      'email'     => 'rlipuser@rlipdomain.com',
                      'city'      => 'rlipcity',
                      'country'   => 'Canada'
                );

        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $newuser = $DB->get_record('user', array('username' => $data['username']));
        $this->assertFalse(empty($newuser));
        $this->assertTrue($newuser->country == 'CA');
    }

    /**
     * Validate that DB logging stores the current user id when processing
     * import files
     */
    public function testVersion1DBLoggingLogsCorrectUseridForManualImport() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

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
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('userid' => $USER->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validates the standard failure message
     */
    public function testVersion1DBLoggingLogsFailureMessage() {
        set_config('createorupdate', 0, 'rlipimport_version1');

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $message = 'One or more lines from import file memoryfile failed because they contain data errors. '.
                   'Please fix the import file and re-upload it.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that database logging works as specified for scheduled import
     * tasks
     */
    public function testVersion1DBLoggingSetsAllFieldsDuringScheduledImportRun() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set the file path to a fixed value
        $filepath = $CFG->dataroot;

        //store it at the system context
        $context = get_context_instance(CONTEXT_SYSTEM);

        //file path and name
        $file_name = 'userscheduledimport.csv';
        // File WILL BE DELETED after import so must copy to moodledata area
        // Note: file_path now relative to moodledata ($CFG->dataroot)
        $file_path = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir . $file_name);

        //create a scheduled job
        $data = array('plugin' => 'rlipimport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipimport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipimport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //lower bound on starttime
        $starttime = time();

        //set up config for plugin so the scheduler knows about our csv file
        set_config('schedule_files_path', $file_path, 'rlipimport_version1');
        set_config('user_schedule_file', $file_name, 'rlipimport_version1');
        //set_config('course_schedule_file', 'course.csv', 'rlipimport_version1');
        //set_config('enrolment_schedule_file', 'enroll.csv', 'rlipimport_version1');
        //set_config('type', 'user', 'rlipimport_version1');

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        $message = 'One or more lines from import file userscheduledimport.csv failed because they contain data errors. '.
                   'Please fix the import file and re-upload it.';

        //upper bound on endtime
        $endtime = time();

        //condition for the logpath
        $like = $DB->sql_like('logpath', ':logpath');

        //data validation
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   targetstarttime = :targetstarttime AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   endtime >= starttime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency AND
                   {$like} AND
                   entitytype = :entitytype";
        $params = array('export' => 0,
                        'plugin' => 'rlipimport_version1',
                        'userid' => $USER->id,
                        'targetstarttime' => 99,
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'filesuccesses' => 2,
                        'filefailures' => 2,
                        'storedsuccesses' => 0,
                        'storedfailures' => 0,
                        'statusmessage' => $message,
                        'dbops' => -1,
                        'unmetdependency' => 0,
                        'logpath' => "{$CFG->dataroot}/%",
                        'entitytype' => 'user');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);
        // Verify completed import deletes input csv file
        $this->assertFalse(file_exists($testdir . $file_name));

        //clean-up data file & test dir
        @unlink($testdir . $file_name);
        @rmdir($testdir);
    }

    /**
     * Validation for log end times
     */

    /**
     * Validate that summary log end time is set when an invalid folder is set
     * for the file system log
     */
    public function testNonWritableLogPathLogsCorrectEndTime() {
        global $DB;

        set_config('logfilelocation', 'adirectorythatshouldnotexist', 'rlipimport_version1');

        $data = array(
            'action'    => 'create',
            'username'  => 'testuserusername',
            'password'  => 'Testpassword!0',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'city'      => 'testcity',
            'country'   => 'CA'
        );

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when the action column is not
     * specified in the import
     */
    public function testMissingActionColumnLogsCorrectEndTime() {
        global $DB;

        $data = array('idnumber' => 'testuseridnumber');

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when a required column is not
     * specified in the import
     */
    public function testMissingRequiredColumnLogsCorrectEndTime() {
        global $DB;

        $data = array('action' => 'create');

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when maximum runtime is exceeded
     * when running the import
     */
    public function testMaxRuntimeExceededLogsCorrectEndTime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');

        $import_file = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/userfiledelay.csv';
        $provider = new rlip_importprovider_file_delay($import_file, 'user');

        //run the import
        $mintime = time();
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run(0, 0, 1);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when successfully processing an
     * import file
     */
    public function testSuccessfulProcessingLogsCorrectEndTime() {
        global $DB;

        $data = array(
            'action'    => 'create',
            'username'  => 'testuserusername',
            'password'  => 'Testpassword!0',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'city'      => 'testcity',
            'country'   => 'CA'
        );

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }
}
