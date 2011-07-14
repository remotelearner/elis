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

require_once elispm::lib('associationpage2.class.php');
require_once elispm::file('/form/addroleform.class.php');

abstract class rolepage extends associationpage2 {
    protected $parent_page;
    protected $section;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->section = $this->get_parent_page()->section;
    }

    abstract protected function get_context();

    abstract protected function get_parent_page();

    function get_tab_page() {
        return $this->get_parent_page();
    }

    function get_title() {
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
        $this->navbar->add(get_string('roles', 'role'), $page->url);

        //if we are looking at a particular role, add it to the navigation
        $roleid = $this->optional_param('role', 0, PARAM_INT);
        if ($roleid != 0) {
            $rolename = $DB->get_field('role', 'name', array('id' => $roleid));
            $page = $this->get_new_page(array('id' => $id,
                                              'role' => $roleid), true);
            $this->navbar->add($rolename, $page->url);
        }
    }

    function print_tabs() {
        $roleid = $this->optional_param('role', '0', PARAM_INT);
        if ($roleid) {
            parent::print_tabs();
        } else {
            $id = $this->required_param('id', PARAM_INT);
            $this->get_parent_page()->print_tabs(get_class($this), array('id' => $id));
        }
    }

    function can_do_default() {
        return has_capability('moodle/role:assign', $this->get_context());
    }

    function display_default() {
        global $CURMAN, $DB;

        //the specific role we are asigning users to
        $roleid = $this->optional_param('role', '', PARAM_INT);
        //the specific context id we are assigning roles on
        $context = $this->get_context();

        if ($roleid) {
            //make sure the current user can assign roles on the current context
            $assignableroles = get_assignable_roles($context, ROLENAME_BOTH);
            $roleids = array_keys($assignableroles);
            if (!in_array($roleid, $roleids)) {
                print_error('nopermissions', 'error');
            }

            return parent::display_default();
        } else {
            //use the standard link decorator to link role names to their specific sub-pages
            $decorator_params = array('id' => $this->required_param('id', PARAM_INT));
            $decorator = new record_link_decorator(get_class($this), $decorator_params, 'id', 'role');
            $decorators = array($decorator, 'decorate');

            //determine all apprlicable roles we can assign users as the current context
            $assignableroles = get_assignable_roles($context, ROLENAME_BOTH);
            $roles = array();

            foreach ($assignableroles as $roleid => $rolename) {
                $rec = new stdClass;
                $rec->id = $roleid;
                $rec->name = $rolename;
                $rec->description = format_string($DB->get_field('role', 'description', array('id' => $roleid)));
                $rec->count = count_role_users($roleid, $context);
                $roles[$roleid] = $rec;
            }

            $columns = array('name'        => array('header' => get_string('name'),
                                                    'decorator' => $decorators),
                             'description' => array('header' => get_string('description')),
                             'count'       => array('header' =>  get_string('users')));
            $table = new nosort_table($roles, $columns, $this->url);
            echo $table->get_html();
        }
    }

    protected function get_base_params() {
        $params = parent::get_base_params();
        $params['role'] = $this->required_param('role', PARAM_INT);
        return $params;
    }

    // ELISAT-349: Part 1
    function get_extra_page_params() {
        $extra_params = array();
        $sort = optional_param('sort', 'name', PARAM_ACTION);
        $order = optional_param('dir', 'ASC', PARAM_ACTION);
        if ($order != 'DESC') {
            $order = 'ASC';
        }
        $extra_params['sort'] = $sort;
        $extra_params['dir'] = $order;
        return $extra_params;
    }

    protected function get_selection_form() {
        if ($this->is_assigning()) {
            return new addroleform();
        } else {
            return new removeroleform();
        }
    }

    protected function process_assignment($data) {
        $context = $this->get_context();

        //make sure the current user can assign roles on the current context
        $assignableroles = get_assignable_roles($context, ROLENAME_BOTH);
        $roleids = array_keys($assignableroles);
        if (!in_array($data->role, $roleids)) {
            print_error('nopermissions', 'error');
        }

        //perform the role assignments
        foreach ($data->_selection as $user) {
            role_assign($data->role, cm_get_moodleuserid($user), $context->id);
        }

        //set up the redirect to the appropriate page
        $id = $this->required_param('id', PARAM_INT);
        $role = $this->required_param('role', PARAM_INT);
        $tmppage = $this->get_new_page(array('_assign' => 'assign',
                                             'id'      => $id,
                                             'role'    => $role));
        redirect($tmppage->url, get_string('users_assigned_to_role','block_curr_admin',count($data->_selection)));
    }

    protected function process_unassignment($data) {
        $context = $this->get_context();

        //make sure the current user can assign roles on the current context
        $assignableroles = get_assignable_roles($context, ROLENAME_BOTH);
        $roleids = array_keys($assignableroles);
        if (!in_array($data->role, $roleids)) {
            print_error('nopermissions', 'error');
        }

        //perform the role unassignments
        foreach ($data->_selection as $user) {
            role_unassign($data->role, cm_get_moodleuserid($user), $context->id);
        }

        //set up the redirect to the appropriate page
        $id = $this->required_param('id', PARAM_INT);
        $role = $this->required_param('role', PARAM_INT);
        $tmppage = $this->get_new_page(array('id'   => $id,
                                             'role' => $role));
        redirect($tmppage->url, get_string('users_removed_from_role','block_curr_admin',count($data->_selection)));
    }

    protected function get_selection_filter() {
        $post = $_POST;
        $filter = new cm_user_filtering(null, 'index.php', array('s' => $this->pagename) + $this->get_base_params());
        $_POST = $post;
        return $filter;
    }

    protected function print_selection_filter($filter) {
        $filter->display_add();
        $filter->display_active();
    }

    protected function get_assigned_records($filter) {
        global $CFG, $DB;

        $context = $this->get_context();
        $roleid = $this->required_param('role', PARAM_INT);

        $pagenum = optional_param('page', 0, PARAM_INT);
        $perpage = 30;

        $sort = optional_param('sort', 'name', PARAM_ACTION);
        $order = optional_param('dir', 'ASC', PARAM_ACTION);
        if ($order != 'DESC') {
            $order = 'ASC';
        }

        static $sortfields = array(
            'name' => array('lastname', 'firstname'),
            'idnumber' => 'idnumber',
            );
        if (!array_key_exists($sort, $sortfields)) {
            $sort = key($sortfields);
        }
        if (is_array($sortfields[$sort])) {
            $sortclause = implode(', ', array_map(create_function('$x', "return \"\$x $order\";"), $sortfields[$sort]));
        } else {
            $sortclause = "{$sortfields[$sort]} $order";
        }

        $where = "idnumber IN (SELECT mu.idnumber
                                 FROM {user} mu
                                 JOIN {role_assignments} ra
                                      ON ra.userid = mu.id
                                WHERE ra.contextid = :contextid
                                  AND ra.roleid = :roleid
                                  AND mu.mnethostid = :mnethostid)";

        $params = array('contextid' => $context->id,
                        'roleid' => $roleid,
                        'mnethostid' => $CFG->mnet_localhost_id);

        list($extrasql, $extraparams) = $filter->get_sql_filter();
        if ($extrasql) {
            $where .= " AND $extrasql";
            $params = array_merge($params, $extraparams);
        }

        $count = $DB->count_records_select('crlm_user', $where, $params);
        $users = $DB->get_records_select('crlm_user', $where, $params, $sortclause, '*', $pagenum*$perpage, $perpage);

        return array($users, $count);
    }

    protected function get_available_records($filter) {
        global $CFG, $DB;

        $context = $this->get_context();
        $roleid = required_param('role', PARAM_INT);

        $pagenum = optional_param('page', 0, PARAM_INT);
        $perpage = 30;

        $sort = optional_param('sort', 'name', PARAM_ACTION);
        $order = optional_param('dir', 'ASC', PARAM_ACTION);
        if ($order != 'DESC') {
            $order = 'ASC';
        }

        static $sortfields = array(
            'name' => array('lastname', 'firstname'),
            'idnumber' => 'idnumber',
            );
        if (!array_key_exists($sort, $sortfields)) {
            $sort = key($sortfields);
        }
        if (is_array($sortfields[$sort])) {
            $sortclause = implode(', ', array_map(create_function('$x', "return \"\$x $order\";"), $sortfields[$sort]));
        } else {
            $sortclause = "{$sortfields[$sort]} $order";
        }

        $where = "idnumber NOT IN (SELECT mu.idnumber
                                     FROM {user} mu
                                LEFT JOIN {role_assignments} ra
                                          ON ra.userid = mu.id
                                    WHERE ra.contextid = :contextid
                                      AND ra.roleid = :roleid
                                      AND mu.mnethostid = :mnethostid)";

        $params = array('contextid' => $context->id,
                        'roleid' => $roleid,
                        'mnethostid' => $CFG->mnet_localhost_id);

        list($extrasql, $extraparams) = $filter->get_sql_filter();

        if ($extrasql) {
            $where .= " AND $extrasql";
            $params = array_merge($params, $extraparams);
        }

        $count = $DB->count_records_select('crlm_user', $where, $params);
        $users = $DB->get_records_select('crlm_user', $where, $params, $sortclause, '*', $pagenum*$perpage, $perpage);

        return array($users, $count);
    }

    function get_records_from_selection($record_ids) {
        global $CURMAN;
        $usersstring = implode(',', $record_ids);
        $records = $CURMAN->db->get_records_select('crlm_user', "id in ($usersstring)");
        return $records;
    }

    protected function print_record_count($count) {
        print_string('usersfound','block_curr_admin',$count);
    }

    protected function create_selection_table($records, $baseurl) {
        $pagenum = optional_param('page', 0, PARAM_INT);
        $baseurl .= "&page={$pagenum}"; // ELISAT-349: part 2

        //persist our specific parameters
        $id = $this->required_param('id', PARAM_INT);
        $baseurl .= "&id={$id}";
        $assign = $this->optional_param('_assign', 'unassign', PARAM_ACTION);
        $baseurl .= "&_assign={$assign}";
        $role = $this->required_param('role', PARAM_INT);
        $baseurl .= "&role={$role}";

        $records = $records ? $records : array();
        $columns = array('_selection' => array('header' => ''),
                         'idnumber'   => array('header' => get_string('idnumber')),
                         'name'       => array('header' => array('firstname' => array('header' => get_string('firstname')),
                                                                 'lastname' => array('header' => get_string('lastname'))
                                                                 ),
                                               'display_function' => 'user_table_fullname'

                        )
        );

        //determine sort order
        $sort = optional_param('sort', 'lastname', PARAM_ALPHA);
        $dir  = optional_param('dir', 'ASC', PARAM_ALPHA);
        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }

        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } elseif (isset($columns['name']['header'][$sort])) {
            $columns['name']['header'][$sort]['sortable'] = $dir;
        } else {
            $sort = 'lastname';
            $columns['name']['header']['lastname']['sortable'] = $dir;
        }

        return new user_selection_table($records, $columns, new moodle_url($baseurl));
    }
}


