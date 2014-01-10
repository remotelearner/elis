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
 * @subpackage dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once(dirname(__FILE__).'/../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/accesslib.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once($CFG->dirroot.'/local/elisprogram/accesslib.php');
    require_once(elis::lib('data/customfield.class.php'));
    require_once(elispm::lib('data/curriculum.class.php'));
    require_once(elispm::lib('data/curriculumcourse.class.php'));
    require_once(elispm::lib('data/curriculumstudent.class.php'));
}

// Database table constants.
define('RLIPEXPORT_VERSION1ELIS_FIELD_TABLE', 'dhexport_version1elis_fld');

/**
 * Helper class that is used for configuring the Version 1 ELIS format export
 */
class rlipexport_version1elis_config {
    // Define the "move" directions - up or down.
    const DIR_UP = 0;
    const DIR_DOWN = 1;

    /**
     * Specifies a recordset that provides a listing of configured export
     * fields, including the mapping id, field name, export header text and
     * field order
     *
     * @return object The appropriate recordset
     */
    public static function get_configured_fields() {
        global $DB;

        $sql = "SELECT export.id, field.name, export.header, export.fieldorder
                  FROM {".field::TABLE."} field
                  JOIN {".RLIPEXPORT_VERSION1ELIS_FIELD_TABLE."} export ON field.id = export.fieldid
              ORDER BY export.fieldorder ASC";

        return $DB->get_recordset_sql($sql);
    }
}

/**
 * Callback function to clean up badly formatted incremental time values
 *
 * @param string $name The appropriate setting name
 */
function rlipexport_version1elis_incrementaldelta_updatedcallback($name) {
    global $CFG;
    require_once($CFG->dirroot.'/local/datahub/lib.php');

    if ($name == 's_rlipexport_version1elis_incrementaldelta') {
        // Have the right setting.

        // Obtain the value.
        $time_string = get_config('dhexport_version1elis', 'incrementaldelta');
        // Sanitize.
        $time_string = rlip_sanitize_time_string($time_string, '1d');

        // Flush.
        set_config('incrementaldelta', $time_string, 'dhexport_version1elis');
    }
}

/**
 * Main control class for dealing the fieldsets as a whole.
 */
class rlipexport_version1elis_extrafields {

    /**
     * Defines the prefix used for collecting defined fieldsets.
     */
    const FIELDSET_PREFIX = 'rlipexport_version1elis_extrafieldset_';

    /**
     * Get available fieldsets
     *
     * @return array An array of available fieldset classes.
     */
    public static function get_available_sets() {
        static $available_sets = array();
        if (empty($available_sets)) {
            $classes = get_declared_classes();
            foreach ($classes as $class) {
                if (strpos($class, static::FIELDSET_PREFIX) === 0) {
                    $setname = substr($class, strlen(static::FIELDSET_PREFIX));
                    if (!empty($setname)) {
                        $available_sets[] = $setname;
                    }
                }
            }
        }
        return $available_sets;
    }

    /**
     * Get an array of available fieldsets and their fields.
     *
     * @return array An array of arrays, indexed by fieldset name, each array containing a list of fields as index, and
     *               labels as value
     */
    public static function get_available_fields() {
        $fields = array();
        $sets = static::get_available_sets();
        foreach ($sets as $set) {
            $class = static::FIELDSET_PREFIX.$set;
            $fields[$set] = $class::get_available_fields();
        }
        return $fields;
    }

    /**
     * Add all additional data for all fields in all fieldsets
     *
     * @param object $record The current record.
     * @return object The fully transformed record.
     */
    public static function get_all_data($record) {
        $sets = static::get_available_sets();
        $enabled_fields = static::get_enabled_fields();

        $additional_data = array();
        foreach ($sets as $set) {
            if (!empty($enabled_fields[$set])) {
                $class = static::FIELDSET_PREFIX.$set;
                $fieldset = new $class($enabled_fields[$set]);
                $fieldset_data = $fieldset->get_data($record);
                foreach ($fieldset_data as $field => $data) {
                    $data = static::clean_outgoing_str($data);
                    $additional_data[(int)$enabled_fields[$set][$field]->fieldorder] = $data;
                }
            }
        }
        ksort($additional_data);
        return $additional_data;
    }

