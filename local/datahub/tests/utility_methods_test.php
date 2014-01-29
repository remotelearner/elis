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
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/exportplugins/version1/lib.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1/lib.php');
require_once($CFG->dirroot.'/local/datahub/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1elis/lib.php');

/**
 * Class for testing utility methods
 * @group local_datahub
 */
class utilitymethod_testcase extends rlip_test {

    public static function setUpBeforeClass() {
        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
        parent::setUpBeforeClass();
    }

    /**
     * Create test user record
     */
    protected function create_test_user() {
        $dataset = $this->createCsvDataSet(array('user' => dirname(__FILE__).'/fixtures/user.csv'));
        $this->loadDataSet($dataset);
    }

    /**
     * Create complete test user record
     */
    protected function create_complete_test_user() {
        $dataset = $this->createCsvDataSet(array('user' => dirname(__FILE__).'/fixtures/completeuser.csv'));
        $this->loadDataSet($dataset);
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_export_csv_data() {
        $csvloc = dirname(__FILE__).'/../exportplugins/version1/tests/fixtures';
        $csvloc2 = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            'grade_items' => $csvloc.'/phpunit_gradeitems.csv',
            'grade_grades' => $csvloc.'/phpunit_gradegrades.csv',
            'user' => $csvloc2.'/user2.csv',
            'course' => $csvloc.'/phpunit_course.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Create a test database log record
     *
     * @param array $extradata Extra fields / overrides to set
     */
    protected function create_db_log($extradata = array()) {
        global $DB;

        // Calculate data.
        $basedata = array(
            'export' => 1,
            'plugin' => 'dhexport_version1',
            'userid' => 100,
            'targetstarttime' => 1000000001,
            'starttime' => 1000000002,
            'endtime' => 1000000003,
            'filesuccesses' => 1,
            'filefailures' => 2,
            'storedsuccesses' => 3,
            'storedfailures' => 4,
            'statusmessage' => 'testmessage',
            'dbops' => 10,
            'unmetdependency' => 1
        );
        $basedata = array_merge($basedata, $extradata);

        // Insert record.
        $record = (object)$basedata;
        $DB->insert_record(RLIP_LOG_TABLE, $record);
    }

    /**
     * Validate that time string sanitization sets the default when input
     * string is empty
     */
    public function test_sanitizetimestringprovidesdefault() {
        $result = rlip_sanitize_time_string('', '1d');
        $this->assertEquals($result, '1d');
    }

    /**
     * Validate that time string sanitization leaves valid strings unchanged
     */
    public function test_sanitizetimestringleavesvalidstringunchanged() {
        $result = rlip_sanitize_time_string('1d2h3m', '1d');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the beginning of a string
     */
    public function test_sanitizetimestringremovesinvalidportionfromstart() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the middle of a string
     */
    public function test_sanitizetimestringremovesinvalidportionfrommiddle() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the end of a string
     */
    public function test_sanitizetimestringremovesinvalidportionfromend() {
        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes non-portion characters
     * from all parts of a time string
     */
    public function test_sanitizetimestringremovesnonportioncharacters() {
        $result = rlip_sanitize_time_string('1d, 2h, 3m');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization converts letters to lowercase
     */
    public function test_sanitizetimestringconvertsletterstolowercase() {
        $result = rlip_sanitize_time_string('1D2H3M');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization prevents multiple units beside
     * one another
     */
    public function test_sanitizetimestringpreventsconsecutiveunits() {
        $result = rlip_sanitize_time_string('1d2h3mm');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that converting time string to offset works for number of
     * days
     */
    public function test_timestringtooffsetreturnscorrectoffsetfordays() {
        $result = rlip_time_string_to_offset('2d');
        $this->assertEquals($result, 2 * DAYSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * hours
     */
    public function test_timestringtooffsetreturnscorrectoffsetforhours() {
        $result = rlip_time_string_to_offset('2h');
        $this->assertEquals($result, 2 * HOURSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * minutes
     */
    public function test_timestringtooffsetreturnscorrectoffsetforminutes() {
        $result = rlip_time_string_to_offset('2m');
        $this->assertEquals($result, 2 * MINSECS);
    }

    /**
     * Validate that converting time string to offset works for complex string
     * with hours, minutes and seconds
     */
    public function test_timestringtooffsetreturnscorrectoffsetforcomplexstring() {
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
            array('4  d 5h  6m', (DAYSECS * 4)/60 + (HOURSECS * 5)/60 + 6),
            array('7 d 8 h 9 m', (DAYSECS * 7)/60 + (HOURSECS * 8)/60 + 9),
            array('20d23h45m', DAYSECS/3 + (HOURSECS * 23)/60 + 45),
            array('2a3b4c', -1)
        );
    }

    /**
     * Test library function: rlip_schedule_period_minutes()
     * @dataProvider period_minutes_provider
     */
    public function test_rlip_schedule_period_minutes($a, $b) {
        $this->assertEquals(rlip_schedule_period_minutes($a), $b);
    }

    /**
     * Validate that adding a new job sets the right next runtime on the IP
     * schedule record
     */
    public function test_addingjobsetsipnextruntime() {
        global $DB;

        // A lower bound for the start time.
        $starttime = time();

        // Create a scheduled job.
        $data = array(
            'plugin' => 'dhexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'dhexport'
        );

        // Obtain scheduled task info.
        $taskid = rlip_schedule_add_job($data);
        $task = $DB->get_record('local_eliscore_sched_tasks', array('id' => $taskid));

        // Obtain IP job info.
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        // Make sure the next runtime is between 5 and 6 minutes from now.
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        // Make sure both records have the same next run time.
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Test that the next runtime is aligned to the correct boundary
     */
    public function test_nextruntimeboundry() {
        $targetstarttime = mktime(12, 0, 0, 1, 1, 2012);    // 12:00.
        $lowerboundtime = mktime(12, 2, 0, 1, 1, 2012);     // 12:02.
        $middleboundtime = mktime(12, 4, 30, 1, 1, 2012);   // 12:04:30.
        $upperboundtime = mktime(12, 7, 0, 1, 1, 2012);     // 12:07.

        $nextruntime =  rlip_calc_next_runtime($targetstarttime, '5m', $lowerboundtime);
        $this->assertEquals($nextruntime, $targetstarttime + (60 * 5)); // 12:05.

        $nextruntime =  rlip_calc_next_runtime($targetstarttime, '5m', $middleboundtime);
        $this->assertEquals($nextruntime, $targetstarttime + (60 * 10)); // 12:10.

        $nextruntime =  rlip_calc_next_runtime($targetstarttime, '5m', $upperboundtime);
        $this->assertEquals($nextruntime, $targetstarttime + (60 * 10)); // 12:10.
    }

    /**
     * Validate that the "add job" method also supports updates
     */
    public function test_updatingjob() {
        global $DB;

        $DB->delete_records('local_eliscore_sched_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        // Create a scheduled job.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport');
        $data['id'] = rlip_schedule_add_job($data);

        // Update the job.
        $data['plugin'] = 'bogusplugin';
        $data['userid'] = 9999;
        rlip_schedule_add_job($data);

        // Data validation.
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $data['id']));
        $this->assertNotEmpty($job);
        $this->assertEquals($job->plugin, 'bogusplugin');
        $this->assertEquals($job->userid, 9999);
    }

    /**
     * Validate that the "delete job" method works
     */
    public function test_deletingjob() {
        global $DB;
        $DB->delete_records('local_eliscore_sched_tasks');
        // Create a scheduled job.
        $data = array(
            'plugin' => 'dhexport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'dhexport'
        );
        $jobid = rlip_schedule_add_job($data);

        // Setup validation.
        $this->assertEquals($DB->count_records(RLIP_SCHEDULE_TABLE), 1);
        $this->assertEquals($DB->count_records('local_eliscore_sched_tasks'), 1);

        // Delete the job.
        rlip_schedule_delete_job($jobid);

        // Data validation.
        $this->assertEquals($DB->count_records(RLIP_SCHEDULE_TABLE), 0);
        $this->assertEquals($DB->count_records('local_eliscore_sched_tasks'), 0);
    }

    /**
     * Validate that scheduled jobs are retrieved via API call
     */
    public function test_getscheduledjobs() {
        global $CFG, $DB;

        // Create a user.
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

        // Create a scheduled job.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport',
                      'userid' => $userid);
        $starttime = time();
        $ipid = rlip_schedule_add_job($data);
        $endtime = time();

        // Fetch jobs.
        $recordset = rlip_get_scheduled_jobs($data['plugin']);

        // Data validation.
        $this->assertTrue($recordset->valid());

        $current = $recordset->current();
        // Ip schedule fields.
        $this->assertEquals($current->plugin, $data['plugin']);
        // User fields.
        $this->assertEquals($current->username, $user->username);
        $this->assertEquals($current->firstname, $user->firstname);
        $this->assertEquals($current->lastname, $user->lastname);
        $this->assertEquals($current->timezone, $user->timezone);
        $this->assertEquals($current->lastruntime, 0);
        // Elis scheduled task field.
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$current->nextruntime);
        $this->assertGreaterThanOrEqual((int)$current->nextruntime, $endtime + 5 * MINSECS);
    }

    /**
     * Validate that IP correctly updates last runtime values
     */
    public function test_runningjobsetsiplastruntime() {
        global $CFG, $DB;

        // Set up the export file & path.
        set_config('export_path', '', 'dhexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'dhexport_version1');

        set_config('disableincron', 0, 'local_datahub');

        // Create a scheduled job.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport');
        $taskid = rlip_schedule_add_job($data);

        // Change the last runtime to a value that is out of range on both records.
        $task = new stdClass;
        $task->id = $taskid;
        // This is the value that will be transferred onto the job record after the run.
        $task->lastruntime = 1000000000;
        $task->nextruntime = 0;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhexport_version1'));
        $job->lastruntime = 0;
        $job->nextruntime = 0;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Run the job.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Obtain both records.
        $task = $DB->get_record('local_eliscore_sched_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        // Validate that the value was obtained from the elis scheduled task.
        $this->assertEquals($job->lastruntime, 1000000000);
    }

    /**
     * Validate that running a job sets the right next runtime on the IP
     * schedule record
     */
    public function test_runningjobsetsipnextruntime() {
        global $CFG, $DB;

        // Set up the export file & path.
        set_config('export_path', '', 'dhexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'dhexport_version1');

        set_config('disableincron', 0, 'local_datahub');

        // Create a scheduled job.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport');
        $taskid = rlip_schedule_add_job($data);

        $timenow = time();
        // Change the next runtime to a value that is out of range on both records.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = $timenow;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhexport_version1'));
        $job->nextruntime = $timenow;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Run the job.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        $starttime = $timenow;
        run_ipjob($taskname);

        // Obtain both records.
        $task = $DB->get_record('local_eliscore_sched_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        // Make sure the next runtime is between 5 and 6 minutes from initial value.
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        // Make sure both records have the same next run time.
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that failed run due to disabled cron correctly resets
     * scheduling times
     */
    public function test_runningjobsfixeselisscheduledtaskwhenexternalcronenabled() {
        global $CFG, $DB;

        // Set up the export file & path.
        set_config('export_path', '', 'dhexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'dhexport_version1');

        // Enable external cron.
        set_config('disableincron', 1, 'local_datahub');

        // Set up the tasks.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport');
        $taskid = rlip_schedule_add_job($data);

        // Set both tasks' times to known states.
        $task = new stdClass;
        $task->id = $taskid;
        $task->lastruntime = 0;
        $task->nextruntime = 0;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhexport_version1'));
        $job->lastruntime = 1000000000;
        $job->nextruntime = 1000000001;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Run the job.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Obtain both records.
        $task = $DB->get_record('local_eliscore_sched_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        // Validate all times.
        $this->assertEquals((int)$task->lastruntime, 1000000000);
        $this->assertEquals((int)$task->nextruntime, 1000000001);
        $this->assertEquals((int)$task->lastruntime, (int)$job->lastruntime);
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that running out of time correctly resets scheduling times
     */
    public function test_runningjobsresetsstatewhentimeexceeded() {
        global $CFG, $DB;

        // Set the log file location to the dataroot.
        $filepath = $CFG->dataroot;
        set_config('logfilelocation', $filepath, 'dhexport_version1');

        // Enable internal cron.
        set_config('disableincron', 0, 'local_datahub');

        // Nonincremental export.
        set_config('nonincremental', 1, 'dhexport_version1');

        // Load in data needed for export.
        $this->load_export_csv_data();

        // Set up the export file & path.
        set_config('export_path', '', 'dhexport_version1');
        set_config('export_file', 'rliptestexport.csv', 'dhexport_version1');

        // Set up the tasks.
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'dhexport');
        $taskid = rlip_schedule_add_job($data);

        // Set both tasks' times to known states.
        $task = new stdClass;
        $task->id = $taskid;
        $task->lastruntime = 0;
        $task->nextruntime = 0;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhexport_version1'));
        $job->lastruntime = 1000000000;
        $job->nextruntime = 1000000001;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Run the job with an impossibly small time limit.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname, -1);

        // Obtain job record.
        $task = $DB->get_record('local_eliscore_sched_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $jobid));

        // Validate all times.
        $this->assertEquals((int)$task->lastruntime, 1000000000);
        $this->assertEquals((int)$task->nextruntime, 1000000001);
        $this->assertEquals((int)$task->lastruntime, (int)$job->lastruntime);
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that log records without associated users are not retrieved
     */
    public function test_countlogsrequiresuserrecord() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create log record.
        $this->create_db_log();

        // Validation.
        $count = rlip_count_logs();
        $this->assertEquals($count, 0);
    }

    /**
     * Validate that log records with associated users are retrieved
     */
    public function test_countlogsreturnscorrectcount() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log();

        // Validation.
        $count = rlip_count_logs();
        $this->assertEquals($count, 1);
    }

    /**
     * Validates that log retrieval returns an empty recordset when no data is
     * available
     */
    public function test_getlogsreturnsemptyrecordset() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Fetch supposedly empty set.
        $logs = rlip_get_logs();

        // Data validation.
        $this->assertFalse($logs->valid());
    }

    /**
     * Validates that log retrieval returns a valid recordset when data is
     * available
     */
    public function test_getlogsreturnsvalidrecordset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log();

        // Validate that at least one record is returned.
        $logs = rlip_get_logs();
        $this->assertTrue($logs->valid());

        // Validate record data.
        $dbrecord = $logs->current();
        unset($dbrecord->id);
        $record = new stdClass;
        $record->export = 1;
        $record->plugin = 'dhexport_version1';
        $record->userid = 100;
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
        $record->logpath = null;
        $record->entitytype = null;
        $this->assertEquals($record, $dbrecord);
    }

    /**
     * Validate that log retrieval retrieves records in the right order
     */
    public function test_getlogsusescorrectorder() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log();

        // Create second log record.
        $this->create_db_log(array('starttime' => 1000000003));

        // Validate that at least one record exists.
        $logs = rlip_get_logs();
        $this->assertTrue($logs->valid());

        // Validate that the most recent record is returned first.
        $dbrecord = $logs->current();
        $this->assertEquals($dbrecord->starttime, 1000000003);
    }

    /**
     * Validate that log retrieval respects the task type filter
     */
    public function test_getlogsrespectstasktypefilter() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log records.
        $this->create_db_log();
        $this->create_db_log(array('export' => 0));

        // Validate count when filtering for "import".
        $where = 'export = :param0';
        $params = array('param0' => 0);
        $logs = rlip_get_logs($where, $params);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);

        // Validate count when filtering for "export".
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
    public function test_getlogsrespectsexecutionfilter() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log records.
        $this->create_db_log();
        $this->create_db_log(array('targetstarttime' => 0));

        // Validate count when filtering for "manual".
        $where = 'targetstarttime = 0';
        $logs = rlip_get_logs($where);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, 1);

        // Validate count when filtering for "scheduled".
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
    public function test_getlogsrespectsactualstarttimefilter() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Calculate the start time of the current day.
        $starttime = rlip_timestamp(0, 0, 0);

        // Create log records.
        $this->create_db_log(array('starttime' => 0));
        $this->create_db_log(array('starttime' => $starttime + 12 * HOURSECS));

        // Validate count when filtering for a particular day.
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
    public function test_getlogsrespectpaging() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Fetch records.
        $numrecords = RLIP_LOGS_PER_PAGE + 1;
        for ($i = 0; $i < $numrecords; $i++) {
            $this->create_db_log(array('targetstarttime' => 1000000000 + $i,
                                       'starttime' => 1000000000,
                                       'endtime' => 1000000000));
        }

        // Validate that the appropriate number of records are shown on page 1.
        $logs = rlip_get_logs('', array(), 0);
        $count = 0;
        foreach ($logs as $log) {
            $count++;
        }
        $this->assertEquals($count, RLIP_LOGS_PER_PAGE);

        // Validate that the single remaining record is shown on page 2.
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
    public function test_getlogtablereturnsvalidtable() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set appropriate display parameters.
        set_config('fullnamedisplay', 'firstname lastname');
        set_config('forcetimezone', 99);

        // Time format used in table.
        $timeformat = get_string('displaytimeformat', 'local_datahub');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log();

        // Obtain table data.
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        // Validate table header.
        $this->assertEquals($table->head, array(
            get_string('logtasktype', 'local_datahub'),
            get_string('logplugin', 'local_datahub'),
            get_string('logexecution', 'local_datahub'),
            get_string('loguser', 'local_datahub'),
            get_string('logscheduledstart', 'local_datahub'),
            get_string('logstart', 'local_datahub'),
            get_string('logend', 'local_datahub'),
            get_string('logfilesuccesses', 'local_datahub'),
            get_string('logfilefailures', 'local_datahub'),
            get_string('logstatus', 'local_datahub'),
            get_string('logentitytype', 'local_datahub'),
            get_string('logdownload', 'local_datahub')
        ));

        // Validate table data.
        $this->assertEquals(count($table->data), 1);
        $datum = reset($table->data);
        $this->assertEquals($datum, array(
            get_string('export', 'local_datahub'),
            get_string('pluginname', 'dhexport_version1'),
            get_string('automatic', 'local_datahub'),
            'Test User',
            userdate(1000000001, $timeformat, 99, false),
            userdate(1000000002, $timeformat, 99, false),
            userdate(1000000003, $timeformat, 99, false),
            '1',
            get_string('na', 'local_datahub'),
            'testmessage',
            'N/A',
            // ELIS-5199 The download link only appears when there is a valid file present on the filesystem.
            ''));
    }

    /**
     * Validate that table data is correct for import plugin records
     */
    public function test_getlogtablereturnsimportplugin() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log(array('export' => 0,
                                   'plugin' => 'dhimport_version1'));

        // Obtain table data.
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        // Validate number of rows.
        $this->assertEquals(count($table->data), 1);

        // Validate row data related to import plugins.
        $datum = reset($table->data);
        $this->assertEquals($datum[0], get_string('import', 'local_datahub'));
        $this->assertEquals($datum[1], get_string('pluginname', 'dhimport_version1'));
    }

    /**
     * Validate that table data is correct for a manual run
     */
    public function test_getlogtablereturnsmanual() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log(array('targetstarttime' => 0));

        // Obtain table data.
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        // Validate number of rows.
        $this->assertEquals(count($table->data), 1);

        // Validate row data related to manual run.
        $datum = reset($table->data);
        $this->assertEquals($datum[2], get_string('manual', 'local_datahub'));
    }

    /**
     * Validate that table data is correct for a manual run
     */
    public function test_getlogtablereturnsnascheduledstarttime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create a test user.
        $this->create_test_user();

        // Create log record.
        $this->create_db_log(array('targetstarttime' => 0));

        // Obtain table data.
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        // Validate number of rows.
        $this->assertEquals(count($table->data), 1);

        // Validate row data related to manual run.
        $datum = reset($table->data);
        $this->assertEquals($datum[4], get_string('na', 'local_datahub'));
    }

    /**
     * Validate that dates in table data respect timezones
     */
    public function test_logtablerespectstimezones() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Force timezone to a particular value.
        set_config('forcetimezone', 10);

        // Time display format for all relevant fields.
        $timeformat = get_string('displaytimeformat', 'local_datahub');

        // Create a test user.
        $this->create_test_user();

        // Create a log record.
        $this->create_db_log();

        // Expected strings.
        $targetstartdisplay = userdate(1000000001, $timeformat, 99, false);
        $startdisplay = userdate(1000000002, $timeformat, 99, false);
        $enddisplay = userdate(1000000003, $timeformat, 99, false);

        // Obtain table data.
        $logs = rlip_get_logs();
        $table = rlip_get_log_table($logs);

        // Validate number of rows.
        $this->assertEquals(count($table->data), 1);

        // Validate time values in row data.
        $datum = reset($table->data);
        $this->assertEquals($datum[4], $targetstartdisplay);
        $this->assertEquals($datum[5], $startdisplay);
        $this->assertEquals($datum[6], $enddisplay);
    }

    /**
     * Validate conversion of nonempty table object to HTML representation
     */
    public function test_logtablehtml() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create table object.
        $table = new html_table();
        $table->head = array('head1', 'head2', 'head3');
        $table->data = array();
        $table->data[] = array('data1', 'data2', 'data3');

        // Validation.
        $output = rlip_log_table_html($table);
        $this->assertEquals($output, html_writer::table($table));
    }

    /**
     * Validate conversion of empty table object to HTML representation
     */
    public function test_logtablehtmlreturnsemptymessage() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Create table object.
        $table = new html_table();
        $output = rlip_log_table_html($table);

        // Validation.
        $this->assertEquals($output, $OUTPUT->heading(get_string('nologmessage', 'local_datahub')));
    }

    /**
     * Validate that the "operation select" custom filter type returns the
     * correct SQL fragment
     */
    public function test_operationselectreturnscorrectsqlfilter() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_log_filtering.class.php');

        // Create filter class.
        $options = array('= 0' => 'label1',
                         '> 0' => 'label2');
        $filter = new rlip_log_filter_operationselect('testname', 'testlabel', 0, 'field', $options);

        // Validate case when not filtering.
        $data = array('value' => '');
        $info = $filter->get_sql_filter($data);
        $this->assertEquals($info, '');

        // Validate first option.
        $data = array('value' => '= 0');
        $info = $filter->get_sql_filter($data);
        $this->assertEquals(count($info), 2);
        $this->assertEquals(substr($info[0], 0, 7), 'field =');
        $value = reset($info[1]);
        $this->assertEquals($value, 0);

        // Validate second option.
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
    public function test_rlip_get_maxruntime() {
        $mrt = rlip_get_maxruntime();

        $this->assertTrue(is_int($mrt));
        $this->assertGreaterThanOrEqual(RLIP_MAXRUNTIME_MIN, $mrt);
    }

    /**
     * Validate that the library function rlip_count_logs()
     * returns properly filtered log entries
     */
    public function test_rlip_count_logs() {
        global $CFG, $DB, $USER;

        $this->create_test_user();
        $USER = $DB->get_record('user', array('id' => 100));

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
        $logrec->plugin = 'dhexport_version1';
        // Input < 1 page of export log entries.
        for ($i = 0; $i < RLIP_LOGS_PER_PAGE - 2; ++$i) {
            $DB->insert_record(RLIP_LOG_TABLE, $logrec);
        }

        $logrec->export = 0;
        $logrec->plugin = 'dhimport_version1';
        // Input > 2 pages of import log entries.
        for ($i = 0; $i <= 2 * RLIP_LOGS_PER_PAGE; ++$i) {
            $DB->insert_record(RLIP_LOG_TABLE, $logrec);
        }

        // Count total log entires.
        $count = rlip_count_logs();
        $this->assertGreaterThanOrEqual(3 * RLIP_LOGS_PER_PAGE - 2, $count);

        // Count filtered log entires.
        $count = rlip_count_logs('export = ?', array(1));
        $this->assertLessThanOrEqual(RLIP_LOGS_PER_PAGE, $count);
    }

    /*
     * Helper function to delete $path including parent dirs upto $basedir
     */
    public function delete_full_path($basedir, $path) {
        $todel = $basedir.$path;
        do {
            @rmdir($todel);
            $todel = dirname($todel);
        } while (strcmp($basedir, $todel));
    }

    /**
     * Validate that the library function rlip_log_file_name()
     * creates log files directories
     */
    public function test_rlip_log_file_name() {
        global $CFG;

        $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
        $exportpath = '/phpunit/datahub/exportlogpath';
        $importpath = '/phpunit/datahub/importlogpath';

        $logfile = rlip_log_file_name('bogus', 'bogus', $exportpath);
        $this->assertTrue(file_exists($dataroot.$exportpath));

        $logfile = rlip_log_file_name('bogus', 'bogus', $importpath);
        $this->assertTrue(file_exists($dataroot.$importpath));

        $this->delete_full_path($dataroot, $exportpath);
        $this->delete_full_path($dataroot, $importpath);
    }

    private function recreate_table($component, $tablename) {
        global $DB;
        unset($DB->donesetup);

        $manager = $DB->get_manager();

        $filename = get_component_directory($component)."/db/install.xml";
        $xmldbfile = new xmldb_file($filename);

        if (!$xmldbfile->fileExists()) {
            throw new ddl_exception('ddlxmlfileerror', null, 'File does not exist');
        }

        $loaded = $xmldbfile->loadXMLStructure();
        if (!$loaded || !$xmldbfile->isLoaded()) {
            // Show info about the error if we can find it.
            if ($structure =& $xmldbfile->getStructure()) {
                if ($errors = $structure->getAllErrors()) {
                    throw new ddl_exception('ddlxmlfileerror', null, 'Errors found in XMLDB file: '.implode (', ', $errors));
                }
            }
            throw new ddl_exception('ddlxmlfileerror', null, 'not loaded??');
        }

        $structure = $xmldbfile->getStructure();
        $table = $structure->getTable($tablename);

        $manager->create_table($table);

        $DB->donesetup = true;
    }

    /**
     * Validate that the block method before_delete()
     * deletes all local_datahub tables & data
     */
    public function test_local_datahub_before_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
        require_once($CFG->dirroot.'/local/datahub/db/uninstall.php');
        require_once($CFG->dirroot.'/local/datahub/fileplugins/log/db/tasks.php');

        // Setup some bogus config_plugins settings and local_eliscore_sched_tasks.
        set_config('bogus1', 1, 'dhexport_version1');
        set_config('bogus2', 1, 'dhimport_version1');

        // Add some bogus RLIP scheduled tasks.
        $testdata = array(
            'type'   => 'dhimport',
            'plugin' => 'dhimport_version1',
            'period' => '15m',
            'label'  => 'bogus'
        );
        rlip_schedule_add_job($testdata);
        rlip_schedule_add_job($testdata);
        $testdata['type'] = 'dhexport';
        $testdata['plugin'] = 'dhexport_version1';
        rlip_schedule_add_job($testdata);
        rlip_schedule_add_job($testdata);

        // Add compress logs cron job.
        $tasks[0]['plugin'] = 'local_datahub';
        $DB->insert_record('local_eliscore_sched_tasks', $tasks[0]);

        // Call the Datahub uninstall method.
        xmldb_local_datahub_uninstall();

        // Test RLIP tables were deleted ....
        // Notes: $dbman->generator not overlay
        //        ... so ->table_exists() calls work on real 'mdl_' tables!?!
        // Found another method to test tables don't exist!
        try {
            $DB->count_records(RLIPEXPORT_VERSION1_FIELD_TABLE);
            $this->assertTrue(false);
        } catch (Exception $e) {
            // Expected exception table not found.
            $this->recreate_table('dhexport_version1', RLIPEXPORT_VERSION1_FIELD_TABLE);
        }
        try {
            $DB->count_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);
            $this->assertTrue(false);
        } catch (Exception $e) {
            // Expected exception table not found.
            $this->recreate_table('dhimport_version1', RLIPIMPORT_VERSION1_MAPPING_TABLE);
        }

