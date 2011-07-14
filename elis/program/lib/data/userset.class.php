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

require_once(elis::lib('data/data_object_with_custom_fields.class.php'));
require_once(elispm::lib('contexts.php'));
require_once(elispm::lib('data/clusterassignment.class.php'));
require_once(elispm::lib('data/clustercurriculum.class.php'));
/*
require_once(CURMAN_DIRLOCATION . '/lib/usercluster.class.php');
require_once(CURMAN_DIRLOCATION . '/lib/clusterassignment.class.php');
require_once CURMAN_DIRLOCATION . '/lib/clustercurriculum.class.php';
*/

define ('CLSTPROFTABLE', 'crlm_cluster_profile');

class userset extends data_object_with_custom_fields {
    const TABLE = 'crlm_cluster';

    public $verbose_name = 'cluster';

    protected $_dbfield_name;
    protected $_dbfield_display;
    protected $_dbfield_parent;
    protected $_dbfield_depth;

    static $associations = array(
        'parentset' => array(
            'class' => 'userset',
            'idfield' => 'parent'
        ),
    );

    const ENROL_PLUGIN_TYPE = 'usersetenrol';

    /**
     * Constructor.
     *
     * @param $clusterdata int/object/array The data id of a data record or data elements to load manually.
     *
     */
    /*
    function cluster($clusterdata=false) {
        global $CURMAN;

        parent::datarecord();

        $this->set_table(CLSTTABLE);
        $this->add_property('id', 'int');
        $this->add_property('name', 'string');
        $this->add_property('display', 'string');
        $this->add_property('leader', 'int');
        $this->add_property('parent', 'int');
        $this->add_property('depth', 'int');

        if (is_numeric($clusterdata)) {
            $this->data_load_record($clusterdata);
        } else if (is_array($clusterdata)) {
            $this->data_load_array($clusterdata);
        } else if (is_object($clusterdata)) {
            $this->data_load_array(get_object_vars($clusterdata));
        }

        if (!empty($this->id)) {
            // custom fields
            $level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            if ($level) {
                $fielddata = field_data::get_for_context(get_context_instance($level,$this->id));
                $fielddata = $fielddata ? $fielddata : array();
                foreach ($fielddata as $name => $value) {
                    $this->{"field_{$name}"} = $value;
                }
            }
        }

/*
 * profile_field1 -select box
 * profile_value1 -select box corresponding to profile_field1
 *
 * profile_field2 -select box
 * profile_value2 -select box corresponding to profile_field2
 * /
        $prof_fields = $CURMAN->db->get_records(CLSTPROFTABLE, 'clusterid', $this->id, '', '*', 0, 2);

        if(!empty($prof_fields)) {
            foreach(range(1,2) as $i) {
                $profile = pos($prof_fields);

                if(!empty($profile)) {
                    $field = 'profile_field' . $i;
                    $value = 'profile_value' . $i;

                    $this->$field = $profile->fieldid;
                    $this->$value = $profile->value;
                }

                next($prof_fields);
            }
        }
    }
    */

    protected function get_field_context_level() {
        return context_level_base::get_custom_context_level('cluster', 'elis_program');
    }