    /**
     * Get all additional SELECT sql fragments for the main query.
     *
     * Runs each defined and enabled fieldset's get_sql_select() function and merges into one array.
     *
     * @return array An array of additinal SELECT sql fragments to be used in the main query.
     */
    public static function get_extra_select() {
        $sets = static::get_available_sets();
        $enabled_fields = static::get_enabled_fields();
        $select = array();
        foreach ($sets as $set) {
            if (!empty($enabled_fields[$set])) {
                $class = static::FIELDSET_PREFIX.$set;
                $fieldset = new $class($enabled_fields[$set]);
                $sql_select = $fieldset->get_sql_select();
                $select = array_merge($select, $sql_select);
            }
        }
        return $select;
    }

    /**
     * Get all additional columns for the export data.
     *
     * Runs each defined and enabled fieldset's get_columns() function and assembles into one array.
     *
     * @return array An array of add additional columns to add to the export data's header.
     */
    public static function get_extra_columns() {
        $sets = static::get_available_sets();
        $enabled_fields = static::get_enabled_fields();

        // Assemble columns.
        $columns = array();
        foreach ($sets as $set) {
            if (!empty($enabled_fields[$set])) {
                $class = static::FIELDSET_PREFIX.$set;
                $fieldset = new $class($enabled_fields[$set]);
                $fieldset_columns = $fieldset->get_columns();

                // Add column to the array, indexed by it's indicated order.
                foreach ($fieldset_columns as $field => $header) {
                    $header = static::clean_outgoing_str($header);
                    $columns[(int)$enabled_fields[$set][$field]->fieldorder] = $header;
                }
            }
        }

        ksort($columns);
        return $columns;
    }

    public static function clean_outgoing_str($str) {
        // TBD return str_replace(',', ' ', $str); .
        return $str;
    }

    /**
     * Gets all extra joins needed for each enabled fieldset for the main query.
     *
     * Runs all enabled fieldset's get_sql_join() function and merged results into single array.
     *
     * @return array All required JOIN sql fragments.
     */
    public static function get_extra_joins() {
        $sets = static::get_available_sets();
        $enabled_fields = static::get_enabled_fields();
        $select = array();
        foreach ($sets as $set) {
            if (!empty($enabled_fields[$set])) {
                $class = static::FIELDSET_PREFIX.$set;
                $fieldset = new $class($enabled_fields[$set]);
                $select = array_merge($select, $fieldset->get_sql_join());
            }
        }
        return $select;
    }

    /**
     * Get current field configuration.
     *
     * @param  string $index How to index the returned array. If "field", array is indexed by fieldset/field, if "order", it is
     *                       indexed by the field order.
     * @return array An array representing the current configuration.
     */
    public static function get_config($index = 'field') {
        global $DB;
        $recs = $DB->get_records(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, array(), 'fieldorder ASC');

        $ret = array();
        foreach ($recs as $rec) {
            if ($index === 'order') {
                $ret[(int)$rec->fieldorder] = $rec;
            } else {
                $ret[$rec->fieldset.'/'.$rec->field] = $rec;
            }
        }
        if ($index === 'order') {
            ksort($ret);
        }
        return $ret;
    }

    /**
     * Get array of enabled fields, ready to pass to each fieldset object.
     *
     * @return array An array with indexes of fieldset suffixes, with each value being an array. Each array is indexed by
     *               by field, and has a value of the database record.
     *               For example:
     *               [fieldset] => array(
     *                   [field] => [database record],
     *                   [field] => [database record]
     *               )
     */
    public static function get_enabled_fields() {
        $config = static::get_config('field');
        $enabled_fields = array();
        foreach ($config as $fieldsetfield => $record) {
            $key_parts = explode('/', $fieldsetfield, 2);
            if (count($key_parts) === 2) {
                $enabled_fields[$key_parts[0]][$key_parts[1]] = $record;
            }
        }
        return $enabled_fields;
    }

    /**
     * Processes incoming form data into a format ready for self::update_configuration
     *
     * @param  array $incoming_data Incoming data, direct from the form.
     * @return array Processed data, ready to be passed to self::update_configuration
     */
    public static function process_config_formdata(array $incoming_data) {
        $processed_configuration_data = array();
        $available_fields = static::get_available_fields();

        if (!isset($incoming_data['fields']) || !is_array($incoming_data['fields'])) {
            throw new Exception('Received Invalid Field Data');
        }

        // Organize incoming fieldset/field entries based on order.
        $order = 0;
        foreach ($incoming_data['fields'] as $i => $fieldsetfield) {
            $fieldparts = explode('/', $fieldsetfield, 2);
            if (count($fieldparts) === 2) {
                $fieldset = $fieldparts[0];
                $field = $fieldparts[1];
                if (isset($available_fields[$fieldset], $available_fields[$fieldset][$field])) {
                    $processed_configuration_data[$fieldsetfield] = array(
                        'header' => '',
                        'fieldset' => $fieldparts[0],
                        'field' => $fieldparts[1],
                        'fieldorder' => $order
                    );
                    $order++;
                } else {
                    unset($incoming_data['fields'][$i]);
                }
            } else {
                unset($incoming_data['fields'][$i]);
            }
        }

        // Set name, if supplied.
        if (isset($incoming_data['fieldnames']) && is_array($incoming_data['fieldnames'])) {
            foreach ($incoming_data['fieldnames'] as $i => $name) {
                if (isset($incoming_data['fields'][$i])) {
                    $field = $incoming_data['fields'][$i];
                    $processed_configuration_data[$field]['header'] = $name;
                }
            }
        }

        return $processed_configuration_data;
    }

