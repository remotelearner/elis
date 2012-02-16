<?php

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1'),
                                                get_string('configcreateorupdate', 'rlipimport_version1'), 0));