<?php
session_start();
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

// 1. Verificar autorización de la sesión
if (!isset($_SESSION['password_reset_user_id'])) {
    sendResponse(['success' => false, 'message' => 'No autorizado. Verifica el código primero.'], 401);
}

$user_id = $_SESSION['password_reset_user_id'];
$db = getDBConnection();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $new_password = $data['new_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        sendResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'], 400);
    }

    // 2. Hashear y actualizar la contraseña
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE Usuarios SET contrasena = ? WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$hash, $user_id]);

    // 3. Limpiar la sesión de autorización
    unset($_SESSION['password_reset_user_id']);
    
    sendResponse(['success' => true, 'message' => 'Contraseña actualizada exitosamente.']);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>