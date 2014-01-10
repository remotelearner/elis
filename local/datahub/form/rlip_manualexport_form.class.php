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
 * Form that displays a button, allowing users to run an export manually
 */
class rlip_manualexport_form extends moodleform {
    function definition() {
        //obtain the QuickForm
        $mform = $this->_form;

        //used to store the plugin between form submits
        $mform->addElement('hidden', 'plugin');
        $mform->setType('plugin', PARAM_TEXT);

        //add submit button (can't use add_action_buttons due to javascript usage)
        //note that the manualrun script includes lib.js for us right now
        $attributes = array('onclick' => 'rlip_clear_export_ui()');
        $mform->addElement('submit', 'submitbutton', get_string('runnow', 'local_datahub'), $attributes);
        $mform->closeHeaderBefore('submitbutton');
    }
}