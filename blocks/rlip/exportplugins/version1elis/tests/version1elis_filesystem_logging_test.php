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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

/**
 * Class for testing export filesystem logging for the "Version 1 ELIS" plugin
 */
class version1elisDatabaseLoggingTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array(
            'config_plugins' => 'moodle',
            RLIP_LOG_TABLE   => 'block_rlip',
            course::TABLE    => 'elis_program',
            pmclass::TABLE   => 'elis_program',
            student::TABLE   => 'elis_program',
            user::TABLE      => 'elis_program'
        );
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

	    $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        //data
        $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourse.csv');
        $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclass.csv');
        $dataset->addTable(student::TABLE, dirname(__FILE__).'/student.csv');
        $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmuser.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Data provider used to specify whether a run is scheduled or manual
     *
     * @return array An array containing a false value representing a scheduled run,
     *               and a true representing a manual run, along with the expected error
     */
    public function exportTypeProvider() {
        return array(
            array(false, get_string('exportexceedstimelimit', 'block_rlip')."\n"),
            array(true, get_string('manualexportexceedstimelimit', 'block_rlip')."\n")
        );
    }

    /**
     * Validate that an appropriate error is logged when maximum runtime is
     * exceeded during a manual or scheduled export
     *
     * @param boolean $manual True if the run should be manual, or false for
     *                        scheduled
     * @param string $expected_error The error we are expecting to find in the log file
     * @dataProvider exportTypeProvider
     */
    public function testFilesystemLoggingLogsRuntimeExceeded($manual, $expected_error) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/phpunit/rlip_fileplugin_export.class.php');

        //setup
        $this->load_csv_data();
        set_config('nonincremental', 1, 'rlipexport_version1elis');
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        set_config('export_path', '/rlip/rlipexport_version1elis', 'rlipexport_version1elis');
        set_config('export_file', 'rlipexport_version1elis.csv', 'rlipexport_version1elis');

        $fileplugin = new rlip_fileplugin_export();
        $plugin = rlip_dataplugin_factory::factory('rlipexport_version1elis', NULL, $fileplugin, $manual);
        //suppress output
        ob_start();
        $plugin->run(0, 0, -1);
        ob_end_clean();

        //get most recent record
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        //validate log file existence existence
        //set the filepath to the dataroot
        $plugin_type = 'export';
        $format = get_string('logfile_timestamp','block_rlip');
        $logtype = $manual ? 'manual' : 'scheduled';

        $testfilename = $filepath.'/'.$plugin_type.'_version1elis_'.$logtype.'_'.
                        userdate($starttime, $format).'.log';
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
}