<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

/**
 * Profile field settings
 */

//start of "scheduling" section
$settings->add(new admin_setting_heading('dhexport_version1/scheduling',
                                         get_string('exportfilesheading', 'dhexport_version1'), ''));

// Export filename settings
$settings->add(new admin_setting_configtext('dhexport_version1/export_path',
                                            get_string('export_path', 'dhexport_version1'),
                                            get_string('config_export_path', 'dhexport_version1'), '/datahub/dhexport_version1'));

$settings->add(new admin_setting_configtext('dhexport_version1/export_file',
                                            get_string('export_file', 'dhexport_version1'),
                                            get_string('config_export_file', 'dhexport_version1'), 'export_version1.csv'));

// timestamp export file
$settings->add(new admin_setting_configcheckbox('dhexport_version1/export_file_timestamp', get_string('export_file_timestamp_label', 'dhexport_version1'), get_string('config_export_file_timestamp', 'dhexport_version1'), 1));

//start of "logging" section
$settings->add(new admin_setting_heading('dhexport_version1/logging',
                                         get_string('logging', 'dhexport_version1'),
                                         ''));

// Log file location
$settings->add(new admin_setting_configtext('dhexport_version1/logfilelocation',
                                            get_string('logfilelocation', 'dhexport_version1'),
                                            get_string('configlogfilelocation', 'dhexport_version1'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('dhexport_version1/emailnotification',
                                            get_string('emailnotification', 'dhexport_version1'),
                                            get_string('configemailnotification', 'dhexport_version1'), ''));

/**
 * Time period settings
 */

//header
$headerstring = get_string('timeperiodheader', 'dhexport_version1');
$settings->add(new admin_setting_heading('dhexport_version1_timeperiod', $headerstring, ''));

//checkbox for enabling
$displaystring = get_string('nonincremental', 'dhexport_version1');
$infostring = get_string('confignonincremental', 'dhexport_version1');
$settings->add(new admin_setting_configcheckbox('dhexport_version1/nonincremental', $displaystring, $infostring, 0));

//time delta
$displaystring = get_string('incrementaldelta', 'dhexport_version1');
$infostring = get_string('configincrementaldelta', 'dhexport_version1');
$setting = new admin_setting_configtext('dhexport_version1/incrementaldelta', $displaystring, $infostring, '1d');
//callback to sanitize time delta
$setting->set_updatedcallback('dhexport_version1_incrementaldelta_updatedcallback');
$settings->add($setting);

