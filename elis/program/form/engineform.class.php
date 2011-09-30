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

    // Form html
    protected $_html = array();

    // Layout switching
    protected $_layout = 'default';

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

        // ** may not need actiontype **
        $actiontype = 0;
        if (isset($this->_customdata['actiontype']) and
            !empty($this->_customdata['actiontype']) ) {

           $actiontype = $this->_customdata['actiontype'];
        }

        $cache = $this->format_cache_data();
        $resultengid = $this->_customdata['rid'];


        $mform->addElement('hidden', 'actiontype', $actiontype);
        $mform->addElement('hidden', 'actioncache');



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

        if (TRACK_ACTION_TYPE == $actiontype) {
            $this->setup_table_type($mform, 'track', $resultengid, $cache);
        } else {
            $this->setup_table_type($mform, 'track', $resultengid, array());
        }

        $attributes = array('onclick' => 'pre_submit_processing("track","'.TRACK_ACTION_TYPE.'");');
        //$mform->registerNoSubmitButton('trk_assignment');
        $mform->addElement('submit', 'trk_assignment', $addscorerange, $attributes);

        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</div>');

        // Add class score range
        $mform->addElement('html', '<div>');
        $mform->addElement('html', '<h3>');
        $mform->addElement('html', '<a href="#">'.$assigntoclass.'</a>');
        $mform->addElement('html', '</h3>');
        $mform->addElement('html', '<div>');

        if (CLASS_ACTION_TYPE == $actiontype) {
            $this->setup_table_type($mform, 'class', $resultengid, $cache);
        } else {
            $this->setup_table_type($mform, 'class', $resultengid, array());
        }

        $attributes = array('onclick' => 'pre_submit_processing("class","'.CLASS_ACTION_TYPE.'");');
        //$mform->registerNoSubmitButton('cls_assignment');
        $mform->addElement('submit', 'cls_assignment', $addscorerange, $attributes);


        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        // Add profile field score range
        $mform->addElement('html', '<div>');
        $mform->addElement('html', '<h3>');
        $mform->addElement('html', '<a href="#">'.$assigntoprofile.'</a>');
        $mform->addElement('html', '</h3>');
        $mform->addElement('html', '<div>');

        if (PROFILE_ACTION_TYPE == $actiontype) {
//            $this->setup_table_type($mform, 'class', $resultengid, $cache);
        } else {
//            $this->setup_table_type($mform, 'class', $resultengid, array());
        }

