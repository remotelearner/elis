<?php
/**
 * Library of functions for the event handlers
 *
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


require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');


/**
 * Handle the event when a user is deleted in Moodle.
 *
 * @param object $user Moodle user record object.
 * @return bool True on success, False otherwise.
 */
function elis_files_user_deleted($user) {
    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!$repo = repository_factory::factory('elisfiles')) {
        return true;
    }

    $repo->delete_user($user->username);

    return true;
}

/**
 * Handle the event when a Moodle course is deleted
 *
 * @param object $course The deleted Moodle course
 * @return bool true
 */
function elis_files_course_deleted($course) {
    global $DB;

    if (isset($course->id)) {
        $DB->delete_records('repository_elisfiles_course', array('courseid' => $course->id));
    }

    return true;
}

/**
 * Handle the event when an ELIS user set is deleted
 *
 * @param int $id The id of the deleted user set
 * @return bool true
 */
function elis_files_userset_deleted($id) {
    global $DB;

    if (!empty($id)) {
        $DB->delete_records('repository_elisfiles_userset', array('usersetid' => $id));
    }

    return true;
}

/**
 * Handle the event when a user has a role unassigned in Moodle.
 *
 * @uses $DB
 * @param object $ra The Moodle role_assignment record object.
 * @return bool True on success, False otherwise.
 */
