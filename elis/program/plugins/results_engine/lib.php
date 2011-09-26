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
 * @subpackage user_activity
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::lib('data/usertrack.class.php');
require_once elis::lib('data/customfield.class.php');


define('RESULTS_ENGINE_LANG_FILE', 'pmplugins_results_engine');

// max out at 2 minutes (= 120 seconds)
define('RESULTS_ENGINE_USERACT_TIME_LIMIT', 120);

define('RESULTS_ENGINE_GRADE_SET', 1);
define('RESULTS_ENGINE_SCHEDULED', 2);
define('RESULTS_ENGINE_MANUAL',    3);

define('RESULTS_ENGINE_AFTER_START', 1);
define('RESULTS_ENGINE_BEFORE_END',  2);
define('RESULTS_ENGINE_AFTER_END',   3);

define('RESULTS_ENGINE_ASSIGN_TRACK',   1);
define('RESULTS_ENGINE_ASSIGN_CLASS',   2);
define('RESULTS_ENGINE_UPDATE_PROFILE', 3);

/**
 * Check if class results are ready to processed and if so process them
 */
function results_engine_cron() {
    $rununtil = time() + RESULTS_ENGINE_USERACT_TIME_LIMIT;

    $actives = results_engine_get_active();

    foreach ($actives as $active) {
        $active = results_engine_check($active);

        if ($active->proceed) {
            results_engine_process($active);
        }

        if (time() >= $rununtil) {
            break;
        }
    }
}

/**
 * Check if this class is ready to be processed
 *
 * Class properties:
 *   id               - id of class
 *   startdate        - startdate of class (0 means unset)
 *   enddate          - enddate of class (0 means unset)
 *   triggerstartdate - the type of start date trigger used (see defines in results_engine plugin)
 *   days             - number of days to offset triggerstartdate
 *
 * @param object $class An object with the important class properties
 * @return bool Whether the class is ready to be processed
 */
function results_engine_check($class) {
    $class->proceed = false;
    $class->rundate = time();

    $offset = $class->days * 86400;

    if ($class->triggerstartdate == RESULTS_ENGINE_AFTER_START) {
        if ($class->startdate <= 0) {
            print(get_string('no_start_date_set', RESULTS_ENGINE_LANG_FILE, $class));
            return $class;
        }
        $class->scheduleddate = $class->startdate + $offset;
    } else {
        if ($class->enddate <= 0) {
            print(get_string('no_end_date_set', RESULTS_ENGINE_LANG_FILE, $class) ."\n");
            return $class;
        }

        if ($class->triggerstartdate == RESULTS_ENGINE_BEFORE_END) {
            $offset = -$offset;
        }
        $class->scheduleddate = $class->enddate + $offset;
    }
    if ($class->rundate > $class->scheduleddate) {
        $class->proceed = true;
    }

    return $class;
}

/**
 * Get the results engines that are active
 *
 * Properties of returned objects:
 *   id               - id of class
 *   startdate        - startdate of class (0 means unset)
 *   enddate          - enddate of class (0 means unset)
 *   triggerstartdate - the type of start date trigger used (see defines in results_engine plugin)
 *   days             - number of days to offset triggerstartdate
 *   criteriatype     - what mark to look at, 0 for final mark, anything else is an element id
 *
 * @return array An array of class objects
 * @uses $CFG
 * @uses $DB
 */
function results_engine_get_active() {
    global $CFG, $DB;

    $courselevel = context_level_base::get_custom_context_level('course', 'elis_program');
    $classlevel  = context_level_base::get_custom_context_level('class', 'elis_program');

    $fields = array('cls.id', 'cls.idnumber', 'cls.startdate', 'cls.enddate',  'cre.id as engineid',
                    'cre.triggerstartdate', 'cre.criteriatype', '0 as days');

    // Get course level instances that have not been overriden or already run.
    $sql = 'SELECT '. implode(', ', $fields)
         ." FROM {$CFG->prefix}crlm_results_engine cre"
         ." JOIN {$CFG->prefix}context c ON c.id = cre.contextid AND c.contextlevel=?"
         ." JOIN {$CFG->prefix}crlm_course cou ON cou.id = c.instanceid"
         ." JOIN {$CFG->prefix}crlm_class cls ON cls.courseid = cou.id"
         ." JOIN {$CFG->prefix}context c2 on c2.instanceid=cls.id AND c2.contextlevel=?"
         ." LEFT JOIN {$CFG->prefix}crlm_results_engine cre2 ON cre2.contextid=c2.id AND cre2.active=1"
         ." LEFT JOIN {$CFG->prefix}crlm_results_engine_class_log crecl ON crecl.classid=cls.id"
         .' WHERE cre.active=1'
         .  ' AND cre.eventtriggertype=?'
         .  ' AND cre2.active IS NULL'
         .  ' AND crecl.daterun IS NULL'
         .' UNION'
    // Get class level instances that have not been already run.
         .' SELECT '. implode(', ', $fields)
         ." FROM {$CFG->prefix}crlm_results_engine cre"
         ." JOIN {$CFG->prefix}context c ON c.id = cre.contextid AND c.contextlevel=?"
         ." JOIN {$CFG->prefix}crlm_class cls ON cls.id = c.instanceid"
         ." LEFT JOIN {$CFG->prefix}crlm_results_engine_class_log crecl ON crecl.classid=cls.id"
         .' WHERE cre.active=1'
         .  ' AND cre.eventtriggertype=?'
         .  ' AND crecl.daterun IS NULL';

    $params = array($courselevel, $classlevel, RESULTS_ENGINE_SCHEDULED, $classlevel, RESULTS_ENGINE_SCHEDULED);

    $actives = $DB->get_records_sql($sql, $params);
    return $actives;
}

