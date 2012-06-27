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

/**
 * Class for validating that ELIS / PM user actions propagate the appropriate
 * over to Moodle
 */
class elis_user_sync_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(user::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program',
                     'user' => 'moodle');
    }

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is created
     * during an import
     */
    public function test_user_sync_on_pm_user_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        //run the user create action
        $record = new stdClass;
        $record->idnumber = 'testuseridnumber';
        $record->username = 'testuserusername';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->email = 'testuseremail';
        $record->address = 'testuseraddress';
        $record->city = 'testusercity';
        $record->country = 'CA';
        $record->language = 'en';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->user_create($record, 'bogus');

        //validate the connection between the PM and Moodle use records
        $sql = "SELECT 'x'
                FROM {".user::TABLE."} crlmu
                JOIN {".usermoodle::TABLE."} usrmdl
                  ON crlmu.id = usrmdl.cuserid
                JOIN {user} mdlu
                  ON usrmdl.muserid = mdlu.id";
        $this->assertTrue($DB->record_exists_sql($sql));

        //validate the Moodle user record
        $this->assertTrue($DB->record_exists('user', array('idnumber' => $record->idnumber,
                                                           'username' => $record->username,
                                                           'firstname' => $record->firstname,
                                                           'lastname' => $record->lastname,
                                                           'email' => $record->email,
                                                           'address' => $record->address,
                                                           'city' => $record->city,
                                                           'country' => $record->country,
                                                           'lang' => $record->language)));
    }

    /**
     * Validate that appropriate fields are synched over to Moodle when PM user is updated
     * during an import
     */
    public function test_user_sync_on_pm_user_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));

        //TODO: may need to actuall set up language pack later on when
        //validation is implemented

        //create our "existing" user
        $user = new user(array('idnumber' => 'initial',
                               'username' => 'initial',
                               'firstname' => 'initial',
                               'lastname' => 'initial',
                               'email' => 'initial@initial.com',
                               'city' => 'initial',
                               'country' => 'CA',
                               'language' => 'en'));
        $user->save();

        //run the user update action
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
        $importplugin->user_update($record, 'bogus');

        //validation
        $this->assertEquals(1, $DB->count_records('user'));
        $this->assertTrue($DB->record_exists('user', array('idnumber' => 'initial',
                                                           'username' => 'initial',
                                                           'firstname' => $record->firstname,
                                                           'lastname' => $record->lastname,
                                                           'email' => 'initial@initial.com',
                                                           'city' => $record->city,
                                                           'country' => 'FR',
                                                           'lang' => 'en_us')));
    }
}