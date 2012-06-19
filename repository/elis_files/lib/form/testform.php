<?php

require_once(dirname(__FILE__) .'/../../../../config.php');

global $CFG, $OUTPUT, $PAGE, $USER;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('./alfresco_filemanager.php');

class alfreso_test_form extends moodleform {
    var $afm_elem = null;

    function definition() {
        $mform = & $this->_form;
        $fp_options = array('maxfiles'   => -1,
                            'maxbytes'   => 1000,
                      );
        $attrs = array('id' => 'id_test_alfresco_file_manager',
                       'name' => 'test_alfresco_file_manager'
                 );
        $this->afm_elem = $mform->createElement('alfresco_filemanager',
                             'test_alfresco_file_manager',
                             '<b>Alfresco File Manager Form Element</b>',
                             $attrs, $fp_options);
        $mform->addElement($this->afm_elem);
    }

  /*
    function unfreeze() {
        if ($this->afm_elem) {
            $this->afm_elem->unfreeze();
        }
    }
  */
}

$PAGE->set_context(get_context_instance(CONTEXT_COURSE, 3));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading('<b>Alfresco File Manager</b>');
$PAGE->set_url('/repository/elis_files/lib/form/testform.php');

echo $OUTPUT->header();

$form = new alfreso_test_form(new moodle_url('/repository/elis_files/lib/form/testform.php'));
//$form->unfreeze();
$form->display();

echo $OUTPUT->footer();

