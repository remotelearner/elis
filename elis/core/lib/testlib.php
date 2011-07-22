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

/**
 * Load a PHPUnit data set into the Moodle database.
 *
 * @params PHPUnit_Extensions_Database_DataSet_IDataSet $dataset the PHPUnit
 * data set
 * @params bool $replace whether to replace the contents of the database tables
 * @params moodle_database $db the Moodle database to use
 */
function load_phpunit_data_set(PHPUnit_Extensions_Database_DataSet_IDataSet $dataset, $replace=false, moodle_database $db = null) {
    if ($db === null) {
        global $DB;
        $db = $DB;
    }

    foreach ($dataset as $tablename => $table) {
        if ($replace) {
            $db->delete_records($tablename);
        }
        $rows = $table->getRowCount();
        for ($i = 0; $i < $rows; $i++) {
            $row = $table->getRow($i);
            if (isset($row['id'])) {
                $db->import_record($tablename, $row);
            } else {
                $db->insert_record($tablename, $row, false, true);
            }
        }
    }
}

/**
 * Overlay certain tables in a Moodle database with dummy tables that can be
 * modified without affecting the original tables.
 *
 * WARNING: This will only affect database calls made through this database
 * object.  So you either need to be able to pass a database object to
 * function/object that you are testing, or you need to replace the global $DB
 * object.
 */
class overlay_database extends moodle_database {
    /**
     * Create a new object.
     *
     * @param moodle_database $basedb the base database object
     * @param array $overlaytables an array of tables that will be overlayed
     * with the dummy tables.  The array is an associative array where the key
     * is the table name (without prefix), and the value is the component where
     * the table's structure is defined (in its db/install.xml file),
     * e.g. 'moodle', or 'block_foo'.
     * @param string $overlayprefix the prefix to use for the dummy tables
     */
    public function __construct(moodle_database $basedb, array $overlaytables, $overlayprefix='ovr_') {
        parent::__construct($basedb->external);
        $this->basedb = $basedb;
        $this->overlaytables = $overlaytables;
        $this->pattern = '/{('.implode('|', array_keys($this->overlaytables)).')}/';
        $this->overlayprefix = $overlayprefix;
        $this->temptables = $basedb->temptables;

        // create temp DB tables
        $manager = $this->get_manager();
        $xmldbfiles = array();
        foreach ($overlaytables as $tablename => $component) {
            if (!isset($xmldbfiles[$component])) {
                $filename = get_component_directory($component)."/db/install.xml";
                $xmldb_file = new xmldb_file($filename);

                if (!$xmldb_file->fileExists()) {
                    throw new ddl_exception('ddlxmlfileerror', null, 'File does not exist');
                }

                $loaded = $xmldb_file->loadXMLStructure();
                if (!$loaded || !$xmldb_file->isLoaded()) {
                    /// Show info about the error if we can find it
                    if ($structure =& $xmldb_file->getStructure()) {
                        if ($errors = $structure->getAllErrors()) {
                            throw new ddl_exception('ddlxmlfileerror', null, 'Errors found in XMLDB file: '. implode (', ', $errors));
                        }
                    }
                    throw new ddl_exception('ddlxmlfileerror', null, 'not loaded??');
                }

                $xmldbfiles[$component] = $xmldb_file;
            } else {
                $xmldb_file = $xmldbfiles[$component];
            }
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);
            // FIXME: when http://bugs.mysql.com/bug.php?id=10327 gets fixed,
            // we can switch this back to create_temp_table
            $manager->create_table($table);
        }
        $this->xmldbfiles = $xmldbfiles;

