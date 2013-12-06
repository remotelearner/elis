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
 * @package    block_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot . '/blocks/rlip/lib.php');

//todo: convert setting to be part of block_rlip rather than rlip

//add all IP-related entities to the standard Moodle admin tree
rlip_admintree_setup($ADMIN);

//start of "scheduling" section
$settings->add(new admin_setting_heading('block_rlip/scheduling',
                                         get_string('rlip_global_scheduling', 'block_rlip'),
                                         ''));

//setting for disabling in Moodle cron
if (empty($CFG->forcedatahubcron)) {
    $settings->add(new admin_setting_configcheckbox('block_rlip/disableincron', get_string('disableincron', 'block_rlip'),
        get_string('configdisableincron', 'block_rlip'), ''));
} else {
    $settings->add(new admin_setting_heading('block_rlip/disableincron_override', '', get_string('cronforcedinconfig', 'block_rlip')));
}
