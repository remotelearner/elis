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

require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

function xmldb_block_rlip_upgrade($oldversion=0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012020900) {

        // Define table block_rlip_summary_log to be created
        $table = new xmldb_table('block_rlip_summary_log');

        // Adding fields to table block_rlip_summary_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('targetstarttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('filesuccesses', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('filefailures', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('storedsuccesses', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('storedfailures', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('statusmessage', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dbops', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1');
        $table->add_field('unmetdependency', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_rlip_summary_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_rlip_summary_log
        $table->add_index('plugin_ix', XMLDB_INDEX_UNIQUE, array('plugin'));
        $table->add_index('userid_ix', XMLDB_INDEX_UNIQUE, array('userid'));
        $table->add_index('targetstarttime_ix', XMLDB_INDEX_UNIQUE, array('targetstarttime'));
        $table->add_index('starttime_ix', XMLDB_INDEX_UNIQUE, array('starttime'));
        $table->add_index('endtime_ix', XMLDB_INDEX_UNIQUE, array('endtime'));

        // Launch create table for block_rlip_summary_log
        $dbman->create_table($table);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012020900, 'rlip');
    }

    if ($result && $oldversion < 2012022700) {
        // Get the summary log table, so we can make its indexes nonunique
        $table = new xmldb_table('block_rlip_summary_log');

        // Drop indexes and re-add them as not unique
        $index = new xmldb_index('plugin_ix', XMLDB_INDEX_UNIQUE, array('plugin'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('plugin_ix', XMLDB_INDEX_NOTUNIQUE, array('plugin'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('userid_ix', XMLDB_INDEX_UNIQUE, array('userid'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('targetstarttime_ix', XMLDB_INDEX_UNIQUE, array('targetstarttime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('targetstarttime_ix', XMLDB_INDEX_NOTUNIQUE, array('targetstarttime'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('starttime_ix', XMLDB_INDEX_UNIQUE, array('starttime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('starttime_ix', XMLDB_INDEX_NOTUNIQUE, array('starttime'));
        $dbman->add_index($table, $index);

        $index = new xmldb_index('endtime_ix', XMLDB_INDEX_UNIQUE, array('endtime'));
        $dbman->drop_index($table, $index);
        $index = new xmldb_index('endtime_ix', XMLDB_INDEX_NOTUNIQUE, array('endtime'));
        $dbman->add_index($table, $index);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012022700, 'rlip');
    }

    if ($result && $oldversion < 2012031300) {
        // Create new ip_schedule table
        $table = new xmldb_table('ip_schedule');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->add_field('config', XMLDB_TYPE_TEXT, 'medium', NULL, XMLDB_NOTNULL);

        // Adding keys to the scheduling table
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add indexes to the scheduling table
        $table->add_index('plugin_ix', XMLDB_INDEX_NOTUNIQUE, array('plugin'));
        $table->add_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch create table for schedule table
        $dbman->create_table($table);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031300, 'rlip');
    }

    if ($result && $oldversion < 2012031600) {
        // Get the summary log table, so we can add the "export" field
        $table = new xmldb_table('block_rlip_summary_log');

        // Add the "export" field
        $field = new xmldb_field('export', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'id');
        $dbman->add_field($table, $field);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031600, 'rlip');
    }

    if ($result && $oldversion < 2012031900) {
        // Get the ip schedule table, so we can add a "nextruntime" column
        $table = new xmldb_table('ip_schedule');

        // Add the "nextruntime" field
        $field = new xmldb_field('nextruntime', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'config');
        $dbman->add_field($table, $field);

        // Set up initial state
        $taskname =  $DB->sql_concat("'ipjob_'", 'ipjob.id');
        $sql = "UPDATE {ip_schedule} ipjob
                SET nextruntime = (
                  SELECT nextruntime
                  FROM {elis_scheduled_tasks} task
                  WHERE task.taskname = {$taskname}
                )";
        $DB->execute($sql);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012031900, 'rlip');
    }

    if ($result && $oldversion < 2012032300) {
        // Get the ip schedule table, so we can add a "lastruntime" column
        $table = new xmldb_table('ip_schedule');

        // Add the "lastruntime" field
        $field = new xmldb_field('lastruntime', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'config');
        $dbman->add_field($table, $field);

        // Set up initial state
        $taskname =  $DB->sql_concat("'ipjob_'", 'ipjob.id');
        $sql = "UPDATE {ip_schedule} ipjob
                SET lastruntime = (
                  SELECT lastruntime
                  FROM {elis_scheduled_tasks} task
                  WHERE task.taskname = {$taskname}
                )";
        $DB->execute($sql);

        // block rlip savepoint reached
        upgrade_block_savepoint(true, 2012032300, 'rlip');
    }

    if ($result && $oldversion < 2012032800) {
        // Make block naming more consistent
        // Will throw an exception if problem is encountered
        $dbman->rename_table(new xmldb_table('block_rlip_summary_log'), 'block_rlip_summary_logs');
        $dbman->rename_table(new xmldb_table('ip_schedule'), 'block_rlip_schedule');
    }

    if ($result && $oldversion < 2012040500) {
        $table = new xmldb_table('block_rlip_summary_logs');

        $field = new xmldb_field('logpath', XMLDB_TYPE_TEXT, 'medium', null, null);
        $dbman->add_field($table, $field);

        upgrade_block_savepoint(true, 2012040500, 'rlip');
    }

    if ($result && $oldversion < 2012040900) {
        // Add a cron task for log rollover
        elis_tasks_update_definition('block_rlip');

        upgrade_block_savepoint(true, 2012040900, 'rlip');
    }

    if ($result && $oldversion < 2012041200) {
        // ELIS-5125: Convert old 'rlip' config setting(s) to 'block_rlip'
        $oldconfig = get_config('rlip');
        foreach ($oldconfig as $key => $val) {
            set_config($key, $val, 'block_rlip');
            unset_config($key, 'rlip');
        }

        upgrade_block_savepoint(true, 2012041200, 'rlip');
    }

    if ($result && $oldversion < 2012041201) {
        $table = new xmldb_table('block_rlip_summary_logs');

        $field = new xmldb_field('entitytype', XMLDB_TYPE_TEXT, 'small', null, null);
        $dbman->add_field($table, $field);

        upgrade_block_savepoint(true, 2012041201, 'rlip');
    }

    // This performs the RLIP 1.9 upgrade to RLIP 2
    if ($result && $oldversion < 2012041700) {

	    $rlipexporttbl = new xmldb_table('block_rlip_export_fieldmap');

        /* One way to determine if this is an RLIP 1.9 upgrade is to check for tables that only
         * exist in that version. block_rlip_export_fieldmap is one such table that only exists
         * in RLIP 1.9
         */
	    if ($dbman->table_exists($rlipexporttbl)) {

            // Using get_config will not work
            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_creategroups'));
            set_config('creategroupsandgroupings', $creategroups->value, 'rlipimport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_exportfiletimestamp'));
            set_config('export_file_timestamp', $creategroups->value, 'rlipexport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_exportallhistorical'));
            set_config('nonincremental', $creategroups->value, 'rlipexport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_impcourse_filename'));
            set_config('course_schedule_file', $creategroups->value, 'rlipimport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_impuser_filename'));
            set_config('user_schedule_file', $creategroups->value, 'rlipimport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_impenrolment_filename'));
            set_config('enrolment_schedule_file', $creategroups->value, 'rlipimport_version1');

            $creategroups = $DB->get_record('config', array('name' => 'block_rlip_nocron'));
            set_config('disableincron', $creategroups->value, 'block_rlip');

            /* RLIP 1.9 uses ID numbers for sending emails while RLIP 2 uses actual email addresses
             * ID numbers will be used to retrieve an corresponding email
             */
            $config = $DB->get_record('config', array('name' => 'block_rlip_emailnotification'));
            $emailids = explode(',', $config->value);
            $emails = array();

            foreach ($emailids as $id) {
                if ($moodleuser = $DB->get_record('user', array('idnumber' => $id))) {
                    $emails[] = $moodleuser->email;
                }
            }

            $configemails = implode(',', $emails);
            /* Save the emails in both the import and export configuration
             * RLIP 1.9 only has email notifications in its import configuration
             */
            set_config('emailnotification', $configemails, 'rlipimport_version1');
            set_config('emailnotification', $configemails, 'rlipexport_version1');

            $admin = get_admin();

            // Handle import scheduling
            $config = $DB->get_record('config', array('name' => 'block_rlip_importperiod'));
            $value = rlip_sanitize_time_string($config->value, '1d');
            /* RLIP 1.9 has no label for scheduling so the plugin name will be used instead
             * More plugins other than version1 may need to be handled for updates in the future
             */
            $data = array('plugin' => 'rlipimport_version1',
                          'period' => $value,
                          'userid' => $admin->id,
                          'label'  => 'rlipimport_version1',
                          'type'   => 'rlipimport');
            rlip_schedule_add_job($data);

            // Handle export scheduling
            $config = $DB->get_record('config', array('name' => 'block_rlip_exportperiod'));
            $value = rlip_sanitize_time_string($config->value, '1d');
            $data = array('plugin' => 'rlipexport_version1',
                          'period' => $value,
                          'userid' => $admin->id,
                          'label'  => 'rlipexport_version1',
                          'type'   => 'rlipexport');
            rlip_schedule_add_job($data);

	    }

        upgrade_block_savepoint(true, 2012041700, 'rlip');
    }

    return $result;
}
