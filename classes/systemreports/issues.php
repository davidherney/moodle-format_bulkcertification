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
 * System report for issues list.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_bulkcertification\systemreports;

// We need to include the bulkcertification lib file because it is not included in AJAX calls.
require_once($CFG->dirroot . '/course/format/bulkcertification/lib.php');

use format_bulkcertification\entities\issue;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\action;

/**
 * Issues list report.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney @ BambuCo - https://bambuco.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues extends system_report {

    /**
     * If the report is for management.
     * @var bool
     */
    private $tomanagment = false;

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $PAGE;

        // We need to ensure page context is always set, as required by output and string formatting.
        $PAGE->set_context($this->get_context());

        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new issue();
        $entitymainalias = $entitymain->get_table_alias('bulkcertification_issues');

        $this->set_main_table('bulkcertification_issues', $entitymainalias);
        $this->add_entity($entitymain);

        $bulkid = $this->get_parameter('bulkid', 0, 'int');

        if (empty($bulkid)) {
            throw new \Exception('Bulk ID is required');
        }

        $param = database::generate_param_name();
        $params = [
            $param => $bulkid,
        ];
        $where = [
            "$entitymainalias.bulkid = :$param",
        ];

        $this->tomanagment = has_capability('format/bulkcertification:manage', $this->get_context());

        $candelete = has_capability('format/bulkcertification:deleteissues', $this->get_context());

        $wheresql = implode(' AND ', $where);

        $this->add_base_condition_sql($wheresql, $params);

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_base_fields("{$entitymainalias}.id");

        // Add actions to the report.
        if ($this->tomanagment) {

            $this->add_action((new action(
                new \moodle_url('/course/view.php', [
                                        'id' => $bulkid,
                                        'issueid' => ':id',
                                        'action' => \format_bulkcertification::ACTION_CERTIFIED,
                                        'op' => \format_bulkcertification::OP_REBUILD,
                                        'sesskey' => sesskey(),
                                    ]
                                ),
                new \pix_icon('a/refresh', ''),
                [],
                false,
                new \lang_string('rebuild', 'format_bulkcertification')
            )));
        }

        if ($candelete) {
            $this->add_action((new action(
                new \moodle_url('/course/view.php', [
                                        'id' => $bulkid,
                                        'delete' => ':id',
                                        'action' => \format_bulkcertification::ACTION_CERTIFIED,
                                        'op' => \format_bulkcertification::OP_DETAILS,
                                        'sesskey' => sesskey(),
                                    ]
                                ),
                new \pix_icon('i/trash', ''),
                [],
                false,
                new \lang_string('delete')
            )));
        }

        // Set if report can be downloaded.
        $this->set_downloadable(true);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return true;
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    public function add_columns(): void {
        global $CFG;

        $columns = [
            'issue:userfullname',
            'issue:receiveddate',
            'issue:certifiedfilename',
        ];

        $showuseridentity = explode(',', $CFG->showuseridentity);

        foreach ($showuseridentity as $field) {
            $columns[] = "issue:user$field";
        }

        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [];

        $this->add_filters_from_entities($filters);
    }
}
