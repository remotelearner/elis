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
 * Validate that IP actions trigger the appropriate userset site groups functionality, including groupings functionality.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_userset_site_groups_testcase extends rlip_elis_test {

    /**
     * Set up data that is needed for testing
     *
     * @param boolean $setcustomfielddata Specify whether the userset's custom fields should be set
     * @param boolean $assignuser Specify whether the user should be directly assigned to the user set
     * @param boolean $setautoassociatefields Specity whether we should set up fields that allow userset autoassociation
     */
    private function set_up_required_data($setcustomfielddata = true, $assignuser = true, $setautoassociatefields = false) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

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

        $user->synchronize_moodle_user();

        // We need a system-level role assignment.
        $roleid = create_role('systemrole', 'systemrole', 'systemrole');
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $context = context_system::instance();

        role_assign($roleid, $userid, $context->id);

        // Set up the userset.
        $userset = new userset();
        $usersetdata = array('name' => 'testusersetname');
        if ($setcustomfielddata) {
            $usersetdata['field_userset_group'] = 1;
            $usersetdata['field_userset_groupings'] = 1;
        }
        $userset->set_from_data((object)$usersetdata);
        $userset->save();

        if ($setautoassociatefields) {
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
                'fieldid' => 1,
                'value' => 1
            ));
            $usersetprofile->save();
        }

        if ($assignuser) {
            // Assign the user to the user set.
            $clusterassignment = new clusterassignment(array(
                'clusterid' => $userset->id,
                'userid' => $user->id,
                'plugin' => 'manual'
            ));
            $clusterassignment->save();
        }
    }

    /**
     * Validate that our constant expect end result is reached
     */
    private function validate_end_result() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/grouplib.php');

        // Validate group creation.
        $groupid = groups_get_group_by_name(SITEID, 'testusersetname');
        $this->assertNotEquals(false, $groupid);

        // Validate user-group assignment.
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertTrue(groups_is_member($groupid, $userid));

        // Validate grouping creation.
        $groupingid = groups_get_grouping_by_name(SITEID, 'testusersetname');
        $this->assertNotEquals(false, $groupingid);

        // Validate group-grouping assignment.
        $this->assertTrue($DB->record_exists('groupings_groups', array('groupingid' => $groupingid, 'groupid' => $groupid)));
    }

    /**
     * Validate that userset updates trigger group and grouping functionality
     */
    public function test_userset_update_triggers_group_and_grouping_setup() {
        global $DB;

        $this->set_up_required_data(false, true);

        // Set up the necessary config data.
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        // Validate setup.
        $this->assertEquals(0, $DB->count_records('groups'));

        // Run the user set update action.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->userset_group = '1';
        $record->userset_groupings = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        // Need to call process_record so that custom field mappings are handled.
        $importplugin->process_record('course', $record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning users to usersets triggers group and grouping
     * functionality
     */
    public function test_userset_assignment_triggers_group_and_grouping_setup() {
        $this->set_up_required_data(true, false);

        // Set up the necessary config data.
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        // Run the user set enrolment action.
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_enrolment_create($record, 'bogus', 'testusersetname');

        $this->validate_end_result();
    }

    /**
     * Validate that updating a user that will be auto-assigned to a user set
     * triggers group and grouping functionality
     */
    public function test_elis_user_update_triggers_group_and_grouping_setup() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->set_up_required_data(true, false, true);

        // Set up the necessary config data.
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        // Run the user update action.
        $record = new stdClass;
        $record->action = 'update';
        $record->idnumber = 'testuseridnumber';
        // TODO: remove the next two fields once we can updated based on just idnumber.
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->autoassociate = 1;

        $temp = new user();
        $temp->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        // Need to call process_record so that custom field mappings are handled.
        $importplugin->process_record('user', $record, 'bogus');

        $this->validate_end_result();
    }
}