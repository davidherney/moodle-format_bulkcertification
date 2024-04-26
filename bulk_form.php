<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->libdir.'/formslib.php');

class format_bulkcertification_bulk_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        //General options
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $certificates = $this->get_certificates();

        if (count($certificates) == 0) {
            $mform->addElement('static', 'note', '', get_string('certificates_notfound', 'format_bulkcertification'));
            return;
        }

        $mform->addElement('select', 'template', get_string('template', 'format_bulkcertification'), $certificates);
        $mform->addHelpButton('template', 'template', 'format_bulkcertification');

        $mform->addElement('text', 'groupcode', get_string('groupcode', 'format_bulkcertification'));
        $mform->setType('groupcode', PARAM_TEXT);
        $mform->addRule('groupcode', null, 'required', null, 'client');
        $mform->addHelpButton('groupcode', 'groupcode', 'format_bulkcertification');

        $mform->addElement('hidden', 'id', $this->_data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'bulk');
        $mform->setType('tab', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search', 'format_bulkcertification'));

        // Finally set the current form data
        $this->set_data($this->_data);
    }

    private function get_certificates() {
        global $COURSE, $DB;

        $module = $DB->get_record('modules', array('name' => 'simplecertificate'));

//        $list = $DB->get_records_menu('simplecertificate', array('course' => $COURSE->id), 'name', 'id, name');
        $list = $DB->get_records('course_modules', array('course' => $COURSE->id, 'module' => $module->id));

        $certificates = array();
        foreach($list as $key => $one) {
            //list ($notused, $cm) = get_course_and_cm_from_cmid($key, 'simplecertificate');
            $cm = get_coursemodule_from_id( 'simplecertificate', $key);

            if ($cm->visible) {
                $certificates[$cm->id] = $cm->name;
            }
        }

        natsort($certificates);

        return $certificates;
    }
}