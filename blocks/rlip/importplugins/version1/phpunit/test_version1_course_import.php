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
                     'enrol' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('log' => 'moodle');
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
     * @return string The name of the created category 
     */
    private function create_test_category() {
        global $DB;

        $category = new stdClass;
        $category->name = 'testcategory';
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

        $this->assertEquals($supports, array('create'));
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
        global $DB;

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
                      'allowguestaccess' => 1,
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
                                                   'password' => 'rlippassword'));
    }

    /**
     * Validate that invalid format values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseFormatOnCreate() {
        $this->run_core_course_import(array('format' => 'invalid'));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid numsections values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNumsectionsOnCreate() {
        $this->run_core_course_import(array('numsections' => 99999));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid startdate values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseStartdateOnCreate() {
        $this->run_core_course_import(array('startdate' => 'invalidstartdate'));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid newsitems values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseNewsitemsOnCreate() {
        $this->run_core_course_import(array('newsitems' => 99999));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid showgrades values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowgradesOnCreate() {
        $this->run_core_course_import(array('showgrades' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid showreports values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseShowreportsOnCreate() {
        $this->run_core_course_import(array('showreports' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid maxbytes values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseMaxbytesOnCreate() {
        $this->run_core_course_import(array('maxbytes' => 25));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid allowguestaccess values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseAllowguestaccessOnCreate() {
        $this->run_core_course_import(array('allowguestaccess' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid visible values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseVisibleOnCreate() {
        $this->run_core_course_import(array('visible' => 2));
        $this->assert_core_course_does_not_exist();
    }

    /**
     * Validate that invalid lang values can't be set on course creation
     */
    public function testVersion1ImportPreventsInvalidCourseLangOnCreate() {
        $this->run_core_course_import(array('lang' => 'boguslang'));
        $this->assert_core_course_does_not_exist();
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
     * Validate that the import only creates a course in a non-unique category
     * if the absolute path is specified
     */
    public function testVersion1ImportCreatesCourseOnlyInAbsoluteCategoryPath() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //set up a parent and child category
        $this->set_up_category_structure();

        //create a grandchild and a grand-grandchild with the same parent/child
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

        //todo: implement check for update action
    }
}