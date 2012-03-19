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

        $this->fileplugin = $fileplugin;
        $this->dblogger = new rlip_dblogger_export();

        //indicate to the databaes logger which plugin we're using
        $class = get_class($this);
        //convert export_plugin_ prefix to rlipexport_
        $plugin = 'rlipexport_'.substr($class, strlen('rlip_exportplugin_'));
        $this->dblogger->set_plugin($plugin);
    }

    /**
     * Mainline for export processing
     */
    function run() {
        //track the start time as the current time
        $this->dblogger->set_starttime(time());
        //open the output file for writing
        $this->fileplugin->open(RLIP_FILE_WRITE);

        //perform any necessary setup
        $this->init();

        //run the main export process
        $this->export_records();

        //clean up
        $this->close();

        //close the output file
        $this->fileplugin->close();

        //track the end time as the current time
        $this->dblogger->set_endtime(time());

        //flush db log record
        $this->dblogger->flush($this->fileplugin->get_filename());
    }

    /**
     * Main loop for handling the body of the export
     */
    function export_records() {
        while ($this->has_next()) {
            //fetch and write out the next record
            $record = $this->next();
            $this->fileplugin->write($record);
            $this->dblogger->track_success(true, true);
        }
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