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

global $CFG;

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/repository/elis_files/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/tests/constants.php');

/**
 * Tests for the utility methods
 * @group repository_elis_files
 */
class repository_elis_files_utility_methods_testcase extends elis_database_test {
    /** @var object $repo repository instance */
    public static $repo = null;
    /** @var string $fileuuid file unique id */
    public static $fileuuid = null;

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_user_account_data2.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
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
     * This methods does the initial work initializing the repository
     */
    public function init_repo() {
        $repo = new repository_elis_files('elis_files', SYSCONTEXTID,
                array('ajax' => false, 'name' => 'bogus', 'type' => 'elis_files'));
        $filename = self::generate_temp_file(1);

        $uploadresponse = elis_files_upload_file('', $filename, $repo->elis_files->uuuid);
        unlink($filename);
        self::$fileuuid = ($uploadresponse && !empty($uploadresponse->uuid)) ? $uploadresponse->uuid : '';
        self::$repo = $repo;
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
        parent::tearDown();
        $this->cleanup_files();
    }

    public function cleanup_files($uuid = '') {
        if (empty($uuid) &&
                ($node = self::$repo->elis_files->get_parent(self::$fileuuid))
                && !empty($node->uuid)) {
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
        return array(
                array(self::$repo->elis_files->muuid, 'Company Home', 'muuid'),
                array(self::$repo->elis_files->suuid, 'moodle', 'suuid'),
                array(self::$repo->elis_files->cuuid, 'moodle', 'cuuid'),
                array(self::$repo->elis_files->uuuid, 'User Homes', 'uuid'),
                array(self::$repo->elis_files->ouuid, 'moodle', 'ouuid'),
                array(self::$fileuuid, USERS_HOME, 'fileuuid'),
        );
    }

    /**
     * Test that info is returned for the root uuid
     * @uses $USER
     */
    public function test_get_parent() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->init_repo();

        global $USER;

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

            $uuid = self::$repo->elis_files->suuid;
            $node = self::$repo->elis_files->get_parent($childuuid);

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
     * @uses $USER
     */
    public function test_get_parent_path_from_tree() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->init_repo();

        global $USER;

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

            $foldertree = elis_files_folder_structure();
            $resultpath = array();
            self::$repo->get_parent_path_from_tree($childuuid, $foldertree, $resultpath, 0, 0, false, 0);

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
}
