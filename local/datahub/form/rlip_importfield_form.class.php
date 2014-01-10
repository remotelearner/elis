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
 * Form to be used for configuring import field mappings in a generic way
 */
class rlip_importfield_form extends moodleform {
    /**
     * Main form UI element definition function
     */
    function definition() {
        //obtain Quickform
        $mform =& $this->_form;

        //add the specified fields to the UI
        $fields = $this->_customdata;
        foreach ($fields as $field) {
            $mform->addElement('text', $field, $field);
            $mform->setType($field, PARAM_TEXT);
        }

        //add hidden element for storing the tab
        $mform->addElement('hidden', 'tab');
        $mform->setType('tab', PARAM_TEXT);

        //add custom action buttons, as defined in the function below
        $this->add_action_buttons();
    }

    /**
     * Custom function to add a third ("reset") button to the bottom of this form
     *
     * @param boolean $cancel whether to show cancel button, default true
     * @param string $submitlabel label for submit button, defaults to get_string('savechanges')
     */
    function add_action_buttons($cancel = true, $submitlabel = NULL) {
        $mform =& $this->_form;
        //button labels
        $submitlabel = get_string('savechanges');
        $restoredefaultslabel = get_string('restoredefaults', 'local_datahub');

        $buttonarray=array();
        //save changes
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        //restore defaults
        $buttonarray[] = &$mform->createElement('submit', 'reset', $restoredefaultslabel);
        //cancel
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Custom validation function
     *
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *               or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    function validation($data, $files) {
        global $DB, $CFG;

        //include any errors from the parent class
        $errors = parent::validation($data, $files);

        //validate that, when submitting, duplicate custom names are not used
        if (isset($data['submitbutton'])) {
            //track names already used
            $customfieldnames = array();

            foreach ($this->_customdata as $standardfieldname) {
                //look up the custom value
                $customfieldname = $data[$standardfieldname];

                if (in_array($customfieldname, $customfieldnames)) {
                    //already used
                    $errors[$standardfieldname] = get_string('duplicate', 'local_datahub');
                } else {
                    $customfieldnames[] = $customfieldname;
                }
            }
        }

        return $errors;
    }
}