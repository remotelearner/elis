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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

if(!defined('NO_ROLE_ID')) {
    define('NO_ROLE_ID', 0);
}

require_once elispm::file('form/cmform.class.php');

class configclsdefaultform extends cmform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'clsdefault', get_string('defaultcls', 'local_elisprogram'));

        $mform->addElement('text', 'clsdftidnumber', get_string('class_idnumber', 'local_elisprogram') . ':');
        $mform->setType('clsdftidnumber', PARAM_TEXT);

        $mform->addElement('date_selector', 'clsdftstartdate', get_string('class_startdate', 'local_elisprogram') . ':', array('optional' => true));
        $mform->addElement('date_selector', 'clsdftenddate', get_string('class_enddate', 'local_elisprogram') . ':', array('optional' => true));

        $mform->addElement('time_selector', 'clsdftstarttime', get_string('class_starttime', 'local_elisprogram') . ':',
                           array('display_12h'=>elis::$config->local_elisprogram->time_format_12h));

        $mform->addElement('time_selector', 'clsdftendtime', get_string('class_endtime', 'local_elisprogram') . ':',
                           array('display_12h'=>elis::$config->local_elisprogram->time_format_12h));

        $mform->addElement('text', 'clsdftmaxstudents', get_string('class_maxstudents', 'local_elisprogram') . ':');
        $mform->setType('clsdftmaxstudents', PARAM_INT);

        $this->add_action_buttons();
    }

    function set_data($default_values, $slashed=false) {

        $default_values = clone $default_values;
        parent::set_data($default_values, $slashed);
    }
}