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
 * Simple certificate gateway interaction.
 *
 * This code is based in mod_simplecertificate by
 * Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_bulkcertification;

require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

class bulksimplecertificate extends \simplecertificate {

    /**
     * Remove an issue certificate
     *
     * @param \stdClass $issue Issue certificate object
     * @return bool true if removed
     */
    public function delete_issue(\stdClass $issue) {
        global $DB;

        // Try to delete certificate file.
        try {
            // Try to get issue file.
            if (!$this->issue_file_exists($issue)) {
                throw new \moodle_exception('filenotfound', 'simplecertificate', null, $issue->certificatename .
                                            ' (issue id: ' . $issue->id . ', userid: ' .$issue->userid . ')');
            }
            $fs = get_file_storage();

            // Do not use $this->get_issue_file($issue), it has many functions calls.
            $file = $fs->get_file_by_hash($issue->pathnamehash);

            $file->delete();

        } catch (\moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
        }

        return $DB->delete_records('simplecertificate_issues', ['id' => $issue->id]);
    }

    /**
     * Get issued certificate object, if it's not exist, it will be create
     *
     * @param mixed User obj or id
     * @param boolean Issue teh user certificate if it's not exists (default = true)
     * @return \stdClass the issue certificate object
     */
    public function get_issue($user = null, $issueifempty = true) {
        global $DB;

        // The user is made mandatory since the session user is not generated.
        if (empty($user)) {
            throw new \moodle_exception('invaliduser');
        }

        if (is_object($user)) {
            $userid = $user->id;
        } else {
            $userid = $user;
        }

        // Check if certificate has already issued.
        // Trying cached first.

        // The cache issue is from this user ?
        $created = false;
        $issuecert = $this->get_issuecert();
        if (!empty($issuecert) && $issuecert->userid == $userid) {
            if (empty($issuecert->haschange)) {
                // Haschange is marked, if no return from cache.
                return $issuecert;
            } else {
                // Haschange is maked, must update.
                $issuedcert = $issuecert;
            }
            // Not in cache, trying get from database.
        } else if (!$issuedcert = $DB->get_record('simplecertificate_issues',
                        ['userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null])) {
            // Not in cache and not in DB, create new certificate issue record.

            if (!$issueifempty) {
                // Not create a new one, only check if exists.
                return null;
            }

            // Mark as created.
            $created = true;
            $issuedcert = new \stdClass();
            $issuedcert->certificateid = 0; // Changed from original: The issue is not associated with a certificate, intentionally.
            $issuedcert->coursename = format_string($this->get_instance()->coursename, true);
            $issuedcert->userid = $userid;
            $issuedcert->haschange = 1;
            $issuedcert->timecreated = time();
            $issuedcert->timedeleted = time();
            $issuedcert->code = $this->get_issue_uuid();
            // Avoiding not null restriction.
            $issuedcert->pathnamehash = '';

            // Changed from original: The certificate name is specially created for this issue.
            $issuedcert->certificatename = $this->get_cert_name($issuedcert);

            // Changed from original: It is not tested, it is only created because the tests are done in the template.
            $issuedcert->id = $DB->insert_record('simplecertificate_issues', $issuedcert);

            // Changed from original: Not send mails.
        }

        // If cache or db issued certificate is maked as haschange, must update.
        if (!empty($issuedcert->haschange) && !$created) { // Check haschange, if so, reissue.
            $issuedcert->certificatename = $this->get_cert_name($issuedcert);
            $DB->update_record('simplecertificate_issues', $issuedcert);
        }

        // Caching to avoid unessecery db queries.
        $this->set_issuecert($issuedcert);
        return $issuedcert;
    }

    /**
     * Returns the grade to display for the certificate.
     *
     * @param int $userid
     * @return string the grade result
     */
    protected function get_grade($userid = null) {

        // Changed from original: The grade is not taken into account in these certificates since
        // users are not actually enrolled/qualified.
        return '0';
    }

    /**
     * Prepare to print an activity grade.
     *
     * @param int $moduleid
     * @param int $userid
     * @return stdClass bool the mod object if it exists, false otherwise
     */
    protected function get_mod_grade($moduleid, $userid) {

        // Changed from original: The grade cannot be associated with a module.

        return false;
    }

    /**
     * Save a certificate pdf file
     *
     * @param \stdClass $issuecert the certificate issue record
     * @param bool $force Force to create a new file, even if it's not changed
     * @return mixed return stored_file if successful, false otherwise
     */
    public function save_pdf_forced(\stdClass $issuecert, bool $force = false) {

        if ($force) {
            $issuecert->haschange = 1;
        }

        return self::save_pdf($issuecert);
    }

    /**
     * Save a certificate pdf file
     *
     * @param \stdClass $issuecert the certificate issue record
     * @return mixed return stored_file if successful, false otherwise
     */
    private function save_pdf(\stdClass $issuecert) {
        global $DB;

        // Check if file exist.
        // If issue certificate has no change, it's must has a file.
        if (empty($issuecert->haschange)) {
            if ($this->issue_file_exists($issuecert)) {
                return $this->get_issue_file($issuecert);
            } else {
                throw new \moodle_exception(get_string('filenotfound', 'simplecertificate'));
            }
        } else {
            // Cache issued cert, to avoid db queries.
            $this->set_issuecert($issuecert);
            $pdf = $this->create_pdf($this->get_issue($issuecert->userid));
            if (!$pdf) {
                throw new \moodle_exception("Error: can't create certificate file to " . $issuecert->userid);
            }

            // This avoid function calls loops.
            $issuecert->haschange = 0;

            // Remove old file, if exists.
            if ($this->issue_file_exists($issuecert)) {
                $file = $this->get_issue_file($issuecert);
                $file->delete();
            }

            // Prepare file record object.
            // Changed from original: Use the user context so as not to link it with the template.
            $context = \context_user::instance($issuecert->userid);

            $filename = $this->get_filecert_name($issuecert);
            // Changed from original: The component, filearea and context are changed so that it is not tied to the activity.
            $fileinfo = ['contextid' => $context->id,
                        'component' => 'user',
                        'filearea' => 'private',
                        'itemid' => $issuecert->id,
                        'filepath' => '/certificates/' . $issuecert->coursename . '/',
                        'mimetype' => 'application/pdf',
                        'userid' => $issuecert->userid,
                        'filename' => $filename,
            ];

            $fs = get_file_storage();
            $file = $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'));
            if (!$file) {
                throw new \moodle_exception('cannotsavefile', 'error', '', $fileinfo['filename']);
            }

            // Changed from original: Not remove user profile image.
            $issuecert->pathnamehash = $file->get_pathnamehash();

            // Changed from original: Not verify if user is a manager, it's no need.
            if (!$DB->update_record('simplecertificate_issues', $issuecert)) {
                throw new \moodle_exception('cannotupdatemod', 'error', null, 'simplecertificate_issue');
            }
             return $file;
        }
    }

    /**
     * Return a stores_file object with issued certificate PDF file or false otherwise
     *
     * @param \stdClass $issuecert Issued certificate object
     * @return mixed <stored_file, boolean>
     */
    public function get_issue_file(\stdClass $issuecert) {
        if (!empty($issuecert->haschange)) {
            return $this->save_pdf($issuecert);
        }

        // Changed from original: Changed to instantiate the local save_pdf, the rest can be as in the parent.

        return parent::get_issue_file($issuecert);
    }

    /**
     * Get the time the user has spent in the course
     *
     * @param int $userid User ID (default= $USER->id)
     * @return int the total time spent in seconds
     */
    public function get_course_time($user = null) {
        // Changed from original: The time spent in the course is not taken into account in these certificates since.
        return 0;
    }

    /**
     * Delivery the issue certificate
     *
     * @param \stdClass $issuecert The issued certificate object
     */
    public function output_pdf(\stdClass $issuecert) {
        global $OUTPUT;

        $file = $this->get_issue_file($issuecert);
        if ($file) {
            switch ($this->get_instance()->delivery) {
                case self::OUTPUT_FORCE_DOWNLOAD:
                    send_stored_file($file, 10, 0, true, ['filename' => $file->get_filename(), 'dontdie' => true]);
                break;

                case self::OUTPUT_SEND_EMAIL:
                    $this->send_certificade_email($issuecert);
                    echo $OUTPUT->header();
                    echo $OUTPUT->box(get_string('emailsent', 'simplecertificate') . '<br>' . $OUTPUT->close_window_button(),
                                    'generalbox', 'notice');
                    echo $OUTPUT->footer();
                break;

                // OUTPUT_OPEN_IN_BROWSER.
                default: // Open in browser.
                    send_stored_file($file, 10, 0, false, ['dontdie' => true]);
                break;
            }

            // Changed from original: The file is not deleted after being sent.
        } else {
            throw new \moodle_exception(get_string('filenotfound', 'simplecertificate'));
        }
    }

    /**
     * Substitutes the certificate text variables
     *
     * @param \stdClass $issuecert The issue certificate object
     * @param string $certtext The certificate text without substitutions
     * @return string Return certificate text with all substutions
     */
    protected function get_certificate_text($issuecert, $certtext = null) {

        $user = get_complete_user_data('id', $issuecert->userid);
        if (!$user) {
            throw new \moodle_exception('nousersfound', 'moodle');
        }

        // Changed from original: Feature to the bulk format.
        // If exist temporal user information, overwrite user information.
        if (property_exists($issuecert, 'tmpuser')) {
            foreach ($issuecert->tmpuser as $key => $value) {
                $user->$key = $value;
            }
        }

        // If no text set get firstpage text.
        if (empty($certtext)) {
            $certtext = $this->get_instance()->certificatetext;
        }
        $certtext = format_text($certtext, FORMAT_HTML, ['noclean' => true]);

        $a = new \stdClass();
        $a->username = strip_tags(fullname($user));
        $a->idnumber = strip_tags($user->idnumber);
        $a->firstname = strip_tags($user->firstname);
        $a->lastname = strip_tags($user->lastname);
        $a->email = strip_tags($user->email);
        $a->phone1 = strip_tags($user->phone1);
        $a->phone2 = strip_tags($user->phone2);
        $a->institution = strip_tags($user->institution);
        $a->department = strip_tags($user->department);
        $a->address = strip_tags($user->address);
        $a->city = strip_tags($user->city);

        // Add userimage url only if have a picture.
        if ($user->picture > 0) {
            $a->userimage = $this->get_user_image_url($user);
        } else {
            $a->userimage = '';
        }

        if (!empty($user->country)) {
            $a->country = get_string($user->country, 'countries'); // Mdlcode-disable-line cannot-parse-string.
        } else {
            $a->country = '';
        }

        // Getting user custom profiles fields.
        $userprofilefields = $this->get_user_profile_fields($user->id);
        foreach ($userprofilefields as $key => $value) {
            $key = 'profile_' . $key;
            $a->$key = strip_tags($value);
        }

        // The course name never change form a certificate to another, useless
        // text mark and atribbute, can be removed.
        $a->coursename = strip_tags($this->get_instance()->coursename);
        $a->grade = $this->get_grade($user->id);
        $a->date = $this->get_date($issuecert, $user->id);
        $a->outcome = $this->get_outcome($user->id);
        $a->certificatecode = $issuecert->code;

        // This code stay here only beace legacy support, coursehours variable was removed
        // see issue 61 https://github.com/bozoh/moodle-mod_simplecertificate/issues/61.
        if (isset($this->get_instance()->coursehours)) {
            $a->hours = strip_tags($this->get_instance()->coursehours . ' ' . get_string('hours', 'simplecertificate'));
        } else {
            $a->hours = '';
        }

        // Changed from original: Feature to the bulk format.
        if (isset($this->get_instance()->customparams)) {
            $customparams = is_string($this->get_instance()->customparams) ?
                                (array)json_decode($this->get_instance()->customparams) :
                                (array)$this->get_instance()->customparams;

            foreach ($customparams as $key => $param) {
                $a->$key = $param;
            }
        }

        $teachers = $this->get_teachers();
        if (empty($teachers)) {
            $teachers = '';
        } else {
            $t = [];
            foreach ($teachers as $teacher) {
                $t[] = content_to_text($teacher->rolename . ': ' . $teacher->username, FORMAT_MOODLE);
            }
            $a->teachers = implode("<br>", $t);
        }

        // Fetch user actitivy restuls.
        $a->userresults = $this->get_user_results($issuecert->userid);

        // Get User role name in course.
        // Changed from original: The role in the course does not apply because the user does not actually enroll.
        $a->userrolename = '';

        // Changed from original: The enrol date does not apply because the user does not actually register
        $a->timestart = '';

        $a = (array)$a;
        $search = [];
        $replace = [];
        foreach ($a as $key => $value) {
            $search[] = '{' . strtoupper($key) . '}';
            // Due #148 bug, i must disable filters, because activities names {USERRESULTS}
            // will be replaced by actitiy link, don't make sense put activity link
            // in the certificate, only activity name and grade
            // para=> false to remove the <div> </div>  form strings
            $replace[] = (string)$value;
        }

        if ($search) {
            $certtext = str_replace($search, $replace, $certtext);
        }

        // Clear not setted  textmark.
        $certtext = preg_replace('[\{(.*)\}]', "", $certtext);
        return $this->remove_links(format_text($certtext, FORMAT_MOODLE));
    }

    /**
     * Returns the date to display for the certificate.
     *
     * @param \stdClass $issuecert The issue certificate object
     * @param int $userid
     * @return string the date
     */
    protected function get_date(\stdClass $issuecert) {

        // Get date format
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }

        // Changed from original: If the date is sent, that is what is taken, otherwise the current date is shown.
        // Other date configuration options are ignored.
        if ($this->get_instance()->certdate > 0) {
            $date = $this->get_instance()->certdate;
        } else {
            $date = time();
        }

        return userdate($date, $format);
    }

    /**
     *  Return all actitity grades, in the format:
     *  Grade Item Name: grade<br>
     *
     * @param int $userid the user id, if none are supplied, gets $USER->id
     */
    protected function get_user_results($userid = null) {

        // Changed from original: Grades are not taken into account at all.
        return 'N/A';
    }

    /**
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    protected function get_outcome($userid) {

        // Changed from original: Does not apply to this case.
        return 'N/A';
    }

    /**
     * Verify if user meet issue conditions
     *
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    protected function can_issue($user = null, $chkcompletation = true) {
        // Changed from original: It is not validated since they are called by a central user with permissions in the course.
        return true;
    }

    /**
     * get full user status of on certificate instance (if it can view/access)
     * this method helps the unit test (easy to mock)
     * @param int $userid
     */
    protected function check_user_can_access_certificate_instance($userid) {
        // Changed from original: It is not validated since they are called by a central user with permissions in the course.
        return true;
    }

    /**
     * Download a bulk of certificates.
     *
     * @param stdClass $bulk The bulk certificate object
     * @param array $issues The issues to download
     * @return void
     */
    public function download_bulk($bulk, array $issues) {

        // Calculate file name.
        $filename = str_replace(' ', '_',
                                clean_filename(
                                            $bulk->coursename . ' ' .
                                                get_string('modulenameplural', 'simplecertificate') . ' ' .
                                                strip_tags(format_string($bulk->certificatename, true)) . '.zip'));

        $filename = str_replace(self::CHARS_NOT, self::CHARS_YES, $filename);

        $filesforzipping = [];
        foreach ($issues as $issuecert) {

            if ($this->issue_file_exists($issuecert)) {
                $fs = get_file_storage();
                $file = $fs->get_file_by_hash($issuecert->pathnamehash);

                if ($file) {
                    $filesforzipping[$file->get_filename()] = $file;
                }
            }
        }

        $tempzip = $this->create_temp_file('issuedcertificate_');

        // Zipping files.
        $zipper = new \zip_packer();
        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            // Send file and delete after sending.
            ob_clean();
            send_temp_file($tempzip, $filename);
        }
    }

