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

require_once(dirname(__FILE__) .'/../../lib/setup.php');

// max out at 2 minutes (= 120 seconds)
define('RESULTS_ENGINE_USERACT_TIME_LIMIT', 120);
define('RESULTS_ENGINE_GRADE_SET', 1);
define('RESULTS_ENGINE_SCHEDULED', 2);
define('RESULTS_ENGINE_MANUAL',    3);

define('RESULTS_ENGINE_AFTER_START', 1);
define('RESULTS_ENGINE_BEFORE_END',  2);
define('RESULTS_ENGINE_AFTER_END',   3);


/**
 * Process course results
 */
function results_engine_cron() {
    $rununtil = time() + RESULTS_ENGINE_USERACT_TIME_LIMIT;

    $actives = results_engine_get_active();
    print_r($actives);

    while ($active = current($actives) && time() < $rununtil) {

    }
}

/**
 * Get the results engines that are active
 *
 * @uses $CFG
 * @uses $DB
 */
function results_engine_get_active() {
    global $CFG, $DB;

    $courselevel = context_level_base::get_custom_context_level('course', 'elis_program');
    $classlevel  = context_level_base::get_custom_context_level('class', 'elis_program');

    // Get course level instances that have not been overriden or already run.
    $sql = 'SELECT cls.id, cls.startdate, cls.enddate, cre.triggerstartdate, cre.criteriatype'
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
         .' SELECT cls.id, cls.startdate, cls.enddate, cre.triggerstartdate, cre.criteriatype'
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