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
 * Test cluster import filesystem logging.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class version1elisclusterfslog_testcase extends rlip_elis_test {

    /**
     * Called before class.
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
        $provider = new $classname($data, 'userset.csv');
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
     * Validate that cluster already exists on create
     */
    public function testelisexisitngclustercreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $this->load_csv_data();

        $data = array(
            'action' => 'create',
            'context' => 'cluster',
            'customname' => 'testcluster'
        );

        $expectederror = "[userset.csv line 2] User set with name \"testcluster\" could not be created. customname value of";
        $expectederror .= " \"testcluster\" refers to a user set that already exists.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that cluster doesn't exist on delete
     */
    public function testelisinvalidclusterdelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'action' => 'delete',
            'context' => 'cluster',
            'customname' => 'invalidtestcluster'
        );

        $expectederror = "[userset.csv line 2] User set with name \"invalidtestcluster\" could not be deleted. customname value";
        $expectederror .= " of \"invalidtestcluster\" does not refer to a valid user set.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate that cluster doesn't exist on update
     */
    public function testelisinvalidclusterupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'action' => 'update',
            'context' => 'cluster',
            'customname' => 'invalidtestcluster'
        );

        $expectederror = "[userset.csv line 2] User set with name \"invalidtestcluster\" could not be updated. customname value";
        $expectederror .= " of \"invalidtestcluster\" does not refer to a valid user set.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate recursive validation on cluster delete
     */
    public function testelisclusterrecursivedelete() {
        // Create mapping record.
        $this->create_mapping_record('course', 'recursive', 'customrecursive');

        $this->load_csv_data();

        $data = array(
            'action' => 'delete',
            'context' => 'cluster',
            'name' => 'testcluster',
            'customrecursive' => 2
        );

        $expectederror = "[userset.csv line 2] User set with name \"testcluster\" could not be deleted. customrecursive value";
        $expectederror .= " of \"2\" is not one of the available options (0, 1).\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate invalid parent on cluster creation
     */
    public function testelisclusterinvalidparentcreate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'parent', 'customparent');

        $data = array(
            'action' => 'create',
            'context' => 'cluster',
            'name' => 'testcluster',
            'customparent' => 'invalidparent'
        );

        $expectederror = "[userset.csv line 2] User set with name \"testcluster\" could not be created. customparent value ";
        $expectederror .= "of \"invalidparent\" should refer to a valid user set, or be set to \"top\" to place this user set at";
        $expectederror .= " the top level.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate invalid parent on cluster update
     */
    public function testelisclusterinvalidparentupdate() {
        // Create mapping record.
        $this->create_mapping_record('course', 'parent', 'customparent');

        $this->load_csv_data();

        $data = array(
            'action' => 'update',
            'context' => 'cluster',
            'name' => 'testcluster',
            'customparent' => 'invalidparent'
        );

        $expectederror = "[userset.csv line 2] User set with name \"testcluster\" could not be updated. customparent value ";
        $expectederror .= "of \"invalidparent\" should refer to a valid user set, or be set to \"top\" to place this user set at";
        $expectederror .= " the top level.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on cluster create */
    public function testclusterinvalididentifyingfieldsoncreate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'context', 'customcontext');

        $data = array(
            'action' => 'create'
        );
        $expectederror = "[userset.csv line 1] Import file userset.csv was not processed because it is missing the following";
        $expectederror .= " required column: customcontext. Please fix the import file and re-upload it.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'create',
            'customcontext' => ''
        );
        $expectederror = "[userset.csv line 2] Entity could not be created.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => '',
            'customcontext' => ''
        );
        $expectederror = "[userset.csv line 2] Entity could not be processed.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        $data = array(
            'customaction' => '',
            'customcontext' => 'cluster'
        );
        $expectederror = "[userset.csv line 2] User set could not be processed. Required field customaction is unspecified or";
        $expectederror .= " empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'customaction' => 'create',
            'customcontext' => 'cluster',
            'customname' => '',
        );
        $expectederror = "[userset.csv line 2] User set could not be created. Required field customname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on cluster update */
    public function testtrackinvalididentifyingfieldsonupdate() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'action' => 'update',
            'context' => 'cluster',
            'customname' => '',
        );
        $expectederror = "[userset.csv line 2] User set could not be updated. Required field customname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'update',
            'context' => 'cluster',
            'customname' => 'testname',
        );
        $expectederror = "[userset.csv line 2] User set with name \"testname\" could not be updated. customname value of";
        $expectederror .= " \"testname\" does not refer to a valid user set.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /* Test that the correct error messages are shown for the provided fields on cluster delete */
    public function testtrackinvalididentifyingfieldsondelete() {
        global $CFG, $DB;

        // Create mapping record.
        $this->create_mapping_record('course', 'name', 'customname');

        $data = array(
            'action' => 'delete',
            'context' => 'cluster',
            'customname' => '',
        );
        $expectederror = "[userset.csv line 2] User set could not be deleted. Required field customname is unspecified or empty.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');

        $data = array(
            'action' => 'delete',
            'context' => 'cluster',
            'customname' => 'testname',
        );
        $expectederror = "[userset.csv line 2] User set with name \"testname\" could not be deleted. customname value of";
        $expectederror .= " \"testname\" does not refer to a valid user set.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Validate log message for an invalid action value for the program
     * entity type
     */
    public function testlogsinvalidclusteraction() {
        // Data.
        $data = array(
            'action' => 'bogus',
            'context' => 'cluster',
            'name' => 'testcluster'
        );
        $expectedmessage = "[userset.csv line 2] User set could not be processed. Action of \"bogus\" is not supported.\n";

        // Validation.
        $this->assert_data_produces_error($data, $expectedmessage, 'course');
    }

    protected function load_csv_data() {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        $dataset = $this->createCsvDataSet(array(userset::TABLE => dirname(__FILE__).'/fixtures/clustertable.csv'));
        $this->loadDataSet($dataset);
    }
}