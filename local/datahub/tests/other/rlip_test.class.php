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
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once(dirname(__FILE__).'/../../../../local/eliscore/lib/setup.php');
require_once(elis::lib('testlib.php'));

/**
 * This class is handling log maintenance
 */
abstract class rlip_test extends elis_database_test {
    static $existing_csvfiles = array();
    static $existing_logfiles = array();
    static $existing_zipfiles = array();

    /**
     * Do setup before tests.
     */
    protected function setUp() {
        global $CFG;
        $ob = ob_get_level();
        if ($ob > 1) {
            ob_end_clean();
        }

        parent::setUp();

        // Create log directory.
        $dirs = array($CFG->dataroot.'/datahub/', $CFG->dataroot.'/datahub/log/');
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir);
            }
        }
    }

    public static function setUpBeforeClass() {
        static::get_csv_files();
        static::get_logfilelocation_files();
        static::get_zip_files();
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass() {
        static::cleanup_csv_files();
        static::cleanup_log_files();
        static::cleanup_zip_files();
        parent::tearDownAfterClass();
    }

    /**
     * Cleans up files created by PHPunit tests
     *
     * @param string $fext The file extension to clean-up
     */
    protected static function cleanup_test_files($fext) {
        global $CFG;
        // Set the log file location.
        $filepath = $CFG->dataroot;
        $farray = "existing_{$fext}files";
        foreach (glob_recursive("{$filepath}/*.{$fext}") as $file) {
            if (!in_array($file, self::$$farray)) {
                unlink($file);
            }
        }
    }

    /**
     * Gets a list of files to not delete
     *
     * @param string $fext  The file extension to check for existing files
     */
    protected static function get_existing_files($fext) {
        global $CFG;
        // Set the log file location.
        $filepath = $CFG->dataroot;
        $farray = "existing_{$fext}files";
        self::$$farray = array();
        foreach (glob_recursive("{$filepath}/*.{$fext}") as $file) {
            self::${$farray}[] = $file;
        }
    }

    /**
     * Cleans up log files created by PHPunit tests
     */
    public static function cleanup_log_files() {
        self::cleanup_test_files('log');
    }

    /**
     * Gets a list of log files to not delete
     */
    public static function get_logfilelocation_files() {
        self::get_existing_files('log');
    }

    /**
     * Cleans up csv files created by PHPunit tests
     */
    public static function cleanup_csv_files() {
        self::cleanup_test_files('csv');
    }

    /**
     * Gets a list of zip files to not delete
     */
    public static function get_csv_files() {
        self::get_existing_files('csv');
    }

    /**
     * Cleans up zip files created buy PHPunit tests
     */
    public static function cleanup_zip_files() {
        self::cleanup_test_files('zip');
    }

    /**
     * Gets a list of zip files to not delete
     */
    public static function get_zip_files() {
        self::get_existing_files('zip');
    }

    /**
     * Find the most recent log file
     * @param string filename The base filename to find the most recent file
     * @return string newestfile The most current filename
     */
    public static function get_current_logfile($filename, $dbugdump = false) {
        global $CFG;

        $filenameprefix = explode('.', $filename);
        $newestfile = $filename;
        $versions = array();
        $file = glob($CFG->dataroot."/".$filenameprefix[0]."*.log");
        if (is_array($file)) {
            foreach ($file as $fn) {
                if ($fn == $filename || !in_array($fn, self::$existing_logfiles)) {
                    $versions[$fn] = filemtime($fn);
                }
            }
        }

        // Get latest version of the log file if there is more than one version.
        if (!empty($versions)) {
            arsort($versions, SORT_NUMERIC);
            if ($dbugdump) {
                echo "get_current_logfile({$filename}, {$dbugdump}): versions => ";
                var_dump($versions);
            }
            $newestfile = key($versions);
        }
        return $newestfile;
    }

    /**
     * Find the next log file
     * @param string filename The base filename to find the most recent file
     * @return string nextfile The most current filename + 1
     */
    public static function get_next_logfile($filename) {
        $newestfile = self::get_current_logfile($filename);
        $filenameprefix = explode('.', $filename);

        // Generate the 'next' filename.
        if (!file_exists($filename)) {
            $nextfile = $filename;
        } else if ($newestfile == $filename) {
            $nextfile = $filenameprefix[0].'_0.log';
        } else {
            $filenamepart = explode('_', $filenameprefix[0]);
            $count = end($filenamepart);
            $nextfile = $filenameprefix[0].'_'.$count++.'.log';
        }
        return $nextfile;
    }

    /**
     * Finds all of the XMLDB files within a given plugin path and sets up the overlay table array to include
     * the tables defined within those plugins.
     *
     * @param string $path The path to look for modules in
     * @return array An array of extra overlay tables
     */
    protected static function load_plugin_xmldb($path) {
        global $CFG;

        require_once($CFG->libdir.'/ddllib.php');

        $tables = array();

        switch ($path) {
            case 'mod':
                $prefix = 'mod_';
                break;

            case 'course/format':
                $prefix = 'format_';
                break;

            default:
                return array();
        }

        $plugins = get_list_of_plugins($path);

        if ($plugins) {
            foreach ($plugins as $plugin) {
                if (!file_exists($CFG->dirroot.'/'.$path.'/'.$plugin.'/db/install.xml')) {
                    continue;
                }

                // Load the XMLDB file and pull the tables out of the XML strcture.
                $xmldbfile = new xmldb_file($CFG->dirroot.'/'.$path.'/'.$plugin.'/db/install.xml');

                if (!$xmldbfile->fileExists()) {
                    continue;
                }

                $xmldbfile->loadXMLStructure();
                $xmldbstructure = $xmldbfile->getStructure();
                $xmldbtables    = $xmldbstructure->getTables();

                if (!empty($xmldbtables)) {
                    foreach ($xmldbtables as $xmldbtable) {
                        // Add each table to the list of overlay tables.
                        $tables[$xmldbtable->getName()] = $prefix.$plugin;
                    }
                }
            }
        }

        return $tables;
    }
}

