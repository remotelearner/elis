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
 * Strings for component 'auth_cas', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   auth_elisfilessso
 * @copyright 2013
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['auth_elisfilesssodescription'] = 'This plug-in allows automatic creation of user accounts on an ELIS Files ' .
                                         'repository.  It should not be used as the configured authentication plug-in ' .
                                         'for individual Moodle user accounts as it will not perform any actual ' .
                                         'login authentication.  It functions more as a meta auth plug-in that handles ' .
                                         'all users on the system, regardless of their specific authentication plug-in.';
$string['auth_elisfilesssotitle'] = 'elisfiles SSO';

$string['elisfilesnotfound'] = 'ELIS Files repository plug-in configuration not found';
$string['configureelisfileshere'] = 'Configure the ELIS Files repository plug-in settings <a href="{$a}">here</a>';
$string['pluginname'] = 'ELIS Files SSO';
