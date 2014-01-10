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
    require_once(elispm::lib('data/clusterassignment.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/userset_enrolment_delete.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method local_datahub_elis_userset_enrolment_delete.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_userset_enrolment_delete_testcase extends rlip_test_ws {

    /**
     * method to create test userset
     * @param string $name the name to use to create userset
     * @return int|bool the userset DB id or false on error
     */
    public function create_userset($name) {
        $params = array(
            'name' => $name,
        );
        $us = new userset($params);
        $us->save();
        return !empty($us->id) ? $us->id : false;
    }

    /**
     * Test successful userset enrolment deletion.
     */
    public function test_success() {
        global $DB, $USER;

        $this->give_permissions(array('local/elisprogram:userset_enrol'));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        // Create test userset.
        $datagen = new elis_program_datagenerator($DB);
        $userset = $datagen->create_userset(array('name' => 'TestUsersetEnrolmentDelete'));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));

        // Create the userset enrolment record to delete.
        $clstass = new clusterassignment(array('clusterid' => $userset->id, 'userid' => $userid, 'plugin' => 'manual'));
        $clstass->save();

        $data = array(
            'userset_name' => $userset->name,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@example.com',
        );

        $response = local_datahub_elis_userset_enrolment_delete::userset_enrolment_delete($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_userset_enrolment_delete_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_userset_enrolment_delete_success_msg', 'local_datahub'), $response['message']);
        $this->assertFalse($DB->record_exists(clusterassignment::TABLE, array(
            'clusterid' => $userset->id,
            'userid' => $userid,
            'plugin' => 'manual'
        )));
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
                            'user_email' => 'assigninguser@example.com',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'userset_name' => 'TestUsersetEnrolmentDelete',
                        )
                ),
                // Test invalid input.
                array(
                        array(
                            'userset_name' => 'BogusProgram',
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
                            'userset_name' => 'TestUsersetEnrolmentDelete',
                            'user_email' => 'assigninguser@example.com',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $data The incoming userset enrolment delete data.
     */
    public function test_failure(array $data) {
        global $DB;

        // Create test userset.
        $datagen = new elis_program_datagenerator($DB);
        $userset = $datagen->create_userset(array('name' => 'TestUsersetEnrolmentDelete'));

        $this->give_permissions(array('local/elisprogram:userset_enrol'));
        $response = local_datahub_elis_userset_enrolment_delete::userset_enrolment_delete($data);
    }
}