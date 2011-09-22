/**
 * Generic JavaScript methods for a association/selection page.  Allows
 * multiple items to be selected using checkboxes, and use AJAX to do
 * paging/searching while maintaining the selection.  The selection will be
 * submitted as a form fieled called '_selection', which will be a JSON-encoded
 * array.
 *
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
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/**
 * This functions opens a new popup window
 */
function show_panel( url ) {
    var x = window.open(url, 'newWindow', 'height=500,width=500,resizable,scrollbars');
}

/**
 * This function updates fields on the results engine results form/table
 * The first parameter is only part of the element id (the suffix).
 * 
 * @param elmid - part of the element id type_randomid 
 * @param label - the name to display in the label
 * @param id - the id of the selected track/class/profile
 */
function user_selection(elmid, label, id) {
//    var element = document.getElementById(elmid);

    var element_label = "label_new_" + elmid;
    var element_hidden = "id_new_" + elmid
    
    var element = window.opener.document.getElementById(element_label);
    element.innerHTML = label;

    element = window.opener.document.getElementById(element_hidden);
    element.value = id;

}

function add_range_selection( type ) {
    // do nothing for now
}
