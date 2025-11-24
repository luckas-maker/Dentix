<?php
session_start();
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

include '../../config/database.php'; 

// función para mostrar las franjas disponibles
function getAvailabilityData($date = null) {
    $pdo = getDBConnection();
    $data = [];
    
    try {
        if ($date) {
            // obtener todas las franjas disponibles
            $stmt = $pdo->prepare("SELECT * FROM franjasdisponibles WHERE fecha = ? ORDER BY hora_inicio");
            $stmt->execute([$date]);
            $results = $stmt->fetchAll();
        } else {
            // todos los datos del mes actual
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            $stmt = $pdo->prepare("SELECT * FROM franjasdisponibles WHERE fecha >= ? AND fecha < ? ORDER BY fecha, hora_inicio");
            $stmt->execute([$currentMonth, $nextMonth]);
            $results = $stmt->fetchAll();
        }
        
        foreach ($results as $row) {
            $dateStr = $row['fecha'];
            if (!isset($data[$dateStr])) {
                $data[$dateStr] = [];
            }
            
            $data[$dateStr][] = [
                'id' => $row['id_franja'],
                'time' => substr($row['hora_inicio'], 0, 5), // Get only HH:MM
                'available' => $row['estado'] === 'Disponible',
                'estado' => $row['estado']
            ];
        }
    } catch (Exception $e) {
        error_log("Error getting availability data: " . $e->getMessage());
    }
    
    return $data;
}

// Función para guardar franjas de disponibilidad
function saveTimeSlots($date, $slots) {
    $pdo = getDBConnection();
    
    try {
        // empieza una transacción
        $pdo->beginTransaction();
        
        // borrar todas las franjas de esta fecha
        $deleteStmt = $pdo->prepare("DELETE FROM franjasdisponibles WHERE fecha = ?");
        $deleteStmt->execute([$date]);
        
        //agregar franjas - TODAS como Disponible por defecto
        $insertStmt = $pdo->prepare("INSERT INTO franjasdisponibles (fecha, hora_inicio, estado) VALUES (?, ?, 'Disponible')");
        
        foreach ($slots as $slot) {
            $insertStmt->execute([
                $date,
                $slot['time'] . ':00', // agregar segundos al formato de hora
            ]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving time slots: " . $e->getMessage());
        return false;
    }
}

// funcion para actualizar el tiempo de una franja
function updateSlotTime($slotId, $newTime) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("UPDATE franjasdisponibles SET hora_inicio = ? WHERE id_franja = ?");
        return $stmt->execute([$newTime . ':00', $slotId]);
    } catch (Exception $e) {
        error_log("Error updating slot time: " . $e->getMessage());
        return false;
    }
}

// funcion para eliminar una franja
function deleteSlot($slotId) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM franjasdisponibles WHERE id_franja = ?");
        return $stmt->execute([$slotId]);
    } catch (Exception $e) {
        error_log("Error deleting slot: " . $e->getMessage());
        return false;
    }
}

//agregar una sola franja, "agregar+" - Por defecto como Disponible
function addCustomSlot($date, $time) {
    $pdo = getDBConnection();
    
    try {
        // revisar si existe esta franja
        $checkStmt = $pdo->prepare("SELECT id_franja FROM franjasdisponibles WHERE fecha = ? AND hora_inicio = ?");
        $checkStmt->execute([$date, $time . ':00']);
        
        if ($checkStmt->fetch()) {
            return false; // si existe
        }
        
        $insertStmt = $pdo->prepare("INSERT INTO franjasdisponibles (fecha, hora_inicio, estado) VALUES (?, ?, 'Disponible')");
        return $insertStmt->execute([$date, $time . ':00']);
    } catch (Exception $e) {
        error_log("Error adding custom slot: " . $e->getMessage());
        return false;
    }
}

