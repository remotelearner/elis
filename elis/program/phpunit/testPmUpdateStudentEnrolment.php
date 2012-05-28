<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage programmanager
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */
require_once(dirname(__FILE__).'/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once(elispm::lib('lib.php'));
require_once(elispm::lib('data/student.class.php'));

/**
 * Class for testing the pm_update_student_enrolment method and its ability to
 * fail students whose class enrolment are in progress and past the class end date
 */
class pmUpdateStudentEnrolmentTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array The mapping of overlay tables to components
     */
    static protected function get_overlay_tables() {
        return array('config' => 'moodle',
                     'user' => 'moodle',
                     course::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     user::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     *
     * @return array The mapping of ignored tables to components
     */
    static protected function get_ignored_tables() {
        return array('context' => 'moodle',
                     'message' => 'moodle',
                     'message_read' => 'moodle',
                     'message_working' => 'moodle');
    }

    /**
     * Load CSV data from file
     */
    function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        //need PM course to create PM class
        $dataset->addTable(course::TABLE, elis::component_file('program', 'phpunit/pmcourse.csv'));
        //need PM user to create enrolment
        $dataset->addTable(user::TABLE, elis::component_file('program', 'phpunit/pmusers.csv'));
        //needed to prevent messaging errors
        $dataset->addTable('user', elis::component_file('program', 'phpunit/mdlusers.csv'));
        //need PM classes to create associations
        $dataset->addTable(pmclass::TABLE, elis::component_file('program', 'phpunit/pmclass.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Data provider for testing validation condition on enrolment status
     *
     * @return array A list of enrolments with a variety of enrolment statuses
     */
    function enrolmentStatusProvider() {
        $associations = array();
        $associations[] = array('classid' => 100,
                                'userid' => 1,
                                'completestatusid' => STUSTATUS_PASSED,
                                'completetime' => 1000000000,
                                'endtime' => 1000000000);
        $associations[] = array('classid' => 100,
                                'userid' => 2,
                                'completestatusid' => STUSTATUS_FAILED,
                                'completetime' => 1000000000,
                                'endtime' => 1000000000);
        $associations[] = array('classid' => 100,
                                'userid' => 3,
                                'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                'completetime' => 1000000000,
                                'endtime' => 1000000000);
        return array(array($associations));
    }

    /**
     * Validate that the method respect enrolment status
     *
     * @param array $associations A list of enrolments/ associations to validate
     *                            against
     * @dataProvider enrolmentStatusProvider
     */
    public function testPmUpdateStudentEnrolmentRespectsEnrolmentStatus($associations) {
        global $DB;

        //prevent messaging emails from being sent
        set_config('noemailever', true);

        //necessary data
        $this->load_csv_data();

        //track the final data state for validation
        $expected_associations = array();

        foreach ($associations as $key => $association) {
            //create student enrolment
            $record = new student($association);
            $record->save();

            $expected_associations[] = $association;

            if ($association['completestatusid'] == STUSTATUS_NOTCOMPLETE) {
                //enrolment will lapse
                $expected_associations[$key]['completestatusid'] = STUSTATUS_FAILED;
                $expected_associations[$key]['completetime'] = 0;
            }
        }

        //fail expired students
        pm_update_student_enrolment();

        //validate count
        $this->assertEquals(count($expected_associations), $DB->count_records(student::TABLE));

        //validate records specifically
        foreach ($expected_associations as $expected_association) {
            $exists = $DB->record_exists(student::TABLE, $expected_association);
            $this->assertTrue($exists);
        }
    }

    /**
     * Data provider for testing validation condition on enrolment end times
     *
     * @return array A list of enrolments with a variety of enrolment end times
     */
    function enrolmentEndtimeProvider() {
        $associations = array();
        $associations[] = array('classid' => 100,
                                'userid' => 1,
                                'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                'completetime' => 1000000000,
                                'endtime' => 0); 
        $associations[] = array('classid' => 100,
                                'userid' => 2,
                                'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                'completetime' => 1000000000,
                                'endtime' => time() + 1000);
        $associations[] = array('classid' => 100,
                                'userid' => 3,
                                'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                'completetime' => 1000000000,
                                'endtime' => time() - 1000);
        return array(array($associations));
    }

    /**
     * Validate that the method respects end time
     *
     * @dataProvider enrolmentEndtimeProvider
     */
    public function testPmUpdateStudentEnrolmentRespectsEnrolmentEndtime($associations) {
        global $DB;

        //prevent messaging emails from being sent
        set_config('noemailever', true);

        //necessary data
        $this->load_csv_data();

        //track the final data state for validation
        $expected_associations = array();

        foreach ($associations as $key => $association) {
            //create student enrolment
            $record = new student($association);
            $record->save();

            $expected_associations[] = $association;

            if ($association['endtime'] > 0 && $association['endtime'] < time()) {
                //enrolment will lapse
                $expected_associations[$key]['completestatusid'] = STUSTATUS_FAILED;
                $expected_associations[$key]['completetime'] = 0;
            }
        }

        //fail expired students
        pm_update_student_enrolment();

        //validate count
        $this->assertEquals(count($expected_associations), $DB->count_records(student::TABLE));

        //validate records specifically
        foreach ($expected_associations as $expected_association) {
            $exists = $DB->record_exists(student::TABLE, $expected_association);
            $this->assertTrue($exists);
        }
    }

    /**
     * Data provider for testing successful execution of the method
     *
     * @return array A list of enrolments satisfying the necessary criteria
     */
    function enrolmentFailProvider() {
        $associations = array();

        for ($i = 1; $i <= 3; $i++) {
            $associations[] = array('classid' => 100,
                                    'userid' => $i,
                                    'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                    'completetime' => 1000000000,
                                    'endtime' => 1000000000);
        }

        return array(array($associations));
    }

    /**
     * Validate the "succss" case of the method, i.e. failing students
     *
     * @dataProvider enrolmentFailProvider
     */
    public function testPmUpdateStudentEnrolmentFailsStudents($associations) {
        global $DB;

        //prevent messaging emails from being sent
        set_config('noemailever', true);

        //necessary data
        $this->load_csv_data();

        //track the final data state for validation
        $expected_associations = array();

        foreach ($associations as $key => $association) {
            //create student enrolment
            $record = new student($association);
            $record->save();

            //all enrolment will be set to failed
            $expected_associations[] = $association;
            $expected_associations[$key]['completestatusid'] = STUSTATUS_FAILED;
            $expected_associations[$key]['completetime'] = 0;
        }

        //fail expired students
        pm_update_student_enrolment();

        //validate count
        $this->assertEquals(count($expected_associations), $DB->count_records(student::TABLE));

        //validate data specifically
        foreach ($expected_associations as $expected_association) {
            $exists = $DB->record_exists(student::TABLE, $expected_association);
            $this->assertTrue($exists);
        }
    }
}