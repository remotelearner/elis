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
 * @package    rlipimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
$file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
require_once($file);
require_once($CFG->dirroot.'/blocks/rlip/lib.php');

/**
 * Class for testing utility methods in the version 1 import plugin.
 * @group block_rlip
 * @group rlipimport_version1
 */
class version1UtilityTest extends basic_testcase {
    /**
     * Validate that the new date format works as expected
     */
    public function test_version1importparsesvaliddate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02/2010');
        $this->assertEquals($timestamp, rlip_timestamp(0, 0, 0, 1, 2, 2010));
    }

    /**
     * Validate that the old date format works as expected
     */
    public function test_version1importparsesvalidlegacydate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('01/02/2010');
        $this->assertEquals($timestamp, rlip_timestamp(0, 0, 0, 1, 2, 2010));
    }

    /**
     * Validate that dates require three parts, separated by slashes
     */
    public function test_version1importvalidatesincorrectnumberofpartsindate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid month names are handled
     */
    public function test_version1importvalidatesincorrecttextualmonthindate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Janx/02/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid numberical months are handled
     */
    public function test_version1importvalidatesincorrectnumericalmonthindate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('13/02/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid days are handled
     */
    public function test_version1importvalidatesincorrectdayindate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/bogusday/2010');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid years are handled
     */
    public function test_version1importvalidatesincorrectyearindate() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Jan/02/bogusyear');
        $this->assertFalse($timestamp);
    }

    /**
     * Validate that invalid date combinations are validated (i.e. dates
     * where all parts are valid but the date is not possible)
     */
    public function test_version1importvalidatesincorrectdatecombination() {
        $plugin = new rlip_importplugin_version1();
        $timestamp = $plugin->parse_date('Feb/31/2010');
        $this->assertFalse($timestamp);
    }
}
