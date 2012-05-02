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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/form/rlip_manualexport_form.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

//permissions checking
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context);

//need base URL for form
$baseurl = $CFG->wwwroot.'/blocks/rlip/exportplugins/manualrun.php';

//determine which plugin we're using
$plugin = required_param('plugin', PARAM_CLEAN);

//page setup
$plugin_display = get_string('pluginname', $plugin);
rlip_manualrun_page_setup($baseurl, $plugin_display);

//javascript library needed by the form
$PAGE->requires->js('/blocks/rlip/js/lib.js');

//create our basic form
$form = new rlip_manualexport_form();
$form->set_data(array('plugin' => $plugin));

//run the export before printing a page header
if ($data = $form->get_data()) {
    //run the export
    $filename = rlip_get_export_filename($plugin, $USER->timezone);
    $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, false, true);
    //indicate to the factory class that this is a manual run
    $manual = true;
    $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin, $manual);
    ob_start();
    $result = $instance->run(0, 0, rlip_get_maxruntime());
    $errors = ob_get_contents();
    ob_end_clean();

    if ($result !== null) {
        // Error running export (probably time limit exceeded)
        echo $OUTPUT->header();
        //display errors in a span so we can clear it using javascript when needed
        $attributes = array('id' => 'rlipexporterrors');
        echo html_writer::tag('span', $errors, $attributes);
        //display the form
        $form->display();
        echo $OUTPUT->footer();
    } else {
        $fileplugin->send_headers();
        $fileplugin->output_file();
        $fileplugin->delete();
    }

    //stop page output so that HTML isn't included in the export file
    die;
}

//header
echo $OUTPUT->header();

//display the form
$form->display();

//footer
echo $OUTPUT->footer();

