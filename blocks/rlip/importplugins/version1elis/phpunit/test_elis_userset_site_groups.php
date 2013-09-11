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
 * Validate that IP actions trigger the appropriate userset site groups functionality,
 * including groupings functionality
 */
class elis_userset_site_groups_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::file('plugins/userset_classification/usersetclassification.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');

        return array(
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'groupings' => 'moodle',
            'groupings_groups' => 'moodle',
            'groups_members' => 'moodle',
            'groups' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
            'user_info_field' => 'moodle',
            'user_info_data' => 'moodle',
            clusterassignment::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_owner::TABLE => 'elis_core',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            userset_profile::TABLE => 'elis_program',
            usersetclassification::TABLE => 'pmplugins_userset_classification'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('cache_flags' => 'moodle');
    }

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
        require_once(elispm::file('plugins/userset_classification/usersetclassification.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        // set up "clsuter groups"-related fields
        $field_category = new field_category(array('name' => 'Associated Group'));
        $field_category->save();

        $flddata = array(
            'categoryid' => $field_category->id,
            'shortname'  => 'userset_group',
            'name'       => 'Enable Corresponding Group',
            'datatype'   => 'bool'
        );
        $field = new field($flddata);
        $field->save();

        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $field_contextlevel->save();

        $flddata = array(
            'categoryid' => $field_category->id,
            'shortname'  => 'userset_groupings',
            'name'       => 'Autoenrol users in groupings',
            'datatype'   => 'bool'
        );
        $field = new field($flddata);
        $field->save();

        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $field_contextlevel->save();

        // set up the test user
        $userdata = array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'test@useremail.com',
            'country'   => 'CA'
        );
        $user = new user($userdata);
        $user->save();

        $user->synchronize_moodle_user();

        // we need a system-level role assignment
        $roleid = create_role('systemrole', 'systemrole', 'systemrole');
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $context = context_system::instance();

        role_assign($roleid, $userid, $context->id);

        // set up the userset
        $usclassification = new usersetclassification();
        $usclassification->param_autoenrol_curricula = 1;
        $usclassification->param_autoenrol_tracks = 1;
        $usclassification->param_autoenrol_groups = 1;
        $usclassification->param_autoenrol_groupings = 1;
        $usclassification->save();

        $userset = new userset();
        $userset_data = array('name' => 'testusersetname');
        if ($setcustomfielddata) {
            $userset_data['field_userset_group'] = 1;
            $userset_data['field_userset_groupings'] = 1;
        }
        $userset->set_from_data((object)$userset_data);
        $userset->save();

        if ($setautoassociatefields) {
            // set up a file we can use to auto-associate users to a userset
            $flddata = array(
                'categoryid' => $field_category->id,
                'shortname'  => 'autoassociate',
                'name'       => 'autoassociate',
                'datatype'   => 'bool'
            );
            $field = new field($flddata);
            $field->save();

            field_owner::ensure_field_owner_exists($field, 'moodle_profile');
            $DB->execute("UPDATE {".field_owner::TABLE."}
                          SET exclude = ?", array(pm_moodle_profile::sync_to_moodle));

            $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USER));
            $field_contextlevel->save();

            // the associated Moodle user profile field
            require_once($CFG->dirroot.'/user/profile/definelib.php');
            require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

            $profile_define_checkbox = new profile_define_checkbox();
            $data = new stdClass;
            $data->datatype = 'checkbox';
            $data->categoryid = 99999;
            $data->shortname = 'autoassociate';
            $data->name = 'autoassociate';
            $profile_define_checkbox->define_save($data);
            $mdlfldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'autoassociate'));

            // the "cluster-profile" association
            $updata = array(
                'clusterid' => $userset->id,
                'fieldid'   => $mdlfldid,
                'value'     => '1'
            );
            $userset_profile = new userset_profile($updata);
            $userset_profile->save();
        }
    
        if ($assignuser) {
            // assign the user to the user set
            $cadata = array(
                'clusterid' => $userset->id,
                'userid'    => $user->id,
                'plugin'    => 'manual'
            );
            $clusterassignment = new clusterassignment($cadata);
            $clusterassignment->save();
        }
    }

    /**
     * Validate that our constant expect end result is reached
     */
    private function validate_end_result() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/grouplib.php');

        //validate group creation
        $groupid = groups_get_group_by_name(SITEID, 'testusersetname');
        $this->assertNotEquals(false, $groupid);

        // validate user-group assignment
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $this->assertTrue(groups_is_member($groupid, $userid));

        //validate grouping creation
        $groupingid = groups_get_grouping_by_name(SITEID, 'testusersetname');
        $this->assertNotEquals(false, $groupingid);

        //validate group-grouping assignment
        $this->assertTrue($DB->record_exists('groupings_groups', array('groupingid' => $groupingid,
                                                                       'groupid' => $groupid)));
    }

    /**
     * Validate that userset updates trigger group and grouping functionality
     */
    public function test_userset_update_triggers_group_and_grouping_setup() {
        global $DB;

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));

        //set up the necessary config data
        set_config('userset_groups', 1, 'pmplugins_userset_groups');
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        $this->set_up_required_data(false, true);

        //validate setup
        $this->assertEquals(0, $DB->count_records('groups'));

        //run the user set update action
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testusersetname';
        $record->userset_group = '1';
        $record->userset_groupings = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        //need to call process_record so that custom field mappings are handled
        $importplugin->process_record('course', $record, 'bogus');

        $this->validate_end_result();
    }

    /**
     * Validate that assigning users to usersets triggers group and grouping
     * functionality
     */
    public function test_userset_assignment_triggers_group_and_grouping_setup() {
        //set up the necessary config data
        set_config('userset_groups', 1, 'pmplugins_userset_groups');
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        $this->set_up_required_data(true, false);

        //run the user set enrolment action
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
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

        // set up the necessary config data
        set_config('userset_groups', 1, 'pmplugins_userset_groups');
        set_config('site_course_userset_groups', 1, 'pmplugins_userset_groups');
        set_config('userset_groupings', 1, 'pmplugins_userset_groups');
        set_config('siteguest', '');

        $this->set_up_required_data(true, false, true);

        //run the user update action
        $record = new stdClass;
        $record->action = 'update';
        $record->idnumber = 'testuseridnumber';
        // TODO: remove the next two fields once we can updated based on just idnumber
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->autoassociate = 1;

        $user = new user(array('username' => 'testuserusername'));
        $user->reset_custom_field_list();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        // need to call process_record so that custom field mappings are handled
        $importplugin->process_record('user', $record, 'bogus');

        $this->validate_end_result();
    }
}
