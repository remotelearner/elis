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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
$plugin_dir = get_plugin_directory('rlipimport', 'version1elis');
require_once($plugin_dir.'/version1elis.class.php');
require_once($plugin_dir.'/lib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Class for validating import configuration
 */
class version1elisImportConfigTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        return array(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
                     field::TABLE => 'elis_core',
                     field_category::TABLE => 'elis_core',
                     field_contextlevel::TABLE => 'elis_core',
                     'config_plugins' => 'moodle');
    }

    /**
     * Data provider for tab validation
     *
     * @return array Data, containing tab position and entity type
     */
    public function getTabsProvider() {
        return array(array(0, 'user'),
                     array(1, 'course'),
                     array(2, 'enrolment'));
    }

    /**
     * Create a custom field category
     *
     * @return int The database id of the new category
     */
    private function create_custom_field_category() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        $category = new field_category(array('name' => 'testcategoryname'));
        $category->save();

        return $category->id;
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @return int The id of the created profile field
     */
    private function create_profile_field($name, $datatype, $categoryid, $contextlevelname = 'user') {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $field = new field(array('categoryid' => $categoryid,
                                 'shortname' => $name,
                                 'name' => $name));
        $field->save();

        //field contextlevel
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => $contextlevel));

        $field_contextlevel->save();

        return $field->id;
    }

    /**
     * Validate that the method to get tabs works correctly
     *
     * @param $index Position in the tabs array to use
     * @param $entitytype The type of entity that tab represents
     *
     * @dataProvider getTabsProvider
     */
    public function testGetTabsReturnsValidObjects($index, $entitytype) {
        global $CFG;

        //get collection of tabs
        $baseurl = 'base';
        $tabs = rlipimport_version1elis_get_tabs($baseurl);
        $this->assertEquals(count($tabs), 3);

        //the string displayed on the tab
        $displaystring = get_string($entitytype.'tab', 'rlipimport_version1elis');

        //data validation
        $tab = $tabs[$index];
        $this->assertEquals($tab->link->out(), $baseurl.'?tab='.$entitytype);
        $this->assertEquals($tab->text, $displaystring);
        $this->assertEquals($tab->title, $displaystring);
    }

    /**
     * Data provider for validating which import fields are valid for all
     * three entity types
     */
    public function availableFieldsProvider() {
        //actual data
        $user_fields = array('entity', 'action', 'username', 'auth',
                             'password', 'firstname', 'lastname', 'email',
                             'maildigest', 'autosubscribe', 'trackforums',
                             'screenreader', 'city', 'country', 'timezone',
                             'theme', 'lang', 'description', 'idnumber',
                             'institution', 'department');
        $course_fields = array('entity', 'action','shortname', 'fullname',
                               'idnumber', 'summary', 'format', 'numsections',
                               'startdate', 'newsitems', 'showgrades', 'showreports',
                               'maxbytes', 'guest', 'password', 'visible',
                               'lang', 'category', 'link');
        $enrolment_fields =  array('entity', 'action', 'username', 'email',
                                   'idnumber', 'context', 'instance', 'role');

        //necessary data structure
        return array(array('user', $user_fields),
                     array('course', $course_fields),
                     array('enrolment', $enrolment_fields));
    }

    /**
     * Validate that available import fields are reported correctly
     *
     * @param $entitytype
     * @param $fields
     *
     * @dataProvider availableFieldsProvider
     */
    public function testGetAvailableFieldsReturnsValidData($entitytype, $fields) {
        //obtain available fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $fields = $plugin->get_available_fields($entitytype);

        //validation
        $this->assertEquals($fields, $fields);
    }

    /**
     * Validate that false is returned when obtaining available fields for an
     * invalid entity type
     */
    public function testGetAvailableFieldsReturnsFalseForInvalidEntityType() {
        //obtain data
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $fields = $plugin->get_available_fields('bogus');

        //data validation
        $this->assertEquals($fields, false);
    }

    /**
     * Data provider for test mapping data
     *
     * @return array Info specifying entity types and a valid field to test
     */
    public function getMappingProvider() {
        return array(array('user', 'username'),
                     array('course', 'idnumber'),
                     array('enrolment', 'user_username'));
    }

    /**
     * Validate that mapping retrieval works as needed
     *
     * @param $entitytype The type of entity
     * @param $field A field that is valid for the supplied entity type
     *
     * @dataProvider getMappingProvider
     */
    public function testGetMappingReturnsValidData($entitytype, $field) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //obtain the entire list of fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $available_fields = $plugin->get_available_fields($entitytype);
        //obtain mapping in default state
        $fields = rlipimport_version1elis_get_mapping($entitytype);
        //validate that the two match
        $this->assertEquals(array_keys($fields), $available_fields);
        $this->assertEquals(array_values($fields), $available_fields);

        //create a mapping record
        $mapping = new stdClass;
        $mapping->entitytype = $entitytype;
        $mapping->standardfieldname = $field;
        $mapping->customfieldname = 'custom'.$field;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);

        //data validation
        $fields = rlipimport_version1elis_get_mapping($entitytype);
        $this->assertEquals(array_keys($fields), $available_fields);
        $this->assertEquals($fields[$field], 'custom'.$field);
    }

    /**
     * Validate that profile fields are supported in the mapping system
     */
    public function testGetMappingIncludesProfileField() {
        //create a test profile field
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('text', 'text', $categoryid);

        //obtain the entire list of fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $available_fields = $plugin->get_available_fields('user');
        //obtain mapping in default state
        $fields = rlipimport_version1elis_get_mapping('user');

        //validation
        $shortname = 'text';
        $exists = isset($fields[$shortname]) && $fields[$shortname] == $shortname;
        $this->assertTrue($exists);
    }

    /**
     * Validate that false is returned when obtaining field mapping for an
     * invalid entity type
     */
    public function testGetMappingReturnsFalseForInvalidEntityType() {
        //obtain data
        $fields = rlipimport_version1elis_get_mapping('bogus');

        //data validation
        $this->assertEquals($fields, false);
    }

    /**
     * Validate that all fields are persisted when saving mapping info
     *
     * @param $entitytype The type of entity
     * @param $field A field that is valid for the supplied entity type
     *
     * @dataProvider getMappingProvider
     */
    public function testSaveMappingPersistsAllData($entitytype, $field) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //obtain available fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $options = $plugin->get_available_fields($entitytype);

        //set up mapping data with all fields as defaults
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option;
        }

        //create a nonstandard mapping value
        $data[$field] = 'custom'.$field;

        //persist
        rlipimport_version1elis_save_mapping($entitytype, $options, $data);

        //construct expected data
        $i = 1;
        $expected_data = array();
        foreach ($data as $key => $value) {
            $record = new stdClass;
            $record->id = $i;
            $record->entitytype = $entitytype;
            $record->standardfieldname = $key;
            $record->customfieldname = $value;
            $expected_data[$i] = $record;
            $i++;
        }

        //data validation
        $params = array('entitytype' => $entitytype);
        $this->assertEquals($DB->get_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $params, 'id'), $expected_data);
    }

    /**
     * Validate that saving field mappings updates existing records
     */
    public function testSaveMappingUpdatesExistingRecords() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //obtain the available fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $options = $plugin->get_available_fields('user');

        //persist the default state
        rlipimport_version1elis_save_mapping('user', $options, array());

        //update all fields
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option.'updated';
        }

        //save the updated values
        rlipimport_version1elis_save_mapping('user', $options, $data);

        //data validation
        $select = $DB->sql_like('customfieldname', ':suffix');
        $params = array('suffix' => '%updated');
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select, $params);
        $this->assertEquals($count, count($data));
    }

    /**
     * Validate that clearing values during configuration save only happens for
     * the specified entity type
     */
    public function testSaveMappingDoesNotDeleteMappingsForOtherEntities() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //create a user mapping record
        $mapping = new stdClass;
        $mapping->entitytype = 'user';
        $mapping->standardfieldname = 'test';
        $mapping->customfieldname = 'customtest';
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);

        //obtain the available fields for course mappings
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $options = $plugin->get_available_fields('user');

        //store course mapping
        rlipimport_version1elis_save_mapping('course', $options, array());

        //data validation
        $exists = $DB->record_exists(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, array('entitytype' => 'user'));
        $this->assertTrue($exists);
    }

    /**
     * Validate that only valid field mappings can be saved
     */
    public function testSaveMappingDoesNotSaveInvalidFields() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //obtain available fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $options = $plugin->get_available_fields('user');

        //persist, with additonal bogus field
        rlipimport_version1elis_save_mapping('user', $options, array('bogus' => 'bogus'));

        //data validation
        $count = $DB->count_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE);
        $this->assertEquals($count, count($options));
    }

    /**
     * Validate restoring default field mappings
     */
    public function testRestoreDefaultMappingUpdatesRecords() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        //obtain available fields
        $plugin = new rlip_importplugin_version1elis(NULL, false);
        $options = $plugin->get_available_fields('user');

        //persist default field
        rlipimport_version1elis_save_mapping('user', $options, array());

        //setup validation
        $select = 'standardfieldname = customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));

        //update all mapping values
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option.'updated';
        }

        //persist updated values and validate
        rlipimport_version1elis_save_mapping('user', $options, $data);
        $select = 'standardfieldname != customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));

        //reset and validate state
        rlipimport_version1elis_reset_mappings('user');
        $select = 'standardfieldname = customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));
    }
}