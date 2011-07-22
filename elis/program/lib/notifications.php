<?php
/**
 * ELIS Notifications Class Definitions
 *
 * Contains all of the classes necessary to provide a notifications API for
 * the ELIS system.
 * This file includes functions from 2.0 that do not exist in 1.9. In particular:
 *  - functions from '/lib/messagelib.php'
 *
 * Notifications can be sent by instantiating an object and calling send_message, or
 * by using the static 'notify' function.
 *
 * Object Example:
 *      $message = new notification();
 *      $message->send_notification("This is a test", $USER);
 *
 * Static Example:
 *      notification::notify("This is a test", $USER);
 *
 * The send_handler function, while public, is intended to be used by the events
 * system only. It is registered in the '/block/cur_admin/events.php' file.
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

global $CFG;
require_once ($CFG->dirroot.'/elis/program/lib/setup.php');
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/instructor.class.php');

class message {

    /// In 2.0, the message_send_handler in /lib/messagelib takes a structure with defined variables below.
    /// These are loaded into a generic object. They are defined here for better documentation, and should
    /// not have to be replaced when upgraded to 2.0.
    /// If a message class is created, and is the same as this, it can be removed here and the notification
    /// class below can extend it instead.
    // TODO: Not sure if we need to extend message_output_email or class_message_output

    public    $modulename;                // The name of the Moodle subsystem generating the event.
    public    $component;                 // The component in the subsystem.
    public    $name;                      // A descriptive name for this event.
    public    $userfrom;                  // The sending user object.
    public    $userto;                    // The receiving user object.
    public    $subject;                   // Subject for the message.
    public    $fullmessage;               // Full text of the message.
    public    $fullmessageformat;         // Format for the message (FORMAT_PLAIN, FORMAT_HTML).
    public    $fullmessagehtml;           // Full HTML of the message.
    public    $smallmessage;              // Brief version of the message.
/*
 * ---------------------------------------------------------------------------------------
 *
 * PUBLIC FUNCTIONS
 *
 */

    public function __construct() {

        $site = get_site();

        $this->modulename = '';
        $this->component = '';
        $this->name = '';
        $this->userfrom = '';
        $this->userto = '';
        $this->subject = '';
        $this->fullmessage = '';
        $this->fullmessageformat = FORMAT_PLAIN;
        $this->fullmessagehtml = '';
        $this->smallmessage = '';
    }

    public function __destruct() {
    }
/*
 * ---------------------------------------------------------------------------------------
 *
 * PRIVATE FUNCTIONS
 *
 */

/*
 * ---------------------------------------------------------------------------------------
 */
}

class notificationlog extends elis_data_object {
    const TABLE = 'crlm_notification_log';

    var $verbose_name = 'notificationlog';

    /**
     * Notification log ID-number
     * @var    int
     * @length 10
     */
    protected $_dbfield_id;
    /**
     * Notification log event string
     * @var    char
     * @length 166
     */
    protected $_dbfield_event;
    protected $_dbfield_instance;
    protected $_dbfield_userid;
    protected $_dbfield_data;
    protected $_dbfield_timecreated;

    private $location;
    private $templateclass;

    static $associations = array(
        'userid' => array(
            'class' => 'user',
            'idfield' => 'userid'
        )
    );
}

class notification extends message {
    /// In 2.0, the message_send_handler in /lib/messagelib takes a structure with defined variables below.
    /// These are loaded into a generic object. They are defined here for better documentation, and should
    /// not have to be replaced when upgraded to 2.0.
    /// If a message class is created, and is the same as this, it can be removed here and the notification
    /// class below can extend it instead.

    private $defname;
    private $defsubject;
/*
 * ---------------------------------------------------------------------------------------
 *
 * PUBLIC FUNCTIONS
 *
 */

    public function __construct() {

        parent::__construct();

        $this->modulename = 'curriculum';
        $this->component = 'notifications';

        $this->defname    = "message";
        $site = get_site();
        $this->defsubject = "Message Event from {$site->fullname}";
    }

