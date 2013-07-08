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
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/lib/phpunittestlib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_exportplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/csv_delay.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Class for validating that filesystem logging works during exports
 */
class version1ExportFilesystemLoggingTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        return array('config_plugins' => 'moodle',
                     'grade_items' => 'moodle',
                     'grade_grades' => 'moodle',
                     'user' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'elis_scheduled_tasks' => 'elis_core',
                     RLIP_SCHEDULE_TABLE => 'block_rlip',
                     RLIP_LOG_TABLE => 'block_rlip');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'context' => 'moodle');
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
	    $dataset->addTable('grade_items', dirname(__FILE__).'/phpunit_gradeitems.csv');
	    $dataset->addTable('grade_grades', dirname(__FILE__).'/phpunit_gradegrades2.csv');
	    $dataset->addTable('user', dirname(__FILE__).'/phpunit_user2.csv');
	    $dataset->addTable('course', dirname(__FILE__).'/phpunit_course.csv');
	    $dataset->addTable('course_categories', dirname(__FILE__).'/phpunit_course_categories.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Validate that an appropriate filesystem log entry is created if an
     * export runs too long
     */
    public function testVersion1ExportLogsRuntimeError() {
        global $CFG, $DB;

        //setup
        $this->load_csv_data();
        set_config('nonincremental', 1, 'rlipexport_version1');

        //set the filepath to the dataroot
        $filepath = $CFG->dataroot . RLIP_DEFAULT_LOG_PATH;

        //no writing actually happens
        $file = $CFG->dataroot.'/bogus';
        $fileplugin = new rlip_fileplugin_csv_delay($file);

        //obtain plugin
        $manual = false;
        $plugin = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin, $manual);
        ob_start();
        $plugin->run(0, 0, 1);
        $ui = ob_get_contents(); // TBD: test this UI string!
        ob_end_clean();

        //expected error
        $expected_error = get_string('exportexceedstimelimit', 'block_rlip')."\n";

        //validate that a log file was created
        $plugin_type = 'export';
        $plugin = 'rlipexport_version1';
        $format = get_string('logfile_timestamp','block_rlip');
        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }
        $testfilename = $filepath .'/' .$plugin_type .'_version1_scheduled_'.
                        userdate($starttime, $format) .'.log';
        $filename = self::get_current_logfile($testfilename);
        $this->assertTrue(file_exists($filename));

        //fetch log line
        $pointer = fopen($filename, 'r');
        $line = fgets($pointer);
        fclose($pointer);

        if ($line == false) {
            //no line found
            $this->assertEquals(0, 1);
        }

        //data validation
        $prefix_length = strlen('[MMM/DD/YYYY:hh:mm:ss -zzzz] ');
        $actual_error = substr($line, $prefix_length);
        $this->assertEquals($expected_error, $actual_error);
    }

    /**
     * Test an invalid log file path
     */
    function testVersion1ExportInvalidLogPath() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        set_config('logfilelocation', 'invalidlogpath', 'rlipexport_version1');

        $filepath = $CFG->dataroot.'/invalidlogpath';

        //create a folder and make it executable only
        mkdir($filepath, 0100);

        //setup
        $this->load_csv_data();
        set_config('nonincremental', 1, 'rlipexport_version1');

        //no writing actually happens
        $file = $CFG->dataroot.'/bogus';
        $fileplugin = new rlip_fileplugin_csv_delay($file);

        //obtain plugin
        $manual = true;
        $plugin = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin, $manual);
        ob_start();
        $plugin->run();
        $ui = ob_get_contents(); // TBD: test this UI string!
        ob_end_clean();

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'Log file access failed during export due to invalid logfile path: invalidlogpath.');
        $exists = $DB->record_exists_select(RLIP_LOG_TABLE, $select, $params);

        //cleanup the new folder
        if (file_exists($filepath)) {
            rmdir($filepath);
        }
        $this->assertEquals($exists, true);
    }
}
