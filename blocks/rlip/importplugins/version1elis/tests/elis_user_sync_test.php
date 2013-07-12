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
 * Class for validating that ELIS / PM user actions propagate the appropriate users over to Moodle.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_user_sync_testcase extends rlip_elis_test {

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is created
     * during an import
     */
    public function test_user_sync_on_pm_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        // TODO: may need to actuallu set up language pack later on when validation is implemented.

        // Run the user create action.
        $record = new stdClass;
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuser@email.com';
        $record->address = 'testuseraddress';
        $record->city = 'testusercity';
        $record->country = 'CA';
        $record->language = 'fr';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_create($record, 'bogus');

        // Validate the connection between the PM and Moodle use records.
        $sql = "SELECT 'x'
                  FROM {".user::TABLE."} crlmu
                  JOIN {".usermoodle::TABLE."} usrmdl ON crlmu.id = usrmdl.cuserid
                  JOIN {user} mdlu ON usrmdl.muserid = mdlu.id";
        $this->assertTrue($DB->record_exists_sql($sql));

        // Validate the Moodle user record.
        $this->assertTrue($DB->record_exists('user', array(
            'idnumber' => $record->idnumber,
            'username' => $record->username,
            'firstname' => $record->firstname,
            'lastname' => $record->lastname,
            'email' => $record->email,
            'address' => $record->address,
            'city' => $record->city,
            'country' => $record->country,
            'lang' => $record->language
        )));
    }

    /**
     * Validate that custom user fields are synched over to Moodle when PM user is created
     * during an import
     */
    public function test_user_custom_field_sync_on_user_create() {
        // NOTE: not testing all cases because ELIS handles the details and this seems to already work.
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elis::file('core/fields/moodle_profile/custom_fields.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

        // Reset cached custom fields.
        $user = new user();
        $user->reset_custom_field_list();

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
        $data->shortname = 'testfieldshortname';
        $data->name = 'testfieldname';
        $profiledefinecheckbox->define_save($data);

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuser@email.com';
        $record->address = 'testuseraddress';
        $record->city = 'testusercity';
        $record->country = 'CA';
        $record->language = 'fr';
        $record->testfieldshortname = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $userid = $DB->get_field('user', 'id', array('username' => 'testuserusername', 'mnethostid' => $CFG->mnet_localhost_id));
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);

        $this->assertEquals(1, $user->profile_field_testfieldshortname);
    }

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is updated
     * during an import
     */
    public function test_user_sync_on_pm_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        $origusercount = $DB->count_records('user');

        // TODO: may need to actuallu set up language pack later on when.
        // Validation is implemented.

        // Create our "existing" user.
        $user = new user(array(
            'idnumber' => 'initial',
            'username' => 'initial',
            'firstname' => 'initial',
            'lastname' => 'initial',
            'email' => 'initial@initial.com',
            'city' => 'initial',
            'country' => 'CA',
            'language' => 'fr'
          ));
        $user->save();

        // Run the user update action.
        $record = new stdClass;
        $record->idnumber = 'initial';
        $record->username = 'initial';
        $record->firstname = 'final';
        $record->lastname = 'final';
        $record->email = 'initial@initial.com';
        $record->city = 'final';
        $record->country = 'FR';
        $record->language = 'en_us';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_update($record, 'bogus');

        // Validation.
        $this->assertEquals((1 + $origusercount), $DB->count_records('user'));
        $this->assertTrue($DB->record_exists('user', array(
            'idnumber' => 'initial',
            'username' => 'initial',
            'firstname' => $record->firstname,
            'lastname' => $record->lastname,
            'email' => 'initial@initial.com',
            'city' => $record->city,
            'country' => 'FR',
            'lang' => 'en_us'
        )));
    }

    /**
     * Validate that custom user fields are synched over to Moodle when PM user is updated
     * during an import
     */
    public function test_user_custom_field_sync_on_user_update() {
        // NOTE: not testing all cases because ELIS handles the details and this.
        // Seems to already work.
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once($CFG->dirroot.'/user/profile/field/checkbox/define.class.php');

        // Set up the user.
        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

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
        $data->shortname = 'testfieldshortname';
        $data->name = 'testfieldname';
        $profiledefinecheckbox->define_save($data);

        // Reset cached custom fields.
        $user = new user();
        $user->reset_custom_field_list();

        // Run the user create action.
        $record = new stdClass;
        $record->action = 'update';
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->email = 'testuser@email.com';
        $record->testfieldshortname = 1;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', $record, 'bogus');

        // Validation.
        $user = new stdClass;
        $user->id = 1;
        profile_load_data($user);

        $this->assertEquals(1, $user->profile_field_testfieldshortname);
    }
}