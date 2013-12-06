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
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Test class for validating that users are auto-assigned to clusters (i.e.
 * user sets) based on profile fields set during user import
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_cluster_profile_import_testcase extends rlip_elis_test {

    /**
     * Set up necessary data
     *
     * @param int $numfields The number of custom fields used in auto-association
     */
    private function init_required_data($numfields = 1) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::file('core/fields/moodle_profile/custom_fields.php'));
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/userset.class.php'));

        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

        // Set up the category only once.
        $fieldcategory = new field_category(array('name' => 'testcategoryname'));
        $fieldcategory->save();

        // Ste up the target userset only once.
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        for ($i = 1; $i <= $numfields; $i++) {
            // Custom field.
            $field = new field(array(
                'categoryid' => $fieldcategory->id,
                'shortname' => 'testfieldshortname'.$i,
                'name' => 'testfieldname'.$i,
                'datatype' => 'bool'
            ));
            $field->save();

            // Field owner.
            field_owner::ensure_field_owner_exists($field, 'moodle_profile');
            $DB->execute("UPDATE {".field_owner::TABLE."} SET exclude = ?", array(pm_moodle_profile::sync_to_moodle));

            // Field context level assocation.
            $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USER));
            $fieldcontextlevel->save();

            // The associated Moodle user profile field.
            $profiledefinecheckbox = new profile_define_checkbox();
            $data = new stdClass;
            $data->datatype = 'checkbox';
            $data->categoryid = 99999;
            $data->shortname = 'testfieldshortname'.$i;
            $data->name = 'testfieldname'.$i;
            $profiledefinecheckbox->define_save($data);

            // The "cluster-profile" association.
            $usersetprofile = new userset_profile(array(
                'clusterid' => $userset->id,
                'fieldid' => $field->id,
                'value' => 1
            ));
            $usersetprofile->save();
        }
    }

    /**
     * Validate that cluster-profile associations take place based on a single
     * custom field value during user create
     */
    public function test_one_field_cluster_profile_on_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');

        // Set up data.
        $this->init_required_data();

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'test@useremail.com';
        $record->country = 'CA';
        $record->testfieldshortname1 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $result = $importplugin->process_record('user', $record, 'bogus');

        $userid = $DB->get_field(user::TABLE, 'id', array('username' => 'testuserusername'));

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $userid,
            'clusterid' => 1,
            'plugin' => 'moodle_profile'
        )));
    }

    /**
     * Validate that cluster-profile associations take place based on two
     * custom field value during user create
     */
    public function test_two_field_cluster_profile_on_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        // Set up data.
        $this->init_required_data(2);

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->username = 'testuserusername';
        $record->idnumber = 'testuseridnumber';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'test@useremail.com';
        $record->country = 'CA';
        $record->testfieldshortname1 = 1;
        $record->testfieldshortname2 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => 1,
            'clusterid' => 1,
            'plugin' => 'moodle_profile'
        )));
    }

    /**
     * Validate that cluster-profile associations take place based on a single
     * custom field value during user update
     */
    public function test_one_field_cluster_profile_on_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        // Set up data.
        $this->init_required_data();

        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->testfieldshortname1 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => 1,
            'clusterid' => 1,
            'plugin' => 'moodle_profile'
        )));
    }

    /**
     * Validate that cluster-profile associations take place based on two
     * custom field value during user update
     */
    public function test_two_field_cluster_profile_on_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        // Set up data.
        $this->init_required_data(2);

        $user = new user(array(
            'username' => 'testuserusername',
            'email' => 'test@useremail.com',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'country' => 'CA'
        ));
        $user->save();

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->testfieldshortname1 = 1;
        $record->testfieldshortname2 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => 1,
            'clusterid' => 1,
            'plugin' => 'moodle_profile'
        )));
    }
}