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
 * @package    rlipexport_version1
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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

/**
 * File plugin that just stores read records in memory
 */
class rlip_fileplugin_bogus extends rlip_fileplugin_base {
    // Stored data.
    private $data;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->data = array();
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        // Nothing to do.
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        $this->data[] = $entry;
    }

    /**
     * Close the file
     */
    public function close() {
        // Nothing to do.
    }

    /**
     * Specifies the data currently stored
     *
     * @return array The data stored
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        return 'bogus';
    }
}

/**
 * File plugin that tracks whether the "file" was opened and whether it was
 * closed
 */
class rlip_fileplugin_openclose extends rlip_fileplugin_base {
    // Variables for tracking status.
    public $opened = false;
    public $closed = false;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->opened = true;
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        // Nothing to do.
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        // Nothing to do.
    }

    /**
     * Close the file
     */
    public function close() {
        $this->closed = true;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        return 'bogus';
    }

    /**
     * Specifies whether the file was ever opened
     *
     * @return boolean true if file was ever opened, otherwise false
     */
    public function get_opened() {
        return $this->opened;
    }

    /**
     * Specifies whether the file was ever closed
     *
     * @return boolean true if file was ever closed, otherwise false
     */
    public function get_closed() {
        return $this->closed;
    }
}

/**
 * Class for version 1 export correctness.
 * @group rlipexport_version1
 * @group block_rlip
 */
