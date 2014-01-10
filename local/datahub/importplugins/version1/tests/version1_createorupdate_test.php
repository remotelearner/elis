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
 * @package    dhimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');

/**
 * Class for testing version 1 "create or update" action correctness
 * @group local_datahub
 * @group dhimport_version1
 */
class version1createorupdate_testcase extends rlip_test {

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Fields to set in the import
     */
    private function run_core_user_import($data) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_createorupdateuser($data);

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
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_createorupdatecourse($data);

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
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_createorupdateenrolment($data);

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
     * Create a test course with required fields set to default values
     *
     * @return int The id of the newly-created course
     */
    private function create_test_course() {
        global $DB;

        // This will handle the creation of the course and category.
        $importdata = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $this->run_core_course_import($importdata);
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
        $file = get_plugin_directory('dhimport', 'version1').'/lib.php';
        require_once($file);

        // Add the record to the DB.
        $record = new stdClass;
        $record->entitytype = $entitytype;
        $record->standardfieldname = $standardfieldname;
        $record->customfieldname = $customfieldname;
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
    }

    /**
     * Validate that when the "create or update" flag is enabled, create
     * can create users
     */
    public function test_version1createorupdatecreatesuser() {
        global $CFG, $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Validate that the standard create action still works.
        $expecteddata = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $importdata = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!1234',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );

        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        // Reset state.
        $DB->delete_records('user');
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can update users
     */
    public function test_version1createorupdateupdatesuser() {
        global $CFG, $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        $initalusercount = $DB->count_records('user');

        // Initial data setup.
        $expecteddata = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $importdata = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!1234',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        // Validate that creates are converted to updates.
        $expecteddata['firstname'] = 'rlipfirstname2';
        $expecteddata['lastname'] = 'rliplastname2';
        $importdata['password'] = 'Rlippassword!12342';
        $importdata['firstname'] = 'rlipfirstname2';
        $importdata['lastname'] = 'rliplastname2';
        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        $this->assertEquals($DB->count_records('user'), $initalusercount+1);

        // Validate that the standard update action still works.
        $expecteddata['firstname'] = 'rlipfirstname2';
        $expecteddata['lastname'] = 'rliplastname2';
        $importdata['action'] = 'update';
        $importdata['password'] = 'Rlippassword!12342';
        $importdata['firstname'] = 'rlipfirstname2';
        $importdata['lastname'] = 'rliplastname2';
        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        $this->assertEquals($DB->count_records('user'), $initalusercount+1);
    }

    /**
     * Validate that the "create or update" flag respects field mappings
     */
    public function test_version1createorupdaterespectsuserfieldmapping() {
        global $CFG, $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Set up mapping record.
        $this->create_mapping_record('user', 'action', 'customaction');

        // Validate that create still works with the mapping set.
        $expecteddata = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $importdata = array(
            'entity' => 'user',
            'customaction' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!1234',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        // Reset state.
        $DB->delete_records('user');
    }

    /**
     * Validate that when the "create or update" flag is enabled, create
     * can create courses
     */
    public function test_version1createorupdatecreatescourse() {
        global $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Validate that the standard create action still works.
        $expecteddata = array('shortname' => 'rlipshortname', 'fullname' => 'rlipfullname');
        $importdata = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);

        // Reset state.
        $select = 'id != ?';
        $params = array(SITEID);
        $DB->delete_records_select('course', $select, $params);
    }

    /**
     * Validate that when the "create or update" flag is enabled, both create
     * and update actions can update courses
     */
    public function test_version1createorupdateupdatescourse() {
        global $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Initial data setup.
        $expecteddata = array('shortname' => 'rlipshortname', 'fullname' => 'rlipfullname');
        $importdata = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);

        // Validate that creates are converted to updates.
        $expecteddata['fullname'] = 'rlipfullname2';
        $importdata['fullname'] = 'rlipfullname2';
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);
        $this->assertEquals($DB->count_records('course'), 2);

