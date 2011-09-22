<?php
/**
 * Common page class for role assignments
 *
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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::lib('data/resultsengine.class.php');
require_once elispm::lib('lib.php');
require_once elispm::lib('page.class.php');
require_once elispm::file('form/engineform.class.php');

abstract class enginepage extends pm_page {
    const LANG_FILE = 'elis_program';

    public $data_class = 'resultsengine';
    public $form_class = 'cmEngineForm';

    protected $parent_page;
    protected $section;
    protected $_form;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->section = $this->get_parent_page()->section;
    }

    abstract protected function get_context();

    abstract protected function get_parent_page();

    abstract protected function get_course_id();

    /**
     * Check if the user can edit
     *
     * @return bool True if the user has permission to use the default action
     */
    function can_do_edit() {
        return $this->can_do_default();
    }

    /**
     * Check if the user can do the default action
     *
     * @return bool True if the user has permission to use the default action
     */
    function can_do_default() {
        return has_capability('elis/program:'. $this->type .'_edit', $this->get_context());
    }

    /**
     * Return the engine form
     *
     * @return object The engine form
     */
    protected function get_engine_form() {

        $this->include_js();

        $known     = false;
        $contextid = $this->get_context()->id;
        $id        = $this->optional_param('id', 0, PARAM_INT);
        $rid       = $this->optional_param('rid', 0, PARAM_INT);
        $obj       = $this->get_new_data_object($rid);

        if ($rid < 1) {
            $filter    = new field_filter('contextid', $contextid);
            $results   = $obj->find($filter);
            $rid = $results->current()->id;
        }

        $filter    = new field_filter('id', $rid);

        if ($obj->exists($filter)) {
            $obj->id = $rid;
            $obj->load();
            $known = true;
        }

        $target    = $this->get_new_page(array('action' => 'edit', 'id' => $id), true);

        $obj->contextid = $contextid;

        $params = $obj->to_array();
        $params['rid'] = $rid;
        $params['courseid'] = $this->get_course_id();
        $params['contextid'] = $contextid;

        $form = new cmEngineForm($target->url, $params);
        $form->set_data($params);

        return $form;
    }

    function get_tab_page() {
        return $this->get_parent_page();
    }

    function get_page_title_default() {
        return print_context_name($this->get_context(), false);
    }

    function build_navbar_default() {
        global $DB;

        //obtain the base of the navbar from the parent page class
        $parent_template = $this->get_parent_page()->get_new_page();
        $parent_template->build_navbar_view();
        $this->_navbar = $parent_template->navbar;

        //add a link to the first role screen where you select a role
        $id = $this->required_param('id', PARAM_INT);
        $page = $this->get_new_page(array('id' => $id), true);
        $this->navbar->add(get_string('results_engine', self::LANG_FILE), $page->url);
    }

    function print_tabs() {
        $id = $this->required_param('id', PARAM_INT);
        $this->get_parent_page()->print_tabs(get_class($this), array('id' => $id));
    }

    /**
     * Return the page parameters for the page.  Used by the constructor for
     * calling $this->set_url().
     *
     * @return array
     */
    protected function _get_page_params() {
        $params = parent::_get_page_params();

        $id = $this->required_param('id', PARAM_INT);
        $params['id'] = $id;

        return $params;
    }

    /**
     * Display the default page
     */
    function display_default() {
        $this->display_edit();
    }

    /**
     * Display the edit page
     */
    function display_edit() {
        if (!isset($this->_form)) {
            throw new ErrorException('Display called before Do');
        }

        echo '
        <script type="text/javascript">
            $(function(){

                // Accordion
                $("#accordion").accordion({ header: "h3" });

            });
        </script>';

        $this->print_tabs();
        $this->_form->display();
    }

    /**
     * Do the default
     *
     * Set up the editing form before save.
     */
    function do_default() {
        $form = $this->get_engine_form();
        $this->_form = $form;
        $this->display('default');
    }

    /**
     * Process the edit
     */
    function do_edit() {
        $known = false;
        $id = $this->required_param('id', PARAM_INT);

        $form = $this->get_engine_form();

        if ($form->is_cancelled()) {
            $target = $this->get_new_page(array('action' => 'default', 'id' => $id), true);
            redirect($target->url);
            return;
        }

        $data = $form->get_data();
        if ($data) {
            require_sesskey();
            $obj       = $this->get_new_data_object($id);
            $obj->set_from_data($data);
            if ($data->rid > 0) {
                $obj->id = $data->rid;
            } else {
                unset($obj->id);
            }
            $obj->save();
            $target = $this->get_new_page(array('action' => 'default', 'id' => $id), true);
            redirect($target->url);
        } else {
            $this->_form = $form;
            $this->display('edit');
        }
    }

    /**
     * Returns a new instance of the data object class this page manages.
     *
     * @param mixed $data Usually either the id or parameters for object, false for blank
     * @return object The data object pulled form the database if an id was passed
     */
    public function get_new_data_object($data=false) {
        return new $this->data_class($data);
    }

    /**
     * Includes the required JavaScript/YUI files for DataTable and the show/hide buttons.
     * @uses $CFG
     * @uses $PAGE
     * @return none
     */
    function include_js() {
        global $CFG, $PAGE;

        //$PAGE->requires->yui2_lib(array('core', 'dom', 'event', 'widget'));

        $PAGE->requires->css('/elis/program/jquery-ui-1.8.16.custom.css', true);
        $PAGE->requires->js('/elis/program/js/jquery-1.6.2.min.js', true);
        $PAGE->requires->js('/elis/program/js/jquery-ui-1.8.16.custom.js', true);
        $PAGE->requires->js('/elis/program/js/resultsengineselector.js', true);

    }

}

