<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para respuesta segura
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(['success' => true]);
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

try {
    // Incluir configuración de BD
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        sendResponse(['success' => false, 'message' => 'Configuración no encontrada'], 500);
    }
    require_once $configPath;
    
    // Obtener datos
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendResponse(['success' => false, 'message' => 'No se recibieron datos'], 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendResponse(['success' => false, 'message' => 'Correo y contraseña son obligatorios'], 400);
    }
    
    // Conectar a BD
    $db = getDBConnection();
    
    // Buscar usuario por correo
    $stmt = $db->prepare("SELECT id_usuario, nombre, apellidos, correo, contrasena, rol, estado_cuenta FROM Usuarios WHERE correo = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        sendResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos']);
    }
    
    // Verificar estado de la cuenta
    if ($usuario['estado_cuenta'] === 'Bloqueado') {
        sendResponse(['success' => false, 'message' => 'Tu cuenta ha sido bloqueada. Contacta al administrador.']);
    }
    
    if ($usuario['estado_cuenta'] === 'Pendiente') {
        sendResponse(['success' => false, 'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.']);
    }
    
    // Verificar contraseña
    if (!password_verify($password, $usuario['contrasena'])) {
        sendResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos']);
    }
    
    // Login exitoso - crear sesión
    $_SESSION['user_id'] = $usuario['id_usuario'];
    $_SESSION['user_name'] = $usuario['nombre'] . ' ' . $usuario['apellidos'];
    $_SESSION['user_email'] = $usuario['correo'];
    $_SESSION['user_role'] = $usuario['rol'];
    
    sendResponse([
        'success' => true, 
        'message' => 'Inicio de sesión exitoso',
        'user' => [
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellidos'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol']
        ]
    ]);
    
} catch (PDOException $e) {
    sendResponse([
        'success' => false, 
        'message' => 'Error de base de datos',
        'debug' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    sendResponse([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ], 500);
}
?>