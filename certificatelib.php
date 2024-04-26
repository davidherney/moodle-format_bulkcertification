<?php

// This file is part of Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Simple Certificate module core interaction API
 *
 * This code is based in simplecertificate by
 * Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/mod/simplecertificate/lib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->dirroot . '/grade/lib.php');
require_once ($CFG->dirroot . '/grade/querylib.php');
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/user/profile/lib.php');


use core_availability\info;
use core_availability\info_module;

class format_bulkcertification_simplecertificate {
    /**
    *  module constats using in file storage
    * @var CERTIFICATE_COMPONENT_NAME  base componete name
    * @var CERTIFICATE_IMAGE_FILE_AREA image filearea
    * @var CERTIFICATE_ISSUES_FILE_AREA issued certificates filearea
    */
    const CERTIFICATE_COMPONENT_NAME = 'mod_simplecertificate';
    const CERTIFICATE_IMAGE_FILE_AREA = 'image';
    const CERTIFICATE_ISSUES_FILE_AREA = 'issues';

    const OUTPUT_OPEN_IN_BROWSER = 0;
    const OUTPUT_FORCE_DOWNLOAD = 1;
    const OUTPUT_SEND_EMAIL = 2;

    const CHARS_NOT = array ("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú", "ñ", "À", "Ã", "Ì", "Ò", "Ù", "Ã™", "Ã ", "Ã¨", "Ã¬", "Ã²", "Ã¹", "ç", "Ç", "Ã¢", "ê", "Ã®", "Ã´", "Ã»", "Ã‚", "ÃŠ", "ÃŽ", "Ã”", "Ã›", "ü", "Ã¶", "Ã–", "Ã¯", "Ã¤", "«", "Ò", "Ã", "Ã„", "Ã‹", "ñ", "Ñ");

    const CHARS_YES = array ("a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "n", "N", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", "c", "C", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "u", "o", "O", "i", "a", "e", "U", "I", "A", "E", "n", "N");

    /**
    *
    * @var stdClass the assignment record that contains the global settings for this simplecertificate instance
    */
    private $instance;

    /**
    *
    * @var context the context of the course module for this simplecertificate instance
    *      (or just the course if we are creating a new one)
    */
    private $context;

    /**
    *
    * @var stdClass the course this simplecertificate instance belongs to
    */
    private $course;

    /**
    *
    * @var stdClass the admin config for all simplecertificate instances
    */
    private $adminconfig;

    /**
    *
    * @var assign_renderer the custom renderer for this module
    */
    private $output;

    /**
    *
    * @var stdClass the course module for this simplecertificate instance
    */
    private $coursemodule;

    /**
    *
    * @var array cache for things like the coursemodule name or the scale menu -
    *      only lives for a single request.
    */
    private $cache;

    /**
    *
    * @var stdClass the current issued certificate
    */
    private $issuecert;

    /**
    * Constructor for the base simplecertificate class.
    *
    * @param mixed $coursemodulecontext context|null the course module context
    *        (or the course context if the coursemodule has not been
    *        created yet).
    * @param mixed $coursemodule the current course module if it was already loaded,
    *        otherwise this class will load one from the context as required.
    * @param mixed $course the current course if it was already loaded,
    *        otherwise this class will load one from the context as required.
    */
    public function __construct($coursemodulecontext, $coursemodule = null, $course = null) {
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = [];
    }

    /**
    * Remove an issue certificate
    *
    * @param stdClass $issue Issue certificate object
    * @return bool true if removed
    */
    public function delete_issue(stdClass $issue) {
        global $DB;

        // Try to delete certificate file
        try {
            // Try to get issue file
            if (!$this->issue_file_exists($issue)) {
                throw new moodle_exception('filenotfound', 'simplecertificate', null, $issue->certificatename . ' (issue id: ' . $issue->id . ', userid: ' .$issue->userid . ')');
            }
            $fs = get_file_storage();

            //Do not use $this->get_issue_file($issue), it has many functions calls
            $file = $fs->get_file_by_hash($issue->pathnamehash);

            $file->delete();

        } catch (moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
        }

        return $DB->delete_records('simplecertificate_issues', ['id' => $issue->id]);
    }

