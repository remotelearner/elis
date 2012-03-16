<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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

/**
 * Add extra admintree configuration structure to the main administration menu tree.
 *
 * @uses $CFG
 * @param object &$adminroot Reference to an admin tree object as generated via admin_get_root().
 * @return none
 */
function rlip_admintree_setup(&$adminroot) {
    global $CFG;

    $plugintypes = array('rlipimport', 'rlipexport');
    foreach ($plugintypes as $plugintype) {
        //obtain the list of plugins of the current type
        if ($plugins = get_plugin_list($plugintype)) {
            ksort($plugins);
    
            foreach ($plugins as $plugin => $path) {
                $plugsettings = $path.'/settings.php';
    
                if (file_exists($plugsettings)) {
                    //the plugin has a settings file, so add it to the tree
                    $name = "rlipsetting{$plugintype}_{$plugin}";
                    $displaystring = get_string('pluginname', "{$plugintype}_$plugin");
                    $settings = new admin_settingpage($name, $displaystring);
    
                    //add the actual settings to the list
                    include($plugsettings);
                    $adminroot->add('blocksettings', $settings);
                }
            }
        }
    }

    //add a link for viewing all plugins
    $displaystring = get_string('plugins', 'block_rlip');
    $url = $CFG->wwwroot.'/blocks/rlip/plugins.php';
    $page = new admin_externalpage('rlipsettingplugins', $displaystring, $url,
                                   'moodle/site:config');
    $adminroot->add('blocksettings', $page);
}

/**
 * Perform page setup for the page that allows you to run tasks manually
 *
 * @param string $baseurl The base page url
 * @param string $plugin_display The display name of the plugin
 */
function rlip_manualrun_page_setup($baseurl, $plugin_display) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $displaystring = get_string('configuretitle', 'rlipexport_version1');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');

    //add navigation items
    $PAGE->navbar->add(get_string('administrationsite'));
    $PAGE->navbar->add(get_string('plugins', 'admin'));
    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('plugins', 'block_rlip'));
    $PAGE->navbar->add($plugin_display);
    $PAGE->navbar->add(get_string('runmanually', 'block_rlip'));

    //block css file
    $PAGE->requires->css('/blocks/rlip/styles.css');
}

/**
 * Perform the handling of an uploaded file, including moving it to a non-draft
 * area
 *
 * @param object $data The data submitted by the file upload form
 * @param string $key The key that represents the field containing the file
 *                    "itemid"
 * @return mixed The file record id on success, or false if not selected
 */
function rlip_handle_file_upload($data, $key) {
    global $USER, $DB;

    $result = false;

    //get general file storage object
    $fs = get_file_storage();

    //obtain the listing of files just uploaded
    $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->$key);

    if ($instanceid = $DB->get_field('block_instances', 'id', array('blockname' => 'rlip'))) {
        //try to use the block context
        $context = get_context_instance(CONTEXT_BLOCK, $instanceid);
    } else {
        //fall back to site context
        $context = get_context_instance(CONTEXT_SYSTEM);
    }

    //set up file parameters
    $file_record = array('contextid' => $context->id,
                         'component' => 'block_rlip',
                         'filearea' => 'files',
                         'filepath' => '/manualupload/');

    //transfer files to a specific area
    foreach ($files as $draftfile) {

        //file API seems to always upload a directory record, so ignore that 
        if (!$draftfile->is_directory()) {
            $exists = false;

            //maintain the same filename
            $draft_filename = $draftfile->get_filename();
            $file = $fs->get_file($context->id, 'block_rlip', 'files',
                                  $data->$key, '/manualupload/', $draft_filename);

            if ($file) {
                //file exists
                $exists = true;
                $samesize = ($file->get_filesize() == $draftfile->get_filesize());
                $sametime = ($file->get_timemodified() == $draftfile->get_timemodified());

                //if not the same file, delete it
                if ((!$samesize || !$sametime) && $file->delete()) {
                    $exists = false;
                }
            }

            if (!$exists) {
                //create as new file
                $file = $fs->create_file_from_storedfile($file_record, $draftfile);
            }

            //delete the draft file
            $draftfile->delete();

            //obtain the file record id
            $result = $file->get_id();
        }
    }

    return $result;
}

/**
 * Displays the status of processing as represented by the supplied log ids
 *
 * @param array $logids The ids of log records to display 
 */
