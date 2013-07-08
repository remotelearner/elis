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
 * @package    rlip
 * @subpackage importplugins/version1/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) .'/../../../../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemorywithname.class.php');

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_mock extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        //turn an associative array into rows of data
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
    function get_fslogger($plugin, $entity = '', $manual = false, $starttime = 0) {
        $fileplugin = rlip_fileplugin_factory::factory('/dev/null', NULL, true);
        $entity = '';
        return rlip_fslogger_factory::factory($plugin, $fileplugin, $entity);
    }
}

/**
 * Mock provider that specifies a filename and writes to a real log file
 */
class rlip_importprovider_withname_mock extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity, $name = '') {
        //turn an associative array into rows of data
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

class rlip_importprovider_multi_mock extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        $datum = reset($this->data);
        foreach (array_keys($datum) as $key) {
            $rows[0][] = $key;
        }

        //iterate through each user
        foreach ($this->data as $datum) {
            $index = count($rows);

            //turn an associative array into rows of data
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
    function get_fslogger($plugin, $entity = '', $manual = false, $starttime = 0) {
        $fileplugin = rlip_fileplugin_factory::factory('/dev/null', NULL, true);
        $entity = '';
        return rlip_fslogger_factory::factory($plugin, $fileplugin, $entity);
    }
}

