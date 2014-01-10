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
 * @package    dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/local/datahub/exportplugins/version1elis/tests/other/rlip_fileplugin_export.class.php');
require_once($CFG->dirroot.'/course/lib.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/classmoodlecourse.class.php'));
    require_once(elispm::lib('data/course.class.php'));
    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/student.class.php'));
    require_once(elispm::lib('data/user.class.php'));
}


/**
 * Test class for validating basic export data during a manual, nonincremental export.
 * @group local_datahub
 * @group dhexport_version1elis
 */
class version1elismanualnonincrementalexport_testcase extends rlip_elis_test {

    /**
     * Fetches our export data as a multi-dimensional array
     * @param bool $manual Set to true if a manual run
     * @param int $targetstarttime The timestamp representing the theoretical time when this task was meant to be run.
     * @param int $lastruntime The last time the export was run (required for incremental scheduled export).
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('dhexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // Set the export to be nonincremental.
        set_config('nonincremental', 1, 'dhexport_version1elis');

        // Plugin for file IO.
        $fileplugin = new rlip_fileplugin_export();
        $fileplugin->open(RLIP_FILE_WRITE);

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Load in our test data from CSV files
     * @param bool $multipleusers Whether to load information for a single user or for multiple users.
     */
    protected function load_csv_data($multipleusers = false) {
        $csvloc = dirname(__FILE__).'/fixtures';

        if ($multipleusers) {
            // Data for multiple users.
            $dataset = $this->createCsvDataSet(array(
                course::TABLE => $csvloc.'/pmcourses.csv',
                pmclass::TABLE => $csvloc.'/pmclasses.csv',
                student::TABLE => $csvloc.'/students.csv',
                user::TABLE => $csvloc.'/pmusers.csv',
            ));
        } else {
            // Data for a single user.
            $dataset = $this->createCsvDataSet(array(
                course::TABLE => $csvloc.'/pmcourse.csv',
                pmclass::TABLE => $csvloc.'/pmclass.csv',
                student::TABLE => $csvloc.'/student.csv',
                user::TABLE => $csvloc.'/pmuser.csv',
            ));
        }

        $this->loadDataSet($dataset);
    }

    /**
     * Provider to be used for validating the export header
     *
     * @return array The expected header structure
     */
    public function valid_header_provider() {
        $expectedheader = array(
                get_string('header_firstname', 'dhexport_version1elis'),
                get_string('header_lastname', 'dhexport_version1elis'),
                get_string('header_username', 'dhexport_version1elis'),
                get_string('header_useridnumber', 'dhexport_version1elis'),
                get_string('header_courseidnumber', 'dhexport_version1elis'),
                get_string('header_startdate', 'dhexport_version1elis'),
                get_string('header_enddate', 'dhexport_version1elis'),
                get_string('header_status', 'dhexport_version1elis'),
                get_string('header_grade', 'dhexport_version1elis'),
                get_string('header_letter', 'dhexport_version1elis')
        );
        return array(array($expectedheader));
    }

    /**
     * Validate that the export contains the appropriate headers
     *
     * @param array $expectedheader The expected header data
     * @dataProvider valid_header_provider
     */
    public function test_exportcontainscorrectheader($expectedheader) {
        // Setup.
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals($expectedheader, $data[0]);
    }

    /**
     * Provider for basic (core) export data
     *
     * @return array The expected column data
     */
    public function valid_data_provider() {
        $expecteddata = array(
                'exportfirstname',
                'exportlastname',
                'exportusername',
                'exportidnumber',
                'testcourseidnumber',
                date('M/d/Y', 1000000000),
                date('M/d/Y', 1500000000),
                'COMPLETED',
                '70.00000',
                'C-'
        );
        return array(array($expecteddata));
    }

    /**
     * Validate that the export contains the necessary data when the
     * approriate data is present in the database
     *
     * @param array $expecteddata The expected column data
     * @dataProvider valid_data_provider
     */
    public function test_exportcontainsvaliddata($expecteddata) {
        // Setup.
        $this->load_csv_data();
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(2, count($data));
        $this->assertEquals($expecteddata, $data[1]);
    }

    /**
     * Validate that the export works with the minimum amount of required data
     */
    public function test_exportworkswithminimalassociations() {
        global $CFG, $DB;

        // Setup.
        $this->load_csv_data();
        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(2, count($data));
    }

