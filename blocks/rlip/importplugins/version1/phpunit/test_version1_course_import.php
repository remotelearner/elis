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
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_mockcourse extends rlip_importprovider_mock {

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
 * Overlay database that allows for the handling of temporary tables as well
 * as some course-specific optimizations
 */
class overlay_course_database extends overlay_database {

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
            // we can switch this back to a simple drop_table
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
        //do nothing
    }
}

/**
 * Class for version 1 course import correctness
 */
class version1CourseImportTest extends rlip_test {
    protected $backupGlobalsBlacklist = array('DB', 'USER');

    protected static $coursedisplay = false;

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        global $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'backup_controllers' => 'moodle',
            'backup_courses' => 'moodle',
            'backup_logs' => 'moodle',
            'block_positions' => 'moodle',
            'block_instances' => 'moodle',
            'cache_flags' => 'moodle',
            'comments' => 'moodle',
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'context_temp' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'course_completion_aggr_methd' => 'moodle',
            'course_completion_crit_compl' => 'moodle',
            'course_completion_criteria' => 'moodle',
            'course_completions' => 'moodle',
            'course_format_options' => 'moodle',
            'course_modules' => 'moodle',
            'course_modules_completion' => 'moodle',
            'course_modules_availability' => 'moodle',
            'course_modules_avail_fields' => 'moodle',
            'course_sections' => 'moodle',
            'course_sections_avail_fields' => 'moodle',
            'enrol' => 'moodle',
            'feedback_template' => 'mod_feedback',
            'files' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'forum' => 'mod_forum',
            'forum_subscriptions' => 'mod_forum',
            'forum_read' => 'mod_forum',
            'grade_categories' => 'moodle',
            'grade_categories_history' => 'moodle',
            'grade_grades' => 'moodle',
            'grade_grades_history' => 'moodle',
            'grade_items' => 'moodle',
            'grade_items_history' => 'moodle',
            'grade_letters' => 'moodle',
            'grade_outcomes' => 'moodle',
            'grade_outcomes_courses' => 'moodle',
            'grade_outcomes_history' => 'moodle',
            'grade_settings' => 'moodle',
            'grading_areas' => 'moodle',
            'groupings' => 'moodle',
            'groupings_groups' => 'moodle',
            'groups' => 'moodle',
            'groups_members' => 'moodle',
            'log' => 'moodle',
            'question' => 'moodle',
            'question_answers' => 'moodle',
            'question_categories' => 'moodle',
            'question_hints' => 'moodle',
            'question_truefalse' => 'qtype_truefalse',
            'rating' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_context_levels' => 'moodle',
            'role_names' => 'moodle',
            'scale' => 'moodle',
            'scale_history' => 'moodle',
            'tag' => 'moodle',
            'tag_instance' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_lastaccess' => 'moodle',
            'user_preferences' => 'moodle',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1'
        );

        $tables = array_merge($tables, self::load_plugin_xmldb('course/format'));
        $tables = array_merge($tables, self::load_plugin_xmldb('mod'));

        if ($DB->get_manager()->table_exists('course_display')) {
            self::$coursedisplay = true;
            $tables['course_display'] = 'moodle';
        }

        if ($DB->get_manager()->table_exists('course_sections_availability')) {
            $tables['course_sections_availability'] = 'moodle';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(
            'event'                   => 'moodle',
            'forum_track_prefs'       => 'mod_forum',
            'quiz'                    => 'moodle',
            'quiz_attempts'           => 'moodle',
            'quiz_feedback'           => 'moodle',
            'quiz_grades'             => 'moodle',
            'quiz_question_instances' => 'moodle',
            RLIP_LOG_TABLE            => 'block_rlip'
        );
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass() {
        global $DB;
        self::$origdb = $DB;

        // Use our custom overlay database type that supports temporary tables
        self::$overlaydb = new overlay_course_database($DB, static::get_overlay_tables(), static::get_ignored_tables());
        self::create_admin_user();

        $DB = self::$overlaydb;

        // Create data we need for many test cases
        self::create_guest_user();
        self::init_contexts_and_site_course();
        self::set_up_category_structure(true);

        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_DISABLED, 'enrol_guest');
        set_config('enrol_plugins_enabled', 'manual,guest');

        // New config settings needed for course format refactoring in 2.4
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

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
     * reset the $DB global
     */
    protected function tearDown() {
    }

    protected function setUp() {
    }

    /**
     * Helper function to get the core fields for a sample course
     *
     * @return array The course data
     */
    private function get_core_course_data($category) {
        $data = array('entity' => 'course',
                      'action' => 'create',
                      'fullname' => 'rlipfullname',
                      'category' => $category);
        return $data;
    }


    /**
     * Helper function that runs the course import for a sample course
     *
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_core_course_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);


        if ($use_default_data) {
            $data = $this->get_core_course_data('childcategory');
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
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
            self::$overlaydb->import_record('user', $record);
        }
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private static function init_contexts_and_site_course() {
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
            self::$overlaydb->import_record('course', $record);
        }

        build_context_path();
    }

    /**
     * Helper function that creates a parent and a child category
     *
     * @param boolean $second_child create a second child category if true
     */
    private static function set_up_category_structure($second_child = false) {
        global $DB;

        //basic parent and child categories
        $parent_category = new stdClass;
        $parent_category->name = 'parentcategory';
        $parent_category->id = $DB->insert_record('course_categories', $parent_category);
        get_context_instance(CONTEXT_COURSECAT, $parent_category->id);

        //child category
        $child_category = new stdClass;
        $child_category->name = 'childcategory';
        $child_category->parent = $parent_category->id;
        $child_category->id = $DB->insert_record('course_categories', $child_category);
        get_context_instance(CONTEXT_COURSECAT, $child_category->id);

        //duplicate parent and child in the form parent/child/parent/child
        $duplicate_parent_1 = new stdClass;
        $duplicate_parent_1->name = 'duplicateparentcategory';
        $duplicate_parent_1->id = $DB->insert_record('course_categories', $duplicate_parent_1);
        get_context_instance(CONTEXT_COURSECAT, $duplicate_parent_1->id);

        $duplicate_child_1 = new stdClass;
        $duplicate_child_1->name = 'duplicatechildcategory';
        $duplicate_child_1->parent = $duplicate_parent_1->id;
        $duplicate_child_1->id = $DB->insert_record('course_categories', $duplicate_child_1);
        get_context_instance(CONTEXT_COURSECAT, $duplicate_child_1->id);

        $duplicate_parent_2 = new stdClass;
        $duplicate_parent_2->name = 'duplicateparentcategory';
        $duplicate_parent_2->parent = $duplicate_child_1->id;
        $duplicate_parent_2->id = $DB->insert_record('course_categories', $duplicate_parent_2);
        get_context_instance(CONTEXT_COURSECAT, $duplicate_parent_2->id);

        $duplicate_child_2 = new stdClass;
        $duplicate_child_2->name = 'duplicatechildcategory';
        $duplicate_child_2->parent = $duplicate_parent_2->id;
        $duplicate_child_2->id = $DB->insert_record('course_categories', $duplicate_child_2);
        get_context_instance(CONTEXT_COURSECAT, $duplicate_child_2->id);

        //parent category with two child categories, both with the same name
        $nonunique_parent = new stdClass;
        $nonunique_parent->name = 'nonuniqueabsoluteparent';
        $nonunique_parent->id = $DB->insert_record('course_categories', $nonunique_parent);
        get_context_instance(CONTEXT_COURSECAT, $nonunique_parent->id);

        $nonunique_child_1 = new stdClass;
        $nonunique_child_1->name = 'nonuniqueabsolutechild';
        $nonunique_child_1->parent = $nonunique_parent->id;
        $nonunique_child_1->id = $DB->insert_record('course_categories', $nonunique_child_1);
        get_context_instance(CONTEXT_COURSECAT, $nonunique_child_1->id);

        $nonunique_child_2 = new stdClass;
        $nonunique_child_2->name = 'nonuniqueabsolutechild';
        $nonunique_child_2->parent = $nonunique_parent->id;
        $nonunique_child_2->id = $DB->insert_record('course_categories', $nonunique_child_2);
        get_context_instance(CONTEXT_COURSECAT, $nonunique_child_2->id);

        build_context_path(true);
    }

    /**
     * Helper function that creates a test category
     *
     * @param string A name to set for the category
     * @param int The id of the parent category, or 0 for top-level
     * @return string The name of the created category
     */
    private static function create_test_category($name, $parent = 0) {
        global $DB;

        $category = new stdClass;
        $category->name = $name;
        $category->parent = $parent;
        $category->id = $DB->insert_record('course_categories', $category);
        get_context_instance(CONTEXT_COURSECAT, $category->id);

        build_context_path(true);

        return $category->name;
    }

    /**
     * Helper function that creates an admin user and initializes the user
     * global
     */
    private static function create_admin_user() {
        global $USER, $DB, $CFG;

        //create the user record
//         $admin = new stdClass();
//         $admin->auth         = 'manual';
//         $admin->firstname    = get_string('admin');
//         $admin->lastname     = get_string('user');
//         $admin->username     = 'admin';
//         $admin->password     = 'adminsetuppending';
//         $admin->email        = '';
//         $admin->confirmed    = 1;
//         $admin->mnethostid   = $CFG->mnet_localhost_id;
//         $admin->lang         = $CFG->lang;
//         $admin->maildisplay  = 1;
//         $admin->timemodified = time();
//         $admin->lastip       = CLI_SCRIPT ? '0.0.0.0' : getremoteaddr(); // installation hijacking prevention
//         $admin->id = $DB->insert_record('user', $admin);

        //set up the guest user to prevent enrolment plugins from thinking the
        //created user is the guest user
        if ($admin = get_admin()) {
            self::$overlaydb->import_record('user', $admin);
        }

        //register as site admin
        set_config('siteadmins', $admin->id);

        //set up user global
        $USER = self::$overlaydb->get_record('user', array('id' => $admin->id));
    }

    /**
     * Asserts, using PHPunit, that the test course does not exist
     */
    private function assert_core_course_does_not_exist($shortname) {
        global $DB;

        $exists = $DB->record_exists('course', array('shortname' => $shortname));
        $this->assertEquals($exists, false);
    }

    /**
     * Assert a course exists
     *
     * @param array $data The fields to check
     * @return int The course id
     */
    private function assert_core_course_exists($data) {
        global $DB;

        $sql    = 'SELECT c.id';
        $tables = '{course} c';

        $criteria = array();

        foreach ($data as $column => $value) {

            if ($column == 'summary') {
                $criteria[] = 'c.'. $DB->sql_compare_text('summary', 255).' = :summary';
            } else if ($column == 'numsections') {
                // Only the topics and weeks formats have numsections settings
                if ((array_key_exists('format', $data)) && ($data['format'] == 'topics' || $data['format'] == 'weeks')) {
                    $tables .= ' LEFT JOIN {course_format_options} cfo ON cfo.courseid = c.id AND cfo.name = \'numsections\'';
                    $criteria[] = 'cfo.value = :numsections';
                }
            } else {
                $criteria[] = "c.$column = :$column";
            }
        }

        $sql .= " FROM $tables WHERE ". implode($criteria, ' AND ');

        $records = $DB->get_records_sql($sql, $data);
        $this->assertEquals(1, count($records), 'Should find 1 and only 1 course!');

        $course = array_shift($records);
        return $course->id;
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
     * Validate that the version 1 plugin supports course actions
     */
    public function testVersion1ImportSupportsCourseActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'course');

        $this->assertEquals($supports, array('create', 'update', 'delete'));
    }

    /**
     * Validate that the version 1 plugin supports the course create action
     */
    public function testVersion1ImportSupportsCourseCreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_create');
        $required_fields = array('shortname',
                                 'fullname',
                                 'category');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports the course update action
     */
    public function testVersion1ImportSupportsCourseUpdate() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_update');
        $required_fields = array('shortname');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that required fields are set to specified values during course creation
     */
    public function testVersion1ImportSetsRequiredCourseFieldsOnCreate() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        //run the import
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'requiredfields';
        $provider = new rlip_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //compare data
        $exists = $DB->record_exists('course', $data);

        $this->assertEquals($exists, true);
    }

    /*
     * Validate that non-required fields are set to specified values during course creation
     */
    public function testVersion1ImportSetsNonRequiredCourseFieldsOnCreate() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        set_config('maxsections', 20, 'moodlecourse');

        $data = array(
            'shortname'   => 'nonrequiredfields',
            'idnumber'    => 'nonrequiredfieldsidnumber',
            'summary'     => 'nonrequiredfieldssummary',
            'format'      => 'social',
            'numsections' => '15',
            'startdate'   => 'Jan/01/2012',
            'newsitems'   => 8,
            'showgrades'  => 0,
            'showreports' => 1,
            'maxbytes'    => 10240,
            'visible'     => 0,
            'lang'        => 'en',
            'guest'       => 1,
            'password'    => 'nonrequiredfieldspassword',
        );

        $this->run_core_course_import($data);

        $data['startdate'] = mktime(0, 0, 0, 1, 1, 2012);
        unset($data['guest']);
        unset($data['password']);

        $courseid = $this->assert_core_course_exists($data);

        $data = array(
            'courseid' => $courseid,
            'enrol'    => 'guest',
            'password' => 'nonrequiredfieldspassword',
            'status'   => ENROL_INSTANCE_ENABLED,
        );
        $this->assert_record_exists('enrol', $data);
    }

    /**
     * Validate that fields are set to specified values during course update
     */
    public function testVersion1ImportSetsFieldsOnCourseUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        set_config('maxsections', 20, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'updateshortname',
                                            'guest' => 0));

        $new_category = new stdClass;
        $new_category->name = 'updatecategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array(
            'action'       => 'update',
            'shortname'   => 'updateshortname',
            'fullname'    => 'updatedfullname',
            'idnumber'    => 'rlipidnumber',
            'summary'     => 'rlipsummary',
            'format'      => 'social',
            'startdate'   => 'Jan/12/2012',
            'newsitems'   => 7,
            'showgrades'  => 0,
            'showreports' => 1,
            'maxbytes'    => 0,
            'guest'       => 1,
            'password'    => 'password',
            'visible'     => 0,
            'lang'        => 'en',
            'category'    => 'updatecategory',
            'numsections' => 7,
        );
        $this->run_core_course_import($data, false);

        unset($data['action']);
        unset($data['guest']);
        unset($data['password']);
        $data['startdate'] = mktime(0, 0, 0, 1, 12, 2012);
        $data['category'] = $new_category->id;

        $courseid = $this->assert_core_course_exists($data);

        $data = array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => 'password',
            'status' => ENROL_INSTANCE_ENABLED
        );
        $this->assert_record_exists('enrol', $data);
    }

    /**
     * Validate that the legacy date format is support for course startdate
     * during creates
     */
    public function testVersion1ImportSupportsLegacyStartdateOnCourseCreate() {
        global $DB;

        // Create the course
        $data = array(
            'action'    => 'create',
            'shortname' => 'legacystartdatecreate',
            'fullname'  => 'legacystartdatecreate',
            'category'  => 'childcategory',
            'startdate' => '01/02/2012',
            'guest'     => 1,
        );
        $this->run_core_course_import($data, false);

        // Data validation
        unset($data['action']);
        unset($data['category']);
        unset($data['guest']);
        $data['startdate'] = mktime(0, 0, 0, 1, 2, 2012);

        $courseid = $this->assert_core_course_exists($data);

        $data = array(
            'courseid' => $courseid,
            'enrol'    => 'guest',
            'status'   => ENROL_INSTANCE_ENABLED
        );

        $this->assert_record_exists('enrol', $data);
    }

    /**
     * Validate that the legacy date format is supported for course startdate
     * during updates
     */
    public function testVersion1ImportSupportsLegacyStartdateOnCourseUpdate() {
        //create the course
        $data = array('action' => 'create',
                      'shortname' => 'legacystartdateupdate',
                      'fullname' => 'legacystartdateupdate',
                      'category' => 'childcategory');
        $this->run_core_course_import($data, false);

        //update the course
        $data = array('action' => 'update',
                      'shortname' => 'legacystartdateupdate',
                      'startdate' => '01/02/2012',
                      'category' => 'childcategory');
        $this->run_core_course_import($data, false);

        //data validation
        $data = array('shortname' => 'legacystartdateupdate',
                      'startdate' => mktime(0, 0, 0, 1, 2, 2012));

        $this->assert_record_exists('course', $data);
    }

    /**
     * Validate that fields are mapped from 'yes', 'no' values to integer values during course update
     */
    public function testVersion1ImportMapsFieldsOnCourseUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        set_config('maxsections', 20, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'mapshortname',
                                            'guest' => 'no'));

        $new_category = new stdClass;
        $new_category->name = 'mapcategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array(
            'action' => 'update',
            'shortname' => 'mapshortname',
            'fullname' => 'mapfullname',
            'idnumber' => 'rlipidnumber2',
            'summary' => 'rlipsummary',
            'format' => 'social',
            'numsections' => 7,
            'startdate' => 'Jan/12/2012',
            'newsitems' => 7,
            'showgrades' => 'no',
            'showreports' => 'yes',
            'maxbytes' => 0,
            'guest' => 'yes',
            'password' => 'password',
            'visible' => 'no',
            'lang' => 'en',
            'category' => 'mapcategory'
        );
        $this->run_core_course_import($data, false);

        foreach ($data as $key => $val) {
            if (in_array((string)$val, array('no', 'yes'))) {
                $data[$key] = ((string)$val == 'yes') ? 1: 0;
            }
        }
        unset($data['action']);
        unset($data['guest']);
        unset($data['password']);
        $data['startdate'] = mktime(0, 0, 0, 1, 12, 2012);
        $data['category'] = $new_category->id;

        $courseid = $this->assert_core_course_exists($data);

        $data = array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => 'password',
            'status' => ENROL_INSTANCE_ENABLED
        );
        $this->assert_record_exists('enrol', $data);

    }

    /**
     * Validate that invalid format values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseFormatOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseformatcreate',
                                            'format' => 'invalid'));
        $this->assert_core_course_does_not_exist('invalidcourseformatcreate');
    }

    /**
     * Validate that invalid format values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseFormatOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseformatupdate',
                                            'format' => 'topics'));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcourseformatupdate',
                      'format' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseformatupdate',
                                                    'format' => 'topics'));
    }

    /**
     * Validate that invalid numsections values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNumsectionsOnCreate() {
        set_config('maxsections', 10, 'moodlecourse');
        $this->run_core_course_import(array('shortname' => 'invalidcoursenumsectionscreate',
                                            'numsections' => 99999));
        $this->assert_core_course_does_not_exist('invalidcoursenumsectionscreate');
    }

    /**
     * Validate that invalid numsections values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseNumsectionsOnUpdate() {
        set_config('maxsections', 10, 'moodlecourse');
        $this->run_core_course_import(array('shortname' => 'invalidcoursenumsectionscreate',
                                            'numsections' => 7));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcoursenumsectionscreate',
                      'numsections' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_core_course_exists(array('shortname' => 'invalidcoursenumsectionscreate', 'numsections' => 7));
    }

    /**
     * Validate that invalid startdate values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseStartdateOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursestartdatecreate',
                                            'startdate' => 'invalidstartdate'));
        $this->assert_core_course_does_not_exist('invalidcoursestartdatecreate');
    }

    /**
     * Validate that invalid startdate values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseStartdateOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursestartdateupdate',
                                            'startdate' => 'Jan/01/2012'));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcoursestartdateupdate',
                      'startdate' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursestartdateupdate',
                                                    'startdate' => mktime(0, 0, 0, 1, 1, 2012)));
    }

    /**
     * Validate that invalid newsitems values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNewsitemsOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursenewsitemscreate',
                                            'newsitems' => 99999));
        $this->assert_core_course_does_not_exist('invalidcoursenewsitemscreate');
    }

    /**
     * Validate that invalid newsitems values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseNewsitemsOnUpdate() {
        //setup
        //$this->init_contexts_and_site_course();

        $this->run_core_course_import(array('shortname' => 'invalidcoursenewsitemsupdate',
                                            'newsitems' => 7));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcoursenewsitemsupdate',
                      'newsitems' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursenewsitemsupdate',
                                                    'newsitems' => 7));
    }

    /**
     * Validate that invalid showgrades values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowgradesOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowgradescreate',
                                            'showgrades' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseshowgradescreate');
    }

    /**
     * Validate that invalid showgrades values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseShowgradesOnUpdate() {
        //setup
        //$this->init_contexts_and_site_course();

        $this->run_core_course_import(array('shortname' => 'invalidcourseshowgradesupdate',
                                            'showgrades' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcourseshowgradesupdate',
                      'showgrades' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseshowgradesupdate',
                                                    'showgrades' => 1));
    }

    /**
     * Validate that invalid showreports values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowreportsOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowreportscreate',
                                            'showreports' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseshowreportscreate');
    }

    /**
     * Validate that invalid showreports values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseShowreportsOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowreportsupdate',
                                            'showreports' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcourseshowreportsupdate',
                      'showreports' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseshowreportsupdate',
                                                    'showreports' => 1));
    }

    /**
     * Validate that invalid maxbytes values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseMaxbytesOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursemaxbytescreate',
                                            'maxbytes' => 25));
        $this->assert_core_course_does_not_exist('invalidcoursemaxbytescreate');
    }

    /**
     * Validate that invalid maxbytes values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseMaxbytesOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursemaxbytesupdate',
                                            'maxbytes' => 0));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcoursemaxbytesupdate',
                      'maxbytes' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursemaxbytesupdate',
                                                    'maxbytes' => 0));
    }

    /**
     * Validate that invalid guest values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseGuestOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseguestcreate',
                                            'guest' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseguestcreate');
    }

    /**
     * Validate that invalid guest values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseGuestOnUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $this->run_core_course_import(array('shortname' => 'invalidcourseguestupdate',
                                            'guest' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcourseguestupdate',
                      'guest' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'invalidcourseguestupdate'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_ENABLED));
    }

    /**
     * Validate that invalid visible values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseVisibleOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursevisiblecreate',
                                            'visible' => 2));
        $this->assert_core_course_does_not_exist('invalidcoursevisiblecreate');
    }

    /**
     * Validate that invalid visible values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseVisibleOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursevisibleupdate',
                                            'visible' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcoursevisibleupdate',
                      'visible' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursevisibleupdate',
                                                    'visible' => 1));
    }

    /**
     * Validate that invalid lang values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseLangOnCreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourselangcreate',
                                            'lang' => 'boguslang'));
        $this->assert_core_course_does_not_exist('invalidcourselangcreate');
    }

    /**
     * Validate that invalid lang values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseLangOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourselangupdate',
                                            'lang' => 'en'));

        $data = array('action' => 'update',
                      'shortname' => 'invalidcourselangupdate',
                      'lang' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'invalidcourselangupdate',
                                                    'lang' => 'en'));
    }

    /**
     * Validate that the import does not set unsupported fields on course creation
     */
    public function testVersion1ImportPreventsSettingUnsupportedCourseFieldsOnCreate() {
        global $DB;

        $starttime = time();

        $data = array();
        $data['shortname'] = 'unsupportedcoursefieldscreate';
        $data['sortorder'] = 999;
        $data['timecreated'] = 25;
        $data['completionnotify'] = 7;
        $this->run_core_course_import($data);

        $select = "shortname = :shortname AND
                   timecreated >= :starttime";
        $params = array('shortname' => 'unsupportedcoursefieldscreate',
                        'starttime' => $starttime);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        //make sure sortorder isn't set to the supplied value
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldscreate',
                                                     'sortorder' => 999));
        $this->assertEquals($exists, false);

        //make sure completionnotify isn't set to the supplied value
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldscreate',
                                                     'completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the import does not set unsupported fields on course update
     */
    public function testVersion1ImportPreventsSettingUnsupportedCourseFieldsOnUpdate() {
        global $DB;

        $starttime = time();

        $this->run_core_course_import(array('shortname' => 'unsupportedcoursefieldsupdate'));

        $data = array();
        $data['action'] = 'update';
        $data['shortname'] = 'unsupportedcoursefieldsupdate';
        $data['sortorder'] = 999;
        $data['timecreated'] = 25;
        $data['completionnotify'] = 7;

        $this->run_core_course_import($data, false);

        $select = "shortname = :shortname AND
                   timecreated >= :starttime";
        $params = array('shortname' => 'unsupportedcoursefieldsupdate',
                        'starttime' => $starttime);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        //make sure sortorder isn't set to the supplied value
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldsupdate',
                                                     'sortorder' => 999));
        $this->assertEquals($exists, false);

        //make sure completionnotify isn't set to the supplied value
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldsupdate',
                                                     'completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that field-length checking works correctly on course creation
     */
    public function testVersion1ImportPreventsLongCourseFieldsOnCreate() {
        $this->run_core_course_import(array('shortname' => 'coursefullnametoolongcreate',
                                            'fullname' => str_repeat('a', 255)));
        $this->assert_core_course_does_not_exist('coursefullnametoolongcreate');

        $shortname = str_repeat('a', 101);
        $this->run_core_course_import(array('shortname' => $shortname));
        $this->assert_core_course_does_not_exist($shortname);

        $this->run_core_course_import(array('shortname' => 'courseidnumbertoolongcreate',
                                            'idnumber' => str_repeat('a', 101)));
        $this->assert_core_course_does_not_exist('courseidnumbertoolongcreate');
    }

    /**
     * Validate that field-length checking works correctly on course update
     */
    public function testVersion1ImportPreventsLongCourseFieldsOnUpdate() {
        $this->run_core_course_import(array('shortname' => 'coursefullnametoolongupdate',
                                            'fullname' => 'coursefullnametoolongupdatefullname',
                                            'idnumber' => 'coursefullnametoolongupdateidnumber'));

        $params = array('action' => 'update',
                        'shortname' => 'coursefullnametoolongupdate',
                        'fullname' => str_repeat('a', 256));
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array('shortname' => 'coursefullnametoolongupdate',
                                                    'fullname' => 'coursefullnametoolongupdatefullname'));

        $params = array('action' => 'update',
                        'shortname' => 'coursefullnametoolongupdate',
                        'idnumber' => str_repeat('a', 101));
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array('shortname' => 'coursefullnametoolongupdate',
                                                    'idnumber' => 'coursefullnametoolongupdateidnumber'));
    }

    /**
     * Validate that the import does not create duplicate course records on creation
     */
    public function testVersion1ImportPreventsDuplicateCourseCreation() {
        global $DB;

        $initial_count = $DB->count_records('course');

        //set up our data
        $this->run_core_course_import(array('shortname' => 'preventduplicatecourses'));
        $count = $DB->count_records('course');
        $this->assertEquals($initial_count + 1, $count);

        //test duplicate username
        $this->run_core_course_import(array('shortname' => 'preventduplicatecourses'));
        $count = $DB->count_records('course');
        $this->assertEquals($initial_count + 1, $count);
    }

    /**
     * Validate that the import can create a course in a category whose name
     * is unique
     */
    public function testVersion1ImportCreatesCourseInUniqueCategory() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'uniquecategorycreate';
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniquecategorycreate',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category whose name
     * is unique
     */
    public function testVersion1ImportMovesCourseToUniqueCategory() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'uniquecategoryupdate';
        $this->run_core_course_import($data);

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array('action' => 'update',
                      'shortname' => 'uniquecategoryupdate',
                      'category' => 'newcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'uniquecategoryupdate',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using a relative category path
     */
    public function testVersion1ImportCreatesCourseInUniqueRelativeCategoryPath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory/childcategory');
        $data['shortname'] = 'uniquerelativepathcreate';
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniquerelativepathcreate',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique relative category path
     */
    public function testVersion1ImportMovesCourseToUniqueRelativeCategoryPath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory');
        $data['shortname'] = 'uniquerelativepathupdate';
        $this->run_core_course_import($data);

        $data = array('action' => 'update',
                      'shortname' => 'uniquerelativepathupdate',
                      'category' => 'parentcategory/childcategory');
        $this->run_core_course_import($data, false);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $this->assert_record_exists('course', array('shortname' => 'uniquerelativepathupdate',
                                                    'category' => $child_category_id));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using an absolute category path
     */
    public function testVersion1ImportCreatesCourseInUniqueAbsoluteCategoryPath() {
        global $DB;

        $data = $this->get_core_course_data('/parentcategory/childcategory');
        $data['shortname'] = 'uniqueabsoluatepathcreate';
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniqueabsoluatepathcreate',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique absolute category path
     */
    public function testVersion1ImportMovesCourseToUniqueAbsoluteCategoryPath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory');
        $data['shortname'] = 'uniqueabsoluatepathupdate';
        $this->run_core_course_import($data);

        $data = array('action' => 'update',
                      'shortname' => 'uniqueabsoluatepathupdate',
                      'category' => '/parentcategory/childcategory');
        $this->run_core_course_import($data, false);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $this->assert_record_exists('course', array('shortname' => 'uniqueabsoluatepathupdate',
                                                    'category' => $child_category_id));
    }

    /**
     * Validate that the import only creates a course in a non-unique category
     * if the absolute path is specified
     */
    public function testVersion1ImportCreatesCourseOnlyInAbsoluteCategoryPath() {
        global $DB;

        //make sure specifying an ambigious relative path does not create a course
        $data = $this->get_core_course_data('duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'ambiguousrelativepathcreate';
        $this->run_core_course_import($data);
        $this->assert_core_course_does_not_exist('ambiguousrelativepathcreate');

        //make sure specifying a non-ambiguous absolute path creates the course
        $data = $this->get_core_course_data('/duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'ambiguousrelativepathcreate';
        $this->run_core_course_import($data);

        $child_category = $DB->get_record_sql("SELECT child.*
                                               FROM {course_categories} child
                                               JOIN {course_categories} parent
                                                 ON child.parent = parent.id
                                               WHERE child.name = ?
                                                 AND parent.parent = 0", array('duplicatechildcategory'));
        $exists = $DB->record_exists('course', array('shortname' => 'ambiguousrelativepathcreate',
                                                     'category' => $child_category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import only moves a course to a non-unique category
     * if the absolute path is specified
     */
    public function testVersion1ImportMovesCourseOnlyToAbsoluteCategoryPath() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'absolutecategorypathupdate';
        $this->run_core_course_import($data);

        $duplicate_child_category = $DB->get_record_sql("SELECT *
                                                         FROM {course_categories} parent
                                                         WHERE name = ?
                                                         AND NOT EXISTS (
                                                           SELECT *
                                                           FROM {course_categories} child
                                                          WHERE parent.id = child.parent
                                                         )", array('duplicatechildcategory'));

        //make sure specifying an ambigious relative path does not move a course
        $data = array('action' => 'update',
                      'shortname' => 'absolutecategorypathupdate',
                      'category' => 'duplicateparentcategory/duplicatechildcategory');
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array('shortname' => 'absolutecategorypathupdate',
                                                     'category' => $duplicate_child_category->id));
        $this->assertEquals($exists, false);

        //make sure specifying a non-ambiguous absolute path moves the course
        $data = array('action' => 'update',
                      'shortname' => 'absolutecategorypathupdate',
                      'category' => 'duplicateparentcategory/duplicatechildcategory/duplicateparentcategory/duplicatechildcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'absolutecategorypathupdate',
                                                    'category' => $duplicate_child_category->id));
    }

    /**
     * Validate that the import can create a course in an existing category
     * based on the category's database record id
     */
    public function testVersion1ImportCreatesCourseInCategoryFromRecordId() {
        global $DB;

        //setup
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $new_category = new stdClass;
        $new_category->name = $categoryid;
        $new_category->id = $DB->insert_record('course_categories', $new_category);
        get_context_instance(CONTEXT_COURSECAT, $new_category->id);
        build_context_path(true);

        //run the import
        $data = $this->get_core_course_data($categoryid);
        $data['shortname'] = 'categoryidcreate';
        $data['category'] = $categoryid;
        $this->run_core_course_import($data);
        $DB->delete_records('course_categories', array('id' => $new_category->id));

        $exists = $DB->record_exists('course', array('shortname' => 'categoryidcreate',
                                                     'category' => $new_category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into an existing category
     * based on the category's database record id
     */
    public function testVersion1ImportMovesCourseIntoCategoryFromRecordId() {
        global $DB;

        //setup
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'categoryidupdate';
        $this->run_core_course_import($data);

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $new_category = new stdClass;
        $new_category->name =  $categoryid;
        $new_category->id = $DB->insert_record('course_categories', $new_category);
        get_context_instance(CONTEXT_COURSECAT, $new_category->id);
        build_context_path(true);

        $data = array('action' => 'update',
                      'shortname' => 'categoryidupdate',
                      'category' => $new_category->id);
        $this->run_core_course_import($data, false);
        $DB->delete_records('course_categories', array('id' => $new_category->id));

        $this->assert_record_exists('course', array('shortname' => 'categoryidupdate',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the course import handles escaped slashes in course
     * category names correctly
     */
    public function testVersion1ImportCreatesCourseInCategoryWithSlash() {
        global $DB;

        //create category
        $category = new stdClass;
        $category->name = 'slash/slash';
        $category->id = $DB->insert_record('course_categories', $category);

        //run import
        $data = $this->get_core_course_data('slash\\/slash');
        $data['shortname'] = 'categoryslashcreate';
        $this->run_core_course_import($data);

        //make sure the import completed
        $exists = $DB->record_exists('course', array('shortname' => 'categoryslashcreate',
                                                     'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import handles escaped backslashes in course
     * category names correctly
     */
    public function testVersion1ImportCreatesCourseInCategoryWithBackslash() {
        global $DB;

        //create category
        $category = new stdClass;
        $category->name = 'backslash\\backslash';
        $category->id = $DB->insert_record('course_categories', $category);

        //run import
        $data = $this->get_core_course_data('backslash\\\\backslash');
        $data['shortname'] = 'categorybackslashcreate';
        $this->run_core_course_import($data);

        //make sure the import completed
        $exists = $DB->record_exists('course', array('shortname' => 'categorybackslashcreate',
                                                     'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not create a course in a category whose
     * name is not unique
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueCategory() {
        $data = $this->get_core_course_data('duplicatechildcategory');
        $data['shortname'] = 'createinnonuniquecategory1';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory1');
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using a relative category path
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueRelativeCategoryPath() {
        $data = $this->get_core_course_data('duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'createinnonuniquecategory2';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory2');
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using an absolute category path
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueAbsoluteCategoryPath() {
        $data = $this->get_core_course_data('/nonuniqueabsoluteparent/nonuniqueabsolutechild');
        $data['shortname'] = 'createinnonuniquecategory3';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory3');
    }

    /**
     * Validate that category handling prioritizes category names over database
     * ids in the case where a numeric category is supplied
     */
    public function testVersion1ImportPrioritizesCategoryNamesOverIds() {
        global $DB;

        $testcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //create a category whose name is the id of the existing category
        $category = new stdClass;
        $category->name = $testcategoryid;
        $category->id = $DB->insert_record('course_categories', $category);

        $data = $this->get_core_course_data($testcategoryid);
        $data['shortname'] = 'prioritizecategoryname';
        $data['category'] = $testcategoryid;
        $this->run_core_course_import($data, false);
        $DB->delete_records('course_categories', array('id' => $category->id));

        //make sure category was identified by name
        $this->assert_record_exists('course', array('shortname' => 'prioritizecategoryname',
                                                    'category' => $category->id));
    }

    /**
     * Validate that the course import creates a single top-level category and
     * assigns a new course to it
     */
    public function testVersion1ImportCourseCreateCreatesCategoryFromName() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('createcategorycreate');
        $data['shortname'] = 'createcategorycreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 1);

        //validate course and category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createcategorycreate', 'createcategorycreate',
                                                     0, 1));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a single top-level category and
     * assigns an existing course to it
     */
    public function testVersion1ImportCourseUpdateCreatesCategoryFromName() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'createcategoryupdate'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'createcategoryupdate',
                      'category' => 'createcategoryupdate');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 1);

        //validate course and category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createcategoryupdate', 'createcategoryupdate',
                                                     0, 1));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns a new course to the child category using a "relative" specification
     */
    public function testVersion1ImportCourseCreateCreatesCategoriesFromRelativePathWithNonexistentPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('createrelativenonexistentparentcreate/createrelativenonexistentchildcreate');
        $data['shortname'] = 'createrelativenonexistentcreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 2);

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'createrelativenonexistentparentcreate',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createrelativenonexistentparentcreate'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createrelativenonexistentcreate', 'createrelativenonexistentchildcreate',
                                                     $parentid, 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns an existing course to the child category using a "relative"
     * specification
     */
    public function testVersion1ImportCourseUpdateCreatesCategoriesFromRelativePathWithNonexistentPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'createrelativenonexistentupdate'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'createrelativenonexistentupdate',
                      'category' => 'createrelativenonexistentparentupdate/createrelativenonexistentchildupdate');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 2);

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'createrelativenonexistentparentupdate',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createrelativenonexistentparentupdate'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createrelativenonexistentupdate', 'createrelativenonexistentchildupdate',
                                                     $parentid, 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns a
     * new course to the child category using a "relative" specification
     */
    public function testVersion1ImportCourseCreateCreatesCategoryFromRelativePathWithExistingPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('parentcategory/childcategory/createrelativeexistentcreatechild');
        $data['shortname'] = 'createrelativeexistentcreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createrelativeexistentcreate', 'createrelativeexistentcreatechild',
                                                     $childid, 3));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns an
     * existing course to the child category using a "relative" specification
     */
    public function testVersion1ImportCourseUpdateCreatesCategoryFromRelativePathWithExistingPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'createrelativeexistentupdate'));

        //create grandparent category
        $this->create_test_category('testgrandparentcategory');
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'testgrandparentcategory'));

        //create parent category
        $this->create_test_category('testparentcategory', $grandparentid);

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'createrelativeexistentupdate',
                      'category' => 'testgrandparentcategory/testparentcategory/createrelativeexistentupdatechild');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 3);

        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'testparentcategory'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createrelativeexistentupdate', 'createrelativeexistentupdatechild',
                                                     $parentid, 3));
        $this->assertEquals($exists, true);
    }

     /**
     * Validate that the course import creates a parent and child category and
     * assigns a new course to the child category using an "absolute" specification
     */
    public function testVersion1ImportCourseCreateCreatesCategoriesFromAbsolutePathWithNonexistentPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('/createabsolutenonexistentcreateparent/createabsolutenonexistentcreatechild');
        $data['shortname'] = 'createabsolutenonexistentcreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 2);

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'createabsolutenonexistentcreateparent',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createabsolutenonexistentcreateparent'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createabsolutenonexistentcreate', 'createabsolutenonexistentcreatechild',
                                                     $parentid, 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns an existing course to the child category using an "absolute"
     * specification
     */
    public function testVersion1ImportCourseUpdateCreatesCategoriesFromAbsolutePathWithNonexistentPrefix() {
        global $DB;

        //get initial counts
        $initial_num_courses = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'createabsolutenonexistentupdate'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'createabsolutenonexistentupdate',
                      'category' => '/createabsolutenonexistentupdateparent/createabsolutenonexistentupdatechild');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_courses + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 2);

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'createabsolutenonexistentupdateparent',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createabsolutenonexistentupdateparent'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createabsolutenonexistentupdate', 'createabsolutenonexistentupdatechild',
                                         $parentid, 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns a
     * new course to the child category using an "absolute" specification
     */
    public function testVersion1ImportCourseCreateCreatesCategoryFromAbsolutePathWithExistingPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('/parentcategory/childcategory/createabsoluteexistentcreatechild');
        $data['shortname'] = 'createabsoluteexistentcreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createabsoluteexistentcreate', 'createabsoluteexistentcreatechild',
                                                     $childid, 3));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns an
     * existing course to the child category using an "absolute" specification
     */
    public function testVersion1ImportCourseUpdateCreatesCategoryFromAbsolutePathWithExistingPrefix() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'createabsoluteexistentupdate'));

        //get parent category
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'createabsoluteexistentupdate',
                      'category' => '/parentcategory/childcategory/createabsoluteexistentupdatechild');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createabsoluteexistentupdate', 'createabsoluteexistentupdatechild',
                                                     $childid, 3));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a grandparent, parent and child
     * category and assigns a new course to the child category
     */
    public function testVersion1ImportCourseCreateCreatesCategoryPath() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('/coursecreatecreatespathgrandparent/coursecreatecreatespathparent/coursecreatecreatespathchild');
        $data['shortname'] = 'coursecreatecreatespath';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 3);

        //validate grandparent category
        $this->assert_record_exists('course_categories', array('name' => 'coursecreatecreatespathgrandparent',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'coursecreatecreatespathgrandparent'));

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'coursecreatecreatespathparent',
                                                               'parent' => $grandparentid,
                                                               'depth' => 2));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'coursecreatecreatespathparent'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('coursecreatecreatespath', 'coursecreatecreatespathchild',
                                                     $parentid, 3));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a grandparent, parent and child
     * category and assigns an existing course to the child category
     */
    public function testVersion1ImportCourseUpdateCreatesCategoryPath() {
        global $DB;

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course and category
        $this->run_core_course_import(array('shortname' => 'courseupdatecreatespath'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'courseupdatecreatespath',
                      'category' => '/courseupdatecreatespathgrandparent/courseupdatecreatespathparent/courseupdatecreatespathchild');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_course + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories + 3);

        //validate grandparent category
        $this->assert_record_exists('course_categories', array('name' => 'courseupdatecreatespathgrandparent',
                                                               'parent' => 0,
                                                               'depth' => 1));
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'courseupdatecreatespathgrandparent'));

        //validate parent category
        $this->assert_record_exists('course_categories', array('name' => 'courseupdatecreatespathparent',
                                                               'parent' => $grandparentid,
                                                               'depth' => 2));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'courseupdatecreatespathparent'));

        //validate course and child category assignment
        $sql = "SELECT *
                FROM {course} c
                JOIN {course_categories} cc
                ON c.category = cc.id
                WHERE c.shortname = ?
                AND cc.name = ?
                AND cc.parent = ?
                AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('courseupdatecreatespath', 'courseupdatecreatespathchild',
                                                     $parentid, 3));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import only allow for category creation when
     * the specified path is non-ambiguous (course create)
     */
    public function testVersion1ImportCourseCreatePreventsCreatingCategoryWithAmbiguousParentPath() {
        global $DB;

        //get initial counts
        $initial_num_courses = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import
        $data = $this->get_core_course_data('/nonuniqueabsoluteparent/nonuniqueabsolutechild/ambiguousparentcreatecategory');
        $data['shortname'] = 'ambiguousparentcreate';
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_courses);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories);
    }

    /**
     * Validate that the course import only allow for category creation when
     * the specified path is non-ambiguous (course update)
     */
    public function testVersion1ImportCourseUpdatePreventsCreatingCategoryWithAmbiguousParentPath() {
        global $DB;

        //get initial counts
        $initial_num_courses = $DB->count_records('course');
        $initial_num_categories = $DB->count_records('course_categories');

        //run import to create initial course
        $this->run_core_course_import(array('shortname' => 'ambiguousparentupdate'));

        //run import to move course to new category
        $data = array('action' => 'update',
                      'shortname' => 'ambiguousparentupdate',
                      'category' => '/nonuniqueabsoluteparent/nonuniqueabsolutechild/ambiguousparentupdatecategory');
        $this->run_core_course_import($data, false);

        //validate counts
        $this->assertEquals($DB->count_records('course'), $initial_num_courses + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initial_num_categories);
    }

    /**
     * Validate that updating users does not produce any side-effects
     * in the user data
     */
    public function testVersion1ImportOnlyUpdatesSuppliedCourseFields() {
        global $DB;

        $this->run_core_course_import(array('shortname' => 'updatescoursefields'));

        $data = array('action' => 'update',
                      'shortname' => 'updatescoursefields',
                      'fullname' => 'updatedfullname');

        $this->run_core_course_import($data, false);

        $data = $this->get_core_course_data('childcategory');
        unset($data['entity']);
        unset($data['action']);
        $data['shortname'] = 'updatescoursefields';
        $data['fullname'] = 'updatedfullname';
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $data['category'] = $categoryid;

        $this->assert_record_exists('course', $data);
    }

    /**
     * Validate that update actions must match existing courses to do anything
     */
    public function testVersion1ImportDoesNotUpdateNonmatchingCourses() {
        $this->run_core_course_import(array('shortname' => 'updatenonmatching',
                                            'fullname' => 'fullname'));

        $check_data = array('shortname' => 'updatenonmatching',
                            'fullname' => 'fullname');

        //bogus shortname
        $data = array('action' => 'update',
                      'shortname' => 'bogus',
                      'fullname' => 'newfullname');
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $check_data);
    }

    /**
     * Validate that the plugin supports creating a course with guest enrolment
     * enabled
     */
    public function testVersion1ImportSupportsCreatingWithGuestEnrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //create course with guest flag and password
        $this->run_core_course_import(array('shortname' => 'createwithguest',
                                            'guest' => 1,
                                            'password' => 'password'));
        //validate plugin configuration
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'createwithguest'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => 'password',
                                                   'status' => ENROL_INSTANCE_ENABLED));
    }

    /**
     * Validate that the plugin supports creating a course with guest enrolment
     * disabled
     */
    public function testVersion1ImportSupportsCreatingWithoutGuestEnrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //create course with guest flag disabled and no password
        $this->run_core_course_import(array('shortname' => 'createwithoutguest',
                                            'guest' => 0));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'createwithoutguest'));
        //validate plugin configuration
        //todo: change password back to NULL if the guest plugin starts using
        //it as the default again
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => '',
                                                   'status' => ENROL_INSTANCE_DISABLED));
    }

    /**
     * Validate that the plugin supports updating a course, enabling guest
     * enrolment and setting a password
     */
    public function testVersion1ImportSupportsEnablingGuestEnrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //create course with guest flag disabled and no password
        $this->run_core_course_import(array('shortname' => 'enableguestenrolment',
                                            'guest' => 0));
        //validate plugin configuration
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'enableguestenrolment'));
        //todo: change password back to NULL if the guest plugin starts using
        //it as the default again
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => '',
                                                   'status' => ENROL_INSTANCE_DISABLED));

        //update course, enabling plugin and creating a password
        $data = array('action' => 'update',
                      'shortname' => 'enableguestenrolment',
                      'guest' => 1,
                      'password' => 'password');
        $this->run_core_course_import($data, false);
        //validate plugin configuration
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => 'password',
                                                   'status' => ENROL_INSTANCE_ENABLED));
    }

    /**
     * Validate that the plugin supports updating a course, disabling guest
     * enrolment
     */
    public function testVersion1ImportSupportsDisablingGuestEnrolment () {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //create course with guest flag enabled and password
        $this->run_core_course_import(array('shortname' => 'disableguestenrolment',
                                            'guest' => 1,
                                            'password' => 'password'));

        //validate setup
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'disableguestenrolment'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => 'password',
                                                   'status' => ENROL_INSTANCE_ENABLED));

        //update course, disabling guest access
        $data = array('action' => 'update',
                      'shortname' => 'disableguestenrolment',
                      'guest' => 0);
        $this->run_core_course_import($data, false);

        //validate plugin configuration
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED));
    }

    public function testVersion1ImportPreventsInvalidGuestEnrolmentConfigurationsOnCreate() {
        //validate that passwords require enrolments to be enabled
        set_config('defaultenrol', 0, 'enrol_guest');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate',
                                            'password' => 'asdf'));
        set_config('defaultenrol', 1, 'enrol_guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate',
                                            'guest' => 0,
                                            'password' => 'asdf'));
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        //validate that creation with guest access fails when the guest plugin
        //is globally disabled
        set_config('enrol_plugins_enabled', 'manual');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate',
                                            'guest' => 1));
        set_config('enrol_plugins_enabled', 'manual,guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        //validate that creation with guest access fails when not adding the
        //guest plugin to courses
        set_config('defaultenrol', 0, 'enrol_guest');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate',
                                            'guest' => 1));
        set_config('defaultenrol', 1, 'enrol_guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');
    }

    public function testVersion1ImportPreventsInvalidGuestEnrolmentConfigurationsOnUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationupdate',
                                            'guest' => 0));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'invalidguestconfigurationupdate'));

        //validate that passwords require enrolments to be enabled
        $this->run_core_course_import(array('action' => 'update',
                                            'shortname' => 'invalidguestconfigurationupdate',
                                            'password' => 'asdf'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED));

        $this->run_core_course_import(array('action' => 'update',
                                            'shortname' => 'invalidguestconfigurationupdate',
                                            'guest' => 0,
                                            'password' => 'asdf'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED));

        //validate that creation with guest access fails when the guest plugin
        //is globally disabled
        set_config('enrol_plugins_enabled', 'manual');
        $this->run_core_course_import(array('action' => 'update',
                                            'shortname' => 'invalidguestconfigurationupdate',
                                            'guest' => 1));
        $exists = $DB->record_exists('enrol', array('courseid' => $courseid,
                                                    'enrol' => 'guest',
                                                    'status' => ENROL_INSTANCE_DISABLED));
        set_config('enrol_plugins_enabled', 'manual,guest');
        $this->assertEquals($exists, true);

        //validate that creation with guest access fails when not adding the
        //guest plugin to courses
        $DB->delete_records('enrol', array('courseid' => $courseid));
        $this->run_core_course_import(array('action' => 'update',
                                            'shortname' => 'invalidguestconfigurationupdate',
                                            'guest' => 1));
        $exists = $DB->record_exists('enrol', array('courseid' => $courseid,
                                                    'enrol' => 'guest'));
        $this->assertEquals($exists, false);

        //validate that creation with guest access fails when not adding the
        //guest plugin to courses
        $this->run_core_course_import(array('action' => 'update',
                                            'shortname' => 'invalidguestconfigurationupdate',
                                            'password' => 'asdf'));
        $exists = $DB->record_exists('enrol', array('courseid' => $courseid,
                                                    'enrol' => 'guest'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that there are no side-effects of enabling or disabling guest
     * access twice
     */
    public function testVersion1ImportCompletesImportWhenEnablingOrDisablingGuestEnrolmentTwice() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //create course without guest access or password
        $this->run_core_course_import(array('shortname' => 'plugintwice'));

        //disable guest access in update action
        $data = array('action' => 'update',
                      'shortname' => 'plugintwice',
                      'guest' => 0);
        $this->run_core_course_import($data);

        //data validation
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'plugintwice'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid,
                                                              'enrol' => 'guest')), 1);
        //todo: change password back to NULL if the guest plugin starts using
        //it as the default again
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED,
                                                   'password' => ''));

        //clean up category
        $DB->delete_records('course_categories');

        //create course with guest access
        $data = array('shortname' => 'plugintwice2',
                      'guest' => 1);

        //enable guest access in update action
        $this->run_core_course_import($data);
        $data = array('action' => 'update',
                      'shortname' => 'plugintwice2',
                      'guest' => 1);
        $this->run_core_course_import($data);

        //data validation
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'plugintwice2'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid,
                                                              'enrol' => 'guest')), 1);
        //todo: change password back to NULL if the guest plugin starts using
        //it as the default again
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_ENABLED,
                                                   'password' => ''));
    }

    /**
     * Validate that the plugin prevents configuring a deleted guest enrolment
     * plugin
     */
    public function testVersionImportPreventsConfiguringRemovedGuestPlugin() {
        global $DB;

        //run basic import
        $this->run_core_course_import(array('shortname' => 'removedguestplugin',
                                            'fullname' => 'fullname'));
        //delete plugin from course
        $DB->delete_records('enrol', array('enrol' => 'guest'));

        $expected = array('shortname' => 'removedguestplugin',
                          'fullname' => 'fullname');

        //validate for specifying guest value of 0
        $data = array('action' => 'update',
                      'shortname' => 'removedguestplugin',
                      'fullname' => 'updatedfullname',
                      'guest' => 0);
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        //validate for specifying guest value of 1
        $data = array('action' => 'update',
                      'shortname' => 'removedguestplugin',
                      'fullname' => 'updatedfullname',
                      'guest' => 1);
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        //validate for specifying a password value
        $data = array('action' => 'update',
                      'shortname' => 'removedguestplugin',
                      'fullname' => 'updatedfullname',
                      'password' => 'password');
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);
    }

    /**
     * Validate that the course rollover via link / template sets up the right
     * data
     */
    public function testVersion1ImportRolloverSetsCorrectCourseData() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //get initial counts
        $initial_num_courses = $DB->count_records('course');

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $initial_num_courses_in_category = $DB->get_field('course_categories', 'coursecount', array('id' => $categoryid));
        $initial_num_forums = $DB->count_records('forum');

        //setup
        set_config('backup_general_activities', 1, 'backup');

        //create a test course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'rollovertemplateshortname';
        $record->fullname = 'rollovertemplatefullname';
        $record->id = $DB->insert_record('course', $record);
        //make sure we have a section to work with
        $section = get_course_section(1, $record->id);

        //create a test forum instance
        $forum = new stdClass;
        $forum->course = $record->id;
        $forum->type = 'news';
        $forum->name = 'rollovertemplateforum';
        $forum->intro = 'rollovertemplateintro';
        $forum->id = $DB->insert_record('forum', $forum);

        //add it as a course module
        $forum->module = $DB->get_field('modules', 'id', array('name' => 'forum'));
        $forum->instance = $forum->id;
        $forum->section = $section->id;
        $cmid = add_course_module($forum);

        //run the import
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'rollovershortname';
        $data['link'] = 'rollovertemplateshortname';
        $this->run_core_course_import($data, false);

        //validate the number of courses
        $this->assertEquals($DB->count_records('course'), $initial_num_courses + 2);

        //validate the course course data, as well as category and sortorder
        $sortorder = $DB->get_field('course', 'sortorder', array('shortname' => 'rollovertemplateshortname'));
        $this->assert_record_exists('course', array('shortname' => 'rollovershortname',
                                                    'fullname' => 'rlipfullname',
                                                    'category' => $categoryid,
                                                    'sortorder' => $sortorder - 1
                                                    ));

        //validate that the category is updated with the correct number of courses
        $this->assert_record_exists('course_categories', array('id' => $categoryid,
                                                               'coursecount' => $initial_num_courses_in_category + 2));

        //validate that the correct number of forum instances exist
        $this->assertEquals($DB->count_records('forum'), $initial_num_forums + 2);

        //validate the specific forum / course module setup within the new
        //course
        $sql = "SELECT *
                FROM {modules} m
                JOIN {course_modules} cm
                  ON m.id = cm.module
                JOIN {forum} f
                  ON cm.instance = f.id
                JOIN {course} c
                  ON cm.course = c.id
                JOIN {course_sections} cs
                  ON c.id = cs.course
                  AND cm.section = cs.id
                WHERE f.type = ?
                  AND f.name = ?
                  AND f.intro = ?
                  AND c.shortname = ?";

        $exists = $DB->record_exists_sql($sql, array('news', 'rollovertemplateforum',
                                                     'rollovertemplateintro', 'rollovershortname'));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that we can roll over into a different category
     */
    public function testVersion1ImportRolloverSupportsSettingCategory() {
        global $DB;

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //create a test course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'categorytemplateshortname';
        $record->fullname = 'categorytemplatefullname';
        $record->id = $DB->insert_record('course', $record);

        //create a second test category
        $category = $this->create_test_category('templatecategory');
        $secondcategoryid = $DB->get_field('course_categories', 'id', array('name' => $category));

        //run the import
        $data = $this->get_core_course_data($category);
        $data['shortname'] = 'categorytemplatecopyshortname';
        $data['link'] = 'categorytemplateshortname';
        $this->run_core_course_import($data, false);

        //validate that the courses are each in their respective categories
        $this->assert_record_exists('course', array('shortname' => 'categorytemplateshortname',
                                                    'category' => $categoryid));
        $this->assert_record_exists('course', array('shortname' => 'categorytemplatecopyshortname',
                                                    'category' => $secondcategoryid));
    }

    /**
     * Validate that the course rollover via link / template does not include
     * user data
     */
    public function testVersion1ImportRolloverExcludesUsers() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //create a test user
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->id = user_create_user($user);

        //create a test template course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'nousertemplateshortname';
        $record->fullname = 'nousertemplatefullname';
        $record = $course = create_course($record);

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $course->id;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //create a test role
        $roleid = create_role('rolloverrole', 'rolloverrole', 'rolloverrole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        //enrol the test user into the template course, assigning them the test
        //role
        enrol_try_internal_enrol($record->id, $user->id, $roleid);

        //validate setup
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        //run the import
        $this->run_core_course_import(array('link' => 'rliptemplateshortname'));

        //validate that no role assignments or enrolments were created
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that invalid template / link values can't be set on course create
     */
    public function testVersion1ImportPreventsInvalidLinkOnCreate() {
        global $DB;

        //validate setup
        //$this->assertEquals($DB->count_records('course'), 1);
        $initial_num_courses = $DB->count_records('course');

        //run the import
        $this->run_core_course_import(array('shortname' => 'invalidlink',
                                            'link' => 'bogusshortname'));

        //validate that no new course was created
        $this->assertEquals($DB->count_records('course'), $initial_num_courses);
    }

    /**
     * Validate that course create and update actions set time created
     * and time modified appropriately
     */
    public function testVersion1ImportSetsCourseTimestamps() {
        global $DB;

        //record the current time
        $starttime = time();

        //set up base data
        $this->run_core_course_import(array('shortname' => 'coursetimestamps'));

        //validate timestamps
        $where = "shortname = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('coursetimestamps', $starttime, $starttime);
        $exists = $DB->record_exists_select('course', $where, $params);
        $this->assertEquals($exists, true);

        //update data
        $this->run_core_course_import(array('shortname' => 'coursetimestamps',
                                            'action' => 'update',
                                            'username' => 'shortname',
                                            'fullname' => 'newfullname'));

        //validate timestamps
        $where = "shortname = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('coursetimestamps', $starttime, $starttime);
        $exists = $DB->record_exists_select('course', $where, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin supports course deletes
     */
    public function testVersion1ImportSupportsCourseDelete() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_delete');
        $required_fields = array('shortname');
        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin can delete courses based on shortname
     */
    public function testVersion1ImportDeletesCourseBasedOnShortname() {
        global $DB;

        $this->run_core_course_import(array('shortname' => 'deleteshortname'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'deleteshortname'));

        $data = array('action' => 'delete',
                      'shortname' => 'deleteshortname');
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array('shortname' => 'deleteshortname'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the version 1 plugin does not delete courses when the
     * specified shortname is incorrect
     */
    public function testVersion1ImportDoesNotDeleteCourseWithInvalidShortname() {
        $this->run_core_course_import(array('shortname' => 'validshortname'));

        $data = array('action' => 'delete',
                      'shortname' => 'bogusshortname');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'validshortname'));
    }

    /**
     * Validate that the version 1 plugin deletes appropriate associations when
     * deleting a course
     */
    public function testVersion1ImportDeleteCourseDeletesAssociations() {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/backup/lib.php');
        require_once($CFG->dirroot.'/lib/conditionlib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/tag/lib.php');
        require_once($CFG->dirroot.'/lib/questionlib.php');

        //setup
        $initial_num_contexts = $DB->count_records('context', array('contextlevel' => CONTEXT_COURSE));

        //set up the course with one section, including default blocks
        set_config('defaultblocks_topics', 'search_forums');
        set_config('maxsections', 10, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'deleteassociationsshortname',
                                            'numsections' => 1));

        //create a user record
        $record = new stdClass;
        $record->username = 'testuser';
        $record->password = 'Testpass!0';
        $userid = user_create_user($record);

        //create a course-level role
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'deleteassociationsshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = create_role('deleterole', 'deleterole', 'deleterole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        //assign the user to the course-level role
        enrol_try_internal_enrol($courseid, $userid, $roleid);

        //create a grade item
        $grade_item = new grade_item(array('courseid' => $courseid,
                                           'itemtype' => 'manual',
                                           'itemname' => 'testitem'), false);
        $grade_item->insert();
        $grade_grade = new grade_grade(array('itemid' => $grade_item->id,
                                             'userid' => $userid), false);

        //assign the user a grade
        $grade_grade->insert();

        //create a grade outcome
        $grade_outcome = new grade_outcome(array('courseid' => $courseid,
                                                 'shortname' => 'bogusshortname',
                                                 'fullname' => 'bogusfullname'));
        $grade_outcome->insert();

        //create a grade scale
        $grade_scale = new grade_scale(array('courseid' => $courseid,
                                             'name' => 'bogusname',
                                             'userid' => $userid,
                                             'scale' => 'bogusscale',
                                             'description' => 'bogusdescription'));
        $grade_scale->insert();

        //set a grade setting value
        grade_set_setting($courseid, 'bogus', 'bogus');

        //set up a grade letter
        $gradeletter = new stdClass;
        $gradeletter->contextid = $course_context->id;
        $gradeletter->lowerboundary = 80;
        $gradeletter->letter = 'A';
        $DB->insert_record('grade_letters', $gradeletter);

        //set up a forum instance
        $forum = new stdClass;
        $forum->course = $courseid;
        $forum->intro = 'intro';
        $forum->id = $DB->insert_record('forum', $forum);

        //add it as a course module
        $forum->module = $DB->get_field('modules', 'id', array('name' => 'forum'));
        $forum->instance = $forum->id;
        $cmid = add_course_module($forum);

        //set up a completion record
        $completion = new stdClass;
        $completion->coursemoduleid = $cmid;
        $completion->completionstate = 0;
        $completion->userid = 9999;
        $completion->timemodified = time();
        $DB->insert_record('course_modules_completion', $completion);

        //set up a completion condition
        $forum->id = $cmid;
        $ci = new condition_info($forum, CONDITION_MISSING_EVERYTHING, false);
        $ci->add_completion_condition($cmid, COMPLETION_ENABLED);

        //set the block position
        $instance = $DB->get_record('block_instances', array('parentcontextid' => $course_context->id));
        $page = new stdClass;
        $page->context = $course_context;
        $page->pagetype = 'course-view-*';
        $page->subpage = false;
        blocks_set_visibility($instance, $page, 1);

        //create a group
        $group = new stdClass;
        $group->name = 'testgroup';
        $group->courseid = $courseid;
        $groupid = groups_create_group($group);

        //add the user to the group
        groups_add_member($groupid, $userid);

        //create a grouping containing our group
        $grouping = new stdClass;
        $grouping->name = 'testgrouping';
        $grouping->courseid = $courseid;
        $groupingid = groups_create_grouping($grouping);
        groups_assign_grouping($groupingid, $groupid);

        //set up a user tag
        tag_set('course', $courseid, array('testtag'));

        //add a course-level log
        add_to_log($courseid, 'bogus', 'bogus');

        //set up the default course question category
        $newcategory = question_make_default_categories(array($course_context));

        //create a test question
        $question = new stdClass;
        $question->qtype = 'truefalse';
        $form = new stdClass;
        $form->category = $newcategory->id;
        $form->name = 'testquestion';
        $form->correctanswer = 1;
        $form->feedbacktrue = array('text' => 'bogustext',
                                    'format' => FORMAT_HTML);
        $form->feedbackfalse = array('text' => 'bogustext',
                                     'format' => FORMAT_HTML);
        $question = question_bank::get_qtype('truefalse')->save_question($question, $form);

        if (function_exists('course_set_display')) {
            //set a "course display" setting
            course_set_display($courseid, 1);
        }

        //make a bogus backup record
        $backupcourse = new stdClass;
        $backupcourse->courseid = $courseid;
        $DB->insert_record('backup_courses',$backupcourse);

        //add a user lastaccess record
        $lastaccess = new stdClass;
        $lastaccess->userid = $userid;
        $lastaccess->courseid = $courseid;
        $DB->insert_record('user_lastaccess', $lastaccess);

        //make a bogus backup log record
        $log = new stdClass();
        $log->backupid = $courseid;
        $log->timecreated = time();
        $log->loglevel = 1;
        $log->message = 'bogus';
        $DB->insert_record('backup_logs', $log);

        //get initial counts
        $initial_num_course = $DB->count_records('course');
        $initial_num_role_assignments = $DB->count_records('role_assignments');
        $initial_num_user_enrolments = $DB->count_records('user_enrolments');
        $initial_num_grade_items = $DB->count_records('grade_items');
        $initial_num_grade_grades = $DB->count_records('grade_grades');
        $initial_num_grade_outcomes = $DB->count_records('grade_outcomes');
        $initial_num_grade_outcomes_courses = $DB->count_records('grade_outcomes_courses');
        $initial_num_scale = $DB->count_records('scale');
        $initial_num_grade_settings = $DB->count_records('grade_settings');
        $initial_num_grade_letters = $DB->count_records('grade_letters');
        $initial_num_forum = $DB->count_records('forum');
        $initial_num_course_modules = $DB->count_records('course_modules');
        $initial_num_course_modules_completion = $DB->count_records('course_modules_completion');
        $initial_num_course_modules_availability = $DB->count_records('course_modules_availability');
        $initial_num_block_instances = $DB->count_records('block_instances');
        $initial_num_block_positions = $DB->count_records('block_positions');
        $initial_num_groups = $DB->count_records('groups');
        $initial_num_groups_members = $DB->count_records('groups_members');
        $initial_num_groupings = $DB->count_records('groupings');
        $initial_num_groupings_groups = $DB->count_records('groupings_groups');
        $initial_num_tag_instance = $DB->count_records('tag_instance');
        $initial_num_course_sections = $DB->count_records('course_sections');
        $initial_num_question_categories = $DB->count_records('question_categories');
        $initial_num_question = $DB->count_records('question');
        if (self::$coursedisplay) {
            $initial_num_course_display = $DB->count_records('course_display');
        }
        $initial_num_backup_courses = $DB->count_records('backup_courses');
        $initial_num_user_lastaccess = $DB->count_records('user_lastaccess');
        $initial_num_backup_logs = $DB->count_records('backup_logs');

        //delete the course
        $data = array('action' => 'delete',
                      'shortname' => 'deleteassociationsshortname');
        $this->run_core_course_import($data, false);

        //validate the result
        $this->assertEquals($DB->count_records('course'), $initial_num_course - 1);
        $this->assertEquals($DB->count_records('role_assignments'), $initial_num_role_assignments - 1);
        $this->assertEquals($DB->count_records('user_enrolments'), $initial_num_user_enrolments -  1);
        $this->assertEquals($DB->count_records('grade_items'), $initial_num_grade_items - 2);
        $this->assertEquals($DB->count_records('grade_grades'), $initial_num_grade_grades -  1);
        $this->assertEquals($DB->count_records('grade_outcomes'), $initial_num_grade_outcomes -  1);
        $this->assertEquals($DB->count_records('grade_outcomes_courses'), $initial_num_grade_outcomes_courses -  1);
        $this->assertEquals($DB->count_records('scale'), $initial_num_scale -  1);
        $this->assertEquals($DB->count_records('grade_settings'), $initial_num_grade_settings -  1);
        $this->assertEquals($DB->count_records('grade_letters'), $initial_num_grade_letters -  1);
        $this->assertEquals($DB->count_records('forum'), $initial_num_forum -  1);
        $this->assertEquals($DB->count_records('course_modules'), $initial_num_course_modules -  1);

        //Uncomment the two lines below when this fix is available: http://tracker.moodle.org/browse/MDL-32988
        //$this->assertEquals($DB->count_records('course_modules_completion'), $initial_num_course_modules_completion - 1);
        //$this->assertEquals($DB->count_records('course_modules_availability'), $initial_num_course_modules_availability - 1);
        $this->assertEquals($DB->count_records('block_instances'), $initial_num_block_instances - 1);
        $this->assertEquals($DB->count_records('block_positions'), $initial_num_block_positions - 1);
        $this->assertEquals($DB->count_records('groups'), $initial_num_groups - 1);
        $this->assertEquals($DB->count_records('groups_members'), $initial_num_groups_members - 1);
        $this->assertEquals($DB->count_records('groupings'), $initial_num_groupings - 1);
        $this->assertEquals($DB->count_records('groupings_groups'), $initial_num_groupings_groups - 1);
        $this->assertEquals($DB->count_records('log', array('course' => $courseid)), 0);
        $this->assertEquals($DB->count_records('tag_instance'), $initial_num_tag_instance - 1);
        $this->assertEquals($DB->count_records('course_sections'), $initial_num_course_sections - 1);
        $this->assertEquals($DB->count_records('question_categories'), $initial_num_question_categories - 1);
        $this->assertEquals($DB->count_records('question'), $initial_num_question - 1);
        if (self::$coursedisplay) {
            $this->assertEquals($DB->count_records('course_display'), $initial_num_course_display - 1);
        }
        $this->assertEquals($DB->count_records('backup_courses'), $initial_num_backup_courses - 1);
        $this->assertEquals($DB->count_records('user_lastaccess'), $initial_num_user_lastaccess - 1);
        //$this->assertEquals($DB->count_records('backup_logs'), $initial_num_backup_logs - 1);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on course creation
     */
    public function testVersion1ImportUsesCourseFieldMappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        //setup
        set_config('maxsections', 20, 'moodlecourse');

        //determine the pre-existing category's id
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        //set up our mapping of standard field names to custom field names
        $mapping = array(
            'action'      => 'action1',
            'shortname'   => 'shortname1',
            'fullname'    => 'fullname1',
            'idnumber'    => 'idnumber1',
            'summary'     => 'summary1',
            'format'      => 'format1',
            'startdate'   => 'startdate1',
            'newsitems'   => 'newsitems1',
            'showgrades'  => 'showgrades1',
            'showreports' => 'showreports1',
            'maxbytes'    => 'maxbytes1',
            'guest'       => 'guest1',
            'password'    => 'password1',
            'visible'     => 'visible1',
            'lang'        => 'lang1',
            'category'    => 'category1',
            'numsections' => 'numsections1',
        );

        //store the mapping records in the database
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'course';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        //run the import
        $data = array(
            'entity'       => 'course',
            'action1'      => 'create',
            'shortname1'   => 'fieldmapping',
            'fullname1'    => 'fieldmappingfullname',
            'idnumber1'    => 'fieldmappingidnumber',
            'summary1'     => 'fieldmappingsummary',
            'format1'      => 'social',
            'startdate1'   => 'Jan/01/2012',
            'newsitems1'   => 8,
            'showgrades1'  => 0,
            'showreports1' => 1,
            'maxbytes1'    => 0,
            'guest1'       => 1,
            'password1'    => 'fieldmappingpassword',
            'visible1'     => 0,
            'lang1'        => 'en',
            'category1'    => 'childcategory',
            'numsections1' => 15,
        );
        $this->run_core_course_import($data, false);

        $params = array(
            'shortname'   => 'fieldmapping',
            'fullname'    => 'fieldmappingfullname',
            'idnumber'    => 'fieldmappingidnumber',
            'summary'     => 'fieldmappingsummary',
            'format'      => 'social',
            'numsections' => 15,
            'startdate'   => mktime(0, 0, 0, 1, 1, 2012),
            'newsitems'   => 8,
            'showgrades'  => 0,
            'showreports' => 1,
            'maxbytes'    => 0,
            'visible'     => 0,
            'lang'        => 'en',
            'category'    => $categoryid
        );

        $courseid = $this->assert_core_course_exists($params);

        //validate enrolment record
        $data = array(
            'courseid' => $courseid,
            'enrol'    => 'guest',
            'password' => 'fieldmappingpassword',
            'status'   => ENROL_INSTANCE_ENABLED
        );
        $this->assert_record_exists('enrol', $data);

        // Clean up the mess
        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE, array('entitytype' => 'course'));
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function testVersion1ImportCourseFieldImportPreventsStandardFieldUse() {
        global $CFG, $DB;
        $plugin_dir = get_plugin_directory('rlipimport', 'version1');
        require_once($plugin_dir.'/version1.class.php');
        require_once($plugin_dir.'/lib.php');

        //create the mapping record
        $record = new stdClass;
        $record->entitytype = 'course';
        $record->standardfieldname = 'shortname';
        $record->customfieldname = 'shortname2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        //get the import plugin set up
        $data = array();
        $provider = new rlip_importprovider_mockcourse($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('course');

        //transform a sample record
        $record = new stdClass;
        $record->shortname = 'shortname';
        $record = $importplugin->apply_mapping('course', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        //validate that the field was unset
        $this->assertEquals(isset($record->shortname), false);
    }

    /**
     * Validate that an import can make a course visible
     */
    public function testVersion1ImportMakeCourseVisible() {
        global $DB;

        //create invisible course
        $this->run_core_course_import(array('shortname' => 'visiblecrs', 'visible' => 0));

        //data validation
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'visiblecrs'));
        $this->assertEquals($visible, 0);

        //make course visible in update action
        $data = array('action' => 'update',
                      'shortname' => 'visiblecrs',
                      'visible' => 1);
        $this->run_core_course_import($data);

        //validate that course import updated the visibility
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'visiblecrs'));
        $this->assertEquals($visible, 1);

    }

    /**
     * Validate that an import can make a course invisible
     */
    public function testVersion1ImportMakeCourseInvisible() {
        global $DB;

        //create a course - visible by default
        $this->run_core_course_import(array('shortname' => 'invisiblecrs'));

        //data validation
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'invisiblecrs'));
        $this->assertEquals($visible, 1);

        //make course visible in update action
        $data = array('action' => 'update',
                      'shortname' => 'invisiblecrs',
                      'visible' => 0);
        $this->run_core_course_import($data);

        //validate that course import updated the visibility
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'invisiblecrs'));
        $this->assertEquals($visible, 0);

    }

    /**
     *  Validate that an import uses moodlecourse defaults
     */
    public function testVersion1ImportCourseCreateUsesDefaults() {
        global $DB;

        //backup current course defaults...
        $backup = get_config('moodlecourse');

        //set course defaults
        $defaults = array(
            'format'         => 'weeks',
            'newsitems'      => 5,
            'showgrades'     => 1,
            'showreports'    => 0,
            'maxbytes'       => 5368709120,
            'groupmode'      => 0,
            'groupmodeforce' => 0,
            'visible'        => 1,
            'lang'           => '',
            'numsections'    => 10,
            'hiddensections' => 0,
        );

        foreach($defaults as $default => $value) {
            set_config($default, $value, 'moodlecourse');
        }

        // Create a course - visible by default
        $this->run_core_course_import(array('shortname' => 'crsdefaults'));

        $sql = 'SELECT c.*, cfo1.value as numsections, cfo2.value as hiddensections
                  FROM {course} c
                  LEFT JOIN {course_format_options} cfo1 ON cfo1.courseid=c.id AND cfo1.name = \'numsections\'
                  LEFT JOIN {course_format_options} cfo2 ON cfo2.courseid=c.id AND cfo2.name = \'hiddensections\'
                 WHERE c.shortname = \'crsdefaults\'';

        // Data validation
        $course = $DB->get_record_sql($sql);
        foreach($defaults as $field => $value) {
            $this->assertEquals($course->$field, $value);
            unset_config($field, 'moodlecourse');
        }

        // Reset moodlecourse config values
        foreach($backup as $default => $value) {
            set_config($default, $value, 'moodlecourse');
        }
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function testVersion1ImportSucceedsWithMaxLengthCourseFields() {
        //data for all fixed-size fields at their maximum sizes
        $data = array('fullname' => str_repeat('x', 254),
                      'shortname' => str_repeat('x', 100),
                      'idnumber' => str_repeat('x', 100));
        //run the import, suppressing warning about log contents being too long
        ob_start();
        $this->run_core_course_import($data);
        ob_end_clean();

        //data validation
        $this->assert_record_exists('course', $data);
    }
}
