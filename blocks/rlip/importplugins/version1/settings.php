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
