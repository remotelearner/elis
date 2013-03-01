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
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
//require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

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

/**
 * Class for version 1 user import correctness
 */
class version1UserImportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'user' => 'moodle',
            'context' => 'moodle',
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'user_info_field' => 'moodle',
            'user_info_category' => 'moodle',
            'user_info_data' => 'moodle',
            'user_enrolments' => 'moodle',
            'cohort_members' => 'moodle',
            'groups_members' => 'moodle',
            'user_preferences' => 'moodle',
            'user_lastaccess' => 'moodle',
            'block_positions' => 'moodle',
            'block_instances' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'comments' => 'moodle',
            'rating' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            'cache_flags' => 'moodle',
            'events_queue' => 'moodle',
            'groups' => 'moodle',
            'course' => 'moodle',
            'course_format_options' => 'moodle',
            'course_sections' => 'moodle',
            'course_categories' => 'moodle',
            'enrol' => 'moodle',
            'external_tokens' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'message' => 'moodle',
            'message_read' => 'moodle',
            'message_working' => 'moodle',
            'grade_items' => 'moodle',
            'grade_items_history' => 'moodle',
            'grade_grades' => 'moodle',
            'grade_grades_history' => 'moodle',
            'grade_categories' => 'moodle',
            'grade_categories_history' => 'moodle',
            'tag' => 'moodle',
            'tag_instance' => 'moodle',
            'tag_correlation' => 'moodle',
            'elis_field_categories' => 'elis_core',
            'elis_field_category_contexts' => 'elis_core',
            'elis_field' => 'elis_core',
            'elis_field_contextlevels' => 'elis_core',
            'elis_field_owner' => 'elis_core',
            'elis_field_data_text' => 'elis_core',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
            //not writing to this one but prevent events from
            //being fired during testing
            'events_queue_handlers' => 'moodle'
        );

        // Detect if we are running this test on a site with the ELIS PM system in place
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/usermoodle.class.php'));
            require_once(elispm::lib('data/clusterassignment.class.php'));
            require_once(elispm::lib('data/clustercurriculum.class.php'));
            require_once(elispm::lib('data/clustertrack.class.php'));
            require_once(elispm::lib('data/curriculumstudent.class.php'));
            require_once(elispm::lib('data/track.class.php'));
            require_once(elispm::lib('data/usertrack.class.php'));
            require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));

            $tables[clusterassignment::TABLE] = 'elis_program';
            $tables[clustercurriculum::TABLE] = 'elis_program';
            $tables[clustertrack::TABLE] = 'elis_program';
            $tables[curriculumstudent::TABLE] = 'elis_program';
            $tables[track::TABLE] = 'elis_program';
            $tables[trackassignment::TABLE] = 'elis_program';
            $tables[user::TABLE] = 'elis_program';
            $tables[usermoodle::TABLE] = 'elis_program';
            $tables[userset_profile::TABLE] = 'elis_program';
            $tables[usertrack::TABLE] = 'elis_program';
        }

        if ($DB->get_manager()->table_exists('external_services_users')) {
            $tables['external_services_users'] = 'moodle';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array('log' => 'moodle',
                     RLIP_LOG_TABLE => 'block_rlip',
                     'events_handlers' => 'moodle',
                     //'external_tokens' => 'moodle',
                     //'external_services_users' => 'moodle',
                     'files' => 'moodle',
                     'sessions' => 'moodle',
                     'forum_subscriptions' => 'moodle',
                     'forum_track_prefs' => 'moodle',
                     'forum_read' => 'moodle');
    }

    /**
     * Helper function to get the core fields for a sample user
     *
     * @return array The user data
     */
    private function get_core_user_data() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!1234',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        return $data;
    }

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $extradata Extra fields to set for the new user
     */
    private function run_core_user_import($extradata, $use_default_data = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_user_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @param string $param1 Extra parameter, used for select options
     */
    private function create_profile_field($name, $datatype, $categoryid, $param1 = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== NULL) {
            $data->param1 = $param1;
        }

        $field->define_save($data);
    }

    /**
     * Asserts, using PHPunit, that the test user does not exist
     */
    private function assert_core_user_does_not_exist() {
        global $CFG, $DB;

        $exists = $DB->record_exists('user', array('username' => 'rlipusername',
                                                   'mnethostid' => $CFG->mnet_localhost_id));
        $this->assertEquals($exists, false);
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
        $tmp = '';
        if (!$exists) {
            ob_start();
            var_dump($params);
            var_dump($DB->get_records($table));
            $tmp = ob_get_contents();
            ob_end_clean();
        }
        $this->assertTrue($exists, "Error: record should exist in {$table} => params/DB = {$tmp}\n");
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private function init_contexts_and_site_course() {
        global $DB;

        $siteid = SITEID ? SITEID : 1; // TBD
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, $siteid));
        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => $siteid))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }
        build_context_path();
    }

    /**
     * Validate that the version 1 plugin supports user actions
     */
    public function testVersion1ImportSupportsUserActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'user');

        $this->assertEquals($supports, array('create', 'add', 'update', 'delete', 'disable'));
    }

    /**
     * Validate that the version 1 plugin supports user creation
     */
    public function testVersion1ImportSupportsUserCreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_create');
        $required_fields = array('username',
                                 'password',
                                 'firstname',
                                 'lastname',
                                 'email',
                                 'city',
                                 'country');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports user addition
     */
    public function testVersion1ImportSupportsUserAdd() {
        //note: this is the same as user creation, but makes up for a weirdness
        //in IP for 1.9
        $supports = plugin_supports('rlipimport', 'version1', 'user_add');
        $required_fields = array('username',
                                 'password',
                                 'firstname',
                                 'lastname',
                                 'email',
                                 'city',
                                 'country');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports user updates
     */
    public function testVersion1ImportSupportsUserUpdate() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_update');
        $required_fields = array(array('username',
                                       'email',
                                       'idnumber'));
        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsRequiredUserFieldsOnCreate() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = $this->get_core_user_data();
        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        //user should be confirmed by default
        $data['confirmed'] = 1;

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsRequiredUserFieldsOnAdd() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = $this->get_core_user_data();
        $data['action'] = 'add';
        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that non-required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsNonRequiredUserFieldsOnCreate() {
        global $CFG, $DB;

        set_config('allowuserthemes', 1);

        $data = array(
            'country' => 'CA',
            'auth' => 'mnet',
            'maildigest' => '2',
            'autosubscribe' => '1',
            'trackforums' => '1',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        //print_object($DB->get_records('user'));

        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that fields are set to specified values during user update
     */
    public function testVersion1ImportSetsFieldsOnUserUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data, false);

        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   institution = :institution AND
                   department = :department";

        $exists = $DB->record_exists_select('user', $select, $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that yes/no fields are mapped to valid values during user update
     */
    public function testVersion1ImportMapsFieldsOnUserUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 'yes',
            'trackforums' => 'yes',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data, false);

        foreach ($data as $key => $val) {
            if (in_array((string)$val, array('no', 'yes'))) {
                $data[$key] = ((string)$val == 'yes') ? 1: 0;
            }
        }
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   institution = :institution AND
                   department = :department";

        $exists = $DB->record_exists_select('user', $select, $data);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that invalid auth plugins can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserAuthOnCreate() {
        $this->run_core_user_import(array('auth' => 'invalidauth'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid auth plugins can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserAuthOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'auth' => 'bogus');

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'auth' => 'manual'));
    }

    protected function set_password_policy_for_tests() {
        global $CFG;
        $CFG->passwordpolicy = true;
        $CFG->minpasswordlength = 8;
        $CFG->minpassworddigits = 1;
        $CFG->minpasswordlower = 1;
        $CFG->minpasswordupper = 1;
        $CFG->minpasswordnonalphanum = 1;
        $CFG->maxconsecutiveidentchars = 0;
    }

    /**
     * Validate that supplied passwords must match the site's password policy on user creation
     */
    public function testVersion1ImportPreventsInvalidUserPasswordOnCreate() {
        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array('password' => 'asdf'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that supplied passwords must match the site's password policy on user update
     */
    public function testVersion1ImportPreventsInvalidUserPasswordOnUpdate() {
        global $CFG, $DB;
        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'asdf');

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'password' => hash_internal_user_password('Rlippassword!1234')));
    }

    /**
     * Validate that invalid email addresses can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserEmailOnCreate() {
        $this->run_core_user_import(array('email' => 'invalidemail'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid maildigest values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserMaildigestOnCreate() {
        $this->run_core_user_import(array('maildigest' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid maildigest values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserMaildigestOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'maildigest' => 3);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'maildigest' => 0));
    }

    /**
     * Validate that invalid autosubscribe values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserAutosubscribeOnCreate() {
        $this->run_core_user_import(array('autosubscribe' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid autosubscribe values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserAutosubscribeOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'autosubscribe' => 3);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'autosubscribe' => 1));
    }

    /**
     * Validate that invalid trackforums values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserTrackforumsOnCreate() {
        set_config('forum_trackreadposts', 0);

        $this->run_core_user_import(array('trackforums' => 1));
        $this->assert_core_user_does_not_exist();

        set_config('forum_trackreadposts', 1);

        $this->run_core_user_import(array('trackforums' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid trackforums values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserTrackforumsOnUpdate() {
        global $CFG, $DB;

        set_config('forum_trackreadposts', 0);
        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'trackforums' => 1);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'trackforums' => 0));

        set_config('forum_trackreadposts', 1);
        $data['trackforums'] = 2;
        $this->run_core_user_import($data);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'trackforums' => 0));
    }

    /**
     * Validate that invalid screenreader values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserScreenreaderOnCreate() {
        $this->run_core_user_import(array('screenreader' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid screenreader values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserScreenreaderOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername'
        );

        $this->run_core_user_import($data, false);

        // make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
    }

    /**
     * Validate that invalid country values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserCountryOnCreate() {
        $this->run_core_user_import(array('country' => 12));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid country values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserCountryOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array('country' => 'CA'));

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'country' => 12);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'country' => 'CA'));
    }

    /**
     * Validate that invalid timezone values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserTimezoneOnCreate() {
        $this->run_core_user_import(array('timezone' => 14.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid timezone values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserTimezoneOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'timezone' => 14.0);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'timezone' => 99));
    }

    /**
     * Validate that timezone values can't be set on user creation when they are forced globally
     */
    public function testVersion1ImportPreventsOverridingForcedTimezoneOnCreate() {
        set_config('forcetimezone', 10);

        $this->run_core_user_import(array('timezone' => 5.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that timezone values can't be set on user update when they are forced globally
     */
    public function testVersion1ImportPreventsOverridingForcedTimezoneOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        set_config('forcetimezone', 10);

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'timezone' => 5.0);

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'timezone' => 99));
    }

    /**
     * Validate that invalid theme values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserThemeOnCreate() {
        set_config('allowuserthemes', 0);

        $this->run_core_user_import(array('theme' => 'rlmaster'));
        $this->assert_core_user_does_not_exist();

        set_config('allowuserthemes', 1);

        $this->run_core_user_import(array('theme' => 'bogus'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid theme values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserThemeOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        set_config('allowuserthemes', 0);

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'theme' => 'rlmaster');

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'theme' => ''));

        set_config('allowuserthemes', 1);

        $data['theme'] = 'bogus';

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'theme' => ''));
    }

    /**
     * Validate that invalid lang values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserLangOnCreate() {
        $this->run_core_user_import(array('lang' => '12'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid lang values can't be set on user update
     */
    public function testVersion1ImportPreventsInvalidUserLangOnUpdate() {
        global $DB, $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'lang' => '12');

        $this->run_core_user_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'lang' => $CFG->lang));
    }

    /**
     * Validate that the import defaults to not setting idnumber values if
     * a value is not supplied and ELIS is not configured to auto-assign
     */
    public function testVersion1ImportDoesNotSetIdnumberWhenNotSuppliedOrConfigured() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        $this->run_core_user_import(array());

        //make sure idnumber wasn't set
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => ''));
    }

    /**
     * Validate the the import can set a user's idnumber value on user creation
     */
    public function testVersion1ImportSetsSuppliedIdnumberOnCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        //run the import
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        //make sure idnumber was set
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipidnumber'));
    }

    /**
     * Validate the the import can't set a user's idnumber value on user update
     */
    public function testVersion1ImportDoesNotSetSuppliedIdnumberOnUpdate() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        //create the user
        $this->run_core_user_import(array());

        //run the import
        $this->run_core_user_import(array('action' => 'update',
                                          'username' => 'rlipusername',
                                          'idnumber' => 'rlipidnumber'));

        //make sure the idnumber was not set
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => ''));
    }

    /**
     * Validate that the import auto-assigns missing idnumbers when the column
     * is not supplied and the config setting is on
     */
    public function testVersion1ImportAutoAssignsMissingIdnumbersOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        //make sure we are auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        //run the import
        $this->run_core_user_import(array('rlipusername' => 'rlipusername'));

        //make sure the idnumber was set to the username value
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipusername'));
    }

    /**
     * Validate that the import auto-assigns missing idnumbers when they are
     * empty and the config setting is on
     */
    public function testVersion1ImportAutoAssignsEmptyIdnumbersOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        //make sure we are auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        //run the import
        $this->run_core_user_import(array('rlipusername' => 'rlipusername',
                                          'idnumber' => ''));

        //make sure the idnumber was set to the username value
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipusername'));
    }

    /**
     * Validate that the import performs user synchronization on user create
     * when an idnumber is supplied
     */
    public function testVersion1ImportSyncsUserToElisOnCreateWithIdnumberSupplied() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once($CFG->dirroot.'/elis/core/fields/moodle_profile/custom_fields.php');

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        //create Moodle custom field category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //create Moodle custom profile field
        $this->create_profile_field('rliptext', 'text', $category->id);

        //obtain the PM user context level
        $contextlevel = CONTEXT_ELIS_USER;

        //make sure the PM category and field exist
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array('shortname'   => 'rliptext',
                                 'name'        => 'rliptext',
                                 'datatype'    => 'text',
                                 'multivalued' => 0));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        //make sure the field is set up for synchronization
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        //update the user class's static cache of define user custom fields
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        //run the import
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber',
                                          'profile_field_rliptext' => 'rliptext'));

        //make sure PM user was created correctly
        $this->assert_record_exists(user::TABLE, array('username' => 'rlipusername',
                                                       'idnumber' => 'rlipidnumber'));

        //make sure the PM custom field data was set
        $sql = "SELECT 'x'
                FROM {".field::TABLE."} f
                JOIN {".field_data_text::TABLE."} d
                  ON f.id = d.fieldid
                WHERE f.shortname = ?
                AND d.data = ?";
        $params = array('rliptext', 'rliptext');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import performs user synchronization on user create
     * when an idnumber is auto-assigned
     */
    public function testVersion1ImportSyncsUserToElisOnCreateWithIdnumberAutoAssigned() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        //create Moodle custom field category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //create Moodle custom profile field
        $this->create_profile_field('rliptext', 'text', $category->id);

        //obtain the PM user context level
        $contextlevel = CONTEXT_ELIS_USER;

        //make sure the PM category and field exist
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array('shortname'   => 'rliptext',
                                 'name'        => 'rliptext',
                                 'datatype'    => 'text',
                                 'multivalued' => 0));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        //make sure the field is set up for synchronization
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        //update the user class's static cache of define user custom fields
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        //run the import
        $this->run_core_user_import(array('profile_field_rliptext' => 'rliptext'));

        //make sure PM user was created correctly
        $this->assert_record_exists(user::TABLE, array('username' => 'rlipusername',
                                                       'idnumber' => 'rlipusername'));

        //make sure the PM custom field data was set
        $sql = "SELECT 'x'
                FROM {".field::TABLE."} f
                JOIN {".field_data_text::TABLE."} d
                  ON f.id = d.fieldid
                WHERE f.shortname = ?
                AND d.data = ?";
        $params = array('rliptext', 'rliptext');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import performs user synchronization on user update
     */
    public function testVersion1ImportSyncsUserToElisOnUpdate() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        //create Moodle custom field category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //create Moodle custom profile field
        $this->create_profile_field('rliptext', 'text', $category->id);

        //obtain the PM user context level
        $contextlevel = CONTEXT_ELIS_USER;

        //make sure the PM category and field exist
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array('shortname'   => 'rliptext',
                                 'name'        => 'rliptext',
                                 'datatype'    => 'text',
                                 'multivalued' => 0));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        //make sure the field is set up for synchronization
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        //update the user class's static cache of define user custom fields
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        //create the user
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber',
                                          'profile_field_rliptext' => 'rliptext'));

        //make sure PM user was created correctly
        $this->assert_record_exists(user::TABLE, array('username' => 'rlipusername',
                                                       'idnumber' => 'rlipidnumber'));

        //run the import, updating the user
        $this->run_core_user_import(array('action' => 'update',
                                          'username' => 'rlipusername',
                                          'profile_field_rliptext' => 'rliptextupdated'));

        //make sure the PM custom field data was set
        $sql = "SELECT 'x'
                FROM {".field::TABLE."} f
                JOIN {".field_data_text::TABLE."} d
                  ON f.id = d.fieldid
                WHERE f.shortname = ?
                AND d.data = ?";
        $params = array('rliptext', 'rliptextupdated');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that default values are correctly set on user creation
     */
    public function testVersion1ImportSetsDefaultsOnUserCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        set_config('forcetimezone', 99);

        //make sure we are not auto-assigning idnumbers
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        $this->run_core_user_import(array());

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'manual',
            'maildigest' => 0,
            'autosubscribe' => 1,
            'trackforums' => 0,
            'timezone' => 99,
            'theme' => '',
            'lang' => $CFG->lang,
            'description' => '',
            'idnumber' => '',
            'institution' => '',
            'department' => ''
        );

        $exists = $DB->record_exists_select('user', $select, $params);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not set unsupported fields on user creation
     */
    public function testVersion1ImportPreventsSettingUnsupportedUserFieldsOnCreate() {
        global $CFG, $DB;

        $data = array();
        $data['forcepasswordchange'] = 1;
        $data['maildisplay'] = 1;
        $data['mailformat'] = 0;
        $data['htmleditor'] = 0;
        //$data['ajax'] = 0;
        $data['descriptionformat'] = FORMAT_WIKI;
        $this->run_core_user_import($data);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   maildisplay = :maildisplay AND
                   mailformat = :mailformat AND
                   htmleditor = :htmleditor AND
                   descriptionformat = :descriptionformat";
        $params = array('username' => 'rlipusername',
                        'mnethostid' => $CFG->mnet_localhost_id,
                        'maildisplay' => 2,
                        'mailformat' => 1,
                        'htmleditor' => 1,
                        'descriptionformat' => FORMAT_HTML);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);

        //check force password change separately
        $user = $DB->get_record('user', array('username' => 'rlipusername',
                                              'mnethostid' => $CFG->mnet_localhost_id));
        $preferences = get_user_preferences('forcepasswordchange', null, $user);

        $this->assertEquals(count($preferences), 0);
    }

    /**
     * Validate that import does not set unsupported fields on user update
     */
    public function testVersion1ImportPreventsSettingUnsupportedUserFieldsOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array();
        $data['action'] = 'update';
        $data['username'] = 'rlipusername';
        $data['forcepasswordchange'] = 1;
        $data['maildisplay'] = 1;
        $data['mailformat'] = 0;
        $data['htmleditor'] = 0;
        $data['descriptionformat'] = FORMAT_WIKI;

        $this->run_core_user_import($data, false);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   maildisplay = :maildisplay AND
                   mailformat = :mailformat AND
                   htmleditor = :htmleditor AND
                   descriptionformat = :descriptionformat";
        $params = array('username' => 'rlipusername',
                        'mnethostid' => $CFG->mnet_localhost_id,
                        'maildisplay' => 2,
                        'mailformat' => 1,
                        'htmleditor' => 1,
                        'descriptionformat' => FORMAT_HTML);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);

        //check force password change separately
        $user = $DB->get_record('user', array('username' => 'rlipusername',
                                              'mnethostid' => $CFG->mnet_localhost_id));
        $preferences = get_user_preferences('forcepasswordchange', null, $user);

        $this->assertEquals(count($preferences), 0);
    }

    /**
     * Validate that field-length checking works correctly on user creation
     */
    public function testVersion1ImportPreventsLongUserFieldsOnCreate() {
        $this->run_core_user_import(array('firstname' => str_repeat('a', 101)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('lastname' => str_repeat('a', 101)));
        $this->assert_core_user_does_not_exist();

        $value = str_repeat('a', 50).'@'.str_repeat('b', 50);
        $this->run_core_user_import(array('email' => $value));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('city' => str_repeat('a', 256)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('idnumber' => str_repeat('a', 256)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('institution' => str_repeat('a', 41)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('department' => str_repeat('a', 31)));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that field-length checking works correct on user update
     */
    public function testVersion1ImportPreventsLongUserFieldsOnUpdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber',
                                          'institution' => 'rlipinstitution',
                                          'department' => 'rlipdepartment'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'firstname' => str_repeat('a', 101));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'firstname' => 'rlipfirstname'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'lastname' => str_repeat('a', 101));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'lastname' => 'rliplastname'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'email' => str_repeat('a', 50).'@'.str_repeat('b', 50));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'email' => 'rlipuser@rlipdomain.com'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'city' => str_repeat('a', 256));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'city' => 'rlipcity'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'idnumber' => str_repeat('a', 256));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipidnumber'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'institution' => str_repeat('a', 41));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'institution' => 'rlipinstitution'));

        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'department' => str_repeat('a', 31));
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'department' => 'rlipdepartment'));
    }

    /**
     * Validate that setting profile fields works on user creation
     */
    public function testVersion1ImportSetsUserProfileFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        //create custom field category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //create custom profile fields
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->create_profile_field('rliplegacydatetime', 'datetime', $category->id);
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption2");
        $this->create_profile_field('rliptextarea', 'textarea', $category->id);
        $this->create_profile_field('rliptext', 'text', $category->id);

        //run import
        $data = array();
        $data['profile_field_rlipcheckbox'] = '1';
        $data['profile_field_rlipdatetime'] = 'jan/12/2011';
        $data['profile_field_rliplegacydatetime'] = '1/12/2011';
        $data['profile_field_rlipmenu'] = 'rlipoption1';
        $data['profile_field_rliptextarea'] = 'rliptextarea';
        $data['profile_field_rliptext'] = 'rliptext';

        $this->run_core_user_import($data);

        //fetch the user and their profile field data
        $user = $DB->get_record('user', array('username' => 'rlipusername',
                                              'mnethostid' => $CFG->mnet_localhost_id));
        profile_load_data($user);

        //validate data
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), true);
        $this->assertEquals($user->profile_field_rlipcheckbox, 1);
        $this->assertEquals(isset($user->profile_field_rlipdatetime), true);
        $this->assertEquals($user->profile_field_rlipdatetime, mktime(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rliplegacydatetime), true);
        $this->assertEquals($user->profile_field_rliplegacydatetime, mktime(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rlipmenu), true);
        $this->assertEquals($user->profile_field_rlipmenu, 'rlipoption1');
        $this->assertEquals(isset($user->profile_field_rliptextarea['text']), true);
        $this->assertEquals($user->profile_field_rliptextarea['text'], 'rliptextarea');
        $this->assertEquals(isset($user->profile_field_rliptext), true);
        $this->assertEquals($user->profile_field_rliptext, 'rliptext');
    }

    /**
     * Validate that setting profile fields works on user update
     */
    public function testVersion1ImportSetsUserProfileFieldsOnUpdate() {
        global $CFG, $DB;

        //perform default "user create" import
        $this->run_core_user_import(array());

        //create custom field category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //create custom profile fields
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->create_profile_field('rliplegacydatetime', 'datetime', $category->id);
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption2");
        $this->create_profile_field('rliptextarea', 'textarea', $category->id);
        $this->create_profile_field('rliptext', 'text', $category->id);

        //run import
        $data = array();
        $data['action'] = 'update';
        $data['username'] = 'rlipusername';
        $data['profile_field_rlipcheckbox'] = '1';
        $data['profile_field_rlipdatetime'] = 'jan/12/2011';
        $data['profile_field_rliplegacydatetime'] = '1/12/2011';
        $data['profile_field_rlipmenu'] = 'rlipoption1';
        $data['profile_field_rliptextarea'] = 'rliptextarea';
        $data['profile_field_rliptext'] = 'rliptext';

        $this->run_core_user_import($data, false);

        //fetch the user and their profile field data
        $user = $DB->get_record('user', array('username' => 'rlipusername',
                                              'mnethostid' => $CFG->mnet_localhost_id));
        profile_load_data($user);

        //validate data
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), true);
        $this->assertEquals($user->profile_field_rlipcheckbox, 1);
        $this->assertEquals(isset($user->profile_field_rlipdatetime), true);
        $this->assertEquals($user->profile_field_rlipdatetime, mktime(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rliplegacydatetime), true);
        $this->assertEquals($user->profile_field_rliplegacydatetime, mktime(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rlipmenu), true);
        $this->assertEquals($user->profile_field_rlipmenu, 'rlipoption1');
        $this->assertEquals(isset($user->profile_field_rliptextarea['text']), true);
        $this->assertEquals($user->profile_field_rliptextarea['text'], 'rliptextarea');
        $this->assertEquals(isset($user->profile_field_rliptext), true);
        $this->assertEquals($user->profile_field_rliptext, 'rliptext');
    }

    /**
     * Validate that the import does not create bogus profile field data on user creation
     */
    public function testVersion1ImportValidatesProfileFieldsOnCreate() {
        global $DB;

        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->run_core_user_import(array('profile_field_rlipcheckbox' => '2'));
        $this->assert_core_user_does_not_exist();

        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->run_core_user_import(array('profile_field_rlipdatetime' => '1000000000'));
        $this->assert_core_user_does_not_exist();

        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption1B");
        $this->run_core_user_import(array('profile_field_rlipmenu' => 'rlipoption2'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that the import does not create bogus profile field data on user update
     */
    public function testVersion1ImportValidatesProfileFieldsOnUpdate() {
        global $CFG, $DB;

        //run the "create user" import
        $this->run_core_user_import(array());

        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                     'mnethostid' => $CFG->mnet_localhost_id));

        //create the category
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        //try to insert bogus checkbox data
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'profile_field_rlipcheckbox' => '2');
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);

        //try to insert bogus datetime data
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'profile_field_rlipdatetime' => '1000000000');
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);

        //try to insert bogus menu data
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption1B");
        $params = array('action' => 'update',
                        'username' => 'rlipusername',
                        'profile_field_rlipmenu' => 'rlipoption2');
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);
    }

    /**
     * Validate that the import does not create duplicate user records on creation
     */
    public function testVersion1ImportPreventsDuplicateUserCreation() {
        global $DB;

        $initial_count = $DB->count_records('user');

        //set up our data
        $this->run_core_user_import(array('idnumber' => 'testdupidnumber'));
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        //test duplicate username
        $data = array('email' => 'testdup2@testdup2.com',
                      'idnumber' => 'testdupidnumber2');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        //test duplicate email
        $data = array('username' => 'testdupusername3',
                      'idnumber' => 'testdupidnumber3');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        //test duplicate idnumber
        $data = array('username' => 'testdupusername4',
                      'email' => 'testdup2@testdup4.com',
                      'idnumber' => 'testdupidnumber');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);
    }

    /**
     * Validate that the plugin can update users based on any combination
     * of username, email and idnumber
     */
    public function testVersion1ImportUpdatesBasedOnIdentifyingFields() {
        global $CFG;

        //set up our data
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        //update based on username
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'setfromusername');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        //update based on email
        $data = array('action' => 'update',
                      'email' => 'rlipuser@rlipdomain.com',
                      'firstname' => 'setfromemail');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        //update based on idnumber
        $data = array('action' => 'update',
                      'idnumber' => 'rlipidnumber',
                      'firstname' => 'setfromidnumber');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        //update based on username, email
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'firstname' => 'setfromusernameemail');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        //update based on username, idnumber
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'idnumber' => 'rlipidnumber',
                      'firstname' => 'setfromusernameidnumber');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        //update based on email, idnumber
        $data = array('action' => 'update',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'firstname' => 'setfromemailidnumber');
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate that updating users does not produce any side-effects
     * in the user data
     */
    public function testVersion1ImportOnlyUpdatesSuppliedUserFields() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'updatedfirstname');

        $this->run_core_user_import($data, false);

        $data = $this->get_core_user_data();
        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        $data['firstname'] = 'updatedfirstname';

        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate that update actions must match existing users to do anything
     */
    public function testVersion1ImportDoesNotUpdateNonmatchingUsers() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber',
                                          'firstname' => 'oldfirstname'));

        $check_data = array('mnethostid' => $CFG->mnet_localhost_id,
                            'username' => 'rlipusername',
                            'email' => 'rlipuser@rlipdomain.com',
                            'idnumber' => 'rlipidnumber',
                            'firstname' => 'oldfirstname');

        //bogus username
        $data = array('action' => 'update',
                      'username' => 'bogususername',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //bogus email
        $data = array('action' => 'update',
                      'email' => 'bogus@domain.com',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //bogus idnumber
        $data = array('action' => 'update',
                      'idnumber' => 'bogusidnumber',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);
    }

    /**
     * Validate that fields identifying users in updates are not updated
     */
    public function testVersion1ImportDoesNotUpdateIdentifyingUserFields() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber',
                                          'firstname' => 'oldfirstname'));

        $check_data = array('mnethostid' => $CFG->mnet_localhost_id,
                            'username' => 'rlipusername',
                            'email' => 'rlipuser@rlipdomain.com',
                            'idnumber' => 'rlipidnumber',
                            'firstname' => 'oldfirstname');

        //valid username, bogus email
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'email' => 'bogus@domain.com',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //valid username, bogus idnumber
        $data = array('action' => 'update',
                      'username' => 'rlipusername',
                      'idnumber' => 'bogusidnumber',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //valid email, bogus username
        $data = array('action' => 'update',
                      'username' => 'bogususername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //valid email, bogus idnumber
        $data = array('action' => 'update',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'bogusidnumber',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //valid idnumber, bogus username
        $data = array('action' => 'update',
                      'username' => 'bogususername',
                      'idnumber' => 'rlipidnumber',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);

        //valid idnumber, bogus email
        $data = array('action' => 'update',
                      'email' => 'bogus@domain.com',
                      'idnumber' => 'rlipidnumber',
                      'firstname' => 'newfirstname');
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $check_data);
    }

    /**
     * Validate that user create and update actions set time created
     * and time modified appropriately
     */
    public function testVersion1ImportSetsUserTimestamps() {
        global $CFG, $DB;

        $starttime = time();

        //set up base data
        $this->run_core_user_import(array());

        //validate timestamps
        $where = "username = ? AND
                  mnethostid = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('rlipusername', $CFG->mnet_localhost_id, $starttime, $starttime);
        $exists = $DB->record_exists_select('user', $where, $params);
        $this->assertEquals($exists, true);

        //reset time modified
        $user = new stdClass;
        $user->id = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                       'mnethostid' => $CFG->mnet_localhost_id));
        $user->timemodified = 0;
        $DB->update_record('user', $user);

        //update data
        $this->run_core_user_import(array('action' => 'update',
                                          'username' => 'rlipusername',
                                          'firstname' => 'newfirstname'));

        //validate timestamps
        $where = "username = ? AND
                  mnethostid = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('rlipusername', $CFG->mnet_localhost_id, $starttime, $starttime);
        $exists = $DB->record_exists_select('user', $where, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin supports user deletes
     */
    public function testVersion1ImportSupportsUserDelete() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_delete');
        $required_fields = array(array('username',
                                       'email',
                                       'idnumber'));
        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports user disabling
     */
    public function testVersion1ImportSupportsUserDisable() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_disable');
        $required_fields = array(array('username',
                                       'email',
                                       'idnumber'));
        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username
     */
    public function testVersion1ImportDeletesUserBasedOnUsername() {
        global $CFG, $DB;
        set_config('siteguest', 0);

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                     'mnethostid' => $CFG->mnet_localhost_id));

        $data = array('action' => 'delete',
                      'username' => 'rlipusername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on email
     */
    public function testVersion1ImportDeletesUserBasedOnEmail() {
        global $DB;

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('action' => 'delete',
                      'email' => 'rlipuser@rlipdomain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

     /**
     * Validate that the version 1 plugin can delete uses based on idnumber
     */
    public function testVersion1ImportDeletesUserBasedOnIdnumber() {
        global $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'idnumber' => 'rlipidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username and
     * email
     */
    public function testVersion1ImportDeletesUserBasedOnUsernameEmail() {
        global $CFG, $DB;

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                     'mnethostid' => $CFG->mnet_localhost_id,
                                                     'email' => 'rlipuser@rlipdomain.com'));

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username and
     * idnumber
     */
    public function testVersion1ImportDeletesUserBasedOnUsernameIdnumber() {
        global $CFG, $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                     'mnethostid' => $CFG->mnet_localhost_id,
                                                     'idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'idnumber' => 'rlipidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on email and
     * idnumber
     */
    public function testVersion1ImportDeletesUserBasedOnEmailIdnumber() {
        global $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('email' => 'rlipuser@rlipdomain.com',
                                                     'idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username, email and
     * idnumber
     */
    public function testVersion1ImportDeletesUserBasedOnUsernameEmailIdnumber() {
        global $CFG, $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                     'mnethostid' => $CFG->mnet_localhost_id,
                                                     'email' => 'rlipuser@rlipdomain.com',
                                                     'idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid,
                                                  'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithInvalidUsername() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'delete',
                      'username' => 'bogususername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithInvalidEmail() {
        $this->run_core_user_import(array());

        $data = array('action' => 'delete',
                      'email' => 'bogus@domain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('email' => 'rlipuser@rlipdomain.com',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithInvalidIdnumber() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'idnumber' => 'bogusidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('idnumber' => 'rlipidnumber',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidUsernameInvalidEmail() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'email' => 'bogus@domain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'email' => 'rlipuser@rlipdomain.com',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidUsernameInvalidIdnumber() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'username' => 'rlipusername',
                      'idnumber' => 'bogusidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipidnumber',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidEmailInvalidUsername() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'delete',
                      'email' => 'rlipuser@rlipdomain.com',
                      'username' => 'bogususername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('email' => 'rlipuser@rlipdomain.com',
                                                  'username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidEmailInvalidIdnumber() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'bogusidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('email' => 'rlipuser@rlipdomain.com',
                                                  'idnumber' => 'rlipidnumber',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidIdnumberInvalidUsername() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'idnumber' => 'rlipidnumber',
                      'username' => 'bogususername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('idnumber' => 'rlipidnumber',
                                                  'username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserWithValidIdnumberInvalidEmail() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete',
                      'idnumber' => 'rlipidnumber',
                      'email' => 'bogus@domain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('idnumber' => 'rlipidnumber',
                                                  'email' => 'rlipuser@rlipdomain.com',
                                                  'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin deletes appropriate associations when
     * deleting a user
     */
    public function testVersion1ImportDeleteUserDeletesAssociations() {
        global $CFG, $DB;
        set_config('siteadmins', 0);
        // New config settings needed for course format refactoring in 2.4
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

        require_once($CFG->dirroot.'/cohort/lib.php');
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/lib/gradelib.php');

        //set up context records
        $this->init_contexts_and_site_course();

        //set up the guest user to prevent enrolment plugins from thinking the
        //created user is the guest user
        if ($record = self::$origdb->get_record('user', array('username' => 'guest',
                                                'mnethostid' => $CFG->mnet_localhost_id))) {
            unset($record->id);
            $DB->insert_record('user', $record);
        }

        //create our test user, and determine their userid
        $this->run_core_user_import(array());
        $userid = (int)$DB->get_field('user', 'id', array('username' => 'rlipusername',
                                                          'mnethostid' => $CFG->mnet_localhost_id));

        //set up the site course
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        //the the user to a cohort - does not require cohort to actually exist
        cohort_add_member(1, $userid);

        //create a course category - there is no API for doing this
        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        //create a course
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');
        $course = new stdClass;
        $course->category = $category->id;
        $course->fullname = 'testfullname';
        $course = create_course($course);

        //create a grade
        $grade_item = new grade_item(array('courseid' => $course->id,
                                           'itemtype' => 'manual',
                                           'itemname' => 'testitem'), false);
        $grade_item->insert();
        $grade_grade = new grade_grade(array('itemid' => $grade_item->id,
                                             'userid' => $userid), false);
        $grade_grade->insert();

        //send the user an unprocessed message
        set_config('noemailever', true);
        $DB->delete_records('message_processors');
        $message = new stdClass;
        $message->userfrom = $userid;
        $message->userto = $userid;
        $message->component = 'moodle';
        $message->name = 'notices';
        $message->subject = 'testsubject';
        $message->fullmessage = 'testmessage';
        $message->fullmessagehtml = 'testmessage';
        $message->smallmessage = 'testmessage';
        $message->fullmessageformat = FORMAT_PLAIN;
        message_send($message);

        //set up a user tag
        tag_set('user', $userid, array('testtag'));

        //create a new course-level role
        $roleid = create_role('testrole', 'testrole', 'testrole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        //enrol the user in the course with the new role
        enrol_try_internal_enrol($course->id, $userid, $roleid);

        //create a group
        $group = new stdClass;
        $group->name = 'testgroup';
        $group->courseid = $course->id;
        $groupid = groups_create_group($group);

        //add the user to the group
        groups_add_member($groupid, $userid);

        set_user_preference('testname', 'testvalue', $userid);

        //create profile field data - don't both with the API here because it's a bit unwieldy
        $userinfodata = new stdClass;
        $userinfodata->fieldid = 1;
        $userinfodata->data = 'bogus';
        $userinfodata->userid = $userid;
        $DB->insert_record('user_info_data', $userinfodata);

        //there is no easily accessible API for doing this
        $lastaccess = new stdClass;
        $lastaccess->userid = $userid;
        $lastaccess->courseid = $course->id;
        $DB->insert_record('user_lastaccess', $lastaccess);

        //assert data condition before delete
        $this->assertEquals($DB->count_records('message_read'), 0);
        $this->assertEquals($DB->count_records('tag_instance'), 1);
        $this->assertEquals($DB->count_records('grade_grades'), 1);
        $this->assertEquals($DB->count_records('cohort_members'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('groups_members'), 1);
        $this->assertEquals($DB->count_records('user_preferences'), 1);
        $this->assertEquals($DB->count_records('user_info_data'), 1);
        $this->assertEquals($DB->count_records('user_lastaccess'), 1);

        $data = array('action' => 'delete',
                      'username' => 'rlipusername');
        $this->run_core_user_import($data, false);

        //assert data condition after delete
        $this->assertEquals($DB->count_records('message_read'), 1);
        $this->assertEquals($DB->count_records('grade_grades'), 0);
        $this->assertEquals($DB->count_records('tag_instance'), 0);
        $this->assertEquals($DB->count_records('cohort_members'), 0);
        $this->assertEquals($DB->count_records('user_enrolments'), 0);
        $this->assertEquals($DB->count_records('role_assignments'), 0);
        $this->assertEquals($DB->count_records('groups_members'), 0);
        $this->assertEquals($DB->count_records('user_preferences'), 0);
        $this->assertEquals($DB->count_records('user_info_data'), 0);
        $this->assertEquals($DB->count_records('user_lastaccess'), 0);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on user creation
     */
    public function testVersion1ImportUsesUserFieldMappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        // set up our mapping of standard field names to custom field names
        $mapping = array(
            'action' => 'action1',
            'username' => 'username1',
            'auth' => 'auth1',
            'password' => 'password1',
            'firstname' => 'firstname1',
            'lastname' => 'lastname1',
            'email' => 'email1',
            'maildigest' => 'maildigest1',
            'autosubscribe' => 'autosubscribe1',
            'trackforums' => 'trackforums1',
            'city' => 'city1',
            'country' => 'country1',
            'timezone' => 'timezone1',
            'theme' => 'theme1',
            'lang' => 'lang1',
            'description' => 'description1',
            'idnumber' => 'idnumber1',
            'institution' => 'institution1',
            'department' => 'department1'
        );

        //store the mapping records in the database
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'user';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        // run the import
        $data = array(
            'entity' => 'user',
            'action1' => 'create',
            'username1' => 'rlipusername',
            'auth1' => 'mnet',
            'password1' => 'Rlippassword!0',
            'firstname1' => 'rlipfirstname',
            'lastname1' => 'rliplastname',
            'email1' => 'rlipuser@rlipdomain.com',
            'maildigest1' => '2',
            'autosubscribe1' => '1',
            'trackforums1' => '1',
            'city1' => 'rlipcity',
            'country1' => 'CA',
            'timezone1' => -5.0,
            'theme1' => 'standard',
            'lang1' => 'en',
            'description1' => 'rlipdescription',
            'idnumber1' => 'rlipidnumber',
            'institution1' => 'rlipinstitution',
            'department1' => 'rlipdepartment'
        );
        $this->run_core_user_import($data, false);

        //validate user record
        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   password = :password AND
                   firstname = :firstname AND
                   lastname = :lastname AND
                   email = :email AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   city = :city AND
                   country = :country AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'mnet',
            'password' => hash_internal_user_password('Rlippassword!0'),
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'city' => 'rlipcity',
            'country' => 'CA',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function testVersion1ImportUserFieldImportPreventsStandardFieldUse() {
        global $CFG, $DB;
        $plugin_dir = get_plugin_directory('rlipimport', 'version1');
        require_once($plugin_dir.'/version1.class.php');
        require_once($plugin_dir.'/lib.php');

        //create the mapping record
        $record = new stdClass;
        $record->entitytype = 'user';
        $record->standardfieldname = 'username';
        $record->customfieldname = 'username2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        //get the import plugin set up
        $data = array();
        $provider = new rlip_importprovider_mockuser($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('user');

        //transform a sample record
        $record = new stdClass;
        $record->username = 'username';
        $record = $importplugin->apply_mapping('user', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        //validate that the field was unset
        $this->assertEquals(isset($record->username), false);
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function testVersion1ImportSucceedsWithMaxLengthUserFields() {
        //data for all fixed-size fields at their maximum sizes
        $data = array('username' => str_repeat('x', 100),
                      'firstname' => str_repeat('x', 100),
                      'lastname' => str_repeat('x', 100),
                      'email' => str_repeat('x', 47).'@'.str_repeat('x', 48).'.com',
                      'city' => str_repeat('x', 120),
                      'idnumber' => str_repeat('x', 255),
                      'institution' => str_repeat('x', 40),
                      'department' => str_repeat('x', 30));
        //run the import
        $this->run_core_user_import($data);

        //data validation
        $this->assert_record_exists('user', $data);
    }
}
