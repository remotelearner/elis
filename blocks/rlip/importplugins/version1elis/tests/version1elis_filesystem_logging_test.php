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
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/file_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/delay_after_three.class.php');

/**
 * Test filesystem logging.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisfilesystemlogging_testcase extends rlip_elis_test {

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array  $data The import data to process
     * @param string $expectederror The error we are expecting (message only)
     * @param user   $entitytype One of 'user', 'course', 'enrolment'
     * @param string $importfilename  name of import file
     */
    protected function assert_data_produces_error($data, $expectederror, $entitytype, $importfilename = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data, $importfilename);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, true);
        // Suppress output for now.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Validate that a log file was created.
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
        $testfilename = $filepath.'/'.$plugintype.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        // Get most recent logfile.

        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename), "\n Can't find logfile: {$filename} for \n{$testfilename}");

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
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
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
        $usernameemaildata = array('customusername' => 'bogus',
                                     'customemail' => 'bogus@bogus.com');
        $usernameemailmessage = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of ";
        $usernameemailmessage .= "\"bogus@bogus.com\" do not refer to a valid user.\n";
        $data[] = array($usernameemaildata, $usernameemailmessage);

        // Invalid combination of username, idnumber.
        $usernameidnumberdata = array('customusername' => 'bogus',
                                        'customidnumber' => 'bogus');
        $usernameidnumbermessage = "[enrolment.csv line 2] customusername value of \"bogus\", customidnumber value of \"bogus\" ";
        $usernameidnumbermessage .= "do not refer to a valid user.\n";
        $data[] = array($usernameidnumberdata, $usernameidnumbermessage);

        // Invalid combination of email, idnumber.
        $emailidnumberdata = array('customemail' => 'bogus@bogus.com',
                                     'customidnumber' => 'bogus');
        $emailidnumbermessage = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\", customidnumber value of ";
        $emailidnumbermessage .= "\"bogus\" do not refer to a valid user.\n";
        $data[] = array($emailidnumberdata, $emailidnumbermessage);

        // Invalid combination of username, email, idnumber.
        $allfieldsdata = array('customusername' => 'bogus',
                                 'customemail' => 'bogus@bogus.com',
                                 'customidnumber' => 'bogus');
        $allfieldsmessage = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\", ";
        $allfieldsmessage .= "customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($allfieldsdata, $allfieldsmessage);

        return $data;
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
     * Data provider function that providers all combinations of identifying user fields.
     *
     * @return array Data in format expected by phpunit
     */
    public function roleassignmentambiguousgroupnameuserprovider() {
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumber = 'rlipidnumber';

        return array(
                array(array('username' => $username)),
                array(array('email' => $email)),
                array(array('idnumber' => $idnumber)),
                array(array('username' => $username, 'email' => $email)),
                array(array('username' => $username, 'idnumber' => $idnumber)),
                array(array('email' => $email, 'idnumber' => $idnumber)),
                array(array('username' => $username, 'email' => $email, 'idnumber' => $idnumber))
        );
    }

    /**
     * Data provider method, providing identifying user fields as well as
     * text to describe those fields, with every combination of identifying
     * user fields provided
     *
     * @return array Array of data, with each elements containing a data set and a descriptive string.
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
     * Validate that the correct error message is logged when an import file has 'no records', i.e. no LFs
     */
    public function test_version1elis_import_norecs_logs_runtime_error() {
        global $CFG, $DB;

        // Set the file path to the dataroot.
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR).RLIP_DEFAULT_LOG_PATH;
        set_config('logfilelocation', '', 'rlipimport_version1elis');

        // Set up a "user" import provider, using a single fixed file.
        $filename = 'userfilenorecs.csv';
        // File WILL BE DELETED after import so must copy to moodledata area.
        // Note: file_path now relative to moodledata ($CFG->dataroot).
        $filepathb = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot.$filepathb;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);
        $provider = new rlip_importprovider_file_delay($CFG->dataroot.$filepathb.$filename, 'user');

        // Run the import.
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, $manual);
        ob_start();
        $result = $importplugin->run(0, 0, 60); // Maxruntime 60 sec.
        $ui = ob_get_contents(); // TBD: test this UI string.
        ob_end_clean();

        // Validate that a log file was created.
        $plugintype = 'import';
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp', 'block_rlip');
        $entity = 'user';
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            $endtime = $record->endtime;
            break;
        }

        // Verify endtime >= starttime.
        $this->assertGreaterThanOrEqual($starttime, $endtime);

        $testfilename = $filepath.'/'.$plugintype.'_version1elis_manual_'.$entity.'_'.userdate($starttime, $format).'.log';

        $filename = self::get_current_logfile($testfilename);

        $this->assertTrue(file_exists($filename));
        // Fetch log line.
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if (empty($line)) {
            // No line found.
            $this->assertTrue(false);
        } else {
            // Expected error.
            $expectederror = 'Could not read data, make sure import file lines end with LF (linefeed) character: 0x0A'."\n";

            // Data validation.
            $actualerror = preg_replace('/^\[.*\] \[.*\] /', '', $line);
            $this->assertEquals($expectederror, $actualerror);
        }

        // Clean-up data file & test dir.
        @unlink($testdir.$filename);
        @rmdir($testdir);
    }

    /**
     * Validate that the correct error message is logged when an import runs
     * too long
     */
    public function test_version1elisimportlogsruntimeerror() {
        global $CFG, $DB;

        // Set the file path to the dataroot.
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR).RLIP_DEFAULT_LOG_PATH;
        set_config('logfilelocation', '', 'rlipimport_version1elis');

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
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, $manual);
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
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp', 'block_rlip');
        $entity = 'user';
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = '/'.$plugintype.'_version1elis_manual_'.$entity.'_'.userdate($starttime, $format).'.log';

        $filename = self::get_current_logfile($CFG->dataroot.'/rlip/log/'.$testfilename);

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
    public function test_version1elismanualimportlogsruntimefilesystemerror() {
        global $CFG, $DB;

        // Set up the log file location.
        set_config('logfilelocation', '', 'rlipimport_version1elis');

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
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, $manual);

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
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the user import file is missing required fields
     */
    public function test_version1elisuserimportlogsmissingcolumns() {
        $data = array(
            'action'    => 'create',
            'firstname' => 'testfirstname',
            'lastname'  => 'testlastname',
            'password'  => 'Testpassword!0',
            'city'      => 'Waterloo',
            'country'   => 'CA',
            'lang'      => 'en'
        );

        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is ";
        $expectederror .= "required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload ";
        $expectederror .= "it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action'    => 'update',
            'firstname' => 'testfirstname',
            'lastname'  => 'testlastname',
            'password'  => 'Testpassword!0',
            'city'      => 'Waterloo',
            'country'   => 'CA',
            'lang'      => 'en'
        );

        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is ";
        $expectederror .= "required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload ";
        $expectederror .= "it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action'    => 'delete',
            'firstname' => 'testfirstname',
            'lastname'  => 'testlastname',
            'password'  => 'Testpassword!0',
            'city'      => 'Waterloo',
            'country'   => 'CA',
            'lang'      => 'en'
        );

        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is ";
        $expectederror .= "required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload ";
        $expectederror .= "it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the course(ELIS entity) import file is missing required fields
     */
    public function test_version1elisentityimportlogsmissingcolumns() {
        // Create.
        $data = array(
            'action'   => 'create',
            'context'  => 'curriculum',
            'name'     => 'ProgramName'
        );

        $expectederror = "[course.csv line 2] Program could not be created. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'create',
            'context' => 'track',
            'name'    => 'TrackName'
        );

        $expectederror = "[course.csv line 2] Track could not be created. Required fields assignment, idnumber are unspecified or";
        $expectederror .= " empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'create',
            'context' => 'course',
            'name'    => 'CourseDescriptionName'
        );

        $expectederror = "[course.csv line 2] Course description could not be created. Required field idnumber is unspecified or ";
        $expectederror .= "empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'create',
            'context' => 'class',
            'name'    => 'ClassInstanceName'
        );

        $expectederror = "[course.csv line 2] Class instance could not be created. Required fields assignment, idnumber are ";
        $expectederror .= "unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'create',
            'context' => 'cluster'
        );

        $expectederror = "[course.csv line 2] User set could not be created. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Update.
        $data = array(
            'action'  => 'update',
            'context' => 'curriculum',
            'name'    => 'NewProgramName'
        );

        $expectederror = "[course.csv line 2] Program could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'update',
            'context' => 'track',
            'name'    => 'NewTrackName'
        );

        $expectederror = "[course.csv line 2] Track could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'update',
            'context' => 'course',
            'name'    => 'NewCourseDescriptionName'
        );

        $expectederror = "[course.csv line 2] Course description could not be updated. Required field idnumber is unspecified ";
        $expectederror .= "or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'update',
            'context' => 'class',
            'name'    => 'NewClassInstanceName'
        );

        $expectederror = "[course.csv line 2] Class instance could not be updated. Required field idnumber is unspecified or ";
        $expectederror .= "empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array('action' => 'update', 'context' => 'cluster' );

        $expectederror = "[course.csv line 2] User set could not be updated. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Delete.
        $data = array(
            'action'  => 'delete',
            'context' => 'curriculum',
            'name'    => 'ProgramName'
        );

        $expectederror = "[course.csv line 2] Program could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'delete',
            'context' => 'track',
            'name'    => 'TrackName'
        );

        $expectederror = "[course.csv line 2] Track could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'delete',
            'context' => 'course',
            'name'    => 'CourseDescriptionName'
        );

        $expectederror = "[course.csv line 2] Course description could not be deleted. Required field idnumber is unspecified ";
        $expectederror .= "or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action'  => 'delete',
            'context' => 'class',
            'name'    => 'ClassInstanceName'
        );

        $expectederror = "[course.csv line 2] Class instance could not be deleted. Required field idnumber is unspecified or ";
        $expectederror .= "empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array('action' => 'delete', 'context' => 'cluster' );

        $expectederror = "[course.csv line 2] User set could not be deleted. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the enrolment import file is missing required fields
     */
    public function test_version1elisenrolmentimportlogsmissingcolumns() {
        // Create.
        $data = array(
            'action'        => 'create',
            'context'       => 'curriculum_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'track_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'course_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'class_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'cluster_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        // Update.
        $data = array(
            'action'        => 'create',
            'context'       => 'class_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'track_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'course_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'class_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'create',
            'context'       => 'cluster_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        // Update - only allowed for class context.
        $data = array(
            'action'        => 'update',
            'context'       => 'class_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        // Delete.
        $data = array(
            'action'        => 'delete',
            'context'       => 'curriculum_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'delete',
            'context'       => 'track_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'delete',
            'context'       => 'course_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'delete',
            'context'       => 'class_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');

        $data = array(
            'action'        => 'delete',
            'context'       => 'cluster_1',
            'enrolmenttime' => 'Jul/17/2012:12:00'
        );

        $expectederror = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns";
        $expectederror .= " is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import";
        $expectederror .= " file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

}
