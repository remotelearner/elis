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
 * @package    elisprogram_usetclassify
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once elispm::file('plugins/usetclassify/lib.php');

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_elisprogram_usetclassify_install() {
    global $CFG, $DB;

    require_once elispm::lib('setup.php');
    require_once elis::lib('data/customfield.class.php');
    require_once elispm::file('plugins/usetclassify/usersetclassification.class.php');

    // Migrate component.
    $oldcmp = 'pmplugins_userset_classification';
    $newcmp = 'elisprogram_usetclassify';
    $upgradestepfuncname = 'elisprogram_usetclassify_pre26upgradesteps';
    $tablechanges = array(
        'crlm_cluster_classification' => 'elisprogram_usetclassify'
    );
    $migrator = new \local_eliscore\install\migration\migrator($oldcmp, $newcmp, $upgradestepfuncname, $tablechanges);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    $field = new field();
    $field->shortname = USERSET_CLASSIFICATION_FIELD;
    $field->name = get_string('classification_field_name', 'elisprogram_usetclassify');
    $field->datatype = 'char';

    $category = new field_category();
    $category->name = get_string('classification_category_name', 'elisprogram_usetclassify');

    $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USERSET, $category);

    // make sure we're set as owner
    if (!isset($field->owners['userset_classifications'])) {
        $owner = new field_owner();
        $owner->fieldid = $field->id;
        $owner->plugin = 'userset_classifications';
        $owner->save();
    }

    // make sure 'manual' is an owner
    if (!isset($field->owners['manual'])) {
        $owner = new field_owner();
        $owner->fieldid = $field->id;
        $owner->plugin = 'manual';
        $owner->param_view_capability = '';
        $owner->param_edit_capability = 'moodle/user:update';
        $owner->param_control = 'menu';
        $owner->param_options_source = 'userset_classifications';
        $owner->param_help_file = 'elisprogram_usetclassify/cluster_classification';
        $owner->save();
    }

    // make sure we have a default value set
    if (!field_data::get_for_context_and_field(NULL, $field)) {
        field_data::set_for_context_and_field(NULL, $field, 'regular');
    }

    $regclassify = $DB->get_record(usersetclassification::TABLE, array('shortname' => 'regular'));
    if (empty($regclassify)) {
        $default = new usersetclassification();
        $default->shortname = 'regular';
        $default->name = get_string('cluster', 'local_elisprogram');
        $default->param_autoenrol_curricula = 1;
        $default->param_autoenrol_tracks = 1;
        $default->save();
    }

    return true;
}

/**
 * Run all upgrade steps from before elis 2.6.
 *
 * @param int $oldversion The currently installed version of the old component.
 * @return bool Success/Failure.
 */
function elisprogram_usetclassify_pre26upgradesteps($oldversion) {
    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2011071400) {
        // Rename field.
        $field = field::find(new field_filter('shortname', '_elis_cluster_classification'));

        if ($field->valid()) {
            $field = $field->current();

            $field->shortname = USERSET_CLASSIFICATION_FIELD;
            if ($field->name == 'Cluster classification') {
                // Rhe field name hasn't been changed from the old default.
                $field->name = get_string('classification_field_name', 'elisprogram_usetclassify');
            }
            $field->save();

            $category = $field->category;
            if ($category->name == 'Cluster classification') {
                // The field name hasn't been changed from the old default.
                $category->name = get_string('classification_category_name', 'elisprogram_usetclassify');
                $category->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011071400, 'pmplugins', 'userset_classification');
    }

    if ($result && $oldversion < 2011101200) {

        $field = field::find(new field_filter('shortname', USERSET_CLASSIFICATION_FIELD));

        if ($field->valid()) {
            $field = $field->current();
            if ($owner = new field_owner((!isset($field->owners) || !isset($field->owners['manual'])) ? false : $field->owners['manual'])) {
                $owner->fieldid = $field->id;
                $owner->plugin = 'manual';
                //$owner->exclude = 0; // TBD
                $owner->param_help_file = 'elisprogram_usetclassify/cluster_classification';
                $owner->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011101200, 'pmplugins', 'userset_classification');
    }

    if ($result && $oldversion < 2011101800) {
        // Userset -> 'User Set'.
        $field = field::find(new field_filter('shortname', USERSET_CLASSIFICATION_FIELD));

        if ($field->valid()) {
            $field = $field->current();
            if (stripos($field->name, 'Userset') !== false) {
                $field->name = str_ireplace('Userset', 'User Set', $field->name);
                $field->save();
            }

            $category = $field->category;
            if (stripos($category->name, 'Userset') !== false) {
                $category->name = str_ireplace('Userset', 'User Set', $category->name);
                $category->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011101800, 'pmplugins', 'userset_classification');
    }

    if ($result && $oldversion < 2011110300) {
        // Make sure to rename the default classification name from "Cluster" to "User set".
        require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));

        // Make sure there are no custom fields with invalid categories.
        pm_fix_orphaned_fields();

        $field = field::find(new field_filter('shortname', USERSET_CLASSIFICATION_FIELD));

        if ($field->valid()) {
            $field = $field->current();
            $category = $field->category;

            $default = usersetclassification::find(new field_filter('shortname', 'regular'));

            if ($default->valid()) {
                $default = $default->current();
                $default->name = get_string('cluster', 'local_elisprogram');
                $default->save();
            }

            // Upgrade field owner data for the default User Set field.
            $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USERSET, $category);

            $owners = field_owner::find(new field_filter('fieldid', $field->id));

            if ($owners->valid()) {
                foreach ($owners as $owner) {
                    if ($owner->plugin == 'cluster_classification') {
                        $owner->plugin = 'userset_classification';

                        $owner->save();
                    } else if ($owner->plugin == 'manual') {
                        $owner->param_options_source = 'userset_classifications';
                        $owner->param_help_file = 'elisprogram_usetclassify/cluster_classification';
                        $owner->save();
                    }
                }
            }

            upgrade_plugin_savepoint($result, 2011110300, 'pmplugins', 'userset_classification');
        }
    }

    return $result;
}