        try {
            $DB->count_records(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE);
            $this->assertTrue(false);
        } catch (Exception $e) {
            // Expected exception table not found.
            $this->recreate_table('dhexport_version1elis', RLIPEXPORT_VERSION1ELIS_FIELD_TABLE);
        }
        try {
            $DB->count_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE);
            $this->assertTrue(false);
        } catch (Exception $e) {
            // Expected exception table not found.
            $this->recreate_table('dhimport_version1elis', RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE);
        }

        // Test RLIP elis schedule task deleted.
        $iprecs = $DB->get_records_select('local_eliscore_sched_tasks', "taskname LIKE 'ipjob_%'");
        $this->assertTrue(empty($iprecs));

        $iprecs = $DB->get_records('local_eliscore_sched_tasks', array('plugin' => 'local_datahub'));
        $this->assertTrue(empty($iprecs));

        $iprecs = $DB->get_records('local_eliscore_sched_tasks', array('plugin' => 'block_rlip'));
        $this->assertTrue(empty($iprecs));

        // Test RLIP config settings deleted.
        $this->assertFalse(get_config('dhexport_version1', 'bogus1'));
        $this->assertFalse(get_config('dhimport_version1', 'bogus2'));
    }

    /**
     * Validate that we can get notification emails configured for an import plugin
     */
    public function test_getnotificationemailsforimport() {
        set_config('emailnotification', 'user1@domain1.com,user2@domain2.com', 'dhimport_version1');

        $emails = rlip_get_notification_emails('dhimport_version1');

        $expectedresult = array('user1@domain1.com',
                                 'user2@domain2.com');

        $this->assertEquals($emails, $expectedresult);
    }

    /**
     * Validate that we can get notification emails configured for an export plugin
     */
    public function test_getnotificationemailsforexport() {
        set_config('emailnotification', 'user1@domain1.com,user2@domain2.com', 'dhexport_version1');

        $emails = rlip_get_notification_emails('dhexport_version1');

        $expectedresult = array('user1@domain1.com',
                                 'user2@domain2.com');

        $this->assertEquals($emails, $expectedresult);
    }

    /**
     * Validate that notification email retrieval handles empty case
     */
    public function test_getnotificationemailsreturnsemptyarrayforemptysetting() {
        set_config('emailnotification', '', 'dhimport_version1');

        $emails = rlip_get_notification_emails('dhimport_version1');
        $this->assertEquals($emails, array());
    }

    /**
     * Validate that notification email retrieval trims emails
     */
    public function test_getnotificationemailstrimswhitespacebetweenemails() {
        set_config('emailnotification', 'user1@domain1.com , user2@domain2.com', 'dhimport_version1');

        $emails = rlip_get_notification_emails('dhimport_version1');

        $expectedresult = array('user1@domain1.com', 'user2@domain2.com');

        $this->assertEquals($emails, $expectedresult);
    }

    /**
     * Validate that notification email retrieval removes empty email addresses
     */
    public function test_getnotificationemailsremovesemptyemails() {
        set_config('emailnotification', 'user1@domain1.com, ,user2@domain2.com', 'dhimport_version1');

        $emails = rlip_get_notification_emails('dhimport_version1');

        $expectedresult = array('user1@domain1.com',
                                 'user2@domain2.com');

        $this->assertEquals($emails, $expectedresult);
    }

    /**
     * Validate that notification email retrieval removes improperly formatted email addresses
     */
    public function test_getnoficiationemailsremovesinvalidemails() {
        set_config('emailnotification', 'user1@domain1.com,bogus,user2@domain2.com', 'dhimport_version1');

        $emails = rlip_get_notification_emails('dhimport_version1');

        $expectedresult = array('user1@domain1.com',
                                 'user2@domain2.com');

        $this->assertEquals($emails, $expectedresult);
    }

    /**
     * Validate that email recipient retrieval creates a mock user record for
     * an email address that is not within Moodle
     */
    public function test_getemailrecipientreturnsmockuserfornonexistentemailaddress() {
        $recipient = rlip_get_email_recipient('test@user.com');

        $expectedresult = new stdClass;
        $expectedresult->email = 'test@user.com';

        $this->assertEquals($recipient, $expectedresult);
    }

    /**
     * Validate that the email recipient retrieval uses an existing user record
     * for an email address that is within Moodle
     */
    public function test_getemailrecipientreturnsvaliduserforexistingemailaddress() {
        global $DB;

        $this->create_complete_test_user();

        $recipient = rlip_get_email_recipient('rlipuser@rlipdomain.com');

        $expectedresult = $DB->get_record('user', array('username' => 'rlipusername'));
        $this->assertEquals($recipient, $expectedresult);
    }

    /**
     * Validate zip file name construction for logging emails related to an
     * import plugin
     */
    public function test_getemailarchivefilenameforimport() {
        $time = time();
        $zipname = rlip_email_archive_name('dhimport_version1', $time);

        $datedisplay = date('M_d_Y_His', $time);
        $expectedfilename = 'import_version1_scheduled_'.$datedisplay.'.zip';
        $this->assertEquals($zipname, $expectedfilename);
    }

    /**
     * Validate zip file name construction for logging emails related to an
     * export plugin
     */
    public function test_getemailarchivefilenameforexport() {
        $time = time();
        $zipname = rlip_email_archive_name('dhexport_version1', $time);

        $datedisplay = date('M_d_Y_His', $time);
        $expectedfilename = 'export_version1_scheduled_'.$datedisplay.'.zip';
        $this->assertEquals($zipname, $expectedfilename);
    }

    /**
     * Validate zip file name construction for logging emails related to an
     * import plugin
     */
    public function test_getemailarchivefilenameformanual() {
        $time = time();
        $zipname = rlip_email_archive_name('dhimport_version1', $time, true);

        $datedisplay = date('M_d_Y_His', $time);
        $expectedfilename = 'import_version1_manual_'.$datedisplay.'.zip';
        $this->assertEquals($zipname, $expectedfilename);
    }

    /**
     * Validate compression of log files into a zip file
     */
    public function test_compresslogsforemail() {
        global $CFG, $DB;

        $logids = array();

        for ($i = 1; $i <= 3; $i++) {
            // Create summary records.
            $filename = 'compress_log_'.$i.'.txt';
            $oldpath = dirname(__FILE__).'/other/'.$filename;
            $newpath = $CFG->dataroot.'/'.$filename;
            copy($oldpath, $newpath);

            $summarylog = new stdClass;
            $summarylog->logpath = $newpath;
            $summarylog->plugin = 'dhimport_version1';
            $summarylog->userid = 9999;
            $summarylog->targetstarttime = 0;
            $summarylog->starttime = 0;
            $summarylog->endtime = 0;
            $summarylog->filesuccesses = 0;
            $summarylog->filefailures = 0;
            $summarylog->storedsuccesses = 0;
            $summarylog->storedfailures = 0;
            $summarylog->statusmessage = '';
            $summarylog->logpath = $newpath;
            $logids[] = $DB->insert_record(RLIP_LOG_TABLE, $summarylog);
        }

        $zipfilename = rlip_compress_logs_email('dhimport_version1', $logids);

        for ($i = 1; $i <= 3; $i++) {
            // Clean up copies.
            $filename = 'compress_log_'.$i.'.txt';
            @unlink($CFG->dataroot.'/'.$filename);
        }

        // Open zip_archive and verify all logs included.
        $zip = new zip_archive();
        $result = $zip->open($CFG->dataroot.'/'.$zipfilename, file_archive::OPEN);
        $this->assertTrue($result);
        $this->assertEquals(3, $zip->count());

        $files = $zip->list_files();
        // Validate zip contents.
        for ($i = 1; $i <= 3; $i++) {
            $filename = 'compress_log_'.$i.'.txt';

            $found = false;
            foreach ($files as $file) {
                if ($file->pathname == $filename) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found);
        }

        $zip->close();
    }

    /**
     * Validate edge case for log compression method
     */
    public function test_compresslogsforemailreturnsfalseforemptylist() {
        $zipfilename = rlip_compress_logs_email('dhimport_version1', array());

        $this->assertFalse($zipfilename);
    }

    /**
     * Validate that the compression method is handles a lack of valid log files
     */
    public function test_compresslogsforemailreturnsfalsewhennologfileisvalid() {
        global $DB;

        // Create a summary log with an empty log file path.
        $summarylog = new stdClass;
        $summarylog->logpath = 'testpath';
        $summarylog->plugin = 'dhimport_version1';
        $summarylog->userid = 9999;
        $summarylog->targetstarttime = 0;
        $summarylog->starttime = 0;
        $summarylog->endtime = 0;
        $summarylog->filesuccesses = 0;
        $summarylog->filefailures = 0;
        $summarylog->storedsuccesses = 0;
        $summarylog->storedfailures = 0;
        $summarylog->statusmessage = '';
        $summarylog->logpath = null;
        $logid = $DB->insert_record(RLIP_LOG_TABLE, $summarylog);

        // Obtian the zip file name.
        $zipfilename = rlip_compress_logs_email('dhimport_version1', array($logid));

        // Validate that the scenario was handled.
        $this->assertFalse($zipfilename);
    }

    /**
     * Validate that, if the setting for disabling the internal Moodle cron is
     * enabled, tasks do not run in the internal cron
     */
    public function test_runipjobskipsjobsifdisabledincronenabled() {
        global $DB;

        // Set up the export file name.
        set_config('export_file', 'export.csv', 'dhexport_version1');

        // Disable running in the standard cron.
        set_config('disableincron', '1', 'local_datahub');

        // Create the job (doesn't really matter which plugin).
        $data = array('plugin' => 'dhexport_version1',
                      'period' => '5m',
                      'type' => 'dhexport');
        $taskid = rlip_schedule_add_job($data);

        // Set up the job to run on the next "cron".
        $DB->execute("UPDATE {local_eliscore_sched_tasks}
                          SET nextruntime = ?", array(0));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."}
                      SET nextruntime = ?", array(0));

        // Run the export.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Validate that tasks are not run by checking that their next runtime values have not been changed..
        $exists = $DB->record_exists('local_eliscore_sched_tasks', array('nextruntime' => 0));
        $this->assertTrue($exists);

        $exists = $DB->record_exists(RLIP_SCHEDULE_TABLE, array('nextruntime' => 0));
        $this->assertTrue($exists);
    }

    public function data_root_paths_provider() {
        global $CFG;

        return array(
            array($CFG->dataroot.'/myexport/', '/myexport'),
            array($CFG->dataroot.'/datahub/import', '/datahub/import'),
            array('/some/bad/path/on/the/filesystem', ''),
            array('/', ''),
            array('', '')
        );
    }


    /**
     * Validate that the function to translate an IP 1.9 path configuration value during the IP 2.0 upgrade works correctly.
     *
     * @dataProvider data_root_paths_provider
     */
    public function test_datarootpathtranslation($a, $b) {
        $this->assertEquals($b, rlip_data_root_path_translation($a));
    }
}

