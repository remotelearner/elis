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
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * Base class for assign/edit actions.
 */
abstract class deepsight_action_instructor_assignedit_base extends deepsight_action_standard {

    /**
     * The type of javascript file to use.
     */
    const TYPE = 'instructor_assignedit';

    /**
     * @var string The mode (assign/edit) to use the javascript in.
     */
    protected $mode = '';

    /**
     * Determine whether the user can manage the instructor association for a given user and class.
     * @param int $classid The ID of the class.
     * @param int $userid The ID of the user.
     * @return bool Whether the user has permission.
     */
    protected function can_manage_assoc($classid, $userid) {
        return instructor::can_manage_assoc($userid, $classid);
    }

    /**
     * Sets options and language strings for the javascript object.
     * @see deepsight_action::get_js_opts();
     */
    public function get_js_opts() {
        global $CFG;
        $opts = parent::get_js_opts();
        $opts['condition'] = $this->condition;
        $opts['opts']['actionurl'] = $this->endpoint;
        $opts['opts']['mode'] = $this->mode;
        $opts['opts']['langbulkconfirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['langworking'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langchanges'] = get_string('ds_changes', 'local_elisprogram');
        $opts['opts']['langnochanges'] = get_string('ds_nochanges', 'local_elisprogram');
        $opts['opts']['langgeneralerror'] = get_string('ds_unknown_error', 'local_elisprogram');
        $opts['opts']['langtitle'] = get_string('ds_assocdata', 'local_elisprogram');
        $opts['opts']['langassigntime'] = get_string('instructor_assignment', 'local_elisprogram');
        $opts['opts']['langcompletetime'] = get_string('instructor_completion', 'local_elisprogram');
        $opts['opts']['lang_months'] = array(
            0 => get_string('month_jan_short', 'local_elisprogram'),
            1 => get_string('month_feb_short', 'local_elisprogram'),
            2 => get_string('month_mar_short', 'local_elisprogram'),
            3 => get_string('month_apr_short', 'local_elisprogram'),
            4 => get_string('month_may_short', 'local_elisprogram'),
            5 => get_string('month_jun_short', 'local_elisprogram'),
            6 => get_string('month_jul_short', 'local_elisprogram'),
            7 => get_string('month_aug_short', 'local_elisprogram'),
            8 => get_string('month_sep_short', 'local_elisprogram'),
            9 => get_string('month_oct_short', 'local_elisprogram'),
            10 => get_string('month_nov_short', 'local_elisprogram'),
            11 => get_string('month_dec_short', 'local_elisprogram')
        );
        return $opts;
    }

    /**
     * Process association data from the form.
     * @param string $assocdata JSON-formatted association data.
     * @param string $bulkaction Whether this is a bulk action or not.
     * @return array The formatted and cleaned association data.
     */
    protected function process_incoming_assoc_data($assocdata, $bulkaction) {
        $assocdata = @json_decode($assocdata, true);
        if (!is_array($assocdata)) {
            return array();
        }
        if ($bulkaction === true && $this->mode === 'edit') {
            $cleanedassoc = array();
        } else {
            $cleanedassoc = array(
                'assigntime' => 0,
                'completetime' => 0,
            );
        }

        foreach (array('assigntime', 'completetime') as $param) {
            if (isset($assocdata[$param])) {
                $cleanedassoc[$param] = ds_process_js_date_data($assocdata[$param]);
            }
        }
        return $cleanedassoc;
    }

    /**
     * Formats association data for display in the table post-edit.
     * @param array $assocdata The incoming association data
     * @return array The formatted association data.
     */
    protected function format_assocdata_for_display($assocdata) {
        foreach (array('assigntime', 'completetime') as $timeparam) {
            $assocdata[$timeparam] = ds_process_displaytime($assocdata[$timeparam]);
        }
        return $assocdata;
    }
}

/**
 * An action to assign users as instructors to a class.
 */
class deepsight_action_instructor_assign extends deepsight_action_instructor_assignedit_base {

    /**
     * @var string The label to use for the action button (this will be overwritten by a proper language string)
     */
    public $label = 'Assign';

    /**
     * @var string The icon CSS class to use for the action.
     */
    public $icon = 'elisicon-assoc';