    /**
     * Update field configuration
     *
     * Receives a processed array of fields and modifies the database to save the state.
     *
     * @param  array  $updated_data An array of updated field information, the result of a self::process_config_formdata() call
     * @return bool   Success/fail.
     */
    public static function update_config(array $updated_data) {
        global $DB;

        $existing_config = static::get_config('field');

        // Delete fields.
        $to_delete = array_diff_key($existing_config, $updated_data);
        if (!empty($to_delete)) {
            $fieldids = array();
            foreach ($to_delete as $field) {
                $fieldids[] = $field->id;
            }
            list($select_sql, $select_params) = $DB->get_in_or_equal($fieldids);
            $select_sql = 'id '.$select_sql;
            $DB->delete_records_select(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $select_sql, $select_params);
        }

        // Add/update fields.
        foreach ($updated_data as $key => $field) {
            if (isset($existing_config[$key])) {
                // Update.
                $new_info = false;
                $record = new stdClass;
                foreach (array('header', 'fieldset', 'field', 'fieldorder') as $reckey) {
                    if ($updated_data[$key][$reckey] !== $existing_config[$key]->$reckey) {
                        $record->$reckey = $updated_data[$key][$reckey];
                        $new_info = true;
                    }
                }
                if ($new_info === true) {
                    $record->id = $existing_config[$key]->id;
                    $DB->update_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $record);
                }
            } else {
                // Add.
                $record = new stdClass;
                $record->header = $field['header'];
                $record->fieldset = $field['fieldset'];
                $record->field = $field['field'];
                $record->fieldorder = $field['fieldorder'];
                $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $record);
            }
        }
        return true;
    }

    /**
     * Handle configuration update request.
     *
     * @param string $baseurl The base url to redirect to after an action takes
     *                        place
     */
    public static function handle_form_submit($baseurl) {
        $data = data_submitted();
        if (!empty($data)) {
            $data = static::process_config_formdata($data);
            static::update_config($data);
        }

        redirect($baseurl, get_string('customfieldsuccessupdate', 'dhexport_version1elis'), 1);
    }
}

/**
 * Base interface for all version1elis extra fieldsets.
 * NOTE: All classes implementing this interface MUST begin with "rlipexport_version1elis_extrafieldset_" to be detected.
 */
interface rlipexport_version1elis_extrafieldset {
    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields();

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label
     */
    public static function get_label();

    /**
     * Constructor
     *
     * @param array $enabled_fields The currently enabled fields for this fieldset, indexed by field name.
     */
    public function __construct(array $enabled_fields);

    /**
     * Get column names for enabled fields.
     *
     * @return array An array of enabled fields and their respective column names, formatted like [field]=>[column name]
     */
    public function get_columns();

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select();

    /**
     * Get additional JOINs for the main export SQL query.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join();

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record);
}

/**
 * Base Fieldset Implementation for a fieldset.
 */
abstract class rlipexport_version1elis_extrafieldsetbase implements rlipexport_version1elis_extrafieldset {
    const FIELDSET_NAME = '';

    /**
     * @var array The currently enabled fields for this fieldset.
     */
    protected $enabled_fields = array();

    /**
     * Constructor.
     *
     * Assigns internal data for enabled field information.
     *
     * @param array $enabled_fields The currently enabled fields for this fieldset, indexed by field name.
     */
    public function __construct(array $enabled_fields) {
        $this->enabled_fields = $this->clean_enabled_fields($enabled_fields);
    }

    /**
     * Ensures enabled fields only contains fields that are listed in static::get_available_fields().
     *
     * @param  array $enabled_fields Enabled fields array from constructor.
     *
     * @return array The incoming enabled_fields array, with any elements that are not in static::get_available_fields() removed.
     */
    protected function clean_enabled_fields($enabled_fields) {
        $available_fields = static::get_available_fields();
        return array_intersect_key($enabled_fields, $available_fields);
    }

