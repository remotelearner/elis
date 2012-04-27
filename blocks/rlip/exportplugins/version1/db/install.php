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
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1/version1.class.php');

function xmldb_rlipexport_version1_install() {
    global $DB;

    $export_fieldmap = $DB->get_records('block_rlip_export_fieldmap');

    // This will copy the profile fields from RLIP 1.9 into the new table
    foreach ($export_fieldmap as $fieldmap) {

        $fldname = substr($fieldmap->fieldname, 14); // eg. get "fld" from "profile_field_fld"
        if ($infofield = $DB->get_record('user_info_field', array('shortname' => $fldname))) {

            /* RLIP 1.9 allows for multiple profile fields with the same name, while RLIP 2 does not
             * Only the first profile field will be copied and the rest will be ignored
             */
            if (!$DB->record_exists('rlipexport_version1_field', array('fieldid' => $infofield->id))) {
                $DB->insert_record('rlipexport_version1_field', array('fieldid' => $infofield->id,
                                   'header' => $fieldmap->fieldmap, 'fieldorder' => $fieldmap->fieldorder));
            }

        }
    }

    return true;
}
