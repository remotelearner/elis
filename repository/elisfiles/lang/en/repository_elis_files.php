<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'repository_alfresco', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   repository_elis_files
 * @copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ELIS language strings - just copied straight over
$string['actions'] = 'Actions';
$string['adminusername'] = 'Admin username override';
$string['advanced'] = 'Advanced';
$string['advancedsearch'] = 'Advanced search';
$string['alfheader'] = 'Alfresco multimedia filters';
$string['alfheaderintro'] = 'To customize the media dimetions add &d=WIDTHxHEIGHT to the end of the URL.  Width and height also accept a percent.';
$string['alfmediapluginavi'] = 'Enable .avi filter';
$string['alfmediapluginflv'] = 'Enable .flv filter';
$string['alfmediapluginmov'] = 'Enable .mov filter';
$string['alfmediapluginmp3'] = 'Enable .mp3 filter';
$string['alfmediapluginmpg'] = 'Enable .mpg filter';
$string['alfmediapluginram'] = 'Enable .ram filter';
$string['alfmediapluginrm'] = 'Enable .rm filter';
$string['alfmediapluginrpm'] = 'Enable .rpm filter';
$string['alfmediapluginswf'] = 'Enable .swf filter';
$string['alfmediapluginswfnote'] = 'As a default security measure, normal users should not be allowed to embed swf flash files.';
$string['alfmediapluginwmv'] = 'Enable .wmv filter';
$string['alfmediapluginyoutube'] = 'Enable YouTube link filter';
$string['alfrescosearch'] = 'Alfresco search';
$string['alfrescourltext'] = 'Afresco API url should be: http://yoursite.com/alfresco/api';
$string['alfrescoversion'] = 'Site Alfresco version';
$string['alfrescoversionselect'] = 'Select Alfresco version';
$string['alfrescoversion30'] = 'Alfresco Version 3.2';
$string['alfrescoversion34'] = 'Alfresco Version 3.4';
$string['alfresco:view'] = 'View ELIS files';
$string['badqueryresponse'] = 'Bad query response!<br/>Your query has returned bad XML due to a Repository Server Error.';
$string['badxmlreturn'] = 'Bad XML return';
$string['cachetime'] = 'Cache files';
$string['categoryfilter'] = 'Category filter';
$string['choosealfrescofile'] = 'Choose ELIS file';
$string['choosefrommyfiles'] = 'Choose from My Files';
$string['chooselocalfile'] = 'Choose local file';
$string['chooserootfolder'] = 'Choose root folder';
$string['close'] = 'Close';
$string['configadminusername'] = 'Alfresco has a default username of <b>admin</b>.  Moodle will also use a default ' .
                                 'username of admin for the first account you create.  We will need to re-map that ' .
                                 'value to something else when creating the Alfresco account for that user.<br /><br />' .
                                 'The value you specify here <b>must</b> be unique to your Moodle site.  You cannot have ' .
                                 'a Moodle user account with the username value you enter and you should ensure that one ' .
                                 'is not created after this value has been set.';
$string['configadminusernameconflict'] = 'The username override that you have set for your Moodle <b>admin</b> account: ' .
                                         '<i>{$a}->username</i> has already been used to create an Alfresco account.<br /><br />' .
                                         '<b>WARNING: A Moodle account with that username has been created which will directly ' .
                                         'conflict with the Alfresco account.  You must either delete or change the username ' .
                                         'of the <a href="{$a}->url">Moodle user</a>.</b>';
$string['configadminusernameset'] = 'The username override that you have set for your Moodle <b>admin</b> account: ' .
                                    '<i>{$a}</i> has already been used to create an Alfresco account.';
$string['configcachetime'] = 'Specify that files from the repository should be cached for this long in the user\'s browser';
$string['configdefaultfilebrowsinglocation'] = 'If you choose a value here it will be the default location that a user ' .
                                               'finds themselves automatically sent to when launching a file browser ' .
                                               'without having a previous location to be sent to.<br /><br /><b>NOTE:</b> ' .
                                               'If a user does not have permissions to view the default location, they ' .
                                               'will see the next available location on the list that they have ' .
                                               'permissions to view.';
