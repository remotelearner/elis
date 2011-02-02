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

defined('MOODLE_INTERNAL') || die();

/**
 * Global ELIS management object.
 */
class elis {
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
    static function plugin_file($component, $plugin, $file) {
        return elis::file("{$component}/plugins/{$plugin}/{$file}");
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

    /**
     * The default database object.
     */
    static $db;
}

global $CFG;
elis::$basedir = "{$CFG->dirroot}/elis";
elis::$libdir = elis::file('core/lib');

global $DB;
elis::$db = $DB;
