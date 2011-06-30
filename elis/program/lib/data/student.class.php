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

//require_once CURMAN_DIRLOCATION . '/lib/curriculumcourse.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/attendance.class.php';

require_once elispm::lib('lib.php');
require_once elispm::lib('deprecatedlib.php');
require_once elispm::lib('data/classmoodlecourse.class.php');
require_once elispm::lib('data/course.class.php');
require_once elispm::lib('data/instructor.class.php');
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/user.class.php');
require_once elispm::lib('data/waitlist.class.php');

define ('STUTABLE', 'crlm_class_enrolment');
define ('GRDTABLE', 'crlm_class_graded');

define ('STUSTATUS_NOTCOMPLETE', 0);
define ('STUSTATUS_FAILED',      1);
define ('STUSTATUS_PASSED',      2);

class student extends elis_data_object {
    const TABLE = STUTABLE; // TBD
    const LANG_FILE = 'elis_program'; // TBD

    var $verbose_name = 'student';

    static $associations = array(
        'users'   => array('class' => 'user',
                           'idfield' => 'userid'),
        'pmclass' => array('class' => 'pmclass',
                           'idfield' => 'classid')
    );

/*
    var $id;                // INT - The data id if in the database.
    var $classid;           // INT - The class ID.
    var $cmclass;           // OBJECT - The class object
    var $userid;            // INT - The user ID.
    var $user;              // OBJECT - The user object.
    var $enrolmenttime;     // INT - The time assigned.
    var $completetime;      // INT - The time completed.
    var $completestatusid;  // INT - Status code for completion.
    var $grade;             // INT - Student grade.
    var $credits;           // INT - Credits awarded.
    var $locked;            // INT - Grade locked.

    var $_dbloaded;    // BOOLEAN - True if loaded from database.
*/
    static $completestatusid_values = array(
        STUSTATUS_NOTCOMPLETE => 'n_completed',
        STUSTATUS_FAILED      => 'failed',
        STUSTATUS_PASSED      => 'passed'
    );

    private $form_url = null;  //moodle_url object

    protected $_dbfield_classid;
    protected $_dbfield_userid;
    protected $_dbfield_enrolmenttime;
    protected $_dbfield_completetime;
    protected $_dbfield_endtime;
    protected $_dbfield_completestatusid;
    protected $_dbfield_grade;
    protected $_dbfield_credits;
    protected $_dbfield_locked;

    static $delete_is_complex = true; // TBD

    var $pmclass;           // OBJECT - The class object

    // STRING - Styles to use for edit form.
    var $_editstyle = '
.attendanceeditform input,
.attendanceeditform textarea {
    margin: 0;
    display: block;
}
';

    /**
     * Constructor.
     *
     * @param $studentdata int/object/array The data id of a data record or data elements to load manually.
     * @param $classdata object Optional cmclass object to load into the structure.
     * @param $complelements array Optional array of completion elements associated with the class.
     *
     */
/* **** disable constructor ****
    function __construct($studentdata=false, $classdata=false, $compelements=false) {
        global $CURMAN;

        parent::datarecord();

        $this->set_table(STUTABLE);
        $this->add_property('id', 'int');
        $this->add_property('classid', 'int', true);
        $this->add_property('userid', 'int', true);
        $this->add_property('enrolmenttime', 'int');
        $this->add_property('completetime', 'int');
        $this->add_property('endtime', 'int');
        $this->add_property('completestatusid', 'int');
        $this->completestatusid = key(student::$completestatusid_values);
        $this->add_property('grade', 'int');
        $this->add_property('credits', 'int');
        $this->add_property('locked', 'int');

        if (is_numeric($studentdata)) {
            $this->data_load_record($studentdata);
        } else if (is_array($studentdata)) {
            $this->data_load_array($studentdata);
        } else if (is_object($studentdata)) {
            $this->data_load_array(get_object_vars($studentdata));
        }

        $this->load_cmclass($classdata, $compelements);

        if (!empty($this->userid)) {
            $this->user = new user($this->userid);
        } else {
            $this->user = new user();
        }
    }
**** */

    function is_available() { // TBD: Move to parent class or library with class as param?
        return $this->_db->get_manager()->table_exists(self::TABLE);
    }

    /**
     *  @param $classdata int/object Optional id, or cmclass object to load.
     *  @param $compelements array Optional array of completion elements associated with the class.
     *
     */
/* **** function only used in disabled constructor ****
    function load_cmclass($classdata=false, $compelements=false) {

        if ($classdata !== false) {
            if (is_int($classdata) || is_numeric($classdata)) {
                $this->classid = $classdata;
                $this->pmclass = null;
            } else if (is_object($classdata) && (get_class($classdata) == 'cmclass')) {
                $this->classid = $classdata->id;
                $this->pmclass = $classdata;
            }
        }

        if (!empty($this->classid)) {

            if (empty($this->pmclass)) {
                $this->pmclass = new pmclass($this->classid);
            }

            /// Load up any completion and grade elements
            if (isset($this->pmclass->course)) {

                if ($compelements === false) {
                    $compelements = $this->pmclass->course->get_completion_elements();
                }
                $select ='classid = ? AND userid = ? ';
                $grades = $this->_db->get_records_select
                            (student_grade::TABLE, $select, array($this->classid, $this->userid),
                             '', 'completionid,id,classid,userid,grade,locked,timegraded,timemodified');
                $this->grades = array();

                if (!empty($compelements)) {
                    foreach ($compelements as $compelement) {
                        if (isset($grades[$compelement->id])) {
                            $this->grades[$compelement->id] = new student_grade($grades[$compelement->id]);
                        } else {
                            $this->grades[$compelement->id] = new student_grade();
                        }
                    }
                }
            }
        } else {
            $this->pmclass = new pmclass();
        }
    }
**** */

