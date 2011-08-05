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
}
