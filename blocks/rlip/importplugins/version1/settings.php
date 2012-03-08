<?php

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1'),
                                                get_string('configcreateorupdate', 'rlipimport_version1'), 0));

//log file location
$settings->add(new admin_setting_configtext('rlipimport_version1/logfilelocation',
                                            get_string('logfilelocation', 'rlipimport_version1'),
                                            get_string('configlogfilelocation', 'rlipimport_version1'), ''));

//groups / groupings
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/creategroupsandgroupings',
                                                get_string('creategroupsandgroupings', 'rlipimport_version1'),
                                                get_string('configcreategroupsandgroupings', 'rlipimport_version1'), ''));