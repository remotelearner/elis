/**
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

function chooseRootFolder(obj) {
/// This function will open a popup window to test the server parameters for
/// successful connection.
    if ((obj.server_host.value.length == 0) ||
    		(obj.server_host.value == '')) {

        return false;
    }

    var queryString = "";
    var urlString = "";

    queryString += "url=" + escape(obj.id_server_host.value);
    queryString += "&port=" + obj.id_server_port.value;
    queryString += "&username=" + escape(obj.id_server_username.value);
    queryString += "&password=" + escape(obj.id_server_password.value);
    queryString += "&choose=id_root_folder";
    urlString = '/repository/elisfiles/rootfolder.php?'  + queryString;

    var myData = { url: urlString, name: "rootfolder", scrollbars: "yes",resizable: "no", width: "640",height: "480" };

//    	var jsonStr = YAHOO.lang.JSON.stringify(myData);
//    	var jsonStr = JSON.stringify(myData);

    return openpopup(null, myData);
}
