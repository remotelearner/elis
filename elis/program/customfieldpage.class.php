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
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elis::lib('data/customfield.class.php');
require_once elispm::lib('page.class.php');
require_once elis::lib('table.class.php');

class customfieldpage extends pm_page {
    var $pagename = 'field';
    var $section = 'admn';

    var $params = array();

    function can_do_default() {
        $context = get_context_instance(CONTEXT_SYSTEM);
        return has_capability('block/curr_admin:managecurricula', $context);
    }

    function display_default() {
        global $CFG, $DB;
        $level = $this->required_param('level', PARAM_ACTION);
        $ctxlvl = context_level_base::get_custom_context_level($level, 'elis_program');
        if (!$ctxlvl) {
            print_error('invalid_context_level', 'elis_program');
        }

        $tmppage = new customfieldpage();
        $tabs = array();
        require $CFG->dirroot.'/elis/program/db/access.php';
        foreach($contextlevels as $contextlevel => $val) {
            $tmppage->params['level'] = $contextlevel;
            $tabs[] = new tabobject($contextlevel, $tmppage->url, get_string($contextlevel, 'elis_program'));
        }
        print_tabs(array($tabs), $level);

        $fields = field::get_for_context_level($ctxlvl);
        $fields = $fields ? $fields : array();

        $categories = field_category::get_for_context_level($ctxlvl);
        $categories = $categories ? $categories : array();

        // divide the fields into categories
        $fieldsbycategory = array();
        foreach ($categories as $category) {
            $fieldsbycategory[$category->id] = array();
        }
        foreach ($fields as $field) {
            $fieldsbycategory[$field->categoryid][] = $field;
        }

        $deletetxt = get_string('delete');
        $edittxt = get_string('edit');
        $syncerr = false;
        if (empty($categories)) {
            print_heading(get_string('field_no_categories_defined', 'elis_program'));
        }
        foreach ($fieldsbycategory as $categoryid => $fields) {
            $tmppage = new customfieldpage();
            $tmppage->params = array('action' => 'deletecategory',
                                     'id' => $categoryid,
                                     'level' => $level);
            $deletelink = $tmppage->get_url();
            $tmppage->params['action'] = 'editcategory';
            $editlink = $tmppage->get_url();
            echo "<h2>{$categories[$categoryid]->name} <a href=\"$editlink\">";
            echo "<img src=\"{$CFG->wwwroot}/elis/program/pix/edit.gif\" alt=\"$edittxt\" title=\"$edittxt\" /></a>";
            echo "<a href=\"$deletelink\"><img src=\"{$CFG->wwwroot}/elis/program/pix/delete.gif\" alt=\"$deletetxt\" title=\"$deletetxt\" /></a>";
            echo "</h2>\n";

            if (empty($fields)) {
                print_string('field_no_fields_defined', 'elis_program');
            } else {
                if ($level == 'user') {
                    require_once $CFG->wwwroot.'/elis/program/plugins/moodle_profile/custom_fields.php';
                    $table = new customuserfieldtable($fields, array('name' => get_string('name'),
                                                                     'datatype' => get_string('field_datatype', 'elis_program'),
                                                                     'syncwithmoodle' => get_string('field_syncwithmoodle', 'elis_program'),
                                                                     'buttons' => ''), $this->get_moodle_url(array('level' => $level)));
                } else {
                    $table = new customfieldtable($fields, array('name' => get_string('name'),
                                                                 'datatype' => 'Data type',
                                                                 'buttons' => ''), $this->get_moodle_url(array('level' => $level)));
                }
                $table->print_table();
                $syncerr = $syncerr || !empty($table->syncerr);
            }
        }
        if ($syncerr) {
            print_string('moodle_field_sync_warning', 'elis_program');
        }

        // button for new category
        $options = array('s' => 'field',
                         'action'=>'editcategory',
                         'level' => $level);
        //print_single_button('index.php', $options, get_string('field_create_category', 'elis_program'));
        $button = new single_button(new moodle_url('index.php', $options), get_string('field_create_category', 'elis_program'), 'get', array('disabled'=>false, 'title'=>get_string('field_create_category', 'elis_program'), 'id'=>''));
        echo $OUTPUT->render($button);

        if (!empty($categories)) {
            if ($level == 'user') {
                // create new field from Moodle field
                $select = 'shortname NOT IN (SELECT shortname FROM {'.field::TABLE.'})';
                $moodlefields = $DB->get_records_select('user_info_field', $select, 'sortorder', 'id,name');
                $moodlefields = $moodlefields ? $moodlefields : array();
                $tmppage->params['action'] = 'editfield';
                $tmppage->params['from'] = 'moodle';
                $tmppage->params['level'] = 'user';
                echo '<div>';
                popup_form("{$tmppage->url}&amp;id=",
                           array_map(create_function('$x', 'return $x->name;'), $moodlefields),
                           'frommoodleform', '', 'choose', '', '', false, 'self', get_string('field_from_moodle', 'elis_program'));
                echo '</div>';

                $options = array('s' => 'field',
                                 'action' => 'forceresync');
                //print_single_button('index.php', $options, get_string('field_force_resync', 'elis_program'));
                $button = new single_button(new moodle_url('index.php', $options), get_string('field_force_resync', 'elis_program'), 'get', array('disabled'=>false, 'title'=>get_string('field_force_resync', 'elis_program'), 'id'=>''));
                echo $OUTPUT->render($button);
            } else {
                // create new field from scratch
                $options = array('s' => 'field',
                                 'action'=>'editfield',
                                 'level' => $level);
                //print_single_button('index.php', $options, get_string('field_create_new', 'elis_program'));
                $button = new single_button(new moodle_url('index.php', $options), get_string('field_create_new', 'elis_program'), 'get', array('disabled'=>false, 'title'=>get_string('field_create_new', 'elis_program'), 'id'=>''));
                echo $OUTPUT->render($button);
            }
        }
    }

