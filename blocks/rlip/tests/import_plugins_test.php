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
 * @package    block_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');

/**
 * Mock file plugin for testing closing of input files
 */
class rlip_fileplugin_inputclosed extends rlip_fileplugin_readmemory {
    // Track whether the file was closed.
    public $closed = false;

    /**
     * Close the file
     */
    public function close() {
        $this->closed = true;
    }

    /**
     * Specifies whether this file was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    public function closed() {
        return $this->closed;
    }
}

/**
 * Mock file plugin that never returns data
 */
class rlip_fileplugin_nodata extends rlip_fileplugin_readmemory {
    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        return false;
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
    public function get_import_file($entity) {
        $data = array(
                array('entity', 'action'),
                array('sampleentity', 'sampleaction')
        );

        return new rlip_fileplugin_readmemory($data);
    }
}

/**
 * File plugin provider that always returns false instead of an actual file plugin
 */
class rlip_importprovider_false extends rlip_importprovider {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        return false;
    }
}

/**
 * File plugin provider that supplies the import with our "test closed" file plugin.
 */
class rlip_importprovider_inputclosed extends rlip_importprovider_mock {
    // File plugin instance.
    public $file;

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        $data = array(
                array('entity', 'action'),
                array('sampleentity', 'sampleaction')
        );

        $this->file = new rlip_fileplugin_inputclosed($data);

        return $this->file;
    }

    /**
     * Specifies whether the file instantiated by this provider
     * was closed
     *
     * @return boolean true if the file was closed, otherwise false
     */
    public function closed() {
        // Delegate to the file plugin.
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
    public function get_import_file($entity) {
        $data = array();

        // Set up a different "file" depending on the entity type.
        switch ($entity) {
            case 'firstentity':
                $data = array(
                        array('entity', 'action'),
                        array('firstentity', 'defaultaction')
                );
                break;
            default:
                $data = array(
                        array('entity', 'action'),
                        array('secondentity', 'defaultaction')
                );
        }

        return new rlip_fileplugin_readmemory($data);
    }
}

/**
 * Mock file plugin provider that returns a data-less file plugin
 */
class rlip_importprovider_nodata extends rlip_importprovider {
    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    public function get_import_file($entity) {
        $data = array(
                array('entity', 'action'),
                array('sampleentity', 'sampleaction')
        );

        return new rlip_fileplugin_nodata($data);
    }
}

/**
 * Class for testing the base import plugin class
 * @group block_rlip
 */
class importplugin_testcase extends rlip_test {

    /**
     * Validate that plugin_supports works for entities
     */
    public function test_importpluginsupportsvalidatesvalidentity() {
        $supports = plugin_supports('rlipimport', 'sample', 'sampleentity');

        $this->assertEquals($supports, array('sampleaction'));
    }

    /**
     * Validate that plugin_supports flags invalid entities
     */
    public function test_importpluginsupportsinvalidatesinvalidentity() {
        $supports = plugin_supports('rlipimport', 'sample', 'bogusentity');

        $this->assertEquals($supports, null);
    }

    /**
     * Validate that plugin_supports works for valid entity-action combinations
     */
    public function test_importpluginsupportsvalidatesvalidentityandaction() {
        $supports = plugin_supports('rlipimport', 'sample', 'sampleentity_sampleaction');

        $this->assertEquals($supports, array('samplefield'));
    }

    /**
     * Validate that plugin_supports flags invalid actions for valid entities
     */
    public function test_importpluginsupportsinvalidatesvalidentityinvalidaction() {
        $supports = plugin_supports('rlimport', 'sample', 'sampleentity_bogusaction');

        $this->assertEquals($supports, null);
    }

    /**
     * Validate that plugin_supports flags invalid actions for invalid entities
     */
    public function test_importpluginsupportsinvalidatesinvalidentityinvalidaction() {
        $supports = plugin_supports('rlimport', 'sample', 'bogusentity_bogusaction');

        $this->assertEquals($supports, null);
    }

    /**
     * Validate that the import process correctly delegates to the right action
     */
    public function test_validinputtriggersaction() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'sample').'/sample.class.php';
        require_once($file);

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
    public function test_importclosesfile() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'sample').'/sample.class.php';
        require_once($file);

        $provider = new rlip_importprovider_inputclosed();

        $importplugin = new rlip_importplugin_sample($provider);
        $importplugin->run();

        $closed = $provider->closed();
        $this->assertEquals($closed, true);
    }

    /**
     * Validate that the import process supports plugins with multiple files
     */
    public function test_importpluginssupportmultiplefiles() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'multiple').'/multiple.class.php';
        require_once($file);

        $provider = new rlip_importprovider_multiple();

        $importplugin = new rlip_importplugin_multiple($provider);
        $importplugin->run();

        $bothcalled = $importplugin->both_called();
        $this->assertEquals($bothcalled, true);
    }

    /**
     * Validates that the import process calls the "header read" hook
     */
    public function test_importtriggersheaderreadhook() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'header').'/header.class.php';
        require_once($file);

        $provider = new rlip_importprovider_mock();

        $importplugin = new rlip_importplugin_header($provider);
        $importplugin->run();

        $this->assertEquals($importplugin->hook_called(), true);
    }

    /**
     * Validates that the file-system logging object provided for direct CSV
     * file input is in "scheduled" mode
     */
    public function test_csvimportproviderprovidesloggerinscheduledmode() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_csv.class.php');

        // Construct our provider.
        $provider = new rlip_importprovider_csv(array(), array());

        // Obtain its logging object.
        set_config('logfilelocation', 'bogus', 'rlipimport_version1');
        $entity = '';
        $manual = false;
        $fslogger = $provider->get_fslogger('rlipimport_version1', $entity, $manual);

        // Validation.
        $this->assertFalse($fslogger->get_manual());
        @rmdir($CFG->dataroot.DIRECTORY_SEPARATOR.'bogus');
    }

    /**
     * Validates that the file-system logging object provided for Moodle file
     * input is in "manual" mode
     */
    public function test_moodlefileimportproviderprovidesloggerinmanualmode() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importprovider_moodlefile.class.php');

        // Construct our provider.
        $provider = new rlip_importprovider_moodlefile(array(), array());

        // Obtain its logging object.
        set_config('logfilelocation', 'bogus', 'rlipimport_version1');
        $entity = '';
        $manual = true;
        $fslogger = $provider->get_fslogger('rlipimport_version1', $entity, $manual);

        // Validation.
        $this->assertTrue($fslogger->get_manual());
        @rmdir($CFG->dataroot.DIRECTORY_SEPARATOR.'bogus');
    }

    /**
     * Validate that the process_import_file method returns false when an invalid
     * entity is specified
     */
    public function test_processimportfilereturnsfalseforinvalidentity() {
        $provider = new rlip_importprovider_false();

        $importplugin = new rlip_importplugin_sample($provider);
        $importplugin->run();

        $result = $importplugin->process_import_file('bogusentity', 0, null);

        // Method should return false instead of null, types are important.
        $this->assertSame(false, $result);
    }

    /**
     * Validate that the process_import_file method returns false when and
     * import file is missing
     */
    public function test_processimportfilereturnsfalseformissingfile() {
        $provider = new rlip_importprovider_nodata();

        $importplugin = new rlip_importplugin_sample($provider);
        $importplugin->run();

        $result = $importplugin->process_import_file('bogusentity', 0, null);

        // Method should return false instead of null, types are important.
        $this->assertSame(false, $result);
    }
}
