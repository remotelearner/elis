<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/user/filters/lib.php');
require_once(elis::lib('form/custom_field_multiselect.php'));

/**
 * Child class to the custom_field_multiselect filter. Returns names - specified for this report - for the supplied ids
 */
class generalized_filter_custom_field_multiselect_values extends generalized_filter_type {
    /**
     * field id and name lists
     */
    var $_fieldidlist;
    var $_reportname;
    var $_options;

    var $_field;

    var $_numeric;

    var $_block_instance;

    /**
     * Constructor
     * @param string $uniqueid the name of the column
     * @param string $alias an alias for the column name
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field user table field name
     * @param array $options select options <= in this case, this contains the list of selected custom fields
     */
    function generalized_filter_custom_field_multiselect_values($uniqueid, $alias, $name, $label, $advanced, $field, $options = array(),$fieldidlist=array(),$fieldnamelist=array(),$action='') {
        parent::generalized_filter_type($uniqueid, $alias, $name, $label, $advanced);

        // Initialize class variables
        $this->_field = $field;
        $this->_block_instance = $options['block_instance'];
        $this->_fieldidlist = $fieldidlist;
        $this->_reportname = (isset($options['reportname'])) ? $options['reportname'] : '';

        if (isset($options['help'])) {
            $this->_filterhelp = $options['help'];
        } else {
            $this->_filterhelp = null;
        }
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        // Check permissions and don't display filter if there are no course fields to display for this user
        if (!$this->check_for_custom_fields('course')) {
            return false;
        }

        $this_scheduled = false;
        // Grab the workflow default values for this element if in the report scheduling interface
        if ($this->execution_mode == php_report::EXECUTION_MODE_SCHEDULED) {
            if (isset($mform->_defaultValues['fieldidlist'.$this->_reportname])) {
                $this->_fieldidlist = $mform->_defaultValues['fieldidlist'.$this->_reportname];
            } else {
                $this->_fieldidlist = '';
            }
            $this_scheduled = true;
        }

        $mform->addElement(elis_custom_field_multiselect::NAME, $this->_uniqueid, $this->_label, array('contextlevel' => context_level_base::get_custom_context_level('course', 'elis_program')));
        if (!empty($this->_filterhelp)) {
            $mform->addHelpButton($this->_uniqueid, $this->_filterhelp[0], $this->_filterhelp[2]);
        }
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field = $this->_uniqueid;
        $fieldids = 'fieldidlist'.$this->_block_instance;
        if (array_key_exists($field, $formdata) and $formdata->$field !== '') {
            return array('value'=>(string)$formdata->$field);
        }

        return false;
    }

    function get_report_parameters($data) {
        return array('value' => $data['value'],
                     'numeric' => $this->_numeric);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        // Modified to give acceptable label for scheduling
        //$value = $data['value'];

        $a = new object();
        $a->label    = $this->_label;
        if (empty($data['value'])) {
            $a->value    = ': none selected';
        } else {
            $selectedfields = explode(',', $data['value']);
            $context = context_level_base::get_custom_context_level('course', 'elis_program');
            $fields = field::get_for_context_level($context)->to_array();
            $a->value = ': ' . implode(', ', array_map(function($id) use ($fields) { return $fields[$id]->name;}, $selectedfields));
        }
        $a->operator = '';

        return get_string('selectlabel', 'filters', $a);
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return string the filtering condition or null if the filter is disabled
     */
    function get_sql_filter($data) {
        // No processing here
        return null;
    }

    /**
     * Return a boolean to indicate whether or not this filter is displayed
     * depending upon whether any custom fields are found for this user
     * @param string       $field_type  type of custom field to check
     * @return boolean  true if the filter is to show
     */
    function check_for_custom_fields($field_type) {

        // Get custom course fields by context level
        $context = context_level_base::get_custom_context_level($field_type, 'elis_program');
        $fields = field::get_for_context_level($context);
        $fields = $fields ? $fields : array();
        $testfields = array();
        foreach ($fields as $field) {
            //make sure the current user can access this field in at least one
            //course context
            $owners = field_owner::get_for_field($field);
            if (!block_php_report_field_accessible($owners)) {
                continue;
            }

            return true;
        }

        return false;
    }
}