class curriculum_rolepage extends rolepage {
    var $pagename = 'currole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('curriculum', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('curriculumpage.class.php');
            $id = $this->required_param('id');
            $this->parent_page = new curriculumpage(array('id' => $id,
                                                          'action' => 'view'));
        }
        return $this->parent_page;
    }
}

class track_rolepage extends rolepage {
    var $pagename = 'trkrole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('track', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('trackpage.class.php');
            $id = $this->required_param('id');
            $this->parent_page = new trackpage(array('id' => $id,
                                                     'action' => 'view'));
        }
        return $this->parent_page;
    }
}

class course_rolepage extends rolepage {
    var $pagename = 'crsrole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('course', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

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
}

class class_rolepage extends rolepage {
    var $pagename = 'clsrole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('class', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

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

class user_rolepage extends rolepage {
    var $pagename = 'usrrole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('user', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('userpage.class.php');
            $id = $this->required_param('id', PARAM_INT);
            $this->parent_page = new userpage(array('id' => $id,
                                                    'action' => 'view'));
        }
        return $this->parent_page;
    }
}

class cluster_rolepage extends rolepage {
    var $pagename = 'clstrole';

    protected function get_context() {
        if (!isset($this->context)) {
            $id = $this->required_param('id', PARAM_INT);

            $context_level = context_level_base::get_custom_context_level('cluster', 'elis_program');
            $context_instance = get_context_instance($context_level, $id);
            $this->set_context($context_instance);
        }
        return $this->context;
    }

