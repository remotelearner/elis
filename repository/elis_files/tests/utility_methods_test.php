<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage PHPUnit_tests
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__) .'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot .'/elis/core/lib/setup.php');
require_once($CFG->dirroot .'/lib/phpunittestlib/testlib.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot .'/repository/elis_files/lib.php');

define('ELIS_FILES_PREFIX', 'elis_files_test_utility_methods');
define('ONE_MB_BYTES', 1048576);
define('USERS_HOME', 'userhomedir');

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

class utilityMethodsTest extends elis_database_test {
    static $repo = null;
    static $file_uuid = null;

    protected $backupGlobalsBlacklist = array('USER');

    protected static function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle',
            'user' => 'moodle'
        );
    }

    public static function initRepo() {
        global $USER;
        if (!self::$repo) {
            $USER = get_test_user('admin');
            //var_dump($USER);

            self::$repo = new repository_elis_files('elis_files', SYSCONTEXTID,
                              array('ajax' => false, 'name' => 'bogus', 'type' =>'elis_files'));
            $filename = generate_temp_file(1);
            $uploadresponse = elis_files_upload_file('', $filename, self::$repo->elis_files->uuuid);
            unlink($filename);
            self::$file_uuid = ($uploadresponse && !empty($uploadresponse->uuid))
                               ? $uploadresponse->uuid : '';
        }
    }

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::initRepo();
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

        if (!self::$repo) {
            $this->markTestSkipped('Repository not configured or enabled');
        }
    }

    public static function cleanupfiles($uuid = '') {
        if (empty($uuid) &&
            ($node = self::$repo->elis_files->get_parent(self::$file_uuid)) &&
            !empty($node->uuid)) {
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

    public static function tearDownAfterClass() {
        self::cleanupfiles();
        parent::tearDownAfterClass();
    }

    public function get_parent_dataProvider() {
        self::initRepo();
        if (!self::$repo) {
            return array();
        }

        return array(
            array(self::$repo->elis_files->muuid, 'Company Home'),
            array(self::$repo->elis_files->suuid, 'moodle'),
            array(self::$repo->elis_files->cuuid, 'moodle'),
            array(self::$repo->elis_files->uuuid, 'User Homes'),
            array(self::$repo->elis_files->ouuid, 'moodle'),
            array(self::$file_uuid, USERS_HOME),
        ); 
    }

    /**
     * Test that info is returned for the root uuid
     * @dataProvider get_parent_dataProvider
     */
    public function test_get_parent($child_uuid, $parent_name) {
        global $USER;
        // Look for the Company Home folder
        if (empty($child_uuid)) {
            $this->markTestSkipped('Warning: Data provider missing uuid!');
        }
        $uuid = self::$repo->elis_files->suuid;
        $node = self::$repo->elis_files->get_parent($child_uuid);
        //mtrace("test_get_parent({$child_uuid}, '{$parent_name}') = ");
        //var_dump($node);
        $this->assertTrue(!empty($node->uuid));
        if ($parent_name == USERS_HOME) {
            $parent_name = elis_files_transform_username($USER->username);
        }
        $this->assertEquals($parent_name, $node->title);
    }

    /**
     * Test broken method: get_parent_path_from_tree()
     * @dataProvider get_parent_dataProvider
     */
    public function test_get_parent_path_from_tree($child_uuid, $parent_name) {
        global $USER;
        if (empty($child_uuid)) {
            $this->markTestSkipped('Warning: Data provider missing uuid!');
        }
    
        $foldertree = elis_files_folder_structure();
        $result_path = array();
        self::$repo->get_parent_path_from_tree($child_uuid, $foldertree,
                                               $result_path, 0, 0, false, 0);
        //mtrace("PHPUnit::get_parent_path({$child_uuid} ...) => ");
        //var_dump($result_path);
        if ($parent_name != 'Company Home') {
            $this->assertTrue(!empty($result_path));
            if ($parent_name == USERS_HOME) {
                $parent_name = elis_files_transform_username($USER->username);
            }
            $this->assertEquals($parent_name, $result_path[count($result_path) -1]['name']);
        } else {
            $this->assertTrue(empty($result_path));
        }
    }

}
