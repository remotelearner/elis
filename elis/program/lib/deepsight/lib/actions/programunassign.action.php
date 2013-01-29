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
 * An action to unassign a user from a program.
 */
class deepsight_action_programunassign extends deepsight_action_confirm {
    public $label = 'Unassign User';
    public $icon = 'icon-exit';

    /**
     * Constructor.
     *
     * Sets internal data.
     */
    public function __construct(moodle_database &$DB, $name, $desc_single='', $desc_multiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('unassign', 'elis_program'));
        $this->desc_single = (!empty($desc_single))
            ? $desc_single
            : get_string('ds_action_programunassign_confirm', 'elis_program');
        $this->desc_multiple = (!empty($desc_multiple))
            ? $desc_multiple
            : get_string('ds_action_programunassign_confirm_multi', 'elis_program');
    }

    /**
     * Unassign the user from the program.
     *
     * @param array $elements    An array of elements to perform the action on.
     * @param bool  $bulk_action Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulk_action) {
        global $DB;
        $pgmid = required_param('id', PARAM_INT);

        // Permissions.
        $cpage = new curriculumpage();
        if ($cpage->_has_capability('elis/program:program_view', $pgmid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'elis_program'));
        }

        foreach ($elements as $userid => $label) {
            $assignrec = $DB->get_record(curriculumstudent::TABLE, array('userid' => $userid, 'curriculumid' => $pgmid));
            if (!empty($assignrec)) {
                $curstu = new curriculumstudent($assignrec->id);
                $curstu->load();
                $curstu->delete();
            }
        }

        return array('result' => 'success', 'msg'=>'Success');
    }
}