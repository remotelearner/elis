<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom post-install method for the "Version 1 ELIS" RLIP export plugin
 */
function xmldb_rlipexport_version1elis_install() {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('block_rlip_export_fieldmap')) {
        //this is a fresh install with no previous 1.9-version IP set up
        //so no need to migrate data
        return true;
    }

    // Skip this work if we should be using Moodle-only IP
    $pm_installed = file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
    $cm_upgraded_from_19 = get_config('block_rlip', 'cm_upgraded_from_19');
    $ip_basic_enabled = !empty($CFG->block_rlip_overrideelisip);
    $init_pm_plugins = $pm_installed && !empty($cm_upgraded_from_19) && !$ip_basic_enabled;

    if (!$init_pm_plugins) {
        return true;
    }

    // Order by id to get the first mapping created
    $export_fieldmap = $DB->get_recordset('block_rlip_export_fieldmap', NULL, 'id');

    $prefix_length = strlen('profile_field_');

    // Need this to look up custom fields
    require_once($CFG->dirroot.'/elis/core/lib/setup.php');
    require_once(elis::lib('data/customfield.class.php'));

    // This will copy the profile fields from RLIP 1.9 into the new table
    foreach ($export_fieldmap as $fieldmap) {
        $fldname = substr($fieldmap->fieldname, $prefix_length); // eg. get "fld" from "profile_field_fld"

        if ($customfield = field::get_for_context_level_with_name('user', $fldname) and
            isset($customfield->id)) {
            /*
             * RLIP 1.9 allows for multiple profile fields with the same name, while RLIP 2 does not
             * Only the first profile field will be copied and the rest will be ignored
             */
            $params = array('fieldid' => $customfield->id);
            if (!$DB->record_exists('rlipexport_version1elis_fld', $params)) {
                // Does not already exists, so create it
                $data = array(
                    'fieldid'    => $customfield->id,
                    'header'     => $fieldmap->fieldmap,
                    'fieldorder' => $fieldmap->fieldorder
                );
                $DB->insert_record('rlipexport_version1elis_fld', $data);
            }

        }
    }

    // Fix the field order because duplicate removal may have created gaps
    $fieldorder = 1;

    if ($newmappings = $DB->get_recordset('rlipexport_version1elis_fld', NULL, 'fieldorder')) {
        foreach ($newmappings as $newmapping) {
            // Set the field order to the appropriate sequential number 
            $newmapping->fieldorder = $fieldorder;
            $DB->update_record('rlipexport_version1elis_fld', $newmapping);

            $fieldorder++;
        }
    }

    return true;
}
