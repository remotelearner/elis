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
 * Class for storing import / export progress and logging end result to the
 * database
 */
class rlip_dblogger {
    //counts were are tracking

    //number of rows successfuly imported from file
    var $filesuccesses = 0;
    //number of rows with error from file
    var $filefailures = 0;
    //number of stored rows successfully impored
    var $storedsuccesses = 0;
    //number of stored rows with error
    var $storedfailures = 0;

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
     * Reset the state of the logger between executions
     */
    function reset_state() {
        //set all counts back to zero
        $this->filesuccesses = 0;
        $this->filefailures = 0;
        $this->storedsuccesses = 0;
        $this->storedfailures = 0;
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
        //todo: finish implementing
        $record = new stdClass;
        $record->userid = $USER->id;
        $record->targetstarttime = 0;
        $record->starttime = 0;
        $record->endtime = 0;
        $record->filesuccesses = $this->filesuccesses;
        $record->filefailures = $this->filefailures;
        $record->storedsuccesses = 0;
        $record->storedfailures = 0;

        if ($this->filefailures == 0) {
            //success message
            $record->statusmessage = "All lines from import file {$filename} were successfully processed.";
        } else {
            //todo: implement
            $record->statusmessage = '';
        }

        //persist
        $DB->insert_record('block_rlip_summary_log', $record);

        //reset state
        $this->reset_state();
    }
}