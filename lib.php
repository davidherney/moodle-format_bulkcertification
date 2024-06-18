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
 * This file contains main class for Bulk certification format.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');

/**
 * Main class for the Bulk certification format.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_bulkcertification extends core_courseformat\base {

    /**
     * The bulk action key.
     */
    const ACTION_BULK = 'bulk';

    /**
     * The certified action key.
     */
    const ACTION_CERTIFIED = 'certified';

    /**
     * The objectives action key.
     */
    const ACTION_OBJECTIVES = 'objectives';

    /**
     * The templates action key.
     */
    const ACTION_TPL = 'tpl';

    /**
     * The add operation key.
     */
    const OP_ADD = 'add';

    /**
     * The delete operation key.
     */
    const OP_DELETE = 'dl';

    /**
     * The delete operation key.
     */
    const OP_DETAILS = 'details';

    /**
     * The import operation key.
     */
    const OP_IMPORT = 'import';

    /**
     * The rebuild operation key.
     */
    const OP_REBUILD = 'rebuild';

    /**
     * The rebuild all operation key.
     */
    const OP_REBUILDALL = 'rebuildall';

    /**
     * The save operation key.
     */
    const OP_SAVE = 'save';

    /**
     * The search operation key.
     */
    const OP_SEARCH = 'search';

    /**
     * The replace import key.
     */
    const IMPORT_REPLACE = 'replace';

    /**
     * The add import key.
     */
    const IMPORT_ADD = 'add';

    /**
     * The current action.
     *
     * @var string
     */
    public $currentaction = null;

    /**
     * The current operation.
     *
     * @var string
     */
    public $currentoperation = null;

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return false;
    }

    public function uses_course_index() {
        return false;
    }

    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        return $this->get_default_section_name($section);
    }

    /**
     * Returns the default section name for the course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        return parent::get_default_section_name($section);
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {

        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns false because the format don't support components.
     *
     * @return bool
     */
    public function supports_components() {
        return false;
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Bulk certification format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        return [];
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {

        $elements = parent::create_edit_form_elements($mform, $forsection);

        return $elements;
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'topics', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return false;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return false;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return false;
    }

}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function format_bulkcertification_extend_navigation_course($navigation, $course, $context) {

    $format = course_get_format($course);

    $isbulk = $format instanceof format_bulkcertification;

    if ($isbulk && has_capability('format/bulkcertification:manage', $context)) {

        $reports = $navigation->children->get('coursereports');

        $params = ['course' => $course->id];

        $url = new moodle_url('/course/format/bulkcertification/statistics.php', $params);
        $reports->add(get_string('report_statistics', 'format_bulkcertification'), $url,
                            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}
