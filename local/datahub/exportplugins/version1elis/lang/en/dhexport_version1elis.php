<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage importplugins_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['addcustomfield'] = 'Add custom field...';$string['columnheader'] = 'Column Header';
$string['addfieldinstructions'] = 'Please add a custom field using the dropdown below.';
$string['completestatusstring'] = 'COMPLETED';
$string['config_export_file'] = 'Enter the filename template to use for exporting data.';
$string['configfieldstreelink'] = 'Field mapping';
$string['configemailnotification'] = 'A comma-separated list of email addresses to send export log files to.';
$string['config_export_path'] = 'Enter the relative path, from moodledata, to store export files.';
$string['config_export_file_timestamp'] = 'If enabled, adds timestamp to export filename.';
$string['configincrementaldelta'] = 'The time delta specifies how far back the manual export includes data from. The time delta must be specified in \*d\*h\*m format, with values representing days, hours and minutes.
This setting only takes effect when using the incremental manual export, and is based on the time at which a user\'s course grade was last modified.
This setting is not used in a scheduled incremental backup. The scheduled incremental backup uses the last schedule run time.';
$string['configlogfilelocation'] = 'The directory path, relative to moodledata, to the location of log files.';
$string['confignonincremental'] = 'Include all historical data in manual exports';
$string['configuretitle'] = 'Plugins Blocks: Configure Version 1 Export Fields';
$string['configplaceholdersetting'] = 'Replace with real settings';
$string['configheader_field_categories'] = 'Field Categories';
$string['configheader_available_fields'] = 'Available Fields';
$string['configheader_active_fields'] = 'Active Fields';
$string['curass_expires'] = 'Student Assignment Expires';
$string['emailnotification'] = 'Email notification';
$string['customfieldname'] = 'Custom Field Name';
$string['customfieldsuccessdelete'] = 'Custom field successfully deleted.';
$string['customfieldsuccessupdate'] = 'Custom field(s) successfully updated.';
$string['customfieldnotconfig'] = 'There are no custom fields configured.';
$string['customfieldalladded'] = 'All custom fields have been added.';
$string['date_format'] = 'M/d/Y';
$string['datetime_format'] = 'M/d/Y:H:i';
$string['export_file'] = 'Export filename';
$string['export_file_timestamp'] = '%b_%d_%Y_%H%M%S';
$string['export_file_timestamp_label'] = 'Timestamp export file';
$string['export_path'] = 'Export path';
$string['exportfilesheading'] = 'Scheduled Export File Locations';
$string['export_path'] = 'Export path';
$string['fieldset_user_label'] = 'User';
$string['fieldset_student_label'] = 'Student';
$string['fieldset_course_label'] = 'Course';
$string['fieldset_class_label'] = 'Class';
$string['fieldset_program_label'] = 'Program';
$string['header_courseidnumber'] = 'Course Idnumber';
$string['header_firstname'] = 'First Name';
$string['header_grade'] = 'Grade';
$string['header_lastname'] = 'Last Name';
$string['header_letter'] = 'Letter';
$string['header_startdate'] = 'Start Date';
$string['header_enddate'] = 'End Date';
$string['header_status'] = 'Status';
$string['header_useridnumber'] = 'User Idnumber';
$string['header_username'] = 'Username';
$string['incrementaldelta'] = 'Time delta for incremental manual export';
$string['logging'] = 'Logging';
$string['logfilelocation'] = 'Log file location';
$string['nodatemarker'] = '-';
$string['nonincremental'] = 'Enable non-incremental export';
$string['placeholdersetting'] = 'Placeholder for settings';
$string['pluginname'] = 'Version 1 ELIS export';
$string['rename'] = 'Set Custom Name';
$string['revertheaders'] = 'Reset fields';
$string['timeperiodheader'] = 'Time Period Settings';
$string['updateheaders'] = 'Save changes';
