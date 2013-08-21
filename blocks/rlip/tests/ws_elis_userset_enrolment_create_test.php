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
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

$dirname = dirname(__FILE__);
require_once($dirname.'/../../../elis/core/test_config.php');
global $CFG;
require_once($dirname.'/other/rlip_test.class.php');

// Libs.
require_once($dirname.'/../lib.php');
require_once($CFG->libdir.'/externallib.php');
if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
    require_once($CFG->dirroot.'/elis/program/lib/setup.php');
    require_once(elispm::lib('data/clusterassignment.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once(elispm::file('plugins/userset_classification/usersetclassification.class.php'));
    require_once($dirname.'/../ws/elis/userset_enrolment_create.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method block_rldh_elis_userset_enrolment_create.
 * @group block_rlip
 * @group block_rlip_ws
 */
class block_rlip_ws_elis_userset_enrolment_create_testcase extends rlip_test_ws {

    /**
     * Test successful program enrolment creation.
     */
    public function test_success() {
        global $DB, $USER;

        $this->give_permissions(array('elis/program:userset_enrol'));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        // Create test userset.
        $datagen = new elis_program_datagenerator($DB);
        $userset = $datagen->create_userset(array('name' => 'TestUsersetEnrolmentCreate'));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));

        $data = array(
            'userset_name' => $userset->name,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@example.com',
            'leader' => true
        );
        $expectdata = array(
            'clusterid' => $userset->id,
            'userid' => $userid,
            'plugin' => 'manual',
            'leader' => '1' // PARAM_BOOL returned as string by WS API.
        );

        $response = block_rldh_elis_userset_enrolment_create::userset_enrolment_create($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_userset_enrolment_create_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_userset_enrolment_create_success_msg', 'block_rlip'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get record.
        $clstass = $DB->get_record(clusterassignment::TABLE, array('id' => $response['record']['id']));
        $this->assertNotEmpty($clstass);
        $clstass= (array)$clstass;
        foreach ($expectdata as $param => $val) {
            $this->assertArrayHasKey($param, $clstass, $param);
            $this->assertEquals($val, $clstass[$param], $param);
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
                            'user_email' => 'assigninguser@example.com',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'userset_name' => 'TestUsersetEnrolmentCreate',
                        )
                ),
                // Test invalid input.
                array(
                        array(
                            'userset_name' => 'BogusUserset',
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
                            'userset_name' => 'TestUsersetEnrolmentCreate',
                            'user_email' => 'assigninguser@example.com',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $data The incoming program enrolment data.
     */
    public function test_failure(array $data) {
        global $DB;

        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_userset(array('name' => 'TestUsersetEnrolmentCreate'));

        $this->give_permissions(array('elis/program:userset_enrol'));
        $response = block_rldh_elis_userset_enrolment_create::userset_enrolment_create($data);
    }
}
