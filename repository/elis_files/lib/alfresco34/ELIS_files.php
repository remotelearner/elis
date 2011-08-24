<?php
/**
 * The DMS repository plug-in for using an external Alfresco DMS site.
 *
 * NOTE: Shamelessly "borrowed" from the enrolment plug-in structure located
 *       in the /enrol/ directory.
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
 * @subpackage elis files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/*
 * This file was based on the enrolment plugin structure located in the /enrol/
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

/// Alfresco API v3.4
//require_once($CFG->libdir . '/alfresco30/lib.php');
require_once(dirname(__FILE__).'/lib.php');
//require_once($CFG->libdir . '/cmis-php/cmis_repository_wrapper.php');
require_once(dirname(__FILE__). '/cmis-php/cmis_repository_wrapper.php');
//require_once(dirname(__FILE__).'/ELIS_files.php');


define('ELIS_FILES_CRON_VALUE',  HOURSECS); // Run the cron job every hour.
define('ELIS_FILES_LOGIN_RESET', 5 * MINSECS);      // Reset a login after 5 minutes.

define('ELIS_FILES_DEBUG_TIME',  false);
define('ELIS_FILES_DEBUG_TRACE', false);

// Alfresco type definitions
define('ELIS_FILES_TYPE_FOLDER',   'cmis:folder');
define('ELIS_FILES_TYPE_DOCUMENT', 'cmis:document');

// Define constants for the Alfresco role names.
define('ELIS_FILES_ROLE_COORDINATOR',  'Coordinator');   // The coordinator gets all permissions and permission groups defined
define('ELIS_FILES_ROLE_COLLABORATOR', 'Collaborator');  // Combines Editor and Contributor permission groups
define('ELIS_FILES_ROLE_CONTRIBUTOR',  'Contributor');   // Includes the Consumer permission group and adds AddChildren and CheckOut.
define('ELIS_FILES_ROLE_EDITOR',       'Editor');        // Includes the Consumer permission group and adds Write and CheckOut
define('ELIS_FILES_ROLE_CONSUMER',     'Consumer');      // Includes Read

// Define constants for the Alfresco capability names.
define('ELIS_FILES_CAPABILITY_ALLOWED', 'ALLOWED');
define('ELIS_FILES_CAPABILITY_DENIED',  'DENIED');

// Define constants for the default file browsing location.
//define('ELIS_FILES_BROWSE_MOODLE_FILES',          10);
defined('ELIS_FILES_BROWSE_SITE_FILES') or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES') or define('ELIS_FILES_BROWSE_USER_FILES',   50);


class ELIS_files {

    var $errormsg = '';  // Standard error message varible.
    var $log      = '';  // Cron task log messages.
    var $cmis     = null;  // CMIS service connection object.
    var $muuid    = '';  // Moodle root folder UUID
    var $suuid    = '';  // Shared folder UUID
    var $cuuid    = '';  // Course folder UUID
    var $uuuid    = '';  // User folder UUID
    var $ouuid    = '';  // Organizations folder UUID
    var $root     = '';  // Root folder UUID

    function ELIS_files() {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('ELIS_files()');


        // Check for the USER->repo and the array elements
       /* if (isset($USER->elis_repo)) {
            $USER->elis_repo = unserialize(serialize($USER->elis_repo));
            if (isset($USER->elis_repo['root'])) {
                $this->root = $USER->elis_repo['root'];
            }
            if (isset($USER->elis_repo['cmis'])) {
                $this->cmis = $USER->elis_repo['cmis'];
            }
            if (isset($USER->elis_repo['muuid'])) {
                $this->muuid = $USER->elis_repo['muuid'];
            }
            if (isset($USER->elis_repo['suuid'])) {
                $this->suuid = $USER->elis_repo['suuid'];
            }
            if (isset($USER->elis_repo['cuuid'])) {
                $this->cuuid = $USER->elis_repo['cuuid'];
            }
            if (isset($USER->elis_repo['uuuid'])) {
                $this->uuuid = $USER->elis_repo['uuuid'];
            }
            if (isset($USER->elis_repo['ouuid'])) {
                $this->ouuid = $USER->elis_repo['ouuid'];
            }
            if (isset($USER->elis_repo->config)) {
                $this->config = $USER->elis_repo->config;
            } else {
                // Get config field object
                $this->config = get_config('elis_files');
            }
        } else {
            $USER->elis_repo = new me?
        }*/
        $this->process_config(get_config('elis_files'));

        if (!$this->is_configured()) {
            return false;
        }

        return $this->verify_setup();
    }


/**
 * See if the plug-in is configured correctly.
 *
 * @param none
 * @return bool True if the plug-in has the minimum setup done, False otherwise.
 */
    function is_configured() {
        global $CFG;

        return (!empty($CFG->repository_elis_files_server_host) &&
                !empty($CFG->repository_elis_files_server_port) &&
                !empty($CFG->repository_elis_files_server_username) &&
                !empty($CFG->repository_elis_files_server_password));
    }



/**
 * Verify that the Alfresco repository is currently setup and ready to be
 * used with Moodle (i.e. the needed directory structure is in place).
 *
 * @uses $CFG
 * @param none
 * @return bool True if setup, False otherwise.
 */
    function verify_setup() {
        // skip for ELIS2
        return true;
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('verify_setup()');

        if (empty($this->cmis)) {
            $this->cmis = new CMISService(elis_files_base_url() . '/api/cmis',
                                          $CFG->repository_elis_files_server_username,
                                          $CFG->repository_elis_files_server_password);
        }

        $root = $this->get_root();

        if ($root == false || !isset($root->uuid)) {
            return false;
        }

//print_object($root);
        // If there is no root folder saved or its set to default,
        // make sure there is a default '/moodle' folder.
        if (empty($CFG->repository_elis_files_root_folder) ||
            ($CFG->repository_elis_files_root_folder == '/moodle')) {

            $dir = $this->read_dir($root->uuid, true);
//print_object($dir);
            if (!empty($dir->folders)) {
                foreach ($dir->folders as $folder) {
                    if ($folder->title == 'moodle') {
                        $muuid = $folder->uuid;
                    }
                }
            }

            // Create the main Moodle directory.
            if (empty($muuid)) {
                $muuid = $this->create_dir('moodle', $root->uuid, '', true);

                if ($muuid === false) {
                    return false;
                }
            }

            if (empty($muuid)) {
                debugging(get_string('invalidpath', 'repository_elis_files'));
                return false;
            }

            $this->muuid = $muuid;
            $this->node_inherit($muuid, false);

        // Otherwise, use the folder that the plug-in has been configured with.
        } else {
            if (!$uuid = elis_files_uuid_from_path($CFG->repository_elis_files_root_folder)) {
                debugging(get_string('invalidpath', 'repository_elis_files'));
                return false;
            }

            $this->muuid = $uuid;
            $this->node_inherit($uuid, false);
        }

        // Attempt to find the UUID of the main storage folders within the root.
        $dir = $this->read_dir($this->muuid, true);

        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == 'shared') {
                    $this->suuid = $folder->uuid;
                } else if ($folder->title == 'course') {
                    $this->cuuid = $folder->uuid;
                } else if ($folder->title == 'user') {
                    $this->uuuid = $folder->uuid;
                } else if ($folder->title == 'organization') {
                    $this->ouuid = $folder->uuid;
                }
            }
        }

        // Create the shared storage directory.
        if (empty($this->suuid)) {
            $suuid = $this->create_dir('shared', $this->muuid, true);

            if ($suuid === false) {
                return false;
            }

            $this->suuid = $suuid;
            $this->node_inherit($suuid, false);
        }

        // Create the course space directory.
        if (empty($this->cuuid)) {
            $cuuid = $this->create_dir('course', $this->muuid, true);

            if ($cuuid === false) {
                return false;
            }

            $this->cuuid = $cuuid;
            $this->node_inherit($cuuid, false);
        }

        // Create the organization shared storage directory.
        if (empty($this->ouuid)) {
            $ouuid = $this->create_dir('organization', $this->muuid, true);

            if ($ouuid === false) {
                return false;
            }

            $this->ouuid = $ouuid;
            $this->node_inherit($ouuid, false);
        }

        // We no longer will automatically create the course space directory as it's no longer needed.


        // Make sure the temp directory is enabled.
        if (!is_dir($CFG->dataroot . '/temp/alfresco')) {
            mkdir($CFG->dataroot . '/temp/alfresco', $CFG->directorypermissions, true);
        }

        return true;
    }


/**
 * Get information about the root node in the repository.
 *
 * @param none
 * @return object|bool Processed node data or, False on error.
 */
    function get_root() {
        if (!$root = $this->cmis->getObjectByPath('/')) {
            return false;
        }
//print_object($root);
        $type = '';
        return elis_files_process_node($root, $type);
    }


/**
 * Processes and stored configuration data for the repository plugin.
 *
 * @param object $config All the configuration data as entered by the admin.
 * @return bool True on success, False otherwise.
 */
    function process_config($config) {
        if (!isset($config->server_host)) {
            $config->server_host = '';
        }
        if (!isset($config->server_port)) {
            $config->server_port = '';
        }
        if (!isset($config->server_username)) {
            $config->server_username = '';
        }
        if (!isset($config->server_password)) {
            $config->server_password = '';
        }
//TODO: for the install issue
//echo '<br>config:';
//print_object($config);
        /*set_config('repository_elis_files_server_host', stripslashes(trim($config->repository_elis_files_server_host)), 'elis_files');
        set_config('repository_elis_files_server_port', stripslashes(trim($config->repository_elis_files_server_port)), 'elis_files');
        set_config('repository_elis_files_server_username', stripslashes(trim($config->repository_elis_files_server_username)), 'elis_files');
        set_config('repository_elis_files_server_password', stripslashes(trim($config->repository_elis_files_server_password)), 'elis_files');*/
        set_config('server_host', stripslashes(trim($config->server_host)));
        set_config('server_port', stripslashes(trim($config->server_port)));
        set_config('server_username', stripslashes(trim($config->server_username)));
        set_config('server_password', stripslashes(trim($config->server_password)));


        return true;
    }


/**
 * Get the URL used to connect to the repository.
 *
 * @return string The connection URL.
 */
    function get_repourl() {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_repourl()');

        $repourl = $CFG->repository_elis_files_server_host;

        if ($repourl[strlen($repourl) - 1] == '/') {
            $repourl = substr($repourl, 0, strlen($repourl) - 1);
        }

        return $repourl . ':' . $CFG->repository_elis_files_server_port . '/alfresco/s/api';
    }


/**
 * Get the URL that allows for direct access to the web application.
 *
 * @param bool $gotologin Set to False to not give a URL directly to the login form.
 * @return string A URL for accessing the Alfrecso web application.
 */
    function get_webapp_url($gotologin = true) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_webapp_url()');

        return $CFG->repository_elis_files_server_host . ':' . $CFG->repository_elis_files_server_port . '/alfresco/' .
               ($gotologin ? 'faces/jsp/login.jsp' : '');
    }


/**
 * Get a WebDAV-specific URL for the repository.
 *
 * @param none
 * @return string A WebDAV-specific URL.
 */
    function get_webdav_url() {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_webdav_url()');

        return $CFG->repository_elis_files_server_host . ':' . $CFG->repository_elis_files_server_port . '/alfresco/webdav/';
    }


