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

if (!($rec = $DB->get_record(RLIP_LOG_TABLE, array('id' => $id)))) {
    print_error('filenotfound', 'error', $CFG->wwwroot.'/');
}

if (!file_exists($rec->logpath)) {
    print_error('filenotfound', 'error', $CFG->wwwroot.'/');
}

$filein = new rlip_fileplugin_log($rec->logpath);
$filein->open(RLIP_FILE_READ);

$fileout = new rlip_fileplugin_log($rec->logpath);
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

?>
