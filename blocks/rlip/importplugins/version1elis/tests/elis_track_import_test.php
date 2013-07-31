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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
    require_once($CFG->dirroot.'/elis/program/lib/setup.php');
    require_once(elispm::lib('data/track.class.php'));
}

/**
 * Test track import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_track_import_testcase extends rlip_elis_test {

    /**
     * Test import with minimum required fields.
     */
    public function test_create_elis_track_minimum_import() {
        global $DB;

        $this->run_core_program_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_curriculum', array('idnumber' => 'testprogramid')));

        $this->run_core_track_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_track', array('idnumber' => 'testtrackid')));
    }

    /**
     * Test import with all required fields
     */
    public function test_create_elis_track_max_import() {
        global $DB;

        $this->run_core_program_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_curriculum', array('idnumber' => 'testprogramid')));

        $data = $this->get_core_track_data();
        $data['description'] = 'testdescription';
        $data['startdate'] = 'Jan/01/2012';
        $data['enddate'] = 'Jan/01/2012';
        $this->run_core_track_import($data, false);

        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);
        $data['enddate'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);

        unset($data['action'], $data['context'], $data['assignment'], $data['description']);
        $this->assertTrue($DB->record_exists('crlm_track', $data));
    }

    /**
     * Test delete track import.
     */
    public function test_delete_elis_track_import() {
        global $DB;

        $this->run_core_program_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_curriculum', array('idnumber' => 'testprogramid')));

        $this->run_core_track_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_track', array('idnumber' => 'testtrackid')));

        $data = array('action' => 'delete', 'context' => 'track', 'idnumber' => 'testtrackid');
        $this->run_core_track_import($data, false);

        unset($data['action'], $data['context']);
        $this->assertFalse($DB->record_exists('crlm_track', $data));
    }

    /**
     * Test update track import.
     */
    public function test_update_elis_track_import() {
        global $DB;

        $this->run_core_program_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_curriculum', array('idnumber' => 'testprogramid')));

        $this->run_core_track_import(array(), true);

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrackpdated',
            'description' => 'testdescriptionupdated',
            'startdate' => 'Jan/01/2012',
            'enddate' => 'Jan/01/2012'
        );
        $this->run_core_track_import($data, false);

        unset($data['action'], $data['context'], $data['description']);
        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);
        $data['enddate'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);

        $this->assertTrue($DB->record_exists('crlm_track', $data));
    }

    /**
     * Data provider for mapping yes to 1 and no to 0
     */
    public function field_provider() {
        return array(
                array('0', '0'),
                array('1', '1'),
                array('yes', '1'),
                array('no', '0')
        );
    }

    /**
     * @dataProvider field_provider
     * @param string The import data (0, 1, yes, no)
     * @param string The expected data (0, 1)
     */
    public function test_elis_track_autocreate_import($data, $expected) {
        global $CFG, $DB;

        $this->run_core_program_import(array(), true);

        $record = array();
        $record = $this->get_core_track_data();
        $record['autocreate'] = $data;

        $this->run_core_track_import($record, false);

        $this->assertEquals(true, $DB->record_exists(track::TABLE, array('idnumber' => $record['idnumber'])));
    }

    /**
     * Helper function to get the core fields for a sample track
     *
     * @return array The track data
     */
    private function get_core_track_data() {
        $data = array(
            'action' => 'create',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'assignment' => 'testprogramid'
        );
        return $data;
    }

    /**
     * Helper function to get the core fields for a sample program
     *
     * @return array The program data
     */
    private function get_core_program_data() {
        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramid',
            'name' => 'testprogram'
        );
        return $data;
    }

    /**
     * Helper function that runs the track import for a sample track
     *
     * @param array $extradata Extra fields to set for the new track
     */
    private function run_core_track_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_track_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1elis_importprovider_mocktrack($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Helper function that runs the program import for a sample program
     *
     * @param array $extradata Extra fields to set for the new program
     */
    private function run_core_program_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_program_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1elis_importprovider_mockprogram($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

}
