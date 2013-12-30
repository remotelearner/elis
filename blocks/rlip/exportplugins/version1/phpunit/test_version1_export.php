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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

/**
 * File plugin that just stores read records in memory
 */
class rlip_fileplugin_bogus extends rlip_fileplugin_base {
    //stored data
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
        //nothing to do
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
        //nothing to do
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
    function get_filename($withpath = false) {
        return 'bogus';
    }
}

/**
 * File plugin that tracks whether the "file" was opened and whether it was
 * closed
 */
class rlip_fileplugin_openclose extends rlip_fileplugin_base {
    //variables for tracking status
    var $opened = false;
    var $closed = false;

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
        //nothing to do
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        //nothing to do
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
    function get_filename($withpath = false) {
        return 'bogus';
    }

    /**
     * Specifies whether the file was ever opened
     *
     * @return boolean true if file was ever opened, otherwise false
     */
    function get_opened() {
        return $this->opened;
    }

    /**
     * Specifies whether the file was ever closed
     *
     * @return boolean true if file was ever closed, otherwise false
     */
    function get_closed() {
        return $this->closed;
    }
}

/**
 * Class for version 1 export correctness
 */
class version1ExportTest extends rlip_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        $result = array('grade_items' => 'moodle',
                        'grade_grades' => 'moodle',
                        'user' => 'moodle',
                        'course' => 'moodle',
                        'course_categories' => 'moodle',
                        'grade_grades_history' => 'moodle',
                        'user_enrolments' => 'moodle',
                        'cohort_members' => 'moodle',
                        'groups_members' => 'moodle',
                        'user_preferences' => 'moodle',
                        'user_info_data' => 'moodle',
                        'user_lastaccess' => 'moodle',
                        'block_instances' => 'moodle',
                        'block_positions' => 'moodle',
                        'filter_active' => 'moodle',
                        'filter_config' => 'moodle',
                        'comments' => 'moodle',
                        'rating' => 'moodle',
                        'role_assignments' => 'moodle',
                        'role_capabilities' => 'moodle',
                        'role_names' => 'moodle',
                        'context' => 'moodle',
                        'events_queue' => 'moodle',
                        'events_queue_handlers' => 'moodle',
                        'cache_flags' => 'moodle',
                        'user_info_category' => 'moodle',
                        'user_info_field' => 'moodle',
                        RLIPEXPORT_VERSION1_FIELD_TABLE => 'rlipexport_version1',
                        'elis_scheduled_tasks' => 'elis_core',
                        RLIP_SCHEDULE_TABLE => 'block_rlip',
                        RLIP_LOG_TABLE => 'block_rlip',
                        'config_plugins' => 'moodle'
                     );

         if ($DB->record_exists('block', array('name' => 'curr_admin'))) {
             //add PM-related tables
             $result['crlm_user_moodle'] = 'elis_program';
         }

         return $result;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE            => 'block_rlip',
                     'external_tokens'         => 'moodle',
                     'external_services_users' => 'moodle',
                     'log'                     => 'moodle',
                     'message'                 => 'moodle',
                     'message_read'            => 'moodle',
                     'message_working'         => 'moodle',
                     'sessions'                => 'moodle');
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    $dataset->addTable('grade_items', dirname(__FILE__).'/phpunit_gradeitems.csv');
	    $dataset->addTable('grade_grades', dirname(__FILE__).'/phpunit_gradegrades.csv');
	    $dataset->addTable('user', dirname(__FILE__).'/phpunit_user.csv');
	    $dataset->addTable('course', dirname(__FILE__).'/phpunit_course.csv');
	    $dataset->addTable('course_categories', dirname(__FILE__).'/phpunit_course_categories.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
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

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_bogus();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
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
    private function create_profile_field($name, $datatype, $categoryid, $param1 = NULL, $defaultdata = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        //core fields
        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== NULL) {
            //set the select options
            $data->param1 = $param1;
        }

        if ($defaultdata !== NULL) {
            //set the default value
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
    private function create_field_mapping($fieldid, $header = 'Header', $fieldorder = 0) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up and insert the record
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

        //set up and insert the record
        $datarecord = new stdClass;
        $datarecord->userid = $userid;
        $datarecord->fieldid = $fieldid;
        $datarecord->data = $data;
        $datarecord->id = $DB->insert_record('user_info_data', $datarecord);

        //return the database id
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
    public function testVersion1ExportCreatesCorrectHeader() {
        $data = $this->get_export_data();

        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'));

        $this->assertEquals(count($data), 1);

        //make sure the data matches the expected header
        $this->assertEquals($data, array($expected_header));
    }

    /**
     * Validate the export data for a simple data set
     */
    public function testVersion1ExportIncludesCorrectData() {
        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);

        $body = $data[1];
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'testcourse',
                               date('M/d/Y', 1000000000),
                               date('M/d/Y'),
                               70.00000,
                               'C-');

        //make sure the data matches the expected data
        $this->assertEquals($body, $expected_body);
    }

    /**
     * Validate that the export data does not include deleted users
     */
    public function testVersion1ExportExcludesDeletedUsers() {
        global $CFG, $DB;

        if ($DB->record_exists('block', array('name' => 'curr_admin'))) {
            //needed to prevent error in PM delete handler
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        }

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //delete the user the "correct" way
        $user = $DB->get_record('user', array('id' => 2));
        delete_user($user);

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing users
     */
    public function testVersion1ExportExcludesMissingUsers() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //delete the user record
        $DB->delete_records('user', array('id' => 2));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing courses
     */
    public function testVersion1ExportExcludesMissingCourses() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //delete the course record
        $DB->delete_records('course', array('id' => 2));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grade items
     */
    public function testVersion1ExportExcludesMissingGradeItems() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //delete the grade item record
        $DB->delete_records('grade_items', array('id' => 1));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data only includes course-level grade items
     */
    public function testVersion1ExportExcludesIncorrectGradeItemTypes() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //change the grade item type
        $item = new stdClass;
        $item->id = 1;
        $item->itemtype = 'category';
        $DB->update_record('grade_items', $item);

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grades
     */
    public function testVersion1ExportExcludesMissingGrades() {
        global $DB;

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //delete the grade record
        $DB->delete_records('grade_grades', array('id' => 1));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the version 1 export includes custom field headers in
     * the output
     */
    public function testVersion1ExportIncludesCustomFieldHeaderInfo() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'),
                                 'Header');

        $this->assertEquals(count($data), 1);

        //make sure the data matches the expected header
        $this->assertEquals($data, array($expected_header));
    }

    /**
     * Validate that the version 1 export includes custom field checkbox data
     * in the output
     */
    public function testVersion1ExportIncludesCustomFieldCheckboxData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipcheckbox', 'checkbox', $categoryid);
        $datarecordid = $this->create_data_record(2, $fieldid, 0);
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
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
    public function testVersion1ExportIncludesCustomFieldCheckboxDefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipcheckbox', 'checkbox', $categoryid, NULL, '0');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
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
    public function testVersion1ExportIncludesCustomFieldDatetimeData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipdate', 'datetime', $categoryid);
        $datarecordid = $this->create_data_record(2, $fieldid, mktime(0, 0, 0, 1, 1, 2012));
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'Jan/01/2012');

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
    public function testVersion1ExportHandlesCustomFieldDatetimeUnset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipdate', 'datetime', $categoryid);
        $datarecordid = $this->create_data_record(2, $fieldid, 0);
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
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
    public function testVersion1ExportIncludesCustomFieldMenuData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rlipmenu', 'menu', $categoryid, 'rlipoption1');
        $this->create_data_record(2, $fieldid, 'rlipoption1');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rlipoption1');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * menu fields
     */
    public function testVersion1ExportIncludesCustomFieldMenuDefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $options = "rlipoption1
                    rlipoption2";
        $fieldid = $this->create_profile_field('rlipmenu', 'menu', $categoryid, $options, 'rlipoption2');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rlipoption2');
    }

    /**
     * Validate that the version 1 export includes custom field textarea data
     * in the output
     */
    public function testVersion1ExportIncludesCustomFieldTextareaData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptextarea', 'textarea', $categoryid);
        $this->create_data_record(2, $fieldid, 'rliptextarea');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextarea');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * textarea fields
     */
    public function testVersion1ExportIncludesCustomFieldTextareaDefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptextarea', 'textarea', $categoryid, NULL, 'rliptextareadefault');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextareadefault');
    }

    /**
     * Validate that the version 1 export includes custom field textinput data
     * in the output
     */
    public function testVersion1ExportIncludesCustomFieldTextinputData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_data_record(2, $fieldid, 'rliptext');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptext');
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * textinput fields
     */
    public function testVersion1ExportIncludesCustomFieldTextinputDefault() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid, NULL, 'rliptextdefault');
        $this->create_field_mapping($fieldid);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals(count($data), 2);
        $row = $data[1];
        $this->assertEquals(count($row), 10);
        $this->assertEquals($row[9], 'rliptextdefault');
    }

    /**
     * Validate that the version 1 export does not include information about
     * delete custom fields
     */
    public function testVersion1ExportIgnoresDeletedCustomFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $this->create_field_mapping(1);

        //set up the expected output
        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'));
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'testcourse',
                               date('M/d/Y', 1000000000),
                               date('M/d/Y'),
                               70.00000,
                               'C-');
        $expected_data = array($expected_header, $expected_body);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals($expected_data, $data);
    }

    /**
     * Validate that the version 1 export shows custom field columns in the
     * configured order, after non-configurable fields
     */
    public function testVersion1ExportRespectsCustomFieldOrder() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        set_config('nonincremental', 1, 'rlipexport_version1');

        $this->load_csv_data();

        //set up necessary custom field information in the database
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_data_record(2, $fieldid, 'rliptext2');
        $this->create_field_mapping($fieldid, 'Header2', 1);
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_data_record(2, $fieldid, 'rliptext');
        $this->create_field_mapping($fieldid);

        //set up the expected output
        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'),
                                 'Header',
                                 'Header2');
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'testcourse',
                               date('M/d/Y', 1000000000),
                               date('M/d/Y'),
                               70.00000,
                               'C-',
                               'rliptext',
                               'rliptext2');
        $expected_data = array($expected_header, $expected_body);

        //obtain our export data based on the current DB state
        $data = $this->get_export_data();

        //data validation
        $this->assertEquals($expected_data, $data);
    }

    /**
     * Validate that the API call for removing a custom profile field from the
     * export works as expected
     */
    public function testVersion1ExportDeletesField() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        //verify setup
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));

        //remove the field from the export
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $fieldid));
        rlipexport_version1_config::delete_field_from_export($id);

        //validation
        $exists = $DB->record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the API call for adding a custom profile field to the
     * export works as expected
     */
    public function testVersion1ExportAddsField() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);

        //add the field to the export
        rlipexport_version1_config::add_field_to_export($fieldid);

        //validation
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid));
    }

    /**
     * Validate that the API call for moving a custom profile field up in the
     * export field order works as expected
     */
    public function testVersion1ExportMovesFieldUp() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second field and mapping record
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //move the second field up
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $secondfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_UP);

        //validation
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid,
                                                                           'fieldorder' => 1));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfieldid,
                                                                           'fieldorder' => 0));
    }

    /**
     * Validate that the API call for moving a custom profile field down in the
     * export field order works as expected
     */
    public function testVersion1ExportMovesFieldDown() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second field and mapping record
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //move the first field down
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_DOWN);

        //validation
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid,
                                                                           'fieldorder' => 1));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfieldid,
                                                                           'fieldorder' => 0));
    }

    /**
     * Validate that the API call for updating the header text for a single
     * configured custom profile field works as expected
     */
    public function testVersion1ExportUpdatesHeader() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $fieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($fieldid);

        //update the header
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $fieldid));
        rlipexport_version1_config::update_field_header($id, 'Updatedvalue');

        //validation
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $fieldid,
                                                                           'header' => 'Updatedvalue'));
    }

    /**
     * Validate that the API call for updating the header text for multiple
     * configured custom profile fields works as expected
     */
    public function testVersion1ExportUpdatesHeaders() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second field and mapping record
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //obtain DB record ids
        $firstid = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfieldid));
        $secondid = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $secondfieldid));

        //update the headers
        $data = array('header_'.$firstid => 'Updatedvalue1',
                      'header_'.$secondid => 'Updatedvalue2');
        rlipexport_version1_config::update_field_headers($data);

        //validation
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid,
                                                                           'header' => 'Updatedvalue1'));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $secondfieldid,
                                                                           'header' => 'Updatedvalue2'));
    }

    /**
     * Validate that the API call for obtaining the recordset of configured
     * export fields works as expected
     */
    public function testVersion1ExportReportsConfiguredFields() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second field and mapping record
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //track whether each expected record was found
        $found_first = false;
        $found_second = false;

        //look through the configured fields recordset
        if ($recordset = rlipexport_version1_config::get_configured_fields()) {
            foreach ($recordset as $record) {
                //conditions for matching the first and second expected records
                $is_first = $record->name == 'rliptext' && $record->header == 'Header' &&
                            $record->fieldorder == 0;
                $is_second = $record->name == 'rliptext2' && $record->header == 'Header2' &&
                             $record->fieldorder == 1;

                if ($is_first) {
                    //first record found
                    $found_first = true;
                } else if ($is_second) {
                    //second record found
                    $found_second = true;
                } else {
                    //invalid record found
                    $this->assertEquals(true, false);
                }
            }
        } else {
            //problem fetching recordset
            $this->assertEquals(true, false);
        }

        //validate that both records were found
        $this->assertEquals($found_first, true);
        $this->assertEquals($found_second, true);
    }

    /**
     * Validate that the API call for obtaining the recordset of available
     * export fields works as expected
     */
    public function testVersion1ExportReportsAvailableFields() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second field without mapping record
        $secondfieldid = $this->create_profile_field('rliptext2', 'text', $categoryid);

        //track whether each expected record was found
        $found_second = false;

        //look through the available fields recordset
        if ($recordset = rlipexport_version1_config::get_available_fields()) {
            foreach ($recordset as $record) {
                //condition for matching the expected record
                $is_second =  $secondfieldid && $record->name = 'rliptext2';

                if ($is_second) {
                    //expected record found
                    $found_second = true;
                } else {
                    //invalid record found
                    $this->assertEquals(true, false);
                }
            }
        } else {
            //problem fetching recordset
            $this->assertEquals(true, false);
        }

        //validate that the record was found
        $this->assertEquals($found_second, true);
    }

    /**
     * Validate that the API call for moving a profile field up in export
     * position deals with deleted user profile fields correctly
     */
    public function testVersion1ExportHandlesDeletedFieldsWhenMovingUp() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second mapping record without a field
        $secondfieldid = 9999;
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //set up a third field with a mapping record
        $thirdfieldid = $this->create_profile_field('rliptext3', 'text', $categoryid);
        $this->create_field_mapping($thirdfieldid, 'Header3', 2);

        //move the third field up
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $thirdfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_UP);

        //validate that the first and third fields swapped, ignoring the second field
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid,
                                                                           'fieldorder' => 2));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $thirdfieldid,
                                                                           'fieldorder' => 0));
    }

    /**
     * Validate that the API call for moving a profile field down in export
     * position deals with deleted user profile fields correctly
     */
    public function testVersion1ExportHandlesDeletedFieldsWhenMovingDown() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipexport', 'version1').'/lib.php';
        require_once($file);

        //set up the category and field, along with the export mapping
        $categoryid = $this->create_custom_field_category();
        $firstfieldid = $this->create_profile_field('rliptext', 'text', $categoryid);
        $this->create_field_mapping($firstfieldid);

        //set up a second mapping record without a field
        $secondfieldid = 9999;
        $this->create_field_mapping($secondfieldid, 'Header2', 1);

        //set up a third field with a mapping record
        $thirdfieldid = $this->create_profile_field('rliptext3', 'text', $categoryid);
        $this->create_field_mapping($thirdfieldid, 'Header3', 2);

        //move the first field down
        $id = $DB->get_field(RLIPEXPORT_VERSION1_FIELD_TABLE, 'id', array('fieldid' => $firstfieldid));
        rlipexport_version1_config::move_field($id, rlipexport_version1_config::DIR_DOWN);

        //validate that the first and third fields swapped, ignoring the second field
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $firstfieldid,
                                                                           'fieldorder' => 2));
        $this->assert_record_exists(RLIPEXPORT_VERSION1_FIELD_TABLE, array('fieldid' => $thirdfieldid,
                                                                           'fieldorder' => 0));
    }

    /**
     * Validates that a standard export run, using the data plugin factory,
     * correctly opens and closes the export file via the file plugin
     */
    public function testVersionExportOpensAndClosesFile() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //run run the export
        $fileplugin = new rlip_fileplugin_openclose();
        $instance = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin);
        $instance->run();

        //validate that the export file was opened
        $this->assertEquals($fileplugin->get_opened(), true);
        //validat that the export file was closed
        $this->assertEquals($fileplugin->get_closed(), true);
    }

    /**
     * Validate that the export runs incrementally, only including data
     * modified within the set time delta
     */
    public function testVersion1ExportRunsIncrementally() {
        global $DB;

        //set to incremental, using 1 day as the maximum delta
        set_config('nonincremental', 0, 'rlipexport_version1');
        set_config('incrementaldelta', '1d', 'rlipexport_version1');

        $this->load_csv_data();

        //this is the record we want to exclude
        $record = new stdClass;
        $record->id = 1;
        $record->timemodified = 0;
        $DB->update_record('grade_grades', $record);

        //time to set on recrod to be included
        $time = time() - 23 * HOURSECS;

        //create a new export record
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
        $gradegrade->userid = 2;
        $gradegrade->finalgrade = 80.00000;
        $gradegrade->timemodified = $time;
        $DB->insert_record('grade_grades', $gradegrade);

        $data = $this->get_export_data();

        //set up the expected output, only including the more recent record
        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'));
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'newcourse',
                               date('M/d/Y', $time),
                               date('M/d/Y'),
                               80.00000,
                               'B-');
        $expected_data = array($expected_header, $expected_body);

        //validation
        $this->assertEquals($data, $expected_data);
    }

    /**
     * Validate that the export runs incrementally, only including data
     * modified within the set time delta
     */
    public function testVersion1ExportRunsIncrementallyScheduled() {
        global $DB;

        set_config('nonincremental', 0, 'rlipexport_version1');

        $this->load_csv_data();

        //this is the record we want to exclude
        $record = new stdClass;
        $record->id = 1;
        $record->timemodified = 0;
        $DB->update_record('grade_grades', $record);

        //time to set on recrod to be included
        $time = time() - 23 * HOURSECS;

        //create a new export record
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
        $gradegrade->userid = 2;
        $gradegrade->finalgrade = 80.00000;
        $gradegrade->timemodified = $time;
        $DB->insert_record('grade_grades', $gradegrade);

        //get export data with manual set to false as this is a scheduled incremental export
        $targetstarttime = time(); //today
        $lastruntime = time() - 86400; //yesterday

        $data = $this->get_export_data(false, $targetstarttime, $lastruntime);

        //set up the expected output, only including the more recent record
        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'));
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'newcourse',
                               date('M/d/Y', $time),
                               date('M/d/Y'),
                               80.00000,
                               'B-');
        $expected_data = array($expected_header, $expected_body);

        //validation
        $this->assertEquals($data, $expected_data);
    }

    /**
     * Validate that the export generates the correct filename
     */
    public function testVersion1ExportCreatesCorrectFilename() {
        global $USER;
        if (empty($USER->timezone)) {
            $USER->timezone = 99;
        }
        $plugin = 'rlipexport_version1';
        $baseexportfile = 'export_version1.csv';
        set_config('export_file', $baseexportfile, $plugin);
        set_config('export_file_timestamp', false, $plugin);
        // base export file w/o timestamp
        $exportfile = rlip_get_export_filename($plugin, $USER->timezone);
        $this->assertEquals(basename($exportfile), $baseexportfile);

        set_config('export_file_timestamp', true, $plugin);
        // export file WITH timestamp
        $exportfile = rlip_get_export_filename($plugin, $USER->timezone);
        $parts = explode('_', basename($exportfile));
        $this->assertEquals($parts[0], 'export');
        $this->assertEquals($parts[1], 'version1');
        $timestamp = userdate(time(), // $string['export_file_timestamp']
                              get_string('export_file_timestamp', $plugin),
                              $USER->timezone);
        $date_parts = explode('_', $timestamp);
        //echo 'Date parts = ';
        //print_object($date_parts);
        if (count($date_parts) == 4) {
            $this->assertEquals($parts[2], $date_parts[0]); // MMM
            $this->assertEquals($parts[3], $date_parts[1]); // DD
            $this->assertEquals($parts[4], $date_parts[2]); // YYYY
            $this->assertLessThanOrEqual($parts[5], $date_parts[3]); // hhmmss
            // TBD^^^ intval($parts[5]), intval($date_parts[3])
        }
    }

    /**
     * Test that the export path is created
     */
    public function testVersion1ExportPathCreated() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        set_config('export_path', 'exportpath/deeper', 'rlipexport_version1');

        $filepath = $CFG->dataroot.'/exportpath/deeper';
        //cleanup filepath if it exists at beginning of test
        if (file_exists($filepath)) {
            rmdir($filepath);
        }

        //set up the export file path
        $filename = 'rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        //set up data for one course and one enroled user
        $this->load_csv_data();

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //lower bound on starttime
        $starttime = time();
        //run the export
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        $records = $DB->get_records(RLIP_LOG_TABLE);
//        foreach ($records as $record) {
//            print_object($record);
//        }
//
//        echo "\n looking for file: $filepath/$filename";
        $exists = file_exists($filepath.'/'.$filename);
        //cleanup the new file and folder
        if ($exists) {
            unlink($filepath.'/'.$filename);
            rmdir($filepath);
        }
        $this->assertEquals($exists, true);

    }

    /**
     * Test for an export path
     */
    public function testVersion1InvalidExportPath() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        set_config('export_path', 'invalidexportpath', 'rlipexport_version1');
        $filepath = $CFG->dataroot.'/invalidexportpath';

        //create a folder and make it executable only
        mkdir($filepath, 0100);

        //set up the export file path
        $filename = 'rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        //set up data for one course and one enroled user
        $this->load_csv_data();

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //lower bound on starttime
        $starttime = time();
        //run the export
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //database error log validation
        $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => "Export file rliptestexport.csv cannot be processed because the folder: {$dataroot}/invalidexportpath/ is not accessible. Please fix the export path.");
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        $records = $DB->get_records(RLIP_LOG_TABLE);
//        foreach ($records as $record) {
//            print_object($record);
//        }

        //cleanup the folder
        if (file_exists($filepath)) {
            rmdir($filepath);
        }
        $this->assertEquals($exists, true);

        //fs logger error log validation
        $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
        $expected_error = "Export file rliptestexport.csv cannot be processed because the folder: {$dataroot}/invalidexportpath/ is not accessible. Please fix the export path.\n";

        //validate that a log file was created
        $plugin_type = 'export';
        $plugin = 'rlipexport_version1';
        $format = get_string('logfile_timestamp','block_rlip');
        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $logfile = $record->logpath;
            break;
        }
        $testfilename = $logfile;;
        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename));

        //fetch log line
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            //no line found
            $this->assertEquals(0, 1);
        }

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);
    }
}
