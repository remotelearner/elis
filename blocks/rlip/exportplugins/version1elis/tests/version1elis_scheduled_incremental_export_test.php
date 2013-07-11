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
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/course.class.php'));
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/student.class.php'));
require_once(elispm::lib('data/user.class.php'));

/**
 * Test class for validating basic export data during a scheduled, incremental export.
 * @group block_rlip
 * @group rlipexport_version1elis
 */
class version1elisscheduledincrementalexport_testcase extends rlip_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        // Data for multiple users.
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => $csvloc.'/pmcourses.csv',
            pmclass::TABLE => $csvloc.'/pmclasses.csv',
            student::TABLE => $csvloc.'/completetimestudents.csv',
            user::TABLE => $csvloc.'/pmusers.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Specifies the data for the expected header row
     * @return array The appropriate header row
     */
    private function get_header() {
        return array(
                get_string('header_firstname', 'rlipexport_version1elis'),
                get_string('header_lastname', 'rlipexport_version1elis'),
                get_string('header_username', 'rlipexport_version1elis'),
                get_string('header_useridnumber', 'rlipexport_version1elis'),
                get_string('header_courseidnumber', 'rlipexport_version1elis'),
                get_string('header_startdate', 'rlipexport_version1elis'),
                get_string('header_enddate', 'rlipexport_version1elis'),
                get_string('header_status', 'rlipexport_version1elis'),
                get_string('header_grade', 'rlipexport_version1elis'),
                get_string('header_letter', 'rlipexport_version1elis')
        );
    }

    /**
     * Specifies the data for the expected first data row
     * @return array The appropriate data row
     */
    private function get_first_row() {
        return array(
                'exportfirstname',
                'exportlastname',
                'exportusername',
                'exportidnumber',
                'testcourseidnumber',
                date('M/d/Y', 1000000000),
                date('M/d/Y', 100),
                'COMPLETED',
                '70.00000',
                'C-'
        );
    }

    /**
     * Specifies the data for the expected second data row
     * @return array The appropriate data row
     */
    private function get_second_row() {
        return array(
                'exportfirstname2',
                'exportlastname2',
                'exportusername2',
                'exportidnumber2',
                'testcourseidnumber',
                date('M/d/Y', 1000000000),
                date('M/d/Y', 1500000000),
                'COMPLETED',
                '70.00000',
                'C-'
        );
    }

    /**
     * Validate that, on first run, the export contains the header and both
     * rows of data
     */
    public function test_exportcontainsalldataonfirstrun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Load data.
        $this->load_csv_data();

        // Set up configuration.
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'export_version1elis.csv', 'rlipexport_version1elis');
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        // Create the job.
        $data = array(
            'plugin' => 'rlipexport_version1elis',
            'period' => '5m',
            'type' => 'rlipexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Run the job.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $starttime = time();
        run_ipjob($taskname);

        // Path to export file.
        $datestr = date('M_j_Y_His', $starttime);
        $filepath = $CFG->dataroot.'/rlip/rlipexport_version1elis/export_version1elis_'.$datestr.'.csv';

        // Validate that the file exists.
        $handle = fopen($filepath, 'r');
        $this->assertNotEquals(false, $handle);

        // Validate header.
        $header = fgetcsv($handle);
        $this->assertEquals($this->get_header(), $header);

        // Validate first row.
        $oldrecord = fgetcsv($handle);
        $this->assertEquals($this->get_first_row(), $oldrecord);

        // Validate second row.
        $newrecord = fgetcsv($handle);
        $this->assertEquals($this->get_second_row(), $newrecord);

        // Validate end of file.
        $eof = fgetcsv($handle);
        $this->assertEquals(false, $eof);
    }

    /**
     * Validate that, on subsequent runs, the export contains the header and only
     * the more recent data row
     */
    public function test_exportcontainsalldatasincelastrun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        // Load data.
        $this->load_csv_data();

        // Set up configuration.
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'export_version1elis.csv', 'rlipexport_version1elis');
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        // Create the job.
        $data = array(
            'plugin' => 'rlipexport_version1elis',
            'period' => '5m',
            'type' => 'rlipexport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Mark the job as having been run after the first record was created.
        $DB->execute("UPDATE {elis_scheduled_tasks} SET lastruntime = ?", array(1000));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET lastruntime = ?", array(1000));

        // Run the job.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $starttime = time();
        run_ipjob($taskname);

        // Path to export file.
        $datestr = date('M_j_Y_His', $starttime);
        $filepath = $CFG->dataroot.'/rlip/rlipexport_version1elis/export_version1elis_'.$datestr.'.csv';

        // Validate that the file exists.
        $this->assertTrue(file_exists($filepath));
        $handle = fopen($filepath, 'r');
        $this->assertNotEquals(false, $handle);

        // Validate header.
        $header = fgetcsv($handle);
        $this->assertEquals($this->get_header(), $header);

        // Validate second row (i.e. earlier row skipped).
        $newrecord = fgetcsv($handle);
        $this->assertEquals($this->get_second_row(), $newrecord);

        // Validate end of file.
        $eof = fgetcsv($handle);
        $this->assertEquals(false, $eof);
    }
}