    public function __destruct() {
    }

    /*
     * Function to notify required users about an event. Assumes most parameters have been
     * preset.
     *
     * @param char $message The text of the message.
     * @param object $userto A Moodle generic user object, or a PM user class object that the message is to.
     * @param object $userfrom A Moodle generic user object, or a PM user class object that the message is from.
     * @param object $logevent Information to log to 'crlm_notification_log'.
     *
     */
    public function send_notification($message='', $userto=null, $userfrom=null, $logevent=false) {
        global $DB, $USER;

        /// Handle parameters:
        if (!empty($userto)) {
            $this->userto = $userto;
        } else if (empty($this->userto)) {
            print_error('message_nodestinationuser', 'elis_program');
            return false;
        }

        if (!empty($userfrom)) {
            $this->userfrom = $userfrom;
        } else if (empty($this->userfrom)) {
            $this->userfrom = $USER;
        }

        if ($message != '') {
            $this->fullmessage = $message;
        }

        /// Check for the user object type. If a PM User was sent in, need to get the Moodle object.
        $topmuserid = false;
        if (get_class($this->userto) == 'user') {
            $topmuserid = $this->userto->id;
//            if (!($this->userto = cm_get_moodleuser($this->userto->id))) {
            if (!($this->userto = $this->userto->get_moodleuser())) {
                debugging(get_string('nomoodleuser', 'elis_program'));
            }
        }
        if (get_class($this->userfrom) == 'user') {
//            if (!($this->userfrom = cm_get_moodleuser($this->userfrom->id))) {
            if (!($this->userfrom = $this->userfrom->get_moodleuser())) {
                debugging(get_string('nomoodleuser', 'elis_program'));
            }
        }

        /// Handle unset variables:
        $this->name    = ($this->name == '') ? $this->defname : $this->name;
        $this->subject = ($this->subject == '') ? $this->defsubject : $this->subject;

        $eventname='message_send';
        events_trigger($eventname, $this);
    /// Insert a notification log if we have data for it.
        if ($logevent !== false) {
            if (!empty($logevent->event)) {
                $newlog = new Object();
                $newlog->event = $logevent->event;
                if (isset($logevent->userid)) {
                    $newlog->userid = $logevent->userid;
                } else if ($topmuserid === false){
                    $newlog->userid = pm_get_crlmuserid($this->userto->id);
                } else {
                    $newlog->userid = $topmuserid;
                }
                if (isset($logevent->instance)) {
                    $newlog->instance = $logevent->instance;
                }
                if (isset($logevent->data)) {
                    $newlog->data = $logevent->data;
                }
                if (isset($logevent->timecreated)) {
                    $newlog->timecreated = $logevent->timecreated;
                } else {
                    $newlog->timecreated = time();
                }
                $DB->insert_record('crlm_notification_log', $newlog);
            }
        }
    }

    /*
     * ---------------------------------------------------------------------------------------
     * STATIC API FUNCTIONS:
     *
     * These functions can be used without instatiating an object.
     * Usage: message::[function_name([args])]
     *
     */

    /*
     * Function to notify required users about an event. Assumes most parameters have been
     * preset.
     *
     */
    public static function notify($text='', $userto=null, $userfrom=null) {

        $message = new notification();
        $message->send_notification($text, $userto, $userfrom);
    }

    /*
     * ---------------------------------------------------------------------------------------
     * STATIC EVENT HANDLERS:
     *
     * These functions are intended to be loaded as event handlers with a function string
     * like "notification::send_handler". Unfortunately, pre-PHP 5.2.3, the PHP function
     * 'call_user_func' can't take 'static' functions, and therefore this will not work. They
     * are re-created outside of the class as stand-alone functions as well. These need to be
     * used to work with versions before 5.2.3.
     * When the system is updated to work with Moodle 2.0, we can move the handlers below back
     * into this class as static functions.
     */

    public static function send_handler($eventdata){
        return pm_notify_send_handler($eventdata);
    }

