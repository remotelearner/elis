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

// Handy constants for readability
define('ELIS_USER_EXISTS', true);
define('ELIS_USER_DOESNOT_EXIST', false);
define('MDL_USER_EXISTS', true);
define('MDL_USER_DOESNOT_EXIST', false);
define('MDL_USER_DELETED', -1);
define('NO_TEST_SETUP', -1);

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

class elis_user_import_test extends elis_database_test {

    var $test_setup_data = array(
            array(
                'action'    => 'create',
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'email'     => 'test@email.com',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'country'   => 'CA'
            )
        );

    protected static function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $tables = array(
                        'block_instances' => 'moodle',
                        'block_positions' => 'moodle',
                        'cache_flags' => 'moodle',
                        'cohort_members' => 'moodle',
                        'comments' => 'moodle',
                        'config' => 'moodle',
                        'config_plugins' => 'moodle',
                        'context' => 'moodle',
                        'course' => 'moodle',
                        'course_categories' => 'moodle',
                        'course_sections' => 'moodle',
                        'crlm_class_graded' => 'elis_program',
                        'crlm_class_instructor' => 'elis_program',
                        'crlm_cluster_assignments' => 'elis_program',
                        'crlm_curriculum_assignment' => 'elis_program',
                        'crlm_curriculum_course' => 'elis_program',
                        'crlm_environment' => 'elis_program',
                        'crlm_results' => 'elis_program',
                        'crlm_results_action' => 'elis_program',
                        'crlm_tag' => 'elis_program',
                        'crlm_tag_instance' => 'elis_program',
                        'crlm_track' => 'elis_program',
                        'crlm_track_class' => 'elis_program',
                        'crlm_user' => 'elis_program',
                        'crlm_user_moodle' => 'elis_program',
                        'crlm_user_track' => 'elis_program',
                        'crlm_usercluster' => 'elis_program',
                        'crlm_wait_list' => 'elis_program',
                        'enrol' => 'moodle',
                        'events_queue' => 'moodle',
                        'events_queue_handlers' => 'moodle',
                        'filter_active' => 'moodle',
                        'filter_config' => 'moodle',
                        'grade_categories' => 'moodle',
                        'grade_categories_history' => 'moodle',
                        'grade_grades' => 'moodle',
                        'grade_grades_history' => 'moodle',
                        'grade_items' => 'moodle',
                        'grade_items_history' => 'moodle',
                        'groups' => 'moodle',
                        'groups_members' => 'moodle',
                        'message' => 'moodle',
                        'message_read' => 'moodle',
                        'message_working' => 'moodle',
                        'rating' => 'moodle',
                        'role' => 'moodle',
                        'role_context_levels' => 'moodle',
                        'role_assignments' => 'moodle',
                        'role_capabilities' => 'moodle',
                        'role_names' => 'moodle',
                        'sessions' => 'moodle',
                        'user' => 'moodle',
                        'user_preferences' => 'moodle',
                        'user_info_data' => 'moodle',
                        'user_lastaccess' => 'moodle',
                        'user_enrolments' => 'moodle'
                     );

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
     * Test data provider
     *
     * @return array the test data
     */
    public function dataProviderForTests() {
        $testdata = array();
        // user create - no idnumber
        $testdata[] = array('create',
                         array(
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // create - no username
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // create - no email
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // create - no firstname
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // create - no lastname
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // create - no country
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_DOESNOT_EXIST,
                         MDL_USER_DOESNOT_EXIST
                      );
        // all required create data!
        $testdata[] = array('create',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         NO_TEST_SETUP,
                         ELIS_USER_EXISTS, MDL_USER_EXISTS
                      );

        // update tests - all identifying fields
        $testdata[] = array('update',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstnamechange1',
                             'lastname'  => 'testlastnamechange1',
                             'country'   => 'US'
                         ),
                         0, // <- test setup index
                         ELIS_USER_EXISTS,
                         MDL_USER_EXISTS
                      );
        // update - idnumber id only
        $testdata[] = array('update',
                         array(
                             'idnumber'  => 'testidnumber',
                             'firstname' => 'testfirstnamechange2',
                             'lastname'  => 'testlastnamechange2',
                             'country'   => 'US'
                         ),
                         0, // <- test setup index
                         ELIS_USER_EXISTS, MDL_USER_EXISTS
                      );
        // update - username id only
        $testdata[] = array('update',
                         array(
                             'username'  => 'testusername',
                             'firstname' => 'testfirstnamechange3',
                             'lastname'  => 'testlastnamechange3',
                             'country'   => 'US'
                         ),
                         0, // <- test setup index
                         ELIS_USER_EXISTS, MDL_USER_EXISTS
                      );
        // update - email id only
        $testdata[] = array('update',
                         array(
                             'email'     => 'test@email.com',
                             'firstname' => 'testfirstnamechange4',
                             'lastname'  => 'testlastnamechange4',
                             'country'   => 'US'
                         ),
                         0, // <- test setup index
                         ELIS_USER_EXISTS, MDL_USER_EXISTS
                      );
        // update - no id columns
        $testdata[] = array('update',
                         array(
                             'firstname' => 'testfirstnamechange5',
                             'lastname'  => 'testlastnamechange5',
                             'country'   => 'US'
                         ),
                         0, // <- test setup index
                         ELIS_USER_DOESNOT_EXIST, MDL_USER_DOESNOT_EXIST
                      );

        // delete cases
        // delete - no id columns
        $testdata[] = array('delete',
                         array(
                             'firstname' => 'testfirstname',
                             'lastname'  => 'testlastname',
                             'country'   => 'CA'
                         ),
                         0, // <- test setup index
                         ELIS_USER_EXISTS, MDL_USER_EXISTS
                      );
        // delete - idnumber id only
        $testdata[] = array('delete',
                         array(
                             'idnumber'  => 'testidnumber',
                         ),
                         0, // <- test setup index
                         ELIS_USER_DOESNOT_EXIST, MDL_USER_DELETED
                      );
        // delete - username id only
        $testdata[] = array('delete',
                         array(
                             'username'  => 'testusername',
                         ),
                         0, // <- test setup index
                         ELIS_USER_DOESNOT_EXIST, MDL_USER_DELETED
                      );
        // delete - email id only
        $testdata[] = array('delete',
                         array(
                             'email'     => 'test@email.com',
                         ),
                         0, // <- test setup index
                         ELIS_USER_DOESNOT_EXIST, MDL_USER_DELETED
                      );
        // delete - all id fields
        $testdata[] = array('delete',
                         array(
                             'idnumber'  => 'testidnumber',
                             'username'  => 'testusername',
                             'email'     => 'test@email.com',
                         ),
                         0, // <- test setup index
                         ELIS_USER_DOESNOT_EXIST, MDL_USER_DELETED
                      );

        return $testdata;
    }

