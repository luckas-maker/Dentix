<?php
session_start(); // <-- Asegúrate de que la sesión esté iniciada

// --- INICIO DE MODIFICACIÓN (SEGURIDAD) ---

// 1. Verificar si hay un user_id
if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión, expulsar al login
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}

// 2. NUEVA REGLA DE SEGURIDAD: Verificar el ROL
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Odontologo') {
    
    // Si NO es Odontologo (es Paciente o error), expulsar.
    // Lo enviamos a su propio dashboard para que no vea un error.
    header('Location: ../../MODULO2/HTML/dashboardclient.php');
    exit;
}

// --- FIN DE MODIFICACIÓN ---

// RUTA CORREGIDA: Subir dos niveles
include '../../config/database.php';

//funcion para obtener citas solicitadas o "pendiente"
function getPendingAppointments() {
    $pdo = getDBConnection();
    $appointments = [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id_cita,
                c.id_paciente,
                u.nombre as patientName,
                u.telefono as phone,
                u.correo as email,
                c.tipo_servicio as service,
                f.fecha as preferredDate,
                f.hora_inicio as preferredTime,
                c.fecha_creacion as requestDate,
                c.estado_cita as status
            FROM citas c
            LEFT JOIN usuarios u ON c.id_paciente = u.id_usuario
            LEFT JOIN franjasdisponibles f ON c.id_franja = f.id_franja
            WHERE c.estado_cita = 'Pendiente'
            ORDER BY c.fecha_creacion DESC
        ");
        $stmt->execute();
        
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting pending appointments: " . $e->getMessage());
    }
    
    return $appointments;
}

// funcion para obtener en tiempo
function getRealTimeStats() {
    $pdo = getDBConnection();
    $stats = ['accepted' => 0, 'rejected' => 0, 'pending' => 0];
    
    try {
        // contar citas pendientes 
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM citas WHERE estado_cita = 'Pendiente'");
        $stmt->execute();
        $stats['pending'] = $stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log("Error getting real-time stats: " . $e->getMessage());
    }
    
    return $stats;
}

// MODIFICACIÓN: Ya no necesitamos la lógica de 'action' aquí
// El JavaScript ahora llamará a la API externa 'actualizar_estado_cita.php'

// obtener información inicial
$pendingAppointments = getPendingAppointments();
$currentStats = getRealTimeStats();

