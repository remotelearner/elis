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
    require_once(elispm::lib('data/usertrack.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/track_enrolment_create.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method block_rldh_elis_track_enrolment_create.
 * @group block_rlip
 * @group block_rlip_ws
 */
class block_rlip_ws_elis_track_enrolment_create_testcase extends rlip_test_ws {

    /**
     * Test successful track enrolment creation.
     */
    public function test_success() {
        global $DB, $USER;

        $this->give_permissions(array('elis/program:track_enrol'));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        // Create test program and track.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'TestProgramForTrack'));
        $track = $datagen->create_track(array('idnumber' => 'TestTrackForProgram', 'curid' => $program->id));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));

        $data = array(
            'track_idnumber' => $track->idnumber,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@example.com',
        );

        $expectdata = array(
            'userid' => $userid,
            'trackid' => $track->id,
        );

        $response = block_rldh_elis_track_enrolment_create::track_enrolment_create($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_track_enrolment_create_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_track_enrolment_create_success_msg', 'block_rlip'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get record.
        $usertrack = $DB->get_record(usertrack::TABLE, array('id' => $response['record']['id']));
        $this->assertNotEmpty($usertrack);
        $usertrack = (array)$usertrack;
        foreach ($expectdata as $param => $val) {
            $this->assertArrayHasKey($param, $usertrack, $param);
            $this->assertEquals($val, $usertrack[$param], $param);
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
                            'track_idnumber' => 'TestTrackForProgram',
                        )
                ),
                // Test invalid input.
                array(
                        array(
                            'track_idnumber' => 'BogusTrack',
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
                            'track_idnumber' => 'TestTrackForProgram',
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

        // Create test program and track.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'TestProgramForTrack'));
        $track = $datagen->create_track(array('idnumber' => 'TestTrackForProgram', 'curid' => $program->id));

        $this->give_permissions(array('elis/program:track_enrol'));
        $response = block_rldh_elis_track_enrolment_create::track_enrolment_create($data);
    }
}