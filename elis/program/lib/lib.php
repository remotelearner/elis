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
 * @param string $label      optional label - defaults to none
 */
function pmalphabox($moodle_url, $pname = 'alpha', $label = null) {
    $alpha    = optional_param($pname, null, PARAM_ALPHA);
    $alphabet = explode(',', get_string('alphabet', 'langconfig'));
    $strall   = get_string('all');

    echo html_writer::start_tag('div', array('style' => 'text-align:center'));
    if (!empty($label)) {
        echo $label, ' '; // TBD: html_writer::???
    }
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
 * @param object|string $page_or_url the page object for the search form action
 *                                   or the url string.
 * @param string $searchname         the parameter name for the search tag
 *                                   i.e. 'searchname=search'
 * @param string $method             the form submit method: get(default)| post
 *                                   TBD: 'post' method flakey, doesn't always work!
 * @param string $showall            label for the 'Show All' link - optional
 *                                   defaults to get_string('showallitems' ...
 * @uses $_GET
 * @uses $CFG
 * @todo convert echo HTML statements to use M2 html_writer, etc.
 * @todo support moodle_url as 1st parameter and not just string url.
 */
function pmsearchbox($page_or_url = null, $searchname = 'search', $method = 'get', $showall = null) {
    global $CFG;
    $search = trim(optional_param($searchname, '', PARAM_TEXT));

    $params = $_GET;
    unset($params['page']);      // TBD: Do we want to go back to the first page
    unset($params[$searchname]); // And clear the search ???

    if (empty($page_or_url)) {
        $target = new stdClass;
        $target->url = $CFG->wwwroot .'/elis/program/index.php'; // TBD: 'index.php'
    } else if (is_object($page_or_url)) {
        $target = $page_or_url->get_new_page($params);
    } else {
        $target = new stdClass;
        $target->url = $page_or_url;
    }
    $query_pos = strpos($target->url, '?');
    $action_url = ($query_pos !== false) ? substr($target->url, 0, $query_pos)
                                         : $target->url;

    echo '<table class="searchbox" style="margin-left:auto;margin-right:auto" cellpadding="10"><tr><td>'; // TBD: style ???
    echo "<form action=\"{$action_url}\" method=\"{$method}\">";
    echo '<fieldset class="invisiblefieldset">';
    foreach($params as $key => $val) {
        echo "<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\" />";
        if (!is_object($page_or_url)) { // TBD
            $target->url .= (strpos($target->url, '?') === false) ? '?' : '&';
            $target->url .= "{$key}={$val}"; // required for onclick, below
        }
    }
    echo "<input type=\"text\" name=\"{$searchname}\" value=\"" . s($search, true) . '" size="20" />';
    echo '<input type="submit" value="'.get_string('search').'" />';

    if ($search) {
        if (empty($showall)) {
            $showall = get_string('showallitems', 'elis_program');
        }
        echo "<input type=\"button\" onclick=\"document.location='{$target->url}';\" value=\"{$showall}\" />";
    }

    echo '</fieldset></form>';
    echo '</td></tr></table>';
}

/**
 * Prints the current 'alpha' and 'search' settings for no table entries
 *
 * @param string $alpha         the current alpha/letter match
 * @param string $namesearch    the current string search
 * @param string $matchlabel    optional get_string identifier for label prefix of match settings
 *                              default get_string('name', 'elis_program')
 * @param string $nomatchlabel  optional get_string identifier for label prefix of no matches
 *                              default get_string('no_users_matching', 'elis_program')
 */
function pmshowmatches($alpha, $namesearch, $matchlabel = null, $nomatchlabel = null) {
    if (empty($matchlabel)) {
        $matchlabel = 'name';
    }
    if (empty($nomatchlabel)) {
        $nomatchlabel = 'no_users_matching';
    }
    $match = array();
    if ($namesearch !== '') {
        $match[] = s($namesearch);
    }
    if ($alpha) {
        $match[] = get_string($matchlabel, 'elis_program') .": {$alpha}___";
    }
    if (!empty($match)) {
        $matchstring = implode(", ", $match);
        $sparam = new stdClass;
        $sparam->match = $matchstring;
        echo get_string($nomatchlabel, 'elis_program', $sparam), '<br/>'; // TBD
    }
}

/** Function to return pm page url with required params
 *
 * @param   string|moodle_url  $baseurl  the pages base url
 *                             defaults to: '/elis/program/index.php'
 * @param   array              $extras   extra parameters for url.
 * @return  moodle_url         the baseurl with required params
 */
function get_pm_url($baseurl = null, $extras = array()) {
    if (empty($baseurl)) {
        $baseurl = '/elis/program/index.php';
    }
    $options = array('s', 'id', 'action', 'section', 'alpha', 'search', 'perpage', 'class', 'association_id', 'mode', '_assign'); // TBD: add more parameters as required: page, [sort, dir] ???
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

/**
 *
 * Call Moodle's set_config with 3rd parm 'elis_program'
 *
 * @param string $name the key to set
 * @param string $value the value to set (without magic quotes)
 * @return n/a
 */
function pm_set_config($name, $value) {
    set_config($name,$value, 'elis_program');
}