    /**
     * Data provider for necessary associations / entities
     *
     * @return array The list of tables containing required data
     */
    public function necessary_associations_provider() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            return array(
                    array(course::TABLE),
                    array(pmclass::TABLE),
                    array(student::TABLE),
                    array(user::TABLE)
            );
        } else {
            return array();
        }
    }

    /**
     * Validate that removing any one required association or entity will
     * invalidate data for consideration in the export
     *
     * @param string $tablename The name of a necessary database table
     * @dataProvider necessary_associations_provider
     */
    public function test_exportrespectsnecessaryassociations($tablename) {
        global $DB;

        // Setup.
        $this->load_csv_data();
        // Delete records from required table.
        $DB->delete_records($tablename);
        $data = $this->get_export_data();

        // Validation (should only have the header row).
        $this->assertEquals(1, count($data));
    }

    /**
     * Data provider used to validate all the various completion status states
     *
     * @return array An array containing each possible completion status state
     */
    public function completion_status_provider() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            return array(
                    array(student::STUSTATUS_NOTCOMPLETE),
                    array(student::STUSTATUS_FAILED),
                    array(student::STUSTATUS_PASSED)
            );
        } else {
            return array();
        }
    }

    /**
     * Validate that the export only includes passed enrolments
     *
     * @param int $status A completion status to use on the student enrolment
     * @dataProvider completion_status_provider
     */
    public function test_exportrespectscompletionstatus($status) {
        global $DB;

        // Setup.
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."} SET completestatusid = ?";
        $params = array($status);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        // Validation.
        if ($status == student::STUSTATUS_PASSED) {
            // The record should be included.
            $this->assertEquals(2, count($data));
        } else {
            // Should only have a header.
            $this->assertEquals(1, count($data));
        }
    }

    /**
     * Data provider for grade letters
     *
     * @return array An array specifying grade-"letter" pairs
     */
    public function grade_letter_provider() {
        return array(
                array(10, 1),
                array(20, 2),
                array(30, 3),
                array(40, 4),
                array(50, 5),
                array(60, 6),
                array(70, 7),
                array(80, 8),
                array(90, 9),
                array(100, 10)
        );
    }

    /**
     * Validate that the export respects grade letter boundaries and the
     * existence of an associated Moodle course
     *
     * @param int $grade The enrolment grade
     * @param int $letter The expected enrolment grade letter
     * @dataProvider grade_letter_provider
     */
    public function test_exportrespectsgradeletters($grade, $letter) {
        global $DB;

        // Setup.
        $this->load_csv_data();

        // Create a Moodle course category.
        $categorydata = new stdClass;
        $categorydata->name = 'testcategory';
        $categorydata->id = $DB->insert_record('course_categories', $categorydata);

        // Create a Moodle course.
        $coursedata = new stdClass;
        $coursedata->category = $categorydata->id;
        $coursedata->fullname = 'testcourse';
        $coursedata = create_course($coursedata);

        // Associate the PM class instance to the Moodle course.
        $classmoodle = new classmoodlecourse(array('classid' => 200, 'moodlecourseid' => $coursedata->id));
        $classmoodle->save();

        // Create grade letter mappings.
        $context = context_course::instance($coursedata->id);
        $mappings = array(
            10 => 1,
            20 => 2,
            30 => 3,
            40 => 4,
            50 => 5,
            60 => 6,
            70 => 7,
            80 => 8,
            90 => 9,
            100 => 10
        );
        foreach ($mappings as $insertlowerboundary => $insertletter) {
            $record = new stdClass;
            $record->contextid = $context->id;
            $record->lowerboundary = $insertlowerboundary;
            $record->letter = $insertletter;
            $DB->insert_record('grade_letters', $record);
        }

        // Set the enrolment grade.
        $sql = "UPDATE {".student::TABLE."} SET grade = ?";
        $params = array($grade);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(2, count($data));
        $this->assertEquals((string)$letter, $data[1][9]);
    }

    /**
     * Data provider for testing sort orders
     *
     * @return array Data needed for testing sort orders
     */
    public function sort_order_provider() {
        $data = array();
        // Sort first based on idnumber.
        $data[] = array(
                array('idnumber' => 'a'),
                array(),
                array(),
                array('idnumber' => 'b'),
                array(),
                array(),
                3,
                'a'
        );
        $data[] = array(
                array('idnumber' => 'b'),
                array(),
                array(),
                array('idnumber' => 'a'),
                array(),
                array(),
                3,
                'a'
        );
        // Sort second based on course idnumber.
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array(),
                array('idnumber' => 'a'),
                array('idnumber' => 'b'),
                array(),
                4,
                'a'
        );
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'b'),
                array(),
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array(),
                4,
                'a'
        );
        // Sort third based on completion time / date.
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1000000000),
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1500000000),
                6,
                date("M/d/Y", 1000000000)
        );
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1500000000),
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1000000000),
                6,
                date("M/d/Y", 1000000000)
        );
        // Sort fourth based on completion grade.
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 2),
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 1),
                8,
                2
        );
        $data[] = array(
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 1),
                array('idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 2),
                8,
                2
        );
        // Sort last based on record id.
        $data[] = array(
                array('username' => 'firstuser', 'idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 1),
                array('username' => 'seconduser', 'idnumber' => 'a'),
                array('idnumber' => 'a'),
                array('completetime' => 1, 'grade' => 1),
                2,
                'firstuser'
        );

        // Return all the appropriate data.
        return $data;
    }

    /**
     * Validate that the export respects the pre-defined sort order
     *
     * @param array $user1attributes Attributes for the first PM user
     * @param array $cd1attributes Attributes for the first course description
     * @param array $enrolment1attributes Attributes for the first enrolment
     * @param array $user2attributes Attributes for the second PM user
     * @param array $cd2attributes Attributes for the second course description
     * @param array $enrolment2attributes Attributes for the second enrolment
     * @param int checkfield Index of the field to check in the export data
     * @param string checkvalue Value to valdate atains
     * @dataProvider sort_order_provider
     */
    public function test_exportrespectssortorder($user1attributes, $cd1attributes, $enrolment1attributes,
                                                 $user2attributes, $cd2attributes, $enrolment2attributes,
                                                 $checkfield, $checkvalue) {
        global $DB;

        // Setup.
        $this->load_csv_data(true);

        // Persist the specified attributes.
        if (count($user1attributes) > 0) {
            $record = (object)$user1attributes;
            $record->id = 200;
            $DB->update_record(user::TABLE, $record);
        }

        if (count($cd1attributes) > 0) {
            $record = (object)$cd1attributes;
            $record->id = 200;
            $DB->update_record(course::TABLE, $record);
        }

        if (count($enrolment1attributes) > 0) {
            $record = (object)$enrolment1attributes;
            $record->id = 200;
            $DB->update_record(student::TABLE, $record);
        }

        if (count($user2attributes) > 0) {
            $record = (object)$user2attributes;
            $record->id = 201;
            $DB->update_record(user::TABLE, $record);
        }

        if (count($cd2attributes) > 0) {
            $record = (object)$cd2attributes;
            $record->id = 201;
            $DB->update_record(course::TABLE, $record);
        }

        if (count($enrolment2attributes) > 0) {
            $record = (object)$enrolment2attributes;
            $record->id = 201;
            $DB->update_record(student::TABLE, $record);
        }

        $data = $this->get_export_data();

        // Validation.
        $this->assertEquals(3, count($data));
        $this->assertEquals($checkvalue, $data[1][$checkfield]);
    }

    /**
     * Data provider for testing completion times
     *
     * @return array A variety of completion times
     */
    public function completion_time_provider() {
        return array(
                array(0),
                array(1000000000),
                array(time() - 25 * HOURSECS),
                array(time() - 23 * HOURSECS),
                array(time())
        );
    }

    /**
     * Validate that export does not respect completion time
     *
     * @param int $completiontime The completion time to assign the student
     * @dataProvider completion_time_provider
     */
    public function test_exportdoesnotrespectcompletiontime($completiontime) {
        global $CFG, $DB;

        // Data setup.
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."} SET completetime = ?";
        $params = array($completiontime);
        $DB->execute($sql, $params);

        // Validation.
        $data = $this->get_export_data();
        $this->assertEquals(2, count($data));
    }

    /**
     * Validate that the export resets state appropriately
     */
    public function test_exportresetsstate() {
        global $CFG;
        $file = get_plugin_directory('dhexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // Data setup.
        $this->load_csv_data();

        // Set the export to be nonincremental.
        set_config('nonincremental', 1, 'dhexport_version1elis');

        // Plugin for file IO.
        $fileplugin = new rlip_fileplugin_export();
        $fileplugin->open(RLIP_FILE_WRITE);

        // Our specific export.
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, true);
        $exportplugin->init(0, 0);

        // Validate setup.
        $this->assertTrue($exportplugin->recordset->valid());

        $exportplugin->close();

        // Validate result.
        $this->assertNull($exportplugin->recordset);

        $fileplugin->close();
    }
}