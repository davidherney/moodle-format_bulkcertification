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

namespace format_bulkcertification;

use core_reportbuilder\system_report_factory;

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Class controller
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {

    /**
     * @var array List of required user fields.
     */
    public static $requiredfields = ['username'];

    /**
     * @var array List of optional user fields.
     */
    public static $otherfields = ['firstname', 'lastname', 'email', 'phone1', 'phone2', 'institution', 'department', 'address',
                                    'city', 'country', 'lang', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
                                    'alternatename'];

    /**
     * Add a new objective.
     *
     * @param int $courseid
     * @param stdClass $data
     * @return array With two elements: logs and errors.
     */
    public static function add_objective($courseid, $data) : array {
        global $DB;

        $addlogs = [
            'logs' => [],
            'errors' => [],
        ];

        $objective = new \stdClass();
        $objective->courseid = $courseid;
        $objective->name = $data->objectivename;
        $objective->code = $data->code;
        $objective->hours = $data->hours;
        $objective->type = $data->type;

        if ($DB->insert_record('bulkcertification_objectives', $objective)) {
            $addlogs['logs'][] = get_string('objective_added', 'format_bulkcertification');
        } else {
            $addlogs['errors'][] = get_string('objectives_erroradding', 'format_bulkcertification');
        }

        return $addlogs;
    }

    /**
     * Delete an objective.
     *
     * @param int $objectiveid
     * @return array With two elements: logs and errors.
     */
    public static function delete_objective($objectiveid) : array {
        global $DB;

        $logs = [
            'logs' => [],
            'errors' => [],
        ];

        if ($DB->delete_records('bulkcertification_objectives', ['id' => $objectiveid])) {
            $logs['logs'][] = get_string('objectives_deleted', 'format_bulkcertification');
        } else {
            $logs['errors'][] = get_string('objectives_errordeleting', 'format_bulkcertification');
        }

        return $logs;
    }

    /**
     * Import objectives from a bulk data.
     *
     * @param int $courseid
     * @param stdClass $bulkdata
     * @return array With two elements: logs and errors.
     */
    public static function import_objectives($courseid, $bulkdata) : array {
        global $DB;

        $delimiters = \format_bulkcertification\forms\objectivesimport::DELIMITERS;
        $delimiter = $delimiters[$bulkdata->delimiter];
        $bulklogs = [
            'logs' => [],
            'errors' => [],
        ];

        if (empty($bulkdata->objectiveslist)) {
            throw new \moodle_exception('emptyobjectivesdelimiter', 'format_bulkcertification');
        }

        $lines = explode("\n", $bulkdata->objectiveslist);

        if ($bulkdata->mode == \format_bulkcertification::IMPORT_REPLACE) {
            $DB->delete_records('bulkcertification_objectives');
            $bulklogs['logs'][] = get_string('recordsdeleted', 'format_bulkcertification');
        }

        $k = 0;
        foreach($lines as $line) {

            $k++;
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $fields = explode($delimiter, $line);

            if (count($fields) != 4) {
                $bulklogs['errors'][] = get_string('fieldsincorrectsize', 'format_bulkcertification', $k);
            } else {
                $objective = new \stdClass();
                $objective->courseid = $courseid;
                $objective->name = substr(trim($fields[0]), 0, 255);
                $objective->code = substr(trim($fields[1]), 0, 31);
                $objective->hours = substr(trim($fields[2]), 0, 4);

                // Only local and remote are used.
                $objective->type = trim($fields[3]);
                if ($objective->type != 'remote' && $objective->type != 'local') {
                    $bulklogs['errors'][] = get_string('fieldsincorrecttype', 'format_bulkcertification', $k);
                    continue;
                }

                if (!is_numeric($objective->hours)) {
                    $bulklogs['errors'][] = get_string('bulkhoursnotnumber', 'format_bulkcertification', $k);
                } else if (strlen($objective->hours) > 4) {
                    $bulklogs['errors'][] = get_string('bulkhoursmaxerror', 'format_bulkcertification', $k);
                } else {

                    // Check for duplicate course-code.
                    $params = ['courseid' => $courseid, 'code' => $objective->code];
                    if ($DB->get_record('bulkcertification_objectives', $params, '*', IGNORE_MULTIPLE)) {
                        $bulklogs['errors'][] = get_string('bulkcodetaken', 'format_bulkcertification', $k);
                    } else {

                        if (!$DB->insert_record('bulkcertification_objectives', $objective)) {
                            $bulklogs['error'][] = get_string('bulkerroradding', 'format_bulkcertification', $k);
                        } else {
                            $bulklogs['logs'][] = get_string('bulkadded', 'format_bulkcertification', $k);
                        }
                    }
                }
            }
        }

        return $bulklogs;
    }

    /**
     * Get the group information.
     *
     * @param object $objective
     * @return object|null Group information.
     */
    public static function get_group($objective) : ?object {

        $group = new \stdClass();
        $group->name = $objective->name;
        $group->code = $objective->code;
        $group->groupcode = $objective->code;
        $group->hours = $objective->hours;
        $group->enddate = 0;
        $group->users = [];

        if ($objective->type == 'remote') {

            $config = get_config('format_bulkcertification');
            if (empty($config->wsuri)) {
                throw new \moodle_exception('wsuriemptyerror', 'format_bulkcertification');
            }

            $curloptions = [];
            if (!empty($config->wsuser) && !empty($config->wspassword)) {
                $curloptions['CURLOPT_USERPWD'] = $config->wsuser . ":" . $config->wspassword;
            }

            $curl = new \curl();
            $curlresponse = $curl->get($config->wsuri, ['code' => $objective->code], $curloptions);

            if (!$curlresponse) {
                throw new \moodle_exception('external_notfound', 'format_bulkcertification');
            }

            $response = json_decode($curlresponse);

            if (!is_object($response)) {
                debugging('<pre>' . (string)$curlresponse . '</pre>');
                throw new \moodle_exception('response_error', 'format_bulkcertification');
            }

            if (!property_exists($response, 'users') || !is_array($response->users) || count($response->users) == 0) {
                throw new \moodle_exception('users_notfound', 'format_bulkcertification');
            }

            if (property_exists($response, 'name')) {
                $group->name = trim($response->name);
            }

            if (property_exists($response, 'groupcode')) {
                $group->groupcode = trim($response->groupcode);
            }

            if (property_exists($response, 'hours')) {
                $group->hours = (int)$response->hours;
            }

            if (property_exists($response, 'enddate')) {
                $enddate = trim($response->enddate);

                if (is_numeric($enddate)) {
                    $group->enddate = $enddate;
                } else {
                    $group->enddate = strtotime($enddate);
                }
            }

            // Load the users information.
            foreach ($response->users as $one) {

                $user = new \stdClass();

                // Check if the required fields are present.
                foreach (self::$requiredfields as $field) {
                    if (!property_exists($one, $field) || empty(trim($one->$field))) {
                        continue 2;
                    }

                    $user->$field = trim($one->$field);
                }

                // Load the optional fields data.
                foreach (self::$otherfields as $field) {
                    if (property_exists($one, $field)) {

                        if ($field == 'email') {
                            $user->email = clean_param(trim($one->email), PARAM_EMAIL);
                        } else {
                            $user->$field = trim($one->$field);
                        }

                    }
                }

                $group->users[] = $user;
            }
        }

        return $group;
    }

    /**
     * Read the local users from text submited into form data.
     *
     * @param object $formdata
     * @return array With three elements: users, logs and errors.
     */
    public static function read_local_users($formdata) {
        global $DB;

        $delimiters = \format_bulkcertification\forms\localbuild::DELIMITERS;
        $delimiter = $delimiters[$formdata->delimiter];
        $logs = [
            'logs' => [],
            'errors' => [],
            'users' => [],
        ];

        $userslist = explode("\n", $formdata->userslist);

        $requiredfields = self::$requiredfields;
        $otherfields = self::$otherfields;
        $columns = [];
        $delimiter = \format_bulkcertification\forms\localbuild::DELIMITERS[$formdata->delimiter];

        // First row is used to fields names.
        $headers = trim(array_shift($userslist));
        $headers = explode($delimiter, $headers);
        $headers = array_map('trim', $headers);

        // All fields are chenged to lowercase.
        $headers = array_map('strtolower', $headers);

        // Required fields.
        foreach ($headers as $m => $field) {
            if (in_array($field, $requiredfields)) {
                $columns[$field] = $m;
            }
        }

        if (count($columns) < count($requiredfields)) {
            $logs['errors'][] = get_string('badcolumnslength', 'format_bulkcertification', count($requiredfields));
            return $logs;
        }

        // Optional fields.
        foreach ($headers as $m => $field) {
            if (in_array($field, $otherfields)) {
                $columns[$field] = $m;
            }
        }

        // Custom profile fields.
        $customfields = $DB->get_records('user_info_field');
        if ($customfields) {
            foreach ($headers as $m => $field) {
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

        foreach ($userslist as $k => $one) {

            // Because the first row is used to fields names and list start in position 0.
            $k = $k + 2;

            // Clean the line. Not use empty lines.
            $one = trim($one);
            if (empty($one)) {
                continue;
            }

            $rowfields = explode($delimiter, $one);
            $rowfields = array_map('trim', $rowfields);

            if (count($rowfields) < count($columns)) {
                $logs['errors'][] = get_string('badfieldslength', 'format_bulkcertification', $k);
                continue;
            }

            $user = new \stdClass();

            $baddata = false;
            foreach ($columns as $field => $position) {
                if ($field == 'email') {
                    $user->email = empty($rowfields[$position]) ? null : clean_param($rowfields[$position], PARAM_EMAIL);
                } else {
                    $user->$field = $rowfields[$position];

                    // Required fields.
                    if (in_array($field, $requiredfields) && empty($user->$field)) {
                        $a = (object)['field' => $field, 'row' => $k];
                        $logs['errors'][] = get_string('badcolumnsrequired', 'format_bulkcertification', $a);
                        $baddata = true;
                        break;
                    }
                }
            }

            if ($baddata) {
                continue;
            }

            $logs['users'][] = $user;
        }

        return $logs;
    }

    /**
     * Read and process the remote users from data submited by the remoted system.
     *
     * @param array $userslist
     * @return array With three elements: users, logs and errors.
     */
    public static function read_remote_users($userslist) {

        $logs = [
            'logs' => [],
            'errors' => [],
            'users' => [],
        ];

        $requiredfields = self::$requiredfields;
        $otherfields = self::$otherfields;
        $columns = array_merge($requiredfields, $otherfields);

        foreach ($userslist as $k => $one) {

            // Not use empty objects or other data representation.
            if (empty($one) || !is_object($one)) {
                continue;
            }

            $user = new \stdClass();

            $baddata = false;

            foreach ($columns as $field) {

                // Required fields.
                if (in_array($field, $requiredfields) && (!property_exists($one, $field) || empty($one->$field))) {
                    $a = (object)['field' => $field, 'row' => $k];
                    $logs['errors'][] = get_string('badcolumnsrequired', 'format_bulkcertification', $a);
                    $baddata = true;
                    break;
                }

                if (!property_exists($one, $field)) {
                    continue;
                }

                if ($field == 'email') {
                    $user->email = (!property_exists($one, 'email') || empty($one->email)) ? null :
                                                                                            clean_param($one->email, PARAM_EMAIL);
                } else {
                    $user->$field = $one->$field;
                }
            }

            if ($baddata) {
                continue;
            }

            $logs['users'][] = $user;
        }

        return $logs;
    }

    /**
     * Process the custom parameters used to print certificates.
     *
     * @param string $customparams
     * @return object
     */
    public static function process_customparams($customparams) {
        $params = new \stdClass();

        if (!empty($customparams)) {
            $customparamslist = explode("\n", $customparams);

            foreach ($customparamslist as $oneparam) {
                $oneparam = trim($oneparam);

                if (!empty($oneparam)) {
                    $customparam = explode("=", $oneparam);
                    $name = $customparam[0];
                    $value = count($customparam) > 1 ? $customparam[1] : '';
                    $params->$name = clean_param($value, PARAM_TEXT);
                }
            }
        }

        return $params;
    }

    /**
     * Get the users in local BD and create a list of users if not exist.
     *
     * @param array $externalusers
     * @param bool $sendmail Send mail with certificate and to new users with the password.
     * @return array With three elements: users, logs and errors.
     */
    public static function real_users($externalusers, $sendmail = false) {
        global $DB, $USER;

        $logs = [
            'logs' => [],
            'errors' => [],
            'users' => [],
        ];

        $realusers = [];
        $defaultemail = get_config('format_bulkcertification', 'defaultemail');

        // Get the current max user id.
        $maxid = $DB->get_field_sql('SELECT MAX(id) FROM {user}');

        foreach ($externalusers as $externaluser) {
            $username = clean_param($externaluser->username, PARAM_USERNAME);

            if (empty($username)) {
                $logs['errors'][] = get_string('badusername', 'format_bulkcertification', $externaluser->username);
                continue;
            }

            $user = $DB->get_record('user', ['username'=> $username]);

            if (!$user) {
                $newuser = new \stdClass();
                $newuser->username = $username;

                // For new users, the firstname and lastname are always required.
                if (empty($externaluser->firstname) || empty($externaluser->lastname)) {
                    $logs['errors'][] = get_string('badusernames', 'format_bulkcertification', $username);
                    continue;
                }

                foreach (self::$requiredfields as $field) {
                    if (empty($externaluser->$field)) {
                        $a = (object)['field' => $field, 'username' => $username];
                        $logs['errors'][] = get_string('requireduserfield', 'format_bulkcertification', $a);
                        continue 2;
                    }
                }

                $newuser->email = '';
                if (property_exists($externaluser, 'email')) {
                    $externaluser->email = trim($externaluser->email);
                    if (!empty($externaluser->email)) {
                        $newuser->email = $externaluser->email;
                    }
                }

                // If the email is empty, use the email according to the configuration.
                if (empty($newuser->email)) {
                    if ($defaultemail == 'creator') {
                        // The "creator" is a special value that is replaced by the current user email.
                        $newuser->email = $USER->email;
                    } else if (!empty($defaultemail)) {
                        $index = $maxid + 1;
                        $tmpdefaultemail = str_replace('{index}', $index, $defaultemail);
                        $tmpdefaultemail = str_replace('{username}', $username, $tmpdefaultemail);
                        $tmpdefaultemail = str_replace('{firstname}', $externaluser->firstname, $tmpdefaultemail);
                        $tmpdefaultemail = str_replace('{lastname}', $externaluser->lastname, $tmpdefaultemail);
                        $newuser->email = $tmpdefaultemail;

                    } else {
                        $a = (object)['field' => 'email', 'username' => $username];
                        $logs['errors'][] = get_string('requireduserfield', 'format_bulkcertification', $a);
                        continue;
                    }
                }

                $newuser->email = strtolower($newuser->email);
                $newuser->email = filter_var($newuser->email, FILTER_SANITIZE_EMAIL);
                $newuser->email = clean_param($newuser->email, PARAM_EMAIL);

                // If the email is empty the default template has errors.
                if (empty($newuser->email)) {
                    $logs['errors'][] = get_string('defaultemailerror', 'format_bulkcertification', $username);
                    continue;
                }

                // Optional fields.
                foreach (self::$otherfields as $field) {

                    // Not overwrite the pre-existing fields.
                    if (property_exists($newuser, $field)) {
                        continue;
                    }

                    if (property_exists($externaluser, $field)) {
                        $newuser->$field = $externaluser->$field;
                    }
                }

                $user = self::create_user($newuser, $sendmail);

                if ($user) {
                    $a = (object)['username' => $newuser->username, 'email' => $newuser->email, 'id' => $user->id];
                    $logs['logs'][] = get_string('usercreated', 'format_bulkcertification', $a);
                    $maxid = $user->id;
                }
            } else {
                if ($user->deleted) {
                    $DB->set_field('user', 'deleted', 0, ['id' => $user->id]);
                }

                $anychange = false;

                $u = new \stdClass();
                $u->id = $user->id;

                $fields = array_merge(self::$requiredfields, self::$otherfields);
                foreach ($fields as $field) {

                    // Only update if the local field is empty.
                    if (empty($user->$field)) {
                        if (property_exists($externaluser, $field) && $user->$field != $externaluser->$field) {
                            $u->$field = $externaluser->$field;
                            $anychange = true;
                        }
                    }
                }

                if ($anychange) {
                    user_update_user($u, false);
                }
            }

            if (!$user) {
                // The user can't be created.
                $logs['errors'][] = get_string('msg_error_not_create_user', 'format_bulkcertification', $externaluser->username);
                continue;
            } else {

                // Save custom fields.
                foreach ($externaluser as $key => $value) {
                    if (strpos($key, 'profile_') === 0) {
                        $user->$key = $value;
                    }
                }

                profile_save_data($user);
                $realusers[$user->username] = $user;
            }

        }

        $logs['users'] = $realusers;
        return $logs;
    }

    /**
     * Insert a new user in moodle data base.
     * The password field is used to indicate insert method saving bulkcertificattion as value. The user need restore the password.
     *
     * @param stdClass $newuser
     * @param bool $sendmail Send mail to new users with the password.
     * @return object|null The new user or null if the user is not created.
     */
    private static function create_user($newuser, $sendmail = false) {
        global $CFG, $DB;

        // Check required moodle fields to new users.
        if (!$newuser || empty($newuser->username) || empty($newuser->email)
                || empty($newuser->firstname) || empty($newuser->lastname)) {
            return null;
        }

        $newuser->password = 'bulkcertification';
        $newuser->modified = time();
        $newuser->confirmed = 1;
        $newuser->auth = 'manual';
        $newuser->mnethostid = $CFG->mnet_localhost_id;

        if (!property_exists($newuser, 'lang') || empty($newuser->lang)) {
            $newuser->lang = $CFG->lang;
        }

        $id = user_create_user($newuser, false);

        if ($id) {
            $user = $DB->get_record('user', ['id' => $id]);

            if ($sendmail) {
                setnew_password_and_mail($user);
            }

            return $user;
        }

        return null;
    }

    /**
     * Delete a certificate.
     *
     * @param \stdClass $course
     * @param \stdClass $issue
     * @param \stdClass $simpleissue
     * @return bool
     */
    private static function delete_one_certificate($course, $issue, $simpleissue): bool {
        global $DB, $PAGE;

        $simplecertificate = new \format_bulkcertification\bulksimplecertificate(null, null, $course);

        if ($simplecertificate->delete_issue($simpleissue)) {
            $DB->delete_records('bulkcertification_issues', ['id' => $issue->id]);

            $event = \format_bulkcertification\event\issue_deleted::create([
                'objectid' => $issue->id,
                'context' => $PAGE->context,
            ]);
            $event->add_record_snapshot('bulkcertification_issues', $issue);
            $event->trigger();

            return true;
        }

        return false;

    }

    /**
     * Delete a group of certificates.
     *
     * @param int $delete The id of the bulk to delete.
     * @param \stdClass $course
     * @return bool
     */
    private static function delete_certificates_group($delete, $course) {
        global $DB, $PAGE;

        $bulk = $DB->get_record('bulkcertification_bulk', ['id' => $delete], '*', MUST_EXIST);

        $event = \format_bulkcertification\event\bulk_deleted::create([
            'objectid' => $bulk->id,
            'context' => $PAGE->context,
        ]);
        $event->add_record_snapshot('bulkcertification_bulk', $bulk);
        $event->trigger();

        $issues = $DB->get_records('bulkcertification_issues', ['bulkid' => $delete]);

        $alldeleted = true;
        if($issues) {
            foreach($issues as $issue) {
                $simpleissue = $DB->get_record('simplecertificate_issues', ['id' => $issue->issueid]);

                if ($simpleissue) {
                    $res = self::delete_one_certificate($course, $issue, $simpleissue);
                    if (!$res) {
                        $alldeleted = false;
                    }
                }
            }
        }

        if ($alldeleted) {
            // If not error deleting the issues, delete the bulk.
            return $DB->delete_records('bulkcertification_bulk', ['id' => $bulk->id]);
        } else {
            return false;
        }

    }

    /**
     * Send email to specified user with certificate information.
     *
     * @param \stdClass $issuecert
     * @param \stdClass $user user record
     * @param string   $filename
     * @return bool
     */
    public static function email_message($issuecert, $user, $filename) {
        global $CFG;

        if (!$user->email || !validate_email($user->email)) {
            debugging(get_string('bademail', 'format_bulkcertification', $user));
            return false;
        }

        $site = get_site();

        $a = new \stdClass();
        $a->firstname   = $user->firstname;
        $a->lastname    = $user->lastname;
        $a->fullname    = $user->fullname;
        $a->certificate = $filename;
        $a->course      = $issuecert->coursename;
        $a->url         = $CFG->wwwroot . '/mod/simplecertificate/certificates.php';
        $a->username    = $user->username;
        $a->password    = $user->username;
        $a->sitename    = format_string($site->fullname);
        $a->admin       = generate_email_signoff();

        $message = '<p>' . get_string('generalmessage', 'format_bulkcertification', $a) . '</p>';

        $messagehtml = format_text($message, FORMAT_MOODLE, ['para' => false, 'newlines' => true, 'filter' => false]);
        $messagetext = html_to_text($messagehtml);

        $subject = get_string('newcertificatesubject', 'format_bulkcertification', format_string($issuecert->coursename));

        $contact = \core_user::get_support_user();


        return email_to_user($user, $contact, $subject, $messagetext, $messagehtml);

    }

    /**
     * Action "certified" implementation.
     *
     * @param \stdClass $data
     * @param \context_course $coursecontext
     * @param \stdClass $course
     * @param string $operation
     * @return void
     */
    public static function action_certified($data, $coursecontext, $course, $operation = null) {
        global $DB, $OUTPUT;

        $data->activecertified = true;
        $data->title = get_string('bulklist', 'format_bulkcertification');

        $delete       = optional_param('delete', 0, PARAM_INT);
        $confirm      = optional_param('confirm', '', PARAM_ALPHANUM); //md5 confirmation hash

        $userformatdatetime = get_string('strftimedatetimeshort');
        $candelete = has_capability('format/bulkcertification:deleteissues', $coursecontext);

        // Delete a group of certificates, after confirmation
        if ($candelete && $delete && confirm_sesskey()) {
            $bulk = $DB->get_record('bulkcertification_bulk', ['id' => $delete], '*', MUST_EXIST);

            if ($confirm != md5($delete)) {
                $returnurl = new \moodle_url('/course/view.php', [
                                                                    'id' => $course->id,
                                                                    'action' => \format_bulkcertification::ACTION_CERTIFIED,
                                                                ]);
                $data->title = get_string('allcertificates_delete', 'format_bulkcertification');
                $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
                $bulktime = userdate($bulk->bulktime, $userformatdatetime);
                $infodeletetext = "'{$bulk->certificatename} - {$bulk->code} - {$bulktime}'";
                $data->certifiedcontent = $OUTPUT->confirm(
                                        get_string('deletecheck', '', $infodeletetext),
                                            new \moodle_url($returnurl, $optionsyes),
                                        $returnurl);
                return;
            }
            else if (data_submitted()) {
                // Confirmed, delete the bulk.
                \format_bulkcertification\controller::delete_certificates_group($delete, $course);
            }
        }

        // List the bulk certified.
        $report = system_report_factory::create(\format_bulkcertification\systemreports\certified::class,
                                                    $coursecontext, '', '', 0, ['courseid' => $course->id]);

        $data->certifiedcontent = $report->output();

    }

    /**
     * Action "certified" implementation and action "detail".
     *
     * @param \stdClass $data
     * @param \context_course $coursecontext
     * @param \stdClass $course
     * @param string $operation
     * @return void
     */
    public static function action_certified_detail($data, $coursecontext, $course, $operation = null) {
        global $DB, $OUTPUT;

        $data->activecertified = true;
        $data->title = get_string('certificate_detail', 'format_bulkcertification');

        $bulkid = required_param('bulkid', PARAM_INT);
        $delete = optional_param('delete', 0, PARAM_INT);
        $confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Md5 confirmation hash.

        $bulk = $DB->get_record('bulkcertification_bulk', ['id' => $bulkid], '*', MUST_EXIST);

        $candelete = has_capability('format/bulkcertification:deleteissues', $coursecontext);

        // Delete a bulk certification, after confirmation.
        if ($candelete && $delete && confirm_sesskey()) {
            $issue          = $DB->get_record('bulkcertification_issues', ['id' => $delete], '*', MUST_EXIST);
            $simpleissue    = $DB->get_record('simplecertificate_issues', ['id' => $issue->issueid], '*', MUST_EXIST);
            $user           = $DB->get_record('user', ['id' => $simpleissue->userid], '*', MUST_EXIST);
            $fullname = fullname($user);

            if ($confirm != md5($delete)) {
                $data->title = get_string('onecertificates_delete', 'format_bulkcertification');
                $returnurl = new \moodle_url('/course/view.php', [
                                                                    'id' => $course->id,
                                                                    'action' => \format_bulkcertification::ACTION_CERTIFIED,
                                                                    'op' => \format_bulkcertification::OP_DETAILS,
                                                                    'bulkid' => $bulkid,
                                                                ]);
                $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
                $data->certifiedcontent = $OUTPUT->confirm(
                                        get_string('deletecheck', '',
                                                    get_string('certificate_owner', 'format_bulkcertification', $fullname)),
                                        new \moodle_url($returnurl, $optionsyes),
                                        $returnurl);
                return;
            }
            else if (data_submitted()) {
                // Confirmed, delete the issue.
                \format_bulkcertification\controller::delete_one_certificate($course, $issue, $simpleissue);
            }
        }

        $userformatdate = get_string('strftimedaydate');
        $userformatdatetime = get_string('strftimedaydatetime');

        $issuing = $DB->get_record('user', ['id' => $bulk->issuingid]);
        $issuingnames = fullname($issuing);

        $a = new \stdClass();
        $a->local = $bulk->localhours;
        $a->remote = $bulk->remotehours;
        $hoursformated = get_string('hours_multi', 'format_bulkcertification', $a);

        $cm = get_coursemodule_from_instance( 'simplecertificate', $bulk->certificateid);
        if (!$cm) {
            $templateurl = '';
        } else {
            $templateurl = new \moodle_url('/mod/simplecertificate/view.php?id=' . $cm->id);
        }

        $data->bulk = $bulk;
        $data->bulk->bulktimeformated = userdate($bulk->bulktime, $userformatdatetime);
        $data->bulk->issuingnames = $issuingnames;
        $data->bulk->customtimeformated = userdate($bulk->customtime, $userformatdate);
        $data->bulk->remotetimeformated = $bulk->remotetime > 0 ? userdate($bulk->remotetime, $userformatdate) : '';
        $data->bulk->hoursformated = $hoursformated;
        $data->bulk->templateurl = $templateurl;

        $report = system_report_factory::create(\format_bulkcertification\systemreports\issues::class,
                        $coursecontext, '', '', 0, ['bulkid' => $bulk->id, 'courseid' => $course->id]);

        $data->certifiedcontent = $report->output();
    }

    /**
     * Action "objectives" implementation.
     *
     * @param \stdClass $data
     * @param \context_course $coursecontext
     * @param \stdClass $course
     * @param string $operation
     * @return void
     */
    public static function action_objectives($data, $coursecontext, $course, $operation = null) {
        global $DB, $OUTPUT;

        $delete = optional_param('delete', 0, PARAM_INT);
        $confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Md5 confirmation hash.
        $data->activeobjectives = true;
        $data->title = get_string('objectives', 'format_bulkcertification');

        // Delete a objective, after confirmation.
        if ($delete && confirm_sesskey()) {
            $objective = $DB->get_record('bulkcertification_objectives', ['id' => $delete], '*', MUST_EXIST);
            $data->title = get_string('objective_delete', 'format_bulkcertification');

            if ($confirm != md5($delete)) {
                $returnurl = new \moodle_url('/course/view.php', [
                                                                    'id' => $course->id,
                                                                    'action' => \format_bulkcertification::ACTION_OBJECTIVES,
                                                                ]);
                $optionsyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
                $data->formcontent = $OUTPUT->confirm(
                                        get_string('deletecheck', '', "'{$objective->name} - {$objective->code}'"),
                                        new \moodle_url($returnurl, $optionsyes),
                                        $returnurl);
                $data->showform = true;
            }
            else if (data_submitted()) {
                // Confirmed, delete the objective.
                $deletelogs = \format_bulkcertification\controller::delete_objective($delete);
                $data->pagemessages = array_merge($data->pagemessages, $deletelogs['logs']);
                $data->pageerrors = array_merge($data->pageerrors, $deletelogs['errors']);
            }
        } else if ($operation) {
            if ($operation == \format_bulkcertification::OP_ADD) {
                $data->title = get_string('newobjective', 'format_bulkcertification');

                $localdata = (object)[
                    'id' => $course->id,
                ];
                $form = new \format_bulkcertification\forms\objective(null, ['data' => $localdata]);

                if ($formdata = $form->get_data()) {
                    $addlogs = \format_bulkcertification\controller::add_objective($course->id, $formdata);
                    $data->pagemessages = array_merge($data->pagemessages, $addlogs['logs']);
                    $data->pageerrors = array_merge($data->pageerrors, $addlogs['errors']);

                    // If error messages are present, the form is displayed again.
                    if (count($addlogs['errors']) > 0) {
                        $data->formcontent = $form->render();
                        $data->showform = true;
                    }

                } else {
                    $data->formcontent = $form->render();
                    $data->showform = true;
                }

            } else if ($operation == \format_bulkcertification::OP_IMPORT) {
                $data->title = get_string('importobjectives', 'format_bulkcertification');

                $localdata = (object)[
                    'id' => $course->id,
                ];
                $form = new \format_bulkcertification\forms\objectivesimport(null, ['data' => $localdata]);

                if ($formdata = $form->get_data()) {
                    $bulklogs = \format_bulkcertification\controller::import_objectives($course->id, $formdata);
                    $data->pagemessages = array_merge($data->pagemessages, $bulklogs['logs']);
                    $data->pageerrors = array_merge($data->pageerrors, $bulklogs['errors']);

                    if (count($bulklogs['errors']) > 0) {
                        $data->formcontent = $form->render();
                        $data->showform = true;
                    }
                } else {
                    $data->formcontent = $form->render();
                    $data->showform = true;
                }

            }

        }

        if (!property_exists($data, 'showform') || !$data->showform) {

            // The objectives are listed by default.
            $report = system_report_factory::create(\format_bulkcertification\systemreports\objectives::class,
                                                        $coursecontext, '', '', 0, ['courseid' => $course->id]);
            $data->objectivescontent = $report->output();
        }

    }

    /**
     * Action "bulk" implementation.
     *
     * @param \stdClass $data
     * @param \context_course $coursecontext
     * @param \stdClass $course
     * @param string $operation
     *
     * @return void
     */
    public static function action_bulk($data, $coursecontext, $course, $operation = null) {
        global $DB, $OUTPUT, $CFG, $USER;

        $data->activebulk = true;

        $localdata = (object)[
            'id' => $course->id,
        ];

        // The cancel button is pressed, so the form is displayed again.
        $cancel = optional_param('cancel', '', PARAM_TEXT);

        $form = new \format_bulkcertification\forms\bulk(null, ['data' => $localdata]);

        if ($cancel || !$operation || $operation == \format_bulkcertification::OP_SEARCH) {

            if (!$cancel && $postbulk = $form->get_data()) {
                $objective = $DB->get_record('bulkcertification_objectives', ['id' => $postbulk->code]);

                if (!$objective) {
                    throw new \moodle_exception('invalidobjective', 'format_bulkcertification');
                }

                try {
                    $group = \format_bulkcertification\controller::get_group($objective);
                } catch (\moodle_exception $e) {
                    $data->pageerrors[] = $e->getMessage();
                    $data->formcontent = $form->render();
                    return;
                }

                $localdata->templatename = $DB->get_field('simplecertificate', 'name', ['id' => $postbulk->template]);
                $localdata->template = $postbulk->template;
                $localdata->code = $postbulk->code;
                $localdata->objective = $objective;
                $localdata->group = $group;
                $localdata->sendmail = false;

                if ($objective->type == 'local') {
                    $buildform = new \format_bulkcertification\forms\localbuild(null, ['data' => $localdata]);
                } else {
                    $buildform = new \format_bulkcertification\forms\remotebuild(null, ['data' => $localdata]);
                }
                $data->formcontent = $buildform->render();

            } else {
                $data->formcontent = $form->render();
            }
        } else if ($operation == \format_bulkcertification::OP_SAVE) {
            $code = optional_param('code', 0, PARAM_INT);

            $objective = $DB->get_record('bulkcertification_objectives', ['id' => $code], '*', MUST_EXIST);

            if ($objective->type == 'local') {
                $buildform = new \format_bulkcertification\forms\localbuild(null, ['data' => $localdata]);
            } else {
                $buildform = new \format_bulkcertification\forms\remotebuild(null, ['data' => $localdata]);
            }

            $postbuild = $buildform->get_data();

            // Validate if the template exist.
            if (!$certificate = $DB->get_record('simplecertificate', ['id' => $postbuild->template])) {
                $data->pageerrors[] = get_string('template_notfound', 'format_bulkcertification');

                // Return to the initial form.
                $data->formcontent = $form->render();
                return;
            }

            // Users are read from the objective source.
            if ($objective->type == 'local') {
                // Group for local is only a prototype.
                $group = \format_bulkcertification\controller::get_group($objective);
                $log = \format_bulkcertification\controller::read_local_users($postbuild);
            } else {
                $groupdata = $postbuild->remotedata;
                $group = json_decode(base64_decode($groupdata));
                $log = \format_bulkcertification\controller::read_remote_users($group->users);
            }

            $data->pagemessages = array_merge($data->pagemessages, $log['logs']);
            $group->users = $log['users'];

            if (count($log['errors']) > 0) {
                // If errors are present, the initial form is displayed again.
                $data->pageerrors = array_merge($data->pageerrors, $log['errors']);
                $data->formcontent = $form->render();
                return;
            }

            $objectivedate = $postbuild->objectivedate;
            $customparams = \format_bulkcertification\controller::process_customparams($postbuild->customparams);

            $sendmail = (property_exists($postbuild, 'sendmail') && $postbuild->sendmail);

            $logrealusers = \format_bulkcertification\controller::real_users($group->users, $sendmail);
            $data->pagemessages = array_merge($data->pagemessages, $logrealusers['logs']);
            $realusers = $logrealusers['users'];

            if (count($logrealusers['errors']) > 0) {
                // If errors are present, the initial form is displayed again.
                $data->pageerrors = array_merge($data->pageerrors, $logrealusers['errors']);
                $data->formcontent = $form->render();
                return;
            }

            // Load the certificate object.
            $cm = get_coursemodule_from_instance('simplecertificate', $certificate->id, $course->id);
            $context = \context_module::instance ($cm->id);
            $certificate->coursehours = $group->hours;
            $certificate->certdate = $objectivedate;
            $certificate->customparams = $customparams;
            $course->fullname = $group->name;
            $simplecertificate = new \format_bulkcertification\bulksimplecertificate($context, $cm, $course);
            $simplecertificate->set_instance($certificate);

            $bulkissue = new \stdClass();
            $bulkissue->issuingid       = $USER->id;
            $bulkissue->certificateid   = $certificate->id;
            $bulkissue->certificatename = $certificate->name;
            $bulkissue->code            = $group->code;
            $bulkissue->groupcode       = $group->groupcode;
            $bulkissue->bulktime        = time();
            $bulkissue->customtime      = $certificate->certdate;
            $bulkissue->remotetime      = $group->enddate;
            $bulkissue->localhours      = $objective->hours;
            $bulkissue->remotehours     = $group->hours;
            $bulkissue->coursename      = $group->name;
            $bulkissue->courseid        = $course->id;
            $bulkissue->customparams    = $customparams ? json_encode($customparams) : '[]';

            $bulkissue->id = $DB->insert_record('bulkcertification_bulk', $bulkissue, true);

            $event = \format_bulkcertification\event\bulk_created::create([
                'objectid' => $course->id,
                'context' => $coursecontext,
                'other' => [
                    'bulkid' => $bulkissue->id,
                ],
            ]);
            $event->trigger();

            foreach($group->users as $externaluser) {
                $user = $realusers[$externaluser->username];

                $issuecert = $simplecertificate->get_issue($user);
                $issuecert->tmpuser = $externaluser;
                $issuecert->haschange = true;
                if ($file = $simplecertificate->get_issue_file($issuecert)) {

                    $user->fullname = fullname($user);
                    $filename = $file->get_filename();

                    $issue = new \stdClass();
                    $issue->issueid = $issuecert->id;
                    $issue->bulkid  = $bulkissue->id;

                    if ($DB->insert_record('bulkcertification_issues', $issue)) {

                        if ($sendmail) {
                            if ($user->email) {
                                if (\format_bulkcertification\controller::email_message($issuecert, $user, $filename)) {
                                    $data->pagemessages[] = get_string('certificate_ok', 'format_bulkcertification', $filename);
                                } else {
                                    $data->pageerrors[] = get_string('certificate_ok_notemail', 'format_bulkcertification',
                                                                        $filename);
                                }
                            } else {
                                $data->pageerrors[] = get_string('certificate_ok_emailempty', 'format_bulkcertification',
                                                                        $filename);
                            }
                        }
                        else {
                            $data->pagemessages[] = get_string('certificate_ok', 'format_bulkcertification', $filename);
                        }

                    } else {
                        $data->pageerrors[] = get_string('certificate_error_ns', 'format_bulkcertification', $user);
                    }
                } else {
                    $data->pageerrors[] = get_string('certificate_error', 'format_bulkcertification', $user);
                }
            }

            $urlreturn = new \moodle_url($CFG->wwwroot . '/course/view.php', ['id' => $course->id,
                                                                                'action' => 'certified',
                                                                                'op' => 'details',
                                                                                'bulkid' => $bulkissue->id,
                                                                            ]);
            $data->formcontent = $OUTPUT->container_start('buttons');
            $data->formcontent .= $OUTPUT->single_button($urlreturn, get_string('showcertified', 'format_bulkcertification'));
            $data->formcontent .= $OUTPUT->container_end();

        }

    }

    /**
     * Rebuild one or all bulk certificates.
     *
     * The params bulkorissueid and clone are used in client mode.
     *
     * @param \stdClass $data
     * @param bool $all Rebuild a bulk release
     * @param int $bulkorissueid Bulk or issue id. If $all = true then $bulkorissueid is the bulk id
     * @param bool $clone Create the issues as a new release
     * @return void
     */
    public static function certified_rebuild($data, $course, $all = false, $bulkorissueid = null, $clone = false) {
        global $DB;

        $data->activecertified = true;
        $data->title = get_string('rebuild', 'format_bulkcertification');

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
            $data->pageerrors[] = get_string('module_notfound', 'format_bulkcertification');
            return;
        }

        if (!$certificate = $DB->get_record('simplecertificate', ['id' => $cm->instance])) {
            $data->pageerrors[] = get_string('template_notfound', 'format_bulkcertification');
            return;
        }

        if (!$course || !is_object($course)) {
            $course = $DB->get_record('course', ['id' => $certificate->course], '*', MUST_EXIST);
        }

        $context = \context_module::instance($cm->id);

        foreach ($issues as $issue) {
            if($issue) {

                $simpleissue = $DB->get_record('simplecertificate_issues', ['id' => $issue->issueid]);

                // Load the certificate object.
                $certificate->coursehours = $bulk->localhours;
                $certificate->certdate = $bulk->customtime;
                $certificate->customparams = $bulk->customparams;
                $course->fullname = $bulk->coursename;
                $simplecertificate = new \format_bulkcertification\bulksimplecertificate($context, $cm, $course);
                $simplecertificate->set_instance($certificate);

                $simpleissue->haschange = 1;
                $simpleissue->timedeleted = time();

                if ($clone) {
                    unset($simpleissue->id);
                    $simpleissue->certificateid = 0;
                    $simpleissue = $simplecertificate->get_issue($simpleissue->userid, true);

                    // Change the issue by the new one in the bulk reference.
                    $updatedata = new \stdClass();
                    $updatedata->id = $issue->id;
                    $updatedata->issueid = $simpleissue->id;
                    $DB->update_record('bulkcertification_issues', $updatedata);
                }

                if ($file = $simplecertificate->save_pdf_forced($simpleissue, true)){
                    $data->pagemessages[] = get_string('certificate_ok', 'format_bulkcertification', $file->get_filename());
                } else {
                    $data->pageerrors[] = get_string('rebuild_error', 'format_bulkcertification', $simpleissue->id);
                }

            } else {
                $data->pageerrors[] = get_string('issue_notfound', 'format_bulkcertification');
            }
        }
    }
}
