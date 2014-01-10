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

require_once($CFG->dirroot.'/local/datahub/lib.php');

/**
 * Class for storing import / export progress and logging end result to the
 * database
 */
abstract class rlip_dblogger {
    //this plugin that doing the work
    var $plugin = '';
    //the user running the task
    var $userid;

    //timing values
    var $targetstarttime = 0;
    var $starttime = 0;
    var $endtime = 0;

    //counts were are tracking

    //number of rows successfuly imported from file
    var $filesuccesses = 0;
    //number of rows with error from file
    var $filefailures = 0;
    //number of stored rows successfully impored
    var $storedsuccesses = 0;
    //number of stored rows with error
    var $storedfailures = 0;

    //number of db operations used
    var $dbops = -1;

    //tracks whether an unmet dependency was encountered
    var $unmetdependency = 0;

    //tracks whether maxruntime has been exceeded
    var $maxruntimeexceeded = false;

    // total records to process (import only)
    var $totalrecords = 0;

    //tracks whether we're performing a manual or scheduled run
    var $manual;

    //path of the log file
    var $logpath = NULL;

    //the type of entity
    var $entitytype = NULL;

    //whether the logfile/logfilepath is valid
    var $logfile_status = true;

    //error on the export path
    var $exportpath_error = NULL;

    //the list of log ids created since this object was constructed
    var $logids = array();

    /**
     * DB logger constructor
     *
     * @param boolean $manual true if manual, otherwise false
     */
    function __construct($manual = false) {
        global $USER;

        //set the userid to the global user id
        $this->userid = $USER->id;

        $this->manual = $manual;
    }

