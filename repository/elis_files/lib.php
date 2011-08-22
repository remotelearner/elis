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

/// ELIS files class
require_once $CFG->dirroot . '/repository/elis_files/ELIS_files_factory.class.php';

//require_once $CFG->dirroot . '/repository/lib.php';

// Define constants for the default file browsing location.
//define('ELIS_FILES_BROWSE_MOODLE_FILES',          10);
defined('ELIS_FILES_BROWSE_SITE_FILES') or define('ELIS_FILES_BROWSE_SITE_FILES',   20);
defined('ELIS_FILES_BROWSE_SHARED_FILES') or define('ELIS_FILES_BROWSE_SHARED_FILES', 30);
defined('ELIS_FILES_BROWSE_COURSE_FILES') or define('ELIS_FILES_BROWSE_COURSE_FILES', 40);
defined('ELIS_FILES_BROWSE_USER_FILES') or define('ELIS_FILES_BROWSE_USER_FILES',   50);

class repository_elis_files extends repository {
    private $ticket = null;
    private $user_session = null;
    private $store = null;
    private $elis_files;
    var $config   = null; // Config options for ELIS files


    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION, $CFG;
        parent::__construct($repositoryid, $context, $options);

        // May or man not need this
        //$this->config = get_config('elis_files');

        $this->elis_files = repository_factory::factory();
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
        $ret['dynload'] = true;
        $ret['nologin'] = true;
        $ret['showselectedactions'] = true;
        $ret['showcurrentactions'] = true;
        $ret['list'] = array();
        //TODO: removed for now as we want a fake file list
        /*$server_url = $this->options['elis_files_url'];
        $pattern = '#^(.*)api#';
        if ($return = preg_match($pattern, $server_url, $matches)) {
            $ret['manage'] = $matches[1].'faces/jsp/dashboards/container.jsp';
        }

        $ret['path'] = array(array('name'=>get_string('pluginname', 'elis_files'), 'path'=>''));

        try {
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
        $str = '<p><b>Uploading Tip</b></p>
                <p>You can select more than one file for uploading by holding down the control key while clicking on the files.</p>
                <table style="border-style:none; padding:5px;">
                    <tr>
                        <td>
                            <div id="uploaderContainer">
                            <div id="uploaderOverlay" style="position:absolute; z-index:2"></div>
                            <div id="selectFilesLink" style="z-index:1"><a id="selectLink" href="javascript:void(0);"><input type="button" value="Select files" /></a></div>
                            </div>
                        </td>
                        <td>
                            <div id="uploadFilesLink"><a id="uploadLink" href="javascript:void(0);"><input type="button" value="Upload selected files" /></a></div>
                        </td>
                    </tr>
                </table>
                <div id="files">
                <table id="filenames" style="border-width:1px; border-style:solid; padding:5px;">
                <tr><td>Filename</td><td>File size</td><td>Percent uploaded</td></tr>
                </table>
                </div>';

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

    public function print_upload_progress() {
        global $OUTPUT;

        $str = '<p><b>Your files are being uploaded</b></p>'
             . '<p><input type="button" name="cancelupload" value="Cancel" /></p>';

        return $str;
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

//        $settings->add(new admin_setting_elis_files_category_select('repository_elis_files_category_filter', get_string('categoryfilter', 'repository_elis_files'),
//                        get_string('repository_elis_files_category_filter', 'repository_elis_files')));
// for now, keep it simply a text element...
// Can't use the class that was created, probably need to setup the javascript as a text/static mform field... fun...
// And do all the checks here? same for root folder
// Need to use $OUTPUT->single_button
//        $button = button_to_popup_window('/repository/elis_files/config-categories.php',
//                                         'config-categories', get_string('configurecategoryfilter', 'repository_elis_files'),
//                                         480, 640, '', '', true);
// For now, just use a link, and only if already installed...

        // Check for installed categories table or display 'plugin not yet installed'
        //if ($DB->get_manager()->table_exists('elis_files_categories')) {
        // Need to check for settings to be saved
        $user_quota = get_config('elis_files','user_quota');
        if (isset($user_quota)) {
            $popup_settings ="height=400,width=500,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
            $url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
             // Or fake out a button?
            $jsondata = array('url'=>$url,'name'=>'config-categories','options'=>$popup_settings);
            $jsondata = json_encode($jsondata);
            $title = get_string('configurecategoryfilter', 'repository_elis_files');
            $button = "<input type='button' value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'>";
            $mform->addElement('static', 'category_filter', get_string('categoryfilter', 'repository_elis_files'), $button);
            $mform->addElement('static', 'category_filter_intro', '', get_string('elis_files_category_filter', 'repository_elis_files'));
        } else {
            $mform->addElement('static', 'category_filter_intro', get_string('categoryfilter', 'repository_elis_files'), get_string('elisfilesnotinstalled', 'repository_elis_files'));
        }


//$formcontinue = new single_button(new moodle_url('index.php', array('confirm' => md5($mnet->public_key))), get_string('yes'));
        //$mform->addElement('text', 'repository_elis_files_category_filter', get_string('categoryfilter', 'repository_elis_files'), array('size' => '30'));
//        $mform->addElement('static', 'repository_elis_files_category_filter', $button, get_string('categoryfilter', 'repository_elis_files'));
//        $mform->setDefault('elis_files_server_password', '');
        //        $mform->addRule('elis_files_category_filter', get_string('required'), 'required', null, 'client');


  /*
        $settings->add(new admin_setting_elis_files_root_folder('repository_elis_files_root_folder', get_string('rootfolder', 'repository_elis_files'),
                            get_string('repository_elis_files_root_folder', 'repository_elis_files'), '/moodle'));
                            'config-categories', get_string('configurecategoryfilter', 'repository_elis_files'),
                                         480, 640, '', '', true);
*/


