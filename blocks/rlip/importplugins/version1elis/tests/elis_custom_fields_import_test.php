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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');

require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

/**
 * Test custom fields functionality
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_user_custom_fields_testcase extends rlip_elis_test {

    /**
     * Create and update userset custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_userset_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name,
                                                          $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_USERSET);

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $usersetcontext = context_elis_userset::instance($DB->get_field('crlm_cluster', 'id', array('name' => 'testcluster')));

        $this->assert_field_values($datatype, $control, $fieldid, $usersetcontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->parent = 0;
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $usersetcontext->id, $updateexpected);
    }

    /**
     * Create and update class custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_class_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name,
                                                        $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcourse';
        $importplugin->course_create($record, 'bogus');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_CLASS);

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->assignment = 'testcourseid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $classcontext = context_elis_class::instance($DB->get_field('crlm_class', 'id', array('idnumber' => 'testclassid')));

        $this->assert_field_values($datatype, $control, $fieldid, $classcontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $classcontext->id, $updateexpected);
    }

    /**
     * Create and update course custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_course_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name,
                                                         $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_COURSE);

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name= 'testcourse';
        $record->idnumber = 'testcourseid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $coursecontext = context_elis_course::instance($DB->get_field('crlm_course', 'id', array('idnumber' => 'testcourseid')));

        $this->assert_field_values($datatype, $control, $fieldid, $coursecontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoure';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $coursecontext->id, $updateexpected);
    }

    /**
     * Create and update track custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_track_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype,
                                                        $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $importplugin->curriculum_create($record, 'bogus');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_TRACK);

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->name= 'testtrack';
        $record->idnumber = 'testtrackid';
        $record->assignment = 'testprogramid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $trackcontext = context_elis_track::instance($DB->get_field('crlm_track', 'id', array('idnumber' => 'testtrackid')));

        $this->assert_field_values($datatype, $control, $fieldid, $trackcontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'track';
        $record->idnumber = 'testtrackid';
        $record->{$name} = $updateddata;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $trackcontext->id, $updateexpected);
    }

    /**
     * Create and update program custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_program_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name,
                                                          $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_PROGRAM);

        $temp = new curriculum();
        // Prevent caching issues.
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $programcontext = context_elis_program::instance($DB->get_field('crlm_curriculum', 'id', array(
            'idnumber' => 'testprogramid'
        )));

        $this->assert_field_values($datatype, $control, $fieldid, $programcontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $programcontext->id, $updateexpected);
    }

    /**
     * Data provider for testing various custom field UI / display types
     *
     * @return array The data, as expected by the testing method
     */
    public function ui_type_provider() {
        return array(
                array(
                        'checkbox',
                        '0',
                        '0',
                        '1',
                        '1',
                        'testcheckbox',
                        'bool',
                        null,
                        null,
                        null
                ),
                array(
                        'checkbox',
                        '1',
                        '1',
                        '0',
                        '0',
                        'testcheckbox',
                        'bool',
                        null,
                        null,
                        null
                ),
                array(
                        'checkbox',
                        'yes',
                        '1',
                        'no',
                        '0',
                        'testcheckbox',
                        'bool',
                        null,
                        null,
                        null
                ),
                array(
                        'checkbox',
                        'no',
                        '0',
                        'yes',
                        '1',
                        'testcheckbox',
                        'bool',
                        null,
                        null,
                        null),
                array(
                        'datetime',
                        'Jan/02/2012',
                        array(0, 0, 0, 1, 2, 2012),
                        'Feb/02/2012',
                        array(0, 0, 0, 2, 2, 2012),
                        'textdatetime',
                        'datetime',
                        null,
                        '0',
                        null
                ),
                array(
                        'datetime',
                        'Jan/02/2012:01:30',
                        array(1, 30, 0, 1, 2, 2012),
                        'Feb/02/2012:01:30',
                        array(1, 30, 0, 2, 2, 2012),
                        'textdatetime',
                        'datetime',
                        null,
                        '1',
                        null
                ),
                array(
                        'password',
                        'passworddata',
                        'passworddata',
                        'passworddataupdated',
                        'passworddataupdated',
                        'testpassword',
                        'char',
                        100,
                        null,
                        null
                ),
                array(
                        'textarea',
                        'textareadata',
                        'textareadata',
                        'textareaupdated',
                        'textareaupdated',
                        'testtextarea',
                        'text',
                        null,
                        null,
                        null
                ),
                array(
                        'text',
                        '2.2',
                        '2.2',
                        '3.3',
                        '3.3',
                        'testtext',
                        'num',
                        100,
                        null,
                        null
                ),
                array(
                        'menu',
                        '2',
                        '2',
                        '3',
                        '3',
                        'testmenu',
                        'int',
                        null,
                        null,
                        array(1, 2, 3)
                )
        );
    }

    /**
     * Create and update user custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $uitype The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or null
     * @param mixed $inctime Include time along with the date as string or null
     * @param mixed $options The options of the field as array or null
     */
    public function test_elis_user_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype,
                                                       $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_USER);

        if ($control === 'datetime' && is_array($expected)) {
            $expected = rlip_timestamp($expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]);
        }

        if ($control === 'datetime' && is_array($updateexpected)) {
            $updateexpected = rlip_timestamp($updateexpected[0], $updateexpected[1], $updateexpected[2], $updateexpected[3],
                    $updateexpected[4], $updateexpected[5]);
        }

        $temp = new user();
        // Prevent caching issues.
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->email = 'testuser@mail.com';
        $record->username = 'testuser';
        $record->idnumber = 'testuserid';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $usercontext = context_elis_user::instance($DB->get_field('crlm_user', 'id', array('idnumber' => 'testuserid')));

        $this->assert_field_values($datatype, $control, $fieldid, $usercontext->id, $expected);

        // Update.
        $record = new stdClass;
        $record->action = 'update';
        $record->email = 'testuser@mail.com';
        $record->username = 'testuser';
        $record->idnumber = 'testuserid';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $usercontext->id, $updateexpected);
    }

    /**
     * Validate that the "menu of choices" custom field type works correctly
     * when options are separated by a carriage return and a line feed
     */
    public function testmenuofchoicesignorescarriagereturns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Setup.
        $field = new field(array(
            'shortname' => 'testcustomfieldshortname',
            'name'      => 'testcustomfieldname',
            'datatype'  => 'char'
        ));
        $category = new field_category(array('name' => 'testcategoryname'));
        field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USER, $category);

        $ownerparams = array(
            'control' => 'menu',
            'options' => "option1\r\noption2"
        );
        field_owner::ensure_field_owner_exists($field, 'manual', $ownerparams);

        // Run the create action.
        $record = new stdClass;
        $record->action = 'create';
        $record->email = 'testuser@mail.com';
        $record->username = 'testuser';
        $record->idnumber = 'testuserid';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';
        $record->testcustomfieldshortname = 'option1';

        $user = new user();
        $user->reset_custom_field_list();

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        // Validation.
        $user = new user(1);
        $user->load();

        $this->assertEquals('option1', $user->field_testcustomfieldshortname);
    }

    private function create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, $context) {
        $fieldcategory = new field_category(array('name' => 'testcategoryname'));
        $fieldcategory->save();

        $field = new field(array(
            'shortname' => $name,
            'name' => $name,
            'datatype' => $datatype,
            'categoryid' => $fieldcategory->id
        ));
        $field->save();

        $ownerdata = array('control' => $control);

        if ($options !== null) {
            $ownerdata['options'] = implode("\n", $options);
        }

        if ($maxlength !== null) {
            $ownerdata['maxlength'] = $maxlength;
        }

        if ($inctime !== null) {
            $ownerdata['inctime'] = $inctime;
        }

        // Associate fields to context levels.
        $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => $context));
        $fieldcontextlevel->save();

        field_owner::ensure_field_owner_exists($field, 'manual', $ownerdata);

        return $field->id;
    }

    private function assert_field_values($datatype, $control, $fieldid, $usercontextid, $expected) {
        global $DB;

        if ($datatype == 'bool' || $datatype == 'datetime') {
            $datatype = 'int';
        }

        $params = array('fieldid'   => $fieldid,
                        'contextid' => $usercontextid);
        $select = 'fieldid = :fieldid AND contextid = :contextid';
        $olddatatype = '';
        if ($control == 'textarea' || $datatype == 'text') { // TBD.
            $olddatatype = " ({$datatype})";
            $datatype = 'text';
            $select .= ' AND '.$DB->sql_compare_text('data').' = :data';
            $params['data'] = substr($expected, 0, 32);
        } else {
            $params['data'] = $expected;
        }

        ob_start();
        var_dump($DB->get_records('elis_field_data_'.$datatype));
        $tmp = ob_get_contents();
        ob_end_clean();
        $msg = "No field data for {$control}: type = {$datatype}{$olddatatype}, fieldid = {$fieldid}, ";
        $msg .= "contextid = {$usercontextid}, data = {$expected}\n elis_field_data_{$datatype} => {$tmp}\n";

        $this->assertTrue($DB->record_exists_select('elis_field_data_'.$datatype, $select, $params), $msg);
    }
}