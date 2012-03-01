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
 * Base class for data (import and export) plugins
 *
 * Instances of this class should implement import and export logic
 * in a way that is file-format agnostic
 */
abstract class rlip_dataplugin {

    /**
     * Mainline for data processing
     */
    abstract function run();
}

/**
 * Data plugin factory class that easily provides import and export plugin
 * instances
 */
class rlip_dataplugin_factory {

    /**
     * Factory method
     *
     * @param string $plugin The name of the plugin to create, either rlipimport_*
     *                       or rlipexport_*
     * @param object $importprovider The import provider to use, if obtaining
     *                               an import plugin
     * @param object $fileplugin The file plugin to use, if obtaining an export
     *                           plugin
     */
    static function factory($plugin, $importprovider = NULL, $fileplugin = NULL) {
        global $CFG;

        //split into plugin type and name
        list($plugintype, $pluginname) = explode('_', $plugin);

        if ($plugintype == 'rlipimport') {
            //import plugin
            $path = "{$CFG->dirroot}/blocks/rlip/importplugins/";
            $classname = "rlip_importplugin_";
        } else {
            //export plugin
            $path = "{$CFG->dirroot}/blocks/rlip/exportplugins/";
            $classname = "rlip_exportplugin_";
        }

        //set up plugin path and class name
        $path .= "{$pluginname}/{$pluginname}.class.php";
        $classname .= $pluginname;

        //load class definition
        require_once($path);

        //obtain the plugin instance
        if ($plugintype == 'rlipimport') {
            //import
            return new $classname($importprovider);
        } else {
            //export
            return new $classname($fileplugin);
        }
    }
}