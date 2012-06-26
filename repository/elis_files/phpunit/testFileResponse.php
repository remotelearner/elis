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

define('ELIS_FILES_TEST_FILE', 'elis_files_test_file_response.txt');
define('ELIS_FILES_TEST_STRING', 'This is a test text file.');

class fileresponseTest extends elis_database_test {

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
                if (strpos($file->title, ELIS_FILES_TEST_FILE) === 0) {
                    elis_files_delete($file->uuid);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Test that uploading a file generates a valid response
     */
    public function testUploadAndGetResponse() {
        global $CFG;

        // Check if Alfresco is enabled, configured and running first
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Repository not configured or enabled');
        }

        $filename = ELIS_FILES_TEST_FILE;
        $path = $CFG->dirroot.'/repository/elis_files/phpunit/';

        $uploadresponse = elis_files_upload_file('', $path.$filename);

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
        // Verify that title the same as the file name
        $this->assertEquals(ELIS_FILES_TEST_FILE, $response->title);
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
