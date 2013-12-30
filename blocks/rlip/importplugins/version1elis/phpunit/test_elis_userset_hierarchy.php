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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Unit test for validating all handling of the user set hierarchy and related
 * context functionality
 */
class elis_userset_hierarchy_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/clustercurriculum.class.php'));
        require_once(elispm::lib('data/clustertrack.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));

        return array(
            'block_instances' => 'moodle',
            'block_positions' => 'moodle',
            'cache_flags' => 'moodle',
            'comments' => 'moodle',
            'config_plugins' => 'moodle',
            'context' => 'moodle',
            'files' => 'moodle',
            'filter_active' => 'moodle',
            'filter_config' => 'moodle',
            'grading_areas' => 'moodle',
            'rating' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'role_names' => 'moodle',
            field_data_int::TABLE => 'elis_core',
            field_data_num::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            clusterassignment::TABLE => 'elis_program',
            clustercurriculum::TABLE => 'elis_program',
            clustertrack::TABLE => 'elis_program',
            curriculumstudent::TABLE => 'elis_program',
            track::TABLE => 'elis_program',
            trackassignment::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            userset::TABLE => 'elis_program',
            usertrack::TABLE => 'elis_program',
            userset_profile::TABLE => 'elis_program'
        );
    }

    /**
     * Create the system context based on the record from the original database
     */
    function create_system_context() {
        global $DB;

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
    }

    /**
     * Clear the in-memory context cache
     */
    function clear_context_cache() {
        accesslib_clear_all_caches(true);
    }

    /**
     * Validate hierarchy structure resulting from creation of a top-level
     * user set
     */
    function test_top_level_userset_hierarchy_on_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //run the user set create action
        $record = new stdClass;
        $record->name = 'usersetname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_create($record, 'bogus');

        //validate userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 1));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('usersetname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate context record
        $context = $DB->get_record('context', array('id' => 2));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(1, $context->instanceid);
        $this->assertEquals('/1/2', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from creation of a non-top-level
     * user set
     */
    function test_child_userset_hierarchy_on_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //run the user set create action
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_create($record, 'bogus');

        //validate userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 2));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(1, $userset->parent);
        $this->assertEquals(2, $userset->depth);

        //validate context record
        $context = $DB->get_record('context', array('id' => 3));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(2, $context->instanceid);
        $this->assertEquals('/1/2/3', $context->path);
        $this->assertEquals(3, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from converting a non-top-level
     * user set to a top-level user set
     */
    function test_promotion_to_top_level_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        //run the user set update action
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'top';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_update($record, 'bogus');

        //validate userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 2));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate context record
        $context = $DB->get_record('context', array('id' => 3));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(2, $context->instanceid);
        $this->assertEquals('/1/3', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from converting a top-level
     * user set to a non-top-level user set
     */
    function test_demotion_to_child_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //create the child userset (at the top level just for now)
        $child = new userset(array('name' => 'childname'));
        $child->save();

        //run the user set update action
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_update($record, 'bogus');

        //validate userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 2));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(1, $userset->parent);
        $this->assertEquals(2, $userset->depth);

        //validate context record
        $context = $DB->get_record('context', array('id' => 3));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(2, $context->instanceid);
        $this->assertEquals('/1/2/3', $context->path);
        $this->assertEquals(3, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from changing the parent of a
     * non-top-level user set from one user set to another
     */
    function test_changed_parent_userset_hierarchy_on_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the initial parent userset
        $initialparent = new userset(array('name' => 'initialparentname'));
        $initialparent->save();

        //create the final parent userset
        $finalparent = new userset(array('name' => 'finalparentname'));
        $finalparent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $initialparent->id));
        $child->save();

        //run the user set update action
        $record = new stdClass;
        $record->name = 'childname';
        $record->parent = 'finalparentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_update($record, 'bogus');

        //validate userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 3));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(2, $userset->parent);
        $this->assertEquals(2, $userset->depth);

        //validate context record
        $context = $DB->get_record('context', array('id' => 4));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(3, $context->instanceid);
        $this->assertEquals('/1/3/4', $context->path);
        $this->assertEquals(3, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion of a top-level
     * user set with no children
     */
    function test_userset_hierarchy_on_standalone_top_level_userset_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create our userset
        $userset = new userset(array('name' => 'usersetname'));
        $userset->save();

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'usersetname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate deletion of userset record
        $this->assertEquals(0, $DB->count_records(userset::TABLE));

        //validate deletion of context record
        $this->assertEquals(1, $DB->count_records('context'));
    }

    /**
     * Validate hierarchy structure resulting from deletion of a non-top-level
     * user set with no children
     */
    function test_userset_hierarchy_on_standalone_child_userset_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'childname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate deletion of userset record
        $this->assertEquals(1, $DB->count_records(userset::TABLE));

        //validate deletion of context record
        $this->assertEquals(2, $DB->count_records('context'));
    }

    /**
     * Data provider for testing both the "1" and "yes" values for the recursive
     * deletion flag
     *
     * @return array Data, as expected by the test methods
     */
    function recursive_provider() {
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
    function test_userset_hierarchy_on_parent_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 1;
            $contextcount = 2;
        } else {
            $usersetcount = 0;
            $contextcount = 1;
        }

        //run the user set delete action
        $record = new stdClass;
        $record->name      = 'parentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        //validate deletion of both context record(s)
        $this->assertEquals($contextcount, $DB->count_records('context'));
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a top-level user set with children
     */
    function test_userset_hierarchy_on_parent_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname'));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate deletion of parent userset record
        $this->assertEquals(1, $DB->count_records(userset::TABLE));
        //validate promotion of child userset record
        $userset = $DB->get_record(userset::TABLE, array('id' => 2));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate deletion of parent context record
        $this->assertEquals(2, $DB->count_records('context'));
        //validate promotion of child context record
        $context = $DB->get_record('context', array('id' => 3));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(2, $context->instanceid);
        $this->assertEquals('/1/3', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from recursive deletion of a
     * non-top-level user set with children
     *
     * @param string $recursive The recursive field value
     * @dataProvider recursive_provider
     */
    function test_userset_hierarchy_on_parent_non_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the grandparent userset
        $grandparent = new userset(array('name' => 'grandparentname'));
        $grandparent->save();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 2;
            $contextcount = 3;
        } else {
            $usersetcount = 1;
            $contextcount = 2;
        }

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'parentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate deletion of userset record(s)
        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        //validate that the "grandparent" userset is the one left intact
        $userset = $DB->get_record(userset::TABLE, array('id' => 1));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('grandparentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate deletion of context record(s)
        $this->assertEquals($contextcount, $DB->count_records('context'));

        //validate that the "grandparent" context is the one left intact
        $context = $DB->get_record('context', array('id' => 2));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(1, $context->instanceid);
        $this->assertEquals('/1/2', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a non-top-level user set with children
     */
    function test_userset_hierarchy_on_parent_non_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the grandparent userset
        $grandparent = new userset(array('name' => 'grandparentname'));
        $grandparent->save();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'parentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate the deletion of the "parent" userset
        $this->assertEquals(2, $DB->count_records(userset::TABLE));
        //validate the promotion of the "child" userset
        $userset = $DB->get_record(userset::TABLE, array('id' => 3));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate the deletion of the "parent" context
        $this->assertEquals(3, $DB->count_records('context'));
        //validate the promotion of the "child" context
        $context = $DB->get_record('context', array('id' => 4));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(3, $context->instanceid);
        $this->assertEquals('/1/4', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from recursive deletion of a
     * non-top-level user set with grandchildren
     *
     * @param string $recursive The recursive field value
     * @dataProvider recursive_provider
     */
    function test_userset_hierarchy_on_grandparent_non_top_level_userset_recursive_delete($recursive) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the great grandparent userset
        $greatgrandparent = new userset(array('name' => 'greatgrandparentname'));
        $greatgrandparent->save();

        //create the grandparent userset
        $grandparent = new userset(array('name' => 'grandparentname', 'parent' => $greatgrandparent->id));
        $grandparent->save();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname', 'parent' => $grandparent->id));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname', 'parent' => $parent->id));
        $child->save();

        // Determine counts to check for after we run the delete -- depending on the value of recursive
        if ($recursive === 0 || $recursive === 'no') {
            $usersetcount = 3;
            $contextcount = 4;
        } else {
            $usersetcount = 1;
            $contextcount = 2;
        }

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'grandparentname';
        $record->recursive = $recursive;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate deletion of userset record(s)
        $this->assertEquals($usersetcount, $DB->count_records(userset::TABLE));

        //validate that the "great grandparent" userset is the one left intact
        $userset = $DB->get_record(userset::TABLE, array('id' => 1));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('greatgrandparentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);

        //validate deletion of context record(s)
        $this->assertEquals($contextcount, $DB->count_records('context'));

        //validate that the "great grandparent" context is the one left intact
        $context = $DB->get_record('context', array('id' => 2));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(1, $context->instanceid);
        $this->assertEquals('/1/2', $context->path);
        $this->assertEquals(2, $context->depth);
    }

    /**
     * Validate hierarchy structure resulting from deletion and child user set
     * promotion of a non-top-level user set with grandchildren
     */
    function test_userset_hierarchy_on_grandparent_non_top_level_userset_delete_and_promote() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('accesslib.php'));

        //create the system context
        $this->create_system_context();

        //clear context cache
        $this->clear_context_cache();

        //create the great grandparent userset
        $greatgrandparent = new userset(array('name' => 'greatgrandparentname'));
        $greatgrandparent->save();

        //create the grandparent userset
        $grandparent = new userset(array('name' => 'grandparentname',
                                         'parent' => $greatgrandparent->id));
        $grandparent->save();

        //create the parent userset
        $parent = new userset(array('name' => 'parentname',
                                    'parent' => $grandparent->id));
        $parent->save();

        //create the child userset
        $child = new userset(array('name' => 'childname',
                                   'parent' => $parent->id));
        $child->save();

        //run the user set delete action
        $record = new stdClass;
        $record->name = 'grandparentname';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->cluster_delete($record, 'bogus');

        //validate the deletion of the "grandparent" userset
        $this->assertEquals(3, $DB->count_records(userset::TABLE));
        //validate the promotion of the "parent" userset
        $userset = $DB->get_record(userset::TABLE, array('id' => 3));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('parentname', $userset->name);
        $this->assertEquals(0, $userset->parent);
        $this->assertEquals(1, $userset->depth);
        //validate the position of the "child" userset below "parent"
        $userset = $DB->get_record(userset::TABLE, array('id' => 4));
        $this->assertNotEquals(false, $userset);
        $this->assertEquals('childname', $userset->name);
        $this->assertEquals(3, $userset->parent);
        //re-add this check once ELIS-6540 is resolved
        //$this->assertEquals(2, $userset->depth);

        //validate the deletion of the "grandparent" context
        $this->assertEquals(4, $DB->count_records('context'));
        //validate the promotion of the "parent" context
        $context = $DB->get_record('context', array('id' => 4));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(3, $context->instanceid);
        $this->assertEquals('/1/4', $context->path);
        $this->assertEquals(2, $context->depth);
        //validate the position of the "child" context below "parent"
        $context = $DB->get_record('context', array('id' => 5));
        $this->assertNotEquals(false, $context);
        $this->assertEquals(CONTEXT_ELIS_USERSET, $context->contextlevel);
        $this->assertEquals(4, $context->instanceid);
        $this->assertEquals('/1/4/5', $context->path);
        $this->assertEquals(3, $context->depth);
    }
}