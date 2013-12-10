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

require_once(elis::plugin_file('usetenrol_manual', 'lib.php'));

/**
 * An action to assign usersets to a user.
 */
class deepsight_action_useruserset_assign extends deepsight_action_confirm {
    public $label = 'Assign';
    public $icon = 'elisicon-assoc';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_assign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('clusters', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_assign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Assign the usersets to the user.
     *
     * @param array $elements An array of userset information to assign to the user.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $userid = required_param('id', PARAM_INT);
        $user = new user($userid);

        // Permissions.
        $upage = new userpage();
        if ($upage->_has_capability('local/elisprogram:user_view', $userid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'local_elisprogram'));
        }

        foreach ($elements as $usersetid => $label) {
            if ($this->can_assign($user->id, $usersetid) === true) {
                cluster_manual_assign_user($usersetid, $user->id);
            }
        }

        return array('result' => 'success', 'msg'=>'Success');
    }

    /**
     * Determine whether the current user can assign the given user to the given userset.
     *
     * @param int $userid The ID of the user.
     * @param int $usersetid The ID of the userset.
     * @return bool Whether the user can assign (true) or not (false)
     */
    protected function can_assign($userid, $usersetid) {
        return clusteruserpage::can_manage_assoc($userid, $usersetid);
    }
}

/**
 * An action to unassign a usersets from a user.
 */
class deepsight_action_useruserset_unassign extends deepsight_action_confirm {
    public $label = 'Unassign';
    public $icon = 'elisicon-unassoc';

    /**
     * Constructor.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('unassign', 'local_elisprogram'));

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_unassign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('clusters', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_unassign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Unassign the usersets from the user.
     *
     * @param array $elements An array containing information on usersets to unassign from the user.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $userid = required_param('id', PARAM_INT);

        // Permissions.
        $upage = new userpage();
        if ($upage->_has_capability('local/elisprogram:user_view', $userid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'local_elisprogram'));
        }

        foreach ($elements as $usersetid => $label) {
            if ($this->can_unassign($userid, $usersetid) === true) {
                $assignrec = $DB->get_record(clusterassignment::TABLE, array('userid' => $userid, 'clusterid' => $usersetid));
                if (!empty($assignrec) && $assignrec->plugin === 'manual') {
                    $usertrack = new clusterassignment($assignrec);
                    $usertrack->delete();
                }
            }
        }

        return array('result' => 'success', 'msg'=>'Success');
    }

    /**
     * Determine whether the current user can assign the given user to the given userset.
     *
     * @param int $userid The ID of the user.
     * @param int $usersetid The ID of the userset.
     * @return bool Whether the user can assign (true) or not (false)
     */
    protected function can_unassign($userid, $usersetid) {
        return clusteruserpage::can_manage_assoc($userid, $usersetid);
    }
}