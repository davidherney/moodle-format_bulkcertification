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
$string['addsections'] = 'Agregar tema';
$string['allcertificates_delete'] = '¿Seguro que desea borrar los certificados asociados a ésta emisión? No se podrán recuperar.';
$string['badcolumnslength'] = 'La longitud de las columnas es incorrecta, {$a} campos son obligatorios';
$string['badcolumnsrequired'] = 'El campo {$a->field} es obligatorio (fila {$a->row})';
$string['bademail'] = 'El correo electrónico ({$a->email}) del usuario {$a->fullname} es inválido, no se le ha enviado el correo de notificación.';
$string['badfieldslength'] = 'La longitud de los campos es incorrecta para la fila: {$a}';
$string['badusername'] = 'El nombre de usuario ({$a}) es inválido.';
$string['badusernames'] = 'El nombre y el apellido son obligatorios para los nuevos usuarios ({$a})';
$string['build'] = 'Generar';
$string['bulk'] = 'Emisión masiva';
$string['bulk_created'] = 'Generación de certificados masivos';
$string['bulk_deleted'] = 'Emisión de certificados eliminada';
$string['bulkcertification:deleteissues'] = "Eliminar certificados en formato de certificación masiva";
$string['bulkcertification:manage'] = "Gestionar el formato de certificación masiva";
$string['bulkcodetaken'] = '{$a}: El código ya existe y debe ser único.';
$string['bulkerroradding'] = '{$a}: Ocurrió un error desconocido y no se pudo almacenar la fila.';
$string['bulkhoursmaxerror'] = '{$a}: No se soportan números de horas de más de cuatro cifras.';
$string['bulkhoursnotnumber'] = '{$a}: El número de horas debe ser numérico.';
$string['bulklist'] = 'Listado de emisiones';
$string['bulkobjectiveadd'] = 'Agregar material';
$string['bulkobjectivemode'] = 'Modo de importación';
$string['bulkobjectivereplace'] = 'Reemplazar materiales actuales';
$string['bulktime'] = 'Fecha de emisión';
$string['certificate_detail'] = 'Detalles de la emisión';
$string['certificate_error'] = 'No se ha podido generar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['certificate_error_ns'] = 'No se ha podido guardar el certificado para el usuario: {$a->username} - {$a->fullname}.';
$string['certificate_ok'] = 'Se ha generado satisfactoriamente el certificado: {$a}.';
$string['certificate_ok_emailempty'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero el usuario no tiene una dirección por lo que no se le ha enviado el correo electrónico.';
$string['certificate_ok_notemail'] = 'Se ha generado satisfactoriamente el certificado: {$a}, pero no se le ha podido enviar el correo electrónico.';
$string['certificate_owner'] = 'certificado de "{$a}"';
$string['certificates_notfound'] = 'No se encontraron certificados para utilizar como plantilla. Cree por lo menos uno para poder continuar.';
$string['certifiedfilenamelabel'] = 'Certificado';
$string['cli_certificationnotfount'] = 'No se encontró información del certificado';
$string['cli_errors'] = 'Errores';
$string['cli_help'] = 'Acciones disponibles en el cli.

Opciones:
-h,  --help              Imprime esta ayuda
-rb, --rebulk=X          Volver a generar la certificación masiva de X, X = bulk id
-ri, --reissue=X         Volver a generar la emisión X, X = issue id (no confundir con el ID de la emisión del Certificado simple)
-c,  --clone             Clona la emisión al volver a generar la certificación (en masa o individual)
-iu, --iuser             Información sobre la certificación de los usuarios... por Nombre de usuario

Ejemplo:
\$sudo -u www-data /usr/bin/php cli.php -iu=student1
';
$string['cli_messages'] = 'Mensajes';
$string['cli_paramvaluerequired'] = 'Debe especificar el {$a}';
$string['cli_rebuilding'] = 'Volviendo a generar la certificación masiva';
$string['cli_rebuildingissue'] = 'Volviendo a generar la emisión';
$string['cli_usernotfound'] = 'Usuario no encontrado';
$string['codetaken'] = 'El código ingresado ya existe y debe ser único.';
$string['count_bulk'] = 'Emisiones';
$string['count_by_certificate'] = 'Certificados por plantilla';
$string['count_issues'] = 'Certificados emitidos';
$string['count_users'] = 'Usuarios certificados';
$string['course_multi'] = '{$a->local} (local) /<br /> {$a->remote} (externo)';
$string['course_statistics'] = 'Estadísticas del curso';
$string['courseoptions'] = 'Opciones del material';
$string['currentsection'] = 'Este tema';
$string['customparams'] = 'Parámetros personalizados';
$string['customparams_help'] = 'Un parámetro por fila, usando la sintaxis: <em>Nombre=valor</em>.
En el certificado, el nombre del campo debe estar en mayúscula.';
$string['defaultemail'] = 'Correo electrónico por defecto';
$string['defaultemail_help'] = 'Correo electrónico por defecto para los usuarios nuevos cuando no se especifique uno en el listado de usuarios.
Tenga presente que este correo se utilizará para enviar la clave de acceso a los usuarios nuevos.<br>
Puede usar una plantilla para la dirección de correo, incluyendo las siguientes variables: {index}, {username}, {firstname}, {lastname}.
Las variables: {firstname} y {lastname} se limpiarán y tendrán formato para ser válidas como correo. Ejemplo: <em>usuario-{index}@midominio.com</em><br>
También puede usar la palabra clave <em>creator</em> para asignar el correo del usuario que está realizando la importación.<br>
<b>Si se deja vacío y un usuario no tiene la información del correo, no se creará su cuenta.</b>';
$string['defaultemailerror'] = 'El correo electrónico por defecto no es válido y el usuario {$a} requiere un correo para ser creado.';
$string['deletesection'] = 'Eliminar tema';
$string['delimiter'] = 'Delimitador';
$string['download_bulk'] = 'Descargar en zip';
$string['editsection'] = 'Editar tema';
$string['emptyobjectivesdelimiter'] = 'Delimitador de materiales de importación vacío';
$string['external_notfound'] = 'No se encontró el grupo en el sistema externo.';
$string['externalinfotitle'] = 'Información del sistema externo';
$string['fieldsincorrectsize'] = '{$a}: La fila no tiene un número correcto de campos, se ha omitido.';
$string['fieldsincorrecttype'] = '{$a}: El tipo de material no es válido, se ha omitido. Sólo se permite: <em>local</em>, <em>remote</em>.';
$string['generalmessage'] = 'Hola {$a->firstname}....

El certificado <strong>{$a->certificate}</strong> se ha generado en la plataforma <em>{$a->sitename}</em> para el curso <strong>{$a->course}</strong>.

Para consultar sus certificados visite el siguiente enlace:
{$a->url}

En la mayoría de los programas de correo electrónico, esto aparecerá como un enlace azul en el que puede hacer clic. Si no funciona, copie y pegue la dirección en la barra de navegación de su navegador.

{$a->admin}
';
$string['groupcode'] = 'Código de grupo';
$string['hidefromothers'] = 'Ocultar tema';
$string['hours_multi'] = '{$a->local} horas (configuradas) / {$a->remote} horas (externo)';
$string['id'] = 'Id';
$string['import'] = 'Importar';
$string['importobjectives'] = 'Importar materiales';
$string['invalidobjective'] = 'El material no es válido';
$string['invalidtype'] = 'El tipo de material no es válido';
$string['issue_deleted'] = 'Certificado eliminado';
$string['issue_notfound'] = 'No se encontró el certificado indicado';
$string['issued'] = 'Emitido';
$string['issues_notfound'] = 'No se encontraron certificados emitidos';
$string['issuing'] = 'Emisor';
$string['module_deleted'] = 'El módulo seleccionado como plantilla ya no existe.';
$string['module_notfound'] = 'No se encontró el módulo seleccionado como plantilla.';
$string['msg_error_not_create_user'] = 'El usuario {$a} no existe en la plataforma y no pudo ser creado.';
$string['newcertificatesubject'] = 'Se le ha generado un certificado para el curso -{$a}-';
$string['newobjective'] = 'Nuevo material';
$string['objective_added'] = 'El material se creó satisfactoriamente.';
$string['objective_code'] = 'Código';
$string['objective_date'] = 'Fecha del grupo';
$string['objective_delete'] = '¿Seguro que desea borrar el material? No se podrá recuperar.';
$string['objective_hours'] = 'Horas';
$string['objective_name'] = 'Nombre';
$string['objective_type'] = 'Tipo de material';
$string['objectives'] = 'Listado de materiales';
$string['objectives_deleted'] = 'Se ha eliminado correctamente el material.';
$string['objectives_erroradding'] = 'El material no pudo ser creado.';
$string['objectives_errordeleting'] = 'Ocurrió un error y no se pudo borrar el material.';
$string['objectiveslist'] = 'Lista de materiales';
$string['objectiveslist_help'] = 'Se requieren cuatro columnas en el siguiente orden:
nombre, código, horas, tipo. El tipo puede ser: <em>local</em> o <em>remote</em>.<br>
<b>Importante:</b> No utilices encabezados.';
$string['onecertificates_delete'] = '¿Seguro que desea borrar el certificado? No se podrá recuperar.';
$string['pluginname'] = 'Formato de certificación masiva';
$string['privacy:metadata'] = 'El complemento de formato de certificación masiva no almacena ningún dato personal.';
$string['rebuild'] = 'Volver a generar';
$string['rebuild_error'] = 'No se pudo volver a generar el certificado {$a}';
$string['recordsdeleted'] = 'Los materiales actuales han sido eliminados.';
$string['remote_date'] = 'Fecha en el sistema externo';
$string['remote_hours'] = 'Horas en el sistema externo';
$string['report_certified'] = 'Emisiones de certificación masiva';
$string['report_statistics'] = 'Estadísticas de certificación masivas';
$string['requireduserfield'] = 'El campo {$a->field} es obligatorio para el usuario {$a->username}';
$string['response_error'] = 'La respuesta obtenida del servidor externo no es válida.';
$string['search'] = 'Consultar';
$string['sectionname'] = 'Sección';
$string['sendmail'] = 'Enviar correo de notificación a los usuarios
(tanto para la notificación de los certificados como con la clave a los usuarios nuevos).';
$string['showcertified'] = 'Mostrar certificados emitidos';
$string['showfromothers'] = 'Mostrar tema';
$string['site_statistics'] = 'Estadísticas del sitio';
$string['statistic_label'] = 'Estadística';
$string['statistic_value'] = 'Valor';
$string['template'] = 'Plantilla';
$string['template_help'] = 'Certificado que se utilizará como plantilla para la generación de los certificados masivos';
$string['template_notfound'] = 'No se encontró el certificado seleccionado como plantilla.';
$string['type_remote'] = 'Externo';
$string['usercreated'] = 'El usuario {$a->username} ha sido creado con el correo {$a->email} (ID: {$a->id}).';
$string['users'] = 'Listado de usuarios';
$string['users_notfound'] = 'No se encontraron usuarios en el grupo con el código especificado';
$string['userslist'] = 'Lista de usuarios';
$string['userslist_help'] = 'Las columnas requeridas son: <em>username</em>. <br>
Para nuevos usuarios: <em>firstname</em>, <em>lastname</em>, <em>email</em>.
Además, se pueden agregar columnas con nombres de campos de usuario personalizados, comenzando por la palabra <em>profile_</em>';
$string['wspassword'] = 'Contraseña del WS';
$string['wsuri'] = 'Uri del Servicio Web (WS)';
$string['wsuriemptyerror'] = 'La Uri del WS es necesaria para fuentes externas.';
$string['wsuser'] = 'Usuario del WS';
