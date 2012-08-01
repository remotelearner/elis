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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Class for validating side effects of PM enrolments
 *
 * NOTE: Notifications are being testing in test_elis_notifications.php
 */
class elis_enrolment_side_effects_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        return array('config' => 'moodle',
                     clusterassignment::TABLE => 'elis_program',
                     clustertrack::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     curriculum::TABLE => 'elis_program',
                     curriculumcourse::TABLE => 'elis_program',
                     curriculumstudent::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     trackassignment::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program',
                     userset::TABLE => 'elis_program',
                     usertrack::TABLE => 'elis_program',
                     'cache_flags' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user_enrolments' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array('context' => 'moodle',
                     'message' => 'moodle',
                     'user' => 'moodle',
                     coursetemplate::TABLE => 'elis_program');
    }

    /**
     * Validate that enrolling a user into a track via IP auto-enrolls them in the
     * appropriate program
     */
    public function test_track_enrolment_creates_program_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //make sure no emails are sent
        set_config('noemailever', true);

        //set up data
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        //validation
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array('userid' => $user->id,
                                                                             'curriculumid' => $program->id)));
    }

    /**
     * Validate that enrolling a user into a track via IP auto-enrolls them in
     * appropriate associated classes
     */
    public function test_track_enrolment_creates_class_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //make sure no emails are sent
        set_config('noemailever', true);

        //set up data

        //test user
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        //test program and track
        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //test course and class
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclass1idnumber'));
        $class->save();

        //associate course to the program
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $program->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        //associate track to the test class
        $trackassignment = new trackassignment(array('trackid' => $track->id,
                                                     'classid' => $class->id,
                                                     'autoenrol' => 1));
        $trackassignment->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        //validation
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id,
                                                                   'classid' => $class->id)));
    }

    /**
     * Validate that enrolling a user into a user set via IP auto-enrolls them in
     * an associated track, and any associated programs or class instances
     */
    public function test_userset_enrolment_creates_track_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
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

        //make sure no emails are sent
        set_config('noemailever', true);

        //set up data

        //test user
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        //test user set
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //test program and track
        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //associate the userset to the track
        $clustertrack = new clustertrack(array('clusterid' => $userset->id,
                                               'trackid' => $track->id,
                                               'autoenrol' => 1));
        $clustertrack->save();

        //test course and class
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclass1idnumber'));
        $class->save();

        //associate course to the program
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $program->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        //associate track to the test class
        $trackassignment = new trackassignment(array('trackid' => $track->id,
                                                     'classid' => $class->id,
                                                     'autoenrol' => 1));
        $trackassignment->save();

        //run the assignment create action
        $record = new stdClass;
        $record->context = 'userset_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_enrolment_create($record, 'bogus', 'testusersetname');

        //validation

        //userset assignment should trigger track assignment
        $this->assertTrue($DB->record_exists(usertrack::TABLE, array('userid' => $user->id,
                                                                     'trackid' => $track->id)));
        //track assignment should trigger program assignment
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array('userid' => $user->id,
                                                                             'curriculumid' => $program->id)));
        //track assignment should create a class enrolment
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id,
                                                                   'classid' => $class->id)));
    }

}