<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->libdir.'/formslib.php');

class format_bulkcertification_objective_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        //General options
        $mform->addElement('header', 'general', get_string('newobjective', 'format_bulkcertification'));
//        $mform->setAdvanced('general', true);
        $mform->setExpanded('general', false);

        $mform->addElement('text', 'objectivename', get_string('objective_name', 'format_bulkcertification'));
        $mform->setType('objectivename', PARAM_TEXT);
        $mform->addRule('objectivename', null, 'required', null, 'client');
        $mform->addRule('objectivename', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
//        $mform->setAdvanced('objectivename', true);

        $mform->addElement('text', 'code', get_string('code', 'format_bulkcertification'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addRule('code', get_string('maximumchars', '', 31), 'maxlength', 31, 'client');
//        $mform->setAdvanced('code', true);

        $mform->addElement('text', 'hours', get_string('objective_hours', 'format_bulkcertification'));
        $mform->setType('hours', PARAM_TEXT);
        $mform->addRule('hours', null, 'required', null, 'client');
        $mform->addRule('hours', null, 'numeric', null, 'client');
        $mform->addRule('hours', get_string('maximumchars', '', 4), 'maxlength', 4, 'client');
//        $mform->setAdvanced('hours', true);

        $values = array();
        $values['remote'] = get_string('type_remote', 'format_bulkcertification');
        $values['local'] = get_string('type_local', 'format_bulkcertification');
        $mform->addElement('select', 'type', get_string('objective_type', 'format_bulkcertification'), $values);

        $mform->addElement('hidden', 'id', $this->_data->id);

        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'objectives');
        $mform->setType('tab', PARAM_TEXT);

        $submit = $mform->addElement('submit', 'importsubmitbutton', get_string('add_objective', 'format_bulkcertification'));
        $mform->closeHeaderBefore('importsubmitbutton');

        // Finally set the current form data
        $this->set_data($this->_data);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($objective = $DB->get_record('bulkcertification_objectives', array('code' => $data['code']), '*', IGNORE_MULTIPLE)) {
            $errors['code'] = get_string('codetaken', 'format_bulkcertification', $objective->code);
        }

        return $errors;
    }
}