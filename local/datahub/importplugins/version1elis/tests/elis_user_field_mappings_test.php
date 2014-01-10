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
require_once($CFG->dirroot.'/local/datahub/importplugins/version1/tests/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');

/**
 * Class for validating that field mappings work correctly during the ELIS user import.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_user_field_mappings_testcase extends rlip_elis_test {
    // Store the mapping we will use.
    private $mapping = array(
        'action' => 'customaction',
        'username' => 'customusername',
        'password' => 'custompassword',
        'idnumber' => 'customidnumber',
        'firstname' => 'customfirstname',
        'lastname' => 'customlastname',
        'mi' => 'custommi',
        'email' => 'customemail',
        'email2' => 'customemail2',
        'address' => 'customaddress',
        'address2' => 'customaddress2',
        'city' => 'customcity',
        'state' => 'customstate',
        'postalcode' => 'custompostalcode',
        'country' => 'customcountry',
        'phone' => 'customphone',
        'phone2' => 'customphone2',
        'fax' => 'customfax',
        'birthdate' => 'custombirthdate',
        'gender' => 'customgender',
        'language' => 'customlanguage',
        'transfercredits' => 'customtransfercredits',
        'comments' => 'customcomments',
        'notes' => 'customnotes',
        'inactive' => 'custominactive',
        'testfieldshortname' => 'customtestfieldshortname'
    );

    /**
     * Run before every test, set as admin user.
     */
    public function setUp() {
        global $DB;
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Initialize the db records needed to represent the field mapping
     */
    private function init_mapping() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/importplugins/version1elis/lib.php');

        foreach ($this->mapping as $standardfieldname => $customfieldname) {
            $mapping = new stdClass;
            $mapping->entitytype = 'user';
            $mapping->standardfieldname = $standardfieldname;
            $mapping->customfieldname = $customfieldname;

            $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);
        }
    }

    /**
     * Create the necessary custom field
     *
     * @return int The id of the created field
     */
    private function create_custom_field() {
        global $CFG;
        require_once($CFG->dirroot.'/local/eliscore/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/accesslib.php');

        // Field category.
        $fieldcategory = new field_category(array('name' => 'testcategoryname'));
        $fieldcategory->save();

        // Custom field.
        $field = new field(array(
            'categoryid' => $fieldcategory->id,
            'shortname' => 'testfieldshortname',
            'name' => 'testfieldname',
            'datatype' => 'bool'
        ));
        $field->save();

        // Field context level assocation.
        $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USER));
        $fieldcontextlevel->save();

        return $field->id;
    }

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Import data to use
     */
    private function run_user_import($data, $usedefaultdata = true) {
        global $CFG;
        $file = get_plugin_directory('dhimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $provider = new rlipimport_version1elis_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Validate that mappings are applied during the user create action
     */
    public function test_mapping_applied_during_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/eliscore/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/accesslib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field();

        // Clear the cached custom field list.
        $usertoclearcustomfieldlist = new user;
        $usertoclearcustomfieldlist->reset_custom_field_list();

        // Run the user create action.
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customusername = 'testuserusername';
        $record->custompassword = 'Testpassword!0';
        $record->customidnumber = 'testuseridnumber';
        $record->customfirstname = 'testuserfirstname';
        $record->customlastname = 'testuserlastname';
        $record->custommi = 'testusermi';
        $record->customemail = 'testuser@email.com';
        $record->customemail2 = 'testuser@email2.com';
        $record->customaddress = 'testuseraddress';
        $record->customaddress2 = 'testuseraddress2';
        $record->customcity = 'testusercity';
        $record->customstate = 'testuserstate';
        $record->custompostalcode = 'testuserpostalcode';
        $record->customcountry = 'CA';
        $record->customphone = 'testuserphone';
        $record->customphone2 = 'testuserphone2';
        $record->customfax = 'testuserfax';
        $record->custombirthdate = 'Jan/01/2012';
        $record->customgender = 'M';
        $record->customlanguage = 'en';
        $record->customtransfercredits = '1';
        $record->customcomments = 'testusercomments';
        $record->customnotes = 'testusernotes';
        $record->custominactive = '0';
        $record->customtestfieldshortname = '1';

        $this->run_user_import((array)$record);

        // Validation.
        $data = array(
            'username' => 'testuserusername',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'mi' => 'testusermi',
            'email' => 'testuser@email.com',
            'email2' => 'testuser@email2.com',
            'address' => 'testuseraddress',
            'address2' => 'testuseraddress2',
            'city' => 'testusercity',
            'state' => 'testuserstate',
            'postalcode' => 'testuserpostalcode',
            'country' => 'CA',
            'phone' => 'testuserphone',
            'phone2' => 'testuserphone2',
            'fax' => 'testuserfax',
            'birthdate' => '2012/01/01',
            'gender' => 'M',
            'language' => 'en',
            'transfercredits' => 1,
            'inactive' => 0
        );

        $this->assertTrue($DB->record_exists(user::TABLE, $data));

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $data['username']));
        $this->assertTrue(validate_internal_user_password($userrec, 'Testpassword!0'));

        $record = $DB->get_record(user::TABLE, array('username' => 'testuserusername'));
        $this->assertEquals('testusercomments', $record->comments);
        $this->assertEquals('testusernotes', $record->notes);

        $instance = \local_elisprogram\context\user::instance(1);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array(
            'fieldid' => $customfieldid,
            'contextid' => $instance->id,
            'data' => 1
        )));
    }

    /**
     * Validate that mappings are applied during the user update action
     */
    public function test_mapping_applied_during_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/eliscore/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field();

        // Clear the cached custom field list.
        $usertoclearcustomfieldlist = new user;
        $usertoclearcustomfieldlist->reset_custom_field_list();

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        // Run the user update action.
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customusername = 'testuserusername';
        $record->custompassword = 'updatedTestpassword!0';
        $record->customidnumber = 'testuseridnumber';
        $record->customfirstname = 'updatedtestuserfirstname';
        $record->customlastname = 'updatedtestuserlastname';
        $record->custommi = 'updatedtestusermi';
        $record->customemail = 'testuser@email.com';
        $record->customemail2 = 'updatedtestuser@email2.com';
        $record->customaddress = 'updatedtestuseraddress';
        $record->customaddress2 = 'updatedtestuseraddress2';
        $record->customcity = 'updatedtestusercity';
        $record->customstate = 'updatedtestuserstate';
        $record->custompostalcode = 'updatedtestuserpostalcode';
        $record->customcountry = 'FR';
        $record->customphone = 'updatedtestuserphone';
        $record->customphone2 = 'updatedtestuserphone2';
        $record->customfax = 'updatedtestuserfax';
        $record->custombirthdate = 'Jan/02/2012';
        $record->customgender = 'F';
        $record->customlanguage = 'fr';
        $record->customtransfercredits = '2';
        $record->customcomments = 'updatedtestusercomments';
        $record->customnotes = 'updatedtestusernotes';
        $record->custominactive = '1';
        $record->customtestfieldshortname = '1';

        $this->run_user_import((array)$record);

        // Validation.
        $data = array(
            'username' => 'testuserusername',
            'idnumber' => 'testuseridnumber',
            'firstname' => 'updatedtestuserfirstname',
            'lastname' => 'updatedtestuserlastname',
            'mi' => 'updatedtestusermi',
            'email' => 'testuser@email.com',
            'email2' => 'updatedtestuser@email2.com',
            'address' => 'updatedtestuseraddress',
            'address2' => 'updatedtestuseraddress2',
            'city' => 'updatedtestusercity',
            'state' => 'updatedtestuserstate',
            'postalcode' => 'updatedtestuserpostalcode',
            'country' => 'FR',
            'phone' => 'updatedtestuserphone',
            'phone2' => 'updatedtestuserphone2',
            'fax' => 'updatedtestuserfax',
            'birthdate' => '2012/01/02',
            'gender' => 'F',
            'language' => 'fr',
            'transfercredits' => 2,
            'inactive' => 1
        );
        $this->assertTrue($DB->record_exists(user::TABLE, $data));

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $data['username']));
        $this->assertTrue(validate_internal_user_password($userrec, 'updatedTestpassword!0'));

        $record = $DB->get_record(user::TABLE, array('username' => 'testuserusername'));
        $this->assertEquals('updatedtestusercomments', $record->comments);
        $this->assertEquals('updatedtestusernotes', $record->notes);

        $instance = \local_elisprogram\context\user::instance(1);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array(
            'fieldid' => $customfieldid,
            'contextid' => $instance->id,
            'data' => 1
        )));
    }

    /**
     * Validate that mappings are applied during the user delete action
     */
    public function test_mapping_applied_during_user_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/data/user.class.php');

        $this->init_mapping();

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        // Run the user delete action.
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customusername = 'testuserusername';
        $record->customidnumber = 'testuseridnumber';
        $record->customemail = 'testuser@email.com';

        $this->run_user_import((array)$record);

        // Validation.
        $this->assertEquals(0, $DB->count_records(user::TABLE));
    }
}