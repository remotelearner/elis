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
    require_once(elispm::lib('data/curriculumstudent.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/program_enrolment_delete.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method block_rldh_elis_program_enrolment_delete.
 * @group block_rlip
 * @group block_rlip_ws
 */
class block_rlip_ws_elis_program_enrolment_delete_testcase extends rlip_test_ws {

    /**
     * Test successful program enrolment creation.
     */
    public function test_success() {
        global $DB;

        $this->give_permissions(array('elis/program:program_enrol'));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        // Create test program.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'TestProgramEnrolmentCreate'));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));

        // Create the program enrolment record to delete.
        $stucur = new curriculumstudent(array('curriculumid' => $program->id, 'userid' => $userid));
        $stucur->save();

        // Perform enrolment delete.
        $data = array(
            'program_idnumber' => $program->idnumber,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@example.com',
        );
        $response = block_rldh_elis_program_enrolment_delete::program_enrolment_delete($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_program_enrolment_delete_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_program_enrolment_delete_success_msg', 'block_rlip'), $response['message']);
        $this->assertFalse($DB->record_exists(curriculumstudent::TABLE, array(
            'curriculumid' => $program->id,
            'userid' => $userid
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
                            'program_idnumber' => 'TestProgramEnrolmentCreate',
                        )
                ),
                // Test invalid input.
                array(
                        array(
                            'program_idnumber' => 'BogusProgram',
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
                            'program_idnumber' => 'TestProgramEnrolmentCreate',
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

        // Create test program.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'TestProgramEnrolmentCreate'));

        $this->give_permissions(array('elis/program:program_enrol'));
        $response = block_rldh_elis_program_enrolment_delete::program_enrolment_delete($data);
    }
}