/**
 * Connect to an external Alfresco DMS repository.
 *
 * In the event of any error the object paramter '$errormsg' will be set
 * to an approriate value.
 *
 * @uses $USER
 * @param string $username The username to authenticate with.
 * @param string $password The password to authenticate with.
 * @return bool True on sucess, False otherwise.
 */
    function connect($username, $password) {
        global $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('connect(' . $username . ', ' . $password . ')');

        $this->errormsg = '';

        if (empty($username) || empty($password)) {
            $this->errormsg = get_string('usernameorpasswordempty', 'repository_elis_files');
            return false;
        }

    /// Create the session
        $repository = new Repository($this->get_repourl());
        $ticket     = null;

        if (!isset($USER->alfresoSession)) {
            $ticket = $repository->authenticate($username, $password);
            $USER->alfrescoTicket = $ticket;
            $USER->alfrescoLogin  = time();
        }

        return true;
    }


/**
 * Get info about a specific node reference.
 *
 * @param string $uuid A node UUID value.
 */
    function get_info($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_info(' . $uuid . ')');

        if (!$node = $this->cmis->getObject('workspace://SpacesStore/' . $uuid)) {
            return false;
        }

        $type = '';
        return elis_files_process_node($node, $type);
    }


/**
 * Is a given node a directory?
 *
 * @param string $uuid A node UUID value.
 * @return bool True if
 */
    function is_dir($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('is_dir(' . $uuid . ')');

        if (!$node = $this->cmis->getObject('workspace://SpacesStore/' . $uuid)) {
            return false;
        }

        $type  = '';
        $pnode = elis_files_process_node($node, $type);

        return ($type == ELIS_FILES_TYPE_FOLDER);
    }


/**
 * Send a search query to Alfresco.
 *
 * The return format of this method is the same as the read_dir() method.
 *
 * @uses $USER
 * @param string $query     The search query to send.
 * @param array  $catfilter An array of Alfresco category DB record IDs. (optional)
 * @return object|bool An object conmtaining the folders and files found by the
 *                     query or False on failure.
 */
    function search($query, $catfilter = array()) {
        global $DB, $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('search(' . $query . ')');

        $this->errormsg = '';

        if (!empty($catfilter)) {
            $cats = $DB->get_records_list('elis_files_categories', array('id'=> implode(', ', $catfilter)));
        }

        if (!empty($cats) && $query == '*') {
            $results = elis_files_category_search($cats);
        } else {
            $results = elis_files_search($query);
        }

        if (!empty($results->folders)) {
            foreach ($results->folders as $i => $node) {
                if (!$this->permission_check($node->uuid, $USER->id, false)) {
                    unset($results->folders[$i]);
                } else if (!empty($cats)) {
                    if (!$this->category_filter($node, $cats)) {
                        unset($results->folders[$i]);
                    }
                }
            }
        }

        if (!empty($results->files)) {
            foreach ($results->files as $i => $node) {
                if (!$this->permission_check($node->uuid, $USER->id, false)) {
                    unset($results->files[$i]);
                } else if (!empty($cats)) {
                    if (!$this->category_filter($node, $cats)) {
                        unset($results->files[$i]);
                    }
                }
            }
        }

        return $results;
    }


/**
 * Filter search results based on selected categories.
 *
 * @param object $node       A processed Alfresco node.
 * @param array  $categories An array of category DB records.
 * @return bool Whether the node has one of the selected categories assigned to it.
 */
    function category_filter($node, $categories) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_filter(' . $node . ', ' . $categories . ')');

        if (!$nodecats = elis_files_get_node_categories($node->noderef)) {
            return false;
        }

        foreach ($nodecats as $uuid => $name) {
            foreach ($categories as $category) {
                if ($uuid == $category->uuid) {
                    return true;
                }
            }
        }

        return false;
    }


/**
 * Read a directory from the repository server.
 *
 * @param string $uuid     Unique identifier for a node.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return object|bool An object representing the layout of the directory or
 *                     False on failure.
 */
    function read_dir($uuid = '', $useadmin = true) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('read_dir(' . $uuid . ', ' . ($useadmin !== true ? 'false' : 'true') . ')');

        if (empty($uuid)) {
            if ($root = $this->get_root()) {
                $uuid = $root->uuid;
            }
        }
//print_object('workspace://SpacesStore/' . $uuid);
        if (!$result = $this->cmis->getChildren('workspace://SpacesStore/' . $uuid)) {
            return false;
        }

        $return = new stdClass;
        $return->folders = array();
        $return->files   = array();

        foreach ($result->objectsById as $child) {
            $type = '';

            $node = elis_files_process_node($child, $type);

            if ($type == ELIS_FILES_TYPE_FOLDER) {
                $return->folders[] = $node;
            // Only include a file in the list if it's title does not start with a period '.'
            } else if ($type == ELIS_FILES_TYPE_DOCUMENT && !empty($node->title) && $node->title[0] !== '.') {
                $return->files[] = $node;
            }
        }

        return $return;
    }


/**
 * Return a file from the repository server.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $uuid      Unique identifier for a node.
 * @param string $localfile A full system path (with filename) to download the contents locally.
 * @param bool   $return    Set to true to just return the file content (optional).
 * @param bool   $process   Whether to process text-based content for relative URLs for converting.
 * @return mixed|bool Returns the file to the browser (with appropriate MIME headers) or False on failure.
 */
    function read_file($uuid, $localfile = '', $return = false, $process = true) {
        global $CFG, $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('read_file(' . $uuid . ')');

        $this->errormsg = '';

        $downloading = false;

        if (!empty($localfile)) {
            if (!$localfh = fopen($localfile, 'w')) {
                return false;
            }

            $downloading = true;
        }

        $node = $this->get_info($uuid);

//        if (isloggedin()) {
//            $username = $USER->username;
//        } else {
//            $username = '';
//        }
        $username = '';

    /// Check for a non-text file type to just pass through to the end user.
        $mimetype = !empty($node->filemimetype) ? $node->filemimetype : '';

        $url = str_replace($node->filename, urlencode($node->filename), $node->fileurl) . '?alf_ticket=' .
               elis_files_utils_get_ticket('refresh', $username);

    /// IE compatibiltiy HACK!
        if (!$downloading &&
            strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false && ini_get('zlib.output_compression') == 'On') {

            ini_set('zlib.output_compression', 'Off');
        }

        $processtypes = array(
            'application/x-javascript',
            'application/xhtml+xml',
            'text/css'
        );

    /// Don't process non-text and "known" filetypes for relative links.
        if ((strpos($mimetype, 'text') !== 0 && !in_array($mimetype, $processtypes)) || !$process) {
            if (!$downloading && !$return && $headers = get_headers($url)) {
                foreach ($headers as $header) {
                    if (0 === strpos(strtolower($header), 'http') ||
                        0 === strpos(strtolower($header), 'server') ||
                        0 === strpos(strtolower($header), 'set-cookie') ||
                        0 === strpos(strtolower($header), 'connection') ||
                        0 === strpos(strtolower($header), 'content-length')) {

                        continue;
                    }

                    header($header);
                }
            }

            if (!$downloading && !$return) {
            /// Cache this file for the required amount of time.
                if (!empty($CFG->repository_elis_files_cachetime)) {
                    $age = $CFG->repository_elis_files_cachetime;
                } else {
                /// The "No" caching option has a value of zero, so
                /// this handles the default (not set), as well as selecting "No"
                    $age = 0;
                }

                header('Cache-Control: max-age=' . $age);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $age) . ' GMT');
                header('Pragma: ');

                header('Content-Disposition: filename="' . $node->filename . '"');
            }

        /// Close session - not needed anymore.
            if (!$return) {
                @session_write_close();
            }

        /// Read the file contents in chunks and output directly.
            if ($fh = fopen($url, 'rb')) {
                if ($return) {
                    $buffer = '';
                }
                if (!$downloading) {
                    while (!feof($fh)) {
                        if (($buf = fread($fh, 8192)) === false) {
                            return false;
                        }

                        if (!$return) {
                            echo $buf;
                        } else {
                            $buffer .= $buf;
                        }
                    }
                } else {
                /// Downloading locally, write the file contents to the local file handler.
                    $byteswritten = stream_copy_to_stream($fh, $localfh);
                    return ($byteswritten == $node->filesize);
                }

                fclose($fh);
            } else {
                return false;
            }

            if ($return) {
                return $buffer;
            }

            exit;
        }

    /// Process the file, re-writing relative links to direct URLs which will
        $filecontents = '';

    /// Read the file contents in chunks and output store for processing.
        if ($fh = fopen($url, 'r')) {
            while (!feof($fh)) {
                if (($filecontents .= fread($fh, 8192)) === false) {
                    return false;
                }
            }

            fclose($fh);
        } else {
            return false;
        }

        $this->fix_links($filecontents, $this->get_file_path($node->uuid));

    /// We've asked for the file contents to just be returned, so do that now.
        if ($return) {
            return $filecontents;
        }

    /// Downloading locally, write the file contents to the local file handler.
        if ($downloading) {
            if (($byteswritten = fwrite($localfh, $filecontents, strlen($filecontents))) === false) {
                return false;
            } else {
                return true;
            }
        }

        if ($headers = get_headers($url)) {
            foreach ($headers as $header) {
                if (0 === strpos($header, 'HTTP') ||
                    0 === strpos($header, 'Server') ||
                    0 === strpos($header, 'Set-Cookie') ||
                    0 === strpos($header, 'Connection')) {

                    continue;
                }

                header($header);
            }
        }

    /// Cache this file for the required amount of time.
        if (!empty($CFG->repository_elis_files_cachetime)) {
            $age = $CFG->repository_elis_files_cachetime;
        } else {
        /// The "No" caching option has a value of zero, so
        /// this handles the default (not set), as well as selecting "No"
            $age = 0;
        }

        header('Cache-Control: max-age=' . $age);
        header('Expires: '. gmdate('D, d M Y H:i:s', time() + $age) .' GMT');
        header('Pragma: ');

        header('Content-Disposition: filename="' . $node->filename . '"');

    /// Close session - not needed anymore.
        @session_write_close();

        echo $filecontents;
        exit;
    }


