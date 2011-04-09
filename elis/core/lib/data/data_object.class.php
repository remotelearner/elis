<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/elis/core/lib/setup.php';

/**
 * Represents a database record as an object.  Fields are identified as
 * protected members with the name '_dbfield_<name>', where <name> is the database
 * field name.
 */
class elis_data_object {
    /**
     * Name of the database table.
     */
    const TABLE_NAME = '';

    /**
     * Associated records.
     * @todo how is this layed out/used?
     * - should handle both one-to-many and many-to-many relationships
     * - should be extendable with plugins
     * @var array
     * - keys are "fake" field names
     * - values are arrays with the following keys:
     *   - class: the name of the foreign class that represents the association
     *   - idfield: field in this object's record that points to the remote ID
     *     (possbly empty)
     *   - foreignidfield: field in the foreign table that points back to this field
     *   - filtermethod: method from "class" to call to get a filter object
     *     (possibly empty).  The method must take this data record as its only
     *     argument.
     *   - listmethod: method from "class" to call to get an iteration of the
     *     associated records (possibly empty).  The method must take two
     *     arguments: this data record (required) and an array of filters
     *     (optional).
     *   - countmethod: method from "class" to call to get a count of
     *     associated records.  The method must take two arguments: this data
     *     record (required) and an array of filters (optional).
     * - exactly one of idfield, foreignidfield, filtermethod, or listmethod
     *   must be defined.  If listmethod is defined, countmethod must also be
     *   defined.
     * e.g. for crlm_class class:
     * $associations = array('tracks' =>
     *                           array('class' => 'track_class',
     *                                 'foreignidfield' => 'classid'),
     *                       'course' =>
     *                           array('class' => 'course',
     *                                 'idfield' => 'courseid'),
     *                       ...);
     * This allows access to the associated course via $this->course, and to
     * the tracks via
     * - $this->tracks (gets all associated tracks)
     * - $this->get_tracks(filters) (get associated tracks subject to filters)
     * - $this->count_tracks(filters) (count associated tracks subject to
     *   filters)
     */
    static $associations = array();

    /**
     * Cache of objects retrieved for associations.  These objects are loaded
     * on-demand.
     */
    protected $_associated_objects = array();

    /**
     * Whether deleting a record requires extra steps.
     */
    static protected $delete_is_complex = false;

    /**
     * Functions to use for validating data.  Each validation function must
     * either be the name of a method (taking no arguments), or a PHP callback
     * (taking one argument: $this).
     */
    static $validation_rules = array();

    /**
     * Validation rules to ignore.  These entries should be the keys for the
     * $validation_rules array that should not be checked.
     */
    public $validation_overrides = array();

    /**
     * The database object to use.
     */
    protected $_db;

    /**
     * Whether missing fields should be loaded from the database.
     */
    private $_is_loaded = false;

    /**
     * Whether the data has not been changed since loading from the database.
     */
    private $_is_saved = false;

    /**
     * Magic constant for marking a field as not set.
     */
    private static $_unset;

    const FIELD_PREFIX = '_dbfield_';

    /**
     * Autoincrement ID field
     * @var    integer
     * @length 10
     */
    protected $_dbfield_id;

    /***************************************************************************
     * High-level methods
     **************************************************************************/

