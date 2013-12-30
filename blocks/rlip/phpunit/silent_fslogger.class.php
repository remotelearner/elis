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

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');

/**
 * Silent FSlogger used to prevent file-system logging
 */
class silent_fslogger extends rlip_fslogger {
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
        //do nothing
        return true;
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
        //do nothing
        return true;
    }
}