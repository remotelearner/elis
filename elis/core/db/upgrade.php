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

function xmldb_elis_core_upgrade($oldversion=0) {
    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2011063000) {

        // Define table elis_scheduled_tasks to be created
        $table = new xmldb_table('elis_scheduled_tasks');

        // Adding fields to table elis_scheduled_tasks
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '166', null, XMLDB_NOTNULL, null, null);
        $table->add_field('taskname', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('callfile', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('callfunction', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastruntime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('nextruntime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('blocking', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('minute', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hour', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('day', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('month', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dayofweek', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timezone', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '99');
        $table->add_field('runsremaining', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('customized', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table elis_scheduled_tasks
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table elis_scheduled_tasks
        $table->add_index('plugin_idx', XMLDB_INDEX_NOTUNIQUE, array('plugin', 'taskname'));
        $table->add_index('nextruntime_idx', XMLDB_INDEX_NOTUNIQUE, array('nextruntime'));

        // Conditionally launch create table for elis_scheduled_tasks
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table elis_workflow_instances to be created
        $table = new xmldb_table('elis_workflow_instances');

        // Adding fields to table elis_workflow_instances
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subtype', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table elis_workflow_instances
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table elis_workflow_instances
        $table->add_index('usertype_idx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'type', 'subtype'));

        // Conditionally launch create table for elis_workflow_instances
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }
    if ($result && $oldversion < 2011071200) {

        // Changing the default of field sortorder on table elis_field to 0
        $table = new xmldb_table('elis_field');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'categoryid');

        // Launch change of default for field sortorder
        $dbman->change_field_default($table, $field);

        // Changing the default of field sortorder on table elis_field_categories to 0
        $table = new xmldb_table('elis_field_categories');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'name');

        // Launch change of default for field sortorder
        $dbman->change_field_default($table, $field);

        // core savepoint reached
        upgrade_plugin_savepoint(true, 2011071200, 'elis', 'core');
    }
    if ($result && $oldversion < 2011071201) {

        // Changing the default of field forceunique on table elis_field to 0
        $table = new xmldb_table('elis_field');
        $field = new xmldb_field('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'multivalued');

        // Launch change of default for field forceunique
        $dbman->change_field_default($table, $field);

        // core savepoint reached
        upgrade_plugin_savepoint(true, 2011071201, 'elis', 'core');
    }


    return $result;
}