// manejar las solicitudes ajax
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_availability': // obtener disponibilidad de horarios
                $date = $_POST['date'] ?? null;
                $data = getAvailabilityData($date);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'save_slots':
                $date = $_POST['date']; // guardar horarios
                $slots = json_decode($_POST['slots'], true);
                $success = saveTimeSlots($date, $slots);
                echo json_encode(['success' => $success]);
                break;
                
            case 'update_slot_time': // actualizar franja
                $slotId = $_POST['slotId'];
                $newTime = $_POST['newTime'];
                $success = updateSlotTime($slotId, $newTime);
                echo json_encode(['success' => $success]);
                break;
                
            case 'delete_slot':
                $slotId = $_POST['slotId']; // borrar franja
                $success = deleteSlot($slotId);
                echo json_encode(['success' => $success]);
                break;
                
            case 'add_custom_slot': // boton de agregar+
                $date = $_POST['date'];
                $time = $_POST['time'];
                $success = addCustomSlot($date, $time);
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

// obtener la disponibilidad del mes
$availabilityData = getAvailabilityData();
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Disponibilidad - Agenda Dental</title>
    <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        .time-slot {
            transition: all 0.2s ease;
        }
        .time-slot:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .available-slot {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .unavailable-slot {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .reserved-slot {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .bg-dental-gradient { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); }
        .dental-accent { color: #0077b6; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 font-sans">
    <div class="min-h-full p-6">
        <!-- Modal de éxito para mensajes bonitos -->
        <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[100]">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                <i id="success-modal-icon" class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                <h3 id="success-modal-title" class="text-lg font-bold mb-2">¡Éxito!</h3>
                <p id="success-modal-message" class="text-gray-600 mb-2"></p>
            </div>
        </div>
        
        <!-- Cabecera(?) -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">🦷 Gestión de Disponibilidad</h1>
                        <p class="text-gray-600">Configure sus horarios disponibles para atender pacientes</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600" id="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sección del calendario -->
            <div>
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📅 Calendario</h2>
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
                        <!-- Calendar days will be generated here -->
                    </div>
                </div>
            </div>

            <!-- Time Slots Configuration -->
            <div class="space-y-6">
                <!-- Selected Date Info -->
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-blue-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">📋 Fecha Seleccionada</h3>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <div class="text-xl font-bold text-blue-600" id="selectedDate">Seleccione una fecha</div>
                    </div>
                </div>

                <!-- Crear franja -->
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-blue-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">⏰ Crear franja disponible</h3>
                    
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Inicio</label>
                                <input type="time" id="startTime" value="08:00" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                                <input type="time" id="endTime" value="17:00" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Duración (min)</label>
                            <select id="slotDuration" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="30">30 minutos</option>
                                <option value="45">45 minutos</option>
                                <option value="60">60 minutos</option>
                                <option value="90">90 minutos</option>
                            </select>
                        </div>
                        
                        <button onclick="generateTimeSlots()" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 px-4 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all transform hover:scale-105">
                            Generar Horarios
                        </button>
                    </div>
                </div>

                <!-- Franjas disponibles -->
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-blue-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-800">🕐 Horarios del Día</h3>
                        <div class="flex space-x-2">
                            <button onclick="addCustomSlot()" class="px-2 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors">
                                + Agregar
                            </button>
                            <button onclick="clearAllSlots()" class="px-2 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                🗑️ Limpiar
                            </button>
                        </div>
                    </div>
                    <div id="timeSlotsList" class="space-y-2 max-h-96 overflow-y-auto">
                        <div class="text-center text-gray-500 py-8">
                            Seleccione una fecha para ver los horarios
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal de confirmación bonito (scope global)
        function showConfirmModal(message, onConfirm) {
            if (document.getElementById('confirm-modal')) {
                document.getElementById('confirm-modal').remove();
            }
            const modal = document.createElement('div');
            modal.id = 'confirm-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101]';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <h3 class="text-lg font-bold mb-2">Confirmar acción</h3>
                    <p class="text-gray-600 mb-6">${message}</p>
                    <div class="flex justify-center gap-4">
                        <button id="confirm-cancel-btn" class="bg-gray-300 text-gray-700 py-2 px-6 rounded-lg">Cancelar</button>
                        <button id="confirm-accept-btn" class="bg-red-600 text-white py-2 px-6 rounded-lg">Eliminar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            document.getElementById('confirm-cancel-btn').onclick = () => modal.remove();
            document.getElementById('confirm-accept-btn').onclick = async () => {
                modal.remove();
                await onConfirm();
            };
        }
        // Normaliza una hora HH:MM garantizando dos dígitos
        function normalizeTime(t) {
            if (!t) return t;
            if (/^\d{2}:\d{2}$/.test(t)) return t;
            const parts = t.split(':');
            if (parts.length < 2) return t;
            const hh = parts[0].padStart(2, '0');
            const mm = parts[1].padStart(2, '0');
            return `${hh}:${mm}`;
        }

        // Modal de mensaje reutilizable (scope global) — acepta tipo 'success' | 'error'
        function showSuccessModal(message, type = 'success') {
            const modal = document.getElementById('success-modal');
            if (!modal) {
                console.warn('showSuccessModal: success-modal element not found');
                return;
            }
            const titleEl = document.getElementById('success-modal-title');
            const msgEl = document.getElementById('success-modal-message');
            const iconEl = document.getElementById('success-modal-icon');

            msgEl.innerText = message;
            if (type === 'error') {
                titleEl.innerText = 'Atención';
                iconEl.className = 'fas fa-exclamation-circle text-4xl text-red-500 mb-4';
            } else {
                titleEl.innerText = '¡Éxito!';
                iconEl.className = 'fas fa-check-circle text-4xl text-green-500 mb-4';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.successModalTimeout) clearTimeout(window.successModalTimeout);
            window.successModalTimeout = setTimeout(hideSuccessModal, 3000);
        }

        function hideSuccessModal() {
            const modal = document.getElementById('success-modal');
            if (!modal) return;
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            if (window.successModalTimeout) clearTimeout(window.successModalTimeout);
        }
        let currentDate = new Date();
        let selectedDateStr = '';
        let availabilityData = <?php echo json_encode($availabilityData); ?>;

        // Inicializa el calendario
        function initCalendar() {
            updateCurrentDate();
            generateCalendar();
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

        function generateCalendar() { // genera un calendario estatico
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('monthYear').textContent = currentDate.toLocaleDateString('es-ES', {
                month: 'long',
                year: 'numeric'
            });

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
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
                const hasAvailability = availabilityData[dateStr] && availabilityData[dateStr].length > 0;
                
                dayElement.className = `
                    p-3 text-center cursor-pointer rounded-lg transition-all hover:scale-105
                    ${isCurrentMonth ? 'text-gray-800' : 'text-gray-400'}
                    ${isToday ? 'bg-blue-600 text-white font-bold' : 'hover:bg-blue-100'}
                    ${hasAvailability ? 'border-2 border-green-400' : ''}
                    ${selectedDateStr === dateStr ? 'ring-2 ring-blue-500 bg-blue-50' : ''}
                `;
                
                dayElement.textContent = date.getDate();
                dayElement.onclick = () => selectDate(dateStr, date);
                
                if (hasAvailability) {
                    const indicator = document.createElement('div');
                    indicator.className = 'w-2 h-2 bg-green-500 rounded-full mx-auto mt-1';
                    dayElement.appendChild(indicator);
                }
                
                calendarGrid.appendChild(dayElement);
            }
        }

        async function selectDate(dateStr, date) {
            selectedDateStr = dateStr;
            document.getElementById('selectedDate').textContent = date.toLocaleDateString('es-ES', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            
            // Cargar disponibilidad para fecha seleccionada
            await loadAvailabilityData(dateStr);
            generateCalendar();
            displayTimeSlots();
        }

        async function loadAvailabilityData(dateStr) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_availability&date=${dateStr}`
                });
                
                const result = await response.json();
                if (result.success) {
                    availabilityData = { ...availabilityData, ...result.data };
                }
            } catch (error) {
                console.error('Error loading availability data:', error);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar(); // visibilizar mes previo
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar(); //visualizar el siguiente mes
        }

        async function generateTimeSlots() {
    if (!selectedDateStr) {
        showSuccessModal('Por favor seleccione una fecha primero', 'error');
        return;
    }

    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const duration = parseInt(document.getElementById('slotDuration').value);

    if (!startTime || !endTime) {
        showSuccessModal('Por favor complete los horarios de inicio y fin', 'error');
        return;
    }

    // obtener las franjas existentes de esta fecha
    const existingSlots = availabilityData[selectedDateStr] || [];
    const existingTimes = new Set(existingSlots.map(slot => slot.time));

    const newSlots = [];
    const start = new Date(`2000-01-01T${startTime}:00`);
    const end = new Date(`2000-01-01T${endTime}:00`);

    let current = new Date(start);
    while (current < end) {
        const timeStr = current.toTimeString().slice(0, 5);
        
        // solo añadir si franjas con estas horas no existen
        if (!existingTimes.has(timeStr)) {
            newSlots.push({
                time: timeStr,
                available: true
            });
        }
        
        current.setMinutes(current.getMinutes() + duration);
    }

    if (newSlots.length === 0) {
        showSuccessModal('No hay nuevos horarios para agregar. Todos los horarios generados ya existen.', 'error');
        return;
    }

    // Combine existing slots with new slots
    //
    const allSlots = [...existingSlots.map(slot => ({
        time: slot.time,
        available: slot.available
    })), ...newSlots];

    // Save combined slots to database
    const success = await saveSlotsToDatabase(selectedDateStr, allSlots);
    if (success) {
        // Update local data with correct status
        await loadAvailabilityData(selectedDateStr);
        generateCalendar();
        displayTimeSlots();
        showSuccessModal(`¡Se agregaron ${newSlots.length} nuevos horarios exitosamente! 🎉`, 'success');
    } else {
        showSuccessModal('Error al guardar los horarios en la base de datos', 'error');
    }
}



        async function saveSlotsToDatabase(date, slots) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save_slots&date=${date}&slots=${encodeURIComponent(JSON.stringify(slots))}`
                });
                
                const result = await response.json();
                return result.success;
            } catch (error) {
                console.error('Error saving slots:', error);
                return false;
            }
        } // guardar franjas creadas en la base de datos

        function displayTimeSlots() {
            const container = document.getElementById('timeSlotsList');
            
            if (!selectedDateStr || !availabilityData[selectedDateStr]) {
                container.innerHTML = '<div class="text-center text-gray-500 py-8">No hay horarios configurados para esta fecha</div>';
                return;
            } // mostrar franja

            const slots = availabilityData[selectedDateStr];
            
            // por hora
            slots.sort((a, b) => a.time.localeCompare(b.time));
            
            container.innerHTML = slots.map((slot, index) => {
                //por defecto trata a todas las franjas 
                const estado = slot.estado || 'Disponible';
                
                let statusClass = 'available-slot text-white'; // DEFAULT: GREEN
                let statusText = '✅ Disponible';
                let statusIcon = '✅';
                
                // Solo cambiar de color si cambia NoDisponible o Reservada
                if (estado === 'NoDisponible') {
                    statusClass = 'unavailable-slot text-white';
                    statusText = '❌ No disponible';
                    statusIcon = '❌';
                } else if (estado === 'Reservada') {
                    statusClass = 'reserved-slot text-white';
                    statusText = '📅 Reservada';
                    statusIcon = '📅';
                }
                
                return `
                <div class="time-slot p-3 rounded-lg ${statusClass}" id="slot-${slot.id || index}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="font-semibold">${slot.time}</span>
                            <span class="text-sm">${statusText}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            ${estado !== 'Reservada' ? `
                            <button onclick="editSlotTime(${slot.id}, '${slot.time}')" class="p-1 hover:bg-white hover:bg-opacity-20 rounded transition-colors" title="Editar horario">
                                ✏️
                            </button>
                            <button onclick="deleteSlot(${slot.id})" class="p-1 hover:bg-white hover:bg-opacity-20 rounded transition-colors" title="Eliminar">
                                🗑️
                            </button>
                            ` : `
                            <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">Reservada</span>
                            `}
                        </div>
                    </div>
                </div>
            `}).join('');
        }

        async function editSlotTime(slotId, currentTime) { // editar hora de inicio
            showEditHorarioModal(currentTime, async (newTime) => {
                if (newTime && /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(newTime)) {
                    const normalizedNew = normalizeTime(newTime);
                    const normalizedCurrent = normalizeTime(currentTime);
                    if (normalizedNew === normalizedCurrent) {
                        return; // sin cambios
                    }
                    // Validar si la hora ya está ocupada
                    const slots = availabilityData[selectedDateStr] || [];
                    console.log('DEBUG editSlotTime:', { slotId, currentTime, newTime, normalizedCurrent, normalizedNew, slots });
                    const exists = slots.some(slot => normalizeTime(slot.time) === normalizedNew && slot.id !== slotId);
                    console.log('DEBUG duplicate check exists=', exists);
                    if (exists) {
                        console.log('DEBUG showing occupied message');
                        showSuccessModal('La hora seleccionada ya está ocupada. Elija otra hora disponible.', 'error');
                        return;
                    }
                    try {
                        const response = await fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update_slot_time&slotId=${slotId}&newTime=${newTime}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            await loadAvailabilityData(selectedDateStr);
                            displayTimeSlots();
                            generateCalendar();
                            showSuccessModal('Horario actualizado exitosamente.', 'success');
                        } else {
                            showSuccessModal('Error al actualizar el horario. Puede que ya exista un horario con esa hora.', 'error');
                        }
                    } catch (error) {
                        console.error('Error updating slot time:', error);
                        showSuccessModal('Error al actualizar el horario', 'error');
                    }
                } else if (newTime) {
                    showSuccessModal('Formato de hora inválido. Use HH:MM (ejemplo: 09:30)', 'error');
                }
            });
                // Modal  para editar horario
                function showEditHorarioModal(currentTime, onEdit) {
                    if (document.getElementById('edit-horario-modal')) {
                        document.getElementById('edit-horario-modal').remove();
                    }
                    const modal = document.createElement('div');
                    modal.id = 'edit-horario-modal';
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101]';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                            <i class="fas fa-edit text-4xl text-blue-500 mb-4"></i>
                            <h3 class="text-lg font-bold mb-2">Editar horario</h3>
                            <p class="text-gray-600 mb-4">Ingrese el nuevo horario en formato HH:MM</p>
                            <input id="edit-horario-input" type="time" class="w-2/3 p-2 border border-gray-300 rounded-lg mb-6 text-center" required value="${currentTime}" />
                            <div class="flex justify-center gap-4">
                                <button id="edit-horario-cancel" class="bg-gray-300 text-gray-700 py-2 px-6 rounded-lg">Cancelar</button>
                                <button id="edit-horario-accept" class="bg-blue-600 text-white py-2 px-6 rounded-lg">Actualizar</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    document.getElementById('edit-horario-cancel').onclick = () => modal.remove();
                    document.getElementById('edit-horario-accept').onclick = () => {
                        const value = document.getElementById('edit-horario-input').value;
                        modal.remove();
                        onEdit(value);
                    };
                    document.getElementById('edit-horario-input').focus();
                }
        }

        async function deleteSlot(slotId) { // borrar franja
            showConfirmModal('¿Está seguro de que desea eliminar este horario?', async () => {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_slot&slotId=${slotId}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        availabilityData[selectedDateStr] = availabilityData[selectedDateStr].filter(s => s.id !== slotId);
                        displayTimeSlots();
                        generateCalendar();
                        showSuccessModal('Se eliminó el horario correctamente.', 'success');
                        if (window.successModalTimeout) clearTimeout(window.successModalTimeout);
                        window.successModalTimeout = setTimeout(hideSuccessModal, 2000);
                    } else {
                        showSuccessModal('Error al eliminar el horario', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting slot:', error);
                    showSuccessModal('Error al eliminar el horario', 'error');
                }
            });
        }

        async function addCustomSlot() {
            if (!selectedDateStr) {
                showSuccessModal('Por favor seleccione una fecha primero', 'error');
                return;
            }

            showAddHorarioModal(async (newTime) => {
                if (newTime && /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(newTime)) {
                    try {
                        const response = await fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=add_custom_slot&date=${selectedDateStr}&time=${newTime}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            await loadAvailabilityData(selectedDateStr);
                            displayTimeSlots();
                            generateCalendar();
                            showSuccessModal('Horario agregado exitosamente.','success');
                        } else {
                            showSuccessModal('Ya existe un horario con esa hora o ocurrió un error','error');
                        }
                    } catch (error) {
                        console.error('Error adding custom slot:', error);
                        showSuccessModal('Error al agregar el horario','error');
                    }
                } else if (newTime) {
                    showSuccessModal('Formato de hora inválido. Use HH:MM (ejemplo: 09:30)','error');
                }
            });
                // Modal  para ingresar horario
                function showAddHorarioModal(onAdd, minTime = '00:00') {
                    if (document.getElementById('add-horario-modal')) {
                        document.getElementById('add-horario-modal').remove();
                    }
                    const modal = document.createElement('div');
                    modal.id = 'add-horario-modal';
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101]';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                            <i class="fas fa-clock text-4xl text-blue-500 mb-4"></i>
                            <h3 class="text-lg font-bold mb-2">Agregar horario</h3>
                            <p class="text-gray-600 mb-4">Ingrese el horario en formato HH:MM (ejemplo: 09:30)</p>
                            <input id="add-horario-input" type="time" class="w-2/3 p-2 border border-gray-300 rounded-lg mb-6 text-center" required min="${minTime}" />
                            <div class="flex justify-center gap-4">
                                <button id="add-horario-cancel" class="bg-gray-300 text-gray-700 py-2 px-6 rounded-lg">Cancelar</button>
                                <button id="add-horario-accept" class="bg-green-600 text-white py-2 px-6 rounded-lg">Agregar</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    document.getElementById('add-horario-cancel').onclick = () => modal.remove();
                    document.getElementById('add-horario-accept').onclick = () => {
                        const value = document.getElementById('add-horario-input').value;
                        modal.remove();
                        onAdd(value);
                    };
                    document.getElementById('add-horario-input').focus();
                }
        }

        async function clearAllSlots() { //eliminar todos los datos
            if (!selectedDateStr) {
                showSuccessModal('Por favor seleccione una fecha primero', 'error');
                return;
            }

            // Modal de confirmación bonito
            showConfirmModal('¿Está seguro de que desea eliminar todos los horarios de esta fecha?', async () => {
                const success = await saveSlotsToDatabase(selectedDateStr, []);
                if (success) {
                    delete availabilityData[selectedDateStr];
                    displayTimeSlots();
                    generateCalendar();
                    showSuccessModal('Todos los horarios han sido eliminados exitosamente.', 'success');
                } else {
                    showSuccessModal('Error al eliminar los horarios', 'error');
                }
            });
        }

        // inicializar el calendario
        initCalendar();
    </script>
</body>
</html>
