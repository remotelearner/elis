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

require_once dirname(__FILE__) . '/../../../test_config.php';
require_once $CFG->dirroot . '/elis/core/lib/data/data_object.class.php';

class test_data_object extends elis_data_object {
    static $table_name = 'test';

    /**
     * Some name
     * @var string
     * @length 255
     */
    protected $_dbfield_name;
}

class data_objectTest extends PHPUnit_Framework_TestCase {
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
    public function testBaseConstructor($init, $expected) {
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
    public function testDerivedConstructor($init, $expectedId, $expectedName) {
        $dataobj = new test_data_object($init);
        $this->assertEquals($dataobj->id, $expectedId);
        $this->assertEquals($dataobj->name, $expectedName);
    }

    /**
     * Test the isset and unset magic methods
     */
    public function testIsSetAndUnSet() {
        $dataobj = new elis_data_object(array('id' => 2));
        $this->assertFalse(isset($dataobj->notafield));
        $this->assertTrue(isset($dataobj->id));
        unset($dataobj->id);
        $this->assertFalse(isset($dataobj->id));
    }

    /**
     * Test the get and set magic methods
     */
    public function testGetAndSet() {
        $dataobj = new elis_data_object();
        $this->assertEquals($dataobj->id, NULL);
        $dataobj->id = 3;
        $this->assertEquals($dataobj->id, 3);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    /*
    public function testIsSet2() {
        $dataobj = new elis_data_object();
        $dataobj->notafield;
    }
    */
}
