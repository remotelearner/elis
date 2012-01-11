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
     * @return object The entry read
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
}

/**
 * Factory for obtaining an appropriate file plugin
 */
class rlip_fileplugin_factory {

	/**
	 * Main factory method for obtaining a file plugin instance
	 *
	 * @return object The file plugin instance
	 */
    static function factory() {
    	global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/csv.class.php');

        //for now, we only have the CSV file type
        return new rlip_fileplugin_csv();
    }
}