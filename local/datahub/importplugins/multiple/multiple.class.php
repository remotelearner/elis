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

require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');

/**
 * Test plugin used to unit testing support for multiple files in one import
 * plugin
 */
class rlip_importplugin_multiple extends rlip_importplugin_base {
    //tracks whether the first entity's method was called
    var $firstcalled = false;
    //tracks whether the second entity's method was called
    var $secondcalled = false;

    /**
     * Delegate processing of an import line for entity type "firstentity"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function firstentity_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "firstentity_{$action}";
        return $this->$method($record);
    }

    /**
     * Test method for entity of "firstentity" and action of "defaultaction"
     *
     * @param object $record One record of import data
     *
     * @return boolean true on success, otherwise false
     */
    function firstentity_defaultaction($record) {
        //remember that this action was processed
        $this->firstcalled = true;

        return true;
    }

    /**
     * Delegate processing of an import line for entity type "secondentity"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied 
     *
     * @return boolean true on success, otherwise false
     */
    function secondentity_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "secondentity_{$action}";
        return $this->$method($record);
    }

    /**
     * Test method for entity of "secondentity" and action of "defaultaction"
     *
     * @param object $record One record of import data
     *
     * @return boolean true on success, otherwise false
     */
    function secondentity_defaultaction($record) {
        //remember that this action was processed
        $this->secondcalled = true;  

        return true;
    }

    /**
     * Specifies whether both entities' action methods were called
     *
     * @return boolean true if both were called, otherwise false
     */
    function both_called() {
        //only return true if both were called
        return $this->firstcalled && $this->secondcalled;
    }

    /**
     * Specifies the UI labels for the various import files supported by this
     * plugin
     *
     * @return array The string labels, in the order in which the
     *               associated [entity]_action methods are defined
     */
    function get_file_labels() {
        return array('First Entity',
                     'Second Entity');
    }

    /**
     * Specifies flag for indicating whether this plugin is actually available
     * on the current system, particularly for viewing in the UI and running
     * scheduled tasks
     */
    function is_available() {
        //this plugin is for testing only
        return false;
    }
}