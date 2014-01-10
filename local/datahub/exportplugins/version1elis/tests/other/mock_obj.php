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
 * @package    dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/**
 * A mock fieldset used to test the main extrafields interface. (Detection, etc)
 */
class rlipexport_version1elis_extrafieldset_test implements rlipexport_version1elis_extrafieldset {

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        return array(
            'testfield' => 'Test Field',
            'testfield2' => 'Test Field 2',
            'testfield3' => 'Test Field 3',
            'testfield4' => 'Test Field 4',
        );
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label
     */
    public static function get_label() {
        return 'Test';
    }

    /**
     * Constructor.
     *
     * Stores $enabled_fields for later use.
     *
     * @param array $enabledfields The currently enabled fields for this fieldset, indexed by field name.
     */
    public function __construct(array $enabledfields) {
        $this->enabled_fields = $enabledfields;
    }

    /**
     * Get additional fields for the SELECT clause of the main export SQL query.
     *
     * @return array An array of fields to be included in the SELECT clause. Will be imploded with a comma when inserting into the
     *               full query.
     */
    public function get_sql_select() {
        $select = array();
        foreach ($this->enabled_fields as $field => $record) {
            $select[] = $field;
        }
        return $select;
    }

    /**
     * Get additional JOINs for the main export SQL query.
     *
     * @return array An array of table joins to imploded and added to the main query.
     */
    public function get_sql_join() {
        $join = array();
        foreach ($this->enabled_fields as $field => $record) {
            $join[] = $field;
        }
        return $join;
    }

    /**
     * Get column names for enabled fields.
     *
     * @return array An array of enabled fields and their respective column names, formatted like [field]=>[column name]
     */
    public function get_columns() {
        $columns = array();
        $defaultcolumns = static::get_available_fields();
        foreach ($this->enabled_fields as $field => $record) {
            $header = ($record->header !== '' && $record->header !== null) ? $record->header : $defaultcolumns[$field];
            $columns[$field] = $header;
        }
        return $columns;
    }

    /**
     * Get data for enable fields for a single record.
     *
     * @param  object $record The current database record for the current row of data.
     *
     * @return array An array of additional data, indexed by field.
     */
    public function get_data($record) {
        $additionaldata = array();
        foreach ($this->enabled_fields as $field => $fieldrec) {
            $additionaldata[$field] = (isset($record->$field)) ? $record->$field : '';
        }
        return $additionaldata;
    }
}

/**
 * A mock fieldset used to test the main extrafields interface. (Detection, etc)
 */
class rlipexport_version1elis_extrafieldset_test2 extends rlipexport_version1elis_extrafieldset_test {
    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        return array(
            'fieldtest' => 'Field Test',
            'fieldtest2' => 'Field Test 2',
            'fieldtest3' => 'Field Test 3',
            'fieldtest4' => 'Field Test 4',
        );
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label
     */
    public static function get_label() {
        return 'Test2';
    }
}

/**
 * A mock fieldset used to test the main extrafields interface. (Detection, etc)
 */
class rlipexport_version1elis_extrafieldset_testcustomfields extends rlipexport_version1elis_extrafieldsetcustomfieldbase {
    /**
     * Unique identifier for this fieldset.
     */
    const FIELDSET_NAME = 'testcustomfields';

    /**
     * @var array Maps custom field IDs to their multivalue status (overridden to public for test accessibility)
     */
    public $customfield_multivaluestatus = array();

    /**
     * @var array Extra SQL SELECT fragments for this fieldset (overridden to public for test accessibility)
     */
    public $sql_select = array();

    /**
     * @var array Extra SQL JOIN fragments for this fieldset. (overridden to public for test accessibility)
     */
    public $sql_joins = array();

    /**
     * @var array The currently enabled fields for this fieldset. (overridden to public for test accessibility)
     */
    public $enabled_fields = array();

    /**
     * Get available fields for this fieldset.
     *
     * @return array An array of available fields, formatted like field=>label.
     */
    public static function get_available_fields() {
        return static::get_available_customfields(CONTEXT_ELIS_USER);
    }

    /**
     * Returns a label for the fieldset. Used in the field categories column, and as a prefix for non-custom fields.
     *
     * @return string A label
     */
    public static function get_label() {
        return 'TestCustomFields';
    }

    /**
     * Test function to call and return init_customfield_data();
     * @return bool Success/Failure
     */
    public function init_customfield_data_test() {
        return parent::init_customfield_data();
    }

    /**
     * Gets SQL JOIN fragments for custom fields.
     *
     * @return array An array of SQL JOIN fragments.
     */
    public function get_sql_join() {
        $join = array();
        $join[] = "LEFT JOIN {context} ".static::FIELDSET_NAME."_ctx
                    ON ".static::FIELDSET_NAME."_ctx.contextlevel = ".CONTEXT_ELIS_USER."
                    AND ".static::FIELDSET_NAME."_ctx.instanceid = u.id";
        $join = array_merge($join, parent::get_sql_join());
        return $join;
    }
}
