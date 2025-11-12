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
    <title>Mis Citas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        .appointment-card {
            transition: all 0.2s ease;
        }
        .appointment-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .bg-dental-gradient { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); }
        .dental-accent { color: #0077b6; }
    </style>
</head>

<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 font-sans">
    <!-- Header -->
    <header class="bg-dental-gradient text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="../../MODULO2/HTML/veragendas.php">
                        <img src="../../assets/img/logo-writopeks.jpg" alt="Writo Peks Consultorio Dental" class="h-8 w-auto">
                    </a>
                    <h1 class="text-xl font-bold">Consultorio Dental Dentix</h1>
                    <nav class="hidden md:flex space-x-4 ml-8">
                        <button onclick="window.location.href='../../MODULO2/HTML/miscitas.php'" class="text-white font-bold hover:text-gray-200 transition-colors">Mis citas</button>
                        <button onclick="window.location.href='../../MODULO2/HTML/veragendas.php'" class="text-white font-bold hover:text-gray-200 transition-colors">Reservar citas</button>
                        <button onclick="window.location.href='../../MODULO1/HTML/miperfil.php'" class="text-white font-bold hover:text-gray-200 transition-colors">Mi perfil</button>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></span>
                    <button onclick="confirmarCerrarSesion()" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="min-h-full p-6">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Mis Citas</h1>
                        <p class="text-gray-600">Visualice todas sus citas reservadas</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600" id="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <!-- Search and Appointments List -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">📋 Lista de Citas Reservadas</h2>
                    <div class="flex items-center space-x-4">
                        <label for="searchDate" class="text-sm text-gray-600">Buscar por fecha:</label>
                        <input type="date" id="searchDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button onclick="filterAppointments()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">Buscar</button>
                        <button onclick="clearSearch()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">Limpiar</button>
                    </div>
                </div>
                <div id="appointmentsList" class="space-y-4">
                    <!-- Appointments will be generated here -->
                </div>
            </div>
        </div>
    </div>

        <!-- Modal de Cancelación de Cita -->
    <div id="modal-cancelar-cita" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">❌ Cancelar Cita</h3>
                <button onclick="cerrarModalCancelar()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
            </div>
            <p class="text-gray-600 mb-4">Por favor, indique el motivo de la cancelación:</p>
            <textarea id="motivo_cancelacion" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 mb-4" placeholder="Escriba aquí el motivo..."></textarea>
            <div class="flex space-x-3 justify-end">
                <button onclick="cerrarModalCancelar()" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Volver
                </button>
                <button onclick="confirmarCancelacion()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Confirmar Cancelación
                </button>
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
        let reservedAppointments = [];

        // ✅ Cargar citas desde la base de datos
        async function loadAppointments() {
            try {
                const response = await fetch('../../api/obtener_mis_citas.php');
                const data = await response.json();
                
                console.log('📡 Datos recibidos de la API:', data); // DEBUG
                
                if(data.success) {
                    reservedAppointments = data.citas;
                    console.log('📋 Citas cargadas:', reservedAppointments); // DEBUG
                    generateAppointmentsList();
                } else {
                    showErrorMessage(data.message || 'Error al cargar citas');
                }
            } catch(error) {
                console.error('Error:', error);
                showErrorMessage('Error al conectar con el servidor');
            }
        }

        // Initialize the page
        function initPage() {
            updateCurrentDate();
            loadAppointments();
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

        function filterAppointments() {
            const searchDate = document.getElementById('searchDate').value;
            generateAppointmentsList(searchDate);
        }

        function clearSearch() {
            document.getElementById('searchDate').value = '';
            generateAppointmentsList();
        }

        let citaACancelar = null;

        function abrirModalCancelar(id_cita) {
            citaACancelar = id_cita;
            document.getElementById('modal-cancelar-cita').classList.remove('hidden');
            document.getElementById('modal-cancelar-cita').classList.add('flex');
            document.getElementById('motivo_cancelacion').value = '';
        }

        function cerrarModalCancelar() {
            citaACancelar = null;
            document.getElementById('modal-cancelar-cita').classList.add('hidden');
            document.getElementById('modal-cancelar-cita').classList.remove('flex');
        }

        async function confirmarCancelacion() {
            const motivo = document.getElementById('motivo_cancelacion').value.trim();
            
            if(motivo === '') {
                alert('Por favor, indique el motivo de la cancelación');
                return;
            }
            
            try {
                const response = await fetch('../../api/cancelar_cita.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_cita: citaACancelar,
                        motivo_cancelacion: motivo
                    })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    cerrarModalCancelar();
                    showSuccessMessage('La cita ha sido cancelada exitosamente');
                    await loadAppointments();
                } else {
                    showErrorMessage(data.message || 'Error al cancelar la cita');
                }
            } catch(error) {
                console.error('Error:', error);
                showErrorMessage('Error al conectar con el servidor');
            }
        }

        function showSuccessMessage(message) {
            alert(message);
        }

        function showErrorMessage(message) {
            alert(message);
        }

        function generateAppointmentsList(filterDate = '') {
            const appointmentsList = document.getElementById('appointmentsList');
            appointmentsList.innerHTML = '';

            let filteredAppointments = reservedAppointments;
            if (filterDate) {
                filteredAppointments = reservedAppointments.filter(appointment => {
                    console.log('Comparando:', appointment.fecha, 'con', filterDate); // DEBUG
                    return appointment.fecha === filterDate;
                });
            }

            console.log('🔍 Fecha buscada:', filterDate);
            console.log('📋 Citas encontradas:', filteredAppointments);

            if (filteredAppointments.length === 0) {
                const noAppointments = document.createElement('div');
                noAppointments.className = 'text-center text-gray-500 py-8';
                noAppointments.textContent = filterDate ? 'No hay citas para la fecha seleccionada' : 'No tiene citas reservadas';
                appointmentsList.appendChild(noAppointments);
                return;
            }

    filteredAppointments.forEach(appointment => {
        const appointmentCard = document.createElement('div');
        
        // Determinar color según estado
        let estadoColor = 'bg-yellow-50 border-yellow-200';
        let estadoTexto = 'text-yellow-800';
        
        switch(appointment.estado) {
            case 'Confirmada':
                estadoColor = 'bg-green-50 border-green-200';
                estadoTexto = 'text-green-800';
                break;
            case 'Rechazada':
                estadoColor = 'bg-red-50 border-red-200';
                estadoTexto = 'text-red-800';
                break;
            case 'Cancelada':
                estadoColor = 'bg-gray-50 border-gray-200';
                estadoTexto = 'text-gray-800';
                break;
            case 'Asistida':
                estadoColor = 'bg-blue-50 border-blue-200';
                estadoTexto = 'text-blue-800';
                break;
            case 'No Asistio':
                estadoColor = 'bg-orange-50 border-orange-200';
                estadoTexto = 'text-orange-800';
                break;
        }
        
        appointmentCard.className = `appointment-card ${estadoColor} rounded-lg p-4 border`;

        // Parsear fecha sin zona horaria para evitar problemas de conversión
        const [year, month, day] = appointment.fecha.split('-');
        const dateObj = new Date(year, month - 1, day); // month es 0-indexado
        const dateFormatted = dateObj.toLocaleDateString('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        appointmentCard.innerHTML = `
            <div class="flex items-center space-x-2 mb-2">
                <span class="text-sm text-gray-600">📅 Fecha:</span>
                <span class="font-semibold text-gray-800">${dateFormatted}</span>
            </div>
            <div class="flex items-center space-x-2 mb-2">
                <span class="text-sm text-gray-600">🕐 Hora:</span>
                <span class="font-bold text-lg text-gray-800">${appointment.hora}</span>
            </div>
            <div class="flex items-center space-x-2 mb-2">
                <span class="text-sm text-gray-600">🦷 Tipo de cita:</span>
                <span class="text-gray-800">${appointment.servicio}</span>
            </div>
            <div class="flex items-center space-x-2 mb-2">
                <span class="text-sm text-gray-600">📋 Estado:</span>
                <span class="font-semibold ${estadoTexto}">${appointment.estado}</span>
            </div>
            <div class="flex justify-end mt-3">
                <button onclick="abrirModalCancelar(${appointment.id_cita})" 
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors ${appointment.estado !== 'Pendiente' ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${appointment.estado !== 'Pendiente' ? 'disabled' : ''}>
                    ❌ Cancelar Cita
                </button>
            </div>
            ${appointment.motivo_cancelacion ? `
                <div class="mt-2 p-2 bg-white rounded border border-gray-300">
                    <span class="text-sm text-gray-600">Motivo: </span>
                    <span class="text-sm text-gray-800">${appointment.motivo_cancelacion}</span>
                </div>
            ` : ''}
        `;

       appointmentsList.appendChild(appointmentCard);
    });
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

        // Initialize the application
        initPage();
    </script>
</body>
</html>