        $this->donesetup = true;
    }

    /**
     * Empty out all the overlay tables.
     */
    public function reset_overlay_tables() {
        foreach ($this->overlaytables as $tablename => $component) {
            $this->delete_records($tablename);
        }
    }

    /**
     * Clean up the temporary tables.  You'd think that if this method was
     * called dispose, then the cleanup would happen automatically, but it
     * doesn't.
     */
    public function cleanup() {
        $manager = $this->get_manager();
        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);
            // FIXME: when http://bugs.mysql.com/bug.php?id=10327 gets fixed,
            // we can switch this back to drop_temp_table
            $manager->drop_table($table);
        }
    }

    public function driver_installed() {
        return true;
    }

    public function get_prefix() {
        //return $this->basedb->get_prefix();
        // the database manager needs the overlay prefix for creating the
        // temporary tables
        return $this->overlayprefix;
    }

    public function get_dbfamily() {
        return $this->basedb->get_dbfamily();
    }

    protected function get_dbtype() {
        return 'overlay';
    }

    protected function get_dblibrary() {
        return 'test';
    }

    public function get_name() {
        return get_string('overlaydbname', 'elis_core', $this->basedb->get_name());
    }

    public function get_configuration_help() {
        return '';
    }

    public function get_configuration_hints() {
        return '';
    }

    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        // do nothing (assume that base DB object is already connected)
    }

    public function get_server_info() {
        return $this->basedb->get_server_info();
    }

    public function allowed_param_types() {
        return $this->basedb->allowed_param_types();
    }

    public function get_last_error() {
        return $this->basedb->get_last_error();
    }

    public function get_tables($usecache=true) {
        $tables = $this->basedb->get_tables($usecache);
        if (empty($this->donesetup)) {
            $tables = array_diff($tables, array_keys($this->overlaytables));
        }
        return $tables;
    }

    public function get_indexes($table) {
        return $this->basedb->get_indexes($table);
    }

    public function get_columns($table, $usecache=true) {
        return $this->basedb->get_columns($table, $usecache);
    }

    public function normalise_value($column, $value) {
        return $this->basedb->normalise_value($column, $value);
    }

    public function reset_caches() {
        $this->basedb->reset_caches();
    }

    public function setup_is_unicodedb() {
        return $this->basedb->setup_is_unicodedb();
    }

    public function change_database_structure($sql) {
        // FIXME: or should we just do nothing?
        return $this->basedb->change_database_structure($sql);
    }

    protected function fix_overlay_table_names($sql) {
        return preg_replace($this->pattern, $this->overlayprefix.'$1', $sql);
    }

    public function execute($sql, array $params=null) {
        return $this->basedb->execute($this->fix_overlay_table_names($sql), $params);
    }

    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        return $this->basedb->get_recordset_sql($this->fix_overlay_table_names($sql), $params, $limitfrom, $limitnum);
    }

    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        return $this->basedb->get_records_sql($this->fix_overlay_table_names($sql), $params, $limitfrom, $limitnum);
    }

    public function get_fieldset_sql($sql, array $params=null) {
        return $this->basedb->get_fieldset_sql($this->fix_overlay_table_names($sql), $params);
    }

    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->insert_record_raw($table, $params, $returnid, $bulk, $customsequence);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->insert_record($table, $dataobject, $returnid, $bulk);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function import_record($table, $dataobject) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->import_record($table, $dataobject);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function update_record_raw($table, $params, $bulk=false) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->update_record_raw($table, $params, $bulk);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function update_record($table, $dataobject, $bulk=false) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->update_record($table, $dataobject, $bulk);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->set_field_select($table, $newfield, $newvalue, $select, $params);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    public function delete_records_select($table, $select, array $params=null) {
        $cacheprefix = $this->basedb->prefix;
        if (isset($this->overlaytables[$table])) {
            // HACK!!!
            $this->basedb->prefix = $this->overlayprefix;
        }
        $result = $this->basedb->delete_records_select($table, $select, $params);
        $this->basedb->prefix = $cacheprefix;
        return $result;
    }

    // SQL constructs -- just hand everything over to the base DB
    public function sql_null_from_clause() {
        return $this->basedb->sql_null_from_clause();
    }

    public function sql_bitand($int1, $int2) {
        return $this->basedb->sql_bitand($int1, $int2);
    }

    public function sql_bitnot($int1) {
        return $this->basedb->sql_bitnot($int1);
    }

    public function sql_bitor($int1, $int2) {
        return $this->basedb->sql_bitor($int1, $int2);
    }

    public function sql_bitxor($int1, $int2) {
        return $this->basedb->sql_bitxor($int1, $int2);
    }

    public function sql_modulo($int1, $int2) {
        return $this->basedb->sql_modulo($int1, $int2);
    }

    public function sql_ceil($fieldname) {
        return $this->basedb->sql_ceil($fieldname);
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return $this->basedb->sql_cast_char2int($fieldname, $text);
    }

    public function sql_cast_char2real($fieldname, $text=false) {
        return $this->basedb->sql_cast_char2real($fieldname, $text);
    }

    public function sql_cast_2signed($fieldname) {
        return $this->basedb->sql_cast_char2signed($fieldname);
    }

    public function sql_compare_text($fieldname, $numchars=32) {
        return $this->basedb->sql_compare_text($fieldname, $numchars);
    }

    public function sql_like($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notlike = false, $escapechar = '\\') {
        return $this->basedb->sql_like($fieldname, $param, $casesensitive, $accentsensitive, $notlike, $escapechar);
    }

    public function sql_like_escape($text, $escapechar = '\\') {
        return $this->basedb->sql_like_escape($text, $escapechar);
    }

    public function sql_ilike() {
        return $this->basedb->sql_ilike();
    }

    public function sql_concat() {
        return $this->basedb->sql_concat();
    }

    public function sql_concat_join($separator="' '", $elements=array()) {
        return $this->basedb->sql_concat_join($separator, $elements);
    }

    public function sql_fullname($first='firstname', $last='lastname') {
        return $this->basedb->sql_fullname($first, $last);
    }

    public function sql_order_by_text($fieldname, $numchars=32) {
        return $this->basedb->sql_order_by_text($fieldname, $numchars);
    }

    public function sql_length($fieldname) {
        return $this->basedb->sql_length($fieldname);
    }

    public function sql_substr($expr, $start, $length=false) {
        return $this->basedb->sql_substr($expr, $start, $length);
    }

    public function sql_position($needle, $haystack) {
        return $this->basedb->sql_position($needle, $haystack);
    }

    public function sql_empty() {
        return $this->basedb->sql_empty();
    }

    public function sql_isempty($tablename, $fieldname, $nullablefield, $textfield) {
        return $this->basedb->sql_isempty($tablename, $fieldname, $nullablefield, $textfield);
    }

    public function sql_isnotempty($tablename, $fieldname, $nullablefield, $textfield) {
        return $this->basedb->sql_isnotempty($tablename, $fieldname, $nullablefield, $textfield);
    }

    public function sql_regex($positivematch=true) {
        return $this->basedb->sql_regex($positivematch);
    }

    // transactions -- just hand everything over to the base DB
    protected function transactions_supported() {
        return $this->basedb->transactions_supported();
    }

    public function is_transaction_started() {
        return $this->basedb->is_transaction_started();
    }

    public function transactions_forbidden() {
        return $this->basedb->transactions_forbidden();
    }

    public function start_delegated_transaction() {
        return $this->basedb->start_delegated_transaction();
    }

    protected function begin_transaction() {
        return $this->basedb->begin_transaction();
    }

    public function commit_delegated_transaction(moodle_transaction $transaction) {
        return $this->basedb->commit_delegated_transaction($transaction);
    }

    protected function commit_transaction() {
        return $this->basedb->commit_transaction();
    }

    public function rollback_delegated_transaction(moodle_transaction $transaction) {
        return $this->basedb->rollback_delegated_transaction($transaction);
    }

    protected function rollback_transaction() {
        return $this->basedb->rollback_transaction();
    }

    public function force_transaction_rollback() {
        return $this->basedb->force_transaction_rollback();
    }

    // session locking -- just hand everything over to the base DB
    public function session_lock_supported() {
        return $this->basedb->session_lock_supported();
    }

    public function get_session_lock($rowid) {
        return $this->basedb->get_session_lock($rowid);
    }

    public function release_session_lock($rowid) {
        return $this->basedb->release_session_lock($rowid);
    }

    // performance and logging -- for now, just use the base DB's numbers
    public function perf_get_reads() {
        return $this->basedb->perf_get_reads();
    }

    public function perf_get_writes() {
        return $this->basedb->perf_get_writes();
    }

    public function perf_get_queries() {
        return $this->basedb->perf_get_queries();
    }
}
