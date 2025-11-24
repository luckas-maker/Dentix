<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';
require_once '../../api/send_email.php';

date_default_timezone_set('America/Mexico_City');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$accion = $data['accion'] ?? '';
$id_usuario = $data['id_usuario'] ?? null;

try {
    $pdo = getDBConnection();
    $pdo->exec("SET time_zone = '-06:00';");

    switch ($accion) {
        // NUEVA ACCIÓN: Obtener lista para el modal
        case 'obtener_citas_usuario':
            obtenerCitasUsuario($pdo, $id_usuario);
            break;
            
        case 'marcar_asistencia_cita': // Acción específica por cita
            marcarAsistenciaCita($pdo, $data['id_cita']);
            break;

        case 'marcar_no_asistio_cita': // Acción específica por cita
            marcarNoAsistioCita($pdo, $data['id_cita']);
            break;

        case 'bloquear_paciente':
            bloquearPaciente($pdo, $id_usuario);
            break;

        case 'desbloquear_paciente':
            desbloquearPaciente($pdo, $id_usuario);
            break;

        case 'eliminar_paciente':
            eliminarPaciente($pdo, $id_usuario);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// --- FUNCIONES ---

function obtenerCitasUsuario($pdo, $id_usuario) {
    // Buscar todas las citas Confirmadas (futuras o pasadas que no se han cerrado)
    $stmt = $pdo->prepare("
        SELECT c.id_cita, c.tipo_servicio, f.fecha, f.hora_inicio 
        FROM citas c 
        JOIN franjasdisponibles f ON c.id_franja = f.id_franja 
        WHERE c.id_paciente = ? 
        AND c.estado_cita = 'Confirmada'
        ORDER BY f.fecha DESC, f.hora_inicio ASC
    ");
    $stmt->execute([$id_usuario]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'citas' => $citas]);
}

function marcarAsistenciaCita($pdo, $id_cita) {
    $stmt = $pdo->prepare("UPDATE citas SET estado_cita = 'Asistida' WHERE id_cita = ?");
    $stmt->execute([$id_cita]);
    echo json_encode(['success' => true, 'message' => 'Asistencia registrada.']);
}

function marcarNoAsistioCita($pdo, $id_cita) {
    $pdo->beginTransaction();
    try {
        // Obtener datos para el correo
        $stmt = $pdo->prepare("
            SELECT u.nombre, u.apellidos, u.correo, f.fecha, f.hora_inicio
            FROM citas c
            JOIN usuarios u ON c.id_paciente = u.id_usuario
            JOIN franjasdisponibles f ON c.id_franja = f.id_franja
            WHERE c.id_cita = ?
        ");
        $stmt->execute([$id_cita]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Actualizar estado
        $pdo->prepare("UPDATE citas SET estado_cita = 'No Asistio' WHERE id_cita = ?")->execute([$id_cita]);
        
        // Enviar correo
        $nombre = $info['nombre'] . ' ' . $info['apellidos'];
        if (function_exists('sendMissedAppointmentEmail')) {
            sendMissedAppointmentEmail($info['correo'], $nombre, $info['fecha'], $info['hora_inicio']);
        } else {
            sendCancellationToPatientEmail($info['correo'], $nombre, $info['fecha'], $info['hora_inicio']);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Falta registrada y correo enviado.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function bloquearPaciente($pdo, $id_usuario) {
    $pdo->beginTransaction();
    try {
        // 1. Obtener datos para correo
        $stmtUser = $pdo->prepare("SELECT nombre, apellidos, correo FROM usuarios WHERE id_usuario = ?");
        $stmtUser->execute([$id_usuario]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        // 2. Cancelar citas pendientes/confirmadas
        $stmtCancel = $pdo->prepare("
            UPDATE citas 
            SET estado_cita = 'Cancelada', motivo_cancelacion = 'Cuenta Bloqueada por Administración' 
            WHERE id_paciente = ? AND estado_cita IN ('Pendiente', 'Confirmada')
        ");
        $stmtCancel->execute([$id_usuario]);
        $citasCanceladas = $stmtCancel->rowCount() > 0; // True si se canceló algo

        // 3. Bloquear usuario
        $stmtBlock = $pdo->prepare("UPDATE usuarios SET estado_cuenta = 'Bloqueado' WHERE id_usuario = ?");
        $stmtBlock->execute([$id_usuario]);

        // 4. Enviar correo
        $nombre = $user['nombre'] . ' ' . $user['apellidos'];
        sendAccountBlockedEmail($user['correo'], $nombre, $citasCanceladas);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cuenta bloqueada. ' . ($citasCanceladas ? 'Se cancelaron sus citas pendientes.' : '')]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function desbloquearPaciente($pdo, $id_usuario) {
    // (Igual que antes)
    $stmt = $pdo->prepare("UPDATE usuarios SET estado_cuenta = 'Activo' WHERE id_usuario = ?");
    if ($stmt->execute([$id_usuario])) {
        $stmtUser = $pdo->prepare("SELECT nombre, apellidos, correo FROM usuarios WHERE id_usuario = ?");
        $stmtUser->execute([$id_usuario]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        sendAccountUnblockedEmail($user['correo'], $user['nombre'] . ' ' . $user['apellidos']);
        echo json_encode(['success' => true, 'message' => 'Cuenta desbloqueada.']);
    }
}

function eliminarPaciente($pdo, $id_usuario) {
    // (Igual que antes, pero ahora si tiene citas, no se borra. 
    // El admin debe bloquear primero para cancelar citas automáticas si quiere borrar)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as futuras FROM citas c JOIN franjasdisponibles f ON c.id_franja = f.id_franja WHERE c.id_paciente = ? AND f.fecha >= CURDATE() AND c.estado_cita IN ('Pendiente', 'Confirmada')");
        $stmt->execute([$id_usuario]);
        if ($stmt->fetch()['futuras'] > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'El paciente tiene citas activas. Bloqueé la cuenta primero para cancelarlas automáticamente.']);
            return;
        }
        $pdo->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ?")->execute([$id_usuario]);
        $pdo->prepare("DELETE FROM citas WHERE id_paciente = ?")->execute([$id_usuario]);
        $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND rol = 'Paciente'")->execute([$id_usuario]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Paciente eliminado.']);
    } catch (Exception $e) {
        $pdo->rollBack(); throw $e;
    }
}
?>