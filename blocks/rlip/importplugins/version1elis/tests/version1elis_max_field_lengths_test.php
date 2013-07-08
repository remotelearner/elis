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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');
//TODO: move file to a general location
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/phpunit/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/rlip_import_version1elis_fslogger.class.php');

/**
 * Class for capturing failure messages
 *
 */
class capture_fslogger extends rlip_import_version1elis_fslogger {
    public $message;

    /**
     * Log a failure message to the log file, and potentially the screen
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     * @param Object $record Imported data
     * @param string $type Type of import
     */
    function log_failure($message, $timestamp = 0, $filename = NULL, $entitydescriptor = NULL, $record = NULL, $type = NULL) {
        if (!empty($record) && !empty($type)) {
            $this->message = $this->general_validation_message($record, $message, $type);
        }

        return true;
    }
}

/**
 * Class for validating functionality related to field lengths and related logging
 */
class version1elisMaxFieldLengthsTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'config_plugins'  => 'moodle',
            'user'            => 'moodle',
            'user_info_field' => 'moodle',
            RLIP_LOG_TABLE    => 'block_rlip',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            field::TABLE      => 'elis_core',
            course::TABLE     => 'elis_program',
            curriculum::TABLE => 'elis_program',
            pmclass::TABLE    => 'elis_program',
            track::TABLE      => 'elis_program',
            user::TABLE       => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE    => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));

        return array(
            'context'                => 'moodle',
            coursetemplate::TABLE    => 'elis_program',
            curriculumstudent::TABLE => 'elis_program'
        );
    }

    /**
     * Unit tests for validating success of create / update actions with max length fields
     */

    /**
     * Validate that users can be created when max-length field values are supplied
     */
    public function testUserCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $record = new stdClass;
        $record->idnumber = str_repeat('a', 255);
        $record->username = str_repeat('a', 100);
        $record->firstname = str_repeat('a', 100);
        $record->lastname = str_repeat('a', 100);
        $record->email = str_repeat('a', 45).'@'.str_repeat('a', 50).'.com';
        $record->country ='CA';
        $record->password = 'A'.str_repeat('a', 22).'!0';
        $record->mi = str_repeat('a', 100);
        $record->email2 = str_repeat('a', 45).'@'.str_repeat('a', 50).'.com';
        $record->address = str_repeat('a', 70); // ELIS-6795 -- mdl_user.address is only 70 characters long
        $record->address2 = str_repeat('a', 100);
        $record->city = str_repeat('a', 100);
        $record->state = str_repeat('a', 100);
        $record->postalcode = str_repeat('a', 32);
        $record->phone = str_repeat('a', 100);
        $record->phone2 = str_repeat('a', 100);
        $record->fax = str_repeat('a', 100);

        $expected_password = hash_internal_user_password($record->password);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_create($record, 'bogus');

        $user = $DB->get_record(user::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->username, $user->username);
        $this->assertEquals($record->firstname, $user->firstname);
        $this->assertEquals($record->lastname, $user->lastname);
        $this->assertEquals($record->email, $user->email);
        $this->assertEquals($expected_password, $user->password);
        $this->assertEquals($record->mi, $user->mi);
        $this->assertEquals($record->email2, $user->email2);
        $this->assertEquals($record->address, $user->address);
        $this->assertEquals($record->address2, $user->address2);
        $this->assertEquals($record->city, $user->city);
        $this->assertEquals($record->state, $user->state);
        $this->assertEquals($record->postalcode, $user->postalcode);
        $this->assertEquals($record->phone, $user->phone);
        $this->assertEquals($record->phone2, $user->phone2);
        $this->assertEquals($record->fax, $user->fax);
    }

    /**
     * Validate that users can be updated when max-length field values are supplied
     */
    public function testUserUpdateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA'
        ));
        $user->save();

        $record = new stdClass;
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->firstname = str_repeat('a', 100);
        $record->lastname = str_repeat('a', 100);
        $record->password = 'A'.str_repeat('a', 22).'!0';
        $record->mi = str_repeat('a', 100);
        $record->email2 = str_repeat('a', 45).'@'.str_repeat('a', 50).'.com';
        $record->address = str_repeat('a', 70); // ELIS-6795 -- mdl_user.address is only 70 characters long
        $record->address2 = str_repeat('a', 100);
        $record->city = str_repeat('a', 100);
        $record->state = str_repeat('a', 100);
        $record->postalcode = str_repeat('a', 32);
        $record->phone = str_repeat('a', 100);
        $record->phone2 = str_repeat('a', 100);
        $record->fax = str_repeat('a', 100);

        $expected_password = hash_internal_user_password($record->password);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_update($record, 'bogus');

        $user = $DB->get_record(user::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->firstname, $user->firstname);
        $this->assertEquals($record->lastname, $user->lastname);
        $this->assertEquals($expected_password, $user->password);
        $this->assertEquals($record->mi, $user->mi);
        $this->assertEquals($record->email2, $user->email2);
        $this->assertEquals($record->address, $user->address);
        $this->assertEquals($record->address2, $user->address2);
        $this->assertEquals($record->city, $user->city);
        $this->assertEquals($record->state, $user->state);
        $this->assertEquals($record->postalcode, $user->postalcode);
        $this->assertEquals($record->phone, $user->phone);
        $this->assertEquals($record->phone2, $user->phone2);
        $this->assertEquals($record->fax, $user->fax);
    }

    /**
     * Validate that course descriptions be created when max-length field values are supplied
     */
    public function testCourseDescriptionCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $record = new stdClass;
        $record->context = 'course';
        $record->name = str_repeat('a', 255);
        $record->idnumber = str_repeat('a', 100);
        $record->code = str_repeat('a', 100);
        $record->lengthdescription = str_repeat('a', 100);
        $record->credits = str_repeat('1', 10);
        $record->cost = str_repeat('1', 10);
        $record->version = str_repeat('a', 100);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->course_create($record, 'bogus');

        $course = $DB->get_record(course::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $course->name);
        $this->assertEquals($record->code, $course->code);
        $this->assertEquals($record->lengthdescription, $course->lengthdescription);
        $this->assertEquals($record->credits, $course->credits);
        $this->assertEquals($record->cost, $course->cost);
        $this->assertEquals($record->version, $course->version);
    }

    /**
     * Validate that course description can be updated when max-length field values are supplied
     */
    public function testCourseDescriptionUpdateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $record = new stdClass;
        $record->context = 'course';
        $record->idnumber = 'testcourseidnumber';
        $record->name = str_repeat('a', 255);
        $record->code = str_repeat('a', 100);
        $record->lengthdescription = str_repeat('a', 100);
        $record->credits = str_repeat('1', 10);
        $record->cost = str_repeat('1', 10);
        $record->version = str_repeat('a', 100);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->course_update($record, 'bogus');

        $course = $DB->get_record(course::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $course->name);
        $this->assertEquals($record->code, $course->code);
        $this->assertEquals($record->lengthdescription, $course->lengthdescription);
        $this->assertEquals($record->credits, $course->credits);
        $this->assertEquals($record->cost, $course->cost);
        $this->assertEquals($record->version, $course->version);
    }

    /**
     * Validate that programs can be created when max-length field values are supplied
     */
    public function testProgramCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = str_repeat('a', 100);
        $record->name = str_repeat('a', 64);
        $record->timetocomplete = str_repeat('1', 63).'h';
        $record->frequency = str_repeat('1', 63).'h';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->curriculum_create($record, 'bogus');

        $program = $DB->get_record(curriculum::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $program->name);
        $this->assertEquals($record->timetocomplete, $program->timetocomplete);
        $this->assertEquals($record->frequency, $program->frequency);
    }

    /**
     * Validate that programs can be updated when max-length field values are supplied
     */
    public function testProgramUpdateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        set_config('enable_curriculum_expiration', 1, 'elis_program');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->name = str_repeat('a', 64);
        $record->timetocomplete = str_repeat('1', 63).'h';
        $record->frequency = str_repeat('1', 63).'h';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->curriculum_update($record, 'bogus');

        $program = $DB->get_record(curriculum::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $program->name);
        $this->assertEquals($record->timetocomplete, $program->timetocomplete);
        $this->assertEquals($record->frequency, $program->frequency);
    }

    /**
     * Validate that tracks can be created when max-length field values are supplied
     */
    public function testTrackCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $record = new stdClass;
        $record->context = 'track';
        $record->assignment = 'testprogramidnumber';
        $record->idnumber = str_repeat('a', 100);
        $record->name = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->track_create($record, 'bogus');

        $track = $DB->get_record(track::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $track->name);
    }

    /**
     * Validate that tracks can be updated when max-length field values are supplied
     */
    public function testTrackUpdateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array(
            'curid'    => $program->id,
            'idnumber' => 'testtrackidnumber'));
        $track->save();

        $record = new stdClass;
        $record->context = 'track';
        $record->idnumber = 'testtrackidnumber';
        $record->name = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->track_update($record, 'bogus');

        $track = $DB->get_record(track::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $track->name);
    }

    /**
     * Validate that class instances can be created when max-length field values are supplied
     */
    public function testClassInstanceCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $record = new stdClass;
        $record->context = 'class';
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = str_repeat('a', 100);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_create($record, 'bogus');

        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => $record->idnumber)));
    }

    //NOTE: no unit test for class update because only identifying field has a length limit

    /**
     * Validate that usersets can be created when max-length field values are supplied
     */
    public function testUsersetCreateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $record = new stdClass;
        $record->context = 'cluster';
        $record->name = str_repeat('a', 255);
        $record->display = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_create($record, 'bogus');

        $userset = $DB->get_record(userset::TABLE, array('name' => $record->name));
        $this->assertEquals($record->display, $userset->display);
    }

    /**
     * Validate that usersets can be updated when max-length field values are supplied
     */
    public function testUsersetUpdateIsSuccessfulWithMaxLengthFields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $record = new stdClass;
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_update($record, 'bogus');

        $userset = $DB->get_record(userset::TABLE, array('name' => $record->name));
        $this->assertEquals($record->display, $userset->display);
    }

    /**
     * Unit tests for validaing log messages with fields that are too long
     */

    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array $data The import data to process
     * @param string $expected_error The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expected_error, $entitytype) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set the log file location
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();
        ob_end_clean();

        //validate that a log file was created
        $manual = true;
        //get first summary record - at times, multiple summary records are created and this handles that problem
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        //get logfile name
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1elis';

        $format = get_string('logfile_timestamp','block_rlip');
        $testfilename = $filepath.'/'.$plugin_type.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        //get most recent logfile

        $filename = self::get_current_logfile($testfilename);
        if (!file_exists($filename)) {
            echo "\n can't find logfile: $filename for \n$testfilename";
        }
        $this->assertTrue(file_exists($filename));

        //fetch log line
        $pointer = fopen($filename, 'r');

        while (!feof($pointer)) {
            $error = fgets($pointer);

            if (!empty($error)) { // could be an empty new line
                //only use the "specific" section

                //"could not be ..." should appear near the end of the general message
                $position = strpos($error, 'could not be');
                $this->assertNotEquals(false, $position);
                $truncated_error = substr($error, $position + 1);

                //subsequent dot (period) ends the general message
                $position = strpos($truncated_error, '.');
                $this->assertNotEquals(false, $position);
                $truncated_error = substr($truncated_error, $position + 2);

                if (is_array($expected_error)) {
                    $actual_error[] = $truncated_error;
                } else {
                    $actual_error = $truncated_error;
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expected_error, $actual_error);
    }

    /**
     * Data provider for overly long user fields during user create
     *
     * @return array The data needed by the unit test method
     */
    public function userCreateFieldProvider() {
        return array(
            array('username', 101, NULL),
            array('password', 26, 'A'.str_repeat('a', 23).'!0'),
            array('idnumber', 256, NULL),
            array('firstname', 101, NULL),
            array('lastname', 101, NULL),
            array('mi', 101, NULL),
            array('email', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
            array('email2', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
            array('address', 101, NULL),
            array('address2', 101, NULL),
            array('city', 101, NULL),
            array('postalcode', 33, NULL),
            array('phone', 101, NULL),
            array('phone2', 101, NULL),
            array('fax', 101, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a user create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider userCreateFieldProvider
     */
    public function testUserCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $data = array(
            'action'    => 'create',
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Data provider for overly long user fields during user update
     *
     * @return array The data needed by the unit test method
     */
    public function userUpdateFieldProvider() {
        return array(
            array('password', 26, 'A'.str_repeat('a', 23).'!0'),
            array('firstname', 101, NULL),
            array('lastname', 101, NULL),
            array('mi', 101, NULL),
            array('email2', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
            array('address', 101, NULL),
            array('address2', 101, NULL),
            array('city', 101, NULL),
            array('postalcode', 33, NULL),
            array('phone', 101, NULL),
            array('phone2', 101, NULL),
            array('fax', 101, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a user update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider userUpdateFieldProvider
     */
    public function testUserUpdateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA')
        );
        $user->save();

        $data = array('action' => 'update',
                      'idnumber' => 'testuseridnumber');

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Data provider for overly long user fields during course description create
     *
     * @return array The data needed by the unit test method
     */
    public function courseCreateFieldProvider() {
        return array(
            array('idnumber', 101, NULL),
            array('name', 256, NULL),
            array('code', 101, NULL),
            array('lengthdescription', 101, NULL),
            array('credits', 11, str_repeat('1', 11)),
            array('cost', 11, NULL),
            array('version', 101, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a course description create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider courseCreateFieldProvider
     */
    public function testCourseDescriptionCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $data = array(
            'action'   => 'create',
            'context'  => 'course',
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during course description update
     *
     * @return array The data needed by the unit test method
     */
    public function courseUpdateFieldProvider() {
        return array(
            array('name', 256, NULL),
            array('code', 101, NULL),
            array('lengthdescription', 101, NULL),
            array('credits', 11, str_repeat('1', 11)),
            array('cost', 11, NULL),
            array('version', 101, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a course description update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider courseUpdateFieldProvider
     */
    public function testCourseDescriptionUpdateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseidnumber');

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during program create
     *
     * @return array The data needed by the unit test method
     */
    public function programCreateFieldProvider() {
        return array(
            array('idnumber', 101, NULL),
            array('name', 65, NULL),
            array('timetocomplete', 65, str_repeat('1', 64).'h'),
            array('frequency', 65, str_repeat('1', 64).'h')
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a program create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider programCreateFieldProvider
     */
    public function testProgramCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $data = array(
            'action'   => 'create',
            'context'  => 'curriculum',
            'name'     => 'testcurriculumname',
            'idnumber' => 'testcurriculumidnumber');

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during program update
     *
     * @return array The data needed by the unit test method
     */
    public function programUpdateFieldProvider() {
        return array(
            array('name', 65, NULL),
            array('timetocomplete', 65, str_repeat('1', 64).'h'),
            array('frequency', 65, str_repeat('1', 64).'h')
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a program update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider programUpdateFieldProvider
     */
    public function testProgramUpdateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $data = array(
            'action'   => 'update',
            'context'  => 'curriculum',
            'idnumber' => 'testprogramidnumber');

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during track create
     *
     * @return array The data needed by the unit test method
     */
    public function trackCreateFieldProvider() {
        return array(
            array('idnumber', 101, NULL),
            array('name', 256, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a track create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider trackCreateFieldProvider
     */
    public function testTrackCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $data = array(
            'action'     => 'create',
            'context'    => 'track',
            'assignment' => 'testprogramidnumber',
            'idnumber'   => 'testtrackidnumber',
            'name'       => 'testtrackname'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during track update
     *
     * @return array The data needed by the unit test method
     */
    public function trackUpdateFieldProvider() {
        return array(array('name', 256, NULL));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a track update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider trackUpdateFieldProvider
     */
    public function testTrackUpdateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $data = array(
            'action'   => 'update',
            'context'  => 'track',
            'idnumber' => 'testtrackidnumber'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during class instance create
     *
     * @return array The data needed by the unit test method
     */
    public function classCreateFieldProvider() {
        return array(array('idnumber', 101, NULL));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a class instance create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider classCreateFieldProvider
     */
    public function testClassInstanceCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $course = new course(array('idnumber' => 'testcourseidnumber',
                                   'name' => 'testcoursename',
                                   'syllabus' => ''));
        $course->save();

        $data = array(
            'action'     => 'create',
            'context'    => 'class',
            'assignment' => 'testcourseidnumber',
            'idnumber'   => 'testclassidnumber'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    //NOTE: no unit test for class update because only identifying field has a length limit

    /**
     * Data provider for overly long user fields during userset create
     *
     * @return array The data needed by the unit test method
     */
    public function usersetCreateFieldProvider() {
        return array(
            array('name', 256, NULL),
            array('display', 256, NULL)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a userset create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider usersetCreateFieldProvider
     */
    public function testUsersetCreateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $data = array(
            'action'  => 'create',
            'context' => 'cluster',
            'name'    => 'testusersetname'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Data provider for overly long user fields during userset update
     *
     * @return array The data needed by the unit test method
     */
    public function usersetUpdateFieldProvider() {
        return array(array('display', 256, NULL));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a userset update action
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or NULL if not applicable
     * @dataProvider usersetUpdateFieldProvider
     */
    public function testUsersetUpdateLogsErrorWhenFieldsTooLong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $data = array(
            'action'  => 'update',
            'context' => 'cluster',
            'name'    => 'testusersetname'
        );

        if ($customvalue !== NULL) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expected_error = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Testing of general message portion
     */

    /**
     * Validate the "user" general message
     */
    public function testUserErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->idnumber = str_repeat('a', 256);
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuser@email.com';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('user');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_user_field_lengths($record, 'bogus');

        $expected_message = "User with username \"{$record->username}\", email \"{$record->email}\", idnumber \"{$record->idnumber}\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }

    /**
     * Validate the "course description" general message
     */
    public function testCourseDescriptionErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testcourseidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_course_field_lengths($record, 'bogus');

        $expected_message = "Course description with idnumber \"testcourseidnumber\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }

    /**
     * Validate the "program" general message
     */
    public function testProgramErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_program_field_lengths($record, 'bogus');

        $expected_message = "Program with idnumber \"testprogramidnumber\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }

    /**
     * Validate the "track" general message
     */
    public function testTrackErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testtrackidnumber';
        //TODO: remove?
        $record->assignment = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_track_field_lengths($record, 'bogus');

        $expected_message = "Track with idnumber \"testtrackidnumber\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }

    /**
     * Validate the "class instance" general message
     */
    public function testClassInstanceErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = str_repeat('a', 101);
        //TODO: remove?
        $record->assignment = 'testcourseidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_class_field_lengths($record, 'bogus');

        $expected_message = "Class instance with idnumber \"{$record->idnumber}\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }

    /**
     * Validate the "userset" general message
     */
    public function testUsersetErrorContainsCorrectPrefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = str_repeat('a', 256);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(NULL);
        $importplugin->check_userset_field_lengths($record, 'bogus');

        $expected_message = "User set with name \"testusersetname\" could not be created.";
        $this->assertStringStartsWith($expected_message, $importplugin->fslogger->message);
    }
}
