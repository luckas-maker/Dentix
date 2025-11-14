<?php
// Usar ruta relativa correcta para incluir database.php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Permitir CORS si es necesario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si json_decode falla, intentar con $_POST
if ($data === null && !empty($_POST)) {
    $data = $_POST;
}

$accion = $data['accion'] ?? '';
$id_usuario = $data['id_usuario'] ?? null;

// Validar datos requeridos
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
        case 'registrar_asistencia':
            registrarAsistencia($pdo, $id_usuario);
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

function registrarAsistencia($pdo, $id_usuario) {
    // Verificar si tiene citas para hoy
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as tiene_citas_hoy 
        FROM citas c 
        JOIN franjasdisponibles f ON c.id_franja = f.id_franja 
        WHERE c.id_paciente = ? 
        AND c.estado_cita IN ('Pendiente', 'Confirmada')
        AND f.fecha = CURDATE()
    ");
    $stmt->execute([$id_usuario]);
    $result = $stmt->fetch();
    
    if ($result['tiene_citas_hoy'] == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'El paciente no tiene citas para hoy'
        ]);
        return;
    }
    
    // Actualizar faltas consecutivas a 0
    $stmt = $pdo->prepare("UPDATE usuarios SET faltas_consecutivas = 0 WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Asistencia registrada correctamente'
    ]);
}

function bloquearPaciente($pdo, $id_usuario) {
    // Verificar si tiene citas pendientes
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
    
    // Bloquear cuenta
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

function eliminarPaciente($pdo, $id_usuario) {
    // Verificar si tiene citas pendientes
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
            'message' => 'El usuario tiene citas agendadas. Debe cancelarlas antes de eliminarlo.'
        ]);
        return;
    }
    
    // Eliminar paciente (con CASCADE se eliminarán tokens y citas relacionadas)
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