<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

/**
 * Upgrade old ELIS tables.
 *
 * @param int $oldversion The old ELIS version.
 * @return bool Success/Failure.
 */
function local_elisprogram_upgrade_old_tables($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2013031400) {
        // ELIS-8066: remove blank/empty menu options from custom field menu/checkbox and defaults using them.
        $customfields = $DB->get_recordset('local_eliscore_field', null, '', 'id');
        foreach ($customfields as $id => $unused) {
            $field = new field($id);
            $field->load();
            if (isset($field->owners['manual'])) {
                $manual = new field_owner($field->owners['manual']);
                $control = $manual->param_control;
                $options = $manual->param_options;
                if (!empty($options) && empty($manual->param_options_source) && ($control == 'menu' || $control == 'checkbox')) {
                    $options = str_replace("\r", '', $options); // Strip CRs.
                    $options = preg_replace("/\n+/", "\n", $options);
                    $manual->param_options = rtrim($options, "\n");
                    $manual->save();
                    // Remove any empty defaults.
                    $DB->delete_records_select($field->data_table(), "contextid IS NULL AND fieldid = ? AND data = ''", array($id));
                }
            }
        }
        upgrade_plugin_savepoint($result, 2013031400, 'elis', 'program');
    }

    // ELIS-7780: remove deprecated capabilites.
    if ($result && $oldversion < 2013041900) {
        $capstodelete = array('elis/program:viewgroupreports', 'elis/program:viewreports');
        list($inorequal, $params) = $DB->get_in_or_equal($capstodelete);
        $where = "capability $inorequal";
        $DB->delete_records_select('role_capabilities', $where, $params);
        $where = "name $inorequal";
        $DB->delete_records_select('capabilities', $where, $params);
        upgrade_plugin_savepoint($result, 2013041900, 'elis', 'program');
    }

    // Remove any duplicate user track records before attempting to apply an index.
    pm_fix_duplicate_usertrack_records('crlm_user_track');

    if ($result && $oldversion < 2013042900) {
        // Add indexes to {crlm_user_track} table.
        $table = new xmldb_table('crlm_user_track');
        if ($dbman->table_exists($table)) {
            // Array of indexes to drop.
            $dropindexes = array(
                new xmldb_index('any_userid_ix', XMLDB_INDEX_UNIQUE, array('userid')),
                new xmldb_index('any_trackid_ix', XMLDB_INDEX_UNIQUE, array('trackid')),
                new xmldb_index('any_userid_trackid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid', 'trackid'))
            );
            foreach ($dropindexes as $index) {
                // Drop unwanted indexes if they exist.
                if ($dbman->index_exists($table, $index)) {
                    $dbman->drop_index($table, $index);
                }
            }

            // Array of indexes to create.
            $createindexes = array(
                new xmldb_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid')),
                new xmldb_index('trackid_ix', XMLDB_INDEX_NOTUNIQUE, array('trackid')),
                new xmldb_index('userid_trackid_ix', XMLDB_INDEX_UNIQUE, array('userid', 'trackid'))
            );

            foreach ($createindexes as $index) {
                // Create desired indexes as required.
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }
        }
        upgrade_plugin_savepoint($result, 2013042900, 'elis', 'program');
    }

    if ($result && $oldversion < 2013051500) {
        // Change results engine action min/max fields from integer to float.
        $table = new xmldb_table('crlm_results_action');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('minimum', XMLDB_TYPE_NUMBER, '10,5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'actiontype');
            $dbman->change_field_type($table, $field);
            $field = new xmldb_field('maximum', XMLDB_TYPE_NUMBER, '10,5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'minimum');
            $dbman->change_field_type($table, $field);
        }
        upgrade_plugin_savepoint($result, 2013051500, 'elis', 'program');
    }

    if ($result && $oldversion < 2013051502) {
        // Define table crlm_certificate_settings to be created
        // Conditionally launch create table for crlm_certificate_settings.
        $table = new xmldb_table('crlm_certificate_settings');
        if (!$dbman->table_exists($table)) {

            // Adding fields to table crlm_certificate_settings.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('entity_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('entity_type', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cert_border', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cert_seal', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cert_template', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
            $table->add_field('disable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            // Adding keys to table crlm_certificate_settings.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Adding indexes to table crlm_certificate_settings.
            $table->add_index('ent_id_type_ix', XMLDB_INDEX_UNIQUE, array('entity_id', 'entity_type'));

            $dbman->create_table($table);
        }

        // Define table crlm_certificate_issued to be created.
        $table = new xmldb_table('crlm_certificate_issued');
        // Conditionally launch create table for crlm_certificate_issued.
        if (!$dbman->table_exists($table)) {

            // Adding fields to table crlm_certificate_issued.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('cm_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cert_setting_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cert_code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timeissued', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            // Adding keys to table crlm_certificate_issued.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Adding indexes to table crlm_certificate_issued.
            $table->add_index('cert_code_ix', XMLDB_INDEX_UNIQUE, array('cert_code'));
            $table->add_index('cm_userid_ix', XMLDB_INDEX_NOTUNIQUE, array('cm_userid'));

            $dbman->create_table($table);
        }

        // ELIS savepoint reached.
        upgrade_plugin_savepoint($result, 2013051502, 'elis', 'program');
    }

    // ELIS-8528: remove orphaned LOs.
    if ($result && $oldversion < 2013051503) {
        $where = 'NOT EXISTS (SELECT \'x\' FROM {crlm_course} cc WHERE cc.id = {crlm_course_completion}.courseid)';
        $DB->delete_records_select('crlm_course_completion', $where);
        upgrade_plugin_savepoint($result, 2013051503, 'elis', 'program');
    }

    if ($result && $oldversion < 2013082100) {
        // Change password field length to 255.
        $table = new xmldb_table('crlm_user');
        $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'username');

        $dbman->change_field_precision($table, $field);

        upgrade_plugin_savepoint($result, 2013082100, 'elis', 'program');
    }

    if ($result && $oldversion < 2013082101) {
        require_once(elis::file('eliscore/fields/manual/custom_fields.php'));
        require_once(elis::file('eliscore/fields/moodleprofile/custom_fields.php'));
        $fieldcat = field_category::ensure_exists_for_contextlevel(get_string('moodlefields', 'local_elisprogram'), CONTEXT_ELIS_USER);

        $sql = 'SELECT m.id
                  FROM {user_info_field} m
                 WHERE NOT EXISTS(
                           SELECT *
                             FROM {local_eliscore_field} e
                            WHERE e.shortname = m.shortname
                       )';
        $fieldstosync = $DB->get_records_sql($sql);
        foreach ($fieldstosync as $mfield) {
            // Create field.
            $efield = field::make_from_moodle_field($mfield->id, $fieldcat, pm_moodle_profile::sync_from_moodle);

            // Sync profile field information from Moodle into ELIS.
            sync_profile_field_from_moodle($efield);
        }
        upgrade_plugin_savepoint($result, 2013082101, 'elis', 'program');
    }

    if ($result && $oldversion < 2013082103) {
        // ELIS-8441 & ELIS-8569: Fix Program timetocomplete & frequency defaults of '0y'.
        $table = new xmldb_table('crlm_curriculum');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('timetocomplete', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'timemodified');
            $dbman->change_field_default($table, $field);

            $field = new xmldb_field('frequency', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'timetocomplete');
            $dbman->change_field_default($table, $field);

            $sql = 'UPDATE {crlm_curriculum} SET timetocomplete = "" WHERE timetocomplete = "0y"';
            $DB->execute($sql);

            $sql = 'UPDATE {crlm_curriculum} SET frequency = "" WHERE frequency = "0y"';
            $DB->execute($sql);
        }
        upgrade_plugin_savepoint($result, 2013082103, 'elis', 'program');
    }

    if ($result && $oldversion < 2013082104) {
        require_once(dirname(__FILE__).'/../../../lib/lib.php');
        pm_set_config('notify_addedtowaitlist_user', 1);
        pm_set_config('notify_enroledfromwaitlist_user', 1);
        pm_set_config('notify_incompletecourse_user', 1);
        upgrade_plugin_savepoint($result, 2013082104, 'elis', 'program');
    }

    return $result;
}