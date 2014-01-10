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

/**
 * Class for logging entry-specific messages to the file system
 */
class rlip_fslogger {
    var $fileplugin;
    var $manual;
    var $opened = false;
    var $logfile_status = false;

    /**
     * Filesystem logger constructor
     *
     * @param object $fileplugin The file plugin used to write data out
     * @param boolean $manual true if a manual run, otherwise false for
     *                        scheduled
     */
    function __construct($fileplugin, $manual = false) {
        $this->fileplugin = $fileplugin;
        $this->manual = $manual;
    }

    /**
     * Specifies whether this logging object is for a manual run
     *
     * @return boolean true if for a manual run, otherwise false
     */
    function get_manual() {
        return $this->manual;
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
        $format = get_string('logtimeformat', 'local_datahub');
        return userdate($timestamp, $format, $timezone, false);
    }

    /**
     * Convert a timezone name to an offset in hours
     *
     * @param string $timezone The name of the timezone
     * @return float The numerical timezone offset
     */
    function get_offset_from_timezone_string($timezone) {
        global $DB;

        //look up timezone by name
        $tzrecord = $DB->get_record_sql('SELECT * FROM {timezone}
                                         WHERE name = ? ORDER BY year DESC', array($timezone), true);
        if ($tzrecord) {
            return (float)$tzrecord->gmtoff / HOURMINS;
        } else {
            return 0.0;
        }
    }

    /**
     * Log a success message to the log file
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     */
    function log_success($message, $timestamp = 0, $filename = NULL, $entitydescriptor = NULL) {
        //re-delegate to the main logging function
        return $this->log($message, $timestamp, $filename, $entitydescriptor, true);
    }

    /**
     * Log a failure message to the log file, and potentially the screen
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     */
    function log_failure($message, $timestamp = 0, $filename = NULL, $entitydescriptor = NULL) {
        //re-delegate to the main logging function
        return $this->log($message, $timestamp, $filename, $entitydescriptor, false);
    }

    /**
     * Log a message to the log file - used internally only (use log_success or
     * log_failure instead for external calls)
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     * @param boolean $success true if the operation was a success, otherwise
     *                         false
     */
    protected function log($message, $timestamp = 0, $filename = NULL, $entitydescriptor = NULL,
                           $success = false) {
        global $CFG, $OUTPUT;

        $message = $this->customize_record($message, $timestamp, $filename, $entitydescriptor, $success);

        if ($this->opened !== true) {
            //open the file for writing if it hasn't been opened yet
            $open = $this->fileplugin->open(RLIP_FILE_WRITE);
            if ($open === true) {
                $this->opened = true;
            } else {
                $this->set_logfile_status(false);
                return false;
            }
        }

        if ($timestamp == 0) {
            //default to current time if time not specified
            $timestamp = time();
        }

        if (!empty($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
            //timezone is forced to some value
            $timezone = $CFG->forcetimezone;

            if (!is_numeric($timezone)) {
                //look up timezone by name
                $timezone = $this->get_offset_from_timezone_string($timezone);
            }

            $offset = self::offset_display($timezone);
        } else if (!empty($CFG->timezone) && $CFG->timezone != 99) {
            //timezone defaults to some value
            $timezone = $CFG->timezone;

            if (!is_numeric($timezone)) {
                //look up timezone by name
                $timezone = $this->get_offset_from_timezone_string($timezone);
            }

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

        if (!$success && $this->manual) {
            echo $OUTPUT->box($message, 'generalbox warning manualstatusbox');
        }
        return true;
    }

    /**
     * API hook for customizing the contents for a file-system log line / record
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     * @param boolean $success true if the operation was a success, otherwise
     *                         false
     */
    function customize_record($message, $timestamp = 0, $filename = NULL,
                              $entitytnum = NULL, $success = false) {
        //no customization by default
        return $message;
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
    /**
     * Set logfile status
     * @param boolean $state the status of the logfile/logfile path - false if not accessible
     */
    function set_logfile_status($state) {
        $this->logfile_status = $state;
    }

    /**
     * Get logfile status
     * @return boolean $state the status of the logfile/logfile path - false if not accessible
     */
    function get_logfile_status() {
        return $this->logfile_status;
    }
}

/**
 * Filesystem logging class that represents file / import types with
tim ~/moodle.dev/rlip_plain/lib/phpunittestlib >
 * line-by-line data
 */
class rlip_fslogger_linebased extends rlip_fslogger {
    /**
     * API hook for customizing the contents for a file-system log line / record
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0
     *                       for the current time
     * @param string $filename The name of the import / export file we are
     *                         reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file
     *                              we are handling, if applicable
     * @param boolean $success true if the operation was a success, otherwise
     *                         false
     */
    function customize_record($message, $timestamp = 0, $filename = NULL,
                              $entitydescriptor = NULL, $success = false) {

        if ($filename !== NULL && $entitydescriptor !== NULL) {
            //add filename and line number
            $prefix = "[{$filename} line {$entitydescriptor}] ";
            return $prefix.$message;
        }

        return $message;
    }
}

/**
 * Factory class for the file system logger
 */
class rlip_fslogger_factory {
    /**
     * Factory method to obtain a default file system logger object
     *
     * @param string $plugin The plugin we are running, either dhimport_* or dhexport_*
     * @param object $fileplugin The file plugin that should be used to write out log data
     * @param boolean $manual True on a manual run, false on a scheduled run
     */
    static function factory($plugin, $fileplugin, $manual = false) {
        global $CFG;

        //determine the components of the plugin (type and instance)
        list($type, $instance) = explode('_', $plugin);

        //try to load in the appropriate file
        $file = get_plugin_directory($type, $instance).'/'.$instance.'.class.php';
        if (!file_exists($file)) {
            //this should only happen during unit tests
            require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');
            return new rlip_fslogger_linebased($fileplugin, $manual);
        }
        require_once($file);

        //determine classname
        $classname = $plugin;
        $classname = str_replace('dhexport_', 'rlip_exportplugin_', $classname);
        $classname = str_replace('dhimport_', 'rlip_importplugin_', $classname);

        //ask the plugin to provide the logger
        return $classname::get_fs_logger($fileplugin, $manual);
    }
}
