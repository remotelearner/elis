<?php
/**
 * Alfresco CMIS REST interface API for Alfresco version 3.2 / 3.4
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
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once dirname(__FILE__). '/ELIS_files.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))). '/elis/core/lib/setup.php';
require_once dirname(__FILE__) . '/cmis-php/cmis_repository_wrapper.php';

/**
 * Send a GET request to the Alfresco repository.
 *
 * @param string $uri      The service URI we are requesting data from.
 * @param string $username The username to use for user authentication.
 * @return mixed Return response from the repository.
 */
function elis_files_request($uri, $username = '') {
    global $CFG;

    if (ELIS_FILES_DEBUG_TRACE) print_object('$uri: ' . $uri.' username: '.$username);
    if (!$response = elis_files_utils_invoke_service($uri, 'ticket', array(), 'GET', array(), $username)) {
//        debugging(get_string('couldnotaccessserviceat', 'repository_elis_files', $uri), DEBUG_DEVELOPER);
        if (ELIS_FILES_DEBUG_TRACE && $CFG->debug == DEBUG_DEVELOPER) print_object($response);
    }

    return $response;
}


/**
 * Send a POST-like request to the Alfresco repository.
 *
 * @param string $uri      The service URI we are sending data to.
 * @param array  $data     An array of data items we're sending.
 * @param string $action   The action we're using (POST, DELETE).
 * @param string $username The username to use for user authentication.
 * @return mixed Return response from the repository.
 */
function elis_files_send($uri, $data = array(), $action = '', $username = '') {
    global $CFG;

    if (!empty($action)) {
        switch ($action) {
            case 'POST':
                $action = 'CUSTOM-POST';
                break;

            case 'DELETE':
                $action = 'CUSTOM-DELETE';
                break;

            default:
                return false;
                break;
        }
    } else {
        $action = 'CUSTOM-POST';
    }

    return elis_files_utils_invoke_service($uri, 'ticket', array(), $action, $data, $username);
}


/**
 * Get a list of service URIs from the Alfresco repository.
 *
 * @param none
 * @return array An array of service URI values.
 */
function elis_files_get_services() {
    $services = array();
    $response = elis_files_request('/api/repository');

    if (empty($response) || !strpos($response, '<?xml') === 0) {
        return false;
    }

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

    $dom = new DOMDocument();
    $dom->loadXML($response);

    foreach ($dom->getElementsByTagName('collection') as $node) {
        if (($type = $node->getAttribute('collectionType')) ||
            ($type = $node->getAttribute('cmis:collectionType'))) {
            switch ($type) {
                case 'root-children':
                case 'rootchildren':
                    $services['root'] = str_replace(elis_files_base_url(), '', $node->getAttribute('href'));
                    break;

                case 'types-children':
                case 'typeschildren':
                    $services['types'] = str_replace(elis_files_base_url(), '', $node->getAttribute('href'));
                    break;

                case 'query':
                    $services['query'] = str_replace(elis_files_base_url(), '', $node->getAttribute('href'));

                default:
                    break;
            }
        }
    }

    return $services;
}

function RLsimpleXMLelement($resp, $displayerrors = true) {
    //echo "RLsimpleXMLelement() => resp = {$resp}";
    $details = $resp;
    if (($preend = strpos($resp, '<title><!DOCTYPE')) !== false &&
        ($lastentry = strrpos($resp, "<entry>")) !== false) {
        $details = substr($resp, $preend + 7, -7);
        $resp = substr($resp, 0, $lastentry - 1) ."\n</feed>";
        error_log("repository/elis_files/lib/lib.php:: WARNING - Alfresco Server Error: {$details}");
    }
    $resp = preg_replace('/&nbsp;/', ' ', $resp); // TBD?
    libxml_use_internal_errors(true);
    try {
        $sxml = new SimpleXMLElement($resp);
    } catch (Exception $e) {
        error_log("repository/elis_files/lib/lib.php:: WARNING - Alfresco Server Error: BAD XML => {$details}");
        return false;
    }
    return $sxml;
}

/**
 * Determine the current repository version.
 *
 * @uses $CFG
 * @param string $versioncheck Optionally supply a version to determine if the
 *                             repository is equal or newer than the specified value.
 * @return string|bool A string containing the version or, True/False if $versioncheck has been specified.
 */
function elis_files_get_repository_version($versioncheck = '') {
    $response = elis_files_request('/moodle/repoversion');

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    $version = current($sxml);

    if (empty($version)) {
        return false;
    }

    if (!empty($version) && !empty($versioncheck)) {
        $rvparts = explode('.', $version);
        $vcparts = explode('.', $versioncheck);

        // Check the Major version number.
        if ($vcparts[0] > $rvparts[0]) {
            return false;
        } else if ($vcparts[0] < $rvparts[0]) {
            return true;
        }

        // Check the Minor version number.
        if ($vcparts[1] > $rvparts[1]) {
            return false;
        } else if ($vcparts[1] < $rvparts[1]) {
            return true;
        }

        // Check the Revision version number.
        if (count($vcparts) == 3 && count($rvparts) == 3 && $vcparts[2] > $rvparts[2]) {
            return false;
        }

        // At this point the version we're checking is equal or greater than the actual repository version.
        return true;
    }

    return $version;
}

/**
 * Get the parent node of a given folder.
 *
 * @param string $uuid The node UUID.
 * @return object Information about the parent node.
 */
function elis_files_get_parent($uuid) {
    if ($response = elis_files_request(elis_files_get_uri($uuid, 'parent'))) {
        if (!strpos($response, '<?xml') === 0) {
            return false;
        }

        $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($response);

        $nodes = $dom->getElementsByTagName('entry');

        if (!$nodes->length) {
            return false;
        }

        $type  = '';

        return elis_files_process_node($dom, $nodes->item(0), $type);
    }

    return false;
}


/**
 * Get a node UUID from it's complete repository path.
 *
 * @param string $path The full path on the repository to the node.
 * @return string|bool The UUID value for the end node, or False on error.
 */
function elis_files_uuid_from_path($path, $uuid = '') {
    if ($path == '/') {
        return true;
    }
/// Remove any extraneous slashes from the ends of the string
    $path = trim($path, '/');

    $parts = explode('/', $path);

/// Initialize the folder structure if a structure piece wasn't passed to this function.
    $children = elis_files_read_dir($uuid);
/// Get the first piece from the list of path elements.
    $pathpiece = array_shift($parts);

/// This node has no child folders, which means the current part of the path we're looking
/// for does not exist.
    if (empty($children->folders)) {
        return false;
    }

    foreach ($children->folders as $folder) {
        if ($folder->title == $pathpiece) {
            $fchildren = elis_files_read_dir($folder->uuid);
        /// If there are no more path elements, we've succeeded!
            if (empty($parts)) {
                return $folder->uuid;
        /// Otherwise, keep looking below.
            } else {
                return elis_files_uuid_from_path(implode('/', $parts), $folder->uuid);
            }
        }
    }
}


/**
 * Read a folder node and process a directory node reference to get a list of
 * folders and files within it.
 *
 * @param string $uuid     The node UUID.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return object An object containing an array of folders and file node references.
 */
function elis_files_read_dir($uuid = '', $useadmin = true) {
    global $USER;

    $return = new stdClass;
    $return->folders = array();
    $return->files   = array();

    if (empty($uuid)) {
        if (ELIS_files::is_version('3.2')) {
            $services = elis_files_get_services();
            $response = elis_files_request($services['root']);
        } else if (ELIS_files::is_version('3.4')) {
            // Force the usage of the configured Alfresco admin account, if requested.
            if ($useadmin) {
                $username = '';
            } else if (isloggedin()) {
                $username = $USER->username;
            } else {
                $username = '';
            }
            $response = elis_files_request('/cmis/p/children', $username);
        }
    } else {

        // Force the usage of the configured Alfresco admin account, if requested.
        if ($useadmin) {
            $username = '';
        } else if (isloggedin()) {
            $username = $USER->username;
        } else {
            $username = '';
        }

        if (ELIS_files::is_version('3.2')) {
            $response = elis_files_request(elis_files_get_uri($uuid, 'children'), $username);
        } else if (ELIS_files::is_version('3.4')) {
            $response = elis_files_request('/cmis/i/' . $uuid .'/children', $username);
        }
    }

    if (empty($response)) {
        return $return;
    }

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($response);
    $nodes = $dom->getElementsByTagName('entry');

    for ($i = 0; $i < $nodes->length; $i++) {
        $node = $nodes->item($i);
        $type = '';
        $contentNode = elis_files_process_node($dom, $node, $type);

        if ($type == ELIS_files::$type_folder) {
            $return->folders[] = $contentNode;

        // Only include a file in the list if it's title does not start with a period '.'
        } else if ($type == ELIS_files::$type_document && !empty($contentNode->title) && $contentNode->title[0] !== '.') {
            $return->files[] = $contentNode;
        }
    }

    usort($return->folders, 'elis_files_ls_sort');
    usort($return->files, 'elis_files_ls_sort');
//echo '<br>returning:';
//print_object($return);
    return $return;
}

/**
 * Return the base URL used for accessing the configured repository.
 *
 * @param none
 * @return string The base URL.
 */
