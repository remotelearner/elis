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

/**
 * Class for validating that IP action trigger appropriate notifications that
 * would normally be sent
 */
class elis_notifications_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array('config' => 'moodle',
                     'config_plugins' => 'moodle',
                     'context' => 'moodle',
                     'message' => 'moodle',
                     'user' => 'moodle',
                     //to prevent accidental association
                     classmoodlecourse::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     user::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array(coursetemplate::TABLE => 'elis_program',
                     usermoodle::TABLE => 'elis_program');
    }

    /**
     * Validating that enrolling a user in a class instance triggers the enrolment
     * notification
     */
    public function test_class_enrolment_sends_class_enrolment_notification() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        //configuration
        set_config('notify_classenrol_user', 1, 'elis_program');
        $message = '%%userenrolname%% has been enrolled in the class instance %%classname%%.';
        set_config('notify_classenrol_message', $message, 'elis_program');
        set_config('noemailever', 1);

        //setup
        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'test@user.com',
                               'country' => 'CA'));
        $user->save();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //run the enrolment create action
        $record = new stdClass;
        $record->context = 'class_testclassidnumber';
        $record->user_username = 'testuserusername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->class_enrolment_create($record, 'bogus', 'testclassidnumber');

        //validation
        $mdluserid = $DB->get_field('user', 'id', array('username' => 'testuserusername'));
        $expected_message = "{$user->firstname} {$user->lastname} has been enrolled in the class instance {$class->idnumber}.";

        $like = $DB->sql_like('fullmessagehtml', ':message');
        $select = "useridto = :userid
                   AND {$like}";
        $this->assertTrue($DB->record_exists_select('message', $select, array('userid' => $mdluserid,
                                                                              'message' => "{$expected_message}%")));
    }
}