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
require_once($CFG->dirroot .'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

/**
 * Class for validating that log files are correctly archived in all
 * scenarios
 */
class archiveLogFileTest extends rlip_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle'
        );
    }

    /**
     * Data provider that provides import-plugin-specific information for testing
     * of archiving log files
     *
     * @return array The parameter data expected by the test method, including
     *               the import plugin and the log path
     */
    public function importPluginProvider() {
        global $CFG;

        $runs = array(
            array('import', 'rlipimport_version1', ''),
            array('import', 'rlipimport_version1', RLIP_DEFAULT_LOG_PATH),
            array('export', 'rlipexport_version1', ''),
            array('export', 'rlipexport_version1', RLIP_DEFAULT_LOG_PATH),
        );

        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            //add the PM plugins if applicable
            $pm_runs = array(
                array('import', 'rlipimport_version1elis', ''),
                array('import', 'rlipimport_version1elis', RLIP_DEFAULT_LOG_PATH),
                array('export', 'rlipexport_version1elis', ''),
                array('export', 'rlipexport_version1elis', RLIP_DEFAULT_LOG_PATH),
            );

            $runs = array_merge($runs, $pm_runs);
        }

        return $runs;
    }

    /**
     * Validate that log files are archived for a variety of import and
     * export plugins, for a variety of configured log paths
     *
     * @param string $plugin_type One of 'import' or 'export'
     * @param string $plugin The import plugin to associate log files to
     * @param string $logfilelocation The logfilelocation setting value to use
     * @dataProvider importPluginProvider
     */
    public function testLogFilesArchived($plugin_type, $plugin, $logfilelocation) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/fileplugins/log/log.class.php');
        require_once($CFG->libdir .'/filestorage/zip_archive.php');

        // clean-up any existing log & zip files
        self::cleanup_log_files();
        self::cleanup_zip_files();

        // set up the log path
        set_config('logfilelocation', $logfilelocation, $plugin);

        $format = get_string('logfile_timestamp', 'block_rlip');

        $USER->timezone = 99;
        // create some log files to be zipped by the cron job
        // Way earlier then any real existing files!
        $starttime = make_timestamp(1971, 1, 3);
        $filenames = array();
        for ($i = 0; $i < 10; ++$i) {
            $filenames[$i] = rlip_log_file_name($plugin_type, $plugin, $logfilelocation, 'user',
                                           false, $starttime + $i * 3600);
            //write out a line to the logfile
            $logfile = new rlip_fileplugin_log($filenames[$i]);
            $logfile->open(RLIP_FILE_WRITE);
            $logfile->write(array('test entry'));
            $logfile->close();
        }

        //call cron job that zips the specified day's log files
        $zipfiles = rlip_compress_logs_cron('bogus', 0, $starttime);
        $this->assertTrue(!empty($zipfiles));

        //was a zip file created?
        //verify that the compressed file exists
        $exists = file_exists($zipfiles[0]);
        $this->assertTrue($exists);

        //open zip_archive and verify all logs included
        $zip = new zip_archive();
        $result = $zip->open($zipfiles[0]);
        $this->assertTrue($result);
        $this->assertEquals(10, $zip->count());
        $zip->close();

        //verify that the log files created are gone...
        for ($i = 0; $i < 10; ++$i) {
            $exists = file_exists($filenames[$i]);
            $this->assertFalse($exists);
        }

        //validate that the zip file name corresponds to the plugin
        //e.g. pugin is 'rlipimport_version1' and file name starts with 'import_version1_'
        $parts = explode('/', $zipfiles[0]);
        $this->assertStringStartsWith($plugin.'_', 'rlip'.$parts[count($parts) - 1]);

        // delete the test zip
        @unlink($zipfiles[0]);
    }
}