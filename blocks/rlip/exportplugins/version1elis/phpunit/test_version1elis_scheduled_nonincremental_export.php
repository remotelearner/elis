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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/lib.php');

/**
 * Test class for validating basic export data during a scheduled, nonincremental
 * export
 */
class version1elisScheduledNonincrementalExportTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     RLIP_SCHEDULE_TABLE => 'block_rlip',
                     course::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     'config_plugins' => 'moodle',
                     'elis_scheduled_tasks' => 'elis_core',
                     RLIPEXPORT_VERSION1ELIS_FIELD_TABLE => 'rlipexport_version1elis',
                     //in case customized
                     'grade_letters' => 'moodle');
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();

	    //data for multiple users
	    $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourses.csv');
	    $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclasses.csv');
	    $dataset->addTable(student::TABLE, dirname(__FILE__).'/completetimestudents.csv');
	    $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmusers.csv');

        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Specifies the data for the expected header row
     * @return array The appropriate header row
     */
    private function get_header() {
        return array(get_string('header_firstname', 'rlipexport_version1elis'),
                     get_string('header_lastname', 'rlipexport_version1elis'),
                     get_string('header_username', 'rlipexport_version1elis'),
                     get_string('header_useridnumber', 'rlipexport_version1elis'),
                     get_string('header_courseidnumber', 'rlipexport_version1elis'),
                     get_string('header_startdate', 'rlipexport_version1elis'),
                     get_string('header_enddate', 'rlipexport_version1elis'),
                     get_string('header_status', 'rlipexport_version1elis'),
                     get_string('header_grade', 'rlipexport_version1elis'),
                     get_string('header_letter', 'rlipexport_version1elis'));
    }

    /**
     * Specifies the data for the expected first data row
     * @return array The appropriate data row
     */
    private function get_first_row() {
        return array('exportfirstname',
                     'exportlastname',
                     'exportusername',
                     'exportidnumber',
                     'testcourseidnumber',
                     date('M/d/Y', 1000000000),
                     date('M/d/Y', 100),
                     'COMPLETED',
                     '70.00000',
                     'C-');
    }

    /**
     * Specifies the data for the expected second data row
     * @return array The appropriate data row
     */
    private function get_second_row() {
        return array('exportfirstname2',
                     'exportlastname2',
                     'exportusername2',
                     'exportidnumber2',
                     'testcourseidnumber',
                     date('M/d/Y', 1000000000),
                     date('M/d/Y', 1500000000),
                     'COMPLETED',
                     '70.00000',
                     'C-');
    }

    /**
     * Validate that, on first run, the export contains the header and both
     * rows of data
     */
    public function testExportContainsAllDataOnFirstRun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //load data
        $this->load_csv_data();

        //set up configuration
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'export_version1elis.csv', 'rlipexport_version1elis');
        set_config('nonincremental', 1, 'rlipexport_version1elis');

        //create the job
        $data = array('plugin' => 'rlipexport_version1elis',
                      'period' => '5m',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //path to export file
        $filepath = $CFG->dataroot.'/rlip/rlipexport_version1elis/export_version1elis.csv';

        //validate that the file exists
        $handle = fopen($filepath, 'r');
        $this->assertNotEquals(false, $handle);

        //validate header
        $header = fgetcsv($handle);
        $this->assertEquals($this->get_header(), $header);

        //validate first row
        $oldrecord = fgetcsv($handle);
        $this->assertEquals($this->get_first_row(), $oldrecord);

        //validate second row
        $newrecord = fgetcsv($handle);
        $this->assertEquals($this->get_second_row(), $newrecord);

        //validate end of file
        $eof = fgetcsv($handle);
        $this->assertEquals(false, $eof);
    }

    /**
     * Validate that, on subsequent runs, the export contains the header and both
     * rows of data
     */
    public function testExportContainsAllDataOnSubsequentRun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //load data
        $this->load_csv_data();

        //set up configuration
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'export_version1elis.csv', 'rlipexport_version1elis');
        set_config('nonincremental', 1, 'rlipexport_version1elis');

        //create the job
        $data = array('plugin' => 'rlipexport_version1elis',
                      'period' => '5m',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //mark the job as having been run after the first record was created
        $DB->execute("UPDATE {elis_scheduled_tasks}
                          SET lastruntime = ?", array(1000));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."}
                      SET lastruntime = ?", array(1000));

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        //path to export file
        $filepath = $CFG->dataroot.'/rlip/rlipexport_version1elis/export_version1elis.csv';

        //validate that the file exists
        $handle = fopen($filepath, 'r');
        $this->assertNotEquals(false, $handle);

        //validate header
        $header = fgetcsv($handle);
        $this->assertEquals($this->get_header(), $header);

        //validate first row
        $oldrecord = fgetcsv($handle);
        $this->assertEquals($this->get_first_row(), $oldrecord);

        //validate second row
        $newrecord = fgetcsv($handle);
        $this->assertEquals($this->get_second_row(), $newrecord);

        //validate end of file
        $eof = fgetcsv($handle);
        $this->assertEquals(false, $eof);
    }

    /**
     * Validate that, when the available runtime is exceeded, IP leaves the next
     * runtime for this plugin unchanged
     */
    public function testExportLeavesNextRuntimeWhenTimeLimitExceeded() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //load data
        $this->load_csv_data();

        //set up configuration
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'export_version1elis.csv', 'rlipexport_version1elis');
        set_config('nonincremental', 1, 'rlipexport_version1elis');

        //create the job
        $data = array('plugin' => 'rlipexport_version1elis',
                      'period' => '5m',
                      'type' => 'rlipexport');
        $taskid = rlip_schedule_add_job($data);

        //set next runtime values to a known state
        $DB->execute("UPDATE {elis_scheduled_tasks}
                          SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."}
                      SET nextruntime = ?", array(1));

        //run the job
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname, -1);

        //validate that nextruntime values haven't changed
        $exists = $DB->record_exists('elis_scheduled_tasks', array('nextruntime' => 1));
        $this->assertTrue($exists);

        $exists = $DB->record_exists(RLIP_SCHEDULE_TABLE, array('nextruntime' => 1));
        $this->assertTrue($exists);
    }
}