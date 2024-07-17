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
 * Strings for component course format.
 *
 * @package    format_bulkcertification
 * @copyright  2024 David Herney - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_bulk'] = 'Issue certificates';
$string['action_certified'] = 'Issued';
$string['action_objectives'] = 'Materials';
$string['action_templates'] = 'Templates';
$string['addobjective'] = 'Add material';
$string['addsections'] = 'Add topic';
$string['allcertificates_delete'] = 'Are you sure you want to delete the certificates associated with this issue? They cannot be recovered.';
$string['badcolumnslength'] = 'The column length is incorrect, {$a} fields are required';
$string['badcolumnsrequired'] = 'Field {$a->field} is required (row {$a->row})';
$string['bademail'] = 'The email ({$a->email}) of user {$a->fullname} is invalid, the notification email has not been sent.';
$string['badfieldslength'] = 'The field length is incorrect for row: {$a}';
$string['badusername'] = 'The username ({$a}) is invalid.';
$string['badusernames'] = 'First and last name are required for new users ({$a})';
$string['build'] = 'Build';
$string['bulk'] = 'Bulk issue';
$string['bulk_created'] = 'Bulk certificate generation';
$string['bulk_deleted'] = 'Certificate issuance deleted';
$string['bulkadded'] = '{$a}: The row has been successfully added.';
$string['bulkcertification:deleteissues'] = "Delete certificates in bulk certification format";
$string['bulkcertification:manage'] = "Manage bulk certification format";
$string['bulkcodetaken'] = '{$a}: The code already exists and must be unique.';
$string['bulkerroradding'] = '{$a}: An unknown error occurred and the row could not be stored.';
$string['bulkhoursmaxerror'] = '{$a}: Hour numbers of more than four figures are not supported.';
$string['bulkhoursnotnumber'] = '{$a}: The number of hours must be numeric.';
$string['bulklist'] = 'Bulklist';
$string['bulkobjectiveadd'] = 'Add material';
$string['bulkobjectivemode'] = 'Import mode';
$string['bulkobjectivereplace'] = 'Replace current materials';
$string['bulktime'] = 'Issue date';
$string['certificate_detail'] = 'Issue details';
$string['certificate_error'] = 'The certificate could not be generated for the user: {$a->username} - {$a->fullname}.';
$string['certificate_error_ns'] = 'The certificate could not be saved for the user: {$a->username} - {$a->fullname}.';
$string['certificate_ok'] = 'The certificate has been successfully generated: {$a}.';
$string['certificate_ok_emailempty'] = 'The certificate: {$a} has been successfully generated, but the user does not have an address so the email has not been sent.';
$string['certificate_ok_notemail'] = 'The certificate: {$a} was successfully generated, but the email could not be sent to you.';
$string['certificate_owner'] = 'certificate of "{$a}"';
$string['certificates_notfound'] = 'No certificates were found to use as a template. Create at least one to continue.';
$string['certifiedfilenamelabel'] = 'Certificate';
$string['cli_certificationnotfount'] = 'Certification info not found';
$string['cli_errors'] = 'Errors';
$string['cli_help'] = 'Execute cli actions.

Options:
-h,  --help              Print out this help
-rb, --rebulk=X          Rebuild the X bulk certification, X = bulk id
-ri, --reissue=X         Rebuild the X issue, X = bulk issue id (not simple certificate issue id)
-c,  --clone             Clone the issue when rebuild the certification (bulk or issue)
-iu, --iuser             Information about the users certification... by username

Example:
\$sudo -u www-data /usr/bin/php cli.php -iu=student1
';
$string['cli_messages'] = 'Messages';
$string['cli_paramvaluerequired'] = 'You must specify the {$a}';
$string['cli_rebuilding'] = 'Rebuilding bulk certification';
$string['cli_rebuildingissue'] = 'Rebuilding issue certificate';
$string['cli_usernotfound'] = 'User not found';
$string['codetaken'] = 'The code entered already exists and must be unique.';
$string['count_bulk'] = 'Emissions';
$string['count_by_certificate'] = 'Certificates by template';
$string['count_issues'] = 'Certificates issued';
$string['count_users'] = 'Certified users';
$string['course_multi'] = '{$a->local} (local) /<br /> {$a->remote} (remote)';
$string['course_statistics'] = 'Course Statistics';
$string['courseoptions'] = 'Material Options';
$string['currentsection'] = 'This topic';
$string['customparams'] = 'Custom parameters';
$string['customparams_help'] = 'One parameter per row, using the syntax: <em>Name=value</em>.
In the certificate, the field name must be capitalized.';
$string['defaultemail'] = 'Default email';
$string['defaultemail_help'] = 'Default email for new users when one is not specified in the user list.
Please note that this email will be used to send the access key to new users.<br>
You can use a template for the email address, including the following variables: {index}, {username}, {firstname}, {lastname}.
The variables: {firstname} and {lastname} will be cleaned and formatted to be valid as mail. Example: <em>user-{index}@mydomain.com</em><br>
You can also use the <em>creator</em> keyword to assign the email of the user who is doing the import.<br>
<b>If left empty and a user does not have the email information, their account will not be created.</b>';
$string['defaultemailerror'] = 'The default email is invalid and user {$a} requires an email to be created.';
$string['deletesection'] = 'Delete topic';
$string['delimiter'] = 'Delimiter';
$string['download_bulk'] = 'Download in zip';
$string['editsection'] = 'Edit theme';
$string['emptyobjectivesdelimiter'] = 'Empty import materials delimiter';
$string['external_notfound'] = 'The group was not found on the external system.';
$string['externalinfotitle'] = 'External system information';
$string['fieldsincorrectsize'] = '{$a}: The row does not have a correct number of fields, it has been skipped.';
$string['fieldsincorrecttype'] = '{$a}: The material type is not valid, it has been ignored. Only allowed: local, remote.';
$string['generalmessage'] = 'Hello {$a->firstname}....

