<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['automatic'] = 'Automatic';
$string['confirm_delete_ipjob'] = 'Are you sure to delete IP job #{$a}?';
$string['configdisableincron'] = 'When configured, the setting disables this plugin from running in the Moodle cron. Use this setting when running imports via a separate system-level cron.';
$string['dateformat'] = 'M/d/Y';
$string['disableincron'] = 'Disable in Moodle cron';
$string['displaytimeformat'] = '%b/%d/%Y, %I:%M:%S %P';
$string['duplicate'] = 'Please enter a unique value';
$string['export'] = 'Export';
$string['exportexceedstimelimit'] = 'Export processing aborted due to time restrictions. Export will be attempted again at next cron.';
$string['go'] = 'Go';
$string['import'] = 'Import';
$string['importexceedstimelimit'] = 'Import processing of entity \'{$a->entity}\' aborted due to time restrictions. Import will continue at next cron.';
$string['importexceedstimelimit_b'] = 'Import processing of entity \'{$a->entity}\' aborted due to time restrictions. Processed {$a->linenumber} line(s) of {$a->filelines} total records. Import will continue at next cron.';
$string['iptypenotsupported'] = 'IP plugin type: \'{$a}\' not supported!';
$string['logs'] = 'Integration Point logs';
$string['logend'] = 'End time';
$string['logexecution'] = 'Execution';
$string['logfilefailures'] = 'Records with error';
$string['logfilesuccesses'] = 'Records successfully processed';
$string['logplugin'] = 'Plugin';
$string['logscheduledstart'] = 'Scheduled start time';
$string['logstart'] = 'Actual start time';
$string['logstatus'] = 'Status';
$string['logtasktype'] = 'Task type';
$string['logtimeformat'] = '%b/%d/%Y:%H:%M:%S';
$string['loguser'] = 'User';
$string['managesched'] = 'Manage';
$string['manual'] = 'Manual';
$string['manualstatus'] = '{$a->statusmessage} ({$a->filesuccesses} of {$a->total})';
$string['na'] = 'N/A';
$string['no_lastruntime'] = 'Never run';
$string['nologmessage'] = 'Sorry, no logs found.';
$string['pluginname'] = 'Integration Point';
$string['plugins'] = 'Integration Point plugins';
$string['restoredefaults'] = 'Restore defaults';
$string['rlip_form_export_header'] = '<b>Schedule IP job for: Export</b>';
$string['rlip_form_import_header'] = '<b>Schedule IP job for: Import</b>';
$string['rlip_form_label'] = 'Label:';
$string['rlip_form_label_help'] = 'Enter a unique label for your IP schedule job.';
$string['rlip_form_period'] = 'Period:';
$string['rlip_form_period_error'] = 'IP schedule period must be of form: *d*h*m and greater than 5 minutes!';
$string['rlip_form_period_help'] = 'This setting controls how often the IP schedule job is run. Values can be entered in the form \*d\*h\*m to represent days, hours, and minutes. Please set this value to five minutes or longer to allow ample time for processing.';
$string['rlip_global_scheduling'] = 'Scheduling';
$string['rlip_jobs_header_label'] = 'Label';
$string['rlip_jobs_header_lastran'] = 'Last ran at';
$string['rlip_jobs_header_modified'] = 'Modified';
$string['rlip_jobs_header_nextrun'] = 'Will run next at*';
$string['rlip_jobs_header_owner'] = 'Owner';
$string['rlip_jobs_heading_fullinstructions'] = 'Click a scheduled job\'s label to edit it or click the "New job" button below to schedule an IP job.<br/>&nbsp;';
$string['rlip_jobs_heading_jobs'] = 'Current scheduled IP jobs for: {$a}';
$string['rlip_jobs_heading_nojobs'] = 'There are no scheduled IP jobs for: {$a}';
$string['rlip_jobs_heading_instructions'] = 'Click the "New job" button below to schedule an IP job.';
$string['rlip_new_job'] = 'New job';
$string['rlipexportplugins'] = 'Export Plugins';
$string['rlipimportplugins'] = 'Import Plugins';
$string['runnow'] = 'Run Now';
$string['runmanually'] = 'Run manually';
$string['schedulepagetitle'] = 'Schedule IP';
$string['schedulingtime'] = "*Scheduled jobs may not run exactly at the specified time.";
