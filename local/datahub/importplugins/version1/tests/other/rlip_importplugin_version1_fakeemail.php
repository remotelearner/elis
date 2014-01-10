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
 * @package    dhimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../version1.class.php');

/**
 * A test object that replaces the sendemail function in rlip_importplugin_version1 for easy testing.
 */
class rlip_importplugin_version1_fakeemail extends rlip_importplugin_version1 {
    /**
     * Send the email.
     *
     * @param object $user The user the email is to.
     * @param object $from The user the email is from.
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @return array An array containing all inputs.
     */
    public function sendemail($user, $from, $subject, $body) {
        return array(
            'user' => $user,
            'from' => $from,
            'subject' => $subject,
            'body' => $body
        );
    }
}