<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlipexport_version1elis/scheduling',
                                         get_string('exportfilesheading', 'rlipexport_version1elis'), ''));

// Export filename settings
$settings->add(new admin_setting_configtext('rlipexport_version1elis/export_path',
                                            get_string('export_path', 'rlipexport_version1elis'),
                                            get_string('config_export_path', 'rlipexport_version1elis'), '/rlip/rlipexport_version1elis'));

$settings->add(new admin_setting_configtext('rlipexport_version1elis/export_file',
                                            get_string('export_file', 'rlipexport_version1elis'),
                                            get_string('config_export_file', 'rlipexport_version1elis'), 'export_version1elis.csv'));

// timestamp export file
$settings->add(new admin_setting_configcheckbox('rlipexport_version1elis/export_file_timestamp',
                                                get_string('export_file_timestamp_label',
                                                'rlipexport_version1elis'), get_string('config_export_file_timestamp', 'rlipexport_version1elis'), 1));


//start of "logging" section
$settings->add(new admin_setting_heading('rlipexport_version1elis/logging',
                                         get_string('logging', 'rlipexport_version1elis'),
                                         ''));

// Log file location
$settings->add(new admin_setting_configtext('rlipexport_version1elis/logfilelocation',
                                            get_string('logfilelocation', 'rlipexport_version1elis'),
                                            get_string('configlogfilelocation', 'rlipexport_version1elis'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('rlipexport_version1elis/emailnotification',
                                            get_string('emailnotification', 'rlipexport_version1elis'),
                                            get_string('configemailnotification', 'rlipexport_version1elis'), ''));


/**
 * Time period settings
 */

//header
$headerstring = get_string('timeperiodheader', 'rlipexport_version1elis');
$settings->add(new admin_setting_heading('rlipexport_version1elis_timeperiod', $headerstring, ''));

//checkbox for enabling
$displaystring = get_string('nonincremental', 'rlipexport_version1elis');
$infostring = get_string('confignonincremental', 'rlipexport_version1elis');
$settings->add(new admin_setting_configcheckbox('rlipexport_version1elis/nonincremental', $displaystring, $infostring, 0));

//time delta
$displaystring = get_string('incrementaldelta', 'rlipexport_version1elis');
$infostring = get_string('configincrementaldelta', 'rlipexport_version1elis');
$setting = new admin_setting_configtext('rlipexport_version1elis/incrementaldelta', $displaystring, $infostring, '1d');
//callback to sanitize time delta
$setting->set_updatedcallback('rlipexport_version1elis_incrementaldelta_updatedcallback');
$settings->add($setting);
