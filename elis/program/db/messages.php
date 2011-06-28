<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines message providers (types of messages being sent)
 *
 * @package    elis
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$messageproviders = array (

    // Receive class enrollment notifications
    'notify_classenrol_message' => array (
        'capability'  => 'block/curr_admin:notify_classenrol'
    ),

    // Receive class completion notifications
    'notify_classcomplete_message' => array (
        'capability'  => 'block/curr_admin:notify_classcomplete'
    ),

    // Receive class not started notifications
    'notify_classnotstart_message' => array (
        'capability'  => 'block/curr_admin:notify_classnotstart'
    ),

    // Receive class not completed notifications
    'notify_classnotcomplete_message' => array (
        'capability'  => 'block/curr_admin:notify_classnotcomplete'
    ),

    // Receive curriculum completed notifications
    'notify_curriculumcomplete_message' => array (
        'capability'  => 'block/curr_admin:notify_curriculumcomplete'
    ),

    // Receive curriculum not completed notifications
    'notify_curriculumnotcomplete_message' => array (
        'capability'  => 'block/curr_admin:notify_curriculumnotcomplete'
    ),

    // Receive track enrollment notifications
    'notify_trackenrol_message' => array (
        'capability'  => 'block/curr_admin:notify_trackenrol'
    ),

    // Receive course expiration notifications
    'notify_courserecurrence_message' => array (
        'capability'  => 'block/curr_admin:notify_courserecurrence'
    ),

    // Receive curriculum expiration notifications
    'notify_curriculumrecurrence_message' => array (
        'capability'  => 'block/curr_admin:notify_curriculumrecurrence'
    )

);



