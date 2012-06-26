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

class elis_user_import_test extends elis_database_test {

    protected static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $tables = array('crlm_user_moodle'  => 'elis_program',
                        'crlm_user' => 'elis_program',
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
                        'events_queue_handlers' => 'moodle');

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

        return array(student::TABLE     => 'elis_program',
                     'log'              => 'moodle',
                     RLIP_LOG_TABLE     => 'block_rlip',
                     'files'            => 'moodle',
                     'external_tokens'  => 'moodle',
                     'external_services_users'      => 'moodle',
                     'elis_field_categories'        => 'elis_program',
                     'elis_field_category_contexts' => 'elis_program',
                     'elis_field_contextlevels'     => 'elis_program',
                     'elis_field_data_char'         => 'elis_program',
                     'elis_field'                   => 'elis_program',
                     'elis_field_data_int'          => 'elis_program',
                     'elis_field_data_num'          => 'elis_program',
                     'elis_field_data_text'         => 'elis_program',
                     'elis_field_owner'             => 'elis_program',
                     'external_tokens'              => 'moodle',
                     'external_services_users'      => 'moodle');
    }

    /**
     * Helper function to get the core fields for a sample user
     *
     * @return array The user data
     */
    private function get_core_user_data() {
        $data = array('entity'      => 'user',
                      'action'      => 'create',
                      'idnumber'    => 'testidnumber',
                      'username'    => 'testusername',
                      'email'       => 'test@email.com',
                      'firstname'   => 'testfirstname',
                      'lastname'    => 'testlastname',
                      'country'     => 'CA');
        return $data;
    }

    function test_create_elis_user_import() {
        global $CFG, $DB;

        $this->run_elis_user_import(array());

        $select = "username     = :username AND
                   idnumber     = :idnumber AND
                   firstname    = :firstname AND
                   lastname     = :lastname AND
                   email        = :email AND
                   country      = :country";

        $params = array('username'  => 'testusername',
                        'idnumber'  => 'testidnumber',
                        'firstname' => 'testfirstname',
                        'lastname'  => 'testlastname',
                        'email'     => 'test@email.com',
                        'country'   => 'CA');

        $exists = $DB->record_exists_select('crlm_user', $select, $params);
        $this->assertEquals($exists, true);
    }

    function test_update_elis_user_import() {
        global $CFG, $DB;

        $this->run_elis_user_import(array());

        $data = array('action'      => 'update',
                      'idnumber'    => 'testidnumber',
                      'username'    => 'testusername',
                      'email'       => 'test@email.com',
                      'firstname'   => 'testfirstnamechanged',
                      'lastname'    => 'testlastnamechanged',
                      'city'        => 'testcity',
                      'country'     => 'US');

        $this->run_elis_user_import($data, false);

        $select = "username     = :username AND
                   idnumber     = :idnumber AND
                   firstname    = :firstname AND
                   lastname     = :lastname AND
                   email        = :email AND
                   city         = :city AND
                   country      = :country";

        unset($data['action']);
        $existselis = $DB->record_exists_select('crlm_user', $select, $data);
        $existsmoodle = $DB->record_exists_select('user', $select, $data);

        $this->assertEquals($existselis && $existsmoodle, true);
    }

    function test_delete_elis_user_import() {
        global $CFG, $DB;

        $this->run_elis_user_import(array());

        $data = array('action'      => 'delete',
                      'username'    => 'testusername',
                      'email'       => 'test@email.com',
                      'idnumber'    => 'testidnumber');

        unset($data['action']);
        $moodleuserid = $DB->get_field('user', 'id', $data);
        $elisuserid = $DB->get_field('crlm_user', 'id', $data);

        $data['action'] = 'delete';
        $this->run_elis_user_import($data, false);

        $this->assertEquals($DB->record_exists('crlm_user', array('id' => $elisuserid)), false);
        $this->assertEquals($DB->record_exists('user', array('id' => $moodleuserid, 'deleted' => 1)), true);
    }

    /**
     * Helper function that runs the user import for a sample user

     *
     * @param array $extradata Extra fields to set for the new user
     */
    private function run_elis_user_import($extradata, $use_default_data = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_user_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

}

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_mockuser extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

