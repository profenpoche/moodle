<?php

namespace local_sonar_analyzer\form;

use moodleform;

require_once("$CFG->libdir/formslib.php");

class upload extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('filemanager', 'codefile', 'codefile', null, array('accepted_types' => '*'));
        $mform->addRule('codefile', null, 'required', null, 'client');
        $checkboxarray = array();
        $checkboxarray[] = $mform->createElement('checkbox', 'enableDocx', '', 'DOCX génération');
        $checkboxarray[] = $mform->createElement('checkbox', 'enableMd', '', 'MD génération');
        $checkboxarray[] = $mform->createElement('checkbox', 'enableXlsx', '', 'XLSX génération');
        $checkboxarray[] = $mform->createElement('checkbox', 'enableCsv', '', 'CSV génération');

        $mform->addGroup($checkboxarray, 'checkboxar', '', array(' '), false);

        $mform->setDefault('enableDocx', 1);
        $mform->setDefault('enableMd', 1);
        $mform->setDefault('enableXlsx', 1);
        $mform->setDefault('enableCsv', 1);
        $this->add_action_buttons(true, 'submit');
    }

    function validation($data, $files)
    {
        $errors = array();

        if (empty($data['enableDocx']) && empty($data['enableMd']) && empty($data['enableXlsx']) && empty($data['enableCsv'])) {
            $errors['checkboxar'] = 'Sélectionnez au moins une option de génération.';
        }

        return $errors;
    }
}
