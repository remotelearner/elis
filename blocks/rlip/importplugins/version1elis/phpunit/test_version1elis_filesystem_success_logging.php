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
 * @subpackage rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(__FILE__) .'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/phpunit/rlip_mock_provider.class.php');

/**
 * Class for testing file-system log success messages as created by the Version 1
 * ELIS import plugin
 */
class version1elisFilesystemSuccessLoggingTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        return array(
            'context'                             => 'moodle',
            'role'                                => 'moodle',
            'user'                                => 'moodle',
            RLIP_LOG_TABLE                        => 'block_rlip',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            clusterassignment::TABLE              => 'elis_program',
            curriculumcourse::TABLE               => 'elis_program',
            course::TABLE                         => 'elis_program',
            curriculum::TABLE                     => 'elis_program',
            curriculumstudent::TABLE              => 'elis_program',
            instructor::TABLE                     => 'elis_program',
            pmclass::TABLE                        => 'elis_program',
            student::TABLE                        => 'elis_program',
            track::TABLE                          => 'elis_program',
            trackassignment::TABLE                => 'elis_program',
            user::TABLE                           => 'elis_program',
            usermoodle::TABLE                     => 'elis_program',
            usertrack::TABLE                      => 'elis_program',
            userset::TABLE                        => 'elis_program',
            waitlist::TABLE                       => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/waitlist.class.php'));

        return array(
            'block_instances'         => 'moodle',
            'block_positions'         => 'moodle',
            'cohort_members'          => 'moodle',
            'comments'                => 'moodle',
            'cache_flags'             => 'moodle',
            'events_queue'            => 'moodle',
            'events_queue_handlers'   => 'moodle',
            'external_services_users' => 'moodle',
            'external_tokens'         => 'moodle',
            'files'                   => 'moodle',
            'filter_active'           => 'moodle',
            'filter_config'           => 'moodle',
            'groups_members'          => 'moodle',
            'log'                     => 'moodle',
            'rating'                  => 'moodle',
            'role_assignments'        => 'moodle',
            'role_capabilities'       => 'moodle',
            'role_names'              => 'moodle',
            'sessions'                => 'moodle',
            'user_enrolments'         => 'moodle',
            'user_info_data'          => 'moodle',
            'user_lastaccess'         => 'moodle',
            'user_preferences'        => 'moodle',
            classmoodlecourse::TABLE  => 'elis_program',
            clustercurriculum::TABLE  => 'elis_program',
            clustertrack::TABLE       => 'elis_program',
            coursetemplate::TABLE     => 'elis_program',
            field_data_char::TABLE    => 'elis_program',
            field_data_int::TABLE     => 'elis_program',
            field_data_num::TABLE     => 'elis_program',
            field_data_text::TABLE    => 'elis_program',
            student_grade::TABLE      => 'elis_program',
            trackassignment::TABLE    => 'elis_program',
            userset_profile::TABLE    => 'elis_program',
            waitlist::TABLE           => 'elis_program'
        );
    }

    public function setUp() {
        global $DB, $USER;

        parent::setUp();

        $DB = self::$origdb;

        $admin = get_admin();
        $USER = $admin;
        $GLOBALS['USER'] = $USER;

        $DB = self::$overlaydb;
    }

    /**
     * Validates that the supplied data produces the expected message
     *
     * @param array  $data The import data to process
     * @param string $expected_message The error we are expecting (message only)
     * @param user   $entitytype One of 'user', 'course', 'enrolment'
     * @param string $importfilename  name of import file
     */
    protected function assert_data_produces_message($data, $expected_message, $entitytype, $importfilename = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set the log file location
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data, $importfilename);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();
        ob_end_clean();

        //validate that a log file was created
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
        $testfilename = $filepath.'/'.$plugin_type.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        //get most recent logfile

        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename), "\n Can't find logfile: {$filename} for \n{$testfilename}");

        //fetch log line
        $pointer = fopen($filename, 'r');

        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');

        while (!feof($pointer)) {
            $error = fgets($pointer);
            if (!empty($error)) { // could be an empty new line
                if (is_array($expected_message)) {
                    $actual_message[] = substr($error, $prefix_length);
                } else {
                    $actual_message = substr($error, $prefix_length);
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expected_message, $actual_message);
    }

    /**
     * Data provider that specifies information for user actions
     *
     * @return array Data, as expected by the test method
     */
    public function userDataProvider() {
        //parameters specific to the user create action
        $user_create_params = array(
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        );

        return array(
            array('create', 'testuserusername', 'testuser@email.com', 'testuseridnumber', false, $user_create_params),
            array('update', 'testuserusername', '', '', true, array()),
            array('update', '', 'testuser@email.com', '', true, array()),
            array('update', '', '', 'testuseridnumber', true, array()),
            array('update', 'testuserusername', 'testuser@email.com', '', true, array()),
            array('update', 'testuserusername', '', 'testuseridnumber', true, array()),
            array('update', '', 'testuser@email.com', 'testuseridnumber', true, array()),
            array('update', 'testuserusername', 'testuser@email.com', 'testuseridnumber', true, array()),
            array('delete', 'testuserusername', '', '', true, array()),
            array('delete', '', 'testuser@email.com', '', true, array()),
            array('delete', '', '', 'testuseridnumber', true, array()),
            array('delete', 'testuserusername', 'testuser@email.com', '', true, array()),
            array('delete', 'testuserusername', '', 'testuseridnumber', true, array()),
            array('delete', '', 'testuser@email.com', 'testuseridnumber', true, array()),
            array('delete', 'testuserusername', 'testuser@email.com', 'testuseridnumber', true, array()),
        );
    }

    /**
     * Create a test user PM user
     */
    private function create_test_user() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'testuser@email.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();
    }

    /**
     * Obtains the string used to identify a test user
     *
     * @param string $username The test user's username
     * @param string $email The test user's email
     * @param string $idnumber The test user's idnumber
     * @return string The string user to identify the user in log messages
     */
    private function get_user_identifier($username, $email, $idnumber) {
        //string representing identifying fields
        $identifier_parts  = array();
        if ($username != '') {
            $identifier_parts[] = 'username "'.$username.'"';
        }
        if ($email != '') {
            $identifier_parts[] = 'email "'.$email.'"';
        }
        if ($idnumber != '') {
            $identifier_parts[] = 'idnumber "'.$idnumber.'"';
        }
        $identifier = implode(', ', $identifier_parts);

        return $identifier;
    }

    /**
     * Validate that user actions create success log messages
     *
     * @dataProvider userDataProvider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $username The value being specified for username
     * @param string $email The value being specified for email
     * @param string $idnumber The value being specified for idnumber
     * @param boolean $create_user Create the sample user before processing the action, if true
     * @param array $extra_params Extra parameters specified, beyond identifying fields
     */
    public function testUserActionCreatesLogMessage($action, $username, $email, $idnumber, $create_user, $extra_params) {
        if ($create_user) {
            //needed for upddate / delete actions
            $this->create_test_user();
        }

        //merge base data with extra parameters
        $data = array(
            'action' => $action,
            'username' => $username,
            'email' => $email,
            'idnumber' => $idnumber
        );
        $data = array_merge($data, $extra_params);

        //string representing identifying fields
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        //validation
        $expected_message = "[user.csv line 2] User with {$identifier} successfully {$action}d.\n";
        $this->assert_data_produces_message($data, $expected_message, 'user');
    }

    /**
     * Load all necessary CSV data
     *
     * @param boolean $create_program Set to true to create the test program
     * @param boolean $create_track Set to true to create the test track
     * @param boolean $create_course Set to true to create the test course description
     * @param boolean $create_class Set to true to create the test class instance
     * @param boolean $create_userset Set to true to create the test user set
     * @param boolean $create_user Set to true to create the test user
     */
    private function load_csv_data($create_program, $create_track, $create_course, $create_class, $create_userset, $create_user) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        //load PM entities from CSV files
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    if ($create_program) {
	        $dataset->addTable(curriculum::TABLE, dirname(__FILE__).'/phpunit_program_success.csv');
	    }
        if ($create_track) {
	        $dataset->addTable(track::TABLE, dirname(__FILE__).'/phpunit_track_success.csv');
	    }
        if ($create_course) {
	        $dataset->addTable(course::TABLE, dirname(__FILE__).'/phpunit_course_success.csv');
	    }
        if ($create_class) {
	        $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/phpunit_class_success.csv');
	    }
        if ($create_userset) {
	        $dataset->addTable(userset::TABLE, dirname(__FILE__).'/phpunit_userset_success.csv');
	    }
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        if ($create_user) {
            //need to use API to create Moodle user
	        $user = new user(array(
	            'username' => 'testuserusername',
	            'email' => 'testuser@email.com',
	            'idnumber' => 'testuseridnumber',
	            'firstname' => 'testuserfirstname',
	            'lastname' => 'testuserlastname',
	            'country' => 'CA'
	        ));
	        $user->save();
	    }
    }

    /**
     * Data provider that specifies information for PM entity actions
     *
     * @return array Data, as expected by the test method
     */
    public function pmentityDataProvider() {
        //parameters needed for specific cases
        $program_create_params = array(
            'name' => 'testprogramname'
        );
        $track_create_params = array(
            'assignment' => 'testprogramidnumber',
            'name' => 'testtrackname'
        );
        $course_create_params = array(
            'name' => 'testcoursename'
        );
        $class_create_params = array(
            'assignment' => 'testcourseidnumber'
        );

        return array(
            array('create', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', $program_create_params),
            array('update', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', array()),
            array('delete', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', array()),
            array('create', 'track', 'Track', 'idnumber', 'testtrackidnumber', $track_create_params),
            array('update', 'track', 'Track', 'idnumber', 'testtrackidnumber', array()),
            array('delete', 'track', 'Track', 'idnumber', 'testtrackidnumber', array()),
            array('create', 'course', 'Course description', 'idnumber', 'testcourseidnumber', $course_create_params),
            array('update', 'course', 'Course description', 'idnumber', 'testcourseidnumber', array()),
            array('delete', 'course', 'Course description', 'idnumber', 'testcourseidnumber', array()),
            array('create', 'class', 'Class instance', 'idnumber', 'testclassidnumber', $class_create_params),
            array('update', 'class', 'Class instance', 'idnumber', 'testclassidnumber', array()),
            array('delete', 'class', 'Class instance', 'idnumber', 'testclassidnumber', array()),
            array('create', 'cluster', 'User set', 'name', 'testusersetname', array()),
            array('update', 'cluster', 'User set', 'name', 'testusersetname', array()),
            array('delete', 'cluster', 'User set', 'name', 'testusersetname', array())
        );
    }

    /**
     * Validate that PM entity actions create success log messages
     *
     * @dataProvider pmentityDataProvider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $context The context import field
     * @param string $context_description The human-readable context level description
     * @param string $uniquefield The shortname of the unique identifying field
     * @param string $uniquevalue The unique identifying value
     * @param $extra_params Extra parameter specified, beyond identifying fields
     */
    public function testPmentityActionCreatesLogMessage($action, $context, $context_description, $uniquefield,
                                                        $uniquevalue, $extra_params) {
        //combine base data with extra info
        $data = array(
            'action' => $action,
            'context' => $context,
            $uniquefield => $uniquevalue
        );
        $data = array_merge($data, $extra_params);

        //set up appropriate data to satisfy any necessary dependencies
        $create_program = $context == 'track' || $context == 'curriculum' && $action != 'create';
        $create_track = $context == 'track' && $action != 'create';
        $create_course = $context == 'class' || $context == 'course' && $action != 'create';
        $create_class = $context == 'class' && $action != 'create';
        $create_userset = $context == 'cluster' && $action != 'create';
        $this->load_csv_data($create_program, $create_track, $create_course, $create_class, $create_userset, false);

        //validation
        $expected_message = "[course.csv line 2] {$context_description} with {$uniquefield} \"$uniquevalue\" successfully {$action}d.\n";
        $this->assert_data_produces_message($data, $expected_message, 'course');
    }

    /**
     * Data provider that specifies information for enrolment entity actions
     *
     * @return array Data, as expected by the test method
     */
    public function enrolmentDataProvider() {
        //extra parameters needed in specific cases
        $instructor_params = array(
            'role' => 'instructor'
        );
        $role_params = array(
            'role' => 'testroleshortname'
        );

        return array(
            //program enrol
            array('create', 'curriculum_testprogramidnumber', 'testuserusername', '', '', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', '', 'testuser@email.com', '', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', '', '', 'testuseridnumber', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', 'testuserusername', 'testuser@email.com', '', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', 'testuserusername', '', 'testuseridnumber', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', '', 'testuser@email.com', 'testuseridnumber', 'enrolled in program "testprogramidnumber".', array()),
            array('create', 'curriculum_testprogramidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'enrolled in program "testprogramidnumber".', array()),
            //program unenrol
            array('delete', 'curriculum_testprogramidnumber', 'testuserusername', '', '', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', '', 'testuser@email.com', '', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', '', '', 'testuseridnumber', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', 'testuserusername', 'testuser@email.com', '', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', 'testuserusername', '', 'testuseridnumber', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', '', 'testuser@email.com', 'testuseridnumber', 'unenrolled from program "testprogramidnumber".', array()),
            array('delete', 'curriculum_testprogramidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unenrolled from program "testprogramidnumber".', array()),
            //track enrol
            array('create', 'track_testtrackidnumber', 'testuserusername', '', '', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', '', 'testuser@email.com', '', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', '', '', 'testuseridnumber', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', 'testuserusername', 'testuser@email.com', '', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', 'testuserusername', '', 'testuseridnumber', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', '', 'testuser@email.com', 'testuseridnumber', 'enrolled in track "testtrackidnumber".', array()),
            array('create', 'track_testtrackidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'enrolled in track "testtrackidnumber".', array()),
            //track unenrol
            array('delete', 'track_testtrackidnumber', 'testuserusername', '', '', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', '', 'testuser@email.com', '', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', '', '', 'testuseridnumber', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', 'testuserusername', 'testuser@email.com', '', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', 'testuserusername', '', 'testuseridnumber', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', '', 'testuser@email.com', 'testuseridnumber', 'unenrolled from track "testtrackidnumber".', array()),
            array('delete', 'track_testtrackidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unenrolled from track "testtrackidnumber".', array()),
            //user set enrol
            array('create', 'cluster_testusersetname', 'testuserusername', '', '', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', '', 'testuser@email.com', '', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', '', '', 'testuseridnumber', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', 'testuserusername', 'testuser@email.com', '', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', 'testuserusername', '', 'testuseridnumber', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', '', 'testuser@email.com', 'testuseridnumber', 'enrolled in user set "testusersetname".', array()),
            array('create', 'cluster_testusersetname', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'enrolled in user set "testusersetname".', array()),
            //user set unenrol
            array('delete', 'cluster_testusersetname', 'testuserusername', '', '', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', '', 'testuser@email.com', '', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', '', '', 'testuseridnumber', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', 'testuserusername', 'testuser@email.com', '', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', 'testuserusername', '', 'testuseridnumber', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', '', 'testuser@email.com', 'testuseridnumber', 'unenrolled from user set "testusersetname".', array()),
            array('delete', 'cluster_testusersetname', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unenrolled from user set "testusersetname".', array()),
            //(student) class enrol
            array('create', 'class_testclassidnumber', 'testuserusername', '', '', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', '', 'testuser@email.com', '', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', '', '', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', '', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', 'testuserusername', '', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', '', 'testuser@email.com', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            array('create', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as a student.', array()),
            //(student) class unenrol
            array('delete', 'class_testclassidnumber', 'testuserusername', '', '', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', '', 'testuser@email.com', '', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', '', '', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', '', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', 'testuserusername', '', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', '', 'testuser@email.com', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            array('delete', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as a student.', array()),
            //(instructor) class enrol
            array('create', 'class_testclassidnumber', 'testuserusername', '', '', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', '', 'testuser@email.com', '', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', '', '', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', '', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', 'testuserusername', '', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', '', 'testuser@email.com', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('create', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'enrolled in class instance "testclassidnumber" as an instructor.', $instructor_params),
            //(instructor) class unenrol
            array('delete', 'class_testclassidnumber', 'testuserusername', '', '', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', '', 'testuser@email.com', '', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', '', '', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', '', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', 'testuserusername', '', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', '', 'testuser@email.com', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            array('delete', 'class_testclassidnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unenrolled from class instance "testclassidnumber" as an instructor.', $instructor_params),
            //user role assign
            array('create', 'user_testuseridnumber', 'testuserusername', '', '', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', '', 'testuser@email.com', '', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', '', '', 'testuseridnumber', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', 'testuserusername', 'testuser@email.com', '', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', 'testuserusername', '', 'testuseridnumber', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', '', 'testuser@email.com', 'testuseridnumber', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('create', 'user_testuseridnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'assigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            //user role unassign
            array('delete', 'user_testuseridnumber', 'testuserusername', '', '', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', '', 'testuser@email.com', '', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', '', '', 'testuseridnumber', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', 'testuserusername', 'testuser@email.com', '', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', 'testuserusername', '', 'testuseridnumber', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', '', 'testuser@email.com', 'testuseridnumber', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
            array('delete', 'user_testuseridnumber', 'testuserusername', 'testuser@email.com', 'testuseridnumber', 'unassigned role with shortname "testroleshortname" on user "testuseridnumber".', $role_params),
       );
    }

    /**
     * Enrol the test user in the provided context
     *
     * @param string $contextlevel The string descriptor of the context level
     * @param string $role The shortname of the import record's role column
     */
    private function create_enrolment($contextlevel, $role) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');

        switch ($contextlevel) {
            case 'curriculum':
                //program enrolment
                require_once(elispm::lib('data/curriculumstudent.class.php'));
                $data = array(
                    'curriculumid' => 1,
                    'userid' => 1
                );
                $curriculumstudent = new curriculumstudent($data);
                $curriculumstudent->save();
                break;
            case 'track':
                //track enrolment
                require_once(elispm::lib('data/usertrack.class.php'));
                $data = array(
                    'trackid' => 1,
                    'userid' => 1
                );
                $usertrack = new usertrack($data);
                $usertrack->save();
                break;
            case 'cluster':
                //user set enrolment
                require_once(elispm::lib('data/clusterassignment.class.php'));
                $data = array(
                    'clusterid' => 1,
                    'userid' => 1
                );
                $clusterassignment = new clusterassignment($data);
                $clusterassignment->save();
                break;
            case 'class':
                if ($role == 'instructor') {
                    //class instance instructor enrolment
                    require_once(elispm::lib('data/instructor.class.php'));
                    $data = array(
                        'classid' => 1,
                        'userid' => 1
                    );
                    $instructor = new instructor($data);
                    $instructor->save();
                } else {
                    //class instance student enrolment
                    require_once(elispm::lib('data/student.class.php'));
                    $data = array(
                        'classid' => 1,
                        'userid' => 1
                    );
                    $student = new student($data);
                    $student->save();
                }
                break;
            case 'user':
                //Moodle user role assignment
                $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
                $userid = $DB->get_field('user', 'id', array('idnumber' => 'testuseridnumber'));

                $context = context_user::instance($userid);
                role_assign($roleid, $userid, $context->id);
                break;
            default:
                break;
        }
    }

    /**
     * Validate that PM enrolment actions create log messages
     *
     * @dataProvider enrolmentDataProvider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $context The context import field
     * @param string $username The user_username import field
     * @param string $email The user_email import field
     * @param string $idnumber The user_idnumber import field
     * @param string $message_suffix Specific piece of the message to expect
     * @param array $extra_params Any additional info to include in the import
     */
    public function testEnrolmentActionCreatesLogMessage($action, $context, $username, $email, $idnumber, $message_suffix,
                                                         $extra_params) {
        //combine base data with additional info
        $data = array(
            'action' => $action,
            'context' => $context,
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber
        );
        $data = array_merge($data, $extra_params);

        //set up appropriate data to satisfy any necessary dependencies
        $parts = explode('_', $context);
        $create_program = $parts[0] == 'curriculum' || $parts[0] == 'track';
        $create_track = $parts[0] == 'track';
        $create_course = $parts[0] == 'course' || $parts[0] == 'class';
        $create_class = $parts[0] == 'class';
        $create_userset = $parts[0] == 'cluster';
        $this->load_csv_data($create_program, $create_track, $create_course, $create_class, $create_userset, true);

        if ($parts[0] == 'user') {
            //need role for a role assignment
            create_role('testrolename', 'testroleshortname', 'testroledescription');
        }

        if ($action != 'create') {
            //make sure our enrolment is set up
            $role = '';
            if (isset($extra_params['role'])) {
                $role = $extra_params['role'];
            }
            $this->create_enrolment($parts[0], $role);
        }

        //string representing identifying fields
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        //validation
        $expected_message = "[enrolment.csv line 2] User with {$identifier} successfully {$message_suffix}\n";
        $this->assert_data_produces_message($data, $expected_message, 'enrolment');
    }

    /**
     * Data provider used for dealing with user information
     */
    public function userIdentifierProvider() {
        return array(
            array('testuserusername', '', ''),
            array('', 'testuser@email.com', ''),
            array('', '', 'testuseridnumber'),
            array('testuserusername', 'testuser@email.com', ''),
            array('testuserusername', '', 'testuseridnumber'),
            array('', 'testuser@email.com', 'testuseridnumber'),
            array('testuserusername', 'testuser@email.com', 'testuseridnumber')
        );
    }

    /**
     * Validate that the student enrolment update action creates log messages
     *
     * @dataProvider userIdentifierProvider
     * @param string $username The import user_username field
     * @param string $email The import user_email field
     * @param string $idnumber The import user_idnumber field
     */
    public function testStudentEnrolmentUpdateCreatesLogMessage($username, $email, $idnumber) {
        $data = array(
            'action' => 'update',
            'context' => 'class_testclassidnumber',
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber
        );

        //set up the course description, class instance, and user
        $this->load_csv_data(false, false, true, true, false, true);
        $this->create_enrolment('class', 'student');

        //string representing identifying fields
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        //validation
        $expected_message = "[enrolment.csv line 2] Student enrolment for user with {$identifier} in class instance \"testclassidnumber\" successfully updated.\n";
        $this->assert_data_produces_message($data, $expected_message, 'enrolment');
    }

    /**
     * Validate that the instructor enrolment update action creates log messages
     *
     * @dataProvider userIdentifierProvider
     * @param string $username The import user_username field
     * @param string $email The import user_email field
     * @param string $idnumber The import user_idnumber field
     */
    public function testInstructorEnrolmentUpdateCreatesLogMessage($username, $email, $idnumber) {
        $data = array(
            'action' => 'update',
            'context' => 'class_testclassidnumber',
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber,
            'role' => 'instructor'
        );

        //set up the course description, class instance, and user
        $this->load_csv_data(false, false, true, true, false, true);
        $this->create_enrolment('class', 'instructor');

        //string representing identifying fields
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        //validation
        $expected_message = "[enrolment.csv line 2] Instructor enrolment for user with {$identifier} in class instance \"testclassidnumber\" successfully updated.\n";
        $this->assert_data_produces_message($data, $expected_message, 'enrolment');
    }
}
