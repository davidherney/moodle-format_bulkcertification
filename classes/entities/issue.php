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
 * Issue entity implementation.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace format_bulkcertification\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Issue entity
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'bulkcertification_bulk',
            'bulkcertification_issues',
            'simplecertificate_issues',
            'user',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('issued', 'format_bulkcertification');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();

        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Add extra columns to report.
     * @return array
     * @throws \coding_exception
     */
    protected function get_all_columns(): array {
        global $CFG;

        $issuealias = $this->get_table_alias('bulkcertification_issues');
        $bulkalias = $this->get_table_alias('bulkcertification_bulk');
        $simplecertificatealias = $this->get_table_alias('simplecertificate_issues');
        $useralias = $this->get_table_alias('user');

        $columns[] = (new column(
            'id',
            new lang_string('id', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$issuealias.id")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'userfullname',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("INNER JOIN {simplecertificate_issues} {$simplecertificatealias} " .
                "ON {$simplecertificatealias}.id = {$issuealias}.issueid")
            ->add_join("INNER JOIN {user} {$useralias} " .
                "ON {$useralias}.id = {$simplecertificatealias}.userid")
            ->add_fields("$useralias.id, $useralias.firstname, $useralias.lastname")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->set_callback(static function(?int $userid, ?object $data): string {
                return \html_writer::link(new \moodle_url('/user/view.php',
                                        ['id' => $userid]),
                                        $data->firstname . ' ' . $data->lastname,
                                        ['target' => '_blank']);
            });

        $columns[] = (new column(
            'receiveddate',
            new lang_string('receiveddate', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("INNER JOIN {bulkcertification_bulk} {$bulkalias} " .
                "ON {$bulkalias}.id = {$issuealias}.bulkid")
            ->add_join("INNER JOIN {simplecertificate_issues} {$simplecertificatealias} " .
                "ON {$simplecertificatealias}.id = {$issuealias}.issueid")
            ->add_fields("$simplecertificatealias.timecreated")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(false)
            ->set_callback(static function(?int $timecreated): string {
                return userdate($timecreated, get_string('strftimedatetimeshortaccurate', 'langconfig'));
            });

        $columns[] = (new column(
            'certifiedfilename',
            new lang_string('certifiedfilenamelabel', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("INNER JOIN {bulkcertification_bulk} {$bulkalias} " .
                "ON {$bulkalias}.id = {$issuealias}.bulkid")
            ->add_join("INNER JOIN {simplecertificate_issues} {$simplecertificatealias} " .
                "ON {$simplecertificatealias}.id = {$issuealias}.issueid")
            ->add_fields("$simplecertificatealias.id, $bulkalias.courseid")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(false)
            ->set_callback(static function(?int $issueid, ?object $data): string {
                global $DB;
                $simpleissue = $DB->get_record('simplecertificate_issues', ['id' => $issueid], '*', MUST_EXIST);
                $course = $DB->get_record('course', ['id' => $data->courseid], '*', MUST_EXIST);
                $simplecertificate = new \format_bulkcertification\bulksimplecertificate(null, null, $course);
                return $simplecertificate->print_issue_certificate_file($simpleissue);
            });

        $showuseridentity = explode(',', $CFG->showuseridentity);

        foreach ($showuseridentity as $field) {

            // Not use profile fields as identity fields.
            if (strpos($field, 'profile_field_') === 0) {
                continue;
            }

            $columns[] = (new column(
                'user' . $field,
                new lang_string($field),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->add_join("INNER JOIN {simplecertificate_issues} {$simplecertificatealias} " .
                    "ON {$simplecertificatealias}.id = {$issuealias}.issueid")
                ->add_join("INNER JOIN {user} {$useralias} " .
                    "ON {$useralias}.id = {$simplecertificatealias}.userid")
                ->add_fields("$useralias.$field")
                ->set_type(column::TYPE_TEXT)
                ->set_is_sortable(true);

        }

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];

        // Not define filters yet.

        return $filters;
    }

}
