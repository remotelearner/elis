<?php
defined('MOODLE_INTERNAL') || die();

require_once elispm::lib('page.class.php');
require_once elispm::lib('lib.php');
require_once elispm::file('form/resultsconfigform.class.php');

class resultsconfigpage extends pm_page {

    var $pagename = 'resultsconfig';
    var $section = 'admn';
    var $form_class = 'resultsconfigform';
    private $customdata = array();

    function can_do_default() {
        $context = get_context_instance(CONTEXT_SYSTEM);
        return has_capability('elis/program:config', $context);
    }

    function build_navbar_default() {
        global $CFG;
        $page = $this->get_new_page(array('action' => 'default'), true);
        $this->navbar->add(get_string('learningplan', 'elis_program'), "{$CFG->wwwroot}/elis/program/");
        $this->navbar->add(get_string('results_engine_defaults_config','elis_program'), $page->url);
    }

    function get_title_default() {
        return get_string('results_engine_defaults_config','elis_program');
    }

    function normalize_submitted_data($raw_submitted_data) {
        $ranges=array();
        foreach ($raw_submitted_data as $k => $v) {
            $kparts=explode('_',$k);
            if ($kparts[0] != 'textgroup' || !is_number($kparts[1])) {
                continue;
            }
            $i=$kparts[1];
            $ranges[$i]=array(
                    	'min'=>$v['mininput'],
                    	'max'=>$v['maxinput'],
                    	'name'=>$v['nameinput']
            );
        }
        return $ranges;
    }

    function do_default() {
        global $CFG, $DB;
        //get saved defaults
        $defaults=get_config('elis_program','results_engine_defaults');
        $defaults=(!empty($defaults))?unserialize($defaults):array();
        $saved_row_count=(!empty($defaults))?sizeof($defaults):1;

        $this->customdata=array('nrc'=>optional_param('rowcount',$saved_row_count,PARAM_INT),'defaults'=>$defaults);

        $form = $this->get_form();
        $data = (array)$form->get_data();

        if (!empty($data) && isset($data['finalize'])) {
            //form being saved
            $data=serialize($this->normalize_submitted_data($data));
            pm_set_config('results_engine_defaults', $data);
            $target = $this->get_new_page(array('s'=>'resultsconfig','action' => 'default'), false);
            redirect($target->url);
            return;
        } else {
            $this->display('default');
        }
    }

    function display_default() {

        $form = $this->get_form();
        $form->display();
    }

    function get_form() {
        $target = $this->get_new_page(array('s'=>'resultsconfig','action' => 'default'));
        $form = new $this->form_class($target->url,$this->customdata);
        return $form;
    }
}