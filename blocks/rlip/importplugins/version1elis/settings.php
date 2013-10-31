<?php

//start of "data handling" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/datahandling',
                                         get_string('datahandling', 'rlipimport_version1elis'),
                                         null));

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1elis'),
                                                get_string('configcreateorupdate', 'rlipimport_version1elis'), 0));

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/scheduling',
                                         get_string('importfilesheading', 'rlipimport_version1elis'), ''));

//setting for schedule_files_path
$settings->add(new admin_setting_configtext('rlipimport_version1elis/schedule_files_path',
                                            get_string('import_files_path', 'rlipimport_version1elis'),
                                            get_string('config_schedule_files_path', 'rlipimport_version1elis'), '/rlip/rlipimport_version1elis'));

//setting for user_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/user_schedule_file',
                                            get_string('user_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_user_schedule_file', 'rlipimport_version1elis'), 'user.csv'));

//setting for course_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/course_schedule_file',
                                            get_string('course_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_course_schedule_file', 'rlipimport_version1elis'), 'course.csv'));

//setting for enrolment_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/enrolment_schedule_file',
                                            get_string('enrolment_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_enrolment_schedule_file', 'rlipimport_version1elis'), 'enroll.csv'));

//start of "logging" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/logging',
                                         get_string('logging', 'rlipimport_version1elis'),
                                         ''));

//log file location
$settings->add(new admin_setting_configtext('rlipimport_version1elis/logfilelocation',
                                            get_string('logfilelocation', 'rlipimport_version1elis'),
                                            get_string('configlogfilelocation', 'rlipimport_version1elis'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('rlipimport_version1elis/emailnotification',
                                            get_string('emailnotification', 'rlipimport_version1elis'),
                                            get_string('configemailnotification', 'rlipimport_version1elis'), ''));

$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/allowduplicateemails',
                                            get_string('allowduplicateemails','rlipimport_version1elis'),
                                            get_string('configallowduplicateemails','rlipimport_version1elis'), ''));

// Start of "emails" section.
$settings->add(new admin_setting_heading('rlipimport_version1elis/emails', get_string('emails', 'rlipimport_version1elis'), ''));

// Toggle new enrolment email notifications.
$settingkey = 'rlipimport_version1elis/newenrolmentemailenabled';
$settingname = get_string('newenrolmentemailenabledname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailenableddesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configcheckbox($settingkey, $settingname, $settingdesc, '0'));

$settingkey = 'rlipimport_version1elis/newenrolmentemailfrom';
$settingname = get_string('newenrolmentemailfromname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailfromdesc', 'rlipimport_version1elis');
$choices = array(
    'admin' => get_string('admin', 'rlipimport_version1elis'),
    'teacher' => get_string('teacher', 'rlipimport_version1elis')
);
$settings->add(new admin_setting_configselect($settingkey, $settingname, $settingdesc, 'admin', $choices));

$settingkey = 'rlipimport_version1elis/newenrolmentemailsubject';
$settingname = get_string('newenrolmentemailsubjectname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailsubjectdesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configtext($settingkey, $settingname, $settingdesc, ''));

$settingkey = 'rlipimport_version1elis/newenrolmentemailtemplate';
$settingname = get_string('newenrolmentemailtemplatename', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailtemplatedesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_confightmleditor($settingkey, $settingname, $settingdesc, '', PARAM_RAW, '60', '20'));