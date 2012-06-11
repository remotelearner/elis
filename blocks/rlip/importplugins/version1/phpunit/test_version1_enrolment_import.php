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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_mockenrolment extends rlip_importprovider_mock {

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
 * Overlay database with some enrolment-specific optimizations
 */
class overlay_enrolment_database extends overlay_database {
    /**
     * Empty out all the overlay tables.
     */
    public function reset_overlay_tables() {
        //only bother with the tables that cause us issues
        $this->delete_records('role_assignments');
        $this->delete_records('user_enrolments');
        $this->delete_records('groups');
        $this->delete_records('groupings');
        $this->delete_records('groupings_groups');
        $this->delete_records('groups_members');
    }
}

/**
 * Class for version 1 enrolment import correctness
 */
class version1EnrolmentImportTest extends rlip_test {
    static $courseroleid;
    static $coursecatroleid;
    static $userroleid;
    static $systemroleid;
    static $userid;
    static $courseid;
    static $allcontextroleid;

    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        return array('context' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'enrol' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'groups' => 'moodle',
                     'groups_members' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'config' => 'moodle',
                     'config_plugins' => 'moodle',
                     RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
                     'role_capabilities' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array('log' => 'moodle',
                     'grade_grades' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     RLIP_LOG_TABLE => 'block_rlip',
                     'forum_read' => 'mod_forum',
                     'forum_subscriptions' => 'mod_forum',
                     'forum_track_prefs' => 'mod_forum');
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_enrolment_database($DB, static::get_overlay_tables(), static::get_ignored_tables());
        $DB = self::$overlaydb;
        self::init_contexts_and_site_course();
        self::create_guest_user();

        self::$courseroleid = self::create_test_role();
        $syscontext = get_context_instance(CONTEXT_SYSTEM);

        self::$systemroleid = self::create_test_role('systemname', 'systemshortname', 'systemdescription', array(CONTEXT_SYSTEM));
        self::$coursecatroleid = self::create_test_role('coursecatname', 'coursecatshortname', 'coursecatdescription', array(CONTEXT_COURSECAT));
        self::$userroleid = self::create_test_role('username', 'usershortname', 'userdescription', array(CONTEXT_USER));
        self::$allcontextroleid = self::create_test_role('allname', 'allshortname', 'alldescription', array(CONTEXT_SYSTEM,
                                                                                                            CONTEXT_COURSE,
                                                                                                            CONTEXT_COURSECAT,
                                                                                                            CONTEXT_USER));
        self::$courseid = self::create_test_course();
        self::$userid = self::create_test_user();
        self::get_csv_files();
        self::get_logfilelocation_files();
        self::get_zip_files();
    }

    /**
     * Clean up the temporary database tables.
     */
    public static function tearDownAfterClass() {
        global $DB;
        $DB = self::$origdb;
        parent::tearDownAfterClass();
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private static function init_contexts_and_site_course() {
        global $DB;

        //setup
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');
        set_config('enrol_plugins_enabled', 'manual');

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
     * Create a test role
     *
     * @param string $name The role's display name
     * @param string $shortname The role's shortname
     * @param string $description The role's description
     * @param array $contexts The contexts at which the role should be
     *                        assignable (defaults to course
     * @return int The created role's id
     */
    private static function create_test_role($name = 'coursename', $shortname = 'courseshortname',
                                      $description = 'coursedescription', $contexts = NULL) {
        if ($contexts === NULL) {
            //use default of course context
            $contexts = array(CONTEXT_COURSE);
        }

        $roleid = create_role($name, $shortname, $description);
        set_role_contextlevels($roleid, $contexts);

        return $roleid;
    }

    /**
     * Create a test course
     *
     * @param array $extra_data Extra field values to set on the course
     * @return int The created course's id
     */
    private static function create_test_course($extra_data = array()) {
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

        foreach ($extra_data as $key => $value) {
            $course->$key = $value;
        }

        $course = create_course($course);

        return $course->id;
    }

    /**
     * Create a test user
     *
     * @param array $extra_data Extra field values to set on the user
     * @return int The created user's id
     */
    private function create_test_user($extra_data = array()) {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';

        foreach ($extra_data as $key => $value) {
            $user->$key = $value;
        }

        return user_create_user($user);
    }

    /**
     * Creates a default guest user record in the database
     */
    private static function create_guest_user() {
        global $CFG, $DB;

        //set up the guest user to prevent enrolment plugins from thinking the
        //created user is the guest user
        if ($record = self::$origdb->get_record('user', array('username' => 'guest',
                                                'mnethostid' => $CFG->mnet_localhost_id))) {
            unset($record->id);
            $DB->insert_record('user', $record);
        }
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
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        return $data;
    }

    /**
     * Helper function that runs the enrolment import for a sample enrolment
     *
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_core_enrolment_import($extradata, $use_default_data = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_enrolment_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockenrolment($data);

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
     * Validate that the version 1 plugin supports enrolment actions
     */
    public function testVersion1ImportSupportsEnrolmentActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment');

        $this->assertEquals($supports, array('create', 'add', 'delete'));
    }

    /**
     * Validate that the version 1 plugin supports the enrolment create action
     */
    public function testVersion1ImportSupportsEnrolmentCreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment_create');
        $required_fields = array(array('username', 'email', 'idnumber'),
                                 'context',
                                 'instance',
                                 'role');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports the enrolment add action
     */
    public function testVersion1ImportSupportsEnrolmentAdd() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment_add');
        $required_fields = array(array('username', 'email', 'idnumber'),
                                 'context',
                                 'instance',
                                 'role');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports the enrolment delete action
     */
    public function testVersion1ImportSupportsEnrolmentDelete() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment_delete');
        $required_fields = array(array('username', 'email', 'idnumber'),
                                 'context',
                                 'instance',
                                 'role');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that required fields are set to specified values during
     * course-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredCourseRoleAssignmentFieldsOnCreate() {
        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = self::$courseroleid;

        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * system-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredSystemRoleAssignmentFieldsOnCreate() {
        //run the import
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'systemshortname';
        unset($data['instance']);
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$systemroleid;

        $system_context = get_context_instance(CONTEXT_SYSTEM);
        $data['contextid'] = $system_context->id;

        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * category-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredCourseCategoryRoleAssignmentFieldsOnCreate() {
        global $DB;

        //run the import
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

        $category_context = get_context_instance(CONTEXT_COURSECAT, $category->id);
        $data['contextid'] = $category_context->id;

        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * user-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredUserRoleAssignmentFieldsOnCreate() {
        //run the import
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'usershortname';
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$userroleid;

        $user_context = get_context_instance(CONTEXT_USER, self::$userid);
        $data['contextid'] = $user_context->id;

        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that invalid username values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentUsernameOnCreate() {
        $this->run_core_enrolment_import(array('username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid email values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentEmailOnCreate() {
        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid idnumber values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentIdnumberOnCreate() {
        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid context level values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidContextOnCreate() {
        $this->run_core_enrolment_import(array('context' => 'boguscontext'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that roles that are not assignable at the required contextlevel
     * can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsUnassignableContextOnCreate() {
        $this->run_core_enrolment_import(array('role' => 'systemshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid course context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsInvalidCourseInstanceOnCreate() {
        $this->run_core_enrolment_import(array('instance' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid category context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsInvalidCourseCategoryInstanceOnCreate() {
        //run the import
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'boguscategory';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that ambiguous category context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsAmbiguousCourseCategoryInstanceOnCreate() {
        //run the import
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid user context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsInvalidUserInstanceOnCreate() {
        //setup

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'user';
        $data['instance'] = 'bogususername';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid role shortname values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidRoleOnCreate() {
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
    public function testVersion1ImportPreventsSettingUnsupportedEnrolmentFieldsOnCreate() {
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
      public function testVersion1ImportPreventsDuplicateRoleAssignmentCreation() {
          global $DB;

          $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
          role_assign(self::$courseroleid, self::$userid, $course_context->id);

          //course
          $this->assertEquals($DB->count_records('role_assignments'), 1);
          $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);
          $this->assertEquals($DB->count_records('role_assignments'), 1);

          //system
          $system_context = get_context_instance(CONTEXT_SYSTEM);
          role_assign(self::$systemroleid, self::$userid, $system_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 2);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'system';
          $data['role'] = 'systemshortname';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 2);

          //course category
          $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
          $category_context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
          role_assign(self::$coursecatroleid, self::$userid, $category_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 3);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'coursecat';
          $data['role'] = 'coursecatshortname';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 3);

          //user
          $user_context = get_context_instance(CONTEXT_USER, self::$userid);
          role_assign(self::$userroleid, self::$userid, $user_context->id);

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
    public function testVersion1ImportPreventsDuplicateEnrolmentCreation() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //enrol the user
        enrol_try_internal_enrol(self::$courseid, self::$userid);

        //attempt to re-enrol
        $this->run_core_enrolment_import(array());

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that the import does not create duplicate enrolment or role
     * assignment records when the role is assigned and the user is enrolled
     */
    public function testVersion1ImportPreventsDuplicateEnrolmentAndRoleAssignmentCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //enrol the user
        enrol_try_internal_enrol(self::$courseid, self::$userid, self::$courseroleid);

        //attempt to re-enrol
        $this->run_core_enrolment_import(array());

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that the import enrols students using the 2.0 mechanism when
     * appropriate
     */
    public function testVersion1ImportEnrolsAppropriateUserAsStudent() {
        global $DB;

        $this->run_core_enrolment_import(array());

        //compare data
        //user enrolment
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => self::$courseid));
        $this->assert_record_exists('user_enrolments', array('userid' => self::$userid,
                                                             'enrolid' => $enrolid));

        //role assignment
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid,
                                                              'roleid' => self::$courseroleid,
                                                              'contextid' => $course_context->id));
    }

    /**
     * Validate that setting up course enrolments only works within the course
     * context
     */
    public function testVersion1ImportEnrolmentsAreCourseContextSpecific() {
        global $DB;

        //$this->init_contexts_and_site_course();
        $this->create_test_role('globalstudentname', 'globalstudentshortname', 'globalstudentdescription', array(CONTEXT_SYSTEM,
                                                                                                                 CONTEXT_COURSE,
                                                                                                                 CONTEXT_COURSECAT,
                                                                                                                 CONTEXT_USER));

        //run the system-level import
        //$this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assert_no_enrolments_exist();

        //run the category-level import
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assert_no_enrolments_exist();

        //run the user-level import
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'globalstudentshortname';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 3);
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsername() {
        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = self::$courseroleid;

        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmail() {
        //run the import
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnIdnumber() {
        //run the import
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = self::$courseroleid;
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmail() {
        //run the import
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
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameIdnumber() {
        //run the import
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
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmailIdnumber() {
        //run the import
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
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username, email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmailIdnumber() {
        //run the import
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
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidEmail() {
        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidIdnumber() {
        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidUsername() {
        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidIdnumber() {
        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidUsername() {
        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidEmail() {
        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that a user can still be enroled in a course even if they
     * already have a role assignment in that course
     */
    public function testVersionImportEnrolsUserAlreadyAssignedARole() {
        global $DB;

        $context = get_context_instance(CONTEXT_COURSE, self::$courseid);

        role_assign(self::$courseroleid, self::$userid, $context->id);

        $this->run_core_enrolment_import(array());

        $this->assert_record_exists('user_enrolments', array());
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that a user can still be assigned a role in a course even if
     * they are enroled in it
     */
    public function testVersion1ImportRoleAssignsAlreadyEnrolledUser() {
        global $DB;

        //set up just an enrolment
        enrol_try_internal_enrol(self::$courseid, self::$userid);
        //validate setup
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assert_no_role_assignments_exist();

        //run the import
        $this->run_core_enrolment_import(array());

        //validation
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from taking place
     */
    public function testVersion1ImportRoleErrorPreventsEnrolment() {
        global $DB;

        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));

        $this->assertEquals($DB->count_records('user_enrolments'), 0);
    }

    /**
     * Validate that the version 1 plugin can create a group and assign a
     * user to it
     */
    public function testVersion1ImportAssignsUserToCreatedGroup() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('groups', array('name' => 'rlipgroup'));

        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('userid' => self::$userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can assign a user to an existing
     * group
     */
    public function testVersion1ImportAssignsUserToExistingGroup() {
        global $DB;

        //set up the "pre-existing" group
        $groupid = $this->create_test_group(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assert_record_exists('groups_members', array('userid' => self::$userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can create a group and a grouping,
     * and assign the group to the grouping
     */
    public function testVersion1ImportAssignsGreatedGroupToCreatedGrouping() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //run the import
        $this->run_core_enrolment_import(array('group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        //compare data
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
    public function testVersion1ImportAssignsExistingGroupToCreatedGrouping() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //set up the "pre-existing" group
        $groupid = $this->create_test_group(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $groupingid = $DB->get_field('groupings', 'id', array('name' => 'rlipgrouping'));

        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the version 1 plugin can create a group and assign it
     * to an existing grouping
     */
    public function testVersion1ImportAssignsCreatedGroupToExistingGrouping() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //set up the "pre-existing" grouping
        $groupingid = $this->create_test_grouping(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));

        $this->assertEquals($DB->count_records('groupings'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that the version 1 plugin can assign an existing group to an
     * existing grouping
     */
    public function testVersion1ImportAssignsExistingGroupToExistingGrouping() {
        global $DB;

        //set up the "pre-existing" group
        $groupid = $this->create_test_group(self::$courseid);

        //set up the "pre-existing" grouping
        $groupingid = $this->create_test_grouping(self::$courseid);;

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assertEquals($DB->count_records('groupings'), 1);
        $this->assert_record_exists('groupings_groups', array('groupid' => $groupid,
                                                              'groupingid' => $groupingid));
    }

    /**
     * Validate that a role-related error prevents processing of
     * groups and groupings information
     */
    public function testVersion1ImportRoleErrorPreventsGroups() {
        global $DB;

        //run the import
        $this->run_core_enrolment_import(array('role' => 'bogusshortname',
                                               'group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        //compare data
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that processing a duplicate role assignment prevents processing
     * of groups and groupings information
     */
    public function testVersion1ImportDuplicateRoleAssignmentPreventsGroups() {
        global $DB;

        //run the import
        $this->run_core_enrolment_import(array());

        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that specifying an ambiguous group name prevents processing of
     * enrolment information
     */
    public function testVersion1ImportAmbiguousGroupNamePreventsEnrolment() {
        //set up two "pre-existing" groups with the same name in our course
        $this->create_test_group(self::$courseid);
        $this->create_test_group(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying a non-existent group name prevents processing
     * of enrolment information when not creating groups
     */
    public function testVersion1ImportInvalidGroupNamePreventsEnrolment() {
        //disable group / grouping creation
        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        //run the import
        $this->run_core_enrolment_import(array('group' => 'rlipgroup'));

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying the name of a group that exists in another
     * course but not the current course does not prevent group creation
     */
    public function testVersion1ImportAllowsDuplicateGroupNamesAcrossCourses() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupsacrosscourses'));

        //set up the "pre-existing" group in another course
        $this->create_test_group($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
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
    public function testVersion1ImportAllowsDuplicateGroupNamesInAnotherCourse() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //setup
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupsinaothercourse'));

        //set up two "pre-existing" groups with the same name in another course
        $this->create_test_group($secondcourseid);
        $this->create_test_group($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
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
    public function testVersion1ImportGroupsAreCourseContextSpecific() {
        global $DB;

        //enable groups functionality
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //run the system-level import
        //$this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'systemshortname';
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('groups'), 0);

        //run the category-level import
        $data['instance'] = 'rlipcategory';
        $data['context'] = 'coursecat';
        $data['role'] = 'coursecatshortname';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assertEquals($DB->count_records('groups'), 0);

        //run the user-level import
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $data['role'] = 'usershortname';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 3);
        $this->assertEquals($DB->count_records('groups'), 0);
    }

    /**
     * Validate that specifying an ambiguous grouping name prevents processing
     * of enrolment information
     */
    public function testVersion1ImportAmbiguousGroupingNamePreventsEnrolments() {
        //set up two "pre-existing" groupings in our course
        $this->create_test_grouping(self::$courseid);
        $this->create_test_grouping(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying a non-existent grouping name prevents
     * processing of enrolment information when not creating groups
     */
    public function testVersion1ImportInvalidGroupingNamePreventsEnrolment() {
        //disable group / grouping creation
        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        //set up the "pre-existing" grouping
        $this->create_test_grouping(self::$courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that specifying the name of a grouping that exists in another
     * course but not the current course does not prevent grouping creation
     */
    public function testVersion1ImportAllowsDuplicateGroupingNamesAcrossCourses() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //setup
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupingsacrosscourses'));

        //set up the "pre-existing" grouping in another course
        $this->create_test_grouping($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
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
    public function testVersion1ImportAllowsDuplicateGroupingNamesInAnotherCourse() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //setup
        $secondcourseid = $this->create_test_course(array('shortname' => 'allowduplicategroupingsinanothercourse'));

        //set up two "pre-existing" groupings in another course
        $this->create_test_grouping($secondcourseid);
        $this->create_test_grouping($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
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
    public function testVersion1ImportPreventsDuplicateUserGroupAssignments() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        //enrol the user in the course
        enrol_try_internal_enrol(self::$courseid, self::$userid, self::$courseroleid, 0);

        //set up the "pre-existing" group
        $groupid = $this->create_test_group(self::$courseid);

        //add the user to the group
        groups_add_member($groupid, self::$userid);

        //validate setup
        $this->assertEquals($DB->count_records('groups_members'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups_members'), 1);
    }

    /**
     * Validate that the import prevents assigning a user to the same grouping
     * twice
     */
    public function testVersion1ImportPreventsDuplicateGroupGroupingAssignments() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        //set up the "pre-existing" group
        $groupid = $this->create_test_group(self::$courseid);
        //set up the "pre-existing" grouping
        $groupingid = $this->create_test_grouping(self::$courseid);

        //assign the group to the grouping
        groups_assign_grouping($groupingid, $groupid);

        //validate setup
        $this->assertEquals($DB->count_records('groupings_groups'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groupings_groups'), 1);
    }

    /**
     * Validate that groups and groupings only work at the course context
     */
    public function testVersion1ImportOnlySupportsGroupsForCourseContext() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //run the import
        $this->run_core_enrolment_import(array('context' => 'system',
                                               'group' => 'rlipgroup'));

        //compare data
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groups_members'), 0);
    }

    /**
     * Validate that groups are not created when the required setting is not
     * enabled
     */
    public function testVersion1ImportOnlyCreatesGroupWithSettingEnabled() {
        global $DB;

        //disable group / grouping creation
        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        //run the import
        $this->run_core_enrolment_import(array('group' => 'rlipgroup'));

        //compare data
        $this->assertEquals($DB->count_records('groups'), 0);
    }

    /**
     * Validate that groupings are not created when the required setting is not
     * enabled
     */
    public function testVersion1ImportOnlyCreatesGroupingWithSettingEnabled() {
        global $DB;

        //disable group / grouping creation
        set_config('creategroupsandgroupings', 0, 'rlipimport_version1');

        //run the import
        $this->run_core_enrolment_import(array('group' => 'rlipgroup',
                                               'grouping' => 'rlipgrouping'));

        //compare data
        $this->assertEquals($DB->count_records('groupings'), 0);
    }

    /**
     * Validate that course enrolment create action sets start time, time
     * created and time modified appropriately
     */
    public function testVersion1ImportSetsEnrolmentTimestamps() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //record the current time
        $starttime = time();

        //data setup
        $this->create_test_course(array('shortname' => 'timestampcourse',
                                        'startdate' => 12345));

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['instance'] = 'timestampcourse';
        $this->run_core_enrolment_import($data, false);

        //ideal enrolment start time
        $course_startdate = $DB->get_field('course', 'startdate', array('shortname' => 'timestampcourse'));

        //validate enrolment record
        $where = 'timestart = :timestart AND
                  timecreated >= :timecreated AND
                  timemodified >= :timemodified';
        $params = array('timestart' => 12345,
                        'timecreated' => $starttime,
                        'timemodified' => $starttime);
        $exists = $DB->record_exists_select('user_enrolments', $where, $params);

        $this->assertEquals($exists, true);

        //validate role assignment record
        $where = 'timemodified >= :timemodified';
        $params = array('timemodified' => $starttime);
        $exists = $DB->record_exists_select('role_assignments', $where, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username,
     * along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnUsername() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email,
     * along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnEmail() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on idnumber,
     * along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnIdnumber() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email,
     * along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnUsernameEmail() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username
     * and idnumber, along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnUsernameIdnumber() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on email
     * and idnumber, along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnEmailIdnumber() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete enrolments based on username,
     * email and idnumber, along with required non-user fields
     */
    public function testVersion1ImportDeletesEnrolmentBasedOnUsernameEmailIdnumber() {
        //set up our enrolment
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete an enrolment when there
     * is no role assignment tied to it
     */
    public function testVersion1ImportDeletesEnrolment() {
        global $DB;

        $this->run_core_enrolment_import(array());
        $DB->delete_records('role_assignments');

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
        $this->assert_no_enrolments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete a course-level role
     * assignment when there is no enrolment tied to it
     */
    public function testVersion1ImportDeletesCourseRoleAssignment() {
        //set up our enrolment
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        role_assign(self::$courseroleid, self::$userid, $course_context->id);

        //validate setup
        $this->assert_no_enrolments_exist();

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete an enrolment and role
     * assignment at the same time
     */
    public function testVersion1ImportDeletesEnrolmentAndCourseRoleAssignment() {
        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete a system-level role
     * assignment
     */
    public function testVersion1ImportDeletesSystemRoleAssignment() {
        global $DB;

        //setup
        $context = get_context_instance(CONTEXT_SYSTEM);
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
    public function testVersion1ImportDeletesCourseCategoryRoleAssignment() {
        global $DB;

        //setup
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //prevent PM from trying to process instructors
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
    public function testVersion1ImportDeletesUserRoleAssignment() {
        global $DB;

        //setup
        $context = get_context_instance(CONTEXT_USER, self::$userid);
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
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidUsername() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidEmail() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidIdnumber() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified context is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidContext() {
        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'boguscontext';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete course-level role
     * assignments when the specified instance is incorrect
     */
    public function testVersion1ImportDoesNotDeleteCourseRoleAssignmentWithInvalidInstance() {
        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['instance'] = 'bogusinstance';

        //run the import
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete category-level role
     * assignments when the specified instance is incorrect
     */
    public function testVersion1ImportDoesNotDeleteCourseCategoryRoleAssignmentWithInvalidInstance() {
        global $DB;

        //setup
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //set up our role assignment
        role_assign(self::$coursecatroleid, self::$userid, $context->id);

        //validate setup
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'coursecat';
        $data['instance'] = 'boguscategory';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete category-level role
     * assignments when the specified instance is ambiguous
     */
    public function testVersion1ImportDoesNotDeleteCourseCategoryRoleAssignmentWithAmbiguousInstance() {
        global $DB;

        //setup
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);

        //set up our role assignment
        role_assign(self::$coursecatroleid, self::$userid, $context->id);

        //validate setup
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete user-level role
     * assignments when the specified instance is incorrect
     */
    public function testVersion1ImportDoesNotDeleteUserRoleAssignmentWithInvalidInstance() {
        global $DB;

        //setup
        $context = get_context_instance(CONTEXT_USER, self::$userid);

        //set up our role assignment
        role_assign(self::$userroleid, self::$userid, $context->id);

        //validate setup
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'user';
        $data['instance'] = 'bogususername';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified role is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidRole() {
        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'bogusrole';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidUsernameInvalidEmail() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidUsernameInvalidIdnumber() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidEmailInvalidUsername() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidEmailInvalidIdnumber() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'rlipuser@rlipdomain.com';
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidIdnumberInvalidUsername() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidIdnumberInvalidEmail() {
        //set up our enrolment
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'rlipidnumber';
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('role_assignments', array('userid' => self::$userid));
    }

    /**
     * Validate that the version 1 import plugin handles deletion of
     * non-existent enrolments gracefully
     */
    public function testVersion1ImportPreventsNonexistentRoleAssignmentDeletion() {
        global $DB;

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        $this->create_test_role('noassignmentdeletionname', 'noassignmentdeletionshortname', 'noassignmentdeletiondescription');
        $this->create_test_course(array('shortname' => 'noassignmentdeletionshort'));
        $this->create_test_user(array('username' => 'noassignmentdeletionshortname'));

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'noassignmentdeletionusername';
        $data['instance'] = 'noassignmentdeletionshortname';
        $data['role'] = 'noassignmentdeletionshortname';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from being deleted
     */
    public function testVersion1ImportRoleErrorPreventsEnrolmentDeletion() {
        global $DB;

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'bogusshortname';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that deleting role assignments is specific to the role
     * specified (i.e. does not delete the user's other role assignments
     * on that same entity)
     */
    public function testVersion1ImportEnrolmentDeletionIsRoleSpecific() {
        global $DB;

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $roleid = $this->create_test_role('deletionrolespecificname', 'deletionrolespecificshortname', 'deletionrolespecificdescription');
        $syscontext = get_context_instance(CONTEXT_SYSTEM);

        //set up a second enrolment
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'deletionrolespecificshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 2);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on enrolment creation
     */
    public function testVersion1ImportUsesEnrolmentFieldMappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        //set up our mapping of standard field names to custom field names
        $mapping = array('action' => 'action1',
                         'username' => 'username1',
                         'email' => 'email1',
                         'idnumber' => 'idnumber1',
                         'context' => 'context1',
                         'instance' => 'instance1',
                         'role' => 'role1');

        //store the mapping records in the database
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'enrolment';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        //run the import
        $data = array('entity' => 'enrolment',
                      'action1' => 'create',
                      'username1' => 'rlipusername',
                      'email1' => 'rlipuser@rlipdomain.com',
                      'idnumber1' => 'rlipidnumber',
                      'context1' => 'course',
                      'instance1' => 'rlipshortname',
                      'role1' => 'courseshortname');
        $this->run_core_enrolment_import($data, false);

        //validate role assignment record
        $data = array();
        $data['roleid'] = self::$courseroleid;
        $course_context = get_context_instance(CONTEXT_COURSE, self::$courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = self::$userid;

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function testVersion1ImportEnrolmentFieldImportPreventsStandardFieldUse() {
        global $CFG, $DB;
        $plugin_dir = get_plugin_directory('rlipimport', 'version1');
        require_once($plugin_dir.'/lib.php');
        require_once($plugin_dir.'/version1.class.php');

        //create the mapping record
        $record = new stdClass;
        $record->entitytype = 'enrolment';
        $record->standardfieldname = 'context';
        $record->customfieldname = 'context2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        //get the import plugin set up
        $data = array();
        $provider = new rlip_importprovider_mockenrolment($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('enrolment');

        //transform a sample record
        $record = new stdClass;
        $record->context = 'course';
        $record = $importplugin->apply_mapping('enrolment', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        //validate that the field was unset
        $this->assertEquals(isset($record->context), false);
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function testVersion1ImportSucceedsWithMaxLengthEnrolmentFields() {
        global $DB;

        //enable group / grouping creation
        set_config('creategroupsandgroupings', 1, 'rlipimport_version1');

        //data for all fixed-size fields at their maximum sizes
        $data = array('username' => str_repeat('x', 100),
                      'group' => str_repeat('x', 254),
                      'grouping' => str_repeat('x', 254));

        //create a test user
        $this->create_test_user(array('username' => str_repeat('x', 100)));

        //run the import
        $this->run_core_enrolment_import($data);

        //validate all record counts
        $num_enrolments = $DB->count_records('user_enrolments');
        $this->assertEquals($num_enrolments, 1);

        $num_assignments = $DB->count_records('role_assignments');
        $this->assertEquals($num_assignments, 1);

        $num_groups = $DB->count_records('groups');
        $this->assertEquals($num_groups, 1);

        $num_groups_members = $DB->count_records('groups_members');
        $this->assertEquals($num_groups_members, 1);

        $num_groupings = $DB->count_records('groupings');
        $this->assertEquals($num_groupings, 1);

        $num_groupings_groups = $DB->count_records('groupings_groups');
        $this->assertEquals($num_groupings_groups, 1);
    }
}
