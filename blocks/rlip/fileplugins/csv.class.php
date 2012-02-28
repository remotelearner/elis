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
 * File plugin that handles the reading and writing of CSV
 * data
 */
class rlip_fileplugin_csv extends rlip_fileplugin_base {
    var $filepointer;
    var $first;
    var $header;

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
    	global $CFG;

    	$fs = get_file_storage();

    	if ($mode == RLIP_FILE_WRITE) {
    	    //we are only writing to files on the file-system
            $this->filepointer = fopen($this->filename, 'w');
    	} else {
    	    if ($this->filename != '') {
    	        //read from the file system
    	        $this->filepointer = fopen($this->filename, 'r');
    	    } else {
    	        //read from a Moodle file
    	        $file = $fs->get_file_by_id($this->fileid);
    	        $this->filepointer = $file->get_content_file_handle();
    	    }
    	}

    	$this->first = true;
    	$this->header = NULL;
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        $result = fgetcsv($this->filepointer);

        if (is_array($result) && count($result) == 1 && $result[0] == '') {
            //this catches an empty line with just a newline character
            return false;
        }

        return $result;
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        fputcsv($this->filepointer, $entry);
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
        //todo: actuall implement?
        return '';
    }

    /**
     * Specifies the extension of the current open file
     *
     * @return string The file extension
     */
    function get_extension() {
        return 'csv';
    }
}