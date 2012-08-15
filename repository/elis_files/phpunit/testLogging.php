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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/repository/elis_files/lib/elis_files_logger.class.php');

/**
 * Class for testing the ELIS Files logging object and its related functionality
 */
class loggingTest extends PHPUnit_Framework_TestCase {
    /**
     * Unit tests for the base logger object 
     */

    /**
     * Validate that, when the logger is first constructed, it is in a clean
     * state, with no error message returned when polled
     */
    public function testLoggerHasCorrectStateOnConstruct() {
        // Obtain logger in its default state
        $logger = elis_files_logger::instance();
        $error_message = $logger->get_error_message();

        // Validate that false is returned instead of an error message
        $this->assertFalse($error_message);
    }

    /**
     * Data provider used to provide error codes and associated error strings
     *
     * @return array Data array containing error codes and associated strings
     */
    public function errorCodeAndMessageProvider() {
        $config = get_config('elis_files');

        $uri = parse_url($config->server_host);
        $a = $uri['host'].':'.$config->ftp_port;

        return array(
            array(ELIS_FILES_ERROR_FTP, get_string('errorftpinvalidport', 'repository_elis_files', $a))
        );
    }

    /**
     * Validate that the correct message is returned during polling, when the
     * logger object is in the correct error state
     *
     * @pararm int $error_code The error code to signal in the logger object
     * @param string $expected_string The expected human-readable error string
     *                                that the logger should return when polled
     * @dataProvider errorCodeAndMessageProvider
     */
    public function testLoggerReturnsCorrectMessageOnError($error_code, $expected_string) {
        // Get the logger into an error state
        $logger = elis_files_logger::instance();
        $logger->signal_error($error_code);
        $error_message = $logger->get_error_message();

        // Reset the state, for later convenience
        $logger->flush();

        $this->assertEquals($expected_string, $error_message);
    }

    /**
     * Validate that flushing an existing error works as expected
     */
    public function testLoggerHasCorrectStateOnFlush() {
        // Get the logger into an error state
        $logger = elis_files_logger::instance();
        $logger->signal_error(ELIS_FILES_ERROR_FTP);

        // Reset the state
        $logger->flush();

        // Validate that false is returned intead of an error message
        $error_message = $logger->get_error_message();
        $this->assertFalse($error_message);
    }

    /**
     * Unit tests for the singleton setup
     */

    /**
     * Validate that the logger "instance" method follows the singleton
     * pattern and only ever returns a single logger object
     */
    public function testLoggerMaintainsSingleInstance() {
        // Validate that only once instance is maintained
        $logger1 = elis_files_logger::instance();
        $logger2 = elis_files_logger::instance();

        // Should be the exact same instance
        $this->assertTrue($logger1 === $logger2);

        // Should be a valid class instance
        $this->assertInstanceOf('elis_files_logger', $logger1);
    }
}