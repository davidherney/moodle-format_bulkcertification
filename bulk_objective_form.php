<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->libdir.'/formslib.php');

class format_bulkcertification_bulk_objective_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data = $this->_customdata['data'];
        $delimiters = $this->_customdata['delimiters'];

        //General options
        $mform->addElement('header', 'general', get_string('importobjectives', 'format_bulkcertification'));
//        $mform->setAdvanced('general', true);
        $mform->setExpanded('general', false);

        $mform->addElement('textarea', 'objectiveslist', get_string('objectiveslist', 'format_bulkcertification'), array('cols' => 80, 'rows' => 20));
        $mform->setType('objectiveslist', PARAM_TEXT);
        $mform->addRule('objectiveslist', null, 'required', null, 'client');
//        $mform->setAdvanced('objectiveslist', true);

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'format_bulkcertification'), $delimiters);

        $values = array();
        $values['add'] = get_string('bulkobjectiveadd', 'format_bulkcertification');
        $values['replace'] = get_string('bulkobjectivereplace', 'format_bulkcertification');
        $mform->addElement('select', 'mode', get_string('bulkobjectivemode', 'format_bulkcertification'), $values);

        $mform->addElement('hidden', 'id', $this->_data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'objectives');
        $mform->setType('tab', PARAM_TEXT);

        $submit = $mform->addElement('submit', 'importsubmitbutton', get_string('import', 'format_bulkcertification'));
        $mform->closeHeaderBefore('importsubmitbutton');

        // Finally set the current form data
        $this->set_data($this->_data);
    }

}