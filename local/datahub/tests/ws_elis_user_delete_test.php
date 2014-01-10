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
    require_once(elispm::lib('data/curriculumstudent.class.php'));
    require_once($dirname.'/../ws/elis/user_delete.class.php');
}

/**
 * Tests webservice method local_datahub_elis_user_delete.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_user_delete_testcase extends rlip_test_ws {

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

        $this->give_permissions(array('local/elisprogram:user_delete'));

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

        $response = local_datahub_elis_user_delete::user_delete($params);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);

        // Verify unique message code.
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertEquals(get_string('ws_user_delete_success_code', 'local_datahub'), $response['messagecode']);

        // Verify human-readable message.
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_user_delete_success_msg', 'local_datahub'), $response['message']);
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
            $this->give_permissions(array('local/elisprogram:user_delete'));
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

        $response = local_datahub_elis_user_delete::user_delete($params);
    }
}