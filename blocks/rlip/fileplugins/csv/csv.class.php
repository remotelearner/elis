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
 * @package    rlip
 * @subpackage block_rlip
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
    var $filepointer = false;
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
            if ($this->sendtobrowser) {
    	        //send directly to the browser
    	        //todo: config
                $filename = basename($this->filename);

                //CSV header
                header("Content-Transfer-Encoding: ascii");
                header("Content-Disposition: attachment; filename={$filename}");
                header("Content-Type: text/comma-separated-values");
                $this->filepointer = fopen('php://output', 'w');
    	    } else {
                //we are only writing to files on the file-system
                $this->filepointer = fopen($this->filename, 'w');
    	    }
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
        if ($this->filepointer === false) {
            return false;
        }
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
        if ($this->filepointer !== false) {
            fputcsv($this->filepointer, $entry);
        }
    }

    /**
     * Close the file
     */
    function close() {
        if ($this->filepointer !== false) {
            fclose($this->filepointer);
        }
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        global $DB;

        if ($this->filename != '') {
            //physical file, so obtain filename from full path
            $parts = explode('/', $this->filename);
            $count = count($parts);
            return $parts[$count - 1];
        } else {
            //Moodle file system file, so obtain filename from db
            $params = array('id' => $this->fileid);
            return $DB->get_field('files', 'filename', $params);
        }
    }
}

