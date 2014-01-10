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
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

//constants for defining which mode we open files in
define('RLIP_FILE_READ', 1);
define('RLIP_FILE_WRITE', 2);

abstract class rlip_fileplugin_base {
    var $filename;
    var $tempfile; // used when sendtobrowser = true
    var $fileid;
    //track whether we're sending to the browser, for writes
    var $sendtobrowser;

	/**
     * Base file plugin constructor
     *
     * @param string $filename The path of the file to open, or the empty
     *                         string if instead using a Moodle file id
     * @param mixed $fileid The id of the Moodle file to open, or NULL if we
     *                      are using a file on the file system
     * @param boolean $sendtobrowser Set to true to send writes to the browser
     */
    function __construct($filename = '', $fileid = NULL, $sendtobrowser = false) {
        $this->filename = $filename;
        $this->fileid = $fileid;
        $this->sendtobrowser = $sendtobrowser;
    }

    /**
     * Hook for sending required HTTP headers to browser for direct download
     */
    function send_headers() {
        // nothing by default
    }

    /**
     * Hook for opening the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    abstract function open($mode);

    /**
     * Hook for reading one entry from the file
     *
     * @return array The entry read
     */
    abstract function read();

    /**
     * Hook for writing one entry to the file
     *
     * @param array $line The entry to write to the file
     */
    abstract function write($entry);

    /**
     * Hook for closing the file
     */
    abstract function close();

    /**
     * Hook for deleting the file
     * TBD: determine if this method should be abstract???
     * @return bool   true on success, false on error
     */
    function delete() {
        //TBD: do nothing
        return true;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name.
     */
    abstract function get_filename($withpath = false);

    /**
     * Output file contents
     * @param bool $return  true to return file contents as string,
     *                      false to output directly to stdout
     */
    function output_file($return = false) {
        // todo: support Moodle fileids
        $filename = $this->get_filename(true);
        if ($return) {
            return file_get_contents($filename);
        }
        if (($fptr = fopen($filename, 'r'))) {
            while (($fline = fgets($fptr)) !== false) {
                echo $fline;
            }
            fclose($fptr);
        }
    }
}

/**
 * Factory for obtaining an appropriate file plugin
 */
class rlip_fileplugin_factory {

	/**
	 * Main factory method for obtaining a file plugin instance
	 *
	 * @param string $filename The path of the file to open
	 * @param boolean $logging If true, the file is being opened for logging,
	 *                         otherwise for import
	 * @param boolean $sendtobrowser Set to true to send writes to the browser
	 * @return object The file plugin instance
	 */
    static function factory($filename = '', $fieldid = NULL, $logging = false, $sendtobrowser = false) {
    	global $CFG;

    	if ($logging) {
      	    //using a standard text file for logging
            $file = get_plugin_directory('dhfile', 'log').'/log.class.php';
            require_once($file);

            return new rlip_fileplugin_log($filename);
    	}

    	//load the CSV file plugin definition
        $file = get_plugin_directory('dhfile', 'csv').'/csv.class.php';
        require_once($file);

    	if ($sendtobrowser) {
    	    //writing a CSV file to the browser
            return new rlip_fileplugin_csv($filename, NULL, true);
    	} else if ($filename == '') {
    	    //reading a CSV file from Moodle file system
            return new rlip_fileplugin_csv($filename, $fieldid);
    	} else {
    	    //using a csv file for import or export
            return new rlip_fileplugin_csv($filename);
    	}
    }
}
