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
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['automatic'] = 'Automatic';
$string['confirm_delete_ipjob'] = 'Are you sure to delete DH job #{$a}?';
$string['configdisableincron'] = 'If enabled, this setting disables this plugin from running in the Moodle cron. Use this setting when running imports & exports via a separate system-level cron.';
$string['datahub:addinstance'] = 'Add a DataHub instance';
$string['datahub_settings'] = 'Datahub Settings';
$string['dateformat'] = 'M/d/Y';
$string['dblogimportexceedstimelimit'] = 'Failed importing all lines from import file {$a->filename} due to time limit exceeded. Processed {$a->recordsprocessed} of {$a->totalrecords} records.';
$string['disableincron'] = 'Disable in Moodle cron';
$string['displaytimeformat'] = '%b/%d/%Y, %I:%M:%S %P';
$string['duplicate'] = 'Please enter a unique value';
$string['export'] = 'Export';
$string['exportexceedstimelimit'] = 'Export processing aborted due to time restrictions. Export will be attempted again at next cron.';
$string['exportinvalidlogfilepath'] = 'Log file access failed during export due to invalid logfile path: {$a->logfilepath}.';
$string['ext_cron_usage'] = 'requires 4 parameters: <plugin> <userid> <targetstarttime> <lastruntime> - only got {$a} parameter(s).';
$string['file_timestamp'] = '%b_%d_%Y_%H%M%S';
$string['go'] = 'Go';
$string['import'] = 'Import';
$string['importexceedstimelimit'] = 'Import processing of entity \'{$a->entity}\' partially processed due to time restrictions. Import will continue at next cron.';
$string['importexceedstimelimit_b'] = 'Import processing of entity \'{$a->entity}\' partially processed due to time restrictions. Processed {$a->recordsprocessed} of {$a->totalrecords} total records. Import will continue at next cron.';
$string['importinvalidlogfilepath'] = 'Log file access failed while importing lines from import file {$a->filename} due to invalid logfile path. Change \'{$a->logfilepath}\' to a valid logfile location on the settings page. Processed {$a->recordsprocessed} of {$a->totalrecords} records.';
$string['importwarning'] = 'Manual processing of import files should only be used for testing purposes and only with small files. Bulk import actions should be performed using the scheduling system.';
$string['iptypenotsupported'] = 'DH plugin type: \'{$a}\' not supported!';
$string['log'] = 'Log';
$string['logdownload'] = 'Download';
$string['logs'] = 'Data Hub logs';
$string['logend'] = 'End time';
$string['logentitytype'] = 'Entity Type';
$string['logexecution'] = 'Execution';
$string['logfilefailures'] = 'Records with error';
$string['logfilesuccesses'] = 'Records successfully processed';
$string['logfile_timestamp'] = '%b_%d_%Y_%H%M%S';
$string['logfiledaily_timestamp'] = '%b_%d_%Y';
$string['logplugin'] = 'Plugin';
$string['logscheduledstart'] = 'Scheduled start time';
$string['logstart'] = 'Actual start time';
$string['logstatus'] = 'Status';
$string['logtasktype'] = 'Task type';
$string['logtimeformat'] = '%b/%d/%Y:%H:%M:%S';
$string['loguser'] = 'User';
$string['managesched'] = 'Manage';
$string['manual'] = 'Manual';
$string['manualexportexceedstimelimit'] = 'Export processing aborted due to time restrictions.';
$string['manualimportexceedstimelimit'] = 'Import processing of entity \'{$a->entity}\' partially processed due to time restrictions.';
$string['manualimportexceedstimelimit_b'] = 'Import processing of entity \'{$a->entity}\' partially processed due to time restrictions. Processed {$a->recordsprocessed} of {$a->totalrecords} total records.';
$string['manualstatus'] = '{$a->statusmessage} ({$a->filesuccesses} of {$a->total})';
$string['na'] = 'N/A';
$string['no_lastruntime'] = 'Never run';
$string['nologmessage'] = 'Sorry, no logs found.';
$string['notificationemailmessage'] = 'The Data Hub {$a} plugin has run and generated the attached log file(s). Please view log file content for information regarding the success of the associated Data Hub task. ';
$string['notificationemailsubject'] = 'Data Hub {$a} logs(s)';
$string['pluginname'] = 'Data Hub';
$string['plugins'] = 'Data Hub plugins';
$string['restoredefaults'] = 'Restore defaults';
$string['rlip:addinstance'] = 'Add a new Data Hub block';
$string['rlip_form_export_header'] = '<b>Schedule DH job for: Export</b>';
$string['rlip_form_import_header'] = '<b>Schedule DH job for: Import</b>';
$string['rlip_form_label'] = 'Label:';
$string['rlip_form_label_help'] = 'Enter a unique label for your DH schedule job.';
$string['rlip_form_period'] = 'Period:';
$string['rlip_form_period_error'] = 'DH schedule period must be of form: *d*h*m and greater than 5 minutes!';
$string['rlip_form_period_help'] = 'This setting controls how often the DH schedule job is run. Values can be entered in the form \*d\*h\*m to represent days, hours, and minutes. Please set this value to five minutes or longer to allow ample time for processing.';
$string['rlip_global_scheduling'] = 'Scheduling';
$string['rlip_jobs_header_label'] = 'Label';
$string['rlip_jobs_header_lastran'] = 'Last ran at';
$string['rlip_jobs_header_modified'] = 'Modified';
$string['rlip_jobs_header_nextrun'] = 'Will run next at*';
$string['rlip_jobs_header_owner'] = 'Owner';
$string['rlip_jobs_heading_fullinstructions'] = 'Click a scheduled job\'s label to edit it or click the "New job" button below to schedule an DH job.<br/>&nbsp;';
$string['rlip_jobs_heading_jobs'] = 'Current scheduled DH jobs for: {$a}';
$string['rlip_jobs_heading_nojobs'] = 'There are no scheduled DH jobs for: {$a}';
$string['rlip_jobs_heading_instructions'] = 'Click the "New job" button below to schedule an DH job.';
$string['rlip_new_job'] = 'New job';
$string['dhexportplugins'] = 'Export Plugins';
$string['dhimportplugins'] = 'Import Plugins';
$string['rlipmanageplugins'] = 'Manage plugins';
$string['runnow'] = 'Run Now';
$string['runmanually'] = 'Run manually';
$string['cronforcedinconfig'] = '"Disable in Moodle cron" has been forced on in config.php: $CFG->forcedatahubcron';
$string['scheduled'] = 'scheduled';
$string['schedulepagetitle'] = 'Schedule DH';
$string['schedulingtime'] = "*Scheduled jobs may not run exactly at the specified time.";

