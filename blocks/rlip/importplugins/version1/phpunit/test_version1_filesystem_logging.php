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
                     'enrol' => 'moodle',
                     //needed for course delete to prevent errors / warnings
                     'course_modules' => 'moodle',
                     'forum' => 'mod_forum');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $DB;
        $tables = array('block_instances' => 'moodle',
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
                     'external_services_users' => 'moodle',
                     'grade_grades' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     'external_services_users' => 'moodle');

        if ($DB->record_exists("block", array("name" => "curr_admin"))) {
            $tables['crlm_user_moodle'] = 'elis_program';
            $tables['crlm_user'] = 'elis_program';
        }
        return $tables;
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
     * Validate log message for ambiguous category name
     */
    public function testVersion1ImportLogsAmbiguousCategoryNameOnCourseCreate() {
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
                      'shortname' => 'rlipcoursename',
                      'fullname' => 'rlipcoursename',
                      'category' => 'rlipname');

        $message = "[course.csv line 2] \"category\" value of rlipname refers to multiple categories.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'course');
    }

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/userfile.csv');
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    public function testVersion1ImportLogsUpdateEmail() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo');
        $expected_error = "\"email\" value of testinvalid@user.com does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteEmail() {
        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo');
        $expected_error = "\"email\" value of testinvalid@user.com does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateMailDigest() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 3);
        $expected_error = "\"maildigest\" value of 3 is not one of the available options (0, 1, 2).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateAutoSubscribe() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 2);
        $expected_error = "\"autosubscribe\" value of 2 is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateTrackingDisabled() {
        $this->load_csv_data();
        set_config('forum_trackreadposts', 0);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 1,
                      'trackforums' => 0);
        $expected_error = "Tracking unread posts is currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateTracking() {
        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 1,
                      'trackforums' => 2);
        $expected_error = "\"trackforums\" value of 2 is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateScreenReader() {
        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 1,
                      'trackforums' => 1,
                      'screenreader' => 2);
        $expected_error = "\"screenreader\" value of 2 is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateUsername() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'invalidusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'city' => 'Waterloo');
        $expected_error = "\"username\" value of invalidusername does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteUsername() {
        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'username' => 'invalidusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'city' => 'Waterloo');
        $expected_error = "\"username\" value of invalidusername does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateIdNumber() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'invalidid',
                      'city' => 'Waterloo');
        $expected_error = "\"idnumber\" value of invalidid does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteIdNumber() {
        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'invalidid',
                      'city' => 'Waterloo');
        $expected_error = "\"idnumber\" value of invalidid does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateAuth() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'password' => '1234567',
                      'city' => 'Waterloo',
                      'auth' => 'invalidauth');
        $expected_error = "\"auth\" values of invalidauth is not a valid auth plugin.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdatePassword() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'password' => '1234567',
                      'city' => 'Waterloo');
        $expected_error = "\"password\" value of 1234567 does not conform to your site's password policy.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateLang() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'lang' => 'invalidlang');
        $expected_error = "\"lang\" value of invalidlang is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateCountry() {
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'lang' => 'en',
                      'country' => 'invalidcountry'
                     );
        $expected_error = "\"country\" value of invalidcountry is not a valid country code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsThemeDisabled() {
        $this->load_csv_data();
        set_config('allowuserthemes', 0);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'theme' => 'invalidtheme',
                     );
        $expected_error = "User themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateTheme() {
        $this->load_csv_data();
        set_config('allowuserthemes', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'theme' => 'invalidtheme',
                     );
        $expected_error = "\"theme\" value of invalidtheme is invalid.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsForceTimezone() {
        global $CFG;
        $this->load_csv_data();
        $CFG->forcetimezone = 97;
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'theme' => 'invalidtheme',
                      'timezone' => 98,
                     );
        $expected_error = "\"timezone\" value of 98 is not consistent with forced timezone value of {$CFG->forcetimezone} on your site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsInvalidTimezone() {
        global $CFG;
        $this->load_csv_data();
        $CFG->forcetimezone = 99;
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'timezone' => 'invalidtimezone',
                     );
        $expected_error = "\"timezone\" value of invalidtimezone is not a valid timezone.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateFormat() {
        global $CFG;
        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'timezone' => 'invalidtimezone',
                      'shortname' => 'cm2',
                      'format' => 'invalidformat'
                     );
        $expected_error = "\"format\" value does not refer to a valid course format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateNumSections() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $invalidmaxsections = $maxsections + 1;
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $invalidmaxsections
                     );
        $expected_error = "\"numsections\" value of {$invalidmaxsections} is not one of the available options (0 .. {$maxsections}).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateStartDate() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => '01/02/2012'
                     );
        $expected_error = "\"startdate\" value of 01/02/2012 is not a valid date in MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateNewsItems() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 100
                     );
        $expected_error = "\"newsitems\" value of 100 is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateShowGrades() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 3
                     );
        $expected_error = "\"showgrades\" value of 3 is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateShowReports() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 3
                     );
        $expected_error = "\"showreports\" value of 3 is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateMaxBytes() {
        global $CFG;
        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        set_config('maxbytes', 100000000, 'moodlecourse');
        $maxbytes = get_config('moodlecourse','maxbytes');
        $invalidmaxbytes = $maxbytes + 1;
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $invalidmaxbytes
                     );
        $expected_error = "\"maxbytes\" value of {$invalidmaxbytes} is not one of the available options.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateGuest() {
        global $CFG;
        $this->load_csv_data();

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $maxbytes,
                      'guest' => 'invalidguest'
                     );
        $expected_error = "\"guest\" value of invalidguest is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateVisible() {
        global $CFG;
        $this->load_csv_data();

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $maxbytes,
                      'guest' => 1,
                      'visible' => 'invalidvisible',
                     );
        $expected_error = "\"visible\" value of invalidvisible is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateCourseLang() {
        global $CFG;
        $this->load_csv_data();

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $maxbytes,
                      'guest' => 1,
                      'visible' => 1,
                      'lang' => 'invalidlang'
                     );
        $expected_error = "\"lang\" value of invalidlang is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateGuestEnrolmentsDisabled() {
        global $CFG;
        $this->load_csv_data();

        set_config('enrol_plugins_enabled', '');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $maxbytes,
                      'guest' => 1,
                      'visible' => 1,
                      'lang' => 'en',
                     );
        $expected_error = "\"guest\" enrolments cannot be enabled because the guest enrolment plugin is globally disabled.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

}
