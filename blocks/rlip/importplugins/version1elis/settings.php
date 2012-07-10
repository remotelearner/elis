<?php

//start of "data handling" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/datahandling',
                                         get_string('datahandling', 'rlipimport_version1elis'),
                                         null));
                                         
//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1elis'),
                                                get_string('configcreateorupdate', 'rlipimport_version1elis'), 0));