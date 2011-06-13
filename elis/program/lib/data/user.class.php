<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage programmanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once elis::lib('data/data_object_with_custom_fields.class.php');

/*
require_once CURMAN_DIRLOCATION . '/lib/cluster.class.php';
require_once CURMAN_DIRLOCATION . '/lib/usercluster.class.php';
require_once CURMAN_DIRLOCATION . '/lib/clusterassignment.class.php';
require_once CURMAN_DIRLOCATION . '/lib/cmclass.class.php';
require_once CURMAN_DIRLOCATION . '/lib/curriculumcourse.class.php';
require_once CURMAN_DIRLOCATION . '/lib/student.class.php';
require_once CURMAN_DIRLOCATION . '/form/userform.class.php';
require_once CURMAN_DIRLOCATION . '/lib/customfield.class.php';
*/
require_once $CFG->dirroot . '/user/filters/text.php';
require_once $CFG->dirroot . '/user/filters/date.php';
require_once $CFG->dirroot . '/user/filters/select.php';
require_once $CFG->dirroot . '/user/filters/simpleselect.php';
require_once $CFG->dirroot . '/user/filters/courserole.php';
require_once $CFG->dirroot . '/user/filters/globalrole.php';
require_once $CFG->dirroot . '/user/filters/profilefield.php';
require_once $CFG->dirroot . '/user/filters/yesno.php';
require_once $CFG->dirroot . '/user/filters/user_filter_forms.php';
require_once $CFG->dirroot . '/user/profile/lib.php';

class user extends data_object_with_custom_fields {
    const TABLE = 'crlm_user';

    var $verbose_name = 'user';

    static $associations = array(
        'classenrolments' => array(
            'class' => 'student',
            'foreignidfield' => 'userid'
        ),
        'waitlist' => array(
            'class' => 'waitlist',
            'foreignidfield' => 'userid'
        ),
        'classestaught' => array(
            'class' => 'instructor',
            'foreignidfield' => 'userid'
        ),
        'clusterassignments' => array(
            'class' => 'usercluster',
            'foreignidfield' => 'userid'
        ),
        'programassignments' => array(
            'class' => 'curriculumstudent',
            'foreignidfield' => 'userid'
        ),
        'trackassignments' => array(
            'class' => 'usertrack',
            'foreignidfield' => 'userid'
        ),
    );

    /**
     * Moodle username
     * @var    char
     * @length 100
     */
    protected $_dbfield_username;

    /**
     * User password
     * @var    char
     * @length 32
     */
    protected $_dbfield_password;

    /**
     * User ID-number
     * @var    char
     * @length 255
     */
    protected $_dbfield_idnumber;

    protected $_dbfield_firstname;
    protected $_dbfield_lastname;
    protected $_dbfield_mi;
    protected $_dbfield_email;
    protected $_dbfield_email2;
    protected $_dbfield_address;
    protected $_dbfield_address2;
    protected $_dbfield_city;
    protected $_dbfield_state;
    protected $_dbfield_country;
    protected $_dbfield_phone;
    protected $_dbfield_phone2;
    protected $_dbfield_fax;
    protected $_dbfield_postalcode;
    protected $_dbfield_birthdate;
    protected $_dbfield_gender;
    protected $_dbfield_language;
    protected $_dbfield_transfercredits;
    protected $_dbfield_comments;
    protected $_dbfield_notes;
    protected $_dbfield_timecreated;
    protected $_dbfield_timeapproved;
    protected $_dbfield_timemodified;
    protected $_dbfield_inactive;

    /**
     * Contructor.
     */
    /*
      FIXME: add support for custom fields
    function __construct($src=false, $field_map=null, array $associations=array(), moodle_database $database=null) {
        parent::datarecord($src, $field_map, $associations, $database);

        if (!empty($this->id)) {
            /// Load any other data we may want that is associated with the id number...
            // custom fields
            $level = context_level_base::get_custom_context_level('user', 'elis_program');
            if ($level) {
                $fielddata = field_data::get_for_context(get_context_instance($level,$this->id));
                $fielddata = $fielddata ? $fielddata : array();
                foreach ($fielddata as $name => $value) {
                    $this->{"field_{$name}"} = $value;
                }
            }
        }
    }
    */

