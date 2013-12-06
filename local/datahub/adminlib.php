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
 * @package    block_rlip
 * @subpackage rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * custom class that handles the versioning structure for RLIP file plugins
 */
class plugininfo_rlipfile extends plugininfo_base {

    /**
     * Gathers and returns the information about all plugins of the given type
     *
     * @param string $type the name of the plugintype, eg. mod, auth or workshopform
     * @param string $typerootdir full path to the location of the plugin dir
     * @param string $typeclass the name of the actually called class
     * @return array of plugintype classes, indexed by the plugin name
     */
    static function get_plugins($plugintype, $plugintyperootdir, $plugintypeclass) {
        global $CFG, $DB;

        // track our method result
        $result = array();
        if (!$DB->get_manager()->table_exists('config_plugins')) {
            return $result;
        }

        // obtain the list of all file plugins
        $fileplugins = get_plugin_list('rlipfile');

        foreach ($fileplugins as $pluginname => $pluginpath) {
            if ($pluginname == 'phpunit') {
                // phpunit directory is a false-positive
                continue;
            }

            // set up the main plugin information
            $instance = new $plugintypeclass();
            $instance->type = $plugintype;
            $instance->typerootdir = $plugintyperootdir;
            $instance->name = 'rlipfile_'.$pluginname;
            $instance->rootdir = $pluginpath;
            $instance->displayname = get_string('pluginname', $instance->name);

            // track the current database version
            $versiondb = get_config($instance->name, 'version');
            $instance->versiondb = ($versiondb !== false) ? $versiondb : NULL;

            // track the proposed new version
            $plugin = new stdClass;
            include("{$instance->rootdir}/version.php");
            $instance->versiondisk = $plugin->version;
            $instance->init_is_standard(); //is this really needed?

            // append to results
            $result[$instance->name] = $instance;
        }

        return $result;
    }
}

/**
 * custom class that handles the versioning structure for RLIP import plugins
 */
class plugininfo_rlipimport extends plugininfo_base {

    /**
     * Gathers and returns the information about all plugins of the given type
     *
     * @param string $type the name of the plugintype, eg. mod, auth or workshopform
     * @param string $typerootdir full path to the location of the plugin dir
     * @param string $typeclass the name of the actually called class
     * @return array of plugintype classes, indexed by the plugin name
     */
    static function get_plugins($plugintype, $plugintyperootdir, $plugintypeclass) {
        global $CFG, $DB;

        // track our method result
        $result = array();
        if (!$DB->get_manager()->table_exists('config_plugins')) {
            return $result;
        }

        // obtain the list of all file plugins
        $fileplugins = get_plugin_list('rlipimport');

        foreach ($fileplugins as $pluginname => $pluginpath) {
            // error_log("rlipimport::subplugin: {$pluginname}");
            if (!file_exists("{$pluginpath}/version.php")) {
                // test/sample directories false-positive
                continue;
            }

            // set up the main plugin information
            $instance = new $plugintypeclass();
            $instance->type = $plugintype;
            $instance->typerootdir = $plugintyperootdir;
            $instance->name = 'rlipimport_'.$pluginname;
            $instance->rootdir = $pluginpath;
            $instance->displayname = get_string('pluginname', $instance->name);

            // track the current database version
            $versiondb = get_config($instance->name, 'version');
            $instance->versiondb = ($versiondb !== false) ? $versiondb : NULL;

            // track the proposed new version
            $plugin = new stdClass;
            include("{$instance->rootdir}/version.php");
            $instance->versiondisk = $plugin->version;
            $instance->init_is_standard(); //is this really needed?

            // append to results
            $result[$instance->name] = $instance;
        }

        return $result;
    }
}

