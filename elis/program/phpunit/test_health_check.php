<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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
 * @subpackage user_activity
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once(elis::file('program/healthpage.class.php'));
require_once(elis::plugin_file('eliscoreplugins_user_activity', 'health.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

class user_activity_health_test extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    protected static function get_overlay_tables() {
        return array(
            'log' => 'moodle',
            'config_plugins' => 'moodle'
        );
    }

    /**
     * Test the user_activity_health_check
     */
    public function testETLbehindmorethanweek() {
        global $DB;
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('log', elis::file('program/phpunit/log_data.csv'));

        $overlaydb = self::$overlaydb;
        load_phpunit_data_set($dataset, true, $overlaydb);

        elis::$config->eliscoreplugins_user_activity->last_run = time() - DAYSECS;
        elis::$config->eliscoreplugins_user_activity->state = serialize(array(
                 "sessiontimeout" => 300,
                 "sessiontail" => 300,
                 "starttime" => time() - (365 * DAYSECS),
                 "startrec" => 1
            ));

        $problem = new user_activity_health_empty();
        $this->assertTrue($problem->exists());

        elis::$config->eliscoreplugins_user_activity->state = serialize(array(
                 "sessiontimeout" => 300,
                 "sessiontail" => 300,
                 "starttime" => time() - (6 * DAYSECS),
                 "startrec" => 1
            ));
        $this->assertFalse($problem->exists());
     }
}
