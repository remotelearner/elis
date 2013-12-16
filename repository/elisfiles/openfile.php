<?php
/**
 * Open an Alfresco file given the file's UUID value.
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
require_once('ELIS_files_factory.class.php');

global $USER;
$uuid = required_param('uuid', PARAM_CLEAN);
$filename = optional_param('filename', '', PARAM_PATH);

if (!$repo = repository_factory::factory()) {
    print_error('couldnotcreaterepositoryobject', 'repository');
}

if (!$repo->permission_check($uuid, $USER->id)) {
    echo '<br>permission check failed for uuid: '.$uuid.' and userid: '.$USER->id.'**';
    print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
}

$repo->read_file($uuid, '', false, true, !empty($filename));

exit;
