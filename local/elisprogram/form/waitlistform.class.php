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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::file('form/cmform.class.php'); // TBD
require_once elispm::file('form/selectionform.class.php');

class waitlistaddform extends cmform {

    public function definition() {
        global $DB;
        parent::definition();

        $mform = &$this->_form;

        if(!empty($this->_customdata['students'])) {
            $student_list = $this->_customdata['students'];

            $mform->addElement('header', 'waitlistaddform', get_string('waitinglistform_title', 'local_elisprogram'));

            foreach($student_list as $student) {
                $mform->addElement('hidden', 'userid[' . $student->userid . ']', $student->userid);
                $mform->setType('userid['.$student->userid.']', PARAM_INT);
                $mform->addElement('hidden', 'classid[' . $student->userid . ']', $student->classid);
                $mform->setType('classid['.$student->userid.']', PARAM_INT);
                $mform->addElement('hidden', 'enrolmenttime[' . $student->userid . ']', $student->enrolmenttime);
                $mform->setType('enrolmenttime['.$student->userid.']', PARAM_INT);

                $enrol_options = array();
                $enrol_options[] = $mform->createElement('radio', 'enrol[' . $student->userid . ']', '', get_string('yes'), 1);
                $enrol_options[] = $mform->createElement('radio', 'enrol[' . $student->userid . ']', '', get_string('no'), 0);

                $context = context_system::instance();

                if(has_capability('local/elisprogram:overrideclasslimit', $context)) {
                    $mform->addElement('hidden', 'grade[' . $student->userid . ']', $student->grade);
                    $mform->setType('grade['.$student->userid.']', PARAM_INT);
                    $mform->addElement('hidden', 'credits[' . $student->credits . ']', $student->credits);
                    $mform->setType('credits['.$student->credits.']', PARAM_INT);
                    $mform->addElement('hidden', 'locked[' . $student->locked . ']', $student->locked);
                    $mform->setType('locked['.$student->locked.']', PARAM_INT);

                    $enrol_options[] = $mform->createElement('radio', 'enrol[' . $student->userid . ']', '', get_string('over_enrol', 'local_elisprogram'), 2);
                }

                $user = new stdClass;
                $user->name = '?';
                if ($tmpuser = new user($student->userid)) {
                    $tmpuser->load();
                    $user = $tmpuser->to_object();
                    $user->name = $tmpuser->moodle_fullname();
                }
                $mform->addGroup($enrol_options, 'options[' . $student->userid . ']', get_string('add_to_waitinglist', 'local_elisprogram', $user), array('&nbsp;&nbsp;&nbsp;'), false);
            }
        } else if(!empty($this->_customdata['student_ids'])) {
            $student_id = $this->_customdata['student_ids'];

            foreach($student_id as $id=>$student) {
                $mform->addElement('hidden', 'userid[' . $id . ']');
                $mform->setType('userid['.$id.']', PARAM_INT);
                $mform->addElement('hidden', 'classid[' . $id . ']');
                $mform->setType('classid['.$id.']', PARAM_INT);
                $mform->addElement('hidden', 'enrolmenttime[' . $id . ']');
                $mform->setType('enrolmenttime['.$id.']', PARAM_INT);

                $enrol_options = array();
                $enrol_options[] = $mform->createElement('radio', 'enrol[' . $id . ']', '', get_string('yes'), 1);
                $enrol_options[] = $mform->createElement('radio', 'enrol[' . $id . ']', '', get_string('no'), 0);

                $context = context_system::instance();

                if(has_capability('local/elisprogram:overrideclasslimit', $context)) {
                    $enrol_options[] = $mform->createElement('radio', 'enrol[' . $id . ']', '', get_string('over_enrol', 'local_elisprogram'), 2);
                }

                $name = 'no name';
                $mform->addGroup($enrol_options, 'options[' . $id . ']', $name, '', false);
            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('submit', 'submitbutton', 'Save');
    }
}

class waitlisteditform extends selectionform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $actions = array('remove' => get_string('remove'),
                         'overenrol' => get_string('over_enrol', 'local_elisprogram'));
        $mform->addElement('select', 'do', get_string('withselectedusers'), $actions);

        parent::definition();
    }
}
