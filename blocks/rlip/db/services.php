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
 * @package    block_rlip
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Webservice functions.
$functions = array(
    'block_rldh_elis_user_create' => array(
        'classname' => 'block_rldh_elis_user_create',
        'methodname' => 'user_create',
        'classpath' => 'blocks/rlip/ws/elis/user_create.class.php',
        'description' => 'Create an ELIS user',
        'type' => 'read',
    ),
    'block_rldh_elis_user_update' => array(
        'classname' => 'block_rldh_elis_user_update',
        'methodname' => 'user_update',
        'classpath' => 'blocks/rlip/ws/elis/user_update.class.php',
        'description' => 'Update an ELIS user',
        'type' => 'read',
    ),
    'block_rldh_elis_user_delete' => array(
        'classname' => 'block_rldh_elis_user_delete',
        'methodname' => 'user_delete',
        'classpath' => 'blocks/rlip/ws/elis/user_delete.class.php',
        'description' => 'Delete an ELIS user',
        'type' => 'read',
    ),
    'block_rldh_elis_program_enrolment_create' => array(
        'classname' => 'block_rldh_elis_program_enrolment_create',
        'methodname' => 'program_enrolment_create',
        'classpath' => 'blocks/rlip/ws/elis/program_enrolment_create.class.php',
        'description' => 'Enrol a user into a program',
        'type' => 'read'
    ),
    'block_rldh_elis_program_enrolment_delete' => array(
        'classname' => 'block_rldh_elis_program_enrolment_delete',
        'methodname' => 'program_enrolment_delete',
        'classpath' => 'blocks/rlip/ws/elis/program_enrolment_delete.class.php',
        'description' => 'Remove a user from a program',
        'type' => 'read'
    ),
    'block_rldh_elis_track_enrolment_create' => array(
        'classname' => 'block_rldh_elis_track_enrolment_create',
        'methodname' => 'track_enrolment_create',
        'classpath' => 'blocks/rlip/ws/elis/track_enrolment_create.class.php',
        'description' => 'Enrol a user into a track',
        'type' => 'read',
    ),
    'block_rldh_elis_track_enrolment_delete' => array(
        'classname' => 'block_rldh_elis_track_enrolment_delete',
        'methodname' => 'track_enrolment_delete',
        'classpath' => 'blocks/rlip/ws/elis/track_enrolment_delete.class.php',
        'description' => 'Remove a user from a track',
        'type' => 'read'
    ),
    'block_rldh_elis_class_enrolment_create' => array(
        'classname' => 'block_rldh_elis_class_enrolment_create',
        'methodname' => 'class_enrolment_create',
        'classpath' => 'blocks/rlip/ws/elis/class_enrolment_create.class.php',
        'description' => 'Enrol a user into a class',
        'type' => 'read'
    ),
    'block_rldh_elis_class_enrolment_delete' => array(
        'classname' => 'block_rldh_elis_class_enrolment_delete',
        'methodname' => 'class_enrolment_delete',
        'classpath' => 'blocks/rlip/ws/elis/class_enrolment_delete.class.php',
        'description' => 'Remove a user from a class',
        'type' => 'read'
    ),
    'block_rldh_elis_userset_enrolment_create' => array(
        'classname' => 'block_rldh_elis_userset_enrolment_create',
        'methodname' => 'userset_enrolment_create',
        'classpath' => 'blocks/rlip/ws/elis/userset_enrolment_create.class.php',
        'description' => 'Enrol a user into a userset',
        'type' => 'read'
    ),
    'block_rldh_elis_userset_enrolment_delete' => array(
        'classname' => 'block_rldh_elis_userset_enrolment_delete',
        'methodname' => 'userset_enrolment_delete',
        'classpath' => 'blocks/rlip/ws/elis/userset_enrolment_delete.class.php',
        'description' => 'Remove a user from a userset',
        'type' => 'read'
    ),
     'block_rldh_elis_userset_update' => array(
        'classname' => 'block_rldh_elis_userset_update',
        'methodname' => 'userset_update',
        'classpath' => 'blocks/rlip/ws/elis/userset_update.class.php',
        'description' => 'Update a userset',
        'type' => 'read'
     )
);

// Pre-built service.
$services = array(
    'RLDH Webservices' => array(
        'functions' => array(
                'block_rldh_elis_user_create',
                'block_rldh_elis_user_update',
                'block_rldh_elis_user_delete',
                'block_rldh_elis_program_enrolment_create',
                'block_rldh_elis_program_enrolment_delete',
                'block_rldh_elis_track_enrolment_create',
                'block_rldh_elis_track_enrolment_delete',
                'block_rldh_elis_class_enrolment_create',
                'block_rldh_elis_class_enrolment_delete',
                'block_rldh_elis_userset_enrolment_create',
                'block_rldh_elis_userset_enrolment_delete',
                'block_rldh_elis_userset_update'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
