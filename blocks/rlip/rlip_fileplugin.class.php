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

//constants for defining which mode we open files in
define('RLIP_FILE_READ', 1);
define('RLIP_FILE_WRITE', 2);

abstract class rlip_fileplugin_base {

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
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    abstract function get_filename();

    /**
     * Specifies the extension of the current open file
     *
     * @return string The file extension
     */
    abstract function get_extension();
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
	 * @return object The file plugin instance
	 */
    static function factory($filename, $logging = false) {
    	global $CFG;

    	if ($logging) {
    	    //using a standard text file for logging
    	    require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log.class.php');

            $filename .= '.'.rlip_fileplugin_log::get_extension();
            return new rlip_fileplugin_log($filename);
    	} else {
    	    //using a csv file for import or export
    	    require_once($CFG->dirroot.'/blocks/rlip/fileplugins/csv.class.php');

            $filename .= '.'.rlip_fileplugin_csv::get_extension();
            return new rlip_fileplugin_csv($filename);
    	}
    }
}