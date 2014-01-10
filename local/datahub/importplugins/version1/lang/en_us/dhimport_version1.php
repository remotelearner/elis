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
 * @package    local_datahub
 * @subpackage importplugins_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

$rlipshortname = 'DH';

$string['config_enrolment_schedule_file'] = 'The filename of the \'enrollment\' '.$rlipshortname.' import file.';
$string['configcreategroupsandgroupings'] = 'If enabled, groups and groupings can be created in the enrollment import.';
$string['enrolment_schedule_file'] = 'Enrollment import filename';
$string['enrolmentfile'] = 'Enrollment file';
$string['enrolmenttab'] = 'Enrollment fields';
$string['newenrolmentemailenabledname'] = 'Send New Enrollment Email Notifications';
$string['newenrolmentemailenableddesc'] = 'When a user is enrolled into a course with this import plugin, send them an email using the template below.';
$string['newenrolmentemailfromname'] = 'Send Enrollment Email from';
$string['newenrolmentemailsubjectname'] = 'New Enrollment Email Notifications Subject';
$string['newenrolmentemailtemplatename'] = 'New Enrollment Email Notifications Template';
$string['newenrolmentemailtemplatedesc'] = 'If enabled, send users enrolled with this plugin the above text. Note that if the above text is empty, no email will be sent.<br />
<b>The following placeholders are available:</b>
<ul>
<li><b>%%sitename%%</b>: The site\'s name.</li>
<li><b>%%user_username%%</b>: The user\'s username.</li>
<li><b>%%user_idnumber%%</b>: The user\'s idnumber.</li>
<li><b>%%user_firstname%%</b>: The user\'s first name.</li>
<li><b>%%user_lastname%%</b>: The user\'s last name.</li>
<li><b>%%user_fullname%%</b>: The user\'s full name.</li>
<li><b>%%user_email%%</b>: The user\'s email address.</li>
<li><b>%%course_fullname%%</b>: The full name of the course..</li>
<li><b>%%course_shortname%%</b>: The shortname of the course.</li>
<li><b>%%course_idnumber%%</b>: The idnumber of the course.</li>
<li><b>%%course_summary%%</b>: The course\'s summary.</li>
</ul>
';
