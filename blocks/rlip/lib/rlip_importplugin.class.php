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

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

/**
 * Base class for a provider that instantiates a file plugin
 * for a particular import entity type
 */
abstract class rlip_importprovider {
    //full path of the log file, including its filename, NOT relative to the
    //moodledata directory
    var $logpath = NULL;

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    abstract function get_import_file($entity);

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

        //for now, the only db logger, and assume scheduled
        return new rlip_dblogger_import(false);
    }

    /**
     * Provides the object used to log information to the file system logfile
     *
     * @param  string $plugin  the plugin
     * @param  string $entity  the entity type
     * @param boolean $manual  Set to true if a manual run
     * @param  integer $starttime the time used in the filename
     * @return object the fslogger
     */
    function get_fslogger($plugin, $entity = '', $manual = false, $starttime = 0) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');
        //set up the file-system logger
        $filepath = get_config($plugin, 'logfilelocation');

        //get filename
        $filename = rlip_log_file_name('import', $plugin, $filepath, $entity, $manual, $starttime);
        if (!empty($filename)) {
            $this->set_log_path($filename);
            $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true);
            return rlip_fslogger_factory::factory($plugin, $fileplugin, $manual);
        }
        return null;
    }

    /**
     * Set the full path of the log file, including its filename
     *
     * @param string $logpath The appropriate path and filename
     */
    public function set_log_path($logpath) {
        $this->logpath = $logpath;
    }

    /**
     * Obtains the full path of the log file, including its filename
     *
     * @return string $logpath The appropriate path and filename
     */
    public function get_log_path() {
        return $this->logpath;
    }
}

/**
 * Base class for Integration Point import plugins
 */
abstract class rlip_importplugin_base extends rlip_dataplugin {
    var $provider = NULL;
    var $dblogger = NULL;
    //file-system logger object
    var $fslogger = NULL;
    //track which import line we are on
    var $linenumber = 0;
    //type of import, true if manual
    var $manual = false;
    //stores entity type
    var $tmp_entity = '';
    //stores import actions
    var $tmp_import_actions = array();

    /**
     * Import plugin constructor
     *
     * @param object $provider The import file provider that will be used to
     *                         obtain any applicable import files
     * @param boolean $manual  Set to true if a manual run
     */
    function __construct($provider = NULL, $manual = false) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

