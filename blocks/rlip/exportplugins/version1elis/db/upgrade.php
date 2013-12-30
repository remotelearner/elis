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

function xmldb_rlipexport_version1elis_upgrade($oldversion=0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012071200) {
        // Define table rlipexport_version1elis_fld to be created
        $table = new xmldb_table('rlipexport_version1elis_field');

        // Adding fields to table rlipexport_version1elis_field
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('header', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fieldorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table rlipexport_version1elis_field
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table rlipexport_version1elis_field
        $table->add_index('fieldid_ix', XMLDB_INDEX_UNIQUE, array('fieldid'));

        // Conditionally launch create table for rlipexport_version1elis_field
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // plugin savepoint reached
        upgrade_plugin_savepoint(true, 2012071200, 'rlipexport', 'version1elis');
    }

    if ($result && $oldversion < 2012080100) {
        $table = new xmldb_table('rlipexport_version1elis_field');
        $result = $result && !empty($table) && $dbman->table_exists($table);
        if ($result) {
            $dbman->rename_table($table, 'rlipexport_version1elis_fld');
            $result = $dbman->table_exists('rlipexport_version1elis_fld');
        }

        // plugin savepoint reached
        upgrade_plugin_savepoint($result, 2012080100, 'rlipexport', 'version1elis');
    }

    return $result;
}
