<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->libdir.'/formslib.php');

class format_bulkcertification_localbuild_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        // This contains the data of this form.
        $this->_data = $this->_customdata['data'];
        $delimiters = $this->_customdata['delimiters'];

        $objective = null;
        if (property_exists($this->_data, 'objective')) {
            $objective  = $this->_data->objective;
        }

        $html_users = '';

        if (!$objective) {
            $objective = new stdClass();
            $objective->name = '';
            $objective->hours = '';
        }

        //General options
        $mform->addElement('header', 'courseoptions', get_string('courseoptions', 'format_bulkcertification'));

        $hours = $objective->hours;
        $coursename = $objective->name;

        $mform->addElement('static', 'name', get_string('objective_name', 'format_bulkcertification'), $coursename);

        $mform->addElement('static', 'hours', get_string('objective_hours', 'format_bulkcertification'), $hours);

        $mform->addElement('date_selector', 'objectivedate', get_string('objective_date', 'format_bulkcertification'));

        $mform->addElement('checkbox', 'sendmail', get_string('sendmail', 'format_bulkcertification'));

        $mform->addElement('header', 'users', get_string('users', 'format_bulkcertification'));

        $mform->addElement('textarea', 'userslist', get_string('userslist', 'format_bulkcertification'), array('cols' => 80, 'rows' => 20));
        $mform->setType('userslist', PARAM_TEXT);
        $mform->addRule('userslist', null, 'required', null, 'client');
        $mform->addHelpButton('userslist', 'userslist', 'format_bulkcertification');

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'format_bulkcertification'), $delimiters);

        $mform->addElement('textarea', 'customparams', get_string('customparams', 'format_bulkcertification'), array('cols' => 80, 'rows' => 10));
        $mform->setType('customparams', PARAM_TEXT);
        $mform->addHelpButton('customparams', 'customparams', 'format_bulkcertification');

        $mform->addElement('hidden', 'groupcode');
        $mform->setType('groupcode', PARAM_TEXT);

        $mform->addElement('hidden', 'template');
        $mform->setType('template', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'bulk');
        $mform->setType('tab', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('build', 'format_bulkcertification'));

        // Finally set the current form data.
        $this->set_data($this->_data);
    }

}
