<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
$file = get_plugin_directory('rlipimport', 'sample').'/sample.class.php';
require_once($file);

/**
 * Test plugin used to test a simple entity and action
 */
class rlip_importplugin_header extends rlip_importplugin_sample {
    //stores whether the "header read" hook was called
    var $hook_called = false;

    /**
     * Hook run after a file header is read
     *
     * @param string $entity   The type of entity
     * @param array  $header   The header record
     * @param string $filename ?
     */
    function header_read_hook($entity, $header, $filename) {
        $this->hook_called = true;
    }

    /**
     * Specifies whether the "header read" hook was called
     *
     * @return boolean true if it was called, otherwise false  
     */
    function hook_called() {
        return $this->hook_called;
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
