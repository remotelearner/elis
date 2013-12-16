<?php
/**
 * Configure the categories used when searching within the repository.
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
require_once(dirname(__FILE__).'/lib/HTML_TreeMenu-1.2.0/TreeMenu.php');
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
//require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/tree_menu_lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');

global $DB, $OUTPUT;

if (!$site = get_site()) {
    redirect($CFG->wwwroot . '/');
}

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/site:config', $context);

$strconfigcatfilter = get_string('configurecategoryfilter', 'repository_elisfiles');

// Initialize the repo object.
$repo = repository_factory::factory();

/// Process any form data submission
if (($data = data_submitted($CFG->wwwroot.'/repository/elisfiles/config-categories.php')) && confirm_sesskey()) {

    if (isset($data->reset)) {
        $DB->delete_records('repository_elisfiles_cats');

        // Perform the back-end category refresh
        $categories = elis_files_get_categories();
        $uuids = array();
        $repo->process_categories($uuids, $categories);
    } else if (isset($data->categories)) {
        set_config('catfilter', serialize($data->categories), 'elisfiles');
    } else {
        set_config('catfilter', '', 'elisfiles');
    }
}

/// Get (or create) the array of category IDs that are already selected in the filter.
$catfilter = elis_files_get_category_filter();

// Set up header etc...
$url = new moodle_url('/repository/elisfiles/config-categories.php');
$PAGE->set_url($url);
$PAGE->requires->js('/repository/elisfiles/lib/HTML_TreeMenu-1.2.0/TreeMenu.js', true);

$PAGE->set_title($strconfigcatfilter);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->box_start();

echo '<form method="post" action="' . $CFG->wwwroot . '/repository/elisfiles/config-categories.php">';
echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';

echo '<center>';
echo '<input type="submit" name="reset" value="' . get_string('resetcategories', 'repository_elisfiles') .
     '" /><br />' . get_string('resetcategoriesdesc', 'repository_elisfiles') . '<br /><br />';

if ($DB->get_manager()->table_exists('repository_elisfiles_cats') && $categories = $repo->category_get_children(0)) {
    echo '<input type="button" value="' . get_string('selectall') . '" onclick="checkall();" />';
    echo '&nbsp;<input type="button" value="' . get_string('deselectall') . '" onclick="checknone();" /><br />';
    echo '<input type="submit" value="' . get_string('savechanges') . '" />';
    echo '</center><br />';

    if ($nodes = elis_files_make_category_select_tree_choose($categories, $catfilter)) {
        $menu  = new HTML_TreeMenu();

        for ($i = 0; $i < count($nodes); $i++) {
            $menu->addItem($nodes[$i]);
        }

        $treemenu = new HTML_TreeMenu_DHTML($menu, array(
            'images' => $CFG->wwwroot . '/repository/elisfiles/lib/HTML_TreeMenu-1.2.0/images'
        ));

        $treemenu->printMenu();
    }

    echo '<center><br />';
    echo '<input type="button" value="' . get_string('selectall') . '" onclick="checkall();" />';
    echo '&nbsp;<input type="button" value="' . get_string('deselectall') . '" onclick="checknone();" /><br />';
    echo '<input type="submit" value="' . get_string('savechanges') . '" /> ';
} else {
    echo get_string('nocategoriesfound', 'repository_elisfiles');
}
echo '</center>';

echo '</form>';

echo $OUTPUT->box_end();
echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();
