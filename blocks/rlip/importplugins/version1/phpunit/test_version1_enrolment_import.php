<?php

global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once(elis::lib('testlib.php'));

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_mockenrolment extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

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

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Class for version 1 enrolment import correctness
 */
class version1EnrolmentImportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
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
                     'config' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('log' => 'moodle');
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
     * Create a test role
     *
     * @param string $name The role's display name
     * @param string $shortname The role's shortname
     * @param string $description The role's description
     * @param array $contexts The contexts at which the role should be
     *                        assignable (defaults to course
     * @return int The created role's id
     */
    private function create_test_role($name = 'rlipname', $shortname = 'rlipshortname',
                                      $description = 'rlipdescription', $contexts = NULL) {
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
    private function create_test_course($extra_data = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

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
        $user->password = 'Password!0';

        foreach ($extra_data as $key => $value) {
            $user->$key = $value;
        }

        return user_create_user($user);
    }

    /**
     * Creates a default guest user record in the database
     */
    private function create_guest_user() {
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
                      'role' => 'rlipshortname');
        return $data;
    }

    /**
     * Helper function that runs the enrolment import for a sample enrolment
     * 
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_core_enrolment_import($extradata, $use_default_data = true) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        if ($use_default_data) {
            $this->create_test_role();
            $this->create_test_course();
            $this->create_test_user();
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
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * system-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredSystemRoleAssignmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_SYSTEM));
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        unset($data['instance']);
        $this->create_test_user();
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $system_context = get_context_instance(CONTEXT_SYSTEM);
        $data['contextid'] = $system_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * category-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredCourseCategoryRoleAssignmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_COURSECAT));
        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'coursecat';
        $data['instance'] = 'rlipcategory';
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'rlipcategory'));
        $category_context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        $data['contextid'] = $category_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that required fields are set to specified values during
     * user-level role assignment creation
     */
    public function testVersion1ImportSetsRequiredUserRoleAssignmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_USER));
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'user';
        $data['instance'] = 'rlipusername';
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $user_context = get_context_instance(CONTEXT_USER, $userid);
        $data['contextid'] = $user_context->id;

        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that invalid username values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentUsernameOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid email values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentEmailOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid idnumber values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentIdnumberOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid context level values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidContextOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('context' => 'boguscontext'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid course context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsInvalidCourseInstanceOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('instance' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid category context instance values can't be set on
     * role assignment creation
     */
    public function testVersion1ImportPreventsInvalidCourseCategoryInstanceOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_COURSECAT));
        $this->create_test_user();
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
        global $DB;

        //setup
        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_COURSECAT));
        $this->create_test_user();
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
        $this->init_contexts_and_site_course();

        //run the import
        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_USER));
        $this->create_test_user();
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
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));
        set_role_contextlevels($roleid, array());
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the import does not set unsupported fields on enrolment creation
     */
    public function testVersion1ImportPreventsSettingUnsupportedEnrolmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student',
                                               'timemodified' => 12345,
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

          //setup
          $this->init_contexts_and_site_course();

          $this->create_test_role('rlipname','rlipshortname', 'rlipdescription', array(CONTEXT_SYSTEM,
                                                                                       CONTEXT_COURSE,
                                                                                       CONTEXT_COURSECAT,
                                                                                       CONTEXT_USER));
          $this->create_test_course();
          $this->create_test_user();

          $roleid = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));
          $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
          $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
          $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
          role_assign($roleid, $userid, $course_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 1);
          $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);
          $this->assertEquals($DB->count_records('role_assignments'), 1);

          //system
          $system_context = get_context_instance(CONTEXT_SYSTEM);
          role_assign($roleid, $userid, $system_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 2);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'system';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 2);

          //course category
          $category = new stdClass;
          $category->name = 'rlipcategory';
          $category->id = $DB->insert_record('course_categories', $category);

          $category_context = get_context_instance(CONTEXT_COURSECAT, $category->id);
          role_assign($roleid, $userid, $category_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 3);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'coursecat';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 3);

          //user

          $user_context = get_context_instance(CONTEXT_USER, $userid);
          role_assign($roleid, $userid, $user_context->id);

          $this->assertEquals($DB->count_records('role_assignments'), 4);
          $data = $this->get_core_enrolment_data();
          $data['context'] = 'user';
          $this->run_core_enrolment_import($data, false);
          $this->assertEquals($DB->count_records('role_assignments'), 4);
      }

    /**
     * Validate that the import does not created duplicate enrolment records on
     * creation
     */
    public function testVersion1ImportPreventsDuplicateEnrolmentCreation() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        //enrol the user
        enrol_try_internal_enrol($courseid, $userid);

        //attempt to re-enrol
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that the import enrols students using the 2.0 mechanism when
     * appropriate
     */
    public function testVersion1ImportEnrolsAppropriateUserAsStudent() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //create the test student role
        $roleid = $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student'));

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => $courseid));

        $this->assert_record_exists('user_enrolments', array('userid' => $userid,
                                                             'enrolid' => $enrolid));
    }

    /**
     * Validate that the import does not enrol students using the 2.0 mechanism
     * when not appropriate
     */
    public function testVersion1ImportDoesNotEnrolInappropriateUserAsStudent() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array());
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => $courseid));

        //compare data
        $exists = $DB->record_exists('user_enrolments', array('userid' => $userid,
                                                              'enrolid' => $enrolid));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that setting up course enrolments only works within the course
     * context
     */
    public function testVersion1ImportEnrolmentsAreCourseContextSpecific() {
        global $DB;

        $this->create_test_role('student', 'Student', 'student', array(CONTEXT_SYSTEM,
                                                                       CONTEXT_COURSE,
                                                                       CONTEXT_COURSECAT,
                                                                       CONTEXT_USER));

        //run the system-level import
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assert_no_enrolments_exist();

        //run the category-level import
        $category = new stdClass;
        $category->name = 'rlipshortname';
        $category->id = $DB->insert_record('course_categories', $category);
        $data['context'] = 'coursecat';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assert_no_enrolments_exist();

        //run the user-level import
        $this->create_test_user(array('username' => 'rlipshortname'));
        $data['context'] = 'user';
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
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmailIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                                'idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username, email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmailIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidEmail() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidIdnumber() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidUsername() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidIdnumber() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidUsername() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidEmail() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'email' => 'rlipuser@rlipdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that a user can still be enroled in a course even if they
     * already have a role assignment in that course
     */
    public function testVersionImportEnrolsUserAlreadyAssignedARole() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        $context = get_context_instance(CONTEXT_COURSE, $courseid);

        role_assign($roleid, $userid, $context->id);

        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        $this->assert_record_exists('user_enrolments', array());
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from taking place
     */
    public function testVersion1ImportRoleErrorPreventsEnrolment() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role('Student', 'student', 'Student');
        $this->create_test_course();
        $this->create_test_user();

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_record_exists('groups', array('name' => 'rlipgroup'));

        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $groupid = $DB->get_field('groups', 'id', array('name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('userid' => $userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can assign a user to an existing
     * group
     */
    public function testVersion1ImportAssignsUserToExistingGroup() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role('Student', 'student', 'Student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        //set up the "pre-existing" group
        $groupid = $this->create_test_group($courseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['group'] = 'rlipgroup';
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assert_record_exists('groups_members', array('userid' => $userid,
                                                            'groupid' => $groupid));
    }

    /**
     * Validate that the version 1 plugin can create a group and a grouping,
     * and assign the group to the grouping
     */
    public function testVersion1ImportAssignsGreatedGroupToCreatedGrouping() {
        global $DB;

        //enable group / grouping creation
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up the "pre-existing" group
        $groupid = $this->create_test_group($courseid);

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up the "pre-existing" grouping
        $groupingid = $this->create_test_grouping($courseid);

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

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up the "pre-existing" group
        $groupid = $this->create_test_group($courseid);

        //set up the "pre-existing" grouping 
        $groupingid = $this->create_test_grouping($courseid);;

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

        //setup
        $this->init_contexts_and_site_course();

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

        //setup
        $this->init_contexts_and_site_course();

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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up two "pre-existing" groups with the same name in our course
        $this->create_test_group($courseid);
        $this->create_test_group($courseid);

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 0);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role('student', 'Student', 'student');
        $courseid = $this->create_test_course();
        $secondcourseid = $this->create_test_course(array('shortname' => 'secondshortname'));
        $userid = $this->create_test_user();

        //set up the "pre-existing" group in another course
        $this->create_test_group($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 2);
        $this->assert_record_exists('groups', array('courseid' => $courseid,
                                                    'name' => 'rlipgroup'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => $courseid,
                                                        'name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('groupid' => $groupid,
                                                            'userid' => $userid));
    }

    /**
     * Vaidate that specifying the name of a group that exists multiple times
     * in another course but does not exist in the current course does not
     * prevent group creation
     */
    public function testVersion1ImportAllowsDuplicateGroupNamesInAnotherCourse() {
        global $DB;

        //enable group / grouping creation
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role('student', 'Student', 'student');
        $courseid = $this->create_test_course();
        $secondcourseid = $this->create_test_course(array('shortname' => 'secondshortname'));
        $userid = $this->create_test_user();

        //set up two "pre-existing" groups with the same name in another course
        $this->create_test_group($secondcourseid);
        $this->create_test_group($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groups'), 3);
        $this->assert_record_exists('groups', array('courseid' => $courseid,
                                                    'name' => 'rlipgroup'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => $courseid,
                                                        'name' => 'rlipgroup'));

        $this->assert_record_exists('groups_members', array('groupid' => $groupid,
                                                            'userid' => $userid));
    }

    /**
     * Validate that groups functionality only works within the course context
     */
    public function testVersion1ImportGroupsAreCourseContextSpecific() {
        global $DB;

        //enable groups functionality
        set_config('bogus_rlip_creategroups', 1);

        $this->create_test_role('rlipname', 'rlipshortname', 'rlipdescription', array(CONTEXT_SYSTEM,
                                                                                      CONTEXT_COURSE,
                                                                                      CONTEXT_COURSECAT,
                                                                                      CONTEXT_USER));

        //run the system-level import
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['context'] = 'system';
        $data['group'] = 'rlipgroup';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('groups'), 0);

        //run the category-level import
        $category = new stdClass;
        $category->name = 'rlipshortname';
        $category->id = $DB->insert_record('course_categories', $category);
        $data['context'] = 'coursecat';
        $this->run_core_enrolment_import($data, false);

        //validation
        $this->assertEquals($DB->count_records('role_assignments'), 2);
        $this->assertEquals($DB->count_records('groups'), 0);

        //run the user-level import
        $this->create_test_user(array('username' => 'rlipshortname'));
        $data['context'] = 'user';
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up two "pre-existing" groupings in our course
        $this->create_test_grouping($courseid);
        $this->create_test_grouping($courseid);

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 0);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up the "pre-existing" grouping
        $this->create_test_grouping($courseid);

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role('student', 'Student', 'student');
        $courseid = $this->create_test_course();
        $secondcourseid = $this->create_test_course(array('shortname' => 'secondshortname'));
        $this->create_test_user();

        //set up the "pre-existing" grouping in another course
        $this->create_test_grouping($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groupings'), 2);
        $this->assert_record_exists('groupings', array('courseid' => $courseid,
                                                       'name' => 'rlipgrouping'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => $courseid,
                                                        'name' => 'rlipgroup'));
        $groupingid = $DB->get_field('groupings', 'id', array('courseid' => $courseid,
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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role('student', 'Student', 'student');
        $courseid = $this->create_test_course();
        $secondcourseid = $this->create_test_course(array('shortname' => 'secondshortname'));
        $this->create_test_user();

        //set up two "pre-existing" groupings in another course
        $this->create_test_grouping($secondcourseid);
        $this->create_test_grouping($secondcourseid);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $data['group'] = 'rlipgroup';
        $data['grouping'] = 'rlipgrouping';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('groupings'), 3);
        $this->assert_record_exists('groupings', array('courseid' => $courseid,
                                                       'name' => 'rlipgrouping'));
        $groupid = $DB->get_field('groups', 'id', array('courseid' => $courseid,
                                                        'name' => 'rlipgroup'));
        $groupingid = $DB->get_field('groupings', 'id', array('courseid' => $courseid,
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

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('student', 'Student', 'student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        //enrol the user in the course
        enrol_try_internal_enrol($courseid, $userid, $roleid, 0);

        //set up the "pre-existing" group
        $groupid = $this->create_test_group($courseid);

        //add the user to the group
        groups_add_member($groupid, $userid);

        //validate setup
        $this->assertEquals($DB->count_records('groups_members'), 1);

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
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

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_role();
        $courseid = $this->create_test_course();
        $this->create_test_user();

        //set up the "pre-existing" group
        $groupid = $this->create_test_group($courseid);
        //set up the "pre-existing" grouping
        $groupingid = $this->create_test_grouping($courseid);

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
     * Validate that users must be enrolled in a course before they are
     * assigned to a group within it
     */
    public function testVersion1ImportPreventsUnenroledUserGroupAssignments() {
        global $DB;

        //enable group / grouping creation
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 1);

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //run the import
        $this->run_core_enrolment_import(array('group' => 'rlipgroup'));

        //compare data
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assertEquals($DB->count_records('groups_members'), 0);
    }

    /**
     * Validate that groups are not created when the required setting is not
     * enabled
     */
    public function testVersion1ImportOnlyCreatesGroupWithSettingEnabled() {
        global $DB;

        //disable group / grouping creation
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 0);

        //setup
        $this->init_contexts_and_site_course();

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
        //todo: use proper setting
        set_config('bogus_rlip_creategroups', 0);

        //setup
        $this->init_contexts_and_site_course();

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

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //record the current time
        $starttime = time();

        //data setup
        $this->create_test_role('Student', 'student', 'Student');
        $this->create_test_course(array('startdate' => 12345)); 
        $this->create_test_user();

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //ideal enrolment start time
        $course_startdate = $DB->get_field('course', 'startdate', array('shortname' => 'rlipshortname'));

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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user();
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('idnumber' => 'rlipidnumber'));
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('idnumber' => 'rlipidnumber'));
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                      'idnumber' => 'rlipidnumber'));
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
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course(); 
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                      'idnumber' => 'rlipidnumber'));
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

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student'));
        $DB->delete_records('role_assignments');

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'student';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
    }
    
    /**
     * Validate that the version 1 plugin can delete a role assignment when
     * there is no enrolment tied to it
     */
    public function testVersion1ImportDeletesRoleAssignment() {
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin can delete an enrolment and role
     * assignment at the same time
     */
    public function testVersion1ImportDeletesEnrolmentAndRoleAssignment() {
        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student'));

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'student';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assert_no_enrolments_exist();
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidUsername() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('idnumber' => 'rlipidnumber'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        unset($data['username']);
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified context is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidContext() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['context'] = 'boguscontext';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified instance is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidInstance() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['instance'] = 'bogusinstance';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete enrolments when the
     * specified role is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithInvalidRole() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['role'] = 'bogusrole';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidUsernameInvalidEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['email'] = 'bogususer@bogusdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidUsernameInvalidIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('idnumber' => 'rlipidnumber'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['idnumber'] = 'bogusidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidEmailInvalidUsername() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['email'] = 'rlipuser@rlipdomain.com';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidEmailInvalidIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                      'idnumber' => 'rlipidnumber'));
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
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidIdnumberInvalidUsername() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('idnumber' => 'rlipidnumber'));
        $data = $this->get_core_enrolment_data();
        $this->run_core_enrolment_import($data, false);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'bogususername';
        $data['idnumber'] = 'rlipidnumber';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that the version 1 plugin does not delete an enrolment with the
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentWithValidIdnumberInvalidEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->create_test_role();
        $this->create_test_course();
        $this->create_test_user(array('idnumber' => 'rlipidnumber',
                                      'email' => 'rlipuser@rlipdomain.com'));
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
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $this->assert_record_exists('role_assignments', array('userid' => $userid));
    }

    /**
     * Validate that unassigning a role does not remove an enrolment for roles
     * other than the student role
     */
    public function testVersion1ImportDoesNotDeleteEnrolmentForNonStudentRole() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('user_enrolments'), 0);

        $this->create_test_role('Student', 'student', 'Student');

        //set up our second enrolment
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student'; 
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that the version 1 import plugin handles deletion of
     * non-existent enrolments gracefully
     */
    public function testVersion1ImportPreventsNonexistentRoleAssignmentDeletion() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $this->assertEquals($DB->count_records('role_assignments'), 1);

        $this->create_test_role('secondname', 'secondshortname', 'seconddescription');
        $this->create_test_course(array('shortname' => 'secondshortname'));
        $this->create_test_user(array('username' => 'secondusername'));

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';
        $data['username'] = 'secondusername';
        $data['instance'] = 'secondshortname';
        $data['role'] = 'secondshortname';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from being deleted
     */
    public function testVersion1ImportRoleErrorPreventsEnrolmentDeletion() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //set up our enrolment
        $this->create_test_role('Student', 'student', 'Student');
        $this->create_test_course();
        $this->create_test_user();
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

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

        //setup
        $this->init_contexts_and_site_course();

        //set up our enrolment
        $this->run_core_enrolment_import(array());

        $this->create_test_role('secondname', 'secondshortname', 'seconddescription');

        //set up a second enrolment
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'secondshortname';
        $this->run_core_enrolment_import($data, false);

        $this->assertEquals($DB->count_records('role_assignments'), 2);

        //perform the delete action
        $data = $this->get_core_enrolment_data();
        $data['action'] = 'delete';

        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }
}