       // $button = $this->output_root_folder_html($mform);

 /*       $label = ' (' . $OUTPUT->action_link(
                    $button,
                    get_string('configurecategoryfilter', 'repository_elis_files'),
                    new popup_action('click', $url, 'config-categories'),
                    array('title'=>get_string('newwindow'))) . ')';
echo '<br>label: '.$label;
*/
            //when two elements we need a group
            $popup_settings = "height=480,width=640,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent";
            //$url = $CFG->wwwroot .'/repository/elis_files/config-categories.php';
             // Or fake out a button?
            //$jsondata = array('url'=>$url,'name'=>'config-categories','options'=>$popup_settings);
            //$jsondata = json_encode($jsondata);
            //$title = get_string('chooserootfolder', 'repository_elis_files');
            //$attributes = array('value='=>$title, 'alt'=>$title, 'title'=>$title,'onclick'=>'return openpopup(null,'.$jsondata.');');
            //$attributes = "value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'";
            //$button = "<input type='button' value='".$title."' alt='".$title."' title='".$title."' onclick='return openpopup(null,$jsondata);'>";
            $button = repository_elis_files::output_root_folder_html('/moodle');

            $rootfolderarray=array();
            $rootfolderarray[] = &MoodleQuickForm::createElement('text', 'root_folder', get_string('rootfolder', 'repository_elis_files'), array('size' => '30'));
            //$rootfolderarray[] = &MoodleQuickForm::createElement('button', 'root_folder_popup', get_string('chooserootfolder', 'repository_elis_files'), $attributes);
            $rootfolderarray[] = &$mform->addElement('static', 'root_folder_popup', get_string('root_folder', 'repository_elis_files'), $button);
            $mform->addGroup($rootfolderarray, 'rootfolderar', get_string('rootfolder', 'repository_elis_files'), array(' '), false);
             $mform->setDefault('root_folder', '/moodle');
             //$mform->closeHeaderBefore('rootfolderar');
        //$mform->addElement('text', 'root_folder', get_string('rootfolder', 'repository_elis_files'), array('size' => '30'));
        //$mform->addElement('static', 'root_folder_default', '', get_string('elis_files_default_root_folder', 'repository_elis_files'));
        // Add checkmark if get root folder works, or whatever...
            $mform->addElement('static', 'root_folder_default', '', get_string('elis_files_default_root_folder', 'repository_elis_files'));
            $mform->addElement('static', 'root_folder_intro', '', get_string('elis_files_root_folder', 'repository_elis_files'));
//        $mform->addRule('elis_files_category_filter', get_string('required'), 'required', null, 'client');


