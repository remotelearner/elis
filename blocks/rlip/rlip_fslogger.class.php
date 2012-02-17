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
 * Class for logging entry-specific messages to the file system
 */
class rlip_fslogger {
    var $fileplugin;
    var $opened = false;

    function __construct($fileplugin) {
        $this->fileplugin = $fileplugin;
    }

    /**
     * Calculates the display string for a timezone offset
     *
     * @param int $timezone The numerical timezone offset
     * @return string The offset display, in the format (+/-)hhmm
     */
    static function offset_display($timezone) {
        //calculate number of hours
        $hours = (string)abs((int)$timezone);

        //calculate number of minutes
        $minutes = abs($timezone) - floor(abs($timezone));
        $minutes = round($minutes * 60);
        $minutes = (string)$minutes;

        //pad out minutes and hourse if needed
        if (strlen($hours) < 2) {
            $hours = '0'.$hours;
        }

        if (strlen($minutes) < 2) {
            $minutes = '0'.$minutes;
        }

        //numerical part
        $offset = $hours.$minutes;

        //+/- prefix
        if ($timezone >= 0) {
            $offset = '+'.$offset;
        } else {
            $offset = '-'.$offset;
        }

        return $offset;
    }

    /**
     * Calculates the display string for a time value
     *
     * @param int $timestamp The time value being displayed
     * @param int $timezone The timezone the time is being displayed for, or 99
     *                      for server default
     * @return string The formatted time string
     */
    static function time_display($timestamp, $timezone) {
        $format = get_string('logtimeformat', 'block_rlip');
        return userdate($timestamp, $format, $timezone, false);
    }

    /**
     * Log a message to the log file
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     */
    function log($message, $timestamp = 0) {
        global $CFG;

        if (!$this->opened) {
            //open the file for writing if it hasn't been opened yet
            $this->fileplugin->open(RLIP_FILE_WRITE);
            $this->opened = true;
        }

        if ($timestamp == 0) {
            //default to current time if time not specified
            $timestamp = time();
        }

        if (!empty($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
            //timezone is forced to some value
            $timezone = $CFG->forcetimezone;
            $offset = self::offset_display($timezone);
        } else if (!empty($CFG->timezone) && $CFG->timezone != 99) {
            //timezone defaults to some value
            $timezone = $CFG->timezone;
            $offset = self::offset_display($timezone);
        } else {
            //use server default timezone
            $timezone = 99;
            $offset = date('Z', $timestamp) / 60 / 60;
            $offset -= date('I', $timestamp);
            $offset = self::offset_display($offset);
        }

        //convert time to human-readable format
        $date = self::time_display($timestamp, $timezone);

        //construct and write the log line
        $line = '['.$date.' '.$offset.'] '.$message;
        $this->fileplugin->write(array($line));
    }

    /**
     * Perform any cleanup that the logger needs to do
     */
    function close() {
        if ($this->opened) {
            $this->fileplugin->close();
            $this->opened = false;
        }
    }
}

/**
 * Factory class for the file system logger
 */
class rlip_fslogger_factory {
    /**
     * Factory method to obtain a default file system logger object
     *
     * @param object $fileplugin The file plugin that should be used to write
     *                           out log data
     */
    static function factory($fileplugin) {
        //only one type of file system logger for now
        return new rlip_fslogger($fileplugin);
    }
}