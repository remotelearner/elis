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

/**
 * Base class for data (import and export) plugins
 *
 * Instances of this class should implement import and export logic
 * in a way that is file-format agnostic
 */
abstract class rlip_dataplugin {

    /**
     * Mainline for data processing
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     *                             false on error, i.e. time limit exceeded.
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     * @param int $maxruntime      The max time in seconds to complete export
     *                             default: 0 => unlimited
     * @param object $state        Previous ran state data to continue from
     *
     * @return object              Current state of RLIP processing
     *                             or null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    abstract function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null);

    /**
     * Specifies flag for indicating whether this plugin is actually available
     * on the current system, particularly for viewing in the UI and running
     * scheduled tasks
     */
    function is_available() {
        //by default, assume the plugin is available
        return true;
    }

    /**
     * Add custom entries to the Settings block tree menu
     *
     * @param object $adminroot The main admin tree root object
     * @param string $parentname The name of the parent node to add children to
     */
    function admintree_setup(&$adminroot, $parentname) {
        //by default, do nothing
    }

    /**
     * Obtain the file-system logger for this plugin
     *
     * @param object $fileplugin The file plugin used for IO in the logger
     * @param boolean $manual True on a manual run, false on a scheduled run
     * @return object The appropriate logging object
     */
    static function get_fs_logger($fileplugin, $manual) {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');

        return new rlip_fslogger_linebased($fileplugin, $manual);
    }
}

/**
 * Data plugin factory class that easily provides import and export plugin
 * instances
 */
class rlip_dataplugin_factory {

    /**
     * Factory method
     *
     * @param string $plugin The name of the plugin to create, either dhimport_* or dhexport_*
     * @param object $importprovider The import provider to use, if obtaining an import plugin
     * @param object $fileplugin The file plugin to use, if obtaining an export plugin
     * @param flag   $manual The type of import, true if done manually
     */
    static function factory($plugin, $importprovider = NULL, $fileplugin = NULL, $manual = false) {
        global $CFG;

        //split into plugin type and name
        list($plugintype, $pluginname) = explode('_', $plugin);

        if ($plugintype == 'dhimport') {
            //import plugin
            $path = "{$CFG->dirroot}/local/datahub/importplugins/";
            $classname = "rlip_importplugin_";
        } else {
            //export plugin
            $path = "{$CFG->dirroot}/local/datahub/exportplugins/";
            $classname = "rlip_exportplugin_";
        }

        //set up plugin path and class name
        $path .= "{$pluginname}/{$pluginname}.class.php";
        $classname .= $pluginname;

        //load class definition
        require_once($path);
        //obtain the plugin instance
        if ($plugintype == 'dhimport') {
            //import
            return new $classname($importprovider, $manual);
        } else {
            //export
            return new $classname($fileplugin, $manual);
        }

    }
}
