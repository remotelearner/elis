<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elis::lib('data/data_object.class.php');

class coursetemplate extends elis_data_object {
    const TABLE = 'crlm_coursetemplate';

    static $associations = array(
        'course' => array(
            'class' => 'course',
            'idfield' => 'courseid'
        ),
    );

    protected $_dbfield_courseid;
    protected $_dbfield_location;
    protected $_dbfield_templateclass;

    protected function get_field_context_level() {
        return context_level_base::get_custom_context_level('course', 'elis_program');
    }

    // overriding method, parameter is now the course id
    function data_load_record($id) {
        if (is_string($id) && !is_numeric($id)) {
            /// $id can be a select string...
            $select = $id;
        } else {
            $select = 'courseid = ' . $id;
        }

        $record = $this->_db->get_record_select(self::TABLE, $select);
        if (empty($record)) {
            return false;
        }

        $fields = get_object_vars($record);

        foreach ($fields as $field => $value) {
            if (isset($this->$field)) {
                $this->$field = $value;
            }
        }

        $this->_dbloaded = true;

        return true;
    }

    /**
     * Data function to update the database record with the object contents.
     * timecreated/modified not used for this table
     *
     * @param $createnew boolean If true, and the record doesn't exist, creates a new one.
     * @return boolean Status of the operation.
     */
    /*
    function data_update_record($createnew = false) {
        if ($this->_dbloaded || !empty($this->id)) {
            $record = new stdClass();

            if (!empty($this->properties)) {
                foreach ($this->properties as $prop => $type) {
                    if (!isset($this->$prop)) {
                        continue;
                    }

                    if ($prop == 'timemodified') {
                        $record->$prop = time();
                    } else {
                        switch ($type) {
                            case 'int':
                                $record->$prop = $this->_db->clean_int($this->$prop);
                                break;

                            case 'string':
                                $record->$prop = $this->_db->clean_text($this->$prop);
                                break;

                            case 'html':
                                $record->$prop = $this->_db->clean_html($this->$prop);
                                break;
                        }
                    }
                }
            }

            if ($this->_db->update_record($this->table, $record)) {
                return true;
            } else if (!$createnew) {
                return false;
            }
        }

        if ($createnew) {
            return ($this->data_insert_record());
        } else {
            return false;
        }
    }
    */

    public function save() {
        $isnew = empty($this->id);

        parent::save();

        // TO-DO: put proper update code here

    }

	public static function delete_for_course($id) {
	    global $DB;
		return $DB->delete_records(coursetemplate::TABLE, array('courseid'=>$id));
	}

}
