<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */


$capabilities = array(

    'repository/elisfiles:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:view'
    ),
    'repository/elisfiles:createsitecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:createsitecontent'
    ),

    'repository/elisfiles:viewsitecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:viewsitecontent'
    ),

    'repository/elisfiles:createsharedcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:createsharedcontent'
    ),

    'repository/elisfiles:viewsharedcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:viewsharedcontent'
    ),


    'repository/elisfiles:createcoursecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:createcoursecontent'
    ),

    'repository/elisfiles:viewcoursecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:viewcoursecontent'
    ),

    'repository/elisfiles:createowncontent' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:createowncontent'
    ),

    'repository/elisfiles:viewowncontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:viewowncontent'
    ),

    'repository/elisfiles:createusersetcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:createusersetcontent'
    ),

    'repository/elisfiles:viewusersetcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager'=> CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'repository/elis_files:viewusersetcontent'
    )
);
