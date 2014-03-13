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

// Data classes.
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));

/**
 * Test auto class enrolment.
 * @group local_elisprogram
 */
class autoclassenrolment_testcase extends elis_database_test {

    /**
     * Load initial data from a CSV files.
     */
    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            'course' => elispm::file('tests/fixtures/mdlcourse.csv'),
            'user' => elispm::file('tests/fixtures/mdluser.csv'),
            classmoodlecourse::TABLE => elispm::file('tests/fixtures/class_moodle_course.csv'),
            pmclass::TABLE => elispm::file('tests/fixtures/pmclass.csv'),
            usermoodle::TABLE => elispm::file('tests/fixtures/usermoodle.csv'),
            user::TABLE => elispm::file('tests/fixtures/pmuser.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Validate that the sync from course role assignment to class instance enrolment works
     */
    public function test_enrolled_course_user_syncstoclass() {
        global $CFG, $DB;
        require_once(elispm::lib('lib.php'));

        // Set up import data.
        $this->load_csv_data();

        // Make sure the context is set up.
        $crsctx = context_course::instance(100);

        // Set up our test role.
        $roleid = create_role('gradedrole', 'gradedrole', 'gradedrole');
        set_config('gradebookroles', $roleid);

        // Create role assignments.
        role_assign($roleid, 100, $crsctx->id);

        // Attempt the sync.
        $sync = new \local_elisprogram\moodle\synchronize;
        $sync->synchronize_moodle_class_grades();

        // Make sure the student record was created.
        $student = student::find();
        $this->assertTrue($student->valid());

        // Make sure the student has the right class id.
        $student = $student->current();
        $this->assertEquals(100, $student->classid);
    }
}
