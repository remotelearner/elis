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
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/pmclass.class.php'));

/**
 * Test class import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_class_import_test extends rlip_elis_test {

    /**
     * Test creating an elis class.
     */
    public function test_create_elis_class_import() {
        global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_class', array('idnumber' => 'testclassid')));
    }

    /**
     * Test deleting an elis class.
     */
    public function test_delete_elis_class_import() {
        global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);

        $data = array('action' => 'delete', 'context' => 'class', 'idnumber' => 'testclassid');
        $this->run_elis_class_import($data, false);

        unset($data['action'], $data['context']);
        $this->assertFalse($DB->record_exists('crlm_class', $data));
    }

    /**
     * Test updating an elis class.
     */
    public function test_update_elis_class_import() {
        global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);

        $data = array('action' => 'update', 'context' => 'class', 'idnumber' => 'testclassid', 'maxstudents' => 30);
        $this->run_elis_class_import($data, false);

        unset($data['action'], $data['context']);
        $this->assertTrue($DB->record_exists('crlm_class', $data));
    }

    /**
     * Data provider for mapping yes to 1 and no to 0.
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
    public function test_elis_class_enrol_from_waitlist_import($data, $expected) {
        global $CFG, $DB;

        $this->run_elis_course_import(array(), true);

        $record = array();
        $record = $this->get_core_class_data();
        $record['enrol_from_waitlist'] = $data;

        $this->run_elis_class_import($record, false);

        $this->assertEquals(true, $DB->record_exists(pmclass::TABLE, array(
            'idnumber' => $record['idnumber'],
            'enrol_from_waitlist' => $expected
        )));
    }

    /**
     * @dataProvider field_provider
     * @param string The import data (0, 1, yes, no)
     * @param string The expected data (0, 1)
     */
    public function test_elis_class_autoenrol_import($data, $expected) {
        global $CFG, $DB;

        $this->run_elis_course_import(array(), true);

        $record = array();
        $record = $this->get_core_class_data();
        $record['autoenrol'] = $data;

        $this->run_elis_class_import($record, false);
        $this->assertEquals(true, $DB->record_exists(pmclass::TABLE, array('idnumber' => $record['idnumber'])));
    }

    /**
     * Helper function to get the core fields for a sample class
     *
     * @return array The program data
     */
    private function get_core_class_data() {
        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'assignment' => 'testcourseid',
            'maxstudents' => 40
        );
        return $data;
    }

    /**
     * Helper function to get the core fields for a sample course
     *
     * @return array The course data
     */
    private function get_core_course_data() {
        $data = array(
            'action' => 'create',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcoursename'
        );
        return $data;
    }

    /**
     * Helper function that runs the class import for a sample class
     *
     * @param array $extradata Extra fields to set for the new class
     */
    private function run_elis_class_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_class_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1elis_importprovider_mockclass($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Helper function that runs the course import for a sample course
     *
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_elis_course_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_course_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1elis_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }
}