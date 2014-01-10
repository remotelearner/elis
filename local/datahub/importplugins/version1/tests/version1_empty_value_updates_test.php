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
 * @package    dhimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

/**
 * Class for testing how Version 1 deals with "empty" values in updates
 * @group local_datahub
 * @group dhimport_version1
 */
class version1emptyvalueupdates_testcase extends rlip_test {

    /**
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $this->assertTrue($exists);
    }

    /**
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for users
     */
    public function test_version1userupdateignoresemptyvalues() {
        global $CFG, $DB;

        set_config('createorupdate', 0, 'dhimport_version1');

        // Create, then update a user.
        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'create',
                    'username' => 'rlipusername',
                    'password' => 'Rlippassword!0',
                    'firstname' => 'rlipfirstname',
                    'lastname' => 'rliplastname',
                    'email' => 'rlipuser@rlipdomain.com',
                    'city' => 'rlipcity',
                    'country' => 'CA',
                    'idnumber' => 'rlipidnumber'
                ),
                array(
                    'entity' => 'user',
                    'action' => 'update',
                    'username' => 'rlipusername',
                    'password' => '',
                    'firstname' => 'updatedrlipfirstname',
                    'lastname' => '',
                    'email' => '',
                    'city' => '',
                    'country' => '',
                    'idnumber' => ''
                )
        );
        $provider = new rlipimport_version1_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        // Validation.
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'updatedrlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA',
            'idnumber' => 'rlipidnumber'
        );
        $this->assert_record_exists('user', $params);

        // Validate password.
        $userrec = $DB->get_record('user', array('username' => $params['username']));
        $this->assertTrue(validate_internal_user_password($userrec, 'Rlippassword!0'));
    }

    /**
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for courses
     */
    public function test_version1courseupdateignoresemptyvalues() {
        global $CFG, $DB;

        set_config('createorupdate', 0, 'dhimport_version1');

        // New config settings required by course format refactoring in 2.4.
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

        // Create, then update a course.
        $data = array(
                array(
                    'entity' => 'course',
                    'action' => 'create',
                    'shortname' => 'rlipshortname',
                    'fullname' => 'rlipfullname',
                    'category' => 'rlipcategory'
                ),
                array(
                    'entity' => 'course',
                    'action' => 'update',
                    'shortname' => 'rlipshortname',
                    'fullname' => '',
                    'category' => 'updatedrlipcategory'
                )
        );
        $provider = new rlipimport_version1_importprovider_emptycourse($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        // Category validation.
        $this->assert_record_exists('course_categories', array('name' => 'updatedrlipcategory'));
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'updatedrlipcategory'));

        // Main validation.
        $params = array(
            'shortname' => 'rlipshortname',
            'fullname' => 'rlipfullname',
            'category' => $categoryid
        );
        $this->assert_record_exists('course', $params);
    }

    /**
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for enrolments
     */
    public function test_version1enrolmentcreateanddeleteignoreemptyvalues() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        set_config('createorupdate', 0, 'dhimport_version1');
        set_config('gradebookroles', '');

        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');

        // Create category.
        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        // Create course.
        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $category->id;

        $course = create_course($course);

        // Create user.
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';
        $user->idnumber = 'rlipidnumber';

        $user->id = user_create_user($user);
        set_config('siteguest', 99999);

        // Create role.
        $context = context_course::instance($course->id);
        $roleid = create_role('rliprole', 'rliprole', 'rliprole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));
        $syscontext = context_system::instance();
        assign_capability('moodle/course:view', CAP_ALLOW, $roleid, $syscontext->id);

        // Create an enrolment.
        $data = array(
                array(
                    'entity' => 'enrolment',
                    'action' => 'create',
                    'username' => 'rlipusername',
                    'email' => '',
                    'idnumber' => '',
                    'context' => 'course',
                    'instance' => 'rlipshortname',
                    'role' => 'rliprole'
                )
        );

        $provider = new rlipimport_version1_importprovider_emptyenrolment($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        $this->assert_record_exists('role_assignments', array(
            'userid' => $user->id,
            'contextid' => $context->id,
            'roleid' => $roleid
        ));

        // Delete an enrolment.
        $data[0]['action'] = 'delete';

        $provider = new rlipimport_version1_importprovider_emptyenrolment($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        // Validation.
        $exists = $DB->record_exists('role_assignments', array(
            'userid' => $user->id,
            'contextid' => $context->id,
            'roleid' => $roleid
        ));
        $this->assertFalse($exists);
    }

    /**
     * Validates that the version 1 update ignores empty fields that could
     * potentially be used to identify a user
     */
    public function test_version1userupdateignoresemptyidentifyingfields() {
        global $CFG;

        require_once($CFG->dirroot.'/user/lib.php');

        // Create a user.
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';
        $user->idnumber = 'rlipidnumber';

        $user->id = user_create_user($user);

        // Update a user with blank email and idnumber.
        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'update',
                    'username' => 'rlipusername',
                    'email' => '',
                    'idnumber' => '',
                    'firstname' => 'updatedrlipfirstname1'
                )
        );

        $provider = new rlipimport_version1_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        // Validation.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber',
            'email' => 'rlipuser@rlipdomain.com',
            'firstname' => 'updatedrlipfirstname1'
        ));

        // Update a user with a blank username.
        $data = array(
                array(
                    'entity' => 'user',
                    'action' => 'update',
                    'username' => '',
                    'email' => 'rlipuser@rlipdomain.com',
                    'idnumber' => 'rlipidnumber',
                    'firstname' => 'updatedrlipfirstname2'
                )
        );

        $provider = new rlipimport_version1_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1', $provider);
        $importplugin->run();

        // Validation.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber',
            'email' => 'rlipuser@rlipdomain.com',
            'firstname' => 'updatedrlipfirstname2'
        ));
    }
}