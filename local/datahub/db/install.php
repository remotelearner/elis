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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

require_once(dirname(__FILE__).'/../lib.php');

function xmldb_local_datahub_install() {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();

    // Migrate block instances.
    $oldrecord = $DB->get_record('block', array('name' => 'rlip'), 'id');
    if (!empty($oldrecord)) {
        // Convert any existing old rlip block instances to html blocks.
        $oldblockinsts = $DB->get_recordset('block_instances', array('blockname' => 'rlip'), '', 'id');
        if ($oldblockinsts && $oldblockinsts->valid()) {
            $plugins = get_string('plugins', 'local_datahub');
            $logs = get_string('logs', 'local_datahub');
            $obj = new stdClass();
            $pluginstag = html_writer::tag('a', $plugins, array('href' => $CFG->wwwroot.'/local/datahub/plugins.php', 'title' => $plugins));
            $logstag = html_writer::tag('a', $logs, array('href' => $CFG->wwwroot.'/local/datahub/viewlogs.php', 'title' => $logs));
            $obj->text = html_writer::tag('p', $pluginstag).html_writer::tag('p', $logstag);
            $obj->title = get_string('pluginname', 'local_datahub');
            $obj->format = 1;
            $configdata = base64_encode(serialize($obj));
            $sql = "UPDATE {block_instances} SET blockname = 'html', configdata = ? WHERE blockname = 'rlip'";
            $DB->execute($sql, array($configdata));

            // Hide blocks from all but site admins
            $cap = 'moodle/block:view';
            foreach ($oldblockinsts as $oldblockinst) {
                $context = context_block::instance($oldblockinst->id);
                $roles = get_roles_with_capability($cap, CAP_ALLOW, $context);
                foreach ($roles as $role) {
                    if ($role->id != 1) {
                        assign_capability($cap, CAP_PREVENT, $role->id, $context->id);
                    }
                }
            }
        }

        // Delete old rlip record in block table.
        $DB->delete_records('block', array('id' => $oldrecord->id));
    }

    // Migrate old config setting.
    if ($disableincron = get_config('block_rlip', 'disableincron')) {
        set_config('disableincron', $disableincron, 'local_datahub');
        unset_config('disableincron', 'block_rlip');
    }

    // Migrate old block_rlip_summary_logs table if it exists.
    $table = new xmldb_table('block_rlip_summary_logs'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('local_datahub_summary_logs');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'local_datahub_summary_logs');
    }

    // Migrate old block_rlip_schedule table if it exists.
    $table = new xmldb_table('block_rlip_schedule'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('local_datahub_schedule');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'local_datahub_schedule');

        // Migrate any elis_scheduled_tasks entries for block_rlip.
        $tableobj = new xmldb_table('elis_scheduled_tasks');
        if ($dbman->table_exists($tableobj)) {
            $tasks = $DB->get_recordset('elis_scheduled_tasks', array('plugin' => 'block_rlip'));
            foreach ($tasks as $task) {
                $task->plugin = 'local_datahub';
                $task->callfile = '/local/datahub/lib.php';
                $DB->update_record('elis_scheduled_tasks', $task);
            }
        }
    }

    // Migrate language strings
    $migrator = new \local_eliscore\install\migration\migrator('block_rlip', 'local_datahub');
    $migrator->migrate_language_strings();

    unset_all_config_for_plugin('block_rlip');

    // Remove the shortname for the old service.
    $oldservice = $DB->get_record('external_services', array('shortname' => 'rldh_webservices'));
    if (!empty($oldservice)) {
        $updated = new \stdClass;
        $updated->id = $oldservice->id;
        $updated->shortname = 'rldh_webservices_old';
        $updated->name = 'RLDH Webservices Old';
        $DB->update_record('external_services', $updated);
    }

    return $result;
}
