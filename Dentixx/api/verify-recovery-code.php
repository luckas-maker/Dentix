<?php
session_start(); // Usaremos la sesión para autorizar el cambio
require_once '../config/database.php';

header('Content-Type: application/json');
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}

$db = getDBConnection();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');

    if (empty($email) || empty($code)) {
        sendResponse(['success' => false, 'message' => 'Faltan datos.'], 400);
    }

    // 1. Buscar el token y el ID de usuario
    $sql = "SELECT t.id_usuario, t.fecha_expiracion FROM tokensvalidacion t
            JOIN Usuarios u ON t.id_usuario = u.id_usuario
            WHERE u.correo = ? AND t.codigo = ? AND t.tipo = 'RecuperacionPass'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email, $code]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        sendResponse(['success' => false, 'message' => 'Código incorrecto.'], 401);
    }
    
    $user_id = $token_data['id_usuario'];
    $expiration_time = strtotime($token_data['fecha_expiracion']);

    // 2. Verificar expiración (15 minutos)
    if (time() > $expiration_time) {
        sendResponse(['success' => false, 'message' => 'El código ha caducado, solicite uno nuevo.'], 401);
    }

    // 3. Éxito: Autorizar el cambio de contraseña en la sesión
    $_SESSION['password_reset_user_id'] = $user_id;
    
    // Limpiar el token usado
    $db->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ? AND tipo = 'RecuperacionPass'")->execute([$user_id]);

    sendResponse(['success' => true, 'message' => 'Código verificado.']);
    
} catch (PDOException $e) {
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>