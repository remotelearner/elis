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
    require_once(elispm::lib('data/track.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($dirname.'/../ws/elis/track_create.class.php');
    require_once(elispm::file('tests/other/datagenerator.php'));
}

/**
 * Tests webservice method local_datahub_elis_track_create.
 * @group local_datahub
 * @group local_datahub_ws
 */
class local_datahub_ws_elis_track_create_testcase extends rlip_test_ws {

    /**
     * Test successful track creation.
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
        $fieldctx->contextlevel = CONTEXT_ELIS_TRACK;
        $fieldctx->save();

        $this->give_permissions(array('local/elisprogram:track_create'));

        // Setup program.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'test_program_idnumber', 'name' => 'ProgramName'));

        $track = array(
            'idnumber' => 'testtrack',
            'name' => 'testtrackname',
            'assignment' => $program->idnumber,
            'field_testfield' => 'Test field'
        );

        $response = local_datahub_elis_track_create::track_create($track);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(get_string('ws_track_create_success_code', 'local_datahub'), $response['messagecode']);
        $this->assertEquals(get_string('ws_track_create_success_msg', 'local_datahub'), $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Track.
        $createdtrk = new track($response['record']['id']);
        $createdtrk->load();
        $createdtrk = $createdtrk->to_array();
        $expecttrk = array(
            'id' => $response['record']['id'],
            'idnumber' => 'testtrack',
            'name' => 'testtrackname',
            'curid' => $program->id,
            'description' => '',
            'field_testfield' => 'Test field'
        );
        foreach ($expecttrk as $param => $val) {
            $this->assertArrayHasKey($param, $createdtrk);
            $this->assertEquals($val, $createdtrk[$param]);
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
                            'idnumber' => 'testtrack',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'assignment' => 'test_program_idnumber',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'name' => 'testtrackname',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'name' => 'testtrackname',
                            'assignment' => 'test_program_idnumber',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'idnumber' => 'testtrack',
                            'name' => 'testtrackname',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'idnumber' => 'testtrack',
                            'assignment' => 'test_program_idnumber',
                        )
                ),
                // Test invalid assignment.
                array(
                        array(
                            'idnumber' => 'testtrack',
                            'name' => 'testtrackname',
                            'assignment' => 'BogusProgram',
                        )
                ),
                // Test invalid startdate.
                array(
                        array(
                            'idnumber' => 'testtrack',
                            'name' => 'testtrackname',
                            'assignment' => 'test_program_idnumber',
                            'startdate' => 'XYZ/01/2013',
                        )
                ),
                // Test invalid enddate.
                array(
                        array(
                            'idnumber' => 'testtrack',
                            'name' => 'testtrackname',
                            'assignment' => 'test_program_idnumber',
                            'enddate' => 'Feb/31/2013',
                        )
                ),
                // Test duplicate track.
                array(
                        array(
                            'idnumber' => 'DupTrackIdnumber',
                            'assignment' => 'test_program_idnumber',
                        )
                )
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $track The incoming track data.
     */
    public function test_failure(array $track) {
        global $DB;

        $this->give_permissions(array('local/elisprogram:track_create'));

        // Setup program.
        $datagen = new elis_program_datagenerator($DB);
        $program = $datagen->create_program(array('idnumber' => 'test_program_idnumber', 'name' => 'ProgramName'));

        // Setup duplicate track.
        $duptrk = new track(array('idnumber' => 'DupTrackIdnumber', 'name' => 'DupTrackName', 'curid' => $program->id));
        $duptrk->save();

        $response = local_datahub_elis_track_create::track_create($track);
    }
}