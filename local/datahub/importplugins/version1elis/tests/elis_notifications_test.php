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
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that IP action trigger appropriate notifications that would normally be sent.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_notifications_testcase extends rlip_elis_test {

    /**
     * Validating that enrolling a user in a class instance triggers the enrolment notification.
     */
    public function test_class_enrolment_sends_class_enrolment_notification() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classenrol_user', 1, 'local_elisprogram');
        $message = '%%userenrolname%% has been enrolled in the class instance %%classname%%.';
        set_config('notify_classenrol_message', $message, 'local_elisprogram');
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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
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

        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');

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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classcompleted_user', 1, 'local_elisprogram');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'local_elisprogram');
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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
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
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');

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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        // Force refreshing of configuration.
        elis::$config = new elis_config();

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_classcompleted_user', 1, 'local_elisprogram');
        $message = '%%userenrolname%% has completed the class instance %%classname%%.';
        set_config('notify_classcompleted_message', $message, 'local_elisprogram');
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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configuration.
        set_config('popup_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('email_provider_elis_program_notify_pm_permitted', 1, 'message');
        set_config('notify_trackenrol_user', 1, 'local_elisprogram');
        $message = '%%userenrolname%% has been enrolled in the track %%trackname%%.';
        set_config('notify_trackenrol_message', $message, 'local_elisprogram');
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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
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

    /**
     * Test main newenrolmentemail() function.
     */
    public function test_version1importnewenrolmentemail() {
        global $CFG, $DB; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1elis_fakeemail.php');

        $importplugin = new rlip_importplugin_version1elis_fakeemail();


        // Create Moodle course.
        $course = $this->getDataGenerator()->create_course();

        // Enrol some students into Moodle course.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Enrol teachers into Moodle course.
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $teacher2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher2->id, $course->id, $teacherrole->id);

        // Create ELIS class and ELIS user.
        $ecourse = new course(array(
            'name' => 'Test Course',
            'idnumber' => 'CRS100',
            'syllabus' => '',
        ));
        $ecourse->save();
        $eclass = new pmclass(array(
            'idnumber' => 'CLS100',
            'courseid' => $ecourse->id,
        ));
        $eclass->save();
        $euser = new user(array(
            'username' => 'testuser',
            'idnumber' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'testuser@example.com',
            'city' => 'Waterloo',
            'country' => 'CA'
        ));
        $euser->save();
        $muser = $euser->get_moodleuser();

        // Create student object for elis user/elis class.
        $student = new student(array(
            'userid' => $euser->id,
            'classid' => $eclass->id,
        ));

        // Test false return when student "no_moodle_enrol" is set.
        $student->no_moodle_enrol = true;
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);
        $student->no_moodle_enrol = false;

        // Test false return when ELIS class is not linked to Moodle course.
         $DB->delete_records(classmoodlecourse::TABLE, array('classid' => $eclass->id, 'moodlecourseid' => $course->id));
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);

        // Test false return when ELIS class is linked to a Moodle course, but Moodle course does not exist.
        $cmcid = $DB->insert_record(classmoodlecourse::TABLE, array('classid' => $eclass->id, 'moodlecourseid' => 999999999));
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);
        $DB->update_record(classmoodlecourse::TABLE, array('id' => $cmcid, 'moodlecourseid' => $course->id));

        // Test false return when ELIS user is not linked to Moodle user.
        $DB->delete_records(usermoodle::TABLE, array('cuserid' => $euser->id, 'muserid' => $muser->id));
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);

        // Test false return when ELIS user is linked to Moodle user, but Moodle user does not exist.
        $usermoodleid = $DB->insert_record(usermoodle::TABLE, array('cuserid' => $euser->id, 'muserid' => 99999999));
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);
        $DB->update_record(usermoodle::TABLE, array('id' => $usermoodleid, 'muserid' => $muser->id));

        // Test false return when not enabled.
        set_config('newenrolmentemailenabled', '0', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', 'Test Subject', 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', 'Test Body', 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);

        // Test false return when enabled but empty template.
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', 'Test Subject', 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', '', 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertFalse($result);

        // Test success when enabled, has template text, and user has email.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($muser, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Test success and from is set to teacher when selected.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($muser, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals($teacher, $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Test that subject is replaced by empty string when not present.
        $testsubject = null;
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($muser, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals('', $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Full testing of replacement is done below, but just test that it's being done at all from the main function.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body %%user_username%%';
        $expectedtestbody = 'Test Body '.$muser->username;
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1elis');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1elis');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1elis');
        $result = $importplugin->newenrolmentemail($student);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($muser, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($expectedtestbody, $result['body']);
    }

    /**
     * Test new user email notifications.
     */
    public function test_version1importnewenrolmentemailgenerate() {
        global $CFG; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1elis_fakeemail.php');
        $importplugin = new rlip_importplugin_version1elis_fakeemail();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templatetext = '<p>Hi %%user_fullname%%, you have been enroled in %%course_shortname%%
            Sitename: %%sitename%%
            User Username: %%user_username%%
            User Idnumber: %%user_idnumber%%
            User First Name: %%user_firstname%%
            User Last Name: %%user_lastname%%
            User Full Name: %%user_fullname%%
            User Email Address: %%user_email%%
            Course Fullname: %%course_fullname%%
            Course Shortname: %%course_shortname%%
            Course Idnumber: %%course_idnumber%%
            Course Summary: %%course_summary%%
            </p>';
        $actualtext = $importplugin->newenrolmentemail_generate($templatetext, $user, $course);

        $expectedtext = '<p>Hi '.datahub_fullname($user).', you have been enroled in '.$course->shortname.'
            Sitename: PHPUnit test site
            User Username: '.$user->username.'
            User Idnumber: '.$user->idnumber.'
            User First Name: '.$user->firstname.'
            User Last Name: '.$user->lastname.'
            User Full Name: '.datahub_fullname($user).'
            User Email Address: '.$user->email.'
            Course Fullname: '.$course->fullname.'
            Course Shortname: '.$course->shortname.'
            Course Idnumber: '.$course->idnumber.'
            Course Summary: '.$course->summary.'
            </p>';
        $this->assertEquals($expectedtext, $actualtext);
    }
}