    /**
     * @var string The mode (assign/edit) to use the javascript in.
     */
    protected $mode = 'assign';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('assign', 'local_elisprogram'));

        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_instructor_assign', 'local_elisprogram');

        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_instructor_assign_multi', 'local_elisprogram');
    }

    /**
     * Assign users as instructors to the class.
     * @throws moodle_exception When user is not allowed manage class enrolments.
     * @param array $elements An array of user information to assign to the class.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $classid = required_param('id', PARAM_INT);
        $assocdata = required_param('assocdata', PARAM_CLEAN);
        $assocdata = $this->process_incoming_assoc_data($assocdata, $bulkaction);

        if (instructorpage::can_enrol_into_class($classid) !== true) {
            throw new moodle_exception('not_permitted', 'local_elisprogram');
        }

        foreach ($elements as $userid => $label) {
            if ($this->can_manage_assoc($classid, $userid) === true) {
                $instructor = array();
                $instructor['classid'] = $classid;
                $instructor['userid']  = $userid;

                foreach (array('assigntime', 'completetime') as $param) {
                    if (isset($assocdata[$param])) {
                        $instructor[$param] = $assocdata[$param];
                    }
                }

                $instructor = new instructor($instructor);
                $status = $instructor->save();
            }
        }
        return array('result' => 'success', 'msg' => 'Success');
    }
}

/**
 * An action to edit instructors.
 */
class deepsight_action_instructor_edit extends deepsight_action_instructor_assignedit_base {

    /**
     * @var string The label to use for the action button (this will be overwritten by a proper language string)
     */
    public $label = 'Edit';

    /**
     * @var string The icon CSS class to use for the action.
     */
    public $icon = 'elisicon-edit';

    /**
     * @var string The mode (assign/edit) to use the javascript in.
     */
    protected $mode = 'edit';

    /**
     * Sets the correct language string on the label.
     */
    protected function postconstruct() {
        $this->label = get_string('edit', 'local_elisprogram');
    }

    /**
     * Assign users as instructors to the class.
     * @throws moodle_exception When user is not allowed manage class enrolments.
     * @param array $elements An array of user information to edit.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $classid = required_param('id', PARAM_INT);
        $assocdata = required_param('assocdata', PARAM_CLEAN);
        $assocdata = $this->process_incoming_assoc_data($assocdata, $bulkaction);

        if (instructorpage::can_enrol_into_class($classid) !== true) {
            throw new moodle_exception('not_permitted', 'local_elisprogram');
        }

        foreach ($elements as $userid => $label) {
            if ($this->can_manage_assoc($classid, $userid) === true) {
                $assoc = $DB->get_record(instructor::TABLE, array('classid' => $classid, 'userid' => $userid));
                if (!empty($assoc)) {
                    $instructor = new instructor($assoc);

                    foreach (array('assigntime', 'completetime') as $param) {
                        if (isset($assocdata[$param])) {
                            $instructor->$param = $assocdata[$param];
                        }
                    }

                    $status = $instructor->save();
                }
            }
        }
        $formatteddata = $this->format_assocdata_for_display($assocdata);
        $newassocdata = array();
        foreach ($assocdata as $key => $val) {
            $newassocdata[$key] = json_encode(array(
                'date' => date('j', $val),
                'month' => date('n', $val)-1,
                'year'=> date('Y', $val)
            ));
        }
        return array(
            'result' => 'success',
            'msg' => 'Success',
            'displaydata' => $formatteddata,
            'saveddata' => $newassocdata
        );
    }
}

/**
 * An action to delete instructor associations.
 */
class deepsight_action_instructor_unassign extends deepsight_action_confirm {
    /**
     * @var string The label to use for the action button (this will be overwritten by a proper language string)
     */
    public $label = 'Unassign';

    /**
     * @var string The icon CSS class to use for the action.
     */
    public $icon = 'elisicon-unassoc';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('unassign', 'local_elisprogram'));

        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_instructor_unassign', 'local_elisprogram');

        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_instructor_unassign_multi', 'local_elisprogram');
    }

    /**
     * Perform unassignments.
     * @throws moodle_exception When user is not allowed manage class enrolments.
     * @param array $elements An array of user information to unassign.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $classid = required_param('id', PARAM_INT);

        if (instructorpage::can_enrol_into_class($classid) !== true) {
            throw new moodle_exception('not_permitted', 'local_elisprogram');
        }

        foreach ($elements as $userid => $label) {
            if ($this->can_manage_assoc($classid, $userid) === true) {
                $assoc = $DB->get_record(instructor::TABLE, array('classid' => $classid, 'userid' => $userid));
                if (!empty($assoc)) {
                    $instructor = new instructor($assoc);
                    $instructor->load();
                    $instructor->delete();
                }
            }
        }

        return array('result' => 'success', 'msg' => 'Success');
    }

    /**
     * Determine whether the user can manage the instructor association for a given user and class.
     * @param int $classid The ID of the class.
     * @param int $userid The ID of the user.
     * @return bool Whether the user has permission.
     */
    protected function can_manage_assoc($classid, $userid) {
        return instructor::can_manage_assoc($userid, $classid);
    }
}
