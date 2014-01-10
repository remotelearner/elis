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

/**
 * Import provider that constructs a file plugin which delays on reading
 */
class rlip_importprovider_file_delay extends rlip_importprovider {
    /**
     * @var string The filename to usefor import
     */
    public $filename;

    /**
     * @var string The type of entity
     */
    public $entity;

    /**
     * Constructor
     *
     * @param string $filename The filename to usefor import
     * @param string $entity The type of entity
     */
    public function __construct($filename, $entity) {
        $this->filename = $filename;
        $this->entity = $entity;
    }

    /**
     * Hook for providing a file plugin for a particular import entity type.
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != $this->entity) {
            return false;
        }

        // Use a file plugin that delays on read.
        return new rlip_fileplugin_csv_delay($this->filename);
    }
}