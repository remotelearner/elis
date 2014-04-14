<?php
/**
 * Example test form for Alfresco file manager.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

require_once(dirname(__FILE__) .'/../../../../config.php');
global $CFG, $OUTPUT, $PAGE, $USER;

require_once("{$CFG->dirroot}/repository/elisfiles/lib.php");
require_once($CFG->dirroot .'/lib/formslib.php');
require_once('./alfresco_filemanager.php');

class alfreso_test_form extends moodleform {
    var $afm_elem = null;

    function definition() {
        global $DB, $USER;
        $mform = & $this->_form;
        $ret = array('locations' => array('course' => 'course'));
        $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE r.type=? AND i.typeid=r.id';
        $repository = $DB->get_record_sql($sql, array('elisfiles'));
        if ($repository) {
            try {
                $repo = new repository_elisfiles('elisfiles', context_user::instance($USER->id), array(
                    'ajax' => false,
                    'name' => $repository->name,
                    'type' => 'elisfiles')
                );
                if (!empty($repo)) {
                    $ret = $repo->get_listing();
                }
            } catch (Exception $e) {
                $repo = null;
           }
        }
        // ob_start();
        // var_dump($ret);
        // $tmp = ob_get_contents();
        // ob_end_clean();
        // error_log("alfresco_filemanager::test_form:: ret = {$tmp}");

        $fm_options = array('maxfiles'   => -1,
                            'maxbytes'   => 1000000000,
                            'sesskey'    => sesskey(),
                            'locations'  => $ret['locations'] // TBD
                      );
        $attrs = null; // TBD
        $this->afm_elem = $mform->createElement('alfresco_filemanager',
                             'files_filemanager',
                          // NOTE: ^^^ element name MUST be 'files_filemanager'
                             '<b>Alfresco File Manager Form Element</b>',
                             $attrs, array_merge($fm_options, $this->_customdata['options']));
        $mform->addElement($this->afm_elem);

        $mform->addElement('hidden', 'returnurl', $this->_customdata['data']->returnurl);
        $mform->setType('returnurl', PARAM_URL);

        $this->add_action_buttons(true, get_string('savechanges'));

        $this->set_data($this->_customdata['data']);
    }

}

$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('user-files');
$PAGE->set_heading('<b>Alfresco File Manager</b>');
$PAGE->set_url('/repository/elisfiles/lib/form/testform.php');

$data = new stdClass;
$data->returnurl = new moodle_url('/repository/elisfiles/lib/form/testform.php');
$options = array('subdirs'=>1, 'maxbytes'=>$CFG->userquota, 'maxfiles'=>-1, 'accepted_types'=>'*');
$data = file_prepare_standard_filemanager($data, 'files', $options, $context, 'user', 'private', 0);

$form = new alfreso_test_form($data->returnurl,
                              array('data' => $data, 'options' => $options));

if ($form->is_cancelled()) {
    redirect($data->returnurl);
} else if ($formdata = $form->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $context, 'user', 'private', 0);
    redirect($data->returnurl);
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
