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

require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_exportplugin.class.php');

/**
 * PM class instance grade export compatible with the original PM grade
 * export for Moodle 1.9
 */
class rlip_exportplugin_version1elis extends rlip_exportplugin_base {
    /**
     * Perform initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    function init($targetstarttime = 0, $lastruntime = 0) {
        //TODO: implement
    }

    /**
     * Specify whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    function has_next() {
        //TODO: implement
        return false;
    }

    /**
     * Hook for export the next data record in-place
     *
     * @return array The next record to be exported
     */
    function next() {
        //TODO: implement
        return array();
    }

    /**
     * Perform cleanup that should
     * be done at the end of the export
     */
    function close() {
        //TODO: implement
    }
}