    /**
     * User import test cases
     *
     * @uses $DB
     * @dataProvider dataProviderForTests
     */
    public function test_elis_user_import($action, $user_data, $setup_index,
                                          $elis_exists, $mdl_exists) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis')
                .'/version1elis.class.php';
        require_once($file);

        $import_data = array('action' => $action);
        foreach ($user_data as $key => $value) {
            $import_data[$key] = $value;
        }

        try {
            if ($setup_index != NO_TEST_SETUP) {
                $provider = new rlip_importprovider_mockuser($this->test_setup_data[$setup_index]);
                $importplugin = new rlip_importplugin_version1elis($provider);
                @$importplugin->run();
            }
            $mdl_userid = $DB->get_field('user', 'id', $user_data);
            $provider = new rlip_importprovider_mockuser($import_data);
            $importplugin = new rlip_importplugin_version1elis($provider);
            @$importplugin->run();
        } catch (Exception $e) {
            mtrace("\nException in test_elis_user_import(): ". $e->getMessage()
                   ."\n");
        }
        ob_start();
        var_dump($user_data);
        $tmp = ob_get_contents();
        ob_end_clean();

        ob_start();
        var_dump($DB->get_records('crlm_user'));
        $crlm_user = ob_get_contents();
        ob_end_clean();

        ob_start();
        var_dump($DB->get_records('user'));
        $mdl_user = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($elis_exists, $DB->record_exists('crlm_user', $user_data), "ELIS user assertion: user_data; crlm_user  = {$tmp} ; {$crlm_user}");
        if ($mdl_exists === true) {
            $user_data['deleted'] = 0;
        } else if ($mdl_exists === MDL_USER_DELETED) {
            $mdl_exists = true;
            $user_data = array('id' => $mdl_userid, 'deleted' => 1);
        }
        $this->assertEquals($mdl_exists, $DB->record_exists('user', $user_data),
                            "Moodle user assertion: user_data; mdl_user = {$tmp}; {$mdl_user}");
    }

}

