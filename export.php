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
 * Bulk certification export data
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/certificatelib.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/exportlib.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/filters/lib.php';

$courseid   = required_param('id', PARAM_INT);  // Course id.
$bulkid     = optional_param('bulkid', 0, PARAM_INT);
$format     = optional_param('format', 'csv', PARAM_ALPHA);
$type       = optional_param('type', 'bulk', PARAM_ALPHA);
$sort       = optional_param('sort', 'id', PARAM_ALPHA);
$dir        = optional_param('dir', 'DESC', PARAM_ALPHA);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = context_course::instance($course->id);

require_login( $course->id, false);
require_capability('mod/simplecertificate:manage', $context);

$msg = array();
$fields = array();
$data = array();

$userformatdate = get_string('strftimedatefullshort');
$userformatdatetime = get_string('strftimedatetimeshort');

if ($type == 'bulk') {

    $ifiltering = new format_bulkcertification_filtering(null, array('id' => $course->id));

    list($extrasql, $params) = $ifiltering->get_sql_filter();

    $bulklist = format_bulkcertification_get_bulk_listing(true, $sort, $dir, 0, 0, '', '', '', $extrasql, $params);

    $fields['bulktime']         = get_string('bulktime', 'format_bulkcertification');
    $fields['issuing']          = get_string('issuing', 'format_bulkcertification');
    $fields['coursename']       = get_string('objective_name', 'format_bulkcertification');
    $fields['code']             = get_string('code', 'format_bulkcertification');
    $fields['groupcode']        = get_string('externalcode', 'format_bulkcertification');
    $fields['customtime']       = get_string('objective_date', 'format_bulkcertification');
    $fields['certificatename']  = get_string('template', 'format_bulkcertification');
    $fields['remotetime']       = get_string('remote_date', 'format_bulkcertification');
    $fields['localhours']       = get_string('objective_hours', 'format_bulkcertification');
    $fields['remotehours']      = get_string('remote_hours', 'format_bulkcertification');
    $fields['issued']           = get_string('issued', 'format_bulkcertification');

    if ($bulklist) {

        $issuing_cache = array();

        foreach($bulklist as $bulk){

            if (!isset($issuing_cache[$bulk->issuingid])) {
                $issuing = $DB->get_record('user', array('id' => $bulk->issuingid));
                $issuingname = fullname($issuing);
                $issuing_cache[$bulk->issuingid] = $issuingname;
            }
            else {
                $issuingname = $issuing_cache[$bulk->issuingid];
            }

            $counter = $DB->count_records('bulkcertification_issues', ['bulkid' => $bulk->id]);

            $datarow = new stdClass();
            $datarow->bulktime          = userdate($bulk->bulktime, $userformatdatetime);
            $datarow->issuing           = $issuingname;
            $datarow->coursename        = $bulk->coursename;
            $datarow->code              = $bulk->code;
            $datarow->groupcode         = $bulk->groupcode;
            $datarow->customtime        = userdate($bulk->customtime, $userformatdate);
            $datarow->certificatename   = $bulk->certificatename;
            $datarow->remotetime        = userdate($bulk->remotetime, $userformatdate);
            $datarow->localhours        = $bulk->localhours;
            $datarow->remotehours       = $bulk->remotehours;
            $datarow->issued            = $counter;

            $data[] = $datarow;
        }

    }
} else {

    $sql = "SELECT bi.id, u.id AS userid, u.firstname, u.lastname, u.idnumber, u.email, u.username, bi.issueid
                FROM {bulkcertification_issues} AS bi
                INNER JOIN {simplecertificate_issues} AS si ON si.id = bi.issueid
                INNER JOIN {user} AS u ON u.id = si.userid
                WHERE bi.bulkid = ?
            ";
    $issues = $DB->get_records_sql($sql, array('bulkid' => $bulkid));

    $fields['name'] = get_string('name');
    $fields['date'] = get_string('receiveddate', 'simplecertificate');
    $fields['filename'] = get_string('filename', 'format_bulkcertification');
    $fields['idnumber'] = get_string('idnumber');
    $fields['username'] = get_string('username');
    $fields['email'] = get_string('email');


    if ($issues) {

        $simplecertificate = new format_bulkcertification_simplecertificate(null, null, $course);

        foreach ($issues as $issue) {

            $simpleissue = $DB->get_record('simplecertificate_issues', array('id' => $issue->issueid));
            $file = $simplecertificate->get_issue_file($simpleissue);

            $datarow = new stdClass();

            $datarow->name      = $issue->firstname . ' ' . $issue->lastname;
            $datarow->date      = userdate($simpleissue->timecreated, $userformatdatetime);
            $datarow->filename  = $file->get_filename();
            $datarow->idnumber  = $issue->idnumber;
            $datarow->username  = $issue->username;
            $datarow->email     = $issue->email;

            $data[] = $datarow;
        }
    }
}


switch ($format) {
    case 'csv' : format_bulkcertification_download_csv($fields, $data);
    case 'ods' : format_bulkcertification_download_ods($fields, $data);
    case 'xls' : format_bulkcertification_download_xls($fields, $data);

}
die;
