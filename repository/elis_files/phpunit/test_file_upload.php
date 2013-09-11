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

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');


define('ONE_MB_BYTES', 1048576);
define('ELIS_FILES_PREFIX', 'elis_files_test_file_upload_');


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

    protected static function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle'
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
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->files as $file) {
                if (strpos($file->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }

        parent::tearDown();
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
        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        $filesize = $mb * ONE_MB_BYTES;
        $filename = generate_temp_file($mb);

        $response = elis_files_upload_file('', $filename);

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

        $response = elis_files_upload_file('', $filename);

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

        $filename = generate_temp_file(1);

        $response = elis_files_upload_file('', $filename);

        unlink($filename);

        $this->assertNotEquals(false, $response);
        $this->assertObjectHasAttribute('uuid', $response);
    }
}
