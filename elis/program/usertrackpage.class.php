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
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') or die();

// Waiting on conversion?
//require_once (CURMAN_DIRLOCATION . '/lib/lib.php');

require_once(elispm::lib('data/track.class.php'));
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/usertrack.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('associationpage.class.php'));
require_once(elispm::lib('deepsightpage.class.php'));
require_once(elispm::lib('page.class.php'));
require_once(elispm::file('trackassignmentpage.class.php'));
require_once(elispm::file('trackpage.class.php'));
require_once(elispm::file('userpage.class.php'));
require_once(elispm::file('usertrackpage.class.php'));

class usertrackbasepage extends associationpage {

    var $data_class = 'usertrack';

    /**
     * @todo Refactor this once we have a common save() method for datarecord subclasses.
     */
    function do_savenew() {
        $trackid = $this->required_param('trackid', PARAM_INT);
        $userid = $this->required_param('userid', PARAM_INT);
        usertrack::enrol($userid, $trackid);

        $target = $this->get_new_page(array('action' => 'default', 'id' => $this->required_param('id', PARAM_INT)));
        redirect($target->url);
    }
}

class usertrackpage extends usertrackbasepage {
    var $pagename = 'usrtrk';
    var $tab_page = 'userpage';
    //var $default_tab = 'usertrackpage';

    var $section = 'users';

    function can_do_default() {
        $id = $this->required_param('id', PARAM_INT);
        // TODO: Ugly, this needs to be overhauled
        $upage = new userpage();
        return $upage->_has_capability('elis/program:user_view', $id);
    }

    function can_do_add() {
        $userid = $this->required_param('userid', PARAM_INT);
        $trackid = $this->required_param('trackid', PARAM_INT);
        return usertrack::can_manage_assoc($userid, $trackid);
    }

    function can_do_savenew() {
        return $this->can_do_add();
    }

    function can_do_delete() {
        $aid = $this->required_param('association_id');
        $usrtrk = new usertrack($aid);
        return usertrack::can_manage_assoc($usrtrk->userid, $usrtrk->trackid);
    }

    function can_do_confirm() {
        return $this->can_do_delete();
    }

    function display_default() {
        global $USER, $DB;

        $id           = required_param('id', PARAM_INT);
        $sort         = $this->optional_param('sort', 'idnumber', PARAM_ALPHANUM);
        $dir          = $this->optional_param('dir', 'ASC', PARAM_ALPHA);
        $contexts = clone(trackpage::get_contexts('elis/program:track_enrol'));

        //look up student's cluster assignments with necessary capability
        $cluster_contexts = pm_context_set::for_user_with_capability('cluster', 'elis/program:track_enrol_userset_user', $USER->id);

        //calculate our filter condition based on cluster accessibility
        //$cluster_filter = $cluster_contexts->sql_filter_for_context_level('clst.id', 'cluster');
        $filter_object = $cluster_contexts->get_filter('clst.id', 'cluster');
        $filter_sql = $filter_object->get_sql(false, 'clst');
        $cluster_filter = '';
        $params = array();
        if (isset($filter_sql['where'])) {
            $cluster_filter = " WHERE ".$filter_sql['where'];
            $params += $filter_sql['where_parameters'];
        }

        //query for getting tracks based on clusters
        $sql = 'SELECT trk.id
                FROM {'.userset::TABLE.'} clst
                JOIN {'.clustertrack::TABLE.'} clsttrk
                ON clst.id = clsttrk.clusterid
                JOIN {'.track::TABLE.'} trk
                ON clsttrk.trackid = trk.id
                '.$cluster_filter;

        //assign the appropriate track ids
        $recordset = $DB->get_recordset_sql($sql, $params);
        if($recordset && count($recordset) > 0) {
            if(!isset($contexts->contexts['track'])) {
                $contexts->contexts['track'] = array();
            }

            $new_tracks = array();
            //while($record = rs_fetch_next_record($recordset)) {
            foreach ($recordset as $record) {
                $new_tracks[] = $record->id;
            }

            $contexts->contexts['track'] = array_merge($contexts->contexts['track'], $new_tracks);
        }

        if (!empty($id)) {
            //print curriculum tabs if viewing from the curriculum view
            $userpage = new userpage(array('id' => $id));
            //$userpage->print_tabs('usertrackpage', array('id' => $id));
        }


        $columns = array(
            'idnumber'    => array('header'=> get_string('track_idnumber', 'elis_program'),
                                   'decorator' => array(new record_link_decorator('trackpage',
                                                                                  array('action'=>'view'),
                                                                                  'trackid'),
                                                        'decorate')),
            'name'        => array('header'=> get_string('track_name', 'elis_program'),
                                   'decorator' => array(new record_link_decorator('trackpage',
                                                                                  array('action'=>'view'),
                                                                                  'trackid'),
                                                        'decorate')),
            'numclasses'   => array('header'=> get_string('track_num_classes', 'elis_program')),
            'manage'      => array('header'=> ''),
        );

        // TBD
        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } else {
            $sort = 'idnumber';
            $columns[$sort]['sortable'] = $dir;
        }

