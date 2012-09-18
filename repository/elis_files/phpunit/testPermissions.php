<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));

require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/ELIS_files.php');
require_once($CFG->dirroot.'/elis/program/lib/setup.php');

define('TEST_PREFIX', 'elis_files_test_');

/**
 * Mock class that allows us to not run back-end Alfresco calls when we can avoid them
 * @author brendan
 *
 */
class mock_repository_elis_files extends repository_elis_files {
    /*
     * Calculate the 'top' of the breadcrumb and then call the requested get_parent_path method
     * @param   string  uuid    node uuid
     * @param   array   path    breadcrumb path to node uuid
     * @param   int     cid     course id related to node uuid
     * @param   int     uid     user id related to node uuid
     * @param   int     shared  shared flag related to node uuid
     * @param   int     oid     user set id related to node uuid
     * @param   string  type    type of parent path retrieval - either tree or parent
     * @return  boolean         Return true if uuid is at root = e.g. end = uuid
     */
    function get_parent_path($uuid, &$path, $cid, $uid, $shared, $oid, $type = 'parent') {
        $path = array($uuid);
    }
}

/**
 * Mock class that allows up to not run back-end Alfresco calls when we can avoid
 * them
 */
class mock_ELIS_files extends ELIS_files {
    var $cuuid = '';
    var $ouuid = '';
    var $uuuid = '';
    var $suuid = '';

 /**
 * Verify that the Alfresco repository is currently setup and ready to be
 * used with Moodle (i.e. the needed directory structure is in place).
 *
 * @uses $CFG
 * @param none
 * @return bool True if setup, False otherwise.
 */
    function verify_setup() {

        $valid_setup = parent::verify_setup();

        $this->cuuid = "mockcuuid";
        $this->ouuid = "mockouuid";
        $this->uuuid = "testuuid";
        $this->suuid = "mocksuuid";

        return $valid_setup;
    }

    /**
     * Get the full path of a specific content node in the repository.
     *
     * @param string $uuid Unique identifier for a node.
     * @return string|bool A string  False.
     */
    function get_file_path($uuid) {
        return $uuid;
    }

   /*
    * Check if a give node is in the parent path
    *
    * @param    string  $uuid           The Unique identifier for a node
    * @param    string  $compareduuid   Unique identifier for a node to compare against
    * @return   bool                    True if the node is in the parent path, otherwise, false
    */
    function match_uuid_path($uuid, $compareduuid, $result) {
        return $uuid == $compareduuid;
    }

    /**
     * Get info about a specific node reference.
     *
     * @param string $uuid A node UUID value.
     */
    function get_info($uuid) {
        $result = new stdClass;

        // Return the correct info
        if ($uuid == 'testsuuid') {
            $result->uuid = $this->suuid;
            $result->title = 'fixedtitle';
        } else if ($uuid == 'testcuuid') {
            $result->uuid = 'testcuuid';
            $result->title = 'testcourseshortname';
        } else if ($uuid == 'testouuid') {
            $result->uuid = 'testouuid';
            $result->title = 'testusersetname';
        } else if ($uuid == 'testuuid') {
            $result->uuid = 'testuuid';
            $result->title = 'testuserusername';
        } else {
            $result->title = 'fixedtitle';
            $result->uuid = 'infouuid';
        }
        return $result;
    }

    /**
     * Assign a user read access to a specific node.
     *
     * @param string $username The Alfresco user's username.
     * @param string $uuid     The Alfresco node UUID.
     * @return bool True on success, False otherwise.
     */
    function allow_read($username, $uuid) {
        return true;
    }


    /**
     * Assign a user write access to a specific node.
     *
     * @param string $username The Alfresco user's username.
     * @param string $uuid     The Alfresco node UUID.
     * @return bool True on success, False otherwise.
     */
    function allow_edit($username, $uuid) {
        return true;
    }

    /**
     * Attempt to create the given directory name within a certain folder.
     *
     * @param string $name        The name of the directory we're checking for.
     * @param string $uuid        The UUID of the parent directory we're checking for a name in.
     * @param string $description An optional description of the directory being created.
     * @param bool   $useadmin    Set to false to make sure that the administrative user configured in
     *                            the plug-in is not used for this operation (default: true).
     * @return bool True on success, False otherwise.
     */
    function create_dir($name, $uuid = '', $description = '', $useadmin = true) {
        // Do nothing
    }

