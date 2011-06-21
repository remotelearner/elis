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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elis::lib('table.class.php');
require_once elispm::lib('lib.php');
require_once elispm::lib('deprecatedlib.php'); // cm_error();
require_once elispm::lib('page.class.php');
//require_once elispm::lib('recordlinkformatter.class.php');

/**
 * This is the base class for a page that manages association data object types, for example
 * curriculumcourse or usertrack objects.  This is in contrast to the "managementpage" class, which
 * is used to manage the basic data objects such as user, track or curriculum.
 *
 * When subclassing, you must have a constructor which sets a number of instance variables that
 * define how the class operates.  See an existing subclass for an example.
 *
 */
class associationpage extends pm_page {
    const LANG_FILE = 'elis_program';
    /**
     * The name of the class used for data objects
     */
    var $data_class;

    /**
     * The name of the class used for the add/edit form
     */
    var $form_class;

    var $tabs;

    var $_form;

    public function can_do_default() {
        $context = get_context_instance(CONTEXT_SYSTEM);
        return has_capability('block/curr_admin:managecurricula', $context);
    }

    public function can_do_edit() { // can_do_update()
        return $this->can_do('edit');
    }

    public function can_do_savenew() {
        return $this->can_do('add');
    }

    public function can_do_delete() { // can_do_confirm()
        return $this->can_do('delete');
    }

    /**
     * Returns an instance of the page class that should provide the tabs for this association page.
     * This allows the association interface to be located "under" the general management interface for
     * the data object whose associations are being viewed or modified.
     *
     * @param $params
     * @return object
     */
    function get_tab_page($params=array()) {
        return new $this->tab_page($params);
    }

    function print_header() {
        $id = $this->required_param('id', PARAM_INT);
        $default_tab = empty($this->default_tab) ? 'view' : $this->default_tab; // TBD
        $action = $this->optional_param('action', $default_tab, PARAM_CLEAN);
        $association_id = $this->optional_param('association_id', 0, PARAM_INT);

        parent::print_header();
        $params = array('id' => $id);
        if (!empty($association_id)) {
            $params['association_id'] = $association_id;
        }
        //$this->get_tab_page()->print_tabs(get_class($this), array('id' => $id));
        $this->get_tab_page()->print_tabs($action, $params); // TBD
    }

    /**
     * Prints the tab bar describe by the $tabs instance variable.
     * - lifted from managmentpage.class.php
     * @param $selected name of tab to display as selected
     * @param $params extra parameters to insert into the tab links, such as an id
     */
    function print_tabs($selected, $params=array()) {
        $row = array();

        foreach($this->tabs as $tab) {
            $tab = $this->add_defaults_to_tab($tab);
            if($tab['showtab'] === true) {
                $target = new $tab['page'](array_merge($tab['params'], $params));
                if (!$target->can_do()) {
                    continue;
                }
                $row[] = new tabobject($tab['tab_id'], $target->url, $tab['name']);
            }
        }
        print_tabs(array($row), $selected);
    }

    function get_new_data_object($id=false) {
        return new $this->data_class($id);
    }

    function print_add_button($params=array(), $text=null) {
        $obj = $this->get_new_data_object();

        echo '<div align="center">';
        $options = array_merge(array('s' => $this->pagename, 'action' => 'add'), $params);
        $dellabel = get_string('delete_label', self::LANG_FILE);
        $objlabel = get_string($obj->get_verbose_name(), self::LANG_FILE); // TBD
        echo print_single_button('index.php', $options, $text ? $text : $dellabel .' ' . $objlabel, 'get', '_self', true, $text ? $text : $dellabel .' ' . $objlabel);
        echo '</div>';
    }

    /**
     * Generic handler for the add action.  Prints the add form.
     */
    function display_add() { // do_add()
        $id = $this->required_param('id', PARAM_INT);
        $parent_obj = new $this->parent_data_class($id);
        $this->print_add_form($parent_obj);
    }

    function do_add() {
        $target = $this->get_new_page(array('action' => 'add'), true);
        $obj = NULL; // $this->get_default_object_for_add();
        $form = new $this->form_class($target->url, $obj ? array('obj' => $obj) : NULL);

        if ($form->is_cancelled()) {
            $target = $this->get_new_page(array(), true);
            redirect($target->url);
            return;
        }

        $data = $form->get_data();

        if($data) {
            require_sesskey();
            $obj = $this->get_new_data_object();
            $obj->set_from_data($data);
            $obj->save();
            $this->after_cm_entity_add($obj);
            $target = $this->get_new_page(array('action' => 'view', 'id' => $obj->id), true);
            redirect($target->url);
        } else {
            $this->_form = $form;
            $this->display('add');
        }
    }

