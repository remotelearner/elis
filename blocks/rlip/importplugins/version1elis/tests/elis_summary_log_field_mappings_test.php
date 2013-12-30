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
require_once($CFG->dirroot.'/blocks/rlip/lib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/lib.php');
require_once(get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php');
require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1elis/tests/other/rlip_mock_provider.class.php');

/**
 * Class for validating that field mappings work correctly during the ELIS user import.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class elis_summary_log_field_mappings_testcase extends rlip_elis_test {

    /**
     * Define input data for the test methods using the following parameters:
     *  - string : entity type
     *  - array  : field mapping values
     *  - array  : input record data values
     *
     * @return array Input parameter data for the unit tests
     */
    public function custommappinganddataprovider() {
        return array(
                // 1 / 28 - user_action.
                array(
                        'user',
                        array('action' => 'custom_action'),
                        array('action' => 'create')
                ),

                // 2 / 28 - user_create.
                array(
                        'user',
                        array(
                            'idnumber'  => 'custom_idnumber',
                            'username'  => 'custom_username',
                            'firstname' => 'custom_fistname',
                            'lastname'  => 'custom_lastname',
                            'email'     => 'custom_email',
                            'country'   => 'custom_country'
                        ),
                        array(
                            'action'    => 'create',
                            'idnumber'  => 'map_test_idnumber',
                            'username'  => 'map_test_username',
                            'firstname' => 'map_test_firstname',
                            'lastname'  => 'map_test_lastname',
                            'email'     => 'map_test_email',
                            'country'   => 'map_test_country'
                        )
                ),

                // 3 / 28 - user_update_username.
                array(
                        'user',
                        array(
                            'username' => 'custom_username'
                        ),
                        array(
                            'action'   => 'update',
                            'username' => 'map_test_username'
                        )
                ),

                // 4 / 28 - user_update_email.
                array(
                        'user',
                        array(
                            'email'  => 'custom_email'
                        ),
                        array(
                            'action' => 'update',
                            'email'  => 'map_test_email'
                        )
                ),

                // 5 / 28 - user_update_idnumber.
                array(
                        'user',
                        array(
                            'idnumber' => 'custom_idnumber'
                        ),
                        array(
                            'action'   => 'update',
                            'idnumber' => 'map_test_idnumber'
                        )
                ),

                // 6 / 28 - user_delete_username.
                array(
                        'user',
                        array(
                            'username' => 'custom_username'
                        ),
                        array(
                            'action'   => 'delete',
                            'username' => 'map_test_username'
                        )
                ),

                // 7 / 28 - user_delete_email.
                array(
                        'user',
                        array(
                            'email'  => 'custom_email'
                        ),
                        array(
                            'action' => 'delete',
                            'email'  => 'map_test_email'
                        )
                ),

                // 8 / 28 - user_delete_idnumber.
                array(
                        'user',
                        array(
                            'idnumber' => 'custom_idnumber'
                        ),
                        array(
                            'action'   => 'delete',
                            'idnumber' => 'map_test_idnumber'
                        )
                ),

                // 9 / 28 - course_action.
                array(
                        'course',
                        array('action' => 'custom_action'),
                        array('action' => 'create')
                ),

                // 10 / 28 - course_create.
                array(
                        'course',
                        array(
                            'context'  => 'custom_context',
                            'name'     => 'custom_name',
                            'idnumber' => 'custom_idnumber'
                        ),
                        array(
                            'action'   => 'create',
                            'context'  => 'map_test_context',
                            'name'     => 'map_test_name',
                            'idnumber' => 'map_test_idnumber'
                        )
                ),

                // 11 / 28 - course_update.
                array(
                        'course',
                        array(
                            'context'  => 'custom_context',
                            'idnumber' => 'custom_idnumber'
                        ),
                        array(
                            'action'   => 'update',
                            'context'  => 'map_test_context',
                            'idnumber' => 'map_test_idnunber'
                        )
                ),

                // 12 / 28 - course_delete.
                array(
                        'course',
                        array(
                            'context'  => 'custom_context',
                            'idnumber' => 'custom_idnumber'
                        ),
                        array(
                            'action'   => 'delete',
                            'context'  => 'map_test_context',
                            'idnumber' => 'map_test_idnunber'
                        )
                ),

                // 13 / 28 - enrolment_action.
                array(
                        'enrolment',
                        array('action' => 'custom_action'),
                        array('action' => 'create')
                ),

                // 14 / 28 - enrolment_create_user_username.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'role'          => 'custom_role',
                            'user_username' => 'custom_user_username'
                        ),
                        array(
                            'action'        => 'create',
                            'context'       => 'user_100',
                            'role'          => 'student',
                            'user_username' => 'map_test_user_username'
                        )
                ),

                // 15 / 28 - enrolment_create_user_email.
                array(
                        'enrolment',
                        array(
                            'context'    => 'custom_context',
                            'role'       => 'custom_role',
                            'user_email' => 'custom_user_email'
                        ),
                        array(
                            'action'     => 'create',
                            'context'    => 'user_100',
                            'role'       => 'student',
                            'user_email' => 'map_test_user_email'
                        )
                ),

                // 16 / 28 - enrolment_create_user_idnumber.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'role'          => 'custom_role',
                            'user_idnumber' => 'custom_user_idnumber'
                        ),
                        array(
                            'action'        => 'create',
                            'context'       => 'user_100',
                            'role'          => 'student',
                            'user_idnumber' => 'map_test_user_idnumber'
                        )
                ),

                // 17 / 28 - enrolment_create_non_user_username.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_username' => 'custom_user_username'
                        ),
                        array(
                            'action'        => 'create',
                            'context'       => 'course_100',
                            'user_username' => 'map_test_user_username'
                        )
                ),

                // 18 / 28 - enrolment_create_non_user_email.
                array(
                        'enrolment',
                        array(
                            'context'    => 'custom_context',
                            'user_email' => 'custom_user_email'
                        ),
                        array(
                            'action'     => 'create',
                            'context'    => 'course_100',
                            'user_email' => 'map_test_user_email'
                        )
                ),

                // 19 / 28 - enrolment_create_non_user_idnumber.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_idnumber' => 'custom_user_idnumber'
                        ),
                        array(
                            'action'        => 'create',
                            'context'       => 'course_100',
                            'user_idnumber' => 'map_test_user_idnumber'
                        )
                ),

                // 20 / 28 - enrolment_update_username.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_username' => 'custom_user_username'
                        ),
                        array(
                            'action'        => 'update',
                            'context'       => 'course_100',
                            'user_username' => 'map_test_user_username'
                        )
                ),

                // 21 / 28 - enrolment_update_email.
                array(
                        'enrolment',
                        array(
                            'context'    => 'custom_context',
                            'user_email' => 'custom_user_email'
                        ),
                        array(
                            'action'     => 'update',
                            'context'    => 'course_100',
                            'user_email' => 'map_test_user_email'
                        )
                ),

                // 22 / 28 - enrolment_update_idnumber.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_idnumber' => 'custom_user_idnumber'
                        ),
                        array(
                            'action'        => 'update',
                            'context'       => 'course_100',
                            'user_idnumber' => 'map_test_user_idnumber'
                        )
                ),

                // 23 / 28 - enrolment_delete_user_username.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'role'          => 'custom_role',
                            'user_username' => 'custom_user_username'
                        ),
                        array(
                            'action'        => 'delete',
                            'context'       => 'user_100',
                            'role'          => 'student',
                            'user_username' => 'map_test_user_username'
                        )
                ),

                // 24 / 28 - enrolment_delete_user_email.
                array(
                        'enrolment',
                        array(
                            'context'    => 'custom_context',
                            'role'       => 'custom_role',
                            'user_email' => 'custom_user_email'
                        ),
                        array(
                            'action'     => 'create',
                            'context'    => 'user_100',
                            'role'       => 'student',
                            'user_email' => 'map_test_user_email'
                        )
                ),

                // 25 / 28 - enrolment_delete_user_idnumber.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'role'          => 'custom_role',
                            'user_idnumber' => 'custom_user_idnumber'
                        ),
                        array(
                            'action'        => 'delete',
                            'context'       => 'user_100',
                            'role'          => 'student',
                            'user_idnumber' => 'map_test_user_idnumber'
                        )
                ),

                // 26 / 28 - enrolment_delete_non_user_username.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_username' => 'custom_user_username'
                        ),
                        array(
                            'action'        => 'delete',
                            'context'       => 'course_100',
                            'user_username' => 'map_test_user_username'
                        )
                ),

                // 27 / 28 - enrolment_delete_non_user_email.
                array(
                        'enrolment',
                        array(
                            'context'    => 'custom_context',
                            'user_email' => 'custom_user_email'
                        ),
                        array(
                            'action'     => 'delete',
                            'context'    => 'course_100',
                            'user_email' => 'map_test_user_email'
                        )
                ),

                // 28 / 28 - enrolment_delete_non_user_idnumber.
                array(
                        'enrolment',
                        array(
                            'context'       => 'custom_context',
                            'user_idnumber' => 'custom_user_idnumber'
                        ),
                        array(
                            'action'        => 'delete',
                            'context'       => 'course_100',
                            'user_idnumber' => 'map_test_user_idnumber'
                        )
                )
        );
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
     * @return int The id of the created field
     */
    private function create_custom_field() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        require_once($CFG->dirroot.'/elis/program/accesslib.php');

        // Field category.
        $fieldcategory = new field_category(array('name' => 'testcategoryname'));
        $fieldcategory->save();

        // Custom field.
        $field = new field(array(
            'categoryid' => $fieldcategory->id,
            'shortname' => 'testfieldshortname',
            'name' => 'testfieldname',
            'datatype' => 'bool'
        ));
        $field->save();

        // Field context level assocation.
        $fieldcontextlevel = new field_contextlevel(array('fieldid' => $field->id, 'contextlevel' => CONTEXT_ELIS_USER));
        $fieldcontextlevel->save();

        return $field->id;
    }

    /**
     * Helper function that runs the user import for a sample user
     *
     * @param array $data Import data to use
     */
    private function run_user_import($data, $usedefaultdata = true) {
        global $CFG;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        $provider = new rlipimport_version1elis_importprovider_mockuser($data);

        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();
    }

    /**
     * Test custom mapping summaries.
     *
     * @dataProvider custommappinganddataprovider
     * @param string $entity  The entity type for import ('user', 'course', 'enrolment')
     * @param array  $mapping The custom field mapping
     * @param array  $data
     */
    public function test_custom_mapping_summaries($entity, $mapping, $data) {
        global $DB;

        // Setup the custom mapping values.
        rlipimport_version1elis_save_mapping($entity, array_keys($mapping), $mapping);

        // Setup the class name fo the mock import provider we need.
        $mockprovider = 'rlipimport_version1elis_importprovider_mock'.$entity;
        $provider     = new $mockprovider($data);
        $importplugin = new rlip_importplugin_version1elis($provider);
        $importplugin->run();

        // Attempt to handle all of the specific messages in as general a way as possible.

        // Testing a custom action mapping value.
        if (isset($mapping['action'])) {
            $message = 'Import file memoryfile was not processed because it is missing the following required column: ';
            $message .= $mapping['action'].'. Please fix the import file and re-upload it.';
        } else if ($entity == 'user' && $data['action'] == 'create') {
            // User create is only a subset of the required fields.
            $message = 'Import file memoryfile was not processed because one of the following columns is required but all are ';
            $message .= 'unspecified: username, email, idnumber. Please fix the import file and re-upload it.';
            $message = str_replace(array_keys($mapping), array_values($mapping), $message);
        } else if ($entity == 'course') {
            // Course actions will only initially display a message if the context field is missing.
            $message = 'Import file memoryfile was not processed because it is missing the following required column: ';
            $message .= $mapping['context'].'. Please fix the import file and re-upload it.';
        } else if ($entity == 'enrolment') {
            // Enrolment actions will only initially display a message if one of the three required fields is missing.
            // (at least one of the three is required).
            $message = 'Import file memoryfile was not processed because one of the following columns is required but all ';
            $message .= 'are unspecified: user_idnumber, user_username, user_email. Please fix the import file and re-upload it.';
            $message = str_replace(array_keys($mapping), array_values($mapping), $message);
        } else {
            // Handle generic cases below.
            if (count($mapping) > 1) {
                $message = 'Import file memoryfile was not processed because one of the following columns is required but all ';
                $message .= 'are unspecified: '.implode(', ', $mapping).'. Please fix the import file and re-upload it.';
            } else {
                // We're importing data where some fields are requires in an OR condition, so we just need to replace that.
                // Single field value with it's custom mapped representation.
                $message = 'Import file memoryfile was not processed because one of the following columns is required but ';
                $message .= 'all are unspecified: username, email, idnumber. Please fix the import file and re-upload it.';

                $message = str_replace(key($mapping), current($mapping), $message);
            }
        }
        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $this->assertTrue($DB->record_exists_select(RLIP_LOG_TABLE, $select, array('statusmessage' => $message)));
    }
}