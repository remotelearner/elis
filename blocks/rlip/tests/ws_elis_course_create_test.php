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
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once(elispm::lib('data/curriculum.class.php'));
require_once(elispm::lib('data/curriculumcourse.class.php'));
require_once(elispm::lib('data/course.class.php'));
require_once(elispm::lib('data/coursetemplate.class.php'));
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/course_create.class.php');

/**
 * Tests webservice method block_rldh_elis_course_create
 */
class block_rlip_ws_elis_course_create_test extends rlip_test {
    /**
     * @var object Holds a backup of the course object so we can do sane permissions handling.
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
            field_data_text::TABLE => 'elis_core',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            curriculum::TABLE => 'elis_program',
            curriculumcourse::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            coursetemplate::TABLE => 'elis_program',
            'cache_flags' => 'moodle',
            'config' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
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

        // Import, get system context.
        $sql = 'INSERT INTO {context} SELECT * FROM '.self::$origdb->get_prefix().'context WHERE contextlevel = ?';
        $DB->execute($sql, array(CONTEXT_SYSTEM));
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

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $USER->id, $syscontext->id);
    }

    /**
     * method to create test program.
     * @param string $idnumber the idnumber to use to create program
     * @return int|bool the program DB id or false on error
     */
    public function create_program($idnumber) {
        $params = array(
            'idnumber' => $idnumber,
            'name' => 'Test Program',
        );
        $program = new curriculum($params);
        $program->save();
        return !empty($program->id) ? $program->id : false;
    }

    /**
     * Test successful course creation.
     */
    public function test_success() {
        global $DB;

        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Test';
        $fieldcat->save();

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'testfield';
        $field->name = 'Test Field';
        $field->datatype = 'text';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_COURSE;
        $fieldctx->save();

        // Grant permissions
        $this->give_permissions(array('elis/program:course_create'));

        // Create test program
        $programidnumber = 'TestProgram';
        $programid = $this->create_program($programidnumber);

        $course = array(
            'idnumber' => 'TestCourse',
            'name' => 'Test Course',
            'code' => 'CRS1',
            'syllabus' => 'Test syllabus',
            'lengthdescription' => 'Weeks',
            'length' => 2,
            'credits' => 1.1,
            'completion_grade' => 50,
            'cost' => '$100',
            'version' => '1.0.0',
            'assignment'=> $programidnumber,
        );

        // Create test course
        $response = block_rldh_elis_course_create::course_create($course);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_course_create_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_course_create_success_msg', 'block_rlip'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get course.
        $createdcourse = new course($response['record']['id']);
        $createdcourse->load();
        $createdcourse = $createdcourse->to_array();
        foreach ($course as $param => $val) {
            if ($param != 'assignment') {
                $this->assertArrayHasKey($param, $createdcourse);
                $this->assertEquals($val, $createdcourse[$param]);
            }
        }

        // Check that course was assigned to program.
        $curriculumcourseid = $DB->get_field(curriculumcourse::TABLE, 'id', array('curriculumid' => $programid, 'courseid' => $response['record']['id']));
        $this->assertNotEmpty($curriculumcourseid);
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
                            'idnumber' => 'testcourse',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'name' => 'Test Course',
                        )
                ),
                // Test invalid credits.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'credits' => -1,
                        )
                ),
                // Test invalid completion grade.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'completiongrade' => -1,
                        )
                ),
                // Test invalid program assignment.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'assignment' => 'bogusProgram',
                        )
                ),
                // Test invalid Moodle course template.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'link' => 'bogusTemplate',
                        )
                ),
                // Test duplicate course.
                array(
                        array(
                            'idnumber' => 'duptestcourse',
                            'name' => 'Duplicate Test Course',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $course The incoming ELIS course data.
     */
    public function test_failure(array $course) {
        global $DB;

        $this->give_permissions(array('elis/program:course_create'));

        // Setup duplicate course
        $dupcrs = new course(array('idnumber' => 'duptestcourse', 'name' => 'Duplicate Test Course'));
        $dupcrs->save();

        $response = block_rldh_elis_course_create::course_create($course);
    }
}