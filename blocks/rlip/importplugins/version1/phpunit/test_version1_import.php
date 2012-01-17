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
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once(elis::lib('testlib.php'));

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_mockuser extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

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

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Class for version 1 import correctness
 */
class version1ImportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        return array('user' => 'moodle',
                     'context' => 'moodle',
                     'config' => 'moodle',
                     'user_info_field' => 'moodle',
                     'user_info_category' => 'moodle',
                     'user_info_data' => 'moodle');
    }

    /**
     * Helper function to get the core fields for a sample user
     *
     * @return array The user data
     */
    private function get_core_user_data() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!1234',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        return $data;
    }

    /**
     * Helper function that runs the user import for a sample user
     * 
     * @param array $extradata Extra fields to set for the new user
     */
    private function run_core_user_import($extradata) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = $this->get_core_user_data();
        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }
        
        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @param string $param1 Extra parameter, used for select options
     */
    private function create_profile_field($name, $datatype, $categoryid, $param1 = NULL) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== NULL) {
            $data->param1 = $param1;
        }

        $field->define_save($data);
    }

    /**
     * Validate that the version 1 plugin supports user actions
     */
    public function testVersion1ImportSupportsUserActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'user');

        $this->assertNotEquals($supports, false);
    }

    /**
     * Validate that the version 1 plugin supports user creation
     */
    public function testVersion1ImportSupportsUserCreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_create');
        $required_fields = array('username',
                                 'password',
                                 'firstname',
                                 'lastname',
                                 'email',
                                 'city',
                                 'country');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports user addition
     */
    public function testVersion1ImportSupportsUserAdd() {
        //note: this is the same as user creation, but makes up for a weirdness
        //in IP for 1.9
        $supports = plugin_supports('rlipimport', 'version1', 'user_add');
        $required_fields = array('username',
                                 'password',
                                 'firstname',
                                 'lastname',
                                 'email',
                                 'city',
                                 'country');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsRequiredUserFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = $this->get_core_user_data();
        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['password'] = hash_internal_user_password($data['password']);

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsRequiredUserFieldsOnAdd() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = $this->get_core_user_data();
        $data['action'] = 'add';
        $provider = new rlip_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['password'] = hash_internal_user_password($data['password']);

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that non-required fields are set to specified values during user creation
     */
    public function testVersion1ImportSetsNonRequiredUserFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        set_config('allowuserthemes', 1);

        $data = array('country' => 'CA',
                      'auth' => 'mnet',
                      'maildigest' => '2',
                      'autosubscribe' => '1',
                      'trackforums' => '1',
                      'screenreader' => '1',
                      'timezone' => -5.0,
                      'theme' => 'rlmaster',
                      'lang' => 'en',
                      'description' => 'rlipdescription',
                      'idnumber' => 'rlipidnumber',
                      'institution' => 'rlipinstitution',
                      'department' => 'rlipdepartment');

        $this->run_core_user_import($data);

        $select = "username = :username AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   screenreader = :screenreader AND
                   timezone = :timezone AND
                   theme = :theme AND 
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array('username' => 'rlipusername',
                        'auth' => 'mnet',
                        'maildigest' => 2,
                        'autosubscribe' => 1,
                        'trackforums' => 1,
                        'screenreader' => 1,
                        'timezone' => -5.0,
                        'theme' => 'rlmaster', 
                        'lang' => 'en',
                        'description' => 'rlipdescription',
                        'idnumber' => 'rlipidnumber',
                        'institution' => 'rlipinstitution',
                        'department' => 'rlipdepartment');

        $exists = $DB->record_exists_select('user', $select, $params);

        $this->assertEquals($exists, true);
    }

    /**
     * Asserts, using PHPunit, that the test user does not exist
     */
    private function assert_core_user_does_not_exist() {
        global $DB;

        $exists = $DB->record_exists('user', array('username' => 'rlipusername'));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that invalid auth plugins can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserAuthOnCreate() {
        $this->run_core_user_import(array('auth' => 'invalidauth'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that supplied passwords must match the site's password policy
     */
    public function testVersion1ImportPreventsInvalidUserPasswordOnCreate() {
        $this->run_core_user_import(array('password' => 'asdf'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid email addresses can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserEmailOnCreate() {
        $this->run_core_user_import(array('email' => 'invalidemail'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid maildigest values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserMaildigestOnCreate() {
        $this->run_core_user_import(array('maildigest' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid autosubscribe values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserAutosubscribeOnCreate() {
        $this->run_core_user_import(array('autosubscribe' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid trackforums values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserTrackforumsOnCreate() {
        set_config('forum_trackreadposts', 0);

        $this->run_core_user_import(array('trackforums' => 1));
        $this->assert_core_user_does_not_exist();

        set_config('forum_trackreadposts', 1);

        $this->run_core_user_import(array('trackforums' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid screenreader values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserScreenreaderOnCreate() {
        $this->run_core_user_import(array('screenreader' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid country values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserCountryOnCreate() {
        $this->run_core_user_import(array('country' => 12));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid timezone values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserTimezoneOnCreate() {
        $this->run_core_user_import(array('timezone' => 14.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that timezone values can't be set when they are forced globally
     */
    public function testVersion1ImportPreventsOverridingForcedTimezoneOnCreate() {
        set_config('forcetimezone', 10);

        $this->run_core_user_import(array('timezone' => 5.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid theme values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserThemeOnCreate() {
        set_config('allowuserthemes', 0);

        $this->run_core_user_import(array('theme' => 'rlmaster'));
        $this->assert_core_user_does_not_exist();

        set_config('allowuserthemes', 1);

        $this->run_core_user_import(array('theme' => 'bogus'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid lang values can't be set on user creation
     */
    public function testVersion1ImportPreventsInvalidUserLangOnCreate() {
        $this->run_core_user_import(array('lang' => '12'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that default values are correctly set on user creation
     */
    public function testVersion1ImportSetsDefaultsOnUserCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        set_config('forcetimezone', 99);

        //todo: disable auto-assigning of idnumbers when testing with elis

        $this->run_core_user_import(array());

        $select = "username = :username AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   screenreader = :screenreader AND
                   timezone = :timezone AND
                   theme = :theme AND 
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array('username' => 'rlipusername',
                        'auth' => 'manual',
                        'maildigest' => 0,
                        'autosubscribe' => 1,
                        'trackforums' => 0,
                        'screenreader' => 0,
                        'timezone' => 99,
                        'theme' => '', 
                        'lang' => $CFG->lang,
                        'description' => '',
                        'idnumber' => '',
                        'institution' => '',
                        'department' => '');

        $exists = $DB->record_exists_select('user', $select, $params);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not set unsupported fields
     */
    public function testVersion1ImportPreventsSettingUnsupportedUserFieldsOnCreate() {
        global $CFG, $DB;

        $data = array();
        $data['forcepasswordchange'] = 1;
        $data['maildisplay'] = 1;
        $data['mailformat'] = 0;
        $data['htmleditor'] = 0;
        $data['ajax'] = 0;
        $data['descriptionformat'] = FORMAT_WIKI;
        $this->run_core_user_import($data);

        $select = "username = :username AND
                   maildisplay = :maildisplay AND
                   mailformat = :mailformat AND
                   htmleditor = :htmleditor AND
                   ajax = :ajax AND
                   descriptionformat = :descriptionformat";
        $params = array('username' => 'rlipusername',
                        'maildisplay' => 2,
                        'mailformat' => 1,
                        'htmleditor' => 1,
                        'ajax' => 1,
                        'descriptionformat' => FORMAT_HTML);

        //make sure that a record exists with the default data rather than with the
        //specified values
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);

        //check force password change separately
        $user = $DB->get_record('user', array('username' => 'rlipusername'));
        $preferences = get_user_preferences('forcepasswordchange', null, $user);

        $this->assertEquals(count($preferences), 0);
    }

    /**
     * Validation that field-length checking works correctly
     */
    public function testVersion1ImportPreventsLongUserFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $this->run_core_user_import(array('firstname' => str_repeat('a', 101)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('lastname' => str_repeat('a', 101)));
        $this->assert_core_user_does_not_exist();

        $value = str_repeat('a', 50).'@'.str_repeat('b', 50);
        $this->run_core_user_import(array('email' => $value));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('city' => str_repeat('a', 256)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('idnumber' => str_repeat('a', 256)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('institution' => str_repeat('a', 41)));
        $this->assert_core_user_does_not_exist();

        $this->run_core_user_import(array('department' => str_repeat('a', 31)));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that setting profile fields works
     */
    public function testVersion1ImportSetsUserProfileFieldsOnCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->create_profile_field('rlipmenu', 'menu', $category->id, 'rlipoption1');
        $this->create_profile_field('rliptextarea', 'textarea', $category->id);
        $this->create_profile_field('rliptext', 'text', $category->id);

        $data = array();
        $data['profile_field_rlipcheckbox'] = '1';
        $data['profile_field_rlipdatetime'] = 'jan/12/2011';
        $data['profile_field_rlipmenu'] = 'rlipoption1';
        $data['profile_field_rliptextarea'] = 'rliptextarea';
        $data['profile_field_rliptext'] = 'rliptext';

        $this->run_core_user_import($data);

        $user = $DB->get_record('user', array('username' => 'rlipusername'));

        profile_load_data($user);

        $this->assertEquals(isset($user->profile_field_rlipcheckbox), true);
        $this->assertEquals($user->profile_field_rlipcheckbox, 1);
        $this->assertEquals(isset($user->profile_field_rlipdatetime), true);
        $this->assertEquals($user->profile_field_rlipdatetime, mktime(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rlipmenu), true);
        $this->assertEquals($user->profile_field_rlipmenu, 'rlipoption1');
        $this->assertEquals(isset($user->profile_field_rliptextarea['text']), true);
        $this->assertEquals($user->profile_field_rliptextarea['text'], 'rliptextarea');
        $this->assertEquals(isset($user->profile_field_rliptext), true);
        $this->assertEquals($user->profile_field_rliptext, 'rliptext');
    }

    /**
     * Validate that the import does not create bogus profile field data
     */
    public function testVersion1ImportValidatesProfileFieldsOnCreate() {
        global $DB;

        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->run_core_user_import(array('profile_field_rlipcheckbox' => '2'));
        $this->assert_core_user_does_not_exist();

        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->run_core_user_import(array('profile_field_rlipdatetime' => '1000000000'));
        $this->assert_core_user_does_not_exist();

        $this->create_profile_field('rlipmenu', 'menu', $category->id, 'rlipoption1');
        $this->run_core_user_import(array('profile_field_rlipmenu' => 'rlipoption2'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that the import does not create duplicate user records
     */
    public function testVersion1ImportPreventsDuplicateUserCreation() {
        global $DB;

        $initial_count = $DB->count_records('user');

        $this->run_core_user_import(array('idnumber' => 'testdupidnumber'));
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        $data = array('email' => 'testdup2@testdup2.com',
                      'idnumber' => 'testdupidnumber2');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        $data = array('username' => 'testdupusername3',
                      'idnumber' => 'testdupidnumber3');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);

        $data = array('username' => 'testdupusername4',
                      'email' => 'testdup2@testdup4.com',
                      'idnumber' => 'testdupidnumber');
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initial_count + 1, $count);
    }

    /**
     * Validate that the version 1 plugin supports course actions
     */
    public function testVersion1ImportSupportsCourseActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'course');

        $this->assertNotEquals($supports, false);
    }

    /**
     * Validate that the version 1 plugin supports enrolment actions
     */
    public function testVersion1ImportSupportsEnrolmentActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment');

        $this->assertNotEquals($supports, false);
    }
}