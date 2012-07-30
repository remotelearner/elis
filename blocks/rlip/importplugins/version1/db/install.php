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

require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

function xmldb_rlipimport_version1_install() {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('block_rlip_fieldmap')) {
        //this is a fresh install with no previous 1.9-version IP set up
        //so no need to migrate data
        return true;
    }

    // Skip this work if we should be using ELIS IP
    $pm_installed = file_exists($CFG->dirroot.'/elis/program/lib/setup.php');
    $cm_upgraded_from_19 = get_config('block_rlip', 'cm_upgraded_from_19');
    $ip_basic_enabled = !empty($CFG->block_rlip_overrideelisip);
    $init_moodle_plugins = !$pm_installed || empty($cm_upgraded_from_19) || $ip_basic_enabled;

    if (!$init_moodle_plugins) {
        return true;
    }

    $plugin = new rlip_importplugin_version1(NULL, false);

    $fields = array();
    $fields['user'] = $plugin->get_available_fields('user');
    $fields['course'] = $plugin->get_available_fields('course');
    $fields['student'] = $plugin->get_available_fields('enrolment');

    // Copy over the field mappings that are common in both RLIP 2 and RLIP 1.9
    foreach ($fields as $entitytype => $field) {
        foreach ($field as $fieldname) {
            $fieldmaps = $DB->get_records('block_rlip_fieldmap', array('context' => $entitytype));

            foreach ($fieldmaps as $fieldmap) {
                // Handle special case when field names are synonymous but have different names
                if ($fieldname == $fieldmap->fieldname || ($fieldname == "lang" && $fieldmap->fieldname == "language") ||
                   ($fieldname == "action" && $fieldmap->fieldname == "execute")) {

                    $entity = $entitytype;
                    // Handle special case for different terminology
                    if ($entitytype == "student") {
                        $entity = "enrolment";
                    }
                    if ($record = $DB->get_record('rlipimport_version1_mapping', array('entitytype' => $entity,
                                                  'standardfieldname' => $fieldname))) {
                        $DB->update_record('rlipimport_version1_mapping', array('id' => $record->id, 'entitytype' => $entity,
                                           'standardfieldname' => $fieldname, 'customfieldname' => $fieldmap->fieldmap));
                    } else {
                        $DB->insert_record('rlipimport_version1_mapping', array('entitytype' => $entity,
                                           'standardfieldname' => $fieldname, 'customfieldname' => $fieldmap->fieldmap));
                    }
                }
            }
        }
    }

    return true;
}