    function do_forceresync() {
        global $CFG;

        $confirm = $this->optional_param('confirm', 0, PARAM_INT);
        if (!$confirm) {
            notice_yesno(get_string('field_confirm_force_resync', 'elis_program'), 'index.php', 'index.php', array('s' => 'field', 'action' => 'forceresync', 'confirm' => 1), array('s' => 'field', 'level' => 'user'), 'post', 'get');
        } else {
            print_string('field_resyncing', 'elis_program');
            $ctxlvl = context_level_base::get_custom_context_level('user', 'elis_program');
            if (!$ctxlvl) {
                print_error('invalid_context_level', 'elis_program');
            }
            $fields = field::get_for_context_level($ctxlvl);
            $fields = $fields ? $fields : array();
            require_once $CFG->wwwroot.'/elis/program/plugins/moodle_profile/custom_fields.php';
            foreach ($fields as $field) {
                $fieldobj = new field($field);
                sync_profile_field_with_moodle($fieldobj);
            }
            $tmppage = new customfieldpage(array('level' => 'user'));
            redirect($tmppage->url, get_string('continue'));
        }
    }

    function display_editcategory() {
        global $CFG;

        require_once $CFG->wwwroot.'/elis/program/form/fieldcategoryform.class.php';
        $level = $this->required_param('level', PARAM_ACTION);
        $ctxlvl = context_level_base::get_custom_context_level($level, 'elis_program');
        if (!$ctxlvl) {
            print_error('invalid_context_level', 'elis_program');
        }
        $id = $this->optional_param('id', 0, PARAM_INT);
        $tmppage = new customfieldpage(array('level' => $level, 'id' => $id, 'action' => 'editcategory', 'level' => $level));
        $form = new fieldcategoryform($tmppage->url);
        if ($form->is_cancelled()) {
            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('edit_cancelled', 'elis_program'));
        } else if ($data = $form->get_data()) {
            $data->id = $id;
            $category = new field_category($data);
            if ($category->id) {
                $category->update();
            } else {
                $category->add();
                // assume each category only belongs to one context level (for now)
                $categorycontext = new field_category_contextlevel();
                $categorycontext->categoryid = $category->id;
                $categorycontext->contextlevel = $ctxlvl;
                $categorycontext->add();
            }
            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('field_category_saved', 'elis_program', $category));
        } else {
            if ($id) {
                $category = new field_category($id);
                $form->set_data($category);
            }
            $form->display();
        }
    }

    function display_deletecategory() {
        $id = $this->required_param('id', PARAM_INT);
        $level = $this->required_param('level', PARAM_ACTION);

        $category = new field_category($id);

        if (!$category->id) {
            print_error('invalid_category_id', 'elis_program');
        }

        $confirm = $this->optional_param('confirm', 0, PARAM_INT);
        if ($confirm) {
            $category->delete();
            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('field_category_deleted', 'elis_program', $category));
        } else {
            notice_yesno(get_string('confirm_delete_category', 'elis_program', $category),
                         'index.php', 'index.php',
                         array(
                             's' => $this->pagename,
                             'action' => 'deletecategory',
                             'id' => $id,
                             'confirm' => 1,
                             'level' => $level,
                             ),
                         array(
                             's' => $this->pagename,
                             'level' => $level,
                             ),
                         'POST', 'GET'
                );
        }
    }

    function do_movecategory() {
        // FIXME:
    }

    function display_editfield() {
        global $CFG, $DB;

        $level = $this->required_param('level', PARAM_ACTION);
        $ctxlvl = context_level_base::get_custom_context_level($level, 'elis_program');
        if (!$ctxlvl) {
            print_error('invalid_context_level', 'elis_program');
        }
        $id = $this->optional_param('id', NULL, PARAM_INT);

        require_once $CFG->wwwroot.'/elis/program/form/customfieldform.class.php';
        $tmppage = new customfieldpage(array('level' => $level, 'action' => 'editfield'), $this);
        $form = new customfieldform($tmppage->get_moodle_url(), $this);
        if ($form->is_cancelled()) {
            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('edit_cancelled', 'elis_program'));
        } else if ($data = $form->get_data()) {
            $field = new field($data);
            if ($id) {
                $field->id = $id;
                $field->update();
            } else {
                $field->add();
                // assume each field only belongs to one context level (for now)
                $fieldcontext = new field_contextlevel();
                $fieldcontext->fieldid = $field->id;
                $fieldcontext->contextlevel = $ctxlvl;
                $fieldcontext->add();
            }

            if (!empty($data->defaultdata)) {
                // save the default value
                $defaultdata = $data->defaultdata;
                if ($field->multivalued) {
                    // parse as a CSV string
                    // until we can use str_getcsv from PHP 5.3...
                    $temp=fopen("php://memory", "rw");
                    fwrite($temp, $defaultdata);
                    rewind($temp);
                    $defaultdata=fgetcsv($temp);
                    fclose($temp);
                }
                field_data::set_for_context_and_field(NULL, $field, $defaultdata);
            } else {
                if ($field->multivalued) {
                    field_data::set_for_context_and_field(NULL, $field, array());
                } else {
                    field_data::set_for_context_and_field(NULL, $field, NULL);
                }
            }

            $plugins = get_list_of_plugins('curriculum/plugins');
            foreach ($plugins as $plugin) {
                if (is_readable($CFG->wwwroot . '/elis/program/plugins/' . $plugin . '/custom_fields.php')) {
                    include_once($CFG->wwwroot . '/elis/program/plugins/' . $plugin . '/custom_fields.php');
                    if (function_exists("{$plugin}_field_save_form_data")) {
                        call_user_func("{$plugin}_field_save_form_data", $form, $field, $data);
                    }
                }
            }

            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('field_saved', 'elis_program', $field));
        } else {
            if (!empty($id)) {
                if ($this->optional_param('from', NULL, PARAM_CLEAN) == 'moodle' && $level == 'user') {
                    $moodlefield = $DB->get_record('user_info_field', 'id', $id);
                    if (!$moodlefield) {
                        print_error('invalid_field_id', 'elis_program');
                    }
                    unset($moodlefield->id);
                    $data_array = (array)$moodlefield;
                    $data_array['datatype'] = 'text';
                    $data_array['manual_field_control'] = $moodlefield->datatype;
                    switch ($moodlefield->datatype) {
                    case field::checkbox:
                        $data_array['datatype'] = 'bool';
                        break;
                    case field::menu:
                        $data_array['datatype'] = 'char';
                        $data_array['manual_field_options'] = $moodlefield->param1;
                        break;
                    case field::textarea:
                        $data_array['manual_field_columns'] = $moodlefield->param1;
                        $data_array['manual_field_rows'] = $moodlefield->param2;
                        break;
                    case field::text:
                        if ($moodlefield->param3) {
                            $data_array['manual_field_control'] = 'password';
                        }
                        $data_array['manual_field_columns'] = $moodlefield->param1;
                        $data_array['manual_field_maxlength'] = $moodlefield->param2;
                        break;
                    }
                } else {
                    $data = new field($id);
                    $data_array = $data->to_array();
                    $defaultdata = field_data::get_for_context_and_field(NULL, $data);
                    if (!empty($defaultdata)) {
                        if ($data->multivalued) {
                            $values = array();
                            // extract the data
                            foreach ($defaultdata as $data) {
                                $values[] = $data->data;
                            }
                            // represent as a CSV string
                            $fh=fopen("php://memory", "rw");
                            fputcsv($fh, $values);
                            rewind($fh);
                            $defaultdata=fgets($fh);
                            fclose($fh);
                        } else {
                            $defaultdata = current($defaultdata);
                            $defaultdata = $defaultdata->data;
                        }
                    }
                    $data_array['defaultdata'] = $defaultdata;

                    $plugins = get_list_of_plugins('elis/program/plugins');
                    foreach ($plugins as $plugin) {
                        if (is_readable($CFG->wwwroot . '/elis/program/plugins/' . $plugin . '/custom_fields.php')) {
                            include_once($CFG->wwwroot . '/elis/program/plugins/' . $plugin . '/custom_fields.php');
                            if (function_exists("{$plugin}_field_get_form_data")) {
                                $data_array += call_user_func("{$plugin}_field_get_form_data", $form, $data);
                            }
                        }
                    }
                }

                $form->set_data($data_array);
            }
            $form->display();
        }
    }

    function display_deletefield() {
        $level = $this->required_param('level', PARAM_ACTION);
        $id = $this->required_param('id', PARAM_INT);

        $field = new field($id);

        if (!$field->id) {
            print_error('invalid_field_id', 'elis_program');
        }

        $confirm = $this->optional_param('confirm', 0, PARAM_INT);
        if ($confirm) {
            $field->delete();
            $tmppage = new customfieldpage(array('level' => $level));
            redirect($tmppage->url, get_string('field_deleted', 'elis_program', $field));
        } else {
            notice_yesno(get_string('confirm_delete_field', 'elis_program', $field),
                         'index.php', 'index.php',
                         array(
                             's' => $this->pagename,
                             'action' => 'deletefield',
                             'id' => $id,
                             'confirm' => 1,
                             'level' => $level,
                             ),
                         array(
                             's' => $this->pagename,
                             'level' => $level,
                             ),
                         'POST', 'GET'
                );
        }
    }

    function action_movefield() {
        // FIXME:
    }
}

