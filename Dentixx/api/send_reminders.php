<?php
// Este script debe ser ejecutado por un Cron Job (Tarea Programada)
// Se recomienda ejecutarlo cada hora.

require_once '../config/database.php';
require_once 'send_email.php';

// Asegurarse de que la zona horaria sea correcta (debe estar en config/database.php)
// date_default_timezone_set('America/Mexico_City'); 

echo "--- Iniciando Script de Recordatorios (" . date('Y-m-d H:i:s') . ") ---<br>";

try {
    $db = getDBConnection();
    
    // --- INICIO DE MODIFICACIÓN (Devuelto a 24 horas) ---
    
    // Regla: 24 horas antes.
    // Buscamos citas que estén entre 24 y 25 horas desde AHORA.
    // (Asumiendo que el Cron Job se ejecuta 1 vez por hora)
    
    $limite_inferior = "DATE_ADD(NOW(), INTERVAL 24 HOUR)";
    $limite_superior = "DATE_ADD(NOW(), INTERVAL 25 HOUR)";

    // --- FIN DE MODIFICACIÓN ---

    // Consulta SQL que cumple todas las reglas:
    $sql = "
        SELECT 
            c.id_cita,
            u.nombre, u.apellidos, u.correo,
            f.fecha, f.hora_inicio
        FROM citas c
        JOIN usuarios u ON c.id_paciente = u.id_usuario
        JOIN franjasdisponibles f ON c.id_franja = f.id_franja
        WHERE 
            c.estado_cita = 'Confirmada'        -- Regla: Solo citas 'Confirmada'
        AND c.recordatorio_enviado = 0          -- Regla: No enviar duplicados
        AND u.recibir_recordatorios = 1         -- Regla: Paciente aceptó recibir
        AND CONCAT(f.fecha, ' ', f.hora_inicio) -- Hora real de la cita
            BETWEEN {$limite_inferior} AND {$limite_superior}
    ";
    
    $stmt = $db->query($sql);
    $citas_a_notificar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($citas_a_notificar)) {
        echo "No hay recordatorios para enviar en esta ventana (24h-25h desde ahora).<br>";
        exit;
    }

    echo "Se encontraron " . count($citas_a_notificar) . " citas para notificar:<br>";
    
    $enviados = 0;
    $fallidos = 0;

    foreach ($citas_a_notificar as $cita) {
        $patient_name = $cita['nombre'] . ' ' . $cita['apellidos'];
        
        $email_sent = sendAppointmentReminderEmail(
            $cita['correo'], 
            $patient_name, 
            $cita['fecha'], 
            $cita['hora_inicio']
        );

        if ($email_sent) {
            $stmt_update = $db->prepare("UPDATE citas SET recordatorio_enviado = 1 WHERE id_cita = ?");
            $stmt_update->execute([$cita['id_cita']]);
            echo "✅ Recordatorio enviado a " . htmlspecialchars($cita['correo']) . " (Cita ID " . $cita['id_cita'] . ")<br>";
            $enviados++;
        } else {
            echo "❌ Fallo al enviar recordatorio a " . htmlspecialchars($cita['correo']) . " (Cita ID " . $cita['id_cita'] . ")<br>";
            $fallidos++;
        }
    }
    
    echo "<br>--- Proceso completado: {$enviados} enviados, {$fallidos} fallidos. ---";

} catch (Exception $e) {
    echo "❌ ERROR FATAL DEL SCRIPT: " . $e->getMessage();
    error_log("Error fatal en send_reminders.php: " . $e->getMessage());
}


/*
usas el "Programador de tareas" (Task Scheduler):

    Abre el Programador de Tareas de Windows.

    Crea una "Tarea Básica".

    Configúrala para que se repita "Diariamente" y que se repita "cada 1 hora".

    Como "Acción", selecciona "Iniciar un programa".

    En "Programa/script", busca la ruta a tu php.exe (ej. C:\xampp\php\php.exe).

    En "Agregar argumentos", pones la ruta a tu script: -f "C:\xampp\htdocs\Dentixx\api\send_reminders.php".

    PARA CORRER MANUALMENTE SE COLOCA LO SIGUIENTE: http://localhost/Dentixx/api/send_reminders.php Y SE DEBE ACTUALIZAR
*/

?>