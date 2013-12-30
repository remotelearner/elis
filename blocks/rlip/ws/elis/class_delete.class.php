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
 * @package    block_rlip
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/pmclass.class.php'));
require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');

/**
 * Delete class webservices method.
 */
class block_rldh_elis_class_delete extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function class_delete_parameters() {
        $params = array(
            'data' => new external_single_structure(array(
                'idnumber' => new external_value(PARAM_TEXT, 'Class idnumber', VALUE_REQUIRED),
            ))
        );
        return new external_function_parameters($params);
    }

    /**
     * Performs class deletion
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error deleting the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function class_delete(array $data) {
        global $USER, $DB;

        // Parameter validation.
        $params = self::validate_parameters(self::class_delete_parameters(), array('data' => $data));

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        // Get the class.
        $clsid = $DB->get_field(pmclass::TABLE, 'id', array('idnumber' => $data['idnumber']));

        if (empty($clsid)) {
            throw new data_object_exception('ws_class_delete_fail_invalid_idnumber', 'block_rlip', '', $data);
        }

        // Capability checking.
        require_capability('elis/program:class_delete', context_elis_class::instance($clsid));

        // Delete the class.
        $pmclass = new pmclass($clsid);
        $pmclass->delete();

        // Verify class deleted & respond.
        if (!$DB->record_exists(pmclass::TABLE, array('id' => $clsid))) {
            return array(
                'messagecode' => get_string('ws_class_delete_success_code', 'block_rlip'),
                'message' => get_string('ws_class_delete_success_msg', 'block_rlip'),
            );
        } else {
            throw new data_object_exception('ws_class_delete_fail', 'block_rlip');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function class_delete_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                )
        );
    }
}
