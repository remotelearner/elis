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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Validate that IP actions trigger the appropriate userset course groups functionality.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_userset_course_groups_testcase extends rlip_elis_test {

    /**
     * Set up data that is needed for testing
     */
    private function set_up_required_data($assignusertouserset = true, $assigncoursetoclass = true,
                                          $assigntracktoclass = true, $initclusterprofile = false,
                                          $initusersetfielddata = true) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        $fieldcategoryid = $DB->get_field(field_category::TABLE, 'id', array('name' => 'Associated Group'));
        $this->assertNotEquals(false, $fieldcategoryid);
        $fieldcategory = new field_category($fieldcategoryid);
        $fieldcategory->load();

        // Set up the test user.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'test@useremail.com',
            'country' => 'CA'
        ));
        $user->save();

        // Set up the test course description and class instance.
        $course = new course(array(
            'name' => 'testcoursename',
            'idnumber' => 'testcourseidnumber',
            'syllabus' => ''
        ));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id, 'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        // Set up the test Moodle course.
        set_config('enrol_plugins_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        if ($assigncoursetoclass) {
            // Assign the Moodle course to a class instance.
            $classmoodlecourse = new classmoodlecourse(array('classid' => $pmclass->id, 'moodlecourseid' => $course->id));
            $classmoodlecourse->save();
        }

        // Set up the test program and track.
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        $track = new track(array('curid' => $curriculum->id, 'idnumber' => 'testtrackidnumber'));
        $track->save();

        if ($assigntracktoclass) {
            // Assign the test track to the test class instance.
            $trackassignment = new trackassignment(array('trackid' => $track->id, 'classid' => $pmclass->id, 'autoenrol' => 1));
            $trackassignment->save();
        }

        // Set up the test userset.
        $userset = new userset();
        $usersetdata = array('name' => 'testusersetname');
        if ($initusersetfielddata) {
            $usersetdata['field_userset_group'] = 1;
            $usersetdata['field_userset_groupings'] = 1;
        }
        $userset->set_from_data((object)$usersetdata);
        $userset->save();

        // Assign the test user to the test track.
        $usertrack = new usertrack(array('userid' => $user->id, 'trackid' => $track->id));
        $usertrack->save();

        $clustertrack = new clustertrack(array('clusterid' => $userset->id, 'trackid' => $track->id));
        $clustertrack->save();

        if ($assignusertouserset) {
            // Assign the test user to the test userset.
            $clusterassignment = new clusterassignment(array(
                'userid' => $user->id,
                'clusterid' => $userset->id,
                'plugin' => 'manual'
            ));
            $clusterassignment->save();
        }

        if ($initclusterprofile) {
            // Set up a file we can use to auto-associate users to a userset.
            $field = new field(array(
                'categoryid' => $fieldcategory->id,
                'shortname' => 'autoassociate',
                'name' => 'autoassociate',
                'datatype' => 'bool'
            ));
            $field->save();

            field_owner::ensure_field_owner_exists($field, 'moodle_profile');
            $DB->execute("UPDATE {".field_owner::TABLE."} SET exclude = ?", array(pm_moodle_profile::sync_to_moodle));

            $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USER));
            $fieldcontextlevel->save();

            // The associated Moodle user profile field.
            require_once($CFG->dirroot.'/user/profile/definelib.php');
            require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

            $profiledefinecheckbox = new profile_define_checkbox();
            $data = new stdClass;
            $data->datatype = 'checkbox';
            $data->categoryid = 99999;
            $data->shortname = 'autoassociate';
            $data->name = 'autoassociate';
            $profiledefinecheckbox->define_save($data);

            // The "cluster-profile" association.
            $usersetprofile = new userset_profile(array(
                'clusterid' => $userset->id,
                'fieldid' => $field->id,
                'value' => true
            ));
            $usersetprofile->save();
        }

        // Enrol the user in the Moodle course.
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $roleid = create_role('testrole', 'testrole', 'testrole');
        enrol_try_internal_enrol($course->id, $mdluserid, $roleid);

        // Set up the necessary config data.
        set_config('userset_groups', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        // Validate setup.
        $this->assertEquals(0, $DB->count_records('groups'));
    }

    /**
     * Validate that our constant expect end result is reached
     */
    private function validate_end_result() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/grouplib.php');

        // Validate group creation.
        $groupid = groups_get_group_by_name(2, 'testusersetname');
        $this->assertNotEquals(false, $groupid);

        // Validate user-group assignment.
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertTrue(groups_is_member($groupid, $userid));
    }

    /**
     * Validate that assigning users to user sets triggers group functionality
     */
    public function test_user_userset_assignment_triggers_group_setup() {
        $this->set_up_required_data(false, true);

        // Run the user-userset assignment create action.
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_enrolment_create($record, 'bogus', 'testusersetname');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning class instances to Moodle courses triggers
     * group functionality
     */
    public function test_class_instance_moodle_course_assignment_triggers_group_setup() {
        $this->set_up_required_data(true, false);

        // Run the class intance-Moodle course assignment create action.
        $record = new stdClass;
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->link = 'testcourseshortname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_update($record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning tracks to class instances triggers group
     * functionality
     */
    public function test_track_class_instance_assignment_triggers_group_setup() {
        $this->set_up_required_data(true, true, false);

        // Run the track-class instance assignment create action.
        $record = new stdClass;
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        $record->autoenrol = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->class_update($record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning user sets to tracks triggers group functionality
     */
    public function test_userset_track_instance_assignment_triggers_group_setup() {
        $this->markTestIncomplete('Implement unit test if / when we allow assignment of user sets to tracks');
    }

    /**
     * Validate that updating users triggers group functionality
     */
    public function test_user_update_triggers_group_setup() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->set_up_required_data(false, true, true, true);

        // Run the user update action.
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->autoassociate = 1;

        $temp = new user;
        $temp->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that updating user sets triggers group functionality
     */
    public function test_userset_update_triggers_group_setup() {

        $this->set_up_required_data(true, true, true, false, false);

        // Run the userset update action.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->userset_group = 1;
        $record->userset_groupings = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', $record, 'bogus');

        $this->validate_end_result();
    }
}