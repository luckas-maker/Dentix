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
    sendResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

try {
    // Obtener datos del POST
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendResponse(['success' => false, 'message' => 'No se recibieron datos'], 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse([
            'success' => false, 
            'message' => 'Datos JSON inválidos',
            'debug' => json_last_error_msg()
        ], 400);
    }
    
    $correo = trim($data['email'] ?? '');
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        sendResponse(['success' => false, 'message' => 'Correo inválido'], 400);
    }
    
    // Generar código de 6 dígitos
    $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    sendResponse([
        'success' => true, 
        'message' => 'Código enviado',
        'token' => $codigo
    ]);
    
} catch (Exception $e) {
    sendResponse([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ], 500);
}
?>