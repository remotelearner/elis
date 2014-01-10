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
    require_once(elispm::lib('data/student.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/class_enrolment_update.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method local_datahub_elis_class_enrolment_update.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_class_enrolment_update_test extends rlip_test_ws {

    /**
     * Method to create test user class enrolment
     * @param int $userid the user DB id
     * @param int $classid the class DB id
     */
    public function create_class_enrolment($userid, $classid) {
        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        $record = new stdClass;
        $record->userid = $userid;
        $record->classid = $classid;
        $record->completestatusid = 0;
        $record->grade = 50;
        $record->credits = 1;
        $record->locked = 0;
        $record->enrolmenttime = $importplugin->parse_date('Jan/10/2013');
        $stu = new student($record);
        $stu->save();
    }

    /**
     * Test successful class enrolment update.
     */
    public function test_success() {
        global $DB, $USER;

        $this->give_permissions(array('local/elisprogram:class_enrol'));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        // Create test course and class.
        $datagen = new elis_program_datagenerator($DB);
        $crs = $datagen->create_course(array('idnumber' => 'TestCourse'));
        $cls = $datagen->create_pmclass(array('idnumber' => 'TestClassEnrolmentCreate', 'courseid' => $crs->id));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));
        $this->create_class_enrolment($userid, $cls->id);

        $data = array(
            'class_idnumber' => $cls->idnumber,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@example.com',
            'completestatus' => 'passed',
            'grade' => 67.89,
            'credits' => 1.1,
            'locked' => 1,
            'enrolmenttime' => 'May/08/2013',
            'completetime' => 'May/31/2013',
        );

        $expectdata = array(
            'classid' => $cls->id,
            'userid' => $userid,
            'completestatusid' => STUSTATUS_PASSED,
            'grade' => 67.89,
            'credits' => 1.1,
            'locked' => 1,
            'enrolmenttime' => $importplugin->parse_date('May/08/2013'),
            'completetime' => $importplugin->parse_date('May/31/2013'),
        );

        $response = local_datahub_elis_class_enrolment_update::class_enrolment_update($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_class_enrolment_update_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_class_enrolment_update_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get record.
        $stu = $DB->get_record(student::TABLE, array('id' => $response['record']['id']));
        $this->assertNotEmpty($stu);
        $stu = (array)$stu;
        foreach ($expectdata as $param => $val) {
            $this->assertArrayHasKey($param, $stu, $param);
            $this->assertEquals($val, $stu[$param], $param);
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
                            'class_idnumber' => 'TestClassEnrolmentUpdate',
                        )
                ),
                // Test invalid class.
                array(
                        array(
                            'class_idnumber' => 'BogusClass',
                        )
                ),
                // Test invalid completestatus.
                array(
                        array(
                            'class_idnumber' => 'TestClassEnrolmentUpdate',
                            'user_idnumber' => 'assigninguserid',
                            'completestatus' => 'bogusStatus',
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
                            'class_idnumber' => 'TestClassEnrolmentUpdate',
                            'user_email' => 'assigninguser@example.com',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $data The incoming class enrolment data.
     */
    public function test_failure(array $data) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:class_enrol'));

        // Create test course and class.
        $datagen = new elis_program_datagenerator($DB);
        $crs = $datagen->create_course(array('idnumber' => 'TestCourse'));
        $cls = $datagen->create_pmclass(array('idnumber' => 'TestClassEnrolmentUpdate', 'courseid' => $crs->id));

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'assigninguser'));
        $this->create_class_enrolment($userid, $cls->id);

        $response = local_datahub_elis_class_enrolment_update::class_enrolment_update($data);
    }
}