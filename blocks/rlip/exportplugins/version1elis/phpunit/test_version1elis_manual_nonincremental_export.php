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

//required classes / libraries
require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/phpunit/rlip_fileplugin_export.class.php');

/**
 * Test class for validating basic export data during a manual, nonincremental
 * export
 */
class version1elisManualNonincrementalExportTest extends elis_database_test {
    /**
     * Fetches our export data as a multi-dimensional array
     *
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        //set the export to be nonincremental
        set_config('nonincremental', 1, 'rlipexport_version1elis');

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_export();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data($multiple_users = false) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();

	    if ($multiple_users) {
	        //data for multiple users
	        $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourses.csv');
	        $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclasses.csv');
	        $dataset->addTable(student::TABLE, dirname(__FILE__).'/students.csv');
	        $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmusers.csv');
	    } else {
	        //data for a single user
	        $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourse.csv');
	        $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclass.csv');
	        $dataset->addTable(student::TABLE, dirname(__FILE__).'/student.csv');
	        $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmuser.csv');
	    }

        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array('config_plugins' => 'moodle',
                     'context' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'course_format_options' => 'moodle',
                     'grade_letters' => 'moodle',
                     classmoodlecourse::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     RLIPEXPORT_VERSION1ELIS_FIELD_TABLE => 'rlipexport_version1elis');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_ignored_tables() {
        return array('block_instances' => 'moodle',
                     'cache_flags' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'log' => 'moodle');
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private static function init_contexts_and_site_course() {
        global $DB;

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));
        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        build_context_path();
    }

    /**
     * Provider to be used for validating the export header
     *
     * @return array The expected header structure
     */
    function valid_header_provider() {
        $expectedheader = array(get_string('header_firstname', 'rlipexport_version1elis'),
                                get_string('header_lastname', 'rlipexport_version1elis'),
                                get_string('header_username', 'rlipexport_version1elis'),
                                get_string('header_useridnumber', 'rlipexport_version1elis'),
                                get_string('header_courseidnumber', 'rlipexport_version1elis'),
                                get_string('header_startdate', 'rlipexport_version1elis'),
                                get_string('header_enddate', 'rlipexport_version1elis'),
                                get_string('header_status', 'rlipexport_version1elis'),
                                get_string('header_grade', 'rlipexport_version1elis'),
                                get_string('header_letter', 'rlipexport_version1elis'));
        return array(array($expectedheader));
    }

    /**
     * Validate that the export contains the appropriate headers
     *
     * @param array $expectedheader The expected header data
     * @dataProvider valid_header_provider
     */
    public function testExportContainsCorrectHeader($expectedheader) {
        //setup
        $data = $this->get_export_data();

        //validation
        $this->assertEquals($expectedheader, $data[0]);
    }

    /**
     * Provider for basic (core) export data
     *
     * @return array The expected column data
     */
    function valid_data_provider() {
        $expecteddata = array('exportfirstname', 'exportlastname', 'exportusername', 'exportidnumber',
                              'testcourseidnumber', date('M/d/Y', 1000000000), date('M/d/Y', 1500000000),
                              'COMPLETED', '70.00000', 'C-');
        return array(array($expecteddata));
    }

    /**
     * Validate that the export contains the necessary data when the
     * approriate data is present in the database
     *
     * @param array $expecteddata The expected column data
     * @dataProvider valid_data_provider
     */
    public function testExportContainsValidData($expecteddata) {
        //setup
        $this->load_csv_data();
        $data = $this->get_export_data();

        //validation
        $this->assertEquals(2, count($data));
        $this->assertEquals($expecteddata, $data[1]);
    }

    /**
     * Validate that the export works with the minimum amount of required data
     */
    public function testExportWorksWithMinimalAssociations() {
        global $CFG, $DB;

        //setup
        $this->load_csv_data();
        $data = $this->get_export_data();

        //validation
        $this->assertEquals(2, count($data));
    }

