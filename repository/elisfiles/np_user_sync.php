<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a script that allows for synchronising users that do not use a username & password to authenticate into Moodle
 * (OpenID and CAS for now) over to Alfresco so that they can properly access the ELIS Files interface.
 *
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('CLI_SCRIPT', true);  // This script may only be run from the commandline


require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');


$repo = repository_factory::factory('elisfiles');

if (!$repo) {
    mtrace(get_string('couldnotinitializerepository', 'repository_elisfiles'));
    exit;
}

set_time_limit(0);  // This may take a long time.

$errors = 0;
$auths = elis_files_nopasswd_auths();

if (empty($auths) || count($auths) === 0) {
    exit;
}

$select = '';
$params = array();

if (count($auths) == 1) {
    $select          = 'auth = :auth1';
    $params['auth1'] = current($auths);
} else {
    $selects = array();

    for ($i = 1; $i <= count($auths); $i++) {
        $selects[]        .= ':auth'.$i;
        $params['auth'.$i] = $auths[$i - 1];
    }

    $select = 'auth IN ('.implode(', ', $selects).')';
}

$select       .= ' AND deleted = :del';
$params['del'] = 0;

$rs = $DB->get_recordset_select('user', $select, $params, 'id ASC', 'id, username, email, firstname, lastname, institution');

$ucount = 0;

if (!empty($rs)) {
    $totalusers = $DB->count_records_select('user', $select, $params);
    $pad        = strlen($totalusers); // Padding for number display so output lines up correctly

    mtrace("\n".get_string('np_startingmigration', 'repository_elisfiles', $totalusers)."\n");

    foreach ($rs as $user) {
        $ucount++;

        // Generate the random password and attempt to synchronise the user over to the Alfresco repository
        $passwd     = random_string(8);
        $migrate_ok = $repo->migrate_user($user, $passwd);

        // Print out the current record / total that is being migrated
        mtrace(sprintf(" %{$pad}s / {$totalusers}:", $ucount), '');

        $a = new stdClass;
        $a->id       = $user->id;
        $a->username = $user->username;
        $a->fullname = fullname($user);

        if (!$migrate_ok) {
            $errors++;
            mtrace(get_string('np_migratefailure', 'repository_elisfiles', $a));
        } else {
            mtrace(get_string('np_migratesuccess', 'repository_elisfiles', $a));
        }
    }
}

$rs->close();

$a = new stdClass;
$a->ucount = $ucount - $errors;
$a->utotal = $ucount;

mtrace("\n".get_string('np_migratecomplete', 'repository_elisfiles', $a)."\n");