/**
 * Correct relative links in a repo file.
 *
 * @uses $CFG
 * @param string $body A reference to the page body data.
 * @param string $path The path for the location of the file itself.
 * @return none
 */
    function fix_links(&$body, $path) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('fix_links($body, ' . $path . ')');

    /// Find all relative links within the document.
        $pattern  = "/((@import\s+[\"'`]([\w:?=@&\/#._;-]+)[\"'`];)|";
        $pattern .= "(:{0,1}\s*url\s*\([\s\"'`]*([\w:?=@&\/#._;-]+)";
        $pattern .= "([\s\"'`]*\))|<[^>]*\s+(src|href|url)\=[\s\"'`]*";
        $pattern .= "([\w:?=@&\/#._;-]+)[\s\"'`]*[^>]*>))/i";
        preg_match_all($pattern, $body, $matches);

        $changes = array();

    /// Go through each relative link and try to find the exact content node,
    /// UUID value after figuring out the exact path in Alfresco, relative to
    /// the 'Company Home' of the Alfreco content heirarchy.
        if (!empty($matches) && is_array($matches)) {
            $totalmatches = array();

            if (!empty($matches[3])) {
                $totalmatches = array_merge($totalmatches, $matches[3]);
            }

            if (!empty($matches[5])) {
                $totalmatches = array_merge($totalmatches, $matches[5]);
            }

            if (!empty($matches[8])) {
                $totalmatches = array_merge($totalmatches, $matches[8]);
            }

            if (empty($totalmatches)) {
                return;
            }

            foreach ($totalmatches as $src) {
                if (empty($src)) {
                    continue;
                }

                $src = str_replace('//', '/', $src);
                $src = str_replace('\\\\', '\\', $src);

                if ($src == '/' || $src == '../' || isset($changes[$src])) {
                    continue;
                }

                $osrc  = $src;
                $npath = $path;

            /// If the relative URL is moving 'up' in the directory structure.
                if (strpos($src, '../') === 0) {
                    while (strpos($src, '../') === 0) {
                        $npath = preg_replace('/(.*\/)(.+\/)/', '$1', $npath);
                        $src   = substr($src, 3);
                    }

                    $npath .= ($npath[strlen($npath) - 1] != '/' ? '/' : '') . urldecode($src);

            /// The URL is relative to the current position as 'root'.
                } else {
                    $npath = $path . ($path[strlen($path) - 1] != '/' ? '/' : '') . urldecode($src);
                }

            /// Attempt to get the UUID value for the node based on it's full path.
                if (($uuid = $this->get_uuid_from_path($npath)) !== false) {
                    $changes[$osrc] = $CFG->wwwroot . '/file/repository/alfresco/' .
                                      'openfile.php?uuid=' . $uuid;
                }
            }
        }

    /// Replace all the relative links with the absolute links we've calculated (if any).
        if (!empty($changes)) {
            foreach ($changes as $src => $new) {
                $body = str_replace($src, $new, $body);
            }
        }
    }


/**
 * Calculate the size (in bytes) of a directory.
 *
 * @param string $uuid A node UUID value.
 * @return int The directory size (in bytes).
 */
    function get_dirsize($uuid) {
        $size = 0;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_dirsize(' . $uuid . ')');

        if ($repodir = $this->read_dir($uuid)) {
            if (!empty($repodir->files)) {
                foreach ($repodir->files as $file) {
                    $size .= $file->size;
                }
            }
        }

        return $size;
    }


/**
 * Process an uploaded file and send it into the repository.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $upload   The array index for the uploaded file in the $_FILES superglobal.
 * @param string $uuid     The parent folder UUID value.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return string|bool The new node's UUID value or, False on error.
 */
    function upload_file($upload = '', $path = '', $uuid = '', $useadmin = true) {
        global $CFG, $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('upload_file(' . $upload . ', ' . $path . ', ' . $uuid . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

        require_once($CFG->libdir . '/filelib.php');

        if (!empty($upload)) {
            if (!isset($_FILES[$upload]) || !empty($_FILES[$upload]->error)) {
                return false;
            }

            $filename = $_FILES[$upload]['name'];
            $filepath = $_FILES[$upload]['tmp_name'];
            $filemime = $_FILES[$upload]['type'];
            $filesize = $_FILES[$upload]['size'];

        } else if (!empty($path)) {
            if (!is_file($path)) {
                return false;
            }

            $filename = basename($path);
            $filepath = $path;
            $filemime = mimeinfo('type', $filename);
            $filesize = filesize($path);
        } else {
            return false;
        }

//        if (!$content = file_get_contents($filepath)) {
//            return false;
//        }

//        $result = $this->cmis->createDocument('workspace://SpacesStore/' . $uuid, $filename, array(), $content, $filemime);
//
//        if ($result !== false) {
//            $type = '';
//            $node = elis_files_process_node($result, $type);
//            return $node->uuid;
//        }
//
//        return false;
//    }

        $chunksize = 8192;

        $data1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<atom:entry xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200908/"
            xmlns:cmism="http://docs.oasis-open.org/ns/cmis/messaging/200908/"
            xmlns:atom="http://www.w3.org/2005/Atom"
            xmlns:app="http://www.w3.org/2007/app"
            xmlns:cmisra="http://docs.oasis-open.org/ns/cmis/restatom/200908/">
    <atom:title>' . $filename . '</atom:title>
    <atom:summary>' . get_string('uploadedbymoodle', 'repository_elis_files') . '</atom:summary>
    <cmisra:content>
        <cmisra:mediatype>' . $filemime . '</cmisra:mediatype>
        <cmisra:base64>';

        $data2 = '</cmisra:base64>
    </cmisra:content>

    <cmisra:object>
        <cmis:properties>
            <cmis:propertyId propertyDefinitionId="cmis:objectTypeId">
                <cmis:value>cmis:document</cmis:value>
            </cmis:propertyId>
        </cmis:properties>
    </cmisra:object>
</atom:entry>';

        $encodedbytes = 0;

        // Use a stream filter to base64 encode the file contents to a temporary file.
        if ($fi = fopen($filepath, 'r')) {
            if ($fo = tmpfile()) {
                stream_filter_append($fi, 'convert.base64-encode');

                // Write the beginning of the XML document to the temporary file.
                $encodedbytes += fwrite($fo, $data1, strlen($data1));

                // Copy the uploaded file into the temporary file (usng the base64 encode stream filter)
                // in 8K chunks to conserve memory.
                while(($encbytes = stream_copy_to_stream($fi, $fo, $chunksize)) !== 0) {
                    $encodedbytes += $encbytes;
                }
                fclose($fi);

                // Write the end of the XML document to the temporary file.
                $encodedbytes += fwrite($fo, $data2, strlen($data2));
            }
        }

        rewind($fo);

        // Force the usage of the configured Alfresco admin account, if requested.
        if ($useadmin) {
            $username = '';
        } else {
            $username = $USER->username;
        }

        $serviceuri = '/cmis/s/workspace://SpacesStore/i/' . $uuid . '/children';
        $url        = elis_files_utils_get_wc_url($serviceuri, 'refresh', $username);

        $uri        = parse_url($url);

        switch ($uri['scheme']) {
            case 'http':
                $port = isset($uri['port']) ? $uri['port'] : 80;
                $host = $uri['host'] . ($port != 80 ? ':'. $port : '');
                $fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
                break;

            case 'https':
            /// Note: Only works for PHP 4.3 compiled with OpenSSL.
                $port = isset($uri['port']) ? $uri['port'] : 443;
                $host = $uri['host'] . ($port != 443 ? ':'. $port : '');
                $fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
                break;

            default:
                $result->error = 'invalid schema '. $uri['scheme'];
                return $result;
        }

        // Make sure the socket opened properly.
        if (!$fp) {
            $result->error = trim($errno .' '. $errstr);
            return $result;
        }

        // Construct the path to act on.
        $path = isset($uri['path']) ? $uri['path'] : '/';
        if (isset($uri['query'])) {
            $path .= '?'. $uri['query'];
        }

        // Create HTTP request.
        $headers = array(
            // RFC 2616: "non-standard ports MUST, default ports MAY be included".
            // We don't add the port to prevent from breaking rewrite rules checking
            // the host that do not take into account the port number.
            'Host'           => "Host: $host",
            'Content-type'   => 'Content-type: application/atom+xml;type=entry',
            'User-Agent'     => 'User-Agent: Moodle (+http://moodle.org/)',
            'Content-Length' => 'Content-Length: ' . $encodedbytes,
            'MIME-Version'   => 'MIME-Version: 1.0'
        );

        $request = 'POST  '. $path . " HTTP/1.0\r\n";
        $request .= implode("\r\n", $headers);
        $request .= "\r\n\r\n";

        fwrite($fp, $request);

        // Write the XML request (which contains the base64-encoded uploaded file contents) into the socket.
        stream_copy_to_stream($fo, $fp);

        fclose($fo);

        fwrite($fp, "\r\n");

        // Fetch response.
        $response = '';
        while (!feof($fp) && $chunk = fread($fp, 1024)) {
            $response .= $chunk;
        }
        fclose($fp);

        // Parse response.
        list($split, $result->data) = explode("\r\n\r\n", $response, 2);
        $split = preg_split("/\r\n|\n|\r/", $split);

        list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
        $result->headers = array();

        // Parse headers.
        while ($line = trim(array_shift($split))) {
            list($header, $value) = explode(':', $line, 2);
            if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
                // RFC 2109: the Set-Cookie response header comprises the token Set-
                // Cookie:, followed by a comma-separated list of one or more cookies.
                $result->headers[$header] .= ','. trim($value);
            } else {
                $result->headers[$header] = trim($value);
            }
        }

        $responses = array(
            100 => 'Continue', 101 => 'Switching Protocols',
            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
            300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
            400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed',
            500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported'
        );

        // RFC 2616 states that all unknown HTTP codes must be treated the same as
        // the base code in their class.
        if (!isset($responses[$code])) {
            $code = floor($code / 100) * 100;
        }
    //TODO: check for $code 500 and add menu to replace copy or cancel the uploaded file with the same name as an existing file
    //        if($code == 500) {
    //
    //        } else
        if ($code != 200 && $code != 201 && $code != 304) {
            debugging(get_string('couldnotaccessserviceat', 'repository_elis_files', $serviceuri), DEBUG_DEVELOPER);
            return false;
        }

        $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($result->data);

        $nodes = $dom->getElementsByTagName('entry');

        if (!$nodes->length) {
            return false;
        }

        $type       = '';
        $properties = elis_files_process_node_old($dom, $nodes->item(0), $type);

        // Ensure that we set the current user to be the owner of the newly created directory.
        if (!empty($properties->uuid)) {
            // So that we don't conflict with the default Alfresco admin account.
            $username = $USER->username == 'admin' ? $CFG->repository_elis_files_admin_username : $USER->username;

            // We must include the tenant portion of the username here.
            if (($tenantname = strpos($CFG->repository_elis_files_server_username, '@')) > 0) {
                $username .= substr($CFG->repository_elis_files_server_username, $tenantname);
            }

            // We're not going to check the response for this right now.
            elis_files_request('/moodle/nodeowner/' . $properties->uuid . '?username=' . $username);
        }

        return $properties;
    }


/**
 * Upload the contents of an entire directory onto the Alfresco server,
 * moving throuh any sub-directories recursively as needed.
 *
 * @uses $CFG
 * @param string $path     The local filesystem path to upload contents from.
 * @param string $uuid     The folder UUID value on Alfresco to send contents.
 * @param bool   $recurse  Whether to recurse into subdirecotires (default: on).
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return bool True on success, False otherwise.
 */
    function upload_dir($path, $uuid, $recurse = true, $useadmin = true) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('upload_dir(' . $path . ', ' . $uuid . ', ' . $recurse . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

    /// Make sure the location we are meant to upload files and create new
    /// directories actually exists and is a folder, not a content node.
        if (!$this->is_dir($uuid)) {
            return false;
        }

    /// Make sure the local path is a directory.
        if (!is_dir($path)) {
            return false;
        }

        $path .= ($path[strlen($path) - 1] != '/' ? '/' : '');

    /// Parse through the directory, creating folders and content nodes for
    /// each directory and file we encounter.
        $dh = opendir($path);

        while ($file = readdir($dh)) {
            if ($file == '..' || $file == '.') {
                continue;
            }

            if (is_dir($path . $file)) {
                if (($fuuid = $this->create_dir($file, $uuid)) === false) {
                    return false;
                }

                if ($recurse) {
                    if (!$this->upload_dir($path . $file, $fuuid)) {
                        return false;
                    }
                }
            } else if (is_file($path . $file)) {
                if (!$this->upload_file('', $path . $file, $uuid)) {
                    return false;
                }
            }
        }

        if (ELIS_FILES_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("upload_dir('$path', '$uuid', $recurse): $time");
        }

        return true;
    }