    /**
     * @todo move out
     */
    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }


    static $delete_is_complex = true;

    public $deletesimple = false;
    public $deletesubs = false;

    /**
     * Perform the necessary actions required to "delete" a cluster from the system.
     *
     * @param none
     * @return bool True on success, False otherwise.
     */
    function delete() {
        require_once elis::lib('data/data_filter.class.php');

        $plugins = get_plugin_list(self::ENROL_PLUGIN_TYPE);
        foreach ($plugins as $plugin => $plugindir) {
            require_once(elis::plugin_file(self::ENROL_PLUGIN_TYPE.'_'.$plugin, 'lib.php'));
        }

        if ($this->deletesimple) {
            return parent::delete();
        }

        $result = true;
        $children = array();
        $delete_ids = array();
        $promote_ids = array();
        $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');

        /// Figure out all the sub-clusters
        $cluster_context_instance = get_context_instance($cluster_context_level, $this->id);
        $instance_id = $cluster_context_instance->id;
        $instance_path = $cluster_context_instance->path;
        $children = userset::find(new join_filter('id', 'context', 'instanceid',
                                                  new AND_filter(array(new field_filter('path', "{$instance_path}/%", field_filter::LIKE),
                                                                       new field_filter('contextlevel', $cluster_context_level)))),
                                  array('id' => 'DESC'), 0, 0, $this->_db);
        $children = $children->to_array();

        if ($this->deletesubs) {
            $todelete = $children;
            $todelete[] = $this; // The specified cluster always gets deleted
        } else {
            $todelete = array($this);
        }

        foreach ($todelete as $userset) {
            // Cascade to regular datarecords
            $filter = new field_filter('clusterid', $userset->id);
            //clustercurriculum::delete_records($filter, $this->_db);
            //clustertrack::delete_records($filter, $this->_db);
            //clusterassignment::delete_records($filter, $this->_db);
            delete_context($cluster_context_level,$userset->id);

            // Cascade to all plugins
            foreach ($plugins as $plugin => $plugindir) {
                $result = $result && call_user_func('usersetenrol_' . $plugin . '_delete_for_userset', $userset->id);
            }

            $userset->deletesimple = true;
            $userset->delete();
        }

        if (!$this->deletesubs && !empty($children)) {
            foreach ($children as $child) {
                $lower_depth = $child->depth - 1;

                if (userset::exists(new field_filter('id', $child->parent))) {
                    /// A parent found so lets lower the depth
                    $child->depth = $child->depth - 1;
                } else {
                    /// Parent not found so this cluster will be top-level
                    $child->parent = 0;
                    $child->depth = 1;
                }
                $child->save();

                $sql = "UPDATE {context}
                        SET depth=0, path=NULL
                        WHERE contextlevel=? AND instanceid=?";
                $this->_db->execute($sql, array($cluster_context_level, $child->id));
            }
            build_context_path(); // Re-build the context table for all sub-clusters
        }

        return $result;
    }

    static $validation_rules = array(
        'validate_name_not_empty',
        'validate_unique_name'
    );

    public function validate_name_not_empty() {
        return validate_not_empty($this, 'name');
    }

    public function validate_unique_name() {
        return validate_is_unique($this, array('name'));
    }

    public function save() {
        $plugins = get_plugin_list(self::ENROL_PLUGIN_TYPE);
        foreach ($plugins as $plugin => $plugindir) {
            require_once(elis::plugin_file(self::ENROL_PLUGIN_TYPE.'_'.$plugin, 'lib.php'));
        }

        if (!empty($this->id)) {
            // cache the database values, so we can know if the parent changed
            $old = new userset($this->id);
            $old->load();
        }

        // figure out the right depth for the cluster
        if (empty($this->depth)) {
            if ($this->parent == 0) {
                $this->depth = 1;
            } else {
                $this->depth = $this->parentset->depth + 1;
            }
        }

        $result = parent::save();

        if (isset($old) && $this->parent != $old->parent) {
            $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $cluster_context_instance = get_context_instance($cluster_context_level, $this->id);

            // find all subclusters and adjust their depth
            $delta_depth = $this->depth - $old->depth;
            $LIKE = $this->_db->sql_like('path', '?');
            $sql = 'UPDATE {'.self::TABLE."}
                       SET depth = depth + ?
                     WHERE id IN (SELECT instanceid
                                    FROM {context}
                                   WHERE contextlevel = ?
                                     AND {$LIKE})";
            $this->_db->execute($sql, array($delta_depth, $cluster_context_level,
                                            "{$cluster_context_instance->path}/%"));

            // Blank out the depth and path for associated records and child records in context table
            $sql = "UPDATE {context}
                       SET depth=0, path=NULL
                     WHERE id=? OR {$LIKE}";
            $this->_db->execute($sql, array($cluster_context_instance->id, "{$cluster_context_instance->path}/%"));

            // Rebuild any blanked out records in context table
            build_context_path();
        }

        $plugins = get_plugin_list(self::ENROL_PLUGIN_TYPE);
        foreach ($plugins as $plugin => $plugindir) {
            call_user_func('userset_' . $plugin . '_update', $this);
        }

        if (isset($old))  {
            //signal that the cluster was created
            events_trigger('crlm_cluster_created', $this);
        } else {
            events_trigger('crlm_cluster_updated', $this);
        }

        return $result;
    }

    public function __toString() {
        return $this->name;
    }

    static function cluster_assigned_handler($eventdata) {
        require_once(elis::pm('data/clusterassignment.class.php'));
        require_once(elis::pm('data/clustercurriculum.class.php'));
        require_once(elis::pm('data/curriculumstudent.class.php'));
        require_once(elis::pm('data/clustertrack.class.php'));
        require_once(elis::pm('data/usertrack.class.php'));

        $assignment = new clusterassignment($eventdata);
        $userset = $assignment->userset;

        // assign user to the curricula associated with the cluster
        /**
        * @todo we may need to change this if associating a user with a
        * curriculum does anything more complicated
        */

        // enrol user in associated curricula
        $prog_assocs = $userset->program_assocs;
        foreach ($prog_assocs as $prog_assoc) {
            if ($prog_assoc->autoenrol
                && !curriculumstudent::exists(array(new field_filter('userid', $eventdata->userid),
                                                    new field_filter('curriculumid', $prog_assoc->curriculumid)))) {
                $progass = new programstudent();
                $progass->userid = $eventdata->userid;
                $progass->curriculumid = $prog_assoc->curriculumid;
                $progass->timecreated = $progass->timemodified = time();
                $progass->save();
            }
        }

        // enrol user in associated tracks if autoenrol flag is set
        if ($eventdata->autoenrol) {
            $track_assocs = $userset->track_assocs;
            foreach ($track_assocs as $track_assoc) {
                if ($track_assoc->autoenrol
                    && !usertrack::exists(array(new field_filter('userid', $eventdata->userid),
                                                new field_filter('trackid', $track_assoc->trackid)))) {
                    $usertrack = new usertrack();
                    $usertrack->userid = $eventdata->userid;
                    $usertrack->trackid = $track->trackid;
                    $usertrack->save();
                }
            }
        }

        return true;
    }

    static function cluster_deassigned_handler($eventdata) {
        return true;
    }

    /**
     * Returns an array of cluster ids that are parents of the supplied cluster
     * and the current user has access to enrol users into
     *
     * @param   int        $clusterid  The cluster whose parents we care about
     * @return  int array              The array of accessible cluster ids
     */
    public static function get_allowed_clusters($clusterid) {
        global $USER, $DB;

        //get the clusters and check the context against them
        $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $cluster_context_instance = get_context_instance($cluster_context_level, $clusterid);

        $path = $DB->sql_concat('ctxt.path', "'/%'");
        $LIKE = $DB->sql_like('?', $path);

        //query to get parent cluster contexts
        $cluster_permissions_sql = 'SELECT clst.*
                                    FROM {' . self::TABLE . "} clst
                                    JOIN {context} ctxt
                                         ON clst.id = ctxt.instanceid
                                         AND ctxt.contextlevel = ?
                                         AND {$LIKE} ";

        $params = array($cluster_context_level, $cluster_context_instance->path);

        // filter out the records that the user can't see
        $context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol_cluster_user', $USER->id);
        $filtersql = $context->get_filter('id')->get_sql(true, 'clst');

        if (isset($filtersql['join'])) {
            $cluster_permission_sql .= $filtersql['join'];
            $params += $filtersql['join_params'];
        }
        if (isset($filtersql['where'])) {
            $cluster_permissions_sql .= ' WHERE ' . $filtersql['where'];
            $params += $filtersql['where_params'];
        }

        $allowed_clusters = $DB->get_records_sql($cluster_permissions_sql, $params);

        $result = array();
        foreach ($allowed_clusters as $cluster) {
            $result[] = $cluster->id;
        }

        return $result;
    }

    /**
     * Determines whether the current user should be able to view any of the existing clusters
     *
     * @return  boolean  True if access is permitted, otherwise false
     */
    public static function all_clusters_viewable() {
        global $USER;

        //retrieve the context at which the current user has the sufficient capability
        $viewable_contexts = get_contexts_by_capability_for_user('cluster', 'block/curr_admin:cluster:view', $USER->id);
        $editable_contexts = get_contexts_by_capability_for_user('cluster', 'block/curr_admin:cluster:edit', $USER->id);

        //allow global access if either capability set allows access at the system context
        if (!empty($viewable_contexts->contexts['system'])) {
            return true;
        }
        if (!empty($editable_contexts->contexts['system'])) {
            return true;
        }

        return false;
    }

    /**
     * Determines the list of clusters the current user has the permissions to view
     *
     * @return  int array  The ids of the applicable clusters
     */
    public static function get_viewable_clusters($capabilities = null) {
        global $USER;

        if ($capabilities === null ) {
            $capabilities = array('block/curr_admin:cluster:view', 'block/curr_admin:cluster:edit');
        }
        if (!is_array($capabilities)) {
            $capabilities = array($capabilities);
        }

        $clusters = array();
        //retrieve the context at which the current user has the sufficient capability
        foreach ($capabilities as $capability) {
            $contexts = get_contexts_by_capability_for_user('cluster', $capability, $USER->id);
            //convert context sets to cluster ids
            $clusters[] = empty($contexts->contexts['cluster']) ? array() : $contexts->contexts['cluster'];
        }

        //merge the sets to get our final result
        $result = array_unique(call_user_func_array('array_merge', $clusters));

        return $result;
    }
}

