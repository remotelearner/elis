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
 * @package    usetenrol_moodleprofile
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_usetenrol_moodleprofile_install() {
    // Migrate component.
    $oldcmp = 'usersetenrol_moodle_profile';
    $newcmp = 'usetenrol_moodleprofile';
    $upgradestepfuncname = 'usetenrol_moodleprofile_pre26upgradesteps';
    $migrator = new \local_elisprogram\install\migration\migrator($oldcmp, $newcmp, $upgradestepfuncname);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }
}

/**
 * Run all upgrade steps from before elis 2.6.
 *
 * @param int $oldversion The currently installed version of the old component.
 * @return bool Success/Failure.
 */
function usetenrol_moodleprofile_pre26upgradesteps($oldversion) {
    global $CFG, $THEME, $DB, $OUTPUT;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2011120800) {
        // Fix plugin name.
        $sql = "UPDATE {".clusterassignment::TABLE."} SET plugin = 'moodleprofile' WHERE plugin = 'profile'";

        $DB->execute($sql);

        // Userset enrol savepoint reached.
        upgrade_plugin_savepoint(true, 2011120800, 'usersetenrol', 'moodle_profile');
    }

    return $result;
}