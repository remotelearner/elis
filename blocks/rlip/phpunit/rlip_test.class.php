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

require_once(elis::lib('testlib.php'));

/*
 * This class is handling log maintenance
 */
abstract class rlip_test extends elis_database_test {
    static $existing_logfiles = array();

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        static::get_logfilelocation_files();
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        static::cleanup_log_files();
    }

    protected function setUp() {
        global $CFG;
        parent::setUp();
        //make sure that the logfile location is set
        set_config('logfilelocation', $CFG->dataroot, 'rlipimport_version1');
        set_config('logfilelocation', $CFG->dataroot, 'rlipexport_version1');
    }
    /**
     * Cleans up log files created whenever an import runs
     */
    public static function cleanup_log_files() {
        global $CFG;
        //set the log file location
        $filepath = $CFG->dataroot;
        //remove all previous log files - assuming manual
        foreach(glob("$filepath/*.log") as $file) {
            if (is_array(self::$existing_logfiles)) {
                if (!in_array($file, self::$existing_logfiles)) {
                    unlink($file);
                }
            } else {
                unlink($file);
            }
        }
    }

    /**
     * Gets a list of log files to not delete
     */
    public static function get_logfilelocation_files() {
        global $CFG;
        //set the log file location
        $filepath = $CFG->dataroot;

        self::$existing_logfiles = array();
        //remove all previous log files - assuming manual
        foreach(glob("$filepath/*.log") as $file) {
            self::$existing_logfiles[] = $file;
        }
    }

    /**
     * Find the most recent log file
     * @param string filename The base filename to find the most recent file
     * @return string newest_file The most current filename
     */
    public static function get_current_logfile($filename) {
        $filename_prefix = explode('.',$filename);
        //get newest file
        $newest_file = $filename;
        $versions = array();
        foreach (glob("$filename_prefix[0]*.log") as $fn) {
            if (($fn != $filename) &&
                ((is_array(self::$existing_logfiles) && !in_array($fn, self::$existing_logfiles)) ||
                 !is_array(self::$existing_logfiles))) {
                //extract count
                $fn_prefix = explode('.',$fn);
                $fn_part = explode('_',$fn_prefix[0]);
                $count = end($fn_part);
                //store version number in an array
                $versions[] = $count;
            }
        }

        //get latest version of the log file if there is more than one version
        if (!empty($versions)) {
            sort($versions,SORT_NUMERIC);
            $newest_file = $filename_prefix[0].'_'.end($versions).'.log';
        }
        return $newest_file;
    }

/**
     * Find the next log file
     * @param string filename The base filename to find the most recent file
     * @return string next_file The most current filename + 1
     */
    public static function get_next_logfile($filename) {
        $newest_file = self::get_current_logfile($filename);
        $filename_prefix = explode('.',$filename);

        // generate the 'next' filename
        if ($newest_file == $filename) {
            $next_file = $filename_prefix[0].'_0.log';
        } else {
            $filename_part = explode('_',$filename_prefix[0]);
            $count = end($filename_part);
            $next_file = $filename_prefix[0].'_'.$count++.'.log';
        }
        return $next_file;
    }

}
