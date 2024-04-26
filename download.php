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
 * Bulk certification download
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/certificatelib.php';

$courseid   = required_param('id', PARAM_INT);
$bulkid     = required_param('bulkid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = context_course::instance($course->id);

require_login( $course->id, false);
require_capability('mod/simplecertificate:manage', $context);


$bulk  = $DB->get_record('bulkcertification_bulk', array('id' => $bulkid), '*', MUST_EXIST);
$issues = $DB->get_records('bulkcertification_issues', array('bulkid' => $bulkid));

if($issues) {
    $simpleissues = array();
    foreach($issues as $issue) {
        $simpleissues[] = $DB->get_record('simplecertificate_issues', array('id' => $issue->issueid));
    }

    $simplecertificate = new format_bulkcertification_simplecertificate(null, null, $course);
    $simplecertificate->download_bulk($bulk, $simpleissues);
    exit;
} else {
    $PAGE->set_url('/course/format/bulkcertification/download.php');
    $PAGE->set_context($context);
    $PAGE->set_title(format_string($bulk->certificatename));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('course');

    echo $OUTPUT->header();

    echo $OUTPUT->notification(get_string('issues_notfound', 'format_bulkcertification'));

    $url_return = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id, 'tab' => 'certified'));
    echo $OUTPUT->container_start('buttons');
    echo $OUTPUT->single_button($url_return, get_string('back'));
    echo $OUTPUT->container_end();

    echo $OUTPUT->footer();
}
