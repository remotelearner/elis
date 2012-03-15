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

$string['confirm_delete_ipjob'] = 'Are you sure to delete IP job #{$a}?';
$string['dateformat'] = 'M/d/Y';
$string['go'] = 'Go';
$string['iptypenotsupported'] = 'IP plugin type: \'{$a}\' not supported!';
$string['logtimeformat'] = '%b/%d/%Y:%H:%M:%S';
$string['managesched'] = 'Manage';
$string['manualstatus'] = '{$a->statusmessage} ({$a->filesuccesses} of {$a->total})';
$string['na'] = 'N/A';
$string['no_lastruntime'] = 'Never run';
$string['pluginname'] = 'Integration Point';
$string['plugins'] = 'Integration Point plugins';
$string['rlip_form_export_header'] = '<b>Schedule IP job for: Export</b>';
$string['rlip_form_import_header'] = '<b>Schedule IP job for: Import</b>';
$string['rlip_form_label'] = 'Label:';
$string['rlip_form_label_help'] = 'Enter a unique label for your IP schedule job.';
$string['rlip_form_period'] = 'Period:';
$string['rlip_form_period_error'] = 'IP schedule period must be of form: *d*h*m and greater than 5 minutes!';
$string['rlip_form_period_help'] = 'This setting controls how often the IP schedule job is run. Values can be entered in the form \*d\*h\*m to represent days, hours, and minutes. Please set this value to five minutes or longer to allow ample time for processing.';
$string['rlip_jobs_header_label'] = 'Label';
$string['rlip_jobs_header_lastran'] = 'Last ran at';
$string['rlip_jobs_header_modified'] = 'Modified';
$string['rlip_jobs_header_nextrun'] = 'Will run next at';
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

