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


class file_uploadTest extends PHPUnit_Framework_TestCase {

    protected function setUp() {
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
            array(1024)
        );
    }

    /**
     * This test validates that the test file generator is creating files of the correct size
     *
     * @dataProvider fileSizeProvider
     */
/*
    public function testGenerateTempFile($mb) {
        $filesize = $mb * ONE_MB_BYTES;
        $filename = generate_temp_file($mb);

        $this->assertEquals($filesize, filesize($filename));

        unlink($filename);
    }
*/
    /**
     * Test uploading files
     *
     * @dataProvider fileSizeProvider
     */
    public function testUploadFile($mb) {
        global $CFG;

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        $filesize = $mb * ONE_MB_BYTES;
        $filename = generate_temp_file($mb);

        $response = elis_files_upload_file('', $filename);

        unlink($filename);

        $this->assertObjectHasAttribute('uuid', $response);
    }

}