/**
 * Download the contents of an entire directory from the Alfresco server,
 * moving throuh any sub-directories recursively as needed.
 *
 * @uses $CFG
 * @param string $path The local filesystem path to download the contents to.
 * @param string $uuid The folder UUID value on Alfresco to get contents.
 * @param bool   $recurse Whether to recurse into subdirecotires (default: on).
 * @return bool True on success, False otherwise.
 */
    function download_dir($path, $uuid, $recurse = true) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('download_dir(' . $path . ', ' . $uuid . ', ' . $recurse . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

    /// Make sure the local path is a directory.
        if (!is_dir($path)) {
            return false;
        }

        $node = $this->get_info($uuid);

    /// Make sure we've been pointed to a directory
        if (!$this->is_dir($node->uuid)) {
            return false;
        }

        $filelist = array();

        $path .= ($path[strlen($path) - 1] != '/' ? '/' : '');
        $npath = $path . $node->title . '/';

        if (!is_dir($npath)) {
            mkdir($npath, $CFG->directorypermissions, true);
        }

        if ($data = $this->read_dir($uuid)) {
            if (!empty($data->folders)) {
                foreach ($data->folders as $folder) {
                    if (!is_dir($npath . $folder->title)) {
                        mkdir($npath . $folder->title, $CFG->directorypermissions, true);
                    }

                    if ($recurse) {
                        $dirfiles = $this->download_dir($npath . $folder->title . '/', $folder->uuid, $recurse);

                        if ($dirfiles !== false) {
                            $filelist = array_merge($filelist, $dirfiles);
                        }
                    }
                }
            }

            if (!empty($data->files)) {
                foreach ($data->files as $file) {
                    if (!is_file($npath . $file->title)) {
                        if ($this->read_file($file->uuid, $npath . $file->title)) {
                            $filelist[] = $npath . $file->title;
                        }
                    }
                }
            }
        }

        if (ELIS_FILES_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("download_dir('$path', '$uuid', $recurse): $time");
        }

        return $filelist;
    }


/**
 * Copy a file from the repository to the local site data store.
 *
 * @param string $uuid     Unique identifier for a node.
 * @param string $filename The filename to write locally.
 * @param string $location The location (relative to Moodle's root data directory).
 */
    function copy_local($uuid, $filename, $location = '/') {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('copy_local(' . $uuid . ', ' . $filename . ', ' . $location . ')');

        $this->errormsg = '';

        $node = $this->get_info($uuid);

        if ($this->is_dir($uuid)) {
            return $this->download_dir($location, $uuid, true);
        }

        if (substr_compare($location, '/', strlen($location) - 1) != 0) {
            $location .= '/';
        }

        if (!$this->read_file($uuid, $location . $filename)) {
            $this->errormsg = get_string('couldnotwritelocalfile', 'repository_elis_files');
            return false;
        }

        return true;
    }


/**
 * Check if the given directory name exists at a certain folder.
 *
 * @param string $name The name of the directory we're checking for.
 * @param string $uuid The UUID of the parent directory we're checking for a name in.
 * @return bool Whether the directory exists or not.
 */
    function dir_exists($name, $uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('dir_exists(' . $name . ', ' . $uuid . ')');

        if ($repodir = $this->read_dir($uuid)) {
            if (!empty($repodir->folders)) {
                foreach ($repodir->folders as $folder) {
                    if ($folder->title == $name) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


/**
 * Attempt to create the given directory name within a certain folder.
 *
 * @param string $name        The name of the directory we're checking for.
 * @param string $uuid        The UUID of the parent directory we're checking for a name in.
 * @param string $description An optional description of the directory being created.
 * @param bool   $useadmin    Set to false to make sure that the administrative user configured in
 *                            the plug-in is not used for this operation (default: true).
 * @return bool True on success, False otherwise.
 */
    function create_dir($name, $uuid = '', $description = '', $useadmin = true) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('create_dir(' . $name . ', ' . $uuid . ', ' . $description . ', ' .
                                         ($useadmin ? 'true' : 'false') . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

        $this->errormsg = '';

        if ($return = $this->cmis->createFolder('workspace://SpacesStore/' . $uuid, $name)) {
            $type = '';
            $node = elis_files_process_node($return, $type);

            return $node->uuid;
        }
/*
        if ($node = elis_files_create_dir($name, $uuid, $description, $useadmin)) {
            if (ELIS_FILES_DEBUG_TIME) {
                $end  = microtime(true);
                $time = $end - $start;
                mtrace("create_dir('$name', '$uuid', '$description'): $time");
            }

            return $node->uuid;
        }
*/
        return false;
    }


/**
 * Delete a node from the repository, optionally recursing into sub-directories (only
 * relevant when the node being deleted is a folder).
 *
 * @uses $CFG
 * @uses $USER
 * @param string $uuid      The node UUID.
 * @param bool   $recursive Whether to recursively delete child content.
 * @return mixed
 */
    function delete($uuid, $recursive = false) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE)  print_object('repo elis_files_delete(' . $uuid . ', ' . $recursive . ')');

        // Ensure that we set the configured admin user to be the owner of the deleted file before deleting.
        // This is to prevent the user's Alfresco account from having space incorrectly attributed to it.
        // ELIS-1102
        elis_files_request('/moodle/nodeowner/' . $uuid . '?username=' . $CFG->repository_elis_files_server_username);

        if ($this->is_dir($uuid)) {
            if (elis_files_send('/cmis/i/' . $uuid.'/descendants', array(), 'DELETE') === false) {
                return false;
            }
        } else {
           if ($this->cmis->deleteObject('workspace://SpacesStore/' . $uuid) === false) {
            return false;
           }
        }

        return true;
    }


/**
 * Get the UUID of the course storage area.
 *
 * @param int  $cid    The course ID.
 * @param bool $create Set to false to not automatically create a course storage area.
 * @return string|bool The course store UUID value or, False on error.
 */
    function get_course_store($cid, $create = true) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_course_store(' . $cid . ')');

        $uuid = '';

        if (empty($this->cuuid)) {
            return false;
        }

        // Attempt to get the store UUID value
        if (($uuid = $DB->get_field('elis_files_course_store', 'uuid', array('courseid'=> $cid))) !== false) {
            // Verify that the folder still exists and if not, delete the DB record so we can create a new one below.
            if ($this->get_info($uuid) === false) {
                delete_records('elis_files_course_store', 'courseid', $cid);
            } else {
                return $uuid;
            }
        }

        $dir = $this->read_dir($this->cuuid);

        // Look at all of the course directories that exist for our course ID.
        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if (!empty($uuid)) {
                    continue;
                }

                if ($folder->title == $cid) {
                    $uuid = $folder->uuid;
                }
            }
        }

        if (!$course = $DB->get_record('course', array('id'=> $cid), 'shortname, fullname')) {
            return false;
        }

        // If we're allowed to create a course store, do so now.
        if ($create && ($uuid = $this->create_dir($course->shortname, $this->cuuid, $course->fullname))) {
            // Disable inheriting parent space permissions.  This can be disabled in Alfresco without being
            // reset by the code elsewhere.
            $this->node_inherit($uuid, false);
        }

        if (!empty($uuid)) {
            // Store the UUID value if it hasn't been stored already.
            if (!record_exists('elis_files_course_store', 'courseid', $cid)) {
                $coursestore = new stdClass;
                $coursestore->courseid = $cid;
                $coursestore->uuid     = $uuid;
                $coursestore->id       = $DB->insert_record('elis_files_course_store', $coursestore);
            }

            return $uuid;
        }

        return false;
    }


/**
 * Get the UUID of a specific orgnanization shared storage area.
 *
 * @param int  $oid    The organization ID.
 * @param bool $create Set to false to not automatically create an organization storage area.
 * @return string|bool The course store UUID value or, False on error.
 */
    function get_organization_store($oid, $create = true) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_organization_store(' . $oid . ')');

        $uuid = '';

        if (!isset($this->ouuid)) {
            return false;
        }

        // Attempt to get the store UUID value
        if (($uuid = $DB->get_field('elis_files_organization_store', 'uuid', array('organizationid'=> $oid))) !== false) {
            return $uuid;
        }

        $dir = $this->read_dir($this->ouuid);

        // Look at all of the organization directories that exist for our organization ID.
        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if (!empty($uuid)) {
                    continue;
                }

                if ($folder->title == $oid) {
                    $uuid = $folder->uuid;
                }
            }
        }

        // If we've forced it off, don't automatically create a course storage directory at this point.
        if (!$create) {
            return false;
        }

        // Make sure that the optional table we are about to check actually exists.
        $table = new XMLDBTable('crlm_cluster');

        if (!table_exists($table)) {
            return false;
        }

        if (!$organization = $DB->get_record('crlm_cluster', array('id'=> $oid))) {
            return false;
        }

        if ($node = elis_files_create_dir($organization->name, $this->ouuid, $organization->display)) {
            $uuid = $node->uuid;

            // Disable inheriting parent space permissions.  This can be disabled in Alfresco without being
            // reset by the code elsewhere.
            $this->node_inherit($uuid, false);
        }

        if (!empty($uuid)) {
            // Store the UUID value if it hasn't been stored already.
            if (!record_exists('elis_files_organization_store', 'organizationid', $oid)) {
                $organizationstore = new stdClass;
                $organizationstore->organizationid = $oid;
                $organizationstore->uuid           = $uuid;
                $organizationstore->id             = $DB->insert_record('elis_files_organization_store', $organizationstore);
            }

            return $uuid;
        }

        return false;
    }


/**
 * Check if a Moodle user has a personal storage area in Alfresco.
 *
 * @param int $uid The Moodleuser ID.
 * @return string|bool The user store UUID value or, False.
 */
    function has_old_user_store($uid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('has_user_store(' . $uid . ')');

        if (empty($this->uuuid)) {
            return false;
        }

        $dir = $this->read_dir($this->uuuid, true);

        // Look at all of the course directories that exist for our course ID.
        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == $uid) {
                    return $folder->uuid;
                }
            }
        }

        return false;
    }


/**
 * Get the UUID of the user personal storage area.
 *
 * @param int  $uid       The Moodle user ID.
 * @param bool $nomigrate Set to True to not force user data migration.
 * @return string|bool The user store UUID value or, False on error.
 */
    function get_user_store($uid, $nomigrate = false) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_user_store(' . $uid . ')');

        // Sanity checking.
        if (empty($uid) || !isset($this->uuuid)) {
            return false;
        }

        // Check if the user already has a personal storage directory.
        if ($nomigrate) {
            if (($uuid = $this->has_old_user_store($uid)) !== false) {
                return $uuid;
            }

            // Create a new personal storage directory for this user.
            $user = $DB->get_record('user', array('id'=> $uid));

            if ($node = elis_files_create_dir($user->id, $this->uuuid, fullname($user) . ' (' . $user->email . ')')) {
                return $node->uuid;
            }
        } else {
            if (!($username = $DB->get_field('user', 'username', array('id'=> $uid)))) {
                return false;
            }

            $uuid = false;

            if (($uuid = $this->has_old_user_store($uid)) !== false) {
                if (!$this->migrate_user($username)) {
                    return false;
                }
            }

            if (empty($this->uuuid)) {
                $this->uuuid = $this->elis_files_userdir($username);
            }
            return $this->uuuid;
        }

        return false;
    }


