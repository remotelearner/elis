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

class elis_class_import_test extends elis_database_test {

    protected static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);
        require_once(elis::lib('data/customfield.class.php'));

        $tables = array('crlm_curriculum' => 'elis_program',
                        'crlm_class_moodle' => 'elis_program',
                        'crlm_class' => 'elis_program',
                        'crlm_cluster_curriculum' => 'elis_program',
                        'crlm_user_moodle'  => 'elis_program',
                        'crlm_user' => 'elis_program',
                        'crlm_course' => 'elis_program',
                        'crlm_coursetemplate' => 'elis_program',
                        'user' => 'moodle',
                        'crlm_curriculum_assignment' => 'elis_program',
                        'crlm_class_graded' => 'elis_program',
                        'crlm_class_instructor' => 'elis_program',
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
                        'cohort_members' => 'moodle',
                        'groups_members' => 'moodle',
                        'user_preferences' => 'moodle',
                        'user_info_data' => 'moodle',
                        'user_lastaccess' => 'moodle',
                        'sessions' => 'moodle',
                        'block_instances' => 'moodle',
                        'block_positions' => 'moodle',
                        'filter_active' => 'moodle',
                        'filter_config' => 'moodle',
                        'comments' => 'moodle',
                        'rating' => 'moodle',
                        'role_assignments' => 'moodle',
                        'role_capabilities' => 'moodle',
                        'role_names' => 'moodle',
                        'cache_flags' => 'moodle',
                        'events_queue' => 'moodle',
                        'groups' => 'moodle',
                        'course' => 'moodle',
                        'course_sections' => 'moodle',
                        'course_categories' => 'moodle',
                        'enrol' => 'moodle',
                        'role' => 'moodle',
                        'role_context_levels' => 'moodle',
                        'message' => 'moodle',
                        'message_read' => 'moodle',
                        'message_working' => 'moodle',
                        'grade_items' => 'moodle',
                        'grade_items_history' => 'moodle',
                        'grade_grades' => 'moodle',
                        'grade_grades_history' => 'moodle',
                        'grade_categories' => 'moodle',
                        'grade_categories_history' => 'moodle',
                        'user_enrolments' => 'moodle',
                        'events_queue_handlers' => 'moodle',
                        field::TABLE => 'elis_core',
                        field_category::TABLE => 'elis_core',
                        field_category_contextlevel::TABLE => 'elis_core',
                        field_contextlevel::TABLE => 'elis_core',
                        field_data_char::TABLE => 'elis_core',
                        field_data_int::TABLE => 'elis_core',
                        field_data_num::TABLE => 'elis_core',
                        field_data_text::TABLE => 'elis_core',
                        field_owner::TABLE => 'elis_core',
                        RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis');

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        return array('log'              => 'moodle',
                     RLIP_LOG_TABLE     => 'block_rlip',
                     'files'            => 'moodle',
                     'external_tokens'  => 'moodle',
                     'external_services_users'      => 'moodle',
                     'external_tokens'              => 'moodle',
                     'external_services_users'      => 'moodle',
                     student::TABLE                 => 'elis_program'
        );

    }

    function test_create_elis_class_import() {
       global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_class', array('idnumber' => 'testclassid')));
    }

    function test_delete_elis_class_import() {
        global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);

        $data = array('action' => 'delete', 'context' => 'class', 'idnumber' => 'testclassid');
        $this->run_elis_class_import($data, false);

        unset($data['action'],$data['context']);
        $this->assertFalse($DB->record_exists('crlm_class', $data));
    }

    function test_update_elis_class_import() {
        global $DB;

        $this->run_elis_course_import(array(), true);
        $this->assertTrue($DB->record_exists('crlm_course', array('idnumber' => 'testcourseid')));

        $this->run_elis_class_import(array(), true);

        $data = array('action' => 'update', 'context' => 'class', 'idnumber' => 'testclassid', 'maxstudents' => 30);
        $this->run_elis_class_import($data, false);

        unset($data['action'],$data['context']);
        $this->assertTrue($DB->record_exists('crlm_class', $data));
    }

    // Data provider for mapping yes to 1 and no to 0
    function field_provider() {
        return array(array('0', '0'),
                     array('1', '1'),
                     array('yes', '1'),
                     array('no', '0'));
    }

    /**
     * @dataProvider field_provider
     * @param string The import data (0, 1, yes, no)
     * @param string The expected data (0, 1)
     */
    function test_elis_class_enrol_from_waitlist_import($data, $expected) {
        global $CFG, $DB;

        $this->run_elis_course_import(array(), true);

        $record = array();
        $record = $this->get_core_class_data();
        $record['enrol_from_waitlist'] = $data;

        $this->run_elis_class_import($record, false);

        $this->assertEquals(true, $DB->record_exists(pmclass::TABLE, array('idnumber' => $record['idnumber'], 'enrol_from_waitlist' => $expected)));
    }

    /**
     * @dataProvider field_provider
     * @param string The import data (0, 1, yes, no)
     * @param string The expected data (0, 1)
     */
    function test_elis_class_autoenrol_import($data, $expected) {
        global $CFG, $DB;

        $this->run_elis_course_import(array(), true);

        $record = array();
        $record = $this->get_core_class_data();
        $record['autoenrol'] = $data;

        $this->run_elis_class_import($record, false);
        $this->assertEquals(true, $DB->record_exists(pmclass::TABLE, array('idnumber' => $record['idnumber'])));
    }

    /**
     * Helper function to get the core fields for a sample class
     *
     * @return array The program data
     */
    private function get_core_class_data() {
        $data = array('action' => 'create',
                      'context' => 'class',
                      'idnumber' => 'testclassid',
                      'assignment' => 'testcourseid',
                      'maxstudents' => 40);
        return $data;
    }

    /**
     * Helper function to get the core fields for a sample course
     *
     * @return array The course data
     */
    private function get_core_course_data() {
        $data = array('action' => 'create',
                      'context' => 'course',
                      'idnumber' => 'testcourseid',
                      'name' => 'testcoursename');
        return $data;
    }

    /**
     * Helper function that runs the class import for a sample class
     *
     * @param array $extradata Extra fields to set for the new class
     */
    private function run_elis_class_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_class_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockclass($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Helper function that runs the course import for a sample course
     *
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_elis_course_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_course_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockcourse($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

}

/**
 * Class that fetches import files for the program import
 */
class rlip_importprovider_mockclass extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_mockcourse extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

