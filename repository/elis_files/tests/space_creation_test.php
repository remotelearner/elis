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
 * @package    repository_elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

global $CFG;

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/tests/constants.php');
require_once($CFG->dirroot.'/repository/elis_files/tests/constants.php');

/**
 * Class to test space creation
 * @group repository_elis_files
 */
class repository_elis_files_space_creation_testcase extends elis_database_test {
    /** @var int $categoryid The course category we are creating our test courses within */
    public $categoryid;

    /** @var bool @haspm Flag to indicate whether the ELIS PM code is present */
    public static $haspm = false;

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * This function initializes all of the setup steps required by each step.
     * @uses $CFG
     */
    protected function setUp() {
        global $CFG;

        parent::setUp();
        $this->setAdminUser();
        $this->categoryid = 1;

        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');
            self::$haspm = true;
        }
    }

    /**
     * Sends back a single character from a larger list of characters for testing valid character synchronisation to Alfresco.
     *
     * @return string A single test character
     */
    public function invalid_folder_name_characters_provider() {
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
     * @uses $DB
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

        $course = $this->getDataGenerator()->create_course((array) $data);

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
     * @uses $DB
     * @dataProvider invalid_folder_name_characters_provider
     * @param string $checkchar A single character to attempt synchronising
     */
    public function test_course_shortname_values($checkchar) {
        $this->markTestIncomplete('This test is currently broken and causes a fatal error');

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB;

        // Test with the special character at the beginning of the course->shortname property
        $courseid = $this->setup_test_course($checkchar, CHAR_POS_L);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid, '$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);

        // Test with the special character in the middle of the course->shortname property
        $courseid = $this->setup_test_course($checkchar, CHAR_POS_M);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid, '$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);

        // Test with the special character at the end of the course->shortname property
        $courseid = $this->setup_test_course($checkchar, CHAR_POS_R);
        $uuid = $repo->get_course_store($courseid);
        $this->assertNotEquals(false, $uuid, '$course->shortname = "'.$DB->get_field('course', 'shortname', array('id' => $courseid)));
        $this->assertTrue($DB->record_exists('elis_files_course_store', array('courseid' => $courseid, 'uuid' => $uuid)));
        $repo->delete($uuid);
    }

    /**
     * Test creating course storage folders in Alfresco from courses that are using potentially invalid characters in their
     * shortname value.
     * @uses $DB
     * @dataProvider invalid_folder_name_characters_provider
     * @param string $checkchar A single character to attempt synchronising
     */
    public function test_userset_name_values($checkchar) {
        if (!self::$haspm) {
            $this->markTestSkipped('ELIS PM is required for Userset testing');
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB;

        $repo = repository_factory::factory('elis_files');

        // Test with the special character at the beginning of the userset->name property
        $usersetid = $this->setup_test_userset($checkchar, CHAR_POS_L);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid, '$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);

        // Test with the special character in the middle of the userset->name property
        $usersetid = $this->setup_test_userset($checkchar, CHAR_POS_M);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid, '$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);

        // Test with the special character at the end of the userset->name property
        $usersetid = $this->setup_test_userset($checkchar, CHAR_POS_R);
        $uuid = $repo->get_userset_store($usersetid);
        $this->assertNotEquals(false, $uuid, '$userset->name = "'.$DB->get_field(userset::TABLE, 'name', array('id' => $usersetid)).'"');
        $this->assertTrue($DB->record_exists('elis_files_userset_store', array('usersetid' => $usersetid, 'uuid' => $uuid)));
        $repo->delete($uuid);
    }

    /**
     * Validate duplicate user set creation
     */
    public function test_duplicate_userset_creation() {
        if (!self::$haspm) {
            $this->markTestSkipped('ELIS PM is required for Userset testing');
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $repo = repository_factory::factory('elis_files');

        $userset = new userset(array('name' => 'testuserset'));
        $userset->save();

        $uuid = $repo->get_userset_store($userset->id);
        $uuidduplicate = $repo->get_userset_store($userset->id);

        $this->assertEquals($uuidduplicate, $uuid);
    }
}
