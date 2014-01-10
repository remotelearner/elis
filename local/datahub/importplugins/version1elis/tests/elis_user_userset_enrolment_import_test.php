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
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

/**
 * Class for validating that enrolment of users into user sets works.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_user_userset_enrolment_testcase extends rlip_elis_test {

    /**
     * Data provider for fields that identify user records
     *
     * @return array Parameter data, as needed by the test methods
     */
    public function user_identifier_provider() {
        return array(
                array('create', 'delete', 'testuserusername', null, null),
                array('enrol', 'unenrol', null, 'testuser@email.com', null),
                array('enroll', 'unenroll', null, null, 'testuseridnumber')
        );
    }

    /**
     * Validate that users can be enrolled into user sets
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_userset_enrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        // Run the track enrolment create action.
        $record = new stdClass;
        $record->action = $actioncreate;
        $record->context = 'cluster_testusersetname';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'manual',
            'autoenrol' => 0,
            'leader' => 0
        )));
    }

    /**
     * Validate that users can be enrolled from user sets
     *
     * @param string $username A sample user's username, or null if not used in the import
     * @param string $email A sample user's email, or null if not used in the import
     * @param string $idnumber A sample user's idnumber, or null if not used in the import
     * @dataProvider user_identifier_provider
     */
    public function test_elis_user_userset_unenrolment_import($actioncreate, $actiondelete, $username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $clusterassignment = new clusterassignment(array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'manual',
            'autoenrol' => 0
        ));
        $clusterassignment->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'manual',
            'autoenrol' => 0
        )));

        // Run the userset enrolment delete action.
        $record = new stdClass;
        $record->action = $actiondelete;
        $record->context = 'cluster_testusersetname';
        if ($username != null) {
            $record->user_username = $user->username;
        }
        if ($email != null) {
            $record->user_email = $user->email;
        }
        if ($idnumber != null) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        // Validation.
        $this->assertEquals(0, $DB->count_records(clusterassignment::TABLE));
    }

    /**
     * Validate that user unenrolment from user sets only happens for the manual
     * userset ("cluster") plugin
     */
    public function test_elis_user_userset_unenrolment_respects_userset_plugin() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        $user = new user(array(
            'idnumber' => 'testuseridnumber',
            'username' => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname' => 'testuserlastname',
            'email' => 'testuser@email.com',
            'country' => 'CA'
        ));
        $user->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        $clusterassignment = new clusterassignment(array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'manual',
            'autoenrol' => 0
        ));
        $clusterassignment->save();
        $clusterassignment = new clusterassignment(array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'another',
            'autoenrol' => 0
        ));
        $clusterassignment->save();

        // Validate setup.
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'manual',
            'autoenrol' => 0
        )));
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'another',
            'autoenrol' => 0
        )));

        // Run the userset enrolment delete action.
        $record = new stdClass;
        $record->context = 'cluster_testusersetname';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_enrolment_delete($record, 'bogus', 'testusersetname');

        // Validation.
        $this->assertEquals(1, $DB->count_records(clusterassignment::TABLE));
        $this->assertTrue($DB->record_exists(clusterassignment::TABLE, array(
            'userid' => $user->id,
            'clusterid' => $userset->id,
            'plugin' => 'another',
            'autoenrol' => 0
        )));
    }
}
