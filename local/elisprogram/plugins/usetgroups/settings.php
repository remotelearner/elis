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
 * @package    elisprogram_usetgroups
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once(elispm::file('plugins/usetgroups/lib.php'));

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('userset_grp_settings', get_string('userset_grp_settings', 'elisprogram_usetgroups'), ''));

    // Allow course-level group population from usersets
    $userset_groups = new admin_setting_configcheckbox('elisprogram_usetgroups/userset_groups',
                           get_string('grp_pop_userset_setting', 'elisprogram_usetgroups'),
                           get_string('grp_pop_userset_help', 'elisprogram_usetgroups'), 0);
    $userset_groups->set_updatedcallback('userset_groups_pm_userset_groups_enabled_handler');
    $settings->add($userset_groups);

    $sc_userset_groups = new admin_setting_configcheckbox('elisprogram_usetgroups/site_course_userset_groups',
                           get_string('fp_pop_userset_setting', 'elisprogram_usetgroups'),
                           get_string('fp_pop_userset_help', 'elisprogram_usetgroups'), 0);
    $sc_userset_groups->set_updatedcallback('userset_groups_pm_site_course_userset_groups_enabled_handler');
    $settings->add($sc_userset_groups);

    // Allow front page grouping creation from userset-based groups
    $userset_groupings = new admin_setting_configcheckbox('elisprogram_usetgroups/userset_groupings',
                           get_string('fp_grp_userset_setting', 'elisprogram_usetgroups'),
                           get_string('fp_grp_userset_help', 'elisprogram_usetgroups'), 0);
    $userset_groupings->set_updatedcallback('userset_groups_pm_userset_groupings_enabled');
    $settings->add($userset_groupings);

}