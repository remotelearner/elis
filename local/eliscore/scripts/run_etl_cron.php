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
 * @package    local_eliscore
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

// Check if the constant is already defined for PHPUnit.
if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once(dirname(__FILE__).'/../plugins/etl/etl.php');

declare(ticks = 1); // This is needed for the pcntl_signal function to work.

$timenow = time();

$period = new stdClass();
$period->hours = 0;
$period->minutes = 0;

// Create list of accepted arguments.
$argumentmap = array(
    'h' => 'help',
    'm' => 'minutes',
    'H' => 'hours'
);

// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false), $argumentmap);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = get_string('cli_help', 'eliscore_etl');
    echo $help;
    die;
}

// Validate minutes argument.
if (array_key_exists('minutes', $options)) {
    if (!validate_parameter($options['minutes'])) {
        cli_error(get_string('cli_error_minutes', 'eliscore_etl'));
    }

    $period->minutes = (int) $options['minutes'];
}

// Validate hours argument.
if (array_key_exists('hours', $options)) {
    if (!validate_parameter($options['hours'])) {
        cli_error(get_string('cli_error_hours', 'eliscore_etl'));
    }

    $period->hours = (int) $options['hours'];
}

$durationinseconds = convert_time_to_seconds($period);
if (0 == $durationinseconds || false == $durationinseconds) {
    cli_error(get_string('cli_error_zero_duration', 'eliscore_etl'));
}

if ($durationinseconds > ETL_BLOCKED_MAX_TIME) {
    cli_error(get_string('cli_error_max_time_exceeded', 'eliscore_etl', ETL_BLOCKED_MAX_TIME));
}

// Print heading.
cli_heading(get_string('cli_run_etl_cron_heading', 'eliscore_etl', $period));

// Check for existing block.
$task = $DB->get_record('local_eliscore_sched_tasks', array('plugin' => 'eliscore_etl'));
if (!empty($task->blocked) && $timenow < $task->blocked) {
    cli_error(get_string('cli_error_blocked', 'eliscore_etl'));
}

$etlobj = new eliscore_etl_useractivity($durationinseconds);

// Set callback method incase the script is terminated.
if (function_exists('pcntl_signal')) {
    $signals = array(SIGINT, SIGTERM, SIGHUP, SIGQUIT, SIGABRT, SIGUSR1, SIGUSR2);
    foreach ($signals as $signal) {
        pcntl_signal($signal, array(&$etlobj, 'save_current_etl_state'));
    }
} else {
    cli_error(get_string('cli_error_no_pcntl', 'eliscore_etl'));
}

// Begin to process ETL cron.
user_activity_etl_cron('CLI', $durationinseconds, $etlobj);

// 0 means success.
exit(0);
