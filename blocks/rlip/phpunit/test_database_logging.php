<?php

/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

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
    function customize_record($record, $filename) {
        //no transformation
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     */
    function display_log($record, $filename) {
        //do nothing
    }
}

/**
 * Class for validating generic database logging functionality
 */
class databaseLoggingTest extends elis_database_test {
   /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'files' => 'moodle',
                     'config_plugins', 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('user' => 'moodle',
                     'context' => 'moodle');
    }

    /**
     * Validate that the DB logger uses "0" as the default target start time
     */
    public function testDBLoggeringTargetStartTimeDefaultsToZero() {
        //obtain dblogger
        $dblogger = new rlip_dblogger_test();

        //data validation
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 0);
    }

    /**
     * Validate that the DB logger uses the specified value as the target start
     * time
     */
    public function testDBLoggingSupportsTargetStartTimes() {
        //obtain dblogger
        $dblogger = new rlip_dblogger_test();

        //set target start time
        $dblogger->set_targetstarttime(1000000000);

        //data validation
        $targetstarttime = $dblogger->get_targetstarttime();
        $this->assertEquals($targetstarttime, 1000000000);
    }

    /**
     * Validation for support of missing database columns in the import
     * database logging mechanism
     */
    public function testDBLoggingSupportsMissingColumns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //obtain dblogger
        $dblogger = new rlip_dblogger_import();

        //validate initial state
        $this->assertFalse($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, '');

        //signal a missing column message and flush
        $dblogger->signal_missing_columns('testmessage');

        //validate state
        $this->assertTrue($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, 'testmessage');

        //validate persisted values
        $dblogger->flush('test');
        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => 'testmessage');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);

        //validate state
        $this->assertFalse($dblogger->missingcolumns);
        $this->assertEquals($dblogger->missingcolumnsmessage, '');
    }

    /**
     * Validate that DB logging produces output as the result of a manual import
     */
    public function testDBLoggingProducesOutputForManualImport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set up the import log path to prevent warnings
        set_config('logfilelocation', $CFG->dataroot, 'rlipimport_version1');

        //store it at the system context
        $context = get_context_instance(CONTEXT_SYSTEM);

        //file path and name
        $file_path = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/';
        $file_name = 'userfile.csv';

        //file information
        $fileinfo = array('contextid' => $context->id,
                          'component' => 'system',
                          'filearea'  => 'draft',
                          'itemid'    => 9999,
                          'filepath'  => $file_path,
                          'filename'  => $file_name
                    );

        //create a file in the Moodle file system with the right content
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, "{$file_path}{$file_name}");
        $fileid = $DB->get_field_select('files', 'id', "filename != '.'");

        //set up the import
        $entity_types = array('user', 'bogus', 'bogus');
        $fileids = array($fileid, false, false);
        $importprovider = new rlip_importprovider_moodlefile($entity_types, $fileids);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $importprovider, NULL, true);

        //run the import, collecting output
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that DB logging does not produce output as the result of a scheduled import
     */
    public function testDBLoggingDoesNotProduceOutputForScheduledImport() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_csv.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set up the import
        // MUST copy file to temp area 'cause it'll be deleted after import
        $entity_types = array('user');
        $userfile = 'userfile.csv';
        $testfile = dirname(__FILE__) .'/'. $userfile;
        $tempdir = $CFG->dataroot .'/blocks/rlip/phpunit/';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $tempdir . $userfile);
        $files = array('user' => $tempdir . $userfile);
        $importprovider = new rlip_importprovider_csv($entity_types, $files);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1', $importprovider);

        //run the import, collecting output
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that DB logging does not produce output as the result of a manual export
     */
    public function testDBLoggingDoesNotProduceOutputForManualExport() {
        global $CFG;
        require_once(get_plugin_directory('rlipexport', 'version1').'/phpunit/rlip_fileplugin_nowrite.class.php');

        //set up the export
        $fileplugin = new rlip_fileplugin_nowrite();
        $instance = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin, true);

        //run the export, collecting output
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that DB logging does not produce output as the result of a scheduled export
     */
    public function testDBLoggingDoesNotProduceOutputForScheduledExport() {
        global $CFG;
        require_once(get_plugin_directory('rlipexport', 'version1').'/phpunit/rlip_fileplugin_nowrite.class.php');

        //set up the export
        $fileplugin = new rlip_fileplugin_nowrite();
        $instance = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin);

        //run the export, collecting output
        ob_start();
        $instance->run();
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger produces output when flushing data related to
     * a manual import
     */
    public function testDBLoggerObjectProducesOutputForManualImport() {
        //obtain logging object
        $dblogger = new rlip_dblogger_import(true);

        //flush, collecting output
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a scheduled import
     */
    public function testDBLoggerObjectDoesNotProduceOutputForScheduledImport() {
        //obtain logging object
        $dblogger = new rlip_dblogger_import(false);

        //flush, collecting output
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a manual export
     */
    public function testDBLoggerObjectDoesNotProduceOutputForManualExport() {
        //obtain logging object
        $dblogger = new rlip_dblogger_export(true);

        //flush, collecting output
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the DB logger does not produce output when flushing data
     * related to a scheduled export
     */
    public function testDBLoggerObjectDoesNotProduceOutputForScheduledExport() {
        //obtain logging object
        $dblogger = new rlip_dblogger_export(false);

        //flush, collecting output
        ob_start();
        $dblogger->flush('bogus');
        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }
}
