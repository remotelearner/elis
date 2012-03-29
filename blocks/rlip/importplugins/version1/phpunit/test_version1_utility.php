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

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
$file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
require_once($file);

/**
 * Class for testing utility methods in the version 1 import plugin
 */
class version1UtilityTest extends PHPUnit_Framework_TestCase {
    /**
     * Validate that the new date format works as expected
     */
    public function testVersion1ImportParsesValidDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02/2010');
        $this->assertEquals($timestamp, mktime(0, 0, 0, 1, 2, 2010));
    }

    /**
     * Validate that the old date format works as expected
     */
    public function testVersion1ImportParsesValidLegacyDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('01/02/2010');
        $this->assertEquals($timestamp, mktime(0, 0, 0, 1, 2, 2010));
    }

    /**
     * Validate that dates require three parts, separated by slashes
     */
    public function testVersion1ImportValidatesIncorrectNumberOfPartsInDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid month names are handled
     */
    public function testVersion1ImportValidatesIncorrectTextualMonthInDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Janx/02/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid numberical months are handled
     */
    public function testVersion1ImportValidatesIncorrectNumericalMonthInDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('13/02/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid days are handled
     */
    public function testVersion1ImportValidatesIncorrectDayInDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/bogusday/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid years are handled
     */
    public function testVersion1ImportValidatesIncorrectYearInDate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02/bogusyear');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid date combinations are validated (i.e. dates
     * where all parts are valid but the date is not possible)
     */
    public function testVersion1ImportValidatesIncorrectDateCombination() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Feb/31/2010');
        $this->assertFalse($timestamp);
    }
}