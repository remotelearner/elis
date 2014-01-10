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

require_once('../../../config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/form/rlip_manualimport_form.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importprovider_moodlefile.class.php');

//permissions checking
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

//determine which plugin we're using
$plugin = required_param('plugin', PARAM_CLEAN);

// Need base URL for form and Moodle block management
$baseurl = $CFG->wwwroot.'/local/datahub/importplugins/manualrun.php?plugin='.$plugin;

//header
$plugin_display = get_string('pluginname', $plugin);
rlip_manualrun_page_setup($baseurl, $plugin_display);
echo $OUTPUT->header();

//add a warning message for all imports
$import_warning = get_string('importwarning','local_datahub');
echo $OUTPUT->box($import_warning);

//need to get number of different files
$instance = rlip_dataplugin_factory::factory($plugin);
$labels = $instance->get_file_labels();
$entity_types = $instance->get_import_entities();

//create our basic form
$form = new rlip_manualimport_form(null, $labels);
$form->set_data(array('plugin' => $plugin));

//need to collect the ids of the important files
$fileids = array();

if ($data = $form->get_data()) {
    //process each uploaded file, moving it out of "draft" space
    for ($i = 0; $i < count($entity_types); $i++) {
        $key = "file{$i}";
        $fileids[] = rlip_handle_file_upload($data, $key);
    }

    //run the entire import once
    $importprovider = new rlip_importprovider_moodlefile($entity_types, $fileids);
    //indicate to the factory class that this is a manual run
    $manual = true;
    $instance = rlip_dataplugin_factory::factory($plugin, $importprovider, NULL, $manual);
    $instance->run(0, 0, rlip_get_maxruntime());
}

//display the form
$form->display();

//footer
echo $OUTPUT->footer();