function rlip_print_manual_status($logids) {
    global $DB, $OUTPUT;

    if (!empty($logids)) {
        //get IN / = clause for specific ids
        list($select, $params) = $DB->get_in_or_equal($logids);
        $select = "id {$select}";

        //only need a couple of fields
        $fields = 'filesuccesses, filefailures, statusmessage';
        if ($recordset = $DB->get_recordset_select('block_rlip_summary_log', $select, $params, 'id', $fields)) {
            foreach ($recordset as $record) {
                //total rows = successes + failures
                $record->total = $record->filesuccesses + $record->filefailures;

                //display status message with successes and total records
                $displaystring = get_string('manualstatus', 'block_rlip', $record);
                echo $OUTPUT->box($displaystring, 'generalbox manualstatusbox');
            }
        }
    }
}

/**
 * Sanitizes time strings and applies a default value if necessary
 *
 * @param string $time_string A user-entered time string
 * @param string $default The field default
 * @return string The time string with proper formatting and invalid data
 *                removed
 */
function rlip_sanitize_time_string($time_string, $default = '') {
    //valid time units - hours, minutes, seconds
    $valid_units = array('d', 'h', 'm');

    $result = '';
    //track the current "group", e.g. 2d
    $current_group = '';

    //iterate through characters
    for ($i = 0; $i < strlen($time_string); $i++) {
        //retrieve current character
        $character = strtolower(substr($time_string, $i, 1));

        if ($character >= '0' && $character <= '9') {
            //append digit
            $current_group .= $character;
        } else {
            if (in_array($character, $valid_units)) {
                //time unit is valid
                if ($current_group != '') {
                    //a number was specified, so append the "group" to the
                    //result
                    $current_group .= $character;
                    $result .= $current_group;
                }
            }

            //looking for new entry
            $current_group = '';
        }
    }

    if ($result == '') {
        //no valid data, so use the default
        return $default;
    }

    return $result;
}

/**
 * Converts a sanitized time string to a numerical offset
 *
 * @param string $time_string A properly formatted time string
 * @return int The equivalent offset, in seconds
 */
function rlip_time_string_to_offset($time_string) {
    //valid time units - hours, minutes, seconds - plus time values
    $valid_units = array('d' => DAYSECS,
                         'h' => HOURSECS,
                         'm' => MINSECS);

    $result = 0;
    //track the current "group", e.g. 2d
    $current_group = '';

    //iterate through characters
    for ($i = 0; $i < strlen($time_string); $i++) {
        //retrieve current character
        $character = substr($time_string, $i, 1);

        if ($character >= '0' && $character <= '9') {
            //append digit
            $current_group .= $character;
        } else {
            //look up the value of the time unit
            $multiplier = $valid_units[$character];
            //value based on numeric string
            $value = (int)$current_group;
            //add to result
            $result += $multiplier * $value;

            $current_group = '';
        }
    }

    return $result;
}

/**
 * Get scheduled IP jobs
 *
 * @param  string $type  The IP plugin type: 'rlipimport', 'rlipexport'
 * @param  string $name  The IP plugin name: 'version1'
 * @uses   $CFG
 * @uses   $DB
 * @return mixed         Either list of scheduled jobs for IP plugin
 *                       or false if none.
 */
