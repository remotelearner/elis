<?php
/**
 * Alfresco CMIS REST interface API for Alfresco version 3.2 / 3.4
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package    elis
 * @subpackage curriculummanagement
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
require_once(dirname(__FILE__).'/../lib/lib.php');

/**
 * Class for testing the the method that converts a folder name to a Moodle user
 * id works correctly
 */
class folderToUseridTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array(
            'config'         => 'moodle',
            'config_plugins' => 'moodle',
            'user'           => 'moodle'
        );
    }

    /**
     * Data provider for testing the folder-to-userid conversion
     *
     * @return array The data as expected by the testing method
     */
    function folder_to_userid_provider() {
        return array(
            array('user_AT_domain', 1, 'moodleadmin'),
            array('user', 2, 'moodleadmin'),
            array('bogus', false, 'moodleadmin'),
            array('moodleadmin', 3, 'moodleadmin'),
            array('moodleadmin_AT_domain', 3, 'moodleadmin@domain'),
            array('moodleadmin@domain', false, 'moodleadmin@domain')
        );
    }

    /**
     * Load Moodle users into the database from a pre-defined user CSV file
     */
    protected function load_csv_data() {
        // load initial data from a CSV file
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/folderusers.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Validate that the given user name is converted to the provided user id
     *
     * @dataProvider folder_to_userid_provider
     * @param string $folder_name The name of the ELIS Files folder
     * @param mixed $expected_userid The userid the method should return (or false
     *                               if no valid user exists)
     * @param string $admin_username The config value to use for the admin username setting
     */
    public function testMethodReturnsCorrectUserid($folder_name, $expected_userid, $admin_username) {
        // Load in our user data
        $this->load_csv_data();

        // Set up the configured admin username
        set_config('admin_username', $admin_username, 'elis_files');
        set_config('mnethostid', 1);
        elis::$config = new elis_config();

        // Validate method output
        $userid = elis_files_folder_to_userid($folder_name);
        $this->assertEquals($expected_userid, $userid);
    }
}