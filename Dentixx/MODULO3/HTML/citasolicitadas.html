<?php
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
        $today = date('Y-m-d');
        
        // contar citas pendientes 
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM citas WHERE estado_cita = 'Pendiente'");
        $stmt->execute();
        $stats['pending'] = $stmt->fetchColumn();
        
        
    } catch (Exception $e) {
        error_log("Error getting real-time stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Funcion para actualizar el estatus de la cita
function updateAppointmentStatus($appointmentId, $status) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE citas 
            SET estado_cita = ? 
            WHERE id_cita = ?
        ");
        return $stmt->execute([$status, $appointmentId]);
    } catch (Exception $e) {
        error_log("Error updating appointment status: " . $e->getMessage());
        return false;
    }
}

// manejar Ajax solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_appointments':
                $appointments = getPendingAppointments();
                $stats = getRealTimeStats();
                echo json_encode([
                    'success' => true, 
                    'appointments' => $appointments,
                    'stats' => $stats
                ]);
                break;
                
            case 'accept_appointment':
                $appointmentId = $_POST['appointmentId'];
                $success = updateAppointmentStatus($appointmentId, 'Confirmada');
                $stats = getRealTimeStats();
                echo json_encode([
                    'success' => $success,
                    'stats' => $stats
                ]);
                break;
                
            case 'reject_appointment':
                $appointmentId = $_POST['appointmentId'];
                $success = updateAppointmentStatus($appointmentId, 'Rechazada');
                $stats = getRealTimeStats();
                echo json_encode([
                    'success' => $success,
                    'stats' => $stats
                ]);
                break;
                
            case 'get_stats':
                $stats = getRealTimeStats();
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// obtener información inicial
$pendingAppointments = getPendingAppointments();
$currentStats = getRealTimeStats();
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Citas</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Card -->
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

            <!-- Appointments List -->
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
                                                    <div class="flex items-center">
                                                        <span class="mr-2">📞</span>
                                                        <?php echo htmlspecialchars($appointment['phone']); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="mr-2">📧</span>
                                                        <?php echo htmlspecialchars($appointment['email']); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="mr-2">🦷</span>
                                                        <?php echo htmlspecialchars($appointment['service']); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="mr-2">📅</span>
                                                        <?php echo formatDate($appointment['preferredDate']); ?> a las <?php echo substr($appointment['preferredTime'], 0, 5); ?>
                                                    </div>
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

    <script>
        async function acceptAppointment(appointmentId) {
            if (confirm('¿Confirmas que quieres aceptar esta cita?')) {
                await processAppointment('accept', appointmentId);
            }
        }

        async function rejectAppointment(appointmentId) {
            if (confirm('¿Estás seguro de que quieres rechazar esta cita?')) {
                await processAppointment('reject', appointmentId);
            }
        }

        async function processAppointment(action, appointmentId) {
            try {
                const actionType = action === 'accept' ? 'accept_appointment' : 'reject_appointment';
                
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${actionType}&appointmentId=${appointmentId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // remover solicitud de la lista
                    const appointmentElement = document.getElementById(`appointment-${appointmentId}`);
                    if (appointmentElement) {
                        appointmentElement.style.opacity = '0';
                        appointmentElement.style.transform = 'translateX(-100%)';
                        setTimeout(() => {
                            appointmentElement.remove();
                            updateStats(result.stats);
                            checkEmptyState();
                        }, 300);
                    }
                    
                    const actionText = action === 'accept' ? 'aceptada' : 'rechazada';
                    showSuccessMessage(`Cita ${actionText} correctamente`);
                } else {
                    showErrorMessage('Error al procesar la cita. Inténtalo de nuevo.');
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorMessage('Error de conexión. Inténtalo de nuevo.');
            }
        }

        function updateStats(stats) {
            if (stats) {
                document.getElementById('pendingCount').textContent = stats.pending;
            }
        } // actualizar contador

        async function fetchTodayStats() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
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

        function checkEmptyState() {
            const appointmentsList = document.getElementById('appointmentsList');
            const appointmentItems = appointmentsList.querySelectorAll('div:not(.text-center)');
            
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

        function showSuccessMessage(message) {
            showMessage(message, 'green');
        }

        function showErrorMessage(message) {
            showMessage(message, 'red');
        }

        function showMessage(message, color) {
            const bgColor = color === 'green' ? 'bg-green-600' : 'bg-red-600';
            const successDiv = document.createElement('div');
            successDiv.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            successDiv.textContent = message;
            document.body.appendChild(successDiv);

            setTimeout(() => {
                successDiv.classList.remove('translate-x-full');
            }, 100);

            setTimeout(() => {
                successDiv.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 300);
            }, 3000);
        }

        // inicializar 
        document.addEventListener('DOMContentLoaded', function() {
            fetchTodayStats();
        });
    </script>
</body>
</html>

<?php
// función para el formato de fecha
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}
?>
