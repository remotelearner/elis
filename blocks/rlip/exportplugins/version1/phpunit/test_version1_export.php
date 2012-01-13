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

require_once(dirname(__FILE__) . '/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot . '/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));

class rlip_fileplugin_bogus extends rlip_fileplugin_base {
    private $data;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->data = array();
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        //nothing to do
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        $this->data[] = $entry;
    }

    /**
     * Close the file
     */
    public function close() {
        //nothing to do
    }

    public function get_data() {
        return $this->data;
    }
}

/**
 * Class for version 1 export correctness
 */
class version1ExportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    protected static function get_overlay_tables() {
        return array('grade_items' => 'moodle',
                     'grade_grades' => 'moodle',
                     'user' => 'moodle',
                     'course' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'cohort_members' => 'moodle',
                     'groups_members' => 'moodle',
                     'user_preferences' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'block_instances' => 'moodle',
                     'block_positions' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'role_assignments' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'context' => 'moodle',
                     'events_queue' => 'moodle',
                     'events_queue_handlers' => 'moodle'
                     );
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    $dataset->addTable('grade_items', dirname(__FILE__).'/phpunit_gradeitems.csv');
	    $dataset->addTable('grade_grades', dirname(__FILE__).'/phpunit_gradegrades.csv');
	    $dataset->addTable('user', dirname(__FILE__).'/phpunit_user.csv');
	    $dataset->addTable('course', dirname(__FILE__).'/phpunit_course.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Fetches our export data as a multi-dimensional array
     *
     * @return array The export data
     */
    protected function get_export_data() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1/version1.class.php');

        //plugin for file IO
    	$fileplugin = new rlip_fileplugin_bogus();
    	$fileplugin->open(RLIP_FILE_WRITE);

    	//our specific export
        $exportplugin = new rlip_exportplugin_version1($fileplugin);
        $exportplugin->init();
        $exportplugin->export_records($fileplugin);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    } 

    /**
     * Validate the export header row for an empty data set
     */
    public function testVersion1ExportCreatesCorrectHeader() {
        $data = $this->get_export_data();

        $expected_header = array(get_string('header_firstname', 'rlipexport_version1'),
                                 get_string('header_lastname', 'rlipexport_version1'),
                                 get_string('header_username', 'rlipexport_version1'),
                                 get_string('header_useridnumber', 'rlipexport_version1'),
                                 get_string('header_courseidnumber', 'rlipexport_version1'),
                                 get_string('header_startdate', 'rlipexport_version1'),
                                 get_string('header_enddate', 'rlipexport_version1'),
                                 get_string('header_grade', 'rlipexport_version1'),
                                 get_string('header_letter', 'rlipexport_version1'));

        $this->assertEquals(count($data), 1);

        //make sure the data matches the expected header
        $this->assertEquals($data, array($expected_header));
    }

    /**
     * Validate the export data for a simple data set
     */
    public function testVersion1ExportIncludesCorrectData() {
        $this->load_csv_data();

        $data = $this->get_export_data();

        $this->assertEquals(count($data), 2);

        $body = $data[1];
        $expected_body = array('test',
                               'user',
                               'testuser',
                               'testuseridnumber',
                               'testcourse',
                               date('M/d/Y', 1000000000),
                               date('M/d/Y'),
                               70.00000,
                               'C-');

        //make sure the data matches the expected data
        $this->assertEquals($body, $expected_body);
    }

    /**
     * Validate that the export data does not include deleted users
     */
    public function testVersion1ExportExcludesDeletedUsers() {
        global $DB;
        $this->load_csv_data();

        //delete the user the "correct" way
        $user = $DB->get_record('user', array('id' => 2));
        delete_user($user);

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing users
     */
    public function testVersion1ExportExcludesMissingUsers() {
        global $DB;
        $this->load_csv_data();

        //delete the user record
        $DB->delete_records('user', array('id' => 2));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing courses
     */
    public function testVersion1ExportExcludesMissingCourses() {
        global $DB;
        $this->load_csv_data();

        //delete the course record
        $DB->delete_records('course', array('id' => 2));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grade items
     */
    public function testVersion1ExportExcludesMissingGradeItems() {
        global $DB;
        $this->load_csv_data();

        //delete the grade item record
        $DB->delete_records('grade_items', array('id' => 1));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data only includes course-level grade items
     */
    public function testVersion1ExportExcludesIncorrectGradeItemTypes() {
        global $DB;
        $this->load_csv_data();

        //change the grade item type
        $item = new stdClass;
        $item->id = 1;
        $item->itemtype = 'category';
        $DB->update_record('grade_items', $item);

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }

    /**
     * Validate that the export data does not include missing grades
     */
    public function testVersion1ExportExcludesMissingGrades() {
        global $DB;
        $this->load_csv_data();

        //delete the grade record
        $DB->delete_records('grade_grades', array('id' => 1));

        $data = $this->get_export_data();

        //make sure only the header was included
        $this->assertEquals(count($data), 1);
    }
}