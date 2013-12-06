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
 * The renderer for configuration page for the version1elis export plugin.
 *
 * @package    rlipexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

class rlipexport_version1elis_renderer extends plugin_renderer_base {

    /**
     * Generate the HTML for the list of fieldsets.
     *
     * @param  array  $fieldsets  An array of fieldsets and their labels, in the form [fieldset]=>[label]
     *
     * @return string The HTML for the list of fieldsets
     */
    protected function generate_fieldset_list(array $fieldsets = array()) {
        $html = '';
        reset($fieldsets);
        $first_key = key($fieldsets);
        foreach ($fieldsets as $fieldset => $fieldset_label) {
            $fieldset_attrs = array(
                'class' => ($fieldset === $first_key) ? 'active' : '',
                'data-fieldset' => $fieldset,
                'data-fieldsetlabel' => $fieldset_label
            );
            $html .= html_writer::tag('li', $fieldset_label, $fieldset_attrs);
        }
        return $html;
    }

    /**
     * Generate the HTML for the list of available fields.
     *
     * @param  array  $fieldsets                 An array of fieldsets and their labels, in the form [fieldset]=>[label]
     * @param  array  $fields_by_fieldset        An array of field information, sorted by fieldset.
     *                                           In the form:
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               ),
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               )
     * @param  array  $active_fields_by_fieldset An array of active fields, indexed by fieldset/field combination.
     *                                           For example:
     *                                               [fieldset/field] => [header]
     *
     * @return sttring The HTML for the list of active fields.
     */
    protected function generate_available_field_list(array $fieldsets, array $fields_by_fieldset, array $active_fields_by_fieldset) {
        $html = '';
        $rename_default = get_string('rename', 'rlipexport_version1elis');
        foreach ($fields_by_fieldset as $fieldset => $fields) {
            $fieldset_label = (isset($fieldsets[$fieldset])) ? $fieldsets[$fieldset] : $fieldset;
            foreach ($fields as $field => $header) {
                $field_attrs = array(
                    'data-fieldset' => $fieldset,
                    'data-fieldsetlabel' => $fieldset_label,
                    'data-field' => $field,
                    'data-renamedefault' => $rename_default,
                    'class' => 'fieldset_'.$fieldset.' field_'.$field
                );
                if (isset($active_fields_by_fieldset[$fieldset.'/'.$field])) {
                    $field_attrs['class'] .= ' active';
                }

                $html .= html_writer::tag('li', $header, $field_attrs);
            }
        }
        return $html;
    }

    /**
     * Generate the HTML for the list of active fields.
     *
     * @param  array  $fieldsets                 An array of fieldsets and their labels, in the form [fieldset]=>[label]
     * @param  array  $fields_by_fieldset        An array of field information, sorted by fieldset.
     *                                           In the form:
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               ),
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               )
     * @param  array  $active_fields_by_fieldset An array of active fields, indexed by fieldset/field combination.
     *                                           For example:
     *                                               [fieldset/field] => [header]
     * @param  array  $overridden_names          An array of overridden names, indexed by fieldset/field combination.
     *
     * @return sttring The HTML for the list of active fields.
     */
    protected function generate_active_field_list(array $fieldsets, array $fields_by_fieldset, array $active_fields_by_fieldset,
                                                  array $overridden_names = array()) {
        $html = '';
        $rename_default = get_string('rename', 'rlipexport_version1elis');
        foreach ($active_fields_by_fieldset as $fieldsetfield => $header) {
            $fieldsetfield_parts = explode('/', $fieldsetfield, 2);
            $fieldset = $fieldsetfield_parts[0];
            $field = $fieldsetfield_parts[1];
            $fieldset_label = (isset($fieldsets[$fieldset])) ? $fieldsets[$fieldset] : $fieldset;

            $name_override = (isset($overridden_names[$fieldset.'/'.$field])) ? $overridden_names[$fieldset.'/'.$field] : '';
            $name_override_display = ($name_override !== '') ? $overridden_names[$fieldset.'/'.$field] : $rename_default;

            $field_attrs = array(
                'data-fieldset' => $fieldset,
                'data-fieldsetlabel' => $fieldset_label,
                'data-field' => $field,
                'class' => 'fieldset_'.$fieldset.' field_'.$field
            );
            $field_body = html_writer::tag('span', 'X', array('class' => 'remove'));
            $field_body .= html_writer::tag('span', $fieldset_label.': '.$header);

            // Field input.
            $field_input_attrs = array(
                'name' => 'fields[]',
                'type' => 'hidden',
                'value' => $fieldset.'/'.$field
            );
            $field_body .= html_writer::empty_tag('input', $field_input_attrs);

            $fieldname_attrs = array(
                'class' => 'fieldname',
                'name' => 'fieldnames[]',
                'type' => 'hidden',
                'value' => $name_override
            );
            $field_body .= html_writer::empty_tag('input', $fieldname_attrs);

            // Rename link.
            $rename_link_attrs = array('class' => 'rename', 'data-default' => $rename_default, 'href' => 'javascript:;');
            $field_body .= html_writer::tag('a', $name_override_display, $rename_link_attrs);

            $html .= html_writer::tag('li', $field_body, $field_attrs);
        }
        return $html;
    }

