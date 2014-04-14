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
 * Tests for the utility methods
 * @group repository_elisfiles
 */
class repository_elisfiles_folder_path_testcase extends elis_database_test {
    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(__DIR__.'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You must define elis_files_config.xml and elis_files_instance.xml inside '.
                    __DIR__.'/fixtures/ directory to execute this test.');
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_permissions_test_data.xml'));

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
     * This method is called after the last test of this test class is run.
     * This method overrides the parent class and the name must not change to meet
     * style code
     */
    public static function tearDownAfterClass() {
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->folders as $folder) {
                if (strpos($folder->title, FOLDER_NAME_PREFIX) === 0) {
                    elis_files_delete($folder->uuid);
                    break 1;
                }
            }
        }

        parent::tearDownAfterClass();
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
     * Test that both get_parent and elis_files_folder_structure return the same path
     * @uses $CFG, $DB
     */
    public function test_parent_and_tree_structure_same() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);

        // Make sure we connected to the repository successfully.
        if (empty($repo->elis_files)) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // create folder, get uuid, get path via get_parent_path and elis_files_folder structure
        // for first folder, create under moodle, then create under the previous folder...
        $parentfolderuuid = $repo->elis_files->get_root()->uuid;
        for ($i = 1; $i <= 20; $i++) {
            $currentfolder = FOLDER_NAME_PREFIX.$i;
            $currentfolderuuid = $repo->elis_files->create_dir($currentfolder, $parentfolderuuid, '', true);

            // get_parent recursive  get_parent_path test
            $recursivepath = array();
            $repo->get_parent_path($currentfolderuuid, $recursivepath, 0, 0, 0, 0, 'parent');

            // elis_files_folder_structure get_parent_path test
            $folders = elis_files_folder_structure();
            $altrecursivepath = array();
            $repo->get_parent_path($currentfolderuuid, $altrecursivepath, 0, 0, 0, 0);

            $this->assertEquals($recursivepath, $altrecursivepath);

            // for nested folders
            $parentfolderuuid = $currentfolderuuid;
        }
    }

    /**
     * Test times for get_parent using recursive get_parent alfresco calls
     * @uses $CFG, $DB
     */
    public function test_get_parent_path_parent() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);

        // Make sure we connected to the repository successfully.
        if (empty($repo->elis_files)) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // set up the storage for the full path of the path's UUIDs to validate against
        $expectedpath = array();

        // create folder, get uuid, get path via get_parent_path and elis_files_folder structure
        // for first folder, create under moodle, then create under the previous folder...
        $parentfolderuuid = $repo->elis_files->get_root()->uuid;
        $times = array();
        for ($i = 1; $i <= 20; $i++) {
            $currentfolder = FOLDER_NAME_PREFIX.$i;

            $currentfolderuuid = $repo->elis_files->create_dir($currentfolder, $parentfolderuuid, '', true);

            // add the parent folder to our expected sequence of UUIDs
            $expectedpath[] = repository_elisfiles::build_encodedpath($parentfolderuuid);

            // get_parent recursive  get_parent_path test
            $starttime = microtime();
            $recursivepath = array();
            $repo->get_parent_path($currentfolderuuid, $recursivepath, 0, 0, 0, 0, 'parent');
            $recursive_time = microtime_diff($starttime, microtime());

            // validate the count
            $this->assertEquals($i, count($recursivepath));
            // validate the encoded folder UUIDs

            // look over the expected path parts
            foreach ($expectedpath as $pathindex => $expectedpart) {
                // obtain the matching part from the actual return value
                $resultpart = $recursivepath[$pathindex];
                $this->assertEquals($expectedpart, $resultpart['path']);
            }

            // NOTE: add this back in if we are testing performance
            // $times[] = "Folder: $currentfolder and time: $recursive_time";
            // for nested folders
            $parentfolderuuid = $currentfolderuuid;
        }
    }

    /**
     * Test times for get_parent using recursive get_parent alfresco calls
     * @uses $CFG, $DB
     */
    public function test_get_parent_path_tree() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);

        // Make sure we connected to the repository successfully.
        if (empty($repo->elis_files)) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // set up the storage for the full path of the path's UUIDs to validate against
        $expectedpath = array();

        // create folder, get uuid, get path via get_parent_path and elis_files_folder structure
        // for first folder, create under moodle, then create under the previous folder...
        $parentfolderuuid = $repo->elis_files->get_root()->uuid;
        $times = array();
        for ($i = 1; $i <= 20; $i++) {
            $currentfolder = FOLDER_NAME_PREFIX.$i;

            $currentfolderuuid = $repo->elis_files->create_dir($currentfolder, $parentfolderuuid, '', true);

            // add the parent folder to our expected sequence of UUIDs
            $expectedpath[] = repository_elisfiles::build_encodedpath($parentfolderuuid);

            // elis_files_folder_structure get_parent_path test
            $starttime = microtime();
            $folders = elis_files_folder_structure();
            $altrecursivepath = array();
            $repo->get_parent_path($currentfolderuuid, $altrecursivepath, 0, 0, 0, 0, 'tree');
            $endtime = time();
            $structuretime = microtime_diff($starttime, microtime());

            // validate the count
            $this->assertEquals($i, count($altrecursivepath));
            // validate the encoded folder UUIDs

            // look over the expected path parts
            foreach ($expectedpath as $pathindex => $expectedpart) {
                // obtain the matching part from the actual return value
                $resultpart = $altrecursivepath[$pathindex];
                $this->assertEquals($expectedpart, $resultpart['path']);
            }

            // NOTE: add this back in if we are testing performance
            $times[] = $times[] = "Folder: $currentfolder and time: $structuretime";

            // or nested folders
            $parentfolderuuid = $currentfolderuuid;
        }
    }
}
