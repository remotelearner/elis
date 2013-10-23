<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * repository_elis_files class
 * This is a class used to browse files from alfresco
 *
 * @since      2.0
 * @package    repository_elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) .'/../lib.php');

// Define constants for the default file browsing location.
defined('ELIS_FILES_BROWSE_SITE_FILES') or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
// Was shared now called server files
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES') or define('ELIS_FILES_BROWSE_USER_FILES',   50);
defined('ELIS_FILES_BROWSE_USERSET_FILES') or define('ELIS_FILES_BROWSE_USERSET_FILES', 60);

defined('ELIS_FILES_SELECT_ALFRESCO_VERSION') or define('ELIS_FILES_SELECT_ALFRESCO_VERSION', null);
defined('ELIS_FILES_ALFRESCO_30') or define('ELIS_FILES_ALFRESCO_30',   '3.2');
defined('ELIS_FILES_ALFRESCO_34') or define('ELIS_FILES_ALFRESCO_34',   '3.4');

// Setup options for the method to transfer files into Alfresco from Moodle
defined('ELIS_FILES_XFER_WS') || define('ELIS_FILES_XFER_WS', 'webservices');
defined('ELIS_FILES_XFER_FTP') || define('ELIS_FILES_XFER_FTP', 'ftp');

defined('ELIS_FILES_CURL_CONNECT_TIMEOUT') || define('ELIS_FILES_CURL_CONNECT_TIMEOUT', '3');
defined('ELIS_FILES_CURL_RESPONSE_TIMEOUT') || define('ELIS_FILES_CURL_RESPONSE_TIMEOUT', '0');

class repository_elis_files extends repository {
    private $ticket = null;
    private $user_session = null; // probably don't need this
    private $store = null;
    public $elis_files;
    var $config   = null; // Config options for ELIS files


    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION, $CFG, $DB, $PAGE;
        if (!is_numeric($repositoryid)) {
            // ELIS-8550: were constructing these with repositoryid = 'elis_files'
            $sql = 'SELECT MIN(ri.id)
                      FROM {repository} r
                      JOIN {repository_instances} ri
                        ON r.id = ri.typeid
                     WHERE r.type = ?';
            $repositoryid = $DB->get_field_sql($sql, array($repositoryid));
        }
        parent::__construct($repositoryid, $context, $options);

        require_once dirname(__FILE__). '/ELIS_files_factory.class.php';

        if (is_object($context)) {
            $this->context = $context;
        } else {
            $this->context = get_context_instance_by_id($context);
        }

        /// ELIS files class
        $this->elis_files = repository_factory::factory();
        $this->config = get_config('elis_files');
        $this->current_node = null;

