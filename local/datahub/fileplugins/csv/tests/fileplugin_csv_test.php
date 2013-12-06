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
 * @package    rlipfile_csv
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

/**
 * Class for testing the CSV file plugin
 * @group block_rlip
 * @group rlipfile_csv
 */
class fileplugincsv_testcase extends rlip_test {
    /**
     * Validate that the CSV file plugin handles empty lines correctly
     */
    public function test_fileplugincsvhandlesemptylines() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        $file = get_plugin_directory('rlipfile', 'csv').'/csv.class.php';
        require_once($file);

        // Fileplugin instance.
        $inputfile = dirname(__FILE__).'/fixtures/blankline.csv';
        $fileplugin = new rlip_fileplugin_csv($inputfile);
        $fileplugin->open(RLIP_FILE_READ);

        // Simple data validation.
        $headerline = $fileplugin->read();
        $this->assertEquals($headerline, array('header1', 'header2'));
        $dataline = $fileplugin->read();
        $this->assertEquals($dataline, array('nextline', 'isblank'));

        // Line with just a newline character.
        $emptyline = $fileplugin->read();
        $this->assertEquals($emptyline, false);

        // Line with no content.
        $finalline = $fileplugin->read();
        $this->assertEquals($finalline, false);
    }
}