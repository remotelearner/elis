<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elispm::lib('deprecatedlib.php'); // cm_get_crlmuserid()
require_once elispm::lib('page.class.php');

/**
 * This page is just a dummy that allows generic linking to the dashboard
 * from the Curriculum Admin menu
 *
 */
class dashboardpage extends pm_page {

    /**
     * Determines whether or not the current user can navigate to the
     * Curriculum Admin dashboard
     *
     * @return  boolean  Whether or not access is allowed
     *
     */
    function can_do_default() {
        //allow any logged-in user since the dashboard varies based on the user
        return isloggedin();
    }

    /**
     * Create a url to the current page (just points to the main PM index)
     *
     * @return moodle_url
     */
    function get_moodle_url($extra = array()) {
        $page = $this->get_new_page($extra);
        $url = $page->url;

        return $url;
    }

    function display_default() {
        global $CFG, $USER, $OUTPUT;

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (true/*(has_capability('block/curr_admin:managecurricula', $context)*/) {
            echo $OUTPUT->heading(get_string('admin_dashboard', 'elis_program'));
            echo $OUTPUT->box(html_writer::tag('p', get_string('elis_doc_class_link', 'elis_program')));
            echo $OUTPUT->box(html_writer::tag('p', get_string('health_check_link', 'elis_program', $CFG)));
            echo html_writer::tag('p', get_string('elispmversion', 'elis_program', elispm::$release));
            echo html_writer::tag('p', get_string('elisversion', 'elis_core', elis::$release));
        }

        if ($cmuid = pm_get_crlmuserid($USER->id)) {
            $user = new user($cmuid);
            echo $user->get_dashboard();
        }
    }
}
