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

class elis_user_program_enrolment_test extends elis_database_test {

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
                        field_owner::TABLE => 'elis_core');

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
                     'external_services_users'      => 'moodle',
                     'external_tokens'              => 'moodle',
                     'external_services_users'      => 'moodle');
    }

    function test_elis_user_program_enrolment_import() {
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

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->user_create($record, 'bogus');

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';

        $importplugin->curriculum_create($record, 'bogus');

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum_testprogramid';
        $record->user_idnumber = 'testidnumber';

        $importplugin->curriculum_enrolment_create($record, 'bogus', 'testprogramid');

        $userid = $DB->get_field('crlm_user', 'id', array('idnumber' => 'testidnumber'));
        $this->assertTrue($DB->record_exists('crlm_curriculum_assignment', array('userid' => $userid)));
    }

    function test_elis_user_program_unenrolment_import() {
        global $DB;
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);

        $this->test_elis_user_program_enrolment_import();

        $record = new stdClass;
        $record->action = 'delete';
        $record->context = 'curriculum_testprogramid';
        $record->user_idnumber = 'testidnumber';

        $importplugin->curriculum_enrolment_delete($record, 'bogus', 'testprogramid');

        $userid = $DB->get_field('crlm_user', 'id', array('idnumber' => 'testidnumber'));
        $this->assertFalse($DB->record_exists('crlm_curriculum_assignment', array('userid' => $userid)));
    }

}

