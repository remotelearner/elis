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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for validating side effects of PM enrolments
 *
 * NOTE: Notifications are being testing in test_elis_notifications.php
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_enrolment_side_effects_testcase extends rlip_elis_test {

    /**
     * Validate that enrolling a user into a track via IP auto-enrolls them in the appropriate program.
     */
    public function test_track_enrolment_creates_program_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Make sure no emails are sent.
        set_config('noemailever', true);

        // Set up data.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array(
            'userid' => $user->id,
            'curriculumid' => $program->id
        )));
    }

    /**
     * Validate that enrolling a user into a track via IP auto-enrolls them in appropriate associated classes.
     */
    public function test_track_enrolment_creates_class_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Make sure no emails are sent.
        set_config('noemailever', true);

        // Set up data.

        // Test user.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();

        // Test program and track.
        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Test course and class.
        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclass1idnumber'));
        $class->save();

        // Associate course to the program.
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $program->id, 'courseid' => $course->id));
        $curriculumcourse->save();

        // Associate track to the test class.
        $trackassignment = new trackassignment(array('trackid' => $track->id, 'classid' => $class->id, 'autoenrol' => 1));
        $trackassignment->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id, 'classid' => $class->id)));
    }

    /**
     * Validate that enrolling a user into a user set via IP auto-enrolls them in
     * an associated track, and any associated programs or class instances
     */
    public function test_userset_enrolment_creates_track_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        // Make sure no emails are sent.
        set_config('noemailever', true);

        // Set up data.

        // Test user.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();

        // Test user set.
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        // Test program and track.
        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Associate the userset to the track.
        $clustertrack = new clustertrack(array('clusterid' => $userset->id, 'trackid' => $track->id, 'autoenrol' => 1));
        $clustertrack->save();

        // Test course and class.
        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclass1idnumber'));
        $class->save();

        // Associate course to the program.
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $program->id, 'courseid' => $course->id));
        $curriculumcourse->save();

        // Associate track to the test class.
        $trackassignment = new trackassignment(array('trackid' => $track->id, 'classid' => $class->id, 'autoenrol' => 1));
        $trackassignment->save();

        // Run the assignment create action.
        $record = new stdClass;
        $record->context = 'userset_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_enrolment_create($record, 'bogus', 'testusersetname');

        // Validation.

        // Userset assignment should trigger track assignment.
        $this->assertTrue($DB->record_exists(usertrack::TABLE, array('userid' => $user->id, 'trackid' => $track->id)));
        // Track assignment should trigger program assignment.
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array(
            'userid' => $user->id,
            'curriculumid' => $program->id
        )));
        // Track assignment should create a class enrolment.
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id, 'classid' => $class->id)));
    }
}