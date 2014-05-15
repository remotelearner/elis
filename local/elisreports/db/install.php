<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote Learner.net Inc (http://www.remote-learner.net)
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
 * @package    local_elisreports
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Standard Moodle post install function defintion
 * @return bool true on success, false otherwise
 */
function xmldb_local_elisreports_install() {
    global $DB;
    $result = true;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('php_report_schedule'); // Old pre 2.6 table
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('local_elisreports_schedule');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'local_elisreports_schedule');
        $tables = array('local_eliscore_sched_tasks', 'elis_scheduled_tasks');
        foreach ($tables as $table) {
            $tableobj = new xmldb_table($table);
            if ($dbman->table_exists($tableobj)) {
                $tasks = $DB->get_recordset($table, array('plugin' => 'block/php_report'));
                foreach ($tasks as $task) {
                    $task->plugin = 'local_elisreports';
                    $task->callfile = '/local/elisreports/runschedule.php';
                    $DB->update_record($table, $task);
                }
            }
        }
    }

    // Migrate capabilities
    $oldcapprefix = 'block/php_report';
    $newcapprefix = 'local/elisreports';
    $sql = 'SELECT * FROM {role_capabilities} WHERE capability LIKE ?';
    $params = array($oldcapprefix.'%');
    $rolecaps = $DB->get_recordset_sql($sql, $params);
    foreach ($rolecaps as $rolecap) {
        $rolecap->capability = str_replace($oldcapprefix, $newcapprefix, $rolecap->capability);
        $DB->update_record('role_capabilities', $rolecap);
    }
    $sql = 'SELECT * FROM {capabilities} WHERE name LIKE ?';
    $caps = $DB->get_recordset_sql($sql, $params);
    foreach ($caps as $cap) {
        $cap->name = str_replace($oldcapprefix, $newcapprefix, $cap->name);
        $cap->component = str_replace('block_php_report', 'local_elisreports', $cap->component);
        $DB->update_record('capabilities', $cap);
    }

    // Migrate language strings
    $migrator = new \local_eliscore\install\migration\migrator('block_php_report', 'local_elisreports');
    $migrator->migrate_language_strings();

    // Remove the old block ...
    $DB->delete_records('block', array('name' => 'php_report'));
    unset_all_config_for_plugin('block_php_report');

    return $result;
}
