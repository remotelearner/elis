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

require_once('../../config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_log_filtering.class.php');

//permissions checking
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

//page information
$page = optional_param('page', 0, PARAM_INT);
$baseurl = $CFG->wwwroot.'/local/datahub/viewlogs.php';

//header
admin_externalpage_setup('rliplogs');
$PAGE->requires->css('/local/datahub/styles.css');
echo $OUTPUT->header();

//filters
$filtering = new rlip_log_filtering();
list($extrasql, $params) = $filtering->get_sql_filter();

//top paging bar
$numrecords = rlip_count_logs($extrasql, $params);
echo $OUTPUT->paging_bar($numrecords, $page, RLIP_LOGS_PER_PAGE, $baseurl);

$filtering->display_add();
$filtering->display_active();

//display main table
$logs = rlip_get_logs($extrasql, $params, $page);
$table = rlip_get_log_table($logs);
echo rlip_log_table_html($table);

//bottom paging bar
echo $OUTPUT->paging_bar($numrecords, $page, RLIP_LOGS_PER_PAGE, $baseurl);

//footer
echo $OUTPUT->footer();
