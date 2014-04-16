<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    dhexport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/csv_delay.class.php');

/**
 * Class for validating that filesystem logging works during exports
 * @group dhexport_version1
 * @group local_datahub
 */
class version1exportfilesystemlogging_testcase extends rlip_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            'grade_items' => $csvloc.'/phpunit_gradeitems.csv',
            'grade_grades' => $csvloc.'/phpunit_gradegrades2.csv',
            'user' => $csvloc.'/phpunit_user2.csv',
            'course' => $csvloc.'/phpunit_course.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Validate that an appropriate filesystem log entry is created if an
     * export runs too long
     */
    public function test_version1exportlogsruntimeerror() {
        global $CFG, $DB;

        // Setup.
        $this->load_csv_data();
        set_config('nonincremental', 1, 'dhexport_version1');

        // Set the filepath to the dataroot.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;

        // No writing actually happens.
        $file = $CFG->dataroot.'/bogus';
        $fileplugin = new rlip_fileplugin_csv_delay($file);

        // Obtain plugin.
        $manual = false;
        $plugin = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin, $manual);
        ob_start();
        $plugin->run(0, 0, 1);
        $ui = ob_get_contents(); // TBD: test this UI string!
        ob_end_clean();

        // Expected error.
        $expectederror = get_string('exportexceedstimelimit', 'local_datahub')."\n";

        // Validate that a log file was created.
        $plugintype = 'export';
        $plugin = 'dhexport_version1';
        $format = get_string('logfile_timestamp', 'local_datahub');
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $filepath.'/'.$plugintype.'_version1_scheduled_'.userdate($starttime, $format).'.log';
        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename));

        // Fetch log line.
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            // No line found.
            $this->assertEquals(0, 1);
        }

        // Data validation.
        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actualerror = substr($line, $prefixlength);
        $this->assertEquals($expectederror, $actualerror);
    }

    /**
     * Test an invalid log file path
     */
    public function test_version1exportinvalidlogpath() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/local/datahub/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib.php');

        set_config('logfilelocation', 'invalidlogpath', 'dhexport_version1');

        $filepath = $CFG->dataroot.'/invalidlogpath';

        // Create a folder and make it executable only.
        mkdir($filepath, 0100);

        // Setup.
        $this->load_csv_data();
        set_config('nonincremental', 1, 'dhexport_version1');

        // No writing actually happens.
        $file = $CFG->dataroot.'/bogus';
        $fileplugin = new rlip_fileplugin_csv_delay($file);

        // Obtain plugin.
        $manual = true;
        $plugin = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin, $manual);
        ob_start();
        $plugin->run();
        $ui = ob_get_contents(); // TBD: test this UI string!
        ob_end_clean();

        // Data validation.
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'Log file access failed during export due to invalid logfile path: invalidlogpath.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        // Cleanup the new folder.
        if (file_exists($filepath)) {
            rmdir($filepath);
        }
        $this->assertEquals($exists, true);
    }

    /**
     * Test that a success log file is created.
     */
    public function test_version1exportsuccesslogcreated() {
        global $CFG, $DB;

        // Setup.
        $this->load_csv_data();
        set_config('nonincremental', 1, 'dhexport_version1');

        // Set the filepath to the dataroot.
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;

        $inputfile = $CFG->dataroot.'/bogus.csv';
        touch($inputfile);
        $fileplugin = new rlip_fileplugin_csv($inputfile);

        // Obtain plugin.
        $manual = true;
        $plugin = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin, $manual);
        $plugin->run(0, 0, 0);

        // Validate that a log file was created.
        $plugintype = 'export';
        $plugin = 'dhexport_version1';
        $format = get_string('logfile_timestamp', 'local_datahub');
        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $filepath.'/'.$plugintype.'_version1_manual_'.userdate($starttime, $format).'.log';
        $filename = self::get_current_logfile($testfilename, true);
        $this->assertTrue(file_exists($filename));

        // Fetch log line.
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        // Data validation.
        $prefixlength = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $expected = "Export file bogus.csv successfully created.\n";
        $actual = substr($line, $prefixlength);
        $this->assertEquals($expected, $actual);
    }
}
