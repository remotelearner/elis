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

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * File plugin that just stores read records in memory 
 */
class rlip_fileplugin_memoryexport extends rlip_fileplugin_base {
    //stored data
    private $data;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->data = array();
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        //nothing to do
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        $this->data[] = $entry;
    }

    /**
     * Close the file
     */
    public function close() {
        //nothing to do
    }

    /**
     * Specifies the data currently stored
     *
     * @return array The data stored
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        return 'memoryexport';
    }
}

/**
 * Class for testing export database logging for the "version 1" plugin
 * @author brendan
 */
class version1ExportDatabaseLoggingTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('grade_items' => 'moodle',
                     'grade_grades' => 'moodle',
                     'user' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'context' => 'moodle',
                     'block_rlip_summary_log' => 'block_rlip',
                     'config_plugins' => 'moodle',
                     'ip_schedule' => 'block_rlip',
                     'elis_scheduled_tasks' => 'elis_core');
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp() {
        parent::setUp();
        //set up contexts and site course record
        $this->setUpContextsTable();
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        $syscontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        self::$overlaydb->import_record('context', $syscontext);

        $site = self::$origdb->get_record('course', array('id' => SITEID));
        self::$overlaydb->import_record('course', $site);
        $sitecontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        self::$overlaydb->import_record('context', $sitecontext);
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    $dataset->addTable('grade_items', dirname(__FILE__).'/phpunit_gradeitems.csv');
	    $dataset->addTable('grade_grades', dirname(__FILE__).'/phpunit_gradegrades.csv');
	    $dataset->addTable('user', dirname(__FILE__).'/phpunit_user.csv');
	    $dataset->addTable('course', dirname(__FILE__).'/phpunit_course.csv');
	    $dataset->addTable('course_categories', dirname(__FILE__).'/phpunit_course_categories.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Run the export for whatever data is currently in the database
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     */
    function run_export($targetstarttime = 0) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1/version1.class.php');

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_memoryexport();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
        $exportplugin = new rlip_exportplugin_version1($fileplugin);
        $exportplugin->run($targetstarttime);
    }

    /**
     * Validate that empty exports still logs to the database
     */
    public function testVersion1DBLoggingLogsEmptyExport() {
        global $USER, $DB;

        //lower bound on starttime
        $starttime = time();
        //run the export
        $this->run_export();
        //upper bound on endtime
        $endtime = time();

        //data validation
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   endtime >= starttime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency";
        $params = array('export' => 1,
                        'plugin' => 'rlipexport_version1',
                        'userid' => $USER->id,
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'filesuccesses' => 0,
                        'filefailures' => 0,
                        'storedsuccesses' => 0,
                        'storedfailures' => 0,
                        'statusmessage' => 'Export file memoryexport successfully created.',
                        'dbops' => -1,
                        'unmetdependency' => 0);
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that non-empty exports log number of records to the database
     */
    public function testVersion1DBLoggingLogsNonemptyExport() {
        global $USER, $DB;

        //make sure the export is insensitive to time values
        set_config('nonincremental', 1, 'rlipexport_version1');
        //set up data for one course and one enroled user
        $this->load_csv_data();

        //lower bound on starttime
        $starttime = time();
        //run the export
        $this->run_export();
        //upper bound on endtime
        $endtime = time();

        //data validation
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   endtime >= starttime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency";
        $params = array('export' => 1,
                        'plugin' => 'rlipexport_version1',
                        'userid' => $USER->id,
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'filesuccesses' => 1,
                        'filefailures' => 0,
                        'storedsuccesses' => 0,
                        'storedfailures' => 0,
                        'statusmessage' => 'Export file memoryexport successfully created.',
                        'dbops' => -1,
                        'unmetdependency' => 0);
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that database logging logs "0" as the target start time when
     * not specified during execution of the version 1 import plugin
     */
    public function testVersion1DBLoggingTargetStartTimeDefaultsToZero() {
        global $DB;

        //make sure the export is insensitive to time values
        set_config('nonincremental', 1, 'rlipexport_version1');
        //set up data for one course and one enroled user
        $this->load_csv_data();

        //run the export
        $this->run_export();

        //data validation
        $exists = $DB->record_exists('block_rlip_summary_log', array('targetstarttime' => 0));
        $this->assertTrue($exists);
    }

    /**
     * Validate that database logging logs the specified value as the target
     * start time when specified during execution of the version 1 import plugin
     */
    public function testVersion1DBLoggingSupportsTargetStartTimes() {
        global $DB;

        //make sure the export is insensitive to time values
        set_config('nonincremental', 1, 'rlipexport_version1');
        //set up data for one course and one enroled user
        $this->load_csv_data();

        //run the export
        $this->run_export(1000000000);

        //data validation
        $exists = $DB->record_exists('block_rlip_summary_log', array('targetstarttime' => 1000000000));
        $this->assertTrue($exists);
    }

    /**
     * Validate that database logging works as specified for scheduled export
     * tasks
     */
    public function testVersion1DBLoggingSetsAllFieldsDuringScheduledRun() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set up the export file path
        $filename = $CFG->dataroot.'/rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        //set up data for one course and one enroled user
        $this->load_csv_data();

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field('ip_schedule', 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record('ip_schedule', $job);

        //lower bound on starttime
        $starttime = time();
        //run the export
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);
        //upper bound on endtime
        $endtime = time();

        //data validation
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   targetstarttime = :targetstarttime AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   endtime >= starttime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency";
        $params = array('export' => 1,
                        'plugin' => 'rlipexport_version1',
                        'userid' => $USER->id,
                        'targetstarttime' => 99,
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'filesuccesses' => 0,
                        'filefailures' => 0,
                        'storedsuccesses' => 0,
                        'storedfailures' => 0,
                        'statusmessage' => 'Export file rliptestexport.csv successfully created.',
                        'dbops' => -1,
                        'unmetdependency' => 0);
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertTrue($exists);
    }
}