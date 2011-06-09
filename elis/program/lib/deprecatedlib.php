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

/**
 * Return an error message formatted the way the application wants it.
 *
 * @param string $message The text to display.
 * @return string The formatted message.
 */
function cm_error($message) {
    /// Using Moodle...
    return notify($message, 'notifyproblem', 'center', true);
}

/**
 * Returns a delete form formatted for the application.
 *
 * @param string $url The page to call.
 * @param string $message The message to ask.
 * @param array $optionsyes The form attributes for the "yes" portion.
 * @param array $optionsno The form attributes for the "no" portion.
 * @uses $OUTPUT
 * @return string The HTML for the form.
 *
 */
function cm_delete_form($url='', $message='', $optionsyes=NULL, $optionsno=NULL) {
    global $OUTPUT;
    $methodyes = 'post';
    $methodno  = 'get';
    $linkyes   = $url;
    $linkno    = $url;

    $buttoncontinue = new single_button(new moodle_url($linkyes, $optionsyes), get_string('yes'), $methodyes);
    $buttoncancel   = new single_button(new moodle_url($linkno, $optionsno), get_string('no'), $methodno);

    return $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
}

