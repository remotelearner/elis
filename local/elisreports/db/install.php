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

    // Remove the old block ...
    $DB->delete_records('block', array('name' => 'php_report'));
    $DB->delete_records('config_plugins', array('plugin' => 'block_php_report'));

    return $result;
}
