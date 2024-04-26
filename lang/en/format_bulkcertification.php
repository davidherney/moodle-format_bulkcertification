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
 * Strings for component 'format_bulkcertifications', language 'en'
 *
 * @package   format_bulkcertification
 * @copyright 2017 David Herney Bernal - cirano - david.bernal@bambuco.co
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['currentsection'] = 'This section';
$string['sectionname'] = 'Section';
$string['pluginname'] = 'Bulk certification format';
$string['page-course-view-topics'] = 'Any course main page in bulkcertification format';
$string['page-course-view-topics-x'] = 'Any course page in bulkcertification format';
$string['hidefromothers'] = 'Hide topic';
$string['showfromothers'] = 'Show topic';

$string['bulkcertification:manage'] = "Manage global bulkcertification format parameters";
$string['bulkcertification:deleteissues'] = "Delete certificates into bulkcertification format";

$string['wsuri'] = 'WS Uri';
$string['wsuser'] = 'WS User';
$string['wspassword'] = 'WS password';
$string['tab_bulk'] = 'Emitir certificados';
$string['tab_templates'] = 'Plantillas';
$string['tab_certified'] = 'Emitidos';
$string['tab_reports'] = 'Informes';
$string['tab_objectives'] = 'Materiales';
$string['template'] = 'Plantilla';
$string['template_help'] = 'Certificado que se utilizará como plantilla para la generación de los certificados masivos';
$string['code'] = 'Código';
$string['groupcode'] = 'Código de grupo';
$string['code_help'] = 'Código del material';
$string['groupcode_help'] = 'Código asociado al grupo';
$string['externalcode'] = 'Código en sistema externo';
$string['courseoptions'] = 'Opciones del material';
$string['search'] = 'Consultar';
$string['certificates_notfound'] = 'No se encontraron certificados para utilizar como plantilla. Cree por lo menos uno para poder continuar.';
$string['objectives_notfound'] = 'No se encontraron materiales con el código SAP del grupo indicado.';
$string['objective_name'] = 'Nombre';
$string['objective_hours'] = 'Horas';
$string['objective_date'] = 'Fecha del grupo';
$string['build'] = 'Generar';
$string['users_notfound'] = 'No se encontraron usuarios en el grupo con el código especificado';
$string['hours_multi'] = '{$a->local} horas (configuradas) / {$a->remote} horas (externo)';
$string['course_multi'] = '{$a->local} (se usará en el certificado) /<br /> {$a->remote} (externo)';
$string['users'] = 'Listado de usuarios';
$string['msg_error_not_create_user'] = 'El usuario {$a} no existe en la plataforma y no pudo ser creado.';
$string['module_notfound'] = 'No se encontró el módulo seleccionado como plantilla.';
$string['template_notfound'] = 'No se encontró el certificado seleccionado como plantilla.';
$string['certificate_ok'] = 'Se ha generado satisfactoriamente el certificado: {$a}.';
$string['certificate_ok_notemail'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero no se le ha podido enviar el correo electrónico.';
$string['certificate_ok_emailempty'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero el usuario no tiene una dirección por lo que no se le ha enviado el correo electrónico.';
$string['certificate_error'] = 'No se ha podido generar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['certificate_error_ns'] = 'No se ha podido guardar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['sendmail'] = 'Enviar correo de notificación a los usuarios';
$string['generalmessage'] = 'Hola {$a->firstname}...

Comfenalco Antioquia le informa que se ha generado el certificado <strong>{$a->certificate}</strong> en la plataforma de <em>{$a->sitename}</em> para el curso que cursó y aprobó de manera satisfactoria denominado <strong>{$a->course}</strong>.

Para consultar sus certificados visite el siguiente enlace:
{$a->url}

En la mayoría de los programas de correo electrónico, esto aparecerá como un enlace en color azul en el que usted puede hacer clic. Si no funcionara, copie y pegue la dirección en la barra de navegación de su navegador.

Sus datos para ingresar a la plataforma son:
<strong>Nombre de usuario:</strong> {$a->username}
<strong>Contraseña:</strong> {$a->password}

Si requiere ayuda, por favor contacte al área o dependencia responsable del servicio

{$a->admin}
';
$string['newcertificatesubject'] = 'Se le ha generado un certificado para el curso -{$a}- de Comfenalco';
$string['bademail'] = 'El correo electrónico ({$a->email}) del usuario {$a->fullname} es inválido, no se le ha enviado el correo de notificación.';
$string['certify_finish'] = 'Proceso completado';
$string['response_error'] = 'La respuesta obtenida del servidor externo no es válida.';
$string['external_notfound'] = 'No se encontró el grupo en el sistema externo.';
$string['allcertificates_delete'] = '¿Seguro que desea borrar los certificados asociados a ésta emisión? No se podrán recuperar.';
$string['bulktime'] = 'Fecha de emisión';
$string['issuing'] = 'Emisor';
$string['bulklist'] = 'Listado de emisiones';
$string['bulklist_count'] = 'Listado de emisiones ({$a->count}/{$a->total})';
$string['download_bulk'] = 'Descargar en zip';
$string['onecertificates_delete'] = '¿Seguro que desea borrar el certificado? No se podrá recuperar.';
$string['certificate_detail'] = 'Detalles de la emisión';
$string['rebuild'] = 'Volver a generar';
$string['certificate_owner'] = 'certificado de "{$a}"';
$string['issue_deleted'] = 'Certificado eliminado';
$string['bulk_deleted'] = 'Emisión de certificados eliminada';
$string['issues_notfound'] = 'No se encontraron certificados emitidos';
$string['issue_notfound'] = 'No se encontró el certificado indicado';
$string['rebuild_error'] = 'No se pudo volver a generar el certificado {$a}';
$string['remote_date'] = 'Fecha en el sistema externo';
$string['bulks_notfound'] = 'No se encontraron emisiones para el curso indicado';
$string['remote_hours'] = 'Horas en el sistema externo';
$string['issued'] = 'Issued';
$string['exportfilename'] = 'exportado_certificaciones';
$string['filename'] = 'Nombre del archivo';
$string['objective_delete'] = '¿Seguro que desea borrar el material? No se podrá recuperar.';
$string['objectives_deleted'] = 'Se ha eliminado correctamente el material.';
$string['objectives_errordeleting'] = 'Ocurrió un error y no se pudo borrar el material.';
$string['objectives'] = 'Listado de materiales';
$string['objectives_count'] = 'Listado de materiales ({$a->count}/{$a->total})';
$string['newobjective'] = 'Nuevo material';
$string['add_objective'] = 'Agregar material';
$string['objective_added'] = 'El material se creó satisfactoriamente.';
$string['objectives_erroradding'] = 'El material no pudo ser creado.';
$string['codetaken'] = 'El código ingresado ya existe y debe ser único.';
$string['course_statistics'] = 'Estadísticas del curso';
$string['site_statistics'] = 'Estadísticas del sitio';
$string['statistic_label'] = 'Estadística';
$string['statistic_value'] = 'Valor';
$string['count_bulk'] = 'Emisiones';
$string['count_issues'] = 'Certificados emitidos';
$string['count_by_certificate'] = 'Certificados por plantilla';
$string['count_users'] = 'Usuarios certificados';
$string['bulk_created'] = 'Generación de certificados masivos';
$string['empty_sapcode'] = 'El grupo no tiene un código SAP asociado. Debe ser corregido antes de poder continuar.';

$string['importobjectives'] = 'Import objectives';
$string['objectiveslist'] = 'Objectives list';
$string['import'] = 'Import';
$string['delimiter'] = 'Delimiter';
$string['bulkobjectiveadd'] = 'Add news';
$string['bulkobjectivereplace'] = 'Replace current objectives';
$string['bulkobjectivemode'] = 'Importation mode';
$string['recordsdeleted'] = 'Current objectives are be deleted.';
$string['fieldsincorrectsize'] = '{$a}: La fila no tiene un número correcto de campos, se ha omitido.';
$string['bulkcodetaken'] = '{$a}: El código ya existe y debe ser único.';
$string['bulkerroradding'] = '{$a}: Ocurrió un error desconocido y no se pudo almacenar la fila.';
$string['bulkhoursnotnumber'] = '{$a}: El número de horas debe ser numérico.';
$string['bulkhoursmaxerror'] = '{$a}: No se soportan números de horas de más de cuatro cifras.';

$string['type_remote'] = 'Remote';
$string['type_local'] = 'Local';
$string['objective_type'] = 'Objective type';
$string['userslist'] = 'Users list';
$string['userslist_help'] = 'Required columns are: username, firstname, lastname, email. Además, se pueden agregar
columnas con nombres de campos de usuario personalizados, empezando por la palabra <em>profile_</em>';
$string['badfieldslength'] = 'Length of fields is incorrect to row: {$a}';
$string['badcolumnslength'] = 'The length of columns is incorrect, {$a} fields are required';
$string['badcolumns'] = 'The columns names are incorrect';
$string['badcolumnsrequired'] = 'The field {$a->field} is required (row {$a->row})';
$string['customparams'] = 'Custom params';
$string['customparams_help'] = 'Un parámetro por fila, usando la sintaxis: <em>Nombre=valor</em>.
En el certificado, el nombre del campo debe estar en mayúscula.';