/**
 * Process all the students in this class
 *
 * Class properties:
 *   id               - id of class
 *   criteriatype     - what mark to look at, 0 for final mark, anything else is an element id
 *   engineid         - id of results engine entry
 *   scheduleddate    - date when it was supposed to run
 *   rundate          - date when it is being run
 *
 * @param $class object The class object see above for required attributes
 * @uses $CFG
 */
function results_engine_process($class) {
    global $CFG, $DB;

    $userlevel = context_level_base::get_custom_context_level('user', 'elis_program');
    $params    = array('classid' => $class->id);
    $fields    = 'userid, grade';
    $students  = $DB->get_records('crlm_class_enrolment', $params, '', $fields);

    if ($class->criteriatype > 0) {
        $grades = $DB->get_records('crlm_class_graded', $params, '', 'grade');
        foreach ($students as $student) {
            $student->grade = 0;
            if (array_key_exists($student->userid, $grades)) {
                $student->grade = $grades[$student->userid]->grade;
            }
        }
    }

    $params = array('resultengineid' => $class->engineid);
    $fields = 'id, actiontype, minimum, maximum, trackid, classid, fieldid, fieldata';
    $actions = $DB->get_records('crlm_results_engine_action', $params, '', $fields);

    $fields= array();

    foreach ($actions as $action) {
        if ($action->actiontype == RESULTS_ENGINE_UPDATE_PROFILE) {
            $fields[] = $action->fieldid;
        }
    }

    foreach ($fields as $field) {
        $userfields[$field] = new field($field);
    }

    // Log that the class has been processed
    $log = new object();
    $log->classid       = $class->id;
    $log->datescheduled = $class->scheduleddate;
    $log->daterun       = $class->rundate;
    $classlogid = $DB->insert_record('crlm_results_engine_class_log', $log);

    $log = new object();
    $log->classlogid = $classlogid;
    $log->daterun    = $class->rundate;

    // Find the correct action to take based on student marks
    foreach ($students as $student) {
        $do = null;

        foreach ($actions as $action) {

            if (($student->grade >= $action->minimum) && ($student->grade <= $action->maximum)) {
                $do = $action;
                break;
            }
        }

        if ($do != null) {

            switch($do->actiontype) {

                case RESULTS_ENGINE_ASSIGN_TRACK:
                    usertrack::enrol($student->userid, $do->trackid);
                    break;

                case RESULTS_ENGINE_ASSIGN_CLASS:
                    $enrol = new student();
                    $enrol->classid = $do->classid;
                    $enrol->userid  = $student->userid;
                    $enrol->save();
                    break;

                case RESULTS_ENGINE_UPDATE_PROFILE:
                    if (! array_key_exists($do->fieldid, $userfields)) {
                        print(get_string('field_not_found', RESULTS_ENGINE_LANG_FILE, $do) ."\n");
                        break;
                    }
                    $context = get_context_instance($userlevel, $student->userid);
                    field_data::set_for_context_and_field($context, $userfields[$do->fieldid], $do->fieldata);
                    break;

                default:
                    // If we don't know what we're doing, do nothing.
                    break;
            }
            $log->action     = $do->id;
            $log->userid     = $student->userid;
            $DB->insert_record('crlm_results_engine_student_log', $log, false);
        }
    }

    print(get_string('class_processed', RESULTS_ENGINE_LANG_FILE, $class) ."\n");
}