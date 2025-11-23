<?php
// ¡ASEGÚRATE DE QUE ESTA ES LA LÍNEA 1, COLUMNA 1! ¡SIN ESPACIOS ANTES!
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// RUTA CORREGIDA: Incluir la conexión PDO y el envío de email
require_once '../config/database.php';
require_once 'send_email.php'; // <--- AÑADIDO para la lógica de 'Pendiente'

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
    // Conectar a BD
    $db = getDBConnection();
    
    // Obtener datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendResponse(['success' => false, 'message' => 'Correo y contraseña son obligatorios'], 400);
    }
    
    // Buscar usuario por correo
    $stmt = $db->prepare("SELECT id_usuario, nombre, apellidos, correo, contrasena, rol, estado_cuenta FROM Usuarios WHERE correo = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC); // Usar FETCH_ASSOC
    
    if (!$usuario || !password_verify($password, $usuario['contrasena'])) {
        sendResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos'], 401);
    }

    // --- LÓGICA DE ESTADO DE CUENTA (MODIFICADA) ---
    
    // 1. Cuenta Bloqueada
    if ($usuario['estado_cuenta'] === 'Bloqueado') {
        sendResponse(['success' => false, 'message' => 'Tu cuenta ha sido bloqueada. Contacta al administrador.'], 403);
    }
    
    // 2. Cuenta Pendiente (Implementar reenvío de enlace)
    if ($usuario['estado_cuenta'] === 'Pendiente') {
        
        $user_id = $usuario['id_usuario'];
        
        // REGLA: Límite de 3 re-envíos en 24 horas
        $limit_sql = "SELECT COUNT(*) AS count FROM tokensvalidacion 
                      WHERE id_usuario = ? 
                      AND tipo = 'ValidacionCorreo' 
                      AND fecha_expiracion > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt_limit = $db->prepare($limit_sql);
        $stmt_limit->execute([$user_id]);
        $limit_count = $stmt_limit->fetch(PDO::FETCH_ASSOC)['count'];
        
        $mensaje_error = 'Tu cuenta está pendiente. ';

        // CORRECCIÓN: Si el límite AÚN NO se alcanza, enviar correo
        if ($limit_count < 3) {
            $token = bin2hex(random_bytes(32)); 
            $fecha_expiracion = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutos

            // Insertar nuevo token (NO borramos los viejos para que el contador funcione)
            $sql_token = "INSERT INTO tokensvalidacion (id_usuario, codigo, tipo, fecha_expiracion) VALUES (?, ?, 'ValidacionCorreo', ?)";
            $db->prepare($sql_token)->execute([$user_id, $token, $fecha_expiracion]);
            
            // Enviar el NUEVO correo (con el enlace)
            // (Asegúrate de que la función 'sendActivationLinkEmail' exista en 'send_email.php')
            sendActivationLinkEmail($usuario['correo'], $token); 
            
            $mensaje_error .= 'Hemos enviado un nuevo enlace de activación a tu correo.';
        } else {
            $mensaje_error .= 'Has alcanzado el límite de reenvíos (3 en 24h).';
        }
        
        sendResponse(['success' => false, 'message' => $mensaje_error], 403);
    }
    
    // 3. Login Exitoso (Cuenta Activa)
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
    sendResponse(['success' => false, 'message' => 'Error de base de datos', 'debug' => $e->getMessage()], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error interno del servidor', 'debug' => $e->getMessage()], 500);
}
// NO HAY ETIQUETA DE CIERRE ?>