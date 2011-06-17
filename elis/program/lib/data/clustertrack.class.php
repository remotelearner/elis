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
require_once elis::lib('data/data_object_with_custom_fields.class.php');

//require_once CURMAN_DIRLOCATION . '/lib/datarecord.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/user.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/curriculum.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/curriculumcourse.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/cluster.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/curriculumstudent.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/usertrack.class.php';
//require_once CURMAN_DIRLOCATION . '/lib/track.class.php';

require_once elispm::lib('data/curriculum.class.php');
require_once elispm::lib('data/curriculumcourse.class.php');
require_once elispm::lib('data/curriculumstudent.class.php');
require_once elispm::lib('data/userset.class.php');
require_once elispm::lib('data/user.class.php');
require_once elispm::lib('data/usertrack.class.php');
require_once elispm::lib('data/track.class.php');

class clustertrack extends elis_data_object {
    const TABLE = 'crlm_cluster_track';
    /**
     * User ID-number
     * @var    char
     * @length 255
     */
    protected $_dbfield_id;
    protected $_dbfield_clusterid;
    protected $_dbfield_trackid;
    protected $_dbfield_autoenrol;
    protected $_dbfield_autounenrol;
    protected $_dbfield_enrolmenttime;

    private $location;
    private $templateclass;

    static $associations = array(
        'track' => array(
            'class' => 'track',
            'idfield' => 'trackid'
        ),
        'userset' => array(
            'class' => 'userset',
            'idfield' => 'clusterid'
        )
    );

    /**
     * Constructor.
     *
     * @param int|object|array $data The data id of a data record or data
     * elements to load manually.
     *
     */
    /*
    function clustertrack($data = false) {
        parent::datarecord();

        $this->set_table(CLSTTRKTABLE);
        $this->add_property('id', 'int');
        $this->add_property('clusterid', 'int');
        $this->add_property('trackid', 'int');
        $this->add_property('autoenrol', 'int');
        $this->add_property('autounenrol', 'int');

        if (is_numeric($data)) {
            $this->data_load_record($data);
        } else if (is_array($data)) {
            $this->data_load_array($data);
        } else if (is_object($data)) {
            $this->data_load_array(get_object_vars($data));
        }
    }

    // defer loading of sub-data elements until requested
    function __get($name) {
        if ($name == 'cluster' && !empty($this->clusterid)) {
            $this->cluster = new cluster($this->clusterid);
            return $this->cluster;
        }
        if ($name == 'track' && !empty($this->trackid)) {
            $this->track = new track($this->trackid);
            return $this->track;
        }
        return null;
    }
*/

    /**
     * Associates a cluster with a track.
     */
    public static function associate($cluster, $track, $autounenrol=true, $autoenrol=true) {
        global $DB;

        // make sure we don't double-associate
        if ($DB->record_exists(self::TABLE, array('clusterid' => $cluster,
                                                  'trackid'   => $track)))
        {
            return;
        }

        $record = new clustertrack();
        $record->clusterid = $cluster;
        $record->trackid = $track;
        $record->autoenrol = $autoenrol;
        $record->autounenrol = $autounenrol;
        $record->save();

        // Enrol all users in the cluster into track.
        $sql = 'SELECT uc.*
                FROM {' . clusterassignment::TABLE . '} as uc
                JOIN {' . user::TABLE . '} as u
                ON uc.userid = u.id
                WHERE uc.clusterid = ? AND uc.autoenrol = 1
                ORDER BY u.lastname';

        $params = array($cluster);
        $users = $DB->get_records_sql($sql, $params);

//        $users = $db->get_records(CLSTUSERTABLE, 'clusterid', $cluster);
        if ($users && !empty($autoenrol)) {
            foreach ($users as $user) {
                usertrack::enrol($user->userid, $track);
            }
        }

        events_trigger('crlm_cluster_track_associated', $record);

    }

