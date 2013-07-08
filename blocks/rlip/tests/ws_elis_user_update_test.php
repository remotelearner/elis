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
require_once($dirname.'/../ws/elis/user_update.class.php');

/**
 * Tests webservice method block_rldh_elis_user_update
 */
class block_rlip_ws_elis_user_update_test extends rlip_test {
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
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
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
     * Dataprovider for test_success
     * @return array Array of tests and parameters.
     */
    public function dataprovider_success() {
        return array(
                // Update by idnumber.
                array(
                        array(
                            'idnumber' => 'testuser',
                            'firstname' => 'testuser2',
                        )
                ),
                // Update by username.
                array(
                        array(
                            'username' => 'testuser',
                            'lastname' => 'testuser2',
                        )
                ),
                // Update by email.
                array(
                        array(
                            'email' => 'testuser@example.com',
                            'firstname' => 'testuser2',
                        )
                ),
                // Update by idnumber + username.
                array(
                        array(
                            'idnumber' => 'testuser',
                            'username' => 'testuser',
                            'lastname' => 'testuser2',
                        )
                ),
        );
    }

    /**
     * Test successful user edit.
     * @dataProvider dataprovider_success
     * @param array $update Incoming update data.
     */
    public function test_success($update) {
        global $DB;
        $this->give_permissions(array('elis/program:user_edit'));

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
        $fieldctx->contextlevel = CONTEXT_ELIS_USER;
        $fieldctx->save();

        $user = array(
            'idnumber' => 'testuser',
            'username' => 'testuser',
            'firstname' => 'testuser',
            'lastname' => 'testuser',
            'email' => 'testuser@example.com',
            'country' => 'CA',
            'field_testfield' => 'Test Field',
        );
        $user = new user($user);
        $user->save();
        $expecteduser = (array)$DB->get_record(user::TABLE, array('id' => $user->id));
        $expecteduser = array_merge($expecteduser, $update);

        $response = block_rldh_elis_user_update::user_update($update);

        // Verify general response structure.
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);

        // Verify response message/code.
        $this->assertEquals(get_string('ws_user_update_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_user_update_success_msg', 'block_rlip'), $response['message']);

        // Verify returned user information.
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $actualuser = $DB->get_record(user::TABLE, array('id' => $response['record']['id']));
        $this->assertNotEmpty($actualuser);
        $actualuser = (array)$actualuser;

        // Unset timemodified as it's unreliable to test.
        unset($actualuser['timemodified']);

        foreach ($actualuser as $param => $val) {
            $this->assertEquals($expecteduser[$param], $val);
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
                // Test no identifying fields.
                array(
                        array(
                            'firstname' => 'newfirstname',
                        )
                ),
                // Test non-existent user.
                array(
                        array(
                            'username' => 'testuser0',
                            'firstname' => 'newfirstname',
                        )
                ),
                // Test attempted identifying field update.
                array(
                        array(
                            'username' => 'testuser1',
                            'idnumber' => 'newtestuser1',
                            'firstname' => 'newfirstname',
                        )
                ),
                // Test conflicting identifying fields.
                array(
                        array(
                            'username' => 'testuser1',
                            'idnumber' => 'testuser2',
                            'firstname' => 'newfirstname',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'username' => 'testuser1',
                            'birthdate' => '409fj49f',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $update The incoming update data.
     */
    public function test_failure(array $update) {
        global $DB;

        $this->give_permissions(array('elis/program:user_edit'));

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

        $user = array(
            'idnumber' => 'testuser2',
            'username' => 'testuser2',
            'firstname' => 'testuser2',
            'lastname' => 'testuser2',
            'email' => 'testuser2@example.com',
            'country' => 'CA',
        );
        $user = new user($user);
        $user->save();

        $response = block_rldh_elis_user_update::user_update($update);
    }
}