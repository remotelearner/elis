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
require_once($CFG->dirroot . '/elis/core/lib/setup.php');
require_once(elis::lib('data/data_object.class.php'));
require_once(elis::lib('testlib.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

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
