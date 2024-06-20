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

$courseid = required_param('id', PARAM_INT);
$bulkid = required_param('bulkid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$context = context_course::instance($course->id);

require_login($course->id, false);
require_capability('mod/simplecertificate:manage', $context);

$bulk = $DB->get_record('bulkcertification_bulk', ['id' => $bulkid], '*', MUST_EXIST);
$issues = $DB->get_records('bulkcertification_issues', ['bulkid' => $bulkid], '', 'id, issueid');

if (!empty($issues)) {

    $ids = [];
    foreach($issues as $issue) {
        $ids[] = $issue->issueid;
    }

    $sql = 'SELECT * FROM {simplecertificate_issues} WHERE id IN (' . implode(',', $ids) . ')';
    $simpleissues = $DB->get_records_sql($sql);

    $simplecertificate = new \format_bulkcertification\bulksimplecertificate(null, null, $course);
    $simplecertificate->download_bulk($bulk, $simpleissues);
    exit;
} else {
    $PAGE->set_url('/course/format/bulkcertification/download.php', ['id' => $course->id, 'bulkid' => $bulkid]);
    $PAGE->set_context($context);
    $PAGE->set_title(format_string($bulk->certificatename));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_pagelayout('course');

    echo $OUTPUT->header();

    echo $OUTPUT->notification(get_string('issues_notfound', 'format_bulkcertification'));

    $url = new moodle_url($CFG->wwwroot . '/course/view.php', [
                                                                'id' => $course->id,
                                                                'action' => \format_bulkcertification::ACTION_CERTIFIED,
                                                            ]);
    echo $OUTPUT->container_start('buttons');
    echo $OUTPUT->single_button($url, get_string('back'));
    echo $OUTPUT->container_end();

    echo $OUTPUT->footer();
}
