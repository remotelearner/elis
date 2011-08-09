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

defined('MOODLE_INTERNAL') || die();

require_once elispm::lib('data/course.class.php');
require_once elispm::lib('data/curriculumstudent.class.php');
require_once elispm::lib('data/userset.class.php');

/** *** TBD: don't think this class is required anymore! ***
 *
 * Only known use is from: /elis/program/bulkuserpage.class.php
 * which calls:
 *     usermanagement_get_users()
 *     usermanagement_count_users()
 *
 * So these 2 functions could be moved to a library???
 */

// define('REQUIRE_USERMANAGEMENT_CLASS', 1);
if (defined('REQUIRE_USERMANAGEMENT_CLASS')) {

  class usermanagement extends curriculum {

    protected $_dbfield_userid;
    protected $_dbfield_curriculumid;
    protected $_dbfield_timecreated;
    protected $_dbfield_timemodified;
    
    static $associations = array(
        'user' => array(
            'class' => 'user',
            'idfield' => 'userid'
        ),
        'curriculum' => array(
            'class' => 'curriculum',
            'idfield' => 'curriculumid'
        ),
    );

    /* *** 
    var $id;           // INT - The data ID if in the database.
    var $userid;       // INT - The user ID.
    var $user;         // OBJECT - The user database object.
    var $curriculumid; // INT - The curriculum ID.
    var $curriculum;   // OBJECT - The curriculum database object.
    var $timecreated;  // INT - The time created (timestamp).
    var $timemodified; // INT - The time modified (timestamp).

    var $_dbloaded;    // BOOLEAN - True if loaded from database.
    *** */

    /**
     * Contructor.
     *
     * @param $curriculumstudentdata int/object/array The data id of a data record or data elements to load manually.
     *
     */
    /* ***
    function usermanagement($curriculumstudentdata = false) {
        $this->_dbloaded = false;

        $this->set_table(CURASSTABLE);
        $this->add_property('id', 'int');
        $this->add_property('userid', 'int');
        $this->add_property('curriculumid', 'int');
        $this->add_property('timecreated', 'int');
        $this->add_property('timemodified', 'int');

        if (is_numeric($curriculumstudentdata)) {
            $this->data_load_record($curriculumstudentdata);
        } else if (is_array($curriculumstudentdata)) {
            $this->data_load_array($curriculumstudentdata);
        } else if (is_object($curriculumstudentdata)) {
            $this->data_load_array(get_object_vars($curriculumstudentdata));
        }

        if (!empty($this->userid)) {
            $this->user = new user($this->userid);
        }

        if (!empty($this->curriculumid)) {
            $this->curriculum = new curriculum($this->curriculumid);
        }
    }
    *** */
  } // END_CLASS
} // END_IF defined('REQUIRE_USERMANAGEMENT_CLASS')

/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)

/**
 * Get a list of the available students not already attached to this course.
 *
 * @uses $CURMAN
 * @param string $search A search filter.
 * @uses $CFG
 * @uses $DB
 * @return array An array of user records.
 */
