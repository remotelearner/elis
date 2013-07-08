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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Mock export plugin for testing exports
 */
class rlip_exportplugin_mock extends rlip_exportplugin_base {
    var $index;
    var $data;

    /**
	 * Default export plugin constructor
	 *
	 * @param object $fileplugin the file plugin used for output
	 * @param array $data the fixed data to include in the export
	 */
    function __construct($fileplugin, $data) {
        parent::__construct($fileplugin);

        $this->index = 0;
        $this->data = $data;
    }

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    function init($targetstarttime = 0, $lastruntime = 0) {
        //nothing to do
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    function has_next() {
        return $this->index < count($this->data);
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    function next() {
        $result = $this->data[$this->index];
        $this->index++;

        return $result;
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    function close() {
        //nothing to do
    }
}

/**
 * Mock file plugin for testing closing of output files
 */
class rlip_fileplugin_writememory extends rlip_fileplugin_base {
    var $entries;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
        $entries = array();
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        //nothing to do
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        $this->entries[] = $entry;
    }

    /**
     * Close the file
     */
    function close() {
        //nothing to do
    }

    /**
     * Specifies the data written to this plugin
     *
     * @return array All of the data written to this plugin
     */
    function get_data() {
        return $this->entries;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    function get_filename($withpath = false) {
        return 'writememory';
    }
}

/**
 * Mock file plugin for testing closing of files
 */
class rlip_fileplugin_outputclosed extends rlip_fileplugin_writememory {
    //track whether the file was closed
    var $closed = false;

    /**
     * Close the file
     */
    function close() {
        $this->closed = true;
    }

    /**
     * Specifies whether this file was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    function closed() {
        return $this->closed;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    function get_filename($withpath = false) {
        return 'outputclosed';
    }
}

/**
 * Mock export plugin containing no data that tracks the number of
 * times its methods are called
 */
class rlip_exportplugin_empty extends rlip_exportplugin_base {
    //track the number of times each of the methods were called
    var $num_init_calls = 0;
    var $num_has_next_calls = 0;
    var $num_next_calls = 0;
    var $num_close_calls = 0;

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    function init($targetstarttime = 0, $lastruntime = 0) {
        $this->num_init_calls++;
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    function has_next() {
        $this->num_has_next_calls++;

        return false;
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    function next() {
        $this->num_next_calls++;

        return array();
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    function close() {
        $this->num_close_calls++;
    }

    /**
     * Specifies the number of times the "init" method was called
     *
     * @return int the number of calls
     */
    function get_num_init_calls() {
        return $this->num_init_calls;
    }

    /**
     * Specifies the number of times the "has_next" method was called
     *
     * @return int the number of calls
     */
    function get_num_has_next_calls() {
        return $this->num_has_next_calls;
    }

    /**
     * Specifies the number of times the "next" method was called
     *
     * @return int the number of calls
     */
    function get_num_next_calls() {
        return $this->num_next_calls;
    }

    /**
     * Specifies the number of times the "close" method was called
     *
     * @return int the number of calls
     */
    function get_num_close_calls() {
        return $this->num_close_calls;
    }
}

/**
 * Class for testing the base export plugin class
 */
class exportPluginTest extends rlip_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('config'         => 'moodle',
                     'config_plugins' => 'moodle'
               );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip');
    }

    /**
     * Validate export file specifies RLIP_EXPORT_TEMPDIR as path
     */
    public function testExportFilenameInCorrectTempDir() {
        global $CFG;
        require_once($CFG->dirroot .'/blocks/rlip/lib.php');
        $plugin = 'test_rlipexport_version1';
        set_config('export_file', "/tmp/{$plugin}/{$plugin}.csv", $plugin);
        $export_filename = rlip_get_export_filename($plugin, 99);
        $target_path = $CFG->dataroot . sprintf(RLIP_EXPORT_TEMPDIR, $plugin);
        $this->assertEquals($target_path, dirname($export_filename) .'/');

        //clean-up created export temp dirs for this bogus plugin
        @rmdir(dirname($export_filename));
        @rmdir(dirname(dirname($export_filename)));
    }

    /**
     * Validate whether a "generic" export is working
     */
    public function testExportPluginExportsCorrectRecords () {
        $data = array(array('header1', 'header2'),
                      array('body1', 'body2'));

        $fileplugin = new rlip_fileplugin_writememory();

        $exportplugin = new rlip_exportplugin_mock($fileplugin, $data);
        $exportplugin->run();

        $data = $fileplugin->get_data();

        $this->assertEquals(count($data), 2);
    }

    /**
     * Validate whether the base export class closes the output file
     */
    public function testExportPluginClosesExportFile () {
        $fileplugin = new rlip_fileplugin_outputclosed();

        $exportplugin = new rlip_exportplugin_mock($fileplugin, array());
        $exportplugin->run();

        $closed = $fileplugin->closed();

        $this->assertEquals($closed, true);
    }

    /**
     * Validate the flow of execution through a "generic" export
     * plugin
     */
    public function testEmptyExportCallsCorrectMethods() {
        $fileplugin = new rlip_fileplugin_writememory();
        $exportplugin = new rlip_exportplugin_empty($fileplugin);
        $exportplugin->run();

        //"init" should be called once
        $num_init_calls = $exportplugin->get_num_init_calls();
        $this->assertEquals($num_init_calls, 1);

        //"has_next" should be called once
        $num_has_next_calls = $exportplugin->get_num_has_next_calls();
        $this->assertEquals($num_has_next_calls, 1);

        //"next" should not be called
        $num_next_calls = $exportplugin->get_num_next_calls();
        $this->assertEquals($num_next_calls, 0);

        //"close" should be called once
        $num_close_calls = $exportplugin->get_num_close_calls();
        $this->assertEquals($num_close_calls, 1);
    }
}

