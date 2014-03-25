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
 * @package    dhimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../local/eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');

// Libs.
require_once(dirname(__FILE__).'/other/rlip_mock_provider.class.php');
require_once($CFG->dirroot.'/local/datahub/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/readmemory.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/rlip_test.class.php');
require_once($CFG->dirroot.'/local/datahub/tests/other/silent_fslogger.class.php');

if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
    require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
    require_once(elispm::lib('data/user.class.php'));
}

// Handy constants for readability.
define('ELIS_USER_EXISTS', true);
define('ELIS_USER_DOESNOT_EXIST', false);
define('MDL_USER_EXISTS', true);
define('MDL_USER_DOESNOT_EXIST', false);
define('MDL_USER_DELETED', -1);
define('NO_TEST_SETUP', -1);

require_once(dirname(__FILE__).'/../version1elis.class.php');

// Must expose protected method for testing
class open_rlip_importplugin_version1elis extends rlip_importplugin_version1elis {
    /**
     * Determine userid from user import record
     *
     * @param object $record One record of import data
     * @param string $filename The import file name, used for logging
     * @param bool   $error Returned errors status, true means error, false ok
     * @param array  $errors Array of error strings (if $error == true)
     * @param string $errsuffix returned error suffix string
     * @return int|bool userid on success, false is not found
     */
    public function get_userid_for_user_actions($record, $filename, &$error, &$errors, &$errsuffix) {
        return parent::get_userid_for_user_actions($record, $filename, $error, $errors, $errsuffix);
    }
}

