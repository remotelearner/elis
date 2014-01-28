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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

//
// Context level definitions for this block.
//
// The context levels are loaded into the database table when the block is
// installed or updated.  Whenever the context level definitions are updated,
// the module version number should be bumped up.
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_contextlevels

global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/accesslib.php');

/*
$contextlevels = array(
    'curriculum' => new context_level_elis_curriculum(),
    'track' => new context_level_elis_track(),
    'course' => new context_level_elis_course(),
    'class' => new context_level_elis_class(),
    'user' => new context_level_elis_user(),
    'cluster' => new context_level_elis_cluster(),
    );
*/
//
// Capability definitions for the this block.
//
// The capabilities are loaded into the database table when the block is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.


$capabilities = array(

    'local/elisprogram:config' => array(

        'riskbitmask' => RISK_SPAM | RISK_PERSONAL | RISK_XSS | RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:config',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

// Master control switch, kind of (legacy):

    'local/elisprogram:manage' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:managecurricula',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// Programs:

    'local/elisprogram:program_view' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:view',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:program_create' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:create',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:program_edit' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:edit',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:program_delete' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:delete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:program_enrol' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:enrol',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// Tracks:

    'local/elisprogram:track_view' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:view',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:track_create' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:create',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:track_edit' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:edit',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:track_delete' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:delete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:track_enrol' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:enrol',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// User sets:

    'local/elisprogram:userset_view' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:view',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:userset_create' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:create',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:userset_edit' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:edit',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:userset_delete' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:delete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:userset_enrol' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:enrol',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// Courses:

    'local/elisprogram:course_view' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:course:view',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:course_create' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:course:create',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:course_edit' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:course:edit',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:course_delete' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:course:delete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// Classes:

    'local/elisprogram:class_view' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:view',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:class_create' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:create',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:class_edit' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:edit',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:class_delete' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:delete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:class_enrol' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:enrol',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:assign_class_instructor' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

// Users:

    'local/elisprogram:user_view' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:user:view',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:user_create' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:user:create',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:user_edit' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:user:edit',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:user_delete' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:user:delete',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

// Reports:

    'local/elisprogram:viewownreports' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:viewownreports',
        'archetypes' => array(
            'user' => CAP_ALLOW,
            //'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

// Files

    'local/elisprogram:managefiles' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_XSS | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:managefiles',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

// Notifications:

    'local/elisprogram:notify_trackenrol' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_trackenrol',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_classenrol' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'clonepermissionsfrom' => 'elis/program:notify_classenrol',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_classcomplete' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'clonepermissionsfrom' => 'elis/program:notify_classcomplete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_classnotstart' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'clonepermissionsfrom' => 'elis/program:notify_classnotstart',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_classnotcomplete' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'clonepermissionsfrom' => 'elis/program:notify_classnotcomplete',
        'archetypes' => array(
            //'teacher' => CAP_ALLOW,
            //'editingteacher' => CAP_ALLOW,
            //'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_courserecurrence' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_courserecurrence',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_programrecurrence' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_curriculumrecurrence',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_programcomplete' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_curriculumcomplete',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_programnotcomplete' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_curriculumnotcomplete',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_programdue' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_curriculumdue',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

    'local/elisprogram:notify_coursedue' => array(

        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:notify_coursedue',
        'archetypes' => array(
            //'teacher' => CAP_PREVENT,
            //'editingteacher' => CAP_PREVENT,
            //'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
     ),

// Enrolment via clusters:

     'local/elisprogram:program_enrol_userset_user' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:curriculum:enrol_cluster_user',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:track_enrol_userset_user' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:track:enrol_cluster_user',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:userset_enrol_userset_user' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:enrol_cluster_user',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:class_enrol_userset_user' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:class:enrol_cluster_user',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisprogram:assign_userset_user_class_instructor' => array(

        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

// Other:

    'local/elisprogram:viewcoursecatalog' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:viewcoursecatalog',
        'archetypes' => array(
            'user' => CAP_ALLOW,
            //'student' => CAP_ALLOW
        )
     ),

     'local/elisprogram:overrideclasslimit' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:overrideclasslimit',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
     ),

     'local/elisprogram:userset_role_assign_userset_users' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:cluster:role_assign_cluster_users',
     ),

     'local/elisprogram:associate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'elis/program:associate',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
     )
);
