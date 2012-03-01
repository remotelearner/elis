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

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;

/**
 * Class for testing utility methods
 */
class utilityMethodTest extends PHPUnit_Framework_TestCase {

    /**
     * Validate that time string sanitization sets the default when input
     * string is empty
     */
    function testSanitizeTimeStringProvidesDefault() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('', '1d');
        $this->assertEquals($result, '1d');
    }

    /**
     * Validate that time string sanitization leaves valid strings unchanged
     */
    function testSanitizeTimeStringLeavesValidStringUnchanged() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1d2h3m', '1d');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the beginning of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromStart() {
         global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the middle of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromMiddle() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes an invalid portion from
     * the end of a string
     */
    function testSanitizeTimeStringRemovesInvalidPortionFromEnd() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1x2d3h4m');
        $this->assertEquals($result, '2d3h4m');
    }

    /**
     * Validate that time string sanitization removes non-portion characters
     * from all parts of a time string 
     */
    function testSanitizeTimeStringRemovesNonPortionCharacters() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1d, 2h, 3m');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization converts letters to lowercase
     */
    function testSanitizeTimeStringConvertsLettersToLowercase() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1D2H3M');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that time string sanitization prevents multiple units beside
     * one another
     */
    function testSanitizeTimeStringPreventsConsecutiveUnits() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_sanitize_time_string('1d2h3mm');
        $this->assertEquals($result, '1d2h3m');
    }

    /**
     * Validate that converting time string to offset works for number of
     * days
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForDays() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_time_string_to_offset('2d');
        $this->assertEquals($result, 2 * DAYSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * hours
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForHours() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_time_string_to_offset('2h');
        $this->assertEquals($result, 2 * HOURSECS);
    }

    /**
     * Validate that converting time string to offset works for number of
     * minutes
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForMinutes() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_time_string_to_offset('2m');
        $this->assertEquals($result, 2 * MINSECS);
    }

    /**
     * Validate that converting time string to offset works for complex string
     * with hours, minutes and seconds
     */
    function testTimeStringToOffsetReturnsCorrectOffsetForComplexString() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        $result = rlip_time_string_to_offset('1d2h3m');
        $this->assertEquals($result, DAYSECS + 2 * HOURSECS + 3 * MINSECS);
    }
}