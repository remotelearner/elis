<?php
/**
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

	//methods to be implemented in specific export

	/**
     * Hook for performing any initialization that should
     * be done at the beginning of the export
     */
	abstract function init($fileplugin);

    /**
     * Hook for specifiying whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
	abstract function has_next();

	/**
	 * Hook for exporting the next data record in-place
	 */
	abstract function next();

    /**
     * Hook for performing any cleanup that should
     * be done at the end of the export
     */
	abstract function close();

	/**
	 * Mainline for export processing
	 */
    function run() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');

        //obtain a file plugin instance, opening the file in write mode
        $fileplugin = rlip_fileplugin_factory::factory();
        $fileplugin->open(RLIP_FILE_WRITE);

        //perform any necessary setup
        $this->init($fileplugin);

        //run the main export process
        $this->export_records($fileplugin);

        //clean up
        $this->close();

        //close the output file
        $fileplugin->close();
    }

    /**
     * Main loop for handling the body of the export
     */
    function export_records($fileplugin) {
        while ($this->has_next()) {
            //fetch and write out the next record
            $record = $this->next();
            $fileplugin->write($record);
        }
    }

}