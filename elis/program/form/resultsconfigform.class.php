<?php

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/lib/formslib.php');

class resultsconfigform extends moodleform {

    function definition() {
        global $OUTPUT, $PAGE;
        $PAGE->requires->js('/elis/program/js/results_engine/jquery-1.6.2.min.js', true);
        $PAGE->requires->js('/elis/program/js/results_engine/results_config.js', true);

        $mform =& $this->_form;
        $cd=(array)$this->_customdata;
        $cd['nrc']=(isset($cd['nrc']))?$cd['nrc']:1;
        $mform->addElement('header', 'activationrules', get_string('results_engine_defaults_config','elis_program'));
        for($i=1;$i<=$cd['nrc'];$i++){
            $this->generate_row($i,$mform);
        }

        $mform->addElement('hidden', 'rowcount',$cd['nrc'],array('id'=>'rowcount'));

        $mform->addElement('submit','addrange',get_string('results_add_another_score_btn', 'elis_program'),array('onclick'=>'$(\'#rowcount\').val(parseInt($(\'#rowcount\').val())+1)'));
        $mform->addElement('submit','finalize',get_string('savechanges'));
    }
/*
    function get_raw_dynamic_data() {
         return ($this->_form->_submitValues);
    }

    function get_dynamic_data() {
        return $this->normalize_submitted_data($this->get_raw_dynamic_data());
    }
*/
    function generate_row($i,&$mform,$id='d') {
        global $OUTPUT;

        $textgroup=array();
        $textgroup[]=&$mform->createElement('static','grouplabel','','Range '.$i.':');
        $textgroup[]=&$mform->createElement('static','minlabel','','Min');
        $textgroup[]=&$mform->createElement('text','mininput','Min',array('size'=>5));
        $textgroup[]=&$mform->createElement('static','maxlabel','','Max');
        $textgroup[]=&$mform->createElement('text','maxinput','Max',array('size'=>5));
        $textgroup[]=&$mform->createElement('static','namelabel','','Name');
        $textgroup[]=&$mform->createElement('text','nameinput','Name');

        $textgroup[]=&$mform->createElement('static','deleteLink','','<img src="'.$OUTPUT->pix_url('delete','elis_program').'" onclick="delete_row('.$i.',$(this))" alt="Delete" style="cursor:pointer" title="Delete" /></a>');

        $mform->addGroup($textgroup,'textgroup_'.$i);
        if (!empty($this->_customdata['defaults'][$i]))
        {
             $fields=array('min','max','name');
             foreach ($fields as $f) {
                 if (isset($this->_customdata['defaults'][$i][$f])) {
                     $mform->setDefault('textgroup_'.$i.'['.$f.'input]',$this->_customdata['defaults'][$i][$f]);
                 }
             }
        }
    }


}

/*$fields=array('mininput','maxinput','nameinput');
foreach ($fields as $field) {
$$field=(isset($v[$field.'_'.$i]))?$v[$field.'_'.$i]:'';
}
*/