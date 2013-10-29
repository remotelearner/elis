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

// Toggle new user email notifications.
$newuseremailenabled = 'rlipimport_version1elis/newuseremailenabled';
$newuseremailenabledname = get_string('newuseremailenabledname', 'rlipimport_version1elis');
$newuseremailenableddesc = get_string('newuseremailenableddesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configcheckbox($newuseremailenabled, $newuseremailenabledname, $newuseremailenableddesc, '0'));

$newuseremailsubject = 'rlipimport_version1elis/newuseremailsubject';
$newuseremailsubjectname = get_string('newuseremailsubjectname', 'rlipimport_version1elis');
$newuseremailsubjectdesc = get_string('newuseremailsubjectdesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configtext($newuseremailsubject, $newuseremailsubjectname, $newuseremailsubjectdesc, ''));

$newuseremailtemplate = 'rlipimport_version1elis/newuseremailtemplate';
$newuseremailtemplatename = get_string('newuseremailtemplatename', 'rlipimport_version1elis');
$newuseremailtemplatedesc = get_string('newuseremailtemplatedesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_confightmleditor($newuseremailtemplate, $newuseremailtemplatename, $newuseremailtemplatedesc, '',
        PARAM_RAW, '60', '20'));