    /**
     * Gets columns for enabled custom fields.
     *
     * @return array An array of colums for enabled custom fields.
     */
    public function get_columns() {
        $available_fields = static::get_available_fields();
        $columns = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if ($this->enabled_fields[$field]->header !== '' && $this->enabled_fields[$field]->header !== null) {
                $header = $this->enabled_fields[$field]->header;
            } else {
                $header = static::get_label().' '.$available_fields[$field];
            }
            $columns[$field] = $header;
        }
        return $columns;
    }

    /**
     * Get additional JOINs for the main export SQL query.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        return array();
    }
}

/**
 * Base Fieldset Implementation for a fieldset supporting custom fields.
 */
abstract class rlipexport_version1elis_extrafieldsetcustomfieldbase extends rlipexport_version1elis_extrafieldsetbase {

    /**
     * @var int Multivalue custom fields - Field currently allows multi-value setups and has relevant data.
     */
    const MULTIVALUE_ENABLED = 1;

    /**
     * @var int Multivalue custom fields - Field does not currently allow multi-value setups but has historical data
     *          (i.e. only display first value).
     */
    const MULTIVALUE_HISTORICAL = 2;

    /**
     * @var int Multivalue Custom Fields - No multi-value data for this field.
     */
    const MULTIVALUE_NONE = 3;

    /**
     * @var array Maps custom field IDs to their multivalue status
     */
    protected $customfield_multivaluestatus = array();

    /**
     * @var array Extra SQL SELECT fragments for this fieldset
     */
    protected $sql_select = array();

    /**
     * @var array Extra SQL JOIN fragments for this fieldset.
     */
    protected $sql_joins = array();

    /**
     * @var array Holds custom field information
     */
    protected $customfield_data = array();

    /**
     * Set up local tracking of whether or not a custom field is multivalued.
     *
     * @param int $fieldid     The id of the appropriate ELIS custom user field
     * @param int $multivalued 1 if the field is multivalued, otherwise 0
     *
     * @return int The multivalue status flag, as calculated and stored for the provided field
     */
    protected function init_multivalue_status_for_field($fieldid, $multivalued) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        if (isset($this->customfield_multivaluestatus[$fieldid])) {
            return $this->customfield_multivaluestatus[$fieldid];
        }

        // Determine if multi-valued data exists for this custom field, whether the field currently supports it or not.
        $field = new field($fieldid);
        $data_table = $field->data_table();
        $sql = "SELECT 'x'
                  FROM {".$data_table."} data1
                 WHERE EXISTS (
                           SELECT 'x'
                             FROM {".$data_table."} data2
                            WHERE data1.contextid = data2.contextid
                                  AND data1.contextid IS NOT NULL
                                  AND data1.fieldid = data2.fieldid
                                  AND data1.id != data2.id
                                  AND data1.fieldid = ?
                       )";
        $params = array($fieldid);

        $multivalue_data_exists = $DB->record_exists_sql($sql, $params);

        if ($multivalue_data_exists) {
            // One or more contexts have multiple values assigned for this field.
            if ($multivalued) {
                // Field currently supports multi-values.
                $this->customfield_multivaluestatus[$fieldid] = static::MULTIVALUE_ENABLED;
            } else {
                // Field no longer supports multi-values.
                $this->customfield_multivaluestatus[$fieldid] = static::MULTIVALUE_HISTORICAL;
            }
        } else {
            // Basic single value case.
            $this->customfield_multivaluestatus[$fieldid] = static::MULTIVALUE_NONE;
        }

        return $this->customfield_multivaluestatus[$fieldid];
    }

    /**
     * Gets available custom fields for a given context level.
     *
     * @param int $context The context of the PM custom field
     *
     * @return object The appropriate recordset
     */
    public static function get_available_customfields($context = CONTEXT_ELIS_USER) {
        global $DB;

        $sql = 'SELECT field.id, field.name
                  FROM {'.field_category::TABLE.'} category
                  JOIN {'.field::TABLE.'} field ON category.id = field.categoryid
            RIGHT JOIN {'.field_contextlevel::TABLE.'} fc ON fc.fieldid = field.id
                 WHERE fc.contextlevel = :context
              ORDER BY category.sortorder ASC, field.sortorder ASC';

        $fields = $DB->get_recordset_sql($sql, array('context' => $context));
        $returnfields = array();
        foreach ($fields as $field) {
            $returnfields['field_'.$field->id] = $field->name;
        }
        $fields->close();
        return $returnfields;
    }

