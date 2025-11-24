<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $db = getDBConnection();
    
    // Obtener mes y año de los parámetros
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
    
    // Calcular primer y último día del mes
    $primer_dia = date('Y-m-01', strtotime("$anio-$mes-01"));
    $ultimo_dia = date('Y-m-t', strtotime("$anio-$mes-01"));
    
    // ✅ Solo mostrar franjas con estado 'Disponible'
    $query = "SELECT id_franja, fecha, hora_inicio 
              FROM franjasdisponibles 
              WHERE fecha BETWEEN ? AND ? 
              AND estado = 'Disponible'
              ORDER BY fecha, hora_inicio";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$primer_dia, $ultimo_dia]);
    
    $horarios = array();
    while($row = $stmt->fetch()) {
        $fecha = $row['fecha'];
        if(!isset($horarios[$fecha])) {
            $horarios[$fecha] = array();
        }
        $horarios[$fecha][] = array(
            'id_franja' => $row['id_franja'],
            'hora' => substr($row['hora_inicio'], 0, 5) // Formato HH:MM
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'horarios' => $horarios
    ));
    
} catch(Exception $e) {
    error_log("Error en obtener_horarios.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error al obtener horarios: ' . $e->getMessage()
    ));
}
?>