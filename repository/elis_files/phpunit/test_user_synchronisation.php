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

require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');


class file_userSynchronisationTest extends elis_database_test {

    private $userstodelete;

    protected static function get_overlay_tables() {
        return array(
            'user' => 'moodle'
        );
    }

    protected function setUp() {
        parent::setUp();

        $this->load_csv_data();
    }

    protected function tearDown() {
        // Remove any users created on the Aflresco server
        if (!empty($this->userstodelete)) {
            foreach ($this->userstodelete as $usertodelete) {
                elis_files_delete_user($usertodelete, true);
            }
        }

        parent::tearDown();
    }

    protected function load_csv_data() {
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', dirname(__FILE__).'/mdluser.csv');
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Test the user migration functionality for creating a user by passing a string containing the username.
     */
    public function testMigrateUserAsString() {
        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        $this->userstodelete[] = '__phpunit_test1__';

        $this->assertTrue($repo->migrate_user('__phpunit_test1__', 'passwords'));
    }


    public function testMigrateInvalidUserAsObject() {
        global $DB;

        if (!$repo = repository_factory::factory('elis_files')) {
            $this->markTestSkipped();
        }

        $this->userstodelete[] = '__phpunit_test1__';

        $user = $DB->get_record('user', array('id' => 100));

        $this->assertTrue($repo->migrate_user($user, 'password'));
    }
}