    /**
     * Set the plugin that we are logging for
     *
     * @param string $plugin The plugin shortname, such as dhimport_version1
     */
    function set_plugin($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Set the target (planned) start time
     *
     * @param int $targetstarttime The target (planned) start time
     */
    function set_targetstarttime($targetstarttime) {
        $this->targetstarttime = $targetstarttime;
    }

    /**
     * Get the target (planned) start time
     *
     * @return int The target (planned) start time
     */
    function get_targetstarttime() {
        return $this->targetstarttime;
    }

    /**
     * Sets the actual start time
     *
     * @param int $starttime The actual start time
     */
    function set_starttime($starttime) {
        $this->starttime = $starttime;
    }

    /**
     * Set the path to the log
     * @param string the log path
     */
    function set_log_path($logpath) {
        $this->logpath = $logpath;
    }

    /**
     * Get the path to the log
     * @return the log path
     */
    function get_log_path($logpath) {
        return $this->logpath;
    }

    /**
     * Set the entity type
     * @param string the entity type
     */
    function set_entity_type($entitytype) {
        $this->entitytype = $entitytype;
    }

    /**
     * Get the entity type
     * @return the entity type
     */
    function get_entity_type($logpath) {
        return $this->entitytype;
    }

    /**
     * Sets the actual end time
     *
     * @param int $starttime The actual end time
     */
    function set_endtime($endtime) {
        $this->endtime = $endtime;
    }

    /**
     * Store the result of a current row's action
     *
     * @param boolean $success true if row was successfully imported, otherwise
     *                         false
     * @param boolean $fromfile true if the action corresponds to row imported
     *                          from a file, otherwise false
     */
    function track_success($success, $fromfile) {
        if ($fromfile && $success) {
            //from file, success
            $this->filesuccesses++;
        } else if ($fromfile) {
            //from file, failure
            $this->filefailures++;
        } else if (!$fromfile && $success) {
            //stored record, success
            $this->storedsuccesses++;
        } else {
            //stored record, failure
            $this->storedfailures++;
        }
    }

    /**
     * Sets the number of DB ops used
     *
     * @param int $dbops The number of DB ops used
     */
    function set_dbops($dbops) {
        $this->dbops = $dbops;
    }

    /**
     * Signals that an unmet dependency was encountered
     */
    function signal_unmetdependency() {
        $this->unmetdependency = 1;
    }

    /**
     * Reset the state of the logger between executions
     */
    function reset_state() {
        $this->starttime = 0;
        $this->endtime = 0;

        //set all counts back to zero
        $this->filesuccesses = 0;
        $this->filefailures = 0;
        $this->storedsuccesses = 0;
        $this->storedfailures = 0;

        $this->dbops = -1;
        $this->unmetdependency = 0;
        $this->maxruntimeexceeded = false;
        $this->totalrecords = 0;
    }

    /**
     * Flush the current information to a record in the database and reset the
     * state of the logging object
     *
     * @param string $filename The filename for which processing is finished
     */
    function flush($filename) {
        global $DB, $USER;

        //set up our basic log record fields
        $record = new stdClass;
        $record->plugin = $this->plugin;
        $record->userid = $this->userid;
        $record->targetstarttime = $this->targetstarttime;
        $record->starttime = $this->starttime;
        $record->endtime = $this->endtime;
        $record->filesuccesses = $this->filesuccesses;
        $record->filefailures = $this->filefailures;
        $record->storedsuccesses = $this->storedsuccesses;
        $record->storedfailures = $this->storedfailures;
        $record->dbops = $this->dbops;
        $record->unmetdependency = $this->unmetdependency;
        $record->maxruntimeexceeded = $this->maxruntimeexceeded;
        $record->totalrecords = $this->totalrecords;

        //this handles the special case where runtime is exceeded and the the
        //file-system log record has not yet been created because it's
        //handled generically in the base class
        if (file_exists($this->logpath) || $this->maxruntimeexceeded) {
            $record->logpath = $this->logpath;
        } else {
            $record->logpath = NULL;
        }

        $record->entitytype = $this->entitytype;

        //perform any necessary data specialization
        $record = $this->customize_record($record, $filename);

        //persist
        $this->logids[] = $DB->insert_record(RLIP_LOG_TABLE, $record);

        //display, if appropriate
        $this->display_log($record, $filename);

        //reset state
        $this->reset_state();
    }

    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    abstract function customize_record($record, $filename);

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     */
    abstract function display_log($record, $filename);

    /**
     * Sets the total number of records to process
     *
     * @param int $total  the total number of records to process
     */
    function set_totalrecords($total) {
        $this->totalrecords = $total;
    }

    /**
     * Signals that the maxruntime has been exceeded
     */
    function signal_maxruntime_exceeded() {
        $this->maxruntimeexceeded = true;
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

    /**
     * Set export path error
     * @param string $error sets an error for the export path
     */
    function set_exportpath_error($error) {
        $this->exportpath_error = $error;
    }

    /**
     * Get export path error
     * @return string $state gets the error for the export path
     */
    function get_exportpath_error() {
        return $this->exportpath_error;
    }
    /**
     * Obtain the list of log record ids created since this object was constructed
     *
     * @return array The list of ids
     */
    function get_log_ids() {
        return $this->logids;
    }
}

/**
 * Database logging class for imports
 */
class rlip_dblogger_import extends rlip_dblogger {
    //track whether there are any missing columns and the associated message
    var $missingcolumns = false;
    var $missingcolumnsmessage = '';

    /**
     * Reset the state of the logger between executions
     */
    function reset_state() {
        parent::reset_state();

        //reset state related to missing columns
        $this->missingcolumns = false;
        $this->missingcolumnsmessage = '';
    }

    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    function customize_record($record, $filename) {
        if (!$this->get_logfile_status()) {
            $logfilepath = get_config($this->plugin,'logfilelocation');
            $record->statusmessage = get_string('importinvalidlogfilepath',
                                 'local_datahub',
                                 array('filename' => $filename,
                                 'recordsprocessed' => $record->filesuccesses + $record->filefailures,
                                 'logfilepath' => $logfilepath,
                                 'totalrecords' => $record->totalrecords));
        } else if ($this->missingcolumns) {
            $record->statusmessage = $this->missingcolumnsmessage;
        } else if ($this->maxruntimeexceeded) {
            // maxruntime exceeded message
            $record->statusmessage = get_string('dblogimportexceedstimelimit',
                                        'local_datahub',
                                        array('filename' => $filename,
                                        'recordsprocessed' => $record->filesuccesses + $record->filefailures,
                                        'totalrecords' => $record->totalrecords));
        } else if ($this->filefailures == 0) {
            //success message
            $record->statusmessage = "All lines from import file {$filename} were successfully processed.";
        } else {
            $record->statusmessage = "One or more lines from import file {$filename} failed because they contain data errors. Please fix the import file and re-upload it.";
        }
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @uses  $OUTPUT
     */
    function display_log($record, $filename) {
        global $OUTPUT;

        $logfile_status = $this->get_logfile_status();
        if ($this->manual) {
            if ($this->maxruntimeexceeded) {
                $displaystring = get_string('dblogimportexceedstimelimit',
                                     'local_datahub',
                                     array('filename' => $filename,
                                     'recordsprocessed' => $record->filesuccesses + $record->filefailures,
                                     'totalrecords' => $record->totalrecords));
                $css = 'generalbox warning manualstatusbox';
                echo $OUTPUT->box($displaystring, $css);
            }
            if (!$logfile_status) {
                $logfilepath = get_config($this->plugin,'logfilelocation');
                $displaystring = get_string('importinvalidlogfilepath',
                                     'local_datahub',
                                     array('filename' => $filename,
                                     'recordsprocessed' => $record->filesuccesses + $record->filefailures,
                                     'logfilepath' => $logfilepath,
                                     'totalrecords' => $record->totalrecords));
                $css = 'errorbox manualstatusbox';
                echo $OUTPUT->box($displaystring, $css);
            }
            //only display the success message if the above two tests are ok
            if ($logfile_status && !$this->maxruntimeexceeded) {
                //total rows = successes + failures
                $record->total = $record->filesuccesses + $record->filefailures;

                //display status message with successes and total records
                $displaystring = get_string('manualstatus', 'local_datahub', $record);
                $css = 'generalbox manualstatusbox';
                echo $OUTPUT->box($displaystring, $css);
            }
        }
    }

    /**
     * Signals that one or more columns are missing from the import file
     *
     * @param string $message The message to log related to missing columns
     */
    function signal_missing_columns($message) {
        $this->missingcolumns = true;
        $this->missingcolumnsmessage = $message;
    }
}

/**
 * Database logging class for exports
 */
class rlip_dblogger_export extends rlip_dblogger {
    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    function customize_record($record, $filename) {
        //flag as export
        $record->export = 1;

        //message
        if (!$this->get_logfile_status()) {
            $logfilepath = get_config($this->plugin,'logfilelocation');
            $record->filesuccesses = 0; // TBD
            $record->statusmessage = get_string('exportinvalidlogfilepath',
                                     'local_datahub',
                                     array('logfilepath' => $logfilepath));
        } else if ($error = $this->get_exportpath_error()) {
            $record->statusmessage = $error;
        } else if ($this->maxruntimeexceeded) {
            $record->filesuccesses = 0; // TBD
            // maxruntime exceeded message
            $record->statusmessage = "Export file {$filename} not created due to time limit exceeded!";
        } else {
            $record->statusmessage = "Export file {$filename} successfully created.";
        }
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @uses  $OUTPUT
     */
    function display_log($record, $filename) {
        if ($this->manual) {
            global $OUTPUT;
            if (!$this->get_logfile_status()) {
                $logfilepath = get_config($this->plugin,'logfilelocation');
                $displaystring = get_string('exportinvalidlogfilepath',
                                             'local_datahub',
                                             array('logfilepath' => $logfilepath));
                $css = 'errorbox manualstatusbox';
                echo $OUTPUT->box($displaystring, 'errorbox manualstatusbox');
            }
            if ($error = $this->get_exportpath_error()) {
                $css = 'errorbox manualstatusbox';
                echo $OUTPUT->box($error, 'errorbox manualstatusbox');
            }
            if ($this->maxruntimeexceeded) {
                echo $OUTPUT->box("Export file {$filename} not created due to time limit exceeded!", 'generalbox manualstatusbox'); // TBD
            }
        }
    }
}
