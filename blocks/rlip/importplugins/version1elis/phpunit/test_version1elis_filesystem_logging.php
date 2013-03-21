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
 * @subpackage rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(__FILE__) .'/../../../../../config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/lib/phpunittestlib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/file_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/delay_after_three.class.php');

/**
 * Overlay database that allows for the handling of temporary tables as well
 * as some course-specific optimizations
 */
class overlay_course_database_fs extends overlay_database {

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool true
     * @throws dml_exception if error
     */
    public function change_database_structure($sql) {
        if (strpos($sql, 'CREATE TEMPORARY TABLE ') === 0) {
            //creating a temporary table, so make it an overlay table

            //find the table name
            $start_pos = strlen('CREATE TEMPORARY TABLE ');
            $length = strpos($sql, '(') - $start_pos;
            $tablename = trim(substr($sql, $start_pos, $length));
            //don't use prefix when storing
            $tablename = substr($tablename, strlen($this->overlayprefix));

            //set it up as an overlay table
            $this->overlaytables[$tablename] = 'moodle';
            $this->pattern = '/{('.implode('|', array_keys($this->overlaytables)).')}/';
        }

        // FIXME: or should we just do nothing?
        return $this->basedb->change_database_structure($sql);
    }

    /**
     * Returns detailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        //determine if this is an overlay table
        $is_overlay_table = array_key_exists($table, $this->overlaytables);

        if ($is_overlay_table) {
            //temporarily set the prefix to the overlay prefix
            $cacheprefix = $this->basedb->prefix;
            $this->basedb->prefix = $this->overlayprefix; // HACK!!!
        }

        $result = $this->basedb->get_columns($table, $usecache);

        if ($is_overlay_table) {
            //restore proper prefix
            $this->basedb->prefix = $cacheprefix;
        }

        return $result;
    }

    /**
     * Clean up the temporary tables.  You'd think that if this method was
     * called dispose, then the cleanup would happen automatically, but it
     * doesn't.
     */
    public function cleanup() {
        $manager = $this->get_manager();
        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);
            // FIXME: when http://bugs.mysql.com/bug.php?id=10327 gets fixed,
            // we can switch this back to drop_temp_table
            if ($table === null) {
                //most likely a temporary table
                try {
                    //attempt to drop the temporary table
                    $table = new xmldb_table($tablename);
                    $manager->drop_table($table);
                } catch (Exception $e) {
                    //temporary table was already dropped
                }
            } else {
                //structure was defined in xml, so drop normal table
                $manager->drop_table($table);
            }
        }
    }

    /**
     * Empty out all the overlay tables.
     */
    public function reset_overlay_tables() {
        $manager = $this->get_manager();

        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);

            if ($table === null) {
                $table = new xmldb_table($tablename);
                try {
                    $manager->drop_table($table);
                } catch (Exception $e) {

                }
                unset($this->overlaytables[$tablename]);
            }
        }

        parent::reset_overlay_tables();
    }

    /**
     * Empty out all the overlay tables.
     */
    /*
    public function reset_overlay_tables() {
        //do nothing
    }
    */
}

class version1elisFilesystemLoggingTest extends rlip_test {

    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $file = get_plugin_directory('rlipimport', 'version1elis') .'/lib.php';
        require_once($file);

        $tables = array(RLIP_LOG_TABLE => 'block_rlip',
                     'user' => 'moodle',
                     'config_plugins' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'groups_members' => 'moodle',
                     'block_positions' => 'moodle',
                     'events_queue_handlers' => 'moodle',
                     'events_queue' => 'moodle',
                     'grade_categories' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'groups' => 'moodle',
                     'grade_items' => 'moodle',
                     'context' => 'moodle',
                     'config' => 'moodle',
                     'backup_controllers' => 'moodle',
                     'backup_courses' => 'moodle',
                     'enrol' => 'moodle',
                     //needed for course delete to prevent errors / warnings
                     'course_modules' => 'moodle',
                     'forum' => 'mod_forum',
                     RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
                     'elis_scheduled_tasks' => 'elis_core',
                     RLIP_SCHEDULE_TABLE => 'block_rlip',
                     RLIP_LOG_TABLE => 'block_rlip',
                     'user' => 'moodle',
                     'user_info_category' => 'moodle',
                     'user_info_field' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'message_working' => 'moodle');

