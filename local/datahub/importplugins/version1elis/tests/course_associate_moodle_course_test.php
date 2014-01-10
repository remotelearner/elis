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
 * Class for testing course description-moodle template course association creation during course description create and update
 * actions.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_course_associate_moodle_course_testcase extends rlip_elis_test {

    /**
     * Validate that course description-moodle template course associations
     * can be created during a course description create action
     */
    public function test_associate_moodle_course_during_course_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $templatecourse = new stdClass;
        $templatecourse->category = $coursecategory->id;
        $templatecourse->shortname = 'testcourseshortname';
        $templatecourse->fullname = 'testcoursefullname';
        $templatecourse = create_course($templatecourse);

        // Run the course description create action.
        $record = new stdClass;
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->link = $templatecourse->shortname;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_create($record, 'bogus');

        // Validation.
        $pmcourseid = $DB->get_field(course::TABLE, 'id', array('idnumber' => 'testcourseidnumber'));
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array(
            'courseid' => $pmcourseid,
            'location' => $templatecourse->id,
            'templateclass' => 'moodlecourseurl'
        )));
    }

    /**
     * Validate that course description-moodle template course associations
     * can be created during a course description update action
     */
    public function test_associate_moodle_course_during_course_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $templatecourse = new stdClass;
        $templatecourse->category = $coursecategory->id;
        $templatecourse->shortname = 'testcourseshortname';
        $templatecourse->fullname = 'testcoursefullname';
        $templatecourse = create_course($templatecourse);

        // Create the course description.
        $pmcourse = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $pmcourse->save();

        // Run the course description update action.
        $record = new stdClass;
        $record->name = 'testcoursename';
        $record->idnumber = 'testcourseidnumber';
        $record->link = $templatecourse->shortname;

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->course_update($record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array(
            'courseid' => $pmcourse->id,
            'location' => $templatecourse->id,
            'templateclass' => 'moodlecourseurl'
        )));
    }
}
