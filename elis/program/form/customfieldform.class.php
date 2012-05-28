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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once elispm::file('form/cmform.class.php');

class customfieldform extends cmform {
    function definition() {
        global $CFG, $DB, $PAGE;

        $form =& $this->_form;

        $form->addElement('hidden', 'id');
        $form->addElement('hidden', 'manual_field_startyear');
        $form->addElement('hidden', 'manual_field_stopyear');
        $form->addElement('hidden', 'manual_field_inctime');
        $form->setType('id', PARAM_INT);

        // Include required yui javascript
        $PAGE->requires->yui2_lib(array('yahoo',
                                        'dom'));
        $form->addElement('html', '<script type="text/javascript">
            function switchDefaultData() {
                var elem;
                var elemid;
                var fcontrol = document.getElementById("id_manual_field_control");
                var dttext = document.getElementById("datatype_text");
                var dtcheckbox = document.getElementById("datatype_checkbox");
                var dtdatetime = document.getElementById("datatype_datetime");
                elemid = "datatype_" + fcontrol.options[fcontrol.selectedIndex].value;
                //alert("switchDefaultData(): elemid = " + elemid);
                if (!(elem = document.getElementById(elemid))) {
                    elemid = "datatype_text";
                    elem = dttext;
                }
                if (elemid == "datatype_checkbox") {
                    dtcheckbox.className = "clearfix custom_field_default_fieldset";
                    dttext.className = "accesshide custom_field_default_fieldset";
                    dtdatetime.className = "accesshide custom_field_default_fieldset";
                } else if (elemid == "datatype_datetime") {
                    dtdatetime.className = "clearfix custom_field_default_fieldset";
                    dtcheckbox.className = "accesshide custom_field_default_fieldset";
                    dttext.className = "accesshide custom_field_default_fieldset";
                } else { // default: datatype_text
                    dttext.className = "clearfix custom_field_default_fieldset";
                    dtdatetime.className = "accesshide custom_field_default_fieldset";
                    dtcheckbox.className = "accesshide custom_field_default_fieldset";
                }
            }
        function initCustomFieldDefault() {
            YAHOO.util.Event.addListener(window, "load", switchDefaultData());
        }
        YAHOO.util.Event.onDOMReady(initCustomFieldDefault);
        </script>');

        // common form elements (copied from /user/profile/definelib.php)
        $form->addElement('header', '_commonsettings', get_string('profilecommonsettings', 'admin'));
        $strrequired = get_string('required');

        $form->addElement('text', 'shortname', get_string('profileshortname', 'admin'), array('maxlength'=>'100', 'size'=>'25'));
        $form->addRule('shortname', $strrequired, 'required', null, 'client');
        $form->setType('shortname', PARAM_SAFEDIR);

        $form->addElement('text', 'name', get_string('profilename', 'admin'), array('size'=>'50'));
        $form->addRule('name', $strrequired, 'required', null, 'client');
        $form->setType('name', PARAM_MULTILANG);

        $level = $this->_customdata->required_param('level', PARAM_ACTION);
        $ctxlvl = context_elis_helper::get_level_from_name($level);
        $categories = field_category::get_for_context_level($ctxlvl);
        $choices = array();
        foreach ($categories as $category) {
            $choices[$category->id] = $category->name;
        }
        $form->addElement('select', 'categoryid', get_string('profilecategory', 'admin'), $choices);

        $form->addElement('htmleditor', 'description', get_string('profiledescription', 'admin'));
        //$form->addHelpButton('description', 'helptext');

        $choices = array(
            'text' => get_string('field_datatype_text', 'elis_program'),
            'char' => get_string('field_datatype_char', 'elis_program'),
            'int' => get_string('field_datatype_int', 'elis_program'),
            'num' => get_string('field_datatype_num', 'elis_program'),
            'bool' => get_string('field_datatype_bool', 'elis_program'),
            'datetime' => get_string('field_datatype_datetime', 'elis_program'),
            );
        $form->addElement('select', 'datatype', get_string('field_datatype', 'elis_program'), $choices);

        $form->addElement('advcheckbox', 'forceunique', get_string('profileforceunique', 'admin'));
        $form->setAdvanced('forceunique');

        $form->addElement('advcheckbox', 'multivalued', get_string('field_multivalued', 'elis_program'));
        $form->setAdvanced('multivalued');
        $form->disabledIf('multivalued', 'datatype', 'eq', 'datetime');

        // ELIS-4592: default needs to use custom field type control
        // for checkbox OR datetime which requires javascript to update this
        // when control type is changed!
        $form->addElement('html', '<fieldset class="clearfix" id="datatype_text">');
        $form->addElement('text', 'defaultdata_text', get_string('profiledefaultdata', 'admin'), array('size'=>'50'));
        $form->setType('defaultdata', PARAM_MULTILANG); // TBD???

        $form->addElement('html', '</fieldset>');

        $form->addElement('html', '<fieldset class="accesshide" id="datatype_checkbox">');
        $form->addElement('advcheckbox', 'defaultdata_checkbox', get_string('profiledefaultdata', 'admin'));
        $form->addElement('html', '</fieldset>');

        $form->addElement('html', '<fieldset class="accesshide" id="datatype_datetime">');

        $fid = $this->_customdata->optional_param('id', 0, PARAM_INT);
        $from = $this->_customdata->optional_param('from', '', PARAM_CLEAN);
        $startyear = $stopyear = $inctime = false;
        if ($from == 'moodle') {
            $startyear = $DB->get_field('user_info_field', 'param1',
                                array('id' => $fid));
            $stopyear = $DB->get_field('user_info_field', 'param2',
                               array('id' => $fid));
            $inctime = $DB->get_field('user_info_field', 'param3',
                               array('id' => $fid));
        } else if ($fid) {
            $fparams = $DB->get_field(field_owner::TABLE, 'params',
                               array('fieldid' => $fid, 'plugin' => 'manual'));
            $foptions = unserialize($fparams);
            $startyear = !empty($foptions['startyear']) ? $foptions['startyear'] : false;
            $stopyear = !empty($foptions['stopyear']) ? $foptions['stopyear'] : false;
            $inctime = !empty($foptions['inctime']);
        }
        $form->addElement($inctime ? 'date_time_selector' : 'date_selector',
                 'defaultdata_datetime', get_string('profiledefaultdata', 'admin'),
                 array('startyear' => $startyear ? $startyear : 1970,
                       'stopyear' => $stopyear ? $stopyear : 2038,
                       'timezone' => 99, 'optional' => false)); // TBD!?!
        $form->addElement('html', '</fieldset>');

        $plugins = get_list_of_plugins('elis/core/fields');

        foreach ($plugins as $plugin) {
            if (is_readable(elis::plugin_file("elisfields_{$plugin}",'custom_fields.php'))) {
                include_once(elis::plugin_file("elisfields_{$plugin}",'custom_fields.php'));
                if (function_exists("{$plugin}_field_edit_form_definition")) {
                    call_user_func("{$plugin}_field_edit_form_definition", $form, array('onchange' => 'switchDefaultData();'));
                }
            }
        }

        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        // copied from /user/profile/definelib.php
        global $CFG, $USER, $DB;

        $err = array();

        /// Check the shortname was not truncated by cleaning
        if (empty($data['shortname'])) {
            $err['shortname'] = get_string('required');
        } else {
            /*
            /// Fetch field-record from DB
            $field = $DB->get_record(field::TABLE, array('shortname'=>$data['shortname']));
            /// Check the shortname is unique
            if ($field and $field->id != $data['id']) {
                $err['shortname'] = get_string('profileshortnamenotunique', 'admin');
            }
            */
        }

        $plugins = get_list_of_plugins('elis/core/fields');

        foreach ($plugins as $plugin) {
            if (is_readable(elis::plugin_file("elisfields_{$plugin}",'custom_fields.php'))) {
                include_once(elis::plugin_file("elisfields_{$plugin}",'custom_fields.php'));
                if (function_exists("{$plugin}_field_edit_form_validation")) {
                    $err += call_user_func("{$plugin}_field_edit_form_validation", $this, $data, $files);
                }
            }
        }

        /// No further checks necessary as the form class will take care of it
        return $err;
    }

    /**
     * Accessor for the inner quickform (needed since _form is now protected)
     *
     * @return  object  The inner quickform
     */
    function get_quickform() {
        return $this->_form;
    }
}