/**
 * Filtering subsets of a given user set(s)
 */
class usersubset_filter extends data_filter {
    /**
     * @param string $name the field name containing the user set ID
     * @param data_filter $filter a filter specifying the target user sets that
     * we want to find the subsets of.  The cluster ID field will be called 'id'.
     * @param bool $not_subset whether to return the user sets that are not
     * subsets, rather than the ones that are
     */
    public function __construct($name, data_filter $filter, $not_subset=false) {
        $this->name = $name;
        $this->filter = $filter;
        $this->not_subset = $not_subset;
    }

    public function get_sql($use_join=false, $tablename=null, moodle_database $db=null) {
        global $DB;
        if ($db === null) {
            $db = $DB;
        }

        $clsttable = data_filter::_get_unique_name();
        $parenttable = data_filter::_get_unique_name();
        $childtable = data_filter::_get_unique_name();

        $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $parent_path = $db->sql_concat("{$parenttable}.path", "'/%'");
        $LIKE = $db->sql_like("{$childtable}.path", $parent_path);

        $sql = "SELECT cluster.id
                  FROM {" . userset::TABLE . "} {$clsttable}
                  JOIN {context} {$parenttable}
                    ON {$parenttable}.instanceid = {$clsttable}.id
                   AND {$parenttable}.contextlevel = {$cluster_context_level}
                  JOIN {context} {$childtable}
                    ON {$LIKE}
                   AND {$childtable}.contextlevel = {$cluster_context_level} ";

        $filtersql = $this->filter(true, $clsttable, $db);
        $params = array();
        if (isset($filtersql['join'])) {
            $sql .= $filtersql['join'];
            $params = $filtersql['join_parameters'];
        }
        if (isset($filtersql['where'])) {
            $sql .= ' WHERE ' . $filtersql['where'];
            $params = $filtersql['where_parameters'];
        }

        $NOT = $this->not_subset ? 'NOT ' : '';
        if ($tablename) {
            return array(
                'where' => "{$NOT}EXISTS (" . $sql
                    . (isset($filtersql['where']) ? " AND " : " WHERE " )
                    . "{$clsttable}.id = {$table}.{$this->name})",
                'where_parameters' => $params
            );
        } else {
            return array(
                'where' => "{$this->name} {$NOT}IN (" . $sql . ')',
                'where_parameters' => $params
            );
        }
    }
}