    /**
     * Initialize internal customfield data - records, SELECT fragments, and additional joins.
     *
     * @return bool Success/Failure
     */
    protected function init_customfield_data() {
        global $DB;

        if (!empty($this->customfield_data)) {
            return true;
        }

        $fieldids = array();
        foreach ($this->enabled_fields as $field => $name) {
            if (strpos($field, 'field_') === 0) {
                $fieldid = substr($field, strlen('field_'));
                if (is_numeric($fieldid)) {
                    $fieldids[] = $fieldid;
                }
            }
        }

        if (!empty($fieldids)) {
            $select = 'id IN ('.implode(',', array_fill(0, count($fieldids), '?')).')';
            $this->customfield_data = $DB->get_records_select(field::TABLE, $select, $fieldids);
        }

        foreach ($this->customfield_data as $fieldid => $record) {
            $field = new field($record);
            $multivaluestatus = $this->init_multivalue_status_for_field($record->id, $record->multivalued);

            // Add joins and select entries, if field is enabled.
            if (isset($this->enabled_fields['field_'.$fieldid])) {
                if ($multivaluestatus === static::MULTIVALUE_NONE) {
                    // Extra columns we'll need to display profile field values.
                    $this->sql_select[] = "custom_data_{$field->id}.data AS custom_field_{$field->id}";

                    // Extra joins we'll need to display profile field values.
                    $field_data_table = "field_data_".$field->data_type();
                    $this->sql_joins[] = "LEFT JOIN {".$field_data_table::TABLE."} custom_data_{$field->id}
                                      ON custom_data_{$field->id}.fieldid = {$field->id}
                                      AND ".static::FIELDSET_NAME."_ctx.id = custom_data_{$field->id}.contextid
                                      AND custom_data_{$field->id}.contextid IS NOT NULL";
                } else {
                    // Extra columns we'll need to display profile field values.
                    $this->sql_select[] = "'' AS custom_field_{$field->id}";
                }
            }
        }
        return true;
    }

    /**
     * Gets columns for enabled custom fields.
     *
     * @return array An array of colums for enabled custom fields.
     */
    public function get_columns() {
        $available_fields = static::get_available_fields();
        $this->init_customfield_data();

        $columns = array();
        foreach ($this->customfield_data as $fieldid => $fieldrec) {
            $fieldkey = 'field_'.$fieldid;
            if (isset($this->enabled_fields[$fieldkey])) {
                if ($this->enabled_fields[$fieldkey]->header !== '' && $this->enabled_fields[$fieldkey]->header !== null) {
                    $header = $this->enabled_fields[$fieldkey]->header;
                } else {
                    $header = $fieldrec->name;
                }
                $columns[$fieldkey] = $header;
            }
        }

        foreach ($this->enabled_fields as $field => $fieldrec) {
            if (!isset($columns[$field])) {
                if ($this->enabled_fields[$field]->header !== '' && $this->enabled_fields[$field]->header !== null) {
                    $header = $this->enabled_fields[$field]->header;
                } else {
                    $header = static::get_label().' '.$available_fields[$field];
                }
                $columns[$field] = $header;
            }
        }

        return $columns;
    }

    /**
     * Gets SQL SELECT fragments for custom fields.
     *
     * @return array An array of SQL SELECT fragments.
     */
    public function get_sql_select() {
        $this->init_customfield_data();
        return $this->sql_select;
    }