//            $mform->addElement('html', '<div>Some more content in div');

        $mform->addElement('submit', 'pro_assignment', $addscorerange);
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</fieldset>');

        // TESTING
        //print_object($mform->_elementIndex);
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

        // Add another track score range button.  Do not validate form on this button click
        if (array_key_exists('trk_assignment', $data)) {
            // ignore
        } else {

            $trackkeys = array();
            $newtrackkeys = array();

            // Iterate through the submitted values.  Separate the newly submitted
            // data from the rest of the data.  New track data has the key
            // track_add_<number>_min/max/etc.  Existing data has the key
            // track_<number>_min/max/etc.
            foreach ($data as $key => $value) {

                if (false !== strpos($key, 'track_add_')) {
                    // Extract the element unique id
                    $element_instance = explode('_', $key);
                    $element_instance = $element_instance[2];

                    $newtrackkeys[$element_instance] = '';

                } elseif (false !== strpos($key, 'track_')) {
                    // Extract the element unique id
                    $element_instance = explode('_', $key);
                    $element_instance = $element_instance[1];

                    $trackkeys[$element_instance] = '';
                }
            }

            // Iterate over new and old data and validate whether
            // minimum is less than maximum and if a track id has bee
            // selected
            foreach ($newtrackkeys as $key => $value) {

                // Skip over empty score ranges.
                if ( empty($data["track_add_{$key}_min"]) and
                     empty($data["track_add_{$key}_max"]) and
                     empty($data["track_add_{$key}_selected"]) ) {

                    continue;
                }

                if ( empty($data["track_add_{$key}_min"]) or
                     empty($data["track_add_{$key}_max"]) or
                     empty($data["track_add_{$key}_selected"]) ) {

                        $errors["track_add_{$key}_group"] = 'INCOMPLETE SCORE RANGE ADD LANGUAGE STRING';
                }

                if ((int) $data["track_add_{$key}_min"] >=
                    (int) $data["track_add_{$key}_max"]) {

                    $errors["track_add_{$key}_group"] = 'MIN >= MAX ADD LANGUAGE STRING';
                }

                if (empty($data["track_add_{$key}_selected"])) {
                    $errors["track_add_{$key}_group"] = 'No track selected ADD LANGUAGE STRING';
                }
            }

            foreach ($trackkeys as $key => $value) {
                if ((int) $data["track_{$key}_min"] >=
                    (int) $data["track_{$key}_max"]) {
                        $errors["track_{$key}_group"] = 'MIN >= MAX ADD LANGUAGE STRING';
                }

                if (empty($data["track_{$key}_selected"])) {
                    $errors["track_{$key}_group"] = 'No track selected ADD LANGUAGE STRING';
                }

            }
        }

        if (array_key_exists('cls_assignment', $data)) {
        }

        if (array_key_exists('profile_assignment', $data)) {
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
        global $OUTPUT, $DB;

        $deletescoretype    = get_string("delete_score", self::LANG_FILE);
        $notypeselected     = get_string("no_{$type}_selected", self::LANG_FILE);
        $selecttype         = get_string("select_{$type}", self::LANG_FILE);

        $setdefault = false;
        $prefix = $type . '_';

        if ($extrarow) {

            $prefix = $type . '_add_';
            $empty_record = new stdClass();
            $empty_record->min = '';
            $empty_record->max = '';
            $empty_record->selected = '';
            $empty_record->name = $notypeselected;
            array_push($dataset, $empty_record);

        }

        $i = 0;


        foreach ($dataset as $data) {

            if (isset($data->id)) {
                $i = $data->id;
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
            $score[] = $mform->createElement('link', 'delete', '', '#', $image);

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


            // Retrieve the track/class name
            $name = '';
            switch ($type) {
                case 'track':
                    $param = array('id' => $data->selected);
                    $name = $DB->get_field(track::TABLE, 'name', $param);
                    break;
                case 'class':
                    $param = array('id' => $data->selected);
                    $name = $DB->get_field(pmclass::TABLE, 'idnumber', $param);
                    break;
                case 'profile':
                    break;
            }

            $output         = '';
            $attributes     = array('id' => "{$prefix}{$i}_label");

            $output         .= html_writer::tag('label', $name, $attributes);

            $output         .= '&nbsp;&nbsp;';

            $url            = "form/{$type}selector.php?id={$prefix}{$i}&callback=add_selection";
            $attributes     = array('onClick' => 'show_panel("'.$url.'")');
            $output         .= html_writer::link('#', $selecttype, $attributes);

            $mform->addElement('html', $output);


            $attributes     = array('id' => "{$prefix}{$i}_selected"); // Needed for javascript call back

            $mform->addElement('hidden', "{$prefix}{$i}_selected", $data->selected, $attributes);


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

    protected function get_assign_to_track_data($resultsid = 0) {
        global $DB, $CFG;

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

    public function definition_after_data() {
        global $CFG, $COURSE;
        $mform =& $this->_form;

        $data = $this->get_submitted_data();

        if (empty($data)) {
            return;
        }

print_object($data);

//print_object($mform->getElement('actiontype'));
//print_object($mform->getElementValue('track_add_0_selected'));

//if ($mform->getElementValue('track_add_0_selected')) {
    //$mform->freeze('class_add_0_group');

//$mform->disabledIf('class_add_0_group', 'track_add_0_max', 'neq', '');
//$mform->disabledIf('cls_assignment', 'track_add_0_max', 'neq', '');
//}
//
//        $data = (array) $data;
//
//        if (array_key_exists('cls_assignment', $data)) {
//            foreach ($data as $key => $value) {
//                if (false !== strpos($key, 'class_add_') and
//                    false !== strpos($key, '_selected')) {

//                    break;
//                }
//            }
//        }
//
//        if (array_key_exists('trk_assignment', $data)) {
//            foreach ($data as $key => $value) {
//
//                if (false !== strpos($key, 'class_')) {
//
//                    $mform->getElementValue($key);
//                }
//                if (false !== strpos($key, 'track_add_') and
//                    false !== strpos($key, '_selected')) {
//
//                    $mform->setDefault($key, 'actiontype');

//                }
//            }
//        }

//print_object('definition_after_data');
//print_object($data);
//print_object('definition_after_data - end');
//die();
//TODO: Use this method to disable elements on the field
/*
        $name = trim($mform->getElementValue('name'));
        $description = trim($mform->getElementValue('description'));
        $url = $mform->getElementValue('url');

        if (empty($name) || empty($description)) {
            $rss = new moodle_simplepie($url);

            if (empty($name) && $rss->get_title()) {
                $mform->setDefault('name', $rss->get_title());
            }

            if (empty($description) && $rss->get_description()) {
                $mform->setDefault('description', $rss->get_description());
            }
        }

        if ($id = $mform->getElementValue('id')) {
            $mform->setDefault('autotags', implode(',', tag_get_tags_array('blog_external', $id)));
            $mform->freeze('url');
            $mform->freeze('filtertags');
            // TODO change the filtertags element to a multiple select, using the tags of the external blog
            // Use $rss->get_channel_tags()
        }
*/
    }

}
