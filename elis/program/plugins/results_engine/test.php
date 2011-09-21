<?php

// Delete this file when test harness is no longer needed.

// Needed to prevent dieing.
define('MOODLE_INTERNAL', 1);

require_once(dirname(__FILE__) .'/lib.php');
results_engine_cron();