<?php

//start of "data handling" section, along with link for configuring mapping
$url = $CFG->wwwroot.'/local/datahub/importplugins/version1/config_fields.php';
$attributes = array('href' => $url,
                    'target' => '_blank');
$settings->add(new admin_setting_heading('dhimport_version1/datahandling',
                                         get_string('datahandling', 'dhimport_version1'),
                                         null));

//groups / groupings
$settings->add(new admin_setting_configcheckbox('dhimport_version1/creategroupsandgroupings',
                                                get_string('creategroupsandgroupings', 'dhimport_version1'),
                                                get_string('configcreategroupsandgroupings', 'dhimport_version1'), ''));

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('dhimport_version1/createorupdate',
                                                get_string('createorupdate', 'dhimport_version1'),
                                                get_string('configcreateorupdate', 'dhimport_version1'), 0));

//start of "scheduling" section
$settings->add(new admin_setting_heading('dhimport_version1/scheduling',
                                         get_string('importfilesheading', 'dhimport_version1'), ''));

//setting for schedule_files_path
$settings->add(new admin_setting_configtext('dhimport_version1/schedule_files_path',
                                            get_string('import_files_path', 'dhimport_version1'),
                                            get_string('config_schedule_files_path', 'dhimport_version1'), '/datahub/dhimport_version1'));

//setting for user_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1/user_schedule_file',
                                            get_string('user_schedule_file', 'dhimport_version1'),
                                            get_string('config_user_schedule_file', 'dhimport_version1'), 'user.csv'));

//setting for course_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1/course_schedule_file',
                                            get_string('course_schedule_file', 'dhimport_version1'),
                                            get_string('config_course_schedule_file', 'dhimport_version1'), 'course.csv'));

//setting for enrolment_schedule_file
$settings->add(new admin_setting_configtext('dhimport_version1/enrolment_schedule_file',
                                            get_string('enrolment_schedule_file', 'dhimport_version1'),
                                            get_string('config_enrolment_schedule_file', 'dhimport_version1'), 'enroll.csv'));

//start of "logging" section
$settings->add(new admin_setting_heading('dhimport_version1/logging',
                                         get_string('logging', 'dhimport_version1'),
                                         ''));

//log file location
$settings->add(new admin_setting_configtext('dhimport_version1/logfilelocation',
                                            get_string('logfilelocation', 'dhimport_version1'),
                                            get_string('configlogfilelocation', 'dhimport_version1'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('dhimport_version1/emailnotification',
                                            get_string('emailnotification', 'dhimport_version1'),
                                            get_string('configemailnotification', 'dhimport_version1'), ''));

$settings->add(new admin_setting_configcheckbox('dhimport_version1/allowduplicateemails',
                                            get_string('allowduplicateemails','dhimport_version1elis'),
                                            get_string('configallowduplicateemails','dhimport_version1elis'), ''));

// Start of "emails" section.
$settings->add(new admin_setting_heading('dhimport_version1/emails', get_string('emails', 'dhimport_version1'), ''));

// Toggle new user email notifications.
$newuseremailenabled = 'dhimport_version1/newuseremailenabled';
$newuseremailenabledname = get_string('newuseremailenabledname', 'dhimport_version1');
$newuseremailenableddesc = get_string('newuseremailenableddesc', 'dhimport_version1');
$settings->add(new admin_setting_configcheckbox($newuseremailenabled, $newuseremailenabledname, $newuseremailenableddesc, '0'));

$newuseremailsubject = 'dhimport_version1/newuseremailsubject';
$newuseremailsubjectname = get_string('newuseremailsubjectname', 'dhimport_version1');
$newuseremailsubjectdesc = get_string('newuseremailsubjectdesc', 'dhimport_version1');
$settings->add(new admin_setting_configtext($newuseremailsubject, $newuseremailsubjectname, $newuseremailsubjectdesc, ''));

$newuseremailtemplate = 'dhimport_version1/newuseremailtemplate';
$newuseremailtemplatename = get_string('newuseremailtemplatename', 'dhimport_version1');
$newuseremailtemplatedesc = get_string('newuseremailtemplatedesc', 'dhimport_version1');
$settings->add(new admin_setting_confightmleditor($newuseremailtemplate, $newuseremailtemplatename, $newuseremailtemplatedesc, '',
        PARAM_RAW, '60', '20'));

// Toggle new enrolment email notifications.
$settingkey = 'dhimport_version1/newenrolmentemailenabled';
$settingname = get_string('newenrolmentemailenabledname', 'dhimport_version1');
$settingdesc = get_string('newenrolmentemailenableddesc', 'dhimport_version1');
$settings->add(new admin_setting_configcheckbox($settingkey, $settingname, $settingdesc, '0'));

$settingkey = 'dhimport_version1/newenrolmentemailfrom';
$settingname = get_string('newenrolmentemailfromname', 'dhimport_version1');
$settingdesc = get_string('newenrolmentemailfromdesc', 'dhimport_version1');
$choices = array(
    'admin' => get_string('admin', 'dhimport_version1'),
    'teacher' => get_string('teacher', 'dhimport_version1')
);
$settings->add(new admin_setting_configselect($settingkey, $settingname, $settingdesc, 'admin', $choices));

$settingkey = 'dhimport_version1/newenrolmentemailsubject';
$settingname = get_string('newenrolmentemailsubjectname', 'dhimport_version1');
$settingdesc = get_string('newenrolmentemailsubjectdesc', 'dhimport_version1');
$settings->add(new admin_setting_configtext($settingkey, $settingname, $settingdesc, ''));

$settingkey = 'dhimport_version1/newenrolmentemailtemplate';
$settingname = get_string('newenrolmentemailtemplatename', 'dhimport_version1');
$settingdesc = get_string('newenrolmentemailtemplatedesc', 'dhimport_version1');
$settings->add(new admin_setting_confightmleditor($settingkey, $settingname, $settingdesc, '', PARAM_RAW, '60', '20'));
