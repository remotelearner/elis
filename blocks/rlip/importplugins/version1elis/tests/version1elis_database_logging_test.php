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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/file_delay.class.php');

/*
 * Class that provides a delay for an import
 */
class rlipimport_version1elis_importprovider_manual_delay extends rlip_importprovider_file_delay {

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    public function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

        // Force MANUAL.
        return new rlip_dblogger_import(true);
    }
}

/**
 * Class for validating database logging functionality for the Version 1 ELIS import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class rlipimport_version1elis_databaselogging_testcase extends rlip_elis_test {

    /**
     * Validate that a success message is logged on user create
     */
    public function testusercreatelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_create($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on user update
     */
    public function testuserupdatelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_update($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on user delete
     */
    public function testuserdeletelogssuccessmessage() {
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
        // TODO: only use one field when that actually works.
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->email = 'testuser@email.com';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_delete($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity create
     */
    public function testpmentitycreatelogssuccessmessage() {
        global $DB;

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->name = 'testprogramname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->curriculum_create($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity update
     */
    public function testpmentityupdatelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->curriculum_update($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on pmentity delete
     */
    public function testpmentitydeletelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->curriculum_delete($record, 'bogus');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment create
     */
    public function testenrolmentcreatelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment update
     */
    public function testenrolmentupdatelogssuccessmessage() {
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
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_update_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
        $this->assertTrue($exists);
    }

    /**
     * Validate that a success message is logged on enrolment delete
     */
    public function testenrolmentdeletelogssuccessmessage() {
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
        // TODO: consider using fewer fields.
        $record->context = 'class_testclassidnumber';
        $record->user_idnumber = 'testuseridnumber';
        $record->user_username = 'testuserusername';
        $record->user_email = 'test@useremail.com';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->dblogger = new rlip_dblogger_import(false);
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_delete_student($record, 'bogus', 'testclassidnumber');
        $importplugin->dblogger->flush('bogus');

        $expectedmessage = 'All lines from import file bogus were successfully processed.';
        $where = 'statusmessage = ?';
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $where, array($expectedmessage));
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

        $provider = new rlipimport_version1elis_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or null to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        if ($message === null) {
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
    public function testnonwritablelogpathlogscorrectendtime() {
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
    public function testmissingactioncolumnlogscorrectendtime() {
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
    public function testmissingrequiredcolumnlogscorrectendtime() {
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
    public function testmaxruntimeexceededlogscorrectendtime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/tests/other/csv_delay.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/tests/other/file_delay.class.php');

        $importfile = $CFG->dirroot.'/blocks/rlip/importplugins/version1elis/tests/fixtures/userfiledelay.csv';
        $provider = new rlip_importprovider_file_delay($importfile, 'user');

        // Run the import.
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
    public function testsuccessfulprocessinglogscorrectendtime() {
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
    public function testlogginglogsfailuremessage() {
        global $DB;
        set_config('createorupdate', 0, 'rlipimport_version1elis');

        $data = array(
            'entity' => 'user',
            'action' => 'update',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $result = $this->run_user_import($data);

        $this->assertNull($result);

        $message = 'One or more lines from import file memoryfile failed because they contain data errors. ';
        $message .= 'Please fix the import file and re-upload it.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals(true, $exists);
    }

    /*
     * Serve filenames for the different entities
     * @return array of filenames
     */
    public function fileprovider() {
        return array(
                array('userfile2.csv', 'user'),
                array('coursefile2.csv', 'course'),
                array('enrolmentfile2.csv', 'enrolment')
        );
    }

    /**
     * Validate that MANUAL import obeys maxruntime
     * @dataProvider fileprovider
     */
    public function test_manualimportobeysmaxruntime($filename, $entity) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/tests/other/csv_delay.class.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // Set the log file name to a fixed value.
        $filepath = $CFG->dataroot;

        // MUST copy file to temp area 'cause it'll be deleted after import.
        $testfile = dirname(__FILE__).'/fixtures/'.$filename;
        $tempdir = $CFG->dataroot.'/block_rlip_phpunit/';
        $file = $tempdir.$filename;
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);

        $provider = new rlipimport_version1elis_importprovider_manual_delay($file, $entity);
        // Run the import.
        $importplugin = new rlip_importplugin_version1elis($provider, true);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // Maxruntime 1 sec.
        ob_end_clean();
        $this->assertNotNull($result);
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $ui = $record->statusmessage;
            break;
        }
        $expectedui = "/.*Failed importing all lines from import file.*due to time limit exceeded.*/";
        $this->assertRegExp($expectedui, $ui);

        // Test that the filename is also included in the error message.
        // The mock provider returns 'filename' as the filename.
        $expectedui = "/.*filename*/";
        $this->assertRegExp($expectedui, $ui);

        // Clean-up data file & tempdir.
        @unlink($file);
        @rmdir($tempdir);
    }

    /*
     * Serve filenames for the different entities, including number of lines
     *
     * @return array Pameter data, as expected by the test method
     */
    public function fileandlinecountprovider() {
        return array(
            array('userfile2.csv', 'user', 3),
            array('coursefile2.csv', 'course', 1),
            array('enrolmentfile2.csv', 'enrolment', 1)
        );
    }

    /**
     * Validate that SCHEDULED import obeys maxruntime
     *
     * @dataProvider fileandlinecountprovider
     * @param string $filename The name of the file we are importing
     * @param string $entity The entity type, such as 'user'
     * @param int $numlines The total number of lines in the file
     */
    public function test_scheduledimportobeysmaxruntime($filename, $entity, $numlines) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $filepath = '/block_rlip_phpunit/';
        $filename = $filename;
        set_config('schedule_files_path', $filepath, 'rlipimport_version1elis');
        set_config($entity.'_schedule_file', $filename, 'rlipimport_version1elis');

        // Set up the test directory.
        $testdir = $CFG->dataroot.$filepath;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create the job.
        $data = array(
            'plugin' => 'rlipimport_version1elis',
            'period' => '5m',
            'type' => 'rlipimport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Set next runtime values to a known state.
        $DB->execute("UPDATE {elis_scheduled_tasks} SET nextruntime = ?", array(1));
        $DB->execute("UPDATE {".RLIP_SCHEDULE_TABLE."} SET nextruntime = ?", array(1));

        // Run the import.
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        $mintime = time();
        run_ipjob($taskname, -1);
        $maxtime = time();

        // Clean-up data file & test dir.
        @unlink($testdir.$filename);
        @rmdir($testdir);

        // Validation.
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
        // Validate entity type.
        $this->assertEquals($entity, $log->entitytype);

        // Validate status message.
        $a = new stdClass;
        $a->filename = $filename;
        $a->recordsprocessed = 0;
        $a->totalrecords = $numlines;
        $expectedmessage = get_string('dblogimportexceedstimelimit', 'block_rlip', $a);
        $this->assertEquals($expectedmessage, $log->statusmessage);

        // Validate logged start time.
        $this->assertGreaterThanOrEqual($mintime, $log->starttime);
        $this->assertLessThanOrEqual($maxtime, $log->starttime);

        // Validate logged end time.
        $this->assertGreaterThanOrEqual($mintime, $log->endtime);
        $this->assertLessThanOrEqual($maxtime, $log->endtime);
    }
}