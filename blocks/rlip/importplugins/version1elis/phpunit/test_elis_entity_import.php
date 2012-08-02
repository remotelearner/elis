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

require_once(dirname(__FILE__) .'/../../../../../config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/rlip_test.class.php');

// Handy constants for readability
define('ELIS_ENTITY_EXISTS', true);
define('ELIS_ENTITY_DOESNOT_EXIST', false);

// readable defs for test_setup_data array index
define('TEST_SETUP_COURSE',     0);
define('TEST_SETUP_CURRICULUM', 1);
define('TEST_SETUP_TRACK',      2);
define('TEST_SETUP_CLASS',      3);
define('TEST_SETUP_CLUSTER',    4);
global $NO_TEST_SETUP;
$NO_TEST_SETUP = array();

/**
 * Class that fetches import files for the user import
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

class elis_entity_import_test extends elis_database_test {

    var $context_to_table = array(
        'course'     => 'crlm_course',
        'curr'       => 'crlm_curriculum',
        'curriculum' => 'crlm_curriculum',
        'track'      => 'crlm_track',
        'class'      => 'crlm_class',
        'cluster'    => 'crlm_cluster'
    );

    var $test_setup_data = array(
        array(
            'action'     => 'create',
            'context'    => 'course',
            'idnumber'   => 'courseidnumber',
            'name'       => 'coursename'
        ),
        array(
            'action'     => 'create',
            'context'    => 'curriculum',
            'idnumber'   => 'programidnumber',
            'name'       => 'programname'
        ),
        array(
            'action'     => 'create',
            'context'    => 'track',
            'idnumber'   => 'trackidnumber',
            'name'       => 'trackname',
            'assignment' => 'programidnumber' // <-- Program/Curriculum idnumber
        ),
        array(
            'action'     => 'create',
            'context'    => 'class',
            'idnumber'   => 'classidnumber',
            'assignment' => 'courseidnumber', // <-- Course Description idnumber
            'maxstudents'=> 0
        ),
        array(
            'action'     => 'create',
            'context'    => 'cluster',
            'name'       => 'usersetname',
            'display'    => 'usersetdescription'
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
            'crlm_class' => 'elis_program',
            'crlm_class_graded' => 'elis_program',
            'crlm_class_instructor' => 'elis_program',
            'crlm_class_moodle' => 'elis_program',
            'crlm_cluster' => 'elis_program',
            'crlm_cluster_assignments' => 'elis_program',
            'crlm_cluster_curriculum' => 'elis_program',
            'crlm_cluster_profile' => 'elis_program',
            'crlm_cluster_track' => 'elis_program',
            'crlm_course' => 'elis_program',
            'crlm_coursetemplate' => 'elis_program',
            'crlm_curriculum' => 'elis_program',
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
            'user_enrolments' => 'moodle',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis'
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

        return array(
            student::TABLE     => 'elis_program',
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
            'external_services_users'      => 'moodle'
        );
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private function init_contexts_and_site_course() {
        global $DB;

        $siteid = SITEID ? SITEID : 1; // TBD
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, $siteid));
        //set up the site course record
        if ($record = self::$origdb->get_record('course',
                                                array('id' => $siteid))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        build_context_path();
    }

    /**
     * Test data provider
     *
     * @return array the test data
     */
    public function dataProviderForTests() {
        global $NO_TEST_SETUP;

        $testdata = array();

        // course create - no idnumber
        $testdata[] = array(
            'create',
            'course',
            array(
                'name'        => 'coursename'
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // course create - no name
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // course create - ok!
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
                'name'        => 'coursename',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // course create - all fields - ok!
        $testdata[] = array(
            'create',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
                'name'        => 'coursename',
                'code'        => 'coursecode',
                'syllabus'    => 'course syllabus',
                'lengthdescription'=> 'Length Description',
                'length'      => '100',
                'credits'     => '7.5',
                'completion_grade'=> '65',
                'cost'        => '$355.80',
                'version'     => '1.01',
                'assignment'  => 'programidnumber',
                //'link'        => 'Moodle Course shortname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // course update - no id
        $testdata[] = array(
            'update',
            'course',
            array(
                'name'        => 'coursenamechanged1'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // course update - ok!
        $testdata[] = array(
            'update',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
                'name'        => 'coursenamechanged2',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Course update - all fields being updated
        $testdata[] = array(
            'update',
            'course',
            array(
                'idnumber'         => 'courseidnumber',
                'name'             => 'coursename_update',
                'code'             => 'coursecode_update',
                'syllabus'         => 'course syllabus - update',
                'lengthdescription'=> 'Years',
                'length'           => '2',
                'credits'          => '10.75',
                'completion_grade' => '75',
                'cost'             => '$499.99',
                'version'          => '2.5',
                'assignment'       => 'programidnumber',
                //'link'             => 'Moodle Course shortname'
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // course delete - no id
        $testdata[] = array(
            'delete',
            'course',
            array(
                'name'        => 'coursename'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // course delete - ok
        $testdata[] = array(
            'delete',
            'course',
            array(
                'idnumber'    => 'courseidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // curriculum create - no id
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'name'        => 'programname'
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // curriculum create - no name
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // curriculum create - ok!
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // curriculum create - all fields - ok!
        $testdata[] = array(
            'create',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
                'description' => 'Program Description',
                'reqcredits'  => '7.5',
                'timetocomplete'=> '1h,2d,3w,4m,5y',
                'frequency'   => '6h,7d,8w,9m,1y',
                'priority'    => '2'
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // curriculum update - no id
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'name'        => 'programnamechanged1'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // curriculum update - ok!
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programnamechanged2',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // Curriculum Update - all fields being updated
        $testdata[] = array(
            'update',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname_update',
                'description' => 'Program Description - Update',
                'reqcredits'  => '5.0',
                'timetocomplete'=> '3h,4d,1w,5m,2y',
                'frequency'   => '6m,1y',
                'priority'    => '1'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // curriculum delete - no id
        $testdata[] = array(
            'delete',
            'curriculum',
            array(
                'name'        => 'programname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // curriculum delete - ok
        $testdata[] = array(
            'delete',
            'curriculum',
            array(
                'idnumber'    => 'programidnumber',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // curriculum as curr create - ok!
        $testdata[] = array(
            'create',
            'curr',
            array(
                'idnumber'    => 'programidnumber',
                'name'        => 'programname',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // track create - no assignment
        $testdata[] = array(
            'create',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // track create - no name
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // track create - no idnumber
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // track create - ok!
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // track create - all-fields ok!
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'description' => 'Track Description',
                'startdate'   => 'Jan/13/2012',
                'enddate'     => 'Jun/13/2012',
                'autocreate'  => 'yes',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // track create - date format YYYY.MM.DD
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '2000.12.25',
                'enddate'     => '2001.01.02',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // track create - date format DD-MM-YYYY
        $testdata[] = array(
            'create',
           'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '25-12-2000',
                'enddate'     => '24-11-2001',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // track create - date format MM/DD/YYYY
        $testdata[] = array(
            'create',
            'track',
            array(
                'assignment'  => 'programidnumber',
                'idnumber'    => 'trackidnumber',
                'name'        => 'trackname',
                'startdate'   => '12/25/2000',
                'enddate'     => '11/24/2001',
            ),
            array(TEST_SETUP_CURRICULUM),
            ELIS_ENTITY_EXISTS
        );

        // track update - no id
        $testdata[] = array(
            'update',
            'track',
            array(
                'name'        => 'tracknamechanged1'
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // track update - ok!
        $testdata[] = array(
            'update',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
                'name'        => 'tracknamechanged2',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // track delete - no id
        $testdata[] = array(
            'delete',
            'track',
            array(
                'name'        => 'trackname'
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // track delete - ok
        $testdata[] = array(
            'delete',
            'track',
            array(
                'idnumber'    => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_TRACK),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // class create - no assignment
        $testdata[] = array(
            'create',
            'class',
            array(
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // class create - no idnumber
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // class create - ok!
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // class create - all fields - ok!
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => 'Jan/13/2012',
                'enddate'     => 'Jun/13/2012',
                'starttimehour'=> '13',
                'starttimeminute'=> '15',
                'endtimehour'=> '14',
                'endtimeminute'=> '25',
                'maxstudents' => '35',
                'enrol_from_waitlist'=> 'yes',
                'track'       => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_COURSE, TEST_SETUP_TRACK),
            ELIS_ENTITY_EXISTS
        );

        // class create - date format YYYY.MM.DD
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '2000.12.25',
                'enddate'     => '2001.11.24'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // class create - date format DD-MM-YYYY
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '25-12-2000',
                'enddate'     => '20-11-2001'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // class create - date format MM/DD/YYYY
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'  => 'courseidnumber',
                'idnumber'    => 'classidnumber',
                'startdate'   => '12/25/2000',
                'enddate'     => '11/20/2001'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 0
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 0
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = no
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'no'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 1
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 1
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - enrol_from_waitlist = 'yes'
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'yes'
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - starttime and endtime out of range (all set to 0)
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'      => 'courseidnumber',
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 0,
                'starttimeminute' => 0,
                'endtimehour'     => 0,
                'endtimeminute'   => 0
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_EXISTS
        );

        // Class Create - starttime and endtime out of range
        $testdata[] = array(
            'create',
            'class',
            array(
                'assignment'      => 'courseidnumber',
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 61,
                'starttimeminute' => 61,
                'endtimehour'     => 61,
                'endtimeminute'   => 61
            ),
            array(TEST_SETUP_COURSE),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // class update - no idnumber
        $testdata[] = array(
            'update',
            'class',
            array(
                'maxstudents' => 101,
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // class update - ok!
        $testdata[] = array(
            'update',
            'class',
            array(
                'idnumber'    => 'classidnumber',
                'maxstudents' => 100,
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - all fields being updated
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'startdate'           => 'Jun/7/2012',
                'enddate'             => 'Aug/31/2012',
                'starttimehour'       => '8',
                'starttimeminute'     => '45',
                'endtimehour'         => '10',
                'endtimeminute'       => '15',
                'maxstudents'         => '45',
                'enrol_from_waitlist' => 'no',
                'track'               => 'trackidnumber',
            ),
            array(TEST_SETUP_CURRICULUM, TEST_SETUP_COURSE, TEST_SETUP_TRACK, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 0
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 0
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = no
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'no'
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 1
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 1
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - enrol_from_waitlist = 'yes'
        $testdata[] = array(
            'update',
            'class',
            array(
                'assignment'          => 'courseidnumber',
                'idnumber'            => 'classidnumber',
                'enrol_from_waitlist' => 'yes'
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // Class Update - starttime out of range (i.e. unset)
        $testdata[] = array(
            'update',
            'class',
            array(
                'idnumber'        => 'classidnumber',
                'starttimehour'   => 61,
                'starttimeminute' => 61,
                'endtimehour'     => 61,
                'endtimeminute'   => 61
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // class delete - no id
        $testdata[] = array(
            'delete',
            'class',
            array(
                'maxstudents' => 0
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_EXISTS
        );

        // class delete - ok
        $testdata[] = array(
            'delete',
            'class',
            array(
                'idnumber'    => 'classidnumber',
            ),
            array(TEST_SETUP_COURSE, TEST_SETUP_CLASS),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // cluster create - no name
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'display'     => 'usersetdescription',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // cluster create - ok!
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetname',
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - minimal fields
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname'
            ),
            $NO_TEST_SETUP,
            ELIS_ENTITY_EXISTS
        );

        // cluster create - all fields - ok!
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'usersetname',
                'recursive'   => 0
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - parent field = "top"
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'top',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Create - parent field = "usersetname"
        $testdata[] = array(
            'create',
            'cluster',
            array(
                'name'        => 'usersetCname',
                'display'     => 'Userset C Description',
                'parent'      => 'usersetname',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // cluster update - no name
        $testdata[] = array(
            'update',
            'cluster',
            array(
                'display'     => 'usersetdescription2',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // cluster update - ok!
        $testdata[] = array(
            'update',
            'cluster',
            array(
                'name'        => 'usersetname',
                'display'     => 'usersetdescription3',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );
        // cluster delete - no name
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'display'     => 'usersetdescription',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_EXISTS
        );

        // Cluster Delete - minimal fields
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'name'     => 'usersetname',
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        // cluster delete - ok
        $testdata[] = array(
            'delete',
            'cluster',
            array(
                'name'        => 'usersetname',
                'recursive'   => 'yes'
            ),
            array(TEST_SETUP_CLUSTER),
            ELIS_ENTITY_DOESNOT_EXIST
        );

        return $testdata;
    }

    /**
     * Field mapping function to convert IP boolean column to user DB field
     *
     * @param array  $input    The input IP data fields
     * @param string $fieldkey The array key to check for boolean strings
     */
    public function map_bool_field(&$input, $fieldkey) {
        if (isset($input[$fieldkey])) {
            if ($input[$fieldkey] == 'no') {
                $input[$fieldkey] = '0';
            } else if ($input[$fieldkey] == 'yes') {
                $input[$fieldkey] = '1';
            }
        }
    }

    /**
     * Field mapping function to convert IP date columns to timestamp DB field
     *
     * @param array  $input    The input IP data fields
     * @param string $fieldkey The array key to check for date strings
     */
    public function map_date_field(&$input, $fieldkey) {
        if (isset($input[$fieldkey])) {
            $date = $input[$fieldkey];

            //determine which case we are in
            if (strpos($date, '/') !== false) {
                $delimiter = '/';
            } else if (strpos($date, '-') !== false) {
                $delimiter = '-';
            } else if (strpos($date, '.') !== false) {
                $delimiter = '.';
            } else {
                return false;
            }

            $parts = explode($delimiter, $date);

            if ($delimiter == '/') {
                //MMM/DD/YYYY or MM/DD/YYYY format
                list($month, $day, $year) = $parts;

                $months = array('jan', 'feb', 'mar', 'apr',
                                'may', 'jun', 'jul', 'aug',
                                'sep', 'oct', 'nov', 'dec');
                $pos = array_search(strtolower($month), $months);
                if ($pos !== false) {
                    $month = $pos + 1;
                }
            } else if ($delimiter == '-') {
                //DD-MM-YYYY format
                list($day, $month, $year) = $parts;
            } else {
                //YYYY.MM.DD format
                list($year, $month, $day) = $parts;
            }

            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            $input[$fieldkey] = $timestamp;
        }
    }

    /**
     * Class mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_class($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $input['courseid'] = $DB->get_field('crlm_course', 'id', array('idnumber' => $input['assignment']));
            unset($input['assignment']);
        }
        $this->map_date_field($input, 'startdate');
        $this->map_date_field($input, 'enddate');

        if (array_key_exists('starttimehour', $input)) {
            if ($input['starttimehour'] >= 25) {
                $input['starttimehour'] = 0;
            }
        }

        if (array_key_exists('starttimeminute', $input)) {
            if ($input['starttimeminute'] >= 61) {
                $input['starttimeminute'] = 0;
            }
        }

        if (array_key_exists('endtimehour', $input)) {
            if ($input['endtimehour'] >= 25) {
                $input['endtimehour'] = 0;
            }
        }

        if (array_key_exists('endtimeminute', $input)) {
            if ($input['endtimeminute'] >= 61) {
                $input['endtimeminute'] = 0;
            }
        }

        if (array_key_exists('track', $input)) {
            if ($shouldexist) {
                $this->assertFalse(!$DB->get_record('crlm_track', array('idnumber' => $input['track'])));
            }
            unset($input['track']);
        }
        $this->map_bool_field($input, 'autoenrol');
        if (array_key_exists('autoenrol', $input)) {
            //TBD: verify autoenrol ok???
            unset($input['autoenrol']);
        }
        $this->map_bool_field($input, 'enrol_from_waitlist');
        if (array_key_exists('track', $input)) {
            //TBD: test valid
            unset($input['track']);
        }
        return $input;
    }

    /**
     * Cluster mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_cluster($input, $shouldexist) {
        global $DB;
        if (array_key_exists('parent', $input)) {
            if ($input['parent'] == 'top') {
                unset($input['parent']);
                $input['parent'] = '0';
            } else {
                $input['parent'] = $DB->get_field('crlm_cluster', 'id', array('name' => $input['parent']));
            }
        }
        $this->map_bool_field($input, 'recursive');
        if (array_key_exists('recursive', $input)) {
            // TBD
            unset($input['recursive']);
        }
        return $input;
    }

    /**
     * Course mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_course($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $this->assertEquals($shouldexist, $DB->record_exists('crlm_curriculum_course', array()));
            unset($input['assignment']);
        }
        if (array_key_exists('syllabus', $input)) {
            $where  = $DB->sql_compare_text('syllabus') .' = ?';
            $params = array(substr($input['syllabus'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select('crlm_course', $where, $params));
            unset($input['syllabus']);
        }
        if (array_key_exists('link', $input)) {
            $mdl_courseid = $DB->get_field('course', 'id', array('shortname' => $input['link']));
            $this->assertEquals($shouldexist && $mdl_courseid !== false,
                                $DB->record_exists('crlm_coursetemplate', array('location' => $mdl_courseid)));
            unset($input['link']);
        }
        return $input;
    }

    /**
     * Curriculum mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_curriculum($input, $shouldexist) {
        global $DB;
        if (array_key_exists('description', $input)) {
            $where  = $DB->sql_compare_text('description') .' = ?';
            $params = array(substr($input['description'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select('crlm_curriculum', $where, $params));
            unset($input['description']);
        }
        return $input;
    }

    /**
     * Track mapping function to convert IP column to DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating if ELIS entity should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_track($input, $shouldexist) {
        global $DB;
        if (array_key_exists('assignment', $input)) {
            $input['curid'] = $DB->get_field('crlm_curriculum', 'id', array('idnumber' => $input['assignment']));
            unset($input['assignment']);
        }
        $this->map_bool_field($input, 'autocreate');
        if (array_key_exists('autocreate', $input)) {
            //TBD: verify autocreate ok???
            unset($input['autocreate']);
        }
        $this->map_date_field($input, 'startdate');
        $this->map_date_field($input, 'enddate');
        if (array_key_exists('description', $input)) {
            $where  = $DB->sql_compare_text('description') .' = ?';
            $params = array(substr($input['description'], 0, 32));
            $this->assertEquals($shouldexist, $DB->record_exists_select('crlm_track', $where, $params));
            unset($input['description']);
        }
        return $input;
    }

    /**
     * User import test cases
     *
     * @uses $DB
     * @dataProvider dataProviderForTests
     */
    public function test_elis_entity_import($action, $context, $entity_data, $setup_array, $entity_exists) {
        global $CFG, $DB;

        if (empty($context)) {
            $this->markTestSkipped("\nPHPunit test coding error, 'context' NOT set - skipping!\n");
            return;
        }

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        set_config('enable_curriculum_expiration', true, 'elis_program');
      /* *** TBD ***
        if ($context == 'course') {
            // create Moodle course with shortname='Moodle Course shortname'
            $DB->insert_record('course_categories', array('name' => 'Moodle Course Category 1'));
            create_course((object)array(
                'idnumber'  => 'Moodle Course ID',
                'shortname' => 'Moodle Course shortname','category'  => 1)
            );
        }
      ****** */

        $import_data = array(
            'action'  => $action,
            'context' => $context
        );
        foreach ($entity_data as $key => $value) {
            $import_data[$key] = $value;
        }

        try {
            foreach ($setup_array as $index) {
                $provider = new rlip_importprovider_mockcourse($this->test_setup_data[$index]);
                $importplugin = new rlip_importplugin_version1elis($provider);
                @$importplugin->run();
            }

            $provider = new rlip_importprovider_mockcourse($import_data);
            $importplugin = new rlip_importplugin_version1elis($provider);
            $importplugin->run();
        } catch (Exception $e) {
            mtrace("\nException in test_elis_entity_import(): ".$e->getMessage()."\n");
        }

        // Call any mapping functions to transform IP column to DB field
        $mapfcn = 'map_'. $context;
        if (method_exists($this, $mapfcn)) {
            $entity_data = $this->$mapfcn($entity_data, $entity_exists);
        }

        ob_start();
        var_dump($entity_data);
        $tmp = ob_get_contents();
        ob_end_clean();

        $crlm_table = $this->context_to_table[$context];
        ob_start();
        var_dump($DB->get_records($crlm_table));
        $crlm_table_data = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($entity_exists, $DB->record_exists($crlm_table, $entity_data),
                            "ELIS entity assertion: [mapped]entity_data ; {$crlm_table} = {$tmp} ; {$crlm_table_data}");
    }
}
