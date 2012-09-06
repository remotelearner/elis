<?php
/**
 *
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
if (file_exists($CFG->dirroot .'/repository/elis_files/')) {
    require_once($CFG->dirroot .'/repository/elis_files/lib.php');
    require_once($CFG->dirroot .'/repository/elis_files/lib/lib.php');
    require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
}
define('ELIS_FILES_PREFIX', 'elis_files_test_folder_');

class Search extends elis_database_test {

    protected static function get_overlay_tables() {
        return array(
            'config_plugins' => 'moodle'
        );
    }

    protected static function get_ignore_tables() {
        return array(
            'repository' => 'moodle',
            'repository_instance' => 'moodle'
        );
    }

    protected function setUp() {
        parent::setUp();

        $rs = self::$origdb->get_recordset('config_plugins', array('plugin' => 'elis_files'));

        if ($rs->valid()) {
            foreach ($rs as $setting) {
                self::$overlaydb->import_record('config_plugins', $setting);
            }
            $rs->close();
        }

        $USER = get_admin();
        $GLOBALS['USER'] = $USER;
    }

    public static function tearDownAfterClass() {
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->folders as $folder) {
                if (strpos($folder->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($folder->uuid);
                    break 1;
                }
            }
        }

        parent::tearDownAfterClass();
    }

    protected function tearDown() {
        if ($dir = elis_files_read_dir()) {
            foreach ($dir->folders as $folder) {
                if (strpos($folder->title, ELIS_FILES_PREFIX) === 0) {
                    elis_files_delete($folder->uuid);
                    break 1;
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Test that searching for folders does not return results
     */
    public function testFolderSearch() {
        global $CFG, $DB;
        // Check for ELIS_files repository
        if (file_exists($CFG->dirroot .'/repository/elis_files/')) {
            // RL: ELIS files: Alfresco
            $data = null;
            $listing = null;
            $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE r.type=? AND i.typeid=r.id';
            $repository = $DB->get_record_sql($sql, array('elis_files'));
            if ($repository) {
                try {
                    $repo = @new repository_elis_files('elis_files',
                                get_context_instance(CONTEXT_SYSTEM),
                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));
                } catch (Exception $e) {
                    $this->markTestSkipped();
               }
            } else {
                $this->markTestSkipped();
            }
        } else {
            $this->markTestSkipped();
        }

        $parent_folder_uuid = $repo->elis_files->get_root()->uuid;

        $folder = ELIS_FILES_PREFIX.'1';
        $repo->elis_files->create_dir($folder, $parent_folder_uuid, '', true);

        $result = $repo->search($folder);

        $this->assertEmpty($result['list']);
    }

}