    public static function role_assign_handler($eventdata){
        return pm_notify_role_assign_handler($eventdata);
    }
/*
 * ---------------------------------------------------------------------------------------
 *
 * PRIVATE FUNCTIONS
 *
 */

/*
 * ---------------------------------------------------------------------------------------
 */
}

/*
 * ---------------------------------------------------------------------------------------
 * NOTIFICATION EVENT HANDLERS:
 *
 * These functions are intended to be loaded as event handlers with a function string
 * like "notification::send_handler". Unfortunately, pre-PHP 5.2.3, the PHP function
 * 'call_user_func' can't take 'static' functions, and therefore this will not work. They
 * are re-created outside of the class as stand-alone functions as well. These need to be
 * used to work with versions before 5.2.3.
 * When the system is updated to work with Moodle 2.0, we can move the handlers below back
 * into the class above as static functions.
 */

/**
 * Triggered when a message provider wants to send a message.
 * This functions checks the user's processor configuration to send the given type of message,
 * then tries to send it.
 * @param object $eventdata information about the message (origin, destination, type, content)
 * @return boolean success
 *
 * @todo Replace with 2.0 functionality
 * COPIED FROM 2.0 '/lib/messagelib.php' - This can be removed after upgrade to 2.0
 *
 */
function pm_notify_send_handler($eventdata){
    global $CFG;

    /// For 1.9, just user the messaging system until we have recreated the 2.0
    /// functionality.
    require_once($CFG->dirroot.'/message/lib.php');
    message_post_message($eventdata->userfrom, $eventdata->userto, $eventdata->fullmessage, $eventdata->fullmessageformat); //, 'direct'
    return true;

//        global $CFG, $DB;
//
//        if (isset($CFG->block_online_users_timetosee)) {
//            $timetoshowusers = $CFG->block_online_users_timetosee * 60;
//        } else {
//            $timetoshowusers = TIMETOSHOWUSERS;
//        }
//
//    /// Work out if the user is logged in or not
//        if ((time() - $eventdata->userto->lastaccess) > $timetoshowusers) {
//            $userstate = 'loggedoff';
//        } else {
//            $userstate = 'loggedin';
//        }
//
//    /// Create the message object
//        $savemessage = new object();
//        $savemessage->useridfrom        = $eventdata->userfrom->id;
//        $savemessage->useridto          = $eventdata->userto->id;
//        $savemessage->subject           = $eventdata->subject;
//        $savemessage->fullmessage       = $eventdata->fullmessage;
//        $savemessage->fullmessageformat = $eventdata->fullmessageformat;
//        $savemessage->fullmessagehtml   = $eventdata->fullmessagehtml;
//        $savemessage->smallmessage      = $eventdata->smallmessage;
//        $savemessage->timecreated       = time();
//
//    /// Find out what processors are defined currently
//    /// When a user doesn't have settings none gets return, if he doesn't want contact "" gets returned
//        $processor = get_user_preferences('message_provider_'.$eventdata->component.'_'.$eventdata->name.'_'.$userstate, NULL, $eventdata->userto->id);
//
//        if ($processor == NULL){ //this user never had a preference, save default
//            if (!message_set_default_message_preferences( $eventdata->userto )){
//                print_error('cannotsavemessageprefs', 'message');
//            }
//            if ( $userstate == 'loggedin'){
//                $processor='popup';
//            }
//            if ( $userstate == 'loggedoff'){
//                $processor='email';
//            }
//        }
//
//        //if we are suposed to do something with this message
//        // No processor for this message, mark it as read
//        if ($processor == "") {  //this user cleared all the preferences
//            $savemessage->timeread = time();
//            $messageid = $message->id;
//            unset($message->id);
//            $DB->insert_record('message_read', $savemessage);
//
//        } else {                        // Process the message
//        /// Store unread message just in case we can not send it
//            $savemessage->id = $DB->insert_record('message', $savemessage);
//
//        /// Try to deliver the message to each processor
//            $processorlist = explode(',', $processor);
//            foreach ($processorlist as $procname) {
//                $processorfile = $CFG->dirroot. '/message/output/'.$procname.'/message_output_'.$procname.'.php';
//
//                if (is_readable($processorfile)) {
//                    include_once( $processorfile );  // defines $module with version etc
//                    $processclass = 'message_output_' . $procname;
//
//                    if (class_exists($processclass)) {
//                        $pclass = new $processclass();
//
//                        if (! $pclass->send_message($savemessage)) {
//                            debugging('Error calling message processor '.$procname);
//                            return false;
//                        }
//                    }
//                } else {
//                    debugging('Error calling message processor '.$procname);
//                    return false;
//                }
//            }
//        }
//
//        return true;
}

