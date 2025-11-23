<?php
session_start();
require_once '../config/database.php';
require_once 'send_email.php'; // Para usar la nueva función

header('Content-Type: application/json');
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Seguridad: Verificar que sea un Odontólogo
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Odontologo') {
    sendResponse(['success' => false, 'message' => 'Acceso denegado.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}

$db = getDBConnection();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $id_cita = $data['id_cita'] ?? 0;
    $nuevo_estado = $data['estado'] ?? ''; // 'Confirmada' o 'Rechazada'

    if (empty($id_cita) || !in_array($nuevo_estado, ['Confirmada', 'Rechazada'])) {
        sendResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
    }

    $db->beginTransaction();
    
    // 2. Regla: Evitar envíos duplicados.
    $sql_check = "SELECT estado_cita FROM citas WHERE id_cita = ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$id_cita]);
    $estado_anterior = $stmt_check->fetch(PDO::FETCH_ASSOC)['estado_cita'];

    if ($estado_anterior === $nuevo_estado) {
        $db->rollBack();
        sendResponse(['success' => false, 'message' => 'La cita ya estaba en ese estado. No se notificó.']);
    }

    // 3. Actualizar el estado de la cita
    // (Tus Triggers de la DB se encargarán de liberar/reservar la franja)
    $sql_update = "UPDATE citas SET estado_cita = ? WHERE id_cita = ?";
    $db->prepare($sql_update)->execute([$nuevo_estado, $id_cita]);

    // 4. Obtener datos para el correo (Nombre, Email, Fecha, Hora)
    $sql_info = "SELECT 
                    u.nombre, u.apellidos, u.correo,
                    c.tipo_servicio,
                    DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha_cita,
                    TIME_FORMAT(f.hora_inicio, '%h:%i %p') as hora_cita
                 FROM citas c
                 JOIN usuarios u ON c.id_paciente = u.id_usuario
                 JOIN franjasdisponibles f ON c.id_franja = f.id_franja
                 WHERE c.id_cita = ?";
    
    $stmt_info = $db->prepare($sql_info);
    $stmt_info->execute([$id_cita]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("No se encontraron datos del paciente para la cita.");
    }

    $patient_name = $info['nombre'] . ' ' . $info['apellidos'];
    $cita_info_email = [
        'servicio' => $info['tipo_servicio'],
        'fecha' => $info['fecha_cita'],
        'hora' => $info['hora_cita']
    ];
    
    // 5. Enviar el correo
    $email_sent = sendAppointmentStatusEmail($info['correo'], $patient_name, $nuevo_estado, $cita_info_email);

    if ($email_sent) {
        // 6. Regla: Registrar la fecha de notificación
        $db->prepare("UPDATE citas SET fecha_notificacion = NOW() WHERE id_cita = ?")->execute([$id_cita]);
        
        // 7. MODIFICACIÓN: Obtener el nuevo conteo de pendientes
        $stmt_stats = $db->prepare("SELECT COUNT(*) as count FROM citas WHERE estado_cita = 'Pendiente'");
        $stmt_stats->execute();
        $new_pending_count = $stmt_stats->fetchColumn();

        $db->commit();
        
        // 8. MODIFICACIÓN: Devolver el nuevo conteo
        sendResponse([
            'success' => true, 
            'message' => 'Se ha notificado al paciente correctamente.',
            'stats' => ['pending' => $new_pending_count] // <-- Devolver nuevo conteo
        ]);
        
    } else {
        $db->rollBack(); 
        sendResponse(['success' => false, 'message' => 'La cita fue actualizada, pero falló el envío del correo. Por favor, reintente.'], 500);
    }
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    sendResponse(['success' => false, 'message' => 'Error de base de datos.', 'debug' => $e->getMessage()], 500);
}
?>