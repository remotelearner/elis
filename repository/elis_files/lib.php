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
//defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_SERVER_FILES') or define('ELIS_FILES_BROWSE_SERVER_FILES', 30);
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
        if ($alfresco_version !== null) {
            /// ELIS files class
            require_once dirname(__FILE__). '/ELIS_files_factory.class.php';
            $this->elis_files = repository_factory::factory('elis_files');
//            $this->alfresco = new Alfresco_Repository($this->options['alfresco_url']);
            $this->config = get_config('elis_files');
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
        if ($node->type == "{http://www.alfresco.org/model/content/1.0}content") {
            $contentData = $node->cm_content;
            if ($contentData != null) {
                $result = $contentData->getUrl();
            }
        } else {
            $result = "index.php?".
                "&uuid=".$node->id.
                "&name=".$node->cm_name.
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
        global $CFG, $SESSION, $OUTPUT;
        $ret = array();
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
        //TODO: removed for now as we want a fake file list
//        $server_url = $this->options['elis_files_url'];
//        $server_url = $this->config->server_host.':'.$this->config->server_port;

        $server_url = $this->elis_files->get_repourl();
//        echo '<br>server url: '.$server_url;
     /*   $pattern = '#^(.*)api#';
        if ($return = preg_match($pattern, $server_url, $matches)) {
            $ret['manage'] = $matches[1].'faces/jsp/dashboards/container.jsp';
        }*/

        // Only use for current
        $ret['path'] = array(array('name'=>get_string('pluginname', 'repository_elis_files'), 'path'=>''));
        // We need to include the 'shortcuts' in the 'path' e.g.
    // process breacrumb trail
       /* $list['path'] = array(
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
//echo '<br>ok, what do we have? this?';
//print_object($this);
//echo '<br>elis_files?';
//print_object($this->elis_files);
        /*try {
            if (empty($uuid)) {
                $this->current_node = $this->store->companyHome;
            } else {
                $this->current_node = $this->user_session->getNode($this->store, $uuid);
            }

            $folder_filter = "{http://www.alfresco.org/model/content/1.0}folder";
            $file_filter = "{http://www.alfresco.org/model/content/1.0}content";

            // top level sites folder
            $sites_filter = "{http://www.alfresco.org/model/content/1.0}sites";
            // individual site
            $site_filter = "{http://www.alfresco.org/model/content/1.0}site";

            foreach ($this->current_node->children as $child)
            {
                if ($child->child->type == $folder_filter or
                    $child->child->type == $sites_filter or
                    $child->child->type == $site_filter)
                {
                    $ret['list'][] = array('title'=>$child->child->cm_name,
                        'path'=>$child->child->id,
                        'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . '',
                        'children'=>array());
                } elseif ($child->child->type == $file_filter) {
                    $ret['list'][] = array('title'=>$child->child->cm_name,
                        'thumbnail' => $OUTPUT->pix_url(file_extension_icon($child->child->cm_name, 32))->out(false),
                        'source'=>$child->child->id);
                }
            }
        } catch (Exception $e) {
            unset($SESSION->{$this->sessname});
            $ret = $this->print_login();
        }*/

        // Let's try... just for 3.2 to save Marko problems for now...
//        $wdir = "/";
//        repository_elis_files::displaydir($wdir);
        //Fake file listing for now
        $ret['list'][] = array('title'=>'Folder',
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
        $node = $this->user_session->getNode($this->store, $uuid);
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
            ELIS_FILES_BROWSE_SERVER_FILES => get_string('repositoryserverfiles', 'repository_elis_files')
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


    function print_cell($alignment="center", $text="&nbsp;") {
        echo "<td align=\"$alignment\" nowrap=\"nowrap\">\n";
        echo "$text";
        echo "</td>\n";
    }

    /// This function get's the image size
    public function get_image_size($uuid) {
        global $repo;

        /// Check if file exists
        if (!$finfo = $repo->get_info($uuid)) {
            return false;
        } else {
            /// Get the mime type so it really an image.
            if(mimeinfo("icon", $finfo->filename) != "image.gif") {
                return false;
            } else {
                $array_size = getimagesize(str_replace($finfo->title, rawurlencode($finfo->title), $finfo->fileurl) .
                                           '?alf_ticket=' . alfresco_utils_get_ticket());
                return $array_size;
            }
        }
        //unset($filepath, $array_size);
    }

    /*
     * Alfresco navigation - start with default as set in ELIS files setting
     */
    public function displaydir ($wdir) {
    //  $wdir == / or /a or /a/b/c/d  etc

        global $basedir;
        global $usecheckboxes;
        global $id;
        global $USER, $CFG, $OUTPUT;

        $fullpath = $basedir.$wdir;

        $directory = opendir($fullpath);             // Find all files
        while (false !== ($file = readdir($directory))) {
            if ($file == "." || $file == "..") {
                continue;
            }

            if (is_dir($fullpath."/".$file)) {
                $dirlist[] = $file;
            } else {
                $filelist[] = $file;
            }
        }
        closedir($directory);

        $strfile = get_string("file");
        $strname = get_string("name");
        $strsize = get_string("size");
        $strmodified = get_string("modified");
        $straction = get_string("action");
        $strmakeafolder = get_string("makeafolder");
        $struploadafile = get_string("uploadafile");
        $strwithchosenfiles = get_string("withchosenfiles");
        $strmovetoanotherfolder = get_string("movetoanotherfolder");
        $strmovefilestohere = get_string("movefilestohere");
        $strdeletecompletely = get_string("deletecompletely");
        $strcreateziparchive = get_string("createziparchive");
        $strrename = get_string("rename");
        $stredit   = get_string("edit");
        $strunzip  = get_string("unzip");
        $strlist   = get_string("list");
        $strchoose   = get_string("choose");


        echo "<form action=\"coursefiles.php\" method=\"post\" name=\"dirform\">\n";
        echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"100%\">\n";

        if ($wdir == "/") {
            $wdir = "";
        } else {
            $bdir = str_replace("/".basename($wdir),"",$wdir);
            if($bdir == "/") {
                $bdir = "";
            }
            print "<tr>\n<td colspan=\"5\">";
            print "<a href=\"coursefiles.php?id=$id&amp;wdir=$bdir&amp;usecheckboxes=$usecheckboxes\" onclick=\"return reset_value();\">";
            print "<img src=\"$CFG->wwwroot/lib/editor/htmlarea/images/folderup.gif\" height=\"14\" width=\"24\" border=\"0\" alt=\"".get_string('parentfolder')."\" />";
            print "</a></td>\n</tr>\n";
        }

        $count = 0;

        if (!empty($dirlist)) {
            asort($dirlist);
            foreach ($dirlist as $dir) {

                $count++;

                $filename = $fullpath."/".$dir;
                $fileurl  = $wdir."/".$dir;
                $filedate = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");

                echo "<tr>";

                if ($usecheckboxes) {
                    if ($fileurl === '/moddata') {
                        repository_elis_files::print_cell();
                    } else {
                        repository_elis_files::print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" onclick=\"return set_rename('$dir');\" />");
                    }
                }
                repository_elis_files::print_cell("left", "<a href=\"coursefiles.php?id=$id&amp;wdir=$fileurl\" onclick=\"return reset_value();\"><img src=\"" . $OUTPUT->pix_url('/f/folder.gif') ."\" class=\"icon\" alt=\"".get_string('folder')."\" /></a> <a href=\"coursefiles.php?id=$id&amp;wdir=$fileurl&amp;usecheckboxes=$usecheckboxes\" onclick=\"return reset_value();\">".htmlspecialchars($dir)."</a>");
                repository_elis_files::print_cell("right", "&nbsp;");
                repository_elis_files::print_cell("right", $filedate);

                echo "</tr>";
            }
        }


        if (!empty($filelist)) {
            asort($filelist);
            foreach ($filelist as $file) {

                $icon = mimeinfo("icon", $file);
                $imgtype = mimeinfo("type",$file);

                $count++;
                $filename    = $fullpath."/".$file;
                $fileurl     = "$wdir/$file";
                $filedate    = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");

                $dimensions = repository_elis_files::get_image_size($filename);
                if($dimensions) {
                    $imgwidth = $dimensions[0];
                    $imgheight = $dimensions[1];
                } else {
                    $imgwidth = "Unknown";
                    $imgheight = "Unknown";
                }
                unset($dimensions);
                echo "<tr>\n";

                if ($usecheckboxes) {
                    repository_elis_files::print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" onclick=\";return set_rename('$file');\" />");
                }
                echo "<td align=\"left\" nowrap=\"nowrap\">";
                $ffurl = get_file_url($id.$fileurl);
                link_to_popup_window ($ffurl, "display",
                                      "<img src=\"" . $OUTPUT->pix_url('/f/'.$icon) . "\" class=\"icon\" alt=\"$strfile\" />",
                                      480, 640);
                $file_size = filesize($filename);

                echo "<a onclick=\"return set_value(info = {url: '".$ffurl."',";
                echo " isize: '".$file_size."', itype: '".$imgtype."', iwidth: '".$imgwidth."',";
                echo " iheight: '".$imgheight."', imodified: '".$filedate."' })\" href=\"#\">$file</a>";
                echo "</td>\n";

                if ($icon == "zip.gif") {
                    $edittext = "<a href=\"coursefiles.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=unzip&amp;sesskey=$USER->sesskey\">$strunzip</a>&nbsp;";
                    $edittext .= "<a href=\"coursefiles.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=listzip&amp;sesskey=$USER->sesskey\">$strlist</a> ";
                } else {
                    $edittext = "&nbsp;";
                }
                print_cell("right", "$edittext ");
                print_cell("right", $filedate);

                echo "</tr>\n";
            }
        }
        echo "</table>\n";

        if (empty($wdir)) {
            $wdir = "/";
        }

        echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
        echo "<tr>\n<td>";
        echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
        echo "<input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
        echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
        $options = array (
                       "move" => "$strmovetoanotherfolder",
                       "delete" => "$strdeletecompletely",
                       "zip" => "$strcreateziparchive"
                   );
        if (!empty($count)) {
            choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:getElementById('dirform').submit()");
        }
        if (!empty($USER->fileop) and ($USER->fileop == "move") and ($USER->filesource <> $wdir)) {
            echo "<form action=\"coursefiles.php\" method=\"get\">\n";
            echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
            echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
            echo " <input type=\"hidden\" name=\"action\" value=\"paste\" />\n";
            echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
            echo " <input type=\"submit\" value=\"$strmovefilestohere\" />\n";
            echo "</form>";
        }
        echo "</td></tr>\n";
        echo "</table>\n";
        echo "</form>\n";
    }

}