function elis_files_base_url() {
    if (!isset(elis::$config->elis_files->server_host)) {
        return '';
    }

    $repourl = elis::$config->elis_files->server_host;

    if ($repourl[strlen($repourl) - 1] == '/') {
        $repourl = substr($repourl, 0, strlen($repourl) - 1);
    }

    if (!empty(elis::$config->elis_files->server_port)) {
        $repourl .= ':' . elis::$config->elis_files->server_port;
    }

    $repourl .= '/alfresco/s';

    return $repourl;
}


/**
 * Generate a specific REST URI for a given UUID value.
 *
 * @uses $CFG
 * @param string $uuid The node UUID value.
 * @param string $function The type
 */
function elis_files_get_uri($uuid = '', $function = '') {

    if (empty($uuid) && empty($function)) {
        return '/api/node/workspace/SpacesStore/';
    }

    switch ($function) {
        case 'sites':
            return '/api/path/workspace/SpacesStore/';
            break;

        case 'parent':
            return '/api/node/workspace/SpacesStore/' . $uuid . '/parent';
            break;

        case 'children':
            return '/api/node/workspace/SpacesStore/' . $uuid . '/children';
            break;

        case 'delete':
            return '/api/node/workspace/SpacesStore/' . $uuid;
            break;

        case 'deleteallversions':
            return '/api/node/workspace/SpacesStore/' . $uuid . '/versions';
            break;

        case 'self':
        default:
            return '/api/node/workspace/SpacesStore/' . $uuid;
            break;
    }
}


/**
 * Determine whether a node UUID is a folder or a file reference.
 *
 * @param string $uuid The node UUID value.
 * @return string|bool A string name for the node type or, False on error.
 */
function elis_files_get_type($uuid) {
    if (ELIS_files::is_version('3.2')) {
        if (!$response = elis_files_request(elis_files_get_uri($uuid, 'self'))) {
            return false;
        }
    } else if (ELIS_files::is_version('3.4')) {
        if (!$response = elis_files_request('/cmis/i/' . $uuid)) {
            return false;
        }
    }

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

    $dom = new DOMDocument();
    $dom->preserverWhiteSpace = false;
    $dom->loadXML($response);

    if (ELIS_files::is_version('3.2')) {
        $entries = $dom->getElementsByTagName('propertyString');

        if ($entries->length) {
            for ($i = 0; $i < $entries->length; $i++) {
                $node = $entries->item($i);

            /// Sloppily handle strict namespacing here.
                if ($node->getAttribute('cmis:name') == 'BaseType' ||
                    $node->getAttribute('name') == 'BaseType') {

                    return $node->nodeValue;
                }
            }
        }
    } else if (ELIS_files::is_version('3.4')) {
        $elm = $dom->getElementsByTagNameNS('http://docs.oasis-open.org/ns/cmis/core/200908/', 'properties');

        if ($elm->length === 1) {
            $elm = $elm->item(0);

            $properties = $elm->childNodes;

               if ($properties->length) {
                   for ($i = 0; $i < $properties->length; $i++) {
                    $node = $properties->item($i);
                    if (!$node->hasAttributes()) {
                        continue;
                    }

            /// Sloppily handle strict namespacing here.
                    if ($node->hasAttribute('propertyDefinitionId') && $node->getAttribute('propertyDefinitionId') == 'cmis:objectTypeId') {
                        return $node->nodeValue;
                    }
                }
            }
        }

    }
    return false;
}


/**
 * Process a single node and return as many properties as we can.
 *
 * @param string $uri A URL reference to a REST request for a specific content node.
 * @return object The properties of the node in an object.
 */
function elis_files_node_properties($uuid) {
    if (!$response = elis_files_request(elis_files_get_uri($uuid, 'self'))) {
        return false;
    }

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($response);

    $nodes = $dom->getElementsByTagName('entry');

    if (!$nodes->length) {
        return false;
    }

    $type  = '';
    $node = elis_files_process_node($dom, $nodes->item(0), $type);
    $node->type = $type;

    return $node;
}


/**
 * Get a file's contents and send them to the user's browser.
 *
 * @param string $uuid The node UUID.
 * @return mixed The file contents.
 */
function elis_files_get_file($uuid) {

    if (ELIS_FILES_DEBUG_TRACE) print_object('elis_files_get_file(' . $uuid . ')');

    $node     = elis_files_node_properties($uuid);
    $contents = elis_files_request($node->fileurl);

    return $contents;
}


/**
 * Delete a node from the repository, optionally recursing into sub-directories (only
 * relevant when the node being deleted is a folder).
 *
 * @param string $uuid      The node UUID.
 * @param bool   $recursive Whether to recursively delete child content.
 * @return mixed
 */
function elis_files_delete($uuid, $recursive = false, $repo = NULL) {

    if (ELIS_FILES_DEBUG_TRACE)  print_object('elis_files_delete(' . $uuid . ', ' . $recursive . ')');

    // Ensure that we set the configured admin user to be the owner of the deleted file before deleting.
    // This is to prevent the user's Alfresco account from having space incorrectly attributed to it.
    // ELIS-1102

    elis_files_request('/moodle/nodeowner/' . $uuid . '?username=' . elis::$config->elis_files->server_username);

    if (ELIS_files::is_version('3.2')) {
        return (true === elis_files_send(elis_files_get_uri($uuid, 'delete'), array(), 'DELETE'));
    } else if (ELIS_files::is_version('3.4')) {
        // Get node type and use descendants delete for folders
        $node_type = elis_files_get_type($uuid);

        if (!(strstr($node_type,ELIS_files::$type_folder) === FALSE)) {
            if (elis_files_send('/cmis/i/' . $uuid.'/descendants', array(), 'DELETE') === false) {
                return false;
            }
        } else {
               //if ($repo->cmis->deleteObject('workspace://SpacesStore/' . $uuid) === false) {
            if (elis_files_send('/cmis/i/' . $uuid, array(), 'DELETE') === false) {
                return false;
            }
        }
        return true;
    }
}


/**
 * Create a directory on the repository.
 *
 * @uses $USER
 * @param string $name        The name of the directory we're checking for.
 * @param string $uuid        The UUID of the parent directory we're checking for a name in.
 * @param string $description An optional description of the directory being created.
 * @param bool   $useadmin    Set to false to make sure that the administrative user configured in
 *                            the plug-in is not used for this operation (default: true).
 * @return object|bool Node information structure on the new folder or, False on error.
 */
