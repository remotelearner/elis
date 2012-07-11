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
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

/**
 * Class for validating that field mappings work correctly during the ELIS
 * PM entity (i.e. "Course") import
 */
class elis_pmentity_field_mappings_test extends elis_database_test {
    private $mapping = array('action' => 'customaction',
                             'context' => 'customcontext',
                             'idnumber' => 'customidnumber',
                             'name' => 'customname',
                             'description' => 'customdescription',
                             'reqcredits' => 'customreqcredits',
                             'timetocomplete' => 'customtimetocomplete',
                             'frequency' => 'customfrequency',
                             'priority' => 'custompriority',
                             'startdate' => 'customstartdate',
                             'enddate' => 'customenddate',
                             'autocreate' => 'customautocreate',
                             'assignment' => 'customassignment',
                             'starttimehour' => 'customstarttimehour',
                             'starttimeminute' => 'customstarttimeminute',
                             'endtimehour' => 'customendtimehour',
                             'endtimeminute' => 'customendtimeminute',
                             'maxstudents' => 'custommaxstudents',
                             'enrol_from_waitlist' => 'customenrol_from_waitlist',
                             'track' => 'customtrack',
                             'autoenrol' => 'customautoenrol',
                             'link' => 'customlink',
                             'display' => 'customdisplay',
                             'parent' => 'customparent',
                             'recursive' => 'customrecursive',
                             'testfieldshortname' => 'customtestfieldshortname');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/curriculumcourse.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/userset.class.php'));

        return array('cache_flags' => 'moodle',
                     'config' => 'moodle',
                     'context' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis',
                     course::TABLE => 'elis_program',
                     curriculum::TABLE => 'elis_program',
                     curriculumcourse::TABLE => 'elis_program',
                     field::TABLE => 'elis_core',
                     pmclass::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     userset::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/coursetemplate.class.php'));

        return array('block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'log' => 'moodle',
                     coursetemplate::TABLE => 'elis_program');
    }

    /**
     * Initialize the db records needed to represent the field mapping
     */
    private function init_mapping() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');

