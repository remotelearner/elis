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
    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/class_delete.class.php');
}

/**
 * Tests webservice method local_datahub_elis_class_delete.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_class_delete_testcase extends rlip_test_ws {

    /**
     * Test successful class deletion.
     */
    public function test_success() {
        global $DB;

        $this->give_permissions(array('local/elisprogram:class_delete'));

        $course = new course(array(
            'idnumber' => 'testcourse',
            'name' => 'Test Course',
            'syllabus' => '',
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'idnumber' => 'testclass',
            'courseid' => $course->id,
        ));
        $pmclass->save();

        $response = local_datahub_elis_class_delete::class_delete(array('idnumber' => 'testclass'));

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);

        // Verify unique message code.
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertEquals(get_string('ws_class_delete_success_code', 'local_datahub'), $response['messagecode']);

        // Verify human-readable message.
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_class_delete_success_msg', 'local_datahub'), $response['message']);
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
                            'idnumber' => '',
                        )
                ),
                // Test non-existant identifying field value.
                array(
                        array(
                            'idnumber' => 'testclass2',
                        )
                ),
                // Test No Permissions.
                array(
                        array(
                            'idnumber' => 'testclass',
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
            $this->give_permissions(array('local/elisprogram:class_delete'));
        }

        $course = new course(array(
            'idnumber' => 'testcourse',
            'name' => 'Test Course',
            'syllabus' => '',
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'idnumber' => 'testclass',
            'courseid' => $course->id,
        ));
        $pmclass->save();

        $response = local_datahub_elis_class_delete::class_delete($params);
    }
}