    /**
     * Construct a data object.
     * @param mixed $src record source.  It can be
     * - false: an empty object is created
     * - an integer: loads the record that has record id equal to $src
     * - an object: creates an object with field data taken from the members
     *   of $src
     * - an array: creates an object with the field data taken from the
     *   elements of $src
     * @param mixed $field_map mapping for field names from $src.  If it is a
     * string, then it will be treated as a prefix for field names.  If it is
     * an array, then it is a mapping of destination field names to source
     * field names.
     * @param array $associations pre-fetched associated objects (to avoid
     * needing to re-fetch)
     * @param boolean $from_db whether or not the record source object/array
     * comes from the database
     * @param moodle_database $database database object to use (null for the
     * default database)
     */
    public function __construct($src=false, $field_map=null, array $associations=array(), $from_db=false, moodle_database $database=null) {
        global $DB;

        if (!isset(self::$_unset)) {
            self::$_unset = new stdClass;
        }

        // mark all the fields as unset
        $reflect = new ReflectionClass(get_class($this));
        $prefix_len = strlen(self::FIELD_PREFIX);
        foreach($reflect->getProperties() as $prop) {
            if (strncmp($prop->getName(), self::FIELD_PREFIX, $prefix_len) === 0) {
                $field_name = $prop->getName();
                $this->$field_name = self::$_unset;
            }
        }

        if ($database === null) {
            $this->_db = $DB;
        } else {
            $this->_db = $database;
        }

        // initialize the object fields
        if ($src === false) {
            // do nothing
        } elseif (is_numeric($src)) {
            $this->_dbfield_id = $src;
        } elseif (is_object($src)) {
            $this->_load_data_from_record($src, false, $field_map, $from_db);
        } elseif (is_array($src)) {
            $this->_load_data_from_record((object)$src, false, $field_map, $from_db);
        } else {
            throw new ErrorException('Invalid argument');
            // FIXME: error
        }

        $this->_associated_objects = $associations;
    }

    /**
     * Delete the record from the database.
     */
    public function delete() {
        if ($this->_dbfield_id !== self::$_unset) {
            $this->_db->delete_records($this->_get_const('TABLE_NAME'), array('id' => $this->_dbfield_id));
        }
    }

    /**
     * Force loading the record from the database.
     */
    public function load($overwrite=true) {
        if (!$this->_is_loaded && $this->_dbfield_id !== self::$_unset) {
            $record = $this->_db->get_record($this->_get_const('TABLE_NAME'),
                                             array('id' => $this->_dbfield_id));
            $this->_load_data_from_record($record, $overwrite, null, true);
        }
    }

    /**
     * Save the record to the database.  This method is used to both create a
     * new record, and to update an existing record.
     */
    public function save() {
        // don't bother saving if nothing has changed
        if (!$this->_is_saved) {
            $this->validate();
            // create a dumb object for Moodle
            $record = $this->to_object();
            if ($this->_dbfield_id !== self::$_unset && !empty($this->_dbfield_id)) {
                $this->_db->update_record($this->_get_const('TABLE_NAME'), $record);
            } else {
                $this->_dbfield_id = $this->_db->insert_record($this->_get_const('TABLE_NAME'), $record);
            }
            $this->_is_saved = true;
        }
    }

    /**
     * Create a duplicate copy of the object.
     * FIXME: finish docs
     */
    public function duplicate(array $options) {
        $objs = array('_errors' => array());
        $classname = get_class($this);
        $clone = new $classname($this);
        $clone->_dbfield_id = self::$_unset;
        if (!$clone->add()) {
            $objs['_errors'][] = get_string('failed_duplicate', 'elis_cm', $this);
            return $objs;
        }

        $objs[$classname] = array($this->_dbfield_id => $clone->_dbfield_id);
        return $objs;
    }

    /**
     * Validate the record before saving.  By default, run all the validation
     * rules except for the ones that are overridden.  Each validation rule
     * should throw an exception if the data fails validation.
     */
    public function validate() {
        $validation_rules = $this->_get_static('validation_rules');
        foreach ($validation_rules as $name => $function) {
            if (!in_array($name, $this->validation_overrides)) {
                // The validation function can either be a method name or some
                // other PHP callback.  Give preference to the method if it
                // exists.
                if (is_string($function) && method_exists($this, $function)) {
                    call_user_func(array($this, $function));
                } else {
                    call_user_func($function, $this);
                }
            }
        }
    }

