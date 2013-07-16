<?php
/**
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

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');

/**
 * Tests for the "migrate_all_users" method
 */
class migrateAllUsersTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle',
            'user'           => 'moodle'
        );
    }

    /**
     * Set up a test user from CSV
     */
    private function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/mdluser.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Validate that the "migrate_all_users" method correctly deletes legacy
     * numeric user directories
     */
    public function testMigrateAllUsersHandlesDeletedUsers() {
        global $CFG, $DB;

        // Set the configuration to specify whether to remove the user's home
        // directory when deleting them, for cleanup convenience
        $DB->execute("INSERT INTO {config_plugins}
                      SELECT * FROM ".self::$origdb->get_prefix().'config_plugins
                      WHERE plugin = ?', array('elis_files'));
        set_config('deleteuserdir', 1, 'elis_files');

        // Set up the user in Moodle
        $this->load_csv_data();
        $DB->execute('UPDATE {user} SET mnethostid = ?', array($CFG->mnet_localhost_id));

        // Our test username
        $username = '__phpunit_test1__';

        // Set up the user in Alfresco
        $elis_files = new ELIS_files();
        $elis_files->migrate_user($username);

        // Validate that the user exists and that their home directory was set up
        // (for sanity reasons only)
        $user_exists = elis_files_request('/api/people/'.$username);
        $this->assertNotEquals(false, $user_exists);

        $initial_user_home = elis_files_get_home_directory($username);
        $this->assertNotEquals(false, $initial_user_home);

        // Change the node name to the "old" style
        $test = elis_files_node_rename($initial_user_home, '100');

        // Run the migration method
        $DB->execute('UPDATE {user} SET deleted = 1');
        $elis_files->migrate_all_users();

        // Validate cleanup for the legacy folder
        $legacy_uuid = false;
        $dir = elis_files_read_dir($elis_files->uhomesuid, true);
        foreach ($dir->folders as $folder) {
            if ($folder->title == '100') {
                $legacy_uuid = $folder->uuid;
            }
        }

        // Clean up the non-legacy data before final validation
        $elis_files->delete_user($username);

        $this->assertEquals(false, $legacy_uuid);
    }
}