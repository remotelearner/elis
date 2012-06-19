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
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Unit test for validating basic userset actions
 */
class elis_userset_import_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        return array(userset::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'context' => 'moodle');
    }

    /**
     * Validate that a userset can be created with a minimal set of fields specified
     */
    function test_create_elis_userset_import_with_minimal_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //run the import
        $this->run_core_userset_import(array(), true);

        //validation
        $data = array('name' => 'testusersetname',
                      'display' => '',
                      'parent' => 0);
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Validate that a userset can be created, setting all available fields
     */
    function test_create_elis_userset_import_with_all_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a parent userset
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        //run the import
        $data = array('name' => 'testusersetname',
                      'display' => 'testusersetdisplay',
                      'parent' => 'testparentusersetname');
        $this->run_core_userset_import($data, false);

        //validation
        $data['parent'] = $parent->id;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Data provider for the parent field
     *
     *  @return array Mapping of parent values to expected results in the database
     */
    function parent_provider() {
        return array(array('', 0),
                     array('top', 0),
                     array('testparentusersetname', 1));
    }

    /**
     * Validate the various behaviours of the parent field during userset creation
     *
     * @param string $input_value The parent value specified
     * @param int $db_value The expected parent value stored in the database
     * @dataProvider parent_provider
     */
    function test_create_elis_userset_respects_parent_field($input_value, $db_value) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));

        //set up a parent userset
        $parent = new userset(array('name' => 'testparentusersetname'));
        $parent->save();

        //run the import
        $data = array('name' => 'testusersetname',
                      'parent' => $input_value);
        $this->run_core_userset_import($data, false);

        //validation
        $data['parent'] = $db_value;
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));
    }

    /**
     * Helper function to get the core fields for a sample userset
     *
     * @return array The userset data
     */
    private function get_core_userset_data() {
        $data = array('action' => 'create',
                      'context' => 'cluster',
                      'name' => 'testusersetname');
        return $data;
    }

    /**
     * Helper function that runs the userset import for a sample userset
     *
     * @param array $extradata Extra fields to set for the new userset
     * @param boolean $use_default_data If true, use the default userset data,
     *                                  along with any data specifically provided
     */
    private function run_core_userset_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_userset_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockuserset($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }
}

/**
 * Class that fetches import files for the userset import
 */
class rlip_importprovider_mockuserset extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}