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
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/lib/phpunittestlib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/delay_after_three.class.php');

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_fsloguser extends rlip_importprovider_withname_mock {

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
        return parent::get_import_file($entity, 'user.csv');
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_fslogcourse extends rlip_importprovider_withname_mock {

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
        return parent::get_import_file($entity, 'course.csv');
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_fslogenrolment extends rlip_importprovider_withname_mock {

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
        return parent::get_import_file($entity, 'enrolment.csv');
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

class version1FilesystemLoggingTest extends rlip_test {

    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'backup_controllers' => 'moodle',
            'backup_courses' => 'moodle',
            'block' => 'moodle',
            'block_positions' => 'moodle',
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            //needed for course delete to prevent errors / warnings
            'course_modules' => 'moodle',
            'course_sections' => 'moodle',
            'enrol' => 'moodle',
            'events_queue_handlers' => 'moodle',
            'events_queue' => 'moodle',
            'forum' => 'mod_forum',
            'grade_categories' => 'moodle',
            'grade_items' => 'moodle',
            'groupings' => 'moodle',
            'groupings_groups' => 'moodle',
            'groups' => 'moodle',
            'groups_members' => 'moodle',
            'tag_instance' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_info_category' => 'moodle',
            'user_info_field' => 'moodle',
            'user_preferences' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_context_levels' => 'moodle',
            'message_working' => 'moodle',
            'elis_scheduled_tasks' => 'elis_core',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
            RLIP_SCHEDULE_TABLE => 'block_rlip',
            RLIP_LOG_TABLE => 'block_rlip'
        );

        // Detect if we are running this test on a site with the ELIS PM system in place
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/usermoodle.class.php'));

            $tables['elis_field'] = 'elis_core';
            $tables['elis_field_categories'] = 'elis_core';
            $tables['elis_field_contextlevels'] = 'elis_core';
            $tables['elis_field_owner'] = 'elis_core';
            $tables[user::TABLE] = 'elis_program';
            $tables[usermoodle::TABLE] = 'elis_program';
        }

        // We are deleting a course, so we need to add a lot of plugin tables here
        $tables = array_merge($tables, self::load_plugin_xmldb('mod'));
        $tables = array_merge($tables, self::load_plugin_xmldb('course/format'));

        if ($DB->get_manager()->table_exists('course_sections_availability')) {
            $tables['course_sections_availability'] = 'moodle';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG, $DB;

        $tables = array(
            'assignment' => 'moodle',
            'assignment_submissions' => 'moodle',
            'backup_log' => 'moodle',
            'block_instances' => 'moodle',
            'cache_flags' => 'moodle',
            'cohort_members' => 'moodle',
            'course_display' => 'moodle',
            'course_sections' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'forum' => 'mod_forum',
            'forum_read' => 'mod_forum',
            'forum_subscriptions' => 'mod_forum',
            'forum_track_prefs' => 'moodle',
            'comments' => 'moodle',
            'rating' => 'moodle',
            'files' => 'moodle',
            'course_completion_criteria' => 'moodle',
            'course_completion_aggr_methd' => 'moodle',
            'course_completions' => 'moodle',
            'course_completion_crit_compl' => 'moodle',
            'course_modules' => 'moodle',
            'course_modules_availability' => 'moodle',
            'course_modules_completion' => 'moodle',
            'event' => 'moodle',
            'external_services_users' => 'moodle',
            'external_tokens' => 'moodle',
            'feedback_items' => 'moodle',
            'feedback_template' => 'moodle',
            //'grade_items' => 'moodle',
            'grade_categories_history' => 'moodle',
            'grade_grades' => 'moodle',
            'grade_grades_history' => 'moodle',
            'grade_items_history' => 'moodle',
            'grade_letters' => 'moodle',
            'grade_outcomes_courses' => 'moodle',
            'grade_settings' => 'moodle',
            'log' => 'moodle',
            'message'            => 'moodle',
            'message_read'       => 'moodle',
            'message_working'    => 'moodle',
            'quiz_attempts' => 'mod_quiz',
            'quiz_grades' => 'mod_quiz',
            'quiz_question_instances' => 'mod_quiz',
            'quiz_feedback' => 'mod_quiz',
            'quiz' => 'mod_quiz',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            'sessions' => 'moodle',
            'url' => 'moodle',
            'user_info_data' => 'moodle',
            'user_lastaccess' => 'moodle'
        );

        if ($DB->record_exists('block', array('name' => 'curr_admin')) && file_exists($CFG->dirroot.'/elis/program/setup.php')) {
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

        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
    }

    public function setUp() {
        parent::setUp();
        set_config('defaultblocks_override', ' ');
        //'activity_modules,recent_activity,search_forums:participants'
    }

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array $data The import data to process
     * @param string $expected_error The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expected_error, $entitytype) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set the log file location
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();
        ob_end_clean();

        //validate that a log file was created
        $manual = true;
        //get first summary record - at times, multiple summary records are created and this handles that problem
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        //get logfile name
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $format = get_string('logfile_timestamp','block_rlip');
        $testfilename = $filepath.'/'.$plugin_type.'_version1_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        //get most recent logfile

        $filename = self::get_current_logfile($testfilename);

        if (!file_exists($filename)) {
            echo "\n can't find logfile: $filename for \n$testfilename";
        }
        $this->assertTrue(file_exists($filename));

        //fetch log line
        $pointer = fopen($filename, 'r');

        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');

        while (!feof($pointer)) {
            $error = fgets($pointer);
            if (!empty($error)) { // could be an empty new line
                if (is_array($expected_error)) {
                    $actual_error[] = substr($error, $prefix_length);
                } else {
                    $actual_error = substr($error, $prefix_length);
                }
            }
        }

        fclose($pointer);

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
        $context_coursecat = get_context_instance(CONTEXT_COURSECAT, $categoryid);

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
     * @param string $fullname The new role's fullname
     * @param string $shortname The new role's shortname
     * @param string $description The new role's description
     * @return int The database record id of the created course
     */
    private function create_test_role($fullname = 'rlipfullname', $shortname = 'rlipshortname',
                                      $description = 'rlipdescription') {
        //create the role
        $roleid = create_role($fullname, $shortname, $description);

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
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
    }

    /**
     * Obtains a list of strings that identify specific user fields using field "value" syntax
     *
     * @param array $data The import data for the current line
     * @param string $prefix Prefix used in field mappings
     * @return array List of identifying strings
     */
    private function get_user_identifiers($data, $prefix = '') {
        $identifiers = array();
        if (isset($data[$prefix.'username'])) {
            $value = $data[$prefix.'username'];
            $identifiers[] = "username \"{$value}\"";
        }
        if (isset($data[$prefix.'email'])) {
            $value = $data[$prefix.'email'];
            $identifiers[] = "email \"{$value}\"";
        }
        if (isset($data[$prefix.'idnumber'])) {
            $value = $data[$prefix.'idnumber'];
            $identifiers[] = "idnumber \"{$value}\"";
        }
        return $identifiers;
    }

//    /**
//     * Validate that version 1 import plugin instances are set up with file-system
//     * loggers
//     */
//    public function testVersion1ImportInstanceHasFsLogger() {
//        global $CFG;
//        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
//        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
//        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');
//
//        //set the log file location
//        $file_path = $CFG->dataroot;
//        set_config('logfilelocation', $file_path, 'rlipimport_version1');
//
//        //set up the plugin
//        $provider = new rlip_importprovider_fsloguser(array());
//        //create a manual import
//        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
//
//        //validation
//        $fslogger = $instance->get_fslogger();
//        $this->assertEquals($fslogger instanceof rlip_fslogger, true);
//    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyUserAction() {
        //create mapping record
        $this->create_mapping_record('user', 'action', 'customaction');

        //validation for an empty action field
        $data = array('customaction' => '',
                      'username' => 'rlipusername');
        $expected_error = "[user.csv line 2] User could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user create
     */
    public function testVersion1ImportLogsEmptyUserUsernameOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        //validation for an empty username field
        $data = array('action' => 'create',
                      'customusername' => '',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User could not be created. Required field customusername is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty password field on user create
     */
    public function testVersion1ImportLogsEmptyUserPasswordOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'password', 'custompassword');

        //validation for an empty password field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'custompassword' => '',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. Required field custompassword is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty firstname field on user create
     */
    public function testVersion1ImportLogsEmptyUserFirstnameOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'firstname', 'customfirstname');

        //validation for an empty firstname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'customfirstname' => '',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. Required field customfirstname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty lastname field on user create
     */
    public function testVersion1ImportLogsEmptyUserLastnameOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'lastname', 'customlastname');

        //validation for an empty lastname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'customlastname' => '',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. Required field customlastname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty email field on user create
     */
    public function testVersion1ImportLogsEmptyUserEmailOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'email', 'customemail');

        //validation for an empty email field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'customemail' => '',
                      'city' => 'Rlipcity',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User could not be created. Required field customemail is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty city field on user create
     */
    public function testVersion1ImportLogsEmptyUserCityOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'city', 'customcity');

        //validation for an empty city field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'customcity' => '',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. Required field customcity is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty country field on user create
     */
    public function testVersion1ImportLogsEmptyUserCountryOnCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'country', 'customcountry');

        //validation for an empty country field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'customcountry' => '');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. Required field customcountry is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on a create action
     */
    public function testVersion1ImportLogsCreateMessageWithUserIdnumber() {
        //create mapping record
        $this->create_mapping_record('user', 'country', 'customcountry');

        //validation for an empty country field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'customcountry' => '',
                      'idnumber' => 'rlipidnumber');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ".
                          "idnumber \"rlipidnumber\" could not be created. Required field customcountry is unspecified ".
                          "or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user update
     */
    public function testVersion1ImportLogsEmptyUserUsernameOnUpdate() {
        //create mapping records
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        //validation for an empty username field
        $data = array('action' => 'update',
                      'customusername' => '');

        $expected_error = "[user.csv line 2] User could not be updated. One of customusername, customemail, customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when not
     * specified on an update action
     */
    public function testVersion1ImportLogsUpdateMessageWithoutUserIdnumber() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array('action' => 'update',
                      'customusername' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ".
                          "could not be updated. customusername value of \"rlipusername\" does not refer to a valid ".
                          "user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on an update action
     */
    public function testVersion1ImportLogsUpdateMessageWithUserIdnumber() {
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ".
                          "idnumber \"rlipidnumber\" could not be updated. username value of \"rlipusername\" does not ".
                          "refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages work without username specified on
     * an update action
     */
    public function testVersion1ImportLogsUserMessageWithoutUsername() {
        //create mapping record
        $this->create_mapping_record('user', 'email', 'customemail');

        //validation for an empty country field
        $data = array('action' => 'update',
                      'customemail' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber');

        $expected_error = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" ".
                          "could not be updated. customemail value of \"rlipuser@rlipdomain.com\" does not refer to a ".
                          "valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user delete
     */
    public function testVersion1ImportLogsEmptyUserUsernameOnDelete() {
        //create mapping records
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        //validation for an empty username field
        $data = array('action' => 'delete',
                      'customusername' => '');
        $expected_error = "[user.csv line 2] User could not be deleted. One of customusername, customemail, customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages do not include the idnumber when
     * not specified on a delete action
     */
    public function testVersion1ImportLogsDeleteMessageWithoutUserIdnumber() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array('action' => 'delete',
                      'customusername' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ".
                          "could not be deleted. customusername value of \"rlipusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on a delete action
     */
    public function testVersion1ImportLogsDeleteMessageWithUserIdnumber() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array('action' => 'delete',
                      'customusername' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber');

        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ".
                          "idnumber \"rlipidnumber\" could not be deleted. customusername value of \"rlipusername\" ".
                          "does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyCourseAction() {
        //create mapping record
        $this->create_mapping_record('course', 'action', 'customaction');

        //validation for an empty action field
        $data = array('customaction' => '',
                      'shortname' => 'rlipshortname');
        $expected_error = "[course.csv line 2] Course could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        //validation for an empty shortname field
        $data = array('action' => 'create',
                      'customshortname' => '',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');

        $expected_error = "[course.csv line 2] Course could not be created. Required field customshortname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty fullname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseFullnameOnCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'fullname', 'customfullname');

        //validation for an empty fullname field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'customfullname' => '',
                      'category' => 'rlipcategory');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Required field customfullname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty category field on course create
     */
    public function testVersion1ImportLogsEmptyCourseCategoryOnCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'category', 'customcategory');

        //validation for an empty category field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'customcategory' => '');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Required field customcategory is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course update
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        //validation for an empty shortname field
        $data = array('action' => 'update',
                      'customshortname' => '');

        $expected_error = "[course.csv line 2] Course could not be updated. Required field customshortname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course delete
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnDelete() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        //validation for an empty shortname field
        $data = array('action' => 'delete',
                      'customshortname' => '');

        $expected_error = "[course.csv line 2] Course could not be deleted. Required field customshortname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty enrolment action field
     */
    public function testVersion1ImportLogsEmptyEnrolmentAction() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'action', 'customaction');

        //validation for an empty action field
        $data = array('customaction' => '',
                      'username' => 'rlipusername',
                      'context' => 'rlipcontext',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $expected_error = "[enrolment.csv line 2] Enrolment could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnCreate() {
        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //validation for an empty username field
        $data = array('action' => 'create',
                      'customusername' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. One of customusername, customemail, customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnCreate() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        //validation for an empty context field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'customcontext' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. Required field customcontext is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnCreate() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        //validation for an empty instance field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'custominstance' => '',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. Required field custominstance is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnCreate() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        //validation for an empty role field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'customrole' => '');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. Required field customrole is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnDelete() {
        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //validation fo an empty username field
        $data = array('action' => 'delete',
                      'customusername' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be deleted. One of customusername, customemail, customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnDelete() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        //validation for an empty context field
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'customcontext' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be deleted. Required field customcontext is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnDelete() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        //validation for an empty instance field
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'custominstance' => '',
                      'role' => 'rliprole');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be deleted. Required field custominstance is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnDelete() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        //validation for an empty role field
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'customrole' => '');

        $expected_error = "[enrolment.csv line 2] Enrolment could not be deleted. Required field customrole is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged when multiple required fields are empty
     */
    public function testVersion1ImportLogsMultipleEmptyFields() {
        //create mapping records
        $this->create_mapping_record('course', 'shortname', 'customshortname');
        $this->create_mapping_record('course', 'fullname', 'customfullname');
        $this->create_mapping_record('course', 'category', 'customcategory');

        //validation for three empty required fields
        $data = array('action' => 'create',
                      'customshortname' => '',
                      'customfullname' => '',
                      'customcategory' => '');

        $expected_error = "[course.csv line 2] Course could not be created. Required fields customshortname, customfullname, customcategory are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an appropriate error is logged for a scenario with missing required fields,
     * some of which are only required in a "1 of n"-fashion
     */
    public function testVersion1ImportLogsMultipleEmptyFieldsWithOption() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //validation for "1 of 3", plus 3 required fields
        $data = array('action' => 'create',
                      'customusername' => '',
                      'customemail' => '',
                      'customidnumber' => '',
                      'context' => '',
                      'role' => '');
        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. One of customusername, customemail, customidnumber is required but all are unspecified or empty. Required fields context, instance, role are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged for a field that is empty
     * in the import file
     */
    public function testVersion1ImportLogsEmptyField() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        //validation for unspecified field "shortname"
        $data = array('action' => 'update',
                      'customshortname' => '');
        $expected_error = "[course.csv line 2] Course could not be updated. Required field customshortname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that error logging works correctly with the user "create or update" functionality
     */
    public function testVersion1ImportLoggingSupportsUserCreateOrUpdate() {
        global $CFG;

        set_config('createorupdate', 1, 'rlipimport_version1');

        //create mapping records
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'password', 'custompassword');
        $this->create_mapping_record('user', 'firstname', 'customfirstname');
        $this->create_mapping_record('user', 'lastname', 'customlastname');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'city', 'customcity');
        $this->create_mapping_record('user', 'country', 'customcountry');

        //create a user so it can be updated
        self::cleanup_log_files();
        $data = array('action' => 'create',
                      'customusername' => 'rlipusername',
                      'custompassword' => 'Rlippassword!0',
                      'customfirstname' => 'rlipfirstname',
                      'customlastname' => 'rliplastname',
                      'customemail' => 'rlipuser@rlipdomain.com',
                      'customcity' => 'rlipcity',
                      'customcountry' => 'CA');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array('mnethostid' => $CFG->mnet_localhost_id,
                      'username' => $data['customusername'],
                      'password' => hash_internal_user_password($data['custompassword']),
                      'firstname' => $data['customfirstname'],
                      'lastname' => $data['customlastname'],
                      'email' => $data['customemail'],
                      'city' => $data['customcity'],
                      'country' => $data['customcountry']);
        $this->assert_record_exists('user', $data);

        //update validation using create
        $data = array('action' => 'create',
                      'customusername' => '',
                      'custompassword' => '',
                      'customfirstname' => '',
                      'customlastname' => '',
                      'customemail' => '',
                      'customcity' => '',
                      'customcountry' => '');
        $expected_error = "[user.csv line 2] User could not be created. Required fields customusername, custompassword, customfirstname, customlastname, customemail, customcity, customcountry are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        //actually update using create
        self::cleanup_log_files();
        $data = array('action' => 'create',
                      'customusername' => 'rlipusername',
                      'customfirstname' => 'updatedrlipfirstname');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
        ob_start();
        $instance->run();
        ob_end_clean();

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

        //create mapping records
        $this->create_mapping_record('course', 'shortname', 'customshortname');
        $this->create_mapping_record('course', 'fullname', 'customfullname');
        $this->create_mapping_record('course', 'category', 'customcategory');

        //set up the site course record
        $this->create_contexts_and_site_course();

        //create a course so it can be updated
        self::cleanup_log_files();
        $data = array('action' => 'create',
                      'customshortname' => 'rlipshortname',
                      'customfullname' => 'rlipfullname',
                      'customcategory' => 'rlipcategory');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array('shortname' => $data['customshortname'],
                      'fullname' => $data['customfullname']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $this->assert_record_exists('course', $data);

        //actually update using create
        self::cleanup_log_files();
        $data = array('action' => 'create',
                      'customshortname' => 'rlipshortname',
                      'customfullname' => 'updatedrlipfullname');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, true);
        ob_start();
        $instance->run();
        ob_end_clean();

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
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $UNITTEST = new stdClass;
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up dependencies
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $syscontext = context_system::instance();
        $roleid = $this->create_test_role();

        set_config('siteguest', '');

        //make sure we can enrol the test user
        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //set up an enrolment but no role assignment
        enrol_try_internal_enrol($courseid, $userid);

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
        $context = context_course::instance($courseid);
        $roleid = $this->create_test_role();

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

        $this->create_contexts_and_site_course();

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

        $this->create_contexts_and_site_course();

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
        global $DB;

        $this->create_contexts_and_site_course();

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
        global $DB;

        $this->create_contexts_and_site_course();

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
        $username_data = array('customusername' => 'bogus');
        $username_message = "[enrolment.csv line 2] customusername value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($username_data, $username_message);

        //invalid email
        $email_data = array('customemail' => 'bogus@bogus.com');
        $email_message = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\" does not refer to a valid user.\n";
        $data[] = array($email_data, $email_message);

        //invalid idnumber
        $idnumber_data = array('customidnumber' => 'bogus');
        $idnumber_message = "[enrolment.csv line 2] customidnumber value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($idnumber_data, $idnumber_message);

        //invalid combination of username, email
        $username_email_data = array('customusername' => 'bogus',
                                     'customemail' => 'bogus@bogus.com');
        $username_email_message = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\" do not refer to a valid user.\n";
        $data[] = array($username_email_data, $username_email_message);

        //invalid combination of username, idnumber
        $username_idnumber_data = array('customusername' => 'bogus',
                                        'customidnumber' => 'bogus');
        $username_idnumber_message = "[enrolment.csv line 2] customusername value of \"bogus\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($username_idnumber_data, $username_idnumber_message);

        //invalid combination of email, idnumber
        $email_idnumber_data = array('customemail' => 'bogus@bogus.com',
                                     'customidnumber' => 'bogus');
        $email_idnumber_message = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($email_idnumber_data, $email_idnumber_message);

        //invalid combination of username, email, idnumber
        $all_fields_data = array('customusername' => 'bogus',
                                 'customemail' => 'bogus@bogus.com',
                                 'customidnumber' => 'bogus');
        $all_fields_message = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
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

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
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

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
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

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'coursecat',
                          'instance' => 'rlipname',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
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

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'user',
                          'instance' => 'rlipusername2',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
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
    public function testVersion1ImportLogsInvalidUserOnSystemRoleAssignmentCreate($data, $message) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
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

        $message = "[enrolment.csv line 2] Enrolment could not be created. The role with shortname \"rlipshortname\" is not assignable on the system context level.\n";

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

        //create mapping record
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'customcontext' => 'bogus',
                      'instance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be created. customcontext value of \"bogus\" is not one of the available options (system, user, coursecat, course).\n";
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role that doesn't exist
     */
    public function testVersion1ImportLogsInvalidRoleOnRoleAssignmentCreate() {
        //set up dependencies
        $this->create_test_user();

        //create mapping records
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'customrole' => 'bogus');

        $message = "[enrolment.csv line 2] Enrolment could not be created. customrole value of \"bogus\" does not refer to a valid role.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate that approval messages still work when roles do not have the
     * standard Moodle "view course" capability
     */
    public function testVersion1ImportLogsSuccessWhenMissingCourseViewCapability() {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";

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

        //create mapping records
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        //data
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => $context,
                      'custominstance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be created. custominstance value of \"bogus\" does not refer to a valid instance of a {$displayname} context.\n";

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

        //create mapping records
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

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
                      'custominstance' => 'rlipname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be created. custominstance value of \"rlipname\" refers to multiple course category contexts.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for invalid group name
     */
    public function testVersion1ImportLogsInvalidGroupNameOnRoleAssignmentCreate() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'group', 'customgroup');

        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname',
                      'customgroup' => 'bogus');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be enrolled in course with shortname \"rlipshortname\". customgroup value of \"bogus\" does not refer to a valid group in course with shortname \"rlipshortname\".\n";
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider function that providers all combinations of identifying
     * user fields
     *
     * @return array Data in format expected by phpunit
     */
    public function roleAssignmentAmbiguousGroupNameUserProvider() {
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumber = 'rlipidnumber';

        return array(array(array('username' => $username)),
                     array(array('email' => $email)),
                     array(array('idnumber' => $idnumber)),
                     array(array('username' => $username,
                                 'email' => $email)),
                     array(array('username' => $username,
                                 'idnumber' => $idnumber)),
                     array(array('email' => $email,
                                 'idnumber' => $idnumber)),
                     array(array('username' => $username,
                                 'email' => $email,
                                 'idnumber' => $idnumber)));
    }

    /**
     * Validate log message for ambiguous group name
     *
     * @param array $data Import data, consisting of identifying user fields
     *                    and values
     * @dataProvider roleAssignmentAmbiguousGroupNameUserProvider
     */
    public function testVersion1ImportLogsAmbiguousGroupNameOnRoleAssignmentCreate($data) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'group', 'customgroup');

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'duplicate';
        groups_create_group($group);
        groups_create_group($group);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'customgroup' => 'duplicate');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be enrolled in course with shortname \"rlipshortname\". customgroup value of \"duplicate\" refers to multiple groups in course with shortname \"rlipshortname\".\n";

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

        //create mapping record
        $this->create_mapping_record('enrolment', 'grouping', 'customgrouping');

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
                      'customgrouping' => 'bogus');

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be enrolled in course with shortname \"rlipshortname\". customgrouping value of \"bogus\" does not refer to a valid grouping in course with shortname \"rlipshortname\".\n";
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

     /**
      * Validate log message for ambiguous grouping name
      */
    public function testVersion1ImportLogsAmbiguousGroupingNameOnRoleAssignmentCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        set_config('enrol_plugin_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'grouping', 'customgrouping');

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
                      'customgrouping' => 'duplicate');

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be enrolled in course with shortname \"rlipshortname\". customgrouping value of \"duplicate\" refers to multiple groupings in course with shortname \"rlipshortname\".\n";
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

     /**
      * Validate log message for assigning a user to a group they already
      * belong to
      */
     public function testVersion1ImportLogsDuplicateGroupAssignment() {
        global $DB;

        //set up dependencies
        $this->create_contexts_and_site_course();

        set_config('enrol_plugin_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        $group->id = groups_create_group($group);

        //enrol the user in some secondary student role
        $secondroleid = $this->create_test_role('secondfullname', 'secondshortname', 'seconddescription');
        enrol_try_internal_enrol($courseid, $userid, $secondroleid);
        //assign the user to the group
        groups_add_member($group->id, $userid);

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'course',
                          'instance' => 'rlipshortname',
                          'role' => 'rlipshortname',
                          'group' => 'rlipname');

        //username
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validate log message for assigning a group to a grouping is already
     * belongs to
     */
    public function testVersion1ImportLogsDuplicateGroupingAssignment() {
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
        $group->id = groups_create_group($group);

        $grouping = new stdClass;
        $grouping->courseid = $courseid;
        $grouping->name = 'rlipname';
        $grouping->id = groups_create_grouping($grouping);

        groups_assign_grouping($grouping->id, $group->id);

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
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');
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
        $adminuser = get_test_user('admin');
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
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be enrolled in course with shortname \"rlipshortname\". User with username \"rlipusername\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" could not be enrolled in course with shortname \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" could not be enrolled in course with shortname \"rlipshortname\". User with idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be enrolled in course with shortname \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" could not be enrolled in course with shortname \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be enrolled in course with shortname \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be enrolled in course with shortname \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already enrolled in course with shortname \"rlipshortname\".\n";
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
        $adminuser = get_test_user('admin');
        set_config('siteadmins', $adminuser->id);

        $userid = $this->create_test_user();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        //base data used every time
        $basedata = array('action' => 'create',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //username
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        //username, email, idnumber
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during
     * role unassignment on system context
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleAssignmentInvalidUserProvider
     */
    public function testVersion1ImportLogsInvalidUserOnSystemRoleAssignmentDelete($data, $message) {
        //set up dependencies
        $roleid = $this->create_test_role();

        //create mapping records
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        //base data used every time
        $basedata = array('action' => 'delete',
                          'context' => 'system',
                          'role' => 'rlipshortname');

        //set up the exact data we need
        $data = array_merge($basedata, $data);

        //add general message to expected log message
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $general_message = 'Enrolment could not be deleted.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$general_message.$suffix;
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for unassigning a role from a context level that
     * doesn't exist
     */
    public function testVersion1ImportLogsInvalidContextOnRoleAssignmentDelete() {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'customcontext' => 'bogus',
                      'instance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. customcontext value of \"bogus\" is not one of the available options (system, user, coursecat, course).\n";
        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for unassigning a role that doesn't exist
     */
    public function testVersion1ImportLogsInvalidRoleOnRoleAssignmentDelete() {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping records
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'customrole' => 'bogus');

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. customrole value of \"bogus\" does not refer to a valid role.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider method, providing identifying user fields as well as
     * text to describe those fields, with every combination of identifying
     * user fields provided
     *
     * @return array Array of data, with each elements containing a data set
     *               and a descriptive string
     */
    public function userDescriptorProvider() {
        $data = array();

        //username
        $username_data = array('username' => 'rlipusername');
        $username_descriptor = "username \"rlipusername\"";
        $data[] = array($username_data, $username_descriptor);

        //email
        $email_data = array('email' => 'rlipuser@rlipdomain.com');
        $email_descriptor = "email \"rlipuser@rlipdomain.com\"";
        $data[] = array($email_data, $email_descriptor);

        //idnumber
        $idnumber_data = array('idnumber' => 'rlipidnumber');
        $idnumber_descriptor = "idnumber \"rlipidnumber\"";
        $data[] = array($idnumber_data, $idnumber_descriptor);

        //username, email
        $username_email_data = array('username' => 'rlipusername',
                                     'email' => 'rlipuser@rlipdomain.com');
        $username_email_descriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $data[] = array($username_email_data, $username_email_descriptor);

        //username, idnumber
        $username_idnumber_data = array('username' => 'rlipusername',
                                        'idnumber' => 'rlipidnumber');
        $username_idnumber_descriptor = "username \"rlipusername\", idnumber \"rlipidnumber\"";
        $data[] = array($username_idnumber_data, $username_idnumber_descriptor);

        //email, idnumber
        $email_idnumber_data = array('email' => 'rlipuser@rlipdomain.com',
                                     'idnumber' => 'rlipidnumber');
        $email_idnumber_descriptor = "email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($email_idnumber_data, $email_idnumber_descriptor);

        //username, email, idnumber
        $all_fields_data = array('username' => 'bogus',
                                 'email' => 'bogus@bogus.com',
                                 'idnumber' => 'bogus');
        $all_fields_descriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($all_fields_data, $all_fields_descriptor);

        return $data;
    }

    /**
     * Validates that deletion of nonexistent enrolments are logged
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userDescriptorProvider
     */
    public function testVersion1ImportLogsNonexistentEnrolmentDelete($data, $descriptor) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $roleid = $this->create_test_role();
        $this->create_test_course();

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be unenrolled from course with shortname \"rlipshortname\". User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is not enrolled in course with shortname \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role enrolments are logged for
     * courses
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userDescriptorProvider
     */
    public function testVersion1ImportLogsNonexistentRoleAssignmentOnCourseRoleAssignmentDelete($data, $descriptor) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $this->create_test_role();
        $this->create_test_course();

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be unenrolled from course with shortname \"rlipshortname\". User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is not enrolled in course with shortname \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that, for deletion of a course role that is not assigned, an error is logged
     * instead of deleting the user's enrolment if they have some other role assignment on
     * the coruse
     */
    public function testVersion1ImportLogsDeletionOfEnrolmentWhenRolesAssignmentsExist() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //set up dependencies
        $this->create_contexts_and_site_course();
        $userid = $this->create_test_user();
        $unassignedroleid = $this->create_test_role('unassigned', 'unassigned', 'unassigned');
        $assignedroleid = $this->create_test_role('assigned', 'assigned', 'assigned');
        $courseid = $this->create_test_course();

        set_config('siteguest', '');

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //set up a role assignment and an enrolment
        enrol_try_internal_enrol($courseid, $userid, $assignedroleid);

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'unassigned');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"unassigned\" on course \"rlipshortname\". User with username \"rlipusername\" could not be unenrolled from course with shortname \"rlipshortname\". User with username \"rlipusername\" is not assigned role with shortname \"unassigned\" on course \"rlipshortname\". User with username \"rlipusername\" requires their enrolment to be maintained because they have another role assignment in this course.\n";
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * course categories
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userDescriptorProvider
     */
    public function testVersion1ImportLogsNonexistentRoleAssignmentOnCategoryRoleAssignmentDelete($data, $descriptor) {
        //set up dependencies
        $this->create_contexts_and_site_course();
        $this->create_test_user();
        $this->create_test_role();
        //also creates test category
        $this->create_test_course();

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'coursecat',
                      'instance' => 'rlipname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on course category \"rlipname\". User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * users
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userDescriptorProvider
     */
    public function testVersion1ImportLogsNonexistentRoleAssignmentOnUserRoleAssignmentDelete($data, $descriptor) {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();
        $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'user',
                      'instance' => 'rlipusername2',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\". User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function testVersion1ImportLogsAmbiguousCategoryNameOnRoleAssignmentDelete() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        //create the category
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //create a duplicate category
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'coursecat',
                      'custominstance' => 'rlipname',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. custominstance value of \"rlipname\" refers to multiple course category contexts.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * the system context
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userDescriptorProvider
     */
    public function testVersion1ImportLogsNonexistentRoleAssignmentOnSystemRoleAssignmentDelete($data, $descriptor) {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on the system context.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
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
    public function testVersion1ImportLogsInvalidEntityOnRoleAssignmentDelete($context, $displayname) {
        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        //data
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => $context,
                      'custominstance' => 'bogus',
                      'role' => 'rlipshortname');

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. custominstance value of \"bogus\" does not refer to a valid instance of a {$displayname} context.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function testVersion1ImportLogsAmbiguousCategoryNameOnCourseCreate() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('course', 'category', 'customcategory');

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
                      'customcategory' => 'rlipname');

        $message = "[course.csv line 2] Course with shortname \"rlipcoursename\" could not be created. customcategory value of \"rlipname\" refers to multiple categories.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate log message for ambiguous parent category name as part of
     * a category path
     */
    public function testVersion1ImportLogsAmbiguousParentCategoryNameOnCourseCreate() {
        global $DB;

        //set up dependencies
        $this->create_test_user();
        $this->create_test_role();

        //create mapping record
        $this->create_mapping_record('course', 'category', 'customcategory');

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
                      'customcategory' => 'rlipname/rlipchildname');

        $message = "[course.csv line 2] Course with shortname \"rlipcoursename\" could not be created. customcategory value of \"rlipname/rlipchildname\" refers to an ambiguous parent category path.\n";

        //validation
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that auth validation works on user create
     */
    public function testVersion1ImportLogsInvalidAuthOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'auth', 'customauth');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customauth' => 'bogus');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customauth value of \"bogus\" is not a valid auth plugin.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that username validation works on user create
     */
    public function testVersion1ImportLogsInvalidUsernameOnUserCreate() {
        global $CFG, $DB;

        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        //data setup
        $this->load_csv_data();
        //make sure the user belongs to "localhost"
        $user = new stdClass;
        $user->id = 3;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $DB->update_record('user', $user);

        $data = array('action' => 'create',
                      'customusername' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"rlipuser@rlipdomain.com\" could not be created. customusername value of \"testusername\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that email validation works on user create
     */
    public function testVersion1ImportLogsInvalidEmailOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'email', 'customemail');

        $data = array(
            'action'      => 'create',
            'username'    => 'testusername',
            'password'    => 'Rlippassword!0',
            'firstname'   => 'rlipfirstname',
            'lastname'    => 'rliplastname',
            'customemail' => 'bogusemail',
            'city'        => 'Waterloo',
            'country'     => 'CA'
        );
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"bogusemail\" could not be created.".
                          " customemail value of \"bogusemail\" is not a valid email address.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $this->load_csv_data();
        $data['username']    = 'validusername';
        $data['customemail'] = 'test@user.com';
        $expected_error = "[user.csv line 2] User with username \"validusername\", email \"test@user.com\" could not be ".
                          "created. customemail value of \"test@user.com\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        set_config('allowduplicateemails','1','rlipimport_version1');

        $expected_error = "[user.csv line 2] User with username \"validusername\", email \"test@user.com\" successfully created.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that idnumber validation works on user create
     */
    public function testVersion1ImportLogsInvalidIdnumberOnUserCreate() {
        $this->load_csv_data();

        //create mapping record
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array('action' => 'create',
                      'username' => 'uniqueusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customidnumber' => 'idnumber');

        $expected_error = "[user.csv line 2] User with username \"uniqueusername\", email \"rlipuser@rlipdomain.com\", idnumber \"idnumber\" could not be created. customidnumber value of \"idnumber\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that password validation works on user create
     */
    public function testVersion1ImportLogsInvalidPasswordOnUserCreate() {
        set_config('passwordpolicy', 1);
        set_config('minpassworddigits', 1);

        //create mapping record
        $this->create_mapping_record('user', 'password', 'custompassword');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'custompassword' => 'invalidpassword',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'idnumber' => 'idnumber'
        );

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be created. custompassword value of \"invalidpassword\" does not conform to your site's password policy.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that maildigest validation works on user create
     */
    public function testVersion1ImportLogsInvalidMaildigestOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'maildigest', 'custommaildigest');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'custommaildigest' => '3');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. custommaildigest value of \"3\" is not one of the available options (0, 1, 2).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that autosubscribe validation works on user create
     */
    public function testVersion1ImportLogsInvalidAutosubscribeOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'autosubscribe', 'customautosubscribe');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customautosubscribe' => '2');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customautosubscribe value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that trackforums validation works on user create
     */
    public function testVersion1ImportLogsInvalidTrackforumsOnUserCreate() {
        set_config('forum_trackreadposts', 0);

        //create mapping record
        $this->create_mapping_record('user', 'trackforums', 'customtrackforums');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customtrackforums' => '1');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. Tracking unread posts is currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        set_config('forum_trackreadposts', 1);
        $data['customtrackforums'] = 2;
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customtrackforums value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that screenreader validation works on user create
     */
    public function testVersion1ImportLogsInvalidScreenreaderOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'screenreader', 'customscreenreader');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customscreenreader' => '2');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customscreenreader value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that country validation works on user create
     */
    public function testVersion1ImportLogsInvalidCountryOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'country', 'customcountry');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'customcountry' => 'bogus');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customcountry value of \"bogus\" is not a valid country or country code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that timezone validation works on user create
     */
    public function testVersion1ImportLogsInvalidTimezoneOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

        set_config('forcetimezone', '99');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customtimezone' => 'bogus');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customtimezone value of \"bogus\" is not a valid timezone.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        set_config('forcetimezone', '-5.0');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customtimezone' => '-4.0');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customtimezone value of \"-4.0\" is not consistent with forced timezone value of \"-5.0\" on your site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that theme validation works on user create
     */
    public function testVersion1ImportLogsInvalidThemeOnUserCreate() {
        set_config('allowuserthemes', 0);

        //create mapping record
        $this->create_mapping_record('user', 'theme', 'customtheme');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customtheme' => 'bartik');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. User themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        set_config('allowuserthemes', 1);
        $data['customtheme'] = 'bogus';
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customtheme value of \"bogus\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that lang validation works on user create
     */
    public function testVersion1ImportLogsInvalidLangOnUserCreate() {
        //create mapping record
        $this->create_mapping_record('user', 'lang', 'customlang');

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customlang' => 'bogus');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be created. customlang value of \"bogus\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Create a custom field category
     *
     * @return int The database id of the new category
     */
    private function create_custom_field_category() {
        global $DB;

        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        return $category->id;
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @param string $param1 Extra parameter, used for select options
     * @param string $defaultdata Default value
     * @return int The id of the created profile field
     */
    private function create_profile_field($name, $datatype, $categoryid, $param1 = NULL, $defaultdata = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        //core fields
        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== NULL) {
            //set the select options
            $data->param1 = $param1;
        }

        if ($defaultdata !== NULL) {
            //set the default value
            $data->defaultdata = $defaultdata;
        }

        $field->define_save($data);
        return $data->id;
    }

    /**
     * Validate that profile field validation works on user create
     */
    public function testVersion1ImportLogsInvalidProfileFieldDataOnUserCreate() {
        //create category and custom fields
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('checkbox', 'checkbox', $categoryid);
        $this->create_profile_field('menu', 'menu', $categoryid, 'option1');
        $this->create_profile_field('date', 'datetime', $categoryid);

        //create mapping records
        $this->create_mapping_record('user', 'profile_field_checkbox', 'customprofile_field_checkbox');
        $this->create_mapping_record('user', 'profile_field_menu', 'customprofile_field_menu');
        $this->create_mapping_record('user', 'profile_field_date', 'customprofile_field_date');

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'RLippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Waterloo',
                      'country' => 'CA',
                      'customprofile_field_checkbox' => 2);


        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. \"2\" is not one of the available options for a checkbox profile field checkbox (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        unset($data['customprofile_field_checkbox']);
        $data['customprofile_field_menu'] = 'option2';
        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. \"option2\" is not one of the available options for a menu of choices profile field menu.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        unset($data['customprofile_field_menu']);
        $data['customprofile_field_date'] = 'bogus';
        $expected_error = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be created. customprofile_field_date value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    protected function load_csv_data() {
        global $CFG;

        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/usertable.csv');
        $dataset->addTable('user_info_field', dirname(__FILE__).'/user_info_field.csv');
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        $dataset->addFullReplacement('##MNET_LOCALHOST_ID##', $CFG->mnet_localhost_id);
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    public function testVersion1ImportLogsUpdateEmail() {
        //create mapping record
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();
        $data = array(
            'action'      => 'update',
            'username'    => 'testusername',
            'customemail' => 'testinvalid@user.com',
            'city'        => 'Waterloo'
        );

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be".
                          " updated. customemail value of \"testinvalid@user.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteEmail() {
        //create mapping record
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'username' => 'testusername',
                      'customemail' => 'testinvalid@user.com',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be deleted. customemail value of \"testinvalid@user.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateMailDigest() {
        //create mapping record
        $this->create_mapping_record('user', 'maildigest', 'custommaildigest');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'custommaildigest' => 3);

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be updated. custommaildigest value of \"3\" is not one of the available options (0, 1, 2).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateAutoSubscribe() {
        //create mapping record
        $this->create_mapping_record('user', 'autosubscribe', 'customautosubscribe');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'testinvalid@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'customautosubscribe' => 2);

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be updated. customautosubscribe value of \"2\" is not one of the available options (0, 1).\n";
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
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated. Tracking unread posts is currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateTracking() {
        //create mapping record
        $this->create_mapping_record('user', 'trackforums', 'customtrackforums');

        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 1,
                      'customtrackforums' => 2);

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated. customtrackforums value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateScreenReader() {
        //create mapping record
        $this->create_mapping_record('user', 'screenreader', 'customscreenreader');

        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'maildigest' => 2,
                      'autosubscribe' => 1,
                      'trackforums' => 1,
                      'customscreenreader' => 2);

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated. customscreenreader value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateUsername() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'customusername' => 'invalidusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"invalidusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. customusername value of \"invalidusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteUsername() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'customusername' => 'invalidusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"invalidusername\", email \"test@user.com\", idnumber \"idnumber\" could not be deleted. customusername value of \"invalidusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateIdNumber() {
        //create mapping record
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'customidnumber' => 'invalidid',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"invalidid\" could not be updated. customidnumber value of \"invalidid\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsDeleteIdNumber() {
        //create mapping record
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $this->load_csv_data();
        $data = array('action' => 'delete',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'customidnumber' => 'invalidid',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"invalidid\" could not be deleted. customidnumber value of \"invalidid\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateAuth() {
        //create mapping record
        $this->create_mapping_record('user', 'auth', 'customauth');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'password' => '1234567',
                      'city' => 'Waterloo',
                      'customauth' => 'invalidauth');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. customauth value of \"invalidauth\" is not a valid auth plugin.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdatePassword() {
        //create mapping record
        $this->create_mapping_record('user', 'password', 'custompassword');

        set_config('minpasswordlower', 1);

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'email' => 'test@user.com',
                      'idnumber' => 'idnumber',
                      'custompassword' => '1234567',
                      'city' => 'Waterloo');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. custompassword value of \"1234567\" does not conform to your site's password policy.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateLang() {
        //create mapping record
        $this->create_mapping_record('user', 'lang', 'customlang');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'customlang' => 'invalidlang');

        $expected_error = "[user.csv line 2] User with username \"testusername\" could not be updated. customlang value of \"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateCountry() {
        //create mapping record
        $this->create_mapping_record('user', 'country', 'customcountry');

        $this->load_csv_data();
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'lang' => 'en',
                      'customcountry' => 'invalidcountry'
                     );

        $expected_error = "[user.csv line 2] User with username \"testusername\" could not be updated. customcountry value of \"invalidcountry\" is not a valid country or country code.\n";
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
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. User themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsUpdateTheme() {
        //create mapping record
        $this->create_mapping_record('user', 'theme', 'customtheme');

        $this->load_csv_data();
        set_config('allowuserthemes', 1);
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'customtheme' => 'invalidtheme',
                     );

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. customtheme value of \"invalidtheme\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsForceTimezone() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

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
                      'customtimezone' => 98,
                     );

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. customtimezone value of \"98\" is not consistent with forced timezone value of \"{$CFG->forcetimezone}\" on your site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testVersion1ImportLogsInvalidTimezone() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

        $this->load_csv_data();
        $CFG->forcetimezone = 99;
        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'password' => 'm0ddl3.paSs',
                      'email' => 'test@user.com',
                      'lang' => 'en',
                      'idnumber' => 'idnumber',
                      'country' => 'CA',
                      'customtimezone' => 'invalidtimezone',
                     );

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\" could not be updated. customtimezone value of \"invalidtimezone\" is not a valid timezone.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that profile field validation works on user update
     */
    public function testVersion1ImportLogsInvalidProfileFieldDataOnUserUpdate() {
        //create category and custom fields
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('checkbox', 'checkbox', $categoryid);
        $this->create_profile_field('menu', 'menu', $categoryid, 'option1');
        $this->create_profile_field('date', 'datetime', $categoryid);

        //create mapping record
        $this->create_mapping_record('user', 'profile_field_checkbox', 'customprofile_field_checkbox');
        $this->create_mapping_record('user', 'profile_field_menu', 'customprofile_field_menu');
        $this->create_mapping_record('user', 'profile_field_date', 'customprofile_field_date');

        //setup
        $this->create_test_user();

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'customprofile_field_checkbox' => 2);

        $expected_error = "[user.csv line 2] User with username \"rlipusername\" could not be updated. \"2\" is not one of the available options for a checkbox profile field checkbox (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        unset($data['customprofile_field_checkbox']);
        $data['customprofile_field_menu'] = 'option2';
        $expected_error = "[user.csv line 2] User with username \"rlipusername\" could not be updated. \"option2\" is not one of the available options for a menu of choices profile field menu.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        unset($data['customprofile_field_menu']);
        $data['customprofile_field_date'] = 'bogus';
        $expected_error = "[user.csv line 2] User with username \"rlipusername\" could not be updated. customprofile_field_date value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate log message for duplicate course shortname when creating a course
     */
    public function testVersion1ImportLogsInvalidShortnameOnCourseCreate() {
        global $CFG, $DB;

        $this->create_contexts_and_site_course();
        $this->create_test_course();

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. shortname value of \"rlipshortname\" refers to a course that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that format validation works on course create
     */
    public function testVersion1ImportLogsInvalidFormatOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'format', 'customformat');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customformat' => 'bogus');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customformat value of \"bogus\" does not refer to a valid course format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that numsections validation works on course create
     */
    public function testVersion1ImportLogsInvalidNumsectionsOnCourseCreate() {
        set_config('maxsections', 10, 'moodlecourse');

        //create mapping record
        $this->create_mapping_record('course', 'numsections', 'customnumsections');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customnumsections' => '11');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customnumsections value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that startdate validation works on course create
     */
    public function testVersion1ImportLogsInvalidStartdateOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customstartdate' => 'bogus');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customstartdate value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that newsitems validation works on course create
     */
    public function testVersion1ImportLogsInvalidNewsitemsOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'newsitems', 'customnewsitems');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customnewsitems' => '11');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customnewsitems value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that showgrades validation works on course create
     */
    public function testVersion1ImportLogsInvalidShowgradesOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'showgrades', 'customshowgrades');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customshowgrades' => '2');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customshowgrades value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that showreports validation works on course create
     */
    public function testVersion1ImportLogsInvalidShowreportsOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'showreports', 'customshowreports');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customshowreports' => '2');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customshowreports value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that maxbytes validation works on course create
     */
    public function testVersion1ImportLogsInvalidMaxbytesOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'maxbytes', 'custommaxbytes');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'custommaxbytes' => 'bogus');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. custommaxbytes value of \"bogus\" is not one of the available options.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that guest validation works on course create
     */
    public function testVersion1ImportLogsInvalidGuestOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'guest', 'customguest');

        set_config('enrol_plugins_enabled', 'guest');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customguest' => '2');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customguest value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for assigning password with guest enrolments
     * globally disabled
     */
    public function testVersion1ImportLogsPasswordSetWithGuestDisabledOnCourseCreate() {
        //setup
        set_config('enrol_plugins_enabled', 'guest');

        //data
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'guest' => '0',
                      'password' => 'rlippassword');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. guest enrolment plugin cannot be assigned a password because the guest enrolment plugin is not enabled.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for assigning a password with guest enrolments
     * not being added to new courses by default
     */
    public function testVesion1ImportLogsPasswordSetWithGuestUnsetOnCourseCreate() {
        //setup
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        //data
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'password' => 'rlippassword');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. guest enrolment plugin cannot be assigned a password because the guest enrolment plugin is not configured to be added to new courses by default.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that visible validation works on course create
     */
    public function testVersion1ImportLogsInvalidVisibleOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'visible', 'customvisible');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customvisible' => '2');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customvisible value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that lang validation works on course create
     */
    public function testVersion1ImportLogsInvalidLangOnCourseCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'lang', 'customlang');

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'customlang' => 'bogus');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customlang value of \"bogus\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that link (template course) validation works on course create
     */
    public function testVersion1ImportLogsInvalidLinkOnCourseCreate() {
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipname',
                      'category' => 'rlipcategory',
                      'link' => 'bogus');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Template course with shortname \"bogus\" could not be found.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that shortname validation works on course update
     */
    public function testVersion1ImportLogsInvalidShortnameOnCourseUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'update',
                      'customshortname' => 'rlipshortname');

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. customshortname value of \"rlipshortname\" does not refer to a valid course.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateFormat() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'format', 'customformat');

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
                      'customformat' => 'invalidformat'
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customformat value of \"invalidformat\" does not refer to a valid course format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateNumSections() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'numsections', 'customnumsections');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $invalidmaxsections = $maxsections + 1;
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'customnumsections' => $invalidmaxsections
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customnumsections value of \"{$invalidmaxsections}\" is not one of the available options (0 .. {$maxsections}).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateStartDate() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'customstartdate' => 'bogus'
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customstartdate value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateNewsItems() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'newsitems', 'customnewsitems');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'customnewsitems' => 100
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customnewsitems value of \"100\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateShowGrades() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'showgrades', 'customshowgrades');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array('action' => 'update',
                      'shortname' => 'cm2',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'customshowgrades' => 3
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customshowgrades value of \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateShowReports() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'showreports', 'customshowreports');

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
                      'customshowreports' => 3
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customshowreports value of \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateMaxBytes() {
        global $CFG;

        //create mapping record
        $this->create_mapping_record('course', 'maxbytes', 'custommaxbytes');

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
                      'custommaxbytes' => $invalidmaxbytes
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. custommaxbytes value of \"{$invalidmaxbytes}\" is not one of the available options.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateGuest() {
        global $CFG;
        $this->create_contexts_and_site_course();
        $this->load_csv_data();
        $this->create_test_course();

        //create mapping record
        $this->create_mapping_record('course', 'guest', 'customguest');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'format' => 'weeks',
                      'numsections' => $maxsections,
                      'startdate' => 'jan/12/2013',
                      'newsitems' => 5,
                      'showgrades' => 1,
                      'showreports' => 0,
                      'maxbytes' => $maxbytes,
                      'customguest' => 'invalidguest'
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. customguest value of \"invalidguest\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for attempting to set the guest flag with the guest enrolment
     * plugin globally disabled
     */
    public function testVersion1ImportLogsGuestWithGuestDisabledOnCourseUpdate() {
        global $CFG;
        //setup
        $this->load_csv_data();

        set_config('enrol_plugins_enabled', '');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        //data
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
        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. guest enrolments cannot be enabled because the guest enrolment plugin is globally disabled.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for attempting to set the guest flag with the guest enrolment
     * plugin removed from the course
     */
    public function testVersion1ImportLogsGuestWithGuestUnsetOnCourseUpdate() {
        //setup
        set_config('enrol_plugins_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        $this->create_contexts_and_site_course();
        $this->create_test_course();

        //data
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'guest' => 1);
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment plugin cannot be enabled because the guest enrolment plugin has been removed from course \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for attempting to set the guest password with the guest enrolment
     * plugin removed from the course
     */
    public function testVesion1ImportLogsPasswordSetWithGuestUnsetOnCourseUpdate() {
        //setup
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        $this->create_contexts_and_site_course();
        $this->create_test_course();

        //data
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'password' => 'rlippassword');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment plugin cannot be assigned a password because the guest enrolment plugin has been removed from course \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for attempting to set guest password while disabling
     * the guest enrolment plugin
     */
    public function testVersion1ImportLogsPasswordSetWithGuestUnsetOnCourseUpdate() {
        //setup
        set_config('enrol_plugins_enabled', 'guest');
        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_guest');

        $this->create_contexts_and_site_course();
        $this->create_test_course();

        //data
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'guest' => '0',
                      'password' => 'rlippassword');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment plugin cannot be assigned a password because the guest enrolment plugin has been disabled in course \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for attempting to set guest password while disabling
     * the guest enrolment plugin, when the guest plugin was previously added and disabled
     */
    public function testVesion1ImportLogsPasswordSetWithGuestUnsetAndPreviouslyDisabledOnCourseUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_DISABLED, 'enrol_guest');

        $this->create_contexts_and_site_course();
        $this->create_test_course();

        //data
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'password' => 'rlippassword');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment plugin cannot be assigned a password because the guest enrolment plugin has been disabled in course \"rlipshortname\".\n";

        //validation
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateVisible() {
        global $CFG;
        $this->load_csv_data();

        //create mapping record
        $this->create_mapping_record('course', 'visible', 'customvisible');

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
                      'customvisible' => 'invalidvisible',
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customvisible value of \"invalidvisible\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    public function testVersion1ImportLogsUpdateCourseLang() {
        global $CFG;
        $this->load_csv_data();

        //create mapping record
        $this->create_mapping_record('course', 'lang', 'customlang');

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
                      'customlang' => 'invalidlang'
                     );

        $expected_error = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customlang value of \"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that shortname validation works on course delete
     */
    public function testVersion1ImportLogsInvalidShortnameOnCourseDelete() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'delete',
                      'customshortname' => 'rlipshortname');
        $expected_error = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be deleted. customshortname value of \"rlipshortname\" does not refer to a valid course.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that a manual import log file generates the proper name
     */
    function testVersion1ImportLogName() {
        global $CFG;

        //pass manual and then scheduled and a timestamp and verify that the name is correct
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $entity = 'user';
        $timestamp = time();
        $format = get_string('logfile_timestamp','block_rlip');
        $entity = 'user';

        $filename = rlip_log_file_name($plugin_type, $plugin, '', $entity, $manual, $timestamp);
        $testfilename = $filepath.'/'.$plugin_type.'_version1_manual_'.$entity.'_'.userdate($timestamp, $format).'.log';
        //get most recent logfile +1 as that is what is returned by rlip_log_file_name
        $testfilename = self::get_next_logfile($testfilename);

        $this->assertEquals($filename, $testfilename);
    }

    /**
     * Test an invalid log file path
     */
    function testVersion1ImportInvalidLogPath() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $filepath = $CFG->dataroot.'/invalidlogpath';

        //create a folder and make it executable only
        mkdir($filepath, 0100);

        set_config('logfilelocation', 'invalidlogpath', 'rlipimport_version1');

        // do a fake import that should create an error in the database
        // check for that error
        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');

        $provider = new rlip_importprovider_fsloguser($data);
        $manual = true;
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, $manual);
        //for now suppress output generated
        ob_start();
        $instance->run();
        ob_end_clean();

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'Log file access failed while importing lines from import file user.csv due to invalid logfile path.'.
                                      ' Change \'invalidlogpath\' to a valid logfile location on the settings page. Processed 0 of 1 records.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        //cleanup the new folder
        if (file_exists($filepath)) {
            rmdir($filepath);
        }
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that a manual import log file generates the correct log file
     */
    function testVersion1ImportLogManual() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //set the file path to the dataroot
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;

        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');

        $provider = new rlip_importprovider_fsloguser($data);
        $manual = true;
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, $manual);
        //for now suppress output generated
        ob_start();
        $instance->run();
        ob_end_clean();

        //create filename to check for existence
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $format = get_string('logfile_timestamp','block_rlip');
        $entity = 'user';
        $starttime = $DB->get_field(RLIP_LOG_TABLE,'starttime',array('id'=>'1'));
        $testfilename = $filepath.'/'.$plugin_type.'_version1_manual_'.$entity.'_'.userdate($starttime, $format).'.log';
        $testfilename = self::get_current_logfile($testfilename);

        $exists = file_exists($testfilename);
        $this->assertEquals($exists, true);

        // cleanup data file
        @unlink($testfilename);
    }

    /**
     * Validate that a scheduled import log file exists with the proper name
     */
    function testVersion1ImportLogScheduled() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');

        //set the file path to the dataroot
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;

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
                      'type' => 'rlipimport',
                      'userid' => $USER->id);
        $taskid = rlip_schedule_add_job($data);

        //lower bound on starttime
        $starttime = time() - 100;

        //change the next runtime to a day from now
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = $starttime + DAYSECS; //tomorrow?
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipimport_version1'));
        $job->nextruntime = $starttime + DAYSECS; //tomorrow?
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //set up config for plugin so the scheduler knows about our csv file
        set_config('schedule_files_path', $file_path, 'rlipimport_version1');
        set_config('user_schedule_file', $file_name, 'rlipimport_version1');

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //get timestamp from summary log
        $records = $DB->get_records(RLIP_LOG_TABLE, null,'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $format = get_string('logfile_timestamp','block_rlip');

        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $entity = 'user';
        $testfilename = $filepath.'/'.$plugin_type.'_version1_scheduled_'.$entity.'_'.userdate($starttime, $format).'.log';
        $testfilename = self::get_current_logfile($testfilename);

        $exists = file_exists($testfilename);
        $this->assertEquals($exists, true);

        // cleanup test directory & import data file
        @unlink($testdir . $file_name);
        @rmdir($testdir);
    }

     /**
     * Validate that a manual import log file generates the correct log file
     */
    function testVersion1ImportLogSequentialLogFiles() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //set the file path to the dataroot
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;

        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');

        $provider = new rlip_importprovider_fsloguser($data);
        $manual = true;

        //loop through w/o deleting logs and see what happens
        for($i = 0; $i <= 15; $i++) {
            $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, $manual);
            //for now suppress output generated
            ob_start();
            $instance->run();
            ob_end_clean();

            //create filename to check for existence
            $plugin_type = 'import';
            $plugin = 'rlipimport_version1';
            $manual = true;
            $entity = 'user';
            $format = get_string('logfile_timestamp','block_rlip');
            //get most recent record
            $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
            foreach ($records as $record) {
                $starttime = $record->starttime;
                break;
            }

            //get base filename
            $basefilename = $filepath.'/'.$plugin_type.'_version1_manual_'.$entity.'_'.userdate($starttime, $format).'.log';
            //get calculated filename
            $testfilename = self::get_current_logfile($basefilename);

            $exists = file_exists($testfilename);
            $this->assertEquals($exists, true);

        }
        $this->assertEquals($i, 16);

        // cleanup data file
        @unlink($testfilename);
    }

    /**
     * Validate that the correct error message is logged when an import runs
     * too long
     */
    public function testVersion1ImportLogsRuntimeError() {
        global $CFG, $DB;

        //set the file path to the dataroot
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . RLIP_DEFAULT_LOG_PATH;
        set_config('logfilelocation', '', 'rlipimport_version1');

        //set up a "user" import provider, using a single fixed file
        $file_name = 'userfile2.csv';
        // File WILL BE DELETED after import so must copy to moodledata area
        // Note: file_path now relative to moodledata ($CFG->dataroot)
        $file_path = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir . $file_name);
        $provider = new rlip_importprovider_file_delay($CFG->dataroot . $file_path . $file_name, 'user');

        //run the import
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, $manual);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // maxruntime 1 sec
        $ui = ob_get_contents(); // TBD: test this UI string
        ob_end_clean();

        //expected error
        $a = new stdClass;
        $a->entity = $result->entity;
        $a->recordsprocessed = $result->linenumber - 1;
        $a->totalrecords = $result->filelines - 1;
        $expected_error = get_string('manualimportexceedstimelimit_b', 'block_rlip', $a)."\n";

        //validate that a log file was created
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $format = get_string('logfile_timestamp','block_rlip');
        $entity = 'user';
        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $filepath . '/' . $plugin_type . '_version1_manual_' . $entity . '_' .
                        userdate($starttime, $format) . '.log';

        $filename = self::get_current_logfile($testfilename);
        //echo "testVersion1ImportLogsRuntimeError(): logfile ?=> {$filename}\n";
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

        //clean-up data file & test dir
        @unlink($testdir . $file_name);
        @rmdir($testdir);
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the import runs for too long on a manual run
     */
    public function testVersion1ManualImportLogsRuntimeFilesystemError() {
        global $CFG, $DB;

        //set up the log file location
        set_config('logfilelocation', '', 'rlipimport_version1');

        //our import data
        $data = array(array('action', 'username', 'password', 'firstname', 'lastname', 'email', 'city', 'country'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'));

        //import provider that creates an instance of a file plugin that delays two seconds
        //between reading the third and fourth entry
        $provider = new rlip_importprovider_delay_after_three_users($data);
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, NULL, $manual);

        //we should run out of time after processing the second real entry
        ob_start();
        //using three seconds to allow for one slow read when counting lines
        $importplugin->run(0, 0, 3);
        ob_end_clean();

        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        $filename = '';
        foreach ($records as $record) {
            //$starttime = $record->starttime;
            $filename = $record->logpath;
            break;
        }

        //validate that the right log file was created
        $this->assertTrue(file_exists($filename));

        //obtain log file lines
        $contents = file_get_contents($filename);
        $contents = explode("\n", $contents);

        //validate line count, accounting for blank line at end
        $this->assertEquals(count($contents), 4);

        //obtain the line we care about
        $line = $contents[2];
        $expected_error = 'Import processing of entity \'user\' partially processed due to time restrictions. '.
                          'Processed 2 of 3 total records.';

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);
    }

    public function testUserProfileFields() {
        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA',
                      'profile_field_user_profile_field_1' => 'my user profile field value',
                      'profile_field_invalid_user_profile_field_1' => 'my user profile field value',
                      'profile_field_user_profile_field_2' => 'my user profile field value',
                      'profile_field_invalid_user_profile_field_2' => 'my user profile field value',
                      );

        $expected_error = array();
        $expected_error[] = "[user.csv line 1] Import file contains the following invalid user profile field(s): invalid_user_profile_field_1, invalid_user_profile_field_2\n";
        $expected_error[] = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully created.\n";

        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    public function testCourseThemes() {
        //create mapping record
        $this->create_mapping_record('course', 'theme', 'customtheme');

        set_config('allowcoursethemes', 0);
        $data = array('action' => 'create',
                      'shortname' => 'shortname',
                      'fullname' => 'fullname',
                      'customtheme' => 'splash',
                      'category' => 'category');
        $expected_error = "[course.csv line 2] Course with shortname \"shortname\" could not be created. Course themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        set_config('allowcoursethemes', 1);
        $data['customtheme'] = 'invalidtheme';
        $expected_error = "[course.csv line 2] Course with shortname \"shortname\" could not be created. customtheme value of \"invalidtheme\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider method, providing user fields with their maximum lengths
     *
     * @return array Mapping of user fields to their maximum lengths
     */
    public function userMaxFieldLengthProvider() {
        return array(array('username', 100),
                     array('firstname', 100),
                     array('lastname', 100),
                     array('email', 100),
                     array('city', 120),
                     array('idnumber', 255),
                     array('institution', 40),
                     array('department', 30));
    }

    /**
     * Validate that errors for user fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider userMaxFieldLengthProvider
     */
    public function testVersion1ImportLogsUserFieldLengthError($field, $maxlength) {
        //create mapping record
        $this->create_mapping_record('user', $field, 'custom'.$field);

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumber_display = '';
        if ($field == 'username') {
            $username = $value;
        } else if ($field == 'email') {
            $email = $value;
        } else if ($field == 'idnumber') {
            $idnumber_display = ', idnumber "'.$value.'"';
        }
        $expected_error = "[user.csv line 2] User with username \"{$username}\", email \"{$email}\"{$idnumber_display} could not be created. custom{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that field length validation works on user update
     */
    public function testVersion1ImportLogsUserFieldLengthErrorOnUpdate() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array('action' => 'update',
                      'customusername' => $value);
        $expected_error = "[user.csv line 2] User with username \"{$value}\" could not be updated. customusername value of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that field length validation works on user delete
     */
    public function testVersion1ImportLogsUserFieldLengthErrorOnDelete() {
        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array('action' => 'delete',
                      'customusername' => $value);
        $expected_error = "[user.csv line 2] User with username \"{$value}\" could not be deleted. customusername value of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Data provider method, providing course fields with their maximum lengths
     *
     * @return array Mapping of course fields to their maximum lengths
     */
    public function courseMaxFieldLengthProvider() {
        return array(array('fullname', 254),
                     array('shortname', 100),
                     array('idnumber', 100));
    }

    /**
     * Validate that errors for course fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider courseMaxFieldLengthProvider
     */
    public function testVersion1ImportLogsCourseFieldLengthError($field, $maxlength) {
        //create mapping record
        $this->create_mapping_record('course', $field, 'custom'.$field);

        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $shortname = 'rlipshortname';
        if ($field == 'shortname') {
            $shortname = $value;
        }
        $expected_error = "[course.csv line 2] Course with shortname \"{$shortname}\" could not be created. custom{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that field length validation works on course update
     */
    public function testVersion1ImportLogsCourseFieldLengthErrorOnUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $value = str_repeat('x', 101);
        $data = array('action' => 'update',
                      'customshortname' => $value);
        $expected_error = "[course.csv line 2] Course with shortname \"{$value}\" could not be updated. customshortname value of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that field length validation works on course delete
     */
    public function testVersion1ImportLogsCourseFieldLengthErrorOnDelete() {
        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $value = str_repeat('x', 101);
        $data = array('action' => 'delete',
                      'customshortname' => $value);
        $expected_error = "[course.csv line 2] Course with shortname \"{$value}\" could not be deleted. customshortname value of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider method, providing enrolment fields with their maximum lengths
     *
     * @return array Mapping of enrolment fields to their maximum lengths
     */
    public function enrolmentMaxFieldLengthProvider() {
        return array(array('username', 100),
                     array('email', 100),
                     array('idnumber', 255),
                     array('group', 254),
                     array('grouping', 254));
    }

    /**
     * Validate that errors for enrolment fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider enrolmentMaxFieldLengthProvider
     */
    public function testVersion1ImportLogsEnrolmentFieldLengthError($field, $maxlength) {
        //create mapping record
        $this->create_mapping_record('enrolment', $field, 'custom'.$field);

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $username = 'rlipusername';
        if ($field == 'username') {
            $username = $value;
        }
        $identifiers = array();
        if (isset($data['customusername'])) {
            $identifiers[] = "username \"{$value}\"";
        } else if (isset($data['username'])) {
            $identifiers[] = "username \"rlipusername\"";
        }
        if (isset($data['customemail'])) {
            $identifiers[] = "email \"{$value}\"";
        }
        if (isset($data['customidnumber'])) {
            $identifiers[] = "idnumber \"{$value}\"";
        }
        $expected_error = "[enrolment.csv line 2] Enrolment could not be created. custom{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validate that field length validation works on enrolment delete
     */
    public function testVersion1ImportLogsEnrolmentFieldLengthErrorOnDelete() {
        //create mapping record
        $this->create_mapping_record('enrolment', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array('action' => 'delete',
                      'customusername' => $value,
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $expected_error = "[enrolment.csv line 2] Enrolment could not be deleted. customusername value of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validate log message for the action column missing on user import
     */
    public function testVersion1ImportLogsMissingActionColumnOnUserImport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('user', 'action', 'customaction');

        $data = array('username' => 'rlipusername');
        $message = "Import file user.csv was not processed because it is missing the following column: customaction. Please fix the import file and re-upload it.\n";
        $expected_message = "[user.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for all of username, email, idnumber columns
     * missing on user import
     */
    public function testVersion1ImportLogsMissingUserColumnGroup() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array('action' => 'create');
        $message = "Import file user.csv was not processed because one of the following columns is required but all are unspecified: customusername, customemail, customidnumber. Please fix the import file and re-upload it.\n";
        $expected_message = "[user.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for action column missing on course import
     */
    public function testVersion1ImportLogsMissingActionColumnOnCourseImport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array('shortname' => 'rlipshortname');
        $message = "Import file course.csv was not processed because it is missing the following column: customaction. Please fix the import file and re-upload it.\n";
        $expected_message = "[course.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'course');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for a single column missing on course import
     */
    public function testVersion1ImportLogsMissingCourseColumn() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'create');
        $message = "Import file course.csv was not processed because it is missing the following required column: customshortname. Please fix the import file and re-upload it.\n";
        $expected_message = "[course.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'course');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for action column missing on enrolment import
     */
    public function testVersion1ImportLogsMissingActionColumnOnEnrolmentImport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('enrolment', 'action', 'customaction');

        $data = array('username' => 'rlipusername');
        $message = "Import file enrolment.csv was not processed because it is missing the following column: customaction. Please fix the import file and re-upload it.\n";
        $expected_message = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for a single column missing on enrolment import
     */
    public function testVersion1ImportLogsMissingEnrolmentColumn() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'user',
                      'instance' => 'rlipusername');
        $message = "Import file enrolment.csv was not processed because it is missing the following required column: customrole. Please fix the import file and re-upload it.\n";
        $expected_message = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for multiple columns missing on enrolment import
     */
    public function testVersion1ImportLogsMissingEnrolmentColumns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create mapping record
        $this->create_mapping_record('enrolment', 'context', 'customcontext');
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        $data = array('action' => 'create',
                      'username' => 'rlipusername');
        $message = "Import file enrolment.csv was not processed because it is missing the following required columns: customcontext, customrole. Please fix the import file and re-upload it.\n";
        $expected_message = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        //remove newline character
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for an invalid action value for the user
     * entity type
     */
    public function testVersion1ImportLogsInvalidUserAction() {
        //data
        $data = array('action' => 'bogus',
                      'username' => 'testusername');
        $expected_message = "[user.csv line 2] User could not be processed. Action of \"bogus\" is not supported.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_message, 'user');
    }

    /**
     * Validate log message for an invalid action value for the course
     * entity type
     */
    public function testVersion1ImportLogsInvalidCourseAction() {
        //data
        $data = array('action' => 'bogus',
                      'shortname' => 'testshortname');
        $expected_message = "[course.csv line 2] Course could not be processed. Action of \"bogus\" is not supported.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    /**
     * Validate log message for an invalid action value for the enrolment
     * entity type
     */
    public function testVersion1ImportLogsInvalidEnrolmentAction() {
        //data
        $data = array('action' => 'bogus',
                      'username' => 'testusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $expected_message = "[enrolment.csv line 2] Enrolment could not be processed. Action of \"bogus\" is not supported.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validate that general enrolment errors are only displayed for enrolments
     * on the course context on enrolment create
     */
    function testVersion1GeneralEnrolmentMessageIsContextSpecific() {
        //setup
        $userid = $this->create_test_user();
        $roleid = $this->create_test_role();

        //assign a role
        $system_context = get_context_instance(CONTEXT_SYSTEM);
        role_assign($roleid, $userid, $system_context->id);

        //run the import and validate
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'role' => 'rlipshortname');
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validate that general unenrolment errors are only displayed for enrolments
     * on the course context on enrolment delete
     */
    function testVersion1GeneralUnenrolmentMessageIsContextSpecific() {
        //setup
        $this->create_test_user();
        $roleid = $this->create_test_role();

        //run the import and validate
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'system',
                      'role' => 'rlipshortname');
        $expected_message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is not assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expected_message, 'enrolment');
    }

    /**
     * Validate that import update action matching multiple users
     * fails with message(s)
     */
    public function testVersion1ImportFailsWithMessageWhenMatchingMultipleUsers() {
        //setup
        $this->create_contexts_and_site_course();

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $roleid = $this->create_test_role();

        // Create required test users
        $data = array('action'    => 'create',
                      'username'  => 'testuser5162a',
                      'idnumber'  => 'testuser5162a',
                      'email'     => 'tu5162a@noreply.com',
                      'password'  => 'TestPassword!0',
                      'firstname' => 'Test',
                      'lastname'  => 'User5162a',
                      'city'      => 'Waterloo',
                      'country'   => 'CA');
        //run the import
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1',
                                                     $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();

        $data = array('action'    => 'create',
                      'username'  => 'testuser5162b',
                      'idnumber'  => 'testuser5162b',
                      'email'     => 'tu5162b@noreply.com',
                      'password'  => 'TestPassword!0',
                      'firstname' => 'Test',
                      'lastname'  => 'User5162b',
                      'city'      => 'Waterloo',
                      'country'   => 'CA');
        //run the import
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1',
                                                     $provider, NULL, true);
        $instance->run();

        $data = array('action'    => 'create',
                      'username'  => 'testuser5162c',
                      'idnumber'  => 'testuser5162c',
                      'email'     => 'tu5162c@noreply.com',
                      'password'  => 'TestPassword!0',
                      'firstname' => 'Test',
                      'lastname'  => 'User5162c',
                      'city'      => 'Waterloo',
                      'country'   => 'CA');
        //run the import
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1',
                                                     $provider, NULL, true);
        $instance->run();

        // Create update error ...
        $data = array('action'    => 'update',
                      'username'  => 'testuser5162a',
                      'idnumber'  => 'testuser5162b',
                      'email'     => 'tu5162c@noreply.com',
                      'password'  => 'TestPassword!0',
                      'firstname' => 'Test',
                      'lastname'  => 'User5162x',
                      'city'      => 'Waterloo',
                      'country'   => 'CA');

        // Assert failure conditions
        $expected_message = "[user.csv line 2] User with username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber \"testuser5162b\" could not be updated. username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber \"testuser5162b\" matches multiple users.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');

        // Create delete error ...
        $data = array('action'    => 'delete',
                      'username'  => 'testuser5162a',
                      'idnumber'  => 'testuser5162b',
                      'email'     => 'tu5162c@noreply.com',
                      'password'  => 'TestPassword!0',
                      'firstname' => 'Test',
                      'lastname'  => 'User5162x',
                      'city'      => 'Waterloo',
                      'country'   => 'CA');

        // Assert failure conditions
        $expected_message = "[user.csv line 2] User with username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber \"testuser5162b\" could not be deleted. username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber \"testuser5162b\" matches multiple users.\n";
        $this->assert_data_produces_error($data, $expected_message, 'user');
    }

}
