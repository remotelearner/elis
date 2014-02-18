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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

require_once(elis::file('elisprogram/dashboardpage.class.php'));

/**
 * Test ELIS Dashboard component info methods
 * @group local_elisprogram
 */
class elisdashboard_testcase extends elis_database_test {

    /**
     * DataProvider for method test_get_jquery_info
     * @return array (array($files), array($infostrings), array($expected))
     */
    public function get_jquery_info_dataprovider() {
        return array(
                array(array('jquery-1.6.2.min.js'), array('name' => 'jquery'), array('jquery', '1.6.2', '')),
                array(array('jquery-1.6.2.js'), array('name' => 'jquery'), array('jquery', '1.6.2', '')),
                array(array('jquery-ui-1.8.16.custom.js'), array('name' => 'jquery-ui'), array('jquery-ui', '1.8.16', '')),
                array(array('jquery-ui-1.8.16.js'), array('name' => 'jquery-ui'), array('jquery-ui', '1.8.16', '')),
                array(array('jquery-1.9.1.min.js'), array('name' => 'jquery'), array('jquery', '1.9.1', '')),
                array(array('jquery-1.9.1.js'), array('name' => 'jquery'), array('jquery', '1.9.1', '')),
                array(array('jquery-ui-1.10.1.custom.js'), array('name' => 'jquery-ui'), array('jquery-ui', '1.10.1', '')),
                array(array('jquery-ui-1.10.1.js'), array('name' => 'jquery-ui'), array('jquery-ui', '1.10.1', ''))
        );
    }

    /**
     * Validate that dashboard method for getting ELIS jquery library version
     * @param array $files list of files to get info from
     * @param array $infostrings associative array of default values, i.e. array('name' => 'Name', 'version' => 'Version', 'release' => 'Release date')
     * @param array $expected array of expected info strings
     * @dataProvider get_jquery_info_dataprovider
     */
    public function test_get_jquery_info($files, $infostrings, $expected) {
        $dashbrd = new dashboardpage();
        list($componentname, $release, $version) = $dashbrd->get_jquery_file_info($files, $infostrings);
        $this->assertEquals($expected[0], $componentname);
        $this->assertEquals($expected[1], $release);
        $this->assertEquals($expected[2], $version);
    }
}