/**
 *
 * Takes a role assignment event from Moodle and assigns class instructors
 * in curriculum admin appropriately, based on "course manager" roles
 *
 * @param  stdClass  $eventdata  The appropriate role_assignments record
 *
 */
function pm_assign_instructor_from_mdl($eventdata) {

    global $CFG, $DB;

    //make sure we have course manager roles defined
    if(empty($CFG->coursemanager)) {
        return;
    }

    //retrieve the appropriate roles
    $valid_instructor_roles = explode(',', $CFG->coursemanager);

    //make sure the assigned role is one of the ones we care about
    if(!in_array($eventdata->roleid, $valid_instructor_roles)) {
        return;
    }

    //get the id of the appropriate curriculum user
    if(!$instructorid = pm_get_crlmuserid($eventdata->userid)) {
        return;
    }

    //get the curriculum user object
    $instructor = new user($instructorid);

    //get the role assignment context
    if(!$context = $DB->get_record('context', array('id'=> $eventdata->contextid))) {
        return;
    }

    //make sure we're using a course context
    if($context->contextlevel != CONTEXT_COURSE) {
        return;
    }

    //make sure the Moodle course is not tied to other curriculum administartion classes
    if($DB->count_records(classmoodlecourse::TABLE, array('moodlecourseid'=> $context->instanceid)) != 1) {
        return true;
    }

    //make sure the course is tied to at least one class
    if(!$crlm_class = $DB->get_record(classmoodlecourse::TABLE, array('moodlecourseid'=> $context->instanceid))) {
        return;
    }

    //add user as instructor for the appropriate class

    if(!$DB->record_exists(instructor::TABLE, array('classid'=> $crlm_class->classid,
                                                    'userid'=> $instructorid))) {
        $ins_record = new instructor(array('classid' => $crlm_class->classid,
                                           'userid' => $instructorid,
                                           'assigntime' => $eventdata->timestart,
                                           'completetime' => $eventdata->timeend));
        $ins_record->save();
    }

}

/**
 *
 * Takes a role assignment event from Moodle and assigns class enrolment
 * in curriculum admin appropriately, based on "gradebook" roles
 *
 * @param  stdClass  $eventdata  The appropriate role_assignments record
 *
 */
function pm_assign_student_from_mdl($eventdata) {
    global $CFG, $DB;

    /// We get all context assigns, so check that this is a class. If not, we're done.
    if (!($context = get_context_instance_by_id($eventdata->contextid))) {
        print_error('invalidcontext');
        return true;
    } else if ($context->contextlevel != CONTEXT_COURSE) {
        return true;
    }

    $pmuserid = pm_get_crlmuserid($eventdata->userid);
    if (!$pmuserid) {
        return;
    }

    $gradebookroles = explode(',',$CFG->gradebookroles);
    if (!in_array($eventdata->roleid, $gradebookroles)) {
        return;
    }

    /// synchronize enrolment to ELIS class (if applicable)
    require_once elispm::lib('data/classmoodlecourse.class.php');
    require_once elispm::lib('data/student.class.php');
    $classes = $DB->get_records(classmoodlecourse::TABLE, array('moodlecourseid'=> $context->instanceid));
    if (count($classes) == 1) { // only if course is associated with one class
        $class = current($classes);
        if (!$DB->get_record(student::TABLE, array('classid'=> $class->classid,
                                                   'userid'=> $pmuserid))) {
            $sturec = new Object();
            $sturec->classid = $class->classid;
            $sturec->userid = $pmuserid;
            /// Enrolment time will be the earliest found role assignment for this user.
            $enroltime = $DB->get_field('role_assignments', 'MIN(timestart) as enroltime',
                                        array('contextid'=> $context->id,
                                              'userid'=> $eventdata->userid));
            $sturec->enrolmenttime = (!empty($enroltime) ? $enroltime : $timenow);
            $sturec->completetime = 0;
            $sturec->completestatusid = STUSTATUS_NOTCOMPLETE;
            $sturec->grade = 0;
            $sturec->credits = 0;
            $sturec->locked = 0;
            $sturec->id = $DB->insert_record(STUTABLE, $sturec);
        }
    }
}

