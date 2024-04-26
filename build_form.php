<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->libdir.'/formslib.php');

class format_bulkcertification_build_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        $objective = null;
        if (property_exists($this->_data, 'objective')) {
            $objective  = $this->_data->objective;
        }

        $group = null;
        if (property_exists($this->_data, 'group')) {
            $group      = $this->_data->group;
        }

        $html_users = '';

        if (!$objective) {
            $objective = new stdClass();
            $objective->name = '';
            $objective->hours = '';
        }

        //General options
        $mform->addElement('header', 'courseoptions', get_string('courseoptions', 'format_bulkcertification'));

        if (is_object($group)) {
            $vars = new stdClass();
            $vars->local = $objective->hours;
            $vars->remote = $group->objective->hours;
            $hours = get_string('hours_multi', 'format_bulkcertification', $vars);

            $vars = new stdClass();
            $vars->local = $objective->name;
            $vars->remote = $group->objective->name;
            $coursename = get_string('course_multi', 'format_bulkcertification', $vars);

            $this->_data->objectivedate = $group->objective->enddate;

            $table = new html_table();
            $table->attributes['class'] = 'generaltable';
            $table->cellspacing = 0;

            $table->head = array();
            $table->head[] = get_string('username');
            $table->head[] = get_string('firstname');
            $table->head[] = get_string('lastname');
            $table->head[] = get_string('email');

            foreach($group->users as $user){

                $data = array ();
                $data[] = $user->username;
                $data[] = $user->firstname;
                $data[] = $user->lastname;
                $data[] = $user->email;

                $table->data[] = $data;
            }

            $html_users = html_writer::table($table);

        } else {
            $hours = $objective->hours;
            $coursename = $objective->name;
        }

        $mform->addElement('static', 'name', get_string('objective_name', 'format_bulkcertification'), $coursename);

        $mform->addElement('static', 'hours', get_string('objective_hours', 'format_bulkcertification'), $hours);

        $mform->addElement('date_selector', 'objectivedate', get_string('objective_date', 'format_bulkcertification'));

        $mform->addElement('checkbox', 'sendmail', get_string('sendmail', 'format_bulkcertification'));

        $mform->addElement('header', 'users', get_string('users', 'format_bulkcertification'));

        $mform->addElement('static', 'userslist', '', $html_users);

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