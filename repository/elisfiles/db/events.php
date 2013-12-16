<?php
/**
 * Contains definitions for notification events.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository
 * @subpackage elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 */

$handlers = array (
    'user_deleted' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_user_deleted',
         'schedule'        => 'instant'
     ),

    'role_unassigned' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_role_unassigned',
         'schedule'        => 'instant'
     ),

    'cluster_assigned' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_userset_assigned',
         'schedule'        => 'instant'
     ),

    'cluster_deassigned' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_userset_deassigned',
         'schedule'        => 'instant'
     ),

    'user_created' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_user_created',
         'schedule'        => 'instant'
     ),

     'course_deleted' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_course_deleted',
         'schedule'        => 'instant'
     ),

     'cluster_deleted' => array (
         'handlerfile'     => '/repository/elis_files/lib/eventlib.php',
         'handlerfunction' => 'elis_files_userset_deleted',
         'schedule'        => 'instant'
     )

);
