<?php
session_start();

// 1. Seguridad: Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// 2. Obtener datos del usuario (solo para verificar que existe)
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
    <title>Mis Citas - Dentix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body { box-sizing: border-box; }
        .appointment-card { transition: all 0.3s ease; }
        .appointment-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .filter-btn.active { background-color: #2563eb; color: white; border-color: #2563eb; }
        .filter-btn:not(.active) { background-color: white; color: #4b5563; border-color: #e5e7eb; }
        .filter-btn:not(.active):hover { background-color: #f3f4f6; }
    </style>
</head>
<body class="h-full bg-gray-50 flex flex-col">
    

    <main class="flex-1 p-6 w-full">
        <div class="max-w-7xl mx-auto">
            
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    
                    <div class="flex flex-wrap gap-2 w-full md:w-auto justify-center md:justify-start" id="statusFilters">
                        <button onclick="setFilter('all')" class="filter-btn active px-4 py-2 rounded-full border text-sm font-semibold transition-all shadow-sm flex-1 md:flex-none text-center">
                            Todas
                        </button>
                        <button onclick="setFilter('Pendiente')" class="filter-btn px-4 py-2 rounded-full border text-sm font-semibold transition-all shadow-sm flex-1 md:flex-none text-center">
                            <i class="fas fa-clock mr-1 text-yellow-500"></i> Pendientes
                        </button>
                        <button onclick="setFilter('Confirmada')" class="filter-btn px-4 py-2 rounded-full border text-sm font-semibold transition-all shadow-sm flex-1 md:flex-none text-center">
                            <i class="fas fa-check-circle mr-1 text-green-500"></i> Confirmadas
                        </button>
                        <button onclick="setFilter('Cancelada')" class="filter-btn px-4 py-2 rounded-full border text-sm font-semibold transition-all shadow-sm flex-1 md:flex-none text-center">
                            <i class="fas fa-times-circle mr-1 text-red-500"></i> Canceladas
                        </button>
                    </div>

                    <div class="flex items-center space-x-4 w-full md:w-auto justify-center md:justify-end">
                        <div class="relative">
                            <i class="fas fa-calendar absolute left-3 top-2.5 text-gray-400"></i>
                            <input type="date" id="searchDate" onchange="filterAppointments()" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <button onclick="clearSearch()" class="p-2 text-gray-500 hover:text-blue-600 transition-colors" title="Limpiar fecha">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="hidden sm:block border-l border-gray-300 h-8 mx-2"></div>
                        <div class="text-right hidden sm:block">
                            <p class="text-xs text-gray-500">Hoy</p>
                            <p class="text-sm font-bold text-blue-600" id="currentDate"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="appointmentsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                </div>
        </div>
    </main>

    <div id="modal-cancelar-cita" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-2xl transform transition-all">
            <form id="form-cancelar" onsubmit="confirmarCancelacion(event)">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Cancelar Cita</h3>
                    <p class="text-gray-600 mb-4 text-sm">¿Estás seguro de que quieres cancelar esta cita? Esta acción notificará al odontólogo.</p>
                    
                    <div class="text-left mb-4">
                        <label for="motivo_cancelacion" class="block text-sm font-medium text-gray-700 mb-1">Motivo (Requerido):</label>
                        <textarea id="motivo_cancelacion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-sm" placeholder="Ej: Surgió un imprevisto..."></textarea>
                        <div id="cancel-reason-error" class="hidden text-red-600 text-xs mt-1 font-semibold">
                            <i class="fas fa-info-circle mr-1"></i> Por favor, ingresa un motivo.
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit" id="btn-confirm-cancel" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors flex justify-center items-center">
                            Sí, Cancelar Cita
                        </button>
                        <button type="button" onclick="cerrarModalCancelar()" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors">
                            Volver
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-2xl text-center">
            <i id="alert-icon" class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
            <h3 id="alert-title" class="text-lg font-bold text-gray-900 mb-2">Éxito</h3>
            <p id="alert-message" class="text-gray-600 mb-6">Operación realizada.</p>
            <button onclick="document.getElementById('alert-modal').classList.add('hidden')" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                Entendido
            </button>
        </div>
    </div>

    <script>
        let reservedAppointments = [];
        let currentStatusFilter = 'all'; 
        let citaACancelar = null;

        // --- CARGA DE DATOS ---
        async function loadAppointments() {
            try {
                const response = await fetch('../../api/obtener_mis_citas.php');
                const data = await response.json();
                if(data.success) {
                    reservedAppointments = data.citas;
                    generateAppointmentsList(); 
                } else {
                    mostrarAlertaCustom('error', 'Error', data.message || 'Error al cargar citas');
                }
            } catch(error) {
                console.error('Error:', error);
                mostrarAlertaCustom('error', 'Error de Conexión', 'No se pudo conectar con el servidor.');
            }
        }

        // --- LÓGICA DE FILTROS ---
        function setFilter(status) {
            currentStatusFilter = status;
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => {
                if (btn.getAttribute('onclick').includes(status)) { btn.classList.add('active'); } 
                else { btn.classList.remove('active'); }
            });
            const searchDate = document.getElementById('searchDate').value;
            generateAppointmentsList(searchDate);
        }

        function filterAppointments() {
            const searchDate = document.getElementById('searchDate').value;
            generateAppointmentsList(searchDate);
        }

        function clearSearch() {
            document.getElementById('searchDate').value = '';
            generateAppointmentsList();
        }

        // --- LÓGICA DE CANCELACIÓN ---
        function abrirModalCancelar(id_cita) {
            citaACancelar = id_cita;
            document.getElementById('motivo_cancelacion').value = '';
            document.getElementById('cancel-reason-error').classList.add('hidden');
            const btn = document.getElementById('btn-confirm-cancel');
            btn.disabled = false;
            btn.innerHTML = 'Sí, Cancelar Cita';
            document.getElementById('modal-cancelar-cita').classList.remove('hidden');
            document.getElementById('modal-cancelar-cita').classList.add('flex');
        }

        function cerrarModalCancelar() {
            citaACancelar = null;
            document.getElementById('modal-cancelar-cita').classList.add('hidden');
            document.getElementById('modal-cancelar-cita').classList.remove('flex');
        }

        async function confirmarCancelacion(event) {
            event.preventDefault(); 
            const motivo = document.getElementById('motivo_cancelacion').value.trim();
            const errorDiv = document.getElementById('cancel-reason-error');
            
            if(motivo === '') { errorDiv.classList.remove('hidden'); return; }
            errorDiv.classList.add('hidden');
            
            const btn = document.getElementById('btn-confirm-cancel');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';

            try {
                const response = await fetch('../../api/cancelar_cita.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_cita: citaACancelar, motivo_cancelacion: motivo })
                });
                const data = await response.json();
                cerrarModalCancelar();
                if(data.success) {
                    mostrarAlertaCustom('success', 'Cita Cancelada', data.message);
                    loadAppointments(); 
                } else {
                    mostrarAlertaCustom('error', 'Error', data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                cerrarModalCancelar();
                mostrarAlertaCustom('error', 'Error de Conexión', 'No se pudo conectar con el servidor.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Sí, Cancelar Cita';
            }
        }

        // --- GENERACIÓN DE LISTA ---
        function generateAppointmentsList(filterDate = '') {
            const appointmentsList = document.getElementById('appointmentsList');
            appointmentsList.innerHTML = '';

            let filtered = reservedAppointments;
            if (filterDate) { filtered = reservedAppointments.filter(app => app.fecha === filterDate); }
            if (currentStatusFilter !== 'all') { filtered = filtered.filter(app => app.estado === currentStatusFilter); }

            if (filtered.length === 0) {
                appointmentsList.innerHTML = `
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                        <i class="fas fa-calendar-times text-5xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No hay citas</h3>
                        <p class="text-gray-500 mt-1">No se encontraron citas con los filtros seleccionados.</p>
                    </div>`;
                return;
            }

            filtered.forEach(cita => {
                const card = document.createElement('div');
                const statusInfo = formatStatus(cita.estado);
                
                let cancelButton = '';
                if (cita.estado === 'Pendiente' || cita.estado === 'Confirmada') {
                    cancelButton = `
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button onclick="abrirModalCancelar(${cita.id_cita})" 
                                    class="w-full py-2 px-4 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors text-sm font-medium flex items-center justify-center">
                                <i class="fas fa-times-circle mr-2"></i> Cancelar Cita
                            </button>
                        </div>
                    `;
                }

                const [y, m, d] = cita.fecha.split('-');
                const dateObj = new Date(y, m-1, d);
                const dateStr = dateObj.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

                card.className = `bg-white p-5 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow relative overflow-hidden appointment-card`;
                card.innerHTML = `
                    <div class="absolute top-0 left-0 w-1 h-full ${statusInfo.borderColor}"></div>
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">${cita.servicio}</h3>
                            <p class="text-sm text-gray-500 capitalize">${dateStr}</p>
                        </div>
                        <span class="${statusInfo.badgeColor} ${statusInfo.textColor} px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                            ${cita.estado}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center"><i class="fas fa-clock w-6 text-center text-blue-500"></i> <span>${cita.hora}</span></div>
                        ${cita.motivo_cancelacion ? `<div class="flex items-start text-red-500 bg-red-50 p-2 rounded mt-2"><i class="fas fa-info-circle w-6 text-center mt-0.5"></i> <span class="text-xs">Motivo: ${cita.motivo_cancelacion}</span></div>` : ''}
                    </div>
                    ${cancelButton}
                `;
                appointmentsList.appendChild(card);
            });
        }

        function formatStatus(status) {
            switch(status) {
                case 'Confirmada': return { borderColor: 'bg-green-500', badgeColor: 'bg-green-100', textColor: 'text-green-700' };
                case 'Pendiente': return { borderColor: 'bg-yellow-500', badgeColor: 'bg-yellow-100', textColor: 'text-yellow-800' };
                case 'Rechazada': return { borderColor: 'bg-red-500', badgeColor: 'bg-red-100', textColor: 'text-red-700' };
                case 'Cancelada': return { borderColor: 'bg-gray-500', badgeColor: 'bg-gray-100', textColor: 'text-gray-700' };
                case 'Asistida': return { borderColor: 'bg-blue-500', badgeColor: 'bg-blue-100', textColor: 'text-blue-700' };
                default: return { borderColor: 'bg-gray-300', badgeColor: 'bg-gray-100', textColor: 'text-gray-600' };
            }
        }

        // --- Alerta Bonita ---
        function mostrarAlertaCustom(tipo, titulo, mensaje) {
            const modal = document.getElementById('alert-modal');
            const icon = document.getElementById('alert-icon');
            const title = document.getElementById('alert-title');
            const msg = document.getElementById('alert-message');

            if (tipo === 'success') {
                icon.className = 'fas fa-check-circle text-4xl text-green-500 mb-4';
            } else {
                icon.className = 'fas fa-times-circle text-4xl text-red-500 mb-4';
            }
            title.textContent = titulo;
            msg.textContent = mensaje;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Inicio
        function initPage() {
            updateCurrentDate();
            loadAppointments();
        }
        
        function updateCurrentDate() {
            const today = new Date();
            document.getElementById('currentDate').textContent = today.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        initPage();
    </script>
</body>
</html>