/**
 * Determine if a given UUID is the company home (root) of the Alfresco DMS
 * node structure or not.
 *
 * @param string $uuid Unique identifier for a node.
 * @return bool Whether the UUID is the company home or not.
 */
    function is_company_home($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('is_company_home(' . $uuid . ')');

        return ($this->get_root()->uuid == $uuid);
    }


/**
 * Get the parent of a specific node.
 *
 * @param string $uuid Unique identifier for a node.
 * @return object|bool An object representing the parent node or False.
 */
    function get_parent($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_parent(' . $uuid . ')');

        if (!$node = $this->cmis->getFolderParent('workspace://SpacesStore/' . $uuid)) {
            return false;
        }

        $type = '';
        return elis_files_process_node($node, $type);
    }


/**
 * Get navigation breadcrumbs for the current position in a repository file browser.
 *
 * @param string $uuid     Unique identifier for a node.
 * @param int    $courseid The course ID (optional).
 * @param int    $userid   The user ID (optional).
 * @param string $shared   Set to 'true' if this is for the shared storage area (optional).
 * @return array|bool An array of navigation link information or, False on error.
 */
    function get_nav_breadcrumbs($uuid, $courseid = 0, $userid = 0, $shared = '') {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_nav_breadcrumbs(' . $uuid . ', ' . $courseid . ', ' . $userid . ', ' . $shared . ')');

    /// Get the "ending" UUID for the 'root' of navigation.
        if ((empty($courseid) || $courseid == SITEID) && empty($userid) && empty($shared)) {
            $end = $this->get_root()->uuid;
        } else if (empty($userid) && $shared == 'true') {
            $end = $this->suuid;
//            print_object('End = shared: ' . $end);
        } else if (empty($userid) && empty($shared) && !empty($courseid) && $courseid != SITEID) {
            $end = $this->get_course_store($courseid);
//            print_object('End = course: ' . $end);
        } else if (!empty($userid)) {
            $end = $this->get_user_store($userid);
//            print_object('End = user: ' . $end);
        }

        $stack = array();
        $node  = $this->get_info($uuid);

        if ($node->type == ELIS_FILES_TYPE_FOLDER) {
            $nav = array(
                'name' => $node->title,
                'uuid' => $node->uuid
            );

            array_push($stack, $nav);

        } else if ($node->type != ELIS_FILES_TYPE_DOCUMENT) {
            return false;
        }

        if ($node->uuid == $end) {
            return false;
        }

        if (!$parent = $this->get_parent($node->uuid)) {
            return false;
        }

        while (!empty($parent->uuid) && $parent->uuid != $end) {
            $nav = array(
                'name' => $parent->title,
                'uuid' => $parent->uuid
            );
            array_push($stack, $nav);
            $parent = $this->get_parent($parent->uuid);
        }

        if (!empty($stack)) {
            return array_reverse($stack);
        }

        return false;
    }


/**
 * Get the full path of a specific content node in the repository.
 *
 * @param string $uuid Unique identifier for a node.
 * @return string|bool A string  False.
 */
    function get_file_path($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_file_path(' . $uuid . ')');

        $this->errormsg = '';

        $stack = array();

        if (($node = $this->get_info($uuid)) === false) {
            print_error('couldnotgetnodeproperties', 'repository_elis_files', '', $uuid);
            return false;
        }

        if ($node->type == ELIS_FILES_TYPE_FOLDER) {
            array_push($stack, $node->title);
        } else if ($node->type !== ELIS_FILES_TYPE_DOCUMENT) {
            return false;
        }

        if ($parent = $this->get_parent($node->uuid)) {
            while ($parent && !$this->is_company_home($parent->uuid)) {
                array_push($stack, $parent->title);
                $parent = $this->get_parent($parent->uuid);
            }
        }

        $path = '/';

        if (!empty($stack)) {
            while (!empty($stack)) {
                $path .= array_pop($stack) . '/';
            }
        }

        return $path;
    }


/**
 * Get the UUID for a node by supplying it's full path, relative to the
 * company home space.
 *
 * @param string $path The full path to the node.
 * @return string|bool The node UUID value, or False on error.
 */
    function get_uuid_from_path($path) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_uuid_from_path(' . $path . ')');

        $this->errormsg = '';

        $node  = $this->get_root();
        $parts = explode('/', $path);
        $uuid  = $node->uuid;

    /// Move through each path element, finding the node for each in turn.
        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            if (empty($part)) {
                continue;
            }

            $children = $this->read_dir($uuid);
            $found    = false;

            if (!empty($children->folders)) {
                foreach ($children->folders as $folder) {
                    if ($found) {
                        continue;
                    }

                    if ($folder->title == $part) {
                        $found = true;
                        $uuid  = $folder->uuid;
                    }
                }
            }

            if (!$found && ($i == count($parts) - 1)) {
                if (!empty($children->files)) {
                    foreach ($children->files as $file) {
                        if ($found) {
                            continue;
                        }

                        if ($file->title == $part) {
                            $found = true;
                            $uuid  = $file->uuid;
                        }
                    }
                }
            }

        /// We did not find a node for the current path element... oops!
            if (!$found) {
                return false;
            }
        }

        return $uuid;
    }


/**
 * Check for valid permissions givent the storage context for a file, also taking
 * into account the location where the file was included within Moodle.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $uuid   Unique identifier for a node.
 * @param int    $uid    The user ID (optional).
 * @param bool   $useurl Check the referring URL for Moodle-based permissions (default: true).
 * @return bool True if the user has permission to access the file, False otherwise.
 */
    function permission_check($uuid, $uid = 0, $useurl = true) {
        global $CFG, $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('permission_check(' . $uuid . ', ' . $uid . ', ' .
                                         ($useurl === true ? 'true' : 'false') . ')');

        if (!empty($uid)) {
            $uid = $USER->id;
        }

        if (!empty($CFG->repository_elis_files_root_folder)) {
            $moodleroot = $CFG->repository_elis_files_root_folder;
        } else {
            $moodleroot = '/moodle';
        }

        $sfile  = false;
        $cfile  = false;
        $shfile = false;
        $ufile  = false;

    /// Determine the context for this file in the store.
        if (($path = $this->get_file_path($uuid)) === false) {
            return false;
        }

        preg_match('/\\' . $moodleroot . '\/course\/([0-9]+)\//', $path, $matches);

    /// Determine, from the node path which area this file is stored in.
        if (count($matches) == 2) {
            $cid     = $matches[1];

        /// This is a server file.
            if ($cid == SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM);
                $sfile   = true;

        /// This is a course file.
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $cid);
                $cfile   = true;
            }
        }

    /// This is a shared file.
        if (empty($sfile) && empty($cfile)) {
            preg_match('/\\' . $moodleroot . '\/shared\//', $path, $matches);

            if (count($matches) == 1) {
                $context = get_context_instance(CONTEXT_SYSTEM);
                $shfile  = true;
            }
        }

    /// This is a user file.
        if (empty($sfile) && empty($cfile) && empty($shfile)) {
            preg_match('/\\' . $moodleroot . '\/user\/([0-9]+)\//', $path, $matches);

            if (count($matches) == 2) {
                $userid  = $matches[1];
                $context = get_context_instance(CONTEXT_SYSTEM);
                $ufile   = true;
            }
        }

    /// Default to the root of the Alfresco repository (requires system-level repository access).
        if (!isset($context)) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        }

    /// Attempt to determine where the file open request came from to determine if the current user
    /// has permission to access that file in Moodle (this overrides the current user's Alfresco
    /// permissions.
        $referer = get_referer(false);

        if ($useurl && !empty($referer)) {
            $frommodule  = strpos($referer, $CFG->wwwroot . '/mod/') !== false;
            $fromblock   = strpos($referer, $CFG->wwwroot . '/blocks/') !== false;
            $frommodedit = strpos($referer, '/course/modedit.php') !== false;
            $fromeditor  = strpos($referer, '/lib/editor/htmlarea/blank.html') !== false;
            $fromcourse  = strpos($referer, '/course/view.php') !== false;
            $fromsite    = ($referer == $CFG->wwwroot . '/' || $referer == $CFG->wwwroot ||
                            $referer == $CFG->wwwroot . '/index.php');

        /// If this access is coming from something inside of the mod or blocks directory, then allow access.
            if ($frommodule || $fromblock || $fromeditor) {
                return true;
            }

            if (!empty($referer) && ($frommodedit || $fromcourse || $fromsite)) {
                if ($fromsite) {
                    return true;
                } else if ($frommodedit) {
                /// Look for the CM ID from editing a module.
                    preg_match('/.+?update=([0-9]+)/', $referer, $matches);

                    if (count($matches) == 2) {
                        $sql = "SELECT cm.*, m.name as modname
                                FROM {$CFG->prefix}course_modules cm
                                INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module
                                WHERE cm.id = " . $matches[1];

                        if ($cm = $DB->get_record_sql($sql)) {
                            require_login($cm->course, false, $cm, false);
                        }
                    }

                } else if ($fromcourse) {
                /// Look for the course ID.
                    preg_match('/.+?id=([0-9]+)/', $referer, $matches);

                    if (count($matches) == 2) {
                        require_login($matches[1], false, false, false);
                    }
                }
            }

    /// This file didn't come from somewhere within Moodle that we know about so access has
    /// to be determined based on the Alfresco capabilities the current user has.
        } else {
            if ($ufile) {
            /// If the current user is not the user who owns this file and we can't access anyting in the
            /// repository, don't allow access.
                if ($USER->id != $userid && !has_capability('block/repository:viewsitecontent', $context)) {
                    return false;
                }

            /// This repository location is not tied to a specific Moodle context, so we need to look for the
            /// specific capability anywhere within the user's role assignments.
                $hascap = false;

                if (!empty($USER->access['rdef'])) {
                    foreach ($USER->access['rdef'] as $ctx) {
                        if (isset($ctx['block/repository:viewowncontent']) &&
                                  $ctx['block/repository:viewowncontent'] == CAP_ALLOW) {
                            $hascap = true;
                        }
                    }
                }

                if (!$hascap) {
                    return false;
                }

            } else if ($cfile) {
            /// This file belongs to a course, make sure the current user can access that course's repository
            /// content.
                if (!has_capability('block/repository:viewcoursecontent', $context)) {
                    return false;
                }

            } else if ($shfile) {
            /// This repository location is not tied to a specific Moodle context, so we need to look for the
            /// specific capability anywhere within the user's role assignments.
                $hascap = false;

                if (!empty($USER->access['rdef'])) {
                    foreach ($USER->access['rdef'] as $ctx) {
                        if (isset($ctx['block/repository:viewsharedcontent'])&&
                                  $ctx['block/repository:viewsharedcontent'] == CAP_ALLOW) {
                            $hascap = true;
                        }
                    }
                }

                if (!$hascap) {
                    return false;
                }
            } else {
            /// This file is not part of a standard Moodle storage area thus requiring full repository access.
                if (!has_capability('block/repository:viewsitecontent', $context)) {
                    return false;
                }
            }
        }

        return true;
    }


