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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

class elis_user_custom_fields_test extends elis_database_test {

    protected static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $tables = array('crlm_user_moodle'  => 'elis_program',
                        'crlm_user' => 'elis_program',
                        'crlm_course' => 'elis_program',
                        'crlm_coursetemplate' => 'elis_program',
                        'user' => 'moodle',
                        'crlm_curriculum' => 'elis_program',
                        'crlm_curriculum_assignment' => 'elis_program',
                        'crlm_class' => 'elis_program',
                        'crlm_class_graded' => 'elis_program',
                        'crlm_class_instructor' => 'elis_program',
                        'crlm_cluster' => 'elis_program',
                        'crlm_wait_list' => 'elis_program',
                        'crlm_tag' => 'elis_program',
                        'crlm_tag_instance' => 'elis_program',
                        'crlm_track' => 'elis_program',
                        'crlm_track_class' => 'elis_program',
                        'crlm_user' => 'elis_program',
                        'crlm_user_moodle' => 'elis_program',
                        'crlm_user_track' => 'elis_program',
                        'crlm_usercluster' => 'elis_program',
                        'crlm_results' => 'elis_program',
                        'crlm_results_action' => 'elis_program',
                        'crlm_curriculum_course' => 'elis_program',
                        'crlm_environment' => 'elis_program',
                        'crlm_cluster_assignments' => 'elis_program',
                        'context' => 'moodle',
                        'config' => 'moodle',
                        'config_plugins' => 'moodle',
                        'filter_active' => 'moodle',
                        'filter_config' => 'moodle',
                        'cache_flags' => 'moodle',
                        'events_queue' => 'moodle',
                        'user_enrolments' => 'moodle',
                        'events_queue_handlers' => 'moodle',
                        'elis_field_categories' => 'elis_core',
                        'elis_field_category_contexts' => 'elis_core',
                        'elis_field_contextlevels' => 'elis_core',
                        'elis_field_data_char' => 'elis_core',
                        'elis_field' => 'elis_core',
                        'elis_field_data_int' => 'elis_core',
                        'elis_field_data_num' => 'elis_core',
                        'elis_field_data_text' => 'elis_core',
                        'elis_field_owner' => 'elis_core');

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array('log'              => 'moodle',
                     RLIP_LOG_TABLE     => 'block_rlip',
                     'files'            => 'moodle',
                     'external_tokens'  => 'moodle',
                     'external_services_users' => 'moodle');
    }

    /**
     * Create and update userset custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_userset_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_USERSET);

        $temp = new userset();
        //prevent caching issues
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $usersetcontext = context_elis_userset::instance($DB->get_field('crlm_cluster', 'id', array('name' => 'testcluster')));

        $this->assert_field_values($datatype, $control, $fieldid, $usersetcontext->id, $expected);

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->parent = 0;
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $usersetcontext->id, $updateexpected);
    }

    /**
     * Create and update class custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_class_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcourse';
        $importplugin->course_create($record, 'bogus');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_CLASS);

        $temp = new pmclass();
        //prevent caching issues
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->assignment = 'testcourseid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $classcontext = context_elis_class::instance($DB->get_field('crlm_class', 'id', array('idnumber' => 'testclassid')));

        $this->assert_field_values($datatype, $control, $fieldid, $classcontext->id, $expected);

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $classcontext->id, $updateexpected);
    }

    /**
     * Create and update course custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_course_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_COURSE);

        $temp = new course();
        //prevent caching issues
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name= 'testcourse';
        $record->idnumber = 'testcourseid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $coursecontext = context_elis_course::instance($DB->get_field('crlm_course', 'id', array('idnumber' => 'testcourseid')));

        $this->assert_field_values($datatype, $control, $fieldid, $coursecontext->id, $expected);

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoure';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $coursecontext->id, $updateexpected);
    }

    /**
     * Create and update track custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_track_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $importplugin->curriculum_create($record, 'bogus');

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_TRACK);

        $temp = new track();
        //prevent caching issues
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->name= 'testtrack';
        $record->idnumber = 'testtrackid';
        $record->assignment = 'testprogramid';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $trackcontext = context_elis_track::instance($DB->get_field('crlm_track', 'id', array('idnumber' => 'testtrackid')));

        $this->assert_field_values($datatype, $control, $fieldid, $trackcontext->id, $expected);

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'track';
        $record->idnumber = 'testtrackid';
        $record->{$name} = $updateddata;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $trackcontext->id, $updateexpected);
    }

    /**
     * Create and update program custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_program_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_PROGRAM);

        $temp = new curriculum();
        //prevent caching issues
        $temp->reset_custom_field_list();

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->{$name} = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $programcontext = context_elis_program::instance($DB->get_field('crlm_curriculum', 'id', array('idnumber' => 'testprogramid')));

        $this->assert_field_values($datatype, $control, $fieldid, $programcontext->id, $expected);

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->{$name} = $updateddata;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('course', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $programcontext->id, $updateexpected);
    }

    /**
     * Data provider for testing various custom field UI / display types
     *
     * @return array The data, as expected by the testing method
     */
    function ui_type_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        return array(array('checkbox', '0', '0', '1', '1', 'testcheckbox', 'bool', NULL, NULL, NULL),
                     array('checkbox', '1', '1', '0', '0', 'testcheckbox', 'bool', NULL, NULL, NULL),
                     array('checkbox', 'yes', '1', 'no', '0', 'testcheckbox', 'bool', NULL, NULL, NULL),
                     array('checkbox', 'no',  '0', 'yes', '1', 'testcheckbox', 'bool', NULL, NULL, NULL),
                     array('datetime', 'Jan/02/2012',  rlip_timestamp(0, 0, 0, 1, 2, 2012), 'Feb/02/2012', rlip_timestamp(0, 0, 0, 2, 2, 2012), 'textdatetime', 'datetime', NULL, '0', NULL),
                     array('datetime', 'Jan/02/2012:01:30',  rlip_timestamp(1, 30, 0, 1, 2, 2012), 'Feb/02/2012:01:30', rlip_timestamp(1, 30, 0, 2, 2, 2012), 'textdatetime', 'datetime', NULL, '1', NULL),
                     array('password', 'passworddata', 'passworddata', 'passworddataupdated', 'passworddataupdated', 'testpassword', 'char', 100, NULL, NULL),
                     array('textarea', 'textareadata', 'textareadata', 'textareaupdated', 'textareaupdated', 'testtextarea', 'text', NULL, NULL, NULL),
                     array('text', '2.2', '2.2', '3.3', '3.3', 'testtext', 'num', 100, NULL, NULL),
                     array('menu', '2', '2', '3', '3', 'testmenu', 'int', NULL, NULL, array(1,2,3)));
    }

    /**
     * Create and update user custom fields
     *
     * @dataProvider ui_type_provider
     * @param string $ui_type The string value representing a UI type
     * @param string $data Value for create
     * @param mixed $expected Expected value after create as a string or int
     * @param string $updateddata Value for update
     * @param mixed $updateexpected Expected value after update as string or int
     * @param string $name The name of the control
     * @param string $datatype The datatype of the field
     * @param mixed $maxlength The maxiumum length of the field as int or NULL
     * @param mixed $inctime Include time along with the date as string or NULL
     * @param mixed $options The options of the field as array or NULL
    */
    function test_elis_user_custom_field_import($control, $data, $expected, $updateddata, $updateexpected, $name, $datatype, $maxlength, $inctime, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $fieldid = $this->create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, CONTEXT_ELIS_USER);

        $temp = new user();
        //prevent caching issues
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
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $usercontext = context_elis_user::instance($DB->get_field('crlm_user', 'id', array('idnumber' => 'testuserid')));

        $this->assert_field_values($datatype, $control, $fieldid, $usercontext->id, $expected);

        // update
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
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $this->assert_field_values($datatype, $control, $fieldid, $usercontext->id, $updateexpected);
    }

    /**
     * Validate that the "menu of choices" custom field type works correctly
     * when options are separated by a carriage return and a line feed
     */
    public function testMenuOfChoicesIgnoresCarriageReturns() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::file('accesslib.php'));
        require_once(elispm::lib('data/user.class.php'));

        //setup
        $field = new field(array(
            'shortname' => 'testcustomfieldshortname',
            'name'      => 'testcustomfieldname',
            'datatype'  => 'char'
        ));
        $category = new field_category(array('name' => 'testcategoryname'));
        field::ensure_field_exists_for_context_level($field, CONTEXT_ELIS_USER, $category);

        $owner_params = array(
            'control' => 'menu',
            'options' => "option1\r\noption2"
        );
        field_owner::ensure_field_owner_exists($field, 'manual', $owner_params);

        //run the create action
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
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->process_record('user', (object)$record, 'bogus');

        //validation
        $user = new user(1);
        $user->load();

        $this->assertEquals('option1', $user->field_testcustomfieldshortname);
    }

    private function create_test_field($name, $datatype, $control, $inctime, $maxlength, $options, $context) {
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        $field = new field(array('shortname' => $name, 'name' => $name, 'datatype' => $datatype, 'categoryid' => $field_category->id));
        $field->save();

        $owner_data = array('control' => $control);

        if ($options !== NULL) {
            $owner_data['options'] = implode("\n", $options);
        }

        if ($maxlength !== NULL) {
            $owner_data['maxlength'] = $maxlength;
        }

        if ($inctime !== NULL) {
            $owner_data['inctime'] = $inctime;
        }

        //associate fields to context levels
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => $context));
        $field_contextlevel->save();

        field_owner::ensure_field_owner_exists($field, 'manual', $owner_data);

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
        if ($control == 'textarea' || $datatype == 'text') { // TBD
            $olddatatype = " ({$datatype})";
            $datatype = 'text';
            $select .= ' AND '. $DB->sql_compare_text('data') .' = :data';
            $params['data'] = substr($expected, 0, 32);
        } else {
            $params['data'] = $expected;
        }

        ob_start();
        var_dump($DB->get_records('elis_field_data_'. $datatype));
        $tmp = ob_get_contents();
        ob_end_clean();
        $msg = "No field data for {$control}: type = {$datatype}{$olddatatype}, fieldid = {$fieldid}, contextid = {$usercontextid}, data = {$expected}\n elis_field_data_{$datatype} => {$tmp}\n";

        $this->assertTrue($DB->record_exists_select(
                               'elis_field_data_'. $datatype, $select, $params),
                          $msg);
    }

}