        $items = usertrack::get_tracks($id, $sort, $dir);

        $this->print_list_view($items, $columns, 'tracks');

        //get the listing specifically for this user
        $this->print_dropdown(track_get_listing('name', 'ASC', 0, 0, '', '', 0, 0, $contexts, $id), $items, 'userid', 'trackid', 'savenew', 'idnumber');
    }

    /**
     * Handler for the confirm (confirm delete) action.  Tries to delete the object and then renders the appropriate page.
     *//*
    function do_delete() {
        $association_id = required_param('association_id', PARAM_INT);
        $id = required_param('id', PARAM_INT);

        $obj = new $this->data_class($association_id);
        $obj->delete();

        $target_page = $this->get_new_page(array('id'=> $id));

        redirect($target_page->url);
    }*/
}

/**
 * Deepsight assignment page for user <-> track associations.
 */
class trackuserpage extends deepsightpage {
    public $pagename = 'trkusr';
    public $section = 'curr';
    public $tab_page = 'trackpage';
    public $data_class = 'usertrack';
    public $parent_page;
    public $context;

    /**
     * Constructor
     */
    public function __construct(array $params = null) {
        $this->context = parent::_get_page_context();
        parent::__construct($params);
    }

    /**
     * Get the context of the current track.
     *
     * @return context_elis_track The current track context object.
     */
    protected function get_context() {
        if (!isset($this->context)) {
            $id = required_param('id', PARAM_INT);
            $this->context = context_elis_track::instance($id);
        }
        return $this->context;
    }

    /**
     * Construct the assigned datatable.
     *
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_assigned_table($uniqid = null) {
        global $DB;
        $trackid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=assigned&id='.$trackid;
        $table = new deepsight_datatable_trackassigned($DB, 'assigned', $endpoint, $uniqid);
        $table->set_trackid($trackid);
        return $table;
    }

    /**
     * Construct the unassigned datatable.
     *
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_unassigned_table($uniqid = null) {
        global $DB;
        $trackid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=unassigned&id='.$trackid;
        $table = new deepsight_datatable_trackavailable($DB, 'unassigned', $endpoint, $uniqid, $trackid);
        $table->set_trackid($trackid);
        return $table;
    }

    /**
     * Track assignment permission is handled at the action-object level.
     *
     * @return true
     */
    public function can_do_action_trackassign() {
        return true;
    }

    /**
     * Track unassignment permission is handled at the action-object level.
     *
     * @return true
     */
    public function can_do_action_trackunassign() {
        return true;
    }

    /**
     * Whether the user has access to see the main page (assigned list)
     *
     * @return bool Whether the user has access.
     */
    public function can_do_default() {
        $id = $this->required_param('id', PARAM_INT);
        $tpage = new trackpage();
        return $tpage->_has_capability('elis/program:track_view', $id);
    }

    /**
     * Determine whether the current user can enrol students into the class.
     *
     * @return bool Whether the user can enrol users into the class or not.
     */
    public function can_do_add() {
        $id = $this->required_param('id', PARAM_INT);
        return trackpage::can_enrol_into_track($id);
    }
}

/**
 * A base datatable object for track assignments.
 */
class deepsight_datatable_trackassignments extends deepsight_datatable_user {
    protected $trackid;

    /**
     * Sets the current track ID
     *
     * @param int $trackid The ID of the track to use.
     */
    public function set_trackid($trackid) {
        $this->trackid = (int)$trackid;
    }

    /**
     * Gets an array of javascript files needed for operation.
     *
     * @see deepsight_datatable::get_js_dependencies()
     */
    public function get_js_dependencies() {
        $deps = parent::get_js_dependencies();
        $deps[] = '/elis/program/lib/deepsight/js/actions/deepsight_action_confirm.js';
        return $deps;
    }

    /**
     * Get an array of options to pass to the deepsight_datatable javascript object.
     *
     * Enables drag and drop, and multiselect
     *
     * @return array An array of options, ready to be passed to $this->get_init_js()
     */
    public function get_table_js_opts() {
        $opts = parent::get_table_js_opts();
        $opts['dragdrop'] = true;
        $opts['multiselect'] = true;
        return $opts;
    }
}

/**
 * A datatable object for users assigned to the track.
 */