    /**
     * Print issed file certificate link
     *
     * @param \stdClass $issuecert The issued certificate object
     * @param bool $shortname
     * @return string file link url
     */
    public function print_issue_certificate_file(\stdClass $issuecert, $shortname = false) {
        global $OUTPUT;

        // Trying to cath course module context
        try {
            $fs = get_file_storage();
            if (!$fs->file_exists_by_hash($issuecert->pathnamehash)) {
                throw new \moodle_exception('filenotfound', 'simplecertificate', null, null, '');
            }

            $file = $fs->get_file_by_hash($issuecert->pathnamehash);
            $output = $OUTPUT->pix_icon(file_mimetype_icon($file->get_mimetype()), $file->get_mimetype()) . '&nbsp;';

            $url = new \moodle_url('/mod/simplecertificate/wmsendfile.php');
            $url->param('code', $issuecert->code);

            $fullfilename = s($file->get_filename());
            $filename = $fullfilename;
            if ($shortname && strlen($filename) > 33) {
                $filename = substr($filename, 0, 15) . '...' . substr($filename, -15, 15);
            }

            $output .= '<a href="' . $url->out(true) . '" target="_blank" title="' . $fullfilename . '" >' . $filename . '</a>';

        } catch (\Exception $e) {
            $output = get_string('filenotfound', 'simplecertificate', '');
        }

        return '<div class="files">' . $output . '<br /> </div>';

    }
}
