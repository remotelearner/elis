<?php

/**
 * This script is used to return a track selection form
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_login(SITEID, false);

global $CFG, $PAGE, $OUTPUT, $DB;

require_once($CFG->dirroot . '/elis/program/lib/lib.php');
require_once($CFG->dirroot . '/elis/program/lib/resultsenginelib.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/dml/moodle_database.php');

define('MAX_NUM_ROWS', 50);

$letterselect   = optional_param('alpha', '', PARAM_TEXT);
$search         = optional_param('search', '', PARAM_TEXT);
$element_update = required_param('id', PARAM_TEXT);

$baseurl        = new moodle_url('/elis/program/form/classselector.php',
                                array('alpha' => $letterselect,
                                      'search' => $search,
                                      'id' => $element_update));

$PAGE->requires->js('/elis/program/js/resultsengineselector.js', true);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('popup');


echo $OUTPUT->header();

pmalphabox($baseurl);
pmsearchbox('/elis/program/form/classselector.php');

echo html_writer::start_tag('center');

$alpha          = explode(',', get_string('alphabet', 'langconfig'));

$table          = new classselectiontable('classselection', $element_update, 'user_selection');
$columns        = 'cls.id,cd.syllabus,cls.idnumber';
$where          = '';
$alphawhere     = '';
$searchwhere    = '';
$params         = array();

if (!empty($letterselect)) {
    $alphawhere = $DB->sql_like('cd.syllabus', ':alphasyllabus', false);
    $params['alphasyllabus'] = $letterselect.'%';
}

if (!empty($search)) {
    $searchwhere = $DB->sql_like('cls.idnumber', ':searchclsidnumber' , false, false) . ' OR ' .
             $DB->sql_like('cd.syllabus', ':searchsyllabus', false, false);

    $params['searchclsidnumber']    = '%'.$search.'%';
    $params['searchsyllabus']       = '%'.$search.'%';


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

$from = "{$CFG->prefix}crlm_course cd RIGHT JOIN {$CFG->prefix}crlm_class cls ON cd.id = cls.courseid ";
$table->set_sql($columns, $from, $where, $params);
$table->define_baseurl($baseurl);
$table->collapsible(false);
$table->define_columns(array('syllabus', 'idnumber'));
$table->define_headers(array('Course Description LANGUAGE STRING', 'ID Number LANGUAGE STRING'));
$table->out(MAX_NUM_ROWS, false);

echo html_writer::end_tag('center');

?>