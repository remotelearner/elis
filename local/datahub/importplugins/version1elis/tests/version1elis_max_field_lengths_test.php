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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');

/**
 * Class for validating functionality related to field lengths and related logging
 * @group local_datahub
 * @group dhimport_version1elis
 */
class version1elismaxfieldlengths_testcase extends rlip_elis_test {

    /**
     * Validate that users can be created when max-length field values are supplied
     */
    public function testusercreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

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
        $record->address = str_repeat('a', 70); // ELIS-6795 -- mdl_user.address is only 70 characters long.
        $record->address2 = str_repeat('a', 100);
        $record->city = str_repeat('a', 100);
        $record->state = str_repeat('a', 100);
        $record->postalcode = str_repeat('a', 32);
        $record->phone = str_repeat('a', 100);
        $record->phone2 = str_repeat('a', 100);
        $record->fax = str_repeat('a', 100);

        $expectedpassword = $record->password;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_create($record, 'bogus');

        $user = $DB->get_record(user::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->username, $user->username);
        $this->assertEquals($record->firstname, $user->firstname);
        $this->assertEquals($record->lastname, $user->lastname);
        $this->assertEquals($record->email, $user->email);
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

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $record->username));
        $this->assertTrue(validate_internal_user_password($userrec, $expectedpassword));
    }

    /**
     * Validate that users can be updated when max-length field values are supplied
     */
    public function testuserupdateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

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
        $record->address = str_repeat('a', 70); // ELIS-6795 -- mdl_user.address is only 70 characters long.
        $record->address2 = str_repeat('a', 100);
        $record->city = str_repeat('a', 100);
        $record->state = str_repeat('a', 100);
        $record->postalcode = str_repeat('a', 32);
        $record->phone = str_repeat('a', 100);
        $record->phone2 = str_repeat('a', 100);
        $record->fax = str_repeat('a', 100);

        $expectedpassword = $record->password;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_update($record, 'bogus');

        $user = $DB->get_record(user::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->firstname, $user->firstname);
        $this->assertEquals($record->lastname, $user->lastname);
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

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $record->username));
        $this->assertTrue(validate_internal_user_password($userrec, $expectedpassword));
    }

    /**
     * Validate that course descriptions be created when max-length field values are supplied
     */
    public function testcoursedescriptioncreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

        $record = new stdClass;
        $record->context = 'course';
        $record->name = str_repeat('a', 255);
        $record->idnumber = str_repeat('a', 100);
        $record->code = str_repeat('a', 100);
        $record->lengthdescription = str_repeat('a', 100);
        $record->credits = str_repeat('1', 10);
        $record->cost = str_repeat('1', 10);
        $record->version = str_repeat('a', 100);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
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
    public function testcoursedescriptionupdateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
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
    public function testprogramcreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        set_config('enable_curriculum_expiration', 1, 'local_elisprogram');

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = str_repeat('a', 100);
        $record->name = str_repeat('a', 64);
        $record->timetocomplete = str_repeat('1', 63).'h';
        $record->frequency = str_repeat('1', 63).'h';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->curriculum_create($record, 'bogus');

        $program = $DB->get_record(curriculum::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $program->name);
        $this->assertEquals($record->timetocomplete, $program->timetocomplete);
        $this->assertEquals($record->frequency, $program->frequency);
    }

    /**
     * Validate that programs can be updated when max-length field values are supplied
     */
    public function testprogramupdateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        set_config('enable_curriculum_expiration', 1, 'local_elisprogram');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $record = new stdClass;
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramidnumber';
        $record->name = str_repeat('a', 64);
        $record->timetocomplete = str_repeat('1', 63).'h';
        $record->frequency = str_repeat('1', 63).'h';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->curriculum_update($record, 'bogus');

        $program = $DB->get_record(curriculum::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $program->name);
        $this->assertEquals($record->timetocomplete, $program->timetocomplete);
        $this->assertEquals($record->frequency, $program->frequency);
    }

    /**
     * Validate that tracks can be created when max-length field values are supplied
     */
    public function testtrackcreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $record = new stdClass;
        $record->context = 'track';
        $record->assignment = 'testprogramidnumber';
        $record->idnumber = str_repeat('a', 100);
        $record->name = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->track_create($record, 'bogus');

        $track = $DB->get_record(track::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $track->name);
    }

    /**
     * Validate that tracks can be updated when max-length field values are supplied
     */
    public function testtrackupdateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->track_update($record, 'bogus');

        $track = $DB->get_record(track::TABLE, array('idnumber' => $record->idnumber));
        $this->assertEquals($record->name, $track->name);
    }

    /**
     * Validate that class instances can be created when max-length field values are supplied
     */
    public function testclassinstancecreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');

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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_create($record, 'bogus');

        $this->assertTrue($DB->record_exists(pmclass::TABLE, array('idnumber' => $record->idnumber)));
    }

    // NOTE: no unit test for class update because only identifying field has a length limit.

    /**
     * Validate that usersets can be created when max-length field values are supplied
     */
    public function testusersetcreateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        $record = new stdClass;
        $record->context = 'cluster';
        $record->name = str_repeat('a', 255);
        $record->display = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_create($record, 'bogus');

        $userset = $DB->get_record(userset::TABLE, array('name' => $record->name));
        $this->assertEquals($record->display, $userset->display);
    }

    /**
     * Validate that usersets can be updated when max-length field values are supplied
     */
    public function testusersetupdateissuccessfulwithmaxlengthfields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $record = new stdClass;
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = str_repeat('a', 255);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
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
     * @param string $expectederror The error we are expecting (message only)
     * @param user $entitytype One of 'user', 'course', 'enrolment'
     */
    protected function assert_data_produces_error($data, $expectederror, $entitytype) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

        // Set the log file location.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        // Run the import.
        $classname = "rlipimport_version1elis_importprovider_fslog{$entitytype}";
        $provider = new $classname($data);
        $instance = rlip_dataplugin_factory::factory('dhimport_version1elis', $provider, null, true);
        // Suppress output for now.
        ob_start();
        $instance->run();
        ob_end_clean();

        // Validate that a log file was created.
        $manual = true;
        // Get first summary record - at times, multiple summary records are created and this handles that problem.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        // Get logfile name.
        $plugintype = 'import';
        $plugin = 'dhimport_version1elis';

        $format = get_string('logfile_timestamp', 'local_datahub');
        $testfilename = $filepath.'/'.$plugintype.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        // Get most recent logfile.

        $filename = self::get_current_logfile($testfilename);
        if (!file_exists($filename)) {
            echo "\n can't find logfile: $filename for \n$testfilename";
        }
        $this->assertTrue(file_exists($filename));

        // Fetch log line.
        $pointer = fopen($filename, 'r');

        while (!feof($pointer)) {
            $error = fgets($pointer);

            if (!empty($error)) { // Could be an empty new line.
                // Only use the "specific" section.

                // Msg "could not be ..." should appear near the end of the general message..
                $position = strpos($error, 'could not be');
                $this->assertNotEquals(false, $position);
                $truncatederror = substr($error, $position + 1);

                // Subsequent dot (period) ends the general message.
                $position = strpos($truncatederror, '.');
                $this->assertNotEquals(false, $position);
                $truncatederror = substr($truncatederror, $position + 2);

                if (is_array($expectederror)) {
                    $actualerror[] = $truncatederror;
                } else {
                    $actualerror = $truncatederror;
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expectederror, $actualerror);
    }

    /**
     * Data provider for overly long user fields during user create
     *
     * @return array The data needed by the unit test method
     */
    public function usercreatefieldprovider() {
        return array(
                array('username', 101, null),
                array('password', 26, 'A'.str_repeat('a', 23).'!0'),
                array('idnumber', 256, null),
                array('firstname', 101, null),
                array('lastname', 101, null),
                array('mi', 101, null),
                array('email', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
                array('email2', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
                array('address', 101, null),
                array('address2', 101, null),
                array('city', 101, null),
                array('postalcode', 33, null),
                array('phone', 101, null),
                array('phone2', 101, null),
                array('fax', 101, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a user create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider usercreatefieldprovider
     */
    public function test_usercreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        $data = array(
            'action'    => 'create',
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Data provider for overly long user fields during user update
     *
     * @return array The data needed by the unit test method
     */
    public function userupdatefieldprovider() {
        return array(
                array('password', 26, 'A'.str_repeat('a', 23).'!0'),
                array('firstname', 101, null),
                array('lastname', 101, null),
                array('mi', 101, null),
                array('email2', 101, str_repeat('a', 46).'@'.str_repeat('a', 50).'.com'),
                array('address', 101, null),
                array('address2', 101, null),
                array('city', 101, null),
                array('postalcode', 33, null),
                array('phone', 101, null),
                array('phone2', 101, null),
                array('fax', 101, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a user update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider userupdatefieldprovider
     */
    public function test_userupdatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA')
        );
        $user->save();

        $data = array('action' => 'update', 'idnumber' => 'testuseridnumber');

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'user');
    }

    /**
     * Data provider for overly long user fields during course description create
     *
     * @return array The data needed by the unit test method
     */
    public function coursecreatefieldprovider() {
        return array(
                array('idnumber', 101, null),
                array('name', 256, null),
                array('code', 101, null),
                array('lengthdescription', 101, null),
                array('credits', 11, str_repeat('1', 11)),
                array('cost', 11, null),
                array('version', 101, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a course description create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider coursecreatefieldprovider
     */
    public function test_coursedescriptioncreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

        $data = array(
            'action'   => 'create',
            'context'  => 'course',
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during course description update
     *
     * @return array The data needed by the unit test method
     */
    public function courseupdatefieldprovider() {
        return array(
                array('name', 256, null),
                array('code', 101, null),
                array('lengthdescription', 101, null),
                array('credits', 11, str_repeat('1', 11)),
                array('cost', 11, null),
                array('version', 101, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a course description update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character, or null if not applicable.
     * @dataProvider courseupdatefieldprovider
     */
    public function test_coursedescriptionupdatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');

        $course = new course(array(
            'name'     => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseidnumber');

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during program create
     *
     * @return array The data needed by the unit test method
     */
    public function programcreatefieldprovider() {
        return array(
               array('idnumber', 101, null),
               array('name', 65, null),
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
     *                            or null if not applicable
     * @dataProvider programcreatefieldprovider
     */
    public function test_programcreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        $data = array(
            'action' => 'create',
            'context' => 'curriculum',
            'name' => 'testcurriculumname',
            'idnumber' => 'testcurriculumidnumber'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during program update
     *
     * @return array The data needed by the unit test method
     */
    public function programupdatefieldprovider() {
        return array(
            array('name', 65, null),
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
     *                            or null if not applicable
     * @dataProvider programupdatefieldprovider
     */
    public function test_programupdatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $data = array(
            'action'   => 'update',
            'context'  => 'curriculum',
            'idnumber' => 'testprogramidnumber');

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during track create
     *
     * @return array The data needed by the unit test method
     */
    public function trackcreatefieldprovider() {
        return array(
            array('idnumber', 101, null),
            array('name', 256, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a track create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider trackcreatefieldprovider
     */
    public function test_trackcreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $data = array(
            'action'     => 'create',
            'context'    => 'track',
            'assignment' => 'testprogramidnumber',
            'idnumber'   => 'testtrackidnumber',
            'name'       => 'testtrackname'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during track update
     *
     * @return array The data needed by the unit test method
     */
    public function trackupdatefieldprovider() {
        return array(array('name', 256, null));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a track update action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider trackupdatefieldprovider
     */
    public function test_trackupdatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/track.class.php');

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

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during class instance create
     *
     * @return array The data needed by the unit test method
     */
    public function classcreatefieldprovider() {
        return array(array('idnumber', 101, null));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a class instance create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider classcreatefieldprovider
     */
    public function test_classinstancecreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');

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

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    // NOTE: no unit test for class update because only identifying field has a length limit.

    /**
     * Data provider for overly long user fields during userset create
     *
     * @return array The data needed by the unit test method
     */
    public function usersetcreatefieldprovider() {
        return array(
                array('name', 256, null),
                array('display', 256, null)
        );
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a userset create action
     *
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider usersetcreatefieldprovider
     */
    public function test_usersetcreatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        $data = array(
            'action'  => 'create',
            'context' => 'cluster',
            'name'    => 'testusersetname'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Data provider for overly long user fields during userset update
     *
     * @return array The data needed by the unit test method
     */
    public function usersetupdatefieldprovider() {
        return array(array('display', 256, null));
    }

    /**
     * Validate that an appropriate error is logged when max field lengths are
     * exceeded during a userset update action
     * @param string $field The identifier for the field we are testing
     * @param int $length The length we are testing at
     * @param string $customvalue A custom value to use rather than simply repeating a character,
     *                            or null if not applicable
     * @dataProvider usersetupdatefieldprovider
     */
    public function test_usersetupdatelogserrorwhenfieldstoolong($field, $length, $customvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $data = array(
            'action'  => 'update',
            'context' => 'cluster',
            'name'    => 'testusersetname'
        );

        if ($customvalue !== null) {
            $value = $customvalue;
        } else {
            $value = str_repeat('a', $length);
        }
        $data[$field] = $value;

        $maxlength = $length - 1;
        $expectederror = "{$field} value of \"{$value}\" exceeds the maximum field length of {$maxlength}.\n";
        $this->assert_data_produces_error($data, $expectederror, 'course');
    }

    /**
     * Testing of general message portion
     */

    /**
     * Validate the "user" general message
     */
    public function test_usererrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->idnumber = str_repeat('a', 256);
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuser@email.com';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('user');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_user_field_lengths($record, 'bogus');

        $expectedmessage = "User with username \"{$record->username}\", email \"{$record->email}\", idnumber";
        $expectedmessage .= " \"{$record->idnumber}\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }

    /**
     * Validate the "course description" general message
     */
    public function test_coursedescriptionerrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testcourseidnumber';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_course_field_lengths($record, 'bogus');

        $expectedmessage = "Course description with idnumber \"testcourseidnumber\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }

    /**
     * Validate the "program" general message
     */
    public function test_programerrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_program_field_lengths($record, 'bogus');

        $expectedmessage = "Program with idnumber \"testprogramidnumber\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }

    /**
     * Validate the "track" general message
     */
    public function test_trackerrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->name = str_repeat('a', 256);
        $record->idnumber = 'testtrackidnumber';
        // TODO: remove?.
        $record->assignment = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_track_field_lengths($record, 'bogus');

        $expectedmessage = "Track with idnumber \"testtrackidnumber\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }

    /**
     * Validate the "class instance" general message
     */
    public function test_classinstanceerrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = str_repeat('a', 101);
        // TODO: remove?.
        $record->assignment = 'testcourseidnumber';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_class_field_lengths($record, 'bogus');

        $expectedmessage = "Class instance with idnumber \"{$record->idnumber}\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }

    /**
     * Validate the "userset" general message
     */
    public function test_userseterrorcontainscorrectprefix() {
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->display = str_repeat('a', 256);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->mappings = rlipimport_version1elis_get_mapping('course');
        $importplugin->fslogger = new capture_fslogger(null);
        $importplugin->check_userset_field_lengths($record, 'bogus');

        $expectedmessage = "User set with name \"testusersetname\" could not be created.";
        $this->assertStringStartsWith($expectedmessage, $importplugin->fslogger->message);
    }
}