The certificate <strong>{$a->certificate}</strong> has been generated on the platform <em>{$a->sitename}</em> for the course <strong>{$a->course}< /strong>.

To consult your certificates visit the following link:
{$a->url}

In most email programs, this will appear as a blue link that you can click. If it doesn\'t work, copy and paste the address into your browser\'s navigation bar.

{$a->admin}
';
$string['groupcode'] = 'Group code';
$string['hidefromothers'] = 'Hide theme';
$string['hours_multi'] = '{$a->local} hours (configured) / {$a->remote} hours (external)';
$string['id'] = 'ID';
$string['import'] = 'Import';
$string['importobjectives'] = 'Import materials';
$string['invalidobjective'] = 'The material is not valid';
$string['invalidtype'] = 'The material type is invalid';
$string['issue_deleted'] = 'Certificate deleted';
$string['issue_notfound'] = 'The indicated certificate was not found';
$string['issued'] = 'Issued';
$string['issues_notfound'] = 'No issued certificates found';
$string['issuing'] = 'Issuing';
$string['module_deleted'] = 'The module selected as a template no longer exists.';
$string['module_notfound'] = 'The module selected as a template was not found.';
$string['msg_error_not_create_user'] = 'The user {$a} does not exist on the platform and could not be created.';
$string['newcertificatesubject'] = 'A certificate has been generated for the course -{$a}-';
$string['newobjective'] = 'New material';
$string['objective'] = 'Material';
$string['objective_added'] = 'The material was created successfully.';
$string['objective_code'] = 'Code';
$string['objective_date'] = 'Group date';
$string['objective_delete'] = 'Are you sure you want to delete the material? It cannot be recovered.';
$string['objective_hours'] = 'Hours';
$string['objective_name'] = 'Name';
$string['objective_type'] = 'Objective type';
$string['objectives'] = 'Bill of materials';
$string['objectives_deleted'] = 'The material has been successfully deleted.';
$string['objectives_erroradding'] = 'The material could not be created.';
$string['objectives_errordeleting'] = 'An error occurred and the material could not be deleted.';
$string['objectiveslist'] = 'List of materials';
$string['objectiveslist_help'] = 'Four columns are required in the following order:
name, code, hours, type. The type can be: <em>local</em> or <em>remote</em>.<br>
<b>Important:</b> Do not use headers.';
$string['onecertificates_delete'] = 'Are you sure you want to delete the certificate? It cannot be recovered.';
$string['pluginname'] = 'Mass Certification Format';
$string['privacy:metadata'] = 'The bulk certification format plugin does not store any personal data.';
$string['rebuild'] = 'Rebuild';
$string['rebuild_error'] = 'Could not rebuild certificate {$a}';
$string['recordsdeleted'] = 'The current materials have been deleted.';
$string['remote_date'] = 'Date on the external system';
$string['remote_hours'] = 'Hours on external system';
$string['report_certified'] = 'Mass certification issues';
$string['report_statistics'] = 'Bulk certification statistics';
$string['requireduserfield'] = 'The field {$a->field} is required for user {$a->username}';
$string['response_error'] = 'The response obtained from the external server is not valid.';
$string['search'] = 'Search';
$string['sectionname'] = 'Topic';
$string['sendmail'] = 'Send notification email to users
(both for notification of the certificates and the key to new users).';
$string['showcertified'] = 'Show certificate';
$string['showfromothers'] = 'Show theme';
$string['site_statistics'] = 'Site statistics';
$string['statistic_label'] = 'Statistics';
$string['statistic_value'] = 'Value';
$string['template'] = 'Template';
$string['template_help'] = 'Certificate to be used as a template for generating mass certificates';
$string['template_notfound'] = 'The certificate selected as a template was not found.';
$string['type_local'] = 'Local';
$string['type_remote'] = 'External';
$string['usercreated'] = 'The user {$a->username} has been created with the email {$a->email} (ID: {$a->id}).';
$string['users'] = 'List of users';
$string['users_notfound'] = 'No users were found in the group with the specified code';
$string['userslist'] = 'User List';
$string['userslist_help'] = 'The required columns are: <em>username</em>. <br>
For new users: <em>firstname</em>, <em>lastname</em>, <em>email</em>.
Additionally, columns can be added with custom user field names, starting with the word <em>profile_</em>';
$string['wspassword'] = 'WS Password';
$string['wsuri'] = 'Web Service Uri';
$string['wsuriemptyerror'] = 'WS Uri is required for external sources.';
$string['wsuser'] = 'WS User';
