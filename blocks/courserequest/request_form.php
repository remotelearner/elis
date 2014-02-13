<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    block_courserequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/lib/formslib.php');
require_once($CFG->dirroot.'/local/eliscore/lib/table.class.php');
require_once($CFG->dirroot.'/local/elisprogram/accesslib.php');
require_once($CFG->dirroot.'/local/elisprogram/lib/data/course.class.php');
require_once($CFG->dirroot.'/local/elisprogram/lib/data/pmclass.class.php');
require_once($CFG->dirroot.'/local/elisprogram/pmclasspage.class.php');

/**
 * Form showing list of classes the current user has access to
 */
class current_form extends moodleform {
    public function definition() {
        $mform = &$this->_form;

        // ensure the user is allowed to view the classes being listed
        $user_classes = pmclass_get_listing('crsname', 'ASC', 0, 11, '', '', 0, false, pmclasspage::get_contexts('local/elisprogram:class_view'));

        if (empty($user_classes)) {
            // show a default label
            $mform->addElement('static', 'static_name', get_string('no_courses', 'block_courserequest'));
        } else {
            // we have classes to list
            $mform->addElement('static', 'static_title', get_string('current_classes', 'block_courserequest'));

            foreach ($user_classes as $uc) {
                $context = \local_elisprogram\context\pmclass::instance($uc->id);

                // this points to the page that displays the specific class
                $target = new pmclasspage(array('id'     => $uc->id,
                                                'action' => 'view'));
                $mform->addElement('static', 'course' . $uc->id, '', "<a href=\"{$target->url}\">$uc->idnumber</a>");
            }

            // use a "More" link if there are more than ten classes
            if (count($user_classes) > 10) {
                $target = new pmclasspage(array()); // TBV
                $more = "<a href=\"{$target->url}\">".get_string('moreclasses', 'block_courserequest').'</a>';
                $mform->addElement('static', '' . $uc->id, '', $more);
            }
        }

        // button for creating a new request
        $mform->addElement('submit', 'add', get_string('request', 'block_courserequest'));
    }
}

class create_form extends moodleform {

    /**
     * Adds fields to this form which are relevant to the course, either
     * new or old, that a class is being requested for, including any associated
     * validation rules
     */
    protected function add_course_info() {
        global $PAGE, $USER;
        $PAGE->requires->js('/blocks/courserequest/forms.js');
        $mform = &$this->_form;

        $mform->addElement('header', 'courseheader', get_string('createcourseheader', 'block_courserequest'));

        $courses = array(0 => get_string('newcourse', 'block_courserequest'));

        /*
         * Get all courses the current user has access to:
         * Access is allowed if you have the correct capability at the system, curriculum, or course level
         */
        $course_contexts = get_contexts_by_capability_for_user('course', 'block/courserequest:request', $USER->id);

        // this will actually handle all cases because it handles curricula explicitly
        $eliscourses = course_get_listing('crs.name', 'ASC', 0, 0, '', '', $course_contexts);
        $eliscourses = $eliscourses ? $eliscourses : array();
        foreach ($eliscourses as $course) {
            $courses[$course->id] = '(' . $course->idnumber . ') ' . $course->name;
        }

        $mform->addElement('select', 'courseid', get_string('course', 'block_courserequest'), $courses, array('onchange' => 'handle_course_change()'));

        // If this user has approval permission then let's give them the class id field so we can skip the approval page
        $syscontext = context_system::instance();
        if (has_capability('block/courserequest:approve', $syscontext)) {
            // indicate that course idnumber is required
            $label = '<span class="required">'.get_string('courseidnumber', 'block_courserequest').'*</span>';
            $mform->addElement('text', 'crsidnumber', $label);
            $mform->addRule('crsidnumber', null, 'maxlength', 100);
            $mform->setType('crsidnumber', PARAM_TEXT);
            $mform->disabledIf('crsidnumber', 'courseid', 'gt', '0');
        }

        // indicate that course name is required
        $label = '<span class="required">'.get_string('title', 'block_courserequest').'*</span>';
        $mform->addElement('text', 'title', $label);
        $mform->setType('title', PARAM_RAW);
        // only needed for new courses
        $mform->disabledIf('title', 'courseid', 'gt', '0');

        $usecoursefields = get_config('block_courserequest', 'use_course_fields');
        if (!empty($usecoursefields)) {
            // add course-level custom fields to the interface
            $this->add_custom_fields('course', true);
        }
    }

