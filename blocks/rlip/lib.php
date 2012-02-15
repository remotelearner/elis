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

    //obtain the list of rlip export plugins
    if ($plugins = get_plugin_list('rlipexport')) {
        ksort($plugins);

        foreach ($plugins as $plugin => $path) {
            $plugsettings = $path.'/settings.php';

            if (file_exists($plugsettings)) {
                //the plugin has a settings file, so add it to the tree
                $name = 'rlipsettingrlipexport_'.$plugin;
                $displaystring = get_string('pluginname', "rlipexport_$plugin");
                $settings = new admin_settingpage($name, $displaystring);

                //add the actual settings to the list
                include($plugsettings);
                $adminroot->add('blocksettings', $settings);
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