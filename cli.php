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
 * This file contains main class for the bulkcertification course format
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php'); // Cli only functions.

// Global options.
$hr = "----------------------------------------\n";

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'rebulk' => false,
        'reissue' => false,
        'clone' => false,
        'iuser' => false,
    ], [
        'h'  => 'help',
        'rb' => 'rebulk',
        'ri' => 'reissue',
        'c'  => 'clone',
        'iu' => 'iuser',
    ]
);

$any = false;
foreach ($options as $option) {
    if ($option) {
        $any = true;
        break;
    }
}

if (!$any) {
    $options['help'] = true;
}

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo get_string('cli_help', 'format_bulkcertification');
    die;
}

$data = new stdClass();
$data->pagemessages = [];
$data->pageerrors = [];

if ($options['rebulk']) {
    $id = $options['rebulk'];

    if ($id === true) {
        echo get_string('cli_paramvaluerequired', 'format_bulkcertification', 'bulk id') . "\n";
        die;
    }

    $clone = isset($options['clone']) && $options['clone'] === true;
    echo get_string('cli_rebuilding', 'format_bulkcertification') . "\n";

    \format_bulkcertification\controller::certified_rebuild($data, null, true, $id, $clone);
    print_messages();
    die;
}

if ($options['reissue']) {
    $id = $options['reissue'];

    if ($id === true) {
        echo get_string('cli_paramvaluerequired', 'format_bulkcertification', 'issue id') . "\n";
        die;
    }

    $clone = isset($options['clone']) && $options['clone'] === true;
    echo get_string('cli_rebuildingissue', 'format_bulkcertification') . "\n";

    \format_bulkcertification\controller::certified_rebuild($data, null, false, $id, $clone);
    print_messages();
    die;
}

if ($options['iuser']) {
    $username = $options['iuser'];

    if ($username === true) {
        echo get_string('cli_paramvaluerequired', 'format_bulkcertification', 'username') . "\n";
        die;
    }

    $user = $DB->get_record('user', ['username' => $username]);

    if (!$user) {
        echo get_string('cli_usernotfound', 'format_bulkcertification') . "\n";
        die;
    }

    $fullname = fullname($user);
    echo $hr;
    echo "User id: $user->id\n";
    echo "User name: $user->username\n";
    echo "User fullname: $fullname\n";
    echo "User email: $user->email\n";
    echo $hr;

    $sql = "SELECT si.id, si.certificateid, si.certificatename, si.code, si.timecreated, si.timedeleted,
                    si.pathnamehash, si.coursename, bb.certificateid AS bcertificateid, bb.certificatename AS bcertificatename,
                    bb.bulktime, bb.coursename AS bcoursename, bb.id AS bulkid, bb.courseid, bb.customparams, bi.id AS biid
                FROM {simplecertificate_issues} si
                LEFT JOIN {bulkcertification_issues} bi ON si.id = bi.issueid
                LEFT JOIN {bulkcertification_bulk} bb ON bi.bulkid = bb.id
                WHERE si.userid = :uid";

    $params = ['uid' => $user->id];

    $rs = $DB->get_recordset_sql($sql, $params);

    if (!$rs->valid()) {
        echo get_string('cli_certificationnotfount', 'format_bulkcertification') . "\n";
        $rs->close(); // Not going to iterate (but exit), close rs
        die;
    }

    $issues = [];
    $totalbulks = 0;
    foreach ($rs as $record) {
        if (!isset($issues[$record->id])) {
            $issues[$record->id] = [];
        }

        $issues[$record->id][] = $record;
        $totalbulks++;
    }
    $rs->close();

    echo "Total issues: " . count($issues) . "\n";
    echo "Total bulks: " . $totalbulks . "\n";
    echo $hr;

    foreach ($issues as $id => $records) {
        echo "Simple certificate issue id: $id\n";

        foreach ($records as $record) {

            echo "  Certificate id/Bulk certificate id: {$record->certificateid} / {$record->bcertificateid}\n";
            echo "  Certificate name/Bulk certificate name: {$record->certificatename} / {$record->bcertificatename}\n";
            echo "  Bulk issue id: $record->biid\n";
            echo "  Code: $record->code\n";
            echo "  Time created: " . userdate($record->timecreated) . "\n";
            echo "  Time deleted: " . userdate($record->timedeleted) . "\n";
            echo "  Course name/Bulk course name: {$record->coursename} / {$record->bcoursename}\n";
            echo "  Bulk time: " . userdate($record->bulktime) . "\n";
            echo "  Bulk id: $record->bulkid\n";
            echo "  Course id: $record->courseid\n";

            $d = @json_decode($record->customparams);
            echo "  Custom params: ";
            print_r($record->customparams);
            echo "\n";

            $file = $DB->get_record('files', ['pathnamehash' => $record->pathnamehash]);

            echo "  File:\n";
            echo "    Path name hash: $record->pathnamehash\n";
            if ($file) {
                echo "    Content hash: $file->contenthash\n";
                echo "    Path: {$file->filepath}{$file->filename}\n";
                echo "    Context id: $file->contextid\n";
                echo "    Component: $file->component\n";
                echo "    Item id: $file->itemid\n";
            } else {
                echo "    File not found\n";
            }
            echo $hr;
        }
    }

    die;
}

/**
 * Print messages and errors.
 */
function print_messages() {
    global $data;

    if (count($data->pagemessages) > 0) {
        echo "------------\n";
        echo "| " . get_string('cli_messages', 'format_bulkcertification') . " |\n";
        echo "------------\n";
        foreach ($data->pagemessages as $i => $message) {
            $i++;
            echo "  $i- $message\n";
        }
    }

    if (count($data->pageerrors) > 0) {
        echo "----------\n";
        echo "| " . get_string('cli_errors', 'format_bulkcertification') . " |\n";
        echo "----------\n";
        foreach ($data->pageerrors as $i => $error) {
            $i++;
            echo "  $i- $error\n";
        }
    }
}
