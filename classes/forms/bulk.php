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
 * Class containing form definition to manage the bulk operation.
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
 * The form for handling the bulk information.
 *
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk extends moodleform {

    /**
     * @var object List of local data.
     */
    protected $_data;

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        $certificates = $this->get_certificates();

        if (count($certificates) == 0) {
            $mform->addElement('static', 'note', '', get_string('certificates_notfound', 'format_bulkcertification'));
            return;
        }

        $mform->addElement('select', 'template', get_string('template', 'format_bulkcertification'), $certificates);
        $mform->addHelpButton('template', 'template', 'format_bulkcertification');

        $codes = $DB->get_records_menu('bulkcertification_objectives', ['courseid' => $this->_data->id], '', 'id, name');
        $mform->addElement('searchableselector', 'code', get_string('objective', 'format_bulkcertification'), $codes);
        $mform->addRule('code', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'id', $this->_data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', \format_bulkcertification::ACTION_BULK);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'op', \format_bulkcertification::OP_SEARCH);
        $mform->setType('op', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search', 'format_bulkcertification'));

        // Finally set the current form data
        $this->set_data($this->_data);
    }

    /**
     * Get the list of certificates in the course to use as template.
     *
     * @return array
     */
    private function get_certificates() {
        global $COURSE, $DB;

        $module = $DB->get_record('modules', ['name' => 'simplecertificate']);

        $sql = "SELECT sc.id, sc.name
                FROM {simplecertificate} sc
                JOIN {course_modules} cm ON sc.id = cm.instance AND cm.module = :module
                WHERE cm.course = :course AND cm.visible = 1 ORDER BY sc.name ASC";
        $certificates = $DB->get_records_sql_menu($sql, ['course' => $COURSE->id, 'module' => $module->id]);

        return $certificates;
    }
}
