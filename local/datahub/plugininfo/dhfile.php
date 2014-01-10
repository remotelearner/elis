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

/**
 * Custom class that handles the versioning structure for Datahub file plugins.
 */
class plugininfo_dhfile extends \core\plugininfo\base {

    /**
     * Gathers and returns the information about all plugins of the given type
     *
     * @param string $type the name of the plugintype, eg. mod, auth or workshopform
     * @param string $typerootdir full path to the location of the plugin dir
     * @param string $typeclass the name of the actually called class
     * @return array of plugintype classes, indexed by the plugin name
     */
    public static function get_plugins($plugintype, $plugintyperootdir, $plugintypeclass) {
        global $CFG, $DB;

        // Track our method result.
        $result = array();
        if (!$DB->get_manager()->table_exists('config_plugins')) {
            return $result;
        }

        // Obtain the list of all file plugins.
        $fileplugins = get_plugin_list('dhfile');

        foreach ($fileplugins as $pluginname => $pluginpath) {
            if ($pluginname == 'phpunit') {
                // Phpunit directory is a false-positive.
                continue;
            }

            // Set up the main plugin information.
            $instance = new $plugintypeclass();
            $instance->type = $plugintype;
            $instance->typerootdir = $plugintyperootdir;
            $instance->name = 'dhfile_'.$pluginname;
            $instance->rootdir = $pluginpath;
            $instance->displayname = get_string('pluginname', $instance->name);

            // Track the current database version.
            $versiondb = get_config($instance->name, 'version');
            $instance->versiondb = ($versiondb !== false) ? $versiondb : null;

            // Track the proposed new version.
            $plugin = new stdClass;
            include("{$instance->rootdir}/version.php");
            $instance->versiondisk = $plugin->version;
            $instance->init_is_standard(); // Is this really needed?

            // Append to results.
            $result[$instance->name] = $instance;
        }

        return $result;
    }
}
