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
    private $user_session = null;
    private $store = null;
    private $elis_files;
    var $config   = null; // Config options for ELIS files


    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION, $CFG, $PAGE;
        parent::__construct($repositoryid, $context, $options);

        // May or man not need this
        //$this->config = get_config('elis_files');

        $alfresco_version = get_config('elis_files', 'alfresco_version');
        require_once dirname(__FILE__). '/ELIS_files_factory.class.php';
        if ($alfresco_version !== null) {
            /// ELIS files class
            $this->elis_files = repository_factory::factory('elis_files');
//            $this->alfresco = new Alfresco_Repository($this->options['alfresco_url']);
            $this->config = get_config('elis_files');
            $this->current_node = null;
        }
        // Probably need some of the following...

        //$this->sessname = 'elis_files_ticket_'.$this->id;
        // Change this to what we need...
        // Need to do this differently...
        /*if (class_exists('SoapClient')) {
            require_once($CFG->libdir . '/elis_files/Service/Repository.php');
            require_once($CFG->libdir . '/elis_files/Service/Session.php');
            require_once($CFG->libdir . '/elis_files/Service/SpacesStore.php');
            require_once($CFG->libdir . '/elis_files/Service/Node.php');
            // setup alfresco
            $server_url = '';
            if (!empty($this->options['elis_files_url'])) {
                $server_url = $this->options['elis_files_url'];
            } else {
                return;
            }
            */
        /*
            //$this->elis_files = new ELIS_Files_Repository($this->options['elis_files_url']);
            $this->username   = optional_param('al_username', '', PARAM_RAW);
            $this->password   = optional_param('al_password', '', PARAM_RAW);
            try{
                // deal with user logging in
                if (empty($SESSION->{$this->sessname}) && !empty($this->username) && !empty($this->password)) {
                    $this->ticket = $this->elis_files->authenticate($this->username, $this->password);
                    $SESSION->{$this->sessname} = $this->ticket;
                } else {
                    if (!empty($SESSION->{$this->sessname})) {
                        $this->ticket = $SESSION->{$this->sessname};
                    }
                }
                $this->user_session = $this->elis_files->createSession($this->ticket);
                $this->store = new SpacesStore($this->user_session);
            } catch (Exception $e) {
                $this->logout();
            }
            $this->current_node = null;*/
       /* } else {
            $this->disabled = true;
        }*/
           // return $this->verify_setup();

        // jQuery files required for file picker - just for this repository
        $PAGE->requires->js('/repository/elis_files/js/jquery-1.6.2.min.js');
        $PAGE->requires->js('/repository/elis_files/js/jquery-ui-1.8.16.custom.min.js');
        $PAGE->requires->js('/repository/elis_files/js/fileuploader.js');
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
//        echo '<br>node:';
//        print_object($node);
//        if ($node->type == "{http://www.alfresco.org/model/content/1.0}content") {
        if ($node->type == "cmis:document") {
//            $contentData = $node->cm_content;
//            if ($contentData != null) {
//                $result = $contentData->getUrl();
            $result = $node->fileurl;
//            }
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
        $ret = array();
//echo '<br>uuid: '.$uuid;
//echo '<br>path: '.$path;
//echo '<br>current node: '.$this->current_node;
//echo '<br>context?';
//print_object($this->context);
        // Might need courseid... maybe...

        // Return an array of optional columns from file list to include in the details view
        // Icon and filename are always displayed
        $title_datecreated =
        $ret['detailcols'] = array(array('field'=>'created',
                                         'title'=>get_string('datecreated','repository_elis_files')),
                                   array('field'=>'modified',
                                         'title'=>get_string('datemodified','repository_elis_files')),
                                   array('field'=>'owner',
                                         'title'=>get_string('modifiedby','repository_elis_files'))
                             );
        // Set up locations shortcuts - need a sort of url... hmmm... to be cross repository...
        // How do I create a link to a particular uuid???
        $ret['locations'] = array(array('name'=> get_string('repositorysitefiles','repository_elis_files'),
                                        'path'=> get_string('datecreated','repository_elis_files')),
                                  array('name'=> get_string('repositorycoursefiles','repository_elis_files'),
                                        'path'=> get_string('datemodified','repository_elis_files')),
                                  array('name'=> get_string('repositoryuserfiles','repository_elis_files'),
                                        'path'=> get_string('modifiedby','repository_elis_files')),
                                  array('name'=> get_string('repositoryusersetfiles','repository_elis_files'),
                                        'path'=> get_string('modifiedby','repository_elis_files')),
                                  array('name'=> get_string('repositoryserverfiles','repository_elis_files'),
                                        'path'=> get_string('modifiedby','repository_elis_files'))
                             );
        $ret['dynload'] = true;
        $ret['nologin'] = true;
        $ret['showselectedactions'] = true;
        $ret['showcurrentactions'] = true;
        $ret['list'] = array();

        $server_url = $this->elis_files->get_repourl();

        // Only use for current
        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));

        // We need to include the 'shortcuts' in the 'path' e.g.
    // process breadcrumb trail
     /*   $list['path'] = array(
            array('name'=>'Root', 'path'=>'')
        );
        $trail = '';
        if (!empty($path)) {
            $parts = explode('/', $path);
            if (count($parts) > 1) {
                foreach ($parts as $part) {
                    if (!empty($part)) {
                        $trail .= ('/'.$part);
                        $list['path'][] = array('name'=>$part, 'path'=>$trail);
                    }
                }
            } else {
                $list['path'][] = array('name'=>$path, 'path'=>$path);
            }
            $this->root_path .= ($path.'/');
        }*/


        if (empty($uuid)) {
            //Initialize the path
            $ret['path'] = '';
            //Set to default
            $userid = $USER->id;
            // $shared   A flag to indicate whether the user is currently located in the shared repository area.
            // for now, $shared = 0 => have to figure out how to pass flags such as this, gets updated by this function...
            $shared = 0;
            if ($this->context) {
                switch ($this->context->contextlevel) {
                    case CONTEXT_SYSTEM:
//        echo '<br>system context';
                        // browse system files
                        $id = optional_param('course', 1, PARAM_INT);
                        $uuid = $this->elis_files->get_root();
                        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>$uuid));
                        break;
                    case CONTEXT_USER:
//        echo '<br>user context';
                        // browse user files
    //                    $id = $USER->id;
                        $uuid =$this->elis_files->get_user_store($userid);
                        // not sure, system files?
                        $id = optional_param('course', 1, PARAM_INT);
                        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>get_string('user', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>$username, 'path'=>$uuid));
                        break;
                    case CONTEXT_COURSE:
//        echo '<br>course context';
                        // browse course files
//                        echo '<br>2 uuid: '.$uuid;
                        if ($this->context->instanceid == $COURSE->id) {
                            $id = $COURSE->id;
                            $uuid = $this->elis_files->get_course_store($id);
                            $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>get_string('course', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>$COURSE->fullname, 'path'=>$uuid));
//                 echo '<br>3 uuid: '.$uuid;
                        } else if (!$course = $DB->get_record('course', array('id'=>$this->context->instanceid))) {
                            $id = optional_param('course', 1, PARAM_INT);
                            $uuid = $this->elis_files->get_default_browsing_location($id, $userid, $shared);
                            $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));
//                            echo '<br>4 uuid: '.$uuid;
                        }
//                        echo '<br>5 uuid: '.$uuid;
                        break;
                    case CONTEXT_MODULE:
//        echo '<br>module context';
                        // browse course files for this module, either parent context or
                        if (!$cm = get_coursemodule_from_id('', $this->context->instanceid)) {
                            $id = optional_param('course', 1, PARAM_INT);
                            $uuid = $this->elis_files->get_default_browsing_location($id, $userid, $shared);
                            $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));
                        } else {
                            if ($cm->course == $COURSE->id) {
                                $id = $COURSE->id;
                                $uuid = $this->elis_files->get_course_store($id);
                                $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>get_string('course', 'repository_elis_files'), 'path'=>''),
                                             array('name'=>$COURSE->fullname, 'path'=>$uuid));
                            } else if (!$course = $DB->get_record('course', array('id'=>$cm->course))) {
                                $id = optional_param('course', 1, PARAM_INT);
                                $uuid = $this->elis_files->get_default_browsing_location($id, $userid, $shared);
                                $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));
                            }
                        }
    //                    return $this->get_file_info_context_module($context, $component, $filearea, $itemid, $filepath, $filename);
                        break;
                    default:
//        echo '<br>default context';
                        $id = optional_param('course', 1, PARAM_INT);
                        $uuid = $this->elis_files->get_default_browsing_location($id, $userid, $shared);
                        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));
                }
            }
        }
//        else echo '<br>UUID FOUND';
        $this->current_node = $this->elis_files->get_info($uuid);
//echo '<br>***uuid: '.$uuid;
        $children = elis_files_read_dir($this->current_node->uuid);
//            print_object($children);
        foreach ($children->folders as $child) {
//            echo '<br>>>>>>>>>in folder loop';
            $ret['list'][] = array('title'=>$child->title,
                    'path'=>$child->uuid, // or links['self']??? need to get a path
                    'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . '',
                    'created'=>'',
                    'modified'=>'',
                    'owner'=>'',
                    'children'=>array());
        }
        foreach ($children->files as $child) {
//            echo '<br><<<<<<<<<<<<<<<<< in item loop';
            $ret['list'][] = array('title'=>$child->title,
                    'thumbnail' => $OUTPUT->pix_url(file_extension_icon($child->title, 32))->out(false),
                    'created'=>date("M. j, Y",$child->created),
                    'modified'=>date("M. j, Y",$child->modified),
                    'owner'=>$child->owner,
                    'source'=>$child->uuid); // or links['self']???
        }

        // Let's try... just for 3.2 to save Marko problems for now...
