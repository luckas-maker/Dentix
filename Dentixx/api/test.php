<?php
header('Content-Type: application/json');
// Probar conexión a BD
try {
    require_once '../config/database.php';
    $db = getDBConnection();
    echo json_encode(['status' => 'success', 'message' => 'Conexión a BD exitosa']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error BD: ' . $e->getMessage()]);
}
?>