    /**
     * Disassociates a cluster from a track.
     */
    public function delete() {

        //FIXME: is it correct to call parent::delete() ?
        //$return = $this->data_delete_record();

        if ($this->autounenrol) {
            // Unenrol all users in the cluster from the track (unless they are
            // in another cluster associated with the track and autoenrolled by
            // that cluster).  Only work on users that were autoenrolled in the
            // track by the cluster.

            // $filter selects all users enrolled in the track due to being in
            // a(nother) cluster associated with the track.  We will left-join
            // with it, and only select non-matching records.
            /* TODO: work out how this works with clusterassignment table :)
            $params = array();
            $filter = 'SELECT u.userid '
                . 'FROM {' . clusteruser::TABLE . '} u '
                . 'INNER JOIN {' . usertrack::TABLE . '} ut ON u.userid = ut.userid '
                . 'WHERE ut.trackid = :trackid AND u.autoenrol=\'1\'';
            $params['trackid'] = $this->trackid;

            $sql = 'SELECT usrtrk.id '
                . 'FROM {' . clusteruser::TABLE . '} cu '
                . 'INNER JOIN {' . ustertrack::TABLE . '} usrtrk ON cu.userid = usrtrk.userid AND usrtrk.trackid = \'' . $this->trackid . '\' '
                . 'LEFT OUTER JOIN (' . $filter . ') f ON f.userid = cu.userid '
                . 'WHERE cu.clusterid = :clusterid AND cu.autoenrol=\'1\' AND f.userid IS NULL';
            $params['clusterid'] = $this->clusterid;

            $usertracks = $this->_db->get_records_sql($sql, $params);

            if ($usertracks) {
                foreach ($usertracks as $usertrack) {
                    $ut = new usertrack($usertrack->id);
                    $ut->unenrol();
                }
            }*/
        }

        //return $return;
        parent::delete();
    }

    /// collection functions. (These may be able to replaced by a generic container/listing class)

    /**
     * Get a list of the clusters assigned to this track.
     *
     * @uses            $CURMAN
     * @param  int      $trackid            The track id
     * @param  int      $parent_cluster_id  Cluster that must be the parent of track's clusters
     * @param  int      $startrec           The index of the record to start with
     * @param  int      $perpage            How many records to include
     * @param  array                        The appropriate cluster records
     */
    public static function get_clusters($trackid = 0, $parent_cluster_id = 0, $sort = 'name', $dir = 'ASC', $startrec = 0, $perpage = 0) {
        global $DB;

        if (empty($DB)) {
            return NULL;
        }

        //require plugin code if enabled
        // TODO: where will this be found?
        $display_priority_enabled = in_array('cluster_display_priority', get_list_of_plugins('curriculum/plugins'));
        if($display_priority_enabled) {
            require_once elispm::file('/plugins/cluster_display_priority/lib.php');
        }

        $select  = 'SELECT clsttrk.id, clsttrk.clusterid, clst.name, clst.display, clsttrk.autoenrol ';
        $tables  = 'FROM {' . self::TABLE . '} clsttrk ';
        $join    = 'LEFT JOIN {' . userset::TABLE . '} clst '.
                   'ON clst.id = clsttrk.clusterid ';

        //handle empty sort case
        if(empty($sort)) {
            $sort = 'name';
            $dir = 'ASC';
        }

        //get the fields we are sorting
        $sort_fields = explode(',', $sort);

        //convert the fields into clauses
        $sort_clauses = array();
        foreach($sort_fields as $key => $value) {
            $new_value = trim($value);
            if($display_priority_enabled && $new_value == 'priority') {
                $sort_clauses[$key] = $new_value . ' DESC';
            } else {
                $sort_clauses[$key] = $new_value . ' ' . $dir;
            }
        }

        //determine if we are handling the priority field for ordering
        if($display_priority_enabled && in_array('priority', $sort_fields)) {
            cluster_display_priority_append_sort_data('clst.id', $select, $join);
        }

        $params = array();
        $where   = 'WHERE clsttrk.trackid = :trackid ';
        $params['trackid'] = $trackid;
        if(!empty($parent_cluster_id)) {
            $where .= " AND clst.parent = :parent_cluster_id ";
            $params['parent_cluster_id'] = $parent_cluster_id;
        }
        $group   = 'GROUP BY clsttrk.id ';

        $sort_clause = 'ORDER BY ' . implode($sort_clauses, ', ') . ' ';

        $sql = $select.$tables.$join.$where.$group.$sort_clause;

        return $DB->get_records_sql($sql, $params, $startrec, $perpage);
    }