    /**
     * Adds fields to this form that are related to the class being created, including
     * any associated validation rules
     * @uses  $CFG
     * @uses  $DB
     */
    protected function add_class_info() {
        global $CFG, $DB;

        $mform = &$this->_form;

        // determine if the current user can approve requests
        $syscontext = context_system::instance();
        $can_approve = has_capability('block/courserequest:approve', $syscontext);
        $config = get_config('block_courserequest');

        if ($can_approve) {
            require_once($CFG->dirroot .'/local/elisprogram/lib/data/coursetemplate.class.php');
            // section header, since we know the section will be displayed
            $mform->addElement('header', 'classheader', get_string('createclassheader', 'block_courserequest'));

            // indicate that class idnumber is required
            $label = '<span class="required">'.get_string('classidnumber', 'block_courserequest').'*</span>';
            $mform->addElement('text', 'clsidnumber', $label);
            $mform->addRule('clsidnumber', null, 'maxlength', 100);
            $mform->setType('clsidnumber', PARAM_TEXT);
            if (empty($config->create_class_with_course)) {
                // disable class fields if creating a new course and
                // create_class_with_course is unset
                $mform->disabledIf('clsidnumber', 'courseid', 'eq', '0');
            }

            // checkbox for whether to use the course template
            $mform->addElement('checkbox', 'usecoursetemplate', get_string('use_course_template', 'block_courserequest'));

            // new course options should disable the use of course templates
            $mform->disabledIf('usecoursetemplate', 'courseid', 'eq', '0');

            // query to retrieve courses without valid templates
            $course_without_template_sql =
                'SELECT id FROM {'. course::TABLE .'}
                 WHERE id NOT IN (
                     SELECT courseid FROM {'. coursetemplate::TABLE ."}
                     WHERE location != ''
                 )";

            // go through courses without templates and force the checkbox to be disabled when they are selected
            if ($courses_without_templates = $DB->get_records_sql($course_without_template_sql)) {
                foreach ($courses_without_templates as $course_without_template) {
                    $mform->disabledIf('usecoursetemplate', 'courseid', 'eq', "{$course_without_template->id}");
                }
            }

            // use config setting to set the default value (works only for self-approval)
            if (!empty($config->use_template_by_default)) {
                $mform->setDefault('usecoursetemplate', $config->use_template_by_default);
            }
        }

        // determine if class fields are enabled
        if (!empty($config->use_class_fields)) {
            // determine if we still need to display the class header
            $section_header = null;
            if (!$can_approve) {
                $section_header = get_string('createclassheader', 'block_courserequest');
            }

            // add class-level custom fields to the interface
            $this->add_custom_fields('class', false, $section_header);
        }
    }

    /**
     * Adds fields to this form that are relevant to the user making the request
     */
    protected function add_user_info() {
        global $USER;

        $mform = &$this->_form;

        $mform->addElement('header', 'userheader', get_string('createuserheader', 'block_courserequest'));

        // get requester's information
        $mform->addElement('text', 'first', get_string('firstname', 'block_courserequest'));
        $mform->addRule('first', get_string('required'), 'required', NULL, 'server');
        $mform->setDefault('first', $USER->firstname);
        $mform->setType('first', PARAM_TEXT);
        $mform->addElement('text', 'last', get_string('lastname', 'block_courserequest'));
        $mform->addRule('last', get_string('required'), 'required', NULL, 'server');
        $mform->setDefault('last', $USER->lastname);
        $mform->setType('last', PARAM_TEXT);
        $mform->addElement('text', 'email', get_string('email', 'block_courserequest'));
        $mform->addRule('email', get_string('required'), 'required', NULL, 'server');
        $mform->setDefault('email', $USER->email);
        $mform->setType('email', PARAM_TEXT);
    }

    /**
     * Adds custom fields to this form for a particular context level
     *
     * @param  string   $contextlevel_name  The name of the context level we are looking
     *                                      for fields at, such as course or class
     * @param  boolean  $new_course_only    If true, only enable the appropriate elements
     *                                      if the course field represents a new course
     * @param  string   $section_header     If not null, display the provided text as a header
     *                                      before displaying the UI for the first field, if found
     * @uses   $CFG
     * @uses   $DB
     */
    protected function add_custom_fields($contextlevel_name, $new_course_only = false, $section_header = null) {
        global $CFG, $DB;
        $mform = &$this->_form;

        $contextlevel = \local_eliscore\context\helper::get_level_from_name($contextlevel_name);

        // track whether we have already displayed a section header
        $header_displayed = false;

        // get custom fields that can be selected
        $fields = $DB->get_records('block_courserequest_fields', array('contextlevel' => $contextlevel));
        $fields = $fields ? $fields : array();
        foreach ($fields as $reqfield) {
            $field = new field($reqfield->fieldid);

            if (!$field->id || !isset($field->owners['manual'])) {
                // skip nonexistent fields, or fields without manual editing
                continue;
            }

            $manual = new field_owner($field->owners['manual']);

            if (empty($manual->param_edit_capability)) {

                // display section header?
                if ($section_header !== null && !$header_displayed) {
                    // header specified, and hasn't been displayed yet
                    $mform->addElement('header', $contextlevel_name.'header', $section_header);
                    // prevent re-displaying for this section
                    $header_displayed = true;
                }

                // add required display if applicable
                $params = unserialize($manual->params);
                if (!empty($params['required'])) {
                    $field->name = '<span class="required">'.$field->name.'*</span>';
                }

                $control = $manual->param_control;
                require_once($CFG->dirroot."/local/eliscore/fields/manual/field_controls/{$control}.php");
                call_user_func("{$control}_control_display", $this, $mform, null, $field); // *TBV*

                $element_name = "field_{$field->shortname}";

                if ($new_course_only) {
                    // disable for existing courses
                    // non-zero implies existing course
                    $mform->disabledIf($element_name, 'courseid', 'gt', '0');
                } else {
                    $createclasswithcourse = get_config('block_courserequest', 'create_class_with_course');
                    if (!empty($createclasswithcourse)) {
                        // disable class fields if creating a new course and
                        // create_class_with_course is unset
                        $mform->disabledIf($element_name, 'courseid', 'eq', '0');
                    }
                }

                $manual_params = unserialize($manual->params);

                $required_flag = empty($manual_params['required']) ? 0 : 1;
                $mform->addElement('hidden','fieldisrequired_'.$element_name.'_'.$contextlevel_name, $required_flag);
                $mform->setType('fieldisrequired_'.$element_name.'_'.$contextlevel_name, PARAM_INT);

            }
        }
    }

    /**
     * Main form definition method, which autments the
     * elements on the form represented by this object
     */
    public function definition() {
        $this->add_course_info();
        $this->add_class_info();
        $this->add_user_info();
        $this->add_action_buttons();
    }

    function validation($approval, $files) {
        global $CFG, $DB;

        $errors = array();

        if ($approval['courseid'] == 0) {
            if (empty($approval['title'])) {
                $errors['title'] = 'Required';
            }
        }

        $syscontext = context_system::instance();

        if (has_capability('block/courserequest:approve', $syscontext) && $approval['courseid'] == 0) {
            if ($approval['crsidnumber'] == '') {
                $errors['crsidnumber'] = 'Required';
            } else if ($DB->record_exists(course::TABLE, array('idnumber' => $approval['crsidnumber']))) {
                $errors['crsidnumber'] = get_string('idnumber_already_used', 'local_elisprogram');
            }
        }

        // determine if the current user can approve requests
        $can_approve = has_capability('block/courserequest:approve', $syscontext);

        if ($can_approve) {
            $createclasswithcourse = get_config('block_courserequest', 'create_class_with_course');
            if ($approval['courseid'] || !empty($createclasswithcourse)) {
                if (empty($approval['clsidnumber'])) {
                    $errors['clsidnumber'] = 'Required';
                } else if ($DB->record_exists(pmclass::TABLE, array('idnumber' => $approval['clsidnumber']))) {
                    $errors['clsidnumber'] = get_string('idnumber_already_used', 'local_elisprogram');
                }
            }
        }

        // Check for required custom fields
        foreach ($approval as $data_key => $data_value) {
            $key_array = explode('_', $data_key);
            $req_check = !empty($key_array[0]) ? $key_array[0] : null;
            if ($req_check == 'fieldisrequired' && !empty($data_value)) {
                $check_var = '';
                for ($i = 1; $i < count($key_array) - 1; $i++) {
                    $check_var .= $key_array[$i] .'_';
                }
                $check_context = $key_array[$i];
                $check_var = rtrim($check_var, '_');
                if (($check_context == 'course' && empty($approval['courseid'])) ||
                    ($check_context == 'class')) {
                    if (isset($approval[$check_var]) && $approval[$check_var] == '') {
                        $errors[$check_var] = 'Required';
                    }
                }
            }
        }

        return $errors;
    }

}

class define_request_form {
    private $action_url;

