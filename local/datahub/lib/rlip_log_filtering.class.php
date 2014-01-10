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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot.'/local/datahub/form/rlip_log_filter_forms.php');
require_once($CFG->dirroot.'/user/filters/lib.php');

/**
 * "Simple select" filter that allows for operations to be specified in its
 * data, with options like "= 1" or "> 0"
 */
class rlip_log_filter_operationselect extends user_filter_simpleselect {
    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array sql string and $params
     */
    function get_sql_filter($data) {
        static $counter = 0;
        $name = 'ex_rlip_lfos'. $counter++;

        $value = $data['value'];
        $params = array();
        $field = $this->_field;
        if ($value == '') {
            return '';
        }

        list($operation, $data_value) = explode(' ', $value);
        return array("$field $operation :{$name}", array($name => $data_value));
    }
}

/**
 * Filtering class used when viewing database logs, similar to Moodle's
 * user_filtering
 */
class rlip_log_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;

    /**
     * Constructor
     */
    function rlip_log_filtering() {
        global $SESSION;

        if (!isset($SESSION->rlip_log_filtering)) {
            $SESSION->rlip_log_filtering = array();
        }

        $fieldnames = array('tasktype' => 0,
                            'execution' => 0,
                            'actualstarttime' => 0
                            );

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // first the new filter form
        $this->_addform = new rlip_log_add_filter_form(null, array('fields'=>$this->_fields));
        if ($adddata = $this->_addform->get_data()) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->rlip_log_filtering)) {
                    $SESSION->rlip_log_filtering[$fname] = array();
                }
                $SESSION->rlip_log_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new rlip_log_add_filter_form(null, array('fields'=>$this->_fields));
        }

        // now the active filters
        $this->_activeform = new rlip_log_active_filter_form(null, array('fields'=>$this->_fields));
        if ($adddata = $this->_activeform->get_data()) {
            if (!empty($adddata->removeall)) {
                $SESSION->rlip_log_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->rlip_log_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->rlip_log_filtering[$fname])) {
                        unset($SESSION->rlip_log_filtering[$fname]);
                    }
                }
            }
            // clear+reload the form
            $_POST = array();
            $this->_activeform = new rlip_log_active_filter_form(null, array('fields'=>$this->_fields));
        }
    }

    /**
     * Creates known user filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE;

        switch ($fieldname) {
            case 'tasktype':
                $display_string = get_string('logtasktype', 'local_datahub');
                $options = array(0 => get_string('import', 'local_datahub'),
                                 1 => get_string('export', 'local_datahub'));
                return new user_filter_simpleselect('tasktype', $display_string, $advanced, 'export', $options);
            case 'execution':
                $display_string = get_string('logexecution', 'local_datahub');
                $options = array('> 0' => get_string('automatic', 'local_datahub'),
                                 '= 0' => get_string('manual', 'local_datahub'));
                return new rlip_log_filter_operationselect('execution', $display_string, $advanced,
                                                           'targetstarttime', $options);
            case 'actualstarttime':
                $display_string = get_string('logstart', 'local_datahub');
                return new user_filter_date('actualstartttime', $display_string, $advanced, 'starttime');
            default:
                return null;
        }
    }

    /**
     * Print the add filter form.
     */
    function display_add() {
        $this->_addform->display();
    }

    /**
     * Print the active filter form.
     */
    function display_active() {
        $this->_activeform->display();
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array named params (recommended prefix ex)
     * @return array sql string and $params
     */
    function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        if (!empty($SESSION->rlip_log_filtering)) {
            foreach ($SESSION->rlip_log_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // filter not used
                }
                $field = $this->_fields[$fname];
                foreach($datas as $i=>$data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }
}
