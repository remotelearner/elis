<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2014 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

global $CFG;

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/tests/constants.php');

/**
 * Tests for the utility methods
 * @group repository_elisfiles
 */
class repository_elisfiles_utility_methods_testcase extends elis_database_test {
    /** @var string $fileuuid file unique id */
    public $fileuuid = null;

    /** @var string Set to the Moodle username of the test Alfresco user created. */
    protected static $testusercreated = '';

    /** @var object Store the initialized repository_elisfiles object. */
    protected $repo = null;

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
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_user_account_data2.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elisfiles')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * Generates a temp file
     * @uses $CFG
     * @param integer $mbs The file size (in MB) to generate.
     * @return string the the file name
     */
    public static function generate_temp_file($mbs) {
        global $CFG;

        $fname = tempnam($CFG->dataroot.'/temp/', ELIS_FILES_PREFIX);

        if (!$fh = fopen($fname, 'w+')) {
            error('Could not open temporary file');
        }

        $maxbytes = $mbs * ONE_MB_BYTES;
        $data     = '';
        $fsize    = 0;

        for ($i = 0; $i < $mbs; $i++) {
            while ((strlen($data) < ONE_MB_BYTES) && ((strlen($data) + $fsize) < $maxbytes)) {
                $data .= 'a';
            }

            fwrite($fh, $data);
            $fsize += strlen($data);
        }
        fclose($fh);
        return $fname;
    }

    /**
     * This method does the initial work initializing the repository
     */
    public function init_repo() {
        global $USER, $SESSION;

        // Check if Alfresco is enabled, configured and running first
        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        if (!$repo = new repository_elisfiles('elisfiles', context_system::instance(), $options)) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        // Check if we need to create a user and then force the repository connection to be reinitialized.
        if (empty($repo->elis_files->uuuid)) {
            $USER->email = 'noreply@example.org';
            $this->assertTrue($repo->elis_files->migrate_user($USER, 'temppass'));
            unset($SESSION->repo);
            self::$testusercreated = $USER->username;
            $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);
        }

        $filename = self::generate_temp_file(1);
        $uploadresponse = elis_files_upload_file('', $filename, $repo->elis_files->uuuid);

        unlink($filename);
        $this->fileuuid = ($uploadresponse && !empty($uploadresponse->uuid)) ? $uploadresponse->uuid : '';

