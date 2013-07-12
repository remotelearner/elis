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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that enrolment of users into class instances as students works.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_user_student_enrolment_testcase extends rlip_elis_test {

    /**
     * Data provider for fields that identify user records
     *
     * @return array Parameter data, as needed by the test methods
     */
    public function user_identifier_provider() {
        return array(
                array('create', 'delete', 'testuserusername', null, null),
                array('enrol', 'unenrol', null, 'testuser@email.com', null),
                array('enroll', 'unenroll', null, null, 'testuseridnumber')
               );
    }

    /**
     * Validate that users can be enrolled into class instances as students with
     * the minimum number of fields specified
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_student_minimal_fields_enrolment_import($actioncreate, $actiondelete, $username, $email,
                                                                           $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Run the class enrolment create action.
        $record = new stdClass;
        $record->action = $actioncreate;
        $record->context = 'class_testclassidnumber';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
        $midnighttoday = rlip_timestamp(0, 0, 0);
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'enrolmenttime' => $midnighttoday,
            'completetime' => $midnighttoday,
            'completestatusid' => student::STUSTATUS_NOTCOMPLETE,
            'grade' => 0.00000,
            'credits' => 0.00,
            'locked' => 0
        )));
    }

    /**
     * Validate that users can be enrolled into class instances as students, including
     * all supported fields
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_student_maximal_fields_enrolment_import($actioncreate, $actiondelete, $username, $email,
                                                                           $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Run the class enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }
        $record->enrolmenttime = 'Jan/01/2012';
        $record->completetime = 'Feb/01/2012';
        $record->completestatusid = student::STUSTATUS_PASSED;
        $record->grade = 80;
        $record->credits = 3;
        $record->locked = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'enrolmenttime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 2, 1, 2012),
            'completestatusid' => student::STUSTATUS_PASSED,
            'grade' => 80,
            'credits' => 3,
            'locked' => 1
        )));
    }

    /**
     * Data provider for testing the various date formats
     *
     * @return array Parameter data, as needed by the test method
     */
    public function date_provider() {
        // Legacy formats are MM/DD/YYYY, DD-MM-YYYY and YYYY.MM.DD.
        // Also need to support cases with no leading zeros.

        return array(
                // New MMM/DD/YYYY format.
                array('Jan/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('Feb/4/2012', array(0, 0, 0, 2, 4, 2012)),
                // Legacy MM/DD/YYYY format.
                array('01/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('2/4/2012', array(0, 0, 0, 2, 4, 2012)),
                // Legacy DD-MM-YYYY format.
                array('03-01-2012', array(0, 0, 0, 1, 3, 2012)),
                array('4-2-2012', array(0, 0, 0, 2, 4, 2012)),
                // Legacy YYYY.MM.DD format.
                array('2012.01.03', array(0, 0, 0, 1, 3, 2012)),
                array('2012.2.4', array(0, 0, 0, 2, 4, 2012))
        );
    }

    /**
     * Validate that date fields handle all necessary date formats
     *
     * @param string $datestring The provided date value
     * @param int $timestamp The equivalent timestamp
     * @dataProvider date_provider
     */
    public function test_elis_user_student_enrolment_handles_dates($datestring, $timestamp) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $timestamp = rlip_timestamp($timestamp[0], $timestamp[1], $timestamp[2], $timestamp[3], $timestamp[4], $timestamp[5]);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Run the class enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->enrolmenttime = $datestring;
        $record->completetime = $datestring;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'enrolmenttime' => $timestamp,
            'completetime' => $timestamp,
            'completestatusid' => student::STUSTATUS_NOTCOMPLETE,
            'grade' => 0.00000,
            'credits' => 0.00,
            'locked' => 0
        )));
    }

    /**
     * Data provider for testing the completion statuses
     *
     * @return array Parameter data, as needed by the test method
     */
    public function completion_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        // Support the numerical values, plus "Not Completed", "Failed", "Passed".

        return array(
                array(student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_NOTCOMPLETE),
                array("Not Completed", student::STUSTATUS_NOTCOMPLETE),
                array("not completed", student::STUSTATUS_NOTCOMPLETE),
                array(student::STUSTATUS_FAILED, student::STUSTATUS_FAILED),
                array("Failed", student::STUSTATUS_FAILED),
                array("failed", student::STUSTATUS_FAILED),
                array(student::STUSTATUS_PASSED, student::STUSTATUS_PASSED),
                array("Passed", student::STUSTATUS_PASSED),
                array("passed", student::STUSTATUS_PASSED)
        );
    }

    /**
     * Validate that the import handles all possible completion status values
     *
     * @param string $completionstring The value used for completion in the import
     * @param int $value The back-end equivalent completion constant
     * @dataProvider completion_provider
     */
    public function test_elis_user_student_enrolment_handles_completion_statuses($completionstring, $value) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Run the class enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->completestatusid = $completionstring;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'enrolmenttime' => rlip_timestamp(0, 0, 0),
            'completetime' => rlip_timestamp(0, 0, 0),
            'completestatusid' => $value,
            'grade' => 0.00000,
            'credits' => 0.00,
            'locked' => 0
        )));
    }

    /**
     * Data provider for a minimum update
     *
     * @return array Data needed for the appropriate unit test
     */
    public function minimal_update_field_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        set_config('noemailever', true);

        // We are being sneaky and testing specific date and completion status format cases here as well.
        return array(
                array('enrolmenttime', 'Jan/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('enrolmenttime', '01/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('enrolmenttime', '03-01-2012', array(0, 0, 0, 1, 3, 2012)),
                array('enrolmenttime', '2012.01.03', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', 'Jan/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '01/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '03-01-2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '2012.01.03', array(0, 0, 0, 1, 3, 2012)),
                array('completestatusid', student::STUSTATUS_NOTCOMPLETE, student::STUSTATUS_NOTCOMPLETE),
                array('completestatusid', "Not Completed", student::STUSTATUS_NOTCOMPLETE),
                array('completestatusid', student::STUSTATUS_FAILED, student::STUSTATUS_FAILED),
                array('completestatusid', "Failed", student::STUSTATUS_FAILED),
                array('completestatusid', student::STUSTATUS_PASSED, student::STUSTATUS_PASSED),
                array('completestatusid', "Passed", student::STUSTATUS_PASSED),
                array('grade', 50, 50),
                array('credits', 1, 1),
                array('locked', 1, 1)
        );
    }

    /**
     * Validate that a "student" enrolment can be updated with a minimal set of fields specified
     *
     * @param string $fieldname The name of the one import field we are setting
     * @param string $value The value to set for that import field
     * @param string $dbvalue The equivalent back-end database value
     * @dataProvider minimal_update_field_provider
     */
    public function test_update_elis_user_student_enrolment_with_minimal_fields($fieldname, $value, $dbvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        if ($fieldname === 'enrolmenttime' || $fieldname === 'completetime') {
            $dbvalue = rlip_timestamp($dbvalue[0], $dbvalue[1], $dbvalue[2], $dbvalue[3], $dbvalue[4], $dbvalue[5]);
        }

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        $student = new student(array('userid' => $user->id, 'classid' => $class->id, 'enrolmenttime' => 0));
        $student->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the student enrolment update action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->$fieldname = $value;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            $fieldname => $dbvalue
        )));
    }

    /**
     * Validate that a "student" enrolment can be updated, setting all available fields
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_update_elis_user_student_enrolment_with_all_fields($actioncreate, $actiondelete, $username, $email,
                                                                            $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        $student = new student(array('userid' => $user->id, 'classid' => $class->id, 'enrolmenttime' => 0));
        $student->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the student enrolment update action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }
        $record->enrolmenttime = 'Jan/01/2012';
        $record->completetime = 'Feb/01/2012';
        $record->completestatusid = student::STUSTATUS_PASSED;
        $record->grade = 80;
        $record->credits = 3;
        $record->locked = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'enrolmenttime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 2, 1, 2012),
            'completestatusid' => student::STUSTATUS_PASSED,
            'grade' => 80,
            'credits' => 3,
            'locked' => 1
        )));
    }

    /**
     * Validate that users can be enrolled from class instances (when assigned as a student)
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_student_unenrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('noemailever', true);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        $student = new student(array('userid' => $user->id, 'classid' => $class->id, 'enrolmenttime' => 0));
        $student->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(student::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the student enrolment delete action.
        $record = new stdClass;
        $record->action = $actiondelete;
        $record->context = 'class_testclassidnumber';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
        $this->assertEquals(0, $DB->count_records(student::TABLE));
    }
}
