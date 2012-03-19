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

require_once($CFG->dirroot.'/blocks/rlip/rlip_dataplugin.class.php');

/**
 * Base class for a provider that instantiates a file plugin
 * for a particular import entity type
 */
abstract class rlip_importprovider {
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
        require_once($CFG->dirroot.'/blocks/rlip/rlip_dblogger.class.php');

        //for now, the only db logger
        return new rlip_dblogger_import();
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

    /**
     * Import plugin constructor
     *
     * @param object $provider The import file provider that will be used to
     *                         obtain any applicable import files
     */
    function __construct($provider = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/rlip_fslogger.class.php');

        if ($provider !== NULL) {
            //note: provider is not set if only using plugin_supports

            //convert class name to plugin name 
            $class = get_class($this);
            $plugin = str_replace('rlip_importplugin_', 'rlipimport_', $class);

            $this->provider = $provider;
            $this->dblogger = $this->provider->get_dblogger();
            $this->dblogger->set_plugin($plugin);

            //set up the file-system logger
            $filename = get_config('rlipimport_version1', 'logfilelocation');
            $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true);
            $this->fslogger = new rlip_fslogger($fileplugin);
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
            return $this->get_import_actions($entity);
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
        //log line prefix
        $prefix = "[{$filename} line {$this->linenumber}]";

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
                    $fields = implode('", "', $value);
                    $messages[] = "One of \"{$fields}\" is required but all are unspecified or empty.";
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
                    $messages[] = "Required field \"{$field}\" is unspecified or empty.";
                }
            } else if (count($missing_fields) > 1) {
                $fields = implode('", "', $missing_fields);
                $messages[] = "Required fields \"{$fields}\" are unspecified or empty.";
            }

            if (count($messages) > 0) {
                //combine and log
                $message = "{$prefix} ".implode(' ', $messages);
                $this->fslogger->log($message);
                return false;
            }
        }

        return true;
    }

    /**
     * Validates whether the "action" field is correctly set on a record,
     * logging error to the file system, if necessary - call from child class
     * when needed
     *
     * @param object $record One data import record
     * @param string $filename The name of the import file, to use in logging
     * @return boolean true if action field is set, otherwise false
     */
    function check_action_field($record, $filename) {
        //log prefix
        $prefix = "[{$filename} line {$this->linenumber}]";

        if ($record->action === '') {
            //not set, so error 
            $message = "{$prefix} Required field \"action\" is unspecified or empty.";
            $this->fslogger->log($message);

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

        $action = $record->action;
        $method = "{$entity}_action";

        return $this->$method($record, $action, $filename);
    }

    /**
     * Hook run after a file header is read
     *
     * @param string $entity The type of entity
     * @param array $header The header record
     */
    function header_read_hook($entity, $header) {
        //by default, nothing to do
    }

    /**
     * Entry point for processing an import file
     *
     * @param string $entity The type of entity
     */
    function process_import_file($entity) {
        //track the start time as the current time
        $this->dblogger->set_starttime(time());

        //fetch a file plugin for the current file
        $fileplugin = $this->provider->get_import_file($entity);

        if ($fileplugin === false) {
            return;
        }

        $fileplugin->open(RLIP_FILE_READ);

        if (!$header = $fileplugin->read()) {
            return;
        }

        //header read, so increment line number
        $this->linenumber++;

        $this->header_read_hook($entity, $header);

        //main processing loop
        while ($record = $fileplugin->read()) {
            //index the import record with the appropriate keys
            $record = $this->index_record($header, $record);

            //track return value
            //todo: change second parameter when in the cron
            $filename = $fileplugin->get_filename();
            $result = $this->process_record($entity, $record, $filename);
            $this->dblogger->track_success($result, true);
        }

        $fileplugin->close();

        //track the end time as the current time
        $this->dblogger->set_endtime(time());

        //flush db log record
        $filename = $fileplugin->get_filename();
        $this->dblogger->flush($filename);
    }

    /**
     * Mainline for running the import
     */
    function run() {
        //determine the entities that represent the different files to process
        $entities = $this->get_import_entities();

        //process each import file
        foreach ($entities as $entity) {
            $this->process_import_file($entity);
        }
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