<?php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';
$message_type = 'error';
$message = 'Enlace inválido o no proporcionado.';

if (!empty($token)) {
    try {
        $db = getDBConnection();
        
        // 1. Buscar el token y verificar que no haya expirado
        $sql = "SELECT id_usuario, fecha_expiracion FROM tokensvalidacion 
                WHERE codigo = ? AND tipo = 'ValidacionCorreo' AND fecha_expiracion > NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute([$token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_data) {
            $user_id = $token_data['id_usuario'];

            // 2. Activar la cuenta
            $db->beginTransaction();
            $sql_activate = "UPDATE Usuarios SET estado_cuenta = 'Activo' WHERE id_usuario = ?";
            $db->prepare($sql_activate)->execute([$user_id]);
            
            // 3. Eliminar el token
            $db->prepare("DELETE FROM tokensvalidacion WHERE codigo = ?")->execute([$token]);
            $db->commit();
            
            $message_type = 'success';
            $message = '¡Cuenta activada! Ya puedes iniciar sesión.';
            
        } else {
            // El token no se encontró o ya expiró
            $message = 'Enlace inválido o expirado. Intenta iniciar sesión para recibir uno nuevo.';
        }
        
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        $message = 'Error de base de datos durante la activación.';
    }
}

// 4. Redirigir de vuelta al login con un mensaje
// RUTA CORREGIDA: Subir de /api/ a /Dentixx/ y bajar a MODULO1/HTML/
$login_url = '../MODULO1/HTML/iniciosesion.php';

if ($message_type === 'success') {
    header("Location: $login_url?activation=success");
} else {
    header("Location: $login_url?activation=error&msg=" . urlencode($message));
}
exit;
?>