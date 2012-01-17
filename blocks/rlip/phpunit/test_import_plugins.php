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

global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));

/**
 * Mock file plugin for testing closing of input files
 */
class rlip_fileplugin_inputclosed extends rlip_fileplugin_readmemory {
    //track whether the file was closed
    var $closed = false;

    /**
     * Close the file
     */
    function close() {
        $this->closed = true;
    }

    /**
     * Specifies whether this file was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    function closed() {
        return $this->closed;
    }
}

/**
 * Mock file plugin provider that supplies the import with our mock file plugin
 */
class rlip_importprovider_mock extends rlip_importprovider {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        $data = array(array('entity', 'action'),
                      array('sampleentity', 'sampleaction'));

        return new rlip_fileplugin_readmemory($data);
    }
}

/**
 * File plugin provider that supplies the import with our "test closed" file
 * plugin
 */
class rlip_importprovider_inputclosed extends rlip_importprovider_mock {
    //file plugin instance
    var $file;

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        $data = array(array('entity', 'action'),
                      array('sampleentity', 'sampleaction'));

        $this->file = new rlip_fileplugin_inputclosed($data);

        return $this->file;
    }

    /**
     * Specifies whether the file instantiated by this provider
     * was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    function closed() {
        //delegate to the file plugin
        return $this->file->closed();
    }
}

/**
 * Sample file plugin provider that works with multiple files
 */
class rlip_importprovider_multiple extends rlip_importprovider {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        $data = array();

        //set up a different "file" depending on the entity type
        switch ($entity) {
            case 'firstentity':
                $data = array(array('entity', 'action'),
                              array('firstentity', 'defaultaction'));
                break;
            default:
                $data = array(array('entity', 'action'),
                              array('secondentity', 'defaultaction'));
        }

        return new rlip_fileplugin_readmemory($data);
    }
}

/**
 * Class for testing the base import plugin class
 */
class importPluginTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Validate that plugin_supports works for entities
     */
    public function testImportPluginSupportsValidatesValidEntity() {
        $supports = plugin_supports('rlipimport', 'sample', 'sampleentity');

        $this->assertEquals($supports, array('sampleaction'));
    }
    
    /**
     * Validate that plugin_supports flags invalid entities
     */
    public function testImportPluginSupportsInvalidatesInvalidEntity() {
        $supports = plugin_supports('rlipimport', 'sample', 'bogusentity');

        $this->assertEquals($supports, NULL);
    }

    /**
     * Validate that plugin_supports works for valid entity-action combinations
     */
    public function testImportPluginSupportsValidatesValidEntityAndAction() {
        $supports = plugin_supports('rlipimport', 'sample', 'sampleentity_sampleaction');

        $this->assertEquals($supports, array('samplefield'));
    }

    /**
     * Validate that plugin_supports flags invalid actions for valid entities
     */
    public function testImportPluginSupportsInvalidatesValidEntityInvalidAction() {
        $supports = plugin_supports('rlimport', 'sample', 'sampleentity_bogusaction');

        $this->assertEquals($supports, NULL);
    }

    /**
     * Validate that plugin_supports flags invalid actions for invalid entities
     */
    public function testImportPluginSupportsInvalidatesInvalidEntityInvalidAction() {
        $supports = plugin_supports('rlimport', 'sample', 'bogusentity_bogusaction');

        $this->assertEquals($supports, NULL);
    }

    /**
     * Validate that the import process correctly delegates to the right action 
     */
    public function testValidInputTriggersAction() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/sample/sample.class.php');

        $provider = new rlip_importprovider_mock();

        $importplugin = new rlip_importplugin_sample($provider);
        $importplugin->run();

        $called = $importplugin->action_called();
        $this->assertEquals($called, true);
    }

    /**
     * Validate that the import process delegates file closing to the file
     * plugin
     */
    public function testImportClosesFile() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/sample/sample.class.php');

        $provider = new rlip_importprovider_inputclosed();

        $importplugin = new rlip_importplugin_sample($provider);
        $importplugin->run();

        $closed = $provider->closed();
        $this->assertEquals($closed, true);
    }

    /**
     * Validate that the import process supports plugins with multiple files
     */
    public function testImportPluginsSupportMultipleFiles() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/multiple/multiple.class.php');

        $provider = new rlip_importprovider_multiple();

        $importplugin = new rlip_importplugin_multiple($provider);
        $importplugin->run();

        $both_called = $importplugin->both_called();
        $this->assertEquals($both_called, true);
    }

    /**
     * Validates that the import process calls the "header read" hook
     */
    public function testImportTriggersHeaderReadHook() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/header/header.class.php');

        $provider = new rlip_importprovider_mock();

        $importplugin = new rlip_importplugin_header($provider);
        $importplugin->run();

        $this->assertEquals($importplugin->hook_called(), true);
    }
}