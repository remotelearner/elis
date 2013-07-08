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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/phpunit/rlip_mock_provider.class.php');

/**
 * Class for testing ELIS custom field validation messages
 */
class elis_customfield_fs_log_test extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        return array(
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'user' => 'moodle',
            RLIP_LOG_TABLE => 'block_rlip',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            course::TABLE => 'elis_program',
            curriculum::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            field_owner::TABLE => 'elis_core',
            pmclass::TABLE => 'elis_program',
            track::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program'
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
            coursetemplate::TABLE => 'elis_program',
            curriculumstudent::TABLE => 'elis_program'
        );
    }

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

        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');

        while (!feof($pointer)) {
            $error = fgets($pointer);
            if (!empty($error)) { // could be an empty new line
                if (is_array($expected_error)) {
                    $actual_error[] = substr($error, $prefix_length);
                } else {
                    $actual_error = substr($error, $prefix_length);
                }
            }
        }

        fclose($pointer);

        $this->assertEquals($expected_error, $actual_error);
    }

    /**
     * Helper method for creating a custom field
     *
     * @param int $contextlevel The context level for which to create the field
     * @param string $uitype The input control / UI type
     * @param array $otherparams Other parameters to give to the field owner
     */
    private function create_custom_field($contextlevel, $uitype, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        //category
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        //field
        $field = new field(array('categoryid' => $field_category->id,
                                 'shortname' => 'testfieldshortname',
                                 'name' => 'testfieldname',
                                 'datatype' => 'text'));
        $field->save();

        //field-contextlevel
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => $contextlevel));
        $field_contextlevel->save();

        //owner
        $owner_params = array_merge(array('control' => $uitype), $otherparams);
        field_owner::ensure_field_owner_exists($field, 'manual', $owner_params);
    }

    /**
     * Helper method for creating a test program
     *
     * @return int The id of the created program
     */
    function create_test_program() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $program = new curriculum(array('name' => 'testprogramname',
                                        'idnumber' => 'testprogramidnumber'));
        $program->save();
        return $program->id;
    }

    /**
     * Helper method for creating a test course description
     *
     * @return int The id of the created course description
     */
    function create_test_course() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();
        return $course->id;
    }

    /**
     * Data provider for validating that valid date formats do no prevent the import
     *
     * @return array data needed for testing
     */
    public function valid_date_format_provider() {
        return array(array('Jan/01/2012', array('inctime' => 0)),
                     array('Jan/01/2012', array('inctime' => 1)),
                     array('Jan/01/2012:00:00', array('inctime' => 1)));
    }

    /**
     * Creates an import field mapping record in the database
     *
     * @param string $entitytype The type of entity, such as user or course
     * @param string $standardfieldname The typical import field name
     * @param string $customfieldname The custom import field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $DB;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }

    /**
     * Validate that valid date formats in custom fields don't invalidate actions
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider valid_date_format_provider
     */
    public function test_logging_does_not_invalidate_date_customfield_formatsaccepted($value, $otherparams) {
        //TODO: consider removing once we have unit tests properly validating
        //date/time custom field imports
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->create_custom_field(CONTEXT_ELIS_USER, 'datetime', $otherparams);

        $data = array('action' => 'create',
                      'username' => 'testuserusername',
                      'email' => 'test@useremail.com',
                      'idnumber' => 'testuseridnumber',
                      'firstname' => 'testuserfirstname',
                      'lastname' => 'testuserlastname',
                      'country' => 'CA',
                      'testfieldshortname' => $value);

        //run the import
        $provider = new rlip_importprovider_fsloguser($data);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();
        ob_end_clean();

        //validate that a user is actually created
        $this->assertEquals(1, $DB->count_records(user::TABLE));
    }

    /**
     * Data provider containing data for the different input control types and
     * their associated validation messages
     *
     * @return array Test method parameter data, int the expected format
     */
    public function type_error_provider() {
        return array(array('checkbox',
                           'nonboolean',
                           '"nonboolean" is not one of the available options for checkbox custom field "customtestfieldshortname" (0, 1).',
                           array()),
                     array('menu',
                           'unavailable',
                           '"unavailable" is not one of the available options for menu of choices custom field "customtestfieldshortname".',
                           array('options' => "1\n2\n3")),
                     array('text',
                           str_repeat('a', 41),
                           'Text input custom field "customtestfieldshortname" value of "'.str_repeat('a', 41).'" exceeds the maximum field length of 40.',
                           array('maxlength' => 40)),
                     array('password',
                           str_repeat('a', 41),
                           'Password custom field "customtestfieldshortname" value of "'.str_repeat('a', 41).'" exceeds the maximum field length of 40.',
                           array('maxlength' => 40)),
                     array('datetime',
                           'nondate',
                           '"nondate" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'Jan/01/2012:00:00',
                           '"Jan/01/2012:00:00" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'Jan/01.5/2012',
                           '"Jan/01.5/2012" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'Jan/01/2012.5',
                           '"Jan/01/2012.5" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'Jan/01/99999',
                           '"Jan/01/99999" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'Jan/00/99999',
                           '"Jan/00/99999" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           '01-01-2012',
                           '"01-01-2012" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           '2012.01.01',
                           '"2012.01.01" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           '2012.01.01:00:00',
                           '"2012.01.01:00:00" is not a valid date in MMM/DD/YYYY format for date custom field "customtestfieldshortname".',
                           array('inctime' => 0)),
                     array('datetime',
                           'nondate',
                           '"nondate" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)),
                     array('datetime',
                           'Jan/01/2012:00.5:00',
                           '"Jan/01/2012:00.5:00" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)),
                     array('datetime',
                           'Jan/01/2012:25:00',
                           '"Jan/01/2012:25:00" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)),
                     array('datetime',
                           'Jan/01/2012:00:00.5',
                           '"Jan/01/2012:00:00.5" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)),
                     array('datetime',
                           'Jan/01/2012:05',
                           '"Jan/01/2012:05" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)),
                     array('datetime',
                           'Jan/01/2012:00:61',
                           '"Jan/01/2012:00:61" is not a valid date / time in MMM/DD/YYYY or MMM/DD/YYYY:HH:MM format for date / time custom field "customtestfieldshortname".',
                           array('inctime' => 1)));
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on user create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_user_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_USER, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('user', 'testfieldshortname', 'customtestfieldshortname');

        $data = array('action' => 'create',
                      'username' => 'testuserusername',
                      'email' => 'test@useremail.com',
                      'idnumber' => 'testuseridnumber',
                      'firstname' => 'testuserfirstname',
                      'lastname' => 'testuserlastname',
                      'country' => 'CA',
                      'customtestfieldshortname' => $value);

        $message = '[user.csv line 2] User with username "testuserusername", email "test@useremail.com", '.
                   'idnumber "testuseridnumber" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'user');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on user update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_user_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->create_custom_field(CONTEXT_ELIS_USER, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('user', 'testfieldshortname', 'customtestfieldshortname');

        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        $data = array('action' => 'update',
                      'username' => 'testuserusername',
                      'email' => 'test@useremail.com',
                      'idnumber' => 'testuseridnumber',
                      'customtestfieldshortname' => $value);

        $message = '[user.csv line 2] User with username "testuserusername", email "test@useremail.com", '.
                   'idnumber "testuseridnumber" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'user');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on program create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_program_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_PROGRAM, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $data = array('action' => 'create',
                      'context' => 'curriculum',
                      'name' => 'testprogramname',
                      'idnumber' => 'testprogramidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Program with idnumber "testprogramidnumber" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on program update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_program_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $this->create_custom_field(CONTEXT_ELIS_PROGRAM, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $program = new curriculum(array('name' => 'testprogramname',
                                        'idnumber' => 'testprogramidnumber'));
        $program->save();

        $data = array('action' => 'update',
                      'context' => 'curriculum',
                      'idnumber' => 'testprogramidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Program with idnumber "testprogramidnumber" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on track create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_track_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_TRACK, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $this->create_test_program();

        $data = array('action' => 'create',
                      'context' => 'track',
                      'assignment' => 'testprogramidnumber',
                      'name' => 'testtrackname',
                      'idnumber' => 'testtrackidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Track with idnumber "testtrackidnumber" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on track update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_track_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->create_custom_field(CONTEXT_ELIS_TRACK, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $programid = $this->create_test_program();

        $track = new track(array('curid' => $programid,
                                 'name' => 'testtrackname',
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $data = array('action' => 'update',
                      'context' => 'track',
                      'idnumber' => 'testtrackidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Track with idnumber "testtrackidnumber" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on course description create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_course_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_COURSE, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $data = array('action' => 'create',
                      'context' => 'course',
                      'name' => 'testcoursename',
                      'idnumber' => 'testcourseidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Course description with idnumber "testcourseidnumber" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on course description update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_course_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $this->create_custom_field(CONTEXT_ELIS_COURSE, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $data = array('action' => 'update',
                      'context' => 'course',
                      'idnumber' => 'testcourseidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Course description with idnumber "testcourseidnumber" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on class instance create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_class_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_CLASS, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $this->create_test_course();

        $data = array('action' => 'create',
                      'context' => 'class',
                      'assignment' => 'testcourseidnumber',
                      'name' => 'testclassname',
                      'idnumber' => 'testclassidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Class instance with idnumber "testclassidnumber" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on class instance update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_class_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $this->create_custom_field(CONTEXT_ELIS_CLASS, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $courseid = $this->create_test_course();

        $class = new pmclass(array('courseid' => $courseid,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $data = array('action' => 'update',
                      'context' => 'class',
                      'idnumber' => 'testclassidnumber',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] Class instance with idnumber "testclassidnumber" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on userset create
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_userset_create_customfield_messages($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->create_custom_field(CONTEXT_ELIS_USERSET, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $data = array('action' => 'create',
                      'context' => 'cluster',
                      'name' => 'testusersetname',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] User set with name "testusersetname" could not be created. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that the provided custom field type and value produce the
     * specified error message on user set update
     *
     * @param string $uitype The input control / UI type
     * @param string $value The value to use for the custom field
     * @param string $message The expected error message
     * @param array $otherparams Other parameters to give to the field owner
     * @dataProvider type_error_provider
     */
    public function test_userset_update_customfield_message($uitype, $value, $message, $otherparams) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->create_custom_field(CONTEXT_ELIS_USERSET, $uitype, $otherparams);
        //create mapping record
        $this->create_mapping_record('course', 'testfieldshortname', 'customtestfieldshortname');

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $data = array('action' => 'update',
                      'context' => 'cluster',
                      'name' => 'testusersetname',
                      'customtestfieldshortname' => $value);

        $message = '[course.csv line 2] User set with name "testusersetname" could not be updated. '.$message."\n";
        $this->assert_data_produces_error($data, $message, 'course');
    }

    /**
     * Validate that multi-value custom fields report the first error found
     */
    public function test_multivalue_field_reports_first_error() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        $this->create_custom_field(CONTEXT_ELIS_USER, 'menu', array('options' => '1'));
        //create mapping record
        $this->create_mapping_record('user', 'testfieldshortname', 'customtestfieldshortname');
        $DB->execute("UPDATE {".field::TABLE."}
                     SET multivalued = 1");

        $data = array('action' => 'create',
                      'username' => 'testuserusername',
                      'email' => 'test@useremail.com',
                      'idnumber' => 'testuseridnumber',
                      'firstname' => 'testuserfirstname',
                      'lastname' => 'testuserlastname',
                      'country' => 'CA',
                      'customtestfieldshortname' => '1/2/3');

        $message = '[user.csv line 2] User with username "testuserusername", email "test@useremail.com", '.
                   'idnumber "testuseridnumber" could not be created. "2" is not one of the available options for menu of choices custom field "customtestfieldshortname".'."\n";
        $this->assert_data_produces_error($data, $message, 'user');
    }
}
