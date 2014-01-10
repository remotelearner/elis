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
 * Class for testing track-class association creation during class instance create and update actions.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_class_associate_track_testcase extends rlip_elis_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        return array(course::TABLE => 'local_elisprogram',
                     curriculum::TABLE => 'local_elisprogram',
                     curriculumcourse::TABLE => 'local_elisprogram',
                     field::TABLE => 'local_eliscore',
                     pmclass::TABLE => 'local_elisprogram',
                     track::TABLE => 'local_elisprogram',
                     trackassignment::TABLE => 'local_elisprogram');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        return array('context' => 'moodle',
                     coursetemplate::TABLE => 'local_elisprogram',
                     student::TABLE => 'local_elisprogram');
    }

    /**
     * Data provider for testing various scenarios related to the autoenrol flag
     *
     * @return array Data in the expected format
     */
    public function autoenrol_provider() {
        return array(
                array(null, 0),
                array(0, 0),
                array(1, 1)
        );
    }

    /**
     * Validate that track-class associations can be created during a class instance
     * create action
     *
     * @param mixed $autoenrol The appropriate autoenrol value specified
     * @param int $dbautoenrol The value expected to be set in the db for autoenrol
     * @dataProvider autoenrol_provider
     */
    public function test_associate_track_during_class_create($autoenrol, $dbautoenrol) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        // Create the course description.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        // Create the curriculum / program.
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        // Associate the course description to the program.
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $curriculum->id, 'courseid' => $course->id));
        $curriculumcourse->save();

        // Create the track.
        $track = new track(array('curid' => $curriculum->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Run the class instance create action.
        $record = new stdClass;
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        if ($autoenrol !== null) {
            $record->autoenrol = $autoenrol;
        }

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_create($record, 'bogus');

        // Validation.
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));
        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array(
            'trackid' => $track->id,
            'classid' => $classid,
            'autoenrol' => $dbautoenrol
        )));
    }

    /**
     * Validate that track-class associations can be created during a class instance
     * update action
     *
     * @param mixed $autoenrol The appropriate autoenrol value specified
     * @param int $dbautoenrol The value expected to be set in the db for autoenrol
     * @dataProvider autoenrol_provider
     */
    public function test_associate_track_during_class_update($autoenrol, $dbautoenrol) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        // Create the course description.
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        // Create the class instance.
        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        // Create the curriculum / program.
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        // Associate the course description to the program.
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $curriculum->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        // Create the track.
        $track = new track(array('curid' => $curriculum->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Run the class instance update action.
        $record = new stdClass;
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        if ($autoenrol !== null) {
            $record->autoenrol = $autoenrol;
        }

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_update($record, 'bogus');

        // Validation.
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));
        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array(
            'trackid' => $track->id,
            'classid' => $classid,
            'autoenrol' => $dbautoenrol
        )));
    }
}