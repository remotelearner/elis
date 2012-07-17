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
 * @package    repository
 * @subpackage elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 */



// Define constants for the default file browsing location.
defined('ELIS_FILES_BROWSE_SITE_FILES') or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
// Was shared now called server files
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES') or define('ELIS_FILES_BROWSE_USER_FILES',   50);

defined('ELIS_FILES_SELECT_ALFRESCO_VERSION') or define('ELIS_FILES_SELECT_ALFRESCO_VERSION', null);
defined('ELIS_FILES_ALFRESCO_30') or define('ELIS_FILES_ALFRESCO_30',   '3.2');
defined('ELIS_FILES_ALFRESCO_34') or define('ELIS_FILES_ALFRESCO_34',   '3.4');

// Setup options for the method to transfer files into Alfresco from Moodle
defined('ELIS_FILES_XFER_WS') || define('ELIS_FILES_XFER_WS', 'webservices');
defined('ELIS_FILES_XFER_FTP') || define('ELIS_FILES_XFER_FTP', 'ftp');


class repository_elis_files extends repository {
    private $ticket = null;
    private $user_session = null; // probably don't need this
    private $store = null;
    public $elis_files;
    var $config   = null; // Config options for ELIS files


    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION, $CFG, $PAGE;
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
            $result = $CFG->wwwroot.'/repository/elis_files/openfile.php?uuid='.$node->uuid;
        }
        return $result;
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
        if (!$this->elis_files->get_defaults()) {
            return false;
        }

        // Check for a TRUE value in the encodedpath and retrieve the location
        // If we don't have something explicitly to load and we didn't get here from the drop-down...
        if ($encodedpath === true || empty($encodedpath)) {

            // Check referer to set proper cid and uid defaults
            $referer = get_referer(false);
            if ($referer !== false) {
                $fromelisfilescoursepage  = stristr($referer, $CFG->wwwroot . '/repository/filemanager.php') !== false;
                $fromcoursepage           = stristr($referer, $CFG->wwwroot . '/course/modedit.php') !== false;
                $fromuserpage             = stristr($referer, $CFG->wwwroot . '/user/filesedit.php') !== false;
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

        // Only use for current node
        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));

        // Get editing privileges - set canedit flag...
        $canedit = repository_elis_files::check_editing_permissions($COURSE->id, $shared, $oid, $uuid, $uid);
        $ret['canedit'] = $canedit;
        $return_path = array();

        // Get parent path/breadcrumb
        repository_elis_files::get_parent_path($uuid, $return_path, $cid, $uid, $shared, $oid);
        $return_path[]= array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>'');
        $ret['path'] = array_reverse($return_path);

        $this->current_node = $this->elis_files->get_info($uuid);

        // Add current node to the return path
        // Include shared and oid parameters
        $params = array('path'=>$uuid,
                        'shared'=>(boolean)$shared,
                        'oid'=>(int)$oid,
                        'cid'=>(int)$cid,
                        'uid'=>(int)$uid);
        $encodedpath = base64_encode(serialize($params));
        $ret['path'][] = array('name'=>$this->current_node->title,
                               'path'=>$encodedpath);

        // Unserialized array of path/shared/oid
        $ret['thisuuid'] = $params;
        $ret['thisuuid']['encodedpath'] = $encodedpath;

        // Store the UUID value that we are currently browsing.
        $this->elis_files->set_repository_location($uuid, $cid, $uid, $shared, $oid);

        $children = elis_files_read_dir($this->current_node->uuid);
        $ret['list'] = array();

        // Check that there are folders to list
        if (isset($children->folders) && is_array($children->folders)) {
            foreach ($children->folders as $child) {
                if (!$this->elis_files->permission_check($child->uuid, $USER->id, false)) {
                    continue;
                }

                // Include shared and oid parameters
                $params = array('path'=>$child->uuid,
                                'shared'=>(boolean)$shared,
                                'oid'=>(int)$oid,
                                'cid'=>(int)$cid,
                                'uid'=>(int)$uid);
                $encodedpath = base64_encode(serialize($params));
                $ret['list'][] = array('title'=>$child->title,
                        'path'=>$encodedpath, //$child->uuid,
                        'name'=>$child->title,
                        'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . '',
                        'created'=>'',
                        'modified'=>'',
                        'owner'=>'',
                        'children'=>array());
            }
        }

        // Check that there are files to list
        if (isset($children->files) && is_array($children->files)) {
            foreach ($children->files as $child) {
                // Check permissions first
                if (!$this->elis_files->permission_check($child->uuid, $USER->id, false)) {
                    continue;
                }

                $params = array('path'=>$child->uuid,
                                'shared'=>(boolean)$shared,
                                'oid'=>(int)$oid,
                                'cid'=>(int)$cid,
                                'uid'=>(int)$uid);
                $encodedpath = base64_encode(serialize($params));

                $ret['list'][] = array('title'=>$child->title,
                        'path'=>$encodedpath, //$child->uuid,
                        'thumbnail' => $OUTPUT->pix_url(file_extension_icon($child->title, 32))->out(false),
                        'created'=>date("M. j, Y",$child->created),
                        'modified'=>date("M. j, Y",$child->modified),
                        'owner'=>$child->owner,
                        'source'=>$child->uuid); // or links['self']???
            }
        }

        return $ret;
    }

    /**
     * Download a file from alfresco - do we use this?
     *
     * @param string $uuid a unique id of directory in alfresco
     * @param string $path path to a directory
     * @return array
     */
    public function get_file($uuid, $file = '') {

        $node = $this->elis_files->get_info($uuid);

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

        return array('path'=>$path, 'url'=>$url);
    }

    /**
     * Return file URL
     *
     * @uses $CFG
     * @param string $url the url of file
     * @return string
     */
    public function get_link($uuid) {
        global $CFG;

        return $CFG->wwwroot.'/repository/elis_files/openfile.php?uuid='.$uuid;
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
            $params = array('path'=>$child->uuid,
                             'shared'=>(boolean)$shared,
                             'oid'=>(int)$oid,
                             'cid'=>(int)$cid,
                             'uid'=>(int)$uid);
            $encodedpath = base64_encode(serialize($params));
            $return[] = array('title'=>$child->title,
                    'path'=>$encodedpath,
                    'name'=>$child->title,
                    'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . '',
                    'created'=>'',
                    'modified'=>'',
                    'owner'=>'',
                    'children'=>array());
        }
        return $return;
    }


    public function print_search() {
        global $CFG, $DB;

        require_once $CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php';
        require_once $CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php';
        require_once $CFG->dirroot.'/repository/elis_files/lib/HTML_TreeMenu-1.2.0/TreeMenu.php';
        require_once $CFG->dirroot.'/repository/elis_files/tree_menu_lib.php';

        $str = '<p>'.get_string('searchforfilesinrepository', 'repository_elis_files').'</p>';

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

        $str .= repository_elis_files::printfilelist($files_array, $filelist);
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
     * Look for a file
     *
     * @param string $search_text
     * @return array
     */
    public function search($search_text, $page = 1, $categories = NULL) {
        global $OUTPUT, $DB, $COURSE, $USER;

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

        if (empty($uuid)) {
            if ($ruuid = $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $oid)) {
                $uuid = $ruuid;
            } else if ($duuid = $this->elis_files->get_default_browsing_location($COURSE->id, $userid, $shared, $oid)) {
                $uuid = $duuid;
            }
            $uuuid = $this->elis_files->get_user_store($USER->id);
            if ($uuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
        } else {
            $uuuid = $this->elis_files->get_user_store($USER->id);
            if ($uuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
            if ($this->elis_files->permission_check($uuid, $USER->id, false)) {
                $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context);
            }
        }

        $canedit = repository_elis_files::check_editing_permissions($COURSE->id, $shared, $oid, $uuid, $uid);

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


        if (!empty($search_text)) {
            $search_result = elis_files_search($search_text);

            // Convert elis category IDs to matching repository category UUIDs
            $category_uuids = array();
            if (is_array($categories))
            {
                foreach ($categories as $category_id) {
                    $category_result = $DB->get_record('elis_files_categories', array('id'=> $category_id));
                    if (!empty($category_result)) {
                        $category_uuids[] = $category_result->uuid;
                    }
                }
            }

            if (!empty($search_result->files)) {
                foreach ($search_result->files as $file_object) {
                    // See if we have categories that we need to check against
                    if (!empty($category_uuids)) {
                        $found_category = false;
                        $category_result = elis_files_get_node_categories($file_object->noderef, $file_object->uuid);
                        foreach ($category_uuids as $category_uuid) {
                            if (!empty($category_result[$category_uuid])) {
                                $found_category = true;
                                break;
                            }
                        }
                        if (!$found_category) {
                            continue;
                        }
                    }

                    // Include shared and oid parameters
                    $uuid = $file_object->uuid;
                    $params = array('path'=>$uuid,
                                    'shared'=>(boolean)$shared,
                                    'oid'=>(int)$oid,
                                    'cid'=>(int)$cid,
                                    'uid'=>(int)$uid);
                    $encodedpath = base64_encode(serialize($params));

                    $alfresco_version = elis_files_get_repository_version();
                    if ($alfresco_version == '3.2.1') {
                      $thumbnail = $OUTPUT->pix_url(file_extension_icon($file_object->filename, 32))->out(false);
                    } else {
                      $thumbnail = $OUTPUT->pix_url(file_extension_icon($file_object->icon, 32))->out(false);
                    }
                    $ret['list'][] = array('title'=>$file_object->title,
                                           'path'=>$encodedpath,
                                           'thumbnail' => $thumbnail,
                                           'created'=>date("M. j, Y",$file_object->created),
                                           'modified'=>date("M. j, Y",$file_object->modified),
                                           'owner'=>$file_object->owner,
                                           'source'=>$file_object->uuid);
                }
            }
        }

        return $ret;
    }

    /**
     * Return names of general options for this plugin
     * They can then be accessed in the construct with $this->
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
            'admin_username'        // The override for a Moodle account using the 'admin' username
        );

        return $option_names;
    }

    /**
     * Add Plugin settings input to Moodle form
     * @param object $mform
     */
    public function type_config_form($mform) {
        global $DB, $CFG, $SESSION, $OUTPUT;

        parent::type_config_form($mform);

        $mform->addElement('text', 'server_host', get_string('serverurl', 'repository_elis_files'), array('size' => '40'));
        $mform->setDefault('server_host', 'http://localhost');
        $mform->addElement('static', 'server_host_default', '', get_string('elis_files_default_server_host', 'repository_elis_files'));
        $mform->addElement('static', 'server_host_intro', '', get_string('elis_files_server_host', 'repository_elis_files'));
        $mform->addRule('server_host', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'server_port', get_string('serverport', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_port_default', '', get_string('elis_files_default_server_port', 'repository_elis_files'));
        $mform->addElement('static', 'server_port_intro', '', get_string('elis_files_server_port', 'repository_elis_files'));

        $mform->addRule('server_port', get_string('required'), 'required', null, 'client');
        $mform->setDefault('server_port', '8080');

        $mform->addElement('text', 'server_username', get_string('serverusername', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_username_default', '', get_string('elis_files_default_server_username', 'repository_elis_files'));
        $mform->addElement('static', 'server_username_intro', '', get_string('elis_files_server_username', 'repository_elis_files'));
        $mform->addRule('server_username', get_string('required'), 'required', null, 'client');

        $mform->addElement('passwordunmask', 'server_password', get_string('serverpassword', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_password_intro', '', get_string('elis_files_server_password', 'repository_elis_files'));
        $mform->addRule('server_password', get_string('required'), 'required', null, 'client');

        $options = array(
            ELIS_FILES_XFER_WS  => get_string('webservices', 'repository_elis_files'),
            ELIS_FILES_XFER_FTP => get_string('ftp', 'repository_elis_files')
        );

        $mform->addElement('select', 'file_transfer_method', get_string('filetransfermethod', 'repository_elis_files'), $options);
        $mform->setDefault('file_transfer_method', ELIS_FILES_XFER_FTP);
        $mform->addElement('static', 'file_transfer_method_default', '', get_string('filetransfermethoddefault', 'repository_elis_files'));
        $mform->addElement('static', 'file_transfer_method_desc', '', get_string('filetransfermethoddesc', 'repository_elis_files'));

        $mform->addElement('text', 'ftp_port', get_string('ftpport', 'repository_elis_files'), array('size' => '30'));
        $mform->setDefault('ftp_port', '21');
        $mform->addElement('static', 'ftp_port_default', '', get_string('ftpportdefault', 'repository_elis_files'));
        $mform->addElement('static', 'ftp_port_desc', '', get_string('ftpportdesc', 'repository_elis_files'));

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
        $button = repository_elis_files::output_root_folder_html($root_folder);

        $rootfolderarray=array();
        $rootfolderarray[] = MoodleQuickForm::createElement('text', 'root_folder', get_string('rootfolder', 'repository_elis_files'), array('size' => '30'));
        $rootfolderarray[] = MoodleQuickForm::createElement('button', 'root_folder_popup', get_string('chooserootfolder', 'repository_elis_files'), $button);

        $mform->addGroup($rootfolderarray, 'rootfolderar', get_string('rootfolder', 'repository_elis_files'), array(' '), false);
        $mform->setDefault('root_folder', '/moodle');

        // Add checkmark if get root folder works, or whatever...
        $valid = repository_elis_files::root_folder_is_valid($root_folder);
        $mform->addElement('static', 'root_folder_default', '', $valid.'&nbsp;'.get_string('elis_files_default_root_folder', 'repository_elis_files'));
        $mform->addElement('static', 'root_folder_intro', '', get_string('elis_files_root_folder', 'repository_elis_files'));

        // Cache time is retrieved from the common cache time and displayed here
        $mform->addElement('text', 'cache_time', get_string('cachetime', 'repository_elis_files'), array('size' => '10'));
        $mform->addElement('static', 'cache_time_default', '', get_string('elis_files_default_cache_time', 'repository_elis_files'));
        $mform->setDefault('cache_time', $CFG->repositorycacheexpire);
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

        $admin_username = trim(get_config('admin_username','elis_files'));
        if (empty($admin_username)) {
            $adminusername = 'moodleadmin';
            set_config('admin_username', $adminusername, 'elis_files');
        } else {
            $adminusername = $admin_username;
        }

        // Only proceed here if the Alfresco plug-in is actually enabled.
        if (repository_elis_files::is_repo_visible('elis_files')) {
            if ($repo = repository_factory::factory()) {
                if (elis_files_get_home_directory($adminusername) == false) {
                    $mform->addElement('text', 'admin_username', get_string('adminusername', 'repository_elis_files'), array('size' => '30'));
                    $mform->addElement('static', 'admin_username_default', '', get_string('elis_files_default_admin_username', 'repository_elis_files'));
                    $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusername', 'repository_elis_files'));
                } else {
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
    function is_repo_visible($typename) {
        global $DB;
        if (!$record = $DB->get_record('repository',array('type' => $typename))) {
            return false;
        }
        if ($record->visible) {
            return true;
        }
    }

    function output_root_folder_html($data, $query = '') {
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
    function root_folder_is_valid($data) {
        $repoisup = false;

    /// Validate the path, if we can.
        if ($repo = repository_factory::factory()) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();
            if ($repoisup) {
                if (elis_files_validate_path($data)) {
                    $valid = '<span class="pathok">&#x2714;</span>';
                } else {
                    $valid = '<span class="patherror">&#x2718;</span>';
                }
            }
        }
        if (!isset($valid)) {
            $valid = '';
        }

        return $valid;
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
        if (!parent::is_visible()) {
            return false;
        }

        return (isset($this->elis_files) && !empty($this->elis_files->isrunning));
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    /// FILE FUNCTIONS ///////////////////////////////////////////////////////////


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

                $str .= repository_elis_files::printfilelist($subfilelist, $filelist, false);
            } else {

                $icon = $OUTPUT->pix_url(file_extension_icon($file->icon, 32));
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
        if (!empty($oid)) {
            $userset_context = context_elis_userset::instance($oid);
        }
        if ($id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $id);
        }
        // Get the non context based permissions
        $capabilities = array(
            'repository/elis_files:createowncontent'=> false,
            'repository/elis_files:createsharedcontent'=> false
        );

        $this->elis_files->get_other_capabilities($capabilities);

        $canedit = false;

        if (empty($userid) && empty($shared) && empty($oid)) {
            if (($id == SITEID && has_capability('repository/elis_files:createsitecontent', $context)) ||
                ($id != SITEID && has_capability('repository/elis_files:createcoursecontent', $context))) {
                $canedit = true;
            }
        } else if (empty($userid) && $shared == true) {
            $canedit = $capabilities['repository/elis_files:createsharedcontent'];
        } else {
            if (($USER->id == $userid) && empty($oid)) {
                $canedit = $capabilities['repository/elis_files:createowncontent'];
            } else {
                if (empty($oid) && has_capability('repository/elis_files:createsitecontent', $context, $USER->id)) {
                    $canedit = true;
                } else if (!empty($oid) && has_capability('repository/elis_files:createusersetcontent', $userset_context, $USER->id)) {
                    $canedit = true;
                }
            }
        }
        return $canedit;
    }

    /**
     * Calculate parent path for this uuid
     *
     * @param   string  uuid    node uuid
     * @param   array   path    breadcrumb path to node uuid
     * @param   int     cid     course id related to node uuid
     * @param   int     uid     user id related to node uuid
     * @param   int     shared  shared flag related to node uuid
     * @param   int     oid     user set id related to node uuid
     * @return  boolean         Return true if uuid is at root = e.g. end = uuid
     */
    function get_parent_path($uuid, &$path = array(), $cid, $uid, $shared, $oid) {
        if (ELIS_FILES_DEBUG_TRACE) mtrace("\n".'get_parent_path ' . $uuid . ', ' . $cid . ', ' . $uid . ', ' . $shared . ', ' . $oid . ')');
    /// Get the "ending" UUID for the 'root' of navigation.
        if ((empty($cid) || $cid == SITEID) && empty($uid) && empty($shared) && empty($oid)) {
            $end   = $this->elis_files->get_root()->uuid;
        } else if (empty($uid) && $shared == true) {
            $end = $this->elis_files->suuid;
        } else if (empty($uid) && empty($oid) && empty($shared) && !empty($cid) && $cid != SITEID) {
            $end = $this->elis_files->get_course_store($cid);
        } else if (!empty($uid)) {
            $end = $this->elis_files->get_user_store($uid);
        } else if (empty($uid) && !empty($oid)) {
            $end = $this->elis_files->get_userset_store($oid);
        }

        // Need to incorporate 'end' testing into here somehow... :)
        if ($uuid == $end) {
            return true;
        }

        if (!$parent_node = $this->elis_files->get_parent($uuid)) {
            return false;
        }

        if ($parent_node) {
            // Include shared and oid parameters
            $params = array('path'=>$parent_node->uuid,
                            'shared'=>(boolean)$shared,
                            'oid'=>(int)$oid,
                            'cid'=>(int)$cid,
                            'uid'=>(int)$uid);
            $encodedpath = base64_encode(serialize($params));
            $path[] = array('name'=>$parent_node->title,'path'=>$encodedpath);
            repository_elis_files::get_parent_path($parent_node->uuid,$path, $cid, $uid, $shared, $oid);
        }
    }

    /**
     * Get the applicable location parent of the given uuid
     *
     * @param   string  $uuid   given uuid
     * @param   int     $cid    related course id
     * @param   int     $uid    related user id
     * @param   int     $shared related shared flag
     * @param   int     $oid    related user set id
     * @return  string  $parent location parent of the given uuid (e.g. course/shared/user/site files)
     **/
    function get_location_parent($uuid, $cid, $uid, $shared, $oid) {
        global $COURSE, $USER;

         // Get encoded path for uuid
        $params = array('path'=>$uuid,
                        'shared'=>(boolean)$shared,
                        'oid'=>(int)$oid,
                        'cid'=>(int)$cid,
                        'uid'=>(int)$uid);
        $encodedpath = base64_encode(serialize($params));

        // Set permissible browsing locations
        $locations = array();
        $createonly = true;
        $this->elis_files->file_browse_options($cid, $uid, $shared, $oid, $locations, $createonly);
        $parent_path = array();
        // Get encoded parent path
        $result = repository_elis_files::get_parent_path($uuid, $parent_path, $cid, $uid, $shared, $oid);
        array_reverse($parent_path);


        // Check if current path IS the parent! // ELIS Site Files
        if ($result === true && empty($parent_path)) {
            // Parent was same as uuid sent... need to get name of uuid...
            $node = $this->elis_files->get_info($uuid);
            $parent = array('name'=> $node->title,
                            'path'=> $encodedpath);
        } else if (empty($parent_path)) {
           // Parent not found, return site files as parent
            $params = array('path'=>$this->elis_files->get_root(),
                            'shared'=>(boolean)0,
                            'oid'=>0,
                            'cid'=>(int)SITEID,
                            'uid'=>0);
            $encodedpath = base64_encode(serialize($params));
            $parent = array('name'=> get_string('repositorysitefiles','repository_elis_files'),
                            'path'=> $encodedpath);
        } else {
            // first check if the uuid passed in is a location
            $parent_found = repository_elis_files::search_array($locations, 'path', $encodedpath);
            if (empty($parent_found)) {
                // find the closest location of the locations...
                foreach ($parent_path as $parent) {
                    $parent_found = repository_elis_files::search_array($locations, 'path', $parent['path']);
                    if (!empty($parent_found)) {
                        $parent = $parent_found[0];
                        break;
                    }
                }
            } else {
                $parent = $parent_found[0];
            }
        }
        return $parent;
    }

    function search_array($array, $key, $value) {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, repository_elis_files::search_array($subarray, $key, $value));
            }
        }

        return $results;
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
