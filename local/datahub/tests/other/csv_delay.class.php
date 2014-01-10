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
$file = get_plugin_directory('dhfile', 'csv').'/csv.class.php';
require_once($file);

/**
 * Class that delays reading import file and delays writing out an export file
 */
class rlip_fileplugin_csv_delay extends rlip_fileplugin_csv {
    private $readdelay = 3; // 3 sec delay before reads.
    private $writedelay = 3; // 3 sec delay before writes.

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    public function open($mode) {
        if ($mode == RLIP_FILE_WRITE) {
            return;
        }

        parent::open($mode);
    }

    /**
     * Delay, then read data in
     *
     * @return array The entry read
     */
    public function read() {
        if (!empty($this->readdelay)) {
            sleep($this->readdelay);
        }
        return parent::read();
    }

    /**
     * Delay, rather than actually write data out
     *
     * @param array $entry The entry to write to the file
     */
    public function write($entry) {
        if (!empty($this->writedelay)) {
            sleep($this->writedelay);
        }
        // Don't actually write anything.
    }

    /**
     * Close the file
     */
    public function close() {
        if (!empty($this->filepointer)) {
            fclose($this->filepointer);
        }
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        // Todo: implement?
        return 'filename';
    }
}