        // Detect if we are running this test on a site with the ELIS PM system in place
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/pmclass.class.php'));
            require_once(elispm::lib('data/course.class.php'));
            require_once(elispm::lib('data/curriculum.class.php'));
            require_once(elispm::lib('data/track.class.php'));
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/userset.class.php'));
            require_once(elispm::lib('data/usermoodle.class.php'));

            $tables[user::TABLE] = 'elis_program';
            $tables[usermoodle::TABLE] = 'elis_program';
            $tables[curriculum::TABLE] = 'elis_program';
            $tables[course::TABLE] = 'elis_program';
            $tables[pmclass::TABLE] = 'elis_program';
            $tables[track::TABLE] = 'elis_program';
            $tables[userset::TABLE] = 'elis_program';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $DB;
        $tables = array('block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'log' => 'moodle',
                     'message'            => 'moodle',
                     'message_read'       => 'moodle',
                     'message_working'    => 'moodle',
                     'cohort_members' => 'moodle',
                     'user_preferences' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'files' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'course_completion_criteria' => 'moodle',
                     'course_completion_aggr_methd' => 'moodle',
                     'course_completions' => 'moodle',
                     'course_completion_crit_compl' => 'moodle',
                     '_categories_history' => 'moodle',
                     //'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_categories_history' => 'moodle',
                     'grade_settings' => 'moodle',
                     'grade_letters' => 'moodle',
                     'course_modules_completion' => 'moodle',
                     'course_modules_availability' => 'moodle',
                     'feedback_items' => 'moodle',
                     'feedback_template' => 'moodle',
                     'course_modules' => 'moodle',
                     'event' => 'moodle',
                     'course_display' => 'moodle',
                     'backup_log' => 'moodle',
                     'external_tokens' => 'moodle',
                     'forum' => 'mod_forum',
                     'forum_subscriptions' => 'mod_forum',
                     'forum_read' => 'mod_forum',
                     'external_services_users' => 'moodle',
                     'grade_grades' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     'external_services_users' => 'moodle',
                     'quiz_attempts' => 'mod_quiz',
                     'quiz_grades' => 'mod_quiz',
                     'quiz_question_instances' => 'mod_quiz',
                     'quiz_feedback' => 'mod_quiz',
                     'quiz' => 'mod_quiz',
                     'url' => 'moodle',
                     'assignment' => 'moodle',
                     'assignment_submissions' => 'moodle',
                     'forum_track_prefs' => 'moodle',
                     'sessions' => 'moodle');

        if ($DB->record_exists("block", array("name" => "curr_admin"))) {
            $tables['crlm_user_moodle'] = 'elis_program';
            $tables['crlm_user'] = 'elis_program';
        }
        return $tables;
    }

    protected $backupGlobalsBlacklist = array('DB');

