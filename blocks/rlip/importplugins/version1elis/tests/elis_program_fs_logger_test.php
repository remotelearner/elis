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
 * Test program filesystem logging.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisprogramfslog_testcase extends rlip_elis_test {

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
        $provider = new $classname($data, 'program.csv');
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
     * Validate that required credits validation works on program create
     */
    public function testelisprograminvalidreqcreditsformatcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'customreqcredits' => '10.000'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customreqcredits value of \"10.000\" is not a number with at most ten total digits and two decimal ";
        $expectederror .= "digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testelisprograminvalidreqcreditsformatupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'customreqcredits' => '10.000'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customreqcredits value of \"10.000\" is not a number with at most ten total digits and two decimal ";
        $expectederror .= "digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that required credits validation works on program create
     */
    public function testelisprograminvalidreqcreditsformat2create() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'customreqcredits' => '10.0.0'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customreqcredits value of \"10.0.0\" is not a number with at most ten total digits and two decimal ";
        $expectederror .= "digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testelisprograminvalidreqcreditsformat2update() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'customreqcredits' => '10.0.0'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customreqcredits value of \"10.0.0\" is not a number with at most ten total digits and two decimal ";
        $expectederror .= "digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that required credits validation works on program create
     */
    public function testelisprograminvalidreqcreditsformat3create() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprorgam',
            'customreqcredits' => '100000000000.00'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customreqcredits value of \"100000000000.00\" is not a number with at most ten total digits and two ";
        $expectederror .= "decimal digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that required credits validation works on program update
     */
    public function testelisprograminvalidreqcreditsformat3update() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprorgam',
            'customreqcredits' => '100000000000.00'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customreqcredits value of \"100000000000.00\" is not a number with at most ten total digits and two ";
        $expectederror .= "decimal digits.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that time to complete validation works on program create
     */
    public function testelisprograminvalidtimetocompleteformat1create() {
        // Create mapping record.
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'customtimetocomplete' => '1x'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customtimetocomplete value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that time to complete validation works on program update
     */
    public function testelisprograminvalidtimetocompleteformat1update() {
        // Create mapping record.
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'customtimetocomplete' => '1x'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customtimetocomplete value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that time to complete validation works on program create
     */
    public function testelisprograminvalidtimetocompleteformat2create() {
        // Create mapping record.
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprorgam',
            'reqcredits' => '10',
            'customtimetocomplete' => '1'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customtimetocomplete value of \"1\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that time to complete validation works on program update
     */
    public function testelisprograminvalidtimetocompleteformat2update() {
        // Create mapping record.
        $this->create_mapping_record('course', 'timetocomplete', 'customtimetocomplete');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'customtimetocomplete' => '1'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customtimetocomplete value of \"1\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that frequency validation works on program create
     */
    public function testelisprogramfrequencyconfignotsetcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'customfrequency' => '1d'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "Program customfrequency / expiration cannot be set because program expiration is globally disabled.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that frequency validation works on program update
     */
    public function testelisprogramfrequencyconfignotsetupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'customfrequency' => '1d'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "Program customfrequency / expiration cannot be set because program expiration is globally disabled.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that frequency validation works on program create
     */
    public function testelisprograminvalidfrequencycreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'customfrequency' => '1x'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "customfrequency value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that frequency validation works on program updatee
     */
    public function testelisprograminvalidfrequencyupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'frequency', 'customfrequency');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'customfrequency' => '1x'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "customfrequency value of \"1x\" is not a valid time delta in *h, *d, *w, *m, *y format.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that priority validation works on program create
     */
    public function testelisprograminvalidprioritycreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'priority', 'custompriority');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'frequency' => '1d',
            'custompriority' => 11
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be created. ";
        $expectederror .= "custompriority value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that priority validation works on program update
     */
    public function testelisprograminvalidpriorityupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'priority', 'custompriority');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber',
            'name' => 'testprogram',
            'reqcredits' => '10',
            'timetocomplete' => '1d',
            'frequency' => '1d',
            'custompriority' => 11
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"testprogramidnumber\" could not be updated. ";
        $expectederror .= "custompriority value of \"11\" is not one of the available options (0 .. 10).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that validation works on program delete
     */
    public function testelisprograminvalidprogramiddelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'reqcredits', 'customreqcredits');

        $this->load_csv_data();

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum',
            'idnumber' => 'invalidtestprogramidnumber'
        );

        $expectederror = "[program.csv line 2] Program with idnumber \"invalidtestprogramidnumber\" could not be deleted. ";
        $expectederror .= "idnumber value of \"invalidtestprogramidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program create */
    public function testprograminvalididentifyingfieldsoncreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'context', 'customcontext');

        $data = array(
            'action' => 'create'
        );
        $expectederror = "[program.csv line 1] Import file program.csv was not processed because it is missing the following ";
        $expectederror .= "required column: customcontext. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'create',
            'customcontext' => ''
        );
        $expectederror = "[program.csv line 2] Entity could not be created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => '',
            'customcontext' => ''
        );
        $expectederror = "[program.csv line 2] Entity could not be processed.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array(
            'customaction' => '',
            'customcontext' => 'curriculum'
        );
        $expectederror = "[program.csv line 2] Program could not be processed. Required field customaction is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customidnumber' => '',
        );
        $expectederror = "[program.csv line 2] Program could not be created. Required fields customidnumber, customname are ";
        $expectederror .= "unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customidnumber' => 'testidnumber'
        );
        $expectederror = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be created. Required field";
        $expectederror .= " customname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'curriculum',
            'customname' => 'testname'
        );
        $expectederror = "[program.csv line 2] Program could not be created. Required field customidnumber is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program update */
    public function testtrackinvalididentifyingfieldsonupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'customidnumber' => '',
        );
        $expectederror = "[program.csv line 2] Program could not be updated. Required field customidnumber is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'update',
            'context' => 'curriculum',
            'customidnumber' => 'testidnumber',
        );
        $expectederror = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be updated. customidnumber value";
        $expectederror .= " of \"testidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on program delete */
    public function testtrackinvalididentifyingfieldsondelete() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'idnumber', 'customidnumber');

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum',
            'customidnumber' => '',
        );
        $expectederror = "[program.csv line 2] Program could not be deleted. Required field customidnumber is unspecified or ";
        $expectederror .= "empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'delete',
            'context' => 'curriculum',
            'customidnumber' => 'testidnumber',
        );
        $expectederror = "[program.csv line 2] Program with idnumber \"testidnumber\" could not be deleted. customidnumber value";
        $expectederror .= " of \"testidnumber\" does not refer to a valid program.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for an invalid action value for the program
     * entity type
     */
    public function testlogsinvalidprogramaction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'curriculum',
            'idnumber' => 'testprogramidnumber'
        );
        $expectedmessage = "[program.csv line 2] Program could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array('crlm_curriculum' => dirname(__FILE__).'/fixtures/programtable.csv'));
        $this->loadDataSet($dataset);
    }
}