    /**
     * Gets SQL JOIN fragments for custom fields.
     *
     * @return array An array of SQL JOIN fragments.
     */
    public function get_sql_join($context_level = '', $join_field = '') {
        $this->init_customfield_data();

        $joins = array();
        if (!empty($context_level) && !empty($this->sql_joins)) {
            $joins[] = 'LEFT JOIN {context} '.static::FIELDSET_NAME.'_ctx
                            ON '.static::FIELDSET_NAME.'_ctx.contextlevel = '.$context_level.'
                            AND '.static::FIELDSET_NAME.'_ctx.instanceid = '.$join_field;
        }
        return array_merge($joins, $this->sql_joins);
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        global $CFG;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));

        $this->init_customfield_data();
        $date_format = get_string('date_format', 'dhexport_version1elis');
        $datetime_format = get_string('datetime_format', 'dhexport_version1elis');

        $extra_data = array();
        foreach ($this->customfield_data as $fieldid => $fieldrec) {
            $fieldkey = 'field_'.$fieldid;
            if (isset($this->enabled_fields[$fieldkey])) {
                $field_recparam = 'custom_field_'.$fieldid;
                if (isset($record->$field_recparam) || $record->$field_recparam === null) {
                    $field = new field($fieldrec);
                    $value = $record->$field_recparam;

                    // Set field to default data if no data present.
                    if ($value === null) {
                        $value = $field->get_default();
                    }

                    // Handle multivalue fields.
                    if ($this->customfield_multivaluestatus[$field->id] !== static::MULTIVALUE_NONE) {
                        $context = \local_elisprogram\context\user::instance($record->userid);
                        $data = field_data::get_for_context_and_field($context, $field);

                        if ($this->customfield_multivaluestatus[$field->id] == static::MULTIVALUE_ENABLED) {
                            $parts = array();
                            foreach ($data as $datum) {
                                $parts[] = $datum->data;
                            }

                            $value = implode(' / ', $parts);
                        } else {
                            $value = $data->current()->data;
                        }
                    }

                    // Display datetime fields as formatted date/time.
                    if ($field->owners['manual']->param_control === 'datetime') {
                        if ($value == 0) {
                            // Use a marker to indicate that it's not set.
                            $value = get_string('nodatemarker', 'dhexport_version1elis');
                        } else if ($field->owners['manual']->param_inctime) {
                            // Date and time.
                            $value = date($datetime_format, $value);
                        } else {
                            // Just date.
                            $value = date($date_format, $value);
                        }
                    }

                    // Remove html from text.
                    $control = $field->owners['manual']->param_control;
                    if ($control === 'text' || $control === 'textarea') {
                        $value = trim(html_to_text($value), "\n\r");
                    }

                    $extra_data[$fieldkey] = $value;
                }
            }
        }

        return $extra_data;
    }
}

/**
 * Fieldset containing user fields.
 */
class rlipexport_version1elis_extrafieldset_user extends rlipexport_version1elis_extrafieldsetcustomfieldbase {

    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'user';

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        $fields = array(
            'mi' => get_string('usermi', 'local_elisprogram'),
            'email' => get_string('email', 'local_elisprogram'),
            'email2' => get_string('email2', 'local_elisprogram'),
            'address' => get_string('address', 'local_elisprogram'),
            'address2' => get_string('address2', 'local_elisprogram'),
            'city' => get_string('city', 'moodle'),
            'state' => get_string('state', 'moodle'),
            'postalcode' => get_string('postalcode', 'local_elisprogram'),
            'country' => get_string('country', 'local_elisprogram'),
            'phone' => get_string('phone', 'moodle'),
            'phone2' => get_string('phone2', 'local_elisprogram'),
            'fax' => get_string('fax', 'local_elisprogram'),
            'birthdate' => get_string('userbirthdate', 'local_elisprogram'),
            'gender' => get_string('usergender', 'local_elisprogram'),
            'language' => get_string('user_language', 'local_elisprogram'),
            'transfercredits' => get_string('user_transfercredits', 'local_elisprogram'),
            'comments' => get_string('user_comments', 'local_elisprogram'),
            'notes' => get_string('user_notes', 'local_elisprogram'),
            'timecreated' => get_string('fld_timecreated', 'local_elisprogram'),
            'timemodified' => get_string('fld_timemodified', 'local_elisprogram'),
        );

        // Add custom fields.
        $fields = array_merge($fields, static::get_available_customfields(CONTEXT_ELIS_USER));

        return $fields;
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label.
     */
    public static function get_label() {
        return get_string('fieldset_user_label', 'dhexport_version1elis');
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if (strpos($field, 'field_') !== 0) {
                $select[] = 'u.'.$field.' AS user_'.$field;
            }
        }
        $select = array_merge($select, parent::get_sql_select());
        return $select;
    }

    /**
     * Get additional JOINs user custom fields, if enabled.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        return parent::get_sql_join(CONTEXT_ELIS_USER, 'u.id');
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        $additional_data = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            $record_field_key = static::FIELDSET_NAME.'_'.$field;
            $additional_data[$field] = (isset($record->$record_field_key)) ? $record->$record_field_key : '';
        }
        $additional_data = array_merge($additional_data, parent::get_data($record));
        return $additional_data;
    }
}

/**
 * Fieldset containing student fields.
 */
class rlipexport_version1elis_extrafieldset_student extends rlipexport_version1elis_extrafieldsetbase {

    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'student';

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        $fields = array(
            'credits' => get_string('credits', 'local_elisprogram')
        );

        return $fields;
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label.
     */
    public static function get_label() {
        return get_string('fieldset_student_label', 'dhexport_version1elis');
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if (strpos($field, 'field_') !== 0) {
                $select[] = 'stu.'.$field.' AS student_'.$field;
            }
        }
        return $select;
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        $additional_data = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            $record_field_key = static::FIELDSET_NAME.'_'.$field;
            $additional_data[$field] = (isset($record->$record_field_key)) ? $record->$record_field_key : '';
        }
        return $additional_data;
    }
}

