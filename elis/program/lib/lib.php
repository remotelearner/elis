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

/**Callback function for ELIS Config/admin: Cluster Group Settings
 *
 * @param string $name  the fullname of the parameter that changed
 * @uses  $DB
 */
function cluster_groups_changed($name) {
    global $DB;
    $shortname = substr($name, strpos($name, 'elis_program_') + strlen('elis_program_'));
    // TBD: following didn't work?
    //$value = elis::$config->elis_program->$shortname;
    $value = $DB->get_field('config_plugins', 'value',
                            array('plugin' => 'elis_program',
                                  'name'   => $shortname));
    //error_log("/elis/program/lib/lib.php::cluster_groups_changed({$name}) {$shortname} = '{$value}'");
    if (!empty($value)) {
        $event = 'crlm_'. $shortname .'_enabled';
        error_log("Triggering event: $event");
        events_trigger($event, 0);
    }
}

/**
 * Prints the 'All A B C ...' alphabetical filter bar.
 *
 * @param object $moodle_url the moodle url object for the alpha/letter links
 * @param string $pname      the parameter name to be appended to the moodle_url
 *                           i.e. 'pname=alpha'
 * @param string $label      optional label - defaults to none
 */
function pmalphabox($moodle_url, $pname = 'alpha', $label = null) {
    $alpha    = optional_param($pname, null, PARAM_ALPHA);
    $alphabet = explode(',', get_string('alphabet', 'langconfig'));
    $strall   = get_string('all');

    echo html_writer::start_tag('div', array('style' => 'text-align:center'));
    if (!empty($label)) {
        echo $label, ' '; // TBD: html_writer::???
    }
    if ($alpha) {
        $url = clone($moodle_url); // TBD
        $url->remove_params($pname);
        echo html_writer::link($url, $strall);
    } else {
        echo html_writer::tag('b', $strall);
    }

    foreach ($alphabet as $letter) {
        if ($letter == $alpha) {
            echo ' ', html_writer::tag('b', $letter);
        } else {
            $url = clone($moodle_url); // TBD
            $url->params(array($pname => $letter));
            echo ' ', html_writer::link($url, $letter);
        }
    }

    echo html_writer::end_tag('div');
}

/**
 * Prints the text substring search interface.
 *
 * @param object|string $page_or_url the page object for the search form action
 *                                   or the url string.
 * @param string $searchname         the parameter name for the search tag
 *                                   i.e. 'searchname=search'
 * @param string $method             the form submit method: get(default)| post
 *                                   TBD: 'post' method flakey, doesn't always work!
 * @param string $showall            label for the 'Show All' link - optional
 *                                   defaults to get_string('showallitems' ...
 * @param string $extra              extra html for input fields displayed BEFORE search fields. i.e. student.class.php::edit_form_html()
 *                                   $extra defaults to none.
 * @uses $_GET
 * @uses $_POST
 * @uses $CFG
 * @todo convert echo HTML statements to use M2 html_writer, etc.
 * @todo support moodle_url as 1st parameter and not just string url.
 */
function pmsearchbox($page_or_url = null, $searchname = 'search', $method = 'get', $showall = null, $extra = '') {
    global $CFG;
    $search = trim(optional_param($searchname, '', PARAM_TEXT));

    $params = $_GET;
    unset($params['page']);      // TBD: Do we want to go back to the first page
    unset($params[$searchname]); // And clear the search ???
    if (isset($params['mode']) && $params['mode'] == 'bare') {
        unset($params['mode']);
    }
    if (empty($params)) {
        //error_log("pmsearchbox() _GET empty using _POST");
        $params = $_POST;
        unset($params['page']);      // TBD: Do we want to go back to the first page
        unset($params[$searchname]); // And clear the search ???
        if (isset($params['mode']) && $params['mode'] == 'bare') {
            unset($params['mode']);
        }
    }

    $target = is_object($page_or_url) ? $page_or_url->get_new_page($params)->url
                                      : get_pm_url($page_or_url, $params);
    if (method_exists($target, 'remove_params')) {
        $target->remove_params($searchname); // TBD: others too???
        $existingparams = $target->params();
        if (isset($existingparams['mode']) && $existingparams['mode'] == 'bare') {
            $target->remove_params('mode');
        }
    }
    $query_pos = strpos($target, '?');
    $action_url = ($query_pos !== false) ? substr($target, 0, $query_pos)
                                         : $target;
    echo '<table class="searchbox" style="margin-left:auto;margin-right:auto" cellpadding="10"><tr><td>'; // TBD: style ???
    echo "<form action=\"{$action_url}\" method=\"{$method}\">";
    echo '<fieldset class="invisiblefieldset">';
    // TBD: merge parameters from $target - if exists
    foreach($params as $key => $val) {
        echo "<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\" />";
        if ($query_pos === false) {
            $target .= (strpos($target, '?') === false) ? '?' : '&';
            $target .= "{$key}={$val}"; // required for onclick, below
        }
    }
    if (!empty($extra)) {
        echo $extra;
    }
    echo "<input type=\"text\" name=\"{$searchname}\" value=\"" . s($search, true) . '" size="20" />';
    echo '<input type="submit" value="'.get_string('search').'" />';

    if ($search) {
        if (empty($showall)) {
            $showall = get_string('showallitems', 'elis_program');
        }
        echo "<input type=\"button\" onclick=\"document.location='{$target}';\" value=\"{$showall}\" />";
        //error_log("/elis/program/lib/lib.php::pmsearchbox() show_all_url = {$target}");
    }

    echo '</fieldset></form>';
    echo '</td></tr></table>';
}