function elis_files_create_dir($name, $uuid = '', $description = '', $useadmin = true) {
    global $CFG, $USER;

    $properties = elis_files_node_properties($uuid);

    if (ELIS_files::is_version('3.2')) {
        $data = '<?xml version="1.0" encoding="utf-8"?>
                  <entry xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app"
                         xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org">
                    <link rel="type" href="' . elis_files_base_url() . '/api/type/folder"/>
                    <link rel="repository" href="' . elis_files_base_url() . '/api/repository"/>
                    <title>' . $name . '</title>
                    <summary>' . $description . '</summary>
                    <cmis:object>
                      <cmis:properties>
                        <cmis:propertyString cmis:name="ObjectTypeId">
                          <cmis:value>'.ELIS_files::$type_folder.'</cmis:value>
                        </cmis:propertyString>
                      </cmis:properties>
                    </cmis:object>
                  </entry>';

        $uri = '/api/node/workspace/SpacesStore/' . $uuid . '/descendants';

    } else if (ELIS_files::is_version('3.4')) {
        $uri = '/cmis/i/' . $uuid . '/children';

        $data = '<?xml version="1.0" encoding="utf-8"?>
                 <entry xmlns="http://www.w3.org/2005/Atom"
                        xmlns:cmisra="http://docs.oasis-open.org/ns/cmis/restatom/200908/"
                        xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200908/">
                   <link rel="type" href="' . elis_files_base_url() . '/api/type/folder"/>
                   <link rel="repository" href="' . elis_files_base_url() . '/api/repository"/>
                   <title>' . $name . '</title>
                   <summary>' . $description . '</summary>
                   <cmisra:object>
                     <cmis:properties>
                       <cmis:propertyId propertyDefinitionId="cmis:objectTypeId">
                         <cmis:value>'.ELIS_files::$type_folder.'</cmis:value>
                       </cmis:propertyId>
                     </cmis:properties>
                   </cmisra:object>
                 </entry>';
    }

    $header[] = 'Content-type: application/atom+xml;type=entry';
    $header[] = 'Content-length: ' . strlen($data);
    $header[] = 'MIME-Version: 1.0';

    // Force the usage of the configured Alfresco admin account, if requested.
    if ($useadmin) {
        $username = '';
    } else {
        $username = $USER->username;
    }

    $response = elis_files_utils_invoke_service($uri, 'basic', $header, 'CUSTOM-POST', $data, $username);

    if ($response === false) {
//        debugging(get_string('couldnotaccessserviceat', 'repository_elis_files', $uri), DEBUG_DEVELOPER);
        return false;
    }

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($response);

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


/**
 * Upload a file into the repository.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $upload   The array index of the uploaded file.
 * @param string $path     The full path to the file on the local filesystem.
 * @param string $uuid     The UUID of the folder where the file is being uploaded to.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return object Node values for the uploaded file.
 */
function elis_files_upload_file($upload = '', $path = '', $uuid = '', $useadmin = true) {
    global $CFG, $USER;

    require_once($CFG->libdir.'/filelib.php');

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
    if (!$repo = repository_factory::factory('elis_files')) {
        return false;
    }

    if (empty($uuid)) {
        $uuid = $repo->get_root()->uuid;

    }

    switch (get_config('elis_files', 'file_transfer_method')) {
        case ELIS_FILES_XFER_WS:
            return elis_files_upload_ws($filename, $filepath, $filemime, $filesize, $uuid, $useadmin);
            break;

        case ELIS_FILES_XFER_FTP:
            return elis_files_upload_ftp($filename, $filepath, $filemime, $filesize, $uuid, $useadmin);
            break;

        default:
            return false;
    }
}


/**
 * Upload a file into the repository via web services.
 *
 * @uses $USER
 * @param string $filename The name of the file being uploaded.
 * @param string $filepath The full path to the file being uploaded on the local filesystem.
 * @param string $filemime The MIME/type of the file being uploaded.
 * @param int    $filesize The size in bytes of the file being uploaded.
 * @param string $uuid     The UUID of the folder where the file is being uploaded to.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return object Node values for the uploaded file.
 */
function elis_files_upload_ws($filename, $filepath, $filemime, $filesize, $uuid = '', $useadmin = true) {
    global $USER;

    $chunksize = 8192;

/// We need to write the XML structure for the upload out to a file on disk to accomdate large files
/// that will potentially overrun the maximum memory allowed to a PHP script.
    $data1 = '<?xml version="1.0" encoding="utf-8"?>' . "\n";

    if (ELIS_files::is_version('3.2')) {
        $data1 .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" ' .
                  'xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org">' . "\n" .
                  '  <link rel="type" href="' . elis_files_base_url() . '/api/type/document"/>' . "\n" .
                  '  <link rel="repository" href="' . elis_files_base_url() . '/api/repository"/>' . "\n";
    } else if (ELIS_files::is_version('3.4')) {
        $data1 .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:cmis="http://www.cmis.org/2008/05">' . "\n";
    }

    $data1 .= '  <title>' . $filename . '</title>' . "\n" .
              '  <summary>' . get_string('uploadedbymoodle', 'repository_elis_files') . '</summary>' . "\n" .
              '  <content type="' . $filemime . '">';

    $data2 = '</content>' . "\n" .
             '  <cmis:object>' . "\n" .
             '    <cmis:properties>' . "\n" .
             '      <cmis:propertyString cmis:name="ObjectTypeId">' . "\n" .
             '        <cmis:value>document</cmis:value>' . "\n" .
             '      </cmis:propertyString>' . "\n" .
             '    </cmis:properties>' . "\n" .
             '  </cmis:object>' . "\n" .
             '</entry>';

    $encodedbytes = 0;

/// Use a stream filter to base64 encode the file contents to a temporary file.
    if ($fi = fopen($filepath, 'r')) {
        if ($fo = tmpfile()) {
            stream_filter_append($fi, 'convert.base64-encode');

        /// Write the beginning of the XML document to the temporary file.
            $encodedbytes += fwrite($fo, $data1, strlen($data1));

        /// Copy the uploaded file into the temporary file (usng the base64 encode stream filter)
        /// in 8K chunks to conserve memory.
            while (!feof($fi)) {
                $encodedbytes += fwrite($fo, fread($fi, $chunksize));
            }
            fclose($fi);

        /// Write the end of the XML document to the temporary file.
            $encodedbytes += fwrite($fo, $data2, strlen($data2));
        } else {
            return false;
        }
    } else {
        return false;
    }

    rewind($fo);

    // Force the usage of the configured Alfresco admin account, if requested.
    if ($useadmin) {
        $username = '';
    } else {
        $username = $USER->username;
    }

    if (ELIS_files::is_version('3.2')) {
        $serviceuri = '/api/node/workspace/SpacesStore/' . $uuid . '/descendants';
    } else if (ELIS_files::is_version('3.4')) {
        $serviceuri = '/cmis/i/' . $uuid . '/children';
    }

    // Initialize the object that return/result data is stored with
    $result = new stdClass;

    $url = elis_files_utils_get_wc_url($serviceuri, 'refresh', $username);
    $uri = parse_url($url);

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

/// Make sure the socket opened properly.
    if (!$fp) {
        $result->error = trim($errno .' '. $errstr);
        return $result;
    }

/// Construct the path to act on.
    $path = isset($uri['path']) ? $uri['path'] : '/';
    if (isset($uri['query'])) {
        $path .= '?'. $uri['query'];
    }

/// Create HTTP request.
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

/// Write the XML request (which contains the base64-encoded uploaded file contents) into the socket.
    while (!feof($fo)) {
        fwrite($fp, fread($fo, $chunksize));
    }

    fclose($fo);
    fwrite($fp, "\r\n");

/// Fetch response.
    $response = '';
    while (!feof($fp) && $chunk = fread($fp, $chunksize)) {
        $response .= $chunk;
    }
    fclose($fp);

    /// Parse response.
    list($split, $result->data) = explode("\r\n\r\n", $response, 2);
    $split = preg_split("/\r\n|\n|\r/", $split);

    list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
    $result->headers = array();

/// Parse headers.
    while ($line = trim(array_shift($split))) {
        list($header, $value) = explode(':', $line, 2);
        if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
        /// RFC 2109: the Set-Cookie response header comprises the token Set-
        /// Cookie:, followed by a comma-separated list of one or more cookies.
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

/// RFC 2616 states that all unknown HTTP codes must be treated the same as
/// the base code in their class.
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


/**
 * Upload a file into the repository via FTP.
 *
 * @uses $USER
 * @param string $filename The name of the file being uploaded.
 * @param string $filepath The full path to the file being uploaded on the local filesystem.
 * @param string $filemime The MIME/type of the file being uploaded.
 * @param int    $filesize The size in bytes of the file being uploaded.
 * @param string $uuid     The UUID of the folder where the file is being uploaded to.
 * @param bool   $useadmin Set to false to make sure that the administrative user configured in
 *                         the plug-in is not used for this operation (default: true).
 * @return object Node values for the uploaded file.
 */
function elis_files_upload_ftp($filename, $filepath, $filemime, $filesize, $uuid = '', $useadmin = true) {
    global $USER;

    $config = get_config('elis_files');

    // We need to get just the hostname out of the configured host URL
    $uri = parse_url($config->server_host);

    if (!($ftp = ftp_connect($uri['host'], $config->ftp_port, 5))) {
        error_log('Could not connect to Alfresco FTP server '.$uri['host'].$config->ftp_port);
        return false;
    }

    if (!ftp_login($ftp, $config->server_username, $config->server_password)) {
        error_log('Could not authenticate to Alfresco FTP server');
        ftp_close($ftp);
        return false;
    }

    $repo_path = elis_files_node_path($uuid);

    // The FTP server represents "Company Home" as "Alfresco" so we need to change that now:
    $repo_path = str_replace("/Company Home", "Alfresco", $repo_path);

    $res = ftp_put($ftp, $repo_path.'/'.$filename, $filepath, FTP_BINARY);

    if ($res != FTP_FINISHED) {
        error_log('Could not upload file '.$filepath.' to Alfresco: '.$repo_path.'/'.$filename);
        mtrace('$res = '.$res);
        ftp_close($ftp);
        return false;
    }

    ftp_close($ftp);

    $dir = elis_files_read_dir($uuid, $useadmin);

    if (!empty($dir->files)) {
        foreach ($dir->files as $file) {
            if ($file->title == $filename) {
                return $file;
            }
        }
    }

    return false;
}


/**
 * Perform a string search on the filenames and contents of files within the repository.
 *
 * @param string $query   The search string to use.
 * @param int    $page    The page of results to display (optional).
 * @param int    $perpage The number of results per pag (optional).
 * @return object An object containing an array of folders and file node references.
 */
function elis_files_search($query, $page = 1, $perpage = 9999) {
    $skip_count = ($page - 1) * $perpage;

    $response = elis_files_utils_invoke_service('/api/search/keyword.atom?q=' . rawurlencode($query) .
                                              '&p=' . $page . '&c=' . $perpage);
    $return   = new stdClass;
    $return->folders = array();
    $return->files   = array();

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    $sxml->registerXPathNamespace('opensearch', 'http://a9.com/-/spec/opensearch/1.1/');
    $sxml->registerXPathNamespace('D', 'http://www.w3.org/2005/Atom');
    $sxml->registerXPathNamespace('alf', 'http://www.alfresco.org/opensearch/1.0/');
    $sxml->registerXPathNamespace('relevance', 'http://a9.com/-/opensearch/extensions/relevance/1.0/');

    $feed = $sxml->xpath('/D:feed');
    $feed = $feed[0];

    $value = $feed->xpath('opensearch:totalResults');
    $return->total_items = (int)$value[0][0];

    $value = $feed->xpath('opensearch:itemsPerPage');
    $return->items_per_page = (int)$value[0][0];

/// Process each node returned in the search results.
    if ($entries = $sxml->xpath('//D:entry')) {
        foreach ($entries as $entry) {
            $node = elis_files_node_properties(str_replace('urn:uuid:', '', $entry->id));

            $value = $entry->xpath('relevance:score');
            $node->score = (float)$value[0][0];
            $value = $entry->xpath('alf:noderef');
            $node->noderef = $value[0][0];

            $type = elis_files_get_type($node->uuid);

            if ($type == ELIS_files::$type_folder) {
                $return->folders[] = $node;
            } else if ($type == ELIS_files::$type_document) {
                $return->files[] = $node;
            }
        }
    }

    return $return;
}

/**
 * Perform an ad-hoc query
 *
 * @param string $statement               The query string to use.
 * @param bool   $searchAllVersions       Toggle to search all versions (default=false) (optional).
 * @param bool   $includeAllowableActions Toggle to include allowable actions (default=false) (optional).
 * @param string $includeRelationships    The relationships to include (default=none) (optional).
 * @param int    $maxItems                The number of results to return (default=0=Repository default) (optional).
 * @param int    $skipCount               The starting position of results (default=0=Start at first position) (optional).
 * @return object An object containing an array of results.
 */
function elis_files_query($statement, $searchAllVersions = false, $includeAllowableActions = false,
                          $includeRelationships = 'none', $maxItems = 0, $skipCount = 0) {

    // TO-DO: this function is under construction

    $response = elis_files_utils_invoke_service('/api/query',
                                                'ticket',
                                                array(),
                                                'CUSTOM-POST',
                                                array("statement"=>$statement,
                                                      "searchAllVersions"=>$searchAllVersions,
                                                      "includeAllowableActions"=>$includeAllowableActions,
                                                      "includeRelationships"=>$includeRelationships,
                                                      "maxItems"=>$maxItems,
                                                      "skipCount"=>$skipCount
                                                     )
                                               );

    $return   = new stdClass;
    $return->folders = array();
    $return->files   = array();

    /*
    try {
        $sxml = new SimpleXMLElement($response);
    } catch (Exception $e) {
        debugging(get_string('badxmlreturn', 'repository_elis_files') . "\n\n$response", DEBUG_DEVELOPER);
        return false;
    }
    */

    return $return;
}

/**
 * Perform a search for all content within a specific category.
 *
 * @param array $categories An array of category database records.
 * @return object An object containing an array of folders and file node references.
 */
function elis_files_category_search($categories) {
    $return   = new stdClass;
    $return->folders = array();
    $return->files   = array();

    $nodes = array();

    foreach ($categories as $category) {
    /// Re-encoded special characters to ISO-9075 standard for Xpath.
        $search = array(
            ':',
            '_',
            ' '
        );

        $replace = array(
            '_x003A_',
            '_x005F_',
            '_x0020_'
        );

        $cattitle = str_replace($search, $replace, $category->title);
        $response = elis_files_utils_invoke_service('/moodle/categorysearch/' . $cattitle);

        $sxml = RLsimpleXMLelement($response);
        if (empty($sxml)) {
            return false;
        }

        foreach ($sxml->node as $node) {
            if (!isset($nodes["$node->uuid"])) {
                $nodes["$node->uuid"] = "$node->title";
            }
        }
    }

    if (!empty($nodes)) {
        foreach ($nodes as $uuid => $title) {
            $node = elis_files_node_properties($uuid);
            $type = elis_files_get_type($node->uuid);

            if ($type == ELIS_files::$type_folder) {
                $return->folders[] = $node;
            } else if ($type == ELIS_files::$type_document) {
                $return->files[] = $node;
            }
        }
    }

    return $return;
}


/**
 * Get the categories associated with a specific node (if any).
 *
 * NOTE: Either the noderef or UUID value needs to be specified for the node.
 *
 * @param string $noderef The noderef URI.
 * @param string $uuid    The node UUID value.
 * @return array|bool An array of category information or, False on error.
 */
function elis_files_get_node_categories($noderef = '', $uuid = '') {
    if (empty($uuid) && empty($noderef)) {
        return false;
    }

    $categories = array();

    if (empty($noderef)) {
         $properties = elis_files_node_properties($uuid);
         $noderef    = $properties->noderef;
    }

    $response = elis_files_request('/moodle/nodecategory?nodeRef=' . $noderef);

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!empty($sxml->category)) {
        foreach ($sxml->category as $category) {
            $categories["$category->uuid"] = "$category->name";
        }
    }

    return $categories;
}


/**
 * Process a SimpleXML object from the Alfresco web script XML data to make an array
 * structure usable in Moodle.
 *
 * @param SimpleXMLElement $sxml A category-level SimpleXMLElement object.
 * @return array An array of category information.
 */
function elis_files_process_categories($sxml) {
    $return = array();

    if (!empty($sxml->category)) {
        foreach ($sxml->category as $category) {
            $cat = array(
                'uuid'     => "$category->uuid",
                'name'     => "$category->name",
                'children' => array()
            );

            if (!empty($category->categories)) {
                $cat['children'] = elis_files_process_categories($category->categories);
            }

            $return[] = $cat;
        }
    }

    return $return;
}


/**
 * Get a listing of categories from the Alfresco server.
 *
 * @param none
 * @return array A nested array of category information.
 */
function elis_files_get_categories() {
    $response = elis_files_request('/moodle/categories');

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!empty($sxml->category)) {
        return elis_files_process_categories($sxml);
    }

    return array();
}

function elis_files_get_config() {
    global $CFG;

    require_once($CFG->dirroot . '/repository/elis_files/ELIS_files_factory.class.php');

    if (!$repo = repository_factory::factory()) {
        return false;
    } else {
        return $repo;
    }

}
/**
 * Process a SimpleXML object from the Alfresco web script XML data to make an array
 * structure usable in Moodle.
 *
 * @param SimpleXMLElement $sxml A folder-level SimpleXMLElement object.
 * @return array An array of folder information.
 */
function elis_files_process_folder_structure($sxml) {
    $return = array();

    if (!empty($sxml->folder)) {
        foreach ($sxml->folder as $folder) {
            $cat = array(
                'uuid'     => "$folder->uuid",
                'name'     => "$folder->name",
                'children' => array()
            );

            if (!empty($folder->folders)) {
                $cat['children'] = elis_files_process_folder_structure($folder->folders);
            }

            $return[] = $cat;
        }
    }

    return $return;
}


/**
 * Get a hierarchical folder structure from the Alfresco server.
 *
 * @param none
 * @return array A nested array of folder node data.
 */
function elis_files_folder_structure() {
    $response = elis_files_request('/moodle/folders');

    $response = preg_replace('/(&[^amp;])+/', '&amp;', $response);

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!empty($sxml->folder)) {
        return elis_files_process_folder_structure($sxml);
    }

    return array();
}