    /**
     * Perform all actions to mark this student record complete.
     *
     * @param   mixed  $status   The completion status (ignored if FALSE)
     * @param   mixed  $time     The completion time (ignored if FALSE)
     * @param   mixed  $grade    Grade in the class (ignored if FALSE)
     * @param   mixed  $credits  Number of credits awarded (ignored if FALSE)
     * @param   mixed  $locked   If TRUE, the assignment record becomes locked
     * @uses    $CFG
     * @return  boolean          TRUE is successful, otherwise FALSE
     */
    function complete($status = false, $time = false, $grade = false, $credits = false, $locked = false) {
        global $CFG;
        // *** TBD ***
        //require_once($CFG->dirroot .'/curriculum/lib/notifications.php');

        /// Set any data passed in...
        if ($status !== false) {
            $this->completestatusid = $status;
        }

        if ($time !== false) {
            $this->completetime = $time;
        }

        if ($grade !== false) {
            $this->grade = $grade;
        }

        if ($credits !== false) {
            $this->credits = $credits;
        }

        if ($locked !== false) {
            $this->locked = $locked;
        }

        /// Check that the data makes sense...
        if (($this->completestatusid == STUSTATUS_NOTCOMPLETE) || !isset(student::$completestatusid_values[$this->completestatusid])) {
            $this->completestatusid = STUSTATUS_PASSED;
        }

        if (($this->completetime <= 0) || !is_numeric($this->completetime)) {
            $this->completetime = time();
        }

        if ($this->update()) {
            /// Does the user receive a notification?
            $sendtouser       = elis::$config->elis_program->notify_classcompleted_user;
            $sendtorole       = elis::$config->elis_program->notify_classcompleted_role;
            $sendtosupervisor = elis::$config->elis_program->notify_classcompleted_supervisor;

            /// Make sure this is a valid user.
            $enroluser = new user($this->userid); // TBD
            if (empty($enroluser->id)) {
                print_error('nouser', self::LANG_FILE);
                return true;
            }

            $message = new stdClass; // TBD: new notification();

            /// Set up the text of the message
            $text = empty(elis::$config->elis_program->notify_classcompleted_message) ?
                        get_string('notifyclasscompletedmessagedef', self::LANG_FILE) :
                        elis::$config->elis_program->notify_classcompleted_message;
            $search = array('%%userenrolname%%', '%%classname%%');

            $pmuser = $this->_db->get_record(user::TABLE, array('id' => $this->userid));
            if (($clsmdl = $this->_db->get_record(classmoodlecourse::TABLE,
                                   array('classid' => $this->classid))) &&
                ($course = get_record('course', array('id' => $clsmdl->moodlecourseid)))) {
                /// If its a Moodle class...
                $replace = array(fullname($pmuser), $course->fullname);
                if (!($context = get_context_instance(CONTEXT_COURSE, $course->id))) {
                    print_error('invalidcontext');
                    return true;
                }
            } else {
                $pmclass = new pmclass($this->classid);
                $replace = array(fullname($pmuser), $pmclass->course->name);
                if (!($context = get_system_context())) {
                    print_error('invalidcontext');
                    return true;
                }
            }

            $text = str_replace($search, $replace, $text);

            if ($sendtouser) {
                $message->send_notification($text, $pmuser);
            }

            $users = array();

            if ($sendtorole) {
                /// Get all users with the notify_classcompleted capability.
                if ($roleusers = get_users_by_capability($context, 'block/curr_admin:notify_classcomplete')) {
                    $users = $users + $roleusers;
                }
            }

            if ($sendtosupervisor) {
                /// Get parent-context users.
                if ($supervisors = cm_get_users_by_capability('user', $this->userid, 'block/curr_admin:notify_classcomplete')) {
                    $users = $users + $supervisors;
                }
            }

            foreach ($users as $user) {
                $message->send_notification($text, $user, $enroluser);
            }
        }

//        events_trigger('crlm_class_completed', $this);
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  STANDARD FUNCTIONS:                                            //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    /**
     * Perform all necessary tasks to add a student enrolment to the system.
     *
     * @param array $checks what checks to perform before adding enrolling the
     * user.  e.g. array('prereq' => 1, 'waitlist' => 1) will check that
     * prerequisites are satisfied, and that the class is not full
     * @param boolean $notify whether or not notifications should be sent if a
     * check fails
     */
    function add($checks = array(), $notify = false) {
        global $CFG;

        $status = true;
        if ($this->_db->record_exists(student::TABLE,
                             array('userid' => $this->userid,
                                   'classid' => $this->classid))) {
            // already enrolled -- pretend we succeeded
            //error_log('student.class::save() - student already enrolled!');
            return true;
        }

        // check that the student can be enrolled first
        if (!empty($checks['prereq'])) {
            // check prerequisites

            $pmclass = new pmclass($this->classid);
            // get all the curricula that the user is in
            $curricula = curriculumstudent::get_curricula($this->userid);
            foreach ($curricula as $curriculum) {
                $curcrs = new curriculumcourse();
                $curcrs->courseid = $pmclass->courseid;
                $curcrs->curriculumid = $curriculum->curid;
                if (!$curcrs->prerequisites_satisfied($this->userid)) {
                    // prerequisites not satisfied
                    if ($notify) {
                        $data = new stdClass;
                        $data->userid = $this->userid;
                        $data->classid = $this->classid;
                        $data->trackid = $trackid;
                        events_trigger('crlm_prereq_unsatisfied', $data);
                    }

                    $status = new Object();
                    $status->message = get_string('unsatisfiedprereqs', self::LANG_FILE);
                    $status->code = 'unsatisfiedprereqs';
                    //error_log('student.class::save() - student missing prereqs!');
                    return $status;
                }
            }
        }

        if (!empty($checks['waitlist'])) {
            // check class enrolment limit
            $pmclass = new pmclass($this->classid);
            $pmclass->load(); // TBD
            $limit = $pmclass->maxstudents;
            if (!empty($limit) && $limit <= $this->count_enroled($this->classid)) {
                // class is full
                // put student on wait list
                $wait_list = new waitlist($this);
                $wait_list->timecreated = time();
                $wait_list->position = 0;
                $wait_list->add();

                if ($notify) {
                    $subject = get_string('user_waitlisted', self::LANG_FILE);

                    $a = new object();
                    $a->user = $this->user->idnumber;
                    $a->pmclass = $pmclass->idnumber;
                    $message = get_string('user_waitlisted_msg', self::LANG_FILE, $a);

                    $from = $user = get_admin();

                    notification::notify($message, $user, $from);
                    email_to_user($user, $from, $subject, $message);
                }

                $status = new Object();
                $status->message = get_string('user_waitlisted', self::LANG_FILE);
                $status->code = 'user_waitlisted';
                //error_log('student.class::save() - class full! wait-listed?');
                return $status;
            }
        }
        //set end time based on class duration
        $studentclass = new pmclass($this->classid);
        if (empty($this->endtime)) {
            if (isset($studentclass->duration) && $studentclass->duration) {
                $this->endtime = $this->enrolmenttime + $studentclass->duration;
            } else {
                // no class duration -> no end time
                $this->endtime = 0;
            }
        }

        /* $status = */ parent::save(); // WAS: $this->data_insert_record()
        // no return status from save()
        //error_log("student.class::save() - called parent::save() => {$status}");

        /// Enrol them into the Moodle class.
        if ($moodlecourseid = moodle_get_course($this->classid)) {
            if ($mcourse = $this->_db->get_record('course', array('id' => $moodlecourseid))) {
                $enrol = $mcourse->enrol;
                if (!$enrol) {
                    $enrol = $CFG->enrol;
                }
                if (elis::$config->elis_program->restrict_to_elis_enrolment_plugin && $enrol != 'elis') {
                    $status = new Object();
                    $status->message = get_string('error_not_using_elis_enrolment', self::LANG_FILE);
                    return $status;
                }

                $timestart = $this->enrolmenttime;
                $timeend = $this->endtime;

                if ($role = get_default_course_role($mcourse)) {
                    $context = get_context_instance(CONTEXT_COURSE, $mcourse->id);

                    /// Get the Moodle user ID or create a new account for this user.
                    if (!($muserid = cm_get_moodleuserid($this->userid))) {
                        $user = new user($this->userid); // TBD

                        if (!$muserid = $user->synchronize_moodle_user(true, true)) {
                            $status = new Object();
                            $status->message = get_string('errorsynchronizeuser', self::LANG_FILE);
                            $muserid = false;
                        }
                    }

                    if (!empty($muserid)) {
                        if (!role_assign($role->id, $muserid, 0, $context->id, $timestart, $timeend, 0, 'manual')) {
                            $status = new Object();
                            $status->message = get_string('errorroleassign', self::LANG_FILE);
                        }
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Perform all necessary tasks to remove a student enrolment from the system.
     */
    function delete() {
        /// Remove any grade records for this enrolment.
        $result = student_grade::delete_for_user_and_class($this->userid, $this->classid);

        /// Unenrol them from the Moodle class.
        if (!empty($this->classid) && !empty($this->userid) &&
            ($moodlecourseid = $this->_db->get_field('crlm_class_moodle', 'moodlecourseid', array('classid' => $this->classid))) &&
            ($muserid = cm_get_moodleuserid($this->userid))) {

            $context = get_context_instance(CONTEXT_COURSE, $moodlecourseid);
            if ($context && $context->id) {
                role_unassign(0, $muserid, 0, $context->id);
            }
        }

        /* $result = $result && */ parent::delete(); // WAS: $this->data_delete_record() - no return code from data_object::delete()

        if ($this->completestatusid == STUSTATUS_NOTCOMPLETE) {
            //error_log("student::delete() - classid = {$this->classid}");
            $pmclass = new pmclass($this->classid);
            if (empty($pmclass->maxstudents) || $pmclass->maxstudents > $this->count_enroled($pmclass->id)) {
                $wlst = waitlist::get_next($this->classid);

                if (!empty($wlst)) {
                    $wlst->enrol();
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves a user object given the users idnumber
     * @param <type> $idnumber
     * @uses $DB
     * @return <type>
     */
    public static function get_userclass($userid, $classid) {
        global $DB;
        $retval = null;

        $student = $DB->get_record(student::TABLE, array('userid' => $userid, 'classid' => $classid));
        if (!empty($student)) {
            $retval = new student($student->id);
        }
        return $retval;
    }

    // Note: we rely on the caller to cascade these deletes to the student_grade
    // table.
    public static function delete_for_class($id) {
        global $DB;
        return $DB->delete_records(student::TABLE, array('classid' => $id));
    }

    public static function delete_for_user($id) {
        global $DB;
        return $DB->delete_records(student::TABLE, array('userid' => $id));
    }

    /**
     * Perform all necessary tasks to update a student enrolment.
     *
     */
    function update() {
        parent::save(); // no return val
        events_trigger('crlm_class_completed', $this);
        return true;    // TBD
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  FORM FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    protected function get_base_url($withquerystring = true) {
        if ($withquerystring) {
            return get_pm_url();
        } else {
            return get_pm_url()->out_omit_querystring();
        }
    }

    function edit_student_html($stuid, $type = '', $sort = 'name', $dir = 'ASC', $page = 0,
                               $perpage = 30, $namesearch = '', $alpha = '') {
        $this->id = $stuid;
        //error_log("student.class.php::edit_student_html({$stuid}, {$type}, ... ); this->classid = {$this->classid}");
        return $this->edit_form_html($this->id /* ->classid */, $type, $sort, $dir, $page,
                                     $perpage, $namesearch, $alpha);
    }

    function edit_classid_html($classid, $type = '', $sort = 'name', $dir = 'ASC', $page = 0,
                               $perpage = 30, $namesearch = '', $alpha = '') {

        //error_log("student.class.php::edit_classid_html({$classid}, {$type}, ... ) - setting this->classid ({$this->classid}) = classid ({$classid})");
        $this->classid = $classid; // TBD ???
        return $this->edit_form_html($classid, $type, $sort, $dir, $page,
                                     $perpage, $namesearch, $alpha);
    }

    /**
     * Return the HTML to edit a specific student.
     * This could be extended to allow for application specific editing,
     * for example a Moodle interface to its formslib.
     *
     * @uses $CFG
     * @uses $OUTPUT
     * @uses $PAGE
     * @return string The form HTML, without the form.
     */
    function edit_form_html($classid, $type = '', $sort = 'name', $dir = 'ASC', $page = 0,
                            $perpage = 30, $namesearch = '', $alpha = '') {
                            // ^^^ set non-zero default for $perpage
        global $CFG, $OUTPUT, $PAGE;

        $output = '';
        ob_start();

        $newarr = array();
        if (empty($this->id)) {
            $columns = array(
                'enrol'            => array('header' => get_string('enrol', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'idnumber'         => array('header' => get_string('student_idnumber', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'name'             => array('header' => get_string('student_name_1', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'enrolmenttime'    => array('header' => get_string('enrolment_time', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'completetime'     => array('header' => get_string('completion_time', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'completestatusid' => array('header' => get_string('student_status', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'grade'            => array('header' => get_string('student_grade', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'credits'          => array('header' => get_string('student_credits', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'locked'           => array('header' => get_string('student_locked', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
            );
        } else {
            $columns = array(
                'idnumber'         => array('header' => get_string('student_idnumber', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'name'             => array('header' => get_string('student_name_1', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'enrolmenttime'    => array('header' => get_string('enrolment_time', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'completetime'     => array('header' => get_string('completion_time', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'completestatusid' => array('header' => get_string('student_status', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'grade'            => array('header' => get_string('student_grade', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'credits'          => array('header' => get_string('student_credits', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
                'locked'           => array('header' => get_string('student_locked', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function',
                                            'sortable' => false),
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

        $users = array();
        if (empty($this->id)) {
            $users     = $this->get_users_avail($sort, $dir, $page * $perpage,
                                                $perpage, $namesearch, $alpha);
            $usercount = $this->count_users_avail($namesearch, $alpha); // TBD

            pmalphabox(new moodle_url('/elis/program/index.php', // TBD
                               array('s' => 'stu', 'section' => 'curr',
                                     'action' => 'add', 'id' => $classid,
                                     'search' => $namesearch, 'sort' => $sort,
                                     'dir' => $dir, 'perpage' => $perpage)),
                       'alpha', get_string('tag_name', self::LANG_FILE) .':');

            $pagingbar = new paging_bar($usercount, $page, $perpage,
                    "index.php?s=stu&amp;section=curr&amp;id=$classid&amp;class=$classid&amp;&amp;action=add&amp;" .
                    "sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;stype=$type" .
                    "&amp;search=" . urlencode(stripslashes($namesearch))); // TBD: .'&amp;'
            echo $OUTPUT->render($pagingbar);
            flush();

            pmsearchbox(null, 'search', 'get', get_string('show_all_users', self::LANG_FILE)); // TBD: moved from below

        } else {
            $user       = $this->_db->get_record(user::TABLE, array('id' => $this->userid)); 
            $user->name = fullname($user);
            $users[]    = $user;
            $usercount  = 0;
        }

        if (empty($this->id) && !$users) {
            pmshowmatches($alpha, $namesearch);
            $table = NULL;
        } else {
            $stuobj = new student();

            $table->width = "100%";
            foreach ($users as $user) {
                $tabobj = new stdClass;
                foreach ($columns as $column => $cdesc) {
                    switch ($column) {
                        case 'enrol':
                            $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][enrol]" value="1" />'.
                                        '<input type="hidden" name="users[' . $user->id . '][idnumber]" '.
                                        'value="' . $user->idnumber . '" />';
                            break;

                        case 'name':
                        case 'idnumber':
                        case 'description';
                            $tabobj->{$column} = isset($user->{$column}) ? $user->{$column} : '';
                            break;

                        case 'enrolmenttime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][startday]',
                                                               'users[' . $user->id . '][startmonth]',
                                                               'users[' . $user->id . '][startyear]',
                                                               $this->enrolmenttime, true);
                            break;

                        case 'completetime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][endday]',
                                                               'users[' . $user->id . '][endmonth]',
                                                               'users[' . $user->id . '][endyear]',
                                                               $this->completetime, true);
                            break;

                        case 'completestatusid':
                            $choices = array();

                            foreach(student::$completestatusid_values as $key => $csidv) {
                                $choices[$key] = get_string($csidv, self::LANG_FILE); // TBD
                            }
                            $tabobj->{$column} = cm_choose_from_menu($choices,
                                                            'users[' . $user->id . '][completestatusid]',
                                                            $this->completestatusid, '', '', '', true);
                            break;

                        case 'grade':
                            $tabobj->{$column} = '<input type="text" name="users[' . $user->id . '][grade]" ' .
                                        'value="' . $this->grade . '" size="5" />';
                            break;

                        case 'credits':
                            $tabobj->{$column} = '<input type="text" name="users[' . $user->id . '][credits]" ' .
                                        'value="' . $this->credits . '" size="5" />';
                            break;

                        case 'locked':
                            $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][locked]" ' .
                                        'value="1" '.($this->locked?'checked="checked"':'').'/>';
                            break;

                        default:
                            $tabobj->{$column} = '';
                            break;
                    }
                }
                $newarr[] = $tabobj;
                //$table->data[] = $newarr;
            }
            // TBD: student_table() ???
            $table = new display_table($newarr, $columns, $this->get_base_url());
        }

        if (empty($this->id)) {
            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="savenew" />'."\n";
        } else {
            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="update" />'."\n";
            echo '<input type="hidden" name="association_id" value="' . $this->id . '" />' . "\n";
            echo '<input type="hidden" name="id" value="' . $this->classid . '" />' . "\n";
            echo '<input type="hidden" name="userid" value="' . $this->userid . '" />' . "\n";
        }

        if (!empty($newarr)) { // TBD: $newarr or $table
            if(empty($this->id)) {
                $PAGE->requires->js('/elis/program/js/classform.js');
                echo '<span class="checkbox selectall">';
                echo '<input type="checkbox" onclick="class_enrol_set_all_selected()"
                             id="class_enrol_select_all" name="class_enrol_select_all"/>';
                echo '<label for="class_enrol_select_all">' . get_string('enrol_select_all', self::LANG_FILE) . '</label>';
                echo '</span>';
            }
            echo $table->get_html();
        }

        if (isset($this->pmclass->course) && is_object($this->pmclass->course) &&
            (get_class($this->pmclass->course) == 'course') &&
            ($elements = $this->pmclass->course->get_completion_elements())) {

            $select = 'classid = ? AND userid = ? ';
            $grades = $this->_db->get_records_select(student_grade::TABLE, $select, array($this->classid, $this->userid), 'id', 'completionid,id,classid,userid,grade,locked,timegraded,timemodified');
            $columns = array( // TBD
                'element'    => array('header' => get_string('grade_element', self::LANG_FILE),
                                      'display_function' => 'htmltab_display_function'),
                'grade'      => array('header' => get_string('grade_element', self::LANG_FILE),
                                      'display_function' => 'htmltab_display_function'),
                'locked'     => array('header' => get_string('student_locked', self::LANG_FILE),
                                      'display_function' => 'htmltab_display_function'),
                'timegraded' => array('header' => get_string('date_graded', self::LANG_FILE),
                                      'display_function' => 'htmltab_display_function')
            );

            if ($dir !== 'DESC') {
                $dir = 'ASC';
            }
            if (isset($columns[$sort])) {
                $columns[$sort]['sortable'] = $dir;
            } else {
                $sort = 'element'; // TBD
                $columns[$sort]['sortable'] = $dir;
            }
            //$table->width = "100%"; // TBD

            $newarr = array();
            foreach ($elements as $element) {
                $tabobj = new stdClass;
                foreach ($columns as $column => $cdesc) {
                    switch ($column) {
                        case 'element':
                            if (isset($grades[$element->id])) {
                                $name = 'element['.$grades[$element->id]->id.']';
                                $value = $element->id;
                            } else {
                                $name = 'newelement['.$element->id.']';
                                $value = $element->id;
                            }
                            $tabobj->{$column} = '<input type="hidden" name="'.$name.'" ' .
                                        'value="' . $value . '" />'.s($element->idnumber);
                            break;

                        case 'timegraded':
                            if (isset($grades[$element->id])) {
                                $name = 'timegraded['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->timegraded;
                            } else {
                                $name = 'newtimegraded['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = cm_print_date_selector($name.'[startday]',
                                                               $name.'[startmonth]',
                                                               $name.'[startyear]',
                                                               $value, true);
                            break;

                        case 'grade':
                            if (isset($grades[$element->id])) {
                                $name = 'grade['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->grade;
                            } else {
                                $name = 'newgrade['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = '<input type="text" name="'.$name.'" ' .
                                        'value="' . $value . '" size="5" />';
                            break;

                        case 'locked':
                            if (isset($grades[$element->id])) {
                                $name = 'locked['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->locked;
                            } else {
                                $name = 'newlocked['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = '<input type="checkbox" name="'.$name.'" ' .
                                        'value="1" '.($value?'checked="checked"':'').'/>';
                            break;

                        default:
                            $tabobj->{$column} = '';
                            break;
                    }
                }
                $newarr[] = $tabobj;
                //$table->data[] = $newarr;
            }
            // TBD: student_table() ???
            $table = new display_table($newarr, $columns, $this->get_base_url());
            if (!empty($newarr)) { // TBD: $table or $newarr?
                echo '<br />';
                echo $table->get_html();
                print_string('grade_update_warning', self::LANG_FILE);
            }
        }

        if (empty($this->id)) {
            echo '<br /><input type="submit" value="' . get_string('enrol_selected', self::LANG_FILE) . '">'."\n";
        } else {
            echo '<br /><input type="submit" value="' . get_string('update_enrolment', self::LANG_FILE) . '">'."\n";
        }
        echo '</form>'."\n";

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Return the HTML to for a view page that also allows editing.
     *
     * @uses $CFG
     * @uses $OUTPUT
     * @uses $PAGE
     * @return string The form HTML, without the form.
     */
    function view_form_html($classid, $type = '', $sort = 'name', $dir = 'ASC', $page = 0,
                            $perpage = 0, $namesearch = '', $alpha = '') {
        global $CFG, $OUTPUT, $PAGE;

        $output = '';
        ob_start();

        $can_unenrol = pmclasspage::can_enrol_into_class($classid);

        if (empty($this->id)) {
            $columns = array(
                'unenrol'          => array('header' => get_string('unenrol', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'idnumber'         => array('header' => get_string('student_idnumber', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'name'             => array('header' => get_string('student_name_1', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
//                'description'      => 'Description',
                'enrolmenttime'    => array('header' => get_string('enrolment_time', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'completetime'     => array('header' => get_string('completion_time', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'completestatusid' => array('header' => get_string('student_status', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'grade'            => array('header' => get_string('student_grade', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'credits'          => array('header' => get_string('student_credits', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'locked'           => array('header' => get_string('student_locked', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function')
            );

            if (!$can_unenrol) {
                unset($columns['unenrol']);
            }
        } else {
            $columns = array(
                'idnumber'         => array('header' => get_string('student_idnumber', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'name'             => array('header' => get_string('student_name_1', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
//                'description'      => 'Description',
                'enrolmenttime'    => array('header' => get_string('enrolment_time', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'completetime'     => array('header' => get_string('completion_time', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'completestatusid' => array('header' => get_string('student_status', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'grade'            => array('header' => get_string('student_grade', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'credits'          => array('header' => get_string('student_credits', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function'),
                'locked'           => array('header' => get_string('student_locked', self::LANG_FILE),
                                            'sortable' => false,
                                            'display_function' => 'htmltab_display_function')
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

        $users = array();
        if (empty($this->id)) {
            $users     = $this->get_users_enrolled($type, $sort, $dir, $page * $perpage, $perpage,
                                                $namesearch, $alpha);
            $usercount = $this->count_users_enrolled($type, $namesearch, $alpha);

            pmalphabox(new moodle_url('/elis/program/index.php',
                               array('s' => 'stu', 'section' => 'curr',
                                     'action' => 'bulkedit', 'id' => $classid,
                                     'class' => $classid, 'perpage' => $perpage,
                                     'search' => $namesearch, 'sort' => $sort,
                                     'dir' => $dir)),
                       'alpha', get_string('lastname', self::LANG_FILE) .':');

            $pagingbar = new paging_bar($usercount, $page, $perpage,
                    "index.php?s=stu&amp;section=curr&amp;id=$classid&amp;class=$classid&amp;&amp;action=bulkedit&amp;" .
                    "sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;stype=$type" .
                    "&amp;search=" . urlencode(stripslashes($namesearch))); // TBD: .'&amp;'
            echo $OUTPUT->render($pagingbar);
            flush();

            pmsearchbox(null, 'search', 'get', get_string('show_all_users', self::LANG_FILE)); // TBD: moved from below

        } else {
            $user       = $this->_db->get_record(user::TABLE, array('id' => $this->userid));
            $user->name = fullname($user);
            $users[]    = $user;
            $usercount  = 0;
        }

        if (empty($this->id) && !$users) {
            pmshowmatches($alpha, $namesearch);
            $table = NULL;
        } else {
            $stuobj = new student();
            $newarr = array();
            $table->width = "100%"; // TBD
            foreach ($users as $user) {
                $tabobj = new stdClass;
                foreach ($columns as $column => $cdesc) {
                    switch ($column) {
                        case 'unenrol':
                            $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][unenrol]" value="1" />';
                            break;

                        case 'name':
                        case 'idnumber':
                        case 'description';
                            $tabobj->{$column} = $user->{$column};
                            break;

                        case 'enrolmenttime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][startday]',
                                                     'users[' . $user->id . '][startmonth]',
                                                     'users[' . $user->id . '][startyear]',
                                                     $user->enrolmenttime, true);
                            break;

                        case 'completetime':
                            $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][endday]',
                                                     'users[' . $user->id . '][endmonth]',
                                                     'users[' . $user->id . '][endyear]',
                                                     $user->completetime, true);
                            break;

                        case 'completestatusid':
                            $choices = array();
                            foreach(student::$completestatusid_values as $key => $csidv) {
                                $choices[$key] = get_string($csidv, self::LANG_FILE);
                            }
                            $tabobj->{$column} = cm_choose_from_menu($choices,
                                                     'users[' . $user->id . '][completestatusid]',
                                                     $user->completestatusid, '', '', '', true);
                            break;

                        case 'grade':
                            $tabobj->{$column} = '<input type="text" name="users[' . $user->id . '][grade]" ' .
                                        'value="' . $user->grade . '" size="5" />';
                            break;

                        case 'credits':
                            $tabobj->{$column} = '<input type="text" name="users[' . $user->id . '][credits]" ' .
                                        'value="' . $user->credits . '" size="5" />';
                            break;

                        case 'locked':
                            $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][locked]" ' .
                                        'value="1" '.($user->locked?'checked="checked"':'').'/>'.
                                        '<input type="hidden" name="users[' . $user->id . '][idnumber]" '.
                                        'value="' . $user->idnumber . '" />' .
                                        '<input type="hidden" name="users[' . $user->id . '][association_id]" '.
                                        'value="' . $user->association_id . '" />';
                            break;

                        default:
                            $tabobj->{$column} = '';
                            break;
                    }
                }
                $newarr[] = $tabobj;
                //$table->data[] = $newarr;
            }
            // TBD: student_table() ???
            $table = new display_table($newarr, $columns, $this->get_base_url());
        }

        if (empty($this->id)) {
            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="updatemultiple" />'."\n";
        } else {
            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;id=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="updatemultiple" />'."\n";
            echo '<input type="hidden" name="association_id" value="' . $this->id . '" />' . "\n";
            echo '<input type="hidden" name="id" value="' . $this->classid . '" />' . "\n";
            echo '<input type="hidden" name="userid" value="' . $this->userid . '" />' . "\n";
        }

        if (!empty($newarr)) { // TBD: $newarr or $table?
            if(empty($this->id)) {
                $PAGE->requires->js('/elis/program/js/classform.js');
                echo '<span class="checkbox selectall">';

                echo '<input type="checkbox" onclick="class_bulkedit_set_all_selected()"
                             id="class_bulkedit_select_all" name="class_bulkedit_select_all"/>';
                echo '<label for="class_bulkedit_select_all">' . get_string('bulkedit_select_all', self::LANG_FILE) . '</label>';
                echo '</span>';
            }
            echo $table->get_html();
        }

        if (isset($this->pmclass->course) && is_object($this->pmclass->course) &&
            (get_class($this->pmclass->course) == 'course') &&
            ($elements = $this->pmclass->course->get_completion_elements())) {

            $select = 'classid = ? AND userid = ? ';
            $grades = $this->_db->get_records_select(student_grade::TABLE, $select, array($this->classid, $this->userid), 'id', 'completionid,id,classid,userid,grade,locked,timegraded,timemodified');

            $columns = array(
                'element'          => array('header' => get_string('grade_element', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'grade'            => array('header' => get_string('grade', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'locked'           => array('header' => get_string('student_locked', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function'),
                'timegraded'       => array('header' => get_string('date_graded', self::LANG_FILE),
                                            'display_function' => 'htmltab_display_function')
            );

            if ($dir !== 'DESC') {
                $dir = 'ASC';
            }
            if (isset($columns[$sort])) {
                $columns[$sort]['sortable'] = $dir;
            } else {
                $sort = 'element'; // TBD
                $columns[$sort]['sortable'] = $dir;
            }
            //$table->width = "100%"; // TBD

            $newarr = array();
            foreach ($elements as $element) {
                $tabobj = new stdClass;
                foreach ($columns as $column => $cdesc) {
                    switch ($column) {
                        case 'element':
                            if (isset($grades[$element->id])) {
                                $name = 'element['.$grades[$element->id]->id.']';
                                $value = $element->id;
                            } else {
                                $name = 'newelement['.$element->id.']';
                                $value = $element->id;
                            }
                            $tabobj->{$column} = '<input type="hidden" name="'.$name.'" ' .
                                        'value="' . $value . '" />'.s($element->idnumber);
                            break;

                        case 'timegraded':
                            if (isset($grades[$element->id])) {
                                $name = 'timegraded['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->timegraded;
                            } else {
                                $name = 'newtimegraded['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = cm_print_date_selector($name.'[startday]',
                                                               $name.'[startmonth]',
                                                               $name.'[startyear]',
                                                               $value, true);
                            break;

                        case 'grade':
                            if (isset($grades[$element->id])) {
                                $name = 'grade['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->grade;
                            } else {
                                $name = 'newgrade['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = '<input type="text" name="'.$name.'" ' .
                                        'value="' . $value . '" size="5" />';
                            break;

                        case 'locked':
                            if (isset($grades[$element->id])) {
                                $name = 'locked['.$grades[$element->id]->id.']';
                                $value = $grades[$element->id]->locked;
                            } else {
                                $name = 'newlocked['.$element->id.']';
                                $value = 0;
                            }
                            $tabobj->{$column} = '<input type="checkbox" name="'.$name.'" ' .
                                        'value="1" '.($value?'checked="checked"':'').'/>';
                            break;

                        default:
                            $tabobj->{$column} = '';
                            break;
                    }
                }
                $newarr[] = $tabobj;
                //$table->data[] = $newarr;
            }
            // TBD: student_table() ???
            $table = new display_table($newarr, $columns, $this->get_base_url());
            if (!empty($table)) { // TBD: $newarr or $table?
                echo '<br />';
                echo $table->get_html();
            }
        }

        if (!empty($users)) {
            echo '<br /><input type="submit" value="' . get_string('save_enrolment_changes', self::LANG_FILE) . '">'."\n";
        }

        echo "<input type=\"button\" onclick=\"document.location='index.php?s=stu&amp;section=curr&amp;" .
                     "action=default&amp;id=$classid&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;alpha=$alpha&amp;search=" . urlencode(stripslashes($namesearch)) . "';\" value=\"Cancel\" />";

        echo '</form>'."\n";

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }


    function attendance_form_html($formid='', $extraclass='', $rows='2', $cols='40') {
        $index = !empty($formid) ? '['.$formid.']' : '';
        $formid_suffix = !empty($formid) ? '_'.$formid : '';

        $output = '';

//        if (!$atn = cm_get_attendance($this->classid, $this->userid)) {
        if (!$atn = get_attendance($this->classid, $this->userid)) {
            $atn = new attendance();
        }

        $output .= '<style>'.$this->_editstyle.'</style>';
        $output .= '<fieldset id="cmclasseditform'.$formid.'" class="cmclasseditform '.$extraclass.'">'."\n";
        $output .= '<legend>' . get_string('edit_student_attendance', self::LANG_FILE) . '</legend>'."\n";

        $output .= '<label for="timestart'.$formid.'" id="ltimestart'.$formid.'">Start Date:<br />';
        $output .= cm_print_date_selector('startday', 'startmonth', 'startyear', $atn->timestart, true);
        $output .= '</label><br /><br />';

        $output .= '<label for="timeend'.$formid.'" id="ltimeend'.$formid.'">End Date:<br />';
        $output .= cm_print_date_selector('endday', 'endmonth', 'endyear', $atn->timeend, true);
        $output .= '</label><br /><br />';

        $output .= '<label for="note'.$formid.'" id="lnote'.$formid.'">Note:<br />';
        $output .= '<textarea name="note'.$index.'" cols="'.$cols.'" rows="'.$rows.'" '.
                   'id="note'.$formid.'" class="attendanceeditform '.$extraclass.'">'.$atn->note.
                   '</textarea>'."\n";
        $output .= '</label>';

        $output .= '<input type="hidden" name="id' . $index . '" value="' . $this->id . '" />'."\n";
        $output .= '<input type="hidden" name="class" value="' . $this->classid . '" />';
        $output .= '<input type="hidden" name="userid" value="' . $this->userid . '" />';
        $output .= '<input type="hidden" name="atnid' . $index . '" value="' . $atn->id . '" />' . "\n";
        $output .= '</fieldset>';

        return $output;
    }

    public function __toString() { // to_string()
        return $this->user->idnumber . ' in ' . $this->pmclass->idnumber; // TBD
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  DATA FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////


    /**
     * Check for a duplicate record when doing an insert.
     *
     * @param object $record The record we want to insert.
     */
    function duplicate_check($record=null) {

        if(empty($record)) {
            $record = $this;
        }

        /// Check for an existing enrolment - it can't already exist.
        if ($this->_db->record_exists(student::TABLE, array('classid' => $record->classid, 'userid' => $record->userid))) {
            return true;
        }

        return false;
    }

    /**
     * Get a list of the existing students for the supplied (or current)
     * class. Regardless of status either passed failed or not completed.
     *
     * @paam int $cid A class ID (optional).
     * @return array An array of user records.
     */
    function get_students($cid = 0) {

        if (!$cid) {
            if (empty($this->classid)) {
                return array();
            }
            $cid = $this->classid;
        }

        $uids = array();

        if ($students = $this->_db->get_records(student::TABLE, array('classid' => $cid))) {
            foreach ($students as $student) {
                $uids[] = $student->userid;
            }
        }

        if (!empty($uids)) {
            $sql = 'SELECT id, idnumber, username, firstname, lastname
                    FROM {'. user::TABLE .'}
                    WHERE id IN ( '. implode(', ', $uids). ' )
                    ORDER BY lastname ASC, firstname ASC';

            return $this->_db->get_records_sql($sql);
        }
        return array();
    }

    /**
     * get the students on the waiting list for the supplied (or current) class
     * @param INT $cid the class id
     */
    public function get_waiting($cid = 0) {

        if (!$cid) {
            if (empty($this->classid)) {
                return array();
            }
            $cid = $this->classid;
        }

        $uids = array();

        if ($students = $this->_db->get_records(waitlist::TABLE, array('classid' => $cid))) {
            foreach ($students as $student) {
                $uids[] = $student->userid;
            }
        }

        if (!empty($uids)) {
            $sql = 'SELECT id, idnumber, username, firstname, lastname
                    FROM {'. user::TABLE .'}
                    WHERE id IN ( '. implode(', ', $uids) .' )
                    ORDER BY lastname ASC, firstname ASC';

            return $this->_db->get_records_sql($sql);
        }
        return array();
    }

    static public function get_waitlist_in_curriculum($userid, $curid) {
        global $DB;
        $select  = 'SELECT wat.id wlid, wat.position, cls.idnumber clsid, crs.name, cls.* ';
        $tables = 'FROM {'. CURCRSTABLE .'} curcrs '; // ***TBD***
        $join   = 'JOIN {'. course::TABLE .'} crs ON curcrs.courseid = crs.id ';
        $join  .= 'JOIN {'. pmclass::TABLE .'} cls ON cls.courseid = crs.id ';
        $join  .= 'JOIN {'. waitlist::TABLE .'} wat ON wat.classid = cls.id ';
        $where  = 'WHERE curcrs.curriculumid = ? ';
        $where .= 'AND wat.userid = ? ';
        $sort = 'ORDER BY curcrs.position';

        $sql = $select.$tables.$join.$where.$sort;
        return $DB->get_records_sql($sql, array($curid, $userid));
    }

    /**
     * gets a list of classes that the given (or current) student is a part of
     * filters are applied to classid_number
     * @param int $cuserid
     * @param str $sort
     * @param str $dir
     * @param int $startrec
     * @param int $perpage
     * @param str $namesearch
     * @param str $alpha
     * @return array
     */
    public function get_waitlist($cuserid=0, $sort='name', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                                            $alpha='') {

        if (!$cuserid) {
            if (empty($this->userid)) {
                return array();
            }
            $cuserid = $this->userid;
        }

        $params = array();
        $CRSNAME_LIKE = $this->_db->sql_like('crs.name', ':crs_like');
        $CRSNAME_STARTSWITH = $this->_db->sql_like('crs.name', ':crs_startswith');
        $CLSID_LIKE = $this->_db->sql_like('cls.idnumber', ':clsid');

        $select  = 'SELECT wat.id wlid, wat.position, cls.idnumber clsid, crs.name, cls.*';
        $tables  = 'FROM {'. waitlist::TABLE .'} wat ';
        $join    = 'JOIN {'. pmclass::TABLE .'} cls ON wat.classid = cls.id ';
        $join   .= 'JOIN {'. course::TABLE .'} crs ON cls.courseid = crs.id ';
        $where   = 'wat.userid = :userid ';
        $params['userid'] = $cuserid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $CRSNAME_LIKE .') OR ('. $CLSID_LIKE .') ';
            $params['crs_like'] = "%{$namesearch}%";
            $params['clsid_like'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') . '('. $CRSNAME_STARTSWITH .') ';
            $params['crs_startswith'] = "{$alpha}%";
        }

        if (!empty($where)) {
            $where = 'WHERE '.$where.' ';
        }

        if ($sort) {
            if ($sort === 'name') {
                $sort = 'crs.name';
            }
            $sort = 'ORDER BY '.$sort .' '. $dir.' ';
        }

        $sql = $select.$tables.$join.$where.$sort;
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }

    /**
     * Gets a student listing with specific sort and other filters from this
     * class (or supplide) includes students that have passed failed or not
     * completed
     *
     * @param int $classid The class ID.
     * @param string $sort Field to sort on.
     * @param string $dir Direction of sort.
     * @param int $startrec Record number to start at.
     * @param int $perpage Number of records per page.
     * @param string $namesearch Search string for student name.
     * @param string $alpha Start initial of student name filter.
     * @return object array Returned records.
     */
    function get_listing($classid=0, $sort='name', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                                 $alpha='') {
        if (!$classid) {
            if (empty($this->classid)) {
                return 0;
            }
            $classid = $this->classid;
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $IDNUMBER_LIKE = $this->_db->sql_like('usr.idnumber', ':id_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT stu.* ';
        $select .= ', ' . $FULLNAME . ' as name, usr.idnumber ';
    //    $select .= ', ' . $FULLNAME . ' as name, usr.type as description ';
        $tables  = 'FROM {'. student::TABLE .'} stu ';
        $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
        $on      = 'ON stu.userid = usr.id ';
        $where   = 'stu.classid = :clsid ';
        $params['clsid'] = $classid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
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
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }

    /**
     * counts the number of students enroled in the supplied (or current) class
     * That have not yet completed the class.
     *
     * @param INT $classid class id number
     * @param STR $namesearch name of the users being searched for
     * @param STR $alpha starting letter of the user being searched for
     * @return INT
     */
    public function count_enroled($classid = 0, $namesearch = '', $alpha = '') {
        global $DB; // NOTE: method called statically from pmclassform.class.php::validation()

        if (!$classid) {
            if (empty($this->classid)) {
                return 0;
            }
            $classid = $this->classid;
        }

        $params = array();
        $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $DB->sql_like($FULLNAME, ':name_like');
        $LASTNAME_STARTSWITH = $DB->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT COUNT(stu.id) ';
        $tables  = 'FROM {'. student::TABLE .'} stu ';
        $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
        $on      = 'ON stu.userid = usr.id ';
        $where   = 'stu.completestatusid = ' . STUSTATUS_NOTCOMPLETE . ' AND stu.classid = :clsid ';
        $params['clsid']= $classid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $FULLNAME_LIKE .') ';
            $params['name_like'] = "%{$namesearch}%";
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

    /**
     * Count the number of students for this class.
     *
     * @param int    $classid     The class ID.
     * @param string $namesearch  name of the users being searched for
     * @param string $alpha       starting letter of the user being searched for
     */
    public function count_records($classid = 0, $namesearch = '', $alpha = '') {

        if (!$classid) {
            if (empty($this->classid)) {
                return 0;
            }
            $classid = $this->classid;
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT COUNT(stu.id) ';
        $tables  = 'FROM {'. student::TABLE .'} stu ';
        $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
        $on      = 'ON stu.userid = usr.id ';
        $where   = 'stu.classid = :clsid ';
        $params['clsid'] = $classid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $FULLNAME_LIKE .') ';
            $params['name_like'] = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
            $params['lastname_startswith'] = "{$alpha}%";
        }

        if (!empty($where)) {
            $where = 'WHERE '.$where.' ';
        }

        $sql = $select . $tables . $join . $on . $where;
        return $this->_db->count_records_sql($sql, $params);
    }

    /**
     * Get a list of the available students not already attached to this course.
     *
     * TBD: add remaining params
     * @param string $namesearch  name of the users being searched for
     * @param string $alpha       starting letter of the user being searched for
     * @return array An array of user records.
     */
    function get_users_avail($sort = 'name', $dir = 'ASC', $startrec = 0,
                             $perpage = 0, $namesearch = '', $alpha = '') {
        if (empty($this->_db)) {
            return NULL;
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $IDNUMBER_LIKE = $this->_db->sql_like('usr.idnumber', ':id_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

//        $select  = 'SELECT usr.id, usr.idnumber, ' . $FULLNAME . ' as name, usr.type as description, ' .
        $select  = 'SELECT usr.id, usr.idnumber, ' . $FULLNAME . ' as name, ' .
                   'stu.classid, stu.userid, stu.enrolmenttime, stu.completetime, ' .
                   'stu.completestatusid, stu.grade ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {'. student::TABLE .'} stu ';
        $on      = 'ON stu.userid = usr.id AND stu.classid = :clsid ';
        $where   = 'stu.id IS NULL';
        $params['clsid'] = $this->classid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
            $params['name_like'] = "%{$namesearch}%";
            $params['id_like']   = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
            $params['lastname_startswith'] = "{$alpha}%";
        }

        $uids = array();
        if ($users = $this->get_students()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if($users = $this->get_waiting()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        $ins = new instructor();
        if ($users = $ins->get_instructors()) {
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

        // *** TBD ***
        if(!pmclasspage::_has_capability('block/curr_admin:class:enrol', $this->classid)) {
            //perform SQL filtering for the more "conditional" capability

            $allowed_clusters = pmclass::get_allowed_clusters($this->classid);

            if(empty($allowed_clusters)) {
                $where .= 'AND 0=1';
            } else {
                $cluster_filter = implode(',', $allowed_clusters);
                // *** TBD ***
                $where .= 'AND usr.id IN (
                             SELECT userid FROM {'. clusteruser::TABLE ."}
                             WHERE clusterid IN ({$cluster_filter}))";
            }
        }

        if ($sort) {
            if ($sort === 'name') {
                $sort = $FULLNAME;
            }
            $sort = 'ORDER BY '.$sort .' '. $dir.' ';
        }

        $sql = $select.$tables.$join.$on.$where.$sort;
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }

    /**
     * Count the available students not already attached to this course.
     *
     * @param string $namesearch  name of the users being searched for
     * @param string $alpha       starting letter of the user being searched for
     * @return  int  count of users.
     */
    function count_users_avail($namesearch = '', $alpha = '') {
        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $IDNUMBER_LIKE = $this->_db->sql_like('usr.idnumber', ':id_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT COUNT(usr.id) ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {'. student::TABLE .'} stu ';
        $on      = 'ON stu.userid = usr.id AND stu.classid = :clsid ';
        $where   = 'stu.id IS NULL';
        $params['clsid'] = $this->classid;

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
            $params['name_like'] = "%{$namesearch}%";
            $params['id_like']   = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
            $params['lastname_startswith'] = "{$alpha}%";
        }

        $uids = array();
        if ($users = $this->get_students()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        if($users = $this->get_waiting()) {
            foreach ($users as $user) {
                $uids[] = $user->id;
            }
        }

        $ins = new instructor();
        if ($users = $ins->get_instructors()) {
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

        // *** TBD ***
        if(!pmclasspage::_has_capability('block/curr_admin:class:enrol', $this->classid)) {
            //perform SQL filtering for the more "conditional" capability

            $allowed_clusters = pmclass::get_allowed_clusters($this->classid);

            if(empty($allowed_clusters)) {
                $where .= 'AND 0=1';
            } else {
                $cluster_filter = implode(',', $allowed_clusters);
                // *** TBD ***
                $where .= 'AND usr.id IN (
                             SELECT userid FROM {'. clusteruser::TABLE ."}
                             WHERE clusterid IN ({$cluster_filter}))";
            }
        }

        $sql = $select.$tables.$join.$on.$where;
        return $this->_db->count_records_sql($sql, $params);
    }

    /**
     * Get a list of the students already attached to this course.
     *
     * TBD - add remaining params
     * @param string $namesearch  name of the users being searched for
     * @param string $alpha       starting letter of the user being searched for
     * @uses  object $CFG         TBD: $CFG->curr_configteams
     * @return array An array of user records.
     */
    function get_users_enrolled($type = '', $sort = 'name', $dir = 'ASC', $startrec = 0,
                             $perpage = 0, $namesearch = '', $alpha = '') {
        global $CFG;

        if (empty($this->_db)) {
            return NULL;
        }

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $IDNUMBER_LIKE = $this->_db->sql_like('usr.idnumber', ':id_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

//        $select  = 'SELECT usr.id, usr.idnumber, ' . $FULLNAME . ' as name, usr.type as description, ' .
        $select  = 'SELECT usr.id, usr.idnumber, ' . $FULLNAME . ' as name, ' .
                   'stu.classid, stu.userid, usr.idnumber AS user_idnumber, stu.enrolmenttime, stu.completetime, ' .
                   'stu.completestatusid, stu.grade, stu.id as association_id, stu.credits, stu.locked ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {'. student::TABLE .'} stu ';
        $on      = 'ON stu.userid = usr.id ';

        /// If limiting returns to specific teams, set that up now.
        if (!empty($CFG->curr_configteams)) { // ***** TBD *****
            $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
        } else {
            $where = '';
        }

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
            $params['name_like'] = "%{$namesearch}%";
            $params['id_like']   = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
            $params['lastname_startswith'] = "{$alpha}%";
        }

        $where .= (!empty($where) ? ' AND ' : '') . "classid = {$this->classid} ";
        $where = "WHERE $where ";

        if ($sort) {
            if ($sort === 'name') {
                $sort = $FULLNAME;
            }
            $sort = 'ORDER BY '.$sort .' '. $dir.' ';
        }

        $sql = $select.$tables.$join.$on.$where.$sort;
        return $this->_db->get_records_sql($sql, $params, $startrec, $perpage);
    }

    /**
     * Count of the students already attached to this course.
     *
     * TBD - add remaining param: type
     * @param string $namesearch  name of the users being searched for
     * @param string $alpha       starting letter of the user being searched for
     * @uses  object $CFG    TBD: $CFG->curr_configteams
     * @return array An array of user records.
     */
    function count_users_enrolled($type = '', $namesearch = '', $alpha = '') {
        global $CFG;

        $params = array();
        $FULLNAME = $this->_db->sql_concat('usr.firstname', "' '", 'usr.lastname');
        $FULLNAME_LIKE = $this->_db->sql_like($FULLNAME, ':name_like');
        $IDNUMBER_LIKE = $this->_db->sql_like('usr.idnumber', ':id_like');
        $LASTNAME_STARTSWITH = $this->_db->sql_like('usr.lastname', ':lastname_startswith');

        $select  = 'SELECT COUNT(usr.id) ';
        $tables  = 'FROM {'. user::TABLE .'} usr ';
        $join    = 'LEFT JOIN {'. student::TABLE .'} stu ';
        $on      = 'ON stu.userid = usr.id ';

        /// If limiting returns to specific teams, set that up now.
        if (!empty($CFG->curr_configteams)) { // *** TBD ***
            $where = 'usr.team IN ('.$CFG->curr_configteams.') ';
        } else {
            $where = '';
        }

        if (!empty($namesearch)) {
            $namesearch = trim($namesearch);
            $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
            $params['name_like'] = "%{$namesearch}%";
            $params['id_like']   = "%{$namesearch}%";
        }

        if ($alpha) {
            $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
            $params['lastname_startswith'] = "{$alpha}%";
        }

//        switch ($type) {
//            case 'student':
//                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Student\' ';
//                break;
//
//            case 'instructor':
//                $where .= (!empty($where) ? ' AND ' : '') . 'usr.type = \'Instructor\' ';
//                break;
//
//            case '':
//                $where .= (!empty($where) ? ' AND ' : '') . '(usr.type = \'Student\' OR usr.type = \'Instructor\') ';
//                break;
//        }

        $where .= (!empty($where) ? ' AND ' : '') . ' classid = :clsid ';
        $params['clsid'] = $this->classid;
        $where = "WHERE $where ";

        $sql = $select.$tables.$join.$on.$where;
        return $this->_db->count_records_sql($sql, $params);
    }

    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  STATIC FUNCTIONS:                                              //
//    These functions can be used without instatiating an object.  //
//    Usage: student::[function_name([args])]                      //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    /*
     * ---------------------------------------------------------------------------------------
     * EVENT HANDLER FUNCTIONS:
     *
     * These functions handle specific student events.
     *
     */

    /* **** TBD: Notifications **** */

    /**
     * Function to handle class not started events.
     *
     * @param   student  $student  The class enrolment object
     * @uses    $CFG
     * @uses    $DB
     * @return  boolean            TRUE is successful, otherwise FALSE
     */

    public static function class_notstarted_handler($student) {
        global $CFG, $DB;
        // *** TBD ***
        //require_once($CFG->dirroot .'/curriculum/lib/notifications.php');

        /// Does the user receive a notification?
        $sendtouser       = elis::$config->elis_program->notify_classnotstarted_user;
        $sendtorole       = elis::$config->elis_program->notify_classnotstarted_role;
        $sendtosupervisor = elis::$config->elis_program->notify_classnotstarted_supervisor;

        /// If nobody receives a notification, we're done.
        if (!$sendtouser && !$sendtorole && !$sendtosupervisor) {
            return true;
        }

        if (!empty($student->moodlecourseid)) {
            if (!($context = get_context_instance(CONTEXT_COURSE, $student->moodlecourseid))) {
                debugging(get_string('invalidcontext'));
                return true;
            }
        } else {
            $context = get_system_context();
        }

        $message = new notification();

        /// Set up the text of the message
        $text = empty(elis::$config->elis_program->notify_classnotstarted_message) ?
                    get_string('notifyclassnotstartedmessagedef', self::LANG_FILE) :
                    elis::$config->elis_program->notify_classnotstarted_message;
        $search = array('%%userenrolname%%', '%%classname%%');
        $pmuser = $DB->get_record(user::TABLE, array('id' => $student->userid));
        $replace = array(fullname($pmuser), $student->pmclass->course->name);
        $text = str_replace($search, $replace, $text);

        $eventlog = new Object();
        $eventlog->event = 'class_notstarted';
        $eventlog->instance = $student->classid;
        if ($sendtouser) {
            $message->send_notification($text, $student->user, null, $eventlog);
        }

        $users = array();

        if ($sendtorole) {
            /// Get all users with the notify_classnotstart capability.
            if ($roleusers = get_users_by_capability($context, 'block/curr_admin:notify_classnotstart')) {
                $users = $users + $roleusers;
            }
        }

        if ($sendtosupervisor) {
            /// Get parent-context users.
            if ($supervisors = cm_get_users_by_capability('user', $this->userid, 'block/curr_admin:notify_classnotstart')) {
                $users = $users + $supervisors;
            }
        }

        foreach ($users as $user) {
            $message->send_notification($text, $user, $enroluser);
        }

        return true;
    }

    /**
     * Function to handle class not completed events.
     *
     * @param   student  $student  The class enrolment / student object who is "not completed"
     * @uses    $CFG
     * @uses    $DB
     * @return  boolean            TRUE is successful, otherwise FALSE
     */

    public static function class_notcompleted_handler($student) {
        global $CFG, $DB;
        // *** TBD ***
        //require_once($CFG->dirroot .'/curriculum/lib/notifications.php');

        /// Does the user receive a notification?
        $sendtouser = elis::$config->elis_program->notify_classnotcompleted_user;
        $sendtorole = elis::$config->elis_program->notify_classnotcompleted_role;

        /// If nobody receives a notification, we're done.
        if (!$sendtouser && !$sendtorole) {
            return true;
        }

        if (!empty($student->moodlecourseid)) {
            if (!($context = get_context_instance(CONTEXT_COURSE, $student->moodlecourseid))) {
                debugging(get_string('invalidcontext'));
                return true;
            }
        } else {
            $context = get_system_context();
        }

        $message = new notification();

        /// Set up the text of the message
        $text = empty(elis::$config->elis_program->notify_classnotcompleted_message) ?
                    get_string('notifyclassnotcompletedmessagedef', self::LANG_FILE) :
                    elis::$config->elis_program->notify_classnotcompleted_message;
        $search = array('%%userenrolname%%', '%%classname%%');
        $pmuser = $DB->get_record(user::TABLE, array('id' => $student->userid));
        $replace = array(fullname($pmuser), $student->pmclass->course->name);
        $text = str_replace($search, $replace, $text);

        $eventlog = new Object();
        $eventlog->event = 'class_notcompleted';
        $eventlog->instance = $student->classid;
        if ($sendtouser) {
            $message->send_notification($text, $pmuser, null, $eventlog);
        }

        $users = array();

        if ($sendtorole) {
            /// Get all users with the notify_classnotcomplete capability.
            if ($roleusers = get_users_by_capability($context, 'block/curr_admin:notify_classnotcomplete')) {
                $users = $users + $roleusers;
            }
        }

        if ($sendtosupervisor) {
            /// Get parent-context users.
            if ($supervisors = cm_get_users_by_capability('user', $this->userid, 'block/curr_admin:notify_classnotcomplete')) {
                $users = $users + $supervisors;
            }
        }

        foreach ($users as $user) {
            $message->send_notification($text, $user, $enroluser);
        }

        return true;
    }

    /**
     * Determines whether the current user is allowed to create, edit, and delete associations
     * between a user and a class
     *
     * @param    int      $userid    The id of the user being associated to the class
     * @param    int      $classid   The id of the class we are associating the user to
     * @uses     $DB
     * @uses     $USER;
     * @return   boolean             True if the current user has the required permissions, otherwise false
     */
    public static function can_manage_assoc($userid, $classid) {
        global $DB, $USER;

        if(!pmclasspage::can_enrol_into_class($classid)) {
            //the users who satisfty this condition are a superset of those who can manage associations
            return false;
        } else if (pmclasspage::_has_capability('block/curr_admin:track:enrol', $classid)) {
            //current user has the direct capability
            return true;
        }

        //get the context for the "indirect" capability
        $context = cm_context_set::for_user_with_capability('cluster', 'block/curr_admin:class:enrol_cluster_user', $USER->id);

        $allowed_clusters = array();
        $allowed_clusters = pmclass::get_allowed_clusters($classid);

        //query to get users associated to at least one enabling cluster
        $cluster_select = '';
        if(empty($allowed_clusters)) {
            $cluster_select = '0=1';
        } else {
            $cluster_select = 'clusterid IN (' . implode(',', $allowed_clusters) . ')';
        }
        $select = "userid = ? AND {$cluster_select}";

        //user just needs to be in one of the possible clusters
        if($DB->record_exists_select(clusteruser::TABLE, $select, array($userid))) {
            return true;
        }

        return false;
    }
}


class student_grade extends elis_data_object {
    const TABLE = GRDTABLE;
    const LANG_FILE = 'elis_program'; // TBD

    var $verbose_name = 'student_grade'; // TBD

    static $associations = array(
        'users'   => array('class' => 'user',
                           'idfield' => 'userid'),
        'pmclass' => array('class' => 'pmclass',
                           'idfield' => 'classid')
    );

/*
    var $id;                // INT - The data id if in the database.
    var $classid;           // INT - The class ID.
    var $userid;            // INT - The user ID.
    var $completionid;      // INT - Status code for completion.
    var $grade;             // INT - Student grade.
    var $locked;            // INT - Grade locked.
    var $timegraded;        // INT - The time graded.
    var $timemodified;      // INT - The time changed.

    var $_dbloaded;    // BOOLEAN - True if loaded from database.
*/

    private $form_url = null;  //moodle_url object

    protected $_dbfield_classid;
    protected $_dbfield_userid;
    protected $_dbfield_completionid;
    protected $_dbfield_grade;
    protected $_dbfield_locked;
    protected $_dbfield_timegraded;
    protected $_dbfield_timemodified;

    /**
     * Contructor.
     *
     * @param $studentdata int/object/array The data id of a data record or data elements to load manually.
     *
     */
/* **** disable constructor ****
    function student_grade($sgradedata=false) {
        $this->set_table(GRDTABLE);
        $this->add_property('id', 'int');
        $this->add_property('classid', 'int');
        $this->add_property('userid', 'int');
        $this->add_property('completionid', 'int');
        $this->add_property('grade', 'int');
        $this->add_property('locked', 'int');
        $this->add_property('timegraded', 'int');
        $this->add_property('timemodified', 'int');

        $this->completestatusid_values = array(
            STUSTATUS_NOTCOMPLETE => 'Not Completed',
            STUSTATUS_FAILED      => 'Failed',
            STUSTATUS_PASSED      => 'Passed'
        );

        $this->_editstyle = '
.attendanceeditform input,
.attendanceeditform textarea {
    margin: 0;
    display: block;
}
        ';

        if (is_numeric($sgradedata)) {
            $this->data_load_record($sgradedata);
        } else if (is_array($sgradedata)) {
            $this->data_load_array($sgradedata);
        } else if (is_object($sgradedata)) {
            $this->data_load_array(get_object_vars($sgradedata));
        }
    }
**** */

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  STANDARD FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    public static function delete_for_class($id) {
        global $DB;
        return $DB->delete_records(student_grade::TABLE, array('classid' => $id));
    }

    public static function delete_for_user($id) {
        global $DB;
        return $DB->delete_records(student_grade::TABLE, array('userid' => $id));
    }

    public static function delete_for_user_and_class($userid, $classid) {
        global $DB;
        return $DB->delete_records(student_grade::TABLE, array('userid' => $userid, 'classid' => $classid));
    }

/////////////////////////////////////////////////////////////////////
//                                                                 //
//  FORM FUNCTIONS:                                                //
//                                                                 //
/////////////////////////////////////////////////////////////////////

    /**
     * Return the HTML to edit a specific student.
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
    function edit_form_html($classid, $type = '', $sort = 'name', $dir = 'ASC', $page = 0,
                            $perpage = 0, $namesearch = '', $alpha = '') {
        global $CFG, $OUTPUT;

        $output = '';
        ob_start();

        $columns = array( // TBD
            'grade'      => array('header' => get_string('grade', self::LANG_FILE),
                                  'display_function' => 'htmltab_display_function'),
            'locked'     => array('header' => get_string('student_locked', self::LANG_FILE),
                                  'display_function' => 'htmltab_display_function'),
            'timegraded' => array('header' => get_string('date_graded', self::LANG_FILE),
                                  'display_function' => 'htmltab_display_function')
        );

        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        } else {
            $sort = 'grade'; // TBD
            $columns[$sort]['sortable'] = $dir;
        }
        //$table->width = "100%"; // TBD

        $newarr = array();
        $tabobj = new stdClass;
        foreach ($columns as $column => $cdesc) {
            switch ($column) {
                case 'timegraded':
                    $tabobj->{$column} = cm_print_date_selector('users[' . $user->id . '][startday]',
                                             'users[' . $user->id . '][startmonth]',
                                             'users[' . $user->id . '][startyear]',
                                             $this->timegraded, true);
                    break;

                case 'grade':
                    $tabobj->{$column} = '<input type="text" name="users[' . $user->id . '][grade]" ' .
                                'value="' . $this->grade . '" size="5" />';
                    break;

                case 'locked':
                    $tabobj->{$column} = '<input type="checkbox" name="users[' . $user->id . '][locked]" ' .
                                'value="1" '.($this->locked?'checked="checked"':'').'/>';
                    break;

                default:
                    $tabobj->{$column} = '';
                    break;
            }
            //$table->data[] = $newarr;
        }
        $newarr[] = $tabobj;
        // TBD: student_table() ???
        $table = new display_table($newarr, $columns, $this->get_base_url());

        if (empty($this->id)) {
            // TBD: move up and add pmalphabox() and pmshowmatches() ???
            pmsearchbox(null, 'search', 'get', get_string('show_all_users', self::LANG_FILE),
               '<input type="radio" name="stype" value="student" '.
                 (($type == 'student') ? ' checked' : '') .'/> '. get_string('students', self::LANG_FILE) .
               ' <input type="radio" name="stype" value="instructor" '.
                 (($type == 'instructor') ? ' checked' : '') .'/> '. get_string('instructors', self::LANG_FILE) .
               ' <input type="radio" name="stype" vale="" ' . (($type == '') ? ' checked' : '') . '/> '. get_string('all') .' ');

            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;class=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="savenew" />'."\n";
        } else {
            echo '<form method="post" action="index.php?s=stu&amp;section=curr&amp;class=' . $classid . '" >'."\n";
            echo '<input type="hidden" name="action" value="update" />'."\n";
            echo '<input type="hidden" name="association_id" value="' . $this->id . '" />' . "\n";
            echo '<input type="hidden" name="id" value="' . $this->classid . '" />' . "\n";
            echo '<input type="hidden" name="userid" value="' . $this->userid . '" />' . "\n";
        }

        if (!empty($newarr)) { // TBD: $newarr or $table?
            echo $table->get_html();
        }

        if (empty($this->id)) {
            echo '<br /><input type="submit" value="' . get_string('add_grade', self::LANG_FILE) . '">'."\n";
        } else {
            echo '<br /><input type="submit" value="' . get_string('update_grade', self::LANG_FILE) . '">'."\n";
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
     * Check for a duplicate record when doing an insert.
     *
     * @param object $record The record we want to insert.
     */
    function duplicate_check($record=null) {

        if(empty($record)) {
            $record = $this;
        }

        if ($this->_db->record_exists(student_grade::TABLE, array('classid' => $record->classid, 'userid' => $record->userid, 'completionid' => $record->completionid))) {
            return true;
        }
        return false;
    }

    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }

}

/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)

/**
 * Gets a student listing with specific sort and other filters.
 *
 * @param int $classid The class ID.
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for student name.
 * @param string $alpha Start initial of student name filter.
 * @uses $DB
 * @return object array Returned records.
 */
function student_get_listing($classid, $sort='name', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                             $alpha='') {
    global $DB;
    $params = array();
    $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $FULLNAME_LIKE = $DB->sql_like($FULLNAME, ':name_like'); // 'name' breaks
    $IDNUMBER_LIKE = $DB->sql_like('usr.idnumber', ':id_like');
    $LASTNAME_STARTSWITH = $DB->sql_like('usr.lastname', ':lastname_startswith');

    $select  = 'SELECT stu.* ';
    $select .= ', ' . $FULLNAME . ' as name, usr.idnumber ';
//    $select .= ', ' . $FULLNAME . ' as name, usr.type as description ';
    $tables  = 'FROM {'. student::TABLE .'} stu ';
    $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
    $on      = 'ON stu.userid = usr.id ';
    $where   = 'stu.classid = :clsid ';
    $params['clsid'] = $classid;

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : ' ') .'(('. $FULLNAME_LIKE .') OR ('. $IDNUMBER_LIKE .')) ';
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
    return $DB->get_records_sql($sql, $params, $startrec, $perpage);
}

/**
 * Count the number of students for this class.
 *
 * @uses $DB
 * @param int $classid The class ID.
 */
function student_count_records($classid, $namesearch = '', $alpha = '') {
    global $DB;
    $params = array();
    $FULLNAME = $DB->sql_concat('usr.firstname', "' '", 'usr.lastname');
    $FULLNAME_LIKE = $DB->sql_like($FULLNAME, ':name_like');
    $LASTNAME_STARTSWITH = $DB->sql_like('usr.lastname', ':lastname_startswith');

    $select  = 'SELECT COUNT(stu.id) ';
    $tables  = 'FROM {'. student::TABLE .'} stu ';
    $join    = 'LEFT JOIN {'. user::TABLE .'} usr ';
    $on      = 'ON stu.userid = usr.id ';
    $where   = 'stu.classid = :clsid ';
    $params['clsid'] =  $classid;

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $where .= (!empty($where) ? ' AND ' : ' ') .'('. $FULLNAME_LIKE .') ';
        $params['name_like'] = "%{$namesearch}%";
    }

    if ($alpha) {
        $where .= (!empty($where) ? ' AND ' : ' ') .'('. $LASTNAME_STARTSWITH .') ';
        $params['lastname_startswith'] = "{$alpha}%";
    }

    if (!empty($where)) {
        $where = "WHERE $where ";
    }

    $sql = $select . $tables . $join . $on . $where;
    return $DB->count_records_sql($sql, $params);
}

/**
 * Get a full list of the classes that a student is enrolled in.
 *
 * @param int $userid The user ID to get classes for.
 * @param int $curid  Optional curriculum ID to limit classes to.
 * @uses $DB
 * @return array An array of class and student enrolment data.
 * TBD: double-check tables!!!
 */
function student_get_student_classes($userid, $curid = 0) {
    global $DB;

    $params = array();
    if (empty($curid)) {
        $sql = 'SELECT cls.*, stu.enrolmenttime, stu.completetime, stu.completestatusid
                FROM {'. student::TABLE .'} stu
                INNER JOIN {'. pmclass::TABLE .'} cls ON stu.classid = cls.id
                WHERE stu.userid = ? ';
                $params[] = $userid;
    } else {
        $sql = 'SELECT cls.*, stu.enrolmenttime, stu.completetime, stu.completestatusid
                FROM {'. student::TABLE .'} stu
                INNER JOIN {'. pmclass::TABLE .'} cls ON stu.classid = cls.id
                INNER JOIN {'. course::TABLE .'} curcrs ON cls.courseid = curcrs.courseid
                WHERE stu.userid = ?
                AND curcrs.curriculumid = ? ';
                $params[] = $userid;
                $params[] = $curid;
    }
    return $DB->get_records_sql($sql, $params);
}

/**
 * Attempt to get the class information about a class that a student is enrolled
 * in for a specific course in the system.
 *
 * @param int $crsid The course ID
 * @uses $DB
 * @return
 */
function student_get_class_from_course($crsid, $userid) {
    global $DB;
    $params = array();
    $sql = 'SELECT cls.*, stu.enrolmenttime, stu.completetime, stu.completestatusid, stu.grade
            FROM {'. student::TABLE .'} stu
            INNER JOIN {'. pmclass::TABLE .'} cls ON stu.classid = cls.id
            WHERE stu.userid = ?
            AND cls.courseid = ? ';
    $params[] = $userid;
    $params[] = $crsid;
    return $DB->get_record_sql($sql, $params);
}

