<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once 'send_email.php';

// 1. Seguridad: Solo Odontólogo
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Odontologo') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

try {
    $db = getDBConnection();
    $data = json_decode(file_get_contents("php://input"), true);
    $id_cita = $data['id_cita'] ?? 0;

    if (empty($id_cita)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido.']);
        exit();
    }

    $db->beginTransaction();

    // 2. Obtener datos del paciente
    $sql_info = "SELECT c.id_paciente, u.nombre, u.apellidos, u.correo, f.fecha, f.hora_inicio
                 FROM citas c
                 JOIN usuarios u ON c.id_paciente = u.id_usuario
                 JOIN franjasdisponibles f ON c.id_franja = f.id_franja
                 WHERE c.id_cita = ?";
    $stmt_info = $db->prepare($sql_info);
    $stmt_info->execute([$id_cita]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) { throw new Exception("Cita no encontrada."); }

    // 3. Actualizar estado a 'No Asistio'
    $query_update = "UPDATE citas SET estado_cita = 'No Asistio' WHERE id_cita = ?";
    $db->prepare($query_update)->execute([$id_cita]);

    // 4. Enviar correo de inasistencia
    // (Usamos sendMissedAppointmentEmail si existe, o la genérica si no)
    $paciente_nombre = $info['nombre'] . ' ' . $info['apellidos'];
    $email_enviado = false;

    if (function_exists('sendMissedAppointmentEmail')) {
        $email_enviado = sendMissedAppointmentEmail($info['correo'], $paciente_nombre, $info['fecha'], $info['hora_inicio']);
    } else {
        // Fallback a la función de cancelación (avisando que no asistió en el motivo implícitamente)
        $email_enviado = sendCancellationToPatientEmail($info['correo'], $paciente_nombre, $info['fecha'], $info['hora_inicio']);
    }

    if ($email_enviado) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Inasistencia registrada y notificación enviada.']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo. Intente más tarde.']);
    }

} catch(Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>