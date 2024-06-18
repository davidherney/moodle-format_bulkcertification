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
 * Class containing form definition to manage an objective.
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
 * The form for handling editing an objective.
 *
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective extends moodleform {

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

        // General options.
        $mform->addElement('header', 'general', get_string('newobjective', 'format_bulkcertification'));
        $mform->setExpanded('general', false);

        $mform->addElement('text', 'objectivename', get_string('objective_name', 'format_bulkcertification'));
        $mform->setType('objectivename', PARAM_TEXT);
        $mform->addRule('objectivename', null, 'required', null, 'client');
        $mform->addRule('objectivename', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'code', get_string('objective_code', 'format_bulkcertification'));
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addRule('code', get_string('maximumchars', '', 31), 'maxlength', 31, 'client');

        $mform->addElement('text', 'hours', get_string('objective_hours', 'format_bulkcertification'));
        $mform->setType('hours', PARAM_TEXT);
        $mform->addRule('hours', null, 'required', null, 'client');
        $mform->addRule('hours', null, 'numeric', null, 'client');
        $mform->addRule('hours', get_string('maximumchars', '', 4), 'maxlength', 4, 'client');

        $options = \format_bulkcertification\entities\objective::get_options_for_type();
        $mform->addElement('select', 'type', get_string('objective_type', 'format_bulkcertification'), $options);

        $mform->addElement('hidden', 'id', $this->_data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', \format_bulkcertification::ACTION_OBJECTIVES);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'op', \format_bulkcertification::OP_ADD);
        $mform->setType('op', PARAM_TEXT);

        $mform->addElement('submit', 'importsubmitbutton', get_string('addobjective', 'format_bulkcertification'));
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
        $params = ['courseid' => $data['id'], 'code' => $data['code']];
        if ($DB->get_record('bulkcertification_objectives', $params, '*', IGNORE_MULTIPLE)) {
            $errors['code'] = get_string('codetaken', 'format_bulkcertification', $data['code']);
        }

        $type = isset($data['type']) ? $data['type'] : '';
        $types = \format_bulkcertification\entities\objective::get_options_for_type();
        if (!in_array($type, array_keys($types))) {
            $errors['type'] = get_string('invalidtype', 'format_bulkcertification');
        }

        return $errors;
    }
}