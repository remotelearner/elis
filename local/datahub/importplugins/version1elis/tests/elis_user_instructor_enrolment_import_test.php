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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that assignment of users to class instances as instructors works.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_user_instructor_enrolment_test extends rlip_elis_test {

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
     * Validate that users can be assigned to class instances as instructors
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_instructor_enrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
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

        // Run the instructor assignment create action.
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
        $record->role = 'teacher';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
        $midnighttoday = rlip_timestamp(0, 0, 0);
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'assigntime' => $midnighttoday,
            'completetime' => $midnighttoday
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
                array('Jan/03/2012', 1, 3, 2012),
                array('Feb/4/2012', 2, 4, 2012),
                // Legacy MM/DD/YYYY format.
                array('01/03/2012', 1, 3, 2012),
                array('2/4/2012', 2, 4, 2012),
                // Legacy DD-MM-YYYY format.
                array('03-01-2012', 1, 3, 2012),
                array('4-2-2012', 2, 4, 2012),
                // Legacy YYYY.MM.DD format.
                array('2012.01.03', 1, 3, 2012),
                array('2012.2.4', 2, 4, 2012)
        );
    }

    /**
     * Validate that date fields handle all necessary date formats
     *
     * @param string $datestring The provided date value
     * @param int $timestamp The equivalent timestamp
     * @dataProvider date_provider
     */
    public function test_elis_user_instructor_enrolment_handles_dates($datestring, $m, $d, $y) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $timestamp = rlip_timestamp(0, 0, 0, $m, $d, $y);

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

        // Run the instructor assignment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->assigntime = $datestring;
        $record->completetime = $datestring;
        $record->role = 'teacher';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'assigntime' => $timestamp,
            'completetime' => $timestamp
        )));
    }

    /**
     * Data provider for testing the various roles that should all be tread as
     * instructors
     *
     * @return array Parameter data, as needed by the test method
     */
    public function role_provider() {
        return array(
                array('Teacher'),
                array('teacher'),
                array('Instructor'),
                array('instructor')
        );
    }

    /**
     * Validate that the role field is handled as needed during assignment
     *
     * @param string $role The input value for the role field
     * @dataProvider role_provider
     */
    public function test_elis_user_instructor_enrolment_handles_role($role) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

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

        // Run the instructor assignment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validation.
        $midnighttoday = rlip_timestamp(0, 0, 0);
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            'assigntime' => $midnighttoday,
            'completetime' => $midnighttoday
        )));
    }

    /**
     * Data provider for a minimum update
     *
     * @return array Data needed for the appropriate unit test
     */
    public function minimal_update_field_provider() {
        // We are being sneaky and testing specific date and completion status format cases here as well.
        return array(
                array('assigntime', 'Jan/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '01/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '03-01-2012', array(0, 0, 0, 1, 3, 2012)),
                array('assigntime', '2012.01.03', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', 'Jan/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '01/03/2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '03-01-2012', array(0, 0, 0, 1, 3, 2012)),
                array('completetime', '2012.01.03', array(0, 0, 0, 1, 3, 2012))
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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $dbvalue = rlip_timestamp($dbvalue[0], $dbvalue[1], $dbvalue[2], $dbvalue[3], $dbvalue[4], $dbvalue[5]);

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->reset_custom_field_list();
        $user->save();

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        $instructor = new instructor(array('userid' => $user->id, 'classid' => $class->id));
        $instructor->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the instructor assignment update action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->$fieldname = $value;
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'userid' => $user->id,
            'classid' => $class->id,
            $fieldname => $dbvalue
        )));
    }

    /**
     * Validate that an "instructor" assignment can be updated, setting all available fields
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_update_elis_user_instructor_enrolment_with_all_fields($actioncreate, $actiondelete, $username, $email,
                                                                               $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

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

        $instructor = new instructor(array('userid' => $user->id, 'classid' => $class->id));
        $instructor->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the instructor assignment update action.
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
        $record->assigntime = 'Jan/01/2012';
        $record->completetime = 'Feb/01/2012';
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/user.class.php'));

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

        $instructor = new instructor(array('userid' => $user->id, 'classid' => $class->id));
        $instructor->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the instructor assignment update action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));
    }

    /**
     * Validate that users can be enrolled from class instances (when assigned as an instructor)
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_instructor_unenrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('coursecontact', 'teacher,editingteacher');

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

        $instructor = new instructor(array('userid' => $user->id, 'classid' => $class->id));
        $instructor->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the instructor assignment delete action.
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
        $record->role = 'instructor';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
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
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        set_config('coursecontact', 'teacher,editingteacher');
        set_config('default_instructor_role', 'teacher', 'local_elisprogram');

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

        $instructor = new instructor(array('userid' => $user->id, 'classid' => $class->id));
        $instructor->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('userid' => $user->id, 'classid' => $class->id)));

        // Run the instructor assignment delete action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';
        $record->role = $role;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_delete($record, 'bogus', 'testclassidnumber');

        // Validation.
        $this->assertEquals(0, $DB->count_records(instructor::TABLE));
    }
}
