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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

/**
 * Class for testing various functionality related to plugin factories
 * @group local_datahub
 */
class pluginfactories_testcase extends basic_testcase {

    /**
     * Validate that the data plugin factory sets the right file plugin when
     * obtaining an export plugin instance
     */
    public function test_datapluginfactorysetscorrectfilepluginforexport() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');

        // Setup.
        $fileplugin = rlip_fileplugin_factory::factory('bogus');
        $exportplugin = rlip_dataplugin_factory::factory('dhexport_version1', null, $fileplugin);
        // Validation.
        $this->assertEquals($fileplugin, $exportplugin->get_file_plugin());
    }

    /**
     * Validate that the file plugin factory sets the browser flag on the
     * appropriate file plugin
     */
    public function test_filepluginfactorysetsbrowserflag() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fileplugin.class.php');

        // Setup.
        $fileplugin = rlip_fileplugin_factory::factory('', null, false, true);
        // Validation.
        $this->assertEquals($fileplugin->sendtobrowser, true);
    }

    /**
     * Validate that the file-system logger factory constructs an object of the
     * correct type
     */
    public function test_fsloggerfactoryinstantiatescorrectclass() {
        global $CFG;
        require_once($CFG->dirroot.'/local/datahub/lib/rlip_fslogger.class.php');
        $file = get_plugin_directory('dhimport', 'version1').'/rlip_import_version1_fslogger.class.php';
        require_once($file);

        // Setup.
        $fslogger = rlip_fslogger_factory::factory('dhexport_version1', null);
        // Validation.
        $this->assertInstanceOf('rlip_fslogger_linebased', $fslogger);

        // Setup.
        $fslogger = rlip_fslogger_factory::factory('dhimport_version1', null);
        // Validation.
        $this->assertInstanceOf('rlip_import_version1_fslogger', $fslogger);
    }
}