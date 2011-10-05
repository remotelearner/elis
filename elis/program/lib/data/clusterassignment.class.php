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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once elis::lib('data/data_object.class.php');
require_once elispm::lib('data/userset.class.php');

class clusterassignment extends elis_data_object {
	/*
	 var $id;            // INT - The data id if in the database.
	 var $name;          // STRING - Textual name of the cluster.
	 var $display;       // STRING - A description of the cluster.
	 */

    const TABLE = 'crlm_cluster_assignments';

    /**
     * User ID-number
     * @var    char
     * @length 255
     */
    protected $_dbfield_id;
    protected $_dbfield_clusterid;
    protected $_dbfield_userid;
    protected $_dbfield_plugin;
    protected $_dbfield_autoenrol;
    protected $_dbfield_leader;

    private $location;
    private $templateclass;

    static $associations = array(
        'user' => array(
            'class' => 'user',
            'idfield' => 'userid'
        ),
        'cluster' => array(
            'class' => 'userset',
            'idfield' => 'clusterid'
        )
    );

	public function delete() {
        $status = parent::delete();

        return $status;
	}

	public static function delete_for_user($id) {
    	global $DB;

    	$status = $DB->delete_records(self::TABLE, array('userid'=> $id));

    	return $status;
    }

	public static function delete_for_cluster($id) {
    	global $DB;

    	$status = $DB->delete_records(self::TABLE, array('clusterid'=> $id));

    	return $status;
    }

    /**
     * Save the record to the database.  This method is used to both create a
     * new record, and to update an existing record.
     */
    public function save() {
        $trigger = false;

        if (!isset($this->id)) {
            $trigger = true;
        }

        parent::save();

        if ($trigger) {
            $usass = new stdClass;
            $usass->userid = $this->userid;
            $usass->clusterid = $this->clusterid;

            events_trigger('cluster_assigned', $usass);
        }
    }

    /**
     * Updates resulting enrolments that are auto-created after users are
     * assigned to user sets (specifically user-track assignments, user-program
     * assignments, and class enrolments in a track's default class)
     *
     * Note: This is essentially equivalent to cluster_assigned_handler but
     * runs a fixed number of queries for scalability reasons
     *
     * @param  int  $userid     A specific PM user id to filter on for
     *                          consideration, or all users if zero
     * @param  int  $clusterid  A specific cluster / user set id to filter
     *                          on for consideration, or all users if zero
     */
	function update_enrolments($userid = 0, $clusterid = 0) {
	    global $DB;

	    //convert provided parameters to SQL conditions
	    $extraconditions = array();
	    $extraparams = array();

	    if (!empty($userid)) {
	    	$extraconditions[] = 'u.id = ?';
	    	$extraparams[] = $userid;
	    }

	    if (!empty($clusterid)) {
	        $extraconditions[] = 'clu.clusterid = ?';
	        $extraparams[] = $clusterid;
	    }

	    //conbine conditions into a where condition
	    $extrawhere = '';
	    if (!empty($extraconditions)) {
	    	$extrawhere = ' AND '.implode(' AND ', $extraconditions);
	    }

	    //use the current time as the time created and modified for curriculum
	    //assignments
	    $timenow = time();

	    //assign to curricula based on user-cluster and cluster-curriculum
	    //associations
	    $sql = "INSERT INTO {".curriculumstudent::TABLE."}
	           (userid, curriculumid, timecreated, timemodified)
	           SELECT DISTINCT u.id, clucur.curriculumid, {$timenow}, {$timenow}
	           FROM {".clusterassignment::TABLE."} clu
	           JOIN {".user::TABLE."} u ON u.id = clu.userid
	           JOIN {".clustercurriculum::TABLE."} clucur
	             ON clucur.clusterid = clu.clusterid
	           LEFT JOIN {".curriculumstudent::TABLE."} ca
	             ON ca.userid = u.id
	             AND ca.curriculumid = clucur.curriculumid
	           WHERE ca.curriculumid IS NULL
	             AND clucur.autoenrol = 1
	             {$extrawhere}";
	    $DB->execute($sql, $extraparams);

	    //assign to curricula based on user-cluster and cluster-track
	    //associations (assigning a user to a track auto-assigns them to
	    //the track's curriculum, track assignment happens below)
	    $sql = "INSERT INTO {".curriculumstudent::TABLE."}
	            (userid, curriculumid, timecreated, timemodified)
	            SELECT DISTINCT u.id, trk.curid, {$timenow}, {$timenow}
	            FROM {".clusterassignment::TABLE."} clu
	            JOIN {".user::TABLE."} u
	              ON u.id = clu.userid
	            JOIN {".clustertrack::TABLE."} clutrk
	              ON clutrk.clusterid = clu.clusterid
	            JOIN {".track::TABLE."} trk ON
	              clutrk.trackid = trk.id 
	            LEFT JOIN {".curriculumstudent::TABLE."} ca
	              ON ca.userid = u.id
	              AND ca.curriculumid = trk.curid
	            WHERE ca.curriculumid IS NULL
	              AND clutrk.autoenrol = 1
	              {$extrawhere}";
	    $DB->execute($sql, $extraparams); 

	    //this represents the tracks that users will be assigned to
	    //based on user-cluster and cluster-track associations
	    //(actual assignment happens below)
	    $exists = "EXISTS (SELECT DISTINCT u.id, clutrk.trackid
	               FROM {".clusterassignment::TABLE."} clu
	               JOIN {".user::TABLE."} u
	                 ON u.id = clu.userid
	               JOIN {".clustertrack::TABLE."} clutrk
	                 ON clutrk.clusterid = clu.clusterid
	               LEFT JOIN {".usertrack::TABLE."} ta
	                 ON ta.userid = u.id
	                 AND ta.trackid = clutrk.trackid
	               WHERE ta.trackid IS NULL
	                 AND clutrk.autoenrol = 1
	                 AND outerta.trackid = clutrk.trackid
	                 {$extrawhere})";

