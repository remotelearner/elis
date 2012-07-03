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
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

/**
 * Class for validating that ELIS / PM user and entity actions support setting of
 * multi-value custom field data
 */
class elis_enrolment_multivalue_custom_fields_import_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));

        return array(curriculum::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     field_category::TABLE => 'elis_core',
                     field_category_contextlevel::TABLE => 'elis_core',
                     field_data_int::TABLE => 'elis_core');
    }

    /**
     * Create the test custom profile field, category and owner
     *
     * @param string $contextlevelname The name of the custom context level to create the field at
     * @param string $data_type The string identifier of the data type to use
     * @param string $ui_type The string identifier of the UI / control type to use
     * @param boolean $multivalued Set to true to make field multivalued, otherwise false
     * @param mixed $option Array of menu options, or NULL for none
     * @return int The id of the created field
     */
    private function create_test_field($contextlevelname, $data_type, $ui_type, $multivalued, $options) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        //category
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        //category contextlevel
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $field_category_contextlevel = new field_category_contextlevel(array('categoryid' => $field_category->id,
                                                                             'contextlevel' => $contextlevel));
        $field_category_contextlevel->save();

        //field
        $field = new field(array('shortname' => 'testfieldshortname',
                                 'name' => 'testfieldname',
                                 'categoryid' => $field_category->id,
                                 'datatype' => $data_type));
        if ($multivalued) {
            //enable multivalued ability
            $field->multivalued = true;
        }

        $field->save();

        //field owner
        $owner_data = array('control' => $ui_type);

        if ($options !== NULL) {
            //set options
            $owner_data['options'] = implode("\n", $options);
        }

        field_owner::ensure_field_owner_exists($field, 'manual', $owner_data);

        return $field->id;
    }

    /**
     * Create a parent entity needed to solved an entity dependency
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none
     * @return mixed THe id of the parent record, or NULL if not created
     */
    private function create_parent_entity($parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');

        if ($parententitytype !== NULL && $parentrecord !== NULL && $parentreffield !== NULL) {
            require_once(elispm::lib('data/'.$parenentitytype.'.class.php'));

            $parent = new $parententitytype($parentrecord);
            $parent->save;
            return $parent->id;
        }

        return NULL;
    }

    /**
     * Validate the state of custom field data values, including the number of values and
     * their specific entries 
     * @param string $contextlevelname The string representing a custom context level
     * @param string $entity_table The name of the PM entity table
     * @param string $customfield_table The name of the custom field data table
     * @param int $fieldid The id of the appropriate field
     * @param array $values Specific data values to check for
     */
    private function assert_field_values($contextlevelname, $entity_table, $customfield_table, $fieldid, $values) {
        global $DB;

        //validate count
        $this->assertEquals(count($values), $DB->count_records(field_data_int::TABLE));

        //obtain instance
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $contextlevel = context_elis_helper::get_class_for_level($contextlevel);
        $instanceid = $DB->get_field($entity_table, array('id' => 1));
        $instance = $contextlevel::instance($instanceid);

        //validate specific values
        $values = array('1', '2', '3');
        foreach ($values as $value) {
            $this->assertTrue($DB->record_exists($customfield_table, array('fieldid' => $fieldid,
                                                                          'contextid' => $instance->id,
                                                                          'data' => $value)));
        }
    }

    /**
     * Data provider that specifies information needed for various context levels
     *
     * @return array The data, as expected by the testing methods
     */
    function entity_type_provider() {
        $curriculum_data = array('context' => 'curriculum',
                                 'idnumber' => 'testcurriculumidnumber',
                                 'name' => 'testcurriculumname');
        $track_data = array('context' => 'class',
                            'idnumber' => 'testtrackidnumber');
        $course_data = array('context' => 'course',
                             'idnumber' => 'testcourseidnumber');
        $class_data = array('context' => 'class',
                            'idnumber' => 'testclassidnumber');
        $cluster_data = array('context' => 'cluster',
                              'name' => 'testclustername');
        $user_data = array('username' => 'testuserusername',
                           'firstname' => 'testuserfirstname',
                           'lastname' => 'testuserlastname',
                           'email' => 'test@useremail.com',
                           'city' => 'testusercity',
                           'country' => 'CA');
        return array(array('curriculum', $curriculum_data, 'curriculum', 'course'),
                     array('track', $track_data, 'track', 'course', 'curriculum', $curriculum_data, 'curid'),
                     array('course', $course_data, 'course', 'course'),
                     array('pmclass', $class_data, 'class', 'course', 'course', $course_data, 'courseid'),
                     array('userset', $cluster_data, 'cluster', 'course'),
                     array('user', $user_data, 'user', 'user'));
    }

    /**
     * Validate multi-valued custom field data creation during entity creation
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none 
     */
    public function test_create_multivalue_field_data_on_entity_create($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        //set up the custom field, category, context association, and owner
        $fieldid = $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'));

        $record['action'] = 'create';
        $record['testfieldshortname'] = '1/2/3';

        //create parent entity if needed
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        //run the entity create action
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        //validation
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_int::TABLE, $fieldid, array('1', '2', '3'));
    }

    /**
     * Validate multi-valued custom field data creation during entity update
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none 
     */
    public function test_create_multivalue_field_data_on_entity_update($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        //set up the custom field, category, context association, and owner
        $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'));

        //create parent entity if needed
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        //persist the entity
        $entity = new $entitytype($record);
        $entity->save();

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        //run the entity update action
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        //validation
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_int::TABLE, $fieldid, array('1', '2', '3'));
    }

    /**
     * Validate multi-valued custom field data update during entity update
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none 
     */
    public function test_update_multivalue_field_data_on_entity_update($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        //set up the custom field, category, context association, and owner
        $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'));

        //create parent entity if needed
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        //persist the entity
        $entity = new $entitytype($record);
        $entity->save();

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        //run the entity update action
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_int::TABLE, $fieldid, array('1', '2', '3'));
    }

    /**
     * Validate multi-valued custom field data update removes previous selection
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none 
     */
    public function test_multivalue_field_data_update_overwrites_previous_selection($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                                    $parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        //set up the custom field, category, context association, and owner
        $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'));

        //create parent entity if needed
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        //persist the entity
        $record['testfieldshortname'] = '4';
        $entity = new $entitytype($record);
        $entity->save();

        $contextlevel = context_elis_helper::get_class_for_level($contextlevel);
        $instanceid = $DB->get_field($entitytype::TABLE, array('id' => 1));
        $instance = $contextlevel::instance($instanceid);

        //validate setup
        $this->assertEquals(1, $DB->count_records(field_data_int::TABLE));
        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $field->id,
                                                                          'contextid' => $instance->id,
                                                                          'data' => '4')));

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        //run the entity update action
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        //validation
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_int::TABLE, $fieldid, array('1', '2', '3'));
    }

    /**
     * Validate multi-valued custom field data respects whether the custom field is
     * flagged as being multi-valued
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or NULL if none
     * @param mixed $parentrecord The parent data record, or NULL if none
     * @param mixed $parentreffield The field used to refer to the parent element, or NULL if none 
     */
    public function test_multivalue_functionality_respects_custom_field_flag($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                             $parententitytype = NULL, $parentrecord = NULL, $parentreffield = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        //set up the custom field, category, context association, and owner
        $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1/2/3', '4'));

        $record['action'] = 'create';
        $record['testfieldshortname'] = '1/2/3';

        //create parent entity if needed
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        //run the entity create action
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        //validation
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_int::TABLE, $fieldid, array('1/2/3'));
    }

    /**
     * Data provider for testing various custom field UI / display types
     *
     * @return array The data, as expected by the testing method
     */
    function ui_type_provider() {
        return array(array('checkbox'),
                     array('text'),
                     array('textarea'),
                     array('datetime'),
                     array('password'));
    }

    /**
     * Validate that multivalue functionality is only used for "menu of choices" UI types
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     */
    public function test_multivalue_functionality_only_used_for_menu_of_choices($ui_type) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //set up the custom field, category, context association, and owner
        $this->create_test_field('user', 'int', $ui_type, true, NULL);

        //run the entity create action
        $record = array('action' => 'create',
                        'username' => 'testuserusername',
                        'password' => 'testuserpassword',
                        'firstname' => 'testuserfirstname',
                        'lastname' => 'testuserlastname',
                        'email' => 'test@useremail.com',
                        'city' => 'testusercity',
                        'country' => 'CA',
                        'testfieldshortname' => 'sometext\\/moretext');
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('user', (object)$record, 'bogus');

        //validation
        $this->assert_field_values($contextlevelname, user::TABLE, field_data_int::TABLE, $fieldid, array('1/2/3'));
    }

    /**
     * Data provider for testing various custom field data types
     *
     * @return array The data, as expected by the testing method
     */
    function data_type_provider() {
        return array(array('text', 'a/b/c', array('a', 'b', 'c')),
                     array('char', 'a/b/c', array('a', 'b', 'c')),
                     array('int', '1/2/3', array('1', '2', '3')),
                     array('num', '1.5/2.5/3.5', array('1.5', '2.5', '3.5')),
                     array('bool', '0/1', array('0', '1')),
                     array('datetime', 'Jan/01/2012/Feb/02/2012', array(mktime(0, 0, 0, 1, 1, 2012), mktime(0, 0, 0, 2, 2, 2012))),
                     //some specific cases with "special characters"
                     array('text', 'a/b/c/\\//\\\\', array('a', 'b', 'c', '/', '\\')),
                     array('char', 'a/b/c/\\//\\\\', array('a', 'b', 'c', '/', '\\'))
                     );
    }

    /**
     * Validate that multivalue functionality is suppored for all data types when using the
     * "menu of choices" UI / input type
     *
     * @dataProvider data_type_provider
     * @param string $ui_type The string value representing a data type
     * @param string $data The multi-valued custom field value to use as input
     * @param array $expected The values to expect in the databaes
     */
    public function test_multivalue_field_data_supports_all_data_types_for_menu_of_choices($data_type, $data, $expected) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //set up the custom field, category, context association, and owner
        //use "expected" as list of available options
        $this->create_test_field('user', $data_type, 'menu', true, $expected);

        //run the entity create action
        $record = array('action' => 'create',
                        'username' => 'testuserusername',
                        'password' => 'testuserpassword',
                        'firstname' => 'testuserfirstname',
                        'lastname' => 'testuserlastname',
                        'email' => 'test@useremail.com',
                        'city' => 'testusercity',
                        'country' => 'CA',
                        'testfieldshortname' => 'sometext\\/moretext');
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        //validation
        $this->assert_field_values('user', $entitytype::TABLE, field_data_int::TABLE, $fieldid, $expected);
    }
}