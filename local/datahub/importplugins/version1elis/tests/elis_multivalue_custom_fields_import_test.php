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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that ELIS / PM user and entity actions support setting of multi-value custom field data.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_elis_multivalue_custom_fields_import_testcase extends rlip_elis_test {

    /**
     * Create the test custom profile field, category and owner
     *
     * @param string $contextlevelname The name of the custom context level to create the field at
     * @param string $datatype The string identifier of the data type to use
     * @param string $uitype The string identifier of the UI / control type to use
     * @param boolean $multivalued Set to true to make field multivalued, otherwise false
     * @param mixed $options Array of menu options, or null for none
     * @param int $maxlength The maximum data length, or null for none
     * @return int The id of the created field
     */
    private function create_test_field($contextlevelname, $datatype, $uitype, $multivalued, $options, $maxlength, $inctime) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        // Category.
        $fieldcategory = new field_category(array('name' => 'testcategoryname'));
        $fieldcategory->save();

        // Category contextlevel.
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $fieldcategorycontextlevel = new field_category_contextlevel(array(
            'categoryid' => $fieldcategory->id,
            'contextlevel' => $contextlevel
        ));
        $fieldcategorycontextlevel->save();

        // Field.
        $field = new field(array(
            'shortname' => 'testfieldshortname',
            'name' => 'testfieldname',
            'categoryid' => $fieldcategory->id,
            'datatype' => $datatype
        ));
        if ($multivalued) {
            // Enable multivalued ability.
            $field->multivalued = true;
        }

        $field->save();

        // Field contextlevel.
        $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => $contextlevel));
        $fieldcontextlevel->save();

        // Field owner.
        $ownerdata = array('control' => $uitype);

        if ($options !== null) {
            // Set options.
            $ownerdata['options'] = implode("\n", $options);
        }

        if ($maxlength !== null) {
            // Set max length.
            $ownerdata['maxlength'] = $maxlength;
        }

        if ($inctime !== null) {
            $ownerdata['inctime'] = $inctime;
        }

        field_owner::ensure_field_owner_exists($field, 'manual', $ownerdata);

        return $field->id;
    }

    /**
     * Create a parent entity needed to solved an entity dependency
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @return mixed THe id of the parent record, or null if not created
     */
    private function create_parent_entity($parententitytype = null, $parentrecord = null, $parentreffield = null) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');

        if ($parententitytype !== null && $parentrecord !== null && $parentreffield !== null) {
            require_once(elispm::lib('data/'.$parententitytype.'.class.php'));

            $parent = new $parententitytype($parentrecord);
            $parent->save();
            return $parent->id;
        }

        return null;
    }

    /**
     * Validate the state of custom field data values, including the number of values and
     * their specific entries
     * @param string $contextlevelname The string representing a custom context level
     * @param string $entitytable The name of the PM entity table
     * @param string $customfieldtable The name of the custom field data table
     * @param int $fieldid The id of the appropriate field
     * @param array $values Specific data values to check for
     */
    private function assert_field_values($contextlevelname, $entitytable, $customfieldtable, $fieldid, $values) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        // Validate count.
        $this->assertEquals(count($values), $DB->count_records($customfieldtable));

        // Obtain instance.
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $contextlevel = context_elis_helper::get_class_for_level($contextlevel);
        $instance = $contextlevel::instance(1);

        // Validate specific values.
        foreach ($values as $value) {
            if ($customfieldtable == field_data_text::TABLE) {
                $select = "fieldid = ? AND contextid = ? AND {$DB->sql_compare_text('data', 255)} = ?";
                $params = array($fieldid, $instance->id, $value);
                $this->assertTrue($DB->record_exists_select($customfieldtable, $select, $params));
            } else {
                $this->assertTrue($DB->record_exists($customfieldtable, array(
                    'fieldid' => $fieldid,
                    'contextid' => $instance->id,
                    'data' => $value
                )));
            }
        }
    }

    /**
     * Data provider that specifies information needed for various context levels
     *
     * @return array The data, as expected by the testing methods
     */
    public function entity_type_provider() {
        $curriculumdata = array(
            'context' => 'curriculum',
            'idnumber' => 'testcurriculumidnumber',
            'name' => 'testcurriculumname'
        );
        $trackdata = array(
            'context' => 'track',
            'idnumber' => 'testtrackidnumber',
            'assignment' => 'testcurriculumidnumber',
            'name' => 'testtrackname'
        );
        $coursedata = array(
            'context' => 'course',
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        );
        $classdata = array(
            'context' => 'class',
            'idnumber' => 'testclassidnumber',
            'assignment' => 'testcourseidnumber'
        );
        $clusterdata = array('context' => 'cluster', 'name' => 'testclustername');
        $userdata = array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'city' => 'testusercity',
            'country' => 'CA',
            'birthday' => '',
            'birthmonth' => '',
            'birthyear' => ''
        );
        return array(
                array('curriculum', $curriculumdata, 'curriculum', 'course'),
                array('track', $trackdata, 'track', 'course', 'curriculum', $curriculumdata, 'curid', 'assignment'),
                array('course', $coursedata, 'course', 'course'),
                array('pmclass', $classdata, 'class', 'course', 'course', $coursedata, 'courseid', 'assignment'),
                array('userset', $clusterdata, 'cluster', 'course'),
                array('user', $userdata, 'user', 'user')
        );
    }

    /**
     * Validate multi-valued custom field data creation during entity creation
     *
     * @dataProvider entity_type_provider
     * @param string $entitytype The type of entity we are running the import for
     * @param array $record The inport record to process
     * @param string $contextlevelname The name used to refer to the appropriate context level
     * @param string $fileidentifier The entity type represented by the input file
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @param mixed $ipparentreffield The field used to refer to the parent element in IP, or null if none
     */
    public function test_create_multivalue_field_data_on_entity_create($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = null, $parentrecord = null,
                                                                       $parentreffield = null, $ipparentreffield = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'), null, null);

        $record['action'] = 'create';
        $record['testfieldshortname'] = '1/2/3';

        // Create parent entity if needed.
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        $entity = new $entitytype;
        $entity->reset_custom_field_list();

        // Run the entity create action.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        // Validation.
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
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @param mixed $ipparentreffield The field used to refer to the parent element in IP, or null if none
     */
    public function test_create_multivalue_field_data_on_entity_update($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = null, $parentrecord = null,
                                                                       $parentreffield = null, $ipparentreffield = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'), null, null);

        // Create parent entity if needed.
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        // Persist the entity.
        $entity = new $entitytype($record);
        $entity->save();

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        // Run the entity update action.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        // Validation.
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
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @param mixed $ipparentreffield The field used to refer to the parent element in IP, or null if none
     */
    public function test_update_multivalue_field_data_on_entity_update($entitytype, $record, $contextlevelname, $fileidentifier,
                                                                       $parententitytype = null, $parentrecord = null,
                                                                       $parentreffield = null, $ipparentreffield = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'), null, null);

        // Create parent entity if needed.
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        // Persist the entity.
        $entity = new $entitytype($record);
        $entity->save();

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        // Run the entity update action.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        // Validation.
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
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @param mixed $ipparentreffield The field used to refer to the parent element in IP, or null if none
     */
    public function test_multivalue_field_data_update_overwrites_previous_selection($entitytype, $record, $contextlevelname,
                                                                                    $fileidentifier, $parententitytype = null,
                                                                                    $parentrecord = null, $parentreffield = null,
                                                                                    $ipparentreffield = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field($contextlevelname, 'int', 'menu', true, array('1', '2', '3', '4'), null, null);

        // Create parent entity if needed.
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        // Persist the entity.
        $entity = new $entitytype();
        $entity->set_from_data((object)array_merge($record, array('field_testfieldshortname' => array('4'))));
        $entity->save();

        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $contextlevel = context_elis_helper::get_class_for_level($contextlevel);
        $instance = $contextlevel::instance($entity->id);

        // Validate setup.
        $this->assertEquals(1, $DB->count_records(field_data_int::TABLE));
        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array(
            'fieldid' => $fieldid,
            'contextid' => $instance->id,
            'data' => '4'
        )));

        $record['action'] = 'update';
        $record['testfieldshortname'] = '1/2/3';

        // Run the entity update action.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        // Validation.
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
     * @param mixed $parententitytype The parent entity type, or null if none
     * @param mixed $parentrecord The parent data record, or null if none
     * @param mixed $parentreffield The field used to refer to the parent element, or null if none
     * @param mixed $ipparentreffield The field used to refer to the parent element in IP, or null if none
     */
    public function test_multivalue_functionality_respects_custom_field_flag($entitytype, $record, $contextlevelname,
                                                                             $fileidentifier, $parententitytype = null,
                                                                             $parentrecord = null, $parentreffield = null,
                                                                             $ipparentreffield = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field($contextlevelname, 'char', 'menu', false, array('1/2/3\\\\\\/', '4'), null, null);

        $record['action'] = 'create';
        $record['testfieldshortname'] = '1/2/3\\\\\\/';

        // Create parent entity if needed.
        if ($parentid = $this->create_parent_entity($parententitytype, $parentrecord, $parentreffield)) {
            $record[$parentreffield] = $parentid;
        }

        // Reset the field list.
        $temp = new $entitytype();
        $temp->reset_custom_field_list();

        // Run the entity create action.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record($fileidentifier, (object)$record, 'bogus');

        // Validation.
        $this->assert_field_values($contextlevelname, $entitytype::TABLE, field_data_char::TABLE, $fieldid, array('1/2/3\\\\\\/'));
    }

    /**
     * Data provider for testing various custom field UI / display types
     *
     * @return array The data, as expected by the testing method
     */
    public function ui_type_provider() {
        return array(
                array('checkbox', '0', array('0'), null, 0),
                array('checkbox', 'no', array('0'), null, 0),
                array('checkbox', '1', array('1'), null, 0),
                array('checkbox', 'yes', array('1'), null, 0),
                array('text', 'sometext/moretext', array('sometext/moretext'), 100, 0),
                array('textarea', 'sometext/moretext', array('sometext/moretext'), null, 0),
                array('datetime', 'Jan/02/2012', array(array(0, 0, 0, 1, 2, 2012)), null, 0),
                array('datetime', 'Jan/02/2012:05:30', array(array(5, 30, 0, 1, 2, 2012)), null, 1),
                array('password', 'sometext/moretext', array('sometext/moretext'), 100, 0)
        );
    }

    /**
     * Validate that multivalue functionality is only used for "menu of choices" UI types
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data The value provided for that field
     * @param string $expected The expected stored value
     * @param int $maxlength The max length for data input
     */
    public function test_multivalue_functionality_only_used_for_menu_of_choices($uitype, $data, $expected, $maxlength, $inctime) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Set up the custom field, category, context association, and owner.
        $fieldid = $this->create_test_field('user', 'char', $uitype, true, null, $maxlength, $inctime);

        if ($uitype === 'datetime') {
            $expected[0] = rlip_timestamp($expected[0][0], $expected[0][1], $expected[0][2], $expected[0][3], $expected[0][4],
                    $expected[0][5]);
        }

        // Reset the field list.
        $temp = new user;
        $temp->reset_custom_field_list();

        // Run the entity create action.
        $record = array(
            'action' => 'create',
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'password' => 'testuserpassword',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'city' => 'testusercity',
            'country' => 'CA',
            'testfieldshortname' => $data
        );
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        // Validation.
        $this->assert_field_values('user', user::TABLE, field_data_char::TABLE, $fieldid, $expected);
    }

    /**
     * Data provider for testing various custom field data types
     *
     * @return array The data, as expected by the testing method
     */
    public function data_type_provider() {
        // Note: not worrying about date-time here because other unit tests will fail if.
        // Splitting on a slash is implemented.
        return array(
                array('text', 'a/b/c', array('a', 'b', 'c')),
                array('char', 'a/b/c', array('a', 'b', 'c')),
                array('int', '1/2/3', array('1', '2', '3')),
                array('num', '1.5/2.5/3.5', array('1.5', '2.5', '3.5')),
                array('bool', '0/1', array('0', '1')),
                // Some specific cases with "special characters".
                array('text', 'a/b/c/\\//\\\\', array('a', 'b', 'c', '/', '\\')),
                array('char', 'a/b/c/\\//\\\\', array('a', 'b', 'c', '/', '\\'))
        );
    }

    /**
     * Validate that multivalue functionality is suppored for all data types when using the
     * "menu of choices" UI / input type
     *
     * @dataProvider data_type_provider
     * @param string $uitype The string value representing a data type
     * @param string $data The multi-valued custom field value to use as input
     * @param array $expected The values to expect in the databaes
     */
    public function test_multivalue_field_data_supports_all_data_types_for_menu_of_choices($datatype, $data, $expected) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Set up the custom field, category, context association, and owner.
        // Use "expected" as list of available options.
        $fieldid = $this->create_test_field('user', $datatype, 'menu', true, $expected, null, null);

        // Reset the field list.
        $temp = new user();
        $temp->reset_custom_field_list();

        // Run the entity create action.
        $record = array(
            'action' => 'create',
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'password' => 'testuserpassword',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'city' => 'testusercity',
            'country' => 'CA',
            'testfieldshortname' => $data
        );
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        // Validation.
        $instance = new field(array('datatype' => $datatype));
        $realtype = $instance->data_type();
        $realdataclass = "field_data_{$realtype}";
        $this->assert_field_values('user', user::TABLE, $realdataclass::TABLE, $fieldid, $expected);
    }

    /**
     * Validate that dates are not supported as multivalue entries
     */
    public function test_multivalue_dates_not_supported() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        // Set up a date/time custom field.
        $fieldid = $this->create_test_field('user', 'datetime', 'datetime', true, null, null, null);

        // Run the entity create action.
        $record = array(
            'action' => 'create',
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'password' => 'testuserpassword',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'city' => 'testusercity',
            'country' => 'CA',
            'testfieldshortname' => 'Jan/01/Feb/02/2012'
        );
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        // Validation.
        $this->assertEquals(0, $DB->count_records(user::TABLE));
    }
}
