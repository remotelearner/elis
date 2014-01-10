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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

$dirname = dirname(__FILE__);
require_once($dirname.'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($dirname.'/other/rlip_test.class.php');

// Libs.
require_once($dirname.'/../lib.php');
require_once($CFG->libdir.'/externallib.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/user_update.class.php');
}

/**
 * Tests webservice method local_datahub_elis_user_update.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_user_update_testcase extends rlip_test_ws {

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

        $this->give_permissions(array('local/elisprogram:user_edit'));

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

        $response = local_datahub_elis_user_update::user_update($update);

        // Verify general response structure.
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);

        // Verify response message/code.
        $this->assertEquals(get_string('ws_user_update_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_user_update_success_msg', 'local_datahub'), $response['message']);

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

        $this->give_permissions(array('local/elisprogram:user_edit'));

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

        $response = local_datahub_elis_user_update::user_update($update);
    }
}