    /**
     * Load the records corresponding to some criteria.
     *
     * @param string $classname the name of the data object class to use
     * @param mixed $filter a filter object, or an array of filter objects.  If
     * omitted, all records will be loaded.
     * @param array $sort sort order for the records.  This is an array of
     * fields, where the array key is the field to sort by, and the value is
     * the direction (ASC or DESC) of the sort.  (If the value is neither ASC
     * nor DESC, then ASC is assumed.)
     * @param moodle_database $db database object to use
     * @return data_collection a collection
     */
    public static function find($classname, $filter=null, array $sort=array(), $limitfrom=0, $limitnum=0, moodle_database $db=null) {
        global $DB;

        $tablename = eval("return $classname::TABLE_NAME;");
        if ($db === null) {
            $db = $DB;
        }

        $sortclause = array();
        foreach ($sort as $field => $order) {
            if ($order !== 'DESC') {
                $order = 'ASC';
            }
            $sortclause[] = "$field $order";
        }
        $sortclause = implode(', ', $sortclause);

        require_once elis::lib('data/data_filter.class.php');
        if ($filter === null) {
            $sql_clauses = array();
        } elseif (is_object($filter)) {
            $sql_clauses = $filter->get_sql(true, 'd');
        } else {
            $sql_clauses = AND_filter::get_combined_sql($filter, true, 'd');
        }
        if (isset($sql_clauses['join'])) {
            $sql = "SELECT DISTINCT d.*
                      FROM {{$tablename}} d
                           {$sql_clauses['join']}";
            $parameters = $sql_clauses['join_parameters'];
            if (isset($sql_clauses['where'])) {
                $sql = "$sql WHERE {$sql_clauses['where']}";
                $parameters = array_merge($parameters, $sql_clauses['where_parameters']);
            }
            if (!empty($sortclause)) {
                $sql = "$sql ORDER BY $sortclause";
            }
            $rs = $db->get_recordset_sql($sql, $parameters, $limitfrom, $limitnum);
        } else {
            if ($filter === null) {
                // nothing
            } elseif (is_object($filter)) {
                $sql_clauses = $filter->get_sql(true);
            } else {
                $sql_clauses = AND_filter::get_combined_sql($filters, true);
            }
            if (!isset($sql_clauses['where'])) {
                $sql_clauses['where'] = '';
                $sql_clauses['where_parameters'] = array();
            }
            $rs = $db->get_recordset_select($tablename,
                                            $sql_clauses['where'],
                                            $sql_clauses['where_parameters'],
                                            $sortclause, '*', $limitfrom, $limitnum);
        }
        return new data_collection($rs, $classname, null, array(), true, $db);
    }

    /**
     * Count the records corresponding to some criteria.
     *
     * @param string $classname the name of the data object class to use
     * @param mixed $filter a filter object or an array of filter objects.  If
     * omitted, all records will be counted.
     * @param moodle_database $db database object to use
     * @return integer
     */
    public static function count($classname, $filter=null, moodle_database $db=null) {
        global $DB;

        $tablename = eval("return $classname::TABLE_NAME;");
        if ($db === null) {
            $db = $DB;
        }

        require_once elis::lib('data/data_filter.class.php');
        if ($filter === null) {
            $sql_clauses = array();
        } elseif (is_object($filter)) {
            $sql_clauses = $filter->get_sql(true, 'd');
        } else {
            $sql_clauses = AND_filter::get_combined_sql($filter, true, 'd');
        }
        if (isset($sql_clauses['join'])) {
            $sql = "SELECT COUNT(DISTINCT d.id)
                      FROM {{$tablename}} d
                           {$sql_clauses['join']}";
            $parameters = $sql_clauses['join_parameters'];
            if (isset($sql_clauses['where'])) {
                $sql = "$sql WHERE {$sql_clauses['where']}";
                $parameters = array_merge($parameters, $sql_clauses['where_parameters']);
            }
            return $db->count_records_sql($sql, $parameters);
        } else {
            if ($filter === null) {
            } elseif (is_object($filter)) {
                $sql_clauses = $filter->get_sql(true);
            } else {
                $sql_clauses = AND_filter::get_combined_sql($filter, true);
            }
            if (!isset($sql_clauses['where'])) {
                $sql_clauses['where'] = '';
                $sql_clauses['where_parameters'] = null;
            }
            return $db->count_records_select($tablename,
                                             $sql_clauses['where'],
                                             $sql_clauses['where_parameters']);
        }
    }

