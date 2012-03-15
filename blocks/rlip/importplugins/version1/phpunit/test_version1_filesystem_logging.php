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
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/lib/phpunittestlib/testlib.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');

class rlip_fileplugin_readmemorywithname extends rlip_fileplugin_base {
    //current file position
    var $index;
    //file data
    var $data;

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     */
    function __construct($data, $filename = '', $fileid = NULL, $sendtobrowser = false) {
        parent::__construct($filename, $fileid, $sendtobrowser);

        $this->index = 0;
        $this->data = $data;
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
        //nothing to do
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        if ($this->index < count($this->data)) {
            //more lines to read, fetch next one
            $result = $this->data[$this->index];
            //move "line pointer"
            $this->index++;
            return $result;
        }

        //out of lines
        return false;
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        //nothing to do
    }

    /**
     * Close the file
     */
    function close() {
        //nothing to do
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        //physical file, so obtain filename from full path
        $parts = explode('/', $this->filename);
        $count = count($parts);
        return $parts[$count - 1];
    }
}

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_fsloguser extends rlip_importprovider {
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

        return new rlip_fileplugin_readmemorywithname($rows, 'user.csv');
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_fslogcourse extends rlip_importprovider {
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

        return new rlip_fileplugin_readmemorywithname($rows, 'course.csv');
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_fslogenrolment extends rlip_importprovider {
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

        return new rlip_fileplugin_readmemorywithname($rows, 'enrolment.csv');
    }
}

/**
 * Overlay database that allows for the handling of temporary tables as well
 * as some course-specific optimizations
 */
class overlay_course_database_fs extends overlay_database {

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool true
     * @throws dml_exception if error
     */
    public function change_database_structure($sql) {
        if (strpos($sql, 'CREATE TEMPORARY TABLE ') === 0) {
            //creating a temporary table, so make it an overlay table

            //find the table name
            $start_pos = strlen('CREATE TEMPORARY TABLE ');
            $length = strpos($sql, '(') - $start_pos;
            $tablename = trim(substr($sql, $start_pos, $length));
            //don't use prefix when storing
            $tablename = substr($tablename, strlen($this->overlayprefix));

            //set it up as an overlay table
            $this->overlaytables[$tablename] = 'moodle';
            $this->pattern = '/{('.implode('|', array_keys($this->overlaytables)).')}/';
        }

        // FIXME: or should we just do nothing?
        return $this->basedb->change_database_structure($sql);
    }

    /**
     * Returns detailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        //determine if this is an overlay table
        $is_overlay_table = array_key_exists($table, $this->overlaytables);

        if ($is_overlay_table) {
            //temporarily set the prefix to the overlay prefix
            $cacheprefix = $this->basedb->prefix;
            $this->basedb->prefix = $this->overlayprefix; // HACK!!!
        }

        $result = $this->basedb->get_columns($table, $usecache);

        if ($is_overlay_table) {
            //restore proper prefix
            $this->basedb->prefix = $cacheprefix;
        }

        return $result;
    }

    /**
     * Clean up the temporary tables.  You'd think that if this method was
     * called dispose, then the cleanup would happen automatically, but it
     * doesn't.
     */
    public function cleanup() {
        $manager = $this->get_manager();
        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);
            // FIXME: when http://bugs.mysql.com/bug.php?id=10327 gets fixed,
            // we can switch this back to drop_temp_table
            if ($table === null) {
                //most likely a temporary table
                try {
                    //attempt to drop the temporary table
                    $table = new xmldb_table($tablename);
                    $manager->drop_temp_table($table);
                } catch (Exception $e) {
                    //temporary table was already dropped
                }
            } else {
                //structure was defined in xml, so drop normal table
                $manager->drop_table($table);
            }
        }
    }

    /**
     * Empty out all the overlay tables.
     */
    public function reset_overlay_tables() {
        $manager = $this->get_manager();

        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);

            if ($table === null) {
                $table = new xmldb_table($tablename);
                try {
                    $manager->drop_temp_table($table);
                } catch (Exception $e) {

                }
                unset($this->overlaytables[$tablename]);
            }
        }

        parent::reset_overlay_tables();
    }

    /**
     * Empty out all the overlay tables.
     */
    /*
    public function reset_overlay_tables() {
        //do nothing
    }
    */
}

