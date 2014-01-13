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

namespace local_elisprogram\install\migration;

/**
 * Migrates components from one component to another.
 */
class elis26 extends migrator {

    /**
     * Constructor.
     *
     * @param string $oldcomponent Not used, overridden by 'elis_program'.
     * @param string $newcomponent Not used, overridden by 'local_elisprogram'.
     * @param string $upgradestepfuncname Not used, overridden by 'local_elisprogram_upgrade_old_tables'.
     * @param array $tablechanges Not used, overridden by elis 2.6 table changes.
     */
    public function __construct($oldcomponent = '', $newcomponent = '', $upgradestepfuncname = '', array $tablechanges = array()) {
        $oldcomponent = 'elis_program';
        $newcomponent = 'local_elisprogram';
        $upgradestepfuncname = 'local_elisprogram_upgrade_old_tables';
        $tablechanges = array(
            'crlm_certificate_issued' => 'local_elisprogram_certissued',
            'crlm_certificate_settings' => 'local_elisprogram_certcfg',
            'crlm_class' => 'local_elisprogram_cls',
            'crlm_class_enrolment' => 'local_elisprogram_cls_enrol',
            'crlm_class_graded' => 'local_elisprogram_cls_graded',
            'crlm_class_instructor' => 'local_elisprogram_cls_nstrct',
            'crlm_class_moodle' => 'local_elisprogram_cls_mdl',
            'crlm_cluster' => 'local_elisprogram_uset',
            'crlm_cluster_assignments' => 'local_elisprogram_uset_asign',
            'crlm_cluster_classification' => 'elisprogram_usetclassify',
            'crlm_cluster_curriculum' => 'local_elisprogram_uset_pgm',
            'crlm_cluster_profile' => 'local_elisprogram_uset_prfle',
            'crlm_cluster_track' => 'local_elisprogram_uset_trk',
            'crlm_config' => 'local_elisprogram_config',
            'crlm_course' => 'local_elisprogram_crs',
            'crlm_coursetemplate' => 'local_elisprogram_crs_tpl',
            'crlm_course_completion' => 'local_elisprogram_crs_cmp',
            'crlm_course_corequisite' => 'local_elisprogram_crs_coreq',
            'crlm_course_prerequisite' => 'local_elisprogram_crs_prereq',
            'crlm_curriculum' => 'local_elisprogram_pgm',
            'crlm_curriculum_assignment' => 'local_elisprogram_pgm_assign',
            'crlm_curriculum_course' => 'local_elisprogram_pgm_crs',
            'crlm_environment' => 'local_elisprogram_env',
            'crlm_notification_log' => 'local_elisprogram_notifylog',
            'crlm_results' => 'local_elisprogram_res',
            'crlm_results_action' => 'local_elisprogram_res_action',
            'crlm_results_class_log' => 'local_elisprogram_res_clslog',
            'crlm_results_student_log' => 'local_elisprogram_res_stulog',
            'crlm_tag' => 'local_elisprogram_tag',
            'crlm_tag_instance' => 'local_elisprogram_tag_inst',
            'crlm_track' => 'local_elisprogram_trk',
            'crlm_track_class' => 'local_elisprogram_trk_cls',
            'crlm_user' => 'local_elisprogram_usr',
            'crlm_usercluster' => 'local_elisprogram_usr_uset',
            'crlm_user_moodle' => 'local_elisprogram_usr_mdl',
            'crlm_user_track' => 'local_elisprogram_usr_trk',
            'crlm_wait_list' => 'local_elisprogram_waitlist',
        );
        parent::__construct($oldcomponent, $newcomponent, $upgradestepfuncname, $tablechanges);
    }

    protected function run_old_upgrade_steps_if_necessary() {
        require_once(dirname(__FILE__).'/elis26_oldupgradesteps.php');
        parent::run_old_upgrade_steps_if_necessary();
    }
}