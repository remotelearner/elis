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
require_once($CFG->dirroot .'/blocks/rlip/lib.php');
require_once($CFG->dirroot .'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot .'/elis/core/lib/testlib.php');

/**
 * Class for testing utility methods
 */
class utilityMethodTest extends rlip_test {

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array(RLIP_SCHEDULE_TABLE => 'block_rlip',
                     'elis_scheduled_tasks' => 'elis_core',
                     'config_plugins' => 'moodle',
                     RLIP_LOG_TABLE => 'block_rlip',
                     'user' => 'moodle',
                     'config' => 'moodle',
                     'grade_grades' => 'moodle',
                     'grade_items' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('context' => 'moodle');
    }

    /**
     * Create test user record
     */
    protected function create_test_user() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/user.csv');
        load_phpunit_data_set($dataset, true);
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_export_csv_data() {
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    $dataset->addTable('grade_items', dirname(__FILE__).'/../exportplugins/version1/phpunit/phpunit_gradeitems.csv');
	    $dataset->addTable('grade_grades', dirname(__FILE__).'/../exportplugins/version1/phpunit/phpunit_gradegrades.csv');
	    $dataset->addTable('user', dirname(__FILE__).'/../exportplugins/version1/phpunit/phpunit_user.csv');
	    $dataset->addTable('course', dirname(__FILE__).'/../exportplugins/version1/phpunit/phpunit_course.csv');
	    $dataset->addTable('course_categories', dirname(__FILE__).'/../exportplugins/version1/phpunit/phpunit_course_categories.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Create a test database log record
     *
     * @param array $extradata Extra fields / overrides to set
     */
    protected function create_db_log($extradata = array()) {
        global $DB;

        //calculate data
        $basedata = array('export' => 1,
                          'plugin' => 'rlipexport_version1',
                          'userid' => 1,
                          'targetstarttime' => 1000000001,
                          'starttime' => 1000000002,
                          'endtime' => 1000000003,
                          'filesuccesses' => 1,
                          'filefailures' => 2,
                          'storedsuccesses' => 3,
                          'storedfailures' => 4,
                          'statusmessage' => 'testmessage',
                          'dbops' => 10,
                          'unmetdependency' => 1);
        $basedata = array_merge($basedata, $extradata);

        //insert record
        $record = (object)$basedata;
        $DB->insert_record(RLIP_LOG_TABLE, $record);
    }

    /**
     * Validate that time string sanitization sets the default when input
     * string is empty
     */
    function testSanitizeTimeStringProvidesDefault() {
        $result = rlip_sanitize_time_string('', '1d');
        $this->assertEquals($result, '1d');
    }

    /**
     * Validate that time string sanitization leaves valid strings unchanged
     */
    function testSanitizeTimeStringLeavesValidStringUnchanged() {
        $result = rlip_sanitize_time_string('1d2h3m', '1d');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the beginning of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromStart() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the middle of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromMiddle() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the end of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromEnd() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes non-portion characters
     * from all parts of a time string 
     */
    function testSanitizeTimeStringRemovesNonPortionCharacters() {
        $result = rlip_sanitize_time_string('1d, 2h, 3m');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization converts letters to lowercase
     */
    function testSanitizeTimeStringConvertsLettersToLowercase() {
        $result = rlip_sanitize_time_string('1D2H3M');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization prevents multiple units beside
     * one another
     */
    function testSanitizeTimeStringPreventsConsecutiveUnits() {
        $result = rlip_sanitize_time_string('1d2h3mm');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that converting time string to offset works for number of
     * days
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForDays() {
        $result = rlip_time_string_to_offset('2d');
        $this->assertEquals($result, 2 * DAYSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * hours
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForHours() {
        $result = rlip_time_string_to_offset('2h');
        $this->assertEquals($result, 2 * HOURSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * minutes
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForMinutes() {
        $result = rlip_time_string_to_offset('2m');
        $this->assertEquals($result, 2 * MINSECS);
    }

    /**
     * Validate that converting time string to offset works for complex string
     * with hours, minutes and seconds
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForComplexString() {
        $result = rlip_time_string_to_offset('1d2h3m');
        $this->assertEquals($result, DAYSECS + 2 * HOURSECS + 3 * MINSECS);
    }

    /**
     * Data provider for test_rlip_schedule_period_minutes()
     */
    public static function period_minutes_provider() {
        return array(
            array('1x', -1),
            array('1m', 1),
            array('5m', 5),
            array('10m', 10),
            array('1h', HOURSECS/60),
            array('1d', DAYSECS/60),
            array('2d3h4m', DAYSECS/30 + (HOURSECS * 3)/60 + 4),
            array('9m 8d 7h', (DAYSECS * 8)/60 + (HOURSECS * 7)/60 + 9),
            array('9h  8m  7d', (DAYSECS * 7)/60 + (HOURSECS * 9)/60 + 8),
            array('4	d 5h	6m', (DAYSECS * 4)/60 + (HOURSECS * 5)/60 + 6),
            array('7 d 8 h 9 m', (DAYSECS * 7)/60 + (HOURSECS * 8)/60 + 9),
            array('20d23h45m', DAYSECS/3 + (HOURSECS * 23)/60 + 45),
            array('2a3b4c', -1)
        );
    }

    /**
     * Test library function: rlip_schedule_period_minutes()
     * @dataProvider period_minutes_provider
     */
    function test_rlip_schedule_period_minutes($a, $b) {
        $this->assertEquals(rlip_schedule_period_minutes($a), $b);
    }

    /**
     * Validate that adding a new job sets the right next runtime on the IP
     * schedule record
     */
    function testAddingJobSetsIPNextRuntime() {
        global $DB;

        //a lower bound for the start time
        $starttime = time();

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');

        //obtain scheduled task info
        $taskid = rlip_schedule_add_job($data);
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));

        //obtain IP job info
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        //make sure the next runtime is between 5 and 6 minutes from now
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        //make sure both records have the same next run time
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that the "add job" method also supports updates 
     */
    function testUpdatingJob() {
        global $DB;

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $data['id'] = rlip_schedule_add_job($data);

        //update the job
        $data['plugin'] = 'bogusplugin';
        $data['userid'] = 9999;
        rlip_schedule_add_job($data);

        //data validation
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $data['id']));
        $this->assertEquals($job->plugin, 'bogusplugin');
        $this->assertEquals($job->userid, 9999);
    }

    /**
     * Validate that the "delete job" method works
     */
    public function testDeletingJob() {
        global $DB;

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $jobid = rlip_schedule_add_job($data);

        //setup validation
        $this->assertEquals($DB->count_records(RLIP_SCHEDULE_TABLE), 1);
        $this->assertEquals($DB->count_records('elis_scheduled_tasks'), 1);

        //delete the job
        rlip_schedule_delete_job($jobid);

        //data validation
        $this->assertEquals($DB->count_records(RLIP_SCHEDULE_TABLE), 0);
        $this->assertEquals($DB->count_records('elis_scheduled_tasks'), 0);
    }

    /**
     * Validate that scheduled jobs are retrieved via API call
     */
    public function testGetScheduledJobs() {
        global $CFG, $DB;

        //create a user
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->firstname = 'rlipfirstname';
        $user->lastname = 'rliplastname';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Rlippassword!1234';
        $user->timezone = -5.0;

        $userid = user_create_user($user);

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport',
                      'userid' => $userid);
        $starttime = time();
        $ipid = rlip_schedule_add_job($data);
        $endtime = time();

        //fetch jobs
        $recordset = rlip_get_scheduled_jobs($data['plugin']);

        //data validation
        $this->assertTrue($recordset->valid());
        
        $current = $recordset->current();
        //ip schedule fields
        $this->assertEquals($current->plugin, $data['plugin']);
        //user fields
        $this->assertEquals($current->username, $user->username);
        $this->assertEquals($current->firstname, $user->firstname);
        $this->assertEquals($current->lastname, $user->lastname);
        $this->assertEquals($current->timezone, $user->timezone);
        $this->assertEquals($current->lastruntime, 0);
        //elis scheduled task field
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$current->nextruntime);
        $this->assertGreaterThanOrEqual((int)$current->nextruntime, $endtime + 5 * MINSECS);
    }

    /**
     * Validate that IP correctly updates last runtime values
     */
    public function testRunningJobSetsIPLastRuntime() {
        global $CFG, $DB;

        //set up the export file & path
        set_config('export_path', '', 'rlipexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'rlipexport_version1');

        set_config('disableincron', 0, 'block_rlip');

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //change the last runtime to a value that is out of range on both records
        $task = new stdClass;
        $task->id = $taskid;
        //this is the value that will be transferred onto the job record after the run
        $task->lastruntime = 1000000000;
        $task->nextruntime = 0;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->lastruntime = 0;
        $job->nextruntime = 0;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //obtain both records
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        //validate that the value was obtained from the elis scheduled task
        $this->assertEquals($job->lastruntime, 1000000000);
    }

    /**
     * Validate that running a job sets the right next runtime on the IP
     * schedule record 
     */
    function testRunningJobSetsIPNextRuntime() {
        global $CFG, $DB;

        //set up the export file & path
        set_config('export_path', '', 'rlipexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'rlipexport_version1');

        set_config('disableincron', 0, 'block_rlip');

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        $timenow = time();
        //change the next runtime to a value that is out of range on both records
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = $timenow;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = $timenow;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $starttime = time();
        run_ipjob($taskname);

        //obtain both records
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        //make sure the next runtime is between 5 and 6 minutes from initial value
        //echo "\nStartTime={$starttime}; nextruntime={$task->nextruntime}\n";
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        //make sure both records have the same next run time
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that failed run due to disabled cron correctly resets
     * scheduling times
     */
    function testRunningJobsFixesELISScheduledTaskWhenExternalCronEnabled() {
        global $CFG, $DB;

        //set up the export file & path
        set_config('export_path', '', 'rlipexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'rlipexport_version1');

        //enable external cron
        set_config('disableincron', 1, 'block_rlip');

        //set up the tasks
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //set both tasks' times to known states
        $task = new stdClass;
        $task->id = $taskid;
        $task->lastruntime = 0;
        $task->nextruntime = 0;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->lastruntime = 1000000000;
        $job->nextruntime = 1000000001;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //obtain both records
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        //validate all times
        $this->assertEquals((int)$task->lastruntime, 1000000000);
        $this->assertEquals((int)$task->nextruntime, 1000000001);
        $this->assertEquals((int)$task->lastruntime, (int)$job->lastruntime);
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that running out of time correctly resets scheduling times
     */
    function testRunningJobsResetsStateWhenTimeExceeded() {
        global $CFG, $DB;

        //set the log file location to the dataroot
        $filepath = $CFG->dataroot;
        set_config('logfilelocation', $filepath, 'rlipexport_version1');

        //enable internal cron
        set_config('disableincron', 0, 'block_rlip');

        //nonincremental export
        set_config('nonincremental', 1, 'rlipexport_version1');

        //load in data needed for export
        $this->load_export_csv_data();

        //set up the export file & path
        set_config('export_path', '', 'rlipexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'rlipexport_version1');

        //set up the tasks
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //set both tasks' times to known states
        $task = new stdClass;
        $task->id = $taskid;
        $task->lastruntime = 0;
        $task->nextruntime = 0;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'rlipexport_version1'));
        $job->lastruntime = 1000000000;
        $job->nextruntime = 1000000001;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //run the job with an impossibly small time limit
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname, -1);

        //obtain job record
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        //validate all times
        $this->assertEquals((int)$task->lastruntime, 1000000000);
        $this->assertEquals((int)$task->nextruntime, 1000000001);
        $this->assertEquals((int)$task->lastruntime, (int)$job->lastruntime);
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that log records without associated users are not retrieved
     */
    function testCountLogsRequiresUserRecord() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create log record
        $this->create_db_log();

        //validation
        $count = rlip_count_logs();
        $this->assertEquals($count, 0);
    }

    /**
     * Validate that log records with associated users are retrieved
     */
    function testCountLogsReturnsCorrectCount() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log();

        //validation
        $count = rlip_count_logs();
        $this->assertEquals($count, 1);
    }

    /**
     * Validates that log retrieval returns an empty recordset when no data is
     * available
     */
    function testGetLogsReturnsEmptyRecordset() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //fetch supposedly empty set
        $logs = rlip_get_logs();

        //data validation
        $this->assertFalse($logs->valid());
    }

    /**
     * Validates that log retrieval returns a valid recordset when data is
     * available
     */
    function testGetLogsReturnsValidRecordset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log();

        //validate that at least one record is returned
        $logs = rlip_get_logs();
        $this->assertTrue($logs->valid());

        //validate record data
        $dbrecord = $logs->current();
        unset($dbrecord->id);
        $record = new stdClass;
        $record->export = 1;
        $record->plugin = 'rlipexport_version1';
        $record->userid = 1;
        $record->targetstarttime = 1000000001;
        $record->starttime = 1000000002;
        $record->endtime = 1000000003;
        $record->filesuccesses = 1;
        $record->filefailures = 2;
        $record->storedsuccesses = 3;
        $record->storedfailures = 4;
        $record->statusmessage = 'testmessage';
        $record->dbops = 10;
        $record->unmetdependency = 1;
        $record->firstname = 'Test';
        $record->lastname = 'User';
        $record->logpath = NULL;
        $record->entitytype = NULL;
        $this->assertEquals($record, $dbrecord);
    }

    /**
     * Validate that log retrieval retrieves records in the right order
     */
    function testeGetLogsUsesCorrectOrder() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log();

        //create second log record
        $this->create_db_log(array('starttime' => 1000000003));

        //validate that at least one record exists
        $logs = rlip_get_logs();
        $this->assertTrue($logs->valid());

        //validate that the most recent record is returned first
        $dbrecord = $logs->current();
        $this->assertEquals($dbrecord->starttime, 1000000003);
    }

    /**
     * Validate that log retrieval respects the task type filter
     */
    function testGetLogsRespectsTaskTypeFilter() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log records
        $this->create_db_log();
        $this->create_db_log(array('export' => 0));

        //validate count when filtering for "import"
        $where = 'export = :param0';
        $params = array('param0' => 0);
        $logs = rlip_get_logs($where, $params);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);

        //validate count when filtering for "export"
        $where = 'export = :param0';
        $params = array('param0' => 1);
        $logs = rlip_get_logs($where, $params);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    /**
     * Validate that log retrieval respects the execution filter
     */
    function testGetLogsRespectsExecutionFilter() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log records
        $this->create_db_log();
        $this->create_db_log(array('targetstarttime' => 0));

        //validate count when filtering for "manual"
        $where = 'targetstarttime = 0';
        $logs = rlip_get_logs($where);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);

        //validate count when filtering for "scheduled"
        $where = 'targetstarttime > 0';
        $logs = rlip_get_logs($where);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    /**
     * Validate that log retrieval respects the start time filter
     */
    function testGetLogsRespectsActualStartTimeFilter() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //calculate the start time of the current day
        $starttime = mktime(0, 0, 0);
        
        //create log records
        $this->create_db_log(array('starttime' => 0));
        $this->create_db_log(array('starttime' => $starttime + 12 * HOURSECS));

        //validate count when filtering for a particular day
        $where = 'starttime >= :param0 AND starttime < :param1';
        $params = array('param0' => $starttime,
                        'param1' => $starttime + DAYSECS);
        $logs = rlip_get_logs($where, $params);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    /**
     * Validate that log retrieval respects the paging mechanism
     */
    function testGetLogsRespectPaging() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //fetch records
        $numrecords = RLIP_LOGS_PER_PAGE + 1;
        for ($i = 0; $i < $numrecords; $i++) {
            $this->create_db_log(array('targetstarttime' => 1000000000 + $i,
                                       'starttime' => 1000000000,
                                       'endtime' => 1000000000));
        }

        //validate that the appropriate number of records are shown on page 1
        $logs = rlip_get_logs('', array(), 0);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, RLIP_LOGS_PER_PAGE);

        //validate that the single remaining record is shown on page 2
        $logs = rlip_get_logs('', array(), 1);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);
    }

    /**
     * Validate that the log table creation method returns a valid table when
     * data is present
     */
    function testGetLogTableReturnsValidTable() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set appropriate display parameters
        set_config('fullnamedisplay', 'firstname lastname');
        set_config('forcetimezone', 99);

        //time format used in table
        $timeformat = get_string('displaytimeformat', 'block_rlip');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log();

        //obtain table data
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        //validate table header
        $this->assertEquals($table->head, array(get_string('logtasktype', 'block_rlip'),
                                                get_string('logplugin', 'block_rlip'),
                                                get_string('logexecution', 'block_rlip'),
                                                get_string('loguser', 'block_rlip'),
                                                get_string('logscheduledstart', 'block_rlip'),
                                                get_string('logstart', 'block_rlip'),
                                                get_string('logend', 'block_rlip'),
                                                get_string('logfilesuccesses', 'block_rlip'),
                                                get_string('logfilefailures', 'block_rlip'),
                                                get_string('logstatus', 'block_rlip'),
                                                get_string('logentitytype', 'block_rlip'),
                                                get_string('logdownload', 'block_rlip')));

        //validate table data
        $this->assertEquals(count($table->data), 1);
        $datum = reset($table->data);
        $this->assertEquals($datum, array(get_string('export', 'block_rlip'),
                                          get_string('pluginname', 'rlipexport_version1'),
                                          get_string('automatic', 'block_rlip'),
                                          'Test User',
                                          userdate(1000000001, $timeformat, 99, false),
                                          userdate(1000000002, $timeformat, 99, false),
                                          userdate(1000000003, $timeformat, 99, false),
                                          '1',
                                          get_string('na', 'block_rlip'),
                                          'testmessage',
                                          'N/A',
                                          '<a href="download.php?id=1">Log</a>'));
    }

    /**
     * Validate that table data is correct for import plugin records
     */
    function testGetLogTableReturnsImportPlugin() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log(array('export' => 0,
                                   'plugin' => 'rlipimport_version1'));

        //obtain table data
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        //validate number of rows
        $this->assertEquals(count($table->data), 1);

        //validate row data related to import plugins
        $datum = reset($table->data);
        $this->assertEquals($datum[0], get_string('import', 'block_rlip'));
        $this->assertEquals($datum[1], get_string('pluginname', 'rlipimport_version1'));
    }

    /**
     * Validate that table data is correct for a manual run
     */
    function testGetLogTableReturnsManual() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log(array('targetstarttime' => 0));

        //obtain table data
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        //validate number of rows
        $this->assertEquals(count($table->data), 1);

        //validate row data related to manual run
        $datum = reset($table->data);
        $this->assertEquals($datum[2], get_string('manual', 'block_rlip'));
    }

    /**
     * Validate that table data is correct for a manual run
     */
    function testGetLogTableReturnsNAScheduledStartTime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create a test user
        $this->create_test_user();

        //create log record
        $this->create_db_log(array('targetstarttime' => 0));

        //obtain table data
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        //validate number of rows
        $this->assertEquals(count($table->data), 1);

        //validate row data related to manual run
        $datum = reset($table->data);
        $this->assertEquals($datum[4], get_string('na', 'block_rlip'));
    }

    /**
     * Validate that dates in table data respect timezones
     */
    function testLogTableRespectsTimezones() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //force timezone to a particular value
        set_config('forcetimezone', 10);

        //time display format for all relevant fields
        $timeformat = get_string('displaytimeformat', 'block_rlip');

        //create a test user
        $this->create_test_user();

        //create a log record
        $this->create_db_log();

        //expected strings
        $targetstartdisplay = userdate(1000000001, $timeformat, 99, false);
        $startdisplay = userdate(1000000002, $timeformat, 99, false);
        $enddisplay = userdate(1000000003, $timeformat, 99, false);

        //obtain table data
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        //validate number of rows
        $this->assertEquals(count($table->data), 1);

        //validate time values in row data
        $datum = reset($table->data);
        $this->assertEquals($datum[4], $targetstartdisplay);
        $this->assertEquals($datum[5], $startdisplay);
        $this->assertEquals($datum[6], $enddisplay);
    }

    /**
     * Validate conversion of nonempty table object to HTML representation
     */
    function testLogTableHtml() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create table object
        $table = new html_table();
        $table->head = array('head1', 'head2', 'head3');
        $table->data = array();
        $table->data[] = array('data1', 'data2', 'data3');

        //validation
        $output = rlip_log_table_html($table);
        $this->assertEquals($output, html_writer::table($table));
    }

    /**
     * Validate conversion of empty table object to HTML representation
     */
    function testLogTableHtmlReturnsEmptyMessage() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //create table object
        $table = new html_table();
        $output = rlip_log_table_html($table);

        //validation
        $this->assertEquals($output, $OUTPUT->heading(get_string('nologmessage', 'block_rlip')));
    }

    /**
     * Validate that the "operation select" custom filter type returns the
     * correct SQL fragment
     */
    function testOperationSelectReturnsCorrectSQLFilter() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_log_filtering.class.php');

        //create filter class
        $options = array('= 0' => 'label1',
                         '> 0' => 'label2');
        $filter = new rlip_log_filter_operationselect('testname', 'testlabel', 0, 'field', $options);

        //validate case when not filtering
        $data = array('value' => '');
        $info = $filter->get_sql_filter($data);
        $this->assertEquals($info, '');

        //validate first option
        $data = array('value' => '= 0');
        $info = $filter->get_sql_filter($data);
        $this->assertEquals(count($info), 2);
        $this->assertEquals(substr($info[0], 0, 7), 'field =');
        $value = reset($info[1]);
        $this->assertEquals($value, 0);

        //validate second option
        $data = array('value' => '> 0');
        $info = $filter->get_sql_filter($data);
        $this->assertEquals(count($info), 2);
        $this->assertEquals(substr($info[0], 0, 7), 'field >');
        $value = reset($info[1]);
        $this->assertEquals($value, 0);
    }

    /**
     * Validate that the library function rlip_get_maxruntime()
     * returns correct type and value.
     */
    function test_rlip_get_maxruntime() {
        $mrt = rlip_get_maxruntime();
        //echo "\n maxruntime = {$mrt}\n";
        $this->assertTrue(is_int($mrt));
        $this->assertGreaterThanOrEqual(RLIP_MAXRUNTIME_MIN, $mrt);
    }

    /**
     * Validate that the library function rlip_count_logs()
     * returns properly filtered log entries
     */
    function test_rlip_count_logs() {
        global $CFG, $DB, $USER;

        $this->create_test_user();
        $USER = $DB->get_record('user', array('id' => 1));

        $logrec = new stdClass;
        $logrec->userid = $USER->id;
        $logrec->targetstarttime = 1;
        $logrec->starttime = time() - 60;
        $logrec->endtime = time();
        $logrec->filesuccesses = 42;
        $logrec->filefailures = 0;
        $logrec->storedsuccesses = 0;
        $logrec->storedfailures = 0;
        $logrec->statusmessage = 'Success!';
        $logrec->dbops = 1;
        $logrec->unmetdependency = 0;
        $logrec->logfilepath = $CFG->dataroot;
        $logrec->export = 1;
        $logrec->plugin = 'rlipexport_version1';
        // input < 1 page of export log entries
        for ($i = 0; $i < RLIP_LOGS_PER_PAGE - 2; ++$i) {
            $DB->insert_record(RLIP_LOG_TABLE, $logrec);
        }

        $logrec->export = 0;
        $logrec->plugin = 'rlipimport_version1';
        // input > 2 pages of import log entries
        for ($i = 0; $i <= 2 * RLIP_LOGS_PER_PAGE; ++$i) {
            $DB->insert_record(RLIP_LOG_TABLE, $logrec);
        }

        //$recs = $DB->get_records(RLIP_LOG_TABLE);
        //var_dump($recs);

        // count total log entires
        $count = rlip_count_logs();
        //echo "test_rlip_count_logs(): totalcount = {$count}";
        $this->assertGreaterThanOrEqual(3 * RLIP_LOGS_PER_PAGE - 2, $count);

        // count filtered log entires
        $count = rlip_count_logs('export = ?', array(1));
        $this->assertLessThanOrEqual(RLIP_LOGS_PER_PAGE, $count);
    }

}
