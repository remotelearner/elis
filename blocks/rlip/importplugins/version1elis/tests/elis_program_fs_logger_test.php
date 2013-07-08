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

class version1ELISProgramFSLogTest extends rlip_test {

    protected $backupGlobalsBlacklist = array('DB');

    static function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $tables = array(
                     'user' => 'moodle',
                     'crlm_curriculum' => 'elis_program',
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
                     RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
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
        $provider = new $classname($data, 'program.csv');
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
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }

    /**
     * Validate that required credits validation works on program create
     */
    public function testELISProgramInvalidReqCreditsFormatCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'customreqcredits' => '10.000');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customreqcredits value of \"10.000\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testELISProgramInvalidReqCreditsFormatUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');


        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'customreqcredits' => '10.000');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customreqcredits value of \"10.000\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that required credits validation works on program create
     */
    public function testELISProgramInvalidReqCreditsFormat2Create() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');


        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'customreqcredits' => '10.0.0');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customreqcredits value of \"10.0.0\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testELISProgramInvalidReqCreditsFormat2Update() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');


        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'customreqcredits' => '10.0.0');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customreqcredits value of \"10.0.0\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that required credits validation works on program create
     */
    public function testELISProgramInvalidReqCreditsFormat3Create() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprorgam',
                      'customreqcredits' => '100000000000.00');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customreqcredits value of \"100000000000.00\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testELISProgramInvalidReqCreditsFormat3Update() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprorgam',
                      'customreqcredits' => '100000000000.00');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customreqcredits value of \"100000000000.00\" is not a number with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that time to complete validation works on program create
     */
    public function testELISProgramInvalidTimetoCompleteFormat1Create() {
        //create mapping record
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'customtimetocomplete' => '1x');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customtimetocomplete value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that time to complete validation works on program update
     */
    public function testELISProgramInvalidTimetoCompleteFormat1Update() {
        //create mapping record
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'customtimetocomplete' => '1x');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customtimetocomplete value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that time to complete validation works on program create
     */
    public function testELISProgramInvalidTimetoCompleteFormat2Create() {
        //create mapping record
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprorgam',
                      'reqcredits' => '10',
                      'customtimetocomplete' => '1');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customtimetocomplete value of \"1\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that time to complete validation works on program update
     */
    public function testELISProgramInvalidTimetoCompleteFormat2Update() {
        //create mapping record
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'customtimetocomplete' => '1');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customtimetocomplete value of \"1\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that frequency validation works on program create
     */
    public function testELISProgramFrequencyConfigNotSetCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'customfrequency' => '1d');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "Program customfrequency / expiration cannot be set because program expiration is globally disabled.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that frequency validation works on program update
     */
    public function testELISProgramFrequencyConfigNotSetUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'customfrequency' => '1d');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "Program customfrequency / expiration cannot be set because program expiration is globally disabled.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that frequency validation works on program create
     */
    public function testELISProgramInvalidFrequencyCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'customfrequency' => '1x');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "customfrequency value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that frequency validation works on program updatee
     */
    public function testELISProgramInvalidFrequencyUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'customfrequency' => '1x');

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "customfrequency value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that priority validation works on program create
     */
    public function testELISProgramInvalidPriorityCreate() {
        //create mapping record
        $this->create_mapping_record('course', 'priority', 'custompriority');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'frequency' => '1d',
                      'custompriority' => 11);

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. " .
                          "custompriority value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that priority validation works on program update
     */
    public function testELISProgramInvalidPriorityUpdate() {
        //create mapping record
        $this->create_mapping_record('course', 'priority', 'custompriority');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'name' => 'testprogram',
                      'reqcredits' => '10',
                      'timetocomplete' => '1d',
                      'frequency' => '1d',
                      'custompriority' => 11);

        $expected_error = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. " .
                          "custompriority value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that validation works on program delete
     */
    public function testELISProgramInvalidProgramIdDelete() {
        //create mapping record
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array('action' => 'delete',
                      'context' => 'curriculum',
                      'idnumber' => 'invalidtestprogramidnumber');

        $expected_error = "[program.csv line 2] Program with idnumber \"invalidtestprogramidnumber\" could not be deleted. " .
                          "idnumber value of \"invalidtestprogramidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program create */
    public function testProgramInvalidIdentifyingFieldsOnCreate() {
        global $CFG, $DB;

        //create mapping record
        $this->create_mapping_record('course', 'context', 'customcontext');

        $data = array(
            'action' => 'create'
        );
        $expected_error = "[program.csv line 1] Import file program.csv was not processed because it is missing the following required column: customcontext. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array(
            'action' => 'create',
            'customcontext' => ''
        );
        $expected_error = "[program.csv line 2] Entity could not be created.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array(
            'action' => '',
            'customcontext' => ''
        );
        $expected_error = "[program.csv line 2] Entity could not be processed.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //create mapping record
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array(
            'customaction' => '',
            'customcontext' => 'curriculum'
        );
        $expected_error = "[program.csv line 2] Program could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //create mapping record
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customidnumber' => '',
        );
        $expected_error = "[program.csv line 2] Program could not be created. Required fields customidnumber, customname are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customidnumber' => 'testidnumber'
        );
        $expected_error = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be created. Required field customname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

       $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customname' => 'testname'
        );
        $expected_error = "[program.csv line 2] Program could not be created. Required field customidnumber is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program update */
    public function testTrackInvalidIdentifyingFieldsOnUpdate() {
        global $CFG, $DB;

        //create mapping record
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');


        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'customidnumber' => '',
        );
        $expected_error = "[program.csv line 2] Program could not be updated. Required field customidnumber is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'customidnumber' => 'testidnumber',
        );
        $expected_error = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be updated. customidnumber value of \"testidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program delete */
    public function testTrackInvalidIdentifyingFieldsOnDelete() {
        global $CFG, $DB;

        //create mapping record
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');


        $data = array(
            'action' => 'delete',
            'context' => 'curriculum',
            'customidnumber' => '',
        );
        $expected_error = "[program.csv line 2] Program could not be deleted. Required field customidnumber is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum',
            'customidnumber' => 'testidnumber',
        );
        $expected_error = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be deleted. customidnumber value of \"testidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate log message for an invalid action value for the program
     * entity type
     */
    public function testLogsInvalidProgramAction() {
        //data
        $data = array('action' => 'bogus',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber');
        $expected_message = "[program.csv line 2] Program could not be processed. Action of \"bogus\" is not supported.\n";

        //validation
        $this->assert_data_produces_error($data, $expected_message, 'course');
    }

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('crlm_curriculum', dirname(__FILE__).'/programtable.csv');
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

}
