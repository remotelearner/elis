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

function xmldb_elisprogram_usetgroups_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2011072600) {
        // rename fields
        $fieldnames = array('group', 'groupings');
        foreach ($fieldnames as $fieldname) {
            $field = field::find(new field_filter('shortname', 'cluster_'.$fieldname));
            if ($field->valid()) {
                $field = $field->current();
                $field->shortname = 'userset_'. $fieldname;
                $field->save();
            }
        }

        upgrade_plugin_savepoint($result, 2011072600, 'elisprogram', 'usetgroups');
    }

    if ($result && $oldversion < 2011101300) {
        // rename field help
        $fieldmap = array('userset_group'
                          => 'elisprogram_usetgroups/userset_group',
                          'userset_groupings'
                          => 'elisprogram_usetgroups/autoenrol_groupings');
        foreach ($fieldmap as $key => $val) {
            $field = field::find(new field_filter('shortname', $key));
            if ($field->valid()) {
                $field = $field->current();
                if ($owner = new field_owner((!isset($field->owners) || !isset($field->owners['manual'])) ? false : $field->owners['manual'])) {
                    //error_log("elisprogram_usetgroups::upgrading help_file for '{$key}' to '{$val}'");
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

        upgrade_plugin_savepoint($result, 2011101300, 'elisprogram', 'usetgroups');
    }

    return $result;
}
