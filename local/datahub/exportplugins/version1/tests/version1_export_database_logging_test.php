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
 * @package    dhexport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

/**
 * File plugin that just stores read records in memory
 */
class rlip_fileplugin_memoryexport extends rlip_fileplugin_base {
    // Stored data.
    private $data;
    private $writedelay;

    /**
     * Base file plugin constructor
     *
     * @param int   $writedelay  Number of seconds to delay write calls
     *                           default: 0 (no delay)
     * Call parent constructor using all defaults
     */
    public function __construct($writedelay = 0) {
        $this->writedelay = $writedelay;
        parent::__construct();
    }

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
        // Nothing to do.
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        if (!empty($this->writedelay)) {
            sleep($this->writedelay);
        }
        $this->data[] = $entry;
    }

    /**
     * Close the file
     */
    public function close() {
        // Nothing to do.
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
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name.
     */
    public function get_filename($withpath = false) {
        return 'memoryexport';
    }
}

/**
 * Class for testing export database logging for the "version 1" plugin
 * @author brendan
 * @group dhexport_version1
 * @group local_datahub
 */
class version1exportdatabaselogging_testcase extends rlip_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            'grade_items' => $csvloc.'/phpunit_gradeitems.csv',
            'grade_grades' => $csvloc.'/phpunit_gradegrades.csv',
            'user' => $csvloc.'/phpunit_user.csv',
            'course' => $csvloc.'/phpunit_course.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data2() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            'grade_items' => $csvloc.'/phpunit_gradeitems.csv',
            'grade_grades' => $csvloc.'/phpunit_gradegrades2.csv',
            'user' => $csvloc.'/phpunit_user2.csv',
            'course' => $csvloc.'/phpunit_course.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Run the export for whatever data is currently in the database
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $writedelay      The number of seconds delay each write call
     *                             (passed to rlip_fileplugin_memoryexport)
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     * @param int $maxruntime      The max time in seconds to complete export
     *                             default: 0 => unlimited
     * @param object $state        Previous ran state data to continue from
     * @uses  $CFG
     * @return object              state object on error (time limit exceeded)
     *                             null on success!
     */
    public function run_export($targetstarttime = 0, $writedelay = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        global $CFG;
        $file = get_plugin_directory('dhexport', 'version1').'/version1.class.php';
        require_once($file);

        // Set the log file location to the dataroot.
        $filepath = $CFG->dataroot;

        // Plugin for file IO.
        $fileplugin = new rlip_fileplugin_memoryexport($writedelay);
        $fileplugin->open(RLIP_FILE_WRITE);

        // Cleanup log files first.
        self::cleanup_log_files();

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1($fileplugin);
        return $exportplugin->run($targetstarttime, $lastruntime, $maxruntime, $state);
    }

    /**
     * Validate that empty exports still logs to the database
     */
    public function test_version1dblogginglogsemptyexport() {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Lower bound on starttime.
        $starttime = time();
        // Run the export.
        $result = $this->run_export();
        // Upper bound on endtime.
        $endtime = time();

        $this->assertNull($result);

        // Data validation.
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
        $params = array(
            'export' => 1,
            'plugin' => 'dhexport_version1',
            'userid' => $USER->id,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'filesuccesses' => 0,
            'filefailures' => 0,
            'storedsuccesses' => 0,
            'storedfailures' => 0,
            'statusmessage' => 'Export file memoryexport successfully created.',
            'dbops' => -1,
            'unmetdependency' => 0
        );
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that non-empty exports log number of records to the database
     */
    public function test_version1dblogginglogsnonemptyexport() {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Lower bound on starttime.
        $starttime = time();
        // Run the export.
        $result = $this->run_export();
        // Upper bound on endtime.
        $endtime = time();

        $this->assertNull($result);

        // Data validation.
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
        $params = array(
            'export' => 1,
            'plugin' => 'dhexport_version1',
            'userid' => $USER->id,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'filesuccesses' => 1,
            'filefailures' => 0,
            'storedsuccesses' => 0,
            'storedfailures' => 0,
            'statusmessage' => 'Export file memoryexport successfully created.',
            'dbops' => -1,
            'unmetdependency' => 0
        );
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that database logging logs "0" as the target start time when
     * not specified during execution of the version 1 import plugin
     */
    public function test_version1dbloggingtargetstarttimedefaultstozero() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Run the export.
        $result = $this->run_export();
        $this->assertNull($result);

        // Data validation.
        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('targetstarttime' => 0));
        $this->assertTrue($exists);
    }

    /**
     * Validate that database logging logs the specified value as the target
     * start time when specified during execution of the version 1 import plugin
     */
    public function test_version1dbloggingsupportstargetstarttimes() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Run the export.
        $result = $this->run_export(1000000000);
        $this->assertNull($result);

        // Data validation.
        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('targetstarttime' => 1000000000));
        $this->assertTrue($exists);
    }

    /**
     * Validate that export restricts run time to value specified
     */
    public function test_version1exportobeysruntime() {
        global $DB;

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data2();

        // Run the export.
        $result = $this->run_export(1000000000, 3, 0, 1);
        $this->assertNotNull($result); // State object should be returned.
    }

    /**
     * Validate that database logging works as specified for scheduled export
     * tasks
     */
    public function test_version1dbloggingsetsallfieldsduringscheduledrun() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set up the export file path.
        $filename = $CFG->dataroot.'/rliptestexport.csv';
        set_config('export_file', $filename, 'dhexport_version1');

        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Create a scheduled job.
        $data = array(
            'plugin' => 'dhexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'dhexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Lower bound on starttime.
        $starttime = time();
        // Run the export.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);
        // Upper bound on endtime.
        $endtime = time();

        // Data validation.
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
        $datestr = date('M_j_Y_His', $starttime);
        $params = array(
            'export' => 1,
            'plugin' => 'dhexport_version1',
            'userid' => $USER->id,
            'targetstarttime' => 99,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'filesuccesses' => 0,
            'filefailures' => 0,
            'storedsuccesses' => 0,
            'storedfailures' => 0,
            'statusmessage' => 'Export file rliptestexport_'.$datestr.'.csv successfully created.',
            'dbops' => -1,
            'unmetdependency' => 0
        );

        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that when a log file is created, its path is stored in the
     * database summary log record
     */
    public function test_version1exportdbloggingstoreslogpathforexistinglogfile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set up the export file path.
        set_config('export_file', '', 'dhexport_version1');
        // Set up the log file location.
        set_config('logfilelocation', '', 'dhexport_version1');
        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data2();

        // Run the export.
        $result = $this->run_export(1000000000, 3, 0, 1);

        // Validation.
        $select = $DB->sql_like('logpath', ':logpath');
        $params = array('logpath' => $CFG->dataroot.RLIP_DEFAULT_LOG_PATH.'/export_version1_%');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validate that when a log file is not created, a value of null is stored
     * in the database summary log record
     */
    public function test_version1exportdbloggingdoesnotstorelogpathfornonexistentlogfile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set up the export file path.
        set_config('export_file', '', 'dhexport_version1');
        // Set up the log file location.
        set_config('logfilelocation', '', 'dhexport_version1');
        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'dhexport_version1');
        // Set up data for one course and one enroled user.
        $this->load_csv_data2();

        // Run the export.
        $result = $this->run_export(1000000000, 0, 0, 0);

        // Validation.
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, "logpath IS NULL");
        $this->assertTrue($exists);
    }
}