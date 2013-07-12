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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/tests/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');

/**
 * Class for validating that field mappings work correctly during the ELIS enrolment import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_enrolment_field_mappings_testcase extends rlip_elis_test {
    /**
     * @var array Store the mapping we will use.
     */
    private $mapping = array(
        'action' => 'customaction',
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
        'role' => 'customrole'
    );

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

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
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

        $provider = new rlipimport_version1elis_importprovider_mockenrolment($data);

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

        // Run the program enrolment create action.
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'curriculum_testprogramidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array('curriculumid' => $program->id, 'userid' => $userid)));
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

        $curriculumstudent = new curriculumstudent(array('curriculumid' => $program->id, 'userid' => $userid));
        $curriculumstudent->save();

        // Run the program enrolment delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'curriculum_testprogramidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
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

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        // Run the track enrolment create action.
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'track_testtrackidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
        $this->assertTrue($DB->record_exists(usertrack::TABLE, array('trackid' => $track->id, 'userid' => $userid)));
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

        $track = new track(array('curid' => $program->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $usertrack = new usertrack(array('trackid' => $track->id, 'userid' => $userid));
        $usertrack->save();

        // Run the track enrolment delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'track_testtrackidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
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

        // Run the user set enrolment create action.
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'cluster_testusersetname';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('clusterid' => $userset->id, 'userid' => $userid)));
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

        $clusterassignment = new clusterassignment(array(
            'clusterid' => $userset->id,
            'userid' => $userid,
            'plugin' => 'manual'
        ));
        $clusterassignment->save();

        // Run the user set enrolment delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'cluster_testusersetname';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';

        $this->run_enrolment_import((array)$record);

        // Validation.
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        // Run the student enrolment create action.
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

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'classid' => $pmclass->id,
            'userid' => $userid,
            'enrolmenttime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completestatusid' => student::STUSTATUS_PASSED,
            'grade' => 50,
            'credits' => 1,
            'locked' => 1
        )));
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $student = new student(array('classid' => $pmclass->id, 'userid' => $userid));
        $student->save();

        // Run the student enrolment update action.
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

        // Validation.
        $this->assertTrue($DB->record_exists(student::TABLE, array(
            'classid' => $pmclass->id,
            'userid' => $userid,
            'enrolmenttime' => rlip_timestamp(0, 0, 0, 1, 2, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 1, 2, 2012),
            'completestatusid' => student::STUSTATUS_FAILED,
            'grade' => 100,
            'credits' => 2,
            'locked' => 1
        )));
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array(
            'courseid' => $course->id,
            'idnumber' => 'testclassidnumber'
        ));
        $pmclass->save();

        $student = new student(array(
            'classid' => $pmclass->id,
            'userid' => $userid
        ));
        $student->save();

        // Run the student enrolment delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'student';

        $this->run_enrolment_import((array)$record);

        // Validation.
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        // Run the instructor enrolment create action.
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

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'classid' => $pmclass->id,
            'userid' => $userid,
            'assigntime' => rlip_timestamp(0, 0, 0, 1, 1, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 1, 1, 2012)
        )));
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $instructor = new instructor(array('classid' => $pmclass->id, 'userid' => $userid));
        $instructor->save();

        // Run the instructor enrolment update action.
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

        // Validation.
        $this->assertTrue($DB->record_exists(instructor::TABLE, array(
            'classid' => $pmclass->id,
            'userid' => $userid,
            'assigntime' => rlip_timestamp(0, 0, 0, 1, 2, 2012),
            'completetime' => rlip_timestamp(0, 0, 0, 1, 2, 2012)
        )));
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

        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $instructor = new instructor(array('classid' => $pmclass->id, 'userid' => $userid));
        $instructor->save();

        // Run the instructor enrolment delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'class_testclassidnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'instructor';

        $this->run_enrolment_import((array)$record);

        // Validation.
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

        // Run the user enrolment create actions.
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'user_testuseridnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'testroleshortname';

        $this->run_enrolment_import((array)$record);

        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));

        // Validation.
        $instance = context_user::instance($userid);

        $this->assertTrue($DB->record_exists('role_assignments', array(
            'roleid' => $roleid,
            'contextid' => $instance->id,
            'userid' => $userid
        )));
    }

    /**
     * Validate that mappings are applied during the user enrolment (role assignment) delete action
     */
    public function test_mapping_applied_during_user_enrolment_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        $this->init_mapping();

        $userid = $this->create_test_user();
        $user = new user($userid);
        $user->load();
        $muser = $user->get_moodleuser();

        $roleid = create_role('testrolename', 'testroleshortname', 'testroledescription');

        $instance = context_user::instance($muser->id);
        role_assign($roleid, $muser->id, $instance->id);

        // Run the user enrolment delete actions.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'user_testuseridnumber';
        $record->customuser_username = 'testuserusername';
        $record->customuser_email = 'test@useremail.com';
        $record->customuser_idnumber = 'testuseridnumber';
        $record->customrole = 'testroleshortname';

        $this->run_enrolment_import((array)$record);

        // Validation.
        $this->assertEquals(0, $DB->count_records('role_assignments'));

    }
}
