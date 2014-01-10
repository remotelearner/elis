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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');

/**
 * Unit test for validating basic userset actions.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_userset_import_testcase extends rlip_elis_test {

    /**
     * Validate that a userset can be created with a minimal set of fields specified
     */
    public function test_create_elis_userset_import_with_minimal_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Run the import.
        $this->run_core_userset_import(array(), true);

        // Validation.
        $data = array(
            'name' => 'testusersetname',
            'display' => '',
            'parent' => 0,
            'depth' => 1
        );
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Validate that a userset can be created, setting all available fields
     */
    public function test_create_elis_userset_import_with_all_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Set up a parent userset.
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        // Run the import.
        $data = array('display' => 'testusersetdisplay',
                      'parent' => 'testparentusersetname');
        $this->run_core_userset_import($data, true);

        // Validation.
        $data['name'] = 'testusersetname';
        $data['parent'] = $parent->id;
        $data['depth'] = 2;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Data provider for a minimum update
     *
     * @return array Data needed for the appropriate unit test
     */
    public function minimal_update_field_provider() {
        return array(array('display', 'updatedusersetdisplay'));
    }

    /**
     * Validate that a userset can be updated with a minimal set of fields specified
     *
     * NOTE: This test current performs the same function of the next unit test, but is
     * conceptually different
     *
     * @param string $fieldname The name of the one import field we are setting
     * @param string $value The value to set for that import field
     * @dataProvider minimal_update_field_provider
     */
    public function test_update_elis_userset_import_with_minimal_fields($fieldname, $value) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Set up a userset.
        $userset = new userset(array('name' => 'testusersetname', 'display' => 'testusersetdisplay'));
        $userset->save();

        // Run the import.
        $data = array('action' => 'update', $fieldname => $value);
        $this->run_core_userset_import($data, true);

        // Validation.
        unset($data['action']);
        $data['name'] = 'testusersetname';
        $data['parent'] = 0;
        $data['depth'] = 1;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
        $this->assertEquals(1, $DB->count_records(userset::TABLE));
    }

    /**
     * Validate that a userset can be updated, setting all available fields
     */
    public function test_update_elis_userset_import_with_all_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Set up a userset.
        $userset = new userset(array('name' => 'testusersetname', 'display' => 'testusersetdisplay'));
        $userset->save();

        // Run the import.
        $data = array('action' => 'update', 'display' => 'updatedusersetdisplay');
        $this->run_core_userset_import($data, true);

        // Validation.
        unset($data['action']);
        $data['name'] = 'testusersetname';
        $data['parent'] = 0;
        $data['depth'] = 1;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * @dataProvider field_provider
     * Validate that a userset can be ]deleted with a minimal set of fields specified
     */
    public function test_delete_elis_userset_import_with_minimal_fields($data, $expected) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Set up a userset.
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        // Run the import.
        $data = array('action' => 'delete', 'recursive' => $data);
        $this->run_core_userset_import($data, true);

        // Validation.
        $count = $DB->count_records(userset::TABLE);
        $this->assertEquals(0, $count);
    }

    // Data provider for mapping yes to 1 and no to 0.
    public function field_provider() {
        return array(
                array('0', '0'),
                array('1', '1'),
                array('yes', '1'),
                array('no', '0')
        );
    }

    /**
     * Validate that deleting a userset deletes all appropriate associations
     */
    public function test_delete_elis_userset_deletes_associations() {
        global $CFG, $DB;
        // Entities.
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elis::lib('data/customfield.class.php'));

        // Associations.
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::file('enrol/userset/moodleprofile/userset_profile.class.php'));

        // For context level access.
        require_once(elispm::file('accesslib.php'));

        $origfieldcount = $DB->count_records(field::TABLE);

        // Set up user set.
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        // Set up other entities and associations.

        // Cluster enrolment.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();
        $clusterassignment = new clusterassignment(array('clusterid' => $userset->id, 'userid' => $user->id));
        $clusterassignment->save();

        // Cluster-curriculum assignment.
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();
        $clustercurriculum = new clustercurriculum(array('clusterid' => $userset->id, 'curriculumid' => $curriculum->id));
        $clustercurriculum->save();

        // Cluster-track assignment.
        $track = new track(array('curid' => $curriculum->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();
        $clustertrack = new clustertrack(array('clusterid' => $userset->id, 'trackid' => $track->id));
        $clustertrack->save();

        // Custom field.
        $field = new field(array('name' => 'testfieldname', 'categoryid' => 9999));
        $field->save();
        $context = \local_elisprogram\context\userset::instance($userset->id);
        $data = new field_data_int(array(
            'contextid' => $context->id,
            'fieldid' => $field->id,
            'data' => 1
        ));
        $data->save();

        // Cluster profile criteria.
        $clusterprofile = new userset_profile(array(
            'clusterid' => $userset->id,
            'fieldid' => $field->id,
            'value' => 0
        ));
        $clusterprofile->save();

        // Validate setup.
        $this->assertEquals(1, $DB->count_records(userset::TABLE));
        $this->assertEquals(1, $DB->count_records(user::TABLE));
        $this->assertEquals(1, $DB->count_records(clusterassignment::TABLE));
        $this->assertEquals(1, $DB->count_records(curriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(clustercurriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(track::TABLE));
        $this->assertEquals(1, $DB->count_records(clustertrack::TABLE));
        $this->assertEquals((1 + $origfieldcount), $DB->count_records(field::TABLE));
        $this->assertEquals(1, $DB->count_records(field_data_int::TABLE));
        $this->assertEquals(1, $DB->count_records(userset_profile::TABLE));

        // Run the import.
        $data = array('action' => 'delete');
        $this->run_core_userset_import($data, true);

        // Validation.
        $this->assertEquals(0, $DB->count_records(userset::TABLE));
        $this->assertEquals(1, $DB->count_records(user::TABLE));
        $this->assertEquals(0, $DB->count_records(clusterassignment::TABLE));
        $this->assertEquals(1, $DB->count_records(curriculum::TABLE));
        $this->assertEquals(0, $DB->count_records(clustercurriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(track::TABLE));
        $this->assertEquals(0, $DB->count_records(clustertrack::TABLE));
        $this->assertEquals((1 + $origfieldcount), $DB->count_records(field::TABLE));
        $this->assertEquals(0, $DB->count_records(field_data_int::TABLE));
        $this->assertEquals(0, $DB->count_records(userset_profile::TABLE));
    }

    /**
     * Data provider for the parent field
     *
     * @return array Mapping of parent values to expected results in the database
     */
    public function parent_provider() {
        return array(
                array('', 0, 1),
                array('top', 0, 1),
                array('testparentusersetname', 1, 2
        ));
    }

    /**
     * Validate the various behaviours of the parent field during userset creation
     *
     * @param string $inputvalue The parent value specified
     * @param int $dbvalue The expected parent value stored in the database
     * @param int $depth The expected userset depth
     * @dataProvider parent_provider
     */
    public function test_create_elis_userset_respects_parent_field($inputvalue, $dbvalue, $depth) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        // Set up a parent userset.
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        // Run the import.
        $data = array('parent' => $inputvalue);
        $this->run_core_userset_import($data, true);

        // Validation.
        $data['name'] = 'testusersetname';
        $data['parent'] = $dbvalue;
        $data['depth'] = $depth;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Helper function to get the core fields for a sample userset
     *
     * @return array The userset data
     */
    private function get_core_userset_data() {
        $data = array(
            'action' => 'create',
            'context' => 'cluster',
            'name' => 'testusersetname'
        );
        return $data;
    }

    /**
     * Helper function that runs the userset import for a sample userset
     *
     * @param array $extradata Extra fields to set for the new userset
     * @param boolean $usedefaultdata If true, use the default userset data,
     *                                  along with any data specifically provided
     */
    private function run_core_userset_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('dhimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_userset_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1elis_importprovider_mockuserset($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }
}