class version1export_testcase extends rlip_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            'grade_items' => $csvloc.'/phpunit_gradeitems.csv',
            'grade_grades' => $csvloc.'/phpunit_gradegrades.csv',
            'user' => $csvloc.'/phpunit_user.csv',
            'course' => $csvloc.'/phpunit_course.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Fetches our export data as a multi-dimensional array
     *
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1').'/version1.class.php';
        require_once($file);

        // Plugin for file IO.
        $fileplugin = new rlip_fileplugin_bogus();
        $fileplugin->open(RLIP_FILE_WRITE);

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Create a custom field category
     *
     * @return int The database id of the new category
     */
    private function create_custom_field_category() {
        global $DB;

        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        return $category->id;
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @param string $param1 Extra parameter, used for select options
     * @param string $defaultdata Default value
     * @return int The id of the created profile field
     */
    private function create_profile_field($name, $datatype, $categoryid, $param1 = null, $defaultdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        // Core fields.
        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== null) {
            // Set the select options.
            $data->param1 = $param1;
        }

        if ($defaultdata !== null) {
            // Set the default value.
            $data->defaultdata = $defaultdata;
        }

        $field->define_save($data);
        return $data->id;
    }

    /**
     * Create a database record maps a field to an export column
     *
     * @param int $fieldid The database id of the Moodle custom field
     * @param string $header The string to display as a CSV column header
     * @param int $fieldorder A number used to order fields in the export
     */
    private function create_field_mapping($fieldid, $header = 'Header', $fieldorder = 1) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up and insert the record.
        $mapping = new stdClass;
        $mapping->fieldid = $fieldid;
        $mapping->header = $header;
        $mapping->fieldorder = $fieldorder;
        $DB->insert_record(RLIPEXPORT_VERSION1_FIELD_TABLE, $mapping);
    }

    /**
     * Creates a user info data record with the supplied information
     *
     * @param int $userid The Moodle user's id
     * @param int $fieldid The Moodle profile field id
     * @param string $data The data to set
     */
    private function create_data_record($userid, $fieldid, $data) {
        global $DB;

        // Set up and insert the record.
        $datarecord = new stdClass;
        $datarecord->userid = $userid;
        $datarecord->fieldid = $fieldid;
        $datarecord->data = $data;
        $datarecord->id = $DB->insert_record('user_info_data', $datarecord);

        // Return the database id.
        return $datarecord->id;
    }

    /**
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate the export header row for an empty data set
     */
    public function test_version1exportcreatescorrectheader() {
        $data = $this->get_export_data();

        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1')
        );

        $this->assertEquals(count($data), 1);

        // Make sure the data matches the expected header.
        $this->assertEquals($data, array($expectedheader));
    }

    /**
     * Validate the export data for a simple data set
     */
    public function test_version1exportincludescorrectdata() {
        global $DB;
        set_config('nonincremental', 1, 'rlipexport_version1');
        $DB->delete_records('user');
        $this->load_csv_data();

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);

        $body = $data[1];
        $expectedbody = array(
                'test',
                'user',
                'testuser',
                'testuseridnumber',
                'testcourse',
                date('M/d/Y', 1000000000),
                date('M/d/Y'),
                70.00000,
                'C-'
        );

        // Make sure the data matches the expected data.
        $this->assertEquals($expectedbody, $body);
    }

    /**
     * Validate that the export data does not include deleted users
     */
    public function test_version1exportexcludesdeletedusers() {
        global $CFG, $DB;

        if ($DB->record_exists('block', array('name' => 'curr_admin'))) {
            // Needed to prevent error in PM delete handler.
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        }

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Clear the config admin list.
        set_config('siteadmins', '');

        // Delete the user the "correct" way.
        $user = $DB->get_record('user', array('id' => 101));
        delete_user($user);

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing users
     */
    public function test_version1exportexcludesmissingusers() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Clear the config admin list.
        set_config('siteadmins', '');

        // Delete the user record.
        $DB->delete_records('user', array('id' => 101));

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing courses
     */
    public function test_version1exportexcludesmissingcourses() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Delete the course record.
        $DB->delete_records('course', array('id' => 2));

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grade items
     */
    public function test_version1exportexcludesmissinggradeitems() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Delete the grade item record.
        $DB->delete_records('grade_items', array('id' => 1));

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data only includes course-level grade items
     */
    public function test_version1exportexcludesincorrectgradeitemtypes() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Change the grade item type.
        $item = new stdClass;
        $item->id = 1;
        $item->itemtype = 'category';
        $DB->update_record('grade_items', $item);

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grades
     */
    public function test_version1exportexcludesmissinggrades() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Delete the grade record.
        $DB->delete_records('grade_grades', array('id' => 1));

        $data = $this->get_export_data();

        // Make sure only the header was included.
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the version 1 export includes custom field headers in
     * the output
     */
    public function test_version1exportincludescustomfieldheaderinfo() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1'),
                'Header'
        );

        $this->assertEquals(count($data), 1);

        // Make sure the data matches the expected header.
        $this->assertEquals($data, array($expectedheader));
    }

    /**
     * Validate that the version 1 export includes custom field checkbox data
     * in the output
     */
    public function test_version1exportincludescustomfieldcheckboxdata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipcheckbox', 'checkbox', $categoryid);
        $datarecordid = $this->create_data_record(101, $fieldid, 0);
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'no');

        $datarecord = new stdClass;
        $datarecord->id = $datarecordid;
        $datarecord->data = 1;
        $DB->update_record('user_info_data', $datarecord);

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'yes');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * checkbox fields
     */
    public function test_version1exportincludescustomfieldcheckboxdefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipcheckbox', 'checkbox', $categoryid, null, '0');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'no');

        $field = new stdClass;
        $field->id = $fieldid;
        $field->defaultdata = '1';
        $DB->update_record('user_info_field', $field);

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'yes');
    }

    /**
     * Validate that the version 1 export includes custom field datetime data
     * in the output
     */
    public function test_version1exportincludescustomfielddatetimedata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipdate', 'datetime', $categoryid);
        $datarecordid = $this->create_data_record(101, $fieldid, mktime(0, 0, 0, 1, 1, 2012));
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals('Jan/01/2012', $row[9]);

        $field = new stdClass;
        $field->id = $fieldid;
        $field->param3 = 1;
        $DB->update_record('user_info_field', $field);

        $datarecord = new stdClass;
        $datarecord->id = $datarecordid;
        $datarecord->data = mktime(10, 10, 0, 1, 1, 2012);
        $DB->update_record('user_info_data', $datarecord);

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'Jan/01/2012, 10:10 am');
    }

    /**
     * Validate that the version 1 export adds a special marker for unset
     * datetime custom fields
     */
    public function test_version1exporthandlescustomfielddatetimeunset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipdate', 'datetime', $categoryid);
        $datarecordid = $this->create_data_record(101, $fieldid, 0);
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $marker = get_string('nodatemarker', 'rlipexport_version1');
        $this->assertEquals($row[9], $marker);
    }

    /**
     * datetime currently doesn't support default values
     */

    /**
     * Validate that the version 1 export includes custom field menu data
     * in the output
     */
    public function test_version1exportincludescustomfieldmenudata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipmenu', 'menu', $categoryid, 'rlipoption1');
        $this->create_data_record(101, $fieldid, 'rlipoption1');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals('rlipoption1', $row[9]);
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * menu fields
     */
    public function test_version1exportincludescustomfieldmenudefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $options = "rlipoption1
                    rlipoption2";
        $fieldid = $this->create_profile_field('rlipmenu', 'menu', $categoryid, $options, 'rlipoption2');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rlipoption2');
    }

    /**
     * Validate that the version 1 export includes custom field textarea data
     * in the output
     */
    public function test_version1exportincludescustomfieldtextareadata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptextarea', 'textarea', $categoryid);
        $this->create_data_record(101, $fieldid, 'rliptextarea');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextarea');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * textarea fields
     */
    public function test_version1exportincludescustomfieldtextareadefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptextarea', 'textarea', $categoryid, null, 'rliptextareadefault');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextareadefault');
    }

    /**
     * Validate that the version 1 export includes custom field textinput data
     * in the output
     */
    public function test_version1exportincludescustomfieldtextinputdata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_data_record(101, $fieldid, 'rliptext');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptext');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * textinput fields
     */
    public function test_version1exportincludescustomfieldtextinputdefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid, null, 'rliptextdefault');
        $this->create_field_mapping($fieldid);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextdefault');
    }

    /**
     * Validate that the version 1 export does not include information about
     * delete custom fields
     */
    public function test_version1exportignoresdeletedcustomfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        $DB->delete_records('user');
        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $this->create_field_mapping(1);

        // Set up the expected output.
        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1')
        );
        $expectedbody = array(
                'test',
                'user',
                'testuser',
                'testuseridnumber',
                'testcourse',
                date('M/d/Y', 1000000000),
                date('M/d/Y'),
                70.00000,
                'C-'
        );
        $expecteddata = array($expectedheader, $expectedbody);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals($expecteddata, $data);
    }

    /**
     * Validate that the version 1 export shows custom field columns in the
     * configured order, after non-configurable fields
     */
    public function test_version1exportrespectscustomfieldorder() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        $DB->delete_records('user');
        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        // Set up necessary custom field information in the database.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_data_record(101, $fieldid, 'rliptext2');
        $this->create_field_mapping($fieldid, 'Header2', 2);
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_data_record(101, $fieldid, 'rliptext');
        $this->create_field_mapping($fieldid);

        // Set up the expected output.
        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1'),
                'Header',
                'Header2'
        );
        $expectedbody = array(
                'test',
                'user',
                'testuser',
                'testuseridnumber',
                'testcourse',
                date('M/d/Y', 1000000000),
                date('M/d/Y'),
                70.00000,
                'C-',
                'rliptext',
                'rliptext2'
        );
        $expecteddata = array($expectedheader, $expectedbody);

        // Obtain our export data based on the current DB state.
        $data = $this->get_export_data();

        // Data validation.
        $this->assertEquals($expecteddata, $data);
    }

    /**
     * Validate that the API call for removing a custom profile field from the
     * export works as expected
     */
    public function test_version1exportdeletesfield() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        // Verify setup.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));

        // Remove the field from the export.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $fieldid));
        rlipexport_version1_config::delete_field_from_export($id);

        // Validation.
        $exists = $DB->record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the API call for adding a custom profile field to the
     * export works as expected
     */
    public function test_version1exportaddsfield() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);

        // Add the field to the export.
        rlipexport_version1_config::add_field_to_export($fieldid);

        // Validation.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));
    }

    /**
     * Validate that the API call for moving a custom profile field up in the
     * export field order works as expected
     */
    public function test_version1exportmovesfieldup() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second field and mapping record.
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 2);

        // Move the second field up.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $secondfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_UP);

        // Validation.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid, 'fieldorder' => 2));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfieldid, 'fieldorder' => 1));
    }

    /**
     * Validate that the API call for moving a custom profile field down in the
     * export field order works as expected
     */
    public function test_version1exportmovesfielddown() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second field and mapping record.
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 2);

        // Move the first field down.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_DOWN);

        // Validation.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid, 'fieldorder' => 2));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfieldid, 'fieldorder' => 1));
    }

    /**
     * Validate that the API call for updating the header text for a single
     * configured custom profile field works as expected
     */
    public function test_version1exportupdatesheader() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        // Update the header.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $fieldid));
        rlipexport_version1_config::update_field_header($id, 'Updatedvalue');

        // Validation.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid, 'header' => 'Updatedvalue'));
    }

    /**
     * Validate that the API call for updating the header text for multiple
     * configured custom profile fields works as expected
     */
    public function test_version1exportupdatesheaders() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfldid);

        // Set up a second field and mapping record.
        $secondfldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfldid, 'Header2', 2);

        // Obtain DB record ids.
        $firstid = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfldid));
        $secondid = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $secondfldid));

        // Update the headers.
        $data = array('header_'.$firstid => 'Updatedvalue1',
                      'header_'.$secondid => 'Updatedvalue2');
        rlipexport_version1_config::update_field_headers($data);

        // Validation.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfldid, 'header' => 'Updatedvalue1'));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfldid, 'header' => 'Updatedvalue2'));
    }

    /**
     * Validate that the API call for obtaining the recordset of configured
     * export fields works as expected
     */
    public function test_version1exportreportsconfiguredfields() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second field and mapping record.
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 2);

        // Track whether each expected record was found.
        $foundfirst = false;
        $foundsecond = false;

        // Look through the configured fields recordset.
        if ($recordset = rlipexport_version1_config::get_configured_fields()) {
            foreach ($recordset as $record) {
                // Conditions for matching the first and second expected records.
                $isfirst = $record->name == 'rliptext' && $record->header == 'Header' && $record->fieldorder == 1;
                $issecond = $record->name == 'rliptext2' && $record->header == 'Header2' && $record->fieldorder == 2;

                if ($isfirst) {
                    // First record found.
                    $foundfirst = true;
                } else if ($issecond) {
                    // Second record found.
                    $foundsecond = true;
                } else {
                    // Invalid record found.
                    $this->assertEquals(true, false);
                }
            }
        } else {
            // Problem fetching recordset.
            $this->assertEquals(true, false);
        }

        // Validate that both records were found.
        $this->assertEquals($foundfirst, true);
        $this->assertEquals($foundsecond, true);
    }

    /**
     * Validate that the API call for obtaining the recordset of available
     * export fields works as expected
     */
    public function test_version1exportreportsavailablefields() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second field without mapping record.
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);

        // Track whether each expected record was found.
        $foundsecond = false;

        // Look through the available fields recordset.
        if ($recordset = rlipexport_version1_config::get_available_fields()) {
            foreach ($recordset as $record) {
                // Condition for matching the expected record.
                $issecond =  $secondfieldid && $record->name = 'rliptext2';

                if ($issecond) {
                    // Expected record found.
                    $foundsecond = true;
                } else {
                    // Invalid record found.
                    $this->assertEquals(true, false);
                }
            }
        } else {
            // Problem fetching recordset.
            $this->assertEquals(true, false);
        }

        // Validate that the record was found.
        $this->assertEquals($foundsecond, true);
    }

    /**
     * Validate that the API call for moving a profile field up in export
     * position deals with deleted user profile fields correctly
     */
    public function test_version1exporthandlesdeletedfieldswhenmovingup() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second mapping record without a field.
        $secondfieldid = 9999;
        $this->create_field_mapping($secondfieldid, 'Header2', 2);

        // Set up a third field with a mapping record.
        $thirdfieldid = $this->create_profile_field('rliptext3', 'text', $categoryid);
        $this->create_field_mapping($thirdfieldid, 'Header3', 3);

        // Move the third field up.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $thirdfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_UP);

        // Validate that the first and third fields swapped, ignoring the second field.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid, 'fieldorder' => 3));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $thirdfieldid, 'fieldorder' => 1));
    }

    /**
     * Validate that the API call for moving a profile field down in export
     * position deals with deleted user profile fields correctly
     */
    public function test_version1exporthandlesdeletedfieldswhenmovingdown() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        // Set up the category and field, along with the export mapping.
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        // Set up a second mapping record without a field.
        $secondfieldid = 9999;
        $this->create_field_mapping($secondfieldid, 'Header2', 2);

        // Set up a third field with a mapping record.
        $thirdfieldid = $this->create_profile_field('rliptext3', 'text', $categoryid);
        $this->create_field_mapping($thirdfieldid, 'Header3', 3);

        // Move the first field down.
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_DOWN);

        // Validate that the first and third fields swapped, ignoring the second field.
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid, 'fieldorder' => 3));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $thirdfieldid, 'fieldorder' => 1));
    }

    /**
     * Validates that a standard export run, using the data plugin factory,
     * correctly opens and closes the export file via the file plugin
     */
    public function test_versionexportopensandclosesfile() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Run run the export.
        $fileplugin = new rlip_fileplugin_openclose();
        $instance = rlip_dataplugin_factory::factory('rlipexport_version1', null, $fileplugin);
        $instance->run();

        // Validate that the export file was opened.
        $this->assertEquals($fileplugin->get_opened(), true);
        // Validat that the export file was closed.
        $this->assertEquals($fileplugin->get_closed(), true);
    }

    /**
     * Validate that the export runs incrementally, only including data
     * modified within the set time delta
     */
    public function test_version1exportrunsincrementally() {
        global $DB;
        $DB->delete_records('user');
        // Set to incremental, using 1 day as the maximum delta.
        set_config('nonincremental', 0, 'rlipexport_version1');
        set_config('incrementaldelta', '1d', 'rlipexport_version1');

        $this->load_csv_data();

        // This is the record we want to exclude.
        $record = new stdClass;
        $record->id = 1;
        $record->timemodified = 0;
        $DB->update_record('grade_grades', $record);

        // Time to set on recrod to be included.
        $time = time() - 23 * HOURSECS;

        // Create a new export record.
        $course = new stdClass;
        $course->shortname = 'newcourse';
        $course->startdate = $time;
        $course->category = 1;
        $course->id = $DB->insert_record('course', $course);

        $gradeitem = new stdClass;
        $gradeitem->itemtype = 'course';
        $gradeitem->courseid = $course->id;
        $gradeitem->id = $DB->insert_record('grade_items', $gradeitem);

        $gradegrade = new stdClass;
        $gradegrade->itemid = $gradeitem->id;
        $gradegrade->userid = 101;
        $gradegrade->finalgrade = 80.00000;
        $gradegrade->timemodified = $time;
        $DB->insert_record('grade_grades', $gradegrade);

        $data = $this->get_export_data();

        // Set up the expected output, only including the more recent record.
        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1')
        );
        $expectedbody = array(
                'test',
                'user',
                'testuser',
                'testuseridnumber',
                'newcourse',
                date('M/d/Y', $time),
                date('M/d/Y'),
                80.00000,
                'B-'
        );
        $expecteddata = array($expectedheader, $expectedbody);

        // Validation.
        $this->assertEquals($expecteddata, $data);
    }

    /**
     * Validate that the export runs incrementally, only including data
     * modified within the set time delta
     */
    public function test_version1exportrunsincrementallyscheduled() {
        global $DB;
        $DB->delete_records('user');
        set_config('nonincremental', 0, 'rlipexport_version1');

        $this->load_csv_data();

        // This is the record we want to exclude.
        $record = new stdClass;
        $record->id = 1;
        $record->timemodified = 0;
        $DB->update_record('grade_grades', $record);

        // Time to set on recrod to be included.
        $time = time() - 23 * HOURSECS;

        // Create a new export record.
        $course = new stdClass;
        $course->shortname = 'newcourse';
        $course->startdate = $time;
        $course->category = 1;
        $course->id = $DB->insert_record('course', $course);

        $gradeitem = new stdClass;
        $gradeitem->itemtype = 'course';
        $gradeitem->courseid = $course->id;
        $gradeitem->id = $DB->insert_record('grade_items', $gradeitem);

        $gradegrade = new stdClass;
        $gradegrade->itemid = $gradeitem->id;
        $gradegrade->userid = 101;
        $gradegrade->finalgrade = 80.00000;
        $gradegrade->timemodified = $time;
        $DB->insert_record('grade_grades', $gradegrade);

        // Get export data with manual set to false as this is a scheduled incremental export.
        $targetstarttime = time(); // Today.
        $lastruntime = time() - 86400; // Yesterday.

        $data = $this->get_export_data(false, $targetstarttime, $lastruntime);

        // Set up the expected output, only including the more recent record.
        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1'),
                get_string('header_lastname', 'rlipexport_version1'),
                get_string('header_username', 'rlipexport_version1'),
                get_string('header_useridnumber', 'rlipexport_version1'),
                get_string('header_courseidnumber', 'rlipexport_version1'),
                get_string('header_startdate', 'rlipexport_version1'),
                get_string('header_enddate', 'rlipexport_version1'),
                get_string('header_grade', 'rlipexport_version1'),
                get_string('header_letter', 'rlipexport_version1')
        );
        $expectedbody = array(
                'test',
                'user',
                'testuser',
                'testuseridnumber',
                'newcourse',
                date('M/d/Y', $time),
                date('M/d/Y'),
                80.00000,
                'B-'
        );
        $expecteddata = array($expectedheader, $expectedbody);

        // Validation.
        $this->assertEquals($data, $expecteddata);
    }

    /**
     * Validate that the export generates the correct filename
     */
    public function test_version1exportcreatescorrectfilename() {
        global $USER;
        if (empty($USER->timezone)) {
            $USER->timezone = 99;
        }
        $plugin = 'rlipexport_version1';
        $baseexportfile = 'export_version1.csv';
        set_config('export_file', $baseexportfile, $plugin);
        set_config('export_file_timestamp', false, $plugin);
        // Base export file w/o timestamp.
        $exportfile = rlip_get_export_filename($plugin, $USER->timezone);
        $this->assertEquals(basename($exportfile), $baseexportfile);

        set_config('export_file_timestamp', true, $plugin);
        // Export file WITH timestamp.
        $exportfile = rlip_get_export_filename($plugin, $USER->timezone);
        $parts = explode('_', basename($exportfile));
        $this->assertEquals($parts[0], 'export');
        $this->assertEquals($parts[1], 'version1');
        $timestamp = userdate(time(), get_string('export_file_timestamp', $plugin), $USER->timezone);
        $dateparts = explode('_', $timestamp);

        if (count($dateparts) == 4) {
            $this->assertEquals($parts[2], $dateparts[0]); // MMM.
            $this->assertEquals($parts[3], $dateparts[1]); // DD.
            $this->assertEquals($parts[4], $dateparts[2]); // YYYY.
            $this->assertLessThanOrEqual($parts[5], $dateparts[3]); // Hhmmss.
        }
    }

    /**
     * Test that the export path is created
     */
    public function test_version1exportpathcreated() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        set_config('export_path', 'exportpath/deeper', 'rlipexport_version1');

        $filepath = $CFG->dataroot.'/exportpath/deeper';
        // Cleanup filepath if it exists at beginning of test.
        if (file_exists($filepath)) {
            rmdir($filepath);
        }

        // Set up the export file path.
        $filename = 'rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Create a scheduled job.
        $data = array(
            'plugin' => 'rlipexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'rlipexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Lower bound on starttime.
        $starttime = time();
        $datestr = date('M_j_Y_His', $starttime);
        $outputfilename = 'rliptestexport_'.$datestr.'.csv';
        // Run the export.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        $records = $DB->get_records(RLIP_LOG_TABLE);
        $exists = file_exists($filepath.'/'.$outputfilename);
        // Cleanup the new file and folder.
        if ($exists) {
            unlink($filepath.'/'.$outputfilename);
            rmdir($filepath);
        }
        $this->assertTrue($exists);

    }

    /**
     * Test for an export path
     */
    public function test_version1invalidexportpath() {
        global $CFG, $DB, $USER;

        // Check if test is being run as root.
        if (posix_getuid() === 0) {
            $this->markTestSkipped('This test will always fail when run as root.');
        }

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        set_config('export_path', 'invalidexportpath', 'rlipexport_version1');
        $filepath = $CFG->dataroot.'/invalidexportpath';

        // Create a folder and make it executable only.
        // Cleanup the folder first if it already exists.
        if (file_exists($filepath)) {
            // Remove any files.
            if (!empty($filepath)) {
                foreach (glob("{$filepath}/*") as $logfile) {
                    unlink($logfile);
                }
            }
            rmdir($filepath);
        }
        mkdir($filepath, 0100);

        // Set up the export file path.
        $filename = 'rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Create a scheduled job.
        $data = array(
            'plugin' => 'rlipexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'rlipexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Lower bound on starttime.
        $starttime = time();
        // Run the export.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Database error log validation.
        $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";

        $msgparam = "Export file rliptestexport.csv cannot be processed because the folder: {$dataroot}/invalidexportpath/ is not";
        $msgparam .= " accessible. Please fix the export path.";
        $params = array('message' => $msgparam);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        // Cleanup the folder.
        if (file_exists($filepath)) {
            // Remove any files.
            if (!empty($filepath)) {
                foreach (glob("{$filepath}/*") as $logfile) {
                    unlink($logfile);
                }
            }
            rmdir($filepath);
        }

        $this->assertEquals($exists, true);

        // Fs logger error log validation.
        $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
        $expectederror = "Export file rliptestexport.csv cannot be processed because the folder: {$dataroot}/invalidexportpath/ is";
        $expectederror .= " not accessible. Please fix the export path.\n";

        // Validate that a log file was created.
        $plugintype = 'export';
        $plugin = 'rlipexport_version1';
        $format = get_string('logfile_timestamp', 'block_rlip');
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $logfile = $record->logpath;
            break;
        }
        $testfilename = $logfile;;
        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename));

        // Fetch log line.
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            // No line found.
            $this->assertEquals(0, 1);
        }

        // Data validation.
        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actualerror = substr($line, $prefixlength);
        $this->assertEquals($expectederror, $actualerror);
    }
}
