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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

/**
 * Class for version 1 enrolment import correctness
 * @group local_datahub
 * @group dhimport_version1
 */
class version1enrolmentimport_testcase extends rlip_test {
    public static $courseroleid;
    public static $coursecatroleid;
    public static $userroleid;
    public static $systemroleid;
    public static $userid;
    public static $courseid;
    public static $allcontextroleid;

    /**
     * Called before each test function.
     */
    protected function setUp() {
        global $DB;
        parent::setUp();

        self::$courseroleid = self::create_test_role();
        $syscontext = context_system::instance();

        self::$systemroleid = self::create_test_role('systemname', 'systemshortname', 'systemdescription', array(CONTEXT_SYSTEM));
        self::$coursecatroleid = self::create_test_role('coursecatname', 'coursecatshortname', 'coursecatdescription',
                array(CONTEXT_COURSECAT));
        self::$userroleid = self::create_test_role('username', 'usershortname', 'userdescription', array(CONTEXT_USER));
        self::$allcontextroleid = self::create_test_role('allname', 'allshortname', 'alldescription', array(
            CONTEXT_SYSTEM,
            CONTEXT_COURSE,
            CONTEXT_COURSECAT,
            CONTEXT_USER
        ));
        self::$courseid = self::create_test_course();
        self::$userid = self::create_test_user();
        self::get_csv_files();
        self::get_logfilelocation_files();
        self::get_zip_files();
    }

    /**
     * Create a test role
     *
     * @param string $name The role's display name
     * @param string $shortname The role's shortname
     * @param string $description The role's description
     * @param array $contexts The contexts at which the role should be assignable (defaults to course.
     * @return int The created role's id
     */
    private static function create_test_role($name = 'coursename', $shortname = 'courseshortname',
                                             $description = 'coursedescription', $contexts = null) {
        if ($contexts === null) {
            // Use default of course context.
            $contexts = array(CONTEXT_COURSE);
        }

        $roleid = create_role($name, $shortname, $description);
        set_role_contextlevels($roleid, $contexts);

        return $roleid;
    }

    /**
     * Create a test course
     *
     * @param array $extradata Extra field values to set on the course
     * @return int The created course's id
     */
    private static function create_test_course($extradata = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        if (!$category = $DB->get_record('course_categories', array('name' => 'rlipcategory'))) {
            $category = new stdClass;
            $category->name = 'rlipcategory';
            $category->id = $DB->insert_record('course_categories', $category);
        }

        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $category->id;

        foreach ($extradata as $key => $value) {
            $course->$key = $value;
        }

        $course = create_course($course);

        return $course->id;
    }

    /**
     * Create a test user
     *
     * @param array $extradata Extra field values to set on the user
     * @return int The created user's id
     */
    private function create_test_user($extradata = array()) {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';

        foreach ($extradata as $key => $value) {
            $user->$key = $value;
        }

        return user_create_user($user);
    }

    /**
     * Creates a group for testing purposes
     *
     * @param int $courseid The id of the course where the group should live
     * @param string $name The name of the group to create
     * @return int The id of the group created
     */
    private function create_test_group($courseid, $name = 'rlipgroup') {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        $data = new stdClass;
        $data->courseid = $courseid;
        $data->name = $name;

        return groups_create_group($data);
    }

    /**
     * Creates a grouping for testing purposes
     *
     * @param int $courseid The id of the course where the grouping should live
     * @param string $name The name of the grouping to create
     * @return int The id of the grouping created
     */
    private function create_test_grouping($courseid, $name = 'rlipgrouping') {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        $data = new stdClass;
        $data->courseid = $courseid;
        $data->name = $name;

        return groups_create_grouping($data);
    }

    /**
     * Asserts, using PHPunit, that no course enrolments exist
     */
    private function assert_no_enrolments_exist() {
        global $DB;

        $exists = $DB->record_exists('user_enrolments', array());
        $this->assertEquals($exists, false);
    }

    /**
     * Asserts, using PHPunit, that no role assignments exist
     */
    private function assert_no_role_assignments_exist() {
        global $DB;

        $exists = $DB->record_exists('role_assignments', array());
        $this->assertEquals($exists, false);
    }

    /**
     * Helper function to get the core fields for a sample enrolment
     *
     * @return array The enrolment data
     */
    private function get_core_enrolment_data() {
        $data = array(
            'entity' => 'enrolment',
            'action' => 'create',
            'username' => 'rlipusername',
            'context' => 'course',
            'instance' => 'rlipshortname',
            'role' => 'courseshortname',
            'enrolmenttime' => date('m/d/Y', strtotime(date('Y/m/d 00:00:00').' -1 day')),
            'completetime' => date('m/d/Y', strtotime(date('Y/m/d 00:00:00').' +1 day'))
        );
        return $data;
    }

