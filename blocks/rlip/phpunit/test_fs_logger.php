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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) .'/config.php');
global $CFG;
require_once($CFG->dirroot .'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/lib/rlip_fslogger.class.php');
require_once($CFG->dirroot .'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot .'/elis/core/lib/setup.php');
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
	 * @return boolean successful open
	 */
    function open($mode) {
        $this->open = true;
        $this->was_opened = true;

        return true;
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
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name.
     */
    function get_filename($withpath = false) {
        return '/dev/null';
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
}

/**
 * Class for testing the file-system logger
 */
class fsLoggerTest extends rlip_test {
    protected $backupGlobals = array('CFG');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('config' => 'moodle',
                     'config_plugins' => 'moodle',
                     'timezone' => 'moodle');
    }

    protected function assert_file_contents_equal($filename, $data) {
        //make sure the number of rows is correct
        $fdata = file($filename);
        $flcnt = count($fdata);
        $datacnt = count($data);
        $this->assertEquals($flcnt, $datacnt,
               "# of file lines ({$flcnt}) doesn't match with data ({$datacnt})");

        //compare all lines
        $result = true;
        $linenum = 0;
        $line = reset($fdata);
        foreach ($data as $datum) {
            ++$linenum;
            $msg = "\nFile {$filename}:{$linenum} - content error - '{$line}' != '{$datum}'\n";
            if ($line != $datum) {
                $result = false;
                break;
            }
            $line = next($fdata);
        }

        //assert that all lines were successful
        $this->assertTrue($result, $msg);
    }

    /**
     * Provides a file-system logger object bound to a log file
     *
     * @param boolean $manual true to indicate to the logger that the run is
     *                        manual, or false for scheduled
     * @return array Array of the logger object and the associated filename
     */
    protected function get_fs_logger($manual = false) {
        global $CFG;

        set_config('logfilelocation',$CFG->dataroot,'bogus_plugin');
        //set up the file plugin for IO
        $filename = $CFG->dataroot .'/rliptest.log';
        $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true);

        //set up the logging object
        $fslogger = rlip_fslogger_factory::factory('bogus_plugin', $fileplugin, $manual);

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
        $fslogger->log_success(1000000000, 'Teststring');

        //track whether data was recorded on-the-fly
        $firstcount = count(file($filename));

        //write a line
        $fslogger->log_success(1000000000, 'Teststring');

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
        $fslogger->log_success(1000000000, 'Teststring');

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
        $filename = $CFG->dataroot .'/rliptest.log';
        @unlink($filename);

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
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
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
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));

        //set default timezone
        set_config('timezone', -6.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $expected_line = '['.userdate($time, $format, -6.0, false).' -0600] Message'."\n";
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
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));

        //set default timezone
        set_config('forcetimezone', -6.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $expected_line = '['.userdate($time, $format, -6.0, false).' -0600] Message'."\n";
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
        $fslogger->log_success($message);

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
        $fslogger->log_success($message);

        //clean up
        $fslogger->close();

        //validation
        $offset = date('Z', $time) / 60 / 60;
        $offset -= date('I', $time);
        $offset_display = rlip_fslogger::offset_display($offset);
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_line = '['.userdate($time, $format, 99, false).' '.$offset_display.'] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expected_line));
    }

    /**
     * Validate that the file-system logger plays nicely with daylight savings
     * time and uses string timezones - this also implicitly sets string timezones
     * with the "timezone" and "forcetimezone" settings
     */
    public function testFsLoggerRespectsDaylightSavings() {
        global $DB;

        /**
         * Something in the southern hemisphere
         */

        //force the timezone to a region that uses DST
        set_config('timezone', 'America/New_York');

        //set up timezone data
        $record = new stdClass;
        $record->name = 'America/New_York';
        $record->year = 2007;
        $record->tzrule = 'US';
        $record->gmtoff = -300;
        $record->dstoff = 60;
        $record->dst_month = 3;
        $record->dst_startday = 8;
        $record->dst_weekday = 0;
        $record->dst_skipweeks = 0;
        $record->dst_time = '-3:00';
        $record->std_month = 11;
        $record->std_startday = 1;
        $record->std_weekday = 0;
        $record->std_skipweeks = 0;
        $record->std_time = '-4.00';
        $DB->insert_record('timezone', $record);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line for the current time
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        //write a line for six months in the future
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation

        //validate number of lines
        $this->assertEquals(count(file($filename)), 2);

        //get file contents
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        //validate that the times match
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        //validate that the offsets are reported correctly
        $position1 = strpos($portion1, '-0500');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '-0500');
        $this->assertTrue($position2 !== false);

        /**
         * Something in the southern hemisphere
         */

        //force the timezone to a region that uses DST
        set_config('forcetimezone', 'America/Asuncion');

        //set up timezone data
        $record = new stdClass;
        $record->name = 'America/Asuncion';
        $record->year = 2010;
        $record->tzrule = 'Para';
        $record->gmtoff = -240;
        $record->dstoff = 60;
        $record->dst_month = 10;
        $record->dst_startday = 1;
        $record->dst_weekday = 0;
        $record->dst_skipweeks = 0;
        $record->dst_time = '-4:00';
        $record->std_month = 4;
        $record->std_startday = 4;
        $record->std_weekday = 8;
        $record->std_skipweeks = 0;
        $record->std_time = '-5.00';
        $DB->insert_record('timezone', $record);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line for the current time
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        //write a line for six months in the future
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation

        //validate number of lines
        $this->assertEquals(count(file($filename)), 2);

        //get file contents
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        //validate that the times match
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        //validate that the offsets are reported correctly
        $position1 = strpos($portion1, '-0400');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '-0400');
        $this->assertTrue($position2 !== false);

        /**
         * Something without DST
         */

        //force the timezone to a region that does not use DST
        set_config('forcetimezone', 'Asia/Jakarta');

        //set up timezone data
        $record = new stdClass;
        $record->name = 'Asia/Jakarta';
        $record->year = 1970;
        $record->tzrule = '';
        $record->gmtoff = 420;
        $record->dstoff = 0;
        $record->dst_month = 0;
        $record->dst_startday = 0;
        $record->dst_weekday = 0;
        $record->dst_skipweeks = 0;
        $record->dst_time = '00:00';
        $record->std_month = 0;
        $record->std_startday = 0;
        $record->std_weekday = 0;
        $record->std_skipweeks = 0;
        $record->std_time = '00.00';
        $DB->insert_record('timezone', $record);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //write a line for the current time
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        //write a line for six months in the future
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation

        //validate number of lines
        $this->assertEquals(count(file($filename)), 2);

        //get file contents
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        //validate that the times match
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        //validate that the offsets are reported correctly
        $position1 = strpos($portion1, '+0700');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '+0700');
        $this->assertTrue($position2 !== false);
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
        $fslogger->log_success($message, $time);
        //write a line
        $message = 'Message 2';
        $fslogger->log_success($message, $time);

        //clean up
        $fslogger->close();

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_lines = array('['.userdate($time, $format, -5.0, false).' -0500] Message'."\n",
                                '['.userdate($time, $format, -5.0, false).' -0500] Message 2'."\n");
        $this->assert_file_contents_equal($filename, $expected_lines);
    }

    /**
     * Validate that the file-system logger closes the appropriate file after writing
     */
    public function testFsLoggerClosesFile() {
        //set up the file plugin for IO
        $fileplugin = new rlip_fileplugin_trackopen();

        //set up the logging object
        $fslogger = rlip_fslogger_factory::factory('bogus_plugin', $fileplugin);

        //write a line
        $result = $fslogger->log_success('Teststring', 1000000000);

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

    /**
     * Validate that the file-system logger correctly implements the API hook
     * for customizing a log line
     */
    public function testLinebasedFsloggerCustomizesRecord() {
        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //obtain the specialized string
        $specialized_output = $fslogger->customize_record('Message', time(), 'rliptest', 10, true);

        //validation
        $expected_line = '[rliptest line 10] Message';
        $this->assertEquals($specialized_output, $expected_line);
    }

    /**
     * Validate that the file-system logger correctly logs a complete line on
     * success
     */
    public function testLinebasedFsloggerCalculatesFileAndLineInformationOnSuccess() {
        //force the timezone
        set_config('forcetimezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //log a line with all information included
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_lines = array('['.userdate($time, $format, -5.0, false).' -0500] [rliptest line 10] Message'."\n");
        $this->assert_file_contents_equal($filename, $expected_lines);
    }

    /**
     * Validate that the file-system logger correctly logs a complete line on
     * failure
     */
    public function testLinebasedFsloggerCalculatesFileAndLineInformationOnFailure() {
        //force the timezone
        set_config('forcetimezone', -5.0);

        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //log a line with all information included
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        //validation
        $format = get_string('logtimeformat', 'block_rlip');
        $expected_lines = array('['.userdate($time, $format, -5.0, false).' -0500] [rliptest line 10] Message'."\n");
        $this->assert_file_contents_equal($filename, $expected_lines);
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * successful record on a manual run
     */
    public function testFsLoggerDoesNotOutputForManualSuccessLog() {
        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger(true);

        //start output buffering
        ob_start();

        //log a line with all information included
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the file-system logger produces output for a failed record
     * on a manual run
     */
    public function testFsLoggerOutputsForManualFailureLog() {
        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger(true);

        //start output buffering
        ob_start();

        //log a line with all information included
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * successful record on a scheduled run
     */
    public function testFsLoggerDoesNotOutputForScheduledSuccessLog() {
        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //start output buffering
        ob_start();

        //log a line with all information included
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * failed record on a scheduled run
     */
    public function testFsLoggerDoesNotOutputForScheduledFailureLog() {
        //set up the logging object
        list($fslogger, $filename) = $this->get_fs_logger();

        //start output buffering
        ob_start();

        //log a line with all information included
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        //validation
        $this->assertEquals($output, '');
    }
}