    /**
    * Get the settings for the current instance of this certificate
    *
    * @return stdClass The settings
    */
    public function get_instance() {
        global $DB;

        if (!isset($this->instance)) {
            $cm = $this->get_course_module();
            if ($cm) {
                $params = ['id' => $cm->instance];
                $this->instance = $DB->get_record('simplecertificate', $params, '*', MUST_EXIST);
            }
            if (!$this->instance) {
                throw new coding_exception('Improper use of the simplecertificate class. ' .
                                'Cannot load the simplecertificate record.');
            }
        }
        if (empty($this->instance->coursename)) {
            $this->instance->coursename = $this->get_course()->fullname;
        }
        return $this->instance;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = ['id' => $this->get_course_context()->instanceid];
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the simplecertificate class. ' . 'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }

        if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('simplecertificate', $this->context->instanceid, 0, false, MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Set the submitted form data.
     *
     * @param stdClass $data The form data (instance)
     */
    public function set_instance(stdClass $data) {
        $this->instance = $data;
    }

    /**
     * Set the context.
     *
     * @param context $context The new context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }


    /**
     * Get the first page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_image_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        $fileinfo = ['contextid' => $contextid, // ID of context
                        'component' => self::CERTIFICATE_COMPONENT_NAME, // usually = table name
                        'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA, // usually = table name
                        'itemid' => 1, // usually = ID of row in table
                        'filepath' => '/']; // any path beginning and ending in /

        return $fileinfo;
    }

    /**
     * Get the second page background image fileinfo
     *
     * @param mixed $context The module context object or id
     * @return the second page background image fileinfo
     */
    public static function get_certificate_secondimage_fileinfo($context) {

        $fileinfo = self::get_certificate_image_fileinfo($context);
        $fileinfo['itemid'] = 2;
        return $fileinfo;
    }

    /**
     * Get the temporary filearea, used to store user
     * profile photos to make the certiticate
     *
     * @param int/object $context The module context
     * @return the temporary fileinfo
     */
    public static function get_certificate_tmp_fileinfo($context){

        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        $filerecord = ['contextid' => $contextid,
                            'component' => self::CERTIFICATE_COMPONENT_NAME,
                            'filearea' => 'tmp',
                            'itemid' => 0,
                            'filepath' => '/'];

        return $filerecord;
    }

    /**
     * Get issued certificate object, if it's not exist, it will be create
     *
     * @param mixed User obj or id
     * @param boolean Issue teh user certificate if it's not exists (default = true)
     * @return stdClass the issue certificate object
     */
    public function get_issue($user = null, $issueifempty = true) {
        global $DB;

        // cirano: Se hace que el usuario sea obligatorio ya que no se le genera al de sesión
        if (is_object($user)) {
            $userid = $user->id;
        } else {
            $userid = $user;
        }

        // Check if certificate has already issued
        // Trying cached first

        // The cache issue is from this user ?
        $created = false;
        if (!empty($this->issuecert) && $this->issuecert->userid == $userid) {
            if (empty($this->issuecert->haschange)) {
                // ...haschange is marked, if no return from cache.
                return $this->issuecert;
            } else {
                // ...haschange is maked, must update.
                $issuedcert = $this->issuecert;
            }
            // Not in cache, trying get from database.
        } else if (!$issuedcert = $DB->get_record('simplecertificate_issues',
                        ['userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null])) {
            // Not in cache and not in DB, create new certificate issue record.

            if (!$issueifempty) {
                // Not create a new one, only check if exists.
                return null;
            }

            // Mark as created
            $created = true;
            $issuedcert = new stdClass();
            $issuedcert->certificateid = 0; // The issue is not associated with a certificate // $this->get_instance()->id; // Intentionally
            $issuedcert->coursename = format_string($this->get_instance()->coursename, true);
            $issuedcert->userid = $userid;
            $issuedcert->haschange = 1;
            $issuedcert->timecreated = time();
            $issuedcert->timedeleted = time();
            $issuedcert->code = $this->get_issue_uuid();
            // Avoiding not null restriction.
            $issuedcert->pathnamehash = '';
            $issuedcert->certificatename = $this->get_cert_name($issuedcert);

            // cirano: No se prueba, solamente se crea porque las pruebas se hacen en la plantilla.
            $issuedcert->id = $DB->insert_record('simplecertificate_issues', $issuedcert);

            // cirano: No se envían correos
        }

        //If cache or db issued certificate is maked as haschange, must update
        if (!empty($issuedcert->haschange) && !$created) { //Check haschange, if so, reissue
            $issuedcert->certificatename = $this->get_cert_name($issuedcert);
            $DB->update_record('simplecertificate_issues', $issuedcert);
        }

        // Caching to avoid unessecery db queries.
        $this->issuecert = $issuedcert;
        return $issuedcert;
    }

    /**
     * Returns the grade to display for the certificate.
     *
     * @param int $userid
     * @return string the grade result
     */
    protected function get_grade($userid = null) {

        // cirano: La calificación no se tiene en cuenta en estos certificados ya que
        // los usuarios no son realmente matriculados/calificados
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

        // cirano: No se puede asociar la calificación a un módulo

        return false;
    }

    /**
     * Generate a UUID
     * you can verify the generated code in:
     * http://www.famkruithof.net/uuid/uuidgen?typeReq=-1
     *
     * @return string UUID
     */
    protected function get_issue_uuid() {
        global $CFG;
        require_once($CFG->libdir . '/horde/framework/Horde/Support/Uuid.php');
        return (string)new Horde_Support_Uuid();
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    protected function get_teachers() {
        global $CFG, $DB;
        $teachers = [];

        if (!empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);
        } else {
            list($coursecontactroles, $trash) = get_roles_with_cap_in_context($this->get_context(), 'mod/simplecertificate:manage');
        }
        foreach ($coursecontactroles as $roleid) {
            $roleid = (int)$roleid;
            $role = $DB->get_record('role', array('id' => $roleid));
            $users = get_role_users($roleid, $this->context, true);
            if ($users) {
                foreach ($users as $teacher) {
                    $manager = new stdClass();
                    $manager->user = $teacher;
                    $manager->username = fullname($teacher);
                    $manager->rolename = role_get_name($role, $this->get_context());
                    $teachers[$teacher->id] = $manager;
                }
            }
        }
        return $teachers;
    }

    /**
     * Create PDF object using parameters
     *
     * @return PDF
     */
    protected function create_pdf_object() {

        // Default orientation is Landescape.
        $orientation = 'L';

        if ($this->get_instance()->height > $this->get_instance()->width) {
            $orientation = 'P';
        }

        // Remove commas to avoid a bug in TCPDF where a string containing a commas will result in two strings.
        $keywords = get_string('keywords', 'simplecertificate') . ',' . format_string($this->get_instance()->coursename, true);
        $keywords = str_replace(",", " ", $keywords); // Replace commas with spaces.
        $keywords = str_replace("  ", " ", $keywords); // Replace two spaces with one.

        $pdf = new pdf($orientation, 'mm', array($this->get_instance()->width, $this->get_instance()->height), true, 'UTF-8');
        $pdf->SetTitle($this->get_instance()->name);
        $pdf->SetSubject($this->get_instance()->name . ' - ' . $this->get_instance()->coursename);
        $pdf->SetKeywords($keywords);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setFontSubsetting(true);
        $pdf->SetMargins(0, 0, 0, true);

        return $pdf;
    }

    /**
     * Create certificate PDF file
     *
     * @param stdClass $issuecert The issue certifcate obeject
     * @param PDF $pdf A PDF object, if null will create one
     * @param bool $isbulk Tell if it is a bulk operation or not
     * @return mixed PDF object or error
     */
    protected function create_pdf(stdClass $issuecert, $pdf = null, $isbulk = false) {
        global $CFG;

        // Check if certificate file is already exists, if issued has changes, it will recreated.
        if (empty($issuecert->haschange) && $this->issue_file_exists($issuecert) && !$isbulk) {
            return false;
        }

        if (empty($pdf)) {
            $pdf = $this->create_pdf_object();
        }

        $pdf->AddPage();

        // Getting certificare image.
        $fs = get_file_storage();

        // Get first page image file.
        if (!empty($this->get_instance()->certificateimage)) {
            // Prepare file record object.
            $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
            $firstpageimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'],
                            $fileinfo['filearea'],
                            $fileinfo['itemid'], $fileinfo['filepath'],
                            $this->get_instance()->certificateimage);
            // Read contents.
            if ($firstpageimagefile) {
                $tmpfilename = $firstpageimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'first_image_');
                $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                @unlink($tmpfilename);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate', $this->get_instance()->certificateimage));
            }
        }

        // Writing text.
        $pdf->SetXY($this->get_instance()->certificatetextx, $this->get_instance()->certificatetexty);
        $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->get_instance()->certificatetext), 0, 0, 0,
                            true, 'C');

