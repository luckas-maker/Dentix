<?php
// RUTA CORREGIDA: Incluir la conexión PDO
require_once '../config/database.php'; 

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
        sendResponse(['success' => false, 'message' => 'Correo y código son obligatorios.'], 400);
    }

    // 1. Buscar ID del usuario y el token asociado
    $sql_data = "
        SELECT u.id_usuario, t.fecha_expiracion
        FROM Usuarios u
        JOIN tokensvalidacion t ON u.id_usuario = t.id_usuario
        WHERE u.correo = ? AND t.tipo = 'ValidacionCorreo' AND t.codigo = ?";
        
    $stmt_data = $db->prepare($sql_data);
    $stmt_data->execute([$email, $code]);
    $token_data = $stmt_data->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        sendResponse(['success' => false, 'message' => 'Código de verificación incorrecto.'], 401);
    }
    
    $user_id = $token_data['id_usuario'];
    $expiration_time = strtotime($token_data['fecha_expiracion']);

    // 2. Verificar expiración
    if (time() > $expiration_time) {
        $db->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ? AND tipo = 'ValidacionCorreo'")->execute([$user_id]);
        sendResponse(['success' => false, 'message' => 'El código de validación ha caducado, solicite uno nuevo.'], 401);
    }

    // 3. Código válido: Activar cuenta y eliminar token
    $db->beginTransaction();
    
    $sql_activate = "UPDATE Usuarios SET estado_cuenta = 'Activo' WHERE id_usuario = ?";
    $db->prepare($sql_activate)->execute([$user_id]);
    
    $sql_delete_token = "DELETE FROM tokensvalidacion WHERE id_usuario = ? AND tipo = 'ValidacionCorreo'";
    $db->prepare($sql_delete_token)->execute([$user_id]);

    $db->commit();

    sendResponse(['success' => true, 'message' => '¡Correo verificado! Su cuenta ha sido activada.']);
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>