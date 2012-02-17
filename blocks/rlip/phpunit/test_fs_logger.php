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
require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/rlip_fslogger.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));

/**
 * Mock object class to track whether a file is open
 */
class rlip_fileplugin_trackopen extends rlip_fileplugin_base {
    var $open = false;
    var $was_opened = false;

    function __construct() {
    }
    
    /**
	 * Hook for opening the file
	 *
	 * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
	 *                  the mode in which the file should be opened
	 */
    function open($mode) {
        $this->open = true;
        $this->was_opened = true;
    }

    /**
     * Hook for reading one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        return array();
    }

    /**
     * Hook for writing one entry to the file
     *
     * @param array $line The entry to write to the file
     */
    function write($entry) {
    }

    /**
     * Hook for closing the file
     */
    function close() {
        $this->open = false;
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        return '';
    }

    /**
     * Specifies whether this file is currently open
     *
     * @return boolean Returns true if the file is still open, otherwise false
     */
    function is_open() {
        return $this->open;
    }

    /**
     * Specifies whether this file was ever opened at any point in time
     *
     * @return boolean Returns true if the file was ever opened, otherwise false
     */
    function was_opened() {
        return $this->was_opened;
    }

    /**
     * Specifies the extension of the current open file
     *
     * @return string The file extension
     */
    function get_extension() {
        return 'bogus';
    }
}

/**
 * Class for testing the file-system logger
 */
