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
 * @package    repository_elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');

/**
 * Tests for the "migrate_all_users" method
 * @group repository_elis_files
 */
class repository_elis_files_migrate_all_users_testcase extends elis_database_test {
    /**
     * This function loads data into the PHPUnit tables for testing
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config2.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config2.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_user_account_data2.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * Validate that the "migrate_all_users" method correctly deletes legacy
     * @uses $CFG, $DB
     * numeric user directories
     */
    public function test_migrate_all_users_handles_deleted_users() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $CFG, $DB;

        $repo = repository_factory::factory('elis_files');

        // Our test username
        $username = '__phpunit_test1__';

        // Set up the user in Alfresco
        $elisfiles = new ELIS_files();
        $elisfiles->migrate_user($username);

        // Validate that the user exists and that their home directory was set up
        // (for sanity reasons only)
        $userexists = elis_files_request('/api/people/'.$username);
        $this->assertNotEquals(false, $userexists);

        $initialuserhome = elis_files_get_home_directory($username);
        $this->assertNotEquals(false, $initialuserhome);

        // Change the node name to the "old" style
        $test = elis_files_node_rename($initialuserhome, '100');

        // Run the migration method
        $usr = new stdClass();
        $usr->id = 100;
        $usr->deleted = 1;
        $DB->update_record('user', $usr);

        $elisfiles->migrate_all_users();

        // Validate cleanup for the legacy folder
        $legacyuuid = false;
        $dir = elis_files_read_dir($elisfiles->uhomesuid, true);
        foreach ($dir->folders as $folder) {
            if ($folder->title == '100') {
                $legacyuuid = $folder->uuid;
            }
        }

        // Clean up the non-legacy data before final validation
        $elisfiles->delete_user($username);

        $this->assertEquals(false, $legacyuuid);
    }
}
