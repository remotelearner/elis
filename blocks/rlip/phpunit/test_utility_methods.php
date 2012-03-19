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
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Class for testing utility methods
 */
class utilityMethodTest extends elis_database_test {

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('ip_schedule' => 'block_rlip',
                     'elis_scheduled_tasks' => 'elis_core',
                     'config_plugins' => 'moodle',
                     'block_rlip_summary_log' => 'block_rlip');
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
        $job = $DB->get_record('ip_schedule', array('id' => $jobid));

        //make sure the next runtime is between 5 and 6 minutes from now
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        //make sure both records have the same next run time
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }

    /**
     * Validate that running a job sets the right next runtime on the IP
     * schedule record 
     */
    function testRunningJobSetsIPNextRuntime() {
        global $CFG, $DB;

        //set up the export file path
        $filename = $CFG->dataroot.'/rliptestexport.csv';
        set_config('export_file', $filename, 'rlipexport_version1');

        //create a scheduled job
        $data = array('plugin' => 'rlipexport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a value that is out of range on both records
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field('ip_schedule', 'id', array('plugin' => 'rlipexport_version1'));
        $job->nextruntime = 99;
        $DB->update_record('ip_schedule', $job);

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $starttime = time();
        run_ipjob($taskname);

        //obtain both records
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid));
        list($name, $jobid) = explode('_', $task->taskname);
        $job = $DB->get_record('ip_schedule', array('id' => $jobid));

        //make sure the next runtime is between 5 and 6 minutes from initial value
        $this->assertGreaterThanOrEqual($starttime + 5 * MINSECS, (int)$task->nextruntime);
        $this->assertGreaterThanOrEqual((int)$task->nextruntime, $starttime + 6 * MINSECS);

        //make sure both records have the same next run time
        $this->assertEquals((int)$task->nextruntime, (int)$job->nextruntime);
    }
}
