<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage elis_files (Alfresco)
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot . '/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot . '/repository/elis_files/lib/ELIS_files.php');

function xmldb_repository_elis_files_upgrade($oldversion = 0) {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();

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
                    error_log("xmldb_block_elis_files_upgrade({$oldversion}) - failed migrating user ({$user->id}) to Alfresco.");
                }
            }
        }
        if (!$errors) {
            set_config('initialized', 1, ELIS_files::$plugin_name);
        }

        // elis_files savepoint reached
        upgrade_plugin_savepoint(true, 2011110301, 'repository', 'elis_files');
    }

    if ($result && $oldversion < 2012042300) {
        //check that elis_files_organization_store exists and elis_files_userset_store does not exist
        if ($dbman->table_exists('elis_files_organization_store') && !$dbman->table_exists('elis_files_userset_store')) {

            $original_table = new xmldb_table('elis_files_organization_store');

            //rename table
            $dbman->rename_table($original_table,'elis_files_userset_store');

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

        // elis_files savepoint reached
        upgrade_plugin_savepoint(true, 2012042300, 'repository', 'elis_files');
    }

    if ($result && $oldversion < 2012042500) {
        defined('ELIS_FILES_BROWSE_USERSET_FILES') or define('ELIS_FILES_BROWSE_USERSET_FILES',   60);

        // ELIS-4676 ELIS UserSet Files is no longer valid, so change to default of ELIS User Files
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
        // elis_files savepoint reached
        upgrade_plugin_savepoint(true, 2012042500, 'repository', 'elis_files');
    }

    if ($result && $oldversion < 2012050200)  {
        // Check for any plug-in settings using an incorrect plug-in name "ELIS_files" instead of "elis_files"
        if ($pcfgs = $DB->get_records('config_plugins', array('plugin' => 'ELIS_files'))) {
            foreach ($pcfgs as $pcfg) {
                $pcfg->plugin = 'elis_files';
                $DB->update_record('config_plugins', $pcfg);
            }
        }

        upgrade_plugin_savepoint(true, 2012050200, 'repository', 'elis_files');
    }

    /*
     * This upgrade step removes any possible stale data in the elis_files_userset_store and
     * elis_files_course_store that not longer have relevant courses or user sets
     */
    if ($result && $oldversion < 2012083000)  {
        $haveuserset = false;
        if (file_exists($CFG->dirroot .'/elis/program/lib/data/userset.class.php')) {
            require_once($CFG->dirroot .'/elis/program/lib/data/userset.class.php');
            $haveuserset = $dbman->table_exists(userset::TABLE);
        }

        $sql = 'DELETE FROM {elis_files_userset_store}';
        if ($haveuserset) {
            $sql .= ' WHERE NOT EXISTS (SELECT *
                                        FROM {'. userset::TABLE .'}
                                        WHERE {elis_files_userset_store}.usersetid = {'. userset::TABLE .'}.id)';

        }
        $DB->execute($sql);

        $sql = 'DELETE FROM {elis_files_course_store}
                WHERE NOT EXISTS (SELECT *
                                  FROM {course}
                                  WHERE {elis_files_course_store}.courseid = {course}.id)';

        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2012083000, 'repository', 'elis_files');
    }

    /*
     * This upgrade step removes backslashes from category titles in the elis_files_categories
     */
    if ($result && $oldversion < 2012090400)  {

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

        upgrade_plugin_savepoint(true, 2012090400, 'repository', 'elis_files');
    }
    return $result;
}
