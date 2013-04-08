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
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['automatic'] = 'Automatic';
$string['confirm_delete_ipjob'] = 'Are you sure to delete DH job #{$a}?';
$string['configdisableincron'] = 'If enabled, this setting disables this plugin from running in the Moodle cron. Use this setting when running imports & exports via a separate system-level cron.';
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
$string['rlipexportplugins'] = 'Export Plugins';
$string['rlipimportplugins'] = 'Import Plugins';
$string['rlipmanageplugins'] = 'Manage plugins';
$string['runnow'] = 'Run Now';
$string['runmanually'] = 'Run manually';
$string['scheduled'] = 'scheduled';
$string['schedulepagetitle'] = 'Schedule DH';
$string['schedulingtime'] = "*Scheduled jobs may not run exactly at the specified time.";
