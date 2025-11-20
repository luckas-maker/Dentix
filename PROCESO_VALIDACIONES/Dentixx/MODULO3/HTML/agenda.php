<?php
session_start();
include '../../config/database.php';

// obtener citas confirmadas desde la base de datos
function getConfirmedAppointments() {
    $pdo = getDBConnection();
    $appointments = [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id_cita,
                c.id_paciente,
                u.nombre,
                u.apellidos,
                u.telefono,
                c.tipo_servicio as treatment,
                f.fecha,
                f.hora_inicio as time,
                c.estado_cita as status,
                c.fecha_creacion
            FROM citas c
            LEFT JOIN usuarios u ON c.id_paciente = u.id_usuario
            LEFT JOIN franjasdisponibles f ON c.id_franja = f.id_franja
            WHERE c.estado_cita IN ('Confirmada', 'Asistida', 'No Asistio')
            ORDER BY f.fecha, f.hora_inicio
        ");
        $stmt->execute();
        
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting confirmed appointments: " . $e->getMessage());
    }
    
    return $appointments;
}

// funcion para actualizar el estado de la cita (SOLO INTERNO)
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

// obtener citas desde la base de datos
$dbAppointments = getConfirmedAppointments();

// convertir la información al formato esperado en el front-end
$appointmentsData = [];
foreach ($dbAppointments as $dbAppt) {
    $dateStr = $dbAppt['fecha'];
    $patientName = $dbAppt['nombre'] . ' ' . $dbAppt['apellidos'];
    
    // convertir el estatus al formato del front-end
    $status = 'confirmed'; // default
    if ($dbAppt['status'] === 'Asistida') {
        $status = 'completed';
    } elseif ($dbAppt['status'] === 'No Asistio') {
        $status = 'absent';
    } elseif ($dbAppt['status'] === 'Cancelada') {
        $status = 'cancelled';
    }
    
    if (!isset($appointmentsData[$dateStr])) {
        $appointmentsData[$dateStr] = [];
    }
    
    $appointmentsData[$dateStr][] = [
        'id_cita' => $dbAppt['id_cita'],
        'time' => substr($dbAppt['time'], 0, 5),
        'patient' => $patientName,
        'treatment' => $dbAppt['treatment'],
        'phone' => $dbAppt['telefono'],
        'status' => $status,
        'db_status' => $dbAppt['status'] //guardar el status anterior para referencia
    ];
}

