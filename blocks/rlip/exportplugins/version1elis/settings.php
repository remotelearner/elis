<?php

defined('MOODLE_INTERNAL') || die;

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlipexport_version1elis/scheduling',
                                         get_string('exportfilesheading', 'rlipexport_version1elis'), ''));

$settings->add(new admin_setting_configtext('rlipexport_version1elis/export_file',
                                            get_string('export_file', 'rlipexport_version1elis'),
                                            get_string('config_export_file', 'rlipexport_version1elis'), 'export_version1elis.csv'));