/**
 * Test class for running ELIS rlip tests.
 */
abstract class rlip_elis_test extends rlip_test {

    /**
     * Require elis_program classes that we reset custom fields for in setUp.
     */
    public static function setUpBeforeClass() {
        global $CFG;

        parent::setUpBeforeClass();

        // We require these classes here so they're defined when we reset custom fields in setUp.
        // For some reason, requiring these in setUp doesn't not actually define them.. phpunit weirdness?
        if (file_exists(dirname(__FILE__).'/../../../../local/elisprogram')) {
            require_once(dirname(__FILE__).'/../../../../local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/curriculum.class.php'));
            require_once(elispm::lib('data/track.class.php'));
            require_once(elispm::lib('data/course.class.php'));
            require_once(elispm::lib('data/pmclass.class.php'));
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/userset.class.php'));
        }
    }

    /**
     * Do setup before tests.
     */
    protected function setUp() {
        global $CFG;
        // Skip test if ELIS is not installed.
        $elis = (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php') === true) ? true : false;

        if ($elis !== true) {
            $this->markTestSkipped('Test requires ELIS to run.');
        }

        parent::setUp();


        if ($elis === true) {
            // Clear custom field caches.
            $classes = array('curriculum', 'track', 'course', 'pmclass', 'user', 'userset');
            foreach ($classes as $class) {
                $temp = new $class;
                $temp->reset_custom_field_list();
            }
        }
    }
}

abstract class rlip_test_ws extends rlip_test {
    /**
     * Give permissions to the current user.
     * @param array $perms Array of permissions to grant.
     */
    public function give_permissions(array $perms) {
        global $DB;

        accesslib_clear_all_caches(true);

        $syscontext = context_system::instance();

        // Create a user to set ourselves to.
        $assigninguser = new user(array(
            'idnumber' => 'assigninguserid',
            'username' => 'assigninguser',
            'firstname' => 'assigninguser',
            'lastname' => 'assigninguser',
            'email' => 'assigninguser@example.com',
            'country' => 'CA'
        ));
        $assigninguser->save();
        $assigningmuser = $DB->get_record('user', array('username' => 'assigninguser'));
        $this->setUser($assigningmuser);

        // Create duplicate user.
        $dupemailuser = new user(array(
            'idnumber' => 'dupemailuserid',
            'username' => 'dupemailuser',
            'firstname' => 'dupemailuserfirstname',
            'lastname' => 'dupemailuserlastname',
            'email' => 'assigninguser@example.com', // Dup email!
            'country' => 'CA'
        ));
        $dupemailuser->save();

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $assigningmuser->id, $syscontext->id);
    }

    /**
     * Do setup before tests.
     */
    protected function setUp() {
        global $CFG;
        // Skip test if ELIS is not installed.
        $elis = (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php') === true) ? true : false;

        if ($elis !== true) {
            $this->markTestSkipped('Test requires ELIS to run.');
        }

        parent::setUp();
    }
}

if (!function_exists('glob_recursive')) {
    // Does not support flag GLOB_BRACE.
    function glob_recursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        if (is_array($files)) {
            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
            }
        }
        return $files;
    }
}

