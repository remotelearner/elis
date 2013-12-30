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
 * Test track import filesystem logging.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elistrackfslog_testcase extends rlip_elis_test {

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
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data, 'track.csv');
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

    /**
     * Validate that start date validation works on track create
     */
    public function testelistrackinvalidstartdatecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $this->load_program();

        $data = array(
            'action' => 'create',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'assignment' => 'testprogramidnumber',
            'name' => 'testtrack',
            'customstartdate' => '1340138101'
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be created. ";
        $expectederror .= "customstartdate value of \"1340138101\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or ";
        $expectederror .= "MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that start date validation works on track update
     */
    public function testelistrackinvalidstartdateupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'customstartdate' => '1340138101'
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be updated. ";
        $expectederror .= "customstartdate value of \"1340138101\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or ";
        $expectederror .= "MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that end date validation works on track create
     */
    public function testelistrackinvalidenddatecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enddate', 'customenddate');

        $this->load_program();

        $data = array(
            'action' => 'create',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'assignment' => 'testprogramidnumber',
            'startdate' => '01/02/2012',
            'customenddate' => '1340138101'
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be created. ";
        $expectederror .= "customenddate value of \"1340138101\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or";
        $expectederror .= " MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that end date validation works on track update
     */
    public function testelistrackinvalidenddateupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enddate', 'customenddate');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'startdate' => '01/02/2012',
            'customenddate' => '1340138101'
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be updated. ";
        $expectederror .= "customenddate value of \"1340138101\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or ";
        $expectederror .= "MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that autocreate validation works on track create
     */
    public function testelistrackinvalidautocreatecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'autocreate', 'customautocreate');

        $this->load_program();

        $data = array(
            'action' => 'create',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'assignment' => 'testprogramidnumber',
            'startdate' => '01/02/2012',
            'enddate' => '01-02-2012',
            'customautocreate' => -1
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be created. ";
        $expectederror .= "customautocreate value of \"-1\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that autocreate validation works on track update
     */
    public function testelistrackinvalidautocreateupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'autocreate', 'customautocreate');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'idnumber' => 'testtrackid',
            'name' => 'testtrack',
            'startdate' => '01/02/2012',
            'enddate' => '01-02-2012',
            'customautocreate' => -1
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"testtrackid\" could not be updated. ";
        $expectederror .= "customautocreate value of \"-1\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that idnumber validation works on track deletion
     */
    public function testelistrackinvalididnumberdelete() {
        $data = array(
            'action' => 'delete',
            'context' => 'track',
            'idnumber' => 'invalidtesttrackid'
        );

        $expectederror = "[track.csv line 2] Track with idnumber \"invalidtesttrackid\" could not be deleted. ";
        $expectederror .= "idnumber value of \"invalidtesttrackid\" does not refer to a valid track.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on track create */
    public function testtrackinvalididentifyingfieldsoncreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'context', 'customcontext');

        $data = array(
            'action' => 'create'
        );
        $expectederror = "[track.csv line 1] Import file track.csv was not processed because it is missing the following ";
        $expectederror .= "required column: customcontext. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'create',
            'customcontext' => ''
        );
        $expectederror = "[track.csv line 2] Entity could not be created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => '',
            'customcontext' => ''
        );
        $expectederror = "[track.csv line 2] Entity could not be processed.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array(
            'customaction' => '',
            'customcontext' => 'track'
        );
        $expectederror = "[track.csv line 2] Track could not be processed. Required field customaction is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'track',
            'customidnumber' => '',
        );
        $expectederror = "[track.csv line 2] Track could not be created. Required fields customassignment, customidnumber, ";
        $expectederror .= "customname are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'track',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[track.csv line 2] Track with idnumber \"testidnumber\" could not be created. Required fields ";
        $expectederror .= "customassignment, customname are unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'track',
            'customidnumber' => 'testidnumber',
            'customname' => 'testname'
        );
        $expectederror = "[track.csv line 2] Track with idnumber \"testidnumber\" could not be created. Required field ";
        $expectederror .= "customassignment is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'track',
            'customidnumber' => 'testidnumber',
            'customassignment' => 'testassignment'
        );
        $expectederror = "[track.csv line 2] Track with idnumber \"testidnumber\" could not be created. Required field customname";
        $expectederror .= " is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on track update */
    public function testtrackinvalididentifyingfieldsonupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'customidnumber' => '',
        );
        $expectederror = "[track.csv line 2] Track could not be updated. Required field customidnumber is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'update',
            'context' => 'track',
            'customidnumber' => 'testidnumber',
        );
        $expectederror = "[track.csv line 2] Track with idnumber \"testidnumber\" could not be updated. idnumber value of ";
        $expectederror .= "\"testidnumber\" does not refer to a valid track.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on track delete */
    public function testtrackinvalididentifyingfieldsondelete() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'delete',
            'context' => 'track',
            'customidnumber' => '',
        );
        $expectederror = "[track.csv line 2] Track could not be deleted. Required field customidnumber is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'delete',
            'context' => 'track',
            'customidnumber' => 'testidnumber',
        );
        $expectederror = "[track.csv line 2] Track with idnumber \"testidnumber\" could not be deleted. idnumber value of ";
        $expectederror .= "\"testidnumber\" does not refer to a valid track.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for an invalid action value for the track
     * entity type
     */
    public function testlogsinvalidtrackaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'track',
            'idnumber' => 'testidnumber'
        );
        $expectedmessage = "[track.csv line 2] Track could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            'crlm_track' => dirname(__FILE__).'/fixtures/tracktable.csv',
            'crlm_curriculum' => dirname(__FILE__).'/fixtures/programtable.csv'
        ));
        $this->loadDataSet($dataset);
    }

    protected function load_program() {
        $dataset = $this->createCsvDataSet(array(
            'crlm_curriculum' => dirname(__FILE__).'/fixtures/programtable.csv'
        ));
        $this->loadDataSet($dataset);
    }
}