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
    global $DB, $CFG;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012020900) {
        // Determine if the CM / PM tables had been previously set up in 1.9
        // Note that this is done in the block upgrade because it runs before the
        // "elis_program" install, while the rlip plugins run afterwards
        $cm_table = new xmldb_table('crlm_user');
        $cm_installed = $dbman->table_exists($cm_table);
        set_config('cm_upgraded_from_19', (int)$cm_installed, 'block_rlip');

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

        if ($dbman->table_exists('elis_scheduled_tasks')) {
            $DB->execute($sql);
        }

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

        if ($dbman->table_exists('elis_scheduled_tasks')) {
            $DB->execute($sql);
        }

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
        if ($dbman->table_exists('elis_scheduled_tasks')) {
            elis_tasks_update_definition('block_rlip');
        }

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
        //skip this work if we should be using ELIS IP
        $pm_installed = file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
        $ip_basic_enabled = !empty($CFG->block_rlip_overrideelisip);
        $init_moodle_plugins = !$pm_installed || $ip_basic_enabled;

	    $rlipexporttbl = new xmldb_table('block_rlip_export_fieldmap');

        /* One way to determine if this is an RLIP 1.9 upgrade is to check for tables that only
         * exist in that version. block_rlip_export_fieldmap is one such table that only exists
         * in RLIP 1.9
         */
        if ($init_moodle_plugins && $dbman->table_exists($rlipexporttbl)) {
            if (isset($CFG->block_rlip_creategroups)) {
                set_config('creategroupsandgroupings', $CFG->block_rlip_creategroups, 'rlipimport_version1');
                unset_config('block_rlip_creategroups');
            }

            if (isset($CFG->block_rlip_exportfiletimestamp)) {
                set_config('export_file_timestamp', $CFG->block_rlip_exportfiletimestamp, 'rlipexport_version1');
                unset_config('block_rlip_exportfiletimestamp');
            }

	        if (isset($CFG->block_rlip_exportallhistorical)) {
                set_config('export_file_timestamp', $CFG->block_rlip_exportallhistorical, 'rlipexport_version1');
                unset_config('block_rlip_exportallhistorical');
            }

            if (isset($CFG->block_rlip_impcourse_filename)) {
                set_config('course_schedule_file', $CFG->block_rlip_impcourse_filename, 'rlipimport_version1');
                unset_config('block_rlip_impcourse_filename');
            }

            if (isset($CFG->block_rlip_impuser_filename)) {
                set_config('user_schedule_file', $CFG->block_rlip_impuser_filename, 'rlipimport_version1');
                unset_config('block_rlip_impuser_filename');
            }

            if (isset($CFG->block_rlip_impenrolment_filename)) {
                set_config('enrolment_schedule_file', $CFG->block_rlip_impenrolment_filename, 'rlipimport_version1');
                unset_config('block_rlip_impenrolment_filename');
            }

            if (isset($CFG->block_rlip_nocron)) {
                set_config('disableincron', $CFG->block_rlip_nocron, 'block_rlip');
                unset_config('block_rlip_nocron');
            }

            if (isset($CFG->block_rlip_filelocation)) {
                if (($relativepath = rlip_data_root_path_translation($CFG->block_rlip_filelocation)) !== false) {
                    set_config('schedule_files_path', $relativepath, 'rlipimport_version1');
                }

                unset_config('block_rlip_filelocation');
            }

            if (isset($CFG->block_rlip_exportfilelocation)) {
                if ($relativepath = rlip_data_root_path_translation($CFG->block_rlip_exportfilelocation)) {
                    $path_parts = pathinfo($relativepath);
                    //just want an empty path if there is no parent folder
                    if ($path_parts['dirname'] == '.' || $path_parts['dirname'] == DIRECTORY_SEPARATOR) {
                        $path_parts['dirname'] = '';
                    }
                    set_config('export_path', $path_parts['dirname'], 'rlipexport_version1');
                    set_config('export_file', $path_parts['basename'], 'rlipexport_version1');
                } else {
                    //try to validate that the setting resembles a path
                    $separator_pos = strrpos($CFG->block_rlip_exportfilelocation, DIRECTORY_SEPARATOR);
                    if ($separator_pos !== false) {
                        //not using basename because it handles trailing slashes strangely
                        $export_filename = substr($CFG->block_rlip_exportfilelocation, $separator_pos + 1);
                        if ($export_filename !== '' && $export_filename !== false) {
                            //set just the filename and use the default path
                            set_config('export_file', $export_filename, 'rlipexport_version1');
                        }
                    }
                }

                unset_config('block_rlip_exportfilelocation');
            }

            if (isset($CFG->block_rlip_logfilelocation)) {
                if (($relativepath = rlip_data_root_path_translation($CFG->block_rlip_logfilelocation)) !== false) {
                    set_config('logfilelocation', $relativepath, 'rlipimport_version1');
                    set_config('logfilelocation', $relativepath, 'rlipexport_version1');
                }

                unset_config('block_rlip_logfilelocation');
            }

            /* RLIP 1.9 uses ID numbers for sending emails while RLIP 2 uses actual email addresses
             * ID numbers will be used to retrieve an corresponding email
             */
            if (isset($CFG->block_rlip_emailnotification)) {
                $emailids = explode(',', $CFG->block_rlip_emailnotification);
                $emails = array();

                foreach ($emailids as $id) {
                    if ($moodleuser = $DB->get_record('user', array('idnumber' => $id), 'id, email')) {
                        $emails[]   = $moodleuser->email;
                    }
                }

                $configemails = implode(',', $emails);
                /* Save the emails in both the import and export configuration
                 * RLIP 1.9 only has email notifications in its import configuration
                 */
                set_config('emailnotification', $configemails, 'rlipimport_version1');
                set_config('emailnotification', $configemails, 'rlipexport_version1');

                unset_config('block_rlip_emailnotification');
            }

            $admin = get_admin();

            // Handle import scheduling
            if (isset($CFG->block_rlip_importperiod)) {
                $value = rlip_sanitize_time_string($CFG->block_rlip_importperiod, '1d');
                /* RLIP 1.9 has no label for scheduling so the plugin name will be used instead
                 * More plugins other than version1 may need to be handled for updates in the future
                 */
                $data = array(
                    'plugin' => 'rlipimport_version1',
                    'period' => $value,
                    'userid' => $admin->id,
                    'label'  => 'rlipimport_version1',
                    'type'   => 'rlipimport'
                );

                if ($dbman->table_exists('elis_scheduled_tasks')) {
                    rlip_schedule_add_job($data);
                }
                unset_config('block_rlip_importperiod');
            }

            // Handle export scheduling
            if (isset($CFG->block_rlip_exportperiod)) {
                $value = rlip_sanitize_time_string($CFG->block_rlip_exportperiod, '1d');
                $data = array(
                    'plugin' => 'rlipexport_version1',
                    'period' => $value,
                    'userid' => $admin->id,
                    'label'  => 'rlipexport_version1',
                    'type'   => 'rlipexport'
                );

                if ($dbman->table_exists('elis_scheduled_tasks')) {
                    rlip_schedule_add_job($data);
                }
                unset_config('block_rlip_exportperiod');
            }

            // Remove any potential config values that may be set in the DB from RLIP 1.9 as well
            $fields = array(
                'block_rlip_dateformat',
                'block_rlip_impcourse_filetype',
                'block_rlip_impenrolment_filetype',
                'block_rlip_impuser_filetype',
                'block_rlip_last_export_cron',
                'block_rlip_last_import_cron'
            );

            foreach ($fields as $field) {
                if (isset($CFG->$field)) {
                    unset_config($field);
                }
            }
	    }

        upgrade_block_savepoint(true, 2012041700, 'rlip');
    }

    //rename block/rlip to block_rlip in elis_scheduled_tasks
    if ($result && $oldversion < 2012050200) {
        if ($dbman->table_exists('elis_scheduled_tasks')) {
            $tasks = $DB->get_recordset('elis_scheduled_tasks', array('plugin' => 'block/rlip'));
            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $task->plugin = 'block_rlip';
                    $DB->update_record('elis_scheduled_tasks', $task);
                }
            }
        }
        upgrade_block_savepoint(true, 2012050200, 'rlip');
    }

    if ($result && $oldversion < 2012072600) {
        //skip this work if we should be using Moodle-only IP
        $pm_installed = file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
        $ip_basic_enabled = !empty($CFG->block_rlip_overrideelisip);
        $init_pm_plugins = $pm_installed && !$ip_basic_enabled;

        if ($init_pm_plugins) {
            //import settings
            $mapping = array(
                'impuser_filename'      => 'user_schedule_file',
                'impcourse_filename'    => 'course_schedule_file',
                'impenrolment_filename' => 'enrolment_schedule_file'
            );

            foreach ($mapping as $old => $new) {
                $old_name = 'block_rlip_'.$old;
                if (isset($CFG->$old_name)) {
                    set_config($new, $CFG->$old_name, 'rlipimport_version1elis');
                    unset_config($old_name);
                }
            }

            if (isset($CFG->block_rlip_filelocation)) {
                if (($relativepath = rlip_data_root_path_translation($CFG->block_rlip_filelocation)) !== false) {
                    set_config('schedule_files_path', $relativepath, 'rlipimport_version1elis');
                }

                unset_config('block_rlip_filelocation');
            }

            //export settings
            $mapping = array(
                'exportfiletimestamp' => 'export_file_timestamp',
                'exportallhistorical' => 'nonincremental'
            );

            foreach ($mapping as $old => $new) {
                $old_name = 'block_rlip_'.$old;
                if (isset($CFG->$old_name)) {
                    set_config($new, $CFG->$old_name, 'rlipexport_version1elis');
                    unset_config($old_name);
                }
            }

            if (isset($CFG->block_rlip_exportfilelocation)) {
                if ($relativepath = rlip_data_root_path_translation($CFG->block_rlip_exportfilelocation)) {
                    $path_parts = pathinfo($relativepath);
                    //just want an empty path if there is no parent folder
                    if ($path_parts['dirname'] == '.' || $path_parts['dirname'] == DIRECTORY_SEPARATOR) {
                        $path_parts['dirname'] = '';
                    }
                    set_config('export_path', $path_parts['dirname'], 'rlipexport_version1elis');
                    set_config('export_file', $path_parts['basename'], 'rlipexport_version1elis');
                } else {
                    //try to validate that the setting resembles a path
                    $separator_pos = strrpos($CFG->block_rlip_exportfilelocation, DIRECTORY_SEPARATOR);
                    if ($separator_pos !== false) {
                        //not using basename because it handles trailing slashes strangely
                        $export_filename = substr($CFG->block_rlip_exportfilelocation, $separator_pos + 1);
                        if ($export_filename !== '' && $export_filename !== false) {
                            //set just the filename and use the default path
                            set_config('export_file', $export_filename, 'rlipexport_version1elis');
                        }
                    }
                }
            }

            //settings that apply to the import and the export
            if (isset($CFG->block_rlip_logfilelocation)) {
                if (($relativepath = rlip_data_root_path_translation($CFG->block_rlip_logfilelocation)) !== false) {
                    set_config('logfilelocation', $relativepath, 'rlipimport_version1elis');
                    set_config('logfilelocation', $relativepath, 'rlipexport_version1elis');
                }

                unset_config('block_rlip_logfilelocation');
            }

            /*
             * RLIP 1.9 uses ID numbers for sending emails while RLIP 2 uses actual email addresses
             * ID numbers will be used to retrieve an corresponding email
             */
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));

            if (isset($CFG->block_rlip_emailnotification)) {
                $emailids = explode(',', $CFG->block_rlip_emailnotification);
                $emails = array();

                foreach ($emailids as $id) {
                    //need to use Moodle user table because PM tables may not be set up yet
                    if ($moodleuser = $DB->get_record('user', array('idnumber' => $id), 'id, email')) {
                        $emails[]   = $moodleuser->email;
                    }
                }

                $configemails = implode(',', $emails);
                /*
                 * Save the emails in both the import and export configuration
                 * RLIP 1.9 only has email notifications in its import configuration
                 */
                set_config('emailnotification', $configemails, 'rlipimport_version1elis');
                set_config('emailnotification', $configemails, 'rlipexport_version1elis');

                unset_config('block_rlip_emailnotification');
            }

            //general settings
            if (isset($CFG->block_rlip_nocron)) {
                set_config('disableincron', $CFG->block_rlip_nocron, 'block_rlip');
                unset_config('block_rlip_nocron');
            }

            unset_config('block_rlip_exportfilelocation');

            $admin = get_admin();

            // Handle import scheduling
            if (isset($CFG->block_rlip_importperiod)) {
                $value = rlip_sanitize_time_string($CFG->block_rlip_importperiod, '1d');
                /* RLIP 1.9 has no label for scheduling so the plugin name will be used instead
                 * More plugins other than version1 may need to be handled for updates in the future
                 */
                $data = array(
                    'plugin' => 'rlipimport_version1elis',
                    'period' => $value,
                    'userid' => $admin->id,
                    'label'  => 'rlipimport_version1elis',
                    'type'   => 'rlipimport'
                );

                if ($dbman->table_exists('elis_scheduled_tasks')) {
                    rlip_schedule_add_job($data);
                }
                unset_config('block_rlip_importperiod');
            }

            // Handle export scheduling
            if (isset($CFG->block_rlip_exportperiod)) {
                $value = rlip_sanitize_time_string($CFG->block_rlip_exportperiod, '1d');
                $data = array(
                    'plugin' => 'rlipexport_version1elis',
                    'period' => $value,
                    'userid' => $admin->id,
                    'label'  => 'rlipexport_version1elis',
                    'type'   => 'rlipexport'
                );

                if ($dbman->table_exists('elis_scheduled_tasks')) {
                    rlip_schedule_add_job($data);
                }
                unset_config('block_rlip_exportperiod');
            }

            // Remove any potential config values that may be set in the DB from RLIP 1.9 as well
            $fields = array(
                'block_rlip_creategroups',
                'block_rlip_dateformat',
                'block_rlip_impcourse_filetype',
                'block_rlip_impenrolment_filetype',
                'block_rlip_impuser_filetype',
                'block_rlip_last_export_cron',
                'block_rlip_last_import_cron'
            );

            foreach ($fields as $field) {
                if (isset($CFG->$field)) {
                    unset_config($field);
                }
            }
        }

        upgrade_block_savepoint(true, 2012072600, 'rlip');
    }

    return $result;
}