    /**
     * Test whether records satisfying the given filters exist
     *
     * @param string $classname the name of the data object class to use
     * @param mixed $filter a filter object or an array of filter objects.  If
     * omitted, all records will be counted.
     * @param moodle_database $db database object to use
     * @return bool true if a matching record exists, else false.
     */
    public static function exists($classname, $filter=null, moodle_database $db=null) {
        global $DB;

        $tablename = eval("return $classname::TABLE_NAME;");
        if ($db === null) {
            $db = $DB;
        }

        require_once elis::lib('data/data_filter.class.php');
        if ($filter === null) {
            $sql_clauses = array();
        } elseif (is_object($filter)) {
            $sql_clauses = $filter->get_sql(true, 'd');
        } else {
            $sql_clauses = AND_filter::get_combined_sql($filter, true, 'd');
        }
        if (isset($sql_clauses['join'])) {
            $sql = "SELECT 'x'
                      FROM {{$tablename}} d
                           {$sql_clauses['join']}";
            $parameters = $sql_clauses['join_parameters'];
            if (isset($sql_clauses['where'])) {
                $sql = "$sql WHERE {$sql_clauses['where']}";
                $parameters = array_merge($parameters, $sql_clauses['where_parameters']);
            }
            return $db->record_exists($sql, $parameters);
        } else {
            if ($filter === null) {
            } elseif (is_object($sql_clauses)) {
                $sql_clauses = $filter->get_sql(true);
            } else {
                $sql_clauses = AND_filter::get_combined_sql($filter, true);
            }
            if (!isset($sql_clauses['where'])) {
                $sql_clauses['where'] = '';
                $sql_clauses['where_parameters'] = null;
            }
            return $db->record_exists_select($tablename,
                                             $sql_clauses['where'],
                                             $sql_clauses['where_parameters']);
        }
    }

    /**
     * Delete the records corresponding to some criteria.
     *
     * @param string $classname the name of the data object class to use
     * @param mixed $filter a filter or an array of filter objects.  (Note:
     * unlike in the find and count methods, this parameter is not optional)
     * @param moodle_database $db database object to use
     */
    public static function delete_records($classname, $filter, moodle_database $db=null) {
        global $DB;

        if (eval("return !empty($classname::delete_is_complex);")) {
            // deleting involves more than just removing the DB records
            $items = eval ("return $classname::find(\$classname, \$filter, array(), 0, 0, \$db);");
            foreach ($items as $item) {
                $item->delete();
            }
            return;
        }

        $tablename = eval("return $classname::TABLE_NAME;");
        if ($db === null) {
            $db = $DB;
        }

        require_once elis::lib('data/data_filter.class.php');
        if (is_object($filter)) {
            $sql_clauses = $filter->get_sql(true);
        } else {
            $sql_clauses = AND_filter::get_combined_sql($filter);
        }
        if (!isset($sql_clauses['where'])) {
            $sql_clauses['where'] = '';
            $sql_clauses['where_parameters'] = null;
        }
        return $db->delete_records_select($tablename,
                                          $sql_clauses['where'],
                                          $sql_clauses['where_parameters']);
    }

    public function get_db() {
        return $this->_db;
    }

    /**
     * Converts the data_object a dumb object representation (without
     * associations).  This is required when using the Moodle *_record
     * functions, or get_string.
     */
    public function to_object() {
        $obj = new object;
        $reflect = new ReflectionClass(get_class($this));
        $prefix_len = strlen(self::FIELD_PREFIX);
        foreach($reflect->getProperties() as $prop) {
            if (strncmp($prop->getName(), self::FIELD_PREFIX, $prefix_len) === 0) {
                $field_name = $prop->getName();
                $name = substr($field_name, $prefix_len);
                if ($this->$field_name !== self::$_unset) {
                    $obj->$name = $this->$field_name;
                }
            }
        }
        return $obj;
    }

