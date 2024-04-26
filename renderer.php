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
 * Bulk certification renderer logic implementation.
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot . '/course/format/renderer.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/certificatelib.php';

/**
 * Basic renderer for bulkcertification format.
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_bulkcertification_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_topics_renderer::section_edit_controls() only displays the 'Set current section'
        // control when editing mode is on we need to be sure that the link 'Turn editing mode on' is
        // available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', ['class' => 'topics']);
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate next/previous section links for navigation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {

        return null;
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $current) {
        global $PAGE;

        $canmanage = has_capability('mod/simplecertificate:manage', $PAGE->context);

        if (!$canmanage) {
            $this->print_sections($course, $sections, $mods, $modnames, $modnamesused);
        }
        else {
            $this->print_tabs($course, $current);

            switch($current) {
                case 'templates':
                    $this->print_sections($course, $sections, $mods, $modnames, $modnamesused);
                    break;
                case 'bulk':
                    $this->print_form_bulk($course);
                    break;
                case 'certified':
                    $action = optional_param('action', '', PARAM_ALPHA);

                    if (!empty($action)) {
                        switch($action) {
                            case 'rebuildall':
                                $this->rebuild_issue($course, true);
                                $this->print_certified_bulk($course);
                                break;
                            case 'rebuild':
                                $this->rebuild_issue($course);
                                // After the rebuild, list the certificates.
                            case 'details':
                                $this->print_certified_list($course);
                                break;
                        }
                    }
                    else {
                        $this->print_certified_bulk($course);
                    }
                    break;
                case 'reports':
                    $this->print_reports($course);
                    break;
                case 'objectives':
                    if (has_capability('format/bulkcertification:manage', context_system::instance())) {
                        $this->print_manage_objectives($course);
                    } else {
                        print_error('nopermissiontoviewpage');
                    }
                    break;
            }
        }

    }

    private function print_reports($course) {
        global $OUTPUT, $DB;

        $canglobal = has_capability('mod/simplecertificate:manage', context_system::instance());

        echo $OUTPUT->heading(get_string('course_statistics', 'format_bulkcertification'));

        $stats = [];
        $stats_labels = [];
        $stats_labels['count_bulk'] = get_string('count_bulk', 'format_bulkcertification');
        $stats_labels['count_issues'] = get_string('count_issues', 'format_bulkcertification');
        $stats_labels['count_by_certificate'] = get_string('count_by_certificate', 'format_bulkcertification');
        $stats_labels['count_users'] = get_string('count_users', 'format_bulkcertification');

        $stats['count_bulk'] = $DB->count_records('bulkcertification_bulk', ['courseid' => $course->id]);

        $sql = 'SELECT COUNT(1)
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id
                    WHERE bb.courseid = ?';
        $stats['count_issues'] = $DB->count_records_sql($sql, array('courseid' => $course->id));

        $sql = 'SELECT bi.id AS id, bb.certificatename AS title, COUNT(1) AS total
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id
                    WHERE bb.courseid = ?
                    GROUP BY bb.certificateid, bb.certificatename';
        $stats['count_by_certificate'] = $DB->get_records_sql($sql, array('courseid' => $course->id));

        $sql = 'SELECT COUNT(DISTINCT si.userid)
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id
                    INNER JOIN {simplecertificate_issues} si ON bi.issueid = si.id
                    WHERE bb.courseid = ?';
        $stats['count_users'] = $DB->count_records_sql($sql, array('courseid' => $course->id));

        $table = new html_table();
        $table->attributes['class'] = 'admintable generaltable format_bulkcertification_table';

        $table->head = [];
        $table->head[] = get_string('statistic_label', 'format_bulkcertification');
        $table->head[] = get_string('statistic_value', 'format_bulkcertification');

        foreach($stats as $key => $statistic) {
            $data = array();
            $data[] = $stats_labels[$key];

            if (is_string($statistic) || is_numeric($statistic)) {
                $data[] = $statistic;
            } else if (is_array($statistic)) {
                $list = html_writer::start_tag('ul');
                foreach($statistic as $one) {
                    $list .= html_writer::start_tag('li');
                    $list .= html_writer::tag('strong', $one->title . ': ');
                    $list .= html_writer::tag('span', $one->total);
                    $list .= html_writer::end_tag('li');
                }
                $list .= html_writer::end_tag('ul');
                $data[] = $list;
            } else {
                $data[] = '';
            }

            $table->data[] = $data;
        }

        echo html_writer::table($table);

        if ($canglobal) {
            echo $OUTPUT->heading(get_string('site_statistics', 'format_bulkcertification'));

            $stats = array();
            $stats['count_bulk'] = $DB->count_records('bulkcertification_bulk');

            $sql = 'SELECT COUNT(1)
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id';
            $stats['count_issues'] = $DB->count_records_sql($sql);

            $sql = 'SELECT bi.id AS id, bb.certificatename AS title, COUNT(1) AS total
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id
                    GROUP BY bb.certificateid, bb.certificatename';
            $stats['count_by_certificate'] = $DB->get_records_sql($sql);

            $sql = 'SELECT COUNT(DISTINCT si.userid)
                    FROM {bulkcertification_bulk} bb
                    INNER JOIN {bulkcertification_issues} bi ON bi.bulkid = bb.id
                    INNER JOIN {simplecertificate_issues} si ON bi.issueid = si.id';
            $stats['count_users'] = $DB->count_records_sql($sql);

            $table = new html_table();
            $table->attributes['class'] = 'admintable generaltable format_bulkcertification_table';

            $table->head = [];
            $table->head[] = get_string('statistic_label', 'format_bulkcertification');
            $table->head[] = get_string('statistic_value', 'format_bulkcertification');

            foreach($stats as $key => $statistic) {
                $data = [];
                $data[] = $stats_labels[$key];

                if (is_string($statistic) || is_numeric($statistic)) {
                    $data[] = $statistic;
                } else if (is_array($statistic)) {
                    $list = html_writer::start_tag('ul');
                    foreach($statistic as $one) {
                        $list .= html_writer::start_tag('li');
                        $list .= html_writer::tag('strong', $one->title . ': ');
                        $list .= html_writer::tag('span', $one->total);
                        $list .= html_writer::end_tag('li');
                    }
                    $list .= html_writer::end_tag('ul');
                    $data[] = $list;
                } else {
                    $data[] = '';
                }

                $table->data[] = $data;
            }

            echo html_writer::table($table);
        }
    }

    private function print_manage_objectives($course) {
        global $CFG, $DB, $OUTPUT, $USER, $PAGE;

        require_once $CFG->dirroot . '/course/format/bulkcertification/objective_form.php';
        require_once $CFG->dirroot . '/course/format/bulkcertification/bulk_objective_form.php';
        require_once $CFG->dirroot . '/course/format/bulkcertification/filters/lib.php';

        $delete       = optional_param('delete', 0, PARAM_INT);
        $confirm      = optional_param('confirm', '', PARAM_ALPHANUM); // Md5 confirmation hash.
        $sort         = optional_param('sort', 'name', PARAM_ALPHA);
        $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
        $page         = optional_param('spage', 0, PARAM_INT);
        $perpage      = optional_param('perpage', 20, PARAM_INT); // How many per page.

        // Delete a group, after confirmation.
        if ($delete && confirm_sesskey()) {
            $objective = $DB->get_record('bulkcertification_objectives', ['id' => $delete], '*', MUST_EXIST);

            if ($confirm != md5($delete)) {
                $returnurl = new moodle_url('/course/view.php', [
                                                                    'id' => $course->id,
                                                                    'tab' => 'objectives',
                                                                    'sort' => $sort,
                                                                    'dir' => $dir,
                                                                    'perpage' => $perpage,
                                                                    'page' => $page
                                                                ]);
                echo $OUTPUT->heading(get_string('objective_delete', 'format_bulkcertification'));
                $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
                echo $OUTPUT->confirm(get_string('deletecheck', '', "'{$objective->name} - {$objective->code}'"),
                                        new moodle_url($returnurl, $optionsyes), $returnurl);
                return;
            }
            else if ($data = data_submitted()) {
                if ($DB->delete_records('bulkcertification_objectives', ['id' => $delete])) {
                    echo $OUTPUT->notification(get_string('objectives_deleted', 'format_bulkcertification'), 'notifysuccess');
                } else {
                    echo $OUTPUT->notification(get_string('objectives_errordeleting', 'format_bulkcertification'));
                }
            }
        }

        $data = new stdClass();
        $data->id = $course->id;
        $objectiveform = new format_bulkcertification_objective_form(null, ['data' => $data]);

        $postobjective = $objectiveform->get_data();
        if ($postobjective) {
            $objective = new stdClass();
            $objective->name    = $postobjective->objectivename;
            $objective->code    = $postobjective->code;
            $objective->hours   = $postobjective->hours;
            $objective->type    = $postobjective->type;

            if ($DB->insert_record('bulkcertification_objectives', $objective)) {
                echo $OUTPUT->notification(get_string('objective_added', 'format_bulkcertification'), 'notifysuccess');
            } else {
                echo $OUTPUT->notification(get_string('objectives_erroradding', 'format_bulkcertification'));
            }
        }

        $delimiters = ['comma' => ',', 'semicolon' => ';', 'tab' => '\t'];
        $bulkobjectiveform = new format_bulkcertification_bulk_objective_form(null, ['data' => $data, 'delimiters' => $delimiters]);

        $bulklogs = [];
        $postbulkobjective = $bulkobjectiveform->get_data();
        if ($postbulkobjective) {

            $delimiter = $postbulkobjective->delimiter == 'tab' ? "\t" : $delimiters[$postbulkobjective->delimiter];

            $lines = explode("\n", $postbulkobjective->objectiveslist);

            if ($postbulkobjective->mode == 'replace') {
                $DB->delete_records('bulkcertification_objectives');
                $bulklogs[] = $OUTPUT->notification(get_string('recordsdeleted', 'format_bulkcertification'), 'notifysuccess');
            }

            $k = 0;
            foreach($lines as $line) {

                $k++;
                if (empty(trim($line))) {
                    continue;
                }

                $fields = explode($delimiter, $line);

                if (count($fields) != 4) {
                    $bulklogs[] = $OUTPUT->notification(get_string('fieldsincorrectsize', 'format_bulkcertification', $k));
                } else {
                    $objective = new stdClass();
                    $objective->name    = substr(trim($fields[0]), 0, 255);
                    $objective->code    = substr(trim($fields[1]), 0, 31);
                    $objective->hours   = trim($fields[2]);
                    $objective->type    = trim($fields[3]) == 'local' ? 'local' : 'remote';

                    if (!is_numeric($objective->hours)) {
                        $bulklogs[] = $OUTPUT->notification(get_string('bulkhoursnotnumber', 'format_bulkcertification', $k));
                    } else if (strlen($objective->hours) > 4) {
                        $bulklogs[] = $OUTPUT->notification(get_string('bulkhoursmaxerror', 'format_bulkcertification', $k));
                    } else {

                        // Check for duplicate code.
                        if ($DB->get_record('bulkcertification_objectives', ['code' => $objective->code], '*', IGNORE_MULTIPLE)) {
                            $bulklogs[] = $OUTPUT->notification(get_string('bulkcodetaken', 'format_bulkcertification', $k));
                        } else {

                            if (!$DB->insert_record('bulkcertification_objectives', $objective)) {
                                $bulklogs[] = $OUTPUT->notification(get_string('bulkerroradding', 'format_bulkcertification', $k));
                            }
                        }
                    }
                }
            }

        }

        // Create the filter form.
        $ifiltering = new format_bulkcertification_objective_filtering(null, ['id' => $course->id, 'tab' => 'objectives']);

        list($extrasql, $params) = $ifiltering->get_sql_filter();

        $objectives = format_bulkcertification_get_objectives_listing(true, $sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasql, $params);
        $objectivescount = format_bulkcertification_get_objectives_listing(false);
        $objectivessearchcount = format_bulkcertification_get_objectives_listing(false, '', '', 0, 0, '', '', '', $extrasql, $params);

        if ($extrasql != '' && $objectivescount) {
            $a = new stdClass();
            $a->count = $objectivessearchcount;
            $a->total = $objectivescount;
            echo $OUTPUT->heading(get_string('objectives_count', 'format_bulkcertification', $a));
            $objectivescount = $objectivessearchcount;
        }
        else {
            echo $OUTPUT->heading(get_string('objectives', 'format_bulkcertification', $objectivescount));
        }

        $pagingbar = new paging_bar($objectivescount, $page, $perpage, "/course/view.php?tab=objectives&id={$course->id}&sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
        $pagingbar->pagevar = 'spage';
        echo $OUTPUT->render($pagingbar);

        // add filters
        $ifiltering->display_add();
        $ifiltering->display_active();

        $table = new html_table();
        $table->attributes['class'] = 'admintable generaltable format_bulkcertification_table';

        $table->head = [];

        $columns = [];
        $columns['name'] = get_string('objective_name', 'format_bulkcertification');
        $columns['code'] = get_string('code', 'format_bulkcertification');
        $columns['hours'] = get_string('objective_hours', 'format_bulkcertification');
        $columns['type'] = get_string('objective_type', 'format_bulkcertification');

        foreach ($columns as $ckey => $column) {
            if ($sort != $ckey) {
                $columnicon = "";
                $columndir = "ASC";
            }
            else {
                $columndir = $dir == "ASC" ? "DESC":"ASC";
                $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
                $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, '');

            }
            $url = new moodle_url('/course/view.php', [
                                                        'id' => $course->id,
                                                        'tab' => 'objectives',
                                                        'sort' => $ckey,
                                                        'dir' => $columndir,
                                                        'perpage' => $perpage,
                                                        'page' => $page
                                                    ]);
            $table->head[] = html_writer::link($url, $column) . $columnicon;
        }

        // Operations column.
        $table->head[] = '';

        if($objectives) {
            foreach($objectives as $objective) {
                $data = [];
                $data[] = $objective->name;
                $data[] = $objective->code;
                $data[] = $objective->hours;
                $data[] = get_string('type_' . $objective->type, 'format_bulkcertification');

                $url = new moodle_url('/course/view.php', [
                                                            'id' => $course->id,
                                                            'tab' => 'objectives',
                                                            'delete' => $objective->id,
                                                            'sesskey' => sesskey()
                                                        ]);
                $data[] = $OUTPUT->action_link($url, get_string('delete'));
                $table->data[] = $data;
            }
        }

        echo html_writer::table($table);
        echo $OUTPUT->render($pagingbar);

        $objectiveform->display();

        $bulkobjectiveform->display();

        foreach($bulklogs as $log) {
            echo $log;
        }
    }

    /**
     * Rebuild one or all bulk certificates.
     *
     * @param stdClass $course
     * @param bool $all Rebuild a bulk release
     * @param int $bulkorissueid Bulk or issue id. If $all = true then $bulkorissueid is the bulk id
     * @param bool $clone Create the issues as a new release
     * @return void
     */
    public function rebuild_issue($course, $all = false, $bulkorissueid = null, $clone = false) {
        global $DB, $OUTPUT;

        $issues = [];

        if (!$all) {
            if (!$bulkorissueid) {
                $issueid = required_param('issueid', PARAM_INT);
            } else {
                $issueid = $bulkorissueid;
            }
            $issues[] = $DB->get_record('bulkcertification_issues', ['id' => $issueid], '*', MUST_EXIST);
            $bulk = $DB->get_record('bulkcertification_bulk', ['id' => $issues[0]->bulkid], '*', MUST_EXIST);
        } else {
            if (!$bulkorissueid) {
                $bulkid = required_param('bulkid', PARAM_INT);
            } else {
                $bulkid = $bulkorissueid;
            }
            $bulk = $DB->get_record('bulkcertification_bulk', ['id' => $bulkid], '*', MUST_EXIST);
            $issues = $DB->get_records('bulkcertification_issues', ['bulkid' => $bulkid]);
        }

        if (!$cm = get_coursemodule_from_instance('simplecertificate', $bulk->certificateid)) {
            echo $OUTPUT->notification(get_string('module_notfound', 'format_bulkcertification'));
            return;
        }

        if (!$certificate = $DB->get_record('simplecertificate', ['id' => $cm->instance])) {
            echo $OUTPUT->notification(get_string('template_notfound', 'format_bulkcertification'));
            return;
        }

        if (!$course) {
            $course = $DB->get_record('course', ['id' => $certificate->course], '*', MUST_EXIST);
        }

        foreach ($issues as $issue) {
            if($issue) {

                $simpleissue = $DB->get_record('simplecertificate_issues', ['id' => $issue->issueid]);

                // Load the certificate object.
                $context = context_module::instance($cm->id);
                $certificate->coursehours = $bulk->localhours;
                $certificate->certdate = $bulk->customtime;
                $certificate->customparams = $bulk->customparams;
                $course->fullname = $bulk->coursename;
                $simplecertificate = new format_bulkcertification_simplecertificate($context, $cm, $course);
                $simplecertificate->set_instance($certificate);

                $simpleissue->haschange = 1;
                $simpleissue->timedeleted = time();

                if ($clone) {
                    unset($simpleissue->id);
                    $simpleissue->certificateid = 0;
                    $simpleissue = $simplecertificate->get_issue($simpleissue->userid, true);

                    // Change the issue by the new one in the bulk reference.
                    $data = new stdClass();
                    $data->id = $issue->id;
                    $data->issueid = $simpleissue->id;
                    $DB->update_record('bulkcertification_issues', $data);
                }

                if ($file = $simplecertificate->save_pdf($simpleissue, true)){
                    echo $OUTPUT->notification(get_string('certificate_ok', 'format_bulkcertification', $file->get_filename()), 'notifysuccess');
                } else {
                    echo $OUTPUT->notification(get_string('rebuild_error', 'format_bulkcertification', $simpleissue->id));
                }

            } else {
                echo $OUTPUT->notification(get_string('issue_notfound', 'format_bulkcertification'));
            }
        }
    }

    private function print_certified_list($course) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        $bulkid     = required_param('bulkid', PARAM_INT);
        $delete     = optional_param('delete', 0, PARAM_INT);
        $confirm    = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash


        $bulk  = $DB->get_record('bulkcertification_bulk', array('id' => $bulkid), '*', MUST_EXIST);

        $userformatdate = get_string('strftimedaydate');
        $userformatdatetime = get_string('strftimedaydatetime');

        $candelete = has_capability('format/bulkcertification:deleteissues', $PAGE->context);

        // Delete a certificate, after confirmation
        if ($delete && confirm_sesskey()) {

            if ($candelete) {
                $issue          = $DB->get_record('bulkcertification_issues', array('id' => $delete), '*', MUST_EXIST);
                $simpleissue    = $DB->get_record('simplecertificate_issues', array('id' => $issue->issueid), '*', MUST_EXIST);
                $user           = $DB->get_record('user', array('id' => $simpleissue->userid), '*', MUST_EXIST);
                $fullname = fullname($user);

                if ($confirm != md5($delete)) {
                    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id, 'bulkid' => $bulkid, 'tab' => 'certified', 'action' => 'details'));
                    echo $OUTPUT->heading(get_string('onecertificates_delete', 'format_bulkcertification'));
                    $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
                    $bulktime = userdate($bulk->bulktime, $userformatdatetime);
                    echo $OUTPUT->confirm(get_string('deletecheck', '', get_string('certificate_owner', 'format_bulkcertification', $fullname)), new moodle_url($returnurl, $optionsyes), $returnurl);
                    return;
                }
                else if ($data = data_submitted()) {
                    $this->delete_one_certificate($course, $bulk, $issue, $simpleissue);
                }
            }
        }

        echo $OUTPUT->heading(get_string('certificate_detail', 'format_bulkcertification'));

        $s_bulktime         = get_string('bulktime', 'format_bulkcertification');
        $s_issuing          = get_string('issuing', 'format_bulkcertification');
        $s_coursename       = get_string('objective_name', 'format_bulkcertification');
        $s_code             = get_string('code', 'format_bulkcertification');
        $s_groupcode        = get_string('groupcode', 'format_bulkcertification');
        $s_customtime       = get_string('objective_date', 'format_bulkcertification');
        $s_remotetime       = get_string('remote_date', 'format_bulkcertification');
        $s_hours            = get_string('objective_hours', 'format_bulkcertification');

        $s_certificatename  = get_string('template', 'format_bulkcertification');

        $issuing = $DB->get_record('user', array('id' => $bulk->issuingid));
        $issuingnames = fullname($issuing);

        $bulktime = userdate($bulk->bulktime, $userformatdatetime);
        $customtime = userdate($bulk->customtime, $userformatdate);
        $remotetime = userdate($bulk->remotetime, $userformatdate);

        $a = new stdClass();
        $a->local = $bulk->localhours;
        $a->remote = $bulk->remotehours;
        $hours = get_string('hours_multi', 'format_bulkcertification', $a);

        if (!$cm = get_coursemodule_from_instance( 'simplecertificate', $bulk->certificateid)) {
            echo $OUTPUT->notification(get_string('module_notfound', 'format_bulkcertification'));
            $templateurl = '';
        } else {
            $templateurl = new moodle_url('/mod/simplecertificate/view.php?id=' . $cm->id);
        }

        $detail_html = <<<EOD
    <table class="generaltable format_bulkcertification_table view_table">
        <tbody>
            <tr>
                <th>{$s_bulktime}</th>
                <td>{$bulktime}</td>
            </tr>
            <tr>
                <th>{$s_issuing}</th>
                <td>{$issuingnames}</td>
            </tr>
            <tr>
                <th>{$s_coursename}</th>
                <td>{$bulk->coursename}</td>
            </tr>
            <tr>
                <th>{$s_code}</th>
                <td>{$bulk->code}</td>
            </tr>
            <tr>
                <th>{$s_groupcode}</th>
                <td>{$bulk->groupcode}</td>
            </tr>
            <tr>
                <th>{$s_customtime}</th>
                <td>{$customtime}</td>
            </tr>
            <tr>
                <th>{$s_remotetime}</th>
                <td>{$remotetime}</td>
            </tr>
            <tr>
                <th>{$s_hours}</th>
                <td>{$hours}</td>
            </tr>
            <tr>
                <th>{$s_certificatename}</th>
                <td><a href="{$templateurl}">{$bulk->certificatename}</a></td>
            </tr>
        </tbody>
    </table>

