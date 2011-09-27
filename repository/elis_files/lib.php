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
 * @copyright  2009 Dongsheng Cai
 * @author     Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



// Define constants for the default file browsing location.
//define('ELIS_FILES_BROWSE_MOODLE_FILES',          10);
defined('ELIS_FILES_BROWSE_SITE_FILES') or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
// Were shared now called server files
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
//defined('ELIS_FILES_BROWSE_SERVER_FILES') or define('ELIS_FILES_BROWSE_SERVER_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES') or define('ELIS_FILES_BROWSE_USER_FILES',   50);
defined('ELIS_FILES_BROWSE_USERSET_FILES') or define('ELIS_FILES_BROWSE_USERSET_FILES', 60);

defined('ELIS_FILES_SELECT_ALFRESCO_VERSION') or define('ELIS_FILES_SELECT_ALFRESCO_VERSION', null);
defined('ELIS_FILES_ALFRESCO_30') or define('ELIS_FILES_ALFRESCO_30',   '3.2');
defined('ELIS_FILES_ALFRESCO_34') or define('ELIS_FILES_ALFRESCO_34',   '3.4');


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

        /// ELIS files class
        $this->elis_files = repository_factory::factory('elis_files');
        $this->config = get_config('elis_files');
        $this->current_node = null;

        // jQuery files required for file picker - just for this repository
        $PAGE->requires->js('/repository/elis_files/js/jquery-1.6.2.min.js');
        $PAGE->requires->js('/repository/elis_files/js/jquery-ui-1.8.16.custom.min.js');
        $PAGE->requires->js('/repository/elis_files/js/fileuploader.js');
        $PAGE->requires->js('/repository/elis_files/lib/HTML_TreeMenu-1.2.0/TreeMenu.js', true);
    }

    public function print_login() {
        if ($this->options['ajax']) {
            $user_field = new stdClass();
            $user_field->label = get_string('username', 'repository_elis_files').': ';
            $user_field->id    = 'elis_files_username';
            $user_field->type  = 'text';
            $user_field->name  = 'al_username';

            $passwd_field = new stdClass();
            $passwd_field->label = get_string('password', 'repository_elis_files').': ';
            $passwd_field->id    = 'elis_files_password';
            $passwd_field->type  = 'password';
            $passwd_field->name  = 'al_password';

            $ret = array();
            $ret['login'] = array($user_field, $passwd_field);
            return $ret;
        } else {
            echo '<table>';
            echo '<tr><td><label>'.get_string('username', 'repository_elis_files').'</label></td>';
            echo '<td><input type="text" name="al_username" /></td></tr>';
            echo '<tr><td><label>'.get_string('password', 'repository_elis_files').'</label></td>';
            echo '<td><input type="password" name="al_password" /></td></tr>';
            echo '</table>';
            echo '<input type="submit" value="Enter" />';
        }
    }

    public function logout() {
        global $SESSION;
        unset($SESSION->{$this->sessname});
        return $this->print_login();
    }

    public function check_login() {
        // TODO: for ELIS2 for now
        return true;
        global $SESSION;
        return !empty($SESSION->{$this->sessname});
    }

    private function get_url($node) {
        $result = null;
        if (isset($node->type) && $node->type == "cmis:document") {
            $result = $node->fileurl;
        } else {
            $result = "index.php?".
                "&uuid=".$node->uuid.
                "&name=".$node->filename.
                "&path=".'Company Home';
        }
        return $result;
    }

    /**
     * Get a file list from alfresco
     *
     * @param string $uuid a unique id of directory in alfresco
     * @param string $path path to a directory
     * @return array
     */
    public function get_listing($uuid = '', $path = '') {
        // We will be doing a fake file listing for testing
        global $CFG, $COURSE, $DB, $SESSION, $OUTPUT, $USER;
        $shared = '';
        // TODO - Need to do a context check... somewhere, maybe for each file that is listed, so during directory/file list?

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

        // Set up locations shortcuts - need a sort of url... hmmm... to be cross repository...
        // This used to be in file_browse_options - modify that then to work with this?
        /*
        $opts = $repo->file_browse_options($course->id, $userid, $ouuid, $shared, $choose, 'file/repository/index.php',
                                               'files/index.php', 'file/repository/index.php', $default);
        */

        // Set permissible browsing locations
        $ret['locations'] = array();
        $this->elis_files->file_browse_options($COURSE->id, '', $ret['locations']);

        $ret['dynload'] = true;
        $ret['nologin'] = true;
        $ret['showselectedactions'] = true;
        $ret['showcurrentactions'] = true;

        $server_url = $this->elis_files->get_repourl();

        // Only use for current
        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));

        // Set id
        if (!empty($USER->id)) {
            $id = $USER->id;
        }
        if (!empty($COURSE->id)) {
            $id = $COURSE->id;
        }

        // setting userid...
        if ($this->context->contextlevel === CONTEXT_USER) {
            $userid = $USER->id;
        } else {
            $userid = '';
        }

        // Find default $uuid
        // If we don't have something explicitly to load and we didn't get here from the drop-down...
        if (empty($uuid)) {
            if ($ruuid = $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context)) {
                $uuid = $ruuid;
            } else if ($duuid = $this->elis_files->get_default_browsing_location($COURSE->id, $userid, $shared, $this->context)) {
                $uuid = $duuid;
            }
            // uid here is actually only set iff current uuid == get_user_store($USER->id)
            $uuuid = $this->elis_files->get_user_store($USER->id);
            if ($uuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
        } else {
             // uid here is actually only set iff current uuid == get_user_store($USER->id)
            $uuuid = $this->elis_files->get_user_store($USER->id);
            if ($uuid == $uuuid) {
                $uid = $USER->id;
            } else {
                $uid = 0;
            }
            // We also need to validate the current node as a browsing location
            if (!$this->elis_files->permission_check($uuid, $uid, false)) {
                //failed permission check...
                //echo '<br>no permission to view: '.$uuid;
                //$uuid = $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context);
            } else {
                // We need a value for $shared, so...
                $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context);
            }

        }

        // Send the userid used for permissions
        $ret['uid'] = $uid;

        // Get editing privileges - set canedit flag...
        $canedit = repository_elis_files::check_editing_permissions($this->context, $COURSE->id, $uuid, $uid);
        $ret['canedit'] = $canedit;
        $return_path = array();
        repository_elis_files::get_parent_path($uuid, $return_path);
        $return_path[]= array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>'');
        $ret['path'] = array_reverse($return_path);

        $this->current_node = $this->elis_files->get_info($uuid);

        // Add current node to the return path
        $ret['path'][] = array ('name'=>$this->current_node->title,
                                'path'=>$uuid);

        $ret['thisuuid'] = $uuid;
        // Store the UUID value that we are currently browsing.
        $this->elis_files->set_repository_location($uuid, $COURSE->id, $uid, $shared);

        $children = elis_files_read_dir($this->current_node->uuid);
        $ret['list'] = array();

        foreach ($children->folders as $child) {
            if (!$this->elis_files->permission_check($child->uuid, $uid, false)) {
                break;
            }
            $ret['list'][] = array('title'=>$child->title,
                    'path'=>$child->uuid,
                    'name'=>$child->title,
                    'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . '',
                    'created'=>'',
                    'modified'=>'',
                    'owner'=>'',
                    'children'=>array());
        }
        foreach ($children->files as $child) {
            // Check permissions first
            if (!$this->elis_files->permission_check($child->uuid, $uid, false)) {
                break;
            }

            $ret['list'][] = array('title'=>$child->title,
                    'path'=>$child->uuid,
                    'thumbnail' => $OUTPUT->pix_url(file_extension_icon($child->title, 32))->out(false),
                    'created'=>date("M. j, Y",$child->created),
                    'modified'=>date("M. j, Y",$child->modified),
                    'owner'=>$child->owner,
                    'source'=>$child->uuid); // or links['self']???
        }
        return $ret;
    }

    /**
     * Download a file from alfresco
     *
     * @param string $uuid a unique id of directory in alfresco
     * @param string $path path to a directory
     * @return array
     */
    public function get_file($uuid, $file = '') {

        $node = $this->elis_files->get_info($uuid);

        /*
        if (isloggedin()) {
            $username = $USER->username;
        } else {
            $username = '';
        }
        */
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
     * @param string $url the url of file
     * @return string
     */
    public function get_link($uuid) {
        $node = $this->elis_files->get_info($uuid);
        $url = $this->get_url($node);
        return $url;
    }

    /*
     * Get Alfresco folders under current uuid
     */
    public function get_folder_listing($uuid, $uid) {
        global $OUTPUT, $USER;

        $children = elis_files_read_dir($uuid);
        $return = array();

        foreach ($children->folders as $child) {
            if (!$this->elis_files->permission_check($child->uuid, $uid, false)) {
                continue;
            }
            $return[] = array('title'=>$child->title,
                    'path'=>$child->uuid,
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
     * @param   string  $parentuuid     parent uuid passed for refreshing page
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
    public function print_move_dialog($parentuuid, $selected_files) {
        global $CFG;
        $str = '<div>
                    <div id="repository_tabs"></div>
                    <input type="hidden" id="parentuuid" name="parentuuid" value="'.$parentuuid.'">
                    <input type="hidden" name="selected_files" id="selected_files" value="'.implode(",",$selected_files).'">
                    <input id="targetfolder" type="hidden" value = "" />
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

        // TODO: These css includes will need to be moved elsewhere
        $str = '<link rel="stylesheet" href="'.$CFG->wwwroot.'/repository/elis_files/css/fileuploader.css" type="text/css" />
                <link rel="stylesheet" href="'.$CFG->wwwroot.'/repository/elis_files/css/jquery-ui-1.8.16.custom.css" type="text/css" media="screen" title="no title" charset="utf-8" />
                <p><b>'.get_string('uploadingtiptitle', 'repository_elis_files').'</b></p>
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
        $shared = '';

        // Set id
        if (!empty($USER->id)) {
            $id = $USER->id;
        }
        if (!empty($COURSE->id)) {
            $id = $COURSE->id;
        }
       // setting userid...
        if ($this->context->contextlevel === CONTEXT_USER) {
            $userid = $USER->id;
        } else {
            $userid = '';
        }

        if (empty($uuid)) {
            if ($ruuid = $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context)) {
                $uuid = $ruuid;
            } else if ($duuid = $this->elis_files->get_default_browsing_location($COURSE->id, $userid, $shared, $this->context)) {
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
            if ($this->elis_files->permission_check($uuid, $uid, false)) {
                $this->elis_files->get_repository_location($COURSE->id, $userid, $shared, $this->context);
            }
        }

        $canedit = repository_elis_files::check_editing_permissions($this->context, $COURSE->id, $uuid, $uid);

        $ret['canedit'] = $canedit;
        $ret['uid'] = $uid;
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

                    $ret['list'][] = array('title'=>$file_object->title,
                                           'thumbnail' => $OUTPUT->pix_url(file_extension_icon($file_object->filename, 32))->out(false),
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
        $option_names = array('pluginname',         //This is for an optional plugin name change
                              'server_host',        // URL for Alfresco server
                              'server_port',        // Alfresco server port
                              'server_username',    // Alfresco server username
                              'server_password',    // Alfresco server password
                              'alfresco_version',   // Alfresco server version
                              'root_folder',        // Moodle root folder
                              'user_quota',         // User quota N.B. cache is now pulled from general Repository options
                              'deleteuserdir',      // Whether or not to delete an Alfresco's user's folder when they are deleted in Moodle <= hmmmm
                              'default_browse'      // Where to start the file browsing session
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
        //$mform->addElement('static', 'elis_files_server_host_intro', '', get_string('elis_files_server_host', 'repository_elis_files'));
        $mform->addRule('server_port', get_string('required'), 'required', null, 'client');
        $mform->setDefault('server_port', '8080');

        $mform->addElement('text', 'server_username', get_string('serverusername', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_username_default', '', get_string('elis_files_default_server_username', 'repository_elis_files'));
        $mform->addElement('static', 'server_username_intro', '', get_string('elis_files_server_username', 'repository_elis_files'));
        $mform->addRule('server_username', get_string('required'), 'required', null, 'client');

        $mform->addElement('passwordunmask', 'server_password', get_string('serverpassword', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_password_intro', '', get_string('elis_files_server_password', 'repository_elis_files'));
        $mform->addRule('server_password', get_string('required'), 'required', null, 'client');

        $alfresco_version = get_config('elis_files', 'alfresco_version');
        // Set site version of Alfresco
        $options = array(
            ELIS_FILES_SELECT_ALFRESCO_VERSION => get_string('alfrescoversionselect', 'repository_elis_files'),
            ELIS_FILES_ALFRESCO_30 => get_string('alfrescoversion30', 'repository_elis_files'),
            ELIS_FILES_ALFRESCO_34 => get_string('alfrescoversion34', 'repository_elis_files')
        );

        $mform->addElement('select', 'alfresco_version', get_string('alfrescoversion', 'repository_elis_files'), $options);
        $mform->setDefault('alfresco_version', ELIS_FILES_SELECT_ALFRESCO_VERSION);
        $mform->addElement('static', 'default_browse_default', '', get_string('elis_files_default_alfresco_version', 'repository_elis_files'));

        // Check for installed categories table or display 'plugin not yet installed'
        if ($DB->get_manager()->table_exists('elis_files_categories')) {
        // Need to check for settings to be saved
            $popup_settings ="height=400,width=500,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
            $url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
            $jsondata = array('url'=>$url,'name'=>'config-categories','options'=>$popup_settings);
            $jsondata = json_encode($jsondata);
            $title = get_string('configurecategoryfilter', 'repository_elis_files');
            $disabled = (get_config('elis_files', 'alfresco_version')) == '' ? 'disabled=\'disabled\'': '';
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
        //$mform->addRule('elis_files_category_filter', get_string('required'), 'required', null, 'client');

        // Cache time is retrieved from the common cache time and displayed here
        $mform->addElement('text', 'cache_time', get_string('cachetime', 'repository_elis_files'), array('size' => '10'));
        $mform->addElement('static', 'cache_time_default', '', get_string('elis_files_default_cache_time', 'repository_elis_files'));
        $mform->setDefault('cache_time', $CFG->repositorycacheexpire);
        $mform->freeze('cache_time');

        // Generate the list of options for choosing a quota limit size.
        $bytes_1mb = 1048576;

        $sizelist = array(
            -1,
            0,
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
            if ($sizebytes == 0) {
                $filesize[$sizebytes] = get_string('quotanotset', 'repository_elis_files');;
            } else if ($sizebytes == -1 ) {
                $filesize[$sizebytes] = get_string('quotaunlimited', 'repository_elis_files');
            } else {
                $filesize[$sizebytes] = display_size($sizebytes);
            }
        }

        krsort($filesize, SORT_NUMERIC);

        $mform->addElement('select', 'user_quota', get_string('userquota', 'repository_elis_files'), $filesize);
        $mform->setDefault('user_quota', '0');
        $mform->addElement('static', 'user_quota_default', '', get_string('elis_files_default_user_quota', 'repository_elis_files'));
        $mform->addElement('static', 'user_quota_intro', '', get_string('configuserquota', 'repository_elis_files'));

        // Add a toggle to control whether we will delete a user's home directory in Alfresco when their account is deleted.
        $options = array(1 => get_string('yes'), 0 => get_string('no'));

        $mform->addElement('select', 'deleteuserdir', get_string('deleteuserdir', 'repository_elis_files'), $options);
        $mform->setDefault('deleteuserdir', '0');
        $mform->addElement('static', 'deleteuserdir_default', '', get_string('elis_files_default_deleteuserdir', 'repository_elis_files'));
        $mform->addElement('static', 'deleteuserdir_intro', '', get_string('configdeleteuserdir', 'repository_elis_files'));

        // Menu setting about choosing the default location where users will end up if they don't have a previous file
        // browsing location saved.
        $options = array(
            ELIS_FILES_BROWSE_SITE_FILES   => get_string('repositorysitefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_COURSE_FILES => get_string('repositorycoursefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_USER_FILES   => get_string('repositoryuserfiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_USERSET_FILES => get_string('repositoryusersetfiles', 'repository_elis_files'),
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
            if ($repo = repository_factory::factory('elis_files')) {
                if (elis_files_get_home_directory($adminusername) == false) {
                    $mform->addElement('text', 'admin_username', get_string('configadminusername', 'repository_elis_files'), array('size' => '30'));
                    $mform->addElement('static', 'admin_username_default', '', get_string('elis_files_default_admin_username', 'repository_elis_files'));
                    $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusername', 'repository_elis_files'));
                } else {
                    // An Alfresco account with the specified username has been created, check if a Moodle account exists with that
                    // username and display a warning if that is the case.
                    if (($userid = $DB->get_field('user', 'id', array('username'=> $adminusername, 'mnethostid'=> $CFG->mnet_localhost_id))) !== false) {
                        $a = new stdClass;
                        $a->username = $adminusername;
                        $a->url      = $CFG->wwwroot . '/user/editadvanced.php?id=' . $userid . '&amp;course=' . SITEID;

                        $mform->addElement('static', 'admin_username_intro', get_string('adminusername', 'repository_elis_files'), get_string('configadminusernameconflict', 'repository_elis_files', $a));
                    } else {
                        $mform->addElement('static', 'admin_username_intro', get_string('adminusername', 'repository_elis_files'), get_string('configadminusernameset', 'repository_elis_files', $adminusername));
                    }
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
        if ($repo = repository_factory::factory('elis_files')) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();
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
        if ($repo = repository_factory::factory('elis_files')) {
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
     * Check if SOAP extension enabled
     *
     * @return bool
     */
    public static function plugin_init() {
        if (!class_exists('SoapClient')) {
            print_error('soapmustbeenabled', 'repository_elis_files');
            return false;
        } else {
            return true;
        }
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    /// FILE FUNCTIONS ///////////////////////////////////////////////////////////

    function setfilelist($VARS) {
        global $USER;

        $USER->filelist = array ();
        $USER->fileop = "";

        $count = 0;

        foreach ($VARS as $key => $val) {
            if (substr($key,0,4) == "file") {
                $val = rawurldecode($val);
                preg_match('/(.+uuid=){0,1}([a-z0-9-]+)/', $val, $matches);

                if (!empty($matches[2])) {
                    $count++;
                    $USER->filelist[] = clean_param($matches[2], PARAM_PATH);
                }
            }
        }

        return $count;
    }

    function clearfilelist() {
        global $USER;

        $USER->filelist = array ();
        $USER->fileop = "";
    }

    function printfilelist($file_array, &$filelist= array()) {
        global $CFG;

        $str = '';
        foreach ($file_array as $uuid) {
            $file = $this->elis_files->get_info($uuid);

            if ($this->elis_files->is_dir($uuid)) {
                $str .= "<img src=\"{$file->icon}\" height=\"16\" width=\"16\" alt=\"\" /> " .
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

                $str .= repository_elis_files::printfilelist($subfilelist, $filelist);
            } else {
                $icon     = mimeinfo("icon", $file->filename);
                $filename = $file->filename;

                $icon = mimeinfo('icon', $file->filename);
                $str .="<img src=\"{$file->icon}\"  height=\"16\" width=\"16\" alt=\"\" /> " .
                     $file->filename . "<br />";
                //also add to a hidden form element for each file
                $filelist[]= $uuid;
            }
        }
        return $str;
    }

    function check_context($id, $uuid, $userid='') {
        global $USER;

        // I think that we should just return true/false here
        /// Get the appropriate context for the site or a course.
        if ($id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $id);
        }

    /// Make sure that we have the correct 'base' UUID for a course or user storage area as well
    /// as checking for correct permissions.
        if (!empty($userid) && !empty($id)) {
            $personalfiles = false;
            if (has_capability('repository/elis_files:viewowncontent')) {
                $personalfiles = true;
            }

            if (!$personalfiles) {
                $capabilityname = get_capability_string('repository/elis_files:viewowncontent');
                print_error('nopermissions', '', '', $capabilityname);
                exit;
            }

            if (empty($uuid)) {
                $uuid = $repo->get_user_store($userid);
            }

        } else if (empty($userid) && !empty($shared)) {
            $sharedfiles = false;

            if (has_capability('repository/elis_files:viewsharedcontent')) {
                $sharedfiles = true;
            }

            if (!$sharedfiles) {
                $capabilityname = get_capability_string('repository/elis_files:viewsharedcontent');
                print_error('nopermissions', '', '', $capabilityname);
                exit;
            }

            if (empty($uuid)) {
                $uuid = $repo->suuid;
            }

        } else if (!empty($id) && $id != SITEID) {
            require_capability('repository/elis_files:viewcoursecontent', $context);

            if (empty($uuid)) {
                $uuid = $repo->get_course_store($id);
            }
        } else {
            require_capability('repository/elis_files:viewsitecontent', $context);
        }
    }

    function check_editing_permissions($context, $id, $uuid, $userid = '') {
        global $USER;

    /// Get the context instance for where we originated viewing this browser from.
        if ($id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $id);
        }
    /// Determine whether the current user has editing persmissions.
        $canedit = false;

        if (empty($userid) && empty($shared)) {
            if (($id == SITEID && has_capability('repository/elis_files:createsitecontent', $context)) ||
                ($id != SITEID && has_capability('repository/elis_files:createcoursecontent', $context)) ||
                ($id != SITEID && has_capability('repository/elis_files:createusersetcontent', $context))) {
                $canedit = true;
            }
        } else if (empty($userid) && $shared == 'true') {
            if (has_capability('repository/elis_files:createsharedcontent', $context, $USER->id)) {
                $canedit = true;
            }
        } else {
            if ($USER->id == $userid) {
                if (has_capability('repository/elis_files:viewowncontent', $context, $USER->id)) {
                    $canedit = true;
                }
            } else {
                if (has_capability('repository/elis_files:createsitecontent', $context, $USER->id)) {
                    $canedit = true;
                } else if (has_capability('repository/elis_files:createusersetcontent', $context, $USER->id)) {
                    $canedit = true;
                }
            }
        }
        return $canedit;
    }

    /**
     * Calculate parent path for this uuid
     * string   uuid    node uuid
     */
    function get_parent_path($uuid, &$path = array()) {
        $parent_node = $this->elis_files->get_parent($uuid);
        if ($parent_node) {
            $path[] = array('name'=>$parent_node->title,'uuid'=>$parent_node->uuid, 'path'=>$parent_node->uuid);
            repository_elis_files::get_parent_path($parent_node->uuid,$path);
        } else {
            return true;
        }
    }

    /*
     * Get the applicable location parent of the given uuid
     */
    function get_location_parent($uuid, $uid) {
        global $COURSE;

        // Set permissible browsing locations
        $locations = array();
        $this->elis_files->file_browse_options($COURSE->id, '', $locations);
        $parent_path = array();
        repository_elis_files::get_parent_path($uuid, $parent_path);
        array_reverse($parent_path);

        // Check if current path IS the parent! // ELIS Site Files
        if (empty($parent_path)) {
            $parent = array('name'=> get_string('repositorysitefiles','repository_elis_files'),
                            'path'=>$uuid);
        } else {
            // first check if the uuid passed in is a location
            $parent_found = repository_elis_files::search_array($locations, 'path', $uuid);
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

        $tree = $DB->get_records('elis_files_categories');

        return $tree;
    }

}