/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)

/**
 * Gets a cluster listing with specific sort and other filters.
 *
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for cluster name.
 * @param string $descsearch Search string for cluster description.
 * @param string $alpha Start initial of cluster name filter.
 * @param int $userid User who you are assigning clusters to
 * @return object array Returned records.
 */

function cluster_get_listing($sort='name', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                             $alpha='', $extrafilters = array(), $userid=0) {
    global $USER, $DB;

    //require plugin code if enabled
    $plugins = get_plugin_list('elisfields');
    $display_priority_enabled = isset($plugins['cluster_display_priority']);
    if($display_priority_enabled) {
        require_once(elis::plugin_file('elisfields_cluster_display_priority', 'lib.php'));
    }

    $select = 'SELECT clst.* ';
    $tables = 'FROM {' . userset::TABLE . '} clst';
    $join = '';

    $filters = array();

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $filters[] = new field_filter('name', "%$namesearch%", field_filter::LIKE);
    }

    if ($alpha) {
        $filters[] = new field_filter('name', "$alpha%", field_filter::LIKE);
    }

    if (!empty($extrafilters['contexts'])) {
        /*
         * Start of cluster hierarchy extension
         */

        $sql_condition = new select_filter('FALSE');

        if (userset::all_clusters_viewable()) {
            //user has capability at system level so allow access to any cluster
            $sql_condition = new select_filter('TRUE');
        } else {
            //user does not have capability at system level, so filter

            $viewable_clusters = userset::get_viewable_clusters();

            if (empty($viewable_clusters)) {
                //user has no access to any clusters, so do not allow additional access
                $sql_condition = new select_filter('FALSE');
            } else {
                //user has additional access to some set of clusters, so "enable" this access
                $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');

                //use the context path to find parent clusters
                $path = $DB->sql_concat('path', "'/%'");
                $LIKE = $DB->sql_like('?', $path);
                $cluster_filter = implode(',', $viewable_clusters);

                $sql_condition = new join_filter('id', 'context', 'instanceid',
                                                 new AND_filter(new field_filter('contextlevel', $cluster_context_level),
                                                                new select_filter($LIKE)));
            }
        }

        /*
         * End of cluster hierarchy extension
         */

        $context_filter = $extrafilters['contexts']->get_filter('id', 'cluster');

        //extend the basic context filter by potentially enabling access to parent clusters
        $filters[] = new OR_filter(array($context_filter, $sql_condition));
    }

    if (isset($extrafilters['parent'])) {
        $filters[] = new field_filter('parent', $extrafilters['parent']);
    }

    if (isset($extrafilters['classification'])) {
        require_once(elis::plugin_file('elisfields_cluster_classification', 'lib.php'));
        $contextlevel = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $field = new field(field::get_for_context_level_with_name($contextlevel, CLUSTER_CLASSIFICATION_FIELD));

        $filters[] = new elis_field_filter($field, 'id', $contextlevel, $extrafilters['classification']);
    }

    if(!empty($userid)) {
        //get the context for the "indirect" capability
        $context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol_cluster_user', $USER->id);

        $clusters = cluster_get_user_clusters($userid);
        $allowed_clusters = $context->get_allowed_instances($clusters, 'cluster', 'clusterid');

        $curriculum_context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol', $USER->id);
        $curriculum_filter = $curriculum_context->get_filter('id');

        if(empty($allowed_clusters)) {
            $filters[] = $curriculum_filter;
        } else {
            $allowed_clusters_list = implode(',', $allowed_clusters);

            $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $path = $DB->sql_concat('parentctxt.path', "'/%'");

            $LIKE = $DB->sql_like('childctxt.path', $path);

            //this allows both the indirect capability and the direct curriculum filter to work
            $subcluster_filter = new select_filter(
                "clst.id IN (SELECT childctxt.instanceid
                               FROM {" . userset::TABLE . "} clst
                               JOIN {context} parentctxt
                                 ON clst.id = parentctxt.instanceid
                                AND parentctxt.contextlevel = {$cluster_context_level}
                               JOIN {context} childctxt
                                 ON {$LIKE}
                                AND childctxt.contextlevel = {$cluster_context_level}
                              WHERE parentctxt.instanceid IN ({$allowed_clusters_list}))");
            $filters[] = new OR_filter(array($subcluster_filter, $curriculum_filter));
        }

    }

    //handle empty sort case
    if(empty($sort)) {
        $sort = 'name';
        $dir = 'ASC';
    }

    //get the fields we are sorting
    $sort_fields = explode(',', $sort);

    //convert the fields into clauses
    $sort_clauses = array();
    foreach($sort_fields as $field) {
        $field = trim($field);
        if($display_priority_enabled && $field == 'priority') {
            $sort_clauses[] = $field . ' DESC';
        } else {
            $sort_clauses[] = $field . ' ' . $dir;
        }
    }

    //determine if we are handling the priority field for ordering
    if($display_priority_enabled && in_array('priority', $sort_fields)) {
        userset_display_priority_append_sort_data('clst.id', $select, $join);
    }

    $filter = new AND_filter($filters);
    $filtersql = $filter->get_sql(true, 'clst');
    $params = array();
    $where = '';
    if(isset($filtersql['join'])) {
        $join .= ' JOIN ' . $filtersql['join'];
        $params += $filtersql['join_parameters'];
    }
    if(isset($filtersql['where'])) {
        $where = ' WHERE ' . $filtersql['where'];
        $params += $filtersql['where_parameters'];
    }

    $sort_clause = ' ORDER BY ' . implode($sort_clauses, ', ') . ' ';

    $sql = $select.$tables.$join.$where.$sort_clause;

    $recordset = $DB->get_recordset_sql($sql, $params, $startrec, $perpage);
    return new data_collection($recordset, 'userset', null, array(), true);
}


