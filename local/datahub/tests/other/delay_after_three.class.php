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
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');

/**
 * File plugin that delays for two seconds between the third or fourth entry read.
 */
class rlip_fileplugin_delay_after_three extends rlip_fileplugin_base {
    /**
     * @var array Fixed data to use for testing purposes
     */
    public $rows;

    /**
     * @var int Current line.
     */
    public $line;

    /**
     * Constructor
     *
     * @param array $rows Fixed data to use for testing purposes
     */
    public function __construct($rows) {
        $this->rows = $rows;
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        $this->line = 0;
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        if ($this->line < count($this->rows)) {
            // Still have data to read.

            // Get the next row.
            $result = $this->rows[$this->line];
            // Increment position.
            $this->line++;

            if ($this->line == 4) {
                // Delay for 2 seconds between third and fourth read.
                sleep(2);
            }

            return $result;
        }

        return false;
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
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
     * @return string The file name.
     */
    public function get_filename($withpath = false) {
        return 'bogus';
    }
}

/**
 * Import provider that provides a delaying file reading mechanism
 */
class rlip_importprovider_delay_after_three_users extends rlip_importprovider {
    /**
     * Constructor
     *
     * @param array $rows Fixed data to use for testing purposes
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Provide a file plugin for a particular import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'user') {
            // This class only cares about users for now.
            return false;
        }

        return new rlip_fileplugin_delay_after_three($this->data);
    }
}