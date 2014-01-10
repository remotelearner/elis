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
 * @package    dhimport_version1elis
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
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/curriculumstudent.class.php'));
    require_once(elispm::lib('data/user.class.php'));
}

/**
 * Test user program enrolment functions.
 * @group local_datahub
 * @group dhimport_version1elis
 */
class elis_user_program_enrolment_testcase extends rlip_elis_test {

    /**
     * Provider for different actions on import
     */
    public function action_provider() {
        return array(
                array('create', 'delete'),
                array('enrol', 'unenrol'),
                array('enroll', 'unenroll')
        );
    }

    /**
     * Test program enrolment import
     *
     * @dataProvider action_provider
     */
    public function test_elis_user_program_enrolment_import($actioncreate, $actiondelete) {
        global $DB;

        $record = new stdClass;
        $record->entity = 'user';
        $record->action = 'create';
        $record->idnumber = 'testidnumber';
        $record->username = 'testusername';
        $record->email = 'test@email.com';
        $record->firstname = 'testfirstname';
        $record->lastname = 'testlastname';
        $record->country = 'CA';

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->user_create($record, 'bogus');

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';

        $importplugin->curriculum_create($record, 'bogus');

        $record = new stdClass;
        $record->action = $actioncreate;
        $record->context = 'curriculum_testprogramid';
        $record->user_idnumber = 'testidnumber';

        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        $userid = $DB->get_field(user::TABLE, 'id', array('idnumber' => 'testidnumber'));
        $this->assertTrue($DB->record_exists(curriculumstudent::TABLE, array('userid' => $userid)));
    }

    /**
     * Test program unenrolment import
     *
     * @dataProvider action_provider
     */
    public function test_elis_user_program_unenrolment_import($actioncreate, $actiondelete) {
        global $DB;
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        $this->test_elis_user_program_enrolment_import($actioncreate, $actiondelete);

        $record = new stdClass;
        $record->action = $actiondelete;
        $record->context = 'curriculum_testprogramid';
        $record->user_idnumber = 'testidnumber';

        $importplugin->process_record('enrolment', (object)$record, 'bogus');

        $userid = $DB->get_field(user::TABLE, 'id', array('idnumber' => 'testidnumber'));
        $this->assertFalse($DB->record_exists(curriculumstudent::TABLE, array('userid' => $userid)));
    }

    /**
     * Validate that enrolments still work when the entity's identifier contains
     * an underscore
     */
    public function test_enrolmentinstancesupportsunderscores() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Set up required data.
        $program = new curriculum(array(
            // Idnumber has an underscore in it.
            'idnumber' => 'testprogram_idnumber'
        ));
        $program->save();

        $user = new user(array(
            'idnumber'  => 'testuseridnumber',
            'username'  => 'testuserusername',
            'firstname' => 'testuserfirstname',
            'lastname'  => 'testuserlastname',
            'email'     => 'testuser@email.com',
            'country'   => 'CA'
        ));
        $user->save();

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        // Create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum_testprogram_idnumber';
        $record->user_idnumber = 'testuseridnumber';

        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation for create action.
        $params = array(
            'userid'       => $user->id,
            'curriculumid' => $program->id
        );
        $exists = $DB->record_exists(curriculumstudent::TABLE, $params);
        $this->assertTrue($exists);

        // Delete action.
        $record->action = 'delete';

        $importplugin->process_record('enrolment', $record, 'bogus');

        // Validation for delete action.
        $this->assertEquals(0, $DB->count_records(curriculumstudent::TABLE));
    }
}