$string['configdeleteuserdir'] = 'When deleting a Moodle user account, if that user has an Alfresco account, it will be ' .
                                 'deleted at the same time.  By default their Alfresco home directory will not be deleted. ' .
                                 'Change this option to enable or disable that behaviour.<br /><br /><b>NOTE:</b> ' .
                                 'deleting a user\'s home directory in Alfresco will break any links in Moodle to content ' .
                                 'that was located in that directory.';
$string['configplugin'] = 'ELIS Files configuration';
$string['configpasswordlessusersync'] = 'Your site contains users with an authentication plug-in that does not use passwords. '.
                                        'In order for those users to be able to properly use this plug-in from Moodle, they '.
                                        'need to have Alfresco accounts created with a generated password. You can do this '.
                                        'automatically by running the <em>np_user_sync.php</em> script within the '.
                                        '<pre>repository/elis_files directory</pre>';
$string['configuserquota'] = 'Set the default value for how much storage space all Moodle users on Alfresco can use.  ' .
                             '<b>Select Unlimited for unlimited storage space.</b>';
$string['configurecategoryfilter'] = 'Configure category filter';
$string['connecttimeout'] = 'Connection timeout';
$string['connecttimeoutdefault'] = 'Default: {$a}';
$string['connecttimeoutdesc'] = 'The time in seconds for to wait for cURL web service connection to be established.';
$string['couldnotaccessserviceat'] = 'Could not access Alfresco service at: {$a}';
$string['couldnotdeletefile'] = '<br />Error: Could not delete: {$a}';
$string['couldnoteditdir'] = 'Could not edit directory.';
$string['couldnotgetalfrescouserdirectory'] = 'Could not get Alfresco user directory for user: {$a}';
$string['couldnotgetfiledataforuuid'] = 'Could not get file data for UUID: {$a}';
$string['couldnotgetnodeproperties'] = 'Could not get node properties for UUID: {$a}';
$string['couldnotinitializerepository'] = 'Could not initialize ELIS Files repository';
$string['couldnotmigrateuser'] = 'Could not migrate user account for: {$a}';
$string['couldnotmovenode'] = 'Could not move node to new location';
$string['couldnotmoveroot'] = 'Could not move root folder contents to new location';
$string['couldnotopenlocalfileforwriting'] = 'Could not open local file for writing: {$a}';
$string['couldnotrenamenode'] = 'Error: Could not rename node to \'{$a}\'';
$string['couldnotupdatedir'] = 'Error: Could not update directory.';
$string['couldnotupdatefile'] = 'Error: Could not update file.';
$string['couldnotwritelocalfile'] = 'Could not write local file';
$string['course'] = 'Course';
$string['createfolder'] = 'Create folder';
$string['curlmustbeenabled'] = 'The cURL extension must be enabled for web services file transfers to Alfresco';
$string['datecreated'] = 'Date Created';
$string['datemodified'] = 'Date Modified';
$string['defaultfilebrowsinglocation'] = 'Default file browsing location';
$string['delete'] = 'Delete';
$string['strdelete'] = 'Delete';
$string['deletecheckfiles'] = 'Are you absolutely sure you want to delete these files?';
$string['deletecheckwarning'] = 'You are about to delete these files';
$string['deleteuserdir'] = 'Auto-delete Alfresco user directories';
$string['details'] = 'Details';
$string['detailview'] = 'View details';
$string['description'] = 'Connect to the Alfresco document management system repository.';
$string['done'] = 'done';
$string['elisfilesnotinstalled'] = '<b>ELIS Files must be configured and saved first</b>';
$string['elis_files:view'] = 'View ELIS Files Repository';
$string['elis_files:createsitecontent'] = 'Create site-level content';
$string['elis_files:viewsitecontent'] = 'View site-level content';
$string['elis_files:createsharedcontent'] = 'Create shared content';
$string['elis_files:viewsharedcontent'] = 'View shared content';
$string['elis_files:createcoursecontent'] = 'Create course-level content';
$string['elis_files:viewcoursecontent'] = 'View course-level content';
$string['elis_files:createowncontent'] = 'Create personal content';
$string['elis_files:viewowncontent'] = 'View personal content';
$string['elis_files:createusersetcontent'] = 'Create shared userset content';
$string['elis_files:viewusersetcontent'] = 'View shared userset content';
$string['elis_files_category_filter'] = 'Choose the categories available when filtering search results';
$string['elis_files_default_admin_username'] = 'Default: moodleadmin';
$string['elis_files_default_alfresco_version'] = 'Default: None';
$string['elis_files_default_cache_time'] = 'Default: Cache time (in seconds) as set in <b>Site Administration->Plugins->Repositories->Common repository settings</b> ';
$string['elis_files_default_default_browse'] = 'Default: ELIS User Files';
$string['elis_files_default_deleteuserdir'] = 'Default: No';
$string['elis_files_default_root_folder'] = 'Default: /moodle';
$string['elis_files_default_server_host'] = 'Default: http://localhost';
$string['elis_files_default_server_port'] = 'Default: 8080';
$string['elis_files_default_server_username'] = 'Default: Empty';
$string['elis_files_default_user_quota'] = 'Default: Not Set';
$string['elis_files_folder_not_found'] = 'Folder not found';
$string['elis_files_root_folder'] = 'The root folder on the repository where this Moodle site will store its files in Alfresco';
$string['elis_files_server_homedir'] = 'This is home directory (relative to the repository root space) for the user configured to access Alfresco without leading slash (/).<br /><br />Examples:<br /><b>my_home_dir<br />Moodle Users/User A</b>';
$string['elis_files_server_host'] = 'The URL to your Alfresco server (should be in the following format http://www.myserver.org).';
$string['elis_files_server_password'] = 'The password to login to the Alfresco server with.';
$string['elis_files_server_port'] = 'The port that your Alfresco server is running on (i.e. 80, 8080).';
$string['elis_files_server_settings'] = 'Alfresco server settings';
$string['elis_files_server_username'] = 'The username to login to the Alfresco server with.';
$string['elis_files_url'] = 'Alfresco URL';
$string['errorcouldnotcreatedirectory'] = 'Error: could not create directory {$a}';
$string['errordirectorynameexists'] = 'Error: directory {$a} already exists';
$string['errorftpinvalidport'] = 'Error: Could not establish a FTP connection to {$a}';
$string['errorupload'] = 'Could not save uploaded file. The upload was cancelled, or server error encountered.';
$string['erroruploadduplicatefilename'] = 'Error: A file with that name already exists in this directory: <b>{$a}</b>';
$string['erroruploadquota'] = 'Error: You do not have enough storage space left to upload this file.';
$string['erroruploadquotasize'] = 'Error: You do not have enough storage space left to upload this file.  You have used {$a->current} of {$a->max}';
$string['erroropeningtempfile'] = 'Error opening temp file';
$string['errorreadingfile'] = 'Error reading file from repository: {$a}';
$string['errorreceivedfromendpoint'] = 'Alfresco: Error received from endpoint -- ';
$string['errorfilenotmoved'] = 'Error: file not moved';
$string['errortitlenotmoved'] = 'Error: {$a} not moved';
$string['errorunzip'] = 'Could not unzip file.';
$string['erroruploadduplicatefilename'] = 'Error: A file with that name already exists in this directory: {$a}';
$string['erroruploadingfile'] = 'Error uploading file to Alfresco';
$string['errorzip'] = 'Could not create a zip file.';
$string['errorzipemptydirectory'] = 'Could not create a zip file. The directory you have selected is empty.';
$string['failedtoinvokeservice'] = 'Failed to invoke service {$a->serviceurl} Code: {$a->code}';
$string['filealreadyexistsinthatlocation'] = '{$a} file already exists in that location';
$string['filetransfermethod'] = 'File transfer method';
$string['filetransfermethoddefault'] = 'Default: FTP';
$string['filetransfermethoddesc'] = 'Select the method used to send files to Alfresco. <b>If FTP is used</b>: the Alfresco server '.
                                    'must be have it\'s internal FTP server configured and the correct port set below.';
