<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function cluster_profile_update_handler($userdata) {
    // make sure a CM user exists
    pm_moodle_user_to_pm($userdata);

    //todo: complete the rest of this function once all the related cluster code is available
    /*
    $cuid = cm_get_crlmuserid($userdata->id);

    if (empty($cuid)) {
        // not a curriculum user -- (guest?)
        return true;
    }

    $usrtable      = $CURMAN->db->prefix_table(USRTABLE);
    $clstproftable = $CURMAN->db->prefix_table(CLSTPROFTABLE);
    $clstasstable  = $CURMAN->db->prefix_table(CLSTASSTABLE);

    // the cluster assignments that the plugin wants to exist
    $new_assignments = "(SELECT DISTINCT cu.id as userid, cp.clusterid
                         FROM {$CFG->prefix}crlm_cluster_profile cp
                         INNER JOIN {$CFG->prefix}crlm_user cu ON cu.id = $cuid
                         INNER JOIN {$CFG->prefix}user mu on cu.idnumber=mu.idnumber AND mu.mnethostid = {$CFG->mnet_localhost_id}
                         WHERE (SELECT COUNT(*)
                                FROM {$CFG->prefix}crlm_cluster_profile cp1
                                JOIN (SELECT i.userid, i.fieldid, i.data FROM {$CFG->prefix}user_info_data i
                                      WHERE i.userid = {$userdata->id}
                                      UNION
                                      SELECT  {$userdata->id} as userid, uif.id as fieldid, uif.defaultdata as data
                                      FROM {$CFG->prefix}user_info_field uif
                                      LEFT JOIN {$CFG->prefix}user_info_data i ON i.userid={$userdata->id} AND uif.id = i.fieldid
                                      WHERE i.id IS NULL
                                     ) inf ON inf.fieldid = cp1.fieldid AND inf.data = cp1.value
                                WHERE cp.clusterid=cp1.clusterid AND inf.userid = mu.id)
                               = (SELECT COUNT(*) FROM {$CFG->prefix}crlm_cluster_profile cp1 WHERE cp.clusterid = cp1.clusterid))";

    // delete existing assignments that should not be there any more
    if ($CFG->dbfamily == 'postgres') {
        $delete = "DELETE FROM $clstasstable
                   WHERE id IN (
                       SELECT id FROM $clstasstable a
                       LEFT OUTER JOIN $new_assignments b ON a.clusterid = b.clusterid AND a.userid = b.userid
                       WHERE a.userid = $cuid AND b.clusterid IS NULL
                   ) AND plugin='profile'";
    } else {
        $delete = "DELETE a FROM $clstasstable a
                   LEFT OUTER JOIN $new_assignments b ON a.clusterid = b.clusterid AND a.userid = b.userid
                   WHERE a.userid = $cuid AND b.clusterid IS NULL AND a.plugin='profile'";
    }
    $CURMAN->db->execute_sql($delete, false);

    // add new assignments
    $insert = "INSERT INTO $clstasstable
               (clusterid, userid, plugin)
               SELECT a.clusterid, a.userid, 'profile'
               FROM $new_assignments a
               LEFT OUTER JOIN $clstasstable b ON a.clusterid = b.clusterid AND a.userid = b.userid AND b.plugin='profile'
               WHERE a.userid = $cuid AND b.clusterid IS NULL";

    $CURMAN->db->execute_sql($insert, false);

    cluster::cluster_update_assignments(null, $cuid);
    */

    return true;
}