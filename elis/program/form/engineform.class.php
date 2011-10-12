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

require_once elispm::lib('lib.php');
require_once elispm::file('form/cmform.class.php');
require_once elispm::file('plugins/results_engine/lib.php');
require_once elispm::lib('data/track.class.php');
require_once elispm::lib('data/pmclass.class.php');
require_once elispm::lib('data/resultsengine.class.php');

/**
 * the form element for curriculum
 */
class cmEngineForm extends cmform {
    const LANG_FILE = 'pmplugins_results_engine';

    public $types = array(
        ACTION_TYPE_TRACK => 'track',
        ACTION_TYPE_CLASS => 'class',
        ACTION_TYPE_PROFILE => 'profile'
    );

    // Form html
    protected $_html = array();

    // Layout switching
    protected $_layout = 'default';

    protected $_submitted_data = '';

    protected $_typenames = array();

    /**
     * defines items in the form
     */
    public function definition() {

        global $PAGE;

        $configData = array('title');

        $PAGE->requires->css('/elis/program/plugins/results_engine/jquery-ui-1.8.16.custom.css', true);
        $PAGE->requires->js('/elis/program/plugins/results_engine/js/jquery-1.6.2.min.js', true);
        $PAGE->requires->js('/elis/program/plugins/results_engine/js/jquery-ui-1.8.16.custom.js', true);
        $PAGE->requires->js('/elis/program/plugins/results_engine/js/results_selection.js', true);

        $this->defineActivation();
        $this->defineResults();

        $submitlabel = get_string('savechanges');
        $mform =& $this->_form;

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    /**
     * Define the activation section of the form.
     *
     * @uses $CFG Get url root.
     * @uses $DB Look up course completions.
     */
    protected function defineActivation() {
        global $CFG, $DB;

        $grades = array(0 => get_string('class_grade', self::LANG_FILE));
        $dates  = array(
            RESULTS_ENGINE_AFTER_START => get_string('after_class_start', self::LANG_FILE),
            RESULTS_ENGINE_BEFORE_END  => get_string('before_class_end', self::LANG_FILE),
            RESULTS_ENGINE_AFTER_END   => get_string('after_class_end', self::LANG_FILE)
        );

        $conditions = array('courseid' => $this->_customdata['courseid']);

        $completions = $DB->get_records(coursecompletion::TABLE, $conditions);

        foreach ($completions as $completion) {
            $grades[$completion->id] = $completion->name;
        }

        $page = 'crsenginestatus';
        if ($this->_customdata['enginetype'] == 'class') {
            $page = 'clsenginestatus';
        }

        $this->_submitted_data = $this->get_submitted_data();

        $reporturl = $CFG->wwwroot .'/elis/program/index.php?s='. $page .'&amp;id='. $this->_customdata['id'];

        $activaterule    = get_string('activate_this_rule', self::LANG_FILE);
        $activationrules = get_string('activation_rules', self::LANG_FILE);
        $criterion       = get_string('criterion', self::LANG_FILE);
        $days            = get_string('days');
        $eventtrigger    = get_string('event_trigger', self::LANG_FILE);
        $executemanually = get_string('execute_manually', self::LANG_FILE);
        $gradeset        = get_string('when_student_grade_set', self::LANG_FILE);
        $on              = get_string('on', self::LANG_FILE);
        $manualtrigger   = get_string('manual_trigger', self::LANG_FILE);
        $selectgrade     = get_string('select_grade', self::LANG_FILE);
        $statusreport    = get_string('results_engine_status_report', self::LANG_FILE);
        $uselocked       = get_string('use_locked_grades',self::LANG_FILE);

        if ($this->_layout == 'custom') {
            // Setup an alternate html output so we can make the form user friendly.
            $html = array();
            $html[] = '<form>';
            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $activationrules .'</legend>';
            $html[] = '<input type="hidden" name="action" value="edit" />';
            $html[] = '<input type="hidden" name="id" value="'. $this->_customdata['id'] .'" />';
            $html[] = '<input type="hidden" name="contextid" value="'. $this->_customdata['contextid'] .'" />';
            $html[] = '<input type="hidden" name="s" value="'. $this->_customdata['s'] .'" />';
            $html[] = $activaterule .' <input type="checkbox" name="active" value="1" /><br />';

            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $eventtrigger .'</legend>';
            $html[] = '<input type="radio" name="eventtriggertype" value="grade" />'. $gradeset
                    .' <input type="checkbox" name="lockedgrade" value="1" />'. $uselocked .'<br />';

            $html[] = '<input type="radio" name="eventtriggertype" value="date" />'. $on;
            $html[] = ' <input type="text" name="days" value="" /> '. $days;

            $html[] = '<select name="triggerstartdate">';
            foreach ($dates as $id => $string) {
                $html[] = "<option value=\"{$id}\">{$string}</option>";
            }
            $html[] = '</select><br />';

            $html[] = '<input type="radio" name="eventtriggertype" value="manual" />'. $manualtrigger;
            $html[] = '</fieldset>';

            $html[] = '<fieldset class="engineform">';
            $html[] = '<legend>'. $criterion .'</legend>';
            $html[] = $selectgrade .':<br />';

            $html[] = '<select name="grade">';
            foreach ($grades as $id => $string) {
                $html[] = "<option value=\"{$id}\">{$string}</option>";
            }
            $html[] = '</select><br />';

            $html[] = '</fieldset>';

            $this->_html = $html;
        } else {
            $mform =& $this->_form;

            $mform->addElement('header', 'statusreport');
            $mform->addElement('html', '<a href="'. $reporturl .'">'. $statusreport .'</a>');
            $mform->addElement('header', 'activationrules', $activationrules);

            $mform->addElement('hidden', 'rid', $this->_customdata['rid']);
            $mform->addElement('hidden', 'contextid', $this->_customdata['contextid']);
            $active= array();
            $active[] = $mform->createElement('advcheckbox', 'active', '', $activaterule);
            $mform->addGroup($active, '', '', ' ', false);
            $mform->setType('active', PARAM_BOOL);

            $exists = array_key_exists('eventtriggertype', $this->_customdata);
            if ($exists && ($this->_customdata['eventtriggertype'] == RESULTS_ENGINE_MANUAL)) {
                $settings = 'height=200,width=500,top=0,left=0,menubar=0,location=0,scrollbars,'
                          . 'resizable,toolbar,status,directories=0,fullscreen=0,dependent';
                $url = $CFG->wwwroot .'/elis/program/plugins/results_engine/pop.php?id='. $this->_customdata['id'];
                $jsondata = array('url'=>$url,'name'=>'resultspopup','options'=>$settings);
                $jsondata = json_encode($jsondata);
                $options  = "onclick='return openpopup(null,$jsondata);'";

                $execute = array();
                $execute[] = $mform->createElement('button', 'executebutton', $executemanually, $options);
                $mform->addGroup($execute, '', '', ' ', false);
            }

            $mform->addElement('html', '<fieldset class="engineform">');
            $mform->addElement('html', '<legend>'. $eventtrigger .'</legend>');

            $grade = array();
            $grade[] = $mform->createElement('radio', 'eventtriggertype', '', $gradeset, RESULTS_ENGINE_GRADE_SET);
            $grade[] = $mform->createElement('advcheckbox', 'lockedgrade', '', $uselocked);

            $date = array();
            $date[] = $mform->createElement('radio', 'eventtriggertype', '', $on, RESULTS_ENGINE_SCHEDULED);
            $date[] = $mform->createElement('text', 'days', '', 'size="2"');
            $date[] = $mform->createElement('select', 'triggerstartdate', '', $dates);

            $manual = array();
            $manual[] = $mform->createElement('radio', 'eventtriggertype', '', $manualtrigger, RESULTS_ENGINE_MANUAL);

            $mform->setDefaults(array('eventtriggertype' => RESULTS_ENGINE_MANUAL));

            $mform->addGroup($grade, 'gradeevent', '', ' ', false);
            $mform->addGroup($date, 'dateevent', '', array(' ', ' '. $days .' '), false);
            $mform->addGroup($manual, 'manualevent', '', ' ', false);

            $mform->setType('locked', PARAM_BOOL);
            $mform->addElement('html', '</fieldset>');


            $mform->addElement('html', '<fieldset class="engineform">');
            $mform->addElement('html', '<legend>'. $criterion .'</legend>');

            $grade = array();
            $grade[] = $mform->createElement('select', 'criteriatype', '', $grades);

            $mform->addElement('html', $selectgrade .'<br />');
            $mform->addGroup($grade);

            $mform->addElement('html', '</fieldset>');

        }
    }

    /**
     * Define the results section of the form.
     */
    protected function defineResults() {

        $result          = get_string('result', self::LANG_FILE);
        $assigntotrack   = get_string('assign_to_track', self::LANG_FILE);
        $assigntoclass   = get_string('assign_to_class', self::LANG_FILE);
        $assigntoprofile = get_string('assign_to_profile', self::LANG_FILE);
        $addscorerange   = get_string('add_another_score_btn', self::LANG_FILE);

        $mform =& $this->_form;

        $cachetrack   = array();
        $cacheclass   = array();
        $cacheprofile = array();

        $cache = $this->format_cache_data();

        switch ($this->_customdata['actiontype']) {
            case ACTION_TYPE_PROFILE:
                $cacheprofile = $cache;
                break;
            case ACTION_TYPE_CLASS:
                $cacheclass   = $cache;
                break;
            case ACTION_TYPE_TRACK:
                $cachetrack   = $cache;
                break;
            default:
                break;
        }

        $rid = $this->_customdata['rid'];

        $mform->addElement('hidden', 'aid');
        $mform->addElement('hidden', 'actioncache');
        $mform->addElement('hidden', 'result_type_id', $this->_customdata['actiontype'], 'id="result_type_id"');

        $mform->addElement('html', '<fieldset class="engineform">');
//            $mform->addElement('html', '<legend>'.$result.'</lengend>');

        // Accordion implementation
        $mform->addElement('html', '<div class="engineform">');
        $mform->addElement('html', '<div id="accordion">');

        // Add track score range
        $mform->addElement('html', '<div>');
        $mform->addElement('html', '<h3>');
        $mform->addElement('html', '<a href="#">'.$assigntotrack.'</a>');
        $mform->addElement('html', '</h3>');

        // Create assign to table elements
        $mform->addElement('html', '<div>');

        $type = $this->types[ACTION_TYPE_TRACK];
        $this->setup_table_type($mform, $type, $rid, $cachetrack);

        $attributes = array('onclick' => 'pre_submit_processing("'. $type .'");');
        $mform->addElement('submit', $type .'_assignment', $addscorerange, $attributes);

        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</div>');

        // Add class score range
        $mform->addElement('html', '<div>');
        $mform->addElement('html', '<h3>');
        $mform->addElement('html', '<a href="#">'.$assigntoclass.'</a>');
        $mform->addElement('html', '</h3>');
        $mform->addElement('html', '<div>');

        $type = $this->types[ACTION_TYPE_CLASS];
        $this->setup_table_type($mform, $type, $rid, $cacheclass);

        $attributes = array('onclick' => 'pre_submit_processing("'. $type .'");');
        $mform->addElement('submit', $type .'_assignment', $addscorerange, $attributes);


        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        // Add profile field score range
        $mform->addElement('html', '<div>');
        $mform->addElement('html', '<h3>');
        $mform->addElement('html', '<a href="#">'.$assigntoprofile.'</a>');
        $mform->addElement('html', '</h3>');
        $mform->addElement('html', '<div>');

        $type = $this->types[ACTION_TYPE_PROFILE];
        $this->setup_table_type($mform, $type, $rid, $cacheprofile);

        $attributes = array('onclick' => 'pre_submit_processing("'. $type .'");');
        $mform->addElement('submit', 'profile_assignment', $addscorerange, $attributes);
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</fieldset>');

    }

    function check_unique($table, $field, $value, $id) {
        global $DB;
        return !$DB->record_exists_select($table, "$field = ? AND id <> ?", array($value, $id));
    }

    /**
     * Validation function, validates the form.
     *
     * @param array $data  The form data
     * @param array $files The form files
     * @return array An array of error strings.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $actiontype = ACTION_TYPE_TRACK;
        if (!empty($data['result_type_id'])) {
            $actiontype = $data['result_type_id'];
        }

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

        $errors = array_merge($errors, $this->validate_fold($actiontype, $data));

        return $errors;
    }

    /**
     * Validate Fold
     *
     * @param int   $actiontype The action type of the fold we're validating
     * @param array $data       The form data in array format
     * @return array An array of error strings
     */
    function validate_fold($actiontype, $data) {
        $errors = array();

        $prefix = $this->types[$actiontype];

        // Add another track score range button.  Validate and make sure all rows have been filled out
        if (! array_key_exists($prefix .'_assignment', $data)) {
            $keys = array();
            $newkeys = array();

            // Iterate through the submitted values.  Separate the newly submitted
            // data from the rest of the data.  New track data has the key
            // track_add_<number>_min/max/etc.  Existing data has the key
            // track_<number>_min/max/etc.
            foreach ($data as $key => $value) {

                if (false !== strpos($key, $prefix .'_add_')) {
                    // Extract the element unique id
                    $element_instance = explode('_', $key);
                    $element_instance = $element_instance[2];

                    $newkeys[$element_instance] = '';

                } elseif (false !== strpos($key, $prefix .'_')) {
                    // Extract the element unique id
                    $element_instance = explode('_', $key);
                    $element_instance = $element_instance[1];

                    $keys[$element_instance] = '';
                }
            }

            // Iterate over new and old data and validate whether minimum is less than maximum
            // and if a value has been selected
            foreach ($newkeys as $key => $value) {
                $keyprefix = $prefix .'_add_'. $key;
                $keymin    = $keyprefix .'_min';
                $keymax    = $keyprefix .'_max';
                $keyselect = $keyprefix .'_selected';
                $keygroup  = $keyprefix .'_group';

                // Skip over empty score ranges.
                if (empty($data[$keymin]) && empty($data[$keymax]) && empty($data[$keyselect])) {
                    continue;
                }

                if (empty($data[$keymin]) || empty($data[$keymax]) || empty($data[$keyselect])) {

                    $errors[$keygroup] = get_string('error_incomplete_score_range', self::LANG_FILE);
                }

                if ((int) $data[$keymin] >= (int) $data[$keymax]) {
                    $errors[$keygroup] = get_string('error_min_larger_than_max', self::LANG_FILE);
                }

                if (empty($data[$keyselect])) {
                    $errors[$keygroup] = get_string('error_no_'. $prefix, self::LANG_FILE);
                }
            }

            foreach ($keys as $key => $value) {
                $keyprefix = $prefix .'_'. $key;
                $keymin    = $keyprefix .'_min';
                $keymax    = $keyprefix .'_max';
                $keyselect = $keyprefix .'_selected';
                $keygroup  = $keyprefix .'_group';

                if (array_key_exists($keygroup, $errors)) {
                    unset($errors[$keygroup]);
                }

                if ((int) $data[$keymin] >= (int) $data[$keymax]) {
                        $errors[$keygroup] = get_string('error_min_larger_than_max', self::LANG_FILE);
                }

                if (empty($data[$keyselect])) {
                    $errors[$keygroup] = get_string('error_no_'. $prefix, self::LANG_FILE);
                }

            }
        }

        return $errors;
    }


    /**
     * Display HTML
     *
     * This function works around the limitations of the moodle forms by printing html
     * directly.  This allows for more custom designed forms.
     */
    function display() {
        if ($this->_layout == 'custom') {
            print(implode("\n", $this->_html));
        } else {
            parent::display();
        }
    }

    /**
     * Get label name
     *
     * @param string $type The type of label to retrieve
     * @param mixed  $id   The id to use to retrieve the label
     * @return string The name of the label.
     * @uses $DB;
     */
    function get_label_name($type, $id) {
        global $DB;

        $name = '';
        switch ($type) {
            case 'track':
                $param = array('id' => $id);
                $name = $DB->get_field(track::TABLE, 'name', $param);

                break;
            case 'class':
                $param = array('id' => $id);
                $name = $DB->get_field(pmclass::TABLE, 'idnumber', $param);

                break;
            case 'profile':
            default:
                break;
        }

        return $name;
    }

    /**
     * TODO: document
     */
    protected function setup_table_type($mform, $type, $resultsid = 0, $cache = array()) {
        global $OUTPUT;

        $scoreheader        = get_string('score', self::LANG_FILE);
        $assigntype         = get_string("assign_to_{$type}", self::LANG_FILE);
        $selecttype         = get_string("select_{$type}", self::LANG_FILE);

        $output = '';
        $i = 1;

        $attributes = array('border' => '1', 'id' => "{$type}_selection_table");
        $tablehtml = html_writer::start_tag('table', $attributes);
        $tablehtml .= html_writer::start_tag('tr');
        $tablehtml .= html_writer::tag('th', $scoreheader);
        $tablehtml .= html_writer::tag('th', $assigntype);
        $tablehtml .= html_writer::end_tag('tr');

        $funcname = "get_assign_to_{$type}_data";
        $result_action_data = $this->$funcname($resultsid);

        $mform->addElement('html', $tablehtml);

        // Add score ranges for existing table records
        $this->setup_table_type_row($mform, $type, $result_action_data, false);

        // Add score ranges for cached data
        $this->setup_table_type_row($mform, $type, $cache, true);

        // End a table row and second column
        $tablehtml = html_writer::end_tag('table');
        $mform->addElement('html', $tablehtml);

    }

    /**
     * TODO: document
     */
    protected function setup_table_type_row($mform, $type, $dataset = array(), $extrarow) {
        global $OUTPUT;

        $deletescoretype    = get_string("delete_score", self::LANG_FILE);
        $notypeselected     = get_string("no_{$type}_selected", self::LANG_FILE);
        $selecttype         = get_string("select_{$type}", self::LANG_FILE);

        $setdefault = false;
        $prefix = $type . '_';
        $cache  = 0;

        if ($extrarow) {

            $prefix = $type . '_add_';
            $empty_record = new stdClass();
            $empty_record->min = '';
            $empty_record->max = '';
            $empty_record->selected = '';
            $empty_record->name = $notypeselected;
            array_push($dataset, $empty_record);
            $cache = 1;
        }

        $i = 0;

        foreach ($dataset as $data) {

            if (isset($data->id)) {
                $i = $data->id;
            } else {
                $value = optional_param($prefix . $i .'_selected', 0, PARAM_INT);
                $data->selected = $value;
            }

            // Start a table row and column
            $tablehtml = html_writer::start_tag('tr');
            $tablehtml .= html_writer::start_tag('td');

            $mform->addElement('html', $tablehtml);

            $score = array();

            // Add minimum field
            $attributes = array('size' => 5, 'maxlength' => 5, 'value' => $data->min);
            $score[] = $mform->createElement('text', "{$prefix}{$i}_min", '', $attributes);

            // Add maximum field
            $attributes['value'] = $data->max;
            $score[] = $mform->createElement('text', "{$prefix}{$i}_max", '', $attributes);

            // Add image element
            $attributes = array('title' => $deletescoretype,
                                'alt' => $deletescoretype,
                                'src' => $OUTPUT->pix_url('delete', 'elis_program'));

            $image  = html_writer::empty_tag('img', $attributes);

            // Add link and image field (Delete link)
            $attributes     = array('onclick' => "return delete_row($i,$cache,'$type');");
            $score[] = $mform->createElement('link', 'delete', '', "#", $image, $attributes);

            // Add minimum, maximum and delete to field group
            $mform->addGroup($score, "{$prefix}{$i}_group", '', '', false);

            $key = "{$prefix}{$i}_min";
            $grouprules[$key][] = array('MIN NUMERIC LANGUAGE', 'numeric', null, 'client');
            $grouprules[$key][] = array('MIN NONZEOR LANGUAGE', 'nonzero', null, 'client');

            $key = "{$prefix}{$i}_max";
            $grouprules[$key][] = array('MAX NUMERIC LANGUAGE', 'numeric', null, 'client');
            $grouprules[$key][] = array('MAX NONZEOR LANGUAGE', 'nonzero', null, 'client');
            $mform->addGroupRule("{$prefix}{$i}_group", $grouprules);

            $tablehtml = html_writer::end_tag('td');
            $tablehtml .= html_writer::start_tag('td');
            $mform->addElement('html', $tablehtml);


            $name = $this->get_label_name($type, $data->selected);

            $attributes     = array('id' => "{$prefix}{$i}_label");

            $output         = html_writer::tag('label', $name, $attributes);

            $mform->addElement('html', $output);

            $tablehtml = html_writer::end_tag('td');
            $tablehtml .= html_writer::start_tag('td');
            $mform->addElement('html', $tablehtml);

            $url            = "form/{$type}selector.php?id={$prefix}{$i}&callback=add_selection";
            $attributes     = array('onclick' => 'show_panel("'.$url.'"); return false;');
            $output         = html_writer::link('#', $selecttype, $attributes);

            $mform->addElement('html', $output);


            $attributes     = array('id' => "{$prefix}{$i}_selected"); // Needed for javascript call back

            $mform->addElement('hidden', "{$prefix}{$i}_selected", $data->selected, $attributes);

            // This element is neccessary because when the form is validated and fails or submitted but no
            // data is saved; the form is redisplayed without access to the unsaved data.  There is magic
            // with formslib in how it retains the values.  But using a hidden element to store the value is
            // more fun
            $mform->addElement('hidden', "{$prefix}{$i}_typename",'');


            $tablehtml = html_writer::end_tag('td');
            $tablehtml .= html_writer::end_tag('tr');

            $mform->addElement('html', $tablehtml);

            $i++;
        }
    }

    protected function format_cache_data() {

        $data = array();

        if (isset($this->_customdata['cache']) and
            !empty($this->_customdata['cache'])) {

            $cachedata = explode(',', $this->_customdata['cache']);
            $x = 0;
            $i = 0;

            for($i; $i < count($cachedata); $i = $i + 3) {
                $data[$x] = new stdClass();
                $data[$x]->min = $cachedata[$i];
                $data[$x]->max = $cachedata[$i+1];
                $data[$x]->selected = $cachedata[$i+2];

                $x++;
            }
        }

        return $data;
    }

    /**
     * Get results engine actions with track data
     *
     * @param int $resultsid The result id
     * @return array The actions
     * @uses $DB
     */
    protected function get_assign_to_track_data($resultsid = 0) {
        global $DB;

        if (empty($resultsid)) {
            return array();
        }

        $sql = 'SELECT rea.id, rea.minimum AS min, rea.maximum AS max, rea.trackid AS selected, t.name '.
               'FROM {'.resultsengineaction::TABLE.'} rea '.
               'RIGHT JOIN {'.track::TABLE.'} t ON rea.trackid = t.id '.
               'WHERE rea.resultengineid = :resultsengineid ORDER BY minimum ASC';

        $params = array('resultsengineid' => $resultsid);

        $data = $DB->get_records_sql($sql, $params);

        if (empty($data)) {
            return array();
        }

        return $data;
    }

    /**
     * Get results engine actions with class data
     *
     * @param int $resultsid The result id
     * @return array The actions
     * @uses $DB
     */
    protected function get_assign_to_class_data($resultsid = 0) {
        global $DB;

        if (empty($resultsid)) {
            return array();
        }

        $sql = 'SELECT rea.id, rea.minimum AS min, rea.maximum AS max, rea.classid AS selected, cls.idnumber AS name '.
               'FROM {'.resultsengineaction::TABLE.'} rea '.
               'RIGHT JOIN {'.pmclass::TABLE.'} cls ON rea.classid = cls.id '.
               'WHERE rea.resultengineid = :resultsengineid '.
               'ORDER BY minimum ASC';

        $params = array('resultsengineid' => $resultsid);

        $data = $DB->get_records_sql($sql, $params);

        if (empty($data)) {
            return array();
        }

        return $data;
    }

    /**
     * Get results engine actions with profile data
     *
     * @param int $resultsid The result id
     * @return array The actions
     * @uses $DB
     */
    protected function get_assign_to_profile_data($resultsid = 0) {
        global $DB;

        if (empty($resultsid)) {
            return array();
        }

        $sql = 'SELECT rea.id, rea.minimum AS min, rea.maximum AS max, rea.classid AS selected, cls.idnumber AS name '.
               'FROM {'.resultsengineaction::TABLE.'} rea '.
               'RIGHT JOIN {'.pmclass::TABLE.'} cls ON rea.classid = cls.id '.
               'WHERE rea.resultengineid = :resultsengineid '.
               'ORDER BY minimum ASC';

        $params = array('resultsengineid' => $resultsid);

        $data = $DB->get_records_sql($sql, $params);

        if (empty($data)) {
            return array();
        }

        return $data;
    }

    /**
     * Definition after data
     *
     * This function will probably be used to setup the default track, class and profile result fields
     *
     * @uses $CFG
     * @uses $COURSE
     */
    public function definition_after_data() {
        $mform =& $this->_form;

        $data = $this->get_submitted_data();

        if (empty($data)) {
            return;
        }
    }
}
