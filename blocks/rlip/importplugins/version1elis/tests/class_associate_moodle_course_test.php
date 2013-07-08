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
require_once($CFG->dirroot.'/blocks/rlip/phpunit/silent_fslogger.class.php');

/**
 * Overlay database that allows for the handling of temporary tables
 */
class overlay_class_associate_moodle_course_database extends overlay_database {

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool true
     * @throws dml_exception if error
     */
    public function change_database_structure($sql) {
        if (strpos($sql, 'CREATE TEMPORARY TABLE ') === 0) {
            //creating a temporary table, so make it an overlay table

            //find the table name
            $start_pos = strlen('CREATE TEMPORARY TABLE ');
            $length = strpos($sql, '(') - $start_pos;
            $tablename = trim(substr($sql, $start_pos, $length));
            //don't use prefix when storing
            $tablename = substr($tablename, strlen($this->overlayprefix));

            //set it up as an overlay table
            $this->overlaytables[$tablename] = 'moodle';
            $this->pattern = '/{('.implode('|', array_keys($this->overlaytables)).')}/';
        }

        // FIXME: or should we just do nothing?
        return $this->basedb->change_database_structure($sql);
    }

    /**
     * Returns detailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        //determine if this is an overlay table
        $is_overlay_table = array_key_exists($table, $this->overlaytables);

        if ($is_overlay_table) {
            //temporarily set the prefix to the overlay prefix
            $cacheprefix = $this->basedb->prefix;
            $this->basedb->prefix = $this->overlayprefix; // HACK!!!
        }

        $result = $this->basedb->get_columns($table, $usecache);

        if ($is_overlay_table) {
            //restore proper prefix
            $this->basedb->prefix = $cacheprefix;
        }

        return $result;
    }

    /**
     * Empty out all the overlay tables.
     */
    public function reset_overlay_tables() {
        foreach ($this->overlaytables as $tablename => $component) {
            try {
                $this->delete_records($tablename);
            } catch (Exception $e) {
                //temporary table was already dropped
            }
        }
    }

    /**
     * Clean up the temporary tables.  You'd think that if this method was
     * called dispose, then the cleanup would happen automatically, but it
     * doesn't.
     */
    public function cleanup() {
        $manager = $this->get_manager();
        foreach ($this->overlaytables as $tablename => $component) {
            $xmldb_file = $this->xmldbfiles[$component];
            $structure = $xmldb_file->getStructure();
            $table = $structure->getTable($tablename);
            // FIXME: when http://bugs.mysql.com/bug.php?id=10327 gets fixed,
            // we can switch this back to drop_temp_table
            if ($table === null) {
                //most likely a temporary table
                try {
                    //attempt to drop the temporary table
                    $table = new xmldb_table($tablename);
                    $manager->drop_table($table);
                } catch (Exception $e) {
                    //temporary table was already dropped
                }
            } else {
                //structure was defined in xml, so drop normal table
                $manager->drop_table($table);
            }
        }
    }
}

/**
 * Class for testing class instance-moodle course association
 * creation during class instance create and update actions
 */
