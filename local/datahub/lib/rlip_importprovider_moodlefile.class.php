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
 * Class that provides file plugins reading from the Moodle file API in a
 * generic way
 */
class rlip_importprovider_moodlefile extends rlip_importprovider {
    var $entity_types;
    var $fileids;

    /**
     * Constructor
     *
     * @param array $fieldids Array of file records' database ids
     * @param array $entity_types Array of strings representing the entity
     *                            types of import files
     */
    function __construct($entity_types, $fieldids) {
        $this->entity_types = $entity_types;
        $this->fileids = $fieldids;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

        foreach ($this->entity_types as $key => $value) {
            if ($entity == $value) {
                if ($this->fileids[$key] !== false) {
                    return rlip_fileplugin_factory::factory('', $this->fileids[$key]);
                }
            }
        }

        return false;
    }

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dblogger.class.php');

        //for now, this is only used in manual runs
        return new rlip_dblogger_import(true);
    }
}
