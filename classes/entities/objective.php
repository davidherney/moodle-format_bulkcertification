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
 * Objective entity implementation.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace format_bulkcertification\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Objective entity
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'bulkcertification_objectives',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('objectives', 'format_bulkcertification');
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
        $objectivealias = $this->get_table_alias('bulkcertification_objectives');

        $columns[] = (new column(
            'id',
            new lang_string('id', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$objectivealias.id")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'name',
            new lang_string('objective_name', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$objectivealias.name")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'code',
            new lang_string('objective_code', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$objectivealias.code")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'hours',
            new lang_string('objective_hours', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$objectivealias.hours")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER);

        $columns[] = (new column(
            'type',
            new lang_string('objective_type', 'format_bulkcertification'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$objectivealias.type")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TEXT)
            ->set_callback(static function(?string $type): string {
                // Mdlcode assume: $type ['local','remote']
                return get_string('type_' . $type, 'format_bulkcertification');
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $objectivealias = $this->get_table_alias('bulkcertification_objectives');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('objective_name', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$objectivealias.name",
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'code',
            new lang_string('objective_code', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$objectivealias.code",
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'hours',
            new lang_string('objective_hours', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$objectivealias.hours"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('objective_type', 'format_bulkcertification'),
            $this->get_entity_name(),
            "$objectivealias.type",
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                return self::get_options_for_type();
            });

        return $filters;
    }

    /**
     * List of options for the field format.
     *
     * @return array
     */
    public static function get_options_for_type(): array {
        return [
            'local' => new lang_string('type_local', 'format_bulkcertification'),
            'remote' => new lang_string('type_remote', 'format_bulkcertification'),
        ];
    }

}
