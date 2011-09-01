<?php
/**
 * A repository factory that is used to create an instance of a repository
 * plug-in.
 *
 * Note: shamelessly "borrowed" from /enrol/enrol.class.php
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
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
 * @subpackage elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/*
 * This file was based on /files/index.php from Moodle, with the following
 * copyright and license:
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

    class repository_factory {

        function factory() {
            /*global $CFG, $SESSION;
            // Check for Alfresco version setting
            $alfresco_version = get_config('elis_files', 'alfresco_version');
            if ($alfresco_version == '3.4') {
                require_once(dirname(__FILE__).'/lib/alfresco34/ELIS_files.php');
            } elseif ($alfresco_version == '3.2') {
                require_once(dirname(__FILE__).'/lib/alfresco30/ELIS_files.php');
            } else {
                return false;
            // part of catch-22 fix require_once(dirname(__FILE__).'/lib/alfresco34/ELIS_files.php');
            }

            $class = "ELIS_files";
            // Don't store in session for now
            //if (!(isset($SESSION->elis_repo))) {
            //    $SESSION->elis_repo = new $class;
           // } else {
           //     $SESSION->elis_repo = unserialize(serialize($SESSION->elis_repo));
           // }
           // return $SESSION->elis_repo;
           return new $class;*/
//        function factory($repository = '') {
            global $CFG, $USER;
//            if (!$repository) {
//                $repository = $CFG->repository;
//            }
            if (file_exists(dirname(__FILE__).'/lib/ELIS_files.php')) {
                require_once(dirname(__FILE__).'/lib/ELIS_files.php');
                $class = "ELIS_files";
                // Need to test that this will work in 2.0
                /*if (!(isset($SESSION->repo))) {
                    $SESSION->repo = new $class;
                } else {
                    $SESSION->repo = unserialize(serialize($USER->repo));
                }
                return $SESSION->repo;*/
                return new $class;
            } else {
                trigger_error(dirname(__FILE__).'/lib/ELIS_files.php does not exist');
                error(dirname(__FILE__).'/lib/ELIS_files.php does not exist');
            }
        }
    }
?>
