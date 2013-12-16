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

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/tests/constants.php');

/**
 * Class for testing for duplicate files
 * @group repository_elis_files
 */
class repository_elis_files_file_duplicate_testcase extends elis_database_test {
    /**
     * This function generates a temporary file for testing.
     * @uses $CFG
     * @param int $mbs The file size (in MB) to generate.
     * @return string the temp file name.
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
     * This function loads data into the PHPUnit tables for testing
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
     * A function to do the initial setup work
     * @uses $GLOBAL, $USER, $DB
     */
    protected function setUp() {
        global $DB;
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Take down any temporary setup data
     */
    protected function tearDown() {
        $this->cleanupfiles();
        parent::tearDown();
    }

    /**
     * Remove temp files
     * @param string $uuid a unique id
     */
    public function cleanupfiles($uuid='') {
        if ($dir = elis_files_read_dir($uuid)) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }
    }

    /**
     * Test that uploading a duplicate file handles overwrites.
     * @uses $CFG, $_POST
     */
    public function test_overwrite_duplicate() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $CFG, $_POST;

        $this->resetAfterTest(true);
        $this->markTestSkipped('elis_files_handle_duplicate_file() - removed');

        $repo = repository_factory::factory('elis_files');

        // Generate a file
        $filesize = 1 * ONE_MB_BYTES;
        $filename = $this->generate_temp_file(1);

        $uploadresponse = elis_files_upload_file('', $filename);

        // Set overwrite param
        $_POST['overwrite'] = true;

        // Setup filemeta
        $pathparts = pathinfo($filename);
        $filemeta = new stdClass;
        $filemeta->name = $pathparts['basename'];
        $filemeta->filepath = $CFG->dataroot.'/temp/';
        $filemeta->type = mime_content_type($filename);
        $filemeta->size = $filesize;
        // We need the uuid of the file to send to the elis_files_handle_duplicate function
        $duplicateresponse = elis_files_handle_duplicate_file('', $filename, '', $uploadresponse->uuid, '', $filemeta);

        if (file_exists($filename)) {
            unlink($filename);
        }

        // Verify that we get a valid response
        $this->assertNotEquals(false, $duplicateresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $duplicateresponse);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($duplicateresponse->uuid);

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
     * Test that uploading a duplicate file handles overwrites
     * @uses $CFG, $_POST
     */
    public function test_rename_duplicate() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $CFG, $_POST;

        $this->resetAfterTest(true);
        $this->markTestSkipped('elis_files_handle_duplicate_file() - removed');

        $repo = repository_factory::factory('elis_files');

        // Generate a file
        $filesize = 1 * ONE_MB_BYTES;
        $filename = $this->generate_temp_file(1);

        $uploadresponse = elis_files_upload_file('', $filename);

        // setup filemeta
        $path_parts = pathinfo($filename);
        $filemeta = new stdClass;
        $filemeta->name = $path_parts['basename'];
        $filemeta->filepath = $CFG->dataroot.'/temp/';
        $filemeta->type = mime_content_type($filename);
        $filemeta->size = $filesize;
        // Generate a duplicate filename
        $listing = new stdClass;
        $listing->files = array((object)array('title' => $filemeta->name,
                                              'uuid'  => true));
        $newfilename =  elis_files_generate_unique_filename($filemeta->name, $listing);

        // we need the uuid of the file to send to the elis_files_handle_duplicate function
        $duplicateresponse = elis_files_handle_duplicate_file('', $filename, '', $uploadresponse->uuid, $newfilename, $filemeta);

        if (file_exists($filename)) {
            unlink($filename);
        }
        if (file_exists($filemeta->filepath.$newfilename)) {
            unlink($filemeta->filepath.$newfilename);
        }
        // Verify that we get a valid response
        $this->assertNotEquals(false, $duplicateresponse);
        // Verify that response has a uuid
        $this->assertObjectHasAttribute('uuid', $duplicateresponse);

        // Check that the uuid returned exists
        $node = elis_files_node_properties($duplicateresponse->uuid);

        // Verify that the node title is the same as the new filename
        $this->assertEquals($newfilename, $node->title);

        // Get info on the uploaded file's uuid...
        $response = $repo->get_info($duplicateresponse->uuid);

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
