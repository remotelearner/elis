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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that IP action trigger appropriate notifications that would normally be sent.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_notifications_testcase extends rlip_elis_test {

    /**
     * Validating that enrolling a user in a class instance triggers the enrolment notification.
     */
    public function test_class_enrolment_sends_class_enrolment_notification() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classenrol_user', 1, 'elis_program');
        $message = '%%userenrolname%% has been enrolled in the class instance %%classname%%.';
        set_config('notify_classenrol_message', $message, 'elis_program');
        set_config('noemailever', 1);
        // Force refreshing of configuration.
        elis::$config = new elis_config();

        // Setup.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@user.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expectedmessage = "{$user->firstname} {$user->lastname} has been enrolled in the class instance {$class->idnumber}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";
        $this->assertTrue($DB->record_exists_select('message', $select, array(
            'userid' => $mdluserid,
            'message' => "{$expectedmessage}%"
        )));
    }

    /**
     * Data provider for testing the class completed notification on enrolment create
     *
     * @return array Parameter data, as expected by the testing method
     */
    public function enrolment_completion_on_create_provider() {
        global $CFG;

        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

            return array(
                    array(student::STUSTATUS_NOTCOMPLETE, false),
                    array(student::STUSTATUS_FAILED, false),
                    array(student::STUSTATUS_PASSED, true)
            );
        } else {
            return array();
        }
    }

    /**
     * Validating that enrolling a user in a class instance with a passed status triggers the class
     * completed notification
     *
     * @param int $completestatus The completion status to enrol the user with
     * @param boolean $expectmessage Whether we expect the notification message to be sent
     * @dataProvider enrolment_completion_on_create_provider
     */
    public function test_class_completion_sends_class_completed_notification_on_enrolment_create($completestatus, $expectmessage) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classcompleted_user', 1, 'elis_program');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'elis_program');
        set_config('noemailever', 1);
        // Force refreshing of configuration.
        elis::$config = new elis_config();

        // Setup.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@user.com',
            'country' => 'CA'
        ));
        $user->reset_custom_field_list();
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->reset_custom_field_list();
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->reset_custom_field_list();
        $class->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completestatusid = $completestatus;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expectedmessage = "{$user->firstname} {$user->lastname} has completed the class instance {$course->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";

        if ($expectmessage) {
            $this->assertTrue($DB->record_exists_select('message', $select, array(
                'userid' => $mdluserid,
                'message' => "{$expectedmessage}%"
            )));
        } else {
            $this->assertFalse($DB->record_exists_select('message', $select, array(
                'userid' => $mdluserid,
                'message' => "{$expectedmessage}%"
            )));
        }
    }

    /**
     * Data provider for testing the class completed notification on enrolment update
     *
     * @return array Parameter data, as expected by the testing method
     */
    public function enrolment_completion_on_update_provider() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

            return array(
                    array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_NOTCOMPLETE, false),
                    array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_FAILED, false),
                    array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_PASSED, true),
                    array(student::STUSTATUS_PASSED, student::STUSTATUS_PASSED, false
            ));
        } else {
            return array();
        }
    }

    /**
     * Validating that updating a user's enrolment in a class instance triggers the enrolment
     * notification if the status is set to passed
     *
     * @param int $oldcompletestatus The completion status to enrol the user with initially
     * @param int $newcompletestatus The completion status to set during enrolment update
     * @param boolean $expectmessage Whether we expect the notification message to be sent
     * @dataProvider enrolment_completion_on_update_provider
     */
    public function test_class_completion_sends_class_completed_notification_on_enrolment_update($oldcompletestatus,
                                                                                                 $newcompletestatus,
                                                                                                 $expectmessage) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        // Force refreshing of configuration.
        elis::$config = new elis_config();

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classcompleted_user', 1, 'elis_program');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'elis_program');
        set_config('noemailever', 1);

        // Setup.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@user.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $class = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber'
        ));
        $class->save();

        // We need an "existing" enrolment record set up.
        $student = new student(array(
            'userid' => $user->id,
            'classid' => $class->id,
            'completestatusid' => $oldcompletestatus
        ));
        $student->save();

        // Run the enrolment update action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completestatusid = $newcompletestatus;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expectedmessage = "{$user->firstname} {$user->lastname} has completed the class instance {$course->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";

        if ($expectmessage) {
            $this->assertTrue($DB->record_exists_select('message', $select, array(
                'userid' => $mdluserid,
                'message' => "{$expectedmessage}%"
            )));
        } else {
            $this->assertFalse($DB->record_exists_select('message', $select, array(
                'userid' => $mdluserid,
                'message' => "{$expectedmessage}%"
            )));
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

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_trackenrol_user', 1, 'elis_program');
        $message = '%%userenrolname%% has been enrolled in the track %%trackname%%.';
        set_config('notify_trackenrol_message', $message, 'elis_program');
        set_config('noemailever', 1);
        // Force refreshing of configuration.
        elis::$config = new elis_config();

        // Setup.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@user.com',
            'country' => 'CA'
        ));
        $user->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber', 'name' => 'testtrackname'));
        $track->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        // Validation.
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expectedmessage = "{$user->firstname} {$user->lastname} has been enrolled in the track {$track->name}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";
        $this->assertTrue($DB->record_exists_select('message', $select, array(
            'userid' => $mdluserid,
            'message' => "{$expectedmessage}%"
        )));
    }
}