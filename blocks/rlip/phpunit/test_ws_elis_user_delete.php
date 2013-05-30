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
require_once(elispm::lib('data/curriculumstudent.class.php'));
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/user_delete.class.php');

/**
 * Tests webservice method block_rldh_elis_user_delete
 */
class block_rlip_ws_elis_user_delete_test extends rlip_test {
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
            clusterassignment::TABLE => 'elis_program',
            curriculum::TABLE => 'elis_program',
            curriculumstudent::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_data_num::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            instructor::TABLE => 'elis_program',
            student::TABLE => 'elis_program',
            student_grade::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            usertrack::TABLE => 'elis_program',
            waitlist::TABLE => 'elis_program',
            'block_instances' => 'moodle',
            'block_positions' => 'moodle',
            'cache_flags' => 'moodle',
            'cohort_members' => 'moodle',
            'comments' => 'moodle',
            'config' => 'moodle',
            'context' => 'moodle',
            'external_services_users' => 'moodle',
            'external_tokens' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'grading_areas' => 'moodle',
            'groups_members' => 'moodle',
            'log' => 'moodle',
            'message' => 'moodle',
            'message_read' => 'moodle',
            'message_working' => 'moodle',
            'rating' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            'sessions' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_info_data' => 'moodle',
            'user_lastaccess' => 'moodle',
            'user_preferences' => 'moodle',
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
        set_config('siteadmins', $assigninguser->id);

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $USER->id, $syscontext->id);
    }

    /**
     * Dataprovider for test_success
     * @return array Array of parameters.
     */
    public function dataprovider_success() {
        return array(
                // Test with one identifying field.
                array(
                        array(
                            'idnumber' => 'testuser1',
                        )
                ),
                // Test with two identifying fields.
                array(
                        array(
                            'idnumber' => 'testuser1',
                            'username' => 'testuser1',
                        )
                ),
                // Test with three identifying fields.
                array(
                        array(
                            'idnumber' => 'testuser1',
                            'username' => 'testuser1',
                            'email' => 'testuser@example.com',
                        )
                )
        );
    }

    /**
     * Test successful user deletion.
     * @dataProvider dataprovider_success
     * @param array $params Incoming parameters for the webservice method.
     */
    public function test_success($params) {
        global $DB;

        $this->give_permissions(array('elis/program:user_delete'));

        $user = array(
            'idnumber' => 'testuser1',
            'username' => 'testuser1',
            'firstname' => 'testuser1',
            'lastname' => 'testuser1',
            'email' => 'testuser1@example.com',
            'country' => 'CA',
        );
        $user = new user($user);
        $user->save();

        $response = block_rldh_elis_user_delete::user_delete($params);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);

        // Verify unique message code.
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertEquals(get_string('ws_user_delete_success_code', 'block_rlip'), $response['messagecode']);

        // Verify human-readable message.
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_user_delete_success_msg', 'block_rlip'), $response['message']);
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
                // Test no valid input.
                array(
                        array(
                            'firstname' => 'test',
                        )
                ),
                // Test non-existant identifying field value.
                array(
                        array(
                            'username' => 'testuser3',
                        )
                ),
                // Test conflicting identifying fields.
                array(
                        array(
                            'username' => 'testuser1',
                            'idnumber' => 'testuser2',
                        )
                ),
                // Test No Permissions.
                array(
                        array(
                            'username' => 'testuser1',
                        ),
                        false
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $params The incoming parameters.
     */
    public function test_failure(array $params, $giveperms = true) {
        global $DB;

        if ($giveperms === true) {
            $this->give_permissions(array('elis/program:user_delete'));
        }

        $user = new user(array(
            'idnumber' => 'testuser1',
            'username' => 'testuser1',
            'firstname' => 'testuser1',
            'lastname' => 'testuser1',
            'email' => 'testuser1@example.com',
            'country' => 'CA',
        ));
        $user->save();

        $user = new user(array(
            'idnumber' => 'testuser2',
            'username' => 'testuser2',
            'firstname' => 'testuser2',
            'lastname' => 'testuser2',
            'email' => 'testuser2@example.com',
            'country' => 'CA',
        ));
        $user->save();

        $response = block_rldh_elis_user_delete::user_delete($params);
    }
}
