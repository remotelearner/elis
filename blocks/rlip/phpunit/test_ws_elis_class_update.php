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
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/class_update.class.php');

/**
 * Tests webservice method block_rldh_elis_class_update
 */
class block_rlip_ws_elis_class_update_test extends rlip_test {
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
            field_data_text::TABLE => 'elis_core',
            'crlm_coursetemplate' => 'elis_program',
            course::TABLE => 'elis_program',
            pmclass::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            'cache_flags' => 'moodle',
            'config' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
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
        $syscontext = context_system::instance();

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
     * Test successful user creation.
     */
    public function test_success() {
        global $DB;

        $this->give_permissions(array('elis/program:class_edit'));

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
        $fieldctx->contextlevel = CONTEXT_ELIS_CLASS;
        $fieldctx->save();

        $course = new course;
        $course->idnumber = 'testcourse1';
        $course->name = 'Test Course 1';
        $course->syllabus = 'Test';
        $course->save();

        $class = new pmclass(array(
            'idnumber' => 'testclass',
            'startdate' => 1357016400,
            'enddate' => 1359694800,
            'courseid' => $course->id,
            'assignment' => $course->idnumber,
            'field_testfield' => 'Test Field',
        ));
        $class->save();

        $classupdates = array(
            'idnumber' => 'testclass',
            'startdate' => 'Feb/04/2013',
            'enddate' => 'Mar/01/2013',
            'field_testfield' => 'Test Field 2',
        );

        $response = block_rldh_elis_class_update::class_update($classupdates);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_class_update_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_class_update_success_msg', 'block_rlip'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get class.
        $expectedclass = array(
            'idnumber' => $class->idnumber,
            'startdate' => 1359954000,
            'enddate' => 1362114000,
            'courseid' => $course->id,
            'field_testfield' => 'Test Field 2',
        );
        $createdclass = new pmclass($response['record']['id']);
        $createdclass->load();
        $createdclass = $createdclass->to_array();
        foreach ($expectedclass as $param => $val) {
            $this->assertArrayHasKey($param, $createdclass);
            $this->assertEquals($val, $createdclass[$param]);
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
                // Test no required input.
                array(
                        array(
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test invalid idnumber.
                array(
                        array(
                            'idnumber' => 'testclass2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test trying to reassign.
                array(
                        array(
                            'idnumber' => 'testclass1',
                            'assignment' => 'testcourse2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test no permissions.
                array(
                        array(
                            'idnumber' => 'testclass1',
                            'startdate' => 'Jan/01/2013',
                        ),
                        false
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $user The incoming user data.
     */
    public function test_failure(array $user, $giveperms = true) {
        global $DB;

        if ($giveperms === true) {
            $this->give_permissions(array('elis/program:class_edit'));
        }

        $course = new course;
        $course->idnumber = 'testcourse1';
        $course->name = 'Test Course 1';
        $course->syllabus = 'Test';
        $course->save();

        // Create a class (used for duplicate test).
        $class = new pmclass;
        $class->idnumber = 'testclass1';
        $class->courseid = $course->id;
        $class->save();

        $response = block_rldh_elis_class_update::class_update($user);
    }
}