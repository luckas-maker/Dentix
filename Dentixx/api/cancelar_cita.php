<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'No autenticado'));
    exit();
}

try {
    $db = getDBConnection();
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id_cita = $data['id_cita'] ?? '';
    $motivo_cancelacion = $data['motivo_cancelacion'] ?? '';
    $id_paciente = $_SESSION['user_id'];
    
    if(empty($id_cita) || empty($motivo_cancelacion)) {
        echo json_encode(array('success' => false, 'message' => 'Datos incompletos'));
        exit();
    }
    
    // Verificar que la cita pertenece al usuario y está pendiente
    $query = "UPDATE citas 
              SET estado_cita = 'Cancelada', motivo_cancelacion = ? 
              WHERE id_cita = ? AND id_paciente = ? AND estado_cita = 'Pendiente'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$motivo_cancelacion, $id_cita, $id_paciente]);
    
    if($stmt->rowCount() > 0) {
        error_log("✅ Cita cancelada - ID: $id_cita, Motivo: $motivo_cancelacion");
        echo json_encode(array('success' => true, 'message' => 'Cita cancelada exitosamente'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'No se puede cancelar esta cita'));
    }
} catch(Exception $e) {
    error_log("❌ Error al cancelar cita: " . $e->getMessage());
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}
?>