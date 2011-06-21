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
            echo ' ', html_writer::tag('b', $letter);
        } else {
            $url = clone($moodle_url); // TBD
            $url->params(array($pname => $letter));
            echo ' ', html_writer::link($url, $letter);
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

/** Function to return pm page url with required params
 *
 * @param    string|moodle_url  $baseurl  the pages base url
 *           defaults to: $CFG->wwwroot .'/elis/program/index.php'
 * @param    array              $extras   extra parameters for url.
 * @uses     $CFG
 * @return   moodle_url   the baseurl with required params
 */
function get_pm_url($baseurl = null, $extras = array()) {
    global $CFG;
    if (empty($baseurl)) {
        $baseurl = $CFG->wwwroot .'/elis/program/index.php';
    }
    $options = array('s', 'id', 'action', 'section', 'alpha', 'search', 'perpage', 'class'); // TBD: add more parameters as required
    $params = array();
    foreach ($options as $option) {
        $val = optional_param($option, null, PARAM_CLEAN);
        if ($val != null) {
            $params[$option] = $val;
        }
    }
    foreach ($extras as $key => $val) {
        $params[$key] = $val;
    }
    return new moodle_url($baseurl, $params);
}

/**
 * New display function callback to allow HTML elements in table
 * see: /elis/core/lib/table.class.php
 */
function htmltab_display_function($column, $item) {
    return isset($item->{$column}) ? $item->{$column} : '';
}

/**
 * display function - originally a method in table.class.php
 * see ELIS_1.9x:/curriculum/lib/table.class.php
 */
function get_date_item_display($column, $item) {
    if (empty($item->$column)) {
        return '-';
    } else {
        $timestamp = $item->$column;
        return is_numeric($timestamp)
               ? date(get_string('pm_date_format', 'elis_program'), $timestamp)
               : '';
    }
}

/**
 * display function - originally a method in table.class.php
 * see ELIS_1.9x:/curriculum/lib/table.class.php
 */
function get_yesno_item_display($column, $item) {
    return get_string($item->$column ? 'yes' : 'no');
}

