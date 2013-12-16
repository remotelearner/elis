<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    block_repository
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot. '/repository/elisfiles/lib/lib.php');

class block_repository extends block_base {

    function block_repository() {
        $this->title = get_string('blockname', 'block_repository');
    }

    function get_content() {
        global $CFG, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        $content = '';
        $footer  = '';

        $nologin_auths = elis_files_nopasswd_auths();
        if (!empty($USER->auth) && in_array($USER->auth, $nologin_auths)) {
            return '';
        }

        // Only proceed here if the Alfresco plug-in is actually enabled.
        if (!isloggedin() || !file_exists($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php')) {
            return $this->content;
        }

        require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');

        if (!$repo = repository_factory::factory()) {
            return $this->content;
        }

        // Get the ELIS Files plugin configuration values
        $pcfg = get_config('elisfiles');

        $username = $USER->username == 'admin' ? $pcfg->admin_username : $USER->username;
        $username = $repo->alfresco_username_fix($username);
        $repo->set_alfresco_username($username);

        if ($repo->elis_files_userdir($username) !== false) {
            // So that we don't conflict with the default Alfresco admin account.
//            $username = $USER->username == 'admin' ? $pcfg->admin_username : $USER->username;

            $hastenant = false;

            // If the Moodle user's user name has an '@' symbol, then replace it with '_AT_' for Alfresco compatibility
            if (($tenantname = strpos($USER->username, '@')) > 0) {
                $username = $repo->get_alfresco_username_fix();
            }

            // We must include the tenant portion of the username here.
            if (($tenantname = strpos(elis::$config->elisfiles->server_username, '@')) > 0) {
                $username .= substr(elis::$config->elisfiles->server_username, $tenantname);
                $hastenant = true;
            }

            // Display a link to access the Alfresco repository directly.
            $content .= get_string('webappaccess', 'block_repository', $repo->get_webapp_url()) . '<br /><br />';

            // Display a link to the configured embedded WebDAV client (if defined).
            if (!empty($CFG->block_course_repository_webdav_client)) {
                $content .= get_string('embeddedwebdavlink', 'block_repository',
                                       $CFG->block_course_repository_webdav_client) . '<br /><br />';
            }

            if ($hastenant || ($username != $USER->username)) {
                $content .= get_string('usernametenantinfo', 'block_repository', $username);
            } else {
                $content .= get_string('usernameinfo', 'block_repository', $username);
            }

            // Display a link to defined help files
            if (!empty($CFG->block_course_repository_help_link)) {
                $footer = get_string('helpfileslink', 'block_repository', $CFG->block_course_repository_help_link);
            }
        }

        // If there is no content and the current user can actually modify the site settings, display some text
        // in the block explaining what is happening.
        if (empty($content) && has_capability('moodle/site:config', context_system::instance())) {
            $url     = $CFG->wwwroot.'/admin/repository.php?action=edit&amp;repos=elisfiles&amp;sesskey='.sesskey();
            $content = get_string('alfresconotconfigured', 'block_repository', $url);
        }

        $this->content->text   = $content;
        $this->content->footer = $footer;

        return $this->content;
    }


    function applicable_formats() {
        return array(
            'site'   => true,
            'course' => true
        );
    }
}