// manejar las actualizaciones de estado con AJAX 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_status':
                $appointmentId = $_POST['appointmentId'];
                $newStatus = $_POST['newStatus'];
                
                // comparación del estatus en la base de datos y en el frontend
                $statusMap = [
                    'completed' => 'Asistida',
                    'absent' => 'No Asistio', 
                    'cancelled' => 'Cancelada'
                ];
                
                // BLOQUEAR cancelación e inasistencia por aquí (se debe usar la API externa para enviar correos)
                if ($newStatus === 'cancelled' || $newStatus === 'absent') {
                      echo json_encode(['success' => false, 'error' => 'Use la API externa (JavaScript)']);
                      exit;
                }
                
                $dbStatus = $statusMap[$newStatus] ?? $newStatus;
                $success = updateAppointmentStatus($appointmentId, $dbStatus);
                
                echo json_encode(['success' => $success]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de citas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        .appointment-slot {
            transition: all 0.2s ease;
        }
        .appointment-slot:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        /* Animación para el spinner */
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 font-sans">
    <div class="min-h-full p-6">
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Agenda de Citas</h1>
                        <p class="text-gray-600">Visualice y gestione todas las citas registradas y confirmadas</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600" id="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📅 Calendario de Citas</h2>
                        <div class="flex items-center space-x-4">
                            <button onclick="previousMonth()" class="p-2 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors">
                                ←
                            </button>
                            <span class="font-semibold text-gray-700" id="monthYear"></span>
                            <button onclick="nextMonth()" class="p-2 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors">
                                →
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-2 mb-4">
                        <div class="text-center font-semibold text-gray-600 py-2">Dom</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Lun</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Mar</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Mié</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Jue</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Vie</div>
                        <div class="text-center font-semibold text-gray-600 py-2">Sáb</div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-2" id="calendarGrid">
                        </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-blue-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">📊 Resumen del Mes</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium text-blue-800">Citas Confirmadas</span>
                            </div>
                            <span class="font-bold text-blue-600" id="confirmedCount"></span>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-blue-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">📋 Citas de Hoy</h3>
                    <div id="todayAppointments" class="space-y-2">
                        </div>
                </div>
            </div>
        </div>
    </div>

    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[70]">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <i id="alert-modal-icon" class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                <h3 id="alert-modal-title" class="text-lg font-bold text-gray-900 mb-2">Éxito</h3>
                <p id="alert-modal-message" class="text-gray-600 mb-6">Tu operación fue exitosa.</p>
                <div class="flex justify-center">
                    <button id="alert-modal-button" onclick="hideAlertModal()" class="bg-blue-500 text-white py-2 px-6 rounded-lg hover:bg-blue-600 transition-colors">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        let appointmentsData = <?php echo json_encode($appointmentsData); ?>;

        // inicializar el calendario
        function initCalendar() {
            updateCurrentDate();
            generateCalendar();
            updateStatistics();
            showTodayAppointments();
        }

        function updateCurrentDate() {
            const today = new Date();
            document.getElementById('currentDate').textContent = today.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function generateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('monthYear').textContent = currentDate.toLocaleDateString('es-ES', {
                month: 'long',
                year: 'numeric'
            });

            const firstDay = new Date(year, month, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const dayElement = document.createElement('div');
                const dateStr = date.toISOString().split('T')[0];
                const isCurrentMonth = date.getMonth() === month;
                const isToday = date.toDateString() === new Date().toDateString();
                const hasAppointments = appointmentsData[dateStr] && appointmentsData[dateStr].length > 0;
                
                dayElement.className = `
                    p-2 cursor-pointer rounded-lg transition-all hover:scale-105 min-h-32 border
                    ${isCurrentMonth ? 'text-gray-800 bg-white border-gray-200' : 'text-gray-400 bg-gray-50 border-gray-100'}
                    ${isToday ? 'border-blue-600 border-2 bg-blue-50' : ''}
                    ${hasAppointments ? 'border-green-400' : ''}
                `;
                
                // numero del dia
                const dayNumber = document.createElement('div');
                dayNumber.className = `text-sm font-semibold mb-1 ${isToday ? 'text-blue-600' : ''}`;
                dayNumber.textContent = date.getDate();
                dayElement.appendChild(dayNumber);
                
                //despliegue de las citas
                if (hasAppointments) {
                    const appointments = appointmentsData[dateStr];
                    const appointmentsContainer = document.createElement('div');
                    appointmentsContainer.className = 'space-y-1';
                    
                    // Vista previa de las citas de un dia
                    const visibleAppointments = appointments.slice(0, 3);
                    visibleAppointments.forEach((appointment, index) => {
                        const appointmentElement = document.createElement('div');
                        let bgColor = 'bg-blue-500 hover:bg-blue-600';
                        let statusIcon = '';
                        
                        if (appointment.status === 'completed') {
                            bgColor = 'bg-green-500 hover:bg-green-600';
                            statusIcon = '✅ ';
                        } else if (appointment.status === 'absent') {
                            bgColor = 'bg-yellow-500 hover:bg-yellow-600';
                            statusIcon = '❌ ';
                        }
                        
                        appointmentElement.className = `text-xs px-1 py-0.5 rounded cursor-pointer transition-colors ${bgColor} text-white`;
                        appointmentElement.textContent = `${statusIcon}${appointment.time} - ${appointment.patient.split(' ')[0]}`;
                        appointmentElement.onclick = (e) => {
                            e.stopPropagation();
                            openSingleAppointmentModal(appointmentsData[dateStr][index], dateStr, index);
                        };
                        appointmentsContainer.appendChild(appointmentElement);
                    });
                    
                    if (appointments.length > 3) {
                        const moreElement = document.createElement('div');
                        moreElement.className = 'text-xs text-gray-500 text-center cursor-pointer hover:text-gray-700';
                        moreElement.textContent = `+${appointments.length - 3} más`;
                        moreElement.onclick = (e) => {
                            e.stopPropagation();
                            openAppointmentsModal(dateStr, date);
                        };
                        appointmentsContainer.appendChild(moreElement);
                    }
                    
                    dayElement.appendChild(appointmentsContainer);
                }
                
                dayElement.onclick = () => openAppointmentsModal(dateStr, date);
                calendarGrid.appendChild(dayElement);
            }
        }

        function openSingleAppointmentModal(appointment, dateStr, appointmentIndex) {
            // Cerrar otros modales si existen
            document.querySelectorAll('.fixed.bg-black').forEach(el => {
                if (el.id !== 'alert-modal') el.remove();
            });

            const modalBackdrop = document.createElement('div');
            modalBackdrop.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modalBackdrop.onclick = (e) => {
                if (e.target === modalBackdrop) {
                    modalBackdrop.remove();
                }
            };

            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4';
            
            const appointmentDate = new Date(dateStr + 'T00:00:00');
            const dateFormatted = appointmentDate.toLocaleDateString('es-ES', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });

            modalContent.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📋 Información de Cita</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                
                <div class="mb-6">
                    <div class="p-4 rounded-lg border bg-blue-50 border-blue-300">
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📅 Fecha:</span>
                                <span class="font-semibold text-gray-800">${dateFormatted}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">🕐 Hora:</span>
                                <span class="font-bold text-lg text-gray-800">${appointment.time}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">👤 Paciente:</span>
                                <span class="font-semibold text-gray-800">${appointment.patient}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">🦷 Tratamiento:</span>
                                <span class="text-gray-800">${appointment.treatment}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📞 Teléfono:</span>
                                <span class="text-gray-800">${appointment.phone}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📊 Estado:</span>
                                <span class="font-medium ${appointment.status === 'completed' ? 'text-green-700' : appointment.status === 'absent' ? 'text-yellow-700' : 'text-blue-700'}">
                                    ${appointment.status === 'completed' ? '✅ Asistida' : appointment.status === 'absent' ? '❌ No Asistió' : '✅ Confirmada'}
                                </span>
                            </div>
                        </div>
                        
                        <div class="border-t border-blue-200 pt-4">
                            <p class="text-sm text-gray-600 mb-3">¿Qué acción desea realizar con esta cita?</p>
                            <div class="flex flex-col space-y-2">
                                ${appointment.status === 'confirmed' ? `
                                    <button onclick="confirmAction('${dateStr}', ${appointmentIndex}, 'completed', ${appointment.id_cita})" 
                                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            ✅ Marcar como Asistida
                                    </button>
                                    <button onclick="confirmAction('${dateStr}', ${appointmentIndex}, 'absent', ${appointment.id_cita})" 
                                            class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                            ❌ Marcar como No Asistió
                                    </button>
                                    <button onclick="confirmAction('${dateStr}', ${appointmentIndex}, 'cancelled', ${appointment.id_cita})" 
                                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                            ❌ Cancelar Cita
                                    </button>
                                ` : `
                                    <div class="w-full px-4 py-2 bg-gray-100 text-gray-800 rounded-lg text-center">
                                        Esta cita ya ha sido procesada.
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            modalBackdrop.appendChild(modalContent);
            document.body.appendChild(modalBackdrop);
        }

        function openAppointmentsModal(dateStr, date) {
            const modalBackdrop = document.createElement('div');
            modalBackdrop.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modalBackdrop.onclick = (e) => {
                if (e.target === modalBackdrop) {
                    modalBackdrop.remove();
                }
            };

            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white rounded-2xl shadow-2xl p-6 max-w-2xl w-full mx-4 max-h-96 overflow-hidden';
            
            const dateFormatted = date.toLocaleDateString('es-ES', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });

            modalContent.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📅 Citas del ${dateFormatted}</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                <div class="mb-4">
                    <div id="modalAppointments" class="space-y-3 max-h-64 overflow-y-auto">
                        ${generateModalAppointments(dateStr)}
                    </div>
                </div>
            `;

            modalBackdrop.appendChild(modalContent);
            document.body.appendChild(modalBackdrop);
        }

        function generateModalAppointments(dateStr) {
            if (!appointmentsData[dateStr] || appointmentsData[dateStr].length === 0) {
                return '<div class="text-center text-gray-500 py-8">No hay citas programadas para esta fecha</div>';
            }

            return appointmentsData[dateStr].map((appointment, index) => {
                let bgColor = 'bg-blue-50 border-blue-300';
                if (appointment.status === 'completed') bgColor = 'bg-green-50 border-green-300';
                else if (appointment.status === 'absent') bgColor = 'bg-yellow-50 border-yellow-300';
                
                return `
                    <div class="p-4 rounded-lg border ${bgColor} cursor-pointer" onclick="openSingleAppointmentModal(appointmentsData['${dateStr}'][${index}], '${dateStr}', ${index})">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-bold text-lg text-gray-800">${appointment.time}</span>
                                <span class="ml-2 font-semibold text-gray-700">${appointment.patient}</span>
                                <div class="text-sm text-gray-600">${appointment.treatment}</div>
                            </div>
                            <div>
                                ${appointment.status === 'confirmed' ? 
                                    `<span class="text-blue-600 font-bold text-sm">Confirmada</span>` : 
                                    `<span class="text-gray-600 text-sm">Cerrada</span>`
                                }
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // --- LÓGICA DE CONFIRMACIÓN Y ACCIÓN ---
        function confirmAction(dateStr, appointmentIndex, newStatus, appointmentId) {
            const appointment = appointmentsData[dateStr][appointmentIndex];
            const patientName = appointment.patient;
            
            let confirmMessage = '';
            let title = '';
            let confirmBtnColor = '';
            
            if (newStatus === 'cancelled') {
                title = 'Cancelar Cita';
                confirmMessage = `¿Estás seguro de que deseas cancelar la cita de ${patientName}? Se enviará una notificación por correo.`;
                confirmBtnColor = 'bg-red-600 hover:bg-red-700';
            } else if (newStatus === 'completed') {
                title = 'Confirmar Asistencia';
                confirmMessage = `¿Confirmar que ${patientName} ha asistido a su cita?`;
                confirmBtnColor = 'bg-green-600 hover:bg-green-700';
            } else {
                title = 'Marcar Inasistencia';
                confirmMessage = `¿Marcar a ${patientName} como NO ASISTIÓ? Se enviará un correo de notificación al paciente.`;
                confirmBtnColor = 'bg-yellow-600 hover:bg-yellow-700';
            }

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] confirm-modal';
            
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">${title}</h3>
                    <p class="text-gray-600 mb-6">${confirmMessage}</p>
                    <div class="flex space-x-3 justify-end">
                        <button onclick="this.closest('.confirm-modal').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancelar
                        </button>
                        <button id="btn-confirm-action" class="px-4 py-2 text-white rounded-lg transition-colors ${confirmBtnColor} flex items-center">
                            <span class="btn-text">Confirmar</span>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);

            // Manejar el click en Confirmar
            document.getElementById('btn-confirm-action').onclick = async function() {
                const btn = this;
                
                // 1. CAMBIO VISUAL (Spinner y Texto)
                btn.disabled = true;
                btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...`;
                btn.classList.add('opacity-75', 'cursor-not-allowed');

                try {
                    let response;
                    
                    // VERIFICAR SI ES CANCELACIÓN O INASISTENCIA (AMBOS USAN API EXTERNA PARA CORREO)
                    if (newStatus === 'cancelled' || newStatus === 'absent') {
                        
                        // Definir la URL según el estado
                        const url = newStatus === 'cancelled' 
                            ? '../../api/cancelar_cita.php' 
                            : '../../api/marcar_inasistencia.php';
                            
                        // Definir el cuerpo de la petición
                        const bodyData = { id_cita: appointmentId };
                        if (newStatus === 'cancelled') {
                            bodyData.motivo_cancelacion = 'Cancelada por el odontólogo.';
                        }

                        // API EXTERNA DE CORREO
                        response = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(bodyData)
                        });
                    } else {
                        // API INTERNA (SOLO BASE DE DATOS, EJ: ASISTIDA)
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        formData.append('appointmentId', appointmentId);
                        formData.append('newStatus', newStatus);
                        
                        response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                    }

                    const result = await response.json();
                    
                    // Cerrar modales
                    modal.remove(); // confirmacion
                    document.querySelectorAll('.fixed.bg-opacity-50').forEach(m => {
                         if(m.id !== 'alert-modal') m.remove();
                    });

                    if (result.success) {
                        if (newStatus === 'cancelled') {
                            // Eliminar del array
                            appointmentsData[dateStr].splice(appointmentIndex, 1);
                            if (appointmentsData[dateStr].length === 0) delete appointmentsData[dateStr];
                        } else {
                            // Actualizar estado local
                            appointmentsData[dateStr][appointmentIndex].status = newStatus;
                        }
                        
                        // Refrescar UI
                        generateCalendar();
                        updateStatistics();
                        showTodayAppointments();
                        
                        showAlert('success', 'Operación Exitosa', result.message || 'El estado se actualizó y se notificó correctamente.');
                    } else {
                        showAlert('error', 'Error', result.error || 'Hubo un error al procesar.');
                    }

                } catch (error) {
                    modal.remove();
                    console.error(error);
                    showAlert('error', 'Error', 'Error de conexión con el servidor.');
                }
            };
        }

        // Mostrar alerta bonita
        function showAlert(type, title, message) {
            const modal = document.getElementById('alert-modal');
            const icon = document.getElementById('alert-modal-icon');
            
            if (type === 'success') {
                icon.className = "fas fa-check-circle text-4xl text-green-500 mb-4";
            } else {
                icon.className = "fas fa-exclamation-triangle text-4xl text-red-500 mb-4";
            }
            
            document.getElementById('alert-modal-title').innerText = title;
            document.getElementById('alert-modal-message').innerText = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function hideAlertModal() {
            document.getElementById('alert-modal').classList.add('hidden');
            document.getElementById('alert-modal').classList.remove('flex');
        }

        function updateStatistics() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            let confirmed = 0;
            Object.keys(appointmentsData).forEach(dateStr => {
                const date = new Date(dateStr + 'T00:00:00');
                if (date.getFullYear() === year && date.getMonth() === month) {
                    appointmentsData[dateStr].forEach(app => {
                        if (app.status !== 'cancelled') confirmed++;
                    });
                }
            });
            document.getElementById('confirmedCount').textContent = confirmed;
        }

        function showTodayAppointments() {
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const todayAppointments = appointmentsData[todayStr] || [];
            const container = document.getElementById('todayAppointments');
            
            if (todayAppointments.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No hay citas para hoy</div>';
                return;
            }
            
            container.innerHTML = todayAppointments.map((appointment, index) => {
                let bgColor = 'bg-blue-500 hover:bg-blue-600';
                let statusIcon = '';
                
                if (appointment.status === 'completed') { bgColor = 'bg-green-500'; statusIcon = '✅ '; }
                else if (appointment.status === 'absent') { bgColor = 'bg-yellow-500'; statusIcon = '❌ '; }
                else if (appointment.status === 'cancelled') return '';
                
                return `<div class="p-2 rounded-lg ${bgColor} text-white text-xs cursor-pointer" onclick="openSingleAppointmentModal(appointmentsData['${todayStr}'][${index}], '${todayStr}', ${index})"><div class="font-semibold">${statusIcon}${appointment.time} - ${appointment.patient.split(' ')[0]}</div><div class="opacity-90">${appointment.treatment}</div></div>`;
            }).join('');
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar();
            updateStatistics();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar();
            updateStatistics();
        }

        initCalendar();
    </script>
</body>
</html>