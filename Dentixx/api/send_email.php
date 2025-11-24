<?php
// Incluye las librerías de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth; // Incluir para futuras extensiones (como OAuth de Gmail)

// Usa __DIR__ para subir a /Dentixx y luego bajar a /assets/phpmailer
// La ruta es robusta, ya que usa la ubicación del archivo actual.
require_once __DIR__ . '/../assets/phpmailer/Exception.php'; 
require_once __DIR__ . '/../assets/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../assets/phpmailer/SMTP.php';

/**
 * Función para enviar un correo de verificación.
 */
function sendVerificationEmail($recipient_email, $code) {
    // Configuración específica de la cuenta de Kevin Eliuth
    $sender_email = 'giselvaar@gmail.com'; 
    // Contraseña de aplicación: qnmi zptw kskk uyrz
    $app_password = 'qnmi zptw kskk uyrz'; 

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $sender_email; 
        $mail->Password   = $app_password; 
        $mail->CharSet = 'UTF-8';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Emisor y Receptor
        $mail->setFrom($sender_email, 'Dentixx - Codigo de Verificacion');
        $mail->addAddress($recipient_email);
        $mail->isHTML(true);
        $mail->Subject = 'Tu Código de Verificación para Dentixx';
        
        // Contenido del Correo
        $mail->Body    = "
            <h2>Tu Código de Verificación</h2>
            <p>Tu código de 6 dígitos para validar tu correo electrónico es:</p>
            <h1 style='color:#0077b6; font-size:32px;'><b>{$code}</b></h1>
            <p>Este código expira en 24 horas.</p>
        ";
        $mail->AltBody = "Tu código de verificación es: {$code}";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // En un entorno real, aquí se usaría un sistema de logs
        // error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Envía un ENLACE de activación para cuentas 'Pendientes'.
 */
function sendActivationLinkEmail($recipient_email, $token) {
    $mail = new PHPMailer(true);

    try {
        // --- Configuración del Servidor SMTP (Gmail) ---
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz'; // Tu contraseña de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Dentixx - Activación de Cuenta');
        $mail->addAddress($recipient_email);

        // RUTA CORREGIDA: El enlace debe apuntar a la nueva API 'activate.php'
        $activation_link = "http://localhost/Dentixx/api/activate.php?token=" . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Activa tu cuenta de Dentixx';
        $mail->Body    = "
            <h2>Activa tu cuenta</h2>
            <p>Detectamos que intentaste iniciar sesión pero tu cuenta aún está pendiente. Haz clic en el siguiente enlace para activarla:</p>
            <p><a href='{$activation_link}' style='background-color:#0077b6;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Activar mi Cuenta</a></p>
            <p>Este enlace expira en <b>15 minutos</b>.</p>
            <p>Si no solicitaste esto, puedes ignorar este correo.</p>
        ";
        $mail->AltBody = "Copia y pega este enlace para activar tu cuenta: {$activation_link}. Expira en 15 minutos.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Envía un CÓDIGO de 6 dígitos para recuperación de contraseña.
 */
function sendRecoveryCodeEmail($recipient_email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Dentixx - Recuperación');
        $mail->addAddress($recipient_email);

        $mail->isHTML(true);
        $mail->Subject = 'Tu Código de Recuperación de Contraseña';
        $mail->Body    = "
            <h2>Recuperación de Contraseña</h2>
            <p>Tu código de 6 dígitos es:</p>
            <h1 style='color:#0077b6; font-size:32px;'><b>{$code}</b></h1>
            <p>Este código expira en <b>15 minutos</b>.</p>
        ";
        $mail->AltBody = "Tu código de recuperación es: {$code}. Expira en 15 minutos.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Envía una notificación de estado de cita (Aceptada o Rechazada).
 */
function sendAppointmentStatusEmail($recipient_email, $patient_name, $status, $cita_info) {
    $mail = new PHPMailer(true);

    // --- Definir el contenido basado en el estado ---
    if ($status === 'Confirmada') {
        $subject = '¡Tu cita ha sido confirmada!';
        $body = "
            <h2>Hola, {$patient_name},</h2>
            <p>¡Buenas noticias! Tu cita en Dentixx ha sido <strong>CONFIRMADA</strong>.</p>
            <p><strong>Detalles de la cita:</strong></p>
            <ul>
                <li><strong>Servicio:</strong> {$cita_info['servicio']}</li>
                <li><strong>Fecha:</strong> {$cita_info['fecha']}</li>
                <li><strong>Hora:</strong> {$cita_info['hora']}</li>
            </ul>
            <p>Te esperamos. Si no puedes asistir, por favor cancela con anticipación desde tu perfil.</p>
        ";
        $altBody = "Hola {$patient_name}, tu cita para {$cita_info['servicio']} el {$cita_info['fecha']} a las {$cita_info['hora']} ha sido CONFIRMADA.";
    
    } elseif ($status === 'Rechazada') {
        $subject = 'Actualización sobre tu solicitud de cita';
        $body = "
            <h2>Hola, {$patient_name},</h2>
            <p>Lamentamos informarte que tu solicitud de cita para el <strong>{$cita_info['fecha']} a las {$cita_info['hora']}</strong> ha sido <strong>RECHAZADA</strong>.</p>
            <p>Esto suele ocurrir si la disponibilidad del odontólogo cambió o si se requiere más información. Por favor, intenta agendar en otro horario disponible.</p>
            <p>Lamentamos las molestias.</p>
        ";
        $altBody = "Hola {$patient_name}, tu solicitud de cita para el {$cita_info['fecha']} a las {$cita_info['hora']} ha sido RECHAZADA.";
    
    } else {
        // No enviar correo si el estado no es uno de estos
        return false; 
    }

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz'; // Tu contraseña de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Dentixx - Estado de Cita');
        $mail->addAddress($recipient_email, $patient_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // error_log("Mailer Error (sendAppointmentStatusEmail): {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Notifica al Odontólogo sobre una nueva solicitud de cita.
 */
function sendNewAppointmentNotificationEmail($patient_name, $patient_email, $fecha_cita, $hora_cita) {
    $mail = new PHPMailer(true);
    $dentist_email = 'giselvaar@gmail.com'; // Correo fijo del Odontólogo
    
    // Formatear hora (de 14:00:00 a 02:00 PM)
    $hora_formateada = date("h:i A", strtotime($hora_cita));
    // Formatear fecha (de YYYY-MM-DD a DD/MM/YYYY)
    $fecha_formateada = date("d/m/Y", strtotime($fecha_cita));

    try {
        // --- Configuración del Servidor SMTP (Gmail) ---
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8'; // Para tildes
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; // Tu correo de envío
        $mail->Password   = 'qnmi zptw kskk uyrz'; // Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Configuración del Emisor y Receptor ---
        $mail->setFrom('giselvaar@gmail.com', 'Sistema Dentixx'); // SE MODIFICARA MAS ADELANTE PARA EL CORREO DEL ODONTOLOGO
        $mail->addAddress($dentist_email, 'Odontólogo Dentixx');

        // --- Contenido del Correo ---
        
        // MODIFICACIÓN: El enlace apunta a 'citasolicitadas.php'
        $panel_link = 'http://localhost/Dentixx/MODULO3/HTML/citasolicitadas.php';

        $mail->isHTML(true);
        $mail->Subject = '¡Nueva Solicitud de Cita Pendiente!';
        $mail->Body    = "
            <h2>Nueva Solicitud de Cita</h2>
            <p>Se ha registrado una nueva solicitud de cita en el sistema:</p>
            <ul>
                <li><strong>Paciente:</strong> " . htmlspecialchars($patient_name) . "</li>
                <li><strong>Correo Paciente:</strong> " . htmlspecialchars($patient_email) . "</li>
                <li><strong>Fecha Solicitada:</strong> {$fecha_formateada}</li>
                <li><strong>Hora Solicitada:</strong> {$hora_formateada}</li>
            </ul>
            <p>Por favor, accede a tu panel para <strong>Aceptar</strong> o <strong>Rechazar</strong> esta solicitud.</p>
            
            <p><a href='{$panel_link}' style='background-color:#0077b6;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:5px;'>
                Ir a Citas Solicitadas
            </a></p>
        ";
        
        // MODIFICACIÓN: Enlace usa $panel_link
        $mail->AltBody = "Nueva solicitud de cita: Paciente: {$patient_name}, Fecha: {$fecha_formateada} a las {$hora_formateada}. Revisa tu panel de Dentixx: {$panel_link}";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Registrar el error sin detener el script si la notificación falla
        error_log("Error al notificar al odontólogo: {$mail->ErrorInfo}");
        // IMPORTANTE: Según tus reglas, si el correo falla, la reserva debe fallar.
        return false; 
    }
}

/**
 * NUEVA FUNCIÓN: Envía un recordatorio 24h antes de la cita.
 */
function sendAppointmentReminderEmail($recipient_email, $patient_name, $fecha_cita, $hora_cita) {
    $mail = new PHPMailer(true);
    $dentist_email = 'giselvaar@gmail.com'; // Correo de envío
    
    // Formatear hora (de 14:00:00 a 02:00 PM)
    $hora_formateada = date("h:i A", strtotime($hora_cita));
    
    // Formatear fecha (de YYYY-MM-DD a 17 de Noviembre, 2025)
    // Asegurarse de que el idioma del servidor esté en español para 'strftime'
    setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish'); 
    $fecha_formateada = strftime("%A, %d de %B de %Y", strtotime($fecha_cita));
    
    // Dirección (PENDIENTE DE AUTORIZACION)
    //$direccion_consultorio = "Av. Siempre Viva 123, Consultorio 4, Springfield";
    //
    //
    //





    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $dentist_email;
        $mail->Password   = 'qnmi zptw kskk uyrz'; // Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom($dentist_email, 'Recordatorios Dentixx');
        $mail->addAddress($recipient_email, $patient_name);

        $mail->isHTML(true);
        $mail->Subject = 'Recordatorio de tu Cita en Dentixx';
        $mail->Body    = "
            <h2>Hola, " . htmlspecialchars($patient_name) . "</h2>
            <p>Este es un recordatorio amistoso de tu próxima cita en Dentixx:</p>
            <div style='background-color:#f4f4f7; padding: 20px; border-radius: 8px;'>
                <p><strong>Fecha:</strong> {$fecha_formateada}</p>
                <p><strong>Hora:</strong> {$hora_formateada}</p>
                <p><strong>Dirección:</strong> {$direccion_consultorio}</p>
            </div>
            <p>Si no puedes asistir, por favor cancela tu cita desde tu panel de paciente. ¡Te esperamos!</p>
        ";
        $mail->AltBody = "Recordatorio de Cita: {$fecha_formateada} a las {$hora_formateada}. Dirección: {$direccion_consultorio}.";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Mailer Error (sendAppointmentReminderEmail): {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Notifica al PACIENTE que el odontólogo canceló su cita.
 */
function sendCancellationToPatientEmail($recipient_email, $patient_name, $fecha_cita, $hora_cita) {
    $mail = new PHPMailer(true);
    $hora_formateada = date("h:i A", strtotime($hora_cita));
    $fecha_formateada = date("d/m/Y", strtotime($fecha_cita));

    try {
        $mail->isSMTP(); $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Dentixx - Aviso de Cita');
        $mail->addAddress($recipient_email, $patient_name);

        $mail->isHTML(true);
        $mail->Subject = 'Tu cita en Dentixx ha sido cancelada';
        $mail->Body    = "
            <h2>Hola, " . htmlspecialchars($patient_name) . "</h2>
            <p>Lamentamos informarte que tu cita programada para el día <strong>{$fecha_formateada} a las {$hora_formateada}</strong> ha sido <strong>CANCELADA</strong> por el consultorio.</p>
            <p>Por favor, ingresa nuevamente a tu panel para agendar en un nuevo horario disponible.</p>
            <p>Lamentamos las molestias.</p>
        ";
        $mail->send(); return true;
    } catch (Exception $e) {
        error_log("Mailer Error (sendCancellationToPatientEmail): {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Notifica al ODONTÓLOGO que el paciente canceló.
 * (Envía a giselvaar@gmail.com)
 */
function sendCancellationToDentistEmail($patient_name, $patient_email, $fecha_cita, $hora_cita, $motivo) {
    $mail = new PHPMailer(true);
    $dentist_email = 'giselvaar@gmail.com'; // Correo fijo del Odontólogo
    
    $hora_formateada = date("h:i A", strtotime($hora_cita));
    $fecha_formateada = date("d/m/Y", strtotime($fecha_cita));

    try {
        $mail->isSMTP(); $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Sistema Dentixx');
        $mail->addAddress($dentist_email, 'Odontólogo Dentixx');

        $mail->isHTML(true);
        $mail->Subject = 'NOTIFICACIÓN: Un paciente ha cancelado su cita';
        $mail->Body    = "
            <h2>Cancelación de Cita</h2>
            <p>El paciente <strong>" . htmlspecialchars($patient_name) . "</strong> (" . htmlspecialchars($patient_email) . ") ha cancelado su cita:</p>
            <ul>
                <li><strong>Fecha:</strong> {$fecha_formateada}</li>
                <li><strong>Hora:</strong> {$hora_formateada}</li>
            </ul>
            <p><strong>Motivo de cancelación proporcionado por el paciente:</strong></p>
            <p style='background-color:#f4f4f7; padding: 15px; border-radius: 8px;'>
                <em>" . htmlspecialchars($motivo) . "</em>
            </p>
        ";
        $mail->send(); return true;
    } catch (Exception $e) {
        error_log("Mailer Error (sendCancellationToDentistEmail): {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * NUEVA FUNCIÓN: Notifica al PACIENTE que se registró una inasistencia.
 */
function sendMissedAppointmentEmail($recipient_email, $patient_name, $fecha_cita, $hora_cita) {
    $mail = new PHPMailer(true);
    $hora_formateada = date("h:i A", strtotime($hora_cita));
    $fecha_formateada = date("d/m/Y", strtotime($fecha_cita));

    try {
        $mail->isSMTP(); $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'giselvaar@gmail.com'; 
        $mail->Password   = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Dentixx - Aviso de Inasistencia');
        $mail->addAddress($recipient_email, $patient_name);

        $mail->isHTML(true);
        $mail->Subject = 'Registro de Inasistencia - Dentixx';
        $mail->Body    = "
            <h2>Hola, " . htmlspecialchars($patient_name) . "</h2>
            <p>Te informamos que se ha registrado una <strong>inasistencia</strong> para tu cita programada el día <strong>{$fecha_formateada} a las {$hora_formateada}</strong>.</p>
            <p>Recuerda que las faltas consecutivas pueden afectar tu prioridad para futuras reservas.</p>
            <p>Si deseas realizar otra cita, puedes acudir a nuestro sitio web.</p>
            <p><a href='http://localhost/Dentixx/MODULO2/HTML/veragendas.php' style='background-color:#0077b6;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Agendar Nueva Cita</a></p>
        ";
        $mail->send(); return true;
    } catch (Exception $e) {
        error_log("Mailer Error (sendMissedAppointmentEmail): {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Notificación de Cuenta Bloqueada
 */
function sendAccountBlockedEmail($email, $nombre, $citas_canceladas = false) {
    $mail = new PHPMailer(true);
    
    // Mensaje adicional si se cancelaron citas
    $aviso_citas = "";
    if ($citas_canceladas) {
        $aviso_citas = "<p style='color: #d32f2f; font-weight: bold;'>⚠️ Nota importante: Tus citas pendientes o confirmadas han sido canceladas automáticamente debido a este bloqueo.</p>";
    }

    try {
        $mail->isSMTP(); $mail->CharSet = 'UTF-8';
        $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'giselvaar@gmail.com'; $mail->Password = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Seguridad Dentixx');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = '⚠️ Alerta de Seguridad: Cuenta Bloqueada';
        $mail->Body = "
            <h2>Estimado/a {$nombre},</h2>
            <p>Tu cuenta ha sido <strong>BLOQUEADA</strong> temporalmente debido a actividad inusual o incumplimiento de nuestras políticas.</p>
            {$aviso_citas}
            <p style='background-color:#ffebee; padding:15px; border-radius:5px; border-left:5px solid #c62828; color:#c62828;'>
                <strong>Acción Requerida:</strong><br>
                Si consideras que esto es un error, por favor acude personalmente a nuestra clínica para verificar tu identidad y restaurar el acceso.
            </p>
            <p>Atentamente,<br>Equipo Administrativo Dentixx</p>
        ";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}

/**
 * Notificación de Cuenta Desbloqueada
 */
function sendAccountUnblockedEmail($email, $nombre) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->CharSet = 'UTF-8';
        $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'giselvaar@gmail.com'; $mail->Password = 'qnmi zptw kskk uyrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

        $mail->setFrom('giselvaar@gmail.com', 'Seguridad Dentixx');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = '✅ Cuenta Restaurada - Dentixx';
        $mail->Body = "
            <h2>¡Buenas noticias, {$nombre}!</h2>
            <p>Tu cuenta ha sido <strong>DESBLOQUEADA</strong> por un administrador.</p>
            <p>Ya puedes iniciar sesión normalmente y acceder a nuestros servicios.</p>
            <p><a href='http://localhost/Dentixx/MODULO1/HTML/iniciosesion.php'>Iniciar Sesión Ahora</a></p>
        ";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}
?>