        // jQuery files required for file picker - just for this repository
        $PAGE->requires->js('/repository/elis_files/js/jquery-1.6.2.min.js');
        $PAGE->requires->js('/repository/elis_files/js/jquery-ui-1.8.16.custom.min.js');
        $PAGE->requires->js('/repository/elis_files/js/fileuploader.js');
        $PAGE->requires->js('/repository/elis_files/lib/HTML_TreeMenu-1.2.0/TreeMenu.js', true);
    }

    private function get_url($node) {
        global $CFG;

        // Verify that we have the repo!
        if (!$this->elis_files->get_defaults()) {
            return false;
        }

        $result = null;
        if (isset($node->type) && $node->type == ELIS_FILES_TYPE_DOCUMENT) {
            $result = $node->fileurl;
        } else {
            $result = $this->get_link($node->uuid);
        }
        return $result;
    }

    /**
     * Static method to build encoded path from components
     *
     * @param string $uuid   ELIS files path
     * @param int    $uid    user id (default: 0)
     * @param int    $cid    course id (default: 0)
     * @param int    $oid    userset id (default: 0)
     * @param bool   $shared shared flag (default: false)
     * @return string the encodedpath
     */
    public static function build_encodedpath($uuid, $uid = 0, $cid = 0, $oid = 0, $shared = false) {
        $params = array('path'   => $uuid,
                        'shared' => (boolean)$shared,
                        'oid'    => (int)$oid,
                        'cid'    => (int)$cid,
                        'uid'    => (int)$uid);
        return base64_encode(serialize($params));
    }

    /**
     * Build a list of "unbiased" encoded UUIDs, starting from the top "ELIS Files"
     * node, up to an including the specified node
     *
     * @param array $parent_path Top-down list of parent UUIDs, not including
     *                           the current node, encoded
     * @param string $uuid The UUID of the current node, not encoded
     * @return array An array of encoded UUIDs, starting from the top, down to the
     *               specified node
     */
    static function encode_path_uuids($parent_path, $uuid) {
        //TODO: add some way to encode the parent path items, if needed later
        global $CFG;
        require_once($CFG->dirroot.'/repository/elis_files/lib.php');

        $result = array();

        // Include parent items
        foreach ($parent_path as $path_item) {
            $result[] = $path_item['path'];
        }

        // Add the encoded version of the current node
        $result[] = repository_elis_files::build_encodedpath($uuid, 0, 0, 0, false);

        return $result;
    }

    /**
     * Determine whether the current user has permission to edit the specified node,
     * making as few database and permissions checking calls as needed based on the parent
     * and child nodes
     *
     * @param string $uuid The unencoded UUID of the parent node
     * @param object $child The child node object
     * @param boolean $canedit True of the user has permissions on the parent,
     *                         otherwise false
     * @return boolean True if the user can edit the child node, otherwise false
     */
    private function can_edit_child($uuid, $child, $canedit) {
        global $CFG, $DB;

        // Unless a critical transition is happening here, permissions should be the
        // same as for the parent node
        $child_canedit = $canedit;

        if ($child->uuid == $this->elis_files->suuid) {
            // Transition down into the shared space
            $child_canedit = $this->check_editing_permissions(SITEID, true, 0, $child->uuid, 0);
        } else if ($uuid == $this->elis_files->uhomesuid) {
            // Transition down into a user's space
            require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
            if ($userid = elis_files_folder_to_userid($child->title)) {
                $child_canedit = $this->check_editing_permissions(SITEID, false, 0, $child->uuid, $userid);
            }
        } else if ($uuid == $this->elis_files->cuuid) {
            // Transition down into a course's space
            if ($courseid = $DB->get_field('elis_files_course_store', 'courseid', array('uuid' => $child->uuid))) {
                if ($DB->record_exists('course', array('id' => $courseid))) {
                    $child_canedit = $this->check_editing_permissions($courseid, false, 0, $child->uuid, 0);
                }
            }
        } else if ($uuid == $this->elis_files->ouuid) {
            if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
                require_once($CFG->dirroot.'/elis/program/lib/setup.php');
                require_once(elispm::lib('data/userset.class.php'));

                // Transition down into a user set's space
                if ($usersetid = $DB->get_field('elis_files_userset_store', 'usersetid', array('uuid' => $child->uuid))) {
                    if ($DB->record_exists(userset::TABLE, array('id' => $usersetid))) {
                        $child_canedit = $this->check_editing_permissions(SITEID, false, $usersetid, $child->uuid, 0);
                    }
                }
            }
        }

        return $child_canedit;
    }

    /**
     * Get a file list from alfresco
     *
     * @param string $encodedpath base64 encoded and serialized arry of path(uuid), shared(shared flag), oid(userset id), cid(course id) and uid(user id)
     * @param string $path path to a directory
     * @return array
     */
    public function get_listing($encodedpath = '', $path = '') {
        global $CFG, $COURSE, $DB, $SESSION, $OUTPUT, $USER;

         // Verify that we have the repo!
        if (!$this->elis_files || !$this->elis_files->get_defaults()) {
            return false;
        }

        // Track the current UUID
        $uuid = false;

        // Track the course id whose context we are currently in
        $courseid = $COURSE->id;

        // Check for a TRUE value in the encodedpath and retrieve the location
        // If we don't have something explicitly to load and we didn't get here from the drop-down...
        if ($encodedpath === true || empty($encodedpath)) {

            // Check referer to set proper cid and uid defaults
            $referer = $this->elis_files->get_referer();
            if (!empty($referer)) {
                $fromelisfilescoursepage  = stristr($referer, $CFG->wwwroot . '/repository/filemanager.php') !== false;
                $fromcoursepage = (
                    stristr($referer, $CFG->wwwroot .'/course/modedit.php') !== false || stristr($referer, $CFG->wwwroot .'/course/view.php') !== false);
                $fromuserpage = stristr($referer, $CFG->wwwroot . '/user/files.php') !== false;
                if (($fromelisfilescoursepage || $fromcoursepage) && $COURSE->id != SITEID) {
                    list($context, $course, $cm) = get_context_info_array($this->context->id);
                }
                if ($fromuserpage) {
                    $uid = $USER->id;
                }
            }

            // Set defaults
            $cid = (isset($course) && is_object($course)) ? $course->id : 0;
            $uid = (isset($uid)) ? $uid : 0;
            $oid = 0;
            $shared = (boolean)0;

            // Get uuid for default browsing location
            if ($duuid = $this->elis_files->get_default_browsing_location($cid, $uid, $shared, $oid)) {
                $uuid = $duuid;
            }

        } else if (!empty($encodedpath)) {
        // Decode path and retrieve parameters
            $params = unserialize(base64_decode($encodedpath));

            if (is_array($params)) {
                $uuid    = empty($params['path']) ? NULL : clean_param($params['path'], PARAM_PATH);
                $shared  = empty($params['shared']) ? 0 : clean_param($params['shared'], PARAM_BOOL);
                $oid     = empty($params['oid']) ? 0 : clean_param($params['oid'], PARAM_INT);
                $cid     = empty($params['cid']) ? 0 : clean_param($params['cid'], PARAM_INT);
                $uid     = empty($params['uid']) ? 0 : clean_param($params['uid'], PARAM_INT);

                if ($cid > 0) {
                    // Set to a valid course id, in case we are in an AJAX request
                    // and the $COURSE global is not correct
                    $courseid = $cid;
                }
            } else {
                $cid = 0;
                $uid = 0;
                $oid = 0;
                $shared = (boolean)0;
                if ($ruuid = $this->elis_files->get_repository_location($cid, $uid, $shared, $oid)) {
                    $uuid = $ruuid;
                } else if ($duuid = $this->elis_files->get_default_browsing_location($cid, $uid, $shared, $oid)) {
                    $uuid = $duuid;
                }
            }
        }

        if ($uuid == false) {
            //TODO: error handling for case other than being stuck in the Moodle
            //files area
            return;
        }

        $ret = array();
        // Return an array of optional columns from file list to include in the details view
        // Icon and filename are always displayed
        $ret['detailcols'] = array(array('field'=>'created',
                                         'title'=>get_string('datecreated','repository_elis_files')),
                                   array('field'=>'modified',
                                         'title'=>get_string('datemodified','repository_elis_files')),
                                   array('field'=>'owner',
                                         'title'=>get_string('modifiedby','repository_elis_files'))
                             );

        // Set permissible browsing locations
        $ret['locations'] = array();
        $this->elis_files->file_browse_options($cid, $uid, $shared, $oid, $ret['locations']);
        $ret['dynload'] = true;
        $ret['nologin'] = true;
        $ret['showselectedactions'] = true;
        $ret['showcurrentactions'] = true;

        // Signal that this plugin triggers the display of the refresh icon's tooltip
        $ret['refreshtooltip'] = true;

        // Signal that this plugin supports the "advanced search" functionality
        $ret['advancedsearch'] = true;

        // Signal that this plugin should display a button for executing a search
        $ret['executesearch'] = true;

        $return_path = array();
        // Get parent path/breadcrumb
        $this->get_parent_path($uuid, $return_path, 0, 0, 0, 0 /*, $cid, $uid, $shared, $oid */);

        // NOTE: do NOT put 'ELIS Files' in path anymore, 'Company Home' will be
        // ... replaced with 'ELIS Files' in this->prepare_fm_listing()
        //$return_path[]= array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>'');
        $ret['path'] = $return_path;

        $this->current_node = $this->elis_files->get_info($uuid);
        if (!$this->current_node || empty($this->current_node->uuid)) {
            return false;
        }

        $ret['parent'] = $this->current_node; // elis_files_get_parent($uuid);

        $check_node = $this->current_node;
        $prev_node = $this->current_node;
        $uid = $cid = $oid = $shared = 0;

        do {
            $folder_name = !empty($prev_node->title) ? $prev_node->title : '';
            $check_uuid = $check_node->uuid;
            if ($check_uuid == $this->elis_files->cuuid) {
                $cid = $DB->get_field('elis_files_course_store',
                                      'courseid',
                                      array('uuid' => $prev_node->uuid));
            } else if ($check_uuid == $this->elis_files->ouuid) {
                $oid = $DB->get_field('elis_files_userset_store',
                                      'usersetid',
                                      array('uuid' => $prev_node->uuid));
            } else if ($check_uuid == $this->elis_files->suuid) {
                $shared = true;
            } else if ($prev_node->uuid == $this->elis_files->uuuid) {
                $uid = elis_files_folder_to_userid($folder_name);
            }
            $prev_node = $check_node;
        } while (!$uid && !$cid && !$oid && !$shared &&
                ($check_node = $this->elis_files->get_parent($check_node->uuid))
                && !empty($check_node->uuid));

        // Add current node to the return path
        // Include shared and oid parameters
        $params = array('path'=>$uuid,
                        'shared'=>(boolean)$shared,
                        'oid'=>(int)$oid,
                        'cid'=>(int)$cid,
                        'uid'=>(int)$uid);
        $encodedpath = base64_encode(serialize($params));
        $ret['path'][] = array('name'=>isset($this->current_node->title)?$this->current_node->title:'',
                               'path'=>$encodedpath);

        // Unserialized array of path/shared/oid
        $ret['thisuuid'] = $params;
        $ret['thisuuid']['encodedpath'] = $encodedpath;

        //proper parent path containing delimeted UUIDs
        $ret['parentpath'] = self::encode_path_uuids($return_path, $uuid);

        // Get editing privileges - set canedit flag...
        // NOTE: next call MUST occur AFTER while-loop above gets path params!
        $canedit = self::check_editing_permissions($cid ? $cid : $courseid //TBD
                                                   , $shared, $oid, $uuid, $uid);
        if ($canedit) {
            $canview = true;
        } else {
            $canview = self::check_viewing_permissions($cid ? $cid : $courseid //TBD
                                                       , $shared, $oid, $uuid, $uid);
        }
        $ret['canedit'] = $canedit;

        // Store the UUID value that we are currently browsing.
        $this->elis_files->set_repository_location($uuid, $cid, $uid, $shared, $oid);
        $children = elis_files_read_dir($this->current_node->uuid);
        $ret['list'] = array();

        $p_uid = $uid;
        $p_cid = $cid;
        $p_oid = $oid;
        $p_shared = $shared;
        // Check that there are folders to list
        if (($canview || $canedit) && isset($children->folders) &&
            is_array($children->folders)) {
            foreach ($children->folders as $child) {
                // Get path parameters!
                if (!empty($uuid) && empty($p_uid) && empty($p_cid) && empty($p_oid) && empty($p_shared)) {
                    $uid = 0;
                    $cid = 0;
                    $oid = 0;
                    $shared = false;
                    $parent_node = $this->current_node;
                    $prev_node = $child;
                    do {
                        $check_uuid = $parent_node->uuid;
                        $folder_name = !empty($prev_node->title)
                                       ? $prev_node->title : '';
                        if ($check_uuid == $this->elis_files->cuuid) {
                            $cid = $DB->get_field('elis_files_course_store',
                                                  'courseid',
                                                  array('uuid' => $prev_node->uuid));
                        } else if ($check_uuid == $this->elis_files->ouuid) {
                            $oid = $DB->get_field('elis_files_userset_store',
                                                  'usersetid',
                                                  array('uuid' => $prev_node->uuid));
                        } else if ($check_uuid == $this->elis_files->suuid) {
                            $shared = true;
                        } else if ($prev_node->uuid == $this->elis_files->uuuid) {
                            $uid = elis_files_folder_to_userid($folder_name);
                        }
                        $prev_node = $parent_node;
                    } while (!$uid && !$cid && !$oid && !$shared &&
                             ($parent_node = $this->elis_files->get_parent($check_uuid))
                             && !empty($parent_node->uuid));
                }
                if (isset($child->uuid)) {
                    $info = $this->elis_files->get_info($child->uuid);
                } else {
                    $info = '';
                }
                $created = isset($info->created) ? $info->created : '';
                $modified = isset($info->modified) ? $info->modified : '';
                $owner = isset($info->owner) ? $info->owner : '';

                // Handle any parent-to-child transitions that may cause permissions
                // on the sub-folder to be different from the current folder
                $child_canedit = $this->can_edit_child($uuid, $child, $canedit);

                $ret['list'][] = array('title' => $child->title,
                        'path' => repository_elis_files::build_encodedpath($child->uuid, $uid, $cid, $oid, $shared),
                        'name' => $child->title,
                        'thumbnail' => $OUTPUT->pix_url('f/folder-64') . '',
                        'author' => $owner,
                        'datemodified' => $modified,
                        'datecreated' => $created,
                        'children' => array(),
                        'canedit' => $child_canedit);
            }
        }

        // Check that there are files to list
        if (($canview || $canedit) && isset($children->files) &&
            is_array($children->files)) {
            foreach ($children->files as $child) {
                if (isset($child->uuid)) {
                    $info = $this->elis_files->get_info($child->uuid);
                } else {
                    $info = '';
                }
                $filesize = isset($info->filesize) ? $info->filesize : '';
                $created = isset($info->created) ? $info->created : '';
                $modified = isset($info->modified) ? $info->modified : '';
                $owner = isset($info->owner) ? $info->owner : '';
                $ret['list'][] = array('title' => $child->title,
                        'path' => repository_elis_files::build_encodedpath($child->uuid, $p_uid, $p_cid, $p_oid, $p_shared),
                        'thumbnail' => $OUTPUT->pix_url(file_extension_icon($child->title, 90))->out(false),
                        'size' => $filesize,
                        'author' => $owner,
                        'datemodified' => $modified,
                        'datecreated' => $created,
                        'source' => $child->uuid,
                        'canedit' => $canedit);
            }
        }

        return $ret;
    }

    /**
     * Download a file from alfresco - do we use this?
     *
     * @param string $uuid a unique id of directory in alfresco
     * @param string $path path to a directory
     * @return array|null
     */
    public function get_file($uuid, $file = '') {
        //error_log("get_file($uuid, '{$file}');");
        $node = $this->elis_files->get_info($uuid);
        if (empty($node)) {
            return null;
        }

        // Test to make sure this works with, say, a teacher or someone non-admin
        $username = '';

    /// Check for a non-text file type to just pass through to the end user.
        $mimetype = !empty($node->filemimetype) ? $node->filemimetype : '';
        $ticket = elis_files_utils_get_ticket('refresh', $username);
        $url = str_replace($node->filename, urlencode($node->filename), $node->fileurl) . '?alf_ticket=' .
               $ticket;

        $path = $this->prepare_file($file);
        $fp = fopen($path, 'w');
        $c = new curl;
        $response = $c->download(array(array('url'=>$url, 'file'=>$fp)));
        return array('path' => $path, 'url' => $url);
    }

    /**
     * Return file URL
     *
     * @param string $uuid the uuid of file
     * @return string the html link to the file
     */
    public function get_link($uuid) {
        // error_log("get_link({$uuid});");
        return $this->elis_files->get_openfile_link($uuid);
    }

    /**
     * Get file from external repository by reference
     * {@link repository::get_file_reference()}
     * {@link repository::get_file()}
     *
     * @param stdClass $ref    file reference db record
     * @return stdClass|null|false
     */
    public function get_file_by_reference($ref) {
        ob_start();
        var_dump($ref);
        $tmp = ob_get_contents();
        ob_end_clean();
        error_log("get_file_by_reference(ref = {$tmp});");
        return parent::get_file_by_reference($ref); // TBD!!!
    }

    /**
     * Repository method to serve file
     *
     * @param stored_file $storedfile
     * @param int $lifetime Number of seconds before the file should expire from caches (default 24 hours)
     * @param int $filter 0 (default)=no filtering, 1=all files, 2=html files only
     * @param bool $forcedownload If true (default false), forces download of file rather than view in browser/plugin
     * @param array $options additional options affecting the file serving
     */
    public function send_file($storedfile, $lifetime=86400 , $filter=0, $forcedownload=false, array $options = null) {
        global $CFG;
      /*
        ob_start();
        var_dump($storedfile);
        $tmp = ob_get_contents();
        ob_end_clean();
        error_log("send_file(storedfile = {$tmp});");
      */
        $uuid = $storedfile->get_reference();
        $ref = $this->get_link($uuid);
        // Let Alfresco serve the file.
        // TBD: this should probably open in a new window/tab???
        header('Location: ' . $ref);
    }

    /*
     * Get Alfresco folders under current uuid
     *
     * @param   string  $encodedpath    encoded parameters passed from get_listing to javascript and back with parameters
     */
    public function get_folder_listing($encodedpath, $cid = SITEID, $uid = 0, $shared = 0, $oid = 0) {
        global $COURSE, $OUTPUT, $USER;

    // Decode path and retrieve parameters
        if (!empty($encodedpath)) {
            $params = unserialize(base64_decode($encodedpath));
            if (is_array($params)) {
                $uuid    = empty($params['path']) ? NULL : clean_param($params['path'], PARAM_PATH);
                $shared  = empty($params['shared']) ? 0 : clean_param($params['shared'], PARAM_BOOL);
                $oid     = empty($params['oid']) ? 0 : clean_param($params['oid'], PARAM_INT);
                $cid     = empty($params['cid']) ? 0 : clean_param($params['cid'], PARAM_INT);
                $uid     = empty($params['uid']) ? 0 : clean_param($params['uid'], PARAM_INT);
            }
        } else {
            $uuid = NULL;
            $shared = 0;
            $oid = 0;
            $cid = 0;
            $uid = 0;
        }
        $children = elis_files_read_dir($uuid);
        $return = array();

        foreach ($children->folders as $child) {
            if (!$this->elis_files->permission_check($child->uuid, $USER->id, false)) {
                continue;
            }
//            $params = array('path'=>$child->uuid,
//                             'shared'=>(boolean)$shared,
//                             'oid'=>(int)$oid,
//                             'cid'=>(int)$cid,
//                             'uid'=>(int)$uid);
//            $encodedpath = base64_encode(serialize($params));
            $return[] = array('title'=>$child->title,
                    'path'      => repository_elis_files::build_encodedpath($child->uuid, $uid, $cid, $oid, $shared),
                    'name'      => $child->title,
                    'thumbnail' => $OUTPUT->pix_url('f/folder-32') . '',
                    'created'   => '',
                    'modified'  => '',
                    'owner'     => !empty($child->owner) ? $child->owner : '',
                    'children'  => array());
        }
        return $return;
    }


    public function print_search() {
        global $CFG, $DB;

//        require_once $CFG->dirroot.'/repository/elis_files/renderer.php';
//        //reset the page template
//        // we need to send filepicker templates to the browser just once
//        $fprenderer = $PAGE->get_renderer('repository', 'elis_files');

        require_once $CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php';
        require_once $CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php';
        require_once $CFG->dirroot.'/repository/elis_files/lib/HTML_TreeMenu-1.2.0/TreeMenu.php';
        require_once $CFG->dirroot.'/repository/elis_files/tree_menu_lib.php';

        $str = get_string('searchforfilesinrepository', 'repository_elis_files');

//        $renderer = $PAGE->get_renderer('repository', 'elis_files');
//        $str .= $renderer->repository_default_searchform();
        $str .= parent::print_search();

        return $str;
    }

    /**
     * Popup to confirm the list of files to delete
     * @param   string  $parentuuid     encoded array of parent uuid, cid, oid, uid passed for refreshing page
     * @param   array   $files_array    list of files to be deleted
     * @return  string  $str            html to be displayed
     */
    public function print_delete_popup($parentuuid, $files_array) {
        global $CFG, $DB, $OUTPUT, $USER;


        // we keep the parent uuid around so we know which uuid to refresh the page to when refreshing
        $str = '<div><input type="hidden" id="parentuuid" name="parentuuid" value="'.$parentuuid.'">';

        // display list of files to delete...
        $str .= '<div>'.get_string('deletecheckfiles','repository_elis_files').'</div>';
        $filelist = array();

        $str .= self::printfilelist($files_array, $filelist);
        $str .= '<input type="hidden" name="fileslist" id="fileslist" value="'.implode(",",$filelist).'">';
        $resourcelist = false;
        $fs = get_file_storage();

        foreach ($filelist as $uuid) {
            // If file is specified in a resource, then delete that too.
            $node = $this->elis_files->get_info($uuid);
            $clean_name = $node->title;
            if ($this->elis_files->is_dir($uuid)) {
                continue;
            }
            if ($DB->record_exists('files', array('filename'=> $clean_name))) {
                // Warn user that they are deleting a resource that is used...
                $str .= '<p>'.get_string('warningdeleteresource', 'repository_elis_files', $clean_name).'</p>';
            }
        }
        $str .= '</div>';

        return $str;
    }

    /**
     * Generate html string to return for popup
     * @return  string  $str
     */
    public function print_move_dialog($parentuuid, $locationparent, $cid, $uid, $shared, $oid, $selected_files) {
        global $CFG;

        $params = array('path'=>$parentuuid,
                        'shared'=>(boolean)$shared,
                        'oid'=>(int)$oid,
                        'cid'=>(int)$cid,
                        'uid'=>(int)$uid);
        $encodedpath = base64_encode(serialize($params));

        $str = '<div>
                    <div id="repository_tabs"></div>
                    <input type="hidden" id="locationparent" name="locationparent" value="'.$locationparent.'" />
                    <input type="hidden" id="parentuuid" name="parentuuid" value="'.$encodedpath.'" />
                    <input type="hidden" id="tabuuid" name="tabuuid" value="" />
                    <input type="hidden" name="selected_files" id="selected_files" value="'.implode(",",$selected_files).'" />
                    <input id="targetfolder" name="targetfolder" type="hidden" value = "'.$locationparent.'" />
                </div>';
        return $str;
    }

    /**
     * Generate html string to return for popup
     * @param   string  $parentuuid parent uuid needed for page refresh
     * @return  string  $str
     */
    public function print_newdir_popup($parentuuid) {
        global $CFG;

        $str = '<div>
                    <input type="hidden" id="parentuuid" name="parentuuid" value="'.$parentuuid.'">
                    <input type="text" id="newdirname" name="newdirname"/>
                </div>';

        return $str;
    }

    /**
     * Generate html string to return for popup
     * @return  string  $str
     */
    public function print_upload_popup() {
        global $CFG;

        $str = '<p><b>'.get_string('uploadingtiptitle', 'repository_elis_files').'</b></p>
                <p>'.get_string('uploadingtiptext', 'repository_elis_files').'</p>
                <div id="progressbar"></div>
                <table style="border-style:none; padding:5px;">
                    <tr>
                        <td>
                            <a id="uploadButton" href="javascript:void(0);"><input type="button" value="'.get_string('selectfiles', 'repository_elis_files').'" /></a>
                            <div id="file-uploader"></div>
                        </td>
                        <td>
                            <a id="uploadCloseButton" href="javascript:void(0);"><input type="button" value="'.get_string('close', 'repository_elis_files').'" /></a>
                        </td>
                    </tr>
                </table>';

        return $str;
    }

    /**
     * Obtain the query needed to obtain our file listing via webservices
     * NOTE: This is currently implemented as a Lucene query
     *
     * @param string $search_text The search text entered, which contains tokens
     *                            representing either file name or file content
     * @param int $categories An array of category ids to filter on (i.e. results
     *                        must be in one or more to be included), or NULL to not filter
     *                        by category
     * @return string The complete search query
     */
    private function get_search_query($search_text, $categories = NULL) {
        global $DB;

        $query_fragments = array();

        // The text-based search component
        if ($search_text != '') {
            $text_query_tokens = array();

            // Valid as long as the file a name or contents match one text token
            $tokens = explode(' ', $search_text);
            foreach ($tokens as $token) {
                $text_query_tokens[] = '@cm\:name:"*'.$token.'*"';
                $text_query_tokens[] = 'TEXT:\"'.$token.'\"';
            }

            $query_fragments[] = implode(' OR ', $text_query_tokens);
        }

        // The category-based search component
        if (is_array($categories)) {
            $category_query_tokens = array();

            // Valid as long as the file is in at least one of the categories
            foreach ($categories as $category) {
                //TODO: search based on UUID rather than title
                // current side-effect is that duplicate titles will be matched
                if ($categorytitle = $DB->get_field('elis_files_categories', 'title', array('id' => $category))) {
                    $cattitle = elis_files_ISO_9075_map($categorytitle);
                    $category_query_tokens[] = 'PATH:"/cm:generalclassifiable//cm:'.$cattitle.'//member"';
                }
            }

            $query_fragments[] = implode(' OR ', $category_query_tokens);
        }

        // File must satisfy both the text-based and category-based conditions
        $query = '('.implode(') AND (', $query_fragments).')';

        return $query;
    }

    /**
     * Look for a file
     *
     * @param string $search_text The search text entered, which contains tokens
     *                            representing either file name or file content
     * @param int $page The page we are showing the contents for
     * @param int $categories An array of category ids to filter on (i.e. results
     *                        must be in one or more to be included), or NULL to not filter
     *                        by category
     * @return array A data structure equivalent to the return value of "get_listing",
     *               containing the filter listing
     */
    function search($search_text, $page = 1, $categories = NULL) {
        global $CFG, $COURSE, $OUTPUT, $USER;
        require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');

        $ret = array();
        $shared = 0;
        $oid = 0;

        // Set id
        if (!empty($USER->id)) {
            $uid = $USER->id;
            $cid = 0;
        }
        if (!empty($COURSE->id)) {
            $cid = $COURSE->id;
            $uid = 0;
        }
       // setting userid...
        if ($this->context->contextlevel === CONTEXT_USER) {
            $userid = $USER->id;
        } else {
            $userid = 0;
        }

        $courseid = $COURSE->id;
        $uuid = false;
        if ($ruuid = $this->elis_files->get_repository_location($courseid, $userid, $shared, $oid)) {
            $uuid = $ruuid;
        } else if ($duuid = $this->elis_files->get_default_browsing_location($courseid, $userid, $shared, $oid)) {
            $uuid = $duuid;
        }
        $uuuid = $this->elis_files->get_user_store($USER->id);
        if ($uuid == $uuuid) {
            $uid = $USER->id;
        } else {
            $uid = 0;
        }

        $canedit = self::check_editing_permissions($COURSE->id, $shared, $oid, $uuid, $uid);

        $ret['canedit'] = $canedit;

        $ret['detailcols'] = array(array('field'=>'created',
                                         'title'=>get_string('datecreated','repository_elis_files')),
                                   array('field'=>'modified',
                                         'title'=>get_string('datemodified','repository_elis_files')),
                                   array('field'=>'owner',
                                         'title'=>get_string('modifiedby','repository_elis_files'))
                             );
        $ret['dynload'] = true;
        $ret['nologin'] = true;
        $ret['showselectedactions'] = true;
        $ret['showcurrentactions'] = false; // do not show current actions for search results because we are not in a folder

        // Can only have either course or user id set
        if (!empty($cid) && !empty($uid)) {
            $cid = 0;
        }

        // Set permissible browsing locations
        $ret['locations'] = array();
        $this->elis_files->file_browse_options($cid, $uid, $shared, $oid, $ret['locations']);
        $ret['list'] = array();

        // Obtain the list of matching files
        $query = $this->get_search_query($search_text, $categories);
        $response = elis_files_utils_invoke_service('/moodle/lucenesearch?searchquery='.rawurlencode($query));
        $sxml = RLsimpleXMLelement($response);

        if ($sxml and $entries = $sxml->xpath('//entry')) {
            foreach ($entries as $entry) {
                // Include shared and oid parameters
                $uuid = (string)$entry->uuid;

                if ($properties = $this->elis_files->get_info($uuid)) {
                    if (strcmp($properties->type, "cmis:folder") !== 0 &&
                        strcmp($properties->type, "folder") !== 0) {
                        $params = array('path'=>$uuid,
                                        'shared'=>(boolean)$shared,
                                        'oid'=>(int)$oid,
                                        'cid'=>(int)$cid,
                                        'uid'=>(int)$uid);
                        $encodedpath = base64_encode(serialize($params));
                        $filesize = isset($properties->filesize) ? $properties->filesize : '';
                        $created = isset($properties->created) ? $properties->created : '';
                        $modified = isset($properties->modified) ? $properties->modified : '';
                        $owner = isset($properties->owner) ? $properties->owner : '';

                        $alfresco_version = elis_files_get_repository_version();
                        if ($alfresco_version == '3.2.1') {
                            $thumbnail = $OUTPUT->pix_url(file_extension_icon($entry->filename, 90))->out(false);
                        } else {
                            $thumbnail = $OUTPUT->pix_url(file_extension_icon($entry->icon, 90))->out(false);
                        }
                        $ret['list'][] = array('title'=>$properties->title,
                                               'path'=>$encodedpath,
                                               'size' => $filesize,
                                               'thumbnail' => $thumbnail,
                                               'datecreated'=>$created,
                                               'datemodified'=>$modified,
                                               'author'=>$owner,
                                               'source'=>$uuid);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Return names of general options for this plugin
     * They can then be accessed in the construct with $this->
     * @return array List of setting option names
     */
    public static function get_type_option_names() {
        $option_names = array(
                'pluginname',           // This is for an optional plugin name change
                'server_host',          // URL for Alfresco server
                'server_port',          // Alfresco server port
                'server_username',      // Alfresco server username
                'server_password',      // Alfresco server password
                'file_transfer_method', // Defines how we are sending files to Alfresco
                'ftp_port',             // Defines the port used for sending files via FTP
                'root_folder',          // Moodle root folder
                'user_quota',           // User quota N.B. cache is now pulled from general Repository options
                'deleteuserdir',        // Whether or not to delete an Alfresco's user's folder when they are deleted in Moodle <= hmmmm
                'default_browse',       // Where to start the file browsing session
                'admin_username',       // The override for a Moodle account using the 'admin' username
                'connect_timeout',      // The timeout value in seconds for establising a CURL connection
                'response_timeout'      // The timeout value in seconds for getting a response from a CURL call
        );

        return $option_names;
    }

    /**
     * Add Plugin settings input to Moodle form
     * @param object $mform
     */
    public static function type_config_form($mform, $classname = 'repository_elis_files') {
        global $DB, $CFG, $SESSION, $OUTPUT;

        parent::type_config_form($mform);

        $mform->addElement('text', 'server_host', get_string('serverurl', 'repository_elis_files'), array('size' => '40'));
        $mform->setDefault('server_host', 'http://localhost');
        $mform->setType('server_host', PARAM_TEXT);
        $mform->addElement('static', 'server_host_default', '', get_string('elis_files_default_server_host', 'repository_elis_files'));
        $mform->addElement('static', 'server_host_intro', '', get_string('elis_files_server_host', 'repository_elis_files'));
        $mform->addRule('server_host', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'server_port', get_string('serverport', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_port_default', '', get_string('elis_files_default_server_port', 'repository_elis_files'));
        $mform->addElement('static', 'server_port_intro', '', get_string('elis_files_server_port', 'repository_elis_files'));

        $mform->addRule('server_port', get_string('required'), 'required', null, 'client');
        $mform->setDefault('server_port', '8080');
        $mform->setType('server_port', PARAM_INT);

        $mform->addElement('text', 'server_username', get_string('serverusername', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_username_default', '', get_string('elis_files_default_server_username', 'repository_elis_files'));
        $mform->addElement('static', 'server_username_intro', '', get_string('elis_files_server_username', 'repository_elis_files'));
        $mform->addRule('server_username', get_string('required'), 'required', null, 'client');
        $mform->setType('server_username', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'server_password', get_string('serverpassword', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_password_intro', '', get_string('elis_files_server_password', 'repository_elis_files'));
        $mform->addRule('server_password', get_string('required'), 'required', null, 'client');
        $mform->setType('server_password', PARAM_TEXT);

        $options = array(
            ELIS_FILES_XFER_WS  => get_string('webservices', 'repository_elis_files'),
            ELIS_FILES_XFER_FTP => get_string('ftp', 'repository_elis_files')
        );

        $mform->addElement('select', 'file_transfer_method', get_string('filetransfermethod', 'repository_elis_files'), $options);
        $mform->setDefault('file_transfer_method', ELIS_FILES_XFER_FTP);
        $mform->addElement('static', 'file_transfer_method_default', '', get_string('filetransfermethoddefault', 'repository_elis_files'));
        $mform->addElement('static', 'file_transfer_method_desc', '', get_string('filetransfermethoddesc', 'repository_elis_files'));

        // Add a green checkmark if FTP connection works, red X on failure
        // (only if transfer method is set to ftp)
        $ftp_indicator = self::get_ftp_config_indicator();
        $mform->addElement('text', 'ftp_port', get_string('ftpport', 'repository_elis_files'), array('size' => '30'));
        $mform->setDefault('ftp_port', '21');
        $mform->setType('ftp_port', PARAM_INT);
        $mform->addElement('static', 'ftp_port_default', '', $ftp_indicator.'&nbsp'.get_string('ftpportdefault', 'repository_elis_files'));
        $mform->addElement('static', 'ftp_port_desc', '', get_string('ftpportdesc', 'repository_elis_files'));

        $mform->addElement('text', 'connect_timeout', get_string('connecttimeout', 'repository_elis_files'), array('size' => '10'));
        $mform->setType('connect_timeout', PARAM_INT);
        $mform->setDefault('connect_timeout', ELIS_FILES_CURL_CONNECT_TIMEOUT);
        $mform->addElement('static', 'connect_timeout_default', '', get_string('connecttimeoutdefault', 'repository_elis_files', ELIS_FILES_CURL_CONNECT_TIMEOUT));
        $mform->addElement('static', 'connect_timeout_desc', '', get_string('connecttimeoutdesc', 'repository_elis_files'));

        $mform->addElement('text', 'response_timeout', get_string('responsetimeout', 'repository_elis_files'), array('size' => '10'));
        $mform->setType('response_timeout', PARAM_INT);
        $mform->setDefault('response_timeout', ELIS_FILES_CURL_RESPONSE_TIMEOUT);
        $mform->addElement('static', 'response_timeout_default', '', get_string('responsetimeoutdefault', 'repository_elis_files', ELIS_FILES_CURL_RESPONSE_TIMEOUT));
        $mform->addElement('static', 'response_timeout_desc', '', get_string('responsetimeoutdesc', 'repository_elis_files'));

        // Check for installed categories table or display 'plugin not yet installed'
        if ($DB->get_manager()->table_exists('elis_files_categories')) {
        // Need to check for settings to be saved
            $popup_settings ="height=400,width=500,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
            $url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
            $jsondata = array('url'=>$url,'name'=>'config_categories','options'=>$popup_settings);
            $jsondata = json_encode($jsondata);
            $title = get_string('configurecategoryfilter', 'repository_elis_files');

            $button = "<input type='button' value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'>";
            $mform->addElement('static', 'category_filter', get_string('categoryfilter', 'repository_elis_files'), $button);
            $mform->addElement('static', 'category_filter_intro', '', get_string('elis_files_category_filter', 'repository_elis_files'));
        } else {
            $mform->addElement('static', 'category_filter_intro', get_string('categoryfilter', 'repository_elis_files'), get_string('elisfilesnotinstalled', 'repository_elis_files'));
        }

        $popup_settings = "height=480,width=640,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";

        $root_folder = get_config('elis_files', 'root_folder');
        $button = self::output_root_folder_html($root_folder);

        $rootfolderarray=array();
        $rootfolderarray[] = $mform->createElement('text', 'root_folder', get_string('rootfolder', 'repository_elis_files'), array('size' => '30'));
        $rootfolderarray[] = $mform->createElement('button', 'root_folder_popup', get_string('chooserootfolder', 'repository_elis_files'), $button);

        $mform->addGroup($rootfolderarray, 'rootfolderar', get_string('rootfolder', 'repository_elis_files'), array(' '), false);
        $mform->setDefault('root_folder', '/moodle');
        $mform->setType('root_folder', PARAM_TEXT);

        // Add checkmark if get root folder works, or whatever...
        $valid = self::root_folder_is_valid($root_folder);
        $mform->addElement('static', 'root_folder_default', '', $valid.'&nbsp;'.get_string('elis_files_default_root_folder', 'repository_elis_files'));
        $mform->addElement('static', 'root_folder_intro', '', get_string('elis_files_root_folder', 'repository_elis_files'));

        // Cache time is retrieved from the common cache time and displayed here
        $mform->addElement('text', 'cache_time', get_string('cachetime', 'repository_elis_files'), array('size' => '10'));
        $mform->addElement('static', 'cache_time_default', '', get_string('elis_files_default_cache_time', 'repository_elis_files'));
        $mform->setDefault('cache_time', $CFG->repositorycacheexpire);
        $mform->setType('cache_time', PARAM_INT);
        $mform->freeze('cache_time');

        // Generate the list of options for choosing a quota limit size.
        $bytes_1mb = 1048576;

        $sizelist = array(
            -1,
            '',
            $bytes_1mb * 10,
            $bytes_1mb * 20,
            $bytes_1mb * 30,
            $bytes_1mb * 40,
            $bytes_1mb * 50,
            $bytes_1mb * 100,
            $bytes_1mb * 200,
            $bytes_1mb * 500
        );

        foreach ($sizelist as $sizebytes) {
            if ($sizebytes == '') {
                $filesize[$sizebytes] = get_string('quotanotset', 'repository_elis_files');;
            } else if ($sizebytes == -1 ) {
                $filesize[$sizebytes] = get_string('quotaunlimited', 'repository_elis_files');
            } else {
                $filesize[$sizebytes] = display_size($sizebytes);
            }
        }

        krsort($filesize, SORT_NUMERIC);

        $mform->addElement('select', 'user_quota', get_string('userquota', 'repository_elis_files'), $filesize);
        $mform->setDefault('user_quota', '');
        $mform->addElement('static', 'user_quota_default', '', get_string('elis_files_default_user_quota', 'repository_elis_files'));
        $mform->addElement('static', 'user_quota_intro', '', get_string('configuserquota', 'repository_elis_files'));

        // Add a toggle to control whether we will delete a user's home directory in Alfresco when their account is deleted.
        $options = array(1 => get_string('yes'), '' => get_string('no'));

        $mform->addElement('select', 'deleteuserdir', get_string('deleteuserdir', 'repository_elis_files'), $options);
        $mform->setDefault('deleteuserdir', '');
        $mform->addElement('static', 'deleteuserdir_default', '', get_string('elis_files_default_deleteuserdir', 'repository_elis_files'));
        $mform->addElement('static', 'deleteuserdir_intro', '', get_string('configdeleteuserdir', 'repository_elis_files'));

        // Menu setting about choosing the default location where users will end up if they don't have a previous file
        // browsing location saved.
        $options = array(
            ELIS_FILES_BROWSE_SITE_FILES   => get_string('repositorysitefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_COURSE_FILES => get_string('repositorycoursefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_USER_FILES   => get_string('repositoryuserfiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_SHARED_FILES => get_string('repositorysharedfiles', 'repository_elis_files')
        );

        $mform->addElement('select', 'default_browse', get_string('defaultfilebrowsinglocation', 'repository_elis_files'), $options);
        $mform->setDefault('default_browse', ELIS_FILES_BROWSE_USER_FILES);
        $mform->addElement('static', 'default_browse_default', '', get_string('elis_files_default_default_browse', 'repository_elis_files'));
        $mform->addElement('static', 'default_browse_intro', '', get_string('configdefaultfilebrowsinglocation', 'repository_elis_files'));

        // Display menu option about overriding the Moodle 'admin' account when creating an Alfresco user account.

        // Check for the existence of a user that will conflict with the default Alfresco administrator account.
        $hasadmin = $DB->record_exists('user', array('username'   => 'admin',
                                                     'mnethostid' => $CFG->mnet_localhost_id));

        $admin_username = trim(get_config('elis_files', 'admin_username'));
        if (empty($admin_username)) {
            $adminusername = 'moodleadmin';
            set_config('admin_username', $adminusername, 'elis_files');
        } else {
            $adminusername = $admin_username;
        }

        // Only proceed here if the Alfresco plug-in is actually enabled.
        if (self::is_repo_visible('elis_files')) {
            if ($repo = repository_factory::factory()) {
                if (elis_files_get_home_directory($adminusername) == false) {
                    $mform->addElement('text', 'admin_username', get_string('adminusername', 'repository_elis_files'), array('size' => '30'));
                    $mform->setType('admin_username', PARAM_TEXT);
                    $mform->addElement('static', 'admin_username_default', '', get_string('elis_files_default_admin_username', 'repository_elis_files'));
                    $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusername', 'repository_elis_files'));
                } else {
                    // Added to prevent an empty value from being stored in the database on form submit
                    $mform->addElement('hidden', 'admin_username', $adminusername);
                    $mform->setType('admin_username', PARAM_TEXT);

                    // An Alfresco account with the specified username has been created, check if a Moodle account exists with that
                    // username and display a warning if that is the case.
                    if (($userid = $DB->get_field('user', 'id', array('username'=> $adminusername, 'mnethostid'=> $CFG->mnet_localhost_id))) !== false) {
                        $a = new stdClass;
                        $a->username = $adminusername;
                        $a->url      = $CFG->wwwroot . '/user/editadvanced.php?id=' . $userid . '&amp;course=' . SITEID;

                        $mform->addElement('static', 'admin_username_intro', get_string('adminusername', 'repository_elis_files'),
                                           get_string('configadminusernameconflict', 'repository_elis_files', $a));
                    } else {
                        $mform->addElement('static', 'admin_username_intro', get_string('adminusername', 'repository_elis_files'),
                                           get_string('configadminusernameset', 'repository_elis_files', $adminusername));
                    }
                }
            }

            // Attmept to detect users in the system that are authenticated with a plug-in that does not use passwords and display
            // a message indicating the script that can be run to do a bulk synchronise of these users.
            $auths = elis_files_nopasswd_auths();

            if (!empty($auths) && count($auths) > 0) {
                $select = '';
                $params = array();

                if (count($auths) == 1) {
                    $select          = 'auth = :auth1';
                    $params['auth1'] = current($auths);
                } else {
                    $selects = array();

                    for ($i = 1; $i <= count($auths); $i++) {
                        $selects[]        .= ':auth'.$i;
                        $params['auth'.$i] = $auths[$i - 1];
                    }

                    $select = 'auth IN ('.implode(', ', $selects).')';
                }

                $select       .= ' AND deleted = :del';
                $params['del'] = 0;

                if ($DB->record_exists_select('user', $select, $params)) {
                    $mform->addElement('html', '<br />');
                    $mform->addElement('html', '<dl class="healthissues notice"><dt>'.get_string('passwordlessusersync', 'repository_elis_files').
                                    '</dt> <dd>'.get_string('configpasswordlessusersync', 'repository_elis_files').'</dd></dl>');
                }
            }
        }

        return true;
    }

    /*
     * Get visibility of this repository
     */
    public static function is_repo_visible($typename) {
        global $DB;
        if (!$record = $DB->get_record('repository',array('type' => $typename))) {
            return false;
        }
        if ($record->visible) {
            return true;
        }
    }

    /**
     * Prepares folder tree structure for JSON encoding to FileManager
     *
     * @param  array &$output     The output array
     * @param  array $folderentry The input folder structure
     * @param string $path        Top-level path folder name (i.e. 'Company Home' or 'ELIS Files')
     * @param   int  $puid        Optional parent folder's uuid
     * @param   int  $uid         Optional user id setting
     * @param   int  $cid         Optional course id setting
     * @param   int  $oid         Optional userset/cluster id setting
     * @param  bool  $shared      Optional shared flag setting
     * @uses   $DB
     */
    protected function folder_tree_to_fm(&$output, $folderentry, $path = '', $puuid = '', $uid = 0, $cid = 0, $oid = 0, $shared = false) {
        global $DB;
        foreach ($folderentry as $folder) {
            $_uid = $uid;
            $_cid = $cid;
            $_oid = $oid;
            $_shared = $shared;
            if (!empty($puuid) && !$uid && !$cid && !$oid && !$shared) {
                // No flags set check if we need to set them
                if ($puuid == $this->elis_files->cuuid) {
                    $_cid = $DB->get_field('elis_files_course_store', 'courseid',
                                          array('uuid' => $folder['uuid']));
                } else if ($puuid == $this->elis_files->ouuid) {
                    $_oid = $DB->get_field('elis_files_userset_store', 'usersetid',
                                          array('uuid' => $folder['uuid']));
                } else if ($puuid == $this->elis_files->suuid) {
                    $_shared = true;
                } else if ($folder['uuid'] == $this->elis_files->uuuid) {
                    $_uid = elis_files_folder_to_userid($folder['name']);
                }
            }
            $entry = array();
            $entry['filepath'] = repository_elis_files::build_encodedpath($folder['uuid'], $_uid, $_cid, $_oid, $_shared);
            $entry['textpath'] = $path .'/'. $folder['name'];
            $entry['fullname'] = $folder['name'];
            $entry['id'] = $folder['uuid']; // TBD
            $entry['sortorder'] = 0; // TBD
            $entry['children'] = array();
            if (!empty($folder['children'])) {
                $this->folder_tree_to_fm($entry['children'], $folder['children'],
                                         $entry['textpath'], $folder['uuid'],
                                         $_uid, $_cid, $_oid, $_shared);
            }
            $output[] = $entry;
        }
    }

    /**
     * Prepares list of files before passing it to AJAX, makes sure data is in the correct
     * format and stores formatted values.
     *
     * @param array|stdClass $listing result of get_listing() or search() or file_get_drafarea_files()
     * @param string         $parent  encoded path of parent folder
     * @uses   $CFG
     * @uses   $OUTPUT
     * @return list((array)listing, count)
     */
    public function prepare_fm_listing($listing, $parent = '') {
        global $CFG, $OUTPUT;

        $locations = null;
        if (is_array($listing) && !empty($listing['locations'])) {
            $locations = &$listing['locations'];
        } else if (is_object($listing) && !empty($listing->locations)) {
            $locations = &$listing->locations;
        }

        if (is_array($listing) && !empty($listing['parent']) && !empty($listing['parent']->uuid)) {
            $parent = repository_elis_files::build_encodedpath($listing['parent']->uuid);
        } else if (is_object($listing) && !empty($listing->parent) && !empty($listing->parent->uuid)) {
            $parent = repository_elis_files::build_encodedpath($listing->parent->uuid);
        }

        $defaultfoldericon = $OUTPUT->pix_url(file_folder_icon(64))->out(false);
        // prepare $listing['path'] or $listing->path
        if (is_array($listing) && isset($listing['path']) && is_array($listing['path'])) {
            $path = &$listing['path'];
        } else if (is_object($listing) && isset($listing->path) && is_array($listing->path)) {
            $path = &$listing->path;
        }
        $elisfilesfolder = array();
        $companyhomefolder = array();
        $textpath = '';
        $lastpathvalue = 0;
        if (isset($path)) {
            $len = count($path);
            for ($i = 0; $i < $len; $i++) {
                $pathname = false;
                $pathvalue = false;
                if (is_array($path[$i])) {
                    if (!isset($path[$i]['icon'])) {
                        $path[$i]['icon'] = $defaultfoldericon;
                    }
                    if (!empty($path[$i]['name'])) {
                        $pathname = $path[$i]['name'];
                    }
                    if (!empty($path[$i]['path'])) {
                        $pathvalue = $path[$i]['path'];
                    }
                } else if (is_object($path[$i])) {
                    if (!isset($path[$i]->icon)) {
                        $path[$i]->icon = $defaultfoldericon;
                    }
                    if (!empty($path[$i]->name)) {
                        $pathname = $path[$i]->name;
                    }
                    if (!empty($path[$i]->path)) {
                        $pathvalue = $path[$i]->path;
                    }
                }
                if ($pathname == $this->elis_files->get_root()->title) {
                    $pathname = get_string('repository', 'repository_elis_files');
                    if (is_array($path[$i])) {
                        $path[$i]['name'] = $pathname;
                    } else {
                        $path[$i]->name = $pathname;
                    }
                    $companyhomefolder['filepath'] = $pathvalue;
                    $companyhomefolder['textpath'] = $pathname;
                    $companyhomefolder['fullname'] = $pathname;
                    $companyhomefolder['id'] = $pathvalue; // TBD
                    $companyhomefolder['sortorder'] = 0; // TBD
                    $companyhomefolder['children'] = array();
                }
                $textpath .= (!empty($textpath) ? '/' : '') . $pathname;
            }
        }

        // prepare $listing['list'] or $listing->list
        if (is_array($listing) && isset($listing['list']) && is_array($listing['list'])) {
            $listing['list'] = array_values($listing['list']); // convert to array
            $files = &$listing['list'];
        } else if (is_object($listing) && isset($listing->list) && is_array($listing->list)) {
            $listing->list = array_values($listing->list); // convert to array
            $files = &$listing->list;
        } else {
            return array($listing, 0); // TBD
        }
        $len = count($files);
        for ($i = 0; $i < $len; $i++) {
            if (is_object($files[$i])) {
                $file = (array)$files[$i];
                $converttoobject = true;
            } else {
                $file = & $files[$i];
                $converttoobject = false;
            }
            if (!empty($parent)) {
                $file['parent'] = $parent;
            }
            if (isset($file['size'])) { // TBD
                $file['size'] = (int)$file['size'];
                $file['size_f'] = display_size($file['size']);
            }
            if (isset($file['license']) &&
                    get_string_manager()->string_exists($file['license'], 'license')) {
                $file['license_f'] = get_string($file['license'], 'license');
            }
            if (isset($file['image_width']) && isset($file['image_height'])) {
                $a = array('width' => $file['image_width'], 'height' => $file['image_height']);
                $file['dimensions'] = get_string('imagesize', 'repository', (object)$a);
            }
            // Map date fields
            foreach (array('datemodified',
                           'datecreated'
                     ) as $key) {
                if (!isset($file[$key]) && isset($file['date'])) {
                    $file[$key] = userdate($file['date']);
                }
                if (isset($file[$key])) {
                    $file[$key.'_f'] = userdate($file[$key], get_string('strftimedatetime', 'langconfig'));
                    $file[$key.'_f_s'] = userdate($file[$key], get_string('strftimedatetimeshort', 'langconfig'));
                }
            }
            // Map other fields
            foreach (array('author' => 'author',
                           'path'  => 'filepath' // TBD
                     ) as $key => $value) {
                if (isset($file[$key])) {
                    $file[$value] = $file[$key];
                } else {
                    $file[$value] = ''; // TBD
                }
            }
            $isfolder = (array_key_exists('children', $file) || (isset($file['type']) && $file['type'] == 'folder'));
            if (!isset($file['type'])) {
                $file['type'] = $isfolder ? 'folder' : 'file';
            }
            $filename = null;
            if (isset($file['title'])) {
                $filename = $file['title'];
            }
            else if (isset($file['fullname'])) {
                $filename = $file['fullname'];
            }
            if ($filename) {
                $file['filename'] = $filename;
                $file['fullname'] = $filename;
            }
            if (isset($file['source'])) {
                $file['url'] = $this->get_link($file['source']);
            }
            //error_log("/repository/elis_files/lib.php::prepare_listing(): filepath = {$textpath}");
            $file['textpath'] = $textpath; // TBD
            if (!isset($file['mimetype']) && !$isfolder && $filename) {
                $file['mimetype'] = get_mimetype_description(array('filename' => $filename));
            }
            if (!isset($file['icon'])) {
                if ($isfolder) {
                    $file['icon'] = $defaultfoldericon;
                } else if ($filename) {
                    $file['icon'] = $OUTPUT->pix_url(file_extension_icon($filename, 90))->out(false);
                }
            }
            if ($converttoobject) {
                $files[$i] = (object)$file;
            }
        }

        // Now build the entire folder tree, respecting "create" permissions ...
        $folders = elis_files_folder_structure(true);
        $foldertree = array();
        $this->folder_tree_to_fm($foldertree, $folders, $companyhomefolder['textpath']);

        // Must add missing 'ELIS Files' & 'Company Home' locations to tree
        // if permissions allow

        // For this, we care about the "root" node
        $root_uuid = $this->elis_files->get_root()->uuid;
        // Validate whether the current user has proper "site-level create" permissions
        $access_permitted = $this->check_editing_permissions(SITEID, 0, 0, $root_uuid, 0);

        if (!empty($companyhomefolder) && $access_permitted) {
            // We have access to 'Company Home'
            $companyhomefolder['children'] = $foldertree;
            $foldertree = array($companyhomefolder);
        }

        if (ELIS_FILES_DEBUG_TRACE) {
            ob_start();
            var_dump($folders);
            $tmp = ob_get_contents();
            ob_end_clean();
            error_log("/repository/elis_files/lib.php::prepare_fm_listing(): folders = {$tmp}");
            ob_start();
            var_dump($foldertree);
            $tmp = ob_get_contents();
            ob_end_clean();
            error_log("/repository/elis_files/lib.php::prepare_fm_listing(): foldertree = {$tmp}");
        }

        if (is_array($listing)) {
            $listing['tree'] = array('children' => $foldertree);
        } else {
            $treeelm = new stdClass;
            $treeelm->children = $foldertree;
            $listing->tree = $treeelm;
        }

        return array($listing, $len);
    }

    private static function output_root_folder_html($data, $query = '') {
        global $CFG, $PAGE;

        $PAGE->requires->js('/repository/elis_files/rootfolder.js');

        $repoisup = false;

        $default = '/moodle';

        /// Validate the path, if we can.
        require_once dirname(__FILE__). '/ELIS_files_factory.class.php';
        if ($repo = repository_factory::factory()) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();
        } else {
            $repoisup = false;
        }

        $id = "id_root_folder";
        $name = "root_folder";
        $inputs = '<div class="form-file defaultsnext"><input type="text" size="48" id="' . $id .
                  '" name="' . $name . '" value="' . s($data) . '" /> <input type="button" ' .
                  'onclick="return chooseRootFolder(document.getElementById(\'mform1\'));" value="' .
                  get_string('chooserootfolder', 'repository_elis_files') . '" name="' . $name .
                  '"' . (!$repoisup ? ' disabled="disabled"' : '') .' /></div>';

        return $inputs;
    }

    /**
     * Determine whether the root folder is valid
     * @return  string
     */
    private static function root_folder_is_valid($data) {
        $repoisup = false;

    /// Validate the path, if we can.
        if ($repo = repository_factory::factory()) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();
            if ($repoisup) {
                if (elis_files_validate_path($data)) {
                    $valid = '<span class="pathok" style="color:green;">&#x2714;</span>';
                } else {
                    $valid = '<span class="patherror" style="color:red;">&#x2718;</span>';
                }
            }
        }
        if (!isset($valid)) {
            $valid = '';
        }

        return $valid;
    }

    /**
     * Obtains the HTML content of a visual indicator used to indicate whether
     * FTP connectivity has been established based on the configured values
     *
     * @return string The HTML representation of the status indicator, or the
     *                empty string if not applicable
     */
    private static function get_ftp_config_indicator() {
        $config = get_config('elis_files');

        $result = '';

        // Only care to show an indicator if we are actually using FTP
        if ($config->file_transfer_method == ELIS_FILES_XFER_FTP) {
            // Assume connection is valid until we discover otherwise
            $connection_valid = true;

            if (empty($config->ftp_port)) {
                // Port is not properly configured, so we can't connect
                $connection_valid = false;
            } else {
                $uri = parse_url($config->server_host);

                if (!($ftp = ftp_connect($uri['host'], $config->ftp_port, 5))) {
                    // Can't connect
                    $connection_valid = false;
                } else if (!ftp_login($ftp, $config->server_username, $config->server_password)) {
                    // Can't log in
                    $connection_valid = false;
                    ftp_close($ftp);
                } else {
                    // Successfully connected
                    ftp_close($ftp);
                }
            }

            if ($connection_valid) {
                // Success
                $result = '<span class="pathok" style="color:green;">&#x2714;</span>';
            } else {
                // Failure
                $result = '<span class="patherror" style="color:red;">&#x2718;</span>';
            }
        }

        return $result;
    }

    function get_full_name() {
        return 's_'.$this->plugin.'_'.$this->name;
    }

    function get_id() {
        return 'id_s_'.$this->plugin.'_'.$this->name;
    }

    /**
     * Check for required PHP extensions depending on the file transfer method
     *
     * @return bool
     */
    public static function plugin_init() {
        if (get_config('elis_files', 'file_transfer_method') == ELIS_FILES_XFER_FTP && !function_exists('ftp_connect')) {
            print_error('ftpmustbeenabled', 'repository_elis_files');
            return false;
        }

        if (get_config('elis_files', 'file_transfer_method') == ELIS_FILES_XFER_WS && !function_exists('curl_init')) {
            print_error('curlmustbeenabled', 'repository_elis_files');
            return false;
        }

        return true;
    }

    /**
     * We want to make sure that not only is this plugin enabled and visible but also that the remote Alfresco
     * repository is currently up and running also.
     *
     * @return boolean
     */
    public function is_visible() {
        global $COURSE, $USER;

        if (!parent::is_visible() || !isset($this->elis_files) || empty($this->elis_files->isrunning)) {
            return false;
        }

        $uid = $USER->id;
        $courseid = $COURSE->id;
        $cid = ($courseid != SITEID) ? $courseid : 0;
        $oid = 0;
        $shared = (boolean)0;

        // ELIS files is visible if the user has any permissions from default browsing location
        return $this->elis_files->get_default_browsing_location($cid, $uid, $shared, $oid);
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL /* | FILE_EXTERNAL */ | FILE_REFERENCE); // TBD
    }

    /// FILE FUNCTIONS //////////////////////////////////////////////////////

    /**
     * Does this repository used to browse moodle files?
     *
     * @return bool
     */
    public function has_moodle_files() {
        return false;
    }

    /**
     * Return human readable reference information
     * {@link stored_file::get_reference()}
     *
     * @param string $reference
     * @param int $filestatus status of the file, 0 - ok, 666 - source missing
     * @return string
     */
    public function get_reference_details($reference, $filestatus = 0) {
        //error_log("repository/elis_files/lib.php::get_reference_details({$reference}, {$filestatus})");
        return $this->get_link($reference);
    }


    /**
     * Recursively generate list of files to be deleted
     *
     * @param   array   $file_array initial list of files
     * @param   array   $filelist   updateable list of files
     * @return  string  $str        breadcrumb string
     */
    function printfilelist($file_array, &$filelist= array(), $encoded = true) {
        global $CFG, $OUTPUT;

        $str = '';
        foreach ($file_array as $encodedpath) {
            // Decode path and retrieve parameters for parent folder
            if (!empty($encodedpath) && $encoded) {
                $params = unserialize(base64_decode($encodedpath));

                if (is_array($params)) {
                    $uuid    = empty($params['path']) ? NULL : clean_param($params['path'], PARAM_PATH);
                } else {
                    $uuid = NULL;
                }
            } else {
                $uuid = $encodedpath;
            }
            $file = $this->elis_files->get_info($uuid);

            if ($this->elis_files->is_dir($uuid)) {
                $icon = $OUTPUT->pix_url('f/folder-32');
                $str .= "<img src=\"{$icon}\" height=\"16\" width=\"16\" alt=\"\" /> " .
                     $file->title . "<br />";

                //also add to a hidden form element for each file
                $filelist[]= $uuid;
                $subfilelist = array();

                if ($currdir = $this->elis_files->read_dir($uuid)) {
                    if (!empty($currdir->folders)) {
                        foreach ($currdir->folders as $folder) {
                            $subfilelist[] = $folder->uuid;
                        }
                    }

                    if (!empty($currdir->files)) {
                        foreach ($currdir->files as $file) {
                            $subfilelist[] = $file->uuid;
                        }
                    }
                }

                $str .= self::printfilelist($subfilelist, $filelist, false);
            } else {

                $icon = $OUTPUT->pix_url(file_extension_icon($file->icon, 90));
                $filename = $file->filename;
                $str .="<img src=\"{$icon}\"  height=\"16\" width=\"16\" alt=\"\" /> " .
                     $file->filename . "<br />";
                //also add to a hidden form element for each file
                $filelist[]= $uuid;
            }
        }
        return $str;
    }

    /**
     * Check the current user's capability to edit the current node
     * @param int       $id     course id related to uuid
     * @param int       $shared shared flag related to uuid
     * @param int       $oid    user set id related to uuid
     * @param string    $uuid   node uuid
     * @param int       $userid user id related to uuid
     * @return boolean  $canedit    Return true or false
     */
    function check_editing_permissions($id, $shared, $oid, $uuid, $userid) {
        global $USER;

        /// Get the context instance for where we originated viewing this browser from.
        //error_log("check_editing_permissions({$id}, {$shared}, {$oid}, {$uuid}, {$userid})");
        if (!empty($oid)) {
            $userset_context = context_elis_userset::instance($oid);
        }
        if ($id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $id);
        }
        // Get the non context based permissions
        $capabilities = array('repository/elis_files:createowncontent'=> false,
                              'repository/elis_files:createsharedcontent'=> false);
        $this->elis_files->get_other_capabilities($USER, $capabilities);
        $canedit = false;

        $syscontext = context_system::instance();
        $site_files_permission = has_capability('repository/elis_files:createsitecontent', $syscontext);

        if (empty($userid) && empty($shared) && empty($oid)) {
            $has_permission = $site_files_permission ||
                              $id == SITEID && has_capability('repository/elis_files:createsitecontent', $context) ||
                              $id != SITEID && has_capability('repository/elis_files:createcoursecontent', $context);
            if ($has_permission) {
                $canedit = true;
            }
        } else if (empty($userid) && $shared == true) {
            $canedit = $site_files_permission ||
                       $capabilities['repository/elis_files:createsharedcontent'];
        } else {
            if (($USER->id == $userid) && empty($oid)) {
                $canedit = $site_files_permission ||
                           $capabilities['repository/elis_files:createowncontent'];
            } else {
                if (has_capability('repository/elis_files:createsitecontent', $context, $USER->id)) {
                    $canedit = true;
                } else if (!empty($oid) && has_capability('repository/elis_files:createusersetcontent', $userset_context)) {
                    $canedit = true;
                }
            }
        }
        return $canedit;
    }

    /**
     * Check the current user's capability to view the current node
     * @param int       $id     course id related to uuid
     * @param int       $shared shared flag related to uuid
     * @param int       $oid    user set id related to uuid
     * @param string    $uuid   node uuid
     * @param int       $userid user id related to uuid
     * @uses  $USER
     * @return boolean  Return true or false
     */
    function check_viewing_permissions($id, $shared, $oid, $uuid, $userid) {
        global $USER;

        /// Get the context instance for where we originated viewing this browser from.
        //error_log("check_viewing_permissions({$id}, {$shared}, {$oid}, {$uuid}, {$userid})");
        if (!empty($oid)) {
            $userset_context = context_elis_userset::instance($oid);
        }
        if ($id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $id);
        }
        // Get the non context based permissions
        $capabilities = array('repository/elis_files:viewowncontent'=> false,
                              'repository/elis_files:viewsharedcontent'=> false);
        $this->elis_files->get_other_capabilities($USER, $capabilities);
        $canview = false;

        $syscontext = context_system::instance();
        $site_files_permission = has_capability('repository/elis_files:viewsitecontent', $syscontext);

        if (empty($userid) && empty($shared) && empty($oid)) {
            $has_permission = $site_files_permission ||
                              $id == SITEID && has_capability('repository/elis_files:viewsitecontent', $context) ||
                              $id != SITEID && has_capability('repository/elis_files:viewcoursecontent', $context);
            if ($has_permission) {
                $canview = true;
            }
        } else if (empty($userid) && $shared == true) {
            $canview = $site_files_permission ||
                       $capabilities['repository/elis_files:viewsharedcontent'];
        } else {
            if (($USER->id == $userid) && empty($oid)) {
                $canview = $site_files_permission ||
                           $capabilities['repository/elis_files:viewowncontent'];
            } else {
                if (has_capability('repository/elis_files:viewsitecontent', $context, $USER->id)) {
                    $canview = true;
                } else if (!empty($oid) && has_capability('repository/elis_files:viewusersetcontent', $userset_context)) {
                    $canview = true;
                }
            }
        }
        return $canview;
    }

    /**
     * Check whether the current user has permissions to to edit the node
     * whose UUID and flags are specified in the provided "decoded path"
     *
     * @param array $decodedpath The information about the node, including the UUID
     *                           and all related flags
     * @return boolean Whether the user has permissions on the provided node
     */
    function can_edit_decoded_path($decodedpath) {
        $required_properties_set = !empty($decodedpath['path']) && isset($decodedpath['cid']) &&
                                   isset($decodedpath['shared']) && isset($decodedpath['oid']) &&
                                   isset($decodedpath['uid']);

        if (!$required_properties_set) {
            // Missing essential data
            return false;
        }

        // Metadata
        $cid = $decodedpath['cid'];
        $shared = $decodedpath['shared'];
        $oid = $decodedpath['oid'];
        $userid = $decodedpath['uid'];

        // Node UUID
        $uuid = $decodedpath['path'];

        // Make sure we always have a course id that is valid
        if ($cid == 0) {
            $cid = SITEID;
        }

        return $this->check_editing_permissions($cid, $shared, $oid, $uuid, $userid);
    }

    /*
     * Calculate the 'top' of the breadcrumb and then call the requested get_parent_path method
     * @param   string  uuid    node uuid
     * @param   array   path    breadcrumb path to node uuid
     * @param   int     cid     course id related to node uuid
     * @param   int     uid     user id related to node uuid
     * @param   int     shared  shared flag related to node uuid
     * @param   int     oid     user set id related to node uuid
     * @param   string  type    type of parent path retrieval - either tree or parent
     * @return  boolean         Return true if uuid is at root = e.g. end = uuid
     */
    function get_parent_path($uuid, &$path, $cid, $uid, $shared, $oid, $type = 'parent') {
        if (ELIS_FILES_DEBUG_TRACE) mtrace("\n".'get_alt_parent_path ' . $uuid . ', ' . $cid . ', ' . $uid . ', ' . $shared . ', ' . $oid . ')');

        // Call the appropriate get_parent_path method
        if ($type == 'tree') {
            $foldertree = elis_files_folder_structure();
            self::get_parent_path_from_tree($uuid, $foldertree, $path, $cid, $uid, $shared, $oid);

            // add Company Home to the top of the array that has been returned, as elis files folder structure does not return Company Home
            $encodedpath = self::build_encodedpath($this->elis_files->get_root()->uuid, $uid, $cid, $oid, $shared);
            $folderparent = array('name'=> $this->elis_files->get_root()->title,'path'=>$encodedpath);
            array_unshift($path,$folderparent);
        } elseif ($type == 'parent') {
            self::get_parent_path_from_parent($uuid, $path, $cid, $uid, $shared, $oid);
            $path = array_reverse($path);
        }
    }

    /**
     * Calculate parent path from parent for this uuid
     * This method calls get_parent for the current uuid until the end is found
     *
     * @param   string  uuid    node uuid
     * @param   array   path    breadcrumb path to node uuid
     * @param   int     cid     course id related to node uuid
     * @param   int     uid     user id related to node uuid
     * @param   int     shared  shared flag related to node uuid
     * @param   int     oid     user set id related to node uuid
     * @return  boolean         Return true if uuid is at root = e.g. end = uuid
     */
    function get_parent_path_from_parent($uuid, &$path, $cid, $uid, $shared, $oid) {

        if ($uuid == $this->elis_files->get_root()->uuid) {
            return true;
        }

        if (!($parent_node = $this->elis_files->get_parent($uuid))) {
            return false;
        }

        //we want to find the path via elis_files_folder_structure
        $encodedpath = self::build_encodedpath($parent_node->uuid, $uid, $cid, $oid, $shared);
        $path[] = array('name'=>$parent_node->title,'path'=>$encodedpath);
        self::get_parent_path_from_parent($parent_node->uuid,$path, $cid, $uid, $shared, $oid);
    }

    /**
     * Calculate parent path from parent for this uuid
     * This method recursively goes through the elis_files folder structure until the uuid is found
     * and then walks back up again to get the path
     * This was written in the hope that it would be more efficient, however, according to the
     * associated unit test, it's no much quicker - but will be kept in the code  for now
     *
     * @param   string  uuid    node uuid
     * @param   array   foldertree  array of folders in alfresco - gets updated recursively
     * @param   array   path    breadcrumb path to node uuid
     * @param   int     cid     course id related to node uuid
     * @param   int     uid     user id related to node uuid
     * @param   int     shared  shared flag related to node uuid
     * @param   int     oid     user set id related to node uuid
     * @param   bool    reset   internal use only!
     * @return  boolean         Return true if uuid is at root = e.g. end = uuid
     */
    public function get_parent_path_from_tree($uuid, $foldertree, &$resultpath, $cid, $uid, $shared, $oid, $reset = true) {
        static $found_parent = 0;
        if ($reset) {
            $found_parent = 0;
        }
        if (!$found_parent) {
            $found_parent = 1;
            $info = $this->elis_files->get_info($uuid);
            if ($info->type != ELIS_files::$type_folder &&
                ($node = $this->elis_files->get_parent($uuid)) &&
                !empty($node->uuid)) {
                $uuid = $node->uuid;
                $found_parent = 2;
            }
        }

        // Find matching
        foreach ($foldertree as $folder) {
            $encodedpath = self::build_encodedpath($folder['uuid'], $uid, $cid, $oid, $shared);
            $resultpath[] = array('name'=>$folder['name'],'path'=>$encodedpath);
            if ($uuid == $folder['uuid']) {
                if ($found_parent == 1) {
                    $removed = array_pop($resultpath);
                }
                return true;
            }
            if (!empty($folder['children'])) {
                if (self::get_parent_path_from_tree($uuid, $folder['children'], $resultpath, $cid, $uid, $shared, $oid, false)) {
                    return true;
                }
            }
            $removed = array_pop($resultpath);
        }
        return false;
    }

    function category_tree() {
        global $DB;

        $tree = array();

        // fetch all the categories
        $fulltree = $DB->get_records('elis_files_categories');

        // fetch the selected categories from config
        $catfilter_serialized = get_config('elis_files', 'catfilter');

        if ($catfilter = unserialize($catfilter_serialized)) {
            // build the new filtered tree
            foreach ($fulltree as $branch) {
                if (in_array($branch->id, $catfilter)) {
                    $tree[$branch->id] = $branch;
                }
            }

            // lets make sure that all remaining branches without parents are set to their top level now
            foreach ($tree as $branch) {
                if (!array_key_exists($branch->parent, $tree)) {
                    // TO-DO: will probably want to figure out the entire parent path from the fulltree
                    //        and assign the next highest parent that is found to exist in the selected tree
                    //        instead of just assigning to top level (need to wait until spec is defined)
                    $tree[$branch->id]->parent = 0;
                }
            }
        } else {
            $tree = $fulltree;
        }

        return $tree;
    }

}
