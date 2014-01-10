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
 * @package    local_datahub
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/local/eliscore/lib/setup.php');
require_once($CFG->dirroot .'/local/datahub/lib.php');
require_once($CFG->dirroot .'/local/datahub/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot .'/local/datahub/form/rlip_schedule_form.class.php');

require_once elis::lib('page.class.php');

class ip_schedule_page extends elis_page {
    protected $type; // IP type: 'dhimport' or 'dhexport'
    protected $name; // IP name: 'version1'
    var       $import_form_class = 'rlip_import_schedule_form';
    var       $export_form_class = 'rlip_export_schedule_form';

    private function get_base_page_params() {
        $plugin = $this->required_param('plugin', PARAM_CLEAN);
        list($this->type, $this->name) = explode('_', $plugin);
    }

    private function get_ip_plugin() {
        return "{$this->type}_{$this->name}";
    }

    protected function _get_page_url() {
        global $CFG;
        return "{$CFG->wwwroot}/local/datahub/schedulepage.php";
    }

    private function add_submit_cancel_buttons($submiturl, $submitlabel,
                                               $cancelurl = null, $cancellabel = '') {
        global $OUTPUT;
        echo $OUTPUT->single_button($submiturl, $submitlabel);
        if ($cancelurl) {
            if (empty($cancellabel)) {
                $cancellabel = get_string('cancel');
            }
            echo $OUTPUT->single_button($cancelurl, $cancellabel);
        }
    }

    function get_page_title_default() {
        // TBD: could check param 'plugin' & show different import/export title
        return get_string('schedulepagetitle', 'local_datahub');
    }

    function build_navbar_default() {
        global $CFG;

        //add navigation items
        $this->navbar->add(get_string('administrationsite'));
        $this->navbar->add(get_string('plugins', 'admin'));
        $this->navbar->add(get_string('localplugins'));
        $this->navbar->add(get_string('plugins', 'local_datahub'));
        $this->navbar->add(get_string('rlipmanageplugins', 'local_datahub'), new moodle_url('/local/datahub/plugins.php'));
        $this->navbar->add(get_string('schedulepagetitle', 'local_datahub'), null);
    }

    function can_do_default() {
        if (has_capability('moodle/site:config', context_system::instance())) {
            // Ensure that scheduling is setup correctly
            rlip_scheduling_init();

            return true;
        }

        return false;
    }

    function display_default() {
        global $OUTPUT, $USER;
        $this->get_base_page_params();
        $display_name = $this->get_ip_plugin(); // TBD: more user-friendly
        $ipscheds = rlip_get_scheduled_jobs($this->get_ip_plugin(),
                                            is_siteadmin() ? 0 : $USER->id);
        if (!empty($ipscheds) && $ipscheds->valid()) {
            echo $OUTPUT->notification(get_string("rlip_jobs_heading_jobs",
                                                  'local_datahub', get_string('pluginname', $display_name)),
                                       'rlip_bold_header', 'left');
            echo $OUTPUT->notification(get_string('rlip_jobs_heading_fullinstructions',
                                                  'local_datahub', $display_name),
                                       'rlip_italic_header', 'left');
            $table = new html_table();
            $table->head = array(
                          get_string('rlip_jobs_header_label', 'local_datahub'),
                          get_string('rlip_jobs_header_owner', 'local_datahub'),
                          get_string('rlip_jobs_header_lastran', 'local_datahub'),
                          get_string('rlip_jobs_header_nextrun', 'local_datahub'),
                          get_string('rlip_jobs_header_modified', 'local_datahub'),
                          '' // Actions: Edit/Delete
                         );
            $table->align      = array('left', 'center', 'left', 'left', 'left', 'center');
            $table->size       = array('5%', '15%', '25%', '25%', '25%', '5%');
            $table->data       = array();
            $table->rowclasses = array(); //TBD
            $edit = get_string('edit');
            $delete = get_string('delete');
            foreach ($ipscheds as $ipjob) {
                $ustr = "{$ipjob->username}<br/>(". fullname($ipjob) .')';
                $tz   = $ipjob->timezone;
                $data = unserialize($ipjob->config);
                $lastruntime = !empty($ipjob->lastruntime)
                               ? userdate($ipjob->lastruntime, '', $tz)
                                 .' (' . usertimezone($tz) .')'
                               : get_string('no_lastruntime', 'local_datahub');
                $nextruntime = !empty($ipjob->nextruntime)
                               ? userdate($ipjob->nextruntime, '', $tz)
                                 .' (' . usertimezone($tz) .')'
                               : get_string('na', 'local_datahub');
                $modified = !empty($data['timemodified'])
                            ? userdate($data['timemodified'], '', $tz)
                              .' (' . usertimezone($tz) .')'
                            : get_string('na', 'local_datahub');
                $target = $this->get_new_page(array('id'     => $ipjob->id,
                                                    'plugin' => $ipjob->plugin));
                $label = '<a name="edit" href="'. $target->url->out(true, array('action' => 'form')) .'">'. $data['label'] .'</a>';
                $edit_link = '<a name="edit" href="'. $target->url->out(true, array('action' => 'form')) .'"><img alt="'. $edit .'" title="'. $edit .'" src="'. $OUTPUT->pix_url('t/edit') .'" /></a>';
                $delete_link = '<a name="delete" href="'. $target->url->out(true, array('action' => 'delete')) .'"><img alt="'. $delete .'" title="'. $delete .'" src="'. $OUTPUT->pix_url('t/delete') .'" /></a>';
                $table->rowclasses[] = ''; //TBD
                $table->data[] = array($label, $ustr, $lastruntime,
                                       $nextruntime, $modified,
                                       "{$edit_link}&nbsp;{$delete_link}"
                                 );
            }
            echo html_writer::table($table);

            echo $OUTPUT->notification(get_string('schedulingtime',
                                                  'local_datahub', $display_name),
                                       'rlip_italic_header', 'left');
        } else {
            echo $OUTPUT->notification(get_string('rlip_jobs_heading_nojobs',
                                                  'local_datahub', get_string('pluginname', $display_name)),
                                       'rlip_bold_header', 'left');
            echo $OUTPUT->notification(get_string('rlip_jobs_heading_instructions',
                                                  'local_datahub', $display_name),
                                       'rlip_italic_header', 'left');
        }
        echo $OUTPUT->spacer();
        $submit = $this->get_new_page(
                             array('action' => 'form',
                                   'plugin' => $this->get_ip_plugin()));
        $this->add_submit_cancel_buttons($submit->url, get_string('rlip_new_job',
                                                                  'local_datahub'));
    }

    function display_delete() {
        global $DB, $OUTPUT;
        $this->get_base_page_params();
        $id = $this->required_param('id', PARAM_INT);
        $confirm = $this->optional_param('confirm', 0, PARAM_INT);
        if ($confirm) {
            rlip_schedule_delete_job($id);
            $this->display_default();
        } else {
            $target = $this->get_new_page(array('id'     => $id,
                                                'plugin' => $this->get_ip_plugin()));
            $continue_url = new moodle_url($target->url->out(true,
                                          array('action' => 'delete',
                                                'confirm' => 1)));
            $buttoncontinue = new single_button($continue_url, get_string('yes'));
            $cancel_url = new moodle_url($target->url->out(true,
                                          array('action' => 'default')));
            $buttoncancel = new single_button($cancel_url, get_string('no'));
            echo $OUTPUT->confirm(get_string('confirm_delete_ipjob', 'local_datahub', $id),
                                  $buttoncontinue, $buttoncancel);
        }
    }

    function display_form() {
        global $DB;

        $this->get_base_page_params();
        $id = $this->optional_param('id', 0, PARAM_INT);
        $target = $this->get_new_page(
                             array('action' => 'form',
                                   'plugin' => $this->get_ip_plugin()));
        $params = array('plugin' => $this->get_ip_plugin(),
                        'type' => $this->type,
                        'name' => $this->name,
                        'id'   => $id);
        if (!empty($id) && ($ipjob = $DB->get_record(RLIP_SCHEDULE_TABLE,
                                               array('id' => $id)))) {
            $data = unserialize($ipjob->config);
            unset($data['id']);
            $params = array_merge($params, $data);
        }
        $form = ($this->type == 'dhimport')
                ? new $this->import_form_class($target->url, $params)
                : new $this->export_form_class($target->url, $params);
        $form->set_data($params);
        if ($form->is_cancelled()) {
            $this->display_default();
            return;
        }

        $data = $form->get_data();
        if ($data && rlip_schedule_add_job((array)$data) > 0) {
            $target = $this->get_new_page(array('plugin' => $this->get_ip_plugin()));
            redirect($target->url);
        } else {
            $form->display();
        }
    }

    function err_dump($var) {
        ob_start();
        var_dump($var);
        $tmp = ob_get_contents();
        ob_end_clean();
        return $tmp;
    }
}

$PAGE = new ip_schedule_page();
$PAGE->requires->css('/local/datahub/styles.css');
$PAGE->run();
