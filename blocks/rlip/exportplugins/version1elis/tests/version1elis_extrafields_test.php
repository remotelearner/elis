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
 * @package    rlipexport_version1elis
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
require_once(dirname(__FILE__).'/other/rlip_fileplugin_export.class.php');
require_once(dirname(__FILE__).'/../lib.php');
require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
require_once(dirname(__FILE__).'/other/mock_obj.php');

if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
    require_once($CFG->dirroot.'/elis/program/lib/setup.php');
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/classmoodlecourse.class.php'));
    require_once(elispm::lib('data/course.class.php'));
    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/student.class.php'));
    require_once($CFG->dirroot.'/elis/program/accesslib.php');
}

/**
 * Test the version1elis export extrafield feature.
 * @group block_rlip
 * @group rlipexport_version1elis
 */
class version1elisextrafields_testcase extends rlip_elis_test {

    /**
     * Perform test setup and reset context caches.
     */
    protected function setUp() {
        parent::setUp();
        context_helper::reset_caches();
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data($multipleusers = false) {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/pmusers.csv',
            course::TABLE => $csvloc.'/pmcourses.csv',
            pmclass::TABLE => $csvloc.'/pmclasses.csv',
            student::TABLE => $csvloc.'/students.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Create a test ELIS user custom field.
     *
     * @param string $name The name of the custom field.
     * @param int $multivalued The multivalued value to set in the field definition
     * @param bool $setoptions Set to true to set the available menu of choices options
     * @param int $ctx The context level to use for the field
     * @param string $control The type of control to use for the field.
     *
     * @return int The id of the created field
     */
    protected function create_custom_field($name = 'name', $multivalued = 1, $setoptions = true, $ctx = CONTEXT_ELIS_USER,
                                           $control = 'menu') {
        global $CFG;

        // Field.
        $fieldparams = array(
            'name' => $name,
            'datatype' => 'char',
            'multivalued' => $multivalued
        );
        $field = new field($fieldparams);
        $category = new field_category(array('name' => 'testcategoryname'));
        $field = field::ensure_field_exists_for_context_level($field, $ctx, $category);

        // Owner.
        $owneroptions = array('control' => $control);
        if ($setoptions) {
            $owneroptions['options'] = "option1\r\noption2\r\noption3";
        }
        field_owner::ensure_field_owner_exists($field, 'manual', $owneroptions);

        // Default.
        $fielddataparams = array(
            'contextid' => null,
            'fieldid' => $field->id,
            'data' => 'option3'
        );
        $data = new field_data_char($fielddataparams);
        $data->save();

        return $field->id;
    }

    /**
     * Create data for our test field field.
     *
     * @param int   $fieldid The database id of the PM custom field
     * @param array $data    The data to set
     */
    protected function create_field_data($fieldid, $data) {
        global $CFG, $DB;

        $field = new field($fieldid);
        $field->load();
        $fieldcontext = $DB->get_field('elis_field_contextlevels', 'contextlevel', array('fieldid'=>$fieldid));

        // Obtain the PM user context.
        switch ($fieldcontext) {
            case CONTEXT_ELIS_USER:
                $context = context_elis_user::instance(200);
                break;
            case CONTEXT_ELIS_COURSE:
                $context = context_elis_course::instance(200);
                break;
            case CONTEXT_ELIS_CLASS:
                $context = context_elis_class::instance(200);
                break;
            case CONTEXT_ELIS_PROGRAM:
                $context = context_elis_program::instance(200);
                break;
        }

        field_data_char::set_for_context_and_field($context, $field, $data);
    }

    /**
     * Create a database record maps a field to an export column.
     *
     * @param string $fieldset   The fieldset to map.
     * @param string    $field      The field to map.
     * @param string $header     The string to display as a CSV column header
     * @param int    $fieldorder A number used to order fields in the export
     */
    protected function create_field_mapping($fieldset, $field, $header = 'Header', $fieldorder = null) {
        global $DB;

        // Set up and insert the record.
        $mapping = new stdClass;
        $mapping->fieldset = $fieldset;
        $mapping->field = $field;
        if (!empty($header)) {
            $mapping->header = $header;
        }

        if ($fieldorder !== null) {
            $mapping->fieldorder = $fieldorder;
        } else {
            $mapping->fieldorder = ($DB->get_field_sql('SELECT MAX(fieldorder) FROM {'.RLIPEXPORT_VERSION1ELIS_FIELD_TABLE.'}')+1);
        }

        $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $mapping);
    }

    /**
     * Fetches our export data as a multi-dimensional array.
     *
     * @param bool $manual          Whether this is a manual run.
     * @param int  $targetstarttime The timestamp representing the theoretical time when this task was meant to be run
     * @param int  $lastruntime     The last time the export was run (required for incremental scheduled export)
     *
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // Set the export to be incremental.
        set_config('nonincremental', 0, 'rlipexport_version1elis');
        // Set the incremental time delta.
        set_config('incrementaldelta', '1d', 'rlipexport_version1elis');

        // Plugin for file IO.
        $fileplugin = new rlip_fileplugin_export();
        $fileplugin->open(RLIP_FILE_WRITE);

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Test the main interface for all fieldsets.
     */
    public function test_rlipexport_version1elis_extrafields_detection() {
        $sets = rlipexport_version1elis_extrafields::get_available_sets();
        $this->assertInternalType('array', $sets);
        $this->assertTrue(in_array('test', $sets));

        $fields = rlipexport_version1elis_extrafields::get_available_fields();
        $this->assertInternalType('array', $fields);
        $this->assertArrayHasKey('test', $fields);
        $this->assertInternalType('array', $fields['test']);
        $this->assertArrayHasKey('testfield', $fields['test']);
        $this->assertEquals('Test Field', $fields['test']['testfield']);
    }

    /**
     * Test getting configuration state from database.
     */
    public function test_rlipexport_version1elis_extrafields_get_config() {
        global $DB;

        $expectedconfigbyfield = array();
        $expectedconfigbyorder = array();

        for ($i = 0; $i < 10; $i++) {
            $record = new stdClass;
            $record->fieldset = 'set'.$i;
            $record->field = 'field'.$i;
            $record->fieldorder = $i;
            $record->header = 'Field Header '.$i;
            $record->id = $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $record);

            $expectedconfigbyfield[$record->fieldset.'/'.$record->field] = $record;
            $expectedconfigbyorder[$record->fieldorder] = $record;
        }

        $configbyfield = rlipexport_version1elis_extrafields::get_config('field');
        $configbyorder = rlipexport_version1elis_extrafields::get_config('order');

        $this->assertInternalType('array', $configbyfield);
        $this->assertEquals($expectedconfigbyfield, $configbyfield);

        $this->assertInternalType('array', $configbyorder);
        $this->assertEquals($expectedconfigbyorder, $configbyorder);
    }

