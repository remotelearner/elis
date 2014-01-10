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

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Form that displays filepickers for each available import file, plus
 * appropriate buttons, for running imports manually
 */
class rlip_manualimport_form extends moodleform {
    function definition() {
        //obtain the QuickForm
        $mform = $this->_form;

        //used to store the plugin between form submits
        $mform->addElement('hidden', 'plugin');
        $mform->setType('plugin', PARAM_TEXT);

        //add a file picker for each label
        for ($i = 0; $i < count($this->_customdata); $i++) {
            $mform->addElement('filepicker', 'file'.$i, $this->_customdata[$i]);
        }

        //add submit button
        $this->add_action_buttons(false, get_string('runnow', 'local_datahub'));
    }
}