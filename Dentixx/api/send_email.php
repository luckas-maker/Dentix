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
    $sender_email = 'kevineliuth1234@gmail.com'; 
    // Contraseña de aplicación: pzmo cngr kyve rbgp
    $app_password = 'pzmo cngr kyve rbgp'; 

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

function sendRecoveryEmail($recipient_email, $code) {
    $mail = new PHPMailer(true);

    try {
        // --- Configuración del Servidor SMTP (Gmail) ---
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8'; // Para tildes (Código)
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kevineliuth1234@gmail.com'; 
        $mail->Password   = 'pzmo cngr kyve rbgp'; // Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Configuración del Emisor y Receptor ---
        $mail->setFrom('kevineliuth1234@gmail.com', 'Dentixx - Recuperación');
        $mail->addAddress($recipient_email);

        // --- Contenido del Correo ---
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - Dentixx';
        $mail->Body    = "
            <h2>Recuperación de Contraseña</h2>
            <p>Has solicitado restablecer tu contraseña. Tu código de 6 dígitos es:</p>
            <h1 style='color:#0077b6; font-size:32px;'><b>{$code}</b></h1>
            <p>Este código expira en <b>15 minutos</b>.</p>
            <p>Si no solicitaste esto, puedes ignorar este correo.</p>
        ";
        $mail->AltBody = "Tu código de recuperación es: {$code}. Expira en 15 minutos.";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // error_log("Mailer Error: {$mail->ErrorInfo}"); // Descomenta para depurar
        return false;
    }
}

?>