        // Display time period options to control browser caching
 /*       $cacheoptions = array(
            7  * DAYSECS  => get_string('numdays', '', 7),
            1  * DAYSECS  => get_string('numdays', '', 1),
            12 * HOURSECS => get_string('numhours', '', 12),
            3  * HOURSECS => get_string('numhours', '', 3),
            2  * HOURSECS => get_string('numhours', '', 2),
            1  * HOURSECS => get_string('numhours', '', 1),
            45 * MINSECS  => get_string('numminutes', '', 45),
            30 * MINSECS  => get_string('numminutes', '', 30),
            15 * MINSECS  => get_string('numminutes', '', 15),
            10 * MINSECS  => get_string('numminutes', '', 10),
            0 => get_string('no')
        );*/

 /*       $settings->add(new admin_setting_configselect('repository_elis_files_cachetime', get_string('cachetime', 'repository_elis_files'),
                            get_string('configcachetime', 'repository_elis_files'), 0, $cacheoptions));

*/
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

        //$settings->add(new admin_setting_configselect('repository_elis_files_user_quota', get_string('userquota', 'repository_elis_files'),
        //                    get_string('configuserquota', 'repository_elis_files'), 0, $filesize));

        $mform->addElement('select', 'user_quota', get_string('userquota', 'repository_elis_files'), $filesize);
        $mform->setDefault('user_quota', '0');
        $mform->addElement('static', 'user_quota_default', '', get_string('elis_files_default_user_quota', 'repository_elis_files'));
        $mform->addElement('static', 'user_quota_intro', '', get_string('configuserquota', 'repository_elis_files'));
//        $mform->addRule('elis_files_category_filter', get_string('required'), 'required', null, 'client');



        // Add a toggle to control whether we will delete a user's home directory in Alfresco when their account is deleted.
        $options = array(1 => get_string('yes'), 0 => get_string('no'));

        //$settings->add(new admin_setting_configselect('repository_elis_files_deleteuserdir', get_string('deleteuserdir', 'repository_elis_files'),
        //                    get_string('configdeleteuserdir', 'repository_elis_files'), 0, $options));

        $mform->addElement('select', 'deleteuserdir', get_string('deleteuserdir', 'repository_elis_files'), $options);
        $mform->setDefault('deleteuserdir', '0');
        $mform->addElement('static', 'deleteuserdir_default', '', get_string('elis_files_default_deleteuserdir', 'repository_elis_files'));
        $mform->addElement('static', 'deleteuserdir_intro', '', get_string('configdeleteuserdir', 'repository_elis_files'));

