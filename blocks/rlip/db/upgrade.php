<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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

defined('MOODLE_INTERNAL') || die();

function xmldb_block_rlip_upgrade($oldversion=0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012020900) {

        // Define table block_rlip_summary_log to be created
        $table = new xmldb_table('block_rlip_summary_log');

        // Adding fields to table block_rlip_summary_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('targetstarttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('filesuccesses', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('filefailures', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('storedsuccesses', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('storedfailures', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('statusmessage', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dbops', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1');
        $table->add_field('unmetdependency', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_rlip_summary_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_rlip_summary_log
        $table->add_index('plugin_ix', XMLDB_INDEX_UNIQUE, array('plugin'));
        $table->add_index('userid_ix', XMLDB_INDEX_UNIQUE, array('userid'));
        $table->add_index('targetstarttime_ix', XMLDB_INDEX_UNIQUE, array('targetstarttime'));
        $table->add_index('starttime_ix', XMLDB_INDEX_UNIQUE, array('starttime'));
        $table->add_index('endtime_ix', XMLDB_INDEX_UNIQUE, array('endtime'));

        // Launch create table for block_rlip_summary_log
        $dbman->create_table($table);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012020900, 'rlip');
    }

    if ($result && $oldversion < 2012022700) {
        // Get the summary log table, so we can make its indexes nonunique
        $table = new xmldb_table('block_rlip_summary_log');

        // Drop indexes and re-add them as not unique
        $index = new xmldb_index('plugin_ix', XMLDB_INDEX_UNIQUE, array('plugin'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('plugin_ix', XMLDB_INDEX_NOTUNIQUE, array('plugin'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('userid_ix', XMLDB_INDEX_UNIQUE, array('userid'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('targetstarttime_ix', XMLDB_INDEX_UNIQUE, array('targetstarttime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('targetstarttime_ix', XMLDB_INDEX_NOTUNIQUE, array('targetstarttime'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('starttime_ix', XMLDB_INDEX_UNIQUE, array('starttime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('starttime_ix', XMLDB_INDEX_NOTUNIQUE, array('starttime'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('endtime_ix', XMLDB_INDEX_UNIQUE, array('endtime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('endtime_ix', XMLDB_INDEX_NOTUNIQUE, array('endtime'));
        $dbman->add_index($table, $index);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012022700, 'rlip');
    }

    if ($result && $oldversion < 2012031300) {
        // Create new ip_schedule table
        $table = new xmldb_table('ip_schedule');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('config', XMLDB_TYPE_TEXT, 'medium', NULL, XMLDB_NOTNULL);

        // Adding keys to the scheduling table
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add indexes to the scheduling table
        $table->add_index('plugin_ix', XMLDB_INDEX_NOTUNIQUE, array('plugin'));
        $table->add_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch create table for schedule table
        $dbman->create_table($table);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031300, 'rlip');
    }

    if ($result && $oldversion < 2012031600) {
        // Get the summary log table, so we can add the "export" field
        $table = new xmldb_table('block_rlip_summary_log');

        // Add the "export" field
        $field = new xmldb_field('export', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'id');
        $dbman->add_field($table, $field);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031600, 'rlip');
    }

    if ($result && $oldversion < 2012031900) {
        // Get the ip schedule table, so we can add a "nextruntime" column
        $table = new xmldb_table('ip_schedule');

        // Add the "nextruntime" field
        $field = new xmldb_field('nextruntime', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'config');
        $dbman->add_field($table, $field);

        // Set up initial state
        $taskname =  $DB->sql_concat("'ipjob_'", 'ipjob.id');
        $sql = "UPDATE {ip_schedule} ipjob
                SET nextruntime = (
                  SELECT nextruntime
                  FROM {elis_scheduled_tasks} task
                  WHERE task.taskname = {$taskname}
                )";
        $DB->execute($sql);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031900, 'rlip');
    }

    if ($result && $oldversion < 2012032300) {
        // Get the ip schedule table, so we can add a "lastruntime" column
        $table = new xmldb_table('ip_schedule');

        // Add the "lastruntime" field
        $field = new xmldb_field('lastruntime', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'config');
        $dbman->add_field($table, $field);

        // Set up initial state
        $taskname =  $DB->sql_concat("'ipjob_'", 'ipjob.id');
        $sql = "UPDATE {ip_schedule} ipjob
                SET lastruntime = (
                  SELECT lastruntime
                  FROM {elis_scheduled_tasks} task
                  WHERE task.taskname = {$taskname}
                )";
        $DB->execute($sql);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012032300, 'rlip');
    }

    if ($result && $oldversion < 2012032800) {
        // Make block naming more consistent
        // Will throw an exception if problem is encountered
        $dbman->rename_table(new xmldb_table('block_rlip_summary_log'), 'block_rlip_summary_logs');
        $dbman->rename_table(new xmldb_table('ip_schedule'), 'block_rlip_schedule');
    }
    
    if ($result && $oldversion < 2012040500) {
        $table = new xmldb_table('block_rlip_summary_logs');

        $field = new xmldb_field('logpath', XMLDB_TYPE_TEXT, 'medium', null, null);
        $dbman->add_field($table, $field);

        upgrade_block_savepoint(true, 2012040500, 'rlip');
    }

    if ($result && $oldversion < 2012040900) {
        // Add a cron task for log rollover
        elis_tasks_update_definition('block_rlip');

        upgrade_block_savepoint(true, 2012040900, 'rlip');
    }

    return $result;
}