    /**
     * Get the UUID of the course storage area.
     *
     * @param int  $cid    The course ID.
     * @param bool $create Set to false to not automatically create a course storage area.
     * @return string|bool The course store UUID value or, False on error.
     */
    function get_course_store($cid, $create = true) {
        return 'courseuuid';
    }

    /**
     * Get the UUID of the user personal storage area.
     *
     * @param int  $uid       The Moodle user ID.
     * @param bool $nomigrate Set to True to not force user data migration.
     * @return string|bool The user store UUID value or, False on error.
     */
    function get_user_store($uid, $nomigrate = false) {
        return 'useruuid';
    }

    /**
     * Get an Alfresco user's home directory UUID.
     *
     * @param string $username The Alfresco user's username.
     * @return string|bool The UUID of the home directory or, False.
     */
    function elis_files_userdir($username) {
        return 'useruuid';
    }

    /**
     * Get the parent of a specific node.
     *
     * @param string $uuid Unique identifier for a node.
     * @return object|bool An object representing the parent node or False.
     */
    function get_parent($uuid) {
        $parent = new stdClass;
        //set it to a 'valid' parent
        if ($uuid == 'testsuuid') {
            $parent->uuid = $this->suuid;
        } else if ($uuid == 'testcuuid') {
            $parent->uuid = $this->cuuid;
        } else if ($uuid == 'testouuid') {
            $parent->uuid = $this->ouuid;
        } else if ($uuid == 'testuuid') {
            $parent->uuid = $this->uuuid;
        } else {
            $parent->uuid = $this->suuid;
        }
        return $parent;
    }
}

