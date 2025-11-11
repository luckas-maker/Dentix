<?php
require_once '../config/database.php';
require_once 'send_email.php'; // Para usar la nueva función sendRecoveryEmail

header('Content-Type: application/json');
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}

$db = getDBConnection();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $email = trim($data['email'] ?? '');

    if (empty($email)) {
        sendResponse(['success' => false, 'message' => 'Correo requerido.'], 400);
    }

    // Regla 1: Verificar si el correo existe
    $sql_user = "SELECT id_usuario FROM Usuarios WHERE correo = ?";
    $stmt_user = $db->prepare($sql_user);
    $stmt_user->execute([$email]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(['success' => false, 'message' => 'El correo electrónico no está registrado.'], 404);
    }
    $user_id = $user['id_usuario'];

    // Regla 2: Limitar a 3 intentos por mes (30 días)
    $limit_sql = "SELECT COUNT(*) AS count FROM tokensvalidacion 
                  WHERE id_usuario = ? 
                  AND tipo = 'RecuperacionPass' 
                  AND fecha_expiracion > DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt_limit = $db->prepare($limit_sql);
    $stmt_limit->execute([$user_id]);
    $limit_count = $stmt_limit->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($limit_count >= 3) {
        sendResponse(['success' => false, 'message' => 'Límite de reenvío alcanzado (3 por mes).'], 429);
    }

    // 3. Generar código y expiración (15 minutos)
    $codigo = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $fecha_expiracion = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutos

    $db->beginTransaction();
    // Limpiar tokens viejos de recuperación
    $db->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ? AND tipo = 'RecuperacionPass'")->execute([$user_id]);
    // Insertar nuevo token
    $sql_token = "INSERT INTO tokensvalidacion (id_usuario, codigo, tipo, fecha_expiracion) VALUES (?, ?, 'RecuperacionPass', ?)";
    $db->prepare($sql_token)->execute([$user_id, $codigo, $fecha_expiracion]);
    $db->commit();
    
    // 4. Enviar Correo
    if (sendRecoveryEmail($email, $codigo)) {
        sendResponse(['success' => true, 'message' => 'Código enviado. Revisa tu correo.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Fallo al enviar el correo.'], 500);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>