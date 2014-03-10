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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for testing class instance-moodle course association creation during class instance create and update actions.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_class_associate_moodle_course_testcase extends rlip_elis_test {

    /**
     * Data provider for testing various linking scenarios
     *
     * @return array The necessary data for testing
     */
    public function link_course_provider() {
        // Use CD template course to auto-create template course.
        return array(
            array('auto'),
            // Link to a specific Moodle course.
            array('testcourseshortname')
        );
    }

    /**
     * Validate that class instance-moodle course associations
     * can be created during a class instance create action
     *
     * @param string $link The link attribute to use in the import, or 'auto' to auto-create
     *                     from template
     * @dataProvider link_course_provider
     */
    public function test_associate_moodle_course_during_class_create($link) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));

        // Make sure $USER is set up for backup/restore.
        $USER = $DB->get_record_select('user', "username != 'guest' and DELETED = 0", array(), '*', IGNORE_MULTIPLE);
        $GLOBAL['USER'] = $USER;

        // Need the moodle/backup:backupcourse capability.
        $guestroleid = create_role('guestrole', 'guestrole', 'guestrole');
        set_config('guestroleid', $guestroleid);
        set_config('siteguest', '');

        $systemcontext = context_system::instance();
        $roleid = create_role('testrole', 'testrole', 'testrole');
        assign_capability('moodle/backup:backupcourse', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $USER->id, $systemcontext->id);

        set_config('siteadmins', $USER->id);

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        context_coursecat::instance($coursecategory->id);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        // Need this for the 'auto' case, at the very least.
        $coursetemplate = new coursetemplate(array(
            'courseid' => $course->id,
            'location' => $moodlecourse->id,
            'templateclass' => 'moodlecourseurl'
        ));
        $coursetemplate->save();

        // Run the class instance create action.
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        $record->link = $link;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_create($record, 'bogus');

        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));

        // Validation.
        if ($record->link == 'auto') {
            $moodlecourseid = $moodlecourse->id + 1;
        } else {
            $moodlecourseid = $moodlecourse->id;
        }
        $dbautocreated = $record->link == 'auto' ? 1 : 0;
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array(
            'classid' => $classid,
            'moodlecourseid' => $moodlecourseid,
            'enroltype' => 0,
            'enrolplugin' => 'crlm',
            'autocreated' => $dbautocreated
        )));

        ini_set('max_execution_time', '0');
    }

    /**
     * Validate that class instance-moodle course associations can be created during a class instance update action.
     *
     * @param string $link The link attribute to use in the import, or 'auto' to auto-create from template.
     * @dataProvider link_course_provider
     */
    public function test_associate_moodle_course_during_class_update($link) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        // Make sure $USER is set up for backup/restore.
        $USER->id = $DB->get_field_select('user', 'id', "username != 'guest' AND deleted = 0", array(), IGNORE_MULTIPLE);

        // Need the moodle/backup:backupcourse capability.
        $guestroleid = create_role('guestrole', 'guestrole', 'guestrole');
        set_config('guestroleid', $guestroleid);
        set_config('siteguest', '');

        $systemcontext = context_system::instance();
        $roleid = create_role('testrole', 'testrole', 'testrole');
        assign_capability('moodle/backup:backupcourse', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $USER->id, $systemcontext->id);

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename', 'idnumber' => 'testcourseidnumber', 'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $class->save();

        // Need this for the 'auto' case, at the very least.
        $coursetemplate = new coursetemplate(array(
            'courseid' => $course->id,
            'location' => $moodlecourse->id,
            'templateclass' => 'moodlecourseurl'
        ));
        $coursetemplate->save();

        // Run the class instance create action.
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        $record->link = $link;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_update($record, 'bogus');

        // Validation.
        if ($record->link == 'auto') {
            $moodlecourseid = $moodlecourse->id + 1;
        } else {
            $moodlecourseid = $moodlecourse->id;
        }
        $dbautocreated = $record->link == 'auto' ? 1 : 0;
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array(
            'classid' => $class->id,
            'moodlecourseid' => $moodlecourseid,
            'enroltype' => 0,
            'enrolplugin' => 'crlm',
            'autocreated' => $dbautocreated
        )));

        ini_set('max_execution_time', '0');
    }
}