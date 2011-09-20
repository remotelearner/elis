<?php

/* ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
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
            $return->error = 'This folder exists';
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
        $fileslist = required_param('fileslist', PARAM_ALPHANUMEXT); //string
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $return = new stdClass();
        $file_array = explode(",",$fileslist);
// Delete repo file AND delete as a resource too...
//echo get context instance and all other info from each of the records matching our filename, and
//                $file = $fs->get_file($user_context->id, 'user', 'draft', $itemid, $filepath, $filename);// followed by
//               NO, we do NOT delete moodle files...
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
        // Check for permissions? but probably delete checks...
//        if ($repo->elis_files->dir_exists($newdirname,$parentuuid)) {
//            $return->error = 'This folder exists';
//        } else {
//            $repo->elis_files->create_dir($newdirname,$parentuuid);
        $return->uuid = $parentuuid;
        echo json_encode($return);
        die;

    case 'movepopup':
//        echo "\n in move popup";
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        // Probably take a single location...
//        $location    = required_param('location', PARAM_RAW);
        $uid = required_param('uid', PARAM_INT);
//        $location_array = json_decode($location, true);
//echo "\n locations? ";
//print_object($locations_array);
//        echo "\n looking for parent uuid: ".$parentuuid;

        // Get the location parent of our current location
        // Just use the parentuuid as the location...
        $location_parent = $repo->get_location_parent($parentuuid, $uid);
        // Get the tabview appropriate folder listing of the location parent
        // May have to also pass the parentuuid back as that would be the selected location in the treeview listing
        $tab_listing = $repo->get_folder_listing($location_parent['path'], $uid);
//        }
//        echo "\n tab listings: ";
//        print_object($tab_listing[0]);
        // Get the form container with an empty div for the tabs
        $return->form = $repo->print_move_dialog();
        $return->listing = $tab_listing;
        $return->location_path = $location_parent['path'];
        $return->location_name = $location_parent['name'];
        echo json_encode($return);
        die;
    case 'movefiles':
        $targetuuid   = required_param('targetuuid', PARAM_ALPHANUMEXT);
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $fileslist = required_param('fileslist', PARAM_NOTAGS); //string
        $return->uuid = $parentuuid; // or targetuuid???
        echo json_encode($return);
        die;

  /*  case 'generatemovelist':

        $location   = required_param('location', PARAM_ALPHANUMEXT);
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $fileslist = required_param('fileslist', PARAM_ALPHANUMEXT); //array?

        // guess we don't need fileslist I've been passing back and forth... lol...
        // add a param if possible... for folders only? or can we do that in the move_list function?
        $listing = $repo->get_listing($parentuuid, $location);
//        $listing['repo_id'] = $repo_id;
        echo json_encode($listing);
        die;
*/
    case 'uploadpopup':
        $upload_popup = array();
        $upload_popup['form'] = $repo->print_upload_popup();
        echo json_encode($upload_popup);
        die;
    case 'uploadmultiple':
        try {
            $repo->upload_multiple();
            $result = array();
            $result['form'] = $repo->print_upload_progress();
            echo json_encode($result);
        } catch (Exception $e) {
            $err->error = $e->getMessage();
            echo json_encode($err);
            die;
        }
        die;
    case 'uploadprogress':
        $upload_form = array();
        $upload_form['form'] = $repo->print_upload_progress();
        echo json_encode($upload_form);
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