/**
 * Fieldset containing course fields.
 */
class rlipexport_version1elis_extrafieldset_course extends rlipexport_version1elis_extrafieldsetcustomfieldbase {

    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'course';

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        $fields = array(
            'name' => get_string('course_name', 'local_elisprogram'),
            'code' => get_string('course_code', 'local_elisprogram'),
            'syllabus' => get_string('course_syllabus', 'local_elisprogram'),
            'lengthdescription' => get_string('courseform:length_description', 'local_elisprogram'),
            'length' => get_string('courseform:duration', 'local_elisprogram'),
            'credits' => get_string('credits', 'local_elisprogram'),
            'completion_grade' => get_string('completion_grade', 'local_elisprogram'),
            'cost' => get_string('cost', 'local_elisprogram'),
            'timecreated' => get_string('timecreated', 'local_elisprogram'),
            'timemodified' => get_string('fld_timemodified', 'local_elisprogram'),
            'version' => get_string('course_version', 'local_elisprogram')
        );

        // Add custom fields.
        $fields = array_merge($fields, static::get_available_customfields(CONTEXT_ELIS_COURSE));
        return $fields;
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label.
     */
    public static function get_label() {
        return get_string('fieldset_course_label', 'dhexport_version1elis');
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if (strpos($field, 'field_') !== 0) {
                $select[] = 'crs.'.$field.' AS course_'.$field;
            }
        }
        $select = array_merge($select, parent::get_sql_select());
        return $select;
    }

    /**
     * Get additional JOINs for course custom fields, if enabled.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        return parent::get_sql_join(CONTEXT_ELIS_COURSE, 'crs.id');
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        $additional_data = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            $record_field_key = static::FIELDSET_NAME.'_'.$field;
            $additional_data[$field] = (isset($record->$record_field_key)) ? $record->$record_field_key : '';
        }
        $additional_data = array_merge($additional_data, parent::get_data($record));
        return $additional_data;
    }
}

/**
 * Fieldset containing class fields.
 */
class rlipexport_version1elis_extrafieldset_class extends rlipexport_version1elis_extrafieldsetcustomfieldbase {

    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'class';

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        $fields = array(
            'idnumber' => get_string('class_idnumber', 'local_elisprogram'),
            'startdate' => get_string('class_startdate', 'local_elisprogram'),
            'enddate' => get_string('class_enddate', 'local_elisprogram'),
            'starttime' => get_string('class_starttime', 'local_elisprogram'),
            'endtime' => get_string('class_endtime', 'local_elisprogram'),
            'maxstudents' => get_string('class_maxstudents', 'local_elisprogram'),
            'instructors' => get_string('instructors', 'local_elisprogram')
        );

        // Add custom fields.
        $fields = array_merge($fields, static::get_available_customfields(CONTEXT_ELIS_CLASS));

