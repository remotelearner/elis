<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008 onward Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008 onward Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

/**
 * Profile field settings
 */

// Start of "scheduling" section
$settings->add(new admin_setting_heading('dhexport_version1/scheduling', get_string('exportfilesheading', 'dhexport_version1'), ''));

// Export filename settings
$settings->add(new admin_setting_configtext('dhexport_version1/export_path', get_string('export_path', 'dhexport_version1'),
        get_string('config_export_path', 'dhexport_version1'), '/datahub/dhexport_version1'));

$settings->add(new admin_setting_configtext('dhexport_version1/export_file', get_string('export_file', 'dhexport_version1'),
        get_string('config_export_file', 'dhexport_version1'), 'export_version1.csv'));

// Timestamp export file
$settings->add(new admin_setting_configcheckbox('dhexport_version1/export_file_timestamp', get_string('export_file_timestamp_label', 'dhexport_version1'),
        get_string('config_export_file_timestamp', 'dhexport_version1'), 1));

// Start of "logging" section
$settings->add(new admin_setting_heading('dhexport_version1/logging', get_string('logging', 'dhexport_version1'), ''));

// Log file location
$settings->add(new admin_setting_configtext('dhexport_version1/logfilelocation', get_string('logfilelocation', 'dhexport_version1'),
        get_string('configlogfilelocation', 'dhexport_version1'), RLIP_DEFAULT_LOG_PATH));

// Email notification
$settings->add(new admin_setting_configtext('dhexport_version1/emailnotification', get_string('emailnotification', 'dhexport_version1'),
        get_string('configemailnotification', 'dhexport_version1'), ''));

/**
 * Time period settings
 */

// Header
$headerstring = get_string('timeperiodheader', 'dhexport_version1');
$settings->add(new admin_setting_heading('dhexport_version1_timeperiod', $headerstring, ''));

// Checkbox for enabling
$displaystring = get_string('nonincremental', 'dhexport_version1');
$infostring = get_string('confignonincremental', 'dhexport_version1');
$settings->add(new admin_setting_configcheckbox('dhexport_version1/nonincremental', $displaystring, $infostring, 0));

// Time delta
$displaystring = get_string('incrementaldelta', 'dhexport_version1');
$infostring = get_string('configincrementaldelta', 'dhexport_version1');
$setting = new admin_setting_configtext('dhexport_version1/incrementaldelta', $displaystring, $infostring, '1d');
// Callback to sanitize time delta
$setting->set_updatedcallback('rlipexport_version1_incrementaldelta_updatedcallback');
$settings->add($setting);