    /**
     * set the form's action url on creation
     *
     * @param string $url the form's action url
     */
    public function __construct($url) {
        $this->action_url = $url;
    }

    /**
     * Displays the portion of the field editing form specific to a context level
     *
     * @param  string  $contextlevel_name  The description of the context level, such
     *                                     as 'course' or 'class'
     * @param  string  $field_header       The display string used for the header above
     *                                     the field value entry elements
     * @param  string  $button_text        Text to display on the add button
     * @uses   $CFG
     * @uses   $DB
     */
    private function display_for_context($contextlevel_name, $field_header, $button_text) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/elisprogram/lib/contexts.php');

        $fields = field::get_for_context_level($contextlevel_name)->to_array();
        $fields = $fields ? $fields : array();

        // only retrieve fields for the specified context level
        $contextlevel = \local_eliscore\context\helper::get_level_from_name($contextlevel_name);
        $questions = $DB->get_records('block_courserequest_fields', array('contextlevel' => $contextlevel));

        print '<fieldset class="hidden">';
        if (!empty($questions)) {
            print '<table cellpadding="2">';
            print '<tr align="right">';
            // print '<th><span style="margin-right:10px;">'.get_string('name_on_form', 'block_courserequest').'</span></th>';
            print '<th><span style="margin-right:10px;">'.get_string('existing_fields', 'block_courserequest').'</span></th>';
            print '<th><span style="margin-right:10px;">'. $field_header .'</span></th>';
            print '</tr>';

            foreach ($questions as $question) {
                print '<tr>';

                if ($question->fieldid) {
                    $field = new field($question->fieldid);
                    try {
                        $fieldname = $field->name;
                    } catch (dml_missing_record_exception $ex) {
                        continue; // ELIS-4014: custom field deleted!
                    }
                } else {
                    $fieldname = 'select a field';
                }

                // print "<td><input type="text" name=\"custom_name[]\" value=\"$value\" /></td>";
                print "<td>$fieldname</td>";

                print "<td><select name=\"field[{$question->id}]\" />";

                // print '<option value="none">none</option>';

                foreach ($fields as $f) {
                    if ($f->id == $question->fieldid) {
                        print '<option value="'. $f->id .'" selected="true">'. $f->name .'</option>';
                        $selected = true;
                    } else {
                        print '<option value="'. $f->id .'">'. $f->name .'</option>';
                    }
                }

                print "</select></td>";

                print "<td><input type=\"submit\" name=\"delete[{$question->id}]\" value=\"".
                        get_string('delete', 'block_courserequest').'" /></td>';
                print '</tr>';
            }

            print '</table>';
        }