        return $fields;
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label.
     */
    public static function get_label() {
        return get_string('fieldset_class_label', 'dhexport_version1elis');
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if ($field === 'starttime') {
                $select[] = 'cls.starttimehour AS class_starttimehour';
                $select[] = 'cls.starttimeminute AS class_starttimeminute';
            } else if ($field === 'endtime') {
                $select[] = 'cls.endtimehour AS class_endtimehour';
                $select[] = 'cls.endtimeminute AS class_endtimeminute';
            } else if ($field === 'instructors') {
                $select[] = 'cls.id AS class_id';
            } else if (strpos($field, 'field_') !== 0) {
                $select[] = 'cls.'.$field.' AS class_'.$field;
            }
        }
        $select = array_merge($select, parent::get_sql_select());
        return $select;
    }

    /**
     * Get additional JOINs for class custom fields, if enabled.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        return parent::get_sql_join(CONTEXT_ELIS_CLASS, 'cls.id');
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @staticvar array Holds a cache of instructor data. Indexed by class ID
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        global $DB;
        static $instructor_cache = array();
        $date_format = get_string('date_format', 'dhexport_version1elis');

        $additional_data = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if ($field === 'instructors' && !empty($record->class_id)) {
                if (!isset($instructor_cache[$record->class_id])) {
                    $sql = 'SELECT *
                              FROM {'.instructor::TABLE.'} ins
                              JOIN {'.user::TABLE.'} usr ON usr.id = ins.userid
                             WHERE ins.classid = :classid';
                    $instructors = $DB->get_recordset_sql($sql, array('classid' => $record->class_id));
                    $instructor_cache[$record->class_id] = '';
                    foreach ($instructors as $instructor) {
                        $inst_user = new user($instructor);
                        $instructor_cache[$record->class_id] .= (!empty($instructor_cache[$record->class_id])) ? ', ' : '';
                        $instructor_cache[$record->class_id] .= $inst_user->fullname();
                    }
                }
                $additional_data['instructors'] = $instructor_cache[$record->class_id];
            } else if ($field === 'startdate') {
                $additional_data[$field] = (isset($record->class_startdate)) ? date($date_format, $record->class_startdate) : '';
            } else if ($field === 'enddate') {
                $additional_data[$field] = (isset($record->class_enddate)) ? date($date_format, $record->class_enddate) : '';
            } else if ($field === 'starttime') {
                $additional_data[$field] = (isset($record->class_starttimehour)) ? $record->class_starttimehour : 0;
                $additional_data[$field] .= ':';
                $additional_data[$field] .= (isset($record->class_starttimeminute))
                        ? str_pad($record->class_starttimeminute, 2, '0', STR_PAD_LEFT)
                        : '00';
            } else if ($field === 'endtime') {
                $additional_data[$field] = (isset($record->class_endtimehour)) ? $record->class_endtimehour : 0;
                $additional_data[$field] .= ':';
                $additional_data[$field] .= (isset($record->class_endtimeminute))
                        ? str_pad($record->class_endtimeminute, 2, '0', STR_PAD_LEFT)
                        : '00';
            } else {
                $record_field_key = static::FIELDSET_NAME.'_'.$field;
                $additional_data[$field] = (isset($record->$record_field_key)) ? $record->$record_field_key : '';
            }
        }
        $additional_data = array_merge($additional_data, parent::get_data($record));
        return $additional_data;
    }
}

/**
 * Fieldset containing program fields.
 */
class rlipexport_version1elis_extrafieldset_program extends rlipexport_version1elis_extrafieldsetcustomfieldbase {

    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'program';

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        $fields = array(
            // Program Fields.
            'idnumber' => get_string('curriculum_idnumber', 'local_elisprogram'),
            'name' => get_string('curriculum_name', 'local_elisprogram'),
            'description' => get_string('curriculum_description', 'local_elisprogram'),
            'reqcredits' => get_string('curriculum_reqcredits', 'local_elisprogram'),
            // Student-Program Assignment Fields.
            'curass_expires' => get_string('curass_expires', 'dhexport_version1elis')
        );

        // Add custom fields.
        $fields = array_merge($fields, static::get_available_customfields(CONTEXT_ELIS_PROGRAM));

        return $fields;
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label.
     */
    public static function get_label() {
        return get_string('fieldset_program_label', 'dhexport_version1elis');
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            if ($field === 'curass_expires') {
                $select[] = 'curstu.timeexpired AS program_curass_expires';
            } else if (strpos($field, 'field_') !== 0) {
                $select[] = 'pgm.'.$field.' AS program_'.$field;
            }
        }
        $select = array_merge($select, parent::get_sql_select());
        return $select;
    }

    /**
     * Get additional JOINs for program course and student associations, as well as program custom fields if enabled.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        $join = array(
                'LEFT JOIN {'.curriculumcourse::TABLE.'} curcrs ON crs.id = curcrs.courseid',
                'LEFT JOIN {'.curriculumstudent::TABLE.'} curstu ON curstu.userid = u.id AND curstu.curriculumid = curcrs.curriculumid',
                'LEFT JOIN {'.curriculum::TABLE.'} pgm ON pgm.id = curstu.curriculumid'
        );
        $custom_fields = parent::get_sql_join(CONTEXT_ELIS_PROGRAM, 'pgm.id');
        return array_merge($join, $custom_fields);
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        $additional_data = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            $record_field_key = static::FIELDSET_NAME.'_'.$field;
            if ($field === 'curass_expires') {
                if (isset($record->$record_field_key) && is_numeric($record->$record_field_key)) {
                    $date_format = get_string('date_format', 'dhexport_version1elis');
                    $additional_data['curass_expires'] = date($date_format, $record->$record_field_key);
                } else {
                    $additional_data['curass_expires'] = get_string('nodatemarker', 'dhexport_version1elis');
                }
            } else {
                $additional_data[$field] = (isset($record->$record_field_key)) ? $record->$record_field_key : '';
            }
        }
        $additional_data = array_merge($additional_data, parent::get_data($record));
        return $additional_data;
    }
}