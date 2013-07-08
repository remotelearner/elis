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
 * Class for validating that ELIS / PM user actions propagate the appropriate
 * enrolments over to Moodle
 */
class elis_enrolment_sync_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'course_format_options'               => 'moodle',
            'enrol' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            classmoodlecourse::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            pmclass::TABLE => 'elis_program',
            student::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array('block_instances' => 'moodle',
                     'cache_flags' => 'moodle',
                     'course_sections' => 'moodle',
                     'log' => 'moodle',
                     coursetemplate::TABLE => 'elis_program');
    }

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is enrolled
     * in a class instance during an import
     */
    public function test_user_sync_on_pm_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //create the system context
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        //create the site course
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        //configure the elis enrolment plugin
        $roleid = $DB->get_field('role', 'id', array(), IGNORE_MULTIPLE);
        set_config('roleid', $roleid, 'enrol_elis');

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);
        //create the associated context
        context_coursecat::instance($category->id);

        $mdlcourse = new stdClass;
        $mdlcourse->category = $category->id;
        $mdlcourse->fullname = 'testcoursefullname';
        $mdlcourse = create_course($mdlcourse);

        //associate class instance to Moodle course
        $classmoodlecourse = new classmoodlecourse(array('classid' => $class->id,
                                                         'moodlecourseid' => $mdlcourse->id));
        $classmoodlecourse->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        //validate the enrolment
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'elis',
                                                       'courseid' => $mdlcourse->id));
        $this->assertNotEquals(false, $enrolid);

        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertNotEquals(false, $mdluserid);

        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid' => $enrolid,
                                                                      'userid' => $mdluserid)));

        //validate the role assignment
        $mdlcoursecontext = context_course::instance($mdlcourse->id);
        $this->assertTrue($DB->record_exists('role_assignments', array('roleid' => $roleid,
                                                                       'contextid' => $mdlcoursecontext->id,
                                                                       'userid' => $mdluserid)));
    }
}