/**
 * Test user import.
 * @group local_datahub
 * @group dhimport_version1elis
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
        set_config('identfield_idnumber', '1', 'dhimport_version1elis');
        set_config('identfield_username', '1', 'dhimport_version1elis');
        set_config('identfield_email', '1', 'dhimport_version1elis');
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

        // Update tests - all user_ identifying fields.
        $testdata[] = array(
            'update',
            array(
                'user_idnumber' => 'testidnumber',
                'user_username' => 'testusername',
                'user_email'    => 'test@email.com',
                'firstname'     => 'testfirstnamechange1',
                'lastname'      => 'testlastnamechange1',
                'country'       => 'US'
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

        // Update - user_idnumber id only.
        $testdata[] = array(
            'update',
            array(
                'user_idnumber' => 'testidnumber',
                'firstname'     => 'testfirstnamechange2',
                'lastname'      => 'testlastnamechange2',
                'country'       => 'US'
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

        // Update - user_username id only.
        $testdata[] = array(
            'update',
            array(
                'user_username'  => 'testusername',
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

        // Update - user_email id only.
        $testdata[] = array(
            'update',
            array(
                'user_email' => 'test@email.com',
                'firstname'  => 'testfirstnamechange4',
                'lastname'   => 'testlastnamechange4',
                'country'    => 'US'
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

        // Delete - user_idnumber id only.
        $testdata[] = array(
            'delete',
            array(
                'user_idnumber'  => 'testidnumber',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Delete - username id only.
        $testdata[] = array(
            'delete',
            array(
                'username' => 'testusername',
            ),
            0, // Test setup index.
            ELIS_USER_DOESNOT_EXIST,
            MDL_USER_DELETED
        );

        // Delete - user_username id only.
        $testdata[] = array(
            'delete',
            array(
                'user_username' => 'testusername',
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

        // Delete - user_email id only.
        $testdata[] = array(
            'delete',
            array(
                'user_email' => 'test@email.com',
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

        // Delete - all user_ id fields.
        $testdata[] = array(
            'delete',
            array(
                'user_idnumber' => 'testidnumber',
                'user_username' => 'testusername',
                'user_email'    => 'test@email.com',
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
        if (array_key_exists('user_idnumber', $input)) {
            $input['idnumber'] = $input['user_idnumber'];
            unset($input['user_idnumber']);
        }
        if (array_key_exists('user_username', $input)) {
            $input['username'] = $input['user_username'];
            unset($input['user_username']);
        }
        if (array_key_exists('user_email', $input)) {
            $input['email'] = $input['user_email'];
            unset($input['user_email']);
        }
        return $input;
    }

    /**
     * Class mapping function to convert IP column to ELIS user::TABLE DB field
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

        if (array_key_exists('user_idnumber', $input)) {
            $input['idnumber'] = $input['user_idnumber'];
            unset($input['user_idnumber']);
        }
        if (array_key_exists('user_username', $input)) {
            $input['username'] = $input['user_username'];
            unset($input['user_username']);
        }
        if (array_key_exists('user_email', $input)) {
            $input['email'] = $input['user_email'];
            unset($input['user_email']);
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
            $this->assertFalse(!$DB->record_exists_select(user::TABLE, $where, $params));
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
        $file = get_plugin_directory('dhimport', 'version1elis').'/version1elis.class.php';
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
        var_dump($DB->get_records(user::TABLE));
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

        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');
        $importplugin->fslogger = new silent_fslogger(null);
        $importplugin->process_record('user', (object)$record, 'bogus');

        $params = array(
            'idnumber' => $record['idnumber'],
            'inactive' => $expected
        );

        $this->assertEquals(true, $DB->record_exists(user::TABLE, $params));
    }

    /**
     * Test main newusermail() function.
     */
    public function test_version1importnewuseremail() {
        global $CFG; // This is needed by the required files.
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1elis_fakeemail.php');
        $importplugin = new rlip_importplugin_version1elis_fakeemail();

        $testuser = new stdClass;
        $testuser->username = 'testusername';
        $testuser->idnumber = 'testidnumber';
        $testuser->firstname = 'testfirstname';
        $testuser->lastname = 'testlastname';
        $testuser->email = 'testemail@example.com';

        // Test false return when not enabled.
        set_config('newuseremailenabled', '0', 'dhimport_version1elis');
        set_config('newuseremailsubject', 'Test Subject', 'dhimport_version1elis');
        set_config('newuseremailtemplate', 'Test Body', 'dhimport_version1elis');
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);

        // Test false return when enabled but empty template.
        set_config('newuseremailenabled', '1', 'dhimport_version1elis');
        set_config('newuseremailsubject', 'Test Subject', 'dhimport_version1elis');
        set_config('newuseremailtemplate', '', 'dhimport_version1elis');
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);

        // Test false return when enabled and has template, but user has empty email.
        set_config('newuseremailenabled', '1', 'dhimport_version1elis');
        set_config('newuseremailsubject', 'Test Subject', 'dhimport_version1elis');
        set_config('newuseremailtemplate', 'Test Body', 'dhimport_version1elis');
        $testuser->email = '';
        $result = $importplugin->newuseremail($testuser);
        $this->assertFalse($result);
        $testuser->email = 'test@example.com';

        // Test success when enabled, has template text, and user has email.
        $testsubject = 'Test Subject';
        $testbody = 'Test Body';
        set_config('newuseremailenabled', '1', 'dhimport_version1elis');
        set_config('newuseremailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newuseremailtemplate', $testbody, 'dhimport_version1elis');
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
        set_config('newuseremailenabled', '1', 'dhimport_version1elis');
        set_config('newuseremailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newuseremailtemplate', $testbody, 'dhimport_version1elis');
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
        set_config('newuseremailenabled', '1', 'dhimport_version1elis');
        set_config('newuseremailsubject', $testsubject, 'dhimport_version1elis');
        set_config('newuseremailtemplate', $testbody, 'dhimport_version1elis');
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
        require_once(dirname(__FILE__).'/other/rlip_importplugin_version1elis_fakeemail.php');
        $importplugin = new rlip_importplugin_version1elis_fakeemail();

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
     * Data Provider for test_version1elis_get_userid_for_user_actions
     * @return array the test data array(array(array(array(usersdata), ...), array(inputdata), array(expected - keys: 'uid', 'error', 'errsuffix', ...)))
     */
    public function version1elis_get_userid_for_user_actions_dataprovider() {
        return array(
                array( // Test case 1: no existing user w/ std.ident. fields
                        array(),
                        array('action' => 'update', 'username' => 'rlipuser1', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " do not refer to a valid user.",
                            'errors' => array(
                                    "username value of \"rlipuser1\"",
                                    "email value of \"noreply1@remote-learner.net\"",
                                    "idnumber value of \"rlipuser1\""
                            )
                        )
                ),
                array( // Test case 2: no existing user w/ user_ prefixed id fields
                        array(),
                        array('action' => 'update', 'user_username' => 'rlipuser1', 'user_idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " do not refer to a valid user.",
                            'errors' => array(
                                    "user_username value of \"rlipuser1\"",
                                    "user_idnumber value of \"rlipuser1\""
                            )
                        )
                ),
                array( // Test case 3: existing user w/ std.ident. fields
                        array(
                                array('username' => 'rlipuser1', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'username' => 'rlipuser1', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => 1, 'error' => false)
                ),
                array( // Test case 4: existing w/ user_ prefixed id fields
                        array(
                                array('username' => 'rlipuser1', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_username' => 'rlipuser1', 'user_idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => 1, 'error' => false)
                ),
                array( // Test case 5: update matching multiple users w/ user_ prefixed id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser2', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_username' => 'rlipuser1a', 'user_idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                            'user_email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " do not refer to a valid user.",
                            'errors' => array(
                                    "user_username value of \"rlipuser1a\"",
                                    "user_email value of \"noreplyB@remote-learner.net\"",
                                    "user_idnumber value of \"rlipuser1\""
                            )
                        )
                ),
                array( // Test case 6: update matching user with others w/ user_ prefixed id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_username' => 'rlipuser1c', 'user_idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => 3, 'error' => false)
                ),
                array( // Test case 7: update matching multiple users w/ std. id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser2', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'username' => 'rlipuser1a', 'idnumber' => 'rlipuser2', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " refer to different users.",
                            'errors' => array(
                                    "username value of \"rlipuser1a\"",
                                    "email value of \"noreplyB@remote-learner.net\"",
                                    "idnumber value of \"rlipuser2\""
                            )
                        )
                ),
                array( // Test case 8: update matching user with others w/ std. id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => 3, 'error' => false)
                ),
                array( // Test case 9: update no matching user w/ std. id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser2', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'username' => 'bogus1', 'firstname' => 'Test', 'lastname' => 'User',
                            'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " does not refer to a valid user.",
                            'errors' => array(
                                    "username value of \"bogus1\""
                            )
                        )
                ),
                array( // Test case 10: update no matching w/ user_ prefix id fields
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_username' => 'bogus1', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " does not refer to a valid user.",
                            'errors' => array(
                                    "user_username value of \"bogus1\""
                            )
                        )
                ),
                array( // Test case 11: update matching ident-field not unique
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_username' => 'rlipuser1a', 'firstname' => 'Test', 'lastname' => 'User',
                            'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " refers to another user - field must be unique.",
                            'errors' => array(
                                    "email set to \"noreplyC@remote-learner.net\""
                            )
                        )
                ),
                array( // Test case 12: update matching ident-fields not unique
                        array(
                                array('username' => 'rlipuser1a', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreply1@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1b', 'idnumber' => 'rlipuser1', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                                array('username' => 'rlipuser1c', 'idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                                    'email' => 'noreplyC@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        ),
                        array('action' => 'update', 'user_idnumber' => 'rlipuser3', 'firstname' => 'Test', 'lastname' => 'User',
                            'username' => 'rlipuser1a', 'email' => 'noreplyB@remote-learner.net', 'password' => 'Test1234!', 'country' => 'CA'),
                        array('uid' => false, 'error' => true,
                            'errsuffix' => " refer to other user(s) - fields must be unique.",
                            'errors' => array(
                                    "username set to \"rlipuser1a\"",
                                    "email set to \"noreplyB@remote-learner.net\""
                            )
                        )
                )
        );
    }

    /**
     * Validate method get_userid_for_user_actions
     * @param array  $usersdata list of users w/ data to insert before test
     * @param array  $inputdata the user import record
     * @param array  $expected array of expected return param values
     * @dataProvider version1elis_get_userid_for_user_actions_dataprovider
     */
    public function test_version1elis_get_userid_for_user_actions($usersdata, $inputdata, $expected) {
        global $CFG, $DB;
        $dir = get_plugin_directory('dhimport', 'version1elis');
        require_once($dir.'/lib.php');

        // Create users for test saving ids for later comparison
        $uids = array(false);
        foreach ($usersdata as $userdata) {
            $uids[] = $DB->insert_record(user::TABLE, (object)$userdata);
        }
        $provider = new rlipimport_version1elis_importprovider_mockuser(array());
        $importplugin = new open_rlip_importplugin_version1elis($provider);
        $importplugin->mappings = rlipimport_version1elis_get_mapping('user');
        $importplugin->fslogger = $provider->get_fslogger('dhimport_version1elis', 'user');
        $error = 0;
        $errors = array();
        $errsuffix = '';
        // Cannot use ReflectionMethod for pass-by-reference params in method
        $uid = $importplugin->get_userid_for_user_actions((object)$inputdata, 'user.csv', $error, $errors, $errsuffix);
        $expecteduid = $expected['uid'];
        if ($expecteduid) {
            $expecteduid = $uids[$expecteduid];
        }
        $this->assertEquals($expecteduid, $uid);
        $this->assertEquals($expected['error'], $error);
        if (isset($expected['errsuffix'])) {
            $this->assertEquals($expected['errsuffix'], $errsuffix);
        }
        if (isset($expected['errors'])) {
            foreach ($expected['errors'] as $err) {
                $this->assertTrue(in_array($err, $errors));
            }
        }
    }
}
