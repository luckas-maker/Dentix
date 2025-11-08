<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();

    // Ejecutar consulta simple para probar comunicación
    $stmt = $db->query("SELECT NOW() as current_time");
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos',
        'server_time' => $result['current_time']
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la conexión a la base de datos',
        'error' => $e->getMessage()
    ]);
}
?>