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

require_once elis::lib('data/data_object.class.php');
require_once elis::lib('table.class.php');

require_once elispm::lib('lib.php');
require_once elispm::lib('deprecatedlib.php');
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/user.class.php');
require_once elispm::lib('data/student.class.php');

define ('INSTABLE', 'crlm_class_instructor');

class instructor extends elis_data_object {
    const TABLE = INSTABLE;
    const LANG_FILE = 'elis_program';

    static $associations = array(
        'users'   => array('class' => 'user',
                           'idfield' => 'userid'),
        'pmclass' => array('class' => 'pmclass',
                           'idfield' => 'classid')
    );

/*
    var $id;           // INT - The data id if in the database.
    var $classid;      // INT - The class ID.
    var $cmclass;      // OBJECT - The class object.
    var $userid;       // INT - The user ID.
    var $user;         // OBJECT - The user object.
    var $assigntime;   // INT - The time assigned.
    var $completetime; // INT - The time completed.

    var $_dbloaded;    // BOOLEAN - True if loaded from database.
*/

    private $form_url = null;  //moodle_url object

    protected $_dbfield_classid;
    protected $_dbfield_userid;
    protected $_dbfield_assigntime;
    protected $_dbfield_completetime;

    //var $pmclass;           // OBJECT - The class object

    // STRING - Styles to use for edit form.
    var $_editstyle = '
.instructoreditform input,
.instructoreditform textarea {
    margin: 0;
    display: block;
}
';

    /**
     * Contructor.
     *
     * @param $instructordata int/object/array The data id of a data record or data elements to load manually.
     *
     */
/* **** disable constructor ****
    function instructor($instructordata=false) {
        parent::datarecord();

        $this->set_table(INSTABLE);
        $this->add_property('id', 'int');
        $this->add_property('classid', 'int');
        $this->add_property('userid', 'int');
        $this->add_property('syllabus', 'string');
        $this->add_property('assigntime', 'int');
        $this->add_property('completetime', 'int');

        if (is_numeric($instructordata)) {
            $this->data_load_record($instructordata);
        } else if (is_array($instructordata)) {
            $this->data_load_array($instructordata);
        } else if (is_object($instructordata)) {
            $this->data_load_array(get_object_vars($instructordata));
        }

        if (!empty($this->classid)) {
            $this->cmclass = new cmclass($this->classid);
        }

        if (!empty($this->userid)) {
            $this->user = new user($this->userid);
        }
    }
**** */

    public static function delete_for_class($id) {
        global $DB;
        return $DB->delete_records(instructor::TABLE, array('classid' => $id));
    }