/**
 * Recursively determine whether the specified path is actually valid on the
 * configured repository.
 *
 * @param string $path
 * @return bool True if the path is valid, False otherwise.
 */
function elis_files_validate_path($path, $folders = null) {
    if ($path == '/') {
        return true;
    }

/// Remove any extraneous slashes from the ends of the string
    $path = trim($path, '/');

    $parts = explode('/', $path);

/// Initialize the folder structure if a structure piece wasn't passed to this function.
    if ($folders == null) {
        $folders = elis_files_folder_structure();
    }

/// Get the first piece from the list of path elements.
    $pathpiece = array_shift($parts);

    if (!empty($folders)) {
        foreach ($folders as $folder) {
            if ($folder['name'] == $pathpiece) {
            /// If there are no more path elements, we've succeeded!
                if (empty($parts)) {
                    return true;

            /// If there are path elements left but no children from the current
            /// folder, we've failed.
                } else if (!empty($parts) && empty($folder['children'])) {
                    return false;

            /// Otherwise, keep looking below.
                } else {
                    return elis_files_validate_path(implode('/', $parts), $folder['children']);
                }
            }
        }
    }

    return false;
}

/**
 * Process a node to return an array of values for it and determine whether it is
 * a folder or content node.
 *
 * @param DOMDocument $dom  The XML data read into a DOMDocument object.
 * @param DOMNode     $node The specific node from the document we are processing.
 * @param string      $type Refernce to a variable to store the node type.
 * @return object The node properties in an object format.
 */