function rlip_get_scheduled_jobs($type, $name) {
    global $CFG, $DB;
    $taskname =  $DB->sql_concat("'ipjob_'", 'ipjob.id');
    $params = array('plugin' => "{$type}_{$name}");
    $sql = "SELECT ipjob.*, usr.username, usr.firstname, usr.lastname,
                   usr.timezone, task.lastruntime, task.nextruntime
              FROM {$CFG->prefix}elis_scheduled_tasks task
              JOIN {$CFG->prefix}ip_schedule ipjob
                ON task.taskname = {$taskname}
              JOIN {$CFG->prefix}user usr
                ON ipjob.userid = usr.id
             WHERE ipjob.plugin = :plugin ";
    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Get scheduled period in minutes
 *
 * @param  string $period  The schedule period in: *d*h*m format
 * @return int             The schedule period in minutes, -1 on error
 */
function rlip_schedule_period_minutes($period) {
    $period_elems = array('d' => DAYSECS/60,
                          'h' => HOURSECS/60,
                          'm' => 1);
    $parray = str_split($period);
    $num = '';
    $min = 0;
    foreach ($parray as $char) {
        if (ctype_space($char)) {
            continue;
        } else if (ctype_digit($char)) {
            $num .= $char;
        } else {
            if (!array_key_exists($char, $period_elems)) {
                return -1; // error
            }
            $multiplier = $period_elems[$char];
            $min += intval($num) * $multiplier;
            $num = '';
        }
    }
    return $min;
}

/**
 * Add schedule job for IP
 *
 * @param  mixed  $data   The scheduled jobs form parameters.
 * @uses   $DB
 * @uses   $USER
 * @return bool           true on success, false on error.
 */
function rlip_schedule_add_job($data) {
    global $DB, $USER;

    $userid = isset($data['userid']) ? $data['userid'] : $USER->id;
    $data['timemodified'] = time();
    $ipjob  = new stdClass;
    $ipjob->userid = $userid;
    $ipjob->plugin = $data['plugin'];
    $ipjob->config = serialize($data);
    if (!empty($data['id'])) {
        $ipjob->id = $data['id'];
        $DB->update_record('ip_schedule', $ipjob);
        // Must delete any existing task records for the old schedule
        $taskname = 'ipjob_'. $ipjob->id;
        $DB->delete_records('elis_scheduled_tasks', array('taskname' => $taskname));
    } else {
        $ipjob->id = $DB->insert_record('ip_schedule', $ipjob);
    }

    $task = new stdClass;
    $task->plugin        = 'block/rlip';
    $task->taskname      = 'ipjob_'. $ipjob->id;
    $task->callfile      = '/blocks/rlip/lib.php';
    $task->callfunction  = serialize('run_ipjob'); // TBD
    $task->lastruntime   = 0;
    $task->blocking      = 0;
    $task->minute        = 0;
    $task->hour          = 0;
    $task->day           = '*';
    $task->month         = '*';
    $task->dayofweek     = '*';
    $task->timezone      = 0;
    $task->enddate       = null;
    $task->runsremaining = null;
    $task->nextruntime   = (int)(time() + rlip_schedule_period_minutes($data['period']) * 60);
    return $DB->insert_record('elis_scheduled_tasks', $task);
}

/**
 * Delete schedule job for IP
 *
 * @param  int $id  The ID of the scheduled job to delete.
 * @uses   $DB
 * @return bool           true on success, false on error.
 */
function rlip_schedule_delete_job($id) {
    global $DB;
    $DB->delete_records('ip_schedule', array('id' => $id));
    $taskname = 'ipjob_'. $id;
    $DB->delete_records('elis_scheduled_tasks', array('taskname' => $taskname));
    return true;
}

/**
 *  Callback function for elis_scheduled_tasks IP jobs
 *
 * @param  string  $taskname  The task name, in the form ipjob_{id}, where id
 *                            is the IP job's schedule id
 * @return boolean            true on success, otherwise false
 */
function run_ipjob($taskname) {
    global $DB;

    // Get the schedule record
    list($prefix, $id) = explode('_', $taskname);
    $ipjob = $DB->get_record('ip_schedule', array('id' => $id));
    if (empty($ipjob)) {
        return false;
    }

    $plugin = $ipjob->plugin;
    $data = unserialize($ipjob->config);

    // Set the next run time
    if ($task = $DB->get_record('elis_scheduled_tasks',
                                array('taskname' => $taskname))) {
        $task->nextruntime = (int)(time() + rlip_schedule_period_minutes($data['period']) * 60);
        $DB->update_record('elis_scheduled_tasks', $task);
    } else {
        mtrace("run_ipjob({$taskname}): DB Error retrieving task record!");
    }

    // Perform the IP scheduled action
    switch ($data['type']) { // TBD
        case 'rlipimport':
            $baseinstance = rlip_dataplugin_factory::factory($plugin);
            $entity_types = $baseinstance->get_import_entities();
            $files = array();
            $path = get_config($plugin, 'schedule_files_path');
            foreach ($entity_types as $entity) {
                $files[$entity] = $path . get_config($plugin, $entity .'_schedule_file');
            }
            $importprovider = new rlip_importprovider_csv($entity_types, $files);
            $instance = rlip_dataplugin_factory::factory($plugin, $importprovider);
            break;

        case 'rlipexport':
            $fileplugin = rlip_fileplugin_factory::factory('', NULL, false, true);
            $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin);
            break;

        default:
            mtrace("run_ipjob({$taskname}): IP plugin '{$plugin}' not supported!");
            return false;
    }

    $instance->run();
    return true;
}

