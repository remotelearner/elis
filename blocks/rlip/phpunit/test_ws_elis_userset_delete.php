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
require_once(elispm::lib('data/clusterassignment.class.php'));
require_once(elispm::lib('data/clustercurriculum.class.php'));
require_once(elispm::lib('data/clustertrack.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once($CFG->dirroot.'/elis/program/enrol/userset/moodle_profile/userset_profile.class.php');
require_once($CFG->libdir.'/externallib.php');
require_once($dirname.'/../ws/elis/userset_delete.class.php');

/**
 * Tests webservice method block_rldh_elis_userset_delete
 */
class block_rlip_ws_elis_userset_delete_test extends rlip_test {
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
        global $CFG;
        $overlays = array(
            clusterassignment::TABLE => 'elis_program',
            clustercurriculum::TABLE => 'elis_program',
            clustertrack::TABLE => 'elis_program',
            field::TABLE => 'elis_core',
            field_category::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            field_data_int::TABLE => 'elis_core',
            field_data_num::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            userset_profile::TABLE => 'elis_program',
            'cache_flags' => 'moodle',
            'config' => 'moodle',
            'context' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            'user' => 'moodle',
        );
        if (file_exists($CFG->dirroot.'/repository/elis_files/version.php')) {
           $overlays['elis_files_userset_store'] = 'repository_elis_files';
        }
        return $overlays;
    }

    /**
     * Get ignored tables.
     * @return array An array of ignore tables.
     */
    protected static function get_ignored_tables() {
        return array(
            'block_instances' => 'moodle',
            'block_positions' => 'moodle',
            'comments' => 'moodle',
            'files' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'grading_areas' => 'moodle',
            'rating' => 'moodle',
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

        // Import, get system context.
        $sql = 'INSERT INTO {context} SELECT * FROM '.self::$origdb->get_prefix().'context WHERE contextlevel = ?';
        $DB->execute($sql, array(CONTEXT_SYSTEM));
        $syscontext = context_system::instance();

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

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $USER->id, $syscontext->id);
    }

    /**
     * Test successful userset delete
     */
    public function test_success() {
        global $DB;

        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Test';
        $fieldcat->save();

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'testfield';
        $field->name = 'Test Field';
        $field->datatype = 'text';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->give_permissions(array('elis/program:userset_delete'));

        $userset = array(
            'name' => 'testuserset',
            'recursive' => true
        );

        // setup userset to delete
        $us = new userset(array('name' => 'testuserset'));
        $us->save();

        $response = block_rldh_elis_userset_delete::userset_delete($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_userset_delete_success_code', 'block_rlip', get_string('ws_userset_delete_recursive', 'block_rlip')), $response['messagecode']);
        $this->assertEquals(get_string('ws_userset_delete_success_msg', 'block_rlip', get_string('ws_userset_delete_subsets', 'block_rlip')), $response['message']);
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
                // Test invalid parent input.
                array(
                        array(
                            'name' => 'testuserset',
                            'recursive' => 'A',
                        )
                )
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $us The incoming userset data.
     */
    public function test_failure(array $us) {
        global $DB;

        $this->give_permissions(array('elis/program:userset_delete'));

        // setup userset to delete
        $userset = new userset(array('name' => 'testuserset'));
        $userset->save();

        $response = block_rldh_elis_userset_delete::userset_delete($us);
    }
}