function usermanagement_get_students($type = 'student', $sort = 'name', $dir = 'ASC',
                                     $startrec = 0, $perpage = 0, $namesearch = '',
                                     $locsearch = '', $alpha = '') {
    global $CFG, $DB;

    $params          = array();
    $FULLNAME        = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $NAME_LIKE       = $DB->sql_like($FULLNAME, ':name_like', FALSE);
    $NAME_STARTSWITH = $DB->sql_like($FULLNAME, ':name_startswith', FALSE);
    // $USER_LOCAL_LIKE = $DB->sql_like('usr.local', ':local_like', FALSE); // TBD

    $select  = 'SELECT usr.id, usr.idnumber as idnumber, ' .
               'curass.id as curassid, curass.curriculumid as curid, ' .
               $FULLNAME .' as name ';
    $tables  = 'FROM {'. user::TABLE .'} usr
                LEFT JOIN {'. curriculumstudent::TABLE .'} curass ON curass.userid = usr.id ';

    /// If limiting returns to specific teams, set that up now.
    if (!empty($CFG->curr_configteams)) { // ***TBD***
        $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
    } else {
        $where = '';
    }

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') ."{$NAME_LIKE} ";
        $params['name_like'] = "%{$namesearch}%";
    }

  /* *** TBD: see above - no table column local in user::TABLE !?!
    if (!empty($locsearch)) {
        $locsearch = trim($locsearch);
        $where    .=  (!empty($where) ? ' AND ' : '') . "{$USER_LOCAL_LIKE} ";
        $params['local_like'] = "%{$locsearch}%";
    }
  *** */

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "($NAME_STARTSWITH} ";
        $params['name_startswith'] = "{$alpha}%";
    }

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

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    if ($sort) { // ***TBD***
        if ($sort == 'name') {
            $sort = "ORDER BY lastname {$dir}, firstname {$dir} ";
        } else {
            $sort = "ORDER BY {$sort} {$dir} ";
        }
    }

    $sql = $select.$tables.$where.$sort;

/// Perform some post-processing on the data received.
    if ($records = $DB->get_records_sql($sql, $params, $startrec, $perpage)) {
        foreach ($records as $i => $record) {
            $record->currentclassid = 0;
            $record->currentclass   = '';
            $record->lastclassid    = 0;
            $record->lastclass      = '';

            $timenow = time();

            $sql = "SELECT cls.id, crs.name
                    FROM {". course::TABLE ."} crs
                    LEFT JOIN {" . userset::TABLE ."} cls ON cls.courseid = crs.id
                    LEFT JOIN {". student::TABLE ."} stu ON stu.classid = cls.id
                    WHERE stu.userid = ?
                    AND stu.completestatusid = '". STUSTATUS_NOTCOMPLETE ."'
                    AND stu.enrolmenttime < ?
                    AND cls.enddate > ? ";

            if ($crs = $DB->get_record_sql($sql, array($record->id, $timenow, $timenow))) {
                $record->currentclassid = $crs->id;
                $record->currentclass   = $crs->name;
            }

            $sql = "SELECT cls.id, crs.name, stu.enrolmenttime
                    FROM {". course::TABLE ."} crs
                    LEFT JOIN {". userset::TABLE ."} cls ON cls.courseid = crs.id
                    LEFT JOIN {". student::TABLE ."} stu ON stu.classid = cls.id
                    WHERE stu.userid = ? 
                    AND stu.completestatusid != '" . STUSTATUS_NOTCOMPLETE . "'
                    AND stu.completetime = (
                        SELECT MAX(completetime)
                        FROM {". student::TABLE ."}
                        WHERE userid = ? 
                        AND completestatusid != '" . STUSTATUS_NOTCOMPLETE . "'
                    ) ";

            if ($crss = $DB->get_records_sql($sql, array($record->id, $record->id))) {
                if (count($crss) > 1) {
                    $starttime = 0;

                    foreach ($crss as $ci => $crst) {
                        if ($crst->enrolmenttime >= $starttime) {
                            $startime = $crst->enrolmenttime;
                            $crs      = $crss[$ci];
                        }
                    }
                } else {
                    $crs = current($crss);
                }

                $record->lastclassid = $crs->id;
                $record->lastclass   = $crs->name;
            }

            $records[$i] = $record;
        }

    } else {
        $records = array();
    }

    return $records;
}

/**
 * Count the number of users
 */
