<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2014 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

function xmldb_repository_elisfiles_upgrade($oldversion = 0) {
    global $DB, $CFG;

    $result = true;

    if ($result && $oldversion < 2014030701) {
        require_once($CFG->wwwroot.'/repository/elisfiles/lib/lib.php');
        $ignoreresult = elis_files_update_references_in_database();
        upgrade_plugin_savepoint(true, 2014030701, 'repository', 'elisfiles');
    }

    // Migrate language strings
    if ($result && $oldversion < 2014030702) {
        $migrator = new \local_eliscore\install\migration\migrator('repository_elis_files', 'repository_elisfiles');
        $result = $migrator->migrate_language_strings();
        upgrade_plugin_savepoint($result, 2014030702, 'repository', 'elisfiles');
    }

    return $result;
}
