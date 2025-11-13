<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// ✅ Verificar autenticación con user_id
if(!isset($_SESSION['user_id'])) {
    error_log("❌ Acceso denegado a reservar_cita.php - No hay user_id en sesión");
    echo json_encode(array(
        'success' => false,
        'message' => 'Usuario no autenticado'
    ));
    exit();
}

try {
    $db = getDBConnection();
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id_franja = $data['id_franja'] ?? '';
    $servicio = $data['servicio'] ?? '';
    $id_paciente = $_SESSION['user_id']; // ✅ Usar user_id
    
    if(empty($id_franja) || empty($servicio)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Datos incompletos'
        ));
        exit();
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Verificar que la franja esté disponible
    $query = "SELECT id_franja, estado, fecha, hora_inicio
              FROM franjasdisponibles 
              WHERE id_franja = ? AND estado = 'Disponible' 
              FOR UPDATE";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id_franja]);
    
    if($stmt->rowCount() == 0) {
        $db->rollBack();
        error_log("❌ Intento de reservar franja no disponible: $id_franja");
        echo json_encode(array(
            'success' => false,
            'message' => 'El horario ya no está disponible'
        ));
        exit();
    }
    
    $franja = $stmt->fetch();
    
    // ✅ Crear la cita (el trigger se encarga de actualizar el estado de la franja)
    $query = "INSERT INTO citas (id_paciente, id_franja, tipo_servicio, estado_cita) 
              VALUES (?, ?, ?, 'Pendiente')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id_paciente, $id_franja, $servicio]);
    
    // Confirmar transacción
    $db->commit();
    
    error_log("✅ Cita creada exitosamente - Usuario: $id_paciente, Franja: $id_franja, Servicio: $servicio");
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Cita reservada exitosamente. Estado: Pendiente de confirmación',
        'cita' => array(
            'fecha' => $franja['fecha'],
            'hora' => $franja['hora_inicio'],
            'servicio' => $servicio,
            'estado' => 'Pendiente'
        )
    ));
    
} catch(Exception $e) {
    if(isset($db)) {
        $db->rollBack();
    }
    error_log("❌ Error en reservar_cita.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error al reservar cita: ' . $e->getMessage()
    ));
}
?>