/**
 * Triggered when a role assignment takes place.
 * This function should use the CM configured values to send messages to appropriate users when a role assignment
 * takes place. Users will be ones configured for the context, which can include the user that is assigned and users
 * assigned to configured roles for that context. The message template used should be the one configured as well.
 *
 * @param object $eventdata the role assignment record
 * @return boolean success
 *
 */
function pm_notify_role_assign_handler($eventdata){
    global $CFG, $DB, $USER;

    pm_assign_instructor_from_mdl($eventdata);
    pm_assign_student_from_mdl($eventdata);

    /// Does the user receive a notification?
    $sendtouser = !empty(elis::$config->elis_program->notify_classenrol_user) ?
                      elis::$config->elis_program->notify_classenrol_user : 0;
    $sendtorole = !empty(elis::$config->elis_program->notify_classenrol_role) ?
                      elis::$config->elis_program->notify_classenrol_role : 0;
    $sendtosupervisor = !empty(elis::$config->elis_program->notify_classenrol_supervisor) ?
                      elis::$config->elis_program->notify_classenrol_supervisor : 0;

    /// If nobody receives a notification, we're done.
    if (!$sendtouser && !$sendtorole && !$sendtosupervisor) {
        return true;
    }

    /// We get all context assigns, so check that this is a class. If not, we're done.
    if (!($context = get_context_instance_by_id($eventdata->contextid))) {
        print_error('invalidcontext');
        return true;
    } else if ($context->contextlevel != CONTEXT_COURSE) {
        return true;
    }

    /// Make sure this is a valid user.
    if (!($enroluser = $DB->get_record('user', array('id'=> $eventdata->userid)))) {
        debugging(get_string('nomoodleuser', 'elis_program'));
        return true;
    }

    /// Get the course record from the context id.
    if (!($course = $DB->get_record('course', array('id'=> $context->instanceid)))) {
        print_error('invalidcourse');
        return true;
    }

    $message = new notification();

    /// Set up the text of the message
    $text = empty(elis::$config->elis_program->notify_classenrol_message) ?
                  get_string('notifyclassenrolmessagedef', 'elis_program') :
                  elis::$config->elis_program->notify_classenrol_message;
    $search = array('%%userenrolname%%', '%%classname%%');
    $replace = array(fullname($enroluser), $course->fullname);
    $text = str_replace($search, $replace, $text);

    if ($sendtouser) {
        $message->send_notification($text, $enroluser);
    }

    $users = array();

    if ($sendtorole) {
        // Get all users with the notify_classenrol capability.
        if ($roleusers = get_users_by_capability($context, 'block/curr_admin:notify_classenrol')) {
            $users = $users + $roleusers;
        }
    }

    if ($sendtosupervisor) {
        $pmuserid = pm_get_crlmuserid($eventdata->userid);
        // Get all users with the notify_classenrol capability.
        if ($supervisors = pm_get_users_by_capability('user', $pmuserid, 'block/curr_admin:notify_classenrol')) {
            $users = $users + $supervisors;
        }
    }

    foreach ($users as $user) {
        $message->send_notification($text, $user, $enroluser);
    }

    /// If you don't return true, the message queue will clog and no more will be sent.
    return true;
}

