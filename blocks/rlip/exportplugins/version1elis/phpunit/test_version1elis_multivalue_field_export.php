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
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/phpunit/rlip_fileplugin_export.class.php');
$file = get_plugin_directory('rlipexport', 'version1elis').'/lib.php';
require_once($file);

/**
 * Test class for validating that multivalue custom fields are correctly
 * supported in the ELIS export
 */
class version1elisMultivalueFieldExport extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array(
            'config_plugins'                    => 'moodle',
            'context'                           => 'moodle',
            RLIPEXPORT_VERSION1ELIS_FIELD_TABLE => 'rlipexport_version1elis',
            course::TABLE                       => 'elis_program',
            field_category::TABLE               => 'elis_core',
            field_category_contextlevel::TABLE  => 'elis_core',
            field_contextlevel::TABLE           => 'elis_core',
            field_data_char::TABLE              => 'elis_core',
            field::TABLE                        => 'elis_core',
            pmclass::TABLE                      => 'elis_program',
            student::TABLE                      => 'elis_program',
            user::TABLE                         => 'elis_program'
        );
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data($multiple_users = false) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();

        //data for multiple users
	    $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourses.csv');
	    $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclasses.csv');
	    $dataset->addTable(student::TABLE, dirname(__FILE__).'/students.csv');
	    $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmusers.csv');

        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Fetches our export data as a multi-dimensional array
     *
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        //set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');
        //set the incremental time delta
        set_config('incrementaldelta', '1d', 'rlipexport_version1elis');

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_export();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Create a test ELIS user custom field
     *
     * @param int $multivalued The multivalued value to set in the field definition
     * @param boolean $set_options Set to true to set the available menu of choices
     *                             options
     * @return int The id of the created field
     */
    protected function create_custom_field($multivalued = 1, $set_options = true) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        //field
        $field = new field(array(
            'name'        => 'testcustomfieldname',
            'datatype'    => 'char',
            'multivalued' => $multivalued
        ));
        $category = new field_category(array('name' => 'testcategoryname'));
        $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USER, $category);

        //owner
        $owner_options = array('control' => 'menu');
        if ($set_options) {
            $owner_options['options'] = "option1\r\noption2\r\noption3";
        }
        field_owner::ensure_field_owner_exists($field, 'manual', $owner_options);

        //default
        $data = new field_data_char(array(
            'contextid' => NULL,
            'fieldid'   => $field->id,
            'data'      => 'option3'
        ));
        $data->save();

        return $field->id;
    }

    /**
     * Create a database record maps a field to an export column
     *
     * @param int $fieldid The database id of the PM custom field
     * @param string $header The string to display as a CSV column header
     * @param int $fieldorder A number used to order fields in the export
     */
    protected function create_field_mapping($fieldid, $header = 'Header', $fieldorder = 0) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/lib.php';
        require_once($file);

        //set up and insert the record
        $mapping = new stdClass;
        $mapping->fieldid = $fieldid;
        $mapping->header = $header;
        $mapping->fieldorder = $fieldorder;
        $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $mapping);
    }

    /**
     * Create data for our test field field
     *
     * @param int $fieldid The database id of the PM custom field
     * @param array $data The data to set
     */
    protected function create_field_data($fieldid, $data) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        //obtain the PM user context
        $context = context_elis_user::instance(200);

        $field = new field($fieldid);

        field_data_char::set_for_context_and_field($context, $field, $data);
    }

    /**
     * Validate that a menu of options can properly support multi-valued custom
     * fields in the export
     */
    public function testExportContainsMultivalueDataForMenuOfChoices() {
        //setup
        $this->load_csv_data();
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping($fieldid);
        $data = array(
            'option1',
            'option2',
            'option3'
        );
        $this->create_field_data($fieldid, $data);

        //obtain data
        $data = $this->get_export_data();

        //validation
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $firstuser = $data[1];
        $this->assertEquals('option1 / option2 / option3', $firstuser[10]);
    }

    /**
     * Data provider that supplies information about the state of custom fields
     * and data
     *
     * @return array The parameter data expected by the test function(s)
     */
    public function multivalueSetupProvider() {
        return array(
            array(0, false),
            array(1, false),
            array(0, true),
            array(1, true)
        );
    }

    /**
     * Validate that a single-value default is used when a user does not have
     * data for a multi-value menu of choices field
     *
     * @param int $multivalued 1 if the custom field should be defined as
     *                         multivalued, otherwise 0
     * @param boolean $multi_data_exists True if multi-valued data should exist
     *                                   for some other context, otherwise false
     * @dataProvider multivalueSetupProvider
     */
    public function testExportContainsDefaultsForMenuOfChoices($multivalued, $multi_data_exists) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        //setup
        $this->load_csv_data();
        //NOTE: always set multivalued at first so array of data can be set
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping($fieldid);

        if ($multi_data_exists) {
            //set up multi-valued data at some context
            $field = new field($fieldid);

            $context = new stdClass;
            $context->id = 9999;

            $values = array(
                'value1',
                'value2'
            );

            //persist
            field_data::set_for_context_and_field($context, $field, $values);
        }

        if ($multivalued == 0) {
            //disable the multivalue setting
            $field = new field($fieldid);
            $field->multivalued = 0;
            $field->save();
        }

        //obtain data
        $data = $this->get_export_data();

        //validation
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $firstuser = $data[1];
        $this->assertEquals('option3', $firstuser[10]);
    }

    /**
     * Validate that if a menu of choices field is converted from multi-valued
     * to non-multi-valued value, users with multi-values just have the first reported back
     */
    public function testExportIgnoresSubsequentValuesForNonMultivalueField() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        //setup
        $this->load_csv_data();
        $fieldid = $this->create_custom_field();
        $this->create_field_mapping($fieldid);
        $data = array(
            'option1',
            'option2',
            'option3'
        );
        $this->create_field_data($fieldid, $data);

        //make non-multi-valued
        $field = new field($fieldid);
        $field->load();
        $field->multivalued = 0;
        $field->save();

        //obtain data
        $data = $this->get_export_data();

        //validation
        $this->assertEquals(3, count($data));

        $header = $data[0];
        $this->assertEquals('Header', $header[10]);

        $firstuser = $data[1];
        $this->assertEquals('option1', $firstuser[10]);
    }
}