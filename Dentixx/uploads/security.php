<?php
// Archivo de seguridad para prevenir listado de directorios
// Si alguien intenta acceder directamente a uploads/, verá este mensaje

header('HTTP/1.0 403 Forbidden');
header('Content-Type: text/plain; charset=utf-8');

// Log del intento de acceso (opcional)
$log_file = '../logs/acceso_uploads.log';
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$request_uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';

$log_entry = "[$timestamp] Acceso denegado a uploads/ - IP: $ip - UA: $user_agent - URI: $request_uri\n";

if (is_writable(dirname($log_file))) {
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

exit('Acceso denegado - Esta carpeta está protegida');
?>