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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once (dirname(__FILE__) . '/../../../config.php');

/**
 * Global ELIS management object.
 */
class elis {
    /**
     * The ELIS DB version
     */
    static $version;

    /**
     * The ELIS human-readable release
     */
    static $release;

    /**
     * The base directory for the ELIS code.
     */
    static $basedir;

    /**
     * Return the full path name for a ELIS file.
     */
    static function file($file) {
        return elis::$basedir . '/' . $file;
    }

    /**
     * Return the full path name for a file in a component.
     */
    static function component_file($component, $file) {
        return elis::file("{$component}/{$file}");
    }

    /**
     * Return the full path name for a file in a plugin.
     */
    static function plugin_file($plugin, $file) {
        list($type,$name) = normalize_component($plugin);
        return get_plugin_directory($plugintype, $name)."/{$file}";
    }

    /**
     * The base directory for the ELIS libraries.
     */
    static $libdir;

    /**
     * Return the full path name for a ELIS library file.
     */
    static function lib($file) {
        return elis::file("core/lib/{$file}");
    }
}

global $CFG;
elis::$basedir = "{$CFG->dirroot}/elis";
elis::$libdir = elis::file('core/lib');

{
    $plugin = new stdClass;
    include elis::file('core/version.php');
    elis::$version = $plugin->version;
    elis::$release = $plugin->release;
}