/**
 * Prints the current 'alpha' and 'search' settings for no table entries
 *
 * @param string $alpha         the current alpha/letter match
 * @param string $namesearch    the current string search
 * @param string $matchlabel    optional get_string identifier for label prefix of match settings
 *                              default get_string('name', 'elis_program')
 * @param string $nomatchlabel  optional get_string identifier for label prefix of no matches
 *                              default get_string('no_users_matching', 'elis_program')
 */
function pmshowmatches($alpha, $namesearch, $matchlabel = null, $nomatchlabel = null) {
    //error_log("pmshowmatches({$alpha}, {$namesearch}, {$matchlabel}, {$nomatchlabel})");
    if (empty($matchlabel)) {
        $matchlabel = 'name';
    }
    if (empty($nomatchlabel)) {
        $nomatchlabel = 'no_item_matching';
    }
    $match = array();
    if ($namesearch !== '') {
        $match[] = '<b>'. s($namesearch) .'</b>';
    }
    if ($alpha) {
        $match[] = get_string($matchlabel, 'elis_program') .": <b>{$alpha}___</b>";
    }
    if (!empty($match)) {
        $matchstring = implode(", ", $match);
        $sparam = new stdClass;
        $sparam->match = $matchstring;
        echo get_string($nomatchlabel, 'elis_program', $sparam), '<br/>'; // TBD
    }
}

/** Function to return pm page url with required params
 *
 * @param   string|moodle_url  $baseurl  the pages base url
 *                             defaults to: '/elis/program/index.php'
 * @param   array              $extras   extra parameters for url.
 * @return  moodle_url         the baseurl with required params
 */
function get_pm_url($baseurl = null, $extras = array()) {
    if (empty($baseurl)) {
        $baseurl = '/elis/program/index.php';
    }
    $options = array('s', 'id', 'action', 'section', 'alpha', 'search', 'perpage', 'class', 'association_id', 'mode', '_assign'); // TBD: add more parameters as required: page, [sort, dir] ???
    $params = array();
    foreach ($options as $option) {
        $val = optional_param($option, null, PARAM_CLEAN);
        if ($val != null) {
            $params[$option] = $val;
        }
    }
    foreach ($extras as $key => $val) {
        $params[$key] = $val;
    }
    return new moodle_url($baseurl, $params);
}

/**
 * New display function callback to allow HTML elements in table
 * see: /elis/core/lib/table.class.php
 */
function htmltab_display_function($column, $item) {
    return isset($item->{$column}) ? $item->{$column} : '';
}

/**
 * display function - originally a method in table.class.php
 * see ELIS_1.9x:/curriculum/lib/table.class.php
 */
function get_date_item_display($column, $item) {
    if (empty($item->$column)) {
        return '-';
    } else {
        $timestamp = $item->$column;
        return is_numeric($timestamp)
               ? date(get_string('pm_date_format', 'elis_program'), $timestamp)
               : '';
    }
}

/**
 * display function - originally a method in table.class.php
 * see ELIS_1.9x:/curriculum/lib/table.class.php
 */