        $add_element_name = "add_field_{$contextlevel}";
        print '<div style="margin-top:5px"><input type="submit" name="'. $add_element_name .'" value="'. $button_text .'" /></div>';

        print '</fieldset>';
    }

    /**
     * Display the the form
     * @uses   $CFG
     */
    public function display() {
        // use one form for everything because submit buttons need to
        // interact with fields from various contexts
        print '<form class="mform" method="post" action="' . $this->action_url . '">';

        // display fields for the course context
        $usecoursefields = get_config('block_courserequest', 'use_course_fields');
        if (!empty($usecoursefields)) {
            // settings allow this type of configuration
            $field_header = get_string('action_fields_course', 'block_courserequest');
            $button_text = get_string('add_field_course', 'block_courserequest');
            $this->display_for_context('course', $field_header, $button_text);
        }

        // display fields for the class context
        $useclassfields = get_config('block_courserequest', 'use_class_fields');
        if (!empty($useclassfields)) {
            // settings allow this type of configuration
            $field_header = get_string('action_fields_class', 'block_courserequest');
            $button_text = get_string('add_field_class', 'block_courserequest');
            $this->display_for_context('class', $field_header, $button_text);
        }

        // submit buttons
        print '<div style="margin-top:10px">';
        print '<input type="submit" name="update" value="'.get_string('update', 'block_courserequest').'" />';
        print '<input type="submit" name="exit" value="'.get_string('exit', 'block_courserequest').'" />';
        print '</div>';

        print '</form>';
    }
}
