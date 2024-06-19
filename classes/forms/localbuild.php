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
 * Class containing form definition to manage local data to build.
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
 * The form for handling the local data source.
 *
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class localbuild extends moodleform {

    /**
     * @var object List of local data.
     */
    protected $_data;

    /**
     * @var array List of delimiters.
     */
    public const DELIMITERS = ['t' => "\t", ',' => ',', ';' => ';'];

    /**
     * Form definition.
     */
    public function definition() {

        $mform = $this->_form;

        $areaprops = ['rows' => 10, 'cols' => 80, 'style' => 'width: 100%'];

        // This contains the data of this form.
        $this->_data = $this->_customdata['data'];

        //General options
        $mform->addElement('header', 'courseoptions', get_string('courseoptions', 'format_bulkcertification'));

        if (property_exists($this->_data, 'group')) {
            $hours = $this->_data->group->hours;
            $coursename = $this->_data->group->name;
            $templatename = $this->_data->templatename;
        } else {
            $hours = 0;
            $coursename = '';
            $templatename = '';
        }

        $mform->addElement('static', 'name', get_string('objective_name', 'format_bulkcertification'), $coursename);

        $mform->addElement('static', 'hours', get_string('objective_hours', 'format_bulkcertification'), $hours);

        $mform->addElement('static', 'templatename', get_string('template', 'format_bulkcertification'), $templatename);

        $mform->addElement('date_selector', 'objectivedate', get_string('objective_date', 'format_bulkcertification'));

        $mform->addElement('checkbox', 'sendmail', get_string('sendmail', 'format_bulkcertification'));

        $mform->addElement('header', 'users', get_string('users', 'format_bulkcertification'));

        $mform->addElement('textarea', 'userslist', get_string('userslist', 'format_bulkcertification'), $areaprops);
        $mform->setType('userslist', PARAM_TEXT);
        $mform->addRule('userslist', null, 'required', null, 'client');
        $mform->addHelpButton('userslist', 'userslist', 'format_bulkcertification');

        $delimiters = self::DELIMITERS;
        $delimiters['t'] = '\t'; // Change the key to show the delimiter in the select box.
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'format_bulkcertification'), $delimiters);

        $mform->addElement('textarea', 'customparams', get_string('customparams', 'format_bulkcertification'), $areaprops);
        $mform->setType('customparams', PARAM_TEXT);
        $mform->addHelpButton('customparams', 'customparams', 'format_bulkcertification');

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
