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
 * @package    rlipimport_version1elis
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
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');
require_once($CFG->dirroot.'/blocks/rlip/tests/other/silent_fslogger.class.php');

if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
    require_once($CFG->dirroot.'/elis/program/lib/setup.php');
    require_once(elispm::lib('data/user.class.php'));
}

// Handy constants for readability.
define('ELIS_USER_EXISTS', true);
define('ELIS_USER_DOESNOT_EXIST', false);
define('MDL_USER_EXISTS', true);
define('MDL_USER_DOESNOT_EXIST', false);
define('MDL_USER_DELETED', -1);
define('NO_TEST_SETUP', -1);

/**
 * Test user import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_user_import_testcase extends rlip_elis_test {

    public $testsetupdata = array(
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

    /**
     * Run before every test, set as admin user.
     */
    public function setUp() {
        global $DB;
        parent::setUp();
        $this->setAdminUser();
    }

    /**
     * Test data provider
     *
     * @return array the test data
     */
    public function dataproviderfortests() {
        $testdata = array();

        // User create - no idnumber.
        $testdata[] = array(
            'create',
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

        // Create - no username.
        $testdata[] = array(
            'create',
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

        // Create - no email.
        $testdata[] = array(
            'create',
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

        // Create - no firstname.
        $testdata[] = array(
            'create',
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

        // Create - no lastname.
        $testdata[] = array(
            'create',
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

        // Create - no country.
        $testdata[] = array(
            'create',
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

        // All required create data!.
        $testdata[] = array(
            'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'email'     => 'test@email.com',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'country'   => 'CA'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // All possible fields create data!.
        $testdata[] = array(
            'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'password'  => 'TestPassword0!',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'mi'        => 'mi',
                'email'     => 'test@email.com',
                'email2'    => 'test@email2.com',
                'address'   => '123 My Street',
                'address2'  => 'Unit 1A',
                'city'      => '*',
                'state'     => 'ON',
                'postalcode'=> 'A1B2C3',
                'country'   => 'CA',
                'phone'     => '123-555-4567',
                'phone2'    => '890-555-1234',
                'fax'       => '567-555-8901',
                'birthdate' => 'Jan/13/2011',
                'gender'    => 'M',
                'language'  => 'en',
                'transfercredits'=> '5',
                'comments'  => 'My comments',
                'notes'     => 'My notes',
                'inactive'  => 'no'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Test date format YYYY.MM.DD.
        $testdata[] = array(
            'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'birthdate' => '2000.12.25',
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
            );

        // Test date format DD-MM-YYYY.
        $testdata[] = array(
            'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'birthdate' => '25-12-2000',
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
            );

        // Test legacy action 'add'.
        $testdata[] = array(
            'add',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
            );

        // Test date format MM/DD/YYYY.
        $testdata[] = array(
            'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'birthdate' => '12/25/2000',
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
            );

        // Create - gender field - "m".
        $testdata[] = array(
          'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'gender'   => 'm'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Create - gender field - "M".
        $testdata[] = array(
          'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'gender'   => 'M'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Create - gender field - "f".
        $testdata[] = array(
          'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'gender'   => 'f'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Create - gender field - "F".
        $testdata[] = array(
          'create',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'email'     => 'test@email.com',
                'country'   => 'CA',
                'gender'   => 'F'
            ),
            NO_TEST_SETUP,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update tests - all identifying fields.
        $testdata[] = array(
            'update',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'email'     => 'test@email.com',
                'firstname' => 'testfirstnamechange1',
                'lastname'  => 'testlastnamechange1',
                'country'   => 'US'
            ),
            0, // Test setup index.
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - idnumber id only.
        $testdata[] = array(
            'update',
            array(
                'idnumber'  => 'testidnumber',
                'firstname' => 'testfirstnamechange2',
                'lastname'  => 'testlastnamechange2',
                'country'   => 'US'
            ),
            0, // Test setup index.
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - username id only.
        $testdata[] = array(
            'update',
            array(
                'username'  => 'testusername',
                'firstname' => 'testfirstnamechange3',
                'lastname'  => 'testlastnamechange3',
                'country'   => 'US'
            ),
            0, // Test setup index.
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - email id only.
        $testdata[] = array(
            'update',
            array(
                'email'     => 'test@email.com',
                'firstname' => 'testfirstnamechange4',
                'lastname'  => 'testlastnamechange4',
                'country'   => 'US'
            ),
            0, // Test setup index.
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - no id columns.
        $testdata[] = array(
            'update',
            array(
                'firstname' => 'testfirstnamechange5',
                'lastname'  => 'testlastnamechange5',
                'country'   => 'US'
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DOESNOT_EXIST
        );

        // Update - Setting all possible data fields.
        $testdata[] = array(
            'update',
            array(
                'idnumber'       => 'testidnumber',
                'username'       => 'testusername',
                'password'       => 'UpdatedPassword',
                'firstname'      => 'testfirstname_update',
                'lastname'       => 'testlastname_update',
                'mi'             => 'IM',
                'email'          => 'test@email.com',
                'email2'         => 'test_update@email2.com',
                'address'        => 'Updated 123 My Street',
                'address2'       => 'Updated Unit 1A',
                'city'           => 'Updated',
                'state'          => 'NY',
                'postalcode'     => '12345',
                'country'        => 'US',
                'phone'          => '987-654-3210',
                'phone2'         => '123-456-7890',
                'fax'            => '109-855-5765',
                'birthdate'      => 'Jul/26/1980',
                'gender'         => 'F',
                'language'       => 'en_us',
                'transfercredits'=> '10',
                'comments'       => 'Updated comments',
                'notes'          => 'Updated notes',
                'inactive'       => 'yes'
            ),
            0,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - gender field - "m".
        $testdata[] = array(
          'update',
            array(
                'idnumber' => 'testidnumber',
                'gender'   => 'm'
            ),
            0,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - gender field - "M".
        $testdata[] = array(
          'update',
            array(
                'idnumber' => 'testidnumber',
                'gender'   => 'M'
            ),
            0,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - gender field - "f".
        $testdata[] = array(
          'update',
            array(
                'idnumber' => 'testidnumber',
                'gender'   => 'f'
            ),
            0,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Update - gender field - "F".
        $testdata[] = array(
          'update',
            array(
                'idnumber' => 'testidnumber',
                'gender'   => 'F'
            ),
            0,
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Delete cases.
        // Delete - no id columns.
        $testdata[] = array(
            'delete',
            array(
                'firstname' => 'testfirstname',
                'lastname'  => 'testlastname',
                'country'   => 'CA'
            ),
            0, // Test setup index.
            ELIS_USER_EXISTS,
            MDL_USER_EXISTS
        );

        // Delete - idnumber id only.
        $testdata[] = array(
            'delete',
            array(
                'idnumber'  => 'testidnumber',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Delete - username id only.
        $testdata[] = array(
            'delete',
            array(
                'username'  => 'testusername',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Delete - email id only.
        $testdata[] = array(
            'delete',
            array(
                'email'     => 'test@email.com',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Delete - all id fields.
        $testdata[] = array(
            'delete',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'email'     => 'test@email.com',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Test legacy action 'disable'.
        $testdata[] = array(
            'disable',
            array(
                'idnumber'  => 'testidnumber',
                'username'  => 'testusername',
                'email'     => 'test@email.com',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
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
     * Field mapping function to convert IP birthdate column to birthdate DB field
     *
     * @param array  $input    The input IP data fields
     * @param string $fieldkey The array key to check for date strings
     */
    public function map_birthdate_field(&$input, $fieldkey) {
        if (isset($input[$fieldkey])) {
            $date = $input[$fieldkey];

            // Determine which case we are in.
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
                // MMM/DD/YYYY or MM/DD/YYYY format.
                list($month, $day, $year) = $parts;

                $months = array('jan', 'feb', 'mar', 'apr',
                                'may', 'jun', 'jul', 'aug',
                                'sep', 'oct', 'nov', 'dec');
                $pos = array_search(strtolower($month), $months);
                if ($pos !== false) {
                    $month = $pos + 1;
                }
            } else if ($delimiter == '-') {
                // DD-MM-YYYY format.
                list($day, $month, $year) = $parts;
            } else {
                // YYYY.MM.DD format.
                list($year, $month, $day) = $parts;
            }

            $timestamp = rlip_timestamp(0, 0, 0, $month, $day, $year);
            $input[$fieldkey] = strftime('%Y/%m/%d', $timestamp);
        }
    }

    /**
     * Class mapping function to convert IP column to Moodle user DB field
     *
     * @param mixed     $input             The input IP data fields
     * @return array    The mapped/translated data ready for DB
     */
    public function map_moodle_user($input) {
        if (array_key_exists('password', $input)) {
            unset($input['password']); // TBD: md5( ... + $CFG->salt) ...
        }
        if (array_key_exists('mi', $input)) {
            unset($input['mi']);
        }
        if (array_key_exists('email2', $input)) {
            unset($input['email2']);
        }
        if (array_key_exists('address2', $input)) {
            unset($input['address2']);
        }
        // TBD: ELIS doesn't sync phone number fields.
        if (array_key_exists('phone', $input)) {
            unset($input['phone']);
        }
        if (array_key_exists('phone2', $input)) {
            unset($input['phone2']);
        }
        if (array_key_exists('fax', $input)) {
            unset($input['fax']);
        }
        if (array_key_exists('postalcode', $input)) {
            unset($input['postalcode']);
        }
        if (array_key_exists('state', $input)) {
            unset($input['state']);
        }
        if (array_key_exists('birthdate', $input)) {
            unset($input['birthdate']);
        }
        if (array_key_exists('gender', $input)) {
            unset($input['gender']);
        }
        if (array_key_exists('language', $input)) {
            $input['lang'] = $input['language'];
            unset($input['language']);
        }
        if (array_key_exists('transfercredits', $input)) {
            unset($input['transfercredits']);
        }
        if (array_key_exists('comments', $input)) {
            unset($input['comments']);
        }
        if (array_key_exists('notes', $input)) {
            unset($input['notes']);
        }
        if (array_key_exists('inactive', $input)) {
            unset($input['inactive']);
        }

        return $input;
    }

    /**
     * Class mapping function to convert IP column to ELIS crlm_user DB field
     *
     * @param mixed $input       The input IP data fields
     * @param bool  $shouldexist Flag indicating whether the ELIS user should exist
     * @return array The mapped/translated data ready for DB
     */
    public function map_elis_user($input, $shouldexist) {
        global $DB;
        $this->map_birthdate_field($input, 'birthdate');
        $this->map_bool_field($input, 'inactive');

        if (array_key_exists('password', $input)) {
            unset($input['password']); // TBD: md5( ... + $CFG->salt) ...
        }

        $comments = null;
        $notes = null;
        // TBD: find way to test longtext fields using sql_compare_text().
        if (array_key_exists('comments', $input)) {
            $comments = substr($input['comments'], 0, 32);
            unset($input['comments']);
        }
        if (array_key_exists('notes', $input)) {
            $notes = substr($input['notes'], 0, 32);
            unset($input['notes']);
        }
        if ($shouldexist && ($comments || $notes)) {
            $where = '';
            $params = array();
            if ($comments) {
                $where .= $DB->sql_compare_text('comments').' = ?';
                $params[] = $comments;
            }
            if ($notes) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                $where .= $DB->sql_compare_text('notes').' = ?';
                $params[] = $notes;
            }
            $this->assertFalse(!$DB->record_exists_select('crlm_user', $where, $params));
        }
        return $input;
    }

    /**
     * User import test cases
     *
     * @uses $DB
     * @dataProvider dataproviderfortests
     */
    public function test_elis_user_import($action, $userdata, $setupindex, $elisexists, $mdlexists) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $CFG->siteguest = '';
        $importdata = array('action' => $action);
        foreach ($userdata as $key => $value) {
            $importdata[$key] = $value;
        }

        try {
            if ($setupindex != NO_TEST_SETUP) {
                $provider = new rlipimport_version1elis_importprovider_mockuser($this->testsetupdata[$setupindex]);
                $importplugin = new rlip_importplugin_version1elis($provider);
                @$importplugin->run();
            }

            $mdluserdata = $this->map_moodle_user($userdata);
            ob_start();
            var_dump($mdluserdata);
            $mdluserdatadump = ob_get_contents();
            ob_end_clean();
            $mdluserid = $DB->get_field('user', 'id', $mdluserdata);
            $provider = new rlipimport_version1elis_importprovider_mockuser($importdata);
            $importplugin = new rlip_importplugin_version1elis($provider);
            @$importplugin->run();
        } catch (Exception $e) {
            mtrace("\nException in test_elis_user_import(): ".$e->getMessage()."\n");
        }
        $elisuserdata = $this->map_elis_user($userdata, $elisexists);
        ob_start();
        var_dump($elisuserdata);
        $tmp = ob_get_contents();
        ob_end_clean();

        ob_start();
        var_dump($DB->get_records('crlm_user'));
        $crlmuser = ob_get_contents();
        ob_end_clean();

        ob_start();
        var_dump($DB->get_records('user'));
        $mdluser = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($elisexists, $DB->record_exists(user::TABLE, $elisuserdata),
                            "ELIS user assertion: elis_user_data; ".user::TABLE."  = $tmp ; $crlmuser");
        if ($mdlexists === true) {
            $mdluserdata['deleted'] = 0;
        } else if ($mdlexists === MDL_USER_DELETED) {
            $mdlexists = true;
            $mdluserdata = array('id' => $mdluserid, 'deleted' => 1);
        }

        $this->assertEquals($mdlexists, $DB->record_exists('user', $mdluserdata),
                            "Moodle user assertion: mdl_user_data; mdl_user = $mdluserdatadump ; $mdluser");
    }

    // Data provider for mapping yes to 1 and no to 0.
    public function field_provider() {
        return array(
            array('0', '0'),
            array('1', '1'),
            array('yes', '1'),
            array('no', '0')
        );
    }

    /**
     * @dataProvider field_provider
     * @param string The import data (0, 1, yes, no)
     * @param string The expected data (0, 1)
     */
    public function test_elis_user_inactive_field($data, $expected) {
        global $DB;

        $record = array();
        $record = $this->testsetupdata[0];
        $record['inactive'] = $data;

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $params = array(
            'idnumber' => $record['idnumber'],
            'inactive' => $expected
        );

        $this->assertEquals(true, $DB->record_exists(user::TABLE, $params));
    }
}
