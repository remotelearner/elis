<?php
/**
 * Validate that synchronising special, allowed, characters within a Moodle 'shortname' value is accepted in Alfresco.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */


if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}


require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');


define('CHAR_POS_L', 'start');
define('CHAR_POS_M', 'middle');
define('CHAR_POS_R', 'end');


class test_space_creation extends elis_database_test {

    var $categoryid;       // The course category we are creating our test courses within
    static $haspm = false; // Flag to indicate whether the ELIS PM code is present

    protected static function get_overlay_tables() {
        global $CFG;

        $tables = array(
            'block_instances' => 'moodle',
            'cache_flags' => 'moodle',
            'context' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'course_sections' => 'moodle',
            'enrol' => 'moodle',
            'log' => 'moodle',
            'elis_files_course_store' => 'repository_elis_files'
        );

        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');

            require_once(elispm::lib('data/userset.class.php'));

            $tables[userset::TABLE]             = 'elis_program';
            $tables['elis_files_userset_store'] = 'repository_elis_files';

            self::$haspm = true;
        }

        return $tables;
    }

    public function setUp() {
        parent::setUp();

        // Load initial context and site course records into overlay
        $sysctx = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        if (!empty($sysctx)) {
            self::$overlaydb->import_record('context', $sysctx);
        }

        $fpcourse = self::$origdb->get_record('course', array('id' => SITEID));
        if (!empty($fpcourse)) {
            self::$overlaydb->import_record('course', $fpcourse);
        }

        $fpctx = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $fpcourse->id));
        if (!empty($fpctx)) {
            self::$overlaydb->import_record('context', $fpctx);
        }

        $categories = self::$origdb->get_records('course_categories', array(), 'id ASC', '*', 0, 1);
        if (!empty($categories)) {
            $category = current($categories);
            self::$overlaydb->import_record('course_categories', $category);
            $this->categoryid = $category->id;
        }

        $GLOBALS['USER'] = get_admin();
    }

    /**
     * Sends back a single character from a larger list of characters for testing valid character synchronisation to Alfresco.
     *
     * @return string A single test character
     */
    public function invalidFolderNameCharactersProvider() {
        $chars = ' `~!@#$%^&*()_-+=[]{}\|;:\'",/?';

        $char_array = str_split($chars);
        $return     = array();

        foreach ($char_array as $char) {
            $return[] = array($char);
        }

        return $return;
    }

    /**
     * Setup a test Moodle course with a given special character at one of three positions within the course 'shortname' value
     *
     * @param string $char     The test character
     * @param string $position The position to insert the character (one of CHAR_POS_L, CHAR_POS_M, CHAR_POS_R)
     * @return int The record ID of the created course
     */
    private function setup_test_course($char, $position) {
        global $DB;

        // Setup the 'shortname' value based on the supplied parameters
        $shortname = ($position == CHAR_POS_L ? $char : '').'TEST'.($position == CHAR_POS_M ? $char : '').'COURSE'.
                     ($position == CHAR_POS_R ? $char : '');

        $data = new stdClass;
        $data->fullname  = 'Test ELIS Files Course';
        $data->shortname = $shortname;
        $data->category  = $this->categoryid;

        $course = create_course($data);

        return $course->id;
    }

    /**
     * Setup a test Moodle course with a given special character at one of three positions within the course 'shortname' value
     *
     * @param string $char     The test character
     * @param string $position The position to insert the character (one of CHAR_POS_L, CHAR_POS_M, CHAR_POS_R)
     * @return int The record ID of the created user set
     */
    private function setup_test_userset($char, $position) {
        // Setup the 'name' value based on the supplied parameters
        $name = ($position == CHAR_POS_L ? $char : '').'TEST'.($position == CHAR_POS_M ? $char : '').'COURSE'.
                ($position == CHAR_POS_R ? $char : '');

        $userset = new userset(array('name' => $name));
        $userset->save();

        return $userset->id;
    }

    /**
     * Test creating course storage folders in Alfresco from courses that are using potentially invalid characters in their
     * shortname value.
     *
     * @dataProvider invalidFolderNameCharactersProvider
     * @param string $check_char A single character to attempt synchronising
     */
    public function testCourseShortnameValues($check_char) {
        global $DB;

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('ELIS Files is not configured or running');
        }
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_L);
        // Test with the special character at the beginning of the course->shortname property
        $courseid = $this->setup_test_course($check_char, CHAR_POS_L);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid, '$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_M);
        // Test with the special character in the middle of the course->shortname property
        $courseid = $this->setup_test_course($check_char, CHAR_POS_M);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid,'$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_R);
        // Test with the special character at the end of the course->shortname property
        $courseid = $this->setup_test_course($check_char, CHAR_POS_R);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid,'$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);
    }

    /**
     * Test creating course storage folders in Alfresco from courses that are using potentially invalid characters in their
     * shortname value.
     *
     * @dataProvider invalidFolderNameCharactersProvider
     * @param string $check_char A single character to attempt synchronising
     */
    public function testUsersetNameValues($check_char) {
        global $DB;

        if (!self::$haspm) {
            $this->markTestSkipped('ELIS PM is required for Userset testing');
        }

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('ELIS Files is not configured or running');
        }
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_L);
        // Test with the special character at the beginning of the userset->name property
        $usersetid = $this->setup_test_userset($check_char, CHAR_POS_L);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid, '$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_M);
        // Test with the special character in the middle of the userset->name property
        $usersetid = $this->setup_test_userset($check_char, CHAR_POS_M);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid,'$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);
//print_object('$check_char: "'.$check_char.'" -- '.CHAR_POS_R);
        // Test with the special character at the end of the userset->name property
        $usersetid = $this->setup_test_userset($check_char, CHAR_POS_R);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid,'$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);
    }

    // Validate duplicate user set creation
    function testDuplicateUsersetCreation() {
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('ELIS Files is not configured or running');
        }

        $userset = new userset(array('name' => 'testuserset'));
        $userset->save();

        $uuid = $repo->get_userset_store($userset->id);
        $uuidduplicate = $repo->get_userset_store($userset->id);

        $this->assertEquals($uuidduplicate, $uuid);
    }

}
