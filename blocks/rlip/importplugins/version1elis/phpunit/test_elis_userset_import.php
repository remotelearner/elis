<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Unit test for validating basic userset actions
 */
class elis_userset_import_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));

        $overlaytables = array(
            clusterassignment::TABLE => 'elis_program',
            clustercurriculum::TABLE => 'elis_program',
            clustertrack::TABLE => 'elis_program',
            curriculum::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_data_num::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            'grading_areas'  => 'moodle',
            track::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            userset_profile::TABLE => 'elis_program',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            'user' => 'moodle'
        );
        if (file_exists($CFG->dirroot.'/repository/elis_files/version.php')) {
            $overlaytables += array('elis_files_userset_store' => 'repository_elis_files');
        }
        return $overlaytables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'block_instances' => 'moodle',
                     'block_positions' => 'moodle',
                     'cache_flags' => 'moodle',
                     'comments' => 'moodle',
                     'context' => 'moodle',
                     'files' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'rating' => 'moodle',
                     'role_assignments' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'user_preferences' => 'moodle');
    }

    /**
     * Validate that a userset can be created with a minimal set of fields specified
     */
    function test_create_elis_userset_import_with_minimal_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //run the import
        $this->run_core_userset_import(array(), true);

        //validation
        $data = array('name' => 'testusersetname',
                      'display' => '',
                      'parent' => 0,
                      'depth' => 1);
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Validate that a userset can be created, setting all available fields
     */
    function test_create_elis_userset_import_with_all_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a parent userset
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        //run the import
        $data = array('display' => 'testusersetdisplay',
                      'parent' => 'testparentusersetname');
        $this->run_core_userset_import($data, true);

        //validation
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
    function minimal_update_field_provider() {
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
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a userset
        $userset = new userset(array('name' => 'testusersetname',
                                     'display' => 'testusersetdisplay'));
        $userset->save();

        //run the import
        $data = array('action' => 'update',
                      $fieldname => $value);
        $this->run_core_userset_import($data, true);

        //validation
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
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a userset
        $userset = new userset(array('name' => 'testusersetname',
                                     'display' => 'testusersetdisplay'));
        $userset->save();

        //run the import
        $data = array('action' => 'update',
                      'display' => 'updatedusersetdisplay');
        $this->run_core_userset_import($data, true);

        //validation
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
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a userset
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //run the import
        $data = array('action' => 'delete', 'recursive' => $data);
        $this->run_core_userset_import($data, true);

        //validation
        $count = $DB->count_records(userset::TABLE);
        $this->assertEquals(0, $count);
    }

    // Data provider for mapping yes to 1 and no to 0
    function field_provider() {
        return array(array('0', '0'),
                     array('1', '1'),
                     array('yes', '1'),
                     array('no', '0'));
    }

    /**
     * Validate that deleting a userset deletes all appropriate associations
     */
    public function test_delete_elis_userset_deletes_associations() {
        global $CFG, $DB;
        //entities
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elis::lib('data/customfield.class.php'));

        //associations
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));

        //for context level access
        require_once(elispm::file('accesslib.php'));

        //set up user set
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //set up other entities and associations

        //cluster enrolment
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();
        $clusterassignment = new clusterassignment(array('clusterid' => $userset->id,
                                                         'userid' => $user->id));
        $clusterassignment->save();

        //cluster-curriculum assignment
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();
        $clustercurriculum = new clustercurriculum(array('clusterid' => $userset->id,
                                                         'curriculumid' => $curriculum->id));
        $clustercurriculum->save();

        //cluster-track assignment
        $track = new track(array('curid' => $curriculum->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();
        $clustertrack = new clustertrack(array('clusterid' => $userset->id,
                                               'trackid' => $track->id));
        $clustertrack->save();

        //custom field
        $field = new field(array('name' => 'testfieldname',
                                 'categoryid' => 9999));
        $field->save();
        $context = context_elis_userset::instance($userset->id);
        $data = new field_data_int(array('contextid' => $context->id,
                                         'fieldid' => $field->id,
                                         'data' => 1));
        $data->save();

        //cluster profile criteria
        $clusterprofile = new userset_profile(array('clusterid' => $userset->id,
                                                    'fieldid' => $field->id,
                                                    'value' => 0));
        $clusterprofile->save();

        //validate setup
        $this->assertEquals(1, $DB->count_records(userset::TABLE));
        $this->assertEquals(1, $DB->count_records(user::TABLE));
        $this->assertEquals(1, $DB->count_records(clusterassignment::TABLE));
        $this->assertEquals(1, $DB->count_records(curriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(clustercurriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(track::TABLE));
        $this->assertEquals(1, $DB->count_records(clustertrack::TABLE));
        $this->assertEquals(1, $DB->count_records(field::TABLE));
        $this->assertEquals(1, $DB->count_records(field_data_int::TABLE));
        $this->assertEquals(1, $DB->count_records(userset_profile::TABLE));

        //run the import
        $data = array('action' => 'delete');
        $this->run_core_userset_import($data, true);

        //validation
        $this->assertEquals(0, $DB->count_records(userset::TABLE));
        $this->assertEquals(1, $DB->count_records(user::TABLE));
        $this->assertEquals(0, $DB->count_records(clusterassignment::TABLE));
        $this->assertEquals(1, $DB->count_records(curriculum::TABLE));
        $this->assertEquals(0, $DB->count_records(clustercurriculum::TABLE));
        $this->assertEquals(1, $DB->count_records(track::TABLE));
        $this->assertEquals(0, $DB->count_records(clustertrack::TABLE));
        $this->assertEquals(1, $DB->count_records(field::TABLE));
        $this->assertEquals(0, $DB->count_records(field_data_int::TABLE));
        $this->assertEquals(0, $DB->count_records(userset_profile::TABLE));
    }

    /**
     * Data provider for the parent field
     *
     *  @return array Mapping of parent values to expected results in the database
     */
    function parent_provider() {
        return array(array('', 0, 1),
                     array('top', 0, 1),
                     array('testparentusersetname', 1, 2));
    }

    /**
     * Validate the various behaviours of the parent field during userset creation
     *
     * @param string $input_value The parent value specified
     * @param int $db_value The expected parent value stored in the database
     * @param int $depth The expected userset depth
     * @dataProvider parent_provider
     */
    function test_create_elis_userset_respects_parent_field($input_value, $db_value, $depth) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a parent userset
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        //run the import
        $data = array('parent' => $input_value);
        $this->run_core_userset_import($data, true);

        //validation
        $data['name'] = 'testusersetname';
        $data['parent'] = $db_value;
        $data['depth'] = $depth;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Helper function to get the core fields for a sample userset
     *
     * @return array The userset data
     */
    private function get_core_userset_data() {
        $data = array('action' => 'create',
                      'context' => 'cluster',
                      'name' => 'testusersetname');
        return $data;
    }

    /**
     * Helper function that runs the userset import for a sample userset
     *
     * @param array $extradata Extra fields to set for the new userset
     * @param boolean $use_default_data If true, use the default userset data,
     *                                  along with any data specifically provided
     */
    private function run_core_userset_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_userset_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockuserset($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }
}

/**
 * Class that fetches import files for the userset import
 */
class rlip_importprovider_mockuserset extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}
