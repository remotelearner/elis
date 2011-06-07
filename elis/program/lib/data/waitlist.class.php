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

require_once elis::lib('data/data_object.class.php'); // TBD: was datarecord
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/student.class.php');
require_once elispm::lib('data/user.class.php');

// TBD:
//require_once CURMAN_DIRLOCATION . '/lib/notifications.php';

define ('WAITLISTTABLE', 'crlm_wait_list');

class waitlist extends elis_data_object {
    const LANG_FILE = 'elis_program'; // TBD
    const TABLE = WAITLISTTABLE;

    var $verbose_name = 'waitlist';

    static $associations = array( // TBD: class student ???
        'users'   => array('class' => 'user',
                           'idfield' => 'userid'),
        'pmclass' => array('class' => 'pmclass',
                           'idfield' => classid)
    );

/*
    var $id;            // INT - The data id if in the database.
    var $classid;       // INT - The id of the class this relationship belongs to.
    var $cmclass;         // OBJECT - class object.
    var $userid;        // INT - The id of the user this relationship belongs to.
    var $user;          // OBJECT - User object.
    var $timecreated;   // INT - Timestamp.
    var $timemodified;  // INT - Timestamp.
    var $position;      // INT - User's position in the waiting list queue.

    var $_dbloaded;         // BOOLEAN - True if loaded from database.
*/

    private $form_url = null;  //moodle_url object

    protected $_dbfield_classid;
    protected $_dbfield_userid;
    protected $_dbfield_timecreated;
    protected $_dbfield_timemodifieid;
    protected $_dbfield_position;
    protected $_dbfield_enrolmenttime;

/* ***** disabled constructor *****
    public function __construct($waitlistdata) {
        parent::__construct();

        $this->set_table(WAITLISTTABLE);
        $this->add_property('id', 'int');
        $this->add_property('classid', 'int');
        $this->add_property('userid', 'int');
        $this->add_property('timecreated', 'int');
        $this->add_property('timemodifieid', 'int');
        $this->add_property('position', 'int');
        $this->add_property('enrolmenttime', 'int');

        if (is_numeric($waitlistdata)) {
            $this->data_load_record($waitlistdata);
        } else if (is_array($waitlistdata)) {
            $this->data_load_array($waitlistdata);
        } else if (is_object($waitlistdata)) {
            $this->data_load_array(get_object_vars($waitlistdata));
        }
    }
***** */

    /**
     *
     * @param <type> $clsid
     * @param <type> $sort
     * @param <type> $dir
     * @param <type> $startrec
     * @param <type> $perpage
     * @param <type> $namesearch
     * @param <type> $alpha
     * @return <type>
     */
    public static function get_students($clsid = 0, $sort = 'timecreated', $dir = 'ASC',
                                        $startrec = 0, $perpage = 0, $namesearch = '',
                                        $alpha = '') {
        // TBD: this method should be replaced by association w/ filter
        if (empty($this->_db)) {
            return array();
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like('name', ':search_fullname');
        $LASTNAME_LIKE = $this->_db->sql_like('usr.lastname', ':search_lastname');

        $select   = 'SELECT watlst.id, usr.id as uid, '. $FULLNAME .' as name, usr.idnumber, usr.country, usr.language, watlst.timecreated ';

        $tables  = 'FROM {'. waitlist::TABLE .'} watlst ';
        $join    = 'JOIN {'. user::TABLE .'} usr ';
        $on      = 'ON watlst.userid = usr.id ';
        $where   = 'watlst.classid = :clsid ';
        $params['clsid'] = $clsid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where     .= (!empty($where) ? ' AND ' : ' ') . $FULLNAME_LIKE;
            $params['search_fullname'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') . LASTNAME_LIKE;
            $params['search_lastname'] = "{$alpha}%";
        }

        if (!empty($where)) {
            $where = ' WHERE '.$where.' ';
        }

        if ($sort) {
            $sort = ' ORDER BY '.$sort .' '. $dir.' ';
        }

        $sql = $select.$tables.$join.$on.$where.$sort.$limit;
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }

    public function check_autoenrol_after_course_completion($enrolment) {
        if($enrolment->completestatusid != STUSTATUS_NOTCOMPLETE) {
            $pmclass = new pmclass($enrolment->classid);

            if((empty($pmclass->maxstudents) || $pmclass->maxstudents > student::count_enroled($pmclass->id)) && !empty($pmclass->enrol_from_waitlist)) {
                $wlst = waitlist::get_next($enrolment->classid);

                if(!empty($wlst)) {
                    $wlst->enrol();
                }
            }
        }

        return true;
    }

    /**
     *
     * @param int $clsid
     * @param string $namesearch
     * @param char $alpha
     * @return array
     */
    public function count_records($clsid, $namesearch = '', $alpha = '') {
        // TBD: this method should be replaced by association w/ filter
        if(empty($clsid)) {
            if(!empty($this->classid)) {
                $clsid = $this->classid;
            } else {
                return array();
            }
        }

        $select = '';
        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':search_fullname');
        $LASTNAME_LIKE = $this->_db->sql_like('usr.lastname', ':search_lastname');

        $select = 'SELECT COUNT(watlist.id) ';
        $tables = 'FROM {'. waitlist::TABLE .'} watlist ';
        $join   = 'INNER JOIN {'. user::TABLE .'} usr ';
        $on     = 'ON watlist.userid = usr.id ';
        $where = 'watlist.classid = :clsid ';
        $params['clsid'] = $clsid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') . $FULLNAME_LIKE;
            $params['search_fullname'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') . LASTNAME_LIKE;
            $params['search_lastname'] = "{$alpha}%";
        }

        if (!empty($where)) {
            $where = ' WHERE '.$where.' ';
        }

        $sql = $select . $tables . $join . $on . $where;
        return $this->_db->count_records_sql($sql, $params);
    }

