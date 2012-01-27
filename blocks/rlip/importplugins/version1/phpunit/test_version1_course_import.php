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

global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once(elis::lib('testlib.php'));

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_mockcourse extends rlip_importprovider {
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
        if ($entity != 'course') {
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
 * Overlay database that allows for the handling of temporary tables
 */
class overlay_temp_database extends overlay_database {

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
                    $manager->drop_temp_table($table);
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
        foreach ($this->overlaytables as $tablename => $component) {
            try {
                $this->delete_records($tablename);
            } catch (Exception $e) {
                //temporary table was already dropped
            }
        }
    }
}

/**
 * Class for version 1 course import correctness
 */
class version1CourseImportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        return array('course_categories' => 'moodle',
                     'course' => 'moodle',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'context' => 'moodle',
                     'enrol' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'user' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_grades' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     'grade_letters' => 'moodle',
                     'grade_settings' => 'moodle',
                     'grade_outcomes' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_outcomes_history' => 'moodle',
                     'grade_categories' => 'moodle',
                     'grade_categories_history' => 'moodle',
                     'scale' => 'moodle',
                     'scale_history' => 'moodle',
                     'groups' => 'moodle',
                     'groups_members' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'tag' => 'moodle',
                     'tag_instance' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'question_categories' => 'moodle',
                     'question' => 'moodle',
                     'question_answers' => 'moodle',
                     'question_truefalse' => 'qtype_truefalse',
                     'config' => 'moodle',
                     'course_display' => 'moodle',
                     'backup_log' => 'moodle',
                     'backup_courses' => 'moodle',
                     'block_positions' => 'moodle',
                     'forum' => 'mod_forum',
                     'forum_subscriptions' => 'mod_forum',
                     'forum_read' => 'mod_forum',
                     'feedback_template' => 'mod_feedback',
                     'course_modules' => 'moodle',
                     'course_modules_completion' => 'moodle',
                     'course_modules_availability' => 'moodle',
                     'course_completion_criteria' => 'moodle',
                     'course_completion_aggr_methd' => 'moodle',
                     'course_completions' => 'moodle',
                     'course_completion_crit_compl' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'user_preferences' => 'moodle',
                     'question_hints' => 'moodle',
                     'backup_controllers' => 'moodle',
                     'log' => 'moodle');
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        //use our custom overlay database type that supports temporary tables
        self::$overlaydb = new overlay_temp_database($DB, static::get_overlay_tables(), static::get_ignored_tables());
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('event' => 'moodle');
    }

    /**
     * Helper function to get the core fields for a sample course
     *
     * @return array The course data
     */
    private function get_core_course_data($category) {
        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
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
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        if ($use_default_data) {
            $category = $this->create_test_category();
            $data = $this->get_core_course_data($category);
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
     * Helper function that creates a test category
     *
     * @param string A name to set for the category
     * @return string The name of the created category 
     */
    private function create_test_category($name = 'testcategory') {
        global $DB;

        $category = new stdClass;
        $category->name = $name;
        $category->id = $DB->insert_record('course_categories', $category);

        return $category->name;
    }

    /**
     * Helper function that creates a parent and a child category
     *
     * @param boolean $second_child create a second child category if true
     */
    private function set_up_category_structure($second_child = false) {
        global $DB;

        //parent category
        $parent_category = new stdClass;
        $parent_category->name = 'parentcategory';
        $parent_category->id = $DB->insert_record('course_categories', $parent_category);

        //child category
        $child_category = new stdClass;
        $child_category->name = 'childcategory';
        $child_category->parent = $parent_category->id;
        $child_category->id = $DB->insert_record('course_categories', $child_category);

        if ($second_child) {
            //another child category with the same name
            $child_category = new stdClass;
            $child_category->name = 'childcategory';
            $child_category->parent = $parent_category->id;
            $child_category->id = $DB->insert_record('course_categories', $child_category);
        }
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private function init_contexts_and_site_course() {
        global $DB;

        //set up context records
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context");

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }
    }

    /**
     * Asserts, using PHPunit, that the test course does not exist
     */
    private function assert_core_course_does_not_exist() {
        global $DB;

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname'));
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
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $category = $this->create_test_category();
        $data = $this->get_core_course_data($category);
        $provider = new rlip_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'testcategory'));

        //compare data
        $exists = $DB->record_exists('course', $data);

        $this->assertEquals($exists, true);
    }

    /*
     * Validate that non-required fields are set to specified values during course creation
     */
    public function testVersion1ImportSetsNonRequiredCourseFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();

        $data = array('idnumber' => 'rlipidnumber',
                      'summary' => 'rlipsummary',
                      'format' => 'social',
                      'numsections' => '15',
                      'startdate' => 'Jan/01/2012',
                      'newsitems' => 8,
                      'showgrades' => 0,
                      'showreports' => 1,
                      'maxbytes' => 10240,
                      'guest' => 1,
                      'password' => 'rlippassword',
                      'visible' => 0,
                      'lang' => 'en');

        $this->run_core_course_import($data);

        $select = "shortname = :shortname AND
                   idnumber = :idnumber AND
                   summary = :summary AND
                   format = :format AND
                   numsections = :numsections AND
                   startdate = :startdate AND
                   newsitems = :newsitems AND
                   showgrades = :showgrades AND
                   showreports = :showreports AND 
                   maxbytes = :maxbytes AND ".
                   "visible = :visible AND
                   lang = :lang";
        $params = array('shortname' => 'rlipshortname',
                        'idnumber' => 'rlipidnumber',
                        'summary' => 'rlipsummary',
                        'format' => 'social',
                        'numsections' => '15',
                        'startdate' => mktime(0, 0, 0, 1, 1, 2012),
                        'newsitems' => 8,
                        'showgrades' => 0,
                        'showreports' => 1,
                        'maxbytes' => 10240,
                        'visible' => 0,
                        'lang' => 'en');

        $exists = $DB->record_exists_select('course', $select, $params);

        $this->assertEquals($exists, true);

        $this->assert_record_exists('enrol', array('enrol' => 'guest',
                                                   'password' => 'rlippassword',
                                                   'status' => ENROL_INSTANCE_ENABLED));
    }

    /**
     * Validate that fields are set to specified values during course update
     */
    public function testVersion1ImportSetsFieldsOnCourseUpdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array());

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);
        
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedfullname',
                      'idnumber' => 'rlipidnumber',
                      'summary' => 'rlipsummary',
                      'format' => 'social',
                      'numsections' => 7,
                      'startdate' => 'Jan/12/2012',
                      'newsitems' => 7,
                      'showgrades' => 0,
                      'showreports' => 1,
                      'maxbytes' => 0,
                      'guest' => 1,
                      'password' => 'password',
                      'visible' => 0,
                      'lang' => 'en',
                      'category' => 'newcategory');
        $this->run_core_course_import($data, false);

        unset($data['action']);
        unset($data['guest']);
        unset($data['password']);
        $data['startdate'] = mktime(0, 0, 0, 1, 12, 2012);
        $data['category'] = $new_category->id;

        $select = "shortname = :shortname AND
                   fullname = :fullname AND
                   idnumber = :idnumber AND
                   summary = :summary AND
                   format = :format AND
                   numsections = :numsections AND
                   startdate = :startdate AND
                   newsitems = :newsitems AND
                   showgrades = :showgrades AND 
                   showreports = :showreports AND
                   maxbytes = :maxbytes AND
                   visible = :visible AND
                   lang = :lang AND
                   category = :category";

        $exists = $DB->record_exists_select('course', $select, $data);
        $this->assertEquals($exists, true);

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => 'password',
                                                   'status' => ENROL_INSTANCE_ENABLED));
    }

    /**
     * Validate that invalid format values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseFormatOnCreate() {
        $this->run_core_course_import(array('format' => 'invalid'));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid format values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseFormatOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('format' => 'topics'));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'format' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'format' => 'topics'));
    }

    /**
     * Validate that invalid numsections values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNumsectionsOnCreate() {
        $this->run_core_course_import(array('numsections' => 99999));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid numsections values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseNumsectionsOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('numsections' => 7));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'numsections' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'numsections' => 7));
    }

    /**
     * Validate that invalid startdate values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseStartdateOnCreate() {
        $this->run_core_course_import(array('startdate' => 'invalidstartdate'));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid startdate values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseStartdateOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('startdate' => 'Jan/01/2012'));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'startdate' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'startdate' => mktime(0, 0, 0, 1, 1, 2012)));
    }

    /**
     * Validate that invalid newsitems values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNewsitemsOnCreate() {
        $this->run_core_course_import(array('newsitems' => 99999));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid newsitems values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseNewsitemsOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('newsitems' => 7));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'newsitems' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'newsitems' => 7));
    }

    /**
     * Validate that invalid showgrades values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowgradesOnCreate() {
        $this->run_core_course_import(array('showgrades' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid showgrades values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseShowgradesOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('showgrades' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'showgrades' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'showgrades' => 1));
    }

    /**
     * Validate that invalid showreports values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowreportsOnCreate() {
        $this->run_core_course_import(array('showreports' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid showreports values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseShowreportsOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('showreports' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'showreports' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'showreports' => 1));
    }

    /**
     * Validate that invalid maxbytes values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseMaxbytesOnCreate() {
        $this->run_core_course_import(array('maxbytes' => 25));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid maxbytes values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseMaxbytesOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('maxbytes' => 0));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'maxbytes' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'maxbytes' => 0));
    }

    /**
     * Validate that invalid guest values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseGuestOnCreate() {
        $this->run_core_course_import(array('guest' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid guest values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseGuestOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('allowguestacces' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'guest' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('enrol', array('enrol' => 'guest'));
    }

    /**
     * Validate that invalid visible values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseVisibleOnCreate() {
        $this->run_core_course_import(array('visible' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid visible values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseVisibleOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('visible' => 1));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'visible' => 9999);

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'visible' => 1));
    }

    /**
     * Validate that invalid lang values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseLangOnCreate() {
        $this->run_core_course_import(array('lang' => 'boguslang'));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid lang values can't be set on course update
     */
    public function testVersion1ImportPreventsInvalidCourseLangOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('lang' => 'en'));

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'lang' => 'bogus');

        $this->run_core_course_import($data, false);

        //make sure the data hasn't changed
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'lang' => 'en'));
    }

    /**
     * Validate that the import does not set unsupported fields on course creation
     */
    public function testVersion1ImportPreventsSettingUnsupportedCourseFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $starttime = time();

        $data = array();
        $data['sortorder'] = 999;
        $data['timecreated'] = 25;
        $data['completionnotify'] = 7;
        $this->run_core_course_import($data);

        $select = "timecreated >= :starttime";
        $params = array('starttime' => $starttime);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        //make sure sortorder isn't set to the supplied value
        $exists = $DB->record_exists('course', array('sortorder' => 999));
        $this->assertEquals($exists, false);

        //make sure completionnotify isn't set to the supplied value
        $exists = $DB->record_exists('course', array('completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the import does not set unsupported fields on course update
     */
    public function testVersion1ImportPreventsSettingUnsupportedCourseFieldsOnUpdate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $starttime = time();

        $this->run_core_course_import(array());

        $data = array();
        $data['action'] = 'update';
        $data['shortname'] = 'rlipshortname';
        $data['sortorder'] = 999;
        $data['timecreated'] = 25;
        $data['completionnotify'] = 7;

        $this->run_core_course_import($data, false);

        $select = "timecreated >= :starttime";
        $params = array('starttime' => $starttime);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        //make sure sortorder isn't set to the supplied value
        $exists = $DB->record_exists('course', array('sortorder' => 999));
        $this->assertEquals($exists, false);

        //make sure completionnotify isn't set to the supplied value
        $exists = $DB->record_exists('course', array('completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that field-length checking works correctly on course creation
     */
    public function testVersion1ImportPreventsLongCourseFieldsOnCreate() {
        $this->run_core_course_import(array('fullname' => str_repeat('a', 255)));
        $this->assert_core_course_does_not_exist();

        $this->run_core_course_import(array('shortname' => str_repeat('a', 101)));
        $this->assert_core_course_does_not_exist();

        $this->run_core_course_import(array('idnumber' => str_repeat('a', 101)));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that field-length checking works correctly on course update
     */
    public function testVersion1ImportPreventsLongCourseFieldsOnUpdate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array('idnumber' => 'rlipidnumber'));

        $params = array('action' => 'update',
                        'shortname' => 'rlipshortname',
                        'fullname' => str_repeat('a', 256));
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'fullname' => 'rlipfullname'));

        $params = array('action' => 'update',
                        'shortname' => 'rlipshortname',
                        'idnumber' => str_repeat('a', 101));
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'idnumber' => 'rlipidnumber'));
    }

    /**
     * Validate that the import does not create duplicate course records on creation
     */
    public function testVersion1ImportPreventsDuplicateCourseCreation() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $initial_count = $DB->count_records('course');

        //set up our data
        $this->run_core_course_import(array());
        $count = $DB->count_records('course');
        $this->assertEquals($initial_count + 1, $count);

        //test duplicate username
        $this->run_core_course_import(array());
        $count = $DB->count_records('course');
        $this->assertEquals($initial_count + 1, $count);
    }

    /**
     * Validate that the import can create a course in a category whose name
     * is unique
     */
    public function testVersion1ImportCreatesCourseInUniqueCategory() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category whose name
     * is unique
     */
    public function testVersion1ImportMovesCourseToUniqueCategory() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => 'newcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using a relative category path
     */
    public function testVersion1ImportCreatesCourseInUniqueRelativeCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('parentcategory/childcategory');
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique relative category path
     */
    public function testVersion1ImportMovesCourseToUniqueRelativeCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->parent = $DB->get_field('course_categories', 'id', array('name' => 'parentcategory'));
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => 'parentcategory/newcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using an absolute category path
     */
    public function testVersion1ImportCreatesCourseInUniqueAbsoluteCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('/parentcategory/childcategory');
        $this->run_core_course_import($data);

        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique absolute category path
     */
    public function testVersion1ImportMovesCourseToUniqueAbsoluteCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->parent = $DB->get_field('course_categories', 'id', array('name' => 'parentcategory'));
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => '/parentcategory/newcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the import only creates a course in a non-unique category
     * if the absolute path is specified
     */
    public function testVersion1ImportCreatesCourseOnlyInAbsoluteCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up a parent and child category
        $this->set_up_category_structure();

        //create a grandchild and a great-grandchild with the same parent/child
        //naming structure
        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $second_parent = new stdClass;
        $second_parent->name = 'parentcategory';
        $second_parent->parent = $child_category_id;
        $second_parent->id = $DB->insert_record('course_categories', $second_parent);

        $second_child = new stdClass;
        $second_child->name = 'childcategory';
        $second_child->parent = $second_parent->id;
        $second_child->id = $DB->insert_record('course_categories', $second_child);

        //make sure specifying an ambigious relative path does not create a course 
        $data = $this->get_core_course_data('parentcategory/childcategory');
        $this->run_core_course_import($data);
        $this->assert_core_course_does_not_exist();

        //make sure specifying a non-ambiguous absolute path creates the course
        $data = $this->get_core_course_data('/parentcategory/childcategory');
        $this->run_core_course_import($data);

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $child_category_id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import only moves a course to a non-unique category
     * if the absolute path is specified
     */
    public function testVersion1ImportMovesCourseOnlyToAbsoluteCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up a parent and child category
        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        //create a grandchild and a great-grandchild with the same parent/child
        //naming structure
        $child_category_id = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $second_parent = new stdClass;
        $second_parent->name = 'parentcategory';
        $second_parent->parent = $child_category_id;
        $second_parent->id = $DB->insert_record('course_categories', $second_parent);

        $second_child = new stdClass;
        $second_child->name = 'childcategory';
        $second_child->parent = $second_parent->id;
        $second_child->id = $DB->insert_record('course_categories', $second_child);

        //make sure specifying an ambigious relative path does not move a course
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => 'parentcategory/childcategory'); 
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $second_child->id));
        $this->assertEquals($exists, false);

        //make sure specifying a non-ambiguous absolute path moves the course
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => '/parentcategory/childcategory/parentcategory/childcategory');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $second_child->id));
    }

    /**
     * Validate that the import can create a course in an existing category
     * based on the category's database record id
     */
    public function testVersion1ImportCreatesCourseInCategoryFromRecordId() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up the test category
        $categoryname = $this->create_test_category();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => $categoryname));

        //run the import
        $data = $this->get_core_course_data($categoryid);
        $this->run_core_course_import($data);

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $categoryid));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into an existing category
     * based on the category's database record id
     */
    public function testVersion1ImportMovesCourseIntoCategoryFromRecordId() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->set_up_category_structure();

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $new_category = new stdClass;
        $new_category->name = 'newcategory';
        $new_category->id = $DB->insert_record('course_categories', $new_category);

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'category' => $new_category->id);
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $new_category->id));
    }

    /**
     * Validate that the course import handles escaped slashes in course
     * category names correctly
     */
    public function testVersion1ImportCreatesCourseInCategoryWithSlash() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //create category
        $category = new stdClass;
        $category->name = 'slash/slash';
        $category->id = $DB->insert_record('course_categories', $category);

        //run import
        $data = $this->get_core_course_data('slash\\/slash');
        $this->run_core_course_import($data);

        //make sure the import completed
        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import handles escaped backslashes in course
     * category names correctly
     */
    public function testVersion1ImportCreatesCourseInCategoryWithBackslash() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //create category
        $category = new stdClass;
        $category->name = 'backslash\\backslash';
        $category->id = $DB->insert_record('course_categories', $category);

        //run import
        $data = $this->get_core_course_data('backslash\\\\backslash');
        $this->run_core_course_import($data);

        //make sure the import completed
        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname',
                                                     'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not create a course in a category whose
     * name is not unique
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueCategory() {
        $this->set_up_category_structure(true);

        $data = $this->get_core_course_data('childcategory');
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using a relative category path
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueRelativeCategoryPath() {
        $this->set_up_category_structure(true);

        $data = $this->get_core_course_data('parentcategory/childcategory');
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using an absolute category path
     */
    public function testVersion1ImportPreventsCreatingCourseInNonuniqueAbsoluteCategoryPath() {
        $this->set_up_category_structure(true);

        $data = $this->get_core_course_data('/parentcategory/childcategory');
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that the import does not create a course in a category whose
     * category does not exist
     */
    public function testVersionImportPreventsCreatingCourseInNonexistentCategory() {
        $data = $this->get_core_course_data('nonexistentcategory');
        $this->run_core_course_import($data, false);

        $this->assert_core_course_does_not_exist();
    }

     /**
     * Validate that the import does not create a course in a category whose
     * path does not refer to an existing category using a relative path
     */
    public function testVersionImportPreventsCreatingCourseInNonexistentRelativeCategoryPath() {
        $data = $this->get_core_course_data('nonexistentparentcategory/nonexistentchildcategory');
        $this->run_core_course_import($data, false);

        $this->assert_core_course_does_not_exist();
    }

     /**
     * Validate that the import does not create a course in a category whose
     * path does not refer to an existing category using an absolute path
     */
    public function testVersionImportPreventsCreatingCourseInNonexistentAbsoluteCategoryPath() {
        $data = $this->get_core_course_data('/nonexistentparentcategory/nonexistentchildcategory');
        $this->run_core_course_import($data, false);

        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that category handling prioritizes category names over database
     * ids in the case where a numeric category is supplied
     */
    public function testVersion1ImportPrioritizesCategoryNamesOverIds() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->create_test_category();
        $testcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'testcategory'));

        //create a category whose name is the id of the existing category
        $category = new stdClass;
        $category->name = $testcategoryid;
        $category->id = $DB->insert_record('course_categories', $category);

        $data = $this->get_core_course_data($testcategoryid);
        $this->run_core_course_import($data, false);

        //make sure category was identified by name
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'category' => $category->id));
    }

    /**
     * Validate that updating users does not produce any side-effects
     * in the user data
     */
    public function testVersion1ImportOnlyUpdatesSuppliedCourseFields() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array());

        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedfullname');

        $this->run_core_course_import($data, false);

        $data = $this->get_core_course_data('testcategory');
        unset($data['entity']);
        unset($data['action']);
        $data['fullname'] = 'updatedfullname';
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'testcategory'));
        $data['category'] = $categoryid;

        $this->assert_record_exists('course', $data);
    }

    /**
     * Validate that update actions must match existing courses to do anything
     */
    public function testVersion1ImportDoesNotUpdateNonmatchingCourses() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array());

        $check_data = array('shortname' => 'rlipshortname',
                            'fullname' => 'rlipfullname');

        //bogus shortname
        $data = array('action' => 'update',
                      'shortname' => 'bogusshortname',
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

        //setup
        $this->init_contexts_and_site_course();

        //create course with guest flag and password
        $this->run_core_course_import(array('guest' => 1,
                                            'password' => 'password'));
        //validate plugin configuration
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
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

        //setup
        $this->init_contexts_and_site_course();

        //create course with guest flag disabled and no password
        $this->run_core_course_import(array());
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        //validate plugin configuration
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => NULL,
                                                   'status' => ENROL_INSTANCE_DISABLED));
    }

    /**
     * Validate that the plugin supports updating a course, enabling guest
     * enrolment and setting a password
     */
    public function testVersion1ImportSupportsEnablingGuestEnrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();

        //create course with guest flag disabled and no password
        $this->run_core_course_import(array());
        //validate plugin configuration
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => NULL,
                                                   'status' => ENROL_INSTANCE_DISABLED));

        //update course, enabling plugin and creating a password
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
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

        //setup
        $this->init_contexts_and_site_course();

        //create course with guest flag enabled and password
        $this->run_core_course_import(array('guest' => 1,
                                            'password' => 'password'));

        //validate setup
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'password' => 'password'));

        //update course, disabling guest access
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'guest' => 0);
        $this->run_core_course_import($data, false);

        //validate plugin configuration
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED));
    }

    /**
     * Validate that the version 1 plugin disallows invalid guest enrolment
     * configuration
     */
    public function testVersion1ImportPreventsInvalidGuestEnrolmentConfigurations() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();

        //attempt to create with password but guest plugin disabled
        $this->run_core_course_import(array('guest' => 0,
                                            'password' => 'password'));
        //validate that creation failed
        $this->assert_core_course_does_not_exist();

        //clean up category
        $DB->delete_records('course_categories');

        //create with plugin disabled and no password
        $this->run_core_course_import(array());

        //attempt to update, setting a password with no guest value supplied
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'password' => 'password');
        $this->run_core_course_import($data, false);
        //validate that update failed
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED,
                                                   'password' => NULL));

        //enable guest plugin
        $record = $DB->get_record('enrol', array('courseid' => $courseid,
                                                 'enrol' => 'guest'));
        $record->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $record);

        //attempt to update, setting a password with the guest flag being disabled
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'guest' => 0,
                      'password' => 'password');
        $this->run_core_course_import($data, false);
        //validate that the update failed
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_ENABLED,
                                                   'password' => NULL));
    }

    /**
     * Validate that there are no side-effects of enabling or disabling guest
     * access twice
     */
    public function testVersion1ImportCompletesImportWhenEnablingOrDisablingGuestEnrolmentTwice() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();

        //create course without guest access or password
        $this->run_core_course_import(array());

        //disable guest access in update action
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'guest' => 0);
        $this->run_core_course_import($data);

        //data validation
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid,
                                                              'enrol' => 'guest')), 1);
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_DISABLED,
                                                   'password' => NULL));

        //clean up category
        $DB->delete_records('course_categories');

        //create course with guest access
        $data = array('shortname' => 'rlipshortname2',
                      'guest' => 1);

        //enable guest access in update action
        $this->run_core_course_import($data);
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname2',
                      'guest' => 1);
        $this->run_core_course_import($data);

        //data validation
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname2'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid,
                                                              'enrol' => 'guest')), 1);
        $this->assert_record_exists('enrol', array('courseid' => $courseid,
                                                   'enrol' => 'guest',
                                                   'status' => ENROL_INSTANCE_ENABLED,
                                                   'password' => NULL));
    }

    /**
     * Validate that the plugin prevents configuring a deleted guest enrolment
     * plugin
     */
    public function testVersionImportPreventsConfiguringRemovedGuestPlugin() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run basic import
        $this->run_core_course_import(array());
        //delete plugin from course
        $DB->delete_records('enrol', array('enrol' => 'guest'));

        $expected = array('shortname' => 'rlipshortname',
                          'fullname' => 'rlipfullname');

        //validate for specifying guest value of 0
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedfullname',
                      'guest' => 0);
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        //validate for specifying guest value of 1
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'updatedfullname',
                      'guest' => 1);
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        //validate for specifying a password value
        $data = array('action' => 'update',
                      'shortname' => 'rlipshortname',
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

        //setup
        $this->init_contexts_and_site_course();

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {user}
                      SELECT * FROM
                      {$prefix}user");

        //create a test category
        $category = $this->create_test_category();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => $category));

        //create a test course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'rliptemplateshortname';
        $record->fullname = 'rliptemplatefullname';
        $record->id = $DB->insert_record('course', $record);
        //make sure we have a section to work with
        get_course_section(1, $record->id);

        //create a test forum instance
        $forum = new stdClass;
        $forum->course = $record->id;
        $forum->type = 'news';
        $forum->name = 'rlipforum';
        $forum->intro = 'rlipintro';
        $forum->id = $DB->insert_record('forum', $forum);

        //add it as a course module
        $forum->module = $DB->get_field('modules', 'id', array('name' => 'forum'));
        $forum->instance = $forum->id;
        $forum->section = 1;
        $cmid = add_course_module($forum);

        //run the import
        $data = $this->get_core_course_data($category);
        $data['link'] = 'rliptemplateshortname';
        $this->run_core_course_import($data, false);

        //validate the number of courses
        $this->assertEquals($DB->count_records('course'), 3);

        //validate the course course data, as well as category and sortorder
        $sortorder = $DB->get_field('course', 'sortorder', array('shortname' => 'rliptemplateshortname'));
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
                                                    'fullname' => 'rlipfullname',
                                                    'category' => $categoryid,
                                                    'sortorder' => $sortorder - 1
                                                    ));

        //validate that the category is updated with the correct number of courses
        $this->assert_record_exists('course_categories', array('id' => $categoryid,
                                                               'coursecount' => 2));

        //validate that the correct number of forum instances exist
        $this->assertEquals($DB->count_records('forum'), 2);

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
                  AND f.course = c.id
                JOIN {course_sections} cs
                  ON c.id = cs.course
                  AND cm.section = cs.id
                WHERE m.name = 'forum'
                  AND f.type = 'news'
                  AND f.name = 'rlipforum'
                  AND f.intro = 'rlipintro'
                  AND c.shortname = 'rlipshortname'
                  AND cs.section = 1";

        $exists = $DB->record_exists_sql($sql);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that we can roll over into a different category
     */
    public function testVersion1ImportRolloverSupportsSettingCategory() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {user}
                      SELECT * FROM
                      {$prefix}user");

        //create a test category
        $category = $this->create_test_category();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => $category));

        //create a test course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'rliptemplateshortname';
        $record->fullname = 'rliptemplatefullname';
        $record->id = $DB->insert_record('course', $record);

        //create a second test category
        $category = $this->create_test_category('rlipsecondcategory');
        $secondcategoryid = $DB->get_field('course_categories', 'id', array('name' => $category));

        //run the import
        $data = $this->get_core_course_data($category);
        $data['link'] = 'rliptemplateshortname';
        $this->run_core_course_import($data, false);

        //validate that the courses are each in their respective categories
        $this->assert_record_exists('course', array('shortname' => 'rliptemplateshortname',
                                                    'category' => $categoryid));
        $this->assert_record_exists('course', array('shortname' => 'rlipshortname',
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

        //setup
        $this->init_contexts_and_site_course();

        //create a test category
        $category = $this->create_test_category();
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => $category));

        //create a test user
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->id = user_create_user($user);

        //create a test template course
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'rliptemplateshortname';
        $record->fullname = 'rliptemplatefullname';
        $record = $course = create_course($record);

        //create a test role
        $roleid = create_role('testrole', 'testrole', 'testrole');
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

        //setup
        $this->init_contexts_and_site_course();

        //validate setup
        $this->assertEquals($DB->count_records('course'), 1);

        //run the import
        $this->run_core_course_import(array('link' => 'bogusshortname'));

        //validate that no new course was created
        $this->assertEquals($DB->count_records('course'), 1);
    }

    /**
     * Validate that course create and update actions set time created
     * and time modified appropriately
     */
    public function testVersion1ImportSetsCourseTimestamps() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //record the current time
        $starttime = time();

        //set up base data
        $this->run_core_course_import(array());

        //validate timestamps
        $where = "timecreated >= ? AND
                  timemodified >= ?";
        $params = array($starttime, $starttime);
        $exists = $DB->record_exists_select('course', $where, $params);
        $this->assertEquals($exists, true);

        //update data
        $this->run_core_course_import(array('action' => 'update',
                                            'username' => 'shortname',
                                            'fullname' => 'newfullname'));

        //validate timestamps
        $where = "timecreated >= ? AND
                  timemodified >= ?";
        $params = array($starttime, $starttime);
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

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array());
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));

        $data = array('action' => 'delete',
                      'shortname' => 'rlipshortname');
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array('shortname' => 'rlipshortname'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the version 1 plugin does not delete courses when the
     * specified shortname is incorrect
     */
    public function testVersion1ImportDoesNotDeleteCourseWithInvalidShortname() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_course_import(array());

        $data = array('action' => 'delete',
                      'shortname' => 'bogusshortname');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'rlipshortname'));
    }

    /**
     * Validate that the version 1 plugin deletes appropriate associations when
     * deleting a course
     */
    public function testVersion1ImportDeleteCourseDeletesAssociations() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/backup/lib.php');
        require_once($CFG->dirroot.'/lib/conditionlib.php');

        //setup
        $this->init_contexts_and_site_course();
        $initial_num_contexts = $DB->count_records('context', array('contextlevel' => CONTEXT_COURSE));

        //set up the guest user to prevent enrolment plugins from thinking the
        //created user is the guest user
        if ($record = self::$origdb->get_record('user', array('username' => 'guest',
                                                'mnethostid' => $CFG->mnet_localhost_id))) {
            unset($record->id);
            $DB->insert_record('user', $record);
        }

        //set up the course with one section, including default blocks
        set_config('defaultblocks_topics', 'search_forums');
        $this->run_core_course_import(array('numsections' => 1));

        //create a user record
        $record = new stdClass;
        $record->password = 'Testpass!0';
        $userid = user_create_user($record);

        //create a course-level role
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = create_role('testrole', 'testrole', 'testrole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

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

        //set a "course display" setting
        course_set_display($courseid, 1);

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
        add_to_backup_log(1, $courseid, 'bogus', 'bogus');

        //validate setup
        $this->assertEquals($DB->count_records('course'), 2);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        //course-level and manually added grade items
        $this->assertEquals($DB->count_records('grade_items'), 2);
        $this->assertEquals($DB->count_records('grade_grades'), 1);
        $this->assertEquals($DB->count_records('grade_outcomes'), 1);
        $this->assertEquals($DB->count_records('grade_outcomes_courses'), 1);
        $this->assertEquals($DB->count_records('scale'), 1);
        $this->assertEquals($DB->count_records('grade_settings'), 1);
        $this->assertEquals($DB->count_records('grade_letters'), 1);
        $this->assertEquals($DB->count_records('forum'), 1);
        $this->assertEquals($DB->count_records('course_modules'), 1);
        $this->assertEquals($DB->count_records('course_modules_completion'), 1);
        $this->assertEquals($DB->count_records('course_modules_availability'), 1);
        $this->assertEquals($DB->count_records('block_instances'), 1);
        $this->assertEquals($DB->count_records('block_positions'), 1);
        $this->assertEquals($DB->count_records('groups'), 1);
        $this->assertEquals($DB->count_records('groups_members'), 1);
        $this->assertEquals($DB->count_records('groupings'), 1);
        $this->assertEquals($DB->count_records('groupings_groups'), 1);
        $this->assert_record_exists('log', array('course' => $courseid));
        $this->assertEquals($DB->count_records('tag_instance'), 1);
        $this->assertEquals($DB->count_records('course_sections'), 1);
        $this->assertEquals($DB->count_records('question_categories'), 1);
        $this->assertEquals($DB->count_records('question'), 1);
        $this->assertEquals($DB->count_records('course_display'), 1);
        $this->assertEquals($DB->count_records('backup_courses'), 1);
        $this->assertEquals($DB->count_records('user_lastaccess'), 1);
        $this->assertEquals($DB->count_records('backup_log'), 1);

        //delete the course
        $data = array('action' => 'delete',
                      'shortname' => 'rlipshortname');
        $this->run_core_course_import($data, false);

        //validate the result
        $this->assertEquals($DB->count_records('course'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 0);
        $this->assertEquals($DB->count_records('user_enrolments'), 0);
        $this->assertEquals($DB->count_records('grade_items'), 0);
        $this->assertEquals($DB->count_records('grade_grades'), 0);
        $this->assertEquals($DB->count_records('grade_outcomes'), 0);
        $this->assertEquals($DB->count_records('grade_outcomes_courses'), 0);
        $this->assertEquals($DB->count_records('scale'), 0);
        $this->assertEquals($DB->count_records('grade_settings'), 0);
        $this->assertEquals($DB->count_records('grade_letters'), 0);
        $this->assertEquals($DB->count_records('forum'), 0);
        $this->assertEquals($DB->count_records('course_modules'), 0);
        $this->assertEquals($DB->count_records('course_modules_completion'), 0);
        $this->assertEquals($DB->count_records('course_modules_availability'), 0);
        $this->assertEquals($DB->count_records('block_instances'), 0);
        $this->assertEquals($DB->count_records('block_positions'), 0);
        $this->assertEquals($DB->count_records('groups'), 0);
        $this->assertEquals($DB->count_records('groups_members'), 0);
        $this->assertEquals($DB->count_records('groupings'), 0);
        $this->assertEquals($DB->count_records('groupings_groups'), 0);
        $this->assertEquals($DB->count_records('log', array('course' => $courseid)), 0);
        $this->assertEquals($DB->count_records('tag_instance'), 0);
        $this->assertEquals($DB->count_records('course_sections'), 0);
        $this->assertEquals($DB->count_records('question_categories'), 0);
        $this->assertEquals($DB->count_records('question'), 0);
        $this->assertEquals($DB->count_records('course_display'), 0);
        $this->assertEquals($DB->count_records('backup_courses'), 0);
        $this->assertEquals($DB->count_records('user_lastaccess'), 0);
        $this->assertEquals($DB->count_records('backup_log'), 0);
    }
}