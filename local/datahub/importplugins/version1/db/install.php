<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
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
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1/version1.class.php');

function xmldb_dhimport_version1_install() {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();

    // Run upgrade steps from old plugin if applicable.
    $oldversion = get_config('rlipimport_version1', 'version');
    if ($oldversion !== false) {
        if ($result && $oldversion < 2012032800) {
            // Make block naming more consistent
            // Will throw an exception if problem is encountered
            $dbman->rename_table(new xmldb_table('block_rlip_version1_fieldmap'), 'rlipimport_version1_mapping');
        }
    }

    // Migrate old rlipimport_version1_mapping table if it exists.
    $table = new xmldb_table('rlipimport_version1_mapping');
    if ($dbman->table_exists($table)) {
        $newtable = new xmldb_table('dhimport_version1_mapping');
        $dbman->drop_table($newtable);
        $dbman->rename_table($table, 'dhimport_version1_mapping');
    }

    $oldconfig = $DB->get_recordset('config_plugins', array('plugin' => 'rlipimport_version1'));
    foreach ($oldconfig as $oldconfigrec) {
        // We don't want version records.
        if ($oldconfigrec->name === 'version') {
            continue;
        }

        // Check if a setting already exists for this name, and delete if it does.
        $newrec = $DB->get_record('config_plugins', array('plugin' => 'dhimport_version1', 'name' => $oldconfigrec->name));
        if (!empty($newrec)) {
            $DB->delete_records('config_plugins', array('id' => $newrec->id));
        }
        $updatedrec = new \stdClass;
        $updatedrec->id = $oldconfigrec->id;
        $updatedrec->plugin = 'dhimport_version1';
        $DB->update_record('config_plugins', $updatedrec);
    }

    unset_all_config_for_plugin('rlipimport_version1');

    return true;
}
