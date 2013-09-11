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

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
global $CFG;

/**
 * Class for testing the CSV file plugin
 */
class filepluginCSVTest extends PHPUnit_Framework_TestCase {
    /**
     * Validate that the CSV file plugin handles empty lines correctly
     */
    function testFilepluginCSVHandlesEmptyLines() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        $file = get_plugin_directory('rlipfile', 'csv').'/csv.class.php';
        require_once($file);

        //fileplugin instance
        $inputfile = $CFG->dirroot.'/blocks/rlip/fileplugins/phpunit/blankline.csv';
        $fileplugin = new rlip_fileplugin_csv($inputfile);
        $fileplugin->open(RLIP_FILE_READ);

        //simple data validation
        $headerline = $fileplugin->read();
        $this->assertEquals($headerline, array('header1', 'header2'));
        $dataline = $fileplugin->read();
        $this->assertEquals($dataline, array('nextline', 'isblank'));

        //line with just a newline character
        $emptyline = $fileplugin->read();
        $this->assertEquals($emptyline, false);

        //line with no content
        $finalline = $fileplugin->read();
        $this->assertEquals($finalline, false);
    }
}