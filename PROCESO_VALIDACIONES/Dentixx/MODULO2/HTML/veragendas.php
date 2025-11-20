<?php
session_start();

// 1. Seguridad: Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// 2. Seguridad: Verificar ROL (NUEVO)
// Si el usuario es Odontólogo, no debe estar aquí. Lo redirigimos a su panel.
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Odontologo') {
    header('Location: ../../MODULO3/HTML/dashboard.php');
    exit();
}

// 2. Obtener datos del usuario
require_once '../../config/database.php';
$db = getDBConnection();
$stmt = $db->prepare("SELECT nombre, apellidos FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Dentix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body { box-sizing: border-box; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #bfdbfe; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #60a5fa; }
    </style>
</head>
<body class="h-full bg-gray-50 flex flex-col">

    <main class="flex-1 p-6 w-full">
        <div class="max-w-5xl mx-auto bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            
            <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
                <h2 id="monthYear" class="text-2xl font-bold text-gray-800 capitalize"></h2>
                
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        Hoy: <span id="currentDate" class="font-semibold text-blue-600 capitalize"></span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="previousMonth()" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors border border-gray-200">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button onclick="nextMonth()" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors border border-gray-200">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-7 gap-1 text-center font-semibold text-gray-500 text-sm mb-4 uppercase tracking-wide">
                <div>Dom</div><div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div>
            </div>
            
            <div id="loadingCalendar" class="text-center py-20 hidden">
                <i class="fas fa-spinner fa-spin text-blue-600 text-4xl"></i>
                <p class="mt-4 text-gray-500">Cargando horarios disponibles...</p>
            </div>
            
            <div id="calendarGrid" class="grid grid-cols-7 gap-3">
                </div>
        </div>
    </main>

    <div id="instructionsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full mx-4 relative">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-check text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">¿Cómo agendar tu cita?</h3>
            </div>
            
            <div class="space-y-4 mb-8">
                <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3 flex-shrink-0">1</div>
                    <p class="text-gray-700 text-sm pt-1">Selecciona un <strong>día disponible</strong> en el calendario (los recuadros blancos).</p>
                </div>
                <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3 flex-shrink-0">2</div>
                    <p class="text-gray-700 text-sm pt-1">Elige el <strong>horario</strong> que prefieras de la lista.</p>
                </div>
                <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3 flex-shrink-0">3</div>
                    <p class="text-gray-700 text-sm pt-1">Selecciona el <strong>tratamiento</strong> y confirma. Recibirás un correo con el estado de tu solicitud.</p>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <label class="flex items-center space-x-2 cursor-pointer group">
                    <input type="checkbox" id="dontShowAgain" class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <span class="text-sm text-gray-500 group-hover:text-gray-700 transition-colors">No volver a mostrar</span>
                </label>
                <button onclick="closeInstructionsModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-lg shadow-blue-200">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <div id="timeSlotsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg transform transition-all p-6 relative">
            <button onclick="closeTimeSlotsModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="text-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800 capitalize" id="slotsModalDate"></h3>
                <p class="text-gray-500 mt-1">Horarios disponibles</p>
            </div>

            <div id="slotsContainer" class="grid grid-cols-3 sm:grid-cols-4 gap-3 max-h-80 overflow-y-auto custom-scrollbar p-2">
                </div>
            
            <div id="noSlotsMessage" class="hidden text-center py-8 text-gray-500">
                <i class="fas fa-clock text-3xl mb-2 block opacity-30"></i>
                No hay horarios disponibles para este día.
            </div>
        </div>
    </div>

    <div id="appointmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">📅 Confirmar Reserva</h3>
                <button onclick="closeAppointmentModal()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">×</button>
            </div>
            
            <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-100">
                <p class="text-gray-600 text-sm mb-1">Fecha seleccionada:</p>
                <p class="text-gray-900 font-semibold text-lg capitalize" id="modalDate"></p>
                <div class="h-px bg-gray-200 my-2"></div>
                <p class="text-gray-600 text-sm mb-1">Hora:</p>
                <p class="text-blue-600 font-bold text-xl" id="modalTime"></p>
            </div>

            <label class="block text-sm font-medium text-gray-700 mb-2">Seleccione el servicio:</label>
            <div class="relative mb-6">
                <select id="servicioSelect" class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none bg-white">
                    <option value="" disabled selected>-- Elija un tratamiento --</option>
                    <option value="Consulta y diagnóstico dental">Consulta y diagnóstico dental</option>
                    <option value="Limpieza dental (profilaxis)">Limpieza dental (profilaxis)</option>
                    <option value="Colocación de selladores">Colocación de selladores</option>
                    <option value="Obturaciones o resinas">Obturaciones o resinas</option>
                    <option value="Extracción o cirugía dental">Extracción o cirugía dental</option>
                    <option value="Coronas">Coronas</option>
                    <option value="Prótesis fija y removible">Prótesis fija y removible</option>
                    <option value="Implantes">Implantes</option>
                    <option value="Ortodoncia (brackets)">Ortodoncia (brackets)</option>
                    <option value="Ortopedia dento facial">Ortopedia dento facial</option>
                    <option value="Blanqueamiento dental">Blanqueamiento dental</option>
                    <option value="Tratamientos infantiles">Tratamientos infantiles</option>
                    <option value="Otro tratamiento">Otro tratamiento</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="flex space-x-3">
                <button onclick="closeAppointmentModal()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                    Cancelar
                </button>
                <button id="btn-confirmar-reserva" onclick="reservarCita()" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-bold shadow-lg shadow-blue-200">
                    Enviar Solicitud
                </button>
            </div>
        </div>
    </div>

    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-2xl text-center">
            <i id="alert-modal-icon" class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
            <h3 id="alert-modal-title" class="text-lg font-bold text-gray-900 mb-2">Éxito</h3>
            <p id="alert-modal-message" class="text-gray-600 mb-6">Operación realizada.</p>
            <button onclick="document.getElementById('alert-modal').classList.add('hidden')" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                Entendido
            </button>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        let availableSlots = {};
        let currentSelectedDateStr = '';
        let currentSelectedDateObj = null;
        let selectedFranjaId = null;

        // --- Inicialización ---
        document.addEventListener('DOMContentLoaded', function() {
            initCalendar();
            checkInstructions(); // Verificar si mostrar instrucciones
        });

        // --- Lógica de Instrucciones (LocalStorage) ---
        function checkInstructions() {
            // Si no existe la clave en localStorage, mostramos el modal
            if (!localStorage.getItem('dentix_hide_instructions')) {
                showInstructionsModal();
            }
        }

        function showInstructionsModal(force = false) {
            // Si se fuerza (botón de ayuda) o no se ha ocultado
            if (force || !localStorage.getItem('dentix_hide_instructions')) {
                const modal = document.getElementById('instructionsModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeInstructionsModal() {
            const checkbox = document.getElementById('dontShowAgain');
            if (checkbox.checked) {
                localStorage.setItem('dentix_hide_instructions', 'true');
            }
            
            const modal = document.getElementById('instructionsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // --- Carga de Datos ---
        async function loadAvailableSlots() {
            showLoading();
            try {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth() + 1;
                
                const response = await fetch(`../../api/obtener_horarios.php?mes=${month}&anio=${year}`);
                const data = await response.json();
                
                if(data.success) {
                    availableSlots = data.horarios;
                    generateCalendar();
                } else {
                    showErrorMessage(data.message || 'Error al cargar horarios');
                }
            } catch(error) {
                console.error('Error:', error);
                showErrorMessage('Error al conectar con el servidor');
            } finally {
                hideLoading();
            }
        }

        function showLoading() {
            document.getElementById('loadingCalendar').classList.remove('hidden');
            document.getElementById('calendarGrid').classList.add('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingCalendar').classList.add('hidden');
            document.getElementById('calendarGrid').classList.remove('hidden');
        }

        function initCalendar() {
            updateCurrentDate();
            loadAvailableSlots();
        }

        function updateCurrentDate() {
            const today = new Date();
            const el = document.getElementById('currentDate');
            if(el) el.textContent = today.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        // --- Generación del Calendario (LÓGICA MEJORADA DE CUPOS) ---
        function generateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('monthYear').textContent = currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            // Fecha de hoy normalizada
            const now = new Date();
            const todayNormalized = new Date();
            todayNormalized.setHours(0, 0, 0, 0);

            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const cellDateNormalized = new Date(date);
                cellDateNormalized.setHours(0, 0, 0, 0);

                const dateStr = date.toISOString().split('T')[0];
                const isCurrentMonth = date.getMonth() === month;
                const isToday = date.toDateString() === new Date().toDateString();
                const isPastDay = cellDateNormalized < todayNormalized;

                const slots = availableSlots[dateStr] || [];
                
                // Calcular cupos VÁLIDOS (ignorando horas pasadas)
                let validSlotsCount = 0;
                if (!isPastDay) {
                    if (isToday) {
                        const currentHour = now.getHours();
                        const currentMinute = now.getMinutes();
                        validSlotsCount = slots.filter(slot => {
                            const [h, m] = slot.hora.split(':').map(Number);
                            // Solo contar si la hora es futura
                            return (h > currentHour || (h === currentHour && m > currentMinute));
                        }).length;
                    } else {
                        validSlotsCount = slots.length;
                    }
                }

                const dayElement = document.createElement('div');
                
                // Estilos
                let classes = "rounded-xl p-3 flex flex-col justify-between h-28 border transition-all duration-200 ";
                if (isPastDay) {
                    classes += "bg-gray-50 border-gray-100 text-gray-300 cursor-not-allowed";
                } else if (isCurrentMonth) {
                    classes += "bg-white border-gray-200 cursor-pointer hover:border-blue-300 hover:shadow-md";
                    if (isToday) classes += " ring-2 ring-blue-500 ring-offset-2";
                    // Evento Click solo si no es pasado
                    dayElement.onclick = () => openTimeSlotsModal(dateStr, date, slots);
                } else {
                    classes += "bg-gray-50 border-transparent text-gray-300"; 
                }

                dayElement.className = classes;
                
                // Número del día
                let dayHTML = `<span class="text-lg font-semibold">${date.getDate()}</span>`;
                if (isToday) dayHTML = `<span class="bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full font-bold">${date.getDate()}</span>`;
                
                // Indicador de cupos
                let slotsIndicator = '';
                if (!isPastDay && isCurrentMonth) {
                    if (validSlotsCount > 0) {
                        slotsIndicator = `
                            <div class="mt-2">
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-md font-medium block text-center">
                                    ${validSlotsCount} cupos
                                </span>
                            </div>`;
                    } else {
                        slotsIndicator = `<div class="mt-2 text-center text-xs text-gray-400 italic">Sin cupos</div>`;
                    }
                }

                dayElement.innerHTML = `
                    <div class="flex justify-between items-start">${dayHTML}</div>
                    ${slotsIndicator}
                `;
                
                calendarGrid.appendChild(dayElement);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadAvailableSlots();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadAvailableSlots();
        }

        // --- MODAL 1: LISTA DE HORARIOS ---
        function openTimeSlotsModal(dateStr, dateObj, slots) {
            currentSelectedDateStr = dateStr;
            currentSelectedDateObj = dateObj;
            
            const modal = document.getElementById('timeSlotsModal');
            const container = document.getElementById('slotsContainer');
            const title = document.getElementById('slotsModalDate');
            const noSlotsMsg = document.getElementById('noSlotsMessage');
            
            title.textContent = dateObj.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
            container.innerHTML = '';

            const now = new Date();
            const isToday = dateObj.toDateString() === now.toDateString();
            
            let validSlotsFound = false;

            slots.forEach(slot => {
                const [slotHour, slotMinute] = slot.hora.split(':').map(Number);
                let isPastTime = false;
                
                // Validación estricta de hora
                if (isToday) {
                    if (slotHour < now.getHours() || (slotHour === now.getHours() && slotMinute <= now.getMinutes())) {
                        isPastTime = true;
                    }
                }

                if (!isPastTime) {
                    validSlotsFound = true;
                    const btn = document.createElement('button');
                    btn.className = "py-3 px-2 bg-white border border-blue-200 text-blue-600 rounded-xl font-semibold hover:bg-blue-50 hover:border-blue-400 transition-all shadow-sm";
                    btn.textContent = slot.hora;
                    btn.onclick = () => openReservationModal(slot.hora, slot.id_franja);
                    container.appendChild(btn);
                }
            });

            if (!validSlotsFound) {
                noSlotsMsg.classList.remove('hidden');
                container.classList.add('hidden');
            } else {
                noSlotsMsg.classList.add('hidden');
                container.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTimeSlotsModal() {
            const modal = document.getElementById('timeSlotsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // --- MODAL 2: CONFIRMACIÓN ---
        function openReservationModal(time, franjaId) {
            closeTimeSlotsModal();
            selectedFranjaId = franjaId;
            const modal = document.getElementById('appointmentModal');
            
            const dateFormatted = currentSelectedDateObj.toLocaleDateString('es-ES', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
            
            document.getElementById('modalDate').innerText = `Fecha: ${dateFormatted}`;
            document.getElementById('modalTime').innerText = `Hora: ${time}`;
            
            const button = document.getElementById('btn-confirmar-reserva');
            button.disabled = false;
            button.innerHTML = 'Enviar Solicitud';
            document.getElementById('servicioSelect').selectedIndex = 0;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAppointmentModal() {
            document.getElementById('appointmentModal').classList.add('hidden');
            document.getElementById('appointmentModal').classList.remove('flex');
            // Reabrir horarios si se cancela
            if (currentSelectedDateStr) {
                const slots = availableSlots[currentSelectedDateStr] || [];
                openTimeSlotsModal(currentSelectedDateStr, currentSelectedDateObj, slots);
            }
        }

        async function reservarCita() {
            const servicio = document.getElementById('servicioSelect').value;
            if (!servicio) {
                showErrorMessage('Por favor, seleccione un tratamiento.');
                return;
            }

            const button = document.getElementById('btn-confirmar-reserva');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando solicitud...';

            try {
                const response = await fetch('../../api/reservar_cita.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_franja: selectedFranjaId,
                        servicio: servicio
                    })
                });
                
                const data = await response.json();
                
                // Cerrar modal de reserva definitivamente
                document.getElementById('appointmentModal').classList.add('hidden');
                document.getElementById('appointmentModal').classList.remove('flex');
                currentSelectedDateStr = ''; 
                
                if (data.success) {
                    showSuccessMessage('Solicitud enviada al odontólogo. Espere la confirmación en su correo electrónico.');
                    loadAvailableSlots(); 
                } else {
                    showErrorMessage(data.message);
                }
                
            } catch (error) {
                document.getElementById('appointmentModal').classList.add('hidden');
                showErrorMessage('Error de conexión al enviar la solicitud.');
            } 
        }

        // --- Alertas ---
        function showSuccessMessage(message) {
            const modal = document.getElementById('alert-modal');
            document.getElementById('alert-modal-icon').className = "fas fa-check-circle text-4xl text-green-500 mb-4";
            document.getElementById('alert-modal-title').innerText = "Solicitud Enviada";
            document.getElementById('alert-modal-message').innerText = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function showErrorMessage(message) {
            const modal = document.getElementById('alert-modal');
            document.getElementById('alert-modal-icon').className = "fas fa-exclamation-triangle text-4xl text-red-500 mb-4";
            document.getElementById('alert-modal-title').innerText = "Atención";
            document.getElementById('alert-modal-message').innerText = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function hideAlertModal() {
            document.getElementById('alert-modal').classList.add('hidden');
        }
    </script>
</body>
</html>