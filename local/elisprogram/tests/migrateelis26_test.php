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

/**
 * Test elis26 migrator.
 */
class migrateelis26_testcase extends elis_database_test {

    /**
     * Test the migrate_contextlevels function.
     */
    public function test_migrate_contextlevels() {
        global $DB;

        // Setup.
        $ctxoldnewmap = array(
            1001 => \local_eliscore\context\helper::get_level_from_name('curriculum'),
            1002 => \local_eliscore\context\helper::get_level_from_name('track'),
            1003 => \local_eliscore\context\helper::get_level_from_name('course'),
            1004 => \local_eliscore\context\helper::get_level_from_name('class'),
            1005 => \local_eliscore\context\helper::get_level_from_name('user'),
            1006 => \local_eliscore\context\helper::get_level_from_name('cluster')
        );
        $createdrecs = array();
        foreach ($ctxoldnewmap as $oldlevel => $newlevel) {
            $context = new \stdClass;
            $context->contextlevel = $oldlevel;
            $context->instanceid = '1'.$oldlevel;
            $context->path = '/2/'.$oldlevel;
            $context->depth = 2;
            $context->id = $DB->insert_record('context', $context);
            $createdrecs['context'][$oldlevel] = $context->id;

            $fieldclevel = new \stdClass;
            $fieldclevel->fieldid = '2'.$oldlevel;
            $fieldclevel->contextlevel = $oldlevel;
            $fieldclevel->id = $DB->insert_record('local_eliscore_field_clevels', $fieldclevel);
            $createdrecs['local_eliscore_field_clevels'][$oldlevel] = $fieldclevel->id;

            $catctx = new \stdClass;
            $catctx->categoryid = '3'.$oldlevel;
            $catctx->contextlevel = $oldlevel;
            $catctx->id = $DB->insert_record('local_eliscore_fld_cat_ctx', $catctx);
            $createdrecs['local_eliscore_fld_cat_ctx'][$oldlevel] = $catctx->id;

            $rolectx = new \stdClass;
            $rolectx->roleid = '3'.$oldlevel;
            $rolectx->contextlevel = $oldlevel;
            $rolectx->id = $DB->insert_record('role_context_levels', $rolectx);
            $createdrecs['role_context_levels'][$oldlevel] = $rolectx->id;
        }

        // Migrate.
        $migrator = new \local_elisprogram\install\migration\elis26();
        $migrator->migrate_context_levels();

        // Check records.
        foreach ($createdrecs as $table => $recs) {
            foreach ($recs as $oldlevel => $recid) {
                $newlevel = $ctxoldnewmap[$oldlevel];
                $rec = $DB->get_record($table, array('id' => $recid));
                $this->assertEquals($newlevel, $rec->contextlevel);
            }
        }
    }

    /**
     * Test migrate capabilities.
     */
    public function test_migrate_capabilities() {
        global $DB;

        // Setup.
        $rolecap = new \stdClass;
        $rolecap->contextid = 3;
        $rolecap->roleid = 4;
        $rolecap->capability = 'elis/program:manage';
        $rolecap->permission = 1;
        $rolecap->timemodified = time();
        $rolecap->modifierid = 5;
        $rolecap->id = $DB->insert_record('role_capabilities', $rolecap);

        // Migrate.
        $migrator = new \local_elisprogram\install\migration\elis26();
        $migrator->migrate_capabilities();

        $rec = $DB->get_record('role_capabilities', array('id' => $rolecap->id));
        $this->assertNotEmpty($rec);
        $this->assertEquals('local/elisprogram:manage', $rec->capability);
    }
}
