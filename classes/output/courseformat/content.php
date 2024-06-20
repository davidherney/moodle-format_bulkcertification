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
 * Contains the default content output class.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_bulkcertification\output\courseformat;

use renderer_base;
use core_courseformat\output\local\content as content_base;

/**
 * Base class to render a course content.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Topic parent format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     *
     * @param renderer_base $renderer typically, the renderer that's calling this function
     * @return string format template name
     */
    public function get_template_name(\renderer_base $renderer): string {

        switch ($this->format->currentaction) {
            case \format_bulkcertification::ACTION_BULK:
                return 'format_bulkcertification/courseformat/bulk';
            case \format_bulkcertification::ACTION_OBJECTIVES:
                return 'format_bulkcertification/courseformat/objectives';
            case \format_bulkcertification::ACTION_CERTIFIED:
                if ($this->format->currentoperation == \format_bulkcertification::OP_DETAILS
                        || $this->format->currentoperation == \format_bulkcertification::OP_REBUILD) {
                    return 'format_bulkcertification/courseformat/certified_detail';
                } else {
                    return 'format_bulkcertification/courseformat/certified';
                }
            case \format_bulkcertification::ACTION_TPL:
            default:
                return 'format_bulkcertification/local/content';
            }
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = parent::export_for_template($output);

        $course = $this->format->get_course();
        $data->courseurl = (string)(new \moodle_url('/course/view.php', ['id' => $course->id]));
        $data->pagemessages = [];
        $data->pageerrors = [];
        $data->urlbase = $CFG->wwwroot;

        $coursecontext = \context_course::instance($course->id);

        $data->canmanage = has_capability('format/bulkcertification:manage', $coursecontext);
        $data->actionbulk = \format_bulkcertification::ACTION_BULK;
        $data->actionobjectives = \format_bulkcertification::ACTION_OBJECTIVES;
        $data->actiontpl = \format_bulkcertification::ACTION_TPL;
        $data->actioncertified = \format_bulkcertification::ACTION_CERTIFIED;
        $data->operationadd = \format_bulkcertification::OP_ADD;
        $data->operationimport = \format_bulkcertification::OP_IMPORT;

        $operation = $this->format->currentoperation;

        if ($this->format->currentaction == \format_bulkcertification::ACTION_BULK) {

            \format_bulkcertification\controller::action_bulk($data, $coursecontext, $course, $operation);

        } else if ($this->format->currentaction == \format_bulkcertification::ACTION_OBJECTIVES) {

            \format_bulkcertification\controller::action_objectives($data, $coursecontext, $course, $operation);

        } else if ($this->format->currentaction == \format_bulkcertification::ACTION_CERTIFIED) {

            switch($operation) {
                case \format_bulkcertification::OP_REBUILD:
                    \format_bulkcertification\controller::certified_rebuild($data, $course);
                    // After the rebuild, list the issue certificates.
                case \format_bulkcertification::OP_DETAILS:
                    \format_bulkcertification\controller::action_certified_detail($data, $coursecontext, $course, $operation);
                    break;
                case \format_bulkcertification::OP_REBUILDALL:
                    \format_bulkcertification\controller::certified_rebuild($data, $course, true);
                    // Continue to default.
                default:
                    \format_bulkcertification\controller::action_certified($data, $coursecontext, $course, $operation);
                    break;
            }

        } else {
            $data->activetpl = true;
        }

        // Clear arrays if empty to avoid mustache errors and messages.
        if (empty($data->pagemessages)) {
            $data->pagemessages = null;
        }

        if (empty($data->pageerrors)) {
            $data->pageerrors = null;
        }

        return $data;
    }
}