    /**
     * Helper function that runs the enrolment import for a sample enrolment
     *
     * @param array $extradata Extra fields to set for the new course.
     * @param bool $usedefaultdata Whether to use core enrolment data.
     */
    private function run_core_enrolment_import($extradata, $usedefaultdata = true) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1').'/version1.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_enrolment_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1_importprovider_mockenrolment($data);

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
        $this->assertTrue($exists);
    }

    /**
     * Validate that the version 1 plugin supports enrolment actions
     */
    public function test_version1importsupportsenrolmentactions() {
        $supports = plugin_supports('dhimport', 'version1', 'enrolment');
        $this->assertEquals($supports, array('create', 'add', 'delete'));
    }

    /**
     * Validate that the version 1 plugin supports the enrolment create action
     */
    public function test_version1importsupportsenrolmentcreate() {
        $supports = plugin_supports('dhimport', 'version1', 'enrolment_create');
        $requiredfields = array(
                array('username', 'email', 'idnumber'),
                'context',
                'instance',
                'role'
        );

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports the enrolment add action
     */
    public function test_version1importsupportsenrolmentadd() {
        $supports = plugin_supports('dhimport', 'version1', 'enrolment_add');
        $requiredfields = array(
                array('username', 'email', 'idnumber'),
                'context',
                'instance',
                'role'
        );

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports the enrolment delete action
     */
    public function test_version1importsupportsenrolmentdelete() {
        $supports = plugin_supports('dhimport', 'version1', 'enrolment_delete');
        $requiredfields = array(
                array('username', 'email', 'idnumber'),
                'context',
                'instance',
                'role'
        );

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that required fields are set to specified values during
     * course-level role assignment creation
     */
    public function test_version1importsetsrequiredcourseroleassignmentfieldsoncreate() {
        // Run the import.
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = self::$courseroleid;

        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;

        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * system-level role assignment creation
     */
    public function test_version1importsetsrequiredsystemroleassignmentfieldsoncreate() {
        global $DB;
        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'systemshortname';
        unset($data['instance']);
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$systemroleid;

        $systemcontext = context_system::instance();
        $data['contextid'] = $systemcontext->id;

        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * category-level role assignment creation
     */
    public function test_version1importsetsrequiredcoursecategoryroleassignmentfieldsoncreate() {
        global $DB;

        // Run the import.
        $category = new stdClass;
        $category->name = 'requiredcategoryfields';
        $category->id = $DB->insert_record('course_categories', $category);

        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'requiredcategoryfields';
        $data['role'] = 'coursecatshortname';
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$coursecatroleid;

        $categorycontext = context_coursecat::instance($category->id);
        $data['contextid'] = $categorycontext->id;

        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * user-level role assignment creation
     */
    public function test_version1importsetsrequireduserroleassignmentfieldsoncreate() {
        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'usershortname';
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$userroleid;

        $usercontext = context_user::instance(self::$userid);
        $data['contextid'] = $usercontext->id;

        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the import sets correct enrolment and complete time on enrolment creation
     * @uses $DB
     */
    public function test_version1importsetsenrolmentandcompletetimeoncreate() {
        global $DB;
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        $timestart = strtotime(date('Y/m/d 00:00:00').' -1 day');
        $exists = $DB->record_exists('user_enrolments', array('timestart' => $timestart));
        $this->assertEquals($exists, true);

        $timeend = strtotime(date('Y/m/d 00:00:00').' +1 day');
        $exists = $DB->record_exists('user_enrolments', array('timeend' => $timeend));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that invalid username values can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidenrolmentusernameoncreate() {
        $this->run_core_enrolment_import(array('username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid email values can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidenrolmentemailoncreate() {
        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid idnumber values can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidenrolmentidnumberoncreate() {
        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid context level values can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidcontextoncreate() {
        $this->run_core_enrolment_import(array('context' => 'boguscontext'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that roles that are not assignable at the required contextlevel
     * can't be set on enrolment creation
     */
    public function test_version1importpreventsunassignablecontextoncreate() {
        $this->run_core_enrolment_import(array('role' => 'systemshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid course context instance values can't be set on
     * role assignment creation
     */
    public function test_version1importpreventsinvalidcourseinstanceoncreate() {
        $this->run_core_enrolment_import(array('instance' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid enrolment time value can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidenrolmenttimeoncreate() {
        $this->run_core_enrolment_import(array('enrolmenttime' => 'bogusenrolmenttime'));
        $this->assert_no_role_assignments_exist();
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that invalid complete time value can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidcompletetimeoncreate() {
        $this->run_core_enrolment_import(array('completetime' => 'boguscompletetime'));
        $this->assert_no_role_assignments_exist();
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that invalid category context instance values can't be set on
     * role assignment creation
     */
    public function test_version1importpreventsinvalidcoursecategoryinstanceoncreate() {
        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'boguscategory';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that ambiguous category context instance values can't be set on
     * role assignment creation
     */
    public function test_version1importpreventsambiguouscoursecategoryinstanceoncreate() {
        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid user context instance values can't be set on
     * role assignment creation
     */
    public function test_version1importpreventsinvaliduserinstanceoncreate() {
        // Setup.

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'user';
        $data['instance'] = 'bogususername';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid role shortname values can't be set on enrolment creation
     */
    public function test_version1importpreventsinvalidroleoncreate() {
        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();

        $roleid = self::create_test_role('nocontextname', 'nocontextshortname', 'nocontextdescription', array());
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'nocontextshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the import does not set unsupported fields on enrolment creation
     */
    public function test_version1importpreventssettingunsupportedenrolmentfieldsoncreate() {
        global $DB;

        $this->run_core_enrolment_import(array('timemodified' => 12345,
                                               'modifierid' => 12345,
                                               'timestart' => 12345));

        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        $exists = $DB->record_exists('role_assignments', array('timemodified' => 12345));
        $this->assertEquals($exists, false);
        $exists = $DB->record_exists('role_assignments', array('modifierid' => 12345));
        $this->assertEquals($exists, false);

        $exists = $DB->record_exists('user_enrolments', array('timestart' => 12345));
        $this->assertEquals($exists, false);
    }

      /**
       * Validate that the import does not create duplicate role assignment
       * records on creation
       */
    public function test_version1importpreventsduplicateroleassignmentcreation() {
          global $DB;

          $coursecontext = context_course::instance(self::$courseid);
          role_assign(self::$courseroleid, self::$userid, $coursecontext->id);

          // Course.
          $this->assertEquals($DB->count_records('role_assignments'), 1);
          $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);
          $this->assertEquals($DB->count_records('role_assignments'), 1);

          // System.
          $systemcontext = context_system::instance();
          role_assign(self::$systemroleid, self::$userid, $systemcontext->id);

          $this->assertEquals($DB->count_records('role_assignments'), 2);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'system';
          $data['role'] = 'systemshortname';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 2);

          // Course category.
          $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
          $categorycontext = context_coursecat::instance($categoryid);
          role_assign(self::$coursecatroleid, self::$userid, $categorycontext->id);

          $this->assertEquals($DB->count_records('role_assignments'), 3);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'coursecat';
          $data['role'] = 'coursecatshortname';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 3);

          // User.
          $usercontext = context_user::instance(self::$userid);
          role_assign(self::$userroleid, self::$userid, $usercontext->id);

          $this->assertEquals($DB->count_records('role_assignments'), 4);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'user';
          $role['role'] = 'usershortname';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 4);
    }

    /**
     * Validate that the import does not create duplicate enrolment records on
     * creation when the role is not assigned but the user is enrolled
     */
    public function test_version1importpreventsduplicateenrolmentcreation() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Enrol the user.
        enrol_try_internal_enrol(self::$courseid, self::$userid);

        // Attempt to re-enrol.
        $this->run_core_enrolment_import(array());

        // Compare data.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that the import does not create duplicate enrolment or role
     * assignment records when the role is assigned and the user is enrolled
     */
    public function test_version1importpreventsduplicateenrolmentandroleassignmentcreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Enrol the user.
        enrol_try_internal_enrol(self::$courseid, self::$userid, self::$courseroleid);

        // Attempt to re-enrol.
        $this->run_core_enrolment_import(array());

        // Compare data.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that the import enrols students using the 2.0 mechanism when
     * appropriate
     */
    public function test_version1importenrolsappropriateuserasstudent() {
        global $DB;

        $this->run_core_enrolment_import(array());

        // Compare data.
        // User enrolment.
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => self::$courseid));
        $this->assert_record_exists('user_enrolments', array('userid' => self::$userid,
                                                             'enrolid' => $enrolid));

        // Role assignment.
        $coursecontext = context_course::instance(self::$courseid);
        $this->assert_record_exists('role_assignments', array(
            'userid' => self::$userid,
            'roleid' => self::$courseroleid,
            'contextid' => $coursecontext->id
        ));
    }

    /**
     * Validate that setting up course enrolments only works within the course
     * context
     */
    public function test_version1importenrolmentsarecoursecontextspecific() {
        global $DB;

        $this->create_test_role('globalstudentname', 'globalstudentshortname', 'globalstudentdescription', array(CONTEXT_SYSTEM,
                                                                                                                 CONTEXT_COURSE,
                                                                                                                 CONTEXT_COURSECAT,
                                                                                                                 CONTEXT_USER));

        // Run the system-level import.
        // $this->create_test_user();.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assert_no_enrolments_exist();

        // Run the category-level import.
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assert_no_enrolments_exist();

        // Run the user-level import.
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 3);
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username
     */
    public function test_version1importcreatesenrolmentbasedonusername() {
        // Run the import.
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = self::$courseroleid;

        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;

        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email
     */
    public function test_version1importcreatesenrolmentbasedonemail() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'add',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * idnumber
     */
    public function test_version1importcreatesenrolmentbasedonidnumber() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and email
     */
    public function test_version1importcreatesenrolmentbasedonusernameemail() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and idnumber
     */
    public function test_version1importcreatesenrolmentbasedonusernameidnumber() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email and idnumber
     */
    public function test_version1importcreatesenrolmentbasedonemailidnumber() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username, email and idnumber
     */
    public function test_version1importcreatesenrolmentbasedonusernameemailidnumber() {
        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        // Compare data.
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified email is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalidusernameinvalidemail() {
        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalidusernameinvalididnumber() {
        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified username is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalidemailinvalidusername() {
        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalidemailinvalididnumber() {
        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified username is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalididnumberinvalidusername() {
        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified email is incorrect
     */
    public function test_version1importdoesnotcreateenrolmentforvalididnumberinvalidemail() {
        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that a user can still be enroled in a course even if they
     * already have a role assignment in that course
     */
    public function test_version1importenrolsuseralreadyassignedarole() {
        global $DB;

        $context = context_course::instance(self::$courseid);

        role_assign(self::$courseroleid, self::$userid, $context->id);

        $this->run_core_enrolment_import(array());

        $this->assert_record_exists('user_enrolments', array());
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that a user can still be assigned a role in a course even if
     * they are enroled in it
     */
    public function test_version1importroleassignsalreadyenrolleduser() {
        global $DB;

        // Set up just an enrolment.
        enrol_try_internal_enrol(self::$courseid, self::$userid);
        // Validate setup.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assert_no_role_assignments_exist();

        // Run the import.
        $this->run_core_enrolment_import(array());

        // Validation.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from taking place
     */
    public function test_version1importroleerrorpreventsenrolment() {
        global $DB;

        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));

        $this->assertEquals($DB->count_records('user_enrolments'), 0);
    }

    /**
     * Validate that the version 1 plugin can create a group and assign a
     * user to it
     */
    public function test_version1importassignsusertocreatedgroup() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('groups', array('name' => 'rlipgroup'));

        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('userid' => self::$userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can assign a user to an existing
     * group
     */
    public function test_version1importassignsusertoexistinggroup() {
        global $DB;

        // Set up the "pre-existing" group.
        $groupid = $this->create_test_group(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assert_record_exists('groups_members', array('userid' => self::$userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can create a group and a grouping,
     * and assign the group to the grouping
     */
    public function test_version1importassignsgreatedgrouptocreatedgrouping() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Run the import.
        $this->run_core_enrolment_import(array('group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        // Compare data.
        $this->assert_record_exists('groups', array('name' => 'rlipgroup'));
        $this->assert_record_exists('groupings', array('name' => 'rlipgrouping'));

        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));
        $groupingid = $DB->get_field('groupings', 'id', array('name' => 'rlipgrouping'));

        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the version 1 plugin can create a grouping and assign an
     * existing group to it
     */
    public function test_version1importassignsexistinggrouptocreatedgrouping() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Set up the "pre-existing" group.
        $groupid = $this->create_test_group(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $groupingid = $DB->get_field('groupings', 'id', array('name' => 'rlipgrouping'));

        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the version 1 plugin can create a group and assign it
     * to an existing grouping
     */
    public function test_version1importassignscreatedgrouptoexistinggrouping() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Set up the "pre-existing" grouping.
        $groupingid = $this->create_test_grouping(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));

        $this->assertEquals($DB->count_records('groupings'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the version 1 plugin can assign an existing group to an
     * existing grouping
     */
    public function test_version1importassignsexistinggrouptoexistinggrouping() {
        global $DB;

        // Set up the "pre-existing" group.
        $groupid = $this->create_test_group(self::$courseid);

        // Set up the "pre-existing" grouping.
        $groupingid = $this->create_test_grouping(self::$courseid);;

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assertEquals($DB->count_records('groupings'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that a role-related error prevents processing of
     * groups and groupings information
     */
    public function test_version1importroleerrorpreventsgroups() {
        global $DB;

        // Run the import.
        $this->run_core_enrolment_import(array('role' => 'bogusshortname',
                                               'group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that processing a duplicate role assignment prevents processing
     * of groups and groupings information
     */
    public function test_version1importduplicateroleassignmentpreventsgroups() {
        global $DB;

        // Run the import.
        $this->run_core_enrolment_import(array());

        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that specifying an ambiguous group name prevents processing of
     * enrolment information
     */
    public function test_version1importambiguousgroupnamepreventsenrolment() {
        // Set up two "pre-existing" groups with the same name in our course.
        $this->create_test_group(self::$courseid);
        $this->create_test_group(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying a non-existent group name prevents processing
     * of enrolment information when not creating groups
     */
    public function test_version1importinvalidgroupnamepreventsenrolment() {
        // Disable group / grouping creation.
        set_config('creategroupsandgroupings', 0, 'dhimport_version1');

        // Run the import.
        $this->run_core_enrolment_import(array('group' => 'rlipgroup'));

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying the name of a group that exists in another
     * course but not the current course does not prevent group creation
     */
    public function test_version1importallowsduplicategroupnamesacrosscourses() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupsacrosscourses'));

        // Set up the "pre-existing" group in another course.
        $this->create_test_group($secondcourseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 2);
        $this->assert_record_exists('groups', array('courseid' => self::$courseid,
                                                    'name' => 'rlipgroup'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => self::$courseid,
                                                        'name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('groupid' => $groupid,
                                                            'userid' => self::$userid));
    }

    /**
     * Vaidate that specifying the name of a group that exists multiple times
     * in another course but does not exist in the current course does not
     * prevent group creation
     */
    public function test_version1importallowsduplicategroupnamesinanothercourse() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Setup.
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupsinaothercourse'));

        // Set up two "pre-existing" groups with the same name in another course.
        $this->create_test_group($secondcourseid);
        $this->create_test_group($secondcourseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 3);
        $this->assert_record_exists('groups', array('courseid' => self::$courseid,
                                                    'name' => 'rlipgroup'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => self::$courseid,
                                                        'name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('groupid' => $groupid,
                                                            'userid' => self::$userid));
    }

    /**
     * Validate that groups functionality only works within the course context
     */
    public function test_version1importgroupsarecoursecontextspecific() {
        global $DB;

        // Enable groups functionality.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Run the system-level import.
        // $this->create_test_user();.
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'systemshortname';
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('groups'), 0);

        // Run the category-level import.
        $data['instance'] = 'rlipcategory';
        $data['context'] = 'coursecat';
        $data['role'] = 'coursecatshortname';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assertEquals($DB->count_records('groups'), 0);

        // Run the user-level import.
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'usershortname';
        $this->run_core_enrolment_import($data, false);

        // Validation.
        $this->assertEquals($DB->count_records('role_assignments'), 3);
        $this->assertEquals($DB->count_records('groups'), 0);
    }

    /**
     * Validate that specifying an ambiguous grouping name prevents processing
     * of enrolment information
     */
    public function test_version1importambiguousgroupingnamepreventsenrolments() {
        // Set up two "pre-existing" groupings in our course.
        $this->create_test_grouping(self::$courseid);
        $this->create_test_grouping(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying a non-existent grouping name prevents
     * processing of enrolment information when not creating groups
     */
    public function test_version1importinvalidgroupingnamepreventsenrolment() {
        // Disable group / grouping creation.
        set_config('creategroupsandgroupings', 0, 'dhimport_version1');

        // Set up the "pre-existing" grouping.
        $this->create_test_grouping(self::$courseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying the name of a grouping that exists in another
     * course but not the current course does not prevent grouping creation
     */
    public function test_version1importallowsduplicategroupingnamesacrosscourses() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Setup.
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupingsacrosscourses'));

        // Set up the "pre-existing" grouping in another course.
        $this->create_test_grouping($secondcourseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groupings'), 2);
        $this->assert_record_exists('groupings', array('courseid' => self::$courseid,
                                                       'name' => 'rlipgrouping'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => self::$courseid,
                                                        'name' => 'rlipgroup'));
        $groupingid = $DB->get_field('groupings', 'id', array('courseid' => self::$courseid,
                                                              'name' => 'rlipgrouping'));

        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Vaidate that specifying the name of a grouping that exists multiple
     * times in another course but does not exist in the current course does
     * not prevent grouping creation
     */
    public function test_version1importallowsduplicategroupingnamesinanothercourse() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Setup.
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupingsinanothercourse'));

        // Set up two "pre-existing" groupings in another course.
        $this->create_test_grouping($secondcourseid);
        $this->create_test_grouping($secondcourseid);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groupings'), 3);
        $this->assert_record_exists('groupings', array('courseid' => self::$courseid,
                                                       'name' => 'rlipgrouping'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => self::$courseid,
                                                        'name' => 'rlipgroup'));
        $groupingid = $DB->get_field('groupings', 'id', array('courseid' => self::$courseid,
                                                              'name' => 'rlipgrouping'));

        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the import prevents assigning a user to the same group
     * twice
     */
    public function test_version1importpreventsduplicateusergroupassignments() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Enrol the user in the course.
        enrol_try_internal_enrol(self::$courseid, self::$userid, self::$courseroleid, 0);

        // Set up the "pre-existing" group.
        $groupid = $this->create_test_group(self::$courseid);

        // Add the user to the group.
        groups_add_member($groupid, self::$userid);

        // Validate setup.
        $this->assertEquals($DB->count_records('groups_members'), 1);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groups_members'), 1);
    }

    /**
     * Validate that the import prevents assigning a user to the same grouping
     * twice
     */
    public function test_version1importpreventsduplicategroupgroupingassignments() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Set up the "pre-existing" group.
        $groupid = $this->create_test_group(self::$courseid);
        // Set up the "pre-existing" grouping.
        $groupingid = $this->create_test_grouping(self::$courseid);

        // Assign the group to the grouping.
        groups_assign_grouping($groupingid, $groupid);

        // Validate setup.
        $this->assertEquals($DB->count_records('groupings_groups'), 1);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('groupings_groups'), 1);
    }

    /**
     * Validate that groups and groupings only work at the course context
     */
    public function test_version1importonlysupportsgroupsforcoursecontext() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Run the import.
        $this->run_core_enrolment_import(array('context' => 'system',
                                               'group' => 'rlipgroup'));

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groups_members'), 0);
    }

    /**
     * Validate that groups are not created when the required setting is not
     * enabled
     */
    public function test_version1importonlycreatesgroupwithsettingenabled() {
        global $DB;

        // Disable group / grouping creation.
        set_config('creategroupsandgroupings', 0, 'dhimport_version1');

        // Run the import.
        $this->run_core_enrolment_import(array('group' => 'rlipgroup'));

        // Compare data.
        $this->assertEquals($DB->count_records('groups'), 0);
    }

    /**
     * Validate that groupings are not created when the required setting is not
     * enabled
     */
    public function test_version1importonlycreatesgroupingwithsettingenabled() {
        global $DB;

        // Disable group / grouping creation.
        set_config('creategroupsandgroupings', 0, 'dhimport_version1');

        // Run the import.
        $this->run_core_enrolment_import(array('group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        // Compare data.
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that course enrolment create action sets start time, time
     * created and time modified appropriately
     */
    public function test_version1importsetsenrolmenttimestamps() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        // Record the current time.
        $starttime = time();

        // Data setup.
        $this->create_test_course(array('shortname' => 'timestampcourse',
                                        'startdate' => 12345));

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['instance'] = 'timestampcourse';
        $this->run_core_enrolment_import($data, false);

        // Ideal enrolment start time.
        $coursestartdate = $DB->get_field('course', 'startdate', array('shortname' => 'timestampcourse'));

        // Validate enrolment record.
        $where = 'timestart = :timestart AND
                  timecreated >= :timecreated AND
                  timemodified >= :timemodified';
        $params = array('timestart' => 12345,
                        'timecreated' => $starttime,
                        'timemodified' => $starttime);
        $exists = $DB->record_exists_select('user_enrolments', $where, $params);

        $this->assertEquals($exists, true);

        // Validate role assignment record.
        $where = 'timemodified >= :timemodified';
        $params = array('timemodified' => $starttime);
        $exists = $DB->record_exists_select('role_assignments', $where, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username,
     * along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonusername() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email,
     * along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonemail() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on idnumber,
     * along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonidnumber() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email,
     * along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonusernameemail() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username
     * and idnumber, along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonusernameidnumber() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email
     * and idnumber, along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonemailidnumber() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username,
     * email and idnumber, along with required non-user fields
     */
    public function test_version1importdeletesenrolmentbasedonusernameemailidnumber() {
        // Set up our enrolment.
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete an enrolment when there
     * is no role assignment tied to it
     */
    public function test_version1importdeletesenrolment() {
        global $DB;

        $this->run_core_enrolment_import(array());
        $DB->delete_records('role_assignments');

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_role_assignments_exist();
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete a course-level role
     * assignment when there is no enrolment tied to it
     */
    public function test_version1importdeletescourseroleassignment() {
        // Set up our enrolment.
        $coursecontext = context_course::instance(self::$courseid);
        role_assign(self::$courseroleid, self::$userid, $coursecontext->id);

        // Validate setup.
        $this->assert_no_enrolments_exist();

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete an enrolment and role
     * assignment at the same time
     */
    public function test_version1importdeletesenrolmentandcourseroleassignment() {
        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete a system-level role
     * assignment
     */
    public function test_version1importdeletessystemroleassignment() {
        global $DB;

        // Setup.
        $context = context_system::instance();
        role_assign(self::$systemroleid, self::$userid, $context->id);

        $this->assertEquals($DB->count_records('role_assignments'), 1);

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'system';
        $data['role'] = 'systemshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 0);
    }

    /**
     * Validate that the version 1 plugin can delete a category-level role
     * assignment
     */
    public function test_version1importdeletescoursecategoryroleassignment() {
        global $DB;

        // Setup.
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = context_coursecat::instance($categoryid);

        // Prevent PM from trying to process instructors.
        set_config('coursecontact', '');

        role_assign(self::$coursecatroleid, self::$userid, $context->id);

        $this->assertEquals($DB->count_records('role_assignments'), 1);

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $data['role'] = 'coursecatshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 0);
    }

    /**
     * Validate that the version 1 plugin can delete a user-level role
     * assignment
     */
    public function test_version1importdeletesuserroleassignment() {
        global $DB;

        // Setup.
        $context = context_user::instance(self::$userid);
        role_assign(self::$userroleid, self::$userid, $context->id);

        $this->assertEquals($DB->count_records('role_assignments'), 1);

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'usershortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 0);
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified username is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithinvalidusername() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified email is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithinvalidemail() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithinvalididnumber() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified context is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithinvalidcontext() {
        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'boguscontext';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete course-level role
     * assignments when the specified instance is incorrect
     */
    public function test_version1importdoesnotdeletecourseroleassignmentwithinvalidinstance() {
        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['instance'] = 'bogusinstance';

        // Run the import.
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete category-level role
     * assignments when the specified instance is incorrect
     */
    public function test_version1importdoesnotdeletecoursecategoryroleassignmentwithinvalidinstance() {
        global $DB;

        // Setup.
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = context_coursecat::instance($categoryid);

        // Set up our role assignment.
        role_assign(self::$coursecatroleid, self::$userid, $context->id);

        // Validate setup.
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'coursecat';
        $data['instance'] = 'boguscategory';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete category-level role
     * assignments when the specified instance is ambiguous
     */
    public function test_version1importdoesnotdeletecoursecategoryroleassignmentwithambiguousinstance() {
        global $DB;

        // Setup.
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = context_coursecat::instance($categoryid);

        // Set up our role assignment.
        role_assign(self::$coursecatroleid, self::$userid, $context->id);

        // Validate setup.
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete user-level role
     * assignments when the specified instance is incorrect
     */
    public function test_version1importdoesnotdeleteuserroleassignmentwithinvalidinstance() {
        global $DB;

        // Setup.
        $context = context_user::instance(self::$userid);

        // Set up our role assignment.
        role_assign(self::$userroleid, self::$userid, $context->id);

        // Validate setup.
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        // Run the import.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'user';
        $data['instance'] = 'bogususername';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified role is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithinvalidrole() {
        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'bogusrole';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified email is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalidusernameinvalidemail() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalidusernameinvalididnumber() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified username is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalidemailinvalidusername() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalidemailinvalididnumber() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified username is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalididnumberinvalidusername() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified email is incorrect
     */
    public function test_version1importdoesnotdeleteenrolmentwithvalididnumberinvalidemail() {
        // Set up our enrolment.
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'rlipidnumber';
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 import plugin handles deletion of
     * non-existent enrolments gracefully
     */
    public function test_version1importpreventsnonexistentroleassignmentdeletion() {
        global $DB;

        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        $this->create_test_role('noassignmentdeletionname', 'noassignmentdeletionshortname', 'noassignmentdeletiondescription');
        $this->create_test_course(array('shortname' => 'noassignmentdeletionshort'));
        $this->create_test_user(array('username' => 'noassignmentdeletionshortname'));

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'noassignmentdeletionusername';
        $data['instance'] = 'noassignmentdeletionshortname';
        $data['role'] = 'noassignmentdeletionshortname';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from being deleted
     */
    public function test_version1importroleerrorpreventsenrolmentdeletion() {
        global $DB;

        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'bogusshortname';
        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that deleting role assignments is specific to the role
     * specified (i.e. does not delete the user's other role assignments
     * on that same entity)
     */
    public function test_version1importenrolmentdeletionisrolespecific() {
        global $DB;

        // Set up our enrolment.
        $this->run_core_enrolment_import(array());

        $roleid = $this->create_test_role('deletionrolespecificname', 'deletionrolespecificshortname',
                'deletionrolespecificdescription');
        $syscontext = context_system::instance();

        // Set up a second enrolment.
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'deletionrolespecificshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 2);

        // Perform the delete action.
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        // Compare data.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on enrolment creation
     */
    public function test_version1importusesenrolmentfieldmappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('dhimport', 'version1').'/lib.php';
        require_once($file);

        // Set up our mapping of standard field names to custom field names.
        $mapping = array('action' => 'action1',
                         'username' => 'username1',
                         'email' => 'email1',
                         'idnumber' => 'idnumber1',
                         'context' => 'context1',
                         'instance' => 'instance1',
                         'role' => 'role1');

        // Store the mapping records in the database.
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'enrolment';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        // Run the import.
        $data = array('entity' => 'enrolment',
                      'action1' => 'create',
                      'username1' => 'rlipusername',
                      'email1' => 'rlipuser@rlipdomain.com',
                      'idnumber1' => 'rlipidnumber',
                      'context1' => 'course',
                      'instance1' => 'rlipshortname',
                      'role1' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        // Validate role assignment record.
        $data = array();
        $data['roleid'] = self::$courseroleid;
        $coursecontext = context_course::instance(self::$courseid);
        $data['contextid'] = $coursecontext->id;
        $data['userid'] = self::$userid;

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function test_version1importenrolmentfieldimportpreventsstandardfielduse() {
        global $CFG, $DB;
        $plugindir = get_plugin_directory('dhimport', 'version1');
        require_once($plugindir.'/lib.php');
        require_once($plugindir.'/version1.class.php');

        // Create the mapping record.
        $record = new stdClass;
        $record->entitytype = 'enrolment';
        $record->standardfieldname = 'context';
        $record->customfieldname = 'context2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        // Get the import plugin set up.
        $data = array();
        $provider = new rlipimport_version1_importprovider_mockenrolment($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('enrolment');

        // Transform a sample record.
        $record = new stdClass;
        $record->context = 'course';
        $record = $importplugin->apply_mapping('enrolment', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        // Validate that the field was unset.
        $this->assertEquals(isset($record->context), false);
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function test_version1importsucceedswithmaxlengthenrolmentfields() {
        global $DB;

        // Enable group / grouping creation.
        set_config('creategroupsandgroupings', 1, 'dhimport_version1');

        // Data for all fixed-size fields at their maximum sizes.
        $data = array('username' => str_repeat('x', 100),
                      'group' => str_repeat('x', 254),
                      'grouping' => str_repeat('x', 254));

        // Create a test user.
        $this->create_test_user(array('username' => str_repeat('x', 100)));

        // Run the import.
        $this->run_core_enrolment_import($data);

        // Validate all record counts.
        $numenrolments = $DB->count_records('user_enrolments');
        $this->assertEquals($numenrolments, 1);

        $numassignments = $DB->count_records('role_assignments');
        $this->assertEquals($numassignments, 1);

        $numgroups = $DB->count_records('groups');
        $this->assertEquals($numgroups, 1);

        $numgroupsmembers = $DB->count_records('groups_members');
        $this->assertEquals($numgroupsmembers, 1);

        $numgroupings = $DB->count_records('groupings');
        $this->assertEquals($numgroupings, 1);

        $numgroupingsgroups = $DB->count_records('groupings_groups');
        $this->assertEquals($numgroupingsgroups, 1);
    }

    /**
     * Test main newenrolmentemail() function.
     */
    public function test_version1importnewenrolmentemail() {
        global $CFG, $DB; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1_fakeemail.php');
        $importplugin = new rlip_importplugin_version1_fakeemail();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Enrol some students.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        // Enrol teachers.
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $teacher2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher2->id, $course->id, $teacherrole->id);

        // Test false return when empty user id or course id.
        $result = $importplugin->newenrolmentemail(null, $course->id);
        $this->assertFalse($result);
        $result = $importplugin->newenrolmentemail($user->id, null);
        $this->assertFalse($result);

        // Test false return when not enabled.
        set_config('newenrolmentemailenabled', '0', 'dhimport_version1');
        set_config('newenrolmentemailsubject', 'Test Subject', 'dhimport_version1');
        set_config('newenrolmentemailtemplate', 'Test Body', 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertFalse($result);

        // Test false return when enabled but empty template.
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1');
        set_config('newenrolmentemailsubject', 'Test Subject', 'dhimport_version1');
        set_config('newenrolmentemailtemplate', '', 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertFalse($result);

        // Test success when enabled, has template text, and user has email.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Test success and from is set to teacher when selected.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'teacher', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals($teacher, $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Test that subject is replaced by empty string when not present.
        $testsubject = null;
        $testbody = 'Test Body';
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals('', $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Full testing of replacement is done below, but just test that it's being done at all from the main function.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body %%user_username%%';
        $expectedtestbody = 'Test Body '.$user->username;
        set_config('newenrolmentemailenabled', '1', 'dhimport_version1');
        set_config('newenrolmentemailsubject', $testsubject, 'dhimport_version1');
        set_config('newenrolmentemailtemplate', $testbody, 'dhimport_version1');
        set_config('newenrolmentemailfrom', 'admin', 'dhimport_version1');
        $result = $importplugin->newenrolmentemail($user->id, $course->id);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user, $result['user']);
        $this->assertArrayHasKey('from', $result);
        $this->assertEquals(get_admin(), $result['from']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($expectedtestbody, $result['body']);
    }

    /**
     * Test new user email notifications.
     */
    public function test_version1importnewenrolmentemailgenerate() {
        global $CFG; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1_fakeemail.php');
        $importplugin = new rlip_importplugin_version1_fakeemail();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templatetext = '<p>Hi %%user_fullname%%, you have been enroled in %%course_shortname%%
            Sitename: %%sitename%%
            User Username: %%user_username%%
            User Idnumber: %%user_idnumber%%
            User First Name: %%user_firstname%%
            User Last Name: %%user_lastname%%
            User Full Name: %%user_fullname%%
            User Email Address: %%user_email%%
            Course Fullname: %%course_fullname%%
            Course Shortname: %%course_shortname%%
            Course Idnumber: %%course_idnumber%%
            Course Summary: %%course_summary%%
            </p>';
        $actualtext = $importplugin->newenrolmentemail_generate($templatetext, $user, $course);

        $expectedtext = '<p>Hi '.fullname($user).', you have been enroled in '.$course->shortname.'
            Sitename: PHPUnit test site
            User Username: '.$user->username.'
            User Idnumber: '.$user->idnumber.'
            User First Name: '.$user->firstname.'
            User Last Name: '.$user->lastname.'
            User Full Name: '.fullname($user).'
            User Email Address: '.$user->email.'
            Course Fullname: '.$course->fullname.'
            Course Shortname: '.$course->shortname.'
            Course Idnumber: '.$course->idnumber.'
            Course Summary: '.$course->summary.'
            </p>';
        $this->assertEquals($expectedtext, $actualtext);
    }
}