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
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');

class rlip_fileplugin_readmemorywithname extends rlip_fileplugin_base {
    //current file position
    var $index;
    //file data
    var $data;

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     */
    function __construct($data, $filename = '', $fileid = NULL, $sendtobrowser = false) {
        parent::__construct($filename, $fileid, $sendtobrowser);

        $this->index = 0;
        $this->data = $data;
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
        //nothing to do
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        if ($this->index < count($this->data)) {
            //more lines to read, fetch next one
            $result = $this->data[$this->index];
            //move "line pointer"
            $this->index++;
            return $result;
        }

        //out of lines
        return false;
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        //nothing to do
    }

    /**
     * Close the file
     */
    function close() {
        //nothing to do
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        //physical file, so obtain filename from full path
        $parts = explode('/', $this->filename);
        $count = count($parts);
        return $parts[$count - 1];
    }
}

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_fsloguser extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemorywithname($rows, 'user.csv');
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_fslogcourse extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

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

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemorywithname($rows, 'course.csv');
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_fslogenrolment extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemorywithname($rows, 'enrolment.csv');
    }
}

class version1FilesystemLoggingTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        return array('block_rlip_summary_log' => 'block_rlip',
                     'user' => 'moodle',
                     'config_plugins' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('context' => 'moodle',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'log' => 'moodle');
    }

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array $data The import data to process
     * @param string $expected_error The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expected_error, $entitytype) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');

        //cleanup from previous run
        $filename = $CFG->dataroot.'/rliptestfile.log';
        if (file_exists($filename)) {
            unlink($filename);
        }

        //set the log file name to a fixed value
        set_config('logfilelocation', $filename, 'rlipimport_version1');

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        //validate that a log file was created
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
     * Validate that version 1 import plugin instances are set up with file-system
     * loggers
     */
    public function testVersion1ImportInstanceHasFsLogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fslogger.class.php');

        //set up the plugin
        $provider = new rlip_importprovider_fsloguser(array());
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);

        //validation
        $fslogger = $instance->get_fslogger();
        $this->assertEquals($fslogger instanceof rlip_fslogger, true);
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyUserAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[user.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user create
     */
    public function testVersionImportLogsEmptyUserUsernameOnCreate() {
        //validation for an empty username field
        $data = array('action' => 'create',
                      'username' => '',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"username\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty password field on user create
     */
    public function testVersion1ImportLogsEmptyUserPasswordOnCreate() {
        //validation for an empty password field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => '',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"password\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty firstname field on user create
     */
    public function testVersion1ImportLogsEmptyUserFirstnameOnCreate() {
        //validation for an empty firstname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => '',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"firstname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty lastname field on user create
     */
    public function testVersion1ImportLogsEmptyUserLastnameOnCreate() {
        //validation for an empty lastname field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => '',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"lastname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty email field on user create
     */
    public function testVersion1ImportLogsEmptyUserEmailOnCreate() {
        //validation for an empty email field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => '',
                      'city' => 'Rlipcity',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"email\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty city field on user create
     */
    public function testVersion1ImportLogsEmptyUserCityOnCreate() {
        //validation for an empty city field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => '',
                      'country' => 'CA');
        $expected_error = "[user.csv line 2] Required field \"city\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty country field on user create
     */
    public function testVersion1ImportLogsEmptyUserCountryOnCreate() {
        //validation for an empty country field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'Rlipfirstname',
                      'lastname' => 'Rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'Rlipcity',
                      'country' => '');
        $expected_error = "[user.csv line 2] Required field \"country\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user update
     */
    public function testVersionImportLogsEmptyUserUsernameOnUpdate() {
        //validation for an empty username field
        $data = array('action' => 'update',
                      'username' => '');
        $expected_error = "[user.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user delete
     */
    public function testVersionImportLogsEmptyUserUsernameOnDelete() {
        //validation for an empty username field
        $data = array('action' => 'update',
                      'username' => '');
        $expected_error = "[user.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function testVersion1ImportLogsEmptyCourseAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[course.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnCreate() {
        //validation for an empty shortname field
        $data = array('action' => 'create',
                      'shortname' => '',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty fullname field on course create
     */
    public function testVersion1ImportLogsEmptyCourseFullnameOnCreate() {
        //validation for an empty fullname field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => '',
                      'category' => 'rlipcategory');
        $expected_error = "[course.csv line 2] Required field \"fullname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty category field on course create
     */
    public function testVersion1ImportLogsEmptyCourseCategoryOnCreate() {
        //validation for an empty category field
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => '');
        $expected_error = "[course.csv line 2] Required field \"category\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course update
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnUpdate() {
        //validation for an empty shortname field
        $data = array('action' => 'update',
                      'shortname' => '');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course delete
     */
    public function testVersion1ImportLogsEmptyCourseShortnameOnDelete() {
        //validation for an empty shortname field
        $data = array('action' => 'delete',
                      'shortname' => '');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an error is logged for an empty enrolment action field
     */
    public function testVersion1ImportLogsEmptyEnrolmentAction() {
        //validation for an empty action field
        $data = array('action' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"action\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnCreate() {
        //validation for an empty username field
        $data = array('action' => 'create',
                      'username' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnCreate() {
        //validation for an empty context field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"context\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnCreate() {
        //validation for an empty instance field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => '',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"instance\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment create
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnCreate() {
        //validation for an empty role field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"role\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentUsernameOnDelete() {
        //validation fo an empty username field
        $data = array('action' => 'delete',
                      'username' => '',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentContextOnDelete() {
        //validation for an empty context field
        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => '',
                      'instance' => 'rlipshortname',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"context\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentInstanceOnDelete() {
        //validation for an empty instance field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => '',
                      'role' => 'rliprole');
        $expected_error = "[enrolment.csv line 2] Required field \"instance\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment delete
     */
    public function testVersion1ImportLogsEmptyEnrolmentRoleOnDelete() {
        //validation for an empty role field
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => '');
        $expected_error = "[enrolment.csv line 2] Required field \"role\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged when multiple required fields are empty
     */
    public function testVersion1ImportLogsMultipleEmptyFields() {
        //validation for three empty required fields
        $data = array('action' => 'create',
                      'shortname' => '',
                      'fullname' => '',
                      'category' => '');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an appropriate error is logged for a scenario with missing required fields,
     * some of which are only required in a "1 of n"-fashion
     */
    public function testVersion1ImportLogsMultipleMissingFieldsWithOption() {
        //validation for "1 of 3", plus 3 required fields
        $data = array('action' => 'create');
        $expected_error = "[enrolment.csv line 2] One of \"username\", \"email\", \"idnumber\" is required but all are unspecified or empty. Required fields \"context\", \"instance\", \"role\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged for a field that is not in
     * the import file
     */
    public function testVersion1ImportLogsMissingField() {
        //validation for unspecified field "shortname"
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required field \"shortname\" is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that an appropriate error is logged for multiple missing
     * fields that are absolutely necessary
     */
    public function testVersion1ImportLogsMultipleMissingFields() {
        //validation for missing fields shortname, fullname, category
        $data = array('action' => 'create');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validates that error logging works correctly with the user "create or update" functionality
     */
    public function testVersion1ImportLoggingSupportsUserCreateOrUpdate() {
        global $CFG;

        set_config('createorupdate', 1, 'rlipimport_version1');

        //create validation using update
        $data = array('action' => 'update');
        $expected_error = "[user.csv line 2] Required fields \"username\", \"password\", \"firstname\", \"lastname\", \"email\", \"city\", \"country\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        //actually create using update
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        $this->assert_record_exists('user', $data);

        //update validation using create
        $data = array('action' => 'create');
        $expected_error = "[user.csv line 2] Required fields \"username\", \"password\", \"firstname\", \"lastname\", \"email\", \"city\", \"country\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');

        //actually update using create
        $data = array('action' => 'create',
                      'username' => 'rlipusername',
                      'firstname' => 'updatedrlipfirstname');
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        $data = array('username' => 'rlipusername',
                      'mnethostid' => $CFG->mnet_localhost_id,
                      'password' => hash_internal_user_password('Rlippassword!0'),
                      'firstname' => 'updatedrlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validates that error logging works correctly with the course "create or update" functionality
     */
    public function testVersion1ImportLoggingSupportsCourseCreateOrUpdate() {
        global $CFG, $DB;

        set_config('createorupdate', 1, 'rlipimport_version1');

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        //create validation using update
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //actually create using update
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        unset($data['action']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $this->assert_record_exists('course', $data);

        //update validation using create
        $data = array('action' => 'update');
        $expected_error = "[course.csv line 2] Required fields \"shortname\", \"fullname\", \"category\" are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');

        //actually update using create
        $data = array('action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedrlipfullname');
        $provider = new rlip_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $instance->run();

        $data = array('shortname' => 'rlipshortname',
                      'fullname' => 'updatedrlipfullname',
                      'category' => $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory')));
        $this->assert_record_exists('course', $data);
    }
}