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
 * @subpackage program
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');
require_once(elispm::lib('data/curriculumcourse.class.php'));

/** Since class isdefined within curriculumcourse.class.php
 *  testDataObjectsFieldsAndAssociations.php will not auto test this class
 */
class courseprerequisiteTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * The overlay database object set up by a test.
     */
    private $overlaydb;
    /**
     * The original global $DB object.
     */
    private $origdb;

    /**
     * Clean up the temporary database tables, and reset the $DB global, if
     * needed.
     */
    protected function tearDown() {
        if (isset($this->overlaydb)) {
            $this->overlaydb->cleanup();
            unset($this->overlaydb);
        }
        if (isset($this->origdb)) {
            global $DB;
            $DB = $this->origdb;
            unset($this->origdb);
        }
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        $syscontext = $this->origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        $this->overlaydb->import_record('context', $syscontext);

        $site = $this->origdb->get_record('course', array('id' => SITEID));
        $this->overlaydb->import_record('course', $site);
        $sitecontext = $this->origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        $this->overlaydb->import_record('context', $sitecontext);
    }

    protected function load_csv_data() {
        // load initial data from a CSV file
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(courseprerequisite::TABLE, elis::component_file('program', 'phpunit/courseprerequisite.csv')); // TBD: more generic 'phpunit/' . get_class($this) ???
        load_phpunit_data_set($dataset, true, $this->overlaydb);
    }

    protected function setUp()
    {
        // called before each test function
        global $DB;
        $this->origdb = $DB;
        $DB = $this->overlaydb = new overlay_database($DB,
                                      array('context'      => 'moodle',
                                            'course'       => 'moodle',
                                 courseprerequisite::TABLE => 'elis_program'));
        $this->setUpContextsTable();
    }

    /**
     * Test that data class has correct DB fields
     */
    public function testDBfields() {
        $testobj = new courseprerequisite(false, null, array(), false, array(), $this->origdb);
        $this->assertTrue($testobj->_test_dbfields(), 'Error(s) with class $_dbfield_ properties.');
    }

    /**
     * Test that data class has correct associations
     */
    public function testAssociations() {
        $testobj = new courseprerequisite(false, null, array(), false, array(), $this->origdb);
        $this->assertTrue($testobj->_test_associations(), 'Error(s) with class associations.');
    }

    /**
     * Test that a record can be created in the database.
     */
    public function testCreateRecord() {
        $this->markTestIncomplete('In development.');
        // create a record
        $src = new courseprerequisite(false, null, array(), false, array(), $this->overlaydb);
        // TBD: setup data
        $src->save();

        // read it back
        $retr = new courseprerequisite($src->id, null, array(), false, array(), $this->overlaydb);
        foreach ($src as $key => $value) {
            if (strpos($key, elis_data_object::FIELD_PREFIX) !== false) {
                $key = substr($key, strlen(elis_data_object::FIELD_PREFIX));
                $this->assertEquals($src->{$key}, $retr->{$key});
            }
        }
    }

    /**
     * Test that a record can be modified.
     */
    public function testUpdate() {
        $this->markTestIncomplete('In development.');
        $this->load_csv_data();

        // read a record
        $src = new courseprerequisite(3, null, array(), false, array(), $this->overlaydb);
        // TBD: setup data
        $src->save();

        // read it back
        $retr = new courseprerequisite(3, null, array(), false, array(), $this->overlaydb);
        foreach ($src as $key => $value) {
            if (strpos($key, elis_data_object::FIELD_PREFIX) !== false) {
                $key = substr($key, strlen(elis_data_object::FIELD_PREFIX));
                $this->assertEquals($src->{$key}, $retr->{$key});
            }
        }
    }

}
