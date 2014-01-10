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
    require_once(elispm::lib('data/curriculum.class.php'));
    require_once(elispm::lib('data/curriculumcourse.class.php'));
    require_once(elispm::lib('data/curriculumstudent.class.php'));
    require_once(elispm::lib('data/course.class.php'));
    require_once(elispm::lib('data/coursetemplate.class.php'));
    require_once($dirname.'/../ws/elis/course_update.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method local_datahub_elis_course_update.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_course_update_testcase extends rlip_test_ws {

    /**
     * Test successful course update.
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
        $fieldctx->contextlevel = CONTEXT_ELIS_COURSE;
        $fieldctx->save();

        // Grant permissions.
        $this->give_permissions(array('local/elisprogram:course_edit'));

        // Create test program.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'TestProgram'));

        // Create test course to update.
        $crs = new course(array('idnumber' => 'TestCourse', 'name' => 'Test Course', 'syllabus' => ''));
        $crs->save();

        $course = array(
            'idnumber' => 'TestCourse',
            'name' => 'Test Course',
            'code' => 'CRS1',
            'syllabus' => 'Test syllabus',
            'lengthdescription' => 'Weeks',
            'length' => 2,
            'credits' => 1.1,
            'completion_grade' => 50,
            'cost' => '$100',
            'version' => '1.0.0',
            'assignment'=> $program->idnumber,
        );

        // Update test course.
        $response = local_datahub_elis_course_update::course_update($course);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_course_update_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_course_update_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get course.
        $updatedcourse = new course($response['record']['id']);
        $updatedcourse->load();
        $updatedcourse = $updatedcourse->to_array();
        foreach ($course as $param => $val) {
            if ($param != 'assignment') {
                $this->assertArrayHasKey($param, $updatedcourse);
                $this->assertEquals($val, $updatedcourse[$param]);
            }
        }

        // Check that course was assigned to program.
        $curriculumcourseid = $DB->get_field(curriculumcourse::TABLE, 'id', array(
            'curriculumid' => $program->id,
            'courseid' => $response['record']['id']
        ));
        $this->assertNotEmpty($curriculumcourseid);
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
                            'name' => 'Test Course',
                            'syllabus' => '',
                        )
                ),
                // Test invalid credits.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'syllabus' => '',
                            'credits' => -1,
                        )
                ),
                // Test invalid completion grade.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'syllabus' => '',
                            'completiongrade' => -1,
                        )
                ),
                // Test invalid program assignment.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'syllabus' => '',
                            'assignment' => 'bogusProgram',
                        )
                ),
                // Test invalid Moodle course template.
                array(
                        array(
                            'idnumber' => 'testcourse',
                            'name' => 'Test Course',
                            'syllabus' => '',
                            'link' => 'bogusTemplate',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $coursetoupdate The incoming ELIS course data.
     */
    public function test_failure(array $coursetoupdate) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:course_edit'));

        // Create test course to update.
        $crs = new course(array('idnumber' => 'testcourse', 'name' => 'Test Course', 'syllabus' => ''));
        $crs->save();

        $response = local_datahub_elis_course_update::course_update($coursetoupdate);
    }
}