        // Menu setting about choosing the default location where users will end up if they don't have a previous file
        // browsing location saved.
        $options = array(
            ELIS_FILES_BROWSE_SITE_FILES   => get_string('repositorysitefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_SHARED_FILES => get_string('repositorysharedfiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_COURSE_FILES => get_string('repositorycoursefiles', 'repository_elis_files'),
            ELIS_FILES_BROWSE_USER_FILES   => get_string('repositoryuserfiles', 'repository_elis_files')
        );

        //$settings->add(new admin_setting_configselect('repository_elis_files_default_browse',
        //                    get_string('defaultfilebrowsinglocation', 'repository_elis_files'),
        //                    get_string('configdefaultfilebrowsinglocation', 'repository_elis_files'),
        //                    ELIS_FILES_BROWSE_MOODLE_FILES, $options));
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

        //echo '<br>USER->';
        //print_object($USER->repo);
        // Only proceed here if the Alfresco plug-in is actually enabled.
        //if (isset($CFG->repository_plugins_enabled) && strstr($CFG->repository_plugins_enabled, 'elis_files')) {
           /*require_once($CFG->dirroot . '/repository/elis_files/repository.class.php');
           if ($repo = repository_factory::factory('elis_files')) { */
           // Only proceed here if the Alfresco/ELIS Files plug-in is actually enabled.
            // Will this work???
           if (isset($SESSION->elis_repo)) {
               //do serialize/unserialize trick here too...
               $SESSION->elis_repo = unserialize(serialize($SESSION->elis_repo));
            /*    if (elis_files_get_home_directory($adminusername) == false) {
                    // If the specified username does not exist in Alfresco yet, allow the value to be changed here.
                    //$settings->add(new admin_setting_configtext('repository_elis_files_admin_username', get_string('adminusername', 'repository_elis_files'),
                    //                    get_string('configadminusername', 'repository_elis_files'), 'moodleadmin'));
                    $mform->addElement('text', 'admin_username', get_string('adminusername', 'repository_elis_files'));
                    $mform->setDefault('admin_username', 'moodleadmin');
                    $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusernameset', 'repository_elis_files'));

                } else {
                    // An Alfresco account with the specified username has been created, check if a Moodle account exists with that
                    // username and display a warning if that is the case.
                    if (($userid = get_field('user', 'id', 'username', $adminusername, 'mnethostid', $CFG->mnet_localhost_id)) !== false) {
                        $a = new stdClass;
                        $a->username = $adminusername;
                        $a->url      = $CFG->wwwroot . '/user/editadvanced.php?id=' . $userid . '&amp;course=' . SITEID;

                        //$settings->add(new admin_setting_heading('repository_elis_files_admin_username', get_string('adminusername', 'repository_elis_files'),
                        ///                    get_string('configadminusernameconflict', 'repository_elis_files', $a)));
                        $mform->addElement('text', 'admin_username', get_string('adminusername', 'repository_elis_files'), $a);
                        //$mform->setDefault('default_browse', ELIS_FILES_BROWSE_USER_FILES);
                        $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusernameconflict', 'repository_elis_files'));

                    } else {
                        //$settings->add(new admin_setting_heading('repository_elis_files_admin_username', get_string('adminusername', 'repository_elis_files'),
                        //                    get_string('configadminusernameset', 'repository_elis_files', $adminusername)));
                        $mform->addElement('text', 'radmin_username', get_string('adminusername', 'repository_elis_files'), $adminusername);
                        //$mform->setDefault('default_browse', ELIS_FILES_BROWSE_USER_FILES);
                        $mform->addElement('static', 'admin_username_intro', '', get_string('configadminusernameset', 'repository_elis_files'));

                    }
                }*/
            }
        //}
        return true;
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

        //$PAGE->requires->js_module('json-stringify');
        $mymodule = array('name'     => 'mod_elis_files',
                           'fullpath' => '/repository/elis_files/rootfolder.js',
                           'requires' => array('json'));

        $PAGE->requires->js_init_call('mod_elis_files', array(), false, $mymodule);
        $PAGE->requires->js('/repository/elis_files/rootfolder.js');

        $default = '/moodle';

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

        $id="id_root_folder";
        $name="root_folder";
        $inputs = '<div class="form-file defaultsnext"><input type="text" size="48" id="' . $id .
                  '" name="' . $name . '" value="' . s($data) . '" /> <input type="button" ' .
                  'onclick="return chooseRootFolder(document.getElementById(\'mform1\'));" value="' .
                  get_string('chooserootfolder', 'repository_elis_files') . '" name="' . $name .
                  '"' . (!$repoisup ? ' disabled="disabled"' : '') .' />' . $valid . '</div>';
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


}
