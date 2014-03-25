<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008 onward Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008 onward Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die;

// Start of "data handling" section
$settings->add(new admin_setting_heading('dhimport_version1elis/datahandling', get_string('datahandling', 'dhimport_version1elis'), null));

// New identifying field selection section
$settings->add(new admin_setting_configcheckbox('dhimport_version1elis/identfield_idnumber',
        get_string('identfield_idnumber', 'dhimport_version1elis'), '', 1));
$settings->add(new admin_setting_configcheckbox('dhimport_version1elis/identfield_username',
        get_string('identfield_username', 'dhimport_version1elis'), '', 1));
$settings->add(new admin_setting_configcheckbox('dhimport_version1elis/identfield_email',
        get_string('identfield_email', 'dhimport_version1elis'), get_string('configidentfield', 'dhimport_version1elis'), 1));

// Setting for "create or update"
$settings->add(new admin_setting_configcheckbox('dhimport_version1elis/createorupdate', get_string('createorupdate', 'dhimport_version1elis'),
        get_string('configcreateorupdate', 'dhimport_version1elis'), 0));

// Start of "scheduling" section
$settings->add(new admin_setting_heading('dhimport_version1elis/scheduling', get_string('importfilesheading', 'dhimport_version1elis'), ''));

// Setting for schedule_files_path
$settings->add(new admin_setting_configtext('dhimport_version1elis/schedule_files_path', get_string('import_files_path', 'dhimport_version1elis'),
        get_string('config_schedule_files_path', 'dhimport_version1elis'), '/datahub/dhimport_version1elis'));

// Setting for user_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1elis/user_schedule_file', get_string('user_schedule_file', 'dhimport_version1elis'),
        get_string('config_user_schedule_file', 'dhimport_version1elis'), 'user.csv'));

// Setting for course_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1elis/course_schedule_file', get_string('course_schedule_file', 'dhimport_version1elis'),
        get_string('config_course_schedule_file', 'dhimport_version1elis'), 'course.csv'));

// Setting for enrolment_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1elis/enrolment_schedule_file', get_string('enrolment_schedule_file', 'dhimport_version1elis'),
        get_string('config_enrolment_schedule_file', 'dhimport_version1elis'), 'enroll.csv'));

// Start of "logging" section
$settings->add(new admin_setting_heading('dhimport_version1elis/logging', get_string('logging', 'dhimport_version1elis'), ''));

// Log file location
$settings->add(new admin_setting_configtext('dhimport_version1elis/logfilelocation', get_string('logfilelocation', 'dhimport_version1elis'),
        get_string('configlogfilelocation', 'dhimport_version1elis'), RLIP_DEFAULT_LOG_PATH));

// Email notification
$settings->add(new admin_setting_configtext('dhimport_version1elis/emailnotification', get_string('emailnotification', 'dhimport_version1elis'),
        get_string('configemailnotification', 'dhimport_version1elis'), ''));

$settings->add(new admin_setting_configcheckbox('dhimport_version1elis/allowduplicateemails', get_string('allowduplicateemails','dhimport_version1elis'),
        get_string('configallowduplicateemails','dhimport_version1elis'), ''));

// Start of "emails" section.
$settings->add(new admin_setting_heading('dhimport_version1elis/emails', get_string('emails', 'dhimport_version1elis'), ''));

// Toggle new user email notifications.
$newuseremailenabled = 'dhimport_version1elis/newuseremailenabled';
$newuseremailenabledname = get_string('newuseremailenabledname', 'dhimport_version1elis');
$newuseremailenableddesc = get_string('newuseremailenableddesc', 'dhimport_version1elis');
$settings->add(new admin_setting_configcheckbox($newuseremailenabled, $newuseremailenabledname, $newuseremailenableddesc, '0'));

$newuseremailsubject = 'dhimport_version1elis/newuseremailsubject';
$newuseremailsubjectname = get_string('newuseremailsubjectname', 'dhimport_version1elis');
$newuseremailsubjectdesc = get_string('newuseremailsubjectdesc', 'dhimport_version1elis');
$settings->add(new admin_setting_configtext($newuseremailsubject, $newuseremailsubjectname, $newuseremailsubjectdesc, ''));

$newuseremailtemplate = 'dhimport_version1elis/newuseremailtemplate';
$newuseremailtemplatename = get_string('newuseremailtemplatename', 'dhimport_version1elis');
$newuseremailtemplatedesc = get_string('newuseremailtemplatedesc', 'dhimport_version1elis');
$settings->add(new admin_setting_confightmleditor($newuseremailtemplate, $newuseremailtemplatename, $newuseremailtemplatedesc, '',
        PARAM_RAW, '60', '20'));

// Toggle new enrolment email notifications.
$settingkey = 'dhimport_version1elis/newenrolmentemailenabled';
$settingname = get_string('newenrolmentemailenabledname', 'dhimport_version1elis');
$settingdesc = get_string('newenrolmentemailenableddesc', 'dhimport_version1elis');
$settings->add(new admin_setting_configcheckbox($settingkey, $settingname, $settingdesc, '0'));

$settingkey = 'dhimport_version1elis/newenrolmentemailfrom';
$settingname = get_string('newenrolmentemailfromname', 'dhimport_version1elis');
$settingdesc = get_string('newenrolmentemailfromdesc', 'dhimport_version1elis');
$choices = array(
    'admin' => get_string('admin', 'dhimport_version1elis'),
    'teacher' => get_string('teacher', 'dhimport_version1elis')
);
$settings->add(new admin_setting_configselect($settingkey, $settingname, $settingdesc, 'admin', $choices));

$settingkey = 'dhimport_version1elis/newenrolmentemailsubject';
$settingname = get_string('newenrolmentemailsubjectname', 'dhimport_version1elis');
$settingdesc = get_string('newenrolmentemailsubjectdesc', 'dhimport_version1elis');
$settings->add(new admin_setting_configtext($settingkey, $settingname, $settingdesc, ''));

$settingkey = 'dhimport_version1elis/newenrolmentemailtemplate';
$settingname = get_string('newenrolmentemailtemplatename', 'dhimport_version1elis');
$settingdesc = get_string('newenrolmentemailtemplatedesc', 'dhimport_version1elis');
$settings->add(new admin_setting_confightmleditor($settingkey, $settingname, $settingdesc, '', PARAM_RAW, '60', '20'));
