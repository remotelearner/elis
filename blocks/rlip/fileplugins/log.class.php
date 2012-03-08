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

/**
 * File plugin used for writing text to log files
 */
class rlip_fileplugin_log extends rlip_fileplugin_base {

	/**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
    	if ($mode == RLIP_FILE_WRITE) {
  	        //todo: determine if we need to make a change to unit tests to
  	        //remove this check
    	    if (!empty($this->filename)) {
                $this->filepointer = fopen($this->filename, 'w');
    	    }
    	} else {
    	    //we never read with this class
    	}
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        //we never read with this class
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        //todo: determine if we need to make a change to unit tests to
        //remove this check
        if (isset($this->filepointer)) {
            $entry = reset($entry);
            fwrite($this->filepointer, $entry."\n");
        }
    }

    /**
     * Close the file
     */
    function close() {
        fclose($this->filepointer);
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        //todo: implement?
        return '';
    }
}