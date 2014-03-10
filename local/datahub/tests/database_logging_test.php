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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dblogger.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/delay_after_three.class.php');

/**
 * DB logging class used to test the bare minimum functionality of the DB
 * logger parent class
 */
class rlip_dblogger_test extends rlip_dblogger {
    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    public function customize_record($record, $filename) {
        // No transformation.
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     */
    public function display_log($record, $filename) {
        // Do nothing.
    }
}

/**
 * Class for validating generic database logging functionality.
 * @group local_datahub
 */
class databaselogging_testcase extends rlip_test {

    /**
     * Validate that the DB logger uses "0" as the default target start time
     */
    public function test_dbloggeringtargetstarttimedefaultstozero() {
        // Obtain dblogger.
        $dblogger = new rlip_dblogger_test();

        // Data validation.
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 0);
    }

    /**
     * Validate that the DB logger uses the specified value as the target start
     * time
     */
    public function test_dbloggingsupportstargetstarttimes() {
        // Obtain dblogger.
        $dblogger = new rlip_dblogger_test();

        // Set target start time.
        $dblogger->set_targetstarttime(1000000000);

        // Data validation.
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 1000000000);
    }

    /**
     * Validation for support of missing database columns in the import
     * database logging mechanism
     */
    public function test_dbloggingsupportsmissingcolumns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Obtain dblogger.
        $dblogger = new rlip_dblogger_import();

        // Validate initial state.
        $this->assertFalse($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, '');

        // Signal a missing column message and flush.
        $dblogger->signal_missing_columns('testmessage');

        // Validate state.
        $this->assertTrue($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, 'testmessage');

        // Validate persisted values.
        $dblogger->flush('test');
        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => 'testmessage');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);

        // Validate state.
        $this->assertFalse($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, '');
    }

    /**
     * Validate that DB logging produces output as the result of a manual import
     */
    public function test_dbloggingproducesoutputformanualimport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_importprovider_moodlefile.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

        // Store it at the system context.
        $context = context_system::instance();

        // File path and name.
        $filepath = $CFG->dirroot.'/local/datahub/importplugins/version1/tests/fixtures/';
        $filename = 'userfile.csv';

        // File information.
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'system',
            'filearea' => 'draft',
            'itemid' => 9999,
            'filepath' => $filepath,
            'filename' => $filename
        );

        $maxid = $DB->get_field_sql('SELECT id FROM {files} ORDER BY id DESC LIMIT 0, 1');

        // Create a file in the Moodle file system with the right content.
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, "{$filepath}{$filename}");
        $fileid = $DB->get_field_select('files', 'id', "filename != '.' AND id > ?", array($maxid));

        // Set up the import.
        $entitytypes = array('user', 'bogus', 'bogus');
        $fileids = array($fileid, false, false);
        $importprovider = new rlip_importprovider_moodlefile($entitytypes, $fileids);
        $instance = rlip_dataplugin_factory::factory('dhimport_version1', $importprovider, null, true);

        // Run the import, collecting output.
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that DB logging does not produce output as the result of a scheduled import
     */
    public function test_dbloggingdoesnotproduceoutputforscheduledimport() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_importprovider_csv.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

        // Set up the import.
        // MUST copy file to temp area 'cause it'll be deleted after import.
        $entitytypes = array('user');
        $userfile = 'userfile.csv';
        $testfile = dirname(__FILE__).'/'.$userfile;
        $tempdir = $CFG->dataroot.'/local_datahub_phpunit/';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $tempdir.$userfile);
        $files = array('user' => $tempdir.$userfile);
        $importprovider = new rlip_importprovider_csv($entitytypes, $files);
        $instance = rlip_dataplugin_factory::factory('dhimport_version1', $importprovider);

        // Run the import, collecting output.
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');

        // Clean-up temp directory & testfile.
        @unlink($tempdir.$userfile);
        @rmdir($tempdir);
    }

    /**
     * Validate that DB logging does not produce output as the result of a manual export
     */
    public function test_dbloggingdoesnotproduceoutputformanualexport() {
        global $CFG;
        require_once(get_plugin_directory('dhexport', 'version1').'/tests/other/rlip_fileplugin_nowrite.class.php');

        // Set up the export.
        $fileplugin = new rlip_fileplugin_nowrite();
        $instance = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin, true);

        // Run the export, collecting output.
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that DB logging does not produce output as the result of a scheduled export
     */
    public function test_dbloggingdoesnotproduceoutputforscheduledexport() {
        global $CFG;
        require_once(get_plugin_directory('dhexport', 'version1').'/tests/other/rlip_fileplugin_nowrite.class.php');

        // Set up the export.
        $fileplugin = new rlip_fileplugin_nowrite();
        $instance = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin);

        // Run the export, collecting output.
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger produces output when flushing data related to
     * a manual import
     */
    public function test_dbloggerobjectproducesoutputformanualimport() {
        // Obtain logging object.
        $dblogger = new rlip_dblogger_import(true);

        // Flush, collecting output.
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a scheduled import
     */
    public function test_dbloggerobjectdoesnotproduceoutputforscheduledimport() {
        // Obtain logging object.
        $dblogger = new rlip_dblogger_import(false);

        // Flush, collecting output.
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a manual export
     */
    public function test_dbloggerobjectdoesnotproduceoutputformanualexport() {
        // Obtain logging object.
        $dblogger = new rlip_dblogger_export(true);

        // Flush, collecting output.
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a scheduled export
     */
    public function test_dbloggerobjectdoesnotproduceoutputforscheduledexport() {
        // Obtain logging object.
        $dblogger = new rlip_dblogger_export(false);

        // Flush, collecting output.
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that database loggers store db log record ids
     * for later retrieval
     */
    public function test_dbloggerobjectaccumulateslogids() {
        // Obtain the logging object.
        $dblogger = new rlip_dblogger_import();

        for ($i = 0; $i < 3; $i++) {
            // Create some database logs.
            $dblogger->flush('test');
        }

        // Validate that all ids were stored and can be retrieved.
        $logids = $dblogger->get_log_ids();
        $this->assertEquals($logids, array(1, 2, 3));
    }

    /**
     * Validate that, in the special case where runtime is exceeded, the
     * database logger stores the log file path, even if it does not yet exist
     */
    public function test_dbloggerobjectstorespathwhenruntimeexceeded() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Obtain the logging object.
        $dblogger = new rlip_dblogger_import();

        // Set the right logging state.
        $dblogger->set_log_path('/parentdir/childdir');
        $dblogger->signal_maxruntime_exceeded();

        // Write out the database record.
        $dblogger->flush('filename');

        // Validation.
        $select = $DB->sql_compare_text('logpath').' = :logpath';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, array('logpath' => '/parentdir/childdir'));
        $this->assertTrue($exists);
    }

    /**
     * Validate that the version 1 import plugin logs the exact message required to the
     * database when the import runs for too long on a manual run
     */
    public function test_version1manualimportlogsruntimedatabaseerror() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Our import data.
        $data = array(
                array('action', 'username', 'password', 'firstname', 'lastname', 'email', 'city', 'country'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA')
        );

        // Import provider that creates an instance of a file plugin that delays two seconds between reading the third and
        // fourth entry.
        $provider = new rlip_importprovider_delay_after_three_users($data);
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider, null, $manual);

        // We should run out of time after processing the second real entry.
        ob_start();
        // Using three seconds to allow for one slow read when counting lines.
        $importplugin->run(0, 0, 3);
        ob_end_clean();

        $expectedmsg = "Failed importing all lines from import file bogus due to time limit exceeded. Processed 2 of 3 records.";

        // Validation.
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => $expectedmsg);
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }
}
