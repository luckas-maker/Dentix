<?php
session_start();
header('Content-Type: application/json');

// 1. Incluir archivos y verificar rutas
$path_config = '../config/database.php';
$path_email = 'send_email.php';

if (!file_exists($path_config) || !file_exists($path_email)) {
    echo json_encode(['success' => false, 'message' => 'Error interno: Archivos de configuración no encontrados.']);
    exit();
}

require_once $path_config;
require_once $path_email;

// 2. Verificar sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

try {
    $db = getDBConnection();
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id_cita = $data['id_cita'] ?? 0;
    $motivo = $data['motivo_cancelacion'] ?? 'Cancelada por el odontólogo'; // Motivo por defecto si viene vacío
    
    $rol_usuario = $_SESSION['user_role'];
    $id_usuario_sesion = $_SESSION['user_id'];
    
    if (empty($id_cita)) {
        echo json_encode(['success' => false, 'message' => 'ID de cita requerido.']);
        exit();
    }
    
    $db->beginTransaction();

    // 3. Obtener datos de la cita y del paciente asociado
    // NOTA: Necesitamos los datos del PACIENTE (u.correo) para enviarle el aviso,
    // incluso si quien cancela es el Odontólogo.
    $sql_info = "SELECT 
                    c.id_paciente, c.estado_cita,
                    u.nombre, u.apellidos, u.correo,
                    f.fecha, f.hora_inicio
                 FROM citas c
                 JOIN usuarios u ON c.id_paciente = u.id_usuario
                 JOIN franjasdisponibles f ON c.id_franja = f.id_franja
                 WHERE c.id_cita = ?";
    
    $stmt_info = $db->prepare($sql_info);
    $stmt_info->execute([$id_cita]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("Cita no encontrada.");
    }

    // 4. Validaciones de Permisos
    if ($rol_usuario === 'Paciente') {
        // El paciente solo puede cancelar sus propias citas
        if ($info['id_paciente'] != $id_usuario_sesion) {
            throw new Exception("No tienes permiso para cancelar esta cita.");
        }
    }
    // El Odontólogo puede cancelar cualquier cita, por lo que no requiere validación de ID.

    // Validar estado de la cita
    if (!in_array($info['estado_cita'], ['Pendiente', 'Confirmada'])) {
        throw new Exception("Esta cita ya no se puede cancelar (Estado actual: " . $info['estado_cita'] . ").");
    }

    // 5. Actualizar la cita en Base de Datos
    $query_update = "UPDATE citas SET estado_cita = 'Cancelada', motivo_cancelacion = ? WHERE id_cita = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$motivo, $id_cita]);

    // 6. Enviar Notificación (Lógica Bidireccional)
    $email_enviado = false;
    $paciente_nombre = $info['nombre'] . ' ' . $info['apellidos'];

    if ($rol_usuario === 'Odontologo') {
        // CASO 1: Odontólogo cancela -> Se envía correo al PACIENTE
        // Se usa el correo del paciente obtenido en la consulta ($info['correo'])
        if (function_exists('sendCancellationToPatientEmail')) {
            $email_enviado = sendCancellationToPatientEmail(
                $info['correo'], 
                $paciente_nombre, 
                $info['fecha'], 
                $info['hora_inicio']
            );
        } else {
             throw new Exception("Función de correo no encontrada.");
        }
    } else {
        // CASO 2: Paciente cancela -> Se envía correo al ODONTÓLOGO (kevineliuth1234@gmail.com)
        if (function_exists('sendCancellationToDentistEmail')) {
            $email_enviado = sendCancellationToDentistEmail(
                $paciente_nombre,
                $info['correo'],
                $info['fecha'],
                $info['hora_inicio'],
                $motivo
            );
        } else {
            throw new Exception("Función de correo no encontrada.");
        }
    }

    // 7. Confirmar Transacción
    if ($email_enviado) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '¡La cita ha sido cancelada exitosamente! El paciente ha sido notificado por correo electrónico. 😊'
        ]);
    } else {
        $db->rollBack();
        // Mensaje amigable pero logueando el error real
        error_log("Fallo al enviar email de cancelación para Cita ID: $id_cita");
        echo json_encode(['success' => false, 'message' => 'No se pudo enviar la notificación. Intente más tarde.']);
    }

} catch(Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Error en cancelar_cita.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>