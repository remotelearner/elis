<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/core/lib/setup.php');
require_once(elis::lib('data/data_object.class.php'));
require_once(elis::lib('testlib.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

// Object classes for Moodle database records.  We only model the minimum
// needed for the tests in this file
class config_object extends elis_data_object {
    const TABLE = 'config';

    /**
     * Some name
     * @var string
     * @length 255
     */
    protected $_dbfield_name;
    protected $_dbfield_value;
}

class user_object extends elis_data_object {
    const TABLE = 'user';

    public static $associations = array(
        'role_assignments' => array(
            'class' => 'role_assignment_object',
            'foreignidfield' => 'userid',
        ),
    );
}

class role_assignment_object extends elis_data_object {
    const TABLE = 'role_assignments';

    public static $associations = array(
        'user' => array(
            'class' => 'user_object',
            'idfield' => 'userid',
        ),
    );

    protected $_dbfield_userid;
    protected $_dbfield_roleid;
    protected $_dbfield_contextid;
}

class data_objectTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobalsBlacklist = array('DB');

    public function baseConstructorProvider() {
        $obj = new stdClass;
        $obj->id = 1;

        return array(array($obj, 1), // initialize from an object
                     array(array('id' => 2),
                           2), // initialize from an array
            );
    }

    /**
     * Test the constructor by initializing it and checking that the id field
     * is set correctly.
     *
     * @dataProvider baseConstructorProvider
     */
    public function testCanInitializeBaseClassFromArrayAndObject($init, $expected) {
        $dataobj = new elis_data_object($init);
        $this->assertEquals($dataobj->id, $expected);
    }

    public function derivedConstructorProvider() {
        $obj = new stdClass;
        $obj->id = 1;
        $obj->name = 'foo';

        return array(array($obj, 1, 'foo'), // initialize from an object
                     array(array('id' => 2,
                                 'name' => 'bar'),
                           2, 'bar'), // initialize from an array
            );
    }

    /**
     * Test the derived class constructor
     *
     * @dataProvider derivedConstructorProvider
     */
    public function testCanInitializeDerivedClassFromArrayAndObject($init, $expectedid, $expectedname) {
        $dataobj = new config_object($init);
        $this->assertEquals($dataobj->id, $expectedid);
        $this->assertEquals($dataobj->name, $expectedname);
    }

    /**
     * Test the isset and unset magic methods
     */
    public function testCanTestAndUnsetFields() {
        $dataobj = new elis_data_object(array('id' => 2));
        $this->assertFalse(isset($dataobj->notafield));
        $this->assertTrue(isset($dataobj->id));
        unset($dataobj->id);
        $this->assertFalse(isset($dataobj->id));
    }

    /**
     * Test the get and set magic methods
     */
    public function testCanGetAndSetFields() {
        $dataobj = new elis_data_object();
        $this->assertEquals($dataobj->id, null);
        $dataobj->id = 3;
        $this->assertEquals($dataobj->id, 3);
    }

    /**
     * Test the find method
     */
    public function testCanFindRecords() {
        global $DB;
        require_once(elis::lib('data/data_filter.class.php'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        $configs = config_object::find(new field_filter('name', 'foo'), array(), 0, 0, $overlaydb);
        // should only find one record, with value foooo
        $this->assertEquals($configs->current()->value, 'foooo');
        $configs->next();
        $this->assertFalse($configs->valid());

        $configs = config_object::find(null, array('name' => 'ASC'), 0, 0, $overlaydb);
        // should find all three records, ordered by name
        $configs = $configs->to_array();
        $this->assertEquals(count($configs), 3);
        $config = current($configs);
        $this->assertEquals($config->name, 'bar');
        $config = next($configs);
        $this->assertEquals($config->name, 'baz');
        $config = next($configs);
        $this->assertEquals($config->name, 'foo');
        $config = next($configs);
        $this->assertEquals($config, false);

        $overlaydb->cleanup();
    }

    /**
     * Test the count method
     */
    public function testCanCountRecords() {
        global $DB;
        require_once(elis::lib('data/data_filter.class.php'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        $configs = config_object::find(new field_filter('name', 'foo'), array(), 0, 0, $overlaydb);

        // should only find one record
        $this->assertEquals(config_object::count(new field_filter('name', 'foo'), $overlaydb), 1);
        // should find all three records
        $this->assertEquals(config_object::count(null, $overlaydb), 3);

        $overlaydb->cleanup();
    }

    /**
     * Test the delete method
     */
    public function testCanDeleteRecords() {
        global $DB;
        require_once(elis::lib('data/data_filter.class.php'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        config_object::delete_records(new field_filter('name', 'foo'), $overlaydb);
        $this->assertEquals(config_object::count(new field_filter('name', 'foo'), $overlaydb), 0);
        $this->assertEquals(config_object::count(null, $overlaydb), 2);

        $overlaydb->cleanup();
    }

    /**
     * Test the exists method
     */
    public function testCanCheckRecordsExist() {
        global $DB;
        require_once(elis::lib('data/data_filter.class.php'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        $this->assertTrue(config_object::exists(new field_filter('name', 'foo'), $overlaydb));
        $this->assertFalse(config_object::exists(new field_filter('name', 'fooo'), $overlaydb));

        $overlaydb->cleanup();
    }

    /**
     * Test loading from the database by record ID
     */
    public function testCanLoadRecordsById() {
        global $DB;
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        $config = new config_object(1, null, array(), false, array(), $overlaydb);
        $this->assertEquals($config->name, 'foo');
        $this->assertEquals($config->value, 'foooo');

        $overlaydb->cleanup();
    }

    /**
     * Test the save method
     */
    public function testCanSaveRecords() {
        global $DB;

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        // create a new record
        $config = new config_object(false, null, array(), false, array(), $overlaydb);
        $config->name = 'foo';
        $config->value = 'foovalue';
        $config->save();

        $config = config_object::find(null, array(), 0, 0, $overlaydb);
        $config = $config->to_array();
        $this->assertEquals(count($config), 1);
        $config = current($config);
        $this->assertEquals($config->name, 'foo');
        $this->assertEquals($config->value, 'foovalue');

        // modify an existing record
        $config->value = 'newfoovalue';
        $config->save();

        $config = config_object::find(null, array(), 0, 0, $overlaydb);
        $config = $config->to_array();
        $this->assertEquals(count($config), 1);
        $config = current($config);
        $this->assertEquals($config->name, 'foo');
        $this->assertEquals($config->value, 'newfoovalue');

        $overlaydb->cleanup();
    }

    public function testCanDeleteASingleRecord() {
        global $DB;
        require_once(elis::lib('data/data_filter.class.php'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('config', elis::component_file('core', 'phpunit/phpunit_data_object_test.csv'));

        $overlaydb = new overlay_database($DB, array('config' => 'moodle'));

        load_phpunit_data_set($dataset, true, $overlaydb);

        $config = config_object::find(new field_filter('name', 'foo'), array(), 0, 0, $overlaydb);
        $config = $config->current();
        $config->delete();

        $this->assertEquals(config_object::count(new field_filter('name', 'foo'), $overlaydb), 0);

        $overlaydb->cleanup();
    }

    public function testGetAssociatedRecords() {
        global $DB, $USER;

        $overlaydb = new overlay_database($DB, array('role_assignments' => 'moodle'));

        // get some random user
        $user = $DB->get_record('user', array(), '*', IGNORE_MULTIPLE);
        // get some random role
        $role = $DB->get_record('role', array(), '*', IGNORE_MULTIPLE);
        // add a role assignment
        $syscontext = get_context_instance(CONTEXT_SYSTEM);
        $ra = new stdClass;
        $ra->userid = $user->id;
        $ra->roleid = $role->id;
        $ra->contextid = $syscontext->id;
        $ra->timemodified = time();
        $ra->modifierid = $USER->id;
        $ra->id = $overlaydb->insert_record('role_assignments', $ra);

        $user = new user_object($user, null, array(), true, array(), $overlaydb);
        $this->assertEquals($user->count_role_assignments(), 1);

        $roleassignments = $user->role_assignments->to_array();
        $this->assertEquals(count($roleassignments), 1);
        $ra = current($roleassignments);
        $this->assertEquals($ra->userid, $user->id);
        $this->assertEquals($ra->roleid, $role->id);
        $this->assertEquals($ra->contextid, $syscontext->id);

        $this->assertEquals($ra->user->id, $user->id);

        $overlaydb->cleanup();
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    /*
    public function testCannotGetANonField() {
        $dataobj = new elis_data_object();
        $dataobj->notafield;
    }
    */
}
