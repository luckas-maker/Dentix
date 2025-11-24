<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// ✅ Verificar autenticación
if(!isset($_SESSION['user_id'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Usuario no autenticado'
    ));
    exit();
}

try {
    $db = getDBConnection();
    $id_paciente = $_SESSION['user_id'];
    
    // Obtener todas las citas del paciente con información de la franja
    $query = "SELECT 
                c.id_cita,
                c.tipo_servicio,
                c.estado_cita,
                c.fecha_creacion,
                c.motivo_cancelacion,
                DATE_FORMAT(f.fecha, '%Y-%m-%d') as fecha,
                TIME_FORMAT(f.hora_inicio, '%H:%i') as hora
              FROM citas c
              INNER JOIN franjasdisponibles f ON c.id_franja = f.id_franja
              WHERE c.id_paciente = ?
              ORDER BY f.fecha DESC, f.hora_inicio DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id_paciente]);
    
    $citas = array();
    while($row = $stmt->fetch()) {
        $citas[] = array(
            'id_cita' => $row['id_cita'],
            'fecha' => $row['fecha'], // Ya viene formateado como string YYYY-MM-DD
            'hora' => $row['hora'],
            'servicio' => $row['tipo_servicio'],
            'estado' => $row['estado_cita'],
            'fecha_creacion' => $row['fecha_creacion'],
            'motivo_cancelacion' => $row['motivo_cancelacion']
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'citas' => $citas
    ));
    
} catch(Exception $e) {
    error_log("Error en obtener_mis_citas.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error al obtener citas: ' . $e->getMessage()
    ));
}
?>