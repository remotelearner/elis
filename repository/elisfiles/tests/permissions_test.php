<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

global $CFG;

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/ELIS_files.php');
if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
}
require_once($CFG->dirroot.'/repository/elisfiles/tests/constants.php');

/**
 * Mock class that allows us to not run back-end Alfresco calls when we can avoid them
 * @author brendan
 */
class repository_elisfiles_mock extends repository_elisfiles {
    /** @var string $type this property is required otherwise a bunch of phpunit tests will fail */
    // public $type = 'repository_elisfiles';

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
    public function get_parent_path($uuid, &$path, $cid, $uid, $shared, $oid, $type = 'parent') {
        $path = array($uuid);
    }

    public function get_name() {
        return get_string('pluginname', 'repository_elisfiles');
    }
}

/**
 * Mock class that allows up to not run back-end Alfresco calls when we can avoid
 * them
 */
class mock_ELIS_files extends ELIS_files {
    /** @var string $ccuid a unique id */
    public $cuuid = '';
    /** @var string $ouuid a unique id */
    public $ouuid = '';
    /** @var string $uuuid a unique id */
    public $uuuid = '';
    /** @var string $suuid a unique id */
    public $suuid = '';

    /**
     * Verify that the Alfresco repository is currently setup and ready to be
     * used with Moodle (i.e. the needed directory structure is in place).
     *
     * @uses $CFG
     * @return bool True if setup, False otherwise.
     */
    public function verify_setup() {
        $valid_setup = parent::verify_setup();

        $this->cuuid = "mockcuuid";
        $this->ouuid = "mockouuid";
        $this->uuuid = "testuuid";
        $this->suuid = "mocksuuid";

        return $valid_setup;
    }

    /**
     * Get the full path of a specific content node in the repository.
     * @param string $uuid Unique identifier for a node.
     * @return string|bool A string  False.
     */
    public function get_file_path($uuid) {
        return $uuid;
    }

    /**
     * Check if a give node is in the parent path
     * @param    string  $uuid           The Unique identifier for a node
     * @param    string  $compareduuid   Unique identifier for a node to compare against
     * @return   bool                    True if the node is in the parent path, otherwise, false
     */
    public function match_uuid_path($uuid, $compareduuid, $result) {
        return $uuid == $compareduuid;
    }

