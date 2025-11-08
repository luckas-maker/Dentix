<?php
// RUTA CORREGIDA: Incluir dependencias (subir un nivel)
require_once '../config/database.php'; // Tu conexión PDO
require_once 'send_email.php';       // La función de envío de correo

// --- Configuración de respuesta JSON ---
header('Content-Type: application/json');
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}
// --- Fin de Configuración ---

$db = getDBConnection();

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $nombres = trim($data["nombres"] ?? '');
    $apellidos = trim($data["apellidos"] ?? '');
    $correo = trim($data["email"] ?? '');
    $contrasena = $data["password"] ?? null;
    $telefono = $data["telefono"] ?? null;
    $rol = "Paciente";
    $estado_inicial = "Pendiente"; // El usuario se crea como Pendiente
    
    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($contrasena) || empty($telefono)) {
        sendResponse(['success' => false, 'message' => 'Todos los campos son obligatorios.'], 400);
    }
    
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Verificar si el correo ya existe
    $sqlCheck = "SELECT id_usuario FROM Usuarios WHERE correo = ?";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->execute([$correo]);
    if ($stmtCheck->fetch()) {
        sendResponse(['success' => false, 'message' => 'El correo electrónico ya está registrado.'], 409);
    }
    $stmtCheck->closeCursor();

    // Iniciar Transacción (CRÍTICO)
    $db->beginTransaction();

    // 1. Insertar el usuario (dejando codigo_paciente NULL temporalmente)
    $sqlInsert = "INSERT INTO Usuarios (nombre, apellidos, correo, contrasena, telefono, rol, estado_cuenta)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->execute([$nombres, $apellidos, $correo, $hash, $telefono, $rol, $estado_inicial]);
    
    // 2. Obtener el ID del usuario recién creado
    $user_id = $db->lastInsertId();

    // 3. Generar y actualizar el codigo_paciente (CLTE + ID)
    $codigo_paciente = 'CLTE' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
    $sqlUpdate = "UPDATE Usuarios SET codigo_paciente = ? WHERE id_usuario = ?";
    $db->prepare($sqlUpdate)->execute([$codigo_paciente, $user_id]);

    // 4. Generar código de 6 dígitos
    $codigo_token = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $fecha_expiracion = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24 horas

    // 5. Insertar en tokensvalidacion
    $sql_token = "INSERT INTO tokensvalidacion (id_usuario, codigo, tipo, fecha_expiracion) VALUES (?, ?, 'ValidacionCorreo', ?)";
    $stmt_token = $db->prepare($sql_token);
    $stmt_token->execute([$user_id, $codigo_token, $fecha_expiracion]);
    
    // 6. Confirmar la transacción
    $db->commit();
    
    // 7. Enviar el Correo Electrónico
    if (sendVerificationEmail($correo, $codigo_token)) {
        sendResponse(['success' => true, 'message' => 'Registro exitoso.', 'email' => $correo]);
    } else {
        // AÚN SI EL CORREO FALLA, EL REGISTRO FUE EXITOSO (PENDIENTE)
        sendResponse(['success' => true, 'message' => 'Registro exitoso, pero falló el envío del código.', 'email' => $correo]);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    sendResponse(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()], 500);
}
?>