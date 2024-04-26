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
 * Upgrade code for install
 *
 * @package   format_bulkcertification
 * @copyright 2020 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade this instance
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_format_bulkcertification_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017090508) {

        // Add a 'type' field to the 'bulkcertification_objectives' table.
        $table = new xmldb_table('bulkcertification_objectives');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, 'remote');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_plugin_savepoint(true, 2017090508, 'format', 'bulkcertification');
    }

    if ($oldversion < 2017090509) {

        // Add a 'type' field to the 'bulkcertification_bulk' table.
        $table = new xmldb_table('bulkcertification_bulk');
        $field = new xmldb_field('customparams', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_plugin_savepoint(true, 2017090509, 'format', 'bulkcertification');
    }

    return true;
}