        foreach ($this->mapping as $standardfieldname => $customfieldname) {
            $mapping = new stdClass;
            $mapping->entitytype = 'user';
            $mapping->standardfieldname = $standardfieldname;
            $mapping->customfieldname = $customfieldname;

            $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);
        }
    }

    /**
     * Create the necessary custom field
     *
     * @param int $contextlevel The context level at which to create the custom field
     * @return int The id of the created field
     */
    private function create_custom_field($contextlevel) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        //field category
        $field_category = new field_category(array('name' => 'testcategoryname'));
        $field_category->save();

        //custom field
        $field = new field(array('categoryid' => $field_category->id,
                                 'shortname' => 'testfieldshortname',
                                 'name' => 'testfieldname',
                                 'datatype' => 'bool'));
        $field->save();

        //field context level assocation
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => $contextlevel));
        $field_contextlevel->save();

        return $field->id;
    }

    /**
     * Validate that mappings are applied during the program create action
     */
    public function test_mapping_applied_during_program_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_PROGRAM);

        //run the program create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'curriculum';
        $record->customidnumber = 'testprogramidnumber';
        $record->customname = 'testprogramname';
        $record->customdescription = 'testprogramdescription';
        $record->customreqcredits = '0';
        $record->customtimetocomplete = '1d';
        $record->customfrequency = '1d';
        $record->custompriority = 0;
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('idnumber' => 'testprogramidnumber',
                      'name' => 'testprogramname',
                      'reqcredits' => 0,
                      'frequency' => DAYSECS,
                      'priority' => 0);
        $this->assertTrue($DB->record_exists(curriculum::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testprogramidnumber'));
        $this->assertEquals('testprogramdescription', $record->description);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the program update action
     */
    public function test_mapping_applied_during_program_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_PROGRAM);

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        //run the program update action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'curriculum';
        $record->customidnumber = 'testprogramidnumber';
        $record->customname = 'updatedtestprogramname';
        $record->customdescription = 'updatedtestprogramdescription';
        $record->customreqcredits = '1';
        $record->customtimetocomplete = '2d';
        $record->customfrequency = '2d';
        $record->custompriority = 1;
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('idnumber' => 'testprogramidnumber',
                      'name' => 'updatedtestprogramname',
                      'reqcredits' => 2,
                      'timetocomplete' => 2 * DAYSECS,
                      'frequency' => 2 * DAYSECS,
                      'priority' => 1);
        $this->assertTrue($DB->record_exists(curriculum::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testprogramidnumber'));
        $this->assertEquals('updatedtestprogramdescription', $record->description);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the program delete action
     */
    public function test_mapping_applied_during_program_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');

        $this->init_mapping();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        //run the program delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'curriculum';
        $record->customidnumber = 'testprogramidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(curriculum::TABLE));
    }

    /**
     * Validate that mappings are applied during the track create action
     */
    public function test_mapping_applied_during_track_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculumcourse.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_TRACK);

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $curriculumcourse = new curriculumcourse(array('curriculumid' => $program->id,
                                                       'courseid' => $course->id));
        $curriculumcourse->save();

        //run the track create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'track';
        $record->customidnumber = 'testtrackidnumber';
        $record->customname = 'testtrackname';
        $record->customdescription = 'testtrackdescription';
        $record->customstartdate = 'Jan/01/2012';
        $record->customenddate = 'Jan/01/2012';
        $record->customautocreate = 1;
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('idnumber' => 'testtrackidnumber',
                      'name' => 'testtrackname',
                      'startdate' => mktime(0, 0, 0, 1, 1, 2012),
                      'enddate' => mktime(0, 0, 0, 1, 1, 2012));
        $this->assertTrue($DB->record_exists(track::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testtrackidnumber'));
        $this->assertEquals('testtrackdescription', $record->description);

        $this->assertEquals(1, $DB->count_records(pmclass::TABLE));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the track update action
     */
    public function test_mapping_applied_during_track_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_TRACK);

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the track update action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'track';
        $record->customidnumber = 'testtrackidnumber';
        $record->customname = 'updatedtesttrackname';
        $record->customdescription = 'updatedtesttrackdescription';
        $record->customstartdate = 'Jan/02/2012';
        $record->customenddate = 'Jan/02/2012';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('idnumber' => 'testtrackidnumber',
                      'name' => 'updatedtesttrackname',
                      'startdate' => mktime(0, 0, 0, 1, 2, 2012),
                      'enddate' => mktime(0, 0, 0, 1, 2, 2012));
        $this->assertTrue($DB->record_exists(track::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testtrackidnumber'));
        $this->assertEquals('updatedtesttrackdescription', $record->description);

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the track delete action
     */
    public function test_mapping_applied_during_track_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the program delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'track';
        $record->customidnumber = 'testtrackidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(track::TABLE));
    }

    /**
     * Validate that mappings are applied during the course description create action
     */
    public function test_mapping_applied_during_course_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculumcourse.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/coursetemplate.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_COURSE);

        set_config('defaultblocks_override', ' ');

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        //run the course create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'course';
        $record->customname = 'testcoursename';
        $record->customcode = 'testcoursecode';
        $record->customidnumber = 'testcourseidnumber';
        $record->customsyllabus = 'testcoursesyllabus';
        $record->customlengthdescription = 'testcourselengthdescription';
        $record->customlength = '1';
        $record->customcredits = '1';
        $record->customcompletion_grade = '50';
        $record->customcost = '5';
        $record->customversion = '1';
        $record->customassignment = 'testprogramidnumber';
        $record->customlink = 'testcourseshortname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('name' => 'testcoursename',
                      'code' => 'testcoursecode',
                      'idnumber' => 'testcourseidnumber',
                      'lengthdescription' => 'testlengthdescription',
                      'length' => 1,
                      'credits' => 1,
                      'completion_grade' => 50,
                      'cost' => '5',
                      'version' => '1');
        $this->assertTrue($DB->record_exists(course::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testcourseidnumber'));
        $this->assertEquals('testcoursesyllabus', $record->syllabus);

        $this->assertTrue($DB->record_exists(curriculumcourse::TABLE, array('curriculumid' => $program->id,
                                                                            'courseid' => $record->id)));
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array('courseid' => $record->id,
                                                                          'location' => $course->id)));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the course description update action
     */
    public function test_mapping_applied_during_course_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculumcourse.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/coursetemplate.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_COURSE);

        set_config('defaultblocks_override', ' ');

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        $pmcourse = new course(array('name' => 'testcoursename',
                                     'idnumber' => 'testcourseidnumber',
                                     'syllabus' => 'testcoursesyllabus'));
        $pmcourse->save();

        //run the course update action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'course';
        $record->customname = 'updatedtestcoursename';
        $record->customcode = 'updatedtestcoursecode';
        $record->customidnumber = 'testcourseidnumber';
        $record->customsyllabus = 'updatedtestcoursesyllabus';
        $record->customlengthdescription = 'updatedtestcourselengthdescription';
        $record->customlength = '2';
        $record->customcredits = '2';
        $record->customcompletion_grade = '100';
        $record->customcost = '10';
        $record->customversion = '2';
        $record->customassignment = 'testprogramidnumber';
        $record->customlink = 'testcourseshortname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('name' => 'updatedtestcoursename',
                      'code' => 'updatedtestcoursecode',
                      'idnumber' => 'testcourseidnumber',
                      'lengthdescription' => 'updatedtestlengthdescription',
                      'length' => 2,
                      'credits' => 2,
                      'completion_grade' => 100,
                      'cost' => '10',
                      'version' => '2');
        $this->assertTrue($DB->record_exists(course::TABLE, $data));

        $record = $DB->get_record(curriculum::TABLE, array('idnumber' => 'testcourseidnumber'));
        $this->assertEquals('updatedtestcoursesyllabus', $record->syllabus);

        $this->assertTrue($DB->record_exists(curriculumcourse::TABLE, array('curriculumid' => $program->id,
                                                                            'courseid' => $record->id)));
        $this->assertTrue($DB->record_exists(coursetemplate::TABLE, array('courseid' => $record->id,
                                                                          'location' => $course->id)));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the course description delete action
     */
    public function test_mapping_applied_during_course_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');

        $this->init_mapping();

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        //run the course delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'course';
        $record->customidnumber = 'testcourseidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(course::TABLE));
    }

    /**
     * Validate that mappings are applied during the class instance create action
     */
    public function test_mapping_applied_during_class_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/classmoodlecourse.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_CLASS);

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $pmcourse = new course(array('name' => 'testcoursename',
                                     'idnumber' => 'testcourseidnumber',
                                     'syllabus' => ''));
        $pmcourse->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        //run the class create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'class';
        $record->customidnumber = 'testclassidnumber';
        $record->customstartdate = 'Jan/01/2012';
        $record->customenddate = 'Jan/01/2012';
        $record->customstarttimehour = '1';       
        $record->customstarttimeminute = '5';
        $record->customendtimehour = '1';
        $record->customendtimeminute = '5';
        $record->custommaxstudents = '1';
        $record->custom_enrol_from_waitlist = '0';
        $record->customassignment = 'testcourseidnumber';
        $record->customtrack = 'testtrackidnumber';
        $record->customautoenrol = '1';
        $record->customlink = 'testcourseshortname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('courseid' => $pmcourse->id,
                      'idnumber' => 'testclassidnumber',
                      'startdate' => mktime(0, 0, 0, 1, 1, 2012),
                      'enddate' => mktime(0, 0, 0, 1, 1, 2012),
                      'starttimehour' => 1,
                      'starttimeminute' => 5,
                      'endtimehour' => 1,
                      'endtimeminute' => 5,
                      'maxstudents' => 1,
                      'enrol_from_waitlist' => 0);
        $this->assertTrue($DB->record_exists(pmclass::TABLE, $data));

        $record = $DB->get_record(pmclass::TABLE, array('idnumber' => 'testclassidnumber'));
        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array('classid' => $record->id,
                                                                           'trackid' => $track->id,
                                                                           'autoenrol' => 1)));
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $record->id,
                                                                             'moodlecourseid' => $course->id)));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the class instance update action
     */
    public function test_mapping_applied_during_class_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/classmoodlecourse.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/curriculum.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/track.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_CLASS);

        $DB->execute("INSERT INTO {context}
                      SELECT * FROM ".self::$origdb->get_prefix()."context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {course}
                      SELECT * FROM ".self::$origdb->get_prefix()."course
                      WHERE id = ?", array(SITEID));

        $pmcourse = new course(array('name' => 'testcoursename',
                                     'idnumber' => 'testcourseidnumber',
                                     'syllabus' => ''));
        $pmcourse->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'testcourseshortname';
        $course->fullname = 'testcoursefullname';
        $course = create_course($course);

        $pmclass = new pmclass(array('courseid' => $pmcourse->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        //run the class create update
        $record = new stdClass;
        $record->customaction = 'update';
        $record->context = 'class';
        $record->customidnumber = 'testclassidnumber';
        $record->customstartdate = 'Jan/02/2012';
        $record->customenddate = 'Jan/02/2012';
        $record->customstarttimehour = '2';       
        $record->customstarttimeminute = '10';
        $record->customendtimehour = '2';
        $record->customendtimeminute = '10';
        $record->custommaxstudents = '2';
        $record->custom_enrol_from_waitlist = '0';
        $record->customassignment = 'testcourseidnumber';
        $record->customtrack = 'testtrackidnumber';
        $record->customautoenrol = '1';
        $record->customlink = 'testcourseshortname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('courseid' => $pmcourse->id,
                      'idnumber' => 'testclassidnumber',
                      'startdate' => mktime(0, 0, 0, 1, 2, 2012),
                      'enddate' => mktime(0, 0, 0, 1, 2, 2012),
                      'starttimehour' => 2,
                      'starttimeminute' => 10,
                      'endtimehour' => 2,
                      'endtimeminute' => 10,
                      'maxstudents' => 2,
                      'enrol_from_waitlist' => 0);
        $this->assertTrue($DB->record_exists(pmclass::TABLE, $data));

        $this->assertTrue($DB->record_exists(trackassignment::TABLE, array('classid' => $pmclass->id,
                                                                           'trackid' => $track->id,
                                                                           'autoenrol' => 1)));
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $pmclass->id,
                                                                             'moodlecourseid' => $course->id)));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the class instance delete action
     */
    public function test_mapping_applied_during_class_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/course.class.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/pmclass.class.php');

        $this->init_mapping();

        $pmcourse = new course(array('name' => 'testcoursename',
                                     'idnumber' => 'testcourseidnumber',
                                     'syllabus' => ''));
        $pmcourse->save();

        $pmclass = new pmclass(array('courseid' => $pmcourse->id,
                                     'idnumber' => 'testclassidnumber'));
        $pmclass->save();

        //run the course delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'class';
        $record->customidnumber = 'testclassidnumber';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(pmclass::TABLE));
    }

    /**
     * Validate that mappings are applied during the user set create action
     */
    public function test_mapping_applied_during_userset_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_USERSET);

        $parentuserset = new userset(array('name' => 'parentusersetname'));
        $parentuserset->save();

        //run the program create action
        $record = new stdClass;
        $record->customaction = 'create';
        $record->customcontext = 'cluster';
        $record->customname = 'testusersetname';
        $record->customdisplay = 'testusersetdisplay';
        $record->customparent = 'parentusersetname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('name' => 'testusersetname',
                      'display' => 'testusersetdisplay',
                      'parent' => $parentuserset->id);
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the user set update create action
     */
    public function test_mapping_applied_during_userset_update() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->init_mapping();

        $customfieldid = $this->create_custom_field(CONTEXT_ELIS_USERSET);

        $parentuserset = new userset(array('name' => 'parentusersetname'));
        $parentuserset->save();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //run the program create action
        $record = new stdClass;
        $record->customaction = 'update';
        $record->customcontext = 'cluster';
        $record->customname = 'testusersetname';
        $record->customdisplay = 'updatedtestusersetdisplay';
        $record->customparent = 'parentusersetname';
        $record->customtestcustomfieldshortname = '1';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $data = array('name' => 'testusersetname',
                      'display' => 'updatedtestusersetdisplay',
                      'parent' => $parentuserset->id);
        $this->assertTrue($DB->record_exists(userset::TABLE, $data));

        $this->assertTrue($DB->record_exists(field_data_int::TABLE, array('fieldid' => $fieldid,
                                                                          'userid' => 1,
                                                                          'data' => 1)));
    }

    /**
     * Validate that mappings are applied during the user set delete action
     */
    public function test_mapping_applied_during_userset_delete() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/data/userset.class.php');

        $this->init_mapping();

        $userset = new userset(array('name' => 'testusersetname'));
        $userset->save();

        //run the course delete action
        $record = new stdClass;
        $record->customaction = 'delete';
        $record->customcontext = 'cluster';
        $record->customname = 'testclustername';

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->process_record('course', $record, 'bogus');

        //validation
        $this->assertEquals(0, $DB->count_records(userset::TABLE));
    }
}