class fsLoggerTest extends elis_database_test {
    protected $backupGlobals = array('CFG');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('config' => 'moodle');
    }

    protected function assert_file_contents_equal($filename, $data) {
        //make sure the number of rows is correct
        $this->assertEquals(count(file($filename)), count($data));

        //track status
        $result = true;

        //compare all lines
        $pointer = fopen($filename, 'r');
        foreach ($data as $datum) {
            $line = fgets($pointer);
            $result = $result && ($line == $datum);
        }
        fclose($pointer);

        //assert that all lines were successful
        $this->assertTrue($result);
    }

    /**
     * Provides a file-system logger object bound to a log file
     *
     * @return array Array of the logger object and the associated filename
     */
    protected function get_fs_logger() {
        global $CFG;

        //set up the file plugin for IO
        $filename = $CFG->dataroot.'/rliptest';
        $fileplugin = rlip_fileplugin_factory::factory($CFG->dataroot.'/rliptest', true);

        //set up the logging object
        $fslogger = rlip_fslogger_factory::factory($fileplugin);
        $filename .= '.'.$fileplugin->get_extension();

        return array($fslogger, $filename);        
    }

    /**
     * Validate that the file-system logger writes data out on-the-fly (i.e.
     * log files are tailable)
     */
    public function testFsLoggerWritesDataOnTheFly() {
        global $CFG;

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $fslogger->log(1000000000, 'Teststring');

        //track whether data was recorded on-the-fly
        $firstcount = count(file($filename));

        //write a line
        $fslogger->log(1000000000, 'Teststring');

        //track whether data was recorded on-the-fly
        $secondcount = count(file($filename));

        //clean up
        $fslogger->close();

        //validation
        $this->assertEquals($firstcount, 1);
        $this->assertEquals($secondcount, 2);
    }

    /**
     * Validate that the file-system logger creates a file with the specified
     * name when a write takes place
     */
    public function testFsLoggerCreatesFileWithCorrectName() {
        global $CFG;

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $fslogger->log(1000000000, 'Teststring');

        //clean up
        $fslogger->close();

        //validation
        $this->assertFileExists($filename);
    }

    /**
     * Validate that the file-system logger does not create a file if no lines
     * are to be written to it
     */
    public function testFsLoggerDoesNotCreateFileWhenNoDataWritten() {
        global $CFG;

        //set up the file plugin for IO
        $filename = $CFG->dataroot.'/rliptest';
        unlink($filename.'.log');

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //clean up
        $fslogger->close();

        //validation
        $this->assertFalse(file_exists($filename));
    }

    /**
     * Validate that the file-system logger writes a line in the correct
     * format to the file-system log
     */
    public function testFsLoggerWritesCorrectLineData() {
        global $CFG;

        //force the timezone
        set_config('forcetimezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));
    }

    /**
     * Validate that the file-system logger never removes leading zeros from
     * the day-of-month
     */
    public function testFsLoggerNeverFixesDay() {
        global $CFG;

        //force the timezone
        set_config('forcetimezone', -5.0);
        set_config('nofixday', 1);

        //validate that day "1" is displayed as "01" when "nofixday" is enabled 
        $time = strtotime("1 September 2000 12:00");
        $expected_result = "Sep/01/2000";
        $converted_string = rlip_fslogger::time_display($time, 99);
        $compare_string = substr($converted_string, 0, strlen($expected_result));
        $this->assertEquals($expected_result, $compare_string);

        //disable "nofixday"
        set_config('nofixday', null);
        unset($CFG->nofixday);

        //validate that day "1" is displayed as "01" when "nofixday" is disabled
        $converted_string = rlip_fslogger::time_display($time, 99);
        $compare_string = substr($converted_string, 0, strlen($expected_result));
        $this->assertEquals($expected_result, $compare_string);
    }

    /**
     * Validate that the file-system logger respects the Moodle default
     * timezone setting
     */
    public function testFsLoggerRespectsDefaultTimezones() {
        global $CFG;

        //set default timezone
        unset($CFG->forcetimezone);
        set_config('timezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));

        //set default timezone
        set_config('timezone', -6.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $expected_line = '['.userdate($time, $format, -6.0).' -0600] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));
    }

    /**
     * Validate that the file-system logger respects the Moodle forced
     * timezone setting
     */
    public function testFsLoggerRespectsForcedTimezones() {
        global $CFG;

        //force the timezone
        set_config('forcetimezone', -5.0);
        //validate that the "forced" value takes precidence
        set_config('timezone', -7.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));

        //set default timezone
        set_config('forcetimezone', -6.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $expected_line = '['.userdate($time, $format, -6.0).' -0600] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));
    }

    /**
     * Validate that the file-system logger logs the current time by default
     */
    public function testFsLoggerLogsCurrentTimeByDefault() {
        global $CFG;

        //force the timezone
        set_config('forcetimezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message);

        //clean up
        $fslogger->close();

        //validation
        $this->assertEquals(count(file($filename)), 1);
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        $expected_substring = date('M').'/'.date('d').'/'.date('Y');
        fclose($pointer);
        $this->assertEquals(strpos($line, $expected_substring), 1);
    }

    /**
     * Validate that the file-system logger respects the server timezone
     */
    public function testFsLoggerRespectsServerTimezone() {
        global $CFG;

        unset($CFG->forcetimezone);
        unset($CFG->timezone);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message);

        //clean up
        $fslogger->close();

        //validation
        $offset = date('Z', $time) / 60 / 60;
        $offset -= date('I', $time);
        $offset_display = rlip_fslogger::offset_display($offset);
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format).' '.$offset_display.'] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));
    }

    /**
     * Validate that the file-system logger plays nicely with daylight savings
     * time
     */
    public function testFsLoggerRespectsDaylightSavings() {
        $this->assertEquals(0, 1);
    }

    /**
     * Validate that the file-system logger can write out multiple lines of
     * data to the file-system log
     */
    public function testFsLoggerWritesMultilineData() {
        global $CFG;

        //force the timezone
        set_config('forcetimezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log($message, $time);
        //write a line
        $time = time();
        $message = 'Message 2';
        $fslogger->log($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_lines = array('['.userdate($time, $format, -5.0).' -0500] Message'."\n",
                                '['.userdate($time, $format, -5.0).' -0500] Message 2'."\n");
        $this->assert_file_contents_equal($filename, $expected_lines);
    }

    /**
     * Validate that the file-system logger closes the appropriate file after writing
     */
    public function testFsLoggerClosesFile() {
        //set up the file plugin for IO
        $fileplugin = new rlip_fileplugin_trackopen();

        //set up the logging object
        $fslogger = rlip_fslogger_factory::factory($fileplugin);

        //write a line
        $fslogger->log('Teststring', 1000000000);

        //clean up
        $fslogger->close();

        //validation
        $this->assertTrue($fileplugin->was_opened());
        $this->assertFalse($fileplugin->is_open());
    }

    /**
     * Validate that the method for converting offsets to display strings
     * works as expected
     */
    public function testFsLoggerCalculatesDisplayedOffset() {
        //basic negative offsets
        $this->assertEquals(rlip_fslogger::offset_display(-12), '-1200');
        $this->assertEquals(rlip_fslogger::offset_display(-5), '-0500');

        //make sure negative mods don't do anything weird
        $this->assertEquals(rlip_fslogger::offset_display(-1.25), '-0115');

        //special case: zero
        $this->assertEquals(rlip_fslogger::offset_display(0), '+0000');

        //test decimals
        $this->assertEquals(rlip_fslogger::offset_display(1.25), '+0115');
        $this->assertEquals(rlip_fslogger::offset_display(1.5), '+0130');

        //test positive hours
        $this->assertEquals(rlip_fslogger::offset_display(5), '+0500');
        $this->assertEquals(rlip_fslogger::offset_display(12), '+1200');
    }
}