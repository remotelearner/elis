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
 * Class for testing the uploading of files
 * @group repository_elis_files
 */
class repository_elis_files_folder_response_testcase extends elis_database_test {
    /**
     * This function initializes all of the setup steps required by each step.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * Test that info is returned for the root uuid
     */
    public function test_get_folder_response() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $repo = repository_factory::factory('elis_files');

        // Look for the Company Home folder
        $uuid = $repo->root->uuid;

        $response = $repo->get_info($uuid);

        // Verify that we get a valid response
        $this->assertNotEquals(false, $response);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $response);
        // Verify that the correct uuid is returned
        $this->assertEquals($repo->root->uuid, $response->uuid);
        // Verify that response has a type
        $this->assertObjectHasAttribute('type', $response);
        // Verify that type is folder
        $this->assertEquals(ELIS_files::$type_folder, $response->type);
        // Verify that title is set
        $this->assertObjectHasAttribute('title', $response);
        // Verify that created is set
        $this->assertObjectHasAttribute('created', $response);
        // Verify that modified is set
        $this->assertObjectHasAttribute('modified', $response);
        // Verify that summary is set
        $this->assertObjectHasAttribute('summary', $response);
        // Verify that Owner is set
        $this->assertObjectHasAttribute('owner', $response);
    }
}
