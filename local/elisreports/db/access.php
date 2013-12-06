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
 * @package    local_elisreports
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

$capabilities = array(

    'local/elisreports:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'block/php_report:view',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisreports:schedule' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'block/php_report:schedule',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/elisreports:manageschedules' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'block/php_report:manageschedules',
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
);
