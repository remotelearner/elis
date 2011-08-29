<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage pm-blocks-phpreport-individual_course_progress
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once (dirname(__FILE__) .'/../../../../config.php');

require_once($CFG->dirroot .'/elis/program/lib/setup.php');
require_once($CFG->dirroot .'/elis/core/lib/data/customfield.class.php');
require_once($CFG->dirroot .'/blocks/php_report/sharedlib.php');

global $OUTPUT, $PAGE;

// Get required yui javascript for ajax calls
$PAGE->requires->yui2_lib(array('yahoo', 'dom', 'event', 'connection'));
$PAGE->requires->js('/elis/core/js/associate.class.js');
$PAGE->requires->js('/elis/program/js/customfields.js');

//TBD: setup $PAGE

$lang_file = 'rlreport_individual_course_progress';

$site = get_site();

$block_id      = required_param('instance', PARAM_RAW);
$fieldidlist   = optional_param('fieldidlist', null,  PARAM_TEXT);
$fieldnamelist = optional_param('fieldnamelist', null,  PARAM_TEXT);

// Get custom course fields by context level
$context = context_level_base::get_custom_context_level('course', 'elis_program');
$fields = field::get_for_context_level($context);
$fields = $fields ? $fields : array();

//Unserialize fieldidlist to check against field list
if (isset($fieldidlist)) {
    $fieldidlist = unserialize(base64_decode($fieldidlist));
}
if (isset($fieldnamelist)) {
    $fieldnamelist = unserialize(base64_decode($fieldnamelist));
}

$categories = field_category::get_for_context_level($context);
$categories = $categories ? $categories : array();

// divide the fields into categories
$fieldsbycategory = array();
foreach ($categories as $category) {
    $fieldsbycategory[$category->name] = array();
}
foreach ($fields as $field) {
    if (is_array($fieldidlist) && in_array($field->id, $fieldidlist)) {
        continue;
    }

    //make sure the current user can access this field in at least one
    //course context
    $owners = field_owner::get_for_field($field);
    if (!block_php_report_field_accessible($owners)) {
        continue;
    }

    $fieldsbycategory[$field->categoryname][] = $field;
}

echo $OUTPUT->header($site->shortname .': '. get_string('selectcustomfields', $lang_file));

// show list of available fields
if (empty($fieldsbycategory)) {
    echo '<div>'. get_string('nofieldsfound', $lang_file) .'</div>';
} else {
    echo '<div>'. get_string('customfields', $lang_file) .'</div>';

    $columns = array(
        'category' => array('header' => get_string('category', $lang_file)),
        'name'     => array('header' => get_string('name', $lang_file))
        );

    $curr_category = '';
    $data = array();
    foreach ($fieldsbycategory as $category => $fields) {
        $field_count = 0;
        foreach ($fields as $field) {
            $newarr = new stdClass;
            // Set up category name to display
            $newarr->category = (!$field_count == 0) ? $category : '&nbsp;';
            $field_count++;

            // Set up course field link to add course field to filter list
            // Custom field name, for now, also includes the category name
            // Exlucde the pretest and posttest because they are already in the report by default
            $newarr->name = '';
            if ($field->shortname != "_elis_course_pretest" && $field->shortname != "_elis_course_posttest") {
                $newarr->name = '<a href="#" '.make_js_event($block_id,
                                                         $field->id,
                                                         $category.' - '.$field->name,
                                                         $fieldidlist,
                                                         $fieldnamelist)
                                          .' >'.$field->name.'</a>';
                $data[] = $newarr;
            }
        }
    }

    $table = new display_table($data, $columns);

  /*** TBV ***
    foreach ($columns as $column => $cdesc) {
        $$column        = $cdesc;
        $table->head[]  = $$column;
        $table->align[] = 'left';
    }
  ***/

    // Setup table
    $table->width = "95%"; // TBD

    echo $table;
}

?>
<div style="text-align: right"><a href="javascript:window.close()">Close window</a></div>
<?php

print_footer('empty');

/**
 * Generates an onclick event that
 * calls javascript to update the fieldtable with
 * a new custom field
 *
 * @param   string  $block_id       The block id uniquely identifies the field table
 * @param   string  $field_id       The custom field form element id
 * @param   string  $fieldname      The custom field display name (category + name)
 * @param   string  $fieldidlist    List of custom field ids currently included in report
 * @param   string  $fieldnamelist  List of custom field names currently included in report
 *
 * @return  string       HTML onclick event call
 */
function make_js_event($block_id, $field_id,$fieldname,$fieldidlist,$fieldnamelist) {
    global $CFG;

    // This link will call the updateTable javascript function to add a new field
    // It will pass the div or whatever to be updated and the path to the php to be called
    // Set add a field action
    $action = 'add';

    // Encode arrays
    $fieldidlist = urlencode(base64_encode(serialize($fieldidlist)));
    $fieldnamelist = urlencode(base64_encode(serialize($fieldnamelist)));
    $fieldname = urlencode(base64_encode(serialize($fieldname)));

    $js_event = ' onclick="customfields_updateTable(\''.$block_id.'\',\''.$action.'\',\''.
                $CFG->wwwroot .'/blocks/php_report/instances/individual_course_progress/\',\''.
                $field_id.'\',\''.$fieldname.'\',\''.$fieldidlist.'\',\''.$fieldnamelist.'\');return false;"';

    return $js_event;
}