function elis_files_process_node_new($node, &$type) {
    $contentNode = new stdClass;

    $contentNode->uuid    = str_replace('urn:uuid:', '', $node->uuid);
    $contentNode->summary = ''; // Not returned with CMIS data
    $contentNode->title   = $node->properties['cmis:name'];
    $contentNode->icon    = '';
    $contentNode->type    = $type = $node->properties['cmis:baseTypeId'];

    if (isset($node->properties['cmis:lastModifiedBy'])) {
        $contentNode->owner   = $node->properties['cmis:lastModifiedBy'];
    } else if (isset($node->properties['cmis:createdBy'])) {
        $contentNode->owner   = $node->properties['cmis:createdBy'];
    } else {
        $contentNode->owner   = 'none';
    }
    if (isset($node->properties['cmis:contentStreamFileName'])) {
        $contentNode->filename = $node->properties['cmis:contentStreamFileName'];
    }

    if (isset($node->properties['cmis:contentStreamLength'])) {
        $contentNode->filesize = $node->properties['cmis:contentStreamLength'];
    }

    if (isset($node->properties['cmis:contentStreamMimeType'])) {
        $contentNode->filemimetype = $node->properties['cmis:contentStreamMimeType'];
    }

    // We have to recontextualize the date & time values given to us into
    // a standard UNIX timestamp value that Moodle uses.
    $created = $node->properties['cmis:creationDate'];

    if (!empty($created)) {
        $d_year   = substr($created, 0, 4);
        $d_month  = substr($created, 5, 2);
        $d_day    = substr($created, 8, 2);
        $t_hour   = substr($created, 11, 2);
        $t_minute = substr($created, 14, 2);
        $t_second = substr($created, 17, 2);
        $tz       = substr($created, -6, 3);
        if (substr($created, -2, 2) == '30') {
            $tz .= '.5';
        } else {
            $tz .= '.0';
        }

        $contentNode->created = make_timestamp($d_year, $d_month, $d_day, $t_hour,
                                               $t_minute, $t_second, $tz);
    }

    // We have to recontextualize the date & time values given to us into
    // a standard UNIX timestamp value that Moodle uses.
    $updated = $node->properties['cmis:lastModificationDate'];

    if (!empty($updated)) {
        $d_year   = substr($updated, 0, 4);
        $d_month  = substr($updated, 5, 2);
        $d_day    = substr($updated, 8, 2);
        $t_hour   = substr($updated, 11, 2);
        $t_minute = substr($updated, 14, 2);
        $t_second = substr($updated, 17, 2);
        $tz       = substr($updated, -6, 3);
        if (substr($updated, -2, 2) == '30') {
            $tz .= '.5';
        } else {
            $tz .= '.0';
        }

        $contentNode->modified = make_timestamp($d_year, $d_month, $d_day, $t_hour,
                                                $t_minute, $t_second, $tz);
    }

    $contentNode->links['self']         = $node->links['self'];

    if (!empty($node->links['down'])) {
        $contentNode->links['children'] = $node->links['down'];
    }

    if (!empty($node->links['down-tree'])) {
        $contentNode->links['descendants'] = $node->links['down-tree'];
    }

    if (!empty($node->links['describedby'])) {
        $contentNode->links['type'] = $node->links['describedby'];
    }

    if (!empty($node->links['edit-media'])) {
        $contentNode->fileurl = $node->links['edit-media'];
    }

    return $contentNode;
}

/**
 * Process a node to return an array of values for it and determine whether it is
 * a folder or content node.
 *
 * @param DOMDocument $dom  The XML data read into a DOMDocument object.
 * @param DOMNode     $node The specific node from the document we are processing.
 * @param string      $type Refernce to a variable to store the node type.
 * @return object The node properties in an object format.
 */
