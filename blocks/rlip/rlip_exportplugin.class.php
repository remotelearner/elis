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

require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');

/**
 * Base class for Integration Point export plugins
 */
abstract class rlip_exportplugin_base extends rlip_dataplugin {
    //track the file being used for export
    var $fileplugin;
    var $fslogger = null;
    var $plugin;

	//methods to be implemented in specific export

	/**
     * Hook for performing any initialization that should
     * be done at the beginning of the export
     */
	abstract function init();

    /**
     * Hook for specifiying whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
	abstract function has_next();

	/**
	 * Hook for exporting the next data record in-place
	 *
	 * @return array The next record to be exported
	 */
	abstract function next();

    /**
     * Hook for performing any cleanup that should
     * be done at the end of the export
     */
	abstract function close();

	/**
	 * Default export plugin constructor
	 *
	 * @param object $fileplugin the file plugin used for output
	 */
    function __construct($fileplugin) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dblogger.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fslogger.class.php');

        $this->fileplugin = $fileplugin;
        $this->dblogger = new rlip_dblogger_export();

        //convert class name to plugin name
        $class = get_class($this);
        $this->plugin = str_replace('rlip_exportplugin_', 'rlipexport_', $class);

        //set up the file-system logger, if exists
        $filename = get_config($this->plugin, 'logfilelocation');
        if (!empty($filename)) {
            $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true);
            $this->fslogger = new rlip_fslogger($fileplugin);
        }

        //indicate to the databaes logger which plugin we're using
        $class = get_class($this);
        //convert export_plugin_ prefix to rlipexport_
        $plugin = 'rlipexport_'.substr($class, strlen('rlip_exportplugin_'));
        $this->dblogger->set_plugin($plugin);
    }

    /**
     * Mainline for export processing
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $maxruntime      The max time in seconds to complete export
     *                             default: 0 => unlimited
     * @param object $state        Previous ran state data to continue from
     *                             (currently not used for export)
     * @return mixed object        Current state of export processing
     *                             or null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    function run($targetstarttime = 0, $maxruntime = 0, $state = null) {
        //track the start time as the current time
        $this->dblogger->set_starttime(time());
        //track the provided target start time
        $this->dblogger->set_targetstarttime($targetstarttime);

        //open the output file for writing
        $this->fileplugin->open(RLIP_FILE_WRITE);

        //perform any necessary setup
        $this->init();

        //run the main export process
        $result = $this->export_records($maxruntime);

        //clean up
        $this->close();

        //close the output file
        $this->fileplugin->close();

        //track the end time as the current time
        $this->dblogger->set_endtime(time());

        //flush db log record
        $this->dblogger->flush($this->fileplugin->get_filename());
        $obj = null;
        if ($result !== true) {
            $obj = new stdClass;
            $obj->result = $result;
            // no other state info to save for export
        }
        return $obj;
    }

    /**
     * Main loop for handling the body of the export
     *
     * @param int $maxruntime  The max time in seconds to complete export
     * @return bool            true on success, false if time limit exceeded
     */
    function export_records($maxruntime = 0) {
        $starttime = time();
        while ($this->has_next()) {
            // check if time limit exceeded
            if ($maxruntime && (time() - $starttime) > $maxruntime) {
                // time limit exceeded - abort with log message
                if ($this->fslogger) {
                    $msg = get_string('exportexceedstimelimit', 'block_rlip');
                    $this->fslogger->log($msg);
                }
                return false;
            }
            //fetch and write out the next record
            $record = $this->next();
            $this->fileplugin->write($record);
            $this->dblogger->track_success(true, true);
        }
        return true;
    }

    /**
     * Getter for the file plugin used for IP by this export plugin
     *
     * @return object The file plugin instance used for IO by this export
     */
    function get_file_plugin() {
        return $this->fileplugin;
    }

}