function elis_files_role_unassigned($ra) {
    global $DB;

    // Only proceed here if we have valid userid,contextid & the Alfresco plug-in is actually enabled.
    if (empty($ra->userid) || empty($ra->contextid) ||
        !($repo = repository_factory::factory('elisfiles'))) {
        return true;
    }

    if (!$username = $DB->get_field('user', 'username', array('id' => $ra->userid))) {
        return true;
    }

    if (!$context = $DB->get_record('context', array('id' => $ra->contextid))) {
        return true;
    }

    $courses = array();

    if ($context->contextlevel == CONTEXT_COURSE) {
        // Get the course record based on the context instance from the role assignment data.
        $sql = 'SELECT c.id, c.shortname
                FROM {context} ct
                INNER JOIN {course} c ON (ct.instanceid = c.id AND ct.id = :ctid)';

        if ($course = $DB->get_record_sql($sql, array('ctid' => $ra->contextid))) {
            $courses[$course->id] = $course;
        }
    } else if ($context->contextlevel == CONTEXT_COURSECAT) {
        $sql = 'SELECT c.id, c.shortname
                FROM {context} ct
                INNER JOIN {course_categories} cat ON ct.instanceid = cat.id
                INNER JOIN {course} c ON (cat.id = c.category AND ct.id = :ctid)
                ORDER BY c.sortorder ASC';

        $courses = $DB->get_recordset_sql($sql, array('ctid' => $ra->contextid));
    }

    foreach ($courses as $course) {
        $context = context_course::instance($course->id);

        if (!has_capability('repository/elisfiles:viewcoursecontent', $context, $ra->userid, false) &&
            !has_capability('repository/elisfiles:createcoursecontent', $context, $ra->userid, false)) {

            if ($uuid = $repo->get_course_store($course->id)) {
                // Look for Alfresco capabilities in this context for this user and assign permissions as required.
                if ($permissions = elis_files_get_permissions($uuid, $username)) {
                    foreach ($permissions as $permission) {
                        // TODO: determine if this is still needed
                        elis_files_set_permission($username, $uuid, $permission, ELIS_FILES_CAPABILITY_DENIED);
                    }
                }
            }
        }
    }

    $root = $repo->get_root();

    // Check to see if we need to remove root-level Alfresco repository permissions for this user.
    if (!empty($root->uuid)) {
        // If the role this user was just unassigned had the editing capability for the root of the
        // Alfresco repository space associated with it.
        $params = array(
            'roleid'     => $ra->roleid,
            'permission' => CAP_ALLOW,
            'capability' => 'repository/elisfiles:createsitecontent'
        );

        if ($DB->record_exists('role_capabilities', $params)) {
            // Check to see if this user still has this capability somewhere in the system and remove it if not.
            $sql = 'SELECT ra.*
                    FROM {role_assignments} ra
                    INNER JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                    WHERE ra.userid = :userid
                    AND rc.capability = :cap
                    AND rc.permission = :perm';

            $params = array(
                'userid' => $ra->userid,
                'cap'    => 'repository/elisfiles:createsitecontent',
                'perm'   => CAP_ALLOW
            );

            if (!$DB->record_exists_sql($sql, $params)) {
                if (elis_files_has_permission($root->uuid, $username, true)) {
                    elis_files_set_permission($username, $root->uuid, ELIS_FILES_ROLE_COLLABORATOR, ELIS_FILES_CAPABILITY_DENIED);
                }
            }
        }

        // If the role this user was just unassigned had the view capability for the root of the
        // Alfresco repository space associated with it.
        $params = array(
    	    'roleid'     => $ra->roleid,
    	    'permission' => CAP_ALLOW,
            'capability' => 'repository/elisfiles:viewsitecontent'
        );

        if ($DB->record_exists('role_capabilities', $params)) {
            // Check to see if this user still has this capability somewhere in the system and remove it if not.
            $sql = 'SELECT ra.*
                    FROM {role_assignments} ra
                    INNER JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                    WHERE ra.userid = :userid
                    AND rc.capability = :cap
                    AND rc.permission = :perm';

            $params = array(
                'userid' => $ra->userid,
                'cap'    => 'repository/elisfiles:viewsitecontent',
                'perm'   => CAP_ALLOW
            );

            if (!$DB->record_exists_sql($sql, $params)) {
                if ($permissions = elis_files_get_permissions($root->uuid, $username)) {
                    foreach ($permissions as $permission) {
                        elis_files_set_permission($username, $root->uuid, $permission, ELIS_FILES_CAPABILITY_DENIED);
                    }
                }
            }
        }
    }


    // If the role this user was just unassigned had the editing capability for the Alfresco shared
    // storage space associated with it.
    $params = array(
        'roleid'     => $ra->roleid,
        'permission' => CAP_ALLOW,
        'capability' => 'repository/elisfiles:createsharedcontent'
    );

    if ($DB->record_exists('role_capabilities', $params)) {
        // Check to see if this user still has this capability somewhere in the system and remove it if not.
        $sql = 'SELECT ra.*
                FROM {role_assignments} ra
                INNER JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                WHERE ra.userid = :userid
                AND rc.capability = :cap
                AND rc.permission = :perm';

        $params = array(
            'userid' => $ra->userid,
            'cap'    => 'repository/elisfiles:createsharedcontent',
            'perm'   => CAP_ALLOW
        );

        if (!$DB->record_exists_sql($sql, $params)) {
            if (elis_files_has_permission($repo->suuid, $username, true)) {
                elis_files_set_permission($username, $repo->suuid, ELIS_FILES_ROLE_COLLABORATOR, ELIS_FILES_CAPABILITY_DENIED);
            }
        }
    }

    // If the role this user was just unassigned had the view capability for the Alfresco shared
    // storage space associated with it.
    $params = array(
        'roleid'     => $ra->roleid,
        'permission' => CAP_ALLOW,
        'capability' => 'repository/elisfiles:viewsharedcontent'
    );

    if ($DB->record_exists('role_capabilities', $params)) {
        // Check to see if this user still has this capability somewhere in the system and remove it if not.
        $sql = 'SELECT ra.*
                FROM {role_assignments} ra
                INNER JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                WHERE ra.userid = :userid
                AND rc.capability = :cap
                AND rc.permission = :perm';

        $params = array(
            'userid' => $ra->userid,
            'cap'    => 'repository/elisfiles:viewsharedcontent',
            'perm'   => CAP_ALLOW
        );

        if (!$DB->record_exists_sql($sql, $params)) {
            if ($permissions = elis_files_get_permissions($repo->suuid, $username)) {
                foreach ($permissions as $permission) {
                    elis_files_set_permission($username, $repo->suuid, $permission, ELIS_FILES_CAPABILITY_DENIED);
                }
            }
        }
    }

    return true;
}


