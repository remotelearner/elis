<?php
/**
 *
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');


class file_userSynchronisationTest extends elis_database_test {

    private $userstodelete;

    protected static function get_overlay_tables() {
        return array(
            'cache_flags' => 'moodle',
            'context' => 'moodle',
            'context_temp' => 'moodle',
            'course' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'user' => 'moodle',
            'user_info_category' => 'moodle',
            'user_info_field' => 'moodle',
            'user_info_data' => 'moodle'
        );
    }

    protected function setUp() {
        parent::setUp();

        $this->setUpRolesTables();
        $this->load_csv_data();

        $DB = self::$origdb; // setUpContextsTable needs $DB to be the real
        // database for get_admin()
        $this->setUpContextsTable();
        $DB = self::$overlaydb;
    }

    protected function tearDown() {
        // Remove any users created on the Aflresco server
        if (!empty($this->userstodelete)) {
            foreach ($this->userstodelete as $usertodelete) {
                elis_files_delete_user($usertodelete, true);
            }
        }

        parent::tearDown();
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        global $CFG;

        $syscontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        self::$overlaydb->import_record('context', $syscontext);

        $site = self::$origdb->get_record('course', array('id' => SITEID));
        self::$overlaydb->import_record('course', $site);


        $sitecontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        self::$overlaydb->import_record('context', $sitecontext);

        // Guest user
        $guest = self::$origdb->get_record('user', array('username' => 'guest', 'mnethostid' => $CFG->mnet_localhost_id));
        if (!empty($guest)) {
            self::$overlaydb->import_record('user', $guest);
        }

        // Primary admin user
        $admin = get_admin();
        if ($admin) {
            self::$overlaydb->import_record('user', $admin);
            $CFG->siteadmins = $admin->id;
            $usercontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_USER,
                                                     'instanceid' => $admin->id));
            self::$overlaydb->import_record('context', $usercontext);
        }
    }

    private function setUpRolesTables() {
        $roles = self::$origdb->get_records('role');
        foreach ($roles as $rolerec) {
            self::$overlaydb->import_record('role', $rolerec);
        }

        $roles_ctxs = self::$origdb->get_records('role_context_levels');
        foreach ($roles_ctxs as $role_ctx) {
            self::$overlaydb->import_record('role_context_levels', $role_ctx);
        }
    }

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/mdluser.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Test whether the ELIS_files::file_browse_options() method properly returns that a user has correct access to
     */
    public function testGetOtherCapabilities() {
        global $DB;

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not initialize EILS Files repository');
        }

        $syscontext = context_system::instance();

        $manager_role = $DB->get_record('role', array('name' => 'manager'));

        $capabilities = array(
            'repository/elis_files:viewsitecontent' => false,
            'repository/elis_files:viewsharedcontent' => false,
            'repository/elis_files:viewowncontent' => false
        );

        foreach (array_keys($capabilities) as $capability) {
            if (!$DB->record_exists('role_capabilities', array('roleid' => $manager_role->id, 'capability' => $capability))) {
                assign_capability($capability, CAP_ALLOW, $manager_role->id, $syscontext->id);
            }
        }

        // Load the test user object and set it to be the global USER record
        $USER = $DB->get_record('user', array('id' => 100));
        $GLOBALS['USER'] = $USER;
        role_assign($manager_role->id, $USER->id, $syscontext->id);

        $repo->get_other_capabilities($capabilities);

        foreach ($capabilities as $capability => $permission) {
            $this->assertTrue($permission, $capability.' is not detected');
        }
    }
}
