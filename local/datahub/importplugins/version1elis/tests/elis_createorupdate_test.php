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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Test the "Datatel" create-or-update functionality for the Version 1 ELIS plugin.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_createorupdate_testcase extends rlip_elis_test {

    /**
     * Validate that users can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $params = array(
            'username'  => 'testuserusername',
            'email'     => 'test@useremail.com',
            'idnumber'  => 'testuseridnumber',
            'firstname' => 'testuserfirstname'
        );

        $this->assertTrue($DB->record_exists(user::TABLE, $params));
    }

    /**
     * Validate that create actions are converted to updates for users when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test user.
        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'updatedtestuserfirstname';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(user::TABLE, array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'updatedtestuserfirstname'
        )));
    }

    /**
     * Validate that programs can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_program() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Run the program create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->name = 'testprogramname';
        $record->idnumber = 'testprogramidnumber';
        $record->priority = '0';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(curriculum::TABLE, array(
            'name' => 'testprogramname',
            'idnumber' => 'testprogramidnumber',
            'priority' => '0'
        )));
    }

    /**
     * Validate that create actions are converted to updates for programs when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_program() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test program.
        $program = new curriculum(array(
            'name' => 'testprogramname',
            'idnumber' => 'testprogramidnumber',
            'description' => 'testprogramdescription'
        ));
        $program->save();

        // Run the program create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->priority = '5';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(curriculum::TABLE, array(
            'name' => 'testprogramname',
            'idnumber' => 'testprogramidnumber',
            'priority' => '5'
        )));
    }

    /**
     * Validate that tracks can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_track() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        $program = new curriculum(array('name' => 'testprogramname', 'idnumber' => 'testprogramidnumber'));
        $program->save();

        // Run the track create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->assignment = 'testprogramidnumber';
        $record->name = 'testtrackname';
        $record->idnumber = 'testtrackidnumber';
        $record->startdate = 'Jan/01/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(track::TABLE, array(
            'name' => 'testtrackname',
            'idnumber' => 'testtrackidnumber',
            'startdate' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        )));
    }

    /**
     * Validate that create actions are converted to updates for tracks when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_track() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        $program = new curriculum(array('name' => 'testprogramname', 'idnumber' => 'testprogramidnumber'));
        $program->save();

        // Create the test track.
        $track = new track(array(
            'curid' => $program->id,
            'name' => 'testtrackname',
            'idnumber' => 'testtrackidnumber',
            'startdate' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        ));
        $track->save();

        // Run the track create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->idnumber = 'testtrackidnumber';
        $record->startdate = 'Jan/02/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(track::TABLE, array(
            'name' => 'testtrackname',
            'idnumber' => 'testtrackidnumber',
            'startdate' => rlip_timestamp(0, 0, 0, 1, 2, 2012)
        )));
    }

    /**
     * Validate that course descriptions can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Run the course create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->credits = '0';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(course::TABLE, array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'credits' => '0'
        )));
    }

    /**
     * Validate that create actions are converted to updates for course descriptions when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => '',
            'credits' => '0'
        ));
        $course->save();

        // Run the course create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->idnumber = 'testcourseidnumber';
        $record->credits = '5';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(course::TABLE, array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'credits' => 5
        )));
    }

    /**
     * Validate that class instances can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_class() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Run theclass create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->maxstudents = '5';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => 'testclassidnumber', 'maxstudents' => 5)));
    }

    /**
     * Validate that create actions are converted to updates for class instances when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_class() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber',
            'maxstudents' => 5
        ));
        $class->save();

        // Run the class create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->maxstudents = '10';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => 'testclassidnumber', 'maxstudents' => '10')));
    }

    /**
     * Validate that user sets can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_userset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Run the user set create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = 'testusersetdisplay';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(userset::TABLE, array(
            'name' => 'testusersetname',
            'display' => 'testusersetdisplay'
        )));
    }

    /**
     * Validate that create actions are converted to updates for user sets when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_userset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test user set.
        $userset = new userset(array('name' => 'testusersetname', 'display' => 'testusersetdisplay'));
        $userset->save();

        // Run the user set create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = 'updatedtestusersetdisplay';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(userset::TABLE, array(
            'name' => 'testusersetname',
            'display' => 'updatedtestusersetdisplay'
        )));
    }

    /**
     * Validate that student enrolments can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_student_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Run the student enrolment create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completetime = 'Jan/01/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        )));
    }

    /**
     * Validate that create actions are converted to updates for student enrolments when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_student_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Create the test student enrolment.
        $student = new student(array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        ));
        $student->save();

        // Run the student enrolment create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completetime = 'Jan/02/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 2, 2012)
        )));
    }

    /**
     * Validate that instructor enrolments can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_instructor_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Run the instructor enrolment create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = 'instructor';
        $record->completetime = 'Jan/01/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        )));
    }

    /**
     * Validate that create actions are converted to updates for instructor enrolments when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_instructor_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Create the instructor enrolment user.
        $instructor = new instructor(array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        ));
        $instructor->save();

        // Run the instructor enrolment create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = 'instructor';
        $record->completetime = 'Jan/02/2012';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'classid' => $class->id,
            'userid' => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 2, 2012)
        )));
    }

    /**
     * Validate that create actions are converted to updates for a student and instructor enrolment when the
     * "createorupdate" flag is enabled.
     *
     * This test first enrols a user as a student within a class and then updates the enrolment completion date and
     * then attempts to enrol the same user as an instructor within a the same class and then update that enrolment
     * completion date. In all four cases, the action is specified as a "create".
     */
    public function test_elis_createorupdate_updates_student_and_instructor_enrolment() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber')
        );
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username'  => 'testuserusername',
            'email'     => 'test@useremail.com',
            'idnumber'  => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'country'   => 'CA'
        ));
        $user->save();

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        // 1 Run the first student enrolment.
        $record = new stdClass;
        $record->action        = 'create';
        $record->context       = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role          = 'student';
        $record->completetime  = 'Jan/01/2012';

        // NOTE: we clone() the record because the createorupdate setting will rewrite the action parameter.
        $importplugin->process_record('enrolment', clone($record), 'bogus');

        // Validation of the enrolment record.
        $params = array(
            'classid'      => $class->id,
            'userid'       => $user->id,
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        );

        $this->assertTrue($DB->record_exists(student::TABLE, $params));

        // 2 Run the second student enrolment.
        $record->completetime = 'Jan/02/2012';
        $importplugin->process_record('enrolment', clone($record), 'bogus');
        $params['completetime'] = rlip_timestamp(0, 0, 0, 1, 2, 2012);
        $this->assertTrue($DB->record_exists(student::TABLE, $params));

        // 3 Run the first teacher enrolment.
        $record->role         = 'instructor';
        $record->completetime = 'Jan/01/2012';
        $importplugin->process_record('enrolment', clone($record), 'bogus');
        $params['completetime'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);
        $this->assertTrue($DB->record_exists(instructor::TABLE, $params));

        // 4 Run the second teacher enrolment.
        $record->completetime = 'Jan/02/2012';
        $importplugin->process_record('enrolment', clone($record), 'bogus');
        $params['completetime'] = rlip_timestamp(0, 0, 0, 1, 2, 2012);
        $this->assertTrue($DB->record_exists(instructor::TABLE, $params));
    }

    /**
     * Validate that createorupdate still works when the class idnumber contains
     * an underscore
     */
    public function testeliscreateorupdatesupportsunderscores() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1elis');

        // Create the test course.
        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the test class.
        $class = new pmclass(array(
            'courseid' => $course->id,
            // Idnumber has an underscore in it.
            'idnumber' => 'testclass_idnumber'
        ));
        $class->save();

        // Create the test user.
        $user = new user(array(
            'username'  => 'testuserusername',
            'email'     => 'test@useremail.com',
            'idnumber'  => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'country'   => 'CA'
        ));
        $user->save();

        // Set up an existing enrolment.
        $student = new student(array(
            'userid'           => $user->id,
            'classid'          => $class->id,
            'completestatusid' => student::STUSTATUS_FAILED
        ));
        $student->save();

        // Import the record, with create acting as an update.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclass_idnumber';
        $record->user_idnumber = 'testuseridnumber';
        $record->completestatusid = student::STUSTATUS_PASSED;

        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation.
        $this->assertEquals(1, $DB->count_records(student::TABLE));
        $params = array(
            'userid' => $user->id,
            'classid' => $class->id,
            'completestatusid' => student::STUSTATUS_PASSED
        );
        $exists = $DB->record_exists(student::TABLE, $params);
        $this->assertTrue($exists);
    }
}