function elis_files_process_node($dom, $node, &$type) {
    $xpath = new DOMXPath($dom);

    if ($node->hasChildNodes()) {
        $contentNode = new stdClass;
        $contentNode->links = array();

        foreach ($node->childNodes as $cnode) {
            if (!isset($cnode->tagName)) {
                continue;
            }

            switch ($cnode->tagName) {
                case 'id':
                    $contentNode->uuid = str_replace('urn:uuid:', '', $cnode->nodeValue);
                    break;

                case 'author':
                    $contentNode->owner = $cnode->nodeValue;

                case 'published':
                    $created = $cnode->nodeValue;

                /// We have to recontextualize the date & time values given to us into
                /// a standard UNIX timestamp value that Moodle uses.
                    if ($created != '-') {
                        $d_year   = substr($created, 0, 4);
                        $d_month  = substr($created, 5, 2);
                        $d_day    = substr($created, 8, 2);
                        $t_hour   = substr($created, 11, 2);
                        $t_minute = substr($created, 14, 2);
                        $t_second = substr($created, 17, 2);
                        $tz       = substr($created, -6, 3);
                        if (substr($created, -2, 2) == '30') {
                            $tz .= '.5';
                        } else {
                            $tz .= '.0';
                        }

                        $contentNode->created = make_timestamp($d_year, $d_month, $d_day, $t_hour,
                                                               $t_minute, $t_second, $tz);
                    }

                    break;

                case 'updated':
                    $updated = $cnode->nodeValue;

                /// We have to recontextualize the date & time values given to us into
                /// a standard UNIX timestamp value that Moodle uses.
                    if ($updated != '-') {
                        $d_year   = substr($updated, 0, 4);
                        $d_month  = substr($updated, 5, 2);
                        $d_day    = substr($updated, 8, 2);
                        $t_hour   = substr($updated, 11, 2);
                        $t_minute = substr($updated, 14, 2);
                        $t_second = substr($updated, 17, 2);
                        $tz       = substr($updated, -6, 3);
                        if (substr($updated, -2, 2) == '30') {
                            $tz .= '.5';
                        } else {
                            $tz .= '.0';
                        }

                        $contentNode->modified = make_timestamp($d_year, $d_month, $d_day, $t_hour,
                                                                $t_minute, $t_second, $tz);
                    }

                    break;

                case 'summary':
                    $contentNode->summary = $cnode->nodeValue;
                    break;

                case 'title':
                    $contentNode->title = $cnode->nodeValue;
                    break;

                case 'alf:icon':
                    $contentNode->icon = $cnode->nodeValue;
                    break;

                case 'link':
                    switch ($cnode->getAttribute('rel')) {
                        case 'self':
                            $contentNode->links['self'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-allowableactions':
                            $contentNode->links['permissions'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-relationships':
                            $contentNode->links['associations'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-parent':
                            $contentNode->links['parent'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-folderparent':
                            $contentNode->links['folderparent'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-children':
                            $contentNode->links['children'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-descendants':
                            $contentNode->links['descendants'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-type':
                            $contentNode->links['type'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        case 'cmis-repository':
                            $contentNode->links['repository'] = str_replace(elis_files_base_url(), '', $cnode->getAttribute('href'));
                            break;

                        default:
                            break;
                    }

                    break;

                default:
                    break;
            }

        }
    }

    $entries    = $xpath->query('.//cmis:properties/*', $node);
//    $isfolder   = false;
//    $isdocument = false;
    $j          = 0;

    while ($prop = $entries->item($j++)) {
    /// Sloppily handle strict namespacing here.
        if ($prop->getAttribute('cmis:name') == 'BaseType' ||
            $prop->getAttribute('name') == 'BaseType') {
            $type = $prop->nodeValue;
        } else {
            $propname = $prop->getAttribute('cmis:name');

            if (empty($propname)) {
                $propname = $prop->getAttribute('name');
            }

            if (!empty($propname)) {
                switch ($propname) {
                    case 'ObjectId':
                        $contentNode->noderef = $prop->nodeValue;
                        break;

                    case 'BaseType':
                        if ($prop->nodeValue == ELIS_files::$type_folder) {
                            $type = $prop->nodeValue;
                        } else if ($prop->nodeValue == ELIS_files::$type_document) {
                            $type = $prop->nodeValue;
                        }

                        break;

                    case 'ContentStreamLength':
                        $contentNode->filesize = $prop->nodeValue;
                        break;

                    case 'ContentStreamMimeType':
                        $contentNode->filemimetype = $prop->nodeValue;
                        break;

                    case 'ContentStreamFilename':
                        $contentNode->filename = $prop->nodeValue;
                        break;

                    case 'ContentStreamURI':
                    case 'ContentStreamUri':
                        $contentNode->fileurl = $prop->nodeValue;
                        break;

                    default:
                        break;
                }
            }

            if (isset($prop->nodeValue) && $prop->nodeValue == ELIS_files::$type_folder) { // Added for Moodle 2.1 as this seems to be the only way to find folders
                $type = $prop->nodeValue;
            } else if (isset($prop->nodeValue) && $prop->nodeValue == ELIS_files::$type_document) { // Added for Moodle 2.1 as this seems to be the only way to find folders
                $type = $prop->nodeValue;
            }
        }
    }

    return $contentNode;
}

/**
 * Move the contents of a root Alfresco node from one location to another.
 *
 * @param string $fromuuid The UUID of the current root Alfresco node.
 * @param string $touuid   The UUID of the desctination root Alfresco node.
 * @return bool True on success, False otherwise.
 */
function elis_files_root_move($fromuuid, $touuid) {
    $response = elis_files_request('/moodle/movenode/' . $fromuuid . '/' . $touuid);

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

/// Verify the return status of the web script.
    if (!empty($sxml->status)) {
        return ("$sxml->status" == 'true') ? true: false;
    }

    return false;
}


/**
 * Move a file or directory somewhere else within the repository.
 *
 * @param string $fileuuid The UUID of the file content node.
 * @param string $touuid   The UUID of the desctination root Alfresco node.
 * @return bool True on success, False otherwise.
 */
function elis_files_move_node($fromuuid, $touuid) {
    $response = elis_files_request('/moodle/movenode/' . $fromuuid . '/' . $touuid);

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

/// Verify the return status of the web script.
    if (!empty($sxml->status)) {
        return ("$sxml->status" == 'true') ? true: false;
    }

    return false;
}


/**
 * Create a new Alfresco account.
 *
 * @param object $user     A Moodle user object.
 * @param string $password A password value to use for this user.
 * @return bool True on success, False otherwise.
 */
function elis_files_create_user($user, $password = '') {
    global $CFG;

    $username = elis_files_transform_username($user->username);

    $result = elis_files_request('/api/people/' . $username);

    // If a user account in Alfresco already exists, return true.
    if (empty($password) && $result !== false) {
        if ($person = elis_files_json_parse($result)) {
            return true;
        }
    }

    // We need to create a new account now.
    $newuser = array(
        'username'     => $username,
        'firstname'    => $user->firstname,
        'lastname'     => $user->lastname,
        'email'        => $user->email,
        'organization' => $user->institution
    );

    // Specify the password if it was supplied here.
    if (!empty($password)) {
        $newuser['password'] = $password;
    }

    if (!empty(elis::$config->elis_files->user_quota)) {
        $newuser['quota'] = elis::$config->elis_files->user_quota;
    }

    if (!$response = elis_files_send('/moodle/createuser', $newuser, 'POST')) {
        return false;
    }

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    // Verify the correct return results.
    return (!empty($sxml->username) && !empty($sxml->firstname) && !empty($sxml->lastname) && !empty($sxml->email));
}


/**
 * Delete an Alfresco user account, optionally
 *
 * @param string $username      The Alfresco account username to delete.
 * @param bool   $deletehomedir Set to true to delete the user's home directory.
 * @return bool True on success, False otherwise.
 */
function elis_files_delete_user($username, $deletehomedir = false) {
    global $CFG;

    $status = true;

    $username = elis_files_transform_username($username);

    if ($deletehomedir) {
        $uuid = elis_files_get_home_directory($username);
    }

    $status = (elis_files_send('/api/people/' . $username, array(), 'DELETE') !== false);

    // Actually go through with deleting the home directory if it was requested and we found the UUID.
    if (!empty($uuid)) {
        $status = $status && elis_files_delete($uuid, true);
    }

    return $status;
}


/**
 * Get a user's home directory UUID.
 *
 * @uses $CFG
 * @param string $username The Moodle / Alfresco username to fetch a home directory UUID for.
 * @return string|bool The home directory UUID value or, False on error.
 */
function elis_files_get_home_directory($username) {
    global $CFG;

    $username = elis_files_transform_username($username);

    $response = elis_files_request('/moodle/homedirectory?username=' . $username);

    // Pull out the UUID value from the XML response.
    preg_match('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $response, $matches);

    if (!empty($matches) && is_array($matches) && count($matches) === 1) {
        return current($matches);
    }

    return false;
}


/**
 * Determine if a user has permission to access a specific node.  If a username is not specified,
 * the username of the currently logged in user will be used instead.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $uuid     The node UUID value.
 * @param string $username A Moodle / Alfresco username (optional).
 * @param bool   $edit     Set to True to check for an editing capability.
 * @return bool True if the user has access, False if not.
 */
function elis_files_has_permission($uuid, $username = '', $edit = false) {
    global $CFG, $USER;

    // If no username was specified, make sure that there is a user currently logged in and use that username.
    if (empty($username)) {
        if (!isloggedin()) {
            return false;
        }

        $username = $USER->username;
    }

    $username = elis_files_transform_username($username);

    $response = elis_files_request('/moodle/getpermissions/' . $uuid . '?username=' . $username);

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!isset($sxml->permission)) {
        return false;
    }

    if (!is_array($sxml->permission)) {
        $permissions = array($sxml->permission);
    } else {
        $permissions = $sxml->permission;
    }

    // Process the returned permission XML data to check for allowed permission on this node for the requested user.
    foreach ($permissions as $permission) {
        $pname = '';  // The role name
        $pfor  = '';  // The username the permission is for
        $pcap  = '' . $permission;  // The permission capability.

        foreach ($permission->attributes() as $var => $val) {
            if ($var == 'name') {
                $pname = $val;
            } else if ($var == 'for') {
                $pfor = $val;
            }
        }

        if ($edit && $pname !== ELIS_FILES_ROLE_COLLABORATOR) {
            continue;
        }

        // Make sure that this user or everyone on the site can access the specified node.
        if (($pfor == $username || $pfor == 'GROUP_EVERYONE') && $pcap == ELIS_FILES_CAPABILITY_ALLOWED) {
            return true;
        }
    }
}


/**
 * Get a list of all the permissions a user has set to "ALLOW" on a specific node.  If a username is not specified,
 * the username of the currently logged in user will be used instead.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $uuid     The node UUID value.
 * @param string $username A Moodle / Alfresco username (optional).
 * @return bool True if the user has access, False if not.
 */
function elis_files_get_permissions($uuid, $username = '') {
    global $CFG, $USER;

    // If no username was specified, make sure that there is a user currently logged in and use that username.
    if (!empty($username)) {
        $username = elis_files_transform_username($username);
    }

    $permissions = array();

    $response = elis_files_request('/moodle/getpermissions/' . $uuid . (!empty($username) ? '?username=' . $username : ''));

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!isset($sxml->permission)) {
        return false;
    }

    if (!is_array($sxml->permission)) {
        $permissions = array($sxml->permission);
    } else {
        $permissions = $sxml->permission;
    }

    // Process the returned permission XML data to check for allowed permission on this node for the requested user.
    foreach ($permissions as $permission) {
        $pname = '';  // The role name
        $pfor  = '';  // The username the permission is for
        $pcap  = '' . $permission;  // The permission capability.

        foreach ($permission->attributes() as $var => $val) {
            if ($var == 'name') {
                $pname = '' . $val;
            } else if ($var == 'for') {
                $pfor = '' . $val;
            }
        }

        // Make sure that this user or everyone on the site can access the specified node.
        if ((!empty($username) && $pfor == $username && $pcap == ELIS_FILES_CAPABILITY_ALLOWED) ||
            ($pcap == ELIS_FILES_CAPABILITY_ALLOWED)) {

            $permissions[] = $pname;
        }
    }

    return $permissions;
}


/**
 * Assign user permission on a specific node in Alfresco
 *
 * The valid $role values and meaning are as follows:
 * - Coordinator
 *   - The coordinator gets all permissions and permission groups defined.
 * - Collaborator
 *   - Combines Editor and Contributor permission groups.
 * - Contributor
 *   - Includes the Consumer permission group and adds AddChildren and CheckOut.
 *     They will, by default own anything they create and have the ROLE_OWNER authority.
 * - Editor
 *   - Includes the Consumer permission group and adds Write and CheckOut.
 * - Consumer
 *   - Includes Read
 *
 * @uses $CFG
 * @param string $username   The Alfresco username value.
 * @param string $uuid       The node UUID value.
 * @param string $role       The capability name being assigned for this user.
 * @param string $capability Either ALLOWED or DENIED.
 * @return bool True on success, False otherwise.
 */
function elis_files_set_permission($username, $uuid, $role, $capability) {
    global $CFG;

    switch ($role) {
        case ELIS_FILES_ROLE_COORDINATOR:
        case ELIS_FILES_ROLE_COLLABORATOR:
        case ELIS_FILES_ROLE_CONTRIBUTOR:
        case ELIS_FILES_ROLE_EDITOR:
        case ELIS_FILES_ROLE_CONSUMER:
            break;

        default:
            return false;
    }

    $capability = strtoupper($capability);  // Just in case.

    switch ($capability) {
        case ELIS_FILES_CAPABILITY_ALLOWED:
        case ELIS_FILES_CAPABILITY_DENIED:
            break;

        default:
            return false;
    }

    $username = elis_files_transform_username($username);

    $postdata = array(
        'username'   => $username,
        'name'       => $role,
        'capability' => $capability
    );

    $response = elis_files_send('/moodle/setpermissions/' . $uuid, $postdata, 'POST');

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    if (!isset($sxml->permission)) {
        return false;
    }

    if (!is_array($sxml->permission)) {
        $permissions = array($sxml->permission);
    } else {
        $permissions = $sxml->permission;
    }

    // NOTE: we need to "cast" values from SimpleXML so that they aren't accidentally stored as objects.

    // Process the returned permission XML data to check for allowed permission on this node for the requested user.
    $found = false;

    foreach ($permissions as $permission) {
        if ($found) {
            continue;
        }

        $pname = '';  // The role name
        $pfor  = '';  // The username the permission is for
        $pcap  = '' . $permission;  // The permission capability.

        foreach ($permission->attributes() as $var => $val) {
            if ($var == 'name') {
                $pname = '' . $val;
            } else if ($var == 'for') {
                $pfor = '' . $val;
            }
        }

        // Make sure that this user or everyone on the site can access the specified node.
        if ($pfor == $username && $pname == $role && $pcap == ELIS_FILES_CAPABILITY_ALLOWED) {
            $found = true;
        }
    }

    // Check for the two possible correct results depending on whether we are allowing or denying this
    if (($found && $capability == ELIS_FILES_CAPABILITY_ALLOWED) ||
        (!$found && $capability == ELIS_FILES_CAPABILITY_DENIED)) {

        return true;
    }

    return false;
}


/**
 * Rename a node in Alfresco, keeping all other data intact.
 *
 * @param string $uuid    The node UUID value.
 * @param string $newname The new name value for the node.
 * @return bool True on success, False otherwise.
 */
function elis_files_node_rename($uuid, $newname) {
    $postdata = array(
        'name' => $newname
    );

    $response = elis_files_send('/moodle/noderename/' . $uuid, array('name' => $newname), 'POST');

    $sxml = RLsimpleXMLelement($response);
    if (empty($sxml)) {
        return false;
    }

    return ($sxml->uuid == $uuid && $sxml->name == $newname);
}


/**
 * Get the current quota information for a user from the Alfresco server.
 *
 * The return object contains two properties as follows:
 *  quota   - The maximum amount of data the user can have in the system (in bytes)
 *  current - The current amount of data that this user has allocated (in bytes)
 *
 * NOTE: no quota is represented by a value of -1 for the 'quota' property.
 *
 * @uses $CFG
 * @uses $USER
 * @param string $username A Moodle username.
 * @return object|bool An object containing the quota values for this user or, False on error.
 */
function elis_files_quota_info($username = '') {
    global $CFG, $USER;

    if (empty($username)) {
        $username = $USER->username;
    }

    $username = elis_files_transform_username($username);

    // Get the JSON response containing user data for the given account.
    if (($json = elis_files_request('/api/people/' . $username)) === false) {
        return false;
    }

    $userdata = elis_files_json_parse($json);

    if (!isset($userdata->quota) || !isset($userdata->sizeCurrent)) {
        return false;
    }

    $userquota = new stdClass;
    $userquota->quota   = $userdata->quota;
    $userquota->current = $userdata->sizeCurrent;

    return $userquota;
}


/**
 * Check if a given file size (in bytes) will run over a user's quota limit on Alfresco.
 *
 * @uses $USER
 * @param unknown_type $size
 * @param unknown_type $user
 * @return bool True if the size will not run over the user's quota, False otherwise.
 */
function elis_files_quota_check($filesize, $user = null) {
    global $USER;

    if ($user == null) {
        $user = $USER;
    }

    if (($userdata = elis_files_quota_info($user->username)) === false) {
        return false;
    }

    // If the user has no quota set or the filesize will not run over their current quota, return true.
    return ($userdata->quota == -1 || ($userdata->current + $filesize <= $userdata->quota));
}


/**
 * Get the UUID value of the web scripts extension directory.
 *
 * The path is as follows /Data Dictionary/Web Scripts Extensions/
 *
 * @param bool $moodle Whether to get the UUID of the Moodle web scripts folder (optional)
 * @return string|bool The node UUID value on succes, False otherwise.
 */
function elis_files_get_web_scripts_dir($moodle = false) {
    $dir = elis_files_read_dir();

    if (empty($dir->folders)) {
        return false;
    }

    foreach ($dir->folders as $folder) {
        if ($folder->title == 'Data Dictionary') {
            $dir = elis_files_read_dir($folder->uuid);

            if (empty($dir->folders)) {
                return false;
            }

            foreach ($dir->folders as $folder) {
                if ($folder->title == 'Web Scripts Extensions') {
                    if (!$moodle) {
                        return $folder->uuid;
                    }

                    $dir = elis_files_read_dir($folder->uuid);

                    if (empty($dir->folders)) {
                        return false;
                    }

                    foreach ($dir->folders as $folder) {
                        if ($folder->title == 'org') {
                            $dir = elis_files_read_dir($folder->uuid);

                            if (empty($dir->folders)) {
                                return false;
                            }

                            foreach ($dir->folders as $folder) {
                                if ($folder->title == 'alfresco') {
                                    $dir = elis_files_read_dir($folder->uuid);

                                    if (empty($dir->folders)) {
                                        return false;
                                    }

                                    foreach ($dir->folders as $folder) {
                                        if ($folder->title == 'moodle') {
                                            debugging('Returning UUID for ' . $folder->title . ': ' . $folder->uuid);
                                            return $folder->uuid;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return false;
}


/**
 * Move files for a web script into the Alfresco repository and refresh the list of currently
 * installed web scripts.
 *
 * @param $CFG
 * @param array  $files An array of full file paths to install.
 * @param string $uuid  The node UUID to install the files in.
 * @return bool True on success, False otherwise.
 */
function elis_files_install_web_script($files, $uuid) {
    global $CFG;

    $status = true;

    if (!is_array($files)) {
        debugging('Not array');
        return false;
    }

    foreach ($files as $file) {
        if (!is_file($file)) {
            debugging('Not a file: ' . $file);
            return false;
        }

        $status = $status && elis_files_upload_file('', $file, $uuid);
    }

    if ($status) {
        sleep(2);
        $url = elis_files_base_url() . '/?reset=on';

    /// Prepare curl session
        $session = curl_init($url);

        curl_setopt($session, CURLOPT_VERBOSE, true);

    /// Don't return HTTP headers. Do return the contents of the call
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_POST, true);

    /// Make the call
        $return_data = curl_exec($session);

    /// Get return http status code
        $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

    /// Close HTTP session
        curl_close($session);

        if ($httpcode !== 200) {
            debugging('HTTP code: ' . $httpcode);
            debugging($return_data);
            $status = false;
        }
    }

    return $status;
}


/**
 * Helper function to handle decoding
 *
 * @uses $CFG
 * @param string $json JSON-encoded data.
 * @return mixed|bool The decoded JSON data or, False on error.
 */
function elis_files_json_parse($json) {
    global $CFG;

    if (($return = json_decode($json)) == null) {
        return false;
    }

    return $return;
}


/**
 * Simply sort the results from an elis_files_read_dir() API call.  This call is
 * fed into usort().
 *
 * @see usort()
 */
function elis_files_ls_sort($a, $b) {
    return strcmp($a->title, $b->title);
}


/**
 * Utility function for generating HTTP Basic Authentication header.
 *
 * @uses $CFG
 * @param string $username The username to use for user authentication.
 * @return array A basic authentication HTTP header array.
 **/
function elis_files_utils_get_auth_headers($username = '') {
    global $CFG;

    if (ELIS_FILES_DEBUG_TRACE) print_object('elis_files_utils_get_auth_headers(' . $username . ')');

    if (!empty($username)) {
        $username = elis_files_transform_username($username);
        $user = $username;
        $pass = 'password';
    } else {
        $user = elis::$config->elis_files->server_username;
        $pass = elis::$config->elis_files->server_password;
    }

    // We must include the tenant portion of the username here if it is not already included.
    if ($user != elis::$config->elis_files->server_username) {
        if (($tenantpos = strpos(elis::$config->elis_files->server_username, '@')) > 0) {
            $tenantname = substr(elis::$config->elis_files->server_username, $tenantpos);

            if (strpos($user, $tenantname) === false) {
                $user .= $tenantname;
            }
        }
    }

    return array('Authorization' => 'Basic ' . base64_encode($user . ':' . $pass));
}


/**
 * Utility function for getting alfresco ticket.
 *
 * @uses $CFG
 * @param string $op       Option for refreshing ticket or not.
 * @param string $username The username to use for user authentication.
 * @return string|bool The ticket value or, False on error.
 */
function elis_files_utils_get_ticket($op = 'norefresh', $username = '') {
    global $CFG;

    if (ELIS_FILES_DEBUG_TRACE) print_object('elis_files_utils_get_ticket(' . $op . ', ' . $username . ')');

    $alf_username = !empty($_SESSION['elis_files_username']) ? $_SESSION['elis_files_username'] : NULL;
    $alf_ticket   = !empty($_SESSION['elis_files_ticket']) ? $_SESSION['elis_files_ticket'] : NULL;

    if (!empty($username)) {
        $username = elis_files_transform_username($username);
        $user = $username;
        $pass = 'password';
    } else {
        if (!isset(elis::$config->elis_files->server_username) || !isset(elis::$config->elis_files->server_password)) {
            return false;
        }

        $user = elis::$config->elis_files->server_username;
        $pass = elis::$config->elis_files->server_password;
    }

    // We must include the tenant portion of the username here if it is not already included.
    if ($user != elis::$config->elis_files->server_username) {
        if (($tenantpos = strpos(elis::$config->elis_files->server_username, '@')) > 0) {
            $tenantname = substr(elis::$config->elis_files->server_username, $tenantpos);

            if (strpos($user, $tenantname) === false) {
                $user .= $tenantname;
            }
        }
    }

    // Make sure that we refresh the ticket if we're authenticating with a different user than what
    // the current ticket was generated for.
    if (empty($alf_username) || (!empty($alf_username) && $alf_username !== $user)) {
        $op = 'refresh';
    }

    if ($alf_ticket == NULL || $op == 'refresh') {
        // Authenticate and store the ticket
        $url = elis_files_utils_get_url('/api/login?u=' . urlencode($user) . '&pw=' . urlencode($pass));

        // Prepare curl session
        $session = curl_init($url);

        if (ELIS_FILES_DEBUG_TRACE) {
            curl_setopt($session, CURLOPT_VERBOSE, true);
        }

        // Don't return HTTP headers. Do return the contents of the call
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_USERPWD, "$user:$pass");

        // Make the call
        $return_data = curl_exec($session);

        // Get return http status code
        $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Close HTTP session
        curl_close($session);

        if ($httpcode == 200 || $httpcode == 201) {
            $sxml = RLsimpleXMLelement($return_data);
            if (empty($sxml)) {
                return false;
            }
            $alf_ticket = current($sxml);
        } else {
            return false;

            debugging(get_string('errorreceivedfromendpoint', 'repository_elis_files') .
                      (!empty($response->code) ? $response->code . ' ' : ' ') .
                      $response->error, DEBUG_DEVELOPER);
        }

        if ($alf_ticket == '') {
            debug(get_string('unabletoauthenticatewithendpoint', 'repository_elis_files'), DEBUG_DEVELOPER);
        }

        $_SESSION['elis_files_ticket']   = $alf_ticket;
        $_SESSION['elis_files_username'] = $user;
    }

    return !empty($alf_ticket) ? $alf_ticket : false;
}


/**
 * Return service url for HTTP basic authentication.
 *
 * @param string $url The service URL to make complete
 * @return string A concatenation of the 'base' URL with the service URL.
 **/
function elis_files_utils_get_url($url) {
    return elis_files_base_url() . $url;
}


/**
 * Return service url for ticket based authentication.
 * $op Option for refreshing ticket or not.
 * @param string $username The username to use for user authentication.
 */
function elis_files_utils_get_wc_url($url, $op = 'norefresh', $username = '') {

    $endpoint = elis_files_base_url();
    $ticket   = elis_files_utils_get_ticket($op, $username);

    if (false === strstr($url, '?') ) {
        return $endpoint . $url . '?alf_ticket=' . $ticket;
    } else {
        return $endpoint . str_replace('?', '?alf_ticket=' . $ticket . '&', $url);
    }
}


/**
 * Invoke Alfresco Webscript based Service.
 * $op Option for service authentication.
 * 'ticket' is for ticket based and 'basic' for http basic authentication.
 * @param string $username The username to use for user authentication.
 */
function elis_files_utils_invoke_service($serviceurl, $op = 'ticket', $headers = array(), $method = 'GET',
                                       $data = NULL, $username = '', $retry = 3) {

   global $CFG;

    if (ELIS_FILES_DEBUG_TRACE) print_object('elis_files_utils_invoke_service(' . $serviceurl . ', ' . $op . ', ' .
                                           'array(), ' . $method . ', $data, ' . $username . ', ' . $retry . ')');

    // We must include the tenant portion of the username here if it is not already included.
//    if ($username != $CFG->repository_elis_files_server_username) {
//        if (($tenantpos = strpos($CFG->repository_elis_files_server_username, '@')) > 0) {
//            $tenantname = substr($CFG->repository_elis_files_server_username, $tenantpos);
//
//            if (strpos($username, $tenantname) === false) {
//                $username .= $tenantname;
//            }
//        }
//    }

    $response = elis_files_utils_http_request($serviceurl, $op, $headers, $method, $data, $username, $retry);

    if ($response->code == 200 || $response->code == 201 || $response->code == 204) {
        $content = $response->data;
        if (false === strstr($content, 'Alfresco Web Client - Login') ) {
            if ($response->code == 204 && $method == 'CUSTOM-DELETE') {
                return true;
            } else {
                return $content;
            }
        } else {
            $response2 = elis_files_utils_http_request($serviceurl, 'refresh', $headers, $method, $data, $username, $retry);

            if ($response2->code == 200 || $response->code == 201) {
                return $response2->data;
            } else {
                $a = new stdClass;
                $a->serviceurl = $serviceurl;
                $a->code       = $response2->code;
                if (ELIS_FILES_DEBUG_TRACE) print_object(get_string('failedtoinvokeservice', 'repository_elis_files', $a));
                if (ELIS_FILES_DEBUG_TRACE && $CFG->debug == DEBUG_DEVELOPER) print_object($response->data);

                return false;
            }
        }
    } else if ($response->code == 302 || $response->code == 505 || $response->code == 401) {
        $response2 = elis_files_utils_http_request($serviceurl, 'refresh', $headers, $method, $data, $username, $retry);

        if ($response2->code == 200 || $response->code == 201) {
            return $response2->data;

        } else {
            $a = new stdClass;
            $a->serviceurl = $serviceurl;
            $a->code       = $response2->code;
            if (ELIS_FILES_DEBUG_TRACE) print_object(get_string('failedtoinvokeservice', 'repository_elis_files', $a));
            if (ELIS_FILES_DEBUG_TRACE && $CFG->debug == DEBUG_DEVELOPER) print_object($response->data);

            return false;
        }
    } else {
        $a = new stdClass;
        $a->serviceurl = $serviceurl;
        $a->code       = $response->code;
        if (ELIS_FILES_DEBUG_TRACE) print_object(get_string('failedtoinvokeservice', 'repository_elis_files', $a) .
                                               (!empty($response->error) ? ' ' . $response->error : ''));
        if (ELIS_FILES_DEBUG_TRACE && $CFG->debug == DEBUG_DEVELOPER) print_object($response->data);

        return false;
    }
}


/**
 * Use Curl to send the request to the Alfresco repository.
 *
 * @see elis_files_utils_invoke_service
 */
function elis_files_utils_http_request($serviceurl, $auth = 'ticket', $headers = array(),
                                     $method = 'GET', $data = NULL, $username = '', $retry = 3) {

    global $CFG;

    switch ($auth) {
        case 'ticket':
        case 'refresh':
            $url = elis_files_utils_get_wc_url($serviceurl, $auth, $username);
            break;

        case 'basic':
            $url     = elis_files_utils_get_url($serviceurl);
            $hauth   = elis_files_utils_get_auth_headers($username);
            $headers = array_merge($hauth, $headers);
            break;

        default:
            return false;
    }

/// Prepare curl sessiontoge
    $session = curl_init($url);

    if (ELIS_FILES_DEBUG_TRACE) {
        curl_setopt($session, CURLOPT_VERBOSE, true);
    }

/// Add additonal headers
    curl_setopt($session, CURLOPT_HTTPHEADER, $headers);

/// Don't return HTTP headers. Do return the contents of the call
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    if ($auth == 'basic') {
        $user = elis::$config->elis_files->server_username;
        $pass = elis::$config->elis_files->server_password;

        curl_setopt($session, CURLOPT_USERPWD, "$user:$pass");
    }

    if ($method == 'CUSTOM-POST') {
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt($session, CURLOPT_POSTFIELDS, $data);
    }

    if ($method == 'CUSTOM-PUT') {
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt($session, CURLOPT_POSTFIELDS, $data);
    }

    if ($method == 'CUSTOM-DELETE') {
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($session, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($session, CURLOPT_ERRORBUFFER, 1);
    }

    // Only wait 10 seconds before considering the connection to have timed out.
    curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 10);

/// Make the call
    $return_data = curl_exec($session);

/// Get return http status code
    $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

/// Close HTTP session
    curl_close($session);

    // Prepare return
    $result = new stdClass();
    $result->code = $httpcode;
    $result->data = $return_data;

    return $result;
}

/**
 * Function to transform Moodle username to Alfresco username
 * converting 'admin' user and adding tenant info.
 * Check to make sure username has NOT already been transformed
 *
 * @param  $username the username to transform
 * @uses   $CFG
 * @uses   $USER
 * @return string    the transformed username.
 */
function elis_files_transform_username($username) {
    global $CFG, $USER;

    $tenantpos = strpos(elis::$config->elis_files->server_username, '@');
    if ($username == 'admin' || empty($USER->username) || $USER->username == $username ||
        (($atpos = strpos($username, '@')) !== false &&
          ($tenantpos === false ||
           strcmp(substr($username, $atpos),
                  substr(elis::$config->elis_files->server_username, $tenantpos))))
       || ($atpos === false && $tenantpos !== false)) {

        // Fix username
        $username = ELIS_files::alfresco_username_fix($username);

        // So that we don't conflict with the default Alfresco admin account.
        $username = ($username == 'admin')
                    ? elis::$config->elis_files->admin_username : $username;

        // We must include the tenant portion of the username here.
        if ($tenantpos > 0) {
            $username .= substr(elis::$config->elis_files->server_username, $tenantpos);
        }
    }
    return $username;
}

/**
 * Recursively get the path to a specific node (folder or document) from the root of the repository down.
 *
 * @param string $uuid The UUID of the node we are processing
 * @param string $path The assmebled path we are building (empty to start)
 * @return string The path to the requested location from the root of the repository
 */
function elis_files_node_path($uuid, $path = '') {
    // If we're starting out and the current node is a folder, include it in the path
    if (empty($path)) {
        $node = elis_files_node_properties($uuid);
        if ($node->type == ELIS_files::$type_folder) {
            $path = $node->title;
        }
    }

    // Get the parents for the given node
    $response = elis_files_request('/cmis/i/'.$uuid.'/parents');

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($response);

    $nodes = $dom->getElementsByTagName('entry');

    if ($nodes->length > 0) {
        // This node has parents so add this node title to the path and recurse on the parent node
        $type = '';
        $node = elis_files_process_node($dom, $nodes->item(0), $type);
        $path = $node->title.(!empty($path) ? '/'.$path : '');
        return elis_files_node_path($node->uuid, $path);
    } else {
        // This node has no parents, we're at the root so prepend a slash and return everything
        return '/'.$path;
    }
}

/**
 * Method to return authentication methods that DO NOT use passwords
 *
 * @return array  list of authentications that DO NOT use passwords
 */
function elis_files_nopasswd_auths() {
    // TBD: determine from auth plugin which don't support passwords ???
    return array('openid', 'cas');
}
