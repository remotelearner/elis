<?php

//start of "data handling" section
$settings->add(new admin_setting_heading('rlipimport_version1/datahandling',
                                         get_string('datahandling', 'rlipimport_version1'),
                                         ''));

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
                                         get_string('scheduling', 'rlipimport_version1'),
                                         ''));

//setting for schedule_files_path
$settings->add(new admin_setting_configtext('rlipimport_version1/schedule_files_path',
                                            get_string('schedule_files_path', 'rlipimport_version1'),
                                            get_string('config_schedule_files_path', 'rlipimport_version1'), ''));

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

//setting for disabling in Moodle cron
//todo: actually use this setting's value somewhere
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/disableincron',
                                                get_string('disableincron', 'rlipimport_version1'),
                                                get_string('configdisableincron', 'rlipimport_version1'), ''));

//start of "logging" section
$settings->add(new admin_setting_heading('rlipimport_version1/logging',
                                         get_string('logging', 'rlipimport_version1'),
                                         ''));

//log file location
$settings->add(new admin_setting_configtext('rlipimport_version1/logfilelocation',
                                            get_string('logfilelocation', 'rlipimport_version1'),
                                            get_string('configlogfilelocation', 'rlipimport_version1'), ''));