    static $delete_is_complex = true;

    protected function get_field_context_level() {
        return context_level_base::get_custom_context_level('user', 'elis_program');
    }

    public function delete () {
        global $CFG;
        $muser = $this->get_moodleuser();

        if(empty($muser) || !is_primary_admin($muser->id)) {
            // delete associated data
            require_once elis::lib('data/data_filter.class.php');
            $filter = new field_filter('userid', $this->id);
            //curriculumstudent::delete_records($filter, $this->_db);
            //instructor::delete_records($filter, $this->_db);
            //student::delete_records($filter, $this->_db);
            //student_grade::delete_records($filter, $this->_db);
            //usertrack::delete_records($filter, $this->_db);
            //usersetassignment::delete_records($filter, $this->_db);
            //waitlist::delete_records($filter, $this->_db);

            $level = context_level_base::get_custom_context_level('user', 'elis_program');
            delete_context($level,$this->id);

            // Delete Moodle user.
            if (!empty($muser)) {
                delete_user($muser);
            }

            parent::delete();
        }
    }

    public static function find($filter=null, array $sort=array(), $limitfrom=0, $limitnum=0, moodle_database $db=null) {
        // if we're sorting by "name", sort by lastname, then firstname
        $newsort = array();
        foreach ($sort as $field => $dir) {
            if ($field == 'name') {
                $newsort['lastname'] = $dir;
                $newsort['firstname'] = $dir;
            } else {
                $newsort[$field] = $dir;
            }
        }

        return parent::find($filter, $newsort, $limitfrom, $limitnum, $db);
    }

    /**
     * @todo move out
     */
    public function set_from_data($data) {
        // Process non-direct elements:
        $this->set_date('birthdate', $data->birthyear, $data->birthmonth, $data->birthday);

        if (!empty($data->newpassword)) {
            $this->change_password($data->newpassword);
        }

        if(!empty($data->id_same_user)) {
            $data->username = $data->idnumber;
        }

        /**
        $fields = field::get_for_context_level(context_level_base::get_custom_context_level('user', 'elis_program'));
        $fields = $fields ? $fields : array();
        foreach ($fields as $field) {
            $fieldname = "field_{$field->shortname}";
            if (isset($data->$fieldname)) {
                $this->$fieldname = $data->$fieldname;
            }
        }
        */

        $this->_load_data_from_record($data, true);
    }

    public function fullname() {
        $name = array();

        if (!empty($this->firstname)) {
            $name[] = $this->firstname;
        }

        if (!empty($this->mi)) {
            $name[] = $this->mi;
        }

        if (!empty($this->lastname)) {
            $name[] = $this->lastname;
        }

        return implode(' ', $name);
    }

    public function __toString() {
        return $this->fullname();
    }

    public function get_moodleuser() {
        return $this->_db->get_record('user', array('idnumber' => $this->idnumber, 'deleted' => 0));
    }

    public function get_country() {
        $countries = get_string_manager()->get_list_of_countries();

        return isset($countries[$this->country]) ? $countries[$this->country] : '';
    }

