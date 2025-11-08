<?php
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
    // Incluir BD
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

    // Procesar datos...
    $nombres = trim($data['nombres'] ?? '');
    $apellidos = trim($data['apellidos'] ?? '');
    $email = trim($data['email'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $contrasena = $data['password'] ?? '';

    // Validaciones...
    if (empty($nombres) || empty($apellidos) || empty($email) || empty($telefono) || empty($contrasena)) {
        sendResponse(['success' => false, 'message' => 'Todos los campos son obligatorios'], 400);
    }

    // Conectar BD
    $db = getDBConnection();

    // Verificar email existente
    $stmt = $db->prepare("SELECT id_usuario FROM Usuarios WHERE correo = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Este correo ya está registrado'], 400);
    }

    // Verificar teléfono existente
    $stmt = $db->prepare("SELECT id_usuario FROM Usuarios WHERE telefono = ?");
    $stmt->execute([$telefono]);
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Este número de teléfono ya está registrado'], 400);
    }

    // Generar código paciente
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(codigo_paciente, 5) AS UNSIGNED)) as max_codigo FROM Usuarios WHERE codigo_paciente IS NOT NULL");
    $result = $stmt->fetch();
    $nextNumber = ($result['max_codigo'] ?? 0) + 1;
    $codigoPaciente = 'CLTE' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // Hash contraseña
    $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $db->prepare("
        INSERT INTO Usuarios (codigo_paciente, nombre, apellidos, correo, contrasena, telefono, rol, estado_cuenta) 
        VALUES (?, ?, ?, ?, ?, ?, 'Paciente', 'Activo')
    ");
    
    $result = $stmt->execute([
        $codigoPaciente,
        $nombres,
        $apellidos,
        $email,
        $contrasenaHash,
        $telefono
    ]);

    if ($result) {
        sendResponse(['success' => true, 'message' => 'Registro exitoso']);
    } else {
        sendResponse(['success' => false, 'message' => 'Error al guardar usuario'], 500);
    }

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
