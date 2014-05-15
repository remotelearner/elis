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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

function local_elisprogram_tests_migrate_upgradestep() {
    global $DB;

    $rec = new \stdClass;
    $rec->plugin = 'testcomponent';
    $rec->name = 'upgradestepran';
    $rec->value = true;
    $DB->insert_record('config_plugins', $rec);
}

/**
 * Test migrator.
 */
class migrate_testcase extends elis_database_test {

    /**
     * Test checking for the old component.
     */
    public function test_old_component_installed() {
        global $DB;

        $oldcomponent = 'testcomponent';
        $migrator = new \local_eliscore\install\migration\migrator('testcomponent', 'newcomponent');
        $this->assertFalse($migrator->old_component_installed());

        // Test correct component search.
        $rec = new \stdClass;
        $rec->name = 'version';
        $rec->value = 20140213;
        $rec->plugin = 'testcomponent2';
        $rec->id = $DB->insert_record('config_plugins', $rec);
        $this->assertFalse($migrator->old_component_installed());

        // Test correct config name search.
        $rec = new \stdClass;
        $rec->name = 'version2';
        $rec->value = 20140213;
        $rec->plugin = 'testcomponent';
        $rec->id = $DB->insert_record('config_plugins', $rec);
        $this->assertFalse($migrator->old_component_installed());

        // Test correct version test.
        $rec = new \stdClass;
        $rec->name = 'version';
        $rec->value = '';
        $rec->plugin = 'testcomponent';
        $rec->id = $DB->insert_record('config_plugins', $rec);
        $this->assertFalse($migrator->old_component_installed());
        $DB->delete_records('config_plugins', array('id' => $rec->id));

        // Test successful check.
        $rec = new \stdClass;
        $rec->name = 'version';
        $rec->value = 20140213;
        $rec->plugin = 'testcomponent';
        $rec->id = $DB->insert_record('config_plugins', $rec);
        $this->assertTrue($migrator->old_component_installed());
    }

    /**
     * Test migrating settings.
     */
    public function test_migrate_settings() {
        global $DB;

        $oldcomponent = 'oldcomponent';
        $newcomponent = 'newcomponent';

        // A setting present in old not present in new.
        $setting = new \stdClass;
        $setting->plugin = $oldcomponent;
        $setting->name = 'configname1';
        $setting->value = 'valuefromold1';
        $DB->insert_record('config_plugins', $setting);

        // A setting present in old and new.
        $setting = new \stdClass;
        $setting->plugin = $oldcomponent;
        $setting->name = 'configname2';
        $setting->value = 'valuefromold2';
        $DB->insert_record('config_plugins', $setting);
        $setting = new \stdClass;
        $setting->plugin = $newcomponent;
        $setting->name = 'configname2';
        $setting->value = 'valuefromnew2';
        $DB->insert_record('config_plugins', $setting);

        // Version settings.
        $setting = new \stdClass;
        $setting->plugin = $oldcomponent;
        $setting->name = 'version';
        $setting->value = 20130213;
        $DB->insert_record('config_plugins', $setting);
        $setting = new \stdClass;
        $setting->plugin = $newcomponent;
        $setting->name = 'version';
        $setting->value = 20140213;
        $DB->insert_record('config_plugins', $setting);

        $migrator = new \local_eliscore\install\migration\migrator($oldcomponent, $newcomponent);
        $migrator->migrate_settings();

        // There should be no old config records present.
        $this->assertEmpty($DB->get_records('config_plugins', array('plugin' => $oldcomponent)));

        // Assert value only present in old component was migrated.
        $rec = $DB->get_record('config_plugins', array('plugin' => $newcomponent, 'name' => 'configname1'));
        $this->assertNotEmpty($rec);
        $this->assertEquals('valuefromold1', $rec->value);

        // Assert value present in old and new components is set to value from old component.
        $rec = $DB->get_record('config_plugins', array('plugin' => $newcomponent, 'name' => 'configname2'));
        $this->assertNotEmpty($rec);
        $this->assertEquals('valuefromold2', $rec->value);

        // Assert new component version config was not touched.
        $rec = $DB->get_record('config_plugins', array('plugin' => $newcomponent, 'name' => 'version'));
        $this->assertNotEmpty($rec);
        $this->assertEquals(20140213, $rec->value);
    }

    /**
     * Test migrate tables.
     */
    public function test_migrate_tables() {
        global $DB;
        $dbman = $DB->get_manager();

        // Rename some tables so they can be set back by the migrator.
        $oldtable = new \xmldb_table('local_elisprogram_cls');
        $dbman->rename_table($oldtable, 'local_elisprogram_aaa');
        $oldtable = new \xmldb_table('local_elisprogram_cls_enrol');
        $dbman->rename_table($oldtable, 'local_elisprogram_bbb');

        // Ensure tables have been renamed.
        $tables = $DB->get_tables(false);
        $this->assertFalse(isset($tables['local_elisprogram_cls']));
        $this->assertFalse(isset($tables['local_elisprogram_cls_enrol']));
        $this->assertTrue(isset($tables['local_elisprogram_aaa']));
        $this->assertTrue(isset($tables['local_elisprogram_bbb']));

        // Run migrator.
        $oldcomponent = 'oldcomponent';
        $newcomponent = 'newcomponent';
        $tablechanges = array(
            'local_elisprogram_aaa' => 'local_elisprogram_cls',
            'local_elisprogram_bbb' => 'local_elisprogram_cls_enrol'
        );
        $migrator = new \local_eliscore\install\migration\migrator($oldcomponent, $newcomponent, '', $tablechanges);
        $migrator->migrate_tables();

        // Check tables.
        $tables = $DB->get_tables(false);
        $this->assertTrue(isset($tables['local_elisprogram_cls']));
        $this->assertTrue(isset($tables['local_elisprogram_cls_enrol']));
        $this->assertFalse(isset($tables['local_elisprogram_aaa']));
        $this->assertFalse(isset($tables['local_elisprogram_bbb']));
    }

    /**
     * Test upgrade step runner.
     */
    public function test_migrate_runupgradesteps() {
        global $DB;

        // Ensure indicator record not present.
        $this->assertEmpty($DB->get_record('config_plugins', array('plugin' => 'testcomponent', 'name' => 'upgradestepran')));

        // Run migration.
        $oldcomponent = 'oldcomponent';
        $newcomponent = 'newcomponent';
        $upgradestepfuncname = 'local_elisprogram_tests_migrate_upgradestep';
        $migrator = new \local_eliscore\install\migration\migrator($oldcomponent, $newcomponent, $upgradestepfuncname);
        $migrator->run_old_upgrade_steps_if_necessary();

        // Upgrade step should not have run as we have not "installed" oldcomponent.
        $this->assertEmpty($DB->get_record('config_plugins', array('plugin' => 'testcomponent', 'name' => 'upgradestepran')));

        // Add version for testcomponent.
        $rec = new \stdClass;
        $rec->plugin = 'oldcomponent';
        $rec->name = 'version';
        $rec->value = 20140213;
        $DB->insert_record('config_plugins', $rec);

        // Run migration.
        $oldcomponent = 'oldcomponent';
        $newcomponent = 'newcomponent';
        $upgradestepfuncname = 'local_elisprogram_tests_migrate_upgradestep';
        $migrator = new \local_eliscore\install\migration\migrator($oldcomponent, $newcomponent, $upgradestepfuncname);
        $migrator->run_old_upgrade_steps_if_necessary();

        // Ensure indicator record present.
        $this->assertNotEmpty($DB->get_record('config_plugins', array('plugin' => 'testcomponent', 'name' => 'upgradestepran')));
    }
}
