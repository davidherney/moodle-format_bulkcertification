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

$string['action_bulk'] = 'Emitir certificados';
$string['action_certified'] = 'Emitidos';
$string['action_objectives'] = 'Materiales';
$string['action_templates'] = 'Plantillas';
$string['addobjective'] = 'Agregar material';
$string['addsections'] = 'Add topic';
$string['allcertificates_delete'] = '¿Seguro que desea borrar los certificados asociados a ésta emisión? No se podrán recuperar.';
$string['badcolumns'] = 'The columns names are incorrect';
$string['badcolumnslength'] = 'The length of columns is incorrect, {$a} fields are required';
$string['badcolumnsrequired'] = 'The field {$a->field} is required (row {$a->row})';
$string['bademail'] = 'El correo electrónico ({$a->email}) del usuario {$a->fullname} es inválido, no se le ha enviado el correo de notificación.';
$string['badfieldslength'] = 'Length of fields is incorrect to row: {$a}';
$string['badusernames'] = 'The firstname and lastname are required for new users ({$a})';
$string['build'] = 'Generar';
$string['bulk'] = 'Emisión masiva';
$string['bulk_created'] = 'Generación de certificados masivos';
$string['bulk_deleted'] = 'Emisión de certificados eliminada';
$string['bulkcertification:deleteissues'] = "Delete certificates into bulkcertification format";
$string['bulkcertification:manage'] = "Manage global bulkcertification format parameters";
$string['bulkcodetaken'] = '{$a}: El código ya existe y debe ser único.';
$string['bulkerroradding'] = '{$a}: Ocurrió un error desconocido y no se pudo almacenar la fila.';
$string['bulkhoursmaxerror'] = '{$a}: No se soportan números de horas de más de cuatro cifras.';
$string['bulkhoursnotnumber'] = '{$a}: El número de horas debe ser numérico.';
$string['bulklist'] = 'Listado de emisiones';
$string['bulklist_count'] = 'Listado de emisiones ({$a->count}/{$a->total})';
$string['bulkobjectiveadd'] = 'Add news';
$string['bulkobjectivemode'] = 'Importation mode';
$string['bulkobjectivereplace'] = 'Replace current objectives';
$string['bulks_notfound'] = 'No se encontraron emisiones para el curso indicado';
$string['bulktime'] = 'Fecha de emisión';
$string['certificate_detail'] = 'Detalles de la emisión';
$string['certificate_error'] = 'No se ha podido generar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['certificate_error_ns'] = 'No se ha podido guardar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['certificate_ok'] = 'Se ha generado satisfactoriamente el certificado: {$a}.';
$string['certificate_ok_emailempty'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero el usuario no tiene una dirección por lo que no se le ha enviado el correo electrónico.';
$string['certificate_ok_notemail'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero no se le ha podido enviar el correo electrónico.';
$string['certificate_owner'] = 'certificado de "{$a}"';
$string['certificates_notfound'] = 'No se encontraron certificados para utilizar como plantilla. Cree por lo menos uno para poder continuar.';
$string['certify_finish'] = 'Proceso completado';
$string['codetaken'] = 'El código ingresado ya existe y debe ser único.';
$string['count_bulk'] = 'Emisiones';
$string['count_by_certificate'] = 'Certificados por plantilla';
$string['count_issues'] = 'Certificados emitidos';
$string['count_users'] = 'Usuarios certificados';
$string['course_multi'] = '{$a->local} (se usará en el certificado) /<br /> {$a->remote} (externo)';
$string['course_statistics'] = 'Estadísticas del curso';
$string['courseoptions'] = 'Opciones del material';
$string['currentsection'] = 'This topic';
$string['customparams'] = 'Custom params';
$string['customparams_help'] = 'Un parámetro por fila, usando la sintaxis: <em>Nombre=valor</em>.
En el certificado, el nombre del campo debe estar en mayúscula.';
$string['defaultemail'] = 'Correo electrónico por defecto';
$string['defaultemail_help'] = 'Correo electrónico por defecto para los usuarios nuevos cuando no se especifique uno en el listado de usuarios.
Tenga presente que este correo se utilizará para enviar la clave de acceso a los usuarios nuevos.<br>
Puede usar una plantilla para la dirección de correo, incluyendo las siguientes variables: {index}, {username}, {firstname}, {lastname}.
Las variables: {firstname} y {lastname} se limpiarán y tendrán formato para ser válidas como correo. Ejemplo: <em>usuario-{index}@midominio.com</em><br>
También puede usar la palabra clave <em>creator</em> para asignar el correo del usuario que está realizando la importación.<br>
<b>Si se deja vacío y un usuario no tiene la información del correo, no se creará su cuenta.</b>';
$string['deletesection'] = 'Delete topic';
$string['delimiter'] = 'Delimiter';
$string['download_bulk'] = 'Descargar en zip';
$string['editsection'] = 'Edit topic';
$string['editsectionname'] = 'Edit topic name';
$string['emptyobjectivesdelimiter'] = 'Empty import objectives delimiter';
$string['exportfilename'] = 'exportado_certificaciones';
$string['external_notfound'] = 'No se encontró el grupo en el sistema externo.';
$string['externalcode'] = 'Código en sistema externo';
$string['externalinfotitle'] = 'Información del sistema externo';
$string['fieldsincorrectsize'] = '{$a}: La fila no tiene un número correcto de campos, se ha omitido.';
$string['fieldsincorrecttype'] = '{$a}: El tipo de material no es válido, se ha omitido. Sólo se permite: local, remote.';
$string['filename'] = 'Nombre del archivo';
$string['generalmessage'] = 'Hi {$a->firstname}...

The certificate <strong>{$a->certificate}</strong> has been generated on the <em>{$a->sitename}</em> platform for the course <strong>{$a->course} </strong>.

To consult your certificates visit the following link:
{$a->url}

In most email programs, this will appear as a blue link that you can click on. If it doesn\'t work, copy and paste the address into your browser\'s navigation bar.

{$a->admin}
';
$string['groupcode'] = 'Código de grupo';
$string['groupcode_help'] = 'Código asociado al grupo';
$string['hidefromothers'] = 'Hide topic';
$string['hours_multi'] = '{$a->local} horas (configuradas) / {$a->remote} horas (externo)';
$string['id'] = 'ID';
$string['import'] = 'Import';
$string['importobjectives'] = 'Import objectives';
$string['indentation'] = 'Allow indentation on course page';
$string['indentation_help'] = 'Allow teachers, and other users with the manage activities capability, to indent items on the course page.';
$string['invalidobjective'] = 'The objective is not valid';
$string['invalidtype'] = 'The objective type is not valid';
$string['issue_deleted'] = 'Certificado eliminado';
$string['issue_notfound'] = 'No se encontró el certificado indicado';
$string['issued'] = 'Issued';
$string['issues_notfound'] = 'No se encontraron certificados emitidos';
$string['issuing'] = 'Emisor';
$string['module_notfound'] = 'No se encontró el módulo seleccionado como plantilla.';
$string['msg_error_not_create_user'] = 'El usuario {$a} no existe en la plataforma y no pudo ser creado.';
$string['newcertificatesubject'] = 'A certificate for course -{$a}- has been generated for you';
$string['newobjective'] = 'Nuevo material';
$string['newsectionname'] = 'New name for topic {$a}';
$string['objective_added'] = 'El material se creó satisfactoriamente.';
$string['objective_code'] = 'Código';
$string['objective_code_help'] = 'Código del material';
$string['objective_date'] = 'Fecha del grupo';
$string['objective_delete'] = '¿Seguro que desea borrar el material? No se podrá recuperar.';
$string['objective_hours'] = 'Horas';
$string['objective_name'] = 'Nombre';
$string['objective_type'] = 'Objective type';
$string['objectives'] = 'Listado de materiales';
$string['objectives_count'] = 'Listado de materiales ({$a->count}/{$a->total})';
$string['objectives_deleted'] = 'Se ha eliminado correctamente el material.';
$string['objectives_erroradding'] = 'El material no pudo ser creado.';
$string['objectives_errordeleting'] = 'Ocurrió un error y no se pudo borrar el material.';
$string['objectives_notfound'] = 'No se encontraron materiales con el código SAP del grupo indicado.';
$string['objectiveslist'] = 'Objectives list';
$string['objectiveslist_help'] = 'Required four columns: name, code, hours, type. The type can be: local or remote.
Important: Don\'t use headers.';
$string['onecertificates_delete'] = '¿Seguro que desea borrar el certificado? No se podrá recuperar.';
$string['page-course-view-topics'] = 'Any course main page in bulk certification format';
$string['page-course-view-topics-x'] = 'Any course page in bulk certification format';
$string['pluginname'] = 'Bulk certification format';
$string['privacy:metadata'] = 'The Bulk certification format plugin does not store any personal data.';
$string['rebuild'] = 'Volver a generar';
$string['rebuild_error'] = 'No se pudo volver a generar el certificado {$a}';
$string['recordsdeleted'] = 'The current objectives have been deleted.';
$string['remote_date'] = 'Fecha en el sistema externo';
$string['remote_hours'] = 'Horas en el sistema externo';
$string['report_certified'] = 'Bulk certification issues';
$string['report_statistics'] = 'Bulk certification statistics';
$string['requireduserfield'] = 'The field {$a->field} is required for the user {$a->username}';
$string['response_error'] = 'La respuesta obtenida del servidor externo no es válida.';
$string['search'] = 'Consultar';
$string['section0name'] = 'General';
$string['sectionname'] = 'Topic';
$string['sendmail'] = 'Enviar correo de notificación a los usuarios
(tanto para la notificación de los certificados como con la clave a los usuarios nuevos).';
$string['showfromothers'] = 'Show topic';
$string['site_statistics'] = 'Estadísticas del sitio';
$string['statistic_label'] = 'Estadística';
$string['statistic_value'] = 'Valor';
$string['template'] = 'Plantilla';
$string['template_help'] = 'Certificado que se utilizará como plantilla para la generación de los certificados masivos';
$string['template_notfound'] = 'No se encontró el certificado seleccionado como plantilla.';
$string['type_local'] = 'Local';
$string['type_remote'] = 'Remote';
$string['users'] = 'Listado de usuarios';
$string['users_notfound'] = 'No se encontraron usuarios en el grupo con el código especificado';
$string['userslist'] = 'Users list';
$string['userslist_help'] = 'Required columns are: username. For new users: firstname, lastname, email. Además, se pueden agregar
columnas con nombres de campos de usuario personalizados, empezando por la palabra <em>profile_</em>';
$string['wspassword'] = 'WS password';
$string['wsuri'] = 'WS Uri';
$string['wsuriemptyerror'] = 'The WS Uri is required for external sources.';
$string['wsuser'] = 'WS User';