class permissionsTest extends elis_database_test {
    protected $created_uuids = array();

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/clusterassignment.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('enrol/userset/moodle_profile/userset_profile.class.php'));
        require_once(elispm::file('plugins/userset_classification/usersetclassification.class.php'));

        return array(
            'config'                     => 'moodle',
            'config_plugins'             => 'moodle',
            'context'                    => 'moodle',
            'course'                     => 'moodle',
            'course_categories'          => 'moodle',
            'elis_files_course_store'    => 'repository_elis_files',
            'elis_files_userset_store'   => 'repository_elis_files',
            'role'                       => 'moodle',
            'role_assignments'           => 'moodle',
            'role_capabilities'          => 'moodle',
            'user'                       => 'moodle',
            clusterassignment::TABLE     => 'elis_program',
            course::TABLE                => 'elis_program',
            curriculumstudent::TABLE     => 'elis_program',
            field::TABLE                 => 'elis_core',
            field_category::TABLE        => 'elis_core',
            field_contextlevel::TABLE    => 'elis_core',
            field_data_char::TABLE       => 'elis_core',
            userset::TABLE               => 'elis_program',
            usersetclassification::TABLE => 'pmplugins_userset_classification',
            userset_profile::TABLE       => 'elis_program'
        );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array(
            'block_instances'     => 'moodle',
            'cache_flags'         => 'moodle',
            'course_sections'     => 'moodle',
            'enrol'               => 'moodle',
            'log'                 => 'moodle',
            'repository'          => 'moodle',
            'repository_instance' => 'moodle',
             coursetemplate::TABLE => 'elis_program'
        );
    }

    protected function setUp() {

        parent::setUp();

        $rs = self::$origdb->get_recordset('config_plugins', array('plugin' => 'elis_files'));

        if ($rs->valid()) {
            foreach ($rs as $setting) {
                self::$overlaydb->import_record('config_plugins', $setting);
            }
            $rs->close();
        }
//        $GLOBALS['USER'] = get_admin();
    }

    protected function tearDown() {
        foreach ($this->created_uuids as $uuid) {
            elis_files_delete($uuid);
        }

        parent::tearDown();
    }

    protected function call_upload_file($repo, $upload, $path, $uuid) {
        $response = elis_files_upload_file($upload, $path, $uuid);
        if ($response && !empty($response->uuid)) {
            $this->created_uuids[] = $response->uuid;
            $node = elis_files_get_parent($response->uuid);
            $this->assertTrue($node && !empty($node->uuid));
            $this->assertEquals($uuid, $node->uuid);
        }
        return $response;
    }

    /**
     * Create a guest role to satisfy accesslib
     */
    private function create_guest_role() {
        global $DB;

        $roleid = create_role('guestrole', 'guestrole', 'guestrole');
        // Archetype needs to be set to 'guest'
        $DB->set_field('role', 'archetype', 'guest', array('id' => $roleid));
    }

    /**
     * Creat the system context, and the record as well as context for the site
     * course
     */
    private function create_contexts_and_site_course() {
        global $DB;

        // Contexts
        $DB->execute('INSERT INTO {context}
                      SELECT * FROM '.self::$origdb->get_prefix().'context
                      WHERE contextlevel = ?', array(CONTEXT_SYSTEM));
        $DB->execute('INSERT INTO {context}
                      SELECT * FROM '.self::$origdb->get_prefix().'context
                      WHERE contextlevel = ? AND instanceid = ?', array(CONTEXT_COURSE, SITEID));

        // Course record
        $DB->execute('INSERT INTO {course}
                      SELECT * FROM '.self::$origdb->get_prefix().'course
                      WHERE ID = ?', array(SITEID));
    }

    /**
     * Create a test user
     *
     * @return object The test user record
     */
    private function create_test_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        $data = new stdClass;
        $data->username = 'testuserusername';
        $data->mnethostid = $CFG->mnet_localhost_id;
        $userid = user_create_user($data);

        return $DB->get_record('user', array('id' => $userid));
    }

    /**
     * Create a test course
     *
     * @return int The course's id
     */
    private function create_test_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        // Create the category
        $category = new stdClass;
        $category->name = 'testcategoryname';
        $category->id = $DB->insert_record('course_categories', $category);

        // Create the course
        $data = new stdClass;
        $data->category = $category->id;
        $data->fullname = 'testcoursefullname';
        $data->shortname = 'testcourseshortname';
        $course = create_course($data);
        return $course->id;
    }

    /**
     * Create a test role with the provided capability
     *
     * @param string $capability The capability to assign to the role
     * @param string $name String to use as shortname, name and description
     * @return int The role's id
     */
    private function create_test_role($capability, $name = 'testrole') {
        // Create the role
        $roleid = create_role($name, $name, $name);

        // Assign the capability to the role
        $syscontext = context_system::instance();
        assign_capability($capability, CAP_ALLOW, $roleid, $syscontext->id);

        return $roleid;
    }

    /**
     * Data provider for capabilities that allow "shared" access
     *
     * @return array Data, as expected by the test method
     */
    public function shared_browsing_capability_provider() {
        return array(
            array('repository/elis_files:viewsharedcontent'),
            array('repository/elis_files:createsharedcontent')
        );
    }

    /**
     * Validate that "get_default_browsing_location" method respects the shared
     * capability at a non-site context
     *
     * @param string $capability The shared files capabilility to assign to the test user
     * @dataProvider shared_browsing_capability_provider
     */
    public function testGetDefaultBrowsingLocationIgnoresContext($capability) {
        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $courseid = $this->create_test_course();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_course::instance($courseid);
        role_assign($roleid, $USER->id, $context->id);

        // Set default browsing location to "shared"
        $config = get_config('elis_files');
        $config->default_browse = ELIS_FILES_BROWSE_SHARED_FILES;

        $elis_files = repository_factory::factory();
        $elis_files->process_config($config);

        // Obtain the appropriate location
        $cid = SITEID;
        $uid = 0;
        $shared = 0;
        $oid = 0;

        $location = $elis_files->get_default_browsing_location($cid, $uid, $shared, $oid);

        // Should be equal to shared files
        $this->assertEquals($elis_files->suuid, $location);
    }

    /**
     * Data provider for capabilities that allow "site" access
     *
     * @return array Data, as expected by the test method
     */
    public function site_capability_provider() {
        return array(
            array('repository/elis_files:viewsitecontent'),
            array('repository/elis_files:createsitecontent')
        );
    }

    /**
     * Data provider for capabilities for user permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function user_capability_provider() {
        return array(
            array('repository/elis_files:viewsitecontent'),
            array('repository/elis_files:createsitecontent'),
            array('repository/elis_files:createowncontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for user files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider user_capability_provider
     */
    public function testPermissionCheckRespectsCapabilitiesForUserFile($capability) {
        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new mock_repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));

        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        // Set the username to the fixed value get_info will return for title
        $USER->username = 'testuserusername';
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elis_files = new mock_ELIS_files();

        // Set the uuid to testuuid to get a valid permission check
        $uuid = 'testuuid';

        $has_permission = $elis_files->permission_check($uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Data provider for capabilities for course permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function course_capability_provider() {
        return array(
            array('repository/elis_files:viewsitecontent'),
            array('repository/elis_files:createsitecontent'),
            array('repository/elis_files:createcoursecontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for course files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider course_capability_provider
     */
    public function testPermissionCheckRespectsCapabilitiesForCourseFile($capability) {
        global $DB, $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new mock_repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                                                 array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));

        $this->create_contexts_and_site_course();
        $courseid = $this->create_test_course();

        $mapping = new stdClass;
        $mapping->courseid = $courseid;
        $mapping->uuid = 'testcuuid';
        $DB->insert_record('elis_files_course_store', $mapping);

        $this->create_guest_role();

        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elis_files = new mock_ELIS_files();
        $has_permission = $elis_files->permission_check($mapping->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Data provider for capabilities for userset permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function userset_capability_provider() {
        return array(
            array('repository/elis_files:viewsitecontent'),
            array('repository/elis_files:createsitecontent'),
            array('repository/elis_files:createusersetcontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for userset files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider userset_capability_provider
     */
    public function testPermissionCheckRespectsCapabilitiesForUsersetFile($capability) {
        global $DB, $USER;
        require_once(elispm::lib('data/userset.class.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new mock_repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                                                 array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));

        $this->create_contexts_and_site_course();

        $userset = new userset(array(
            'name' => 'testusersetname'
        ));
        $userset->save();

        $mapping = new stdClass;
        $mapping->usersetid = $userset->id;
        $mapping->uuid = 'testouuid';
        $DB->insert_record('elis_files_userset_store', $mapping);

        $this->create_guest_role();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elis_files = new mock_ELIS_files();

        $has_permission = $elis_files->permission_check($mapping->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Data provider for capabilities for shared permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function shared_capability_provider() {
        return array(
            array('repository/elis_files:viewsitecontent'),
            array('repository/elis_files:createsitecontent'),
            array('repository/elis_files:createsharedcontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the site files
     * capabilities assigned at the system level for shared files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider shared_capability_provider
     */
    public function testPermissionCheckRespectsCapabilitiesForSharedFile($capability) {
        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new mock_repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));

        $this->create_contexts_and_site_course();

        $this->create_guest_role();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elis_files = new mock_ELIS_files();

        $has_permission = $elis_files->permission_check($elis_files->suuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Validate that the "permission_check" method respects the site files
     * capabilities assigned at the system level for site files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function testPermissionCheckRespectsCapabilitiesForSiteFile($capability) {
        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new mock_repository_elis_files('elis_files', get_context_instance(CONTEXT_SYSTEM),
                                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));

        $this->create_contexts_and_site_course();

        $this->create_guest_role();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elis_files = new mock_ELIS_files();

        $has_permission = $elis_files->permission_check($elis_files->muuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Validate that the "file_browse_options" method respect site files
     * capabilities assigned at the system level when handling system-level files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function testFileBrowseOptionsRespectsSiteFilesFromSystemContext($capability) {
        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Obtain the browsing options
        $elis_files = new mock_ELIS_files();
        $cid = SITEID;
        $uid = 0;
        $shared = 0;
        $oid = 0;
        $options = $elis_files->file_browse_options($cid, $uid, $shared, $oid);

        // Validation: should have site, shared, and user files
        $this->assertEquals(3, count($options));

        $site_files_label = get_string('repositorysitefiles','repository_elis_files');
        $this->assertEquals($site_files_label, $options[0]['name']);

        $shared_files_label = get_string('repositoryserverfiles','repository_elis_files');
        $this->assertEquals($shared_files_label, $options[1]['name']);

        $user_files_label = get_string('repositoryuserfiles','repository_elis_files');
        $this->assertEquals($user_files_label, $options[2]['name']);
    }

    /**
     * Validate that the "file_browse_options" method respect site files
     * capabilities assigned at the system level when handling course-level files
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function testFileBrowseOptionsRespectsSiteFilesFromCourseContext($capability) {
        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Obtain the browsing options
        $elis_files = new mock_ELIS_files();

        $cid = $this->create_test_course();
        $uid = 0;
        $shared = 0;
        $oid = 0;
        $options = $elis_files->file_browse_options($cid, $uid, $shared, $oid);

        // Validation: should have site, shared, and user files
        $this->assertEquals(3, count($options));

        $site_files_label = get_string('repositorycoursefiles','repository_elis_files');
        $this->assertEquals($site_files_label, $options[0]['name']);

        $shared_files_label = get_string('repositoryserverfiles','repository_elis_files');
        $this->assertEquals($shared_files_label, $options[1]['name']);

        $user_files_label = get_string('repositoryuserfiles','repository_elis_files');
        $this->assertEquals($user_files_label, $options[2]['name']);
    }

    /**
     * Data provider for capabilities that allow "userset" access, as well as
     * associated data
     *
     * @return array Data, as expected by the test method
     */
    public function userset_folders_provider() {
        return array(
            array('repository/elis_files:viewsitecontent', false, array('testusersetname')),
            array('repository/elis_files:viewsitecontent', true, array()),
            array('repository/elis_files:createsitecontent', false, array('testusersetname')),
            array('repository/elis_files:createsitecontent', true, array('testusersetname'))
        );
    }

    /**
     * Validate that the "find_userset_folders" method respect site files
     * capabilities assigned at the system level
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @param boolean $createonly The "create only" flag, as needed by the method
     * @param array $names The expected set of userset names that should be returned
     * @dataProvider userset_folders_provider
     */
    public function testFindUsersetFoldersRespectsSiteFiles($capability, $createonly, $names) {
        global $DB, $USER;
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('plugins/userset_classification/usersetclassification.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $DB->execute('INSERT INTO {'.field::TABLE.'}
                      SELECT * FROM '.self::$origdb->get_prefix().field::TABLE);
        $DB->execute('INSERT INTO {'.field_category::TABLE.'}
                      SELECT * FROM '.self::$origdb->get_prefix().field_category::TABLE);
        $DB->execute('INSERT INTO {'.field_contextlevel::TABLE.'}
                      SELECT * FROM '.self::$origdb->get_prefix().field_contextlevel::TABLE);

        $this->create_contexts_and_site_course();

        $classification = new usersetclassification(array(
            'shortname' => 'testclassification'
        ));
        $classification->param_elis_files_shared_folder = 1;
        $classification->save();

        $userset = new userset(array(
            'name' => 'testusersetname'
        ));
        $userset->save();
        $userset->reset_custom_field_list();
        $userset->load();
        $userset->field__elis_userset_classification = 'testclassification';
        $userset->save();

        $USER = $this->create_test_user();

        // Assign the "site files" role to the test user
        $roleid = $this->create_test_role($capability);
        $systemcontext = context_system::instance();
        role_assign($roleid, $USER->id, $systemcontext->id);

        // Assign the "view userset content" role to the test user
        $usersetcontext = context_elis_userset::instance($userset->id);
        $roleid = $this->create_test_role('repository/elis_files:viewusersetcontent', 'usersetrole');
        role_assign($roleid, $USER->id, $usersetcontext->id);

        // Obtain the set of userset folders
        $elis_files = repository_factory::factory();

        $folders = array();
        $elis_files->find_userset_folders($folders, $createonly);

        // Validate that the method returned the right number of folders
        $this->assertEquals(count($names), count($folders));

        // Validate the specific names
        foreach ($names as $i => $name) {
            $this->assertEquals($name, $folders[$i]['name']);
        }
    }

    /**
     * Data provider for all data needed to specify the set of flags needed to
     * identify a node's area and associated capability
     *
     * @return array Data, as expected by the test method
     */
    public function get_repository_location_provider() {
        return array(
            //course
            array(true, false, false, false, 'repository/elis_files:viewsitecontent'),
            array(true, false, false, false, 'repository/elis_files:createsitecontent'),
            array(true, false, false, false, 'repository/elis_files:createcoursecontent'),
            //shared
            array(false, true, false, false, 'repository/elis_files:viewsitecontent'),
            array(false, true, false, false, 'repository/elis_files:createsitecontent'),
            array(false, true, false, false, 'repository/elis_files:createsharedcontent'),
            //userset
            array(false, false, true, false, 'repository/elis_files:viewsitecontent'),
            array(false, false, true, false, 'repository/elis_files:createsitecontent'),
            array(false, false, true, false, 'repository/elis_files:createusersetcontent'),
            //own
            array(false, false, false, true, 'repository/elis_files:viewsitecontent'),
            array(false, false, false, true, 'repository/elis_files:createsitecontent'),
            array(false, false, false, true, 'repository/elis_files:createowncontent')
        );
    }

    /**
     * Validate that the "get_repository_location" method respects appropriate permissions
     *
     * @param boolean $course True if checking permissions on course files
     * @param boolean $shared True if checking permissions on shared files
     * @param boolean $userset True if checking permissions on userset files
     * @param boolean $own True if checking permissions on own (personal) files
     * @param string $capability The capability to assign to the test user
     * @dataProvider get_repository_location_provider
     */
    public function testGetRepositoryLocationRespectsCapabilities($course, $shared, $userset, $own, $capability) {
        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Determine the correct set of parameters
        $cid = SITEID;
        // Note: shared left as-is
        $oid = false;
        $uid = 0;
        // Note: expected uuid is set in one of the cases below
        $uuid = false;

        $elis_files = new mock_ELIS_Files();

        if ($course) {
            $cid = $this->create_test_course();

            $mapping = new stdClass;
            $mapping->courseid = $cid;
            $uuid = $mapping->uuid = 'courseuuid';
            $DB->insert_record('elis_files_course_store', $mapping);
        }

        if ($shared) {
            $uuid = $elis_files->suuid;
        }

        if ($userset) {
            require_once(elispm::lib('data/userset.class.php'));

            $userset = new userset(array(
                'name' => 'testusersetname'
            ));
            $userset->save();

            $mapping = new stdClass;
            $oid = $mapping->usersetid = $userset->id;
            $uuid = $mapping->uuid = 'usersetuuid';
            $DB->insert_record('elis_files_userset_store', $mapping);
        }

        if ($own) {
            $uid = $USER->id;

            $uuid = $elis_files->uuuid = 'useruuid';
        }

        // Set up our state in the user object in some other location
        $USER->elis_files_repository_location = new stdClass;
        if (!$shared && !$own) {
            // All cases except "shared" and "own" work with a uuid set
            $USER->elis_files_repository_location->uuid = 'someuuid';
        }

        if ($course) {
            // Course case requires different course id
            $USER->elis_files_repository_location->cid = $cid + 1;
        } else {
            $USER->elis_files_repository_location->cid = $cid;
        }

        if ($own) {
            // Own case requires different userid
            $USER->elis_files_repository_location->uid = $uid + 1;
        } else {
            $USER->elis_files_repository_location->uid = $uid;
        }

        // Always set to false, because shared case also expects this
        $USER->elis_files_repository_location->shared = false;

        if ($userset) {
            // Userset case requires different userset id
            $USER->elis_files_repository_location->oid = $oid + 1;
        } else {
            $USER->elis_files_repository_location->oid = $oid;
        }

        // Run the method for getting the repository location
        $location = $elis_files->get_repository_location($cid, $uid, $shared, $oid);

        // Validation
        $this->assertEquals($uuid, $location);
    }

    /**
     * Validate that the "get_default_browsing_location" method respects site files
     * capabilities assigned at the system level
     *
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function testGetDefaultBrowsingLocationRespectsSiteFiles($capability) {
        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Create the test course and map out a uuid
        $courseid = $this->create_test_course();
        $course_mapping = new stdClass;
        $course_mapping->courseid = $courseid;
        $course_mapping->uuid = 'courseuuid';
        $DB->insert_record('elis_files_course_store', $course_mapping);

        $elis_files = new mock_ELIS_Files();

        // Run through the setting options and their expected locations
        $setting_options = array(
            ELIS_FILES_BROWSE_SITE_FILES   => $elis_files->root->uuid,
            ELIS_FILES_BROWSE_SHARED_FILES => $elis_files->suuid,
            ELIS_FILES_BROWSE_COURSE_FILES => $course_mapping->uuid,
            ELIS_FILES_BROWSE_USER_FILES   => 'testuuid'
        );

        foreach ($setting_options as $setting_option => $expected_uuid) {
            // Set default browsing location to the appropriate setting value
            $config = get_config('elis_files');
            $config->default_browse = $setting_option;

            $elis_files->process_config($config);

            // Obtain the appropriate location
            if ($setting_option == ELIS_FILES_BROWSE_COURSE_FILES) {
                $cid = $courseid;
            } else {
                $cid = SITEID;
            }
            $uid = 0;
            $shared = 0;
            $oid = 0;

            // Validate that the browsing location matches the expected value
            $location = $elis_files->get_default_browsing_location($cid, $uid, $shared, $oid);
            $this->assertEquals($expected_uuid, $location);
        }
    }

    /**
     * Data provider for all data needed to specify the set of flags needed to
     * identify a node's area
     *
     * @return array Data, as expected by the test method
     */
    public function node_flag_provider() {
        return array(
            array(true, false, false, false),
            array(false, true, false, false),
            array(false, false, true, false),
            array(false, false, false, true)
        );
    }

    /**
     * Validate that the "check_editing_permissions" method respects site files
     * capabilities assigned at the system level
     *
     * @param boolean $course True if checking permissions on course files
     * @param boolean $shared True if checking permissions on shared files
     * @param boolean $userset True if checking permissions on userset files
     * @param boolean $own True if checking permissions on own (personal) files
     * @dataProvider node_flag_provider
     */
    public function testCheckEditingPermissionsRespectsSiteFiles($course, $shared, $userset, $own) {
        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->create_guest_role();

        $this->create_contexts_and_site_course();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role('repository/elis_files:createsitecontent');

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Determine the correct set of parameters
        $id = SITEID;
        // Note: shared left as-is
        $oid = false;
        $userid = 0;
        // Note: UUID is not currently used in the method we are testing
        $uuid = false;

        $elis_files = repository_factory::factory();

        if ($course) {
            $id = $this->create_test_course();

            $mapping = new stdClass;
            $mapping->courseid = $id;
            $mapping->uuid = 'testuuid';
            $DB->insert_record('elis_files_course_store', $mapping);
        }

        if ($shared) {
            // Note: nothing to do because flag is already set
        }

        if ($userset) {
            require_once(elispm::lib('data/userset.class.php'));

            $userset = new userset(array(
                'name' => 'testusersetname'
            ));
            $userset->save();

            $mapping = new stdClass;
            $oid = $mapping->usersetid = $userset->id;
            $uuid = $mapping->uuid = 'testuuid';
            $DB->insert_record('elis_files_userset_store', $mapping);
        }

        if ($own) {
            $userid = $USER->id;
        }

        // Run the permission check method
        $sql = 'SELECT i.name, i.typeid, r.type
                FROM {repository} r,
                {repository_instances} i
                WHERE r.type=?
                  AND i.typeid=r.id';
        $repository = $DB->get_record_sql($sql, array('elis_files'));
        $repo = @new repository_elis_files('elis_files',
                                           context_user::instance($USER->id),
                                           array(
                                               'ajax' => false,
                                               'name' => $repository->name,
                                               'type' => 'elis_files'
                                           ));
        $has_permission = $repo->check_editing_permissions($id, $shared, $oid, $uuid, $userid);

        // Validation
        $this->assertTrue($has_permission);
    }

   /**
     * Data provider for folder depth testing for permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function hierarchy_provider() {
        return array(
            array('1'),
            array('2'),
            array('3'),
            array('4'),
        );
    }

    /**
     * Validate that the "permission_check" method works hierarchically and respects the
     * capabilities assigned at the system level for userset files
     *
     * @param string $depth The folder depth to build for hierachy testing
     * @dataProvider hierarchy_provider
     */
    public function testPermissionCheckHierarchicalCapabilitiesForUsersetFile($depth) {
        global $CFG, $DB, $SESSION, $USER;
        require_once(elispm::lib('data/userset.class.php'));

// Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');
        // Use a fixed capability
        $capability = 'repository/elis_files:createusersetcontent';

        // unset the repo to avoid the is_siteadmin problem in the factory class
        unset($SESSION->repo);

        // create a test user and give them the capability and role they need
        $this->create_contexts_and_site_course();

        $this->create_guest_role();
        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

//print_object("user id ".$USER->id." name? ".$USER->username." is admin? ".is_siteadmin());

        // Check for ELIS_files repository
        if (file_exists($CFG->dirroot .'/repository/elis_files/')) {
            // RL: ELIS files: Alfresco
            $data = null;
            $listing = null;
            $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE r.type=? AND i.typeid=r.id';
            $repository = $DB->get_record_sql($sql, array('elis_files'));
            if ($repository) {
                try {
                    $repo = @new repository_elis_files('elis_files',
                                get_context_instance(CONTEXT_SYSTEM),
                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));
                } catch (Exception $e) {
                    $this->markTestSkipped();
               }
            } else {
                $this->markTestSkipped();
            }
        } else {
            $this->markTestSkipped();
        }

        // Explicitly set the file transfer method to web services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elis_files');


        $userset = new userset(array(
            'name' => 'elisunittestuserset'
        ));
        $userset->save();

        $current_uuid = $repo->elis_files->get_userset_store($userset->id);
        $this->created_uuids[] = $current_uuid;

        // Now we need to create folders up to the depth from the data provider and upload a file
        for ($i = 1; $i <= $depth; ++$i) {
            //create a sub-folder
            $current_node = elis_files_create_dir(TEST_PREFIX.'userset_'.$i,$current_uuid);
            $this->assertTrue($current_node && !empty($current_node->uuid));
            $current_uuid = $current_node->uuid;
            $this->created_uuids[] = $current_uuid;
        }

        $filename = $CFG->dirroot.'/repository/elis_files/phpunit/'.TEST_PREFIX.'file.txt';
        //upload a file to this folder
        $response = $this->call_upload_file($repo, '', $filename, $current_uuid);
        // FTP was failing, but this is a good check to keep in
        $this->assertFalse(!$response);

        $has_permission = $repo->elis_files->permission_check($response->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }

    /**
     * Validate that the "permission_check" method method works hierarchically and respects the appropriate
     * capabilities assigned at the system level for course files
     *
     * @param string $depth The folder depth to build for hierachy testing
     * @dataProvider hierarchy_provider
     */
    public function testPermissionCheckRespectsHierarchyForCourseFile($depth) {
        global $CFG, $DB, $SESSION, $USER;
        require_once(elispm::lib('data/userset.class.php'));

// Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');
        // Use a fixed capability
        $capability = 'repository/elis_files:createcoursecontent';

        // unset the repo to avoid the is_siteadmin problem in the factory class
        unset($SESSION->repo);

        $this->create_contexts_and_site_course();
        $courseid = $this->create_test_course();

        // Check for ELIS_files repository
        if (file_exists($CFG->dirroot .'/repository/elis_files/')) {
            // RL: ELIS files: Alfresco
            $data = null;
            $listing = null;
            $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE r.type=? AND i.typeid=r.id';
            $repository = $DB->get_record_sql($sql, array('elis_files'));
            if ($repository) {
                try {
                    $repo = @new repository_elis_files('elis_files',
                                get_context_instance(CONTEXT_SYSTEM),
                                array('ajax'=>false, 'name'=>$repository->name, 'type'=>'elis_files'));
                } catch (Exception $e) {
                    $this->markTestSkipped();
               }
            } else {
                $this->markTestSkipped();
            }
        } else {
            $this->markTestSkipped();
        }

        // Explicitly set the file transfer method to web services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elis_files');

        // use test course id
        $current_uuid = $repo->elis_files->get_course_store($courseid);
        $this->created_uuids[] = $current_uuid;

        $this->create_guest_role();

        $USER = $this->create_test_user();
        $roleid = $this->create_test_role($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

// Now we need to create folders up to the depth from the data provider and upload a file
        for ($i = 1; $i <= $depth; ++$i) {
            //create a sub-folder
            $current_node = elis_files_create_dir(TEST_PREFIX.'course_'.$i,$current_uuid);
            $this->assertTrue($current_node && !empty($current_node->uuid));
            $current_uuid = $current_node->uuid;
            $this->created_uuids[] = $current_uuid;
        }

        $filename = $CFG->dirroot.'/repository/elis_files/phpunit/'.TEST_PREFIX.'file.txt';
        //upload a file to this folder
        $response = $this->call_upload_file($repo, '', $filename, $current_uuid);
        // FTP was failing, but this is a good check to keep in
        $this->assertFalse(!$response);

        // Perform the appropriate permission check
//        $elis_files = new mock_ELIS_files();
        $has_permission = $repo->elis_files->permission_check($response->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($has_permission);
    }
}
