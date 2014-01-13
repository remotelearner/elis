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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once dirname(__FILE__) . '/lib/setup.php';
require_once elispm::lib('resultsengine.php');

$id = required_param('id', PARAM_INT);

require_login();

$context = context::instance_by_id($id);

if ((! $context) || (($context->contextlevel != CONTEXT_ELIS_CLASS) && ($context->contextlevel != CONTEXT_ELIS_COURSE))) {
    print_string('results_unknown_classcourse', RESULTS_ENGINE_LANG_FILE);
    exit;
}

$capability = 'local/elisprogram:course_edit';
$table      = 'local_elisprogram_crs';
$fields     = 'id, name as idnumber';

if ($context->contextlevel == CONTEXT_ELIS_CLASS) {
    $capability = 'local/elisprogram:class_edit';
    $table      = 'local_elisprogram_cls';
    $fields     = 'id, idnumber';
}

if (! has_capability($capability, $context)) {
    print_string('results_not_permitted', RESULTS_ENGINE_LANG_FILE);
    exit;
}

$object = $DB->get_record($table, array('id' => $context->instanceid), $fields);
$source = $CFG->wwwroot .'/local/elisprogram/resultsmanualprocess.php';

$PAGE->requires->yui_module('moodle-local_elisprogram-resultsengine', 'M.local_elisprogram.init_processmanual',
        array(
                array(
                    'source' => $source,
                    'id' => $id,
                    'message' => get_string('results_done', RESULTS_ENGINE_LANG_FILE)
                )
            )
);
$PAGE->set_context($context);
$PAGE->set_url($_SERVER['PHP_SELF']);
$PAGE->set_pagelayout('popup');

print($OUTPUT->header());
print_string('results_processing_manual', RESULTS_ENGINE_LANG_FILE, $object);
print('<div id="results"></div>');
print($OUTPUT->footer());