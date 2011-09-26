<?php

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