/*
    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_course_database_fs($DB, static::get_overlay_tables(), static::get_ignored_tables());
        //self::$overlaydb = new overlay_database($DB, static::get_overlay_tables(), static::get_ignored_tables());

        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
    }
*/
    /**
     * Validates that the supplied data produces the expected error
     *
     * @param array  $data The import data to process
     * @param string $expected_error The error we are expecting (message only)
     * @param user   $entitytype One of 'user', 'course', 'enrolment'
     * @param string $importfilename  name of import file
     */
    protected function assert_data_produces_error($data, $expected_error, $entitytype, $importfilename = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //set the log file location
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;
        self::cleanup_log_files();

        //run the import
        $classname = "rlip_importprovider_fslog{$entitytype}";
        $provider = new $classname($data, $importfilename);
        $instance = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, true);
        //suppress output for now
        ob_start();
        $instance->run();
        ob_end_clean();

        //validate that a log file was created
        //get first summary record - at times, multiple summary records are created and this handles that problem
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        //get logfile name
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1';
        $format = get_string('logfile_timestamp','block_rlip');
        $testfilename = $filepath.'/'.$plugin_type.'_version1elis_manual_'.$entitytype.'_'.userdate($starttime, $format).'.log';
        //get most recent logfile

        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename), "\n Can't find logfile: {$filename} for \n{$testfilename}");

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
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Creates a test user
     *
     * @param string $username The user's username
     * @param string $email The user's email
     * @param string $idnumber The user's idnumber
     *
     * @return int The database record id of the created user
     */
    private function create_test_user($username = 'rlipusername', $email = 'rlipuser@rlipdomain.com',
                                      $idnumber = 'rlipidnumber') {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = $username;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->email = $email;
        $user->password = 'Rlippassword!1234';
        $user->idnumber = $idnumber;

        return user_create_user($user);
    }

    /**
     * Creates a test course, including the category it belongs to
     *
     * @return int The database record id of the created course
     */
    private function create_test_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //create the category
        $category = new stdClass;
        $category->name = 'rlipname';
        $categoryid = $DB->insert_record('course_categories', $category);
        $context_coursecat = get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //create the course
        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $categoryid;

        $course = create_course($course);
        get_context_instance(CONTEXT_COURSE, $course->id);

        return $course->id;
    }

    /**
     * Creates a test role, assignable at all necessary context levels
     *
     * @param string $fullname The new role's fullname
     * @param string $shortname The new role's shortname
     * @param string $description The new role's description
     * @return int The database record id of the created course
     */
    private function create_test_role($fullname = 'rlipfullname', $shortname = 'rlipshortname',
                                      $description = 'rlipdescription') {
        //create the role
        $roleid = create_role($fullname, $shortname, $description);

        //make it assignable at all necessary contexts
        $contexts = array(CONTEXT_COURSE,
                          CONTEXT_COURSECAT,
                          CONTEXT_USER,
                          CONTEXT_SYSTEM);
        set_role_contextlevels($roleid, $contexts);

        return $roleid;
    }

    /**
     * Creates the system and site course context, as well as the site course
     * record
     */
    private function create_contexts_and_site_course() {
        global $DB;

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }
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

        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $record);
    }

    /**
     * Obtains a list of strings that identify specific user fields using field "value" syntax
     *
     * @param array $data The import data for the current line
     * @param string $prefix Prefix used in field mappings
     * @return array List of identifying strings
     */
    private function get_user_identifiers($data, $prefix = '') {
        $identifiers = array();
        if (isset($data[$prefix.'username'])) {
            $value = $data[$prefix.'username'];
            $identifiers[] = "username \"{$value}\"";
        }
        if (isset($data[$prefix.'email'])) {
            $value = $data[$prefix.'email'];
            $identifiers[] = "email \"{$value}\"";
        }
        if (isset($data[$prefix.'idnumber'])) {
            $value = $data[$prefix.'idnumber'];
            $identifiers[] = "idnumber \"{$value}\"";
        }
        return $identifiers;
    }

    /**
     * Data provider function for invalid user info in role assignments
     *
     * @return array The array of data to use in test cases
     */
    public function roleAssignmentInvalidUserProvider() {
        $data = array();

        //invalid username
        $username_data = array('customusername' => 'bogus');
        $username_message = "[enrolment.csv line 2] customusername value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($username_data, $username_message);

        //invalid email
        $email_data = array('customemail' => 'bogus@bogus.com');
        $email_message = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\" does not refer to a valid user.\n";
        $data[] = array($email_data, $email_message);

        //invalid idnumber
        $idnumber_data = array('customidnumber' => 'bogus');
        $idnumber_message = "[enrolment.csv line 2] customidnumber value of \"bogus\" does not refer to a valid user.\n";
        $data[] = array($idnumber_data, $idnumber_message);

        //invalid combination of username, email
        $username_email_data = array('customusername' => 'bogus',
                                     'customemail' => 'bogus@bogus.com');
        $username_email_message = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\" do not refer to a valid user.\n";
        $data[] = array($username_email_data, $username_email_message);

        //invalid combination of username, idnumber
        $username_idnumber_data = array('customusername' => 'bogus',
                                        'customidnumber' => 'bogus');
        $username_idnumber_message = "[enrolment.csv line 2] customusername value of \"bogus\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($username_idnumber_data, $username_idnumber_message);

        //invalid combination of email, idnumber
        $email_idnumber_data = array('customemail' => 'bogus@bogus.com',
                                     'customidnumber' => 'bogus');
        $email_idnumber_message = "[enrolment.csv line 2] customemail value of \"bogus@bogus.com\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($email_idnumber_data, $email_idnumber_message);

        //invalid combination of username, email, idnumber
        $all_fields_data = array('customusername' => 'bogus',
                                 'customemail' => 'bogus@bogus.com',
                                 'customidnumber' => 'bogus');
        $all_fields_message = "[enrolment.csv line 2] customusername value of \"bogus\", customemail value of \"bogus@bogus.com\", customidnumber value of \"bogus\" do not refer to a valid user.\n";
        $data[] = array($all_fields_data, $all_fields_message);

        return $data;
    }

    /**
     * Data provider method for logging invalid entity messages
     *
     * @return array An array containing information about the context
     *               "shortname" and display name
     */
    public function roleAssignmentInvalidEntityProvider() {
        return array(array('course', 'course'),
                     array('coursecat', 'course category'),
                     array('user', 'user'));
    }

    /**
     * Data provider function that providers all combinations of identifying
     * user fields
     *
     * @return array Data in format expected by phpunit
     */
    public function roleAssignmentAmbiguousGroupNameUserProvider() {
        $username = 'rlipusername';
        $email = 'rlipuser@rlipdomain.com';
        $idnumber = 'rlipidnumber';

        return array(array(array('username' => $username)),
                     array(array('email' => $email)),
                     array(array('idnumber' => $idnumber)),
                     array(array('username' => $username,
                                 'email' => $email)),
                     array(array('username' => $username,
                                 'idnumber' => $idnumber)),
                     array(array('email' => $email,
                                 'idnumber' => $idnumber)),
                     array(array('username' => $username,
                                 'email' => $email,
                                 'idnumber' => $idnumber)));
    }

    /**
     * Data provider method, providing identifying user fields as well as
     * text to describe those fields, with every combination of identifying
     * user fields provided
     *
     * @return array Array of data, with each elements containing a data set
     *               and a descriptive string
     */
    public function userDescriptorProvider() {
        $data = array();

        //username
        $username_data = array('username' => 'rlipusername');
        $username_descriptor = "username \"rlipusername\"";
        $data[] = array($username_data, $username_descriptor);

        //email
        $email_data = array('email' => 'rlipuser@rlipdomain.com');
        $email_descriptor = "email \"rlipuser@rlipdomain.com\"";
        $data[] = array($email_data, $email_descriptor);

        //idnumber
        $idnumber_data = array('idnumber' => 'rlipidnumber');
        $idnumber_descriptor = "idnumber \"rlipidnumber\"";
        $data[] = array($idnumber_data, $idnumber_descriptor);

        //username, email
        $username_email_data = array('username' => 'rlipusername',
                                     'email' => 'rlipuser@rlipdomain.com');
        $username_email_descriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\"";
        $data[] = array($username_email_data, $username_email_descriptor);

        //username, idnumber
        $username_idnumber_data = array('username' => 'rlipusername',
                                        'idnumber' => 'rlipidnumber');
        $username_idnumber_descriptor = "username \"rlipusername\", idnumber \"rlipidnumber\"";
        $data[] = array($username_idnumber_data, $username_idnumber_descriptor);

        //email, idnumber
        $email_idnumber_data = array('email' => 'rlipuser@rlipdomain.com',
                                     'idnumber' => 'rlipidnumber');
        $email_idnumber_descriptor = "email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($email_idnumber_data, $email_idnumber_descriptor);

        //username, email, idnumber
        $all_fields_data = array('username' => 'bogus',
                                 'email' => 'bogus@bogus.com',
                                 'idnumber' => 'bogus');
        $all_fields_descriptor = "username \"rlipusername\", email \"rlipuser@rlipdomain.com\", idnumber \"rlipidnumber\"";
        $data[] = array($all_fields_data, $all_fields_descriptor);

        return $data;
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @param string $param1 Extra parameter, used for select options
     * @param string $defaultdata Default value
     * @return int The id of the created profile field
     */
    private function create_profile_field($name, $datatype, $categoryid, $param1 = NULL, $defaultdata = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        //core fields
        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== NULL) {
            //set the select options
            $data->param1 = $param1;
        }

        if ($defaultdata !== NULL) {
            //set the default value
            $data->defaultdata = $defaultdata;
        }

        $field->define_save($data);
        return $data->id;
    }

    /**
     * Validate that the correct error message is logged when an import runs
     * too long
     */
    public function testVersion1ELISimportLogsRuntimeError() {
        global $CFG, $DB;

        //set the file path to the dataroot
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . RLIP_DEFAULT_LOG_PATH;
        set_config('logfilelocation', '', 'rlipimport_version1elis');

        //set up a "user" import provider, using a single fixed file
        $file_name = 'userfile2.csv';
        // File WILL BE DELETED after import so must copy to moodledata area
        // Note: file_path now relative to moodledata ($CFG->dataroot)
        $file_path = '/block_rlip_phpunit/';
        $testdir = $CFG->dataroot . $file_path;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__) ."/{$file_name}", $testdir . $file_name);
        $provider = new rlip_importprovider_file_delay($CFG->dataroot . $file_path . $file_name, 'user');

        //run the import
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, $manual);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // maxruntime 1 sec
        $ui = ob_get_contents(); // TBD: test this UI string
        ob_end_clean();

        //expected error
        $a = new stdClass;
        $a->entity = $result->entity;
        $a->recordsprocessed = $result->linenumber - 1;
        $a->totalrecords = $result->filelines - 1;
        $expected_error = get_string('manualimportexceedstimelimit_b', 'block_rlip', $a)."\n";

        //validate that a log file was created
        $plugin_type = 'import';
        $plugin = 'rlipimport_version1elis';
        $format = get_string('logfile_timestamp','block_rlip');
        $entity = 'user';
        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $filepath . '/' . $plugin_type . '_version1elis_manual_' . $entity . '_' .
                        userdate($starttime, $format) . '.log';

        $filename = self::get_current_logfile($testfilename);
        //echo "testVersion1ImportLogsRuntimeError(): logfile ?=> {$filename}\n";
        $this->assertTrue(file_exists($filename));
        //fetch log line
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            //no line found
            $this->assertEquals(0, 1);
        }

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);

        //clean-up data file & test dir
        @unlink($testdir . $file_name);
        @rmdir($testdir);
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the import runs for too long on a manual run
     */
    public function testVersion1ELISmanualImportLogsRuntimeFilesystemError() {
        global $CFG, $DB;

        //set up the log file location
        set_config('logfilelocation', '', 'rlipimport_version1elis');

        //our import data
        $data = array(array('action', 'username', 'password', 'firstname', 'lastname', 'email', 'city', 'country'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'),
                      array('create', 'testuser', 'Password!0', 'firstname', 'lastname', 'a@b.c', 'test', 'CA'));

        //import provider that creates an instance of a file plugin that delays two seconds
        //between reading the third and fourth entry
        $provider = new rlip_importprovider_delay_after_three_users($data);
        $manual = true;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis', $provider, NULL, $manual);

        //we should run out of time after processing the second real entry
        ob_start();
        //using three seconds to allow for one slow read when counting lines
        $importplugin->run(0, 0, 3);
        ob_end_clean();

        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        $filename = '';
        foreach ($records as $record) {
            //$starttime = $record->starttime;
            $filename = $record->logpath;
            break;
        }

        //validate that the right log file was created
        $this->assertTrue(file_exists($filename));

        //obtain log file lines
        $contents = file_get_contents($filename);
        $contents = explode("\n", $contents);

        //validate line count, accounting for blank line at end
        $this->assertEquals(count($contents), 4);

        //obtain the line we care about
        $line = $contents[2];
        $expected_error = 'Import processing of entity \'user\' partially processed due to time restrictions. '.
                          'Processed 2 of 3 total records.';

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);
    }


    /**
     * Data provider method, providing user fields with their maximum lengths
     *
     * @return array Mapping of user fields to their maximum lengths
     */
    public function userMaxFieldLengthProvider() {
        return array(array('username', 100),
                     array('firstname', 100),
                     array('lastname', 100),
                     array('email', 100),
                     array('city', 120),
                     array('idnumber', 255),
                     array('institution', 40),
                     array('department', 30));
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the user import file is missing required fields
     */
    public function testVersion1ELISuserImportLogsMissingColumns() {
        $data = array('action'    => 'create',
                      'firstname' => 'testfirstname',
                      'lastname'  => 'testlastname',
                      'password'  => 'Testpassword!0',
                      'city'      => 'Waterloo',
                      'country'   => 'CA',
                      'lang'      => 'en'
                 );

        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array('action'    => 'update',
                      'firstname' => 'testfirstname',
                      'lastname'  => 'testlastname',
                      'password'  => 'Testpassword!0',
                      'city'      => 'Waterloo',
                      'country'   => 'CA',
                      'lang'      => 'en'
                 );

        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'user');

        $data = array('action'    => 'delete',
                      'firstname' => 'testfirstname',
                      'lastname'  => 'testlastname',
                      'password'  => 'Testpassword!0',
                      'city'      => 'Waterloo',
                      'country'   => 'CA',
                      'lang'      => 'en'
                 );

        $expected_error = "[user.csv line 1] Import file user.csv was not processed because one of the following columns is required but all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'user');
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the course(ELIS entity) import file is missing required fields
     */
    public function testVersion1ELISentityImportLogsMissingColumns() {
        // create
        $data = array('action'   => 'create',
                      'context'  => 'curriculum',
                      'name'     => 'ProgramName'
                 );

        $expected_error = "[course.csv line 2] Program could not be created. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'create',
                      'context' => 'track',
                      'name'    => 'TrackName'
                 );

        $expected_error = "[course.csv line 2] Track could not be created. Required fields assignment, idnumber are unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'create',
                      'context' => 'course',
                      'name'    => 'CourseDescriptionName'
                 );

        $expected_error = "[course.csv line 2] Course description could not be created. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'create',
                      'context' => 'class',
                      'name'    => 'ClassInstanceName'
                 );

        $expected_error = "[course.csv line 2] Class instance could not be created. Required fields assignment, idnumber are unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'create',
                      'context' => 'cluster'
                 );

        $expected_error = "[course.csv line 2] User set could not be created. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        // update
        $data = array('action'  => 'update',
                      'context' => 'curriculum',
                      'name'    => 'NewProgramName'
                 );

        $expected_error = "[course.csv line 2] Program could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'update',
                      'context' => 'track',
                      'name'    => 'NewTrackName'
                 );

        $expected_error = "[course.csv line 2] Track could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'update',
                      'context' => 'course',
                      'name'    => 'NewCourseDescriptionName'
                 );

        $expected_error = "[course.csv line 2] Course description could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'update',
                      'context' => 'class',
                      'name'    => 'NewClassInstanceName'
                 );

        $expected_error = "[course.csv line 2] Class instance could not be updated. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'update',
                      'context' => 'cluster'
                 );

        $expected_error = "[course.csv line 2] User set could not be updated. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        // delete
        $data = array('action'  => 'delete',
                      'context' => 'curriculum',
                      'name'    => 'ProgramName'
                 );

        $expected_error = "[course.csv line 2] Program could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'delete',
                      'context' => 'track',
                      'name'    => 'TrackName'
                 );

        $expected_error = "[course.csv line 2] Track could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'delete',
                      'context' => 'course',
                      'name'    => 'CourseDescriptionName'
                 );

        $expected_error = "[course.csv line 2] Course description could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'delete',
                      'context' => 'class',
                      'name'    => 'ClassInstanceName'
                 );

        $expected_error = "[course.csv line 2] Class instance could not be deleted. Required field idnumber is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');

        $data = array('action'  => 'delete',
                      'context' => 'cluster'
                 );

        $expected_error = "[course.csv line 2] User set could not be deleted. Required field name is unspecified or empty.\n";

        $this->assert_data_produces_error($data, $expected_error, 'course');
    }

    /**
     * Validate that the verison 1 import plugin logs the exact message required to the
     * file system when the enrolment import file is missing required fields
     */
    public function testVersion1ELISenrolmentImportLogsMissingColumns() {
        // create
        $data = array('action'        => 'create',
                      'context'       => 'curriculum_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'track_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'course_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'class_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'cluster_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        // update
        $data = array('action'        => 'create',
                      'context'       => 'class_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'track_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'course_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'class_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'create',
                      'context'       => 'cluster_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        // update - only allowed for class context
        $data = array('action'        => 'update',
                      'context'       => 'class_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        // delete
        $data = array('action'        => 'delete',
                      'context'       => 'curriculum_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'delete',
                      'context'       => 'track_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'delete',
                      'context'       => 'course_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'delete',
                      'context'       => 'class_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');

        $data = array('action'        => 'delete',
                      'context'       => 'cluster_1',
                      'enrolmenttime' => 'Jul/17/2012:12:00'
                 );

        $expected_error = "[enrolment.csv line 1] Import file enrolment.csv was not processed because one of the following columns is required but all are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.\n";

        $this->assert_data_produces_error($data, $expected_error, 'enrolment');
    }

}
