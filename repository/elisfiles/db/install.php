<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

function xmldb_repository_elisfiles_install() {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    // Run upgrade steps from old plugin if applicable
    $oldversion = get_config('repository_elis_files', 'version');
    if ($oldversion !== false) {
        if ($oldversion < 2011110301) {
            $errors = false;
            $auths = elis_files_nopasswd_auths();
            $authlist = "'". implode("', '", $auths) ."'";
            $users = $DB->get_records_select('user', "auth IN ({$authlist})", array(), 'id, auth');
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user = get_complete_user_data('id', $user->id);
                    $migrate_ok = elis_files_user_created($user);
                    if (!$migrate_ok) {
                        $errors = true;
                        // error_log("xmldb_block_elis_files_upgrade({$oldversion}) - failed migrating user ({$user->id}) to Alfresco.");
                    }
                }
            }
            if (!$errors) {
                set_config('initialized', 1, ELIS_files::$plugin_name);
            }
        }

        if ($result && $oldversion < 2012042300) {
            //check that elis_files_organization_store exists and repository_elisfiles_userset does not exist
            if ($dbman->table_exists('elis_files_organization_store') && !$dbman->table_exists('elis_files_userset_store')) {

                $original_table = new xmldb_table('elis_files_organization_store');

                //rename table
                $dbman->rename_table($original_table, 'elis_files_userset_store');
                $new_table = new xmldb_table('elis_files_userset_store');

                //drop the keys
                $original_uuid_index = new xmldb_index('elisfileorgastor_orguu_uix', XMLDB_INDEX_UNIQUE, array('organizationid', 'uuid'));
                $original_index = new xmldb_index('elisfileorgastor_org_ix', XMLDB_INDEX_NOTUNIQUE, array('organizationid'));
                $dbman->drop_index($new_table, $original_uuid_index);
                $dbman->drop_index($new_table, $original_index);

                //rename field
                $organization = new xmldb_field('organizationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);
                $dbman->rename_field($new_table, $organization, 'usersetid');

                //add the keys
                $new_uuid_index = new xmldb_index('elisfileuserstor_useuu_uix', XMLDB_INDEX_UNIQUE, array('usersetid', 'uuid'));
                $new_index = new xmldb_index('elisfileuserstor_use_ix', XMLDB_INDEX_NOTUNIQUE, array('usersetid'));
                $dbman->add_index($new_table, $new_uuid_index);
                $dbman->add_index($new_table, $new_index);
            }
        }

        if ($result && $oldversion < 2012042500) {
            defined('ELIS_FILES_BROWSE_USERSET_FILES') or define('ELIS_FILES_BROWSE_USERSET_FILES',   60);

            // ELIS-4676 ELIS UserSet Files is no longer valid, so change to default of ELIS User Files
            $select = "plugin = 'elis_files' AND name = 'default_browse'";

            if ($record = $DB->get_record_select('config_plugins', $select)) {
                require_once($CFG->dirroot.'/repository/elisfiles/lib/ELIS_files.php');
                $int_value = (int)$record->value;
                $valid_values = array(
                        ELIS_FILES_BROWSE_SITE_FILES,
                        ELIS_FILES_BROWSE_SHARED_FILES,
                        ELIS_FILES_BROWSE_COURSE_FILES,
                        ELIS_FILES_BROWSE_USER_FILES
                );
                if (!in_array($int_value, $valid_values)) {
                    $record->value = ELIS_FILES_BROWSE_USER_FILES;
                    $DB->update_record('config_plugins', $record);
                }
            }
        }

        if ($result && $oldversion < 2012050200)  {
            // Check for any plug-in settings using an incorrect plug-in name "ELIS_files" instead of "elis_files"
            if ($pcfgs = $DB->get_records('config_plugins', array('plugin' => 'ELIS_files'))) {
                foreach ($pcfgs as $pcfg) {
                    $pcfg->plugin = 'elis_files';
                    $DB->update_record('config_plugins', $pcfg);
                }
            }
        }

        /*
         * This upgrade step removes any possible stale data in the repository_elisfiles_userset and
         * repository_elisfiles_course that not longer have relevant courses or user sets
         */
        if ($result && $oldversion < 2012083000)  {
            $haveuserset = false;
            if (file_exists($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php')) {
                require_once($CFG->dirroot.'/local/elisprogram/lib/data/userset.class.php');
                $haveuserset = $dbman->table_exists(userset::TABLE);
            }

            $sql = 'DELETE FROM {elis_files_userset_store}';
            if ($haveuserset) {
                $sql .= ' WHERE NOT EXISTS (SELECT *
                                              FROM {'.userset::TABLE.'}
                                             WHERE {elis_files_userset_store}.usersetid = {'.userset::TABLE.'}.id)';

            }
            $DB->execute($sql);

            $sql = 'DELETE FROM {elis_files_course_store}
                    WHERE NOT EXISTS (SELECT *
                                        FROM {course}
                                       WHERE {elis_files_course_store}.courseid = {course}.id)';

            $DB->execute($sql);
        }

        /*
         * This upgrade step removes backslashes from category titles in the repository_elisfiles_cats
         */
        if ($result && $oldversion < 2012090400)  {
            require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
            require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
            // Initialize the repo object.
            $repo = repository_factory::factory();
            $table = 'elis_files_categories';
            if ($repo && $dbman->table_exists($table) && $categories = elis_files_get_categories()) {
                $DB->delete_records($table);

                // Perform the back-end category refresh
                $categories = elis_files_get_categories();
                $uuids = array();
                $repo->process_categories($uuids, $categories);
            }
        }

        // If the pre-ELIS 2 Alfresco plugin was present and enabled on this site, then we need to automatically
        // enable the ELIS Files plugin
        $select1 = "name = 'repository' AND ".$DB->sql_compare_text('value')." = 'alfresco'";
        $select2 = "name = 'repository_plugins_enabled' AND ".$DB->sql_compare_text('value')." = 'alfresco'";

        if ($DB->record_exists_select('config', $select1) && $DB->record_exists_select('config', $select2)) {
            require_once($CFG->dirroot.'/repository/lib.php');

            $elis_files = new repository_type('elisfiles', array(), true);

            if (!empty($elis_files)) {
                $elis_files->update_visibility(true);
                $elis_files->create();
            }

            $DB->delete_records_select('config', "(name = 'repository' OR name = 'repository_plugins_enabled')");
        }

        // ELIS-3677, ELIS-3802 Moodle files is no longer valid, so change to default of ELIS User Files
        $select = "plugin = 'elis_files' AND name = 'default_browse'";

        if ($record = $DB->get_record_select('config_plugins', $select)) {
            require_once($CFG->dirroot.'/repository/elisfiles/lib/ELIS_files.php');
            $int_value = (int)$record->value;
            $valid_values = array(
                    ELIS_FILES_BROWSE_SITE_FILES,
                    ELIS_FILES_BROWSE_SHARED_FILES,
                    ELIS_FILES_BROWSE_COURSE_FILES,
                    ELIS_FILES_BROWSE_USER_FILES
            );
            if (!in_array($int_value, $valid_values)) {
                $record->value = ELIS_FILES_BROWSE_USER_FILES;
                $DB->update_record('config_plugins', $record);
            }
        }
    }

    // Convert old tables to new
    static $tablemap = array(
        'elis_files_categories'    => 'repository_elisfiles_cats',
        'elis_files_course_store'  => 'repository_elisfiles_course',
        'elis_files_userset_store' => 'repository_elisfiles_userset'
    );
    foreach ($tablemap as $oldtable => $newtable) {
        $oldtableobj = new xmldb_table($oldtable);
        if ($dbman->table_exists($oldtableobj)) {
            $newtableobj = new xmldb_table($newtable);
            $dbman->drop_table($newtableobj);
            $dbman->rename_table($oldtableobj, $newtable);
        }
    }

    // Migrate capabilities
    $oldcapprefix = 'repository/elis_files';
    $newcapprefix = 'repository/elisfiles';
    $sql = 'SELECT * FROM {role_capabilities} WHERE capability LIKE ?';
    $params = array($oldcapprefix.'%');
    $rolecaps = $DB->get_recordset_sql($sql, $params);
    foreach ($rolecaps as $rolecaprec) {
        $updaterec = new stdClass;
        $updaterec->id = $rolecaprec->id;
        $updaterec->capability = str_replace($oldcapprefix, $newcapprefix, $rolecaprec->capability);
        $DB->update_record('role_capabilities', $updaterec);
    }
    $sql = 'SELECT * FROM {capabilities} WHERE name LIKE ?';
    $caps = $DB->get_recordset_sql($sql, $params);
    foreach ($caps as $cap) {
        $cap->name = str_replace($oldcapprefix, $newcapprefix, $cap->name);
        $cap->component = str_replace('repository_elis_files', 'repository_elisfiles', $cap->component);
        $DB->update_record('capabilities', $cap);
    }

    // Copy any settings from old plugin
    $oldconfig = get_config('elis_files');
    foreach ($oldconfig as $name => $value) {
        set_config($name, $value, 'elisfiles');
    }
    unset_all_config_for_plugin('elis_files');
    unset_all_config_for_plugin('repository_elis_files');

    // Update repository table
    $sql = 'UPDATE {repository} SET type = "elisfiles" WHERE type = "elis_files"';
    $DB->execute($sql);

    return $result;
}
