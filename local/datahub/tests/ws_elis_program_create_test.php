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
    require_once(elispm::lib('data/curriculum.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/program_create.class.php');
}

/**
 * Tests webservice method local_datahub_elis_program_create.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_program_create_testcase extends rlip_test_ws {

    /**
     * Test successful program creation.
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

        $program = array(
            'idnumber' => 'testprogram',
            'name' => 'testprgram',
            'reqcredits' => 4.5,
            'timetocomplete' => '6m',
            'frequency' => '1y',
            'field_testfield' => 'Test Field',
        );

        $this->give_permissions(array('local/elisprogram:program_create'));
        $response = local_datahub_elis_program_create::program_create($program);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_program_create_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_program_create_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Program.
        $createdprg = new curriculum($response['record']['id']);
        $createdprg->load();
        $createdprg = $createdprg->to_array();
        foreach ($program as $param => $val) {
            $this->assertArrayHasKey($param, $createdprg);
            $this->assertEquals($val, $createdprg[$param]);
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
                            'idnumber' => 'test',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'name' => 'test',
                        )
                ),
                // Test invalid reqcredits.
                array(
                        array(
                            'idnumber' => 'testprogram',
                            'name' => 'test program name',
                            'description' => 'test program description',
                            'reqcredits' => 123456789.123
                        )
                ),
                // Test invalid timetocomplete.
                array(
                        array(
                            'idnumber' => 'testprogram',
                            'name' => 'test program name',
                            'description' => 'test program description',
                            'timetocomplete' => '22x'
                        )
                ),
                // Test invalid frequency.
                array(
                        array(
                            'idnumber' => 'testprogram',
                            'name' => 'test program name',
                            'description' => 'test program description',
                            'frequency' => '22x'
                        )
                ),
                // Test invalid priority.
                array(
                        array(
                            'idnumber' => 'testprogram',
                            'name' => 'test program name',
                            'description' => 'test program description',
                            'priority' => 22
                        )
                ),
                // Test duplicate program.
                array(
                        array(
                            'idnumber' => 'DupProgramIdnumber',
                            'name' => 'DupProgramName',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $programtocreate The incoming program data.
     */
    public function test_failure(array $programtocreate) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:program_create'));

        // Setup duplicate program.
        $dupcur = new curriculum(array('idnumber' => 'DupProgramIdnumber', 'name' => 'DupProgramName'));
        $dupcur->save();

        $response = local_datahub_elis_program_create::program_create($programtocreate);
    }
}