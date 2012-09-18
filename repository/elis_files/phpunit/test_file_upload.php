<?php
/**
 *
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
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/lib.php');


define('ONE_MB_BYTES', 1048576);
define('ELIS_FILES_PREFIX', 'elis_files_test_file_upload_');
define('FILE_NAME_PREFIX', 'elis_files_test_file_type_upload');

/**
 *
 * @param integer $mbs The file size (in MB) to generate.
 */
function generate_temp_file($mbs) {
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


class file_uploadTest extends elis_database_test {
    protected $created_uuids = array();

    protected static function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle'
        );
    }

    protected static function get_ignore_tables() {
        return array(
            'repository' => 'moodle',
            'repository_instance' => 'moodle'
        );
    }

    protected function setUp() {
        parent::setUp();

        $rs = self::$origdb->get_recordset('config_plugins', array('plugin' => 'elis_files'));

        if ($rs->valid()) {
            foreach ($rs as $setting) {
                self::$overlaydb->import_record('config_plugins', $setting);
            }
            $rs->close();
        }

        $USER = get_admin();
        $GLOBALS['USER'] = $USER;
    }

    protected function tearDown() {
        foreach ($this->created_uuids as $uuid) {
            elis_files_delete($uuid);
        }
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0 ||
                    strpos($file->title, FILE_NAME_PREFIX) === 0 ) {
                    elis_files_delete($file->uuid);
                }
            }
        }
        parent::tearDown();
    }

    protected function call_upload_file($repo, $upload, $path, $uuid) {
        $response = elis_files_upload_file($upload, $path, $uuid);
        if ($response && !empty($response->uuid)) {
            $this->created_uuids[] = $response->uuid;
            $node = elis_files_get_parent($response->uuid);
            $this->assertTrue($node && !empty($node->uuid));
            $this->assertEquals($uuid, $node->uuid);
        }
        return $response;
    }

    public function fileSizeProvider() {
        return array(
            array(8),
            array(16),
            array(32),
            array(64),
            array(128),
            array(256),
            array(512),
            array(1024),
            array(2048),
        );
    }

//     /**
//      * This test validates that the test file generator is creating files of the correct size
//      *
//      * @dataProvider fileSizeProvider
//      */
/*
    public function testGenerateTempFile($mb) {
        $filesize = $mb * ONE_MB_BYTES;
        $filename = generate_temp_file($mb);

        $this->assertEquals($filesize, filesize($filename));

        unlink($filename);
    }
*/
    /**
     * Test uploading of files with progressively larger sizes
     *
     * @dataProvider fileSizeProvider
     */
    public function testUploadIncrementalFileSizes($mb) {
        $this->markTestSkipped('Not necessary to test incremental file sizes at this time.');

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        $filesize = $mb * ONE_MB_BYTES;
        $filename = generate_temp_file($mb);

        $response = $this->call_upload_file($repo, '', $filename, $repo->root->uuid);

        unlink($filename);

        $this->assertNotEquals(false, $response);
        $this->assertObjectHasAttribute('uuid', $response);
    }

    /**
     * Test uploading a file to Alfresco explicitly using the web services method
     */
    public function testUploadFileViaWs() {
        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        // Explicitly set the file transfer method to Web Services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elis_files');

        $filename = generate_temp_file(1);

        $response = $this->call_upload_file($repo, '', $filename, $repo->root->uuid);

        unlink($filename);

        $this->assertNotEquals(false, $response);
        $this->assertObjectHasAttribute('uuid', $response);
    }

    /**
     * Test uploading a file to Alfresco explicitly using the web services method
     */
    public function testUploadFileViaFTP() {
        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        // Explicitly set the file transfer method to FTP
        set_config('file_transfer_method', ELIS_FILES_XFER_FTP, 'elis_files');

        $targets = array($repo->root->uuid, $repo->muuid, $repo->suuid, $repo->cuuid,
                         $repo->uuuid, $repo->ouuid);
        foreach ($targets as $uuid) {
            $filename = generate_temp_file(1);
            $response = $this->call_upload_file($repo, '', $filename, $uuid);
            unlink($filename);
            $this->assertNotEquals(false, $response);
            $this->assertObjectHasAttribute('uuid', $response);
        }
    }

    public function fileExtensionsProvider() {
        return array(
            array('EMPTY'),
            array('c'),
            array('csv'),
            array('docx'),
            array('pdf'),
            array('png'),
            array('xml'),        );
    }
    /**
     * Test uploading a file to Alfresco explicitly using the web services method
     *
     * @dataProvider fileExtensionsProvider
     */
    public function testUploadFileTypesViaWs($extension) {
        global $CFG, $DB;

    // Check for ELIS_files repository
        if (file_exists($CFG->dirroot .'/repository/elis_files/')) {
            // RL: ELIS files: Alfresco
            $data = null;
            $listing = null;
            $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE r.type=? AND i.typeid=r.id';
            $repository = $DB->get_record_sql($sql, array('elis_files'));
            if ($repository) {
                try {
                    $repo = @new repository_elis_files('elis_files',
                                get_context_instance(CONTEXT_SYSTEM),
                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));
                } catch (Exception $e) {
                    $this->markTestSkipped();
               }
            } else {
                $this->markTestSkipped();
            }
        } else {
            $this->markTestSkipped();
        }

        // Explicitly set the file transfer method to Web Services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elis_files');

        // Handle the no extension test case
        $extension = ($extension == 'EMPTY') ? '' : '.'.$extension;

        $filename = $CFG->dirroot.'/repository/elis_files/phpunit/'.FILE_NAME_PREFIX.$extension;
        $response = $this->call_upload_file($repo, '', $filename, $repo->elis_files->root->uuid);

        //Download the file and compare contents
        $thefile = $repo->get_file($response->uuid);

        // Assert that the downloaded file is the same as the uploaded file
        $this->assertFileEquals($filename,$thefile['path']);
    }
}
