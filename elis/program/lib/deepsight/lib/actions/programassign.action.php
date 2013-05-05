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

require_once(elispm::lib('data/clusterassignment.class.php'));

/**
 * An action to assign a user to a program.
 */
class deepsight_action_programassign extends deepsight_action_confirm {
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
            : get_string('ds_action_programassign_confirm', 'elis_program');
        $this->desc_multiple = (!empty($desc_multiple))
            ? $desc_multiple
            : get_string('ds_action_programassign_confirm_multi', 'elis_program');
    }

    /**
     * Assign the user to the program.
     *
     * @param array $elements    An array of elements to perform the action on.
     * @param bool  $bulk_action Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulk_action) {
        global $DB;
        $pgmid = required_param('id', PARAM_INT);

        // Permissions.
        if (curriculumpage::can_enrol_into_curriculum($pgmid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'elis_program'));
        }

        foreach ($elements as $userid => $label) {
            if ($this->can_assign($pgmid, $userid) !== true) {
                continue;
            }
            $stucur = new curriculumstudent(array('userid' => $userid, 'curriculumid' => $pgmid));
            $stucur->save();
        }

        return array('result' => 'success', 'msg'=>'Success');
    }

    /**
     * Determines whether the active user can assign the given userid to the given programid
     *
     * @param  int $pgmid  The ID of the program.
     * @param  int $userid The ID of the user.
     * @return bool Whether they have permission (true) or not (false)
     */
    protected function can_assign($pgmid, $userid) {
        global $USER;

        $cpage = new curriculumpage();
        if (!$cpage->_has_capability('elis/program:program_enrol', $pgmid)) {
            // Perform SQL filtering for the more "conditional" capability.
            $context = pm_context_set::for_user_with_capability('cluster', 'elis/program:program_enrol_userset_user', $USER->id);

            $allowedclusters = array();

            // Get the clusters assigned to this curriculum.
            $clusters = clustercurriculum::get_clusters($pgmid);
            if (!empty($clusters)) {
                foreach ($clusters as $cluster) {
                    if ($context->context_allowed($cluster->clusterid, 'cluster')) {
                        $allowedclusters[] = $cluster->clusterid;
                    }
                }
            }

            if (empty($allowedclusters)) {
                return array(array(), 0);
            } else {
                $clusterfilter = implode(',', $allowedclusters);
                $sql = 'SELECT userid FROM {'.clusterassignment::TABLE.'} WHERE userid=? AND clusterid IN ('.$clusterfilter.'))';
                $params = array($userid);
                $result = $DB->get_record_sql($sql, $params);
                return (!empty($result)) ? true : false;
            }
        } else {
            return true;
        }
    }
}