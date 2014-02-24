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
 * @package    block_courserequest
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

function xmldb_block_courserequest_install() {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();

    $blockid = $DB->get_field('block', 'id', array('name' => 'course_request'));
    if ($blockid) {
        // Convert any existing old course_request block instances to courserequest blocks.
        $sql = "UPDATE {block_instances} SET blockname = 'courserequest' WHERE blockname = 'course_request'";
        $DB->execute($sql);

        // Migrate old config settings.
        $configsettings = array(
                'course_role',
                'class_role',
                'use_template_by_default',
                'use_course_fields',
                'use_class_fields',
                'create_class_with_course'
        );
        foreach ($configsettings as $configsetting) {
            $settingvalue = get_config('', 'block_course_request_'.$configsetting);
            // mtrace("Migrating setting: {$settingvalue} ...");
            if ($settingvalue !== false) {
                set_config($configsetting, $settingvalue, 'block_courserequest');
                unset_config('block_course_request_'.$configsetting, '');
            }
        }
        unset_all_config_for_plugin('block_course_request');

        // Delete old course_request record in block table.
        $DB->delete_records('block', array('id' => $blockid));
    }

    // Migrate old block_courserequest table if it exists.
    $table = new xmldb_table('block_course_request'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('block_courserequest');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'block_courserequest');
    }

    // Migrate old block_courserequest table if it exists.
    $table = new xmldb_table('block_course_request_fields'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('block_courserequest_fields');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'block_courserequest_fields');
    }

    // Migrate old block_courserequest table if it exists.
    $table = new xmldb_table('block_course_request_data'); // Old pre 2.6 table.
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('block_courserequest_data');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'block_courserequest_data');
    }

    // Migrate capabilities.
    $oldcapprefix = 'block/course_request';
    $newcapprefix = 'block/courserequest';
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
        $cap->component = str_replace('block_course_request', 'block_courserequest', $cap->component);
        $DB->update_record('capabilities', $cap);
    }

    return $result;
}