function usermanagement_count_students($type = 'student', $namesearch = '',
                                       $locsearch = '', $alpha = '') {
    global $CFG, $DB;

    $params          = array();
    $FULLNAME        = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $NAME_LIKE       = $DB->sql_like($FULLNAME, ':name_like', FALSE);
    $NAME_STARTSWITH = $DB->sql_like($FULLNAME, ':name_startswith', FALSE);
    $ID_LIKE         = $DB->sql_like('usr.idnumber', ':id_like', FALSE);

    $select = 'SELECT COUNT(usr.id) ';
    $tables = 'FROM {'. user::TABLE .'} usr ';
    $join   = '';
    $on     = '';

    /// If limiting returns to specific teams, set that up now.
    if (!empty($CFG->curr_configteams)) { // ***TBD***
        $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
    } else {
        $where = '';
    }

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : '') . "({$NAME_LIKE} OR {$ID_LIKE}) ";
        $params['name_like'] = "%{$namesearch}%";
        $params['id_like'] = "%{$namesearch}%";
    }

  /* *** TBD: see above - no table column local in user::TABLE !?!
    if (!empty($locsearch)) {
        $locsearch = trim($locsearch);
        $where    .=  (!empty($where) ? ' AND ' : '') . "(usr.local $LIKE '%$locsearch%') ";
    }
  *** */

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : '') . "{$NAME_STARTSWITH} ";
        $params['name_startswith'] = "{$alpha}%";
    }

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

    if (!empty($where)) {
        $where = 'WHERE '.$where.' ';
    }

    $sql = $select.$tables.$join.$on.$where;
    return $DB->count_records_sql($sql, $params);
}

function usermanagement_get_users($sort = 'name', $dir = 'ASC', $startrec = 0,
                                  $perpage = 0, $extrasql = array(), $contexts = null) {
    global $CFG, $DB;

    $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $select   = 'SELECT usr.id, usr.idnumber as idnumber, usr.country, usr.language, usr.timecreated, '.
               $FULLNAME . ' as name ';
    $tables   = 'FROM {'. user::TABLE .'} usr ';
    $where    = array();
    $params   = null;

    if (!empty($extrasql) && $extrasql[0]) {
        $where[] = $extrasql[0];
        if ($extrasql[1]) {
            $params = $extrasql[1];
        }
    }

    if ($contexts !== null) { // TBV
        $user_obj = $contexts->get_filter('usr.id', 'user'); // 'id' ???
        $filter_array = $user_obj->get_sql(false, 'usr');
        if (isset($filter_array['where'])) {
            $where[] = '('.$filter_array['where'].')';
        }
    }

    if (!empty($where)) {
        $s_where = 'WHERE '. implode(' AND ', $where) .' ';
    } else {
        $s_where = '';
    }

    if ($sort) { // ***TBD***
        if ($sort == 'name') {
            $sort = "ORDER BY lastname {$dir}, firstname {$dir} ";
        } else {
            $sort = "ORDER BY {$sort} {$dir} ";
        }
    }

    $sql = $select.$tables.$s_where.$sort;
    return $DB->get_records_sql($sql, $params, $startrec, $perpage);
}

/**
 * Count the number of users
 */
function usermanagement_count_users($extrasql = array(), $contexts = null) {
    global $CFG, $DB;

    $select  = 'SELECT COUNT(usr.id) ';
    $tables  = 'FROM {'. user::TABLE .'} usr ';
    $join    = '';
    $on      = '';
    $where   = array();
    $params  = null;

    if (!empty($extrasql) && $extrasql[0]) {
        $where[] = $extrasql[0];
        if ($extrasql[1]) {
            $params = $extrasql[1];
        }
    }

    if ($contexts !== null) { // TBV
        $user_obj = $contexts->get_filter('usr.id', 'user'); // 'id' ???
        $filter_array = $user_obj->get_sql(false, 'usr');
        if (isset($filter_array['where'])) {
            $where[] = '('.$filter_array['where'].')';
        }
    }

    if (!empty($where)) {
        $s_where = 'WHERE '. implode(' AND ', $where) .' ';
    } else {
        $s_where = '';
    }

    $sql = $select.$tables.$join.$on.$s_where;
    return $DB->count_records_sql($sql, $params);
}

