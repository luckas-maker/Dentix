<?php
session_start();
header('Content-Type: application/json');

// RUTA CORREGIDA (subir un nivel)
require_once '../config/database.php';
// AÑADIDO: Incluir la función de envío de correo
require_once 'send_email.php';

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
        echo json_encode(array('success' => false, 'message' => 'Datos incompletos'));
        exit();
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Verificar que la franja esté disponible (con bloqueo para evitar duplicados)
    $query_franja = "SELECT id_franja, estado, fecha, hora_inicio
                     FROM franjasdisponibles 
                     WHERE id_franja = ? AND estado = 'Disponible' FOR UPDATE";
    $stmt_franja = $db->prepare($query_franja);
    $stmt_franja->execute([$id_franja]);
    
    $franja = $stmt_franja->fetch(PDO::FETCH_ASSOC);
    
    if(!$franja) {
        $db->rollBack();
        error_log("❌ Intento de reservar franja no disponible: $id_franja");
        echo json_encode(array(
            'success' => false,
            'message' => 'Lo sentimos, ese horario ya no está disponible.'
        ));
        exit();
    }
    
    // ✅ Crear la cita (El Trigger [cite: uploaded:Oficial.sql] actualiza la franja a 'NoDisponible')
    $query_cita = "INSERT INTO citas (id_paciente, id_franja, tipo_servicio, estado_cita) 
                   VALUES (?, ?, ?, 'Pendiente')";
    
    $stmt_cita = $db->prepare($query_cita);
    $stmt_cita->execute([$id_paciente, $id_franja, $servicio]);
    
    // --- INICIO DE MODIFICACIÓN (Módulo 5.3) ---
    
    // 1. Obtener datos del paciente para el correo
    $stmt_paciente = $db->prepare("SELECT nombre, apellidos, correo FROM Usuarios WHERE id_usuario = ?");
    $stmt_paciente->execute([$id_paciente]);
    $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);
    $paciente_nombre = $paciente['nombre'] . ' ' . $paciente['apellidos'];
    
    // 2. Enviar notificación al odontólogo
    $email_sent = sendNewAppointmentNotificationEmail(
        $paciente_nombre, 
        $paciente['correo'], 
        $franja['fecha'], 
        $franja['hora_inicio']
    );

    // 3. Aplicar Regla de Negocio: Si el correo falla, la cita falla.
    if ($email_sent) {
        // Éxito: Confirmar transacción
        $db->commit();
        
        error_log("✅ Cita creada y notificada - Usuario: $id_paciente, Franja: $id_franja");
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Cita reservada exitosamente. Se ha notificado al odontólogo.',
            'cita' => array(
                'fecha' => $franja['fecha'],
                'hora' => substr($franja['hora_inicio'], 0, 5),
                'servicio' => $servicio,
                'estado' => 'Pendiente'
            )
        ));
    } else {
        // Fallo: Revertir la reserva de la cita
        $db->rollBack();
        
        error_log("❌ Fallo al reservar cita - Usuario: $id_paciente, Franja: $id_franja. EL CORREO AL ODONTÓLOGO FALLÓ.");
        
        echo json_encode(array(
            'success' => false,
            // Criterio de Aceptación: Mensaje de fallo de red
            'message' => 'No es posible agendar su cita en este momento (Error de notificación). Favor de intentar más tarde.'
        ));
    }
    // --- FIN DE MODIFICACIÓN ---

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("❌ Error PDO en reservar_cita: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error de base de datos al reservar la cita.'
    ));
}
?>