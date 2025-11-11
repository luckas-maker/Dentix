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
        
        // 4. Verificar el estado de la cuenta
        if ($user['estado_cuenta'] === 'Activo') {
            // ✅ Iniciar sesión con nombres consistentes
            session_start();
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellidos'];
            $_SESSION['user_email'] = $user['correo'];
            $_SESSION['user_role'] = $user['rol'];
            
            // Log para debug
            error_log("✅ Sesión creada - Session ID: " . session_id() . ", User ID: " . $_SESSION['user_id']);
            
            sendResponse([
                'success' => true, 
                'message' => 'Inicio de sesión exitoso.',
                'user' => [ 
                    'nombre' => $user['nombre'] . ' ' . $user['apellidos'], 
                    'rol' => $user['rol'] 
                ]
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
    error_log("❌ Error en login: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Error de base de datos.'], 500);
}
?>