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
 * Custom post-install method for the "Version 1 ELIS" RLIP import plugin
 */
function xmldb_rlipimport_version1elis_install() {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('block_rlip_fieldmap')) {
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

    //need the plugin to get the field list
    require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/version1elis.class.php');
    $plugin = new rlip_importplugin_version1elis();

    $fields = array();
    $fields['user'] = $plugin->get_available_fields('user');
    $fields['enrolment'] = $plugin->get_available_fields('enrolment');

    // Copy over the field mappings that are common in both RLIP 2 and RLIP 1.9
    foreach ($fields as $entitytype => $field) {
        foreach ($field as $fieldname) {
            $legacy_entitytype = $entitytype == 'enrolment' ? 'student' : $entitytype;
            $fieldmaps = $DB->get_recordset('block_rlip_fieldmap', array('context' => $legacy_entitytype));

            foreach ($fieldmaps as $fieldmap) {
                // Handle special case when field names are synonymous but have different names
                if ($fieldname == $fieldmap->fieldname || $fieldname == 'action' && $fieldmap->fieldname == 'execute') {
                    // Determine if a mapping record already exists
                    $params = array(
                        'entitytype' => $entitytype,
                        'standardfieldname' => $fieldname
                    );
                    if ($record = $DB->get_record('rlipimport_version1elis_map', $params)) {
                        // A mapping exists, so update it
                        $data = array(
                            'id'                => $record->id,
                            'entitytype'        => $entitytype,
                            'standardfieldname' => $fieldname,
                            'customfieldname'   => $fieldmap->fieldmap
                        );
                        $DB->update_record('rlipimport_version1elis_map', $data);
                    } else {
                        // No mapping exists, so create it
                        $data = array(
                            'entitytype'        => $entitytype,
                            'standardfieldname' => $fieldname,
                            'customfieldname'   => $fieldmap->fieldmap
                        );
                        $DB->insert_record('rlipimport_version1elis_map', $data);
                    }
                }
            }
        }
    }

    // Handle courses separately
    $course_fields = $plugin->get_available_fields('course');
    // Entity type priority
    $entities = array(
        'course',
        'class',
        'track',
        'curriculum'
    );

    foreach ($course_fields as $course_fieldname) {
        // Look for the first entity, in the order above, that has the mapping
        foreach ($entities as $entity) {
            $params = array(
                'context'   => $entity,
                'fieldname' => $course_fieldname
            );
            if ($old_mapping = $DB->get_record('block_rlip_fieldmap', $params)) {
                // Found a legacy mapping
                $params = array(
                    'entitytype' => 'course',
                    'standardfieldname' => $course_fieldname
                );
                if ($record = $DB->get_record('rlipimport_version1elis_map', $params)) {
                    // A mapping exists, so update it
                    $data = array(
                        'id'                => $record->id,
                        'entitytype'        => 'course',
                        'standardfieldname' => $course_fieldname,
                        'customfieldname'   => $old_mapping->fieldmap
                    );
                    $DB->update_record('rlipimport_version1elis_map', $data);
                } else {
                    // No mapping exists, so create it
                    $data = array(
                        'entitytype'        => 'course',
                        'standardfieldname' => $course_fieldname,
                        'customfieldname'   => $old_mapping->fieldmap
                    );
                    $DB->insert_record('rlipimport_version1elis_map', $data);
                }

                break;
            }
        }
    }

    return true;
}