$string['ws_bad_param'] = 'Invalid parameter received: {$a}';
$string['ws_unknown_error_code'] = 'unknown_error';
$string['ws_unknown_error_msg'] = 'An unknown error occurred';
$string['ws_class_delete_success_code'] = 'class_deleted';
$string['ws_class_delete_success_msg'] = 'Class deleted successfully';
$string['ws_class_delete_fail'] = 'Could not delete class';
$string['ws_class_delete_fail_invalid_idnumber'] = 'Class idnumber: \'{$a->idnumber}\' is not a valid class.';
$string['ws_user_create_success_code'] = 'user_created';
$string['ws_user_create_success_msg'] = 'User created successfully';
$string['ws_user_create_fail'] = 'Could not create user';
$string['ws_user_update_success_code'] = 'user_updated';
$string['ws_user_update_success_msg'] = 'User updated successfully';
$string['ws_user_update_fail_conflictingidfields'] = 'Conflicting identifying fields received: supplied {$a} do not refer to the same user.';
$string['ws_user_update_fail_noidfields'] = 'No valid identifying fields received';
$string['ws_user_update_fail_idfieldsnotallowed'] = 'Identifying fields cannot be updated using this method. Please use local_datahub_elis_user_update_identifiers() instead.';
$string['ws_user_update_fail_multipleusersforidentifier'] = 'Multiple users found for field {$a}. Try another identifying field?';
$string['ws_user_update_fail'] = 'Could not update user';
$string['ws_user_update_identifiers_success_code'] = 'user_identfiers_updated';
$string['ws_user_update_identifiers_success_msg'] = 'User identifiers updated successfully';
$string['ws_user_update_identifiers_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_user_delete_success_code'] = 'user_deleted';
$string['ws_user_delete_success_msg'] = 'User deleted successfully';
$string['ws_user_delete_fail_conflictingidfields'] = 'Conflicting identifying fields received';
$string['ws_user_delete_fail_noidfields'] = 'No valid identifying fields received';
$string['ws_user_delete_fail'] = 'Could not delete user';
$string['ws_program_enrolment_create_success_code'] = 'program_enrolment_created';
$string['ws_program_enrolment_create_success_msg'] = 'User successfully enroled into Program';
$string['ws_program_enrolment_create_fail'] = 'Could not enrol user into program';
$string['ws_program_enrolment_create_fail_invalid_program'] = 'Program identified by program_idnumber \'{$a->program_idnumber}\' is not a valid program.';
$string['ws_program_enrolment_create_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_program_enrolment_delete_success_code'] = 'program_enrolment_deleted';
$string['ws_program_enrolment_delete_success_msg'] = 'User successfully unenroled from Program';
$string['ws_program_enrolment_delete_fail'] = 'Could not unenrol user from program';
$string['ws_program_enrolment_delete_fail_invalid_program'] = 'Program identified by program_idnumber \'{$a->program_idnumber}\' is not a valid program.';
$string['ws_program_enrolment_delete_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_track_enrolment_create_success_code'] = 'track_enrolment_created';
$string['ws_track_enrolment_create_success_msg'] = 'User successfully enroled into track';
$string['ws_track_enrolment_create_fail'] = 'Could not enrol user into track';
$string['ws_track_enrolment_create_fail_invalid_track'] = 'Track identified by track_idnumber \'{$a->track_idnumber}\' is not a valid track.';
$string['ws_track_enrolment_create_fail_invalid_user'] = 'User identified by {$a->userparams} was not found.';
$string['ws_track_enrolment_delete_success_code'] = 'track_enrolment_deleted';
$string['ws_track_enrolment_delete_success_msg'] = 'User successfully unenroled from track';
$string['ws_track_enrolment_delete_fail'] = 'Could not unenrol user from track';
$string['ws_track_enrolment_delete_fail_invalid_track'] = 'Track identified by track_idnumber \'{$a->track_idnumber}\' is not a valid track.';
$string['ws_track_enrolment_delete_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_class_enrolment_create_success_code'] = 'class_enrolment_created';
$string['ws_class_enrolment_create_success_msg'] = 'User successfully enroled into Class';
$string['ws_class_enrolment_create_fail'] = 'Could not enrol user into class';
$string['ws_class_enrolment_create_fail_invalid_class'] = 'Class identified by class_idnumber \'{$a->class_idnumber}\' is not a valid class.';
$string['ws_class_enrolment_create_fail_invalid_completestatus'] = 'Class completion status: \'{$a->completionstatus}\' is not a valid class completion status - must be: notcompleted, passed or failed.';
$string['ws_class_enrolment_create_fail_invalid_completetime'] = 'Class completetime: \'{$a->completetime}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_class_enrolment_create_fail_invalid_enrolmenttime'] = 'Class enrolmenttime: \'{$a->enrolmenttime}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_class_enrolment_create_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_class_enrolment_update_success_code'] = 'class_enrolment_updated';
$string['ws_class_enrolment_update_success_msg'] = 'User class enrolment successfully updated';
$string['ws_class_enrolment_update_fail'] = 'Could not update user class enrolment';
$string['ws_class_enrolment_update_fail_invalid_class'] = 'Class identified by class_idnumber \'{$a->class_idnumber}\' is not a valid class.';
$string['ws_class_enrolment_update_fail_invalid_enrolmenttime'] = 'Class enrolment time: \'{$a->enrolmenttime}\' is not a valid date - must be in format MMM/DD/YYYY';
$string['ws_class_enrolment_update_fail_invalid_completetime'] = 'Class completion time: \'{$a->completetime}\' is not a valid date - must be in format MMM/DD/YYYY';
$string['ws_class_enrolment_update_fail_invalid_completestatus'] = 'Class completion status: \'{$a->completionstatus}\' is not a valid class completion status - must be: notcompleted, passed or failed.';
$string['ws_class_enrolment_update_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_class_enrolment_update_fail_missing_enrolment'] = 'No class enrolment record found for class identified by class_idnumber \'{$a->class_idnumber}\'.';
$string['ws_class_enrolment_delete_success_code'] = 'class_enrolment_deleted';
$string['ws_class_enrolment_delete_success_msg'] = 'User successfully unenroled from Class';
$string['ws_class_enrolment_delete_fail'] = 'Could not unenrol user from class';
$string['ws_class_enrolment_delete_fail_invalid_class'] = 'Class identified by class_idnumber \'{$a->class_idnumber}\' is not a valid class.';
$string['ws_class_enrolment_delete_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_userset_enrolment_create_success_code'] = 'userset_enrolment_created';
$string['ws_userset_enrolment_create_success_msg'] = 'User successfully enroled into Userset';
$string['ws_userset_enrolment_create_fail'] = 'Could not enrol user into userset';
$string['ws_userset_enrolment_create_fail_invalid_userset'] = 'Userset identified by userset_name \'{$a->userset_name}\' is not a valid userset.';
$string['ws_userset_enrolment_create_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_userset_enrolment_delete_success_code'] = 'userset_enrolment_deleted';
$string['ws_userset_enrolment_delete_success_msg'] = 'User successfully unenroled from Userset';
$string['ws_userset_enrolment_delete_fail'] = 'Could not unenrol user from userset';
$string['ws_userset_enrolment_delete_fail_invalid_userset'] = 'Userset identified by userset_name \'{$a->userset_name}\' is not a valid userset.';
$string['ws_userset_enrolment_delete_fail_invalid_user'] = 'No unique user identified by {$a->userparams} was found.';
$string['ws_course_create_success_code'] = 'course_created';
$string['ws_course_create_success_msg'] = 'ELIS course description created successfully';
$string['ws_course_create_fail'] = 'Could not create ELIS course description';
$string['ws_course_create_fail_invalid_credits'] = 'Credits \'{$a->credits}\' is not valid - must be numeric 0 or larger.';
$string['ws_course_create_fail_invalid_completion_grade'] = 'Completion grade \'{$a->completion_grade}\' is not valid - must be between 0 and 100.';
$string['ws_course_create_fail_invalid_assignment'] = 'Program identified by idnumber \'{$a->assignment}\' is not a valid program.';
$string['ws_course_create_fail_invalid_link'] = 'Moodle course identified by shortname \'{$a->link}\' is not a valid Moodle course.';
$string['ws_course_update_success_code'] = 'course_updated';
$string['ws_course_update_success_msg'] = 'ELIS course description updated successfully';
$string['ws_course_update_fail'] = 'Could not update ELIS course description';
$string['ws_course_update_fail_invalid_credits'] = 'Credits \'{$a->credits}\' is not valid - must be numeric 0 or larger.';
$string['ws_course_update_fail_invalid_completion_grade'] = 'Completion grade \'{$a->completion_grade}\' is not valid - must be between 0 and 100.';
$string['ws_course_update_fail_invalid_assignment'] = 'Program identified by idnumber \'{$a->assignment}\' is not a valid program.';
$string['ws_course_update_fail_invalid_link'] = 'Moodle course identified by shortname \'{$a->link}\' is not a valid Moodle course.';
$string['ws_course_delete_success_code'] = 'course_deleted';
$string['ws_course_delete_success_msg'] = 'ELIS course deleted successfully';
$string['ws_course_delete_fail'] = 'Could not delete ELIS course';
$string['ws_course_delete_fail_invalid_idnumber'] = 'Course idnumber: \'{$a->idnumber}\' is not a valid ELIS course.';
$string['ws_class_create_success_code'] = 'class_created';
$string['ws_class_create_success_msg'] = 'Class created successfully';
$string['ws_class_create_fail'] = 'Could not create class.';
$string['ws_class_create_fail_duplicateidnumber'] = 'Could not create class - duplicate idnumber received.';
$string['ws_class_create_fail_invalidcourseassignment'] = 'Could not create class - invalid course idnumber received for parameter "assignment"';
$string['ws_class_update_success_code'] = 'class_updated';
$string['ws_class_update_success_msg'] = 'Class updated successfully';
$string['ws_class_update_fail_badidnumber'] = 'Could not find class with that idnumber.';
$string['ws_class_update_fail_cannotreassign'] = 'Class instance was not re-assigned to course description because moving class instances between course descriptions is not supported.';
$string['ws_function_requires_elis'] = 'This webservices method requires ELIS. ELIS was not found. Cannot continue.';
$string['ws_program_create_success_code'] = 'program_created';
$string['ws_program_create_success_msg'] = 'Program created successfully';
$string['ws_program_create_fail'] = 'Could not create program';
$string['ws_program_create_fail_invalid_reqcredits'] = 'Program reqcredits: \'{$a->reqcredits}\' is not valid, must be a number with at most ten total digits and two decimal digits.';
$string['ws_program_create_fail_invalid_timetocomplete'] = 'Program timetocomplete: \'{$a->timetocomplete}\' is not a valid time delta in *h, *d, *w, *m, *y format.';
$string['ws_program_create_fail_invalid_frequency'] = 'Program frequency: \'{$a->frequency}\' is not a valid time delta in *h, *d, *w, *m, *y format.';
$string['ws_program_create_fail_invalid_priority'] = 'Program priority: \'{$a->priority}\' is not valid, must be a number between 0 and 10.';
$string['ws_program_update_success_code'] = 'program_updated';
$string['ws_program_update_success_msg'] = 'Program updated successfully';
$string['ws_program_update_fail'] = 'Could not update program';
$string['ws_program_update_fail_invalid_idnumber'] = 'Program idnumber: \'{$a->idnumber}\' is not a valid program.';
$string['ws_program_update_fail_invalid_reqcredits'] = 'Program reqcredits: \'{$a->reqcredits}\' is not valid, must be a number with at most ten total digits and two decimal digits.';
$string['ws_program_update_fail_invalid_timetocomplete'] = 'Program timetocomplete: \'{$a->timetocomplete}\' is not a valid time delta in *h, *d, *w, *m, *y format.';
$string['ws_program_update_fail_invalid_frequency'] = 'Program frequency: \'{$a->frequency}\' is not a valid time delta in *h, *d, *w, *m, *y format.';
$string['ws_program_update_fail_invalid_priority'] = 'Program priority: \'{$a->priority}\' is not valid, must be a number between 0 and 10.';
$string['ws_program_delete_success_code'] = 'program_deleted';
$string['ws_program_delete_success_msg'] = 'Program deleted successfully';
$string['ws_program_delete_fail'] = 'Could not delete program';
$string['ws_program_delete_fail_invalid_idnumber'] = 'Program idnumber: \'{$a->idnumber}\' is not a valid program.';
$string['ws_track_create_success_code'] = 'track_created';
$string['ws_track_create_success_msg'] = 'Track created successfully';
$string['ws_track_create_fail'] = 'Could not create track';
$string['ws_track_create_fail_invalid_assignment'] = 'Track assignment: \'{$a->assignment}\' is not a valid program idnumber.';
$string['ws_track_create_fail_invalid_startdate'] = 'Track startdate: \'{$a->startdate}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_track_create_fail_invalid_enddate'] = 'Track enddate: \'{$a->enddate}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_track_update_success_code'] = 'track_updated';
$string['ws_track_update_success_msg'] = 'Track updated successfully';
$string['ws_track_update_fail'] = 'Could not update track';
$string['ws_track_update_fail_invalid_idnumber'] = 'Track idnumber: \'{$a->idnumber}\' is not a valid track.';
$string['ws_track_update_fail_invalid_startdate'] = 'Track startdate: \'{$a->startdate}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_track_update_fail_invalid_enddate'] = 'Track enddate: \'{$a->enddate}\' is not a valid date in MMM/DD/YYYY format.';
$string['ws_track_delete_success_code'] = 'track_deleted';
$string['ws_track_delete_success_msg'] = 'Track deleted successfully';
$string['ws_track_delete_fail'] = 'Could not delete track';
$string['ws_track_delete_fail_invalid_idnumber'] = 'Track idnumber: \'{$a->idnumber}\' is not a valid track.';
$string['ws_userset_create_success_code'] = 'userset_created';
$string['ws_userset_create_success_msg'] = 'Userset created successfully';
$string['ws_userset_create_fail'] = 'Could not create userset';
$string['ws_userset_create_fail_invalid_parent'] = 'Userset parent: \'{$a->parent}\' is not a valid userset.';
$string['ws_userset_update_success_code'] = 'userset_updated';
$string['ws_userset_update_success_msg'] = 'Userset updated successfully';
$string['ws_userset_update_fail'] = 'Could not update userset';
$string['ws_userset_update_fail_invalid_name'] = 'Userset name: \'{$a->name}\' is not a valid userset.';
$string['ws_userset_update_fail_invalid_parent'] = 'Userset parent: \'{$a->parent}\' is not a valid userset.';
$string['ws_userset_delete_recursive'] = '_recursive';
$string['ws_userset_delete_subsets'] = ' and all of its subsets';
$string['ws_userset_delete_success_code'] = 'userset_deleted{$a}';
$string['ws_userset_delete_success_msg'] = 'Userset{$a} deleted successfully';
$string['ws_userset_delete_fail'] = 'Could not delete userset';
$string['ws_userset_delete_fail_invalid_name'] = 'Userset name: \'{$a->name}\' is not a valid userset.';