class elis_class_associate_moodle_course_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        $tables = array(
            'backup_controllers' => 'moodle',
            'config' => 'moodle',
            'context' => 'moodle',
            'context_temp' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'course_format_options' => 'moodle',
            'course_modules' => 'moodle',
            'grade_categories' => 'moodle',
            'grade_items' => 'moodle',
            'groups' => 'moodle',
            'message' => 'moodle',
            'message_working' => 'moodle',
            'role' => 'moodle',
            'role_assignments' => 'moodle',
            'role_capabilities' => 'moodle',
            'user_enrolments' => 'moodle',
            field::TABLE => 'elis_core',
            classmoodlecourse::TABLE => 'elis_program',
            course::TABLE => 'elis_program',
            coursetemplate::TABLE => 'elis_program',
            pmclass::TABLE => 'elis_program',
            student::TABLE => 'elis_program',
            RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE => 'rlipimport_version1elis'
        );

        if ($DB->get_manager()->table_exists('backup_ids_temp')) {
            $tables['backup_ids_temp'] = 'moodle';
        }
        if ($DB->get_manager()->table_exists('backup_files_temp')) {
            $tables['backup_files_temp'] = 'moodle';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array(
            'block_instances' => 'moodle',
            'cache_flags' => 'moodle',
            'config_plugins' => 'moodle',
            'course_sections' => 'moodle',
            'enrol' => 'moodle',
            'grade_categories_history' => 'moodle',
            'grade_items_history' => 'moodle',
            'log' => 'moodle'
        );
    }

    public static function setUpBeforeClass() {
        // called before each test function
        global $DB;
        self::$origdb = $DB;
        self::$overlaydb = new overlay_class_associate_moodle_course_database($DB, static::get_overlay_tables(),
                                                                              static::get_ignored_tables());
    }

    public function setUp() {
        parent::setUp();

        $DB = self::$overlaydb;

        $sysctx = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_SYSTEM));
        if (!empty($sysctx)) {
            $DB->import_record('context', $sysctx);
        }

        $syscrsctx = self::$origdb->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => SITEID));
        if (!empty($syscrsctx)) {
            $DB->import_record('context', $syscrsctx);
        }

        $syscrs = self::$origdb->get_record('course', array('id' => SITEID));
        if (!empty($syscrs)) {
            $DB->import_record('course', $syscrs);
        }

        accesslib_clear_all_caches(true);
    }

    /**
     * Data provider for testing various linking scenarios
     *
     * @return array The necessary data for testing
     */
    function link_course_provider() {
        return array(//use CD template course to auto-create template course
            array('auto'),
            //link to a specific Moodle course
            array('testcourseshortname')
        );
    }

    /**
     * Validate that class instance-moodle course associations
     * can be created during a class instance create action
     *
     * @param string $link The link attribute to use in the import, or 'auto' to auto-create
     *                     from template
     * @dataProvider link_course_provider
     */
    function test_associate_moodle_course_during_class_create($link) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));

        //make sure $USER is set up for backup/restore
        $USER = $DB->get_record_select('user', "username != 'guest' and DELETED = 0", array(), '*', IGNORE_MULTIPLE);
        $GLOBAL['USER'] = $USER;

        //need the moodle/backup:backupcourse capability
        $guestroleid = create_role('guestrole', 'guestrole', 'guestrole');
        set_config('guestroleid', $guestroleid);
        set_config('siteguest', '');

        $systemcontext = context_system::instance();
        $roleid = create_role('testrole', 'testrole', 'testrole');
        assign_capability('moodle/backup:backupcourse', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $USER->id, $systemcontext->id);

        set_config('siteadmins', $USER->id);

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        context_coursecat::instance($coursecategory->id);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        //need this for the 'auto' case, at the very least
        $coursetemplate = new coursetemplate(array('courseid' => $course->id,
                                                   'location' => $moodlecourse->id,
                                                   'templateclass' => 'moodlecourseurl'));
        $coursetemplate->save();

        //run the class instance create action
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        $record->link = $link;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_create($record, 'bogus');

        $classid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => 'testclassidnumber'));

        //validation
        if ($record->link == 'auto') {
            $moodlecourseid = $moodlecourse->id + 1;
        } else {
            $moodlecourseid = $moodlecourse->id;
        }
        $db_autocreated = $record->link == 'auto' ? 1 : 0;
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $classid,
                                                                             'moodlecourseid' => $moodlecourseid,
                                                                             'enroltype' => 0,
                                                                             'enrolplugin' => 'crlm',
                                                                             'autocreated' => $db_autocreated)));
    }

    /**
     * Validate that class instance-moodle course associations
     * can be created during a class instance update action
     *
     * @param string $link The link attribute to use in the import, or 'auto' to auto-create
     *                     from template
     * @dataProvider link_course_provider
     */
    function test_associate_moodle_course_during_class_update($link) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/coursetemplate.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));

        //make sure $USER is set up for backup/restore
        $USER->id = $DB->get_field_select('user', 'id', "username != 'guest' AND deleted = 0", array(), IGNORE_MULTIPLE);

        //need the moodle/backup:backupcourse capability
        $guestroleid = create_role('guestrole', 'guestrole', 'guestrole');
        set_config('guestroleid', $guestroleid);
        set_config('siteguest', '');

        $systemcontext = context_system::instance();
        $roleid = create_role('testrole', 'testrole', 'testrole');
        assign_capability('moodle/backup:backupcourse', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $USER->id, $systemcontext->id);

        //set up the site course record
//        $DB->execute("INSERT INTO {course}
//                      SELECT * FROM ".self::$origdb->get_prefix()."course
//                      WHERE id = ?", array(SITEID));

        $coursecategory = new stdClass;
        $coursecategory->name = 'testcoursecategoryname';
        $coursecategory->id = $DB->insert_record('course_categories', $coursecategory);

        $moodlecourse = new stdClass;
        $moodlecourse->category = $coursecategory->id;
        $moodlecourse->shortname = 'testcourseshortname';
        $moodlecourse->fullname = 'testcoursefullname';
        $moodlecourse = create_course($moodlecourse);

        $course = new course(array('name' => 'testcoursename',
                                   'idnumber' => 'testcourseidnumber',
                                   'syllabus' => ''));
        $course->save();

        $class = new pmclass(array('courseid' => $course->id,
                                   'idnumber' => 'testclassidnumber'));
        $class->save();

        //need this for the 'auto' case, at the very least
        $coursetemplate = new coursetemplate(array('courseid' => $course->id,
                                                   'location' => $moodlecourse->id,
                                                   'templateclass' => 'moodlecourseurl'));
        $coursetemplate->save();

        //run the class instance create action
        $record = new stdClass;
        $record->idnumber = 'testclassidnumber';
        $record->assignment = 'testcourseidnumber';
        $record->link = $link;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(NULL);
        $importplugin->class_update($record, 'bogus');

        //validation
        if ($record->link == 'auto') {
            $moodlecourseid = $moodlecourse->id + 1;
        } else {
            $moodlecourseid = $moodlecourse->id;
        }
        $db_autocreated = $record->link == 'auto' ? 1 : 0;
        $this->assertTrue($DB->record_exists(classmoodlecourse::TABLE, array('classid' => $class->id,
                                                                             'moodlecourseid' => $moodlecourseid,
                                                                             'enroltype' => 0,
                                                                             'enrolplugin' => 'crlm',
                                                                             'autocreated' => $db_autocreated)));
    }
}
