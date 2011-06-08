<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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

/**
 * Prints the 'All A B C ...' alphabetical filter bar.
 *
 * @param object $moodle_url the moodle url object for the alpha/letter links
 * @param string $pname      the parameter name to be appended to the moodle_url
 *                           i.e. 'pname=alpha'
 */
function pmalphabox($moodle_url, $pname = 'alpha') {
    $alpha        = optional_param($pname, null, PARAM_ALPHA);

    $alphabet = explode(',', get_string('alphabet', 'langconfig'));
    $strall = get_string('all');

    echo html_writer::start_tag('div', array('style' => 'text-align:center'));

    if ($alpha) {
        $url = clone($moodle_url); // TBD
        $url->remove_params($pname);
        echo html_writer::link($url, $strall);
    } else {
        echo html_writer::tag('b', $strall);
    }

    foreach ($alphabet as $letter) {
        if ($letter == $alpha) {
            echo html_writer::tag('b', $letter);
        } else {
            $url = clone($moodle_url); // TBD
            $url->params(array($pname => $letter));
            echo html_writer::link($url, $letter);
        }
    }

    echo html_writer::end_tag('div');
}


/**
 * Prints the text substring search interface.
 *
 * @param object $page       the page object for the search form action & links
 * @param string $searchname the parameter name for the search tag
 *                           i.e. 'searchname=search'
 * @todo convert echo HTML statements to use M2 html_writer, etc.
 */
function pmsearchbox($page, $searchname = 'search') {
    $search = trim(optional_param($searchname, '', PARAM_TEXT));

    // TODO: with a little more work, we could keep the previously selected sort here
    $params = $_GET;
    unset($params['page']);      // We want to go back to the first page
    unset($params[$searchname]); // And clear the search ???

    $target = $page->get_new_page($params);

    echo "<table class=\"searchbox\" style=\"margin-left:auto;margin-right:auto\" cellpadding=\"10\"><tr><td>";
    echo "<form action=\"" . $target->url . "\" method=\"post\">";
    echo "<fieldset class=\"invisiblefieldset\">";
    echo "<input type=\"text\" name=\"{$searchname}\" value=\"" . s($search, true) . "\" size=\"20\" />";
    echo '<input type="submit" value="'.get_string('search').'" />';

    if ($search) {
        echo "<input type=\"button\" onclick=\"document.location='". $target->url ."';\" " .
             "value=\"Show all items\" />";
    }

    echo "</fieldset></form>";
    echo "</td></tr></table>";
}

/**
 * Function to get a parameter from _POST or _GET. If not present, will return
 * the value defined in the $default parameter, or false if not defined.
 *
 * @param string $param     The parameter to look for.
 * @param string $default   Default value to return if not found.
 * @return string | boolean The value of the parameter, or $default.
 */
function cm_get_param($param, $default = false) {
    return optional_param($param, $default, PARAM_CLEAN);
}

