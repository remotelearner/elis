<?php // $Id: fix_program_completion.php,v 1.0 2013/01/10 11:00:00 brentb Exp $
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
 * @subpackage programmanagement-scripts
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */
if (!isset($_SERVER['REMOTE_ADDR'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(__FILE__) .'/../lib/setup.php');
require_once(elispm::lib('data/curriculumcourse.class.php'));

global $DB, $FULLME, $OUTPUT, $PAGE;

if (isset($_SERVER['REMOTE_ADDR'])) {
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url($FULLME);
    $PAGE->set_title('Fix Program Completion Dates');
    echo $OUTPUT->header();
    $br = '<br/>';
} else {
    $br = '';
    if ($argc > 1) {
        die(print_usage());
    }
}

mtrace("Begin Program Completion Date updating ...");
$fixed_cnt = 0;

$currs = curriculum_get_listing();
foreach ($currs as $cur) {
    $currcourses = $DB->get_records(curriculumcourse::TABLE, array('curriculumid' => $cur->id));
    //mtrace("\ncur: {$cur->id} - courses = ". var_sdump($currcourses));
    if (count($currcourses) == 1) {
        $currcourse = current($currcourses);
        $currclasses = $DB->get_records('crlm_class', array('courseid' => $currcourse->courseid), '', 'id');
        //mtrace("\ncur: {$cur->id} - classes = ". var_sdump($currclasses));
        if (empty($currclasses)) {
            continue;
        }
        $currasses = $DB->get_recordset('crlm_curriculum_assignment',
                             array('curriculumid' => $cur->id));
        foreach ($currasses as $currass) {
            $classenrolments = $DB->get_records_select('crlm_class_enrolment',
                                       'userid = ? AND classid IN ('.implode(',', array_keys($currclasses)).')',
                                       array($currass->userid));
            if (count($classenrolments) != 1) {
                continue;
            }
            $classenrolment = current($classenrolments);
            if ($classenrolment->locked && $classenrolment->completestatusid == STUSTATUS_PASSED &&
                $currass->timecompleted != 0 && $classenrolment->completetime != 0 &&
                $classenrolment->completetime != $currass->timecompleted) {
                $currass->timecompleted = $classenrolment->completetime;
                $DB->update_record('crlm_curriculum_assignment', $currass);
                $fixed_cnt++; 
            }
        }
    }
}

if ($fixed_cnt > 0) {
    mtrace("{$fixed_cnt} Program Completion dates updated.{$br}");
} else {
    mtrace("No Programs (with single course) to update found!{$br}");
}

if ($br != '') {
    echo '<p><a href="'. $CFG->wwwroot .'/elis/program/index.php?s=health">Go back to health check page</a></p>';
    echo $OUTPUT->footer();
}

exit;

function print_usage() {
    mtrace('Usage: '. basename(__FILE__) ."\n");
}

function var_sdump($var) {
    ob_start();
    var_dump($var);
    $tmp = ob_get_contents();
    ob_end_clean();
    return $tmp;
}

