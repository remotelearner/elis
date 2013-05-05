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
 * @package    elis
 * @subpackage programmanager
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * An action to assign a user to a track.
 */
class deepsight_action_trackassign extends deepsight_action_confirm {
    public $label = 'Assign User';
    public $icon = 'elisicon-assoc';

    /**
     * Constructor.
     *
     * Sets internal data.
     */
    public function __construct(moodle_database &$DB, $name, $desc_single='', $desc_multiple='') {
        parent::__construct($DB, $name);

        $this->desc_single = (!empty($desc_single))
            ? $desc_single
            : get_string('ds_action_trackassign_confirm', 'elis_program');
        $this->desc_multiple = (!empty($desc_multiple))
            ? $desc_multiple
            : get_string('ds_action_trackassign_confirm_multi', 'elis_program');
    }

    /**
     * Assign the user to the track.
     *
     * @param array $elements    An array of elements to perform the action on.
     * @param bool  $bulk_action Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulk_action) {
        global $DB;
        $trkid = required_param('id', PARAM_INT);
        $track = new track($trkid);

        // Permissions.
        if (trackpage::can_enrol_into_track($track->id) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'elis_program'));
        }

        foreach ($elements as $userid => $label) {
            if ($this->can_assign($track->id, $userid) !== true) {
                continue;
            }

            usertrack::enrol($userid, $track->id);
        }

        return array('result' => 'success', 'msg'=>'Success');
    }

    /**
     * Determine whether the current user can assign the given user to the given track.
     *
     * @param  int $trkid  The ID of the track.
     * @param  int $userid The ID of the user.
     * @return bool Whether the user can assign (true) or not (false)
     */
    protected function can_assign($trkid, $userid) {
        global $USER;
        return usertrack::can_manage_assoc($userid, $trkid);
    }
}