/**
 * Handle the event when a user is assigned to a cluster.
 *
 * @uses $DB
 * @param object $usersetinfo The ELIS crlm_cluster_assignments record object.
 * @return bool True on success or failure (event handlers must always return true).
 */
function elis_files_userset_assigned($usersetinfo) {
    global $DB;

    // Only proceed here if we have valid userid, clusterid & the Alfresco plug-in is actually enabled.
    if (empty($usersetinfo->userid) || empty($usersetinfo->clusterid) ||
        !($repo = repository_factory::factory('elisfiles'))) {
        return true;
    }

    $user = new user($usersetinfo->userid);

    // Get the Moodle user info from the CM user record.
    if (!$muser = $user->get_moodleuser()) {
        return true;
    }

    if (!$userset = $DB->get_record(userset::TABLE, array('id' => $usersetinfo->clusterid))) {
        return true;
    }

    if (!file_exists(elispm::file('plugins/usetclassify/usersetclassification.class.php'))) {
        return true;
    }

    require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));

    // Get the extra user set data and ensure it is present before proceeding.
    $usersetdata = usersetclassification::get_for_cluster($userset);

    if (empty($usersetdata->params)) {
        return true;
    }

    $usersetparams = unserialize($usersetdata->params);

    // Make sure this user set has the Alfresco shared folder property defined
    if (empty($usersetparams['elis_files_shared_folder'])) {
        return true;
    }

    // Make sure we can get the storage space from Alfresco for this organization.
    if (!$uuid = $repo->get_userset_store($userset->id)) {
        return true;
    }

    $context = \local_elisprogram\context\userset::instance($userset->id);

    $sql = 'SELECT ra.*
            FROM {role_assignments} ra
            INNER JOIN {role} r ON ra.roleid = r.id
            INNER JOIN {role_capabilities} rc ON r.id = rc.roleid
            WHERE ra.contextid = :contextid
            AND ra.userid = :userid
            AND rc.capability IN (:cap1, :cap2)
            AND rc.permission = '.CAP_ALLOW;

    $params = array(
        'contextid' => $context->id,
        'userid'    => $muser->id,
        'cap1'      => 'repository/elisfiles:createusersetcontent',
        'cap2'      => 'local/elisprogram:userset_enrol'
    );

    // Check if the user has the the editing capability for the Alfresco organization shared
    // storage space assigned at this user set context level or is designated as a user set leader in order to
    // enable editing permission on the Alfresco space.
    if ($DB->record_exists_sql($sql, $params)) {
        // Ensure that this user already has an Alfresco account.
        if (!$repo->elis_files_userdir($muser->username)) {
            if (!$repo->migrate_user($muser->username)) {
                return true;
            }
        }

        if (!elis_files_has_permission($uuid, $muser->username, true)) {
            elis_files_set_permission($muser->username, $uuid, ELIS_FILES_ROLE_COLLABORATOR, ELIS_FILES_CAPABILITY_ALLOWED);
        }

        // Double-check tjhat the user is designated as a user set member (we should not be here otherwise) in order to
        // enable viewinging permission on the Alfresco space.
    } else if ($DB->record_exists(clusterassignment::TABLE, array('userid' => $usersetinfo->userid, 'clusterid' => $userset->id))) {
        // Ensure that this user already has an Alfresco account.
        if (!$repo->elis_files_userdir($muser->username)) {
            if (!$repo->migrate_user($muser->username)) {
                return true;
            }
        }

        if (!elis_files_has_permission($uuid, $muser->username, false)) {
            elis_files_set_permission($muser->username, $uuid, ELIS_FILES_ROLE_CONSUMER, ELIS_FILES_CAPABILITY_ALLOWED);
        }
    }

    return true;
}


/**
 * Handle the event when a user is unassigned from a user set.
 *
 * @uses $DB
 * @param object $usersetinfo The ELIS crlm_cluster_assignments record object.
 * @return bool True on success or failure (event handlers must always return true).
 */
