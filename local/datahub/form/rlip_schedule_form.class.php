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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot .'/lib/formslib.php');
require_once($CFG->dirroot .'/local/datahub/lib.php');

class rlip_base_schedule_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'plugin');
        $mform->setType('plugin', PARAM_TEXT);
        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_TEXT);

        $elem = $mform->createElement('text', 'label',
                           get_string('rlip_form_label', 'local_datahub'));
        $mform->addElement($elem);
        $mform->setType('label', PARAM_TEXT);
        $mform->addRule('label', get_string('required'), 'required');
        $mform->addHelpButton('label', 'rlip_form_label', 'local_datahub');

        $elem = $mform->createElement('text', 'period', 
                           get_string('rlip_form_period', 'local_datahub'));
        $mform->addElement($elem);
        $mform->setType('period', PARAM_TEXT);
        $mform->addRule('period', get_string('required'), 'required');
        $mform->addHelpButton('period', 'rlip_form_period', 'local_datahub');

        // Add any custom fields for specific IP plugin form
        $this->add_custom_fields($mform);

        // Add submit & cancel buttons
        $this->add_action_buttons(true, get_string('submit')); //TBD: savechanges
    }

    function add_custom_fields($mform) {
        // does nothing in base
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!isset($data['period']) ||
            rlip_schedule_period_minutes($data['period']) < 5) {
            $errors['period'] = get_string('rlip_form_period_error', 'local_datahub');
        }
        return $errors;
    }
}

class rlip_import_schedule_form extends rlip_base_schedule_form {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', get_string('rlip_form_import_header',
                                              'local_datahub'));
        parent::definition();
    }

    // Add file selections to base form
    function _add_custom_fields($mform) {
        $mform->addElement('html', '<hr/>'); // TBD
        for ($i = 0; $i < count($this->_customdata['files']); $i++) {
            $mform->addElement('filepicker', 'file'. $i,
                               $this->_customdata['files'][$i]);
        }
        $mform->addElement('html', '<hr/>'); // TBD
    }
}

class rlip_export_schedule_form extends rlip_base_schedule_form {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', get_string('rlip_form_export_header',
                                              'local_datahub'));
        parent::definition();
    }
}