class customfieldtable extends display_table {
    function is_sortable_default() {
        return false;
    }

    function get_item_display_datatype($column, $item) {
        return get_string("field_datatype_{$item->datatype}", 'elis_program');
    }

    function get_item_display_buttons($column, $item) {
        global $CFG;
        $tmppage = new customfieldpage();
        $tmppage->params = array('action' => 'deletefield',
                                 'level' => $this->pageurl->params['level'],
                                 'id' => $item->id);
        $deletelink = $tmppage->url;
        $tmppage->params['action'] = 'editfield';
        $editlink = $tmppage->url;
        $deletetxt = get_string('delete');
        $edittxt = get_string('edit');
        return "<a href=\"$editlink\"><img src=\"{$CFG->wwwroot}/elis/program/pix/edit.gif\" alt=\"$edittxt\" title=\"$edittxt\" /></a> <a href=\"$deletelink\"><img src=\"{$CFG->wwwroot}/elis/program/pix/delete.gif\" alt=\"$deletetxt\" title=\"$deletetxt\" /></a>";
    }
}

class customuserfieldtable extends customfieldtable {
    var $syncerr = false;

    function get_item_display_name($column, $item) {
        return $item->name;
    }

    function get_item_display_syncwithmoodle($column, $item) {
        if ($item->syncwithmoodle === NULL) {
            return get_string('field_no_sync', 'elis_program');
        } elseif ($item->syncwithmoodle == cm_moodle_profile::sync_from_moodle) {
            $result = get_string('field_sync_from_moodle', 'elis_program');
        } else {
            $result = get_string('field_sync_to_moodle', 'elis_program');
        }
        if (empty($item->mfieldid)) {
            $this->syncerr = true;
            return "$result *";
        }
        return $result;
    }
}
