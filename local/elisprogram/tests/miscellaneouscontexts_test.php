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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

// Libs.
require_once(elispm::lib('data/user.class.php'));
require_once(elispm::lib('data/userset.class.php'));
require_once(elispm::lib('data/usermoodle.class.php'));
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::file('enrol/userset/moodleprofile/userset_profile.class.php'));

/**
 * Test miscellaneous usages of ELIS contexts. Initially done as part of Moodle 2.2 support.
 * @group local_elisprogram
 */
class miscellaneouscontexts_testcase extends elis_database_test {

    /**
     * Import userset data from CSVs.
     */
    protected function setup_usersets() {
        $dataset = $this->createCsvDataSet(array(
            userset::TABLE => elispm::file('tests/fixtures/userset.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import curriculum data from CSVs.
     */
    protected function setup_curriculum() {
        $dataset = $this->createCsvDataSet(array(
            curriculum::TABLE => elispm::file('tests/fixtures/curriculum.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import curriculumcourse data from CSVs.
     */
    protected function setup_curriculumcourse() {
        $dataset = $this->createCsvDataSet(array(
            curriculumcourse::TABLE => elispm::file('tests/fixtures/curriculum_course.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import curriculumstudent data from CSVs.
     */
    protected function setup_curriculumstudent() {
        $dataset = $this->createCsvDataSet(array(
            curriculumstudent::TABLE => elispm::file('tests/fixtures/curriculum_student.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import user data from CSVs.
     */
    protected function setup_users() {
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => elispm::file('tests/fixtures/pmuser.csv'),
            usermoodle::TABLE => elispm::file('tests/fixtures/usermoodle.csv'),
            'user' => elispm::file('tests/fixtures/mdluser.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import custom field data from CSVs.
     */
    protected function setup_usercustomfields() {
        $dataset = $this->createCsvDataSet(array(
            field::TABLE => elispm::file('tests/fixtures/user_field.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import course data from CSVs.
     */
    protected function setup_courses() {
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => elispm::file('tests/fixtures/pmcourse.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Import class data from CSVs.
     */
    protected function setup_classes() {
        $dataset = $this->createCsvDataSet(array(
            pmclass::TABLE => elispm::file('tests/fixtures/pmclass.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Create a test custom field.
     * @return field The test custom field.
     */
    protected function create_field($shortname, field_category &$cat, $context) {
        $data = new stdClass;
        $data->shortname = $shortname;
        $data->name = ' Test Field';
        $data->categoryid = $cat->id;
        $data->description = 'Test Field';
        $data->datatype = 'text';
        $data->forceunique = '0';
        $data->mform_showadvanced_last = 0;
        $data->multivalued = '0';
        $data->defaultdata = '';
        $data->manual_field_enabled = '1';
        $data->manual_field_edit_capability = '';
        $data->manual_field_view_capability = '';
        $data->manual_field_control = 'text';
        $data->manual_field_options_source = '';
        $data->manual_field_options = '';
        $data->manual_field_columns = 30;
        $data->manual_field_rows = 10;
        $data->manual_field_maxlength = 2048;

        $field = new field($data);
        $field->save();

        $fieldcontext = new field_contextlevel();
        $fieldcontext->fieldid      = $field->id;
        $fieldcontext->contextlevel = $context;
        $fieldcontext->save();

        return $field;
    }

    /**
     * Create a test custom field category.
     * @return field_category The test category.
     */
    protected function create_field_category($context) {
        $data = new stdClass;
        $data->name = context_elis_helper::get_class_for_level($context).' Test';

        $category = new field_category($data);
        $category->save();

        $categorycontext = new field_category_contextlevel();
        $categorycontext->categoryid   = $category->id;
        $categorycontext->contextlevel = $context;
        $categorycontext->save();

        return $category;
    }

    /**
     * Create a test userset.
     * @return userset The test userset.
     */
    public function create_userset(field $field=null) {
        $usrset = new userset;
        $usrset->reset_custom_field_list();
        $usrset->name = 'Test User Set For Field'.str_replace('.', '', microtime(true));
        $usrset->parent = '0';

        if (!empty($field)) {
            $fieldvar = 'field_'.$field->shortname;
            $usrset->$fieldvar='test field data';
        }

        $usrset->save();
        return $usrset;
    }

    /**
     * Create a test curriculum.
     * @return curriculum The test curriculum.
     */
    public function create_curriculum(field &$field = null) {
        $data = new stdClass;
        $data->courseid = '';
        $data->idnumber = 'testprg';
        $data->name = 'Test Program';
        $data->description = '';
        $data->reqcredits = '';
        $data->priority = '0';
        $data->timetocomplete = '';
        $data->frequency = '';

        if (!empty($field)) {
            $fieldvar = 'field_'.$field->shortname;
            $data->$fieldvar = 'test field data';
        }

        $cur = new curriculum();
        $cur->set_from_data($data);
        $cur->save();
        return $cur;
    }

    /**
     * Tests local/elisprogram/scripts/fix_cluster_orphans.php
     */
    public function test_fixclusterorphans() {
        global $DB;
        $this->setup_usersets();

        // Create orphan usersets.
        $DB->delete_records(userset::TABLE, array('id' => 1));

        // Run.
        try {
            require_once(elis::file('elisprogram/scripts/fix_cluster_orphans.php'));
        } catch (Exception $e) {
            if ($e->getMessage() !== 'Constant CLI_SCRIPT already defined') {
                throw new $e;
            }
        }
    }

    /**
     * Tests contexts in track data object.
     *
     * Covers:
     * local/elisprogram/lib/data/track.class.php:291
     */
    public function test_deletetrack () {
        $this->setup_curriculum();

        $data = new stdClass;
        $data->curid=1;
        $data->idnumber='TRK1';
        $data->name='Track 1';
        $data->description='Track Description';
        $data->startdate=0;
        $data->enddate='0';

        $trk = new track();
        $trk->set_from_data($data);
        $trk->save();

        $trk = new track($trk->id);
        $trk->delete();
    }

    /**
     * Tests contexts in userset data object.
     *
     * Covers:
     * local/elisprogram/lib/data/userset.class.php:334
     * local/elisprogram/lib/data/userset.class.php:453
     * local/elisprogram/lib/data/userset.class.php:561
     * local/elisprogram/lib/data/userset.class.php:595
     * local/elisprogram/lib/data/userset.class.php:616
     * local/elisprogram/lib/data/userset.class.php:721
     * local/elisprogram/lib/data/userset.class.php:755
     * local/elisprogram/lib/data/userset.class.php:847
     * local/elisprogram/lib/data/userset.class.php:901
     */
    public function test_usersetcontexts() {
        global $USER, $DB;
        require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));
        require_once(elispm::file('plugins/usetclassify/lib.php'));

        $this->setup_users();
        $this->setup_usersets();

        // TEST local/elisprogram/lib/data/userset.class.php:334.
        $res = userset::get_allowed_clusters(1);

        // TEST local/elisprogram/lib/data/userset.class.php:453.
        $ussfilter = new usersubset_filter('id', new field_filter('id', 1));
        $res = $ussfilter->get_sql();

        // TEST
        // local/elisprogram/lib/data/userset.class.php:561
        // local/elisprogram/lib/data/userset.class.php:595
        // local/elisprogram/lib/data/userset.class.php:616
        // local/elisprogram/lib/data/userset.class.php:721
        // local/elisprogram/lib/data/userset.class.php:755.
        $field = new field(array('shortname' => USERSET_CLASSIFICATION_FIELD));
        $field->load();
        $userset = $this->create_userset($field);

        // Get a role to assign.
        $rolesctx = $DB->get_records('role_context_levels', array('contextlevel' => CONTEXT_ELIS_USERSET));
        foreach ($rolesctx as $i => $rolectx) {
            $roleid = $rolectx->roleid;
        }

        // Add userset_view capability to our role.
        $usersetcontext = context_elis_userset::instance($userset->id);
        $rc = new stdClass;
        $rc->contextid = $usersetcontext->id;
        $rc->roleid = $roleid;
        $rc->capability = 'local/elisprogram:userset_view';
        $rc->permission = 1;
        $rc->timemodified = time();
        $rc->modifierid = 0;
        $DB->insert_record('role_capabilities', $rc);
        $rc = new stdClass;
        $rc->contextid = $usersetcontext->id;
        $rc->roleid = $roleid;
        $rc->capability = 'local/elisprogram:userset_enrol_userset_user';
        $rc->permission = 1;
        $rc->timemodified = time();
        $rc->modifierid = 0;
        $DB->insert_record('role_capabilities', $rc);

        // Assign role.
        $user  = new user(103);
        $muser = $user->get_moodleuser();
        $raid = role_assign($roleid, $muser->id, $usersetcontext->id);
        $this->setUser(100);

        // Assign other user to userset.
        $clst = new clusterassignment;
        $clst->clusterid = $userset->id;
        $clst->userid = 104;
        $clst->plugin = 'manual';
        $clst->save();

        // Get cluster listing.
        $capability = 'local/elisprogram:userset_view';
        $contexts = get_contexts_by_capability_for_user('cluster', $capability, 100);
        $extrafilters = array(
            'contexts' => $contexts,
            'classification' => 'test field data'
        );

        $res = cluster_get_listing('name', 'ASC', 0, 0, '', '', $extrafilters, 104);

        $res = cluster_count_records('', '', $extrafilters);

        // TEST local/elisprogram/lib/data/userset.class.php:847.
        cluster_get_non_child_clusters(1);

        // TEST local/elisprogram/lib/data/userset.class.php:901.
        cluster_get_possible_sub_clusters(1);

        $this->setUser(null);
    }

    /**
     * Tests contexts in user data object.
     *
     * Covers:
     * local/elisprogram/lib/data/user.class.php:169
     * local/elisprogram/lib/data/user.class.php:673
     * local/elisprogram/lib/data/user.class.php:1081
     * local/elisprogram/lib/data/user.class.php:1291
     */
    public function test_usercontexts() {
        global $DB;

        $this->setup_users();
        $this->setup_curriculum();
        $this->setup_curriculumstudent();
        $user = new user(103);
        // ELIS-5861 -- This method will write HTML to STDOUT, so wrap it in an output buffer.
        ob_start();
        $user->get_dashboard();
        ob_end_clean();

        $this->setUser(1);

        $this->setup_usercustomfields();
        $fieldname = 'sometext';
        $rec = new field($DB->get_record(field::TABLE, array('shortname' => $fieldname)));
        $ufilter = new pm_custom_field_filter('field_'.$fieldname, $rec->shortname, false, $rec);
        $sql = $ufilter->get_sql_filter(array('value' => 'test'));

        $ufiltering = new pm_user_filtering();
    }

    /**
     * Tests contexts in course data object.
     *
     * Covers:
     * local/elisprogram/lib/data/course.class.php:619
     */
    public function test_coursecontexts() {
        $this->setup_curriculum();
        $this->setup_curriculumcourse();
        $this->setup_courses();
        $crs = new course(100);
        $crs->delete();
    }

    /**
     * Tests contexts in pmclass data object.
     *
     * Covers:
     * local/elisprogram/lib/data/pmclass.class.php:259
     */
    public function test_classcontexts() {
        $this->setup_curriculum();
        $this->setup_curriculumcourse();
        $this->setup_courses();
        $this->setup_classes();

        $pmclass = new pmclass(101);
        $pmclass->delete();
    }

    /**
     * Tests contexts in curriculum data object.
     *
     * Covers:
     * local/elisprogram/lib/data/curriculum.class.php:109
     */
    public function test_curriculumcontexts() {
        $this->setup_curriculum();
        $cur = new curriculum(1);
        $cur->delete();
    }

    /**
     * Test contexts in notifications.
     *
     * Covers:
     * local/elisprogram/lib/notifications.php:616
     * local/elisprogram/lib/notifications.php:617
     */
    public function test_notifications() {
        global $DB;
        $this->setup_users();
        context_helper::reset_caches();

        elis::$config->local_elisprogram->notify_classenrol_user = true;

        $rolesctx = $DB->get_records('role_context_levels', array('contextlevel' => CONTEXT_ELIS_PROGRAM));
        foreach ($rolesctx as $rolectx) {
            $roleid = $rolectx->roleid;
            break;
        }

        // Get user to assign role.
        $user  = new user(103);
        $muser = $user->get_moodleuser();

        // Get specific context.
        $cur = $this->create_curriculum();
        $context = context_elis_program::instance($cur->id);

        // Assign role.
        $raid = role_assign($roleid, $muser->id, $context);
    }

    /**
     * Test contexts in usersetclassification data object.
     *
     * Covers:
     * local/elisprogram/plugins/usetclassify/usersetclassification.class.php:132
     */
    public function test_usersetclassification() {
        require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));
        require_once(elispm::file('plugins/usetclassify/lib.php'));

        $cat = $this->create_field_category(CONTEXT_ELIS_USERSET);
        $field = $this->create_field(USERSET_CLASSIFICATION_FIELD, $cat, CONTEXT_ELIS_USERSET);
        $userset = $this->create_userset($field);

        $res = usersetclassification::get_for_cluster($userset);
    }

    /**
     * Test contexts in enrolment_role_sync
     *
     * Covers:
     * local/elisprogram/plugins/enrolrolesync/lib.php:101
     * local/elisprogram/plugins/enrolrolesync/lib.php:150
     */
    public function test_enrolrolesync() {
        require_once(elispm::file('plugins/enrolrolesync/lib.php'));

        set_config('student_role', 9, 'elisprogram_enrolrolesync');
        enrolment_role_sync::student_sync_role_set();

        set_config('instructor_role', 9, 'elisprogram_enrolrolesync');
        enrolment_role_sync::instructor_sync_role_set();
    }

    /**
     * Test contexts in userset_display_priority_append_sort_data
     *
     * Covers:
     * local/elisprogram/plugins/usetdisppriority/lib.php:43
     */
    public function test_usersetdisplaypriority() {
        require_once(elispm::file('plugins/usetdisppriority/lib.php'));
        $select = '';
        $join = '';
        userset_display_priority_append_sort_data('id', $select, $join);
    }

    /**
     * Test contexts for userset_groups
     *
     * Covers:
     * local/elisprogram/plugins/usetgroups/lib.php:643
     * local/elisprogram/plugins/usetgroups/lib.php:308
     * local/elisprogram/plugins/usetgroups/lib.php:601
     */
    public function test_usersetgroups() {
        global $DB;
        set_config('userset_groupings', true, 'elisprogram_usetgroups');

        $field = new field(array('shortname' => 'userset_groupings'));
        $field->load();
        $userset = $this->create_userset($field);
        userset_groups_grouping_helper($userset->id, $userset->name);

        $field = new field(array('shortname' => 'userset_group'));
        $field->load();
        $userset = $this->create_userset($field);
        userset_groups_userset_allows_groups($userset->id);
    }
}