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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/datahub/lib.php');

function xmldb_local_datahub_uninstall() {
    global $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // Delete any local_eliscore_sched_tasks for local plugin
    $DB->delete_records('local_eliscore_sched_tasks', array('plugin' => 'local_datahub'));

    // Delegate to sub-plugins
    $subplugintypes = array('dhimport', 'dhexport', 'dhfile');
    foreach ($subplugintypes as $subplugintype) {
        $subplugins = get_plugin_list($subplugintype);

        // Go through the subplugins for this type
        foreach ($subplugins as $subpluginname => $subpluginpath) {
            $uninstalllib = $subpluginpath.'/db/uninstall.php';

            if (file_exists($uninstalllib)) {
                // We have an unstall db file
                require_once($uninstalllib);
                $uninstallfunction = 'xmldb_'.$subplugintype.'_'.$subpluginname.'_uninstall';

                if (function_exists($uninstallfunction)) {
                    // We have an uninstall function, so run it
                    if (!$uninstallfunction()) {
                        echo $OUTPUT->notification('Encountered a problem running uninstall function for '.
                                $subplugintype.'_'.$subpluginname);
                    }
                }
            }
        }
    }
}
