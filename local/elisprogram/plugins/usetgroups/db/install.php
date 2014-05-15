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
 * @package    elisprogram_usetgroups
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
 * Sets up the fields necessary for enabling cluster groups
 *
 * @return  boolean  Returns true to indicate success
 */
function xmldb_elisprogram_usetgroups_install() {

    // Migrate component.
    $oldcmp = 'pmplugins_userset_groups';
    $newcmp = 'elisprogram_usetgroups';
    $upgradestepfuncname = 'elisprogram_usetgroups_pre26upgradesteps';
    $migrator = new \local_eliscore\install\migration\migrator($oldcmp, $newcmp, $upgradestepfuncname);
    if ($migrator->old_component_installed() === true) {
        $migrator->migrate();
    }

    //set up the cluster group category
    $group_category = new field_category();
    $group_category->name = get_string('userset_group_category', 'elisprogram_usetgroups');

    //set up the field that allows users to turn the groupings on
    $group_field = new field();
    $group_field->shortname = 'userset_group';
    $group_field->name = get_string('userset_group', 'elisprogram_usetgroups');
    $group_field->datatype = 'bool';

    //set up the field and category
    $group_field = field::ensure_field_exists_for_context_level($group_field, CONTEXT_ELIS_USERSET, $group_category);

    //set up the field owner
    $owner_options = array('required' => 0,
                           'edit_capability' => '',
                           'view_capability' => '',
                           'control' => 'checkbox',
                           'columns' => 30,
                           'rows' => 10,
                           'maxlength' => 2048,
                           'help_file' => 'elisprogram_usetgroups/userset_group',
                          );
    field_owner::ensure_field_owner_exists($group_field, 'manual', $owner_options);

    $field = new field();
    $field->shortname = 'userset_groupings';
    $field->name = get_string('autoenrol_groupings', 'elisprogram_usetclassify');
    $field->datatype = 'bool';
    $field = field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USERSET, $group_category);

    $owner_options = array('required' => 0,
                           'edit_capability' => '',
                           'view_capability' => '',
                           'control' => 'checkbox',
                           'columns' => 30,
                           'rows' => 10,
                           'maxlength' => 2048,
                           'help_file' => 'elisprogram_usetgroups/autoenrol_groupings');
    field_owner::ensure_field_owner_exists($field, 'manual', $owner_options);

    return true;
}

/**
 * Run all upgrade steps from before elis 2.6.
 *
 * @param int $oldversion The currently installed version of the old component.
 * @return bool Success/Failure.
 */
function elisprogram_usetgroups_pre26upgradesteps($oldversion) {
    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2011072600) {
        // Rename fields.
        $fieldnames = array('group', 'groupings');
        foreach ($fieldnames as $fieldname) {
            $field = field::find(new field_filter('shortname', 'cluster_'.$fieldname));
            if ($field->valid()) {
                $field = $field->current();
                $field->shortname = 'userset_'.$fieldname;
                $field->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011072600, 'pmplugins', 'userset_groups');
    }

    if ($result && $oldversion < 2011101300) {
        // Rename field help.
        $fieldmap = array('userset_group'
                          => 'elisprogram_usetgroups/userset_group',
                          'userset_groupings'
                          => 'elisprogram_usetgroups/autoenrol_groupings');
        foreach ($fieldmap as $key => $val) {
            $field = field::find(new field_filter('shortname', $key));
            if ($field->valid()) {
                $field = $field->current();
                if ($owner = new field_owner((!isset($field->owners) || !isset($field->owners['manual'])) ? false : $field->owners['manual'])) {
                    $owner->fieldid = $field->id;
                    $owner->plugin = 'manual';
                    //$owner->exclude = 0; // TBD
                    $owner->params = serialize(array('required'    => 0,
                                                 'edit_capability' => '',
                                                 'view_capability' => '',
                                                 'control'         => 'checkbox',
                                                 'columns'         => 30,
                                                 'rows'            => 10,
                                                 'maxlength'       => 2048,
                                                 'help_file'       => $val));
                    $owner->save();
                }
            }
        }

        upgrade_plugin_savepoint($result, 2011101300, 'pmplugins', 'userset_groups');
    }

    return $result;
}