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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

//database table constants
define('RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE', 'rlipimport_version1elis_mapping');

/**
 * Performs page setup work needed on the page for configuring field mapping
 * for the import
 *
 * @param string $baseurl The page's base url
 */
function rlipimport_version1elis_page_setup($baseurl) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $displaystring = get_string('configuretitle', 'rlipimport_version1elis');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');
}

/**
 * Performs tab setup work needed on the page for configuring field mapping
 * for the import
 *
 * @param string $baseurl The page's base url
 * @return array An array of appropriate tab objects
 */
function rlipimport_version1elis_get_tabs($baseurl) {
    $entitytypes = array('user', 'course', 'enrolment');

    $tabs = array();

    foreach ($entitytypes as $entitytype) {
        $url = new moodle_url($baseurl, array('tab' => $entitytype));
        $displaystring = get_string("{$entitytype}tab", 'rlipimport_version1elis');

        $tabs[] = new tabobject($entitytype, $url, $displaystring);
    }
    return $tabs;
}

/**
 * Retrieves a complete mapping from standard import field names to custom
 * field names
 *
 * @param string $entitytype The entity type to retrieve the mapping for
 * @return array The appropriate mapping
 */
function rlipimport_version1elis_get_mapping($entitytype) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
    $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
    require_once($file);

    //obtain the list of supported fields
    $plugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
    $fields = $plugin->get_available_fields($entitytype);

    if ($fields == false) {
        //invalid entitytype was supplied
        return false;
    }

    //by default, map each field to itself
    $result = array();
    foreach ($fields as $field) {
        $result[$field] = $field;
    }

    //apply mapping info from the database
    $params = array('entitytype' => $entitytype);
    if ($mappings = $DB->get_recordset(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $params)) {
        foreach ($mappings as $mapping) {
            $result[$mapping->standardfieldname] = $mapping->customfieldname;
        }
    }

    return $result;
}