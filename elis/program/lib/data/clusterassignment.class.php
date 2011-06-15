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
require_once elis::lib('data/data_object.class.php');
require_once elispm::lib('data/userset.class.php');

//require_once(CURMAN_DIRLOCATION . '/lib/datarecord.class.php');
//require_once(CURMAN_DIRLOCATION . '/lib/cluster.class.php');

//define ('CLSTASSTABLE', 'crlm_cluster_assignments');

// TODO: is this to be clusterstudent??
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

	/**
	 * Constructor.
	 *
	 * @param $clusterdata int/object/array The data id of a data record or data elements to load manually.
	 *
	 */
    /*
	function clusterassignment($data=false) {
		parent::datarecord();

		$this->set_table(CLSTASSTABLE);
		$this->add_property('id', 'int');
		$this->add_property('clusterid', 'int');
		$this->add_property('userid', 'int');
		$this->add_property('plugin', 'string');
		$this->add_property('autoenrol', 'int');
                $this->add_property('leader', 'int');

		if (is_numeric($data)) {
			$this->data_load_record($data);
		} else if (is_array($data)) {
			$this->data_load_array($data);
		} else if (is_object($data)) {
			$this->data_load_array(get_object_vars($data));
		}
	}*/

	public function delete() {
        $status = parent::delete();
        cluster::cluster_update_assignments($this->clusterid, $this->userid);
        return $status;
	}

	/*public static function delete_for_user($id) {
    	global $CURMAN;

    	$status = $CURMAN->db->delete_records(CLSTASSTABLE, 'userid', $id);
    	cluster::cluster_update_assignments(null, $id);
    	return $status;
    }

	public static function delete_for_cluster($id) {
    	global $CURMAN;

    	$status = $CURMAN->db->delete_records(CLSTASSTABLE, 'clusterid', $id);
    	cluster::cluster_update_assignments($id, null);
    	return $status;
    }*/
}
?>