/**
 * Get a category record from the database.
 *
 * @param int $id The record ID (optional).
 * @param string $uuid The category UUID (optional).
 * @return object The database record.
 */
    function category_get($id = 0, $uuid = '') {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_get(' . $id . ', ' . $uuid . ')');

        if (!empty($id)) {
            if ($cat = $DB->get_record('elis_files_categories', array('id'=> $id))) {
                return $cat;
            }
        }

        if (!empty($uuid)) {
            if ($cat = $DB->get_record('elis_files_categories', array('uuid'=> $uuid))) {
                return $cat;
            }
        }

        return NULL;
    }


/**
 * Get a parent category object.
 *
 * @param int $catid The category ID to find the parent for.
 * @return object The parent category DB object.
 */
    function category_get_parent($catid) {
        global $DB;

//        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_get_parent(' . $catid . ')');

        $pid = $DB->get_field('elis_files_categories', 'parent', array('id'=> $catid));

        if ($pid > 0) {
            if ($cat = $DB->get_record('elis_files_categories', array('id'=> $pid))) {
                return $cat;
            }
        }

        return NULL;
    }


/**
 * Get the categories which are children of the specified category.
 *
 * @param int $catid The category database record ID.
 * @return array An array of category objects.
 */
    function category_get_children($catid) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_get_children(' . $catid . ')');

        $cats = array();
        if ($children = $DB->get_records('elis_files_categories', array('parent'=> $catid))) {
            foreach ($children as $child) {
                $cats[] = $child;
            }
        }

        return $cats;
    }


/**
 * Recursively store Alfresco categories (keeping intact the parent / child relationships).
 *
 * @paran array $uuids      Refernce to an array (contains all the UUIDs processed)
 *                          and used to delete obsolete DB entries after processing.
 * @param array $categories A nested array of returned category data.
 * @param int   $parent     The parent category ID (from the Moodle DB).
 * @return none
 */
    function process_categories(&$uuids, $categories, $parent = 0) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('process_categories(' . $uuids . ', ' . $categories . ', ' . $parent . ')');

        if (empty($categories)) {
            return;
        }

        foreach ($categories as $category) {
            global $DB;

            if (!in_array($category['uuid'], $uuids)) {
                $uuids[] = $category['uuid'];
            }

            if (!$cat = $this->category_get(0, $category['uuid'])) {
                $cat = new Object();
                $cat->parent = $parent;
                $cat->uuid   = $category['uuid'];
                $cat->path   = !empty($classification->category->id->path) ?
                               $classification->category->id->path : '';
                $cat->title  = addslashes($category['name']);
                echo '<br>adding category:';
                print_object($cat);
                $cat->id     = $DB->insert_record('elis_files_categories', $cat);
            } else {
                $cat->parent = $parent;
                $cat->uuid   = $category['uuid'];
                $cat->path   = !empty($classification->category->id->path) ?
                               $classification->category->id->path : '';
                $cat->title  = addslashes($category['name']);

                update_record('elis_files_categories', $cat);
            }

            if (!empty($cat->id)) {
                if (!empty($category['children'])) {
                    $this->process_categories($uuids, $category['children'], $cat->id);
                }
            }
        }
    }


/**
* Recursively builds a dynamic tree menu for seleting the categories to filter
* search results by.
*
* @param array  $cats     An array of category objects from the DB.
* @param array  $selected An array of currently selected category IDs.
* @return array An array of completed HTML_TreeMenu nodes.
*/
    function make_root_folder_select_tree($folders = false, $path = '') {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('make_root_folder_select_tree()');

        if (empty($folders) && (!$folders = elis_files_folder_structure())) {
            return false;
        }

        $icon  = 'folder.gif';
        $eicon = 'folder-expanded.gif';
        $nodes = array();

        foreach ($folders as $i => $folder) {
            $npath = $path . '/' . $folder['name'];

            $text = ' <a href="#" onclick="set_value(\'' . $npath . '\')" title="' . $npath . '">' . $folder['name'] .
                    (!empty($CFG->repository_elis_files_root_folder) && $CFG->repository_elis_files_root_folder == $npath ?
                    ' <span class="pathok">&#x2714;</span>' : '') . '</a>';

            $node = new HTML_TreeNode(array(
                'text'         => $text,
                'icon'         => $icon,
                'expandedIcon' => $eicon,
                'expanded'     => false
            ));

            if (!empty($folder['children'])) {
                if ($cnodes = $this->make_root_folder_select_tree($folder['children'], $npath)) {
                    for ($j = 0; $j < count($cnodes); $j++) {
                        $node->addItem($cnodes[$j]);
                    }
                }
            }

            $nodes[] = $node;
        }

        return $nodes;
    }


