/**
 * Generic JavaScript methods for a results selection relaged page(s).
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
 * @param elmid - unique id of element that is to be updated
 * @param label - name of track
 * @param id - track id
 */
function add_selection(elmid, label, id) {

    var elementid = elmid + "_label";
    
    var element = window.opener.document.getElementById(elementid);
    element.innerHTML = label;

    elementid = elmid + "_selected";
    element = window.opener.document.getElementById(elementid);
    element.value = id;

    window.close();
}


function pre_submit_processing(type, actiontypeid) {

    var max = 99;
    var i = 0;
    var ele_min = '';
    var ele_max = '';
    var ele_sel = '';
    var temp = '';
    var cache = document.getElementsByName("actioncache")[0]; 
    var actiontype = document.getElementsByName("actiontype")[0];


    if ( (typeof cache == 'undefined') ) {
        return 0;
    }
    
    if ( (typeof actiontype == 'undefined') ) {
        return 0;
    }
    
    for (i=0; i < max; i++) {

        ele_min = document.getElementsByName(type + '_add_' + i + '_min')[0];
        ele_max = document.getElementsByName(type + '_add_' + i + '_max')[0];
        ele_sel = document.getElementsByName(type + '_add_' + i + '_selected')[0];
        
        if ( (typeof ele_min == 'undefined') ||
             (typeof ele_max == 'undefined') ||
             (typeof ele_sel == 'undefined') ) {
            // We've gone too far time to exit
            i = max;
            continue;
        }

        if ( ('' != ele_min.value) &&
             ('' != ele_max.value) &&
             ('' != ele_sel.value) ) {
            // Only cache complete rows.  Incomplete rows are discarded for now
            temp = temp + ele_min.value + ',' + ele_max.value + ',' + ele_sel.value + ',';
        }
 
        
        
    }
    
    // Remove the last comma
    
    var last_occurance = temp.lastIndexOf(',');
    last_occurance = parseInt(last_occurance);
    
    cache.value = temp.slice(0, last_occurance);
    
    // Update action type hidden field
    actiontype.value = actiontypeid;


}

