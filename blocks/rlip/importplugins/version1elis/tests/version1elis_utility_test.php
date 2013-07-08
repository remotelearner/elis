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
 * @package    blocks/rlip
 * @subpackage importplugins/version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
$file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
require_once($file);

/**
 * Class for testing utility methods in the version 1 import plugin
 */
class version1elis_utility_test extends PHPUnit_Framework_TestCase {

    /**
     * Data Provider for testing parse_date()
     * Data format: array(inputdate string, oldformats bool, includetime bool, minyear int, maxyear int, result mixed)
     *
     * @return array testing data
     */
    public function pddataprovider() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        $bogusobj = new stdClass;
        $bogusobj->name = 'bogus';
        return array(
                array('Jan/02/2010', true, false, 0, 0, rlip_timestamp(0, 0, 0, 1, 2, 2010)),
                array('01/02/2010', true, false, 0, 0, rlip_timestamp(0, 0, 0, 1, 2, 2010)),
                array('Jan/02', true, false, 0, 0, false),
                array('Janx/02/2010', true, false, 0, 0, false),
                array('13/02/2010', true, false, 0, 0, false),
                array('Jan/bogusday/2010', true, false, 0, 0, false),
                array('Jan/02/bogusyear', true, false, 0, 0, false),
                array('Feb/31/2010', true, false, 0, 0, false),
                array('JAN/02/2010 9:45', true, false, 0, 0, rlip_timestamp(0, 0, 0, 1, 2, 2010)),
                array('jan/02/2010 9:45', true, true, 0, 0, rlip_timestamp(9, 45, 0, 1, 2, 2010)),
                array('jan/02/2010 9:60', true, true, 0, 0, false),
                array('jAN/02/2010:9:45', true, true, 0, 0, rlip_timestamp(9, 45, 0, 1, 2, 2010)),
                array('Jan/02/2010', true, false, 2011, 0, false),
                array('Jan/02/2010', true, false, 2008, 2009, false),
                array('02-01-2010', true, false, 0, 0, rlip_timestamp(0, 0, 0, 1, 2, 2010)),
                array('02-01-2010', false, false, 0, 0, false),
                array('2010.01.02', true, false, 0, 0, rlip_timestamp(0, 0, 0, 1, 2, 2010)),
                array('2010.01.02', false, false, 0, 0, false),
                array('*/01/2013', true, true, 0, 0, false),
                array('_/01/2013', true, true, 0, 0, false),
                array(false, true, true, 0, 0, false),
                array('', true, true, 0, 0, false),
                array(1, true, true, 0, 0, false),
                array(1.001, true, true, 0, 0, false),
                array(array(1), true, true, 0, 0, false),
                array($bogusobj, true, true, 0, 0, false),
                array(null, true, true, 0, 0, false)
        );
    }

    /**
     * Validate that the new date format works as expected
     * @param string $instr  input date/time
     * @param bool   $oldformats  whether it supports old formats
     * @param bool   $inctime     whether it supports additional time info
     * @param int    $minyear     the minimum year allowed
     * @param int    $maxyear     the maximum year allowed
     * @param mixed  $expected    the expected output from parse_date() method given the above parameters
     * @dataProvider pddataprovider
     */
    public function testversion1elisparsedate($instr, $oldformats, $inctime, $minyear, $maxyear, $expected) {
        $plugin = new rlip_importplugin_version1elis();
        $timestamp = $plugin->parse_date($instr, $oldformats, $inctime, $minyear, $maxyear);
        $this->assertEquals($expected, $timestamp);
    }

    /**
     * test_rlip_gmt_from_usertime() data provider
     * values format: array( inputtimestamp, timezone, outputtimestamp)
     * @return array the test data
     */
    public function rlip_gmt_from_usertime_dataprovider() {
        return array(
            array(12345678, 0, 12345678),
            array(12345678, 14, 12345678),
            array(12345678, 13.1, 12345678),
            array(100000, 12, 100000 - (12 * HOURSECS)),
            array(100000, -12, 100000 - (-12 * HOURSECS)),
            array(100000, 12.5, 100000 - (12.5 * HOURSECS)),
            array(1366619400, 'America/Toronto', 1366633800), // w/ DST
            array(1385112600, 'America/Toronto', 1385130600), // w/o DST
        );
    }

    /**
     * Test rlip_gmt_from_usertime() function
     * @param int $intimestamp the input timestamp
     * @param float|int|string $timezone the timezone of $intimestamp
     * @param int $outtimestamp the GMT timestamp for $intimestamp in $timezone
     * @dataProvider rlip_gmt_from_usertime_dataprovider
     */
    public function test_rlip_gmt_from_usertime($intimestamp, $timezone, $outtimestamp) {
        if (get_user_timezone_offset($timezone) == 99) {
            $this->markTestSkipped("\nSkipping test_rlip_gmt_from_usertime() with undefined timezone = '{$timezone}'\n");
        } else {
            $this->assertEquals($outtimestamp, rlip_gmt_from_usertime($intimestamp, $timezone));
        }
    }
}
