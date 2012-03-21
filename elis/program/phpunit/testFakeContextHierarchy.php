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
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('data/track.class.php'));


class curriculumCustomFieldsTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobalsBlacklist = array('DB');

    private $tprogramid;
    private $ttrackid;
    private $tcourseid;
    private $classid;
    private $tuserid;
    private $tusersetid;
    private $mdluserid;
    private $tableids = array(
        'cache_flags' => 0,
        'context' => 0,
        'course' => 0,
        'crlm_course' => 0,
        'crlm_class' => 0,
        'crlm_cluster' => 0,
        'crlm_curriculum' => 0,
        'crlm_track' => 0,
        'crlm_user' => 0,
        'crlm_user_moodle' => 0,
        'role_assignments' => 0,
        'role_capabilities' => 0,
        'user' => 0
    );

    protected function setUp() {
        global $DB;

        // Get the maximum record ID for each of the tables we are going to modify in these tests
        foreach ($this->tableids as $tablename => $id) {
            if ($records = $DB->get_records($tablename, array(), 'id DESC', '*', 0, 1)) {
                $this->tableids[$tablename] = current($records)->id;
            }
        }

        // Ensure that the editing teacher role has a specific capapbility enabled
        $syscontext = context_system::instance();
        assign_capability('elis/program:userset_enrol_userset_user', CAP_ALLOW, 3, $syscontext);
        $syscontext->mark_dirty();

        // Initialise testing data
        $this->initProgram();
        $this->initTrack($this->tprogramid);
        $this->initCourse();
        $this->initClass($this->tcourseid);
        $this->initUser();
        $this->initUserset();
    }

    protected function tearDown() {
        global $DB;

        // Remove any new data that we have added to datbase tables
        foreach ($this->tableids as $tablename => $id) {
            if ($id > 0) {
                $DB->delete_records_select($tablename, 'id > :id', array('id' => $id));
            } else {
                $DB->delete_records($tablename);
            }
        }
    }

    /**
     * Initialize a new program object
     */
    private function initProgram() {
        $data = array(
            'idnumber' => '__fcH__TESTID001__',
            'name'     => 'Test Program 1'
        );

        $newprogram = new curriculum($data);
        $newprogram->save();
        $this->tprogramid = $newprogram->id;
    }

    /**
     * Initialize a new track object
     *
     * @param integer $curid A curriculum record ID
     */
    private function initTrack($curid) {
        $data = array(
            'curid'    => $curid,
            'idnumber' => '__fcH__TESTID001__',
            'name'     => 'Test Track 1'
        );

        $newtrack = new track($data);
        $newtrack->save();
        $this->ttrackid = $newtrack->id;
    }

    /**
     * Initialize a new course description object
     */
    private function initCourse() {
        $data = array(
            'idnumber' => '__fcH__TESTID001__',
            'name'     => 'Test Course 1',
            'syllabus' => ''  // For some reason this field needs to be defined, or INSERT fails?!
        );

        $newcourse = new course($data);
        $newcourse->save();
        $this->tcourseid = $newcourse->id;
    }

    /**
     * Initialize a new class object
     *
     * @param integer $courseid A course record ID
     */
    private function initClass($courseid) {
        $data = array(
            'idnumber' => '__fcH__TESTID001__',
            'courseid' => $courseid
        );

        $newclass = new pmclass($data);
        $newclass->save();
        $this->tclassid = $newclass->id;
    }

    /**
     * Initialize a new user description object
     */
    private function initUser() {
        global $CFG, $DB;

        $data = array(
            'idnumber'  => '__fcH__TESTID001__',
            'username'  => '__fcH__testuser1__',
            'firstname' => 'Test',
            'lastname'  => 'User1',
            'email'     => 'testuser1@example.com',
            'country'   => 'us'
        );

        $newuser = new user($data);
        $newuser->save();
        $this->tuserid = $newuser->id;

        $usernew = new stdClass;
        $usernew->username    = '__fcH__testuser__';
        $usernew->firstname   = 'Test';
        $usernew->lastname    = 'User';
        $usernew->email       = 'testuser@example.com';
        $usernew->confirmed   = 1;
        $usernew->auth        = 'manual';
        $usernew->mnethostid  = $CFG->mnet_localhost_id;
        $usernew->confirmed   = 1;
        $usernew->timecreated = time();
        $usernew->password    = hash_internal_user_password('testpassword');

        $this->mdluserid = $DB->insert_record('user', $usernew);
    }

    /**
     * Initialize a new user description object
     */
    private function initUserset() {
        $data = array(
            'name'    => 'Test User Set 1',
            'display' => 'We\'re just testing user set creation!'
        );

        $newuserset = new userset($data);
        $newuserset->save();
        $this->tusersetid = $newuserset->id;
    }

    public function testProgramCapabilityCheck() {
        $ctx = context_elis_program::instance($this->tprogramid);
        $this->assertGreaterThan(0, role_assign(3, $this->mdluserid, $ctx->id));

        // Validate the return value when looking at the 'curriculum' level
        $contexts_curriculum = new pm_context_set();
        $contexts_curriculum->contexts = array(
            'curriculum' => array($this->tprogramid)
        );
        $contexts_curriculum->contextlevel = 'curriculum';

        $contexts = pm_context_set::for_user_with_capability('curriculum', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_curriculum, $contexts);

        // Validate the return value when looking at the 'track' level
        $contexts_track = new pm_context_set();
        $contexts_track->contexts = array(
            'curriculum' => array($this->tprogramid)
        );
        $contexts_track->contextlevel = 'track';

        $contexts = pm_context_set::for_user_with_capability('track', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_track, $contexts);

        // Validate the return value when looking at the 'course' level
        $contexts_course = new pm_context_set();
        $contexts_course->contexts = array(
            'curriculum' => array($this->tprogramid)
        );
        $contexts_course->contextlevel = 'course';

        $contexts = pm_context_set::for_user_with_capability('course', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_course, $contexts);

        // Validate the return value when looking at the 'class' level
        $contexts_class = new pm_context_set();
        $contexts_class->contexts = array(
            'curriculum' => array($this->tprogramid)
        );
        $contexts_class->contextlevel = 'class';

        $contexts   = pm_context_set::for_user_with_capability('class', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_class, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('curriculum', $this->tprogramid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }

    public function testTrackCapabilityCheck() {
        // Assign the test user the editing teacher role on a test curriculum
        $ctx = context_elis_track::instance($this->ttrackid);
        $this->assertNotEmpty(role_assign(3, $this->mdluserid, $ctx->id));

        // Validate the return value when looking at the 'track' level
        $contexts_track = new pm_context_set();
        $contexts_track->contexts = array(
            'track' => array($this->ttrackid)
        );
        $contexts_track->contextlevel = 'track';

        $contexts = pm_context_set::for_user_with_capability('track', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_track, $contexts);

        // Validate the return value when looking at the 'class' level
        $contexts_class = new pm_context_set();
        $contexts_class->contexts = array(
            'track' => array($this->ttrackid)
        );
        $contexts_class->contextlevel = 'class';

        $contexts   = pm_context_set::for_user_with_capability('class', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_class, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('track', $this->ttrackid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }

    public function testCourseCapabilityCheck() {
        // Assign the test user the editing teacher role on a test curriculum
        $ctx = context_elis_course::instance($this->tcourseid);
        $this->assertNotEmpty(role_assign(3, $this->mdluserid, $ctx->id));

        // Validate the return value when looking at the 'course' level
        $contexts_course = new pm_context_set();
        $contexts_course->contexts = array(
            'course' => array($this->tcourseid)
        );
        $contexts_course->contextlevel = 'course';

        $contexts = pm_context_set::for_user_with_capability('course', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_course, $contexts);

        // Validate the return value when looking at the 'class' level
        $contexts_class = new pm_context_set();
        $contexts_class->contexts = array(
            'course' => array($this->tcourseid)
        );
        $contexts_class->contextlevel = 'class';

        $contexts = pm_context_set::for_user_with_capability('class', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_class, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('course', $this->tcourseid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }

    public function testClassCapabilityCheck() {
        // Assign the test user the editing teacher role on a test curriculum
        $ctx = context_elis_class::instance($this->tclassid);
        $this->assertNotEmpty(role_assign(3, $this->mdluserid, $ctx->id));

        // Validate the return value when looking at the 'class' level
        $contexts_class = new pm_context_set();
        $contexts_class->contexts = array(
            'class' => array($this->tclassid)
        );
        $contexts_class->contextlevel = 'class';

        $contexts = pm_context_set::for_user_with_capability('class', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_class, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('class', $this->tclassid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }

    public function testUsersetCapabilityCheck() {
        // Assign the test user the editing teacher role on a test cluster
        $ctx = context_elis_userset::instance($this->tusersetid);
        $this->assertNotEmpty(role_assign(3, $this->mdluserid, $ctx->id));

         // Validate the return value when looking at the 'cluster' level
        $contexts_cluster = new pm_context_set();
        $contexts_cluster->contexts = array(
            'cluster' => array($this->tusersetid)
        );
        $contexts_cluster->contextlevel = 'cluster';

        $contexts = pm_context_set::for_user_with_capability('cluster', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_cluster, $contexts);

         // Validate the return value when looking at the 'user' level
        $contexts_user = new pm_context_set();
        $contexts_user->contexts = array(
            'cluster' => array($this->tusersetid)
        );
        $contexts_user->contextlevel = 'user';

        $contexts = pm_context_set::for_user_with_capability('user', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_user, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('cluster', $this->tusersetid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }

    public function testUserCapabilityCheck() {
        // Assign the test user the editing teacher role on a test cluster
        $ctx = context_elis_user::instance($this->tuserid);
        $this->assertNotEmpty(role_assign(3, $this->mdluserid, $ctx->id));

         // Validate the return value when looking at the 'user' level
        $contexts_user = new pm_context_set();
        $contexts_user->contexts = array(
            'user' => array($this->tuserid)
        );
        $contexts_user->contextlevel = 'user';

        $contexts = pm_context_set::for_user_with_capability('user', 'elis/program:userset_enrol_userset_user', $this->mdluserid);
        $this->assertEquals($contexts_user, $contexts);

        // Validate checking for users with the given capability on this context
        $users = pm_get_users_by_capability('user', $this->tuserid, 'elis/program:userset_enrol_userset_user');
        $this->assertEquals($this->mdluserid, current($users)->id);
    }
}
