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
 * @package    dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once('../../../../config.php');
$PAGE->requires->js('/local/datahub/js/jquery-1.9.1.min.js', true);
$PAGE->requires->css('/local/datahub/exportplugins/version1elis/config_fields.css');
$file = get_plugin_directory('dhexport', 'version1elis').'/lib.php';
require_once($file);

// Permissions checking.
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Handle submitted actions.
$baseurl = $CFG->wwwroot.'/local/datahub/exportplugins/version1elis/config_fields.php';
$data_submitted = optional_param('data_submitted', false, PARAM_INT);
if (!empty($data_submitted)) {
    require_sesskey();
    $submitted_fields = optional_param_array('fields', array(), PARAM_SAFEPATH); // Safepath needed for '/'.
    $submitted_fieldnames = optional_param_array('fieldnames', array(), PARAM_ALPHANUM);
    $submitted_data = array(
        'fields' => $submitted_fields,
        'fieldnames' => $submitted_fieldnames
    );

    $processed_formdata = rlipexport_version1elis_extrafields::process_config_formdata($submitted_data);
    rlipexport_version1elis_extrafields::update_config($processed_formdata);
    redirect($baseurl);
}


$available_fields = rlipexport_version1elis_extrafields::get_available_fields();
$enabled_fields = rlipexport_version1elis_extrafields::get_enabled_fields();

$fieldsets = array();
$fields_by_fieldset = array();
$active_fields_by_fieldset_unordered = array();
$active_fields_by_fieldset_order = array();
$name_overrides = array();

foreach ($available_fields as $fieldset => $fields) {
    $fieldset_class = rlipexport_version1elis_extrafields::FIELDSET_PREFIX.$fieldset;
    $fieldsets[$fieldset] = $fieldset_class::get_label();
    foreach ($fields as $field => $header) {
        if (isset($enabled_fields[$fieldset][$field])) {
            $enabled_field_rec = $enabled_fields[$fieldset][$field];
            if ($enabled_field_rec->header !== '' && $enabled_field_rec->header !== null) {
                $name_overrides[$fieldset.'/'.$field] = $enabled_field_rec->header;
            }
            $active_fields_by_fieldset_unordered[$fieldset.'/'.$field] = $header;
            $active_fields_by_fieldset_order[$enabled_fields[$fieldset][$field]->fieldorder] = $fieldset.'/'.$field;
        }

        $fields_by_fieldset[$fieldset][$field] = $header;
    }
}

ksort($active_fields_by_fieldset_order);

// Generate sorted active fields.
$active_fields_by_fieldset_ordered = array();
foreach ($active_fields_by_fieldset_order as $fieldsetfield) {
    $active_fields_by_fieldset_ordered[$fieldsetfield] = $active_fields_by_fieldset_unordered[$fieldsetfield];
}

// Page header.
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$renderer = $PAGE->get_renderer('dhexport_version1elis');
$renderer->page_setup($baseurl);
echo $OUTPUT->header();

// Main Output.
echo $renderer->display_config_ui($fieldsets, $fields_by_fieldset, $active_fields_by_fieldset_ordered, $name_overrides);

// Page footer.
echo $OUTPUT->footer();