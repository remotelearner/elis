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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

// Data objects.
require_once(elispm::lib('data/clusterassignment.class.php'));
require_once(elispm::lib('data/usertrack.class.php'));
require_once(elispm::lib('data/curriculumstudent.class.php'));
require_once(elispm::lib('data/student.class.php'));

/**
 * Test the clusterassignment data object.
 * @group local_elisprogram
 */
class clusterassignment_testcase extends elis_database_test {

    /**
     * Load initial data from csv.
     */
    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            clusterassignment::TABLE => elispm::file('tests/fixtures/cluster_assignment.csv'),
            userset::TABLE => elispm::file('tests/fixtures/userset.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Test validation of duplicates
     * @expectedException data_object_validation_exception
     */
    public function test_clusterassignment_validationpreventsduplicates() {
        $this->load_csv_data();
        $clusterassignment = new clusterassignment(array('clusterid' => 1, 'userid' => 1, 'plugin' => 'manual'));
        $clusterassignment->save();
    }

    /**
     * Test validation of entries with the same userid and clusterid but different plugin
     */
    public function test_clusterassignment_validationallowsmultipleplugins() {
        $this->load_csv_data();
        $clusterassignment = new clusterassignment(array('clusterid' => 1, 'userid' => 1, 'plugin' => 'moodleprofile'));
        $clusterassignment->save();
        $this->assertEquals(1, 1);
    }
}