function cluster_count_records($namesearch = '', $alpha = '', $extrafilters = array()) {

    $select = 'SELECT clst.* ';
    $tables = 'FROM {' . userset::TABLE . '}';
    $join = '';

    $filters = array();

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $filters[] = new field_filter('name', "%$namesearch%", field_filter::LIKE);
    }

    if ($alpha) {
        $filters[] = new field_filter('name', "$alpha%", field_filter::LIKE);
    }

    if (!empty($extrafilters['contexts'])) {
        /*
         * Start of cluster hierarchy extension
         */

        $sql_condition = new select_filter('FALSE');

        if (userset::all_clusters_viewable()) {
            //user has capability at system level so allow access to any cluster
            $sql_condition = new select_filter('TRUE');
        } else {
            //user does not have capability at system level, so filter

            $viewable_clusters = userset::get_viewable_clusters();

            if (empty($viewable_clusters)) {
                //user has no access to any clusters, so do not allow additional access
                $sql_condition = new select_filter('FALSE');
            } else {
                //user has additional access to some set of clusters, so "enable" this access
                $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');

                //use the context path to find parent clusters
                $path = $DB->sql_concat('path', "'/%'");
                $LIKE = $DB->sql_like('?', $path);
                $cluster_filter = implode(',', $viewable_clusters);

                $sql_condition = new join_filter('id', 'context', 'instanceid',
                                                 new AND_filter(new field_filter('contextlevel', $cluster_context_level),
                                                                new select_filter($LIKE)));
            }
        }

        /*
         * End of cluster hierarchy extension
         */

        $context_filter = $extrafilters['contexts']->get_filter('id', 'cluster');

        //extend the basic context filter by potentially enabling access to parent clusters
        $filters[] = new OR_filter(array($context_filter, $sql_condition));
    }

    if (isset($extrafilters['parent'])) {
        $filters[] = new field_filter('parent', $extrafilters['parent']);
    }

    if (isset($extrafilters['classification'])) {
        require_once(elis::plugin_file('elisfields_cluster_classification', 'lib.php'));
        $contextlevel = context_level_base::get_custom_context_level('cluster', 'elis_program');
        $field = new field(field::get_for_context_level_with_name($contextlevel, CLUSTER_CLASSIFICATION_FIELD));

        $filters[] = new elis_field_filter($field, 'id', $contextlevel, $extrafilters['classification']);
    }

    if(!empty($userid)) {
        //get the context for the "indirect" capability
        $context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol_cluster_user', $USER->id);

        $clusters = cluster_get_user_clusters($userid);
        $allowed_clusters = $context->get_allowed_instances($clusters, 'cluster', 'clusterid');

        $curriculum_context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:cluster:enrol', $USER->id);
        $curriculum_filter = $curriculum_context->get_filter('id');

        if(empty($allowed_clusters)) {
            $filters[] = $curriculum_filter;
        } else {
            $allowed_clusters_list = implode(',', $allowed_clusters);

            $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $path = sql_concat('parentctxt.path', "'/%'");

            $like = sql_ilike();
            $LIKE = $DB->sql_like('childctxt.path', $path);

            //this allows both the indirect capability and the direct curriculum filter to work
            $subcluster_filter = new select_filter(
                "clst.id IN (SELECT childctxt.instanceid
                               FROM {" . userset::TABLE . "} clst
                               JOIN {context} parentctxt
                                 ON clst.id = parentctxt.instanceid
                                AND parentctxt.contextlevel = {$cluster_context_level}
                               JOIN {context} childctxt
                                 ON {$LIKE}
                                AND childctxt.contextlevel = {$cluster_context_level}
                              WHERE parentctxt.instanceid IN ({$allowed_clusters_list}))");
            $filters[] = new OR_filter(array($subcluster_filter, $curriculum_filter));
        }

    }

    return userset::count($filters);
}