    /**
     * Prints the add form.
     * @param $parent_obj is the basic data object we are forming an association with.
     */
    function print_add_form($parent_obj) {
        $id = $this->required_param('id', PARAM_INT);
        $target = $this->get_new_page(array('action' => 'savenew', 'id' => $id));
        $form = new $this->form_class($target->url, array('parent_obj' => $parent_obj));
        $form->set_data(array('id' => $id));
        $form->display();
    }

    /**
     * Generic handler for the edit action.  Prints the edit form.
     */
    function display_edit() { // do_edit()
        $association_id = $this->required_param('association_id', PARAM_INT);
        $id             = $this->required_param('id', PARAM_INT);
        $obj            = new $this->data_class($association_id);
        $parent_obj     = new $this->parent_data_class($id);

        if(!$obj->get_dbloaded()) { // TBD
            $sparam = new stdClass;
            $sparam->id = $id;
            print_error('invalid_objectid', 'elis_program', '', $sparam);
        }
        $this->print_edit_form($obj, $parent_obj);
    }

    /**
     * Generic handler for the edit action.  Prints the form for editing an
     * existing record, or updates the record.
     */
    function do_edit() {
        $association_id = $this->required_param('association_id', PARAM_INT);

        $target = $this->get_new_page(array('action' => 'edit', 'association_id' => $association_id), true);
        $obj = $this->get_new_data_object($association_id);
        $id = $this->required_param('id', PARAM_INT);

        $obj->load();

        $form = new $this->form_class($target->url, array('obj' => $obj->to_object()));

        if ($form->is_cancelled()) {
            $target = $this->get_new_page(array('action' => 'view', 'id' => $id), true);
            redirect($target->url);
            return;
        }

        $data = $form->get_data();

        if($data) {
            require_sesskey();

            $obj->set_from_data($data);
            $obj->save();
            $target = $this->get_new_page(array('action' => 'view', 'id' => $id), true);
            redirect($target->url);
        } else {
            $this->_form = $form;
            $this->display('edit');
        }
    }

    /**
     * Prints the form to edit a new record.
     */
    //My version of display_edit
    /*
    public function display_edit() {
        if (!isset($this->_form)) {
            throw new ErrorException('Display called before Do');
        }
        $this->_form->display();
    }*/

    /**
     * Prints the edit form.
     * @param $obj The association object being edited.
     * @param $parent_obj The basic data object being associated with.
     */
    function print_edit_form($obj, $parent_obj) {
        $parent_id = $this->required_param('id', PARAM_INT);

        $target = $this->get_new_page(array('action' => 'update', 'id' => $parent_id, 'association_id' => $obj->id));

        $form = new $this->form_class($target->url, array('obj' => $obj, 'parent_obj' => $parent_obj));

        $form->display();
    }

    /**
     * Generic handler for the savenew action.  Tries to save the object and then prints the appropriate page.
     */
    function display_savenew() { // TBD: do_savenew() ?
        $parent_id = $this->required_param('id', PARAM_INT);
        $parent_obj = new $this->parent_data_class($parent_id);
        $target = $this->get_new_page(array('action' => 'savenew', 'id' => $parent_id));

        $form = new $this->form_class($target->url, array('parent_obj' => $parent_obj));

        if ($form->is_cancelled()) {
            $this->display('default'); // do_default()
            return;
        }

        $data = $form->get_data();

        if($data) {
            $obj = new $this->data_class();
            $obj->set_from_data($data);
            $obj->save();
            $target = $this->get_new_page(array('action' => 'default', 'id' => $parent_id));
            redirect($target->url, ucwords($obj->get_verbose_name())  . ' ' . $obj->__toString() . ' saved.');
        } else {
            // Validation must have failed, redisplay form
            $form->display();
        }
    }

    /**
     * Generic handler for the update action.  Tries to update the object and then prints the appropriate page.
     */
    function display_update() { // do_update()
        $parent_id = $this->required_param('id', PARAM_INT);
        $parent_obj = new $this->parent_data_class($parent_id);

        $association_id = $this->required_param('association_id', PARAM_INT);
        $obj = new $this->data_class($association_id);

        $target = $this->get_new_page(array('action' => 'update'));


        $form = new $this->form_class($target->url, array('obj' => $obj, 'parent_obj' => $parent_obj));

        if ($form->is_cancelled()) {
            $this->display('default'); // do_default()
            return;
        }

        $data = $form->get_data();

        if($data) {
            $obj->set_from_data($data);
            $obj->save();
            $target = $this->get_new_page(array('action' => 'default', 'id' => $parent_id));
            redirect($target->url, ucwords($obj->get_verbose_name())  . ' ' . $obj->__toString() . ' updated.');
        } else {
            // Validation must have failed, redisplay form
            $form->display();
        }
    }

