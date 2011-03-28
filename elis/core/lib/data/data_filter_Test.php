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
require_once $CFG->dirroot . '/elis/core/lib/data/data_filter.class.php';

class field_filterTest extends PHPUnit_Framework_TestCase {
    /**
     * test cases for field_filter
     */
    public function field_filterProvider() {
        $tests = array();

        // basic functionality
        $tests[] = array(array('foo', 'bar'),
                         array('where' => 'foo = ?',
                               'where_parameters' => array('bar')));

        // other comparison operator
        $tests[] = array(array('foo', 'bar', field_filter::NEQ),
                         array('where' => 'foo != ?',
                               'where_parameters' => array('bar')));

        // checking for null
        $tests[] = array(array('foo', null),
                         array('where' => 'foo IS NULL',
                               'where_parameters' => array()));

        // checking for null
        $tests[] = array(array('foo', null, field_filter::NEQ),
                         array('where' => 'foo IS NOT NULL',
                               'where_parameters' => array()));

        return $tests;
    }

    /**
     * @dataProvider field_filterProvider
     */
    public function testFieldFilter($init, $expected) {
        $filter = isset($init[2]) ? new field_filter($init[0], $init[1], $init[2]) : new field_filter($init[0], $init[1]);

        $this->assertEquals($filter->get_sql(), $expected);
        $this->assertEquals($filter->get_sql(true), $expected);
        $expected['where'] = 'x.' . $expected['where'];
        $this->assertEquals($filter->get_sql(false, 'x'), $expected);
        $this->assertEquals($filter->get_sql(true, 'x'), $expected);
    }
}
