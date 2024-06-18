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
 * Settings for format_bulkcertification
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {


    $settings->add(new admin_setting_configtext('format_bulkcertification/defaultemail',
            new lang_string('defaultemail', 'format_bulkcertification'),
            new lang_string('defaultemail_help', 'format_bulkcertification'),
            '', PARAM_RAW));

    $settings->add(new admin_setting_heading('format_bulkcertification/externalinfo',
            new lang_string('externalinfotitle', 'format_bulkcertification'),
            ''));

    $settings->add(new admin_setting_configtext('format_bulkcertification/wsuri',
            new lang_string('wsuri', 'format_bulkcertification'),
            null, '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('format_bulkcertification/wsuser',
            new lang_string('wsuser', 'format_bulkcertification'),
            null, '', PARAM_RAW));

    $settings->add(new admin_setting_configpasswordunmask('format_bulkcertification/wspassword',
            new lang_string('wspassword', 'format_bulkcertification'),
            null, ''));
}
