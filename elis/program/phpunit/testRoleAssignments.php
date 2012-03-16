<?php
/**
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
 * @package    elis
 * @subpackage programmanager
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) . '/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot . '/elis/program/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once(elispm::lib('data/curriculum.class.php'));
require_once(elis::lib('data/customfield.class.php'));
require_once(elis::file('core/fields/moodle_profile/custom_fields.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once('PHPUnit/Extensions/Database/DataSet/CsvDataSet.php');

class curriculumCustomFieldsTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    protected static function get_overlay_tables() {
		return array(
            'context' => 'moodle',
            'course' => 'moodle',
            'events_queue' => 'moodle',
            'events_queue_handlers' => 'moodle',
            'user' => 'moodle',
            'user_info_category' => 'moodle',
            'user_info_field' => 'moodle',
            'user_info_data' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'role_assignments' => 'moodle',
            'cache_flags' => 'moodle',
            field_category::TABLE => 'elis_core',
            field_category_contextlevel::TABLE => 'elis_core',
            field::TABLE => 'elis_core',
            field_contextlevel::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            curriculum::TABLE => 'elis_program',
            track::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            coursetemplate::TABLE => 'elis_program',
            pmclass::TABLE => 'elis_program',
            user::TABLE => 'elis_program',
            usermoodle::TABLE => 'elis_program',
            field_owner::TABLE => 'elis_core',
            userset::TABLE => 'elis_program'
        );
    }

    protected function setUp() {
        parent::setUp();
        $this->setUpContextsTable();
        $this->setUpRolesTables();
        $this->load_csv_data();
    }

    /**
     * Set up the contexts table with the minimum that we need.
     */
    private function setUpContextsTable() {
        $syscontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        self::$overlaydb->import_record('context', $syscontext);

        $site = self::$origdb->get_record('course', array('id' => SITEID));
        self::$overlaydb->import_record('course', $site);


        $sitecontext = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE,
                                                                  'instanceid' => SITEID));
        self::$overlaydb->import_record('context', $sitecontext);

        $elis_contexts = context_elis_helper::get_all_levels();
        foreach ($elis_contexts as $context_level) {
            $dbfilter = array('contextlevel' => $context_level);
            $recs = self::$origdb->get_records('context', $dbfilter);
            foreach ($recs as $rec) {
                self::$overlaydb->import_record('context', $rec);
            }
        }
    }

    private function setUpRolesTables() {
        $roles = self::$origdb->get_records('role');
        foreach ($roles as $rolerec) {
            self::$overlaydb->import_record('role', $rolerec);
        }

        $roles_ctxs = self::$origdb->get_records('role_context_levels');
        foreach ($roles_ctxs as $role_ctx) {
            self::$overlaydb->import_record('role_context_levels', $role_ctx);
        }
    }

    protected function load_csv_data() {

        // load initial data from a CSV file
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable('user', elis::component_file('program', 'phpunit/mdluser.csv'));
        $dataset->addTable('user_info_category', elis::component_file('program', 'phpunit/user_info_category.csv'));
        $dataset->addTable('user_info_field', elis::component_file('program', 'phpunit/user_info_field.csv'));
        $dataset->addTable('user_info_data', elis::component_file('program', 'phpunit/user_info_data.csv'));
        $dataset->addTable(user::TABLE, elis::component_file('program', 'phpunit/pmuser.csv'));
        $dataset->addTable(usermoodle::TABLE, elis::component_file('program', 'phpunit/usermoodle.csv'));
        $dataset->addTable(field_category::TABLE, elis::component_file('program', 'phpunit/user_field_category.csv'));
        $dataset->addTable(field::TABLE, elis::component_file('program', 'phpunit/user_field.csv'));
        $dataset->addTable(field_owner::TABLE, elis::component_file('program', 'phpunit/user_field_owner.csv'));
        $dataset = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataset);
        $dataset->addSubStrReplacement('\n', "\n");
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        //load curriculum data
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(curriculum::TABLE, elis::component_file('program', 'phpunit/curriculum.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        //load track data
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(track::TABLE, elis::component_file('program', 'phpunit/track.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        //load course data
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(course::TABLE, elis::component_file('program', 'phpunit/pmcourse.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        //load class data
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(pmclass::TABLE, elis::component_file('program', 'phpunit/pmclass.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);

        //load userset data
        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataset->addTable(userset::TABLE, elis::component_file('program', 'phpunit/userset.csv'));
        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * ELIS-4745: Test for assigning a user a role on a program context
     */
    public function testAssignUserforProgramCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_PROGRAM));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $cur = new curriculum(1, null, array(), false, array(), self::$overlaydb);
        $context = context_elis_program::instance($cur->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }

    /**
     * ELIS-4746: Test for assigning a user a role on a track context
     */
    public function testAssignUserforTrackCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_TRACK));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $trk = new track(1, null, array(), false, array(), self::$overlaydb);
        $context = context_elis_track::instance($trk->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }

    /**
     * ELIS-4747: Test for assigning a user a role on a course context
     */
    public function testAssignUserforCourseCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_COURSE));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $crs = new course(100, null, array(), false, array(), self::$overlaydb);
        $context = context_elis_course::instance($crs->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }

    /**
     * ELIS-4748: Test for assigning a user a role on a course context
     */
    public function testAssignUserforClassCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_CLASS));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $cls = new pmclass(100, null, array(), false, array(), self::$overlaydb);
        $context = context_elis_class::instance($cls->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }

    /**
     * ELIS-4749: Test for assigning a user a role on a user context
     */
    public function testAssignUserforUserCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_USER));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $context = context_elis_user::instance($user->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }

    /**
     * ELIS-4749: Test for assigning a user a role on a user context
     */
    public function testAssignUserforUsersetCTX() {
        //get role to assign (we'll just take the first one returned)
        $roles_ctx = self::$overlaydb->get_records('role_context_levels',array('contextlevel' => CONTEXT_ELIS_USERSET));
        foreach ($roles_ctx as $role_ctx) {
            $roleid = $role_ctx->roleid;
            break;
        }

        //get user to assign role
        $user = new user(103, null, array(), false, array(), self::$overlaydb);

        //get specific context
        $usrset = new userset(1, null, array(), false, array(), self::$overlaydb);
        $context = context_elis_userset::instance($usrset->id);

        //assign role
        $raid = role_assign($roleid, cm_get_moodleuserid($user->id), $context->id);

        //assert
        $this->assertNotEmpty($raid);
    }
}
