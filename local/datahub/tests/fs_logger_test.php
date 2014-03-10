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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');
require_once($CFG->dirroot.'/local/datahub/lib.php');

/**
 * Mock object class to track whether a file is open
 */
class rlip_fileplugin_trackopen extends rlip_fileplugin_base {
    public $open = false;
    public $was_opened = false;

    public function __construct() {
    }

    /**
     * Hook for opening the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     * @return boolean successful open
     */
    public function open($mode) {
        $this->open = true;
        $this->was_opened = true;

        return true;
    }

    /**
     * Hook for reading one entry from the file
     *
     * @return array The entry read
     */
    public function read() {
        return array();
    }

    /**
     * Hook for writing one entry to the file
     *
     * @param array $line The entry to write to the file
     */
    public function write($entry) {
    }

    /**
     * Hook for closing the file
     */
    public function close() {
        $this->open = false;
    }

    /**
     * Specifies the name of the current open file
     *
     * @param  bool   $withpath  Whether to include fullpath with filename
     *                           default is NOT to include full path.
     * @return string The file name.
     */
    public function get_filename($withpath = false) {
        return '/dev/null';
    }

    /**
     * Specifies whether this file is currently open
     *
     * @return boolean Returns true if the file is still open, otherwise false
     */
    public function is_open() {
        return $this->open;
    }

    /**
     * Specifies whether this file was ever opened at any point in time
     *
     * @return boolean Returns true if the file was ever opened, otherwise false
     */
    public function was_opened() {
        return $this->was_opened;
    }
}

/**
 * Class for testing the file-system logger
 * @group local_datahub
 */
class fslogger_testcase extends rlip_test {

    protected function assert_file_contents_equal($filename, $data) {
        // Make sure the number of rows is correct.
        $fdata = file($filename);
        $flcnt = count($fdata);
        $datacnt = count($data);
        $this->assertEquals($flcnt, $datacnt, "# of file lines ({$flcnt}) doesn't match with data ({$datacnt})");

        // Compare all lines.
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

        // Assert that all lines were successful.
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

        set_config('logfilelocation', $CFG->dataroot, 'bogus_plugin');
        // Set up the file plugin for IO.
        $filename = $CFG->dataroot.'/rliptest.log';
        $fileplugin = rlip_fileplugin_factory::factory($filename, null, true);

        // Set up the logging object.
        $fslogger = rlip_fslogger_factory::factory('bogus_plugin', $fileplugin, $manual);

        return array($fslogger, $filename);
    }

    /**
     * Validate that the file-system logger writes data out on-the-fly (i.e.
     * log files are tailable)
     */
    public function test_fsloggerwritesdataonthefly() {
        global $CFG;

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $fslogger->log_success(1000000000, 'Teststring');

        // Track whether data was recorded on-the-fly.
        $firstcount = count(file($filename));

        // Write a line.
        $fslogger->log_success(1000000000, 'Teststring');

        // Track whether data was recorded on-the-fly.
        $secondcount = count(file($filename));

        // Clean up.
        $fslogger->close();

        // Validation.
        $this->assertEquals($firstcount, 1);
        $this->assertEquals($secondcount, 2);
    }

    /**
     * Validate that the file-system logger creates a file with the specified
     * name when a write takes place
     */
    public function test_fsloggercreatesfilewithcorrectname() {
        global $CFG;

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $fslogger->log_success(1000000000, 'Teststring');

        // Clean up.
        $fslogger->close();

        // Validation.
        $this->assertFileExists($filename);
    }

    /**
     * Validate that the file-system logger does not create a file if no lines
     * are to be written to it
     */
    public function test_fsloggerdoesnotcreatefilewhennodatawritten() {
        global $CFG;

        // Set up the file plugin for IO.
        $filename = $CFG->dataroot.'/rliptest.log';
        @unlink($filename);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Clean up.
        $fslogger->close();

        // Validation.
        $this->assertFalse(file_exists($filename));
    }

