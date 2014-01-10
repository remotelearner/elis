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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that ELIS / PM user actions propagate the appropriate enrolments over to Moodle.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_enrolment_sync_testcase extends rlip_elis_test {

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is enrolled in a class instance during an import.
     */
    public function test_user_sync_on_pm_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Configure the elis enrolment plugin.
        $roleid = $DB->get_field('role', 'id', array(), IGNORE_MULTIPLE);
        set_config('roleid', $roleid, 'enrol_elis');

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);
        // Create the associated context.
        context_coursecat::instance($category->id);

        $mdlcourse = new stdClass;
        $mdlcourse->category = $category->id;
        $mdlcourse->fullname = 'testcoursefullname';
        $mdlcourse = create_course($mdlcourse);

        // Associate class instance to Moodle course.
        $classmoodlecourse = new classmoodlecourse(array('classid' => $class->id, 'moodlecourseid' => $mdlcourse->id));
        $classmoodlecourse->save();

        // Run the enrolment create action.
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        // Validate the enrolment.
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'elis', 'courseid' => $mdlcourse->id));
        $this->assertNotEquals(false, $enrolid);

        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertNotEquals(false, $mdluserid);

        $this->assertTrue($DB->record_exists('user_enrolments', array(
            'enrolid' => $enrolid,
            'userid' => $mdluserid
        )));

        // Validate the role assignment.
        $mdlcoursecontext = context_course::instance($mdlcourse->id);
        $this->assertTrue($DB->record_exists('role_assignments', array(
            'roleid' => $roleid,
            'contextid' => $mdlcoursecontext->id,
            'userid' => $mdluserid
        )));
    }
}