    /**
     * Get info about a specific node reference.
     * @param string $uuid A node UUID value.
     * @return object a node object
     */
    public function get_info($uuid) {
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
     * @param string $username The Alfresco user's username.
     * @param string $uuid     The Alfresco node UUID.
     * @return bool True on success, False otherwise.
     */
    public function allow_read($username, $uuid) {
        return true;
    }

    /**
     * Assign a user write access to a specific node.
     * @param string $username The Alfresco user's username.
     * @param string $uuid     The Alfresco node UUID.
     * @return bool True on success, False otherwise.
     */
    public function allow_edit($username, $uuid) {
        return true;
    }

    /**
     * Attempt to create the given directory name within a certain folder.
     * @param string $name        The name of the directory we're checking for.
     * @param string $uuid        The UUID of the parent directory we're checking for a name in.
     * @param string $description An optional description of the directory being created.
     * @param bool   $useadmin    Set to false to make sure that the administrative user configured in
     *                            the plug-in is not used for this operation (default: true).
     * @return bool True on success, False otherwise.
     */
    public function create_dir($name, $uuid = '', $description = '', $useadmin = true) {
        // Do nothing
    }

    /**
     * Get the UUID of the course storage area.
     * @param int  $cid    The course ID.
     * @param bool $create Set to false to not automatically create a course storage area.
     * @return string The course store UUID value
     */
    public function get_course_store($cid, $create = true) {
        return 'courseuuid';
    }

    /**
     * Get the UUID of the user personal storage area.
     * @param int  $uid       The Moodle user ID.
     * @param bool $nomigrate Set to True to not force user data migration.
     * @return string The user store UUID value
     */
    public function get_user_store($uid, $nomigrate = false) {
        return 'useruuid';
    }

    /**
     * Get an Alfresco user's home directory UUID.
     * @param string $username The Alfresco user's username.
     * @return string The UUID of the home directory
     */
    public function elis_files_userdir($username) {
        return 'useruuid';
    }

    /**
     * Get the parent of a specific node.
     * @param string $uuid Unique identifier for a node.
     * @return object An object representing the parent node
     */
    public function get_parent($uuid) {
        $parent = new stdClass;
        // set it to a 'valid' parent
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

/**
 * Class to test permissions
 * @group repository_elisfiles
 */
class repository_elisfiles_permissions_testcase extends elis_database_test {
    /** @var array $createduuids test uuids */
    protected $createduuids = array();

    /**
     * This function loads data into the PHPUnit tables for testing.
     */
    protected function setup_test_data_xml() {
        if (!file_exists(__DIR__.'/fixtures/elis_files_config.xml')) {
            $this->markTestSkipped('You must define elis_files_config.xml inside '.__DIR__.
                    '/fixtures/ directory to execute this test.');
        }
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_config.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_instance.xml'));
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/elis_files_permissions_test_data.xml'));

        // Check if Alfresco is enabled, configured and running first.
        if (!$repo = repository_factory::factory('elisfiles')) {
            $this->markTestSkipped('Could not connect to alfresco with supplied credentials. Please try again.');
        }
    }

    /**
     * This function initializes all of the setup steps required by each step.
     */
    protected function setUp() {
        global $DB;
        parent::setUp();
        $this->setAdminUser();

        // Create elisfiles repository records
        $elisfiles = new stdClass;
        $elisfiles->type = 'elisfiles';
        $elisfiles->id = $DB->insert_record('repository', $elisfiles);
        $efinst = new stdClass;
        $efinst->typeid = $elisfiles->id;
        $efinst->name = 'ELIS Files';
        $efinst->contextid = SYSCONTEXTID;
        $DB->insert_record('repository_instances', $efinst);
    }

    /**
     * This function removes any initialized data.
     */
    protected function tearDown() {
        foreach ($this->createduuids as $uuid) {
            elis_files_delete($uuid);
        }

        parent::tearDown();
    }

    /**
     * This funciton uploads a file asserts some tests.
     * @param ELIS_files $repo an instance of ELIS_files
     * @param string $upload   The array index of the uploaded file.
     * @param string $path     The full path to the file on the local filesystem.
     * @param string $uuid     The UUID of the folder where the file is being uploaded to.
     * @return object Node values for the uploaded file.
     */
    protected function call_upload_file($repo, $upload, $path, $uuid) {
        $response = elis_files_upload_file($upload, $path, $uuid);
        if ($response && !empty($response->uuid)) {
            $this->createduuids[] = $response->uuid;
            $node = elis_files_get_parent($response->uuid);
            $this->assertTrue($node && !empty($node->uuid));
            $this->assertEquals($uuid, $node->uuid);
        }
        return $response;
    }

    /**
     * Create a test role with the provided capability
     *
     * @param string $capability The capability to assign to the role
     * @param string $roleid the role id
     * @return int The role's id
     */
    private function assign_role_capability($capability, $roleid = 99) {
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
                array('repository/elisfiles:viewsharedcontent'),
                array('repository/elisfiles:createsharedcontent')
        );
    }

    /**
     * Validate that "get_default_browsing_location" method respects the shared
     * capability at a non-site context
     * @uses $USER
     * @param string $capability The shared files capabilility to assign to the test user
     * @dataProvider shared_browsing_capability_provider
     */
    public function test_get_default_browsing_location_ignores_context($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $courseid = 99;
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_course::instance($courseid);
        role_assign($roleid, $USER->id, $context->id);

        // Set default browsing location to "shared"
        $config = get_config('elisfiles');
        $config->default_browse = ELIS_FILES_BROWSE_SHARED_FILES;

        $elisfiles = repository_factory::factory();
        $elisfiles->process_config($config);

        // Obtain the appropriate location
        $cid = SITEID;
        $uid = 0;
        $shared = 0;
        $oid = 0;

        $location = $elisfiles->get_default_browsing_location($cid, $uid, $shared, $oid);

        // Should be equal to shared files
        $this->assertEquals($elisfiles->suuid, $location);
    }

    /**
     * Data provider for capabilities that allow "site" access
     *
     * @return array Data, as expected by the test method
     */
    public function site_capability_provider() {
        return array(
            array('repository/elisfiles:viewsitecontent'),
            array('repository/elisfiles:createsitecontent')
        );
    }

    /**
     * Data provider for capabilities for user permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function user_capability_provider() {
        return array(
            array('repository/elisfiles:viewsitecontent'),
            array('repository/elisfiles:createsitecontent'),
            array('repository/elisfiles:createowncontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for user files
     * @uses $USER, $DB
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider user_capability_provider
     */
    public function test_permission_check_respects_capabilities_for_user_file($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new repository_elisfiles_mock('repository_elisfiles', context_system::instance(),
                array('ajax' => false, 'name' => 'mock repository test name', 'type' => 'elisfiles'));

        $this->setUser(100);
        // Set the username to the fixed value get_info will return for title
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elisfiles = new mock_ELIS_files();

        // Set the uuid to testuuid to get a valid permission check
        $uuid = 'testuuid';

        $haspermission = $elisfiles->permission_check($uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Data provider for capabilities for course permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function course_capability_provider() {
        return array(
                array('repository/elisfiles:viewsitecontent'),
                array('repository/elisfiles:createsitecontent'),
                array('repository/elisfiles:createcoursecontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for course files
     * @uses $USER, $DB
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider course_capability_provider
     */
    public function test_permission_check_respects_capabilities_for_course_file($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB, $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new repository_elisfiles_mock('repository_elisfiles', context_system::instance(),
                array('ajax' => false, 'name' => 'mock repository test name', 'type' => 'elisfiles'));

        $courseid = 99;

        $mapping = new stdClass;
        $mapping->courseid = $courseid;
        $mapping->uuid = 'testcuuid';
        $DB->insert_record('repository_elisfiles_course', $mapping);

        $this->setUser(100);

        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elisfiles = new mock_ELIS_files();
        $haspermission = $elisfiles->permission_check($mapping->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Data provider for capabilities for userset permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function userset_capability_provider() {
        return array(
                array('repository/elisfiles:viewsitecontent'),
                array('repository/elisfiles:createsitecontent'),
                array('repository/elisfiles:createusersetcontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the appropriate
     * capabilities assigned at the system level for userset files
     * @uses $USER, $DB
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider userset_capability_provider
     */
    public function test_permission_check_respects_capabilities_for_userset_file($capability) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('elis_program needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB, $USER;

        require_once(elispm::lib('data/userset.class.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new repository_elisfiles_mock('repository_elisfiles', context_system::instance(),
                array('ajax' => false, 'name' => 'mock repository test name', 'type' => 'elisfiles'));

        $userset = new userset(array(
            'name' => 'testusersetname'
        ));
        $userset->save();

        $mapping = new stdClass;
        $mapping->usersetid = $userset->id;
        $mapping->uuid = 'testouuid';
        $DB->insert_record('repository_elisfiles_userset', $mapping);

        $this->setUser(100);

        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elisfiles = new mock_ELIS_files();

        $haspermission = $elisfiles->permission_check($mapping->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Data provider for capabilities for shared permissions checking
     *
     * @return array Data, as expected by the test method
     */
    public function shared_capability_provider() {
        return array(
                array('repository/elisfiles:viewsitecontent'),
                array('repository/elisfiles:createsitecontent'),
                array('repository/elisfiles:createsharedcontent'),
        );
    }

    /**
     * Validate that the "permission_check" method respects the site files
     * capabilities assigned at the system level for shared files
     * @uses $USER
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider shared_capability_provider
     */
    public function test_permission_check_respects_capabilities_for_shared_file($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new repository_elisfiles_mock('repository_elisfiles', context_system::instance(),
                array('ajax' => false, 'name' => 'mock repository test name', 'type' => 'elisfiles'));

        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elisfiles = new mock_ELIS_files();

        $haspermission = $elisfiles->permission_check($elisfiles->suuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Validate that the "permission_check" method respects the site files
     * capabilities assigned at the system level for site files
     * @uses $USER
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function test_permission_check_respects_capabilities_for_site_file($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $repo = @new repository_elisfiles_mock('repository_elisfiles', context_system::instance(),
                array('ajax' => false, 'name' => 'mock repository test name', 'type' => 'elisfiles'));

        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Perform the appropriate permission check
        $elisfiles = new mock_ELIS_files();

        $haspermission = $elisfiles->permission_check($elisfiles->muuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Validate that the "file_browse_options" method respect site files
     * capabilities assigned at the system level when handling system-level files
     * @uses $USER
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function test_file_browse_options_respects_site_files_from_system_context($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Obtain the browsing options
        $elisfiles = new mock_ELIS_files();
        $cid = SITEID;
        $uid = 0;
        $shared = 0;
        $oid = 0;
        $options = $elisfiles->file_browse_options($cid, $uid, $shared, $oid);

        // Validation: should have site, shared, and user files
        $this->assertEquals(3, count($options));

        $sitefileslabel = get_string('repositorysitefiles', 'repository_elisfiles');
        $this->assertEquals($sitefileslabel, $options[0]['name']);

        $sharedfileslabel = get_string('repositoryserverfiles', 'repository_elisfiles');
        $this->assertEquals($sharedfileslabel, $options[1]['name']);

        $userfileslabel = get_string('repositoryuserfiles', 'repository_elisfiles');
        $this->assertEquals($userfileslabel, $options[2]['name']);
    }

    /**
     * Validate that the "file_browse_options" method respect site files
     * capabilities assigned at the system level when handling course-level files
     * @uses $USER
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function test_file_browse_options_respects_site_files_from_course_context($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Obtain the browsing options
        $elisfiles = new mock_ELIS_files();

        $cid = 99;
        $uid = 0;
        $shared = 0;
        $oid = 0;
        $options = $elisfiles->file_browse_options($cid, $uid, $shared, $oid);

        // Validation: should have site, shared, and user files
        $this->assertEquals(3, count($options));

        $sitefileslabel = get_string('repositorycoursefiles', 'repository_elisfiles');
        $this->assertEquals($sitefileslabel, $options[0]['name']);

        $sharedfileslabel = get_string('repositoryserverfiles', 'repository_elisfiles');
        $this->assertEquals($sharedfileslabel, $options[1]['name']);

        $userfileslabel = get_string('repositoryuserfiles', 'repository_elisfiles');
        $this->assertEquals($userfileslabel, $options[2]['name']);
    }

    /**
     * Data provider for capabilities that allow "userset" access, as well as
     * associated data
     *
     * @return array Data, as expected by the test method
     */
    public function userset_folders_provider() {
        return array(
                array('repository/elisfiles:viewsitecontent', false, array('testusersetname')),
                array('repository/elisfiles:viewsitecontent', true, array()),
                array('repository/elisfiles:createsitecontent', false, array('testusersetname')),
                array('repository/elisfiles:createsitecontent', true, array('testusersetname'))
        );
    }

    /**
     * Validate that the "find_userset_folders" method respect site files
     * capabilities assigned at the system level
     * @uses $USER, $DB
     * @param string $capability The site files capabilility to assign to the test user
     * @param boolean $createonly The "create only" flag, as needed by the method
     * @param array $names The expected set of userset names that should be returned
     * @dataProvider userset_folders_provider
     */
    public function test_find_userset_folders_respects_site_files($capability, $createonly, $names) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('local_elisprogram needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $DB, $USER;
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::file('plugins/usetclassify/usersetclassification.class.php'));
        require_once(elispm::file('accesslib.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

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

        $this->setUser(100);

        // Assign the "site files" role to the test user
        $roleid = $this->assign_role_capability($capability);
        $systemcontext = context_system::instance();
        role_assign($roleid, $USER->id, $systemcontext->id);

        // Assign the "view userset content" role to the test user
        $usersetcontext = \local_elisprogram\context\userset::instance($userset->id);
        $roleid = $this->assign_role_capability('repository/elisfiles:viewusersetcontent', 100);
        role_assign($roleid, $USER->id, $usersetcontext->id);

        // Obtain the set of userset folders
        $elisfiles = repository_factory::factory();

        $folders = array();
        $elisfiles->find_userset_folders($folders, $createonly);

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
                // course
                array(true, false, false, false, 'repository/elisfiles:viewsitecontent'),
                array(true, false, false, false, 'repository/elisfiles:createsitecontent'),
                array(true, false, false, false, 'repository/elisfiles:createcoursecontent'),
                // shared
                array(false, true, false, false, 'repository/elisfiles:viewsitecontent'),
                array(false, true, false, false, 'repository/elisfiles:createsitecontent'),
                array(false, true, false, false, 'repository/elisfiles:createsharedcontent'),
                // userset
                array(false, false, true, false, 'repository/elisfiles:viewsitecontent'),
                array(false, false, true, false, 'repository/elisfiles:createsitecontent'),
                array(false, false, true, false, 'repository/elisfiles:createusersetcontent'),
                // own
                array(false, false, false, true, 'repository/elisfiles:viewsitecontent'),
                array(false, false, false, true, 'repository/elisfiles:createsitecontent'),
                array(false, false, false, true, 'repository/elisfiles:createowncontent')
        );
    }

    /**
     * Validate that the "get_repository_location" method respects appropriate permissions
     * @uses $USER, $DB
     * @param boolean $course True if checking permissions on course files
     * @param boolean $shared True if checking permissions on shared files
     * @param boolean $userset True if checking permissions on userset files
     * @param boolean $own True if checking permissions on own (personal) files
     * @param string $capability The capability to assign to the test user
     * @dataProvider get_repository_location_provider
     */
    public function test_get_repository_location_respects_capabilities($course, $shared, $userset, $own, $capability) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('local_elisprogram needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

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

        $elisfiles = new mock_ELIS_Files();

        if ($course) {
            $cid = 99;

            $mapping = new stdClass;
            $mapping->courseid = $cid;
            $uuid = $mapping->uuid = 'courseuuid';
            $DB->insert_record('repository_elisfiles_course', $mapping);
        }

        if ($shared) {
            $uuid = $elisfiles->suuid;
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
            $DB->insert_record('repository_elisfiles_userset', $mapping);
        }

        if ($own) {
            $uid = $USER->id;

            $uuid = $elisfiles->uuuid = 'useruuid';
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
        $location = $elisfiles->get_repository_location($cid, $uid, $shared, $oid);

        // Validation
        $this->assertEquals($uuid, $location);
    }

    /**
     * Validate that the "get_default_browsing_location" method respects site files
     * capabilities assigned at the system level
     * @uses $USER, $DB
     * @param string $capability The site files capabilility to assign to the test user
     * @dataProvider site_capability_provider
     */
    public function test_get_default_browsing_location_respects_site_files($capability) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Create the test course and map out a uuid
        $courseid = 99;
        $coursemapping = new stdClass;
        $coursemapping->courseid = $courseid;
        $coursemapping->uuid = 'courseuuid';
        $DB->insert_record('repository_elisfiles_course', $coursemapping);

        $elisfiles = new mock_ELIS_Files();

        // Run through the setting options and their expected locations
        $settingoptions = array(
            ELIS_FILES_BROWSE_SITE_FILES   => $elisfiles->root->uuid,
            ELIS_FILES_BROWSE_SHARED_FILES => $elisfiles->suuid,
            ELIS_FILES_BROWSE_COURSE_FILES => $coursemapping->uuid,
            ELIS_FILES_BROWSE_USER_FILES   => 'testuuid'
        );

        foreach ($settingoptions as $settingoption => $expecteduuid) {
            // Set default browsing location to the appropriate setting value
            $config = get_config('elisfiles');
            $config->default_browse = $settingoption;

            $elisfiles->process_config($config);

            // Obtain the appropriate location
            if ($settingoption == ELIS_FILES_BROWSE_COURSE_FILES) {
                $cid = $courseid;
            } else {
                $cid = SITEID;
            }
            $uid = 0;
            $shared = 0;
            $oid = 0;

            // Validate that the browsing location matches the expected value
            $location = $elisfiles->get_default_browsing_location($cid, $uid, $shared, $oid);
            $this->assertEquals($expecteduuid, $location);
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
     * @uses $USER, $DB
     * @param boolean $course True if checking permissions on course files
     * @param boolean $shared True if checking permissions on shared files
     * @param boolean $userset True if checking permissions on userset files
     * @param boolean $own True if checking permissions on own (personal) files
     * @dataProvider node_flag_provider
     */
    public function test_check_editing_permissions_respects_site_files($course, $shared, $userset, $own) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('elis_program needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER, $DB;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $roleid = $this->assign_role_capability('repository/elisfiles:createsitecontent');

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

        // RL: ELIS files: Alfresco
        $data = null;
        $listing = null;
        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        try {
            $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);
        } catch (Exception $e) {
            $this->markTestSkipped('Exception when creating repository_elisfiles object: '.$e->getMessage());
        }

        if ($course) {
            $id = 99;

            $mapping = new stdClass;
            $mapping->courseid = $id;
            $mapping->uuid = 'testuuid';
            $DB->insert_record('repository_elisfiles_course', $mapping);
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
            $DB->insert_record('repository_elisfiles_userset', $mapping);
        }

        if ($own) {
            $userid = $USER->id;
        }

        // Run the permission check method
        $haspermission = $repo->check_editing_permissions($id, $shared, $oid, $uuid, $userid);

        // Validation
        $this->assertTrue($haspermission);
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
     * @uses $USER, $DB, $SESSION, $USER
     * @param string $depth The folder depth to build for hierachy testing
     * @dataProvider hierarchy_provider
     */
    public function test_permission_check_hierarchical_capabilities_for_userset_file($depth) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('elis_program needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $CFG, $DB, $SESSION, $USER;

        require_once(elispm::lib('data/userset.class.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');
        // Use a fixed capability
        $capability = 'repository/elisfiles:createusersetcontent';

        // unset the repo to avoid the is_siteadmin problem in the factory class
        unset($SESSION->repo);

        // create a test user and give them the capability and role they need
        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // RL: ELIS files: Alfresco
        $data = null;
        $listing = null;
        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        try {
            $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);
        } catch (Exception $e) {
            $this->markTestSkipped('Exception when creating repository_elisfiles object: '.$e->getMessage());
        }

        // Explicitly set the file transfer method to web services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elisfiles');

        $userset = new userset(array(
            'name' => 'elisunittestuserset'
        ));
        $userset->save();

        $currentuuid = $repo->elis_files->get_userset_store($userset->id);
        $this->createduuids[] = $currentuuid;

        // Now we need to create folders up to the depth from the data provider and upload a file
        for ($i = 1; $i <= $depth; ++$i) {
            // create a sub-folder
            $currentnode = elis_files_create_dir(TEST_PREFIX.'userset_'.$i, $currentuuid);
            $this->assertTrue($currentnode && !empty($currentnode->uuid));
            $currentuuid = $currentnode->uuid;
            $this->createduuids[] = $currentuuid;
        }

        $filename = $CFG->dirroot.'/repository/elisfiles/tests/'.TEST_PREFIX.'file.txt';
        // upload a file to this folder
        $response = $this->call_upload_file($repo, '', $filename, $currentuuid);
        // FTP was failing, but this is a good check to keep in
        $this->assertFalse(!$response);

        $haspermission = $repo->elis_files->permission_check($response->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    /**
     * Validate that the "permission_check" method method works hierarchically and respects the appropriate
     * capabilities assigned at the system level for course files
     * @uses $USER, $DB, $SESSION, $CFG
     * @param string $depth The folder depth to build for hierachy testing
     * @dataProvider hierarchy_provider
     */
    public function test_permission_check_respects_hierarchy_for_course_file($depth) {
        if (!class_exists('elispm')) {
            $this->markTestSkipped('elis_program needed for test');
            return false;
        }

        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $CFG, $DB, $SESSION, $USER;
        require_once(elispm::lib('data/userset.class.php'));

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');
        // Use a fixed capability
        $capability = 'repository/elisfiles:createcoursecontent';

        // unset the repo to avoid the is_siteadmin problem in the factory class
        unset($SESSION->repo);

        $courseid = 99;

        // RL: ELIS files: Alfresco
        $data = null;
        $listing = null;
        $options = array(
            'ajax' => false,
            'name' => 'elis files phpunit test',
            'type' => 'elisfiles'
        );

        try {
            $repo = new repository_elisfiles('elisfiles', context_system::instance(), $options);
        } catch (Exception $e) {
            $this->markTestSkipped('Exception when creating repository_elisfiles object: '.$e->getMessage());
        }

        // Explicitly set the file transfer method to web services
        set_config('file_transfer_method', ELIS_FILES_XFER_WS, 'elisfiles');

        // use test course id
        $currentuuid = $repo->elis_files->get_course_store($courseid);
        $this->createduuids[] = $currentuuid;

        $this->setUser(100);
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Now we need to create folders up to the depth from the data provider and upload a file
        for ($i = 1; $i <= $depth; ++$i) {
            // create a sub-folder
            $currentnode = elis_files_create_dir(TEST_PREFIX.'course_'.$i, $currentuuid);
            $this->assertTrue($currentnode && !empty($currentnode->uuid));
            $currentuuid = $currentnode->uuid;
            $this->createduuids[] = $currentuuid;
        }

        $filename = $CFG->dirroot.'/repository/elisfiles/tests/'.TEST_PREFIX.'file.txt';
        // upload a file to this folder
        $response = $this->call_upload_file($repo, '', $filename, $currentuuid);
        // FTP was failing, but this is a good check to keep in
        $this->assertFalse(!$response);

        // Perform the appropriate permission check
        $haspermission = $repo->elis_files->permission_check($response->uuid, 0, true, $repo);

        // Validation
        $this->assertTrue($haspermission);
    }

    public function php_notice_error_handler($errno, $errstr) {
        $this->fail($errstr);
    }

    /**
     * Validate that the "file_browse_options" method has no undefined variable errors
     * For ELIS-7453
     * @uses $USER
     */
    public function test_file_browse_options_undefined_variable_error() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        global $USER;

        // Make sure the test user is not mistaken for a site admin or guest
        set_config('siteadmins', '');
        set_config('siteguest', '');

        // Setup
        $this->setUser(100);
        $capability = 'repository/elisfiles:viewsitecontent';
        $roleid = $this->assign_role_capability($capability);

        // Assign the test role to the test user
        $context = context_system::instance();
        role_assign($roleid, $USER->id, $context->id);

        // Obtain the browsing options
        $elisfiles = new mock_ELIS_files();
        $cid = SITEID;
        $uid = 0;
        $shared = 0;
        $oid = 0;
        $elisfiles->root->uuid = false; // Force undefined variable $uuid error
        set_error_handler(array($this, 'php_notice_error_handler'), E_ALL);
        $options = $elisfiles->file_browse_options($cid, $uid, $shared, $oid);

        // Validation: should have site, shared, and user files
        $this->assertEquals(3, count($options));
    }
}