class deepsight_datatable_trackassigned extends deepsight_datatable_trackassignments {
    /**
     * Gets the unassignment action.
     *
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    public function get_actions() {
        $actions = parent::get_actions();
        $unassignaction = new deepsight_action_trackunassign($this->DB, 'trackunassign');
        $unassignaction->endpoint = (strpos($this->endpoint, '?') !== false)
                ? $this->endpoint.'&m=action'
                : $this->endpoint.'?m=action';
        array_unshift($actions, $unassignaction);
        return $actions;
    }

    /**
     * Adds the assignment table for this track.
     *
     * @param array $filters An array of active filters to use to determne join sql.
     * @return string A SQL string containing any JOINs needed for the full query.
     */
    protected function get_join_sql(array $filters=array()) {
        $joinsql = parent::get_join_sql($filters);
        $joinsql[] = 'JOIN {'.usertrack::TABLE.'} trkass ON trkass.trackid='.$this->trackid.' AND trkass.userid = element.id';
        return $joinsql;
    }
}

/**
 * A datatable for users not yet assigned to the track.
 */
class deepsight_datatable_trackavailable extends deepsight_datatable_trackassignments {
    /**
     * Gets the track assignment action.
     *
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    public function get_actions() {
        $actions = parent::get_actions();
        $assignaction = new deepsight_action_trackassign($this->DB, 'trackassign');
        $assignaction->endpoint = (strpos($this->endpoint, '?') !== false)
            ? $this->endpoint.'&m=action'
            : $this->endpoint.'?m=action';
        array_unshift($actions, $assignaction);
        return $actions;
    }

    /**
     * Adds the assignment table for this track.
     *
     * @param array $filters An array of active filters to use to determne join sql.
     * @return string A SQL string containing any JOINs needed for the full query.
     */
    protected function get_join_sql(array $filters=array()) {
        $joinsql = parent::get_join_sql($filters);
        $joinsql[] = 'LEFT JOIN {'.usertrack::TABLE.'} trkass ON trkass.trackid='.$this->trackid.' AND trkass.userid = element.id';
        return $joinsql;
    }

    /**
     * Removes instructors and waitlisted users, and adds permission limits, if applicable.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array An array consisting of the SQL WHERE clause, and the parameters for the SQL.
     */
    protected function get_filter_sql(array $filters) {
        global $USER;

        list($filtersql, $filterparams) = parent::get_filter_sql($filters);

        $additionalfilters = array();

        // Limit to users not currently assigned.
        $additionalfilters[] = 'trkass.userid IS NULL';

        // Permissions.
        $tpage = new trackpage();
        if (!$tpage->_has_capability('elis/program:track_enrol', $this->trackid)) {
            // Perform SQL filtering for the more "conditional" capability.
            // Get the context for the "indirect" capability.
            $context = pm_context_set::for_user_with_capability('cluster', 'elis/program:track_enrol_userset_user', $USER->id);

            // Get the clusters and check the context against them.
            $clusters = clustertrack::get_clusters($this->trackid);
            $allowedclusters = $context->get_allowed_instances($clusters, 'cluster', 'clusterid');

            if (empty($allowedclusters)) {
                $additionalfilters[] = '0=1';
            } else {
                $clusterfilter = 'SELECT userid FROM {'.clusterassignment::TABLE.'} WHERE clusterid IN (:clusterfilter)';
                $additionalfilters[] = 'AND element.id IN ('.$clusterfilter.')';
                $filterparams['clusterfilter'] = implode(',', $allowedclusters);
            }
        }

        // Add our additional filters.
        $filtersql = (!empty($filtersql))
            ? $filtersql.' AND '.implode(' AND ', $additionalfilters)
            : 'WHERE '.implode(' AND ', $additionalfilters);

        return array($filtersql, $filterparams);
    }
}

class trackuserpage_old extends usertrackbasepage {
    var $pagename = 'trkusr';
    var $tab_page = 'trackpage';
    //var $default_tab = 'trackuserpage';

    var $section = 'curr';

    function can_do_default() {
        $id = $this->required_param('id', PARAM_INT);
        // TODO: Ugly, this needs to be overhauled
        $tpage = new trackpage();
        return $tpage->_has_capability('elis/program:track_view', $id);
    }

    function can_do_add() {
        //note: actual permission checking happens in usertrackpopup.php
        $id = $this->required_param('id', PARAM_INT);
        return trackpage::can_enrol_into_track($id);
    }

    function can_do_delete() {
        global $USER;

        $association_id = $this->required_param('association_id', PARAM_INT);
        $usrtrk = new usertrack($association_id);

        return usertrack::can_manage_assoc($usrtrk->userid, $usrtrk->trackid);
    }
}
