<?php
session_start();
header('Content-Type: application/json');
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$correo = trim($data['email'] ?? '');
$codigo = trim($data['code'] ?? '');

if (empty($correo) || empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Correo y código son obligatorios']);
    exit;
}

try {
    $db = getDBConnection();

    // Buscar usuario
    $stmt = $db->prepare("SELECT id_usuario FROM Usuarios WHERE correo = :correo");
    $stmt->execute(['correo' => $correo]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Buscar token válido
    $stmt = $db->prepare("
        SELECT codigo 
        FROM TokensValidacion 
        WHERE id_usuario = :id_usuario 
        AND tipo = 'ValidacionCorreo' 
        AND fecha_expiracion > NOW() 
        ORDER BY id_token DESC 
        LIMIT 1
    ");
    $stmt->execute(['id_usuario' => $usuario['id_usuario']]);
    $token = $stmt->fetch();

    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'Código expirado o no válido']);
        exit;
    }

    // Verificar código
    if (!password_verify($codigo, $token['codigo'])) {
        echo json_encode(['success' => false, 'message' => 'Código incorrecto']);
        exit;
    }

    // Activar cuenta
    $stmt = $db->prepare("UPDATE Usuarios SET estado_cuenta = 'Activo' WHERE id_usuario = :id_usuario");
    $stmt->execute(['id_usuario' => $usuario['id_usuario']]);

    // Eliminar tokens usados
    $stmt = $db->prepare("DELETE FROM TokensValidacion WHERE id_usuario = :id_usuario AND tipo = 'ValidacionCorreo'");
    $stmt->execute(['id_usuario' => $usuario['id_usuario']]);

    echo json_encode(['success' => true, 'message' => 'Correo verificado exitosamente']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?>
