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
require_once($dirname.'/../ws/elis/class_create.class.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
}

/**
 * Tests webservice method local_datahub_elis_class_create.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_class_create_testcase extends rlip_test_ws {

    /**
     * Test successful user creation.
     */
    public function test_success() {
        global $DB;

        $this->give_permissions(array('local/elisprogram:class_create'));

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
        $fieldctx->contextlevel = CONTEXT_ELIS_CLASS;
        $fieldctx->save();

        $course = new course;
        $course->idnumber = 'testcourse1';
        $course->name = 'Test Course 1';
        $course->syllabus = 'Test';
        $course->save();

        $class = array(
            'idnumber' => 'testuser',
            'startdate' => 'Jan/01/2013',
            'enddate' => 'Feb/01/2013',
            'assignment' => $course->idnumber,
            'field_testfield' => 'Test Field',
        );

        $response = local_datahub_elis_class_create::class_create($class);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_class_create_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_class_create_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get class.
        $expectedclass = array(
            'idnumber' => $class['idnumber'],
            'courseid' => $course->id,
            'field_testfield' => 'Test Field',
        );
        $createdclass = new pmclass($response['record']['id']);
        $createdclass->load();
        $createdclass = $createdclass->to_array();
        foreach ($expectedclass as $param => $val) {
            $this->assertArrayHasKey($param, $createdclass);
            $this->assertEquals($val, $createdclass[$param], $param);
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
                // Test no required input.
                array(
                        array(
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test not all required input. (no assignment).
                array(
                        array(
                            'idnumber' => 'testclass2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test not all required input. (no idnumber).
                array(
                        array(
                            'assignment' => 'testcourse1',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test non-existent assignment.
                array(
                        array(
                            'idnumber' => 'testclass2',
                            'assignment' => 'testcourse2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test no permissions.
                array(
                        array(
                            'idnumber' => 'testclass2',
                        ),
                        false
                ),
                // Test duplicate idnumber.
                array(
                        array(
                            'idnumber' => 'testclass1',
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
    public function test_failure(array $user, $giveperms = true) {
        global $DB;

        if ($giveperms === true) {
            $this->give_permissions(array('local/elisprogram:class_create'));
        }

        $course = new course;
        $course->idnumber = 'testcourse1';
        $course->name = 'Test Course 1';
        $course->syllabus = 'Test';
        $course->save();

        // Create a class (used for duplicate test).
        $class = new pmclass;
        $class->idnumber = 'testclass1';
        $class->courseid = $course->id;
        $class->save();

        $response = local_datahub_elis_class_create::class_create($user);
    }
}