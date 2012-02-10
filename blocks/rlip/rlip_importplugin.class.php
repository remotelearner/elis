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
        return new rlip_dblogger();
    }
}

/**
 * Base class for Integration Point import plugins
 */
class rlip_importplugin_base extends rlip_dataplugin {
    var $provider = NULL;
    var $dblogger = NULL;

    /**
     * Import plugin constructor
     *
     * @param object $provider The import file provider that will be used to
     *                         obtain any applicable import files
     */
    function __construct($provider = NULL) {
        if ($provider !== NULL) {
            //note: provider is not set if only using plugin_supports
            $this->provider = $provider;
            $this->dblogger = $this->provider->get_dblogger();
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
     * Specifies whether an import record has data for all
     * required fields
     *
     * @param array $record One record of import data
     * @param array $required_fields The fields required for the current action
     *
     * @return boolean true if the supplied entity has the required fields,
     *                 otherwise false
     */
    function record_has_required_fields($record, $required_fields) {
        //todo: implement proper checking here

        return true;
    }

    /**
     * Entry point for processing a single record
     *
     * @param string $entity The type of entity
     * @param object $record One record of import data
     *
     * @return boolean true on success, otherwise false
     */
    function process_record($entity, $record) {
        $action = $record->action;
        $method = "{$entity}_action";

        //todo: add checking for whether the action is supported for the
        //entity
        if ($required_fields = $this->plugin_supports($entity)) {
            if ($this->record_has_required_fields($record, $required_fields)) {
                return $this->$method($record, $action);
            }
        }

        return false;
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
        //fetch a file plugin for the current file
        $fileplugin = $this->provider->get_import_file($entity);

        if ($fileplugin === false) {
            return;
        }

        $fileplugin->open(RLIP_FILE_READ);

        if (!$header = $fileplugin->read()) {
            return;
        }

        $this->header_read_hook($entity, $header);

        //main processing loop
        while ($record = $fileplugin->read()) {
            //index the import record with the appropriate keys
            $record = $this->index_record($header, $record);

            //track return value
            //todo: change second parameter when in the cron
            $result = $this->process_record($entity, $record);
            $this->dblogger->track_success($result, true);
        }

        $fileplugin->close();

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
}