        // Print QR code in first page (if enable).
        if (!empty($this->get_instance()->qrcodefirstpage) && !empty($this->get_instance()->printqrcode)) {
            $this->print_qrcode($pdf, $issuecert->code);
        }

        if (!empty($this->get_instance()->enablesecondpage)) {
            $pdf->AddPage();
            if (!empty($this->get_instance()->secondimage)) {
                // Prepare file record object.
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                // Get file.
                $secondimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->secondimage);

                // Read contents.
                if (!empty($secondimagefile)) {
                    $tmpfilename = $secondimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'second_image_');
                    $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                    @unlink($tmpfilename);
                } else {
                    print_error(get_string('filenotfound', 'simplecertificate', $this->get_instance()->secondimage));
                }
            }
            if (!empty($this->get_instance()->secondpagetext)) {
                $pdf->SetXY($this->get_instance()->secondpagex, $this->get_instance()->secondpagey);
                $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->get_instance()->secondpagetext), 0,
                                    0, 0, true, 'C');
            }
        }

        if (!empty($this->get_instance()->printqrcode) && empty($this->get_instance()->qrcodefirstpage)) {
            // Add certificade code using QRcode, in a new page (to print in the back).
            if (empty($this->get_instance()->enablesecondpage)) {
                // If secondpage is disabled, create one.
                $pdf->AddPage();
            }
            $this->print_qrcode($pdf, $issuecert->code);

        }
        return $pdf;
    }

    /**
     * Put a QR code in cerficate pdf object
     *
     * @param pdf $pdf The pdf object
     * @param string $code The certificate code
     */
    protected function print_qrcode($pdf, $code) {
        global $CFG;
        $style = ['border' => 2, 'vpadding' => 'auto', 'hpadding' => 'auto',
                        'fgcolor' => [0, 0, 0], // Black.
                        'bgcolor' => [255, 255, 255], // White.
                        'module_width' => 1, // Width of a single module in points.
                        'module_height' => 1]; // Height of a single module in points.

        $codeurl = new moodle_url("$CFG->wwwroot/mod/simplecertificate/verify.php");
        $codeurl->param('code', $code);

        $pdf->write2DBarcode($codeurl->out(false), 'QRCODE,M', $this->get_instance()->codex, $this->get_instance()->codey, 50, 50,
                            $style, 'N');
        $pdf->SetXY($this->get_instance()->codex, $this->get_instance()->codey + 49);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell(50, 0, $code, 'LRB', 0, 'C', true, '', 2);
    }

    /**
     * Save a certificate pdf file
     *
     * @param stdClass $issuecert the certificate issue record
     * @param bool $force Force to create a new file, even if it's not changed
     * @return mixed return stored_file if successful, false otherwise
     */
    public function save_pdf(stdClass $issuecert, bool $force = false) {
        global $DB;

        // Check if file exist.
        // If issue certificate has no change, it's must has a file.
        if (!$force && empty($issuecert->haschange)) {
            if ($this->issue_file_exists($issuecert)) {
                return $this->get_issue_file($issuecert);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate'));
                return false;
            }
        } else {
            // Cache issued cert, to avoid db queries.
            $this->issuecert = $issuecert;
            $pdf = $this->create_pdf($this->get_issue($issuecert->userid));
            if (!$pdf) {
                // TODO add can't create certificate file error.
                echo "Error: can't create certificate file to " . $issuecert->userid;
                return false;
            }

            // This avoid function calls loops.
            $issuecert->haschange = 0;

            // Remove old file, if exists.
            if ($this->issue_file_exists($issuecert)) {
                $file = $this->get_issue_file($issuecert);
                $file->delete();
            }

            // Prepare file record object.
            // Try get user context.
            $context = context_user::instance($issuecert->userid);

            $filename = $this->get_filecert_name($issuecert);

            // Cirano: se cambia el component, filearea y contexto para que no se amarre a la actividad.
            $fileinfo = ['contextid' => $context->id,
                    'component' => 'user',
                    'filearea' => 'private',
                    'itemid' => $issuecert->id,
                    'filepath' => '/certificates/' . $issuecert->coursename . '/',
                    'mimetype' => 'application/pdf',
                    'userid' => $issuecert->userid,
                    'filename' => $filename
            ];

            $fs = get_file_storage();
            $file = $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'));
            if (!$file) {
                print_error('cannotsavefile', 'error', '', $fileinfo['filename']);
                return false;
            }

            $issuecert->pathnamehash = $file->get_pathnamehash();

            // Verify if user is a manager, if not, update issuedcert.
            // cirano: No se tiene en cuenta el rol ya que se le crea a todos, no hay uno de prueba.
            if (!$DB->update_record('simplecertificate_issues', $issuecert)) {
                print_error('cannotupdatemod', 'error', null, 'simplecertificate_issue');
                return false;
            }

            return $file;
        }
    }

    /**
     * Return a stores_file object with issued certificate PDF file or false otherwise
     *
     * @param stdClass $issuecert Issued certificate object
     * @return mixed <stored_file, boolean>
     */
    public function get_issue_file(stdClass $issuecert) {
        if (!empty($issuecert->haschange)) {
            return $this->save_pdf($issuecert);
        }

        if (!$this->issue_file_exists($issuecert)) {
            return false;
        }

        $fs = get_file_storage();
        return $fs->get_file_by_hash($issuecert->pathnamehash);
    }

    /**
     * Get the time the user has spent in the course
     *
     * @param int $userid User ID (default= $USER->id)
     * @return int the total time spent in seconds
     */
    public function get_course_time($user = null) {

        // cirano: No se calcula el tiempo ya que no aplica para este método
        return 0;

    }

    /**
     * Delivery the issue certificate
     *
     * @param stdClass $issuecert The issued certificate object
     */
    public function output_pdf(stdClass $issuecert) {
        global $OUTPUT;

        $file = $this->get_issue_file($issuecert);
        if ($file) {
            switch ($this->get_instance()->delivery) {
                case self::OUTPUT_FORCE_DOWNLOAD:
                    send_stored_file($file, 10, 0, true, ['filename' => $file->get_filename(), 'dontdie' => true]);
                break;

                case self::OUTPUT_SEND_EMAIL:
                    // cirano: no se envía correo.
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
        } else {
            print_error(get_string('filenotfound', 'simplecertificate'));
        }
    }

    /**
     * Substitutes the certificate text variables
     *
     * @param stdClass $issuecert The issue certificate object
     * @param string $certtext The certificate text without substitutions
     * @return string Return certificate text with all substutions
     */
    protected function get_certificate_text($issuecert, $certtext = null) {
        global $DB, $CFG;

        $user = get_complete_user_data('id', $issuecert->userid);
        if (!$user) {
            print_error('nousersfound', 'moodle');
        }

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

        $a = new stdClass();
        $a->identity = $user->username; //I.F. N: 20210310; cirano
        $a->username = strip_tags(fullname($user));
        $a->idnumber = strip_tags($user->idnumber);
        $a->firstname = strip_tags($user->firstname);
        $a->lastname = strip_tags($user->lastname);
        $a->email = strip_tags($user->email);
        $a->icq = strip_tags($user->icq);
        $a->skype = strip_tags($user->skype);
        $a->yahoo = strip_tags($user->yahoo);
        $a->aim = strip_tags($user->aim);
        $a->msn = strip_tags($user->msn);
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
            $a->country = get_string($user->country, 'countries');
        } else {
            $a->country = '';
        }

        // Formatting URL, if needed.
        $url = $user->url;
        if (!empty($url) && strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }
        $a->url = $url;

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
            $t = array();
            foreach ($teachers as $teacher) {
                $t[] = content_to_text($teacher->rolename . ': ' . $teacher->username, FORMAT_MOODLE);
            }
            $a->teachers = implode("<br>", $t);
        }

        // Fetch user actitivy restuls.
        $a->userresults = $this->get_user_results($issuecert->userid);

        // Get User role name in course.
        // cirano: El rol en el curso no aplica porque el usuario realmente no se matricula
        $a->userrolename = '';

        // cirano: La fecha de matrícula no aplica porque el usuario realmente no se matricula
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

    // Auto link filter puts links in the certificate text,
    // and it's must be removed. See #111.
    protected function remove_links($htmltext) {
        global $CFG;
        require_once($CFG->libdir.'/htmlpurifier/HTMLPurifier.safe-includes.php');
        require_once($CFG->libdir.'/htmlpurifier/locallib.php');

        // This code is in weblib.php (purify_html function).
        $config = HTMLPurifier_Config::createDefault();
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        if (!file_exists($cachedir)) {
            // Purging of caches may remove the cache dir at any time,
            // luckily file_exists() results should be cached for all existing directories.
            $purifiers = array();
            $caches = array();
            gc_collect_cycles();

            make_localcache_directory('htmlpurifier', false);
            check_dir_exists($cachedir);
        }
        $config->set('Cache.SerializerPath', $cachedir);
        $config->set('Cache.SerializerPermissions', $CFG->directorypermissions);
        $config->set('HTML.ForbiddenElements', array('script', 'style', 'applet', 'a'));
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($htmltext);
    }

    /**
     * Return user profile image URL
     */
    protected function get_user_image_url($user) {
        global $CFG;

        // Beacuse bug #141 forceloginforprofileimage=enabled
        // i must check if this contiguration is enalbe and by pass it.
        $path = '/';
        $filename = 'f1';
        $usercontext = context_user::instance($user->id, IGNORE_MISSING);
        if (empty($CFG->forceloginforprofileimage)) {
            // Not enable so it's very easy.
            $url = moodle_url::make_pluginfile_url($usercontext->id, 'user', 'icon', null, $path, $filename);
            $url->param('rev', $user->picture);
        } else {

            // It's enable, so i must copy the profile image to somewhere else, so i can get the image;
            // Try to get the profile image file.
            $fs = get_file_storage();
            $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.png');

            if (!$file) {
                $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.jpg');
                if (!$file) {
                    // I Can't get the file, sorry.
                    return '';
                }
            }

            // With the file, now let's copy to plugin filearea.
            $fileinfo = self::get_certificate_tmp_fileinfo($this->get_context()->id);

            // Since f1 is the same name for all user, i must to rename the file, i think
            // add userid, since it's unique.
            $fileinfo['filename'] = 'f1-' . $user->id;

            // I must verify if image is already copied, or i get an error.
            // This file will be removed  as soon as certificate file is generated.
            if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
                // File don't exists yet, so, copy to tmp file area.
                $fs->create_file_from_storedfile($fileinfo, $file);
            }

            // Now creating the image URL.
            $url = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                    null, $fileinfo['filepath'], $fileinfo['filename']);
        }
        return '<img src="' . $url->out() . '"  width="100" height="100" />';
    }

    /**
     * Returns the date to display for the certificate.
     *
     * @param stdClass $issuecert The issue certificate object
     * @param int $userid
     * @return string the date
     */
    protected function get_date(stdClass $issuecert) {
        global $DB;

        // Get date format
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }

        // cirano: Si se envía la fecha, es esa la que se toma, sino entonces se muestra la fecha actual.
        // No se tienen en cuenta las otras opciones de configuración de fecha.
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

        // cirano: No se tiene en cuenta las calificaciones de nada.
        return 'N/A';
    }

    /**
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    protected function get_outcome($userid) {

        // cirano: No aplica para este caso
        return 'N/A';
    }

    protected function create_temp_file($file) {
        global $CFG;

        $path = make_temp_directory(self::CERTIFICATE_COMPONENT_NAME);
        return tempnam($path, $file);
    }

    protected function get_user_profile_fields($userid) {
        global $CFG, $DB;

        $usercustomfields = new stdClass();
        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        if ($categories) {
            foreach ($categories as $category) {
                $fields = $DB->get_records('user_info_field', ['categoryid' => $category->id], 'sortorder ASC');
                if ($fields) {
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                        $newfield = 'profile_field_' . $field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        if ($formfield->is_visible() && !$formfield->is_empty()) {
                            if ($field->datatype == 'checkbox') {
                                $usercustomfields->{$field->shortname} = (
                                    $formfield->data == 1 ? get_string('yes') : get_string('no')
                                );
                            } else {
                                $usercustomfields->{$field->shortname} = $formfield->display_data();
                            }
                        } else {
                            $usercustomfields->{$field->shortname} = '';
                        }
                    }
                }
            }
        }
        return $usercustomfields;
    }

    /**
     * Verify if user meet issue conditions
     *
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    protected function can_issue($user = null, $chkcompletation = true) {
        // cirano: No se valida ya que se llaman por un usuario central con permisos en el curso
        return true;
    }

    /**
     * get full user status of on certificate instance (if it can view/access)
     * this method helps the unit test (easy to mock)
     * @param int $userid
     */
    protected function check_user_can_access_certificate_instance($userid) {
        // cirano: No se valida ya que se llaman por un usuario central con permisos en el curso
        return true;
    }

    /**
     * Verify if cetificate file exists
     *
     * @param stdClass $issuecert Issued certificate object
     * @return true if exist
     */
    protected function issue_file_exists(stdClass $issuecert) {
        $fs = get_file_storage();

        // Check for file first.
        return $fs->file_exists_by_hash($issuecert->pathnamehash);
    }

    private function get_filecert_name($issue) {
        $name = '';

        $coursename = $this->get_instance()->coursename;
        if (!empty($coursename)) {
            $name .= $coursename;
        }
        $name .=  '_';

        $certificatename = $this->get_instance()->name;
        if (!empty($certificatename)) {
            $name .= $certificatename;
        }
        $name .=  '_';

        if (!empty($issue->timecreated)) {
            $name .= date('Ymd', $issue->timecreated);
        }

        $name .= '_' . $issue->id . '.pdf';

        $name = str_replace(self::CHARS_NOT, self::CHARS_YES, $name);
        $name = str_replace(" ", "_", $name);

        return clean_filename($name);
    }

    private function get_cert_name($issue = null) {
        $formatedcoursename = $this->get_instance()->coursename;
        $formatedcertificatename = $this->get_instance()->name;
        $certificatename = format_string($formatedcoursename . '-' . $formatedcertificatename, true);

        if ($issue && !empty($issue->timecreated)) {
            $certificatename .= ' ' . date('Y-m-d', $issue->timecreated);
        }

        return $certificatename;
    }

    public function download_bulk($bulk, array $issues) {

        global $DB;

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
        $zipper = new zip_packer();
        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            // Send file and delete after sending.
            ob_clean();
            send_temp_file($tempzip, $filename);
        }
    }

    /**
     * Print issed file certificate link
     *
     * @param stdClass $issuecert The issued certificate object
     * @param bool $shortname
     * @return string file link url
     */
    public function print_issue_certificate_file(stdClass $issuecert, $shortname = false) {
        global $CFG, $OUTPUT;

        // Trying to cath course module context
        try {
            $fs = get_file_storage();
            if (!$fs->file_exists_by_hash($issuecert->pathnamehash)) {
                throw new moodle_exception('filenotfound', 'simplecertificate', null, null, '');
            }

            $file = $fs->get_file_by_hash($issuecert->pathnamehash);
            $output = $OUTPUT->pix_icon(file_mimetype_icon($file->get_mimetype()), $file->get_mimetype()) . '&nbsp;';

            $url = new moodle_url('/mod/simplecertificate/wmsendfile.php');
            $url->param('code', $issuecert->code);

            $fullfilename = s($file->get_filename());
            $filename = $fullfilename;
            if ($shortname && strlen($filename) > 33) {
                $filename = substr($filename, 0, 15) . '...' . substr($filename, -15, 15);
            }

            $output .= '<a href="' . $url->out(true) . '" target="_blank" title="' . $fullfilename . '" >' . $filename . '</a>';

        } catch (Exception $e) {
            $output = get_string('filenotfound', 'simplecertificate', '');
        }

        return '<div class="files">' . $output . '<br /> </div>';

    }
}
