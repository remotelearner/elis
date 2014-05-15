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
 * @package    elisprogram_usetthemes
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/local/elisprogram/lib/setup.php');
require_once(elis::lib('data/customfield.class.php'));

/**
 * Install function for this plugin
 *
 * @return  boolean  true  Returns true to satisfy install procedure
 */
function xmldb_elisprogram_usetthemes_install() {

    // Migrate component.
    $oldcmp = 'pmplugins_userset_themes';
    $newcmp = 'elisprogram_usetthemes';
    $upgradestepfuncname = 'elisprogram_usetthemes_pre26upgradesteps';
    $migrator = new \local_eliscore\install\migration\migrator($oldcmp, $newcmp, $upgradestepfuncname);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    //set up the cluster theme category
    $theme_category = new field_category();
    $theme_category->name = get_string('userset_theme_category', 'elisprogram_usetthemes');

    //set up the theme priority field
    $theme_priority_field = new field();
    $theme_priority_field->shortname = '_elis_userset_themepriority';
    $theme_priority_field->name = get_string('userset_theme_priority', 'elisprogram_usetthemes');
    $theme_priority_field->datatype = 'int';

    //set up the field and category
    $theme_priority_field = field::ensure_field_exists_for_context_level($theme_priority_field, CONTEXT_ELIS_USERSET, $theme_category);
    $owner_options = array('required' => 0,
                           'edit_capability' => '',
                           'view_capability' => '',
                           'control' => 'text',
                           'columns' => 30,
                           'rows' => 10,
                           'maxlength' => 2048,
                           'help_file' => 'elisprogram_usetthemes/_elis_userset_themepriority');
    field_owner::ensure_field_owner_exists($theme_priority_field, 'manual', $owner_options);

    //set up the field for selecting the applicable theme
    $theme_field = new field();
    $theme_field->shortname = '_elis_userset_theme';
    $theme_field->name = get_string('userset_theme', 'elisprogram_usetthemes');
    $theme_field->datatype = 'char';

    //set up the field and category
    $theme_field = field::ensure_field_exists_for_context_level($theme_field, CONTEXT_ELIS_USERSET, $theme_category);
    $owner_options = array('control' => 'menu',
                           'options_source' => 'themes',
                           'required' => 0,
                           'edit_capability' => '',
                           'view_capability' => '',
                           'columns' => 30,
                           'rows' => 10,
                           'maxlength' => 2048,
                           'help_file' => 'elisprogram_usetthemes/_elis_userset_theme');
    field_owner::ensure_field_owner_exists($theme_field, 'manual', $owner_options);

    return true;
}

/**
 * Run all upgrade steps from before elis 2.6.
 *
 * @param int $oldversion The currently installed version of the old component.
 * @return bool Success/Failure.
 */
function elisprogram_usetthemes_pre26upgradesteps($oldversion) {
    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2011071300) {
        // Rename fields.
        $fieldnames = array('theme', 'themepriority');
        foreach ($fieldnames as $fieldname) {
            $field = field::find(new field_filter('shortname', 'cluster_'.$fieldname));

            if ($field->valid()) {
                $field = $field->current();
                $field->shortname = '_elis_userset_'.$fieldname;
                $field->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011071300, 'pmplugins', 'userset_themes');
    }

    if ($result && $oldversion < 2011101800) {
        // Userset -> 'User Set'.
        $fieldnames = array('theme', 'themepriority');
        foreach ($fieldnames as $fieldname) {
            $fname = '_elis_userset_'. $fieldname;
            $field = field::find(new field_filter('shortname', $fname));

            if ($field->valid()) {
                $field = $field->current();
                // Add help file.
                if ($owner = new field_owner((!isset($field->owners) || !isset($field->owners['manual'])) ? false : $field->owners['manual'])) {
                    $owner->fieldid = $field->id;
                    $owner->plugin = 'manual';
                    //$owner->exclude = 0; // TBD
                    $owner->param_help_file = "elisprogram_usetthemes/{$fname}";
                    $owner->save();
                }

                $category = $field->category;
                if (stripos($category->name, 'Userset') !== false) {
                    $category->name = str_ireplace('Userset', 'User Set', $category->name);
                    $category->save();
                }
            }
        }

        upgrade_plugin_savepoint($result, 2011101800, 'pmplugins', 'userset_themes');
    }

    if ($oldversion < 2013020400) {
        // Rename field if it is still 'Cluster Theme'.
        $field = field::find(new field_filter('shortname', '_elis_userset_theme'));

        if ($field->valid()) {
            $field = $field->current();
            $category = $field->category;
            if ($category->name == 'Cluster Theme') {
                // The field name hasn't been changed from the old default.
                $category->name = get_string('userset_theme_category', 'elisprogram_usetthemes');
                $category->save();
            }
        }

        upgrade_plugin_savepoint($result, 2013020400, 'pmplugins', 'userset_themes');
    }

    return $result;
}