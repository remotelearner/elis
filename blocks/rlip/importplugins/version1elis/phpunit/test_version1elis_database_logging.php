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
//TODO: move to a more general location
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/phpunit/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_mockuser extends rlip_importprovider_mock {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/*
 * Class that provides a delay for an import
 */
class rlip_importprovider_manual_delay
      extends rlip_importprovider_file_delay {

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

        //force MANUAL
        return new rlip_dblogger_import(true);
    }
}

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
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'config_plugins'        => 'moodle',
            'context'               => 'moodle',
            'elis_scheduled_tasks'  => 'elis_core',
            'events_queue'          => 'moodle',
            'events_queue_handlers' => 'moodle',
            'grade_grades'          => 'moodle',
            RLIP_LOG_TABLE          => 'block_rlip',
            RLIP_SCHEDULE_TABLE     => 'block_rlip',
            course::TABLE           => 'elis_program',
            curriculum::TABLE       => 'elis_program',
            curriculumcourse::TABLE => 'elis_program',
            pmclass::TABLE          => 'elis_program',
            student::TABLE          => 'elis_program',
            track::TABLE            => 'elis_program',
            user::TABLE             => 'elis_program',
            usermoodle::TABLE       => 'elis_program',
            clustertrack::TABLE     => 'elis_program',
            track::TABLE            => 'elis_program',
            trackassignment::TABLE  => 'elis_program',
            curriculumcourse::TABLE => 'elis_program',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
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
            'cache_flags'             => 'moodle',
            'files'                   => 'moodle',
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

    /**
     * Validation for log end times
     */

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Import data to use
     */
    private function run_user_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or NULL to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = NULL) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        if ($message === NULL) {
            $message = 'All lines from import file memoryfile were successfully processed.';
        }

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => $message);
        return $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
    }

    /**
     * Validate that summary log end time is set when an invalid folder is set
     * for the file system log
     */
    public function testNonWritableLogPathLogsCorrectEndTime() {
        global $DB;

        set_config('logfilelocation', 'adirectorythatshouldnotexist', 'rlipimport_version1elis');

        $data = array(
            'action'    => 'create',
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'email'     => 'test@useremail.com',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'country'   => 'CA'
        );

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when the action column is not
     * specified in the import
     */
    public function testMissingActionColumnLogsCorrectEndTime() {
        global $DB;

        $data = array('idnumber' => 'testuseridnumber');

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when a required column is not
     * specified in the import
     */
    public function testMissingRequiredColumnLogsCorrectEndTime() {
        global $DB;

        $data = array('action' => 'create');

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when maximum runtime is exceeded
     * when running the import
     */
    public function testMaxRuntimeExceededLogsCorrectEndTime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');

        $import_file = $CFG->dirroot.'/blocks/rlip/importplugins/version1elis/phpunit/userfiledelay.csv';
        $provider = new rlip_importprovider_file_delay($import_file, 'user');

        //run the import
        $mintime = time();
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider);
        $importplugin->run(0, 0, 1);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validate that summary log end time is set when successfully processing an
     * import file
     */
    public function testSuccessfulProcessingLogsCorrectEndTime() {
        global $DB;

        $data = array(
            'action'    => 'create',
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'email'     => 'test@useremail.com',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'country'   => 'CA'
        );

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }

    /**
     * Validates the standard failure message
     */
    public function testLoggingLogsFailureMessage() {
        set_config('createorupdate', 0, 'rlipimport_version1elis');

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $message = 'One or more lines from import file memoryfile failed because they contain data errors. '.
                   'Please fix the import file and re-upload it.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals(true, $exists);
    }

    /*
     * Serve filenames for the different entities
     * @return array of filenames
     */
    public function fileProvider() {
        return array(array('userfile2.csv', 'user'),
                     array('coursefile2.csv', 'course'),
                     array('enrolmentfile2.csv', 'enrolment'));
    }

    /**
     * Validate that MANUAL import obeys maxruntime
     * @dataProvider fileProvider
     */
    public function testManualImportObeysMaxRunTime($filename, $entity) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        //set the log file name to a fixed value
        $filepath = $CFG->dataroot;

        // MUST copy file to temp area 'cause it'll be deleted after import
        $testfile = dirname(__FILE__) .'/'.$filename;
        $tempdir = $CFG->dataroot .'/block_rlip_phpunit/';
        $file = $tempdir .$filename;
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);

        $provider = new rlip_importprovider_manual_delay($file, $entity);
        //run the import
        $importplugin = new rlip_importplugin_version1elis($provider, true);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // maxruntime 1 sec
        ob_end_clean();
        $this->assertNotNull($result);
        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $ui = $record->statusmessage;
            break;
        }
        $expected_ui = "/.*Failed importing all lines from import file.*due to time limit exceeded.*/";
        $this->assertRegExp($expected_ui, $ui);

        // test that the filename is also included in the error message
        // the mock provider returns 'filename' as the filename
        $expected_ui = "/.*filename*/";
        $this->assertRegExp($expected_ui, $ui);

        // clean-up data file & tempdir
        @unlink($file);
        @rmdir($tempdir);
    }

    /*
     * Serve filenames for the different entities, including number of lines
     *
     * @return array Pameter data, as expected by the test method
     */
    public function fileAndLineCountProvider() {
        return array(array('userfile2.csv', 'user', 3),
                     array('coursefile2.csv', 'course', 1),
                     array('enrolmentfile2.csv', 'enrolment', 1));
    }

    /**
     * Validate that SCHEDULED import obeys maxruntime
     *
     * @dataProvider fileAndLineCountProvider
     * @param string $filename The name of the file we are importing
     * @param string $entity The entity type, such as 'user'
     * @param int $numlines The total number of lines in the file
     */
    public function testScheduledImportObeysMaxRunTime($filename, $entity, $numlines) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $file_path = '/block_rlip_phpunit/';
        $file_name = $filename;
        set_config('schedule_files_path', $file_path, 'rlipimport_version1elis');
        set_config($entity.'_schedule_file', $file_name, 'rlipimport_version1elis');

        //set up the test directory
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir.$file_name);

        //create the job
        $data = array('plugin' => 'rlipimport_version1elis',
                      'period' => '5m',
                      'type' => 'rlipimport');
        $taskid = rlip_schedule_add_job($data);

        //set next runtime values to a known state
        $DB->execute("UPDATE {elis_scheduled_tasks}
                          SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."}
                      SET nextruntime = ?", array(1));

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $mintime = time();
        run_ipjob($taskname, -1);
        $maxtime = time();

        //clean-up data file & test dir
        @unlink($testdir.$file_name);
        @rmdir($testdir);

        //validation
        $params = array(
            'export' => 0,
            'plugin' => 'rlipimport_version1elis',
            'targetstarttime' => 1,
            'filesuccesses' => 0,
            'filefailures' => 0,
            'storedsuccesses' => 0,
            'storedfailures' => 0
        );
        $exists = $DB->record_exists(RLIP_LOG_TABLE, $params);

        $log = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        //validate entity type
        $this->assertEquals($entity, $log->entitytype);

        //validate status message
        $a = new stdClass;
        $a->filename = $filename;
        $a->recordsprocessed = 0;
        $a->totalrecords = $numlines;
        $expected_message = get_string('dblogimportexceedstimelimit', 'block_rlip', $a);
        $this->assertEquals($expected_message, $log->statusmessage);

        //validate logged start time
        $this->assertGreaterThanOrEqual($mintime, $log->starttime);
        $this->assertLessThanOrEqual($maxtime, $log->starttime);

        //validate logged end time
        $this->assertGreaterThanOrEqual($mintime, $log->endtime);
        $this->assertLessThanOrEqual($maxtime, $log->endtime);
    }
}
