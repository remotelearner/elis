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
 * @subpackage enrol_survey
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/blocks/enrol_survey/forms.php');
require_once($CFG->dirroot .'/blocks/enrol_survey/lib.php');
require_once($CFG->dirroot .'/elis/program/lib/setup.php');
require_once($CFG->dirroot .'/elis/program/lib/lib.php');
require_once($CFG->dirroot .'/elis/program/lib/deprecatedlib.php'); // cm_get_crlmuserid()
require_once($CFG->dirroot .'/elis/program/lib/data/user.class.php');

global $COURSE, $DB, $ME, $OUTPUT, $PAGE, $USER;

$instanceid = required_param('id', PARAM_INT);
$instance = $DB->get_record('block_instances', array('id' => $instanceid));
$block = block_instance('enrol_survey', $instance);

if (fnmatch($block->instance->pagetypepattern, 'course-view-') && !empty($COURSE->id)) {
    require_course_login($COURSE->id); // TBD
}

if ($COURSE->id == SITEID) {
    $context = get_context_instance(CONTEXT_SYSTEM);
} else {
    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
}

require_capability('block/enrol_survey:take', $context);

$survey_form = new survey_form($CFG->wwwroot .'/blocks/enrol_survey/survey.php?id='. $instanceid);

if ($survey_form->is_cancelled()) {
    redirect($CFG->wwwroot .'/course/view.php?id='. $COURSE->id);
} else if ($formdata = $survey_form->get_data()) {
    $customfields = get_customfields();
    $profilefields = get_profilefields();

    $data = get_object_vars($formdata);
    $u = new user(cm_get_crlmuserid($USER->id));
    if (empty($u->id)) { // ***TBD***
        print_error(get_string('noelisuser', 'block_enrol_survey'));
    }

    foreach ($data as $key => $fd) {
        if (!empty($fd)) {
            if (in_array($key, $profilefields)) {
                if (!empty($u->properties[$key])) {
                    $u->$key($fd);
                }
            } else if (in_array($key, $customfields)) {
                $id = $DB->get_field('user_info_field', 'id', array('shortname' => $key));

                if ($DB->record_exists('user_info_data', array('userid' => $USER->id, 'fieldid' => $id))) {
                    $DB->set_field('user_info_data', 'data', $fd, array('userid'=> $USER->id, 'fieldid' => $id));
                } else {
                    $dataobj = new object();
                    $dataobj->userid = $USER->id;
                    $dataobj->fieldid = $id;
                    $dataobj->data = $fd;
                    $DB->insert_record('user_info_data', $dataobj);
                }
            }
        } else {
            $incomplete = true;
        }
    }

    $u->save();
       
    $usernew = $DB->get_record('user', array('id' => $USER->id));
    foreach ((array)$usernew as $variable => $value) {
        $USER->$variable = $value;
    }

    if (!is_survey_taken($USER->id, $instanceid) && empty($incomplete)) {
        $dataobject = new object();
        $dataobject->blockinstanceid = $instanceid;
        $dataobject->userid = $USER->id;
        $DB->insert_record('block_enrol_survey_taken', $dataobject);
    }

    if (!empty($formdata->save_exit)) {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
    }
}

$toform = array();
$u = new user(cm_get_crlmuserid($USER->id));
$toform = $u->to_object(); // get_object_vars($u);

$customdata = $DB->get_records('user_info_data', array('userid' => $USER->id));
if (!empty($customdata)) {
    foreach ($customdata as $cd) {
        $customfields = $DB->get_record('user_info_field', array('id' => $cd->fieldid));
        $toform[$customfields->shortname] = $cd->data;
    }
}

$blockname = get_string('blockname', 'block_enrol_survey');
$PAGE->set_pagelayout('standard'); // TBV
$PAGE->set_pagetype('elis'); // TBV
$PAGE->set_context($context);
$PAGE->set_url($ME);
$PAGE->set_title($blockname);
$PAGE->set_heading($blockname);
$PAGE->set_cacheable(true);
$PAGE->set_button('&nbsp;');

$PAGE->navbar->add($blockname);
$PAGE->blocks->add_regions(array('side-pre', 'side-post')); // TBV ?

echo $OUTPUT->header();
echo $OUTPUT->heading($block->config->title);
$survey_form->set_data($toform);
$survey_form->display();
echo $OUTPUT->footer();

