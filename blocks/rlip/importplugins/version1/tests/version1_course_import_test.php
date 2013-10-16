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
 * @package    rlipimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');

/**
 * Class for version 1 course import correctness
 * @group block_rlip
 * @group rlipimport_version1
 */
class version1courseimport_testcase extends rlip_test {

    protected static $coursedisplay = false;

    /**
     * Do setup before tests.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        // Create data we need for many test cases.
        self::set_up_category_structure(true);

        set_config('defaultenrol', 1, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_DISABLED, 'enrol_guest');
        set_config('enrol_plugins_enabled', 'manual,guest');

        // New config settings needed for course format refactoring in 2.4.
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

        self::get_csv_files();
        self::get_logfilelocation_files();
        self::get_zip_files();
    }

    /**
     * Helper function to get the core fields for a sample course
     *
     * @return array The course data
     */
    protected function get_core_course_data($category) {
        $data = array(
            'entity' => 'course',
            'action' => 'create',
            'fullname' => 'rlipfullname',
            'category' => $category
        );
        return $data;
    }


    /**
     * Helper function that runs the course import for a sample course
     *
     * @param array $extradata Extra fields to set for the new course
     * @param bool $usedefaultdata Whether to use core course data.
     */
    protected function run_core_course_import($extradata, $usedefaultdata = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_course_data('childcategory');
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Helper function that creates a parent and a child category
     *
     * @param boolean $secondchild create a second child category if true
     */
    protected static function set_up_category_structure($secondchild = false) {
        global $DB;

        // Basic parent and child categories.
        $parentcategory = new stdClass;
        $parentcategory->name = 'parentcategory';
        $parentcategory->id = $DB->insert_record('course_categories', $parentcategory);
        get_context_instance(CONTEXT_COURSECAT, $parentcategory->id);

        // Child category.
        $childcategory = new stdClass;
        $childcategory->name = 'childcategory';
        $childcategory->parent = $parentcategory->id;
        $childcategory->id = $DB->insert_record('course_categories', $childcategory);
        get_context_instance(CONTEXT_COURSECAT, $childcategory->id);

        // Duplicate parent and child in the form parent/child/parent/child.
        $duplicateparent1 = new stdClass;
        $duplicateparent1->name = 'duplicateparentcategory';
        $duplicateparent1->id = $DB->insert_record('course_categories', $duplicateparent1);
        get_context_instance(CONTEXT_COURSECAT, $duplicateparent1->id);

        $duplicatechild1 = new stdClass;
        $duplicatechild1->name = 'duplicatechildcategory';
        $duplicatechild1->parent = $duplicateparent1->id;
        $duplicatechild1->id = $DB->insert_record('course_categories', $duplicatechild1);
        get_context_instance(CONTEXT_COURSECAT, $duplicatechild1->id);

        $duplicateparent2 = new stdClass;
        $duplicateparent2->name = 'duplicateparentcategory';
        $duplicateparent2->parent = $duplicatechild1->id;
        $duplicateparent2->id = $DB->insert_record('course_categories', $duplicateparent2);
        get_context_instance(CONTEXT_COURSECAT, $duplicateparent2->id);

        $duplicatechild2 = new stdClass;
        $duplicatechild2->name = 'duplicatechildcategory';
        $duplicatechild2->parent = $duplicateparent2->id;
        $duplicatechild2->id = $DB->insert_record('course_categories', $duplicatechild2);
        get_context_instance(CONTEXT_COURSECAT, $duplicatechild2->id);

        // Parent category with two child categories, both with the same name.
        $nonuniqueparent = new stdClass;
        $nonuniqueparent->name = 'nonuniqueabsoluteparent';
        $nonuniqueparent->id = $DB->insert_record('course_categories', $nonuniqueparent);
        get_context_instance(CONTEXT_COURSECAT, $nonuniqueparent->id);

        $nonuniquechild1 = new stdClass;
        $nonuniquechild1->name = 'nonuniqueabsolutechild';
        $nonuniquechild1->parent = $nonuniqueparent->id;
        $nonuniquechild1->id = $DB->insert_record('course_categories', $nonuniquechild1);
        get_context_instance(CONTEXT_COURSECAT, $nonuniquechild1->id);

        $nonuniquechild2 = new stdClass;
        $nonuniquechild2->name = 'nonuniqueabsolutechild';
        $nonuniquechild2->parent = $nonuniqueparent->id;
        $nonuniquechild2->id = $DB->insert_record('course_categories', $nonuniquechild2);
        get_context_instance(CONTEXT_COURSECAT, $nonuniquechild2->id);

        build_context_path(true);
    }

    /**
     * Helper function that creates a test category
     *
     * @param string A name to set for the category
     * @param int The id of the parent category, or 0 for top-level
     * @return string The name of the created category
     */
    protected static function create_test_category($name, $parent = 0) {
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
     * Asserts, using PHPunit, that the test course does not exist
     */
    protected function assert_core_course_does_not_exist($shortname) {
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
    protected function assert_core_course_exists($data) {
        global $DB;

        $sql    = 'SELECT c.id';
        $tables = '{course} c';

        $criteria = array();

        foreach ($data as $column => $value) {

            if ($column == 'summary') {
                $criteria[] = 'c.'.$DB->sql_compare_text('summary', 255).' = :summary';
            } else if ($column == 'numsections') {
                // Only the topics and weeks formats have numsections settings.
                if ((array_key_exists('format', $data)) && ($data['format'] == 'topics' || $data['format'] == 'weeks')) {
                    $tables .= ' LEFT JOIN {course_format_options} cfo ON cfo.courseid = c.id AND cfo.name = \'numsections\'';
                    $criteria[] = 'cfo.value = :numsections';
                }
            } else {
                $criteria[] = "c.$column = :$column";
            }
        }

        $sql .= " FROM $tables WHERE ".implode($criteria, ' AND ');

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
    protected function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin supports course actions
     */
    public function test_version1importsupportscourseactions() {
        $supports = plugin_supports('rlipimport', 'version1', 'course');

        $this->assertEquals($supports, array('create', 'update', 'delete'));
    }

    /**
     * Validate that the version 1 plugin supports the course create action
     */
    public function test_version1importsupportscoursecreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_create');
        $requiredfields = array('shortname', 'fullname', 'category');

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports the course update action
     */
    public function test_version1importsupportscourseupdate() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_update');
        $requiredfields = array('shortname');

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that required fields are set to specified values during course creation
     */
    public function test_version1importsetsrequiredcoursefieldsoncreate() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        // Run the import.
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'requiredfields';
        $provider = new rlipimport_version1_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['category'] = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Compare data.
        $exists = $DB->record_exists('course', $data);

        $this->assertEquals($exists, true);
    }

    /*
     * Validate that non-required fields are set to specified values during course creation
     */
    public function test_version1importsetsnonrequiredcoursefieldsoncreate() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Setup.
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

        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 1, 2012);
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
    public function test_version1importsetsfieldsoncourseupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Setup.
        set_config('maxsections', 20, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'updateshortname', 'guest' => 0));

        $newcategory = new stdClass;
        $newcategory->name = 'updatecategory';
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);

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
        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 12, 2012);
        $data['category'] = $newcategory->id;

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
    public function test_version1importsupportslegacystartdateoncoursecreate() {
        global $DB;

        // Create the course.
        $data = array(
            'action'    => 'create',
            'shortname' => 'legacystartdatecreate',
            'fullname'  => 'legacystartdatecreate',
            'category'  => 'childcategory',
            'startdate' => '01/02/2012',
            'guest'     => 1,
        );
        $this->run_core_course_import($data, false);

        // Data validation.
        unset($data['action']);
        unset($data['category']);
        unset($data['guest']);
        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 2, 2012);

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
    public function test_version1importsupportslegacystartdateoncourseupdate() {
        // Create the course.
        $data = array(
            'action' => 'create',
            'shortname' => 'legacystartdateupdate',
            'fullname' => 'legacystartdateupdate',
            'category' => 'childcategory'
        );
        $this->run_core_course_import($data, false);

        // Update the course.
        $data = array(
            'action' => 'update',
            'shortname' => 'legacystartdateupdate',
            'startdate' => '01/02/2012',
            'category' => 'childcategory'
        );
        $this->run_core_course_import($data, false);

        // Data validation.
        $data = array(
            'shortname' => 'legacystartdateupdate',
            'startdate' => rlip_timestamp(0, 0, 0, 1, 2, 2012)
        );

        $this->assert_record_exists('course', $data);
    }

    /**
     * Validate that fields are mapped from 'yes', 'no' values to integer values during course update
     */
    public function test_version1importmapsfieldsoncourseupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Setup.
        set_config('maxsections', 20, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'mapshortname', 'guest' => 'no'));

        $newcategory = new stdClass;
        $newcategory->name = 'mapcategory';
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);

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
        $data['startdate'] = rlip_timestamp(0, 0, 0, 1, 12, 2012);
        $data['category'] = $newcategory->id;

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
    public function test_version1importpreventsinvalidcourseformatoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseformatcreate', 'format' => 'invalid'));
        $this->assert_core_course_does_not_exist('invalidcourseformatcreate');
    }

    /**
     * Validate that invalid format values can't be set on course update
     */
    public function test_version1importpreventsinvalidcourseformatonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseformatupdate', 'format' => 'topics'));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcourseformatupdate',
            'format' => 'bogus'
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseformatupdate', 'format' => 'topics'));
    }

    /**
     * Validate that invalid numsections values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcoursenumsectionsoncreate() {
        set_config('maxsections', 10, 'moodlecourse');
        $this->run_core_course_import(array('shortname' => 'invalidcoursenumsectionscreate', 'numsections' => 99999));
        $this->assert_core_course_does_not_exist('invalidcoursenumsectionscreate');
    }

    /**
     * Validate that invalid numsections values can't be set on course update
     */
    public function test_version1importpreventsinvalidcoursenumsectionsonupdate() {
        set_config('maxsections', 10, 'moodlecourse');
        $this->run_core_course_import(array('shortname' => 'invalidcoursenumsectionscreate', 'numsections' => 7));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcoursenumsectionscreate',
            'numsections' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_core_course_exists(array('shortname' => 'invalidcoursenumsectionscreate', 'numsections' => 7));
    }

    /**
     * Validate that invalid startdate values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcoursestartdateoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursestartdatecreate', 'startdate' => 'invalidstartdate'));
        $this->assert_core_course_does_not_exist('invalidcoursestartdatecreate');
    }

    /**
     * Validate that invalid startdate values can't be set on course update
     */
    public function test_version1importpreventsinvalidcoursestartdateonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursestartdateupdate', 'startdate' => 'Jan/01/2012'));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcoursestartdateupdate',
            'startdate' => 'bogus'
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array(
            'shortname' => 'invalidcoursestartdateupdate',
            'startdate' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        ));
    }

    /**
     * Validate that invalid newsitems values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcoursenewsitemsoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursenewsitemscreate', 'newsitems' => 99999));
        $this->assert_core_course_does_not_exist('invalidcoursenewsitemscreate');
    }

    /**
     * Validate that invalid newsitems values can't be set on course update
     */
    public function test_version1importpreventsinvalidcoursenewsitemsonupdate() {
        // Setup.

        $this->run_core_course_import(array('shortname' => 'invalidcoursenewsitemsupdate', 'newsitems' => 7));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcoursenewsitemsupdate',
            'newsitems' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursenewsitemsupdate', 'newsitems' => 7));
    }

    /**
     * Validate that invalid showgrades values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcourseshowgradesoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowgradescreate', 'showgrades' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseshowgradescreate');
    }

    /**
     * Validate that invalid showgrades values can't be set on course update
     */
    public function test_version1importpreventsinvalidcourseshowgradesonupdate() {
        // Setup.

        $this->run_core_course_import(array('shortname' => 'invalidcourseshowgradesupdate', 'showgrades' => 1));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcourseshowgradesupdate',
            'showgrades' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseshowgradesupdate', 'showgrades' => 1));
    }

    /**
     * Validate that invalid showreports values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcourseshowreportsoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowreportscreate', 'showreports' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseshowreportscreate');
    }

    /**
     * Validate that invalid showreports values can't be set on course update
     */
    public function test_version1importpreventsinvalidcourseshowreportsonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseshowreportsupdate', 'showreports' => 1));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcourseshowreportsupdate',
            'showreports' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcourseshowreportsupdate', 'showreports' => 1));
    }

    /**
     * Validate that invalid maxbytes values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcoursemaxbytesoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursemaxbytescreate', 'maxbytes' => 25));
        $this->assert_core_course_does_not_exist('invalidcoursemaxbytescreate');
    }

    /**
     * Validate that invalid maxbytes values can't be set on course update
     */
    public function test_version1importpreventsinvalidcoursemaxbytesonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursemaxbytesupdate', 'maxbytes' => 0));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcoursemaxbytesupdate',
            'maxbytes' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursemaxbytesupdate', 'maxbytes' => 0));
    }

    /**
     * Validate that invalid guest values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcourseguestoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourseguestcreate', 'guest' => 2));
        $this->assert_core_course_does_not_exist('invalidcourseguestcreate');
    }

    /**
     * Validate that invalid guest values can't be set on course update
     */
    public function test_version1importpreventsinvalidcourseguestonupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $this->run_core_course_import(array('shortname' => 'invalidcourseguestupdate', 'guest' => 1));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcourseguestupdate',
            'guest' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'invalidcourseguestupdate'));
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_ENABLED
        ));
    }

    /**
     * Validate that invalid visible values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcoursevisibleoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursevisiblecreate', 'visible' => 2));
        $this->assert_core_course_does_not_exist('invalidcoursevisiblecreate');
    }

    /**
     * Validate that invalid visible values can't be set on course update
     */
    public function test_version1importpreventsinvalidcoursevisibleonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcoursevisibleupdate', 'visible' => 1));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcoursevisibleupdate',
            'visible' => 9999
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcoursevisibleupdate', 'visible' => 1));
    }

    /**
     * Validate that invalid lang values can't be set on course creation
     */
    public function test_version1importpreventsinvalidcourselangoncreate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourselangcreate', 'lang' => 'boguslang'));
        $this->assert_core_course_does_not_exist('invalidcourselangcreate');
    }

    /**
     * Validate that invalid lang values can't be set on course update
     */
    public function test_version1importpreventsinvalidcourselangonupdate() {
        $this->run_core_course_import(array('shortname' => 'invalidcourselangupdate', 'lang' => 'en'));

        $data = array(
            'action' => 'update',
            'shortname' => 'invalidcourselangupdate',
            'lang' => 'bogus'
        );

        $this->run_core_course_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('course', array('shortname' => 'invalidcourselangupdate', 'lang' => 'en'));
    }

    /**
     * Validate that the import does not set unsupported fields on course creation
     */
    public function test_version1importpreventssettingunsupportedcoursefieldsoncreate() {
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
        $params = array('shortname' => 'unsupportedcoursefieldscreate', 'starttime' => $starttime);

        // Make sure that a record exists with the default data rather than with the specified values.
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        // Make sure sortorder isn't set to the supplied value.
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldscreate', 'sortorder' => 999));
        $this->assertEquals($exists, false);

        // Make sure completionnotify isn't set to the supplied value.
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldscreate', 'completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the import does not set unsupported fields on course update
     */
    public function test_version1importpreventssettingunsupportedcoursefieldsonupdate() {
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

        // Make sure that a record exists with the default data rather than with the specified values.
        $exists = $DB->record_exists_select('course', $select, $params);
        $this->assertEquals($exists, true);

        // Make sure sortorder isn't set to the supplied value.
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldsupdate', 'sortorder' => 999));
        $this->assertEquals($exists, false);

        // Make sure completionnotify isn't set to the supplied value.
        $exists = $DB->record_exists('course', array('shortname' => 'unsupportedcoursefieldsupdate', 'completionnotify' => 7));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that field-length checking works correctly on course creation
     */
    public function test_version1importpreventslongcoursefieldsoncreate() {
        $this->run_core_course_import(array('shortname' => 'coursefullnametoolongcreate', 'fullname' => str_repeat('a', 255)));
        $this->assert_core_course_does_not_exist('coursefullnametoolongcreate');

        $shortname = str_repeat('a', 101);
        $this->run_core_course_import(array('shortname' => $shortname));
        $this->assert_core_course_does_not_exist($shortname);

        $this->run_core_course_import(array('shortname' => 'courseidnumbertoolongcreate', 'idnumber' => str_repeat('a', 101)));
        $this->assert_core_course_does_not_exist('courseidnumbertoolongcreate');
    }

    /**
     * Validate that field-length checking works correctly on course update
     */
    public function test_version1importpreventslongcoursefieldsonupdate() {
        $this->run_core_course_import(array(
            'shortname' => 'coursefullnametoolongupdate',
            'fullname' => 'coursefullnametoolongupdatefullname',
            'idnumber' => 'coursefullnametoolongupdateidnumber'
        ));

        $params = array(
            'action' => 'update',
            'shortname' => 'coursefullnametoolongupdate',
            'fullname' => str_repeat('a', 256)
        );
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array(
            'shortname' => 'coursefullnametoolongupdate',
            'fullname' => 'coursefullnametoolongupdatefullname'
        ));

        $params = array(
            'action' => 'update',
            'shortname' => 'coursefullnametoolongupdate',
            'idnumber' => str_repeat('a', 101)
        );
        $this->run_core_course_import($params, false);
        $this->assert_record_exists('course', array(
            'shortname' => 'coursefullnametoolongupdate',
            'idnumber' => 'coursefullnametoolongupdateidnumber'
        ));
    }

    /**
     * Validate that the import does not create duplicate course records on creation
     */
    public function test_version1importpreventsduplicatecoursecreation() {
        global $DB;

        $initialcount = $DB->count_records('course');

        // Set up our data.
        $this->run_core_course_import(array('shortname' => 'preventduplicatecourses'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 1, $count);

        // Test duplicate username.
        $this->run_core_course_import(array('shortname' => 'preventduplicatecourses'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 1, $count);
    }

    /**
     * Validate that the import does not create courses with duplicate id numbers on creation.
     * @uses $DB
     */
    public function test_version1importpreventsduplicatecourseidnumberoncreation() {
        global $DB;

        $initialcount = $DB->count_records('course');

        // Set up our data.
        $this->run_core_course_import(array('shortname' => 'shortname1', 'idnumber' => 'preventduplicateidnumber'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 1, $count);

        // Test duplicate idnumber.
        $this->run_core_course_import(array('shortname' => 'shortname2', 'idnumber' => 'preventduplicateidnumber'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 1, $count);
    }

    /**
     * Validate that the import does not create courses with duplicate id numbers on update.
     * @uses $DB
     */
    public function test_version1importpreventsduplicatecourseidnumberonupdate() {
        global $DB;

        $initialcount = $DB->count_records('course');

        // Set up our data.
        $this->run_core_course_import(array('shortname' => 'shortname1', 'idnumber' => 'preventduplicateidnumber1'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 1, $count);

        $this->run_core_course_import(array('shortname' => 'shortname2', 'idnumber' => 'preventduplicateidnumber2'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 2, $count);

        // Test duplicate idnumber on update.
        $this->run_core_course_import(array('action' => 'update', 'shortname' => 'shortname2', 'idnumber' => 'preventduplicateidnumber1'));
        $count = $DB->count_records('course');
        $this->assertEquals($initialcount + 2, $count);
        $count = $DB->count_records('course', array('idnumber' => 'preventduplicateidnumber1'));
        $this->assertEquals(1, $count);
    }

    /**
     * Validate that the import can create a course in a category whose name
     * is unique
     */
    public function test_version1importcreatescourseinuniquecategory() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'uniquecategorycreate';
        $this->run_core_course_import($data);

        $childcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniquecategorycreate', 'category' => $childcategoryid));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category whose name
     * is unique
     */
    public function test_version1importmovescoursetouniquecategory() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'uniquecategoryupdate';
        $this->run_core_course_import($data);

        $newcategory = new stdClass;
        $newcategory->name = 'newcategory';
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);

        $data = array(
            'action' => 'update',
            'shortname' => 'uniquecategoryupdate',
            'category' => 'newcategory'
        );
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'uniquecategoryupdate', 'category' => $newcategory->id));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using a relative category path
     */
    public function test_version1importcreatescourseinuniquerelativecategorypath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory/childcategory');
        $data['shortname'] = 'uniquerelativepathcreate';
        $this->run_core_course_import($data);

        $childcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniquerelativepathcreate', 'category' => $childcategoryid));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique relative category path
     */
    public function test_version1importmovescoursetouniquerelativecategorypath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory');
        $data['shortname'] = 'uniquerelativepathupdate';
        $this->run_core_course_import($data);

        $data = array(
            'action' => 'update',
            'shortname' => 'uniquerelativepathupdate',
            'category' => 'parentcategory/childcategory'
        );
        $this->run_core_course_import($data, false);

        $childcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $this->assert_record_exists('course', array('shortname' => 'uniquerelativepathupdate', 'category' => $childcategoryid));
    }

    /**
     * Validate that the import can create a course in a category whose path
     * is unique using an absolute category path
     */
    public function test_version1importcreatescourseinuniqueabsolutecategorypath() {
        global $DB;

        $data = $this->get_core_course_data('/parentcategory/childcategory');
        $data['shortname'] = 'uniqueabsoluatepathcreate';
        $this->run_core_course_import($data);

        $childcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $exists = $DB->record_exists('course', array('shortname' => 'uniqueabsoluatepathcreate', 'category' => $childcategoryid));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into a category via
     * specifying a unique absolute category path
     */
    public function test_version1importmovescoursetouniqueabsolutecategorypath() {
        global $DB;

        $data = $this->get_core_course_data('parentcategory');
        $data['shortname'] = 'uniqueabsoluatepathupdate';
        $this->run_core_course_import($data);

        $data = array(
            'action' => 'update',
            'shortname' => 'uniqueabsoluatepathupdate',
            'category' => '/parentcategory/childcategory'
        );
        $this->run_core_course_import($data, false);

        $childcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $this->assert_record_exists('course', array('shortname' => 'uniqueabsoluatepathupdate', 'category' => $childcategoryid));
    }

    /**
     * Validate that the import only creates a course in a non-unique category
     * if the absolute path is specified
     */
    public function test_version1importcreatescourseonlyinabsolutecategorypath() {
        global $DB;

        // Make sure specifying an ambigious relative path does not create a course.
        $data = $this->get_core_course_data('duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'ambiguousrelativepathcreate';
        $this->run_core_course_import($data);
        $this->assert_core_course_does_not_exist('ambiguousrelativepathcreate');

        // Make sure specifying a non-ambiguous absolute path creates the course.
        $data = $this->get_core_course_data('/duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'ambiguousrelativepathcreate';
        $this->run_core_course_import($data);

        $sql = "SELECT child.*
                  FROM {course_categories} child
                  JOIN {course_categories} parent ON child.parent = parent.id
                 WHERE child.name = ? AND parent.parent = 0";
        $childcategory = $DB->get_record_sql($sql, array('duplicatechildcategory'));
        $exists = $DB->record_exists('course', array(
            'shortname' => 'ambiguousrelativepathcreate',
            'category' => $childcategory->id
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import only moves a course to a non-unique category
     * if the absolute path is specified
     */
    public function test_version1importmovescourseonlytoabsolutecategorypath() {
        global $DB;

        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'absolutecategorypathupdate';
        $this->run_core_course_import($data);

        $sql = "SELECT *
                  FROM {course_categories} parent
                 WHERE name = ? AND NOT EXISTS (
                       SELECT *
                         FROM {course_categories} child
                        WHERE parent.id = child.parent
                       )";
        $duplicatechildcategory = $DB->get_record_sql($sql, array('duplicatechildcategory'));

        // Make sure specifying an ambigious relative path does not move a course.
        $data = array(
            'action' => 'update',
            'shortname' => 'absolutecategorypathupdate',
            'category' => 'duplicateparentcategory/duplicatechildcategory'
        );
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array(
            'shortname' => 'absolutecategorypathupdate',
            'category' => $duplicatechildcategory->id
        ));
        $this->assertEquals($exists, false);

        // Make sure specifying a non-ambiguous absolute path moves the course.
        $data = array(
            'action' => 'update',
            'shortname' => 'absolutecategorypathupdate',
            'category' => 'duplicateparentcategory/duplicatechildcategory/duplicateparentcategory/duplicatechildcategory'
        );
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array(
            'shortname' => 'absolutecategorypathupdate',
            'category' => $duplicatechildcategory->id
        ));
    }

    /**
     * Validate that the import can create a course in an existing category
     * based on the category's database record id
     */
    public function test_version1importcreatescourseincategoryfromrecordid() {
        global $DB;

        // Setup.
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $newcategory = new stdClass;
        $newcategory->name = $categoryid;
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);
        get_context_instance(CONTEXT_COURSECAT, $newcategory->id);
        build_context_path(true);

        // Run the import.
        $data = $this->get_core_course_data($categoryid);
        $data['shortname'] = 'categoryidcreate';
        $data['category'] = $categoryid;
        $this->run_core_course_import($data);
        $DB->delete_records('course_categories', array('id' => $newcategory->id));

        $exists = $DB->record_exists('course', array('shortname' => 'categoryidcreate', 'category' => $newcategory->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import can move a course into an existing category
     * based on the category's database record id
     */
    public function test_version1importmovescourseintocategoryfromrecordid() {
        global $DB;

        // Setup.
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'categoryidupdate';
        $this->run_core_course_import($data);

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        $newcategory = new stdClass;
        $newcategory->name =  $categoryid;
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);
        get_context_instance(CONTEXT_COURSECAT, $newcategory->id);
        build_context_path(true);

        $data = array(
            'action' => 'update',
            'shortname' => 'categoryidupdate',
            'category' => $newcategory->id
        );
        $this->run_core_course_import($data, false);
        $DB->delete_records('course_categories', array('id' => $newcategory->id));

        $this->assert_record_exists('course', array('shortname' => 'categoryidupdate', 'category' => $newcategory->id));
    }

    /**
     * Validate that the course import handles escaped slashes in course
     * category names correctly
     */
    public function test_version1importcreatescourseincategorywithslash() {
        global $DB;

        // Create category.
        $category = new stdClass;
        $category->name = 'slash/slash';
        $category->id = $DB->insert_record('course_categories', $category);

        // Run import.
        $data = $this->get_core_course_data('slash\\/slash');
        $data['shortname'] = 'categoryslashcreate';
        $this->run_core_course_import($data);

        // Make sure the import completed.
        $exists = $DB->record_exists('course', array('shortname' => 'categoryslashcreate', 'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import handles escaped backslashes in course
     * category names correctly
     */
    public function test_version1importcreatescourseincategorywithbackslash() {
        global $DB;

        // Create category.
        $category = new stdClass;
        $category->name = 'backslash\\backslash';
        $category->id = $DB->insert_record('course_categories', $category);

        // Run import.
        $data = $this->get_core_course_data('backslash\\\\backslash');
        $data['shortname'] = 'categorybackslashcreate';
        $this->run_core_course_import($data);

        // Make sure the import completed.
        $exists = $DB->record_exists('course', array('shortname' => 'categorybackslashcreate', 'category' => $category->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not create a course in a category whose
     * name is not unique
     */
    public function test_version1importpreventscreatingcourseinnonuniquecategory() {
        $data = $this->get_core_course_data('duplicatechildcategory');
        $data['shortname'] = 'createinnonuniquecategory1';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory1');
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using a relative category path
     */
    public function test_version1importpreventscreatingcourseinnonuniquerelativecategorypath() {
        $data = $this->get_core_course_data('duplicateparentcategory/duplicatechildcategory');
        $data['shortname'] = 'createinnonuniquecategory2';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory2');
    }

    /**
     * Validate that the import does not create a course in a category whose
     * path is not unique using an absolute category path
     */
    public function test_version1importpreventscreatingcourseinnonuniqueabsolutecategorypath() {
        $data = $this->get_core_course_data('/nonuniqueabsoluteparent/nonuniqueabsolutechild');
        $data['shortname'] = 'createinnonuniquecategory3';
        $this->run_core_course_import($data);

        $this->assert_core_course_does_not_exist('createinnonuniquecategory3');
    }

    /**
     * Validate that category handling prioritizes category names over database
     * ids in the case where a numeric category is supplied
     */
    public function test_version1importprioritizescategorynamesoverids() {
        global $DB;

        $testcategoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Create a category whose name is the id of the existing category.
        $category = new stdClass;
        $category->name = $testcategoryid;
        $category->id = $DB->insert_record('course_categories', $category);

        $data = $this->get_core_course_data($testcategoryid);
        $data['shortname'] = 'prioritizecategoryname';
        $data['category'] = $testcategoryid;
        $this->run_core_course_import($data, false);
        $DB->delete_records('course_categories', array('id' => $category->id));

        // Make sure category was identified by name.
        $this->assert_record_exists('course', array('shortname' => 'prioritizecategoryname', 'category' => $category->id));
    }

    /**
     * Validate that the course import creates a single top-level category and
     * assigns a new course to it
     */
    public function test_version1importcoursecreatecreatescategoryfromname() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('createcategorycreate');
        $data['shortname'] = 'createcategorycreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 1);

        // Validate course and category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createcategorycreate', 'createcategorycreate', 0, 1));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a single top-level category and
     * assigns an existing course to it
     */
    public function test_version1importcourseupdatecreatescategoryfromname() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'createcategoryupdate'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'createcategoryupdate',
            'category' => 'createcategoryupdate'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 1);

        // Validate course and category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array('createcategoryupdate', 'createcategoryupdate', 0, 1));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns a new course to the child category using a "relative" specification
     */
    public function test_version1importcoursecreatecreatescategoriesfromrelativepathwithnonexistentprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('createrelativenonexistentparentcreate/createrelativenonexistentchildcreate');
        $data['shortname'] = 'createrelativenonexistentcreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 2);

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'createrelativenonexistentparentcreate',
            'parent' => 0,
            'depth' => 1
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createrelativenonexistentparentcreate'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createrelativenonexistentcreate',
            'createrelativenonexistentchildcreate',
            $parentid,
            2
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns an existing course to the child category using a "relative"
     * specification
     */
    public function test_version1importcourseupdatecreatescategoriesfromrelativepathwithnonexistentprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'createrelativenonexistentupdate'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'createrelativenonexistentupdate',
            'category' => 'createrelativenonexistentparentupdate/createrelativenonexistentchildupdate'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 2);

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'createrelativenonexistentparentupdate',
            'parent' => 0,
            'depth' => 1
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createrelativenonexistentparentupdate'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createrelativenonexistentupdate',
            'createrelativenonexistentchildupdate',
            $parentid,
            2
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns a
     * new course to the child category using a "relative" specification
     */
    public function test_version1importcoursecreatecreatescategoryfromrelativepathwithexistingprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('parentcategory/childcategory/createrelativeexistentcreatechild');
        $data['shortname'] = 'createrelativeexistentcreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createrelativeexistentcreate',
            'createrelativeexistentcreatechild',
            $childid,
            3
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns an
     * existing course to the child category using a "relative" specification
     */
    public function test_version1importcourseupdatecreatescategoryfromrelativepathwithexistingprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'createrelativeexistentupdate'));

        // Create grandparent category.
        $this->create_test_category('testgrandparentcategory');
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'testgrandparentcategory'));

        // Create parent category.
        $this->create_test_category('testparentcategory', $grandparentid);

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'createrelativeexistentupdate',
            'category' => 'testgrandparentcategory/testparentcategory/createrelativeexistentupdatechild'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 3);

        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'testparentcategory'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createrelativeexistentupdate',
            'createrelativeexistentupdatechild',
            $parentid,
            3
        ));
        $this->assertEquals($exists, true);
    }

     /**
      * Validate that the course import creates a parent and child category and
      * assigns a new course to the child category using an "absolute" specification
      */
    public function test_version1importcoursecreatecreatescategoriesfromabsolutepathwithnonexistentprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('/createabsolutenonexistentcreateparent/createabsolutenonexistentcreatechild');
        $data['shortname'] = 'createabsolutenonexistentcreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 2);

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'createabsolutenonexistentcreateparent',
            'parent' => 0,
            'depth' => 1
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createabsolutenonexistentcreateparent'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createabsolutenonexistentcreate',
            'createabsolutenonexistentcreatechild',
            $parentid,
            2
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a parent and child category and
     * assigns an existing course to the child category using an "absolute"
     * specification
     */
    public function test_version1importcourseupdatecreatescategoriesfromabsolutepathwithnonexistentprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourses = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'createabsolutenonexistentupdate'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'createabsolutenonexistentupdate',
            'category' => '/createabsolutenonexistentupdateparent/createabsolutenonexistentupdatechild'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourses + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 2);

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'createabsolutenonexistentupdateparent',
            'parent' => 0,
            'depth' => 1
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'createabsolutenonexistentupdateparent'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createabsolutenonexistentupdate',
            'createabsolutenonexistentupdatechild',
            $parentid,
            2
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns a
     * new course to the child category using an "absolute" specification
     */
    public function test_version1importcoursecreatecreatescategoryfromabsolutepathwithexistingprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('/parentcategory/childcategory/createabsoluteexistentcreatechild');
        $data['shortname'] = 'createabsoluteexistentcreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createabsoluteexistentcreate',
            'createabsoluteexistentcreatechild',
            $childid,
            3
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a child category and assigns an
     * existing course to the child category using an "absolute" specification
     */
    public function test_version1importcourseupdatecreatescategoryfromabsolutepathwithexistingprefix() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'createabsoluteexistentupdate'));

        // Get parent category.
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'createabsoluteexistentupdate',
            'category' => '/parentcategory/childcategory/createabsoluteexistentupdatechild'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 1);

        $childid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'createabsoluteexistentupdate',
            'createabsoluteexistentupdatechild',
            $childid,
            3
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a grandparent, parent and child
     * category and assigns a new course to the child category
     */
    public function test_version1importcoursecreatecreatescategorypath() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = '/coursecreatecreatespathgrandparent/coursecreatecreatespathparent/coursecreatecreatespathchild';
        $data = $this->get_core_course_data($data);
        $data['shortname'] = 'coursecreatecreatespath';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 3);

        // Validate grandparent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'coursecreatecreatespathgrandparent',
            'parent' => 0,
            'depth' => 1
        ));
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'coursecreatecreatespathgrandparent'));

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'coursecreatecreatespathparent',
            'parent' => $grandparentid,
            'depth' => 2
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'coursecreatecreatespathparent'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'coursecreatecreatespath',
            'coursecreatecreatespathchild',
            $parentid,
            3
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import creates a grandparent, parent and child
     * category and assigns an existing course to the child category
     */
    public function test_version1importcourseupdatecreatescategorypath() {
        global $DB;

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course and category.
        $this->run_core_course_import(array('shortname' => 'courseupdatecreatespath'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'courseupdatecreatespath',
            'category' => '/courseupdatecreatespathgrandparent/courseupdatecreatespathparent/courseupdatecreatespathchild'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories + 3);

        // Validate grandparent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'courseupdatecreatespathgrandparent',
            'parent' => 0,
            'depth' => 1
        ));
        $grandparentid = $DB->get_field('course_categories', 'id', array('name' => 'courseupdatecreatespathgrandparent'));

        // Validate parent category.
        $this->assert_record_exists('course_categories', array(
            'name' => 'courseupdatecreatespathparent',
            'parent' => $grandparentid,
            'depth' => 2
        ));
        $parentid = $DB->get_field('course_categories', 'id', array('name' => 'courseupdatecreatespathparent'));

        // Validate course and child category assignment.
        $sql = "SELECT *
                  FROM {course} c
                  JOIN {course_categories} cc ON c.category = cc.id
                 WHERE c.shortname = ? AND cc.name = ? AND cc.parent = ? AND cc.depth = ?";
        $exists = $DB->record_exists_sql($sql, array(
            'courseupdatecreatespath',
            'courseupdatecreatespathchild',
            $parentid,
            3
        ));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the course import only allow for category creation when
     * the specified path is non-ambiguous (course create)
     */
    public function test_version1importcoursecreatepreventscreatingcategorywithambiguousparentpath() {
        global $DB;

        // Get initial counts.
        $initialnumcourses = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import.
        $data = $this->get_core_course_data('/nonuniqueabsoluteparent/nonuniqueabsolutechild/ambiguousparentcreatecategory');
        $data['shortname'] = 'ambiguousparentcreate';
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourses);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories);
    }

    /**
     * Validate that the course import only allow for category creation when
     * the specified path is non-ambiguous (course update)
     */
    public function test_version1importcourseupdatepreventscreatingcategorywithambiguousparentpath() {
        global $DB;

        // Get initial counts.
        $initialnumcourses = $DB->count_records('course');
        $initialnumcategories = $DB->count_records('course_categories');

        // Run import to create initial course.
        $this->run_core_course_import(array('shortname' => 'ambiguousparentupdate'));

        // Run import to move course to new category.
        $data = array(
            'action' => 'update',
            'shortname' => 'ambiguousparentupdate',
            'category' => '/nonuniqueabsoluteparent/nonuniqueabsolutechild/ambiguousparentupdatecategory'
        );
        $this->run_core_course_import($data, false);

        // Validate counts.
        $this->assertEquals($DB->count_records('course'), $initialnumcourses + 1);
        $this->assertEquals($DB->count_records('course_categories'), $initialnumcategories);
    }

    /**
     * Validate that updating users does not produce any side-effects
     * in the user data
     */
    public function test_version1importonlyupdatessuppliedcoursefields() {
        global $DB;

        $this->run_core_course_import(array('shortname' => 'updatescoursefields'));

        $data = array(
            'action' => 'update',
            'shortname' => 'updatescoursefields',
            'fullname' => 'updatedfullname'
        );

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
    public function test_version1importdoesnotupdatenonmatchingcourses() {
        $this->run_core_course_import(array('shortname' => 'updatenonmatching', 'fullname' => 'fullname'));

        $checkdata = array('shortname' => 'updatenonmatching', 'fullname' => 'fullname');

        // Bogus shortname.
        $data = array('action' => 'update', 'shortname' => 'bogus', 'fullname' => 'newfullname');
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $checkdata);
    }

    /**
     * Validate that the plugin supports creating a course with guest enrolment
     * enabled
     */
    public function test_version1importsupportscreatingwithguestenrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Create course with guest flag and password.
        $this->run_core_course_import(array(
            'shortname' => 'createwithguest',
            'guest' => 1,
            'password' => 'password'
        ));
        // Validate plugin configuration.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'createwithguest'));
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => 'password',
            'status' => ENROL_INSTANCE_ENABLED
        ));
    }

    /**
     * Validate that the plugin supports creating a course with guest enrolment
     * disabled
     */
    public function test_version1importsupportscreatingwithoutguestenrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Create course with guest flag disabled and no password.
        $this->run_core_course_import(array('shortname' => 'createwithoutguest', 'guest' => 0));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'createwithoutguest'));
        // Validate plugin configuration.
        // TODO: change password back to NULL if the guest plugin starts using it as the default again.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => '',
            'status' => ENROL_INSTANCE_DISABLED
        ));
    }

    /**
     * Validate that the plugin supports updating a course, enabling guest
     * enrolment and setting a password
     */
    public function test_version1importsupportsenablingguestenrolment() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Create course with guest flag disabled and no password.
        $this->run_core_course_import(array('shortname' => 'enableguestenrolment',
                                            'guest' => 0));
        // Validate plugin configuration.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'enableguestenrolment'));
        // TODO: change password back to NULL if the guest plugin starts using it as the default again.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => '',
            'status' => ENROL_INSTANCE_DISABLED
        ));

        // Update course, enabling plugin and creating a password.
        $data = array(
            'action' => 'update',
            'shortname' => 'enableguestenrolment',
            'guest' => 1,
            'password' => 'password'
        );
        $this->run_core_course_import($data, false);
        // Validate plugin configuration.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => 'password',
            'status' => ENROL_INSTANCE_ENABLED
        ));
    }

    /**
     * Validate that the plugin supports updating a course, disabling guest
     * enrolment
     */
    public function test_version1importsupportsdisablingguestenrolment () {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Create course with guest flag enabled and password.
        $this->run_core_course_import(array(
            'shortname' => 'disableguestenrolment',
            'guest' => 1,
            'password' => 'password'
        ));

        // Validate setup.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'disableguestenrolment'));
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'password' => 'password',
            'status' => ENROL_INSTANCE_ENABLED
        ));

        // Update course, disabling guest access.
        $data = array('action' => 'update',
                      'shortname' => 'disableguestenrolment',
                      'guest' => 0);
        $this->run_core_course_import($data, false);

        // Validate plugin configuration.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_DISABLED
        ));
    }

    public function test_version1importpreventsinvalidguestenrolmentconfigurationsoncreate() {
        // Validate that passwords require enrolments to be enabled.
        set_config('defaultenrol', 0, 'enrol_guest');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate', 'password' => 'asdf'));
        set_config('defaultenrol', 1, 'enrol_guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        $this->run_core_course_import(array(
            'shortname' => 'invalidguestconfigurationcreate',
            'guest' => 0,
            'password' => 'asdf'
        ));
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        // Validate that creation with guest access fails when the guest plugin is globally disabled.
        set_config('enrol_plugins_enabled', 'manual');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate', 'guest' => 1));
        set_config('enrol_plugins_enabled', 'manual,guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');

        // Validate that creation with guest access fails when not adding the guest plugin to courses.
        set_config('defaultenrol', 0, 'enrol_guest');
        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationcreate', 'guest' => 1));
        set_config('defaultenrol', 1, 'enrol_guest');
        $this->assert_core_course_does_not_exist('invalidguestconfigurationcreate');
    }

    public function test_version1importpreventsinvalidguestenrolmentconfigurationsonupdate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $this->run_core_course_import(array('shortname' => 'invalidguestconfigurationupdate', 'guest' => 0));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'invalidguestconfigurationupdate'));

        // Validate that passwords require enrolments to be enabled.
        $this->run_core_course_import(array(
            'action' => 'update',
            'shortname' => 'invalidguestconfigurationupdate',
            'password' => 'asdf'
        ));
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_DISABLED
        ));

        $this->run_core_course_import(array(
            'action' => 'update',
            'shortname' => 'invalidguestconfigurationupdate',
            'guest' => 0,
            'password' => 'asdf'
        ));
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_DISABLED
        ));

        // Validate that creation with guest access fails when the guest plugin is globally disabled.
        set_config('enrol_plugins_enabled', 'manual');
        $this->run_core_course_import(array(
            'action' => 'update',
            'shortname' => 'invalidguestconfigurationupdate',
            'guest' => 1
        ));
        $exists = $DB->record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_DISABLED
        ));
        set_config('enrol_plugins_enabled', 'manual,guest');
        $this->assertEquals($exists, true);

        // Validate that creation with guest access fails when not adding the guest plugin to courses.
        $DB->delete_records('enrol', array('courseid' => $courseid));
        $this->run_core_course_import(array(
            'action' => 'update',
            'shortname' => 'invalidguestconfigurationupdate',
            'guest' => 1
        ));
        $exists = $DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
        $this->assertEquals($exists, false);

        // Validate that creation with guest access fails when not adding the guest plugin to courses.
        $this->run_core_course_import(array(
            'action' => 'update',
            'shortname' => 'invalidguestconfigurationupdate',
            'password' => 'asdf'
        ));
        $exists = $DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that there are no side-effects of enabling or disabling guest
     * access twice
     */
    public function test_version1importcompletesimportwhenenablingordisablingguestenrolmenttwice() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        // Create course without guest access or password.
        $this->run_core_course_import(array('shortname' => 'plugintwice'));

        // Disable guest access in update action.
        $data = array('action' => 'update', 'shortname' => 'plugintwice', 'guest' => 0);
        $this->run_core_course_import($data);

        // Data validation.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'plugintwice'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest')), 1);
        // TODO: change password back to NULL if the guest plugin starts using it as the default again.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_DISABLED,
            'password' => ''
        ));

        // Clean up category.
        $DB->delete_records('course_categories');

        // Create course with guest access.
        $data = array('shortname' => 'plugintwice2', 'guest' => 1);

        // Enable guest access in update action.
        $this->run_core_course_import($data);
        $data = array('action' => 'update', 'shortname' => 'plugintwice2', 'guest' => 1);
        $this->run_core_course_import($data);

        // Data validation.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'plugintwice2'));
        $this->assertEquals($DB->count_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest')), 1);
        // TODO: change password back to NULL if the guest plugin starts using it as the default again.
        $this->assert_record_exists('enrol', array(
            'courseid' => $courseid,
            'enrol' => 'guest',
            'status' => ENROL_INSTANCE_ENABLED,
            'password' => ''
        ));
    }

    /**
     * Validate that the plugin prevents configuring a deleted guest enrolment
     * plugin
     */
    public function test_versionimportpreventsconfiguringremovedguestplugin() {
        global $DB;

        // Run basic import.
        $this->run_core_course_import(array('shortname' => 'removedguestplugin', 'fullname' => 'fullname'));
        // Delete plugin from course.
        $DB->delete_records('enrol', array('enrol' => 'guest'));

        $expected = array('shortname' => 'removedguestplugin', 'fullname' => 'fullname');

        // Validate for specifying guest value of 0.
        $data = array(
            'action' => 'update',
            'shortname' => 'removedguestplugin',
            'fullname' => 'updatedfullname',
            'guest' => 0
        );
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        // Validate for specifying guest value of 1.
        $data = array(
            'action' => 'update',
            'shortname' => 'removedguestplugin',
            'fullname' => 'updatedfullname',
            'guest' => 1
        );
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);

        // Validate for specifying a password value.
        $data = array(
            'action' => 'update',
            'shortname' => 'removedguestplugin',
            'fullname' => 'updatedfullname',
            'password' => 'password'
        );
        $this->run_core_course_import($data, false);
        $this->assert_record_exists('course', $expected);
    }

    /**
     * Validate that the course rollover via link / template sets up the right
     * data
     */
    public function test_version1importrolloversetscorrectcoursedata() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        // Get initial counts.
        $initialnumcourses = $DB->count_records('course');

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));
        $initialnumcoursesincategory = $DB->get_field('course_categories', 'coursecount', array('id' => $categoryid));
        $initialnumforums = $DB->count_records('forum');

        // Setup.
        set_config('backup_general_activities', 1, 'backup');

        // Create a test course.
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'rollovertemplateshortname';
        $record->fullname = 'rollovertemplatefullname';
        $record->id = $DB->insert_record('course', $record);
        // Make sure we have a section to work with.
        course_create_sections_if_missing($record, 1);
        $section = get_fast_modinfo($record)->get_section_info(1);

        // Create a test forum instance.
        $forum = new stdClass;
        $forum->course = $record->id;
        $forum->type = 'news';
        $forum->name = 'rollovertemplateforum';
        $forum->intro = 'rollovertemplateintro';
        $forum->id = $DB->insert_record('forum', $forum);

        // Add it as a course module.
        $forum->module = $DB->get_field('modules', 'id', array('name' => 'forum'));
        $forum->instance = $forum->id;
        $forum->section = $section->id;
        $cmid = add_course_module($forum);

        // Run the import.
        $data = $this->get_core_course_data('childcategory');
        $data['shortname'] = 'rollovershortname';
        $data['link'] = 'rollovertemplateshortname';
        $this->run_core_course_import($data, false);

        // Validate the number of courses.
        $this->assertEquals($DB->count_records('course'), $initialnumcourses + 2);

        // Validate the course course data, as well as category and sortorder.
        $sortorder = $DB->get_field('course', 'sortorder', array('shortname' => 'rollovertemplateshortname'));
        $this->assert_record_exists('course', array(
            'shortname' => 'rollovershortname',
            'fullname' => 'rlipfullname',
            'category' => $categoryid,
            'sortorder' => $sortorder - 1
        ));

        // Validate that the category is updated with the correct number of courses.
        $this->assert_record_exists('course_categories', array(
            'id' => $categoryid,
            'coursecount' => $initialnumcoursesincategory + 2
        ));

        // Validate that the correct number of forum instances exist.
        $this->assertEquals($DB->count_records('forum'), $initialnumforums + 2);

        // Validate the specific forum / course module setup within the new course.
        $sql = "SELECT *
                  FROM {modules} m
                  JOIN {course_modules} cm ON m.id = cm.module
                  JOIN {forum} f ON cm.instance = f.id
                  JOIN {course} c ON cm.course = c.id
                  JOIN {course_sections} cs ON c.id = cs.course AND cm.section = cs.id
                 WHERE f.type = ? AND f.name = ? AND f.intro = ? AND c.shortname = ?";

        $exist = $DB->record_exists_sql($sql, array('news', 'rollovertemplateforum', 'rollovertemplateintro', 'rollovershortname'));
        $this->assertTrue($exist);
    }

    /**
     * Validate that we can roll over into a different category
     */
    public function test_version1importrolloversupportssettingcategory() {
        global $DB;

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Create a test course.
        $record = new stdClass;
        $record->category = $categoryid;
        $record->shortname = 'categorytemplateshortname';
        $record->fullname = 'categorytemplatefullname';
        $record->id = $DB->insert_record('course', $record);

        // Create a second test category.
        $category = $this->create_test_category('templatecategory');
        $secondcatid = $DB->get_field('course_categories', 'id', array('name' => $category));

        // Run the import.
        $data = $this->get_core_course_data($category);
        $data['shortname'] = 'categorytemplatecopyshortname';
        $data['link'] = 'categorytemplateshortname';
        $this->run_core_course_import($data, false);

        // Validate that the courses are each in their respective categories.
        $this->assert_record_exists('course', array('shortname' => 'categorytemplateshortname', 'category' => $categoryid));
        $this->assert_record_exists('course', array('shortname' => 'categorytemplatecopyshortname', 'category' => $secondcatid));
    }

    /**
     * Validate that the course rollover via link / template does not include
     * user data
     */
    public function test_version1importrolloverexcludesusers() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Create a test user.
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->id = user_create_user($user);

        // Create a test template course.
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

        // Create a test role.
        $roleid = create_role('rolloverrole', 'rolloverrole', 'rolloverrole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        // Enrol the test user into the template course, assigning them the test role.
        enrol_try_internal_enrol($record->id, $user->id, $roleid);

        // Validate setup.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);

        // Run the import.
        $this->run_core_course_import(array('link' => 'rliptemplateshortname'));

        // Validate that no role assignments or enrolments were created.
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that invalid template / link values can't be set on course create
     */
    public function test_version1importpreventsinvalidlinkoncreate() {
        global $DB;

        // Validate setup.
        $initialnumcourses = $DB->count_records('course');

        // Run the import.
        $this->run_core_course_import(array('shortname' => 'invalidlink', 'link' => 'bogusshortname'));

        // Validate that no new course was created.
        $this->assertEquals($DB->count_records('course'), $initialnumcourses);
    }

    /**
     * Validate that course create and update actions set time created
     * and time modified appropriately
     */
    public function test_version1importsetscoursetimestamps() {
        global $DB;

        // Record the current time.
        $starttime = time();

        // Set up base data.
        $this->run_core_course_import(array('shortname' => 'coursetimestamps'));

        // Validate timestamps.
        $where = "shortname = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('coursetimestamps', $starttime, $starttime);
        $exists = $DB->record_exists_select('course', $where, $params);
        $this->assertEquals($exists, true);

        // Update data.
        $this->run_core_course_import(array(
            'shortname' => 'coursetimestamps',
            'action' => 'update',
            'username' => 'shortname',
            'fullname' => 'newfullname'
        ));

        // Validate timestamps.
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
    public function test_version1importsupportscoursedelete() {
        $supports = plugin_supports('rlipimport', 'version1', 'course_delete');
        $requiredfields = array('shortname');
        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin can delete courses based on shortname
     */
    public function test_version1importdeletescoursebasedonshortname() {
        global $DB;

        $this->run_core_course_import(array('shortname' => 'deleteshortname'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'deleteshortname'));

        $data = array('action' => 'delete', 'shortname' => 'deleteshortname');
        $this->run_core_course_import($data, false);

        $exists = $DB->record_exists('course', array('shortname' => 'deleteshortname'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the version 1 plugin does not delete courses when the
     * specified shortname is incorrect
     */
    public function test_version1importdoesnotdeletecoursewithinvalidshortname() {
        $this->run_core_course_import(array('shortname' => 'validshortname'));

        $data = array('action' => 'delete', 'shortname' => 'bogusshortname');
        $this->run_core_course_import($data, false);

        $this->assert_record_exists('course', array('shortname' => 'validshortname'));
    }

    /**
     * Validate that the version 1 plugin deletes appropriate associations when
     * deleting a course
     */
    public function test_version1importdeletecoursedeletesassociations() {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/backup/lib.php');
        require_once($CFG->dirroot.'/lib/conditionlib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/tag/lib.php');
        require_once($CFG->dirroot.'/lib/questionlib.php');

        // Setup.
        $initialnumcontexts = $DB->count_records('context', array('contextlevel' => CONTEXT_COURSE));
        $DB->delete_records('block_instances');

        // Set up the course with one section, including default blocks.
        set_config('defaultblocks_topics', 'search_forums');
        set_config('maxsections', 10, 'moodlecourse');

        $this->run_core_course_import(array('shortname' => 'deleteassociationsshortname', 'numsections' => 1));

        // Create a user record.
        $record = new stdClass;
        $record->username = 'testuser';
        $record->password = 'Testpass!0';
        $userid = user_create_user($record);

        // Create a course-level role.
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'deleteassociationsshortname'));
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        $roleid = create_role('deleterole', 'deleterole', 'deleterole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $enrol = new stdClass;
        $enrol->enrol = 'manual';
        $enrol->courseid = $courseid;
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->insert_record('enrol', $enrol);

        // Assign the user to the course-level role.
        enrol_try_internal_enrol($courseid, $userid, $roleid);

        // Create a grade item.
        $gradeitem = new grade_item(array('courseid' => $courseid, 'itemtype' => 'manual', 'itemname' => 'testitem'), false);
        $gradeitem->insert();
        $gradegrade = new grade_grade(array('itemid' => $gradeitem->id, 'userid' => $userid), false);

        // Assign the user a grade.
        $gradegrade->insert();

        // Create a grade outcome.
        $gradeoutcome = new grade_outcome(array(
            'courseid' => $courseid,
            'shortname' => 'bogusshortname',
            'fullname' => 'bogusfullname'
        ));
        $gradeoutcome->insert();

        // Create a grade scale.
        $gradescale = new grade_scale(array(
            'courseid' => $courseid,
            'name' => 'bogusname',
            'userid' => $userid,
            'scale' => 'bogusscale',
            'description' => 'bogusdescription'
        ));
        $gradescale->insert();

        // Set a grade setting value.
        grade_set_setting($courseid, 'bogus', 'bogus');

        // Set up a grade letter.
        $gradeletter = new stdClass;
        $gradeletter->contextid = $coursecontext->id;
        $gradeletter->lowerboundary = 80;
        $gradeletter->letter = 'A';
        $DB->insert_record('grade_letters', $gradeletter);

        // Set up a forum instance.
        $forum = new stdClass;
        $forum->course = $courseid;
        $forum->intro = 'intro';
        $forum->id = $DB->insert_record('forum', $forum);

        // Add it as a course module.
        $forum->module = $DB->get_field('modules', 'id', array('name' => 'forum'));
        $forum->instance = $forum->id;
        $cmid = add_course_module($forum);

        // Set up a completion record.
        $completion = new stdClass;
        $completion->coursemoduleid = $cmid;
        $completion->completionstate = 0;
        $completion->userid = 9999;
        $completion->timemodified = time();
        $DB->insert_record('course_modules_completion', $completion);

        // Set up a completion condition.
        $forum->id = $cmid;
        $ci = new condition_info($forum, CONDITION_MISSING_EVERYTHING, false);
        $ci->add_completion_condition($cmid, COMPLETION_ENABLED);

        // Set the block position.
        $instance = $DB->get_record('block_instances', array('parentcontextid' => $coursecontext->id));
        $page = new stdClass;
        $page->context = $coursecontext;
        $page->pagetype = 'course-view-*';
        $page->subpage = false;
        blocks_set_visibility($instance, $page, 1);

        // Create a group.
        $group = new stdClass;
        $group->name = 'testgroup';
        $group->courseid = $courseid;
        $groupid = groups_create_group($group);

        // Add the user to the group.
        groups_add_member($groupid, $userid);

        // Create a grouping containing our group.
        $grouping = new stdClass;
        $grouping->name = 'testgrouping';
        $grouping->courseid = $courseid;
        $groupingid = groups_create_grouping($grouping);
        groups_assign_grouping($groupingid, $groupid);

        // Set up a user tag.
        tag_set('course', $courseid, array('testtag'));

        // Add a course-level log.
        add_to_log($courseid, 'bogus', 'bogus');

        // Set up the default course question category.
        $newcategory = question_make_default_categories(array($coursecontext));

        // Create a test question.
        $question = new stdClass;
        $question->qtype = 'truefalse';
        $form = new stdClass;
        $form->category = $newcategory->id;
        $form->name = 'testquestion';
        $form->correctanswer = 1;
        $form->feedbacktrue = array('text' => 'bogustext', 'format' => FORMAT_HTML);
        $form->feedbackfalse = array('text' => 'bogustext', 'format' => FORMAT_HTML);
        $question = question_bank::get_qtype('truefalse')->save_question($question, $form);

        if (function_exists('course_set_display')) {
            // Set a "course display" setting.
            course_set_display($courseid, 1);
        }

        // Make a bogus backup record.
        $backupcourse = new stdClass;
        $backupcourse->courseid = $courseid;
        $DB->insert_record('backup_courses', $backupcourse);

        // Add a user lastaccess record.
        $lastaccess = new stdClass;
        $lastaccess->userid = $userid;
        $lastaccess->courseid = $courseid;
        $DB->insert_record('user_lastaccess', $lastaccess);

        // Make a bogus backup log record.
        $log = new stdClass();
        $log->backupid = $courseid;
        $log->timecreated = time();
        $log->loglevel = 1;
        $log->message = 'bogus';
        $DB->insert_record('backup_logs', $log);

        // Get initial counts.
        $initialnumcourse = $DB->count_records('course');
        $initialnumroleassignments = $DB->count_records('role_assignments');
        $initialnumuserenrolments = $DB->count_records('user_enrolments');
        $initialnumgradeitems = $DB->count_records('grade_items');
        $initialnumgradegrades = $DB->count_records('grade_grades');
        $initialnumgradeoutcomes = $DB->count_records('grade_outcomes');
        $initialnumgradeoutcomescourses = $DB->count_records('grade_outcomes_courses');
        $initialnumscale = $DB->count_records('scale');
        $initialnumgradesettings = $DB->count_records('grade_settings');
        $initialnumgradeletters = $DB->count_records('grade_letters');
        $initialnumforum = $DB->count_records('forum');
        $initialnumcoursemodules = $DB->count_records('course_modules');
        $initialnumcoursemodulescompletion = $DB->count_records('course_modules_completion');
        $initialnumcoursemodulesavailability = $DB->count_records('course_modules_availability');
        $initialnumblockinstances = $DB->count_records('block_instances');
        $initialnumblockpositions = $DB->count_records('block_positions');
        $initialnumgroups = $DB->count_records('groups');
        $initialnumgroupsmembers = $DB->count_records('groups_members');
        $initialnumgroupings = $DB->count_records('groupings');
        $initialnumgroupingsgroups = $DB->count_records('groupings_groups');
        $initialnumtaginstance = $DB->count_records('tag_instance');
        $initialnumcoursesections = $DB->count_records('course_sections');
        $initialnumquestioncategories = $DB->count_records('question_categories');
        $initialnumquestion = $DB->count_records('question');
        if (self::$coursedisplay) {
            $initialnumcoursedisplay = $DB->count_records('course_display');
        }
        $initialnumbackupcourses = $DB->count_records('backup_courses');
        $initialnumuserlastaccess = $DB->count_records('user_lastaccess');
        $initialnumbackuplogs = $DB->count_records('backup_logs');

        // Delete the course.
        $data = array('action' => 'delete', 'shortname' => 'deleteassociationsshortname');
        $this->run_core_course_import($data, false);

        // Validate the result.
        $this->assertEquals($DB->count_records('course'), $initialnumcourse - 1);
        $this->assertEquals($DB->count_records('role_assignments'), $initialnumroleassignments - 1);
        $this->assertEquals($DB->count_records('user_enrolments'), $initialnumuserenrolments -  1);
        $this->assertEquals($DB->count_records('grade_items'), $initialnumgradeitems - 2);
        $this->assertEquals($DB->count_records('grade_grades'), $initialnumgradegrades -  1);
        $this->assertEquals($DB->count_records('grade_outcomes'), $initialnumgradeoutcomes -  1);
        $this->assertEquals($DB->count_records('grade_outcomes_courses'), $initialnumgradeoutcomescourses -  1);
        $this->assertEquals($DB->count_records('scale'), $initialnumscale -  1);
        $this->assertEquals($DB->count_records('grade_settings'), $initialnumgradesettings -  1);
        $this->assertEquals($DB->count_records('grade_letters'), $initialnumgradeletters -  1);
        $this->assertEquals($DB->count_records('forum'), $initialnumforum -  1);
        $this->assertEquals($DB->count_records('course_modules'), $initialnumcoursemodules -  1);

        /*
         Uncomment the two lines below when this fix is available: http://tracker.moodle.org/browse/MDL-32988
         $this->assertEquals($DB->count_records('course_modules_completion'), $initialnumcourse_modules_completion - 1);
         $this->assertEquals($DB->count_records('course_modules_availability'), $initialnumcourse_modules_availability - 1);
        */
        $this->assertEquals($initialnumblockinstances - 4, $DB->count_records('block_instances'));
        $this->assertEquals($DB->count_records('block_positions'), $initialnumblockpositions - 1);
        $this->assertEquals($DB->count_records('groups'), $initialnumgroups - 1);
        $this->assertEquals($DB->count_records('groups_members'), $initialnumgroupsmembers - 1);
        $this->assertEquals($DB->count_records('groupings'), $initialnumgroupings - 1);
        $this->assertEquals($DB->count_records('groupings_groups'), $initialnumgroupingsgroups - 1);
        $this->assertEquals($DB->count_records('log', array('course' => $courseid)), 0);
        $this->assertEquals($DB->count_records('tag_instance'), $initialnumtaginstance - 1);
        $this->assertEquals($DB->count_records('course_sections'), $initialnumcoursesections - 1);
        $this->assertEquals($DB->count_records('question_categories'), $initialnumquestioncategories - 1);
        $this->assertEquals($DB->count_records('question'), $initialnumquestion - 1);
        if (self::$coursedisplay) {
            $this->assertEquals($DB->count_records('course_display'), $initialnumcoursedisplay - 1);
        }
        $this->assertEquals($DB->count_records('backup_courses'), $initialnumbackupcourses - 1);
        $this->assertEquals($DB->count_records('user_lastaccess'), $initialnumuserlastaccess - 1);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on course creation
     */
    public function test_version1importusescoursefieldmappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        // Setup.
        set_config('maxsections', 20, 'moodlecourse');

        // Determine the pre-existing category's id.
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'childcategory'));

        // Set up our mapping of standard field names to custom field names.
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

        // Store the mapping records in the database.
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'course';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        // Run the import.
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
            'startdate'   => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'newsitems'   => 8,
            'showgrades'  => 0,
            'showreports' => 1,
            'maxbytes'    => 0,
            'visible'     => 0,
            'lang'        => 'en',
            'category'    => $categoryid
        );

        $courseid = $this->assert_core_course_exists($params);

        // Validate enrolment record.
        $data = array(
            'courseid' => $courseid,
            'enrol'    => 'guest',
            'password' => 'fieldmappingpassword',
            'status'   => ENROL_INSTANCE_ENABLED
        );
        $this->assert_record_exists('enrol', $data);

        // Clean up the mess.
        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE, array('entitytype' => 'course'));
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function test_version1importcoursefieldimportpreventsstandardfielduse() {
        global $CFG, $DB;
        $plugindir = get_plugin_directory('rlipimport', 'version1');
        require_once($plugindir.'/version1.class.php');
        require_once($plugindir.'/lib.php');

        // Create the mapping record.
        $record = new stdClass;
        $record->entitytype = 'course';
        $record->standardfieldname = 'shortname';
        $record->customfieldname = 'shortname2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        // Get the import plugin set up.
        $data = array();
        $provider = new rlipimport_version1_importprovider_mockcourse($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('course');

        // Transform a sample record.
        $record = new stdClass;
        $record->shortname = 'shortname';
        $record = $importplugin->apply_mapping('course', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        // Validate that the field was unset.
        $this->assertEquals(isset($record->shortname), false);
    }

    /**
     * Validate that an import can make a course visible
     */
    public function test_version1importmakecoursevisible() {
        global $DB;

        // Create invisible course.
        $this->run_core_course_import(array('shortname' => 'visiblecrs', 'visible' => 0));

        // Data validation.
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'visiblecrs'));
        $this->assertEquals($visible, 0);

        // Make course visible in update action.
        $data = array('action' => 'update', 'shortname' => 'visiblecrs', 'visible' => 1);
        $this->run_core_course_import($data);

        // Validate that course import updated the visibility.
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'visiblecrs'));
        $this->assertEquals($visible, 1);

    }

    /**
     * Validate that an import can make a course invisible
     */
    public function test_version1importmakecourseinvisible() {
        global $DB;

        // Create a course - visible by default.
        $this->run_core_course_import(array('shortname' => 'invisiblecrs'));

        // Data validation.
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'invisiblecrs'));
        $this->assertEquals($visible, 1);

        // Make course visible in update action.
        $data = array('action' => 'update', 'shortname' => 'invisiblecrs', 'visible' => 0);
        $this->run_core_course_import($data);

        // Validate that course import updated the visibility.
        $visible = $DB->get_field('course', 'visible', array('shortname' => 'invisiblecrs'));
        $this->assertEquals($visible, 0);

    }

    /**
     *  Validate that an import uses moodlecourse defaults
     */
    public function test_version1importcoursecreateusesdefaults() {
        global $DB;

        // Backup current course defaults....
        $backup = get_config('moodlecourse');

        // Set course defaults.
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

        foreach ($defaults as $default => $value) {
            set_config($default, $value, 'moodlecourse');
        }

        // Create a course - visible by default.
        $this->run_core_course_import(array('shortname' => 'crsdefaults'));

        $sql = 'SELECT c.*, cfo1.value as numsections, cfo2.value as hiddensections
                  FROM {course} c
                  LEFT JOIN {course_format_options} cfo1 ON cfo1.courseid=c.id AND cfo1.name = \'numsections\'
                  LEFT JOIN {course_format_options} cfo2 ON cfo2.courseid=c.id AND cfo2.name = \'hiddensections\'
                 WHERE c.shortname = \'crsdefaults\'';

        // Data validation.
        $course = $DB->get_record_sql($sql);
        foreach ($defaults as $field => $value) {
            $this->assertEquals($course->$field, $value);
            unset_config($field, 'moodlecourse');
        }

        // Reset moodlecourse config values.
        foreach ($backup as $default => $value) {
            set_config($default, $value, 'moodlecourse');
        }
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function test_version1importsucceedswithmaxlengthcoursefields() {
        // Data for all fixed-size fields at their maximum sizes.
        $data = array(
            'fullname' => str_repeat('x', 254),
            'shortname' => str_repeat('x', 100),
            'idnumber' => str_repeat('x', 100)
        );
        // Run the import, suppressing warning about log contents being too long.
        ob_start();
        $this->run_core_course_import($data);
        ob_end_clean();

        // Data validation.
        $this->assert_record_exists('course', $data);
    }
}
