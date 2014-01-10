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
    require_once(elispm::lib('data/clustercurriculum.class.php'));
    require_once(elispm::lib('data/curriculum.class.php'));
    require_once(elispm::lib('data/curriculumcourse.class.php'));
    require_once(elispm::lib('data/curriculumstudent.class.php'));
    require_once(elispm::lib('data/track.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/userset.class.php'));
    require_once(elispm::lib('data/usertrack.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/program_delete.class.php');
}

/**
 * Tests webservice method local_datahub_elis_program_delete.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_program_delete_testcase extends rlip_test_ws {

    /**
     * Test successful program delete
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
        $fieldctx->contextlevel = CONTEXT_ELIS_PROGRAM;
        $fieldctx->save();

        // Create test program to delete.
        $cur = new curriculum(array('idnumber' => 'testprogram', 'name' => 'testprogram'));
        $cur->save();

        $program = array(
            'idnumber' => 'testprogram',
        );

        $this->give_permissions(array('local/elisprogram:program_delete'));
        $response = local_datahub_elis_program_delete::program_delete($program);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_program_delete_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_program_delete_success_msg', 'local_datahub'), $response['message']);

        $this->assertFalse($DB->record_exists(curriculum::TABLE, array('idnumber' => 'testprogram')));
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
                // Test invalid input.
                array(
                        array(
                            'name' => 'test',
                        )
                ),
                // Test non-existant program.
                array(
                        array(
                            'idnumber' => 'BogusProgramIdnumber',
                        )
                )
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $prg The incoming program data.
     */
    public function test_failure(array $prg) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:program_delete'));

        // Create test program to update.
        $cur = new curriculum(array('idnumber' => 'testprogram', 'name' => 'testprogram'));
        $cur->save();

        $response = local_datahub_elis_program_delete::program_delete($prg);
    }
}