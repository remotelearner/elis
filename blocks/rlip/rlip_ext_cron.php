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
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

// External RLIP 'cron' processing file
define('CLI_SCRIPT', 1);

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/blocks/rlip/lib.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_dataplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_importprovider_csv.class.php');

// TBD: adjust some php variables for the execution of this script
set_time_limit(0);
@ini_set('max_execution_time', '3000');
if (empty($CFG->extramemorylimit)) {
    raise_memory_limit('128M');
} else {
    raise_memory_limit($CFG->extramemorylimit);
}

mtrace('RLIP external cron start - Server Time: '.date('r', time())."\n\n");

$filename = basename($argv[0]);
// Get command-line args: <plugin> <userid> <targetstarttime> <lastruntime>
if ($argc <= 4) {
    $usage = get_string('ext_cron_usage', 'block_rlip', $argc - 1);
    mtrace("{$filename} {$usage}");
    exit;
}
$plugin          = $argv[1];
$userid          = $argv[2];
$targetstarttime = $argv[3];
$lastruntime     = $argv[4];

// validate plugin
$plugparts = explode('_', $plugin);
$rlip_plugins = get_plugin_list($plugparts[0]);
//print_object($rlip_plugins);
if (!array_key_exists($plugparts[1], $rlip_plugins)) {
    mtrace("{$filename}: RLIP plugin '{$plugin}' unknown!");
    exit;
}

mtrace("Processing external cron function for: {$plugin} ...",'');
$type = $plugparts[0];

switch ($type) {
    case 'rlipimport':
        $baseinstance = rlip_dataplugin_factory::factory($plugin);
        $entity_types = $baseinstance->get_import_entities();
        $files = array();
        $path = get_config($plugin, 'schedule_files_path');
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        foreach ($entity_types as $entity) {
            $files[$entity] = $path . get_config($plugin, $entity .'_schedule_file');
        }
        $importprovider = new rlip_importprovider_csv($entity_types, $files);
        $instance = rlip_dataplugin_factory::factory($plugin, $importprovider);
        break;

    case 'rlipexport':
        $user = get_complete_user_data('id', $userid);
        $export = rlip_get_export_filename($plugin,
                      empty($user) ? 99 : $user->timezone);
        $fileplugin = rlip_fileplugin_factory::factory($export, NULL, false);
        $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin);
        break;

    default:
        mtrace("{$filename}: RLIP plugin '{$plugin}' not supported!");
        exit;
}
$instance->run($targetstarttime);
mtrace('RLIP external cron end - Server Time: '.date('r', time())."\n\n");

// end of file
