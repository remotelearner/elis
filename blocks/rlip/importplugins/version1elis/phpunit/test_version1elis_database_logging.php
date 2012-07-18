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
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Class for validating database logging functionality for the Version 1 ELIS
 * import
 */
class version1elisMaxFieldLengthsTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'context'               => 'moodle',
            'events_queue'          => 'moodle',
            'events_queue_handlers' => 'moodle',
            RLIP_LOG_TABLE          => 'block_rlip',
            course::TABLE           => 'elis_program',
            curriculum::TABLE       => 'elis_program',
            pmclass::TABLE          => 'elis_program',
            student::TABLE          => 'elis_program',
            user::TABLE             => 'elis_program',
            usermoodle::TABLE       => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));
        require_once(elispm::lib('data/waitlist.class.php'));

        return array(
            'block_instances'         => 'moodle',
            'block_positions'         => 'moodle',
            'cohort_members'          => 'moodle',
            'comments'                => 'moodle',
            'external_services_users' => 'moodle',
            'external_tokens'         => 'moodle',
            'filter_active'           => 'moodle',
            'filter_config'           => 'moodle',
            'groups_members'          => 'moodle',
            'log'                     => 'moodle',
            'rating'                  => 'moodle',
            'role_assignments'        => 'moodle',
            'role_capabilities'       => 'moodle',
            'role_names'              => 'moodle',
            'sessions'                => 'moodle',
            'user'                    => 'moodle',
            'user_enrolments'         => 'moodle',
            'user_info_data'          => 'moodle',
            'user_lastaccess'         => 'moodle',
            'user_preferences'        => 'moodle',
            clusterassignment::TABLE  => 'elis_program',
            clustercurriculum::TABLE  => 'elis_program',
            coursetemplate::TABLE     => 'elis_program',
            curriculumstudent::TABLE  => 'elis_program',
            field_data_char::TABLE    => 'elis_core',
            field_data_int::TABLE     => 'elis_core',
            field_data_num::TABLE     => 'elis_core',
            field_data_text::TABLE    => 'elis_core',
            instructor::TABLE         => 'elis_program',
            student_grade::TABLE      => 'elis_program',
            usertrack::TABLE          => 'elis_program',
            waitlist::TABLE           => 'elis_program'
        );
    }

    /**
     * Validate that a success message is logged on user create
     */
    public function testUserCreateLogsSuccessMessage() {
        global $DB;

        $record = new stdClass;
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuser@email.com';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_create($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on user update
     */
    public function testUserUpdateLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $record = new stdClass;
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'updatedtestuserfirstname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_update($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on user delete
     */
    public function testUserDeleteLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $record = new stdClass;
        //TODO: only use one field when that actually works
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->email = 'testuser@email.com';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_delete($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity create
     */
    public function testPmentityCreateLogsSuccessMessage() {
        global $DB;

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->name = 'testprogramname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->curriculum_create($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity update
     */
    public function testPmentityUpdateLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $program = new curriculum(array(
            'idnumber' => 'testprogramidnumber',
            'name'     => 'testprogramname'
        ));
        $program->save();

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->name = 'updatedtestprogramname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->curriculum_update($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity delete
     */
    public function testPmentityDeleteLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $program = new curriculum(array(
            'idnumber' => 'testprogramidnumber',
            'name'     => 'testprogramname'
        ));
        $program->save();

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->curriculum_delete($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment create
     */
    public function testEnrolmentCreateLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber'
        ));
        $pmclass->save();

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'testuser@email.com',
            'country'   => 'CA'
        ));
        $user->save();

        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_idnumber = 'testuseridnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment update
     */
    public function testEnrolmentUpdateLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber'
        ));
        $pmclass->save();

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'testuser@email.com',
            'country'   => 'CA'
        ));
        $user->save();

        $student = new student(array(
            'classid' => $pmclass->id,
            'userid'  => $user->id
        ));
        $student->save();

        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_idnumber = 'testuseridnumber';
        $record->completestatusid = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_update_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment delete
     */
    public function testEnrolmentDeleteLogsSuccessMessage() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber'
        ));
        $pmclass->save();

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'testuser@email.com',
            'country'   => 'CA'
        ));
        $user->save();

        $student = new student(array(
            'classid' => $pmclass->id,
            'userid'  => $user->id
        ));
        $student->save();

        $record = new stdClass;
        //TODO: consider using fewer fields
        $record->context = 'class_testclassidnumber';
        $record->user_idnumber = 'testuseridnumber';
        $record->user_username = 'testuserusername';
        $record->user_email = 'test@useremail.com';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_delete_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expected_message = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expected_message));
        $this->assertTrue($exists);
    }
}