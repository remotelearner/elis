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
 * Class for validating that enrolment of users into user sets works
 */
class elis_user_userset_enrolment_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        return array(
            clusterassignment::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            'user_info_data' => 'moodle'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');

        return array('context' => 'moodle',
                     'user' => 'moodle');
    }

    /**
     * Data provider for fields that identify user records
     *
     * @return array Parameter data, as needed by the test methods
     */
    function user_identifier_provider() {
        return array(
                array('create', 'delete', 'testuserusername', NULL, NULL),
                array('enrol', 'unenrol', NULL, 'testuser@email.com', NULL),
                array('enroll', 'unenroll', NULL, NULL, 'testuseridnumber')
               );
    }

    /**
     * Validate that users can be enrolled into user sets
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    function test_elis_user_userset_enrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //run the track enrolment create action
        $record = new stdClass;
        $record->action = $actioncreate;
        $record->context = 'cluster_testusersetname';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        //validation
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => $user->id,
                                                                             'clusterid' => $userset->id,
                                                                             'plugin' => 'manual',
                                                                             'autoenrol' => 0,
                                                                             'leader' => 0)));
    }

    /**
     * Validate that users can be enrolled from user sets
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    function test_elis_user_userset_unenrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $clusterassignment = new clusterassignment(array('userid' => $user->id,
                                                         'clusterid' => $userset->id,
                                                         'plugin' => 'manual',
                                                         'autoenrol' => 0));
        $clusterassignment->save();

        //validate setup
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => $user->id,
                                                                             'clusterid' => $userset->id,
                                                                             'plugin' => 'manual',
                                                                             'autoenrol' => 0)));

        //run the userset enrolment delete action
        $record = new stdClass;
        $record->action = $actiondelete;
        $record->context = 'cluster_testusersetname';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(clusterassignment::TABLE));
    }

    /**
     * Validate that user unenrolment from user sets only happens for the manual
     * userset ("cluster") plugin
     */
    function test_elis_user_userset_unenrolment_respects_userset_plugin() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $clusterassignment = new clusterassignment(array('userid' => $user->id,
                                                         'clusterid' => $userset->id,
                                                         'plugin' => 'manual',
                                                         'autoenrol' => 0));
        $clusterassignment->save();
        $clusterassignment = new clusterassignment(array('userid' => $user->id,
                                                         'clusterid' => $userset->id,
                                                         'plugin' => 'another',
                                                         'autoenrol' => 0));
        $clusterassignment->save();

        //validate setup
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => $user->id,
                                                                             'clusterid' => $userset->id,
                                                                             'plugin' => 'manual',
                                                                             'autoenrol' => 0)));
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => $user->id,
                                                                             'clusterid' => $userset->id,
                                                                             'plugin' => 'another',
                                                                             'autoenrol' => 0)));

        //run the userset enrolment delete action
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_enrolment_delete($record, 'bogus', 'testusersetname');

        //validation
        $this->assertEquals(1, $DB->count_records(clusterassignment::TABLE));
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array('userid' => $user->id,
                                                                             'clusterid' => $userset->id,
                                                                             'plugin' => 'another',
                                                                             'autoenrol' => 0)));
    }
}