    /**
     * Calculates the number of clusters associated to the provided track
     *
     * @param   int  $trackid            The track to check associations for
     * @param   int  $parent_cluster_id  Cluster that must be the parent of track's clusters
     * @return                           The number of associated records
     */
    public static function count_clusters($trackid = 0, $parent_cluster_id = 0) {
        global $DB;

        if (empty($DB)) {
            return 0;
        }

        $params = array();
        $select  = 'SELECT COUNT(*) ';
        $tables  = 'FROM {' . self::TABLE . '} clsttrk ';
        $join    = 'LEFT JOIN {' . userset::TABLE . '} clst '.
                   'ON clst.id = clsttrk.clusterid ';
        $where   = 'WHERE clsttrk.trackid = :trackid ';
        $params['trackid'] = $trackid;
        if(!empty($parent_cluster_id)) {
            $where .= " AND clst.parent = :parent_cluster_id ";
            $params['parent_cluster_id'] = $parent_cluster_id;
        }
        $sort    = 'ORDER BY clst.name ASC ';

        $sql = $select.$tables.$join.$where.$sort;

        return $DB->count_records_sql($sql, $params);
    }


    /**
     * Get a list of the tracks assigned to this cluster.
     *
     * @uses $CURMAN
     * @param int $clusterid The cluster id.
     */
    public static function get_tracks($clusterid = 0) {
        global $DB;

        if (empty($DB)) {
            return NULL;
        }

        $select  = 'SELECT clsttrk.id, clsttrk.trackid, trk.idnumber, trk.name, trk.description, trk.startdate, trk.enddate, clsttrk.autoenrol ';
        $tables  = 'FROM {' . self::TABLE . '} clsttrk ';
        $join    = 'LEFT JOIN {' . track::TABLE . '} trk '.
          'ON trk.id = clsttrk.trackid ';
        $where   = 'WHERE clsttrk.clusterid = ? ';
        $sort    = 'ORDER BY trk.idnumber ASC ';
        $params = array($clusterid);

        $sql = $select.$tables.$join.$where.$sort;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Updates the autoenrol flag for a particular cluster-track association
     *
     * @param   int     $association_id  The id of the appropriate association record
     * @param   int     $autoenrol       The new autoenrol value
     *
     * @return  object                   The updated record
     */
	public static function update_autoenrol($association_id, $autoenrol) {
	    global $DB;

	    $old_autoenrol = $DB->get_field(self::TABLE, 'autoenrol', array('id' => $association_id));

        //update the flag on the association record
	    $update_record = new stdClass;
	    $update_record->id = $association_id;
	    $update_record->autoenrol = $autoenrol;
	    $result = $DB->update_record(self::TABLE, $update_record);

        if(!empty($autoenrol) and
           empty($old_autoenrol) and
           $cluster = $DB->get_field(self::TABLE, 'clusterid', array('id' => $association_id)) and
           $track = $DB->get_field(self::TABLE, 'trackid', array('id' => $association_id))) {
            //Enrol all users in the cluster into track.
            $sql = 'SELECT uc.*
                    FROM {' . clusterassignment::TABLE . '} as uc
                    JOIN {' . user::TABLE . '} as u
                    ON uc.userid = u.id
                    WHERE uc.clusterid = ? AND uc.autoenrol = 1
                    ORDER BY u.lastname';
            $params = array($cluster);

            $users = $db->get_records_sql($sql, $params);

            if ($users) {
                foreach ($users as $user) {
                    usertrack::enrol($user->userid, $track);
                }
            }
        }

        return $result;
	}
}