    public static function delete_for_user($id) {
        global $DB;
        return $DB->delete_records(instructor::TABLE, array('userid' => $id));
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  FORM FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    /**
     * Return the HTML to edit a specific instructor.
     * This could be extended to allow for application specific editing, for example
     * a Moodle interface to its formslib.
     *
     * @param $formid string A suffix to put on all 'id' and index for all 'name' attributes.
     *                       This should be unique if being used more than once in a form.
     * @param $extraclass string Any extra class information to add to the output.
     * @uses $CFG
     * @uses $OUTPUT
     * @return string The form HTML, without the form.
     */
    function edit_form_html($classid, $sort = 'name', $dir = 'ASC', $page = 0,
                            $perpage = 30, $namesearch = '', $alpha = '') {
        global $CFG, $OUTPUT; // ^^^ set new non-zero default for $perpage

        $this->classid = $classid;
        $output = '';
        ob_start();

        if (empty($this->id)) {
            $columns = array(
                'assign'       => array('header' => get_string('assign', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false),
                'idnumber'     => array('header' => get_string('class_idnumber', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function'),
                'name'         => array('header' => get_string('tag_name', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function'),
                'assigntime'   => array('header' => get_string('assigntime', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false),
                'completetime' => array('header' => get_string('completion_time', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false)
            );

        } else {
            $columns = array(
                'idnumber'     => array('header' => get_string('class_idnumber', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function'),
                'name'         => array('header' => get_string('tag_name', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function'),
                'assigntime'   => array('header' => get_string('assigntime', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false),
                'completetime' => array('header' => get_string('completion_time', self::LANG_FILE),
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false)
              /* ****
                , 'buttons'      => array('header' => '',
                                        'display_function' => 'htmltab_display_function',
                                        'sortable' => false)
              **** */
            );
        }

        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } else {
            $sort = 'name';
            $columns[$sort]['sortable'] = $dir;
        }

        $newarr = array();
        $users = array();
        if (empty($this->id)) {
            $users     = $this->get_users_avail($sort, $dir, $page * $perpage,
                                                $perpage, $namesearch, $alpha);
            $usercount = $this->count_users_avail($namesearch, $alpha);

            pmalphabox(new moodle_url('/elis/program/index.php',
                               array('s' => 'ins', 'section' => 'curr',
                                     'action' => 'add', 'id' => $classid,
                                     'sort' => $sort, 'dir' => $dir,
                                     'perpage' => $perpage)),
                       'alpha', get_string('tag_name', self::LANG_FILE) .':');

            $pagingbar = new paging_bar($usercount, $page, $perpage,
                             "index.php?s=ins&amp;section=curr&amp;id=$classid&amp;action=add&amp;" .
                             "sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;" .
                             "search=".urlencode(stripslashes($namesearch))); // TBD: .'&amp;'
            echo $OUTPUT->render($pagingbar);
            flush();
        } else {
            //error_log("instructor.class.php::edit_form_html(); userid = {$this->userid}");
            if (($tmpuser = $this->_db->get_record(user::TABLE, array('id' => $this->userid)))) {
                // TBD - above was: $tmpuser = new user($this->userid)
                //print_object($tmpuser);
                $user = new stdClass;
                $user->id = $this->userid;
                foreach ($tmpuser as $key => $val) {
                    $user->{$key} = $val;
                }
                $user->name = fullname($user);
                $users[]    = $user;
                $usercount  = 0; // TBD: 1 ???
            }
        }

        if (empty($this->id) && !$users) {
            pmshowmatches($alpha, $namesearch);
            $table = NULL;
        } else {
            $insobj = new instructor();
            $table->width = "100%";
            foreach ($users as $user) {
                $tabobj = new stdClass;
              /* **** debug code
                ob_start();
                var_dump($user);
                $tmp = ob_get_contents();
                ob_end_clean();
                error_log("instructor.class.php::edit_form_html() user = $tmp");
              **** */
                foreach ($columns as $column => $cdesc) {
                    switch ($column) {
                        case 'assign':
                            $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][assign]" value="1" />'.
                                        '<input type="hidden" name="users[' . $user->id . '][idnumber]" '.
                                        'value="' . $user->idnumber . '" />';
                            break;

                        case 'name':
                        case 'idnumber':
                        case 'description';
                            $tabobj->{$column} = $user->{$column};
                            break;

                        case 'assigntime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][startday]',
                                                     'users[' . $user->id . '][startmonth]',
                                                     'users[' . $user->id . '][startyear]',
                                                     $this->assigntime, true);
                            break;

                        case 'completetime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][endday]',
                                                     'users[' . $user->id . '][endmonth]',
                                                     'users[' . $user->id . '][endyear]',
                                                     $this->completetime, true);
                            break;

                        default:
                            $tabobj->{$column} = '';
                            break;
                    }
                }
                $newarr[] = $tabobj;
                //$table->data[] = $newarr;
            }
            $table = new display_table($newarr, $columns, get_pm_url());
        }

        if (empty($this->id)) {
            pmsearchbox(null, 'search', 'get', get_string('show_all_users', self::LANG_FILE));

            echo '<form method="post" action="index.php?s=ins&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="savenew" />'."\n";

        } else {
            echo '<form method="post" action="index.php?s=ins&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="update" />'."\n";
            echo '<input type="hidden" name="association_id" value="' . $this->id . '" />' . "\n";
            echo '<input type="hidden" name="id" value="' . $this->classid . '" />' . "\n";
            echo '<input type="hidden" name="userid" value="' . $this->userid . '" />' . "\n";
        }

        if (!empty($table) && !empty($newarr)) {
            echo $table->get_html();
            $pagingbar = new paging_bar($usercount, $page, $perpage,
                             "index.php?s=ins&amp;section=curr&amp;id=$classid&amp;action=add&amp;" .
                             "sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;" .
                             "search=".urlencode(stripslashes($namesearch))); // TBD: .'&amp;'
            echo $OUTPUT->render($pagingbar);
        }

        if (empty($this->id)) {
            echo '<br /><input type="submit" value="' . get_string('assign_selected', self::LANG_FILE) . '">'."\n";
        } else {
            echo '<br /><input type="submit" value="' . get_string('update_assignment', self::LANG_FILE) . '">'."\n";
        }
        echo '</form>'."\n";

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  DATA FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    /**
     * Get a list of the existing instructors for the supplied (or current)
     * class.
     *
     * @paam int $cid A class ID (optional).
     * @return array An array of user records.
     */
    function get_instructors($cid = 0) {

        if (!$cid) {
            if (empty($this->classid)) {
                return array();
            }

            $cid = $this->classid;
        }

        $uids  = array();

        if ($instructors = $this->_db->get_records(instructor::TABLE, array('classid' => $cid))) {
            foreach ($instructors as $instructor) {
                $uids[] = $instructor->userid;
            }
        }

        if (!empty($uids)) {
            $sql = 'SELECT id, idnumber, username, firstname, lastname
                    FROM {'. user::TABLE . '}
                    WHERE id IN ( ' . implode(', ', $uids) . ' )
                    ORDER BY lastname ASC, firstname ASC';

            return $this->_db->get_records_sql($sql);
        }
        return array();
    }

    /**
     * Get a list of the available instructors not already attached to this course.
     *
     * @param string $search A search filter.
     * @return array An array of user records.
     */
    function get_users_avail($sort = 'name', $dir = 'ASC', $startrec = 0,
                             $perpage = 0, $namesearch = '', $alpha = '') {
        global $CFG;
        if (empty($this->_db)) {
            return NULL;
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT usr.id, ' . $FULLNAME . ' as name, usr.idnumber, ' .
                   'ins.classid, ins.userid, ins.assigntime, ins.completetime ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {' . instructor::TABLE .'} ins ';
        $on      = 'ON ins.userid = usr.id ';

        /// If limiting returns to specific teams, set that up now.
        if (!empty($CFG->curr_configteams)) {
            $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
        } else {
            $where = '';
        }

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where     .= (!empty($where) ? ' AND ' : ' ')."{$FULLNAME_LIKE} ";
            $params['name_like'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ')."{$LASTNAME_STARTSWITH}  ";
            $params['lastname_startswith'] = "{$alpha}%";
        }
/*
        switch ($type) {
            case 'student':
                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Student\' ';
                break;

            case 'instructor':
                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Instructor\' ';
                break;

            case '':
                $where .= (!empty($where) ? ' AND ' : '') . '(usr.type = \'Student\' OR usr.type = \'Instructor\') ';
                break;
        }
*/
        $uids = array();
        $stu = new student();
        if ($users = $stu->get_students()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if ($users = $this->get_instructors()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if (!empty($uids)) {
            $where .= (!empty($where) ? ' AND ' : '') . 'usr.id NOT IN ( ' .
                      implode(', ', $uids) . ' ) ';
        }

        if (!empty($where)) {
            $where = 'WHERE '.$where.' ';
        }

        if ($sort) {
            if ($sort === 'name') {
                $sort = $FULLNAME;
            }
            $sort = 'ORDER BY '.$sort .' '. $dir.' ';
        }

        $sql = $select.$tables.$join.$on.$where.$sort;
        //error_log("instructor.class::get_users_avail(); sql = {$sql}");
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }


    function count_users_avail($namesearch = '', $alpha = '') {
        global $CFG;
        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT COUNT(usr.id) ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {'. instructor::TABLE .'} ins ';
        $on      = 'ON ins.userid = usr.id ';

        /// If limiting returns to specific teams, set that up now.
        if (!empty($CFG->curr_configteams)) {
            $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
        } else {
            $where = '';
        }

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where     .= (!empty($where) ? ' AND ' : ' ')."{$FULLNAME_LIKE} ";
            $params['name_like'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ')."{$LASTNAME_STARTSWITH}  ";
            $params['lastname_startswith'] = "{$alpha}%";
        }
/*
        switch ($type) {
            case 'student':
                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Student\' ';
                break;

            case 'instructor':
                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Instructor\' ';
                break;

            case '':
                $where .= (!empty($where) ? ' AND ' : '') . '(usr.type = \'Student\' OR usr.type = \'Instructor\') ';
                break;
        }
*/
        $uids = array();
        $stu  = new student();
        if ($users = $stu->get_students()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if ($users = $this->get_instructors()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if (!empty($uids)) {
            $where .= (!empty($where) ? ' AND ' : '') . 'usr.id NOT IN ( ' .
                      implode(', ', $uids) . ' ) ';
        }

        if (!empty($where)) {
            $where = 'WHERE '.$where.' ';
        }

        $sql = $select.$tables.$join.$on.$where;
        return $this->_db->count_records_sql($sql, $params);
    }

    static function user_is_instructor_of_class($userid, $classid) {
        global $DB;
        return $DB->record_exists(instructor::TABLE,
                   array('userid' => $userid, 'classid' => $classid));
    }

    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }

}

/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)

/**
 * Gets a instructor listing with specific sort and other filters.
 *
 * @param int $classid The class ID.
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for instructor name.
 * @param string $alpha Start initial of instructor name filter.
 * @uses $DB
 * @return object array Returned records.
 */

function instructor_get_listing($classid, $sort = 'name', $dir = 'ASC', $startrec = 0,
                                $perpage = 0, $namesearch = '', $alpha='') {
    global $DB;
    $params = array();
    $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $FULLNAME_LIKE = $DB->sql_like($FULLNAME, ':name_like');
    $IDNUMBER_LIKE = $DB->sql_like('usr.idnumber', ':id_like');
    $LASTNAME_STARTSWITH = $DB->sql_like('usr.lastname', ':lastname_startswith');

    $select  = 'SELECT ins.* ';
    $select .= ', ' . $FULLNAME . ' as name, usr.idnumber ';
    $tables  = 'FROM {'. instructor::TABLE .'} ins ';
    $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
    $on      = 'ON ins.userid = usr.id ';
    $where   = 'ins.classid = :clsid ';
    $params['clsid'] = $classid;

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('.
                          $IDNUMBER_LIKE .')) ';
        $params['name_like'] = "%{$namesearch}%";
        $params['id_like']   = "%{$namesearch}%";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
        $params['lastname_startswith'] = "{$alpha}%";
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    if ($sort) {
        if ($sort === 'name') {
            $sort = $FULLNAME;
        }
        $sort = 'ORDER BY '.$sort .' '. $dir.' ';
    }

    $sql = $select.$tables.$join.$on.$where.$sort;
//print_object($sql);
    return $DB->get_records_sql($sql, $params, $startrec, $perpage);
}


/**
 * Count the number of instructors for this class.
 *
 * @uses $DB
 * @param int $classid The class ID.
 */
function instructor_count_records($classid, $namesearch = '', $alpha='') {
    global $DB;
    $params = array();
    $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $FULLNAME_LIKE = $DB->sql_like($FULLNAME, ':name_like');
    $IDNUMBER_LIKE = $DB->sql_like('usr.idnumber', ':id_like');
    $LASTNAME_STARTSWITH = $DB->sql_like('usr.lastname', ':lastname_startswith');

    $select  = 'SELECT COUNT(ins.id) ';
    $tables  = 'FROM {'. instructor::TABLE .'} ins ';
    $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
    $on      = 'ON ins.userid = usr.id ';
    $where   = 'ins.classid = :clsid ';
    $params['clsid'] = $classid; 

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('.
                          $IDNUMBER_LIKE .')) ';
        $params['name_like'] = "%{$namesearch}%";
        $params['id_like']   = "%{$namesearch}%";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
        $params['lastname_startswith'] = "{$alpha}%";
    }

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    $sql = $select . $tables . $join . $on . $where;
    return $DB->count_records_sql($sql, $params);
}

