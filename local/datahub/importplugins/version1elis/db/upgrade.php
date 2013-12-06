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
 * @package    elis-rlip
 * @subpackage rlipimport
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_rlipimport_version1elis_upgrade($oldversion=0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012080100) {
        $table = new xmldb_table('rlipimport_version1elis_mapping');
        $result = $result && !empty($table) && $dbman->table_exists($table);
        if ($result) {
            $dbman->rename_table($table, 'rlipimport_version1elis_map');
            $result = $dbman->table_exists('rlipimport_version1elis_map');
        }
 
        // plugin savepoint reached
        upgrade_plugin_savepoint($result, 2012080100, 'rlipimport', 'version1elis');
    }

    return $result;
}
