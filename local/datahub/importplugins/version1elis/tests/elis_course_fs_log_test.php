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
 * Test course import filesystem logging.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class version1eliscoursefslog_testcase extends rlip_elis_test {

    /**
     * Called before each test function
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

    /**
     * Validate that link validation works on course create
     */
    public function testeliscourseinvalidlinkcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'link', 'customlink');

        $data = array(
            'action' => 'create',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcourse',
            'customlink' => 'invalidmoodlecourse'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. customlink";
        $expectederror .= " value of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that link validation works on course update
     */
    public function testeliscourseinvalidlinkupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'link', 'customlink');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'customlink' => 'invalidmoodlecourse'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. customlink";
        $expectederror .= " value of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that link validation works on course create
     */
    public function testeliscourseinvalidassignmentcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');

        $data = array(
            'action' => 'create',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcourse',
            'customassignment' => 'invalidprogram'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. ";
        $expectederror .= "customassignment value of \"invalidprogram\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that assignment validation works on course update
     */
    public function testeliscourseinvalidassignmentupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcourse',
            'customassignment' => 'invalidprogram'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. ";
        $expectederror .= "customassignment value of \"invalidprogram\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that credit validation works on course creation
     */
    public function testeliscourseinvalidcreditcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'credits', 'customcredits');

        $data = array(
            'action' => 'create',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcourse',
            'customcredits' => '-1'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. ";
        $expectederror .= "customcredits value of \"-1\" is not a non-negative number.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that credit validation works on course update
     */
    public function testeliscourseinvalidcreditupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'credits', 'customcredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'customcredits' => '-1'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. ";
        $expectederror .= "customcredits value of \"-1\" is not a non-negative number.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that completion grade validation works on course create
     */
    public function testeliscourseinvalidcompletiongradecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'completion_grade', 'customcompletion_grade');

        $data = array(
            'action' => 'create',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'name' => 'testcourse',
            'credits' => '1',
            'customcompletion_grade' => '-1'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be created. ";
        $expectederror .= "customcompletion_grade value of \"-1\" is not one of the available options (0 .. 100).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that completion grade validation works on course update
     */
    public function testeliscourseinvalidcompletiongradeupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'completion_grade', 'customcompletion_grade');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'course',
            'idnumber' => 'testcourseid',
            'credits' => '1',
            'customcompletion_grade' => '-1'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"testcourseid\" could not be updated. ";
        $expectederror .= "customcompletion_grade value of \"-1\" is not one of the available options (0 .. 100).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate idnumber validation works on course update
     */
    public function testeliscourseinvalididnumberupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'course',
            'customidnumber' => 'invalidtestcourseid'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"invalidtestcourseid\" could not be updated. ";
        $expectederror .= "customidnumber value of \"invalidtestcourseid\" does not refer to a valid course description.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate idnumber validation works on course delete
     */
    public function testeliscourseinvalididnumberdelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $this->load_csv_data();

        $data = array(
            'action' => 'delete',
            'context' => 'course',
            'customidnumber' => 'invalidtestcourseid'
        );

        $expectederror = "[course.csv line 2] Course description with idnumber \"invalidtestcourseid\" could not be deleted. ";
        $expectederror .= "customidnumber value of \"invalidtestcourseid\" does not refer to a valid course description.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for an invalid action value for the course
     * entity type
     */
    public function testlogsinvalidcourseaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'course',
            'shortname' => 'testshortname'
        );
        $expectedmessage = "[course.csv line 2] Course could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    protected function load_csv_data() {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));

        $dataset = $this->createCsvDataSet(array(course::TABLE => dirname(__FILE__).'/fixtures/coursetable.csv'));
        $this->loadDataSet($dataset);
    }
}