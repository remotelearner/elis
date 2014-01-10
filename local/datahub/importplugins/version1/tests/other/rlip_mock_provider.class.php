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
 * @package    dhimport_version1
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
require_once($CFG->dirroot.'/local/datahub/tests/other/csv_delay.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/file_delay.class.php');

/**
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_mock extends rlip_importprovider {
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
     * @param  string $entity    the entity type
     * @param boolean $manual    Set to true if a manual run
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
class rlipimport_version1_importprovider_withname_mock extends rlip_importprovider {
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

class rlipimport_version1_importprovider_multi_mock extends rlip_importprovider {
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
     * @param  string $entity    the entity type
     * @param boolean $manual    Set to true if a manual run
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
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_mockcourse extends rlipimport_version1_importprovider_mock {

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
class rlipimport_version1_importprovider_mockenrolment extends rlipimport_version1_importprovider_mock {

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
class rlipimport_version1_importprovider_mockuser extends rlipimport_version1_importprovider_mock {

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
 * Class that fetches import files for the user import
 */
class rlipimport_version1_importprovider_createorupdateuser extends rlipimport_version1_importprovider_mock {

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
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_createorupdatecourse extends rlipimport_version1_importprovider_mock {

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
class rlipimport_version1_importprovider_createorupdateenrolment extends rlipimport_version1_importprovider_mock {

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
class rlipimport_version1_importprovider_emptyuser extends rlipimport_version1_importprovider_multi_mock {

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
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_emptycourse extends rlipimport_version1_importprovider_multi_mock {

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
class rlipimport_version1_importprovider_emptyenrolment extends rlipimport_version1_importprovider_multi_mock {

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
class rlipimport_version1_importprovider_loguser extends rlipimport_version1_importprovider_mock {

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
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_logcourse extends rlipimport_version1_importprovider_mock {

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
 * Class that fetches import files for the course import
 */
class rlipimport_version1_importprovider_logenrolment extends rlipimport_version1_importprovider_mock {

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
 * File plugin that reads from memory and reports a dynamic filename
 */
class rlip_fileplugin_readmemory_dynamic extends rlip_fileplugin_readmemory {

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     * @param string $filename The name of the file to report
     */
    public function __construct($rows, $filename) {
        parent::__construct($rows);
        $this->filename = $filename;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name
     */
    public function get_filename($withpath = false) {
        return $this->filename;
    }
}

/**
 * Import provider that allow for multiple user records to be passed to the
 * import plugin
 */
class rlipimport_version1_importprovider_multiuser extends rlipimport_version1_importprovider_multi_mock {

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
 * Import provider that allows for user processing and specifies a dynamic
 * filename to the file plugin
 */
class rlipimport_version1_importprovider_loguser_dynamic extends rlipimport_version1_importprovider_loguser {
    public $data;
    public $filename;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     * @param string $filename The name of the file to report
     */
    public function __construct($data, $filename) {
        $this->data = $data;
        $this->filename = $filename;
    }

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

        return new rlip_fileplugin_readmemory_dynamic($rows, $this->filename);
    }
}

class rlipimport_version1_importprovider_file extends rlip_importprovider {
    public $filename;

    public function __construct($filename) {
        $this->filename = $filename;
    }

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

        return rlip_fileplugin_factory::factory($this->filename);
    }
}

class rlipimport_version1_importprovider_manual_delay extends rlip_importprovider_file_delay {

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    public function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dblogger.class.php');

        // Force MANUAL.
        return new rlip_dblogger_import(true);
    }
}