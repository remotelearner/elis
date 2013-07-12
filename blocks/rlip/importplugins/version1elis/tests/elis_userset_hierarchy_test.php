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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Unit test for validating all handling of the user set hierarchy and related context functionality.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_userset_hierarchy_testcase extends rlip_elis_test {

    /**
     * Validate hierarchy structure resulting from creation of a top-level user set.
     */
    public function test_top_level_userset_hierarchy_on_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Run the user set create action.
        $record = new stdClass;
        $record->name = 'usersetname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_create($record, 'bogus');

        // Validate userset record.
        $userset = $DB->get_record(userset::TABLE, array('name' => 'usersetname'));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate context record.
        $context = $DB->get_record('context', array('instanceid' => $userset->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $context);
        $this->assertEquals('/1/'.$context->id, $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from creation of a non-top-level
     * user set
     */
    public function test_child_userset_hierarchy_on_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Run the user set create action.
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_create($record, 'bogus');

        // Validate userset record.
        $child = $DB->get_record(userset::TABLE, array('name' => 'childname'));
        $this->assertNotEquals(false, $child);
        $this->assertEquals($parent->id, $child->parent);
        $this->assertEquals(2, $child->depth);

        // Get parent context record.
        $parentctx = $DB->get_record('context', array('instanceid' => $parent->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $parentctx);

        // Validate child context record.
        $childctx = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $childctx);
        $this->assertEquals('/1/'.$parentctx->id.'/'.$childctx->id, $childctx->path);
        $this->assertEquals(3, $childctx->depth);
    }

    /**
     * Validate hierarchy structure resulting from converting a non-top-level
     * user set to a top-level user set
     */
    public function test_promotion_to_top_level_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Run the user set update action.
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'top';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_update($record, 'bogus');

        // Validate userset record.
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate child context record.
        $childctx = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $childctx);
        $this->assertEquals('/1/'.$childctx->id, $childctx->path);
        $this->assertEquals(2, $childctx->depth);
    }

    /**
     * Validate hierarchy structure resulting from converting a top-level
     * user set to a non-top-level user set
     */
    public function test_demotion_to_child_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Create the child userset (at the top level just for now).
        $child = new userset(array('name' => 'childname'));
        $child->save();

        // Run the user set update action.
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_update($record, 'bogus');

        // Validate userset record.
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals($parent->id, $userset->parent);
        $this->assertEquals(2, $userset->depth);

        // Get parent context record.
        $parentctx = $DB->get_record('context', array('instanceid' => $parent->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $parentctx);

        // Validate child context record.
        $childctx = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $childctx);
        $this->assertEquals('/1/'.$parentctx->id.'/'.$childctx->id, $childctx->path);
        $this->assertEquals(3, $childctx->depth);
    }

    /**
     * Validate hierarchy structure resulting from changing the parent of a
     * non-top-level user set from one user set to another
     */
    public function test_changed_parent_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Create the initial parent userset.
        $initialparent = new userset(array('name' => 'initialparentname'));
        $initialparent->save();

        // Create the final parent userset.
        $finalparent = new userset(array('name' => 'finalparentname'));
        $finalparent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $initialparent->id));
        $child->save();

        // Run the user set update action.
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'finalparentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_update($record, 'bogus');

        // Validate userset record.
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(2, $userset->parent);
        $this->assertEquals(2, $userset->depth);

        // Get new parent context record.
        $newparentctx = $DB->get_record('context', array('instanceid' => $finalparent->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $newparentctx);

        // Validate child context record.
        $childctx = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $childctx);
        $this->assertEquals('/1/'.$newparentctx->id.'/'.$childctx->id, $childctx->path);
        $this->assertEquals(3, $childctx->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion of a top-level
     * user set with no children
     */
    public function test_userset_hierarchy_on_standalone_top_level_userset_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create our userset.
        $userset = new userset(array('name' => 'usersetname'));
        $userset->save();

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'usersetname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate deletion of userset record.
        $this->assertEquals((0 + $origusersetcount), $DB->count_records(userset::TABLE));

        // Validate deletion of context record.
        $this->assertEquals((0 + $origcontextcount), $DB->count_records('context'));
    }

    /**
     * Validate hierarchy structure resulting from deletion of a non-top-level
     * user set with no children
     */
    public function test_userset_hierarchy_on_standalone_child_userset_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'childname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate deletion of userset record.
        $this->assertEquals((1 + $origusersetcount), $DB->count_records(userset::TABLE));

        // Validate deletion of context record.
        $this->assertEquals((1 + $origcontextcount), $DB->count_records('context'));
    }

    /**
     * Data provider for testing both the "1" and "yes" values for the recursive
     * deletion flag
     *
     * @return array Data, as expected by the test methods
     */
    public function recursive_provider() {
        return array(
            array(1),
            array('yes'),
            array(0),
            array('no')
        );
    }

    /**
     * Validate hierarchy structure resulting from recursive deletion of a
     * top-level user set with children
     *
     * @param string $recursive The recursive field value
     * @dataProvider recursive_provider
     */
    public function test_userset_hierarchy_on_parent_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive.
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 1 + $origusersetcount;
            $contextcount = 1 + $origcontextcount;
        } else {
            $usersetcount = 0 + $origusersetcount;
            $contextcount = 0 + $origcontextcount;
        }

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'parentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        // Validate deletion of both context record(s).
        $this->assertEquals($contextcount, $DB->count_records('context'));
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a top-level user set with children
     */
    public function test_userset_hierarchy_on_parent_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate deletion of parent userset record.
        $this->assertEquals((1 + $origusersetcount), $DB->count_records(userset::TABLE));

        // Validate promotion of child userset record.
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate deletion of parent context record.
        $this->assertEquals((1 + $origcontextcount), $DB->count_records('context'));

        // Validate promotion of child context record.
        $context = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $context);
        $this->assertEquals('/1/'.$context->id, $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from recursive deletion of a
     * non-top-level user set with children
     *
     * @param string $recursive The recursive field value
     * @dataProvider recursive_provider
     */
    public function test_userset_hierarchy_on_parent_non_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the grandparent userset.
        $grandparent = new userset(array('name' => 'grandparentname'));
        $grandparent->save();

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive.
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 2 + $origusersetcount;
            $contextcount = 2 + $origcontextcount;
        } else {
            $usersetcount = 1 + $origusersetcount;
            $contextcount = 1 + $origcontextcount;
        }

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'parentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate deletion of userset record(s).
        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        // Validate that the "grandparent" userset is the one left intact.
        $userset = $DB->get_record(userset::TABLE, array('id' => $grandparent->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('grandparentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate deletion of context record(s).
        $this->assertEquals($contextcount, $DB->count_records('context'));

        // Validate that the "grandparent" context is the one left intact.
        $context = $DB->get_record('context', array('instanceid' => $grandparent->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $context);
        $this->assertEquals('/1/'.$context->id, $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a non-top-level user set with children
     */
    public function test_userset_hierarchy_on_parent_non_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the grandparent userset.
        $grandparent = new userset(array('name' => 'grandparentname'));
        $grandparent->save();

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate the deletion of the "parent" userset.
        $this->assertEquals((2 + $origusersetcount), $DB->count_records(userset::TABLE));

        // Validate the promotion of the "child" userset.
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate the deletion of the "parent" context.
        $this->assertEquals((2 + $origcontextcount), $DB->count_records('context'));

        // Validate the promotion of the "child" context.
        $context = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $context);
        $this->assertEquals('/1/'.$context->id, $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from recursive deletion of a
     * non-top-level user set with grandchildren
     *
     * @param string $recursive The recursive field value
     * @dataProvider recursive_provider
     */
    public function test_userset_hierarchy_on_grandparent_non_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');

        // Create the great grandparent userset.
        $greatgrandparent = new userset(array('name' => 'greatgrandparentname'));
        $greatgrandparent->save();

        // Create the grandparent userset.
        $grandparent = new userset(array('name' => 'grandparentname', 'parent' => $greatgrandparent->id));
        $grandparent->save();

        // Create the parent userset.
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        // Create the child userset.
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive.
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 3;
            $contextcount = 3 + $origcontextcount;
        } else {
            $usersetcount = 1;
            $contextcount = 1 + $origcontextcount;
        }

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'grandparentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate deletion of userset record(s).
        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        // Validate that the "great grandparent" userset is the one left intact.
        $userset = $DB->get_record(userset::TABLE, array('id' => $greatgrandparent->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('greatgrandparentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate deletion of context record(s).
        $this->assertEquals($contextcount, $DB->count_records('context'));

        // Validate that the "great grandparent" context is the one left intact.
        $context = $DB->get_record('context', array(
            'instanceid' => $greatgrandparent->id,
            'contextlevel' => CONTEXT_ELIS_USERSET
        ));
        $this->assertNotEquals(false, $context);
        $this->assertEquals('/1/'.$context->id, $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a non-top-level user set with grandchildren
     */
    public function test_userset_hierarchy_on_grandparent_non_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $origcontextcount = $DB->count_records('context');
        $origusersetcount = $DB->count_records(userset::TABLE);

        // Create the great grandparent userset.
        $greatgrandparent = new userset(array('name' => 'greatgrandparentname'));
        $greatgrandparent->save();

        // Create the grandparent userset.
        $grandparent = new userset(array(
            'name' => 'grandparentname',
            'parent' => $greatgrandparent->id
        ));
        $grandparent->save();

        // Create the parent userset.
        $parent = new userset(array(
            'name' => 'parentname',
            'parent' => $grandparent->id
        ));
        $parent->save();

        // Create the child userset.
        $child = new userset(array(
            'name' => 'childname',
            'parent' => $parent->id
        ));
        $child->save();

        // Run the user set delete action.
        $record = new stdClass;
        $record->name = 'grandparentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->cluster_delete($record, 'bogus');

        // Validate the deletion of the "grandparent" userset.
        $this->assertEquals((3 + $origusersetcount), $DB->count_records(userset::TABLE));

        // Validate the promotion of the "parent" userset.
        $userset = $DB->get_record(userset::TABLE, array('id' => $parent->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('parentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        // Validate the position of the "child" userset below "parent".
        $userset = $DB->get_record(userset::TABLE, array('id' => $child->id));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals($parent->id, $userset->parent);

        // Validate the deletion of the "grandparent" context.
        $this->assertEquals((3 + $origcontextcount), $DB->count_records('context'));

        // Validate the promotion of the "parent" context.
        $parentctx = $DB->get_record('context', array('instanceid' => $parent->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $parentctx);
        $this->assertEquals('/1/'.$parentctx->id, $parentctx->path);
        $this->assertEquals(2, $parentctx->depth);

        // Validate the position of the "child" context below "parent".
        $childctx = $DB->get_record('context', array('instanceid' => $child->id, 'contextlevel' => CONTEXT_ELIS_USERSET));
        $this->assertNotEquals(false, $childctx);
        $this->assertEquals('/1/'.$parentctx->id.'/'.$childctx->id, $childctx->path);
        $this->assertEquals(3, $childctx->depth);
    }
}