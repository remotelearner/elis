<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

// Constants for error states
define('ELIS_FILES_ERROR_NONE', 0);
define('ELIS_FILES_ERROR_FTP', 1);
define('ELIS_FILES_ERROR_WS', 2);
define('ELIS_FILES_ERROR_UPLOAD_PERMS', 3);

/**
 * Class for storing a single ELIS Files error state, and polling the message
 * tied to that particular state
 */
class elis_files_logger {
    // Maintain a single logger instance
    private static $instance = NULL;

    // Error state, defauling to no error
    private $state = ELIS_FILES_ERROR_NONE;

    /**
     * Obtain the single instance of the elis_files_logger class using the
     * singleton pattern
     *
     * @return object Our single elis_files_logger object
     */
    public static function instance() {
        if (self::$instance == NULL) {
            // Instantiate our instance
            self::$instance = new elis_files_logger();
        }

        return self::$instance;
    }

    /**
     * Use a private constructor to prevent multiple instances from being
     * instantiated
     */
    private function __construct() {
    }

    /**
     * Signal an error, based on its code constant
     * 
     * @param int $error_code One of the ELIS_FILES_ERROR_* constants, representing
     *                        the type of error that happened
     */
    public function signal_error($error_code) {
        $this->state = $error_code;
    }

    /**
     * Obtain the error message corresponding to this logger's current state
     *
     * @return mixed The string corresponding to the current error, or false if
     *               currently in the success state
     */
    public function get_error_message() {
        switch ($this->state) {
            case ELIS_FILES_ERROR_FTP:
                $config = get_config('elisfiles');

                $uri = parse_url($config->server_host);
                $a = $uri['host'].':'.$config->ftp_port;

                return get_string('errorftpinvalidport', 'repository_elisfiles', $a);
                break;
            case ELIS_FILES_ERROR_WS:
                return get_string('errorupload', 'repository_elisfiles');
                break;
            case ELIS_FILES_ERROR_UPLOAD_PERMS:
                // Insufficient permissions to upload
                // TODO: use a more specific error message?
                return get_string('errorupload', 'repository_elisfiles');
                break;
        }

        // No error
        return false;
    }

    /**
     * Reset this logger to the default success state
     */
    public function flush() {
        $this->state = ELIS_FILES_ERROR_NONE;
    }
}