EOD;

        echo $detail_html;

        $sql = "SELECT bi.id, u.id AS userid, u.firstname, u.lastname, u.idnumber, u.email, u.username, bi.issueid
                    FROM {bulkcertification_issues} AS bi
                    INNER JOIN {simplecertificate_issues} AS si ON si.id = bi.issueid
                    INNER JOIN {user} AS u ON u.id = si.userid
                    WHERE bi.bulkid = ?
                ";
        $issues = $DB->get_records_sql($sql, array('bulkid' => $bulkid));

        $table = new html_table();
        $table->attributes['class'] = 'admintable generaltable format_bulkcertification_table';
        $table->head = [];
        $table->head[] = get_string('name');
        $table->head[] = get_string('receiveddate', 'simplecertificate');
        $table->head[] = get_string('idnumber');
        $table->head[] = get_string('username');
        $table->head[] = get_string('email');

        //Operations column
        $table->head[] = '';

        $simplecertificate = new format_bulkcertification_simplecertificate(null, null, $course);
        $site = get_site();
        foreach ($issues as $issue) {

            $simpleissue = $DB->get_record('simplecertificate_issues', array('id' => $issue->issueid));

            $row = array ();
            $row[] = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$issue->userid}&amp;course={$site->id}\">{$issue->firstname} {$issue->lastname}</a>";
            $row[] = userdate($simpleissue->timecreated, $userformatdatetime) . $simplecertificate->print_issue_certificate_file($simpleissue, true);
            $row[] = $issue->idnumber;
            $row[] = $issue->username;
            $row[] = $issue->email;

            $menu = new action_menu();
            $menu->set_alignment(action_menu::TL, action_menu::BL);
            $menu->set_menu_trigger(get_string('edit'));

            $url = new moodle_url('/course/view.php', array('id'=> $course->id, 'tab' => 'certified', 'bulkid' => $bulkid, 'action' => 'rebuild', 'issueid' => $issue->id));
            $action = new action_link($url, get_string('rebuild', 'format_bulkcertification'));
            $action->primary = false;
            $menu->add($action);

            if ($candelete) {
                $url = new moodle_url('/course/view.php', array('id'=> $course->id, 'tab' => 'certified', 'bulkid' => $bulkid, 'action' => 'details', 'delete' => $issue->id, 'sesskey' => sesskey()));
                $action = new action_link($url, get_string('delete'));
                $action->primary = false;
                $menu->add($action);
            }

            $row[] = $OUTPUT->render_action_menu($menu);

            $table->data[] = $row;
        }

        echo html_writer::table($table);

        $baseurl = new moodle_url('/course/format/bulkcertification/export.php', array('id' => $course->id, 'bulkid' => $bulkid, 'type' => 'detail'));
        echo $OUTPUT->box_start();
        echo '<ul>';
        echo '    <li><a href="' . $baseurl . '&format=csv">'.get_string('downloadtext').'</a></li>';
        echo '    <li><a href="' . $baseurl . '&format=ods">'.get_string('downloadods').'</a></li>';
        echo '    <li><a href="' . $baseurl . '&format=xls">'.get_string('downloadexcel').'</a></li>';
        echo '</ul>';
        echo $OUTPUT->box_end();

        $url_return = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id, 'tab' => 'certified'));
        echo $OUTPUT->container_start('buttons');
        echo $OUTPUT->single_button($url_return, get_string('back'));
        echo $OUTPUT->container_end();

    }

    private function print_certified_bulk($course) {
        global $CFG, $DB, $OUTPUT, $USER, $PAGE;

        require_once $CFG->dirroot . '/course/format/bulkcertification/filters/lib.php';
        $delete       = optional_param('delete', 0, PARAM_INT);
        $confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
        $sort         = optional_param('sort', 'id', PARAM_ALPHA);
        $dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
        $page         = optional_param('spage', 0, PARAM_INT);
        $perpage      = optional_param('perpage', 20, PARAM_INT);        // how many per page

        $userformatdate = get_string('strftimedatefullshort');
        $userformatdatetime = get_string('strftimedatetimeshort');
        $candelete = has_capability('format/bulkcertification:deleteissues', $PAGE->context);

        // Delete a group of certificates, after confirmation
        if ($delete && confirm_sesskey()) {
            $bulk = $DB->get_record('bulkcertification_bulk', array('id' => $delete), '*', MUST_EXIST);

            if ($candelete) {
                if ($confirm != md5($delete)) {
                    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'certified', 'sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page' => $page));
                    echo $OUTPUT->heading(get_string('allcertificates_delete', 'format_bulkcertification'));
                    $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
                    $bulktime = userdate($bulk->bulktime, $userformatdatetime);
                    echo $OUTPUT->confirm(get_string('deletecheck', '', "'{$bulk->certificatename} - {$bulk->code} - {$bulktime}'"), new moodle_url($returnurl, $optionsyes), $returnurl);
                    return;
                }
                else if ($data = data_submitted()) {
                    $this->delete_certificates_group($delete, $course);
                }
            }
        }

        // create the filter form
        $ifiltering = new format_bulkcertification_filtering(null, array('id' => $course->id, 'tab' => 'certified'));


        list($extrasql, $params) = $ifiltering->get_sql_filter('courseid = :courseid', array('courseid' => $course->id));

        $bulklist = format_bulkcertification_get_bulk_listing(true, $sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasql, $params);
        $bulklistcount = format_bulkcertification_get_bulk_listing(false);
        $bulklistsearchcount = format_bulkcertification_get_bulk_listing(false, '', '', 0, 0, '', '', '', $extrasql, $params);

        if ($extrasql != '' && $bulklistcount) {
            $a = new stdClass();
            $a->count = $bulklistsearchcount;
            $a->total = $bulklistcount;
            echo $OUTPUT->heading(get_string('bulklist_count', 'format_bulkcertification', $a));
            $bulklistcount = $bulklistsearchcount;
        }
        else {
            echo $OUTPUT->heading(get_string('bulklist', 'format_bulkcertification', $bulklistcount));
        }

        $pagingbar = new paging_bar($bulklistcount, $page, $perpage, "/course/view.php?tab=certified&id={$course->id}&sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
        $pagingbar->pagevar = 'spage';
        echo $OUTPUT->render($pagingbar);

        // add filters
        $ifiltering->display_add();
        $ifiltering->display_active();

        $table = new html_table();
        $table->attributes['class'] = 'admintable generaltable format_bulkcertification_table';

        $table->head = array();

        $columns = array();
        $columns['bulktime'] = get_string('bulktime', 'format_bulkcertification');
        $columns['issuing'] = get_string('issuing', 'format_bulkcertification');
        $columns['coursename'] = get_string('objective_name', 'format_bulkcertification');
        $columns['code'] = get_string('code', 'format_bulkcertification');
        $columns['groupcode'] = get_string('groupcode', 'format_bulkcertification');
        $columns['customtime'] = get_string('objective_date', 'format_bulkcertification');
        $columns['certificatename'] = get_string('template', 'format_bulkcertification');

        foreach ($columns as $ckey => $column) {
            if ($ckey == 'issuing') {
                $table->head[] = $column;
                continue;
            }

            if ($sort != $ckey) {
                $columnicon = "";
                $columndir = "ASC";
            }
            else {
                $columndir = $dir == "ASC" ? "DESC":"ASC";
                $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
                $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, '');

            }
            $url = new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'certified', 'sort' => $ckey, 'dir' => $columndir, 'perpage' => $perpage, 'page'=>$page));
            $table->head[] = html_writer::link($url, $column) . $columnicon;
        }

        // Operations column.
        $table->head[] = '';

        if($bulklist) {

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

                $data = array ();
                $data[] = $bulk->bulktime ? userdate($bulk->bulktime, $userformatdatetime) : '';
                $data[] = $issuingname;
                $data[] = $bulk->coursename;
                $data[] = $bulk->code;
                $data[] = $bulk->groupcode;
                $data[] = $bulk->customtime ? userdate($bulk->customtime, $userformatdate) : '';
                $data[] = $bulk->certificatename;

                $menu = new action_menu();
                $menu->set_alignment(action_menu::TL, action_menu::BL);
                $menu->set_menu_trigger(get_string('edit'));

                $url = new moodle_url('/course/view.php', array('id'=> $course->id, 'tab' => 'certified', 'bulkid' => $bulk->id, 'action' => 'details'));
                $action = new action_link($url, get_string('view'));
                $action->primary = false;
                $menu->add($action);

                $url = new moodle_url('/course/format/bulkcertification/download.php', array('id'=> $course->id, 'bulkid' => $bulk->id));
                $action = new action_link($url, get_string('download_bulk', 'format_bulkcertification'));
                $action->primary = false;
                $menu->add($action);

                $url = new moodle_url('/course/view.php', ['id'=> $course->id, 'tab' => 'certified', 'bulkid' => $bulk->id, 'action' => 'rebuildall']);
                $action = new action_link($url, get_string('rebuild', 'format_bulkcertification'));
                $action->primary = false;
                $menu->add($action);

                if ($candelete) {
                    $url = new moodle_url('/course/view.php', array('id'=> $course->id, 'tab' => 'certified', 'delete' => $bulk->id, 'sesskey' => sesskey()));
                    $action = new action_link($url, get_string('delete'));
                    $action->primary = false;
                    $menu->add($action);
                }

                $data[] = $OUTPUT->render_action_menu($menu);
                $table->data[] = $data;
            }
        }

        echo html_writer::table($table);
        echo $OUTPUT->render($pagingbar);

        $baseurl = new moodle_url('/course/format/bulkcertification/export.php', array('id' => $course->id, 'sort' => $sort, 'dir' => $dir));
        echo $OUTPUT->box_start();
        echo '<ul>';
        echo '    <li><a href="' . $baseurl . '&format=csv">'.get_string('downloadtext').'</a></li>';
        echo '    <li><a href="' . $baseurl . '&format=ods">'.get_string('downloadods').'</a></li>';
        echo '    <li><a href="' . $baseurl . '&format=xls">'.get_string('downloadexcel').'</a></li>';
        echo '</ul>';
        echo $OUTPUT->box_end();

    }

    private function print_form_bulk($course) {
        global $CFG, $DB, $OUTPUT, $USER, $PAGE;

        require_once $CFG->dirroot . '/course/format/bulkcertification/bulk_form.php';
        require_once $CFG->dirroot . '/course/format/bulkcertification/build_form.php';
        require_once $CFG->dirroot . '/course/format/bulkcertification/localbuild_form.php';
        require_once $CFG->libdir  . '/filelib.php';

        $data = new stdClass();
        $data->id = $course->id;
        $bulkform = new format_bulkcertification_bulk_form(NULL, ['data' => $data]);

        $objective = null;
        $group = null;
        $postbulk = $bulkform->get_data();
        if ($postbulk) {

            $objective = $DB->get_record('bulkcertification_objectives', ['code' => $postbulk->groupcode]);

            if ($objective && $objective->type == 'local') {

                $group = new stdClass();

                $group->objective = $objective;
                $group->objective->group = $postbulk->groupcode;
                $group->objective->enddate = 0;

                $group->users = [];

            } else {
                $group = $this->get_external_group($postbulk->groupcode);
                $objective = null;

                if ($group) {

                    if (empty($group->objective->code)) {
                        echo $OUTPUT->notification(get_string('empty_sapcode', 'format_bulkcertification'));
                    } else {
                        $objective = $DB->get_record('bulkcertification_objectives', ['code' => $group->objective->code]);

                        if (!$objective) {
                            echo $OUTPUT->notification(get_string('objectives_notfound', 'format_bulkcertification'));
                        }
                    }
                }
            }
        }

        $data = new stdClass();
        $data->id = $course->id;
        $data->group = $group;

        if ($postbulk && $objective) {
            $data->groupcode = $postbulk->groupcode;
            $data->template = $postbulk->template;
            $data->objective = $objective;
            $data->group = $group;
            $data->sendmail = true;
        }

        $buildform = new format_bulkcertification_build_form(null, ['data' => $data]);

        $delimiters = ['tab' => '\t', 'comma' => ',', 'semicolon' => ';'];
        $localbuildform = new format_bulkcertification_localbuild_form(null, ['data' => $data, 'delimiters' => $delimiters]);

        $postbuild = $buildform->get_data();
        $localpostbuild = $localbuildform->get_data();
        if ($postbuild || $localpostbuild) {

            $customfields = $DB->get_records('user_info_field');
            $customparams = new stdClass();

            if ($localpostbuild) {
                $objective = $DB->get_record('bulkcertification_objectives', ['code' => $localpostbuild->groupcode]);

                $group = new stdClass();

                $group->objective = $objective;
                $group->objective->group      = $localpostbuild->groupcode;
                $group->objective->enddate    = $localpostbuild->objectivedate;

                $group->users = [];

                $userslist = explode("\n", $localpostbuild->userslist);

                $fields = ['username', 'firstname', 'lastname', 'email'];
                $otherfields = ['phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang', 'imagealt',
                                'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename'];
                $columns = [];
                $delimiter = $localpostbuild->delimiter == 'tab' ? "\t" : $delimiters[$localpostbuild->delimiter];

                foreach ($userslist as $k => $one) {

                    if (empty($one)) {
                        continue;
                    }

                    $rowfields = explode($delimiter, $one);

                    // First row is used to fields names.
                    if ($k == 0) {

                        if (count($rowfields) < count($fields)) {
                            echo $OUTPUT->notification(
                                get_string('badcolumnslength', 'format_bulkcertification', count($fields)),
                                'notifyproblem');
                                return;
                        }

                        // Required fields.
                        foreach ($rowfields as $m => $field) {
                            $field = strtolower(trim($field));
                            if (in_array($field, $fields)) {
                                $columns[$field] = $m;
                            }
                        }

                        if (count($columns) < count($fields)) {
                            echo $OUTPUT->notification(
                                get_string('badcolumns', 'format_bulkcertification'), 'notifyproblem');
                                return;
                        }

                        // Optional fields.
                        foreach ($rowfields as $m => $field) {
                            $field = strtolower(trim($field));
                            if (in_array($field, $otherfields)) {
                                $columns[$field] = $m;
                            }
                        }

                        // Custom profile fields.
                        if ($customfields) {
                            foreach ($rowfields as $m => $field) {
                                $field = trim($field);
                                if (strpos($field, 'profile_') !== false) {
                                    foreach ($customfields as $cfield) {
                                        if (ltrim($field, 'profile_') == $cfield->shortname) {
                                            $columns[$field] = $m;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                    } else {
                        if (count($rowfields) < count($columns)) {
                            echo $OUTPUT->notification(get_string('badfieldslength', 'format_bulkcertification', $one),
                                'notifyproblem');
                            continue;
                        }

                        $user = new stdClass();

                        $baddata = false;
                        foreach ($columns as $field => $position) {
                            if ($field == 'email' && trim($rowfields[$position]) == '') {
                                $user->email = null;
                            } else {
                                $user->$field = trim($rowfields[$position]);

                                // Required fields.
                                if (in_array($field, $fields) && empty($user->$field)) {
                                    $a = (object)['field' => $field, 'row' => $k];
                                    echo $OUTPUT->notification(get_string('badcolumnsrequired', 'format_bulkcertification', $a),
                                                                'notifyproblem');
                                    $baddata = true;
                                    break;
                                }
                            }
                        }

                        if ($baddata) {
                            continue;
                        }

                        $group->users[] = $user;
                    }
                }

                $template = $localpostbuild->template;
                $objectivedate = $localpostbuild->objectivedate;
                $sendmail = (property_exists($localpostbuild, 'sendmail') && $localpostbuild->sendmail);

                if (!empty($localpostbuild->customparams)) {
                    $customparamslist = explode("\n", $localpostbuild->customparams);

                    foreach ($customparamslist as $oneparam) {
                        $oneparam = trim($oneparam);

                        if (!empty($oneparam)) {
                            $customparam = explode("=", $oneparam);
                            $name = $customparam[0];
                            $value = count($customparam) > 1 ? $customparam[1] : '';
                            $customparams->$name = $value;
                        }
                    }
                }

            } else {
                $group = $this->get_external_group($postbuild->groupcode);
                $template = $postbuild->template;
                $objectivedate = $postbuild->objectivedate;
                $sendmail = (property_exists($postbuild, 'sendmail') && $postbuild->sendmail);
            }

            if ($group && !empty($group->objective->code)) {

                $objective = $DB->get_record('bulkcertification_objectives', ['code' => $group->objective->code]);

                if (!$objective) {
                    echo $OUTPUT->notification(get_string('objectives_notfound', 'format_bulkcertification'));
                    return;
                }

                $course->fullname = $objective->name;

                if (!$cm = get_coursemodule_from_id( 'simplecertificate', $template)) {
                    echo $OUTPUT->notification(get_string('module_notfound', 'format_bulkcertification'));
                    return;
                }

                if (!$certificate = $DB->get_record('simplecertificate', ['id' => $cm->instance])) {
                    echo $OUTPUT->notification(get_string('template_notfound', 'format_bulkcertification'));
                    return;
                }

                // Load the certificate object.
                $context = context_module::instance ($cm->id);
                $certificate->coursehours = $objective->hours;
                $certificate->certdate = $objectivedate;
                $certificate->customparams = $customparams;
                $simplecertificate = new format_bulkcertification_simplecertificate($context, $cm, $course);
                $simplecertificate->set_instance($certificate);

                $event = \format_bulkcertification\event\bulk_created::create(array(
                    'objectid' => $course->id,
                    'context' => $PAGE->context
                ));
                $event->trigger();

                $pbar = new progress_bar('format_bulkcertification_progress_bar', 500, true);

                $bulkissue = new stdClass();
                $bulkissue->issuingid       = $USER->id;
                $bulkissue->certificateid   = $certificate->id;
                $bulkissue->certificatename = $certificate->name;
                $bulkissue->code            = $objective->code;
                $bulkissue->groupcode       = $group->objective->group;
                $bulkissue->bulktime        = time();
                $bulkissue->customtime      = $certificate->certdate;
                $bulkissue->remotetime      = $group->objective->enddate;
                $bulkissue->localhours      = $objective->hours;
                $bulkissue->remotehours     = $group->objective->hours;
                $bulkissue->coursename      = $objective->name;
                $bulkissue->courseid        = $course->id;
                $bulkissue->customparams    = $customparams ? json_encode($customparams) : '[]';

                $bulkissue->id = $DB->insert_record('bulkcertification_bulk', $bulkissue, true);

                $barsize = count($group->users);
                $k = 1;

                foreach($group->users as $externaluser) {
                    $user = $DB->get_record('user', ['username'=> $externaluser->username]);

                    $pbar->update_full($k*100 / $barsize, $externaluser->username);
                    $k++;

                    if (!$user) {
                        $new_user = new stdClass();
                        $new_user->username = $externaluser->username;
                        $new_user->firstname = $externaluser->firstname;
                        $new_user->lastname = $externaluser->lastname;
                        $new_user->email = $externaluser->email ? $externaluser->email : $USER->email;

                        // Optional fields.
                        foreach ($otherfields as $field) {
                            if (property_exists($externaluser, $field)) {
                                $new_user->$field = $externaluser->$field;
                            }
                        }

                        $user = $this->create_user($new_user);
                    } else {
                        if ($user->deleted) {
                            $DB->set_field('user', 'deleted', 0, ['id' => $user->id]);
                        } else {
                            $DB->set_field('user', 'password', hash_internal_user_password($externaluser->username),
                                            ['id' => $user->id]);
                        }

                        $anychange = false;

                        $u = new stdClass();
                        $u->id = $user->id;

                        if (!empty($externaluser->firstname) && $user->firstname != $externaluser->firstname) {
                            $u->firstname = $externaluser->firstname;
                            $anychange = true;
                        }

                        if (!empty($externaluser->lastname) && $user->lastname != $externaluser->lastname) {
                            $u->lastname = $externaluser->lastname;
                            $anychange = true;
                        }

                        if (!empty($externaluser->email) && $user->email != $externaluser->email) {
                            $u->email = $externaluser->email;
                            $anychange = true;
                        }

                        // Optional fields.
                        foreach ($otherfields as $field) {
                            if (property_exists($externaluser, $field) && $user->$field != $externaluser->$field) {
                                $u->$field = $externaluser->$field;
                                $anychange = true;
                            }
                        }

                        $u->timemodified = time();

                        if ($anychange) {
                            $DB->update_record('user', $u);
                        }
                    }

                    if (!$user) {
                        echo $OUTPUT->notification(get_string('msg_error_not_create_user',
                                                              'format_bulkcertification',
                                                              $externaluser->username));
                        continue;
                    } else {

                        foreach ($customfields as $cfield) {
                            $fieldname = 'profile_' . $cfield->shortname;

                            if (property_exists($externaluser, $fieldname)) {
                                $datafield = $DB->get_record('user_info_data',
                                                                ['userid' => $user->id, 'fieldid' => $cfield->id]);

                                if ($datafield) {
                                    $datafield->data = $externaluser->$fieldname;
                                    $DB->update_record('user_info_data', $datafield);
                                } else {
                                    $datafield = new stdClass();
                                    $datafield->userid = $user->id;
                                    $datafield->fieldid = $cfield->id;
                                    $datafield->data = $externaluser->$fieldname;
                                    $DB->insert_record('user_info_data', $datafield);
                                }
                            }
                        }

                        $issuecert = $simplecertificate->get_issue($user);
                        $issuecert->tmpuser = $externaluser;
                        $issuecert->haschange = true;
                        if ($file = $simplecertificate->get_issue_file($issuecert)) {

                            $user->fullname = fullname($user);
                            $filename = $file->get_filename();

                            $issue = new stdClass();
                            $issue->issueid = $issuecert->id;
                            $issue->bulkid  = $bulkissue->id;

                            if ($DB->insert_record('bulkcertification_issues', $issue)) {

                                if ($sendmail) {
                                    if ($externaluser->email) {
                                        if ($this->email_message($bulkissue, $issue, $issuecert, $user, $filename)) {
                                            echo $OUTPUT->notification(get_string('certificate_ok', 'format_bulkcertification', $filename), 'notifysuccess');
                                        } else {
                                            echo $OUTPUT->notification(get_string('certificate_ok_notemail', 'format_bulkcertification', $filename), 'notifyproblem');
                                        }
                                    } else {
                                        echo $OUTPUT->notification(get_string('certificate_ok_emailempty', 'format_bulkcertification', $filename), 'notifyproblem');
                                    }
                                }
                                else {
                                    echo $OUTPUT->notification(get_string('certificate_ok', 'format_bulkcertification', $filename), 'notifysuccess');
                                }

                            } else {
                                echo $OUTPUT->notification(get_string('certificate_error_ns', 'format_bulkcertification', $user));
                            }
                        } else {
                            echo $OUTPUT->notification(get_string('certificate_error', 'format_bulkcertification', $user));
                        }
                    }
                }

            }

            $pbar->update_full(100, get_string('certify_finish', 'format_bulkcertification'));

            $url_return = new moodle_url($CFG->wwwroot . '/course/view.php', ['id' => $course->id, 'tab' => 'bulk']);
            echo $OUTPUT->container_start('buttons');
            echo $OUTPUT->single_button($url_return, get_string('back'));
            echo $OUTPUT->container_end();

        } else if ($postbulk && $objective && $data->group) {
            if ($objective->type == 'local') {
                $localbuildform->display();
            } else {
                $buildform->display();
            }
        } else {
            $bulkform->display();
        }
    }

    private function get_external_group($group) {
        global $OUTPUT;

        $config = get_config('format_bulkcertification');

        $curl = new curl();
        $curlresponse = $curl->get($config->wsuri, array('codgrupo' => $group), array('CURLOPT_USERPWD' => $config->wsuser . ":" . $config->wspassword));

        if (!$curlresponse) {
            echo $OUTPUT->notification(get_string('users_notfound', 'format_bulkcertification'));
            return null;
        }

        $response = json_decode($curlresponse);

        if (!is_object($response)) {
            echo $OUTPUT->notification(get_string('response_error', 'format_bulkcertification') . '<br /><pre>' . (string)$curlresponse . '</pre>');
            return null;
        }

        if (!is_object($response)) {
            echo $OUTPUT->notification(get_string('response_error', 'format_bulkcertification') . '<br /><pre>' . (string)$curlresponse . '</pre>');
            return null;
        }

        if (!property_exists($response, 'alumnos') || !is_array($response->alumnos) || count($response->alumnos) == 0) {
            echo $OUTPUT->notification(get_string('users_notfound', 'format_bulkcertification'));
            return null;
        }

        $res = new stdClass();

        $res->objective = new stdClass();
        $res->objective->hours      = $response->numerodehoras;
        $res->objective->code       = trim($response->codigosap);
        $res->objective->group      = $group;
        $res->objective->enddate    = strtotime(trim($response->fechafinalizaciongrupo));
        $res->objective->name       = trim($response->nombregrupo);

        $res->users = array();

        foreach($response->alumnos as $one) {
            $user = new stdClass();
            $user->username     = trim($one->documentoiden);
            $user->firstname    = trim($one->nombres);
            $user->lastname     = trim($one->apellidos);

            if (property_exists($one, 'correoelectronico')) {
                $user->email = trim($one->correoelectronico);
            } else {
                $user->email = null;
            }

            $res->users[] = $user;
        }

        return $res;
    }

    private function print_sections($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section show the header
                echo $this->section_header($thissection, $course, false, 0);
            }

            if ($thissection->uservisible) {
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
            }
        }

        echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
        echo $this->section_footer();

        echo $this->end_section_list();
    }

    private function print_tabs($course, $current) {
        global $OUTPUT;

        $tabs = array();

        $tabs[] = new tabobject("tab_bulk", new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'bulk')),
            get_string('tab_bulk', 'format_bulkcertification'), get_string('tab_bulk', 'format_bulkcertification'));

        $tabs[] = new tabobject("tab_templates", new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'templates')),
                get_string('tab_templates', 'format_bulkcertification'), get_string('tab_templates', 'format_bulkcertification'));

        $tabs[] = new tabobject("tab_certified", new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'certified')),
            get_string('tab_certified', 'format_bulkcertification'), get_string('tab_certified', 'format_bulkcertification'));

        $tabs[] = new tabobject("tab_reports", new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'reports')),
            get_string('tab_reports', 'format_bulkcertification'), get_string('tab_reports', 'format_bulkcertification'));

        if (has_capability('format/bulkcertification:manage', context_system::instance())) {
            $tabs[] = new tabobject("tab_objectives", new moodle_url('/course/view.php', array('id' => $course->id, 'tab' => 'objectives')),
            get_string('tab_objectives', 'format_bulkcertification'), get_string('tab_objectives', 'format_bulkcertification'));
        }

        echo $OUTPUT->tabtree($tabs, "tab_" . $current);
    }

    // Insert a new user in moodle data base.
    // The password field is used to indicate insert method saving ws_enrolment as value. The user need restore the password.
    private function create_user ($new_user) {
        global $CFG, $DB;

        if (!$new_user || empty($new_user->username)) {
            return null;
        }

        $new_user->password   = hash_internal_user_password($new_user->username);
        $new_user->idnumber   = $new_user->username;
        $new_user->modified   = time();
        $new_user->confirmed  = 1;
        $new_user->auth       = 'manual';
        $new_user->mnethostid = $CFG->mnet_localhost_id;
        $new_user->lang       = $CFG->lang;

        if ($id = $DB->insert_record ('user', $new_user)) {
            return $DB->get_record('user', ['id' => $id]);
        }

        return null;
    }

    /**
     * Send email to specified user with certificate information.
     *
     * @param stdClass $bulkissue
     * @param stdClass $issue
     * @param stdClass $issuecert
     * @param stdClass $user user record
     * @param string   $filename
     * @return bool
     */
    protected function email_message($bulkissue, $issue, $issuecert, $user, $filename) {
        global $CFG, $OUTPUT;

        if (!$user->email || !validate_email($user->email)) {
            echo $OUTPUT->notification(get_string('bademail', 'format_bulkcertification', $user));
            return false;
        }

        $site = get_site();

        $a = new stdClass();
        $a->firstname   = $user->firstname;
        $a->lastname    = $user->lastname;
        $a->fullname    = $user->fullname;
        $a->certificate = $filename;
        $a->course      = $issuecert->coursename;
        $a->url         = $CFG->wwwroot . '/blocks/simple_certificate/view.php?uid=' . $user->id;
        $a->username    = $user->username;
        $a->password    = $user->username;
        $a->sitename    = format_string($site->fullname);
        $a->admin       = generate_email_signoff();

        $message = '<p>' . get_string('generalmessage', 'format_bulkcertification', $a) . '</p>';

        $messagehtml = format_text($message, FORMAT_MOODLE, array('para' => false, 'newlines' => true, 'filter' => false));
        $messagetext = html_to_text($messagehtml);

        $subject = get_string('newcertificatesubject', 'format_bulkcertification', format_string($issuecert->coursename));

        $contact = core_user::get_support_user();


        return email_to_user($user, $contact, $subject, $messagetext, $messagehtml);

    }

    private function delete_certificates_group($delete, $course) {
        global $DB, $CFG, $PAGE;

        $bulk = $DB->get_record('bulkcertification_bulk', array('id' => $delete), '*', MUST_EXIST);

        require_once $CFG->dirroot . '/course/format/bulkcertification/classes/event/bulk_deleted.php';
        $event = \format_bulkcertification\event\bulk_deleted::create(array(
            'objectid' => $bulk->id,
            'context' => $PAGE->context,
        ));
        $event->add_record_snapshot('bulkcertification_bulk', $bulk);
        $event->trigger();


        $simplecertificate = new format_bulkcertification_simplecertificate(null, null, $course);

        $issues = $DB->get_records('bulkcertification_issues', array('bulkid' => $delete));

        if($issues) {
            foreach($issues as $issue) {
                $simpleissue = $DB->get_record('simplecertificate_issues', array('id' => $issue->issueid));

                if ($simpleissue) {
                    $this->delete_one_certificate($course, $bulk, $issue, $simpleissue);
                }
            }
        }

        return $DB->delete_records('bulkcertification_bulk', array('id' => $bulk->id));

    }

    private function delete_one_certificate($course, $bulk, $issue, $simpleissue) {
        global $DB, $CFG, $PAGE;

        $simplecertificate = new format_bulkcertification_simplecertificate(null, null, $course);

        if ($simplecertificate->delete_issue($simpleissue)) {
            $DB->delete_records('bulkcertification_issues', array('id' => $issue->id));

            require_once $CFG->dirroot . '/course/format/bulkcertification/classes/event/issue_deleted.php';
            $event = \format_bulkcertification\event\issue_deleted::create(array(
                'objectid' => $issue->id,
                'context' => $PAGE->context,
            ));
            $event->add_record_snapshot('bulkcertification_issues', $issue);
            $event->trigger();

            return true;
        }

        return false;

    }
}
