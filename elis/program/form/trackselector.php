<?php

/**
 * This script is used to return a track selection form
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_login(SITEID, false);

global $CFG, $PAGE, $OUTPUT, $DB;

require_once($CFG->dirroot . '/elis/program/lib/lib.php');
require_once($CFG->dirroot . '/elis/program/lib/selectiontable.class.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/dml/moodle_database.php');

define('MAX_NUM_ROWS', 50);

$letterselect   = optional_param('alpha', '', PARAM_TEXT);
$search         = optional_param('search', '', PARAM_TEXT);
$element_update = required_param('id', PARAM_TEXT);

$baseurl        = new moodle_url('/elis/program/form/trackselector.php',
                                array('alpha' => $letterselect,
                                      'search' => $search,
                                      'id' => $element_update));

$PAGE->requires->js('/elis/program/js/resultsengineselector.js', true);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('popup');


echo $OUTPUT->header();

pmalphabox($baseurl);
pmsearchbox('/elis/program/form/trackselector.php');

echo html_writer::start_tag('center');

$alpha          = explode(',', get_string('alphabet', 'langconfig'));

$table          = new trackselectiontable('trackselection', $element_update, 'user_selection');
$columns        = 'id,name,description';
$where          = '';
$alphawhere     = '';
$searchwhere    = '';

if (!empty($letterselect)) {
    $alphawhere = $DB->sql_like('name', "'$letterselect%'", false, false);
}

if (!empty($search)) {
    $searchwhere = $DB->sql_like('name', "'%$search%'", false, false) . ' OR ' .
             $DB->sql_like('description', "'%$search%'", false, false);

}

if (empty($alphawhere) and empty($searchwhere)) {
    $where = '1';
} else {
    if (!empty($alphawhere)) {
        $where = $alphawhere;
    }

    if (!empty($searchwhere)) {

        $where = (empty($where)) ? $searchwhere :
                                   $where . ' AND (' .  $searchwhere .')';
    }

}

$table->set_sql($columns, "{$CFG->prefix}crlm_track", $where);
$table->define_baseurl($baseurl);
$table->collapsible(false);
$table->define_columns(array('name', 'description'));
$table->define_headers(array('Name', 'Description'));
$table->out(MAX_NUM_ROWS);

echo html_writer::end_tag('center');

?>