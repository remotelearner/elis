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
 * @package    repository_elis_files
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/elis_files_logger.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/tests/constants.php');

/**
 * Class for testing the ELIS Files logging object and its related functionality
 * @group repository_elis_files
 */
class repository_elis_files_logging_testcase extends elis_database_test {
    /**
     * This function loads data into the PHPUnit tables for testing
     */
    protected function setup_test_data_xml() {
        if (!file_exists(dirname(__FILE__).'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You need to configure the test config file to run ELIS files tests');
            return false;
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * Validate that, when the logger is first constructed, it is in a clean
     * state, with no error message returned when polled
     */
    public function test_logger_has_correct_state_on_construct() {
        // Obtain logger in its default state
        $logger = elis_files_logger::instance();
        $errormessage = $logger->get_error_message();

        // Validate that false is returned instead of an error message
        $this->assertFalse($errormessage);
    }

    /**
     * Validate that the correct message is returned during polling, when the logger object is in the correct error state.
     */
    public function test_logger_returns_correct_message_on_error() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $config = get_config('elis_files');

        $uri = parse_url($config->server_host);
        $a = $uri['host'].':'.$config->ftp_port;

        $errorcode = ELIS_FILES_ERROR_FTP;
        $expectedstring = get_string('errorftpinvalidport', 'repository_elis_files', $a);

        // Get the logger into an error state
        $logger = elis_files_logger::instance();
        $logger->signal_error($errorcode);
        $errormessage = $logger->get_error_message();

        // Reset the state, for later convenience
        $logger->flush();

        $this->assertEquals($expectedstring, $errormessage);
    }

    /**
     * Validate that flushing an existing error works as expected
     */
    public function test_logger_has_correct_state_on_flush() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        // Get the logger into an error state
        $logger = elis_files_logger::instance();
        $logger->signal_error(ELIS_FILES_ERROR_FTP);

        // Reset the state
        $logger->flush();

        // Validate that false is returned intead of an error message
        $errormessage = $logger->get_error_message();
        $this->assertFalse($errormessage);
    }

    /**
     * Validate that the logger "instance" method follows the singleton
     * pattern and only ever returns a single logger object
     */
    public function test_logger_maintains_single_instance() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        // Validate that only once instance is maintained
        $logger1 = elis_files_logger::instance();
        $logger2 = elis_files_logger::instance();

        // Should be the exact same instance
        $this->assertTrue($logger1 === $logger2);

        // Should be a valid class instance
        $this->assertInstanceOf('elis_files_logger', $logger1);
    }
}