/**
 * Generate a list of menu options and a default selection which will be used as arguments to a popup_form()
 * function call in order to display a drop-down list of places from which to browse files using any Moodle
 * file browsing interface anywhere in the system.
 *
 * @uses $USER
 * @param int    $cid         A course record ID.
 * @param int    $uid         A user record ID.
 * @param int    $ouuid       The Alfresco uuid of an organizational cluster.
 * @param bool   $shared      A flag to indicate whether the user is currently located in the shared repository area.
 * @param string $choose      File browser 'choose' parameter.
 * @param string $origurl     The originating URL from where this function was called.
 * @param string $moodleurl   The base URL for browsing files from Moodledata.
 * @param string $alfrescourl The base URL for browsing files from Alfresco.
 * @param string $default     The default option to be selected in the form.
 * @return array An array of options meant to be used in a popup_form() function call.
 */
    function file_browse_options($cid, $uid, $ouuid, $shared, $choose, $origurl, $moodleurl, $alfrescourl, &$default) {
        global $USER;

        $opts = array();

        // Modify the base URLs to prepare them correctly for additional parameters to be added later.
        $origurl     .= ((strpos($origurl, '?') !== false) ? '&amp;' : '?') . 'dd=1&amp;';
        $moodleurl   .= ((strpos($moodleurl, '?') !== false) ? '&amp;' : '?') . 'dd=1&amp;';
        $alfrescourl .= ((strpos($alfrescourl, '?') !== false) ? '&amp;' : '?') . 'dd=1&amp;';

        if ($cid === SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $cid);
        }

        // Determine if the user has access to their own personal file storage area.
        $editalfshared   = false;
        $viewalfshared   = false;
        $editalfpersonal = false;
        $viewalforganization = false;
        $editalforganization = false;

        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $rdef) {
                if ($viewalfshared && $editalfshared &&
                    $viewalforganization && $editalforganization) {
                    continue;
                }

                if (isset($rdef['block/repository:createowncontent']) &&
                          $rdef['block/repository:createowncontent'] == CAP_ALLOW) {
                    $editalfpersonal = true;
                }

                if (isset($rdef['block/repository:viewsharedcontent']) &&
                          $rdef['block/repository:viewsharedcontent'] == CAP_ALLOW) {
                    $viewalfshared = true;
                }
                if (isset($rdef['block/repository:createsharedcontent']) &&
                          $rdef['block/repository:createsharedcontent'] == CAP_ALLOW) {
                    $editalfshared = true;
                }
                if (isset($rdef['block/repository:vieworganizationcontent']) &&
                          $rdef['block/repository:vieworganizationcontent'] == CAP_ALLOW) {
                    $viewalforganization = true;
                }
                if (isset($rdef['block/repository:createorganizationcontent']) &&
                          $rdef['block/repository:createorganizationcontent'] == CAP_ALLOW) {
                    $editalforganization = true;
                }
            }
        }


        $editmoodle            = has_capability('moodle/course:managefiles', $context);
        $editalfsite           = has_capability('block/repository:createsitecontent', $context);
        $viewalfsite           = has_capability('block/repository:viewsitecontent', $context);
        $editalfcourse         = has_capability('block/repository:createcoursecontent', $context);
        $viewalfcourse         = has_capability('block/repository:viewcoursecontent', $context);

        // Build the option for browsing from the Moodle site files.
        if ($editmoodle) {
            $surl        = $moodleurl . 'id=' . $cid . '&amp;userid=' . $uid . '&amp;choose=' . $choose;
            $opts[$surl] = ($cid == SITEID) ? get_string('sitefiles') : get_string('coursefiles');
        }

        // Build the option for browsing from the repository organization / course / site files.
        if ($cid === SITEID && $viewalfsite) {
            $curl        = $alfrescourl . 'id=' . $cid . '&amp;userid=0&amp;choose=' . $choose;
            $opts[$curl] = get_string('repositorysitefiles', 'repository');

            $alfroot = $this->get_root();

            if (!empty($alfroot->uuid)) {
                $uuid = $alfroot->uuid;

                // Make sure this user actually has the correct permissions assigned here.
                if ($editalfsite) {
                    if (!elis_files_has_permission($uuid, $USER->username, true)) {
                        $this->allow_edit($USER->username, $uuid);
                    }
                } else {
                    if (!elis_files_has_permission($uuid, $USER->username)) {
                        $this->allow_read($USER->username, $uuid);
                   }
                }
            }

        } else if ($cid != SITEID && $viewalfcourse) {
            $curl        = $alfrescourl . 'id=' . $cid . '&amp;userid=0&amp;choose=' . $choose;
            $opts[$curl] = get_string('repositorycoursefiles', 'repository');
            if (!elis_files_has_permission($this->muuid, $USER->username)) {
                $this->allow_read($USER->username, $this->muuid);
            }

            if (!elis_files_has_permission($this->cuuid, $USER->username)) {
                $this->allow_read($USER->username, $this->cuuid);
            }

            if (($uuid = $this->get_course_store($cid)) !== false) {
                // Make sure this user actually has the correct permissions assigned here.
                if ($editalfcourse) {
                    if (!elis_files_has_permission($uuid, $USER->username, true)) {
                        $this->allow_edit($USER->username, $uuid);
                    }
                } else {
                    if (!elis_files_has_permission($uuid, $USER->username)) {
                        $this->allow_read($USER->username, $uuid);
                    }
                }
            }
        }

        // Build the option for browsing from the repository shared files.
        if ($viewalfshared) {
            $shurl        = $alfrescourl . 'id=' . $cid . '&amp;shared=true&amp;userid=0&amp;choose=' . $choose;
            $opts[$shurl] = get_string('repositorysharedfiles', 'repository');

            if (!elis_files_has_permission($this->muuid, $USER->username)) {
                $this->allow_read($USER->username, $this->muuid);
            }

            if (!elis_files_has_permission($this->suuid, $USER->username)) {
                $this->allow_read($USER->username, $this->suuid);
            }

            if ($editalfshared) {
                if (!elis_files_has_permission($this->suuid, $USER->username, true)) {
                    $this->allow_edit($USER->username, $this->suuid);
                }
            } else {
                if (!elis_files_has_permission($this->suuid, $USER->username)) {
                    $this->allow_read($USER->username, $this->suuid);
                }
            }
        }

        // Build the option for browsing from the repository personal / user files.
        if ($editalfpersonal) {
            $uurl        = $alfrescourl . 'id=' . $cid . '&amp;userid=' . $USER->id . '&amp;choose=' . $choose;
            $opts[$uurl] = get_string('repositoryuserfiles', 'repository');
        }


        // Only allow organizational views/edits to those users who have the capability
        if ($viewalforganization  || $editalforganization) {
            // Get organizations folders to which the users belongs
            if ($organization_folders = $this->find_organization_folders($USER->id, $USER->username)) {
                foreach ($organization_folders as $name => $uuid) {
                    // Include organization name and uuid in the url
                    $ourl        = $alfrescourl . 'id=' . $cid . '&amp;ouuid='.$uuid.'&amp;oname='.$name. '&amp;userid=0&amp;choose=' . $choose;
                    $opts[$ourl] = $name;
                    if ($editalforganization) {
                        if (!elis_files_has_permission($uuid, $USER->username, true)) {
                            $this->allow_edit($USER->username, $uuid);
                        }
                    } else { // viewalforganization
                        if (!elis_files_has_permission($uuid, $USER->username)) {
                            $this->allow_read($USER->username, $uuid);
                        }
                    }
                }
            }
        }

        // If ouuid is passed to this function, include it and the organization name in the default url
        if (!empty($ouuid)) {
            // Get the organization folder name from Moodle
            $oname = array_search($ouuid,$this->find_organization_folders($USER->id,$USER->username));
        }

        // Assemble the default menu selection based on the information given to this method.
        $default = $origurl . 'id=' . $cid . (!empty($ouuid) ? '&amp;ouuid='.$ouuid : '') . (!empty($oname) ? '&amp;oname='.$oname : '') . (!empty($shared) ? '&amp;shared=true' : '') . '&amp;userid=' .
                   ($editalfpersonal ? $uid : '0') . '&amp;choose=' . $choose;

        return $opts;
    }

 /**
 * Find a list of organization folders that the user has access to.
 *
 * @param $CFG
 * @param int $muserid     The Moodle user id.
 * @param string $username The Moodle user name.
 * @return array Alfresco repository folder names.
 */
    function find_organization_folders($muserid,$username) {
        global $CFG, $DB;

        require_once($CFG->libdir . '/ddllib.php');

        // Ensure that the cluster table actually exists before we query it.
        $table = new XMLDBTable('crlm_cluster');
        if (!table_exists($table)) {
            return false;
        }

        if (!$cluster = $DB->get_record('crlm_cluster', array('id'=> $muserid))) {
            return false;
        }

        if (!file_exists($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php')) {
            return false;
        }

        require_once($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php');
        require_once($CFG->dirroot . '/curriculum/lib/cluster.class.php');

        // Get user clusters
        if ($clusters = cluster_get_user_clusters($muserid)) {
           if (!$cluster_info = $this->load_cluster_info($clusters, $muserid)) {
               return false;
           }
        } else {
            return false;
        }

        // There may be multiple clusters that this user is assigned to...
        $org_folders = array();
        foreach ($cluster_info as $cluster) {
            // Get the extra cluster data and ensure it is present before proceeding.
            $clusterdata = clusterclassification::get_for_cluster($cluster);

            if (empty($clusterdata->params)) {
                return false;
            }

            $clusterparams = unserialize($clusterdata->params);

            // Make sure this cluster has the Alfresco shared folder property defined
            if (empty($clusterparams['elis_files_shared_folder'])) {
                return false;
            }

            // Make sure we can get the storage space from Alfresco for this organization.
            if (!$uuid = $this->get_organization_store($cluster->id)) {
                return false;
            }

            $org_folders[$cluster->name] = $uuid;
        }

        return $org_folders;
    }


 /**
  * Function to load assigned cluster information into the user object.
  * @param int | array The cluster information as data id or cluster information array.
  * @param int The Moodle userid
  * #return array cluster data
 */
    function load_cluster_info($clusterinfo, $muserid) {
        global $DB;

        if (is_int($clusterinfo) || is_numeric($clusterinfo)) {
            if (!isset($cluster_data)) {
                $cluster_data = array();
            }
            if (!($ucid = $DB->get_field(CLSTUSERTABLE, 'id', array('userid'=> $muserid, 'clusterid'=> $clusterinfo)))) {
                $ucid = 0;
            }
            $cluster_data[$ucid] = new cluster($clusterinfo);
        } else if (is_array($clusterinfo)) {
            foreach ($clusterinfo as $ucid => $usercluster) {
                if (!isset($cluster_data)) {
                    $cluster_data = array();
                }
                $cluster_data[$ucid] = new cluster($usercluster->clusterid);
            }
        }
        return $cluster_data;
    }
/**
 * Update a Moodle user's Alfresco account with a new password value.
 *
 * @param $CFG
 * @param object $user     The Moodle DB record object or username.
 * @param string $password The new password for the Alfresco account.
 * @return bool True on success, False otherwise.
 */
    function update_user_password($user, $password) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('update_user_password(' . $user->username . ', ' . $password . ')');

        // If the user has an old-style user directory, migrate it's contents and delete the directory.
        $username = $user->username == 'admin' ? $CFG->repository_elis_files_admin_username : $user->username;

        // We must include the tenant portion of the username here.
        if (($tenantname = strpos($CFG->repository_elis_files_server_username, '@')) > 0) {
            $username .= substr($CFG->repository_elis_files_server_username, $tenantname);
        }

        // We need to create a new account now.
        $userdata = array(
            'username'     => $username,
            'password'     => $password,
            'firstname'    => $user->firstname,
            'lastname'     => $user->lastname,
            'email'        => $user->email,
            'organization' => $user->institution
        );

        if (!empty($CFG->repository_elis_files_user_quota)) {
            $userdata['quota'] = $CFG->repository_elis_files_user_quota;
        }

        $response = elis_files_send('/moodle/createuser', $userdata, 'POST');

        try {
            $sxml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            debugging(get_string('badxmlreturn', 'repository_elis_files') . "\n\n$response", DEBUG_DEVELOPER);
            return false;
        }

        // Verify the correct return results.
        return (!empty($sxml->username) && !empty($sxml->firstname) && !empty($sxml->lastname) && !empty($sxml->email));
    }


/**
 * Migrate a user account from the old style of personal file storage to the new, SSO-based, approach.
 *
 * @param $CFG
 * @param object|string $userorusername The Moodle DB user record object or username.
 * @param string        $password       Optionally specify a password for the Alfresco account.
 * @return bool True on success, False otherwise.
 */
    function migrate_user($userorusername, $password = '') {
        global $CFG, $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('migrate_user(' . (is_object($userorusername) ? 'object' : $userorusername) . ')');

        if (is_string($userorusername)) {
            if (!$user = $DB->get_record('user', array('username'=> $userorusername, 'mnethostid'=> $CFG->mnet_localhost_id))) {
                return false;
            }
        } else if (is_object($userorusername)) {
            $user = $userorusername;
        } else {
            return false;
        }

        // Create the user's Alfresco account (if one does not already exist).
        if (!elis_files_create_user($user, $password)) {
            return false;
        }

        // If the user has an old-style user directory, migrate it's contents and delete the directory.
        if ($this->has_old_user_store($user->id)) {
            $uuid = $this->get_user_store($user->id, true);

            if (($touuid = elis_files_get_home_directory($user->username)) === false) {
                debugging(get_string('couldnotgetalfrescouserdirectory', 'repository_elis_files', $user->username));
                return false;
            }

            $dir = $this->read_dir($uuid, true);

            if (!empty($dir->folders)) {
                foreach ($dir->folders as $folder) {
                    if (!elis_files_move_node($folder->uuid, $touuid)) {
                        debugging(get_string('couldnotmovenode', 'repository_elis_files'));
                        return false;
                    }
                }
            }

            if (!empty($dir->files)) {
                foreach ($dir->files as $file) {
                    if (!elis_files_move_node($file->uuid, $touuid)) {
                        debugging(get_string('couldnotmovenode', 'repository_elis_files'));
                        return false;
                    }
                }
            }

            // Redmove the old-style user storage directory.
            if (!elis_files_delete($uuid, true)) {
                debugging(get_string('couldnotdeletefile', 'repository_elis_files', $user->id));
                return false;
            }
        }

        return true;
    }


/**
 * Migrate all the Moodle users who have an old-style Alfresco storage directory to have their own personal
 * Alfresco user accounts and storage.
 *
 * @param int $limit Limit the number of users migrated to the specified number (default 50).
 * @return bool True on success, False otherwise.
 */
    function migrate_all_users($limit = 50) {
        global $CFG, $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('migrate_all_users(' . $limit . ')');

        $dir = $this->read_dir($this->uuuid);

        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                // Make sure that the folder name is actually just a Moodle user ID (created automatically by Moodle).
                preg_match('/^[0-9]+$/', $folder->title, $matches);

                if (!empty($matches) && is_array($matches) && count($matches) === 1) {
                    $uid = current($matches);

                    // If this user exists, migrate them to a legitimate Alfresco user now.
                    if ($user = $DB->get_record('user', array('id'=> $uid, 'deleted'=> 0))) {
                        if (!$this->migrate_user($user)) {
                            debugging(get_string('couldnotmigrateuser', 'repository_elis_files', $user->username));
                            return false;
                        }

                    // If this user account in Moodle is deleted, then we will remove their Alfresco storage.
                    } else if ($DB->record_exists('user', 'id', $uid, 'deleted', 1)) {
                        if (!afresco_delete($dir->uuid, true)) {
                            debugging(get_string('couldnotdeletefile', 'repository_elis_files', $dir->title));
                        }
                    }
                }

                // Check that we are still within the requested limit of users to process.
                if ($limit-- <= 0) {
                    return true;
                }
            }
        }

        // We must have migrated all the users but let's check to be sure.
        if ($limit > 0) {
            $dir = $this->read_dir($this->uuuid);

            if (empty($dir->folders)) {
                // We no longer need this directory so it may be removed now.
                elis_files_delete($this->uuuid);
            }
        }
    }


/**
 * Get an Alfresco user's home directory UUID.
 *
 * @param string $username The Alfresco user's username.
 * @return string|bool The UUID of the home directory or, False.
 */
    function elis_files_userdir($username) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('elis_files_userdir(' . $username . ')');

        if (($uuid = elis_files_get_home_directory($username)) === false) {
            return false;
        }

        return $uuid;
    }


/**
 * Delete a user's Alfresco account.
 *
 * @uses $CFG
 * @param string $username The Alfresco user's username.
 * @return bool True on success, False otherwise.
 */
    function delete_user($username) {
        global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('elis_files_delete(' . $username . ')');

        // Get the current value from the configuration option to enable or disable home directory deletion.
        $deletehomedir = !empty($CFG->repository_elis_files_deleteuserdir);

        return elis_files_delete_user($username, $deletehomedir);
    }


/**
 * Assign a user read access to a specific node.
 *
 * @param string $username The Alfresco user's username.
 * @param string $uuid     The Alfresco node UUID.
 * @return bool True on success, False otherwise.
 */
    function allow_read($username, $uuid) {
        return elis_files_set_permission($username, $uuid, ELIS_FILES_ROLE_CONSUMER, ELIS_FILES_CAPABILITY_ALLOWED);
    }


/**
 * Assign a user write access to a specific node.
 *
 * @param string $username The Alfresco user's username.
 * @param string $uuid     The Alfresco node UUID.
 * @return bool True on success, False otherwise.
 */
    function allow_edit($username, $uuid) {
        return elis_files_set_permission($username, $uuid, ELIS_FILES_ROLE_COLLABORATOR, ELIS_FILES_CAPABILITY_ALLOWED);
    }


