<?php

//start of "data handling" section, along with link for configuring mapping
$url = $CFG->wwwroot.'/blocks/rlip/importplugins/version1/config_fields.php';
$attributes = array('href' => $url,
                    'target' => '_blank');
$settings->add(new admin_setting_heading('rlipimport_version1/datahandling',
                                         get_string('datahandling', 'rlipimport_version1'),
                                         null));

//groups / groupings
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/creategroupsandgroupings',
                                                get_string('creategroupsandgroupings', 'rlipimport_version1'),
                                                get_string('configcreategroupsandgroupings', 'rlipimport_version1'), ''));

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1'),
                                                get_string('configcreateorupdate', 'rlipimport_version1'), 0));

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlipimport_version1/scheduling',
                                         get_string('importfilesheading', 'rlipimport_version1'), ''));

//setting for schedule_files_path
$settings->add(new admin_setting_configtext('rlipimport_version1/schedule_files_path',
                                            get_string('import_files_path', 'rlipimport_version1'),
                                            get_string('config_schedule_files_path', 'rlipimport_version1'), '/rlip/rlipimport_version1'));

//setting for user_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1/user_schedule_file',
                                            get_string('user_schedule_file', 'rlipimport_version1'),
                                            get_string('config_user_schedule_file', 'rlipimport_version1'), 'user.csv'));

//setting for course_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1/course_schedule_file',
                                            get_string('course_schedule_file', 'rlipimport_version1'),
                                            get_string('config_course_schedule_file', 'rlipimport_version1'), 'course.csv'));

//setting for enrolment_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1/enrolment_schedule_file',
                                            get_string('enrolment_schedule_file', 'rlipimport_version1'),
                                            get_string('config_enrolment_schedule_file', 'rlipimport_version1'), 'enroll.csv'));

//start of "logging" section
$settings->add(new admin_setting_heading('rlipimport_version1/logging',
                                         get_string('logging', 'rlipimport_version1'),
                                         ''));

//log file location
$settings->add(new admin_setting_configtext('rlipimport_version1/logfilelocation',
                                            get_string('logfilelocation', 'rlipimport_version1'),
                                            get_string('configlogfilelocation', 'rlipimport_version1'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('rlipimport_version1/emailnotification',
                                            get_string('emailnotification', 'rlipimport_version1'),
                                            get_string('configemailnotification', 'rlipimport_version1'), ''));