    /**
     *
     * @global object $CFG
     * @uses $CFG
     * @uses $OUTPUT
     */
    public function enrol() {
        global $CFG;
        $this->data_delete_record();

        $class = new pmclass($this->classid);
        $courseid = $class->get_moodle_course_id();

        // enrol directly in the course
        $student = new student($this);
        $student->enrolmenttime = max(time(), $class->startdate);
        $student->save(); // add()

        if ($courseid) {
            $course = $this->_db->get_record('course', array('id' => $this->id));
            // the elis plugin is treated specially
            if ($course->enrol != 'elis') {
                // send the user to the Moodle enrolment page
                $a = new stdClass;
                $a->crs = $course;
                $a->class = $class;
                $a->wwwroot = $CFG->wwwroot;
                // update strings in block_curr_admin, add {} where missing
                $subject = get_string('moodleenrol_subj', self::LANG_FILE, $a);
                $message = get_string('moodleenrol', self::LANG_FILE, $a);
            }
        }

        if (!isset($message)) {
            $a = new stdClass;
            $a->idnum = $class->idnumber;
            $subject = get_string('nowenroled', self::LANG_FILE, $a);
            $message = get_string('nowenroled', self::LANG_FILE, $a);
        }

        // TBD: $user = cm_get_moodleuser($this->userid);
        $cuser = new user($this->userid);
        $user = $cuser->get_moodleuser();
        $from = get_admin();

        // TBD: notification::notify($message, $user, $from);
        email_to_user($user, $from, $subject, $message);
    }

    /**
     *
     */
    public function save() { // add()

        if(empty($this->position)) {
            //SELECT MIN(userid) FROM eli_crlm_wait_list WHERE 1
            // TBD: MAX(postion) or MAX(wl.position) ???
            $sql = 'SELECT MAX(position) as max 
                    FROM {'. waitlist::TABLE .'}  wl
                    WHERE wl.classid = ? ';
            $max_record = get_record_sql($sql, array($this->classid));
            $max = $max_record->max;
            $this->position = $max + 1;
        }

        $subject = get_string('waitlist', self::LANG_FILE);
        $pmclass = new pmclass($this->classid);
        $message = get_string('added_to_waitlist_message', self::LANG_FILE, $pmclass);

        // TBD: $user = cm_get_moodleuser($this->userid);
        $cuser = new user($this->userid);
        $user = $cuser->get_moodleuser();
        $from = get_admin();

        // TBD: notification::notify($message, $user, $from);
        email_to_user($user, $from, $subject, $message);

        parent::save(); // add()
    }

    public static function get_next($clsid) {

        $select = 'SELECT * ';
        $from   = 'FROM {'. waitlist::TABLE .'} wlst ';
        $where  = 'WHERE wlst.classid = ? ';
        $order  = 'ORDER BY wlst.position ASC LIMIT 0,1';
        $sql = $select . $from . $where . $order;
        $nextStudent = $this->_db->get_records_sql($sql, array($clsid));

        if(!empty($nextStudent)) {
            $nextStudent = current($nextStudent);
            $nextStudent = new waitlist($nextStudent);
        }

        return $nextStudent;
    }

    public static function delete_for_user($id) {
    	$status = $this->_db->delete_records(waitlist::TABLE, array('userid' => $id));
    	return $status;
    }

    public static function delete_for_class($id) {
    	$status = $this->_db->delete_records(waitlist::TABLE, array('classid' => $id));
    	return $status;
    }
}

