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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die;
require_once dirname(__FILE__) .'/lib/setup.php';
require_once elispm::lib('data/curriculumstudent.class.php'); // defines
require_once elispm::lib('certificate.php'); // TBD: cm_certificate_get__()

global $CFG, $DB, $PAGE, $USER;
global $SESSION;

if ($ADMIN->fulltree) {
    $USER->currentitypath = 'admn/local_elisprogram_settings'; // TBD: to expand menu

    if (!is_siteadmin() &&
        optional_param('section', '', PARAM_SAFEDIR) == 'local_elisprogram_settings') {
        // TBD: Ugly hack to get navbar/breadcrumbs to appear for non-admins ...
        // since Moodle core methods: navbar->get/has_items() are broken!!!
        $PAGE->navbar->add(get_string('administrationsite'), null);
        $PAGE->navbar->add(get_string('elis_config', 'local_elisprogram'), null);
        $PAGE->navbar->add(get_string('elis_settings', 'local_elisprogram'), $PAGE->url);
    }

    //flag that can be overrideen to signal that re-calculating student' curriculum
    //expiry times has already been done for the current settings change
    $SESSION->curriculum_expiration_toggled = false;

    $ADMIN->add('root', new admin_category('local_elisprogram', get_string('elis_config', 'local_elisprogram'), true));

    $settings = new admin_settingpage('local_elisprogram_settings', get_string('elis_settings', 'local_elisprogram'), 'local/elisprogram:config');

    // ***Track Settings
    $settings->add(new admin_setting_heading('track_settings', get_string('track_settings', 'local_elisprogram'), '' /* get_string('track_settings_info', 'local_elisprogram') */));
    // Turn off user defined tracks
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/userdefinedtrack',
                           get_string('userdef_tracks_setting', 'local_elisprogram'),
                           ''/* get_string('user_tracks_help', 'local_elisprogram') */, 0));

    // ***Learning Plan
    $settings->add(new admin_setting_heading('learning_plan', get_string('learningplan', 'local_elisprogram'), '' /* get_string('learningplan_info', 'local_elisprogram') */));
    // Turn off learning plan
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/disablecoursecatalog',
                           get_string('learning_plan_setting', 'local_elisprogram'),
                           '' /* get_string('learning_plan_help', 'local_elisprogram') */, 0));
    // Number of curricula to display before automatically collapsing
    $settings->add(new admin_setting_configtext('local_elisprogram/catalog_collapse_count',
                           get_string('auto_collapse_setting', 'local_elisprogram'),
                           '' /* get_string('auto_collapse_help', 'local_elisprogram') */, 4, PARAM_INT)); // TBD

    // Enable curriculum expiration
    $setting = new admin_setting_configcheckbox('local_elisprogram/enable_curriculum_expiration',
                           get_string('crlm_expire_setting', 'local_elisprogram'),
                           '' /* get_string('crlm_expire_help', 'local_elisprogram') */, 0);
    //callback to handle updating curriculum assignment expiry times
    $setting->set_updatedcallback('curriculum_expiration_enabled_updatedcallback');
    $settings->add($setting);

    // Calculate curriculum expiration based on the time a student
    $opts = array(
                CURR_EXPIRE_ENROL_START    => get_string('curriculum_expire_enrol_start', 'local_elisprogram'),
                CURR_EXPIRE_ENROL_COMPLETE => get_string('curriculum_expire_enrol_complete', 'local_elisprogram')
            );
    $setting = new admin_setting_configselect('local_elisprogram/curriculum_expiration_start',
                           get_string('expire_basis_setting', 'local_elisprogram'),
                           '' /* get_string('expire_basis_help', 'local_elisprogram') */,
                           CURR_EXPIRE_ENROL_START, $opts);
    //callback to handle updating curriculum assignment expiry times
    $setting->set_updatedcallback('curriculum_expiration_start_updatedcallback');
    $settings->add($setting); // TBD

    // Allow students to see completed courses on their dashboard
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/display_completed_courses',
                                                     get_string('completed_courses_setting', 'local_elisprogram'),
                                                     get_string('completed_courses_help', 'local_elisprogram'), 1));

    // ***Certificates
    $settings->add(new admin_setting_heading('certificates', get_string('certificates', 'local_elisprogram'), '' /* get_string('certificate_info', 'local_elisprogram') */));
    // Disable Certificates
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/disablecoursecertificates',
                                                    get_string('disable_crs_cert', 'local_elisprogram'),
                                                    get_string('disable_crs_cert_desc', 'local_elisprogram'), 0));

    $settings->add(new admin_setting_configcheckbox('local_elisprogram/disablecertificates',
                           get_string('disable_cert_setting', 'local_elisprogram'),
                           '' /* get_string('disable_cert_help', 'local_elisprogram') */, 1));

    // Certificate border image
    $borders = function_exists('cm_certificate_get_borders')
               ? cm_certificate_get_borders()
               : array('Fancy1-blue.jpg'  => 'Fancy1-blue',
                       'Fancy1-green.jpg' => 'Fancy1-green',
                       'Fancy2-black.jpg' => 'Fancy2-black',
                       'Fancy2-brown.jpg' => 'Fancy2-brown',
                       ''                 => 'None'); // for testing
    $settings->add(new admin_setting_configselect('local_elisprogram/certificate_border_image',
                           get_string('cert_border_setting', 'local_elisprogram'),
                           get_string('cert_border_help', 'local_elisprogram'),
                           0, $borders)); // TBD

    // Certificate seal image
    $seals = function_exists('cm_certificate_get_seals')
             ? cm_certificate_get_seals()
             : array('Fancy.png' => 'Fancy', 'Logo.png' => 'Logo',
                     'Plain.png' => 'Plain', 'Quality.png' => 'Quality',
                     'Teamwork.png' => 'Teamwork', '' => 'None'); // for testing
    $settings->add(new admin_setting_configselect('local_elisprogram/certificate_seal_image',
                           get_string('cert_seal_setting', 'local_elisprogram'),
                           get_string('cert_seal_help', 'local_elisprogram'),
                           0, $seals)); // TBD

    // Certificate templates
    if ($templates = cm_certificate_get_templates()) {
        $settings->add(new admin_setting_configselect('local_elisprogram/certificate_template_file',
                           get_string('certificate_template_file', 'local_elisprogram'),
                           get_string('certificate_template_file_help', 'local_elisprogram'), 0, $templates));
    }

    // ***Interface Settings
    $settings->add(new admin_setting_heading('interface_settings', get_string('interface_settings', 'local_elisprogram'), '' /* get_string('interface_settings_info', 'local_elisprogram') */));

    // Display time selection in a 12 hour format
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/time_format_12h',
                           get_string('time_12h_setting', 'local_elisprogram'),
                           '' /* get_string('time_12h_help', 'local_elisprogram') */, 0));

    // Redirect users accessing My Moodle to the dashboard
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/mymoodle_redirect',
                           get_string('redirect_dashbrd_setting', 'local_elisprogram'),
                           '' /* get_string('redirect_dashbrd_help', 'local_elisprogram') */, 0));

    // ***User Settings
    $settings->add(new admin_setting_heading('user_settings', get_string('user_settings', 'local_elisprogram'), '' /* get_string('user_settings_info', 'local_elisprogram') */));

    // Automatically assign an ID number to Moodle users without one
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/auto_assign_user_idnumber',
            get_string('auto_idnumber_setting', 'local_elisprogram'),
            get_string('auto_idnumber_help', 'local_elisprogram'), 1));
    // Default Instructor Role
    $crsroles = array();
    if (!defined('NO_ROLE_ID')) { // TBD
        define('NO_ROLE_ID', 0);
    }
    $crsroles[NO_ROLE_ID] = get_string('noroleselected', 'local_elisprogram');
    // Get roles assignable at Moodle Course context ...
    pm_get_select_roles_for_contexts($crsroles, array(CONTEXT_COURSE));
    if (!empty($CFG->coursecontact)) {
        $crscontacts = explode(',', $CFG->coursecontact);
        foreach ($crsroles as $id => $unused) {
            // TBD: limit to Course Contacts since only these are unassigned ?
            if ($id != NO_ROLE_ID && !in_array($id, $crscontacts)) {
                unset($crsroles[$id]);
            }
        }
    }
    $settings->add(new admin_setting_configselect('local_elisprogram/default_instructor_role',
                           get_string('instructor_role_setting', 'local_elisprogram'), get_string('instructor_role_help', 'local_elisprogram'),
                           0, $crsroles));

    // forcibly unenrol students from the Moodle course regardless of what
    // enrolment plugin they used to get in
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/force_unenrol_in_moodle',
                           get_string('force_unenrol_in_moodle_setting', 'local_elisprogram'),
                           get_string('force_unenrol_in_moodle_help', 'local_elisprogram'), 0));

    // ***Curriculum Administration Block Settings
    $settings->add(new admin_setting_heading('crlm_admin_blk_settings', get_string('crlm_admin_blk_settings', 'local_elisprogram'), '' /* get_string('crlm_admin_blk_settings_info', 'local_elisprogram') */));

    // Number of entity icons to display before collapsing
    $settings->add(new admin_setting_configtext('local_elisprogram/num_block_icons',
                           get_string('icon_collapse_setting', 'local_elisprogram'),
                           get_string('icon_collapse_help', 'local_elisprogram'), 5, PARAM_INT)); // TBD

    // Display Clusters at the Top Level
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/display_clusters_at_top_level',
                           get_string('top_clusters_setting', 'local_elisprogram'),
                           get_string('top_clusters_help', 'local_elisprogram'), 1));
    // Display Curricula at the Top Level
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/display_curricula_at_top_level',
                           get_string('top_curricula_setting', 'local_elisprogram'),
                           get_string('top_curricula_help', 'local_elisprogram'), 0));

    // ***Default Role Assignments Settings
    $settings->add(new admin_setting_heading('default_role_settings', get_string('default_role_settings', 'local_elisprogram'), '' /* get_string('default_role_settings_info', 'local_elisprogram') */));

    // Default Cluster Role
    $allroles = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($allroles, array(CONTEXT_ELIS_USERSET));
    $settings->add(new admin_setting_configselect('local_elisprogram/default_cluster_role_id',
            get_string('cluster_role_setting', 'local_elisprogram'), get_string('cluster_role_help', 'local_elisprogram'), 0,
            $allroles));

    // Default Curriculum Role
    $allroles = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($allroles, array(CONTEXT_ELIS_PROGRAM));
    $settings->add(new admin_setting_configselect('local_elisprogram/default_curriculum_role_id',
            get_string('curriculum_role_setting', 'local_elisprogram'), get_string('curriculum_role_help', 'local_elisprogram'), 0,
            $allroles));

    // Default Course Role
    $allroles = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($allroles, array(CONTEXT_ELIS_COURSE));
    $settings->add(new admin_setting_configselect('local_elisprogram/default_course_role_id',
            get_string('course_role_setting', 'local_elisprogram'), get_string('course_role_help', 'local_elisprogram'), 0,
            $allroles));

    // Default Class Role
    $allroles = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($allroles, array(CONTEXT_ELIS_CLASS));
    $settings->add(new admin_setting_configselect('local_elisprogram/default_class_role_id',
            get_string('class_role_setting', 'local_elisprogram'), get_string('class_role_help', 'local_elisprogram'), 0,
            $allroles));

    // Default Track Role
    $allroles = array(0 => get_string('no_default_role', 'local_elisprogram'));
    pm_get_select_roles_for_contexts($allroles, array(CONTEXT_ELIS_TRACK));
    $settings->add(new admin_setting_configselect('local_elisprogram/default_track_role_id',
            get_string('track_role_setting', 'local_elisprogram'), get_string('track_role_help', 'local_elisprogram'), 0,
            $allroles));

    // ***Auto-create  Settings
    $settings->add(new admin_setting_heading('auto_create_settings', get_string('auto_create_settings', 'local_elisprogram'), '' /* get_string('auto_create_settings_info', 'local_elisprogram') */));
    // Moodle courses with unknown status treated as auto-created
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/autocreated_unknown_is_yes',
            get_string('auto_create_setting', 'local_elisprogram'),
            get_string('auto_create_help', 'local_elisprogram'), 1));

    //include settings for all subplugins
    include(elispm::file('db/subplugins.php'));

    foreach ($subplugins as $subplugintype => $subplugintyperootdir) {
        //get the list of instances of the current subplugin type
        $directories = get_plugin_list($subplugintype);

        //iterate thorugh each instance of this subplugin type
        foreach ($directories as $directory) {
            $settings_path = $directory.'/settings.php';
            if (file_exists($settings_path)) {
                //include the settings file
                include($settings_path);
            }
        }
    }

    // ***Legacy Settings
    $settings->add(new admin_setting_heading('legacy_settings', get_string('legacy_settings', 'local_elisprogram'), ''));

    // Legacy toggle for showing inactive users on pm pages
    $settings->add(new admin_setting_configcheckbox('local_elisprogram/legacy_show_inactive_users',
            get_string('legacy_show_inactive_users', 'local_elisprogram'),
            get_string('legacy_show_inactive_users_help', 'local_elisprogram'), 0));

    $ADMIN->add('local_elisprogram', $settings);
}