    function set_date($field, $year, $month, $day) {
        if ($field == '') {
            return '';
        }
        if (empty($year) || empty($month) || empty($day)) {
            return '';
        }
        $this->$field = sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    /**
     * @todo move out
     */
    function get_add_form($form) {
        require_once elispm::file('/form/userform.class.php');

        return new addform($form);
    }

    static $validation_rules = array(
        'validate_idnumber_not_empty',
        'validate_unique_idnumber'
    );

    function validate_idnumber_not_empty() {
        return validate_not_empty($this, 'idnumber');
    }

    function validate_unique_idnumber() {
        return validate_is_unique($this, array('idnumber'));
    }

    public function save() {
        $isnew = empty($this->id);

        parent::save();

        /// Synchronize Moodle data with this data.
        $this->synchronize_moodle_user(true, $isnew);
    }

    function save_field_data() {
        static $loopdetect;

        if(!empty($loopdetect)) {
            return true;
        }

        field_data::set_for_context_from_datarecord('user', $this);

        $loopdetect = true;
        events_trigger('user_updated', $this->get_moodleuser());
        $loopdetect = false;

        return true;
    }

    /**
     * This function should change the password for the CM user.
     * It should treat it properly according to the text/HASH settings.
     *
     */
    function change_password($password) {
        $this->password = hash_internal_user_password($password);
    }

    /////////////////////////////////////////////////////////////////////
    //                                                                 //
    //  DATA FUNCTIONS:                                                //
    //                                                                 //
    /////////////////////////////////////////////////////////////////////

    /**
     * Function to synchronize the curriculum data with the Moodle data.
     *
     * @param boolean $tomoodle Optional direction to synchronize the data.
     *
     */
    function synchronize_moodle_user($tomoodle = true, $createnew = false) {
        global $CFG;

        static $mu_loop_detect = array();

        // Create a new Moodle user record to update with.

        if (!($muser = $this->get_moodleuser()) && !$createnew) {
            return false;
        }
        $muserid = $muser ? $muser->id : false;

        if ($tomoodle) {
            if ($createnew && !$muserid) {
                /// Add a new user
                $record                 = new stdClass();
                $record->idnumber       = $this->idnumber;
                $record->username       = $this->username;
                $record->password       = $this->password;
                $record->firstname      = $this->firstname;
                $record->lastname       = $this->lastname;
                $record->email          = $this->email;
                $record->confirmed      = 1;
                $record->mnethostid     = $CFG->mnet_localhost_id;
                $record->address        = $this->address;
                $record->city           = $this->city;
                $record->country        = $this->country;
                $record->timemodified   = time();
                $record->id = $this->_db->insert_record('user', $record);
            } else if ($muserid) {
                /// Update an existing user
                $record                 = new stdClass();
                $record->id             = $muserid;
                $record->idnumber       = $this->idnumber;
                $record->username       = $this->username;
                $record->password       = $this->password;
                $record->firstname      = $this->firstname;
                $record->lastname       = $this->lastname;
                $record->email          = $this->email;
                $record->address        = $this->address;
                $record->city           = $this->city;
                $record->country        = $this->country;
                $record->timemodified   = time();
                $this->_db->update_record('user', $record);
            } else {
                return true;
            }

            // avoid update loops
            if (isset($mu_loop_detect[$this->id])) {
                return $record->id;
            }
            $mu_loop_detect[$this->id] = true;

            // synchronize profile fields
            /*
            $origrec = clone($record);
            profile_load_data($origrec);
            $fields = field::get_for_context_level(context_level_base::get_custom_context_level('user', 'elis_user'));
            $mfields = $this->_db->get_records('user_info_field', array(), '', 'shortname');
            $fields = $fields ? $fields : array();
            $changed = false;
            require_once elis::plugin_file('elisfields_moodle_profile','custom_fields.php');
            foreach ($fields as $field) {
                $field = new field($field);
                if (isset($field->owners['moodle_profile']) && $field->owners['moodle_profile']->exclude == cm_moodle_profile::sync_to_moodle && isset($mfields[$field->shortname])) {
                    $shortname = $field->shortname;
                    $fieldname = "field_$shortname";
                    $mfieldname = "profile_$fieldname";
                    $mfieldvalue = isset($origrec->$mfieldname) ? $origrec->$mfieldname : null;
                    if ($mfieldvalue != $this->$fieldname) {
                        $record->$mfieldname = $this->$fieldname;
                        $changed = true;
                    }
                }
            }
            profile_save_data(addslashes_recursive($record));
            */

            if ($muserid) {
                //if ($changed) {
                    events_trigger('user_updated', $record);
                //}
            } else {
                events_trigger('user_created', $record);
            }

            unset($mu_loop_detect[$this->id]);
            return $record->id;
        }
    }

    /**
     * Retrieves a list of classes the specified user is currently enrolled in under the specified curriculum.
     * @param $userid ID of the user
     * @param $curid ID of the curriculum
     * @return unknown_type
     */
    static function get_current_classes_in_curriculum($userid, $curid) {
        $sql = 'SELECT curcrs.*, crs.name AS coursename
                  FROM {'.curriculumcourse::TABLE.'} curcrs
                  JOIN {'.course::TABLE.'} crs ON curcrs.courseid = crs.id
                       -- Next two are to limit to currently enrolled courses
                  JOIN {'.pmclass::TABLE.'} cls ON cls.courseid = crs.id
                  JOIN {'.student::TABLE.'} clsenrol ON cls.id = clsenrol.classid
                 WHERE curcrs.curriculumid = ?
                   AND clsenrol.userid = ?
                   AND clsenrol.completestatusid = ?
              ORDER BY curcrs.position';

        $params = array($curid, $userid, student::STATUS_NOTCOMPLETE);

        return $this->_db->get_records_sql($sql, $params);
    }

    /**
     * Retrieves a list of classes the specified user is currently enrolled in that don't fall under a
     * curriculum the user is assigned to.
     * @param $userid ID of the user
     * @param $curid ID of the curriculum
     * @return unknown_type
     */
    static function get_non_curriculum_classes($userid) {
        $sql = 'SELECT curcrs.*, crs.name AS coursename, crs.id AS courseid
                  FROM {'.student::TABLE.'} clsenrol
                  JOIN {'.pmclass::TABLE.'} cls ON cls.id = clsenrol.classid
                  JOIN {'.course::TABLE.'} crs ON crs.id = cls.courseid
             LEFT JOIN (SELECT curcrs.courseid
                          FROM {'.curriculumcourse::TABLE.'} curcrs
                          JOIN {'.curriculumstudent::TABLE.'} curass ON curass.curriculumid = curcrs.curriculumid AND curass.userid = ?) curcrs
                       ON curcrs.courseid = crs.id
                 WHERE clsenrol.userid = ? AND curcrs.courseid IS NULL';
        $params = array($userid, $userid);

        return $this->_db->get_records_sql($sql, $params);
    }

    /**
     * Retrieves a list of courses that:
     * - Belong to the specified curriculum.
     * - The user is not currently enrolled in.
     * @param $userid ID of the user to retrieve the courses for.
     * @param $curid ID of the curriculum to retrieve the courses for.
     * @return unknown_type
     */
    static function get_user_course_curriculum($userid, $curid) {
        $sql = 'SELECT curcrs.*, crs.name AS coursename, cls.count as classcount, prereq.count as prereqcount, enrol.completestatusid as completionid, waitlist.courseid as waiting
                  FROM {'.curriculumcourse::TABLE.'} curcrs
                  JOIN {'.course::TABLE.'} crs ON curcrs.courseid = crs.id
                       -- limit to non-enrolled courses
                  JOIN (SELECT cls.courseid, clsenrol.completestatusid FROM {'.pmclass::TABLE.'} cls
                          JOIN {'.student::TABLE.'} clsenrol ON cls.id = clsenrol.classid AND clsenrol.userid = :userid) enrol
                       ON enrol.courseid = crs.id
                       -- limit to courses where user is not on waitlist
             LEFT JOIN (SELECT cls.courseid
                          FROM {'.pmclass::TABLE.'} cls
                          JOIN {'.waitlist::TABLE.'} watlst ON cls.id = watlst.classid AND watlst.userid = :userid) waitlist
                       ON waitlist.courseid = crs.id
                       -- count the number of classes for each course
             LEFT JOIN (SELECT cls.courseid, COUNT(*) as count
                          FROM {'.pmclass::TABLE.'} cls
                               -- enddate is beginning of day
                         WHERE (cls.enddate > (:currtime - 24*60*60)) OR NOT cls.enddate
                      GROUP BY cls.courseid) cls
                       ON cls.courseid = crs.id
                       -- count the number of unsatisfied prerequisities
             LEFT JOIN (SELECT prereq.curriculumcourseid, COUNT(*) as count
                          FROM {'.courseprerequisite::TABLE.'} prereq
                          JOIN {'.course::TABLE.'} crs ON prereq.courseid=crs.id
                     LEFT JOIN (SELECT cls.courseid
                                  FROM {'.pmclass::TABLE.'} cls
                                  JOIN {'.student::TABLE.'} enrol ON enrol.classid = cls.id
                                 WHERE enrol.completestatusid = '.student::STATUS_PASSED.' AND enrol.userid=$userid
                                   AND (cls.enddate > :currtime OR NOT cls.enddate)) cls
                               ON cls.courseid = crs.id
                         WHERE cls.courseid IS NULL
                      GROUP BY prereq.curriculumcourseid) prereq
                       ON prereq.curriculumcourseid = curcrs.id
                 WHERE curcrs.curriculumid = :curid
              ORDER BY curcrs.position';

        $params = array(
            'userid' => $userid,
            'currtime' => time(),
            'curid' => $curid,
        );

        return $this->_db->get_records_sql($sql, $params);
    }

    /**
     * Retrieves a list of classes the user instructs.
     * @param $userid ID of the user
     * @return unknown_type
     */
    static function get_instructed_classes($userid) {
        $sql = 'SELECT cls.*, crs.name AS coursename
                  FROM {'.pmclass::TABLE.'} cls
                  JOIN {'.course::TABLE.'} crs ON cls.courseid = crs.id
                  JOIN {'.instructor::TABLE.'} clsinstr ON cls.id = clsinstr.classid
                 WHERE clsinstr.userid = ?
              GROUP BY cls.id ';
        $params = array($userid);

        return $this->_db->get_records_sql($sql, $params);
    }

    /**
     * Get the user dashboard report view.
     *
     * @uses $CFG
     * @param none
     * @return string The HTML for the dashboard report.
     * @todo move out of this class
     */
    function get_dashboard() {
        global $CFG;

        require_once elispm::lib('curriculumstudent.class.php');

        $content = '';

        $table = new stdClass;
        $table->head = array(
            get_string('learningplan', 'elis_program'),
            get_string('class', 'elis_program'),
            get_string('score', 'elis_program'),
            get_string('datecompleted', 'elis_program')
        );

        $table->data = array();

        $data = array();

        $totalcourses    = 0;
        $completecourses = 0;

    /// Store class IDs the student is enrolled into from their current curriculua.
        $classids = array();

        if ($usercurs = curriculumstudent::get_curricula($this->id)) {
            foreach ($usercurs as $usercur) {
                if ($courses = curriculumcourse_get_listing($usercur->curid, 'curcrs.position, crs.name', 'ASC')) {
                    foreach ($courses as $course) {
                        $totalcourses++;

                        if ($classdata = student_get_class_from_course($course->courseid, $this->id)) {
                            if (!in_array($classdata->id, $classids)) {
                                $classids[] = $classdata->id;
                            }

                            if ($classdata->completestatusid == student::STATUS_PASSED) {
                                $completecourses++;
                            }

                            if ($mdlcrs = moodle_get_course($classdata->id)) {
                                $coursename = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' .
                                              $mdlcrs . '">' . $course->coursename . '</a>';
                            } else {
                                $coursename = $course->coursename;
                            }

                            if ($classdata->completestatusid == student::STATUS_PASSED && !empty($classdata->completetime)) {

                            }

                            $data[] = array(
                                empty(elispm::$config->disablecoursecatalog) ? ('<a href="index.php?s=crscat&section=curr&showcurid=' . $usercur->curid . '">' . $usercur->name . '</a>') : $usercur->name,
                                $coursename,
                                $classdata->grade,
                                $classdata->completestatusid == student::STATUS_PASSED && !empty($classdata->completetime) ?
                                    date('M j, Y', $classdata->completetime) : 'NA'
                            );
                        } else {
                            $data[] = array(
                                empty(elispm::$config->disablecoursecatalog) ? ('<a href="index.php?s=crscat&section=curr&showcurid=' . $usercur->curid . '">' . $usercur->name . '</a>') : $usercur->name,
                                $course->coursename,
                                0,
                                'NA'
                            );
                        }
                    }
                }
            }
        }

        if (!empty($data)) {
            $cursused = array();

            foreach ($data as $datum) {
                $curname = '';

                if (in_array($datum[0], $cursused)) {
                    $datum[0] = '';
                } else {
                    $cursused[] = $datum[0];
                }

                $table->data[] = $datum;
            }
        }

        $content .= print_heading_block(get_string('learningplanwelcome', 'elis_user', cm_fullname($this)), '', true);

        if ($totalcourses === 0) {
            $content .= '<br /><center>' . get_string('nolearningplan', 'elis_program') . '</center>';
            return $content;
        }

        $a = new stdClass;
        $a->percent         = ($totalcourses != 0) ? sprintf("%d%%", $completecourses / $totalcourses * 100) : '';
        $a->coursescomplete = $completecourses;
        $a->coursestotal    = $totalcourses;

        $content .= print_heading(get_string('learningplanintro', 'elis_program', $a), 'left', 2, 'main', true);

        $content .= print_table($table, true);

    /// Get old completed course data for this user.
        if (!empty($classids)) {
            list($in_sql, $in_params) = $this->_db->get_in_or_equal($classids, SQL_PARAMS_QM, '', false);
            $sql = 'SELECT stu.id, crs.name as coursename, stu.completetime, stu.grade, stu.completestatusid
                      FROM {'.student::TABLE.'} stu
                      JOIN {'.pmclass::TABLE.' cls ON cls.id = stu.classid
                      JOIN {'.course::TABLE.' crs ON crs.id = cls.courseid
                     WHERE userid = ?
                       AND stu.completestatusid != '.student::STATUS_NOTCOMPLETE.'
                       AND classid $is_sql
                  ORDER BY crs.name ASC, stu.completetime ASC';

            $params = array_merge(array($this->id), $in_params);

            if ($classes = get_records_sql($sql)) {
                $table = new stdClass;
                $table->head = array(
                    'Class',
                    'Score',
                    'Completion Date'
                );

                $table->data = array();

                foreach ($classes as $class) {
                    $table->data[] = array(
                        $class->coursename,
                        $class->grade,
                        date('M j, Y', $class->completetime)
                    );
                }

                $content .= '<hr style="height: 2px; border-width: 0; color: gray; background-color: gray" />';
                $content .= print_heading(get_string('previouscourses', 'elis_program'), 'center',
                                          2, 'main', true);

                $content .= print_table($table, true);
            }
        }

        return $content;
    }
}


/**
 * "Show inactive users" filter type.
 */
class cm_show_inactive_filter extends user_filter_type {
    /**
     * options for the list values
     */
    var $_options;

    var $_field;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field user table filed name
     * @param array $options select options
     */
    function cm_show_inactive_filter($name, $label, $advanced, $field, $options) {
        parent::user_filter_type($name, $label, $advanced);
        $this->_field   = $field;
        $this->_options = $options;
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        $mform->addElement('select', $this->_name, $this->_label, $this->_options);

        // TODO: add help
        //$mform->setHelpButton($this->_name, array('simpleselect', $this->_label, 'filters'));

        if ($this->_advanced) {
            $mform->setAdvanced($this->_name);
        }
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field = $this->_name;

        if (array_key_exists($field, $formdata)) {
            if($formdata->$field != 0) {
                return array('value'=>(string)$formdata->$field);
            }
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return string the filtering condition or null if the filter is disabled
     */
    function get_sql_filter($data) {
        $retval = $this->_field . ' = 0';
        $value = $data['value'];

        switch($value) {
        case '1':
            $retval = '1=1';

            break;
        case '2':
            $retval = $this->_field . ' = 1';

            break;
        }

        return array($retval,array());
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        $retval = '';

        if(!empty($data['value'])) {
            if($data['value'] == 1) {
                $retval = get_string('all');
            } else if($data['value'] == 2) {
                $retval = get_string('inactive', 'elis_program');
            }
        }

        return $retval;
    }
}

class cm_custom_field_filter extends user_filter_type {
    /**
     * options for the list values
     */
    var $_field;

    function cm_custom_field_filter($name, $label, $advanced, $field) {
        parent::user_filter_type($name, $label, $advanced);
        $this->_field   = $field;
    }

    function setupForm(&$mform) {
        $fieldname = "field_{$this->_field->shortname}";

        if (isset($this->_field->owners['manual'])) {
            $manual = new field_owner($this->_field->owners['manual']);
            if (isset($manual->param_control)) {
                $control = $manual->param_control;
            }
        }
        if (!isset($control)) {
            $control = 'text';
        }
        require_once elis::plugin_file('elisfields_manual', 'field_controls/{$control}.php');
        call_user_func("{$control}_control_display", $mform, $this->_field, true);

        $mform->setAdvanced($fieldname);
    }

    function check_data($formdata) {
        $field = "field_{$this->_field->shortname}";

        if (!empty($formdata->$field)) {
            return array('value'=>(string)$formdata->$field);
        }

        return false;
    }

    function get_sql_filter($data) {
        global $DB;

        static $counter = 0;
        $name = 'ex_elisfield'.$counter++;
        $ilike = sql_ilike();
        $level = context_level_base::get_custom_context_level('user', 'elis_program');
        $sql = 'EXISTS (SELECT * FROM {'.$this->_field->data_table()."} data
                        JOIN {context} ctx ON ctx.id = data.contextid
                        WHERE ctx.instanceid = {crlm_user}.id
                          AND ctx.contextlevel = $level
                          AND data.fieldid = $this->_field->id
                          AND {".$DB->sql_like('data.data', ":$name", false).'}';
        $params = array($name => "%{$DB->sql_like_escape($data['value'])}%");

        return array($sql,$params);
    }

    function get_label($data) {
        $retval = '';

        if(!empty($data['value'])) {
            $a = new stdClass;
            $a->label = $this->_field->name;
            $a->value = "\"{$data['value']}\"";
            $a->operator = get_string('contains', 'filters');

            return get_string('textlabel', 'filters', $a);
        }

        return $retval;
    }
}

/**
 * User filtering wrapper class.
 */
class cm_user_filtering extends user_filtering {
    /**
     * Contructor
     * @param array array of visible user fields
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     */
    function cm_user_filtering($fieldnames=null, $baseurl=null, $extraparams=null) {
        if (empty($fieldnames)) {
            $fieldnames = array(
                'realname' => 0,
                'lastname' => 1,
                'firstname' => 1,
                'idnumber' => 1,
                'email' => 0,
                'city' => 1,
                'country' => 1,
                'username' => 0,
                'language' => 1,
                'clusterid' => 1,
                'curriculumid' => 1,
            	'inactive' => 1,
                );

            /*
              FIXME:
            $fields = field::get_for_context_level(context_level_base::get_custom_context_level('user', 'block_curr_admin'));
            $fields = $fields ? $fields : array();
            foreach ($fields as $field) {
                $fieldnames["field_{$field->shortname}"] = 1;
            }
            */
        }

        /// Remove filters if missing capability...
        $context = get_context_instance(CONTEXT_SYSTEM);
        if (!has_capability('block/curr_admin:viewreports', $context)) {
            if (has_capability('block/curr_admin:viewgroupreports', $context)) {
                unset($fieldnames['clusterid']);
            }
        }

        parent::user_filtering($fieldnames, $baseurl, $extraparams);
    }

    /**
     * Creates known user filter if present
     *
     * @uses $USER
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced) {
        global $USER, $DB;

        $IFNULL = "COALESCE(mi, '')";

        $FULLNAME = $DB->sql_concat_join("' '", array('firstname', $IFNULL, 'lastname'));

        switch ($fieldname) {
        case 'username':    return new user_filter_text('username', get_string('username'), $advanced, 'username');
        case 'realname':    return new user_filter_text('realname', get_string('fullname'),
                                                        $advanced, $FULLNAME);
        case 'lastname':    return new user_filter_text('lastname', get_string('lastname'), $advanced, 'lastname');
        case 'firstname':   return new user_filter_text('firstname', get_string('firstname'), $advanced, 'firstname');
        case 'idnumber':    return new user_filter_text('idnumber', get_string('idnumber'), $advanced, 'idnumber');
        case 'email':       return new user_filter_text('email', get_string('email'), $advanced, 'email');

        case 'city':        return new user_filter_text('city', get_string('city'), $advanced, 'city');
        case 'country':     return new user_filter_select('country', get_string('country'), $advanced, 'country', get_string_manager()->get_list_of_countries(), $USER->country);
        case 'timecreated': return new user_filter_date('timecreated', get_string('timecreated'), $advanced, 'timecreated');

        case 'language':
            return new user_filter_select('language', get_string('preferredlanguage'), $advanced, 'language', get_string_manager()->get_list_of_languages());

            //case 'clusterid':
            //$clusters = cm_get_list_of_clusters();
            //return new user_filter_select('clusterid', get_string('usercluster', 'block_curr_admin'), $advanced, 'clusterid', $clusters);

            //case 'curriculumid':
            //$choices = curriculum_get_menu();
            //return new user_filter_select('curriculumid', get_string('usercurricula', 'block_curr_admin'), $advanced, 'curass.curriculumid', $choices);

        case 'inactive':
            $inactive_options = array(get_string('o_active', 'elis_program'), get_string('all'), get_string('o_inactive', 'elis_program'));
            return new cm_show_inactive_filter('inactive', get_string('showinactive', 'elis_program'), $advanced, 'inactive', $inactive_options);


        default:
            if (strncmp($fieldname, 'field_', 6) === 0) {
                $f = substr($fieldname, 6);
                $rec = new field($DB->get_record(FIELDTABLE, 'shortname', $f));
                return new cm_custom_field_filter($fieldname, $rec->shortname, $advanced, $rec);
            }
            return null;
        }
    }

    /**
     * Print the add filter form.
     */
    function display_add($return = false) {
        if ($return) {
            return $this->_addform->_form->toHtml();
        } else {
            $this->_addform->display();
        }
    }

    /**
     * Print the active filter form.
     */
    function display_active($return = false) {
        if ($return) {
            return $this->_activeform->_form->toHtml();
        } else {
            $this->_activeform->display();
        }
    }

    /**
     * Returns sql where statement based on active user filters.  Overridden to provide proper
     * 'show inactive' default condition.
     *
     * @param string $extra sql
     * @return string
     */
    function get_sql_filter($extra='') {
        global $SESSION;

        $newextra = '';

        // Include default SQL if inactive filter has not been included in list
        if (empty($SESSION->user_filtering) || !isset($SESSION->user_filtering['inactive']) || !$SESSION->user_filtering['inactive']) {
            $newextra = ($extra ? $extra . ' AND ' : '') . 'inactive=0';
        }

        return parent::get_sql_filter($newextra);
    }
}

/// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)


/**
 * Gets a instructor listing with specific sort and other filters.
 *
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for instructor name.
 * @param string $alpha Start initial of instructor name filter.
 * @return object array Returned records.
 */

function user_get_listing($sort='name', $dir='ASC', $startrec=0, $perpage=0, $namesearch='',
                          $alpha='') {
    global $DB;

    $FULLNAME = $DB->sql_concat_join("' '", array('firstname', 'lastname'));

    $filters = array();

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $filters[] = new field_filter($FULLNAME, "%{$DB->sql_like_escape($namesearch)}%", field_filter::LIKE);
    }

    if ($alpha) {
        $filters[] = new field_filter($FULLNAME, "%{$DB->sql_like_escape($alpha)}", field_filter::LIKE);
    }

    if ($sort) {
        $sort = array($sort,$dir);
    } else {
        $sort = array();
    }

    return user::find(new AND_filter($filters), $sort, $startrec, $perpage);
}


function user_count_records() {
    return data_record::count();
}

?>
