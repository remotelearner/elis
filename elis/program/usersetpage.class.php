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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(elispm::file('clustertrackpage.class.php'));
require_once(elispm::lib('managementpage.class.php'));
require_once(elispm::lib('data/clustercurriculum.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('contexts.php'));
require_once(elispm::file('form/usersetform.class.php'));
require_once(elis::plugin_file('usersetenrol_manual', 'usersetassignmentpage.class.php'));
/*
require_once (CURMAN_DIRLOCATION . '/lib/cluster.class.php');
require_once (CURMAN_DIRLOCATION . '/lib/managementpage.class.php');
require_once (CURMAN_DIRLOCATION . '/clustertrackpage.class.php');
require_once (CURMAN_DIRLOCATION . '/clustercurriculumpage.class.php');
require_once (CURMAN_DIRLOCATION . '/rolepage.class.php');
require_once (CURMAN_DIRLOCATION . '/lib/contexts.php');
*/

class usersetpage extends managementpage {
    var $data_class = 'userset';
    var $form_class = 'usersetform';
    var $pagename = 'clst';

    var $section = 'users';

    var $view_columns = array('name');

    static $contexts = array();

    static function get_contexts($capability) {
        if (!isset(self::$contexts[$capability])) {
            global $USER;
            self::$contexts[$capability] = get_contexts_by_capability_for_user('cluster', $capability, $USER->id);
        }
        return self::$contexts[$capability];
    }

    /**
     * Check the cached capabilities for the current cluster.
     */
    static function check_cached($capability, $id) {
        if (isset(self::$contexts[$capability])) {
            // we've already cached which contexts the user has
            // capabilities in
            $contexts = self::$contexts[$capability];
            return $contexts->context_allowed($id, 'cluster');
        }
        return null;
    }

    /**
     * Determines whether the current user is allowed to enrol users into the provided cluster
     *
     * @param   int      $clusterid  The id of the cluster we are checking permissions on
     *
     * @return  boolean              Whether the user is allowed to enrol users into the cluster
     *
     */
    static function can_enrol_into_cluster($clusterid) {
        global $USER;

        //check the standard capability
        if (self::_has_capability('block/curr_admin:cluster:enrol', $clusterid)) {
            return true;
        }

        $cluster = new userset($clusterid);
        if(!empty($cluster->parent)) {
            //check to see if the current user has the secondary capability anywhere up the cluster tree
            $contexts = pm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol_cluster_user', $USER->id);
            return $contexts->context_allowed($clusterid, 'cluster');
        }

        return false;
    }

    /**
     * Check if the user has the given capability for the requested cluster
     */
    function _has_capability($capability, $id = null) {
        $id = $id ? $id : $this->required_param('id', PARAM_INT);
        $cached = self::check_cached($capability, $id);
        if ($cached !== null) {
            return $cached;
        }
        $context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'elis_program'), $id);
        return has_capability($capability, $context);
    }

    public function _get_page_context() {
        $id = $this->optional_param('id', 0, PARAM_INT);

        if ($id) {
            return get_context_instance(context_level_base::get_custom_context_level('cluster', 'elis_program'), $id);
        } else {
            return parent::_get_page_context();
        }
    }

    public function __construct(array $params=null) {
        $this->tabs = array(
        array('tab_id' => 'view', 'page' => 'usersetpage', 'params' => array('action' => 'view'), 'name' => get_string('detail','elis_program'), 'showtab' => true),
        array('tab_id' => 'edit', 'page' => 'usersetpage', 'params' => array('action' => 'edit'), 'name' => get_string('edit','elis_program'), 'showtab' => true, 'showbutton' => true, 'image' => 'edit'),
        array('tab_id' => 'subclusters', 'page' => 'usersetpage', 'params' => array(), 'name' => get_string('usersubsets','elis_program'), 'showtab' => true),
        array('tab_id' => 'clustertrackpage', 'page' => 'clustertrackpage', 'name' => get_string('tracks','elis_program'), 'showtab' => true, 'showbutton' => true, 'image' => 'track'),
        array('tab_id' => 'clusteruserpage', 'page' => 'clusteruserpage', 'name' => get_string('users','elis_program'), 'showtab' => true, 'showbutton' => true, 'image' => 'user'),
        array('tab_id' => 'clustercurriculumpage', 'page' => 'clustercurriculumpage', 'name' => get_string('curricula','elis_program'), 'showtab' => true, 'showbutton' => true, 'image' => 'curriculum'),
        array('tab_id' => 'cluster_rolepage', 'page' => 'cluster_rolepage', 'name' => get_string('roles', 'role'), 'showtab' => true, 'showbutton' => false, 'image' => 'tag'),

        array('tab_id' => 'delete', 'page' => 'usersetpage', 'params' => array('action' => 'delete'), 'name' => get_string('delete_label','elis_program') , 'showbutton' => true, 'image' => 'delete'),
        );

        parent::__construct($params);
    }

    function can_do_view() {
        $id = $this->required_param('id', PARAM_INT);
        if ($this->_has_capability('block/curr_admin:cluster:view')) {
            return true;
        }

        /*
         * Start of cluster hierarchy extension
         */

        $viewable_clusters = cluster::get_viewable_clusters();
        return userset::exists(array(new usersubset_filter('id', new field_filter('id', $id)),
                                     new in_list_filter('id', $viewable_clusters)));

        /*
         * End of cluster hierarchy extension
         */
    }

    function can_do_edit() {
        return $this->_has_capability('block/curr_admin:cluster:edit');
    }

    function can_do_subcluster() {
        return $this->_has_capability('block/curr_admin:cluster:edit');
    }

    function can_do_delete() {
        return $this->_has_capability('block/curr_admin:cluster:delete');
    }

    function can_do_confirm() {
        return $this->can_do_delete();
    }

    function can_do_add() {
        $parent = ($this->optional_param('id', 0, PARAM_INT))
                ? $this->optional_param('id', 0, PARAM_INT)
                : $this->optional_param('parent', 0, PARAM_INT);

        if ($parent) {
            $level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $context = get_context_instance($level,$parent);
        } else {
            $context = get_context_instance(CONTEXT_SYSTEM);
        }

        return has_capability('block/curr_admin:cluster:create', $context);
    }

    /**
     * Dummy can_do method for viewing a curriculum report (needed for the
     * cluster tree parameter for reports)
     */
    function can_do_viewreport() {
        global $CFG, $DB;

        $id = $this->required_param('id', PARAM_INT);

        //needed for execution mode constants
        require_once($CFG->dirroot . '/blocks/php_report/php_report_base.php');

        //check if we're scheduling or viewing
        $execution_mode = $this->optional_param('execution_mode', php_report::EXECUTION_MODE_SCHEDULED, PARAM_INT);

        //check the correct capability
        $capability = ($execution_mode == php_report::EXECUTION_MODE_SCHEDULED) ? 'block/php_report:schedule' : 'block/php_report:view';
        if ($this->_has_capability($capability)) {
            return true;
        }

        /*
         * Start of cluster hierarchy extension
         */
        $viewable_clusters = cluster::get_viewable_clusters($capability);

        $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');

        //if the user has no additional access through parent clusters, then they can't view this cluster
        if (empty($viewable_clusters)) {
            return false;
        }

        $like_clause = $DB->sql_like('child_context.path', '?');
        $parent_path = sql_concat('parent_context.path', "'/%'");

        list($in_clause, $params) = $DB->get_in_or_equal($viewable_clusters);

        //determine if this cluster is the parent of some accessible child cluster
        $sql = "SELECT parent_context.instanceid
                FROM {context} parent_context
                JOIN {context} child_context
                  ON child_context.instanceid {$in_clause}
                  AND {$like_clause}
                  AND parent_context.contextlevel = {$cluster_context_level}
                  AND child_context.contextlevel = {$cluster_context_level}
                  AND parent_context.instanceid = {$id}";

        $params = array_merge($params, array($parent_path, $cluster_context_level, $cluster_context_level, $id));

        return $DB->record_exists_sql($sql, $params);

        /*
         * End of cluster hierarchy extension
         */
    }

    function can_do_default() {
        $parent = $this->optional_param('id', 0, PARAM_INT);
        if ($parent) {
            return $this->can_do_view();
        }
        $contexts = self::get_contexts('block/curr_admin:cluster:view');
        return !$contexts->is_empty();
    }

    function build_navbar_view() {
        // cluster name is already added by build_navbar_default, so don't
        // print it again
        return $this->build_navbar_default();
    }

    public function build_navbar_default() {
        global $CFG, $DB;

        parent::build_navbar_default();

        // add cluster hierarchy if cluster defined
        $id = $this->optional_param('id', 0, PARAM_INT);
        if ($id) {
            $level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $context = get_context_instance($level, $id);
            $ancestorids = substr(str_replace('/',',',$context->path),1);
            $sql = "SELECT cluster.*
                    FROM {context} ctx
                    JOIN {" . userset::TABLE . "} cluster ON ctx.instanceid = cluster.id
                   WHERE ctx.id IN ($ancestorids) AND ctx.contextlevel = $level
                   ORDER BY ctx.depth";
            $ancestors = $DB->get_recordset_sql($sql);
            foreach ($ancestors as $ancestor) {
                $url = $this->get_new_page(array('action' => 'view',
                                                 'id' => $ancestor->id), true)->url;
                $this->navbar->add($ancestor->name, $url);
            }
        }
    }

    public function get_navigation_view() {
        return $this->get_navigation_default();
    }

    function display_default() {
        global $OUTPUT;

        // Get parameters
        $sort         = optional_param('sort', 'name', PARAM_ALPHA);
        $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);

        $page         = optional_param('page', 0, PARAM_INT);
        $perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page

        $namesearch   = trim(optional_param('search', '', PARAM_TEXT));
        $alpha        = optional_param('alpha', '', PARAM_ALPHA);

        $parent = $this->optional_param('id', 0, PARAM_INT);
        $classification = $this->optional_param('classification', NULL, PARAM_SAFEDIR);

        if ($parent) {
            $this->print_tabs('subclusters', array('id' => $parent));
        }

        // Define columns
        $columns = array(
            'name' => array('header' => get_string('userset_name','elis_program')),
            'display' => array('header' => get_string('userset_description','elis_program')),
        );

        // set sorting
        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } else {
            $sort = 'defaultsortcolumn';
            $columns[$sort]['sortable'] = $dir;
        }

        $extrafilters = array('contexts' => self::get_contexts('block/curr_admin:cluster:view'),
                              'parent' => $parent,
                              'classification' => $classification);
        $items = cluster_get_listing($sort, $dir, $page*$perpage, $perpage, $namesearch, $alpha, $extrafilters);
        $numitems = cluster_count_records($namesearch, $alpha, $extrafilters);

        self::get_contexts('block/curr_admin:cluster:edit');
        self::get_contexts('block/curr_admin:cluster:delete');

        $this->print_list_view($items, $numitems, $columns, $filter=null, $alphaflag=true, $searchflag=true);

        if ($this->optional_param('id', 0, PARAM_INT)) {
            echo html_writer::start_tag('div', array('align' => 'center'));
            echo get_string('cluster_subcluster_prompt','elis_program') . ': ';
            $non_parent_clusters = cluster_get_possible_sub_clusters($this->optional_param('id', 0, PARAM_INT));
            $url = $this->get_new_page(array('action'=>'subcluster','id'=>$this->optional_param('id', 0, PARAM_INT)))->url . '&amp;subclusterid=';
            echo $OUTPUT->single_select($url, 'subclusterid', $non_parent_clusters);
            echo html_writer::end_tag('div');
        }
    }

    /**
     * Handler for the delete action.  Deletes the record identified by the
     * 'id' parameter, if the confirm parameter is set.
     *
     * Modified from the default handler to pass in whether or not subclusters
     * should be deleted or promoted.
     */
    public function do_delete() {
        global $CFG;

        if (!optional_param('confirm', 0, PARAM_INT)) {
            return $this->display('delete');
        }

        $deletesubs = optional_param('deletesubs', 0, PARAM_INT);

        require_sesskey();

        $id = required_param('id', PARAM_INT);

        $obj = $this->get_new_data_object($id);
        $obj->load(); // force load, so that the confirmation notice has something to display
        $obj->deletesubs = $deletesubs;
        $obj->delete();

        $returnurl = optional_param('return_url', null, PARAM_URL);
        if ($returnurl === null) {
            $target_page = $this->get_new_page(array(), true);
            $returnurl = $target_page->url;
        } else {
            $returnurl = $CFG->wwwroot.$returnurl;
        }

        redirect($returnurl, get_string('notice_'.get_class($obj).'_deleted', 'elis_program', $obj->to_object()));
    }

    /**
     * Handler for the confirm action.  Assigns a child cluster to specified cluster.
     */
    function do_subcluster() {
        global $CFG;

        $id = $this->required_param('id',PARAM_INT);
        $target_page = $this->get_new_page(array('id'=>$id));
        $sub_cluster_id = $this->required_param('subclusterid',PARAM_INT);

        $cluster = new userset($sub_cluster_id);
        $cluster->parent = $id;
        $cluster->update();

        redirect($target_page->url, get_string('cluster_assigned','elis_program'));
    }

    /**
     * Prints a deletion confirmation form.
     * @param $obj record whose deletion is being confirmed
     */
    function print_delete_form($obj) {
        global $DB;
        if (($count = userset::count(new field_filter('parent', $obj->id)))) {
            // cluster has sub-clusters, so ask the user if they want to
            // promote or delete the sub-clusters
            $a = new stdClass;
            $a->name = $obj;
            $a->subclusters = $count;
            $context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'elis_program'), $obj->id);
            $like = $DB->sql_like('path', '?');
            $a->descendants = $DB->count_records_select('context',$DB->sql_like('path', '?'), array("{$context->path}/%")) - $a->subclusters;
            print_string($a->descendants ? 'confirm_delete_with_subclusters_and_descendants' : 'confirm_delete_with_subclusters', 'elis_program', $a);
            $target = $this->get_new_page(array('action' => 'confirm'));
            $form = new usersetdeleteform($target->url, array('obj' => $obj, 'a' => $a));
            $form->display();
        } else {
            parent::print_delete_form($obj);
        }
    }

    /**
     * Prints the single-button form used to request the add action for a record type.
     */
    function print_add_button() {
        global $OUTPUT;

        if (!$this->can_do('add')) {
            return;
        }

        $options = array('action' => 'add');
        $parent = $this->optional_param('id', 0, PARAM_INT);
        if ($parent) {
            $options['parent'] = $parent;
        }
        $target_page = $this->get_new_page($options, true);
        $url = $target_page->url;

        echo html_writer::tag('div', $OUTPUT->single_button($url, get_string("add_{$this->data_class}",'elis_program'), 'get'), array('style' => 'text-align: center'));
    }

    function get_default_object_for_add() {
        $parent = $this->optional_param('parent', 0, PARAM_INT);
        if ($parent) {
            $obj = new stdClass;
            $obj->parent = $parent;
            if ($parent) {
                //require_once(elis::plugin_file('elisfields_cluster_classification','clusterclassification.class.php'));
                //require_once(elis::plugin_file('elisfields_cluster_classification','lib.php'));
                /*
                if ($classification = clusterclassification::get_for_cluster($parent)) {
                    $fieldname = 'field_'.CLUSTER_CLASSIFICATION_FIELD;
                    if ($classification->param_child_classification) {
                        $obj->$fieldname = $classification->param_child_classification;
                    } else {
                        $obj->$fieldname = $classification->shortname;
                    }

                    //default groups and groupings settings
                    if ($classification->param_autoenrol_groups) {
                        $obj->field_cluster_group = $classification->param_autoenrol_groups;
                    }
                    if ($classification->param_autoenrol_groupings) {
                        $obj->field_cluster_groupings = $classification->param_autoenrol_groupings;
                    }
                }
                */
            }
            return $obj;
        } else {
            return NULL;
        }
    }

    /**
     * Hook that gets called after a CM entity is added through this page
     * (Note: this function should only use the id field from the supplied cm entity
     *  as the rest of the data is not guaranteed to be there)
     *
     * @param  object  $cm_entity  The CM entity added
     */
    function after_cm_entity_add($cm_entity) {
        global $USER, $DB;

        //make sure a valid role is set
        if(!empty(elis::$config->elis_program->default_cluster_role_id)
           && $DB->record_exists('role', array('id' => elis::$config->elis_program->default_cluster_role_id))) {

            //get the context instance for capability checking
            $context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $context_instance = get_context_instance($context_level, $cm_entity->id);

            //assign the appropriate role if the user does not have the edit capability
            if(!has_capability('block/curr_admin:cluster:edit', $context_instance)) {
                role_assign(elis::$config->elis_program->default_cluster_role_id, $USER->id, 0, $context_instance->id);
            }
        }
    }

    /**
     * Specifies a unique shortname for the entity represented by
     * a page of this type, transforming the supplied value if necessary
     *
     * @param   string       $parent_path  Path of all parent elements, or the empty string
     *                                     if none
     * @param   string       $name         Initial name provided for the element
     *
     * @return  string|NULL                A valid name to identify the item with, or NULL if
     *                                     not applicable
     */
    static function get_entity_name($parent_path, $name) {
        $parts = explode('_', $name);

        //try to find the entity type and id, and combine them
        if (count($parts) == 2) {
            if ($parts[0] == 'cluster') {
                return $parts[0] . '-' . $parts[1];
            }
        }

        return NULL;
    }
}
?>
