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

define('TRACK_ACTION_TYPE', 1);
define('CLASS_ACTION_TYPE', 2);
define('PROFILE_ACTIONE_TYPE', 3);

require_once elispm::lib('data/resultsengine.class.php');
require_once elispm::lib('lib.php');
require_once elispm::lib('page.class.php');
require_once elispm::file('form/engineform.class.php');

abstract class enginepage extends pm_page {
    const LANG_FILE = 'elis_program';

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
     * Return the engine form
     *
     * @return object The engine form
     */
    protected function get_engine_form() {



        $known      = false;
        $contextid  = $this->get_context()->id;
        $id         = $this->optional_param('id', 0, PARAM_INT);
        $rid        = $this->optional_param('rid', 0, PARAM_INT);
        $cache      = $this->optional_param('cache', 0, PARAM_TEXT);
        $type       = $this->optional_param('type', 0, PARAM_INT);
        $obj        = $this->get_new_data_object($rid);
        $childobj   = $this->get_new_child_data_object($rid);

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

        // Count actions
        $filter         = new field_filter('resultengineid', $rid);
        $actioncount    = $childobj->count($filter);
        $actiontype     = 0;

        if (!empty($actioncount)) {
           $childdata = $childobj->find($filter, array(), 1, 1);

            if (!empty($childdata)) {
                // TODO: set the action type
           }
        }

        $target    = $this->get_new_page(array('action' => 'edit', 'id' => $id), true);

        $obj->contextid = $contextid;

        $params = $obj->to_array();
        $params['rid'] = $rid;
        $params['courseid'] = $this->get_course_id();
        $params['contextid'] = $contextid;

        if (!empty($cache)) {
            $actiontype = $type;
            $cache = urldecode($cache);
        }


        $params['actiontype'] = $actiontype;
        $params['cache'] = $cache;

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

        $data           = $form->get_data();
        $childdata      = array();
        $newchilddata   = '';
        $actiontype     = '';


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

            // Adding a new track score range element
            if (array_key_exists('trk_assignment', $data)) {

                $newchilddata = $this->get_new_data_submited('track', $data);

//                print_object('newdata');
//                print_object($newchilddata);

                $actiontype = TRACK_ACTION_TYPE;

            } elseif (array_key_exists('cls_assignment', $data)) {
            } elseif (array_key_exists('pro_assignment', $data)) {
            } else {

                $childdata = $this->get_existing_data_submitted($data);

                if (empty($childdata)) {
                    // Check for new track data submitted
                }

                foreach ($childdata['ids'] as $recid => $dummy_value) {

                    // Update existing records one by one
                    $existingrecord = $this->get_new_child_data_object($recid);

                    // TODO: check for an empty $existing record

                    $existingrecord->minimum = $childdata['type'] . '_'. $recid . '_minimum';
                    $existingrecord->maximum = $childdata['type'] . '_'. $recid . '_maximum';

                    switch ($childdata['type']) {
                        case 'track':
                            $existingrecord->trackid = $childdata['type'] . '_'. $recid . '_selected';
                            break;
                        case 'class':
                            $existingrecord->classid = $childdata['type'] . '_'. $recid . '_selected';
                            break;
                        case 'profile':
                            // WIP
                            break;
                    }

                    $existingrecord->save();
                }
            }

            $target = $this->get_new_page(array('action' => 'default',
                                                'id' => $id,
                                                'cache' => urlencode($newchilddata),
                                                'type' => $actiontype), false);
            redirect($target->url);

//print_object('SUBMITTED DATA');
//print_object($data);
//print_object('SUBMITTED DATA END');

        } else {
            $this->_form = $form;
            $this->display('edit');
        }
    }

    /**
     * TODO: document
     */
    protected function get_new_data_submited($type, $data) {
        $newdata = '';

        foreach ($data as $key => $value) {

            $matchkey = "{$type}_add_";
            if (false !== strpos($key, $matchkey)) {
                // Lucky that the data array is sorted in the same order taht
                // fields are added to the form
                $newdata .= ',' . $value;
            }
        }

        $newdata = ltrim($newdata, ',');
        return $newdata;
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
     * whose keys are existing record ids)
     */
    protected function get_existing_data_submitted($data) {
        $savetype       = '';
        $savechilddata  = new stdClass();
        $instance       = array();

        // Check for existing data regarding track/class/profile actions
        foreach($data as $key => $value) {

            // Check for existing track data in the form of track_<id>_...
            $type = strpos($key, 'track_');

            if (false !== $type) {
                $pos = strpos($key, '_');
                $lpos = strrpos($key, '_');

                if (false !== $pos and
                    false !== $lpos) {

                    $length = $lpos - $pos;
                    $instance_key = substr($key, $pos, $length);

                    if (is_int($instance_key)) {
                        $savetype = 'track';
                        $instance[$instance_key] = 'track';
                        continue;
                    }
                }

            }

            // Check for existing track data in the form of class_<id>_...
            $type = strpos($key, 'class_');

            if (false !== $type) {
                $pos = strpos($key, '_');
                $lpos = strrpos($key, '_');

                if (false !== $pos and
                    false !== $lpos) {

                    $length = $lpos - $pos;
                    $instance_key = substr($key, $pos, $length);

                    if (is_int($instance_key)) {
                        $savetype = 'class';
                        $instance[$instance_key] = 'class';
                        continue;
                    }
                }

            }

        }

        return array('type' => $savetype, 'ids' => $instance);
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
