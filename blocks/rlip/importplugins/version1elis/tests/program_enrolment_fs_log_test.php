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
 * Test program enrolment filesystem logging
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisprogramenrolmentfslog_testcase extends rlip_elis_test {

    /**
     * Called before the test class.
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
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, true);
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
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp', 'block_rlip');
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
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }

    // Validate that program validation works on enrolment create.
    public function testelisprogramenrolmentvalidationcreate() {
        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'context' => 'curriculum_invalidtestprogramid',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. instance value of \"invalidtestprogramid\" ";
        $expectederror .= "does not refer to a valid instance of a program context.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate that user validation works on program create.
    public function testelisprogramenrolmentusercreate() {
        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'invalidtestuserid',
            'user_username' => 'invaludtestuser',
            'user_email' => 'invalidtestuser@mail.com'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. username value of \"invaludtestuser\", email";
        $expectederror .= " value of \"invalidtestuser@mail.com\", idnumber value of \"invalidtestuserid\" do not refer to a valid";
        $expectederror .= " user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate credits validation works on enrolment create.
    public function testelisprogramenrolmentcreditscreate() {
        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com',
            'credits' => '10.000000'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. credits value of \"10.000000\" is not a number";
        $expectederror .=" with at most ten total digits and two decimal digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate locked validation works on enrolment create.
    public function testelisprogramenrolmentlockedcreate() {
        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com',
            'credits' => '10.00',
            'locked' => -1
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. locked value of \"-1\" is not one of the";
        $expectederror .= " available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate that program validation works on enrolment deletion.
    public function testelisprogramenrolmentvalidationdelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum_invalidtestprogramid',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. instance value of \"invalidtestprogramid\" does";
        $expectederror .= " not refer to a valid instance of a program context.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate user validation works on enrolment deletion.
    public function testelisprogramenrolmentuserdelete() {

        $this->load_csv_data();

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'invalidtestuserid',
            'user_username' => 'invaludtestuser',
            'user_email' => 'invalidtestuser@mail.com'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be deleted. username value of \"invaludtestuser\", email";
        $expectederror .= " value of \"invalidtestuser@mail.com\", idnumber value of \"invalidtestuserid\" do not refer to a valid";
        $expectederror .= " user.\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    // Validate that enrolment already exists on create.
    public function testelisprogramenrolmentexistscreate() {
        global $DB;

        $this->load_csv_data();

        $record = new stdClass;
        $record->curriculumid = 1;
        $record->userid = 3;

        $DB->insert_record('crlm_curriculum_assignment', $record);

        $idnumber = $DB->get_field('user', 'idnumber', array('id' => 3));

        $data = array(
            'action' => 'create',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com'
        );

        $expectederror = "[enrolment.csv line 2] Enrolment could not be created. User with username \"testusername\", email";
        $expectederror .= " \"test@user.com\", idnumber \"testidnumber\" is already enrolled in program \"testprogramidnumber\".\n";
        $this->assert_data_produces_error($data, $expectederror, 'enrolment');
    }

    /**
     * Validate log message for an invalid action value for program enrolments
     */
    public function testlogsinvalidprogramenrolmentaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'curriculum_testprogramidnumber',
            'user_idnumber' => 'testidnumber',
            'user_username' => 'testusername',
            'user_email' => 'test@user.com'
        );
        $expectedmessage = "[enrolment.csv line 2] Enrolment in \"curriculum_testprogramidnumber\" could not be processed. Action";
        $expectedmessage .= " of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'enrolment');
    }

    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            'crlm_curriculum' => dirname(__FILE__).'/fixtures/programtable.csv',
            'user' => dirname(__FILE__).'/fixtures/usertable.csv',
            'crlm_user' => dirname(__FILE__).'/fixtures/usertable.csv',
        ));
        $this->loadDataSet($dataset);
    }
}