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
function track_add_selection(elmid, label, id) {
    
    var elementid = "track_add_" + elmid + "_label";
    
    var element = window.opener.document.getElementById(elementid);
    element.innerHTML = label;

    elementid = "track_add_" + elmid + "_selected";
    element = window.opener.document.getElementById(elementid);
    element.value = id;

    window.close();
}

/**
 * This function caches the user's inserted data to a hidden HTML
 * element in a comma separated format
 * 
 * @param string type - the type of data to cache track, class or profile
 */
function add_range_selection( id, type ) {
return 0;
    alert('hey');

    var min_elements_name = type + "_minimum";
    var min_elements = document.getElementsByName(min_elements_name);
    var min_cache_el = document.getElementsByName("range_cache_minimum");
    var min_cache = '';
    var i = 0;
    
    for (i=0; i < i.length; i++) {
        min_cache = min_cache + ',' + min_elements[i].value;
    }
    
    alert(min_elements.length);
    alert(min_cache);
    // Remove the extra comma at the beginning
    min_cache.substring(1);

    min_cache_el[0].value = min_cache;
}

