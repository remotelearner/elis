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
    require_once($dirname.'/../ws/elis/class_update.class.php');
}

/**
 * Tests webservice method local_datahub_elis_class_update.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_class_update_testcase extends rlip_test_ws {

    /**
     * Test successful user creation.
     */
    public function test_success() {
        global $DB;

        $this->give_permissions(array('local/elisprogram:class_edit'));

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

        $class = new pmclass(array(
            'idnumber' => 'testclass',
            'startdate' => 1357016400,
            'enddate' => 1359694800,
            'courseid' => $course->id,
            'assignment' => $course->idnumber,
            'field_testfield' => 'Test Field',
        ));
        $class->save();

        $classupdates = array(
            'idnumber' => 'testclass',
            'startdate' => 'Feb/04/2013',
            'enddate' => 'Mar/01/2013',
            'field_testfield' => 'Test Field 2',
        );

        $response = local_datahub_elis_class_update::class_update($classupdates);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_class_update_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_class_update_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get class.
        $expectedclass = array(
            'idnumber' => $class->idnumber,
            'startdate' => rlip_timestamp(0, 0, 0, 2, 4, 2013),
            'enddate' => rlip_timestamp(0, 0, 0, 3, 1, 2013),
            'courseid' => $course->id,
            'field_testfield' => 'Test Field 2',
        );
        $createdclass = new pmclass($response['record']['id']);
        $createdclass->load();
        $createdclass = $createdclass->to_array();
        foreach ($expectedclass as $param => $val) {
            $this->assertArrayHasKey($param, $createdclass);
            $this->assertEquals($val, $createdclass[$param]);
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
                // Test invalid idnumber.
                array(
                        array(
                            'idnumber' => 'testclass2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test trying to reassign.
                array(
                        array(
                            'idnumber' => 'testclass1',
                            'assignment' => 'testcourse2',
                            'startdate' => 'Jan/01/2013',
                        )
                ),
                // Test no permissions.
                array(
                        array(
                            'idnumber' => 'testclass1',
                            'startdate' => 'Jan/01/2013',
                        ),
                        false
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $classupdate The incoming class update data.
     */
    public function test_failure(array $classupdate, $giveperms = true) {
        global $DB;

        if ($giveperms === true) {
            $this->give_permissions(array('local/elisprogram:class_edit'));
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

        $response = local_datahub_elis_class_update::class_update($classupdate);
    }
}