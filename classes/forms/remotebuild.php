<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class containing form definition to manage users data to certificate.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_bulkcertification\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use moodleform;

/**
 * The form for handling the remote data source.
 *
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remotebuild extends moodleform {

    /**
     * @var object List of local data.
     */
    protected $_data;

    /**
     * Form definition.
     */
    public function definition() {

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        if (property_exists($this->_data, 'objective')) {
            $hours = $this->_data->objective->hours;
            $coursename = $this->_data->objective->name;
            $templatename = $this->_data->templatename;
        } else {
            $hours = 0;
            $coursename = '';
            $templatename = '';
        }

        $group = null;
        if (property_exists($this->_data, 'group')) {
            $group = $this->_data->group;
        }

        $html_users = '';

        //General options
        $mform->addElement('header', 'courseoptions', get_string('courseoptions', 'format_bulkcertification'));

        if (is_object($group)) {
            $vars = new \stdClass();
            $vars->local = $hours;
            $vars->remote = $group->objective->hours;
            $hours = get_string('hours_multi', 'format_bulkcertification', $vars);

            $vars = new \stdClass();
            $vars->local = $coursename;
            $vars->remote = $group->objective->name;
            $coursename = get_string('course_multi', 'format_bulkcertification', $vars);

            $this->_data->objectivedate = $group->objective->enddate;

            $table = new \html_table();
            $table->attributes['class'] = 'generaltable';

            $table->head = [];
            $table->head[] = get_string('username');
            $table->head[] = get_string('firstname');
            $table->head[] = get_string('lastname');
            $table->head[] = get_string('email');

            foreach($group->users as $user){

                $data = [];
                $data[] = $user->username;
                $data[] = $user->firstname;
                $data[] = $user->lastname;
                $data[] = $user->email;

                $table->data[] = $data;
            }

            $html_users = \html_writer::table($table);

        } else {
            $hours = $hours;
            $coursename = $coursename;
        }

        $mform->addElement('static', 'name', get_string('objective_name', 'format_bulkcertification'), $coursename);

        $mform->addElement('static', 'hours', get_string('objective_hours', 'format_bulkcertification'), $hours);

        $mform->addElement('date_selector', 'objectivedate', get_string('objective_date', 'format_bulkcertification'));

        $mform->addElement('checkbox', 'sendmail', get_string('sendmail', 'format_bulkcertification'));

        $mform->addElement('header', 'users', get_string('users', 'format_bulkcertification'));

        $mform->addElement('static', 'userslist', '', $html_users);

        $mform->addElement('hidden', 'code');
        $mform->setType('code', PARAM_INT);

        $mform->addElement('hidden', 'template');
        $mform->setType('template', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', \format_bulkcertification::ACTION_BULK);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'op', \format_bulkcertification::OP_SAVE);
        $mform->setType('op', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('build', 'format_bulkcertification'));

        // Finally set the current form data.
        $this->set_data($this->_data);
    }

}
