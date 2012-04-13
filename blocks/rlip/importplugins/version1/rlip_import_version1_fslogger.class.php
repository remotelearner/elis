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

require_once(dirname(__FILE__).'/../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');

/**
 * Class for logging general entry messages to the file system.
 * These "general" messages should likely NOT have been separated from the "specific" messages,
 * but rather inserted together.
 */
class rlip_import_version1_fslogger extends rlip_fslogger_linebased {

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
        //need the plugin class for some utility functions
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        // "action" is not always provided. In that case, return only the specific message
        if (empty($record->action)) {
            return $message;
        }

        $msg = "";

        if ($type == "enrolment") {
            //determine if a user identifier was set
            $user_identifier_set = !empty($record->username) || !empty($record->email) || !empty($record->idnumber);
            //determine if some required field is missing
            $missing_required_field = !$user_identifier_set || empty($record->instance);

            //descriptive string for user
            $user_descriptor = rlip_importplugin_version1::get_user_descriptor($record);

            switch ($record->action) {
                case "create":
                    if ($missing_required_field) {
                        //required field missing, so use generic failure message
                        $msg = "Enrolment could not be created. " . $message;
                    } else {
                        //more accurate failure message
                        $msg = "User with {$user_descriptor} could not be enroled in ".
                               "course with shortname \"{$record->instance}\". " . $message;
                    }
                    break;
                case "delete":
                    if ($missing_required_field) {
                        //required field missing, so use generic failure message
                        $msg = "Enrolment could not be deleted. " . $message;
                    } else {
                        //more accurate failure message
                        $msg = "User with {$user_descriptor} could not be unenroled ".
                               "in course with shortname \"{$record->instance}\". " . $message;
                    }
                    break;
            }
        }

        if ($type == "group") {
            switch ($record->action) {
                case "create":
                    $msg = "Group with name \"{$record->group}\" could not be created in course ".
                           "with shortname \"{$record->instance}\". " . $message;
                    break;
                case "update":
                    $msg = "Group with name \"{$record->group}\" could not be updated in course ".
                           "with shortname \"{$record->instance}\". " . $message;
                    break;
                case "delete":
                    $msg = "Group with name \"{$record->group}\" could not be deleted in course ".
                           "with shortname \"{$record->instance}\". " . $message;
                    break;
            }
        }

        if ($type == "roleassignment") {
            //determine if a user identifier was set
            $user_identifier_set = !empty($record->username) || !empty($record->email) || !empty($record->idnumber);
            //determine if all required fields were set            
            $required_fields_set = !empty($record->role) && $user_identifier_set && !empty($record->context);
            //list of contexts at which role assignments are allowed for specific instances
            $valid_contexts = array('coursecat', 'course', 'user');

            //descriptive string for user and context
            $user_descriptor = rlip_importplugin_version1::get_user_descriptor($record);
            $context_descriptor = rlip_importplugin_version1::get_context_descriptor($record);

            switch ($record->action) {
                case "create":
                    if ($required_fields_set && in_array($record->context, $valid_contexts) && !empty($record->instance)) {
                        //assignment on a specific context
                        $msg = "User with {$user_descriptor} could not be assigned role ".
                               "with shortname \"{$record->role}\" on {$context_descriptor}. " . $message;
                    } else if ($required_fields_set && $record->context == 'system') {
                        //assignment on the system context
                        $msg = "User with {$user_descriptor} could not be assigned role ".
                               "with shortname \"{$record->role}\" on the system context. " . $message;
                    } else {
                        //not valid
                        $msg = "Role assignment could not be created. " . $message;
                    }
                    break;
                case "delete":
                    if ($required_fields_set && in_array($record->context, $valid_contexts) && !empty($record->instance)) {
                        //unassignment from a specific context
                        $msg = "User with {$user_descriptor} could not be unassigned role ".
                               "with shortname \"{$record->role}\" on {$context_descriptor}. " . $message;                        
                    } else if ($required_fields_set && $record->context == 'system') {
                        //unassignment from the system context
                        $msg = "User with {$user_descriptor} could not be unassigned role ".
                               "with shortname \"{$record->role}\" on the system context. " . $message;
                    } else {
                        //not valid
                        $msg = "Role assignment could not be deleted. " . $message;
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