/**
 *
 * Triggered when a role unassignment takes place.
 * @param $eventdata
 * @return unknown_type
 */
function pm_notify_role_unassign_handler($eventdata){
    global $CFG, $DB;

    //make sure we have course manager roles defined
    if(empty($CFG->coursemanager)) {
        return true;
    }

    //retrieve the list of role ids we want to sync to curriculum admin
    $valid_instructor_roles = explode(',', $CFG->coursemanager);

    //make sure we actually care about the current role
    if(!in_array($eventdata->roleid, $valid_instructor_roles)) {
        return true;
    }

    //prevent removal from curriculum admin if the user still has an appropriate role in Moodle
    foreach($valid_instructor_roles as $valid_instructor_role) {
        if(user_has_role_assignment($eventdata->userid, $eventdata->roleid, $eventdata->contextid)) {
            return true;
        }
    }

    //retrieve the course context
    if(!$course_context = $DB->get_record('context',
                                          array('contextlevel'=> CONTEXT_COURSE,
                                                'id'=> $eventdata->contextid))) {
        return true;
    }

    //if the course is not tied to any curriculum admin classes, then we are done
    if(!$associated_classes = $DB->get_records(classmoodlecourse::TABLE, array('moodlecourseid'=> $course_context->instanceid))) {
        return true;
    }

    //retrieve the curriculum admin user's id
    if(!$crlm_userid = pm_get_crlmuserid($eventdata->userid)) {
        return true;
    }

    //clear out instructor assignments in all associated classes
    foreach($associated_classes as $associated_class) {
        if($instructor_record = $DB->get_record(instructor::TABLE, array('classid'=> $associated_class->classid,
                                                                         'userid'=>  $crlm_userid))) {
            $delete_record = new instructor($instructor_record->id);
            $delete_record->delete();
        }
    }

    return true;
}

/**
 * Triggered when a track assignment takes place.
 * This function should use the CM configured values to send messages to appropriate users when a track assignment
 * takes place. Users will be ones configured for the context, which can include the user that is assigned and users
 * assigned to configured roles for that context. The message template used should be the one configured as well.
 *
 * @param object $eventdata the track assignment record
 * @return boolean success
 *
 */
function pm_notify_track_assign_handler($eventdata){
    global $CFG, $DB, $USER;

    /// Does the user receive a notification?
    $sendtouser       = isset(elis::$config->elis_program->notify_trackenrol_user) ? elis::$config->elis_program->notify_trackenrol_user : '';
    $sendtorole       = isset(elis::$config->elis_program->notify_trackenrol_role) ? elis::$config->elis_program->notify_trackenrol_role : '';
    $sendtosupervisor = isset(elis::$config->elis_program->notify_trackenrol_supervisor) ? elis::$config->elis_program->notify_trackenrol_supervisor : '';

    /// If nobody receives a notification, we're done.
    if (!$sendtouser && !$sendtorole && !$sendtosupervisor) {
        return true;
    }

    /// We get all context assigns, so check that this is a class. If not, we're done.
    $context = get_system_context();

    /// Make sure this is a valid user.
    $enroluser = new user($eventdata->userid);
    // Due to lazy loading, we need to pre-load this object
    $enroluser->load();
    if (empty($enroluser->id)) {
        print_error('nouser', 'elis_program');
        return true;
    }

    /// Get the track record from the track id.
    if (!($track = $DB->get_record('crlm_track', array('id'=> $eventdata->trackid)))) {
        print_error('notrack', 'elis_program');
        return true;
    }

    $message = new notification();

    /// Set up the text of the message
    $text = empty(elis::$config->elis_program->notify_trackenrol_message) ?
                  get_string('notifytrackenrolmessagedef', 'elis_program') :
                  elis::$config->elis_program->notify_trackenrol_message;
    $search = array('%%userenrolname%%', '%%trackname%%');
    $replace = array(fullname($enroluser), $track->name);
    $text = str_replace($search, $replace, $text);

    if ($sendtouser) {
        $message->send_notification($text, $enroluser);
    }

    $users = array();

    if ($sendtorole) {
        /// Get all users with the notify_trackenrol capability.
        if ($roleusers = get_users_by_capability($context, 'block/curr_admin:notify_trackenrol')) {
            $users = $users + $roleusers;
        }
    }

    if ($sendtosupervisor) {
        /// Get parent-context users.
        if ($supervisors = pm_get_users_by_capability('user', $eventdata->userid, 'block/curr_admin:notify_trackenrol')) {
            $users = $users + $supervisors;
        }
    }

    foreach ($users as $user) {
        $message->send_notification($text, $user, $enroluser);
    }

    /// If you don't return true, the message queue will clog and no more will be sent.
    return true;
}

