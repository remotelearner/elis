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

/**
 * Class for testing various functionality related to plugin factories
 */
class pluginFactoriesTest extends PHPUnit_Framework_TestCase {

    /**
     * Validate that the data plugin factory sets the right file plugin when
     * obtaining an export plugin instance
     */
    public function testDatapluginFactorySetsCorrectFilepluginForExport() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

        //setup
        $fileplugin = rlip_fileplugin_factory::factory('bogus');
        $exportplugin = rlip_dataplugin_factory::factory('rlipexport_version1', NULL, $fileplugin);
        //validation
        $this->assertEquals($fileplugin, $exportplugin->get_file_plugin());
    }

    /**
     * Validate that the file plugin factory sets the browser flag on the
     * appropriate file plugin
     */ 
    public function testFilepluginFactorySetsBrowserFlag() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

        //setup
        $fileplugin = rlip_fileplugin_factory::factory('', NULL, false, true);
        //validation
        $this->assertEquals($fileplugin->sendtobrowser, true);
    }

    /**
     * Validate that the file-system logger factory constructs an object of the
     * correct type
     */
    public function testFsloggerFactoryInstantiatesCorrectClass() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');
        $file = get_plugin_directory('rlipimport', 'version1').'/rlip_import_version1_fslogger.class.php';
        require_once($file);

        //setup
        $fslogger = rlip_fslogger_factory::factory('rlipexport_version1', NULL);
        //validation
        $this->assertInstanceOf('rlip_fslogger_linebased', $fslogger);

        //setup
        $fslogger = rlip_fslogger_factory::factory('rlipimport_version1', NULL);
        //validation
        $this->assertInstanceOf('rlip_import_version1_fslogger', $fslogger);
    }
}