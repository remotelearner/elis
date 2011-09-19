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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_elis_program_upgrade($oldversion=0) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2011070800) {
        // Must switch enum in table: crlm_curriculum_course  field: timeperiod
        // to type text, small - saving & restoring table data
        $tabname = 'crlm_curriculum_course';
        $fldname = 'timeperiod';
        $table = new xmldb_table($tabname);
        $field = new xmldb_field($fldname);

        // save existing field data
        $rs = $DB->get_recordset($tabname, null, '', 'id, '. $fldname);

        // drop ENUM field
        $dbman->drop_field($table, $field);

        // re-add w/o ENUM - convert to text, small
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null,
                               null, 'frequency');
        $dbman->add_field($table, $field);

        // Restore old field data to new field
        if (!empty($rs)) {
            foreach ($rs as $rec) {
                if (empty($rec->timeperiod)) {
                    $rec->timeperiod = 'year';
                }
                if (!($result = $result && $DB->update_record($tabname, $rec))) {
                    error_log("xmldb_elis_program_upgrade(): update error!");
                    break;
                }
            }
            $rs->close();
        }

        //error_log("xmldb_elis_program_upgrade(): result = {$result}");
        upgrade_plugin_savepoint($result, 2011070800, 'elis', 'program');
    }

    if ($oldversion < 2011080200) {

        // Changing the default of field autounenrol on table crlm_cluster_track to 0
        $table = new xmldb_table('crlm_cluster_track');
        $field = new xmldb_field('autounenrol', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'autoenrol');

        // Launch change of default for field autounenrol
        $dbman->change_field_default($table, $field);

        // elis savepoint reached
        upgrade_plugin_savepoint(true, 2011080200, 'elis', 'program');
    }

    if ($result && $oldversion < 2011091600) {
        require_once($CFG->dirroot.'/blocks/curr_admin/lib.php');
        //make sure the site has exactly one curr admin block instance
        //that is viewable everywhere
        block_curr_admin_create_instance();

        // elis savepoint reached
        upgrade_plugin_savepoint(true, 2011091600, 'elis', 'program');
    }

    if ($result && $oldversion < 2011091900) {
        require_once($CFG->dirroot.'/blocks/curr_admin/lib.php');

        //migrate tag data to custom fields
        pm_migrate_tags();
        //migrade environment data to custom fields
        pm_migrate_environments();

        // elis savepoint reached
        upgrade_plugin_savepoint(true, 2011091900, 'elis', 'program');
    }

    return $result;
}

