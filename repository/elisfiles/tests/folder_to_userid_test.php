<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
require_once(dirname(__FILE__).'/../lib/lib.php');

/**
 * Class for testing the the method that converts a folder name to a Moodle user id works correctly
 * @group repository_elisfiles
 */
class repository_elisfiles_folder_to_userid_testcase extends elis_database_test {
    /**
     * Data provider for testing the folder-to-userid conversion.
     * @return array The data as expected by the testing method
     */
    public function folder_to_userid_provider() {
        return array(
                array('user_AT_domain', 99, 'moodleadmin'),
                array('user', 100, 'moodleadmin'),
                array('bogus', false, 'moodleadmin'),
                array('moodleadmin', 2, 'moodleadmin'),
                array('moodleadmin_AT_domain', 2, 'moodleadmin@domain'),
                array('moodleadmin@domain', false, 'moodleadmin@domain')
        );
    }

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_user_account_data.xml'));
    }

    /**
     * Validate that the given user name is converted to the provided user id.
     * @dataProvider folder_to_userid_provider
     * @param string $foldername The name of the ELIS Files folder
     * @param mixed $expecteduserid The userid the method should return (or false if no valid user exists)
     * @param string $adminusername The config value to use for the admin username setting
     */
    public function test_method_returns_correct_userid($foldername, $expecteduserid, $adminusername) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        // Set up the configured admin username
        set_config('admin_username', $adminusername, 'elisfiles');
        set_config('mnethostid', 1);
        elis::$config = new elis_config();

        // Validate method output
        $userid = elis_files_folder_to_userid($foldername);
        $this->assertEquals($expecteduserid, $userid);
    }
}