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
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');

/**
 * Tests for the user synchronisation
 * @group repository_elis_files
 */
class repository_elis_files_file_user_synchronisation_testcase extends elis_database_test {
    /** @var array $userstodelete an array of users to delete */
    private $userstodelete;

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_user_account_data3.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * This function initializes all of the setup steps required by each step.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * This function removes any initialized data.
     */
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
     * Test the user migration functionality for creating a user by passing a string containing the username.
     */
    public function test_migrate_user_as_string() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $repo = repository_factory::factory('elis_files');

        $this->userstodelete[] = '__phpunit_test1__';

        $this->assertTrue($repo->migrate_user('__phpunit_test1__', 'passwords'));
    }

    /**
     * Test the user migration functionality using an invalid user object
     * @uses $DB
     */
    public function test_migrate_invalid_user_as_object() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB;

        $repo = repository_factory::factory('elis_files');

        $this->userstodelete[] = '__phpunit_test1__';

        $user = $DB->get_record('user', array('id' => 100));

        $this->assertTrue($repo->migrate_user($user, 'password'));
    }
}
