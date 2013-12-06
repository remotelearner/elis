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
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Test filesystem logging.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisclassfslog_testcase extends rlip_elis_test {

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
        $provider = new $classname($data, 'class.csv');
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, null, true);
        $instance->fslogger = new silent_fslogger(null);
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
     * Validate that start date validation works on class create
     */
    public function test_elisclassstartdatecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'assignment' => 'testcourseid',
            'name' => 'testclassname',
            'customstartdate' => '01-02'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customstartdate ";
        $expectederror .= "value of \"01-02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that start date validation works on class update
     */
    public function test_elisclassstartdateupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'startdate', 'customstartdate');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'assignment' => 'testcourseid',
            'name' => 'testclassname',
            'customstartdate' => '01-02'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customstartdate ";
        $expectederror .= "value of \"01-02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that end date validation works on class create
     */
    public function test_elisclassenddatecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enddate', 'customenddate');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'customenddate' => '01.02'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customenddate ";
        $expectederror .= "value of \"01.02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that end date validation works on class update
     */
    public function test_elisclassenddateupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enddate', 'customenddate');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'customenddate' => '01.02'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customenddate ";
        $expectederror .= "value of \"01.02\" is not a valid date in MM/DD/YYYY, DD-MM-YYYY, YYYY.MM.DD, or MMM/DD/YYYY format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that stat time validation works on class create
     */
    public function test_elisclassstarttimeminutecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'starttimeminute', 'customstarttimeminute');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'customstarttimeminute' => 7
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. ";
        $expectederror .= "customstarttimeminute value of \"7\" is not on a five-minute boundary.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that start time validation works on class create
     */
    public function test_elisclassstarttimeminuteupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'starttimeminute', 'customstarttimeminute');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'customstarttimeminute' => 7
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. ";
        $expectederror .= "customstarttimeminute value of \"7\" is not on a five-minute boundary.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that end time validation works on class create
     */
    public function test_elisclassendtimeminutecreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'endtimeminute', 'customendtimeminute');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'customendtimeminute' => 6
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. ";
        $expectederror .= "customendtimeminute value of \"6\" is not on a five-minute boundary.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that start time validation works on class update
     */
    public function test_elisclassendtimeminuteupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'endtimeminute', 'customendtimeminute');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'customendtimeminute' => 6
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. ";
        $expectederror .= "customendtimeminute value of \"6\" is not on a five-minute boundary.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that max students validation works on class create
     */
    public function test_elisclassmaxstudentscreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'maxstudents', 'custommaxstudents');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'custommaxstudents' => -1
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. ";
        $expectederror .= "custommaxstudents value of \"-1\" is not a non-negative integer.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that max students validation works on class update
     */
    public function test_elisclassmaxstudentsupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'maxstudents', 'custommaxstudents');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'custommaxstudents' => -1
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. ";
        $expectederror .= "custommaxstudents value of \"-1\" is not a non-negative integer.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that enrol from waitlist validation works on class create
     */
    public function test_elisclassenrolfromwaitlistcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enrol_from_waitlist', 'customenrol_from_waitlist');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'customenrol_from_waitlist' => 'invalidflag'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customenrol_";
        $expectederror .= "from_waitlist value of \"invalidflag\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that enrol from waitlist validation works on class update
     */
    public function test_elisclassenrolfromwaitlistupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enrol_from_waitlist', 'customenrol_from_waitlist');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'customenrol_from_waitlist' => 'invalidflag'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customenrol_from";
        $expectederror .= "_waitlist value of \"invalidflag\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }


    /**
     * Validate that track validation works on class create
     */
    public function test_elisclassinvalidtrackcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'customassignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'enrol_from_waitlist' => 1,
            'track' => 'invalidtrack'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customassignment ";
        $expectederror .= "value of \"invalidtrack\" does not refer to a valid track.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that track validation works on class create
     */
    public function test_elisclassinvalidtrackupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'customassignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'enrol_from_waitlist' => 1,
            'track' => 'invalidtrack'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customassignment ";
        $expectederror .= "value of \"invalidtrack\" does not refer to a valid track.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that autoenrol validation works on class create
     */
    public function test_elisclassautoenrolcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enrol_from_waitlist', 'customenrol_from_waitlist');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'customenrol_from_waitlist' => 3
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customenrol_";
        $expectederror .= "from_waitlist value of \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that autoenrol validation works on class update
     */
    public function test_elisclassautoenrolupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'enrol_from_waitlist', 'customenrol_from_waitlist');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'customenrol_from_waitlist' => 3
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customenrol_";
        $expectederror .= "from_waitlist value of \"3\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that link validation works on class create
     */
    public function test_elisclasslinkcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'link', 'customlink');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $data = array(
            'action' => 'create',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'enrol_from_waitlist' => 1,
            'customlink' => 'invalidmoodlecourse'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be created. customlink ";
        $expectederror .= "value of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that link validation works on class update
     */
    public function test_elisclasslinkupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'link', 'customlink');

        $record = new stdClass;
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoursename';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'idnumber' => 'testclassid',
            'name' => 'testclassname',
            'assignment' => 'testcourseid',
            'startdate' => '01-02-2012',
            'enddate' => '2012.01.02',
            'starttimehour' => 5,
            'endtimeminute' => 5,
            'maxstudents' => 30,
            'enrol_from_waitlist' => 1,
            'customlink' => 'invalidmoodlecourse'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be updated. customlink value";
        $expectederror .= " of \"invalidmoodlecourse\" does not refer to a valid Moodle course.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on class create */
    public function test_classinvalididentifyingfieldsoncreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'context', 'customcontext');

        $data = array(
            'action' => 'create'
        );
        $expectederror = "[class.csv line 1] Import file class.csv was not processed because it is missing the following required";
        $expectederror .= " column: customcontext. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'create',
            'customcontext' => ''
        );
        $expectederror = "[class.csv line 2] Entity could not be created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => '',
            'customcontext' => ''
        );
        $expectederror = "[class.csv line 2] Entity could not be processed.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'assignment', 'customassignment');

        $data = array(
            'action' => 'create',
            'customcontext' => 'class',
            'idnumber' => 'testidnumber'
        );
        $expectederror = "[class.csv line 2] Class instance with idnumber \"testidnumber\" could not be created. Required field";
        $expectederror .= " customassignment is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'create',
            'customcontext' => 'class',
            'customassignment' => 'testassignment'
        );
        $expectederror = "[class.csv line 2] Class instance could not be created. Required field customidnumber is unspecified ";
        $expectederror .= "or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on class update */
    public function test_classinvalididentifyingfieldsonupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'customidnumber' => ''
        );
        $expectederror = "[class.csv line 2] Class instance could not be updated. Required field customidnumber is unspecified ";
        $expectederror .= "or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'update',
            'context' => 'class',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[class.csv line 2] Class instance with idnumber \"testidnumber\" could not be updated. customidnumber ";
        $expectederror .= "value of \"testidnumber\" does not refer to a valid class instance.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on class delete */
    public function test_classinvalididentifyingfieldsondelete() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'delete',
            'context' => 'class',
            'customidnumber' => ''
        );
        $expectederror = "[class.csv line 2] Class instance could not be deleted. Required field customidnumber is unspecified ";
        $expectederror .= "or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'delete',
            'context' => 'class',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[class.csv line 2] Class instance with idnumber \"testidnumber\" could not be deleted. customidnumber";
        $expectederror .= " value of \"testidnumber\" does not refer to a valid class instance.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that class deletion validation works
     */
    public function test_elisclassdelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'delete',
            'context' => 'class',
            'customidnumber' => 'testclassid'
        );

        $expectederror = "[class.csv line 2] Class instance with idnumber \"testclassid\" could not be deleted. customidnumber";
        $expectederror .= " value of \"testclassid\" does not refer to a valid class instance.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for an invalid action value for the class
     * entity type
     */
    public function test_logsinvalidclassaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'class',
            'idnumber' => 'testclassid'
        );
        $expectedmessage = "[class.csv line 2] Class instance could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array('crlm_class' => dirname(__FILE__).'/fixtures/classtable.csv'));
        $this->loadDataSet($dataset);
    }
}