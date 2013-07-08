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
 * Test class for validating that users are auto-assigned to clusters (i.e.
 * user sets) based on profile fields set during user import
 */
class elis_cluster_profile_import_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        return array('context' => 'moodle',
                     'user' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_info_field' => 'moodle',
                     field::TABLE => 'elis_core',
                     field_category::TABLE => 'elis_core',
                     field_contextlevel::TABLE => 'elis_core',
                     field_data_int::TABLE => 'elis_core',
                     field_owner::TABLE => 'elis_core',
                     user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program',
                     userset::TABLE => 'elis_program',
                     userset_profile::TABLE => 'elis_program');
    }

    /**
     * Set up necessary data
     *
     * @param int $num_fields The number of custom fields used in auto-association
     */
    private function init_required_data($num_fields = 1) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::file('core/fields/moodle_profile/custom_fields.php'));
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/userset.class.php'));

        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

        //set up the category only once
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        //ste up the target userset only once
        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        for ($i = 1; $i <= $num_fields; $i++) {
            //custom field
            $field = new field(array('categoryid' => $field_category->id,
                                     'shortname' => 'testfieldshortname'.$i,
                                     'name' => 'testfieldname'.$i,
                                     'datatype' => 'bool'));
            $field->save();
    
            //field owner
            field_owner::ensure_field_owner_exists($field, 'moodle_profile');
            $DB->execute("UPDATE {".field_owner::TABLE."}
                          SET exclude = ?", array(pm_moodle_profile::sync_to_moodle));
    
            //field context level assocation
            $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                               'contextlevel' => CONTEXT_ELIS_USER));
            $field_contextlevel->save();
    
            //the associated Moodle user profile field
            $profile_define_checkbox = new profile_define_checkbox();
            $data = new stdClass;
            $data->datatype = 'checkbox';
            $data->categoryid = 99999;
            $data->shortname = 'testfieldshortname'.$i;
            $data->name = 'testfieldname'.$i;
            $profile_define_checkbox->define_save($data);
    
            //the "cluster-profile" association
            $userset_profile = new userset_profile(array('clusterid' => $userset->id,
                                                         'fieldid' => $i,
                                                         'value' => 1));
            $userset_profile->save();
        }
    }

    /**
     * Validate that cluster-profile associations take place based on a single
     * custom field value during user create
     */
    public function test_one_field_cluster_profile_on_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');

        //set up data
        $this->init_required_data();

        //run the user create action
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
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => 1,
                                                                             'clusterid' => 1,
                                                                             'plugin' => 'moodle_profile')));
    }

    /**
     * Validate that cluster-profile associations take place based on two
     * custom field value during user create
     */
    public function test_two_field_cluster_profile_on_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up data
        $this->init_required_data(2);

        //run the user create action
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

        $user = new user();
        $user->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => 1,
                                                                             'clusterid' => 1,
                                                                             'plugin' => 'moodle_profile')));
    }

    /**
     * Validate that cluster-profile associations take place based on a single
     * custom field value during user update
     */
    public function test_one_field_cluster_profile_on_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up data
        $this->init_required_data();

        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //run the user create action
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->testfieldshortname1 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => 1,
                                                                             'clusterid' => 1,
                                                                             'plugin' => 'moodle_profile')));
    }

    /**
     * Validate that cluster-profile associations take place based on two
     * custom field value during user update
     */
    public function test_two_field_cluster_profile_on_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/clusterassignment.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        //set up data
        $this->init_required_data(2);

        $user = new user(array('username' => 'testuserusername',
                               'email' => 'test@useremail.com',
                               'idnumber' => 'testuseridnumber',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'country' => 'CA'));
        $user->save();

        //run the user create action
        $record = new stdClass;
        $record->action = 'update';
        $record->username = 'testuserusername';
        $record->email = 'test@useremail.com';
        $record->idnumber = 'testuseridnumber';
        $record->testfieldshortname1 = 1;
        $record->testfieldshortname2 = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', $record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => 1,
                                                                             'clusterid' => 1,
                                                                             'plugin' => 'moodle_profile')));
    }
}

?>