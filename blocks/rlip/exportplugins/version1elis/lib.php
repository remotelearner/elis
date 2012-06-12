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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/**
 * Helper class that is used for configuring the Version 1 ELIS format export
 */
class rlipexport_version1elis_config {
    /**
     * Specifies a recordset that provides a listing of configured export
     * fields, including the mapping id, field name, export header text and
     * field order
     *
     * @return object The appropriate recordset
     */
    static function get_configured_fields() {
        //TODO: implement
        return array();
    }

    /**
     * Specifies a recordset that provides a listing of PM user profile
     * fields that have not yet been included in the recordset, including their
     * record ids and names
     *
     * @return object The appropriate recordset
     */
    static function get_available_fields() {
        global $DB;

        //TODO: implement
        return array();
    }

    /**
     * Handles actions submitted by the form for configuring which profile
     * fields are part of the export, redirecting if necessary
     *
     * @param string $baseurl The base url to redirect to after an action takes
     *                        place
     */
    static function handle_field_action($baseurl) {
        //TODO: implement
    }
}

/**
 * Performs page setup work needed on the page for configuring which profile
 * fields are part of the export
 *
 * @param string $baseurl The page's base url
 */
function rlipexport_version1elis_page_setup($baseurl) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $displaystring = get_string('configuretitle', 'rlipexport_version1elis');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');
}