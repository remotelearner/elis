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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Class for testing course description-moodle template course association
 * creation during course description create and update actions
 */
class elis_course_associate_moodle_course_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array('course' => 'moodle',
                     'course_categories' => 'moodle',
                     field::TABLE => 'elis_core',
                     course::TABLE => 'elis_program',
                     coursetemplate::TABLE => 'elis_program',
                     RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('block_instances' => 'moodle',
                     'cache_flags' => 'moodle',
                     'context' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'log' => 'moodle');
    }

    /**
     * Validate that course description-moodle template course associations
     * can be created during a course description create action
     */
    function test_associate_moodle_course_during_course_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));

        //set up the site course record
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $templatecourse = new stdClass;
        $templatecourse->category = $coursecategory->id;
        $templatecourse->shortname = 'testcourseshortname';
        $templatecourse->fullname = 'testcoursefullname';
        $templatecourse = create_course($templatecourse);

        //run the course description create action
        $record = new stdClass;
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->link = $templatecourse->shortname;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->course_create($record, 'bogus');

        //validation
        $pmcourseid = $DB->get_field(course::TABLE, 'id', array('idnumber' => 'testcourseidnumber'));
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array('courseid' => $pmcourseid,
                                                                          'location' => $templatecourse->id,
                                                                          'templateclass' => 'moodlecourseurl')));
    }

    /**
     * Validate that course description-moodle template course associations
     * can be created during a course description update action
     */
    function test_associate_moodle_course_during_course_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));

        //set up the site course record
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $templatecourse = new stdClass;
        $templatecourse->category = $coursecategory->id;
        $templatecourse->shortname = 'testcourseshortname';
        $templatecourse->fullname = 'testcoursefullname';
        $templatecourse = create_course($templatecourse);

        //create the course description
        $pmcourse = new course(array('name' => 'testcoursename',
                                     'idnumber' => 'testcourseidnumber',
                                     'syllabus' => ''));
        $pmcourse->save();

        //run the course description update action
        $record = new stdClass;
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->link = $templatecourse->shortname;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->course_update($record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array('courseid' => $pmcourse->id,
                                                                          'location' => $templatecourse->id,
                                                                          'templateclass' => 'moodlecourseurl')));
    }
}
