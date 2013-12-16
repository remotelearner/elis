<?php
/**
 * Handle the jquery file uploads to Alfresco
 * Works in conjunction with Valums fileuploader.js
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once('lib/lib.php');

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
     function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()){
            return false;
        }

        // save temporary file in moodledata
        // TO-DO: eventually this should be changed to send the file directory to alfresco and skip the intermediate temporary local save step
        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        // send temporary file to alfresco
        $result = elis_files_upload_file('',$path,$_GET['uuid']);

        // clean up temporary file
        if (file_exists($path)) {
            unlink($path);
        }

        return $result !== false;
    }

    function getName() {
        return $_GET['qqfile'];
    }

    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }

        // send temporary file to alfresco
        $result = elis_files_upload_file('',$path,$_GET['uuid']);

        // clean up temporary file
        if (file_exists($path)) {
            unlink($path);
        }

        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 20971520;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 20971520){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        $this->checkServerSettings();

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false;
        }
    }

    private function checkServerSettings(){
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
    }

    function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     * Validates that a filename is unique within the specified folder in Alfresco
     *
     * @param string $filename The filename we are checking, without the path included
     * @return mixed true if unique, otherwise a string error message
     */
    function validate_unique_filename($filename) {
        require_once('ELIS_files_factory.class.php');

        $uuid = required_param('uuid', PARAM_TEXT);

        if ($repo = repository_factory::factory()) {
            // look through the files in the current directory
            if ($dir = $repo->read_dir($uuid)) {
                if (!empty($dir->files)) {
                    foreach ($dir->files as $file) {
                        if ($file->title == $filename) {
                            // found an existing file with the same name
                            return get_string('erroruploadduplicatefilename', 'repository_elisfiles', $filename);
                        }
                    }
                }
            }
        } else {
            // this is unlikely but possible
            return get_string('errorupload', 'repository_elisfiles');
        }

        // file not already found, so ok to upload
        return true;
    }

    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        global $USER;

        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }

        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }

        $size = $this->file->getSize();

        if ($size == 0) {
            return array('error' => 'File is empty');
        }

        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        // logic for handling file extensions
        if (isset($pathinfo['extension'])) {
            $ext = $pathinfo['extension'];
            $filename .= '.'.$ext;
        } else {
            $ext = NULL;
        }


        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }

        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            /// this just handles temporary files - consider forcing uniqueness here in the future?
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }

        // make sure we're not going over the user's quota
        if (!elis_files_quota_check($size, $USER)) {
            if ($quotadata = elis_files_quota_info($USER->username)) {
                //specific error message, if possible
                $a = new stdClass;
                $a->current = round($quotadata->current / 1048576 * 10, 1) / 10 . get_string('sizemb');
                $a->max     = round($quotadata->quota / 1048576 * 10, 1) / 10 . get_string('sizemb');

                return array('error' => get_string('erroruploadquotasize', 'repository_elisfiles', $a));
            } else {
                //non-specific error message
                return array('error' => get_string('erroruploadquota', 'repository_elisfiles'));
            }
        }

        // make sure we're not uploading a duplicate filename
        $test = $this->validate_unique_filename($filename);
        if ($test !== true) {
            return array('error' => $test);
        }

        if ($this->file->save($uploadDirectory . $filename)) {
            return array('success'=>true);
        } else {
            $config = get_config('elisfiles');

            // ELIS-4982 -- If FTP is enabled, check that the port is set correctly
            if ($config->file_transfer_method == ELIS_FILES_XFER_FTP) {
                // Attempt to make a connection to the FTP server
                $uri = parse_url($config->server_host);
                if (ftp_connect($uri['host'], $config->ftp_port, 5) === false) {
                    return array('error' => get_string('errorftpinvalidport', 'repository_elisfiles', $uri['host'].':'.$config->ftp_port));
                }
            }

            // Unknown error occurred
            return array('error' => get_string('errorupload', 'repository_elisfiles'));
        }

    }
}

// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array(); // leave empty to allow all for now

// set max file size in bytes to match system setting
$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));

$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

$result = $uploader->handleUpload($CFG->dataroot.'/temp/');

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