    protected function get_parent_page() {
        if (!isset($this->parent_page)) {
            global $CFG, $CURMAN;
            require_once elispm::file('usersetpage.class.php');
            $id = $this->required_param('id', PARAM_INT);
            $this->parent_page = new usersetpage(array('id' => $id,
                                                       'action' => 'view'));
        }
        return $this->parent_page;
    }

    /*
     * Override the base get record method -- if the user has a certain
     * capability, then only show them the cluster members (ELIS-1570).
     */
    protected function get_assigned_records($filter) {
        if (has_capability('block/curr_admin:cluster:role_assign_cluster_users', $this->get_context(), NULL, false)) {
            global $CFG, $DB;

            $context = $this->get_context();
            $roleid = required_param('role', PARAM_INT);

            $pagenum = optional_param('page', 0, PARAM_INT);
            $perpage = 30;

            $sort = optional_param('sort', 'name', PARAM_ACTION);
            $order = optional_param('dir', 'ASC', PARAM_ACTION);
            if ($order != 'DESC') {
                $order = 'ASC';
            }

            static $sortfields = array(
                'name' => array('lastname', 'firstname'),
                'idnumber' => 'idnumber',
                );
            if (!array_key_exists($sort, $sortfields)) {
                $sort = key($sortfields);
            }
            if (is_array($sortfields[$sort])) {
                $sortclause = implode(', ', array_map(create_function('$x', "return \"\$x $order\";"), $sortfields[$sort]));
            } else {
                $sortclause = "{$sortfields[$sort]} $order";
            }

            $where = "idnumber IN (SELECT mu.idnumber
                                     FROM {user} mu
                                     JOIN {role_assignments} ra
                                          ON ra.userid = mu.id
                                    WHERE ra.contextid = :contextid
                                      AND ra.roleid = :roleid
                                      AND mu.mnethostid = :mnethostid)
                        AND id IN (SELECT userid
                                     FROM {".clusterassignment::TABLE."} uc
                                    WHERE uc.clusterid = :clusterid)";

            $params = array('contextid' => $context->id,
                            'roleid' => $roleid,
                            'mnethostid' => $CFG->mnet_localhost_id,
                            'clusterid' => $context->instanceid);

            list($extrasql, $extraparams) = $filter->get_sql_filter();

            if ($extrasql) {
                $where .= " AND $extrasql";
                $params = array_merge($params, $extraparams);
            }

            $count = $DB->count_records_select('crlm_user', $where, $params);
            $users = $DB->get_records_select('crlm_user', $where, $params, $sortclause, '*', $pagenum*$perpage, $perpage);

            return array($users, $count);
        } else {
            return parent::get_assigned_records($filter);
        }
    }

    /*
     * Override the base get record method -- if the user has a certain
     * capability, then only show them the cluster members (ELIS-1570).
     */
    protected function get_available_records($filter) {
        if (has_capability('block/curr_admin:cluster:role_assign_cluster_users', $this->get_context(), NULL, false)) {
            global $CFG, $DB;

            $context = $this->get_context();
            $roleid = required_param('role', PARAM_INT);

            $pagenum = optional_param('page', 0, PARAM_INT);
            $perpage = 30;

            $sort = optional_param('sort', 'name', PARAM_ACTION);
            $order = optional_param('dir', 'ASC', PARAM_ACTION);
            if ($order != 'DESC') {
                $order = 'ASC';
            }

            static $sortfields = array(
                'name' => array('lastname', 'firstname'),
                'idnumber' => 'idnumber',
                );
            if (!array_key_exists($sort, $sortfields)) {
                $sort = key($sortfields);
            }
            if (is_array($sortfields[$sort])) {
                $sortclause = implode(', ', array_map(create_function('$x', "return \"\$x $order\";"), $sortfields[$sort]));
            } else {
                $sortclause = "{$sortfields[$sort]} $order";
            }

            $where = "idnumber NOT IN (SELECT mu.idnumber
                                         FROM {user} mu
                                    LEFT JOIN {role_assignments} ra
                                              ON ra.userid = mu.id
                                        WHERE ra.contextid = :contextid
                                          AND ra.roleid = :roleid
                                          AND mu.mnethostid = :mnethostid)
                            AND id IN (SELECT userid
                                         FROM {".clusterassignment::TABLE."} uc
                                        WHERE uc.clusterid = :clusterid)";

            $params = array('contextid' => $context->id,
                            'roleid' => $roleid,
                            'mnethostid' => $CFG->mnet_localhost_id,
                            'clusterid' => $context->instanceid);

            list($extrasql, $extraparams) = $filter->get_sql_filter();

            if ($extrasql) {
                $where .= " AND $extrasql";
                $params = array_merge($params, $extraparams);
            }

            $count = $DB->count_records_select('crlm_user', $where, $params);
            $users = $DB->get_records_select('crlm_user', $where, $params, $sortclause, '*', $pagenum*$perpage, $perpage);

            return array($users, $count);
        } else {
            return parent::get_available_records($filter);
        }
    }
}

/******************************************************************************
 * Tables
 ******************************************************************************/

class nosort_table extends display_table {
    function is_sortable_default() {
        return false;
    }
}

class user_selection_table extends selection_table {
    function get_item_display_name($column, $item) {
        return fullname($item);
    }
}

