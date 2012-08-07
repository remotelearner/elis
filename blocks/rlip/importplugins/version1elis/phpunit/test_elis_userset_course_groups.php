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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Validate that IP actions trigger the appropriate userset course groups functionality
 */
class elis_userset_course_groups_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        return array(
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'enrol' => 'moodle',
            'groups' => 'moodle',
            'groups_members' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_info_field' => 'moodle',
            'user_info_data' => 'moodle',
            classmoodlecourse::TABLE => 'elis_program',
            clusterassignment::TABLE => 'elis_program',
            clustertrack::TABLE => 'elis_program',
            curriculum::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_owner::TABLE => 'elis_core',
            pmclass::TABLE => 'elis_program',
            student::TABLE => 'elis_program',
            track::TABLE => 'elis_program',
            trackassignment::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            userset_profile::TABLE => 'elis_program',
            usertrack::TABLE => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        return array(
            'block_instances' => 'moodle',
            'cache_flags' => 'moodle',
            'course_sections' => 'moodle',
            'log' => 'moodle',
            coursetemplate::TABLE => 'elis_program'
        );
    }

    /**
     * Set up data that is needed for testing
     */
    private function set_up_required_data($assign_user_to_userset = true, $assign_course_to_class = true,
                                          $assign_track_to_class = true, $init_cluster_profile = false,
                                          $init_userset_field_data = true) {
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

        //set up the necessary "site course" information
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up "cluster groups"-related fields
        $field_category = new field_category(array('name' => 'Associated Group'));
        $field_category->save();

        $field = new field(array('categoryid' => $field_category->id,
                                 'shortname' => 'userset_group',
                                 'name' => 'Enable Corresponding Group',
                                 'datatype' => 'bool'));
        $field->save();

        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => CONTEXT_ELIS_USERSET));
        $field_contextlevel->save();

        $field = new field(array('categoryid' => $field_category->id,
                                 'shortname' => 'userset_groupings',
                                 'name' => 'Autoenrol users in groupings',
                                 'datatype' => 'bool'));
        $field->save();

        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => CONTEXT_ELIS_USERSET));
        $field_contextlevel->save();

        //set up the test user
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@useremail.com',
                               'country' => 'CA'));
        $user->save();

        //set up the test course description and class instance
        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $pmclass = new pmclass(array('courseid' => $course->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        //set up the test Moodle course
        set_config('enrol_plugins_enabled', 'manual');
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        if ($assign_course_to_class) {
            //assign the Moodle course to a class instance
            $classmoodlecourse = new classmoodlecourse(array('classid' => $pmclass->id,
                                                             'moodlecourseid' => $course->id));
            $classmoodlecourse->save();
        }

        //set up the test program and track
        $curriculum = new curriculum(array('idnumber' => 'testcurriculumidnumber'));
        $curriculum->save();

        $track = new track(array('curid' => $curriculum->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        if ($assign_track_to_class) {
            //assign the test track to the test class instance
            $trackassignment = new trackassignment(array('trackid' => $track->id,
                                                         'classid' => $pmclass->id,
                                                         'autoenrol' => 1));
            $trackassignment->save();
        }

        //set up the test userset
        $userset = new userset();
        $userset_data = array('name' => 'testusersetname');
        if ($init_userset_field_data) {
            $userset_data['field_userset_group'] = 1;
            $userset_data['field_userset_groupings'] = 1;
        }
        $userset->set_from_data((object)$userset_data);
        $userset->save();

        //assign the test user to the test track
        $usertrack = new usertrack(array('userid' => $user->id,
                                         'trackid' => $track->id));
        $usertrack->save();

        $clustertrack = new clustertrack(array('clusterid' => $userset->id,
                                               'trackid' => $track->id));
        $clustertrack->save();

        if ($assign_user_to_userset) {
            //assign the test user to the test userset
            $clusterassignment = new clusterassignment(array('userid' => $user->id,
                                                             'clusterid' => $userset->id,
                                                             'plugin' => 'manual'));
            $clusterassignment->save();
        }

        if ($init_cluster_profile) {
            //set up a file we can use to auto-associate users to a userset
            $field = new field(array('categoryid' => $field_category->id,
                                     'shortname' => 'autoassociate',
                                     'name' => 'autoassociate',
                                     'datatype' => 'bool'));
            $field->save();

            field_owner::ensure_field_owner_exists($field, 'moodle_profile');
            $DB->execute("UPDATE {".field_owner::TABLE."}
                          SET exclude = ?", array(pm_moodle_profile::sync_to_moodle));

            $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                               'contextlevel' => CONTEXT_ELIS_USER));
            $field_contextlevel->save();

            //the associated Moodle user profile field
            require_once($CFG->dirroot.'/user/profile/definelib.php');
            require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

            $profile_define_checkbox = new profile_define_checkbox();
            $data = new stdClass;
            $data->datatype = 'checkbox';
            $data->categoryid = 99999;
            $data->shortname = 'autoassociate';
            $data->name = 'autoassociate';
            $profile_define_checkbox->define_save($data);

            //the "cluster-profile" association
            $userset_profile = new userset_profile(array('clusterid' => $userset->id,
                                                         'fieldid' => 1,
                                                         'value' => true));
            $userset_profile->save();
        }

        //enrol the user in the Moodle course
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $roleid = create_role('testrole', 'testrole', 'testrole');
        enrol_try_internal_enrol($course->id, $mdluserid, $roleid);

        //set up the necessary config data
        set_config('userset_groups', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        //validate setup
        $this->assertEquals(0, $DB->count_records('groups'));
    }

    /**
     * Validate that our constant expect end result is reached
     */
    private function validate_end_result() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/grouplib.php');

        //validate group creation
        $groupid = groups_get_group_by_name(2, 'testusersetname');
        $this->assertNotEquals(false, $groupid);

        //validate user-group assignment
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertTrue(groups_is_member($groupid, $userid));
    }

    /**
     * Validate that assigning users to user sets triggers group functionality
     */
    public function test_user_userset_assignment_triggers_group_setup() {
        $this->set_up_required_data(false, true);

        //run the user-userset assignment create action
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_enrolment_create($record, 'bogus', 'testusersetname');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning class instances to Moodle courses triggers
     * group functionality
     */
    public function test_class_instance_moodle_course_assignment_triggers_group_setup() {
        $this->set_up_required_data(true, false);

        //run the class intance-Moodle course assignment create action
        $record = new stdClass;
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->link = 'testcourseshortname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_update($record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning tracks to class instances triggers group
     * functionality
     */
    public function test_track_class_instance_assignment_triggers_group_setup() {
        $this->set_up_required_data(true, true, false);

        //run the track-class instance assignment create action
        $record = new stdClass;
        $record->context = 'class';
        $record->idnumber = 'testclassidnumber';
        $record->track = 'testtrackidnumber';
        $record->autoenrol = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_update($record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning user sets to tracks triggers group functionality
     */
    public function test_userset_track_instance_assignment_triggers_group_setup() {
        $this->markTestIncomplete('Implement unit test if / when we allow assignment of '.
                                   'user sets to tracks');
    }

    /**
     * Validate that updating users triggers group functionality
     */
    public function test_user_update_triggers_group_setup() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->set_up_required_data(false, true, true, true);

        //run the user update action
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->autoassociate = 1;

        $temp = new user();
        $temp->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', $record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that updating user sets triggers group functionality
     */
    public function test_userset_update_triggers_group_setup() {

        $this->set_up_required_data(true, true, true, false, false);

        //run the userset update action
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->userset_group = 1;
        $record->userset_groupings = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', $record, 'bogus');

        $this->validate_end_result();
    }
}
