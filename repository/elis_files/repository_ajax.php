<?php
/* ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository
 * @subpackage elis files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/*
 * This file extends the repository_ajax code located in the /repository/
 * directory in Moodle, with the following copyright and license:
 */
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2004  Martin Dougiamas  http://moodle.com               //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__). '/lib/lib.php';

/// These actions all occur on the currently active repository instance
switch ($action) {
    // Pop to allow entry of new folder name
    case 'newfolderpopup':
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $newfolder_popup = array();
        $newfolder_popup['form'] = $repo->print_newdir_popup($parentuuid);
        echo json_encode($newfolder_popup);
        die;

    // Creates new folder
    case 'createnewfolder':
        $newdirname = required_param('newdirname', PARAM_ALPHANUMEXT);
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $return = new stdClass();
        if ($repo->elis_files->dir_exists($newdirname,$parentuuid)) {
            $return->error = get_string('folderalreadyexists', 'repository_elis_files');
        } else {
            $repo->elis_files->create_dir($newdirname,$parentuuid);
        }
        $return->uuid = $parentuuid;
        echo json_encode($return);
        die;

    // Popup to list files about to be deleted
    case 'deletepopup':
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $files   = required_param('files', PARAM_ALPHANUMEXT); //array
        $delete_popup = array();
        $delete_popup['form'] = $repo->print_delete_popup($parentuuid, $files);
        echo json_encode($delete_popup);
        break;

    // Delete file(s)
    case 'deletefiles':
        $fileslist = required_param('fileslist', PARAM_RAW); //string
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $return = new stdClass();
        $file_array = explode(",",$fileslist);

        $alfresco_version = elis_files_get_repository_version();
        if ($alfresco_version == '3.2.1') {
            foreach($file_array as $uuid) {
                elis_files_delete($uuid);
            }
        } else { // Alfresco 3.4
            foreach($file_array as $uuid) {
                $repo->elis_files->delete($uuid);
            }
        }
        $return->uuid = $parentuuid;
        echo json_encode($return);
        die;

    // Popup to allow the selection of a folder to move selected file(s) to
    case 'movepopup':
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $selected_files = required_param('files', PARAM_NOTAGS);
        $uid = required_param('uid', PARAM_INT);
        $return = new stdClass();

        // Get the default locations...
        $locations = array();
        $repo->elis_files->file_browse_options($COURSE->id, '', $locations);

        // Get the location parent of the current parentuuid
        $location_parent = $repo->get_location_parent($parentuuid, $uid);

        // Get the tabview appropriate folder listing of the location parent
        $tab_listing = array();
        foreach ($locations as $key=>$location) {
            $tab_listing[$location['path']] = $repo->get_folder_listing($location['path'], $uid);
        }

        // Get the form container with an empty div for the tabs
        $return->form = $repo->print_move_dialog($parentuuid,$selected_files);
        $return->listing = $tab_listing;
        $return->location_path = $location_parent['path'];
        $return->location_name = $location_parent['name'];
        echo json_encode($return);
        die;

    // Move the selected file(s) to the targetuuid
    case 'movefiles':
        $errormsg = '';
        $targetuuid = required_param('targetuuid', PARAM_ALPHANUMEXT);
        $parentuuid = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $selected_files = required_param('selected_files', PARAM_NOTAGS);
        $return = new stdClass();

        $files_array = explode(",",$selected_files);
        foreach ($files_array as $file) {
            if (!elis_files_move_node($file, $targetuuid)) {
                if ($properties = $repo->get_info($file)) {
                    $errormsg = get_string('errortitlenotmoved', 'repository_elis_files', $properties->title);
                } else {
                    $errormsg = get_string('errorfilenotmoved', 'repository_elis_files');
                }
            }
        }

        if (!empty($errormsg)) {
            $return->error = $errormsg;
        } else {
            $return->uuid = $parentuuid;
        }

        echo json_encode($return);
        die;

    // Popup to allow batch uploading of files
    case 'uploadpopup':
        $upload_popup = array();
        $upload_popup['form'] = $repo->print_upload_popup();
        echo json_encode($upload_popup);
        die;

    case 'overwrite':
        // existing file
        $filepath    = required_param('existingfilepath', PARAM_PATH);
        $filename    = required_param('existingfilename', PARAM_FILE);
        // user added file which needs to replace the existing file
        $newfilepath = required_param('newfilepath', PARAM_PATH);
        $newfilename = required_param('newfilename', PARAM_FILE);

        echo json_encode(repository::overwrite_existing_draftfile($itemid, $filepath, $filename, $newfilepath, $newfilename));
        die;

    case 'deletetmpfile':
        // delete tmp file
        $newfilepath = required_param('newfilepath', PARAM_PATH);
        $newfilename = required_param('newfilename', PARAM_FILE);
        echo json_encode(repository::delete_tempfile_from_draft($itemid, $newfilepath, $newfilename));
        die;
}
