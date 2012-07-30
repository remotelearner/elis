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

$subplugins = array('rlipimport' => 'blocks/rlip/importplugins',
                    'rlipexport' => 'blocks/rlip/exportplugins',
                    'rlipfile' => 'blocks/rlip/fileplugins');

if (!class_exists('plugininfo_rlipfile')) {
    require_once(dirname(__FILE__) .'/../../../config.php');
    global $CFG;
    require_once("{$CFG->dirroot}/lib/pluginlib.php");
    if (class_exists('pluginfo_base')) { // Moodle 2.3 only!
        class plugininfo_rlipfile extends plugininfo_base {
            static function get_plugins($plugintype, $plugintyperootdir, $plugintypeclass) {
                global $CFG, $DB;
                $subplugins = parent::get_plugins($plugintype, $plugintyperootdir, $plugintypeclass);
                $csvclass = new $plugintypeclass();
                $csvclass->type = $plugintype;
                $csvclass->typerootdir = $plugintyperootdir;
                $csvclass->name = 'rlipfile_csv';
                $csvclass->rootdir = "{$CFG->dirroot}/blocks/rlip/fileplugins/csv";
                $csvclass->displayname = get_string('pluginname', 'rlipfile_csv');
                $csvversiondb = $DB->get_field('config_plugins', 'value',
                                               array('plugin' => $csvclass->name,
                                                     'name'   => 'version'));
                $csvclass->versiondb = ($csvversiondb !== false) ? $csvversiondb : null;
                $plugin = new stdClass;
                include("{$csvclass->rootdir}/version.php");
                $csvclass->versiondisk = $plugin->version;
                $csvclass->init_is_standard(); // TBD

                $logclass = new $plugintypeclass();
                $logclass->type = $plugintype;
                $logclass->typerootdir = $plugintyperootdir;
                $logclass->name = 'rlipfile_log';
                $logclass->rootdir = "{$CFG->dirroot}/blocks/rlip/fileplugins/log";
                $logclass->displayname = get_string('pluginname', 'rlipfile_log');
                $logversiondb = $DB->get_field('config_plugins', 'value',
                                               array('plugin' => $logclass->name,
                                                     'name'   => 'version'));
                $logclass->versiondb = ($logversiondb !== false) ? $logversiondb : null;
                $plugin = new stdClass;
                include("{$logclass->rootdir}/version.php");
                $logclass->versiondisk = $plugin->version;
                $logclass->init_is_standard(); // TBD

                //mtrace("plugininfo_rlipfile::get_plugins({$plugintype}, {$plugintyperootdir}, {$plugintypeclass})");
                return array($csvclass->name => $csvclass,
                             $logclass->name => $logclass
                       );
            }
        }
    }
}

