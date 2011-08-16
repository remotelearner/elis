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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['data_object_construct_invalid_source'] = 'Attempted to construct a data_object from an invalid source';
$string['data_object_validation_not_empty'] = '{$a->tablename} record cannot have empty {$a->field} field';
$string['data_object_validation_unique'] = '{$a->tablename} record must have unique {$a->fields} fields';
$string['elis'] = 'ELIS';
$string['elisversion'] = '<strong>ELIS Version:</strong> {$a}';
$string['finish'] = 'Finish';

// Default user profile field labels for userprofilematch filter - can override
$string['fld_auth'] = 'Authentication';
$string['fld_city'] = 'City/town';
$string['fld_confirmed'] = 'Confirmed';
$string['fld_country'] = 'Country';
$string['fld_coursecat'] = 'Course category';
$string['fld_courserole'] = 'Course role';
$string['fld_email'] = 'Email address';
$string['fld_firstaccess'] = 'First access';
$string['fld_firstname'] = 'First name';
$string['fld_fullname'] = 'Full name';
$string['fld_idnumber'] = 'ID number';
$string['fld_lang'] = 'Preferred language';
$string['fld_lastaccess'] = 'Last access';
$string['fld_lastlogin'] = 'Last login';
$string['fld_lastname'] = 'Last name';
$string['fld_systemrole'] = 'System role';
$string['fld_timemodified'] = 'Last modified';
$string['fld_username'] = 'Username';

$string['invalidid'] = 'Invalid ID';
$string['invalidoperator'] = 'Invalid Operator';
$string['pluginname'] = 'ELIS Core';
$string['report_filter_all'] = 'Show All';
$string['report_filter_anyvalue'] = 'No filtering';
$string['unknown_action'] = 'Unknown action ({$a})';
$string['set_nonexistent_member'] = 'Attempt to set nonexistent member variable {$a->classname}::${$a->name}';
$string['subplugintype_eliscoreplugins_plural'] = 'General plugins';
$string['subplugintype_elisfields_plural'] = 'Custom field types';
$string['workflow_cancelled'] = 'Cancelled';
$string['workflow_invalidstep'] = 'Invalid step specified';
$string['write_to_non_overlay_table'] = 'Attempted write to a non-overlay table: {$a}';
