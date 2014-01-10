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
    require_once($dirname.'/../ws/elis/user_create.class.php');
}

/**
 * Tests webservice method local_datahub_elis_user_create.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_user_create_testcase extends rlip_test_ws {

    /**
     * Test successful user creation.
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

        $tempuser = new user;
        $tempuser->reset_custom_field_list();

        $this->give_permissions(array('local/elisprogram:user_create'));
        $response = local_datahub_elis_user_create::user_create($user);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_user_create_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_user_create_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get user.
        $createduser = new user($response['record']['id']);
        $createduser->load();
        $createduser = $createduser->to_array();
        foreach ($user as $param => $val) {
            $this->assertArrayHasKey($param, $createduser);
            $this->assertEquals($val, $createduser[$param]);
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
                            'username' => 'test',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'username' => 'test',
                            'idnumber' => 'test',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'username' => 'test',
                            'idnumber' => 'test',
                            'email' => 'test@test.com',
                        )
                ),
                // Test duplicate user (uses assigning user information from $this->give_permissions).
                array(
                        array(
                            'idnumber' => 'assigninguserid',
                            'username' => 'assigninguser',
                            'firstname' => 'assigninguser',
                            'lastname' => 'assigninguser',
                            'email' => 'assigninguser@example.com',
                            'country' => 'CA'
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $user The incoming user data.
     */
    public function test_failure(array $user) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:user_create'));
        $response = local_datahub_elis_user_create::user_create($user);
    }
}