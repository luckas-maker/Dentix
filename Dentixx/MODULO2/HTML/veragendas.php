
<?php
session_start();

// Verificar autenticación con user_id
if(!isset($_SESSION['user_id'])) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// Obtener datos del usuario
require_once '../../config/database.php';
$db = getDBConnection();

$query = "SELECT nombre, apellidos FROM usuarios WHERE id_usuario = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// ✅ Verificar con el nombre correcto
if (!isset($_SESSION['user_id'])) {
    error_log("❌ Acceso denegado a veragendas.php - No hay user_id en sesión");
    error_log("Session ID: " . session_id());
    error_log("Contenido sesión: " . print_r($_SESSION, true));
    
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    $user_id = $_SESSION['user_id']; // ✅ Usar user_id
    
    error_log("✅ Acceso permitido a veragendas.php - User ID: " . $user_id);
    
    // Obtener datos del usuario
    $stmt = $db->prepare("SELECT nombre, apellidos FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        error_log("❌ Usuario no encontrado en BD con ID: " . $user_id);
        header('Location: ../../MODULO1/HTML/iniciosesion.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("❌ Error en veragendas.php: " . $e->getMessage());
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de citas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        .calendar-day {
            transition: all 0.2s ease;
        }
        .calendar-day:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .today {
            border: 2px solid #2563eb;
            background-color: #eff6ff;
        }
        .available-slot {
            border: 2px solid #10b981;
            background-color: #f0fdf4;
            margin-bottom: 2px;
            cursor: pointer;
        }
        .available-slot:hover {
            background-color: #dcfce7;
        }
        .bg-dental-gradient {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
        }
        .dental-accent {
            color: #0077b6;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 119, 182, 0.3);
            border-radius: 50%;
            border-top-color: #0077b6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 font-sans">
    
    <div class="min-h-full p-6">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Reserva de citas</h1>
                        <p class="text-gray-600">Visualice y reserve su cita en los horarios disponibles</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600" id="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <!-- Calendar Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">📅 Calendario de horarios disponibles</h2>
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
                
                <div id="loadingCalendar" class="text-center py-8">
                    <div class="loading mx-auto"></div>
                    <p class="text-gray-600 mt-2">Cargando horarios...</p>
                </div>
                
                <div class="grid grid-cols-7 gap-2 hidden" id="calendarGrid">
                    <!-- Calendar days will be generated here -->
                </div>
            </div>
        </div>
    </div>

    <div id="modal-cerrar-sesion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <i class="fas fa-sign-out-alt text-4xl text-red-500 mb-4"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-2">¿Cerrar Sesión?</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres cerrar tu sesión actual?</p>
            
            <div class="flex space-x-3">
                <button onclick="cerrarSesion()" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sí, cerrar sesión
                </button>
                <button onclick="cancelarCerrarSesion()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

    <script>
        let currentDate = new Date();
        let availableSlots = {};

        // ✅ Cargar horarios desde la base de datos
        async function loadAvailableSlots() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;
            
            try {
                const response = await fetch(`../../api/obtener_horarios.php?mes=${month}&anio=${year}`);
                const data = await response.json();
                
                if(data.success) {
                    availableSlots = data.horarios;
                    hideLoading();
                    generateCalendar();
                } else {
                    hideLoading();
                    showErrorMessage(data.message || 'Error al cargar horarios');
                }
            } catch(error) {
                console.error('Error:', error);
                hideLoading();
                showErrorMessage('Error al conectar con el servidor');
            }
        }

        // Mostrar/ocultar loading
        function showLoading() {
            document.getElementById('loadingCalendar').classList.remove('hidden');
            document.getElementById('calendarGrid').classList.add('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingCalendar').classList.add('hidden');
            document.getElementById('calendarGrid').classList.remove('hidden');
        }

        // Inicializar el calendario
        function initCalendar() {
            updateCurrentDate();
            loadAvailableSlots();
        }

        function updateCurrentDate() {
            const today = new Date();
            const currentDateElement = document.getElementById('currentDate');
            currentDateElement.textContent = today.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function generateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            const monthYearElement = document.getElementById('monthYear');
            monthYearElement.textContent = currentDate.toLocaleDateString('es-ES', {
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
                const slots = availableSlots[dateStr] || [];
                
                dayElement.className = `
                    p-2 rounded-lg transition-all hover:scale-105 min-h-32 border calendar-day
                    ${isCurrentMonth ? 'text-gray-800 bg-white border-gray-200' : 'text-gray-400 bg-gray-50 border-gray-100'}
                    ${isToday ? 'today' : ''}
                `;
                
                // Número del día
                const dayNumber = document.createElement('div');
                dayNumber.className = `text-sm font-semibold mb-1 ${isToday ? 'text-blue-600' : ''}`;
                dayNumber.textContent = date.getDate();
                dayElement.appendChild(dayNumber);
                
                // Horarios disponibles
                if (slots.length > 0) {
                    const slotsContainer = document.createElement('div');
                    slotsContainer.className = 'space-y-1';
                    slots.forEach(slot => {
                        const slotElement = document.createElement('div');
                        slotElement.className = 'available-slot text-xs px-1 py-0.5 rounded text-center text-green-800 font-medium';
                        slotElement.textContent = slot.hora;
                        slotElement.onclick = (e) => {
                            e.stopPropagation();
                            openReservationModal(dateStr, date, slot.hora, slot.id_franja);
                        };
                        slotsContainer.appendChild(slotElement);
                    });
                    dayElement.appendChild(slotsContainer);
                }
                
                calendarGrid.appendChild(dayElement);
            }
        }

        function openReservationModal(dateStr, date, time, id_franja) {
            const modalBackdrop = document.createElement('div');
            modalBackdrop.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modalBackdrop.onclick = (e) => {
                if (e.target === modalBackdrop) {
                    modalBackdrop.remove();
                }
            };

            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4';
            
            const dateFormatted = date.toLocaleDateString('es-ES', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            const modalHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📅 Reservar Cita</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                <p class="text-gray-600 mb-4">Fecha: ${dateFormatted} - Hora: ${time}</p>
                <label class="block text-sm font-medium text-gray-700 mb-2">Seleccione el servicio odontológico:</label>
                <select id="serviceSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
                    <option value="Consulta y diagnóstico dental">Consulta y diagnóstico dental</option>
                    <option value="Limpieza dental (profilaxis)">Limpieza dental (profilaxis)</option>
                    <option value="Colocación de selladores de fosetas y fisuras">Colocación de selladores de fosetas y fisuras</option>
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
                <div class="flex space-x-3 justify-end">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button id="confirmBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <span id="btnText">Reservar</span>
                        <span id="btnLoading" class="hidden">
                            <i class="fas fa-spinner fa-spin"></i> Reservando...
                        </span>
                    </button>
                </div>
            `;
            
            modalContent.innerHTML = modalHTML;
            modalBackdrop.appendChild(modalContent);
            document.body.appendChild(modalBackdrop);
            
            document.getElementById('confirmBtn').addEventListener('click', async () => {
                const selectedService = document.getElementById('serviceSelect').value;
                const btnText = document.getElementById('btnText');
                const btnLoading = document.getElementById('btnLoading');
                const confirmBtn = document.getElementById('confirmBtn');
                
                // Deshabilitar botón y mostrar loading
                confirmBtn.disabled = true;
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                
                await confirmReservation(id_franja, selectedService);
                modalBackdrop.remove();
            });
        }

        async function confirmReservation(id_franja, servicio) {
            try {
                const response = await fetch('../../api/reservar_cita.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_franja: id_franja,
                        servicio: servicio
                    })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    showSuccessMessage(`✅ Cita reservada exitosamente para ${servicio}`);
                    showLoading();
                    await loadAvailableSlots();
                } else {
                    showErrorMessage(data.message || 'Error al reservar la cita');
                }
            } catch(error) {
                console.error('Error:', error);
                showErrorMessage('Error al conectar con el servidor');
            }
        }

        function showSuccessMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform';
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showErrorMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform';
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            showLoading();
            loadAvailableSlots();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            showLoading();
            loadAvailableSlots();
        }

        function confirmarCerrarSesion() {
            document.getElementById('modal-cerrar-sesion').classList.remove('hidden');
            document.getElementById('modal-cerrar-sesion').classList.add('flex');
        }

        async function cerrarSesion() {
            try {
                // Primero, cerrar sesión en el servidor
                const response = await fetch('../../api/logout.php');
                const data = await response.json();
                
                if (data.success) {
                    // Limpiar datos locales
                    localStorage.clear();
                    
                    // Cerrar modal
                    document.getElementById('modal-cerrar-sesion').classList.add('hidden');
                    document.getElementById('modal-cerrar-sesion').classList.remove('flex');
                    
                    // Redirigir al inicio de sesión
                    window.location.href = '../../MODULO1/HTML/iniciosesion.php';
                } else {
                    alert('Error al cerrar sesión');
                }
            } catch (error) {
                console.error('Error:', error);
                // Fallback: limpiar localStorage y redirigir de todas formas
                localStorage.clear();
                window.location.href = '../../MODULO1/HTML/iniciosesion.php';
            }
        }

        function cancelarCerrarSesion() {
            document.getElementById('modal-cerrar-sesion').classList.add('hidden');
            document.getElementById('modal-cerrar-sesion').classList.remove('flex');
        }

        // ✅ Inicializar la aplicación
        initCalendar();
    </script>
</body>
</html>
