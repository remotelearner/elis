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
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/tests/constants.php');

/**
 * Class for testing getting a response about a file from the alfresco server.
 * @group repository_elisfiles
 */
class repository_elisfiles_file_response_testcase extends elis_database_test {
    /**
     * This function create a temp file used by other tests.
     * @uses $CFG
     * @param int $mbs The file size (in MB) to generate
     * @return string the file name
     */
    protected function generate_temp_file($mbs) {
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
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(__DIR__.'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You must define elis_files_config.xml inside '.__DIR__.
                    '/fixtures/ directory to execute this test.');
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));

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
        $this->cleanup_files();
        parent::tearDown();
    }

    /**
     * This function removes files that are no longer needed.
     * @param string $uuid a unique file id
     */
    public function cleanup_files($uuid = '') {
        if ($dir = elis_files_read_dir($uuid)) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }
    }

    /**
     * This function returns an array of sample data.
     * @return array An an array of arrays of integers
     */
    public function file_size_provider() {
        return array(
            array(32)
        );
    }

    /**
     * Test that uploading a file generates a valid response.
     * @dataProvider file_size_provider
     */
    public function test_upload_and_get_response($mb) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $repo = repository_factory::factory('elisfiles');

        // Used data provider to just generate one file
        $filesize = $mb * ONE_MB_BYTES;
        $filename = $this->generate_temp_file($mb);

        $uploadresponse = elis_files_upload_file('', $filename);

        unlink($filename);

        // Verify that we get a valid response
        $this->assertNotEquals(false, $uploadresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $uploadresponse);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($uploadresponse->uuid);

        // Verify that response has a type
        $this->assertObjectHasAttribute('type', $response);
        // Verify that type is folder
        $this->assertEquals(ELIS_files::$type_document, $response->type);
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

    /**
     * Test that uploading a file to a specific folder generates a valid response
     * @dataProvider file_size_provider
     */
    public function test_upload_to_folder_and_get_response($mb) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $repo = repository_factory::factory('elisfiles');

        // Used data provider to just generate one file
        $filesize = $mb * ONE_MB_BYTES;
        $filename = $this->generate_temp_file($mb);

        // Upload to a folder
        $uploadresponse = elis_files_upload_file('', $filename, $repo->muuid);

        unlink($filename);

        // Verify that we get a valid response
        $this->assertNotEquals(false, $uploadresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $uploadresponse);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($uploadresponse->uuid);

        // Cleanup the uploaded file
        $this->cleanup_files($repo->muuid);

        // Verify that response has a type
        $this->assertObjectHasAttribute('type', $response);
        // Verify that type is folder
        $this->assertEquals(ELIS_files::$type_document, $response->type);
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
