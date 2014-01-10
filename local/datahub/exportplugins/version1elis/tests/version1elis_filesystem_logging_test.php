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
 * @package    dhexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once($CFG->dirroot.'/local/datahub/lib.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/course.class.php'));
    require_once(elispm::lib('data/pmclass.class.php'));
    require_once(elispm::lib('data/student.class.php'));
    require_once(elispm::lib('data/user.class.php'));
}

/**
 * Class for testing export filesystem logging for the "Version 1 ELIS" plugin
 * @group local_datahub
 * @group dhexport_version1elis
 */
class version1elisfilesytemlogging_testcase extends rlip_elis_test {

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data() {
        $csvloc = dirname(__FILE__).'/fixtures';
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => $csvloc.'/pmcourse.csv',
            pmclass::TABLE => $csvloc.'/pmclass.csv',
            student::TABLE => $csvloc.'/student.csv',
            user::TABLE => $csvloc.'/pmuser.csv',
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Data provider used to specify whether a run is scheduled or manual
     *
     * @return array An array containing a false value representing a scheduled run,
     *               and a true representing a manual run, along with the expected error
     */
    public function dataprovider_exporttype() {
        return array(
                array(false, get_string('exportexceedstimelimit', 'local_datahub')."\n"),
                array(true, get_string('manualexportexceedstimelimit', 'local_datahub')."\n")
        );
    }

    /**
     * Validate that an appropriate error is logged when maximum runtime is
     * exceeded during a manual or scheduled export
     *
     * @param bool $manual True if the run should be manual, or false for scheduled
     * @param string $expectederror The error we are expecting to find in the log file
     * @dataProvider dataprovider_exporttype
     */
    public function test_filesystemlogginglogsruntimeexceeded($manual, $expectederror) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
        require_once($CFG->dirroot.'/local/datahub/exportplugins/version1elis/tests/other/rlip_fileplugin_export.class.php');

        // Setup.
        $this->load_csv_data();
        set_config('nonincremental', 1, 'dhexport_version1elis');
        $filepath = $CFG->dataroot.RLIP_DEFAULT_LOG_PATH;
        set_config('export_path', '/datahub/dhexport_version1elis', 'dhexport_version1elis');
        set_config('export_file', 'dhexport_version1elis.csv', 'dhexport_version1elis');

        $fileplugin = new rlip_fileplugin_export();
        $plugin = rlip_dataplugin_factory::factory('dhexport_version1elis', null, $fileplugin, $manual);
        // Suppress output.
        ob_start();
        $plugin->run(0, 0, -1);
        ob_end_clean();

        // Get most recent record.
        $records = $DB->get_records(RLIP_LOG_TABLE, null, 'starttime DESC');
        foreach ($records as $record) {
            $starttime = $record->starttime;
            break;
        }

        // Validate log file existence existence.
        // Set the filepath to the dataroot.
        $plugintype = 'export';
        $format = get_string('logfile_timestamp', 'local_datahub');
        $logtype = $manual ? 'manual' : 'scheduled';

        $testfilename = $filepath.'/'.$plugintype.'_version1elis_'.$logtype.'_'.userdate($starttime, $format).'.log';
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
}