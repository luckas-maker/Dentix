<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Ajusta las rutas según donde instalaste PHPMailer
require_once '../assets/phpmailer/Exception.php'; 
require_once '../assets/phpmailer/PHPMailer.php';
require_once '../assets/phpmailer/SMTP.php';

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
?>