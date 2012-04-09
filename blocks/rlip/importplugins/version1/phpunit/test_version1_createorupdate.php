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
require_once(elis::lib('testlib.php'));

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_createorupdateuser extends rlip_importprovider_mock {

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
 * Class that fetches import files for the course import
 */
class rlip_importprovider_createorupdatecourse extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_createorupdateenrolment extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class for testing version 1 "create or update" action correctness
 */
class version1CreateorupdateTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Fields to set in the import
     */
    private function run_core_user_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_createorupdateuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Helper function that runs the course import for a sample user
     *
     * @param array $data Fields to set in the import
     */
    private function run_core_course_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_createorupdatecourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Helper function that runs the enrolment import for a sample user
     *
     * @param array $data Fields to set in the import
     */
    private function run_core_enrolment_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlip_importprovider_createorupdateenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
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
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private function init_contexts_and_site_course() {
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

        build_context_path();
    }

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        global $CFG;

        //custom fields in "elis core"
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'config_plugins' => 'moodle',
            'user' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'role_assignments' => 'moodle',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
            field_data_int::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            'config' => 'moodle'
        );

        // Detect if we are running this test on a site with the ELIS PM system in place
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/usermoodle.class.php'));

            $tables[user::TABLE] = 'elis_program';
            $tables[usermoodle::TABLE] = 'elis_program';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'log' => 'moodle',
                     'user_enrolments' => 'moodle');
    }

    /**
     * Create a test course with required fields set to default values
     *
     * @return int The id of the newly-created course
     */
    private function create_test_course() {
        global $DB;

        //this will handle the creation of the course and category
        $import_data = array('entity' => 'course',
                             'action' => 'create',
                             'shortname' => 'rlipshortname',
                             'fullname' => 'rlipfullname',
                             'category' => 'rlipcategory');
        $this->run_core_course_import($import_data);
        return $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
    }

    /**
     * Create a test user with required fields set to default values
     *
     * @return int The id of the newly-created user
     */
    private function create_test_user() {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Rlippassword!1234';

        return user_create_user($user);
    }

    /**
     * Creating an import mapping record
     *
     * @param string $entitytype The type of entity the mapping applies to
     * @param string $standardfieldname The standard field name
     * @param $customfieldname The custom field name
     */
    private function create_mapping_record($entitytype, $standardfieldname, $customfieldname) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        //add the record to the DB
        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can create users
     */
    public function testVersion1CreateorupdateCreatesUser() {
        global $CFG, $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //validate that the standard create action still works
        $expected_data = array('username' => 'rlipusername',
                               'password' => hash_internal_user_password('Rlippassword!1234'),
                               'mnethostid' => $CFG->mnet_localhost_id,
                               'firstname' => 'rlipfirstname',
                               'lastname' => 'rliplastname',
                               'email' => 'rlipuser@rlipdomain.com',
                               'city' => 'rlipcity',
                               'country' => 'CA');
        $import_data = array('entity' => 'user',
                             'action' => 'create',
                             'username' => 'rlipusername',
                             'password' => 'Rlippassword!1234',
                             'firstname' => 'rlipfirstname',
                             'lastname' => 'rliplastname',
                             'email' => 'rlipuser@rlipdomain.com',
                             'city' => 'rlipcity',
                             'country' => 'CA');
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);

        //reset state
        $DB->delete_records('user');

        //validate that updates are converted to creates
        $import_data['action'] = 'update';
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can update users
     */
    public function testVersion1CreateorupdateUpdatesUser() {
        global $CFG, $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //initial data setup
        $expected_data = array('username' => 'rlipusername',
                               'password' => hash_internal_user_password('Rlippassword!1234'),
                               'mnethostid' => $CFG->mnet_localhost_id,
                               'firstname' => 'rlipfirstname',
                               'lastname' => 'rliplastname',
                               'email' => 'rlipuser@rlipdomain.com',
                               'city' => 'rlipcity',
                               'country' => 'CA');
        $import_data = array('entity' => 'user',
                             'action' => 'create',
                             'username' => 'rlipusername',
                             'password' => 'Rlippassword!1234',
                             'firstname' => 'rlipfirstname',
                             'lastname' => 'rliplastname',
                             'email' => 'rlipuser@rlipdomain.com',
                             'city' => 'rlipcity',
                             'country' => 'CA');
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);

        //validate that creates are converted to updates
        $expected_data['password'] = hash_internal_user_password('Rlippassword!12342');
        $expected_data['firstname'] = 'rlipfirstname2';
        $expected_data['lastname'] = 'rliplastname2';
        $import_data['password'] = 'Rlippassword!12342';
        $import_data['firstname'] = 'rlipfirstname2';
        $import_data['lastname'] = 'rliplastname2';
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);
        $this->assertEquals($DB->count_records('user'), 1);

        //validate that the standard update action still works
        $expected_data['password'] = hash_internal_user_password('Rlippassword!12342');
        $expected_data['firstname'] = 'rlipfirstname2';
        $expected_data['lastname'] = 'rliplastname2';
        $import_data['action'] = 'update';
        $import_data['password'] = 'Rlippassword!12342';
        $import_data['firstname'] = 'rlipfirstname2';
        $import_data['lastname'] = 'rliplastname2';
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);
        $this->assertEquals($DB->count_records('user'), 1);
    }

    /**
     * Validate that the "create or update" flag respects field mappings
     */
    public function testVersion1CreateorupdateRespectsUserFieldMapping() {
        global $CFG, $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //set up mapping record
        $this->create_mapping_record('user', 'action' ,'customaction');

        //validate that create still works with the mapping set
        $expected_data = array('username' => 'rlipusername',
                               'password' => hash_internal_user_password('Rlippassword!1234'),
                               'mnethostid' => $CFG->mnet_localhost_id,
                               'firstname' => 'rlipfirstname',
                               'lastname' => 'rliplastname',
                               'email' => 'rlipuser@rlipdomain.com',
                               'city' => 'rlipcity',
                               'country' => 'CA');
        $import_data = array('entity' => 'user',
                             'customaction' => 'create',
                             'username' => 'rlipusername',
                             'password' => 'Rlippassword!1234',
                             'firstname' => 'rlipfirstname',
                             'lastname' => 'rliplastname',
                             'email' => 'rlipuser@rlipdomain.com',
                             'city' => 'rlipcity',
                             'country' => 'CA');
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);

        //reset state
        $DB->delete_records('user');

        //validate that updates are converted to creates with the mapping set
        $import_data['customaction'] = 'update';
        $this->run_core_user_import($import_data);
        $this->assert_record_exists('user', $expected_data);
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can create courses
     */
    public function testVersion1CreateorupdateCreatesCourse() {
        global $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //validate that the standard create action still works
        $expected_data = array('shortname' => 'rlipshortname',
                               'fullname' => 'rlipfullname');
        $import_data = array('entity' => 'course',
                             'action' => 'create',
                             'shortname' => 'rlipshortname',
                             'fullname' => 'rlipfullname',
                             'category' => 'rlipcategory');
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);

        //reset state
        $select = 'id != ?';
        $params = array(SITEID);
        $DB->delete_records_select('course', $select, $params);

        //validate that updates are converted to creates
        $import_data['action'] = 'update';
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can update courses
     */
    public function testVersion1CreateorupdateUpdatesCourse() {
        global $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //initial data setup
        $expected_data = array('shortname' => 'rlipshortname',
                               'fullname' => 'rlipfullname');
        $import_data = array('entity' => 'course',
                             'action' => 'create',
                             'shortname' => 'rlipshortname',
                             'fullname' => 'rlipfullname',
                             'category' => 'rlipcategory');
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);

        //validate that creates are converted to updates
        $expected_data['fullname'] = 'rlipfullname2';
        $import_data['fullname'] = 'rlipfullname2';
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);
        $this->assertEquals($DB->count_records('course'), 2);

        //validate that the standard update action still works
        $expected_data['fullname'] = 'fullname3';
        $import_data['action'] = 'update';
        $import_data['fullname'] = 'fullname3';
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);
        $this->assertEquals($DB->count_records('course'), 2);
    }

    /**
     * Validate that the "create or update" flag respects field mappings
     */
    public function testVersion1CreateorupdateRespectsCourseFieldMapping() {
        global $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //set up mapping record
        $this->create_mapping_record('course', 'action' ,'customaction');

        //validate that create still works with the mapping set
        $expected_data = array('shortname' => 'rlipshortname',
                               'fullname' => 'rlipfullname');
        $import_data = array('entity' => 'course',
                             'customaction' => 'create',
                             'shortname' => 'rlipshortname',
                             'fullname' => 'rlipfullname',
                             'category' => 'rlipcategory');
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);

        //reset state
        $select = 'id != ?';
        $params = array(SITEID);
        $DB->delete_records_select('course', $select, $params);

        //validate that updates are converted to creates with the mapping set
        $import_data['customaction'] = 'update';
        $this->run_core_course_import($import_data);
        $this->assert_record_exists('course', $expected_data);
    }

    /**
     * Validate that when the "create or update" flag is enabled, create
     * actions can create enrolments
     */
    public function testVersion1CreateorupdateCreatesEnrolmentFromCreateAction() {
        global $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        set_config('gradebookroles', '');
        $this->init_contexts_and_site_course();

        //initial data setup
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));
        $syscontext = get_context_instance(CONTEXT_SYSTEM);
        //make sure it has the course view capability so it can be assigned as
        //a non-student role
        assign_capability('moodle/course:view', CAP_ALLOW, $roleid, $syscontext->id);

        //validate that the standard create action still works
        $expected_data = array('userid' => $userid,
                               'contextid' => $context->id,
                               'roleid' => $roleid);
        $import_data = array('entity' => 'enrolment',
                             'action' => 'create',
                             'username' => 'rlipusername',
                             'context' => 'course',
                             'instance' => 'rlipshortname',
                             'role' => 'rlipshortname');
        $this->run_core_enrolment_import($import_data);
        $this->assert_record_exists('role_assignments', $expected_data);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that when the "create or update" flag is enabled, update
     * actions can not create enrolments
     */
    public function testVersion1CreateorupdateDoesNotCreateEnrolmentFromUpdateAction() {
        global $DB;

        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //initial data setup
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        //validate that updates are not converted to creates for enrolments
        $import_data = array('entity' => 'enrolment',
                             'action' => 'update',
                             'username' => 'rlipusername',
                             'context' => 'course',
                             'instance' => 'rlipshortname',
                             'role' => 'rlipshortname');
        $this->run_core_enrolment_import($import_data);
        $this->assertEquals($DB->count_records('role_assignments'), 0);
    }

    /**
     * Validate that the "create or update" flag doesn't accidentally trigger a
     * user mis-match when a required field is empty
     */
    public function testVersion1CreateorupdateIgnoresEmptyUserFields() {
        //set up initial conditions
        set_config('createorupdate', 1, 'rlipimport_version1');
        $this->init_contexts_and_site_course();

        //initial data setup
        $this->create_test_user();

        //update with empty fields
        $import_data = array('entity' => 'user',
                             'action' => 'update',
                             'username' => 'rlipusername',
                             'firstname' => 'updatedfirst',
                             'email' => '');
        $this->run_core_user_import($import_data);

        //validate that the record was updated and not created due to the
        //"create or update" logic thinking it's a non-existent user
        $data = array('username' => 'rlipusername',
                      'firstname' => 'updatedfirst',
                      'email' => 'rlipuser@rlipdomain.com');
        $this->assert_record_exists('user', $data);
    }
}