	    /**
	     * Get autoenrollable classes in the track.  Classes are autoenrollable
	     * if:
	     * - the autoenrol flag is set
	     * - it is the only class in that course slot for the track
	     */
	    // group the classes from the same course together
	    // only select the ones that are the only class for that course in
	    // the given track, and if the autoenrol flag is set
	    $sql = "SELECT outerta.classid, outerta.courseid
	            FROM {".trackassignment::TABLE."} outerta
	            WHERE {$exists}
	            GROUP BY courseid
	            HAVING COUNT(*) = 1
	              AND MAX(autoenrol) = 1";

	    //go through and assign user(s) to the autoenollable classes
	    $classes = $DB->get_records_sql($sql, $extraparams);
	    if (!empty($classes)) {
	        foreach ($classes as $class) {
	            $now = time();
	            // enrol user in each autoenrolable class
	            $stu_record = new object();
	            $stu_record->userid = $userid;
	            $stu_record->classid = $class->classid;
	            $stu_record->enrolmenttime = $now;
	            $enrolment = new student($stu_record);

	            // catch enrolment limits
	            try {
	                $status = $enrolment->save();
	            } catch (pmclass_enrolment_limit_validation_exception $e) {
	                // autoenrol into waitlist
	                $wait_record = new object();
	                $wait_record->userid = $userid;
	                $wait_record->classid = $class->classid;
	                $wait_record->enrolmenttime = $now;
	                $wait_record->timecreated = $now;
	                $wait_record->position = 0;
	                $wait_list = new waitlist($wait_record);
	                $wait_list->save();
	                $status = true;
	            } catch (Exception $e) {
	                echo cm_error(get_string('record_not_created_reason',
	                                         'elis_program', $e));
	            }
	        }
	    }

	    //assign to tracks based on user-cluster and cluster-track
	    //associations
	    $sql = "INSERT INTO {".usertrack::TABLE."}
	            (userid, trackid)
	            SELECT DISTINCT u.id, clutrk.trackid
	            FROM {".clusterassignment::TABLE."} clu
	            JOIN {".user::TABLE."} u
	              ON u.id = clu.userid
	            JOIN {".clustertrack::TABLE."} clutrk
	              ON clutrk.clusterid = clu.clusterid
	            LEFT JOIN {".usertrack::TABLE."} ta
	              ON ta.userid = u.id
	              AND ta.trackid = clutrk.trackid
	            WHERE ta.trackid IS NULL
	              AND clutrk.autoenrol = 1
	              {$extrawhere}";
	    $DB->execute($sql, $extraparams);
	}
}
