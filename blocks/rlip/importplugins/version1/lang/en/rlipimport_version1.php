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
 * @package    rlip
 * @subpackage importplugins_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$rlipshortname = 'DH';

$string['allowduplicateemails'] = 'Allow duplicate email addresses.';
$string['configallowduplicateemails'] = 'If on, duplicate email addresses will be allowed.';
$string['config_schedule_files_path'] = 'The directory path, relative to moodledata, to the location of import files.';
$string['config_course_schedule_file'] = 'The filename of the \'course\' '.$rlipshortname.' import file.';
$string['config_enrolment_schedule_file'] = 'The filename of the \'enrolment\' '.$rlipshortname.' import file.';
$string['config_user_schedule_file'] = 'The filename of the \'user\' '.$rlipshortname.' import file.';
$string['configcreategroupsandgroupings'] = 'If enabled, groups and groupings can be created in the enrolment import.';
$string['configcreateorupdate'] = 'If enabled, all create or update actions on users and courses are converted to create
actions if the entity does not already exist, or update actions if the entity exists.';
$string['configemailnotification'] = 'A comma-separated list of email addresses to send import log files to.';
$string['configfieldstreelink'] = 'Field mapping';
$string['configlogfilelocation'] = 'The directory path, relative to moodledata, to the location of log files.';
$string['configurelink'] = 'Click here to configure field mappings to use for importing data';
$string['configuretitle'] = 'Plugins Blocks: Configure Version 1 Import Fields';
$string['course_schedule_file'] = 'Course import filename';
$string['coursefile'] = 'Course file';
$string['coursetab'] = 'Course fields';
$string['creategroupsandgroupings'] = 'Create groups and groupings';
$string['createorupdate'] = 'Create or update';
$string['datahandling'] = 'Data handling';
$string['emails'] = 'Emails';
$string['emailnotification'] = 'Email notification';
$string['enrolment_schedule_file'] = 'Enrolment import filename';
$string['enrolmentfile'] = 'Enrolment file';
$string['enrolmenttab'] = 'Enrolment fields';
$string['importfields'] = 'Version 1 import fields';
$string['importfilesheading'] = 'Scheduled Import File Locations';
$string['import_file_timestamp'] = '%b_%d_%Y_%H%M%S';
$string['logfilelocation'] = 'Log file location';
$string['logging'] = 'Logging';
$string['mappingscancelled'] = 'Previously saved field mappings successfully restored';
$string['mappingsreset'] = 'Default field mappings successfully restored';
$string['mappingssaved'] = 'Field mappings successfully saved';
$string['newuseremailenabledname'] = 'Send New User Email Notifications';
$string['newuseremailenableddesc'] = 'When a new user is created with this import plugin, send them an email using the template below';
$string['newuseremailsubjectname'] = 'New User Email Notifications Subject';
$string['newuseremailsubjectdesc'] = 'This is the text to use for the subject line of the email.';
$string['newuseremailtemplatename'] = 'New User Email Notifications Template';
$string['newuseremailtemplatedesc'] = 'If enabled, send new users created with this plugin the above text. Note that if the above text is empty, no email will be sent.<br />
<b>The following placeholders are available:</b>
<ul>
<li><b>%%sitename%%</b>: The site\'s name.</li>
<li><b>%%loginlink%%</b>: A URL to the site\'s login page</li>
<li><b>%%username%%</b>: The user\'s username.</li>
<li><b>%%idnumber%%</b>: The user\'s idnumber.</li>
<li><b>%%password%%</b>: The user\'s password.</li>
<li><b>%%firstname%%</b>: The user\'s first name.</li>
<li><b>%%lastname%%</b>: The user\'s last name.</li>
<li><b>%%fullname%%</b>: The user\'s full name.</li>
<li><b>%%email%%</b>: The user\'s email address.</li>
</ul>
';
$string['pluginname'] = 'Version 1 import';
$string['scheduling'] = 'Scheduling';
$string['import_files_path'] = 'Import files path';
$string['user_schedule_file'] = 'User import filename';
$string['userfile'] = 'User file';
$string['usertab'] = 'User fields';