    public function get_page_title_default() {
        return get_string('breadcrumb_' . get_class($this), self::LANG_FILE);
    }

    public function get_title_default() {
        return $this->get_page_title_default();
    }

    public function build_navbar_default() { // build_navigation_default
        parent::build_navbar_default();
        $uparams = array('s' => $this->pagename);
        if (($id = $this->optional_param('id', null, PARAM_INT)) != null) {
            $uparams['id'] = $id;
        }
        $url = new moodle_url($this->_get_page_url(), $uparams); // TBD: $_GET?
        $this->navbar->add(get_string("association_{$this->data_class}",
                                      self::LANG_FILE), $url); // TBD
        // arg1 was: $this->get_tab_page()->build_navigation_view()
    }

    /**
     * Generic handler for the delete action.  Prints the delete confirmation form.
     */
    public function display_delete() {
        $association_id = $this->required_param('association_id', PARAM_INT);
        if (empty($association_id)) {
            print_error('invalid_id');
        }

        $obj = $this->get_new_data_object($association_id);
        $this->print_delete_form($obj);
    }

    /**
     * Prints the delete confirmation form.
     * @param $obj Basic data object being associated with.
     */
    public function print_delete_form($obj) {
        global $OUTPUT;

        $obj->load(); // force load, so that the confirmation notice has something to display
        $message    = get_string('confirm_delete_association', 'elis_program', $obj->to_object());

        $target_page = $this->get_new_page(array('action' => 'view', 'id' => $obj->id, 'sesskey' => sesskey()), true);
        $no_url = $target_page->url;
        $no = new single_button($no_url, get_string('no'), 'get');

        $optionsyes = array('action' => 'delete', 'id' => $obj->id, 'confirm' => 1);
        $yes_url = clone($no_url);
        $yes_url->params($optionsyes);
        $yes = new single_button($yes_url, get_string('yes'), 'get');

        echo $OUTPUT->confirm($message, $yes, $no);
    }

/**
     * Generic handler for the confirm (confirm delete) action.  Tries to delete the object and then renders the appropriate page.
     */
    public function do_delete() {
        global $CFG;

        if (!$this->optional_param('confirm', 0, PARAM_INT)) {
            return $this->display('delete');
        }

        require_sesskey();

        $id = $this->required_param('id', PARAM_INT);

        $obj = $this->get_new_data_object($id);
        $obj->load(); // force load, so that the confirmation notice has something to display
        $obj->delete();

        $returnurl = $this->optional_param('return_url', null, PARAM_URL);
        if ($returnurl === null) {
            $target_page = $this->get_new_page(array(), true);
            $returnurl = $target_page->url;
        } else {
            $returnurl = $CFG->wwwroot.$returnurl;
        }

        //TODO: not returning something valid here... needs id=1
        redirect($returnurl, get_string('notice_'.get_class($obj).'_deleted', 'elis_program', $obj->to_object()));
    }

    /**
     * Prints out the page that displays a list of records returned from a query.
     * @param $items array of records to print
     * @param $columns associative array of column id => column heading text
     */
    function print_list_view($items, $columns) { // TBD
        global $CFG;

        $id = $this->required_param('id', PARAM_INT);

        if (empty($items)) {
            echo '<div>' . get_string('none', self::LANG_FILE) . '</div>';
            return;
        }

        $table = $this->create_table_object($items, $columns);
        echo $table;
    }

    /**
     * Creates a new table object with specified $items and $columns.
     * @param array $items
     * @param array $columns
     */
    function create_table_object($items, $columns) {
        return new association_page_table($items, $columns, $this);
    }

    /**
     * Prints out the item count for the list interface with the appropriate formatting.
     * @param $numitems number of items
     */
    function print_num_items($numitems) {
        $a = new stdClass;
        $a->num = $numitems;
        echo '<div style="float:right;">' . get_string('items_found', self::LANG_FILE, $a) . '</div>';
    }