function get_yesno_item_display($column, $item) {
    return get_string($item->$column ? 'yes' : 'no');
}

/**
 *
 * Call Moodle's set_config with 3rd parm 'elis_program'
 *
 * @param string $name the key to set
 * @param string $value the value to set (without magic quotes)
 * @return n/a
 */
function pm_set_config($name, $value) {
    set_config($name,$value, 'elis_program');
}

/**
 * Synchronize Moodle enrolments over to the PM system based on associations of Moodle
 * courses to PM classes, as well as converting grade item grades to learning objective grades
 */
function pm_synchronize_moodle_class_grades() {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/grade/lib.php');
    require_once(elispm::lib('data/classmoodlecourse.class.php'));

    if ($moodleclasses = moodle_get_classes()) {
        $timenow = time();
        foreach ($moodleclasses as $class) {
            $pmclass = new pmclass($class->classid);
            $context = get_context_instance(CONTEXT_COURSE, $class->moodlecourseid);
            $moodlecourse = $DB->get_record('course', array('id' => $class->moodlecourseid));

            // Get CM enrolment information (based on Moodle enrolments)
            // IMPORTANT: this record set must be sorted using the Moodle
            // user ID
            $relatedcontextsstring = get_related_contexts_string($context);
            $sql = "SELECT DISTINCT u.id AS muid, u.username, cu.id AS cmid, stu.*
                      FROM {user} u
                      JOIN {role_assignments} ra ON u.id = ra.userid
                 LEFT JOIN {".user::TABLE."} cu ON cu.idnumber = u.idnumber
                 LEFT JOIN {".student::TABLE."} stu on stu.userid = cu.id AND stu.classid = :classid
                     WHERE ra.roleid in (:roles)
                       AND ra.contextid {$relatedcontextsstring}
                  ORDER BY muid ASC";
            $causers = $DB->get_recordset_sql($sql, array('classid' => $pmclass->id,
                                                          'roles' => $CFG->gradebookroles));

            if(empty($causers)) {
                // nothing to see here, move on
                continue;
            }

            /// Get CM completion elements and related Moodle grade items
            $comp_elements = array();
            $gis = array();
            if (isset($pmclass->course) && (get_class($pmclass->course) == 'course')
                && ($elements = $pmclass->course->get_completion_elements())) {

                foreach ($elements as $element) {
                    // In Moodle 1.9, Moodle actually stores the "slashes" on the idnumber field in the grade_items
                    // table so we to check both with and without addslashes. =(  - ELIS-1830
                    $idnumber = $element->idnumber;
                    if ($gi = $DB->get_record('grade_items', array('courseid' => $class->moodlecourseid,
                                                                   'idnumber' => $idnumber))) {
                        $gis[$gi->id] = $gi;
                        $comp_elements[$gi->id] = $element;
                    } else if ($gi = $DB->get_record('grade_items', array('courseid' => $class->moodlecourseid,
                                                                          'idnumber' => addslashes($idnumber)))) {
                        $gis[$gi->id] = $gi;
                        $comp_elements[$gi->id] = $element;
                    }
                }
            }
            // add grade item for the overall course grade
            $coursegradeitem = grade_item::fetch_course_item($moodlecourse->id);
            $gis[$coursegradeitem->id] = $coursegradeitem;

            if ($coursegradeitem->grademax == 0) {
                // no maximum course grade, so we can't calculate the
                // student's grade
                continue;
            }

            if (!empty($elements)) {
                // get current completion element grades if we have any
                // IMPORTANT: this record set must be sorted using the Moodle
                // user ID

                //todo: use table constant
                $sql = "SELECT grades.*, mu.id AS muid
                          FROM {crlm_class_graded} grades
                          JOIN {".user::TABLE."} cu ON grades.userid = cu.id
                          JOIN {user} mu ON cu.idnumber = mu.idnumber
                         WHERE grades.classid = :classid
                      ORDER BY mu.id";
                $allcompelemgrades = $DB->get_recordset_sql($sql, array('classid' => $pmclass->id));
                $last_rec = null; // will be used to store the last completion
                                  // element that we fetched from the
                                  // previous iteration (which may belong
                                  // to the current user)
            }

            // get the Moodle course grades
            // IMPORTANT: this iterator must be sorted using the Moodle
            // user ID
            $gradedusers = new graded_users_iterator($moodlecourse, $gis, 0, 'id', 'ASC', null);
            $gradedusers->init();

            // only create a new enrolment record if there is only one CM
            // class attached to this Moodle course
            $doenrol = ($DB->count_records(classmoodlecourse::TABLE, array('moodlecourseid' => $class->moodlecourseid)) == 1);

            // main loop -- go through the student grades
            foreach ($causers as $sturec) {
                if (!$stugrades = $gradedusers->next_user()) {
                    break;
                }

                // skip user records that don't match up
                // (this works since both sets are sorted by Moodle user ID)
                // (in theory, we shouldn't need this, but just in case...)
                while ($sturec && $sturec->muid < $stugrades->user->id) {
                    $sturec = rs_fetch_next_record($causers);
                }
                if (!$sturec) {
                    break;
                }
                while($stugrades && $stugrades->user->id < $sturec->muid) {
                    $stugrades = $gradedusers->next_user();
                }
                if (!$stugrades) {
                    break;
                }

                /// If the user doesn't exist in CM, skip it -- should we flag it?
                if (empty($sturec->cmid)) {
                    mtrace("No user record for Moodle user id: {$sturec->muid}: {$sturec->username}<br />\n");
                    continue;
                }
                $cmuserid = $sturec->cmid;

                /// If no enrolment record in ELIS, then let's set one.
                if (empty($sturec->id)) {
                    if(!$doenrol) {
                        continue;
                    }
                    $sturec->classid = $class->classid;
                    $sturec->userid = $cmuserid;
                    /// Enrolment time will be the earliest found role assignment for this user.
                    $enroltime = $DB->get_field('user_enrolments', 'timestart as enroltime', array('enrolid' => $class->moodlecourseid,
                                                                                                   'userid'  => $sturec->muid));
                    $sturec->enrolmenttime = (!empty($enroltime) ? $enroltime : $timenow);
                    $sturec->completetime = 0;
                    $sturec->endtime = 0;
                    $sturec->completestatusid = STUSTATUS_NOTCOMPLETE;
                    $sturec->grade = 0;
                    $sturec->credits = 0;
                    $sturec->locked = 0;
                    $sturec->id = $DB->insert_record(STUTABLE, $sturec);
                }

                /// Handle the course grade
                if (isset($stugrades->grades[$coursegradeitem->id]->finalgrade)) {

                    /// Set the course grade if there is one and it's not locked.
                    $usergradeinfo = $stugrades->grades[$coursegradeitem->id];
                    if (!$sturec->locked && !is_null($usergradeinfo->finalgrade)) {
                        // clone of student record, to see if we actually change anything
                        $old_sturec = clone($sturec);

                        $grade = $usergradeinfo->finalgrade / $coursegradeitem->grademax * 100.0;
                        $sturec->grade = $grade;

                        /// Update completion status if all that is required is a course grade.
                        if (empty($elements)) {
                            if ($pmclass->course->completion_grade <= $sturec->grade) {
                                $sturec->completetime = $usergradeinfo->get_dategraded();
                                $sturec->completestatusid = STUSTATUS_PASSED;
                                $sturec->credits = floatval($pmclass->course->credits);
                            } else {
                                $sturec->completetime = 0;
                                $sturec->completestatusid = STUSTATUS_NOTCOMPLETE;
                                $sturec->credits = 0;
                            }
                        } else {
                            $sturec->completetime = 0;
                            $sturec->completestatusid = STUSTATUS_NOTCOMPLETE;
                            $sturec->credits = 0;
                        }

                        // only update if we actually changed anything
                        // (exception: if the completetime gets smaller,
                        // it's probably because $usergradeinfo->get_dategraded()
                        // returned an empty value, so ignore that change)
                        if ($old_sturec->grade != $sturec->grade
                            || $old_sturec->completetime < $sturec->completetime
                            || $old_sturec->completestatusid != $sturec->completestatusid
                            || $old_sturec->credits != $sturec->credits) {

                            if ($sturec->completestatusid == STUSTATUS_PASSED && empty($sturec->completetime)) {
                                // make sure we have a valid complete time, if we passed
                                $sturec->completetime = $timenow;
                            }

                            $DB->update_record(student::TABLE, $sturec);
                        }
                    }
                }

                /// Handle completion elements
                if (!empty($allcompelemgrades)) {
                    // get student's completion elements
                    $cmgrades = array();
                    // NOTE: we use a do-while loop, since $last_rec might
                    // be set from the last run, so we need to check it
                    // before we load from the database

                    //need to track whether we're on the first record because of how
                    //recordsets work
                    $first = true;

                    do {
                        if (isset($last_rec->muid)) {
                            if ($last_rec->muid > $sturec->muid) {
                                // we've reached the end of this student's
                                // grades ($last_rec will save this record
                                // for the next student's run)
                                break;
                            }
                            if ($last_rec->muid == $sturec->muid) {
                                $cmgrades[$last_rec->completionid] = $last_rec;
                            }
                        }

                        if (!$first) {
                            //not using a cached record, so advance the recordset
                            $allcompelemgrades->next();
                        }

                        //obtain the next record
                        $last_rec = $allcompelemgrades->current();
                        //signal that we are now within the current recordset
                        $first = false;
                    } while ($allcompelemgrades->valid());

                    foreach ($comp_elements as $gi_id => $element) {
                        if (!isset($stugrades->grades[$gi_id]->finalgrade)) {
                            continue;
                        }
                        // calculate Moodle grade as a percentage
                        $gradeitem = $stugrades->grades[$gi_id];
                        $maxgrade = $gis[$gi_id]->grademax;
                        /// Ignore mingrade for now... Don't really know what to do with it.
                        $gradepercent =  ($gradeitem->finalgrade >= $maxgrade) ? 100.0
                                      : (($gradeitem->finalgrade <= 0) ? 0.0
                                      :  ($gradeitem->finalgrade / $maxgrade * 100.0));

                        if (isset($cmgrades[$element->id])) {
                            // update existing completion element grade
                            $grade_element = $cmgrades[$element->id];
                            if (!$grade_element->locked
                                && ($gradeitem->get_dategraded() > $grade_element->timegraded)) {

                                // clone of record, to see if we actually change anything
                                $old_grade = clone($grade_element);

                                $grade_element->grade = $gradepercent;
                                $grade_element->timegraded = $gradeitem->get_dategraded();
                                /// If completed, lock it.
                                $grade_element->locked = ($grade_element->grade >= $element->completion_grade) ? 1 : 0;

                                // only update if we actually changed anything
                                if ($old_grade->grade != $grade_element->grade
                                    || $old_grade->timegraded != $grade_element->timegraded
                                    || $old_grade->grade != $grade_element->grade
                                    || $old_grade->locked != $grade_element->locked) {

                                    $grade_element->timemodified = $timenow;
                                    //todo: use class constant
                                    $DB->update_record('crlm_class_graded', $grade_element);
                                }
                            }
                        } else {
                            // no completion element grade exists: create a new one
                            $grade_element = new Object();
                            $grade_element->classid = $class->classid;
                            $grade_element->userid = $cmuserid;
                            $grade_element->completionid = $element->id;
                            $grade_element->grade = $gradepercent;
                            $grade_element->timegraded = $gradeitem->get_dategraded();
                            $grade_element->timemodified = $timenow;
                            /// If completed, lock it.
                            $grade_element->locked = ($grade_element->grade >= $element->completion_grade) ? 1 : 0;
                            //todo: use class constant
                            $DB->insert_record('crlm_class_graded', $grade_element);
                        }
                    }
                }
            }
            set_time_limit(600);
        }
    }
}