class version1FilesystemLoggingTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        return array('block_rlip_summary_log' => 'block_rlip',
                     'user' => 'moodle',
                     'config_plugins' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'groups_members' => 'moodle',
                     'block_positions' => 'moodle',
                     'events_queue_handlers' => 'moodle',
                     'events_queue' => 'moodle',
                     'grade_categories' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'groups' => 'moodle',
                     'grade_items' => 'moodle',
                     'context' => 'moodle',
                     'config' => 'moodle',
                     'backup_controllers' => 'moodle',
                     'backup_courses' => 'moodle',
                     'enrol' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'log' => 'moodle',
                     'cohort_members' => 'moodle',
                     'user_preferences' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'files' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'course_completion_criteria' => 'moodle',
                     'course_completion_aggr_methd' => 'moodle',
                     'course_completions' => 'moodle',
                     'course_completion_crit_compl' => 'moodle',
                     'grade_categories_history' => 'moodle',
                     //'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_settings' => 'moodle',
                     'grade_letters' => 'moodle',
                     'course_modules_completion' => 'moodle',
                     'course_modules_availability' => 'moodle',
                     'feedback_items' => 'moodle',
                     'feedback_template' => 'moodle',
                     'course_modules' => 'moodle',
                     'event' => 'moodle',
                     'course_display' => 'moodle',
                     'backup_log' => 'moodle',
                     'external_tokens' => 'moodle',
                     'forum' => 'mod_forum',
                     'forum_subscriptions' => 'mod_forum',
                     'forum_read' => 'mod_forum',
                     'external_services_users' => 'moodle');
    }

    protected $backupGlobalsBlacklist = array('DB');

    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_course_database_fs($DB, static::get_overlay_tables(), static::get_ignored_tables());
        //self::$overlaydb = new overlay_database($DB, static::get_overlay_tables(), static::get_ignored_tables());
    }

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array $data The import data to process
     * @param string $expected_error The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expected_error, $entitytype) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');

        //cleanup from previous run
        $filename = $CFG->dataroot.'/rliptestfile.log';
        if (file_exists($filename)) {
            unlink($filename);
        }

        //set the log file name to a fixed value
        set_config('logfilelocation', $filename, 'rlipimport_version1');

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        //validate that a log file was created
        $this->assertTrue(file_exists($filename));

        //fetch log line
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            //no line found
            $this->assertEquals(0, 1);
        }

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);
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
     * Creates a test user
     *
     * @param string $username The user's username
     * @param string $email The user's email
     * @param string $idnumber The user's idnumber
     *
     * @return int The database record id of the created user
     */
    private function create_test_user($username = 'rlipusername', $email = 'rlipuser@rlipdomain.com',
                                      $idnumber = 'rlipidnumber') {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = $username;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->email = $email;
        $user->password = 'Rlippassword!1234';
        $user->idnumber = $idnumber;

        return user_create_user($user);
    }

    /**
     * Creates a test course, including the category it belongs to
     *
     * @return int The database record id of the created course
     */
    private function create_test_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //create the category
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //create the course
        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $categoryid;

        $course = create_course($course);
        get_context_instance(CONTEXT_COURSE, $course->id);

        return $course->id;
    }

    /**
     * Creates a test role, assignable at all necessary context levels
     *
     * @return int The database record id of the created course
     */
    private function create_test_role() {
        //create the role
        $roleid = create_role('rlipfullshortname', 'rlipshortname', 'rlipdescription');

        //make it assignable at all necessary contexts
        $contexts = array(CONTEXT_COURSE,
                          CONTEXT_COURSECAT,
                          CONTEXT_USER,
                          CONTEXT_SYSTEM);
        set_role_contextlevels($roleid, $contexts);

        return $roleid;
    }

    /**
     * Creates the system and site course context, as well as the site course
     * record
     */
    private function create_contexts_and_site_course() {
        global $DB;

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }
    }

    /**
     * Validate that version 1 import plugin instances are set up with file-system
     * loggers
     */
    public function testVersion1ImportInstanceHasFsLogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fslogger.class.php');

        //set up the plugin
        $provider = new rlip_importprovider_fsloguser(array());
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);

        //validation
        $fslogger = $instance->get_fslogger();
        $this->assertEquals($fslogger instanceof rlip_fslogger, true);
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyUserAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[user.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user create
     */
    public function testVersionImportLogsEmptyUserUsernameOnCreate() {
        //validation for an empty username field
        $data = array('action' => 'create',
                      'username' => '',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"username\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty password field on user create
     */
    public function testVersion1ImportLogsEmptyUserPasswordOnCreate() {
        //validation for an empty password field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => '',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"password\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty firstname field on user create
     */
    public function testVersion1ImportLogsEmptyUserFirstnameOnCreate() {
        //validation for an empty firstname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => '',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"firstname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty lastname field on user create
     */
    public function testVersion1ImportLogsEmptyUserLastnameOnCreate() {
        //validation for an empty lastname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => '',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"lastname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty email field on user create
     */
    public function testVersion1ImportLogsEmptyUserEmailOnCreate() {
        //validation for an empty email field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => '',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"email\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty city field on user create
     */
    public function testVersion1ImportLogsEmptyUserCityOnCreate() {
        //validation for an empty city field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => '',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"city\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty country field on user create
     */
    public function testVersion1ImportLogsEmptyUserCountryOnCreate() {
        //validation for an empty country field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => '');
        $expected_error = "[user.csv line 2] Required field \"country\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user update
     */
    public function testVersionImportLogsEmptyUserUsernameOnUpdate() {
        //validation for an empty username field
        $data = array('action' => 'update',
                      'username' => '');
        $expected_error = "[user.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user delete
     */
    public function testVersionImportLogsEmptyUserUsernameOnDelete() {
        //validation for an empty username field
        $data = array('action' => 'update',
                      'username' => '');
        $expected_error = "[user.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyCourseAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[course.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnCreate() {
        //validation for an empty shortname field
        $data = array('action' => 'create',
                      'shortname' => '',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty fullname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseFullnameOnCreate() {
        //validation for an empty fullname field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => '',
                      'category' => 'rlipcategory');
        $expected_error = "[course.csv line 2] Required field \"fullname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty category field on course create
     */
    public function testVersion1ImportLogsEmptyCourseCategoryOnCreate() {
        //validation for an empty category field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => '');
        $expected_error = "[course.csv line 2] Required field \"category\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course update
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnUpdate() {
        //validation for an empty shortname field
        $data = array('action' => 'update',
                      'shortname' => '');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course delete
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnDelete() {
        //validation for an empty shortname field
        $data = array('action' => 'delete',
                      'shortname' => '');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty enrolment action field
     */
    public function testVersion1ImportLogsEmptyEnrolmentAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnCreate() {
        //validation for an empty username field
        $data = array('action' => 'create',
                      'username' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnCreate() {
        //validation for an empty context field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"context\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnCreate() {
        //validation for an empty instance field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => '',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"instance\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnCreate() {
        //validation for an empty role field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"role\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnDelete() {
        //validation fo an empty username field
        $data = array('action' => 'delete',
                      'username' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnDelete() {
        //validation for an empty context field
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"context\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnDelete() {
        //validation for an empty instance field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => '',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"instance\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnDelete() {
        //validation for an empty role field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"role\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged when multiple required fields are empty
     */
    public function testVersion1ImportLogsMultipleEmptyFields() {
        //validation for three empty required fields
        $data = array('action' => 'create',
                      'shortname' => '',
                      'fullname' => '',
                      'category' => '');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an appropriate error is logged for a scenario with missing required fields,
     * some of which are only required in a "1 of n"-fashion
     */
    public function testVersion1ImportLogsMultipleMissingFieldsWithOption() {
        //validation for "1 of 3", plus 3 required fields
        $data = array('action' => 'create');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty. Required fields \"context\", \"instance\", \"role\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged for a field that is not in
     * the import file
     */
    public function testVersion1ImportLogsMissingField() {
        //validation for unspecified field "shortname"
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an appropriate error is logged for multiple missing
     * fields that are absolutely necessary
     */
    public function testVersion1ImportLogsMultipleMissingFields() {
        //validation for missing fields shortname, fullname, category
        $data = array('action' => 'create');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that error logging works correctly with the user "create or update" functionality
     */
    public function testVersion1ImportLoggingSupportsUserCreateOrUpdate() {
        global $CFG;

        set_config('createorupdate', 1, 'rlipimport_version1');

        //create validation using update
        $data = array('action' => 'update');
        $expected_error = "[user.csv line 2] Required fields \"username\", \"password\", \"firstname\", \"lastname\", \"email\", \"city\", \"country\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        //actually create using update
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        $this->assert_record_exists('user', $data);

        //update validation using create
        $data = array('action' => 'create');
        $expected_error = "[user.csv line 2] Required fields \"username\", \"password\", \"firstname\", \"lastname\", \"email\", \"city\", \"country\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        //actually update using create
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'firstname' => 'updatedrlipfirstname');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        $data = array('username' => 'rlipusername',
                      'mnethostid' => $CFG->mnet_localhost_id,
                      'password' => hash_internal_user_password('Rlippassword!0'),
                      'firstname' => 'updatedrlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validates that error logging works correctly with the course "create or update" functionality
     */
    public function testVersion1ImportLoggingSupportsCourseCreateOrUpdate() {
        global $CFG, $DB;

        set_config('createorupdate', 1, 'rlipimport_version1');

        //set up the site course record
        $this->create_contexts_and_site_course();

        //create validation using update
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //actually create using update
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        unset($data['action']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $this->assert_record_exists('course', $data);

        //update validation using create
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //actually update using create
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedrlipfullname');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        $data = array('shortname' => 'rlipshortname',
                      'fullname' => 'updatedrlipfullname',
                      'category' => $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory')));
        $this->assert_record_exists('course', $data);
    }

    /**
     * Validates success message for the user create action
     */
    public function testVersion1ImportLogsSuccessfulUserCreate() {
        global $DB;

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully created.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        $DB->delete_records('user', array('username' => 'rlipusername'));

        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully created.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');
    }

    /**
     * Validates success message for the user update action
     */
    public function testVersion1ImportLogsSuccesfulUserUpdate() {
        $this->create_test_user();

        //base data used every time
        $basedata = array('action' => 'update');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with idnumber \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');
    }

    /**
     * Validates success message for the user delete action
     */
    public function testVersion1ImportLogsSuccesfulUserDelete() {
        //base data used every time
        $basedata = array('action' => 'delete');

        //username
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //email
        $this->create_test_user();
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //idnumber
        $this->create_test_user();
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with idnumber \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, email
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, idnumber
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //email, idnumber
        $this->create_test_user();
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        //username, email, idnumber
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');
    }

    /**
     * Validates success message for the course create action
     */
    public function testVersion1ImportLogsSuccesfulCourseCreate() {
        $this->create_contexts_and_site_course();

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $expected_message = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully created.\n";
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    /**
     * Validates success message for the course create action, when creating
     * from a template course
     */
    public function testVersion1ImportLogsSuccesfulCourseCreateFromTemplate() {
        global $USER;

        $this->create_contexts_and_site_course();

        $USER->id = $this->create_test_user();
        set_config('siteadmins', $USER->id);
        set_config('siteguest', 99999);
        $this->create_test_course();

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname2',
                      'fullname' => 'rlipfullname2',
                      'category' => 'rlipcategory',
                      'link' => 'rlipshortname');
        $expected_message = "[course.csv line 2] Course with shortname \"rlipshortname2\" successfully created from template course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    /**
     * Validates success message for the course update action
     */
    public function testVersion1ImportLogsSuccesfulCourseUpdate() {
        $this->create_contexts_and_site_course();

        $this->create_test_course();

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $expected_message = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    /**
     * Validates success message for the course delete action
     */
    public function testVersion1ImportLogsSuccesfulCourseDelete() {
        $this->create_contexts_and_site_course();

        $this->create_test_course();

        $data = array('action' => 'delete',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $expected_message = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    /**
     * Validates success message for the role assignment create action on courses
     */
    public function testVersion1ImportLogsSuccesfulCourseRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for course enrolment creation
     */
    public function testVersion1ImportLogsSuccesfulCourseEnrolmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //make sure they already have a role assignment
        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
    }

    /**
     * Validates success message for course enrolment and role assignment creation
     * (at the same time)
     */
    public function testVersion1ImportLogsSuccesfulCourseEnrolmentAndRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');
    }

    /**
     * Validates success message for assigning users to groups during course
     * enrolment creation
     */
    public function testVersion1ImportLogsSuccessfulGroupAssignment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //make sure they already have a role assignment
        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'group' => 'rlipname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');
    }

    /**
     * Validates success message for creating a group and assigning a user to
     * it during course enrolment creation
     */
    public function testVersion1ImportLogsSuccessfulGroupCreationAndAssignment() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //make sure they already have a role assignment
        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'group' => 'rlipname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
    }

    /**
     * Validates success message for creating a group and grouping, assigning
     * a user to the group and the group to the grouping during course
     * enrolment creation
     */
    public function testVersion1ImportLogsSuccessfulGroupAndGroupingCreationAndAssignment() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //make sure they already have a role assignment
        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'group' => 'rlipname',
                          'grouping' => 'rlipname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');
    }

    /**
     * Validates success message for creating a group, assigning a user to the
     * group and the group to the grouping during course enrolment creation
     */
    public function testVersion1ImportLogsSuccessfulGroupCreationAndGroupingAssignment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $grouping = new stdClass;
        $grouping->courseid = $courseid;
        $grouping->name = 'rlipname';
        groups_create_grouping($grouping);

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //make sure they already have a role assignment
        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'group' => 'rlipname',
                          'grouping' => 'rlipname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');
    }

    /**
     * Validates success message for the role assignment create action on course categories
     */
    public function testVersion1ImportLogsSuccesfulCategoryRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'coursecat',
                          'instance' => 'rlipname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment create action on users
     */
    public function testVersion1ImportLogsSuccesfulUserRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'user',
                          'instance' => 'rlipusername2',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment create action on the system context
     */
    public function testVersion1ImportLogsSuccesfulSystemRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment delete action on courses
     */
    public function testVersion1ImportLogsSuccesfulCourseRoleAssignmentDelete() {
        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates success message for course enrolment deletion
     */
    public function testVersion1ImportLogsSuccesfulCourseEnrolmentDelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates success message for course enrolment and role assignment
     * deletion (at the same time)
     */
    public function testVersion1ImportLogsSuccessfulCourseEnrolmentAndRoleAssignmentDelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on course categories
     */
    public function testVersion1ImportLogsSuccesfulCategoryRoleAssignmentDelete() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $this->create_test_course();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipname'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'coursecat',
                          'instance' => 'rlipname',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on users
     */
    public function testVersion1ImportLogsSuccesfulUserRoleAssignmentDelete() {
        //set up dependencies
        $userid = $this->create_test_user();
        $seconduserid = $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $context = get_context_instance(CONTEXT_USER, $seconduserid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'user',
                          'instance' => 'rlipusername2',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on the system context
     */
    public function testVersion1ImportLogsSuccesfulSystemRoleAssignmentDelete() {
        //set up dependencies
        $userid = $this->create_test_user();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

/**
     * Data provider function for invalid user info in role assignments
     *
     * @return array The array of data to use in test cases
     */
    public function roleAssignmentInvalidUserProvider() {
        $data = array();

        //invalid username
        $username_data = array('username' => 'bogus');
        $username_message = "[enrolment.csv line 2] \"username\" value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($username_data, $username_message);

        //invalid email
        $email_data = array('email' => 'bogus@bogus.com');
        $email_message = "[enrolment.csv line 2] \"email\" value of \"bogus@bogus.com\" does not refer to a valid user.\n";
        $data[] = array($email_data, $email_message);

        //invalid idnumber
        $idnumber_data = array('idnumber' => 'bogus');
        $idnumber_message = "[enrolment.csv line 2] \"idnumber\" value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($idnumber_data, $idnumber_message);

        //invalid combination of username, email
        $username_email_data = array('username' => 'bogus',
                                     'email' => 'bogus@bogus.com');
        $username_email_message = "[enrolment.csv line 2] \"username\" value of \"bogus\", \"email\" value of \"bogus@bogus.com\" do not refer to a valid user.\n";
        $data[] = array($idnumber_data, $idnumber_message);

        //invalid combination of username, idnumber
        $username_idnumber_data = array('username' => 'bogus',
                                        'idnumber' => 'bogus');
        $username_idnumber_message = "[enrolment.csv line 2] \"username\" value of \"bogus\", \"idnumber\" value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($username_idnumber_data, $username_idnumber_message);

        //invalid combination of email, idnumber
        $email_idnumber_data = array('email' => 'bogus@bogus.com',
                                     'idnumber' => 'bogus');
        $email_idnumber_message = "[enrolment.csv line 2] \"email\" value of \"bogus@bogus.com\", \"idnumber\" value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($email_idnumber_data, $email_idnumber_message);

        //invalid combination of username, email, idnumber
        $all_fields_data = array('username' => 'bogus',
                                 'email' => 'bogus@bogus.com',
                                 'idnumber' => 'bogus');
        $all_fields_message = "[enrolment.csv line 2] \"username\" value of \"bogus\", \"email\" value of \"bogus@bogus.com\", \"idnumber\" value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($all_fields_data, $all_fields_message);

        return $data;
    }

    /**
     * Validates that invalid identifying user fields are logged during
     * enrolment and role assignment action on a course
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnCourseEnrolmentAndRoleAssignmentCreate($data, $message) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();
        set_config('gradebookroles', $roleid);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a course
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnCourseRoleAssignmentCreate($data, $message) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a course category
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnCategoryRoleAssignmentCreate($data, $message) {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();
        $userid = $this->create_test_user();
        $this->create_test_course();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipname'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'coursecat',
                          'instance' => 'rlipname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a user
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnUserRoleAssignmentCreate($data, $message) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $seconduserid = $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $context = get_context_instance(CONTEXT_USER, $seconduserid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'user',
                          'instance' => 'rlipusername2',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on the system context
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnSystemeRoleAssignmentCreate($data, $message) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role on a context level where it
     * is not assignable
     */
    public function testVersion1ImportLogsUnassignableContextOnRoleAssignmentCreate() {
        //set up dependencies
        $this->create_test_user();
        //create the role without enabling it at any context
        create_role('rlipfullshortname', 'rlipshortname', 'rlipdescription');

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] The role with shortname rlipshortname is not assignable on the system context level.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role on a context level that
     * doesn't exist
     */
    public function testVersion1ImportLogsInvalidContextOnRoleAssignmentCreate() {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'bogus',
                      'instance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] \"context\" value of bogus is not one of the available options (system, user, coursecat, course).\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider method for logging invalid entity messages
     *
     * @return array An array containing information about the context
     *               "shortname" and display name
     */
    public function roleAssignmentInvalidEntityProvider() {
        return array(array('course', 'course'),
                     array('coursecat', 'course category'),
                     array('user', 'user'));
    }

    /**
     * Validates that invalid identifying entity fields are logged during role
     * assignment actions on various contexts
     *
     * @param string $context The string representing the context level
     * @param string $displayname The display name for the context level
     *
     * @dataProvider roleAssignmentInvalidEntityProvider
     */
    public function testVersion1ImportLogsInvalidEntityOnRoleAssignmentCreate($context, $displayname) {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => $context,
                      'instance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] \"instance\" value of bogus does not refer to a valid instance of a {$displayname} context.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function testVersion1ImportLogsAmbiguousCategoryNameOnRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create the category
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //create a duplicate category
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'coursecat',
                      'instance' => 'rlipname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] \"instance\" value of rlipname refers to multiple course category contexts.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for invalid group name
     */
    public function testVersion1ImportLogsInvalidGroupNameOnRoleAssignmentCreate() {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname',
                      'group' => 'bogus');

        $message = "[enrolment.csv line 2] \"group\" value of bogus does not refer to a valid group in course with shortname rlipshortname.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous group name
     */
    public function testVersion1ImportLogsAmbiguousGroupNameOnRoleAssignmentCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'duplicate';
        groups_create_group($group);
        groups_create_group($group);

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname',
                      'group' => 'duplicate');

        $message = "[enrolment.csv line 2] \"group\" value of duplicate refers to multiple groups in course with shortname rlipshortname.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for invalid grouping name
     */
    public function testVersion1ImportLogsInvalidGroupingNameOnRoleAssignmentCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname',
                      'group' => 'rlipname',
                      'grouping' => 'bogus');

        $message = "[enrolment.csv line 2] \"grouping\" value of bogus does not refer to a valid grouping in course with shortname rlipshortname.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous grouping name
     */
    public function testVersion1ImportLogsAmbiguousGroupingNameOnRoleAssignmentCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        $grouping = new stdClass;
        $grouping->name = 'duplicate';
        $grouping->courseid = $courseid;
        groups_create_grouping($grouping);
        groups_create_grouping($grouping);

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname',
                      'group' => 'rlipname',
                      'grouping' => 'duplicate');

        $message = "[enrolment.csv line 2] \"grouping\" value of duplicate refers to multiple groupings in course with shortname rlipshortname.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates a duplicate enrolment failure message
     */
    public function testVersion1ImportLogsDuplicateEnrolmentFailureMessage() {
       global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();
        // Create guest user
        $guestuser = get_test_user('guest');
        set_config('siteguest', $guestuser->id);

        // Create admin user
        $admiuser = get_test_user('admin');
        set_config('siteadmins', $adminuser->id);

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //enable manual enrolments
        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //make our role a "student" role
        set_config('gradebookroles', $roleid);

        $timestart = $DB->get_field('course', 'startdate', array('id' => $courseid));
        enrol_try_internal_enrol($courseid, $userid, null,$timestart);

        role_assign($roleid, $userid, $context->id);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');
        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already enroled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates a duplicate role assignment failure message
    */
    public function testVersion1ImportLogsDuplicateRoleAssignmentFailureMessage() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        // Create guest user
        $guestuser = get_test_user('guest');
        set_config('siteguest', $guestuser->id);

        // Create admin user
        $admiuser = get_test_user('admin');
        set_config('siteadmins', $adminuser->id);

        //make our role NOT a "student" role
        set_config('gradebookroles', null);

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

    }
}
