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
 * Class that fetches import files for the user import
 */
class rlip_importprovider_mockuser extends rlip_importprovider_mock {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class for validating that field mappings work correctly during the ELIS
 * user import
 */
class elis_user_field_mappings_test extends elis_database_test {
    //store the mapping we will use
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
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(
            'context' => 'moodle',
            //prevent events functionality
            'events_handlers' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
            'user_info_field' => 'moodle',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
            field::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_owner::TABLE => 'elis_core',
            student::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/instructor.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));
        require_once(elispm::lib('data/waitlist.class.php'));

        return array(
            'cohort_members' => 'moodle',
            'external_services_users' => 'moodle',
            'external_tokens' => 'moodle',
            'groups_members' => 'moodle',
            'log' => 'moodle',
            'sessions' => 'moodle',
            'user_enrolments' => 'moodle',
            'user_info_data' => 'moodle',
            'user_lastaccess' => 'moodle',
            'user_preferences' => 'moodle',
            RLIP_LOG_TABLE => 'block_rlip',
            clusterassignment::TABLE => 'elis_program',
            curriculumstudent::TABLE => 'elis_program',
            field_data_char::TABLE => 'elis_core',
            field_data_num::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            instructor::TABLE => 'elis_program',
            student_grade::TABLE => 'elis_program',
            usertrack::TABLE => 'elis_program',
            waitlist::TABLE => 'elis_program'
        );
    }

    public function setUp() {
        global $DB, $USER;

        parent::setUp();

        $DB = self::$origdb;

        $admin = get_admin();
        $USER = $admin;
        $GLOBALS['USER'] = $USER;

        $DB = self::$overlaydb;
    }

    /**
     * Initialize the db records needed to represent the field mapping
     */
    private function init_mapping() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');

        $CFG->siteguest = '';

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
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        //field category
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        //custom field
        $field = new field(array('categoryid' => $field_category->id,
                                 'shortname' => 'testfieldshortname',
                                 'name' => 'testfieldname',
                                 'datatype' => 'bool'));
        $field->save();

        //field context level assocation
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => CONTEXT_ELIS_USER));
        $field_contextlevel->save();

        return $field->id;
    }

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Import data to use
     */
    private function run_user_import($data, $use_default_data = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Validate that mappings are applied during the user create action
     */
    public function test_mapping_applied_during_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field();

        //run the user create action
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

        //validation
        $data = array(
            'username' => 'testuserusername',
            'password' => hash_internal_user_password('Testpassword!0'),
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

        $record = $DB->get_record(user::TABLE, array('username' => 'testuserusername'));
        $this->assertEquals('testusercomments', $record->comments);
        $this->assertEquals('testusernotes', $record->notes);

        $instance = context_elis_user::instance(1);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $customfieldid,
                                                                          'contextid' => $instance->id,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the user update action
     */
    public function test_mapping_applied_during_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field();

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        //run the user update action
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

        //validation
        $data = array(
            'username' => 'testuserusername',
            'password' => hash_internal_user_password('updatedTestpassword!0'),
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

        $record = $DB->get_record(user::TABLE, array('username' => 'testuserusername'));
        $this->assertEquals('updatedtestusercomments', $record->comments);
        $this->assertEquals('updatedtestusernotes', $record->notes);

        $instance = context_elis_user::instance(1);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $customfieldid,
                                                                          'contextid' => $instance->id,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the user delete action
     */
    public function test_mapping_applied_during_user_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/user.class.php');

        $this->init_mapping();

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        //run the user delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customusername = 'testuserusername';
        $record->customidnumber = 'testuseridnumber';
        $record->customemail = 'testuser@email.com';

        $this->run_user_import((array)$record);

        //validation
        $this->assertEquals(0, $DB->count_records(user::TABLE));
    }
}