        $this->repo = $repo;
    }

    /**
     * This function initializes all of the setup steps required by each step.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();
        $this->setup_test_data_xml();
        $this->init_repo();
    }

    /**
     * This function removes any initialized data.
     */
    protected function tearDown() {
        $this->cleanup_files();
        parent::tearDown();
    }

    /**
     * Delete a test user created during this class.
     */
    public static function tearDownAfterClass() {
        if (!empty(self::$testusercreated)) {
            elis_files_delete_user(self::$testusercreated, true);
        }

        parent::tearDownAfterClass();
    }

    /**
     * Remove temporary files that were created on the Alfresco instance during testing.
     *
     * @param string $uuid The UUID of the directory where test files were uploaded (optional).
     */
    public function cleanup_files($uuid = '') {
        if (empty($this->fileuuid)) {
            return;
        }

        if (empty($uuid) && ($node = $this->repo->elis_files->get_parent($this->fileuuid)) && !empty($node->uuid)) {
            $uuid = $node->uuid;
        } else {
            return;
        }

        if ($dir = elis_files_read_dir($uuid)) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }
    }

    /**
     * This method originally was a data provider, however when running the test there were errors
     * when the static properties werw referenced.  Now it is just a regular function that is called
     * by each individual test
     * @return array and array of array test data
     */
    public function get_parent_data_provider() {
        $sources =  array(
                array($this->repo->elis_files->muuid, 'Company Home', 'muuid'),
                array($this->repo->elis_files->suuid, 'moodle', 'suuid'),
                array($this->repo->elis_files->cuuid, 'moodle', 'cuuid'),
                array($this->repo->elis_files->uuuid, 'User Homes', 'uuid')
        );

        // If we successfully uploaded a temp. file during setup then use that for testing.
        if (!empty($this->fileuuid)) {
           $sources[] = array($this->fileuuid, USERS_HOME, 'fileuuid');
        }

        // This will only be set if local_elisprogram is present.
        if (!empty($this->repo->elis_files->ouuid)) {
            $sources[] = array($this->repo->elis_files->ouuid, 'moodle', 'ouuid');
        }

        return $sources;
    }

    /**
     * Test that info is returned for the root uuid
     */
    public function test_get_parent() {
        global $USER;

        $this->resetAfterTest(true);

        $dataset = $this->get_parent_data_provider();
        $i = 0;

        foreach ($dataset as $data) {
            $childuuid = $data[0];
            $parentname = $data[1];

            // This condition was added to test if the uuid was false, assert for a false value and then report on the error
            // The idea is to mimic a data provider method where by the test will continue if there is one false assertion
            if (empty($childuuid)) {
                $this->assertFalse($childuuid, 'Data set#'.$i.': '.$data[2].' in parent '.$data[1].' is false ');
                $i++;
                continue;
            }

            $uuid = $this->repo->elis_files->suuid;
            $node = $this->repo->elis_files->get_parent($childuuid);

            $this->assertTrue(!empty($node->uuid), 'Data set#'.$i.' childuuid = '.$childuuid);
            if ($parentname == USERS_HOME) {
                $parentname = elis_files_transform_username($USER->username);
            }
            $this->assertEquals($parentname, $node->title, 'Data set#'.$i);
            $i++;
        }
    }

    /**
     * Test broken method: get_parent_path_from_tree()
     */
    public function test_get_parent_path_from_tree() {
        global $USER;

        $this->resetAfterTest(true);

        $dataset = $this->get_parent_data_provider();
        $i = 0;

        foreach ($dataset as $data) {
            $childuuid = $data[0];
            $parentname = $data[1];

            // This condition was added to test if the uuid was false, assert for a fals value and then report on the error
            // The idea is to mimic a data provider method where by the test will continue if there is one false assertion
            if (empty($childuuid)) {
                $this->assertFalse($childuuid, 'Data set#'.$i.': '.$data[2].' in parent '.$data[1].' is false ');
                $i++;
                continue;
            }

            $foldertree = elis_files_folder_structure();
            $resultpath = array();
            $this->repo->get_parent_path_from_tree($childuuid, $foldertree, $resultpath, 0, 0, false, 0);

            if ($parentname != 'Company Home') {
                $this->assertTrue(!empty($resultpath), 'Data set#'.$i.' childuuid = '.$childuuid);
                if ($parentname == USERS_HOME) {
                    $parentname = elis_files_transform_username($USER->username);
                }
                $this->assertEquals($parentname, $resultpath[count($resultpath) -1]['name'], 'Data set#'.$i);
            } else {
                $this->assertTrue(empty($resultpath), 'Data set#'.$i);
            }
            $i++;
        }
    }

    /**
     * Test link conversions in database.
     */
    public function test_linkconversionsindatabase() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/lib/adminlib.php');

        $this->resetAfterTest(true);

        $data = new stdClass;
        $data->fullname = 'Test Course';
        $data->shortname = 'testcourse';
        $data->category = 1;
        $data->summary = '<a href="http://localhost/repository/alfresco/openfile.php?uuid=1">test1</a>'.
                '<a href="http://localhost/repository/elis_files/openfile.php?uuid=2">test2</a>';
        $course = $this->getDataGenerator()->create_course((array) $data);

        ob_start();
        $ignoreresult = elis_files_update_references_in_database();
        ob_end_clean();

        $expected = '<a href="http://localhost/repository/elisfiles/openfile.php?uuid=1">test1</a>'.
                '<a href="http://localhost/repository/elisfiles/openfile.php?uuid=2">test2</a>';

        $record = $DB->get_record('course', array('id' => $course->id));
        $this->assertEquals($expected, $record->summary);
    }
}