    /**
     * Validate that the file-system logger writes a line in the correct
     * format to the file-system log
     */
    public function test_fsloggerwritescorrectlinedata() {
        global $CFG;

        // Force the timezone.
        set_config('forcetimezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedline = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));
    }

    /**
     * Validate that the file-system logger never removes leading zeros from
     * the day-of-month
     */
    public function test_fsloggerneverfixesday() {
        global $CFG;

        // Force the timezone.
        set_config('forcetimezone', -5.0);
        set_config('nofixday', 1);

        // Validate that day "1" is displayed as "01" when "nofixday" is enabled.
        $time = strtotime("1 September 2000 12:00");
        $expectedresult = "Sep/01/2000";
        $convertedstring = rlip_fslogger::time_display($time, 99);
        $comparestring = substr($convertedstring, 0, strlen($expectedresult));
        $this->assertEquals($expectedresult, $comparestring);

        // Disable "nofixday".
        set_config('nofixday', null);
        unset($CFG->nofixday);

        // Validate that day "1" is displayed as "01" when "nofixday" is disabled.
        $convertedstring = rlip_fslogger::time_display($time, 99);
        $comparestring = substr($convertedstring, 0, strlen($expectedresult));
        $this->assertEquals($expectedresult, $comparestring);
    }

    /**
     * Validate that the file-system logger respects the Moodle default
     * timezone setting
     */
    public function test_fsloggerrespectsdefaulttimezones() {
        global $CFG;

        // Set default timezone.
        unset($CFG->forcetimezone);
        set_config('timezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedline = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));

        // Set default timezone.
        set_config('timezone', -6.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $expectedline = '['.userdate($time, $format, -6.0, false).' -0600] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));
    }

    /**
     * Validate that the file-system logger respects the Moodle forced
     * timezone setting
     */
    public function test_fsloggerrespectsforcedtimezones() {
        global $CFG;

        // Force the timezone.
        set_config('forcetimezone', -5.0);
        // Validate that the "forced" value takes precidence.
        set_config('timezone', -7.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedline = '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));

        // Set default timezone.
        set_config('forcetimezone', -6.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $expectedline = '['.userdate($time, $format, -6.0, false).' -0600] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));
    }

    /**
     * Validate that the file-system logger logs the current time by default
     */
    public function test_fsloggerlogscurrenttimebydefault() {
        global $CFG;

        // Force the timezone.
        set_config('forcetimezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        // Clean up.
        $fslogger->close();

        // Validation.
        $this->assertEquals(count(file($filename)), 1);
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        $date = rlip_fslogger::time_display(time(), -5);
        $expectedsubstring = substr($date, 0, strpos($date, ':'));
        fclose($pointer);
        $this->assertEquals(strpos($line, $expectedsubstring), 1);
    }

    /**
     * Validate that the file-system logger respects the server timezone
     */
    public function test_fsloggerrespectsservertimezone() {
        global $CFG;

        unset($CFG->forcetimezone);
        unset($CFG->timezone);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        // Clean up.
        $fslogger->close();

        // Validation.
        $offset = date('Z', $time) / 60 / 60;
        $offset -= date('I', $time);
        $offsetdisplay = rlip_fslogger::offset_display($offset);
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedline = '['.userdate($time, $format, 99, false).' '.$offsetdisplay.'] Message'."\n";
        $this->assert_file_contents_equal($filename, array($expectedline));
    }

    /**
     * Validate that the file-system logger plays nicely with daylight savings
     * time and uses string timezones - this also implicitly sets string timezones
     * with the "timezone" and "forcetimezone" settings
     */
    public function test_fsloggerrespectsdaylightsavings() {
        global $DB;

        /*
         * Something in the southern hemisphere
         */

        // Force the timezone to a region that uses DST.
        set_config('timezone', 'America/New_York');

        // Set up timezone data.
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

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line for the current time.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        // Write a line for six months in the future.
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.

        // Validate number of lines.
        $this->assertEquals(count(file($filename)), 2);

        // Get file contents.
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        // Validate that the times match.
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        // Validate that the offsets are reported correctly.
        $position1 = strpos($portion1, '-0500');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '-0500');
        $this->assertTrue($position2 !== false);

        /*
         * Something in the southern hemisphere
         */

        // Force the timezone to a region that uses DST.
        set_config('forcetimezone', 'America/Asuncion');

        // Set up timezone data.
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

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line for the current time.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        // Write a line for six months in the future.
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.

        // Validate number of lines.
        $this->assertEquals(count(file($filename)), 2);

        // Get file contents.
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        // Validate that the times match.
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        // Validate that the offsets are reported correctly.
        $position1 = strpos($portion1, '-0400');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '-0400');
        $this->assertTrue($position2 !== false);

        /*
         * Something without DST
         */

        // Force the timezone to a region that does not use DST.
        set_config('forcetimezone', 'Asia/Jakarta');

        // Set up timezone data.
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

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line for the current time.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message);

        // Write a line for six months in the future.
        $time += 182 * DAYSECS;
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.

        // Validate number of lines.
        $this->assertEquals(count(file($filename)), 2);

        // Get file contents.
        $pointer = fopen($filename, 'r');
        $line1 = fgets($pointer);
        $line2 = fgets($pointer);
        fclose($pointer);

        // Validate that the times match.
        $portion1 = substr($line1, 12);
        $portion2 = substr($line2, 12);
        $this->assertEquals($portion1, $portion2);

        // Validate that the offsets are reported correctly.
        $position1 = strpos($portion1, '+0700');
        $this->assertTrue($position1 !== false);
        $position2 = strpos($portion2, '+0700');
        $this->assertTrue($position2 !== false);
    }

    /**
     * Validate that the file-system logger can write out multiple lines of
     * data to the file-system log
     */
    public function test_fsloggerwritesmultilinedata() {
        global $CFG;

        // Force the timezone.
        set_config('forcetimezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Write a line.
        $time = time();
        $message = 'Message';
        $fslogger->log_success($message, $time);
        // Write a line.
        $message = 'Message 2';
        $fslogger->log_success($message, $time);

        // Clean up.
        $fslogger->close();

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedlines = array(
                '['.userdate($time, $format, -5.0, false).' -0500] Message'."\n",
                '['.userdate($time, $format, -5.0, false).' -0500] Message 2'."\n"
        );
        $this->assert_file_contents_equal($filename, $expectedlines);
    }

    /**
     * Validate that the file-system logger closes the appropriate file after writing
     */
    public function test_fsloggerclosesfile() {
        // Set up the file plugin for IO.
        $fileplugin = new rlip_fileplugin_trackopen();

        // Set up the logging object.
        $fslogger = rlip_fslogger_factory::factory('bogus_plugin', $fileplugin);

        // Write a line.
        $result = $fslogger->log_success('Teststring', 1000000000);

        // Clean up.
        $fslogger->close();

        // Validation.
        $this->assertTrue($fileplugin->was_opened());
        $this->assertFalse($fileplugin->is_open());
    }

    /**
     * Validate that the method for converting offsets to display strings
     * works as expected
     */
    public function test_fsloggercalculatesdisplayedoffset() {
        // Basic negative offsets.
        $this->assertEquals(rlip_fslogger::offset_display(-12), '-1200');
        $this->assertEquals(rlip_fslogger::offset_display(-5), '-0500');

        // Make sure negative mods don't do anything weird.
        $this->assertEquals(rlip_fslogger::offset_display(-1.25), '-0115');

        // Special case: zero.
        $this->assertEquals(rlip_fslogger::offset_display(0), '+0000');

        // Test decimals.
        $this->assertEquals(rlip_fslogger::offset_display(1.25), '+0115');
        $this->assertEquals(rlip_fslogger::offset_display(1.5), '+0130');

        // Test positive hours.
        $this->assertEquals(rlip_fslogger::offset_display(5), '+0500');
        $this->assertEquals(rlip_fslogger::offset_display(12), '+1200');
    }

    /**
     * Validate that the file-system logger correctly implements the API hook
     * for customizing a log line
     */
    public function test_linebasedfsloggercustomizesrecord() {
        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Obtain the specialized string.
        $specializedoutput = $fslogger->customize_record('Message', time(), 'rliptest', 10, true);

        // Validation.
        $expectedline = '[rliptest line 10] Message';
        $this->assertEquals($specializedoutput, $expectedline);
    }

    /**
     * Validate that the file-system logger correctly logs a complete line on
     * success
     */
    public function test_linebasedfsloggercalculatesfileandlineinformationonsuccess() {
        // Force the timezone.
        set_config('forcetimezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedlines = array('['.userdate($time, $format, -5.0, false).' -0500] [rliptest line 10] Message'."\n");
        $this->assert_file_contents_equal($filename, $expectedlines);
    }

    /**
     * Validate that the file-system logger correctly logs a complete line on
     * failure
     */
    public function test_linebasedfsloggercalculatesfileandlineinformationonfailure() {
        // Force the timezone.
        set_config('forcetimezone', -5.0);

        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        // Validation.
        $format = get_string('logtimeformat', 'local_datahub');
        $expectedlines = array('['.userdate($time, $format, -5.0, false).' -0500] [rliptest line 10] Message'."\n");
        $this->assert_file_contents_equal($filename, $expectedlines);
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * successful record on a manual run
     */
    public function test_fsloggerdoesnotoutputformanualsuccesslog() {
        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger(true);

        // Start output buffering.
        ob_start();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the file-system logger produces output for a failed record
     * on a manual run
     */
    public function test_fsloggeroutputsformanualfailurelog() {
        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger(true);

        // Start output buffering.
        ob_start();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertNotEquals($output, '');
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * successful record on a scheduled run
     */
    public function test_fsloggerdoesnotoutputforscheduledsuccesslog() {
        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Start output buffering.
        ob_start();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_success('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }

    /**
     * Validate that the file-system logger does not produce output for a
     * failed record on a scheduled run
     */
    public function test_fsloggerdoesnotoutputforscheduledfailurelog() {
        // Set up the logging object.
        list($fslogger, $filename) = $this->get_fs_logger();

        // Start output buffering.
        ob_start();

        // Log a line with all information included.
        $time = time();
        $fslogger->log_failure('Message', $time, 'rliptest', 10);

        $output = ob_get_contents();
        ob_end_clean();

        // Validation.
        $this->assertEquals($output, '');
    }
}
