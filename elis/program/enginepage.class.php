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

define('ACTION_TYPE_TRACK', 1);
define('ACTION_TYPE_CLASS', 2);
define('ACTION_TYPE_PROFILE', 3);

require_once elispm::lib('data/resultsengine.class.php');
require_once elispm::lib('lib.php');
require_once elispm::lib('page.class.php');
require_once elispm::file('form/engineform.class.php');

abstract class enginepage extends pm_page {
    const LANG_FILE = 'pmplugins_results_engine';

    public $data_class = 'resultsengine';
    public $child_data_class = 'resultsengineaction';
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
     * Get the id of this results engine
     *
     * @return int The id of the results engine (0 if doesn't exist).
     */
    function get_engine_id() {
        $contextid  = $this->get_context()->id;
        $rid        = $this->optional_param('rid', 0, PARAM_INT);
        $obj        = $this->get_new_data_object($rid);

        if ($rid < 1) {
            $filter    = new field_filter('contextid', $contextid);
            $results   = $obj->find($filter);
            $rid = $results->current()->id;
        }
        return $rid;
    }

    /**
     * Get the action type.
     *
     * @param array $data An array of data values
     * @return int The action type value.
     * @uses $DB
     */
    function get_action_type() {
        global $DB;

        $type = optional_param('result_type_id', 0, PARAM_INT);

        // If a button hasn't been pressed we have to look in the db.
        if ($type == 0) {
            $params = array('resultengineid' => $this->get_engine_id());
            if (! $type = $DB->get_field('crlm_results_action', 'actiontype', $params, IGNORE_MULTIPLE)) {
                $type = ACTION_TYPE_TRACK;
            }
        }

        return $type;
    }

    /**
     * Return the engine form
     *
     * @return object The engine form
     */
    protected function get_engine_form($cache = '') {

        $known      = false;
        $contextid  = $this->get_context()->id;
        $id         = $this->optional_param('id', 0, PARAM_INT);
        $rid        = $this->get_engine_id();
        $obj        = $this->get_new_data_object($rid);
        $childobj   = $this->get_new_child_data_object($rid);

        $filter    = new field_filter('id', $rid);

        if ($obj->exists($filter)) {
            $obj->id = $rid;
            $obj->load();
            $known = true;
        }

        // Count actions types (if any)
        $filter         = new field_filter('resultengineid', $rid);
        $actioncount    = $childobj->count($filter);

        // Action type is needed because it helps to identify which form elements need
        // to be disabled
        if ($actioncount) {
            $data = $childobj->find($filter, array(), 0, 1);
            $data = $data->current();
        }


        $target    = $this->get_new_page(array('action' => 'edit', 'id' => $id), true);

        $obj->contextid = $contextid;

        $params = $obj->to_array();
        $params['id'] = $id;
        $params['rid'] = $rid;
        $params['courseid'] = $this->get_course_id();
        $params['contextid'] = $contextid;
        $params['enginetype'] = $this->type;
        $params['actiontype'] = $this->get_action_type();

        $params['cache'] = $cache;

        $form = new cmEngineForm($target->url, $params);
        $form->set_data($params);

        return $form;
    }

    /**
     * Get the page with tab definitions
     */
    function get_tab_page() {
        return $this->get_parent_page();
    }

    /**
     * Get the default pate title.
     */
    function get_page_title_default() {
        return print_context_name($this->get_context(), false);
    }

    /**
     * Build the default navigation bar.
     */
    function build_navbar_default() {

        //obtain the base of the navbar from the parent page class
        $parent_template = $this->get_parent_page()->get_new_page();
        $parent_template->build_navbar_view();
        $this->_navbar = $parent_template->navbar;

        //add a link to the first role screen where you select a role
        $id = $this->required_param('id', PARAM_INT);
        $page = $this->get_new_page(array('id' => $id), true);
        $this->navbar->add(get_string('results_engine', self::LANG_FILE), $page->url);
    }

    /**
     * Print the tabs
     */
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

        $type = $this->get_action_type();

        echo '
        <script type="text/javascript">
            $(function(){

                // Accordion
                $("#accordion").accordion({ header: "h3", active: '. intval($type - 1) .' });
                $("#accordion").accordion({ change:
                    function(event, ui) {
                        document.getElementById("result_type_id").value = (ui.options.active + 1);
                    }
                });

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
        $cache = $this->optional_param('actioncache', '', PARAM_SEQUENCE);

        $form = $this->get_engine_form($cache);

        if ($form->is_cancelled()) {
            $target = $this->get_new_page(array('action' => 'default', 'id' => $id), true);
            redirect($target->url);
            return;
        }

        $data           = $form->get_data();
        $childdata      = array();
        $newchilddata   = '';
        $actiontype     = '';



        if ($form->no_submit_button_pressed()) {

            $this->_form = $form;
            $this->display('edit');

        } elseif  ($data) {

            require_sesskey();

            if (array_key_exists('track_assignment', $data) or
                array_key_exists('class_assignment', $data) or
                array_key_exists('profile_assignment', $data)) {

                $this->_form = $form;
                $this->display('edit');

            } else {



                $obj       = $this->get_new_data_object($id);
                $obj->set_from_data($data);
                if ($data->rid > 0) {
                    $obj->id = $data->rid;
                } else {
                    unset($obj->id);
                }

                $obj->save();

                // Updating existing score ranges
                // Iterate through the data array until you find either a
                // track_ or class_ or profile_ prefix, then set the action type
                // and break out of the loop
                $type = $this->get_action_type();
                $data = (array) $data;

                // Iterate through the data array and update the existing score ranges submitted
                $this->save_existing_data_submitted($data, $type, $actiontype);

                // Save new score ranges submitted
                $this->save_new_data_submitted($data, $type, $actiontype, $obj->id);

                $target = $this->get_new_page(array('action' => 'default',
                                                    'id' => $id), false);
                redirect($target->url);
            }


        } else {
            $this->_form = $form;
            $this->display('edit');
        }
    }

    /**
     * TODO: document
     */
    protected function save_new_data_submitted($data, $type, $actiontype, $results_engine_id) {

        $savetype       = '';
        $instance       = array();
        $dataobj        = (object) $data;

        // Check for existing data regarding track/class/profile actions
        foreach($data as $key => $value) {

            if (empty($data[$key])) {
                // If value is empty then it must be an empyt score range row
                // because form validation will catch any incomplete rows
                continue;
            }

            // Check for existing track data in the form of track_<id>_...
            $pos = strpos($key, "{$type}_add_");
            $length = strlen("{$type}_add_");

            if (false !== $pos) {
                $lpos = strrpos($key, '_');
                $pos = strpos($key, '_', $length - 1);

                if (false !== $pos and
                    false !== $lpos) {

                    $length = $lpos - $pos - 1;
                    $instance_key = substr($key, $pos+1, $length);

                    if (is_numeric($instance_key) and
                       !array_key_exists($instance_key, $instance)) {

                        $instance[$instance_key] = '';
                        continue;
                    }
                }
            }
        }

        $updaterec = new stdClass();
        $field = '';
        $fieldvalue = '';

        switch ($actiontype) {
            case ACTION_TYPE_TRACK:
                $dataobj->actiontype = ACTION_TYPE_TRACK;
                $field = 'trackid';
                break;
            case ACTION_TYPE_CLASS:
                $field = 'classid';
                $dataobj->actiontype = ACTION_TYPE_CLASS;
                break;
            case ACTION_TYPE_PROFILE:
                //
                $dataobj->actiontype = ACTION_TYPE_PROFILE;
                break;
        }

        $field_map = array();
        $field_map['actiontype'] = 'actiontype';

        foreach ($instance as $recid => $dummy_val) {

            $updaterec = $this->get_new_child_data_object();
            $updaterec->resultengineid = $results_engine_id;

            $key = "{$type}_add_{$recid}_min";
            $field_map['minimum'] = $key;

            $key = "{$type}_add_{$recid}_max";
            $field_map['maximum'] = $key;

            $key = "{$type}_add_{$recid}_selected";
            $field_map[$field] = $key;

            if (empty($fieldvalue)) {
                // TODO profile field work
            }

            $updaterec->set_from_data($dataobj, true, $field_map);
            $updaterec->save();
        }


    }

    /**
     * This function check to see if existing track/class/profile record data
     * was submitted with the form; because the use are only submit only submit
     * track or class or profile data.  And returns an array with the type of
     * data (track/class/profile) and id values for existing records to be
     * updated
     *
     * @param obj $data - data from the submitted form
     * @return array - key -- type (either track/class/profile), ids (array
     * whose keys are existing record ids)\
     *
     * TODO: update documentation
     */
    protected function save_existing_data_submitted($data, $type, $actiontype) {
        $savetype       = '';
        $instance       = array();
        $dataobj        = (object) $data;

        // Check for existing data regarding track/class/profile actions
        foreach($data as $key => $value) {

            // Check for existing track data in the form of track_<id>_...
            $pos = strpos($key, "{$type}_");

            if (false !== $pos) {
                $pos = strpos($key, '_');
                $lpos = strrpos($key, '_');

                if (false !== $pos and
                    false !== $lpos) {

                    $length = $lpos - $pos - 1;
                    $instance_key = substr($key, $pos+1, $length);

                    if (is_numeric($instance_key) and
                       !array_key_exists($instance_key, $instance)) {

                        $instance[$instance_key] = '';
                        continue;
                    }
                }
            }
        }

        $updaterec = new stdClass();
        $field = '';
        $fieldvalue = '';

        switch ($actiontype) {
            case ACTION_TYPE_TRACK:
                $field = 'trackid';
                break;
            case ACTION_TYPE_CLASS:
                $field = 'classid';
                break;
            case ACTION_TYPE_PROFILE:
                //
                break;
        }

        $field_map = array();

        foreach ($instance as $recid => $dummy_val) {
            $updaterec = $this->get_new_child_data_object($recid);
            $key = "{$type}_{$recid}_min";
            $field_map['minimum'] = $key;

            $key = "{$type}_{$recid}_max";
            $field_map['maximum'] = $key;

            $key = "{$type}_{$recid}_selected";
            $field_map[$field] = $key;

            if (empty($fieldvalue)) {
                // TODO profile field work
            }

            $updaterec->set_from_data($dataobj, true, $field_map);
            $updaterec->save();
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
     * Returns a new instance of the child data object class this page manages.
     *
     * @param mixed $data Usually either the id or parameters for object, false for blank
     * @return object The data object pulled form the database if an id was passed
     */
    public function get_new_child_data_object($data=false) {
        return new $this->child_data_class($data);
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
