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
 * @package    local_datahub
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../lib.php');

/**
 * Update track webservices method.
 */
class local_datahub_elis_track_update extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/curriculum.class.php'));
            require_once(elispm::lib('data/track.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets track custom fields
     * @return array An array of custom track fields
     */
    public static function get_track_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            // Get custom fields.
            $sql = 'SELECT shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_TRACK);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Gets a description of the track input object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_track_input_object_description() {
        global $DB;
        $params = array(
            'idnumber' => new external_value(PARAM_TEXT, 'Track idnumber', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Track name', VALUE_OPTIONAL),
            'description' => new external_value(PARAM_TEXT, 'Track description', VALUE_OPTIONAL),
            'startdate' => new external_value(PARAM_TEXT, 'Track startdate', VALUE_OPTIONAL),
            'enddate' => new external_value(PARAM_TEXT, 'Track enddate', VALUE_OPTIONAL),
            'autocreate' => new external_value(PARAM_BOOL, 'Track autocreate falg', VALUE_OPTIONAL)
        );

        $fields = self::get_track_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Gets a description of the track output object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_track_output_object_description() {
        global $DB;
        $params = array(
            'id' => new external_value(PARAM_INT, 'Track DB id', VALUE_REQUIRED),
            'idnumber' => new external_value(PARAM_TEXT, 'Track idnumber', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Track name', VALUE_REQUIRED),
            'curid' => new external_value(PARAM_INT, 'Track program DB id', VALUE_REQUIRED),
            'description' => new external_value(PARAM_TEXT, 'Track description', VALUE_OPTIONAL),
            'startdate' => new external_value(PARAM_INT, 'Track startdate', VALUE_OPTIONAL),
            'enddate' => new external_value(PARAM_INT, 'Track enddate', VALUE_OPTIONAL)
        );

        $fields = self::get_track_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function track_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_track_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs track update
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function track_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::track_update_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $data = (object)$data;
        $record = new stdClass;
        $record = $data;  // need all custom fields, etc.

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('dhimport_version1elis');

        // Validate
        if (empty($data->idnumber) || !($trkid = $DB->get_field(track::TABLE, 'id', array('idnumber' => $data->idnumber)))) {
            throw new data_object_exception('ws_track_update_fail_invalid_idnumber', 'local_datahub', '', $data);
        }
        unset($record->idnumber);

        // Capability checking.
        require_capability('local/elisprogram:track_edit', \local_elisprogram\context\track::instance($trkid));

        if (isset($data->startdate)) {
            $startdate = $importplugin->parse_date($data->startdate);
            if (empty($startdate)) {
                throw new data_object_exception('ws_track_update_fail_invalid_startdate', 'local_datahub', '', $data);
            } else {
                $record->startdate = $startdate;
            }
        }

        if (isset($data->enddate)) {
            $enddate = $importplugin->parse_date($data->enddate);
            if (empty($enddate)) {
                throw new data_object_exception('ws_track_update_fail_invalid_enddate', 'local_datahub', '', $data);
            } else {
                $record->enddate = $enddate;
            }
        }

        $track = new track($trkid);
        $track->load();
        $track->set_from_data($record);
        $track->save();

        // Respond.
        if (!empty($track->id)) {
            $trackrec = (array)$DB->get_record(track::TABLE, array('id' => $track->id));
            $trackobj = $track->to_array();
            // convert multi-valued custom field arrays to comma-separated listing
            $fields = self::get_track_custom_fields();
            foreach ($fields as $field) {
                // Generate name using custom field prefix.
                $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

                if ($field->multivalued && isset($trackobj[$fullfieldname]) && is_array($trackobj[$fullfieldname])) {
                    $trackobj[$fullfieldname] = implode(',', $trackobj[$fullfieldname]);
                }
            }
            return array(
                'messagecode' => get_string('ws_track_update_success_code', 'local_datahub'),
                'message' => get_string('ws_track_update_success_msg', 'local_datahub'),
                'record' => array_merge($trackrec, $trackobj)
            );
        } else {
            throw new data_object_exception('ws_track_update_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function track_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_track_output_object_description())
                )
        );
    }
}
