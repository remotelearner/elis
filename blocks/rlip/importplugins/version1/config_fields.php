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

require_once('../../../../config.php');

//plugin directory
$plugindir = get_plugin_directory('rlipimport', 'version1');

require_once($plugindir.'/version1.class.php');
require_once($plugindir.'/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/form/rlip_importfield_form.class.php');

//permissions checking
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context);

$pluginwwwroot = str_replace($CFG->dirroot, $CFG->wwwroot, $plugindir);
$baseurl = $pluginwwwroot.'/config_fields.php';
$tab = optional_param('tab', 'user', PARAM_CLEAN);

//page header
rlipimport_version1_page_setup($baseurl);
echo $OUTPUT->header();

//tabs
$tabs = rlipimport_version1_get_tabs($baseurl);
print_tabs(array($tabs), $tab);

//data from db
$mappingdata = rlipimport_version1_get_mapping($tab);

//options
$plugin = new rlip_importplugin_version1(NULL, false);
$options = $plugin->get_available_fields($tab);

//body
$form = new rlip_importfield_form(null, $options);

//handle data submission
if ($data = $form->get_data()) {
    if (isset($data->submitbutton)) {
        rlipimport_version1_save_mapping($tab, $options, $data);

        //notify to the user that settings were saved
        echo $OUTPUT->heading(get_string('mappingssaved', 'rlipimport_version1'));
    } else if (isset($data->reset)) {
        rlipimport_version1_reset_mappings($tab);
        redirect($baseurl."?tab={$tab}&resetmessage=1", '', 0);
    }
} else if ($form->is_cancelled()) {
    redirect($baseurl."?tab={$tab}&cancelmessage=1", '', 0);
}

//message handling
$resetmessage = optional_param('resetmessage', 0, PARAM_INT);
if ($resetmessage) {
    //notify the user that the settings were reset to defaults
    echo $OUTPUT->heading(get_string('mappingsreset', 'rlipimport_version1'));
}

$cancelmessage = optional_param('cancelmessage', 0, PARAM_INT);
if ($cancelmessage) {
    //notify the user that the settings were reset to DB values
    echo $OUTPUT->heading(get_string('mappingscancelled', 'rlipimport_version1'));
}

$mappingdata['tab'] = $tab;
$form->set_data($mappingdata);
$form->display();

//page footer
echo $OUTPUT->footer();