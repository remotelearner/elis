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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Unit testing specifically related to scheduling the Version 1 ELIS import
 */
class version1elisScheduledImportTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'config'               => 'moodle',
            'config_plugins'       => 'moodle',
            'user'                 => 'moodle',
            'user_info_field'      => 'moodle',
            'elis_scheduled_tasks' => 'elis_core',
            user::TABLE            => 'elis_program',
            usermoodle::TABLE      => 'elis_program',
            RLIP_LOG_TABLE         => 'block_rlip',
            RLIP_SCHEDULE_TABLE    => 'block_rlip',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array(
            'context' => 'moodle',
        );
    }

    /**
     * Validate that, when the available runtime is exceeded, IP leaves the next
     * runtime for this plugin unchanged
     */
    public function testImportLeavesNextRuntimeWhenTimeLimitExceeded() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $file_path = '/block_rlip_phpunit/';
        $file_name = 'userfile2.csv';
        set_config('schedule_files_path', $file_path, 'rlipimport_version1elis');
        set_config('user_schedule_file', $file_name, 'rlipimport_version1elis');

        //set up the test directory
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir.$file_name);

        //create the job
        $data = array('plugin' => 'rlipimport_version1elis',
                      'period' => '5m',
                      'type' => 'rlipimport');
        $taskid = rlip_schedule_add_job($data);

        //set next runtime values to a known state
        $DB->execute("UPDATE {elis_scheduled_tasks} SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET nextruntime = ?", array(1));

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname, -1);

        //clean-up data file & test dir
        @unlink($testdir.$file_name);
        @rmdir($testdir);

        //validate that nextruntime values haven't changed
        $exists = $DB->record_exists('elis_scheduled_tasks', array('nextruntime' => 1));
        $this->assertTrue($exists);

        $exists = $DB->record_exists(RLIP_SCHEDULE_TABLE, array('nextruntime' => 1));
        $this->assertTrue($exists);
    }

    /**
     * Validate that import starts from saved state
     */
    public function testImportFromSavedState() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $file_path = '/block_rlip_phpunit/';
        $file_name = 'userfile2.csv';
        set_config('schedule_files_path', $file_path, 'rlipimport_version1elis');
        set_config('user_schedule_file', $file_name, 'rlipimport_version1elis');

        //set up the test directory
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir.$file_name);

        //create the job
        $data = array(
            'plugin' => 'rlipimport_version1elis',
            'period' => '5m',
            'type'   => 'rlipimport'
        );
        $taskid = rlip_schedule_add_job($data);

        //set next runtime values to a known state
        $DB->execute("UPDATE {elis_scheduled_tasks} SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET nextruntime = ?", array(1));

        //put the import in a particular state
        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('plugin' => 'rlipimport_version1elis'));
        $state = new stdClass;
        $state->result = false;
        $state->entity = 'user';
        $state->filelines = 4;
        $state->linenumber = 2; // Should start at line 2 of userfile2.csv
        $ipjobdata = unserialize($job->config);
        $ipjobdata['state'] = $state;
        $job->config = serialize($ipjobdata);
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //clean-up data file & test dir
        @unlink($testdir.$file_name);
        @rmdir($testdir);

        //validation
        $count = $DB->count_records(user::TABLE);
        $this->assertEquals(2, $count);

        //validate specific records
        $exists = $DB->record_exists(user::TABLE, array('idnumber' => 'idnumber2'));
        $this->assertTrue($exists);
        $exists = $DB->record_exists(user::TABLE, array('idnumber' => 'idnumber3'));
        $this->assertTrue($exists);
    }
}