/*
 * Takes an instructor assignment event and propagates and appropriate
 * course role assignment back to moodle based on the configured default role
 *
 * @param  stdClass  $eventdata  The appropriate crlm_class_instructor record
 *
 */
function pm_notify_instructor_assigned_handler($eventdata) {
    global $CFG, $CURMAN;

    //make sure we actually have a default instructor role specified
    if(empty(elis::$config->elis_program->default_instructor_role)) {
        return true;
    }

    //get our curriculum admin class
    if(!$pmclass = new pmclass($eventdata->classid)) {
        return true;
    }

    //make sure our class is tied to a Moodle course
    if(empty($pmclass->moodlecourseid)) {
        return true;
    }

    //retrieve the Moodle course's context
    if(!$course_context = get_context_instance(CONTEXT_COURSE, $pmclass->moodlecourseid)) {
        return true;
    }

    //retrieve the appropriate Moodle user based on the event's curriculum admin user
    if(!$instructor = cm_get_moodleuser($eventdata->userid)) {
        return true;
    }

    //make sure the Moodle user does not already have a Non-Editing Teacher, Teacher or Admin role in the course
    if(has_capability('moodle/course:viewhiddenactivities', $course_context, $instructor->id)) {
        return true;
    }

    /* TODO: roles must be ported to ELIS2
    //assign the Moodle user to the default instructor role
    role_assign(elis::$config->elis_program->default_instructor_role, $instructor->id, 0, $course_context->id,
                $eventdata->assigntime, $eventdata->completetime);
*/
    return true;
}

/**
 *
 * Takes an instructor unassignment and deletes the appropriate
 * Moodle course role assignments
 *
 * @param  stdClass  $eventdata  The appropriate crlm_class_instructor record
 */
function pm_notify_instructor_unassigned_handler($eventdata) {

    global $CFG, $DB;

    //make sure users in some roles are identified as course managers
    if(empty($CFG->coursemanager)) {
        return true;
    }

    //create the curriculum administration class
    if(!$pmclass = new pmclass($eventdata->classid)) {
        return true;
    }

    //ensure that the class is tied to a Moodle course
    if(empty($pmclass->moodlecourseid)) {
        return true;
    }

    //retrieve the context for the Moodle course
    if(!$course_context = get_context_instance(CONTEXT_COURSE, $pmclass->moodlecourseid)) {
        return true;
    }

    //make sure the Moodle course is not tied to other curriculum administartion classes
    if($DB->count_records(classmoodlecourse::TABLE, array('moodlecourseid'=> $pmclass->moodlecourseid)) != 1) {
        return true;
    }

    //retrieve the Moodle user
    if(!$instructor = cm_get_moodleuser($eventdata->userid)) {
        return true;
    }

    //go through all applicable roles to see if we can remove them from the Moodle side of things
    $roleids = explode(',', $CFG->coursemanager);

    foreach($roleids as $roleid) {

         /* TODO: roles must be ported to ELIS2
        //unassign the role if found
        if(user_has_role_assignment($instructor->id, $roleid, $course_context->id)) {
            role_unassign($roleid, $instructor->id, 0, $course_context->id);
        }*/
    }

    return true;
}

?>
