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
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Class for validating that assignment of users to class instances as instructors
 * works
 */
class elis_user_instructor_enrolment_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(course::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     instructor::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array('context' => 'moodle',
                     'user' => 'moodle',
                     coursetemplate::TABLE => 'elis_program');
    }

    /**
     * Data provider for fields that identify user records
     *
     * @return array Parameter data, as needed by the test methods
     */
    function user_identifier_provider() {
        return array(
                array('create', 'delete', 'testuserusername', NULL, NULL),
                array('enrol', 'unenrol', NULL, 'testuser@email.com', NULL),
                array('enroll', 'unenroll', NULL, NULL, 'testuseridnumber')
               );
    }

    /**
     * Validate that users can be assigned to class instances as instructors
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    function test_elis_user_instructor_enrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the instructor assignment create action
        $record = new stdClass;
        $record->action = $actioncreate;
        $record->context = 'class_testclassidnumber';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }
        $record->role = 'teacher';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // validation
        $midnight_today = rlip_timestamp(0, 0, 0);
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id,
                                                                      'assigntime' => $midnight_today,
                                                                      'completetime' => $midnight_today)));
    }

    /**
     * Data provider for testing the various date formats
     *
     * @return array Parameter data, as needed by the test method
     */
    function date_provider() {
        // legacy formats are MM/DD/YYYY, DD-MM-YYYY and YYYY.MM.DD
        // also need to support cases with no leading zeros

        return array(
                // new MMM/DD/YYYY format
                array('Jan/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('Feb/4/2012', rlip_timestamp(0, 0, 0, 2, 4, 2012)),
                // legacy MM/DD/YYYY format
                array('01/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('2/4/2012', rlip_timestamp(0, 0, 0, 2, 4, 2012)),
                // legacy DD-MM-YYYY format
                array('03-01-2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('4-2-2012', rlip_timestamp(0, 0, 0, 2, 4, 2012)),
                // legacy YYYY.MM.DD format
                array('2012.01.03', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('2012.2.4', rlip_timestamp(0, 0, 0, 2, 4, 2012))
        );
    }

    /**
     * Validate that date fields handle all necessary date formats
     *
     * @param string $datestring The provided date value
     * @param int $timestamp The equivalent timestamp
     * @dataProvider date_provider
     */
    public function test_elis_user_instructor_enrolment_handles_dates($datestring, $timestamp) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the instructor assignment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->assigntime = $datestring;
        $record->completetime = $datestring;
        $record->role = 'teacher';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id,
                                                                      'assigntime' => $timestamp,
                                                                      'completetime' => $timestamp)));
    }

    /**
     * Data provider for testing the various roles that should all be tread as
     * instructors
     *
     * @return array Parameter data, as needed by the test method
     */
    function role_provider() {
        return array(array('Teacher'),
                     array('teacher'),
                     array('Instructor'),
                     array('instructor'));
    }

    /**
     * Validate that the role field is handled as needed during assignment
     *
     * @param string $role The input value for the role field
     * @dataProvider role_provider
     */
    public function test_elis_user_instructor_enrolment_handles_role($role) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the instructor assignment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // validation
        $midnight_today = rlip_timestamp(0, 0, 0);
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id,
                                                                      'assigntime' => $midnight_today,
                                                                      'completetime' => $midnight_today)));
    }

    /**
     * Data provider for a minimum update
     *
     * @return array Data needed for the appropriate unit test
     */
    function minimal_update_field_provider() {
        // we are being sneaky and testing specific date and completion status format
        // cases here as well
        return array(
                array('assigntime', 'Jan/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '01/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '03-01-2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '2012.01.03', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('completetime', 'Jan/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('completetime', '01/03/2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('completetime', '03-01-2012', rlip_timestamp(0, 0, 0, 1, 3, 2012)),
                array('completetime', '2012.01.03', rlip_timestamp(0, 0, 0, 1, 3, 2012))
        );
    }

    /**
     * Validate that an "instructor" assignment can be updated with a minimal set of fields specified
     *
     * @param string $fieldname The name of the one import field we are setting
     * @param string $value The value to set for that import field
     * @param string $dbvalue The equivalent back-end database value
     * @dataProvider minimal_update_field_provider
     */
    public function test_update_elis_user_instructor_enrolment_with_minimal_fields($fieldname, $value, $dbvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id,
                                           'classid' => $class->id));
        $instructor->save();

        //validate setup
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));

        //run the instructor assignment update action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->$fieldname = $value;
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id,
                                                                      $fieldname => $dbvalue)));
    }

    /**
     * Validate that an "instructor" assignment can be updated, setting all available fields
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_update_elis_user_instructor_enrolment_with_all_fields($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id,
                                           'classid' => $class->id));
        $instructor->save();

        //validate setup
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));

        //run the instructor assignment update action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }
        $record->assigntime = 'Jan/01/2012';
        $record->completetime = 'Feb/01/2012';
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'assigntime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 2, 1, 2012)
        )));
    }

    /**
     * Validate that the role field is handled as needed during updates
     *
     * @param string $role The input value for the role field
     * @dataProvider role_provider
     */
    public function test_update_elis_user_instructor_enrolment_handles_role($role) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id,
                                           'classid' => $class->id));
        $instructor->save();

        //validate setup
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));

        //run the instructor assignment update action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));
    }

    /**
     * Validate that users can be enrolled from class instances (when assigned as an instructor)
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_instructor_unenrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id,
                                           'classid' => $class->id));
        $instructor->save();

        //validate setup
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));

        //run the instructor assignment delete action
        $record = new stdClass;
        $record->action = $actiondelete;
        $record->context = 'class_testclassidnumber';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(instructor::TABLE));
    }

    /**
     * Validate that the role field is handled as needed during unassignment
     *
     * @param string $role The input value for the role field
     * @dataProvider role_provider
     */
    public function test_elis_user_instructor_unenrolment_handles_role($role) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id,
                                           'classid' => $class->id));
        $instructor->save();

        //validate setup
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id,
                                                                      'classid' => $class->id)));

        //run the instructor assignment delete action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_delete($record, 'bogus', 'testclassidnumber');

        //validation
        $this->assertEquals(0, $DB->count_records(instructor::TABLE));
    }
}