    /**
     * Data provider for necessary associations / entities
     *
     * @return array The list of tables containing required data
     */
    function necessary_associations_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array(array(course::TABLE),
                     array(pmclass::TABLE),
                     array(student::TABLE),
                     array(user::TABLE));
    }

    /**
     * Validate that removing any one required association or entity will
     * invalidate data for consideration in the export
     *
     * @param string $tablename The name of a necessary database table
     * @dataProvider necessary_associations_provider
     */
    public function testExportRespectsNecessaryAssociations($tablename) {
        global $DB;

        //setup
        $this->load_csv_data();
        //delete records from required table
        $DB->delete_records($tablename);
        $data = $this->get_export_data();

        //validation (should only have the header row)
        $this->assertEquals(1, count($data));
    }

    /**
     * Data provider used to validate all the various completion status states
     *
     * @return array An array containing each possible completion status state
     */
    function completion_status_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        return array(array(student::STUSTATUS_NOTCOMPLETE),
                     array(student::STUSTATUS_FAILED),
                     array(student::STUSTATUS_PASSED));
    }

    /**
     * Validate that the export only includes passed enrolments
     *
     * @param int $status A completion status to use on the student enrolment
     * @dataProvider completion_status_provider
     */
    public function testExportRespectsCompletionStatus($status) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        //setup
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."}
                SET completestatusid = ?";
        $params = array($status);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        //validation
        if ($status == student::STUSTATUS_PASSED) {
            //the record should be included
            $this->assertEquals(2, count($data));
        } else {
            //should only have a header
            $this->assertEquals(1, count($data));
        }
    }

    /**
     * Data provider for grade letters
     *
     * @return array An array specifying grade-"letter" pairs
     */
    function grade_letter_provider() {
        return array(array(10, 1),
                     array(20, 2),
                     array(30, 3),
                     array(40, 4),
                     array(50, 5),
                     array(60, 6),
                     array(70, 7),
                     array(80, 8),
                     array(90, 9),
                     array(100, 10));
    }

    /**
     * Validate that the export respects grade letter boundaries and the
     * existence of an associated Moodle course
     *
     * @param int $grade The enrolment grade
     * @param int $letter The expected enrolment grade letter
     * @dataProvider grade_letter_provider
     */
    public function testExportRespectsGradeLetters($grade, $letter) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));

        //setup
        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        //create a Moodle course category
        $categorydata = new stdClass;
        $categorydata->name = 'testcategory';
        $categorydata->id = $DB->insert_record('course_categories', $categorydata);

        //create a Moodle course
        $coursedata = new stdClass;
        $coursedata->category = $categorydata->id;
        $coursedata->fullname = 'testcourse';
        $coursedata = create_course($coursedata);

        //associate the PM class instance to the Moodle course
        $classmoodle = new classmoodlecourse(array('classid' => 200,
                                                   'moodlecourseid' => $coursedata->id));
        $classmoodle->save();

        //create grade letter mappings
        $context = context_course::instance($coursedata->id);
        $mappings = array(10 => 1,
                          20 => 2,
                          30 => 3,
                          40 => 4,
                          50 => 5,
                          60 => 6,
                          70 => 7,
                          80 => 8,
                          90 => 9,
                          100 => 10);
        foreach ($mappings as $insertlowerboundary => $insertletter) {
            $record = new stdClass;
            $record->contextid = $context->id;
            $record->lowerboundary = $insertlowerboundary;
            $record->letter = $insertletter;
            $DB->insert_record('grade_letters', $record);
        }

        //set the enrolment grade
        $sql = "UPDATE {".student::TABLE."}
                SET grade = ?";
        $params = array($grade);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        //validation
        $this->assertEquals(2, count($data));
        $this->assertEquals((string)$letter, $data[1][9]);
    }

    /**
     * Data provider for testing sort orders
     *
     * @return array Data needed for testing sort orders
     */
    function sort_order_provider() {
        $data = array();
        //sort first based on idnumber
        $data[] = array(array('idnumber' => 'a'),
                        array(),
                        array(),
                        array('idnumber' => 'b'),
                        array(),
                        array(),
                        3,
                        'a');
        $data[] = array(array('idnumber' => 'b'),
                        array(),
                        array(),
                        array('idnumber' => 'a'),
                        array(),
                        array(),
                        3,
                        'a');
        //sort second based on course idnumber
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array(),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'b'),
                        array(),
                        4,
                        'a');
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'b'),
                        array(),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array(),
                        4,
                        'a');
        //sort third based on completion time / date
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1000000000),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1500000000),
                        6,
                        date("M/d/Y", 1000000000));
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1500000000),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1000000000),
                        6,
                        date("M/d/Y", 1000000000));
        //sort fourth based on completion grade
        $data[] = array(array('idnumber' => 'a'),
                       array('idnumber' => 'a'),
                       array('completetime' => 1,
                             'grade' => 2),
                       array('idnumber' => 'a'),
                       array('idnumber' => 'a'),
                       array('completetime' => 1,
                             'grade' => 1),
                       8,
                       2);
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1,
                              'grade' => 1),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1,
                              'grade' => 2),
                        8,
                        2);
        //sort last based on record id
        $data[] = array(array('username' => 'firstuser',
                              'idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1,
                              'grade' => 1),
                        array('username' => 'seconduser',
                              'idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => 1,
                              'grade' => 1),
                        2,
                        'firstuser');

        //return all the appropriate data
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
    public function testExportRespectsSortOrder($user1attributes, $cd1attributes, $enrolment1attributes,
                                                $user2attributes, $cd2attributes, $enrolment2attributes,
                                                $checkfield, $checkvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        //setup
        $this->load_csv_data(true);

        //persist the specified attributes
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

        //validation
        $this->assertEquals(3, count($data));
        $this->assertEquals($checkvalue, $data[1][$checkfield]);
    }

    /**
     * Data provider for testing completion times
     *
     * @return array A variety of completion times
     */
    function completion_time_provider() {
        return array(array(0),
                     array(1000000000),
                     array(time() - 25 * HOURSECS),
                     array(time() - 23 * HOURSECS),
                     array(time()));
    }

    /**
     * Validate that export does not respect completion time
     *
     * @param int $completiontime The completion time to assign the student
     * @dataProvider completion_time_provider
     */
    public function testExportDoesNotRespectCompletionTime($completiontime) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        //data setup
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."}
                SET completetime = ?";
        $params = array($completiontime);
        $DB->execute($sql, $params);

        //validation
        $data = $this->get_export_data();
        $this->assertEquals(2, count($data));
    }

    /**
     * Validate that the export resets state appropriately
     */
    public function testExportResetsState() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        //data setup
        $this->load_csv_data();

        //set the export to be nonincremental
        set_config('nonincremental', 1, 'rlipexport_version1elis');

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_export();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, true);
        $exportplugin->init(0, 0);

        //validate setup
        $this->assertTrue($exportplugin->recordset->valid());

        $exportplugin->close();

        //validate result
        $this->assertNull($exportplugin->recordset);

        $fileplugin->close();
    }
}