<?php
/**
 * Test creation of PM elements. *
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
 * @subpackage program
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');
require_once(elispm::lib('data/curriculum.class.php'));
require_once(elispm::lib('data/track.class.php'));
require_once(elispm::lib('data/course.class.php'));
require_once(elispm::lib('data/coursetemplate.class.php'));
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/userset.class.php'));

class test_element_creation extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    protected static function get_overlay_tables() {
        return array(
            'context'             => 'moodle',
            'course'              => 'moodle',
            curriculum::TABLE     => 'elis_program',
            track::TABLE          => 'elis_program',
            course::TABLE         => 'elis_program',
            coursetemplate::TABLE => 'elis_program',
            pmclass::TABLE        => 'elis_program',
            user::TABLE           => 'elis_program',
            userset::TABLE        => 'elis_program'
        );
    }

    protected static function get_ignored_tables() {
        return array(
            // these aren't actually used, but userset::delete will run a query
            // on them
//             clustercurriculum::TABLE => 'elis_program',
//             clustertrack::TABLE => 'elis_program',
//             clusterassignment::TABLE => 'elis_program',
//             userset_profile::TABLE => 'elis_program',
//             field_data_int::TABLE => 'elis_core',
//             field_data_num::TABLE => 'elis_core',
//             field_data_char::TABLE => 'elis_core',
//             field_data_text::TABLE => 'elis_core',
//             'block_instances' => 'moodle',
//             'block_positions' => 'moodle',
//             'filter_active' => 'moodle',
//             'filter_config' => 'moodle',
//             'comments' => 'moodle',
//             'rating' => 'moodle',
//             'cache_flags' => 'moodle',
//             'role_assignments' => 'moodle',
//             'role_capabilities' => 'moodle',
//             'role_names' => 'moodle',
        );
    }

    protected function setUp() {
        parent::setUp();
        $this->setUpContextsTable();
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        $syscontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        self::$overlaydb->import_record('context', $syscontext);

        $site = self::$origdb->get_record('course', array('id' => SITEID));
        self::$overlaydb->import_record('course', $site);
        $sitecontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        self::$overlaydb->import_record('context', $sitecontext);
    }

    /**
     * Initialize a new program object
     *
     * @param none
     * @return curriculum The new program object
     */
    private function initProgram() {
        $data = array(
            'idnumber' => 'TESTID001',
            'name'     => 'Test Program 1'
        );

        $newprogram = new curriculum($data);

        return $newprogram;
    }

    /**
     * Initialize a new track object
     *
     * @param integer $curid A curriculum record ID
     * @return track The new track object
     */
    private function initTrack($curid) {
        $data = array(
            'curid'    => $curid,
            'idnumber' => 'TESTID001',
            'name'     => 'Test Track 1'
        );

        $newtrack = new track($data);

        return $newtrack;
    }

    /**
     * Initialize a new course description object
     *
     * @return course The new course object
     */
    private function initCourse() {
        $data = array(
            'idnumber' => 'TESTID001',
            'name'     => 'Test Course 1',
            'syllabus' => ''  // For some reason this field needs to be defined, or INSERT fails?!
        );

        $newcourse = new course($data);

        return $newcourse;
    }

    /**
     * Initialize a new class object
     *
     * @param integer $courseid A course record ID
     * @return class The new class object
     */
    private function initClass($courseid) {
        $data = array(
            'idnumber' => 'TESTID001',
            'courseid' => $courseid
        );

        $newclass = new pmclass($data);

        return $newclass;
    }

    /**
     * Test that a new Program instance can be created and saved to the database.
     */
    public function testCreateProgram() {
        $newobj = $this->initProgram();
        $newobj->save();

        // Verify that the object was saved to the database and the record ID was assigned to the object
        $this->assertGreaterThan(0, $newobj->id);

        // Fetch the record from the database
        $testobj = new curriculum($newobj->id);

        // Verify that the record returned from the database matches what was inserted
        $this->assertEquals($newobj->id, $testobj->id);
        $this->assertEquals($newobj->idnumber, $testobj->idnumber);
        $this->assertEquals($newobj->name, $testobj->name);
    }

    /**
     * Test that a new Track instance can be created and saved to the database.
     */
    public function testCreateTrack() {
        $newprogram = $this->initProgram();
        $newprogram->save();

        $newobj = $this->initTrack($newprogram->id);
        $newobj->save();

        $this->assertGreaterThan(0, $newobj->id);

        // Fetch the record from the database
        $testobj = new track($newobj->id);

        // Verify that the record returned from the database matches what was inserted
        $this->assertEquals($newobj->id, $testobj->id);
        $this->assertEquals($newobj->idnumber, $testobj->idnumber);
        $this->assertEquals($newobj->name, $testobj->name);
    }

    /**
     * Test that a new Course instance can be created and saved to the database.
     */
    public function testCreateCourse() {
        $newobj = $this->initCourse();
        $newobj->save();

        $this->assertGreaterThan(0, $newobj->id);

        // Fetch the record from the database
        $testobj = new course($newobj->id);

        // Verify that the record returned from the database matches what was inserted
        $this->assertEquals($newobj->id, $testobj->id);
        $this->assertEquals($newobj->idnumber, $testobj->idnumber);
        $this->assertEquals($newobj->name, $testobj->name);
    }

    /**
     * Test that a new Class instance can be created and saved to the database.
     */
    public function testCreateClass() {
        $newcourse = $this->initCourse();
        $newcourse->save();

        $newobj = $this->initClass($newcourse->id);
        $newobj->save();

        $this->assertGreaterThan(0, $newobj->id);

        // Fetch the record from the database
        $testobj = new pmclass($newobj->id);

        // Verify that the record returned from the database matches what was inserted
        $this->assertEquals($newobj->id, $testobj->id);
        $this->assertEquals($newobj->idnumber, $testobj->idnumber);
    }
}
