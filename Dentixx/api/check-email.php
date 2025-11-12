<?php
// Forzar que siempre se devuelva JSON válido
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para devolver respuesta JSON y salir
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(['success' => true]);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['available' => false, 'message' => 'Método no permitido'], 405);
}

try {
    // Verificar que el archivo de configuración existe
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        sendResponse([
            'available' => false, 
            'message' => 'Error interno del servidor',
            'debug' => 'Archivo de configuración no encontrado: ' . $configPath
        ], 500);
    }
    
    require_once $configPath;
    
    // Obtener datos del POST
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendResponse(['available' => false, 'message' => 'No se recibieron datos'], 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse([
            'available' => false, 
            'message' => 'Datos JSON inválidos',
            'debug' => json_last_error_msg()
        ], 400);
    }
    
    $correo = trim($data['email'] ?? '');
    
    if (empty($correo)) {
        sendResponse(['available' => false, 'message' => 'Correo requerido'], 400);
    }
    
    // Verificar formato de email
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        sendResponse(['available' => false, 'message' => 'Formato de correo inválido'], 400);
    }
    
    // Conectar a la base de datos
    $db = getDBConnection();
    
    // Verificar si el email existe
    $stmt = $db->prepare("SELECT id_usuario FROM Usuarios WHERE correo = ?");
    if (!$stmt) {
        sendResponse([
            'available' => false, 
            'message' => 'Error interno del servidor',
            'debug' => 'Error al preparar consulta'
        ], 500);
    }
    
    $result = $stmt->execute([$correo]);
    if (!$result) {
        sendResponse([
            'available' => false, 
            'message' => 'Error interno del servidor',
            'debug' => 'Error al ejecutar consulta'
        ], 500);
    }
    
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Devolver respuesta exitosa
    sendResponse(['available' => !$exists]);
    
} catch (PDOException $e) {
    sendResponse([
        'available' => false, 
        'message' => 'Error de base de datos',
        'debug' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    sendResponse([
        'available' => false, 
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ], 500);
}
?>