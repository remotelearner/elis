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

require_once('../../../config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/form/rlip_manualimport_form.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');

//permissions checking
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context);

//need base URL for form
$baseurl = $CFG->wwwroot.'/blocks/rlip/importplugins/manualrun.php';

//determine which plugin we're using
$plugin = required_param('plugin', PARAM_CLEAN);

//header
$plugin_display = get_string('pluginname', $plugin);
rlip_manualrun_page_setup($baseurl, $plugin_display);
echo $OUTPUT->header();

//add a warning message for all imports
$import_warning = get_string('importwarning','block_rlip');
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
