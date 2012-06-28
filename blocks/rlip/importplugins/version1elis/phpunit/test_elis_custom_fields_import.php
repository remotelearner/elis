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
                     'external_services_users'      => 'moodle',
                     'external_tokens'              => 'moodle',
                     'external_services_users'      => 'moodle');
    }

    // Create and update cluster custom fields
    function test_elis_cluster_custom_field_import() {
        global $DB;

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldclustercategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testclustercheckboxshortname', 'name' => 'testclustercheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testclusterintshortname', 'name' => 'testclusterintname', 'datatype' => 'int', 'categoryid' => $id));
        $idshort = $DB->insert_record('elis_field', array('shortname' => 'testclustershortshortname', 'name' => 'testclustershortname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testclustertextareashortname', 'name' => 'testclustertextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testclustertextshortname', 'name' => 'testclustertextname', 'datatype' => 'num', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->testclustercheckboxshortname = 1;
        $record->testclusterintshortname = 100;
        $record->testclustershortshortname = 'A';
        $record->testclustertextareashortname = 'textareadata';
        $record->testclustertextshortname = '2.0';

        $this->run_elis_course_import($record, false);

        $usercontext = context_elis_userset::instance($DB->get_field('crlm_cluster', 'id', array('name' => 'testcluster')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 100)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.00000')));

        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'cluster';
        $record->name = 'testcluster';
        $record->parent = 0;
        $record->testclustercheckboxshortname = 0;
        $record->testclusterintshortname = 200;
        $record->testclustershortshortname = 'B';
        $record->testclustertextareashortname = 'textareadataupdated';
        $record->testclustertextshortname = '3.0';

        $this->run_elis_course_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 200)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.00000')));
    }

    // Create and update class custom fields
    function test_elis_class_custom_field_import() {
        global $DB;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcourse';
        $importplugin->course_create($record, 'bogus');

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldclasscategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testclasscheckboxshortname', 'name' => 'testtrackcheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testclassintshortname', 'name' => 'testtrackintname', 'datatype' => 'int', 'categoryid' => $id));
        $idshort = $DB->insert_record('elis_field', array('shortname' => 'testclassshortshortname', 'name' => 'testtrackshortname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testclasstextareashortname', 'name' => 'testtracktextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testclasstextshortname', 'name' => 'testtracktextname', 'datatype' => 'num', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->assignment = 'testcourseid';
        $record->testclasscheckboxshortname = 1;
        $record->testclassintshortname = 100;
        $record->testclassshortshortname = 'A';
        $record->testclasstextareashortname = 'textareadata';
        $record->testclasstextshortname = '2.0';

        $this->run_elis_course_import($record, false);

        $usercontext = context_elis_class::instance($DB->get_field('crlm_class', 'id', array('idnumber' => 'testclassid')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 100)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.00000')));

        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'class';
        $record->idnumber = 'testclassid';
        $record->testclasscheckboxshortname = 0;
        $record->testclassintshortname = 200;
        $record->testclassshortshortname = 'B';
        $record->testclasstextareashortname = 'textareadataupdated';
        $record->testclasstextshortname = '3.0';

        $this->run_elis_course_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 200)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.00000')));
    }

    // Create and update course custom fields
    function test_elis_course_custom_field_import() {
        global $DB;

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldcoursecategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testcoursecheckboxshortname', 'name' => 'testtrackcheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testcourseintshortname', 'name' => 'testtrackintname', 'datatype' => 'int', 'categoryid' => $id));
        $idshort = $DB->insert_record('elis_field', array('shortname' => 'testcourseshortshortname', 'name' => 'testtrackshortname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testcoursetextareashortname', 'name' => 'testtracktextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testcoursetextshortname', 'name' => 'testtracktextname', 'datatype' => 'num', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'course';
        $record->name= 'testcourse';
        $record->idnumber = 'testcourseid';
        $record->testcoursecheckboxshortname = 1;
        $record->testcourseintshortname = 100;
        $record->testcourseshortshortname = 'A';
        $record->testcoursetextareashortname = 'textareadata';
        $record->testcoursetextshortname = '2.0';

        $this->run_elis_course_import($record, false);

        $usercontext = context_elis_course::instance($DB->get_field('crlm_course', 'id', array('idnumber' => 'testcourseid')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 100)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.00000')));

        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'course';
        $record->idnumber = 'testcourseid';
        $record->name = 'testcoure';
        $record->testcoursecheckboxshortname = 0;
        $record->testcourseintshortname = 200;
        $record->testcourseshortshortname = 'B';
        $record->testcoursetextareashortname = 'textareadataupdated';
        $record->testcoursetextshortname = '3.0';

        $this->run_elis_course_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 200)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.00000')));
    }

    // Create and update track custom fields
    function test_elis_track_custom_field_import() {
        global $DB;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $record = new stdClass;
        $record->action = 'create';
        $record->context  = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $importplugin->curriculum_create($record, 'bogus');

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldtrackcategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testtrackcheckboxshortname', 'name' => 'testtrackcheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testtrackintshortname', 'name' => 'testtrackintname', 'datatype' => 'int', 'categoryid' => $id));
        $idshort = $DB->insert_record('elis_field', array('shortname' => 'testtrackshortshortname', 'name' => 'testtrackshortname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testtracktextareashortname', 'name' => 'testtracktextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testtracktextshortname', 'name' => 'testtracktextname', 'datatype' => 'num', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'track';
        $record->name= 'testtrack';
        $record->idnumber = 'testtrackid';
        $record->assignment = 'testprogramid';
        $record->testtrackcheckboxshortname = 1;
        $record->testtrackintshortname = 100;
        $record->testtrackshortshortname = 'A';
        $record->testtracktextareashortname = 'textareadata';
        $record->testtracktextshortname = '2.0';

        $this->run_elis_course_import($record, false);

        $usercontext = context_elis_track::instance($DB->get_field('crlm_track', 'id', array('idnumber' => 'testtrackid')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 100)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.0')));

        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'track';
        $record->idnumber = 'testtrackid';
        $record->testtrackcheckboxshortname = 0;
        $record->testtrackintshortname = 200;
        $record->testtrackshortshortname = 'B';
        $record->testtracktextareashortname = 'textareadataupdated';
        $record->testtracktextshortname = '3.0';

        $this->run_elis_course_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 200)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.0')));
    }

    // Create and update program custom fields
    function test_elis_program_custom_field_import() {
        global $DB;

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldprogramcategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testprogramcheckboxshortname', 'name' => 'testprogramcheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testprogramintshortname', 'name' => 'testprogramintname', 'datatype' => 'int', 'categoryid' => $id));
        $idshort = $DB->insert_record('elis_field', array('shortname' => 'testprogramshortshortname', 'name' => 'testprogramshortname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testprogramtextareashortname', 'name' => 'testprogramtextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testprogramtextshortname', 'name' => 'testprogramtextname', 'datatype' => 'num', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->testprogramcheckboxshortname = 1;
        $record->testprogramintshortname = 100;
        $record->testprogramshortshortname = 'A';
        $record->testprogramtextareashortname = 'textareadata';
        $record->testprogramtextshortname = '2.0';

        $this->run_elis_course_import($record, false);

        $usercontext = context_elis_program::instance($DB->get_field('crlm_curriculum', 'id', array('idnumber' => 'testprogramid')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 100)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.00000')));

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->context = 'curriculum';
        $record->idnumber = 'testprogramid';
        $record->name = 'testprogram';
        $record->testprogramcheckboxshortname = 0;
        $record->testprogramintshortname = 200;
        $record->testprogramshortshortname = 'B';
        $record->testprogramtextareashortname = 'textareadataupdated';
        $record->testprogramtextshortname = '3.0';

        $this->run_elis_course_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 200)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idshort, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.00000')));
    }

    // Create and update user custom fields
    function test_elis_user_custom_field_import() {
        global $DB;

        $id = $DB->insert_record('elis_field_categories', array('name' => 'testelisfieldcategory'));

        $idchkbox = $DB->insert_record('elis_field', array('shortname' => 'testcheckboxshortname', 'name' => 'testcheckboxname', 'datatype' => 'bool', 'categoryid' => $id));
        $iddatetime = $DB->insert_record('elis_field', array('shortname' => 'testdatetimeshortname', 'name' => 'testdatetimename', 'datatype' => 'text', 'categoryid' => $id));
        $idmenu = $DB->insert_record('elis_field', array('shortname' => 'testmenushortname', 'name' => 'testmenuname', 'datatype' => 'char', 'categoryid' => $id));
        $idtextarea = $DB->insert_record('elis_field', array('shortname' => 'testtextareashortname', 'name' => 'testtextareaname', 'datatype' => 'text', 'categoryid' => $id));
        $idtext = $DB->insert_record('elis_field', array('shortname' => 'testtextshortname', 'name' => 'testtextname', 'datatype' => 'num', 'categoryid' => $id));
        $idint = $DB->insert_record('elis_field', array('shortname' => 'testintshortname', 'name' => 'testintname', 'datatype' => 'int', 'categoryid' => $id));

        $record = new stdClass;
        $record->action = 'create';
        $record->email = 'testuser@mail.com';
        $record->username = 'testuser';
        $record->idnumber = 'testuserid';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';
        $record->testcheckboxshortname = 1;
        $record->testdatetimeshortname = 'JAN/01/2012';
        $record->testmenushortname = 'A';
        $record->testtextareashortname = 'textareadata';
        $record->testtextshortname = '2.0';
        $record->testintshortname = 2;

        $this->run_elis_user_import($record, false);

        $usercontext = context_elis_user::instance($DB->get_field('crlm_user', 'id', array('idnumber' => 'testuserid')));

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 1)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 2)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:date',
                                            array('fieldid'=>$iddatetime, 'contextid'=>$usercontext->id, 'date' => 'JAN/01/2012'));
        $this->assertTrue($exists);
        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadata'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idmenu, 'contextid' => $usercontext->id, 'data' => 'A')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '2.00000')));

        // update
        $record = new stdClass;
        $record->action = 'update';
        $record->email = 'testuser@mail.com';
        $record->username = 'testuser';
        $record->idnumber = 'testuserid';
        $record->firstname = 'testuserfirstname';
        $record->lastname = 'testuserlastname';
        $record->country = 'CA';
        $record->testcheckboxshortname = 0;
        $record->testdatetimeshortname = 'FEB/01/2013';
        $record->testmenushortname = 'B';
        $record->testtextareashortname = 'textareadataupdated';
        $record->testtextshortname = '3.0';
        $record->testintshortname = 3;

        $this->run_elis_user_import($record, false);

        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idchkbox, 'contextid' => $usercontext->id, 'data' => 0)));
        $this->assertTrue($DB->record_exists('elis_field_data_int', array('fieldid' => $idint, 'contextid' => $usercontext->id, 'data' => 3)));

        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:date',
                                            array('fieldid'=>$iddatetime, 'contextid'=>$usercontext->id, 'date' => 'FEB/01/2013'));
        $this->assertTrue($exists);
        $exists = $DB->record_exists_select('elis_field_data_text', "fieldid=:fieldid AND contextid=:contextid AND ".$DB->sql_compare_text('data').'=:data',
                                            array('fieldid'=>$idtextarea, 'contextid'=>$usercontext->id, 'data' => 'textareadataupdated'));
        $this->assertTrue($exists);

        $this->assertTrue($DB->record_exists('elis_field_data_char', array('fieldid' => $idmenu, 'contextid' => $usercontext->id, 'data' => 'B')));
        $this->assertTrue($DB->record_exists('elis_field_data_num', array('fieldid' => $idtext, 'contextid' => $usercontext->id, 'data' => '3.00000')));
    }

    /**
     * Helper function that runs the program import for a sample program
     *
     * @param array $extradata Extra fields to set for the new program
     */
    private function run_elis_course_import($extradata, $use_default_data = true) {
        global $CFG;

        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        if ($use_default_data) {
            $data = $this->get_core_program_data();
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

/**
 * Class that fetches import files for the program import
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