    /**
     * Converts the data_object an array representation (without associations).
     */
    public function to_array() {
        return (array)($this->to_object());
    }

    /***************************************************************************
     * Magic Methods
     **************************************************************************/

    /**
     * Magic get method -- allows access to fields and associations via
     * $this->fieldname and $this->associationname.
     */
    public function __get($name) {
        $field_name = self::FIELD_PREFIX.$name;
        if (property_exists(get_class($this), $field_name)) {
            if ($this->$field_name === self::$_unset) {
                if ($name === 'id') {
                    return null;
                }
                $this->load();
            }
            if ($this->$field_name === self::$_unset) {
                return null;
            } else {
                return $this->$field_name;
            }
        } else if ($this->_has_association($name)) {
            $associations = $this->_get_static('associations');
            $association = $associations[$name];
            $classname = $associations['class'];
            if (isset($association['idfield'])) {
                if (!isset($this->_associated_objects[$name])) {
                    // we don't have a cached copy, so load it and cache
                    $id_field_name = self::FIELD_PREFIX.$association['idfield'];
                    $this->_associated_objects[$name] = new $classname($this->$id_field_name);
                }
                return $this->_associated_objects[$name];
            } elseif (isset($association['foreignidfield'])) {
                require_once elis::lib('data/data_filter.class.php');
                return elis_data_object::find($classname, new field_filter($association['foreignidfield'], $this->_dbfield_id));
            } elseif (isset($association['filtermethod'])) {
                return elis_data_object::find($classname, call_user_func(array($classname,$association['filtermethod']),$this));
            } else {
                return call_user_func(array($classname,$association['listmethod']),$this);
            }
        } else {
            $trace = debug_backtrace();
            $classname = get_class($this);
            trigger_error(
                "Undefined property via __get(): $classname::\${$name} in {$trace[1]['file']} on line {$trace[1]['line']}",
                E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Magic set method -- allows setting field values via $this->fieldname.
     */
    public function __set($name, $value) {
        $field_name = self::FIELD_PREFIX.$name;
        if (property_exists(get_class($this), $field_name)) {
            $this->$field_name = $value;
            $this->_is_saved = false;
        } else {
            throw new ErrorException('Invalid access');
            // FIXME: error
        }
    }

    /**
     * Magic isset method -- allows checking if a field value is set.
     */
    public function __isset($name) {
        $field_name = self::FIELD_PREFIX.$name;
        // we have to do it this way because isset will just call this method again
        $field_isset = property_exists(get_class($this), $field_name) && $this->$field_name !== self::$_unset;
        return $field_isset || $this->_has_association($name);
    }

    /**
     * Magic unset method -- allows unsetting a field value.
     */
    public function __unset($name) {
        $field_name = self::FIELD_PREFIX.$name;
        if (property_exists(get_class($this), $field_name)) {
            $this->$field_name = self::$_unset;
        }
        // FIXME: handle associations?
    }

    /**
     * Magic method call method -- allows getting and counting associations via
     * $this->get_associationname($filters) and
     * $this->count_associationname($filters), where $filters is an (optional)
     * filter or array of filter objects.
     */
    public function __call($name, $args) {
        if (strncmp($name, 'get_', 4) === 0) {
            $name = substr($name, 4);
            if ($this->_has_association($name)) {
                $associations = $this->_get_static('associations');
                $association = $associations[$name];
                $classname = $associations['class'];
                if (isset($association['foreignidfield'])) {
                    if (isset($args[0])) {
                        // $filters specified
                        require_once elis::lib('data/data_filter.class.php');
                        $foreign_filter = new field_filter($association['foreignidfield'], $this->_dbfield_id);
                        if (is_array($args[0])) {
                            $args[0][] = $foreign_filter;
                        } else {
                            $args[0] = array($args[0], $foreign_filter);
                        }
                        return elis_data_object::find($classname,$args);
                    } else {
                        require_once elis::lib('data/data_filter.class.php');
                        return elis_data_object::find($classname, new field_filter($association['foreignidfield'], $this->_dbfield_id));
                    }
                } elseif (isset($association['filtermethod'])) {
                    if (isset($args[0])) {
                        // $filters specified
                        $foreign_filter = call_user_func(array($classname,$association['filtermethod']),$this);
                        if (is_array($args[0])) {
                            $args[0][] = $foreign_filter;
                        } else {
                            $args[0] = array($args[0], $foreign_filter);
                        }
                        return elis_data_object::find($classname, $args);
                    } else {
                        return elis_data_object::find($classname, call_user_func(array($classname,$association['filtermethod']),$this));
                    }
                } else if (isset($association['listmethod'])) {
                    array_unshift($args, $this);
                    return call_user_func_array(array($classname,$association['listmethod']), $args);
                }
            }
        } else if (strncmp($name,'count_', 6) === 0) {
            $name = substr($name, 4);
            if ($this->_has_association($name)) {
                $associations = $this->_get_static('associations');
                $association = $associations[$name];
                $classname = $associations['class'];
                if (isset($association['foreignidfield'])) {
                    if (isset($args[0])) {
                        require_once elis::lib('data/data_filter.class.php');
                        $foreign_filter = new field_filter($association['foreignidfield'], $this->_dbfield_id);
                        if (is_array($args[0])) {
                            $args[0][] = $foreign_filter;
                        } else {
                            $args[0] = array($args[0], $foreign_filter);
                        }
                        return elis_data_object::count($classname,$args);
                    } else {
                        require_once elis::lib('data/data_filter.class.php');
                        return elis_data_object::count($classname, new field_filter($association['foreignidfield'], $this->_dbfield_id));
                    }
                } elseif (isset($association['filtermethod'])) {
                    if (isset($args[0])) {
                        // $filters specified
                        $foreign_filter = call_user_func(array($classname,$association['filtermethod']),$this);
                        if (is_array($args[0])) {
                            $args[0][] = $foreign_filter;
                        } else {
                            $args[0] = array($args[0], $foreign_filter);
                        }
                        return elis_data_object::count($classname, $args);
                    } else {
                        return elis_data_object::count($classname, call_user_func(array($classname,$association['filtermethod']),$this));
                    }
                } else if (isset($association['countmethod'])) {
                    array_unshift($args, $this);
                    return call_user_func_array(array($classname,$association['countmethod']), $args);
                }
            }
        }
        $trace = debug_backtrace();
        $classname = get_class($this);
        trigger_error(
            "Call to undefined method via __call(): $classname::$name in {$trace[1]['file']} on line {$trace[1]['line']}",
            E_USER_NOTICE);
    }

    /***************************************************************************
     * Low-level methods
     **************************************************************************/

    /**
     * Load data from a record object
     * @param object $rec the source record object
     * @param boolean $overwrite whether to overwrite existing values
     * @param mixed $field_map mapping for field names from $rec.  If it is a
     * string, then it will be treated as a prefix for field names.  If it is
     * an array, then it is a mapping of destination field names to source
     * field names.
     * @param boolean $from_db whether or not the record source object/array
     * comes from the database
     */
    protected function _load_data_from_record($rec, $overwrite=false, $field_map=null, $from_db=false) {
        // find all the fields from the current object
        $reflect = new ReflectionClass(get_class($this));
        $prefix_len = strlen(self::FIELD_PREFIX);
        foreach($reflect->getProperties() as $prop) {
            if (strncmp($prop->getName(), self::FIELD_PREFIX, $prefix_len) === 0) {
                $field_name = $prop->getName();

                // figure out the name of the field to copy from
                $rec_name = substr($field_name, $prefix_len);
                if (is_string($field_map)) {
                    // just a simple prefix
                    $rec_name = $field_map.$rec_name;
                } elseif (is_array($field_map)) {
                    if (!isset($field_map[$rec_name])) {
                        // field isn't mapped -- skip it
                        continue;
                    }
                    $rec_name = $field_map[$rec_name];
                }

                // set the field from the record if:
                // - we don't have a value already set, or if we want to
                //   overwrite; and
                // - the value is set in the source record
                if (($this->$field_name === self::$_unset || $overwrite)
                    && isset($rec->$rec_name)) {
                    $this->$field_name = $rec->$rec_name;
                }
            }
        }
        $this->_is_loaded = true;
        if ($from_db) {
            $this->_is_saved = true;
        } else {
            $this->_is_saved = false;
        }
    }

    /**
     * Get the static variable for the object's class (workaround until we can
     * use "static::" from PHP 5.3)
     *
     * @param string $field the name of the static variable
     */
    protected function _get_static($field) {
        $classname = get_class($this);
        return eval("return $classname::\$$field;");
    }

    /**
     * Set the static variable for the object's class (workaround until we can
     * use "static::" from PHP 5.3)
     *
     * @param string $field the name of the static variable
     * @param mixed $value the value to set the variable to
     */
    protected function _set_static($field, $value) {
        $classname = get_class($this);
        eval("$classname::\$$field = \$value;");
    }

    /**
     * Get the static variable for the object's class (workaround until we can
     * use "static::" from PHP 5.3)
     *
     * @param string $field the name of the static variable
     */
    protected function _get_const($field) {
        $classname = get_class($this);
        return eval("return $classname::$field;");
    }

    /**
     * Convenience function to check if an association exists.
     *
     * @param string $name the name of the association to check
     */
    protected function _has_association($name) {
        $associations = $this->_get_static('associations');
        return isset($associations[$name]);
    }
}

/**
 * A collection of data objects (based on a Moodle recordset, or any other
 * iterator that contains a data record)
 */
class data_collection implements Iterator {
    /**
     * @param object $rs the iterator to base the collection on
     * @param string $dataclass the class to create the data objects from
     * @param mixed $field_map see elis_data_object constructor
     * @param array $associations see elis_data_object constructor
     * @param boolean $from_db whether or not the record source object/array
     * comes from the database
     * @param moodle_database $database see elis_data_object constructor
     */
    public function __construct($rs, $dataclass, $field_map=null, array $associations=array(), $from_db=false, moodle_database $database=null) {
        $this->rs = $rs;
        $this->dataclass = $dataclass;
        $this->field_map = $field_map;
        $this->associations = $associations;
        $this->from_db = $from_db;
        $this->database = $database;
    }

    public function current() {
        return new $this->dataclass($this->rs->current(), $this->field_map, $this->associations, $this->from_db, $this->database);
    }

    public function key() {
        return $this->rs->key();
    }

    public function next() {
        return $this->rs->next();
    }

    public function rewind() {
        return $this->rs->rewind();
    }

    public function valid() {
        return $this->rs->valid();
    }

    public function close() {
        return $this->rs->close();
    }
}

/**
 * Helper function for validating that a record has unique values in some
 * fields.
 */
function validate_is_unique(elis_data_object $record, array $fields) {
    require_once elis::lib('data/data_filter.class.php');
    $classname = get_class($record);
    $tablename = eval("return $classname::TABLE_NAME;");
    $db = $record->get_db();
    $filters = array();
    foreach ($fields as $field) {
        $filters[] = new field_filter($field, $record->$field);
    }
    if (isset($record->id)) {
        $filters[] = new field_filter('id', $record->id, field_filter::NEQ);
    }
    if (eval("return $classname::exists(\$classname, \$filters, \$record->get_db());")) {
        throw new ErrorException('Not unique');
        // FIXME: new exception
    }
}

/**
 * Helper function for validating that a field is not empty.
 */
function validate_not_empty(elis_data_object $record, $field) {
    // if it's an existing record, and the field is set but empty, or if it's a
    // new record and the field is empty, then we have an error
    if ((isset($record->id) && isset($record->$field) && empty($record->$field))
        || (!isset($record->id) && empty($record->$field))) {
        throw new ErrorException('Empty');
        // FIXME: new exception
    }
}
