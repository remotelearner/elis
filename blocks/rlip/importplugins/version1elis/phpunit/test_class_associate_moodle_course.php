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

/**
 * Class for testing class instance-moodle course association
 * creation during class instance create and update actions
 */
class elis_class_associate_moodle_course_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        return array('course' => 'moodle',
                     'course_categories' => 'moodle',
                     classmoodlecourse::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     coursetemplate::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program');
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
     * Data provider for testing various linking scenarios
     *
     * @return array The necessary data for testing
     */
    function link_course_provider() {
        return array(//auto-create new Moodle course
                     array(true, NULL),
                     //use CD template course
                     array(false, 'auto'),
                     //link to a specific Moodle course
                     array(false, 'testcourseshortname'));
    }

    /**
     * Validate that class instance-moodle course associations
     * can be created during a class instance create action
     *
     * @param boolean $autocreate Whether we should auto-create a new Moodle course
     * @param string $link The link attribute to use in the import
     * @dataProvider link_course_provider
     */
    function test_associate_moodle_course_during_class_create($autocreate, $link) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));

        //set up the site course record
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        //need this for the 'auto' case, at the very least
        $coursetemplate = new coursetemplate(array('courseid' => $course->id,
                                                   'location' => $moodlecourse->id,
                                                   'templatecourse' => 'moodlecourseurl'));
        $coursetemplate->save();

        //run the class instance create action
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        if ($autocreate) {
            $record->autocreate = 1;
        } else {
            $record->link = $link;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->class_create($record, 'bogus');

        //validation
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber')); 
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $classid,
                                                                             'moodlecourseid' => $moodlecourse->id,
                                                                             'enroltype' => 0,
                                                                             'enrolplugin' => 'crlm',
                                                                             'autocreated' => (int)$autocreate)));
    }

    /**
     * Validate that class instance-moodle course associations
     * can be created during a class instance update action
     *
     * @param boolean $autocreate Whether we should auto-create a new Moodle course
     * @param string $link The link attribute to use in the import
     * @dataProvider link_course_provider
     */
    function test_associate_moodle_course_during_class_update($autocreate, $link) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        //set up the site course record
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //need this for the 'auto' case, at the very least
        $coursetemplate = new coursetemplate(array('courseid' => $course->id,
                                                   'location' => $moodlecourse->id,
                                                   'templatecourse' => 'moodlecourseurl'));
        $coursetemplate->save();

        //run the class instance create action
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        if ($autocreate) {
            $record->autocreate = 1;
        } else {
            $record->link = $link;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->class_update($record, 'bogus');

        //validation 
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $class->id,
                                                                             'moodlecourseid' => $moodlecourse->id,
                                                                             'enroltype' => 0,
                                                                             'enrolplugin' => 'crlm',
                                                                             'autocreated' => (int)$autocreate)));
    }
}