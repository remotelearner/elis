<?php
/**
 * A script to run certain steps that are required before an upgrade to Moodle 2.x / ELIS 2.
 *
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


define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');


/*
 * Handle duplicate records in the mdl_grade_letters table.
 */

$status = true;

// Detect if we have any duplicate records before we try to remove duplicates
$sql = "SELECT contextid, lowerboundary, letter, COUNT(*) count
        FROM {grade_letters}
        GROUP BY contextid, lowerboundary, letter
        ORDER BY count DESC";

if ($rec = $DB->get_records_sql($sql, array(), 0, 1)) {
    $count = current($rec);

    if ($count->count <= 1) {
        break;
    }

    try {
        $DB->execute('CREATE TABLE {grade_letters_temp} LIKE {grade_letters}');
    } catch (Excpetion $e) {
        $status = false;
    }

    if ($status) {
        $tx = $DB->start_delegated_transaction();

        $sql = 'INSERT INTO {grade_letters_temp} (contextid, lowerboundary, letter)
                SELECT contextid, lowerboundary, letter
                FROM {grade_letters}
                GROUP BY contextid, lowerboundary, letter';

        try {
            $DB->execute($sql);
        } catch (Exception $e) {
            $status = false;
            $tx->rollback($e);
            $tx->dispose();
            break;
        }

        $tx->allow_commit();
        $tx->dispose();
    }

    if ($status) {
        try {
            $DB->execute('DROP TABLE {grade_letters}');
            $DB->execute('RENAME TABLE {grade_letters_temp} TO {grade_letters}');
        } catch (Exception $e) {
            $status = false;
        }
    }
}


/*
 * Migrate the old Alfresco capability / role assignments to new ELIS Files capabilities.
 */

if ($status) {
    $tx = $DB->start_delegated_transaction();

    try {
        // Find all of the capabilities that are
        $select = 'capability LIKE :cap AND permission LIKE :perm';
        $params = array('cap' => 'block/repository:%', 'perm' => CAP_ALLOW);

        if ($rcaps = $DB->get_recordset_select('role_capabilities', $select, $params, 'timemodified ASC', 'id, capability')) {
            foreach ($rcaps as $rcap) {
                $rcap->capability = str_replace('block/repository:', 'repository/elis_files:', $rcap->capability);
                $DB->update_record_raw('role_capabilities', $rcap, true);
            }

            $rcaps->close();
        }
    } catch (Exception $e) {
        $status = false;
        $tx->rollback($e);
    }

    if ($status) {
        $tx->allow_commit();
    }

    $tx->dispose();
}


/*
 * Migrate the old Alfresco repository plugin configuration settings to the new ELIS Files repository plugin.
 */

if ($status) {
    $tx = $DB->start_delegated_transaction();

    try {
    // Find all of the old Alfresco repository plugin capabilities that are set to enabled
        $select = 'name LIKE :name';
        $params = array('name' => 'repository_alfresco%');

        if ($cfgs = $DB->get_recordset_select('config', $select, $params, 'name ASC')) {
            foreach ($cfgs as $cfg) {
                // We need to create a new entry in the mdl_plugin_config table and remove the mdl_config values
                $pcfg = new stdClass;
                $pcfg->plugin = 'elis_files';

                // Soem variables should not be migrated and need to just be deleted
                if ($cfg->name == 'repository_alfresco_version' || $cfg->name == 'repository_alfresco_cachetime') {
                    continue;
                }

                $pcfg->name  = str_replace('repository_alfresco_', '', $cfg->name);
                $pcfg->value = $cfg->value;

                // ELIS-3677 changing "empty" values as a workaround for limitations in the repository
                // system
                $update_setting = ($pcfg->name == 'user_quota' || $pcfg->name == 'deleteuserdir') &&
                                  $pcfg->value === '0';
                if ($update_setting) {
                    $pcfg->value = '';
                }

                $DB->insert_record_raw('config_plugins', $pcfg, false, true);
            }

            $cfgs->close();

            // Delete the old plugin configuration values
            $DB->delete_records_select('config', $select, $params);
        }
    } catch (Exception $e) {
        $status = false;
        $tx->rollback($e);
    }

    if ($status) {
        $tx->allow_commit();
    }

    $tx->dispose();
}

/*
 * Ensure that if the Alfresco SSO auth plugin is enabled that it is replaced witht the ELIS Files SSO plugin instead.
 */

if ($status) {
    $tx = $DB->start_delegated_transaction();

    try {
        $auth = $DB->get_field('config', 'value', array('name' => 'auth'));

        $auth = str_replace('alfrescosso', 'elisfilessso', $auth, $count);

        if ($count > 0) {
            $DB->set_field('config', 'value', $auth, array('name' => 'auth'));
        }
    } catch (Excpetion $e) {
        $status = false;
        $tx->rollback($e);
    }

    if ($status) {
        $tx->allow_commit();
    }

    $tx->dispose();
}