/**
 * Remove a user's permissions from a specific node in Alfresco.
 *
 * @param string $username The Alfresco user's username.
 * @param string $uuid     The Alfresco node UUID.
 * @return bool True on success, False otherwise.
 */
    function remove_permissions($username, $uuid) {
        // Get all of the permissions that this user has set to ALLOW on this node and then remove them.
        if ($permissions = elis_files_get_permissions($uuid, $username)) {
            foreach ($permissions as $permission) {
                if (!elis_files_set_permission($username, $uuid, $permission, ELIS_FILES_CAPABILITY_ALLOWED)) {
                    return false;
                }
            }
        }

        return true;
    }


/**
 * Toggle the setting for "Inherit Parent Space Permissions" on a specific node.
 *
 * @param string $uuid    Unique identifier for a node.
 * @param bool   $inherit Flag to indicate whether to set inheritance on or off.
 * @return bool True on success, False otherwise.
 */
    function node_inherit($uuid, $inherit) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('/moodle/nodeinherit/' . $uuid . '?enabled=' . ($inherit ? 'true' : 'false'));

        $response = elis_files_request('/moodle/nodeinherit/' . $uuid . '?enabled=' . ($inherit ? 'true' : 'false'));

        try {
            $sxml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            debugging(get_string('badxmlreturn', 'repository_elis_files') . "\n\n$response", DEBUG_DEVELOPER);
            return false;
        }

        if (empty($sxml->uuid) || empty($sxml->enabled)) {
            return false;
        }

        return ($sxml->uuid == $uuid && ($inherit && $sxml->enabled == 'true' || !$inherit && $sxml->enabled == 'false'));
    }


/**
 * Synchronize a Moodle user's permissions to Alfresco to ensure that the can access Moodle-specific
 * areas in Alfresco directly.
 *
 * @uses $CFG
 * @param object $user Moodle DB user record object
 * @return none
 */
    function sync_permissions($user) {
        global $CFG;

        // Presently, we are only checking for site-wide and shared repository space capabilities.
        $capabilities = array(
            'block/repository:createsitecontent',
            'block/repository:viewsitecontent',
            'block/repository:createsharedcontent',
            'block/repository:viewsharedcontent'
        );

        $sitecap  = false;
        $sharecap = false;

        $root = $this->get_root();

        foreach ($capabilities as $capability) {
            $sql = "SELECT ra.id
                    FROM {$CFG->prefix}role_assignments ra
                    INNER JOIN {$CFG->prefix}role_capabilities rc ON rc.roleid = ra.roleid
                    WHERE ra.userid = {$user->id}
                    AND rc.capability = '$capability'
                    AND rc.permission = " . CAP_ALLOW;

            if (record_exists_sql($sql)) {
                if ($capability == 'block/repository:createsitecontent') {
                    $this->allow_edit($user->username, $root->uuid);
                    $sitecap = true;

                } else if (!$sitecap && $capability == 'block/repository:viewsitecontent') {
                    $this->allow_read($user->username, $root->uuid);
                    $sitecap = true;

                } else if ($capability == 'block/repository:viewsitecontent') {
                    $this->allow_edit($user->username, $this->suuid);
                    $sharecap = true;

                } else if (!$sharecap && $capability == 'block/repository:createsharedcontent') {
                    $this->allow_read($user->username, $this->suuid);
                    $sharecap = true;
                }
            }
        }
    }


/**
 * Store the current UUID value that the user is browsing.
 *
 * @param string $uuid   A node UUID value.
 * @param int    $cid    A course record ID.
 * @param int    $uid    A user record ID.
 * @param bool   $shared A flag to indicate whether the user is currently located in the shared repository area.
 * @param bool   $clear  Set to true to clear out the previous location settings.
 * @return none
 */
    function set_repository_location($uuid, $cid, $uid, $shared, $clear = false) {
        global $USER;

        if ($clear) {
            unset($USER->elis_files_repository_location);
        }

        $location = new stdClass;
        $location->uuid   = $uuid;
        $location->cid    = $cid;
        $location->uid    = $uid;
        $location->shared = $shared;

        $USER->elis_files_repository_location = $location;
    }


/**
 * Get the previous UUID value that the user was browsing inside of and return it so long their current
 * context relates to the context of where that UUID is located.
 *
 * @uses $USER
 * @param int  $cid      A course record ID.
 * @param int  $uid      A user record ID.
 * @param bool $shared   A flag to indicate whether the user is currently located in the shared repository area.
 * @return string The UUID of the last location the user was browsing files in.
 */
    function get_repository_location(&$cid, &$uid, &$shared) {
        global $USER;
//print_object('$cid:    ' . $cid);
//print_object('$uid:    ' . $uid);
//print_object('$shared: ' . $shared);
        // If there was no previous location stored we have nothing to return.
        if (!isset($USER->elis_files_repository_location)) {
            return '';
        }

        $location = $USER->elis_files_repository_location;
//print_object($location);
        // If the previous value comes from within a course that is not the current course, return the root
        // storage value for the current course directory.
        if (!empty($location->uuid) && isset($location->cid) && ($location->cid != $cid) &&
            ($location->uid === 0) && ($location->shared == $shared) && ($location->uid == $uid)) {

            if ($cid === SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM);

                if (has_capability('block/repository:viewsitecontent', $context)) {
                    $root = $this->get_root();

                    if (!empty($root->uuid)) {
                        return $root->uuid;
                    }
                }
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $cid);

                if (has_capability('block/repository:viewcoursecontent', $context)) {
                    return $this->get_course_store($cid);
                }
            }
        }

        if (empty($location->uuid)) {
            // If we have explicity requested a user's home directory, make sure we return that
            if ((isset($location->cid) && $location->cid == $cid) &&
                (isset($location->uid) && ($location->uid != $uid) && ($uid === $USER->id)) &&
                empty($location->uuid)) {

                // Check for correct permissions
                $personalfiles = false;

                if (!empty($USER->access['rdef'])) {
                    foreach ($USER->access['rdef'] as $ucontext) {
                        if (isset($ucontext['block/repository:createowncontent']) &&
                            $ucontext['block/repository:createowncontent'] == CAP_ALLOW) {

                            $shared = '';
                            return $this->get_user_store($uid);
                        }
                    }
                }
            }

            // If we requested the shared repository location
            if ((isset($location->cid) && $location->cid == $cid) &&
                (isset($location->uid) && $location->uid == $uid) &&
                ((isset($location->shared) && ($location->shared != $shared) && ($shared == 'true')) ||
                (!isset($location->shared) && $shared == 'true'))) {

                if (!empty($USER->access['rdef'])) {
                    foreach ($USER->access['rdef'] as $ucontext) {
                        if (isset($ucontext['block/repository:viewsharedcontent']) &&
                                  $ucontext['block/repository:viewsharedcontent'] == CAP_ALLOW) {

                            $uid = 0;
                            return $this->suuid;
                        }
                    }
                }
            }
        }

        // Otherwise, we are using the same settings as the previous location, so ensure that the calling script
        // has those values.
        if (empty($uid)) {
            $cid = (isset($location->cid) && $location->cid == $cid ? $location->cid : $cid);
        }
        $uid    = ((!empty($location->uuid) && isset($location->uid)) ? $location->uid : $uid);
        $shared = ((!empty($location->uuid) && isset($location->shared)) ? $location->shared : $shared);

        return $location->uuid;
    }


/**
 * Get the default repository location.
 *
 * @uses $CFG
 * @uses $USER
 * @param int  $cid      A course record ID.
 * @param int  $uid      A user record ID.
 * @param bool $shared   A flag to indicate whether the user is currently located in the shared repository area.
 * @return string The UUID of the last location the user was browsing files in.
 */
    function get_default_browsing_location(&$cid, &$uid, &$shared) {
        global $CFG, $USER;

        // If the default location is set to the default value or not set at all, just return nothing now.
        if (!isset($CFG->repository_elis_files_default_browse) ||
            $CFG->repository_elis_files_default_browse == ELIS_FILES_BROWSE_MOODLE_FILES) {

            return '';

        // Or, handle determining if the user can actually access the chosen default location.
        } else if (isset($CFG->repository_elis_files_default_browse)) {
            if ($cid == SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM);
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $cid);
            }

            // If a user does not have permission to access the default location, fall through to the next
            // lower level to see if they can access that loation.
            switch ($CFG->repository_elis_files_default_browse) {
                case ELIS_FILES_BROWSE_ELIS_FILES_SITE_FILES:
                    if ($cid == SITEID && (has_capability('block/repository:viewsitecontent', $context) ||
                        has_capability('block/repository:createsitecontent', $context))) {

                        $root = $this->get_root();

                        if (!empty($root->uuid)) {
                            $shared = '';
                            $uid    = 0;

                            return $root->uuid;
                        }
                    }

                case ELIS_FILES_BROWSE_ELIS_FILES_SHARED_FILES:
                    if (!empty($USER->access['rdef'])) {
                        foreach ($USER->access['rdef'] as $rdef) {
                            if (isset($rdef['block/repository:viewsharedcontent']) &&
                                      $rdef['block/repository:viewsharedcontent'] == CAP_ALLOW ||
                                isset($rdef['block/repository:createsharedcontent']) &&
                                      $rdef['block/repository:createsharedcontent'] == CAP_ALLOW) {

                                $shared = 'true';
                                $uid    = 0;

                                return $this->suuid;
                            }
                        }
                    }

                case ELIS_FILES_BROWSE_ELIS_FILES_COURSE_FILES:
                    if ($cid != SITEID && (has_capability('block/repository:viewcoursecontent', $context) ||
                        has_capability('block/repository:createcoursecontent', $context))) {
                            $shared = '';
                            $uid    = 0;

                            return $this->get_course_store($cid);
                    }

                case ELIS_FILES_BROWSE_ELIS_FILES_USER_FILES:
                    if (empty($this->uuuid)) {
                        $this->uuuid = $this->elis_files_userdir($USER->username);
                    }
                    if (($uuid = $this->uuuid) !== false) {
                        $shared = '';
                        $uid    = $USER->id;

                        return $uuid;
                    }

                default:
                    return '';
            }
        }

        return '';
    }


/**
 * Cron functionality will poll the Alfresco DMS for a list of categories.
 *
 * @uses $CFG
 * @param none
 * @return none (status messages contained in the $log class variable)
 */
    function cron() {
        global $CFG, $DB;

        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

    /// See if it's time to execute the cron task.
        if (!empty($CFG->repository_elis_files_cron) &&
            ((time() - $CFG->repository_elis_files_cron) < ELIS_FILES_CRON_VALUE)) {

            return;
        }

    /// Execute the cron task.
        set_config('cron', time(), 'elis_files');

        $this->log = '';

        $this->log .= '   ' . get_string('startingalfrescocron', 'repository_elis_files');

        $categories = elis_files_get_categories();

        $this->log .= get_string('done', 'repository_elis_files') . "\n";
        $this->log .= '   ' . get_string('processingcategories', 'repository_elis_files');

        $uuids = array();
        $this->process_categories($uuids, $categories);

        if (!empty($uuids)) {
            $DB->delete_records_select('elis_files_categories', 'uuid NOT IN (\'' . implode('\', \'', $uuids) . '\')');
        }

        $this->log .= get_string('done', 'repository_elis_files') . "\n";

        // Migrate old-style user data to the new SSO system.
        if (isset($this->uuuid)) {
            $this->log .= '   ' . get_string('startingpartialusermigration', 'repository_elis_files');

            $this->migrate_all_users();

            $this->log .= get_string('done', 'repository_elis_files') . "\n";
        }

        if (ELIS_FILES_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("cron(): $time");
        }
    }
}

?>