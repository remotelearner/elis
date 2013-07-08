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
 * @package    block_rlip
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}
$dirname = dirname(__FILE__);
require_once($dirname.'/../../../config.php');
global $CFG;
require_once($dirname.'/../lib.php');
require_once($dirname.'/rlip_test.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/student.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/class_enrolment_create.class.php');

/**
 * Tests webservice method block_rldh_elis_class_enrolment_create
 */
class block_rlip_ws_elis_class_enrolment_create_test extends rlip_test {
    /**
     * @var object Holds a backup of the user object so we can do sane permissions handling.
     */
    static public $userbackup;

    /**
     * @var array Array of globals to not do backup.
     */
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Get overlay tables.
     * @return array An array of overlay tables.
     */
    protected static function get_overlay_tables() {
        return array(
            field::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            classmoodlecourse::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            coursetemplate::TABLE => 'elis_program',
            pmclass::TABLE => 'elis_program',
            student::TABLE => 'elis_program',
            student_grade::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            'cache_flags' => 'moodle',
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
        );
    }

    /**
     * Perform teardown after test - restore the user global.
     */
    protected function tearDown() {
        global $USER;
        $USER = static::$userbackup;
        parent::tearDown();
    }

    /**
     * Perform setup before test - backup the user global.
     */
    protected function setUp() {
        global $USER;
        static::$userbackup = $USER;
        parent::setUp();
    }

    /**
     * Give permissions to the current user.
     * @param array $perms Array of permissions to grant.
     */
    public function give_permissions(array $perms) {
        global $USER, $DB;

        accesslib_clear_all_caches(true);

        set_config('siteguest', '');
        set_config('siteadmins', '');

        $syscontext = get_context_instance(CONTEXT_SYSTEM);

        $assigninguser = new user(array(
            'idnumber' => 'assigninguserid',
            'username' => 'assigninguser',
            'firstname' => 'assigninguser',
            'lastname' => 'assigninguser',
            'email' => 'assigninguser@testuserdomain.com',
            'country' => 'CA'
        ));
        $assigninguser->save();
        $USER = $DB->get_record('user', array('id' => $assigninguser->id));

        $dupemailuser = new user(array(
            'idnumber' => 'dupemailuserid',
            'username' => 'dupemailuser',
            'firstname' => 'dupemailuserfirstname',
            'lastname' => 'dupemailuserlastname',
            'email' => 'assigninguser@testuserdomain.com', // dup email!
            'country' => 'CA'
        ));
        $dupemailuser->save();

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $USER->id, $syscontext->id);
    }

    /**
     * method to create test course
     * @param string $idnumber the idnumber to use to create course
     * @return int|bool the class DB id or false on error
     */
    public function create_course($idnumber) {
        $params = array(
            'idnumber' => $idnumber,
            'code' => 'Course code',
            'name' => 'Course name',
            'syllabus' => 'syllabus'
        );
        $crs = new course($params);
        $crs->save();
        return !empty($crs->id) ? $crs->id : false;
    }

    /**
     * method to create test class
     * @param string $idnumber the idnumber to use to create class
     * @param int $crsid the course DB id use to create class
     * @return int|bool the class DB id or false on error
     */
    public function create_class($idnumber, $crsid) {
        $params = array(
            'idnumber' => $idnumber,
            'courseid' => $crsid
        );
        $cls = new pmclass($params);
        $cls->save();
        return !empty($cls->id) ? $cls->id : false;
    }

    /**
     * Test successful class enrolment creation.
     */
    public function test_success() {
        global $DB, $USER;

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $crsidnumber = 'TestCourse';
        $crsid = $this->create_course($crsidnumber);

        $clsidnumber = 'TestClassEnrolmentCreate';
        $classid = $this->create_class($clsidnumber, $crsid);

        $userid = 1; // $USER->id;

        $data = array(
            'class_idnumber' => $clsidnumber,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@testuserdomain.com',
            'completestatus' => 'passed',
            'grade' => 67.89,
            'credits' => 1.1,
            'locked' => 1,
            'enrolmenttime' => 'May/08/2013',
            'completetime' => 'May/31/2013',
        );

        $expectdata = array(
            'classid' => $classid,
            'userid' => $userid,
            'completestatusid' => STUSTATUS_PASSED,
            'grade' => 67.89,
            'credits' => 1.1,
            'locked' => 1,
            'enrolmenttime' => $importplugin->parse_date('May/08/2013'),
            'completetime' => $importplugin->parse_date('May/31/2013'),
        );

        $this->give_permissions(array('elis/program:class_enrol'));
        $response = block_rldh_elis_class_enrolment_create::class_enrolment_create($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_class_enrolment_create_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_class_enrolment_create_success_msg', 'block_rlip'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get record
        $stu = $DB->get_record(student::TABLE, array('id' => $response['record']['id']));
        $this->assertNotEmpty($stu);
        $stu = (array)$stu;
        foreach ($expectdata as $param => $val) {
            $this->assertArrayHasKey($param, $stu, $param);
            $this->assertEquals($val, $stu[$param], $param);
        }
    }

    /**
     * Dataprovider for test_failure()
     * @return array An array of parameters
     */
    public function dataprovider_failure() {
        return array(
                // Test empty input.
                array(
                        array()
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                            'user_idnumber' => 'assigninguserid',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                            'user_idnumber' => 'assigninguserid',
                            'user_email' => 'assigninguser@testuserdomain.com',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentCreate',
                        )
                ),
                // Test invalid class.
                array(
                        array(
                            'class_idnumber' => 'BogusClass',
                        )
                ),
                // Test invalid completestatus.
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentCreate',
                            'user_idnumber' => 'assigninguserid',
                            'completestatus' => 'bogusStatus',
                        )
                ),
                // Test invalid enrolmenttime
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentCreate',
                            'user_idnumber' => 'assigninguserid',
                            'enrolmenttime' => 'XYZ/01/1971',
                        )
                ),
                // Test invalid completetime
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentCreate',
                            'user_idnumber' => 'assigninguserid',
                            'completetime' => 'FEB/31/1971',
                        )
                ),
                // Test conflicting input.
                array(
                        array(
                            'user_username' => 'anotheruser',
                            'user_idnumber' => 'assigninguserid',
                        )
                ),
                // Test not unique user input.
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentCreate',
                            'user_email' => 'assigninguser@testuserdomain.com',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $data The incoming class enrolment data.
     */
    public function test_failure(array $data) {
        global $DB;

        $crsidnumber = 'TestCourse';
        $crsid = $this->create_course($crsidnumber);

        $clsidnumber = 'TestClassEnrolmentDelete';
        $classid = $this->create_class($clsidnumber, $crsid);

        $this->give_permissions(array('elis/program:class_enrol'));
        $response = block_rldh_elis_class_enrolment_create::class_enrolment_create($data);
    }
}
