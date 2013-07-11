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
 * @package    rlipimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/file_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/delay_after_three.class.php');

/**
 * Class that fetches import files for the user import
 */
class rlipimport_version1_importprovider_fsloguser extends rlipimport_version1_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }
        return parent::get_import_file($entity, 'user.csv');
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_fslogcourse extends rlipimport_version1_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity, 'course.csv');
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlipimport_version1_importprovider_fslogenrolment extends rlipimport_version1_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }
        return parent::get_import_file($entity, 'enrolment.csv');
    }
}

/**
 * Test filesystem logging.
 * @group block_rlip
 * @group rlipimport_version1
 */
class version1filesystemlogging_testcase extends rlip_test {

    /**
     * Run before the start of the class.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
    }

    /**
     * Run before each test.
     */
    public function setUp() {
        parent::setUp();
        set_config('defaultblocks_override', ' ');
    }

    /**
     * Get site shortname
     * @return string The site shortname.
     */
    public function getsitename() {
        global $DB;
        $record = $DB->get_record('course', array('id' => SITEID));
        return $record->shortname;
    }

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array $data The import data to process
     * @param string $expectederror The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expectederror, $entitytype) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        // Suppress output for now.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Validate that a log file was created.
        $manual = true;
        // Get first summary record - at times, multiple summary records are created and this handles that problem.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        // Get logfile name.
        $plugintype = 'import';
        $plugin = 'rlipimport_version1';
        $format = get_string('logfile_timestamp', 'block_rlip');
        $testfilename = $filepath.'/'.$plugintype.'_version1_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        // Get most recent logfile.

        $filename = self::get_current_logfile($testfilename);

        if (!file_exists($filename)) {
            echo "\n can't find logfile: $filename for \n$testfilename";
        }
        $this->assertTrue(file_exists($filename));

        // Fetch log line.
        $pointer = fopen($filename, 'r');

        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');

        while (!feof($pointer)) {
            $error = fgets($pointer);
            if (!empty($error)) { // Could be an empty new line.
                if (is_array($expectederror)) {
                    $actualerror[] = substr($error, $prefixlength);
                } else {
                    $actualerror = substr($error, $prefixlength);
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expectederror, $actualerror);
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
     * Creates a test user
     *
     * @param string $username The user's username
     * @param string $email The user's email
     * @param string $idnumber The user's idnumber
     *
     * @return int The database record id of the created user
     */
    private function create_test_user($username = 'rlipusername', $email = 'rlipuser@rlipdomain.com',
                                      $idnumber = 'rlipidnumber') {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = $username;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->email = $email;
        $user->password = 'Rlippassword!1234';
        $user->idnumber = $idnumber;

        return user_create_user($user);
    }

    /**
     * Creates a test course, including the category it belongs to
     *
     * @return int The database record id of the created course
     */
    private function create_test_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        // Create the category.
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        $contextcoursecat = get_context_instance(CONTEXT_COURSECAT, $categoryid);

        // Create the course.
        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $categoryid;

        $course = create_course($course);
        get_context_instance(CONTEXT_COURSE, $course->id);

        return $course->id;
    }

    /**
     * Creates a test role, assignable at all necessary context levels
     *
     * @param string $fullname The new role's fullname
     * @param string $shortname The new role's shortname
     * @param string $description The new role's description
     * @return int The database record id of the created course
     */
    private function create_test_role($fullname = 'rlipfullname', $shortname = 'rlipshortname',
                                      $description = 'rlipdescription') {
        // Create the role.
        $roleid = create_role($fullname, $shortname, $description);

        // Make it assignable at all necessary contexts.
        $contexts = array(
            CONTEXT_COURSE,
            CONTEXT_COURSECAT,
            CONTEXT_USER,
            CONTEXT_SYSTEM
        );
        set_role_contextlevels($roleid, $contexts);

        return $roleid;
    }

    /**
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
    }

    /**
     * Obtains a list of strings that identify specific user fields using field "value" syntax
     *
     * @param array $data The import data for the current line
     * @param string $prefix Prefix used in field mappings
     * @return array List of identifying strings
     */
    private function get_user_identifiers($data, $prefix = '') {
        $identifiers = array();
        if (isset($data[$prefix.'username'])) {
            $value = $data[$prefix.'username'];
            $identifiers[] = "username \"{$value}\"";
        }
        if (isset($data[$prefix.'email'])) {
            $value = $data[$prefix.'email'];
            $identifiers[] = "email \"{$value}\"";
        }
        if (isset($data[$prefix.'idnumber'])) {
            $value = $data[$prefix.'idnumber'];
            $identifiers[] = "idnumber \"{$value}\"";
        }
        return $identifiers;
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function test_version1importlogsemptyuseraction() {
        // Create mapping record.
        $this->create_mapping_record('user', 'action', 'customaction');

        // Validation for an empty action field.
        $data = array('customaction' => '', 'username' => 'rlipusername');
        $expectederror = "[user.csv line 2] User could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user create
     */
    public function test_version1importlogsemptyuserusernameoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        // Validation for an empty username field.
        $data = array(
            'action' => 'create',
            'customusername' => '',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User could not be created. Required field customusername is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty password field on user create
     */
    public function test_version1importlogsemptyuserpasswordoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'password', 'custompassword');

        // Validation for an empty password field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'custompassword' => '',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be ";
        $expectederror .= "created. Required field custompassword is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty firstname field on user create
     */
    public function test_version1importlogsemptyuserfirstnameoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'firstname', 'customfirstname');

        // Validation for an empty firstname field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'customfirstname' => '',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be ";
        $expectederror .= "created. Required field customfirstname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty lastname field on user create
     */
    public function test_version1importlogsemptyuserlastnameoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'lastname', 'customlastname');

        // Validation for an empty lastname field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'customlastname' => '',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. Required field customlastname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty email field on user create
     */
    public function test_version1importlogsemptyuseremailoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        // Validation for an empty email field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'customemail' => '',
            'city' => 'Rlipcity',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User could not be created. Required field customemail is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty city field on user create
     */
    public function test_version1importlogsemptyusercityoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'city', 'customcity');

        // Validation for an empty city field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'customcity' => '',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. Required field customcity is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty country field on user create
     */
    public function test_version1importlogsemptyusercountryoncreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        // Validation for an empty country field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'customcountry' => ''
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. Required field customcountry is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on a create action
     */
    public function test_version1importlogscreatemessagewithuseridnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        // Validation for an empty country field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'Rlipfirstname',
            'lastname' => 'Rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Rlipcity',
            'customcountry' => '',
            'idnumber' => 'rlipidnumber'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectederror .= "idnumber \"rlipidnumber\" could not be created. Required field customcountry is unspecified ";
        $expectederror .= "or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user update
     */
    public function test_version1importlogsemptyuserusernameonupdate() {
        // Create mapping records.
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        // Validation for an empty username field.
        $data = array('action' => 'update', 'customusername' => '');

        $expectederror = "[user.csv line 2] User could not be updated. One of customusername, customemail, customidnumber is ";
        $expectederror .= "required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when not
     * specified on an update action
     */
    public function test_version1importlogsupdatemessagewithoutuseridnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array(
            'action' => 'update',
            'customusername' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ";
        $expectederror .= "could not be updated. customusername value of \"rlipusername\" does not refer to a valid ";
        $expectederror .= "user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on an update action
     */
    public function test_version1importlogsupdatemessagewithuseridnumber() {
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectederror .= "idnumber \"rlipidnumber\" could not be updated. username value of \"rlipusername\" does not ";
        $expectederror .= "refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages work without username specified on
     * an update action
     */
    public function test_version1importlogsusermessagewithoutusername() {
        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        // Validation for an empty country field.
        $data = array(
            'action' => 'update',
            'customemail' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        );

        $expectederror = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" ";
        $expectederror .= "could not be updated. customemail value of \"rlipuser@rlipdomain.com\" does not refer to a ";
        $expectederror .= "valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty username field on user delete
     */
    public function test_version1importlogsemptyuserusernameondelete() {
        // Create mapping records.
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        // Validation for an empty username field.
        $data = array('action' => 'delete', 'customusername' => '');
        $expectederror = "[user.csv line 2] User could not be deleted. One of customusername, customemail, customidnumber is ";
        $expectederror .= "required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages do not include the idnumber when
     * not specified on a delete action
     */
    public function test_version1importlogsdeletemessagewithoutuseridnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array(
            'action' => 'delete',
            'customusername' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ";
        $expectederror .= "could not be deleted. customusername value of \"rlipusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that general user messages include the idnumber when specified
     * on a delete action
     */
    public function test_version1importlogsdeletemessagewithuseridnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array(
            'action' => 'delete',
            'customusername' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectederror .= "idnumber \"rlipidnumber\" could not be deleted. customusername value of \"rlipusername\" ";
        $expectederror .= "does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validates that an error is logged for an empty user action field
     */
    public function test_version1importlogsemptycourseaction() {
        // Create mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        // Validation for an empty action field.
        $data = array('customaction' => '', 'shortname' => 'rlipshortname');
        $expectederror = "[course.csv line 2] Course could not be processed. Required field customaction is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course create
     */
    public function test_version1importlogsemptycourseshortnameoncreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        // Validation for an empty shortname field.
        $data = array(
            'action' => 'create',
            'customshortname' => '',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );

        $expectederror = "[course.csv line 2] Course could not be created. Required field customshortname is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty fullname field on course create
     */
    public function test_version1importlogsemptycoursefullnameoncreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'fullname', 'customfullname');

        // Validation for an empty fullname field.
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'customfullname' => '',
            'category' => 'rlipcategory'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Required field ";
        $expectederror .= "customfullname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty category field on course create
     */
    public function test_version1importlogsemptycoursecategoryoncreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'category', 'customcategory');

        // Validation for an empty category field.
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'customcategory' => ''
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Required field ";
        $expectederror .= "customcategory is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course update
     */
    public function test_version1importlogsemptycourseshortnameonupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        // Validation for an empty shortname field.
        $data = array('action' => 'update', 'customshortname' => '');

        $expectederror = "[course.csv line 2] Course could not be updated. Required field customshortname is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty shortname field on course delete
     */
    public function test_version1importlogsemptycourseshortnameondelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        // Validation for an empty shortname field.
        $data = array('action' => 'delete', 'customshortname' => '');

        $expectederror = "[course.csv line 2] Course could not be deleted. Required field customshortname is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an error is logged for an empty enrolment action field
     */
    public function test_version1importlogsemptyenrolmentaction() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'action', 'customaction');

        // Validation for an empty action field.
        $data = array(
            'customaction' => '',
            'username' => 'rlipusername',
            'context' => 'rlipcontext',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $expectederror = "[enrolment.csv line 2] Enrolment could not be processed. Required field customaction is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment create
     */
    public function test_version1importlogsemptyenrolmentusernameoncreate() {
        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Validation for an empty username field.
        $data = array(
            'action' => 'create',
            'customusername' => '',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. One of customusername, customemail, ";
        $expectederror .= "customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment create
     */
    public function test_version1importlogsemptyenrolmentcontextoncreate() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        // Validation for an empty context field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'customcontext' => '',
            'instance' => 'rlipshortname',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. Required field customcontext is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment create
     */
    public function test_version1importlogsemptyenrolmentinstanceoncreate() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Validation for an empty instance field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'custominstance' => '',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. Required field custominstance is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment create
     */
    public function test_version1importlogsemptyenrolmentroleoncreate() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        // Validation for an empty role field.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'customrole' => ''
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. Required field customrole is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty username field on enrolment delete
     */
    public function test_version1importlogsemptyenrolmentusernameondelete() {
        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Validation fo an empty username field.
        $data = array(
            'action' => 'delete',
            'customusername' => '',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. One of customusername, customemail, ";
        $expectederror .= "customidnumber is required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty context field on enrolment delete
     */
    public function test_version1importlogsemptyenrolmentcontextondelete() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        // Validation for an empty context field.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'customcontext' => '',
            'instance' => 'rlipshortname',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. Required field customcontext is unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty instance field on enrolment delete
     */
    public function test_version1importlogsemptyenrolmentinstanceondelete() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Validation for an empty instance field.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'custominstance' => '',
            'role' => 'rliprole'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. Required field custominstance is unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an error is logged for an empty role field on enrolment delete
     */
    public function test_version1importlogsemptyenrolmentroleondelete() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        // Validation for an empty role field.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'customrole' => ''
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. Required field customrole is unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged when multiple required fields are empty
     */
    public function test_version1importlogsmultipleemptyfields() {
        // Create mapping records.
        $this->create_mapping_record('course', 'shortname', 'customshortname');
        $this->create_mapping_record('course', 'fullname', 'customfullname');
        $this->create_mapping_record('course', 'category', 'customcategory');

        // Validation for three empty required fields.
        $data = array(
            'action' => 'create',
            'customshortname' => '',
            'customfullname' => '',
            'customcategory' => ''
        );

        $expectederror = "[course.csv line 2] Course could not be created. Required fields customshortname, customfullname,";
        $expectederror .= " customcategory are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that an appropriate error is logged for a scenario with missing required fields,
     * some of which are only required in a "1 of n"-fashion
     */
    public function test_version1importlogsmultipleemptyfieldswithoption() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Validation for "1 of 3", plus 3 required fields.
        $data = array(
            'action' => 'create',
            'customusername' => '',
            'customemail' => '',
            'customidnumber' => '',
            'context' => '',
            'role' => ''
        );
        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. One of customusername, customemail,";
        $expectederror .= " customidnumber is required but all are unspecified or empty. Required fields context, instance,";
        $expectederror .= " role are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validates that an appropriate error is logged for a field that is empty
     * in the import file
     */
    public function test_version1importlogsemptyfield() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        // Validation for unspecified field "shortname".
        $data = array('action' => 'update', 'customshortname' => '');
        $expectederror = "[course.csv line 2] Course could not be updated. Required field customshortname is unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validates that error logging works correctly with the user "create or update" functionality
     */
    public function test_version1importloggingsupportsusercreateorupdate() {
        global $CFG;

        set_config('createorupdate', 1, 'rlipimport_version1');

        // Create mapping records.
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'password', 'custompassword');
        $this->create_mapping_record('user', 'firstname', 'customfirstname');
        $this->create_mapping_record('user', 'lastname', 'customlastname');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'city', 'customcity');
        $this->create_mapping_record('user', 'country', 'customcountry');

        // Create a user so it can be updated.
        self::cleanup_log_files();
        $data = array(
            'action' => 'create',
            'customusername' => 'rlipusername',
            'custompassword' => 'Rlippassword!0',
            'customfirstname' => 'rlipfirstname',
            'customlastname' => 'rliplastname',
            'customemail' => 'rlipuser@rlipdomain.com',
            'customcity' => 'rlipcity',
            'customcountry' => 'CA'
        );
        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array(
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $data['customusername'],
            'password' => hash_internal_user_password($data['custompassword']),
            'firstname' => $data['customfirstname'],
            'lastname' => $data['customlastname'],
            'email' => $data['customemail'],
            'city' => $data['customcity'],
            'country' => $data['customcountry']
        );
        $this->assert_record_exists('user', $data);

        // Update validation using create.
        $data = array(
            'action' => 'create',
            'customusername' => '',
            'custompassword' => '',
            'customfirstname' => '',
            'customlastname' => '',
            'customemail' => '',
            'customcity' => '',
            'customcountry' => ''
        );
        $expectederror = "[user.csv line 2] User could not be created. Required fields customusername, custompassword,";
        $expectederror .= " customfirstname, customlastname, customemail, customcity, customcountry are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Actually update using create.
        self::cleanup_log_files();
        $data = array(
            'action' => 'create',
            'customusername' => 'rlipusername',
            'customfirstname' => 'updatedrlipfirstname'
        );
        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'password' => hash_internal_user_password('Rlippassword!0'),
            'firstname' => 'updatedrlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validates that error logging works correctly with the course "create or update" functionality
     */
    public function test_version1importloggingsupportscoursecreateorupdate() {
        global $CFG, $DB;

        set_config('createorupdate', 1, 'rlipimport_version1');

        // Create mapping records.
        $this->create_mapping_record('course', 'shortname', 'customshortname');
        $this->create_mapping_record('course', 'fullname', 'customfullname');
        $this->create_mapping_record('course', 'category', 'customcategory');

        // Create a course so it can be updated.
        self::cleanup_log_files();
        $data = array(
            'action' => 'create',
            'customshortname' => 'rlipshortname',
            'customfullname' => 'rlipfullname',
            'customcategory' => 'rlipcategory'
        );
        $provider = new rlipimport_version1_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array(
            'shortname' => $data['customshortname'],
            'fullname' => $data['customfullname']
        );
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $this->assert_record_exists('course', $data);

        // Actually update using create.
        self::cleanup_log_files();
        $data = array('action' => 'create',
                      'customshortname' => 'rlipshortname',
                      'customfullname' => 'updatedrlipfullname');
        $provider = new rlipimport_version1_importprovider_fslogcourse($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        ob_start();
        $instance->run();
        ob_end_clean();

        $data = array(
            'shortname' => 'rlipshortname',
            'fullname' => 'updatedrlipfullname',
            'category' => $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'))
        );
        $this->assert_record_exists('course', $data);
    }

    /**
     * Validates success message for the user create action
     */
    public function test_version1importlogssuccessfulusercreate() {
        global $DB;

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully";
        $expectedmessage .= " created.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        $DB->delete_records('user', array('username' => 'rlipusername'));

        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectedmessage .= " \"rlipidnumber\" successfully created.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
    }

    /**
     * Validates success message for the user update action
     */
    public function test_version1importlogssuccesfuluserupdate() {
        $this->create_test_user();

        // Base data used every time.
        $basedata = array('action' => 'update');

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with idnumber \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully";
        $expectedmessage .= " updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectedmessage .= " \"rlipidnumber\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
    }

    /**
     * Validates success message for the user delete action
     */
    public function test_version1importlogssuccesfuluserdelete() {

        // Base data used every time.
        $basedata = array('action' => 'delete');

        // Username.
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Email.
        $this->create_test_user();
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Idnumber.
        $this->create_test_user();
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with idnumber \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, email.
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully";
        $expectedmessage .= " deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, idnumber.
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Email, idnumber.
        $this->create_test_user();
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Username, email, idnumber.
        $this->create_test_user();
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectedmessage .= " \"rlipidnumber\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
    }

    /**
     * Validates success message for the course create action
     */
    public function test_version1importlogssuccesfulcoursecreate() {
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $expectedmessage = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully created.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    /**
     * Validates success message for the course create action, when creating
     * from a template course
     */
    public function test_version1importlogssuccesfulcoursecreatefromtemplate() {
        global $USER;

        $USER->id = $this->create_test_user();
        set_config('siteadmins', $USER->id);
        set_config('siteguest', 99999);
        $this->create_test_course();

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname2',
            'fullname' => 'rlipfullname2',
            'category' => 'rlipcategory',
            'link' => 'rlipshortname'
        );
        $expectedmessage = "[course.csv line 2] Course with shortname \"rlipshortname2\" successfully created from template";
        $expectedmessage .= " course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    /**
     * Validates success message for the course update action
     */
    public function test_version1importlogssuccesfulcourseupdate() {
        $this->create_test_course();

        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname2'
        );
        $expectedmessage = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully updated.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    /**
     * Validates success message for the course delete action
     */
    public function test_version1importlogssuccesfulcoursedelete() {
        $this->create_test_course();

        $data = array(
            'action' => 'delete',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname2'
        );
        $expectedmessage = "[course.csv line 2] Course with shortname \"rlipshortname\" successfully deleted.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    /**
     * Validates success message for the role assignment create action on courses
     */
    public function test_version1importlogssuccesfulcourseroleassignmentcreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Prevent problem with cached contexts.
        accesslib_clear_all_caches(true);

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $syscontext = context_system::instance();
        $roleid = $this->create_test_role();

        set_config('siteguest', '');

        // Make sure we can enrol the test user.
        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        // Set up an enrolment but no role assignment.
        enrol_try_internal_enrol($courseid, $userid);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for course enrolment creation
     */
    public function test_version1importlogssuccesfulcourseenrolmentcreate() {
        global $DB;

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = context_course::instance($courseid);
        $roleid = $this->create_test_role();

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in";
        $expectedmessage .= " course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled";
        $expectedmessage .= " in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
    }

    /**
     * Validates success message for course enrolment and role assignment creation
     * (at the same time)
     */
    public function test_version1importlogssuccesfulcourseenrolmentandroleassignmentcreate() {
        global $DB;

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" enrolled in";
        $expectedmessage .= " course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" enrolled in ";
        $expectedmessage .= "course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " username \"rlipusername\", email \"rlipuser@rlipdomain.com\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username";
        $expectedmessage .= " \"rlipusername\", idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectedmessage .= " \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');
    }

    /**
     * Validates success message for assigning users to groups during course
     * enrolment creation
     */
    public function test_version1importlogssuccessfulgroupassignment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name ";
        $expectedmessage .= "\"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ";
        $expectedmessage .= "enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", ";
        $expectedmessage .= "email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in ";
        $expectedmessage .= "course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", idnumber ";
        $expectedmessage .= "\"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" ";
        $expectedmessage .= "enrolled in course with shortname \"rlipshortname\". Assigned user with email ";
        $expectedmessage .= "\"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectedmessage .= "idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with";
        $expectedmessage .= " username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group";
        $expectedmessage .= " with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');
    }

    /**
     * Validates success message for creating a group and assigning a user to
     * it during course enrolment creation
     */
    public function test_version1importlogssuccessfulgroupcreationandassignment() {
        global $DB;

        // Set up dependencies.
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username";
        $expectedmessage .= " \"rlipusername\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email";
        $expectedmessage .= " \"rlipuser@rlipdomain.com\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber";
        $expectedmessage .= " \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned";
        $expectedmessage .= " user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name";
        $expectedmessage .= " \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in";
        $expectedmessage .= " course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with";
        $expectedmessage .= " username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\".";
        $expectedmessage .= " Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name";
        $expectedmessage .= " \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with";
        $expectedmessage .= " name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
    }

    /**
     * Validates success message for creating a group and grouping, assigning
     * a user to the group and the group to the grouping during course
     * enrolment creation
     */
    public function test_version1importlogssuccessfulgroupandgroupingcreationandassignment() {
        global $DB;

        // Set up dependencies.
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname',
            'grouping' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username";
        $expectedmessage .= " \"rlipusername\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned";
        $expectedmessage .= " group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with";
        $expectedmessage .= " shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email";
        $expectedmessage .= " \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Created grouping with name";
        $expectedmessage .= " \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber";
        $expectedmessage .= " \"rlipidnumber\" to group with name \"rlipname\". Created grouping with name \"rlipname\". Assigned";
        $expectedmessage .= " group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\".";
        $expectedmessage .= " Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with name";
        $expectedmessage .= " \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to";
        $expectedmessage .= " grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in";
        $expectedmessage .= " course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with";
        $expectedmessage .= " username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Created";
        $expectedmessage .= " grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name";
        $expectedmessage .= " \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". ";
        $expectedmessage .= "Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with ";
        $expectedmessage .= "name \"rlipname\". Created grouping with name \"rlipname\". Assigned group with name \"rlipname\"";
        $expectedmessage .= " to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created";
        $expectedmessage .= " with name \"rlipname\". Assigned user with username \"rlipusername\", email";
        $expectedmessage .= " \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\".";
        $expectedmessage .= " Created grouping with name \"rlipname\". Assigned group with name \"rlipname\" to grouping";
        $expectedmessage .= " with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings');
        $DB->delete_records('groupings_groups');
    }

    /**
     * Validates success message for creating a group, assigning a user to the
     * group and the group to the grouping during course enrolment creation
     */
    public function test_version1importlogssuccessfulgroupcreationandgroupingassignment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Set up dependencies.
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $grouping = new stdClass;
        $grouping->courseid = $courseid;
        $grouping->name = 'rlipname';
        groups_create_grouping($grouping);

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname',
            'grouping' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with username";
        $expectedmessage .= " \"rlipusername\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to";
        $expectedmessage .= " grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with email";
        $expectedmessage .= " \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Assigned group with name \"rlipname\"";
        $expectedmessage .= " to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". Group created with name \"rlipname\". Assigned user with idnumber";
        $expectedmessage .= " \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\" to grouping";
        $expectedmessage .= " with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\".";
        $expectedmessage .= " Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" to group with";
        $expectedmessage .= " name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in";
        $expectedmessage .= " course with shortname \"rlipshortname\". Group created with name \"rlipname\". Assigned user with";
        $expectedmessage .= " username \"rlipusername\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned";
        $expectedmessage .= " group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". Group created with name \"rlipname\". ";
        $expectedmessage .= "Assigned user with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with";
        $expectedmessage .= " name \"rlipname\". Assigned group with name \"rlipname\" to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Group created with";
        $expectedmessage .= " name \"rlipname\". Assigned user with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" to group with name \"rlipname\". Assigned group with name \"rlipname\"";
        $expectedmessage .= " to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups');
        $DB->delete_records('groups_members');
        $DB->delete_records('groupings_groups');
    }

    /**
     * Validates success message for the role assignment create action on course categories
     */
    public function test_version1importlogssuccesfulcategoryroleassignmentcreate() {
        global $DB;

        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'coursecat',
            'instance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment create action on users
     */
    public function test_version1importlogssuccesfuluserroleassignmentcreate() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'user',
            'instance' => 'rlipusername2',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on user";
        $expectedmessage .= " \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment create action on the system context
     */
    public function test_version1importlogssuccessfulsystemroleassignmentcreate() {
        global $DB;

        $sitename = $this->getsitename();

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action'   => 'create',
            'context'  => 'system',
            'instance' => $sitename,
            'role'     => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on the system";
        $expectedmessage .= " context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validates success message for the role assignment delete action on courses
     */
    public function test_version1importlogssuccesfulcourseroleassignmentdelete() {
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'delete',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates success message for course enrolment deletion
     */
    public function test_version1importlogssuccesfulcourseenrolmentdelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        // Base data used every time.
        $basedata = array(
            'action' => 'delete',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" unenrolled from course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" unenrolled from course with";
        $expectedmessage .= " shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" unenrolled from course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" unenrolled";
        $expectedmessage .= " from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        $data = $basedata;
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates success message for course enrolment and role assignment
     * deletion (at the same time)
     */
    public function test_version1importlogssuccessfulcourseenrolmentandroleassignmentdelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        // Base data used every time.
        $basedata = array(
            'action' => 'delete',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Username.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\"";
        $expectedmessage .= " unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\"";
        $expectedmessage .= " unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User";
        $expectedmessage .= " with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" unenrolled from course with";
        $expectedmessage .= " shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username";
        $expectedmessage .= " \"rlipusername\", idnumber \"rlipidnumber\" unenrolled from course with shortname ";
        $expectedmessage .= "\"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course \"rlipshortname\". ";
        $expectedmessage .= "User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" unenrolled from course ";
        $expectedmessage .= "with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        $data = $basedata;
        role_assign($roleid, $userid, $context->id);
        enrol_try_internal_enrol($courseid, $userid);
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on";
        $expectedmessage .= " course \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" unenrolled from course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on course categories
     */
    public function test_version1importlogssuccesfulcategoryroleassignmentdelete() {
        global $DB;

        $userid = $this->create_test_user();
        $this->create_test_course();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipname'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'delete',
            'context' => 'coursecat',
            'instance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        // Username.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role";
        $expectedmessage .= " with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on ";
        $expectedmessage .= "course category \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on users
     */
    public function test_version1importlogssuccesfuluserroleassignmentdelete() {
        global $DB;

        // Set up dependencies.
        $userid = $this->create_test_user();
        $seconduserid = $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $context = get_context_instance(CONTEXT_USER, $seconduserid);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action' => 'delete',
            'context' => 'user',
            'instance' => 'rlipusername2',
            'role' => 'rlipshortname'
        );

        // Username.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned role";
        $expectedmessage .= " with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on user \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on user";
        $expectedmessage .= " \"rlipusername2\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates success message for the role assignment delete action on the system context
     */
    public function test_version1importlogssuccessfulsystemroleassignmentdelete() {
        global $DB;

        $sitename = $this->getsitename();

        // Set up dependencies.
        $userid = $this->create_test_user();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action'   => 'delete',
            'context'  => 'system',
            'instance' => $sitename,
            'role'     => 'rlipshortname'
        );

        // Username.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully unassigned";
        $expectedmessage .= " role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully";
        $expectedmessage .= " unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " successfully unassigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" successfully unassigned role with shortname \"rlipshortname\" on the";
        $expectedmessage .= " system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Data provider function for invalid user info in role assignments
     *
     * @return array The array of data to use in test cases
     */
    public function roleassignmentinvaliduserprovider() {
        $data = array();

        // Invalid username.
        $usernamedata = array('customusername' => 'bogus');
        $usernamemessage = "[enrolment.csv line 2] customusername value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($usernamedata, $usernamemessage);

        // Invalid email.
        $emaildata = array('customemail' => 'bogus@bogus.com');
        $emailmessage = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\" does not refer to a valid user.\n";
        $data[] = array($emaildata, $emailmessage);

        // Invalid idnumber.
        $idnumberdata = array('customidnumber' => 'bogus');
        $idnumbermessage = "[enrolment.csv line 2] customidnumber value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($idnumberdata, $idnumbermessage);

        // Invalid combination of username, email.
        $usernameemaildata = array('customusername' => 'bogus', 'customemail' => 'bogus@bogus.com');
        $usernameemailmessage = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of ";
        $usernameemailmessage .= "\"bogus@bogus.com\" do not refer to a valid user.\n";
        $data[] = array($usernameemaildata, $usernameemailmessage);

        // Invalid combination of username, idnumber.
        $usernameidnumberdata = array('customusername' => 'bogus', 'customidnumber' => 'bogus');
        $usernameidnumbermessage = "[enrolment.csv line 2] customusername value of \"bogus\", customidnumber value of \"bogus\"";
        $usernameidnumbermessage .= " do not refer to a valid user.\n";
        $data[] = array($usernameidnumberdata, $usernameidnumbermessage);

        // Invalid combination of email, idnumber.
        $emailidnumberdata = array('customemail' => 'bogus@bogus.com', 'customidnumber' => 'bogus');
        $emailidnumbermessage = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\", customidnumber value of ";
        $emailidnumbermessage .= "\"bogus\" do not refer to a valid user.\n";
        $data[] = array($emailidnumberdata, $emailidnumbermessage);

        // Invalid combination of username, email, idnumber.
        $allfieldsdata = array('customusername' => 'bogus', 'customemail' => 'bogus@bogus.com', 'customidnumber' => 'bogus');
        $allfieldsmessage = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\",";
        $allfieldsmessage .= " customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($allfieldsdata, $allfieldsmessage);

        return $data;
    }

    /**
     * Validates that invalid identifying user fields are logged during
     * enrolment and role assignment action on a course
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseroncourseenrolmentandroleassignmentcreate($data, $message) {
        // Set up dependencies.
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a course
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseroncourseroleassignmentcreate($data, $message) {
        // Set up dependencies.
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a course category
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseroncategoryroleassignmentcreate($data, $message) {
        global $DB;

        // Set up dependencies.
        $userid = $this->create_test_user();
        $this->create_test_course();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipname'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        $roleid = $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'coursecat',
            'instance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on a user
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseronuserroleassignmentcreate($data, $message) {
        // Set up dependencies.
        $seconduserid = $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');
        $context = get_context_instance(CONTEXT_USER, $seconduserid);
        $roleid = $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'user',
            'instance' => 'rlipusername2',
            'role' => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during role
     * assignment action on the system context
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseronsystemroleassignmentcreate($data, $message) {
        // Set up dependencies.
        $roleid = $this->create_test_role();
        $sitename = $this->getsitename();
        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action'   => 'create',
            'context'  => 'system',
            'instance' => $sitename,
            'role'     => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be created.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role on a context level where it
     * is not assignable
     */
    public function test_version1importlogsunassignablecontextonroleassignmentcreate() {
        // Set up dependencies.
        $this->create_test_user();
        // Create the role without enabling it at any context.
        create_role('rlipfullshortname', 'rlipshortname', 'rlipdescription');

        // Data.
        $data = array(
            'action'   => 'create',
            'username' => 'rlipusername',
            'context'  => 'system',
            'instance' => 'rlipusername',
            'role'     => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be created. The role with shortname \"rlipshortname\" is not";
        $message .= " assignable on the system context level.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role on a context level that
     * doesn't exist
     */
    public function test_version1importlogsinvalidcontextonroleassignmentcreate() {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        // Data.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'customcontext' => 'bogus',
            'instance' => 'bogus',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be created. customcontext value of \"bogus\" is not one of the";
        $message .= " available options (system, user, coursecat, course).\n";
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for assigning a role that doesn't exist
     */
    public function test_version1importlogsinvalidroleonroleassignmentcreate() {
        // Set up dependencies.
        $this->create_test_user();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        // Data.
        $data = array(
            'action'     => 'create',
            'username'   => 'rlipusername',
            'context'    => 'system',
            'instance'   => 'rlipusername',
            'customrole' => 'bogus'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be created. customrole value of \"bogus\" does not refer to a";
        $message .= " valid role.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate that approval messages still work when roles do not have the
     * standard Moodle "view course" capability
     */
    public function test_version1importlogssuccesswhenmissingcourseviewcapability() {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        // Data.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $message = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $message .= " \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\".";
        $message .= " User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" enrolled";
        $message .= " in course with shortname \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider method for logging invalid entity messages
     *
     * @return array An array containing information about the context
     *               "shortname" and display name
     */
    public function roleassignmentinvalidentityprovider() {
        return array(
                array('course', 'course'),
                array('coursecat', 'course category'),
                array('user', 'user')
        );
    }

    /**
     * Validates that invalid identifying entity fields are logged during role
     * assignment actions on various contexts
     *
     * @param string $context The string representing the context level
     * @param string $displayname The display name for the context level
     *
     * @dataProvider roleassignmentinvalidentityprovider
     */
    public function test_version1importlogsinvalidentityonroleassignmentcreate($context, $displayname) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Data.
        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => $context,
            'custominstance' => 'bogus',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be created. custominstance value of \"bogus\" does not refer to a";
        $message .= " valid instance of a {$displayname} context.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function test_version1importlogsambiguouscategorynameonroleassignmentcreate() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Create the category.
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        // Create a duplicate category.
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'coursecat',
            'custominstance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be created. custominstance value of \"rlipname\" refers to";
        $message .= " multiple course category contexts.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for invalid group name
     */
    public function test_version1importlogsinvalidgroupnameonroleassignmentcreate() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_course();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'group', 'customgroup');

        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'customgroup' => 'bogus'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname";
        $message .= " \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be enrolled";
        $message .= " in course with shortname \"rlipshortname\". customgroup value of \"bogus\" does not refer to a valid group";
        $message .= " in course with shortname \"rlipshortname\".\n";
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider function that providers all combinations of identifying
     * user fields
     *
     * @return array Data in format expected by phpunit
     */
    public function roleassignmentambiguousgroupnameuserprovider() {
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumber = 'rlipidnumber';

        return array(
                array(
                        array('username' => $username)
                ),
                array(
                        array('email' => $email)
                ),
                array(
                        array('idnumber' => $idnumber)
                ),
                array(
                        array(
                            'username' => $username,
                            'email' => $email
                        )
                ),
                array(
                        array(
                            'username' => $username,
                            'idnumber' => $idnumber
                        )
                ),
                array(
                        array(
                            'email' => $email,
                            'idnumber' => $idnumber
                        )
                ),
                array(
                        array(
                            'username' => $username,
                            'email' => $email,
                            'idnumber' => $idnumber
                        )
                )
        );
    }

    /**
     * Validate log message for ambiguous group name
     *
     * @param array $data Import data, consisting of identifying user fields
     *                    and values
     * @dataProvider roleassignmentambiguousgroupnameuserprovider
     */
    public function test_version1importlogsambiguousgroupnameonroleassignmentcreate($data) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Set up dependencies.
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'group', 'customgroup');

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'duplicate';
        groups_create_group($group);
        groups_create_group($group);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'customgroup' => 'duplicate'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname";
        $message .= " \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be";
        $message .= " enrolled in course with shortname \"rlipshortname\". customgroup value of \"duplicate\" refers to multiple";
        $message .= " groups in course with shortname \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for invalid grouping name
     */
    public function test_version1importlogsinvalidgroupingnameonroleassignmentcreate() {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // Set up dependencies.
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'grouping', 'customgrouping');

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname',
            'customgrouping' => 'bogus'
        );

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname";
        $message .= " \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be";
        $message .= " enrolled in course with shortname \"rlipshortname\". customgrouping value of \"bogus\" does not refer to a";
        $message .= " valid grouping in course with shortname \"rlipshortname\".\n";
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

     /**
      * Validate log message for ambiguous grouping name
      */
    public function test_version1importlogsambiguousgroupingnameonroleassignmentcreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        set_config('enrol_plugin_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        // Set up dependencies.
        $this->create_test_user();
        $courseid = $this->create_test_course();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'grouping', 'customgrouping');

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        groups_create_group($group);

        $grouping = new stdClass;
        $grouping->name = 'duplicate';
        $grouping->courseid = $courseid;
        groups_create_grouping($grouping);
        groups_create_grouping($grouping);

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname',
            'customgrouping' => 'duplicate'
        );

        $identifiers = $this->get_user_identifiers($data);
        $message = "[enrolment.csv line 2] User with ".implode(', ', $identifiers)." could not be assigned role with shortname";
        $message .= " \"rlipshortname\" on course \"rlipshortname\". User with ".implode(', ', $identifiers)." could not be ";
        $message .= "enrolled in course with shortname \"rlipshortname\". customgrouping value of \"duplicate\" refers to ";
        $message .= "multiple groupings in course with shortname \"rlipshortname\".\n";
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

     /**
      * Validate log message for assigning a user to a group they already
      * belong to
      */
    public function test_version1importlogsduplicategroupassignment() {
        global $DB;

        set_config('enrol_plugin_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        $group->id = groups_create_group($group);

        // Enrol the user in some secondary student role.
        $secondroleid = $this->create_test_role('secondfullname', 'secondshortname', 'seconddescription');
        enrol_try_internal_enrol($courseid, $userid, $secondroleid);
        // Assign the user to the group.
        groups_add_member($group->id, $userid);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" successfully assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is ";
        $expectedmessage .= "already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" successfully assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" successfully assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" is already ";
        $expectedmessage .= "assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ";
        $expectedmessage .= "successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ";
        $expectedmessage .= "username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned to group with name";
        $expectedmessage .= " \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" successfully ";
        $expectedmessage .= "assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username ";
        $expectedmessage .= "\"rlipusername\", idnumber \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" ";
        $expectedmessage .= "successfully assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned to group with ";
        $expectedmessage .= "name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectedmessage .= "idnumber \"rlipidnumber\" successfully assigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectedmessage .= " \"rlipidnumber\" is already assigned to group with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('role_assignments');
    }

    /**
     * Validate log message for assigning a group to a grouping is already
     * belongs to
     */
    public function test_version1importlogsduplicategroupingassignment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        $group = new stdClass;
        $group->courseid = $courseid;
        $group->name = 'rlipname';
        $group->id = groups_create_group($group);

        $grouping = new stdClass;
        $grouping->courseid = $courseid;
        $grouping->name = 'rlipname';
        $grouping->id = groups_create_grouping($grouping);

        groups_assign_grouping($grouping->id, $group->id);

        // Make sure they already have a role assignment.
        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname',
            'group' => 'rlipname',
            'grouping' => 'rlipname'
        );

        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with username \"rlipusername\" to group with name \"rlipname\". ";
        $expectedmessage .= "Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with email \"rlipuser@rlipdomain.com\" to group with name ";
        $expectedmessage .= "\"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" enrolled in course with shortname ";
        $expectedmessage .= "\"rlipshortname\". Assigned user with idnumber \"rlipidnumber\" to group with name \"rlipname\". ";
        $expectedmessage .= "Group with name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" ";
        $expectedmessage .= "enrolled in course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", ";
        $expectedmessage .= "email \"rlipuser@rlipdomain.com\" to group with name \"rlipname\". Group with name \"rlipname\" is ";
        $expectedmessage .= "already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" enrolled in ";
        $expectedmessage .= "course with shortname \"rlipshortname\". Assigned user with username \"rlipusername\", idnumber ";
        $expectedmessage .= "\"rlipidnumber\" to group with name \"rlipname\". Group with name \"rlipname\" is already assigned ";
        $expectedmessage .= "to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" ";
        $expectedmessage .= "enrolled in course with shortname \"rlipshortname\". Assigned user with email ";
        $expectedmessage .= "\"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group with name \"rlipname\". Group with ";
        $expectedmessage .= "name \"rlipname\" is already assigned to grouping with name \"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectedmessage .= "idnumber \"rlipidnumber\" enrolled in course with shortname \"rlipshortname\". Assigned user with ";
        $expectedmessage .= "username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" to group ";
        $expectedmessage .= "with name \"rlipname\". Group with name \"rlipname\" is already assigned to grouping with name ";
        $expectedmessage .= "\"rlipname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('groups_members');
    }

    /**
     * Validates a duplicate enrolment failure message
     */
    public function test_version1importlogsduplicateenrolmentfailuremessage() {
        global $DB;

        // Create guest user.
        $guestuser = get_test_user('guest');
        set_config('siteguest', $guestuser->id);

        // Create admin user.
        $adminuser = get_test_user('admin');
        set_config('siteadmins', $adminuser->id);

        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = $this->create_test_role();

        // Enable manual enrolments.
        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        $timestart = $DB->get_field('course', 'startdate', array('id' => $courseid));
        enrol_try_internal_enrol($courseid, $userid, null, $timestart);

        role_assign($roleid, $userid, $context->id);

        // Base data used every time.
        $basedata = array(
            'action' => 'create',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        // Username.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could ";
        $expectedmessage .= "not be enrolled in course with shortname \"rlipshortname\". User with username \"rlipusername\" is ";
        $expectedmessage .= "already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ";
        $expectedmessage .= "username \"rlipusername\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" could not be assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on course \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " could not be enrolled in course with shortname \"rlipshortname\". User with email ";
        $expectedmessage .= "\"rlipuser@rlipdomain.com\" is already assigned role with shortname \"rlipshortname\" on course ";
        $expectedmessage .= "\"rlipshortname\". User with email \"rlipuser@rlipdomain.com\" is already enrolled in course with ";
        $expectedmessage .= "shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" could not be assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on course \"rlipshortname\". User with idnumber \"rlipidnumber\" could not be";
        $expectedmessage .= " enrolled in course with shortname \"rlipshortname\". User with idnumber \"rlipidnumber\" is already";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with idnumber";
        $expectedmessage .= " \"rlipidnumber\" is already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could";
        $expectedmessage .= " not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be enrolled in course with";
        $expectedmessage .= " shortname \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $expectedmessage .= " is already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " username \"rlipusername\", email \"rlipuser@rlipdomain.com\" is already enrolled in course with";
        $expectedmessage .= " shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" could not be";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username";
        $expectedmessage .= " \"rlipusername\", idnumber \"rlipidnumber\" could not be enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\". User with username \"rlipusername\", idnumber \"rlipidnumber\" is already";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username";
        $expectedmessage .= " \"rlipusername\", idnumber \"rlipidnumber\" is already enrolled in course with shortname";
        $expectedmessage .= " \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could";
        $expectedmessage .= " not be assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with";
        $expectedmessage .= " email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" could not be enrolled in course ";
        $expectedmessage .= "with shortname \"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber ";
        $expectedmessage .= "\"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on course ";
        $expectedmessage .= "\"rlipshortname\". User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is ";
        $expectedmessage .= "already enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectedmessage .= "idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on course";
        $expectedmessage .= " \"rlipshortname\". User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", ";
        $expectedmessage .= "idnumber \"rlipidnumber\" could not be enrolled in course with shortname \"rlipshortname\". User ";
        $expectedmessage .= "with username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is ";
        $expectedmessage .= "already assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with ";
        $expectedmessage .= "username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already ";
        $expectedmessage .= "enrolled in course with shortname \"rlipshortname\".\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates a duplicate role assignment failure message
     */
    public function test_version1importlogsduplicateroleassignmentfailuremessage() {
        global $DB;

        $sitename = $this->getsitename();

        // Create guest user.
        $guestuser = get_test_user('guest');
        set_config('siteguest', $guestuser->id);

        // Create admin user.
        $adminuser = get_test_user('admin');
        set_config('siteadmins', $adminuser->id);

        $userid = $this->create_test_user();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->create_test_role();

        // Base data used every time.
        $basedata = array(
            'action'   => 'create',
            'context'  => 'system',
            'instance' => $sitename,
            'role'     => 'rlipshortname',
        );

        // Username.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is already ";
        $expectedmessage .= "assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\" could not be assigned role with ";
        $expectedmessage .= "shortname \"rlipshortname\" on the system context. User with email \"rlipuser@rlipdomain.com\" is";
        $expectedmessage .= " already assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with idnumber \"rlipidnumber\" could not be assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on the system context. User with idnumber \"rlipidnumber\" is already assigned ";
        $expectedmessage .= "role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could";
        $expectedmessage .= " not be assigned role with shortname \"rlipshortname\" on the system context. User with username ";
        $expectedmessage .= "\"rlipusername\", email \"rlipuser@rlipdomain.com\" is already assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", idnumber \"rlipidnumber\" could not be";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on the system context. User with username ";
        $expectedmessage .= "\"rlipusername\", idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\"";
        $expectedmessage .= " on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $expectedmessage .= " could not be assigned role with shortname \"rlipshortname\" on the system context. User with";
        $expectedmessage .= " email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\" is already assigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        // Username, email, idnumber.
        role_assign($roleid, $userid, $context->id);
        $data = $basedata;
        $data['username'] = 'rlipusername';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" could not be assigned role with shortname \"rlipshortname\" on the";
        $expectedmessage .= " system context. User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\",";
        $expectedmessage .= " idnumber \"rlipidnumber\" is already assigned role with shortname \"rlipshortname\" on the";
        $expectedmessage .= " system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validates that invalid identifying user fields are logged during
     * role unassignment on system context
     *
     * @param array $data Additional data to feed to the import
     * @param array $message The error message to expect in the log
     *
     * @dataProvider roleassignmentinvaliduserprovider
     */
    public function test_version1importlogsinvaliduseronsystemroleassignmentdelete($data, $message) {
        // Set up dependencies.
        $roleid = $this->create_test_role();
        $sitename = $this->getsitename();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'username', 'customusername');
        $this->create_mapping_record('enrolment', 'email', 'customemail');
        $this->create_mapping_record('enrolment', 'idnumber', 'customidnumber');

        // Base data used every time.
        $basedata = array(
            'action'   => 'delete',
            'context'  => 'system',
            'instance' => $sitename,
            'role'     => 'rlipshortname'
        );

        // Set up the exact data we need.
        $data = array_merge($basedata, $data);

        // Add general message to expected log message.
        $pos = strrpos($message, ']');
        $prefix = substr($message, 0, $pos + 2);
        $generalmessage = 'Enrolment could not be deleted.';
        $suffix = substr($message, $pos + 1);
        $message = $prefix.$generalmessage.$suffix;
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for unassigning a role from a context level that
     * doesn't exist
     */
    public function test_version1importlogsinvalidcontextonroleassignmentdelete() {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'context', 'customcontext');

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'customcontext' => 'bogus',
            'instance' => 'bogus',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. customcontext value of \"bogus\" is not one of the ";
        $message .= "available options (system, user, coursecat, course).\n";
        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates log message for unassigning a role that doesn't exist
     */
    public function test_version1importlogsinvalidroleonroleassignmentdelete() {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping records.
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        // Data.
        $data = array(
            'action'     => 'delete',
            'username'   => 'rlipusername',
            'context'    => 'system',
            'instance'   => 'system',
            'customrole' => 'bogus'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. customrole value of \"bogus\" does not refer to a";
        $message .= " valid role.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Data provider method, providing identifying user fields as well as
     * text to describe those fields, with every combination of identifying
     * user fields provided
     *
     * @return array Array of data, with each elements containing a data set
     *               and a descriptive string
     */
    public function userdescriptorprovider() {
        $data = array();

        // Username.
        $usernamedata = array('username' => 'rlipusername');
        $usernamedescriptor = "username \"rlipusername\"";
        $data[] = array($usernamedata, $usernamedescriptor);

        // Email.
        $emaildata = array('email' => 'rlipuser@rlipdomain.com');
        $emaildescriptor = "email \"rlipuser@rlipdomain.com\"";
        $data[] = array($emaildata, $emaildescriptor);

        // Idnumber.
        $idnumberdata = array('idnumber' => 'rlipidnumber');
        $idnumberdescriptor = "idnumber \"rlipidnumber\"";
        $data[] = array($idnumberdata, $idnumberdescriptor);

        // Username, email.
        $usernameemaildata = array('username' => 'rlipusername', 'email' => 'rlipuser@rlipdomain.com');
        $usernameemaildescriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $data[] = array($usernameemaildata, $usernameemaildescriptor);

        // Username, idnumber.
        $usernameidnumberdata = array('username' => 'rlipusername', 'idnumber' => 'rlipidnumber');
        $usernameidnumberdescriptor = "username \"rlipusername\", idnumber \"rlipidnumber\"";
        $data[] = array($usernameidnumberdata, $usernameidnumberdescriptor);

        // Email, idnumber.
        $emailidnumberdata = array('email' => 'rlipuser@rlipdomain.com', 'idnumber' => 'rlipidnumber');
        $emailidnumberdescriptor = "email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($emailidnumberdata, $emailidnumberdescriptor);

        // Username, email, idnumber.
        $allfieldsdata = array('username' => 'bogus', 'email' => 'bogus@bogus.com', 'idnumber' => 'bogus');
        $allfieldsdescriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($allfieldsdata, $allfieldsdescriptor);

        return $data;
    }

    /**
     * Validates that deletion of nonexistent enrolments are logged
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userdescriptorprovider
     */
    public function test_version1importlogsnonexistentenrolmentdelete($data, $descriptor) {
        // Set up dependencies.
        $this->create_test_user();
        $roleid = $this->create_test_role();
        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname";
        $message .= " \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be";
        $message .= " unenrolled from course with shortname \"rlipshortname\". User with username \"rlipusername\" is";
        $message .= " not assigned role with shortname \"rlipshortname\" on course \"rlipshortname\". User with username";
        $message .= " \"rlipusername\" is not enrolled in course with shortname \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role enrolments are logged for
     * courses
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userdescriptorprovider
     */
    public function test_version1importlogsnonexistentroleassignmentoncourseroleassignmentdelete($data, $descriptor) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();
        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname ";
        $message .= "\"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" could not be unenrolled";
        $message .= " from course with shortname \"rlipshortname\". User with username \"rlipusername\" is not assigned role with";
        $message .= " shortname \"rlipshortname\" on course \"rlipshortname\". User with username \"rlipusername\" is not enrolled";
        $message .= " in course with shortname \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that, for deletion of a course role that is not assigned, an error is logged
     * instead of deleting the user's enrolment if they have some other role assignment on
     * the coruse
     */
    public function test_version1importlogsdeletionofenrolmentwhenrolesassignmentsexist() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Set up dependencies.
        $userid = $this->create_test_user();
        $unassignedroleid = $this->create_test_role('unassigned', 'unassigned', 'unassigned');
        $assignedroleid = $this->create_test_role('assigned', 'assigned', 'assigned');
        $courseid = $this->create_test_course();

        set_config('siteguest', '');

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        // Set up a role assignment and an enrolment.
        enrol_try_internal_enrol($courseid, $userid, $assignedroleid);

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'unassigned'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname";
        $message .= " \"unassigned\" on course \"rlipshortname\". User with username \"rlipusername\" could not be unenrolled";
        $message .= " from course with shortname \"rlipshortname\". User with username \"rlipusername\" is not assigned role";
        $message .= " with shortname \"unassigned\" on course \"rlipshortname\". User with username \"rlipusername\" requires";
        $message .= " their enrolment to be maintained because they have another role assignment in this course.\n";
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * course categories
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userdescriptorprovider
     */
    public function test_version1importlogsnonexistentroleassignmentoncategoryroleassignmentdelete($data, $descriptor) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();
        // Also creates test category.
        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'coursecat',
            'instance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname";
        $message .= " \"rlipshortname\" on course category \"rlipname\". User with username \"rlipusername\" is not assigned";
        $message .= " role with shortname \"rlipshortname\" on course category \"rlipname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * users
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userdescriptorprovider
     */
    public function test_version1importlogsnonexistentroleassignmentonuserroleassignmentdelete($data, $descriptor) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();
        $this->create_test_user('rlipusername2', 'rlipuser@rlipdomain2.com', 'rlipidnumber2');

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'user',
            'instance' => 'rlipusername2',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname";
        $message .= " \"rlipshortname\" on user \"rlipusername2\". User with username \"rlipusername\" is not assigned role with";
        $message .= " shortname \"rlipshortname\" on user \"rlipusername2\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function test_version1importlogsambiguouscategorynameonroleassignmentdelete() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Create the category.
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        // Create a duplicate category.
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'coursecat',
            'custominstance' => 'rlipname',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. custominstance value of \"rlipname\" refers to";
        $message .= " multiple course category contexts.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that deletion of nonexistent role assignments are logged for
     * the system context
     *
     * @param array $data The user-specific information
     * @param string $descriptor Descriptor for user fields to use in message
     *
     * @dataProvider userdescriptorprovider
     */
    public function test_version1importlogsnonexistentroleassignmentonsystemroleassignmentdelete($data, $descriptor) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Data.
        $data = array(
            'action'   => 'delete',
            'username' => 'rlipusername',
            'context'  => 'system',
            'instance' => 'system',
            'role'     => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with shortname";
        $message .= " \"rlipshortname\" on the system context. User with username \"rlipusername\" is not assigned role with";
        $message .= " shortname \"rlipshortname\" on the system context.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validates that invalid identifying entity fields are logged during role
     * assignment actions on various contexts
     *
     * @param string $context The string representing the context level
     * @param string $displayname The display name for the context level
     *
     * @dataProvider roleassignmentinvalidentityprovider
     */
    public function test_version1importlogsinvalidentityonroleassignmentdelete($context, $displayname) {
        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'instance', 'custominstance');

        // Data.
        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => $context,
            'custominstance' => 'bogus',
            'role' => 'rlipshortname'
        );

        $message = "[enrolment.csv line 2] Enrolment could not be deleted. custominstance value of \"bogus\" does not refer to";
        $message .= " a valid instance of a {$displayname} context.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'enrolment');
    }

    /**
     * Validate log message for ambiguous category name
     */
    public function test_version1importlogsambiguouscategorynameoncoursecreate() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('course', 'category', 'customcategory');

        // Create the category.
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        // Create a duplicate category.
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipcoursename',
            'fullname' => 'rlipcoursename',
            'customcategory' => 'rlipname'
        );

        $message = "[course.csv line 2] Course with shortname \"rlipcoursename\" could not be created. customcategory value of";
        $message .= " \"rlipname\" refers to multiple categories.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate log message for ambiguous parent category name as part of
     * a category path
     */
    public function test_version1importlogsambiguousparentcategorynameoncoursecreate() {
        global $DB;

        // Set up dependencies.
        $this->create_test_user();
        $this->create_test_role();

        // Create mapping record.
        $this->create_mapping_record('course', 'category', 'customcategory');

        // Create the category.
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        // Create a duplicate category.
        $categoryid = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $categoryid);

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipcoursename',
            'fullname' => 'rlipcoursename',
            'customcategory' => 'rlipname/rlipchildname'
        );

        $message = "[course.csv line 2] Course with shortname \"rlipcoursename\" could not be created. customcategory value of";
        $message .= " \"rlipname/rlipchildname\" refers to an ambiguous parent category path.\n";

        // Validation.
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that auth validation works on user create
     */
    public function test_version1importlogsinvalidauthonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'auth', 'customauth');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customauth' => 'bogus'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customauth value of \"bogus\" is not a valid auth plugin.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that username validation works on user create
     */
    public function test_version1importlogsinvalidusernameonusercreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        // Data setup.
        $this->load_csv_data();
        // Make sure the user belongs to "localhost".
        $user = new stdClass;
        $user->id = 3;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $DB->update_record('user', $user);

        $data = array(
            'action' => 'create',
            'customusername' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. customusername value of \"testusername\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that email validation works on user create
     */
    public function test_version1importlogsinvalidemailonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $data = array(
            'action'      => 'create',
            'username'    => 'testusername',
            'password'    => 'Rlippassword!0',
            'firstname'   => 'rlipfirstname',
            'lastname'    => 'rliplastname',
            'customemail' => 'bogusemail',
            'city'        => 'Waterloo',
            'country'     => 'CA'
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"bogusemail\" could not be created.";
        $expectederror .= " customemail value of \"bogusemail\" is not a valid email address.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $this->load_csv_data();
        $data['username']    = 'validusername';
        $data['customemail'] = 'test@user.com';
        $expectederror = "[user.csv line 2] User with username \"validusername\", email \"test@user.com\" could not be ";
        $expectederror .= "created. customemail value of \"test@user.com\" refers to a user that already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        set_config('allowduplicateemails', '1', 'rlipimport_version1');

        $expectederror = "[user.csv line 2] User with username \"validusername\", email \"test@user.com\" successfully created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that idnumber validation works on user create
     */
    public function test_version1importlogsinvalididnumberonusercreate() {
        $this->load_csv_data();

        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'create',
            'username' => 'uniqueusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customidnumber' => 'idnumber'
        );

        $expectederror = "[user.csv line 2] User with username \"uniqueusername\", email \"rlipuser@rlipdomain.com\", idnumber";
        $expectederror .= " \"idnumber\" could not be created. customidnumber value of \"idnumber\" refers to a user that";
        $expectederror .= " already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that password validation works on user create
     */
    public function test_version1importlogsinvalidpasswordonusercreate() {
        set_config('passwordpolicy', 1);
        set_config('minpassworddigits', 1);

        // Create mapping record.
        $this->create_mapping_record('user', 'password', 'custompassword');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'custompassword' => 'invalidpassword',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'idnumber' => 'idnumber'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be created. custompassword value of \"invalidpassword\" does not conform to your site's";
        $expectederror .= " password policy.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that maildigest validation works on user create
     */
    public function test_version1importlogsinvalidmaildigestonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'maildigest', 'custommaildigest');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'custommaildigest' => '3'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. custommaildigest value of \"3\" is not one of the available options (0, 1, 2).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that autosubscribe validation works on user create
     */
    public function test_version1importlogsinvalidautosubscribeonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'autosubscribe', 'customautosubscribe');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customautosubscribe' => '2'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customautosubscribe value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that trackforums validation works on user create
     */
    public function test_version1importlogsinvalidtrackforumsonusercreate() {
        set_config('forum_trackreadposts', 0);

        // Create mapping record.
        $this->create_mapping_record('user', 'trackforums', 'customtrackforums');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customtrackforums' => '1'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. Tracking unread posts is currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        set_config('forum_trackreadposts', 1);
        $data['customtrackforums'] = 2;
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customtrackforums value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that screenreader validation works on user create
     */
    public function test_version1importlogsinvalidscreenreaderonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'screenreader', 'customscreenreader');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customscreenreader' => '2'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customscreenreader value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that country validation works on user create
     */
    public function test_version1importlogsinvalidcountryonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'customcountry' => 'bogus'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customcountry value of \"bogus\" is not a valid country or country code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that timezone validation works on user create
     */
    public function test_version1importlogsinvalidtimezoneonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

        set_config('forcetimezone', '99');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customtimezone' => 'bogus'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customtimezone value of \"bogus\" is not a valid timezone.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        set_config('forcetimezone', '-5.0');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customtimezone' => '-4.0'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customtimezone value of \"-4.0\" is not consistent with forced timezone value of \"-5.0\"";
        $expectederror .= " on your site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that theme validation works on user create
     */
    public function test_version1importlogsinvalidthemeonusercreate() {
        set_config('allowuserthemes', 0);

        // Create mapping record.
        $this->create_mapping_record('user', 'theme', 'customtheme');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customtheme' => 'bartik'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. User themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        set_config('allowuserthemes', 1);
        $data['customtheme'] = 'bogus';
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customtheme value of \"bogus\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that lang validation works on user create
     */
    public function test_version1importlogsinvalidlangonusercreate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'lang', 'customlang');

        $data = array(
            'action' => 'create',
            'username' => 'testusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customlang' => 'bogus'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " created. customlang value of \"bogus\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
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
        require_once($CFG->dirroot.'/user/profile/definelib.php');
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
     * Validate that profile field validation works on user create
     */
    public function test_version1importlogsinvalidprofilefielddataonusercreate() {
        // Create category and custom fields.
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('checkbox', 'checkbox', $categoryid);
        $this->create_profile_field('menu', 'menu', $categoryid, 'option1');
        $this->create_profile_field('date', 'datetime', $categoryid);

        // Create mapping records.
        $this->create_mapping_record('user', 'profile_field_checkbox', 'customprofile_field_checkbox');
        $this->create_mapping_record('user', 'profile_field_menu', 'customprofile_field_menu');
        $this->create_mapping_record('user', 'profile_field_date', 'customprofile_field_date');

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'RLippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'Waterloo',
            'country' => 'CA',
            'customprofile_field_checkbox' => 2
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. \"2\" is not one of the available options for a checkbox profile field checkbox (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        unset($data['customprofile_field_checkbox']);
        $data['customprofile_field_menu'] = 'option2';
        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. \"option2\" is not one of the available options for a menu of choices profile field menu.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        unset($data['customprofile_field_menu']);
        $data['customprofile_field_date'] = 'bogus';
        $expectederror = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" could not be";
        $expectederror .= " created. customprofile_field_date value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY";
        $expectederror .= " format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    protected function load_csv_data() {
        global $CFG;
        $dataset = $this->createCsvDataSet(array(
            'user' => dirname(__FILE__).'/fixtures/usertable.csv',
            'user_info_field' => dirname(__FILE__).'/fixtures/user_info_field.csv'
        ));
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        $dataset->addFullReplacement('##MNET_LOCALHOST_ID##', $CFG->mnet_localhost_id);
        $this->loadDataSet($dataset);
    }

    public function test_version1importlogsupdateemail() {
        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();
        $data = array(
            'action'      => 'update',
            'username'    => 'testusername',
            'customemail' => 'testinvalid@user.com',
            'city'        => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " updated. customemail value of \"testinvalid@user.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsdeleteemail() {
        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();
        $data = array(
            'action' => 'delete',
            'username' => 'testusername',
            'customemail' => 'testinvalid@user.com',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " deleted. customemail value of \"testinvalid@user.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatemaildigest() {
        // Create mapping record.
        $this->create_mapping_record('user', 'maildigest', 'custommaildigest');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'custommaildigest' => 3
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " updated. custommaildigest value of \"3\" is not one of the available options (0, 1, 2).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdateautosubscribe() {
        // Create mapping record.
        $this->create_mapping_record('user', 'autosubscribe', 'customautosubscribe');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'testinvalid@user.com',
            'city' => 'Waterloo',
            'maildigest' => 2,
            'customautosubscribe' => 2
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"testinvalid@user.com\" could not be";
        $expectederror .= " updated. customautosubscribe value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatetrackingdisabled() {
        $this->load_csv_data();
        set_config('forum_trackreadposts', 0);
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 0
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated.";
        $expectederror .= " Tracking unread posts is currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatetracking() {
        // Create mapping record.
        $this->create_mapping_record('user', 'trackforums', 'customtrackforums');

        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'customtrackforums' => 2
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated.";
        $expectederror .= " customtrackforums value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatescreenreader() {
        // Create mapping record.
        $this->create_mapping_record('user', 'screenreader', 'customscreenreader');

        $this->load_csv_data();
        set_config('forum_trackreadposts', 1);
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'customscreenreader' => 2
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\" could not be updated.";
        $expectederror .= " customscreenreader value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdateusername() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'customusername' => 'invalidusername',
            'email' => 'test@user.com',
            'idnumber' => 'idnumber',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"invalidusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. customusername value of \"invalidusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsdeleteusername() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();
        $data = array(
            'action' => 'delete',
            'customusername' => 'invalidusername',
            'email' => 'test@user.com',
            'idnumber' => 'idnumber',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"invalidusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be deleted. customusername value of \"invalidusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdateidnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'customidnumber' => 'invalidid',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"invalidid\"";
        $expectederror .= " could not be updated. customidnumber value of \"invalidid\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsdeleteidnumber() {
        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $this->load_csv_data();
        $data = array(
            'action' => 'delete',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'customidnumber' => 'invalidid',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"invalidid\"";
        $expectederror .= " could not be deleted. customidnumber value of \"invalidid\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdateauth() {
        // Create mapping record.
        $this->create_mapping_record('user', 'auth', 'customauth');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'idnumber' => 'idnumber',
            'password' => '1234567',
            'city' => 'Waterloo',
            'customauth' => 'invalidauth'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. customauth value of \"invalidauth\" is not a valid auth plugin.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatepassword() {
        // Create mapping record.
        $this->create_mapping_record('user', 'password', 'custompassword');

        set_config('minpasswordlower', 1);

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'email' => 'test@user.com',
            'idnumber' => 'idnumber',
            'custompassword' => '1234567',
            'city' => 'Waterloo'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. custompassword value of \"1234567\" does not conform to your site's password ";
        $expectederror .= "policy.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatelang() {
        // Create mapping record.
        $this->create_mapping_record('user', 'lang', 'customlang');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'customlang' => 'invalidlang'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\" could not be updated. customlang value of";
        $expectederror .= " \"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatecountry() {
        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'lang' => 'en',
            'customcountry' => 'invalidcountry'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\" could not be updated. customcountry value of";
        $expectederror .= " \"invalidcountry\" is not a valid country or country code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsthemedisabled() {
        $this->load_csv_data();
        set_config('allowuserthemes', 0);
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'email' => 'test@user.com',
            'lang' => 'en',
            'idnumber' => 'idnumber',
            'country' => 'CA',
            'theme' => 'invalidtheme',
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. User themes are currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsupdatetheme() {
        // Create mapping record.
        $this->create_mapping_record('user', 'theme', 'customtheme');

        $this->load_csv_data();
        set_config('allowuserthemes', 1);
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'email' => 'test@user.com',
            'lang' => 'en',
            'idnumber' => 'idnumber',
            'country' => 'CA',
            'customtheme' => 'invalidtheme',
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. customtheme value of \"invalidtheme\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsforcetimezone() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

        $this->load_csv_data();
        $CFG->forcetimezone = 97;
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'email' => 'test@user.com',
            'lang' => 'en',
            'idnumber' => 'idnumber',
            'country' => 'CA',
            'theme' => 'invalidtheme',
            'customtimezone' => 98,
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. customtimezone value of \"98\" is not consistent with forced timezone value";
        $expectederror .= " of \"{$CFG->forcetimezone}\" on your site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_version1importlogsinvalidtimezone() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('user', 'timezone', 'customtimezone');

        $this->load_csv_data();
        $CFG->forcetimezone = 99;
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'email' => 'test@user.com',
            'lang' => 'en',
            'idnumber' => 'idnumber',
            'country' => 'CA',
            'customtimezone' => 'invalidtimezone',
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber \"idnumber\"";
        $expectederror .= " could not be updated. customtimezone value of \"invalidtimezone\" is not a valid timezone.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that profile field validation works on user update
     */
    public function test_version1importlogsinvalidprofilefielddataonuserupdate() {
        // Create category and custom fields.
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('checkbox', 'checkbox', $categoryid);
        $this->create_profile_field('menu', 'menu', $categoryid, 'option1');
        $this->create_profile_field('date', 'datetime', $categoryid);

        // Create mapping record.
        $this->create_mapping_record('user', 'profile_field_checkbox', 'customprofile_field_checkbox');
        $this->create_mapping_record('user', 'profile_field_menu', 'customprofile_field_menu');
        $this->create_mapping_record('user', 'profile_field_date', 'customprofile_field_date');

        // Setup.
        $this->create_test_user();

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'customprofile_field_checkbox' => 2
        );

        $expectederror = "[user.csv line 2] User with username \"rlipusername\" could not be updated. \"2\" is not one of the";
        $expectederror .= " available options for a checkbox profile field checkbox (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        unset($data['customprofile_field_checkbox']);
        $data['customprofile_field_menu'] = 'option2';
        $expectederror = "[user.csv line 2] User with username \"rlipusername\" could not be updated. \"option2\" is not one of";
        $expectederror .= " the available options for a menu of choices profile field menu.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        unset($data['customprofile_field_menu']);
        $data['customprofile_field_date'] = 'bogus';
        $expectederror = "[user.csv line 2] User with username \"rlipusername\" could not be updated. customprofile_field_date";
        $expectederror .= " value of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate log message for duplicate course shortname when creating a course
     */
    public function test_version1importlogsinvalidshortnameoncoursecreate() {
        global $CFG, $DB;

        $this->create_test_course();

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. shortname value of";
        $expectederror .= " \"rlipshortname\" refers to a course that already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that format validation works on course create
     */
    public function test_version1importlogsinvalidformatoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'format', 'customformat');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customformat' => 'bogus'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customformat value of";
        $expectederror .= " \"bogus\" does not refer to a valid course format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that numsections validation works on course create
     */
    public function test_version1importlogsinvalidnumsectionsoncoursecreate() {
        set_config('maxsections', 10, 'moodlecourse');

        // Create mapping record.
        $this->create_mapping_record('course', 'numsections', 'customnumsections');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customnumsections' => '11'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customnumsections";
        $expectederror .= " value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that startdate validation works on course create
     */
    public function test_version1importlogsinvalidstartdateoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customstartdate' => 'bogus'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customstartdate value";
        $expectederror .= " of \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that newsitems validation works on course create
     */
    public function test_version1importlogsinvalidnewsitemsoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'newsitems', 'customnewsitems');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customnewsitems' => '11'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customnewsitems";
        $expectederror .= " value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that showgrades validation works on course create
     */
    public function test_version1importlogsinvalidshowgradesoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'showgrades', 'customshowgrades');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customshowgrades' => '2'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customshowgrades";
        $expectederror .= " value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that showreports validation works on course create
     */
    public function test_version1importlogsinvalidshowreportsoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'showreports', 'customshowreports');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customshowreports' => '2'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customshowreports";
        $expectederror .= " value of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that maxbytes validation works on course create
     */
    public function test_version1importlogsinvalidmaxbytesoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'maxbytes', 'custommaxbytes');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'custommaxbytes' => 'bogus'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. custommaxbytes";
        $expectederror .= " value of \"bogus\" is not one of the available options.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that guest validation works on course create
     */
    public function test_version1importlogsinvalidguestoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'guest', 'customguest');

        set_config('enrol_plugins_enabled', 'guest');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customguest' => '2'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customguest value";
        $expectederror .= " of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for assigning password with guest enrolments
     * globally disabled
     */
    public function test_version1importlogspasswordsetwithguestdisabledoncoursecreate() {
        // Setup.
        set_config('enrol_plugins_enabled', 'guest');

        // Data.
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'guest' => '0',
            'password' => 'rlippassword'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. guest enrolment";
        $expectederror .= " plugin cannot be assigned a password because the guest enrolment plugin is not enabled.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for assigning a password with guest enrolments
     * not being added to new courses by default
     */
    public function test_version1importlogspasswordsetwithguestunsetoncoursecreate() {
        // Setup.
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        // Data.
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'password' => 'rlippassword'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. guest enrolment";
        $expectederror .= " plugin cannot be assigned a password because the guest enrolment plugin is not configured to be";
        $expectederror .= " added to new courses by default.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that visible validation works on course create
     */
    public function test_version1importlogsinvalidvisibleoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'visible', 'customvisible');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customvisible' => '2'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customvisible value";
        $expectederror .= " of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that lang validation works on course create
     */
    public function test_version1importlogsinvalidlangoncoursecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'lang', 'customlang');

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'customlang' => 'bogus'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. customlang value";
        $expectederror .= " of \"bogus\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that link (template course) validation works on course create
     */
    public function test_version1importlogsinvalidlinkoncoursecreate() {
        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipname',
            'category' => 'rlipcategory',
            'link' => 'bogus'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be created. Template course with";
        $expectederror .= " shortname \"bogus\" could not be found.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that shortname validation works on course update
     */
    public function test_version1importlogsinvalidshortnameoncourseupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'update', 'customshortname' => 'rlipshortname');

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. customshortname";
        $expectederror .= " value of \"rlipshortname\" does not refer to a valid course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdateformat() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'format', 'customformat');

        $this->load_csv_data();
        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'password' => 'm0ddl3.paSs',
            'email' => 'test@user.com',
            'lang' => 'en',
            'idnumber' => 'idnumber',
            'country' => 'CA',
            'timezone' => 'invalidtimezone',
            'shortname' => 'cm2',
            'customformat' => 'invalidformat'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customformat value of";
        $expectederror .= " \"invalidformat\" does not refer to a valid course format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatenumsections() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'numsections', 'customnumsections');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $invalidmaxsections = $maxsections + 1;
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'customnumsections' => $invalidmaxsections
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customnumsections value";
        $expectederror .= " of \"{$invalidmaxsections}\" is not one of the available options (0 .. {$maxsections}).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatestartdate() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'customstartdate' => 'bogus'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customstartdate value of";
        $expectederror .= " \"bogus\" is not a valid date in MMM/DD/YYYY or MM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatenewsitems() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'newsitems', 'customnewsitems');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'customnewsitems' => 100
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customnewsitems value of";
        $expectederror .= " \"100\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdateshowgrades() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'showgrades', 'customshowgrades');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'customshowgrades' => 3
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customshowgrades value of";
        $expectederror .= " \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdateshowreports() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'showreports', 'customshowreports');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'customshowreports' => 3
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customshowreports value of";
        $expectederror .= " \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatemaxbytes() {
        global $CFG;

        // Create mapping record.
        $this->create_mapping_record('course', 'maxbytes', 'custommaxbytes');

        $this->load_csv_data();
        set_config('maxsections', 20, 'moodlecourse');
        $maxsections = (int)get_config('moodlecourse', 'maxsections');
        set_config('maxbytes', 100000000, 'moodlecourse');
        $maxbytes = get_config('moodlecourse', 'maxbytes');
        $invalidmaxbytes = $maxbytes + 1;
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'showreports' => 0,
            'custommaxbytes' => $invalidmaxbytes
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. custommaxbytes value of";
        $expectederror .= " \"{$invalidmaxbytes}\" is not one of the available options.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdateguest() {
        global $CFG;
        $this->load_csv_data();
        $this->create_test_course();

        // Create mapping record.
        $this->create_mapping_record('course', 'guest', 'customguest');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'showreports' => 0,
            'maxbytes' => $maxbytes,
            'customguest' => 'invalidguest'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. customguest value";
        $expectederror .= " of \"invalidguest\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for attempting to set the guest flag with the guest enrolment
     * plugin globally disabled
     */
    public function test_version1importlogsguestwithguestdisabledoncourseupdate() {
        global $CFG;
        // Setup.
        $this->load_csv_data();

        set_config('enrol_plugins_enabled', '');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        // Data.
        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'showreports' => 0,
            'maxbytes' => $maxbytes,
            'guest' => 1,
            'visible' => 1,
            'lang' => 'en',
        );
        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. guest enrolments cannot be";
        $expectederror .= " enabled because the guest enrolment plugin is globally disabled.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for attempting to set the guest flag with the guest enrolment
     * plugin removed from the course
     */
    public function test_version1importlogsguestwithguestunsetoncourseupdate() {
        // Setup.
        set_config('enrol_plugins_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'guest' => 1
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment";
        $expectederror .= " plugin cannot be enabled because the guest enrolment plugin has been removed from course";
        $expectederror .= " \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for attempting to set the guest password with the guest enrolment
     * plugin removed from the course
     */
    public function test_version1importlogspasswordsetwithguestunsetoncourseupdate() {
        // Setup.
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 0, 'enrol_guest');

        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'password' => 'rlippassword'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment";
        $expectederror .= " plugin cannot be assigned a password because the guest enrolment plugin has been removed from";
        $expectederror .= " course \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for attempting to set guest password while disabling
     * the guest enrolment plugin
     */
    public function test_version1importlogspasswordsetwithguestunsetoncourseupdate2() {
        // Setup.
        set_config('enrol_plugins_enabled', 'guest');
        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_guest');

        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'guest' => '0',
            'password' => 'rlippassword'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment";
        $expectederror .= " plugin cannot be assigned a password because the guest enrolment plugin has been disabled in";
        $expectederror .= " course \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for attempting to set guest password while disabling
     * the guest enrolment plugin, when the guest plugin was previously added and disabled
     */
    public function test_version1importlogspasswordsetwithguestunsetandpreviouslydisabledoncourseupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Setup.
        set_config('enrol_plugin_enabled', 'guest');
        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_DISABLED, 'enrol_guest');

        $this->create_test_course();

        // Data.
        $data = array(
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'password' => 'rlippassword'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be updated. guest enrolment";
        $expectederror .= " plugin cannot be assigned a password because the guest enrolment plugin has been disabled in";
        $expectederror .= " course \"rlipshortname\".\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatevisible() {
        global $CFG;
        $this->load_csv_data();

        // Create mapping record.
        $this->create_mapping_record('course', 'visible', 'customvisible');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'showreports' => 0,
            'maxbytes' => $maxbytes,
            'guest' => 1,
            'customvisible' => 'invalidvisible',
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customvisible value of ";
        $expectederror .= "\"invalidvisible\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    public function test_version1importlogsupdatecourselang() {
        global $CFG;
        $this->load_csv_data();

        // Create mapping record.
        $this->create_mapping_record('course', 'lang', 'customlang');

        $maxbytes = 51200;
        set_config('maxbytes', $maxbytes, 'moodlecourse');
        $maxsections = 20;
        set_config('maxsections', $maxsections, 'moodlecourse');

        $data = array(
            'action' => 'update',
            'shortname' => 'cm2',
            'format' => 'weeks',
            'numsections' => $maxsections,
            'startdate' => 'jan/12/2013',
            'newsitems' => 5,
            'showgrades' => 1,
            'showreports' => 0,
            'maxbytes' => $maxbytes,
            'guest' => 1,
            'visible' => 1,
            'customlang' => 'invalidlang'
        );

        $expectederror = "[course.csv line 2] Course with shortname \"cm2\" could not be updated. customlang value of ";
        $expectederror .= "\"invalidlang\" is not a valid language code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that shortname validation works on course delete
     */
    public function test_version1importlogsinvalidshortnameoncoursedelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'delete',
                      'customshortname' => 'rlipshortname');
        $expectederror = "[course.csv line 2] Course with shortname \"rlipshortname\" could not be deleted. customshortname ";
        $expectederror .= "value of \"rlipshortname\" does not refer to a valid course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that a manual import log file generates the proper name
     */
    public function testversion1importlogname() {
        global $CFG;

        // Pass manual and then scheduled and a timestamp and verify that the name is correct.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        $plugintype = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $entity = 'user';
        $timestamp = time();
        $format = get_string('logfile_timestamp', 'block_rlip');
        $entity = 'user';

        $filename = rlip_log_file_name($plugintype, $plugin, '', $entity, $manual, $timestamp);
        $testfilename = $filepath.'/'.$plugintype.'_version1_manual_'.$entity.'_'.userdate($timestamp, $format).'.log';
        // Get most recent logfile +1 as that is what is returned by rlip_log_file_name.
        $testfilename = self::get_next_logfile($testfilename);

        $this->assertEquals($filename, $testfilename);
    }

    /**
     * Test an invalid log file path
     */
    public function test_version1importinvalidlogpath() {
        global $CFG, $DB, $USER;

        // Check if test is being run as root.
        if (posix_getuid() === 0) {
            $this->markTestSkipped('This test will always fail when run as root.');
        }

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $filepath = $CFG->dataroot.'/invalidlogpath';

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

        set_config('logfilelocation', 'invalidlogpath', 'rlipimport_version1');

        // Do a fake import that should create an error in the database.
        // Check for that error.
        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );

        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $manual = true;
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, $manual);

        // For now suppress output generated.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Data validation.
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $message = 'Log file access failed while importing lines from import file user.csv due to invalid logfile path.';
        $message .= ' Change \'invalidlogpath\' to a valid logfile location on the settings page. Processed 0 of 1 records.';
        $params = array('message' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        // Cleanup the new folder.
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
    }

    /**
     * Validate that a manual import log file generates the correct log file
     */
    public function test_version1importlogmanual() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        // Set the file path to the dataroot.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;

        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );

        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $manual = true;
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, $manual);
        // For now suppress output generated.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Create filename to check for existence.
        $plugintype = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $format = get_string('logfile_timestamp', 'block_rlip');
        $entity = 'user';
        $starttime = $DB->get_field(RLIP_LOG_TABLE, 'starttime', array('id'=>'1'));
        $testfilename = $filepath.'/'.$plugintype.'_version1_manual_'.$entity.'_'.userdate($starttime, $format).'.log';
        $testfilename = self::get_current_logfile($testfilename);

        $exists = file_exists($testfilename);
        $this->assertEquals($exists, true, 'Manual import should have generated the file');

        // Cleanup data file.
        @unlink($testfilename);
    }

    /**
     * Validate that a scheduled import log file exists with the proper name
     */
    public function test_version1importlogscheduled() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');

        $DB->delete_records('elis_scheduled_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        // Set the file path to the dataroot.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;

        // File path and name.
        $filename = 'userscheduledimport.csv';
        // File WILL BE DELETED after import so must copy to moodledata area.
        // Note: file_path now relative to moodledata ($CFG->dataroot).
        $filepath = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot.$filepath;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create a scheduled job.
        $data = array(
            'plugin' => 'rlipimport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'rlipimport',
            'userid' => $USER->id
        );
        $taskid = rlip_schedule_add_job($data);

        // Lower bound on starttime.
        $starttime = time() - 100;

        // Change the next runtime to a day from now.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = $starttime + DAYSECS; // Tomorrow?
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipimport_version1'));
        $job->nextruntime = $starttime + DAYSECS; // Tomorrow?
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Set up config for plugin so the scheduler knows about our csv file.
        set_config('schedule_files_path', $filepath, 'rlipimport_version1');
        set_config('user_schedule_file', $filename, 'rlipimport_version1');

        // Run the import.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Get timestamp from summary log.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $format = get_string('logfile_timestamp', 'block_rlip');

        $plugintype = 'import';
        $plugin = 'rlipimport_version1';
        $manual = true;
        $entity = 'user';
        $testfilename = $plugintype.'_version1_scheduled_'.$entity.'_'.userdate($starttime, $format).'.log';
        $testfilename = self::get_current_logfile($CFG->dataroot.'/rlip/log/'.$testfilename);

        $exists = file_exists($testfilename);
        $this->assertTrue($exists);

        // Cleanup test directory & import data file.
        @unlink($testdir.$filename);
        @rmdir($testdir);
    }

     /**
      * Validate that a manual import log file generates the correct log file
      */
    public function test_version1importlogsequentiallogfiles() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        // Set the file path to the dataroot.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;

        $USER->id = 9999;
        self::cleanup_log_files();

        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );

        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $manual = true;

        // Loop through w/o deleting logs and see what happens.
        for ($i = 0; $i <= 15; $i++) {
            $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, $manual);
            // For now suppress output generated.
            ob_start();
            $instance->run();
            ob_end_clean();

            // Create filename to check for existence.
            $plugintype = 'import';
            $plugin = 'rlipimport_version1';
            $manual = true;
            $entity = 'user';
            $format = get_string('logfile_timestamp', 'block_rlip');
            // Get most recent record.
            $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
            foreach ($records as $record) {
                $starttime = $record->starttime;
                break;
            }

            // Get base filename.
            $basefilename = $filepath.'/'.$plugintype.'_version1_manual_'.$entity.'_'.userdate($starttime, $format).'.log';
            // Get calculated filename.
            $testfilename = self::get_current_logfile($basefilename);

            $exists = file_exists($testfilename);
            $this->assertEquals($exists, true);

        }
        $this->assertEquals($i, 16);

        // Cleanup data file.
        @unlink($testfilename);
    }

    /**
     * Validate that the correct error message is logged when an import runs
     * too long
     */
    public function test_version1importlogsruntimeerror() {
        global $CFG, $DB;

        // Set the file path to the dataroot.
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR).RLIP_DEFAULT_LOG_PATH;
        set_config('logfilelocation', '', 'rlipimport_version1');

        // Set up a "user" import provider, using a single fixed file.
        $filename = 'userfile2.csv';
        // File WILL BE DELETED after import so must copy to moodledata area.
        // Note: file_path now relative to moodledata ($CFG->dataroot).
        $filepath = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot.$filepath;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);
        $provider = new rlip_importprovider_file_delay($CFG->dataroot.$filepath.$filename, 'user');

        // Run the import.
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, $manual);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // Maxruntime 1 sec.
        $ui = ob_get_contents(); // TBD: test this UI string.
        ob_end_clean();

        // Expected error.
        $a = new stdClass;
        $a->entity = $result->entity;
        $a->recordsprocessed = $result->linenumber - 1;
        $a->totalrecords = $result->filelines - 1;
        $expectederror = get_string('manualimportexceedstimelimit_b', 'block_rlip', $a)."\n";

        // Validate that a log file was created.
        $plugintype = 'import';
        $plugin = 'rlipimport_version1';
        $format = get_string('logfile_timestamp', 'block_rlip');
        $entity = 'user';
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $plugintype.'_version1_manual_'.$entity.'_'.userdate($starttime, $format).'.log';
        $filename = self::get_current_logfile($CFG->dataroot.'/rlip/log/'.$testfilename);

        // Echo "testVersion1ImportLogsRuntimeError(): logfile ?=> {$filename}\n";.
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

        // Clean-up data file & test dir.
        @unlink($testdir.$filename);
        @rmdir($testdir);
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the import runs for too long on a manual run
     */
    public function test_version1manualimportlogsruntimefilesystemerror() {
        global $CFG, $DB;

        // Set up the log file location.
        set_config('logfilelocation', '', 'rlipimport_version1');

        // Our import data.
        $data = array(
                array('action', 'username', 'password', 'firstname', 'lastname', 'email', 'city', 'country'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA')
        );

        // Import provider that creates an instance of a file plugin that delays two seconds.
        // Between reading the third and fourth entry.
        $provider = new rlip_importprovider_delay_after_three_users($data);
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, $manual);

        // We should run out of time after processing the second real entry.
        ob_start();
        // Using three seconds to allow for one slow read when counting lines.
        $importplugin->run(0, 0, 3);
        ob_end_clean();

        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        $filename = '';
        foreach ($records as $record) {
            $filename = $record->logpath;
            break;
        }

        // Validate that the right log file was created.
        $this->assertTrue(file_exists($filename));

        // Obtain log file lines.
        $contents = file_get_contents($filename);
        $contents = explode("\n", $contents);

        // Validate line count, accounting for blank line at end.
        $this->assertEquals(count($contents), 4);

        // Obtain the line we care about.
        $line = $contents[2];
        $expectederror = 'Import processing of entity \'user\' partially processed due to time restrictions. ';
        $expectederror .= 'Processed 2 of 3 total records.';

        // Data validation.
        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actualerror = substr($line, $prefixlength);
        $this->assertEquals($expectederror, $actualerror);
    }

    public function test_userprofilefields() {
        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA',
            'profile_field_user_profile_field_1' => 'my user profile field value',
            'profile_field_invalid_user_profile_field_1' => 'my user profile field value',
            'profile_field_user_profile_field_2' => 'my user profile field value',
            'profile_field_invalid_user_profile_field_2' => 'my user profile field value',
        );

        $expectederror1 = "[user.csv line 1] Import file contains the following invalid user profile field(s): invalid_user_";
        $expectederror1 .= "profile_field_1, invalid_user_profile_field_2\n";
        $expectederror2 = "[user.csv line 2] User with username \"rlipusername\", email \"rlipuser@rlipdomain.com\" successfully";
        $expectederror2 .= " created.\n";

        $expectederror = array($expectederror1, $expectederror2);

        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function test_coursethemes() {
        // Create mapping record.
        $this->create_mapping_record('course', 'theme', 'customtheme');

        set_config('allowcoursethemes', 0);
        $data = array(
            'action' => 'create',
            'shortname' => 'shortname',
            'fullname' => 'fullname',
            'customtheme' => 'splash',
            'category' => 'category'
        );
        $expectederror = "[course.csv line 2] Course with shortname \"shortname\" could not be created. Course themes are ";
        $expectederror .= "currently disabled on this site.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        set_config('allowcoursethemes', 1);
        $data['customtheme'] = 'invalidtheme';
        $expectederror = "[course.csv line 2] Course with shortname \"shortname\" could not be created. customtheme value of ";
        $expectederror .= "\"invalidtheme\" is not a valid theme.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider method, providing user fields with their maximum lengths
     *
     * @return array Mapping of user fields to their maximum lengths
     */
    public function usermaxfieldlengthprovider() {
        return array(
                array('username', 100),
                array('firstname', 100),
                array('lastname', 100),
                array('email', 100),
                array('city', 120),
                array('idnumber', 255),
                array('institution', 40),
                array('department', 30)
        );
    }

    /**
     * Validate that errors for user fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider usermaxfieldlengthprovider
     */
    public function test_version1importlogsuserfieldlengtherror($field, $maxlength) {
        // Create mapping record.
        $this->create_mapping_record('user', $field, 'custom'.$field);

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumberdisplay = '';
        if ($field == 'username') {
            $username = $value;
        } else if ($field == 'email') {
            $email = $value;
        } else if ($field == 'idnumber') {
            $idnumberdisplay = ', idnumber "'.$value.'"';
        }
        $expectederror = "[user.csv line 2] User with username \"{$username}\", email \"{$email}\"{$idnumberdisplay} could not ";
        $expectederror .= "be created. custom{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that field length validation works on user update
     */
    public function test_version1importlogsuserfieldlengtherroronupdate() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array('action' => 'update', 'customusername' => $value);
        $expectederror = "[user.csv line 2] User with username \"{$value}\" could not be updated. customusername value of ";
        $expectederror .= "\"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that field length validation works on user delete
     */
    public function test_version1importlogsuserfieldlengtherrorondelete() {
        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array('action' => 'delete', 'customusername' => $value);
        $expectederror = "[user.csv line 2] User with username \"{$value}\" could not be deleted. customusername value of ";
        $expectederror .= "\"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Data provider method, providing course fields with their maximum lengths
     *
     * @return array Mapping of course fields to their maximum lengths
     */
    public function coursemaxfieldlengthprovider() {
        return array(
                array('fullname', 254),
                array('shortname', 100),
                array('idnumber', 100)
        );
    }

    /**
     * Validate that errors for course fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider coursemaxfieldlengthprovider
     */
    public function test_version1importlogscoursefieldlengtherror($field, $maxlength) {
        // Create mapping record.
        $this->create_mapping_record('course', $field, 'custom'.$field);

        $data = array(
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $shortname = 'rlipshortname';
        if ($field == 'shortname') {
            $shortname = $value;
        }
        $expectederror = "[course.csv line 2] Course with shortname \"{$shortname}\" could not be created. custom{$field} ";
        $expectederror .= "value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that field length validation works on course update
     */
    public function test_version1importlogscoursefieldlengtherroronupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $value = str_repeat('x', 101);
        $data = array('action' => 'update', 'customshortname' => $value);
        $expectederror = "[course.csv line 2] Course with shortname \"{$value}\" could not be updated. customshortname value ";
        $expectederror .= "of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that field length validation works on course delete
     */
    public function test_version1importlogscoursefieldlengtherrorondelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $value = str_repeat('x', 101);
        $data = array('action' => 'delete', 'customshortname' => $value);
        $expectederror = "[course.csv line 2] Course with shortname \"{$value}\" could not be deleted. customshortname value ";
        $expectederror .= "of \"{$value}\" exceeds the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider method, providing enrolment fields with their maximum lengths
     *
     * @return array Mapping of enrolment fields to their maximum lengths
     */
    public function enrolmentmaxfieldlengthprovider() {
        return array(
                array('username', 100),
                array('email', 100),
                array('idnumber', 255),
                array('group', 254),
                array('grouping', 254)
        );
    }

    /**
     * Validate that errors for enrolment fields exceeding their maximum length are
     * logged
     *
     * @param string $field The identifying for the field we are testing
     * @param int $maxlength The maximum length allowed for the field we are
     *                       testing
     * @dataProvider enrolmentmaxfieldlengthprovider
     */
    public function test_version1importlogsenrolmentfieldlengtherror($field, $maxlength) {
        // Create mapping record.
        $this->create_mapping_record('enrolment', $field, 'custom'.$field);

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $value = str_repeat('x', $maxlength + 1);
        $data['custom'.$field] = $value;
        $username = 'rlipusername';
        if ($field == 'username') {
            $username = $value;
        }
        $identifiers = array();
        if (isset($data['customusername'])) {
            $identifiers[] = "username \"{$value}\"";
        } else if (isset($data['username'])) {
            $identifiers[] = "username \"rlipusername\"";
        }
        if (isset($data['customemail'])) {
            $identifiers[] = "email \"{$value}\"";
        }
        if (isset($data['customidnumber'])) {
            $identifiers[] = "idnumber \"{$value}\"";
        }
        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. custom{$field} value of \"{$value}\" exceeds ";
        $expectederror .= "the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validate that field length validation works on enrolment delete
     */
    public function test_version1importlogsenrolmentfieldlengtherrorondelete() {
        // Create mapping record.
        $this->create_mapping_record('enrolment', 'username', 'customusername');

        $value = str_repeat('x', 101);
        $data = array(
            'action' => 'delete',
            'customusername' => $value,
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. customusername value of \"{$value}\" exceeds ";
        $expectederror .= "the maximum field length of 100.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validate log message for the action column missing on user import
     */
    public function test_version1importlogsmissingactioncolumnonuserimport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('user', 'action', 'customaction');

        $data = array('username' => 'rlipusername');
        $message = "Import file user.csv was not processed because it is missing the following column: customaction. Please ";
        $message .= "fix the import file and re-upload it.\n";
        $expectedmessage = "[user.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for all of username, email, idnumber columns
     * missing on user import
     */
    public function test_version1importlogsmissingusercolumngroup() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array('action' => 'create');
        $message = "Import file user.csv was not processed because one of the following columns is required but all are ";
        $message .= "unspecified: customusername, customemail, customidnumber. Please fix the import file and re-upload it.\n";
        $expectedmessage = "[user.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for action column missing on course import
     */
    public function test_version1importlogsmissingactioncolumnoncourseimport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array('shortname' => 'rlipshortname');
        $message = "Import file course.csv was not processed because it is missing the following column: customaction. Please fix";
        $message .= " the import file and re-upload it.\n";
        $expectedmessage = "[course.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for a single column missing on course import
     */
    public function test_version1importlogsmissingcoursecolumn() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('course', 'shortname', 'customshortname');

        $data = array('action' => 'create');
        $message = "Import file course.csv was not processed because it is missing the following required column: ";
        $message .= "customshortname. Please fix the import file and re-upload it.\n";
        $expectedmessage = "[course.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'course');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for action column missing on enrolment import
     */
    public function test_version1importlogsmissingactioncolumnonenrolmentimport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'action', 'customaction');

        $data = array('username' => 'rlipusername');
        $message = "Import file enrolment.csv was not processed because it is missing the following column: customaction. Please";
        $message .= " fix the import file and re-upload it.\n";
        $expectedmessage = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for a single column missing on enrolment import
     */
    public function test_version1importlogsmissingenrolmentcolumn() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        $data = array(
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'user',
            'instance' => 'rlipusername'
        );
        $message = "Import file enrolment.csv was not processed because it is missing the following required column: customrole.";
        $message .= " Please fix the import file and re-upload it.\n";
        $expectedmessage = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for multiple columns missing on enrolment import
     */
    public function test_version1importlogsmissingenrolmentcolumns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Create mapping record.
        $this->create_mapping_record('enrolment', 'context', 'customcontext');
        $this->create_mapping_record('enrolment', 'role', 'customrole');

        $data = array('action' => 'create', 'username' => 'rlipusername');
        $message = "Import file enrolment.csv was not processed because it is missing the following required columns: ";
        $message .= "customcontext, customrole. Please fix the import file and re-upload it.\n";
        $expectedmessage = "[enrolment.csv line 1] {$message}";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        // Remove newline character.
        $message = substr($message, 0, strlen($message) - 1);
        $params = array('statusmessage' => $message);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate log message for an invalid action value for the user
     * entity type
     */
    public function test_version1importlogsinvaliduseraction() {
        // Data.
        $data = array('action' => 'bogus', 'username' => 'testusername');
        $expectedmessage = "[user.csv line 2] User could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
    }

    /**
     * Validate log message for an invalid action value for the course
     * entity type
     */
    public function test_version1importlogsinvalidcourseaction() {
        // Data.
        $data = array('action' => 'bogus', 'shortname' => 'testshortname');
        $expectedmessage = "[course.csv line 2] Course could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    /**
     * Validate log message for an invalid action value for the enrolment
     * entity type
     */
    public function test_version1importlogsinvalidenrolmentaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'username' => 'testusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $expectedmessage = "[enrolment.csv line 2] Enrolment could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validate that general enrolment errors are only displayed for enrolments
     * on the course context on enrolment create
     */
    public function test_version1generalenrolmentmessageiscontextspecific() {
        // Setup.
        $userid = $this->create_test_user();
        $roleid = $this->create_test_role();

        // Assign a role.
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        role_assign($roleid, $userid, $systemcontext->id);

        // Run the import and validate.
        $data = array(
            'action'    => 'create',
            'username'  => 'rlipusername',
            'context'   => 'system',
            'instance'  => 'system',
            'role'      => 'rlipshortname'
        );
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" could not be assigned role with shortname";
        $expectedmessage .= " \"rlipshortname\" on the system context. User with username \"rlipusername\" is already assigned ";
        $expectedmessage .= "role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validate that general unenrolment errors are only displayed for enrolments
     * on the course context on enrolment delete
     */
    public function test_version1generalunenrolmentmessageiscontextspecific() {
        // Setup.
        $this->create_test_user();
        $roleid = $this->create_test_role();

        // Run the import and validate.
        $data = array(
            'action'   => 'delete',
            'username' => 'rlipusername',
            'context'  => 'system',
            'instance' => 'system',
            'role'     => 'rlipshortname'
        );
        $expectedmessage = "[enrolment.csv line 2] User with username \"rlipusername\" could not be unassigned role with";
        $expectedmessage .= " shortname \"rlipshortname\" on the system context. User with username \"rlipusername\" is not";
        $expectedmessage .= " assigned role with shortname \"rlipshortname\" on the system context.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    /**
     * Validate that import update action matching multiple users
     * fails with message(s)
     */
    public function test_version1importfailswithmessagewhenmatchingmultipleusers() {
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $roleid = $this->create_test_role();

        // Create required test users.
        $data = array(
            'action'    => 'create',
            'username'  => 'testuser5162a',
            'idnumber'  => 'testuser5162a',
            'email'     => 'tu5162a@noreply.com',
            'password'  => 'TestPassword!0',
            'firstname' => 'Test',
            'lastname'  => 'User5162a',
            'city'      => 'Waterloo',
            'country'   => 'CA'
        );
        // Run the import.
        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        // Suppress output for now.
        ob_start();
        $instance->run();

        $data = array(
            'action'    => 'create',
            'username'  => 'testuser5162b',
            'idnumber'  => 'testuser5162b',
            'email'     => 'tu5162b@noreply.com',
            'password'  => 'TestPassword!0',
            'firstname' => 'Test',
            'lastname'  => 'User5162b',
            'city'      => 'Waterloo',
            'country'   => 'CA'
        );
        // Run the import.
        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        $instance->run();

        $data = array(
            'action'    => 'create',
            'username'  => 'testuser5162c',
            'idnumber'  => 'testuser5162c',
            'email'     => 'tu5162c@noreply.com',
            'password'  => 'TestPassword!0',
            'firstname' => 'Test',
            'lastname'  => 'User5162c',
            'city'      => 'Waterloo',
            'country'   => 'CA'
        );
        // Run the import.
        $provider = new rlipimport_version1_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $provider, null, true);
        $instance->run();

        // Create update error ...
        $data = array(
            'action'    => 'update',
            'username'  => 'testuser5162a',
            'idnumber'  => 'testuser5162b',
            'email'     => 'tu5162c@noreply.com',
            'password'  => 'TestPassword!0',
            'firstname' => 'Test',
            'lastname'  => 'User5162x',
            'city'      => 'Waterloo',
            'country'   => 'CA'
        );

        // Assert failure conditions.
        $expectedmessage = "[user.csv line 2] User with username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber";
        $expectedmessage .= " \"testuser5162b\" could not be updated. username \"testuser5162a\", email \"tu5162c@noreply.com\",";
        $expectedmessage .= " idnumber \"testuser5162b\" matches multiple users.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');

        // Create delete error ...
        $data = array(
            'action'    => 'delete',
            'username'  => 'testuser5162a',
            'idnumber'  => 'testuser5162b',
            'email'     => 'tu5162c@noreply.com',
            'password'  => 'TestPassword!0',
            'firstname' => 'Test',
            'lastname'  => 'User5162x',
            'city'      => 'Waterloo',
            'country'   => 'CA'
        );

        // Assert failure conditions.
        $expectedmessage = "[user.csv line 2] User with username \"testuser5162a\", email \"tu5162c@noreply.com\", idnumber";
        $expectedmessage .= " \"testuser5162b\" could not be deleted. username \"testuser5162a\", email \"tu5162c@noreply.com\",";
        $expectedmessage .= " idnumber \"testuser5162b\" matches multiple users.\n";
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
    }
}