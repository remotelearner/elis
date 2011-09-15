<?php
/**
 *  ELIS(TM): Enterprise Learning Intelligence Suite
 *
 *  Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package    elis
 *  @subpackage programmanagement
 *  @author     Remote-Learner.net Inc
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *  @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 */

defined('MOODLE_INTERNAL') || die();

require_once elispm::file('form/cmform.class.php');
require_once elispm::lib('lib.php');

/**
 * the form element for curriculum
 */
class cmEngineForm extends cmform {
    const LANG_FILE = 'elis_program';

    // Form html
    protected $_html = array();
    /**
     * defines items in the form
     */
    public function definition() {
        $configData = array('title');

        $this->defineActivation();
        $this->defineResults();

        $mform =& $this->_form;

        $attributes = array('rows'=>'2', 'cols'=>'40');
        $mform->addElement('textarea', 'description', get_string('curriculum_description', 'elis_program') . ':', $attributes);
        $mform->setType('description', PARAM_CLEAN);
        $mform->addHelpButton('description', 'curriculaform:curriculum_description', 'elis_program');

        $mform->addElement('text', 'reqcredits', get_string('required_credits', 'elis_program') . ':');
        $mform->setType('reqcredits', PARAM_TEXT);
        $mform->addRule('reqcredits', null, 'maxlength', 10);
        $mform->addHelpButton('reqcredits', 'curriculaform:required_credits', 'elis_program');

        $choices = range(0, 10);
        $mform->addElement('select', 'priority', get_string('priority', 'elis_program') . ':', $choices);
        $mform->addHelpButton('priority', 'curriculaform:priority', 'elis_program');

        //because moodle forms will not allow headers within headers
        $mform->addElement('header', 'editform', get_string('time_settings', 'elis_program'));

        // Time to complete
        $mform->addElement('text', 'timetocomplete', get_string('time_to_complete', 'elis_program') . ':');
        $mform->setType('timetocomplete', PARAM_TEXT);
        $mform->addRule('timetocomplete', null, 'maxlength', 64);
        $mform->addHelpButton('timetocomplete', 'curriculaform:time_to_complete', 'elis_program');

        //$mform->addElement('html', '<small>' . get_string('tips_time_to_complete', 'elis_program') . '</small><br /><br />');

        // Frequency (only display if curriculum expiration is currently enabled).
        if (!empty(elis::$config->elis_program->enable_curriculum_expiration)) {
            $mform->addElement('text', 'frequency', get_string('expiration', 'elis_program') . ':');
            $mform->setType('frequency', PARAM_TEXT);
            $mform->addRule('frequency', null, 'maxlength', 64);
            $mform->addHelpButton('frequency', 'curriculaform:expiration', 'elis_program');
        } else {
            $mform->addElement('hidden', 'frequency');
        }

        //$mform->addElement('html', '<small>' . get_string('tips_time_to_redo', 'elis_program') . '</small><br /><br />');

        $mform->addElement('static', '', '', '<small>'.get_string('tips_time_format', 'elis_program').'</small>');

        // custom fields
        $fields = field::get_for_context_level('curriculum');
        $fields = $fields ? $fields : array();

        $lastcat = null;
        $context = isset($this->_customdata['obj']) && isset($this->_customdata['obj']->id)
            ? get_context_instance(context_level_base::get_custom_context_level('curriculum', 'elis_program'), $this->_customdata['obj']->id)
            : get_context_instance(CONTEXT_SYSTEM);
        require_once(elis::plugin_file('elisfields_manual', 'custom_fields.php'));

        foreach ($fields as $rec) {
            $field = new field($rec);
            if (!isset($field->owners['manual'])) {
                continue;
            }
            if ($lastcat != $rec->categoryid) {
                $lastcat = $rec->categoryid;
                $mform->addElement('header', "category_{$lastcat}", htmlspecialchars($rec->categoryname));
            }
            manual_field_add_form_element($this, $mform, $context, $this->_customdata, $field);
        }

        $this->add_action_buttons();
    }