/**
 * Engine page for courses
 *
 * @author Tyler Bannister <tyler.bannister@remote-learner.net>
 */
class course_enginepage extends enginepage {
    public $pagename = 'crsengine';
    public $type     = 'course';

    /**
     * Get context
     *
     * @return object The context
     */
    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('course', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    /**
     * Get the course id.
     *
     * @return int The course id
     */
    protected function get_course_id() {
        return $this->required_param('id', PARAM_INT);
    }

    /**
     * Get parent page object
     *
     * @return object An object of the same type as the parent page
     * @uses $CFG
     * @uses $CURMAN
     */
    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('coursepage.class.php');
            $id = $this->required_param('id', PARAM_INT);
            $this->parent_page = new coursepage(array('id' => $id,
                                                      'action' => 'view'));
        }
        return $this->parent_page;
    }

    /**
     * Check if the user can do the default action
     *
     * @return bool True if the user has permission to use the default action
     */
    function can_do_default() {
        return has_capability('elis/program:course_edit', $this->get_context());
    }
}

/**
 * Engine page for classes
 *
 * Classes have an extra form field that courses don't have.
 *
 * @author Tyler Bannister <tyler.bannister@remote-learner.net>
 */
class class_enginepage extends enginepage {
    public $pagename = 'clsengine';
    public $type     = 'class';

    /**
     * Get context
     *
     * @return object The context
     */
    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('class', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    /**
     * Get the course id.
     *
     * @return int The course id
     * @uses $DB
     */
    protected function get_course_id() {
        global $DB;

        $classid  = $this->required_param('id', PARAM_INT);
        $courseid = $DB->get_field('crlm_class', 'courseid', array('id' => $classid));
        return $courseid;
    }

    /**
     * Get parent page object
     *
     * @return object An object of the same type as the parent page
     * @uses $CFG
     * @uses $CURMAN
     */
    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('pmclasspage.class.php');
            $id = $this->required_param('id');
            $this->parent_page = new pmclasspage(array('id' => $id,
                                                       'action' => 'view'));
        }
        return $this->parent_page;
    }
}
