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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

/**
 * Base class for Integration Point export plugins
 */
abstract class rlip_exportplugin_base extends rlip_dataplugin {
    //track the file being used for export
    var $fileplugin;
    var $fslogger = null;
    var $plugin;
    //type of import, true if manual
    var $manual = false;

    //methods to be implemented in specific export

    /**
     * Hook for performing any initialization that should
     * be done at the beginning of the export
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     */
    abstract function init($targetstarttime = 0, $lastruntime = 0);

    /**
     * Hook for specifiying whether more data remains to be exported
     * within the current run
     *
     * @return boolean true if there is more data, otherwise false
     */
    abstract function has_next();

    /**
     * Hook for exporting the next data record in-place
     *
     * @return array The next record to be exported
     */
    abstract function next();

    /**
     * Hook for performing any cleanup that should
     * be done at the end of the export
     */
    abstract function close();

    /**
     * Hook for performing any final actions depending on export result
     * @param   bool  $result   The state of the export, true => success
     * @return  mixed           State info on failure, or null for success.
     */
    function finish($result) {
        // default no actions, overload in derived classes as needed.
        return null;
    }

    /**
     * Default export plugin constructor
     *
     * @param object $fileplugin the file plugin used for output
     * @param boolean $manual  Set to true if a manual run
     */
    function __construct($fileplugin, $manual = false) {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dblogger.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');

        $this->fileplugin = $fileplugin;
        $this->manual = $manual;
        $this->dblogger = new rlip_dblogger_export($this->manual);

        //convert class name to plugin name
        $class = get_class($this);
        $this->plugin = str_replace('rlip_exportplugin_', 'dhexport_', $class);

        //track the start time as the current time - moved from run() in order to support rlip_log_file_name
        $this->dblogger->set_starttime(time());

        //set up the file-system logger, if exists
        $filepath = get_config($this->plugin, 'logfilelocation');
        $filename = rlip_log_file_name('export', $this->plugin, $filepath, '', $manual, $this->dblogger->starttime);
        if (!empty($filename)) {
            $this->dblogger->set_log_path($filename);
            $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true, $manual);
            $this->fslogger = rlip_fslogger_factory::factory($this->plugin, $fileplugin, $this->manual);
        }

        $this->dblogger->set_plugin($this->plugin);
    }

    /**
     * Mainline for export processing
     *
     * @param int $targetstarttime The timestamp representing the theoretical
     *                             time when this task was meant to be run
     * @param int $lastruntime     The last time the export was run
     *                             (required for incremental scheduled export)
     * @param int $maxruntime      The max time in seconds to complete export
     *                             default: 0 => unlimited
     * @param object $state        Previous ran state data to continue from
     *                             (currently not used for export)
     * @return mixed object        Current state of export processing
     *                             or null on success!
     *         ->result            false on error, i.e. time limit exceeded.
     */
    public function run($targetstarttime = 0, $lastruntime = 0, $maxruntime = 0, $state = null) {
        global $CFG;

        $csvfilename = $this->fileplugin->get_filename();

        // Check that the file directory is valid.
        $filepath = get_config($this->dblogger->plugin, 'logfilelocation');
        if (!$writable = is_writable($CFG->dataroot.'/'.$filepath)) {
            // Invalid folder specified for the logfile.
            $this->fslogger->set_logfile_status(false);
            $this->dblogger->set_logfile_status(false);
            $this->dblogger->flush($csvfilename);
            return false;
        } else {
            $this->fslogger->set_logfile_status(true);
            $this->dblogger->set_logfile_status(true);
        }

        // Check that the export file and filepath are valid.
        $exportbase = get_config($this->plugin, 'export_file');
        $exportpath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (get_config($this->plugin, 'export_path')) {
            $exportpath .= rtrim(trim(get_config($this->plugin, 'export_path'), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }
        $outfile = $exportpath.$exportbase;

        if ((!file_exists($exportpath) && !@mkdir($exportpath, 0777, true))
            || (file_exists($exportpath) && !is_writable($exportpath))) {
            $message = "Export file {$exportbase} cannot be processed because the folder: {$exportpath} is not accessible. Please fix the export path.";
            $this->fslogger->log_failure($message, 0, $outfile);
            $this->dblogger->set_endtime(time());
            $this->dblogger->set_exportpath_error($message);
            $this->dblogger->flush($csvfilename);
            return null;
        }

        // Track the provided target start time.
        $this->dblogger->set_targetstarttime($targetstarttime);

        // Open the output file for writing.
        $this->fileplugin->open(RLIP_FILE_WRITE);

        // Perform any necessary setup.
        $this->init($targetstarttime, $lastruntime);

        // Run the main export process.
        $result = $this->export_records($maxruntime);

        // Clean up.
        $this->close();

        // Close the output file.
        $this->fileplugin->close();

        // Track the end time as the current time.
        $this->dblogger->set_endtime(time());

        // Create log file.
        $message = "Export file ".$csvfilename." successfully created.";
        $this->fslogger->log_success($message, 0, $outfile);

        // Flush db log record.
        $this->dblogger->flush($csvfilename);

        // Perform any final actions depending on export outcome.
        return $this->finish($result);
    }

    /**
     * Main loop for handling the body of the export
     *
     * @param int $maxruntime  The max time in seconds to complete export
     * @return bool            true on success, false if time limit exceeded
     */
    function export_records($maxruntime = 0) {
        $starttime = time();
        while ($this->has_next()) {
            // check if time limit exceeded
            if ($maxruntime && (time() - $starttime) > $maxruntime) {
                // time limit exceeded - abort with log message
                $this->dblogger->signal_maxruntime_exceeded();
                if ($this->fslogger) {
                    $msg = get_string($this->manual
                                      ? 'manualexportexceedstimelimit'
                                      : 'exportexceedstimelimit', 'local_datahub');
                    $this->fslogger->log_failure($msg);
                }
                return false;
            }
            //fetch and write out the next record
            $record = $this->next();
            $this->fileplugin->write($record);
            $this->dblogger->track_success(true, true);
        }
        return true;
    }

    /**
     * Getter for the file plugin used for IP by this export plugin
     *
     * @return object The file plugin instance used for IO by this export
     */
    function get_file_plugin() {
        return $this->fileplugin;
    }

}