    /**
     * Test rlipexport_version1elis_extrafields::get_enabled_fields()
     */
    public function test_rlipexport_version1elis_extrafields_get_enabled_fields() {
        $this->create_field_mapping('fieldset1', 'field1', 'Field 1', 0);
        $this->create_field_mapping('fieldset1', 'field2', 'Field 2', 1);
        $this->create_field_mapping('fieldset1', 'field3', 'Field 3', 2);
        $this->create_field_mapping('fieldset2', 'field4', 'Field 4', 3);
        $this->create_field_mapping('fieldset2', 'field5', 'Field 5', 4);
        $this->create_field_mapping('fieldset3', 'field6', 'Field 6', 5);

        $expectedout = array();

        $enabledfields = rlipexport_version1elis_extrafields::get_enabled_fields();

        $this->assertInternalType('array', $enabledfields);
        $this->assertEquals(3, count($enabledfields));
        for ($i = 1; $i <= 3; $i++) {
            $this->assertArrayHasKey('fieldset'.$i, $enabledfields);
            $this->assertInternalType('array', $enabledfields['fieldset'.$i]);
        }

        $this->assertEquals(3, count($enabledfields['fieldset1']));
        $this->assertEquals(2, count($enabledfields['fieldset2']));
        $this->assertEquals(1, count($enabledfields['fieldset3']));

        for ($i = 1; $i <= 6; $i++) {
            $fieldset = ($i <= 3) ? 'fieldset1' : 'fieldset2';
            $fieldset = ($i == 6) ? 'fieldset3' : $fieldset;
            $field = 'field'.$i;

            $this->assertArrayHasKey($field, $enabledfields[$fieldset]);
            $this->assertInternalType('object', $enabledfields[$fieldset][$field]);
            $this->assertObjectHasAttribute('fieldset', $enabledfields[$fieldset][$field]);
            $this->assertObjectHasAttribute('field', $enabledfields[$fieldset][$field]);
            $this->assertObjectHasAttribute('header', $enabledfields[$fieldset][$field]);
            $this->assertObjectHasAttribute('fieldorder', $enabledfields[$fieldset][$field]);

            $this->assertEquals($fieldset, $enabledfields[$fieldset][$field]->fieldset);
            $this->assertEquals($field, $enabledfields[$fieldset][$field]->field);
            $this->assertEquals('Field '.$i, $enabledfields[$fieldset][$field]->header);
            $this->assertEquals(($i-1), $enabledfields[$fieldset][$field]->fieldorder);
        }

    }

    /**
     * Test processing incoming form data.
     */
    public function test_rlipexport_version1elis_extrafields_process_config_formdata() {
        $incomingformdata = array(
            'fields' => array(
                    'test/testfield',
                    'test/testfield1', // Non-existant field.
                    'test/testfield2',
                    'test/testfield3',
                    'test/testfield4'
            ),
            'fieldnames' => array(
                    '',
                    'Non-existant',
                    'Test Field One',
                    '',
                    'Test Field Three'
            )
        );
        $expectedprocessedformdata = array(
            'test/testfield' => array(
                'header' => '',
                'fieldset' => 'test',
                'field' => 'testfield',
                'fieldorder' => 0,
            ),
            'test/testfield2' => array(
                'header' => 'Test Field One',
                'fieldset' => 'test',
                'field' => 'testfield2',
                'fieldorder' => 1,
            ),
            'test/testfield3' => array(
                'header' => '',
                'fieldset' => 'test',
                'field' => 'testfield3',
                'fieldorder' => 2,
            ),
            'test/testfield4' => array(
                'header' => 'Test Field Three',
                'fieldset' => 'test',
                'field' => 'testfield4',
                'fieldorder' => 3,
            ),
        );

        $processedformdata = rlipexport_version1elis_extrafields::process_config_formdata($incomingformdata);
        $this->assertEquals($expectedprocessedformdata, $processedformdata);
    }

