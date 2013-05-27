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
    )
);

// Pre-built service.
$services = array(
    'RLDH Webservices' => array(
        'functions' => array('block_rldh_elis_user_create'),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);