/**
 * Specifies a mapping of cluster ids to names for display purposes
 *
 * @param  string  $orderby  Sort order and direction, if sorting is desired
 */
function cluster_get_cluster_names($orderby = 'name ASC') {
    global $DB;

    $select = 'SELECT c.id, c.name ';
    $from   = 'FROM {'.userset::TABLE.'} ';
    $join   = '';
    $where  = '';
    if (!empty($orderby)) {
        $order = 'ORDER BY '.$orderby.' ';
    } else {
        $order = '';
    }

    return $DB->get_records_menu(userset::TABLE, null, $orderby, 'id, name');
}

function cluster_get_user_clusters($userid) {
    return clusterassignment::find(new field_filter('userid', $userid));
}

function cluster_assign_to_user($clusterid, $userid, $autoenrol=true, $leader=false) {
    if (!is_numeric($clusterid) || !is_numeric($userid) || ($clusterid <= 0) || ($userid <= 0)) {
        // invalid data
        return false;
    }

    if (clusterassignment::exists(array(new field_filter('userid', $userid),
                                        new field_filter('clusterid', $clusterid)))) {
        // user already assigned
        return true;
    }

    $usass = new clusterassignment();
    $usass->userid = $userid;
    $usass->clusterid = $clusterid;
    $usass->save();

    events_trigger('cluster_assigned', $usass);

    return true;
}

