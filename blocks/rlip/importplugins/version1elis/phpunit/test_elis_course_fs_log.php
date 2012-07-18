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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/userfile_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/delay_after_three.class.php');

class version1ELISCourseFSLogTest extends rlip_test {

    protected $backupGlobalsBlacklist = array('DB');

    static function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(RLIP_LOG_TABLE => 'block_rlip',
                     'user' => 'moodle',
                     'crlm_curriculum' => 'elis_program',
                     'crlm_coursetemplate' => 'elis_program',
                     'crlm_course' => 'elis_program',
                     'crlm_curriculum_course' => 'elis_program',
                     'crlm_track' => 'elis_program',
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
                     'crlm_user_moodle' => 'elis_program',
                     'elis_field_categories' => 'elis_core',
                     'elis_field_category_contexts' => 'elis_core',
                     'elis_field_contextlevels' => 'elis_core',
                     'elis_field_data_char' => 'elis_core',
                     'elis_field' => 'elis_core',
                     'elis_field_data_int' => 'elis_core',
                     'elis_field_data_num' => 'elis_core',
                     'elis_field_data_text' => 'elis_core',
                     'elis_field_owner' => 'elis_core');

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
     * Validate that link validation works on course create
     */
    public function testELISCourseInvalidLinkCreate() {
        $data = array('action' => 'create',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcourse',
                      'link' => 'invalidmoodlecourse');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. link value of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that link validation works on course update
     */
    public function testELISCourseInvalidLinkUpdate() {
        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'link' => 'invalidmoodlecourse');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. link value of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that link validation works on course create
     */
    public function testELISCourseInvalidAssignmentCreate() {
        $data = array('action' => 'create',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcourse',
                      'assignment' => 'invalidprogram');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. assignment value of \"invalidprogram\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that assignment validation works on course update
     */
    public function testELISCourseInvalidAssignmentUpdate() {
        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcourse',
                      'assignment' => 'invalidprogram');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. assignment value of \"invalidprogram\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that credit validation works on course creation
     */
    public function testELISCourseInvalidCreditCreate() {
        $data = array('action' => 'create',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcourse',
                      'credits' => '-1');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. transfercredits value of \"-1\" is not a non-negative number.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that credit validation works on course update
     */
    public function testELISCourseInvalidCreditUpdate() {
        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'credits' => '-1');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. transfercredits value of \"-1\" is not a non-negative number.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that completion grade validation works on course create
     */
    public function testELISCourseInvalidCompletionGradeCreate() {
        $data = array('action' => 'create',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcourse',
                      'credits' => '1',
                      'completion_grade' => '-1');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. completion_grade value of \"-1\" is not one of the available options (0 .. 100).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that completion grade validation works on course update
     */
    public function testELISCourseInvalidCompletionGradeUpdate() {
        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'credits' => '1',
                      'completion_grade' => '-1');

        $expected_error = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. completion_grade value of \"-1\" is not one of the available options (0 .. 100).\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate idnumber validation works on course update
     */
    public function testELISCourseInvalidIdnumberUpdate() {
        $this->load_csv_data();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'invalidtestcourseid');

        $expected_error = "[course.csv line 2] Course description with idnumber \"invalidtestcourseid\" could not be updated. idnumber value of \"invalidtestcourseid\" does not refer to a valid course description.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate idnumber validation works on course delete
     */
    public function testELISCourseInvalidIdnumberDelete() {
        $this->load_csv_data();

        $data = array('action' => 'delete',
                      'context' => 'course',
                      'idnumber' => 'invalidtestcourseid');

        $expected_error = "[course.csv line 2] Course description with idnumber \"invalidtestcourseid\" could not be deleted. idnumber value of \"invalidtestcourseid\" does not refer to a valid course description.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('crlm_course', dirname(__FILE__).'/coursetable.csv');
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

}