        if ($provider !== NULL) {
            //note: provider is not set if only using plugin_supports

            //convert class name to plugin name
            $class = get_class($this);
            $plugin = str_replace('rlip_importplugin_', 'rlipimport_', $class);

            $this->provider = $provider;
            $this->dblogger = $this->provider->get_dblogger();
            $this->dblogger->set_plugin($plugin);
            $this->manual = $manual;
        }
    }

    /**
     * Determines whether the current plugin supports the supplied feature
     *
     * @param string $feature A feature description, either in the form
     *                        [entity] or [entity]_[action]
     *
     * @return mixed An array of actions for a supplied entity, an array of
     *               required fields for a supplied action, or false on error
     */
    function plugin_supports($feature) {
        $parts = explode('_', $feature);

        if (count($parts) == 1) {
            //is this entity supported?
            return $this->plugin_supports_entity($feature);
        } else if (count($parts) == 2) {
            //is this action supported?
            list($entity, $action) = $parts;
            return $this->plugin_supports_action($entity, $action);
        }

        return false;
    }

    /**
     * Determines whether the current plugin supports the supplied entity type
     *
     * @param string $entity The type of entity
     *
     * @return mixed An array of actions for a supplied entity, or false on
     *               error
     */
    function plugin_supports_entity($entity) {
        $methods = get_class_methods($this);
        //look for a method named [entity]_action
        $method = "{$entity}_action";

        if (method_exists($this, $method)) {
            /* for performance, retrieve the temporary stored import actions
             * from the previous entity; otherwise, retrieve new import actions
             */
            if ($this->tmp_entity == $entity) {
                return $this->tmp_import_actions;
            } else {
                $this->tmp_entity = $entity;
                $this->tmp_import_actions = $this->get_import_actions($entity);
                return $this->tmp_import_actions;
            }
        }

        return false;
    }

    /**
     * Determines whether the current plugin supports the supplied combination
     * of entity type and action
     *
     * @param string $entity The type of entity
     * @param string $action The action being performed
     *
     * @return mixed An array of required fields, or false on error
     */
    function plugin_supports_action($entity, $action) {
        //first make sure the entity is supported
        if (!$this->plugin_supports_entity($entity)) {
            return false;
        }

        //look for a method named [entity]_[action]
        $method = "{$entity}_{$action}";
        if (method_exists($this, $method)) {
            return $this->get_import_fields($entity, $action);
        }

        return false;
    }

    /**
     * Specifies the list of entities that the current import
     * plugin supports actions for
     *
     * @return array An array of entity types
     */
    function get_import_entities() {
        $result = array();
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            $parts = explode('_', $method);
            if (count($parts) == 2) {
                if (end($parts) == 'action') {
                    $result[] = $parts[0];
                }
            }
        }

        return $result;
    }

    /**
     * Specifies the list of actions that the current import
     * plugin supports on the supplied entity type
     *
     * @param string $entity The type of entity
     *
     * @return array An array of actions
     */
    function get_import_actions($entity) {
        $result = array();
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            $parts = explode('_', $method);
            if (count($parts) == 2) {
                if (reset($parts) == $entity && end($parts) != 'action') {
                    $result[] = $parts[1];
                }
            }
        }

        return $result;
    }

    /**
     * Specifies the list of required import fields that the
     * current import requires for the supplied entity type
     * and action
     *
     * @param string $entity The type of entity
     * @param string $action The action being performed
     *
     * @return array An array of required fields
     */
    function get_import_fields($entity, $action) {
        $attribute = 'import_fields_'.$entity.'_'.$action;

        if (property_exists($this, $attribute)) {
            return static::$$attribute;
        }

        return array();
    }

    /**
     * Re-indexes an import record based on the import header
     *
     * @param array $header Field names from the input file header
     * @param array $record One record of import data
     *
     * @return object An object with the supplied data, indexed by the columns
     *                names
     */
    function index_record($header, $record) {
        $result = new stdClass;

        //todo: add more error checking

        //iterate through header fields
        foreach ($header as $index => $shortname) {
            //look up the value from the import data
            $value = $record[$index];
            //index the result based on the header shortname
            $result->$shortname = $value;
        }

        return $result;
    }

    /**
     * Obtains a list of required fields that are missing from the supplied
     * import record (helper method)
     *
     * @param object $record One import record
     * @param array $required_fields The required fields, with sub-arrays used
     *                               in "1-of-n required" scenarios
     * @return array An array, in the same format as $required_fields
     */
    function get_missing_required_fields($record, $required_fields) {
        $result = array();

        if (empty($required_fields)) {
            return false;
        }

        foreach ($required_fields as $field_or_group) {
            if (is_array($field_or_group)) {
                //"1-of-n" secnario
                $group = $field_or_group;

                //determine if one or more values in the group is set
                $found = false;
                foreach ($group as $key => $value) {
                    if (isset($record->$value) && $record->$value != '') {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    //not found, so include this group as missing and required
                    $result[] = $group;
                }
            } else {
                //simple scenario
                $field = $field_or_group;
                if (!isset($record->$field) || $record->$field === '') {
                    //not found, so include this field as missing an required
                    $result[] = $field;
                }
            }
        }

        if (count($result) == 0) {
            return false;
        }

        return $result;
    }

    /**
     * Validate that the action field is included in the header
     *
     * @param string $entity Type of entity, such as 'user'
     * @param array $header The list of supplied header columns
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if the action column is correctly specified,
     *                 otherwise false
     */
    function check_action_header($entity, $header, $filename) {
        if (!in_array('action', $header)) {
            //action column not specified
            $message = "Import file {$filename} was not processed because it is missing the ".
                       "following column: action. Please fix the import file and re-upload it.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            $this->dblogger->signal_missing_columns($message);
            return false;
        }

        return true;
    }

    /**
     * Validate that all required fields are included in the header
     *
     * @param string $entity Type of entity, such as 'user'
     * @param array $header The list of supplied header columns
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if the action column is correctly specified,
     *                 otherwise false
     */
    function check_required_headers($entity, $header, $filename) {
        //get list of required fields
        //note: for now, assuming that the delete action is available for
        //all entity types and requires the bare minimum in terms of fields
        $required_fields = $this->plugin_supports_action($entity, 'delete');

        //convert the header into a data record
        $record = new stdClass;
        foreach ($header as $value) {
            $record->$value = $value;
        }

        //figure out which are missing
        $missing_fields = $this->get_missing_required_fields($record, $required_fields);

        if ($missing_fields !== false) {
            $field_display = '';
            $first = reset($missing_fields);

            //for now, assume "groups" are always first and only showing
            //that one problem in the log
            if (!is_array($first)) {
                //1-of-n case

                //list of fields, as displayed
                $field_display = implode(', ', $missing_fields);

                //singular/plural handling
                $label = count($missing_fields) > 1 ? 'columns' : 'column';

                $message = "Import file {$filename} was not processed because it is missing the following ".
                           "required {$label}: {$field_display}. Please fix the import file and re-upload it.";
            } else {
                //basic case, all missing fields are required

                //list of fields, as displayed
                $group = reset($missing_fields);
                $field_display = implode(', ', $group);

                $message = "Import file {$filename} was not processed because one of the following columns is ".
                           "required but all are unspecified: {$field_display}. Please fix the import file and re-upload it.";
            }

            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            $this->dblogger->signal_missing_columns($message);
            return false;
        }

        return true;
    }

    /**
     * Validates whether all required fields are set, logging to the filesystem
     * where not - call from child class where needed
     *
     * @param string $entity Type of entity, such as 'user'
     * @param object $record One data import record
     * @param string $filename The name of the import file, to use in logging
     * @param array $exceptions A mapping from a field to a key, value pair that
     *                          allows that missing field to be ignored - does
     *                          not work for "1-of-n" setups
     * @return boolean true if fields ok, otherwise false
     */
    function check_required_fields($entity, $record, $filename, $exceptions = array()) {

        //get list of required fields
        $required_fields = $this->plugin_supports_action($entity, $record->action);
        //figure out which are missing
        $missing_fields = $this->get_missing_required_fields($record, $required_fields);

        $messages = array();

        if ($missing_fields !== false) {
            //missing one or more fields

            //process "1-of-n" type fields first
            foreach ($missing_fields as $key => $value) {
                if (count($value) > 1) {
                    //use helper to do any display-related field name transformation
                    $display_value = $this->get_required_field_display($value);
                    $fields = implode(', ', $display_value);

                    $messages[] = "One of {$fields} is required but all are unspecified or empty.";
                    //remove so we don't re-process
                    unset($missing_fields[$key]);
                }
            }

            //handle absolutely required fields
            if (count($missing_fields) == 1) {
                $append = true;

                $field = reset($missing_fields);

                if (isset($exceptions[$field])) {
                    //determine the dependency key and value
                    $dependency = $exceptions[$field];
                    $key = reset(array_keys($dependency));
                    $value = reset(array_values($dependency));

                    if (isset($record->$key) && $record->$key == $value) {
                        //dependency applies, so no error
                        $append = false;
                    }
                }

                if ($append) {
                    //use helper to do any display-related field name transformation
                    $field_display = $this->get_required_field_display($field);
                    $messages[] = "Required field {$field_display} is unspecified or empty.";
                }
            } else if (count($missing_fields) > 1) {
                //use helper to do any display-related field name transformation
                $missing_fields_display = $this->get_required_field_display($missing_fields);
                $fields = implode(', ', $missing_fields_display);
                $messages[] = "Required fields {$fields} are unspecified or empty.";
            }

            if (count($messages) > 0) {
                //combine and log
                $message = implode(' ', $messages);
                //todo: consider only adding these parameters in the version 1 import plugin
                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber, $record, $entity);
                return false;
            }
        }

        return true;
    }

    /**
     * Perform any necessary transformation on required fields
     * for display purposes
     *
     * @param mixed $fieldorgroup a single field name string, or an array
     *                            of them
     * @return mixed the field or array of fields to display
     */
    function get_required_field_display($fieldorgroup) {
        return $fieldorgroup;
    }

    /**
     * Obtains the listing of fields that are available for the specified
     * entity type
     *
     * @param string $entitytype The type of entity
     */
    function get_available_fields($entitytype) {
        global $DB;

        if ($this->plugin_supports($entitytype) !== false) {
            $attribute = 'available_fields_'.$entitytype;

            $result = array_merge(array('action'), static::$$attribute);

            return $result;
        } else {
            return false;
        }
    }

    /**
     * Validates whether the "action" field is correctly set on a record,
     * logging error to the file system, if necessary - call from child class
     * when needed
     *
     * @param string $entitytype The type of entity we are performing an action on
     * @param object $record One data import record
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if action field is set, otherwise false
     */
    function check_action_field($entitytype, $record, $filename) {
        if (!isset($record->action) || $record->action === '') {
            //not set, so error

            //use helper to do any display-related field name transformation
            $field_display = $this->get_required_field_display('action');
            $message = "Required field {$field_display} is unspecified or empty.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);

            return false;
        }

        //feature, in the standard Moodle "plugin_supports" format
        $feature = $entitytype.'_'.$record->action;

        if (!$this->plugin_supports($feature)) {
            //invalid action for this entity type
            $message = "Action of \"{$record->action}\" is not supported.";
            $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            return false;
        }

        return true;
    }

    /**
     * Entry point for processing a single record
     *
     * @param string $entity The type of entity
     * @param object $record One record of import data
     * @param string $filename Import file name to user for logging
     *
     * @return boolean true on success, otherwise false
     */
    function process_record($entity, $record, $filename) {
        //increment which record we're on
        $this->linenumber++;

        $action = isset($record->action) ? $record->action : '';
        $method = "{$entity}_action";

        return $this->$method($record, $action, $filename);
    }

    /**
     * Hook run after a file header is read
     *
     * @param string $entity   The type of entity
     * @param array  $header   The header record
     * @param string $filename ?
     */
    function header_read_hook($entity, $header, $filename) {
        //by default, nothing to do
    }

    /**
     * Entry point for processing an import file
     *
     * @param string $entity       The type of entity
     * @param int    $maxruntime   The max time in seconds to complete import
     *                             default: 0 => unlimited time
     * @param object $state        Previous ran state data to continue from
     * @return mixed object        Current state of import processing,
     *                             null for success, false if file is skipped.
     */
    function process_import_file($entity, $maxruntime = 0, $state = null) {
        global $CFG;

        if (!$state) {
            $state = new stdClass;
        }

        $starttime = time();

        //track the start time as the current time
        $this->dblogger->set_starttime($starttime);

        //fetch a file plugin for the current file
        $fileplugin = $this->provider->get_import_file($entity);
        if ($fileplugin === false) {
            return false; // no error cause we're just gonna skip this entity
        }

        // must count import files lines in case of error
        $filelines = 0;
        $fileplugin->open(RLIP_FILE_READ);
        $filename = $fileplugin->get_filename();
        while ($fileplugin->read()) {
            ++$filelines;
        }

        //track the total number of records to process
        //todo: find a way to get this number more generically, e.g.
        //for non-flat formats like CSV
        $this->dblogger->set_totalrecords($filelines - 1);
        $fileplugin->close();

        //set up fslogger with this starttime for this entity
        $this->fslogger = $this->provider->get_fslogger($this->dblogger->plugin, $entity, $this->manual, $starttime);

        $this->dblogger->set_log_path($this->provider->get_log_path());
        $this->dblogger->set_entity_type($entity);

        if ($filelines == 1) {
            // header but no records or lines end with CR only
            if ($this->fslogger) {
                $message = 'Could not read data, make sure import file lines end with LF (linefeed) character: 0x0A';
                $this->fslogger->log_failure($message, 0, $filename, $this->linenumber);
            }
            $this->dblogger->track_success(false, true);
            $this->dblogger->flush($filename);
            return null;
        }

        $fileplugin->open(RLIP_FILE_READ);
        if (!$header = $fileplugin->read()) {
            return false; // no error cause we're just gonna skip this entity
        }
        //initialize line number
        $this->linenumber = 0;

        //header read, so increment line number
        $this->linenumber++;

        //check that the file directory is valid
        $filepath = get_config($this->dblogger->plugin, 'logfilelocation');
        if (!$writable = is_writable($CFG->dataroot.'/'.$filepath)) {
            //invalid folder specified for the logfile
            //log this message...
            $this->dblogger->set_endtime(time());
            $this->fslogger->set_logfile_status(false);
            $this->dblogger->set_logfile_status(false);
            $this->dblogger->flush($filename);
            return null;
        } else {
            $this->fslogger->set_logfile_status(true);
            $this->dblogger->set_logfile_status(true);
        }

        $this->header_read_hook($entity, $header, $fileplugin->get_filename());

        if (!$this->check_action_header($entity, $header, $filename)) {
            //action field not specified in the header, so we can't continue
            $this->dblogger->set_endtime(time());
            $this->dblogger->flush($filename);
            return null;
        }

        if (!$this->check_required_headers($entity, $header, $filename)) {
            //a required field is missing from the header, so we can't continue
            $this->dblogger->set_endtime(time());
            $this->dblogger->flush($filename);
            return null;
        }

        //main processing loop
        while ($record = $fileplugin->read()) {
            if (isset($state->linenumber)) {
                if ($this->linenumber < $state->linenumber) {
                    $this->linenumber++;
                    continue;
                }
                unset($state->linenumber);
            }
            // check if timelimit excceeded
            if ($maxruntime && (time() - $starttime) > $maxruntime) {
                $this->dblogger->signal_maxruntime_exceeded();
                $state->result = false;
                $state->entity = $entity;
                $state->filelines = $filelines;
                $state->linenumber = $this->linenumber;
                // clean-up before exiting ...
                $fileplugin->close();
                $this->dblogger->set_endtime(time());
                //flush db log record
                $this->dblogger->flush($filename);
                return $state;
            }
            //index the import record with the appropriate keys
            $record = $this->index_record($header, $record);

            //track return value
            //todo: change second parameter when in the cron
            $result = $this->process_record($entity, $record, $filename);

            $this->dblogger->track_success($result, true);
        }

        $fileplugin->close();

        //track the end time as the current time
        $this->dblogger->set_endtime(time());

        //flush db log record
        //$filename = $fileplugin->get_filename();
        $this->dblogger->flush($filename);

        if (!$this->manual) {
            // delete processed import file - TBD: check return
            $fileplugin->delete();
        }

        return null;
    }

    /**
     * Mainline for running the import
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (N/A for import)
     * @param int $maxruntime      The max time in seconds to complete import
     *                             default: 0 => unlimited time
     * @param object $state        Previous ran state data to continue from
     *
     * @return object              State data to pass back on re-entry,
     *                             null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        //track the provided target start time
        $this->dblogger->set_targetstarttime($targetstarttime);

        if (!$state) {
            $state = new stdClass;
        }

        //determine the entities that represent the different files to process
        $entities = $this->get_import_entities();

        //track whether some file was processed
        $file_processed = false;

        //process each import file
        foreach ($entities as $entity) {
            $starttime = time();
            if (isset($state->entity)) {
                if ($entity != $state->entity) {
                    continue;
                }
                unset($state->entity);
            }

            $result = $this->process_import_file($entity, $maxruntime, $state);

            //flag a file having been processed if method was successful
            $file_processed = $file_processed || ($result === NULL);

            if ($result !== NULL && $result !== false) {
                if ($this->fslogger) {
                    //todo: look at a better way to do this for non-flat
                    //file formats like XML
                    $a = new stdClass;
                    $a->entity = $result->entity;
                    $a->recordsprocessed = $result->linenumber - 1;
                    $a->totalrecords = $result->filelines - 1;
                    $strid = 'importexceedstimelimit_b';
                    if ($this->manual) {
                        $strid = 'manualimportexceedstimelimit_b';
                    }
                    $msg = get_string($strid, 'block_rlip', $a);
                    $this->fslogger->log_failure($msg);
                }
                return $result;
            }
            if ($maxruntime) {
                $usedtime = time() - $starttime;

                //NOTE: if no file was processed, we should keep running
                //this will never hapen in practise but is helpful in unit testing
                if ($usedtime < $maxruntime || !$file_processed) {
                    $maxruntime -= $usedtime;
                } else if (($nextentity = next($entities)) !== false) {
                    // import time limit already exceeded, log & exit
                    $this->dblogger->signal_maxruntime_exceeded();
                    $filename = '{unknown}'; // default if $fileplugin false
                    //fetch a file plugin for the current file
                    $fileplugin = $this->provider->get_import_file($entity);
                    if ($fileplugin !== false) {
                        $filename = $fileplugin->get_filename();
                    }
                    //flush db log record
                    //TODO: set end time?
                    $this->dblogger->flush($filename);
                    $state = new stdClass;
                    $state->result = false;
                    $state->entity = $nextentity;
                    if ($this->fslogger) {
                        $strid = 'importexceedstimelimit';
                        if ($this->manual) {
                            $strid = 'manualimportexceedstimelimit';
                        }
                        $msg = get_string($strid, 'block_rlip', $state);
                        $this->fslogger->log_failure($msg);
                    }
                    return $state;
                } else {
                    // actually we're done, no next entities
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * Specifies the UI labels for the various import files supported by this
     * plugin
     *
     * @return array The string labels, in the order in which the
     *               associated [entity]_action methods are defined
     */
    abstract function get_file_labels();

    /**
     * Getter for the file system logging object
     *
     * @return object The file system logging object
     */
    function get_fslogger() {
        return $this->fslogger;
    }
}
