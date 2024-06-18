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
 * Class containing form definition to import objectives.
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
 * The form for handling import objectives.
 *
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objectivesimport extends moodleform {

    /**
     * @var object List of local data.
     */
    protected $_data;

    /**
     * @var array List of delimiters.
     */
    public const DELIMITERS = ['t' => '\t', ',' => ',', ';' => ';'];

    /**
     * Form definition.
     */
    public function definition() {

        $mform = $this->_form;

        // This contains the data of this form.
        $this->_data = $this->_customdata['data'];

        //General options
        $mform->addElement('header', 'general', get_string('importobjectives', 'format_bulkcertification'));
        $mform->setExpanded('general', false);

        $attrs = ['cols' => 80, 'rows' => 20];
        $mform->addElement('textarea', 'objectiveslist', get_string('objectiveslist', 'format_bulkcertification'), $attrs);
        $mform->setType('objectiveslist', PARAM_TEXT);
        $mform->addHelpButton('objectiveslist', 'objectiveslist', 'format_bulkcertification');
        $mform->addRule('objectiveslist', null, 'required', null, 'client');

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'format_bulkcertification'), self::DELIMITERS);

        $values = [];
        $values[\format_bulkcertification::IMPORT_ADD] = get_string('bulkobjectiveadd', 'format_bulkcertification');
        $values[\format_bulkcertification::IMPORT_REPLACE] = get_string('bulkobjectivereplace', 'format_bulkcertification');
        $mform->addElement('select', 'mode', get_string('bulkobjectivemode', 'format_bulkcertification'), $values);

        $mform->addElement('hidden', 'id', $this->_data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', \format_bulkcertification::ACTION_OBJECTIVES);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'op', \format_bulkcertification::OP_IMPORT);
        $mform->setType('op', PARAM_TEXT);

        $mform->addElement('submit', 'importsubmitbutton', get_string('import', 'format_bulkcertification'));
        $mform->closeHeaderBefore('importsubmitbutton');

        // Finally set the current form data
        $this->set_data($this->_data);
    }

}