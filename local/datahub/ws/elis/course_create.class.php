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

/**
 * Create course webservices method.
 */
class local_datahub_elis_course_create extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/course.class.php'));
            require_once(elispm::lib('data/coursetemplate.class.php'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets course custom fields
     * @return array An array of custom course fields
     */
    public static function get_course_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            // Get custom fields.
            $sql = 'SELECT shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_COURSE);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Gets a description of the course object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a course record in webservice terms.
     */
    public static function get_course_object_description() {
        global $DB;

        $params = array(
            'idnumber' => new external_value(PARAM_TEXT, 'The ELIS course idnumber.', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The ELIS course name.', VALUE_REQUIRED),
            'code' => new external_value(PARAM_TEXT, 'The ELIS course code.', VALUE_OPTIONAL),
            'syllabus' => new external_value(PARAM_TEXT, 'The ELIS course description.', VALUE_OPTIONAL),
            'lengthdescription' => new external_value(PARAM_TEXT, 'The ELIS course length description. For example, days, weeks, months, semesters, etc.', VALUE_OPTIONAL),
            'length' => new external_value(PARAM_INT, 'The ELIS course duration.', VALUE_OPTIONAL),
            'credits' => new external_value(PARAM_FLOAT, 'The credits given for completing the ELIS course.', VALUE_OPTIONAL),
            'completion_grade' => new external_value(PARAM_INT, 'A number from 0 to 100 to indicate the grade needed to complete the ELIS course.', VALUE_OPTIONAL),
            'cost' => new external_value(PARAM_TEXT, 'The cost of the ELIS course.', VALUE_OPTIONAL),
            'version' => new external_value(PARAM_TEXT, 'The ELIS course version.', VALUE_OPTIONAL),
            'assignment' => new external_value(PARAM_TEXT, 'The ELIS program this ELIS course is assigned to.', VALUE_OPTIONAL),
            'link' => new external_value(PARAM_TEXT, 'The shortname of the Moodle course that should be used as a template.', VALUE_OPTIONAL),
        );

        $fields = self::get_course_custom_fields();
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
    public static function course_create_parameters() {
        $params = array('data' => new external_single_structure(static::get_course_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs course creation
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function course_create(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_datahub');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::course_create_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        require_capability('local/elisprogram:course_create', context_system::instance());

        $data = (object)$data;

        // Set default syllabus value if required.
        if (!isset($data->syllabus)) {
            $data->syllabus = '';
        }

        // Validate credits.
        if (isset($data->credits) && !(is_numeric($data->credits) && $data->credits >= 0)) {
            throw new data_object_exception('ws_course_create_fail_invalid_credits', 'local_datahub', '', $data);
        } else if (!isset($data->credits)) {
            $data->credits = 0;
        }

        // Validate completion grade.
        if (isset($data->completion_grade) && !(is_numeric($data->completion_grade) && $data->completion_grade >= 0 && $data->completion_grade <= 100)) {
            throw new data_object_exception('ws_course_create_fail_invalid_completion_grade', 'local_datahub', '', $data);
        }

        // Handle assignment to program.
        if (isset($data->assignment) && !empty($data->assignment)) {
            $curriculumid = $DB->get_field(curriculum::TABLE, 'id', array('idnumber' => $data->assignment));
            if ($curriculumid) {
                $data->curriculum = array($curriculumid);
            } else {
                throw new data_object_exception('ws_course_create_fail_invalid_assignment', 'local_datahub', '', $data);
            }
        }

        // Handle linking to Moodle course.
        if (isset($data->link) && !empty($data->link)) {
            $moodlecourseid = $DB->get_field('course', 'id', array('shortname' => $data->link));
            if ($moodlecourseid) {
                $data->location = $moodlecourseid;
                $data->templateclass = 'moodlecourseurl';
            } else {
                throw new data_object_exception('ws_course_create_fail_invalid_link', 'local_datahub', '', $data);
            }
        }

        $course = new course;
        $course->set_from_data($data);
        $course->save();

        // Respond.
        if (!empty($course->id)) {
            $courserec = (array)$DB->get_record(course::TABLE, array('id' => $course->id));
            $courseobj = $course->to_array();
            // convert multi-valued custom field arrays to comma-separated listing
            $fields = self::get_course_custom_fields();
            foreach ($fields as $field) {
                // Generate name using custom field prefix.
                $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

                if ($field->multivalued && isset($courseobj[$fullfieldname]) && is_array($courseobj[$fullfieldname])) {
                    $courseobj[$fullfieldname] = implode(',', $courseobj[$fullfieldname]);
                }
            }
            return array(
                'messagecode' => get_string('ws_course_create_success_code', 'local_datahub'),
                'message' => get_string('ws_course_create_success_msg', 'local_datahub'),
                'record' => array_merge($courserec, $courseobj),
            );
        } else {
            throw new data_object_exception('ws_course_create_fail', 'local_datahub');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function course_create_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_course_object_description())
                )
        );
    }
}
