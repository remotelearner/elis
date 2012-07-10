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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');

/**
 * Test the "Datatel" create-or-update functionality for the Version 1 ELIS
 * plugin
 */
class elis_createorupdate_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        return array('config_plugins' => 'moodle',
                     user::TABLE => 'elis_program');
    }

    /**
     * Validate that users can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //run the user create action
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(user::TABLE, array('username' => 'testuserusername',
                                                                'email' => 'test@useremail.com',
                                                                'idnumber' => 'testuseridnumber',
                                                                'firstname' => 'testuserfirstname')));
    }

    /**
     * Validate that create actions are converted to updates for users when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test user
        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //run the user create action
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'updatedtestuserfirstname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(user::TABLE, array('username' => 'testuserusername',
                                                                'email' => 'test@useremail.com',
                                                                'idnumber' => 'testuseridnumber',
                                                                'firstname' => 'updatedtestuserfirstname')));
    }

    /**
     * Validate that programs can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_program() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //run the program create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->name = 'testprogramname';
        $record->idnumber = 'testprogramidnumber';
        $record->description = 'testprogramdescription';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(curriculum::TABLE, array('name' => 'testprogramname',
                                                                      'idnumber' => 'testprogramidnumber',
                                                                      'description' => 'testprogramdescription')));
    }

    /**
     * Validate that create actions are converted to updates for programs when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_program() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test program
        $program = new curriculum(array('name' => 'testprogramname',
                                        'idnumber' => 'testprogramidnumber',
                                        'description' => 'testprogramdescription'));
        $program->save();

        //run the program create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->description = 'updatedtestprogramdescription';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(course::TABLE, array('name' => 'testprogramname',
                                                                  'idnumber' => 'testprogramidnumber',
                                                                  'description' => 'updatedtestprogramdescription')));
    }

    /**
     * Validate that tracks can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_track() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        $program = new curriculum(array('name' => 'testprogramname',
                                        'idnumber' => 'testprogramidnumber'));
        $program->save();

        //run the track create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->assignment = 'testprogramidnumber';
        $record->name = 'testtrackname';
        $record->idnumber = 'testtrackidnumber';
        $record->description = 'testtrackdescription';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(curriculum::TABLE, array('name' => 'testtrackname',
                                                                      'idnumber' => 'testtrackidnumber',
                                                                      'description' => 'testtrackdescription')));
    }

    /**
     * Validate that create actions are converted to updates for tracks when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_track() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        $program = new curriculum(array('name' => 'testprogramname',
                                        'idnumber' => 'testprogramidnumber'));
        $program->save();

        //create the test track
        $track = new track(array('curid' => $program->id,
                                 'name' => 'testtrackname',
                                 'idnumber' => 'testtrackidnumber',
                                 'description' => 'testtrackdescription'));
        $track->save();

        //run the track create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->idnumber = 'testtrackidnumber';
        $record->description = 'updatedtesttrackdescription';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(track::TABLE, array('name' => 'testtrackname',
                                                                 'idnumber' => 'testtrackidnumber',
                                                                 'description' => 'updatedtesttrackdescription')));
    }

    /**
     * Validate that course descriptions can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //run the course create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->syllabus = 'testcoursesyllabus';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(course::TABLE, array('name' => 'testcoursename',
                                                                  'idnumber' => 'testcourseidnumber',
                                                                  'syllabus' => 'testcoursesullabys')));
    }

    /**
     * Validate that create actions are converted to updates for course descriptions when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => 'testcoursesyllabus'));
        $course->save();

        //run the course create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->idnumber = 'testcourseidnumber';
        $record->syllabus = 'updatedtestcoursesyllabus';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(course::TABLE, array('name' => 'testcoursename',
                                                                  'idnumber' => 'tescourseidnumber',
                                                                  'syllabus' => 'updatedtestcoursesyllabus')));
    }

    /**
     * Validate that class instances can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_class() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //run theclass create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->maxstudents = '5';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => 'testclassidnumber',
                                                                   'maxstudents' => 5)));
    }

    /**
     * Validate that create actions are converted to updates for class instances when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_class() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //create the test class
        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber',
                                   'maxstudents' => 5));
        $class->save();

        //run the class create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->maxstudents = '10';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => 'tesclassidnumber',
                                                                   'maxstudents' => '10')));
    }

    /**
     * Validate that user sets can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_userset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //run the user set create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = 'testusersetdisplay';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(userset::TABLE, array('name' => 'testusersetname',
                                                                   'display' => 'testusersetdisplay')));
    }

    /**
     * Validate that create actions are converted to updates for user sets when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_userset() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test user set
        $userset = new userset(array('name' => 'testusersetname',
                                     'display' => 'testusersetdisplay'));
        $course->save();

        //run the user set create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = 'updatedtestusersetdisplay';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(userset::TABLE, array('name' => 'testusersetname',
                                                                   'display' => 'updatedtestusersetdisplay')));
    }

    /**
     * Validate that student enrolments can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_student_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/class.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //create the test class
        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //create the test user
        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //run the student enrolment create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completetime = 'Jan/01/2012';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('enrolment', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(student::TABLE, array('classid' => $class->id,
                                                                   'userid' => $user->id,
                                                                   'completetime' => mktime(0, 0, 0, 1, 1, 2012))));
    }

    /**
     * Validate that create actions are converted to updates for student enrolments when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_student_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/class.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //create the test class
        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //create the test user
        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //create the test student enrolment
        $student = new student(array('classid' => $class->id,
                                     'userid' => $user->id,
                                     'completetime' => mktime(0, 0, 0, 1, 1, 2012)));
        $student->save();

        //run the student enrolment create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completetime = 'Jan/02/2012';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('enrolment', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(student::TABLE, array('classid' => $class->id,
                                                                   'userid' => $user->id,
                                                                   'completestatusid' => mktime(0, 0, 0, 1, 2, 2012))));
    }

    /**
     * Validate that instructor enrolments can still be created when the "creatorupdate" flag is enabled
     */
    public function test_elis_createorupdate_creates_instructor_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/class.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //create the test class
        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //create the test user
        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //run the instructor enrolment create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = 'instructor';
        $record->completetime = 'Jan/01/2012';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('enrolment', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('classid' => $class->id,
                                                                      'userid' => $user->id,
                                                                      'completetime' => mktime(0, 0, 0, 1, 1, 2012))));
    }

    /**
     * Validate that create actions are converted to updates for instructor enrolments when the
     * "createorupdate" flag is enabled
     */
    public function test_elis_createorupdate_updates_instructor_enrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/class.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1elis');

        //create the test course
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber'));
        $course->save();

        //create the test class
        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //create the test user
        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //create the instructor enrolment user
        $instructor = new instructor(array('classid' => $class->id,
                                           'userid' => $user->id,
                                           'completetime' => mktime(0, 0, 0, 1, 1, 2012)));
        $instructor->save();

        //run the instructor enrolment create action
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completetime = 'Jan/02/2012';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('enrolment', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('classid' => $class->id,
                                                                      'userid' => $user->id,
                                                                      'completestatusid' => mktime(0, 0, 0, 1, 2, 2012))));
    }
}