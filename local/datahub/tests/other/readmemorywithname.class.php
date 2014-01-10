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

require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

class rlip_fileplugin_readmemorywithname extends rlip_fileplugin_base {
    /**
     * @var int Current file position.
     */
    public $index;

    /**
     * @var array File data.
     */
    public $data;

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     */
    public function __construct($data, $filename = '', $fileid = null, $sendtobrowser = false) {
        parent::__construct($filename, $fileid, $sendtobrowser);

        $this->index = 0;
        $this->data = $data;
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->index = 0;
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        if ($this->index < count($this->data)) {
            // More lines to read, fetch next one.
            $result = $this->data[$this->index];
            // Move "line pointer".
            $this->index++;
            return $result;
        }

        // Out of lines.
        return false;
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        // Nothing to do.
    }

    /**
     * Close the file
     */
    public function close() {
        // Nothing to do.
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name.
     */
    public function get_filename($withpath = false) {
        // Physical file, so obtain filename from full path.
        if ($withpath) {
            return $this->filename;
        }
        $parts = explode('/', $this->filename);
        $count = count($parts);
        return $parts[$count - 1];
    }
}