$string['filesuploaded'] = 'File(s) uploaded';
$string['folderalreadyexists'] = 'This folder already exists';
$string['ftp'] = 'FTP';
$string['ftpmustbeenabled'] = 'The FTP extension must be enabled for FTP file transfers to Alfresco';
$string['ftpport'] = 'FTP port';
$string['ftpportdefault'] = 'Default: 21';
$string['ftpportdesc'] = 'The port that the Alfresco FTP server is running on';
$string['incompletequeryresponse'] = 'Incomplete query response!<br/>Your query failed to complete successfully due to a Repository Server Error.';
$string['incorectformatforchooseparameter'] = 'Incorrect format for choose parameter';
$string['installingwebscripts'] = 'Installing new web scripts, please wait...';
$string['invalidcourseid'] = 'Invalid course ID: {$a}';
$string['invalidpath'] = 'Invalid repository path';
$string['invalidschema'] = 'invalid schema {$a}';
$string['invalidsite'] = 'Invalid site';
$string['jump'] = 'Jump to...';
$string['lockingdownpermissionson'] = 'Locking down permissions on Alfresco folder <b>{$a}->name</b> (<i>{$a}->uuid</i>)';
$string['myfiles'] = 'My Files';
$string['myfilesquota'] = 'My Files - {$a} free';
$string['modifiedby'] = 'Modified By';
$string['moodle'] = 'Moodle';
$string['move'] = 'Move';
$string['movefiles'] = 'Move file(s) to:';
$string['nocategoriesfound'] = 'No categories found';
$string['notitle'] = 'notitle';
$string['np_migratecomplete'] = 'Migrated {$a->ucount} / {$a->utotal} users to Alfresco';
$string['np_migratefailure'] = '  xxx  Failed migrating user {$a->id}:{$a->username} ".{$a->fullname}';
$string['np_migratesuccess'] = '  ---  Migrated user ID: {$a->id} username: {$a->username} {$a->fullname}';
$string['np_startingmigration'] = '  ===  Starting user migration {$a} users to migrate  ===';
$string['onlyincategories'] = 'Only show results from the following selected categories:';
$string['password'] = 'Password';
$string['passwordlessusersync'] = 'Synchronize users without passwords';
$string['pleaseenterfoldername'] = 'Please enter a folder name!';
$string['pleaseselectfilesfirst'] = 'Please select files first!';
$string['pleaseselectfolder'] = 'Please select a destination folder';
$string['pluginname'] = 'ELIS Files';
$string['pluginname_help'] = 'A plug-in for Alfresco CMS';
$string['processingcategories'] = 'Processing categories...';
$string['quotanotset'] = 'Not Set';
$string['quotaunlimited'] = 'Unlimited';
$string['repository'] = 'ELIS Files';
$string['repositoryusersetfiles'] = 'ELIS UserSet Files';
$string['repositorycoursefiles'] = 'ELIS Course Files';
$string['repositoryname'] = 'ELIS Files';
$string['repositoryprivatefiles'] = 'My Private Files';
$string['repositoryserverfiles'] = 'ELIS Server Files';
$string['repositorysharedfiles'] = 'ELIS Server Files';
$string['repositorysitefiles'] = 'ELIS Site Files';
$string['repositoryuserfiles'] = 'ELIS User Files';
$string['resetcategories'] = 'Reset categories';
$string['resetcategoriesdesc'] = 'This will force an update of all the categories from the repository (note: this will probably take about 30-60 seconds to complete)';
$string['responsetimeout'] = 'Response timeout';
$string['responsetimeoutdefault'] = 'Default: {$a}';
$string['responsetimeoutdesc'] = 'The time in seconds for to wait for a cURL web service response to be returned.  0 signifies to wait indefinitely.';
$string['rootfolder'] = 'Root folder';
$string['searchforfilesinrepository'] = 'Search for files in the repository';
$string['selectfiles'] = 'Select files';
$string['serverpassword'] = 'Password';
$string['serverport'] = 'Port';
$string['serverurl'] = 'URL';
$string['serverusername'] = 'Username';
$string['startingalfrescocron'] = 'Starting ELIS files cron...';
$string['startingpartialusermigration'] = 'Starting partial user migration...';
$string['unabletoauthenticatewithendpoint'] = 'Alfresco: Unable to authenticate with endpoint';
$string['user'] = 'User';
$string['userquota'] = 'User storage quota';
$string['uploadedbymoodle'] = 'Uploaded by Moodle';
$string['uploadfiles'] = 'Upload files';
$string['uploadingtiptitle'] = 'Uploading Tip';
$string['uploadingtiptext'] = 'You can select more than one file for uploading by holding down the control key while clicking on the files.';
$string['uploadpopuptitle'] = 'Upload Files';
$string['username'] = 'User name';
$string['usernameorpasswordempty'] = 'Username and / or password is empty';
$string['warningdeleteresource'] = 'Warning: {$a} is currently used as a resource. You might need to update this resource.';
$string['webservices'] = 'Web services';
$string['withselectedfiles'] = 'With selected files...';
$string['youdonothaveaccesstothisfunctionality'] = 'You do not have access to this functionality';
