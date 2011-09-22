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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_pmplugins_results_engine_upgrade($oldversion=0) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2011091601) {

        /// table
        $table = new xmldb_table('crlm_results_engine');

        /// Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, true);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('eventtriggertype', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('lockedgrade', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('triggerstartdate', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('criteriatype', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);


        /// Adding keys and index
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        //$table->add_index('rs_contextid', XMLDB_INDEX_UNIQUE, array('contextid'));


        /// Create table
        if (!$dbman->table_exists($table)) {
           $result = $result and $dbman->create_table($table);
        }

        /// table
        $table = new xmldb_table('crlm_results_engine_action');

        if (!$dbman->table_exists($table)) {
            /// Adding fields
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('resultengineid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('actiontype', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('minimum', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('maximum', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('trackid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
            $table->add_field('fieldata', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);

            /// Adding keys and index
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('rs_resultengineid', XMLDB_INDEX_NOTUNIQUE, array('resultengineid'));

            /// Create table
            $result = $result and $dbman->create_table($table);

        } else {

            if (!$dbman->field_exists($table, 'classid')) {

                $field = new xmldb_field('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'trackid');

                $result = $result and $dbman->add_field($table, $field);
            }
        }

        /// table
        $table = new xmldb_table('crlm_results_engine_class_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('classid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('datescheduled', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
        $table->add_field('daterun', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);

        /// Adding keys and index
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('rs_classid', XMLDB_INDEX_NOTUNIQUE, array('classid'));

        /// Create table
        if (!$dbman->table_exists($table)) {
           $result = $result and $dbman->create_table($table);
        }

        /// table
        $table = new xmldb_table('crlm_results_engine_student_log');

        /// Adding keys and index
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('classlogid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('action', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('daterun', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);

        /// Adding keys and index
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('rs_classlogid', XMLDB_INDEX_NOTUNIQUE, array('classlogid'));

        /// Create table
        if (!$dbman->table_exists($table)) {
           $result = $result and $dbman->create_table($table);
        }

        upgrade_plugin_savepoint($result, 2011091601, 'pmplugins_results_engine', '');
    }

    return $result;
}