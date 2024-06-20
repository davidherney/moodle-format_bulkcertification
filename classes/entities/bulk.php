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
 * Bulk entity implementation.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace format_bulkcertification\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Bulk entity
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'bulkcertification_bulk' => 'fbb'
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('bulk', 'format_bulkcertification');
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
        $bulkalias = $this->get_table_alias('bulkcertification_bulk');

        $columns[] = (new column(
            'id',
            new lang_string('id', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.id")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'certificatename',
            new lang_string('template', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.certificatename")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'code',
            new lang_string('objective_code', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.code")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'groupcode',
            new lang_string('groupcode', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.code")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'bulktime',
            new lang_string('bulktime', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.bulktime")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback(static function(?int $bulktime): string {
                return userdate($bulktime, get_string('strftimedatetimeshortaccurate', 'langconfig'));
            });

        $columns[] = (new column(
            'customtime',
            new lang_string('objective_date', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.customtime")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback(static function(?int $bulktime): string {
                return userdate($bulktime, get_string('strftimedatefullshort', 'langconfig'));
            });

        $columns[] = (new column(
            'localhours',
            new lang_string('objective_hours', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.hours")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER);

        $columns[] = (new column(
            'coursename',
            new lang_string('objective_name', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$bulkalias.coursename")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $bulkalias = $this->get_table_alias('bulkcertification_bulk');

        $filters[] = (new filter(
            text::class,
            'certificatename',
            new lang_string('template', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.certificatename",
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'code',
            new lang_string('objective_code', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.code",
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'groupcode',
            new lang_string('groupcode', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.groupcode",
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'bulktime',
            new lang_string('bulktime', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.bulktime"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'customtime',
            new lang_string('objective_date', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.customtime"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'localhours',
            new lang_string('objective_hours', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.localhours"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'coursename',
            new lang_string('objective_name', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$bulkalias.coursename",
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
