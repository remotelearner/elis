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
 * Class for testing track-class association creation during class instance
 * create and update actions
 */
class elis_class_associate_track_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        return array(course::TABLE => 'elis_program',
                     curriculum::TABLE => 'elis_program',
                     curriculumcourse::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     pmclass::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     trackassignment::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        return array('context' => 'moodle',
                     coursetemplate::TABLE => 'elis_program',
                     student::TABLE => 'elis_program');
    }

    /**
     * Data provider for testing various scenarios related to the autoenrol flag
     *
     * @return array Data in the expected format
     */
    function autoenrol_provider() {
        return array(array(NULL, 0),
                     array(0, 0),
                     array(1, 1));
    }

    /**
     * Validate that track-class associations can be created during a class instance
     * create action
     *
     * @param mixed $autoenrol The appropriate autoenrol value specified 
     * @param int $db_autoenrol The value expected to be set in the db for autoenrol
     * @dataProvider autoenrol_provider
     */
    function test_associate_track_during_class_create($autoenrol, $db_autoenrol) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        //create the course description
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        //create the curriculum / program
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        //associate the course description to the program
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $curriculum->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        //create the track
        $track = new track(array('curid' => $curriculum->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the class instance create action
        $record = new stdClass;
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        if ($autoenrol !== NULL) {
            $record->autoenrol = $autoenrol;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_create($record, 'bogus');

        //validation
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));
        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array('trackid' => $track->id,
                                                                           'classid' => $classid,
                                                                           'autoenrol' => $db_autoenrol)));
    }

    /**
     * Validate that track-class associations can be created during a class instance
     * update action
     *
     * @param mixed $autoenrol The appropriate autoenrol value specified 
     * @param int $db_autoenrol The value expected to be set in the db for autoenrol
     * @dataProvider autoenrol_provider
     */
    function test_associate_track_during_class_update($autoenrol, $db_autoenrol) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));

        //create the course description
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        //create the class instance
        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        //create the curriculum / program
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        //associate the course description to the program
        $curriculumcourse = new curriculumcourse(array('curriculumid' => $curriculum->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        //create the track
        $track = new track(array('curid' => $curriculum->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the class instance update action
        $record = new stdClass;
        $record->assignment = 'testcourseidnumber';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        if ($autoenrol !== NULL) {
            $record->autoenrol = $autoenrol;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_update($record, 'bogus');

        //validation
        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));
        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array('trackid' => $track->id,
                                                                           'classid' => $classid,
                                                                           'autoenrol' => $db_autoenrol)));
    }
}