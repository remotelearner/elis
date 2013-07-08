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
 * @package    block_rlip
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}
$dirname = dirname(__FILE__);
require_once($dirname.'/../../../config.php');
global $CFG;
require_once($dirname.'/../lib.php');
require_once($dirname.'/rlip_test.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/usertrack.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/track_enrolment_delete.class.php');

/**
 * Tests webservice method block_rldh_elis_track_enrolment_delete
 */
class block_rlip_ws_elis_track_enrolment_delete_test extends rlip_test {
    /**
     * @var object Holds a backup of the user object so we can do sane permissions handling.
     */
    static public $userbackup;

    /**
     * @var array Array of globals to not do backup.
     */
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Get overlay tables.
     * @return array An array of overlay tables.
     */
    protected static function get_overlay_tables() {
        return array(
            field::TABLE => 'elis_core',
            curriculum::TABLE => 'elis_program',
            curriculumstudent::TABLE => 'elis_program',
            track::TABLE => 'elis_program',
            trackassignment::TABLE => 'elis_program',
            usertrack::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            'cache_flags' => 'moodle',
            'config' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'user' => 'moodle',
        );
    }

    /**
     * Perform teardown after test - restore the user global.
     */
    protected function tearDown() {
        global $USER;
        $USER = static::$userbackup;
        parent::tearDown();
    }

    /**
     * Perform setup before test - backup the user global.
     */
    protected function setUp() {
        global $USER;
        static::$userbackup = $USER;
        parent::setUp();
    }

    /**
     * Give permissions to the current user.
     * @param array $perms Array of permissions to grant.
     */
    public function give_permissions(array $perms) {
        global $USER, $DB;

        accesslib_clear_all_caches(true);

        set_config('siteguest', '');
        set_config('siteadmins', '');

        $syscontext = get_context_instance(CONTEXT_SYSTEM);

        $assigninguser = new user(array(
            'idnumber' => 'assigninguserid',
            'username' => 'assigninguser',
            'firstname' => 'assigninguser',
            'lastname' => 'assigninguser',
            'email' => 'assigninguser@testuserdomain.com',
            'country' => 'CA'
        ));
        $assigninguser->save();
        $USER = $DB->get_record('user', array('id' => $assigninguser->id));

        $dupemailuser = new user(array(
            'idnumber' => 'dupemailuserid',
            'username' => 'dupemailuser',
            'firstname' => 'dupemailuserfirstname',
            'lastname' => 'dupemailuserlastname',
            'email' => 'assigninguser@testuserdomain.com', // dup email!
            'country' => 'CA'
        ));
        $dupemailuser->save();

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $USER->id, $syscontext->id);
    }

    /**
     * method to create test program.
     * @param string $idnumber the idnumber to use to create program
     * @return int|bool the program DB id or false on error
     */
    public function create_program($idnumber) {
        $params = array(
            'idnumber' => $idnumber,
            'name' => 'Test Program',
        );
        $program = new curriculum($params);
        $program->save();
        return !empty($program->id) ? $program->id : false;
    }

    /**
     * method to create test track.
     * @param string $trackidnumber the idnumber to use to create track
     * @param string $programid the id of the program to associate the track to
     * @return int|bool the track DB id or false on error
     */
    public function create_track($trackidnumber, $programid) {
        $params = array(
            'curid' => $programid,
            'idnumber' => $trackidnumber,
            'name' => 'Test Track',
        );
        $track = new track($params);
        $track->save();
        return !empty($track->id) ? $track->id : false;
    }

    /**
     * Test successful track enrolment deletion.
     */
    public function test_success() {
        global $DB, $USER;

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $programidnumber = 'TestProgramForTrack';
        $programid = $this->create_program($programidnumber);

        $trackidnumber = 'TestTrackForProgram';
        $trackid = $this->create_track($trackidnumber, $programid);

        $userid = 1;

        // Create the track enrolment record to delete
        usertrack::enrol($userid, $trackid);

        $data = array(
            'track_idnumber' => $trackidnumber,
            'user_username' => 'assigninguser',
            'user_email' => 'assigninguser@testuserdomain.com',
        );

        $this->give_permissions(array('elis/program:track_enrol'));
        $response = block_rldh_elis_track_enrolment_delete::track_enrolment_delete($data);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_track_enrolment_delete_success_code', 'block_rlip'), $response['messagecode']);
        $this->assertEquals(get_string('ws_track_enrolment_delete_success_msg', 'block_rlip'), $response['message']);
        $this->assertFalse($DB->record_exists(usertrack::TABLE, array('userid' => $userid, 'trackid' => $trackid)));
    }

    /**
     * Dataprovider for test_failure()
     * @return array An array of parameters
     */
    public function dataprovider_failure() {
        return array(
                // Test empty input.
                array(
                        array()
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                            'user_idnumber' => 'assigninguserid',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'user_username' => 'assigninguser',
                            'user_idnumber' => 'assigninguserid',
                            'user_email' => 'assigninguser@testuserdomain.com',
                        )
                ),
                // Test not all required input.
                array(
                        array(
                            'track_idnumber' => 'TestTrackForProgram',
                        )
                ),
                // Test invalid input.
                array(
                        array(
                            'track_idnumber' => 'BogusTrack',
                        )
                ),
                // Test conflicting input.
                array(
                        array(
                            'user_username' => 'anotheruser',
                            'user_idnumber' => 'assigninguserid',
                        )
                ),
                // Test not unique user input.
                array(
                        array(
                            'track_idnumber' => 'TestTrackForProgram',
                            'user_email' => 'assigninguser@testuserdomain.com',
                        )
                ),
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $data The incoming track enrolment data.
     */
    public function test_failure(array $data) {
        global $DB;

        $programidnumber = 'TestProgramForTrack';
        $programid = $this->create_program($programidnumber);

        $trackidnumber = 'TestTrackForProgram';
        $trackid = $this->create_track($trackidnumber, $programid);

        $this->give_permissions(array('elis/program:track_enrol'));
        $response = block_rldh_elis_track_enrolment_delete::track_enrolment_delete($data);
    }
}