    /**
     * Prints the dropdown for adding a new association with the basic object currently being managed.
     * @param $items complete list of possible objects to associate with
     * @param $taken_items list of objects that are already associated with
     * @param $local_key property name in the association class that stores the ID of the basic object being managed
     * @param $nonlocal_key property name in the association class that stores the ID of the basic object being associated with
     * @param $action action to request when an item is selected from the dropdown
     * @param $namefield property name in the class to associate with that should be displayed in the dropdown rows
     */
    function print_dropdown($items, $taken_items, $local_key, $nonlocal_key, $action='savenew', $namefield='name') {
        global $OUTPUT;

        $id = $this->required_param('id', PARAM_INT);

        // As most of the listing functions return 'false' if there are no records.
        $taken_items = $taken_items ? $taken_items : array();

        $taken_ids = array();
        foreach($taken_items as $taken_item) {
            $taken_ids[] = $taken_item->$nonlocal_key;
            //$taken_ids[] = $taken_item->id;
        }

        if (!($avail = $items)) {
            $avail = array();
        }

        $menu = array();

        foreach ($avail as $info) {
            if (!in_array($info->id, $taken_ids)) {
                $menu[$info->id] = $info->$namefield;
            }
        }

        echo '<div align="center"><br />';

        if (!empty($menu)) {
            // TODO: use something that doesn't require this append-to-url-string approach
            // Double use of $id is to keep idea of foreign key for association and parent object separate
            $url = $this->get_new_page(array('action' => $action, $local_key => $id, 'id' => $id))->url;
            $actionurl = new moodle_url($url, array('sesskey'=>sesskey()));
            $select = new single_select($actionurl, $nonlocal_key, $menu, null, array(''=>get_string('adddots')));
            echo $OUTPUT->render($select);
        } else {
            echo get_string('all_items_assigned', self::LANG_FILE);
        }
        echo '</div>';
    }

    /**
     * Inserts default values into the tabs array provided by the page class.
     * @param $tab tab to set the defaults for
     */
    function add_defaults_to_tab($tab) {
        $defaults = array('params' => array(), 'showbutton' => false, 'showtab' => 'false', 'image' => '');

        return array_merge($defaults, $tab);
    }

    /**
     * Generates the HTML for the management buttons (such as edit and delete) for a record's row in the table.
     * @param $params extra parameters to pass through the buttons, such as a record id
     * @uses $OUTPUT
     */
    function get_buttons($params) {
        global $OUTPUT;
        // TODO: function copied from managementpage.class.php.  Refactor at some point.
        $buttons = '';

        foreach($this->tabs as $tab) {
            $tab = $this->add_defaults_to_tab($tab);
            if($tab['showbutton'] === true) {
                $target = new $tab['page'](array_merge($tab['params'], $params));

                if ($target->can_do()) {
                    $buttons .= '<a href="'. $target->url .'"><img title="' . $tab['name'] . '" alt="' . $tab['name'] . '" src="'. $OUTPUT->pix_url($tab['image']) .'" /></a> ';
                }
            }
        }

        return $buttons;
    }

    /**
     * Prints the 'All A B C ...' alphabetical filter bar.
     */
    function print_alpha() {
        pmalphabox($this->url);
    }

    /**
     * Prints the text substring search interface.
     */
    function print_search() {
        pmsearchbox($this);
    }
}

class association_page_table extends display_table {
    function __construct(&$items, $columns, $page) {
        $this->page = $page;
        if (isset($columns['buttons']) && is_array($columns['buttons'])) {
            $columns['buttons']['display_function'] = array(&$this, 'get_item_display_buttons');
        }
        if (isset($columns['manage']) && is_array($columns['manage'])) {
            $columns['manage']['display_function'] = array(&$this, 'get_item_display_manage');
        }
        parent::__construct($items, $columns, $page->url);
    }

    function get_column_align_buttons() {
        return 'center';
    }

    function is_column_wrapped_buttons() {
        return false;
    }

    function get_item_display_buttons($column, $item) {
        $id = $this->required_param('id', PARAM_INT);
        return $this->page->get_buttons(array('id' => $id, 'association_id' => $item->id));
    }

    function get_item_display_manage($column, $item) {
        global $OUTPUT;
        $id = $this->required_param('id', PARAM_INT);
        $target = $this->page->get_new_page(array('action' => 'delete', 'association_id' => $item->id, 'id' => $id));
        if ($target->can_do('delete')) {
            $deletebutton = '<a href="'. $target->url .'">'.
                '<img src="'. $OUTPUT->pix_url('delete') .'" alt="Unenrol" title="Unenrol" /></a>';
        } else {
            $deletebutton = '';
        }
        return $deletebutton;
    }
}

