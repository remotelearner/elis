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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemorywithname.class.php');
require_once($CFG->dirroot.'/local/datahub/importplugins/version1elis/rlip_import_version1elis_fslogger.class.php');

/**
 * Class that fetches import files for the course import
 */
class rlipimport_version1elis_importprovider_mock extends rlip_importprovider {
    /**
     * @var array Fixed data to use as import data.
     */
    public $data;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        // Turn an associative array into rows of data.
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }

    /**
     * Valid fslogger required for phpunit tests
     * @param string  $plugin
     * @param  string $entity  the entity type
     * @param boolean $manual  Set to true if a manual run
     * @param integer $starttime the time used in the filename
     * @return object The fslogger instance
     */
    public function get_fslogger($plugin, $entity = '', $manual = false, $starttime = 0) {
        $fileplugin = rlip_fileplugin_factory::factory('/dev/null', null, true);
        $entity = '';
        return rlip_fslogger_factory::factory($plugin, $fileplugin, $entity);
    }
}

/**
 * Mock provider that specifies a filename and writes to a real log file
 */
class rlipimport_version1elis_importprovider_withname_mock extends rlip_importprovider {
    /**
     * @var array Fixed data to use as import data.
     */
    public $data;

    /**
     * @var string Name of .csv file.
     */
    public $importfilename;

    /**
     * Constructor
     *
     * @param array  $data           Fixed file contents
     * @param string $importfilename Name of '.csv' file
     */
    public function __construct($data, $importfilename = null) {
        $this->data = $data;
        $this->importfilename = $importfilename;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity, $name = '') {
        // Turn an associative array into rows of data.
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemorywithname($rows, $name);
    }
}

class rlipimport_version1elis_importprovider_multi_mock extends rlip_importprovider {
    /**
     * @var array Fixed data to use as import data.
     */
    public $data;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        // Turn an associative array into rows of data.
        $rows = array();
        $rows[] = array();
        $datum = reset($this->data);
        foreach (array_keys($datum) as $key) {
            $rows[0][] = $key;
        }

        // Iterate through each user.
        foreach ($this->data as $datum) {
            $index = count($rows);

            // Turn an associative array into rows of data.
            $rows[] = array();
            foreach (array_values($datum) as $value) {
                $rows[$index][] = $value;
            }
        }

        return new rlip_fileplugin_readmemory($rows);
    }

    /**
     * Valid fslogger required for phpunit tests
     * @param string  $plugin
     * @param  string $entity  the entity type
     * @param boolean $manual  Set to true if a manual run
     * @param integer $starttime the time used in the filename
     * @return object The fslogger instance
     */
    public function get_fslogger($plugin, $entity = '', $manual = false, $starttime = 0) {
        $fileplugin = rlip_fileplugin_factory::factory('/dev/null', null, true);
        $entity = '';
        return rlip_fslogger_factory::factory($plugin, $fileplugin, $entity);
    }
}

/**
 * Class that fetches import files for the user import
 */
class rlipimport_version1elis_importprovider_fsloguser extends rlipimport_version1elis_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }
        if (empty($this->importfilename)) {
            $this->importfilename = 'user.csv';
        }
        return parent::get_import_file($entity, $this->importfilename);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlipimport_version1elis_importprovider_fslogcourse extends rlipimport_version1elis_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        if (empty($this->importfilename)) {
            $this->importfilename = 'course.csv';
        }
        return parent::get_import_file($entity, $this->importfilename);
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlipimport_version1elis_importprovider_fslogenrolment extends rlipimport_version1elis_importprovider_withname_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }
        if (empty($this->importfilename)) {
            $this->importfilename = 'enrolment.csv';
        }
        return parent::get_import_file($entity, $this->importfilename);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlipimport_version1elis_importprovider_mockcourse extends rlipimport_version1elis_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the enrolment import
 */
class rlipimport_version1elis_importprovider_mockenrolment extends rlipimport_version1elis_importprovider_mock {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the user import
 */
class rlipimport_version1elis_importprovider_mockuser extends rlipimport_version1elis_importprovider_mock {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the program import
 */
class rlipimport_version1elis_importprovider_mockclass extends rlipimport_version1elis_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the track import
 */
class rlipimport_version1elis_importprovider_mocktrack extends rlipimport_version1elis_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the program import
 */
class rlipimport_version1elis_importprovider_mockprogram extends rlipimport_version1elis_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the userset import
 */
class rlipimport_version1elis_importprovider_mockuserset extends rlipimport_version1elis_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class for capturing failure messages
 *
 */
class capture_fslogger extends rlip_import_version1elis_fslogger {
    public $message;

    /**
     * Log a failure message to the log file, and potentially the screen
     *
     * @param string $message The message to long
     * @param int $timestamp The timestamp to associate the message with, or 0 for the current time
     * @param string $filename The name of the import / export file we are reporting on
     * @param int $entitydescriptor A descriptor of which entity from an import file we are handling, if applicable.
     * @param Object $record Imported data
     * @param string $type Type of import
     */
    public function log_failure($message, $timestamp = 0, $filename = null, $entitydescriptor = null, $record = null,
                                $type = null) {
        if (!empty($record) && !empty($type)) {
            $this->message = $this->general_validation_message($record, $message, $type);
        }

        return true;
    }
}