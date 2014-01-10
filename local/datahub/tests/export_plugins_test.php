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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

/**
 * Mock export plugin for testing exports
 */
class rlip_exportplugin_mock extends rlip_exportplugin_base {
    public $index;
    public $data;

    /**
     * Default export plugin constructor
     *
     * @param object $fileplugin the file plugin used for output
     * @param array $data the fixed data to include in the export
     */
    public function __construct($fileplugin, $data) {
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
    public function init($targetstarttime = 0, $lastruntime = 0) {
        // Nothing to do.
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    public function has_next() {
        return $this->index < count($this->data);
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    public function next() {
        $result = $this->data[$this->index];
        $this->index++;

        return $result;
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    public function close() {
        // Nothing to do.
    }
}

/**
 * Mock file plugin for testing closing of output files
 */
class rlip_fileplugin_writememory extends rlip_fileplugin_base {
    public $entries;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $entries = array();
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        // Nothing to do.
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        $this->entries[] = $entry;
    }

    /**
     * Close the file
     */
    public function close() {
        // Nothing to do.
    }

    /**
     * Specifies the data written to this plugin
     *
     * @return array All of the data written to this plugin
     */
    public function get_data() {
        return $this->entries;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        return 'writememory';
    }
}

/**
 * Mock file plugin for testing closing of files
 */
class rlip_fileplugin_outputclosed extends rlip_fileplugin_writememory {
    // Track whether the file was closed.
    public $closed = false;

    /**
     * Close the file
     */
    public function close() {
        $this->closed = true;
    }

    /**
     * Specifies whether this file was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    public function closed() {
        return $this->closed;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        return 'outputclosed';
    }
}

/**
 * Mock export plugin containing no data that tracks the number of
 * times its methods are called
 */
class rlip_exportplugin_empty extends rlip_exportplugin_base {
    /**
     * @var int Track the number of init calls
     */
    public $numinitcalls = 0;

    /**
     * @var int Track the number of has_next calls
     */
    public $numhasnextcalls = 0;

    /**
     * @var int Track the number of next calls
     */
    public $numnextcalls = 0;

    /**
     * @var int Track the number of close calls
     */
    public $numclosecalls = 0;

    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    public function init($targetstarttime = 0, $lastruntime = 0) {
        $this->numinitcalls++;
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    public function has_next() {
        $this->numhasnextcalls++;

        return false;
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    public function next() {
        $this->numnextcalls++;

        return array();
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    public function close() {
        $this->numclosecalls++;
    }

    /**
     * Specifies the number of times the "init" method was called
     *
     * @return int the number of calls
     */
    public function get_num_init_calls() {
        return $this->numinitcalls;
    }

    /**
     * Specifies the number of times the "has_next" method was called
     *
     * @return int the number of calls
     */
    public function get_num_has_next_calls() {
        return $this->numhasnextcalls;
    }

    /**
     * Specifies the number of times the "next" method was called
     *
     * @return int the number of calls
     */
    public function get_num_next_calls() {
        return $this->numnextcalls;
    }

    /**
     * Specifies the number of times the "close" method was called
     *
     * @return int the number of calls
     */
    public function get_num_close_calls() {
        return $this->numclosecalls;
    }
}

/**
 * Class for testing the base export plugin class
 * @group local_datahub
 */
class exportplugin_testcase extends rlip_test {

    /**
     * Validate export file specifies RLIP_EXPORT_TEMPDIR as path
     */
    public function test_exportfilenameincorrecttempdir() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib.php');
        $plugin = 'test_dhexport_version1';
        set_config('export_file', "/tmp/{$plugin}/{$plugin}.csv", $plugin);
        $exportfilename = rlip_get_export_filename($plugin, 99);
        $targetpath = $CFG->dataroot.sprintf(RLIP_EXPORT_TEMPDIR, $plugin);
        $this->assertEquals($targetpath, dirname($exportfilename).'/');

        // Clean-up created export temp dirs for this bogus plugin.
        @rmdir(dirname($exportfilename));
        @rmdir(dirname(dirname($exportfilename)));
    }

    /**
     * Validate whether a "generic" export is working
     */
    public function test_exportpluginexportscorrectrecords () {
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
    public function test_exportpluginclosesexportfile () {
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
    public function test_emptyexportcallscorrectmethods() {
        $fileplugin = new rlip_fileplugin_writememory();
        $exportplugin = new rlip_exportplugin_empty($fileplugin);
        $exportplugin->run();

        // Function "init" should be called once.
        $numinitcalls = $exportplugin->get_num_init_calls();
        $this->assertEquals($numinitcalls, 1);

        // Function "has_next" should be called once.
        $numhasnextcalls = $exportplugin->get_num_has_next_calls();
        $this->assertEquals($numhasnextcalls, 1);

        // Function "next" should not be called.
        $numnextcalls = $exportplugin->get_num_next_calls();
        $this->assertEquals($numnextcalls, 0);

        // Function "close" should be called once.
        $numclosecalls = $exportplugin->get_num_close_calls();
        $this->assertEquals($numclosecalls, 1);
    }
}

