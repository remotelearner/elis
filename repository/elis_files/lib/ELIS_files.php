<?php
/**
 * The DMS repository plug-in for using an external Alfresco DMS site.
 *
 * NOTE: Shamelessly "borrowed" from the enrolment plug-in structure located
 *       in the /enrol/ directory.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
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
 * @package    repository_elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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

/// Alfresco API v3.0
require_once dirname(__FILE__).'/lib.php';
// Alfresco 3.4
require_once(dirname(__FILE__).'/cmis-php/cmis_repository_wrapper.php');

define('ELIS_FILES_CRON_VALUE',  HOURSECS); // Run the cron job every hour.
define('ELIS_FILES_LOGIN_RESET', 5 * MINSECS);      // Reset a login after 5 minutes.

define('ELIS_FILES_DEBUG_TIME',  false);
define('ELIS_FILES_DEBUG_TRACE', false);

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
defined('ELIS_FILES_BROWSE_SITE_FILES')   or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
// Was shared, now called server files
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES')   or define('ELIS_FILES_BROWSE_USER_FILES',   50);
defined('ELIS_FILES_BROWSE_USERSET_FILES') or define('ELIS_FILES_BROWSE_USERSET_FILES', 60);

// Setup options for the method to transfer files into Alfresco from Moodle
defined('ELIS_FILES_XFER_WS')  || define('ELIS_FILES_XFER_WS', 'webservices');
defined('ELIS_FILES_XFER_FTP') || define('ELIS_FILES_XFER_FTP', 'ftp');


class ELIS_files {

    static $plugin_name          = 'repository/elis_files'; // TBD
    static $init                 = false; // Prevent recursion
    var $errormsg                = '';  // Standard error message varible.
    var $log                     = '';  // Cron task log messages.
    var $cmis                    = null;  // CMIS service connection object.
    var $muuid                   = '';  // Moodle root folder UUID
    var $suuid                   = '';  // Shared folder UUID
    var $cuuid                   = '';  // Top level Course folder UUID
    var $uuuid                   = '';  // User specific folder UUID
    var $uhomesuid               = ''; // Top level User Homes folder UUID
    var $ouuid                   = '';  // Top level usersets folder UUID
    var $root                    = '';  // Object representation of the root node
    var $config                  = '';  // Config object setting variables for Alfresco
    var $isrunning               = null;
    var $alfresco_username_fix   = ''; // The fixed username for Alfresco where @ is replaced with _AT_
    public static $version       = null; // Alfresco version
    public static $type_document = null;
    public static $type_folder   = null;

    function ELIS_files() {
        global $CFG, $USER, $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('ELIS_files()');

        $this->process_config(get_config('elis_files'));

        if (!$this->is_configured()) {
            return false;
        }

        if (!$this->is_running()) {
            return false;
        }

        if (!$this->get_defaults()) {
            return false;
        }

        $result = $this->verify_setup();
    }


/**
 * See if the plug-in is configured correctly.
 *
 * @param none
 * @return bool True if the plug-in has the minimum setup done, False otherwise.
 */
    function is_configured() {
        return (!empty($this->config->server_host) &&
                !empty($this->config->server_port) &&
                !empty($this->config->server_username) &&
                !empty($this->config->server_password));
    }


    /**
     * Detect whether or not the remove Alfreco repository is currently running.
     *
     * @param none
     * @return bool True if the remote system is running, False otherwise.
     */
    function is_running() {
        // TODO: This means that if Alfresco comes up during a user's login session, this won't be refelcted until they
        // log back into Moodle again. We probably want to add somethign here that will check after a certain amount of
        // time since the last check for an individual user.

        // Don't check if Alfresco is running if we have already checked once.
        if ($this->isrunning !== null) {
            return $this->isrunning;
        }

        if (empty(elis::$config->elis_files->server_host)) {
            $this->isrunning = false;
            return false;
        }

        $repourl = elis::$config->elis_files->server_host;

        if ($repourl[strlen($repourl) - 1] == '/') {
            $repourl = substr($repourl, 0, strlen($repourl) - 1);
        }

        if (!empty(elis::$config->elis_files->server_port)) {
            $repourl .= ':' . elis::$config->elis_files->server_port;
        }

        $repourl .= '/alfresco';

        // A list of valid HTTP response codes
        $validresponse = array(
            200,
            201,
            204,
            302,
            401,
            505
        );

        // Initialize the cURL session
        $session = curl_init($repourl);

        elis_files_set_curl_timeouts($session);

        // Execute the cURL call
        curl_exec($session);

        // Get the HTTP response code from our request
        $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

        curl_close($session);

        // Make sure the code is in the list of valid, acceptible codes
        if (array_search($httpcode, $validresponse)) {
            $this->isrunning = true;
            return true;
        }

        $this->isrunning = false;
        return false;
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
        global $CFG, $DB, $USER;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('verify_setup()');

        if (!$this->get_defaults()) {
            return false;
        }

        if (self::is_version('3.2')) {
            if (!elis_files_get_services()) {
                return false;
            }

            // Set up the root node
            $response = elis_files_request(elis_files_get_uri('', 'sites'));

            $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->loadXML($response);

            $nodes = $dom->getElementsByTagName('entry');
            $type  = '';

            $this->root = elis_files_process_node($dom, $nodes->item(0), $type);
        } else if (self::is_version('3.4')) {
            if (empty($this->cmis)) {
                $this->cmis = new CMISService(elis_files_base_url() . '/api/cmis',
                $this->config->server_username,
                $this->config->server_password);

                if (empty($this->cmis->authenticated)) {
                    return false;
                }

                if (!$root = $this->cmis->getObjectByPath('/')) {
                    return false;
                }

                $type = '';
                $this->root = elis_files_process_node_new($root, $type);
            }
        }


        // If there is no root folder saved or it's set to default,
        // make sure there is a default '/moodle' folder.
        if (empty($this->config->root_folder) ||
            ($this->config->root_folder == '/moodle')) {

            $root = $this->get_root();
            if ($root == false || !isset($root->uuid)) {
                return false;
            }

            $dir = $this->read_dir($root->uuid, true);

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
            if (!$uuid = elis_files_uuid_from_path($this->config->root_folder)) {
                debugging(get_string('invalidpath', 'repository_elis_files'));
                return false;
            }

            $this->muuid = $uuid;
            $this->node_inherit($uuid, false);
        }

        // Attempt to find the UUID of the main storage folders within the "moodle" folder.
        $dir = $this->read_dir($this->muuid, true);

        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == 'shared') {
                    $this->suuid = $folder->uuid;
                } else if ($folder->title == 'course') {
                    $this->cuuid = $folder->uuid;
                } else if ($folder->title == 'userset') {
                    $this->ouuid = $folder->uuid;
                }
            }
        }

        // Attemping to find the UUID of any storage folders within the top-level folder.
        $dir = $this->read_dir($this->root->uuid);

        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == 'User Homes') {
                    $this->uhomesuid = $folder->uuid;
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

        // Create the userset shared storage directory.
        if (empty($this->ouuid)) {
            $ouuid = $this->create_dir('userset', $this->muuid, true);

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

        if ($username = $DB->get_field('user', 'username', array('id' => $USER->id))) {
            //mtrace("ELIS_files::verify_setup(): username = {$username}\n");
            $this->uuuid = elis_files_get_home_directory($username);
            //error_log("verify_setup:: elis_files_get_home_directory({$username}) = {$this->uuuid}");
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
       return $this->root;
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

        // Set the config object to what was retrieved from get_config
        $this->config = $config;

        return true;
    }


/**
 * Get the URL used to connect to the repository.
 *

 * @return string The connection URL.
 */
    function get_repourl() {
        //global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_repourl()');

        $repourl = elis::$config->server_host;

        if ($repourl[strlen($repourl) - 1] == '/') {
            $repourl = substr($repourl, 0, strlen($repourl) - 1);
        }

        return $repourl . ':' . $this->config->server_port . '/alfresco/s/api';
    }


/**
 * Get the URL that allows for direct access to the web application.
 *
 * @param bool $gotologin Set to False to not give a URL directly to the login form.
 * @return string A URL for accessing the Alfresco web application.
 */
    function get_webapp_url($gotologin = true) {
        //global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_webapp_url()');

        return $this->config->server_host . ':' . $this->config->server_port . '/alfresco/' .
               ($gotologin ? 'faces/jsp/login.jsp' : '');
    }


/**
 * Get a WebDAV-specific URL for the repository.
 *

 * @param none
 * @return string A WebDAV-specific URL.
 */
    function get_webdav_url() {
       // global $CFG;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_webdav_url()');

        return $this->config->server_host . ':' . $this->config->server_port . '/alfresco/webdav/';
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
     * @return object|bool A node object or false on error.
     */
    function get_info($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_info(' . $uuid . ')');

        if (!$this->get_defaults()) {
            return false;
        }

        if (self::is_version('3.2')) {
            $node = elis_files_node_properties($uuid);
        } else if (self::is_version('3.4')) {
            //Check that the uuid is valid before trying to get the object
            if (!elis_files_request(elis_files_get_uri($uuid, 'self'))) {
                return false;
            }
            if (!$node = $this->cmis->getObject('workspace://SpacesStore/' . $uuid)) {
                return false;
            }

            $type = '';
            $node = elis_files_process_node_new($node, $type);
            $node->type = $type;
        }

        // ELIS-5750: the following requires the updated webscript: nodeowner.get.js
        if (!empty($node->uuid) && strpos($node->type, 'document') !== false && ($response = elis_files_request('/moodle/nodeowner/'.$node->uuid))
                && ($sxml = RLsimpleXMLelement($response)) && !empty($sxml->owner)) {
            foreach ((array)$sxml->owner AS $val) {
                // error_log("nodeOwner: {$val}");
                $node->owner = $val; // ($val != 'admin') ? $val : $this->config->admin_username;
                break;
            }
        }
        return $node;
    }

/**
 * Is a given node a directory?
 *
 * @param string $uuid A node UUID value.
 * @return bool True if
 */
    function is_dir($uuid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('is_dir(' . $uuid . ')');

        // Set the file and folder type
        if (!$this->get_defaults()) {
            return false;
        }

        return (elis_files_get_type($uuid) == self::$type_folder);
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

        if (!isset($node->noderef) || !($nodecats = elis_files_get_node_categories($node->noderef)) ) {
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

        if (!$this->get_defaults()) {
            return false;
        }

        if (self::is_version('3.2')) {
            return elis_files_read_dir($uuid, $useadmin);
        } else if (self::is_version('3.4')) {
            if (empty($uuid)) {
                if ($root = $this->get_root()) {
                    $uuid = $root->uuid;
                }
            }

            if (!($result = $this->cmis->getChildren('workspace://SpacesStore/' . $uuid))) {
                return false;
            }

            $return = new stdClass;
            $return->folders = array();
            $return->files   = array();

            foreach ($result->objectsById as $child) {
                $type = '';

                $node =  elis_files_process_node_new($child, $type);

                if ($type == self::$type_folder) {
                    $return->folders[] = $node;
                    // Only include a file in the list if it's title does not start with a period '.'
                    } else if ($type == self::$type_document && !empty($node->title) && $node->title[0] !== '.') {
                    $return->files[] = $node;
                }
            }

            return $return;
        }
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
 * @param bool   $attachment true if desired as attachment, false(default) for file
 * @return mixed|bool Returns the file to the browser (with appropriate MIME headers) or False on failure.
 */
    function read_file($uuid, $localfile = '', $return = false, $process = true, $attachment = false) {
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

        if (!($node = $this->get_info($uuid))) {
            return false;
        }

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
                if (!empty($this->config->cache_time)) {
                    $age = $this->config->cache_time;
                } else {
                /// The "No" caching option has a value of zero, so
                /// this handles the default (not set), as well as selecting "No"
                    $age = 0;
                }

                header('Cache-Control: max-age=' . $age);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $age) . ' GMT');
                header('Pragma: ');

                $cd_header = 'Content-Disposition: ';
                if ($attachment) {
                    $cd_header .= 'attachment; ';
                }
                header($cd_header .'filename="'. $node->filename .'"');
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
        if (!empty($this->config->cache_time)) {
            $age = $this->config->cache_time;
        } else {
        /// The "No" caching option has a value of zero, so
        /// this handles the default (not set), as well as selecting "No"
            $age = 0;
        }

        header('Cache-Control: max-age=' . $age);
        header('Expires: '. gmdate('D, d M Y H:i:s', time() + $age) .' GMT');
        header('Pragma: ');

        $cd_header = 'Content-Disposition: ';
        if ($attachment) {
            $cd_header .= 'attachment; ';
        }
        header($cd_header .'filename="'. $node->filename .'"');

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
                    $changes[$osrc] = $this->get_openfile_link($uuid);
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

        // TODO: Remove this method as it seems that it is not necessary anymore?
        return false;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('upload_file(' . $upload . ', ' . $path . ', ' . $uuid . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

        require_once($CFG->libdir . '/filelib.php');

       if (self::is_version('3.2')) {
            if ($node = elis_files_upload_file($upload, $path, $uuid)) {
                if (ELIS_FILES_DEBUG_TIME) {
                    $end  = microtime(true);
                    $time = $end - $start;
                    mtrace("upload_file('$upload', '$path', '$uuid'): $time");
                }
                return $node->uuid;
            }

            return false;
        } else if (self::is_version('3.4')) {
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
            $properties = elis_files_process_node($dom, $nodes->item(0), $type);

            // Ensure that we set the current user to be the owner of the newly created directory.
            if (!empty($properties->uuid)) {
                $username = elis_files_transform_username($USER->username);

                // We're not going to check the response for this right now.
                elis_files_request('/moodle/nodeowner/' . $properties->uuid . '?username=' . $username);
            }

            return $properties;
        }
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

        if (!$this->get_defaults()) {
            return false;
        }
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
                if (!elis_files_upload_file('', $path . $file, $uuid)) {
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
        static $within = false;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('download_dir(' . $path . ', ' . $uuid . ', ' . $recurse . ')');
        if (ELIS_FILES_DEBUG_TIME) $start = microtime(true);

    /// Make sure the local path is a directory.
        if (!is_dir($path)) {
            return false;
        }

        if (!($node = $this->get_info($uuid))) {
            return false;
        }

    /// Make sure we've been pointed to a directory
        if (!$this->is_dir($node->uuid)) {
            return false;
        }

        $filelist = array();

        $path .= ($path[strlen($path) - 1] != '/' ? '/' : '');
        $npath = $path;
        if (!$within) {
            $within = true;
            $npath .= $node->title . '/';
            if (!is_dir($npath)) {
                mkdir($npath, $CFG->directorypermissions, true);
            }
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

        if (!($node = $this->get_info($uuid))) {
            return false;
        }

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

        // ELIS-6920 Remove invalid characters that can't be in a node's title property
        $strip_chars = array( '&', '*', '\\', '|', ':', '"', '/', '?', '`', '~', '!', '@');
        $name = str_replace($strip_chars, ' ', $name);
        $name = trim($name); // Trim whitespace from the end of the folder name

        if (self::is_version('3.2')) {
            if ($node = elis_files_create_dir($name, $uuid, $description, $useadmin)) {
                if (ELIS_FILES_DEBUG_TIME) {
                    $end  = microtime(true);
                    $time = $end - $start;
                    mtrace("create_dir('$name', '$uuid', '$description'): $time");
                }
                // was $node->uuid
                return $node->uuid;
            }
        } else if (self::is_version('3.4')) {
            if ($return = $this->cmis->createFolder('workspace://SpacesStore/' . $uuid, $name)) {
                $type = '';
                $node =  elis_files_process_node_new($return, $type);

                return $node->uuid;
            }
        }

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

        if (!$this->get_defaults()) {
            return false;
        }

        // Ensure that we set the configured admin user to be the owner of the deleted file before deleting.
        // This is to prevent the user's Alfresco account from having space incorrectly attributed to it.
        // ELIS-1102
        elis_files_request('/moodle/nodeowner/' . $uuid . '?username=' . elis::$config->elis_files->server_username);

        if (self::is_version('3.2')) {
            return (true === elis_files_send(elis_files_get_uri($uuid, 'delete'), array(), 'DELETE'));
        } else if (self::is_version('3.4')) {
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

        if (!isset($this->cuuid)) {
            return false;
        }

        // Attempt to get the store UUID value
        if (($uuid = $DB->get_field('elis_files_course_store', 'uuid', array('courseid'=> $cid))) !== false) {
            // Verify that the folder still exists and if not, delete the DB record so we can create a new one below.
            if ($this->get_info($uuid) === false) {
                $DB->delete_records('elis_files_course_store', array('courseid'=> $cid));
                $uuid = false;
            } else {
                return $uuid;
            }
        }

        $dir = $this->read_dir($this->cuuid);

        if (!$course = $DB->get_record('course', array('id'=> $cid), 'shortname, fullname')) {
            return false;
        }

        // Look at all of the course directories that exist for our course ID.
        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == $course->shortname) {
                    $uuid = $folder->uuid;
                    break;
                }
            }
        }

        // If we're allowed to create a course store, do so now.
        if ($create && empty($uuid) && ($uuid = $this->create_dir($course->shortname, $this->cuuid, $course->fullname))) {
            // Disable inheriting parent space permissions.  This can be disabled in Alfresco without being
            // reset by the code elsewhere.
            $this->node_inherit($uuid, false);
        }

        if (!empty($uuid)) {
            // Store the UUID value if it hasn't been stored already.
            if (!$DB->record_exists('elis_files_course_store', array('courseid'=> $cid))) {
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
 * @param int  $oid    The userset ID.
 * @param bool $create Set to false to not automatically create an userset storage area.
 * @return string|bool The course store UUID value or, False on error.
 */
    function get_userset_store($oid, $create = true) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_userset_store(' . $oid . ')');

        $uuid = '';

        if (!isset($this->ouuid)) {
            return false;
        }

        // Attempt to get the store UUID value
        if (($uuid = $DB->get_field('elis_files_userset_store', 'uuid', array('usersetid'=> $oid))) !== false) {
            // Verify that the folder still exists and if not, delete the DB record so we can create a new one below.
            if ($this->get_info($uuid) === false) {
                $DB->delete_records('elis_files_userset_store', array('usersetid'=> $oid));
                $uuid = false;
            } else {
                return $uuid;
            }
        } else if (!$create) {
            return false;
        }

        // Make sure that the optional table we are about to check actually exists.
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('crlm_cluster')) {
            return false;
        }

        if (!$userset = $DB->get_record('crlm_cluster', array('id'=> $oid))) {
            return false;
        }

        $dir = $this->read_dir($this->ouuid);

        // Look at all of the userset directories that exist for our userset ID.
        if (!empty($dir->folders)) {
            foreach ($dir->folders as $folder) {
                if ($folder->title == $userset->name) {
                    $uuid = $folder->uuid;
                    break;
                }
            }
        }

        // If we've forced it off, don't automatically create a course storage directory at this point.
        if (!$create) {
            return false;
        }

        // Create directory only if it doesn't already exist
        if (empty($uuid) && ($uuid = $this->create_dir($userset->name, $this->ouuid, $userset->display))) {
            // Disable inheriting parent space permissions.  This can be disabled in Alfresco without being
            // reset by the code elsewhere.
            $this->node_inherit($uuid, false);
        }

        if (!empty($uuid)) {
            // Store the UUID value if it hasn't been stored already.
            if (!$DB->record_exists('elis_files_userset_store', array('usersetid'=> $oid))) {
                $usersetstore = new stdClass;
                $usersetstore->usersetid = $oid;
                $usersetstore->uuid      = $uuid;
                $usersetstore->id        = $DB->insert_record('elis_files_userset_store', $usersetstore);
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
        if (ELIS_FILES_DEBUG_TRACE) mtrace('has_old_user_store(' . $uid . ')');

        if (empty($this->uuuid)) {
            return false;
        }

        $dir = $this->read_dir($this->uuuid, true);

        // Look at all of the course directories that exist for our user ID.
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
                //error_log("ELIS_files::get_user_store({$uid}) => NO username => false!");
                return false;
            }

            $uuid = false;

            if (($uuid = $this->has_old_user_store($uid)) !== false) {
                $fixed_username = $this->fix_username($username);
                if (!$this->migrate_user($fixed_username)) {
                    //error_log("ELIS_files::get_user_store({$uid}) => NO migrate_user => false!");
                    return false;
                }
            }

            if (empty($this->uuuid)) {
                $this->uuuid = $this->elis_files_userdir($username);
                //error_log("ELIS_files::get_user_store({$uid}) => WAS empty => {$this->uuuid}");
            }
            //error_log("ELIS_files::get_user_store({$uid}) => {$this->uuuid}");
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

        if (!$this->get_defaults()) {
            return false;
        }

        if (self::is_version('3.2')) {
            return elis_files_get_parent($uuid);
        } else if (self::is_version('3.4')) {
            if ($uuid == $this->root->uuid) {
                // At the top level, so there is no parent
                return false;
            }

            if (!$node = $this->cmis->getFolderParent('workspace://SpacesStore/' . $uuid)) {
                return false;
            }

            $type = '';
            return elis_files_process_node_new($node, $type);
        }
    }


/**
 * Get navigation breadcrumbs for the current position in a repository file browser. <= NOT USED
 *
 * @param string $uuid     Unique identifier for a node.
 * @param int    $cid      The course ID (optional).
 * @param int    $uid      The user ID (optional).
 * @param int    $shared   Set to true if this is for the shared storage area (optional).
 * @param int    $oid      The cluster ID (optional).
 * @return array|bool An array of navigation link information or, False on error.
 */
    function get_nav_breadcrumbs($uuid, $cid, $uid, $shared, $oid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace('get_nav_breadcrumbs(' . $uuid . ', ' . $cid . ', ' . $uid . ', ' . $shared . ', ' . $oid . ')');

    /// Get the "ending" UUID for the 'root' of navigation.
        if ((empty($cid) || $cid == SITEID) && empty($uid) && empty($shared) && empty($oid)) {
            $end   = $this->get_root()->uuid;
        } else if (empty($uid) && $shared == true) {
            $end = $this->suuid;
        } else if (empty($uid) && empty($oid) && empty($shared) && !empty($cid) && $cid != SITEID) {
            $end = $this->get_course_store($cid);
        } else if (!empty($uid)) {
            $end = $this->get_user_store($uid);
        } else if (empty($uid) && !empty($oid)) {
            $end = $this->get_userset_store($oid);
        }

        $stack = array();

        if (!($node = $this->get_info($uuid))) {
            return false;
        }

        if ($node->type == self::$type_folder) {
            // Include shared and oid parameters
            $params = array('path'=>$node->uuid,
                            'shared'=>(boolean)$shared,
                            'oid'=>(int)$oid,
                            'cid'=>(int)$cid,
                            'uid'=>(int)$uid);
            $encodedpath = base64_encode(serialize($params));
            $nav = array(
                'name' => $node->title,
                'path' => $encodedpath
                );

                array_push($stack, $nav);

        } else if ($node->type != self::$type_document) {
            return false;
        }

        if ($node->uuid == $end) {
            return false;
        }

        if (!$parent = $this->get_parent($node->uuid)) {
            return false;
        }

        while (!empty($parent->uuid) && $parent->uuid != $end) {
            // Include shared and oid parameters
            $params = array('path'=>$parent->uuid,
                            'shared'=>(boolean)$shared,
                            'oid'=>(int)$oid,
                            'cid'=>(int)$cid,
                            'uid'=>(int)$uid);
            $encodedpath = base64_encode(serialize($params));
            $nav = array(
                'name' => $parent->title,
                'path' => $encodedpath
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

        if (!$this->get_defaults()) {
            return false;
        }

        $stack = array();

        if (($node = $this->get_info($uuid)) === false) {
            print_error('couldnotgetnodeproperties', 'repository_elis_files', '', $uuid);
            return false;
        }

        if ($this->is_company_home($node->uuid)) {
            //this is the root "Company Home" node
            return '/';
        }

        if ($node->type == self::$type_folder) {
            array_push($stack, $node->title);
        } else if ($node->type !== self::$type_document) {
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

   /*
    * Check if a give node is in the parent path
    *
    * @param    string  $uuid           The Unique identifier for a node
    * @param    string  $compareduuid   Unique identifier for a node to compare against
    * @return   bool                    True if the node is in the parent path, otherwise, false
    */
    function match_uuid_path($uuid, $compareduuid, $result) {
        if (is_array($result)) {
            foreach ($result as $paths) {
                $path = unserialize(base64_decode($paths['path']));
                if ($path['path'] == $compareduuid) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
    * Check for valid permissions given the storage context for a file, also taking
    * into account the location where the file was included within Moodle.
    *
    * @uses $CFG
    * @uses $USER
    * @param string $uuid   Unique identifier for a node.
    * @param int    $uid    The user ID (optional).
    * @param bool   $useurl Check the referring URL for Moodle-based permissions (default: true).
    * @param object $repo   ELIS Files repo object to use (only for unit testing)
    * @return bool True if the user has permission to access the file, False otherwise.
    */
    function permission_check($uuid, $uid = 0, $useurl = true, $repo = NULL) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot .'/repository/elis_files/lib.php');

//        error_log("/repository/elis_files/lib/lib.php::permission_check({$uuid}, {$uid}, {$useurl})");
        if (ELIS_FILES_DEBUG_TRACE) mtrace('permission_check(' . $uuid . ', ' . $uid . ', ' .
                                         ($useurl === true ? 'true' : 'false') . ')');

        if ($repo === NULL) {
            $repo = new repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                    array('ajax' => false, 'type' => 'elis_files'));
        }

        $repo->get_parent_path($uuid, $result, 0, 0, 0, 0);


        if (!empty($this->config->root_folder)) {
            $moodleroot = $this->config->root_folder;
        } else {
            $moodleroot = '/moodle';
        }
        // get the flags for uid, cid, oid and shared
        $uid = 0;
        $cid = 0;
        $oid = 0;
        $shared = false;
        $parent_node = $this->get_parent($uuid);
        $prev_node = $this->get_info($uuid);

        do {
            $check_uuid = !empty($parent_node->uuid) ? $parent_node->uuid : 0;
            $folder_name = !empty($prev_node->title)
                           ? $prev_node->title : '';
            if ($check_uuid == $this->cuuid) {
                $cid = $DB->get_field('elis_files_course_store',
                                      'courseid',
                                      array('uuid' => $prev_node->uuid));
            } else if ($check_uuid == $this->ouuid) {
                $oid = $DB->get_field('elis_files_userset_store',
                                      'usersetid',
                                      array('uuid' => $prev_node->uuid));
            } else if ($check_uuid == $this->suuid) {
                $shared = true;
            } else if (!empty($prev_node->uuid) &&
                       $prev_node->uuid == $this->uuuid) {
                $uid = elis_files_folder_to_userid($folder_name);
            }
            $prev_node = $parent_node;
        } while (!$uid && !$cid && !$oid && !$shared &&
                 ($parent_node = $this->get_parent($check_uuid))
                 && !empty($parent_node->uuid));

        if (!empty($cid)) {
            $cid = $DB->get_field('course', 'id', array('id' => $cid), IGNORE_MULTIPLE);

            // This is a server file.
            if ($cid == SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM);
            }

            // This is a course file.
            if (!empty($cid)) {
                $context = get_context_instance(CONTEXT_COURSE, $cid);
            }
        }

        // This is a shared file.
        if ($shared) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        }

        // This is a user file.
        if (!empty($uid)) {
            $info = $this->get_info($this->uuuid);
            if (isset($info->title)) {
                $username  = str_replace('_AT_', '@', $info->title);
                //error_log("preg_match('/\/User\sHomes\/([-_a-zA-Z0-9\s]+)\//', {$path}, = {$tmp}) => username = {$username}");
                $context = get_context_instance(CONTEXT_SYSTEM);
            }
        }

        /// This is a userset file.
        if (!empty($oid)) {
            $cluster_context = context_elis_userset::instance($oid);

        }

    /// Default to the root of the Alfresco repository (requires system-level repository access).
        if (!isset($context)) {
            $context = context_system::instance();
        }

    /// Attempt to determine where the file open request came from to determine if the current user
    /// has permission to access that file in Moodle (this overrides the current user's Alfresco
    /// permissions.
        $referer = $this->get_referer();
        if ($useurl && !empty($referer)) {
            $fromplugin  = strpos($referer, $CFG->wwwroot.'/pluginfile.php') !== false;
            $frommodule  = strpos($referer, $CFG->wwwroot . '/mod/') !== false;
            $fromblock   = strpos($referer, $CFG->wwwroot . '/blocks/') !== false;
            $frommodedit = strpos($referer, '/course/modedit.php') !== false;
            $fromeditor  = strpos($referer, '/lib/editor/htmlarea/blank.html') !== false;
            $fromcourse  = strpos($referer, '/course/view.php') !== false;
            $fromsite    = ($referer == $CFG->wwwroot . '/' || $referer == $CFG->wwwroot ||
                            $referer == $CFG->wwwroot . '/index.php');

            // error_log("ELIS_files::permissions_check(): fromplugin={$fromplugin}, frommodule={$frommodule}, fromblock={$fromblock}"
            //         .", frommodedit={$frommodedit}, fromeditor={$fromeditor}, fromcourse={$fromcourse}, fromsite={$fromsite}");

        /// If this access is coming from something inside of the mod or blocks directory, then allow access.
            if ($frommodule || $fromblock || $fromeditor) {
                return true;
            }

            if (!empty($referer) && ($frommodedit || $fromcourse || $fromsite || $fromplugin)) {
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

                } else if ($fromplugin) {
                    // Look for module contextid
                    preg_match('/pluginfile.php\/([0-9]+)\/mod/', $referer, $matches);
                    if (count($matches) != 2 && !$CFG->slasharguments) {
                        // Look for 'file' url parameter in referer
                        preg_match('/file=\/([0-9]+)\/mod/', $referer, $matches);
                    }

                    if (count($matches) == 2 && ($instanceid = $DB->get_field('context', 'instanceid', array('id' => $matches[1]))) !== false
                            && ($cm = $DB->get_record('course_modules', array('id' => $instanceid)))) {
                        // error_log("ELIS_files::permissions_check(): pluginfile instanceid = {$instanceid}");
                        require_login($cm->course, false, $cm, false);
                    }
                }
            }

    /// This file didn't come from somewhere within Moodle that we know about so access has
    /// to be determined based on the Alfresco capabilities the current user has.
        } else {
            // Get the non context based permissions
            $capabilities = array(
                'repository/elis_files:viewowncontent'      => false,
                'repository/elis_files:createowncontent'    => false,
                'repository/elis_files:viewsharedcontent'   => false,
                'repository/elis_files:createsharedcontent' => false
            );
            $this->get_other_capabilities($USER, $capabilities);

            // Determine if the user has "site files" permissions
            $syscontext = context_system::instance();
            $allowsitefiles = has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                              has_capability('repository/elis_files:createsitecontent', $syscontext);

            if ($uid) {
            /// If the current user is not the user who owns this file and we can't access anything in the
            /// repository, don't allow access.
                if ($USER->username != $username && !has_capability('repository/elis_files:viewsitecontent', $context)) {
                    return false;
                }

            /// This repository location is not tied to a specific Moodle context, so we need to look for the
            /// specific capability anywhere within the user's role assignments.
                $hascap = $allowsitefiles ||
                          $capabilities['repository/elis_files:viewowncontent'] ||
                          $capabilities['repository/elis_files:createowncontent'];
                if (!$hascap) {
                    return false;
                }

            } else if ($cid) {
            /// This file belongs to a course, make sure the current user can access that course's repository
            /// content.
                $hascap = $allowsitefiles ||
                          has_capability('repository/elis_files:viewcoursecontent', $context) ||
                          has_capability('repository/elis_files:createcoursecontent', $context);
                if (!$hascap) {
                    return false;
                }

            } else if ($oid) {
            /// This file belongs to a course, make sure the current user can access that course's repository
            /// content.
                $hascap = $allowsitefiles ||
                          has_capability('repository/elis_files:viewusersetcontent', $cluster_context) ||
                          has_capability('repository/elis_files:createusersetcontent', $cluster_context);
                if (!$hascap) {
                    return false;
                }

            } else if ($shared) {
            /// This repository location is not tied to a specific Moodle context, so we need to look for the
            /// specific capability anywhere within the user's role assignments.
                $hascap = $allowsitefiles ||
                          $capabilities['repository/elis_files:viewsharedcontent'] ||
                          $capabilities['repository/elis_files:createsharedcontent'];

                if (!$hascap) {
                    return false;
                }
            } else {
            /// This file is not part of a standard Moodle storage area thus requiring full repository access.
                if (!$allowsitefiles) {
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

        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_get_parent(' . $catid . ')');

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
    static function category_get_children($catid) {
        global $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('category_get_children(' . $catid . ')');

        $cats = array();
        if ($children = $DB->get_records('elis_files_categories', array('parent'=> $catid))) {
            foreach ($children as $child) {
                // html encode special characters and single quotes for tree menu
                $child->title = htmlspecialchars($child->title,ENT_QUOTES);
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
                $cat->title  = $category['name'];
                $cat->id     = $DB->insert_record('elis_files_categories', $cat);
            } else {
                $cat->parent = $parent;
                $cat->uuid   = $category['uuid'];
                $cat->path   = !empty($classification->category->id->path) ?
                               $classification->category->id->path : '';
                $cat->title  = $category['name'];

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

            $text = ' <a href="#" onclick="set_value(\\\'' . $npath . '\\\')" title="' . $npath . '">' . $folder['name'] .
                    (!empty(elis::$config->elis_files->root_folder) && elis::$config->elis_files->root_folder == $npath ?
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
 * @uses $USER, $COURSE
 * @param int    $cid         A course record ID.
 * @param int    $uid         A user record ID.
 * @param bool   $shared      Shared/server flag set to TRUE if browsing in this area.
 * @param int    $oid         A cluster record ID.
 * @param array  $opts        Array of available options.
 * @param bool   $createonly  Flag that, if set to true, only returns a list of options with create capability
 * @return array An array of options meant to be used in a popup_form() function call.
 */
    function file_browse_options($cid, $uid, $shared, $oid, &$opts=array(), $createonly = false) {
        global $USER, $COURSE;

        $uuid = ''; // ELIS-7453: avoid undefined variable $uuid error
        //Check defaults
        $opts = array();

        if (empty($cid) || $cid == SITEID) {
            // $COURSE is no longer set correctly
            $referer = $this->get_referer();
            if (!empty($referer) && ($crspos = strpos($referer, 'course=')) !== false) {
                $crs = substr($referer, $crspos + strlen('course='));
                // error_log("file_browse_options(): crs = {$crs}");
                $cid = intval($crs);
            } else {
                $cid = $COURSE->id;
            }
        }
        if (empty($cid)) {
            $cid = SITEID;
        }
        // error_log("file_browse_options(): cid = {$cid}");
        if (empty($uid)) {
            $uid = $USER->id;
        }

        if ($cid == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $cid);
        }

        // Determine if the user has access to their own personal file storage area.
        $editalfsite           = has_capability('repository/elis_files:createsitecontent', $context);
        $viewalfsite           = has_capability('repository/elis_files:viewsitecontent', $context);
        $editalfcourse         = has_capability('repository/elis_files:createcoursecontent', $context);
        $viewalfcourse         = has_capability('repository/elis_files:viewcoursecontent', $context);

        // Get the non context based permissions
        $capabilities = array(
            'repository/elis_files:viewowncontent'      => false,
            'repository/elis_files:createowncontent'    => false,
            'repository/elis_files:viewsharedcontent'   => false,
            'repository/elis_files:createsharedcontent' => false
        );

        $this->get_other_capabilities($USER, $capabilities);

        // Build the option for browsing from the repository userset / course / site files.
        if ($cid == SITEID && ($viewalfsite || $editalfsite)) {
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
            if (!$createonly || ($createonly && $editalfsite)) {
                $params = array('path'=>$uuid,
                                'shared'=>(boolean)0,
                                'oid'=>0,
                                'cid'=>(int)$cid,
                                'uid'=>0);
                $encodedpath = base64_encode(serialize($params));

                // Calculate "unbiased" parameters, that use the default flag values
                // for comparrison with paths on the Javascript side
                $unbiasedparams = array(
                    'path'   => $uuid,
                    'shared' => false,
                    'oid'    => 0,
                    'cid'    => 0,
                    'uid'    => 0
                );
                $unbiasedpath = base64_encode(serialize($unbiasedparams));

                $opts[] = array('name'=> get_string('repositorysitefiles','repository_elis_files'),
                                'path'=> $encodedpath,
                                'unbiasedpath' => $unbiasedpath);
            }

        } else if ($cid != SITEID && ($viewalfcourse || $viewalfsite || $editalfsite)) {
            if (!elis_files_has_permission($this->cuuid, $USER->username)) {
                $this->allow_read($USER->username, $this->cuuid);
            }

            if ($cid != SITEID && ($uuid = $this->get_course_store($cid)) !== false) {
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
            } else {
                // No course available... just link to course folder?
                $uuid = $this->cuuid;
            }

            if (!$createonly || ($createonly && $editalfcourse)) {
                $params = array('path'=>$uuid,
                                'shared'=>(boolean)0,
                                'oid'=>0,
                                'cid'=>(int)$cid,
                                'uid'=>0);
                $encodedpath = base64_encode(serialize($params));

                // Calculate "unbiased" parameters, that use the default flag values
                // for comparrison with paths on the Javascript side
                $unbiasedparams = array(
                    'path'   => $uuid,
                    'shared' => false,
                    'oid'    => 0,
                    'cid'    => 0,
                    'uid'    => 0
                );
                $unbiasedpath = base64_encode(serialize($unbiasedparams));
                $opts[] = array('name'=> get_string('repositorycoursefiles','repository_elis_files'),
                                'path'=> $encodedpath,
                                'unbiasedpath' => $unbiasedpath);
            }
        }

        // Build the option for browsing from the repository shared files.
        $allowshared = $viewalfsite ||
                       $editalfsite ||
                       $capabilities['repository/elis_files:viewsharedcontent'];

        if ($allowshared) {
            if (!elis_files_has_permission($this->suuid, $USER->username)) {
                $this->allow_read($USER->username, $this->suuid);
            }

            if ($capabilities['repository/elis_files:createsharedcontent'] == true) {
                if (!elis_files_has_permission($this->suuid, $USER->username, true)) {
                    $this->allow_edit($USER->username, $this->suuid);
                }
            } else {
                if (!elis_files_has_permission($this->suuid, $USER->username)) {
                    $this->allow_read($USER->username, $this->suuid);
                }
            }
            if (!$createonly || ($createonly && ($capabilities['repository/elis_files:createsharedcontent'] == true))) {
                $params = array('path'=>$this->suuid,
                                'shared'=>true,
                                'oid'=>(int)0,
                                'cid'=>(int)0,
                                'uid'=>(int)0);
                $encodedpath = base64_encode(serialize($params));

                // Calculate "unbiased" parameters, that use the default flag values
                // for comparrison with paths on the Javascript side
                $unbiasedparams = array(
                    'path'   => $this->suuid,
                    'shared' => false,
                    'oid'    => 0,
                    'cid'    => 0,
                    'uid'    => 0
                );
                $unbiasedpath = base64_encode(serialize($unbiasedparams));

                $opts[] = array('name'=> get_string('repositoryserverfiles','repository_elis_files'),
                                'path'=> $encodedpath,
                                'unbiasedpath'=> $unbiasedpath);
            }
        }

        // Build the option for browsing from the repository personal / user files.
        $allowpersonal = $viewalfsite ||
                         $editalfsite ||
                         $capabilities['repository/elis_files:viewowncontent'];

        if ($allowpersonal) {
            if (!$createonly || ($createonly && ($capabilities['repository/elis_files:createowncontent'] == true))) {
                //error_log("ELIS_files::file_browse_options(): this->get_user_store({$USER->id}) = ". $this->get_user_store($USER->id));
                $params = array('path'=>$this->get_user_store($USER->id),
                                'shared'=>(boolean)0,
                                'oid'=>(int)0,
                                'cid'=>(int)0,
                                'uid'=>(int)$USER->id);
                $encodedpath = base64_encode(serialize($params));

                // Calculate "unbiased" parameters, that use the default flag values
                // for comparrison with paths on the Javascript side
                $unbiasedparams = array(
                    'path'   => $this->get_user_store($USER->id),
                    'shared' => false,
                    'oid'    => 0,
                    'cid'    => 0,
                    'uid'    => 0
                );
                $unbiasedpath = base64_encode(serialize($unbiasedparams));

                $opts[] = array('name'=> get_string('repositoryuserfiles','repository_elis_files'),
                                'path'=> $encodedpath,
                                'unbiasedpath'=>$unbiasedpath);
            }
        }

        // Get usersets folders to which the users belongs
        $this->find_userset_folders($opts, $createonly);

        // Assemble the default menu selection based on the information given to this method.
//        $default = $origurl . 'id=' . $cid . (!empty($oid) ? '&amp;oid='.$oid : '') . (!empty($shared) ? '&amp;shared=true' : '') . '&amp;userid=' .
//                   ($editalfpersonal || $viewalfpersonal ? $uid : '0') . '&amp;choose=' . $choose;
        // Do we need to 'select' default... hmmm...

        return $opts;
    }

 /**
 * Find a list of userset folders that the user has access to.
 *
 * @param $CFG
 * @param array $opts      The current drop down list to which to add userset folders
 * @return array Alfresco repository folder names.
 */
    function find_userset_folders(&$opts, $createonly) {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/ddllib.php');

        // Ensure that the cluster table actually exists before we query it.
        $manager = $DB->get_manager();
        if (!$manager->table_exists('crlm_cluster')) {
            return false;
        }

        if (!file_exists($CFG->dirroot . '/elis/program/plugins/userset_classification/usersetclassification.class.php')) {
            return false;
        }

        require_once($CFG->dirroot . '/elis/program/plugins/userset_classification/usersetclassification.class.php');
        require_once($CFG->dirroot . '/elis/program/lib/data/userset.class.php');

        // Get cm userid
        $cmuserid = pm_get_crlmuserid($USER->id);

        $timenow = time();

        $capability = 'repository/elis_files:viewusersetcontent';

        $child_path = $DB->sql_concat('c_parent.path', "'/%'");
        $like = $DB->sql_like('c.path',':child_path');

        // Select clusters and sub-clusters for the current user
        // to which they have the vieworganization capability
        $sql = "SELECT DISTINCT clst.id AS instanceid
            FROM {crlm_cluster} clst
            WHERE EXISTS ( SELECT 'x'
                FROM {context} c
                JOIN {context} c_parent
                  ON {$like}
                  OR c.path = c_parent.path
                JOIN {role_assignments} ra
                  ON ra.contextid = c_parent.id
                  AND ra.userid = :muserid
                JOIN {role_capabilities} rc
                  ON ra.roleid = rc.roleid
                WHERE c_parent.instanceid = clst.id
                  AND c.contextlevel = :contextlevelnum
                  AND c_parent.contextlevel = :contextlevelnum2
                  AND rc.capability = :capability
                )
             OR EXISTS ( SELECT 'x'
                 FROM {crlm_cluster_assignments} ca
                WHERE ca.clusterid = clst.id
                  AND ca.userid = :cmuserid)
              ";

        $params = array(
            'capability'       => $capability,
            'child_path'       => $child_path,
            'contextlevelnum'  => CONTEXT_ELIS_USERSET,
            'contextlevelnum2' => CONTEXT_ELIS_USERSET,
            'muserid'          => $USER->id,
            'cmuserid'         => $cmuserid
        );
        $viewable_clusters = $DB->get_recordset_sql($sql, $params);

        // Get user clusters
        $cluster_info = array();
        if ($viewable_clusters) {
            foreach ($viewable_clusters as $cluster) {
                if (!$new_cluster_info = $this->load_cluster_info($cluster->instanceid)) {
                   continue;
                } else {
                    $cluster_info[$cluster->instanceid] = $new_cluster_info;
                }
            }
        } else {
            return false;
        }
        if (empty($cluster_info)) {
            return false;
        }

        // There may be multiple clusters that this user is assigned to...
        foreach ($cluster_info as $cluster) {
            // Get the extra cluster data and ensure it is present before proceeding.
            $clusterdata = usersetclassification::get_for_cluster($cluster);

            if (empty($clusterdata->params)) {
                continue;
            }

            $clusterparams = unserialize($clusterdata->params);

            // Make sure this cluster has the Alfresco shared folder property defined
            if (empty($clusterparams['elis_files_shared_folder'])) {
                continue;
            }

            // Make sure we can get the storage space from Alfresco for this userset.
            if (!$uuid = $this->get_userset_store($cluster->id)) {
                continue;
            }

            // Add to opts array
            $cluster_context = context_elis_userset::instance($cluster->id);
            $system_context = context_system::instance();

            $viewalfuserset = has_capability('repository/elis_files:viewusersetcontent', $cluster_context) ||
                              has_capability('repository/elis_files:viewsitecontent', $system_context);
            $editalfuserset = has_capability('repository/elis_files:createusersetcontent', $cluster_context) ||
                              has_capability('repository/elis_files:createsitecontent', $system_context);
            if ($editalfuserset) {
                if (!elis_files_has_permission($uuid, $USER->username, true)) {
                    $this->allow_edit($USER->username, $uuid);
                }
            } else if ($viewalfuserset) { // viewalfuserset
                if (!elis_files_has_permission($uuid, $USER->username)) {
                    $this->allow_read($USER->username, $uuid);
                }
            }
            if ((!$createonly && ($editalfuserset || $viewalfuserset)) || ($createonly && $editalfuserset)) {
                $params = array('path'=>$uuid,
                                'shared'=>(boolean)0,
                                'oid'=>(int)$cluster->id,
                                'cid'=>0,
                                'uid'=>0);
                $encodedpath = base64_encode(serialize($params));

                $unbiasedparams = array(
                    'path'   => $uuid,
                    'shared' => false,
                    'oid'    => 0,
                    'cid'    => 0,
                    'uid'    => 0
                );
                $unbiasedpath = base64_encode(serialize($unbiasedparams));
                $opts[] = array('name'=> $cluster->name,
                                'path'=> $encodedpath,
                                'unbiasedpath'=> $unbiasedpath
                                );
            }
        }

        return true;
    }


 /**
  * Function to load assigned cluster information into the user object.
  * @param int | array The cluster information as data id or cluster information array.
  * @param int The Moodle userid
  * #return array cluster data
 */
    function load_cluster_info($usersetinfo) {
        global $DB;

        if (is_int($usersetinfo) || is_numeric($usersetinfo)) {
            if (!isset($userset_data)) {
                $userset_data = array();
            }
            $userset_data = new userset($usersetinfo);
        } else if (is_array($usersetinfo)) {
            foreach ($usersetinfo as $ucid => $userset) {
                if (!isset($userset_data)) {
                    $userset_data = array();
                }
                $userset_data = new userset($userset->clusterid);
            }
        }
        return $userset_data;
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

        $username = elis_files_transform_username($user->username);

        // We need to create a new account now.
        $userdata = array(
            'username'     => $username,
            'password'     => $password,
            'firstname'    => $user->firstname,
            'lastname'     => $user->lastname,
            'email'        => $user->email,
            'organization' => $user->institution
        );

        if (!empty($this->config->user_quota)) {
            $userdata['quota'] = $this->config->user_quota;
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
 * @uses $CFG
 * @uses $DB
 * @param object|string $userorusername The Moodle DB user record object or username.
 * @param string        $password       Optionally specify a password for the Alfresco account.
 * @return bool True on success, False otherwise.
 */
    function migrate_user($userorusername, $password = '') {
        global $CFG, $DB;

        if (ELIS_FILES_DEBUG_TRACE) mtrace('migrate_user(' . (is_object($userorusername) ? 'object' : $userorusername) . ')');

        if (is_string($userorusername)) {
            if (!$user = $DB->get_record('user', array('username' => $userorusername, 'mnethostid'=> $CFG->mnet_localhost_id))) {
                return false;
            }
        } else if (is_object($userorusername)) {
            $user = clone $userorusername;
        } else {
            return false;
        }

        $user->username = $this->alfresco_username_fix($user->username);
        $this->set_alfresco_username($user->username);

        // Create the user's Alfresco account (if one does not already exist).
        if (!elis_files_create_user($user, $password)) {
            return false;
        }

        // If the user has an old-style user directory, migrate its contents and delete the directory.
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

            // Remove the old-style user storage directory.
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

        $dir = $this->read_dir($this->uhomesuid);

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
                    } else if ($user = $DB->get_record('user', array('id' => $uid, 'deleted' => 1))) {
                        // The user's directory should have been deleted but their legacy directory may still
                        // be hanging around
                        // TODO: respect the "delete home directories" setting?
                        if (!elis_files_delete($folder->uuid, true)) {
                            debugging(get_string('couldnotdeletefile', 'repository_elis_files', $folder->title));
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
            //error_log("ELIS_files::elis_files_userdir({$username}): elis_files_get_home_directory() === false!");
            return false;
        }
        //error_log("ELIS_files::elis_files_userdir({$username}): elis_files_get_home_directory() => {$uuid}");

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
        $deletehomedir = !empty($this->config->deleteuserdir);

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
     * Find personal and shared repository capabilities
     *
     * @uses $DB
     * @param object $user
     * @param array  $capabilities
     * @return none
     */
    function get_other_capabilities($user, &$capabilities) {
        global $DB;

        // Site administrators can do anything
        if (is_siteadmin($user->id)) {
            foreach ($capabilities as $capability => $value) {
                $capabilities[$capability] = true;
            }
            return;
        }

        // Look for these permissions anywhere in the system
        foreach ($capabilities as $capability => $value) {
            $sql = "SELECT ra.*
                    FROM {role_assignments} ra
                    INNER JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
                    WHERE ra.userid = :userid
                    AND rc.capability = :capability
                    AND rc.permission = :permission";

            $params = array(
                'userid'     => $user->id,
                'capability' => $capability,
                'permission' => CAP_ALLOW
            );

            if ($DB->record_exists_sql($sql, $params)) {
                $capabilities[$capability] = true;
            }
        }
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
        global $CFG, $DB;

        // Presently, we are only checking for site-wide and shared repository space capabilities.
        $capabilities = array(
            'repository/elis_files:createsitecontent',
            'repository/elis_files:viewsitecontent',
            'repository/elis_files:createsharedcontent',
            'repository/elis_files:viewsharedcontent'
        );

        $sitecap  = false;
        $sharecap = false;

        $root = $this->get_root();

        foreach ($capabilities as $capability) {
            $sql = "SELECT ra.id
                    FROM {role_assignments} ra
                    INNER JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
                    WHERE ra.userid = :userid
                    AND rc.capability = :capability
                    AND rc.permission = :perm";

            $params = array(
                'userid'     => $user->id,
                'capability' => $capability,
                'perm'       => CAP_ALLOW
            );

            if ($DB->record_exists_sql($sql, $params)) {
                if ($capability == 'repository/elis_files:createsitecontent') {
                    $this->allow_edit($user->username, $root->uuid);
                    $sitecap = true;

                } else if (!$sitecap && $capability == 'repository/elis_files:viewsitecontent') {
                    $this->allow_read($user->username, $root->uuid);
                    $sitecap = true;

                } else if ($capability == 'repository/elis_files:viewsitecontent') {
                    $this->allow_edit($user->username, $this->suuid);
                    $sharecap = true;

                } else if (!$sharecap && $capability == 'repository/elis_files:createsharedcontent') {
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
 * @param int    $oid    A cluster record ID.
 * @param bool   $clear  Set to true to clear out the previous location settings.
 * @return none
 */
    function set_repository_location($uuid, $cid, $uid, $shared, $oid, $clear = false) {
        global $USER;

        if ($clear) {
            unset($USER->elis_files_repository_location);
        }

        $location = new stdClass;
        $location->uuid   = $uuid;
        $location->cid    = (int)$cid;
        $location->uid    = (int)$uid;
        $location->oid    = (int)$oid;
        $location->shared = (boolean)$shared;

        $USER->elis_files_repository_location = $location;
    }


/**
 * Get the previous UUID value that the user was browsing inside of and return it so long as their current
 * context relates to the context of where that UUID is located.
 *
 * @uses $USER
 * @param int  $cid      A course record ID.
 * @param int  $uid      A user record ID.
 * @param bool $shared   A flag to indicate whether the user is currently located in the shared repository area.
 * @param int  $oid      A cluster record ID.
 * @return string The UUID of the last location the user was browsing files in.
 */
    function get_repository_location(&$cid, &$uid, &$shared, &$oid) {
        global $COURSE, $USER;

        if (!isset($USER->elis_files_repository_location)) {
            return false;
        }

        $location = $USER->elis_files_repository_location;

        // Get the non context based permissions
        $capabilities = array(
            'repository/elis_files:viewowncontent'      => false,
            'repository/elis_files:createowncontent'    => false,
            'repository/elis_files:viewsharedcontent'   => false,
            'repository/elis_files:createsharedcontent' => false
        );
        $this->get_other_capabilities($USER, $capabilities);

        // If the previous value comes from within a cluster that is not the current cluster, return the root
        // storage value for the current cluster directory.
        if (!empty($location->uuid) && !empty($oid) &&  !empty($location->oid) && ($location->oid != $oid) &&
            ($location->uid === 0) && ($location->shared == $shared) && ($location->uid == $uid)) {

            $cluster_context = context_elis_userset::instance($oid);
            $syscontext = context_system::instance();

            $has_permissions = has_capability('repository/elis_files:viewusersetcontent', $cluster_context) ||
                               has_capability('repository/elis_files:createusersetcontent', $cluster_context) ||
                               has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                               has_capability('repository/elis_files:createsitecontent', $syscontext);
            if ($has_permissions) {
                return $this->get_userset_store($oid);
            }
        }

        // If the previous value comes from within a course that is not the current course, return the root
        // storage value for the current course directory.
        if (!empty($location->uuid) && isset($location->cid) && ($location->cid != $cid) &&
            ($location->uid === 0) && ($location->shared == $shared) && ($location->uid == $uid)) {

            if (empty($cid)) {
                $cid = $COURSE->id;
            }

            if ($cid == SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM);

                if (has_capability('repository/elis_files:viewsitecontent', $context)) {
                    $root = $this->get_root();

                    if (!empty($root->uuid)) {
                        return $root->uuid;
                    }
                }
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $cid);
                $syscontext = context_system::instance();

                $has_permissions = has_capability('repository/elis_files:viewcoursecontent', $context) ||
                                   has_capability('repository/elis_files:createcoursecontent', $context) ||
                                   has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                   has_capability('repository/elis_files:createsitecontent', $syscontext);
                if ($has_permissions) {
                    return $this->get_course_store($cid);
                }
            }
        }

        if (empty($location->uuid)) {
            // If we have explicity requested a user's home directory, make sure we return that
            if ((isset($location->oid) && $location->oid == $oid) &&
                (isset($location->cid) && $location->cid == $cid) &&
                (isset($location->uid) && ($location->uid != $uid) && ($uid === $USER->id)) &&
                empty($location->uuid)) {

                // Check for correct permissions
                $syscontext = context_system::instance();

                $has_permissions = $capabilities['repository/elis_files:viewowncontent'] ||
                                   $capabilities['repository/elis_files:createowncontent'] ||
                                   has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                   has_capability('repository/elis_files:createsitecontent', $syscontext);
                if ($has_permissions) {
                    $shared = (boolean)0;
                    return $this->get_user_store($uid);
                }
            }

            // If we requested the shared repository location
            if ((isset($location->oid) && $location->oid == $oid) &&
                (isset($location->cid) && $location->cid == $cid) &&
                (isset($location->uid) && $location->uid == $uid) &&
                ((isset($location->shared) && ($location->shared != $shared) && ($shared == true)) ||
                (!isset($location->shared) && $shared == true))) {

                $syscontext = context_system::instance();

                $has_permissions = $capabilities['repository/elis_files:viewsharedcontent'] ||
                                   $capabilities['repository/elis_files:createsharedcontent'] ||
                                   has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                   has_capability('repository/elis_files:createsitecontent', $syscontext);
                if ($has_permissions) {
                    $uid = 0;
                    return $this->suuid;
                }
            }
        }

        // Otherwise, we are using the same settings as the previous location, so ensure that the calling script
        // has those values.
        if (empty($uid)) {
            $cid = (isset($location->cid) && $location->cid == $cid ? $location->cid : $cid);
        }
        $oid    = ((!empty($location->uuid) && isset($location->oid)) ? $location->oid : $oid);
        $uid    = ((!empty($location->uuid) && isset($location->uid)) ? $location->uid : $uid);
        $shared = ((!empty($location->uuid) && isset($location->shared)) ? $location->shared : $shared);

        return $location->uuid;
    }


/**
 * Get the default repository location.
 *
 * @uses $CFG, $COURSE, $USER
 * @param int  $cid      A course record ID.
 * @param int  $uid      A user record ID.
 * @param bool $shared   A flag to indicate whether the user is currently located in the shared repository area.
 * @param int  $oid      A userset record ID.
 * @return string The UUID of the last location the user was browsing files in.
 */
    function get_default_browsing_location(&$cid, &$uid, &$shared, &$oid) {
        global $CFG, $COURSE, $USER, $DB;

        // If the default location is not set at all, just return nothing now.
        if (!isset($this->config->default_browse)) {
            return false;
        } elseif (isset($this->config->default_browse)) {
        // Handle determining if the user can actually access the chosen default location.
            if (empty($cid)) {
                $cid = $COURSE->id;
            }
            $syscontext = get_context_instance(CONTEXT_SYSTEM);
            if ($cid == SITEID) {
                $context = $syscontext;
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $cid);
            }

         /* **** Disable following block for ELIS-7127 ****
            // If on ELIS Files page or in course context - default to course page if we have access to it
            if ($cid != SITEID && (has_capability('repository/elis_files:viewcoursecontent', $context) ||
                has_capability('repository/elis_files:createcoursecontent', $context))) {
                    $shared = 0;
                    $uid    = 0;
                    return $this->get_course_store($cid);
            } else if ($cid == SITEID && $uid == 0 && (has_capability('repository/elis_files:viewsitecontent', $context) ||
                        has_capability('repository/elis_files:createsitecontent', $context))) {
            // If on home page and not in user context - default to Company Home if we have access to it
                    $root = $this->get_root();
                    if (!empty($root->uuid)) {
                        $shared = 0;
                        $uid    = 0;
                        return $root->uuid;
                    }
            }
         **** END Disable block for ELIS-7127 **** */

            $oid = 0;

            /**
             * ELIS-7452: We're gonna go thru all possible browing locations
             * in pre-determined order:
             * User > Site > Shared [> Userset > Course]
             * but we'll put desired default_browsing location first!
             */
            $browsing_locs = array(ELIS_FILES_BROWSE_USER_FILES,
                                   ELIS_FILES_BROWSE_SITE_FILES,
                                   ELIS_FILES_BROWSE_SHARED_FILES,
                                   ELIS_FILES_BROWSE_COURSE_FILES,
                                   ELIS_FILES_BROWSE_USERSET_FILES);

            $default_entry = array_search($this->config->default_browse,
                                          $browsing_locs);
            if ($default_entry !== false) {
                array_splice($browsing_locs, $default_entry, 1);
            }
            $browsing_locs = array_merge(array($this->config->default_browse),
                                         $browsing_locs);

            // If a user does not have permission to access the default location, fall through to the next
            // lower level to see if they can access that location.
            // TBD: MUST CHECK FOR CAPABILITIES AY ANY CONTEXT LEVEL!!!
            foreach ($browsing_locs as $browse_loc) {
                switch ($browse_loc) {
                case ELIS_FILES_BROWSE_SITE_FILES:
                    if (has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                        has_capability('repository/elis_files:createsitecontent', $syscontext)) {

                        $root = $this->get_root();

                        if (!empty($root->uuid)) {
                            $shared = 0;
                            $uid    = 0;
                            $cid    = 0;
                            return $root->uuid;
                        }
                    }
                    break;

                case ELIS_FILES_BROWSE_SHARED_FILES:
                    // Get the non context based permissions
                    $capabilities = array(
                        'repository/elis_files:viewsharedcontent'  => false,
                        'repository/elis_files:createsharedcontent'=> false
                    );
                    $this->get_other_capabilities($USER, $capabilities);

                    $has_permission = $capabilities['repository/elis_files:viewsharedcontent'] ||
                                      $capabilities['repository/elis_files:createsharedcontent'] ||
                                      has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                      has_capability('repository/elis_files:createsitecontent', $syscontext);
                    if ($has_permission) {
                        $shared = true;
                        $uid    = 0;
                        $cid    = 0;
                        return $this->suuid;
                    }
                    break;

                case ELIS_FILES_BROWSE_COURSE_FILES:
                    $has_permission = false;
                    if ($cid == SITEID && $COURSE->id != SITEID) {
                        $cid = $COURSE->id;
                    }
                    if (!$cid || $cid == SITEID) {
                        // TBD: no valid $COURSE so just find first one???
                        $courses = enrol_get_my_courses();
                        if (empty($courses)) {
                            $cid = 0;
                            break;
                        }
                        foreach ($courses as $course) {
                            $context = get_context_instance(CONTEXT_COURSE, $course->id);
                            $has_permission = has_capability('repository/elis_files:viewcoursecontent', $context) ||
                                              has_capability('repository/elis_files:createcoursecontent', $context) ||
                                              has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                              has_capability('repository/elis_files:createsitecontent', $syscontext);
                            if ($has_permission) {
                                $cid = $course->id;
                                break;
                            }
                        }
                    }
                    if ($cid && $cid != SITEID) {
                        if (!$has_permission) {
                            $context = get_context_instance(CONTEXT_COURSE, $cid);
                            $has_permission = has_capability('repository/elis_files:viewcoursecontent', $context) ||
                                              has_capability('repository/elis_files:createcoursecontent', $context) ||
                                              has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                              has_capability('repository/elis_files:createsitecontent', $syscontext);
                        }
                        if ($has_permission) {
                            $shared = 0;
                            $uid    = 0;
                            return $this->get_course_store($cid);
                        }
                    }
                    $cid = 0;
                    break;

                case ELIS_FILES_BROWSE_USER_FILES:
                    $context = get_context_instance(CONTEXT_USER, $USER->id);

                    $has_permission = has_capability('repository/elis_files:viewowncontent', $syscontext) ||
                                      has_capability('repository/elis_files:createowncontent', $syscontext) ||
                                      has_capability('repository/elis_files:viewowncontent', $context) ||
                                      has_capability('repository/elis_files:createowncontent', $context) ||
                                      has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                      has_capability('repository/elis_files:createsitecontent', $syscontext);
                    if ($has_permission) {

                        if (empty($this->uuuid)) {
                            $this->uuuid = $this->elis_files_userdir($USER->username);
                        }
                        if (($uuid = $this->uuuid) !== false) {
                            $shared = 0;
                            $uid    = $USER->id;
                            $cid    = 0;
                            return $uuid;
                        }
                    }
                    break;

                case ELIS_FILES_BROWSE_USERSET_FILES:
                    if (!file_exists($CFG->dirroot .'/elis/program/accesslib.php')) {
                        break;
                    }
                    require_once($CFG->dirroot .'/elis/program/accesslib.php');
                    require_once($CFG->dirroot .'/elis/program/lib/setup.php');
                    require_once($CFG->dirroot .'/elis/program/lib/deprecatedlib.php');
                    $crlm_user = cm_get_crlmuserid($USER->id);
                    if ($crlm_user === false) {
                        break;
                    }
                    $contextclass = context_elis_helper::get_class_for_level(CONTEXT_ELIS_USERSET);
                    $assignments = $DB->get_records('crlm_cluster_assignments',
                                                    array('userid' => $crlm_user));
                    // TBD: just get the first valid userset store???
                    foreach ($assignments as $cluster_assignment) {
                        $context = $contextclass::instance($cluster_assignment->clusterid);

                        $has_permission = has_capability('repository/elis_files:viewusersetcontent', $context) ||
                                          has_capability('repository/elis_files:createusersetcontent', $context) ||
                                          has_capability('repository/elis_files:viewsitecontent', $syscontext) ||
                                          has_capability('repository/elis_files:createsitecontent', $syscontext);
                        if ($has_permission) {
                            $uuid = $this->get_userset_store($cluster_assignment->clusterid);
                            if (!empty($uuid)) {
                                $oid    = $cluster_assignment->clusterid;
                                $shared = 0;
                                $uid    = 0;
                                $cid    = 0;
                                return $uuid;
                            }
                        }
                    }
                    break;
                }
            }
        }

        return false;
    }

    /*
     * This function sets version and folder_type as required
     * @return  bool false if we cannot connect to Alfresco
     */
    function get_defaults() {
        // Initialize the alfresco version
        if (!(self::$version = elis_files_get_repository_version())) {
            return false;
        }

        // Set the file and folder type
        if (!isset(self::$type_document)) {
            if (self::is_version('3.2')) {
                self::$type_folder   = 'folder';
                self::$type_document = 'document';
            } else if (self::is_version('3.4')) {
                self::$type_folder   = 'cmis:folder';
                self::$type_document = 'cmis:document';
            }
        }

        return true;
    }

    /**
     * Check if a given version of Alfresco is running with as much specificity to the version as required.
     *
     * Example: $vcomp = "3.2" will return true if the version is 3.2.1 and false if the version is 3.4.6.
     *
     * @param string $vcomp The partial or full version string to check for.
     * @return bool True if the version matches the request, False otherwise.
     */
    public static function is_version($vcomp) {
        return (strpos(self::$version, $vcomp) === 0) ? true : false;
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
        if (!empty($this->config->cron) &&
            ((time() - $this->config->cron) < ELIS_FILES_CRON_VALUE)) {

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

    /**
    * User names with an @ symbol in Alfresco have a special meaning, referring to tenants/organizations.
    * The @ symbol in a Moodle user name must be replaced for Alfresco compatibility when creating Alfresco accounts.
    *
    * @param string The Moodle user's user name.
    * @return string The modified user name.
    */
    public static function alfresco_username_fix($username) {
        $modified_username = str_replace('@','_AT_', $username);
        return $modified_username;
    }

    /**
     * Correct Moodle username so it can be used for Alfresco
     *
     * @param string $username The Moodle user's username.
     * @return string|bool The UUID of the home directory or, False.
     */
    function fix_username($username) {
        if (ALFRESCO_DEBUG_TRACE) mtrace('alfresco_name(' . $username . ')');

        $username = str_replace(array_keys(repository_plugin_alfresco::$username_map),array_values(repository_plugin_alfresco::$username_map), $username);

        return $username;
    }
    function set_alfresco_username($username) {
        $this->alfresco_username_fix = $username;
    }

    function get_alfresco_username_fix() {
        return $this->alfresco_username_fix;
    }

    /**
     * Specialized get_referer method to check for url parameter 'referer' if referer not set
     * @see ELIS-8527
     * @return bool|string the referer or false if none found
     */
    public function get_referer() {
        $referer = get_referer(false);
        if (empty($referer)) {
            if ($referer = optional_param('referer', false, PARAM_CLEAN)) {
                $referer = htmlspecialchars_decode(urldecode($referer));
            }
            // error_log("repository/elis_files::get_referer(): optional_param('referer') = {$referer}");
        }
        return $referer;
    }

    /**
     * Method to return a link to an ELIS Files file
     * @param string $uuid the uuid of the requested file
     * @return string the html link to the repository file
     */
    public function get_openfile_link($uuid) {
        global $CFG, $ME;
        $referer = $this->get_referer();
        $link = new moodle_url('/repository/elis_files/openfile.php', array('uuid' => $uuid, 'referer' => empty($referer) ? $ME : $referer));
        // error_log("get_openfile_link({$uuid}) => {$link}");
        return $link->out(false);
    }
}
