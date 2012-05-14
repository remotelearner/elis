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
//        echo "\n newfolderpopup and parentuuid: ".$parentuuid;
        $newfolder_popup = array();
        $newfolder_popup['form'] = $repo->print_newdir_popup($parentuuid);
        echo json_encode($newfolder_popup);
        die;

    // Creates new folder
    case 'createnewfolder':
        $newdirname = required_param('newdirname', PARAM_ALPHANUMEXT);
        $encodedparentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT);

        // Decode parentuuid to get uuid for dir_exists and create_dir
        $params = unserialize(base64_decode($encodedparentuuid));
        $parentuuid = $params['path'];
        if ($repo->elis_files->dir_exists($newdirname,$parentuuid)) {
            $return->error = get_string('folderalreadyexists', 'repository_elis_files');
        } else {
            $repo->elis_files->create_dir($newdirname,$parentuuid);
        }
        $return->uuid = $encodedparentuuid;
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
        $parentuuid   = required_param('parentuuid', PARAM_ALPHANUMEXT); //encoded

        // Check for empty parentuuid - coming from search results which have no parents
        if (empty($parentuuid) || $parentuuid == 'undefined') {
            // setting userid...
            if ($repo->context->contextlevel === CONTEXT_USER) {
                $userid = $USER->id;
            } else {
                $userid = 0;
            }

            if ($ruuid = $repo->elis_files->get_repository_location($COURSE->id, $userid, $shared, $oid)) {
                $parentuuid = $ruuid;
            } else if ($duuid = $repo->elis_files->get_default_browsing_location($COURSE->id, $userid, $shared, $oid)) {
                $parentuuid = $duuid;
            }
            $uuuid = $repo->elis_files->get_user_store($USER->id);
            if ($parentuuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
            // Set other variables
            $oid = 0;
            $shared = (boolean)0;
            if (empty($uid)) {
                $cid = $COURSE->id;
            } else {
                $cid = 0;
            }
        }

        $return = new stdClass();
        $file_array = explode(",",$fileslist);
        $repo->elis_files->get_defaults();

        if ($repo->elis_files->is_version('3.2')) {
            foreach($file_array as $uuid) {
                elis_files_delete($uuid);
            }
        } else if ($repo->elis_files->is_version('3.4')) {
            foreach($file_array as $uuid) {
                $repo->elis_files->delete($uuid);
            }
        }
        $return->uuid = $parentuuid;
        echo json_encode($return);
        die;

    // Popup to allow the selection of a folder to move selected file(s) to
    case 'movepopup':
        $parentuuid     = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $oid            = required_param('oid', PARAM_INT);
        $shared         = required_param('shared', PARAM_BOOL);
        $uid            = required_param('uid', PARAM_INT);
        $cid            = required_param('cid', PARAM_INT);
        $selected_files = required_param('files', PARAM_NOTAGS);
        $shared         = (boolean)$shared;
        $return = new stdClass();

        // Check for empty parentuuid - coming from search results which have no parents
        if (empty($parentuuid) || $parentuuid == 'undefined') {
            // setting userid...
            if ($repo->context->contextlevel === CONTEXT_USER) {
                $userid = $USER->id;
            } else {
                $userid = 0;
            }

            if ($ruuid = $repo->elis_files->get_repository_location($COURSE->id, $userid, $shared, $oid)) {
                $parentuuid = $ruuid;
            } else if ($duuid = $repo->elis_files->get_default_browsing_location($COURSE->id, $userid, $shared, $oid)) {
                $parentuuid = $duuid;
            }
            $uuuid = $repo->elis_files->get_user_store($USER->id);
            if ($parentuuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
            // Set other variables
            $oid = 0;
            $shared = (boolean)0;
            if (empty($uid)) {
                $cid = $COURSE->id;
            } else {
                $cid = 0;
            }
        }

        // Get the default locations...
        $locations = array();
        $createonly = true;
        $repo->elis_files->file_browse_options($cid, $uid, $shared, $oid, $locations, $createonly);

        // Get the encoded location parent of the current parentuuid
        $location_parent = $repo->get_location_parent($parentuuid, $cid, $uid, $shared, $oid);

        // Get the tabview appropriate folder listing of the location parent
        $tab_listing = array();
        foreach ($locations as $key=>$location) {
            $tab_listing[$location['path']] = $repo->get_folder_listing($location['path'], $cid, $uid, $shared, $oid);
        }


        // Get the form container with an empty div for the tabs
        $return->form = $repo->print_move_dialog($parentuuid, $location_parent['path'], $cid, $uid, $shared, $oid, $selected_files);
        $return->listing = $tab_listing;
        $return->locations = $locations;
        $return->location_path = $location_parent['path'];
        $return->location_name = $location_parent['name'];
        echo json_encode($return);
        die;

    // Move the selected file(s) to the targetuuid
    case 'movefiles':
        $errormsg = '';
        $encodedtargetuuid = required_param('targetuuid', PARAM_ALPHANUMEXT);
        $encodedparentuuid = required_param('parentuuid', PARAM_ALPHANUMEXT);
        $selected_files = required_param('selected_files', PARAM_NOTAGS);

        $targetparams = unserialize(base64_decode($encodedtargetuuid));
        if (is_array($targetparams)) {
            $targetuuid    = empty($targetparams['path']) ? NULL : clean_param($targetparams['path'], PARAM_PATH);
        } else {
            $targetuuid   = NULL;
        }
        $return = new stdClass();

        $files_array = explode(",",$selected_files);
        foreach ($files_array as $file) {
            $sourceparams = unserialize(base64_decode($file));
            if (is_array($sourceparams)) {
                $sourceuuid    = empty($sourceparams['path']) ? NULL : clean_param($sourceparams['path'], PARAM_PATH);
            } else {
                $sourceuuid   = NULL;
            }
            if (!elis_files_move_node($sourceuuid, $targetuuid)) {
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
            $return->uuid = $encodedparentuuid;
        }

        echo json_encode($return);
        die;

    // Popup to allow batch uploading of files
    case 'uploadpopup':
        $upload_popup = array();
        $upload_popup['form'] = $repo->print_upload_popup();
        echo json_encode($upload_popup);
        die;

}