//        $wdir = "/";
//        repository_elis_files::displaydir($server_url, $wdir);
//echo elis_files_get_home_directory('admin');
//$this->elis_files->make_root_folder_select_tree();
        //Fake file listing for now
        /*$ret['list'][] = array('title'=>'Folder',
                        'path'=>'fake/files',
                        'thumbnail'=>$OUTPUT->pix_url('f/image-32') . '',
                        'created'=>'23423225',
                        'modified'=>'54645645',
                        'owner'=>'John Smith',
                        'children'=>array(array('title'=>'Folder',
                                        'path'=>'fake/files/Folder',
                                        'thumbnail'=>$OUTPUT->pix_url('f/image-32') . '')
                    ));
        $ret['list'][] = array('title'=>'File2.png',
                        'path'=>'fake/files',
                        'created'=>'23423225',
                        'modified'=>'54645645',
                        'owner'=>'Jane Smith',
                        'thumbnail'=>$OUTPUT->pix_url('f/image-32') . '');
        $ret['list'][] = array('title'=>'File3.csv',
                        'path'=>'fake/files',
                        'created'=>'23423225',
                        'modified'=>'54645645',
                        'owner'=>'Jim Bob Smith',
                        'thumbnail'=>$OUTPUT->pix_url('f/excel') . '');
*/
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
//        $node = $this->user_session->getNode($this->store, $uuid);
        $node = $this->elis_files->get_info($uuid);
        $url = $this->get_url($node);
        $path = $this->prepare_file($file);
        $fp = fopen($path, 'w');
        $c = new curl;
        $c->download(array(array('url'=>$url, 'file'=>$fp)));
        return array('path'=>$path, 'url'=>$url);
    }

    /**
     * Return file URL
     *
     * @param string $url the url of file
     * @return string
     */
    public function get_link($uuid) {
        $node = $this->user_session->getNode($this->store, $uuid);
        $url = $this->get_url($node);
        return $url;
    }

    public function print_search() {
        $str = parent::print_search();
        $str .= '<label>Space: </label><br /><select name="space">';
        foreach ($this->user_session->stores as $v) {
            $str .= '<option ';
            if ($v->__toString() === 'workspace://SpacesStore') {
                $str .= 'selected ';
            }
            $str .= 'value="';
            $str .= $v->__toString().'">';
            $str .= $v->__toString();
            $str .= '</option>';
        }
        $str .= '</select>';
        return $str;
    }

    public function print_upload_popup() {
        global $CFG;

        $str = '<link rel="stylesheet" href="'.$CFG->wwwroot.'/repository/elis_files/css/fileuploader.css" type="text/css" />
                <link rel="stylesheet" href="'.$CFG->wwwroot.'/repository/elis_files/css/jquery-ui-1.8.16.custom.css" type="text/css" media="screen" title="no title" charset="utf-8" />
                <p><b>Uploading Tip</b></p>
                <p>You can select more than one file for uploading by holding down the control key while clicking on the files.</p>
                <div id="progressbar"></div>
                <table style="border-style:none; padding:5px;">
                    <tr>
                        <td>
                            <a id="uploadButton" href="javascript:void(0);"><input type="button" value="Select files" /></a>
                            <div id="file-uploader"></div>
                        </td>
                        <td>
                            <a id="closeButton" href="javascript:void(0);"><input type="button" value="Close" /></a>
                        </td>
                    </tr>
                </table>';

        return $str;
    }

    public function upload_multiple() {
        //throw new moodle_exception('nofile');
        $result = array();
        foreach ($_FILES['files'] as $key=>$val) {
            // do something with uploaded files
            error_log('DEBUG: '.$key);
        }
        return $result;
    }

    /**
     * Look for a file
     *
     * @param string $search_text
     * @return array
     */
    public function search($search_text) {
        $space = optional_param('space', 'workspace://SpacesStore', PARAM_RAW);
        $currentStore = $this->user_session->getStoreFromString($space);
        $nodes = $this->user_session->query($currentStore, $search_text);
        $ret = array();
        $ret['list'] = array();
        foreach($nodes as $v) {
            $ret['list'][] = array('title'=>$v->cm_name, 'source'=>$v->id);
        }
        return $ret;
    }

    /**
     * Enable mulit-instance
     *
     * @return array
     */
    /*public static function get_instance_option_names() {
        $option_names = array();
        //host
        $option_names[] = 'elis_files_server_host';
        //port
        $option_names[] = 'elis_files_server_port';
//        server_username
        $option_names[] = 'elis_files_server_username';
//server_password
        $option_names[] = 'elis_files_server_password';
//category_filter (ugh)
        $option_names[] = 'elis_files_category_filter';
//root_folder
        $option_names[] = 'elis_files_root_folder';
//cachetime
        $option_names[] = 'elis_files_cachetime';
//user_quota
        $option_names[] = 'elis_files_user_quota';
//option to delete user dir
        $option_names[] = 'elis_files_deleteuserdir';
//default_browse (drop-down selection)
        $option_names[] = 'elis_files_default_browse';
//admin_username
        $option_names[] = 'elis_files_admin_username';
        //return array('elis_files_url');
        return $option_names;
    }*/

    /*
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
                              'default_browse'     // Where to start the file browsing session
                            );

        return $option_names;
    }
    /**
     * Add Plugin settings input to Moodle form
     * @param object $mform
     */
    public function type_config_form($mform) {
        global $DB, $CFG, $SESSION, $OUTPUT;

        /*if (!class_exists('SoapClient')) {
            $mform->addElement('static', null, get_string('notice'), get_string('soapmustbeenabled', 'repository_elis_files'));
            return false;
        }*/
        parent::type_config_form($mform);
        //$mform->addElement('text', 'elis_files_url', get_string('elis_files_url', 'repository_elis_files'), array('size' => '40'));
        //$mform->addElement('static', 'alfreco_url_intro', '', get_string('elis_filesurltext', 'repository_elis_files'));
        //$mform->addRule('elis_files_url', get_string('required'), 'required', null, 'client');

        //OK, this is where we have all our settings code - ugh, this library will be, ummm, large?
//        $settings->add(new admin_setting_configtext('repository_elis_files_server_host', get_string('serverurl', 'repository_elis_files'),
//                   get_string('repository_elis_files_server_host', 'repository_elis_files'), 'http://localhost', PARAM_URL, 30));
        $mform->addElement('text', 'server_host', get_string('serverurl', 'repository_elis_files'), array('size' => '40'));
        $mform->setDefault('server_host', 'http://localhost');
        $mform->addElement('static', 'server_host_default', '', get_string('elis_files_default_server_host', 'repository_elis_files'));
        $mform->addElement('static', 'server_host_intro', '', get_string('elis_files_server_host', 'repository_elis_files'));
        $mform->addRule('server_host', get_string('required'), 'required', null, 'client');


//$settings->add(new admin_setting_configtext('repository_elis_files_server_port', get_string('serverport', 'repository_elis_files'),
       //            get_string('repository_elis_files_server_port', 'repository_elis_files'), '8080', PARAM_INT, 30));
        $mform->addElement('text', 'server_port', get_string('serverport', 'repository_elis_files'), array('size' => '30'));
        $mform->addElement('static', 'server_port_default', '', get_string('elis_files_default_server_port', 'repository_elis_files'));
        $mform->addElement('static', 'server_port_intro', '', get_string('elis_files_server_port', 'repository_elis_files'));
//        $mform->addElement('static', 'elis_files_server_host_intro', '', get_string('elis_files_server_host', 'repository_elis_files'));
        $mform->addRule('server_port', get_string('required'), 'required', null, 'client');
        $mform->setDefault('server_port', '8080');

//$settings->add(new admin_setting_configtext('repository_elis_files_server_username', get_string('serverusername', 'repository_elis_files'),
//                   get_string('repository_elis_files_server_username', 'repository_elis_files'), '', PARAM_NOTAGS, 30));
        $mform->addElement('text', 'server_username', get_string('serverusername', 'repository_elis_files'), array('size' => '30'));
//        $mform->setDefault('elis_files_server_username', '');
        $mform->addElement('static', 'server_username_default', '', get_string('elis_files_default_server_username', 'repository_elis_files'));
        $mform->addElement('static', 'server_username_intro', '', get_string('elis_files_server_username', 'repository_elis_files'));
        $mform->addRule('server_username', get_string('required'), 'required', null, 'client');

//        $settings->add(new admin_setting_configpasswordunmask('repository_elis_files_server_password', get_string('serverpassword', 'repository_elis_files'),
//                   get_string('repository_elis_files_server_password', 'repository_elis_files'), ''));
        $mform->addElement('passwordunmask', 'server_password', get_string('serverpassword', 'repository_elis_files'), array('size' => '30'));
//        $mform->setDefault('elis_files_server_password', '');
        $mform->addElement('static', 'server_password_intro', '', get_string('elis_files_server_password', 'repository_elis_files'));
        $mform->addRule('server_password', get_string('required'), 'required', null, 'client');

//        $mform->addElement('text', 'alfresco_version', get_string('alfrescoversion', 'repository_elis_files'), array('size' => '10'));

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
//        $mform->addElement('static', 'default_browse_intro', '', get_string('configdefaultfilebrowsinglocation', 'repository_elis_files'));
//                        $mform->setDefault('alfresco_version', $alfresco_version);
//        $mform->setDefault('elis_files_server_password', '');
//        $mform->addElement('static', 'server_password_intro', '', get_string('elis_files_alfresco_version', 'repository_elis_files'));
//        $mform->addRule('alfresco_version', get_string('required'), 'required', null, 'client');

//        $settings->add(new admin_setting_elis_files_category_select('repository_elis_files_category_filter', get_string('categoryfilter', 'repository_elis_files'),
//                        get_string('repository_elis_files_category_filter', 'repository_elis_files')));
        // Check for installed categories table or display 'plugin not yet installed'
        if ($DB->get_manager()->table_exists('elis_files_categories')) {
        // Need to check for settings to be saved
//        if (isset($this->config->user_quota)) {
            $popup_settings ="height=400,width=500,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
            $url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
             // Or fake out a button?
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
//        $mform->addRule('elis_files_category_filter', get_string('required'), 'required', null, 'client');

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
        //$hasadmin = $DB->record_exists('user', 'username', 'admin', 'mnethostid', $CFG->mnet_localhost_id);
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
        //    require_once($CFG->dirroot . '/file/repository/repository.class.php');

            if ($repo = repository_factory::factory('elis_files')) {
                if (elis_files_get_home_directory($adminusername) == false) {
                    // If the specified username does not exist in Alfresco yet, allow the value to be changed here.
        //            $settings->add(new admin_setting_configtext('admin_username', get_string('adminusername', 'repository_elis_files'),
        //                                get_string('configadminusername', 'repository_elis_files'), 'moodleadmin'));
                    $mform->addElement('text', 'admin_username', get_string('configadminusername', 'repository_elis_files'), array('size' => '30'));
        //            $mform->setDefault('admin_username', $adminusername);
                    $mform->addElement('static', 'admin_username_default', '', get_string('elis_files_default_admin_username', 'repository_elis_files'));
                    $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusername', 'repository_elis_files'));
                } else {
                    // An Alfresco account with the specified username has been created, check if a Moodle account exists with that
                    // username and display a warning if that is the case.
                    if (($userid = $DB->get_field('user', 'id', array('username'=> $adminusername, 'mnethostid'=> $CFG->mnet_localhost_id))) !== false) {
                        $a = new stdClass;
                        $a->username = $adminusername;
                        $a->url      = $CFG->wwwroot . '/user/editadvanced.php?id=' . $userid . '&amp;course=' . SITEID;

        //                $settings->add(new admin_setting_heading('admin_username', get_string('adminusername', 'repository_elis_files'),
        //                                    get_string('configadminusernameconflict', 'repository_alfresco', $a)));
        //                $mform->addElement('static', 'admin_username_default', '', get_string('adminusername', 'repository_elis_files'));
                        $mform->addElement('static', 'admin_username_intro', get_string('adminusername', 'repository_elis_files'), get_string('configadminusernameconflict', 'repository_elis_files', $a));
                    } else {
        //                $settings->add(new admin_setting_heading('admin_username', get_string('adminusername', 'repository_elis_files'),
        //                                    get_string('configadminusernameset', 'repository_alfresco', $adminusername)));
        //                $mform->addElement('static', 'admin_username_default', '', get_string('adminusername', 'repository_elis_files'));
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
    /**
     * Add Plugin settings input to Moodle form
     * @param object $mform
     */
  /*  public function type_config_form($mform) {
        global $CFG;
        parent::type_config_form($mform);
        $key    = get_config('dropbox', 'dropbox_key');
        $secret = get_config('dropbox', 'dropbox_secret');

        if (empty($key)) {
            $key = '';
        }
        if (empty($secret)) {
            $secret = '';
        }

        $strrequired = get_string('required');

        $mform->addElement('text', 'dropbox_key', get_string('apikey', 'repository_dropbox'), array('value'=>$key,'size' => '40'));
        $mform->addElement('text', 'dropbox_secret', get_string('secret', 'repository_dropbox'), array('value'=>$secret,'size' => '40'));

        $mform->addRule('dropbox_key', $strrequired, 'required', null, 'client');
        $mform->addRule('dropbox_secret', $strrequired, 'required', null, 'client');
        $str_getkey = get_string('instruction', 'repository_dropbox');
        $mform->addElement('static', null, '',  $str_getkey);
    }*/

/*
 * class admin_setting_elis_files_category_select extends admin_setting {
    function admin_setting_elis_files_category_select($name, $heading, $information) {
        parent::admin_setting($name, $heading, $information, '');
    }

    function get_setting() {
        return false;
    }

    function write_setting() {
        return false;
    }

    function output_html($data, $query='') {
        $default = $this->get_defaultsetting();

        $button = button_to_popup_window('/file/repository/alfresco/config-categories.php',
                                         'config-categories', get_string('configurecategoryfilter', 'repository_elis_files'),
                                         480, 640, '', '', true);

        return format_admin_setting($this, $this->visiblename, $button, $this->description, true, '', NULL, $query);
    }

}*/
    function output_root_folder_html($data, $query = '') {
        global $CFG, $PAGE;

//        $PAGE->requires->js_module('json-stringify');
// USED TO BE:
 /*require_js($CFG->wwwroot . '/file/repository/alfresco/rootfolder.js');

        $default = $this->get_defaultsetting();

        $repoisup = false;
        */
        //NOW
//        $mymodule = array('name'     => 'mod_elis_files',
//                           'fullpath' => '/repository/elis_files/rootfolder.js',
//                           'requires' => array('json'));

//        $PAGE->requires->js_init_call('mod_elis_files', array(), false, $mymodule);
        $PAGE->requires->js('/repository/elis_files/rootfolder.js');

        $repoisup = false;

        $default = '/moodle';
/// Validate the path, if we can.

        // Catch-22 fix along with adding alfresco_version set to 3.4 in the database
        //require_once dirname(__FILE__). '/ELIS_files_factory.class.php';
        if ($repo = repository_factory::factory('elis_files')) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();
        }

        $id="id_root_folder";
        $name="root_folder";
        $inputs = '<div class="form-file defaultsnext"><input type="text" size="48" id="' . $id .
                  '" name="' . $name . '" value="' . s($data) . '" /> <input type="button" ' .
                  'onclick="return chooseRootFolder(document.getElementById(\'mform1\'));" value="' .
                  get_string('chooserootfolder', 'repository_elis_files') . '" name="' . $name .
                  '"' . (!$repoisup ? ' disabled="disabled"' : '') .' /></div>';
        //$popup_settings = "height=480,width=640,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
        //$url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
         // Or fake out a button?
        //$jsondata = array('url'=>$url,'name'=>'config-categories','options'=>$popup_settings);
        //$jsondata = json_encode($jsondata);
        //$title = get_string('chooserootfolder', 'repository_elis_files');
        //$attributes = array('value='=>$title, 'alt'=>$title, 'title'=>$title,'onclick'=>'return openpopup(null,'.$jsondata.');');
        //$attributes = "value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'";
        //$button = "<input type='button' value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'>";


/*
        $inputs = '<div class="form-file defaultsnext"><input type="text" size="48" id="' . $this->get_id() .
                  '" name="' . $this->get_full_name() . '" value="' . s($data) . '" /> <input type="button" ' .
                  'onclick="return chooseRootFolder(document.getElementById(\'mform1\'));" value="' .
                  get_string('chooserootfolder', 'repository_elis_files') . '" name="' . $this->get_full_name() .
                  '"' . (!$repoisup ? ' disabled="disabled"' : '') .' />' . $valid . '</div>';
*/
        //return format_admin_setting($this, $this->visiblename, $inputs, $this->description,
        //                            true, '', $default, $query);
        return $inputs;
    }

    /*
     * Determine whether the root folder is valid
     * @return  string
     */
    function root_folder_is_valid ($data) {
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

    /*
     * Alfresco navigation - start with default as set in ELIS files setting
     */
function displaydir($uuid, $wdir, $courseid = 0) {
    global $USER;
    global $CFG;
    global $basedir;
    global $id;
    global $shared;
    global $userid;
    global $choose;
//    global $repo;
    //global $uuid;
    global $canedit;
    global $category;

    $repo = $this->elis_files;

    $search = optional_param('search', '', PARAM_CLEAN);


/// Get the context instance for where we originated viewing this browser from.
    if ($id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $id);
    }


    $strname                = get_string("name");
    $strsize                = get_string("size");
    $strmodified            = get_string("modified");
    $straction              = get_string("action");
    $strmakeafolder         = get_string("makeafolder");
    $struploadafile         = get_string("uploadafile");
    $strselectall           = get_string("selectall");
    $strselectnone          = get_string("deselectall");
    $strwithchosenfiles     = get_string("withchosenfiles");
    $strmovetoanotherfolder = get_string("movetoanotherfolder");
    $strmovefilestohere     = get_string("movefilestohere");
    $strdeletecompletely    = get_string("deletecompletely");
    $strcreateziparchive    = get_string("createziparchive");
    $strrename              = get_string("rename");
    $stredit                = get_string("edit");
    $strunzip               = get_string("unzip");
    $strlist                = get_string("list");
    $strrestore             = get_string("restore");
    $strchoose              = get_string("choose");
    $strbrowserepo          = get_string('browserepository', 'repository');
    $strdownloadlocal       = get_string('downloadlocally', 'repository');

    $dirlist  = array();
    $filelist = array();

    $parentdir = new Object();

    if (!empty($userid)) {
        $ustore = $repo->get_user_store($userid);
    }

    if (!empty($search)) {
        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($userid) && ($uuid == '' || $uuid == $ustore)) {
        if (empty($uuid)) {
            $uuid = $ustore;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($shared) && ($uuid == '' || $uuid == $repo->suuid)) {
        if (empty($uuid)) {
            $uuid = $repo->suuid;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if ($id != SITEID && ($uuid == '' || !($parent = $repo->get_parent($uuid)) ||
               ($uuid == '' || $uuid == $repo->get_course_store($id)))) {

        if (empty($uuid)) {
            $uuid = $repo->get_course_store($id);
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if ($id == SITEID && ($uuid == '' || !($parent = $repo->get_parent($uuid)))) {
        if (empty($uuid)) {
            $node = $repo->get_root();
            $uuid = $node->uuid;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else {
        $parentdir->uuid  = $parent->uuid;
        $parentdir->name  = $parent->title;
        $parentdir->title = '..';
    }

    $dirlist[] = $parentdir;
    $catselect = array();

    if (!empty($search)) {
        if (($data = data_submitted()) && confirm_sesskey()) {
            if (!empty($data->categories)) {
                $catselect = $data->categories;
            }
        } else if (!empty($category)) {
            $catselect = array($category);
        }

        $search  = stripslashes($search);
        $repodir = $repo->search($search, $catselect);
    } else {
        $repodir = $repo->read_dir($uuid);
    }

    // Store the UUID value that we are currently browsing.
    $repo->set_repository_location($uuid, $id, $userid, $shared);

    if (!empty($repodir->folders)) {
        foreach ($repodir->folders as $folder) {
            $dirlist[] = $folder;
        }
    }
    if (!empty($repodir->files)) {
        foreach ($repodir->files as $file) {
            $filelist[] = $file;
        }
    }

    echo '<form action="index.php" method="post" name="reposearch">';
    echo '<input type="hidden" name="choose" value="' . $choose . '" />';
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "<input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
    echo "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
    echo "<input type=\"hidden\" name=\"uuid\" value=\"$uuid\" /> ";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";



    echo '<center>';
    echo '<input type="text" name="search" size="40" value="' . s($search) . '" /> ';
    echo '<input type="submit" value="' . get_string('search') . '" />';

    helpbutton('search', get_string('alfrescosearch', 'repository_alfresco'), 'repository/alfresco');

    echo '</center><br />';

    if ($cats = $repo->category_get_children(0)) {
        $baseurl = $CFG->wwwroot . '/file/repository/index.php?choose='. $choose . '&amp;id=' .
                   $id . '&amp;shared=' . $shared . '&amp;userid=' . $userid . '&amp;wdir=' .
                   $wdir . '&amp;uuid=' . $uuid;

        $catfilter = repository_alfresco_get_category_filter();

        $icon  = 'folder.gif';
        $eicon = 'folder-expanded.gif';
        $menu  = new HTML_TreeMenu();

        $tnodes = array();

        if ($cats = $repo->category_get_children(0)) {
            if ($nodes = repository_alfresco_make_category_select_tree_browse($cats, $catselect, $baseurl)) {
                for ($i = 0; $i < count($nodes); $i++) {
                    $menu->addItem($nodes[$i]);
                }
            }

        }

        $treemenu = &new HTML_TreeMenu_DHTML($menu, array(
            'images' => $CFG->wwwroot . '/lib/HTML_TreeMenu-1.2.0/images'
        ));

        // Add roll up - roll down code here, similar to Show Advanced in course/modedit
        // Advanced Search/Hide Advanced Search
        // "category filter" now has help text - so, how to add that too, but use yui library
        // for hiding this
        echo '<script language="JavaScript" type="text/javascript">';
        echo "<!--\n";
        include($CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.js');
        echo "\n// -->";
        echo '</script>';

        print_simple_box_start('center', '75%');
        //looks for search.html file and gets text alfrescosearch from repository_alfresco lang file which I guess we use too...
        // now hmmm, so where does search.html go? or in our case, categoryfilter.html, I guess repository/alfresco
        print_heading(helpbutton('categoryfilter', get_string('alfrescocategoryfilter', 'repository_alfresco'), 'repository/alfresco',true,false,null,true).get_string('categoryfilter', 'repository_alfresco') . ':', 'center', '3');
        $treemenu->printMenu();
        echo '<br />';
        print_simple_box_end();
    }
    echo '</form>';

    echo '<center>';
    print_single_button('index.php', array('id' => $id, 'shared' => $shared, 'userid' => $userid, 'uuid' => $uuid),
                        get_string('showall'), 'get');

    echo '</center>';

    if ($canedit) {
        echo "<form action=\"index.php\" method=\"post\" name=\"dirform\" id=\"dirform\">";
        echo '<input type="hidden" name="choose" value="'.$choose.'" />';
    }

    echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\" class=\"files\">";
    echo "<tr>";
    echo "<th width=\"5\"></th>";
    echo "<th align=\"left\" class=\"header name\">$strname</th>";
    echo "<th align=\"right\" class=\"header size\">$strsize</th>";
    echo "<th align=\"right\" class=\"header date\">$strmodified</th>";
    echo "<th align=\"right\" class=\"header commands\">$straction</th>";
    echo "</tr>\n";


    $count = 0;

    if (!empty($dirlist)) {
        foreach ($dirlist as $dir) {
            if (empty($dir->title)) {
                continue;
            }

            echo "<tr class=\"folder\">";

            if (($dir->title == '..') || ($dir->title == $strbrowserepo)) {
                if (!empty($dir->url)) {
                    print_cell();
                    if (!empty($search)) {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.$strbrowserepo.'" /></a> <a href="' . $dir->url . '">'.$strbrowserepo.'</a>', 'name');
                    } else {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.get_string('parentfolder').'" /></a> <a href="' . $dir->url . '">'.get_string('parentfolder').'</a>', 'name');
                    }
                    print_cell();
                    print_cell();
                    print_cell();
                } else {
                    $pdir    = urlencode($dir->title);
                    $fileurl = $dir->uuid;

                    print_cell();
                    print_cell('left', '<a href="index.php?id='.$id.'&amp;shared='.$shared.'&amp;userid='.$userid.'&amp;wdir='.$pdir.'&amp;uuid='.$fileurl.'&amp;choose='.$choose.'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.get_string('parentfolder').'" /></a> <a href="index.php?id='.$id.'&amp;shared='.$shared.'&amp;userid='.$userid.'&amp;wdir='.$pdir.'&amp;uuid='.$fileurl.'&amp;choose='.$choose.'">'.get_string('parentfolder').'</a>', 'name');
                    print_cell();
                    print_cell();
                    print_cell();
                }
            } else {
                $count++;

                $filename = $dir->title;
                $pdir     = urlencode($filename);
                $fileurl  = $dir->uuid;
                $filesafe = rawurlencode($dir->title);
                $filesize = '-';
                $filedate = !empty($dir->modified) ? userdate($dir->modified, "%d %b %Y, %I:%M %p") : '-';

                if ($canedit) {
                    print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" />", 'checkbox');
                } else {
                    print_cell();
                }

                print_cell("left", "<a href=\"index.php?id=$id&amp;shared=$shared&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\"><img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" border=\"0\" alt=\"Folder\" /></a> <a href=\"index.php?id=$id&amp;shared=$shared&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\">".htmlspecialchars($dir->title)."</a>", 'name');
                print_cell("right", $filesize, 'size');
                print_cell("right", $filedate, 'date');
                print_cell('right', '-', 'commands');
            }

            echo "</tr>";
        }
    }


    if (!empty($filelist)) {
        asort($filelist);
echo '
<script language="javascript">
<!--
    function openextpopup(url,name,options,fullscreen) {
      windowobj = window.open(url,name,options);
      if (fullscreen) {
         windowobj.moveTo(0,0);
         windowobj.resizeTo(screen.availWidth,screen.availHeight);
      }
      windowobj.focus();
      return false;
    }
// -->
</script>
';

        foreach ($filelist as $file) {
            $icon = $file->icon;

            $count++;

            $filename    = $file->title;
            $fileurl     = $CFG->wwwroot . '/file/repository/alfresco/openfile.php?uuid=' . $file->uuid;
            $filesafe    = rawurlencode($file->title);
            $fileurlsafe = rawurlencode($fileurl);
            $filedate    = !empty($file->modified) ? userdate($file->modified, "%d %b %Y, %I:%M %p") : '-';
            $filesize    = '';

            $selectfile  = $fileurl;

            echo "<tr class=\"file\">";

            if ($canedit) {
                print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$file->uuid\" />", 'checkbox');
            } else {
                print_cell();
            }

            echo "<td align=\"left\" nowrap=\"nowrap\" class=\"name\">";
            if ($CFG->slasharguments) {
                $ffurl = str_replace('//', '/', "/file.php/$id/$fileurl");
            } else {
                $ffurl = str_replace('//', '/', "/file.php?file=/$id/$fileurl");
            }

            $height   = 480;
            $width    = 640;
            $name     = 'display';
            $url      = $fileurl;
            $title    = 'Popup window';
            $linkname = "<img src=\"$icon\" height=\"16\" width=\"16\" border=\"0\" alt=\"File\" />";
            $options  = 'menubar=0,location=0,scrollbars,resizable,width='. $width .',height='. $height;

            if (!empty($search) && !empty($file->parent)) {
                $pdir     = urlencode($file->parent->name);
                $fileurl  = $file->parent->uuid;
                $motext   = get_string('parentfolder', 'repository', $file->parent->name);

                echo "<a href=\"index.php?id=$id&amp;shared=$shared&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\" title=\"$motext\"><img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" border=\"0\" alt=\"Folder\" /></a> ";
            }

            echo '<a target="'. $name .'" title="'. $title .'" href="'. $url .'" '.
                 "onclick=\"return openextpopup('$url', '$name', '$options', 0);\">$linkname</a>";

            echo '&nbsp;';

            $linkname = htmlspecialchars($file->title);

            echo '<a target="'. $name .'" title="'. $title .'" href="'. $url .'" '.
                 "onclick=\"return openextpopup('$url', '$name', '$options', 0);\">$linkname</a>";

            echo "</td>";

            print_cell("right", display_size($file->filesize), 'size');
            print_cell("right", $filedate, 'date');

            if ($choose) {
                $edittext = "<strong><a onclick=\"return set_value('$selectfile')\" href=\"#\">$strchoose</a></strong>&nbsp;";
            } else {
                $edittext = '';
            }

            if (strstr($icon, 'zip.gif') !== false) {
                $edittext .= "<a href=\"index.php?id=$id&amp;shared=$shared&amp;userid=$userid&amp;uuid=$file->uuid&amp;action=unzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strunzip</a>&nbsp;";
                $edittext .= "<a href=\"index.php?id=$id&amp;shared=$shared&amp;userid=$userid&amp;uuid=$file->uuid&amp;action=listzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strlist</a> ";
            }

        /// User's cannot download files locally if they cannot access the local file storage.
            if (has_capability('moodle/course:managefiles', $context)) {
                $popupurl = '/files/index.php?id=' . $id . '&amp;shared=' . $shared . '&amp;userid=' . $userid .
                            '&amp;repouuid=' . $file->uuid . '&amp;repofile=' . $filesafe . '&amp;dd=1';

                $edittext .= link_to_popup_window($popupurl, 'coursefiles', $strdownloadlocal, 500, 750,
                                                $strdownloadlocal, 'none', true);
            }

            print_cell('right', $edittext, 'commands');

            echo "</tr>";
        }
    }
    echo "</table>";
    echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";

/// Don't display the editing form buttons (yet).

    if (empty($search)) {
        echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\">";
        echo "<tr><td>";

        if ($canedit) {
            echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
            echo "<input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
            echo "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
            echo '<input type="hidden" name="choose" value="'.$choose.'" />';
            echo "<input type=\"hidden\" name=\"uuid\" value=\"$uuid\" /> ";
            echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";

            $options = array (
               'move'   => $strmovetoanotherfolder,
               'delete' => $strdeletecompletely,
               'zip'    => $strcreateziparchive
            );

            if (!empty($count)) {
                choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:document.dirform.submit()");
            }
        }

        echo "</form>";
        echo "<td align=\"center\">";
        if (!empty($USER->fileop) and ($USER->fileop == "move") and ($USER->filesource <> $uuid)) {
            echo "<form action=\"index.php\" method=\"get\">";
            echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
            echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
            echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
            echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
            echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
            echo " <input type=\"hidden\" name=\"action\" value=\"paste\" />";
            echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
            echo " <input type=\"submit\" value=\"$strmovefilestohere\" />";
            echo "</form>";
        }
        echo "</td>";

        if ($canedit) {
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"makedir\" />";
                echo " <input type=\"submit\" value=\"$strmakeafolder\" />";
                echo "</form>";
            echo "</td>";
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">"; //dummy form - alignment only
                echo " <input type=\"button\" value=\"$strselectall\" onclick=\"checkall();\" />";
                echo " <input type=\"button\" value=\"$strselectnone\" onclick=\"uncheckall();\" />";
                echo "</form>";
            echo "</td>";
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"upload\" />";
                echo " <input type=\"submit\" value=\"$struploadafile\" />";
                echo "</form>";
            echo "</td>";
        }

        echo '</tr>';
        echo "</table>";
    } else {
        $url = 'index.php?id=' . $id . '&amp;shared=' . $shared . '&amp;userid=' . $userid . '&amp;uuid=' . $uuid;
        echo '<h3><a href="' . $url . '">' . get_string('returntofilelist', 'repository') . '</a></h3>';
    }

    if ($canedit) {
        echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";
    }
}

function files_get_cm_from_resource_name($clean_name) {
    global $CFG;

    $SQL =  'SELECT a.id  FROM '.$CFG->prefix.'course_modules a, '.$CFG->prefix.'resource b
        WHERE a.instance = b.id AND b.reference = "'.$clean_name.'"';
    $resource = get_record_sql($SQL);
    return $resource->id;
}

function get_dir_name_from_resource($clean_name) {
    global $CFG;

    $LIKE    = sql_ilike();

    $SQL  = 'SELECT * FROM '.$CFG->prefix.'resource WHERE reference '.$LIKE. "\"%$clean_name%\"";
    $resource = get_records_sql($SQL);
    return $resource;
}

}
