<?php
/**
 * General class for displaying pages in the curriculum management system.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

// Delete this file when test harness is no longer needed.
require_once(dirname(__FILE__) .'/../../lib/setup.php');
require_once(dirname(__FILE__) .'/lib.php');

$id = required_param('id', PARAM_INT);

if (! isloggedin()) {
    print_string('loggedinnot');
    exit;
}

$classlevel = context_level_base::get_custom_context_level('class', 'elis_program');
$context = get_context_instance($classlevel, $id);

if (! has_capability('elis/program:class_edit', $context)) {
    print_string('not_permitted', RESULTS_ENGINE_LANG_FILE);
    exit;
}

if (results_engine_manual($id)) {
    print_string('manual_success', RESULTS_ENGINE_LANG_FILE);
} else {
    print_string('manual_failure', RESULTS_ENGINE_LANG_FILE);
}