    /**
     * Define the activation section of the form.
     *
     * @uses $DB
     */
    protected function defineActivation() {
        global $DB;

        $grades = array(0 => get_string('engineform:class_grade', self::LANG_FILE));
        $dates  = array(1 => get_string('engineform:after_class_start', self::LANG_FILE),
                        2 => get_string('engineform:before_class_end', self::LANG_FILE),
                        3 => get_string('engineform:after_class_end', self::LANG_FILE));

        $conditions = array('courseid' => $this->_customdata['courseid']);

        $completions = $DB->get_records(coursecompletion::TABLE, $conditions);

        foreach ($completions as $completion) {
            $grades[$completion->id] = $completion->name;
        }

        // Setup form elements so we can use Moodle form validation and stuff.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'instanceid');

        $mform->addElement('checkbox', 'activate', get_string('engineform:activate_this_rule', self::LANG_FILE));
        $mform->setType('activate', PARAM_BOOL);

        $mform->addElement('radio', 'trigger', '', null, 'grade');
        $mform->addElement('radio', 'trigger', '', null, 'date');
        $mform->addElement('radio', 'trigger', '', null, 'manual');
        $mform->setType('trigger', PARAM_ALPHA);
        $mform->addRule('trigger', null, 'maxlength', 6);

        $mform->addElement('checkbox', 'locked',
                           get_string('engineform:use_locked_grades',self::LANG_FILE));
        $mform->setType('locked', PARAM_BOOL);

        $mform->addElement('text', 'days', get_string('days'));

        $mform->addElement('select', 'date', '', $dates);

        $mform->addElement('select', 'grade', '', $grades);

        // Setup an alternate html output so we can make the form user friendly.
        $html = array();
        $html[] = '<form>';
        $html[] = '<fieldset class="engineform">';
        $html[] = '<legend>'. get_string('engineform:activation_rules', self::LANG_FILE) .'</legend>';
        $html[] = '<input type="hidden" name="id" value="" />';
        $html[] = '<input type="hidden" name="instanceid" value="" />';
        $html[] = get_string('engineform:activate_this_rule', self::LANG_FILE)
                .' <input type="checkbox" name="activate" value="1" /><br />';

        $html[] = '<fieldset class="engineform">';
        $html[] = '<legend>'. get_string('engineform:event_trigger', self::LANG_FILE) .'</legend>';
        $html[] = '<input type="radio" name="trigger" value="grade" />'
                . get_string('when_student_grade_set', self::LANG_FILE)
                . ' <input type="checkbox" name="locked_only" value="1" />'
                . get_string('engineform:use_locked_grades',self::LANG_FILE) .'<br />';

        $html[] = '<input type="radio" name="trigger" value="date" />'
                . get_string('on', self::LANG_FILE);
        $html[] = ' <input type="text" name="days" value="" /> '. get_string('days');

        $html[] = '<select name="date">';
        foreach ($dates as $id => $string) {
            $html[] = "<option value=\"{$id}\">{$string}</option>";
        }
        $html[] = '</select><br />';

        $html[] = '<input type="radio" name="trigger" value="manual" />'
                . get_string('engineform:manual_trigger', self::LANG_FILE);
        $html[] = '</fieldset>';

        $html[] = '<fieldset class="engineform">';
        $html[] = '<legend>'. get_string('engineform:criterion', self::LANG_FILE) .'</legend>';
        $html[] = get_string('engineform:select_grade', self::LANG_FILE) .':<br />';

        $html[] = '<select name="grade">';
        foreach ($grades as $id => $string) {
            $html[] = "<option value=\"{$id}\">{$string}</option>";
        }
        $html[] = '</select><br />';

        $html[] = '</fieldset>';

        $html[] = '</fieldset>';

        $this->_html = $html;
    }

    /**
     * Define the results section of the form.
     */
    protected function defineResults() {
    }

    function check_unique($table, $field, $value, $id) {
        global $DB;
        return !$DB->record_exists_select($table, "$field = ? AND id <> ?", array($value, $id));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if(!empty($data['timetocomplete'])) {
            $datedelta = new datedelta($data['timetocomplete']);

            if(!$datedelta->getDateString()) {
                $errors['timetocomplete'] = get_string('error_not_timeformat', 'elis_program');
            }
        }

        if(!empty($data['frequency'])) {
            $datedelta = new datedelta($data['frequency']);

            if(!$datedelta->getDateString()) {
                $errors['frequency'] = get_string('error_not_durrationformat', 'elis_program');
            }
        }

        if (!empty($data['idnumber'])) {
            if (!$this->check_unique(curriculum::TABLE, 'idnumber', $data['idnumber'], $data['id'])) {
                $errors['idnumber'] = get_string('badidnumber', 'elis_program');
            }
        }

        return $errors;
    }

    /**
     * Overridden to specially handle timetocomplete and frequency fields.
     */
    function get_data($slashed=false) {
        $data = parent::get_data($slashed);

        if(!empty($data)) {
            $datedelta = new datedelta($data->timetocomplete);
            $data->timetocomplete = $datedelta->getDateString();

            $datedelta = new datedelta($data->frequency);
            $data->frequency = $datedelta->getDateString();
        }

        return $data;
    }

    /**
     * Display HTML
     *
     * This function works around the limitations of the moodle form forms by printing html
     * directly.  This allows for more custom designed forms.
     */
    function display_html() {
        print(implode("\n", $this->_html));
    }
}
