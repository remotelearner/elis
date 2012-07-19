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
$file = get_plugin_directory('rlipexport', 'version1elis').'/lib.php';
require_once($file);

//permissions checking
require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context);

//handle submitted actions
$baseurl = $CFG->wwwroot.'/blocks/rlip/exportplugins/version1elis/config_fields.php';
rlipexport_version1elis_config::handle_field_action($baseurl);

//page header
rlipexport_version1elis_page_setup($baseurl);
echo $OUTPUT->header();

//TODO: implement all necessary calls, etc, to make the rest of this code work

//initialize the display table
$table = new html_table();
$table->head = array(get_string('customfieldname', 'rlipexport_version1elis'),
                     get_string('columnheader', 'rlipexport_version1elis'), '', '', '');
$table->data = array();

//fill table rows with selected field information

//information used to track first / last rows
$first = true;
$max_order = $DB->get_field(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, 'MAX(fieldorder)', array());

if ($recordset = rlipexport_version1elis_config::get_configured_fields()) {
    foreach ($recordset as $record) {
        //text box for setting header
        $attributes = array('type' => 'text',
                            'name' => 'header_'.$record->id,
                            'value' => $record->header);
        $header = html_writer::empty_tag('input', $attributes);

        //"move up" link
        if (!$first) {
            $url = $baseurl.'?up='.$record->id;
            $uplink = rlipexport_version1elis_linked_image($url, 't/up');
        } else {
            //first record
            $uplink = '';
        }

        //"move down" link
        if ($record->fieldorder != $max_order) {
            $url = $baseurl.'?down='.$record->id;
            $downlink = rlipexport_version1elis_linked_image($url, 't/down');
        } else {
            //last record
            $downlink = '';
        }

        //delete link
        $url = $baseurl.'?delete='.$record->id;
        $deletelink = rlipexport_version1elis_linked_image($url, 't/delete');

        //append the row
        $table->data[] = array($record->name, $header, $downlink, $uplink, $deletelink);

        //no longer the first record
        $first = false;
    }
}

//output the table within a form
if (!empty($table->data)) {
    //open form
    $attributes = array('action' => $baseurl,
                        'method' => 'post');
    echo html_writer::start_tag('form', $attributes);

    //table
    echo html_writer::table($table);

    //button to revert headers to last saved state
    $attributes = array('type' => 'submit',
                        'value' => get_string('revertheaders', 'rlipexport_version1elis'),
                        'name' => 'revertfields');
    echo html_writer::empty_tag('input', $attributes);
    echo $OUTPUT->spacer(null, true);
    echo $OUTPUT->spacer(null, true);

    //button to save changes to headers
    $attributes = array('type' => 'submit',
                        'value' => get_string('updateheaders', 'rlipexport_version1elis'),
                        'name' => 'updatefields');
    echo html_writer::empty_tag('input', $attributes);
    echo $OUTPUT->spacer(null, true);
    echo $OUTPUT->spacer(null, true);

    //close form
    echo html_writer::end_tag('form');
}

//fetch all available custom fields
$options = array();
if ($recordset = rlipexport_version1elis_config::get_available_fields()) {
    foreach ($recordset as $record) {
        $options[$record->id] = $record->name;
    }
}

if (empty($options)) {
    if (empty($table->data)) {
        echo html_writer::tag('span', get_string('customfieldnotconfig', 'rlipexport_version1elis'));
    } else {
        echo html_writer::tag('span', get_string('customfieldalladded', 'rlipexport_version1elis'));
    }
} else {
    echo html_writer::tag('span', get_string('addfieldinstructions', 'rlipexport_version1elis'));
    echo $OUTPUT->spacer(null, true);

    $displaystring = get_string('addcustomfield', 'rlipexport_version1elis');
    echo $OUTPUT->single_select($baseurl, 'field', $options, '', array('' => $displaystring));
}

//page footer
echo $OUTPUT->footer();
