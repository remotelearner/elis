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
 * Class for validating that IP action trigger appropriate notifications that
 * would normally be sent
 */
class elis_notifications_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        return array('config' => 'moodle',
                     'config_plugins' => 'moodle',
                     'context' => 'moodle',
                     'message' => 'moodle',
                     'user' => 'moodle',
                     //to prevent accidental association
                     classmoodlecourse::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     curriculum::TABLE => 'elis_program',
                     curriculumstudent::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     trackassignment::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program',
                     usertrack::TABLE => 'elis_program',
                     waitlist::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array(coursetemplate::TABLE => 'elis_program');
    }

    /**
     * Validating that enrolling a user in a class instance triggers the enrolment
     * notification
     */
    public function test_class_enrolment_sends_class_enrolment_notification() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //configuration
        set_config('notify_classenrol_user', 1, 'elis_program');
        $message = '%%userenrolname%% has been enrolled in the class instance %%classname%%.';
        set_config('notify_classenrol_message', $message, 'elis_program');
        set_config('noemailever', 1);
        //force refreshing of configuration
        elis::$config = new elis_config();

        //setup
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@user.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        //validation
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expected_message = "{$user->firstname} {$user->lastname} has been enrolled in the class instance {$class->idnumber}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";
        $this->assertTrue($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                              'message' => "{$expected_message}%")));
    }

    /**
     * Data provider for testing the class completed notification on enrolment create
     *
     * @return array Parameter data, as expected by the testing method
     */
    function enrolment_completion_on_create_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

        return array(array(student::STUSTATUS_NOTCOMPLETE, false),
                     array(student::STUSTATUS_FAILED, false),
                     array(student::STUSTATUS_PASSED, true));
    }

    /**
     * Validating that enrolling a user in a class instance with a passed status triggers the class
     * completed notification
     *
     * @param int $completestatus The completion status to enrol the user with
     * @param boolean $expect_message Whether we expect the notification message to be sent
     * @dataProvider enrolment_completion_on_create_provider
     */
    public function test_class_completion_sends_class_completed_notification_on_enrolment_create($completestatus, $expect_message) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //configuration
        set_config('notify_classcompleted_user', 1, 'elis_program');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'elis_program');
        set_config('noemailever', 1);
        //force refreshing of configuration
        elis::$config = new elis_config();

        //setup
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@user.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completestatusid = $completestatus;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        //validation
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expected_message = "{$user->firstname} {$user->lastname} has completed the class instance {$course->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";

        if ($expect_message) {
            $this->assertTrue($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                                  'message' => "{$expected_message}%")));
        } else {
            $this->assertFalse($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                                   'message' => "{$expected_message}%")));
        }
    }

    /**
     * Data provider for testing the class completed notification on enrolment update
     *
     * @return array Parameter data, as expected by the testing method
     */
    function enrolment_completion_on_update_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

        return array(array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_NOTCOMPLETE, false),
                     array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_FAILED, false),
                     array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_PASSED, true),
                     array(student::STUSTATUS_PASSED, student::STUSTATUS_PASSED, false));
    }

    /**
     * Validating that updating a user's enrolment in a class instance triggers the enrolment
     * notification if the status is set to passed
     *
     * @param int $oldcompletestatus The completion status to enrol the user with initially
     * @param int $newcompletestatus The completion status to set during enrolment update
     * @param boolean $expect_message Whether we expect the notification message to be sent
     * @dataProvider enrolment_completion_on_update_provider
     */
    public function test_class_completion_sends_class_completed_notification_on_enrolment_update($oldcompletestatus, $newcompletestatus,
                                                                                                 $expect_message) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        //force refreshing of configuration
        elis::$config = new elis_config();

        //configuration
        set_config('notify_classcompleted_user', 1, 'elis_program');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'elis_program');
        set_config('noemailever', 1);

        //setup
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@user.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //we need an "existing" enrolment record set up
        $student = new student(array('userid' => $user->id,
                                     'classid' => $class->id,
                                     'completestatusid' => $oldcompletestatus));
        $student->save();

        //run the enrolment update action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completestatusid = $newcompletestatus;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        //validation
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expected_message = "{$user->firstname} {$user->lastname} has completed the class instance {$course->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";

        if ($expect_message) {
            $this->assertTrue($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                                  'message' => "{$expected_message}%")));
        } else {
            $this->assertFalse($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                                  'message' => "{$expected_message}%")));
        }
    }

    /**
     * Validating that enrolling a user in a track instance triggers the enrolment
     * notification
     */
    public function test_track_enrolment_sends_class_enrolment_notification() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //configuration
        set_config('notify_trackenrol_user', 1, 'elis_program');
        $message = '%%userenrolname%% has been enrolled in the track %%trackname%%.';
        set_config('notify_trackenrol_message', $message, 'elis_program');
        set_config('noemailever', 1);
        //force refreshing of configuration
        elis::$config = new elis_config();

        //setup
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@user.com',
                               'country' => 'CA'));
        $user->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber',
                                 'name' => 'testtrackname'));
        $track->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        //validation
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expected_message = "{$user->firstname} {$user->lastname} has been enrolled in the track {$track->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";
        $this->assertTrue($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                              'message' => "{$expected_message}%")));
    }
}