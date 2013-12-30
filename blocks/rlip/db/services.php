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
    'block_rldh_elis_class_delete' => array(
        'classname' => 'block_rldh_elis_class_delete',
        'methodname' => 'class_delete',
        'classpath' => 'blocks/rlip/ws/elis/class_delete.class.php',
        'description' => 'Delete an ELIS class',
        'type' => 'read',
    ),
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
    'block_rldh_elis_user_update_identifiers' => array(
        'classname' => 'block_rldh_elis_user_update_identifiers',
        'methodname' => 'user_update_identifiers',
        'classpath' => 'blocks/rlip/ws/elis/user_update_identifiers.class.php',
        'description' => 'Update an ELIS user\'s identifying fields',
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
    'block_rldh_elis_class_enrolment_update' => array(
        'classname' => 'block_rldh_elis_class_enrolment_update',
        'methodname' => 'class_enrolment_update',
        'classpath' => 'blocks/rlip/ws/elis/class_enrolment_update.class.php',
        'description' => 'Update a user class enrolment',
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
    'block_rldh_elis_course_create' => array(
        'classname' => 'block_rldh_elis_course_create',
        'methodname' => 'course_create',
        'classpath' => 'blocks/rlip/ws/elis/course_create.class.php',
        'description' => 'Create an ELIS course description',
        'type' => 'read',
    ),
    'block_rldh_elis_course_update' => array(
        'classname' => 'block_rldh_elis_course_update', 
        'methodname' => 'course_update',
        'classpath' => 'blocks/rlip/ws/elis/course_update.class.php',
        'description' => 'Update an ELIS course description',
        'type' => 'read',
    ),
    'block_rldh_elis_course_delete' => array(
        'classname' => 'block_rldh_elis_course_delete',
        'methodname' => 'course_delete',
        'classpath' => 'blocks/rlip/ws/elis/course_delete.class.php',
        'description' => 'Delete an ELIS course description',
        'type' => 'read'
    ),
    'block_rldh_elis_class_create' => array(
        'classname' => 'block_rldh_elis_class_create',
        'methodname' => 'class_create',
        'classpath' => 'blocks/rlip/ws/elis/class_create.class.php',
        'description' => 'Create an ELIS class',
        'type' => 'read',
    ),
    'block_rldh_elis_class_update' => array(
        'classname' => 'block_rldh_elis_class_update',
        'methodname' => 'class_update',
        'classpath' => 'blocks/rlip/ws/elis/class_update.class.php',
        'description' => 'Update an ELIS class',
        'type' => 'read',
    ),
    'block_rldh_elis_program_create' => array(
        'classname' => 'block_rldh_elis_program_create',
        'methodname' => 'program_create',
        'classpath' => 'blocks/rlip/ws/elis/program_create.class.php',
        'description' => 'Create a program',
        'type' => 'read'
    ),
    'block_rldh_elis_program_update' => array(
        'classname' => 'block_rldh_elis_program_update',
        'methodname' => 'program_update',
        'classpath' => 'blocks/rlip/ws/elis/program_update.class.php',
        'description' => 'Update a program',
        'type' => 'read'
    ),
    'block_rldh_elis_program_delete' => array(
        'classname' => 'block_rldh_elis_program_delete',
        'methodname' => 'program_delete',
        'classpath' => 'blocks/rlip/ws/elis/program_delete.class.php',
        'description' => 'Delete a program',
        'type' => 'read'
    ),
    'block_rldh_elis_track_create' => array(
        'classname' => 'block_rldh_elis_track_create',
        'methodname' => 'track_create',
        'classpath' => 'blocks/rlip/ws/elis/track_create.class.php',
        'description' => 'Create a track',
        'type' => 'read'
    ),
    'block_rldh_elis_track_update' => array(
        'classname' => 'block_rldh_elis_track_update',
        'methodname' => 'track_update',
        'classpath' => 'blocks/rlip/ws/elis/track_update.class.php',
        'description' => 'Update a track',
        'type' => 'read'
    ),
    'block_rldh_elis_track_delete' => array(
        'classname' => 'block_rldh_elis_track_delete',
        'methodname' => 'track_delete',
        'classpath' => 'blocks/rlip/ws/elis/track_delete.class.php',
        'description' => 'Delete a track',
        'type' => 'read'
    ),
    'block_rldh_elis_userset_create' => array(
        'classname' => 'block_rldh_elis_userset_create',
        'methodname' => 'userset_create',
        'classpath' => 'blocks/rlip/ws/elis/userset_create.class.php',
        'description' => 'Create a userset',
        'type' => 'read'
    ),
    'block_rldh_elis_userset_update' => array(
        'classname' => 'block_rldh_elis_userset_update',
        'methodname' => 'userset_update',
        'classpath' => 'blocks/rlip/ws/elis/userset_update.class.php',
        'description' => 'Update a userset',
        'type' => 'read'
    ),
    'block_rldh_elis_userset_delete' => array(
        'classname' => 'block_rldh_elis_userset_delete',
        'methodname' => 'userset_delete',
        'classpath' => 'blocks/rlip/ws/elis/userset_delete.class.php',
        'description' => 'Delete a userset (and optionally its subsets)',
        'type' => 'read'
    )
);

// Pre-built service.
$services = array(
    'RLDH Webservices' => array(
        'functions' => array(
                'block_rldh_elis_class_delete',
                'block_rldh_elis_user_create',
                'block_rldh_elis_user_update',
                'block_rldh_elis_user_update_identifiers',
                'block_rldh_elis_user_delete',
                'block_rldh_elis_program_enrolment_create',
                'block_rldh_elis_program_enrolment_delete',
                'block_rldh_elis_track_enrolment_create',
                'block_rldh_elis_track_enrolment_delete',
                'block_rldh_elis_class_enrolment_create',
                'block_rldh_elis_class_enrolment_update',
                'block_rldh_elis_class_enrolment_delete',
                'block_rldh_elis_userset_enrolment_create',
                'block_rldh_elis_userset_enrolment_delete',
                'block_rldh_elis_course_update',
                'block_rldh_elis_course_delete',
                'block_rldh_elis_class_create',
                'block_rldh_elis_class_update',
                'block_rldh_elis_program_create',
                'block_rldh_elis_program_update',
                'block_rldh_elis_program_delete',
                'block_rldh_elis_track_create',
                'block_rldh_elis_track_update',
                'block_rldh_elis_track_delete',
                'block_rldh_elis_userset_create',
                'block_rldh_elis_userset_update',
                'block_rldh_elis_userset_delete'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'rldh_webservices',
    )
);