/**
 * Notifies that students have not passed their classes via the notifications where applicable,
 * setting enrolment status to failed where applicable
 */
function pm_update_student_enrolment() {
    global $DB;

    require_once(elispm::lib('data/student.class.php'));
    require_once(elispm::lib('notifications.php'));

    //look for all enrolments where status is incomplete / in progress and end time has passed
    $select = 'completestatusid = :status AND endtime > 0 AND endtime < :time';
    $students = $DB->get_recordset_select(student::TABLE, $select, array('status' => STUSTATUS_NOTCOMPLETE,
                                                                         'time'   => time()));

    if(!empty($students)) {
        foreach($students as $s) {
            //send message
            $a = $DB->get_field(pmclass::TABLE, 'idnumber', array('id' => $s->classid));

            $message = get_string('incomplete_course_message', 'elis_program', $a);

            $user = cm_get_moodleuser($s->userid);
            $from = get_admin();

            notification::notify($message, $user, $from);

            //set status to failed
            $s->completetime = 0;
            $s->completestatusid = STUSTATUS_FAILED;
            $DB->update_record(student::TABLE, $s);
        }
    }

    return true;
}

/**
 * Migrate any existing Moodle users to the Curriculum Management
 * system.
 */
function pm_migrate_moodle_users($setidnumber = false, $fromtime = 0) {
    global $CFG, $DB;

    require_once(elispm::lib('data/user.class.php'));

    $timenow = time();
    $result  = true;

    // set time modified if not set, so we can keep track of "new" users
    $sql = "UPDATE {user}
               SET timemodified = :timenow
             WHERE timemodified = 0";
    $result = $result && $DB->execute($sql, array('timenow' => $timenow));

    if ($setidnumber || elis::$config->elis_program->auto_assign_user_idnumber) {
        $sql = "UPDATE {user}
                   SET idnumber = username
                 WHERE idnumber=''
                   AND username != 'guest'
                   AND deleted = 0
                   AND confirmed = 1
                   AND mnethostid = :hostid";
        $result = $result && $DB->execute($sql, array('hostid' => $CFG->mnet_localhost_id));
    }

    $rs = $DB->get_recordset_select('user',
                  "username != 'guest'
               AND deleted = 0
               AND confirmed = 1
               AND mnethostid = :hostid
               AND idnumber != ''
               AND timemodified >= :time
               AND NOT EXISTS (SELECT 'x'
                               FROM {".user::TABLE."} cu
                               WHERE cu.idnumber = {user}.idnumber)",
                  array('hostid' => $CFG->mnet_localhost_id,
                        'time'   => $fromtime));

    if ($rs) {
        require_once elis::plugin_file('usersetenrol_moodle_profile', 'lib.php');

        foreach ($rs as $user) {
            // FIXME: shouldn't depend on cluster functionality -- should
            // be more modular
            cluster_profile_update_handler($user);
        }
    }
    return $result;
}

