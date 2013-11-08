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
 * @package    rlipimport_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/elis/core/lib/lib.php');

/**
 * Class for version 1 user import correctness.
 * @group block_rlip
 * @group rlipimport_version1
 */
class version1userimport_testcase extends rlip_test {

    /**
     * Helper function to get the core fields for a sample user
     *
     * @return array The user data
     */
    private function get_core_user_data() {
        $data = array(
            'entity' => 'user',
            'action' => 'create',
            'username' => 'rlipusername',
            'password' => 'Rlippassword!1234',
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'city' => 'rlipcity',
            'country' => 'CA'
        );
        return $data;
    }

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $extradata Extra fields to set for the new user
     */
    private function run_core_user_import($extradata, $usedefaultdata = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        if ($usedefaultdata) {
            $data = $this->get_core_user_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        $provider = new rlipimport_version1_importprovider_mockuser($data);

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
    private function create_profile_field($name, $datatype, $categoryid, $param1 = null) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');

        $class = "profile_define_{$datatype}";
        $field = new $class();
        $data = new stdClass;
        $data->shortname = $name;
        $data->name = $name;
        $data->datatype = $datatype;
        $data->categoryid = $categoryid;

        if ($param1 !== null) {
            $data->param1 = $param1;
        }

        $field->define_save($data);
    }

    /**
     * Asserts, using PHPunit, that the test user does not exist
     */
    private function assert_core_user_does_not_exist() {
        global $CFG, $DB;

        $exists = $DB->record_exists('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        $this->assertEquals($exists, false);
    }

    /**
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $tmp = '';
        if (!$exists) {
            ob_start();
            var_dump($params);
            var_dump($DB->get_records($table));
            $tmp = ob_get_contents();
            ob_end_clean();
        }
        $this->assertTrue($exists, "Error: record should exist in {$table} => params/DB = {$tmp}\n");
    }

    /**
     * Validate that the version 1 plugin supports user actions
     */
    public function test_version1importsupportsuseractions() {
        $supports = plugin_supports('rlipimport', 'version1', 'user');

        $this->assertEquals($supports, array('create', 'add', 'update', 'delete', 'disable'));
    }

    /**
     * Validate that the version 1 plugin supports user creation
     */
    public function test_version1importsupportsusercreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_create');
        $requiredfields = array(
                'username',
                'password',
                'firstname',
                'lastname',
                'email',
                'city',
                'country'
        );

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports user addition
     */
    public function test_version1importsupportsuseradd() {
        // Note: this is the same as user creation, but makes up for a weirdness.
        // In IP for 1.9.
        $supports = plugin_supports('rlipimport', 'version1', 'user_add');
        $requiredfields = array(
                'username',
                'password',
                'firstname',
                'lastname',
                'email',
                'city',
                'country'
        );

        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports user updates
     */
    public function test_version1importsupportsuserupdate() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_update');
        $requiredfields = array(array('username', 'email', 'idnumber'));
        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function test_version1importsetsrequireduserfieldsoncreate() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = $this->get_core_user_data();
        $provider = new rlipimport_version1_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        // User should be confirmed by default.
        $data['confirmed'] = 1;

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that required fields are set to specified values during user creation
     */
    public function test_version1importsetsrequireduserfieldsonadd() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/version1.class.php';
        require_once($file);

        $data = $this->get_core_user_data();
        $data['action'] = 'add';
        $provider = new rlipimport_version1_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();

        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);

        $exists = $DB->record_exists('user', $data);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that non-required fields are set to specified values during user creation
     */
    public function test_version1importsetsnonrequireduserfieldsoncreate() {
        global $CFG, $DB;

        set_config('allowuserthemes', 1);

        $data = array(
            'country' => 'CA',
            'auth' => 'mnet',
            'maildigest' => '2',
            'autosubscribe' => '1',
            'trackforums' => '1',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that fields are set to specified values during user update
     */
    public function test_version1importsetsfieldsonuserupdate() {
        global $CFG, $DB;
        $CFG->allowuserthemes = true;
        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data, false);

        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   institution = :institution AND
                   department = :department";

        $exists = $DB->record_exists_select('user', $select, $data);

        $this->assertTrue($exists);
    }

    /**
     * Validate that yes/no fields are mapped to valid values during user update
     */
    public function test_version1importmapsfieldsonuserupdate() {
        global $CFG, $DB;
        $CFG->allowuserthemes = true;
        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'auth' => 'mnet',
            'maildigest' => 2,
            'autosubscribe' => 'yes',
            'trackforums' => 'yes',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );

        $this->run_core_user_import($data, false);

        foreach ($data as $key => $val) {
            if (in_array((string)$val, array('no', 'yes'))) {
                $data[$key] = ((string)$val == 'yes') ? 1: 0;
            }
        }
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   institution = :institution AND
                   department = :department";

        $exists = $DB->record_exists_select('user', $select, $data);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that invalid auth plugins can't be set on user creation
     */
    public function test_version1importpreventsinvaliduserauthoncreate() {
        $this->run_core_user_import(array('auth' => 'invalidauth'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid auth plugins can't be set on user update
     */
    public function test_version1importpreventsinvaliduserauthonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update', 'username' => 'rlipusername', 'auth' => 'bogus');

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
                'username' => 'rlipusername',
                'mnethostid' => $CFG->mnet_localhost_id,
                'auth' => 'manual'
        ));
    }

    protected function set_password_policy_for_tests() {
        global $CFG;
        $CFG->passwordpolicy = true;
        $CFG->minpasswordlength = 8;
        $CFG->minpassworddigits = 1;
        $CFG->minpasswordlower = 1;
        $CFG->minpasswordupper = 1;
        $CFG->minpasswordnonalphanum = 1;
        $CFG->maxconsecutiveidentchars = 0;
    }

    /**
     * Validate that supplied passwords must match the site's password policy on user creation
     */
    public function test_version1importpreventsinvaliduserpasswordoncreate() {
        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array('password' => 'asdf'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that supplied passwords must match the site's password policy on user update
     */
    public function test_version1importpreventsinvaliduserpasswordonupdate() {
        global $CFG, $DB;
        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array());

        $data = array('action' => 'update', 'username' => 'rlipusername', 'password' => 'asdf');

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
                'username' => 'rlipusername',
                'mnethostid' => $CFG->mnet_localhost_id,
                'password' => hash_internal_user_password('Rlippassword!1234')
        ));
    }

    /**
     * Validate that invalid email addresses can't be set on user creation
     */
    public function test_version1importpreventsinvaliduseremailoncreate() {
        $this->run_core_user_import(array('email' => 'invalidemail'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid maildigest values can't be set on user creation
     */
    public function test_version1importpreventsinvalidusermaildigestoncreate() {
        $this->run_core_user_import(array('maildigest' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid maildigest values can't be set on user update
     */
    public function test_version1importpreventsinvalidusermaildigestonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update', 'username' => 'rlipusername', 'maildigest' => 3);

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
                'username' => 'rlipusername',
                'mnethostid' => $CFG->mnet_localhost_id,
                'maildigest' => 0
        ));
    }

    /**
     * Validate that invalid autosubscribe values can't be set on user creation
     */
    public function test_version1importpreventsinvaliduserautosubscribeoncreate() {
        $this->run_core_user_import(array('autosubscribe' => 3));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid autosubscribe values can't be set on user update
     */
    public function test_version1importpreventsinvaliduserautosubscribeonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array('action' => 'update', 'username' => 'rlipusername', 'autosubscribe' => 3);

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
                'username' => 'rlipusername',
                'mnethostid' => $CFG->mnet_localhost_id,
                'autosubscribe' => 1
        ));
    }

    /**
     * Validate that invalid trackforums values can't be set on user creation
     */
    public function test_version1importpreventsinvalidusertrackforumsoncreate() {
        set_config('forum_trackreadposts', 0);

        $this->run_core_user_import(array('trackforums' => 1));
        $this->assert_core_user_does_not_exist();

        set_config('forum_trackreadposts', 1);

        $this->run_core_user_import(array('trackforums' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid trackforums values can't be set on user update
     */
    public function test_version1importpreventsinvalidusertrackforumsonupdate() {
        global $CFG, $DB;

        set_config('forum_trackreadposts', 0);
        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'trackforums' => 1
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'trackforums' => 0
        ));

        set_config('forum_trackreadposts', 1);
        $data['trackforums'] = 2;
        $this->run_core_user_import($data);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'trackforums' => 0
        ));
    }

    /**
     * Validate that invalid screenreader values can't be set on user creation
     */
    public function test_version1importpreventsinvaliduserscreenreaderoncreate() {
        $this->run_core_user_import(array('screenreader' => 2));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid screenreader values can't be set on user update
     */
    public function test_version1importpreventsinvaliduserscreenreaderonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername'
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
    }

    /**
     * Validate that invalid country values can't be set on user creation
     */
    public function test_version1importpreventsinvalidusercountryoncreate() {
        $this->run_core_user_import(array('country' => 12));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid country values can't be set on user update
     */
    public function test_version1importpreventsinvalidusercountryonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array('country' => 'CA'));

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'country' => 12
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'country' => 'CA'
        ));
    }

    /**
     * Validate that invalid timezone values can't be set on user creation
     */
    public function test_version1importpreventsinvalidusertimezoneoncreate() {
        $this->run_core_user_import(array('timezone' => 14.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid timezone values can't be set on user update
     */
    public function test_version1importpreventsinvalidusertimezoneonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'timezone' => 14.0
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'timezone' => 99
        ));
    }

    /**
     * Validate that timezone values can't be set on user creation when they are forced globally
     */
    public function test_version1importpreventsoverridingforcedtimezoneoncreate() {
        set_config('forcetimezone', 10);

        $this->run_core_user_import(array('timezone' => 5.0));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that timezone values can't be set on user update when they are forced globally
     */
    public function test_version1importpreventsoverridingforcedtimezoneonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        set_config('forcetimezone', 10);

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'timezone' => 5.0
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'timezone' => 99
        ));
    }

    /**
     * Validate that invalid theme values can't be set on user creation
     */
    public function test_version1importpreventsinvaliduserthemeoncreate() {
        set_config('allowuserthemes', 0);

        $this->run_core_user_import(array('theme' => 'rlmaster'));
        $this->assert_core_user_does_not_exist();

        set_config('allowuserthemes', 1);

        $this->run_core_user_import(array('theme' => 'bogus'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid theme values can't be set on user update
     */
    public function test_version1importpreventsinvaliduserthemeonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        set_config('allowuserthemes', 0);

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'theme' => 'rlmaster'
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'theme' => ''
        ));

        set_config('allowuserthemes', 1);

        $data['theme'] = 'bogus';

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'theme' => ''
        ));
    }

    /**
     * Validate that invalid lang values can't be set on user creation
     */
    public function test_version1importpreventsinvaliduserlangoncreate() {
        $this->run_core_user_import(array('lang' => '12'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that invalid lang values can't be set on user update
     */
    public function test_version1importpreventsinvaliduserlangonupdate() {
        global $DB, $CFG;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'lang' => '12'
        );

        $this->run_core_user_import($data, false);

        // Make sure the data hasn't changed.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'lang' => $CFG->lang
        ));
    }

    /**
     * Validate that the import defaults to not setting idnumber values if
     * a value is not supplied and ELIS is not configured to auto-assign
     */
    public function test_version1importdoesnotsetidnumberwhennotsuppliedorconfigured() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        $this->run_core_user_import(array());

        // Make sure idnumber wasn't set.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => ''
        ));
    }

    /**
     * Validate the the import can set a user's idnumber value on user creation
     */
    public function test_version1importsetssuppliedidnumberoncreate() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        // Run the import.
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        // Make sure idnumber was set.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber'
        ));
    }

    /**
     * Validate the the import can't set a user's idnumber value on user update
     */
    public function test_version1importdoesnotsetsuppliedidnumberonupdate() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        // Create the user.
        $this->run_core_user_import(array());

        // Run the import.
        $this->run_core_user_import(array(
            'action' => 'update',
            'username' => 'rlipusername',
            'idnumber' => 'rlipidnumber'
        ));

        // Make sure the idnumber was not set.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => ''
        ));
    }

    /**
     * Validate that the import auto-assigns missing idnumbers when the column
     * is not supplied and the config setting is on
     */
    public function test_version1importautoassignsmissingidnumbersoncreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        // Make sure we are auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        // Run the import.
        $this->run_core_user_import(array('rlipusername' => 'rlipusername'));

        // Make sure the idnumber was set to the username value.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipusername'
        ));
    }

    /**
     * Validate that the import auto-assigns missing idnumbers when they are
     * empty and the config setting is on
     */
    public function test_version1importautoassignsemptyidnumbersoncreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        // Make sure we are auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        // Run the import.
        $this->run_core_user_import(array('rlipusername' => 'rlipusername', 'idnumber' => ''));

        // Make sure the idnumber was set to the username value.
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipusername'
        ));
    }

    /**
     * Validate that the import performs user synchronization on user create
     * when an idnumber is supplied
     */
    public function test_version1importsyncsusertoelisoncreatewithidnumbersupplied() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once($CFG->dirroot.'/elis/core/fields/moodle_profile/custom_fields.php');

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        // Create Moodle custom field category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create Moodle custom profile field.
        $this->create_profile_field('rliptext', 'text', $category->id);

        // Obtain the PM user context level.
        $contextlevel = CONTEXT_ELIS_USER;

        // Make sure the PM category and field exist.
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array(
            'shortname'   => 'rliptext',
            'name'        => 'rliptext',
            'datatype'    => 'text',
            'multivalued' => 0
        ));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        // Make sure the field owner is setup.
        field_owner::ensure_field_owner_exists($field, 'manual');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'manual'));
        $owner = new field_owner($ownerid);
        $owner->param_control = 'text';
        $owner->save();

        // Make sure the field is set up for synchronization.
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'moodle_profile'));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        // Update the user class's static cache of define user custom fields.
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        // Run the import.
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber', 'profile_field_rliptext' => 'rliptext'));

        // Make sure PM user was created correctly.
        $this->assert_record_exists(user::TABLE, array('username' => 'rlipusername', 'idnumber' => 'rlipidnumber'));

        // Make sure the PM custom field data was set.
        $sql = "SELECT 'x'
                  FROM {".field::TABLE."} f
                  JOIN {".field_data_text::TABLE."} d ON f.id = d.fieldid
                 WHERE f.shortname = ? AND d.data = ?";
        $params = array('rliptext', 'rliptext');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import performs user synchronization on user create
     * when an idnumber is auto-assigned
     */
    public function test_version1importsyncsusertoelisoncreatewithidnumberautoassigned() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 1, 'elis_program');
        elis::$config = new elis_config();

        // Create Moodle custom field category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create Moodle custom profile field.
        $this->create_profile_field('rliptext', 'text', $category->id);

        // Obtain the PM user context level.
        $contextlevel = CONTEXT_ELIS_USER;

        // Make sure the PM category and field exist.
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array(
            'shortname'   => 'rliptext',
            'name'        => 'rliptext',
            'datatype'    => 'text',
            'multivalued' => 0
        ));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        // Make sure the field owner is setup.
        field_owner::ensure_field_owner_exists($field, 'manual');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'manual'));
        $owner = new field_owner($ownerid);
        $owner->param_control = 'text';
        $owner->save();

        // Make sure the field is set up for synchronization.
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'moodle_profile'));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        // Update the user class's static cache of define user custom fields.
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        // Run the import.
        $this->run_core_user_import(array('profile_field_rliptext' => 'rliptext'));

        // Make sure PM user was created correctly.
        $this->assert_record_exists(user::TABLE, array('username' => 'rlipusername', 'idnumber' => 'rlipusername'));

        // Make sure the PM custom field data was set.
        $sql = "SELECT 'x'
                  FROM {".field::TABLE."} f
                  JOIN {".field_data_text::TABLE."} d ON f.id = d.fieldid
                 WHERE f.shortname = ? AND d.data = ?";
        $params = array('rliptext', 'rliptext');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import performs user synchronization on user update
     */
    public function test_version1importsyncsusertoelisonupdate() {
        global $CFG, $DB;

        if (!$DB->record_exists('block', array('name' => 'curr_admin'))) {
            $this->markTestIncomplete('This test depends on the PM system');
        }

        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');
        require_once(elis::lib('data/customfield.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        // Create Moodle custom field category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create Moodle custom profile field.
        $this->create_profile_field('rliptext', 'text', $category->id);

        // Obtain the PM user context level.
        $contextlevel = CONTEXT_ELIS_USER;

        // Make sure the PM category and field exist.
        $category = new field_category(array('name' => 'rlipcategory'));

        $field = new field(array(
            'shortname'   => 'rliptext',
            'name'        => 'rliptext',
            'datatype'    => 'text',
            'multivalued' => 0
        ));
        $field = field::ensure_field_exists_for_context_level($field, $contextlevel, $category);

        // Make sure the field owner is setup.
        field_owner::ensure_field_owner_exists($field, 'manual');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'manual'));
        $owner = new field_owner($ownerid);
        $owner->param_control = 'text';
        $owner->save();

        // Make sure the field is set up for synchronization.
        field_owner::ensure_field_owner_exists($field, 'moodle_profile');
        $ownerid = $DB->get_field('elis_field_owner', 'id', array('fieldid' => $field->id, 'plugin' => 'moodle_profile'));
        $owner = new field_owner($ownerid);
        $owner->exclude = pm_moodle_profile::sync_from_moodle;
        $owner->save();

        // Update the user class's static cache of define user custom fields.
        $tempuser = new user();
        $tempuser->reset_custom_field_list();

        // Create the user.
        $this->run_core_user_import(array(
            'idnumber' => 'rlipidnumber',
            'profile_field_rliptext' => 'rliptext'
        ));

        // Make sure PM user was created correctly.
        $this->assert_record_exists(user::TABLE, array(
            'username' => 'rlipusername',
            'idnumber' => 'rlipidnumber'
        ));

        // Run the import, updating the user.
        $this->run_core_user_import(array(
            'action' => 'update',
            'username' => 'rlipusername',
            'profile_field_rliptext' => 'rliptextupdated'
        ));

        // Make sure the PM custom field data was set.
        $sql = "SELECT 'x'
                  FROM {".field::TABLE."} f
                  JOIN {".field_data_text::TABLE."} d ON f.id = d.fieldid
                 WHERE f.shortname = ? AND d.data = ?";
        $params = array('rliptext', 'rliptextupdated');
        $exists = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that default values are correctly set on user creation
     */
    public function test_version1importsetsdefaultsonusercreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');

        set_config('forcetimezone', 99);

        // Make sure we are not auto-assigning idnumbers.
        set_config('auto_assign_user_idnumber', 0, 'elis_program');
        elis::$config = new elis_config();

        $this->run_core_user_import(array());

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   timezone = :timezone AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'manual',
            'maildigest' => 0,
            'autosubscribe' => 1,
            'trackforums' => 0,
            'timezone' => 99,
            'theme' => '',
            'lang' => $CFG->lang,
            'description' => '',
            'idnumber' => '',
            'institution' => '',
            'department' => ''
        );

        $exists = $DB->record_exists_select('user', $select, $params);

        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the import does not set unsupported fields on user creation
     */
    public function test_version1importpreventssettingunsupporteduserfieldsoncreate() {
        global $CFG, $DB;

        $data = array();
        $data['forcepasswordchange'] = 1;
        $data['maildisplay'] = 1;
        $data['mailformat'] = 0;
        $data['htmleditor'] = 0;
        $data['descriptionformat'] = FORMAT_WIKI;
        $this->run_core_user_import($data);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   maildisplay = :maildisplay AND
                   mailformat = :mailformat AND
                   htmleditor = :htmleditor AND
                   descriptionformat = :descriptionformat";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'maildisplay' => 2,
            'mailformat' => 1,
            'htmleditor' => 1,
            'descriptionformat' => FORMAT_HTML
        );

        // Make sure that a record exists with the default data rather than with the.
        // Specified values.
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);

        // Check force password change separately.
        $user = $DB->get_record('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        $preferences = get_user_preferences('forcepasswordchange', null, $user);

        $this->assertEquals(count($preferences), 0);
    }

    /**
     * Validate that import does not set unsupported fields on user update
     */
    public function test_version1importpreventssettingunsupporteduserfieldsonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array());

        $data = array();
        $data['action'] = 'update';
        $data['username'] = 'rlipusername';
        $data['forcepasswordchange'] = 1;
        $data['maildisplay'] = 1;
        $data['mailformat'] = 0;
        $data['htmleditor'] = 0;
        $data['descriptionformat'] = FORMAT_WIKI;

        $this->run_core_user_import($data, false);

        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   maildisplay = :maildisplay AND
                   mailformat = :mailformat AND
                   htmleditor = :htmleditor AND
                   descriptionformat = :descriptionformat";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'maildisplay' => 2,
            'mailformat' => 1,
            'htmleditor' => 1,
            'descriptionformat' => FORMAT_HTML
        );

        // Make sure that a record exists with the default data rather than with the.
        // Specified values.
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);

        // Check force password change separately.
        $user = $DB->get_record('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        $preferences = get_user_preferences('forcepasswordchange', null, $user);

        $this->assertEquals(count($preferences), 0);
    }

    /**
     * Validate that field-length checking works correctly on user creation
     */
    public function test_version1importpreventslonguserfieldsoncreate() {
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
     * Validate that field-length checking works correct on user update
     */
    public function test_version1importpreventslonguserfieldsonupdate() {
        global $CFG, $DB;

        $this->run_core_user_import(array(
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => str_repeat('a', 101)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => 'rlipfirstname'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'lastname' => str_repeat('a', 101)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'lastname' => 'rliplastname'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'email' => str_repeat('a', 50).'@'.str_repeat('b', 50)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'email' => 'rlipuser@rlipdomain.com'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'city' => str_repeat('a', 256)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'city' => 'rlipcity'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'idnumber' => str_repeat('a', 256)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'institution' => str_repeat('a', 41)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'institution' => 'rlipinstitution'
        ));

        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'department' => str_repeat('a', 31)
        );
        $this->run_core_user_import($params, false);
        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'department' => 'rlipdepartment'
        ));
    }

    /**
     * Validate that setting profile fields works on user creation
     */
    public function test_version1importsetsuserprofilefieldsoncreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/profile/definelib.php');

        // Create custom field category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create custom profile fields.
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->create_profile_field('rliplegacydatetime', 'datetime', $category->id);
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption2");
        $this->create_profile_field('rliptextarea', 'textarea', $category->id);
        $this->create_profile_field('rliptext', 'text', $category->id);

        // Run import.
        $data = array();
        $data['profile_field_rlipcheckbox'] = '1';
        $data['profile_field_rlipdatetime'] = 'jan/12/2011';
        $data['profile_field_rliplegacydatetime'] = '1/12/2011';
        $data['profile_field_rlipmenu'] = 'rlipoption1';
        $data['profile_field_rliptextarea'] = 'rliptextarea';
        $data['profile_field_rliptext'] = 'rliptext';

        $this->run_core_user_import($data);

        // Fetch the user and their profile field data.
        $user = $DB->get_record('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        profile_load_data($user);
        fix_moodle_profile_fields($user);

        // Validate data.
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), true);
        $this->assertEquals($user->profile_field_rlipcheckbox, 1);
        $this->assertEquals(isset($user->profile_field_rlipdatetime), true);
        $this->assertEquals($user->profile_field_rlipdatetime, rlip_timestamp(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rliplegacydatetime), true);
        $this->assertEquals($user->profile_field_rliplegacydatetime, rlip_timestamp(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rlipmenu), true);
        $this->assertEquals($user->profile_field_rlipmenu, 'rlipoption1');
        $this->assertEquals(isset($user->profile_field_rliptextarea['text']), true);
        $this->assertEquals($user->profile_field_rliptextarea['text'], 'rliptextarea');
        $this->assertEquals(isset($user->profile_field_rliptext), true);
        $this->assertEquals($user->profile_field_rliptext, 'rliptext');
    }

    /**
     * Validate that setting profile fields works on user update
     */
    public function test_version1importsetsuserprofilefieldsonupdate() {
        global $CFG, $DB;

        // Perform default "user create" import.
        $this->run_core_user_import(array());

        // Create custom field category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Create custom profile fields.
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $this->create_profile_field('rliplegacydatetime', 'datetime', $category->id);
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption2");
        $this->create_profile_field('rliptextarea', 'textarea', $category->id);
        $this->create_profile_field('rliptext', 'text', $category->id);

        // Run import.
        $data = array();
        $data['action'] = 'update';
        $data['username'] = 'rlipusername';
        $data['profile_field_rlipcheckbox'] = '1';
        $data['profile_field_rlipdatetime'] = 'jan/12/2011';
        $data['profile_field_rliplegacydatetime'] = '1/12/2011';
        $data['profile_field_rlipmenu'] = 'rlipoption1';
        $data['profile_field_rliptextarea'] = 'rliptextarea';
        $data['profile_field_rliptext'] = 'rliptext';

        $this->run_core_user_import($data, false);

        // Fetch the user and their profile field data.
        $user = $DB->get_record('user', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        profile_load_data($user);
        fix_moodle_profile_fields($user);

        // Validate data.
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), true);
        $this->assertEquals($user->profile_field_rlipcheckbox, 1);
        $this->assertEquals(isset($user->profile_field_rlipdatetime), true);
        $this->assertEquals($user->profile_field_rlipdatetime, rlip_timestamp(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rliplegacydatetime), true);
        $this->assertEquals($user->profile_field_rliplegacydatetime, rlip_timestamp(0, 0, 0, 1, 12, 2011));
        $this->assertEquals(isset($user->profile_field_rlipmenu), true);
        $this->assertEquals($user->profile_field_rlipmenu, 'rlipoption1');
        $this->assertEquals(isset($user->profile_field_rliptextarea['text']), true);
        $this->assertEquals($user->profile_field_rliptextarea['text'], 'rliptextarea');
        $this->assertEquals(isset($user->profile_field_rliptext), true);
        $this->assertEquals($user->profile_field_rliptext, 'rliptext');
    }

    /**
     * Validate that the import does not create bogus profile field data on user creation
     */
    public function test_version1importvalidatesprofilefieldsoncreate() {
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

        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption1B");
        $this->run_core_user_import(array('profile_field_rlipmenu' => 'rlipoption2'));
        $this->assert_core_user_does_not_exist();
    }

    /**
     * Validate that the import does not create bogus profile field data on user update
     */
    public function test_version1importvalidatesprofilefieldsonupdate() {
        global $CFG, $DB;

        // Run the "create user" import.
        $this->run_core_user_import(array());

        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));

        // Create the category.
        $category = new stdClass;
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $category->id = $DB->insert_record('user_info_category', $category);

        // Try to insert bogus checkbox data.
        $this->create_profile_field('rlipcheckbox', 'checkbox', $category->id);
        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'profile_field_rlipcheckbox' => '2'
        );
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        fix_moodle_profile_fields($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);

        // Try to insert bogus datetime data.
        $this->create_profile_field('rlipdatetime', 'datetime', $category->id);
        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'profile_field_rlipdatetime' => '1000000000'
        );
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        fix_moodle_profile_fields($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);

        // Try to insert bogus menu data.
        $this->create_profile_field('rlipmenu', 'menu', $category->id, "rlipoption1\nrlipoption1B");
        $params = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'profile_field_rlipmenu' => 'rlipoption2'
        );
        $this->run_core_user_import($params);
        $user = new stdClass;
        $user->id = $userid;
        profile_load_data($user);
        fix_moodle_profile_fields($user);
        $this->assertEquals(isset($user->profile_field_rlipcheckbox), false);
    }

    /**
     * Validate that the import does not create duplicate user records on creation
     */
    public function test_version1importpreventsduplicateusercreation() {
        global $DB;

        $initialcount = $DB->count_records('user');

        // Set up our data.
        $this->run_core_user_import(array('idnumber' => 'testdupidnumber'));
        $count = $DB->count_records('user');
        $this->assertEquals($initialcount + 1, $count);

        // Test duplicate username.
        $data = array(
            'email' => 'testdup2@testdup2.com',
            'idnumber' => 'testdupidnumber2'
        );
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initialcount + 1, $count);

        // Test duplicate email.
        $data = array(
            'username' => 'testdupusername3',
            'idnumber' => 'testdupidnumber3'
        );
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initialcount + 1, $count);

        // Test duplicate idnumber.
        $data = array(
            'username' => 'testdupusername4',
            'email' => 'testdup2@testdup4.com',
            'idnumber' => 'testdupidnumber'
        );
        $this->run_core_user_import($data);
        $count = $DB->count_records('user');
        $this->assertEquals($initialcount + 1, $count);
    }

    /**
     * Validate that the plugin can update users based on any combination
     * of username, email and idnumber
     */
    public function test_version1importupdatesbasedonidentifyingfields() {
        global $CFG;

        // Set up our data.
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        // Update based on username.
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'setfromusername'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        // Update based on email.
        $data = array(
            'action' => 'update',
            'email' => 'rlipuser@rlipdomain.com',
            'firstname' => 'setfromemail'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        // Update based on idnumber.
        $data = array(
            'action' => 'update',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'setfromidnumber'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        // Update based on username, email.
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'firstname' => 'setfromusernameemail'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        // Update based on username, idnumber.
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'setfromusernameidnumber'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);

        // Update based on email, idnumber.
        $data = array(
            'action' => 'update',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'setfromemailidnumber'
        );
        $this->run_core_user_import($data, false);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate that updating users does not produce any side-effects
     * in the user data
     */
    public function test_version1importonlyupdatessupplieduserfields() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'updatedfirstname'
        );

        $this->run_core_user_import($data, false);

        $data = $this->get_core_user_data();
        unset($data['entity']);
        unset($data['action']);
        $data['mnethostid'] = $CFG->mnet_localhost_id;
        $data['password'] = hash_internal_user_password($data['password']);
        $data['firstname'] = 'updatedfirstname';

        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate that update actions must match existing users to do anything
     */
    public function test_version1importdoesnotupdatenonmatchingusers() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber', 'firstname' => 'oldfirstname'));

        $checkdata = array(
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'oldfirstname'
        );

        // Bogus username.
        $data = array(
            'action' => 'update',
            'username' => 'bogususername',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Bogus email.
        $data = array(
            'action' => 'update',
            'email' => 'bogus@domain.com',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Bogus idnumber.
        $data = array(
            'action' => 'update',
            'idnumber' => 'bogusidnumber',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);
    }

    /**
     * Validate that fields identifying users in updates are not updated
     */
    public function test_version1importdoesnotupdateidentifyinguserfields() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber', 'firstname' => 'oldfirstname'));

        $checkdata = array(
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'oldfirstname'
        );

        // Valid username, bogus email.
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'email' => 'bogus@domain.com',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Valid username, bogus idnumber.
        $data = array(
            'action' => 'update',
            'username' => 'rlipusername',
            'idnumber' => 'bogusidnumber',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Valid email, bogus username.
        $data = array(
            'action' => 'update',
            'username' => 'bogususername',
            'email' => 'rlipuser@rlipdomain.com',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Valid email, bogus idnumber.
        $data = array(
            'action' => 'update',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'bogusidnumber',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Valid idnumber, bogus username.
        $data = array(
            'action' => 'update',
            'username' => 'bogususername',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);

        // Valid idnumber, bogus email.
        $data = array(
            'action' => 'update',
            'email' => 'bogus@domain.com',
            'idnumber' => 'rlipidnumber',
            'firstname' => 'newfirstname'
        );
        $this->run_core_user_import($data, false);
        $this->assert_record_exists('user', $checkdata);
    }

    /**
     * Validate that user create and update actions set time created
     * and time modified appropriately
     */
    public function test_version1importsetsusertimestamps() {
        global $CFG, $DB;

        $starttime = time();

        // Set up base data.
        $this->run_core_user_import(array());

        // Validate timestamps.
        $where = "username = ? AND
                  mnethostid = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('rlipusername', $CFG->mnet_localhost_id, $starttime, $starttime);
        $exists = $DB->record_exists_select('user', $where, $params);
        $this->assertEquals($exists, true);

        // Reset time modified.
        $user = new stdClass;
        $user->id = $DB->get_field('user', 'id', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));
        $user->timemodified = 0;
        $DB->update_record('user', $user);

        // Update data.
        $this->run_core_user_import(array(
            'action' => 'update',
            'username' => 'rlipusername',
            'firstname' => 'newfirstname'
        ));

        // Validate timestamps.
        $where = "username = ? AND
                  mnethostid = ? AND
                  timecreated >= ? AND
                  timemodified >= ?";
        $params = array('rlipusername', $CFG->mnet_localhost_id, $starttime, $starttime);
        $exists = $DB->record_exists_select('user', $where, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that the version 1 plugin supports user deletes
     */
    public function test_version1importsupportsuserdelete() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_delete');
        $requiredfields = array(array('username', 'email', 'idnumber'));
        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin supports user disabling
     */
    public function test_version1importsupportsuserdisable() {
        $supports = plugin_supports('rlipimport', 'version1', 'user_disable');
        $requiredfields = array(array('username', 'email', 'idnumber'));
        $this->assertEquals($supports, $requiredfields);
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username
     */
    public function test_version1importdeletesuserbasedonusername() {
        global $CFG, $DB;
        set_config('siteguest', 0);

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));

        $data = array('action' => 'delete', 'username' => 'rlipusername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on email
     */
    public function test_version1importdeletesuserbasedonemail() {
        global $DB;

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('action' => 'delete', 'email' => 'rlipuser@rlipdomain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

     /**
      * Validate that the version 1 plugin can delete uses based on idnumber
      */
    public function test_version1importdeletesuserbasedonidnumber() {
        global $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete', 'idnumber' => 'rlipidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username and
     * email
     */
    public function test_version1importdeletesuserbasedonusernameemail() {
        global $CFG, $DB;

        $this->run_core_user_import(array());
        $userid = $DB->get_field('user', 'id', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'email' => 'rlipuser@rlipdomain.com'
        ));

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username and
     * idnumber
     */
    public function test_version1importdeletesuserbasedonusernameidnumber() {
        global $CFG, $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber'
        ));

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'idnumber' => 'rlipidnumber'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on email and
     * idnumber
     */
    public function test_version1importdeletesuserbasedonemailidnumber() {
        global $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array('email' => 'rlipuser@rlipdomain.com', 'idnumber' => 'rlipidnumber'));

        $data = array(
            'action' => 'delete',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin can delete uses based on username, email and
     * idnumber
     */
    public function test_version1importdeletesuserbasedonusernameemailidnumber() {
        global $CFG, $DB;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));
        $userid = $DB->get_field('user', 'id', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        ));

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('id' => $userid, 'deleted' => 1));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified username is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithinvalidusername() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array('action' => 'delete', 'username' => 'bogususername');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified email is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithinvalidemail() {
        $this->run_core_user_import(array());

        $data = array('action' => 'delete', 'email' => 'bogus@domain.com');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('email' => 'rlipuser@rlipdomain.com', 'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete users when the
     * specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithinvalididnumber() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array('action' => 'delete', 'idnumber' => 'bogusidnumber');
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array('idnumber' => 'rlipidnumber', 'deleted' => 0));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified username if the specified email is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalidusernameinvalidemail() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'email' => 'bogus@domain.com'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'email' => 'rlipuser@rlipdomain.com',
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified username if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalidusernameinvalididnumber() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array(
            'action' => 'delete',
            'username' => 'rlipusername',
            'idnumber' => 'bogusidnumber'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'rlipidnumber',
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified email if the specified username is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalidemailinvalidusername() {
        global $CFG;

        $this->run_core_user_import(array());

        $data = array(
            'action' => 'delete',
            'email' => 'rlipuser@rlipdomain.com',
            'username' => 'bogususername'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'email' => 'rlipuser@rlipdomain.com',
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified email if the specified idnumber is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalidemailinvalididnumber() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array(
            'action' => 'delete',
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'bogusidnumber'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'email' => 'rlipuser@rlipdomain.com',
            'idnumber' => 'rlipidnumber',
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified idnumber if the specified username is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalididnumberinvalidusername() {
        global $CFG;

        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array(
            'action' => 'delete',
            'idnumber' => 'rlipidnumber',
            'username' => 'bogususername'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'idnumber' => 'rlipidnumber',
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin does not delete a user with the
     * specified idnumber if the specified email is incorrect
     */
    public function test_version1importdoesnotdeleteuserwithvalididnumberinvalidemail() {
        $this->run_core_user_import(array('idnumber' => 'rlipidnumber'));

        $data = array(
            'action' => 'delete',
            'idnumber' => 'rlipidnumber',
            'email' => 'bogus@domain.com'
        );
        $this->run_core_user_import($data, false);

        $this->assert_record_exists('user', array(
            'idnumber' => 'rlipidnumber',
            'email' => 'rlipuser@rlipdomain.com',
            'deleted' => 0
        ));
    }

    /**
     * Validate that the version 1 plugin deletes appropriate associations when
     * deleting a user
     */
    public function test_version1importdeleteuserdeletesassociations() {
        global $CFG, $DB;
        set_config('siteadmins', 0);
        // New config settings needed for course format refactoring in 2.4.
        set_config('numsections', 15, 'moodlecourse');
        set_config('hiddensections', 0, 'moodlecourse');
        set_config('coursedisplay', 1, 'moodlecourse');

        require_once($CFG->dirroot.'/cohort/lib.php');
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/lib/gradelib.php');

        // Create our test user, and determine their userid.
        $this->run_core_user_import(array());
        $userid = (int)$DB->get_field('user', 'id', array('username' => 'rlipusername', 'mnethostid' => $CFG->mnet_localhost_id));

        // The the user to a cohort - does not require cohort to actually exist.
        cohort_add_member(1, $userid);

        // Create a course category - there is no API for doing this.
        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        // Create a course.
        set_config('defaultenrol', 1, 'enrol_manual');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_manual');
        $course = new stdClass;
        $course->category = $category->id;
        $course->fullname = 'testfullname';
        $course = create_course($course);

        // Create a grade.
        $gradeitem = new grade_item(array('courseid' => $course->id, 'itemtype' => 'manual', 'itemname' => 'testitem'), false);
        $gradeitem->insert();
        $gradegrade = new grade_grade(array('itemid' => $gradeitem->id, 'userid' => $userid), false);
        $gradegrade->insert();

        // Send the user an unprocessed message.
        set_config('noemailever', true);
        $DB->delete_records('message_processors');
        $message = new stdClass;
        $message->userfrom = $userid;
        $message->userto = $userid;
        $message->component = 'moodle';
        $message->name = 'notices';
        $message->subject = 'testsubject';
        $message->fullmessage = 'testmessage';
        $message->fullmessagehtml = 'testmessage';
        $message->smallmessage = 'testmessage';
        $message->fullmessageformat = FORMAT_PLAIN;
        message_send($message);

        // Set up a user tag.
        tag_set('user', $userid, array('testtag'));

        // Create a new course-level role.
        $roleid = create_role('testrole', 'testrole', 'testrole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        // Enrol the user in the course with the new role.
        enrol_try_internal_enrol($course->id, $userid, $roleid);

        // Create a group.
        $group = new stdClass;
        $group->name = 'testgroup';
        $group->courseid = $course->id;
        $groupid = groups_create_group($group);

        // Add the user to the group.
        groups_add_member($groupid, $userid);

        set_user_preference('testname', 'testvalue', $userid);

        // Create profile field data - don't both with the API here because it's a bit unwieldy.
        $userinfodata = new stdClass;
        $userinfodata->fieldid = 1;
        $userinfodata->data = 'bogus';
        $userinfodata->userid = $userid;
        $DB->insert_record('user_info_data', $userinfodata);

        // There is no easily accessible API for doing this.
        $lastaccess = new stdClass;
        $lastaccess->userid = $userid;
        $lastaccess->courseid = $course->id;
        $DB->insert_record('user_lastaccess', $lastaccess);

        // Assert data condition before delete.
        $this->assertEquals($DB->count_records('message_read'), 0);
        $this->assertEquals($DB->count_records('tag_instance'), 1);
        $this->assertEquals($DB->count_records('grade_grades'), 1);
        $this->assertEquals($DB->count_records('cohort_members'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('groups_members'), 1);
        $this->assertEquals($DB->count_records('user_preferences'), 1);
        $this->assertEquals($DB->count_records('user_info_data'), 1);
        $this->assertEquals($DB->count_records('user_lastaccess'), 1);

        $data = array('action' => 'delete', 'username' => 'rlipusername');
        $this->run_core_user_import($data, false);

        // Assert data condition after delete.
        $this->assertEquals($DB->count_records('message_read'), 1);
        $this->assertEquals($DB->count_records('grade_grades'), 0);
        $this->assertEquals($DB->count_records('tag_instance'), 0);
        $this->assertEquals($DB->count_records('cohort_members'), 0);
        $this->assertEquals($DB->count_records('user_enrolments'), 0);
        $this->assertEquals($DB->count_records('role_assignments'), 0);
        $this->assertEquals($DB->count_records('groups_members'), 0);
        $this->assertEquals($DB->count_records('user_preferences'), 0);
        $this->assertEquals($DB->count_records('user_info_data'), 0);
        $this->assertEquals($DB->count_records('user_lastaccess'), 0);
    }

    /**
     * Validate that the version 1 import plugin correctly uses field mappings
     * on user creation
     */
    public function test_version1importusesuserfieldmappings() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);
        $CFG->allowuserthemes = true;

        // Set up our mapping of standard field names to custom field names.
        $mapping = array(
            'action' => 'action1',
            'username' => 'username1',
            'auth' => 'auth1',
            'password' => 'password1',
            'firstname' => 'firstname1',
            'lastname' => 'lastname1',
            'email' => 'email1',
            'maildigest' => 'maildigest1',
            'autosubscribe' => 'autosubscribe1',
            'trackforums' => 'trackforums1',
            'city' => 'city1',
            'country' => 'country1',
            'timezone' => 'timezone1',
            'theme' => 'theme1',
            'lang' => 'lang1',
            'description' => 'description1',
            'idnumber' => 'idnumber1',
            'institution' => 'institution1',
            'department' => 'department1'
        );

        // Store the mapping records in the database.
        foreach ($mapping as $standardfieldname => $customfieldname) {
            $record = new stdClass;
            $record->entitytype = 'user';
            $record->standardfieldname = $standardfieldname;
            $record->customfieldname = $customfieldname;
            $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);
        }

        // Run the import.
        $data = array(
            'entity' => 'user',
            'action1' => 'create',
            'username1' => 'rlipusername',
            'auth1' => 'mnet',
            'password1' => 'Rlippassword!0',
            'firstname1' => 'rlipfirstname',
            'lastname1' => 'rliplastname',
            'email1' => 'rlipuser@rlipdomain.com',
            'maildigest1' => '2',
            'autosubscribe1' => '1',
            'trackforums1' => '1',
            'city1' => 'rlipcity',
            'country1' => 'CA',
            'timezone1' => -5.0,
            'theme1' => 'standard',
            'lang1' => 'en',
            'description1' => 'rlipdescription',
            'idnumber1' => 'rlipidnumber',
            'institution1' => 'rlipinstitution',
            'department1' => 'rlipdepartment'
        );
        $this->run_core_user_import($data, false);

        // Validate user record.
        $select = "username = :username AND
                   mnethostid = :mnethostid AND
                   auth = :auth AND
                   password = :password AND
                   firstname = :firstname AND
                   lastname = :lastname AND
                   email = :email AND
                   maildigest = :maildigest AND
                   autosubscribe = :autosubscribe AND
                   trackforums = :trackforums AND
                   city = :city AND
                   country = :country AND
                   theme = :theme AND
                   lang = :lang AND
                   {$DB->sql_compare_text('description')} = :description AND
                   idnumber = :idnumber AND
                   institution = :institution AND
                   department = :department";
        $params = array(
            'username' => 'rlipusername',
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth' => 'mnet',
            'password' => hash_internal_user_password('Rlippassword!0'),
            'firstname' => 'rlipfirstname',
            'lastname' => 'rliplastname',
            'email' => 'rlipuser@rlipdomain.com',
            'maildigest' => 2,
            'autosubscribe' => 1,
            'trackforums' => 1,
            'city' => 'rlipcity',
            'country' => 'CA',
            'timezone' => -5.0,
            'theme' => 'standard',
            'lang' => 'en',
            'description' => 'rlipdescription',
            'idnumber' => 'rlipidnumber',
            'institution' => 'rlipinstitution',
            'department' => 'rlipdepartment'
        );
        $exists = $DB->record_exists_select('user', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that field mapping does not use a field if its name should be
     * mapped to some other value
     */
    public function test_version1importuserfieldimportpreventsstandardfielduse() {
        global $CFG, $DB;
        $plugindir = get_plugin_directory('rlipimport', 'version1');
        require_once($plugindir.'/version1.class.php');
        require_once($plugindir.'/lib.php');

        // Create the mapping record.
        $record = new stdClass;
        $record->entitytype = 'user';
        $record->standardfieldname = 'username';
        $record->customfieldname = 'username2';
        $DB->insert_record(RLIPIMPORT_VERSION1_MAPPING_TABLE, $record);

        // Get the import plugin set up.
        $data = array();
        $provider = new rlipimport_version1_importprovider_mockuser($data);
        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->mappings = rlipimport_version1_get_mapping('user');

        // Transform a sample record.
        $record = new stdClass;
        $record->username = 'username';
        $record = $importplugin->apply_mapping('user', $record);

        $DB->delete_records(RLIPIMPORT_VERSION1_MAPPING_TABLE);

        // Validate that the field was unset.
        $this->assertEquals(isset($record->username), false);
    }

    /**
     * Validate that the import succeeds with fixed-size fields at their
     * maximum sizes
     */
    public function test_version1importsucceedswithmaxlengthuserfields() {
        // Data for all fixed-size fields at their maximum sizes.
        $data = array(
            'username' => str_repeat('x', 100),
            'firstname' => str_repeat('x', 100),
            'lastname' => str_repeat('x', 100),
            'email' => str_repeat('x', 47).'@'.str_repeat('x', 48).'.com',
            'city' => str_repeat('x', 120),
            'idnumber' => str_repeat('x', 255),
            'institution' => str_repeat('x', 40),
            'department' => str_repeat('x', 30)
        );
        // Run the import.
        $this->run_core_user_import($data);

        // Data validation.
        $this->assert_record_exists('user', $data);
    }

    /**
     * Validate version1 import detects duplicate users under a specific condition.
     *
     * This verifies that duplicate users are detected when:
     *     - The user being imported has the email of an existing user, the username of another existing user, and the
     *     allowduplicateemails setting is on.
     */
    public function test_version1importdetectsduplicateswhenmultipleexist() {
        global $DB, $CFG;
        $userone = array(
            'username' => 'three',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'one',
            'email' => 'email@example.com',
            'firstname' => 'Test',
            'lastname' => 'User'
        );

        $usertwo = array(
            'username' => 'two',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'two',
            'email' => 'email2@example.com',
            'firstname' => 'Test',
            'lastname' => 'User'
        );
        $DB->insert_record('user', $userone);
        $DB->insert_record('user', $usertwo);
        set_config('allowduplicateemails', 1, 'rlipimport_version1');
        $usertoimport = array(
            'username' => 'two',
            'mnethostid' => $CFG->mnet_localhost_id,
            'idnumber' => 'two',
            'email' => 'email@example.com'
        );
        // Run the import.
        $this->run_core_user_import($usertoimport);
    }

    /**
     * Test main newusermail() function.
     */
    public function test_version1importnewuseremail() {
        global $CFG; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1_fakeemail.php');
        $importplugin = new rlip_importplugin_version1_fakeemail();

        $testuser = new stdClass;
        $testuser->username = 'testusername';
        $testuser->idnumber = 'testidnumber';
        $testuser->firstname = 'testfirstname';
        $testuser->lastname = 'testlastname';
        $testuser->email = 'testemail@example.com';

        // Test false return when not enabled.
        set_config('newuseremailenabled', '0', 'rlipimport_version1');
        set_config('newuseremailsubject', 'Test Subject', 'rlipimport_version1');
        set_config('newuseremailtemplate', 'Test Body', 'rlipimport_version1');
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);

        // Test false return when enabled but empty template.
        set_config('newuseremailenabled', '1', 'rlipimport_version1');
        set_config('newuseremailsubject', 'Test Subject', 'rlipimport_version1');
        set_config('newuseremailtemplate', '', 'rlipimport_version1');
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);

        // Test false return when enabled and has template, but user has empty email.
        set_config('newuseremailenabled', '1', 'rlipimport_version1');
        set_config('newuseremailsubject', 'Test Subject', 'rlipimport_version1');
        set_config('newuseremailtemplate', 'Test Body', 'rlipimport_version1');
        $testuser->email = '';
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);
        $testuser->email = 'test@example.com';

        // Test success when enabled, has template text, and user has email.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newuseremailenabled', '1', 'rlipimport_version1');
        set_config('newuseremailsubject', $testsubject, 'rlipimport_version1');
        set_config('newuseremailtemplate', $testbody, 'rlipimport_version1');
        $result = $importplugin->newuseremail($testuser);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($testuser, $result['user']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Test that subject is replaced by empty string when not present.
        $testsubject = null;
        $testbody = 'Test Body';
        set_config('newuseremailenabled', '1', 'rlipimport_version1');
        set_config('newuseremailsubject', $testsubject, 'rlipimport_version1');
        set_config('newuseremailtemplate', $testbody, 'rlipimport_version1');
        $result = $importplugin->newuseremail($testuser);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($testuser, $result['user']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals('', $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($testbody, $result['body']);

        // Full testing of replacement is done below, but just test that it's being done at all from the main function.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body %%username%%';
        $expectedtestbody = 'Test Body '.$testuser->username;
        set_config('newuseremailenabled', '1', 'rlipimport_version1');
        set_config('newuseremailsubject', $testsubject, 'rlipimport_version1');
        set_config('newuseremailtemplate', $testbody, 'rlipimport_version1');
        $result = $importplugin->newuseremail($testuser);
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($testuser, $result['user']);
        $this->assertArrayHasKey('subject', $result);
        $this->assertEquals($testsubject, $result['subject']);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals($expectedtestbody, $result['body']);
    }

    /**
     * Test new user email notifications.
     */
    public function test_version1importnewuseremailgenerate() {
        global $CFG; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1_fakeemail.php');
        $importplugin = new rlip_importplugin_version1_fakeemail();

        $templatetext = '<p>Hi %%fullname%%, your account has been created! It has the following information
            Sitename: %%sitename%%
            Login Link: %%loginlink%%
            Username: %%username%%
            Password: %%password%%
            Idnumber: %%idnumber%%
            First Name: %%firstname%%
            Last Name: %%lastname%%
            Full Name: %%fullname%%
            Email Address: %%email%%</p>';
        $user = new stdClass;
        $user->username = 'testusername';
        $user->cleartextpassword = 'cleartextpassword';
        $user->idnumber = 'testidnumber';
        $user->firstname = 'testfirstname';
        $user->lastname = 'testlastname';
        $user->email = 'testemail@example.com';
        $actualtext = $importplugin->newuseremail_generate($templatetext, $user);

        $expectedtext = '<p>Hi testfirstname testlastname, your account has been created! It has the following information
            Sitename: PHPUnit test site
            Login Link: http://www.example.com/moodle/login/index.php
            Username: testusername
            Password: cleartextpassword
            Idnumber: testidnumber
            First Name: testfirstname
            Last Name: testlastname
            Full Name: testfirstname testlastname
            Email Address: testemail@example.com</p>';
        $this->assertEquals($expectedtext, $actualtext);
    }

   /**
     * Validate that force password flag is set when password "changeme" is used on user creation.
     */
    public function test_version1importforcepasswordchangeoncreate() {
        global $DB;

        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array('password' => 'changeme'));

        $record = $DB->get_record('user', array('username' => 'rlipusername'));
        $id = isset($record->id) ? $record->id : 0;
        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record('user_preferences', array('userid' => $id));
        $name = isset($record->name) ? $record->name : '';
        $this->assertEquals('auth_forcepasswordchange', $name);
    }

    /**
     * Validate that force password flag is set when password "changeme" is used on user update.
     */
    public function test_version1importforcepasswordchangeonupdate() {
        global $DB;

        $this->set_password_policy_for_tests();
        $this->run_core_user_import(array());

        $record = $DB->get_record('user', array('username' => 'rlipusername'));
        $id = isset($record->id) ? $record->id : 0;
        $this->assertGreaterThan(0, $id);

        $this->run_core_user_import(array('action' => 'update', 'password' => 'changeme'));

        $record = $DB->get_record('user_preferences', array('userid' => $id));
        $name = isset($record->name) ? $record->name : '';
        $this->assertEquals('auth_forcepasswordchange', $name);
    }
}
