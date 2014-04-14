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

global $CFG;

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elisfiles/tests/constants.php');

/**
 * Class to test search
 * @group repository_elisfiles
 */
class repository_elisfiles_search_testcase extends elis_database_test {
    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(__DIR__.'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You must define elis_files_config.xml inside '.__DIR__.
                    '/fixtures/ directory to execute this test.');
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elisfiles')) {
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
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->folders as $folder) {
                if (strpos($folder->title, FOLDER_NAME_PREFIX) === 0) {
                    elis_files_delete($folder->uuid);
                    break 1;
                }
            }
        }
        parent::tearDown();
    }

    /**
     * Test that searching for folders does not return results
     * @uses $CFG, $DB
     */
    public function test_folder_search() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $data = null;
        $listing = null;

        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        if (!$repo = new repository_elisfiles('elisfiles', context_system::instance(), $options)) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        $parentfolderuuid = $repo->elis_files->get_root()->uuid;

        $folder = FOLDER_NAME_PREFIX.'1';
        $repo->elis_files->create_dir($folder, $parentfolderuuid, '', true);

        $result = $repo->search($folder);

        $this->assertEmpty($result['list']);
    }
}
