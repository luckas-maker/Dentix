<?php
// Incluir archivos necesarios
require_once '../config/database.php'; // Conexión a novasoft
require_once 'send_email.php';       // Función sendVerificationEmail

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
    // 1. Obtener datos (id_usuario es ahora REQUERIDO)
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = intval($data['id_usuario'] ?? 0);
    $email = trim($data['email'] ?? '');
    
    if ($user_id <= 0 || empty($email)) {
        sendResponse(['success' => false, 'message' => 'Datos de usuario inválidos.'], 400);
    }
    
    // 2. Opcional: Verificar si el usuario ya está activo
    $sql_status = "SELECT estado_cuenta FROM Usuarios WHERE id_usuario = ?";
    $stmt_status = $db->prepare($sql_status);
    $stmt_status->execute([$user_id]);
    $user_status = $stmt_status->fetch(PDO::FETCH_ASSOC)['estado_cuenta'] ?? '';

    if ($user_status !== 'Pendiente') {
        sendResponse(['success' => false, 'message' => 'Esta cuenta ya está activa o tiene un estado diferente.'], 409);
    }
    
    // 3. Generar código de 6 dígitos y fecha de expiración (24 horas)
    $codigo = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $fecha_expiracion = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24 horas

    // 4. Iniciar Transacción: Limpiar tokens viejos e insertar nuevo
    $db->beginTransaction();
    
    // Eliminar tokens antiguos para este usuario y tipo
    $db->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ? AND tipo = 'ValidacionCorreo'")->execute([$user_id]);

    // Insertar el nuevo token
    $sql_token = "INSERT INTO tokensvalidacion (id_usuario, codigo, tipo, fecha_expiracion) VALUES (?, ?, 'ValidacionCorreo', ?)";
    $stmt_insert = $db->prepare($sql_token);
    
    if (!$stmt_insert->execute([$user_id, $codigo, $fecha_expiracion])) {
        throw new Exception("Fallo al insertar el token.");
    }
    
    $db->commit();
    
    // 5. Enviar el correo electrónico
    if (sendVerificationEmail($email, $codigo)) {
        sendResponse([
            'success' => true, 
            'message' => 'Código de 6 dígitos enviado a tu correo. Revisa tu bandeja.'
        ]);
    } else {
        // Notificar fallo en el envío, pero la DB ya tiene el token guardado
        sendResponse(['success' => false, 'message' => 'Fallo al enviar el correo. Revisa la configuración de SMTP.'], 500);
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    sendResponse(['success' => false, 'message' => 'Error de base de datos.', 'debug' => $e->getMessage()], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
?>