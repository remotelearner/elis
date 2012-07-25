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

/*
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

class version1elisFilesystemLoggingTest extends rlip_test {


    protected $backupGlobalsBlacklist = array('DB');


    static function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(RLIP_LOG_TABLE => 'block_rlip',
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
                     'forum' => 'mod_forum',
                     //RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
                     'elis_scheduled_tasks' => 'elis_core',
                     RLIP_SCHEDULE_TABLE => 'block_rlip',
                     RLIP_LOG_TABLE => 'block_rlip',
                     'user' => 'moodle',
                     'user_info_category' => 'moodle',
                     'user_info_field' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'message_working' => 'moodle',
                     'crlm_user' => 'elis_program',
                     'crlm_user_moodle' => 'elis_program');

        return $tables;
    }

    static protected function get_ignored_tables() {
        global $DB;
        $tables = array('block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'log' => 'moodle',
                     'message'            => 'moodle',
                     'message_read'       => 'moodle',
                     'message_working'    => 'moodle',
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
                     '_categories_history' => 'moodle',
                     //'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_categories_history' => 'moodle',
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
                     'external_services_users' => 'moodle',
                     'quiz_attempts' => 'mod_quiz',
                     'quiz_grades' => 'mod_quiz',
                     'quiz_question_instances' => 'mod_quiz',
                     'quiz_feedback' => 'mod_quiz',
                     'quiz' => 'mod_quiz',
                     'url' => 'moodle',
                     'assignment' => 'moodle',
                     'assignment_submissions' => 'moodle',
                     'forum_track_prefs' => 'moodle',
                     'sessions' => 'moodle');

        return $tables;
    }


    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_database($DB, static::get_overlay_tables(), static::get_ignored_tables());

        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
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
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, true);
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
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp','block_rlip');
        $testfilename = $filepath.'/'.$plugin_type.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
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
     * Validate that username validation works on user create
     */
    public function testELISUserUsernameCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be created. username value of \"testusername\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that email validation works on user create
     */
    public function testELISInvalidUserEmailCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'com.mail@user',
                      'city' => 'Waterloo',
                      'birhtdate' => 'JAN/01/2012',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"com.mail@user\", idnumber \"validtestidnumber\" could not be created. email value of \"com.mail@user\" is not a valid email address.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that email2 validation works on user create
     */
    public function testELISInvalidUserEmail2Create() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'email2' => 'com.mail@user',
                      'city' => 'Waterloo',
                      'birthdate' => '2012.01.02',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. email2 value of \"com.mail@user\" is not a valid email address.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that email2 validation works on user update
     */
    public function testELISInvalidUserEmail2Update() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'email2' => 'com.mail@user',
                      'city' => 'Waterloo',
                      'birthdate' => '2012.01.02',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. email2 value of \"com.mail@user\" is not a valid email address.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that country validation works on user create
     */
    public function testELISInvalidUserCountryCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'city' => 'Waterloo',
                      'birthdate' => '01/02/2012',
                      'country' => 'invalidCA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. country value of \"invalidCA\" is not a valid country or country code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that country validation works on user update
     */
    public function testELISInvalidUserCountryUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'birthdate' => '01/02/2012',
                      'country' => 'invalidCA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. country value of \"invalidCA\" is not a valid country or country code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that birthdate validation works on user create
     */
    public function testELISInvalidUserBirthdateCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'city' => 'Waterloo',
                      'birthdate' => '01/02',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. birthdate value of \"01/02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that birthdate validation works on user update
     */
    public function testELISInvalidUserBirthdateUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'birthdate' => '01/02',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. birthdate value of \"01/02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that gender validation works on user create
     */
    public function testELISInvalidUserGenderCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'invalidgender',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. gender value of \"invalidgender\" is not one of the available options (M, male, F, female).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that gender validation works on user update
     */
    public function testELISInvalidUserGenderUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'invalidgender',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. gender value of \"invalidgender\" is not one of the available options (M, male, F, female).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that lang validation works on user create
     */
    public function testELISInvalidUserLangCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'F',
                      'lang' => 'invalidlang',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. language value of \"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that lang validation works on user update
     */
    public function testELISInvalidUserLangUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'F',
                      'lang' => 'invalidlang',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. language value of \"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that transfer credits validation works on user create
     */
    public function testELISInvalidUserTransferCreditsCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'user@validmail.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'F',
                      'lang' => 'en',
                      'transfercredits' => -1,
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber \"validtestidnumber\" could not be created. transfercredits value of \"-1\" is not a non-negative integer.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that transfer credits validation works on user update
     */
    public function testELISInvalidUserTransferCreditsUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'lang' => 'en',
                      'gender' => 'F',
                      'lang' => 'en',
                      'transfercredits' => -1,
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. transfercredits value of \"-1\" is not a non-negative integer.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that inactive validation works on user create
     */
    public function testELISUserInvalidInactiveCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'validtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@validmail.com',
                      'city' => 'Waterloo',
                      'inactive' => 2,
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"test@validmail.com\", idnumber \"validtestidnumber\" could not be created. inactive value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
   }

    /**
     * Validate that inactive validation works on user create
     */
    public function testELISUserInvalidInactiveUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'inactive' => 2,
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. inactive value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
   }

    /**
     * Validate that idnumber validation works on user create
     */
    public function testELISUserIdnumberCreate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'create',
                      'username' => 'validtestusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'validtest@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"validtestusername\", email \"validtest@user.com\", idnumber \"testidnumber\" could not be created. idnumber value of \"testidnumber\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that username validation works on user update
     */
    public function testELISUserUsernameUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'invalidtestusername',
                      'idnumber' => 'testidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"invalidtestusername\", email \"test@user.com\", idnumber \"testidnumber\" could not be updated. username value of \"invalidtestusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that email validation works on user update
     */
    public function testELISUserIdnumberUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'testusername',
                      'idnumber' => 'invalidtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'test@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"invalidtestidnumber\" could not be updated. idnumber value of \"invalidtestidnumber\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate multiple invalid fields on user update
     */
    public function testELISUserMultipleInvalidFieldUpdate() {
        global $CFG, $DB;

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'username' => 'invalidtestusername',
                      'idnumber' => 'invalidtestidnumber',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'invalidtest@user.com',
                      'city' => 'Waterloo',
                      'country' => 'CA');

        $expected_error = "[user.csv line 2] User with username \"invalidtestusername\", email \"invalidtest@user.com\", idnumber \"invalidtestidnumber\" could not be updated. username value of \"invalidtestusername\", email value of \"invalidtest@user.com\", idnumber value of \"invalidtestidnumber\" do not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user create */
    public function testUserInvalidIdentifyingFieldsOnCreate() {
        global $CFG, $DB;

        $data = array(
            'action' => 'create'
        );
        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => '',
            'username' => ''
        );
        $expected_error = "[user.csv line 2] User could not be processed. Required field action is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'username' => '',
            'email' => '',
            'idnumber' => ''
        );

        $expected_error = "[user.csv line 2] User could not be created. Required fields idnumber, username, firstname, lastname, email, country are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'username' => 'testusername'
        );
        $expected_error = "[user.csv line 2] User with username \"testusername\" could not be created. Required fields idnumber, firstname, lastname, email, country are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'email' => 'user@mail.com'
        );
        $expected_error = "[user.csv line 2] User with email \"user@mail.com\" could not be created. Required fields idnumber, username, firstname, lastname, country are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'idnumber' => 'testidnumber'
        );
        $expected_error = "[user.csv line 2] User with idnumber \"testidnumber\" could not be created. Required fields username, firstname, lastname, email, country are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'email' => 'user@mail.com',
            'idnumber' => 'testidnumber'
        );
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"user@mail.com\", idnumber \"testidnumber\" could not be created. Required fields firstname, lastname, country are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'email' => 'user@mail.com',
            'idnumber' => 'testidnumber',
            'firstname' => 'dsfds',
            'lastname' => 'sadfs'
        );
        $expected_error = "[user.csv line 2] User with username \"testusername\", email \"user@mail.com\", idnumber \"testidnumber\" could not be created. Required field country is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user update */
    public function testUserInvalidIdentifyingFieldsOnUpdate() {
        global $CFG, $DB;

        $data = array(
            'action' => 'update',
            'username' => 'testusername'
        );

        $expected_error = "[user.csv line 2] User with username \"testusername\" could not be updated. username value of \"testusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'update',
            'email' => 'user@mail.com'
        );
        $expected_error = "[user.csv line 2] User with email \"user@mail.com\" could not be updated. email value of \"user@mail.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'update',
            'idnumber' => 'testidnumber'
        );
        $expected_error = "[user.csv line 2] User with idnumber \"testidnumber\" could not be updated. idnumber value of \"testidnumber\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'update',
            'username' => '',
            'email' => '',
            'idnumber' => ''
        );

        $expected_error = "[user.csv line 2] User could not be updated. One of username, email, idnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'update',
        );
        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user delete */
    public function testUserInvalidIdentifyingFieldsOnDelete() {
        global $CFG, $DB;

        $data = array(
            'action' => 'delete',
            'username' => 'testusername'
        );

        $expected_error = "[user.csv line 2] User with username \"testusername\" could not be deleted. username value of \"testusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');


        $data = array(
            'action' => 'delete',
            'email' => 'user@mail.com'
        );
        $expected_error = "[user.csv line 2] User with email \"user@mail.com\" could not be deleted. email value of \"user@mail.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'delete',
            'idnumber' => 'testidnumber'
        );
        $expected_error = "[user.csv line 2] User with idnumber \"testidnumber\" could not be deleted. idnumber value of \"testidnumber\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'delete',
            'username' => '',
            'email' => '',
            'idnumber' => ''
        );

        $expected_error = "[user.csv line 2] User could not be deleted. One of username, email, idnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array(
            'action' => 'delete',
        );
        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate log message for an invalid action value for the user
     * entity type
     */
    public function testLogsInvalidUserAction() {
        //data
        $data = array('action' => 'bogus',
                      'username' => 'testusername');
        $expected_message = "[user.csv line 2] User could not be processed. Action of \"bogus\" is not supported.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_message, 'user');
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

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('crlm_user', dirname(__FILE__).'/usertable.csv');
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

}
