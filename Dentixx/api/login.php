<?php
// RUTA CORREGIDA: Incluir la conexión PDO
require_once '../config/database.php';

header('Content-Type: application/json');
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}

// 1. Obtener la conexión PDO y los datos
$db = getDBConnection();
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    sendResponse(['success' => false, 'message' => 'Correo y contraseña son requeridos.'], 400);
}

try {
    // 2. Buscar al usuario y su estado
    $sql = "SELECT id_usuario, nombre, apellidos, correo, contrasena, rol, estado_cuenta 
            FROM Usuarios WHERE correo = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Verificar si el usuario existe y si la contraseña es correcta
    if ($user && password_verify($password, $user['contrasena'])) {
        
        // 4. (NUEVA REGLA DE SEGURIDAD) Verificar el estado de la cuenta
        if ($user['estado_cuenta'] === 'Activo') {
            // Éxito: Iniciar sesión
            session_start();
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            
            sendResponse([
                'success' => true, 
                'message' => 'Inicio de sesión exitoso.',
                'user' => [ 'nombre' => $user['nombre'] . ' ' . $user['apellidos'], 'rol' => $user['rol'] ]
            ]);

        } elseif ($user['estado_cuenta'] === 'Pendiente') {
            sendResponse(['success' => false, 'message' => 'Tu cuenta está pendiente. Por favor, verifica tu correo electrónico.'], 403);
        
        } elseif ($user['estado_cuenta'] === 'Bloqueado') {
            sendResponse(['success' => false, 'message' => 'Tu cuenta ha sido bloqueada. Contacta a soporte.'], 403);
        }

    } else {
        // Usuario no encontrado o contraseña incorrecta
        sendResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos.'], 401);
    }

} catch (PDOException $e) {
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>