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
 * @package    rlipexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

/**
 * File plugin that just stores read records in memory
 */
class rlipexport_version1elis_fileplugin_memoryexport extends rlip_fileplugin_base {
    // Stored data.
    private $data;
    private $writedelay;

    /**
     * Base file plugin constructor - Call parent constructor using all defaults.
     *
     * @param int $writedelay Number of seconds to delay write calls default: 0 (no delay)
     */
    public function __construct($writedelay = 0) {
        $this->writedelay = $writedelay;
        parent::__construct();
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying the mode in which the file should be opened
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
     * @param bool $withpath Whether to include fullpath with filename default is NOT to include full path.
     * @return string The file name.
     */
    public function get_filename($withpath = false) {
        return 'memoryexport';
    }
}

/**
 * Class for testing export database logging for the "Version 1 ELIS" plugin
 * @group block_rlip
 * @group rlipexport_version1elis
 */
class rlipexport_version1elis_databaselogging_testcase extends rlip_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => $csvloc.'/pmcourse.csv',
            pmclass::TABLE => $csvloc.'/pmclass.csv',
            student::TABLE => $csvloc.'/student.csv',
            user::TABLE => $csvloc.'/pmuser.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Run the export for whatever data is currently in the database
     *
     * @param int $targetstarttime The timestamp representing the theoretical time when this task was meant to be run
     * @param int $writedelay The num of seconds delay each write call (passed to rlipexport_version1elis_fileplugin_memoryexport)
     * @param int $lastruntime The last time the export was run (required for incremental scheduled export)
     * @param int $maxruntime The max time in seconds to complete export default: 0 => unlimited
     * @param object $state Previous ran state data to continue from
     * @uses  $CFG
     * @return object State object on error (time limit exceeded) null on success.
     */
    public function run_export($targetstarttime = 0, $writedelay = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // Set the log file location to the dataroot.
        $filepath = $CFG->dataroot;

        // Plugin for file IO.
        $fileplugin = new rlipexport_version1elis_fileplugin_memoryexport($writedelay);
        $fileplugin->open(RLIP_FILE_WRITE);

        // Cleanup log files first.
        self::cleanup_log_files();

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin);
        return $exportplugin->run($targetstarttime, $lastruntime, $maxruntime, $state);
    }

    /**
     * Validate that empty exports still logs to the database
     */
    public function test_exportdblogginglogsemptyexport() {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

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
            'plugin' => 'rlipexport_version1elis',
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
    public function test_exportdblogginglogsnonemptyexport() {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'rlipexport_version1elis');
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
            'plugin' => 'rlipexport_version1elis',
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
     * Validate that database logging works as specified for scheduled export
     * tasks
     */
    public function test_version1dbloggingsetsallfieldsduringscheduledrun() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Set up the export file path.
        $filename = $CFG->dataroot.'/rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        // Create a scheduled job.
        $data = array(
            'plugin' => 'rlipexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'rlipexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Lower bound on starttime.
        $starttime = time();
        // Run the export.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
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
            'plugin' => 'rlipexport_version1',
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
     * Data provider used to specify whether a run is scheduled or manual
     *
     * @return array An array containing a false value representing a scheduled run,
     *               and a true representing a manual run
     */
    public function dataprovider_exporttype() {
        return array(
            array(false),
            array(true)
        );
    }

    /**
     * Validate that an appropriate error is logged when maximum runtime is
     * exceeded during a manual or scheduled export
     *
     * @param boolean $manual True if the run should be manual, or false for
     *                        scheduled
     * @dataProvider dataprovider_exporttype
     */
    public function test_dblogginglogsruntimeexceeded($manual) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/tests/other/rlip_fileplugin_export.class.php');

        // Make sure the export is insensitive to time values.
        set_config('nonincremental', 1, 'rlipexport_version1elis');
        // Set up data for one course and one enroled user.
        $this->load_csv_data();

        $fileplugin = new rlip_fileplugin_export();
        $plugin = rlip_dataplugin_factory::factory('rlipexport_version1elis', null, $fileplugin, $manual);
        // Lower bound on starttime.
        $starttime = time();
        // Suppress output in the "manual" case.
        ob_start();
        // Run the export (note that we don't particularly care about scheduled start time here).
        $plugin->run(0, 0, -1);
        ob_end_clean();
        // Upper bound on endtime.
        $endtime = time();

        // Validation.
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   targetstarttime = :targetstarttime AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency";
        $params = array(
            'export'          => 1,
            'plugin'          => 'rlipexport_version1elis',
            'userid'          => $USER->id,
            'targetstarttime' => 0,
            'starttime'       => $starttime,
            'endtime'         => $endtime,
            'filesuccesses'   => 0,
            'filefailures'    => 0,
            'storedsuccesses' => 0,
            'storedfailures'  => 0,
            'statusmessage'   => 'Export file bogus not created due to time limit exceeded!',
            'dbops'           => -1,
            'unmetdependency' => 0
        );

        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
    }
}
