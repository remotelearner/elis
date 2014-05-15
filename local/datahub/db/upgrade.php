<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/datahub/lib.php');

function xmldb_local_datahub_upgrade($oldversion = 0) {
    global $DB, $CFG;

    $result = true;

    $dbman = $DB->get_manager();

    // Always upon any upgrade, ensure ELIS scheduled tasks is in good health
    if ($result && @file_exists($CFG->dirroot.'/local/eliscore/lib/tasklib.php')) {
        require_once($CFG->dirroot.'/local/eliscore/lib/tasklib.php');
        elis_tasks_update_definition('local_datahub');
    }

    // Migrate language strings
    if ($result && $oldversion < 2014030701) {
        $migrator = new \local_eliscore\install\migration\migrator('block_rlip', 'local_datahub');
        $result = $migrator->migrate_language_strings();
        upgrade_plugin_savepoint($result, 2014030701, 'local', 'datahub');
    }

    return $result;
}
