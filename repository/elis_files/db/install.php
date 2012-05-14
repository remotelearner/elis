<?php
/**
 *
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository
 * @subpackage elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function xmldb_repository_elis_files_install() {
    global $CFG, $DB;

    $result = true;

    // If the pre-ELIS 2 Alfresco plugin was present and enabled on this site, then we need to automatically
    // enable the ELIS Files plugin
    $select1 = "name = 'repository' AND ".$DB->sql_compare_text('value')." = 'alfresco'";
    $select2 = "name = 'repository_plugins_enabled' AND ".$DB->sql_compare_text('value')." = 'alfresco'";

    if ($DB->record_exists_select('config', $select1) && $DB->record_exists_select('config', $select2)) {
        require_once($CFG->dirroot.'/repository/lib.php');

        $elis_files = new repository_type('elis_files', array(), true);

        if (!empty($elis_files)) {
            $elis_files->update_visibility(true);
            $elis_files->create();
        }

        $DB->delete_records_select('config', "(name = 'repository' OR name = 'repository_plugins_enabled')");
    }

    // ELIS-3677, ELIS-3802 Moodle files is no longer valid, so change to default of ELIS User Files
    $select = "plugin = 'elis_files' AND name = 'default_browse'";

    if ($record = $DB->get_record_select('config_plugins', $select)) {
        require_once($CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php');
        $int_value = (int)$record->value;
        $valid_values = array(ELIS_FILES_BROWSE_SITE_FILES,
                              ELIS_FILES_BROWSE_SHARED_FILES,
                              ELIS_FILES_BROWSE_COURSE_FILES,
                              ELIS_FILES_BROWSE_USER_FILES);
        if (!in_array($int_value, $valid_values)) {
            $record->value = ELIS_FILES_BROWSE_USER_FILES;
            $DB->update_record('config_plugins', $record);
        }
    }

    return $result;
}