    /**
     * Performs page setup work needed on the page for configuring which profile
     * fields are part of the export
     *
     * @param string $baseurl The page's base url
     */
    public function page_setup($baseurl) {
        global $PAGE, $SITE;

        // Set up the basic page info.
        $PAGE->set_url($baseurl);
        $displaystring = get_string('configuretitle', 'rlipexport_version1elis');
        $PAGE->set_title($SITE->shortname.': '.$displaystring);
        $PAGE->set_heading($SITE->fullname);

        // Use the default admin layout.
        $PAGE->set_pagelayout('admin');
    }

    /**
     * Generate the HTML for the config page.
     *
     * @param  array  $fieldsets                 An array of fieldsets and their labels, in the form [fieldset]=>[label]
     * @param  array  $fields_by_fieldset        An array of field information, sorted by fieldset.
     *                                           In the form:
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               ),
     *                                               [fieldset] => array(
     *                                                   [field]=>[header]
     *                                               )
     * @param  array  $active_fields_by_fieldset An array of active fields, indexed by fieldset/field combination.
     *                                           For example:
     *                                               [fieldset/field] => [header]
     * @param  array  $overridden_names          An array of overridden names, indexed by fieldset/field combination.
     *
     * @return sttring The HTML for the config page.
     */
    public function display_config_ui(array $fieldsets, array $fields_by_fieldset, array $active_fields_by_fieldset,
                                      array $overridden_names = array()) {
        global $PAGE;

        $PAGE->requires->js('/blocks/rlip/exportplugins/version1elis/jquery-ui-1.10.2.custom.min.js');
        $PAGE->requires->js('/blocks/rlip/exportplugins/version1elis/config_fields.js');

        // Generate Fieldset LIs.
        $html_fieldsets = $this->generate_fieldset_list($fieldsets);
        $html_available_fields = $this->generate_available_field_list($fieldsets, $fields_by_fieldset, $active_fields_by_fieldset);
        $html_active_fields = $this->generate_active_field_list($fieldsets, $fields_by_fieldset, $active_fields_by_fieldset,
                                                                $overridden_names);

        $html = html_writer::start_tag('form', array('method' => 'post'));

        // Sesskey.
        $sesskey_attrs = array(
            'name' => 'sesskey',
            'type' => 'hidden',
            'value' => sesskey()
        );
        $html .= html_writer::empty_tag('input', $sesskey_attrs);

        // Header.
        $html .= html_writer::tag('h2', get_string('configuretitle', 'rlipexport_version1elis'), array('class' => 'config_header'));

        $html .= html_writer::start_tag('div', array('class' => 'config_form'));

        // Categories.
        $html .= html_writer::start_tag('div', array('class' => 'fieldsets'));
        $html .= html_writer::tag('h3', get_string('configheader_field_categories', 'rlipexport_version1elis'));
        $html .= html_writer::tag('ul', $html_fieldsets, array('class' => 'fieldsets'));
        $html .= html_writer::end_tag('div');

        // Available Fields.
        $html .= html_writer::start_tag('div', array('class' => 'available_fields'));
        $html .= html_writer::tag('h3', get_string('configheader_available_fields', 'rlipexport_version1elis'));
        $html .= html_writer::tag('ul', $html_available_fields, array('class' => 'fieldlist'));
        $html .= html_writer::end_tag('div');

        // Active Fields.
        $html .= html_writer::start_tag('div', array('class' => 'active_fields'));
        $html .= html_writer::tag('h3', get_string('configheader_active_fields', 'rlipexport_version1elis'));
        $html .= html_writer::tag('ul', $html_active_fields, array('class' => 'fieldlist'));
        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        // Hidden Inputs.
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'data_submitted', 'value' => 1));

        $attr = array('type' => 'submit', 'value' => get_string('updateheaders', 'rlipexport_version1elis'));
        $html .= html_writer::empty_tag('input', $attr);

        // End form.
        $html .= html_writer::end_tag('form');

        return $html;
    }
}