// función para el formato de fecha
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Citas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 font-sans">
    <div class="min-h-full">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center mb-4 sm:mb-0">
                        <div class="text-3xl mr-4">🦷</div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Solicitudes de Citas Pendientes</h1>
                            <p class="text-gray-600">Revisa y gestiona las nuevas solicitudes de citas de tus pacientes</p>
                        </div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-xl px-4 py-3">
                        <div class="flex items-center">
                            <div class="text-2xl mr-3">⏳</div>
                            <div>
                                <p class="text-sm font-medium text-orange-600">Pendientes</p>
                                <p class="text-2xl font-bold text-orange-700" id="pendingCount"><?php echo $currentStats['pending']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Solicitudes Recientes</h3>
                </div>
                <div class="divide-y divide-gray-200" id="appointmentsList">
                    <?php if (empty($pendingAppointments)): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">¡Todas las solicitudes procesadas!</h3>
                            <p class="text-gray-600">No hay solicitudes pendientes en este momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingAppointments as $appointment): ?>
                            <div class="p-6 hover:bg-gray-50 transition-colors fade-in" id="appointment-<?php echo $appointment['id_cita']; ?>">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                                    <div class="flex-1">
                                        <div class="flex items-start space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-semibold text-lg">
                                                        <?php echo strtoupper(substr($appointment['patientName'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($appointment['patientName']); ?></h4>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        Pendiente
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                                                    <div class="flex items-center"><span class="mr-2">📞</span><?php echo htmlspecialchars($appointment['phone']); ?></div>
                                                    <div class="flex items-center"><span class="mr-2">📧</span><?php echo htmlspecialchars($appointment['email']); ?></div>
                                                    <div class="flex items-center"><span class="mr-2">🦷</span><?php echo htmlspecialchars($appointment['service']); ?></div>
                                                    <div class="flex items-center"><span class="mr-2">📅</span><?php echo formatDate($appointment['preferredDate']); ?> a las <?php echo substr($appointment['preferredTime'], 0, 5); ?></div>
                                                </div>
                                                <p class="text-xs text-gray-500">Solicitada el <?php echo formatDate($appointment['requestDate']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-3 lg:ml-6">
                                        <button onclick="acceptAppointment(<?php echo $appointment['id_cita']; ?>)" 
                                            class="flex-1 lg:flex-none bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-medium transition-all duration-200 flex items-center justify-center shadow-md hover:shadow-lg">
                                            <span class="mr-2">✅</span>
                                            Aceptar
                                        </button>
                                        <button onclick="rejectAppointment(<?php echo $appointment['id_cita']; ?>)" 
                                            class="flex-1 lg:flex-none bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-medium transition-all duration-200 flex items-center justify-center shadow-md hover:shadow-lg">
                                            <span class="mr-2">❌</span>
                                            Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <i id="alert-modal-icon" class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                <h3 id="alert-modal-title" class="text-lg font-bold text-gray-900 mb-2">Éxito</h3>
                <p id="alert-modal-message" class="text-gray-600 mb-6">Tu operación fue exitosa.</p>
                <div class="flex justify-center">
                    <button onclick="hideAlertModal()" class="bg-blue-500 text-white py-2 px-6 rounded-lg hover:bg-blue-600 transition-colors">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <i id="confirm-modal-icon" class="fas fa-question-circle text-4xl text-blue-500 mb-4"></i>
                <h3 id="confirm-modal-title" class="text-lg font-bold text-gray-900 mb-2">Confirmar Acción</h3>
                <p id="confirm-modal-message" class="text-gray-600 mb-6">¿Estás seguro?</p>
                <div class="flex space-x-3">
                    <button id="confirm-modal-button" class="flex-1 bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors">
                        Sí, Aceptar
                    </button>
                    <button onclick="hideConfirmationModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        // --- INICIO DE CÓDIGO JAVASCRIPT MODIFICADO ---

        // Variable global solo para el callback (la acción a ejecutar)
        let confirmCallback = null;

        // Función de ayuda para formato de fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
            return date.toLocaleDateString('es-ES', options);
        }

        // --- FUNCIONES DEL MODAL DE CONFIRMACIÓN (BONITO) ---

        // Función para MOSTRAR el modal de confirmación
        function showConfirmationModal(title, message, iconClass, btnText, btnClass, callback) {
            
            // Obtener elementos DENTRO de la función (Corrige el error 'null')
            const confirmModal = document.getElementById('confirmation-modal');
            const confirmBtn = document.getElementById('confirm-modal-button');
            const confirmIcon = document.getElementById('confirm-modal-icon');
            const confirmTitle = document.getElementById('confirm-modal-title');
            const confirmMessage = document.getElementById('confirm-modal-message');

            if (!confirmModal) {
                console.error("Error: No se encontró #confirmation-modal.");
                // Fallback al alert feo si el modal bonito no carga
                if (confirm(message)) {
                    callback();
                }
                return;
            }

            confirmTitle.innerText = title;
            confirmMessage.innerText = message;
            confirmIcon.className = `fas ${iconClass} text-4xl mb-4`;
            
            confirmBtn.innerText = btnText;
            // Aplicar clases base + clases dinámicas
            confirmBtn.className = `flex-1 ${btnClass} text-white py-2 px-4 rounded-lg transition-colors`;
            
            confirmCallback = callback;
            confirmBtn.onclick = handleConfirm; // Asignar la función manejadora

            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
        }

        // Función para OCULTAR el modal de confirmación
        function hideConfirmationModal() {
            const confirmModal = document.getElementById('confirmation-modal');
            if (confirmModal) {
                confirmModal.classList.add('hidden');
                confirmModal.classList.remove('flex');
            }
            confirmCallback = null;
        }

        // Función que ejecuta la acción de confirmación
        function handleConfirm() {
            if (typeof confirmCallback === 'function') {
                confirmCallback(); // Ejecuta la acción (ej. actualizarEstadoCita)
            }
            hideConfirmationModal();
        }

        // --- FUNCIONES DE ACCIÓN (MODIFICADAS) ---

        // MODIFICADO: acceptAppointment AHORA MUESTRA EL MODAL "BONITO"
        async function acceptAppointment(appointmentId) {
            showConfirmationModal(
                'Aceptar Cita',
                '¿Confirmas que quieres ACEPTAR esta cita? Se notificará al paciente.',
                'fa-check-circle text-green-500', // Icono
                'Sí, Aceptar',
                'bg-green-500 hover:bg-green-600', // Clase del botón
                () => { // Callback
                    actualizarEstadoCita(appointmentId, 'Confirmada');
                }
            );
        }

        // MODIFICADO: rejectAppointment AHORA MUESTRA EL MODAL "BONITO"
        async function rejectAppointment(appointmentId) {
            showConfirmationModal(
                'Rechazar Cita',
                '¿Estás seguro de que quieres RECHAZAR esta cita? Se notificará al paciente.',
                'fa-trash-alt text-red-500', // Icono
                'Sí, Rechazar',
                'bg-red-500 hover:bg-red-600', // Clase del botón
                () => { // Callback
                    actualizarEstadoCita(appointmentId, 'Rechazada');
                }
            );
        }

        // Función que llama a la API (MODIFICADA)
        async function actualizarEstadoCita(idCita, nuevoEstado) {
            const appointmentElement = document.getElementById(`appointment-${idCita}`);
            
            // 1. MODIFICACIÓN (FLUIDEZ): Mostrar "Procesando..." INMEDIATAMENTE
            // Usamos la función showSuccessMessage pero con un icono de carga
            showProcessingMessage();

            try {
                // RUTA CORREGIDA: Subir dos niveles
                const response = await fetch('../../api/actualizar_estado_cita.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_cita: idCita, estado: nuevoEstado })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 2. MODIFICACIÓN (FLUIDEZ): Actualizar el modal a "Éxito"
                    showSuccessMessage(result.message); 
                    
                    // 3. MODIFICACIÓN (CONTADOR): Actualizar el contador con la respuesta de la API
                    updateStats(result.stats);
                    
                    if (appointmentElement) {
                        appointmentElement.style.opacity = '0';
                        appointmentElement.style.transform = 'translateX(-100%)';
                        setTimeout(() => {
                            appointmentElement.remove();
                            // Ya no necesitamos fetchTodayStats() aquí
                            checkEmptyState();
                        }, 300);
                    }
                } else {
                    // Criterio de Aceptación: Mensaje de error/reintento
                    showErrorMessage(result.message);
                    if (appointmentElement) {
                        appointmentElement.style.opacity = '1';
                        appointmentElement.style.pointerEvents = 'auto';
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorMessage('Error de conexión. Inténtalo de nuevo.');
                if (appointmentElement) {
                    appointmentElement.style.opacity = '1';
                    appointmentElement.style.pointerEvents = 'auto';
                }
            }
        }

        // Función para actualizar el contador (Corregida)
        function updateStats(stats) {
            const pendingCountEl = document.getElementById('pendingCount');
            if (stats && pendingCountEl) {
                pendingCountEl.textContent = stats.pending;
            }
        } 

        // Función para obtener estadísticas (Corregida)
        // Esta función llama a la lógica PHP que está en ESTE MISMO archivo
        async function fetchTodayStats() {
            try {
                const response = await fetch('', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_stats' 
                });
                const result = await response.json();
                if (result.success) {
                    updateStats(result.stats);
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        }
        
        // Función para verificar si la lista está vacía (Sin cambios)
        function checkEmptyState() {
            const appointmentsList = document.getElementById('appointmentsList');
            const appointmentItems = appointmentsList.querySelectorAll('div[id^="appointment-"]');
            
            if (appointmentItems.length === 0) {
                appointmentsList.innerHTML = `
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">🎉</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">¡Todas las solicitudes procesadas!</h3>
                        <p class="text-gray-600">No hay solicitudes pendientes en este momento.</p>
                    </div>
                `;
            }
        }

        // --- FUNCIONES DEL MODAL DE ALERTA "BONITO" ---
        
        function hideAlertModal() {
            const modal = document.getElementById('alert-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // NUEVA FUNCIÓN: Muestra un estado de "Procesando"
        function showProcessingMessage() {
            const modal = document.getElementById('alert-modal');
            if (!modal) { return; } // Salir si el modal no existe

            document.getElementById('alert-modal-icon').className = "fas fa-spinner fa-spin text-4xl text-blue-500 mb-4";
            document.getElementById('alert-modal-title').innerText = "Procesando";
            document.getElementById('alert-modal-message').innerText = "Estamos procesando la solicitud y notificando al paciente...";
            
            // Ocultar el botón "Entendido"
            modal.querySelector('button').classList.add('hidden');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // MODIFICADA: Muestra Éxito y re-habilita el botón "Entendido"
        function showSuccessMessage(message) {
            const modal = document.getElementById('alert-modal');
            if (!modal) { alert(message); return; } 

            document.getElementById('alert-modal-icon').className = "fas fa-check-circle text-4xl text-green-500 mb-4";
            document.getElementById('alert-modal-title').innerText = "Éxito";
            document.getElementById('alert-modal-message').innerText = message;
            
            // Mostrar el botón "Entendido"
            modal.querySelector('button').classList.remove('hidden');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // MODIFICADA: Muestra Error y re-habilita el botón "Entendido"
        function showErrorMessage(message) {
            const modal = document.getElementById('alert-modal');
            if (!modal) { alert(message); return; }

            document.getElementById('alert-modal-icon').className = "fas fa-exclamation-triangle text-4xl text-red-500 mb-4";
            document.getElementById('alert-modal-title').innerText = "Error";
            document.getElementById('alert-modal-message').innerText = message;
            
            // Mostrar el botón "Entendido"
            modal.querySelector('button').classList.remove('hidden');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // inicializar 
        document.addEventListener('DOMContentLoaded', function() {
            // fetchTodayStats(); // No es necesario si la página ya carga las stats con PHP
            checkEmptyState(); // Comprobar al cargar
        });

    </script>
</body>
</html>