    /**
     * Test updating configuration.
     */
    public function test_rlipexport_version1elis_extrafields_update_config() {
        global $DB;

        // Generate initial config.
        for ($i = 0; $i < 6; $i++) {
            $record = new stdClass;
            $record->header = 'Test Field '.$i;
            $record->fieldorder = $i;
            $record->fieldset = 'fieldset'.$i;
            $record->field = 'field'.$i;
            $record->id = $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $record);
        }

        // Update config.
        $updateddata = array(
            // Updated record.
            'fieldset0/field0' => array(
                'header' => 'Updated Field 0',
                'fieldset' => 'fieldset0',
                'field' => 'field0',
                'fieldorder' => 0,
            ),
            // Unchanged record.
            'fieldset1/field1' => array(
                'header' => 'Test Field 1',
                'fieldset' => 'fieldset1',
                'field' => 'field1',
                'fieldorder' => 1,
            ),
            // Deleted record (fieldset2/field2), also test reordering after deletion.
            'fieldset3/field3' => array(
                'header' => 'Test Field 3',
                'fieldset' => 'fieldset3',
                'field' => 'field3',
                'fieldorder' => 2,
            ),
            // Added record.
            'fieldset6/field6' => array(
                'header' => 'Test Field 6',
                'fieldset' => 'fieldset6',
                'field' => 'field6',
                'fieldorder' => 3,
            ),
            // Reordered record.
            'fieldset5/field5' => array(
                'header' => 'Test Field 5',
                'fieldset' => 'fieldset5',
                'field' => 'field5',
                'fieldorder' => 4,
            ),
            // Reorder cascade record.
            'fieldset4/field4' => array(
                'header' => 'Test Field 4',
                'fieldset' => 'fieldset4',
                'field' => 'field4',
                'fieldorder' => 5,
            ),
            // Added record to make size of total dataset different.
            'fieldset7/field7' => array(
                'header' => 'Test Field 7',
                'fieldset' => 'fieldset7',
                'field' => 'field7',
                'fieldorder' => 7,
            ),
        );
        rlipexport_version1elis_extrafields::update_config($updateddata);

        // Verify update.
        $records = $DB->get_records(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, array(), 'fieldorder ASC');
        $this->assertNotEmpty($records);
        $this->assertEquals(7, count($records));

