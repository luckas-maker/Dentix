<?php
// acciones_admin.php - VERSIÓN CORREGIDA
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    $data = $_POST;
}

$accion = $data['accion'] ?? '';
$id_usuario = $data['id_usuario'] ?? null;

if (empty($accion) || empty($id_usuario)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Datos incompletos: accion e id_usuario son requeridos'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    switch ($accion) {
        case 'marcar_asistencia':
            marcarAsistencia($pdo, $id_usuario);
            break;
            
        case 'marcar_no_asistio':
            marcarNoAsistio($pdo, $id_usuario);
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
            echo json_encode([
                'success' => false, 
                'message' => 'Acción no válida: ' . $accion
            ]);
    }
    
} catch (Exception $e) {
    error_log("Error en acciones_admin.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

function marcarAsistencia($pdo, $id_usuario) {
    $stmt = $pdo->prepare("
        SELECT c.id_cita 
        FROM citas c 
        JOIN franjasdisponibles f ON c.id_franja = f.id_franja 
        WHERE c.id_paciente = ? 
        AND c.estado_cita IN ('Pendiente', 'Confirmada')
        AND f.fecha = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$id_usuario]);
    $cita = $stmt->fetch();
    
    if (!$cita) {
        echo json_encode([
            'success' => false, 
            'message' => 'El paciente no tiene citas para hoy'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE citas SET estado_cita = 'Asistida' WHERE id_cita = ?");
    $stmt->execute([$cita['id_cita']]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Asistencia registrada correctamente'
    ]);
}

function marcarNoAsistio($pdo, $id_usuario) {
    $stmt = $pdo->prepare("
        SELECT c.id_cita 
        FROM citas c 
        JOIN franjasdisponibles f ON c.id_franja = f.id_franja 
        WHERE c.id_paciente = ? 
        AND c.estado_cita IN ('Pendiente', 'Confirmada')
        AND f.fecha = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$id_usuario]);
    $cita = $stmt->fetch();
    
    if (!$cita) {
        echo json_encode([
            'success' => false, 
            'message' => 'El paciente no tiene citas para hoy'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE citas SET estado_cita = 'No Asistio' WHERE id_cita = ?");
    $stmt->execute([$cita['id_cita']]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'No asistencia registrada correctamente'
    ]);
}

function bloquearPaciente($pdo, $id_usuario) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as citas_pendientes 
        FROM citas 
        WHERE id_paciente = ? 
        AND estado_cita IN ('Pendiente', 'Confirmada')
    ");
    $stmt->execute([$id_usuario]);
    $result = $stmt->fetch();
    
    if ($result['citas_pendientes'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'El usuario tiene citas agendadas. Debe reagendar su cita antes de bloquearlo.'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE usuarios SET estado_cuenta = 'Bloqueado' WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cuenta bloqueada correctamente'
    ]);
}

function desbloquearPaciente($pdo, $id_usuario) {
    $stmt = $pdo->prepare("UPDATE usuarios SET estado_cuenta = 'Activo' WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cuenta desbloqueada correctamente'
    ]);
}

// SOLO UNA DEFINICIÓN DE LA FUNCIÓN eliminarPaciente
function eliminarPaciente($pdo, $id_usuario) {
    // Verificar si tiene citas pendientes (más estricto)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_citas 
        FROM citas 
        WHERE id_paciente = ?
    ");
    $stmt->execute([$id_usuario]);
    $result = $stmt->fetch();
    
    if ($result['total_citas'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'No se puede eliminar el paciente porque tiene historial de citas. Use la opción de bloquear en su lugar.'
        ]);
        return;
    }
    
    // Verificar tokens
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_tokens FROM tokensvalidacion WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $tokens = $stmt->fetch();
    
    if ($tokens['total_tokens'] > 0) {
        // Eliminar tokens primero
        $stmt = $pdo->prepare("DELETE FROM tokensvalidacion WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }
    
    // Eliminar paciente
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND rol = 'Paciente'");
    $stmt->execute([$id_usuario]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Paciente eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo eliminar el paciente'
        ]);
    }
}
?>
