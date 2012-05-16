<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('deprecatedlib.php'));
require_once(elispm::lib('data/user.class.php'));

//determine which program we are operating on (or 'na' for non-program courses)
$programid = required_param('programid', PARAM_CLEAN);
//determine which state we're in
$showcompleted = required_param('showcompleted', PARAM_INT);
$showcompleted = $showcompleted == 1 ? true : false;

if ($cmuid = cm_get_crlmuserid($USER->id)) {
    //need the current PM user, since that's where the dashboard is defined
    $user = new user($cmuid);

    if ($programid == 'na') {
        //non-program courses
        if ($classes = $user->get_dashboard_nonprogram_data(array(), $showcompleted)) {
            //send back the table to display
            $table = $user->get_dashboard_nonprogram_table($classes);
            echo html_writer::table($table);
        }
    } else {
        //program courses
        list($userprograms, $programs, $classids, $totalprograms) = $user->get_dashboard_program_data(false, false, $showcompleted);

        if (isset($programs[$programid])) {
            $program = $programs[$programid];
            //send back the table to display
            $table = $user->get_dashboard_program_table($program);
            echo html_writer::table($table);
        }
    }
}