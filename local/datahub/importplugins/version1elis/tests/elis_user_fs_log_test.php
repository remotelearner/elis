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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/csv_delay.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/file_delay.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/delay_after_three.class.php');

/**
 * Test user filesystem logging.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elisuserfslogging_testcase extends rlip_elis_test {

    /**
     * Called before each test function.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
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
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('dhimport_version1elis', $provider, null, true);
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
        $plugin = 'dhimport_version1elis';
        $format = get_string('logfile_timestamp', 'local_datahub');
        $testfilename = $filepath.'/'.$plugintype.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
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
     * Validate that username validation works on user create
     */
    public function testelisuserusernamecreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'customusername' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber ";
        $expectederror .= "\"testidnumber\" could not be created. customusername value of \"testusername\" refers to a user that ";
        $expectederror .= "already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that email validation works on user create
     */
    public function testelisinvaliduseremailcreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'customemail' => 'com.mail@user',
            'city' => 'Waterloo',
            'birhtdate' => 'JAN/01/2012',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"com.mail@user\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customemail value of \"com.mail@user\" is not a valid";
        $expectederror .= " email address.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    public function testelisinvaliduserduplicateemailcreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'customemail' => 'test@user.com',
            'city' => 'Waterloo',
            'birhtdate' => 'JAN/01/2012',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customemail value of \"test@user.com\" refers to a user";
        $expectederror .= " that already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        set_config('allowduplicateemails', '1', 'dhimport_version1elis');

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" successfully created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that email2 validation works on user create
     */
    public function testelisinvaliduseremail2create() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'email2', 'customemail2');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'customemail2' => 'com.mail@user',
            'city' => 'Waterloo',
            'birthdate' => '2012.01.02',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customemail2 value of \"com.mail@user\" is not a valid ";
        $expectederror .= "email address.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that email2 validation works on user update
     */
    public function testelisinvaliduseremail2update() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'email2', 'customemail2');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'customemail2' => 'com.mail@user',
            'city' => 'Waterloo',
            'birthdate' => '2012.01.02',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be updated. customemail2 value of \"com.mail@user\" is not a valid email";
        $expectederror .= " address.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that country validation works on user create
     */
    public function testelisinvalidusercountrycreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'city' => 'Waterloo',
            'birthdate' => '01/02/2012',
            'customcountry' => 'invalidCA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customcountry value of \"invalidCA\" is not a valid ";
        $expectederror .= "country or country code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that country validation works on user update
     */
    public function testelisinvalidusercountryupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'country', 'customcountry');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'birthdate' => '01/02/2012',
            'customcountry' => 'invalidCA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be updated. customcountry value of \"invalidCA\" is not a valid country or";
        $expectederror .= " country code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that birthdate validation works on user create
     */
    public function testelisinvaliduserbirthdatecreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'birthdate', 'custombirthdate');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'city' => 'Waterloo',
            'custombirthdate' => '01/02',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. custombirthdate value of \"01/02\" is not a valid date in";
        $expectederror .= " MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that birthdate validation works on user update
     */
    public function testelisinvaliduserbirthdateupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'birthdate', 'custombirthdate');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'custombirthdate' => '01/02',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber ";
        $expectederror .= "\"testidnumber\" could not be updated. custombirthdate value of \"01/02\" is not a valid date in";
        $expectederror .= " MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that gender validation works on user create
     */
    public function testelisinvalidusergendercreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'gender', 'customgender');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'city' => 'Waterloo',
            'lang' => 'en',
            'customgender' => 'invalidgender',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customgender value of \"invalidgender\" is not one of the";
        $expectederror .= " available options (M, male, F, female).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that gender validation works on user update
     */
    public function testelisinvalidusergenderupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'gender', 'customgender');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'lang' => 'en',
            'customgender' => 'invalidgender',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber ";
        $expectederror .= "\"testidnumber\" could not be updated. customgender value of \"invalidgender\" is not one of the";
        $expectederror .= " available options (M, male, F, female).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that lang validation works on user create
     */
    public function testelisinvaliduserlangcreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'lang', 'customlang');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'city' => 'Waterloo',
            'gender' => 'F',
            'customlang' => 'invalidlang',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customlang value of \"invalidlang\" is not a valid";
        $expectederror .= " language code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that lang validation works on user update
     */
    public function testelisinvaliduserlangupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'lang', 'customlang');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'gender' => 'F',
            'customlang' => 'invalidlang',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber ";
        $expectederror .= "\"testidnumber\" could not be updated. customlang value of \"invalidlang\" is not a valid language";
        $expectederror .= " code.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that transfer credits validation works on user create
     */
    public function testelisinvalidusertransfercreditscreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'transfercredits', 'customtransfercredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'user@validmail.com',
            'city' => 'Waterloo',
            'lang' => 'en',
            'gender' => 'F',
            'lang' => 'en',
            'customtransfercredits' => -1,
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"user@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. customtransfercredits value of \"-1\" is not a";
        $expectederror .= " non-negative integer.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that transfer credits validation works on user update
     */
    public function testelisinvalidusertransfercreditsupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'transfercredits', 'customtransfercredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'lang' => 'en',
            'gender' => 'F',
            'lang' => 'en',
            'customtransfercredits' => -1,
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be updated. customtransfercredits value of \"-1\" is not a";
        $expectederror .= " non-negative integer.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that inactive validation works on user create
     */
    public function testelisuserinvalidinactivecreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'inactive', 'custominactive');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'idnumber' => 'validtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@validmail.com',
            'city' => 'Waterloo',
            'custominactive' => 2,
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"test@validmail.com\", idnumber";
        $expectederror .= " \"validtestidnumber\" could not be created. custominactive value of \"2\" is not one of the available";
        $expectederror .= " options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that inactive validation works on user create
     */
    public function testelisuserinvalidinactiveupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'inactive', 'custominactive');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'username' => 'testusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'custominactive' => 2,
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be updated. custominactive value of \"2\" is not one of the available";
        $expectederror .= " options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that idnumber validation works on user create
     */
    public function testelisuseridnumbercreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'username' => 'validtestusername',
            'customidnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'validtest@user.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"validtestusername\", email \"validtest@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be created. customidnumber value of \"testidnumber\" refers to a user that";
        $expectederror .= " already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that username validation works on user update
     */
    public function testelisuserusernameupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'customusername' => 'invalidtestusername',
            'idnumber' => 'testidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"invalidtestusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be updated. customusername value of \"invalidtestusername\" does not refer";
        $expectederror .= " to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate that email validation works on user update
     */
    public function testelisuseridnumberupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'customusername' => 'testusername',
            'idnumber' => 'invalidtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'test@user.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"test@user.com\", idnumber";
        $expectederror .= " \"invalidtestidnumber\" could not be updated. idnumber value of \"invalidtestidnumber\" does not refer";
        $expectederror .= " to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate multiple invalid fields on user update
     */
    public function testelisusermultipleinvalidfieldupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'customusername' => 'invalidtestusername',
            'idnumber' => 'invalidtestidnumber',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'invalidtest@user.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        );

        $expectederror = "[user.csv line 2] User with username \"invalidtestusername\", email \"invalidtest@user.com\", ";
        $expectederror .= "idnumber \"invalidtestidnumber\" could not be updated. idnumber value of \"invalidtestidnumber\", ";
        $expectederror .= "customusername value of \"invalidtestusername\", email value of \"invalidtest@user.com\" do not refer to a";
        $expectederror .= " valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user create */
    public function testuserinvalididentifyingfieldsoncreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');
        $this->create_mapping_record('user', 'email', 'customemail');
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'create'
        );
        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is";
        $expectederror .= " required but all are unspecified: user_username, user_email, user_idnumber, customidnumber, customusername, customemail.";
        $expectederror .= " Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Create mapping record.
        $this->create_mapping_record('user', 'action', 'customaction');

        $data = array(
            'customaction' => '',
            'customusername' => ''
        );
        $expectederror = "[user.csv line 2] User could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $this->create_mapping_record('user', 'firstname', 'customfirstname');
        $this->create_mapping_record('user', 'lastname', 'customlastname');
        $this->create_mapping_record('user', 'country', 'customcountry');

        $data = array(
            'customaction' => 'create',
            'customusername' => '',
            'customemail' => '',
            'customidnumber' => ''
        );

        $expectederror = "[user.csv line 2] User could not be created. Required fields customidnumber, customusername,";
        $expectederror .= " customfirstname, customlastname, customemail, customcountry are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'customaction' => 'create',
            'customusername' => 'testusername'
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\" could not be created. Required fields";
        $expectederror .= " customidnumber, customfirstname, customlastname, customemail, customcountry are unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'customaction' => 'create',
            'customemail' => 'user@mail.com'
        );
        $expectederror = "[user.csv line 2] User with email \"user@mail.com\" could not be created. Required fields";
        $expectederror .= " customidnumber, customusername, customfirstname, customlastname, customcountry are unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'customaction' => 'create',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[user.csv line 2] User with idnumber \"testidnumber\" could not be created. Required fields";
        $expectederror .= " customusername, customfirstname, customlastname, customemail, customcountry are unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'customaction' => 'create',
            'customusername' => 'testusername',
            'customemail' => 'user@mail.com',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"user@mail.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be created. Required fields customfirstname, customlastname, ";
        $expectederror .= "customcountry are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'customaction' => 'create',
            'customusername' => 'testusername',
            'customemail' => 'user@mail.com',
            'customidnumber' => 'testidnumber',
            'customfirstname' => 'dsfds',
            'customlastname' => 'sadfs'
        );
        $expectederror = "[user.csv line 2] User with username \"testusername\", email \"user@mail.com\", idnumber";
        $expectederror .= " \"testidnumber\" could not be created. Required field customcountry is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user update */
    public function testuserinvalididentifyingfieldsonupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array(
            'action' => 'update',
            'customusername' => 'testusername'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\" could not be updated. customusername value";
        $expectederror .= " of \"testusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $data = array(
            'action' => 'update',
            'customemail' => 'user@mail.com'
        );
        $expectederror = "[user.csv line 2] User with email \"user@mail.com\" could not be updated. customemail value of";
        $expectederror .= " \"user@mail.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'update',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[user.csv line 2] User with idnumber \"testidnumber\" could not be updated. customidnumber value";
        $expectederror .= " of \"testidnumber\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action' => 'update',
            'customusername' => '',
            'customemail' => '',
            'customidnumber' => ''
        );

        $expectederror = "[user.csv line 2] User could not be updated. One of user_username, user_email, user_idnumber, customidnumber, customusername, customemail is";
        $expectederror .= " required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action' => 'update',
        );
        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is";
        $expectederror .= " required but all are unspecified: user_username, user_email, user_idnumber, customidnumber, customusername, customemail.";
        $expectederror .= " Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /* Test that the correct error messages are shown for the provided fields on user delete */
    public function testuserinvalididentifyingfieldsondelete() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('user', 'username', 'customusername');

        $data = array(
            'action' => 'delete',
            'customusername' => 'testusername'
        );

        $expectederror = "[user.csv line 2] User with username \"testusername\" could not be deleted. customusername value of";
        $expectederror .= " \"testusername\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Create mapping record.
        $this->create_mapping_record('user', 'email', 'customemail');

        $data = array(
            'action' => 'delete',
            'customemail' => 'user@mail.com'
        );
        $expectederror = "[user.csv line 2] User with email \"user@mail.com\" could not be deleted. customemail value of";
        $expectederror .= " \"user@mail.com\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        // Create mapping record.
        $this->create_mapping_record('user', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'delete',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[user.csv line 2] User with idnumber \"testidnumber\" could not be deleted. customidnumber value of";
        $expectederror .= " \"testidnumber\" does not refer to a valid user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action' => 'delete',
            'customusername' => '',
            'customemail' => '',
            'customidnumber' => ''
        );

        $expectederror = "[user.csv line 2] User could not be deleted. One of user_username, user_email, user_idnumber, customidnumber, customusername, customemail is";
        $expectederror .= " required but all are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');

        $data = array(
            'action' => 'delete',
        );
        $expectederror = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is";
        $expectederror .= " required but all are unspecified: user_username, user_email, user_idnumber, customidnumber, customusername, customemail.";
        $expectederror .= " Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Validate log message for an invalid action value for the user
     * entity type
     */
    public function testlogsinvaliduseraction() {
        // Data.
        $data = array('action' => 'bogus', 'username' => 'testusername');
        $expectedmessage = "[user.csv line 2] User could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'user');
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
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('dhimport', 'version1elis').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }

    protected function load_csv_data() {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        $dataset = $this->createCsvDataSet(array(user::TABLE => dirname(__FILE__).'/fixtures/usertable.csv'));
        $this->loadDataSet($dataset);
    }
}
