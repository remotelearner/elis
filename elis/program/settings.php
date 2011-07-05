<?php

defined('MOODLE_INTERNAL') || die;

require_once dirname(__FILE__) .'/lib/setup.php';
require_once elispm::lib('data/curriculumstudent.class.php'); // defines
//require_once elispm::lib('certificate.class.php'); // TBD: cm_certificate_get__()

global $DB; // TBD: roles

if ($ADMIN->fulltree) {
    $ADMIN->add('root', new admin_category('elis_program', get_string('elis_config', 'elis_program'), true));

    $settings = new admin_settingpage('elis_program_settings', get_string('elis_settings', 'elis_program'));

    // ***Track Settings
    $settings->add(new admin_setting_heading('track_settings', get_string('track_settings', 'elis_program'), '' /* get_string('track_settings_info', 'elis_program') */));
    // Turn off user defined tracks
    $settings->add(new admin_setting_configcheckbox('elis_program/userdefinedtrack',
                           get_string('userdef_tracks_setting', 'elis_program'),
                           ''/* get_string('user_tracks_help', 'elis_program') */, 0));

    // ***Learning Plan
    $settings->add(new admin_setting_heading('learning_plan', get_string('learningplan', 'elis_program'), '' /* get_string('learningplan_info', 'elis_program') */));
    // Turn off learning plan
    $settings->add(new admin_setting_configcheckbox('elis_program/disablecoursecatalog',
                           get_string('learning_plan_setting', 'elis_program'),
                           '' /* get_string('learning_plan_help', 'elis_program') */, 0));
    // Number of curricula to display before automatically collapsing
    $settings->add(new admin_setting_configtext('elis_program/catalog_collapse_count',
                           get_string('auto_collapse_setting', 'elis_program'),
                           '' /* get_string('auto_collapse_help', 'elis_program') */, 4, PARAM_INT)); // TBD

    // Enable curriculum expiration
    $settings->add(new admin_setting_configcheckbox('elis_program/crlm_expire',
                           get_string('crlm_expire_setting', 'elis_program'),
                           '' /* get_string('crlm_expire_help', 'elis_program') */, 0));

    // Calculate curriculum expiration based on the time a student
    $opts = array(
                CURR_EXPIRE_ENROL_START    => get_string('curriculum_expire_enrol_start', 'elis_program'),
                CURR_EXPIRE_ENROL_COMPLETE => get_string('curriculum_expire_enrol_complete', 'elis_program')
            );
    $settings->add(new admin_setting_configselect('elis_program/curriculum_expiration_start',
                           get_string('expire_basis_setting', 'elis_program'),
                           '' /* get_string('expire_basis_help', 'elis_program') */,
                           CURR_EXPIRE_ENROL_START, $opts)); // TBD

    // ***Certificates
    $settings->add(new admin_setting_heading('certificates', get_string('certificates', 'elis_program'), '' /* get_string('certificate_info', 'elis_program') */));
    // Disable Certificates
    $settings->add(new admin_setting_configcheckbox('elis_program/disablecertificates',
                           get_string('disable_cert_setting', 'elis_program'),
                           '' /* get_string('disable_cert_help', 'elis_program') */, 0));

    // Certificate border image
    $borders = function_exists('cm_certificate_get_borders')
               ? cm_certificate_get_borders()
               : array('Fancy1-blue', 'Fancy1-green',
                       'Fancy2-black', 'Fancy2-brown', 'None'); // for testing
    $settings->add(new admin_setting_configselect('elis_program/certificate_border_image',
                           get_string('cert_border_setting', 'elis_program'),
                           get_string('cert_border_help', 'elis_program'),
                           0, $borders)); // TBD

    // Certificate seal image
    $seals = function_exists('cm_certificate_get_seals')
             ? cm_certificate_get_seals()
             : array('Fancy', 'Logo', 'Plain', 'Quality',
                     'Teamwork', 'None'); // for testing
    $settings->add(new admin_setting_configselect('elis_program/certificate_seal_image',
                           get_string('cert_seal_setting', 'elis_program'),
                           get_string('cert_seal_help', 'elis_program'),
                           0, $seals)); // TBD

    // ***Interface Settings
    $settings->add(new admin_setting_heading('interface_settings', get_string('interface_settings', 'elis_program'), '' /* get_string('interface_settings_info', 'elis_program') */));

    // Display time selection in a 12 hour format
    $settings->add(new admin_setting_configcheckbox('elis_program/time_format_12h',
                           get_string('time_12h_setting', 'elis_program'),
                           '' /* get_string('time_12h_help', 'elis_program') */, 0));

    // Redirect users accessing My Moodle to the dashboard
    $settings->add(new admin_setting_configcheckbox('elis_program/mymoodle_redirect',
                           get_string('redirect_dashbrd_setting', 'elis_program'),
                           '' /* get_string('redirect_dashbrd_help', 'elis_program') */, 0));

    // ***User Settings
    $settings->add(new admin_setting_heading('user_settings', get_string('user_settings', 'elis_program'), '' /* get_string('user_settings_info', 'elis_program') */));

    // Automatically assign an ID number to Moodle users without one
    $settings->add(new admin_setting_configcheckbox('elis_program/auto_assign_user_idnumber',
                           get_string('auto_idnumber_setting', 'elis_program'),
                           get_string('auto_idnumber_help', 'elis_program'), 0));
    // Default Instructor Role
    $roles = array();
    if (!defined('NO_ROLE_ID')) { // TBD
        define('NO_ROLE_ID', 0);
    }
    $roles[NO_ROLE_ID] = get_string('noroleselected', 'elis_program');
    if (($role_records = $DB->get_records('role'))) { // TBD: sort order?
        foreach($role_records as $id => $role_record) {
            $roles[$id] = $role_record->name;
        }
    }
    $settings->add(new admin_setting_configselect('elis_program/default_instructor_role',
                           get_string('instructor_role_setting', 'elis_program'),
                           get_string('instructor_role_help', 'elis_program'),
                           0, $roles)); // TBD


    // Only allow enrolments to Moodle courses that use the ELIS plugin
    $settings->add(new admin_setting_configcheckbox('elis_program/restrict_to_elis_enrolment_plugin',
                           get_string('enrol_elis_setting', 'elis_program'),
                           get_string('enrol_elis_help', 'elis_program'), 0));

    // ***Cluster Group Settings
    $settings->add(new admin_setting_heading('cluster_grp_settings', get_string('cluster_grp_settings', 'elis_program'), '' /* get_string('cluster_grp_settings_info', 'elis_program') */));

    // Allow course-level group population from clusters
    $settings->add(new admin_setting_configcheckbox('elis_program/cluster_groups',
                           get_string('grp_pop_cluster_setting', 'elis_program'),
                           '' /*get_string('grp_pop_cluster_help', 'elis_program') */, 0));
    // Allow front page group population from clusters
    $settings->add(new admin_setting_configcheckbox('elis_program/site_course_cluster_groups',
                           get_string('fp_pop_clusters_setting', 'elis_program'),
                           '' /* get_string('fp_pop_clusters_help', 'elis_program') */, 0));
    // Allow front page grouping creation from cluster-based groups
    $settings->add(new admin_setting_configcheckbox('elis_program/cluster_groupings',
                           get_string('fp_grp_cluster_setting', 'elis_program'),
                           get_string('fp_grp_cluster_help', 'elis_program'), 0));

    // ***Curriculum Administration Block Settings
    $settings->add(new admin_setting_heading('crlm_admin_blk_settings', get_string('crlm_admin_blk_settings', 'elis_program'), '' /* get_string('crlm_admin_blk_settings_info', 'elis_program') */));

    // Number of entity icons to display before collapsing
    $settings->add(new admin_setting_configtext('elis_program/num_block_icons',
                           get_string('icon_collapse_setting', 'elis_program'),
                           get_string('icon_collapse_help', 'elis_program'), 5, PARAM_INT)); // TBD

    // Display Clusters as the Top Level
    $settings->add(new admin_setting_configcheckbox('elis_program/display_clusters_at_top_level',
                           get_string('top_clusters_setting', 'elis_program'),
                           get_string('top_clusters_help', 'elis_program'), 0));
    // Display Curricula at the Top Level
    $settings->add(new admin_setting_configcheckbox('elis_program/display_curricula_at_top_level',
                           get_string('top_curricula_setting', 'elis_program'),
                           get_string('top_curricula_help', 'elis_program'), 0));

    // ***Default Role Assignments Settings
    $settings->add(new admin_setting_heading('default_role_settings', get_string('default_role_settings', 'elis_program'), '' /* get_string('default_role_settings_info', 'elis_program') */));

    // we must not use assignable roles here:
    //   1/ unsetting roles as assignable for admin might bork the settings!
    //   2/ default user role should not be assignable anyway
    $allroles = array(0 => get_string('no_default_role', 'elis_program'));
    $nonguestroles = array();
    if (($roles = get_all_roles())) {
        foreach ($roles as $role) {
            $rolename = strip_tags(format_string($role->name, true));
            $allroles[$role->id] = $rolename;
            if (!isset($guestroles[$role->id])) { // TBD: where's this defined?
                $nonguestroles[$role->id] = $rolename;
            }
        }
    }

    // Default Cluster Role
    $settings->add(new admin_setting_configselect('elis_program/default_cluster_role_id',
                           get_string('cluster_role_setting', 'elis_program'),
                           get_string('cluster_role_help', 'elis_program'),
                           0, $allroles)); // TBD
    // Default Curriculum Role
    $settings->add(new admin_setting_configselect('elis_program/default_curriculum_role_id',
                           get_string('curriculum_role_setting', 'elis_program'),
                           get_string('curriculum_role_help', 'elis_program'),
                           0, $allroles)); // TBD
    // Default Course Role
    $settings->add(new admin_setting_configselect('elis_program/default_course_role_id',
                           get_string('course_role_setting', 'elis_program'),
                           get_string('course_role_help', 'elis_program'),
                           0, $allroles)); // TBD
    // Default Class Role
    $settings->add(new admin_setting_configselect('elis_program/default_class_role_id',
                           get_string('class_role_setting', 'elis_program'),
                           get_string('class_role_help', 'elis_program'),
                           0, $allroles)); // TBD
    // Default Track Role
    $settings->add(new admin_setting_configselect('elis_program/default_track_role_id',
                           get_string('track_role_setting', 'elis_program'),
                           get_string('track_role_help', 'elis_program'),
                           0, $allroles)); // TBD

    // ***Enrolment Role Sync Settings
    $settings->add(new admin_setting_heading('enrole_sync_settings', get_string('enrole_sync_settings', 'elis_program'), '' /* get_string('enrole_sync_settings_info', 'elis_program') */));

    // Student Role
    $settings->add(new admin_setting_configselect('elis_program/enrolment_role_sync_student_role',
                           get_string('sync_student_role_setting', 'elis_program'),
                           get_string('sync_student_role_help', 'elis_program'),
                           0, $allroles)); // TBD
    // Instructor Role
    $settings->add(new admin_setting_configselect('elis_program/enrolment_role_sync_instructor_role',
                           get_string('sync_instructor_role_setting', 'elis_program'),
                           get_string('sync_instructor_role_help', 'elis_program'),
                           0, $allroles)); // TBD

    // ***Auto-create  Settings
    $settings->add(new admin_setting_heading('auto_create_settings', get_string('auto_create_settings', 'elis_program'), '' /* get_string('auto_create_settings_info', 'elis_program') */));
    // Moodle courses with unknown status treated as auto-created
    $settings->add(new admin_setting_configcheckbox('elis_program/autocreated_unknown_is_yes',
                           get_string('auto_create_setting', 'elis_program'),
                           get_string('auto_create_help', 'elis_program'), 0));

    $ADMIN->add('elis_program', $settings);
}

