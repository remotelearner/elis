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
 * @package    dhexport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

/**
 * Dummy file plugin that ignores write actions
 */
class rlip_fileplugin_nowrite extends rlip_fileplugin_base {
    /**
     * Hook for opening the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        // Do nothing.
    }

    /**
     * Hook for reading one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        // Do nothing.
        return array();
    }

    /**
     * Hook for writing one entry to the file
     *
     * @param array $line The entry to write to the file
     */
    public function write($entry) {
        // Do nothing.
    }

    /**
     * Hook for closing the file
     */
    public function close() {
        // Do nothing.
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        // Bogus filename.
        return 'bogus';
    }
}
