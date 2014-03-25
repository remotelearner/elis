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
$file = get_plugin_directory('dhfile', 'csv').'/csv.class.php';
require_once($file);
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1/version1.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

/**
 * Class for testing database logging with the version 1 plugin
 * @group local_datahub
 * @group dhimport_version1
 */
class version1databaselogging_testcase extends rlip_test {

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or null to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        if ($message === null) {
            $message = 'All lines from import file memoryfile were successfully processed.';
        }

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => $message);
        return $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
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
     * Run the user import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_user_import($data) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_loguser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the course import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_course_import($data) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_logcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the enrolment import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_enrolment_import($data) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $provider = new rlipimport_version1_importprovider_logenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * create
     */
    public function test_version1dblogginglogssuccessmessageonusercreate() {
        $data = array(
            'entity' => 'user',
            'action' => 'create',
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

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user create
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfailedusercreate() {
        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'boguscountry'
        );
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * update
     */
    public function test_version1dblogginglogssuccessmessageonuserupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        $data = array(
            'entity' => 'user',
            'action' => 'create',
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

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array(
            'entity' => 'user',
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'rlipfirstname2'
        );
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user update
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfaileduserupdate() {
        $data = array(
            'entity' => 'user',
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'rlipfirstname2'
        );
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * delete
     */
    public function test_version1dblogginglogssuccessmessageonuserdelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        set_config('siteguest', 0);
        set_config('siteadmins', 0);

        $data = array(
            'entity' => 'user',
            'action' => 'create',
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

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array(
            'entity' => 'user',
            'action' => 'delete',
            'username' => 'rlipusername'
        );
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user delete
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfaileduserdelete() {
        $data = array(
            'entity' => 'user',
            'action' => 'delete',
            'username' => 'rlipusername'
        );
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * create
     */
    public function test_version1dblogginglogssuccessmessageoncoursecreate() {
        global $CFG, $DB;

        // New config settings needed for course format refactoring in 2.4.
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course create
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfailedcoursecreate() {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory',
            'format' => 'bogusformat'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * update
     */
    public function test_version1dblogginglogssuccessmessageoncourseupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        // Prevent problem with cached contexts.
        accesslib_clear_all_caches(true);

        $data = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array(
            'entity' => 'course',
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname2'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course update
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfailedcourseupdate() {
        global $CFG;

        $data = array(
            'entity' => 'course',
            'action' => 'update',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname2'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * delete
     */
    public function test_version1dblogginglogssuccessmessageoncoursedelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        // Prevent problem with cached contexts.
        accesslib_clear_all_caches(true);

        $data = array(
            'entity' => 'course',
            'action' => 'create',
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => 'rlipcategory'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array(
            'entity' => 'course',
            'action' => 'delete',
            'shortname' => 'rlipshortname'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course delete
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfailedcoursedelete() {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = array(
            'entity' => 'course',
            'action' => 'delete',
            'shortname' => 'rlipshortname'
        );
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * create
     */
    public function test_version1dblogginglogssuccessmessageonenrolmentcreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));
        set_config('gradebookroles', "{$roleid}");

        $data = array(
            'entity' => 'enrolment',
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment create
     */
    public function test_version1dbloggingdoesnotlogsuccessmessageonfailedenrolmentcreate() {
        global $CFG;

        $data = array(
            'entity' => 'enrolment',
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * delete
     */
    public function test_version1dblogginglogssuccessmessageonenrolmentdelete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Prevent problem with cached contexts.
        accesslib_clear_all_caches(true);

        // Set up config values.
        set_config('enrol_plugins_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));
        $syscontext = context_system::instance();

        $data = array(
            'entity' => 'enrolment',
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);

        $data = array(
            'entity' => 'enrolment',
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment delete
     */
    public function tesv_ersion1tdbloggingdoesnotlogsuccessmessageonfailedenrolmentdelete() {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $data = array(
            'entity' => 'enrolment',
            'action' => 'delete',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'rlipshortname'
        );
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging includes the correct file name / path in the
     * success summary log message
     */
    public function test_version1dblogginglogscorrectfilenameonsuccess() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        $provider = new rlipimport_version1_importprovider_loguser_dynamic($data, 'fileone');

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file fileone were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);

        // Prevent db conflicts.
        $DB->delete_records(RLIP_LOG_TABLE);
        $DB->delete_records('user');

        $provider = new rlipimport_version1_importprovider_loguser_dynamic($data, 'filetwo');

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file filetwo were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging object correctly persists values and resets its state
     * when flushing data to the DB
     */
    public function test_version1dbloggingsuccesstrackingstorescorrectvaluesviaapi() {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set up the logger object.
        $logger = new rlip_dblogger_import();

        // Provide appropriate times.
        $logger->set_plugin('plugin');
        $logger->set_targetstarttime(1000000000);
        $logger->set_starttime(1000000001);
        $logger->set_endtime(1000000002);

        // Give it one of each "status".
        $logger->track_success(true, true);
        $logger->track_success(true, false);
        $logger->track_success(false, true);
        $logger->track_success(false, false);

        // Specify number of db ops.
        $logger->set_dbops(5);

        $logger->signal_unmetdependency();

        // Validate setup.
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 1000000001);
        $this->assertEquals($logger->endtime, 1000000002);
        $this->assertEquals($logger->filesuccesses, 1);
        $this->assertEquals($logger->filefailures, 1);
        $this->assertEquals($logger->storedsuccesses, 1);
        $this->assertEquals($logger->storedfailures, 1);
        $this->assertEquals($logger->dbops, 5);
        $this->assertEquals($logger->unmetdependency, 1);

        // Flush.
        $logger->flush('bogusfilename');

        // Validate that the values were correctly persisted.
        $params = array(
            'plugin' => 'plugin',
            'userid' => $USER->id,
            'targetstarttime' => 1000000000,
            'starttime' => 1000000001,
            'endtime' => 1000000002,
            'filesuccesses' => 1,
            'filefailures' => 1,
            'storedsuccesses' => 1,
            'storedfailures' => 1,
            'dbops' => 5,
            'unmetdependency' => 1
        );
        $this->assert_record_exists(RLIP_LOG_TABLE, $params);

        // Validate that the state is reset.
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 0);
        $this->assertEquals($logger->endtime, 0);
        $this->assertEquals($logger->filesuccesses, 0);
        $this->assertEquals($logger->filefailures, 0);
        $this->assertEquals($logger->storedsuccesses, 0);
        $this->assertEquals($logger->storedfailures, 0);
        $this->assertEquals($logger->dbops, -1);
        $this->assertEquals($logger->unmetdependency, 0);
    }

    /**
     * Validate that correct values are stored after an actual run of a
     * "version 1" import
     */
    public function test_version1dbloggingstorescorrectvaluesonrun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Capture the earliest possible start time.
        $mintime = time();

        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname',
                    'lastname' => 'rliplastname',
                    'email' => 'rlipuser@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'CA'
                ),
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername2',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname2',
                    'lastname' => 'rliplastname2',
                    'email' => 'rlipuse2r@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'boguscountry'
                )
        );

        // Run the import.
        $provider = new rlipimport_version1_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        // Capture the latest possible end time.
        $maxtime = time();

        // Validate that values were persisted correctly.
        $select = "plugin = :plugin AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   starttime >= :minstarttime AND
                   starttime <= :maxstarttime AND
                   endtime >= :minendtime AND
                   endtime <= :maxendtime";
        $params = array(
            'plugin' => 'dhimport_version1',
            'filesuccesses' => 1,
            'filefailures' => 1,
            'minstarttime' => $mintime,
            'maxstarttime' => $maxtime,
            'minendtime' => $mintime,
            'maxendtime' => $maxtime
        );
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that filenames are correctly stored when an import is run from
     * a file on the file system
     */
    public function test_version1dbloggingstorescorrectfilenameonrun() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set the log file name to a fixed value.
        $filepath = $CFG->dataroot;

        // Set up a "user" import provider, using a single fixed file.
        // MUST copy file to temp area 'cause it'll be deleted after import.
        $testfile = dirname(__FILE__).'/fixtures/userfile.csv';
        $tempdir = $CFG->dataroot.'/local_datahub_phpunit/';
        $file = $tempdir.'userfile.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlipimport_version1_importprovider_file($file, 'user');

        // Run the import.
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        // Data validation.
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);

        // Clean-up data file & tempdir.
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that MANUAL import obeys maxruntime
     */
    public function test_version1manualimportobeysmaxruntime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/tests/other/csv_delay.class.php');
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        // Set the log file name to a fixed value.
        $filepath = $CFG->dataroot;
        // Set up a "user" import provider, using a single fixed file.
        // MUST copy file to temp area 'cause it'll be deleted after import.
        $testfile = dirname(__FILE__).'/fixtures/userfile2.csv';
        $tempdir = $CFG->dataroot.'/local_datahub_phpunit/';
        $file = $tempdir.'userfile2.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlipimport_version1_importprovider_manual_delay($file, 'user');

        // Run the import.
        $importplugin = new rlip_importplugin_version1($provider, true);
        ob_start();
        $result = $importplugin->run(0, 0, 1); // Maxruntime 1 sec.
        $ui = ob_get_contents();
        ob_end_clean();
        $this->assertNotNull($result);
        $expectedui = "/.*generalbox.*Failed importing all lines from import file.*due to time limit exceeded.*/";
        $this->assertRegExp($expectedui, $ui);

        // Clean-up data file & tempdir.
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that scheduled import obeys maxruntime
     */
    public function test_version1scheduledimportobeysmaxruntime() {
        global $CFG, $DB;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        // Set the log file name to a fixed value.
        $filepath = $CFG->dataroot;

        // Set up a "user" import provider, using a single fixed file.
        // MUST copy file to temp area 'cause it'll be deleted after import.
        $testfile = dirname(__FILE__).'/fixtures/userfile2.csv';
        $tempdir = $CFG->dataroot.'/local_datahub_phpunit/';
        $file = $tempdir.'userfile2.csv';
        @mkdir($tempdir, 0777, true);
        @copy($testfile, $file);
        $provider = new rlip_importprovider_file_delay($file, 'user');

        // Run the import.
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run(0, 0, 1); // Maxruntime 1 sec.
        $this->assertNotNull($result);
        if (!empty($result)) {
            $this->assertFalse($result->result);
            $this->assertEquals($result->entity, 'user');
            $this->assertEquals($result->filelines, 4);
            $this->assertEquals($result->linenumber, 1);
        }

        // Clean-up data file & test dir.
        @unlink($file);
        @rmdir($tempdir);
    }

    /**
     * Validate that import starts from saved state
     */
    public function test_version1importfromsavedstate() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        // Set up the import file path & entities filenames.
        // Note: schedule_files_path now relative to $CFG->dataroot
        //       must copy them there.
        $relimportpath = '/local_datahub_phpunit/';
        $testdir = $CFG->dataroot.$relimportpath;
        set_config('schedule_files_path', $relimportpath, 'dhimport_version1');
        set_config('user_schedule_file', 'userfile2.csv', 'dhimport_version1');
        set_config('course_schedule_file', 'course.csv', 'dhimport_version1');
        set_config('enrolment_schedule_file', 'enroll.csv', 'dhimport_version1');
        @copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures/userfile2.csv', $testdir.'userfile2.csv');
        @copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures/course.csv', $testdir.'course.csv');
        @copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures/enroll.csv', $testdir.'enroll.csv');

        // Create a scheduled job.
        $data = array(
            'plugin' => 'dhimport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'dhimport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = $DB->get_record(RLIP_SCHEDULE_TABLE, array('plugin' => 'dhimport_version1'));
        $job->nextruntime = 99;
        $state = new stdClass;
        $state->result = false;
        $state->entity = 'user';
        $state->filelines = 4;
        $state->linenumber = 3; // Should start at line 3 of userfile2.csv.
        $ipjobdata = unserialize($job->config);
        $ipjobdata['state'] = $state;
        $job->config = serialize($ipjobdata);
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // MUST copy the userfile2.csv file to process into temp location
        // ... where it would be left if state != NULL.
        $temppath = sprintf($CFG->dataroot.RLIP_IMPORT_TEMPDIR, 'dhimport_version1');
        mkdir($CFG->dataroot.'/datahub/dhimport_version1');
        mkdir($CFG->dataroot.'/datahub/dhimport_version1/temp');
        copy(dirname(__FILE__).'/fixtures/userfile2.csv', $temppath.'/userfile2.csv');

        // Run the import.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);
        // Verify the 1st & 2nd lines were NOT processed.
        $notexists1 = $DB->record_exists('user', array('username' => 'testusername'));
        $this->assertFalse($notexists1);
        $notexists2 = $DB->record_exists('user', array('username' => 'testusername2'));
        $this->assertFalse($notexists2);
        $exists = $DB->record_exists('user', array('username' => 'testusername3'));
        $this->assertTrue($exists);

        // Clean-up data files.
        @unlink($testdir.'userfile2.csv');
        @unlink($testdir.'course.csv');
        @unlink($testdir.'enroll.csv');
    }

    /**
     * Validate that filenames are correctly stored when an import is run
     * based on a Moodle file-system file
     */
    public function test_version1dbloggingstorescorrectfilenameonrunwithmoodlefile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_importprovider_moodlefile.class.php');

        // Set the filepath to a fixed value.
        $filepath = $CFG->dataroot;

        // Store it at the system context.
        $context = context_system::instance();

        // File path and name.
        $filepath = $CFG->dirroot.'/local/datahub/importplugins/version1/tests/fixtures/';
        $filename = 'userfile.csv';

        // File information.
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'system',
            'filearea'  => 'draft',
            'itemid'    => 9999,
            'filepath'  => $filepath,
            'filename'  => $filename
        );

        // Create a file in the Moodle file system with the right content.
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, "{$filepath}{$filename}");
        $fileid = $DB->get_field_select('files', 'id', "filename = '{$filename}'");
        // Run the import.
        $entitytypes = array('user', 'bogus', 'bogus');
        $fileids = array($fileid, false, false);
        $provider = new rlip_importprovider_moodlefile($entitytypes, $fileids);
        $importplugin = new rlip_importplugin_version1($provider);

        // Buffer output due to summary display.
        ob_start();
        $result = $importplugin->run();
        ob_end_clean();
        $this->assertNull($result);

        // Data validation.
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertEquals($exists, true);
    }


    /**
     * Validate that DB logging does not log a success message when a mixtures
     * of successes and failures is encountered
     */
    public function test_version1dbloggingdoesnotlogsuccessonmixedresults() {
        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname',
                    'lastname' => 'rliplastname',
                    'email' => 'rlipuser@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'CA'
                ),
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername2',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname2',
                    'lastname' => 'rliplastname2',
                    'email' => 'rlipuse2r@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'boguscountry'
                )
        );

        $provider = new rlipimport_version1_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging records the correct number of successes and
     * failues from import file
     */
    public function test_version1dblogginglogscorrectcountsformanualimport() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname',
                    'lastname' => 'rliplastname',
                    'email' => 'rlipuser@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'CA'
                ),
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername2',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname2',
                    'lastname' => 'rliplastnam2e',
                    'email' => 'rlipuser2@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'boguscountry'
                ),
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername3',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname3',
                    'lastname' => 'rliplastname3',
                    'email' => 'rlipuser3@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'boguscountry'
                )
        );

        $provider = new rlipimport_version1_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('filesuccesses' => 1,
                                                           'filefailures' => 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate import accepts full country name in addition to country code
     */
    public function test_version1acceptsfullcountryonimport() {
        global $DB;

        $data = array(
            'entity'    => 'user',
            'action'    => 'create',
            'username'  => 'rlipusername',
            'password'  => 'Rlippassword!0',
            'firstname' => 'rlipfirstname',
            'lastname'  => 'rliplastname',
            'email'     => 'rlipuser@rlipdomain.com',
            'city'      => 'rlipcity',
            'country'   => 'Canada'
        );

        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $newuser = $DB->get_record('user', array('username' => $data['username']));
        $this->assertFalse(empty($newuser));
        $this->assertTrue($newuser->country == 'CA');
    }

    /**
     * Validate that DB logging stores the current user id when processing
     * import files
     */
    public function test_version1dblogginglogscorrectuseridformanualimport() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        $USER->id = 9999;

        $data = array(
            'entity' => 'user',
            'action' => 'create',
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

        $exists = $DB->record_exists(RLIP_LOG_TABLE, array('userid' => $USER->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validates the standard failure message
     */
    public function test_version1dblogginglogsfailuremessage() {
        set_config('createorupdate', 0, 'dhimport_version1');

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
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that database logging works as specified for scheduled import
     * tasks
     */
    public function test_version1dbloggingsetsallfieldsduringscheduledimportrun() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_importprovider_moodlefile.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        $DB->delete_records('local_eliscore_sched_tasks');
        $DB->delete_records(RLIP_SCHEDULE_TABLE);

        // Set the file path to a fixed value.
        $filepath = $CFG->dataroot;

        // Store it at the system context.
        $context = context_system::instance();

        // File path and name.
        $filename = 'userscheduledimport.csv';
        // File WILL BE DELETED after import so must copy to moodledata area
        // Note: file_path now relative to moodledata ($CFG->dataroot).
        $filepath = '/local_datahub_phpunit/';
        $testdir = $CFG->dataroot.$filepath;
        @mkdir($testdir, 0777, true);
        @copy(dirname(__FILE__)."/fixtures/{$filename}", $testdir.$filename);

        // Create a scheduled job.
        $data = array(
            'plugin' => 'dhimport_version1',
            'period' => '5m',
            'label' => 'bogus',
            'type' => 'dhimport'
        );
        $taskid = rlip_schedule_add_job($data);

        // Change the next runtime to a known value in the past.
        $task = new stdClass;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('local_eliscore_sched_tasks', $task);

        $job = new stdClass;
        $job->id = $DB->get_field(RLIP_SCHEDULE_TABLE, 'id', array('plugin' => 'dhimport_version1'));
        $job->nextruntime = 99;
        $DB->update_record(RLIP_SCHEDULE_TABLE, $job);

        // Lower bound on starttime.
        $starttime = time();

        // Set up config for plugin so the scheduler knows about our csv file.
        set_config('schedule_files_path', $filepath, 'dhimport_version1');
        set_config('user_schedule_file', $filename, 'dhimport_version1');

        // Run the import.
        $taskname = $DB->get_field('local_eliscore_sched_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);

        $message = 'One or more lines from import file userscheduledimport.csv failed because they contain data errors. ';
        $message .= 'Please fix the import file and re-upload it.';

        // Upper bound on endtime.
        $endtime = time();

        // Condition for the logpath.
        $like = $DB->sql_like('logpath', ':logpath');

        // Data validation.
        $select = "export = :export AND
                   plugin = :plugin AND
                   userid = :userid AND
                   targetstarttime = :targetstarttime AND
                   starttime >= :starttime AND
                   endtime <= :endtime AND
                   endtime >= starttime AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   storedsuccesses = :storedsuccesses AND
                   storedfailures = :storedfailures AND
                   {$DB->sql_compare_text('statusmessage')} = :statusmessage AND
                   dbops = :dbops AND
                   unmetdependency = :unmetdependency AND
                   {$like} AND
                   entitytype = :entitytype";
        $params = array(
            'export' => 0,
            'plugin' => 'dhimport_version1',
            'userid' => $USER->id,
            'targetstarttime' => 99,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'filesuccesses' => 2,
            'filefailures' => 2,
            'storedsuccesses' => 0,
            'storedfailures' => 0,
            'statusmessage' => $message,
            'dbops' => -1,
            'unmetdependency' => 0,
            'logpath' => "{$CFG->dataroot}/%",
            'entitytype' => 'user'
        );

        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);
        $this->assertTrue($exists);
        // Verify completed import deletes input csv file.
        $this->assertFalse(file_exists($testdir.$filename));

        // Clean-up data file & test dir.
        @unlink($testdir.$filename);
        @rmdir($testdir);
    }

    /**
     * Validation for log end times
     */

    /**
     * Validate that summary log end time is set when an invalid folder is set
     * for the file system log
     */
    public function test_nonwritablelogpathlogscorrectendtime() {
        global $DB;

        set_config('logfilelocation', 'adirectorythatshouldnotexist', 'dhimport_version1');

        $data = array(
            'action'    => 'create',
            'username'  => 'testuserusername',
            'password'  => 'Testpassword!0',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'city'      => 'testcity',
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
    public function test_missingactioncolumnlogscorrectendtime() {
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
    public function test_missingrequiredcolumnlogscorrectendtime() {
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
    public function test_maxruntimeexceededlogscorrectendtime() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/tests/other/csv_delay.class.php');
        require_once($CFG->dirroot.'/local/datahub/tests/other/file_delay.class.php');

        $importfile = $CFG->dirroot.'/local/datahub/importplugins/version1/tests/fixtures/userfiledelay.csv';
        $provider = new rlip_importprovider_file_delay($importfile, 'user');

        // Run the import.
        $mintime = time();
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
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
    public function test_successfulprocessinglogscorrectendtime() {
        global $DB;

        $data = array(
            'action'    => 'create',
            'username'  => 'testuserusername',
            'password'  => 'Testpassword!0',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'city'      => 'testcity',
            'country'   => 'CA'
        );

        $mintime = time();
        $this->run_user_import($data);
        $maxtime = time();

        $record = $DB->get_record(RLIP_LOG_TABLE, array('id' => 1));
        $this->assertGreaterThanOrEqual($mintime, $record->endtime);
        $this->assertLessThanOrEqual($maxtime, $record->endtime);
    }
}