        // Validate that the standard update action still works.
        $expecteddata['fullname'] = 'fullname3';
        $importdata['action'] = 'update';
        $importdata['fullname'] = 'fullname3';
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);
        $this->assertEquals($DB->count_records('course'), 2);
    }

    /**
     * Validate that the "create or update" flag respects field mappings
     */
    public function test_version1createorupdaterespectscoursefieldmapping() {
        global $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Set up mapping record.
        $this->create_mapping_record('course', 'action', 'customaction');

        // Validate that create still works with the mapping set.
        $expecteddata = array('shortname' => 'rlipshortname', 'fullname' => 'rlipfullname');
        $importdata = array(
            'entity' => 'course',
            'customaction' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);

        // Reset state.
        $select = 'id != ?';
        $params = array(SITEID);
        $DB->delete_records_select('course', $select, $params);
    }

    /**
     * Validate that when the "create or update" flag is enabled, create
     * actions can create enrolments
     */
    public function test_version1createorupdatecreatesenrolmentfromcreateaction() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');
        set_config('gradebookroles', '');

        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        // Initial data setup.
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = context_course::instance($courseid);
        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));
        $syscontext = context_system::instance();
        // Make sure it has the course view capability so it can be assigned as a non-student role.
        assign_capability('moodle/course:view', CAP_ALLOW, $roleid, $syscontext->id);

        // Validate that the standard create action still works.
        $expecteddata = array('userid' => $userid, 'contextid' => $context->id, 'roleid' => $roleid);
        $importdata = array(
            'entity' => 'enrolment',
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $this->run_core_enrolment_import($importdata);
        $this->assert_record_exists('role_assignments', $expecteddata);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that when the "create or update" flag is enabled, update
     * actions can not create enrolments
     */
    public function test_version1createorupdatedoesnotcreateenrolmentfromupdateaction() {
        global $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Initial data setup.
        $userid = $this->create_test_user();
        $courseid = $this->create_test_course();
        $context = context_course::instance($courseid);
        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        // Validate that updates are not converted to creates for enrolments.
        $importdata = array(
            'entity' => 'enrolment',
            'action' => 'update',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $this->run_core_enrolment_import($importdata);
        $this->assertEquals($DB->count_records('role_assignments'), 0);
    }

    /**
     * Validate that the "create or update" flag doesn't accidentally trigger a
     * user mis-match when a required field is empty
     */
    public function test_version1createorupdateignoresemptyuserfields() {
        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Initial data setup.
        $this->create_test_user();

        // Update with empty fields.
        $importdata = array(
            'entity' => 'user',
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'updatedfirst',
            'email' => ''
        );
        $this->run_core_user_import($importdata);

        // Validate that the record was updated and not created due to the "create or update" logic thinking it's a
        // non-existent user.
        $data = array('username' => 'rlipusername', 'firstname' => 'updatedfirst', 'email' => 'rlipuser@rlipdomain.com');
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate that when the "create or update" flag is enabled, delete
     * actions still work for a course
     */
    public function test_version1createorupdatedeletescourse() {
        global $DB;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Validate that the standard delete action still works first create course.
        $expecteddata = array('shortname' => 'rlipshortname', 'fullname' => 'rlipfullname');
        $importdata = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $this->run_core_course_import($importdata);
        $this->assert_record_exists('course', $expecteddata);
        // Now delete course.
        $importdata = array('entity' => 'course', 'action' => 'delete', 'shortname' => 'rlipshortname');
        $this->run_core_course_import($importdata);
        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that when the "create or update" flag is enabled, delete
     * actions still work for a course
     */
    public function test_version1createorupdatedeletesuser() {
        global $CFG, $DB;
        $CFG->siteguest = 0;

        // Set up initial conditions.
        set_config('createorupdate', 1, 'dhimport_version1');

        // Validate that the standard delete action still works first create the user.
        $expecteddata = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $importdata = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!1234',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $this->run_core_user_import($importdata);
        $this->assert_record_exists('user', $expecteddata);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $importdata['username']));
        $this->assertTrue(validate_internal_user_password($userrec, $importdata['password']));

        // Then delete the user.
        $importdata = array('entity' => 'user', 'action' => 'delete', 'username' => 'rlipusername');
        $this->run_core_user_import($importdata);
        $all = $DB->get_records('user');
        $exists = $DB->record_exists('user', array('username' => 'rlipusername', 'deleted' => 0));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate legacy support using "add" for action with create or update
     */
    public function test_version1createorupdateaddaction() {
        set_config('createorupdate', 1, 'dhimport_version1');

        $this->create_test_user();

        $importdata = array(
            'entity' => 'user',
            'action' => 'add',
            'username' => 'rlipusername',
            'firstname' => 'updatedfirst',
            'email' => ''
        );

        $this->run_core_user_import($importdata);

        $data = array('username' => 'rlipusername', 'firstname' => 'updatedfirst', 'email' => 'rlipuser@rlipdomain.com');

        // Validate that the record was updated.
        $this->assert_record_exists('user', $data);
    }
}