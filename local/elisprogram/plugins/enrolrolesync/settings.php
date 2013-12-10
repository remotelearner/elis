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
 * @package    elisprogram_enrolrolesync
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once(elispm::lib('lib.php'));
require_once(elispm::file('plugins/enrolrolesync/lib.php'));

if ($ADMIN->fulltree) {

    $options = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($options, array(CONTEXT_ELIS_CLASS));

    //setting header
    $settings->add(new admin_setting_heading('elisprogram_enrolrolesync/settings',
                                             get_string('enrolment_role_sync_settings', 'elisprogram_enrolrolesync'),
                                             ''));

    //student role setting
    $setting = new admin_setting_configselect('elisprogram_enrolrolesync/student_role',
                                              get_string('sync_student_role_setting', 'elisprogram_enrolrolesync'),
                                              get_string('sync_student_role_help', 'elisprogram_enrolrolesync'),
                                              0, $options);
    $setting->set_updatedcallback('enrolment_role_sync_updatedcallback');
    $settings->add($setting);

    //instructor role setting
    $setting = new admin_setting_configselect('elisprogram_enrolrolesync/instructor_role',
                                              get_string('sync_instructor_role_setting', 'elisprogram_enrolrolesync'),
                                              get_string('sync_instructor_role_help', 'elisprogram_enrolrolesync'),
                                              0, $options);
    $setting->set_updatedcallback('enrolment_role_sync_updatedcallback');
    $settings->add($setting);
}
