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
    var $manual;
    var $opened = false;

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
        $format = get_string('logtimeformat', 'block_rlip');
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
        return (float)$tzrecord->gmtoff / HOURMINS;
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
        $this->log($message, $timestamp, $filename, $entitydescriptor, true);
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
        $this->log($message, $timestamp, $filename, $entitydescriptor, false);
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
}

/**
 * Class for logging general entry messages to the file system.
 * These "general" messages should likely NOT have been separated from the "specific" messages,
 * but rather inserted together.
 */
class rlip_import_version1_fslogger extends rlip_fslogger {

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
     * @param Object $record Imported data
     * @param string $type Type of import
     */
    function log_failure($message, $timestamp = 0, $filename = NULL, $entitydescriptor = NULL, $record = NULL, $type = NULL) {
        if (!empty($record) && !empty($type)) {
            $this->type_validation($type);
            $message = $this->general_validation_message($record, $message, $type);
        }
        parent::log_failure($message, $timestamp, $filename, $entitydescriptor);
    }

    /*
     * Adds the general message to the specific message for a given type
     * @param Object $record Imported data
     * @param string $message The specific message
     * @param string $type Type of import
    */
    function general_validation_message($record, $message, $type) {
        // "action" is not always provided. In that case, return only the specific message
        if (empty($record->action)) {
            return $message;
        }

        $msg = "";

        if ($type == "enrolment") {
            switch ($record->action) {
                case "create":
                    // If the field data is not provided, provide the least general message
                    if (empty($record->username) || empty($record->instance)) {
                        $msg = "Enrolment could not be created. " . $message;
                    } else {
                        $msg = "User with username \"{$record->username}\" could not be enroled in course with shortname \"{$record->instance}\". " . $message;
                    }
                    break;
                case "delete":
                    if (empty($record->username) || empty($record->instance)) {
                        $msg = "Enrolment could not be deleted. " . $message;
                    } else {
                        $msg = "User with username \"{$record->username}\" could not be unenroled in course with shortname \"{$record->instance}\". " . $message;
                    }
                    break;
            }
        }

        if ($type == "group") {
            switch ($record->action) {
                case "create":
                    $msg = "Group with name \"{$record->group}\" could not be created in course with shortname \"{$record->instance}\". " . $message;
                    break;
                case "update":
                    $msg = "Group with name \"{$record->group}\" could not be updated in course with shortname \"{$record->instance}\". " . $message;
                    break;
                case "delete":
                    $msg = "Group with name \"{$record->group}\" could not be deleted in course with shortname \"{$record->instance}\". " . $message;
                    break;
            }
        }

        if ($type == "roleassignment") {
            switch ($record->action) {
                case "create":
                    if (empty($record->shortname) || empty($record->username) || empty($record->context) || empty($record->instance)) {
                        $msg = "Role assignment could not be created. " . $message;
                    } else {
                        $msg = "User with username \"{$record->username}\" could not be assigned role with shortname \"{$record->shortname}\"" .
                           " on \"{$record->context}\" \"$record->instance\" " . $message;
                    }
                    break;
                case "delete":
                    if (empty($record->shortname) || empty($record->username) || empty($record->context) || empty($record->instance)) {
                        $msg = "Role assignment could not be deleted. " . $message;
                    } else {
                        $msg = "User with username \"{$record->username}\" could not be unassigned role with shortname \"{$record->shortname}\"" .
                           " on \"{$record->context}\" \"$record->instance\" " . $message;
                    }
                    break;
            }
        }

        if ($type == "course") {
            $type = ucfirst($type);
            switch ($record->action) {
                case "create":
                    if (empty($record->shortname)) {
                        $msg = "Course could not be created. " . $message;
                    } else {
                        $msg =  "{$type} with shortname \"{$record->shortname}\" could not be created. " . $message;
                    }
                    break;
                case "update":
                    if (empty($record->shortname)) {
                        $msg = "Course could not be updated. " . $message;
                    } else {
                        $msg = "{$type} with shortname \"{$record->shortname}\" could not be updated. " . $message;
                    }
                    break;
                case "delete":
                    if (empty($record->shortname)) {
                        $msg = "Course could not be deleted. " . $message;
                    } else {
                        $msg = "{$type} with shortname \"{$record->shortname}\" could not be deleted. " . $message;
                    }
                    break;
            }
        }

        if ($type == "user") {
            $type = ucfirst($type);
            switch ($record->action) {
                case "create":
                    if (empty($record->username)) {
                        $msg = "User could not be created. " . $message;
                    } else {
                        $msg =  "{$type} with username \"{$record->username}\" could not be created. " . $message;
                    }
                    break;
                case "update":
                    if (empty($record->username)) {
                        $msg = "User could not be updated. " . $message;
                    } else {
                        $msg = "{$type} with username \"{$record->username}\" could not be updated. " . $message;
                    }
                    break;
                case "delete":
                    if (empty($record->username)) {
                        $msg = "User could not be deleted. " . $message;
                    } else {
                        $msg = "{$type} with username \"{$record->username}\" could not be deleted. " . $message;
                    }
                    break;
            }
        }

        return $msg;
    }

    // Validate the provided type
    private function type_validation($type) {
        $types = array('course','user','roleassignment','group','enrolment');
        if (!in_array($type, $types)) {
            throw new Exception("\"$type\" in an invalid type. The available types are " . implode(', ', $types));
        }
    }

}

/**
 * Filesystem logging class that represents file / import types with
tim ~/moodle.dev/rlip_plain/lib/phpunittestlib >
 * line-by-line data
 */
class rlip_fslogger_linebased extends rlip_import_version1_fslogger {
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
     * @param object $fileplugin The file plugin that should be used to write
     *                           out log data
     */
    static function factory($fileplugin, $manual = false) {
        //only one type of file system logger for now
        return new rlip_fslogger_linebased($fileplugin, $manual);
    }
}