/**
 * Migrate a single Moodle user to the Program Management system.  Will
 * only do this for users who have an idnumber set.
 */
function pm_moodle_user_to_pm($mu) {
    global $CFG, $DB;
    require_once(elis::lib('data/customfield.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once($CFG->dirroot . '/user/profile/lib.php');
    // re-fetch, in case this is from a stale event
    $mu = $DB->get_record('user', array('id' => $mu->id));
    if (empty($mu->idnumber) && elis::$config->elis_program->auto_assign_user_idnumber) {
        $mu->idnumber = $mu->username;
        $DB->update_record('user', $mu);
    }
    if (empty($mu->idnumber)) {
        return true;
    } else if ($cu = $DB->get_record(user::TABLE, array('idnumber' => $mu->idnumber))) {
        $cu = new user($cu);

        // synchronize any profile changes
        $cu->username = $mu->username;
        $cu->password = $mu->password;
        $cu->idnumber = $mu->idnumber;
        $cu->firstname = $mu->firstname;
        $cu->lastname = $mu->lastname;
        $cu->email = $mu->email;
        $cu->address = $mu->address;
        $cu->city = $mu->city;
        $cu->country = $mu->country;
        $cu->phone = empty($mu->phone1)?empty($cu->phone)? '': $cu->phone: $mu->phone1;
        $cu->phone2 = empty($mu->phone2)?empty($cu->phone2)? '': $cu->phone2: $mu->phone2;
        $cu->language = empty($mu->lang)?empty($cu->language)? '': $cu->language: $mu->lang;
        $cu->timemodified = time();

        //todo: implement this section once necessary profile field code is available
        // synchronize custom profile fields
        //profile_load_data($mu);
        //$fields = field::get_for_context_level(context_level_base::get_custom_context_level('user', 'block_curr_admin'));
        //$fields = $fields ? $fields : array();
        //require_once (CURMAN_DIRLOCATION . '/plugins/moodle_profile/custom_fields.php');
        //foreach ($fields as $field) {
        //    $field = new field($field);
        //    if (isset($field->owners['moodle_profile']) && $field->owners['moodle_profile']->exclude == cm_moodle_profile::sync_from_moodle) {
        //        $fieldname = "field_{$field->shortname}";
        //        $cu->$fieldname = $mu->{"profile_field_{$field->shortname}"};
        //    }
        //}
        $cu->save();
     } else {
        $cu = new user();
        $cu->username = $mu->username;
        $cu->password = $mu->password;
        $cu->idnumber = $mu->idnumber;
        $cu->firstname = $mu->firstname;
        $cu->lastname = $mu->lastname;
        $cu->email = $mu->email;
        $cu->address = $mu->address;
        $cu->city = $mu->city;
        $cu->country = $mu->country;
        $cu->phone = $mu->phone1;
        $cu->phone2 = $mu->phone2;
        $cu->language = $mu->lang;
        $cu->transfercredits = 0;
        $cu->timecreated = $cu->timemodified = time();

        //todo: implement this section once necessary profile field code is available
        // synchronize profile fields
        //profile_load_data($mu);
        //$fields = field::get_for_context_level(context_level_base::get_custom_context_level('user', 'block_curr_admin'));
        //$fields = $fields ? $fields : array();
        //require_once (CURMAN_DIRLOCATION . '/plugins/moodle_profile/custom_fields.php');
        //foreach ($fields as $field) {
        //    $field = new field($field);
        //    if (isset($field->owners['moodle_profile']) && $field->owners['moodle_profile']->exclude == cm_moodle_profile::sync_from_moodle) {
        //        $fieldname = "field_{$field->shortname}";
        //        $cu->$fieldname = $mu->{"profile_field_{$field->shortname}"};
        //    }
        //}

        $cu->save();
    }
    return true;
}

/**
 * Get all of the data from Moodle and update the curriculum system.
 * This should do the following:
 *      - Get all Moodle courses connected with classes.
 *      - Get all users in each Moodle course.
 *      - Get grade records from the class's course and completion elements.
 *      - For each user:
 *          - Check if they have an enrolment record in CM, and add if not.
 *          - Update grade information in the enrollment and grade tables in CM.
 *
 */
function pm_update_student_progress() {
    global $CFG;

    require_once ($CFG->dirroot.'/grade/lib.php');
    require_once ($CFG->dirroot.'/grade/querylib.php');

    /// Get all grades in all relevant courses for all relevant users.
    require_once (elispm::lib('data/classmoodlecourse.class.php'));
    require_once (elispm::lib('data/student.class.php'));
    require_once (elispm::lib('data/pmclass.class.php'));
    require_once (elispm::lib('data/course.class.php'));

/// Start with the Moodle classes...
    mtrace("Synchronizing Moodle class grades<br />\n");
    pm_synchronize_moodle_class_grades();

    flush(); sleep(1);

/// Now we need to check all of the student and grade records again, since data may have come from sources
/// other than Moodle.
    mtrace("Updating all class grade completions.<br />\n");
    pm_update_enrolment_status();

    return true;
}

/**
 * Update enrolment status of users enroled in all classes, completing and locking
 * records where applicable based on class grade and required completion elements
 */
function pm_update_enrolment_status() {
    global $CFG, $DB;

    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/student.class.php'));

/// Need to separate this out so that the enrolments by class are checked for completion.
/// ... for each class and then for each enrolment...
/// Goal is to minimize database reads, so we can't just instantiate a student object, as
/// each one will go and get the same things for one class. So, we probably need a class-level
/// function that then manages the student objects. Once this is in place, add completion notice
/// to the code.


    /// Get all classes with unlocked enrolments.
    $select = 'SELECT cce.classid as classid, COUNT(cce.userid) as numusers ';
    $from   = 'FROM {'.student::TABLE.'} cce ';
    $where  = 'WHERE cce.locked = 0 ';
    $group  = 'GROUP BY classid ';
    $order  = 'ORDER BY classid ASC ';
    $sql    = $select . $from . $where . $group . $order;

    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $rec) {
        $pmclass = new pmclass($rec->classid);
        $pmclass->update_enrolment_status();
        //todo: investigate as to whether ten minutes is too long for one class
        set_time_limit(600);
    }
}

/**
 * Get Curriculum user id for a given Moodle user id.
 *
 */
function pm_get_crlmuserid($userid) {
    global $DB;
    require_once(elispm::lib('data/user.class.php'));

    $select = 'SELECT cu.id ';
    $from   = 'FROM {user} mu ';
    $join   = 'INNER JOIN {'.user::TABLE.'} cu ON cu.idnumber = mu.idnumber ';
    $where  = 'WHERE mu.id = :userid';
    $params  = array('userid'=>$userid);
    return $DB->get_field_sql($select.$from.$join.$where, $params);
}
