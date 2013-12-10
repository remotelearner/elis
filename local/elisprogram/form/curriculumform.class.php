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

require_once elispm::file('form/cmform.class.php');
require_once elispm::lib('lib.php');

/**
 * the form element for curriculum
 */
class cmCurriculaForm extends cmform {
    /**
     * defines items in the form
     */
    public function definition() {
        $configData = array('title');

        if($this->_customdata['obj']) {
            // FIXME: This is probably not be the right place for set_data.  Move it.
            $this->set_data($this->_customdata['obj']);
        }

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'idnumber', get_string('curriculum_idnumber', 'local_elisprogram') . ':');
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', null, 'required', null, 'client');
        $mform->addRule('idnumber', null, 'maxlength', 100);
        $mform->addHelpButton('idnumber', 'curriculaform:curriculum_idnumber', 'local_elisprogram');

        $mform->addElement('text', 'name', get_string('curriculum_name', 'local_elisprogram') . ':');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 64);
        $mform->addHelpButton('name', 'curriculaform:curriculum_name', 'local_elisprogram');

        $attributes = array('rows'=>'2', 'cols'=>'40');
        $mform->addElement('textarea', 'description', get_string('description', 'local_elisprogram') . ':', $attributes);
        $mform->setType('description', PARAM_CLEAN);
        $mform->addHelpButton('description', 'curriculaform:curriculum_description', 'local_elisprogram');

        $mform->addElement('text', 'reqcredits', get_string('required_credits', 'local_elisprogram') . ':');
        $mform->setType('reqcredits', PARAM_TEXT);
        $mform->addRule('reqcredits', null, 'maxlength', 10);
        $mform->addHelpButton('reqcredits', 'curriculaform:required_credits', 'local_elisprogram');

        $choices = range(0, 10);
        $mform->addElement('select', 'priority', get_string('priority', 'local_elisprogram') . ':', $choices);
        $mform->addHelpButton('priority', 'curriculaform:priority', 'local_elisprogram');

        //because moodle forms will not allow headers within headers
        $mform->addElement('header', 'editform', get_string('time_settings', 'local_elisprogram'));

        // Time to complete
        $mform->addElement('text', 'timetocomplete', get_string('time_to_complete', 'local_elisprogram') . ':');
        $mform->setType('timetocomplete', PARAM_TEXT);
        $mform->addRule('timetocomplete', null, 'maxlength', 64);
        $mform->addHelpButton('timetocomplete', 'curriculaform:time_to_complete', 'local_elisprogram');

        //$mform->addElement('html', '<small>' . get_string('tips_time_to_complete', 'local_elisprogram') . '</small><br /><br />');

        // Frequency (only display if curriculum expiration is currently enabled).
        if (!empty(elis::$config->local_elisprogram->enable_curriculum_expiration)) {
            $mform->addElement('text', 'frequency', get_string('expiration', 'local_elisprogram') . ':');
            $mform->setType('frequency', PARAM_TEXT);
            $mform->addRule('frequency', null, 'maxlength', 64);
            $mform->addHelpButton('frequency', 'curriculaform:expiration', 'local_elisprogram');
        } else {
            $mform->addElement('hidden', 'frequency');
            $mform->setType('frequency', PARAM_TEXT);
        }

        //$mform->addElement('html', '<small>' . get_string('tips_time_to_redo', 'local_elisprogram') . '</small><br /><br />');

        $mform->addElement('static', '', '', '<small>'.get_string('tips_time_format', 'local_elisprogram').'</small>');

        // custom fields
        $this->add_custom_fields('curriculum', 'local/elisprogram:program_edit',
                                 'local/elisprogram:program_view');

        $this->add_action_buttons();
    }

    function check_unique($table, $field, $value, $id) {
        global $DB;
        return !$DB->record_exists_select($table, "$field = ? AND id <> ?", array($value, $id));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['timetocomplete'])) {
            if (!datedelta::validate($data['timetocomplete'])) {
                $errors['timetocomplete'] = get_string('error_not_timeformat', 'local_elisprogram');
            } else {
                $datedelta = new datedelta($data['timetocomplete']);

                if ($datedelta->is_zero() || !$datedelta->getDateString()) {
                    $errors['timetocomplete'] = get_string('error_invalid_timeperiod', 'local_elisprogram');
                }
            }
        }

        if (!empty($data['frequency'])) {
            if (!datedelta::validate($data['frequency'])) {
                $errors['frequency'] = get_string('error_not_timeformat', 'local_elisprogram');
            } else {
                $datedelta = new datedelta($data['frequency']);

                if ($datedelta->is_zero() || !$datedelta->getDateString()) {
                    $errors['frequency'] = get_string('error_invalid_timeperiod', 'local_elisprogram');
                }
            }
        }

        if (!empty($data['idnumber'])) {
            if (!$this->check_unique(curriculum::TABLE, 'idnumber', $data['idnumber'], $data['id'])) {
                $errors['idnumber'] = get_string('badidnumber', 'local_elisprogram');
            }
        }

        $errors += parent::validate_custom_fields($data, 'curriculum');

        return $errors;
    }

    /**
     * Overridden to specially handle timetocomplete and frequency fields.
     */
    function get_data($slashed=false) {
        $data = parent::get_data($slashed);

        if(!empty($data)) {
            $datedelta = new datedelta($data->timetocomplete);
            $data->timetocomplete = $datedelta->getDateString();

            $datedelta = new datedelta($data->frequency);
            $data->frequency = $datedelta->getDateString();
        }

        return $data;
    }
}
