<?php
/**
 * Display a JS-enabled tree of folders from the Alfresco repository, allowing a
 * user to select a folder name, passing the path value back to a calling form.
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

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/../lib.php');
require_once(dirname(__FILE__).'/lib/HTML_TreeMenu-1.2.0/TreeMenu.php');
require_once(dirname(__FILE__).'/ELIS_files_factory.class.php');

require_login(SITEID, false);
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/site:config', $context);

//    if (!isadmin()) {
if (!has_capability('moodle/site:config', $context)) {
    redirect($CFG->wwwroot);
}

if (!$site = get_site()) {
    redirect($CFG->wwwroot);
}

$url      = required_param('url', PARAM_URL);
$port     = required_param('port', PARAM_INT);
$username = required_param('username', PARAM_NOTAGS);
$password = required_param('password', PARAM_NOTAGS);
$choose   = required_param('choose', PARAM_FILE);

if (!$repo = repository_factory::factory()) {
    print_error('couldnotcreaterepositoryobject', 'repository_elisfiles');
}

$icon  = 'folder.gif';
$eicon = 'folder-expanded.gif';
$menu  = new HTML_TreeMenu();

if ($nodes = $repo->make_root_folder_select_tree()) {
    for ($i = 0; $i < count($nodes); $i++) {
        $menu->addItem($nodes[$i]);
    }
}

$treemenu = new HTML_TreeMenu_DHTML($menu, array(
    'images' => $CFG->wwwroot . '/repository/elisfiles/lib/HTML_TreeMenu-1.2.0/images'
));

$strrootfolder = get_string('chooserootfolder', 'repository_elisfiles');

$PAGE->requires->js('/repository/elisfiles/lib/HTML_TreeMenu-1.2.0/TreeMenu.js', true);

$url = new moodle_url('/repository/elisfiles/rootfolder.php');
$PAGE->set_url($url);

$PAGE->set_title($strrootfolder);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$chooseparts = explode('.', $choose);

if (count($chooseparts) == 2) {
?>
    <script type="text/javascript">
    //<![CDATA[
    function set_value(txt) {
        opener.document.forms['<?php echo $chooseparts[0]."'].".$chooseparts[1] ?>.value = txt;
        window.close();
    }
    //]]>
    </script>

<?php

} elseif (count($chooseparts) == 1) {

?>
    <script type="text/javascript">
    //<![CDATA[
    function set_value(txt) {
        opener.document.getElementById('<?php echo $chooseparts[0] ?>').value = txt;
        window.close();
    }
    //]]>
    </script>

<?php

}
echo $OUTPUT->box_start();
//print_simple_box_start('center', '75%');
echo $OUTPUT->heading(get_string('chooserootfolder', 'repository_elisfiles'));

$treemenu->printMenu();
echo $OUTPUT->box_end();
echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();