function elis_files_userset_deassigned($usersetinfo) {
    global $DB;

    // Only proceed here if we have valid userid,clusterid & the Alfresco plug-in is actually enabled.
    if (empty($usersetinfo->userid) || empty($usersetinfo->clusterid) ||
        !($repo = repository_factory::factory('elisfiles'))) {
        return true;
    }

    $user = new user($usersetinfo->userid);

    // Get the Moodle user info from the CM user record.
    if (!$muser = $user->get_moodleuser()) {
        return true;
    }

    if (!($userset = $DB->get_record(userset::TABLE, array('id' => $usersetinfo->clusterid)))) {
        return true;
    }

    if (!file_exists(elispm::file('plugins/usetclassify/usersetclassification.class.php'))) {
        return true;
    }

    require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));

    // Get the extra user set data and ensure it is present before proceeding.
    $usersetdata = usersetclassification::get_for_cluster($userset);

    if (empty($usersetdata->params)) {
        return true;
    }

    $usersetparams = unserialize($usersetdata->params);

    // Make sure this user set has the Alfresco shared folder property defined
    if (empty($usersetparams['elis_files_shared_folder'])) {
        return true;
    }

    // Does this organization have an Alfresco storage space?
    if (!$uuid = $repo->get_userset_store($userset->id, false)) {
        return true;
    }

    $context = \local_elisprogram\context\userset::instance($userset->id);

    $sql = 'SELECT rc.*
            FROM {role_assignments} ra
            INNER JOIN {role} r ON ra.roleid = r.id
            INNER JOIN {role_capabilities} rc ON r.id = rc.roleid
            WHERE ra.contextid = :contextid
            AND ra.userid = :userid
            AND rc.capability IN (:cap1, :cap2)
            AND rc.permission = '.CAP_ALLOW;

    $params = array(
        'contextid' => $context->id,
        'userid'    => $muser->id,
        'cap1'      => 'repository/elisfiles:createusersetcontent',
        'cap2'      => 'local/elisprogram:userset_enrol'
    );

    // Check if the user has a specific role assignment on the user set context with the editing capability
    if ($DB->record_exists_sql($sql, $params)) {
        // Remove all non-editing permissions for this user on the organization shared space.
        if ($permissions = elis_files_get_permissions($uuid, $muser->username)) {
            foreach ($permissions as $permission) {
                // Do not remove editing permissions if this user still actually has a user set membership.
                if ($permission == ELIS_FILES_ROLE_COLLABORATOR) {
                    continue;
                }

                elis_files_set_permission($muser->username, $uuid, $permission, ELIS_FILES_CAPABILITY_DENIED);
            }
        }

    // Remove all permissions for this user on the organization shared space.
    } else if ($permissions = elis_files_get_permissions($uuid, $muser->username)) {
        require_once(elispm::lib('data/clusterassignment.class.php'));
        foreach ($permissions as $permission) {
            // Do not remove view permissions if this user still actually has a user set membership.
            $params = array(
                'userid'    => $usersetinfo->userid,
                'clusterid' => $userset->id
            );

            if ($permission == ELIS_FILES_ROLE_CONSUMER && $DB->record_exists(clusterassignment::TABLE, $params)) {
                continue;
            }

            elis_files_set_permission($muser->username, $uuid, $permission, ELIS_FILES_CAPABILITY_DENIED);
        }
    }

    return true;
}

/**
 * Handle the event when a user is created in Moodle.
 *
 * @uses $CFG
 * @param object $user Moodle user record object.
 * @return bool True on success, False otherwise.
 */
function elis_files_user_created($user) {
    global $CFG;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!($repo = repository_factory::factory('elisfiles')) || !$repo->is_configured() || !$repo->verify_setup()) {
        //error_log("elis_files_user_created(): Alfresco NOT enabled!");
    } else {
        // create a random password for certain authentications
        $auths = elis_files_nopasswd_auths();
        if (!empty($user->auth) && in_array($user->auth, $auths)) {
            $passwd = random_string(8);
            //$user->password = md5($passwd); // TBD: or reversible encrypt
            //update_record('user', $user);
            //error_log("elis_files_user_created(): generating password for {$user->id} ({$user->auth}) => {$passwd}");
            $repo->migrate_user($user, $passwd);
        }
    }

    return true;
}