function cluster_deassign_user($clusterid, $userid) {
    if (!is_numeric($clusterid) || !is_numeric($userid) || ($clusterid <= 0) || ($userid <= 0)) {
        return false;
    }

    $records = clusterassignment::find(array(new field_filter('userid', $userid),
                                             new field_filter('clusterid', $clusterid)));

    foreach ($records as $rec) {
        $rec->delete();
        events_trigger('cluster_deassigned', $rec);
    }

    return true;
}

function cluster_deassign_all_user($userid) {
    if (!is_numeric($userid) || ($userid <= 0)) {
        return false;
    }

    return clusterassignment::delete(new field_filter('userid', $userid));
}

/**
 * Gets cluster IDs and names of all non child clusters of target cluster.
 * This is used for selecting a cluster to be a parent of the target cluster.
 *
 * @param int $target_cluster_id Target cluster id
 * @return array Returned results with key as cluster id and value as cluster name
 */
function cluster_get_non_child_clusters($target_cluster_id) {
    global $DB;
    $return = array(0=>get_string('userset_top_level','elis_program'));

    $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');

    if (!empty($target_cluster_id)) {
        $cluster_context_instance = get_context_instance($cluster_context_level, $target_cluster_id);
        $target_cluster_path = $cluster_context_instance->path;
    } else {
        // provide a dummy id and path that won't match anything
        $target_cluster_id = 0;
        $target_cluster_path = 'NaN';
    }

    $LIKE = $DB->sql_like('ctx.path', '?', true, true, true /* not like */);

    $sql = "SELECT clst.id, clst.name
              FROM {" . userset::TABLE . "} clst
              JOIN {context} ctx ON ctx.instanceid = clst.id
                   AND ctx.contextlevel = ?
             WHERE {$LIKE}
                   AND ctx.instanceid != ?";

    $params = array($cluster_context_level,
                    "{$target_cluster_path}/%",
                    $target_cluster_id);

    $clusters = $DB->get_records_sql_menu($sql, $params);
    $clusters = array(0=>get_string('userset_top_level','elis_program')) + $clusters;

    return $clusters;
}

/**
 * Gets cluster IDs and names of all clusters that could be made into
 * subclusters of the target cluster.  Excludes:
 * - clusters that are an ancestor of the target cluster
 * - clusters that are already direct subclusters of the target cluster
 *
 * @param int $target_cluster_id Target cluster id
 * @return array Returned results with key as cluster id and value as cluster name
 */
function cluster_get_possible_sub_clusters($target_cluster_id) {
    global $DB;
    if ($target_cluster_id == '') {
        return array();
    }

    $cluster_context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
    $cluster_context_instance = get_context_instance($cluster_context_level, $target_cluster_id);
    // get parent contexts as a comma-separated list of context IDs
    $parent_contexts = explode('/', substr($cluster_context_instance->path,1));
    list($EQUAL, $params) = $DB->get_in_or_equal($parent_contexts, SQL_PARAMS_NAMED, 'param0000', false);

    $clusters = cluster_get_listing();

    $sql = "SELECT clst.id, clst.name
              FROM {" . userset::TABLE . "} clst
              JOIN {context} ctx ON ctx.instanceid = clst.id
                   AND ctx.contextlevel = :ctxlvl
             WHERE ctx.id {$EQUAL}
                   AND clst.parent != :parent";

    $params['ctxlvl'] = $cluster_context_level;
    $params['parent'] = $target_cluster_id;

    return $DB->get_records_sql_menu($sql, $params);
}
