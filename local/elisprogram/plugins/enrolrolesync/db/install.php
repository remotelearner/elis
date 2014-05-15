<?php
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
 * @package    elisprogram_enrolrolesync
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_elisprogram_enrolrolesync_install() {

    // Migrate component.
    $oldcmp = 'pmplugins_enrolment_role_sync';
    $newcmp = 'elisprogram_enrolrolesync';
    $migrator = new \local_eliscore\install\migration\migrator($oldcmp, $newcmp);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    //set the student role based on the old value
    $old_student_roleid = get_config('local_elisprogram', 'enrolment_role_sync_student_role');
    if ($old_student_roleid !== null) {
        set_config('student_role', $old_student_roleid, 'elisprogram_enrolrolesync');
    }

    //set the instructor role based on the old value
    $old_instructor_roleid = get_config('local_elisprogram', 'enrolment_role_sync_instructor_role');
    if ($old_instructor_roleid !== null) {
        set_config('instructor_role', $old_instructor_roleid, 'elisprogram_enrolrolesync');
    }

    return true;
}