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

/**
 * Unit testing specifically related to scheduling the Version 1 ELIS import
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisscheduledimport_testcase extends rlip_elis_test {

    /**
     * Validate that, when the available runtime is exceeded, IP leaves the next runtime for this plugin unchanged.
     */
    public function test_importleavesnextruntimewhentimelimitexceeded() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $DB->delete_records('elis_scheduled_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        $filepath = '/block_rlip_phpunit/';
        $filename = 'userfile2.csv';
        set_config('schedule_files_path', $filepath, 'rlipimport_version1elis');
        set_config('user_schedule_file', $filename, 'rlipimport_version1elis');

        // Set up the test directory.
        $testdir = $CFG->dataroot.$filepath;
        mkdir($testdir, 0777, true);
        copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create the job.
        $data = array(
            'plugin' => 'rlipimport_version1elis',
            'period' => '5m',
            'type' => 'rlipimport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Set next runtime values to a known state.
        $DB->execute("UPDATE {elis_scheduled_tasks} SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET nextruntime = ?", array(1));

        // Run the import.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname, -1);

        // Validate that nextruntime values haven't changed.
        $exists = $DB->record_exists('elis_scheduled_tasks', array('nextruntime' => 1));
        $this->assertTrue($exists);

        $exists = $DB->record_exists(RLIP_SCHEDULE_TABLE, array('nextruntime' => 1));
        $this->assertTrue($exists);
    }

    /**
     * Validate that import starts from saved state
     */
    public function test_importfromsavedstate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $DB->delete_records('elis_scheduled_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        $filename = 'userfile2.csv';
        set_config('user_schedule_file', $filename, 'rlipimport_version1elis');

        // Set up the test directory.
        $testdir = $CFG->dataroot.'/rlip/rlipimport_version1elis/temp/';
        mkdir($testdir, 0777, true);
        copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create the job.
        $data = array(
            'plugin' => 'rlipimport_version1elis',
            'period' => '5m',
            'type'   => 'rlipimport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Set next runtime values to a known state.
        $DB->execute("UPDATE {elis_scheduled_tasks} SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET nextruntime = ?", array(1));

        // Put the import in a particular state.
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('plugin' => 'rlipimport_version1elis'));
        $state = new stdClass;
        $state->result = false;
        $state->entity = 'user';
        $state->filelines = 4;
        $state->linenumber = 2; // Should start at line 2 of userfile2.csv.
        $ipjobdata = unserialize($job->config);
        $ipjobdata['state'] = $state;
        $job->config = serialize($ipjobdata);
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Run the import.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        // Validation.
        $count = $DB->count_records(user::TABLE);
        $this->assertEquals(2, $count);

        // Validate specific records.
        $exists = $DB->record_exists(user::TABLE, array('idnumber' => 'idnumber2'));
        $this->assertTrue($exists);
        $exists = $DB->record_exists(user::TABLE, array('idnumber' => 'idnumber3'));
        $this->assertTrue($exists);
    }

    /**
     * Validate that scheduled import is prevented if existing incomplete run exists.
     */
    public function test_importpreventmultipleimports() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/core/lib/tasklib.php');

        $DB->delete_records('elis_scheduled_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        $filepath = '/block_rlip_phpunit/';
        $filename = 'userfile2.csv';
        set_config('schedule_files_path', $filepath, 'rlipimport_version1elis');
        set_config('user_schedule_file', $filename, 'rlipimport_version1elis');

        // Set up the test directory.
        $testdir = $CFG->dataroot.$filepath;
        mkdir($testdir, 0777, true);
        copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create the job.
        $data = array(
            'plugin' => 'rlipimport_version1elis',
            'period' => '5m',
            'type' => 'rlipimport'
        );
        $taskid1 = rlip_schedule_add_job($data);

        // Run the import with a time in the past so it stops immediately.
        $taskname1 = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid1));
        $result1 = run_ipjob($taskname1, -1);

        // Validate the first import run was started.
        $this->assertEquals(true, $result1);

        // Validate the state was saved.
        $config = $DB->get_field(RLIP_SCHEDULE_TABLE, 'config', array('id' => 1));
        $this->assertRegExp('/s:5:"state";/', $config);

        // Create a duplicate job.
        $taskid2 = rlip_schedule_add_job($data);

        // Get the initial duplicate job lastruntime and nextruntime values.
        $initlastruntime = $DB->get_field('elis_scheduled_tasks', 'lastruntime', array('id' => $taskid2));
        $initnextruntime = $DB->get_field('elis_scheduled_tasks', 'nextruntime', array('id' => $taskid2));

        // Emulate the ELIS cron adjusting the job run times.
        $task = $DB->get_record('elis_scheduled_tasks', array('id' => $taskid2));
        $task->lastruntime = time();
        $nextruntime = cron_next_run_time($task->lastruntime, (array)$task);
        $task->nextruntime = $nextruntime;
        $DB->update_record('elis_scheduled_tasks', $task);

        // Attempt to do another import run.
        $taskname2 = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid2));
        $result2 = run_ipjob($taskname2);

        // Validate that the second import run attempt fails.
        $this->assertEquals(false, $result2);

        // Get the later lastruntime and nextruntime values.
        $lastruntime = $DB->get_field('elis_scheduled_tasks', 'lastruntime', array('id' => $taskid2));
        $nextruntime = $DB->get_field('elis_scheduled_tasks', 'nextruntime', array('id' => $taskid2));

        // Validate that the job run time values are back to initial values.
        $this->assertEquals($initlastruntime, $lastruntime);
        $this->assertEquals($initnextruntime, $nextruntime);
    }
}