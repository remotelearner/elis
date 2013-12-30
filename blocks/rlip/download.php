<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    blocks
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once('../../config.php');
require_login();
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
$file = get_plugin_directory('rlipfile', 'log').'/log.class.php';
require_once($file);

$id = required_param('id', PARAM_INT);

if (!($log = $DB->get_record(RLIP_LOG_TABLE, array('id' => $id)))) {
    print_error('filenotfound', 'error', $CFG->wwwroot.'/');
}

$logfilename = '';

// Check if the log file still exists on the filesystem
if (!empty($log->logpath) && file_exists($log->logpath)) {
    $logfilename = $log->logpath;
}

// Check if a zip archive exists for the date the job was started on
if ($logfilename == '') {
    $archivelog = rlip_get_archive_log_filename($log);
    //error_log("download.php: checking for log archive: {$archivelog}");
    if (!empty($archivelog) && file_exists($archivelog)) {
        // Create a directory for temporary unzipping the log archive
        do {
            $path = $CFG->dataroot.'/temp/rlip_download'.mt_rand(0, 9999999);
        } while (file_exists($path));

        check_dir_exists($path);

        // Unzip the log archive file
        $fp = get_file_packer('application/zip');
        if (!$fp->extract_to_pathname($archivelog, $path)) {
            @remove_dir($path);
        } else {
            // Look for to see if the specific file we want exists in the unarchive zip file
            $logfilename = $path .'/'. basename($log->logpath);
            if (!file_exists($logfilename)) {
                $logfilename = '';
            }
        }
    }
}

// If we haven't found a valid archive file by now, make sure that we display an appropriate error
if ($logfilename == '') {
    // ELIS-5224: delete the temp log files & directory
    if (!empty($path)) {
        foreach(glob("{$path}/*") as $logfile) {
            @unlink($logfile);
        }
        @rmdir($path);
    }
    print_error('filenotfound', 'error', $CFG->wwwroot.'/blocks/rlip/viewlogs.php');
}

$filein = new rlip_fileplugin_log($logfilename);
$filein->open(RLIP_FILE_READ);

// ELIS-5199 only use the filename part of the log file path when creating the filename for download
$fileout = new rlip_fileplugin_log(basename($logfilename));
$fileout->sendtobrowser = true;
$fileout->open(RLIP_FILE_WRITE);

while ($entry = $filein->read()) {
    // remove new lines, they will be added back in write()
    $entry = preg_replace("/[\n\r]/", "", $entry);
    // write expects an array
    $fileout->write(array($entry));
}

$filein->close();
$fileout->close();

// ELIS-5224: delete the temp log files & directory
if (!empty($path)) {
    foreach(glob("{$path}/*") as $logfile) {
        @unlink($logfile);
    }
    @rmdir($path);
}

