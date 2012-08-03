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
//TODO: move to a more general location
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/rlip_mock_provider.class.php');

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_mockenrolment extends rlip_importprovider_mock {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class for validating that field mappings work correctly during the ELIS
 * enrolment import
 */
class elis_enrolment_field_mappings_test extends elis_database_test {
    //store the mapping we will use
    private $mapping = array('action' => 'customaction',
                             'context' => 'customcontext',
                             'user_idnumber' => 'customuser_idnumber',
                             'user_username' => 'customuser_username',
                             'user_email' => 'customuser_email',
                             'enrolmenttime' => 'customenrolmenttime',
                             'assigntime' => 'customassigntime',
                             'completetime' => 'customcompletetime',
                             'completestatusid' => 'customcompletestatusid',
                             'grade' => 'customgrade',
                             'credits' => 'customcredits',
                             'locked' => 'customlocked',
                             'role' => 'customrole');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        return array('context' => 'moodle',
                     'role' => 'moodle',
                     'role_assignments' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'user' => 'moodle',
                     RLIP_LOG_TABLE => 'block_rlip',
                     RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
                     clusterassignment::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     curriculum::TABLE => 'elis_program',
                     curriculumstudent::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     instructor::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     trackassignment::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program',
                     userset::TABLE => 'elis_program',
                     usertrack::TABLE => 'elis_program',
                     waitlist::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        return array('cache_flags' => 'moodle',
                     coursetemplate::TABLE => 'elis_program',
                     student_grade::TABLE => 'elis_program');
    }

    /**
     * Initialize the db records needed to represent the field mapping
     */
    private function init_mapping() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');

        foreach ($this->mapping as $standardfieldname => $customfieldname) {
            $mapping = new stdClass;
            $mapping->entitytype = 'enrolment';
            $mapping->standardfieldname = $standardfieldname;
            $mapping->customfieldname = $customfieldname;

            $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);
        }
    }

    /**
     * Create a test user record
     *
     * @return int The id of the user record
     */
    private function create_test_user() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        return $user->id;
    }

    /**
     * Helper function that runs the user import for a sample enrolment
     *
     * @param array $data Import data to use
     */
    private function run_enrolment_import($data) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $provider = new rlip_importprovider_mockenrolment($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Validate that mappings are applied during the program enrolment create action
     */
    public function test_mapping_applied_during_program_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculumstudent.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        //run the program enrolment create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'curriculum_testprogramidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array('curriculumid' => $program->id,
                                                                             'userid' => $userid)));
    }

    /**
     * Validate that mappings are applied during the program enrolment delete action
     */
    public function test_mapping_applied_during_program_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculumstudent.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $curriculumstudent = new curriculumstudent(array('curriculumid' => $program->id,
                                                         'userid' => $userid));
        $curriculumstudent->save();

        //run the program enrolment delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'curriculum_testprogramidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(curriculumstudent::TABLE));
    }

    /**
     * Validate that mappings are applied during the track enrolment create action
     */
    public function test_mapping_applied_during_track_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the track enrolment create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'track_testtrackidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(usertrack::TABLE, array('trackid' => $track->id,
                                                                     'userid' => $userid)));
    }

    /**
     * Validate that mappings are applied during the track enrolment delete action
     */
    public function test_mapping_applied_during_track_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $usertrack = new usertrack(array('trackid' => $track->id,
                                         'userid' => $userid));
        $usertrack->save();

        //run the track enrolment delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'track_testtrackidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(usertrack::TABLE));
    }

    /**
     * Validate that mappings are applied during the user set enrolment create action
     */
    public function test_mapping_applied_during_userset_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //run the user set enrolment create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'cluster_testusersetname';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('clusterid' => $userset->id,
                                                                             'userid' => $userid)));
    }

    /**
     * Validate that mappings are applied during the user set enrolment delete action
     */
    public function test_mapping_applied_during_userset_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $clusterassignment = new clusterassignment(array('clusterid' => $userset->id,
                                                         'userid' => $userid,
                                                         'plugin' => 'manual'));
        $clusterassignment->save();

        //run the user set enrolment delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'cluster_testusersetname';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(clusterassignment::TABLE));
    }

    /**
     * Validate that mappings are applied during the student enrolment create action
     */
    public function test_mapping_applied_during_student_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        //run the student enrolment create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customenrolmenttime = 'Jan/01/2012';
        $record->customcompletetime = 'Jan/01/2012';
        $record->customcompletestatusid = student::STUSTATUS_PASSED;
        $record->customgrade = '50';
        $record->customcredits = '1';
        $record->customlocked = '1';
        $record->customrole = 'student';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(student::TABLE, array('classid' => $pmclass->id,
                                                                   'userid' => $userid,
                                                                   'enrolmenttime' => mktime(0, 0, 0, 1, 1, 2012),
                                                                   'completetime' => mktime(0, 0, 0, 1, 1, 2012),
                                                                   'completestatusid' => student::STUSTATUS_PASSED,
                                                                   'grade' => 50,
                                                                   'credits' => 1,
                                                                   'locked' => 1)));
    }

    /**
     * Validate that mappings are applied during the student enrolment update action
     */
    public function test_mapping_applied_during_student_enrolment_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $student = new student(array('classid' => $pmclass->id,
                                     'userid' => $userid));
        $student->save();

        //run the student enrolment update action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customenrolmenttime = 'Jan/02/2012';
        $record->customcompletetime = 'Jan/02/2012';
        $record->customcompletestatusid = student::STUSTATUS_FAILED;
        $record->customgrade = '100';
        $record->customcredits = '2';
        $record->customlocked = '1';
        $record->customrole = 'student';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(student::TABLE, array('classid' => $pmclass->id,
                                                                   'userid' => $userid,
                                                                   'enrolmenttime' => mktime(0, 0, 0, 1, 2, 2012),
                                                                   'completetime' => mktime(0, 0, 0, 1, 2, 2012),
                                                                   'completestatusid' => student::STUSTATUS_FAILED,
                                                                   'grade' => 100,
                                                                   'credits' => 2,
                                                                   'locked' => 1)));
    }

    /**
     * Validate that mappings are applied during the student enrolment delete action
     */
    public function test_mapping_applied_during_student_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/student.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $student = new student(array('classid' => $pmclass->id,
                                     'userid' => $userid));
        $student->save();

        //run the student enrolment delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'student';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(student::TABLE));
    }

    /**
     * Validate that mappings are applied during the instructor enrolment create action
     */
    public function test_mapping_applied_during_instructor_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        //run the instructor enrolment create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customassigntime = 'Jan/01/2012';
        $record->customcompletetime = 'Jan/01/2012';
        $record->customrole = 'instructor';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('classid' => $pmclass->id,
                                                                      'userid' => $userid,
                                                                      'assigntime' => mktime(0, 0, 0, 1, 1, 2012),
                                                                      'completetime' => mktime(0, 0, 0, 1, 1, 2012))));
    }

    /**
     * Validate that mappings are applied during the instructor enrolment update action
     */
    public function test_mapping_applied_during_instructor_enrolment_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $instructor = new instructor(array('classid' => $pmclass->id,
                                           'userid' => $userid));
        $instructor->save();

        //run the instructor enrolment update action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customassigntime = 'Jan/02/2012';
        $record->customcompletetime = 'Jan/02/2012';
        $record->customrole = 'instructor';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertTrue($DB->record_exists(instructor::TABLE, array('classid' => $pmclass->id,
                                                                      'userid' => $userid,
                                                                      'assigntime' => mktime(0, 0, 0, 1, 2, 2012),
                                                                      'completetime' => mktime(0, 0, 0, 1, 2, 2012))));
    }

    /**
     * Validate that mappings are applied during the instructor enrolment delete action
     */
    public function test_mapping_applied_during_instructor_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/instructor.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $instructor = new instructor(array('classid' => $pmclass->id,
                                           'userid' => $userid));
        $instructor->save();

        //run the instructor enrolment delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'instructor';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(instructor::TABLE));
    }

    /**
     * Validate that mappings are applied during the user enrolment (role assignment) create action
     */
    public function test_mapping_applied_during_user_enrolment_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $roleid = create_role('testrolename', 'testroleshortname', 'testroledescription');

        //run the user enrolment create actions
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'user_testuseridnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'testroleshortname';

        $this->run_enrolment_import((array)$record);

        //validation
        $instance = context_user::instance(1);

        $this->assertTrue($DB->record_exists('role_assignments', array('roleid' => $roleid,
                                                                       'contextid' => $instance->id,
                                                                       'userid' => 1)));
    }

    /**
     * Validate that mappings are applied during the user enrolment (role assignment) delete action
     */
    public function test_mapping_applied_during_user_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->init_mapping();

        $userid = $this->create_test_user();

        $roleid = create_role('testrolename', 'testroleshortname', 'testroledescription');

        $instance = context_user::instance(1);
        role_assign($roleid, 1, $instance->id);

        //run the user enrolment delete actions
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'user_testuseridnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'testroleshortname';

        $this->run_enrolment_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records('role_assignments'));
    }
}
