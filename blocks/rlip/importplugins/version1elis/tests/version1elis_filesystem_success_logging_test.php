<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');

/**
 * Class for testing file-system log success messages as created by the Version 1 ELIS import plugin.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisfilesystemsuccesslogging_testcase extends rlip_elis_test {

    /**
     * Run before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Validates that the supplied data produces the expected message
     *
     * @param array  $data The import data to process
     * @param string $expectedmessage The error we are expecting (message only)
     * @param user   $entitytype One of 'user', 'course', 'enrolment'
     * @param string $importfilename  name of import file
     */
    protected function assert_data_produces_message($data, $expectedmessage, $entitytype, $importfilename = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data, $importfilename);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, true);
        // Suppress output for now.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Validate that a log file was created.
        // Get first summary record - at times, multiple summary records are created and this handles that problem.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        // Get logfile name.
        $plugintype = 'import';
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp', 'block_rlip');
        $testfilename = $filepath.'/'.$plugintype.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        // Get most recent logfile.

        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename), "\n Can't find logfile: {$filename} for \n{$testfilename}");

        // Fetch log line.
        $pointer = fopen($filename, 'r');

        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');

        while (!feof($pointer)) {
            $error = fgets($pointer);
            if (!empty($error)) { // Could be an empty new line.
                if (is_array($expectedmessage)) {
                    $actualmessage[] = substr($error, $prefixlength);
                } else {
                    $actualmessage = substr($error, $prefixlength);
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expectedmessage, $actualmessage);
    }

    /**
     * Data provider that specifies information for user actions
     *
     * @return array Data, as expected by the test method
     */
    public function userdataprovider() {
        // Parameters specific to the user create action.
        $usercreateparams = array(
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        );

        return array(
            array('create', 'testuserusername', 'testuser@email.com', 'testuseridnumber', false, $usercreateparams),
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
        // String representing identifying fields.
        $identifierparts  = array();
        if ($username != '') {
            $identifierparts[] = 'username "'.$username.'"';
        }
        if ($email != '') {
            $identifierparts[] = 'email "'.$email.'"';
        }
        if ($idnumber != '') {
            $identifierparts[] = 'idnumber "'.$idnumber.'"';
        }
        $identifier = implode(', ', $identifierparts);

        return $identifier;
    }

    /**
     * Validate that user actions create success log messages
     *
     * @dataProvider userdataprovider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $username The value being specified for username
     * @param string $email The value being specified for email
     * @param string $idnumber The value being specified for idnumber
     * @param boolean $createuser Create the sample user before processing the action, if true
     * @param array $extraparams Extra parameters specified, beyond identifying fields
     */
    public function test_useractioncreateslogmessage($action, $username, $email, $idnumber, $createuser, $extraparams) {
        if ($createuser) {
            // Needed for upddate / delete actions.
            $this->create_test_user();
        }

        // Merge base data with extra parameters.
        $data = array(
            'action' => $action,
            'username' => $username,
            'email' => $email,
            'idnumber' => $idnumber
        );
        $data = array_merge($data, $extraparams);

        // String representing identifying fields.
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        // Validation.
        $expectedmessage = "[user.csv line 2] User with {$identifier} successfully {$action}d.\n";
        $this->assert_data_produces_message($data, $expectedmessage, 'user');
    }

    /**
     * Load all necessary CSV data
     *
     * @param boolean $createprogram Set to true to create the test program
     * @param boolean $createtrack Set to true to create the test track
     * @param boolean $createcourse Set to true to create the test course description
     * @param boolean $createclass Set to true to create the test class instance
     * @param boolean $createuserset Set to true to create the test user set
     * @param boolean $createuser Set to true to create the test user
     */
    private function load_csv_data($createprogram, $createtrack, $createcourse, $createclass, $createuserset, $createuser) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        // Load PM entities from CSV files.
        $csvs = array();
        $csvloc = dirname(__FILE__).'/fixtures';
        if ($createprogram) {
            $csvs[curriculum::TABLE] = $csvloc.'/phpunit_program_success.csv';
        }
        if ($createtrack) {
            $csvs[track::TABLE] = $csvloc.'/phpunit_track_success.csv';
        }
        if ($createcourse) {
            $csvs[course::TABLE] = $csvloc.'/phpunit_course_success.csv';
        }
        if ($createclass) {
            $csvs[pmclass::TABLE] = $csvloc.'/phpunit_class_success.csv';
        }
        if ($createuserset) {
            $csvs[userset::TABLE] = $csvloc.'/phpunit_userset_success.csv';
        }

        $dataset = $this->createCsvDataSet($csvs);
        $this->loadDataSet($dataset);

        if ($createuser) {
            // Need to use API to create Moodle user.
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
    public function pmentitydataprovider() {
        // Parameters needed for specific cases.
        $programcreateparams = array(
            'name' => 'testprogramname'
        );
        $trackcreateparams = array(
            'assignment' => 'testprogramidnumber',
            'name' => 'testtrackname'
        );
        $coursecreateparams = array(
            'name' => 'testcoursename'
        );
        $classcreateparams = array(
            'assignment' => 'testcourseidnumber'
        );

        return array(
            array('create', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', $programcreateparams),
            array('update', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', array()),
            array('delete', 'curriculum', 'Program', 'idnumber', 'testprogramidnumber', array()),
            array('create', 'track', 'Track', 'idnumber', 'testtrackidnumber', $trackcreateparams),
            array('update', 'track', 'Track', 'idnumber', 'testtrackidnumber', array()),
            array('delete', 'track', 'Track', 'idnumber', 'testtrackidnumber', array()),
            array('create', 'course', 'Course description', 'idnumber', 'testcourseidnumber', $coursecreateparams),
            array('update', 'course', 'Course description', 'idnumber', 'testcourseidnumber', array()),
            array('delete', 'course', 'Course description', 'idnumber', 'testcourseidnumber', array()),
            array('create', 'class', 'Class instance', 'idnumber', 'testclassidnumber', $classcreateparams),
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
     * @dataProvider pmentitydataprovider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $context The context import field
     * @param string $contextdescription The human-readable context level description
     * @param string $uniquefield The shortname of the unique identifying field
     * @param string $uniquevalue The unique identifying value
     * @param $extraparams Extra parameter specified, beyond identifying fields
     */
    public function test_pmentityactioncreateslogmessage($action, $context, $contextdescription, $uniquefield,
                                                         $uniquevalue, $extraparams) {
        // Combine base data with extra info.
        $data = array(
            'action' => $action,
            'context' => $context,
            $uniquefield => $uniquevalue
        );
        $data = array_merge($data, $extraparams);

        // Set up appropriate data to satisfy any necessary dependencies.
        $createprogram = $context == 'track' || $context == 'curriculum' && $action != 'create';
        $createtrack = $context == 'track' && $action != 'create';
        $createcourse = $context == 'class' || $context == 'course' && $action != 'create';
        $createclass = $context == 'class' && $action != 'create';
        $createuserset = $context == 'cluster' && $action != 'create';
        $this->load_csv_data($createprogram, $createtrack, $createcourse, $createclass, $createuserset, false);

        // Validation.
        $expectedmsg = "[course.csv line 2] {$contextdescription} with {$uniquefield} \"$uniquevalue\" successfully {$action}d.\n";
        $this->assert_data_produces_message($data, $expectedmsg, 'course');
    }

    /**
     * Data provider that specifies information for enrolment entity actions
     *
     * @return array Data, as expected by the test method
     */
    public function enrolmentdataprovider() {
        // Extra parameters needed in specific cases.
        $instructorparams = array(
            'role' => 'instructor'
        );
        $roleparams = array(
            'role' => 'testroleshortname'
        );

        return array(
                // Program enrol.
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        '',
                        '',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                array(
                        'create',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in program "testprogramidnumber".',
                        array()
                ),
                // Program unenrol.
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        '',
                        '',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'curriculum_testprogramidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from program "testprogramidnumber".',
                        array()
                ),
                // Track enrol.
                array(
                        'create',
                        'track_testtrackidnumber',
                        'testuserusername',
                        '',
                        '',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                array(
                        'create',
                        'track_testtrackidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in track "testtrackidnumber".',
                        array()
                ),
                // Track unenrol.
                array(
                        'delete',
                        'track_testtrackidnumber',
                        'testuserusername',
                        '',
                        '',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                array(
                        'delete',
                        'track_testtrackidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from track "testtrackidnumber".',
                        array()
                ),
                // User set enrol.
                array(
                        'create',
                        'cluster_testusersetname',
                        'testuserusername',
                        '',
                        '',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        '',
                        'testuser@email.com',
                        '',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        '',
                        '',
                        'testuseridnumber',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                array(
                        'create',
                        'cluster_testusersetname',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in user set "testusersetname".',
                        array()
                ),
                // User set unenrol.
                array(
                        'delete',
                        'cluster_testusersetname',
                        'testuserusername',
                        '',
                        '',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        '',
                        'testuser@email.com',
                        '',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        '',
                        '',
                        'testuseridnumber',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                array(
                        'delete',
                        'cluster_testusersetname',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from user set "testusersetname".',
                        array()
                ),
                // Student class enrol.
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        '',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as a student.',
                        array()
                ),
                // Student class unenrol.
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        '',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as a student.',
                        array()
                ),
                // Instructor class enrol.
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        '',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'create',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'enrolled in class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                // Instructor class unenrol.
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        '',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                array(
                        'delete',
                        'class_testclassidnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unenrolled from class instance "testclassidnumber" as an instructor.',
                        $instructorparams
                ),
                // User role assign.
                array(
                        'create',
                        'user_testuseridnumber',
                        'testuserusername',
                        '',
                        '',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'create',
                        'user_testuseridnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'assigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                // User role unassign.
                array(
                        'delete',
                        'user_testuseridnumber',
                        'testuserusername',
                        '',
                        '',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        '',
                        'testuser@email.com',
                        '',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        '',
                        '',
                        'testuseridnumber',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        'testuserusername',
                        'testuser@email.com',
                        '',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        'testuserusername',
                        '',
                        'testuseridnumber',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        '',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                ),
                array(
                        'delete',
                        'user_testuseridnumber',
                        'testuserusername',
                        'testuser@email.com',
                        'testuseridnumber',
                        'unassigned role with shortname "testroleshortname" on user "testuseridnumber".',
                        $roleparams
                )
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
                // Program enrolment.
                require_once(elispm::lib('data/curriculumstudent.class.php'));
                $data = array(
                    'curriculumid' => 1,
                    'userid' => 1
                );
                $curriculumstudent = new curriculumstudent($data);
                $curriculumstudent->save();
                break;
            case 'track':
                // Track enrolment.
                require_once(elispm::lib('data/usertrack.class.php'));
                $data = array(
                    'trackid' => 1,
                    'userid' => 1
                );
                $usertrack = new usertrack($data);
                $usertrack->save();
                break;
            case 'cluster':
                // User set enrolment.
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
                    // Class instance instructor enrolment.
                    require_once(elispm::lib('data/instructor.class.php'));
                    $data = array(
                        'classid' => 1,
                        'userid' => 1
                    );
                    $instructor = new instructor($data);
                    $instructor->save();
                } else {
                    // Class instance student enrolment.
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
                // Moodle user role assignment.
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
     * @dataProvider enrolmentdataprovider
     * @param string $action The action to run (i.e. create, update or delete)
     * @param string $context The context import field
     * @param string $username The user_username import field
     * @param string $email The user_email import field
     * @param string $idnumber The user_idnumber import field
     * @param string $messagesuffix Specific piece of the message to expect
     * @param array $extraparams Any additional info to include in the import
     */
    public function test_enrolmentactioncreateslogmessage($action, $context, $username, $email, $idnumber, $messagesuffix,
                                                          $extraparams) {
        // Combine base data with additional info.
        $data = array(
            'action' => $action,
            'context' => $context,
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber
        );
        $data = array_merge($data, $extraparams);

        // Set up appropriate data to satisfy any necessary dependencies.
        $parts = explode('_', $context);
        $createprogram = $parts[0] == 'curriculum' || $parts[0] == 'track';
        $createtrack = $parts[0] == 'track';
        $createcourse = $parts[0] == 'course' || $parts[0] == 'class';
        $createclass = $parts[0] == 'class';
        $createuserset = $parts[0] == 'cluster';
        $this->load_csv_data($createprogram, $createtrack, $createcourse, $createclass, $createuserset, true);

        if ($parts[0] == 'user') {
            // Need role for a role assignment.
            create_role('testrolename', 'testroleshortname', 'testroledescription');
        }

        if ($action != 'create') {
            // Make sure our enrolment is set up.
            $role = '';
            if (isset($extraparams['role'])) {
                $role = $extraparams['role'];
            }
            $this->create_enrolment($parts[0], $role);
        }

        // String representing identifying fields.
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        // Validation.
        $expectedmessage = "[enrolment.csv line 2] User with {$identifier} successfully {$messagesuffix}\n";
        $this->assert_data_produces_message($data, $expectedmessage, 'enrolment');
    }

    /**
     * Data provider used for dealing with user information
     */
    public function useridentifierprovider() {
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
     * @dataProvider useridentifierprovider
     * @param string $username The import user_username field
     * @param string $email The import user_email field
     * @param string $idnumber The import user_idnumber field
     */
    public function test_studentenrolmentupdatecreateslogmessage($username, $email, $idnumber) {
        $data = array(
            'action' => 'update',
            'context' => 'class_testclassidnumber',
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber
        );

        // Set up the course description, class instance, and user.
        $this->load_csv_data(false, false, true, true, false, true);
        $this->create_enrolment('class', 'student');

        // String representing identifying fields.
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        // Validation.
        $expectedmessage = "[enrolment.csv line 2] Student enrolment for user with {$identifier} in class instance ";
        $expectedmessage .= "\"testclassidnumber\" successfully updated.\n";
        $this->assert_data_produces_message($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validate that the instructor enrolment update action creates log messages
     *
     * @dataProvider useridentifierprovider
     * @param string $username The import user_username field
     * @param string $email The import user_email field
     * @param string $idnumber The import user_idnumber field
     */
    public function test_instructorenrolmentupdatecreateslogmessage($username, $email, $idnumber) {
        $data = array(
            'action' => 'update',
            'context' => 'class_testclassidnumber',
            'user_username' => $username,
            'user_email' => $email,
            'user_idnumber' => $idnumber,
            'role' => 'instructor'
        );

        // Set up the course description, class instance, and user.
        $this->load_csv_data(false, false, true, true, false, true);
        $this->create_enrolment('class', 'instructor');

        // String representing identifying fields.
        $identifier = $this->get_user_identifier($username, $email, $idnumber);

        // Validation.
        $expectedmessage = "[enrolment.csv line 2] Instructor enrolment for user with {$identifier} in class instance ";
        $expectedmessage .= "\"testclassidnumber\" successfully updated.\n";
        $this->assert_data_produces_message($data, $expectedmessage, 'enrolment');
    }
}