        $records = array_values($records);
        $expected = array_values($updateddata);
        for ($i = 0; $i < 6; $i++) {
            $this->assertEquals($expected[$i]['header'], $records[$i]->header);
            $this->assertEquals($expected[$i]['fieldset'], $records[$i]->fieldset);
            $this->assertEquals($expected[$i]['field'], $records[$i]->field);
            $this->assertEquals($expected[$i]['fieldorder'], $records[$i]->fieldorder);
        }
    }

    /**
     * Test getting all extra SELECTs from all fieldsets with enabled fields.
     */
    public function test_rlipexport_version1elis_extrafields_get_extra_select() {
        $this->create_field_mapping('test', 'testfield');
        $this->create_field_mapping('test', 'testfield3');
        $this->create_field_mapping('test2', 'fieldtest2');
        $this->create_field_mapping('test2', 'fieldtest4');

        $extraselect = rlipexport_version1elis_extrafields::get_extra_select();

        $expectedselect = array('testfield', 'testfield3', 'fieldtest2', 'fieldtest4');
        $this->assertEquals($expectedselect, $extraselect);
    }

    /**
     * Test gettng all extra JOINs from all fieldsets with enabled fields.
     */
    public function test_rlipexport_version1elis_extrafields_get_extra_joins() {
        $this->create_field_mapping('test', 'testfield');
        $this->create_field_mapping('test', 'testfield3');
        $this->create_field_mapping('test2', 'fieldtest2');
        $this->create_field_mapping('test2', 'fieldtest4');

        $extrajoins = rlipexport_version1elis_extrafields::get_extra_joins();

        $expectedjoins = array('testfield', 'testfield3', 'fieldtest2', 'fieldtest4');
        $this->assertEquals($expectedjoins, $extrajoins);
    }

    /**
     * Test getting all columns from all fieldsets
     */
    public function test_rlipexport_version1elis_extrafields_get_extra_columns() {
        $this->create_field_mapping('test', 'testfield', 'Header 3', 3);
        $this->create_field_mapping('test', 'testfield2', null, 4);
        $this->create_field_mapping('test', 'testfield3', 'Header 1', 1);
        $this->create_field_mapping('test2', 'fieldtest2', 'Header 2', 2);
        $this->create_field_mapping('test2', 'fieldtest4', 'Header 0', 0);

        $extracolumns = rlipexport_version1elis_extrafields::get_extra_columns();
        $expectedcolumns = array('Header 0', 'Header 1', 'Header 2', 'Header 3', 'Test Field 2');
        $this->assertEquals($expectedcolumns, $extracolumns);
    }

    /**
     * Test getting all data from all fieldsets.
     */
    public function test_rlipexport_version1elis_extrafields_get_all_data() {
        // Mock configured fields.
        $this->create_field_mapping('test', 'testfield', 'Header 3', 3);
        $this->create_field_mapping('test', 'testfield2', 'Header 4', 4);
        $this->create_field_mapping('test', 'testfield3', 'Header 1', 1);
        $this->create_field_mapping('test2', 'fieldtest2', 'Header 2', 2);
        $this->create_field_mapping('test2', 'fieldtest4', 'Header 0', 0);

        // Mock record.
        $record = new stdClass;
        $record->testfield = 'Data 0';
        $record->testfield2 = 'Data 1';
        $record->testfield3 = 'Data 2';
        $record->fieldtest2 = 'Data 3';
        $record->fieldtest4 = 'Data 4';

        // Perform test.
        $additionaldata = rlipexport_version1elis_extrafields::get_all_data($record);
        $expecteddata = array(
            0 => 'Data 4',
            1 => 'Data 2',
            2 => 'Data 3',
            3 => 'Data 0',
            4 => 'Data 1',
        );
        $this->assertEquals($expecteddata, $additionaldata);
    }

    /**
     * Test getting custom fields using the extrafieldsetcustomfieldbase base class.
     */
    public function test_rlipexport_version1elis_extrafieldsetcustomfieldbase_get() {
        // Create custom fields.
        $fieldid1 = $this->create_custom_field('testcf1', 0, true, CONTEXT_ELIS_USER);
        $fieldid2 = $this->create_custom_field('testcf2', 0, true, CONTEXT_ELIS_USER);
        $fieldid3 = $this->create_custom_field('testcf3', 0, true, CONTEXT_ELIS_PROGRAM);
        $fieldid4 = $this->create_custom_field('testcf4', 0, true, CONTEXT_ELIS_PROGRAM);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid1);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid2);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid3);

        // Get available fields.
        $fields = rlipexport_version1elis_extrafieldset_testcustomfields::get_available_fields();

        // Verify.
        $this->assertInternalType('array', $fields);
        $this->assertArrayHasKey('field_'.$fieldid1, $fields);
        $this->assertArrayHasKey('field_'.$fieldid2, $fields);
        $this->assertArrayNotHasKey('field_'.$fieldid3, $fields);
        $this->assertArrayNotHasKey('field_'.$fieldid4, $fields);
        $this->assertEquals('testcf1', $fields['field_'.$fieldid1]);
        $this->assertEquals('testcf2', $fields['field_'.$fieldid2]);
    }

    /**
     * Test rlipexport_version1elis_extrafieldsetcustomfieldbase::init_customfield_data()
     */
    public function test_rlipexport_version1elis_extrafieldsetcustomfieldbase_initcustomfielddata() {
        global $DB;
        $this->load_csv_data();

        // Create custom fields.
        $fieldid1 = $this->create_custom_field('testcf1', 0, true, CONTEXT_ELIS_USER);
        $fieldid2 = $this->create_custom_field('testcf2', 0, true, CONTEXT_ELIS_USER);
        $fieldid3 = $this->create_custom_field('testcf3', 0, false, CONTEXT_ELIS_USER);
        $fieldid4 = $this->create_custom_field('testcf4', 1, true, CONTEXT_ELIS_USER);
        $fieldid5 = $this->create_custom_field('testcf5', 1, false, CONTEXT_ELIS_USER);
        $fieldid6 = $this->create_custom_field('testcf6', 1, true, CONTEXT_ELIS_USER);

        // Populate Data.
        $data = 'option2';
        $this->create_field_data($fieldid1, $data);
        $this->create_field_data($fieldid2, $data);
        $this->create_field_data($fieldid3, $data);
        $data = array('option1', 'option2', 'option3');
        $this->create_field_data($fieldid4, $data);
        $this->create_field_data($fieldid5, $data);
        $this->create_field_data($fieldid6, $data);

        // Update field to not multivalued to create historical multivalue state.
        $field = new field($fieldid6);
        $field->load();
        $field->multivalued = 0;
        $field->save();

        // Fake enabled fields configuration.
        $enabledfields = array(
            'field_'.$fieldid1 => '',
            'field_'.$fieldid2 => '',
            'field_'.$fieldid3 => '',
            'field_'.$fieldid4 => '',
            'field_'.$fieldid5 => '',
            'field_'.$fieldid6 => '',
        );
        $fieldset = new rlipexport_version1elis_extrafieldset_testcustomfields($enabledfields);
        $fieldset->init_customfield_data_test();

        // Test proper tracking of multivalue status.
        $this->assertNotEmpty($fieldset->customfield_multivaluestatus);
        $this->assertInternalType('array', $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid1, $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid2, $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid3, $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid4, $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid5, $fieldset->customfield_multivaluestatus);
        $this->assertArrayHasKey($fieldid6, $fieldset->customfield_multivaluestatus);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_NONE,
                $fieldset->customfield_multivaluestatus[$fieldid1]);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_NONE,
                $fieldset->customfield_multivaluestatus[$fieldid2]);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_NONE,
                $fieldset->customfield_multivaluestatus[$fieldid3]);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_ENABLED,
                $fieldset->customfield_multivaluestatus[$fieldid4]);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_ENABLED,
                $fieldset->customfield_multivaluestatus[$fieldid5]);
        $this->assertEquals(rlipexport_version1elis_extrafieldset_testcustomfields::MULTIVALUE_HISTORICAL,
                            $fieldset->customfield_multivaluestatus[$fieldid6]);

        $this->assertEquals(3, count($fieldset->sql_joins));
        $this->assertEquals(6, count($fieldset->sql_select));

        for ($i = 1; $i <= 3; $i++) {
            $fieldidstr = 'fieldid'.$i;
            $fieldid = $$fieldidstr;
            $this->assertTrue(in_array("custom_data_{$fieldid}.data AS custom_field_{$fieldid}", $fieldset->sql_select));
        }

        for ($i = 4; $i <= 6; $i++) {
            $fieldidstr = 'fieldid'.$i;
            $fieldid = $$fieldidstr;
            $this->assertTrue(in_array("'' AS custom_field_{$fieldid}", $fieldset->sql_select));
        }
    }

    /**
     * Test rlipexport_version1elis_extrafieldsetcustomfieldbase::get_columns()
     */
    public function test_rlipexport_version1elis_extrafieldsetcustomfieldbase_getcolumns() {
        $fieldid1 = $this->create_custom_field('Custom Field One', 0, true, CONTEXT_ELIS_USER);
        $fieldid2 = $this->create_custom_field('Custom Field Two', 0, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid1, '');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid2, 'Updated Name Two');

        $enabledfields = rlipexport_version1elis_extrafields::get_enabled_fields();
        $fieldset = new rlipexport_version1elis_extrafieldset_testcustomfields($enabledfields['testcustomfields']);
        $columns = $fieldset->get_columns();

        $expectedcolumns = array(
            'field_'.$fieldid1 => 'Custom Field One',
            'field_'.$fieldid2 => 'Updated Name Two'
        );
        $this->assertEquals($expectedcolumns, $columns);
    }

    /**
     * Test rlipexport_version1elis_extrafieldsetcustomfieldbase::get_columns()
     */
    public function test_rlipexport_version1elis_extrafieldsetcustomfieldbase_getdata() {
        $this->load_csv_data();

        // Create Fields.
        // Normal Field.
        $fieldid1 = $this->create_custom_field('testcf1', 0, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid1, '');
        $this->create_field_data($fieldid1, 'option1');

        // Second Normal Field.
        $fieldid2 = $this->create_custom_field('testcf2', 0, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid2, '');
        $this->create_field_data($fieldid2, 'option2');

        // Multivalued field with multiple values.
        $fieldid3 = $this->create_custom_field('testcf3', 1, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid3, '');
        $this->create_field_data($fieldid3, array('option1', 'option2'));

        // Multivalued field with single value.
        $fieldid4 = $this->create_custom_field('testcf4', 1, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid4, '');
        $this->create_field_data($fieldid4, array('option1'));

        // Historical multivalued field.
        $fieldid5 = $this->create_custom_field('testcf5', 1, true, CONTEXT_ELIS_USER);
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid5, '');
        $this->create_field_data($fieldid5, array('option1', 'option2'));
        // Update field to not multivalued to create historical multivalue state.
        $field = new field($fieldid5);
        $field->load();
        $field->multivalued = 0;
        $field->save();

        // Datetime field.
        $fieldid6 = $this->create_custom_field('testcf6', 0, false, CONTEXT_ELIS_USER, 'datetime');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid6, '');
        $this->create_field_data($fieldid6, mktime(0, 0, 0, 7, 24, 2012));

        // Datetime with time field.
        $fieldid7 = $this->create_custom_field('testcf7', 0, false, CONTEXT_ELIS_USER, 'datetime');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid7, '');
        $this->create_field_data($fieldid7, mktime(10, 20, 30, 7, 24, 2012));
        $field = new field($fieldid7);
        $field->load();
        $fieldowner = new field_owner($field->owners['manual']->id);
        $fieldowner->load();
        $fieldowner->param_inctime = 1;
        $fieldowner->save();

        // Fields with HTML.
        $fieldid8 = $this->create_custom_field('testcf8', 0, false, CONTEXT_ELIS_USER, 'text');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid8, '');
        $this->create_field_data($fieldid8, '<b>Test Text One</b>');
        $fieldid9 = $this->create_custom_field('testcf9', 0, false, CONTEXT_ELIS_USER, 'textarea');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid9, '');
        $this->create_field_data($fieldid9, '<i>Test Text Two</i>');

        // Mock database record.
        $record = new stdClass;
        $record->userid = 200;
        $record->{'custom_field_'.$fieldid1} = 'option1';
        $record->{'custom_field_'.$fieldid2} = 'option2';
        $record->{'custom_field_'.$fieldid3} = '';
        $record->{'custom_field_'.$fieldid4} = 'option1';
        $record->{'custom_field_'.$fieldid5} = '';
        $record->{'custom_field_'.$fieldid6} = mktime(0, 0, 0, 7, 24, 2012);
        $record->{'custom_field_'.$fieldid7} = mktime(10, 20, 30, 7, 24, 2012);
        $record->{'custom_field_'.$fieldid8} = '<b>Test Text One</b>';
        $record->{'custom_field_'.$fieldid9} = '<i>Test Text Two</i>';

        // Perform Test.
        $enabledfields = rlipexport_version1elis_extrafields::get_enabled_fields();
        $fieldset = new rlipexport_version1elis_extrafieldset_testcustomfields($enabledfields['testcustomfields']);
        $data = $fieldset->get_data($record);

        // Verify Result.
        $expecteddata = array(
            'field_'.$fieldid1 => 'option1',
            'field_'.$fieldid2 => 'option2',
            'field_'.$fieldid3 => 'option1 / option2',
            'field_'.$fieldid4 => 'option1',
            'field_'.$fieldid5 => 'option1',
            'field_'.$fieldid6 => 'Jul/24/2012',
            'field_'.$fieldid7 => 'Jul/24/2012:10:20',
            'field_'.$fieldid8 => 'TEST TEXT ONE',
            'field_'.$fieldid9 => '_Test Text Two_',
        );
        $this->assertEquals($expecteddata, $data);
    }

    /**
     * Test that when a custom field is configured, then deleted, the field is removed from configuration.
     */
    public function test_rlipexport_version1elis_extrafieldsetcustomfieldbase_handledeleted() {
        $this->load_csv_data();
        $fieldid1 = $this->create_custom_field('testcf1', 0, false, CONTEXT_ELIS_USER, 'text');
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid1, 'Field One');
        $this->create_field_data($fieldid1, 'Test Text One');

        $field = new field($fieldid1);
        $field->load();
        $field->delete();

        $enabledfields = rlipexport_version1elis_extrafields::get_enabled_fields();
        $fieldset = new rlipexport_version1elis_extrafieldset_testcustomfields($enabledfields['testcustomfields']);
        $columns = $fieldset->get_columns();

        $this->assertInternalType('array', $columns);
        $this->assertEmpty($columns);

        $record = new stdClass;
        $data = $fieldset->get_data($record);
        $this->assertInternalType('array', $data);
        $this->assertEmpty($data);
    }

    /**
     * Validate that a menu of options can properly support multi-valued custom fields in the export
     */
    public function test_export_multivaluedata_menuofchoices() {
        // Setup.
        $this->load_csv_data();
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid);
        $data = array('option1', 'option2', 'option3');
        $this->create_field_data($fieldid, $data);

        // Obtain data.
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $firstuser = $data[1];
        $this->assertEquals('option1 / option2 / option3', $firstuser[10]);
    }

    /**
     * Data provider that supplies information about the state of custom fields and data
     *
     * @return array The parameter data expected by the test function(s)
     */
    public function multivalue_setup_provider() {
        return array(
                array(0, false),
                array(1, false),
                array(0, true),
                array(1, true)
        );
    }

    /**
     * Validate that a single-value default is used when a user does not have data for a multi-value menu of choices field
     *
     * @param int  $multivalued       1 if the custom field should be defined as multivalued, otherwise 0
     * @param bool $multidataexists True if multi-valued data should exist for some other context, otherwise false
     * @dataProvider multivalue_setup_provider
     */
    public function test_export_multivaluedata_menuofchoices_defaults($multivalued, $multidataexists) {
        global $CFG;

        // Setup.
        $this->load_csv_data();

        // NOTE: always set multivalued at first so array of data can be set.
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid);

        if ($multidataexists) {
            // Set up multi-valued data at some context.
            $field = new field($fieldid);

            $context = new stdClass;
            $context->id = 9999;

            $values = array('value1', 'value2');

            // Persist.
            field_data::set_for_context_and_field($context, $field, $values);
        }

        if ($multivalued === 0) {
            // Disable the multivalue setting.
            $field = new field($fieldid);
            $field->multivalued = 0;
            $field->save();
        }

        // Obtain data.
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $val = ($multivalued === 1 && $multidataexists === false) ? $data[1][10][0] : $data[1][10];
        $this->assertEquals('option3', $val);
    }

    /**
     * Validate that if a menu of choices field is converted from multi-valued to non-multi-valued value, users with multi-values
     * just have the first reported back.
     */
    public function test_export_nonmultivaluedata_ignoresubsequentvalues() {
        global $CFG, $DB;

        // Setup.
        $this->load_csv_data();
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping('testcustomfields', 'field_'.$fieldid);
        $data = array('option1', 'option2', 'option3');
        $this->create_field_data($fieldid, $data);

        // Make non-multi-valued.
        $field = new field($fieldid);
        $field->load();
        $field->multivalued = 0;
        $field->save();

        // Obtain data.
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $firstuser = $data[1];
        $this->assertEquals('option1', $firstuser[10]);
    }

    /**
     * Verify the export against given expected headers and data.
     *
     * @param  array $expectedheaders An array of expected headers.
     * @param  array $expecteddata    An array of expected data.
     */
    public function verify_export_data($expectedheaders, $expecteddata) {
        // Obtain data.
        $data = $this->get_export_data();
        $actualheaders = $data[0];

        // Validation.
        $this->assertEquals(2, count($data));

        // Validate extra field headers. (10 built-in fields + size of $expectedheaders for total header size).
        $this->assertEquals((10 + count($expectedheaders)), count($actualheaders));
        $actualheaderscount = count($actualheaders);
        $expectedheadersnumindexed = array_values($expectedheaders);
        for ($i = 10; $i < $actualheaderscount; $i++) {
            $this->assertEquals($expectedheadersnumindexed[$i - 10], $actualheaders[$i]);
        }

        // Validate data.
        $actualdata = $data[1];
        $this->assertEquals((10 + count($expecteddata)), count($actualdata));
        $actualdatacount = count($actualdata);
        $expecteddatanumindexed = array_values($expecteddata);
        for ($i = 10; $i < $actualdatacount; $i++) {
            $this->assertEquals($expecteddatanumindexed[$i - 10], $actualdata[$i]);
        }
    }

    /**
     * Validate user extra fields.
     */
    public function test_fieldset_user() {
        global $CFG;

        // Expected Headers.
        $expectedheaders = array(
            'mi' => get_string('usermi', 'elis_program'),
            'email' => get_string('email', 'elis_program'),
            'email2' => get_string('email2', 'elis_program'),
            'address' => get_string('address', 'elis_program'),
            'address2' => get_string('address2', 'elis_program'),
            'city' => get_string('city', 'moodle'),
            'state' => get_string('state', 'moodle'),
            'postalcode' => get_string('postalcode', 'elis_program'),
            'country' => get_string('country', 'elis_program'),
            'phone' => get_string('phone', 'moodle'),
            'phone2' => get_string('phone2', 'elis_program'),
            'fax' => get_string('fax', 'elis_program'),
            'birthdate' => get_string('userbirthdate', 'elis_program'),
            'gender' => get_string('usergender', 'elis_program'),
            'language' => get_string('user_language', 'elis_program'),
            'transfercredits' => get_string('user_transfercredits', 'elis_program'),
            'comments' => get_string('user_comments', 'elis_program'),
            'notes' => get_string('user_notes', 'elis_program'),
            'timecreated' => get_string('fld_timecreated', 'elis_program'),
            'timemodified' => get_string('fld_timemodified', 'elis_program'),
        );

        // Add fieldset label prefix.
        $fieldsetlabel = rlipexport_version1elis_extrafieldset_user::get_label();
        foreach ($expectedheaders as $field => $label) {
            $expectedheaders[$field] = $fieldsetlabel.' '.$label;
        }

        // Expected data.
        $expecteddata = array(
            'mi' => 'export_mi',
            'email' => 'export_email',
            'email2' => 'export_email2',
            'address' => 'export_address',
            'address2' => 'export_address2',
            'city' => 'export_city',
            'state' => 'export_state',
            'postalcode' => 'export_postalcode',
            'country' => 'CA',
            'phone' => '1234567890',
            'phone2' => '0987654321',
            'fax' => '1112223333',
            'birthdate' => '1912/01/17',
            'gender' => 'M',
            'language' => 'en',
            'transfercredits' => '2',
            'comments' => 'export_comments',
            'notes' => 'export_notes',
            'timecreated' => '100',
            'timemodified' => '200'
        );

        // Basic Data.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/fieldset_user.csv',
            course::TABLE => $csvloc.'/pmcourses.csv',
            pmclass::TABLE => $csvloc.'/pmclasses.csv',
            student::TABLE => $csvloc.'/students.csv',
        ));
        $this->loadDataSet($dataset);

        // Enable all fields.
        foreach ($expecteddata as $field => $val) {
            $this->create_field_mapping('user', $field, '');
        }

        // Custom Field.
        $fieldid = $this->create_custom_field('custom field 1', 0, false, CONTEXT_ELIS_USER, 'text');
        $this->create_field_mapping('user', 'field_'.$fieldid, '');
        $data = 'custom field 1 data';
        $this->create_field_data($fieldid, $data);
        $expectedheaders['field_'.$fieldid] = 'custom field 1';
        $expecteddata['field_'.$fieldid] = $data;

        $this->verify_export_data($expectedheaders, $expecteddata);
    }

    /**
     * Validate student extra fields.
     */
    public function test_fieldset_student() {
        global $CFG;

        // Expected Headers.
        $expectedheaders = array(
            'credits' => get_string('credits', 'elis_program')
        );

        // Add fieldset label prefix.
        $fieldsetlabel = rlipexport_version1elis_extrafieldset_student::get_label();
        foreach ($expectedheaders as $field => $label) {
            $expectedheaders[$field] = $fieldsetlabel.' '.$label;
        }

        // Expected data.
        $expecteddata = array(
            'credits' => '12'
        );

        // Basic Data.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/pmuser.csv',
            course::TABLE => $csvloc.'/pmcourses.csv',
            pmclass::TABLE => $csvloc.'/pmclasses.csv',
            student::TABLE => $csvloc.'/fieldset_student.csv',
        ));
        $this->loadDataSet($dataset);

        // Enable all fields.
        foreach ($expecteddata as $field => $val) {
            $this->create_field_mapping('student', $field, '');
        }

        $this->verify_export_data($expectedheaders, $expecteddata);
    }

    /**
     * Validate course extra fields.
     */
    public function test_fieldset_course() {
        global $CFG;

        // Expected Headers.
        $expectedheaders = array(
            'name' => get_string('course_name', 'elis_program'),
            'code' => get_string('course_code', 'elis_program'),
            'syllabus' => get_string('course_syllabus', 'elis_program'),
            'lengthdescription' => get_string('courseform:length_description', 'elis_program'),
            'length' => get_string('courseform:duration', 'elis_program'),
            'credits' => get_string('credits', 'elis_program'),
            'completion_grade' => get_string('completion_grade', 'elis_program'),
            'cost' => get_string('cost', 'elis_program'),
            'timecreated' => get_string('timecreated', 'elis_program'),
            'timemodified' => get_string('fld_timemodified', 'elis_program'),
            'version' => get_string('course_version', 'elis_program')
        );

        // Add fieldset label prefix.
        $fieldsetlabel = rlipexport_version1elis_extrafieldset_course::get_label();
        foreach ($expectedheaders as $field => $label) {
            $expectedheaders[$field] = $fieldsetlabel.' '.$label;
        }

        // Expected data.
        $expecteddata = array(
            'name' => 'testcoursename',
            'code' => 'testcode',
            'syllabus' => 'testsyllabus',
            'lengthdescription' => 'testlengthdesc',
            'length' => '50',
            'credits' => '4',
            'completion_grade' => '88',
            'cost' => 'testcost',
            'timecreated' => '100',
            'timemodified' => '200',
            'version' => 'testversion'
        );

        // Basic Data.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/pmuser.csv',
            course::TABLE => $csvloc.'/fieldset_course.csv',
            pmclass::TABLE => $csvloc.'/pmclasses.csv',
            student::TABLE => $csvloc.'/student.csv',
        ));
        $this->loadDataSet($dataset);

        // Enable all fields.
        foreach ($expecteddata as $field => $val) {
            $this->create_field_mapping('course', $field, '');
        }

        // Custom Field.
        $fieldid = $this->create_custom_field('course custom field 1', 0, false, CONTEXT_ELIS_COURSE, 'text');
        $this->create_field_mapping('course', 'field_'.$fieldid, '');
        $data = 'course custom field 1 data';
        $this->create_field_data($fieldid, $data);
        $expectedheaders['field_'.$fieldid] = 'course custom field 1';
        $expecteddata['field_'.$fieldid] = $data;

        $this->verify_export_data($expectedheaders, $expecteddata);
    }

    /**
     * Validate class extra fields.
     */
    public function test_fieldset_class() {
        global $CFG;

        // Expected Headers.
        $expectedheaders = array(
            'idnumber' => get_string('class_idnumber', 'elis_program'),
            'startdate' => get_string('class_startdate', 'elis_program'),
            'enddate' => get_string('class_enddate', 'elis_program'),
            'starttime' => get_string('class_starttime', 'elis_program'),
            'endtime' => get_string('class_endtime', 'elis_program'),
            'maxstudents' => get_string('class_maxstudents', 'elis_program'),
            'instructors' => get_string('instructors', 'elis_program')
        );

        // Add fieldset label prefix.
        $fieldsetlabel = rlipexport_version1elis_extrafieldset_class::get_label();
        foreach ($expectedheaders as $field => $label) {
            $expectedheaders[$field] = $fieldsetlabel.' '.$label;
        }

        // Expected data.
        $expecteddata = array(
            'idnumber' => 'testclass',
            'startdate' => 'Mar/01/2013',
            'enddate' => 'Mar/10/2013',
            'starttime' => '9:30',
            'endtime' => '5:45',
            'maxstudents' => '23',
            'instructors' => 'user1firstname user1lastname, user2firstname user2lastname, user3firstname user3lastname'
        );

        // Basic Data.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/fieldset_class_users.csv',
            instructor::TABLE => $csvloc.'/fieldset_class_instructors.csv',
            course::TABLE => $csvloc.'/pmcourse.csv',
            pmclass::TABLE => $csvloc.'/fieldset_class.csv',
            student::TABLE => $csvloc.'/student.csv',
        ));
        $this->loadDataSet($dataset);

        // Enable all fields.
        foreach ($expecteddata as $field => $val) {
            $this->create_field_mapping('class', $field, '');
        }

        // Custom Field.
        $fieldid = $this->create_custom_field('class custom field 1', 0, false, CONTEXT_ELIS_CLASS, 'text');
        $this->create_field_mapping('class', 'field_'.$fieldid, '');
        $data = 'class custom field 1 data';
        $this->create_field_data($fieldid, $data);
        $expectedheaders['field_'.$fieldid] = 'class custom field 1';
        $expecteddata['field_'.$fieldid] = $data;

        $this->verify_export_data($expectedheaders, $expecteddata);
    }

    /**
     * Validate program extra fields.
     */
    public function test_fieldset_program() {
        global $CFG;

        // Expected Headers.
        $expectedheaders = array(
            'idnumber' => get_string('curriculum_idnumber', 'elis_program'),
            'name' => get_string('curriculum_name', 'elis_program'),
            'description' => get_string('curriculum_description', 'elis_program'),
            'reqcredits' => get_string('curriculum_reqcredits', 'elis_program'),
            'curass_expires' => get_string('curass_expires', 'rlipexport_version1elis')
        );

        // Add fieldset label prefix.
        $fieldsetlabel = rlipexport_version1elis_extrafieldset_program::get_label();
        foreach ($expectedheaders as $field => $label) {
            $expectedheaders[$field] = $fieldsetlabel.' '.$label;
        }

        // Expected data.
        $expecteddata = array(
            'idnumber' => 'PGM1',
            'name' => 'Test Program One',
            'description' => 'Test Program One Description',
            'reqcredits' => '4',
            'curass_expires' => date(get_string('date_format', 'rlipexport_version1elis'), 1362114000)
        );

        // Basic Data.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => $csvloc.'/pmuser.csv',
            course::TABLE => $csvloc.'/pmcourse.csv',
            pmclass::TABLE => $csvloc.'/pmclass.csv',
            student::TABLE => $csvloc.'/student.csv',
            curriculum::TABLE => $csvloc.'/pmprogram.csv',
            curriculumstudent::TABLE => $csvloc.'/pmprogramstudent.csv',
            curriculumcourse::TABLE => $csvloc.'/pmprogramcourse.csv',
        ));
        $this->loadDataSet($dataset);

        // Enable all fields.
        foreach ($expecteddata as $field => $val) {
            $this->create_field_mapping('program', $field, '');
        }

        // Custom Field.
        $fieldid = $this->create_custom_field('program custom field 1', 0, false, CONTEXT_ELIS_PROGRAM, 'text');
        $this->create_field_mapping('program', 'field_'.$fieldid, '');
        $data = 'program custom field 1 data';
        $this->create_field_data($fieldid, $data);
        $expectedheaders['field_'.$fieldid] = 'program custom field 1';
        $expecteddata['field_'.$fieldid] = $data;

        $this->verify_export_data($expectedheaders, $expecteddata);
    }
}