<?php
// Usar ruta relativa correcta para incluir database.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Obtener solo pacientes (no odontólogos)
    $stmt = $pdo->prepare("
        SELECT 
            u.id_usuario,
            u.codigo_paciente,
            u.nombre,
            u.apellidos,
            u.correo,
            u.telefono,
            u.foto_perfil,
            u.estado_cuenta,
            u.faltas_consecutivas,
            u.ultima_cita_fecha,
            u.ultima_cita_motivo,
            u.fecha_registro,
            COUNT(c.id_cita) as citas_pendientes
        FROM usuarios u
        LEFT JOIN citas c ON u.id_usuario = c.id_paciente AND c.estado_cita IN ('Pendiente', 'Confirmada')
        WHERE u.rol = 'Paciente'
        GROUP BY u.id_usuario
        ORDER BY u.fecha_registro DESC
    ");
    
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pacientes' => $pacientes
    ]);
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en obtener_pacientes.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener pacientes: ' . $e->getMessage()
    ]);
}
?>